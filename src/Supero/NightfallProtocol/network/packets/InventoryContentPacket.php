<?php

declare(strict_types=1);

namespace Supero\NightfallProtocol\network\packets;

use pocketmine\network\mcpe\protocol\InventoryContentPacket as PM_Packet;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use Supero\NightfallProtocol\network\CustomProtocolInfo;
use Supero\NightfallProtocol\network\packets\types\inventory\CustomFullContainerName;
use function count;

class InventoryContentPacket extends PM_Packet {
	public int $windowId;
	/** @var ItemStackWrapper[] */
	public array $items = [];
	public CustomFullContainerName $customContainerName;
	public int $dynamicContainerSize;
	public ItemStackWrapper $storage;

	/**
	 * @generate-create-func
	 */
	public static function createPacket(int $windowId, array $items, CustomFullContainerName $containerName, int $dynamicContainerSize, ItemStackWrapper $storage) : self {
		$result = new self();
		$result->windowId = $windowId;
		$result->items = $items;
		$result->customContainerName = $containerName;
		$result->dynamicContainerSize = $dynamicContainerSize;
		$result->storage = $storage;
		return $result;
	}
	protected function decodePayload(PacketSerializer $in) : void{
		$this->windowId = $in->getUnsignedVarInt();
		$count = $in->getUnsignedVarInt();
		for($i = 0; $i < $count; ++$i){
			$this->items[] = $in->getItemStackWrapper();
		}
		if($in->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_30){
			$this->customContainerName = CustomFullContainerName::read($in);
			if($in->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_40){
				$this->storage = $in->getItemStackWrapper();
			}else{
				$this->dynamicContainerSize = $in->getUnsignedVarInt();
			}
		}elseif($in->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_20){
			$this->customContainerName = new CustomFullContainerName(0, $in->getUnsignedVarInt());
		}
	}

	protected function encodePayload(PacketSerializer $out) : void{
		$out->putUnsignedVarInt($this->windowId);
		$out->putUnsignedVarInt(count($this->items));
		foreach($this->items as $item){
			$out->putItemStackWrapper($item);
		}
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
	}

	public function getConstructorArguments(PM_Packet $packet) : array {
		$customContainerName = new CustomFullContainerName($packet->containerName->getContainerId(), $packet->containerName->getDynamicId());
		return [
			$packet->windowId,
			$packet->items,
			$customContainerName,
			0,
			$packet->storage
		];
	}
}
