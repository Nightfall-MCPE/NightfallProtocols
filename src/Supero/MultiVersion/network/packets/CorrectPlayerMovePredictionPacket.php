<?php

declare(strict_types=1);

namespace Supero\MultiVersion\network\packets;

use pocketmine\math\Vector2;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\CorrectPlayerMovePredictionPacket as PM_Packet;

use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use Supero\MultiVersion\network\CustomProtocolInfo;

class CorrectPlayerMovePredictionPacket extends PM_Packet{

	public const PREDICTION_TYPE_PLAYER = 0;
	public const PREDICTION_TYPE_VEHICLE = 1;

	private Vector3 $position;
	private Vector3 $delta;
	private bool $onGround;
	private int $tick;
	private int $predictionType;
	private Vector2 $vehicleRotation;
	private ?float $vehicleAngularVelocity;

	public static function createPacket(
		Vector3 $position,
		Vector3 $delta,
		bool $onGround,
		int $tick,
		int $predictionType,
		Vector2 $vehicleRotation,
		?float $vehicleAngularVelocity,
	) : self{
		$result = new self();
		$result->position = $position;
		$result->delta = $delta;
		$result->onGround = $onGround;
		$result->tick = $tick;
		$result->predictionType = $predictionType;
		$result->vehicleRotation = $vehicleRotation;
		$result->vehicleAngularVelocity = $vehicleAngularVelocity;
		return $result;
	}

	protected function decodePayload(PacketSerializer $in) : void{
		if($in->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_20_80){
			$this->predictionType = $in->getByte();
		}
		$this->position = $in->getVector3();
		$this->delta = $in->getVector3();
		if($in->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_20_80 && ($this->predictionType === self::PREDICTION_TYPE_VEHICLE || $in->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_100)){
			$this->vehicleRotation = new Vector2($in->getFloat(), $in->getFloat());
			if($in->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_20){
				$this->vehicleAngularVelocity = $in->readOptional($in->getFloat(...));
			}
		}
		$this->onGround = $in->getBool();
		$this->tick = $in->getUnsignedVarLong();
		if($in->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_20_60 && $in->getProtocol() < CustomProtocolInfo::PROTOCOL_1_20_80){
			$this->predictionType = $in->getByte();
		}
	}

	protected function encodePayload(PacketSerializer $out) : void{
		if($out->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_20_80){
			$out->putByte($this->predictionType);
		}
		$out->putVector3($this->position);
		$out->putVector3($this->delta);
		if($out->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_20_80 && ($this->predictionType === self::PREDICTION_TYPE_VEHICLE || $out->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_100)){
			$out->putFloat($this->vehicleRotation->getX());
			$out->putFloat($this->vehicleRotation->getY());

			if($out->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_20){
				$out->writeOptional($this->vehicleAngularVelocity, $out->putFloat(...));
			}
		}
		$out->putBool($this->onGround);
		$out->putUnsignedVarLong($this->tick);
		if($out->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_20_60 && $out->getProtocol() < CustomProtocolInfo::PROTOCOL_1_20_80){
			$out->putByte($this->predictionType);
		}
	}

	public function getConstructorArguments(PM_Packet $packet) : array
	{
		return [
			$packet->getPosition(),
			$packet->getDelta(),
			$packet->isOnGround(),
			$packet->getTick(),
			$packet->getPredictionType(),
			$packet->getVehicleRotation(),
			$packet->getVehicleAngularVelocity()
		];
	}
}
