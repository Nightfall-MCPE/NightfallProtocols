<?php

declare(strict_types=1);

namespace Supero\MultiVersion\network\packets;

use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\BiomeDefinitionListPacket as PM_Packet;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\types\biome\BiomeDefinitionEntry;
use pocketmine\network\mcpe\protocol\types\CacheableNbt;
use Supero\MultiVersion\network\CustomProtocolInfo;

use Supero\MultiVersion\network\packets\types\biome\CustomBiomeDefinitionData;
use Supero\MultiVersion\utils\ReflectionUtils;
use function array_map;
use function count;
class BiomeDefinitionListPacket extends PM_Packet{

	/**
	 * @var CustomBiomeDefinitionData[]
	 * @phpstan-var list<CustomBiomeDefinitionData>
	 */
	private ?array $definitionData;
	/**
	 * @var string[]
	 * @phpstan-var list<string>
	 */
	private ?array $strings = [];

	/** @phpstan-var CacheableNbt<CompoundTag> */
	private ?CacheableNbt $legacyDefinitions;

	/**
	 * @generate-create-func
	 * @param CustomBiomeDefinitionData[] $definitionData
	 * @param string[]                    $strings
	 * @phpstan-param list<CustomBiomeDefinitionData> $definitionData
	 * @phpstan-param list<string>              $strings
	 * @phpstan-param CacheableNbt<CompoundTag> $legacyDefinitions
	 */
	public static function createPacket(?array $definitionData, ?array $strings, ?CacheableNbt $legacyDefinitions) : self{
		$result = new self();
		$result->definitionData = $definitionData;
		$result->strings = $strings;
		$result->legacyDefinitions = $legacyDefinitions;
		return $result;
	}

	/**
	 * @phpstan-param list<BiomeDefinitionEntry> $definitions
	 */
	public static function fromDefinitions(array $definitions) : self{
		/**
		 * @var int[]                      $stringIndexLookup
		 * @phpstan-var array<string, int> $stringIndexLookup
		 */
		$stringIndexLookup = [];
		$strings = [];
		$addString = function(string $string) use (&$stringIndexLookup, &$strings) : int{
			if(isset($stringIndexLookup[$string])){
				return $stringIndexLookup[$string];
			}

			$stringIndexLookup[$string] = count($stringIndexLookup);
			$strings[] = $string;
			return $stringIndexLookup[$string];
		};

		$definitionData = array_map(fn(BiomeDefinitionEntry $entry) => new CustomBiomeDefinitionData(
			$addString($entry->getBiomeName()),
			$entry->getId(),
			$entry->getTemperature(),
			$entry->getDownfall(),
			$entry->getRedSporeDensity(),
			$entry->getBlueSporeDensity(),
			$entry->getAshDensity(),
			$entry->getWhiteAshDensity(),
			$entry->getDepth(),
			$entry->getScale(),
			$entry->getMapWaterColor(),
			$entry->hasRain(),
			$entry->getTags() === null ? null : array_map($addString, $entry->getTags()),
			$entry->getChunkGenData(),
		), $definitions);

		return self::createPacket($definitionData, $strings, null);
	}

	/**
	 * @phpstan-param CacheableNbt<CompoundTag> $definitions
	 */
	public static function createLegacy(CacheableNbt $definitions) : self{
		return self::createPacket(null, null, $definitions);
	}

	/**
	 * @throws PacketDecodeException
	 */
	private function locateString(int $index) : string{
		return $this->strings[$index] ?? throw new PacketDecodeException("Unknown string index $index");
	}

	/**
	 * Returns biome definition data with all string indexes resolved to actual strings.
	 *
	 * @return BiomeDefinitionEntry[]
	 * @phpstan-return list<BiomeDefinitionEntry>
	 *
	 * @throws PacketDecodeException
	 */
	public function buildDefinitionsFromData() : array{
		return array_map(fn(CustomBiomeDefinitionData $data) => new BiomeDefinitionEntry(
			$this->locateString($data->getNameIndex()),
			$data->getId(),
			$data->getTemperature(),
			$data->getDownfall(),
			$data->getRedSporeDensity(),
			$data->getBlueSporeDensity(),
			$data->getAshDensity(),
			$data->getWhiteAshDensity(),
			$data->getDepth(),
			$data->getScale(),
			$data->getMapWaterColor(),
			$data->hasRain(),
			($tagIndexes = $data->getTagIndexes()) === null ? null : array_map($this->locateString(...), $tagIndexes),
			$data->getChunkGenData(),
		), $this->definitionData ?? throw new PacketDecodeException("No definition data available"));
	}

	protected function decodePayload(PacketSerializer $in) : void{
		if($in->getProtocol() < CustomProtocolInfo::PROTOCOL_1_21_80){
			$this->legacyDefinitions = new CacheableNbt($in->getNbtCompoundRoot());
			$this->definitionData = null;
			$this->strings = null;
			return;
		}

		$this->legacyDefinitions = null;
		for($i = 0, $count = $in->getUnsignedVarInt(); $i < $count; ++$i){
			$this->definitionData[] = CustomBiomeDefinitionData::read($in);
		}

		for($i = 0, $count = $in->getUnsignedVarInt(); $i < $count; ++$i){
			$this->strings[] = $in->getString();
		}
	}

	protected function encodePayload(PacketSerializer $out) : void{
		if($out->getProtocol() < CustomProtocolInfo::PROTOCOL_1_21_80){
			if($this->legacyDefinitions === null){
				throw new \LogicException("Legacy definitions not set");
			}
			$out->put($this->legacyDefinitions->getEncodedNbt());
			return;
		}

		if($this->definitionData === null || $this->strings === null){
			throw new \LogicException("Definition data not set");
		}

		$out->putUnsignedVarInt(count($this->definitionData));
		foreach($this->definitionData as $data){
			$data->write($out);
		}

		$out->putUnsignedVarInt(count($this->strings));
		foreach($this->strings as $string){
			$out->putString($string);
		}
	}

	public function getConstructorArguments(PM_Packet $packet) : array
	{
		return [
			ReflectionUtils::getProperty($packet::class, $packet, "definitionData"),
			ReflectionUtils::getProperty($packet::class, $packet, "strings"),
			null
		];
	}
}
