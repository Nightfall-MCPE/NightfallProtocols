<?php

declare(strict_types=1);

namespace Supero\NightfallProtocol\network\static;

use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\Packet;
use pocketmine\network\mcpe\protocol\PacketPool;
use pocketmine\utils\Binary;
use pocketmine\utils\BinaryDataException;
use Supero\NightfallProtocol\network\packets\CameraInstructionPacket;
use Supero\NightfallProtocol\network\packets\ChangeDimensionPacket;
use Supero\NightfallProtocol\network\packets\CodeBuilderSourcePacket;
use Supero\NightfallProtocol\network\packets\ContainerClosePacket;
use Supero\NightfallProtocol\network\packets\DisconnectPacket;
use Supero\NightfallProtocol\network\packets\EditorNetworkPacket;
use Supero\NightfallProtocol\network\packets\EmotePacket;
use Supero\NightfallProtocol\network\packets\InventoryContentPacket;
use Supero\NightfallProtocol\network\packets\InventorySlotPacket;
use Supero\NightfallProtocol\network\packets\InventoryTransactionPacket;
use Supero\NightfallProtocol\network\packets\ItemStackRequestPacket;
use Supero\NightfallProtocol\network\packets\ItemStackResponsePacket;
use Supero\NightfallProtocol\network\packets\LecternUpdatePacket;
use Supero\NightfallProtocol\network\packets\LevelChunkPacket;
use Supero\NightfallProtocol\network\packets\MobArmorEquipmentPacket;
use Supero\NightfallProtocol\network\packets\MobEffectPacket;
use Supero\NightfallProtocol\network\packets\PlayerArmorDamagePacket;
use Supero\NightfallProtocol\network\packets\PlayerAuthInputPacket;
use Supero\NightfallProtocol\network\packets\PlayerListPacket;
use Supero\NightfallProtocol\network\packets\ResourcePacksInfoPacket;
use Supero\NightfallProtocol\network\packets\ResourcePackStackPacket;
use Supero\NightfallProtocol\network\packets\SetActorMotionPacket;
use Supero\NightfallProtocol\network\packets\SetTitlePacket;
use Supero\NightfallProtocol\network\packets\StartGamePacket;
use Supero\NightfallProtocol\network\packets\StopSoundPacket;
use Supero\NightfallProtocol\network\packets\TextPacket;
use Supero\NightfallProtocol\network\packets\TickSyncPacket;
use Supero\NightfallProtocol\network\packets\TransferPacket;
use Supero\NightfallProtocol\network\packets\UpdateAttributesPacket;
use Supero\NightfallProtocol\network\packets\UpdatePlayerGameTypePacket;

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

		$this->registerPacket(new CameraInstructionPacket());
		$this->registerPacket(new ChangeDimensionPacket());
		$this->registerPacket(new CodeBuilderSourcePacket());
		$this->registerPacket(new ContainerClosePacket());
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
		$this->registerPacket(new MobArmorEquipmentPacket());
		$this->registerPacket(new MobEffectPacket());
		$this->registerPacket(new PlayerArmorDamagePacket());
		$this->registerPacket(new PlayerAuthInputPacket());
		$this->registerPacket(new PlayerListPacket());
		$this->registerPacket(new ResourcePacksInfoPacket());
		$this->registerPacket(new ResourcePackStackPacket());
		$this->registerPacket(new SetActorMotionPacket());
		$this->registerPacket(new SetTitlePacket());
		$this->registerPacket(new StartGamePacket());
		$this->registerPacket(new StopSoundPacket());
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
