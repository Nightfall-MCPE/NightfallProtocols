<?php

declare(strict_types=1);

namespace Supero\NightfallProtocol\network\caches;

use pocketmine\crafting\CraftingManager;
use pocketmine\crafting\FurnaceType;
use pocketmine\crafting\ShapedRecipe;
use pocketmine\crafting\ShapelessRecipe;
use pocketmine\crafting\ShapelessRecipeType;
use pocketmine\data\bedrock\item\ItemTypeSerializeException;
use pocketmine\network\mcpe\protocol\CraftingDataPacket;
use pocketmine\network\mcpe\protocol\types\recipe\CraftingRecipeBlockName;
use pocketmine\network\mcpe\protocol\types\recipe\FurnaceRecipe as ProtocolFurnaceRecipe;
use pocketmine\network\mcpe\protocol\types\recipe\FurnaceRecipeBlockName;
use pocketmine\network\mcpe\protocol\types\recipe\IntIdMetaItemDescriptor;
use pocketmine\network\mcpe\protocol\types\recipe\PotionContainerChangeRecipe as ProtocolPotionContainerChangeRecipe;
use pocketmine\network\mcpe\protocol\types\recipe\PotionTypeRecipe as ProtocolPotionTypeRecipe;
use pocketmine\network\mcpe\protocol\types\recipe\RecipeUnlockingRequirement;
use pocketmine\timings\Timings;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\utils\Binary;
use pocketmine\utils\SingletonTrait;
use Ramsey\Uuid\Uuid;
use Supero\NightfallProtocol\network\packets\types\recipe\CustomShapedRecipe as ProtocolShapedRecipe;
use Supero\NightfallProtocol\network\packets\types\recipe\CustomShapelessRecipe as ProtocolShapelessRecipe;
use Supero\NightfallProtocol\network\static\convert\CustomTypeConverter;
use Supero\NightfallProtocol\network\static\data\bedrock\ItemTagDowngrader;

use function array_map;
use function spl_object_id;

class CustomCraftingDataCache
{
	use SingletonTrait;

	/**
	 * @var CraftingDataPacket[]
	 * @phpstan-var array<int, CraftingDataPacket>
	 */
	private array $caches = [];

	public function getCache(CraftingManager $manager, int $protocol) : CraftingDataPacket{
		$id = spl_object_id($manager);
		if(!isset($this->caches[$id])){
			$manager->getDestructorCallbacks()->add(function() use ($id) : void{
				unset($this->caches[$id]);
			});
			$manager->getRecipeRegisteredCallbacks()->add(function() use ($id) : void{
				unset($this->caches[$id]);
			});
			$this->caches[$id] = $this->buildCraftingDataCache($manager, $protocol);
		}
		return $this->caches[$id];
	}

