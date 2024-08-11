<?php

namespace Supero\NightfallProtocol\network\static;

use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\Packet;
use pocketmine\network\mcpe\protocol\PacketPool;
use pocketmine\utils\Binary;
use pocketmine\utils\BinaryDataException;
use Supero\NightfallProtocol\network\packets\CodeBuilderSourcePacket;
use Supero\NightfallProtocol\network\packets\ContainerClosePacket;
use Supero\NightfallProtocol\network\packets\LecternUpdatePacket;
use Supero\NightfallProtocol\network\packets\MobEffectPacket;
use Supero\NightfallProtocol\network\packets\PlayerAuthInputPacket;
use Supero\NightfallProtocol\network\packets\ResourcePacksInfoPacket;
use Supero\NightfallProtocol\network\packets\ResourcePackStackPacket;
use Supero\NightfallProtocol\network\packets\SetActorMotionPacket;
use Supero\NightfallProtocol\network\packets\StartGamePacket;
use Supero\NightfallProtocol\network\packets\TextPacket;
use Supero\NightfallProtocol\network\packets\UpdatePlayerGameTypePacket;

class CustomPacketPool extends PacketPool
{
    protected static ?PacketPool $instance = null;

    public static function getInstance() : self{
        if(self::$instance === null){
            self::$instance = new self;
        }
        return self::$instance;
    }
    public function __construct()
    {
        parent::__construct();

        $this->registerPacket(new CodeBuilderSourcePacket());
        $this->registerPacket(new ContainerClosePacket());
        $this->registerPacket(new LecternUpdatePacket());
        $this->registerPacket(new MobEffectPacket());
        $this->registerPacket(new PlayerAuthInputPacket());
        $this->registerPacket(new ResourcePacksInfoPacket());
        $this->registerPacket(new ResourcePackStackPacket());
        $this->registerPacket(new SetActorMotionPacket());
        $this->registerPacket(new StartGamePacket());
        $this->registerPacket(new TextPacket());
        $this->registerPacket(new UpdatePlayerGameTypePacket());
    }

    public function registerPacket(Packet $packet) : void{
        $this->pool[$packet->pid()] = clone $packet;
    }

    public function getPacketById(int $pid) : ?Packet{
        return isset($this->pool[$pid]) ? clone $this->pool[$pid] : null;
    }

    /**
     * @throws BinaryDataException
     */
    public function getPacket(string $buffer) : ?Packet{
        $offset = 0;
        return $this->getPacketById(Binary::readUnsignedVarInt($buffer, $offset) & DataPacket::PID_MASK);
    }
}