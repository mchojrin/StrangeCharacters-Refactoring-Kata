<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use StrangeCharacters\CharacterDataParser;

class CharacterDataParserTest extends TestCase
{
    private readonly CharacterDataParser $parser;

    protected function setUp(): void
    {
        $this->parser = new CharacterDataParser() ;
    }

    #[Test]
    public function findCharacterByPath() {
        self::assertEquals("Eleven", $this->parser->findByPath("/Jim/Eleven")->firstName);
    }

    #[Test]
    public function findCharacterByEmptyPath(): void
    {
        self::assertNull($this->parser->findByPath(""));
    }

    #[Test]
    public function FindCharacterByPathWithFamilyName(): void
    {
        self::assertEquals("Nancy", $this->parser->findByPath("/Wheeler:Karen/Wheeler:Nancy")->firstName);
    }

    #[Test]
    public function FindNemesisByPath(): void
    {
        self::assertEquals("Mindflayer", $this->parser->findByPath("/Joyce/Will{Nemesis}")->firstName);
    }

    #[Test]
    public function FindNemesisByPathAndFamilyName(): void
    {
        self::assertNull($this->parser->findByPath("/Wheeler:Karen/Wheeler:Nancy{Nemesis}"));
    }

    #[Test]
    public function FindNothingByPathAndFamilyName(): void
    {
        self::assertNull($this->parser->findByPath("/Wheeler:Karen/Wheeler:George"));
    }
}
