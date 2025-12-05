<?php

namespace Tedon\Kachet\Patterns;

use Error;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;
use Tedon\Kachet\Exceptions\KachetException;
use Tedon\Tooner\Exceptions\ToonDecodingException;
use Tedon\Tooner\Exceptions\ToonEncodingException;
use Tedon\Tooner\Facades\Tooner;

class ToonPattern extends Pattern
{
    private array $processedObjects = [];

    /**
     * @throws KachetException
     * @throws ToonEncodingException
     */
    public function encode($value): string
    {
        $this->processedObjects = [];
        $encoded = $this->encodeValue($value);
        return Tooner::encode($encoded);
    }

    /**
     * @throws ToonDecodingException
     * @throws KachetException
     */
    public function decode($value): mixed
    {
        $cachedValue = Tooner::decode($value);
        return $this->decodeValue($cachedValue);
    }

    /**
     * @throws KachetException
     */
    private function encodeValue($value): mixed
    {
        if (is_null($value)) {
            return null;
        }

        if (is_scalar($value)) {
            return $value;
        }

        if (is_array($value)) {
            return array_map(function ($item) {
                return $this->encodeValue($item);
            }, $value);
        }

        if (is_object($value)) {
            $objectId = spl_object_id($value);

            // Prevent circular reference
            if (isset($this->processedObjects[$objectId])) {
                return [
                    '__kachet_type' => 'circular_reference',
                    '__kachet_id' => $objectId,
                ];
            }

            $this->processedObjects[$objectId] = true;

            try {
                $reflection = new ReflectionClass($value);
                $properties = [];

                // Get all properties (public, protected, private)
                foreach ($reflection->getProperties() as $property) {
                    if ($property->isStatic()) {
                        continue;
                    }

                    $property->setAccessible(true);
                    $propertyName = $property->getName();
                    $propertyValue = $property->getValue($value);

                    $properties[$propertyName] = [
                        'value' => $this->encodeValue($propertyValue),
                        'visibility' => $this->getPropertyVisibility($property),
                    ];
                }

                return [
                    '__kachet_type' => 'object',
                    '__kachet_class' => get_class($value),
                    '__kachet_properties' => $properties,
                ];
            } catch (ReflectionException $e) {
                throw new KachetException("Failed to encode object: " . $e->getMessage(), 0, $e);
            }
        }

        // Resource type
        if (is_resource($value)) {
            throw new KachetException("Cannot cache resource types");
        }

        return $value;
    }

    /**
     * @throws KachetException
     */
    private function decodeValue($value): mixed
    {
        if (is_null($value)) {
            return null;
        }

        if (is_scalar($value)) {
            return $value;
        }

        // Handle Tooner objects (stdClass)
        if (is_object($value)) {
            $value = (array) $value;
        }

        if (is_array($value)) {
            // Check if it's a special kachet object
            if (isset($value['__kachet_type'])) {
                if ($value['__kachet_type'] === 'circular_reference') {
                    // For now, return null for circular references
                    // A more sophisticated approach would require object graph reconstruction
                    return null;
                }

                if ($value['__kachet_type'] === 'object') {
                    return $this->decodeObject($value);
                }
            }

            // Regular array
            return array_map(function ($item) {
                return $this->decodeValue($item);
            }, $value);
        }

        return $value;
    }

    /**
     * @throws KachetException
     */
    private function decodeObject(array $data): object
    {
        $className = $data['__kachet_class'];

        try {
            if (!class_exists($className)) {
                throw new KachetException("Class $className does not exist");
            }

            $reflection = new ReflectionClass($className);
            $returnObject = $reflection->newInstanceWithoutConstructor();

            // Restore all properties
            foreach ($data['__kachet_properties'] as $propertyName => $propertyData) {
                // Handle both array and object property data (from Tooner)
                $propValue = is_object($propertyData) ? $propertyData->value : $propertyData['value'];
                $propertyValue = $this->decodeValue($propValue);

                if ($reflection->hasProperty($propertyName)) {
                    $property = $reflection->getProperty($propertyName);
                    $property->setAccessible(true);
                    $property->setValue($returnObject, $propertyValue);
                } else {
                    // Handle dynamic properties (PHP 8.2+ compatibility)
                    try {
                        $returnObject->$propertyName = $propertyValue;
                    } catch (Error $e) {
                        // Ignore dynamic property deprecation warnings
                        // The property was dynamic in the original object, so we restore it as-is
                        if (!str_contains($e->getMessage(), 'dynamic property')) {
                            throw $e;
                        }
                    }
                }
            }

            return $returnObject;
        } catch (ReflectionException $e) {
            throw new KachetException("Failed to decode object of class $className: " . $e->getMessage(), 0, $e);
        }
    }

    private function getPropertyVisibility(ReflectionProperty $property): string
    {
        if ($property->isPublic()) {
            return 'public';
        }
        if ($property->isProtected()) {
            return 'protected';
        }
        if ($property->isPrivate()) {
            return 'private';
        }
        return 'public';
    }
}