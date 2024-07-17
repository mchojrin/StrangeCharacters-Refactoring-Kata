<?php

declare(strict_types=1);

use StrangeCharacters\CharacterDataParser;

require __DIR__ . '/vendor/autoload.php';

$filename = $argc > 1 ? $argv[1] : ROOT_DIR . DIRECTORY_SEPARATOR . "resources" . DIRECTORY_SEPARATOR . "strange_characters.json";
echo "Using character file: $filename" . PHP_EOL;
CharacterDataParser::initWithDataFrom($filename);

while (true) {
    echo "Please enter the path to evaluate: (type 'exit' to exit program)".PHP_EOL;

    $path = readline();

    if ("exit" === $path) {
        break;
    }
    $character = CharacterDataParser::findCharacterByPath($path);

    echo "Character: $character".PHP_EOL;
}