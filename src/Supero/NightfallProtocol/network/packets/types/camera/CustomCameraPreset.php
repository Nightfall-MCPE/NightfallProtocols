<?php

declare(strict_types=1);

namespace Supero\NightfallProtocol\network\packets\types\camera;

use pocketmine\math\Vector2;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use Supero\NightfallProtocol\network\CustomProtocolInfo;

final class CustomCameraPreset{
	public const AUDIO_LISTENER_TYPE_CAMERA = 0;
	public const AUDIO_LISTENER_TYPE_PLAYER = 1;

	public function __construct(
		private string $name,
		private string $parent,
		private ?float $xPosition,
		private ?float $yPosition,
		private ?float $zPosition,
		private ?float $pitch,
		private ?float $yaw,
		private ?float $rotationSpeed,
		private ?bool $snapToTarget,
		private ?Vector2 $horizontalRotationLimit,
		private ?Vector2 $verticalRotationLimit,
		private ?bool $continueTargeting,
		private ?Vector2 $viewOffset,
		private ?Vector3 $entityOffset,
		private ?float $radius,
		private ?int $audioListenerType,
		private ?bool $playerEffects,
		private ?bool $alignTargetAndCameraForward
	){}

	public function getName() : string{ return $this->name; }

	public function getParent() : string{ return $this->parent; }

	public function getXPosition() : ?float{ return $this->xPosition; }

	public function getYPosition() : ?float{ return $this->yPosition; }

	public function getZPosition() : ?float{ return $this->zPosition; }

	public function getPitch() : ?float{ return $this->pitch; }

	public function getYaw() : ?float{ return $this->yaw; }

	public function getRotationSpeed() : ?float { return $this->rotationSpeed; }

	public function getSnapToTarget() : ?bool { return $this->snapToTarget; }

	public function getHorizontalRotationLimit() : ?Vector2{ return $this->horizontalRotationLimit; }

	public function getVerticalRotationLimit() : ?Vector2{ return $this->verticalRotationLimit; }

	public function getContinueTargeting() : ?bool{ return $this->continueTargeting; }

	public function getViewOffset() : ?Vector2{ return $this->viewOffset; }

	public function getEntityOffset() : ?Vector3{ return $this->entityOffset; }

	public function getRadius() : ?float{ return $this->radius; }

	public function getAudioListenerType() : ?int{ return $this->audioListenerType; }

	public function getPlayerEffects() : ?bool{ return $this->playerEffects; }

	public function getAlignTargetAndCameraForward() : ?bool{ return $this->alignTargetAndCameraForward; }

	public static function read(PacketSerializer $in) : self{
		$name = $in->getString();
		$parent = $in->getString();
		$xPosition = $in->readOptional($in->getLFloat(...));
		$yPosition = $in->readOptional($in->getLFloat(...));
		$zPosition = $in->readOptional($in->getLFloat(...));
		$pitch = $in->readOptional($in->getLFloat(...));
		$yaw = $in->readOptional($in->getLFloat(...));
		if($in->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_20){
			if($in->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_30){
				$rotationSpeed = $in->readOptional($in->getLFloat(...));
				$snapToTarget = $in->readOptional($in->getBool(...));
				if($in->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_40){
					$horizontalRotationLimit = $in->readOptional($in->getVector2(...));
					$verticalRotationLimit = $in->readOptional($in->getVector2(...));
					$continueTargeting = $in->readOptional($in->getBool(...));
				}
			}
			$viewOffset = $in->readOptional($in->getVector2(...));
			if($in->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_30){
				$entityOffset = $in->readOptional($in->getVector3(...));
			}
			$radius = $in->readOptional($in->getLFloat(...));
		}
		$audioListenerType = $in->readOptional($in->getByte(...));
		$playerEffects = $in->readOptional($in->getBool(...));
		if($in->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_40){
			$alignTargetAndCameraForward = $in->readOptional($in->getBool(...));
		}

		return new self(
			$name,
			$parent,
			$xPosition,
			$yPosition,
			$zPosition,
			$pitch,
			$yaw,
			$rotationSpeed ?? null,
			$snapToTarget ?? null,
			$horizontalRotationLimit ?? null,
			$verticalRotationLimit ?? null,
			$continueTargeting ?? null,
			$viewOffset ?? null,
			$entityOffset ?? null,
			$radius ?? null,
			$audioListenerType,
			$playerEffects,
			$alignTargetAndCameraForward ?? null
		);
	}

