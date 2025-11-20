<?php

use Tedon\Kachet\Patterns\BasePattern;
use Tedon\Kachet\Patterns\JsonPattern;
use Tedon\Kachet\Patterns\ToonPattern;
use Tedon\Kachet\Constants\CachePattern;

return [
    'toon' => [
        'validate_lengths' => env('KACHET_TOON_VALIDATE_LENGTHS', true),
        'restore_dates' => env('KACHET_TOON_RESTORE_DATES', true),
        'max_depth' => env('KACHET_TOON_MAX_DEPTH', 100),
        'object_as_array' => env('KACHET_TOON_OBJECT_AS_ARRAY', false),
        'key_folding' => env('KACHET_TOON_KEY_FOLDING', true),
        'tabular_arrays' => env('KACHET_TOON_TABULAR_ARRAYS', true),
        'indentation' => env('KACHET_TOON_INDENTATION', 2),
        'indent_char' => env('KACHET_TOON_INDENT_CHAR', ' '),
        'explicit_lengths' => env('KACHET_TOON_EXPLICIT_LENGTHS', true),
        'skip_nulls' => env('KACHET_TOON_SKIP_NULLS', false),
        'normalize_numeric_keys' => env('KACHET_TOON_NORMALIZE_NUMERIC_KEYS', true),
    ],
    'patterns' => [
        CachePattern::BASE->value => BasePattern::class,
        CachePattern::JSON->value => JsonPattern::class,
        CachePattern::TOON->value => ToonPattern::class,
    ]
];
