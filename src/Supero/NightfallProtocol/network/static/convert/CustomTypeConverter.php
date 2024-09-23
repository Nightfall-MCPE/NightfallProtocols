<?php

declare(strict_types=1);

namespace Supero\NightfallProtocol\network\static\convert;

use JsonException;
use pocketmine\block\VanillaBlocks;
use pocketmine\crafting\ExactRecipeIngredient;
use pocketmine\crafting\MetaWildcardRecipeIngredient;
use pocketmine\crafting\RecipeIngredient;
use pocketmine\crafting\TagWildcardRecipeIngredient;
use pocketmine\data\bedrock\item\BlockItemIdMap;
use pocketmine\data\bedrock\item\ItemTypeNames;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\NbtException;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\convert\ItemTranslator;
use pocketmine\network\mcpe\convert\LegacySkinAdapter;
use pocketmine\network\mcpe\convert\SkinAdapter;
use pocketmine\network\mcpe\convert\TypeConversionException;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\protocol\serializer\ItemTypeDictionary;
use pocketmine\network\mcpe\protocol\types\GameMode as ProtocolGameMode;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStack;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackExtraData;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackExtraDataShield;
use pocketmine\network\mcpe\protocol\types\recipe\IntIdMetaItemDescriptor;
use pocketmine\network\mcpe\protocol\types\recipe\RecipeIngredient as ProtocolRecipeIngredient;
use pocketmine\network\mcpe\protocol\types\recipe\StringIdMetaItemDescriptor;
use pocketmine\network\mcpe\protocol\types\recipe\TagItemDescriptor;
use pocketmine\player\GameMode;
use pocketmine\utils\AssumptionFailedError;
use Supero\NightfallProtocol\network\CustomProtocolInfo;
use Supero\NightfallProtocol\network\static\CustomPacketSerializer;
use Supero\NightfallProtocol\network\static\data\CustomGlobalItemDataHandlers;
use Supero\NightfallProtocol\utils\ProtocolSingletonTrait;
use function get_class;

class CustomTypeConverter extends TypeConverter
{
	use ProtocolSingletonTrait;
	private const PM_ID_TAG = "___Id___";

	private const RECIPE_INPUT_WILDCARD_META = 0x7fff;

	private BlockItemIdMap $blockItemIdMap;
	private CustomBlockTranslator $blockTranslator;
	private CustomItemTranslator $itemTranslator;
	private ItemTypeDictionary $itemTypeDictionary;
	private CustomItemIdMetaDowngrader $itemDataDowngrader;
	private int $shieldRuntimeId;

	private SkinAdapter $skinAdapter;
	private int $protocol;

	/**
	 * @throws JsonException
	 */
	public function __construct(int $protocol = CustomProtocolInfo::CURRENT_PROTOCOL){

		parent::__construct();

		$this->setProtocolInstance($this, $protocol);

		$this->protocol = $protocol;
		$this->blockItemIdMap = BlockItemIdMap::getInstance();

		$this->blockTranslator = CustomBlockTranslator::loadFromProtocol($protocol);
		$this->itemTypeDictionary = CustomItemTypeDictionaryFromDataHelper::loadFromProtocolId($protocol);
		$this->itemDataDowngrader = new CustomItemIdMetaDowngrader($this->itemTypeDictionary, CustomItemTranslator::getItemSchemaId($protocol));

		$this->shieldRuntimeId = $this->itemTypeDictionary->fromStringId(ItemTypeNames::SHIELD);

		$this->itemTranslator = new CustomItemTranslator(
			$this->itemTypeDictionary,
			$this->blockTranslator->getBlockStateDictionary(),
			CustomGlobalItemDataHandlers::getSerializer(),
			CustomGlobalItemDataHandlers::getDeserializer(), // Just in case we change the deserializer in the future
			$this->blockItemIdMap,
			$this->itemDataDowngrader
		);

		$this->skinAdapter = new LegacySkinAdapter();
	}

	public function getCustomBlockTranslator() : CustomBlockTranslator{ return $this->blockTranslator; }

	public function getItemTypeDictionary() : ItemTypeDictionary{ return $this->itemTypeDictionary; }

