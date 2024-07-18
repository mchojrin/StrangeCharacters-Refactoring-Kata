<?php

declare(strict_types=1);

namespace StrangeCharacters;

use stdClass;

class CharacterDataParser
{
    const string DEFAULT_INPUT_FILENAME = ROOT_DIR . DIRECTORY_SEPARATOR . "resources" . DIRECTORY_SEPARATOR . "strange_characters.json";
    private static CharacterFinder $characterFinder;

    private readonly CharacterFinder $finder;

    public function __construct(readonly ?string $filename = self::DEFAULT_INPUT_FILENAME)
    {
        $charactersData = $this->readFrom($filename);
        $characters = $this->buildFrom($charactersData);
        $this->finder = new CharacterFinder($characters);
        $this->complete($charactersData, $characters);
    }

    public static function initWithDataFrom(?string $filename): void
    {
        $charactersData = self::readCharactersDataFrom($filename ?? self::DEFAULT_INPUT_FILENAME);
        $characters = self::buildCharactersFrom($charactersData);
        self::$characterFinder = new CharacterFinder($characters);
        self::completeCharacters($charactersData, $characters);
    }

    public function findByPath(string $path): ?Character
    {
        return $this->finder->findByPath($path);
    }

    public static function findCharacterBy(string $path): ?Character
    {
        return self::$characterFinder->findByPath($path);
    }

    private function buildFrom(array $data): array
    {
        return self::buildCharactersFrom($data);
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
        self::addNemesis($characterData);
        self::addFamily($characterData, $characters);
    }

    /**
     * @param stdClass $characterData
     * @return void
     */
    private static function addNemesis(stdClass $characterData): void
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
        $child = self::$characterFinder->findChild($characters, $childName);

        if ($child != null) {
            $character->addChild($child);
        }
    }

    private function complete(array $allCharactersData, array $allCharacters): void
    {
        self::completeCharacters($allCharactersData, $allCharacters);
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

    private function readFrom(string $filename): array
    {
        return self::readCharactersDataFrom($filename);
    }
    /**
     * @param string $filename
     * @return array
     */
    private static function readCharactersDataFrom(string $filename): array
    {
        return json_decode(file_get_contents($filename), false);
    }

}