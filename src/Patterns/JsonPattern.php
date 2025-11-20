<?php

namespace Tedon\Kachet\Patterns;

use Illuminate\Contracts\Support\Arrayable;

class JsonPattern extends Pattern
{
    public function encode($value): mixed
    {
        if (is_null($value)) {
            return null;
        }
        if (!$value instanceof Arrayable) {
            return null;
        }
        $arrayResult = [
            'value' => $value->toArray(),
            'className' => get_class($value),
        ];
        return json_encode($arrayResult, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function decode($value): mixed
    {
        $cachedValue = json_decode($value, true);
        $returnObject = new $cachedValue['className']();
        foreach ($cachedValue['value'] as $itemIndex => $itemValue) {
            $returnObject->$itemIndex = $itemValue;
        }
        return $returnObject;
    }
}