<?php

declare(strict_types=1);

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
use pocketmine\network\mcpe\compression\DecompressionException;
use pocketmine\network\mcpe\encryption\DecryptionException;
use pocketmine\network\mcpe\encryption\EncryptionContext;
use pocketmine\network\mcpe\encryption\PrepareEncryptionTask;
use pocketmine\network\mcpe\handler\HandshakePacketHandler;
use pocketmine\network\mcpe\handler\InGamePacketHandler;
use pocketmine\network\mcpe\handler\PacketHandler;
use pocketmine\network\mcpe\handler\PreSpawnPacketHandler;
use pocketmine\network\mcpe\handler\ResourcePacksPacketHandler;
use pocketmine\network\mcpe\handler\SessionStartPacketHandler;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\Packet;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\PlayStatusPacket;
use pocketmine\network\mcpe\protocol\serializer\PacketBatch;
use pocketmine\network\mcpe\protocol\ServerboundPacket;
use pocketmine\network\mcpe\protocol\ServerToClientHandshakePacket;
use pocketmine\network\mcpe\protocol\types\CompressionAlgorithm;
use pocketmine\network\PacketHandlingException;
use pocketmine\player\Player;
use pocketmine\player\PlayerInfo;
use pocketmine\player\UsedChunkStatus;
use pocketmine\player\XboxLivePlayerInfo;
use pocketmine\promise\Promise;
use pocketmine\promise\PromiseResolver;
use pocketmine\Server;
use pocketmine\timings\Timings;
use pocketmine\utils\BinaryDataException;
use pocketmine\utils\BinaryStream;
use pocketmine\utils\TextFormat;
use pocketmine\YmlServerProperties;
use ReflectionException;
use Supero\NightfallProtocol\network\chunk\CustomChunkCache;
use Supero\NightfallProtocol\network\handlers\CustomLoginPacketHandler;
use Supero\NightfallProtocol\network\handlers\CustomPreSpawnPacketHandler;
use Supero\NightfallProtocol\network\handlers\CustomSessionStartPacketHandler;
use Supero\NightfallProtocol\network\handlers\static\InGamePacketHandler as CustomInGamePacketHandler;
use Supero\NightfallProtocol\network\packets\TextPacket;
use Supero\NightfallProtocol\network\static\convert\CustomTypeConverter;
use Supero\NightfallProtocol\network\static\CustomPacketSerializer;
use Supero\NightfallProtocol\network\static\PacketConverter;
use Supero\NightfallProtocol\utils\ProtocolUtils;
use Supero\NightfallProtocol\utils\ReflectionUtils;
use function base64_encode;
use function bin2hex;
use function count;
use function get_class;
use function ord;
use function strcasecmp;
use function strlen;
use function substr;
use function time;

class CustomNetworkSession extends NetworkSession
{
	public int $protocol = CustomProtocolInfo::CURRENT_PROTOCOL;

	public function getProtocol() : int
	{
		return $this->protocol;
	}

	/**
	 * @throws ReflectionException
	 */
	public function setProtocol(int $protocol) : void
	{
		$this->protocol = $protocol;

		$broadcaster = ProtocolUtils::getPacketBroadcaster($protocol);
		$typeConverter = CustomTypeConverter::getProtocolInstance($protocol);
		$eventBroadcaster = ProtocolUtils::getEntityEventBroadcaster($protocol);

		$this->setProperty("typeConverter", $typeConverter);
		$this->setProperty("broadcaster", $broadcaster);
		$this->setProperty("entityEventBroadcaster", $eventBroadcaster);
	}

