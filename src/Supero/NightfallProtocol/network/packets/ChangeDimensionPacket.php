<?php

namespace Supero\NightfallProtocol\network\packets;

use pocketmine\math\Vector3;
use Supero\NightfallProtocol\network\CustomProtocolInfo;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\PacketHandlerInterface;
use pocketmine\network\mcpe\protocol\ChangeDimensionPacket as PM_Packet;

class ChangeDimensionPacket extends PM_Packet
{
    public int $dimension;
    public Vector3 $position;
    public bool $respawn = false;
    private ?int $loadingScreenId = null;

    /**
     * @generate-create-func
     */
    public static function create(int $dimension, Vector3 $position, bool $respawn, ?int $loadingScreenId): self{
        $result = new self;
        $result->dimension = $dimension;
        $result->position = $position;
        $result->respawn = $respawn;
        $result->loadingScreenId = $loadingScreenId;
        return $result;
    }

    protected function decodePayload(PacketSerializer $in): void {
        $this->dimension = $in->getVarInt();
        $this->position = $in->getVector3();
        $this->respawn = $in->getBool();
        if($in->getProtocolId() >= CustomProtocolInfo::PROTOCOL_1_21_20){
            $this->loadingScreenId = $in->readOptional(fn() => $in->getLInt());
        }
    }

    protected function encodePayload(PacketSerializer $out): void {
        $out->putVarInt($this->dimension);
        $out->putVector3($this->position);
        $out->putBool($this->respawn);
        if($out->getProtocolId() >= CustomProtocolInfo::PROTOCOL_1_21_20){
            $out->writeOptional($this->loadingScreenId, $out->putLInt(...));
        }
    }

    public function handle(PacketHandlerInterface $handler): bool {
        return $handler->handleChangeDimension($this);
    }

}