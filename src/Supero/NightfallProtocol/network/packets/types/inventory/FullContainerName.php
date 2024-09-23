<?php

declare(strict_types=1);

namespace Supero\NightfallProtocol\network\packets\types\inventory;

use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use Supero\NightfallProtocol\network\CustomProtocolInfo;

final class FullContainerName{
	public function __construct(
		private int $containerId,
		private ?int $dynamicId = null
	){}

	public function getContainerId() : int{ return $this->containerId; }

	public function getDynamicId() : ?int{ return $this->dynamicId; }

	public static function read(PacketSerializer $in) : self{
		$containerId = $in->getByte();
		if($in->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_30){
			$dynamicId = $in->readOptional($in->getLInt(...));
		}elseif($in->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_20){
			$dynamicId = $in->getLInt();
		}
		return new self($containerId, $dynamicId ?? null);
	}

	public function write(PacketSerializer $out) : void{
		$out->putByte($this->containerId);
		if($out->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_30){
			$out->writeOptional($this->dynamicId, $out->putLInt(...));
		}elseif($out->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_20){
			$out->putLInt($this->dynamicId ?? 0);
		}
	}
}
