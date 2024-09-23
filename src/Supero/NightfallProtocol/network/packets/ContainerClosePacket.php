<?php

declare(strict_types=1);

namespace Supero\NightfallProtocol\network\packets;

use pocketmine\network\mcpe\protocol\ContainerClosePacket as PM_Packet;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\types\inventory\WindowTypes;
use Supero\NightfallProtocol\network\CustomProtocolInfo;
use Supero\NightfallProtocol\network\static\CustomPacketSerializer;

class ContainerClosePacket extends PM_Packet
{

	public int $windowId;
	public int $windowType;
	public bool $server = false;

	/**
	 * @generate-create-func
	 */
	public static function createPacket(int $windowId, int $windowType, bool $server) : self{
		$result = new self();
		$result->windowId = $windowId;
		$result->windowType = $windowType;
		$result->server = $server;
		return $result;
	}

	/**
	 * @param CustomPacketSerializer $in
	 */
	protected function decodePayload(PacketSerializer $in) : void{
		$this->windowId = $in->getByte();
		if($in->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_0){
			$this->windowType = $in->getByte();
		}
		$this->server = $in->getBool();
	}

	/**
	 * @param CustomPacketSerializer $out
	 */
	protected function encodePayload(PacketSerializer $out) : void
	{
		$out->putByte($this->windowId);
		if($out->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_0){
			$out->putByte($this->windowType);
		}
		$out->putBool($this->server);
	}

	public function getConstructorArguments(PM_Packet $packet) : array
	{
		return [
			$packet->windowId,
			$packet->windowType ?? WindowTypes::CONTAINER,
			$packet->server
		];
	}
}
