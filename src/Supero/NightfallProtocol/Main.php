<?php

declare(strict_types=1);

namespace Supero\NightfallProtocol;

use pocketmine\event\EventPriority;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\event\server\NetworkInterfaceRegisterEvent;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\protocol\PacketViolationWarningPacket;
use pocketmine\network\mcpe\raklib\RakLibInterface;
use pocketmine\network\mcpe\StandardEntityEventBroadcaster;
use pocketmine\network\mcpe\StandardPacketBroadcaster;
use pocketmine\network\query\DedicatedQueryNetworkInterface;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use ReflectionException;
use Supero\NightfallProtocol\network\CustomRaklibInterface;

class Main extends PluginBase
{
    public static function getProtocolDataFolder(): string
    {
        return dirname(__DIR__, 3) . "\\resources\\versions";
    }

    /**
     * @throws ReflectionException
     */
    protected function onEnable(): void
    {

        $server = $this->getServer();

        $regInterface = function(Server $server, bool $ipv6){
            $typeConverter = TypeConverter::getInstance();
            $packetBroadcaster = new StandardPacketBroadcaster(Server::getInstance());
            $entityEventBroadcaster = new StandardEntityEventBroadcaster($packetBroadcaster, $typeConverter);
            $server->getNetwork()->registerInterface(new CustomRaklibInterface(
                $server,
                $server->getIp(),
                $server->getPort(),
                $ipv6,
                $packetBroadcaster,
                $entityEventBroadcaster,
                $typeConverter
            ));
        };


        ($regInterface)($server, $server->getConfigGroup()->getConfigBool("enable-ipv6", true));

        $server->getPluginManager()->registerEvent(NetworkInterfaceRegisterEvent::class, function(NetworkInterfaceRegisterEvent $event) : void{
            $interface = $event->getInterface();
            if($interface instanceof CustomRaklibInterface || (!$interface instanceof RakLibInterface && !$interface instanceof DedicatedQueryNetworkInterface)){
                return;
            }

            $this->getLogger()->debug("Prevented network interface " . get_class($interface) . " from being registered");
            $event->cancel();
        }, EventPriority::NORMAL, $this);

        if($this->getConfig()->get("debug-mode")){
            $server->getPluginManager()->registerEvent(DataPacketReceiveEvent::class, function(DataPacketReceiveEvent $event) : void{
                $packet = $event->getPacket();
                var_dump($packet::class);
                if($packet instanceof PacketViolationWarningPacket){
                    $this->getLogger()->warning("Received [{$packet->getType()}] Packet Violation message: '{$packet->getMessage()}' Packet ID: 0x" . str_pad(dechex($packet->getPacketId()), 2, "0", STR_PAD_LEFT));
                }
            }, EventPriority::NORMAL, $this);
            $server->getPluginManager()->registerEvent(DataPacketSendEvent::class, function(DataPacketSendEvent $event) : void{
                foreach ($event->getPackets() as $packet) {
                    var_dump($packet::class);
                }
            }, EventPriority::NORMAL, $this);

        }
    }
}
