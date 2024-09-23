<?php

declare(strict_types=1);

namespace Supero\NightfallProtocol\network\packets\types\inventory\stackrequest;

use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\types\GetTypeIdFromConstTrait;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\ItemStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\ItemStackRequestActionType;
use pocketmine\network\mcpe\protocol\types\recipe\RecipeIngredient;
use Supero\NightfallProtocol\network\CustomProtocolInfo;
use function count;

/**
 * Tells that the current transaction crafted the specified recipe, using the recipe book. This is effectively the same
 * as the regular crafting result action.
 */
final class CraftRecipeAutoStackRequestAction extends ItemStackRequestAction{
	use GetTypeIdFromConstTrait;

	public const ID = ItemStackRequestActionType::CRAFTING_RECIPE_AUTO;

	/**
	 * @param RecipeIngredient[] $ingredients
	 * @phpstan-param list<RecipeIngredient> $ingredients
	 */
	final public function __construct(
		private int $recipeId,
		private int $repetitions,
		private int $repetitions2,
		private array $ingredients
	){}

	public function getRecipeId() : int{ return $this->recipeId; }

	public function getRepetitions() : int{ return $this->repetitions; }

	public function getRepetitions2() : int{ return $this->repetitions2; }

	/**
	 * @return RecipeIngredient[]
	 * @phpstan-return list<RecipeIngredient>
	 */
	public function getIngredients() : array{ return $this->ingredients; }

	public static function read(PacketSerializer $in) : self{
		$recipeId = $in->readRecipeNetId();
		$repetitions = $in->getByte();
		if($in->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_20){
			$repetitions2 = $in->getByte(); //repetitions property is sent twice, mojang...
		}
		$ingredients = [];
		for($i = 0, $count = $in->getByte(); $i < $count; ++$i){
			$ingredients[] = $in->getRecipeIngredient();
		}
		return new self($recipeId, $repetitions, $repetitions2 ?? 0, $ingredients);
	}

	public function write(PacketSerializer $out) : void{
		$out->writeRecipeNetId($this->recipeId);
		$out->putByte($this->repetitions);
		if($out->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_20){
			$out->putByte($this->repetitions2);
		}
		$out->putByte(count($this->ingredients));
		foreach($this->ingredients as $ingredient){
			$out->putRecipeIngredient($ingredient);
		}
	}
}