	public function getCustomItemTranslator() : CustomItemTranslator{ return $this->itemTranslator; }

	public function getSkinAdapter() : SkinAdapter{ return $this->skinAdapter; }

	public function setSkinAdapter(SkinAdapter $skinAdapter) : void{
		$this->skinAdapter = $skinAdapter;
	}

	public function getProtocol() : int{  return $this->protocol; }

	/**
	 * Returns a client-friendly gamemode of the specified real gamemode
	 * This function takes care of handling gamemodes known to MCPE (as of 1.1.0.3, that includes Survival, Creative and Adventure)
	 *
	 * @internal
	 */
	public function coreGameModeToProtocol(GameMode $gamemode) : int{
		return match($gamemode){
			GameMode::SURVIVAL => ProtocolGameMode::SURVIVAL,
			GameMode::CREATIVE, GameMode::SPECTATOR => ProtocolGameMode::CREATIVE,
			GameMode::ADVENTURE => ProtocolGameMode::ADVENTURE,
		};
	}

	public function protocolGameModeToCore(int $gameMode) : ?GameMode{
		return match($gameMode){
			ProtocolGameMode::SURVIVAL => GameMode::SURVIVAL,
			ProtocolGameMode::CREATIVE => GameMode::CREATIVE,
			ProtocolGameMode::ADVENTURE => GameMode::ADVENTURE,
			ProtocolGameMode::SURVIVAL_VIEWER, ProtocolGameMode::CREATIVE_VIEWER => GameMode::SPECTATOR,
			default => null,
		};
	}

	public function coreRecipeIngredientToNet(?RecipeIngredient $ingredient) : ProtocolRecipeIngredient{
		if($ingredient === null){
			return new ProtocolRecipeIngredient(null, 0);
		}
		if($ingredient instanceof MetaWildcardRecipeIngredient){
			$oldStringId = $ingredient->getItemId();
			[$stringId, $meta] = $this->itemDataDowngrader->downgrade($oldStringId, 0);

			$id = $this->itemTypeDictionary->fromStringId($stringId);
			$meta = $meta === 0 && $stringId === $oldStringId ? self::RECIPE_INPUT_WILDCARD_META : $meta; // downgrader returns the same meta
			$descriptor = new IntIdMetaItemDescriptor($id, $meta);
		}elseif($ingredient instanceof ExactRecipeIngredient){
			$item = $ingredient->getItem();
			[$id, $meta, $blockRuntimeId] = $this->itemTranslator->toNetworkId($item);
			if($blockRuntimeId !== null){
				$meta = $this->blockTranslator->getBlockStateDictionary()->getMetaFromStateId($blockRuntimeId);
				if($meta === null){
					throw new AssumptionFailedError("Every block state should have an associated meta value");
				}
			}
			$descriptor = new IntIdMetaItemDescriptor($id, $meta);
		}elseif($ingredient instanceof TagWildcardRecipeIngredient){
			$descriptor = new TagItemDescriptor($ingredient->getTagName());
		}else{
			throw new \LogicException("Unsupported recipe ingredient type " . get_class($ingredient) . ", only " . ExactRecipeIngredient::class . " and " . MetaWildcardRecipeIngredient::class . " are supported");
		}

		return new ProtocolRecipeIngredient($descriptor, 1);
	}

	public function netRecipeIngredientToCore(ProtocolRecipeIngredient $ingredient) : ?RecipeIngredient{
		$descriptor = $ingredient->getDescriptor();
		if($descriptor === null){
			return null;
		}

		if($descriptor instanceof TagItemDescriptor){
			return new TagWildcardRecipeIngredient($descriptor->getTag());
		}

		if($descriptor instanceof IntIdMetaItemDescriptor){
			$stringId = $this->itemTypeDictionary->fromIntId($descriptor->getId());
			$meta = $descriptor->getMeta();
		}elseif($descriptor instanceof StringIdMetaItemDescriptor){
			$stringId = $descriptor->getId();
			$meta = $descriptor->getMeta();
		}else{
			throw new \LogicException("Unsupported conversion of recipe ingredient to core item stack");
		}

		if($meta === self::RECIPE_INPUT_WILDCARD_META){
			return new MetaWildcardRecipeIngredient($stringId);
		}

		$blockRuntimeId = null;
		if(($blockId = $this->blockItemIdMap->lookupBlockId($stringId)) !== null){
			$blockRuntimeId = $this->blockTranslator->getBlockStateDictionary()->lookupStateIdFromIdMeta($blockId, $meta);
			if($blockRuntimeId !== null){
				$meta = 0;
			}
		}
		$result = $this->itemTranslator->fromNetworkId(
			$this->itemTypeDictionary->fromStringId($stringId),
			$meta,
			$blockRuntimeId ?? ItemTranslator::NO_BLOCK_RUNTIME_ID
		);
		return new ExactRecipeIngredient($result);
	}

