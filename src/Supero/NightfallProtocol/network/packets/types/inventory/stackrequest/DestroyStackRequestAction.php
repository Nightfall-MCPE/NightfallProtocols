<?php

declare(strict_types=1);

namespace Supero\NightfallProtocol\network\packets\types\inventory\stackrequest;

use pocketmine\network\mcpe\protocol\types\GetTypeIdFromConstTrait;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\ItemStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\ItemStackRequestActionType;

/**
 * Sends some (or all) items from the source slot to /dev/null. This happens when the player clicks items into the
 * creative inventory menu in creative mode.
 */
final class DestroyStackRequestAction extends ItemStackRequestAction{
	use DisappearStackRequestActionTrait;
	use GetTypeIdFromConstTrait;

	public const ID = ItemStackRequestActionType::DESTROY;
}
