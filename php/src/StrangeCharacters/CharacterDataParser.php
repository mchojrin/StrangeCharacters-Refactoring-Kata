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
        $charactersData = $this->readCharactersDataFrom($filename);
        $characters = $this->buildCharacterCollectionFrom($charactersData);
        $this->finder = new CharacterFinder($characters);
        $this->complete($charactersData, $characters);
    }

    public function findByPath(string $path): ?Character
    {
        return $this->finder->findByPath($path);
    }

    private function buildCharacterCollectionFrom(array $data): array
    {
        return $this->buildCharactersFrom($data);
    }
    /**
     * @param array $data
     * @return array
     */
    private function buildCharactersFrom(array $data): array
    {
        return array_map(fn(stdClass $characterData) => Character::withFirstAndLastNameAndMonsterStatus($characterData->FirstName, $characterData->LastName, $characterData->IsMonster), $data);
    }

    private function completeCharacter(stdClass $characterData, array $characters): void
    {
        $this->addNemesis($characterData);
        $this->addFamily($characterData, $characters);
    }

    private function addNemesis(stdClass $characterData): void
    {
        if (!empty($characterData->Nemesis)) {
            $character = $this->finder->find($characterData->FirstName);
            $character->setNemesis($this->finder->find($characterData->Nemesis));
        }
    }

    private function addFamily(stdClass $characterData, array $characters): void
    {
        if (!empty($characterData->Children)) {
            $this->addChildren($this->finder->find($characterData->FirstName), $characterData, $characters);
        }
    }

    private function addChildren(Character $character, stdClass $characterData, array $characters): void
    {
        foreach ($characterData->Children as $childName) {
            $this->addChild($character, $childName, $characters);
        }
    }


    private function addChild(Character $character, string $childName, array $characters): void
    {
        $child = $this->finder->findChild($characters, $childName);

        if ($child != null) {
            $character->addChild($child);
        }
    }

    private function complete(array $allCharactersData, array $allCharacters): void
    {
        $this->completeCharacters($allCharactersData, $allCharacters);
    }

    private function completeCharacters(array $allCharactersData, array $allCharacters): void
    {
        foreach ($allCharactersData as $characterData) {
            $this->completeCharacter($characterData, $allCharacters);
        }
    }

    /**
     * @param string $filename
     * @return array
     */
    private function readCharactersDataFrom(string $filename): array
    {
        return json_decode(file_get_contents($filename), false);
    }

}