<?php

namespace Supero\NightfallProtocol\network\static;

use pocketmine\block\RuntimeBlockStateRegistry;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\utils\SingletonTrait;

class CustomRuntimeIDtoStateID
{
    use SingletonTrait;

    private array $runtimeIdToStateId = [];

    public function __construct()
    {
        $blockTranslator = TypeConverter::getInstance()->getBlockTranslator();
        foreach (RuntimeBlockStateRegistry::getInstance()->getAllKnownStates() as $state) {
            $blockRuntimeId = $blockTranslator->internalIdToNetworkId($stateId = $state->getStateId());
            $this->runtimeIdToStateId[$blockRuntimeId] = $stateId;
        }
    }

    public function getStateIdFromRuntimeId(int $blockRuntimeId): int
    {
        return $this->runtimeIdToStateId[$blockRuntimeId] ?? 0;
    }

}