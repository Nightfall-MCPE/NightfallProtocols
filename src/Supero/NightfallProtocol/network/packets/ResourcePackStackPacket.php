<?php

declare(strict_types=1);

namespace Supero\NightfallProtocol\network\packets;

use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\ResourcePackStackPacket as PM_Packet;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\types\Experiments;
use pocketmine\network\mcpe\protocol\types\resourcepacks\ResourcePackStackEntry;
use Supero\NightfallProtocol\network\CustomProtocolInfo;
use function count;

class ResourcePackStackPacket extends PM_Packet
{
	/** @var ResourcePackStackEntry[] */
	public array $resourcePackStack = [];
	/** @var ResourcePackStackEntry[] */
	public array $behaviorPackStack = [];
	public bool $mustAccept = false;
	public string $baseGameVersion = ProtocolInfo::MINECRAFT_VERSION_NETWORK;
	public Experiments $experiments;
	public bool $useVanillaEditorPacks;

	/**
	 * @generate-create-func
	 * @param ResourcePackStackEntry[] $resourcePackStack
	 * @param ResourcePackStackEntry[] $behaviorPackStack
	 */
	public static function createPacket(array $resourcePackStack, array $behaviorPackStack, bool $mustAccept, string $baseGameVersion, Experiments $experiments, bool $useVanillaEditorPacks) : self{
		$result = new self();
		$result->resourcePackStack = $resourcePackStack;
		$result->behaviorPackStack = $behaviorPackStack;
		$result->mustAccept = $mustAccept;
		$result->baseGameVersion = $baseGameVersion;
		$result->experiments = $experiments;
		$result->useVanillaEditorPacks = $useVanillaEditorPacks;
		return $result;
	}

	protected function decodePayload(PacketSerializer $in) : void{
		$this->mustAccept = $in->getBool();
		$behaviorPackCount = $in->getUnsignedVarInt();
		while($behaviorPackCount-- > 0){
			$this->behaviorPackStack[] = ResourcePackStackEntry::read($in);
		}

		$resourcePackCount = $in->getUnsignedVarInt();
		while($resourcePackCount-- > 0){
			$this->resourcePackStack[] = ResourcePackStackEntry::read($in);
		}

		$this->baseGameVersion = $in->getString();
		$this->experiments = Experiments::read($in);
		if($in->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_20_80){
			$this->useVanillaEditorPacks = $in->getBool();
		}
	}

	protected function encodePayload(PacketSerializer $out) : void{
		$out->putBool($this->mustAccept);

		$out->putUnsignedVarInt(count($this->behaviorPackStack));
		foreach($this->behaviorPackStack as $entry){
			$entry->write($out);
		}

		$out->putUnsignedVarInt(count($this->resourcePackStack));
		foreach($this->resourcePackStack as $entry){
			$entry->write($out);
		}

		$out->putString($this->baseGameVersion);
		$this->experiments->write($out);
		if($out->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_20_80) {
			$out->putBool($this->useVanillaEditorPacks);
		}
	}

	public function getConstructorArguments(PM_Packet $packet) : array
	{
		return [
			$packet->resourcePackStack,
			$packet->behaviorPackStack,
			$packet->mustAccept,
			$packet->baseGameVersion,
			$packet->experiments,
			$packet->useVanillaEditorPacks ?? false
		];
	}
}
