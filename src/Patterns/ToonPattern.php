<?php

namespace Tedon\Kachet\Patterns;

use Tedon\Tooner\Exceptions\ToonDecodingException;
use Tedon\Tooner\Facades\Tooner;

class ToonPattern extends Pattern
{
    public function encode($value): mixed
    {
        return Tooner::encode([
            'value' => $value,
            'className' => get_class($value),
        ]);
    }

    /**
     * @throws ToonDecodingException
     */
    public function decode($value): mixed
    {
        $cachedValue = Tooner::decode($value);
        $className = $cachedValue->className;
        $returnObject = new $className();

        foreach ($cachedValue->value as $itemIndex => $itemValue) {
            $returnObject->$itemIndex = $itemValue;
        }
        return $returnObject;
    }
}