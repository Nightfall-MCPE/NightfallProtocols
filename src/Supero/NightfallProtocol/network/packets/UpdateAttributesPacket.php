<?php

declare(strict_types=1);

namespace Supero\NightfallProtocol\network\packets;

use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\UpdateAttributesPacket as PM_Packet;
use Supero\NightfallProtocol\network\packets\types\CustomUpdateAttribute;
use function count;

class UpdateAttributesPacket extends PM_Packet {
	public int $actorRuntimeId;
	/** @var CustomUpdateAttribute[] */
	public array $entries = [];
	public int $tick = 0;

	/**
	 * @generate-create-func
	 * @param CustomUpdateAttribute[] $entries
	 */
	public static function createPacket(int $actorRuntimeId, array $entries, int $tick) : self{
		$result = new self();
		$result->actorRuntimeId = $actorRuntimeId;
		$result->entries = $entries;
		$result->tick = $tick;
		return $result;
	}

	protected function decodePayload(PacketSerializer $in) : void{
		$this->actorRuntimeId = $in->getActorRuntimeId();
		for($i = 0, $len = $in->getUnsignedVarInt(); $i < $len; ++$i){
			$this->entries[] = CustomUpdateAttribute::read($in);
		}
		$this->tick = $in->getUnsignedVarLong();
	}

	protected function encodePayload(PacketSerializer $out) : void{
		$out->putActorRuntimeId($this->actorRuntimeId);
		$out->putUnsignedVarInt(count($this->entries));
		foreach($this->entries as $entry){
			$entry->write($out);
		}
		$out->putUnsignedVarLong($this->tick);
	}

	public function getConstructorArguments(PM_Packet $packet) : array {
		$entries = [];
		foreach ($packet->entries as $label => $entry) {
			$entries[$label] = new CustomUpdateAttribute(
				$entry->getId(),
				$entry->getMin(),
				$entry->getMax(),
				$entry->getCurrent(),
				$entry->getDefaultMin(),
				$entry->getDefaultMax(),
				$entry->getDefault(),
				$entry->getModifiers()
			);
		}
		return [
			$packet->actorRuntimeId,
			$entries,
			$packet->tick
		];
	}
}
