<?php

namespace Tedon\Kachet\Traits;

use Illuminate\Support\Collection;
use Tedon\Kachet\KachetProxy;
use Tedon\Kachet\Definitions\CachedMethodDefinition;

trait Kachetable
{
    protected string $cachePrefix = 'kachet:';

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

            $this->kachetProxy = new KachetProxy($this, $this->cachedMethodDefinitions ?? null, $this->cachePrefix);
        }
        return $this->kachetProxy;
    }
}