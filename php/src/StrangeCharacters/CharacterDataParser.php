<?php

declare(strict_types=1);

namespace StrangeCharacters;

use stdClass;

class CharacterDataParser
{
    const string DEFAULT_INPUT_FILENAME = ROOT_DIR . DIRECTORY_SEPARATOR . "resources" . DIRECTORY_SEPARATOR . "strange_characters.json";

    private readonly CharacterFinder $finder;

    public function __construct(readonly ?string $filename = self::DEFAULT_INPUT_FILENAME)
    {
        $charactersData = $this->readFrom($filename);
        $characters = $this->buildFrom($charactersData);
        $this->finder = new CharacterFinder($characters);
        $this->complete($charactersData, $characters);
    }

    public function findByPath(string $path): ?Character
    {
        return $this->finder->findByPath($path);
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

    private function nonStaticCompleteCharacter(stdClass $characterData, array $characters): void
    {
        $this->nonStaticAddNemesis($characterData);
        $this->nonStaticAddFamily($characterData, $characters);
    }

    private function nonStaticAddNemesis(stdClass $characterData): void
    {
        if (!empty($characterData->Nemesis)) {
            $character = $this->finder->find($characterData->FirstName);
            $character->setNemesis($this->finder->find($characterData->Nemesis));
        }
    }

    private function nonStaticAddFamily(stdClass $characterData, array $characters): void
    {
        if (!empty($characterData->Children)) {
            $this->nonStaticAddChildren($this->finder->find($characterData->FirstName), $characterData, $characters);
        }
    }

    private function nonStaticAddChildren(Character $character, stdClass $characterData, array $characters): void
    {
        foreach ($characterData->Children as $childName) {
            $this->nonStaticAddChild($character, $childName, $characters);
        }
    }


    private function nonStaticAddChild(Character $character, string $childName, array $characters): void
    {
        $child = $this->finder->findChild($characters, $childName);

        if ($child != null) {
            $character->addChild($child);
        }
    }

    private function complete(array $allCharactersData, array $allCharacters): void
    {
        $this->nonStaticCompleteCharacters($allCharactersData, $allCharacters);
    }

    private function nonStaticCompleteCharacters(array $allCharactersData, array $allCharacters): void
    {
        foreach ($allCharactersData as $characterData) {
            $this->nonStaticCompleteCharacter($characterData, $allCharacters);
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