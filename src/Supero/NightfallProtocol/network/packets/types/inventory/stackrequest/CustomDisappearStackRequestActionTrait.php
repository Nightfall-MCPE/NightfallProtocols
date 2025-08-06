<?php

declare(strict_types=1);

namespace Supero\NightfallProtocol\network\packets\types\inventory\stackrequest;

use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;

trait CustomDisappearStackRequestActionTrait{
	final public function __construct(
		private int $count,
		private CustomItemStackRequestSlotInfo $source
	){}

	final public function getCount() : int{ return $this->count; }

	final public function getSource() : CustomItemStackRequestSlotInfo{ return $this->source; }

	public static function read(PacketSerializer $in) : self{
		$count = $in->getByte();
		$source = CustomItemStackRequestSlotInfo::read($in);
		return new self($count, $source);
	}

	public function write(PacketSerializer $out) : void{
		$out->putByte($this->count);
		$this->source->write($out);
	}
}
