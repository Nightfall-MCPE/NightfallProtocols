<?php

declare(strict_types=1);

namespace Supero\NightfallProtocol\network\packets\types\resourcepacks;

use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Supero\NightfallProtocol\network\CustomProtocolInfo;

class CustomResourcePackInfoEntry{
	public function __construct(
		private UuidInterface $packId,
		private string $version,
		private int $sizeBytes,
		private string $encryptionKey = "",
		private string $subPackName = "",
		private string $contentId = "",
		private bool $hasScripts = false,
		private bool $isAddonPack = false,
		private bool $isRtxCapable = false,
		private string $cdnUrl = ""
	){}

	public function getPackId() : UuidInterface{
		return $this->packId;
	}

	public function getVersion() : string{
		return $this->version;
	}

	public function getSizeBytes() : int{
		return $this->sizeBytes;
	}

	public function getEncryptionKey() : string{
		return $this->encryptionKey;
	}

	public function getSubPackName() : string{
		return $this->subPackName;
	}

	public function getContentId() : string{
		return $this->contentId;
	}

	public function hasScripts() : bool{
		return $this->hasScripts;
	}

	public function isAddonPack() : bool{ return $this->isAddonPack; }

	public function isRtxCapable() : bool{ return $this->isRtxCapable; }

	public function getCdnUrl() : string{ return $this->cdnUrl; }

	public function write(PacketSerializer $out) : void{
		if($out->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_50){
			$out->putUUID($this->packId);
		}else{
			$out->putString($this->packId->toString());
		}
		$out->putString($this->version);
		$out->putLLong($this->sizeBytes);
		$out->putString($this->encryptionKey);
		$out->putString($this->subPackName);
		$out->putString($this->contentId);
		$out->putBool($this->hasScripts);
		if($out->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_20) {
			$out->putBool($this->isAddonPack);
		}
		$out->putBool($this->isRtxCapable);
		if($out->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_40) {
			$out->putString($this->cdnUrl);
		}
	}

	public static function read(PacketSerializer $in) : self{
		if($in->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_50){
			$uuid = $in->getUUID();
		}else{
			$uuid = Uuid::fromString($in->getString());
		}
		$version = $in->getString();
		$sizeBytes = $in->getLLong();
		$encryptionKey = $in->getString();
		$subPackName = $in->getString();
		$contentId = $in->getString();
		$hasScripts = $in->getBool();
		if($in->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_20){
			$isAddonPack = $in->getBool();
		}
		$rtxCapable = $in->getBool();
		if($in->getProtocol() >= CustomProtocolInfo::PROTOCOL_1_21_40) {
			$cdnUrl = $in->getString();
		}
		return new self($uuid, $version, $sizeBytes, $encryptionKey, $subPackName, $contentId, $hasScripts, $isAddonPack ?? false, $rtxCapable, $cdnUrl ?? "");
	}
}
