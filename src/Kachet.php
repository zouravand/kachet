<?php

namespace Tedon\Kachet;

class Kachet
{
    public function __construct(protected array $config = [])
    {
    }

    public function getConfig($key = null)
    {
        if ($key) {
            return $this->config[$key] ?? null;
        }
        return $this->config;
    }
}