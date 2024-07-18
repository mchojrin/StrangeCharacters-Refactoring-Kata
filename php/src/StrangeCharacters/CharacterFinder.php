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