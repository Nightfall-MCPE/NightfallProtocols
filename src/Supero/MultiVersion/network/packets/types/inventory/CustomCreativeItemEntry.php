<?php

declare(strict_types=1);

namespace Supero\MultiVersion\network\packets\types\inventory;

use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStack;
use Supero\MultiVersion\network\CustomProtocolInfo;

final class CustomCreativeItemEntry{
	public function __construct(
		private int $entryId,
		private ItemStack $item,
		private int $groupId
	){}

	public function getEntryId() : int{ return $this->entryId; }

	public function getItem() : ItemStack{ return $this->item; }

	public function getGroupId() : int{ return $this->groupId; }

	public static function read(PacketSerializer $in) : self{
		$entryId = $in->readCreativeItemNetId();
		$item = $in->getItemStackWithoutStackId();
		if($in->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_60){
			$groupId = $in->getUnsignedVarInt();
		}
		return new self($entryId, $item, $groupId ?? -1);
	}

	public function write(PacketSerializer $out) : void{
		$out->writeCreativeItemNetId($this->entryId);
		$out->putItemStackWithoutStackId($this->item);
		if($out->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_60){
			$out->putUnsignedVarInt($this->groupId);
		}
	}
}
