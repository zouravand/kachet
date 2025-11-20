<?php

namespace Tedon\Kachet\Facades;

use Illuminate\Support\Facades\Facade;

/**
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