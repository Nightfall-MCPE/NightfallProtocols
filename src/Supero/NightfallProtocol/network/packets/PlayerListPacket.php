<?php

declare(strict_types=1);

namespace Supero\NightfallProtocol\network\packets;

use pocketmine\network\mcpe\protocol\PlayerListPacket as PM_Packet;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\types\PlayerListEntry;
use Supero\NightfallProtocol\network\CustomProtocolInfo;
use function count;

class PlayerListPacket extends PM_Packet {

	public const TYPE_ADD = 0;
	public const TYPE_REMOVE = 1;

	public int $type;
	/** @var PlayerListEntry[] */
	public array $entries = [];

	/**
	 * @generate-create-func
	 */
	public static function createPacket(int $type, array $entries) : self {
		$result = new self();
		$result->type = $type;
		$result->entries = $entries;
		return $result;
	}
	protected function decodePayload(PacketSerializer $in) : void {
		$this->type = $in->getByte();
		$count = $in->getUnsignedVarInt();
		for($i = 0; $i < $count; ++$i){
			$entry = new PlayerListEntry();

			$entry->uuid = $in->getUUID();
			if($this->type === self::TYPE_ADD){
				$entry->actorUniqueId = $in->getActorUniqueId();
				$entry->username = $in->getString();
				$entry->xboxUserId = $in->getString();
				$entry->platformChatId = $in->getString();
				$entry->buildPlatform = $in->getLInt();
				$entry->skinData = $in->getSkin();
				$entry->isTeacher = $in->getBool();
				$entry->isHost = $in->getBool();
				if ($in->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_20_60) {
					$entry->isSubClient = $in->getBool();
				}
			}

			$this->entries[$i] = $entry;
		}
		if($this->type === self::TYPE_ADD){
			for($i = 0; $i < $count; ++$i){
				$this->entries[$i]->skinData->setVerified($in->getBool());
			}
		}
	}
	protected function encodePayload(PacketSerializer $out) : void {
		$out->putByte($this->type);
		$out->putUnsignedVarInt(count($this->entries));
		foreach($this->entries as $entry){
			if($this->type === self::TYPE_ADD){
				$out->putUUID($entry->uuid);
				$out->putActorUniqueId($entry->actorUniqueId);
				$out->putString($entry->username);
				$out->putString($entry->xboxUserId);
				$out->putString($entry->platformChatId);
				$out->putLInt($entry->buildPlatform);
				$out->putSkin($entry->skinData);
				$out->putBool($entry->isTeacher);
				$out->putBool($entry->isHost);
				if ($out->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_20_60) {
					$out->putBool($entry->isSubClient);
				}
			}else{
				$out->putUUID($entry->uuid);
			}
		}
		if($this->type === self::TYPE_ADD){
			foreach($this->entries as $entry){
				$out->putBool($entry->skinData->isVerified());
			}
		}
	}
	public function getConstructorArguments(PM_Packet $packet) : array {
		return [
			$packet->type,
			$packet->entries
		];
	}
}
