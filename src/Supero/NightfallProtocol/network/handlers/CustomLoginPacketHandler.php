<?php

declare(strict_types=1);

namespace Supero\NightfallProtocol\network\handlers;

use Closure;
use InvalidArgumentException;
use JsonMapper;
use JsonMapper_Exception;
use pocketmine\entity\InvalidSkinException;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\lang\KnownTranslationFactory;
use pocketmine\lang\Translatable;
use pocketmine\network\mcpe\auth\ProcessLoginTask;
use pocketmine\network\mcpe\handler\PacketHandler;
use pocketmine\network\mcpe\JwtException;
use pocketmine\network\mcpe\JwtUtils;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\network\mcpe\protocol\types\login\AuthenticationData;
use pocketmine\network\mcpe\protocol\types\login\ClientData;
use pocketmine\network\mcpe\protocol\types\login\ClientDataToSkinDataHelper;
use pocketmine\network\mcpe\protocol\types\login\JwtChain;
use pocketmine\network\PacketHandlingException;
use pocketmine\player\Player;
use pocketmine\player\PlayerInfo;
use pocketmine\player\XboxLivePlayerInfo;
use pocketmine\Server;
use Ramsey\Uuid\Uuid;
use function is_array;

class CustomLoginPacketHandler extends PacketHandler{
	/**
	 * @phpstan-param Closure(PlayerInfo) : void $playerInfoConsumer
	 * @phpstan-param Closure(bool $isAuthenticated, bool $authRequired, Translatable|string|null $error, ?string $clientPubKey) : void $authCallback
	 */
	public function __construct(
		private Server $server,
		private NetworkSession $session,
		private Closure $playerInfoConsumer,
		private Closure $authCallback
	){}

	public function handleLogin(LoginPacket $packet) : bool{
		$extraData = $this->fetchAuthData($packet->chainDataJwt);

		if(!Player::isValidUserName($extraData->displayName)){
			$this->session->disconnectWithError(KnownTranslationFactory::disconnectionScreen_invalidName());

			return true;
		}

		$clientData = $this->parseClientData($packet->clientDataJwt);

		try{
			$skin = $this->session->getTypeConverter()->getSkinAdapter()->fromSkinData(ClientDataToSkinDataHelper::fromClientData($clientData));
		}catch(InvalidArgumentException | InvalidSkinException $e){
			$this->session->disconnectWithError(
				reason: "Invalid skin: " . $e->getMessage(),
				disconnectScreenMessage: KnownTranslationFactory::disconnectionScreen_invalidSkin()
			);

			return true;
		}

		if(!Uuid::isValid($extraData->identity)){
			throw new PacketHandlingException("Invalid login UUID");
		}
		$uuid = Uuid::fromString($extraData->identity);
		$arrClientData = (array) $clientData;
		$arrClientData["TitleID"] = $extraData->titleId;

		if($extraData->XUID !== ""){
			$playerInfo = new XboxLivePlayerInfo(
				$extraData->XUID,
				$extraData->displayName,
				$uuid,
				$skin,
				$clientData->LanguageCode,
				$arrClientData
			);
		}else{
			$playerInfo = new PlayerInfo(
				$extraData->displayName,
				$uuid,
				$skin,
				$clientData->LanguageCode,
				$arrClientData
			);
		}
		($this->playerInfoConsumer)($playerInfo);

		$ev = new PlayerPreLoginEvent(
			$playerInfo,
			$this->session->getIp(),
			$this->session->getPort(),
			$this->server->requiresAuthentication()
		);
		if($this->server->getNetwork()->getValidConnectionCount() > $this->server->getMaxPlayers()){
			$ev->setKickFlag(PlayerPreLoginEvent::KICK_FLAG_SERVER_FULL, KnownTranslationFactory::disconnectionScreen_serverFull());
		}
		if(!$this->server->isWhitelisted($playerInfo->getUsername())){
			$ev->setKickFlag(PlayerPreLoginEvent::KICK_FLAG_SERVER_WHITELISTED, KnownTranslationFactory::pocketmine_disconnect_whitelisted());
		}

		$banMessage = null;
		if(($banEntry = $this->server->getNameBans()->getEntry($playerInfo->getUsername())) !== null){
			$banReason = $banEntry->getReason();
			$banMessage = $banReason === "" ? KnownTranslationFactory::pocketmine_disconnect_ban_noReason() : KnownTranslationFactory::pocketmine_disconnect_ban($banReason);
		}elseif(($banEntry = $this->server->getIPBans()->getEntry($this->session->getIp())) !== null){
			$banReason = $banEntry->getReason();
			$banMessage = KnownTranslationFactory::pocketmine_disconnect_ban($banReason !== "" ? $banReason : KnownTranslationFactory::pocketmine_disconnect_ban_ip());
		}
		if($banMessage !== null){
			$ev->setKickFlag(PlayerPreLoginEvent::KICK_FLAG_BANNED, $banMessage);
		}

		$ev->call();
		if(!$ev->isAllowed()){
			$this->session->disconnect($ev->getFinalDisconnectReason(), $ev->getFinalDisconnectScreenMessage());
			return true;
		}

		$this->processLogin($packet, $ev->isAuthRequired());

		return true;
	}

