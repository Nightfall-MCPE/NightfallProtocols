<?php

declare(strict_types=1);

namespace Supero\MultiVersion\network\packets;

use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket as PM_Packet;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use Supero\MultiVersion\network\CustomProtocolInfo;
class LevelSoundEventPacket extends PM_Packet{

	public int $sound;
	public Vector3 $position;
	public int $extraData = -1;
	public string $entityType = ":"; //???
	public bool $isBabyMob = false; //...
	public bool $disableRelativeVolume = false;
	public int $actorUniqueId = -1;

	public static function createPacket(
		int $sound,
		Vector3 $position,
		int $extraData,
		string $entityType,
		bool $isBabyMob,
		bool $disableRelativeVolume,
		int $actorUniqueId,
	) : self{
		$result = new self();
		$result->sound = $sound;
		$result->position = $position;
		$result->extraData = $extraData;
		$result->entityType = $entityType;
		$result->isBabyMob = $isBabyMob;
		$result->disableRelativeVolume = $disableRelativeVolume;
		$result->actorUniqueId = $actorUniqueId;
		return $result;
	}

	public static function nonActorSound(int $sound, Vector3 $position, bool $disableRelativeVolume, int $extraData = -1) : self{
		return self::createPacket($sound, $position, $extraData, ":", false, $disableRelativeVolume, -1);
	}

	protected function decodePayload(PacketSerializer $in) : void{
		$this->sound = $in->getUnsignedVarInt();
		$this->position = $in->getVector3();
		$this->extraData = $in->getVarInt();
		$this->entityType = $in->getString();
		$this->isBabyMob = $in->getBool();
		$this->disableRelativeVolume = $in->getBool();
		if($in->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_70){
			$this->actorUniqueId = $in->getLLong(); //WHY IS THIS NON-STANDARD?
		}
	}

	protected function encodePayload(PacketSerializer $out) : void{
		$out->putUnsignedVarInt($this->sound);
		$out->putVector3($this->position);
		$out->putVarInt($this->extraData);
		$out->putString($this->entityType);
		$out->putBool($this->isBabyMob);
		$out->putBool($this->disableRelativeVolume);
		if($out->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_70){
			$out->putLLong($this->actorUniqueId);
		}
	}

	public function getConstructorArguments(PM_Packet $packet) : array
	{
		return [
			$packet->sound,
			$packet->position,
			$packet->extraData,
			$packet->entityType,
			$packet->isBabyMob,
			$packet->disableRelativeVolume,
			$packet->actorUniqueId
		];
	}
}
