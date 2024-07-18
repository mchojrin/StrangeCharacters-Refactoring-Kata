<?php

declare(strict_types=1);

namespace StrangeCharacters;

use stdClass;
use function array_filter;
use function current;

class CharacterDataParser
{
    const string NAME_TYPE_SEPARATOR = ":";
    const string CURLY_BRACES_PATTERN = "|(.*)\{([^{]*)}|";
    const string DEFAULT_INPUT_FILENAME = ROOT_DIR . DIRECTORY_SEPARATOR . "resources" . DIRECTORY_SEPARATOR . "strange_characters.json";
    private static CharacterFinder $characterFinder;

    public static function initWithDataFrom(?string $filename): void
    {
        $charactersData = self::getAllCharactersDataFrom($filename ?? self::DEFAULT_INPUT_FILENAME);
        $characters = self::buildCharactersFrom($charactersData);
        self::$characterFinder = new CharacterFinder($characters);
        self::completeCharacters($charactersData, $characters);
    }

    public static function findCharacterByPath(string $path): ?Character
    {
        return self::findCharacterOrRelated(self::buildSearchCriteriaFrom($path));
    }

    private static function createCompleteCharactersFrom(array $allCharactersData): array
    {
        $allCharacters = self::buildCharactersFrom($allCharactersData);

        self::completeCharacters($allCharactersData, $allCharacters);

        return $allCharacters;
    }

    /**
     * @param array $data
     * @return array
     */
    private static function buildCharactersFrom(array $data): array
    {
        return array_map(fn(stdClass $characterData) => Character::withFirstAndLastNameAndMonsterStatus($characterData->FirstName, $characterData->LastName, $characterData->IsMonster), $data);
    }

    /**
     * @param stdClass $characterData
     * @param array $characters
     * @return void
     */
    private static function completeCharacter(stdClass $characterData, array $characters): void
    {
        self::addNemesis($characterData, $characters);
        self::addFamily($characterData, $characters);
    }

    /**
     * @param stdClass $characterData
     * @param array $characters
     * @return void
     */
    private static function addNemesis(stdClass $characterData, array $characters): void
    {
        if (!empty($characterData->Nemesis)) {
            $character = self::$characterFinder->find($characterData->FirstName);
            $character->setNemesis(self::$characterFinder->find($characterData->Nemesis));
        }
    }

    /**
     * @param stdClass $characterData
     * @param array $characters
     * @return void
     */
    private static function addFamily(stdClass $characterData, array $characters): void
    {
        if (!empty($characterData->Children)) {
            self::addChildren(self::$characterFinder->find($characterData->FirstName), $characterData, $characters);
        }
    }

    /**
     * @param Character $character
     * @param stdClass $characterData
     * @param array $characters
     * @return void
     */
    private static function addChildren(Character $character, stdClass $characterData, array $characters): void
    {
        foreach ($characterData->Children as $childName) {
            self::addChild($character, $childName, $characters);
        }
    }

    /**
     * @param Character $character
     * @param string $childName
     * @param array $characters
     * @return void
     */
    private static function addChild(Character $character, string $childName, array $characters): void
    {
        $name1 = $childName;
        $child = current(array_filter($characters, function (Character $character) use ($name1) {
            return $character->firstName === $name1;
        }));
        if ($child != null)
            $character->addChild($child);
    }

    /**
     * @param array $allCharactersData
     * @param array $allCharacters
     * @return void
     */
    private static function completeCharacters(array $allCharactersData, array $allCharacters): void
    {
        foreach ($allCharactersData as $characterData) {
            self::completeCharacter($characterData, $allCharacters);
        }
    }

    /**
     * @param string $filename
     * @return mixed
     */
    private static function getAllCharactersDataFrom(string $filename): mixed
    {
        return json_decode(file_get_contents($filename), false);
    }

    /**
     * @param string $localName
     * @return string
     */
    private static function getRelationFrom(string $localName): string
    {
        $matches = [];

        return preg_match(self::CURLY_BRACES_PATTERN, $localName, $matches) ? $matches[2] : "";
    }

    /**
     * @param string $localName
     * @return string
     */
    private static function extractPureNameFrom(string $localName): string
    {
        return preg_replace("|\{[^{]*?}|", "", $localName);
    }

    /**
     * @param string $names
     * @return string[]
     */
    private static function separateNamesByType(string $names): array
    {
        return explode(self::NAME_TYPE_SEPARATOR, $names);
    }

    /**
     * @param string $path
     * @return string[]
     */
    private static function getNamesIn(string $path): array
    {
        return array_filter(CharacterFinder::separateNamesByPath($path));
    }

    /**
     * @param string $names
     * @return array
     */
    private static function separateNames(string $names): array
    {
        $currentPersonNames = self::separateNamesByType($names);

        return count($currentPersonNames) == 2 ? $currentPersonNames : ["", $currentPersonNames[0]];
    }

    /**
     * @param string $path
     * @return array
     */
    private static function getNamesFrom(string $path): array
    {
        return array_values(
            array_filter(
                CharacterFinder::separateNamesByPath($path)
            )
        );
    }

    /**
     * @param CharacterSearchCriteria $criteria
     * @return Character|null
     */
    private static function findCharacterOrRelated(CharacterSearchCriteria $criteria): ?Character
    {
        $mainCharacter = self::findMainCharacter($criteria);

        if (empty($mainCharacter)) {
            return null;
        }

        return $criteria->relation == "Nemesis" ? $mainCharacter->getNemesis() : $mainCharacter;
    }

    /**
     * @param string $path
     * @return CharacterSearchCriteria
     */
    private static function buildSearchCriteriaFrom(string $path): CharacterSearchCriteria
    {
        $characterName = "";
        $tempPathWithoutModifier = "";
        $persons = self::getNamesFrom($path);

        for ($i = count($persons) - 1; $i >= 0; $i--) {
            [$familyName, $firstName] = self::separateNames($persons[$i]);

            if ($i == count($persons) - 1) {
                $relation = self::getRelationFrom($firstName);
                $characterName = self::extractPureNameFrom($firstName);
            }

            $tempPathWithoutModifier = CharacterFinder::PATH_SEPARATOR . $characterName . $tempPathWithoutModifier;
        }

        return new CharacterSearchCriteria($characterName, $tempPathWithoutModifier, $relation, $familyName);
    }

    /**
     * @param CharacterSearchCriteria $criteria
     * @return Character|null
     */
    private static function findMainCharacter(CharacterSearchCriteria $criteria): ?Character
    {
        if (!empty($criteria->familyName)) {

            return self::findFamilyMemberByName($criteria->familyName, $criteria->pathWithoutRelations);
        } else {

            return self::$characterFinder->findByFirstName($criteria->characterName);
        }
    }

    /**
     * @param string $lastName
     * @param string $path
     * @return Character|null
     */
    private static function findFamilyMemberByName(string $lastName, string $path): ?Character
    {
        $family = self::$characterFinder->findFamilyByLastName($lastName);
        if (!empty($family)) {
            $names = self::getNamesIn($path);
            if (count($names) == 2) {
                $character = self::$characterFinder->findInGroup(next($names), $family);
            }
        }

        return !empty($character) ? $character : null;
    }
}