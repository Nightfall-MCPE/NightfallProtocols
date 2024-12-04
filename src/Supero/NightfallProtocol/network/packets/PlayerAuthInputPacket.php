<?php

declare(strict_types=1);

namespace Supero\NightfallProtocol\network\packets;

use pocketmine\math\Vector2;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket as PM_Packet;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\types\PlayerAction;
use pocketmine\network\mcpe\protocol\types\PlayerAuthInputFlags;
use pocketmine\network\mcpe\protocol\types\PlayerBlockAction;
use pocketmine\network\mcpe\protocol\types\PlayerBlockActionStopBreak;
use pocketmine\network\mcpe\protocol\types\PlayerBlockActionWithBlockInfo;
use pocketmine\network\mcpe\protocol\types\PlayMode;
use Supero\NightfallProtocol\network\CustomProtocolInfo;
use Supero\NightfallProtocol\network\packets\serializer\CustomBitSet;
use Supero\NightfallProtocol\network\packets\types\CustomItemInteractionData;
use Supero\NightfallProtocol\network\packets\types\inventory\CustomItemStackRequest;
use Supero\NightfallProtocol\network\packets\types\inventory\CustomUseItemTransactionData;
use function assert;
use function count;

class PlayerAuthInputPacket extends PM_Packet
{
	private Vector3 $position;
	private float $pitch;
	private float $yaw;
	private float $headYaw;
	private float $moveVecX;
	private float $moveVecZ;
	private CustomBitSet $inputFlags;
	private int $inputMode;
	private int $playMode;
	private int $interactionMode;
	private ?Vector3 $vrGazeDirection = null;
	private Vector2 $interactRotation;
	private int $tick;
	private Vector3 $delta;
	private ?CustomItemInteractionData $itemInteractionData = null;
	private ?CustomItemStackRequest $itemStackRequest = null;
	/** @var PlayerBlockAction[]|null */
	private ?array $blockActions = null;
	private ?PlayerAuthInputVehicleInfo $vehicleInfo = null;
	private float $analogMoveVecX;
	private float $analogMoveVecZ;
	private Vector3 $cameraOrientation;
	private Vector2 $rawMove;

	/**
	 * @param CustomBitSet             $inputFlags      @see PlayerAuthInputFlags
	 * @param int                      $inputMode       @see InputMode
	 * @param int                      $playMode        @see PlayMode
	 * @param int                      $interactionMode @see InteractionMode
	 * @param Vector3|null             $vrGazeDirection only used when PlayMode::VR
	 * @param PlayerBlockAction[]|null $blockActions    Blocks that the client has interacted with
	 */
	public static function createPacket(
		Vector3 $position,
		float $pitch,
		float $yaw,
		float $headYaw,
		float $moveVecX,
		float $moveVecZ,
		CustomBitSet $inputFlags,
		int $inputMode,
		int $playMode,
		int $interactionMode,
		?Vector3 $vrGazeDirection,
		Vector2 $interactRotation,
		int $tick,
		Vector3 $delta,
		?CustomItemInteractionData $itemInteractionData,
		?CustomItemStackRequest $itemStackRequest,
		?array $blockActions,
		?PlayerAuthInputVehicleInfo $vehicleInfo,
		float $analogMoveVecX,
		float $analogMoveVecZ,
		Vector3 $cameraOrientation,
		Vector2 $rawMove
	) : self{
		if($playMode === PlayMode::VR && $vrGazeDirection === null){
			//yuck, can we get a properly written packet just once? ...
			throw new \InvalidArgumentException("Gaze direction must be provided for VR play mode");
		}

		$inputFlags->set(PlayerAuthInputFlags::PERFORM_ITEM_STACK_REQUEST, $itemStackRequest !== null);
		$inputFlags->set(PlayerAuthInputFlags::PERFORM_ITEM_INTERACTION, $itemInteractionData !== null);
		$inputFlags->set(PlayerAuthInputFlags::PERFORM_BLOCK_ACTIONS, $blockActions !== null);
		$inputFlags->set(PlayerAuthInputFlags::IN_CLIENT_PREDICTED_VEHICLE, $vehicleInfo !== null);

		$result = new self();
		$result->position = $position->asVector3();
		$result->pitch = $pitch;
		$result->yaw = $yaw;
		$result->headYaw = $headYaw;
		$result->moveVecX = $moveVecX;
		$result->moveVecZ = $moveVecZ;
		$result->inputFlags = $inputFlags;
		$result->inputMode = $inputMode;
		$result->playMode = $playMode;
		$result->interactionMode = $interactionMode;
		if($vrGazeDirection !== null){
			$result->vrGazeDirection = $vrGazeDirection->asVector3();
		}
		$result->interactRotation = $interactRotation;
		$result->tick = $tick;
		$result->delta = $delta;
		$result->itemInteractionData = $itemInteractionData;
		$result->itemStackRequest = $itemStackRequest;
		$result->blockActions = $blockActions;
		$result->vehicleInfo = $vehicleInfo;
		$result->analogMoveVecX = $analogMoveVecX;
		$result->analogMoveVecZ = $analogMoveVecZ;
		$result->cameraOrientation = $cameraOrientation;
		$result->rawMove = $rawMove;
		return $result;
	}

