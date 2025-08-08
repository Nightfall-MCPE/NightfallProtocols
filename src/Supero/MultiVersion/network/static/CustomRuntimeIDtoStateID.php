<?php

declare(strict_types=1);

namespace Supero\MultiVersion\network\static;

use pocketmine\block\RuntimeBlockStateRegistry;
use Supero\MultiVersion\network\static\convert\CustomTypeConverter;
use Supero\MultiVersion\utils\ProtocolSingletonTrait;

class CustomRuntimeIDtoStateID
{
	use ProtocolSingletonTrait;

	private array $runtimeIdToStateId = [];

	public function __construct(private readonly int $protocol)
	{
		$this->setProtocolInstance($this, $protocol);
		$blockTranslator = CustomTypeConverter::getInstance()->getBlockTranslator();
		foreach (RuntimeBlockStateRegistry::getInstance()->getAllKnownStates() as $state) {
			$blockRuntimeId = $blockTranslator->internalIdToNetworkId($stateId = $state->getStateId());
			$this->runtimeIdToStateId[$protocol][$blockRuntimeId] = $stateId;
		}
	}

	public function getStateIdFromRuntimeId(int $blockRuntimeId) : int
	{
		return $this->runtimeIdToStateId[$this->protocol][$blockRuntimeId] ?? 0;
	}

}
