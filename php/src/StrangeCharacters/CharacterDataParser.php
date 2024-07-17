<?php

declare(strict_types=1);

namespace StrangeCharacters;

use stdClass;

class CharacterDataParser
{
    private static array $allCharacters = [];
    private static CharacterFinder $characterFinder;

    public static function createCharactersFromFileAndCreateCharacterFinder(?string $filename): void
    {
        self::$allCharacters = self::createCharactersFromArrayAndFindTheirNemesisAndAddTheirFamily(self::getAllCharactersDataFrom($filename ?? ROOT_DIR . DIRECTORY_SEPARATOR . "resources" . DIRECTORY_SEPARATOR . "strange_characters.json"));
        self::$characterFinder = new CharacterFinder(self::$allCharacters);
    }

    private static function createCharactersFromArrayAndFindTheirNemesisAndAddTheirFamily(array $allCharactersData): array
    {
        $allCharacters = self::buildCharactersFrom($allCharactersData);

        self::completeCharacters($allCharactersData, $allCharacters);

        return $allCharacters;
    }

    private static function findCharacter(string $name, array $result): ?Character
    {
        return current(array_filter($result, function (Character $character) use ($name) {
            return $character->firstName === $name;
        }));
    }

    public static function evaluatePath(string $path): ?Character
    {
        $character = null;

        $hasFamilyName = false;
        $characterName = "";
        $familyName = "";
        $tempPathWithoutCurlyBraces = "";
        $curlyBraces = "";
        $structureList = explode("/", $path);
        for ($i = count($structureList) - 1; $i >= 0; $i--) {
            if (empty($structureList[$i]))
                continue;
            $localName = "";
            $familyLocalNameList = explode(":", $structureList[$i]);
            if (count($familyLocalNameList) == 2) {
                if (!$hasFamilyName) {
                    $hasFamilyName = true;
                }

                $familyName = $familyLocalNameList[0];
                $localName = $familyLocalNameList[1];
            } else if (count($familyLocalNameList) == 1) {
                $localName = $familyLocalNameList[0];
            }

            if ($i == count($structureList) - 1) {
                $characterName = $localName;
            }

            $localNameWithoutCurlyBraces = preg_replace("|\{[^{]*?}|", "", $localName);
            $pattern = "|(.*)\{([^{]*)}|";
            $matches = [];
            preg_match($pattern, $localName, $matches);
            if (count($matches) > 0) {
                $curlyBraces = $matches[2];
                $characterName = $matches[1];
            }

            $tempPathWithoutCurlyBraces = "/" . $localNameWithoutCurlyBraces . $tempPathWithoutCurlyBraces;
        }

        if (!$hasFamilyName) {
            $character = self::$characterFinder->findByFirstName($characterName);
            if ($curlyBraces == "Nemesis") {
                return $character->getNemesis();
            }

            return $character;
        }

        $filteredCharacters = self::$characterFinder->findFamilyByLastName($familyName);
        if (!empty($filteredCharacters)) {
            $names = array_filter(explode("/", $tempPathWithoutCurlyBraces), fn(string $name) => !empty($name));
            if (count($names) == 2) {
                $firstName = next($names);
                $candidates = array_filter($filteredCharacters, fn(Character $c) => ($c->firstName == $firstName));
                $character = !empty($candidates) ? current($candidates) : null;
            }
            if ($character != null && $curlyBraces == "Nemesis") {

                return $character->getNemesis();
            }
        }

        return $character;
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
}