	public static function fromNBT(CompoundTag $nbt) : self{
		return new self(
			$nbt->getString("identifier"),
			$nbt->getString("inherit_from"),
			$nbt->getTag("pos_x") === null ? null : $nbt->getFloat("pos_x"),
			$nbt->getTag("pos_y") === null ? null : $nbt->getFloat("pos_y"),
			$nbt->getTag("pos_z") === null ? null : $nbt->getFloat("pos_z"),
			$nbt->getTag("rot_x") === null ? null : $nbt->getFloat("rot_x"),
			$nbt->getTag("rot_y") === null ? null : $nbt->getFloat("rot_y"),
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			$nbt->getTag("audio_listener_type") === null ? null : match($nbt->getString("audio_listener_type")){
				"camera" => self::AUDIO_LISTENER_TYPE_CAMERA,
				"player" => self::AUDIO_LISTENER_TYPE_PLAYER,
				default => throw new \InvalidArgumentException("Invalid audio listener type: " . $nbt->getString("audio_listener_type")),
			},
			$nbt->getTag("player_effects") === null ? null : $nbt->getByte("player_effects") !== 0,
			null
		);
	}

	public function write(PacketSerializer $out) : void{
		$out->putString($this->name);
		$out->putString($this->parent);
		$out->writeOptional($this->xPosition, $out->putLFloat(...));
		$out->writeOptional($this->yPosition, $out->putLFloat(...));
		$out->writeOptional($this->zPosition, $out->putLFloat(...));
		$out->writeOptional($this->pitch, $out->putLFloat(...));
		$out->writeOptional($this->yaw, $out->putLFloat(...));
		if($out->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_20){
			if($out->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_30){
				$out->writeOptional($this->rotationSpeed, $out->putLFloat(...));
				$out->writeOptional($this->snapToTarget, $out->putBool(...));
				if($out->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_40){
					$out->writeOptional($this->horizontalRotationLimit, $out->putVector2(...));
					$out->writeOptional($this->verticalRotationLimit, $out->putVector2(...));
					$out->writeOptional($this->continueTargeting, $out->putBool(...));
				}
			}
			$out->writeOptional($this->viewOffset, $out->putVector2(...));
			if($out->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_30){
				$out->writeOptional($this->entityOffset, $out->putVector3(...));
			}
			$out->writeOptional($this->radius, $out->putLFloat(...));
		}
		$out->writeOptional($this->audioListenerType, $out->putByte(...));
		$out->writeOptional($this->playerEffects, $out->putBool(...));
		if($out->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_40){
			$out->writeOptional($this->alignTargetAndCameraForward, $out->putBool(...));
		}
	}

	public function toNBT(int $protocolId) : CompoundTag{
		$nbt = CompoundTag::create()
			->setString("identifier", $this->name)
			->setString("inherit_from", $this->parent);

		if($this->xPosition !== null){
			$nbt->setFloat("pos_x", $this->xPosition);
		}

		if($this->yPosition !== null){
			$nbt->setFloat("pos_y", $this->yPosition);
		}

		if($this->zPosition !== null){
			$nbt->setFloat("pos_z", $this->zPosition);
		}

		if($this->pitch !== null){
			$nbt->setFloat("rot_x", $this->pitch);
		}

		if($this->yaw !== null){
			$nbt->setFloat("rot_y", $this->yaw);
		}

		if($this->audioListenerType !== null){
			$nbt->setString("audio_listener_type", match($this->audioListenerType){
				self::AUDIO_LISTENER_TYPE_CAMERA => "camera",
				self::AUDIO_LISTENER_TYPE_PLAYER => "player",
				default => throw new \InvalidArgumentException("Invalid audio listener type: $this->audioListenerType"),
			});
		}

		if($this->playerEffects !== null){
			$nbt->setByte("player_effects", (int) $this->playerEffects);
		}

		return $nbt;
	}
}
