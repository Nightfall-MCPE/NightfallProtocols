<?php

namespace Supero\NightfallProtocol\network\packets;

use pocketmine\network\mcpe\protocol\InventorySlotPacket as PM_Packet;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;

use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use Supero\NightfallProtocol\network\CustomProtocolInfo;

class InventorySlotPacket extends PM_Packet {
    public int $windowId;
    public int $inventorySlot;
    public ItemStackWrapper $item;
    public int $dynamicContainerId;

    /**
     * @generate-create-func
     */
    public static function createPacket(int $windowId, int $inventorySlot, ItemStackWrapper $item, int $dynamicContainerId) : self {
        $result = new self;
        $result->windowId = $windowId;
        $result->inventorySlot = $inventorySlot;
        $result->item = $item;
        $result->dynamicContainerId = $dynamicContainerId;
        return $result;
    }
    protected function decodePayload(PacketSerializer $in) : void {
       $this->windowId = $in->getUnsignedVarInt();
       $this->inventorySlot = $in->getUnsignedVarInt();
       if ($in->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_20) {
           $this->dynamicContainerId = $in->getUnsignedVarInt();
       }
        $this->item = $in->getItemStackWrapper();
    }
    protected function encodePayload(PacketSerializer $out) : void {
       $out->putUnsignedVarInt($this->windowId);
       $out->putUnsignedVarInt($this->inventorySlot);
       if ($out->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_20) {
           $out->putUnsignedVarInt($this->dynamicContainerId);
       }
       $out->putItemStackWrapper($this->item);
    }
    public function getConstructorArguments(PM_Packet $packet): array {
        return [
            $packet->windowId,
            $packet->inventorySlot,
            $packet->item,
            $packet->dynamicContainerId,
        ];
    }
}