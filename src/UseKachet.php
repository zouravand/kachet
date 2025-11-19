<?php
namespace Tedon\Kachet;

use Attribute;

#[Attribute]
class UseKachet
{
    function __construct(
        public string $cacheKey,
        public ?int $ttl = null,

    ){}
}