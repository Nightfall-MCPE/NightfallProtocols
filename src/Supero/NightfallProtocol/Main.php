<?php

declare(strict_types=1);

namespace Supero\NightfallProtocol;

use pocketmine\event\EventPriority;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\event\server\NetworkInterfaceRegisterEvent;
use pocketmine\network\mcpe\protocol\PacketViolationWarningPacket;
use pocketmine\network\mcpe\raklib\RakLibInterface;
use pocketmine\network\mcpe\StandardEntityEventBroadcaster;
use pocketmine\network\mcpe\StandardPacketBroadcaster;
use pocketmine\network\query\DedicatedQueryNetworkInterface;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use ReflectionException;
use Supero\NightfallProtocol\network\CustomRaklibInterface;
use Supero\NightfallProtocol\network\packets\PlayerAuthInputPacket;
use Supero\NightfallProtocol\network\static\convert\CustomTypeConverter;
use Supero\NightfallProtocol\network\static\CustomPacketPool;
use function dechex;
use function dirname;
use function get_class;
use function str_pad;
use const DIRECTORY_SEPARATOR;
use const STR_PAD_LEFT;

final class Main extends PluginBase
{
	private const PACKET_VIOLATION_WARNING_SEVERITY = [
		PacketViolationWarningPacket::SEVERITY_WARNING => "WARNING",
		PacketViolationWarningPacket::SEVERITY_FINAL_WARNING => "FINAL WARNING",
		PacketViolationWarningPacket::SEVERITY_TERMINATING_CONNECTION => "TERMINATION",
	];

	public static function getProtocolDataFolder() : string
	{
		return dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . "resources" . DIRECTORY_SEPARATOR . "versions";
	}

	/**
	 * @throws ReflectionException
	 */
	protected function onEnable() : void
	{
		$server = $this->getServer();

		$regInterface = function(Server $server, bool $ipV6){
			$typeConverter = CustomTypeConverter::getProtocolInstance();
			$packetBroadcaster = new StandardPacketBroadcaster(Server::getInstance());
			$entityEventBroadcaster = new StandardEntityEventBroadcaster($packetBroadcaster, $typeConverter);
			$server->getNetwork()->registerInterface(new CustomRaklibInterface(
				$server,
				$ipV6 ? $server->getIpv6() : $server->getIp(),
				$server->getPort(),
				$ipV6,
				$packetBroadcaster,
				$entityEventBroadcaster,
				$typeConverter
			));
		};

		($regInterface)($server, false);
		if($server->getConfigGroup()->getConfigBool("enable-ipv6", true)){
			($regInterface)($server, true);
		}

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
				if($packet instanceof PacketViolationWarningPacket){
					$this->getLogger()->debug("Packet Violation (" . self::PACKET_VIOLATION_WARNING_SEVERITY[$packet->getSeverity()] . ") from {$event->getOrigin()->getDisplayName()} message: '{$packet->getMessage()}' Packet ID: 0x" . str_pad(dechex($packet->getPacketId()), 2, "0", STR_PAD_LEFT));
				}
				//if(!$packet instanceof PlayerAuthInputPacket) $this->getLogger()->debug("Received " . $packet::class . " from " . $event->getOrigin()->getDisplayName());
			}, EventPriority::NORMAL, $this);
			$server->getPluginManager()->registerEvent(DataPacketSendEvent::class, function(DataPacketSendEvent $event) : void{
				foreach($event->getTargets() as $target){
					foreach($event->getPackets() as $packet){
						$this->getLogger()->debug("Sending " . $packet::class . " to " . $target->getDisplayName());
					}
				}
			}, EventPriority::NORMAL, $this);
		}

		CustomPacketPool::getInstance();
	}
}
