<?php

declare(strict_types=1);

namespace StrangeCharacters;

use function array_filter;
use function current;

readonly class CharacterFinder
{
    public const string PATH_SEPARATOR = "/";

    /**
     * @param array<Character> $allCharacters
     */
    public function __construct(private array $allCharacters)
    {
    }

    public function find(string $characterName): ?Character
    {
        $found = array_filter($this->allCharacters, fn(Character $c) => $c->firstName == $characterName);

        return !empty($found) ? current($found) : null;
    }

    public function findByPath(string $path): ?Character
    {
        return $this->findCharacterOrRelated($this->buildSearchCriteriaFrom($path));
    }

    /**
     * @param CharacterSearchCriteria $criteria
     * @return Character|null
     */
    public function findCharacterOrRelated(CharacterSearchCriteria $criteria): ?Character
    {
        $mainCharacter = $this->findMainCharacter($criteria);

        if (empty($mainCharacter)) {
            return null;
        }

        return $criteria->relation == "Nemesis" ? $mainCharacter->getNemesis() : $mainCharacter;
    }

    /**
     * @param string $lastName
     * @param string $path
     * @return Character|null
     */
    public function findByLastName(string $lastName, string $path): ?Character
    {
        $family = $this->findFamily($lastName);
        if (!empty($family)) {
            $names = $this->getNamesIn($path);
            if (count($names) == 2) {
                $character = $this->findInGroup(next($names), $family);
            }
        }

        return !empty($character) ? $character : null;
    }

    public function findInGroup(string $name, array $group): ?Character
    {
        $characters = array_filter($group, fn(Character $c) => ($c->firstName == $name));

        return !empty($characters) ? current($characters) : null;
    }

    public function findParent(string $firstName): ?Character
    {
        $child = $this->find($firstName);
        if ($child == null) {
            return null;
        }

        return current($child->getParents()) ?? null;
    }

    public function findFamily(?string $lastName): array
    {
        $family = array_filter($this->allCharacters, fn(Character $c) => $c->lastName == $lastName);

        return $lastName == null ? array_filter($family, fn(Character $c) => !$c->isMonster) : $family;
    }

    public function findMonsters(): array
    {
        return array_filter($this->allCharacters, fn(Character $c) => $c->isMonster);
    }

    public function findFamilyByCharacter(string $firstName): array
    {
        $character = $this->find($firstName);
        if ($character == null) {
            return [];
        }

        return $character->getParents() + $character->getChildren() + $character->getSiblings();
    }

    /**
     * @param CharacterSearchCriteria $criteria
     * @return Character|null
     */
    private function findMainCharacter(CharacterSearchCriteria $criteria): ?Character
    {
        if (!empty($criteria->familyName)) {

            return $this->findByLastName($criteria->familyName, $criteria->pathWithoutRelations);
        } else {

            return $this->find($criteria->characterName);
        }
    }

    /**
     * @param string $path
     * @return CharacterSearchCriteria
     */
    private function buildSearchCriteriaFrom(string $path): CharacterSearchCriteria
    {
        $characterName = "";
        $tempPathWithoutModifier = "";
        $persons = $this->getNamesFrom($path);

        for ($i = count($persons) - 1; $i >= 0; $i--) {
            [$familyName, $firstName] = $this->separateNames($persons[$i]);

            if ($i == count($persons) - 1) {
                $relation = $this->getRelationFrom($firstName);
                $characterName = $this->extractPureNameFrom($firstName);
            }

            $tempPathWithoutModifier = CharacterFinder::PATH_SEPARATOR . $characterName . $tempPathWithoutModifier;
        }

        return new CharacterSearchCriteria($characterName, $tempPathWithoutModifier, $relation, $familyName);
    }

    /**
     * @param string $names
     * @return string[]
     */
    private function separateNamesByType(string $names): array
    {
        return explode(CharacterDataParser::NAME_TYPE_SEPARATOR, $names);
    }

    /**
     * @param string $names
     * @return array
     */
    private function separateNames(string $names): array
    {
        $currentPersonNames = $this->separateNamesByType($names);

        return count($currentPersonNames) == 2 ? $currentPersonNames : ["", $currentPersonNames[0]];
    }

    /**
     * @param string $localName
     * @return string
     */
    private function getRelationFrom(string $localName): string
    {
        $matches = [];

        return preg_match(CharacterDataParser::CURLY_BRACES_PATTERN, $localName, $matches) ? $matches[2] : "";
    }

    /**
     * @param string $localName
     * @return string
     */
    private function extractPureNameFrom(string $localName): string
    {
        return preg_replace("|\{[^{]*?}|", "", $localName);
    }

    /**
     * @param string $path
     * @return array
     */
    private function getNamesFrom(string $path): array
    {
        return array_values(
            array_filter(
                $this->separateNamesByPath($path)
            )
        );
    }

    /**
     * @param string $path
     * @return string[]
     */
    private function getNamesIn(string $path): array
    {
        return array_filter($this->separateNamesByPath($path));
    }

    /**
     * @param string $tempPathWithoutCurlyBraces
     * @return string[]
     */
    private function separateNamesByPath(string $tempPathWithoutCurlyBraces): array
    {
        return explode(self::PATH_SEPARATOR, $tempPathWithoutCurlyBraces);
    }
}