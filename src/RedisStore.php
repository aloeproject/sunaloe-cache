<?php

namespace Sunaloe\Cache;

use Closure;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Contracts\Redis\Factory as Redis;

class RedisStore implements Store
{

    /**
     * The Redis factory implementation.
     *
     * @var \Illuminate\Contracts\Redis\Factory
     */
    protected $redis;

    /**
     * A string that should be prepended to keys.
     *
     * @var string
     */
    protected $prefix;

    /**
     * The Redis connection that should be used.
     *
     * @var string
     */
    protected $connection;

    protected $hKeyPrefix;

    protected $redisConfig;

    /**
     * Create a new Redis store.
     *
     * @param  \Illuminate\Contracts\Redis\Factory $redis
     * @param  string $prefix
     * @param  string $connection
     * @return void
     */
    public function __construct(Redis $redis, $prefix = '', $connection = 'default', $redisConfig = [])
    {
        $this->redis = $redis;
        $this->setPrefix($prefix);
        $this->redisConfig = $redisConfig;
        $this->setConnection($connection);
    }


    /**
     * @param $key string
     * @return $this
     */
    public function setHkeyPrefix($key)
    {
        $this->hKeyPrefix = $this->prefix . $key;
        return $this;
    }

    /**
     * @return string
     */
    private function getHkeyPrefix()
    {
        return $this->hKeyPrefix;
    }

    /**
     * Retrieve an item from the cache by key.
     *
     * @param  string|array $key
     * @return mixed
     */
    public function get($key)
    {
        $value = $this->connection()->hget($this->getHkeyPrefix(), $key);

        $redisClient = $this->redisConfig['client'] ?? '';
        if ($redisClient == 'phpredis') {
            return $value !== false ? $this->unserialize($value) : null;
        }

        return !is_null($value) ? $this->unserialize($value) : null;
    }

    /**
     * Retrieve multiple items from the cache by key.
     *
     * Items not found in the cache will have a null value.
     *
     * @param  array $keys
     * @return array
     */
    public function many(array $keys)
    {
        $results = [];
        $values = [];
        foreach ($keys as $key) {
            $values[$key] = $this->get($key);
        }

        foreach ($values as $index => $value) {
            $results[$index] = !is_null($value) ? $this->unserialize($value) : null;
        }

        return $results;
    }

    /**
     * Store an item in the cache for a given number of minutes.
     *
     * @param  string $key
     * @param  mixed $value
     * @param  float|int $minutes
     * @return bool
     */
    public function put($key, $value, $minutes)
    {
        $minutes = (int)max(1, $minutes * 60);

        $lua = "return redis.call('hset',KEYS[1],KEYS[2],ARGV[1]) and redis.call('expire',KEYS[1],ARGV[2])";

        return (bool)$this->connection()->eval(
            $lua, 2, $this->getHkeyPrefix(), $key, $this->serialize($value), $minutes
        );
    }

    /**
     * Store multiple items in the cache for a given number of minutes.
     *
     * @param  array $values
     * @param  float|int $minutes
     * @return void
     */
    public function putMany(array $values, $minutes)
    {
        $this->connection()->multi();

        foreach ($values as $key => $value) {
            $this->put($key, $value, $minutes);
        }

        $this->connection()->exec();
    }

    /**
     * Store an item in the cache if the key doesn't exist.
     *
     * @param  string $key
     * @param  mixed $value
     * @param  float|int $minutes
     * @return bool
     */
    public function add($key, $value, $minutes)
    {
        $lua = "return redis.call('exists',KEYS[1])<1 and redis.call('hset',KEYS[1],KEYS[2],ARGV[1]) and redis.call('expire',KEYS[1],ARGV[2])";

        return (bool)$this->connection()->eval(
            $lua, 2, $this->getHkeyPrefix(), $key, $this->serialize($value), (int)max(1, $minutes * 60)
        );
    }

    /**
     * Increment the value of an item in the cache.
     *
     * @param  string $key
     * @param  mixed $value
     * @return int
     */
    public function increment($key, $value = 1)
    {
        return $this->connection()->hincrby($this->getHkeyPrefix(), $key, $value);
    }

    /**
     * Decrement the value of an item in the cache.
     *
     * @param  string $key
     * @param  mixed $value
     * @return int
     */
    public function decrement($key, $value = 1)
    {
        $this->increment($key, -$value);
    }

    /**
     * Store an item in the cache indefinitely.
     *
     * @param  string $key
     * @param  mixed $value
     * @return void
     */
    public function forever($key, $value)
    {
        $this->connection()->hset($this->getHkeyPrefix(), $key, $this->serialize($value));
    }


    /**
     * Remove an item from the cache.
     *
     * @param  string $key
     * @return bool
     */
    public function forget($key)
    {
        return (bool)$this->connection()->hdel($this->getHkeyPrefix(), [$key]);
    }

    /**
     * Remove all items from the cache.
     *
     * @return bool
     */
    public function flush()
    {
        $key = sprintf($this->getHkeyPrefix() . "*");
        $keys = $this->connection()->keys($key);
        $this->connection()->multi();
        foreach ($keys as $k) {
            $this->connection()->del($k);
        }
        $this->connection()->exec();
        return true;
    }


    /**
     * Get the Redis connection instance.
     *
     * @return \Predis\ClientInterface
     */
    public function connection()
    {
        return $this->redis->connection($this->connection);
    }

    /**
     * Set the connection name to be used.
     *
     * @param  string $connection
     * @return void
     */
    public function setConnection($connection)
    {
        $this->connection = $connection;
    }

    /**
     * Get the Redis database instance.
     *
     * @return \Illuminate\Contracts\Redis\Factory
     */
    public function getRedis()
    {
        return $this->redis;
    }

    /**
     * Get the cache key prefix.
     *
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * Set the cache key prefix.
     *
     * @param  string $prefix
     * @return void
     */
    public function setPrefix($prefix)
    {
        $this->prefix = !empty($prefix) ? $prefix . ':' : '';
    }

    /**
     * Serialize the value.
     *
     * @param  mixed $value
     * @return mixed
     */
    protected function serialize($value)
    {
        return is_numeric($value) ? $value : serialize($value);
    }

    /**
     * Unserialize the value.
     *
     * @param  mixed $value
     * @return mixed
     */
    protected function unserialize($value)
    {
        return is_numeric($value) ? $value : unserialize($value);
    }

    /**
     * Get an item from the cache, or execute the given Closure and store the result.
     *
     * @param  string $key
     * @param  \DateTimeInterface|\DateInterval|float|int $minutes
     * @param  \Closure $callback
     * @return mixed
     */
    public function remember($key, $minutes, Closure $callback)
    {
        $value = $this->get($key);

        // If the item exists in the cache we will just return this immediately and if
        // not we will execute the given Closure and cache the result of that for a
        // given number of minutes so it's available for all subsequent requests.
        if (!is_null($value)) {
            return $value;
        }

        $this->put($key, $value = $callback(), $minutes);

        return $value;
    }

    /**
     * Get an item from the cache, or execute the given Closure and store the result forever.
     *
     * @param  string $key
     * @param  \Closure $callback
     * @return mixed
     */
    public function rememberForever($key, Closure $callback)
    {
        $value = $this->get($key);

        // If the item exists in the cache we will just return this immediately and if
        // not we will execute the given Closure and cache the result of that for a
        // given number of minutes so it's available for all subsequent requests.
        if (!is_null($value)) {
            return $value;
        }

        $this->forever($key, $value = $callback());

        return $value;
    }
}
