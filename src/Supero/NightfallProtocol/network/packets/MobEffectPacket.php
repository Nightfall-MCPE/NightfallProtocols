<?php

declare(strict_types=1);

namespace Supero\NightfallProtocol\network\packets;

use pocketmine\network\mcpe\protocol\MobEffectPacket as PM_Packet;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use Supero\NightfallProtocol\network\CustomProtocolInfo;

class MobEffectPacket extends PM_Packet
{
	public int $actorRuntimeId;
	public int $eventId;
	public int $effectId;
	public int $amplifier = 0;
	public bool $particles = true;
	public int $duration = 0;
	public int $tick = 0;

	/**
	 * @generate-create-func
	 */
	public static function createPacket(
		int $actorRuntimeId,
		int $eventId,
		int $effectId,
		int $amplifier,
		bool $particles,
		int $duration,
		int $tick,
	) : self{
		$result = new self();
		$result->actorRuntimeId = $actorRuntimeId;
		$result->eventId = $eventId;
		$result->effectId = $effectId;
		$result->amplifier = $amplifier;
		$result->particles = $particles;
		$result->duration = $duration;
		$result->tick = $tick;
		return $result;
	}

	protected function decodePayload(PacketSerializer $in) : void{
		$this->actorRuntimeId = $in->getActorRuntimeId();
		$this->eventId = $in->getByte();
		$this->effectId = $in->getVarInt();
		$this->amplifier = $in->getVarInt();
		$this->particles = $in->getBool();
		$this->duration = $in->getVarInt();
		if($in->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_20_70){
			$this->tick = $in->getLLong();
		}
	}

	protected function encodePayload(PacketSerializer $out) : void{
		$out->putActorRuntimeId($this->actorRuntimeId);
		$out->putByte($this->eventId);
		$out->putVarInt($this->effectId);
		$out->putVarInt($this->amplifier);
		$out->putBool($this->particles);
		$out->putVarInt($this->duration);
		if($out->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_20_70){
			$out->putLLong($this->tick);
		}
	}

	public function getConstructorArguments(PM_Packet $packet) : array
	{
		return [
			$packet->actorRuntimeId,
			$packet->eventId,
			$packet->effectId,
			$packet->amplifier,
			$packet->particles,
			$packet->duration,
			$packet->tick ?? 0
		];
	}
}
