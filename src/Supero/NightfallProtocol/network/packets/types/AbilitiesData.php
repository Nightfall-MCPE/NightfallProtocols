<?php

declare(strict_types=1);

namespace Supero\NightfallProtocol\network\packets\types;

use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;

use function count;

final class AbilitiesData{
	/**
	 * @param AbilitiesLayer[] $abilityLayers
	 * @phpstan-param array<int, AbilitiesLayer> $abilityLayers
	 */
	public function __construct(
		private int $commandPermission,
		private int $playerPermission,
		private int $targetActorUniqueId, //This is a little-endian long, NOT a var-long. (WTF Mojang)
		private array $abilityLayers
	){}

	public function getCommandPermission() : int{ return $this->commandPermission; }

	public function getPlayerPermission() : int{ return $this->playerPermission; }

	public function getTargetActorUniqueId() : int{ return $this->targetActorUniqueId; }

	/**
	 * @return AbilitiesLayer[]
	 * @phpstan-return array<int, AbilitiesLayer>
	 */
	public function getAbilityLayers() : array{ return $this->abilityLayers; }

	public static function decode(PacketSerializer $in) : self {
		$targetActorUniqueId = $in->getLLong(); //WHY IS THIS NON-STANDARD?
		$playerPermission = $in->getByte();
		$commandPermission = $in->getByte();

		$abilityLayers = [];
		for($i = 0, $len = $in->getByte(); $i < $len; $i++){
			$abilityLayers[] = AbilitiesLayer::decode($in);
		}

		return new self($commandPermission, $playerPermission, $targetActorUniqueId, $abilityLayers);
	}

	public function encode(PacketSerializer $out) : void {
		$out->putLLong($this->targetActorUniqueId);
		$out->putByte($this->playerPermission);
		$out->putByte($this->commandPermission);

		$out->putByte(count($this->abilityLayers));
		foreach($this->abilityLayers as $abilityLayer){
			$abilityLayer->encode($out);
		}
	}
}
