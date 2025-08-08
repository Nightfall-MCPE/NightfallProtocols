<?php

declare(strict_types=1);

namespace Supero\MultiVersion\network\packets;

use pocketmine\network\mcpe\protocol\CameraAimAssistPresetsPacket as PM_Packet;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\types\camera\CameraAimAssistCategory;
use pocketmine\network\mcpe\protocol\types\camera\CameraAimAssistPreset;

use Supero\MultiVersion\network\CustomProtocolInfo;
use Supero\MultiVersion\network\packets\types\camera\CustomCameraAimAssistCategories;
use function count;
class CameraAimAssistPresetsPacket extends PM_Packet{

	private array $categories;

	private array $presets;
	private int $operation;

	public static function createPacket(array $categories, array $presets, int $operation) : self{
		$result = new self();
		$result->categories = $categories;
		$result->presets = $presets;
		$result->operation = $operation;
		return $result;
	}

	public function getCategories() : array{ return $this->categories; }

	public function getPresets() : array{ return $this->presets; }

	public function getOperation() : int{ return $this->operation; }

	protected function decodePayload(PacketSerializer $in) : void{
		$this->categories = [];
		for($i = 0, $count = $in->getUnsignedVarInt(); $i < $count; ++$i){
			if($in->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_80){
				$this->categories[] = CameraAimAssistCategory::read($in);
			}else{
				$categories = CustomCameraAimAssistCategories::read($in);
				foreach($categories->getCategories() as $category){
					$this->categories[] = $category;
				}
			}
		}

		$this->presets = [];
		for($i = 0, $count = $in->getUnsignedVarInt(); $i < $count; ++$i){
			$this->presets[] = CameraAimAssistPreset::read($in);
		}

		if($in->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_60){
			$this->operation = $in->getByte();
		}
	}

	protected function encodePayload(PacketSerializer $out) : void{
		$out->putUnsignedVarInt(count($this->categories));
		foreach($this->categories as $category){
			if($out->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_80){
				$category->write($out);
			}else{
				$categories = new CustomCameraAimAssistCategories($category->getName(), [$category]);
				$categories->write($out);
			}
		}

		$out->putUnsignedVarInt(count($this->presets));
		foreach($this->presets as $preset){
			$preset->write($out);
		}

		if($out->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_60){
			$out->putByte($this->operation);
		}
	}

	public function getConstructorArguments(PM_Packet $packet) : array
	{
		return [
			$packet->getCategories(),
			$packet->getPresets(),
			$packet->getOperation()
		];
	}
}
