<?php

namespace Supero\NightfallProtocol\network;

use Closure;
use InvalidArgumentException;
use pocketmine\event\player\PlayerDuplicateLoginEvent;
use pocketmine\event\player\PlayerResourcePackOfferEvent;
use pocketmine\event\server\DataPacketDecodeEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\lang\KnownTranslationFactory;
use pocketmine\lang\Translatable;
use pocketmine\nbt\tag\StringTag;
use pocketmine\network\mcpe\compression\CompressBatchPromise;
use pocketmine\network\mcpe\encryption\EncryptionContext;
use pocketmine\network\mcpe\encryption\PrepareEncryptionTask;
use pocketmine\network\mcpe\handler\HandshakePacketHandler;
use pocketmine\network\mcpe\handler\LoginPacketHandler;
use pocketmine\network\mcpe\handler\PacketHandler;
use pocketmine\network\mcpe\handler\PreSpawnPacketHandler;
use pocketmine\network\mcpe\handler\ResourcePacksPacketHandler;
use pocketmine\network\mcpe\handler\SessionStartPacketHandler;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\Packet;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\PlayStatusPacket;
use pocketmine\network\mcpe\protocol\ServerboundPacket;
use pocketmine\network\mcpe\protocol\ServerToClientHandshakePacket;
use pocketmine\network\mcpe\StandardEntityEventBroadcaster;
use pocketmine\network\PacketHandlingException;
use pocketmine\player\Player;
use pocketmine\player\PlayerInfo;
use pocketmine\player\UsedChunkStatus;
use pocketmine\player\XboxLivePlayerInfo;
use pocketmine\promise\Promise;
use pocketmine\promise\PromiseResolver;
use pocketmine\Server;
use pocketmine\timings\Timings;
use pocketmine\utils\TextFormat;
use pocketmine\YmlServerProperties;
use ReflectionException;
use Supero\NightfallProtocol\network\chunk\CustomChunkCache;
use Supero\NightfallProtocol\network\handlers\CustomPreSpawnPacketHandler;
use Supero\NightfallProtocol\network\handlers\CustomSessionStartPacketHandler;
use Supero\NightfallProtocol\network\static\convert\CustomTypeConverter;
use Supero\NightfallProtocol\network\static\CustomPacketSerializer;
use Supero\NightfallProtocol\network\static\PacketConverter;
use Supero\NightfallProtocol\utils\ReflectionUtils;

class CustomNetworkSession extends NetworkSession
{
    public int $protocol = CustomProtocolInfo::CURRENT_PROTOCOL;

    public function getProtocol(): int
    {
        return $this->protocol;
    }

    /**
     * @throws ReflectionException
     */
    public function setProtocol(int $protocol): void
    {
        $this->protocol = $protocol;

        $broadcaster = new CustomStandardPacketBroadcaster(Server::getInstance(), $protocol);
        $typeConverter = CustomTypeConverter::getFakeInstance($protocol);

        $this->setProperty("typeConverter", $typeConverter);
        $this->setProperty("broadcaster", $broadcaster);
        $this->setProperty("entityEventBroadcaster", new StandardEntityEventBroadcaster($broadcaster, $typeConverter));
    }


    /**
     * @throws ReflectionException
     */
    public function setHandler(?PacketHandler $handler): void
    {
        if($handler !== null){
            switch ($handler::class){
                case SessionStartPacketHandler::class:
                    $handler = new CustomSessionStartPacketHandler(
                        $this,
                        $this->onSessionStartSuccess(...)
                    );
                    break;
                case PreSpawnPacketHandler::class:
                    $this->setHandler(new CustomPreSpawnPacketHandler(
                            $this->getProperty("server"),
                            $this->getProperty("player"),
                            $this,
                            $this->getProperty("invManager"))
                    );
                    break;
                default:
                    break;
            }
        }

        parent::setHandler($handler);
    }

