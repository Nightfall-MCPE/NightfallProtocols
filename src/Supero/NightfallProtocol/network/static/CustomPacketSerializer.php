<?php

namespace Supero\NightfallProtocol\network\static;

use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use Supero\NightfallProtocol\network\CustomProtocolInfo;

class CustomPacketSerializer extends PacketSerializer
{
    public static int $protocol = CustomProtocolInfo::CURRENT_PROTOCOL;
    public static function setProtocol(int $protocol) : void
    {
        self::$protocol = $protocol;
    }

    /**
     * @param string $buffer
     * @param int $offset
     * @return CustomPacketSerializer
     */
    public static function decoder(string $buffer, int $offset): PacketSerializer
    {
        return new self($buffer, $offset);
    }

    /**
     * @return CustomPacketSerializer
     */
    public static function encoder(): PacketSerializer
    {
        return new self();
    }

    public static function getProtocol() : int
    {
        return self::$protocol;
    }

}