<?php

declare(strict_types=1);

namespace Supero\NightfallProtocol\network\packets;

use pocketmine\math\Vector2;
use pocketmine\network\mcpe\protocol\CameraAimAssistPacket as PM_Packet;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\types\camera\CameraAimAssistActionType;
use pocketmine\network\mcpe\protocol\types\camera\CameraAimAssistTargetMode;
use Supero\NightfallProtocol\network\CustomProtocolInfo;

class CameraAimAssistPacket extends PM_Packet{

	private string $presetId;
	private Vector2 $viewAngle;
	private float $distance;
	private CameraAimAssistTargetMode $targetMode;
	private CameraAimAssistActionType $actionType;
	private bool $showDebugRender;

	public static function createPacket(string $presetId, Vector2 $viewAngle, float $distance, CameraAimAssistTargetMode $targetMode, CameraAimAssistActionType $actionType, bool $showDebugRender) : self{
		$result = new self();
		$result->presetId = $presetId;
		$result->viewAngle = $viewAngle;
		$result->distance = $distance;
		$result->targetMode = $targetMode;
		$result->actionType = $actionType;
		$result->showDebugRender = $showDebugRender;
		return $result;
	}

	public function getPresetId() : string{ return $this->presetId; }

	public function getViewAngle() : Vector2{ return $this->viewAngle; }

	public function getDistance() : float{ return $this->distance; }

	public function getTargetMode() : CameraAimAssistTargetMode{ return $this->targetMode; }

	public function getActionType() : CameraAimAssistActionType{ return $this->actionType; }

	public function getShowDebugRender() : bool{ return $this->showDebugRender; }

	protected function decodePayload(PacketSerializer $in) : void{
		if($in->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_50){
			$this->presetId = $in->getString();
		}
		$this->viewAngle = $in->getVector2();
		$this->distance = $in->getLFloat();
		$this->targetMode = CameraAimAssistTargetMode::fromPacket($in->getByte());
		$this->actionType = CameraAimAssistActionType::fromPacket($in->getByte());
		if($in->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_100){
			$this->showDebugRender = $in->getBool();
		}
	}

	protected function encodePayload(PacketSerializer $out) : void{
		if($out->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_50){
			$out->putString($this->presetId);
		}
		$out->putVector2($this->viewAngle);
		$out->putLFloat($this->distance);
		$out->putByte($this->targetMode->value);
		$out->putByte($this->actionType->value);
		if($out->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_100){
			$out->putBool($this->showDebugRender);
		}
	}

	public function getConstructorArguments(PM_Packet $packet) : array
	{
		return [
			$packet->getPresetId(),
			$packet->getViewAngle(),
			$packet->getDistance(),
			$packet->getTargetMode(),
			$packet->getActionType(),
			$packet->getShowDebugRender()
		];
	}
}
