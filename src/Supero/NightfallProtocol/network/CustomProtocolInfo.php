<?php

namespace Supero\NightfallProtocol\network;

class CustomProtocolInfo {

    public const CURRENT_PROTOCOL = self::PROTOCOL_1_21_2;

    public const ACCEPTED_PROTOCOLS = [
        self::CURRENT_PROTOCOL,
        self::PROTOCOL_1_21_0,
        self::PROTOCOL_1_20_80
	];

    public const PROTOCOL_1_21_2 = 686;
    public const PROTOCOL_1_21_0 = 685;
    public const PROTOCOL_1_20_80 = 671;
}