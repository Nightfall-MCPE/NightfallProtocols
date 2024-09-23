<?php

declare(strict_types=1);

namespace Supero\NightfallProtocol\network\packets;

use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\TransferPacket as PM_Packet;
use Supero\NightfallProtocol\network\CustomProtocolInfo;

class TransferPacket extends PM_Packet {

	public string $address;
	public int $port = 19132;
	public bool $reloadWorld;

	/**
	 * @generate-create-func
	 */
	public static function createPacket(string $address, int $port, bool $reloadWorld) : self {
		$result = new self();
		$result->address = $address;
		$result->port = $port;
		$result->reloadWorld = $reloadWorld;
		return $result;
	}

	protected function decodePayload(PacketSerializer $in) : void{
		$this->address = $in->getString();
		$this->port = $in->getLShort();
		if($in->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_30){
			$this->reloadWorld = $in->getBool();
		}
	}

	protected function encodePayload(PacketSerializer $out) : void{
		$out->putString($this->address);
		$out->putLShort($this->port);
		if($out->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_30){
			$out->putBool($this->reloadWorld);
		}
	}

	public function getConstructorArguments(PM_Packet $packet) : array {
		return [
			$packet->address,
			$packet->port,
			$packet->reloadWorld ?? false,
		];
	}
}
