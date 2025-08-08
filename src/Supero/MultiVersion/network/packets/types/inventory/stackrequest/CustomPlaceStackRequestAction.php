<?php

declare(strict_types=1);

namespace Supero\MultiVersion\network\packets\types\inventory\stackrequest;

use pocketmine\network\mcpe\protocol\types\GetTypeIdFromConstTrait;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\ItemStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\ItemStackRequestActionType;

/**
 * Puts some (or all) of the items from the source slot into the destination slot.
 */
final class CustomPlaceStackRequestAction extends ItemStackRequestAction{
	use GetTypeIdFromConstTrait;
	use CustomTakeOrPlaceStackRequestActionTrait;

	public const ID = ItemStackRequestActionType::PLACE;
}
