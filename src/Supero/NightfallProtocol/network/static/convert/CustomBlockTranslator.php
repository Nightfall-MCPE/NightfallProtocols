<?php

declare(strict_types=1);

namespace Supero\NightfallProtocol\network\static\convert;

use JsonException;
use pocketmine\data\bedrock\BedrockDataFiles;
use pocketmine\data\bedrock\block\BlockStateData;
use pocketmine\data\bedrock\block\BlockStateSerializeException;
use pocketmine\data\bedrock\block\BlockStateSerializer;
use pocketmine\data\bedrock\block\BlockTypeNames;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\utils\Filesystem;
use pocketmine\world\format\io\GlobalBlockStateHandlers;
use Supero\NightfallProtocol\Main;
use Supero\NightfallProtocol\network\CustomProtocolInfo;
use function in_array;
use function str_replace;

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
			self::CANONICAL_BLOCK_STATES_PATH => "",
			self::BLOCK_STATE_META_MAP_PATH => "",
		],
		CustomProtocolInfo::PROTOCOL_1_21_40 => [
			self::CANONICAL_BLOCK_STATES_PATH => "-1.21.40",
			self::BLOCK_STATE_META_MAP_PATH => "-1.21.40",
		],
		CustomProtocolInfo::PROTOCOL_1_21_30 => [
			self::CANONICAL_BLOCK_STATES_PATH => "-1.21.30",
			self::BLOCK_STATE_META_MAP_PATH => "-1.21.30",
		],
		CustomProtocolInfo::PROTOCOL_1_21_20 => [
			self::CANONICAL_BLOCK_STATES_PATH => "-1.21.20",
			self::BLOCK_STATE_META_MAP_PATH => "-1.21.20",
		],
		CustomProtocolInfo::PROTOCOL_1_21_2 => [
			self::CANONICAL_BLOCK_STATES_PATH => "-1.21.2",
			self::BLOCK_STATE_META_MAP_PATH => "-1.21.2",
		],
		CustomProtocolInfo::PROTOCOL_1_21_0 => [
			self::CANONICAL_BLOCK_STATES_PATH => "-1.21.2",
			self::BLOCK_STATE_META_MAP_PATH => "-1.21.2",
		],
		CustomProtocolInfo::PROTOCOL_1_20_80 => [
			self::CANONICAL_BLOCK_STATES_PATH => "-1.20.80",
			self::BLOCK_STATE_META_MAP_PATH => "-1.20.80",
		],
		CustomProtocolInfo::PROTOCOL_1_20_70 => [
			self::CANONICAL_BLOCK_STATES_PATH => "-1.20.70",
			self::BLOCK_STATE_META_MAP_PATH => "-1.20.70",
		],
		CustomProtocolInfo::PROTOCOL_1_20_60 => [
			self::CANONICAL_BLOCK_STATES_PATH => "-1.20.60",
			self::BLOCK_STATE_META_MAP_PATH => "-1.20.60",
		],
		CustomProtocolInfo::PROTOCOL_1_20_50 => [
			self::CANONICAL_BLOCK_STATES_PATH => "-1.20.50",
			self::BLOCK_STATE_META_MAP_PATH => "-1.20.50",
		],
	];

	/**
	 * @throws JsonException
	 */
	public static function loadFromProtocol(int $protocolId) : self{
		if(in_array($protocolId, CustomProtocolInfo::COMBINED_LATEST, true)){
			$canonicalBlockStatesRaw = Filesystem::fileGetContents(BedrockDataFiles::CANONICAL_BLOCK_STATES_NBT);
			$metaMappingRaw = Filesystem::fileGetContents(BedrockDataFiles::BLOCK_STATE_META_MAP_JSON);
		} else {
			$canonicalBlockStatesRaw = Filesystem::fileGetContents(str_replace(".nbt", self::PATHS[$protocolId][self::CANONICAL_BLOCK_STATES_PATH] . ".nbt", Main::getProtocolDataFolder() . '/canonical_block_states.nbt'));
			$metaMappingRaw = Filesystem::fileGetContents(str_replace(".json", self::PATHS[$protocolId][self::BLOCK_STATE_META_MAP_PATH] . ".json", Main::getProtocolDataFolder() . '/block_state_meta_map.json'));
		}

		return new self(
			CustomBlockStateDictionary::loadFromString($canonicalBlockStatesRaw, $metaMappingRaw),
			GlobalBlockStateHandlers::getSerializer(),
		);
	}

	public function __construct(
		private CustomBlockStateDictionary $blockStateDictionary,
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
	 * Looks up the network state data associated with the given internal state ID.1
	 */
	public function internalIdToNetworkStateData(int $internalStateId) : BlockStateData{
		//we don't directly use the blockstate serializer here - we can't assume that the network blockstate NBT is the
		//same as the disk blockstate NBT, in case we decide to have different world version than network version (or in
		//case someone wants to implement multi version).
		$networkRuntimeId = $this->internalIdToNetworkId($internalStateId);

		return $this->blockStateDictionary->generateDataFromStateId($networkRuntimeId) ?? throw new AssumptionFailedError("We just looked up this state ID, so it must exist");
	}

	public function getBlockStateDictionary() : CustomBlockStateDictionary{ return $this->blockStateDictionary; }

	public function getFallbackStateData() : BlockStateData{ return $this->fallbackStateData; }

}
