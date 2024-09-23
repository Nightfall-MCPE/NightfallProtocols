<?php

declare(strict_types=1);

namespace Supero\NightfallProtocol\network\packets;

use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\SetTitlePacket as PM_Packet;
use Supero\NightfallProtocol\network\CustomProtocolInfo;

class SetTitlePacket extends PM_Packet {

	public int $type;
	public string $text = "";
	public int $fadeInTime = 0;
	public int $stayTime = 0;
	public int $fadeOutTime = 0;
	public string $xuid = "";
	public string $platformOnlineId = "";
	public string $filteredTitleText = "";

	/**
	 * @generate-create-func
	 */
	public static function createPacket(
		int $type,
		string $text,
		int $fadeInTime,
		int $stayTime,
		int $fadeOutTime,
		string $xuid,
		string $platformOnlineId,
		string $filteredTitleText,
	) : self{
		$result = new self();
		$result->type = $type;
		$result->text = $text;
		$result->fadeInTime = $fadeInTime;
		$result->stayTime = $stayTime;
		$result->fadeOutTime = $fadeOutTime;
		$result->xuid = $xuid;
		$result->platformOnlineId = $platformOnlineId;
		$result->filteredTitleText = $filteredTitleText;
		return $result;
	}

	protected function decodePayload(PacketSerializer $in) : void {
		$this->type = $in->getVarInt();
		$this->text = $in->getString();
		$this->fadeInTime = $in->getVarInt();
		$this->stayTime = $in->getVarInt();
		$this->fadeOutTime = $in->getVarInt();
		$this->xuid = $in->getString();
		$this->platformOnlineId = $in->getString();
	   if ($in->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_20) {
		   $this->filteredTitleText = $in->getString();
	   }
	}
	protected function encodePayload(PacketSerializer $out) : void {
		$out->putVarInt($this->type);
		$out->putString($this->text);
		$out->putVarInt($this->fadeInTime);
		$out->putVarInt($this->stayTime);
		$out->putVarInt($this->fadeOutTime);
		$out->putString($this->xuid);
		$out->putString($this->platformOnlineId);
	   if ($out->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_20) {
		   $out->putString($this->filteredTitleText);
	   }
	}
	public function getConstructorArguments(PM_Packet $packet) : array {
		return [
			$packet->type,
			$packet->text,
			$packet->fadeInTime,
			$packet->stayTime,
			$packet->fadeOutTime,
			$packet->xuid,
			$packet->platformOnlineId,
			$packet->filteredTitleText ?? false,
		];
	}
}
