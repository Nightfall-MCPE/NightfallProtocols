<?php

declare(strict_types=1);

namespace Supero\NightfallProtocol\network\packets;

use pocketmine\network\mcpe\protocol\InventorySlotPacket as PM_Packet;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use Supero\NightfallProtocol\network\CustomProtocolInfo;
use Supero\NightfallProtocol\network\packets\types\inventory\FullContainerName;

class InventorySlotPacket extends PM_Packet {

	public int $windowId;
	public int $inventorySlot;
	public FullContainerName $customContainerName;
	public int $dynamicContainerSize;
	public ItemStackWrapper $item;
	public ItemStackWrapper $storage;

	/**
	 * @generate-create-func
	 */
	public static function createPacket(int $windowId, int $inventorySlot, FullContainerName $containerName, int $dynamicContainerSize, ItemStackWrapper $item, ItemStackWrapper $storage) : self{
		$result = new self();
		$result->windowId = $windowId;
		$result->inventorySlot = $inventorySlot;
		$result->customContainerName = $containerName;
		$result->dynamicContainerSize = $dynamicContainerSize;
		$result->item = $item;
		$result->storage = $storage;
		return $result;
	}

	protected function decodePayload(PacketSerializer $in) : void{
		$this->windowId = $in->getUnsignedVarInt();
		$this->inventorySlot = $in->getUnsignedVarInt();
		if($in->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_30){
			$this->customContainerName = FullContainerName::read($in);
			if($in->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_40){
				$this->storage = $in->getItemStackWrapper();
			}else{
				$this->dynamicContainerSize = $in->getUnsignedVarInt();
			}
		}elseif($in->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_20){
			$this->customContainerName = new FullContainerName(0, $in->getUnsignedVarInt());
		}
		$this->item = $in->getItemStackWrapper();
	}

	protected function encodePayload(PacketSerializer $out) : void{
		$out->putUnsignedVarInt($this->windowId);
		$out->putUnsignedVarInt($this->inventorySlot);
		if($out->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_30){
			$this->customContainerName->write($out);
			if($out->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_40){
				$out->putItemStackWrapper($this->storage);
			}else{
				$out->putUnsignedVarInt($this->dynamicContainerSize);
			}
		}elseif($out->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_20){
			$out->putUnsignedVarInt($this->customContainerName->getDynamicId() ?? 0);
		}
		$out->putItemStackWrapper($this->item);
	}
	public function getConstructorArguments(PM_Packet $packet) : array {
		$customContainerName = new FullContainerName($packet->containerName->getContainerId(), $packet->containerName->getDynamicId());
		return [
			$packet->windowId,
			$packet->inventorySlot,
			$customContainerName,
			0,
			$packet->item,
			$packet->storage,
		];
	}
}
