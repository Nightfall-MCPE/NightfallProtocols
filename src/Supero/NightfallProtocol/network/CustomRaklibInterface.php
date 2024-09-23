<?php

declare(strict_types=1);

namespace Supero\NightfallProtocol\network;

use pocketmine\network\mcpe\compression\ZlibCompressor;
use pocketmine\network\mcpe\raklib\RakLibInterface;
use pocketmine\network\mcpe\raklib\RakLibPacketSender;
use pocketmine\Server;
use ReflectionException;
use Supero\NightfallProtocol\network\static\CustomPacketPool;
use Supero\NightfallProtocol\utils\ReflectionUtils;

class CustomRaklibInterface extends RakLibInterface
{
	/**
	 * @throws ReflectionException
	 */
	public function onClientConnect(int $sessionId, string $address, int $port, int $clientID) : void
	{
		$session = new CustomNetworkSession(
			Server::getInstance(),
			ReflectionUtils::getProperty(RakLibInterface::class, $this, "network")->getSessionManager(),
			CustomPacketPool::getInstance(),
			new RakLibPacketSender($sessionId, $this),
			ReflectionUtils::getProperty(RakLibInterface::class, $this, "packetBroadcaster"),
			ReflectionUtils::getProperty(RakLibInterface::class, $this, "entityEventBroadcaster"),
			ZlibCompressor::getInstance(),
			ReflectionUtils::getProperty(RakLibInterface::class, $this, "typeConverter"),
			$address,
			$port
		);

		$sessions = ReflectionUtils::getProperty(RakLibInterface::class, $this, "sessions");
		$sessions[$sessionId] = $session;
		ReflectionUtils::setProperty(RakLibInterface::class, $this, "sessions", $sessions);
	}

}
