<?php

declare(strict_types=1);

namespace Supero\NightfallProtocol\network\packets\types\inventory\stackrequest;

use pocketmine\network\mcpe\protocol\types\GetTypeIdFromConstTrait;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\ItemStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\ItemStackRequestActionType;

/**
 * Takes some (or all) of the items from the source slot into the destination slot (usually the cursor?).
 */
final class TakeStackRequestAction extends ItemStackRequestAction{
	use GetTypeIdFromConstTrait;
	use TakeOrPlaceStackRequestActionTrait;

	public const ID = ItemStackRequestActionType::TAKE;
}
