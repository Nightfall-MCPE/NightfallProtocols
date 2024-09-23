<?php

declare(strict_types=1);

namespace Supero\NightfallProtocol\network\packets;

use pocketmine\network\mcpe\protocol\ResourcePacksInfoPacket as PM_Packet;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use Supero\NightfallProtocol\network\CustomProtocolInfo;
use Supero\NightfallProtocol\network\packets\types\resourcepacks\CustomBehaviourPackInfoEntry;
use Supero\NightfallProtocol\network\packets\types\resourcepacks\CustomResourcePackInfoEntry;
use function count;

class ResourcePacksInfoPacket extends PM_Packet
{
	/** @var CustomResourcePackInfoEntry[] */
	public array $resourcePackEntries = [];
	/** @var CustomBehaviourPackInfoEntry[] */
	public array $behaviorPackEntries = [];
	public bool $mustAccept = false; //if true, forces client to choose between accepting packs or being disconnected
	public bool $hasAddons = false;
	public bool $hasScripts = false; //if true, causes disconnect for any platform that doesn't support scripts yet
	public bool $forceServerPacks = false;
	/**
	 * @var string[]
	 * @phpstan-var array<string, string>
	 */
	public array $cdnUrls = [];

	/**
	 * @generate-create-func
	 * @param CustomResourcePackInfoEntry[]  $resourcePackEntries
	 * @param CustomBehaviourPackInfoEntry[] $behaviorPackEntries
	 * @param string[]                       $cdnUrls
	 * @phpstan-param array<string, string> $cdnUrls
	 */
	public static function createPacket(
		array $resourcePackEntries,
		array $behaviorPackEntries,
		bool $mustAccept,
		bool $hasAddons,
		bool $hasScripts,
		bool $forceServerPacks,
		array $cdnUrls,
	) : self{
		$result = new self();
		$result->resourcePackEntries = $resourcePackEntries;
		$result->behaviorPackEntries = $behaviorPackEntries;
		$result->mustAccept = $mustAccept;
		$result->hasAddons = $hasAddons;
		$result->hasScripts = $hasScripts;
		$result->forceServerPacks = $forceServerPacks;
		$result->cdnUrls = $cdnUrls;
		return $result;
	}

	protected function decodePayload(PacketSerializer $in) : void{
		$this->mustAccept = $in->getBool();
		if($in->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_20_70){
			$this->hasAddons = $in->getBool();
		}
		$this->hasAddons = $in->getBool();
		$this->hasScripts = $in->getBool();
		if($in->getProtocol() <= CustomProtocolInfo::PROTOCOL_1_21_20){
			$this->forceServerPacks = $in->getBool();
			$behaviorPackCount = $in->getLShort();
			while($behaviorPackCount-- > 0){
				$this->behaviorPackEntries[] = CustomBehaviourPackInfoEntry::read($in);
			}
		}

		$resourcePackCount = $in->getLShort();
		while($resourcePackCount-- > 0){
			$this->resourcePackEntries[] = CustomResourcePackInfoEntry::read($in);
		}

		$this->cdnUrls = [];
		for($i = 0, $count = $in->getUnsignedVarInt(); $i < $count; $i++){
			$packId = $in->getString();
			$cdnUrl = $in->getString();
			$this->cdnUrls[$packId] = $cdnUrl;
		}
	}

	protected function encodePayload(PacketSerializer $out) : void{
		$out->putBool($this->mustAccept);
		if($out->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_20_70) {
			$out->putBool($this->hasAddons);
		}
		$out->putBool($this->hasScripts);
		if($out->getProtocol() <= CustomProtocolInfo::PROTOCOL_1_21_20) {
			$out->putBool($this->forceServerPacks);
			$out->putLShort(count($this->behaviorPackEntries));
			foreach ($this->behaviorPackEntries as $entry) {
				$entry->write($out);
			}
		}
		$out->putLShort(count($this->resourcePackEntries));
		foreach($this->resourcePackEntries as $entry){
			$entry->write($out);
		}
		$out->putUnsignedVarInt(count($this->cdnUrls));
		foreach($this->cdnUrls as $packId => $cdnUrl){
			$out->putString($packId);
			$out->putString($cdnUrl);
		}
	}

	public function getConstructorArguments(PM_Packet $packet) : array
	{
		return [
			$packet->resourcePackEntries,
			[],
			$packet->mustAccept,
			$packet->hasAddons ?? false,
			$packet->hasScripts,
			false,
			$packet->cdnUrls,
		];
	}
}
