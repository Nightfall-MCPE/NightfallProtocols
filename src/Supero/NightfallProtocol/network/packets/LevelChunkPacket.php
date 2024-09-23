<?php

declare(strict_types=1);

namespace Supero\NightfallProtocol\network\packets;

use pocketmine\network\mcpe\protocol\LevelChunkPacket as PM_Packet;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\types\ChunkPosition;
use pocketmine\utils\Limits;
use Supero\NightfallProtocol\network\CustomProtocolInfo;
use function count;
use const PHP_INT_MAX;

class LevelChunkPacket extends PM_Packet {

	/**
	 * Client will request all subchunks as needed up to the top of the world
	 */
	private const CLIENT_REQUEST_FULL_COLUMN_FAKE_COUNT = Limits::UINT32_MAX;
	/**
	 * Client will request subchunks as needed up to the height written in the packet, and assume that anything above
	 * that height is air (wtf mojang ...)
	 */
	private const CLIENT_REQUEST_TRUNCATED_COLUMN_FAKE_COUNT = Limits::UINT32_MAX - 1;

	//this appears large enough for a world height of 1024 blocks - it may need to be increased in the future
	private const MAX_BLOB_HASHES = 64;

	private ChunkPosition $chunkPosition;
	/** @phpstan-var DimensionIds::* */
	private int $dimensionId;
	private int $subChunkCount;
	private bool $clientSubChunkRequestsEnabled;
	/** @var int[]|null */
	private ?array $usedBlobHashes = null;
	private string $extraPayload;

	/**
	 * @generate-create-func
	 */
	public static function createPacket(ChunkPosition $chunkPosition, int $dimensionId, int $subChunkCount, bool $clientSubChunkRequestsEnabled, ?array $usedBlobHashes, string $extraPayload) : self {
		$result = new self();
		$result->chunkPosition = $chunkPosition;
		$result->dimensionId = $dimensionId;
		$result->subChunkCount = $subChunkCount;
		$result->clientSubChunkRequestsEnabled = $clientSubChunkRequestsEnabled;
		$result->usedBlobHashes = $usedBlobHashes;
		$result->extraPayload = $extraPayload;
		return $result;
	}
	protected function decodePayload(PacketSerializer $in) : void {
		$this->chunkPosition = ChunkPosition::read($in);
		if ($in->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_20_60) {
			$this->dimensionId = $in->getVarInt();
		}

		$subChunkCountButNotReally = $in->getUnsignedVarInt();
		if($subChunkCountButNotReally === self::CLIENT_REQUEST_FULL_COLUMN_FAKE_COUNT){
			$this->clientSubChunkRequestsEnabled = true;
			$this->subChunkCount = PHP_INT_MAX;
		}elseif($subChunkCountButNotReally === self::CLIENT_REQUEST_TRUNCATED_COLUMN_FAKE_COUNT){
			$this->clientSubChunkRequestsEnabled = true;
			$this->subChunkCount = $in->getLShort();
		}else{
			$this->clientSubChunkRequestsEnabled = false;
			$this->subChunkCount = $subChunkCountButNotReally;
		}

		$cacheEnabled = $in->getBool();
		if($cacheEnabled){
			$this->usedBlobHashes = [];
			$count = $in->getUnsignedVarInt();
			if($count > self::MAX_BLOB_HASHES){
				throw new PacketDecodeException("Expected at most " . self::MAX_BLOB_HASHES . " blob hashes, got " . $count);
			}
			for($i = 0; $i < $count; ++$i){
				$this->usedBlobHashes[] = $in->getLLong();
			}
		}
		$this->extraPayload = $in->getString();
	}
	protected function encodePayload(PacketSerializer $out) : void {
		$this->chunkPosition->write($out);
		if ($out->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_20_60) {
			$out->putVarInt($this->dimensionId);
		}

		if($this->clientSubChunkRequestsEnabled){
			if($this->subChunkCount === PHP_INT_MAX){
				$out->putUnsignedVarInt(self::CLIENT_REQUEST_FULL_COLUMN_FAKE_COUNT);
			}else{
				$out->putUnsignedVarInt(self::CLIENT_REQUEST_TRUNCATED_COLUMN_FAKE_COUNT);
				$out->putLShort($this->subChunkCount);
			}
		}else{
			$out->putUnsignedVarInt($this->subChunkCount);
		}

		$out->putBool($this->usedBlobHashes !== null);
		if($this->usedBlobHashes !== null){
			$out->putUnsignedVarInt(count($this->usedBlobHashes));
			foreach($this->usedBlobHashes as $hash){
				$out->putLLong($hash);
			}
		}
		$out->putString($this->extraPayload);
	}
	public function getConstructorArguments(PM_Packet $packet) : array {
		return [
			$packet->getChunkPosition(),
			$packet->getDimensionId() ?? 0,
			$packet->getSubChunkCount(),
			$packet->isClientSubChunkRequestEnabled(),
			$packet->getUsedBlobHashes(),
			$packet->getExtraPayload(),
		];
	}
}
