<?php

declare(strict_types=1);

namespace Supero\NightfallProtocol\network\static\convert;

use pocketmine\data\bedrock\BedrockDataFiles;
use pocketmine\network\mcpe\protocol\serializer\ItemTypeDictionary;
use pocketmine\network\mcpe\protocol\types\ItemTypeEntry;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\utils\Filesystem;
use Supero\NightfallProtocol\Main;
use Supero\NightfallProtocol\network\CustomProtocolInfo;
use function in_array;
use function is_array;
use function is_bool;
use function is_int;
use function is_string;
use function json_decode;
use function str_replace;

class CustomItemTypeDictionaryFromDataHelper
{
	private const PATHS = [
		CustomProtocolInfo::CURRENT_PROTOCOL => "",
		CustomProtocolInfo::PROTOCOL_1_21_40 => "-1.21.40",
		CustomProtocolInfo::PROTOCOL_1_21_30 => "-1.21.30",
		CustomProtocolInfo::PROTOCOL_1_21_20 => "-1.21.20",
		CustomProtocolInfo::PROTOCOL_1_21_2 => "-1.21.2",
		CustomProtocolInfo::PROTOCOL_1_21_0 => "-1.21.2",
		CustomProtocolInfo::PROTOCOL_1_20_80 => "-1.20.80",
		CustomProtocolInfo::PROTOCOL_1_20_70 => "-1.20.70",
		CustomProtocolInfo::PROTOCOL_1_20_60 => "-1.20.60",
		CustomProtocolInfo::PROTOCOL_1_20_50 => "-1.20.50",
	];

	public static function loadFromProtocolId(int $protocolId) : ItemTypeDictionary{
		if(in_array($protocolId, CustomProtocolInfo::COMBINED_LATEST, true)){
			$path = BedrockDataFiles::REQUIRED_ITEM_LIST_JSON;
		} else {
			$path = str_replace(".json", self::PATHS[$protocolId] . ".json", Main::getProtocolDataFolder() . '/required_item_list.json');
		}
		return self::loadFromString(Filesystem::fileGetContents($path));
	}

	public static function loadFromString(string $data) : ItemTypeDictionary{
		$table = json_decode($data, true);
		if(!is_array($table)){
			throw new AssumptionFailedError("Invalid item list format");
		}

		$params = [];
		foreach($table as $name => $entry){
			if(!is_array($entry) || !is_string($name) || !isset($entry["component_based"], $entry["runtime_id"]) || !is_bool($entry["component_based"]) || !is_int($entry["runtime_id"])){
				throw new AssumptionFailedError("Invalid item list format");
			}
			$params[] = new ItemTypeEntry($name, $entry["runtime_id"], $entry["component_based"]);
		}
		return new ItemTypeDictionary($params);
	}
}
