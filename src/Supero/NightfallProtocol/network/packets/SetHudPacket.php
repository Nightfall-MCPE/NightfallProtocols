<?php

declare(strict_types=1);

namespace Supero\NightfallProtocol\network\packets;

use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\SetHudPacket as PM_Packet;
use pocketmine\network\mcpe\protocol\types\hud\HudElement;
use pocketmine\network\mcpe\protocol\types\hud\HudVisibility;
use Supero\NightfallProtocol\network\CustomProtocolInfo;
use function count;

class SetHudPacket extends PM_Packet{

	private array $hudElements = [];
	private HudVisibility $visibility;

	public static function createPacket(array $hudElements, HudVisibility $visibility) : self{
		$result = new self();
		$result->hudElements = $hudElements;
		$result->visibility = $visibility;
		return $result;
	}

	public function getHudElements() : array{ return $this->hudElements; }

	public function getVisibility() : HudVisibility{ return $this->visibility; }

	protected function decodePayload(PacketSerializer $in) : void{
		$this->hudElements = [];
		for($i = 0, $count = $in->getUnsignedVarInt(); $i < $count; ++$i){
			$this->hudElements[] = HudElement::fromPacket($in->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_70 ? $in->getVarInt() : $in->getByte());
		}
		$this->visibility = HudVisibility::fromPacket($in->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_70 ? $in->getVarInt() : $in->getByte());
	}

	protected function encodePayload(PacketSerializer $out) : void{
		$out->putUnsignedVarInt(count($this->hudElements));
		foreach($this->hudElements as $element){
			if($out->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_70){
				$out->putVarInt($element->value);
			}else{
				$out->putByte($element->value);
			}
		}
		if($out->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_70){
			$out->putVarInt($this->visibility->value);
		}else{
			$out->putByte($this->visibility->value);
		}
	}

	public function getConstructorArguments(PM_Packet $packet) : array
	{
		return [
			$packet->getHudElements(),
			$packet->getVisibility()
		];
	}
}
