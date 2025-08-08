<?php

declare(strict_types=1);

namespace Supero\MultiVersion\network\packets\types\camera;

use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\types\camera\CameraAimAssistCategory;
use function count;

class CustomCameraAimAssistCategories
{

	/**
	 * @param CameraAimAssistCategory[] $categories
	 */
	public function __construct(
		private string $identifier,
		private array $categories
	){}

	public function getIdentifier() : string{ return $this->identifier; }

	/**
	 * @return CameraAimAssistCategory[]
	 */
	public function getCategories() : array{ return $this->categories; }

	public static function read(PacketSerializer $in) : self{
		$identifier = $in->getString();

		$categories = [];
		for($i = 0, $len = $in->getUnsignedVarInt(); $i < $len; ++$i){
			$categories[] = CameraAimAssistCategory::read($in);
		}

		return new self(
			$identifier,
			$categories
		);
	}

	public function write(PacketSerializer $out) : void{
		$out->putString($this->identifier);
		$out->putUnsignedVarInt(count($this->categories));
		foreach($this->categories as $category){
			$category->write($out);
		}
	}
}
