<?php

declare(strict_types=1);

namespace Supero\MultiVersion\network\static;

use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\Packet;
use pocketmine\network\mcpe\protocol\PacketPool;
use pocketmine\utils\Binary;
use pocketmine\utils\BinaryDataException;
use Supero\MultiVersion\network\packets\BiomeDefinitionListPacket;
use Supero\MultiVersion\network\packets\CameraAimAssistPacket;
use Supero\MultiVersion\network\packets\CameraAimAssistPresetsPacket;
use Supero\MultiVersion\network\packets\CameraInstructionPacket;
use Supero\MultiVersion\network\packets\CameraPresetsPacket;
use Supero\MultiVersion\network\packets\ChangeDimensionPacket;
use Supero\MultiVersion\network\packets\ClientMovementPredictionSyncPacket;
use Supero\MultiVersion\network\packets\CodeBuilderSourcePacket;
use Supero\MultiVersion\network\packets\ContainerClosePacket;
use Supero\MultiVersion\network\packets\CorrectPlayerMovePredictionPacket;
use Supero\MultiVersion\network\packets\DisconnectPacket;
use Supero\MultiVersion\network\packets\EditorNetworkPacket;
use Supero\MultiVersion\network\packets\EmotePacket;
use Supero\MultiVersion\network\packets\InventoryContentPacket;
use Supero\MultiVersion\network\packets\InventorySlotPacket;
use Supero\MultiVersion\network\packets\InventoryTransactionPacket;
use Supero\MultiVersion\network\packets\ItemStackRequestPacket;
use Supero\MultiVersion\network\packets\ItemStackResponsePacket;
use Supero\MultiVersion\network\packets\LecternUpdatePacket;
use Supero\MultiVersion\network\packets\LevelChunkPacket;
use Supero\MultiVersion\network\packets\LevelSoundEventPacket;
use Supero\MultiVersion\network\packets\MobArmorEquipmentPacket;
use Supero\MultiVersion\network\packets\MobEffectPacket;
use Supero\MultiVersion\network\packets\PlayerArmorDamagePacket;
use Supero\MultiVersion\network\packets\PlayerAuthInputPacket;
use Supero\MultiVersion\network\packets\PlayerListPacket;
use Supero\MultiVersion\network\packets\ResourcePacksInfoPacket;
use Supero\MultiVersion\network\packets\ResourcePackStackPacket;
use Supero\MultiVersion\network\packets\SetActorMotionPacket;
use Supero\MultiVersion\network\packets\SetHudPacket;
use Supero\MultiVersion\network\packets\SetMovementAuthorityPacket;
use Supero\MultiVersion\network\packets\SetTitlePacket;
use Supero\MultiVersion\network\packets\StartGamePacket;
use Supero\MultiVersion\network\packets\StopSoundPacket;
use Supero\MultiVersion\network\packets\SubChunkPacket;
use Supero\MultiVersion\network\packets\TextPacket;
use Supero\MultiVersion\network\packets\TickSyncPacket;
use Supero\MultiVersion\network\packets\TransferPacket;
use Supero\MultiVersion\network\packets\UpdateAttributesPacket;
use Supero\MultiVersion\network\packets\UpdatePlayerGameTypePacket;

class CustomPacketPool extends PacketPool
{
	protected static ?PacketPool $instance = null;

	public static function getInstance() : self{
		if(self::$instance === null){
			self::$instance = new self();
		}
		return self::$instance;
	}
	public function __construct()
	{
		parent::__construct();

		$this->registerPacket(new BiomeDefinitionListPacket());
		$this->registerPacket(new CameraAimAssistPacket());
		$this->registerPacket(new CameraAimAssistPresetsPacket());
		$this->registerPacket(new CameraInstructionPacket());
		$this->registerPacket(new CameraPresetsPacket());
		$this->registerPacket(new ChangeDimensionPacket());
		$this->registerPacket(new ClientMovementPredictionSyncPacket());
		$this->registerPacket(new CodeBuilderSourcePacket());
		$this->registerPacket(new ContainerClosePacket());
		$this->registerPacket(new CorrectPlayerMovePredictionPacket());
		$this->registerPacket(new DisconnectPacket());
		$this->registerPacket(new EditorNetworkPacket());
		$this->registerPacket(new EmotePacket());
		$this->registerPacket(new InventoryContentPacket());
		$this->registerPacket(new InventorySlotPacket());
		$this->registerPacket(new InventoryTransactionPacket());
		$this->registerPacket(new ItemStackRequestPacket());
		$this->registerPacket(new ItemStackResponsePacket());
		$this->registerPacket(new LecternUpdatePacket());
		$this->registerPacket(new LevelChunkPacket());
		$this->registerPacket(new LevelSoundEventPacket());
		$this->registerPacket(new MobArmorEquipmentPacket());
		$this->registerPacket(new MobEffectPacket());
		$this->registerPacket(new PlayerArmorDamagePacket());
		$this->registerPacket(new PlayerAuthInputPacket());
		$this->registerPacket(new PlayerListPacket());
		$this->registerPacket(new ResourcePacksInfoPacket());
		$this->registerPacket(new ResourcePackStackPacket());
		$this->registerPacket(new SetActorMotionPacket());
		$this->registerPacket(new SetHudPacket());
		$this->registerPacket(new SetMovementAuthorityPacket());
		$this->registerPacket(new SetTitlePacket());
		$this->registerPacket(new StartGamePacket());
		$this->registerPacket(new StopSoundPacket());
		$this->registerPacket(new SubChunkPacket());
		$this->registerPacket(new TextPacket());
		$this->registerPacket(new TickSyncPacket());
		$this->registerPacket(new TransferPacket());
		$this->registerPacket(new UpdateAttributesPacket());
		$this->registerPacket(new UpdatePlayerGameTypePacket());
	}

	public function registerPacket(Packet $packet) : void{
		$this->pool[$packet->pid()] = clone $packet;
	}

	public function getPacketById(int $pid) : ?Packet{
		return isset($this->pool[$pid]) ? clone $this->pool[$pid] : null;
	}

	/**
	 * @throws BinaryDataException
	 */
	public function getPacket(string $buffer) : ?Packet{
		$offset = 0;
		return $this->getPacketById(Binary::readUnsignedVarInt($buffer, $offset) & DataPacket::PID_MASK);
	}
}
