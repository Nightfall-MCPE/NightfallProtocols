<?php

declare(strict_types=1);

namespace Supero\MultiVersion\network\packets\types;

class CustomSubChunkPacketEntryWithoutCacheList
{
	/**
	 * @param CustomSubChunkPacketEntryWithoutCache[] $entries
	 */
	public function __construct(
		private array $entries
	){}

	/**
	 * @return CustomSubChunkPacketEntryWithoutCache[]
	 */
	public function getEntries() : array{ return $this->entries; }
}
