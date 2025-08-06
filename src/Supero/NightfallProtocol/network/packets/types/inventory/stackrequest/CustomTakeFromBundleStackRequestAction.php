<?php

declare(strict_types=1);

namespace Supero\NightfallProtocol\network\packets\types\inventory\stackrequest;

use pocketmine\network\mcpe\protocol\types\GetTypeIdFromConstTrait;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\ItemStackRequestAction;

final class CustomTakeFromBundleStackRequestAction extends ItemStackRequestAction{
	use GetTypeIdFromConstTrait;
	use CustomTakeOrPlaceStackRequestActionTrait;

	public const ID = 8;
}
