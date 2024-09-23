<?php

declare(strict_types=1);

namespace Supero\NightfallProtocol\network\packets;

use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\PacketHandlerInterface;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\ServerboundPacket;
use Supero\NightfallProtocol\network\CustomProtocolInfo;

class TickSyncPacket extends DataPacket implements ClientboundPacket, ServerboundPacket{
	public const NETWORK_ID = CustomProtocolInfo::TICK_SYNC_PACKET;

	private int $clientSendTime;
	private int $serverReceiveTime;

	private static function create(int $clientSendTime, int $serverReceiveTime) : self{
		$result = new self();
		$result->clientSendTime = $clientSendTime;
		$result->serverReceiveTime = $serverReceiveTime;
		return $result;
	}

	public static function request(int $clientTime) : self{
		return self::create($clientTime, 0 /* useless, but always written anyway */);
	}

	public static function response(int $clientSendTime, int $serverReceiveTime) : self{
		return self::create($clientSendTime, $serverReceiveTime);
	}

	protected function decodePayload(PacketSerializer $in) : void{
		$this->clientSendTime = $in->getLLong();
		$this->serverReceiveTime = $in->getLLong();
	}

	protected function encodePayload(PacketSerializer $out) : void{
		$out->putLLong($this->clientSendTime);
		$out->putLLong($this->serverReceiveTime);
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return true;
	}
}
