<?php

declare(strict_types=1);

namespace Supero\NightfallProtocol\network\static\convert;

use pocketmine\network\mcpe\protocol\serializer\ItemTypeDictionary;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\utils\Utils;
use pocketmine\world\format\io\GlobalItemDataHandlers;
use function is_array;

class CustomItemIdMetaDowngrader
{

	/**
	 * @var string[] $renamedIds
	 * @phpstan-var array<string, string> $renamedIds
	 */
	private array $renamedIds = [];
	/**
	 * @var string[][] $remappedMetas
	 * @phpstan-var array<string, array{string, int}> $remappedMetas
	 */
	private array $remappedMetas = [];

	public function __construct(ItemTypeDictionary $dictionary, int $schemaId){
		$upgrader = GlobalItemDataHandlers::getUpgrader()->getIdMetaUpgrader();

		$networkIds = [];
		foreach($upgrader->getSchemas() as $id => $schema){
			if($id <= $schemaId){
				continue;
			}

			foreach(Utils::stringifyKeys($schema->getRenamedIds()) as $oldId => $newStringId){
				if(isset($networkIds[$oldId])){
					$networkIds[$newStringId] = $networkIds[$oldId];
				}else{
					try{
						$dictionary->fromStringId($oldId);
						$networkIds[$newStringId] = $oldId;
					}catch(\InvalidArgumentException $e){
						//ignore
					}
				}
			}

			foreach(Utils::stringifyKeys($schema->getRemappedMetas()) as $oldId => $metaToNewId){
				if(isset($networkIds[$oldId])){
					foreach($metaToNewId as $oldMeta => $newStringId){
						if(is_array($networkIds[$oldId])){
							throw new AssumptionFailedError("Can't flatten IDs twice");
						}else{
							$networkIds[$newStringId] = [$networkIds[$oldId], $oldMeta];
						}
					}
				}else{
					try{
						$dictionary->fromStringId($oldId);
						foreach($metaToNewId as $oldMeta => $newStringId){
							$networkIds[$newStringId] = [$oldId, $oldMeta];
						}
					}catch(\InvalidArgumentException $e){
						//ignore
					}
				}
			}
		}

		foreach(Utils::stringifyKeys($networkIds) as $newStringId => $oldId){
			if(is_array($oldId)){
				$this->remappedMetas[$newStringId] = $oldId;
			}else{
				$this->renamedIds[$newStringId] = $oldId;
			}
		}
	}

	/**
	 * @phpstan-return array{string, int}
	 */
	public function downgrade(string $id, int $meta) : array{
		$newId = $id;
		$newMeta = $meta;

		if(isset($this->remappedMetas[$newId])){
			[$newId, $newMeta] = $this->remappedMetas[$newId];
		}elseif(isset($this->renamedIds[$newId])){
			$newId = $this->renamedIds[$newId];
		}

		return [$newId, $newMeta];
	}
}
