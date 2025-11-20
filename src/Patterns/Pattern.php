<?php

namespace Tedon\Kachet\Patterns;

abstract class Pattern
{
    protected string $prefix = '';
    abstract public function encode($value): mixed;
    abstract public function decode($value): mixed;

    public function getPrefix(): string
    {
        return $this->prefix;
    }
}