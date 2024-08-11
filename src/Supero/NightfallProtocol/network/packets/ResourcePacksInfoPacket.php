<?php

namespace Supero\NightfallProtocol\network\packets;

use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\ResourcePacksInfoPacket as PM_Packet;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\ServerboundPacket;
use pocketmine\network\mcpe\protocol\types\resourcepacks\BehaviorPackInfoEntry;
use pocketmine\network\mcpe\protocol\types\resourcepacks\ResourcePackInfoEntry;
use Supero\NightfallProtocol\network\CustomProtocolInfo;

class ResourcePacksInfoPacket extends PM_Packet
{
    /** @var ResourcePackInfoEntry[] */
    public array $resourcePackEntries = [];
    /** @var BehaviorPackInfoEntry[] */
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
     * @param ResourcePackInfoEntry[] $resourcePackEntries
     * @param BehaviorPackInfoEntry[] $behaviorPackEntries
     * @param string[]                $cdnUrls
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
        $result = new self;
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
        $this->forceServerPacks = $in->getBool();
        $behaviorPackCount = $in->getLShort();
        while($behaviorPackCount-- > 0){
            $this->behaviorPackEntries[] = BehaviorPackInfoEntry::read($in);
        }

        $resourcePackCount = $in->getLShort();
        while($resourcePackCount-- > 0){
            $this->resourcePackEntries[] = ResourcePackInfoEntry::read($in);
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
        $out->putBool($this->forceServerPacks);
        $out->putLShort(count($this->behaviorPackEntries));
        foreach($this->behaviorPackEntries as $entry){
            $entry->write($out);
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

    public function getConstructorArguments(PM_Packet $packet): array
    {
        return [
            $packet->resourcePackEntries,
            $packet->behaviorPackEntries,
            $packet->mustAccept,
            $packet->hasAddons ?? false,
            $packet->hasScripts,
            $packet->forceServerPacks,
            $packet->cdnUrls,
        ];
    }
}