	public function coreItemStackToNet(Item $itemStack) : ItemStack{
		if($itemStack->isNull()){
			return ItemStack::null();
		}
		$nbt = $itemStack->getNamedTag();
		if($nbt->count() === 0){
			$nbt = null;
		}else{
			$nbt = clone $nbt;
		}

		$idMeta = $this->itemTranslator->toNetworkIdQuiet($itemStack);
		if($idMeta === null){
			//Display unmapped items as INFO_UPDATE, but stick something in their NBT to make sure they don't stack with
			//other unmapped items.
			[$id, $meta, $blockRuntimeId] = $this->itemTranslator->toNetworkId(VanillaBlocks::INFO_UPDATE()->asItem());
			if($nbt === null){
				$nbt = new CompoundTag();
			}
			$nbt->setLong(self::PM_ID_TAG, $itemStack->getStateId());
		}else{
			[$id, $meta, $blockRuntimeId] = $idMeta;
		}

		$extraData = $id === $this->shieldRuntimeId ?
			new ItemStackExtraDataShield($nbt, canPlaceOn: [], canDestroy: [], blockingTick: 0) :
			new ItemStackExtraData($nbt, canPlaceOn: [], canDestroy: []);
		$extraDataSerializer = CustomPacketSerializer::encoder();
		$extraDataSerializer->setProtocol($this->protocol);
		$extraData->write($extraDataSerializer);

		return new ItemStack(
			$id,
			$meta,
			$itemStack->getCount(),
			$blockRuntimeId ?? ItemTranslator::NO_BLOCK_RUNTIME_ID,
			$extraDataSerializer->getBuffer(),
		);
	}

	/**
	 * WARNING: Avoid this in server-side code. If you need to compare ItemStacks provided by the client to the
	 * server, prefer encoding the server's itemstack and comparing the serialized ItemStack, instead of converting
	 * the client's ItemStack to a core Item.
	 * This method will fully decode the item's extra data, which can be very costly if the item has a lot of NBT data.
	 *
	 * @throws TypeConversionException
	 */
	public function netItemStackToCore(ItemStack $itemStack) : Item{
		if($itemStack->getId() === 0){
			return VanillaItems::AIR();
		}
		$extraData = $this->deserializeItemStackExtraData($itemStack->getRawExtraData(), $itemStack->getId());

		$compound = $extraData->getNbt();

		$itemResult = $this->itemTranslator->fromNetworkId($itemStack->getId(), $itemStack->getMeta(), $itemStack->getBlockRuntimeId());

		if($compound !== null){
			$compound = clone $compound;
		}

		$itemResult->setCount($itemStack->getCount());
		if($compound !== null){
			try{
				$itemResult->setNamedTag($compound);
			}catch(NbtException $e){
				throw TypeConversionException::wrap($e, "Bad itemstack NBT data");
			}
		}

		return $itemResult;
	}

	public function deserializeItemStackExtraData(string $extraData, int $id) : ItemStackExtraData{
		$serializer = CustomPacketSerializer::decoder($extraData, 0);
		$serializer->setProtocol($this->protocol);

		$extraDataDeserializer = $serializer;
		return $id === $this->shieldRuntimeId ?
			ItemStackExtraDataShield::read($extraDataDeserializer) :
			ItemStackExtraData::read($extraDataDeserializer);
	}
}
