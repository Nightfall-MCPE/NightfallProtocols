<?php

declare(strict_types=1);

namespace Supero\NightfallProtocol\network;

class CustomProtocolInfo {

	public const CURRENT_PROTOCOL = self::PROTOCOL_1_21_50;

	public const ACCEPTED_PROTOCOLS = [
		self::CURRENT_PROTOCOL,
		self::PROTOCOL_1_21_40,
		self::PROTOCOL_1_21_30,
		self::PROTOCOL_1_21_20,
		self::PROTOCOL_1_21_2,
		self::PROTOCOL_1_21_0,
		self::PROTOCOL_1_20_80,
		self::PROTOCOL_1_20_70,
		self::PROTOCOL_1_20_60,
		self::PROTOCOL_1_20_50,
	];

	//Latest + Version unharmed.
	//In case a version has no real protocol/item/block changes.
	//If the latest has changed from the previous, only put the current protocol here
	public const COMBINED_LATEST = [
		self::CURRENT_PROTOCOL
	];

	public const PROTOCOL_1_21_50 = 766;
	public const PROTOCOL_1_21_40 = 748;
	public const PROTOCOL_1_21_30 = 729;
	public const PROTOCOL_1_21_20 = 712;
	public const PROTOCOL_1_21_2 = 686;
	public const PROTOCOL_1_21_0 = 685;
	public const PROTOCOL_1_20_80 = 671;
	public const PROTOCOL_1_20_70 = 662;
	public const PROTOCOL_1_20_60 = 649;
	public const PROTOCOL_1_20_50 = 630;

	public const TICK_SYNC_PACKET = 0x17;

}
