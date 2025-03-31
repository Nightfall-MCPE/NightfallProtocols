<?php

declare(strict_types=1);

namespace Supero\NightfallProtocol\utils;

use function array_combine;
use function array_keys;
use function array_map;

class Utils{

	/**
	 * Generator which forces array keys to string during iteration.
	 * This is necessary because PHP has an anti-feature where it casts numeric string keys to integers, leading to
	 * various crashes.
	 *
	 * @phpstan-template TKeyType of string
	 * @phpstan-template TValueType
	 * @phpstan-param array<TKeyType, TValueType> $array
	 * @phpstan-return \Generator<TKeyType, TValueType, void, void>
	 */
	public static function stringifyKeys(array $array) : \Generator{
		foreach($array as $key => $value){ // @phpstan-ignore-line - this is where we fix the stupid bullshit with array keys :)
			yield (string) $key => $value;
		}
	}

	/**
	 *  Array map implementation that preserves keys.
	 *
	 *  @phpstan-template TKeyType
	 *  @phpstan-template TValueType
	 *  @phpstan-template TResultType
	 *  @phpstan-param callable(TValueType) : TResultType $callback
	 *  @phpstan-param array<TKeyType, TValueType> $array
	 *  @phpstan-return array<TKeyType, TResultType>
	 */
	public static function arrayMapPreserveKeys(callable $callback, array $array) : array{
		return array_combine(array_keys($array), array_map($callback, $array));
	}
}
