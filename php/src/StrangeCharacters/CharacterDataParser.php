<?php

declare(strict_types=1);

namespace StrangeCharacters;

use stdClass;

class CharacterDataParser
{
    const string PATH_SEPARATOR = "/";
    const string NAME_TYPE_SEPARATOR = ":";
    const string CURLY_BRACES_PATTERN = "|(.*)\{([^{]*)}|";
    private static array $allCharacters = [];
    private static CharacterFinder $characterFinder;

    public static function initWithDataFrom(?string $filename): void
    {
        self::$allCharacters = self::createCompleteCharactersFrom(self::getAllCharactersDataFrom($filename ?? ROOT_DIR . DIRECTORY_SEPARATOR . "resources" . DIRECTORY_SEPARATOR . "strange_characters.json"));
        self::$characterFinder = new CharacterFinder(self::$allCharacters);
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
            $character = CharacterFinder::findCharacter($characterData->FirstName, $characters);
            $character->setNemesis(CharacterFinder::findCharacter($characterData->Nemesis, $characters));
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
            self::addChildren(CharacterFinder::findCharacter($characterData->FirstName, $characters), $characterData, $characters);
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
        $child = CharacterFinder::findCharacter($childName, $characters);
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
     * @param string $firstName
     * @param array $familyMembers
     * @return array
     */
    private static function findRelativesNamed(string $firstName, array $familyMembers): array
    {
        return array_filter($familyMembers, fn(Character $c) => ($c->firstName == $firstName));
    }

    /**
     * @param string $tempPathWithoutCurlyBraces
     * @return string[]
     */
    private static function separatePersonsByPath(string $tempPathWithoutCurlyBraces): array
    {
        return explode(self::PATH_SEPARATOR, $tempPathWithoutCurlyBraces);
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
    private static function getPersonsIn(string $path): array
    {
        return array_filter(self::separatePersonsByPath($path));
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
    private static function getPersonsFrom(string $path): array
    {
        return array_values(
            array_filter(
                self::separatePersonsByPath($path)
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
        $persons = self::getPersonsFrom($path);

        for ($i = count($persons) - 1; $i >= 0; $i--) {
            [$familyName, $firstName] = self::separateNames($persons[$i]);

            if ($i == count($persons) - 1) {
                $relation = self::getRelationFrom($firstName);
                $characterName = self::extractPureNameFrom($firstName);
            }

            $tempPathWithoutModifier = self::PATH_SEPARATOR . $characterName . $tempPathWithoutModifier;
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
        $familyMembers = self::$characterFinder->findFamilyByLastName($lastName);
        if (!empty($familyMembers)) {
            $relativeNames = self::getPersonsIn($path);
            if (count($relativeNames) == 2) {
                $relativesNamedFirstName = self::findRelativesNamed(next($relativeNames), $familyMembers);
            }
        }

        return !empty($relativesNamedFirstName) ? current($relativesNamedFirstName) : null;
    }
}