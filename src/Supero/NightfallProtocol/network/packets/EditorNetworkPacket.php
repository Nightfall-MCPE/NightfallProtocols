<?php

declare(strict_types=1);

namespace Supero\NightfallProtocol\network\packets;

use pocketmine\network\mcpe\protocol\EditorNetworkPacket as PM_Packet;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\types\CacheableNbt;
use Supero\NightfallProtocol\network\CustomProtocolInfo;

class EditorNetworkPacket extends PM_Packet {

	public bool $isRouteToManager;
	public CacheableNbt $payload;

	/**
	 * @generate-create-func
	 */
	public static function createPacket(bool $isRouteToManager, CacheableNbt $payload) : self {
		$result = new self();
		$result->isRouteToManager = $isRouteToManager;
		$result->payload = $payload;
		return $result;
	}
	protected function decodePayload(PacketSerializer $in) : void {
		if ($in->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_20) {
			$this->isRouteToManager = $in->getBool();
		}
		$this->payload = new CacheableNbt($in->getNbtCompoundRoot());
	}
	protected function encodePayload(PacketSerializer $out) : void {
		if ($out->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_20) {
			$out->putBool($this->isRouteToManager);
		}
		$out->put($this->payload->getEncodedNbt());
	}

	public function getConstructorArguments(PM_Packet $packet) : array {
		return [
			$packet->isRouteToManager() ?? false,
			$packet->getPayload(),
		];
	}
}
