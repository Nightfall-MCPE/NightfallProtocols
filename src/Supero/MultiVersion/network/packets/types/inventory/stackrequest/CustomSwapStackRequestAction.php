<?php

declare(strict_types=1);

namespace Supero\MultiVersion\network\packets\types\inventory\stackrequest;

use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\types\GetTypeIdFromConstTrait;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\ItemStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\ItemStackRequestActionType;

/**
 * Swaps two stacks. These don't have to be in the same inventory. This action does not modify the stacks themselves.
 */
final class CustomSwapStackRequestAction extends ItemStackRequestAction{
	use GetTypeIdFromConstTrait;

	public const ID = ItemStackRequestActionType::SWAP;

	public function __construct(
		private CustomItemStackRequestSlotInfo $slot1,
		private CustomItemStackRequestSlotInfo $slot2
	){}

	public function getSlot1() : CustomItemStackRequestSlotInfo{ return $this->slot1; }

	public function getSlot2() : CustomItemStackRequestSlotInfo{ return $this->slot2; }

	public static function read(PacketSerializer $in) : self{
		$slot1 = CustomItemStackRequestSlotInfo::read($in);
		$slot2 = CustomItemStackRequestSlotInfo::read($in);
		return new self($slot1, $slot2);
	}

	public function write(PacketSerializer $out) : void{
		$this->slot1->write($out);
		$this->slot2->write($out);
	}
}
