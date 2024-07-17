<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use StrangeCharacters\CharacterDataParser;

class CharacterDataParserTest extends TestCase
{
    #[Test]
    public function findCharacterByPath(): void
    {
        CharacterDataParser::initWithDataFrom(null);
        self::assertEquals("Eleven", CharacterDataParser::findCharacterByPath("/Jim/Eleven")->firstName);
    }

    #[Test]
    public function findCharacterByEmptyPath(): void
    {
        CharacterDataParser::initWithDataFrom(null);
        self::assertNull(CharacterDataParser::findCharacterByPath(""));
    }

    #[Test]
    public function FindCharacterByPathWithFamilyName(): void
    {
        CharacterDataParser::initWithDataFrom(null);
        self::assertEquals("Nancy", CharacterDataParser::findCharacterByPath("/Wheeler:Karen/Wheeler:Nancy")->firstName);
    }

    #[Test]
    public function FindNemesisByPath(): void
    {
        CharacterDataParser::initWithDataFrom(null);
        self::assertEquals("Mindflayer", CharacterDataParser::findCharacterByPath("/Joyce/Will{Nemesis}")->firstName);
    }

    #[Test]
    public function FindNemesisByPathAndFamilyName(): void
    {
        CharacterDataParser::initWithDataFrom(null);
        self::assertNull(CharacterDataParser::findCharacterByPath("/Wheeler:Karen/Wheeler:Nancy{Nemesis}"));
    }

    #[Test]
    public function FindNothingByPathAndFamilyName(): void
    {
        CharacterDataParser::initWithDataFrom(null);
        self::assertNull(CharacterDataParser::findCharacterByPath("/Wheeler:Karen/Wheeler:George"));
    }
}
