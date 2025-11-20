<?php

require __DIR__ . '/../vendor/autoload.php';

$tooner = new Tedon\Tooner\Tooner([
    'validate_lengths' => true,
    'restore_dates' => true,
    'max_depth' => 100,
    'object_as_array' => false,
    'key_folding' => true,
    'tabular_arrays' => true,
    'indentation' => 2,
    'indent_char' => ' ',
    'explicit_lengths' => true,
    'skip_nulls' => false,
    'normalize_numeric_keys' => true,
]);

$res = $tooner->encode(['test' => 123, 'oops' => 'bar', 'var' => true]);

var_dump($res);


use Tedon\Kachet\KachetProxy;
use Tedon\Kachet\Traits\Kachetable;

/**
 * @method KachetProxy<static> cached()
 */
#[Tedon\Kachet\UseKachet(cacheKey: 'user:')]
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


$userHandler = new User();

echo "\n=== Direct calls ===\n";
$result1 = $userHandler->listLatest(); // call directly
echo "listLatest(): ";
var_dump($result1);

$result2 = $userHandler->findById(1); // call directly
echo "\nfindById(1): ";
var_dump($result2);

echo "\n=== Cached calls ===\n";
$result3 = $userHandler->cached()->listLatest(); // call Kachetable -> loadFromCache('listLatest')
echo "cached()->listLatest(): ";
var_dump($result3);

$result4 = $userHandler->cached()->findById(2); // call Kachetable -> loadFromCache('findById', [2])
echo "\ncached()->findById(2): ";
var_dump($result4);

