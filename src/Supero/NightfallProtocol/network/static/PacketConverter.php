<?php

namespace Supero\NightfallProtocol\network\static;

use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\CreativeContentPacket;
use pocketmine\network\mcpe\protocol\ItemStackResponsePacket;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\ResourcePacksInfoPacket;
use pocketmine\network\mcpe\protocol\ServerboundPacket;
use pocketmine\network\mcpe\protocol\types\command\CommandData;
use pocketmine\network\mcpe\protocol\types\command\CommandOverload;
use pocketmine\network\mcpe\protocol\types\command\CommandParameter;
use pocketmine\network\mcpe\protocol\types\LevelEvent;
use pocketmine\network\mcpe\protocol\types\LevelSoundEvent;
use pocketmine\network\mcpe\protocol\types\ParticleIds;
use pocketmine\network\mcpe\protocol\types\UpdateSubChunkBlocksPacketEntry;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;
use pocketmine\network\mcpe\protocol\UpdateBlockSyncedPacket;
use pocketmine\network\mcpe\protocol\UpdateSubChunkBlocksPacket;
use Supero\NightfallProtocol\network\caches\CustomCreativeInventoryCache;
use Supero\NightfallProtocol\network\CustomNetworkSession;
use Supero\NightfallProtocol\network\CustomProtocolInfo;
use Supero\NightfallProtocol\network\packets\ItemStackResponsePacket as CustomItemStackResponsePacket;
use Supero\NightfallProtocol\network\packets\types\inventory\FullContainerName;
use Supero\NightfallProtocol\network\packets\types\inventory\stackresponse\ItemStackResponse;
use Supero\NightfallProtocol\network\packets\types\inventory\stackresponse\ItemStackResponseContainerInfo;
use Supero\NightfallProtocol\network\packets\types\resourcepacks\CustomBehaviourPackInfoEntry;
use Supero\NightfallProtocol\network\packets\types\resourcepacks\CustomResourcePackInfoEntry;
use Supero\NightfallProtocol\network\static\convert\CustomTypeConverter;

/**
 * This class is for translations within packets that go unhandled.
 * TODO: Translate all needed packets
 * @see https://github.com/Flonja/multiversion/blob/master/translator/block.go#L172
 * @see https://github.com/Flonja/multiversion/blob/master/translator/item.go#L296
 */
class PacketConverter
{
    public const CLIENTBOUND_TRANSLATED = [
        LevelEventPacket::NETWORK_ID,
        LevelSoundEventPacket::NETWORK_ID,
        UpdateBlockPacket::NETWORK_ID,
        UpdateBlockSyncedPacket::NETWORK_ID,
        UpdateSubChunkBlocksPacket::NETWORK_ID,
        CreativeContentPacket::NETWORK_ID,
        AvailableCommandsPacket::NETWORK_ID,
        ResourcePacksInfoPacket::NETWORK_ID,
        ItemStackResponsePacket::NETWORK_ID
    ];

    public const SERVERBOUND_TRANSLATED = [
        LevelSoundEventPacket::NETWORK_ID
    ];

    public static function handleServerbound(ServerboundPacket $packet, CustomTypeConverter $converter) : ServerboundPacket
    {
        $protocol = $converter->getProtocol();
        if(in_array($protocol, CustomProtocolInfo::COMBINED_LATEST)) return  $packet;

        $searchedPacket = CustomPacketPool::getInstance()->getPacketById($packet::NETWORK_ID);
        if(
            $searchedPacket !== null &&
            !method_exists($packet, "createPacket") &&
            method_exists($searchedPacket, "getConstructorArguments") &&
            method_exists($searchedPacket, "createPacket")
        ){
            $packet = $searchedPacket::createPacket(...$searchedPacket->getConstructorArguments($packet));
        }

        if(!in_array($packet::NETWORK_ID, self::SERVERBOUND_TRANSLATED)) return $packet;

        if ($packet instanceof LevelSoundEventPacket) {
            if (($packet->sound === LevelSoundEvent::BREAK && $packet->extraData !== -1) || $packet->sound === LevelSoundEvent::PLACE || $packet->sound === LevelSoundEvent::HIT || $packet->sound === LevelSoundEvent::LAND || $packet->sound === LevelSoundEvent::ITEM_USE_ON) {
                $packet->extraData = $converter->getCustomBlockTranslator()->internalIdToNetworkId(CustomRuntimeIDtoStateID::getProtocolInstance($protocol)->getStateIdFromRuntimeId($packet->extraData));
            }
            return $packet;
        }

        return $packet;
    }

