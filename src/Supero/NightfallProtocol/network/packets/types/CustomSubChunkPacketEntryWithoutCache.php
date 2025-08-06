<?php

declare(strict_types=1);

namespace Supero\NightfallProtocol\network\packets\types;

use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\types\SubChunkPacketEntryWithoutCache;

class CustomSubChunkPacketEntryWithoutCache
{

	public function __construct(
		private CustomSubChunkPacketEntryCommon $base
	){}

	public function getBase() : CustomSubChunkPacketEntryCommon{ return $this->base; }

	public static function read(PacketSerializer $in) : self{
		return new self(CustomSubChunkPacketEntryCommon::read($in, false));
	}

	public function write(PacketSerializer $out) : void{
		$this->base->write($out, false);
	}

	public static function fromEntry(SubChunkPacketEntryWithoutCache $cache) : self
	{
		return new self(
			CustomSubChunkPacketEntryCommon::fromEntry($cache->getBase())
		);
	}
}
