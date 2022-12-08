# Laravel Operator

[![tests](https://github.com/xuliangTang/laravel-test-generator/workflows/tests/badge.svg?branch=main)](https://github.com/xuliangTang/laravel-operator/actions?query=workflow:tests+branch:master)
[![PHP Version Require](https://img.shields.io/packagist/php-v/lain/laravel-operator)](https://packagist.org/packages/lain/laravel-operator)
[![Latest Stable Version](https://img.shields.io/github/v/release/xuliangTang/laravel-operator)](https://packagist.org/packages/lain/laravel-operator)

Laravel 扩展工具包/库的封装

## 安装

```
$ composer require lain/laravel-operator
```

## 功能

### PreloadJson

可以预加载 db 中字段为 json 类型的关联关系。

假设现在有2张表，songs 的 artist_ids 和 compose_ids 字段都关联 artists 表

```postgresql
CREATE TABLE `songs` (
  `id` integer,
  `name` varchar(50),
  `artist_ids` json
  `compose_ids` json
) 

CREATE TABLE `artists` (
  `id` integer,
  `name` varchar(50),
  `avatar` varchar(200)
) 
```

这时候查询 song 列表时如果需要关联所有的 artist 数据，应该怎么做？

首先预加载 collection 的所有 artist 数据，并把对应的数据写入给每个结果的 ``storedArtists`` 属性中

```php
$songs = Song::query()->get();

$preloaded = new PreloadJson($songs);
$preloaded->setJsonFields('artist_ids', 'compose_ids')
	->preload(Artist::query(), 'storedArtists', 'name', 'avatar');

return response()->success(SongResource::collection($songs));
```

在 resource 中调用 ``fetchValue`` 方法获取数据

```php
public function toArray($request)
{
    return [
        'id' => $this->id,
        'name' => $this->name,
        'artists' => PreloadJson::fetchValue($this->storedArtists, $this->artist_ids),
        'composes' => PreloadJson::fetchValue($this->storedArtists, $this->compose_ids)
    ];
}
```

