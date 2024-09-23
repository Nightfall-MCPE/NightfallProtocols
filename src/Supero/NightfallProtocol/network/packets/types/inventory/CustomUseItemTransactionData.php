<?php

declare(strict_types=1);

namespace Supero\NightfallProtocol\network\packets\types\inventory;

use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\types\BlockPosition;
use pocketmine\network\mcpe\protocol\types\GetTypeIdFromConstTrait;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use pocketmine\network\mcpe\protocol\types\inventory\NetworkInventoryAction;
use pocketmine\network\mcpe\protocol\types\inventory\PredictedResult;
use pocketmine\network\mcpe\protocol\types\inventory\TriggerType;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemTransactionData;
use Supero\NightfallProtocol\network\CustomProtocolInfo;

class CustomUseItemTransactionData extends UseItemTransactionData
{
	use GetTypeIdFromConstTrait;

	public const ID = InventoryTransactionPacket::TYPE_USE_ITEM;

	public const ACTION_CLICK_BLOCK = 0;
	public const ACTION_CLICK_AIR = 1;
	public const ACTION_BREAK_BLOCK = 2;

	private int $actionType;
	private TriggerType $triggerType;
	private BlockPosition $blockPosition;
	private int $face;
	private int $hotbarSlot;
	private ItemStackWrapper $itemInHand;
	private Vector3 $playerPosition;
	private Vector3 $clickPosition;
	private int $blockRuntimeId;
	private PredictedResult $clientInteractPrediction;

	public function getActionType() : int{
		return $this->actionType;
	}

	public function getTriggerType() : TriggerType{ return $this->triggerType; }

	public function getBlockPosition() : BlockPosition{
		return $this->blockPosition;
	}

	public function getFace() : int{
		return $this->face;
	}

	public function getHotbarSlot() : int{
		return $this->hotbarSlot;
	}

	public function getItemInHand() : ItemStackWrapper{
		return $this->itemInHand;
	}

	public function getPlayerPosition() : Vector3{
		return $this->playerPosition;
	}

	public function getClickPosition() : Vector3{
		return $this->clickPosition;
	}

	public function getBlockRuntimeId() : int{
		return $this->blockRuntimeId;
	}

	public function getClientInteractPrediction() : PredictedResult{ return $this->clientInteractPrediction; }

	protected function decodeData(PacketSerializer $stream) : void{
		$this->actionType = $stream->getUnsignedVarInt();
		if($stream->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_20){
			$this->triggerType = TriggerType::fromPacket($stream->getUnsignedVarInt());
		}
		$this->blockPosition = $stream->getBlockPosition();
		$this->face = $stream->getVarInt();
		$this->hotbarSlot = $stream->getVarInt();
		$this->itemInHand = $stream->getItemStackWrapper();
		$this->playerPosition = $stream->getVector3();
		$this->clickPosition = $stream->getVector3();
		$this->blockRuntimeId = $stream->getUnsignedVarInt();
		if($stream->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_20) {
			$this->clientInteractPrediction = PredictedResult::fromPacket($stream->getUnsignedVarInt());
		}
	}

	protected function encodeData(PacketSerializer $stream) : void{
		$stream->putUnsignedVarInt($this->actionType);
		if($stream->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_20) {
			$stream->putUnsignedVarInt($this->triggerType->value);
		}
		$stream->putBlockPosition($this->blockPosition);
		$stream->putVarInt($this->face);
		$stream->putVarInt($this->hotbarSlot);
		$stream->putItemStackWrapper($this->itemInHand);
		$stream->putVector3($this->playerPosition);
		$stream->putVector3($this->clickPosition);
		$stream->putUnsignedVarInt($this->blockRuntimeId);
		if($stream->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_20) {
			$stream->putUnsignedVarInt($this->clientInteractPrediction->value);
		}
	}

	/**
	 * @param NetworkInventoryAction[] $actions
	 */
	public static function new(array $actions, int $actionType, TriggerType $triggerType, BlockPosition $blockPosition, int $face, int $hotbarSlot, ItemStackWrapper $itemInHand, Vector3 $playerPosition, Vector3 $clickPosition, int $blockRuntimeId, PredictedResult $clientInteractPrediction) : self{
		$result = new self();
		$result->actions = $actions;
		$result->actionType = $actionType;
		$result->triggerType = $triggerType;
		$result->blockPosition = $blockPosition;
		$result->face = $face;
		$result->hotbarSlot = $hotbarSlot;
		$result->itemInHand = $itemInHand;
		$result->playerPosition = $playerPosition;
		$result->clickPosition = $clickPosition;
		$result->blockRuntimeId = $blockRuntimeId;
		$result->clientInteractPrediction = $clientInteractPrediction;
		return $result;
	}
}