    /**
     * @throws ReflectionException
     */
    private function onSessionStartSuccess() : void{
        $this->getProperty("logger")->debug("Session start handshake completed, awaiting login packet");
        ReflectionUtils::invoke(NetworkSession::class, $this, "flushSendBuffer", true);
        $this->setProperty("enableCompression", true);
        $this->setHandler(new LoginPacketHandler(
            Server::getInstance(),
            $this,
            function(PlayerInfo $info) : void{
                $this->setProperty("info", $info);
                $this->getProperty("logger")->info(Server::getInstance()->getLanguage()->translate(KnownTranslationFactory::pocketmine_network_session_playerName(TextFormat::AQUA . $info->getUsername() . TextFormat::RESET)));
                $this->getProperty("logger")->setPrefix($this->getLogPrefix());
                $this->getProperty("manager")->markLoginReceived($this);
            },
            $this->setAuthenticationStatus(...)
        ));
    }

    /**
     * @throws ReflectionException
     */
    private function setAuthenticationStatus(bool $authenticated, bool $authRequired, Translatable|string|null $error, ?string $clientPubKey) : void{
        if(!$this->getProperty("connected")){
            return;
        }
        if($error === null){
            if($authenticated && !($this->getProperty("info") instanceof XboxLivePlayerInfo)){
                $error = "Expected XUID but none found";
            }elseif($clientPubKey === null){
                $error = "Missing client public key"; //failsafe
            }
        }

        if($error !== null){
            $this->disconnectWithError(
                reason: KnownTranslationFactory::pocketmine_disconnect_invalidSession($error),
                disconnectScreenMessage: KnownTranslationFactory::pocketmine_disconnect_error_authentication()
            );

            return;
        }

        $this->setProperty("authenticated", $authenticated);

        if(!$this->getProperty("authenticated")){
            if($authRequired){
                $this->disconnect("Not authenticated", KnownTranslationFactory::disconnectionScreen_notAuthenticated());
                return;
            }
            if($this->getProperty("info") instanceof XboxLivePlayerInfo){
                $this->getProperty("logger")->warning("Discarding unexpected XUID for non-authenticated player");
                $this->setProperty("info", $this->getProperty("info")->withoutXboxData());
            }
        }
        $this->getProperty("logger")->debug("Xbox Live authenticated: " . ($this->getProperty("authenticated") ? "YES" : "NO"));

        $checkXUID = Server::getInstance()->getConfigGroup()->getPropertyBool(YmlServerProperties::PLAYER_VERIFY_XUID, true);
        $myXUID = $this->getProperty("info") instanceof XboxLivePlayerInfo ? $this->getProperty("info")->getXuid() : "";
        $kickForXUIDMismatch = function(string $xuid) use ($checkXUID, $myXUID) : bool{
            if($checkXUID && $myXUID !== $xuid){
                $this->getProperty("logger")->debug("XUID mismatch: expected '$xuid', but got '$myXUID'");
                $this->disconnect("XUID does not match (possible impersonation attempt)");
                return true;
            }
            return false;
        };

        foreach($this->getProperty("manager")->getSessions() as $existingSession){
            if($existingSession === $this){
                continue;
            }
            $info = $existingSession->getPlayerInfo();
            if($info !== null && (strcasecmp($info->getUsername(), $this->getProperty("info")->getUsername()) === 0 || $info->getUuid()->equals($this->getProperty("info")->getUuid()))){
                if($kickForXUIDMismatch($info instanceof XboxLivePlayerInfo ? $info->getXuid() : "")){
                    return;
                }
                $ev = new PlayerDuplicateLoginEvent($this, $existingSession, KnownTranslationFactory::disconnectionScreen_loggedinOtherLocation(), null);
                $ev->call();
                if($ev->isCancelled()){
                    $this->disconnect($ev->getDisconnectReason(), $ev->getDisconnectScreenMessage());
                    return;
                }

                $existingSession->disconnect($ev->getDisconnectReason(), $ev->getDisconnectScreenMessage());
            }
        }

        $this->setProperty("cachedOfflinePlayerData", Server::getInstance()->getOfflinePlayerData($this->getProperty("info")->getUsername()));
        if($checkXUID){
            $recordedXUID = $this->getProperty("cachedOfflinePlayerData")?->getTag(Player::TAG_LAST_KNOWN_XUID);
            if(!($recordedXUID instanceof StringTag)){
                $this->getProperty("logger")->debug("No previous XUID recorded, no choice but to trust this player");
            }elseif(!$kickForXUIDMismatch($recordedXUID->getValue())){
                $this->getProperty("logger")->debug("XUID match");
            }
        }

        if(EncryptionContext::$ENABLED){
            Server::getInstance()->getAsyncPool()->submitTask(new PrepareEncryptionTask($clientPubKey, function(string $encryptionKey, string $handshakeJwt) : void{
                if(!$this->getProperty("connected")){
                    return;
                }
                $this->sendDataPacket(ServerToClientHandshakePacket::create($handshakeJwt), true); //make sure this gets sent before encryption is enabled

                $this->setProperty("cipher", EncryptionContext::fakeGCM($encryptionKey));

                $this->setHandler(new HandshakePacketHandler($this->onServerLoginSuccess(...)));
                $this->getProperty("logger")->debug("Enabled encryption");
            }));
        }else{
            $this->onServerLoginSuccess();
        }
    }

