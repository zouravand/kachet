<?php

namespace Tedon\Kachet;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use ReflectionClass;
use ReflectionException;
use Tedon\Kachet\Constants\CachePattern;
use Tedon\Kachet\Definitions\CachedMethodDefinition;
use Tedon\Kachet\Exceptions\KachetException;

/**
 * CacheProxy intercepts method calls and delegates them to the loadFromCache method
 *
 * @template T
 * @mixin T
 */
class KachetProxy
{
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
     */
    public function __call(string $method, array $args): mixed
    {
        $cachedMethod = $this->getCachedMethod($method);
        $cacheKey = $this->generateCacheKey($cachedMethod, $args);


        $callback = fn() => $this->targetClass->{$cachedMethod->methodName}(...$args);
        return ($cachedMethod->ttl === null)
            ? Cache::driver($cachedMethod->driver)->rememberForever($cacheKey, $callback)
            : Cache::driver($cachedMethod->driver)->remember($cacheKey, $cachedMethod->ttl, $callback);
    }

    public function generateCacheKey(CachedMethodDefinition $cachedMethodDefinition, array $arguments = []): string
    {
        preg_match_all('/%(\d+\$)?[bcdeEfFgGosuxX]/', $cachedMethodDefinition->cacheKey, $matches);
        $cacheKey = vsprintf($cachedMethodDefinition->cacheKey, [...$arguments, ...array_fill(0, count($matches[0]) - count($arguments), '')]);
        return $this->getCachePatternPrefix($cachedMethodDefinition) . $this->cachePrefix . $cacheKey;
    }

    private function getCachePatternPrefix(CachedMethodDefinition $cachedMethodDefinition): string
    {
        return '';
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
            $attribute = array_first($attributes);

            if ($attribute) {
                $arguments = $attribute->getArguments();

                $cachedMethod = (object)[
                    'methodName' => $method,
                    'cacheKey' => $arguments['cacheKey'] ?? '',
                    'ttl' => $arguments['ttl'] ?? null,
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
            cacheKey: $cachedMethod['cacheKey'] ?? '',
            ttl: $cachedMethod['ttl'] ?? null,
            tags: $cachedMethod['tags'] ?? [],
            cacheNullValue: $cachedMethod['cacheNullValue'] ?? true,
            storePattern: $cachedMethod['storePattern'] ?? CachePattern::NONE,
            driver: $cachedMethod['driver'] ?? null
        );
    }
}
