# Kachet - Elegant Method-Level Caching for PHP

A Laravel package that provides elegant, method-level caching through a simple proxy pattern. Cache your class methods with zero boilerplate using either PHP attributes or programmatic configuration.

## Features

- **Zero boilerplate caching** - Add caching with a single method call
- **Dual configuration** - Use PHP attributes or programmatic configuration
- **Cache proxy pattern** - `$obj->method()` calls directly, `$obj->cached()->method()` uses cache
- **Flexible cache keys** - Support for dynamic cache keys with sprintf-style placeholders
- **Redis integration** - Built for Redis but works with any Laravel cache driver
- **Laravel integration** - Service provider and facade included
- **PHP 8.2+** - Built with modern PHP features

## What is Kachet?

Kachet provides a clean, intuitive API for caching method results in your PHP classes. Instead of manually wrapping your methods with cache logic, you can:

1. Add the `Kachetable` trait to your class
2. Configure cacheable methods using attributes or a configuration method
3. Call `cached()` when you want to use the cache

```php
$user = new UserRepository();

// Direct call - always executes the method
$result = $user->findById(1);

// Cached call - uses cache if available
$result = $user->cached()->findById(1);
```

## Installation

Install via Composer:

```bash
composer require tedon/kachet
```

For Laravel applications, the service provider will be automatically registered.

## Basic Usage

### Using PHP Attributes (Recommended)

The simplest way to add caching is using PHP attributes:

```php
use Tedon\Kachet\Traits\Kachetable;
use Tedon\Kachet\UseKachet;
use Tedon\Kachet\KachetProxy;

/**
 * @method KachetProxy<static> cached()
 */
class UserRepository
{
    use Kachetable;

    #[UseKachet(cacheKey: 'user:%d', ttl: 3600)]
    public function findById(int $id): array
    {
        // Expensive database query
        return DB::table('users')->find($id);
    }

    #[UseKachet(cacheKey: 'users:latest', ttl: 60)]
    public function listLatest(): array
    {
        return DB::table('users')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
    }
}

// Usage
$repo = new UserRepository();

// Direct call - always executes the query
$user = $repo->findById(1);

// Cached call - uses cache for 3600 seconds
$user = $repo->cached()->findById(1);
// Cache key: "kachet:user:1"

// List without cache
$users = $repo->listLatest();

// List with cache
$users = $repo->cached()->listLatest();
// Cache key: "kachet:users:latest"
```

### Using Programmatic Configuration

If you prefer to configure cache settings in a method, use the `cachedMethods()` approach:

```php
use Tedon\Kachet\Traits\Kachetable;
use Tedon\Kachet\KachetProxy;

/**
 * @method KachetProxy<static> cached()
 */
class ProductRepository
{
    use Kachetable;

    public function findById(int $id): array
    {
        return DB::table('products')->find($id);
    }

    public function listByCategory(string $category): array
    {
        return DB::table('products')
            ->where('category', $category)
            ->get();
    }

    public function cachedMethods(): array
    {
        return [
            [
                'methodName' => 'findById',
                'cacheKey' => 'product:%d',
                'ttl' => 3600,
            ],
            [
                'methodName' => 'listByCategory',
                'cacheKey' => 'products:category:%s',
                'ttl' => 1800,
            ],
        ];
    }
}

// Usage
$repo = new ProductRepository();

// Cache product for 1 hour
$product = $repo->cached()->findById(42);
// Cache key: "kachet:product:42"

// Cache category listing for 30 minutes
$products = $repo->cached()->listByCategory('electronics');
// Cache key: "kachet:products:category:electronics"
```

## Advanced Usage

### Dynamic Cache Keys with sprintf

Cache keys support sprintf-style placeholders that automatically map to method arguments:

```php
#[UseKachet(cacheKey: 'post:%d:comments:page:%d', ttl: 600)]
public function getPostComments(int $postId, int $page): array
{
    return DB::table('comments')
        ->where('post_id', $postId)
        ->paginate($page);
}

// Usage
$comments = $repo->cached()->getPostComments(123, 2);
// Cache key: "kachet:post:123:comments:page:2"
```

Supported sprintf formats:
- `%d` - Integer
- `%s` - String
- `%f` - Float
- And all other standard sprintf formats

### Forever Cache (No TTL)

Omit the TTL to cache indefinitely:

```php
#[UseKachet(cacheKey: 'settings')]
public function getSettings(): array
{
    return DB::table('settings')->pluck('value', 'key');
}
```

### Custom Cache Drivers

Specify a custom cache driver (configured in `config/cache.php`):

