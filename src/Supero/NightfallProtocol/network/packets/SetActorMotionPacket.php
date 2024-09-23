<?php

declare(strict_types=1);

namespace Supero\NightfallProtocol\network\packets;

use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\SetActorMotionPacket as PM_Packet;
use Supero\NightfallProtocol\network\CustomProtocolInfo;

class SetActorMotionPacket extends PM_Packet
{
	public int $actorRuntimeId;
	public Vector3 $motion;
	public int $tick;

	/**
	 * @generate-create-func
	 */
	public static function createPacket(int $actorRuntimeId, Vector3 $motion, int $tick) : self{
		$result = new self();
		$result->actorRuntimeId = $actorRuntimeId;
		$result->motion = $motion;
		$result->tick = $tick;
		return $result;
	}

	protected function decodePayload(PacketSerializer $in) : void{
		$this->actorRuntimeId = $in->getActorRuntimeId();
		$this->motion = $in->getVector3();
		if($in->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_20_70){
			$this->tick = $in->getUnsignedVarLong();
		}
	}

	protected function encodePayload(PacketSerializer $out) : void{
		$out->putActorRuntimeId($this->actorRuntimeId);
		$out->putVector3($this->motion);
		if($out->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_20_70) {
			$out->putUnsignedVarLong($this->tick);
		}
	}

	public function getConstructorArguments(PM_Packet $packet) : array
	{
		return [
			$packet->actorRuntimeId,
			$packet->motion,
			$packet->tick ?? 0
		];
	}
}
