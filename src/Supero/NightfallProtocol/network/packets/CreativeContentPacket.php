<?php

namespace Supero\NightfallProtocol\network\packets;

use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\types\inventory\CreativeContentEntry;
use Supero\NightfallProtocol\network\packets\types\CustomCreativeContentEntry;

class CreativeContentPacket extends \pocketmine\network\mcpe\protocol\CreativeContentPacket{

	/** @var CustomCreativeContentEntry[] */
	private array $entries;

	/**
	 * @param array $entries
	 *
	 * @return CustomCreativeContentEntry[]
	 */
	public static function __convert(array $entries) : array{
		/** @var CustomCreativeContentEntry[] $newEntries */
		$newEntries = [];
		/** @var CreativeContentEntry $entry */
		foreach($entries as $entry){
			$newEntries[] = new CustomCreativeContentEntry($entry->getEntryId(), $entry->getItem());
		}
		return $newEntries;
	}

	public static function createPacket(array $entries) : CreativeContentPacket{
		$pk = new self();
		$pk->entries = self::__convert($entries);
		return $pk;
	}

	public function getConstructorArguments(\pocketmine\network\mcpe\protocol\CreativeContentPacket $packet) : array{
		return [
			$packet->getEntries()
		];
	}

	protected function decodePayload(PacketSerializer $in) : void{
		$this->entries = [];
		for($i = 0, $len = $in->getUnsignedVarInt(); $i < $len; ++$i){
			$this->entries[] = CustomCreativeContentEntry::read($in);
		}
	}

	protected function encodePayload(PacketSerializer $out) : void{
		$out->putUnsignedVarInt(count($this->entries));
		foreach($this->entries as $entry){
			$entry->write($out);
		}
	}
}