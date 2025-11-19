<?php

namespace Tedon\Kachet\Traits;

use Illuminate\Support\Collection;
use ReflectionClass;
use Tedon\Kachet\KachetProxy;
use Tedon\Kachet\Definitions\CachedMethodDefinition;
use Tedon\Kachet\UseKachet;

trait Kachetable
{
    private const DEFAULT_KACHET_PREFIX = 'kachet:';

    /** @var ?Collection<CachedMethodDefinition> $cachedMethodDefinitions */
    protected ?Collection $cachedMethodDefinitions = null;
    protected ?KachetProxy $kachetProxy = null;

    /**
     * @return CachedMethodDefinition[]|null
     */
    public function cachedMethods(): ?array
    {
        return null;
    }

    /**
     * @return KachetProxy<static>
     */
    public function cached(): KachetProxy
    {
        if($this->kachetProxy === null){
            if ($this->cachedMethodDefinitions === null && $this->cachedMethods() !== null) {
                $this->cachedMethodDefinitions = collect($this->cachedMethods())->keyBy('methodName');
            }

            $this->kachetProxy = new KachetProxy($this, $this->cachedMethodDefinitions ?? null, $this->getKachetPrefix());
        }
        return $this->kachetProxy;
    }

    private function getKachetPrefix(): ?string
    {
        $ref = new ReflectionClass($this);

        $attributes = $ref->getAttributes(UseKachet::class);

        if (empty($attributes)) {
            return self::DEFAULT_KACHET_PREFIX;
        }

        $attr = $attributes[0]->newInstance();

        return $attr->cacheKey ?? self::DEFAULT_KACHET_PREFIX;
    }
}