	public function getPosition() : Vector3{
		return $this->position;
	}

	public function getPitch() : float{
		return $this->pitch;
	}

	public function getYaw() : float{
		return $this->yaw;
	}

	public function getHeadYaw() : float{
		return $this->headYaw;
	}

	public function getMoveVecX() : float{
		return $this->moveVecX;
	}

	public function getMoveVecZ() : float{
		return $this->moveVecZ;
	}

	/**
	 * @see PlayerAuthInputFlags
	 */
	public function getInputFlags() : CustomBitSet{
		return $this->inputFlags;
	}

	/**
	 * @see InputMode
	 */
	public function getInputMode() : int{
		return $this->inputMode;
	}

	/**
	 * @see PlayMode
	 */
	public function getPlayMode() : int{
		return $this->playMode;
	}

	/**
	 * @see InteractionMode
	 */
	public function getInteractionMode() : int{
		return $this->interactionMode;
	}

	public function getVrGazeDirection() : ?Vector3{
		return $this->vrGazeDirection;
	}

	public function getInteractRotation() : Vector2{
		return $this->interactRotation;
	}

	public function getTick() : int{
		return $this->tick;
	}

	public function getDelta() : Vector3{
		return $this->delta;
	}

	public function getCustomItemInteractionData() : ?CustomItemInteractionData{
		return $this->itemInteractionData;
	}

	public function getCustomItemStackRequest() : ?CustomItemStackRequest{
		return $this->itemStackRequest;
	}

	public function getCameraOrientation() : Vector3{
		return $this->cameraOrientation;
	}

	/**
	 * @return PlayerBlockAction[]|null
	 */
	public function getBlockActions() : ?array{
		return $this->blockActions;
	}

	public function getCustomVehicleInfo() : ?PlayerAuthInputVehicleInfo{ return $this->vehicleInfo; }

	public function getAnalogMoveVecX() : float{ return $this->analogMoveVecX; }

	public function getAnalogMoveVecZ() : float{ return $this->analogMoveVecZ; }

	protected function decodePayload(PacketSerializer $in) : void{
		$this->pitch = $in->getLFloat();
		$this->yaw = $in->getLFloat();
		$this->position = $in->getVector3();
		$this->moveVecX = $in->getLFloat();
		$this->moveVecZ = $in->getLFloat();
		$this->headYaw = $in->getLFloat();
		$this->inputFlags = CustomBitSet::read($in, $in->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_50 ? 65 : 64);
		$this->inputMode = $in->getUnsignedVarInt();
		$this->playMode = $in->getUnsignedVarInt();
		$this->interactionMode = $in->getUnsignedVarInt();
		if($in->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_40){
			$this->interactRotation = $in->getVector2();
		}elseif($this->playMode === PlayMode::VR){
			$this->vrGazeDirection = $in->getVector3();
		}
		$this->tick = $in->getUnsignedVarLong();
		$this->delta = $in->getVector3();
		if($this->inputFlags->get(PlayerAuthInputFlags::PERFORM_ITEM_INTERACTION)){
			$this->itemInteractionData = CustomItemInteractionData::read($in);
		}
		if($this->inputFlags->get(PlayerAuthInputFlags::PERFORM_ITEM_STACK_REQUEST)){
			$this->itemStackRequest = CustomItemStackRequest::read($in);
		}
		if($this->inputFlags->get(PlayerAuthInputFlags::PERFORM_BLOCK_ACTIONS)){
			$this->blockActions = [];
			$max = $in->getVarInt();
			for($i = 0; $i < $max; ++$i){
				$actionType = $in->getVarInt();
				$this->blockActions[] = match(true){
					PlayerBlockActionWithBlockInfo::isValidActionType($actionType) => PlayerBlockActionWithBlockInfo::read($in, $actionType),
					$actionType === PlayerAction::STOP_BREAK => new PlayerBlockActionStopBreak(),
					default => throw new PacketDecodeException("Unexpected block action type $actionType")
				};
			}
		}
		if($this->inputFlags->get(PlayerAuthInputFlags::IN_CLIENT_PREDICTED_VEHICLE) && $in->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_20_60){
			$this->vehicleInfo = PlayerAuthInputVehicleInfo::read($in);
		}
		$this->analogMoveVecX = $in->getLFloat();
		$this->analogMoveVecZ = $in->getLFloat();
		if($in->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_40){
			$this->cameraOrientation = $in->getVector3();
			if($in->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_50){
				$this->rawMove = $in->getVector2();
			}
		}
	}

