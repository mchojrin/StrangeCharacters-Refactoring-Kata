<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use StrangeCharacters\CharacterDataParser;

class CharacterDataParserTest extends TestCase
{
    #[Test]
    public function findCharacterByPath() {
        $parser = new CharacterDataParser();
        self::assertEquals("Eleven", $parser->findByPath("/Jim/Eleven")->firstName);
    }

    #[Test]
    public function findCharacterByEmptyPath(): void
    {
        $parser = new CharacterDataParser();
        self::assertNull($parser->findByPath(""));
    }

    #[Test]
    public function FindCharacterByPathWithFamilyName(): void
    {
        $parser = new CharacterDataParser();
        self::assertEquals("Nancy", $parser->findByPath("/Wheeler:Karen/Wheeler:Nancy")->firstName);
    }

    #[Test]
    public function FindNemesisByPath(): void
    {
        $parser = new CharacterDataParser();
        self::assertEquals("Mindflayer", $parser->findByPath("/Joyce/Will{Nemesis}")->firstName);
    }

    #[Test]
    public function FindNemesisByPathAndFamilyName(): void
    {
        $parser = new CharacterDataParser();
        self::assertNull($parser->findByPath("/Wheeler:Karen/Wheeler:Nancy{Nemesis}"));
    }

    #[Test]
    public function FindNothingByPathAndFamilyName(): void
    {
        $parser = new CharacterDataParser();
        self::assertNull($parser->findByPath("/Wheeler:Karen/Wheeler:George"));
    }
}
