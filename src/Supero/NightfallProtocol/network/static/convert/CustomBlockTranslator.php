<?php

namespace Supero\NightfallProtocol\network\static\convert;

use JsonException;
use pocketmine\data\bedrock\BedrockDataFiles;
use pocketmine\data\bedrock\block\BlockStateData;
use pocketmine\data\bedrock\block\BlockStateSerializeException;
use pocketmine\data\bedrock\block\BlockStateSerializer;
use pocketmine\data\bedrock\block\BlockTypeNames;
use pocketmine\network\mcpe\convert\BlockStateDictionary;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\utils\Filesystem;
use pocketmine\world\format\io\GlobalBlockStateHandlers;
use Supero\NightfallProtocol\network\CustomProtocolInfo;

class CustomBlockTranslator
{
    /**
     * @var int[]
     * @phpstan-var array<int, int>
     */
    private array $networkIdCache = [];

    /** Used when a blockstate can't be correctly serialized (e.g. because it's unknown) */
    private BlockStateData $fallbackStateData;
    private int $fallbackStateId;

    public const CANONICAL_BLOCK_STATES_PATH = 0;
    public const BLOCK_STATE_META_MAP_PATH = 1;
    private const PATHS = [
        CustomProtocolInfo::CURRENT_PROTOCOL => [
            self::CANONICAL_BLOCK_STATES_PATH => '',
            self::BLOCK_STATE_META_MAP_PATH => '',
        ],
        CustomProtocolInfo::PROTOCOL_1_21_0 => [
            self::CANONICAL_BLOCK_STATES_PATH => '',
            self::BLOCK_STATE_META_MAP_PATH => '',
        ],
        CustomProtocolInfo::PROTOCOL_1_20_80 => [
            self::CANONICAL_BLOCK_STATES_PATH => '-1.20.80',
            self::BLOCK_STATE_META_MAP_PATH => '-1.20.80',
        ],
    ];


    /**
     * @throws JsonException
     */
    public static function loadFromProtocol(int $protocolId) : self{
        if($protocolId == CustomProtocolInfo::CURRENT_PROTOCOL){
            $canonicalBlockStatesRaw = Filesystem::fileGetContents(str_replace(".nbt", self::PATHS[$protocolId][self::CANONICAL_BLOCK_STATES_PATH] . ".nbt", BedrockDataFiles::CANONICAL_BLOCK_STATES_NBT));
            $metaMappingRaw = Filesystem::fileGetContents(str_replace(".json", self::PATHS[$protocolId][self::BLOCK_STATE_META_MAP_PATH] . ".json", BedrockDataFiles::BLOCK_STATE_META_MAP_JSON));
        } else {
            $canonicalBlockStatesRaw = Filesystem::fileGetContents(str_replace(".nbt", self::PATHS[$protocolId][self::CANONICAL_BLOCK_STATES_PATH] . ".nbt", DATA_FILES));
            $metaMappingRaw = Filesystem::fileGetContents(str_replace(".json", self::PATHS[$protocolId][self::BLOCK_STATE_META_MAP_PATH] . ".json", DATA_FILES));
        }
        return new self(
            BlockStateDictionary::loadFromString($canonicalBlockStatesRaw, $metaMappingRaw),
            GlobalBlockStateHandlers::getSerializer(),
        );
    }

    public function __construct(
        private BlockStateDictionary $blockStateDictionary,
        private BlockStateSerializer $blockStateSerializer
    ){
        $this->fallbackStateData = BlockStateData::current(BlockTypeNames::INFO_UPDATE, []);
        $this->fallbackStateId = $this->blockStateDictionary->lookupStateIdFromData($this->fallbackStateData) ??
            throw new AssumptionFailedError(BlockTypeNames::INFO_UPDATE . " should always exist");
    }

    public function internalIdToNetworkId(int $internalStateId) : int{
        if(isset($this->networkIdCache[$internalStateId])){
            return $this->networkIdCache[$internalStateId];
        }

        try{
            $blockStateData = $this->blockStateSerializer->serialize($internalStateId);

            $networkId = $this->blockStateDictionary->lookupStateIdFromData($blockStateData);
            if($networkId === null){
                throw new AssumptionFailedError("Unmapped blockstate returned by blockstate serializer: " . $blockStateData->toNbt());
            }
        }catch(BlockStateSerializeException){
            $networkId = $this->fallbackStateId;
        }

        return $this->networkIdCache[$internalStateId] = $networkId;
    }

    /**
     * Looks up the network state data associated with the given internal state ID.
     */
    public function internalIdToNetworkStateData(int $internalStateId) : BlockStateData{
        //we don't directly use the blockstate serializer here - we can't assume that the network blockstate NBT is the
        //same as the disk blockstate NBT, in case we decide to have different world version than network version (or in
        //case someone wants to implement multi version).
        $networkRuntimeId = $this->internalIdToNetworkId($internalStateId);

        return $this->blockStateDictionary->generateDataFromStateId($networkRuntimeId) ?? throw new AssumptionFailedError("We just looked up this state ID, so it must exist");
    }

    public function getBlockStateDictionary() : BlockStateDictionary{ return $this->blockStateDictionary; }

    public function getFallbackStateData() : BlockStateData{ return $this->fallbackStateData; }

}