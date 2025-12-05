<?php

namespace Tedon\Kachet;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Psr\SimpleCache\InvalidArgumentException;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use Tedon\Kachet\Constants\CachePattern;
use Tedon\Kachet\Definitions\CachedMethodDefinition;
use Tedon\Kachet\Exceptions\KachetException;
use Tedon\Kachet\Patterns\Pattern;
use ValueError;

/**
 * CacheProxy intercepts method calls and delegates them to the loadFromCache method
 *
 * @template T
 * @mixin T
 */
class KachetProxy
{
    /** @var array<string, Pattern> */
    private array $patternCache = [];

    /**
     * @param T $targetClass
     * @param Collection<CachedMethodDefinition>|null $cachedMethodDefinitions
     * @param string $cachePrefix
     */
    public function __construct(
        private readonly mixed $targetClass,
        private ?Collection     $cachedMethodDefinitions = null,
        private readonly string $cachePrefix = '',
    )
    {
    }

    /**
     * Intercept method calls and delegate to loadFromCache
     * @param string $method
     * @param array $args
     * @return mixed
     * @throws KachetException
     * @throws ReflectionException
     * @throws InvalidArgumentException
     */
    public function __call(string $method, array $args): mixed
    {
        $cachedMethod = $this->getCachedMethod($method);
        $storePattern = $this->getStorePattern($cachedMethod);
        $cacheKey = $storePattern->getPrefix() . $this->generateCacheKey($cachedMethod, $args);

        $cache = Cache::driver($cachedMethod->driver);

        // Apply tags if provided
        if (!empty($cachedMethod->tags)) {
            $cache = $cache->tags($cachedMethod->tags);
        }

        // Check if value exists in cache
        if ($cache->has($cacheKey)) {
            $encodedValue = $cache->get($cacheKey);
            return $storePattern->decode($encodedValue);
        }

        // Execute the method
        $result = $this->targetClass->{$cachedMethod->methodName}(...$args);

        // Check if we should cache null values
        if ($result === null && !$cachedMethod->cacheNullValue) {
            return null;
        }

        // Encode and store in cache
        $encodedValue = $storePattern->encode($result);

        if ($cachedMethod->ttl === null) {
            $cache->forever($cacheKey, $encodedValue);
        } else {
            $cache->put($cacheKey, $encodedValue, $cachedMethod->ttl);
        }

        return $result;
    }

    /**
     * @throws KachetException
     */
    public function generateCacheKey(CachedMethodDefinition $cachedMethodDefinition, array $arguments = []): string
    {
        // Count placeholders in the format string
        preg_match_all('/%(\d+\$)?[bcdeEfFgGosuxX]/', $cachedMethodDefinition->cacheKey, $matches);
        $placeholderCount = count($matches[0]);

        // Prepare arguments array with proper padding
        $formattedArgs = $arguments;
        if ($placeholderCount > count($arguments)) {
            // Pad with empty strings if we have more placeholders than arguments
            $formattedArgs = [...$arguments, ...array_fill(0, $placeholderCount - count($arguments), '')];
        }

        // Generate cache key with error handling
        try {
            $cacheKey = vsprintf($cachedMethodDefinition->cacheKey, $formattedArgs);
            if (!$cacheKey) {
                throw new KachetException("Failed to generate cache key from format string: $cachedMethodDefinition->cacheKey");
            }
        } catch (ValueError $e) {
            throw new KachetException("Invalid cache key format: " . $e->getMessage(), 0, $e);
        }

        return $this->cachePrefix . $cacheKey;
    }

    /**
     * @throws KachetException
     */
    public function getStorePattern(CachedMethodDefinition $cachedMethod): Pattern
    {
        $patternKey = $cachedMethod->storePattern->value;

        // Return cached instance if available
        if (isset($this->patternCache[$patternKey])) {
            return $this->patternCache[$patternKey];
        }

        // Create new instance and cache it
        $storePatternClass = config('kachet.patterns.'.$patternKey);
        if (!$storePatternClass) {
            throw new KachetException("Pattern '$patternKey' is not configured");
        }

        $this->patternCache[$patternKey] = new $storePatternClass();
        return $this->patternCache[$patternKey];
    }

    /**
     * @param string $method
     * @return object
     * @throws KachetException
     * @throws ReflectionException
     */
    private function getCachedMethod(string $method): object
    {
        $cachedMethod = $this->cachedMethodDefinitions?->firstWhere('methodName', $method);
        if (!$cachedMethod) {
            $reflectionClass = new ReflectionClass($this->targetClass);

            // Check if the method exists
            if (!$reflectionClass->hasMethod($method)) {
                throw new KachetException("Method $method does not exist in " . get_class($this->targetClass));
            }

            // Get the method reflection
            $reflectionMethod = $reflectionClass->getMethod($method);

            // Get attributes from the method
            $attributes = $reflectionMethod->getAttributes(UseKachet::class);
            /** @var ReflectionAttribute $attribute */
            $attribute = Arr::first($attributes);

            if ($attribute) {
                $arguments = $attribute->getArguments();

                $cachedMethod = (object)[
                    'methodName' => $method,
                    'cacheKey' => $arguments['cacheKey'] ?? '',
                    'ttl' => $arguments['ttl'] ?? null,
                    'tags' => $arguments['tags'] ?? [],
                    'cacheNullValue' => $arguments['cacheNullValue'] ?? false,
                    'storePattern' => $arguments['storePattern'] ?? CachePattern::BASE,
                    'driver' => $arguments['driver'] ?? null,
                ];
                if ($this->cachedMethodDefinitions === null) {
                    $this->cachedMethodDefinitions = collect();
                }

                $this->cachedMethodDefinitions->push($cachedMethod);
            }
        }
        if ($cachedMethod === null) {
            throw new KachetException("Method $method does not exist");
        }

        return new CachedMethodDefinition(
            methodName: $method,
            cacheKey: $cachedMethod->cacheKey ?? '',
            ttl: $cachedMethod->ttl ?? null,
            tags: $cachedMethod->tags ?? [],
            cacheNullValue: $cachedMethod->cacheNullValue ?? false,
            storePattern: $cachedMethod->storePattern ?? CachePattern::BASE,
            driver: $cachedMethod->driver ?? null
        );
    }
}