```php
public function cachedMethods(): array
{
    return [
        [
            'methodName' => 'heavyComputation',
            'cacheKey' => 'computation:%d',
            'ttl' => 86400,
            'driver' => 'redis',
        ],
    ];
}
```

### Cache Tags

Use tags for easier cache invalidation:

```php
public function cachedMethods(): array
{
    return [
        [
            'methodName' => 'findById',
            'cacheKey' => 'user:%d',
            'ttl' => 3600,
            'tags' => ['users'],
        ],
    ];
}

// Invalidate all user-related caches
Cache::tags(['users'])->flush();
```

### Cache Patterns

Kachet supports different serialization patterns for cached data:

```php
use Tedon\Kachet\Constants\CachePattern;

public function cachedMethods(): array
{
    return [
        [
            'methodName' => 'getComplexData',
            'cacheKey' => 'complex:data',
            'ttl' => 3600,
            'storePattern' => CachePattern::JSON, // JSON serialization
        ],
        [
            'methodName' => 'getStructuredData',
            'cacheKey' => 'structured:data',
            'ttl' => 3600,
            'storePattern' => CachePattern::TOON, // TOON serialization
        ],
    ];
}
```

Available patterns:
- `CachePattern::BASE` - No serialization (default)
- `CachePattern::JSON` - JSON serialization
- `CachePattern::TOON` - TOON format (requires tedon/tooner)

### Caching Null Values

Control whether null results should be cached:

```php
public function cachedMethods(): array
{
    return [
        [
            'methodName' => 'findOptional',
            'cacheKey' => 'optional:%d',
            'ttl' => 600,
            'cacheNullValue' => true, // Cache null results
        ],
    ];
}
```

### Custom Cache Prefix

Change the default cache key prefix using a class-level attribute:

```php
use Tedon\Kachet\Traits\Kachetable;
use Tedon\Kachet\UseKachet;
use Tedon\Kachet\KachetProxy;

/**
 * @method KachetProxy<static> cached()
 */
#[UseKachet(cacheKey: 'myapp:v2:')]
class MyRepository
{
    use Kachetable;

    #[UseKachet(cacheKey: 'user:%d', ttl: 3600)]
    public function findById(int $id): array
    {
        return DB::table('users')->find($id);
    }
}

// Cache key will be: "myapp:v2:user:1"
$user = $repo->cached()->findById(1);
```

## Configuration Options

### Attribute Configuration

When using the `#[UseKachet]` attribute:

```php
#[UseKachet(
    cacheKey: 'my:cache:key',  // Required: Cache key with optional sprintf placeholders
    ttl: 3600,                  // Optional: Time to live in seconds (null = forever)
)]
```

### Programmatic Configuration

When using `cachedMethods()`:

```php
[
    'methodName' => 'myMethod',          // Required: Method name to cache
    'cacheKey' => 'my:cache:key',        // Required: Cache key with sprintf placeholders
    'ttl' => 3600,                       // Optional: Time to live (null = forever)
    'tags' => ['tag1', 'tag2'],          // Optional: Cache tags
    'driver' => 'redis',                 // Optional: Specific cache driver
    'cacheNullValue' => true,            // Optional: Cache null results (default: false)
    'storePattern' => CachePattern::JSON, // Optional: Serialization pattern
]
```

## Complete Example

Here's a comprehensive example showing multiple caching strategies:

```php
use Tedon\Kachet\Traits\Kachetable;
use Tedon\Kachet\UseKachet;
use Tedon\Kachet\KachetProxy;
use Tedon\Kachet\Constants\CachePattern;

/**
 * @method KachetProxy<static> cached()
 */
class BlogRepository
{
    use Kachetable;

    protected string $cachePrefix = 'blog:v1:';

    // Simple attribute-based caching
    #[UseKachet(cacheKey: 'post:%d', ttl: 3600)]
    public function findPost(int $id): array
    {
        return DB::table('posts')->find($id);
    }

    // Multi-parameter cache key
    #[UseKachet(cacheKey: 'posts:%s:page:%d', ttl: 600)]
    public function listByCategory(string $category, int $page = 1): array
    {
        return DB::table('posts')
            ->where('category', $category)
            ->paginate($page);
    }

    // Forever cache
    #[UseKachet(cacheKey: 'categories')]
    public function getAllCategories(): array
    {
        return DB::table('categories')->pluck('name', 'id');
    }

    // Complex programmatic configuration
    public function getStats(int $year): array
    {
        return DB::table('posts')
            ->whereYear('created_at', $year)
            ->selectRaw('COUNT(*) as total, AVG(views) as avg_views')
            ->first();
    }

    public function cachedMethods(): array
    {
        return [
            [
                'methodName' => 'getStats',
                'cacheKey' => 'stats:%d',
                'ttl' => 86400,
                'tags' => ['statistics', 'posts'],
                'storePattern' => CachePattern::JSON,
                'driver' => 'redis',
            ],
        ];
    }
}

// Usage examples
$blog = new BlogRepository();

// Direct calls - no caching
$post = $blog->findPost(1);
$posts = $blog->listByCategory('tech', 2);
$categories = $blog->getAllCategories();
$stats = $blog->getStats(2024);

// Cached calls
$post = $blog->cached()->findPost(1);
// Key: "blog:v1:post:1", TTL: 3600s

$posts = $blog->cached()->listByCategory('tech', 2);
// Key: "blog:v1:posts:tech:page:2", TTL: 600s

$categories = $blog->cached()->getAllCategories();
// Key: "blog:v1:categories", TTL: forever

$stats = $blog->cached()->getStats(2024);
// Key: "blog:v1:stats:2024", TTL: 86400s
// Serialized as JSON, tagged with ['statistics', 'posts']

// Clear specific caches
Cache::tags(['statistics'])->flush();
```

