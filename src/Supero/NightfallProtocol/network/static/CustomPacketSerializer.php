<?php

declare(strict_types=1);

namespace Supero\NightfallProtocol\network\static;

use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\types\entity\EntityLink;
use Supero\NightfallProtocol\network\CustomProtocolInfo;

class CustomPacketSerializer extends PacketSerializer
{
	public static int $protocol = CustomProtocolInfo::CURRENT_PROTOCOL;
	public static function setProtocol(int $protocol) : void
	{
		self::$protocol = $protocol;
	}

	/**
	 * @return CustomPacketSerializer
	 */
	public static function decoder(string $buffer, int $offset) : PacketSerializer
	{
		return new self($buffer, $offset);
	}

	/**
	 * @return CustomPacketSerializer
	 */
	public static function encoder() : PacketSerializer
	{
		return new self();
	}

	public static function getProtocol() : int
	{
		return self::$protocol;
	}

	public function getEntityLink() : EntityLink{
		$fromActorUniqueId = $this->getActorUniqueId();
		$toActorUniqueId = $this->getActorUniqueId();
		$type = $this->getByte();
		$immediate = $this->getBool();
		$causedByRider = $this->getBool();
		if($this->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_20){
			$vehicleAngularVelocity = $this->getLFloat();
		}
		return new EntityLink($fromActorUniqueId, $toActorUniqueId, $type, $immediate, $causedByRider, $vehicleAngularVelocity ?? 0.0);
	}

	public function putEntityLink(EntityLink $link) : void{
		$this->putActorUniqueId($link->fromActorUniqueId);
		$this->putActorUniqueId($link->toActorUniqueId);
		$this->putByte($link->type);
		$this->putBool($link->immediate);
		$this->putBool($link->causedByRider);
		if($this->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_20) {
			$this->putLFloat($link->vehicleAngularVelocity);
		}
	}

}
