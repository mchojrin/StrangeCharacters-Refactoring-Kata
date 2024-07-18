<?php

declare(strict_types=1);

namespace StrangeCharacters;

readonly class CharacterSearchCriteria
{
    public function __construct(
        public string $characterName,
        public string $tempPathWithoutModifier,
        public ?string $relation = "",
        public ?string $familyName = "",
    )
    {
    }
}