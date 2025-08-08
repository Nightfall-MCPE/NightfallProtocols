<?php

declare(strict_types=1);

namespace Supero\MultiVersion\network\packets;

use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\PacketHandlerInterface;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use Supero\MultiVersion\network\CustomProtocolInfo;
use Supero\MultiVersion\network\packets\types\ServerAuthMovementMode;

class SetMovementAuthorityPacket extends DataPacket implements ClientboundPacket{
	public const NETWORK_ID = CustomProtocolInfo::SET_MOVEMENT_AUTHORITY_PACKET;

	private ServerAuthMovementMode $mode;

	public static function createPacket(ServerAuthMovementMode $mode) : self{
		$result = new self();
		$result->mode = $mode;
		return $result;
	}

	public function getMode() : ServerAuthMovementMode{ return $this->mode; }

	protected function decodePayload(PacketSerializer $in) : void{
		$this->mode = ServerAuthMovementMode::fromPacket($in->getByte());
	}

	protected function encodePayload(PacketSerializer $out) : void{
		$out->putByte($this->mode->value);
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return true;
	}
}
