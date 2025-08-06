<?php

declare(strict_types=1);

namespace Supero\NightfallProtocol\network\packets;

use pocketmine\network\mcpe\protocol\ItemStackResponsePacket as PM_Packet;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use Supero\NightfallProtocol\network\packets\types\inventory\stackresponse\CustomItemStackResponse;
use function count;

class ItemStackResponsePacket extends PM_Packet{

	/** @var CustomItemStackResponse[] */
	private array $responses;

	/**
	 * @generate-create-func
	 * @param CustomItemStackResponse[] $responses
	 */
	public static function createPacket(array $responses) : self{
		$result = new self();
		$result->responses = $responses;
		return $result;
	}

	/** @return CustomItemStackResponse[] */
	public function getResponses() : array{ return $this->responses; }

	protected function decodePayload(PacketSerializer $in) : void{
		$this->responses = [];
		for($i = 0, $len = $in->getUnsignedVarInt(); $i < $len; ++$i){
			$this->responses[] = CustomItemStackResponse::read($in);
		}
	}

	protected function encodePayload(PacketSerializer $out) : void{
		$out->putUnsignedVarInt(count($this->responses));
		foreach($this->responses as $response){
			$response->write($out);
		}
	}

	public function getConstructorArguments(PM_Packet $packet) : array {
		return [
			$packet->getResponses(),
		];
	}
}
