<?php

declare(strict_types=1);

namespace Supero\MultiVersion\network\packets;

use pocketmine\network\mcpe\protocol\CameraPresetsPacket as PM_Packet;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use Supero\MultiVersion\network\packets\types\camera\CustomCameraPreset;
use function count;

class CameraPresetsPacket extends PM_Packet{
	private array $presets;

	public static function createPacket(array $presets) : self{
		$result = new self();
		$result->presets = $presets;
		return $result;
	}

	public function getPresets() : array{ return $this->presets; }

	protected function decodePayload(PacketSerializer $in) : void{
		$this->presets = [];
		for($i = 0, $count = $in->getUnsignedVarInt(); $i < $count; $i++){
			$this->presets[] = CustomCameraPreset::read($in);
		}
	}

	protected function encodePayload(PacketSerializer $out) : void{
		$out->putUnsignedVarInt(count($this->presets));
		foreach($this->presets as $preset){
			$preset->write($out);

		}
	}

	public function getConstructorArguments(PM_Packet $packet) : array
	{
		return [
			$packet->getPresets()
		];
	}
}
