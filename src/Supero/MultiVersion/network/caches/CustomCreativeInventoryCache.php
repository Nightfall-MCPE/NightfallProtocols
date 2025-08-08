<?php

declare(strict_types=1);

namespace Supero\MultiVersion\network\caches;

use pocketmine\inventory\CreativeCategory;
use pocketmine\inventory\CreativeInventory;
use pocketmine\lang\Translatable;
use pocketmine\network\mcpe\cache\CreativeInventoryCacheEntry;
use pocketmine\network\mcpe\protocol\types\inventory\CreativeGroupEntry;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStack;
use Supero\MultiVersion\network\CustomNetworkSession;
use Supero\MultiVersion\network\packets\CreativeContentPacket;
use Supero\MultiVersion\network\packets\types\inventory\CustomCreativeItemEntry;
use Supero\MultiVersion\network\static\convert\CustomTypeConverter;
use Supero\MultiVersion\utils\ProtocolSingletonTrait;
use function is_string;
use function spl_object_id;
use const PHP_INT_MIN;

class CustomCreativeInventoryCache{

	use ProtocolSingletonTrait;

	/**
	 * @var CreativeInventoryCacheEntry[]
	 * @phpstan-var array<int, CreativeInventoryCacheEntry>
	 */
	private array $caches = [];

	private function getCacheEntry(CreativeInventory $inventory) : CreativeInventoryCacheEntry{
		$id = spl_object_id($inventory);
		if(!isset($this->caches[$id])){
			$inventory->getDestructorCallbacks()->add(function() use ($id) : void{
				unset($this->caches[$id]);
			});
			$inventory->getContentChangedCallbacks()->add(function() use ($id) : void{
				unset($this->caches[$id]);
			});
			$this->caches[$id] = $this->buildCacheEntry($inventory);
		}
		return $this->caches[$id];
	}

	/**
	 * Rebuild the cache for the given inventory.
	 */
	private function buildCacheEntry(CreativeInventory $inventory) : CreativeInventoryCacheEntry{
		$categories = [];
		$groups = [];

		$typeConverter = CustomTypeConverter::getProtocolInstance($this->protocolId);

		$nextIndex = 0;
		$groupIndexes = [];
		$itemGroupIndexes = [];

		foreach($inventory->getAllEntries() as $k => $entry){
			$group = $entry->getGroup();
			$category = $entry->getCategory();
			if($group === null){
				$groupId = PHP_INT_MIN;
			}else{
				$groupId = spl_object_id($group);
				unset($groupIndexes[$category->name][PHP_INT_MIN]); //start a new anonymous group for this category
			}

			//group object may be reused by multiple categories
			if(!isset($groupIndexes[$category->name][$groupId])){
				$groupIndexes[$category->name][$groupId] = $nextIndex++;
				$categories[] = $category;
				$groups[] = $group;
			}
			$itemGroupIndexes[$k] = $groupIndexes[$category->name][$groupId];
		}

		//creative inventory may have holes if items were unregistered - ensure network IDs used are always consistent
		$items = [];
		foreach($inventory->getAllEntries() as $k => $entry){
			$items[] = new CustomCreativeItemEntry(
				$k,
				$typeConverter->coreItemStackToNet($entry->getItem()),
				$itemGroupIndexes[$k]
			);
		}

		return new CreativeInventoryCacheEntry($categories, $groups, $items);
	}

	public function buildPacket(CreativeInventory $inventory, CustomNetworkSession $session) : CreativeContentPacket{
		$player = $session->getPlayer() ?? throw new \LogicException("Cannot prepare creative data for a session without a player");
		$language = $player->getLanguage();
		$forceLanguage = $player->getServer()->isLanguageForced();
		$typeConverter = $session->getTypeConverter();
		$cachedEntry = $this->getCacheEntry($inventory);
		$translate = function(Translatable|string $translatable) use ($session, $language, $forceLanguage) : string{
			if(is_string($translatable)){
				$message = $translatable;
			}elseif(!$forceLanguage){
				[$message,] = $session->prepareClientTranslatableMessage($translatable);
			}else{
				$message = $language->translate($translatable);
			}
			return $message;
		};

		$groupEntries = [];
		foreach($cachedEntry->categories as $index => $category){
			$group = $cachedEntry->groups[$index];
			$categoryId = match ($category) {
				CreativeCategory::CONSTRUCTION => CreativeContentPacket::CATEGORY_CONSTRUCTION,
				CreativeCategory::NATURE => CreativeContentPacket::CATEGORY_NATURE,
				CreativeCategory::EQUIPMENT => CreativeContentPacket::CATEGORY_EQUIPMENT,
				CreativeCategory::ITEMS => CreativeContentPacket::CATEGORY_ITEMS
			};
			if($group === null){
				$groupEntries[] = new CreativeGroupEntry($categoryId, "", ItemStack::null());
			}else{
				$groupIcon = $group->getIcon();
				//TODO: HACK! In 1.21.60, Workaround glitchy behaviour when an item is used as an icon for a group it
				//doesn't belong to. Without this hack, both instances of the item will show a +, but neither of them
				//will actually expand the group work correctly.
				$groupIcon->getNamedTag()->setInt("___GroupBugWorkaround___", $index);
				$groupName = $group->getName();
				$groupEntries[] = new CreativeGroupEntry(
					$categoryId,
					$translate($groupName),
					$typeConverter->coreItemStackToNet($groupIcon)
				);
			}
		}

		/** @var CustomCreativeItemEntry $items */
		$items = $cachedEntry->items;
		return CreativeContentPacket::createPacket($groupEntries, $items);
	}
}
