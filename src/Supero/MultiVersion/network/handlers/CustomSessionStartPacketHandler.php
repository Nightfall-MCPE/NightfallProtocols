<?php

declare(strict_types=1);

namespace Supero\MultiVersion\network\handlers;

use Closure;
use pocketmine\network\mcpe\handler\PacketHandler;
use pocketmine\network\mcpe\protocol\NetworkSettingsPacket;
use pocketmine\network\mcpe\protocol\RequestNetworkSettingsPacket;
use ReflectionException;
use Supero\MultiVersion\network\CustomNetworkSession;
use Supero\MultiVersion\network\CustomProtocolInfo;
use function in_array;

final class CustomSessionStartPacketHandler extends PacketHandler{

	/**
	 * @phpstan-param Closure() : void $onSuccess
	 */
	public function __construct(
		private CustomNetworkSession $session,
		private Closure $onSuccess
	){}

	/**
	 * @throws ReflectionException
	 */
	public function handleRequestNetworkSettings(RequestNetworkSettingsPacket $packet) : bool{
		$protocolVersion = $packet->getProtocolVersion();
		if(!$this->isCompatibleProtocol($protocolVersion)){
			$this->session->disconnectIncompatibleProtocol($protocolVersion);

			return true;
		}

		$this->session->setProtocol($protocolVersion);

		$this->session->sendDataPacket(NetworkSettingsPacket::create(
			NetworkSettingsPacket::COMPRESS_EVERYTHING,
			$this->session->getCompressor()->getNetworkId(),
			false,
			0,
			0
		));
		($this->onSuccess)();

		return true;
	}

	protected function isCompatibleProtocol(int $protocolVersion) : bool{
		return in_array($protocolVersion, CustomProtocolInfo::ACCEPTED_PROTOCOLS, true);
	}
}
