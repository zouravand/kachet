<?php

namespace Tedon\Kachet\Definitions;

use Tedon\Kachet\Constants\CachePattern;

class CachedMethodDefinition
{
    public function __construct(
        public string $methodName,
        public string $cacheKey,
        public ?int $ttl = null,
        public array $tags = [],
        public bool $cacheNullValue = false,
        public CachePattern $storePattern = CachePattern::BASE,
        public ?string $driver = null,
    ) {}
}