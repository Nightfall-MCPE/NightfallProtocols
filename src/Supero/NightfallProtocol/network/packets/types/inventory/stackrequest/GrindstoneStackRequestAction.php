<?php

declare(strict_types=1);

namespace Supero\NightfallProtocol\network\packets\types\inventory\stackrequest;

use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\types\GetTypeIdFromConstTrait;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\ItemStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\ItemStackRequestActionType;
use Supero\NightfallProtocol\network\CustomProtocolInfo;

/**
 * Repair and/or remove enchantments from an item in a grindstone.
 */
final class GrindstoneStackRequestAction extends ItemStackRequestAction{
	use GetTypeIdFromConstTrait;

	public const ID = ItemStackRequestActionType::CRAFTING_GRINDSTONE;

	public function __construct(
		private int $recipeId,
		private int $repairCost,
		private int $repetitions
	){}

	public function getRecipeId() : int{ return $this->recipeId; }

	/** WARNING: This may be negative */
	public function getRepairCost() : int{ return $this->repairCost; }

	public function getRepetitions() : int{ return $this->repetitions; }

	public static function read(PacketSerializer $in) : self{
		$recipeId = $in->readRecipeNetId();
		$repairCost = $in->getVarInt(); //WHY!!!!
		if($in->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_20){
			$repetitions = $in->getByte();
		}

		return new self($recipeId, $repairCost, $repetitions ?? 0);
	}

	public function write(PacketSerializer $out) : void{
		$out->writeRecipeNetId($this->recipeId);
		$out->putVarInt($this->repairCost);
		if($out->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_20){
			$out->putByte($this->repetitions);
		}
	}
}