	/**
	 * @throws PacketHandlingException
	 */
	protected function fetchAuthData(JwtChain $chain) : AuthenticationData{
		/** @var AuthenticationData|null $extraData */
		$extraData = null;
		foreach($chain->chain as $jwt){
			//validate every chain element
			try{
				[, $claims, ] = JwtUtils::parse($jwt);
			}catch(JwtException $e){
				throw PacketHandlingException::wrap($e);
			}
			if(isset($claims["extraData"])){
				if($extraData !== null){
					throw new PacketHandlingException("Found 'extraData' more than once in chainData");
				}

				if(!is_array($claims["extraData"])){
					throw new PacketHandlingException("'extraData' key should be an array");
				}
				$mapper = new JsonMapper();
				$mapper->bEnforceMapType = false;
				$mapper->bExceptionOnMissingData = true;
				$mapper->bExceptionOnUndefinedProperty = true;
				$mapper->bStrictObjectTypeChecking = true;
				try{
					/** @var AuthenticationData $extraData */
					$extraData = $mapper->map($claims["extraData"], new AuthenticationData());
				}catch(JsonMapper_Exception $e){
					throw PacketHandlingException::wrap($e);
				}
			}
		}
		if($extraData === null){
			throw new PacketHandlingException("'extraData' not found in chain data");
		}
		return $extraData;
	}

	/**
	 * @throws PacketHandlingException
	 */
	protected function parseClientData(string $clientDataJwt) : ClientData{
		try{
			[, $clientDataClaims, ] = JwtUtils::parse($clientDataJwt);
		}catch(JwtException $e){
			throw PacketHandlingException::wrap($e);
		}

		$mapper = new JsonMapper();
		$mapper->bEnforceMapType = false;
		$mapper->bExceptionOnMissingData = true;
		$mapper->bExceptionOnUndefinedProperty = true;
		$mapper->bStrictObjectTypeChecking = true;
		try{
			$clientData = $mapper->map($clientDataClaims, new ClientData());
		}catch(JsonMapper_Exception $e){
			throw PacketHandlingException::wrap($e);
		}
		return $clientData;
	}

	/**
	 * This is separated for the purposes of allowing plugins (like Specter) to hack it and bypass authentication.
	 * In the future this won't be necessary.
	 *
	 * @throws InvalidArgumentException
	 */
	protected function processLogin(LoginPacket $packet, bool $authRequired) : void{
		$this->server->getAsyncPool()->submitTask(new ProcessLoginTask($packet->chainDataJwt->chain, $packet->clientDataJwt, $authRequired, $this->authCallback));
		$this->session->setHandler(null); //drop packets received during login verification
	}
}
