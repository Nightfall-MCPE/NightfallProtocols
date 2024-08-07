<?php

namespace Supero\NightfallProtocol\network\packets\types;

use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStack;

class CustomCreativeContentEntry{

	public function __construct(
		private int $entryId,
		private ItemStack $item
	){}

	public function getEntryId() : int{ return $this->entryId; }

	public function getItem() : ItemStack{ return $this->item; }

	public static function read(PacketSerializer $in) : self{
		$entryId = $in->readCreativeItemNetId();
		$item = $in->getItemStackWithoutStackId();
		return new self($entryId, $item);
	}

	public function write(PacketSerializer $out) : void{
		$out->writeCreativeItemNetId($this->entryId);
		$out->putItemStackWithoutStackId($this->item);
	}
}