<?php

namespace Supero\NightfallProtocol\network;

use pocketmine\event\player\PlayerDuplicateLoginEvent;
use pocketmine\event\player\PlayerResourcePackOfferEvent;
use pocketmine\lang\KnownTranslationFactory;
use pocketmine\lang\Translatable;
use pocketmine\nbt\tag\StringTag;
use pocketmine\network\mcpe\encryption\EncryptionContext;
use pocketmine\network\mcpe\encryption\PrepareEncryptionTask;
use pocketmine\network\mcpe\handler\HandshakePacketHandler;
use pocketmine\network\mcpe\handler\LoginPacketHandler;
use pocketmine\network\mcpe\handler\PacketHandler;
use pocketmine\network\mcpe\handler\ResourcePacksPacketHandler;
use pocketmine\network\mcpe\handler\SessionStartPacketHandler;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\PlayStatusPacket;
use pocketmine\network\mcpe\protocol\ServerToClientHandshakePacket;
use pocketmine\player\Player;
use pocketmine\player\PlayerInfo;
use pocketmine\player\XboxLivePlayerInfo;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use pocketmine\YmlServerProperties;
use ReflectionException;
use Supero\NightfallProtocol\network\handlers\CustomSessionStartPacketHandler;
use Supero\NightfallProtocol\utils\ReflectionUtils;

class CustomNetworkSession extends NetworkSession
{

    public function setHandler(?PacketHandler $handler): void
    {
        if($handler instanceof SessionStartPacketHandler){
            $handler = new CustomSessionStartPacketHandler(
                $this,
                $this->onSessionStartSuccess(...)
            );
        }
        parent::setHandler($handler);
    }

    /**
     * @throws ReflectionException
     */
    private function onSessionStartSuccess() : void{
        ReflectionUtils::getProperty(NetworkSession::class, $this, "logger")->debug("Session start handshake completed, awaiting login packet");
        ReflectionUtils::invoke(NetworkSession::class, $this, "flushSendBuffer", true);
        ReflectionUtils::setProperty(NetworkSession::class, $this, "enableCompression", true);
        $this->setHandler(new LoginPacketHandler(
            Server::getInstance(),
            $this,
            function(PlayerInfo $info) : void{
                ReflectionUtils::setProperty(NetworkSession::class, $this, "info", $info);
                ReflectionUtils::getProperty(NetworkSession::class, $this, "logger")->info(Server::getInstance()->getLanguage()->translate(KnownTranslationFactory::pocketmine_network_session_playerName(TextFormat::AQUA . $info->getUsername() . TextFormat::RESET)));
                ReflectionUtils::getProperty(NetworkSession::class, $this, "logger")->setPrefix($this->getLogPrefix());
                ReflectionUtils::getProperty(NetworkSession::class, $this, "manager")->markLoginReceived($this);
            },
            $this->setAuthenticationStatus(...)
        ));
    }

