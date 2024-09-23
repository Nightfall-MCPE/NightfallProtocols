<?php

declare(strict_types=1);

namespace Supero\NightfallProtocol\network\static\convert;

use pocketmine\data\bedrock\block\BlockStateData;
use pocketmine\nbt\LittleEndianNbtSerializer;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\Tag;
use pocketmine\nbt\TreeRoot;
use function count;
use function ksort;
use const SORT_STRING;

class CustomBlockStateDictionaryEntry
{

	/**
	 * @var string[]
	 * @phpstan-var array<string, string>
	 */
	private static array $uniqueRawStates = [];

	private string $rawStateProperties;

	/**
	 * @param Tag[] $stateProperties
	 */
	public function __construct(
		private string $stateName,
		array $stateProperties,
		private int $meta,
		private ?BlockStateData $oldBlockStateData
	){
		$rawStateProperties = self::encodeStateProperties($stateProperties);
		$this->rawStateProperties = self::$uniqueRawStates[$rawStateProperties] ??= $rawStateProperties;
	}

	public function getStateName() : string{ return $this->stateName; }

	public function getRawStateProperties() : string{ return $this->rawStateProperties; }

	public function generateStateData() : BlockStateData{
		return $this->oldBlockStateData ?? $this->generateCurrentStateData();
	}

	public function generateCurrentStateData() : BlockStateData{
		return new BlockStateData(
			$this->stateName,
			self::decodeStateProperties($this->rawStateProperties),
			BlockStateData::CURRENT_VERSION
		);
	}

	public function getMeta() : int{ return $this->meta; }

	/**
	 * @return Tag[]
	 */
	public static function decodeStateProperties(string $rawProperties) : array{
		if($rawProperties === ""){
			return [];
		}
		return (new LittleEndianNbtSerializer())->read($rawProperties)->mustGetCompoundTag()->getValue();
	}

	/**
	 * @param Tag[] $properties
	 */
	public static function encodeStateProperties(array $properties) : string{
		if(count($properties) === 0){
			return "";
		}
		ksort($properties, SORT_STRING);
		$tag = new CompoundTag();
		foreach($properties as $k => $v){
			$tag->setTag($k, $v);
		}
		return (new LittleEndianNbtSerializer())->write(new TreeRoot($tag));
	}

}
