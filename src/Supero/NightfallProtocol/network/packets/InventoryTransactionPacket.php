<?php

declare(strict_types=1);

namespace Supero\NightfallProtocol\network\packets;

use pocketmine\network\mcpe\protocol\InventoryTransactionPacket as PM_Packet;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\types\inventory\InventoryTransactionChangedSlotsHack;
use pocketmine\network\mcpe\protocol\types\inventory\MismatchTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\NormalTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\ReleaseItemTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\TransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemOnEntityTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemTransactionData;
use Supero\NightfallProtocol\network\packets\types\inventory\CustomUseItemTransactionData;
use function count;

class InventoryTransactionPacket extends PM_Packet {
	public int $requestId;
	public array $requestChangedSlots;
	public TransactionData $trData;

	/**
	 * @generate-create-func
	 */
	public static function createPacket(int $requestId, array $requestChangedSlots, TransactionData $trData) : self {
		$result = new self();
		$result->requestId = $requestId;
		$result->requestChangedSlots = $requestChangedSlots;
		$result->trData = $trData;
		return $result;
	}
	protected function decodePayload(PacketSerializer $in) : void{
		$this->requestId = $in->readLegacyItemStackRequestId();
		$this->requestChangedSlots = [];
		if($this->requestId !== 0){
			for($i = 0, $len = $in->getUnsignedVarInt(); $i < $len; ++$i){
				$this->requestChangedSlots[] = InventoryTransactionChangedSlotsHack::read($in);
			}
		}

		$transactionType = $in->getUnsignedVarInt();

		$this->trData = match($transactionType){
			NormalTransactionData::ID => new NormalTransactionData(),
			MismatchTransactionData::ID => new MismatchTransactionData(),
			UseItemTransactionData::ID => new CustomUseItemTransactionData(),
			UseItemOnEntityTransactionData::ID => new UseItemOnEntityTransactionData(),
			ReleaseItemTransactionData::ID => new ReleaseItemTransactionData(),
			default => throw new PacketDecodeException("Unknown transaction type $transactionType"),
		};

		$this->trData->decode($in);
	}

	protected function encodePayload(PacketSerializer $out) : void{
		$out->writeLegacyItemStackRequestId($this->requestId);
		if($this->requestId !== 0){
			$out->putUnsignedVarInt(count($this->requestChangedSlots));
			foreach($this->requestChangedSlots as $changedSlots){
				$changedSlots->write($out);
			}
		}

		$out->putUnsignedVarInt($this->trData->getTypeId());

		$this->trData->encode($out);
	}
	public function getConstructorArguments(PM_Packet $packet) : array {
		return [
			$packet->requestId,
			$packet->requestChangedSlots,
			$packet->trData,
		];
	}
}
