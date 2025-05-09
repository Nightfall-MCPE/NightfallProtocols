<?php

declare(strict_types=1);

namespace Supero\NightfallProtocol\network\static\data\bedrock;

use pocketmine\crafting\MetaWildcardRecipeIngredient;
use pocketmine\crafting\RecipeIngredient;
use pocketmine\crafting\ShapedRecipe;
use pocketmine\crafting\ShapelessRecipe;
use pocketmine\crafting\TagWildcardRecipeIngredient;
use Supero\NightfallProtocol\utils\ProtocolSingletonTrait;
use Supero\NightfallProtocol\utils\ReflectionUtils;
use Supero\NightfallProtocol\utils\Utils;
use function array_key_first;
use function array_keys;
use function array_map;
use function count;

/**
 * Tracks Minecraft Bedrock item tags, and the item IDs which belong to them
 *
 * @internal
 */
final class ItemTagDowngrader{
	use ProtocolSingletonTrait;

	private ItemTagToIdMap $map;
	/** @phpstan-var array<string, list<string>> */
	private array $tagToIdsMap;

	public function __construct(
		protected readonly int $protocolId,
	){
		$latestMap = ItemTagToIdMap::getProtocolInstance();
		$this->map = ItemTagToIdMap::getProtocolInstance($protocolId);

		$this->tagToIdsMap = $latestMap->diff($this->map);
	}

	/**
	 * Returns true when the given tags are all present in the current version of the game.
	 *
	 * @phpstan-param list<string> $tags
	 */
	public function mapHasTags(array $tags) : bool {
		foreach ($tags as $tag){
			if (count($this->map->getIdsForTag($tag)) === 0){
				return false;
			}
		}

		return true;
	}

	/**
	 * Get all the ids that are not included in the wildcard ingredients of the current version,
	 * but are included in the wildcard ingredients of the last version.
	 *
	 * @phpstan-param array<RecipeIngredient> $ingredients
	 * @phpstan-return array<string, list<string>>
	 */
	private function getNotIncludedWildcardIds(array $ingredients) : array {
		$notIncludedWildcardIngredients = [];

		foreach ($ingredients as $ingredient){
			if($ingredient instanceof TagWildcardRecipeIngredient){
				$tagName = $ingredient->getTagName();

				if (!isset($notIncludedWildcardIngredients[$tagName]) && isset($this->tagToIdsMap[$tagName])){
					$notIncludedWildcardIngredients[$tagName] = $this->tagToIdsMap[$tagName];
				}
			}
		}

		return $notIncludedWildcardIngredients;
	}

	/**
	 * Generates a cartesian product of the possible ingredient combinations for a given wildcard to ids map.
	 *
	 * @param array<string, list<string>> $wildcardToIdsMap
	 * @phpstan-return list<array<string, string>>
	 */
	public function getIngredientCombinations(array $wildcardToIdsMap) : array {
		if(count($wildcardToIdsMap) === 0){
			return [];
		}

		if(count($wildcardToIdsMap) === 1){
			$tagName = array_key_first($wildcardToIdsMap);
			return array_map(fn(string $id) => [$tagName => $id], $wildcardToIdsMap[$tagName]);
		}

		$ingredientCombinations = [[]];

		foreach(Utils::stringifyKeys($wildcardToIdsMap) as $tagName => $wildcardIds){
			$appendedIngredientCombinations = [];

			foreach ($wildcardIds as $wildcardId){
				foreach ($ingredientCombinations as $ingredientCombination){
					$appendedIngredientCombinations[] = $ingredientCombination + [$tagName => $wildcardId];
				}
			}

			$ingredientCombinations = $appendedIngredientCombinations;
		}

		return $ingredientCombinations;
	}

	/**
	 * Downgrades a single ingredient using a given wildcard to ids map.
	 *
	 * @param array<string, string> $tagsToIdMap
	 */
	private function getDowngradedIngredient(RecipeIngredient $ingredient, array $tagsToIdMap) : RecipeIngredient {
		if ($ingredient instanceof TagWildcardRecipeIngredient){
			$tagName = $ingredient->getTagName();

			if (isset($tagsToIdMap[$tagName])){
				return new MetaWildcardRecipeIngredient($tagsToIdMap[$tagName]);
			}
		}

		return $ingredient;
	}

	/**
	 * Downgrades a single shapeless recipe
	 *
	 * @return list<ShapelessRecipe>
	 */
	public function downgradeShapelessRecipe(ShapelessRecipe $recipe) : array {
		$notIncludedWildcardIngredients = $this->getNotIncludedWildcardIds($ingredients = $recipe->getIngredientList());
		$downgradedRecipes = [];

		if($this->mapHasTags(array_keys($notIncludedWildcardIngredients))){
			$downgradedRecipes[] = $recipe;
		}

		foreach($this->getIngredientCombinations($notIncludedWildcardIngredients) as $ingredientCombination){
			$downgradedIngredients = array_map(fn(RecipeIngredient $ingredient) => $this->getDowngradedIngredient($ingredient, $ingredientCombination), $ingredients);
			$downgradedRecipes[] = new ShapelessRecipe($downgradedIngredients, $recipe->getResults(), $recipe->getType());
		}

		return $downgradedRecipes;
	}

	/**
	 * Downgrades a single shaped recipe
	 *
	 * @return list<ShapedRecipe>
	 */
	public function downgradeShapedRecipe(ShapedRecipe $recipe) : array {
		$notIncludedWildcardIngredients = $this->getNotIncludedWildcardIds($ingredients = ReflectionUtils::getProperty($recipe::class, $recipe, "ingredientList"));
		$downgradedRecipes = [];

		if($this->mapHasTags(array_keys($notIncludedWildcardIngredients))){
			$downgradedRecipes[] = $recipe;
		}

		foreach($this->getIngredientCombinations($notIncludedWildcardIngredients) as $ingredientCombination){
			$downgradedIngredients = Utils::arrayMapPreserveKeys(fn(RecipeIngredient $ingredient) => $this->getDowngradedIngredient($ingredient, $ingredientCombination), $ingredients);
			$downgradedRecipes[] = new ShapedRecipe($recipe->getShape(), $downgradedIngredients, $recipe->getResults());
		}

		return $downgradedRecipes;
	}
}
