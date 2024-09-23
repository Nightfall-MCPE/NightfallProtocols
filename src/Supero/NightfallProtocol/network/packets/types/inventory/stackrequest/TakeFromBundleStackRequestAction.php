<?php

declare(strict_types=1);

namespace Supero\NightfallProtocol\network\packets\types\inventory\stackrequest;

use pocketmine\network\mcpe\protocol\types\GetTypeIdFromConstTrait;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\ItemStackRequestAction;

final class TakeFromBundleStackRequestAction extends ItemStackRequestAction{
	use GetTypeIdFromConstTrait;
	use TakeOrPlaceStackRequestActionTrait;

	public const ID = 8;
}
