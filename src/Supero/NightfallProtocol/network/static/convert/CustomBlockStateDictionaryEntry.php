<?php

namespace Supero\NightfallProtocol\network\static\convert;

use pocketmine\data\bedrock\block\BlockStateData;
use pocketmine\nbt\LittleEndianNbtSerializer;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\Tag;
use pocketmine\nbt\TreeRoot;

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
        //TODO: make a more efficient encoding - NBT will do for now, but it's not very compact
        ksort($properties, SORT_STRING);
        $tag = new CompoundTag();
        foreach($properties as $k => $v){
            $tag->setTag($k, $v);
        }
        return (new LittleEndianNbtSerializer())->write(new TreeRoot($tag));
    }

}