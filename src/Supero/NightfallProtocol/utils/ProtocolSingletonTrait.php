<?php

declare(strict_types=1);

namespace Supero\NightfallProtocol\utils;

use Supero\NightfallProtocol\network\CustomProtocolInfo;

trait ProtocolSingletonTrait{

	/** @var self[] */
	private static $instance = [];

	private static function make(int $protocolId) : self{
		return new self($protocolId);
	}

	private function __construct(protected readonly int $protocolId){
		//NOOP
	}

	public static function getProtocolInstance(int $protocolId = CustomProtocolInfo::CURRENT_PROTOCOL) : self{
		return self::$instance[$protocolId] ??= self::make($protocolId);
	}

	/**
	 * @return array<int, self>
	 */
	public static function getAll(bool $create = false) : array{
		if($create){
			foreach(CustomProtocolInfo::ACCEPTED_PROTOCOLS as $protocolId){
				self::getProtocolInstance($protocolId);
			}
		}

		return self::$instance;
	}

	public static function setProtocolInstance(self $instance, int $protocolId) : void{
		self::$instance[$protocolId] = $instance;
	}

	public static function reset() : void{
		self::$instance = [];
	}
}
