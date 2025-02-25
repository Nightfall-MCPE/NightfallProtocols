<?php

declare(strict_types=1);

namespace Supero\NightfallProtocol\network\packets;

use pocketmine\network\mcpe\protocol\ItemRegistryPacket as PM_Packet;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\types\CacheableNbt;
use pocketmine\network\mcpe\protocol\types\ItemTypeEntry;
use Supero\NightfallProtocol\network\CustomProtocolInfo;

use function count;

class ItemRegistryPacket extends PM_Packet{

	/**
	 * @var ItemTypeEntry[]
	 * @phpstan-var list<ItemTypeEntry>
	 */
	private array $entries;

	/**
	 * @generate-create-func
	 * @param ItemTypeEntry[] $entries
	 * @phpstan-param list<ItemTypeEntry> $entries
	 */
	public static function createPacket(array $entries) : self{
		$result = new self;
		$result->entries = $entries;
		return $result;
	}

	/**
	 * @return ItemTypeEntry[]
	 * @phpstan-return list<ItemTypeEntry>
	 */
	public function getEntries() : array{ return $this->entries; }

	protected function decodePayload(PacketSerializer $in) : void{
		$this->entries = [];
		for($i = 0, $len = $in->getUnsignedVarInt(); $i < $len; ++$i){
			$stringId = $in->getString();
			if($in->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_60){
				$numericId = $in->getSignedLShort();
				$isComponentBased = $in->getBool();
				$version = $in->getVarInt();
			}
			$nbt = $in->getNbtCompoundRoot();
			$this->entries[] = new ItemTypeEntry($stringId, $numericId ?? -1, $isComponentBased ?? false, $version ?? -1, new CacheableNbt($nbt));
		}
	}

	protected function encodePayload(PacketSerializer $out) : void{
		$out->putUnsignedVarInt(count($this->entries));
		foreach($this->entries as $entry){
			$out->putString($entry->getStringId());
			if($out->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_60){
				$out->putLShort($entry->getNumericId());
				$out->putBool($entry->isComponentBased());
				$out->putVarInt($entry->getVersion());
			}
			$out->put($entry->getComponentNbt()->getEncodedNbt());
		}
	}

	public function getConstructorArguments(PM_Packet $packet) : array {
		return [
			$packet->entries
		];
	}
}