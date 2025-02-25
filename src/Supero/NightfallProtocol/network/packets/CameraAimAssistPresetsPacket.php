<?php

declare(strict_types=1);

namespace Supero\NightfallProtocol\network\packets;

use pocketmine\network\mcpe\protocol\CameraAimAssistPresetsPacket as PM_Packet;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\types\camera\CameraAimAssistCategories;
use pocketmine\network\mcpe\protocol\types\camera\CameraAimAssistPreset;
use Supero\NightfallProtocol\network\CustomProtocolInfo;

use function count;

class CameraAimAssistPresetsPacket extends PM_Packet {

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

	protected function decodePayload(PacketSerializer $in) : void{
		$categoriesCount = $in->getUnsignedVarInt();
		while ($categoriesCount-- > 0) {
			$this->categories[] = CameraAimAssistCategories::read($in);
		}

		$presetsCount = $in->getUnsignedVarInt();
		while ($presetsCount-- > 0) {
			$this->presets[] = CameraAimAssistPreset::read($in);
		}

		if($in->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_60){
			$this->operation = $in->getByte();
		}
	}
	protected function encodePayload(PacketSerializer $out) : void{
		$out->putUnsignedVarInt(count($this->categories));
		foreach($this->categories as $category) {
			$category->write($out);
		}

		$out->putUnsignedVarInt(count($this->presets));
		foreach($this->presets as $preset) {
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
