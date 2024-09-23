<?php

declare(strict_types=1);

namespace Supero\NightfallProtocol\network\packets;

use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use Supero\NightfallProtocol\network\CustomProtocolInfo;

final class PlayerAuthInputVehicleInfo
{
	public function __construct(
		private ?float $vehicleRotationX,
		private ?float $vehicleRotationZ,
		private int $predictedVehicleActorUniqueId
	){}

	public function getVehicleRotationX() : ?float{ return $this->vehicleRotationX; }

	public function getVehicleRotationZ() : ?float{ return $this->vehicleRotationZ; }

	public function getPredictedVehicleActorUniqueId() : int{ return $this->predictedVehicleActorUniqueId; }

	public static function read(PacketSerializer $in) : self{
		if($in->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_20_70){
			$vehicleRotationX = $in->getLFloat();
			$vehicleRotationZ = $in->getLFloat();
		}
		$predictedVehicleActorUniqueId = $in->getActorUniqueId();

		return new self($vehicleRotationX ?? null, $vehicleRotationZ ?? null, $predictedVehicleActorUniqueId);
	}

	public function write(PacketSerializer $out) : void{
		if($out->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_20_70){
			$out->putLFloat($this->vehicleRotationX ?? throw new \InvalidArgumentException("vehicleRotationX must be set for 1.20.70+"));
			$out->putLFloat($this->vehicleRotationZ ?? throw new \InvalidArgumentException("vehicleRotationZ must be set for 1.20.70+"));
		}
		$out->putActorUniqueId($this->predictedVehicleActorUniqueId);
	}
}
