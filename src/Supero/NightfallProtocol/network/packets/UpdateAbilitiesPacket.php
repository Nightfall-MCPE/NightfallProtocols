<?php

declare(strict_types=1);

namespace Supero\NightfallProtocol\network\packets;

use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\UpdateAbilitiesPacket as PM_Packet;
use Supero\NightfallProtocol\network\packets\types\CustomAbilitiesData;

/**
 * Updates player abilities and permissions, such as command permissions, flying/noclip, fly speed, walk speed etc.
 * Abilities may be layered in order to combine different ability sets into a resulting set.
 */
class UpdateAbilitiesPacket extends PM_Packet{

	private CustomAbilitiesData $data;

	/**
	 * @generate-create-func
	 */
	public static function createPacket(CustomAbilitiesData $data) : self {
		$result = new self();
		$result->data = $data;
		return $result;
	}

	protected function decodePayload(PacketSerializer $in) : void {
		$this->data = CustomAbilitiesData::decode($in);
	}

	protected function encodePayload(PacketSerializer $out) : void {
		$this->data->encode($out);
	}

	public function getConstructorArguments(PM_Packet $packet) : array {
		return [
			$packet->data
		];
	}
}
