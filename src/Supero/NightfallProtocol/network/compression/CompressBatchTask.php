<?php

declare(strict_types=1);

namespace Supero\NightfallProtocol\network\compression;

use pocketmine\network\mcpe\compression\CompressBatchPromise;
use pocketmine\network\mcpe\compression\Compressor;
use pocketmine\scheduler\AsyncTask;
use pocketmine\thread\NonThreadSafeValue;
use Supero\NightfallProtocol\network\CustomProtocolInfo;
use function chr;

class CompressBatchTask extends AsyncTask{

	private const TLS_KEY_PROMISE = "promise";

	/** @phpstan-var NonThreadSafeValue<Compressor> */
	private NonThreadSafeValue $compressor;

	public function __construct(
		private string $data,
		CompressBatchPromise $promise,
		Compressor $compressor,
		private int $protocolId
	){
		$this->compressor = new NonThreadSafeValue($compressor);
		$this->storeLocal(self::TLS_KEY_PROMISE, $promise);
	}

	public function onRun() : void{
		$compressor = $this->compressor->deserialize();
		$protocolAddition = $this->protocolId >= CustomProtocolInfo::PROTOCOL_1_20_60 ? chr($compressor->getNetworkId()) : '';
		$this->setResult($protocolAddition . $compressor->compress($this->data));
	}

	public function onCompletion() : void{
		/** @var CompressBatchPromise $promise */
		$promise = $this->fetchLocal(self::TLS_KEY_PROMISE);
		$promise->resolve($this->getResult());
	}
}
