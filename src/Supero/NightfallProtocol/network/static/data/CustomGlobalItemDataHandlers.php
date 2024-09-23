<?php

declare(strict_types=1);

namespace Supero\NightfallProtocol\network\static\data;

use pocketmine\data\bedrock\item\ItemDeserializer;
use pocketmine\data\bedrock\item\upgrade\ItemDataUpgrader;
use pocketmine\data\bedrock\item\upgrade\ItemIdMetaUpgrader;
use pocketmine\data\bedrock\item\upgrade\ItemIdMetaUpgradeSchemaUtils;
use pocketmine\data\bedrock\item\upgrade\LegacyItemIdToStringIdMap;
use pocketmine\data\bedrock\item\upgrade\R12ItemIdToBlockIdMap;
use pocketmine\world\format\io\GlobalBlockStateHandlers;
use Symfony\Component\Filesystem\Path;
use const PHP_INT_MAX;
use const pocketmine\BEDROCK_ITEM_UPGRADE_SCHEMA_PATH;

class CustomGlobalItemDataHandlers
{
	private static ?CustomItemSerializer $itemSerializer = null;
	private static ?ItemDeserializer $itemDeserializer = null;
	private static ?ItemDataUpgrader $itemDataUpgrader = null;

	public static function getSerializer() : CustomItemSerializer
	{
		return self::$itemSerializer ??= new CustomItemSerializer(GlobalBlockStateHandlers::getSerializer());
	}

	public static function getDeserializer() : ItemDeserializer
	{
		return self::$itemDeserializer ??= new ItemDeserializer(GlobalBlockStateHandlers::getDeserializer());
	}

	public static function getUpgrader() : ItemDataUpgrader
	{
		return self::$itemDataUpgrader ??= new ItemDataUpgrader(
			new ItemIdMetaUpgrader(ItemIdMetaUpgradeSchemaUtils::loadSchemas(Path::join(BEDROCK_ITEM_UPGRADE_SCHEMA_PATH, 'id_meta_upgrade_schema'), PHP_INT_MAX)),
			LegacyItemIdToStringIdMap::getInstance(),
			R12ItemIdToBlockIdMap::getInstance(),
			GlobalBlockStateHandlers::getUpgrader()
		);
	}
}
