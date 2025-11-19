<?php


use Tedon\Kachet\KachetProxy;
use Tedon\Kachet\Traits\Kachetable;

/**
 * @method KachetProxy<static> cached()
 */
class User
{
    use Kachetable;

    #[Tedon\Kachet\UseKachet(cacheKey: 'latest', ttl: 3600)]
    public function listLatest(): array
    {
        return [
            [
                'id' => 1,
                'name' => 'John Doe',
                'age' => 25,
                'email' => 'john.doe@example.com',
                'active' => true
            ],
            [
                'id' => 2,
                'name' => 'Jane Doe',
                'age' => 33,
                'email' => 'jane.doe@example.com',
                'active' => false
            ],
        ];
    }

    #[Tedon\Kachet\UseKachet(cacheKey: 'find_by_id', ttl: 60)]
    public function findById(int $id): array
    {
        return [
            'id' => $id * 3,
            'name' => 'Jake Doe',
            'age' => $id * 13,
            'email' => 'jake.doe@example.com',
            'active' => true,
        ];
    }

    public function cachedMethods(): array
    {
        return [
            [
                'methodName' => 'findById',
                'cacheKey' => 'find_by_id:%d',
                3600,
            ],
            [
                'methodName' => 'listLatest',
                'cacheKey' => 'latest',
                60,
            ],
        ];
    }
}