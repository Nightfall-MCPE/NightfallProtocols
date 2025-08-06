<?php

declare(strict_types=1);

namespace Supero\NightfallProtocol\network\handlers\static;

use pocketmine\network\mcpe\handler\InGamePacketHandler;
use pocketmine\network\mcpe\handler\ItemStackRequestProcessException;
use pocketmine\network\mcpe\protocol\ItemStackRequestPacket;
use pocketmine\network\mcpe\protocol\types\inventory\stackresponse\ItemStackResponse;
use pocketmine\network\PacketHandlingException;
use pocketmine\utils\Utils;
use Supero\NightfallProtocol\network\handlers\CustomItemStackRequestExecutor;
use Supero\NightfallProtocol\network\handlers\CustomItemStackResponseBuilder;
use Supero\NightfallProtocol\network\packets\ItemStackResponsePacket;
use Supero\NightfallProtocol\network\packets\types\inventory\CustomItemStackRequest;
use Supero\NightfallProtocol\utils\ReflectionUtils;
use function count;
use function implode;

class CustomInGamePacketHandler extends InGamePacketHandler
{

	public function handleItemStackRequest(ItemStackRequestPacket $packet) : bool{
		$responses = [];
		if(count($packet->getRequests()) > 80){
			throw new PacketHandlingException("Too many requests in ItemStackRequestPacket");
		}
		/** @var CustomItemStackRequest $request */
		foreach($packet->getRequests() as $request){
			$responses[] = $this->handleSingleCustomItemStackRequest($request)?->build() ?? new ItemStackResponse(ItemStackResponse::RESULT_ERROR, $request->getRequestId());
		}

		ReflectionUtils::getProperty(InGamePacketHandler::class, $this,"session")->sendDataPacket(ItemStackResponsePacket::createPacket($responses));

		return true;
	}

	private function handleSingleCustomItemStackRequest(CustomItemStackRequest $request) : ?CustomItemStackResponseBuilder{
		if(count($request->getActions()) > 60){
			//recipe book auto crafting can affect all slots of the inventory when consuming inputs or producing outputs
			//this means there could be as many as 50 CraftingConsumeInput actions or Place (taking the result) actions
			//in a single request (there are certain ways items can be arranged which will result in the same stack
			//being taken from multiple times, but this is behaviour with a calculable limit)
			//this means there SHOULD be AT MOST 53 actions in a single request, but 60 is a nice round number.
			//n64Stacks = ?
			//n1Stacks = 45 - n64Stacks
			//nItemsRequiredFor1Craft = 9
			//nResults = floor((n1Stacks + (n64Stacks * 64)) / nItemsRequiredFor1Craft)
			//nTakeActionsTotal = floor(64 / nResults) + max(1, 64 % nResults) + ((nResults * nItemsRequiredFor1Craft) - (n64Stacks * 64))
			throw new PacketHandlingException("Too many actions in ItemStackRequest");
		}

		$executor = new CustomItemStackRequestExecutor(ReflectionUtils::getProperty(InGamePacketHandler::class, $this, "player"), ReflectionUtils::getProperty(InGamePacketHandler::class, $this, "inventoryManager"), $request);
		try{
			$transaction = $executor->generateInventoryTransaction();
			if($transaction !== null){
				$result = ReflectionUtils::invoke(InGamePacketHandler::class, $this, "executeInventoryTransaction", $transaction, $request->getRequestId());
			}else{
				$result = true; //predictions only, just send responses
			}
		}catch(ItemStackRequestProcessException $e){
			$result = false;
			ReflectionUtils::getProperty(InGamePacketHandler::class, $this, "session")->getLogger()->info("ItemStackRequest #" . $request->getRequestId() . " failed: " . $e->getMessage());
			ReflectionUtils::getProperty(InGamePacketHandler::class, $this, "session")->getLogger()->info(implode("\n", Utils::printableExceptionInfo($e)));
			ReflectionUtils::getProperty(InGamePacketHandler::class, $this, "inventoryManager")->requestSyncAll();
		}

		return $result ? $executor->getItemStackResponseBuilder() : null;
	}

}