    /**
     * @throws ReflectionException
     */
    private function onServerLoginSuccess() : void{
        $this->setProperty("loggedIn", true);

        $this->sendDataPacket(PlayStatusPacket::create(PlayStatusPacket::LOGIN_SUCCESS));

        $this->getProperty("logger")->debug("Initiating resource packs phase");

        $packManager = Server::getInstance()->getResourcePackManager();
        $resourcePacks = $packManager->getResourceStack();
        $keys = [];
        foreach($resourcePacks as $resourcePack){
            $key = $packManager->getPackEncryptionKey($resourcePack->getPackId());
            if($key !== null){
                $keys[$resourcePack->getPackId()] = $key;
            }
        }
        $event = new PlayerResourcePackOfferEvent($this->getProperty("info"), $resourcePacks, $keys, $packManager->resourcePacksRequired());
        $event->call();
        $this->setHandler(new ResourcePacksPacketHandler($this, $event->getResourcePacks(), $event->getEncryptionKeys(), $event->mustAccept(), function() : void{
            $this->createPlayer();
        }));
    }

    /**
     * @throws ReflectionException
     */
    public function handleDataPacket(Packet $packet, string $buffer) : void{
        if(!($packet instanceof ServerboundPacket)){
            throw new PacketHandlingException("Unexpected non-serverbound packet");
        }
        //Ugly
        $convertedPacket = PacketConverter::handleServerbound($packet, $this->getProperty("typeConverter")) ?? $packet;
        $packet = $convertedPacket;

        $timings = Timings::getReceiveDataPacketTimings($packet);
        $timings->startTiming();

        try{
            if(DataPacketDecodeEvent::hasHandlers()){
                $ev = new DataPacketDecodeEvent($this, $packet->pid(), $buffer);
                $ev->call();
                if($ev->isCancelled()){
                    return;
                }
            }

            $decodeTimings = Timings::getDecodeDataPacketTimings($packet);
            $decodeTimings->startTiming();
            try{
                $stream = CustomPacketSerializer::decoder($buffer, 0);
                $stream->setProtocol($this->getProtocol());
                try{
                    $packet->decode($stream);
                }catch(PacketDecodeException $e){
                    throw PacketHandlingException::wrap($e);
                }
                if(!$stream->feof()){
                    $remains = substr($stream->getBuffer(), $stream->getOffset());
                    $this->getProperty("logger")->debug("Still " . strlen($remains) . " bytes unread in " . $packet->getName() . ": " . bin2hex($remains));
                }
            }finally{
                $decodeTimings->stopTiming();
            }

            if(DataPacketReceiveEvent::hasHandlers()){
                $ev = new DataPacketReceiveEvent($this, $packet);
                $ev->call();
                if($ev->isCancelled()){
                    return;
                }
            }
            $handlerTimings = Timings::getHandleDataPacketTimings($packet);
            $handlerTimings->startTiming();
            try{
                if($this->getProperty("handler") === null || !$packet->handle($this->getProperty("handler"))){
                    $this->getProperty("logger")->debug("Unhandled " . $packet->getName() . ": " . base64_encode($stream->getBuffer()));
                }
            }finally{
                $handlerTimings->stopTiming();
            }
        }finally{
            $timings->stopTiming();
        }
    }