	/**
	 * Rebuilds the cached CraftingDataPacket.
	 */
	private function buildCraftingDataCache(CraftingManager $manager, int $protocol) : CraftingDataPacket{
		Timings::$craftingDataCacheRebuild->startTiming();

		$nullUUID = Uuid::fromString(Uuid::NIL);
		$itemTagDowngrader = ItemTagDowngrader::getProtocolInstance($protocol);
		$converter = CustomTypeConverter::getProtocolInstance($protocol);
		$recipesWithTypeIds = [];

		$noUnlockingRequirement = new RecipeUnlockingRequirement(null);
		foreach($manager->getCraftingRecipeIndex() as $index => $recipe){
			try{
				if($recipe instanceof ShapelessRecipe){
					$typeTag = match($recipe->getType()){
						ShapelessRecipeType::CRAFTING => CraftingRecipeBlockName::CRAFTING_TABLE,
						ShapelessRecipeType::STONECUTTER => CraftingRecipeBlockName::STONECUTTER,
						ShapelessRecipeType::CARTOGRAPHY => CraftingRecipeBlockName::CARTOGRAPHY_TABLE,
						ShapelessRecipeType::SMITHING => CraftingRecipeBlockName::SMITHING_TABLE,
					};

					foreach($itemTagDowngrader->downgradeShapelessRecipe($recipe) as $r){
						$recipesWithTypeIds[] = new ProtocolShapelessRecipe(
							CraftingDataPacket::ENTRY_SHAPELESS,
							Binary::writeInt($index),
							array_map($converter->coreRecipeIngredientToNet(...), $r->getIngredientList()),
							array_map($converter->coreItemStackToNet(...), $r->getResults()),
							$nullUUID,
							$typeTag,
							50,
							$noUnlockingRequirement,
							$index
						);
					}
				}elseif($recipe instanceof ShapedRecipe){
					foreach($itemTagDowngrader->downgradeShapedRecipe($recipe) as $r){
						$inputs = [];

						for($row = 0, $height = $r->getHeight(); $row < $height; ++$row){
							for($column = 0, $width = $r->getWidth(); $column < $width; ++$column){
								$inputs[$row][$column] = $converter->coreRecipeIngredientToNet($r->getIngredient($column, $row));
							}
						}
						$recipesWithTypeIds[] = new ProtocolShapedRecipe(
							CraftingDataPacket::ENTRY_SHAPED,
							Binary::writeInt($index),
							$inputs,
							array_map($converter->coreItemStackToNet(...), $r->getResults()),
							$nullUUID,
							CraftingRecipeBlockName::CRAFTING_TABLE,
							50,
							true,
							$noUnlockingRequirement,
							$index,
						);
					}
				}
			}catch(\InvalidArgumentException|ItemTypeSerializeException){
				continue;
			}
		}

		foreach(FurnaceType::cases() as $furnaceType){
			try{
				$typeTag = match($furnaceType){
					FurnaceType::FURNACE => FurnaceRecipeBlockName::FURNACE,
					FurnaceType::BLAST_FURNACE => FurnaceRecipeBlockName::BLAST_FURNACE,
					FurnaceType::SMOKER => FurnaceRecipeBlockName::SMOKER,
					FurnaceType::CAMPFIRE => FurnaceRecipeBlockName::CAMPFIRE,
					FurnaceType::SOUL_CAMPFIRE => FurnaceRecipeBlockName::SOUL_CAMPFIRE
				};
				foreach($manager->getFurnaceRecipeManager($furnaceType)->getAll() as $recipe){
					$input = $converter->coreRecipeIngredientToNet($recipe->getInput())->getDescriptor();
					if(!$input instanceof IntIdMetaItemDescriptor){
						throw new AssumptionFailedError();
					}
					$recipesWithTypeIds[] = new ProtocolFurnaceRecipe(
						CraftingDataPacket::ENTRY_FURNACE_DATA,
						$input->getId(),
						$input->getMeta(),
						$converter->coreItemStackToNet($recipe->getResult()),
						$typeTag
					);
				}
			}catch(\InvalidArgumentException|ItemTypeSerializeException){
				continue;
			}
		}

		$potionTypeRecipes = [];
		foreach($manager->getPotionTypeRecipes() as $recipe){
			try{
				$input = $converter->coreRecipeIngredientToNet($recipe->getInput())->getDescriptor();
				$ingredient = $converter->coreRecipeIngredientToNet($recipe->getIngredient())->getDescriptor();
				if(!$input instanceof IntIdMetaItemDescriptor || !$ingredient instanceof IntIdMetaItemDescriptor){
					throw new AssumptionFailedError();
				}
				$output = $converter->coreItemStackToNet($recipe->getOutput());
				$potionTypeRecipes[] = new ProtocolPotionTypeRecipe(
					$input->getId(),
					$input->getMeta(),
					$ingredient->getId(),
					$ingredient->getMeta(),
					$output->getId(),
					$output->getMeta()
				);
			}catch(\InvalidArgumentException|ItemTypeSerializeException){
				continue;
			}
		}

		$potionContainerChangeRecipes = [];
		$itemTypeDictionary = $converter->getItemTypeDictionary();
		foreach($manager->getPotionContainerChangeRecipes() as $recipe){
			try{
				$input = $itemTypeDictionary->fromStringId($recipe->getInputItemId());
				$ingredient = $converter->coreRecipeIngredientToNet($recipe->getIngredient())->getDescriptor();
				if(!$ingredient instanceof IntIdMetaItemDescriptor){
					throw new AssumptionFailedError();
				}
				$output = $itemTypeDictionary->fromStringId($recipe->getOutputItemId());
				$potionContainerChangeRecipes[] = new ProtocolPotionContainerChangeRecipe(
					$input,
					$ingredient->getId(),
					$output
				);
			}catch(\InvalidArgumentException|ItemTypeSerializeException){
				continue;
			}
		}

		Timings::$craftingDataCacheRebuild->stopTiming();
		return CraftingDataPacket::create($recipesWithTypeIds, $potionTypeRecipes, $potionContainerChangeRecipes, [], true);
	}
}
