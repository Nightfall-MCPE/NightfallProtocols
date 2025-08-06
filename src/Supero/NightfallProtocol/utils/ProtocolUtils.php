<?php

declare(strict_types=1);

namespace Supero\NightfallProtocol\utils;

use pocketmine\network\mcpe\compression\CompressBatchPromise;
use pocketmine\network\mcpe\compression\Compressor;
use pocketmine\network\mcpe\EntityEventBroadcaster;
use pocketmine\network\mcpe\PacketBroadcaster;
use pocketmine\network\mcpe\protocol\types\CompressionAlgorithm;
use pocketmine\Server;
use pocketmine\timings\Timings;
use pocketmine\timings\TimingsHandler;
use Supero\NightfallProtocol\network\compression\CompressBatchTask;
use Supero\NightfallProtocol\network\CustomProtocolInfo;
use Supero\NightfallProtocol\network\CustomStandardEntityEventBroadcaster;
use Supero\NightfallProtocol\network\CustomStandardPacketBroadcaster;
use Supero\NightfallProtocol\network\static\convert\CustomTypeConverter;
use function chr;
use function strlen;

class ProtocolUtils
{
	private static array $packetBroadcasters = [];
	private static array $entityEventBroadcasters = [];

	public static function getPacketBroadcaster(int $protocolId) : PacketBroadcaster{
		return self::$packetBroadcasters[$protocolId] ??= new CustomStandardPacketBroadcaster(Server::getInstance(), $protocolId);
	}
	public static function getEntityEventBroadcaster(int $protocolId) : EntityEventBroadcaster{
		return self::$entityEventBroadcasters[$protocolId] ??= new CustomStandardEntityEventBroadcaster(
			self::getPacketBroadcaster($protocolId),
			CustomTypeConverter::getProtocolInstance($protocolId)
		);
	}

	public static function prepareBatch(string $buffer, Compressor $compressor, Server $server, int $protocol, ?bool $sync = null, ?TimingsHandler $timings = null) : CompressBatchPromise|string{
		$timings ??= Timings::$playerNetworkSendCompress;
		try{
			$timings->startTiming();

			$threshold = $compressor->getCompressionThreshold();
			if($threshold === null || strlen($buffer) < $compressor->getCompressionThreshold() && $protocol >= CustomProtocolInfo::PROTOCOL_1_20_60){
				$compressionType = CompressionAlgorithm::NONE;
				$compressed = $buffer;

			}else{
				$sync ??= !(ReflectionUtils::getProperty(Server::class, $server, "networkCompressionAsync"));

				if(!$sync && strlen($buffer) >= ReflectionUtils::getProperty(Server::class, $server, "networkCompressionAsyncThreshold")){
					$promise = new CompressBatchPromise();
					$task = new CompressBatchTask($buffer, $promise, $compressor, $protocol);
					$server->getAsyncPool()->submitTask($task);
					return $promise;
				}

				$compressionType = $compressor->getNetworkId();
				$compressed = $compressor->compress($buffer);
			}

			return ($protocol >= CustomProtocolInfo::PROTOCOL_1_20_60 ? chr($compressionType) : '') . $compressed;
		}finally{
			$timings->stopTiming();
		}
	}
}
