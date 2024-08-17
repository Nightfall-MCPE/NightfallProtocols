<?php

namespace Supero\NightfallProtocol\network\packets;

use pocketmine\network\mcpe\protocol\InventoryContentPacket as PM_Packet;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;

use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use Supero\NightfallProtocol\network\CustomProtocolInfo;

class InventoryContentPacket extends PM_Packet {
    public int $windowId;
    /** @var ItemStackWrapper[] */
    public array $items = [];
    public int $dynamicContainerId;

    /**
     * @generate-create-func
     */
    public static function createPacket(int $windowId, array $items, int $dynamicContainerId) : self {
        $result = new self;
        $result->windowId = $windowId;
        $result->items = $items;
        $result->dynamicContainerId = $dynamicContainerId;
        return $result;
    }
    protected function decodePayload(PacketSerializer $in) : void {
       $this->windowId = $in->getInt();
       if ($in->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_20) {
           $this->dynamicContainerId = $in->getUnsignedVarInt();
       }
    }
    protected function encodePayload(PacketSerializer $out) : void {
       $out->putInt($this->windowId);
        $out->putUnsignedVarInt(count($this->items));
        foreach($this->items as $item){
            $out->putItemStackWrapper($item);
        }
        if ($out->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_20) {
           $out->putInt($this->dynamicContainerId);
        }
    }
    public function getConstructorArguments(PM_Packet $packet): array {
        return [
            $packet->windowId,
            $packet->items,
            $packet->dynamicContainerId,
        ];
    }
}