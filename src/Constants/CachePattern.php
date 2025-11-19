<?php

namespace Tedon\Kachet\Constants;

enum CachePattern: string
{
    case NONE = 'none';
    case JSON = 'json';
    case TOON  = 'toon';
}