    public static function handleClientbound(ClientboundPacket $packet, CustomTypeConverter $converter, ?CustomNetworkSession $session) : ClientboundPacket
    {
        $protocol = $converter->getProtocol();
        if(in_array($converter->getProtocol(), CustomProtocolInfo::COMBINED_LATEST)) return  $packet;

        $searchedPacket = CustomPacketPool::getInstance()->getPacketById($packet::NETWORK_ID);
        if(
            $searchedPacket !== null &&
            !method_exists($packet, "createPacket") &&
            method_exists($searchedPacket, "getConstructorArguments") &&
            method_exists($searchedPacket, "createPacket")
        ){
            $packet = $searchedPacket::createPacket(...$searchedPacket->getConstructorArguments($packet));
        }
        if(!in_array($packet::NETWORK_ID, self::CLIENTBOUND_TRANSLATED)) return $packet;

        $blockTranslator = $converter->getCustomBlockTranslator();
        $runtimeToStateId = CustomRuntimeIDtoStateID::getProtocolInstance($protocol);
        switch ($packet::NETWORK_ID) {
            case UpdateSubChunkBlocksPacket::NETWORK_ID:
                // normal human: supero what type of code is this
                // supero: yes

                /** @var UpdateSubChunkBlocksPacket $packet */
                $layer0 = $packet->getLayer0Updates();
                $layer1 = $packet->getLayer1Updates();

                $layer0Entries = [];
                $layer1Entries = [];

                foreach ($layer0 as $entry) {
                    /** @var UpdateSubChunkBlocksPacketEntry $entry */
                    $layer0Entries[] = new UpdateSubChunkBlocksPacketEntry(
                        $entry->getBlockPosition(),
                        $blockTranslator->internalIdToNetworkId($runtimeToStateId->getStateIdFromRuntimeId($entry->getBlockRuntimeId())),
                        $entry->getFlags(),
                        $entry->getSyncedUpdateActorUniqueId(),
                        $entry->getSyncedUpdateType()
                    );
                }

                foreach ($layer1 as $entry) {
                    /** @var UpdateSubChunkBlocksPacketEntry $entry */
                    $layer1Entries[] = new UpdateSubChunkBlocksPacketEntry(
                        $entry->getBlockPosition(),
                        $blockTranslator->internalIdToNetworkId($runtimeToStateId->getStateIdFromRuntimeId($entry->getBlockRuntimeId())),
                        $entry->getFlags(),
                        $entry->getSyncedUpdateActorUniqueId(),
                        $entry->getSyncedUpdateType()
                    );
                }

                return UpdateSubChunkBlocksPacket::create(
                    $packet->getBaseBlockPosition(),
                    $layer0Entries,
                    $layer1Entries
                );

            case UpdateBlockSyncedPacket::NETWORK_ID:
            case UpdateBlockPacket::NETWORK_ID:
                /** @var UpdateBlockPacket $packet */
                $packet->blockRuntimeId = $blockTranslator->internalIdToNetworkId($runtimeToStateId->getStateIdFromRuntimeId($packet->blockRuntimeId));
                return $packet;
            case ItemStackResponsePacket::NETWORK_ID:
                $responses = [];
                /** @var ItemStackResponsePacket $packet */
                foreach($packet->getResponses() as $response){
                    if(!$response instanceof ItemStackResponse){
                        var_dump($response);
                        $containerInfos = [];
                        foreach($response->getContainerInfos() as $containerInfo){
                            if(!$containerInfo instanceof ItemStackResponseContainerInfo){
                                $containerName = $containerInfo->getContainerName();
                                if(!$containerName instanceof FullContainerName){
                                    $containerName = new FullContainerName($containerName->getContainerId(), $containerName->getDynamicId());
                                }
                                $containerInfos[] = new ItemStackResponseContainerInfo($containerName, $containerInfo->getSlots());
                            }
                        }
                        $responses[] = new ItemStackResponse($response->getResult(), $response->getRequestId(), $containerInfos);
                    }
                }
                return CustomItemStackResponsePacket::createPacket($responses);
            case LevelEventPacket::NETWORK_ID:
                /** @var LevelEventPacket $packet */
                if ($packet->eventId === LevelEvent::PARTICLE_DESTROY || $packet->eventId === (LevelEvent::ADD_PARTICLE_MASK | ParticleIds::TERRAIN)) {
                    $packet->eventData = $blockTranslator->internalIdToNetworkId($runtimeToStateId->getStateIdFromRuntimeId($packet->eventData));
                    return $packet;

                } elseif ($packet->eventId === LevelEvent::PARTICLE_PUNCH_BLOCK) {
                    $packet->eventData = $blockTranslator->internalIdToNetworkId($runtimeToStateId->getStateIdFromRuntimeId($packet->eventData & 0xFFFFFF));
                    return $packet;
                }
                return $packet;
            case LevelSoundEventPacket::NETWORK_ID:
                /** @var LevelSoundEventPacket $packet */
                if($packet->sound === LevelSoundEvent::ITEM_USE_ON){
                    $packet->extraData = $blockTranslator->internalIdToNetworkId($runtimeToStateId->getStateIdFromRuntimeId($packet->extraData));
                    return $packet;
                }
                return $packet;
            case AvailableCommandsPacket::NETWORK_ID:
                /** @var AvailableCommandsPacket $packet */
                $commandData = $packet->commandData;
                $newCommandData = [];
                foreach ($commandData as $label => $commandDatum) {
                    $overloads = [];
                    foreach ($commandDatum->overloads as $overloadLabel => $overload) {
                        $overloads[$overloadLabel] = new CommandOverload($overload->isChaining(), parameters: [CommandParameter::standard("args", self::convertArg($protocol, AvailableCommandsPacket::ARG_TYPE_RAWTEXT), 0, true)]);
                    }
                    $newCommandData[$label] = new CommandData(
                        $commandDatum->name,
                        $commandDatum->description,
                        $commandDatum->flags,
                        $commandDatum->permission,
                        $commandDatum->aliases,
                        $overloads,
                        $commandDatum->chainedSubCommandData
                    );
                }
                return AvailableCommandsPacket::create($newCommandData, $packet->hardcodedEnums, $packet->softEnums, $packet->enumConstraints);
            case CreativeContentPacket::NETWORK_ID:
                if($session == null) return $packet;
                return CustomCreativeInventoryCache::getProtocolInstance($protocol)->getCache($session->getPlayer()->getCreativeInventory());
            case ResourcePacksInfoPacket::NETWORK_ID:
                $behaviourEntries = [];
                /** @var ResourcePacksInfoPacket $packet */
                foreach($packet->behaviorPackEntries as $label => $behaviourEntry){
                    $behaviourEntries[$label] = new CustomBehaviourPackInfoEntry(
                        $behaviourEntry->getPackId(),
                        $behaviourEntry->getVersion(),
                        $behaviourEntry->getSizeBytes(),
                        $behaviourEntry->getEncryptionKey(),
                        $behaviourEntry->getSubPackName(),
                        $behaviourEntry->getContentId(),
                        $behaviourEntry->hasScripts(),
                        $behaviourEntry->isAddonPack()
                    );
                }

                $resourceEntries = [];
                foreach($packet->resourcePackEntries as $label => $resourcePackEntry){
                    $resourceEntries[$label] = new CustomResourcePackInfoEntry(
                        $resourcePackEntry->getPackId(),
                        $resourcePackEntry->getVersion(),
                        $resourcePackEntry->getSizeBytes(),
                        $resourcePackEntry->getEncryptionKey(),
                        $resourcePackEntry->getSubPackName(),
                        $resourcePackEntry->getContentId(),
                        $resourcePackEntry->hasScripts(),
                        $resourcePackEntry->isAddonPack(),
                        $resourcePackEntry->isRtxCapable()
                    );
                }

                return \Supero\NightfallProtocol\network\packets\ResourcePacksInfoPacket::createPacket(
                    $resourceEntries,
                    $behaviourEntries,
                    $packet->mustAccept,
                    $packet->hasAddons,
                    $packet->hasScripts,
                    $packet->forceServerPacks,
                    $packet->cdnUrls
                );
            default:
                return $packet;
        }
    }

    public static function convertArg(int $protocolId, int $type) : int{
        if($protocolId <= CustomProtocolInfo::PROTOCOL_1_20_60){
            return match($type){
                AvailableCommandsPacket::ARG_TYPE_EQUIPMENT_SLOT => 43,
                AvailableCommandsPacket::ARG_TYPE_STRING => 44,
                AvailableCommandsPacket::ARG_TYPE_INT_POSITION => 52,
                AvailableCommandsPacket::ARG_TYPE_POSITION => 53,
                AvailableCommandsPacket::ARG_TYPE_MESSAGE => 55,
                AvailableCommandsPacket::ARG_TYPE_RAWTEXT => 58,
                AvailableCommandsPacket::ARG_TYPE_JSON => 62,
                AvailableCommandsPacket::ARG_TYPE_BLOCK_STATES => 71,
                AvailableCommandsPacket::ARG_TYPE_COMMAND => 74,
                default => $type,
            };
        }

        return $type;
    }
}