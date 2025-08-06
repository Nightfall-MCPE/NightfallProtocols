<?php

declare(strict_types=1);

namespace Supero\NightfallProtocol\network\packets\types;

use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use Supero\NightfallProtocol\network\CustomProtocolInfo;

class CustomPlayerMovementSettings{
	public function __construct(
		private ServerAuthMovementMode $movementType,
		private int $rewindHistorySize,
		private bool $serverAuthoritativeBlockBreaking
	){}

	public function getMovementType() : ServerAuthMovementMode{ return $this->movementType; }

	public function getRewindHistorySize() : int{ return $this->rewindHistorySize; }

	public function isServerAuthoritativeBlockBreaking() : bool{ return $this->serverAuthoritativeBlockBreaking; }

	public static function read(PacketSerializer $in) : self{
		if($in->getProtocol() <= CustomProtocolInfo::PROTOCOL_1_21_80){
			$movementType = ServerAuthMovementMode::fromPacket($in->getVarInt());
		}
		$rewindHistorySize = $in->getVarInt();
		$serverAuthBlockBreaking = $in->getBool();
		return new self($movementType ?? ServerAuthMovementMode::SERVER_AUTHORITATIVE_V3, $rewindHistorySize, $serverAuthBlockBreaking);
	}

	public function write(PacketSerializer $out) : void{
		if($out->getProtocol() <= CustomProtocolInfo::PROTOCOL_1_21_80){
			$out->putVarInt($this->movementType->value);
		}elseif($this->movementType !== ServerAuthMovementMode::SERVER_AUTHORITATIVE_V3){
			throw new \InvalidArgumentException("Unsupported movement type for protocol version {$out->getProtocol()}: {$this->movementType->name}");
		}
		$out->putVarInt($this->rewindHistorySize);
		$out->putBool($this->serverAuthoritativeBlockBreaking);
	}
}
