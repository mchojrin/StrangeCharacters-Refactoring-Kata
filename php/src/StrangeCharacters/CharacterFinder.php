<?php

declare(strict_types=1);

namespace StrangeCharacters;

use function array_filter;
use function current;

readonly class CharacterFinder
{
    /**
     * @param array<Character> $allCharacters
     */
    public function __construct(private array $allCharacters)
    {
    }

    public function find(string $name): ?Character {
        return current(array_filter($this->allCharacters, function (Character $character) use ($name) {
            return $character->firstName === $name;
        }));
    }

    /**
     * @param string $firstName
     * @param array $familyMembers
     * @return array
     */
    public static function findRelativesNamed(string $firstName, array $familyMembers): array
    {
        return array_filter($familyMembers, fn(Character $c) => ($c->firstName == $firstName));
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