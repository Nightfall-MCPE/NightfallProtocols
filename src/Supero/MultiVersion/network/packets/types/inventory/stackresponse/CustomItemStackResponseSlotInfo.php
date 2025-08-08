<?php

declare(strict_types=1);

namespace Supero\MultiVersion\network\packets\types\inventory\stackresponse;

use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use Supero\MultiVersion\network\CustomProtocolInfo;

final class CustomItemStackResponseSlotInfo{
	public function __construct(
		private int $slot,
		private int $hotbarSlot,
		private int $count,
		private int $itemStackId,
		private string $customName,
		private string $filteredCustomName,
		private int $durabilityCorrection
	){}

	public function getSlot() : int{ return $this->slot; }

	public function getHotbarSlot() : int{ return $this->hotbarSlot; }

	public function getCount() : int{ return $this->count; }

	public function getItemStackId() : int{ return $this->itemStackId; }

	public function getCustomName() : string{ return $this->customName; }

	public function getFilteredCustomName() : string{ return $this->filteredCustomName; }

	public function getDurabilityCorrection() : int{ return $this->durabilityCorrection; }

	public static function read(PacketSerializer $in) : self{
		$slot = $in->getByte();
		$hotbarSlot = $in->getByte();
		$count = $in->getByte();
		$itemStackId = $in->readServerItemStackId();
		$customName = $in->getString();
		if($in->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_50){
			$filteredCustomName = $in->getString();
		}
		$durabilityCorrection = $in->getVarInt();
		return new self($slot, $hotbarSlot, $count, $itemStackId, $customName, $filteredCustomName ?? $customName, $durabilityCorrection);
	}

	public function write(PacketSerializer $out) : void{
		$out->putByte($this->slot);
		$out->putByte($this->hotbarSlot);
		$out->putByte($this->count);
		$out->writeServerItemStackId($this->itemStackId);
		$out->putString($this->customName);
		if($out->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_50){
			$out->putString($this->filteredCustomName);
		}
		$out->putVarInt($this->durabilityCorrection);
	}
}
