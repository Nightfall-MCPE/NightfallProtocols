<?php

declare(strict_types=1);

namespace Supero\NightfallProtocol\network\packets;

use pocketmine\network\mcpe\protocol\CreativeContentPacket as PM_Packet;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\types\inventory\CreativeGroupEntry;
use Supero\NightfallProtocol\network\CustomProtocolInfo;
use Supero\NightfallProtocol\network\packets\types\inventory\CreativeItemEntry;
use function count;

class CreativeContentPacket extends PM_Packet{

	/** @var CreativeGroupEntry[] */
	private array $groups;
	/** @var CreativeItemEntry[] */
	private array $items;

	/**
	 * @generate-create-func
	 * @param CreativeGroupEntry[] $groups
	 * @param CreativeItemEntry[]  $items
	 */
	public static function createPacket(array $groups, array $items) : self {
		$result = new self();
		$result->groups = $groups;
		$result->items = $items;
		return $result;
	}

	/** @return CreativeGroupEntry[] */
	public function getGroups() : array{ return $this->groups; }

	/** @return CreativeItemEntry[] */
	public function getItems() : array{ return $this->items; }

	protected function decodePayload(PacketSerializer $in) : void {
		if($in->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_60){
			$this->groups = [];
			for($i = 0, $len = $in->getUnsignedVarInt(); $i < $len; ++$i){
				$this->groups[] = CreativeGroupEntry::read($in);
			}
		}

		$this->items = [];
		for($i = 0, $len = $in->getUnsignedVarInt(); $i < $len; ++$i){
			$this->items[] = CreativeItemEntry::read($in);
		}
	}

	protected function encodePayload(PacketSerializer $out) : void {
		if($out->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_60){
			$out->putUnsignedVarInt(count($this->groups));
			foreach($this->groups as $entry){
				$entry->write($out);
			}
		}

		$out->putUnsignedVarInt(count($this->items));
		foreach($this->items as $entry){
			$entry->write($out);
		}
	}

	public function getConstructorArguments(PM_Packet $packet) : array {
		return [
			$packet->groups,
			$packet->items
		];
	}
}
