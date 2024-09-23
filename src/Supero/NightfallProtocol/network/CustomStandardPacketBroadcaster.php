<?php

declare(strict_types=1);

namespace Supero\NightfallProtocol\network;

use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\PacketBroadcaster;
use pocketmine\Server;
use pocketmine\timings\Timings;
use pocketmine\utils\BinaryStream;
use Supero\NightfallProtocol\network\static\convert\CustomTypeConverter;
use Supero\NightfallProtocol\network\static\CustomPacketBatch;
use Supero\NightfallProtocol\network\static\CustomPacketSerializer;
use Supero\NightfallProtocol\network\static\PacketConverter;
use Supero\NightfallProtocol\utils\ProtocolUtils;
use function count;
use function log;
use function spl_object_id;
use function strlen;

class CustomStandardPacketBroadcaster implements PacketBroadcaster{
	public function __construct(
		private Server $server,
		private int $protocolId
	){}

	public function broadcastPackets(array $recipients, array $packets) : void{
		if(DataPacketSendEvent::hasHandlers()){
			$ev = new DataPacketSendEvent($recipients, $packets);
			$ev->call();
			if($ev->isCancelled()){
				return;
			}
			$packets = $ev->getPackets();
		}

		$compressors = [];

		/** @var NetworkSession[][] $targetsByCompressor */
		$targetsByCompressor = [];
		foreach($recipients as $recipient){
			$compressor = $recipient->getCompressor();
			$compressors[spl_object_id($compressor)] = $compressor;

			$targetsByCompressor[spl_object_id($compressor)][] = $recipient;
		}

		$totalLength = 0;
		$packetBuffers = [];
		foreach($packets as $packet){
			$packet = PacketConverter::handleClientbound($packet, CustomTypeConverter::getProtocolInstance($this->protocolId), null);
			$encoder = CustomPacketSerializer::encoder();
			$encoder->setProtocol($this->protocolId);

			$buffer = NetworkSession::encodePacketTimed($encoder, $packet);
			//varint length prefix + packet buffer
			$totalLength += (((int) log(strlen($buffer), 128)) + 1) + strlen($buffer);
			$packetBuffers[] = $buffer;
		}

		foreach($targetsByCompressor as $compressorId => $compressorTargets){
			$compressor = $compressors[$compressorId];

			$threshold = $compressor->getCompressionThreshold();
			if(count($compressorTargets) > 1 && $threshold !== null && $totalLength >= $threshold){
				//do not prepare shared batch unless we're sure it will be compressed
				$stream = new BinaryStream();
				CustomPacketBatch::encodeRaw($stream, $packetBuffers);
				$batchBuffer = $stream->getBuffer();

				$batch = ProtocolUtils::prepareBatch($batchBuffer, $compressor, $this->server, $this->protocolId, timings: Timings::$playerNetworkSendCompressBroadcast);
				foreach($compressorTargets as $target){
					$target->queueCompressed($batch);
				}
			}else{
				foreach($compressorTargets as $target){
					foreach($packetBuffers as $packetBuffer){
						$target->addToSendBuffer($packetBuffer);
					}
				}
			}
		}
	}
}
