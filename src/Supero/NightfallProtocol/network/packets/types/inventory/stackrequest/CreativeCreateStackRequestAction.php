<?php

declare(strict_types=1);

namespace Supero\NightfallProtocol\network\packets\types\inventory\stackrequest;

use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\types\GetTypeIdFromConstTrait;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\ItemStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\ItemStackRequestActionType;
use Supero\NightfallProtocol\network\CustomProtocolInfo;

/**
 * Creates an item by copying it from the creative inventory. This is treated as a crafting action by vanilla.
 */
final class CreativeCreateStackRequestAction extends ItemStackRequestAction{
	use GetTypeIdFromConstTrait;

	public const ID = ItemStackRequestActionType::CREATIVE_CREATE;

	public function __construct(
		private int $creativeItemId,
		private int $repetitions
	){}

	public function getCreativeItemId() : int{ return $this->creativeItemId; }

	public function getRepetitions() : int{ return $this->repetitions; }

	public static function read(PacketSerializer $in) : self{
		$creativeItemId = $in->readCreativeItemNetId();
		if($in->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_20){
			$repetitions = $in->getByte();
		}
		return new self($creativeItemId, $repetitions ?? 0);
	}

	public function write(PacketSerializer $out) : void{
		$out->writeCreativeItemNetId($this->creativeItemId);
		if($out->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_20){
			$out->putByte($this->repetitions);
		}
	}
}
