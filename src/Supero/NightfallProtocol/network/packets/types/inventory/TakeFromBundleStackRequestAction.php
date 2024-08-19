<?php

namespace Supero\NightfallProtocol\network\packets\types\inventory;

use pocketmine\network\mcpe\protocol\types\GetTypeIdFromConstTrait;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\ItemStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\TakeOrPlaceStackRequestActionTrait;

final class TakeFromBundleStackRequestAction extends ItemStackRequestAction{
    use GetTypeIdFromConstTrait;
    use TakeOrPlaceStackRequestActionTrait;

    public const ID = 8;
}