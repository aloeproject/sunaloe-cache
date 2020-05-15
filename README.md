 ## 概述

laravel 自带的缓存是存在string类型，本扩展包保存到hash类型

## 运行环境
- PHP 7.0
- laravel 5.7
- lumen 5.7


## 安装方法 

composer require sunaloe/cache

### lumen
- 添加门面方法

```php
$app->withFacades(true, [
    \Sunaloe\Cache\Facades\SunaloeCache::class => 'SunaloeCache',
]);
```

- 配置引入
把 sunaloe/cache/config/sunaloe-cache.php 拷贝放到配置目录

```php
    $app->configure('sunaloe-cache');
```

- 服务提供者引入


```php
    $app->register(\Illuminate\Redis\RedisServiceProvider::class);
   $app->register(\Sunaloe\Cache\CacheServiceProvider::class);
```


## 使用

```php
# == hset hk k4 a
\Sunaloe\Cache\Facades\SunaloeCache::setHkeyPrefix("hk")->forever("k4", "a");

# 如果hash 中hk键k2 不存在 则使用 回调函数的值进行设置
\Sunaloe\Cache\Facades\SunaloeCache::setHkeyPrefix("hk")->remember("k2", 100, function () {
            return ['a' => 1];
  });

# 所有hash key 进行删除
\Sunaloe\Cache\Facades\SunaloeCache::flush()

```

## License

- MIT


