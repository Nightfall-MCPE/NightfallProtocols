<?php

declare(strict_types=1);

namespace Supero\NightfallProtocol\network\packets\serializer;

use pocketmine\network\mcpe\protocol\serializer\BitSet;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use function array_pad;
use function array_slice;
use function array_values;
use function count;
use function intdiv;
use const PHP_INT_SIZE;

class CustomBitSet extends BitSet
{
	private const INT_BITS = PHP_INT_SIZE * 8;
	private const SHIFT = 7;

	/**
	 * @param int[] $parts
	 */
	public function __construct(
		private readonly int $length,
		private array $parts = []
	){
		$expectedPartsCount = self::getExpectedPartsCount($length);
		$partsCount = count($parts);

		if($partsCount > $expectedPartsCount){
			throw new \InvalidArgumentException("Too many parts");
		}elseif($partsCount < $expectedPartsCount){
			$parts = array_pad($parts, $expectedPartsCount, 0);
		}

		$this->parts = array_values($parts);
	}

	public function get(int $index) : bool{
		[$partIndex, $bitIndex] = $this->getPartIndex($index);

		return ($this->parts[$partIndex] & (1 << $bitIndex)) !== 0;
	}

	public function set(int $index, bool $value) : void{
		[$partIndex, $bitIndex] = $this->getPartIndex($index);

		if($value){
			$this->parts[$partIndex] |= 1 << $bitIndex;
		}else{
			$this->parts[$partIndex] &= ~(1 << $bitIndex);
		}
	}

	/**
	 * Returns the part index and the bit index within that part for a given bit index.
	 *
	 * @return array{int, int}
	 */
	private function getPartIndex(int $index) : array{
		if($index < 0 || $index >= $this->length){
			throw new \InvalidArgumentException("Index out of bounds");
		}

		return [
			intdiv($index, self::INT_BITS),
			$index % self::INT_BITS
		];
	}

	/**
	 * @internal
	 */
	public function getPartsCount() : int{
		return count($this->parts);
	}

	/**
	 * @return int[]
	 */
	public function getParts() : array{
		return $this->parts;
	}

	private static function getExpectedPartsCount(int $length) : int{
		return intdiv($length + self::INT_BITS - 1, self::INT_BITS);
	}

	public static function read(PacketSerializer $in, int $length) : self{
		$result = [0];

		$currentIndex = 0;
		$currentShift = 0;

		for($i = 0; $i < $length; $i += self::SHIFT){
			$b = $in->getByte();
			$bits = $b & 0x7f;

			$result[$currentIndex] |= $bits << $currentShift; //extra bits will be discarded
			$nextShift = $currentShift + self::SHIFT;
			if($nextShift >= self::INT_BITS){
				$nextShift -= self::INT_BITS;
				$rightShift = self::SHIFT - $nextShift;
				$result[++$currentIndex] = $bits >> $rightShift;
			}
			$currentShift = $nextShift;

			if(($b & 0x80) === 0){
				return new self($length, array_slice($result, 0, self::getExpectedPartsCount($length)));
			}
		}

		return new self($length, array_slice($result, 0, self::getExpectedPartsCount($length)));
	}

	public function write(PacketSerializer $out, int $length = null) : void{
		$parts = $this->parts;
		$length ??= $this->length;

		if($length > $this->length){
			throw new \InvalidArgumentException("Cannot write more bits than the CustomBitSet contains");
		}

		$currentIndex = 0;
		$currentShift = 0;

		for($i = 0; $i < $length; $i += self::SHIFT){
			$bits = $parts[$currentIndex] >> $currentShift;
			$nextShift = $currentShift + self::SHIFT;
			if($nextShift >= self::INT_BITS){
				$nextShift -= self::INT_BITS;
				$bits |= $parts[++$currentIndex] << (self::SHIFT - $nextShift);
			}
			$currentShift = $nextShift;

			$last = $i + self::SHIFT >= $length;
			$bits |= $last ? 0 : 0x80;

			$out->putByte($bits);
			if($last){
				break;
			}
		}
	}

	public function getLength() : int{
		return $this->length;
	}
}
