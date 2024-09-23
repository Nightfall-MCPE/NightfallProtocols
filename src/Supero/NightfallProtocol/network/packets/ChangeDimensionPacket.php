<?php

declare(strict_types=1);

namespace Supero\NightfallProtocol\network\packets;

use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\ChangeDimensionPacket as PM_Packet;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use Supero\NightfallProtocol\network\CustomProtocolInfo;

class ChangeDimensionPacket extends PM_Packet
{
	public int $dimension;
	public Vector3 $position;
	public bool $respawn = false;
	public ?int $loadingScreenId = null;

	/**
	 * @generate-create-func
	 */
	public static function createPacket(int $dimension, Vector3 $position, bool $respawn, ?int $loadingScreenId) : self{
		$result = new self();
		$result->dimension = $dimension;
		$result->position = $position;
		$result->respawn = $respawn;
		$result->loadingScreenId = $loadingScreenId;
		return $result;
	}

	protected function decodePayload(PacketSerializer $in) : void {
		$this->dimension = $in->getVarInt();
		$this->position = $in->getVector3();
		$this->respawn = $in->getBool();
		if($in->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_20){
			$this->loadingScreenId = $in->readOptional(fn() => $in->getLInt());
		}
	}

	protected function encodePayload(PacketSerializer $out) : void {
		$out->putVarInt($this->dimension);
		$out->putVector3($this->position);
		$out->putBool($this->respawn);
		if($out->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_20){
			$out->writeOptional($this->loadingScreenId, $out->putLInt(...));
		}
	}

	public function getConstructorArguments(PM_Packet $packet) : array
	{
		return [
			$packet->dimension,
			$packet->position,
			$packet->respawn,
			$packet->getLoadingScreenId() ?? 0,
		];
	}

}
