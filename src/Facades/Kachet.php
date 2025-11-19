<?php

namespace Tedon\Kachet\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string encode(mixed $value, array $options = [])
 * @method static mixed decode(string $value, array $options = [])
 * @method static mixed getConfig(string $key = null)
 *
 * @see \Tedon\Kachet\Kachet
 */
class Kachet extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'kachet';
    }
}