	protected function encodePayload(PacketSerializer $out) : void{
		$inputFlags = $this->inputFlags;

		if($this->vehicleInfo !== null && $out->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_20_60){
			$inputFlags->set(PlayerAuthInputFlags::IN_CLIENT_PREDICTED_VEHICLE, true);
		}

		$out->putLFloat($this->pitch);
		$out->putLFloat($this->yaw);
		$out->putVector3($this->position);
		$out->putLFloat($this->moveVecX);
		$out->putLFloat($this->moveVecZ);
		$out->putLFloat($this->headYaw);
		$this->inputFlags->write($out, $out->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_50 ? 65 : 64);
		$out->putUnsignedVarInt($this->inputMode);
		$out->putUnsignedVarInt($this->playMode);
		$out->putUnsignedVarInt($this->interactionMode);
		if($out->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_40){
			$out->putVector2($this->interactRotation);
		}elseif($this->playMode === PlayMode::VR){
			assert($this->vrGazeDirection !== null);
			$out->putVector3($this->vrGazeDirection);
		}
		$out->putUnsignedVarLong($this->tick);
		$out->putVector3($this->delta);
		$this->itemInteractionData?->write($out);
		$this->itemStackRequest?->write($out);
		if($this->blockActions !== null){
			$out->putVarInt(count($this->blockActions));
			foreach($this->blockActions as $blockAction){
				$out->putVarInt($blockAction->getActionType());
				$blockAction->write($out);
			}
		}
		if($this->vehicleInfo !== null && $out->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_20_60){
			$this->vehicleInfo->write($out);
		}
		$out->putLFloat($this->analogMoveVecX);
		$out->putLFloat($this->analogMoveVecZ);
		if($out->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_40){
			$out->putVector3($this->cameraOrientation);
			if($out->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_50){
				$out->putVector2($this->rawMove);
			}
		}
	}

	public function getConstructorArguments(PM_Packet $packet) : array
	{
		$vehicleInfo = new PlayerAuthInputVehicleInfo(
			$packet->getVehicleInfo()->getVehicleRotationX(),
			$packet->getVehicleInfo()->getVehicleRotationX(),
			$packet->getVehicleInfo()->getPredictedVehicleActorUniqueId()
		);

		$itemStackRequest = new CustomItemStackRequest(
			$packet->getItemStackRequest()->getRequestId(),
			$packet->getItemStackRequest()->getActions(),
			$packet->getItemStackRequest()->getFilterStrings(),
			$packet->getItemStackRequest()->getFilterStringCause()
		);

		$trData = $packet->getItemInteractionData()->getTransactionData();

		$itemInteractionData = new CustomItemInteractionData(
			$packet->getItemInteractionData()->getRequestId(),
			$packet->getItemInteractionData()->getRequestChangedSlots(),
			CustomUseItemTransactionData::new(
				$trData->getActions(),
				$trData->getActionType(),
				$trData->getTriggerType(),
				$trData->getBlockPosition(),
				$trData->getFace(),
				$trData->getHotbarSlot(),
				$trData->getItemInHand(),
				$trData->getPlayerPosition(),
				$trData->getClickPosition(),
				$trData->getBlockRuntimeId(),
				$trData->getClientInteractPrediction()
			)
		);

		return [
			$packet->getPosition(),
			$packet->getPitch(),
			$packet->getYaw(),
			$packet->getHeadYaw(),
			$packet->getMoveVecX(),
			$packet->getMoveVecZ(),
			$packet->getInputFlags(),
			$packet->getInputMode(),
			$packet->getPlayMode(),
			$packet->getInteractionMode(),
			$packet->getVrGazeDirection(),
			$packet->getTick(),
			$packet->getDelta(),
			$itemInteractionData,
			$itemStackRequest,
			$packet->getBlockActions(),
			$vehicleInfo,
			$packet->getAnalogMoveVecX(),
			$packet->getAnalogMoveVecZ(),
			$packet->getCameraOrientation(),
			$packet->getRawMove()
		];
	}
}
