<?php

declare(strict_types=1);

namespace Supero\MultiVersion\network\packets\types;

use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\types\SubChunkPacketEntryWithCache;

class CustomSubChunkPacketEntryWithCache
{

	public function __construct(
		private CustomSubChunkPacketEntryCommon $base,
		private int $usedBlobHash
	){}

	public function getBase() : CustomSubChunkPacketEntryCommon{ return $this->base; }

	public function getUsedBlobHash() : int{ return $this->usedBlobHash; }

	public static function read(PacketSerializer $in) : self{
		$base = CustomSubChunkPacketEntryCommon::read($in, true);
		$usedBlobHash = $in->getLLong();

		return new self($base, $usedBlobHash);
	}

	public function write(PacketSerializer $out) : void{
		$this->base->write($out, true);
		$out->putLLong($this->usedBlobHash);
	}

	public static function fromEntry(SubChunkPacketEntryWithCache $cache) : self
	{
		return new self(
			CustomSubChunkPacketEntryCommon::fromEntry($cache->getBase()),
			$cache->getUsedBlobHash()
		);
	}
}
