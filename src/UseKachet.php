<?php
namespace Tedon\Kachet;

use Attribute;
use Tedon\Kachet\Constants\CachePattern;

#[Attribute]
class UseKachet
{
    function __construct(
        public string $cacheKey,
        public ?int $ttl = null,
        public array $tags = [],
        public bool $cacheNullValue = false,
        public CachePattern $storePattern = CachePattern::BASE,
        public ?string $driver = null,
    ) {}
}