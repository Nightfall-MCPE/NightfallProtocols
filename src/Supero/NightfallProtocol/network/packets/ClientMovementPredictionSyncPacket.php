<?php

declare(strict_types=1);

namespace Supero\NightfallProtocol\network\packets;

use pocketmine\network\mcpe\protocol\ClientMovementPredictionSyncPacket as PM_Packet;
use pocketmine\network\mcpe\protocol\serializer\BitSet;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use Supero\NightfallProtocol\network\CustomProtocolInfo;

class ClientMovementPredictionSyncPacket extends PM_Packet {

	public const OLD_FLAG_LENGTH = 120;
	public const FLAG_LENGTH = 123;

	private BitSet $flags;

	private float $scale;
	private float $width;
	private float $height;

	private float $movementSpeed;
	private float $underwaterMovementSpeed;
	private float $lavaMovementSpeed;
	private float $jumpStrength;
	private float $health;
	private float $hunger;

	private int $actorUniqueId;
	private bool $actorFlyingState;

	private static function internalCreate(
		BitSet $flags,
		float $scale,
		float $width,
		float $height,
		float $movementSpeed,
		float $underwaterMovementSpeed,
		float $lavaMovementSpeed,
		float $jumpStrength,
		float $health,
		float $hunger,
		int $actorUniqueId,
		bool $actorFlyingState,
	) : self{
		$result = new self();
		$result->flags = $flags;
		$result->scale = $scale;
		$result->width = $width;
		$result->height = $height;
		$result->movementSpeed = $movementSpeed;
		$result->underwaterMovementSpeed = $underwaterMovementSpeed;
		$result->lavaMovementSpeed = $lavaMovementSpeed;
		$result->jumpStrength = $jumpStrength;
		$result->health = $health;
		$result->hunger = $hunger;
		$result->actorUniqueId = $actorUniqueId;
		$result->actorFlyingState = $actorFlyingState;
		return $result;
	}

	public static function createPacket(
		BitSet $flags,
		float $scale,
		float $width,
		float $height,
		float $movementSpeed,
		float $underwaterMovementSpeed,
		float $lavaMovementSpeed,
		float $jumpStrength,
		float $health,
		float $hunger,
		int $actorUniqueId,
		bool $actorFlyingState,
	) : self{
		if($flags->getLength() !== self::FLAG_LENGTH){
			throw new \InvalidArgumentException("Input flags must be " . self::FLAG_LENGTH . " bits long");
		}

		return self::internalCreate($flags, $scale, $width, $height, $movementSpeed, $underwaterMovementSpeed, $lavaMovementSpeed, $jumpStrength, $health, $hunger, $actorUniqueId, $actorFlyingState);
	}

	public function getFlags() : BitSet{ return $this->flags; }

	public function getScale() : float{ return $this->scale; }

	public function getWidth() : float{ return $this->width; }

	public function getHeight() : float{ return $this->height; }

	public function getMovementSpeed() : float{ return $this->movementSpeed; }

	public function getUnderwaterMovementSpeed() : float{ return $this->underwaterMovementSpeed; }

	public function getLavaMovementSpeed() : float{ return $this->lavaMovementSpeed; }

	public function getJumpStrength() : float{ return $this->jumpStrength; }

	public function getHealth() : float{ return $this->health; }

	public function getHunger() : float{ return $this->hunger; }

	public function getActorUniqueId() : int{ return $this->actorUniqueId; }

	public function getActorFlyingState() : bool{ return $this->actorFlyingState; }

	protected function decodePayload(PacketSerializer $in) : void{
		$this->flags = BitSet::read($in, $in->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_70 ? self::FLAG_LENGTH : self::OLD_FLAG_LENGTH);
		$this->scale = $in->getLFloat();
		$this->width = $in->getLFloat();
		$this->height = $in->getLFloat();
		$this->movementSpeed = $in->getLFloat();
		$this->underwaterMovementSpeed = $in->getLFloat();
		$this->lavaMovementSpeed = $in->getLFloat();
		$this->jumpStrength = $in->getLFloat();
		$this->health = $in->getLFloat();
		$this->hunger = $in->getLFloat();
		$this->actorUniqueId = $in->getActorUniqueId();
		if($in->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_70){
			$this->actorFlyingState = $in->getBool();
		}
	}

	protected function encodePayload(PacketSerializer $out) : void{
		$this->flags->write($out, $out->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_70 ? self::FLAG_LENGTH : self::OLD_FLAG_LENGTH);
		$out->putLFloat($this->scale);
		$out->putLFloat($this->width);
		$out->putLFloat($this->height);
		$out->putLFloat($this->movementSpeed);
		$out->putLFloat($this->underwaterMovementSpeed);
		$out->putLFloat($this->lavaMovementSpeed);
		$out->putLFloat($this->jumpStrength);
		$out->putLFloat($this->health);
		$out->putLFloat($this->hunger);
		$out->putActorUniqueId($this->actorUniqueId);
		if($out->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_70){
			$out->putBool($this->actorFlyingState);
		}
	}

	public function getConstructorArguments(PM_Packet $packet) : array
	{
		return [
			$packet->getFlags(),
			$packet->getScale(),
			$packet->getWidth(),
			$packet->getHeight(),
			$packet->getMovementSpeed(),
			$packet->getUnderwaterMovementSpeed(),
			$packet->getLavaMovementSpeed(),
			$packet->getJumpStrength(),
			$packet->getHealth(),
			$packet->getHunger(),
			$packet->getActorUniqueId(),
			$packet->getActorFlyingState(),
		];
	}
}
