<?php

namespace Supero\NightfallProtocol\utils;

use pocketmine\network\mcpe\PacketBroadcaster;
use pocketmine\Server;
use Supero\NightfallProtocol\network\CustomStandardPacketBroadcaster;

class ProtocolUtils
{
    private static array $packetBroadcasters = [];

    public static function getPacketBroadcaster(int $protocolId) : PacketBroadcaster{
        return self::$packetBroadcasters[$protocolId] ??= new CustomStandardPacketBroadcaster(Server::getInstance(), $protocolId);
    }

}