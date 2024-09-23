<?php

declare(strict_types=1);

namespace Supero\NightfallProtocol\network\packets;

use pocketmine\network\mcpe\protocol\LecternUpdatePacket as PM_Packet;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\types\BlockPosition;
use Supero\NightfallProtocol\network\CustomProtocolInfo;

class LecternUpdatePacket extends PM_Packet
{
	public int $page;
	public int $totalPages;
	public BlockPosition $blockPosition;
	public bool $dropBook;

	/**
	 * @generate-create-func
	 */
	public static function createPacket(int $page, int $totalPages, BlockPosition $blockPosition, bool $dropBook) : self{
		$result = new self();
		$result->page = $page;
		$result->totalPages = $totalPages;
		$result->blockPosition = $blockPosition;
		$result->dropBook = $dropBook;
		return $result;
	}

	protected function decodePayload(PacketSerializer $in) : void{
		$this->page = $in->getByte();
		$this->totalPages = $in->getByte();
		$this->blockPosition = $in->getBlockPosition();
		if($in->getProtocol() <= CustomProtocolInfo::PROTOCOL_1_20_60){
			$this->dropBook = $in->getBool();
		}
	}

	protected function encodePayload(PacketSerializer $out) : void{
		$out->putByte($this->page);
		$out->putByte($this->totalPages);
		$out->putBlockPosition($this->blockPosition);
		if($out->getProtocol() <= CustomProtocolInfo::PROTOCOL_1_20_60){
			$out->putBool($this->dropBook);
		}
	}

	public function getConstructorArguments(PM_Packet $packet) : array
	{
		return [
			$packet->page,
			$packet->totalPages,
			$packet->blockPosition,
			$this->dropBook ?? false
		];
	}
}
