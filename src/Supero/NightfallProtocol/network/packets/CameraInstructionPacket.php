<?php

declare(strict_types=1);

namespace Supero\NightfallProtocol\network\packets;

use pocketmine\network\mcpe\protocol\CameraInstructionPacket as PM_Packet;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\types\camera\CameraFadeInstruction;
use pocketmine\network\mcpe\protocol\types\camera\CameraFovInstruction;
use pocketmine\network\mcpe\protocol\types\camera\CameraSetInstruction;
use pocketmine\network\mcpe\protocol\types\camera\CameraTargetInstruction;
use Supero\NightfallProtocol\network\CustomProtocolInfo;

class CameraInstructionPacket extends PM_Packet
{

	private ?CameraSetInstruction $set;
	private ?bool $clear;
	private ?CameraFadeInstruction $fade;
	private ?CameraTargetInstruction $target;
	private ?bool $removeTarget;
	private ?CameraFovInstruction $fieldOfView;

	/**
	 * @generate-create-func
	 */
	public static function createPacket(?CameraSetInstruction $set, ?bool $clear, ?CameraFadeInstruction $fade, ?CameraTargetInstruction $target, ?bool $removeTarget, ?CameraFovInstruction $fieldOfView) : self{
		$result = new self();
		$result->set = $set;
		$result->clear = $clear;
		$result->fade = $fade;
		$result->target = $target;
		$result->removeTarget = $removeTarget;
		$result->fieldOfView = $fieldOfView;
		return $result;
	}

	protected function decodePayload(PacketSerializer $in) : void{
		$this->set = $in->readOptional(fn() => CameraSetInstruction::read($in));
		$this->clear = $in->readOptional($in->getBool(...));
		$this->fade = $in->readOptional(fn() => CameraFadeInstruction::read($in));
		if($in->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_20){
			$this->target = $in->readOptional(fn() => CameraTargetInstruction::read($in));
			$this->removeTarget = $in->readOptional($in->getBool(...));
			if($in->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_100){
				$this->fieldOfView = $in->readOptional(fn() => CameraFovInstruction::read($in));
			}
		}
	}

	protected function encodePayload(PacketSerializer $out) : void{
		$out->writeOptional($this->set, fn(CameraSetInstruction $v) => $v->write($out));
		$out->writeOptional($this->clear, $out->putBool(...));
		$out->writeOptional($this->fade, fn(CameraFadeInstruction $v) => $v->write($out));
		if($out->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_20){
			$out->writeOptional($this->target, fn(CameraTargetInstruction $v) => $v->write($out));
			$out->writeOptional($this->removeTarget, $out->putBool(...));
			if($out->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_100){
				$out->writeOptional($this->fieldOfView, fn(CameraFovInstruction $v) => $v->write($out));
			}
		}
	}

	public function getConstructorArguments(PM_Packet $packet) : array
	{
		return [
			$packet->getSet(),
			$packet->getClear(),
			$packet->getFade(),
			$packet->getTarget() ?? null,
			$packet->getRemoveTarget() ?? null,
			$packet->getFieldOfView() ?? null
		];
	}
}
