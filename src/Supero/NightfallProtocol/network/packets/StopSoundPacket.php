<?php

declare(strict_types=1);

namespace Supero\NightfallProtocol\network\packets;

use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\StopSoundPacket as PM_Packet;
use Supero\NightfallProtocol\network\CustomProtocolInfo;

class StopSoundPacket extends PM_Packet {
	public string $soundName;
	public bool $stopAll;
	public bool $stopLegacyMusic;

	/**
	 * @generate-create-func
	 */
	public static function createPacket(string $soundName, bool $stopAll, bool $stopLegacyMusic) : self {
		$result = new self();
		$result->soundName = $soundName;
		$result->stopAll = $stopAll;
		$result->stopLegacyMusic = $stopLegacyMusic;
		return $result;
	}
	protected function decodePayload(PacketSerializer $in) : void {
	   $this->soundName = $in->getString();
	   $this->stopAll = $in->getBool();
	   if ($in->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_20) {
		   $this->stopLegacyMusic = $in->getBool();
	   }
	}
	protected function encodePayload(PacketSerializer $out) : void {
	   $out->putString($this->soundName);
	   $out->putBool($this->stopAll);
	   if ($out->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_20) {
		   $out->putBool($this->stopLegacyMusic);
	   }
	}
	public function getConstructorArguments(PM_Packet $packet) : array {
		return [
			$packet->soundName,
			$packet->stopAll,
			$packet->stopLegacyMusic ?? false,
		];
	}
}
