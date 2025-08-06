<?php

declare(strict_types=1);

namespace Supero\NightfallProtocol\network\packets\types\inventory\stackresponse;

use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use Supero\NightfallProtocol\network\packets\types\inventory\CustomFullContainerName;
use function count;

final class CustomItemStackResponseContainerInfo{
	/**
	 * @param CustomItemStackResponseSlotInfo[] $slots
	 */
	public function __construct(
		private CustomFullContainerName $containerName,
		private array                   $slots
	){}

	public function getContainerName() : CustomFullContainerName{ return $this->containerName; }

	/** @return CustomItemStackResponseSlotInfo[] */
	public function getSlots() : array{ return $this->slots; }

	public static function read(PacketSerializer $in) : self{
		$containerName = CustomFullContainerName::read($in);
		$slots = [];
		for($i = 0, $len = $in->getUnsignedVarInt(); $i < $len; ++$i){
			$slots[] = CustomItemStackResponseSlotInfo::read($in);
		}
		return new self($containerName, $slots);
	}

	public function write(PacketSerializer $out) : void{
		$this->containerName->write($out);
		$out->putUnsignedVarInt(count($this->slots));
		foreach($this->slots as $slot){
			$slot->write($out);
		}
	}
}
