<?php
/**
 * Created by PhpStorm.
 * User: lkboy
 * Date: 2020/5/14
 * Time: 18:30
 */

namespace Sunaloe\Cache\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static bool has(string $key)
 * @method static mixed get(string $key, mixed $default = null)
 * @method static mixed pull(string $key, mixed $default = null)
 * @method static void put(string $key, $value, \DateTimeInterface | \DateInterval | float | int $minutes)
 * @method static bool add(string $key, $value, \DateTimeInterface | \DateInterval | float | int $minutes)
 * @method static void setHkeyPrefix(string $key) \Illuminate\Contracts\Cache\Repository
 * @method static int|bool increment(string $key, $value = 1)
 * @method static int|bool decrement(string $key, $value = 1)
 * @method static void forever(string $key, $value)
 * @method static mixed remember(string $key, \DateTimeInterface | \DateInterval | float | int $minutes, \Closure $callback)
 * @method static mixed rememberForever(string $key, \Closure $callback)
 * @method static bool forget(string $key)
 *
 */
class SunaloeCache extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'sunale-cache';
    }
}