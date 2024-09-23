<?php

declare(strict_types=1);

namespace Supero\NightfallProtocol\network\packets\types;

use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\types\inventory\InventoryTransactionChangedSlotsHack;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemTransactionData;
use Supero\NightfallProtocol\network\packets\types\inventory\CustomUseItemTransactionData;
use function count;

class CustomItemInteractionData{
	/**
	 * @param InventoryTransactionChangedSlotsHack[] $requestChangedSlots
	 */
	public function __construct(
		private int $requestId,
		private array $requestChangedSlots,
		private CustomUseItemTransactionData $transactionData
	){}

	public function getRequestId() : int{
		return $this->requestId;
	}

	/**
	 * @return InventoryTransactionChangedSlotsHack[]
	 */
	public function getRequestChangedSlots() : array{
		return $this->requestChangedSlots;
	}

	public function getTransactionData() : UseItemTransactionData{
		return $this->transactionData;
	}

	public static function read(PacketSerializer $in) : self{
		$requestId = $in->getVarInt();
		$requestChangedSlots = [];
		if($requestId !== 0){
			$len = $in->getUnsignedVarInt();
			for($i = 0; $i < $len; ++$i){
				$requestChangedSlots[] = InventoryTransactionChangedSlotsHack::read($in);
			}
		}
		$transactionData = new CustomUseItemTransactionData();
		$transactionData->decode($in);
		return new self($requestId, $requestChangedSlots, $transactionData);
	}

	public function write(PacketSerializer $out) : void{
		$out->putVarInt($this->requestId);
		if($this->requestId !== 0){
			$out->putUnsignedVarInt(count($this->requestChangedSlots));
			foreach($this->requestChangedSlots as $changedSlot){
				$changedSlot->write($out);
			}
		}
		$this->transactionData->encode($out);
	}
}
