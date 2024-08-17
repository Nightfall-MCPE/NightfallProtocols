<?php

namespace Supero\NightfallProtocol\network\packets;

use pocketmine\network\mcpe\protocol\MobArmorEquipmentPacket as PM_Packet;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use ReflectionException;
use Supero\NightfallProtocol\network\CustomProtocolInfo;
use Supero\NightfallProtocol\utils\ReflectionUtils;

class MobArmorEquipmentPacket extends PM_Packet {
    public int $actorRuntimeId;
    public ItemStackWrapper $head;
    public ItemStackWrapper $chest;
    public ItemStackWrapper $legs;
    public ItemStackWrapper $feet;
    public ItemStackWrapper $body;

    /**
     * @generate-create-func
     */
    public static function createPacket(int $actorRuntimeId, ItemStackWrapper $head, ItemStackWrapper $chest, ItemStackWrapper $legs, ItemStackWrapper $feet, ItemStackWrapper $body) : self {
        $result = new self;
        $result->actorRuntimeId = $actorRuntimeId;
        $result->head = $head;
        $result->chest = $chest;
        $result->legs = $legs;
        $result->feet = $feet;
        $result->body = $body;
        return $result;
    }
    protected function decodePayload(PacketSerializer $in) : void {
       $this->actorRuntimeId = $in->getInt();
       $this->head = $in->getItemStackWrapper();
       $this->chest = $in->getItemStackWrapper();
       $this->legs = $in->getItemStackWrapper();
       $this->feet = $in->getItemStackWrapper();
       if ($in->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_20) {
           $this->body = $in->getItemStackWrapper();
       }
    }
    protected function encodePayload(PacketSerializer $out) : void {
       $out->putInt($this->actorRuntimeId);
       $out->putItemStackWrapper($this->head);
       $out->putItemStackWrapper($this->chest);
       $out->putItemStackWrapper($this->legs);
       $out->putItemStackWrapper($this->feet);
       if ($out->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_20) {
           $out->putItemStackWrapper($this->body);
       }
    }

    public function getConstructorArguments(PM_Packet $packet): array {
        return [
            $packet->actorRuntimeId,
            $packet->head,
            $packet->chest,
            $packet->legs,
            $packet->feet,
            $packet->body
        ];
    }
}