    /**
     * @throws ReflectionException
     */
    private function setAuthenticationStatus(bool $authenticated, bool $authRequired, Translatable|string|null $error, ?string $clientPubKey) : void{
        if(!ReflectionUtils::getProperty(NetworkSession::class, $this, "connected")){
            return;
        }
        if($error === null){
            if($authenticated && !(ReflectionUtils::getProperty(NetworkSession::class, $this, "info") instanceof XboxLivePlayerInfo)){
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

        ReflectionUtils::setProperty(NetworkSession::class, $this, "authenticated", $authenticated);

        if(!ReflectionUtils::getProperty(NetworkSession::class, $this, "authenticated")){
            if($authRequired){
                $this->disconnect("Not authenticated", KnownTranslationFactory::disconnectionScreen_notAuthenticated());
                return;
            }
            if(ReflectionUtils::getProperty(NetworkSession::class, $this, "info") instanceof XboxLivePlayerInfo){
                ReflectionUtils::getProperty(NetworkSession::class, $this, "logger")->warning("Discarding unexpected XUID for non-authenticated player");
                ReflectionUtils::setProperty(NetworkSession::class, $this, "info", ReflectionUtils::getProperty(NetworkSession::class, $this, "info")->withoutXboxData());
            }
        }
        ReflectionUtils::getProperty(NetworkSession::class, $this, "logger")->debug("Xbox Live authenticated: " . (ReflectionUtils::getProperty(NetworkSession::class, $this, "authenticated") ? "YES" : "NO"));

        $checkXUID = Server::getInstance()->getConfigGroup()->getPropertyBool(YmlServerProperties::PLAYER_VERIFY_XUID, true);
        $myXUID = ReflectionUtils::getProperty(NetworkSession::class, $this, "info") instanceof XboxLivePlayerInfo ? ReflectionUtils::getProperty(NetworkSession::class, $this, "info")->getXuid() : "";
        $kickForXUIDMismatch = function(string $xuid) use ($checkXUID, $myXUID) : bool{
            if($checkXUID && $myXUID !== $xuid){
                ReflectionUtils::getProperty(NetworkSession::class, $this, "logger")->debug("XUID mismatch: expected '$xuid', but got '$myXUID'");
                $this->disconnect("XUID does not match (possible impersonation attempt)");
                return true;
            }
            return false;
        };

        foreach(ReflectionUtils::getProperty(NetworkSession::class, $this, "manager")->getSessions() as $existingSession){
            if($existingSession === $this){
                continue;
            }
            $info = $existingSession->getPlayerInfo();
            if($info !== null && (strcasecmp($info->getUsername(), ReflectionUtils::getProperty(NetworkSession::class, $this, "info")->getUsername()) === 0 || $info->getUuid()->equals(ReflectionUtils::getProperty(NetworkSession::class, $this, "info")->getUuid()))){
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

        ReflectionUtils::setProperty(NetworkSession::class, $this, "cachedOfflinePlayerData", Server::getInstance()->getOfflinePlayerData(ReflectionUtils::getProperty(NetworkSession::class, $this, "info")->getUsername()));
        if($checkXUID){
            $recordedXUID = ReflectionUtils::getProperty(NetworkSession::class, $this, "cachedOfflinePlayerData",)?->getTag(Player::TAG_LAST_KNOWN_XUID);
            if(!($recordedXUID instanceof StringTag)){
                ReflectionUtils::getProperty(NetworkSession::class, $this, "logger")->debug("No previous XUID recorded, no choice but to trust this player");
            }elseif(!$kickForXUIDMismatch($recordedXUID->getValue())){
                ReflectionUtils::getProperty(NetworkSession::class, $this, "logger")->debug("XUID match");
            }
        }

        if(EncryptionContext::$ENABLED){
            Server::getInstance()->getAsyncPool()->submitTask(new PrepareEncryptionTask($clientPubKey, function(string $encryptionKey, string $handshakeJwt) : void{
                if(!ReflectionUtils::getProperty(NetworkSession::class, $this, "connected")){
                    return;
                }
                $this->sendDataPacket(ServerToClientHandshakePacket::create($handshakeJwt), true); //make sure this gets sent before encryption is enabled

                ReflectionUtils::setProperty(NetworkSession::class, $this, "cipher", EncryptionContext::fakeGCM($encryptionKey));

                $this->setHandler(new HandshakePacketHandler($this->onServerLoginSuccess(...)));
                ReflectionUtils::getProperty(NetworkSession::class, $this, "logger")->debug("Enabled encryption");
            }));
        }else{
            $this->onServerLoginSuccess();
        }
    }

    /**
     * @throws ReflectionException
     */
    private function onServerLoginSuccess() : void{
        ReflectionUtils::setProperty(NetworkSession::class, $this, "loggedIn", true);

        $this->sendDataPacket(PlayStatusPacket::create(PlayStatusPacket::LOGIN_SUCCESS));

        ReflectionUtils::getProperty(NetworkSession::class, $this, "logger")->debug("Initiating resource packs phase");

        $packManager = Server::getInstance()->getResourcePackManager();
        $resourcePacks = $packManager->getResourceStack();
        $keys = [];
        foreach($resourcePacks as $resourcePack){
            $key = $packManager->getPackEncryptionKey($resourcePack->getPackId());
            if($key !== null){
                $keys[$resourcePack->getPackId()] = $key;
            }
        }
        $event = new PlayerResourcePackOfferEvent(ReflectionUtils::getProperty(NetworkSession::class, $this, "info"), $resourcePacks, $keys, $packManager->resourcePacksRequired());
        $event->call();
        $this->setHandler(new ResourcePacksPacketHandler($this, $event->getResourcePacks(), $event->getEncryptionKeys(), $event->mustAccept(), function() : void{
            $this->createPlayer();
        }));
    }

    private function getLogPrefix() : string{
        return "NetworkSession: " . $this->getDisplayName();
    }
}