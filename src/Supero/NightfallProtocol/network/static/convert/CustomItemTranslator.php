<?php

namespace Supero\NightfallProtocol\network\static\convert;

use pocketmine\block\VanillaBlocks;
use pocketmine\data\bedrock\item\BlockItemIdMap;
use pocketmine\data\bedrock\item\ItemDeserializer;
use pocketmine\data\bedrock\item\ItemSerializer;
use pocketmine\data\bedrock\item\ItemTypeDeserializeException;
use pocketmine\data\bedrock\item\ItemTypeSerializeException;
use pocketmine\data\bedrock\item\SavedItemData;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\convert\TypeConversionException;
use pocketmine\network\mcpe\protocol\serializer\ItemTypeDictionary;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\world\format\io\GlobalItemDataHandlers;
use Supero\NightfallProtocol\network\CustomProtocolInfo;

class CustomItemTranslator
{
    public const NO_BLOCK_RUNTIME_ID = 0; //this is technically a valid block runtime ID, but is used to represent "no block" (derp mojang)

    public function __construct(
        private ItemTypeDictionary $itemTypeDictionary,
        private CustomBlockStateDictionary $blockStateDictionary,
        private ItemSerializer $itemSerializer,
        private ItemDeserializer $itemDeserializer,
        private BlockItemIdMap $blockItemIdMap,
        private CustomItemIdMetaDowngrader $itemDataDowngrader,
    ){}

    /**
     * @return int[]|null
     * @phpstan-return array{int, int, ?int}|null
     */
    public function toNetworkIdQuiet(Item $item) : ?array{
        try{
            return $this->toNetworkId($item);
        }catch(ItemTypeSerializeException){
            return null;
        }
    }

    /**
     * @return int[]
     * @phpstan-return array{int, int, ?int}
     *
     * @throws ItemTypeSerializeException
     */
    public function toNetworkId(Item $item) : array{
        $itemData = $this->itemSerializer->serializeType($item);

        try {
            $numericId = $this->itemTypeDictionary->fromStringId($itemData->getName());
        } catch (\InvalidArgumentException) {
            //TODO: fix this using a downgrader
            // https://github.com/NetherGamesMC/PocketMine-MP/blob/stable/src/network/mcpe/convert/ItemTranslator.php#L73
            // https://github.com/NetherGamesMC/PocketMine-MP/blob/stable/src/data/bedrock/item/downgrade/ItemIdMetaDowngrader.php#L104
            //throw new ItemTypeSerializeException("Unknown item type " . $itemData->getName());
            var_dump($itemData->getName() . " not found, replacing with an update block instead");
            return $this->toNetworkId(VanillaBlocks::INFO_UPDATE()->asItem());
        }

        $blockStateData = $itemData->getBlock();

        if($blockStateData !== null){
            $blockRuntimeId = $this->blockStateDictionary->lookupStateIdFromData($blockStateData);
            if($blockRuntimeId === null){
                throw new AssumptionFailedError("Unmapped blockstate returned by blockstate serializer: " . $blockStateData->toNbt());
            }
        }else{
            $blockRuntimeId = null;
        }

        return [$numericId, $itemData->getMeta(), $blockRuntimeId];
    }

    /**
     * @throws ItemTypeSerializeException
     */
    public function toNetworkNbt(Item $item) : CompoundTag{
        //this relies on the assumption that network item NBT is the same as disk item NBT, which may not always
        //be true - if we stick on an older world version while updating network version, this could be a problem (and
        //may be a problem for multi version implementations)
        return $this->itemSerializer->serializeStack($item, null, $this->itemDataDowngrader)->toNbt();
    }

    /**
     * @throws TypeConversionException
     */
    public function fromNetworkId(int $networkId, int $networkMeta, int $networkBlockRuntimeId) : Item{
        try{
            $stringId = $this->itemTypeDictionary->fromIntId($networkId);
        }catch(\InvalidArgumentException $e){
            throw TypeConversionException::wrap($e, "Invalid network itemstack ID $networkId");
        }

        $blockStateData = null;
        if($this->blockItemIdMap->lookupBlockId($stringId) !== null){
            $blockStateData = $this->blockStateDictionary->generateCurrentDataFromStateId($networkBlockRuntimeId);
            if($blockStateData === null){
                throw new TypeConversionException("Blockstate runtimeID $networkBlockRuntimeId does not correspond to any known blockstate");
            }
        }elseif($networkBlockRuntimeId !== self::NO_BLOCK_RUNTIME_ID){
            throw new TypeConversionException("Item $stringId is not a blockitem, but runtime ID $networkBlockRuntimeId was provided");
        }

        [$stringId, $networkMeta] = GlobalItemDataHandlers::getUpgrader()->getIdMetaUpgrader()->upgrade($stringId, $networkMeta);

        try{
            return $this->itemDeserializer->deserializeType(new SavedItemData($stringId, $networkMeta, $blockStateData));
        }catch(ItemTypeDeserializeException $e){
            throw TypeConversionException::wrap($e, "Invalid network itemstack data");
        }
    }

    public static function getItemSchemaId(int $protocolId) : int{
        return match($protocolId){
            CustomProtocolInfo::PROTOCOL_1_21_2,
            CustomProtocolInfo::PROTOCOL_1_21_0 => 191,
            CustomProtocolInfo::PROTOCOL_1_20_80 => 181,
            CustomProtocolInfo::PROTOCOL_1_20_70 => 171,
            CustomProtocolInfo::PROTOCOL_1_20_60 => 161,

            default => throw new AssumptionFailedError("Unknown protocol ID $protocolId"),
        };
    }
}