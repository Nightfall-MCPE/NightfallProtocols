<?php

declare(strict_types=1);

namespace Supero\NightfallProtocol\network\packets;

use pocketmine\network\mcpe\protocol\CodeBuilderSourcePacket as PM_Packet;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use Supero\NightfallProtocol\network\CustomProtocolInfo;
use Supero\NightfallProtocol\network\static\CustomPacketSerializer;
use function method_exists;

class CodeBuilderSourcePacket extends PM_Packet
{

	private int $operation;
	private int $category;
	private string $value;
	private int $codeStatus;

	/**
	 * @generate-create-func
	 */
	public static function createPacket(int $operation, int $category, string $value, int $codeStatus) : self{
		$result = new self();
		$result->operation = $operation;
		$result->category = $category;
		$result->value = $value;
		$result->codeStatus = $codeStatus;
		return $result;
	}

	public function getValue() : string{ return $this->value; }

	/**
	 * @param CustomPacketSerializer $in
	 */
	protected function decodePayload(PacketSerializer $in) : void{
		$this->operation = $in->getByte();
		$this->category = $in->getByte();
		if($in->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_0){
			$this->codeStatus = $in->getByte();
		}else{
			$this->value = $in->getString();
		}
	}

	/**
	 * @param CustomPacketSerializer $out
	 */
	protected function encodePayload(PacketSerializer $out) : void{
		$out->putByte($this->operation);
		$out->putByte($this->category);
		if($out->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_0){
			$out->putByte($this->codeStatus);
		}else{
			$out->putString($this->value);
		}
	}

	public function getConstructorArguments(PM_Packet $packet) : array
	{
		return [
			$packet->getOperation(),
			$packet->getCategory(),
			(method_exists($packet, "getCodeStatus") ? $packet->getCodeStatus() : $packet->getValue()) ?? 0,
		];
	}
}
