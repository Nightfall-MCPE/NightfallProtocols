<?php

declare(strict_types=1);

namespace Supero\NightfallProtocol\network\packets;

use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\SubChunkPacket as PM_Packet;
use pocketmine\network\mcpe\protocol\types\SubChunkPacketEntryWithCacheList;
use pocketmine\network\mcpe\protocol\types\SubChunkPacketEntryWithoutCacheList;
use pocketmine\network\mcpe\protocol\types\SubChunkPosition;
use Supero\NightfallProtocol\network\packets\types\CustomSubChunkPacketEntryWithCache as EntryWithBlobHash;
use Supero\NightfallProtocol\network\packets\types\CustomSubChunkPacketEntryWithCacheList as ListWithBlobHashes;
use Supero\NightfallProtocol\network\packets\types\CustomSubChunkPacketEntryWithoutCache as EntryWithoutBlobHash;
use Supero\NightfallProtocol\network\packets\types\CustomSubChunkPacketEntryWithoutCacheList as ListWithoutBlobHashes;

use function array_map;
use function count;
class SubChunkPacket extends PM_Packet{

	private int $dimension;
	private SubChunkPosition $baseSubChunkPosition;
	private ListWithBlobHashes|ListWithoutBlobHashes $entries;

	public static function createPacket(int $dimension, SubChunkPosition $baseSubChunkPosition, ListWithBlobHashes|ListWithoutBlobHashes $entries) : self{
		$result = new self();
		$result->dimension = $dimension;
		$result->baseSubChunkPosition = $baseSubChunkPosition;
		$result->entries = $entries;
		return $result;
	}

	public function isCacheEnabled() : bool{ return $this->entries instanceof ListWithBlobHashes; }

	public function getDimension() : int{ return $this->dimension; }

	public function getBaseSubChunkPosition() : SubChunkPosition{ return $this->baseSubChunkPosition; }

	public function getCustomEntries() : ListWithBlobHashes|ListWithoutBlobHashes{ return $this->entries; }

	protected function decodePayload(PacketSerializer $in) : void{
		$cacheEnabled = $in->getBool();
		$this->dimension = $in->getVarInt();
		$this->baseSubChunkPosition = SubChunkPosition::read($in);

		$count = $in->getLInt();
		if($cacheEnabled){
			$entries = [];
			for($i = 0; $i < $count; $i++){
				$entries[] = EntryWithBlobHash::read($in);
			}
			$this->entries = new ListWithBlobHashes($entries);
		}else{
			$entries = [];
			for($i = 0; $i < $count; $i++){
				$entries[] = EntryWithoutBlobHash::read($in);
			}
			$this->entries = new ListWithoutBlobHashes($entries);
		}
	}

	protected function encodePayload(PacketSerializer $out) : void{
		$out->putBool($this->entries instanceof ListWithBlobHashes);
		$out->putVarInt($this->dimension);
		$this->baseSubChunkPosition->write($out);

		$out->putLInt(count($this->entries->getEntries()));

		foreach($this->entries->getEntries() as $entry){
			$entry->write($out);
		}
	}

	public function getConstructorArguments(PM_Packet $packet) : array
	{
		$entries = $packet->getEntries();
		if($entries instanceof SubChunkPacketEntryWithCacheList){
			$newEntries = array_map(function ($entry) {
				return EntryWithBlobHash::fromEntry($entry);
			}, $entries->getEntries());
			$entries = new ListWithBlobHashes($newEntries);
		}elseif($entries instanceof SubChunkPacketEntryWithoutCacheList){
			$newEntries = array_map(function ($entry) {
				return EntryWithoutBlobHash::fromEntry($entry);
			}, $entries->getEntries());
			$entries = new ListWithoutBlobHashes($newEntries);
		}
		return [
			$packet->getDimension(),
			$packet->getBaseSubChunkPosition(),
			$entries
		];
	}
}
