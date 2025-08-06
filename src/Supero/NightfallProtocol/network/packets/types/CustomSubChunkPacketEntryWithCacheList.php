<?php

declare(strict_types=1);

namespace Supero\NightfallProtocol\network\packets\types;

class CustomSubChunkPacketEntryWithCacheList
{
	/**
	 * @param CustomSubChunkPacketEntryWithCache[] $entries
	 */
	public function __construct(
		private array $entries
	){}

	/**
	 * @return CustomSubChunkPacketEntryWithCache[]
	 */
	public function getEntries() : array{ return $this->entries; }
}
