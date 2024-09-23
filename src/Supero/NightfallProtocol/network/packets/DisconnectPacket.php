<?php

declare(strict_types=1);

namespace Supero\NightfallProtocol\network\packets;

use pocketmine\network\mcpe\protocol\DisconnectPacket as PM_Packet;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use Supero\NightfallProtocol\network\CustomProtocolInfo;

class DisconnectPacket extends PM_Packet {
	public int $reason;
	public ?string $message;
	public ?string $filteredMessage;

	/**
	 * @generate-create-func
	 */
	public static function createPacket(int $reason, ?string $message, ?string $filteredMessage) : self {
		$result = new self();
		$result->reason = $reason;
		$result->message = $message;
		$result->filteredMessage = $filteredMessage;
		return $result;
	}
	protected function decodePayload(PacketSerializer $in) : void {
		$this->reason = $in->getVarInt();
		$skipMessage = $in->getBool();
		$this->message = $skipMessage ? null : $in->getString();
		if($in->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_20){
			$this->filteredMessage = $skipMessage ? null : $in->getString();
		}
	}
	protected function encodePayload(PacketSerializer $out) : void {
		$out->putVarInt($this->reason);
		$out->putBool($skipMessage = $this->message === null && $this->filteredMessage === null);
		if(!$skipMessage){
			$out->putString($this->message ?? "");
			if($out->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_20){
				$out->putString($this->filteredMessage ?? "");
			}
		}
	}
	public function getConstructorArguments(PM_Packet $packet) : array {
		return [
			$packet->reason,
			$packet->message,
			$packet->filteredMessage ?? "",
		];
	}
}