	/**
	 * @throws ReflectionException
	 */
	public function setHandler(?PacketHandler $handler) : void
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
					$handler = new CustomPreSpawnPacketHandler(
						$this->getProperty("server"),
						$this->getProperty("player"),
						$this,
						$this->getProperty("invManager")
					);
					break;
				case InGamePacketHandler::class:
					$handler = new CustomInGamePacketHandler(
						$this->getProperty("player"),
						$this,
						$this->getProperty("invManager")
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
		$this->flushSendBuffer(true);
		$this->setProperty("enableCompression", true);
		$this->setHandler(new CustomLoginPacketHandler(
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

	private function flushSendBuffer(bool $immediate = false) : void{
		if(count($this->getProperty("sendBuffer")) > 0){
			Timings::$playerNetworkSend->startTiming();
			try{
				$syncMode = null; //automatic
				if($immediate){
					$syncMode = true;
				}elseif($this->getProperty("forceAsyncCompression")){
					$syncMode = false;
				}

				$stream = new BinaryStream();
				PacketBatch::encodeRaw($stream, $this->getProperty("sendBuffer"));

				if($this->getProperty("enableCompression")){
					$batch = ProtocolUtils::prepareBatch(
						$stream->getBuffer(),
						$this->getProperty("compressor"),
						$this->getProperty("server"),
						$this->protocol,
						$syncMode,
						Timings::$playerNetworkSendCompressSessionBuffer
					);
				}else{
					$batch = $stream->getBuffer();
				}
				$this->setProperty("sendBuffer", []);
				$ackPromises = $this->getProperty("sendBufferAckPromises");
				$this->setProperty("sendBufferAckPromises", []);
				ReflectionUtils::invoke(NetworkSession::class, $this, "queueCompressedNoBufferFlush", $batch, $immediate, $ackPromises);
			}finally{
				Timings::$playerNetworkSend->stopTiming();
			}
		}
	}

	/**
	 * @throws PacketHandlingException
	 */
	public function handleEncoded(string $payload) : void{
		if(!$this->getProperty("connected")){
			return;
		}

		Timings::$playerNetworkReceive->startTiming();
		try{
			$this->getProperty("packetBatchLimiter")->decrement();

			if($this->getProperty("cipher") !== null){
				Timings::$playerNetworkReceiveDecrypt->startTiming();
				try{
					$payload = $this->getProperty("cipher")->decrypt($payload);
				}catch(DecryptionException $e){
					$this->getProperty("logger")->debug("Encrypted packet: " . base64_encode($payload));
					throw PacketHandlingException::wrap($e, "Packet decryption error");
				}finally{
					Timings::$playerNetworkReceiveDecrypt->stopTiming();
				}
			}

			if(strlen($payload) < 1){
				throw new PacketHandlingException("No bytes in payload");
			}

			if($this->getProperty("enableCompression")){
				if($this->protocol >= CustomProtocolInfo::PROTOCOL_1_20_60){
					$compressionType = ord($payload[0]);
					$compressed = substr($payload, 1);
					if($compressionType === CompressionAlgorithm::NONE){
						$decompressed = $compressed;
					}elseif($compressionType === $this->getProperty("compressor")->getNetworkId()){
						try{
							Timings::$playerNetworkReceiveDecompress->startTiming();
							$decompressed = $this->getProperty("compressor")->decompress($compressed);
						}catch(DecompressionException $e){
							$this->getProperty("logger")->debug("Failed to decompress packet: " . base64_encode($compressed));
							throw PacketHandlingException::wrap($e, "Compressed packet batch decode error");
						}finally{
							Timings::$playerNetworkReceiveDecompress->stopTiming();
						}
					}else{
						throw new PacketHandlingException("Packet compressed with unexpected compression type $compressionType");
					}
				}else{
					try{
						Timings::$playerNetworkReceiveDecompress->startTiming();
						$decompressed = $this->getProperty("compressor")->decompress($payload);
					}catch(DecompressionException $e){
						$this->getProperty("logger")->debug("Failed to decompress packet: " . base64_encode($payload));
						throw PacketHandlingException::wrap($e, "Compressed packet batch decode error");
					}finally{
						Timings::$playerNetworkReceiveDecompress->stopTiming();
					}
				}
			}else{
				$decompressed = $payload;
			}

			try{
				$stream = new BinaryStream($decompressed);
				foreach(PacketBatch::decodeRaw($stream) as $buffer){
					$this->getProperty("gamePacketLimiter")->decrement();
					$packet = $this->getProperty("packetPool")->getPacket($buffer);
					if($packet === null){
						$this->getProperty("logger")->debug("Unknown packet: " . base64_encode($buffer));
						throw new PacketHandlingException("Unknown packet received");
					}
					try{
						$this->handleDataPacket($packet, $buffer);
					}catch(PacketHandlingException $e){
						$this->getProperty("logger")->debug($packet->getName() . ": " . base64_encode($buffer));
						throw PacketHandlingException::wrap($e, "Error processing " . $packet->getName());
					}
				}
			}catch(PacketDecodeException|BinaryDataException $e){
				$this->getProperty("logger")->logException($e);
				throw PacketHandlingException::wrap($e, "Packet batch decode error");
			}
		}finally{
			Timings::$playerNetworkReceive->stopTiming();
		}
	}

	/**
	 * @throws ReflectionException
	 */
	public function handleDataPacket(Packet $packet, string $buffer) : void{
		if(!($packet instanceof ServerboundPacket)){
			throw new PacketHandlingException("Unexpected non-serverbound packet");
		}
		//TODO: WHAT DO I DO HERE????
		// The modified packet is being decoded properly but not the original one due to us overriding it
		// Meaning we can't send the original packet in the event (WTF !!!!!!!!)
		$packet = PacketConverter::handleServerbound($packet, $this->getProperty("typeConverter"));

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
	public function startUsingChunk(int $chunkX, int $chunkZ, Closure $onCompletion) : void
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

	public function queueCompressed(CompressBatchPromise|string $payload, bool $immediate = false) : void{
		Timings::$playerNetworkSend->startTiming();
		try{
			$this->flushSendBuffer($immediate); //Maintain ordering if possible
			ReflectionUtils::invoke(NetworkSession::class, $this, "queueCompressedNoBufferFlush", $payload, $immediate);
		}finally{
			Timings::$playerNetworkSend->stopTiming();
		}
	}

	/**
	 * @throws ReflectionException
	 */
	public function sendDataPacket(ClientboundPacket $packet, bool $immediate = false) : bool{
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

		$oldPacket = clone $packet;

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
				$ev = new DataPacketSendEvent([$this], [$oldPacket]);
				$ev->call();
				if($ev->isCancelled()){
					return false;
				}
				//what the sigma?
				$packets = [];
				foreach ($ev->getPackets() as $label => $packet) {
					$packets[$label] = PacketConverter::handleClientbound($packet, $this->getProperty("typeConverter"), $this);
				}
			}else{
				$packets = [(PacketConverter::handleClientbound($packet, $this->getProperty("typeConverter"), $this))];
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
				$this->flushSendBuffer(true);
			}

			return true;
		}finally{
			$timings->stopTiming();
		}
	}

	public function onChatMessage(Translatable|string $message) : void{
		if($message instanceof Translatable){
			if(!$this->getProperty("server")->isLanguageForced()){
				$this->sendDataPacket(TextPacket::translation(...$this->prepareClientTranslatableMessage($message)));
			}else{
				$this->sendDataPacket(TextPacket::raw($this->getProperty("server")->getLanguage()->translate($message)));
			}
		}else{
			$this->sendDataPacket(TextPacket::raw($message));
		}
	}

	public function onJukeboxPopup(Translatable|string $message) : void{
		$parameters = [];
		if($message instanceof Translatable){
			if(!$this->getProperty("server")->isLanguageForced()){
				[$message, $parameters] = $this->prepareClientTranslatableMessage($message);
			}else{
				$message = $this->getProperty("player")->getLanguage()->translate($message);
			}
		}
		$this->sendDataPacket(TextPacket::jukeboxPopup($message, $parameters));
	}

	public function onPopup(string $message) : void{
		$this->sendDataPacket(TextPacket::popup($message));
	}

	public function onTip(string $message) : void{
		$this->sendDataPacket(TextPacket::tip($message));
	}

	/**
	 * @throws ReflectionException
	 */
	public function tick() : void
	{
		if(!$this->isConnected()){
			ReflectionUtils::invoke(NetworkSession::class, $this, "dispose");
			return;
		}

		if($this->getProperty("info") === null){
			if(time() >= $this->getProperty("connectTime") + 10){
				$this->disconnectWithError(KnownTranslationFactory::pocketmine_disconnect_error_loginTimeout());
			}

			return;
		}

		if($this->getProperty("player") !== null){
			$this->getProperty("player")->doChunkRequests();

			$dirtyAttributes = $this->getProperty("player")->getAttributeMap()->needSend();
			$this->getProperty("entityEventBroadcaster")->syncAttributes([$this], $this->getProperty("player"), $dirtyAttributes);
			foreach($dirtyAttributes as $attribute){
				//we might need to send these to other players in the future
				//if that happens, this will need to become more complex than a flag on the attribute itself
				$attribute->markSynchronized();
			}
		}
		Timings::$playerNetworkSendInventorySync->startTiming();
		try{
			$this->getProperty("invManager")?->flushPendingUpdates();
		}finally{
			Timings::$playerNetworkSendInventorySync->stopTiming();
		}

		$this->flushSendBuffer();
	}

	private function getLogPrefix() : string{
		return "MultiVersionNetworkSession: " . $this->getDisplayName();
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
