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

### Preload JSON

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



### SQL Debug

可以轻松的获取完整的 sql 语句

#### 获取单条 sql

```
$sql = User::query()->where('id', '>', 500)
            ->where(function ($query) {
                $query->whereIn('mode', [1,2,4,5])
                    ->orWhere('status', 1);
            })
            ->limit(10)->sql();
```

#### 监听所有执行的 sql

```
DB::connection()->enableQueryLog();
User::query()->where('id', '>', 500)
            ->where(function ($query) {
                $query->whereIn('mode', [1,2,4,5])
                    ->orWhere('status', 1);
            })
            ->limit(10)->get();
```

期间所有的 sql 语句会被输出到日志中

```
$ tail -f ./storage/logs/laravel.log
```

```
[2022-12-09 18:44:34] local.INFO: [sql] select * from "oauth_clients" where "id" = 1 limit 1  
[2022-12-09 18:44:34] local.INFO: [sql] select "roles".*, "model_has_roles"."model_id" as "pivot_model_id", "model_has_roles"."role_id" as "pivot_role_id", "model_has_roles"."model_type" as "pivot_model_type" from "roles" inner join "model_has_roles" on "roles"."id" = "model_has_roles"."role_id" where "model_has_roles"."model_id" in (2) and "model_has_roles"."model_type" = 'App\Models\OrganizationMember'  
[2022-12-09 18:44:34] local.INFO: [sql] select * from "users" where "id" > 500 and ("mode" in (1, 2, 4, 5) or "status" = 1) limit 10  
```

