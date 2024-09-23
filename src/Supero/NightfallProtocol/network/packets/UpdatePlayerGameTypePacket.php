<?php

declare(strict_types=1);

namespace Supero\NightfallProtocol\network\packets;

use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\UpdatePlayerGameTypePacket as PM_Packet;
use Supero\NightfallProtocol\network\CustomProtocolInfo;

class UpdatePlayerGameTypePacket extends PM_Packet
{
	/** @see GameMode */
	private int $gameMode;
	private int $playerActorUniqueId;
	private int $tick;

	/**
	 * @generate-create-func
	 */
	public static function createPacket(int $gameMode, int $playerActorUniqueId, int $tick) : self{
		$result = new self();
		$result->gameMode = $gameMode;
		$result->playerActorUniqueId = $playerActorUniqueId;
		$result->tick = $tick;
		return $result;
	}

	public function getGameMode() : int{ return $this->gameMode; }

	public function getPlayerActorUniqueId() : int{ return $this->playerActorUniqueId; }

	public function getTick() : int{ return $this->tick; }

	protected function decodePayload(PacketSerializer $in) : void{
		$this->gameMode = $in->getVarInt();
		$this->playerActorUniqueId = $in->getActorUniqueId();
		if($in->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_20_80){
			$this->tick = $in->getUnsignedVarInt();
		}
	}

	protected function encodePayload(PacketSerializer $out) : void{
		$out->putVarInt($this->gameMode);
		$out->putActorUniqueId($this->playerActorUniqueId);
		if($out->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_20_80) {
			$out->putUnsignedVarInt($this->tick);
		}
	}

	public function getConstructorArguments(PM_Packet $packet) : array
	{
		return [
			$packet->getGameMode(),
			$packet->getPlayerActorUniqueId(),
			$packet->getTick() ?? 0
		];
	}
}
