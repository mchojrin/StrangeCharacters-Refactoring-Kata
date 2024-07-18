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

    private static function findCharacter(string $name, array $characters): ?Character
    {
        return current(array_filter($characters, function (Character $character) use ($name) {
            return $character->firstName === $name;
        }));
    }

    /**
     * @param array $data
     * @return array
     */
    protected static function buildCharactersFrom(array $data): array
    {
        return array_map(fn(stdClass $characterData) => Character::withFirstAndLastNameAndMonsterStatus($characterData->FirstName, $characterData->LastName, $characterData->IsMonster), $data);
    }

    /**
     * @param stdClass $characterData
     * @param array $characters
     * @return void
     */
    protected static function completeCharacter(stdClass $characterData, array $characters): void
    {
        self::addNemesis($characterData, $characters);
        self::addFamily($characterData, $characters);
    }

    /**
     * @param stdClass $characterData
     * @param array $characters
     * @return void
     */
    protected static function addNemesis(stdClass $characterData, array $characters): void
    {
        if (!empty($characterData->Nemesis)) {
            $character = self::findCharacter($characterData->FirstName, $characters);
            $character->setNemesis(self::findCharacter($characterData->Nemesis, $characters));
        }
    }

    /**
     * @param stdClass $characterData
     * @param array $characters
     * @return void
     */
    protected static function addFamily(stdClass $characterData, array $characters): void
    {
        if (!empty($characterData->Children)) {
            self::addChildren(self::findCharacter($characterData->FirstName, $characters), $characterData, $characters);
        }
    }

    /**
     * @param Character $character
     * @param stdClass $characterData
     * @param array $characters
     * @return void
     */
    protected static function addChildren(Character $character, stdClass $characterData, array $characters): void
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
    protected static function addChild(Character $character, string $childName, array $characters): void
    {
        $child = self::findCharacter($childName, $characters);
        if ($child != null)
            $character->addChild($child);
    }

    /**
     * @param array $allCharactersData
     * @param array $allCharacters
     * @return void
     */
    protected static function completeCharacters(array $allCharactersData, array $allCharacters): void
    {
        foreach ($allCharactersData as $characterData) {
            self::completeCharacter($characterData, $allCharacters);
        }
    }

    /**
     * @param string $filename
     * @return mixed
     */
    protected static function getAllCharactersDataFrom(string $filename): mixed
    {
        return json_decode(file_get_contents($filename), false);
    }

    /**
     * @param string $localName
     * @return string
     */
    protected static function getRelationFrom(string $localName): string
    {
        $matches = [];

        return preg_match(self::CURLY_BRACES_PATTERN, $localName, $matches) ? $matches[2] : "";
    }

    /**
     * @param string $localName
     * @return string
     */
    protected static function extractPureNameFrom(string $localName): string
    {
        return preg_replace("|\{[^{]*?}|", "", $localName);
    }

    /**
     * @param string $firstName
     * @param array $familyMembers
     * @return array
     */
    protected static function findRelativesNamed(string $firstName, array $familyMembers): array
    {
        return array_filter($familyMembers, fn(Character $c) => ($c->firstName == $firstName));
    }

    /**
     * @param string $tempPathWithoutCurlyBraces
     * @return string[]
     */
    protected static function separatePersonsByPath(string $tempPathWithoutCurlyBraces): array
    {
        return explode(self::PATH_SEPARATOR, $tempPathWithoutCurlyBraces);
    }

    /**
     * @param string $names
     * @return string[]
     */
    protected static function separateNamesByType(string $names): array
    {
        return explode(self::NAME_TYPE_SEPARATOR, $names);
    }

    /**
     * @param string $tempPathWithoutCurlyBraces
     * @return string[]
     */
    protected static function getPersonsIn(string $tempPathWithoutCurlyBraces): array
    {
        return array_filter(self::separatePersonsByPath($tempPathWithoutCurlyBraces));
    }

    /**
     * @param string $names
     * @return array
     */
    protected static function separateNames(string $names): array
    {
        $currentPersonNames = self::separateNamesByType($names);

        return count($currentPersonNames) == 2 ? $currentPersonNames : ["", $currentPersonNames[0]];
    }

    /**
     * @param string $path
     * @return array
     */
    protected static function getPersonsFrom(string $path): array
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
    protected static function findCharacterOrRelated(CharacterSearchCriteria $criteria): ?Character
    {
        if ($criteria->hasFamilyName) {
            $familyMembers = self::$characterFinder->findFamilyByLastName($criteria->familyName);
            if (!empty($familyMembers)) {
                $personsWithoutCurlyBraces = self::getPersonsIn($criteria->tempPathWithoutModifier);
                if (count($personsWithoutCurlyBraces) == 2) {
                    $relativesNamedFirstName = self::findRelativesNamed(next($personsWithoutCurlyBraces), $familyMembers);
                    $character = !empty($relativesNamedFirstName) ? current($relativesNamedFirstName) : null;
                }
            }
        } else {
            $character = self::$characterFinder->findByFirstName($criteria->characterName);
        }

        if (!empty($character)) {

            return $criteria->relation == "Nemesis" ? $character->getNemesis() : $character;
        }

        return null;
    }

    /**
     * @param string $path
     * @return CharacterSearchCriteria
     */
    protected static function buildSearchCriteriaFrom(string $path): CharacterSearchCriteria
    {
        $hasFamilyName = false;
        $characterName = "";
        $tempPathWithoutModifier = "";
        $persons = self::getPersonsFrom($path);

        for ($i = count($persons) - 1; $i >= 0; $i--) {
            [$familyName, $firstName] = self::separateNames($persons[$i]);

            $hasFamilyName = $hasFamilyName || !empty($familyName);

            if ($i == count($persons) - 1) {
                $relation = self::getRelationFrom($firstName);
                $characterName = self::extractPureNameFrom($firstName);
            }

            $tempPathWithoutModifier = self::PATH_SEPARATOR . $characterName . $tempPathWithoutModifier;
        }

        $searchCriteria = new CharacterSearchCriteria($characterName, $tempPathWithoutModifier, $relation, $familyName);

        return $searchCriteria;
    }
}