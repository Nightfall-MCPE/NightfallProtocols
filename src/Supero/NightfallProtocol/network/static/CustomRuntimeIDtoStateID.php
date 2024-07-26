<?php

namespace Supero\NightfallProtocol\network\static;

use pocketmine\block\RuntimeBlockStateRegistry;
use Supero\NightfallProtocol\network\static\convert\CustomTypeConverter;
use Supero\NightfallProtocol\utils\ProtocolSingletonTrait;

class CustomRuntimeIDtoStateID
{
    use ProtocolSingletonTrait;

    private array $runtimeIdToStateId = [];

    public function __construct(int $protocol)
    {
        $this->setProtocolInstance($this, $protocol);
        $blockTranslator = CustomTypeConverter::getInstance()->getBlockTranslator();
        foreach (RuntimeBlockStateRegistry::getInstance()->getAllKnownStates() as $state) {
            $blockRuntimeId = $blockTranslator->internalIdToNetworkId($stateId = $state->getStateId());
            $this->runtimeIdToStateId[$protocol][$blockRuntimeId] = $stateId;
        }
    }

    public function getStateIdFromRuntimeId(int $blockRuntimeId): int
    {
        return $this->runtimeIdToStateId[$this->getProtocolId()][$blockRuntimeId] ?? 0;
    }

}