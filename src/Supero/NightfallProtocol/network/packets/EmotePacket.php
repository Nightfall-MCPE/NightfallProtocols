<?php

declare(strict_types=1);

namespace Supero\NightfallProtocol\network\packets;

use pocketmine\network\mcpe\protocol\EmotePacket as PM_Packet;
use pocketmine\network\mcpe\protocol\PacketHandlerInterface;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use Supero\NightfallProtocol\network\CustomProtocolInfo;

class EmotePacket extends PM_Packet {

	private int $actorRuntimeId;
	private string $emoteId;
	private int $emoteLengthTicks;
	private string $xboxUserId;
	private string $platformChatId;
	private int $flags;

	/**
	 * @generate-create-func
	 */
	public static function createPacket(int $actorRuntimeId, string $emoteId, int $emoteLengthTicks, string $xboxUserId, string $platformChatId, int $flags) : self {
		$result = new self();
		$result->actorRuntimeId = $actorRuntimeId;
		$result->emoteId = $emoteId;
		$result->emoteLengthTicks = $emoteLengthTicks;
		$result->xboxUserId = $xboxUserId;
		$result->platformChatId = $platformChatId;
		$result->flags = $flags;
		return $result;
	}

	public function getActorRuntimeId() : int{
		return $this->actorRuntimeId;
	}

	public function getEmoteId() : string{
		return $this->emoteId;
	}

	public function getEmoteLengthTicks() : int{ return $this->emoteLengthTicks; }

	public function getXboxUserId() : string{ return $this->xboxUserId; }

	public function getPlatformChatId() : string{ return $this->platformChatId; }

	public function getFlags() : int{
		return $this->flags;
	}

	protected function decodePayload(PacketSerializer $in) : void {
		$this->actorRuntimeId = $in->getActorRuntimeId();
		$this->emoteId = $in->getString();
		if($in->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_30) {
			$this->emoteLengthTicks = $in->getUnsignedVarInt();
		}
		$this->xboxUserId = $in->getString();
		$this->platformChatId = $in->getString();
		$this->flags = $in->getByte();
	}
	protected function encodePayload(PacketSerializer $out) : void {
		$out->putActorRuntimeId($this->actorRuntimeId);
		$out->putString($this->emoteId);
		if($out->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_30) {
			$out->putUnsignedVarInt($this->emoteLengthTicks);
		}
		$out->putString($this->xboxUserId);
		$out->putString($this->platformChatId);
		$out->putByte($this->flags);
	}
	public function getConstructorArguments(PM_Packet $packet) : array {
		return [
			$packet->getActorRuntimeId(),
			$packet->getEmoteId(),
			$packet->getEmoteLengthTicks(),
			$packet->getXboxUserId(),
			$packet->getPlatformChatId(),
			$packet->getFlags(),
		];
	}

	public function handle(PacketHandlerInterface $handler) : bool
	{
		return $handler->handleEmote($this);
	}
}
