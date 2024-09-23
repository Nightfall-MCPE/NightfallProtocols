<?php

declare(strict_types=1);

namespace Supero\NightfallProtocol\network\chunk;

use pocketmine\network\mcpe\compression\CompressBatchPromise;
use pocketmine\network\mcpe\compression\Compressor;
use pocketmine\network\mcpe\protocol\types\ChunkPosition;
use pocketmine\scheduler\AsyncTask;
use pocketmine\thread\NonThreadSafeValue;
use pocketmine\utils\BinaryStream;
use pocketmine\world\format\Chunk;
use pocketmine\world\format\io\FastChunkSerializer;
use Supero\NightfallProtocol\network\chunk\serializer\CustomChunkSerializer;
use Supero\NightfallProtocol\network\CustomProtocolInfo;
use Supero\NightfallProtocol\network\packets\LevelChunkPacket;
use Supero\NightfallProtocol\network\static\convert\CustomTypeConverter;
use Supero\NightfallProtocol\network\static\CustomPacketBatch;
use function chr;

class CustomChunkRequestTask extends AsyncTask{
	private const TLS_KEY_PROMISE = "promise";

	protected string $chunk;
	protected int $chunkX;
	protected int $chunkZ;
	/** @phpstan-var DimensionIds::* */
	private int $dimensionId;
	/** @phpstan-var NonThreadSafeValue<Compressor> */
	protected NonThreadSafeValue $compressor;
	private string $tiles;
	protected int $protocol;

	/**
	 * @phpstan-param DimensionIds::* $dimensionId
	 */
	public function __construct(int $chunkX, int $chunkZ, int $dimensionId, Chunk $chunk, CompressBatchPromise $promise, Compressor $compressor, int $protocol){
		$this->compressor = new NonThreadSafeValue($compressor);

		$this->chunk = FastChunkSerializer::serializeTerrain($chunk);
		$this->chunkX = $chunkX;
		$this->chunkZ = $chunkZ;
		$this->dimensionId = $dimensionId;
		$this->tiles = CustomChunkSerializer::serializeTiles($chunk);
		$this->protocol = $protocol;

		$this->storeLocal(self::TLS_KEY_PROMISE, $promise);
	}

	public function onRun() : void{
		$chunk = FastChunkSerializer::deserializeTerrain($this->chunk);
		$dimensionId = $this->dimensionId;

		$subCount = CustomChunkSerializer::getSubChunkCount($chunk, $dimensionId);
		$converter = CustomTypeConverter::getProtocolInstance($this->protocol);
		$payload = CustomChunkSerializer::serializeFullChunk($chunk, $dimensionId, $converter->getCustomBlockTranslator(), $this->protocol, $this->tiles);

		$stream = new BinaryStream();

		CustomPacketBatch::encodePackets($this->protocol, $stream, [LevelChunkPacket::createPacket(new ChunkPosition($this->chunkX, $this->chunkZ), $dimensionId, $subCount, false, null, $payload)]);

		$compressor = $this->compressor->deserialize();
		$this->setResult(($this->protocol >= CustomProtocolInfo::PROTOCOL_1_20_60 ? chr($compressor->getNetworkId()) : '') . $compressor->compress($stream->getBuffer()));
	}

	public function onCompletion() : void{
		/** @var CompressBatchPromise $promise */
		$promise = $this->fetchLocal(self::TLS_KEY_PROMISE);
		$promise->resolve($this->getResult());
	}
}
