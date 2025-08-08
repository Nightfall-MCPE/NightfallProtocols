<?php

declare(strict_types=1);

namespace Supero\MultiVersion\network\packets\types;

use pocketmine\network\mcpe\protocol\types\PacketIntEnumTrait;

enum ServerAuthMovementMode : int{
	use PacketIntEnumTrait;

	case SERVER_AUTHORITATIVE_V2 = 1;
	case SERVER_AUTHORITATIVE_V3 = 2;
}
