<?php

namespace Supero\NightfallProtocol\network\packets;

use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\PacketHandlerInterface;
use pocketmine\network\mcpe\protocol\PlayerArmorDamagePacket as PM_Packet;
use Supero\NightfallProtocol\network\CustomProtocolInfo;

class PlayerArmorDamagePacket extends PM_Packet
{
    private const FLAG_HEAD = 0;
    private const FLAG_CHEST = 1;
    private const FLAG_LEGS = 2;
    private const FLAG_FEET = 3;
    private const FLAG_BODY = 4;

    private ?int $headSlotDamage;
    private ?int $chestSlotDamage;
    private ?int $legsSlotDamage;
    private ?int $feetSlotDamage;
    private ?int $bodySlotDamage;

    /**
     * @generate-create-func
     */
    public static function create(?int $headSlotDamage, ?int $chestSlotDamage, ?int $legsSlotDamage, ?int $feetSlotDamage, ?int $bodySlotDamage) : self{
        $result = new self;
        $result->headSlotDamage = $headSlotDamage;
        $result->chestSlotDamage = $chestSlotDamage;
        $result->legsSlotDamage = $legsSlotDamage;
        $result->feetSlotDamage = $feetSlotDamage;
        $result->bodySlotDamage = $bodySlotDamage;
        return $result;
    }

    public function getHeadSlotDamage() : ?int{ return $this->headSlotDamage; }

    public function getChestSlotDamage() : ?int{ return $this->chestSlotDamage; }

    public function getLegsSlotDamage() : ?int{ return $this->legsSlotDamage; }

    public function getFeetSlotDamage() : ?int{ return $this->feetSlotDamage; }

    public function getBodySlotDamage() : ?int{ return $this->bodySlotDamage; }

    private function maybeReadDamage(int $flags, int $flag, PacketSerializer $in) : ?int{
        if(($flags & (1 << $flag)) !== 0){
            return $in->getVarInt();
        }
        return null;
    }

    protected function decodePayload(PacketSerializer $in) : void{
        $flags = $in->getByte();

        $this->headSlotDamage = $this->maybeReadDamage($flags, self::FLAG_HEAD, $in);
        $this->chestSlotDamage = $this->maybeReadDamage($flags, self::FLAG_CHEST, $in);
        $this->legsSlotDamage = $this->maybeReadDamage($flags, self::FLAG_LEGS, $in);
        $this->feetSlotDamage = $this->maybeReadDamage($flags, self::FLAG_FEET, $in);
        if($in->getProtocolId() >= CustomProtocolInfo::PROTOCOL_1_21_20){
            $this->bodySlotDamage = $this->maybeReadDamage($flags, self::FLAG_BODY, $in);
        }
    }

    private function composeFlag(?int $field, int $flag) : int{
        return $field !== null ? (1 << $flag) : 0;
    }

    private function maybeWriteDamage(?int $field, PacketSerializer $out) : void{
        if($field !== null){
            $out->putVarInt($field);
        }
    }

    protected function encodePayload(PacketSerializer $out) : void{
        $out->putByte(
            $this->composeFlag($this->headSlotDamage, self::FLAG_HEAD) |
            $this->composeFlag($this->chestSlotDamage, self::FLAG_CHEST) |
            $this->composeFlag($this->legsSlotDamage, self::FLAG_LEGS) |
            $this->composeFlag($this->feetSlotDamage, self::FLAG_FEET) |
            ($out->getProtocolId() >= CustomProtocolInfo::PROTOCOL_1_21_20 ? $this->composeFlag($this->bodySlotDamage, self::FLAG_BODY) : 0)
        );

        $this->maybeWriteDamage($this->headSlotDamage, $out);
        $this->maybeWriteDamage($this->chestSlotDamage, $out);
        $this->maybeWriteDamage($this->legsSlotDamage, $out);
        $this->maybeWriteDamage($this->feetSlotDamage, $out);
        if($out->getProtocolId() >= CustomProtocolInfo::PROTOCOL_1_21_20){
            $this->maybeWriteDamage($this->bodySlotDamage, $out);
        }
    }

    public function handle(PacketHandlerInterface $handler) : bool{
        return $handler->handlePlayerArmorDamage($this);
    }
}