## Laravel Facade Usage

Kachet provides a facade for direct cache operations:

```php
use Tedon\Kachet\Facades\Kachet;

// Check if a cache key exists
if (Kachet::has('user:123')) {
    // ...
}

// Manually cache a value
Kachet::put('custom:key', $value, 3600);

// Retrieve a cached value
$value = Kachet::get('custom:key');
```

## How It Works

Kachet uses PHP's magic `__call` method to intercept method calls on the proxy object:

1. When you call `$obj->cached()`, it returns a `KachetProxy` instance
2. The proxy holds a reference to your original object
3. When you call a method on the proxy (e.g., `->findById(1)`), the proxy:
   - Looks up the cache configuration for that method
   - Generates a cache key using the method arguments
   - Checks if the result exists in cache
   - If cached: returns the cached value
   - If not cached: calls the original method, caches the result, and returns it

## Requirements

- PHP 8.2 or higher
- Laravel 10.x, 11.x, or 12.x
- Redis (recommended) or any Laravel-supported cache driver

## Testing

```bash
composer test
```

## IDE Support & Autocomplete

Kachet fully supports IDE autocomplete for cached methods using PHPDoc annotations. To enable autocomplete in your IDE (PhpStorm, VSCode, etc.), add the following annotation to your class:

```php
use Tedon\Kachet\Traits\Kachetable;
use Tedon\Kachet\KachetProxy;

/**
 * @method KachetProxy<static> cached()
 */
class UserRepository
{
    use Kachetable;

    public function findById(int $id): array { /* ... */ }
    public function listLatest(): array { /* ... */ }
}
```

With this annotation:
- Your IDE will autocomplete `$repo->cached()->findById()`
- Type hints and parameter suggestions will work correctly
- You'll get proper code navigation and refactoring support

The `@method KachetProxy<static> cached()` annotation tells the IDE that:
1. The `cached()` method returns a `KachetProxy` instance
2. The proxy is generic over `static` (your class type)
3. Through the `@mixin` annotation in `KachetProxy`, the IDE knows the proxy has all your class methods

**Note:** This annotation is optional - your code will work without it, but adding it significantly improves the development experience.

## Best Practices

1. **Use attributes for simple cases** - They're cleaner and easier to read
2. **Use programmatic config for complex cases** - When you need tags, custom drivers, or patterns
3. **Choose appropriate TTLs** - Shorter for frequently changing data, longer for stable data
4. **Use cache tags** - Makes cache invalidation easier
5. **Set custom prefixes** - Include version numbers for easier cache busting
6. **Cache expensive operations** - Database queries, API calls, complex computations
7. **Don't cache everything** - Simple getters/setters don't need caching
8. **Add IDE autocomplete annotations** - Include `@method KachetProxy<static> cached()` for better IDE support

## Cache Invalidation

```php
use Illuminate\Support\Facades\Cache;

// Clear specific key
Cache::forget('kachet:user:123');

// Clear by pattern (Redis only)
Cache::tags(['users'])->flush();

// Clear all cache
Cache::flush();
```

## Performance Tips

- Use Redis for better performance with high-traffic applications
- Set appropriate TTLs to balance freshness and performance
- Use cache tags for efficient bulk invalidation
- Monitor cache hit rates using Laravel Telescope or similar tools

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

MIT License - see the [LICENSE](LICENSE) file for details.

## Author

**Pouya Zouravand**
- Email: pouya.zuravand@gmail.com

## Credits

Built with inspiration from Laravel's elegant API design principles and modern PHP best practices.