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

    /**
     * @param string $path
     * @return CharacterSearchCriteria
     */
    public function buildSearchCriteriaFrom(string $path): CharacterSearchCriteria
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
    public function separateNamesByType(string $names): array
    {
        return explode(CharacterDataParser::NAME_TYPE_SEPARATOR, $names);
    }

    /**
     * @param string $names
     * @return array
     */
    public function separateNames(string $names): array
    {
        $currentPersonNames = $this->separateNamesByType($names);

        return count($currentPersonNames) == 2 ? $currentPersonNames : ["", $currentPersonNames[0]];
    }

    /**
     * @param string $localName
     * @return string
     */
    public function getRelationFrom(string $localName): string
    {
        $matches = [];

        return preg_match(CharacterDataParser::CURLY_BRACES_PATTERN, $localName, $matches) ? $matches[2] : "";
    }

    /**
     * @param string $localName
     * @return string
     */
    public function extractPureNameFrom(string $localName): string
    {
        return preg_replace("|\{[^{]*?}|", "", $localName);
    }

    /**
     * @param string $path
     * @return array
     */
    public function getNamesFrom(string $path): array
    {
        return array_values(
            array_filter(
                $this->separateNamesByPath($path)
            )
        );
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
     * @param CharacterSearchCriteria $criteria
     * @return Character|null
     */
    public function findMainCharacter(CharacterSearchCriteria $criteria): ?Character
    {
        if (!empty($criteria->familyName)) {

            return $this->findFamilyMemberByName($criteria->familyName, $criteria->pathWithoutRelations);
        } else {

            return $this->findByFirstName($criteria->characterName);
        }
    }

    /**
     * @param string $lastName
     * @param string $path
     * @return Character|null
     */
    public function findFamilyMemberByName(string $lastName, string $path): ?Character
    {
        $family = $this->findFamilyByLastName($lastName);
        if (!empty($family)) {
            $names = $this->getNamesIn($path);
            if (count($names) == 2) {
                $character = $this->findInGroup(next($names), $family);
            }
        }

        return !empty($character) ? $character : null;
    }

    /**
     * @param string $path
     * @return string[]
     */
    public function getNamesIn(string $path): array
    {
        return array_filter($this->separateNamesByPath($path));
    }

    /**
     * @param string $tempPathWithoutCurlyBraces
     * @return string[]
     */
    public function separateNamesByPath(string $tempPathWithoutCurlyBraces): array
    {
        return explode(self::PATH_SEPARATOR, $tempPathWithoutCurlyBraces);
    }

    public function find(string $name): ?Character {
        return current(array_filter($this->allCharacters, function (Character $character) use ($name) {
            return $character->firstName === $name;
        }));
    }

    public function findInGroup(string $name, array $group): ?Character
    {
        $characters = array_filter($group, fn(Character $c) => ($c->firstName == $name));

        return !empty($characters) ? current($characters) : null;
    }

    public function findByFirstName(string $characterName): ?Character
    {
        $found = array_filter($this->allCharacters, fn(Character $c) => $c->firstName == $characterName);

        return !empty($found) ? current($found) : null;
    }

    public function findParent(string $firstName): ?Character
    {
        $child = $this->findByFirstName($firstName);
        if ($child == null) {
            return null;
        }

        return current($child->getParents()) ?? null;
    }

    public function findFamilyByLastName(?string $lastName): array
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
        $person = $this->findByFirstName($firstName);
        if ($person == null) {
            return [];
        }

        return $person->getParents() + $person->getChildren() + $person->getSiblings();
    }
}