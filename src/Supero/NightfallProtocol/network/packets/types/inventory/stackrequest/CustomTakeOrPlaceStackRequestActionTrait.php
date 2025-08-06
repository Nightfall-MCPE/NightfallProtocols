<?php

declare(strict_types=1);

namespace Supero\NightfallProtocol\network\packets\types\inventory\stackrequest;

use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;

trait CustomTakeOrPlaceStackRequestActionTrait{
	final public function __construct(
		private int                            $count,
		private CustomItemStackRequestSlotInfo $source,
		private CustomItemStackRequestSlotInfo $destination
	){}

	final public function getCount() : int{ return $this->count; }

	final public function getSource() : CustomItemStackRequestSlotInfo{ return $this->source; }

	final public function getDestination() : CustomItemStackRequestSlotInfo{ return $this->destination; }

	public static function read(PacketSerializer $in) : self{
		$count = $in->getByte();
		$src = CustomItemStackRequestSlotInfo::read($in);
		$dst = CustomItemStackRequestSlotInfo::read($in);
		return new self($count, $src, $dst);
	}

	public function write(PacketSerializer $out) : void{
		$out->putByte($this->count);
		$this->source->write($out);
		$this->destination->write($out);
	}
}
