<?php

declare(strict_types=1);

namespace Supero\NightfallProtocol\network\packets;

use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\types\AbilitiesData as PM_AbilitiesData;
use pocketmine\network\mcpe\protocol\UpdateAbilitiesPacket as PM_Packet;
use Supero\NightfallProtocol\network\packets\types\AbilitiesData;

/**
 * Updates player abilities and permissions, such as command permissions, flying/noclip, fly speed, walk speed etc.
 * Abilities may be layered in order to combine different ability sets into a resulting set.
 */
class UpdateAbilitiesPacket extends PM_Packet{

	private AbilitiesData $data;

	/**
	 * @generate-create-func
	 */
	public static function createPacket(AbilitiesData $data) : self {
		$result = new self();
		$result->data = $data;
		return $result;
	}

	public function getData() : PM_AbilitiesData{ return $this->data; }

	protected function decodePayload(PacketSerializer $in) : void {
		$this->data = AbilitiesData::decode($in);
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
