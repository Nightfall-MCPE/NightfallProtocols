<?php

declare(strict_types=1);

namespace Supero\NightfallProtocol\network\caches;

use pocketmine\color\Color;
use pocketmine\data\bedrock\BedrockDataFiles;
use pocketmine\data\SavedDataLoadingException;
use pocketmine\network\mcpe\protocol\AvailableActorIdentifiersPacket;
use pocketmine\network\mcpe\protocol\serializer\NetworkNbtSerializer;
use pocketmine\network\mcpe\protocol\types\biome\BiomeDefinitionEntry;
use pocketmine\network\mcpe\protocol\types\CacheableNbt;
use pocketmine\utils\Filesystem;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\Utils;
use pocketmine\world\biome\model\BiomeDefinitionEntryData;
use Supero\NightfallProtocol\Main;
use Supero\NightfallProtocol\network\CustomProtocolInfo;
use Supero\NightfallProtocol\network\packets\BiomeDefinitionListPacket;
use function count;
use function get_debug_type;
use function is_array;
use function json_decode;
use const DIRECTORY_SEPARATOR;

class CustomStaticPacketCache{
	use SingletonTrait;

	/**
	 * @phpstan-return CacheableNbt<\pocketmine\nbt\tag\CompoundTag>
	 */
	protected static function loadCompoundFromFile(string $filePath) : CacheableNbt{
		return new CacheableNbt((new NetworkNbtSerializer())->read(Filesystem::fileGetContents($filePath))->mustGetCompoundTag());
	}

	/**
	 * @return list<BiomeDefinitionEntry>
	 */
	private static function loadBiomeDefinitionModel(string $filePath) : array{
		$biomeEntries = json_decode(Filesystem::fileGetContents($filePath), associative: true);
		if(!is_array($biomeEntries)){
			throw new SavedDataLoadingException("$filePath root should be an array, got " . get_debug_type($biomeEntries));
		}

		$jsonMapper = new \JsonMapper();
		$jsonMapper->bExceptionOnMissingData = true;
		$jsonMapper->bStrictObjectTypeChecking = true;
		$jsonMapper->bEnforceMapType = false;

		$entries = [];
		foreach(Utils::promoteKeys($biomeEntries) as $biomeName => $entry){
			if(!is_array($entry)){
				throw new SavedDataLoadingException("$filePath should be an array of objects, got " . get_debug_type($entry));
			}

			try{
				$biomeDefinition = $jsonMapper->map($entry, new BiomeDefinitionEntryData());

				$mapWaterColour = $biomeDefinition->mapWaterColour;
				$entries[] = new BiomeDefinitionEntry(
					(string) $biomeName,
					$biomeDefinition->id,
					$biomeDefinition->temperature,
					$biomeDefinition->downfall,
					$biomeDefinition->redSporeDensity,
					$biomeDefinition->blueSporeDensity,
					$biomeDefinition->ashDensity,
					$biomeDefinition->whiteAshDensity,
					$biomeDefinition->depth,
					$biomeDefinition->scale,
					new Color(
						$mapWaterColour->r,
						$mapWaterColour->g,
						$mapWaterColour->b,
						$mapWaterColour->a
					),
					$biomeDefinition->rain,
					count($biomeDefinition->tags) > 0 ? $biomeDefinition->tags : null,
				);
			}catch(\JsonMapper_Exception $e){
				throw new \RuntimeException($e->getMessage(), 0, $e);
			}
		}

		return $entries;
	}

	private static function make() : self{
		return new self(
			BiomeDefinitionListPacket::fromDefinitions(self::loadBiomeDefinitionModel(BedrockDataFiles::BIOME_DEFINITIONS_JSON)),
			BiomeDefinitionListPacket::createLegacy(self::loadCompoundFromFile(Main::getProtocolDataFolder() . DIRECTORY_SEPARATOR . "legacy_biome_definitions.nbt")),
			AvailableActorIdentifiersPacket::create(self::loadCompoundFromFile(BedrockDataFiles::ENTITY_IDENTIFIERS_NBT))
		);
	}

	public function __construct(
		private BiomeDefinitionListPacket $biomeDefs,
		private BiomeDefinitionListPacket $legacyBiomeDefs,
		private AvailableActorIdentifiersPacket $availableActorIdentifiers
	){}

	public function getBiomeDefs(int $protocolId) : BiomeDefinitionListPacket{
		return $protocolId >= CustomProtocolInfo::PROTOCOL_1_21_80 ? $this->biomeDefs : $this->legacyBiomeDefs;
	}

	public function getAvailableActorIdentifiers() : AvailableActorIdentifiersPacket{
		return $this->availableActorIdentifiers;
	}
}
