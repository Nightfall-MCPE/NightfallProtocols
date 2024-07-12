<?php

namespace Supero\NightfallProtocol\network\static\convert;

use pocketmine\data\bedrock\item\BlockItemIdMap;
use pocketmine\data\bedrock\item\ItemDeserializer;
use pocketmine\data\bedrock\item\ItemSerializer;
use pocketmine\data\bedrock\item\ItemTypeDeserializeException;
use pocketmine\data\bedrock\item\ItemTypeSerializeException;
use pocketmine\data\bedrock\item\SavedItemData;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\convert\BlockStateDictionary;
use pocketmine\network\mcpe\convert\TypeConversionException;
use pocketmine\network\mcpe\protocol\serializer\ItemTypeDictionary;
use pocketmine\utils\AssumptionFailedError;

class CustomItemTranslator
{
    public const NO_BLOCK_RUNTIME_ID = 0; //this is technically a valid block runtime ID, but is used to represent "no block" (derp mojang)

    public function __construct(
        private ItemTypeDictionary $itemTypeDictionary,
        private BlockStateDictionary $blockStateDictionary,
        private ItemSerializer $itemSerializer,
        private ItemDeserializer $itemDeserializer,
        private BlockItemIdMap $blockItemIdMap
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

        $numericId = $this->itemTypeDictionary->fromStringId($itemData->getName());
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
        return $this->itemSerializer->serializeStack($item)->toNbt();
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
            $blockStateData = $this->blockStateDictionary->generateDataFromStateId($networkBlockRuntimeId);
            if($blockStateData === null){
                throw new TypeConversionException("Blockstate runtimeID $networkBlockRuntimeId does not correspond to any known blockstate");
            }
        }elseif($networkBlockRuntimeId !== self::NO_BLOCK_RUNTIME_ID){
            throw new TypeConversionException("Item $stringId is not a blockitem, but runtime ID $networkBlockRuntimeId was provided");
        }

        try{
            return $this->itemDeserializer->deserializeType(new SavedItemData($stringId, $networkMeta, $blockStateData));
        }catch(ItemTypeDeserializeException $e){
            throw TypeConversionException::wrap($e, "Invalid network itemstack data");
        }
    }
}