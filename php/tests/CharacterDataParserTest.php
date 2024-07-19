<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use StrangeCharacters\CharacterDataParser;

class CharacterDataParserTest extends TestCase
{
    private readonly CharacterDataParser $parser;

    public static function expectationsProvider()
    {
        return [
            ["/Jim/Eleven", "Eleven"],
            ["/Wheeler:Karen/Wheeler:Nancy", "Nancy"],
            ["/Joyce/Will{Nemesis}", "Mindflayer"],
            ["/Wheeler:Karen/Wheeler:Nancy{Nemesis}", null],
            ["/Wheeler:Karen/Wheeler:George", null],
            ["", null],
        ];
    }

    protected function setUp(): void
    {
        $this->parser = new CharacterDataParser() ;
    }

    #[Test]
    #[DataProvider("expectationsProvider")]
    public function shouldFindCharactersByPath(string $path, ?string $name): void
    {
        self::assertEquals($name, $this->parser->findByPath($path)->firstName);
    }
}
