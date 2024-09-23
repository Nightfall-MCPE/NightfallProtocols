<?php

declare(strict_types=1);

namespace Supero\NightfallProtocol\network\packets\types\inventory\stackrequest;

use pocketmine\network\mcpe\protocol\types\GetTypeIdFromConstTrait;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\ItemStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\ItemStackRequestActionType;

/**
 * Sends some (or all) items from the source slot to the magic place where crafting ingredients turn into result items.
 */
final class CraftingConsumeInputStackRequestAction extends ItemStackRequestAction{
	use DisappearStackRequestActionTrait;
	use GetTypeIdFromConstTrait;

	public const ID = ItemStackRequestActionType::CRAFTING_CONSUME_INPUT;
}