    /**
     * @throws ReflectionException
     */
    public function startUsingChunk(int $chunkX, int $chunkZ, Closure $onCompletion): void
    {
        $world = $this->getProperty("player")->getLocation()->getWorld();
        CustomChunkCache::getInstance($world, $this->getProperty("compressor"), $this->getProtocol())->request($chunkX, $chunkZ)->onResolve(
            function(CompressBatchPromise $promise) use ($world, $onCompletion, $chunkX, $chunkZ) : void{
                if(!$this->isConnected()){
                    return;
                }
                $currentWorld = $this->getProperty("player")->getLocation()->getWorld();
                if($world !== $currentWorld || ($status = $this->getProperty("player")->getUsedChunkStatus($chunkX, $chunkZ)) === null){
                    $this->getProperty("logger")->debug("Tried to send no-longer-active chunk $chunkX $chunkZ in world " . $world->getFolderName());
                    return;
                }
                if($status !== UsedChunkStatus::REQUESTED_SENDING){
                    return;
                }
                $world->timings->syncChunkSend->startTiming();
                try{
                    $this->queueCompressed($promise);
                    $onCompletion();
                }finally{
                    $world->timings->syncChunkSend->stopTiming();
                }
            }
        );
    }

    /**
     * @throws ReflectionException
     */
    public function sendDataPacket(ClientboundPacket $packet, bool $immediate = false) : bool{
        $packet = PacketConverter::handleClientbound($packet, $this->getProperty("typeConverter")) ?? $packet;
        return $this->sendDataPacketInternal($packet, $immediate, null);
    }

    /**
     * @phpstan-return Promise<true>
     * @throws ReflectionException
     */
    public function sendDataPacketWithReceipt(ClientboundPacket $packet, bool $immediate = false) : Promise{
        $resolver = new PromiseResolver();

        if(!$this->sendDataPacketInternal($packet, $immediate, $resolver)){
            $resolver->reject();
        }

        return $resolver->getPromise();
    }

    /**
     * @phpstan-param PromiseResolver<true>|null $ackReceiptResolver
     * @throws ReflectionException
     */
    private function sendDataPacketInternal(ClientboundPacket $packet, bool $immediate, ?PromiseResolver $ackReceiptResolver) : bool{
        if(!$this->getProperty("connected")){
            return false;
        }
        if(!$this->getProperty("loggedIn") && !$packet->canBeSentBeforeLogin()){
            throw new InvalidArgumentException("Attempted to send " . get_class($packet) . " to " . $this->getDisplayName() . " too early");
        }

        $timings = Timings::getSendDataPacketTimings($packet);
        $timings->startTiming();
        try{
            if(DataPacketSendEvent::hasHandlers()){
                $ev = new DataPacketSendEvent([$this], [$packet]);
                $ev->call();
                if($ev->isCancelled()){
                    return false;
                }
                $packets = $ev->getPackets();
            }else{
                $packets = [$packet];
            }

            if($ackReceiptResolver !== null){
                $promises = $this->getProperty("sendBufferAckPromises");
                $promises[] = $ackReceiptResolver;

                $this->setProperty("sendBufferAckPromises", $promises);
            }
            foreach($packets as $evPacket){
                $encoder = CustomPacketSerializer::encoder();
                $encoder->setProtocol($this->protocol);

                $this->addToSendBuffer(self::encodePacketTimed($encoder, $evPacket));
            }
            if($immediate){
                ReflectionUtils::invoke(NetworkSession::class, $this, "flushSendBuffer", true);
            }

            return true;
        }finally{
            $timings->stopTiming();
        }
    }

    private function getLogPrefix() : string{
        return "NetworkSession: " . $this->getDisplayName();
    }

    /**
     * @throws ReflectionException
     */
    public function getProperty($name) : mixed
    {
        return ReflectionUtils::getProperty(NetworkSession::class, $this, $name);
    }

    /**
     * @throws ReflectionException
     */
    public function setProperty($name, $value) : void
    {
        ReflectionUtils::setProperty(NetworkSession::class, $this, $name, $value);
    }
}