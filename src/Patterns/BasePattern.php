<?php

namespace Tedon\Kachet\Patterns;

class BasePattern extends Pattern
{
    public function encode($value): mixed
    {
        return $value;
    }

    public function decode($value): mixed
    {
        return $value;
    }
}