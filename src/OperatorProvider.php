<?php

namespace Lain\LaravelOperator;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class OperatorProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {

    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // 获取完整 sql 语句
        \Illuminate\Database\Query\Builder::macro('sql', function () {
            $bindings = $this->getBindings();
            $sql = str_replace('?', '%s', $this->toSql());

            return vsprintf($sql, $bindings);
        });

        \Illuminate\Database\Eloquent\Builder::macro('sql', function () {
            return $this->getQuery()->sql();
        });

        // QueryLog 记录完整 sql 日志
        DB::listen(function ($query) {
            $sql = array_reduce($query->bindings, function($sql, $binding) {
                return preg_replace('/\?/', is_numeric($binding) ? $binding : sprintf("'%s'", $binding), $sql, 1);
            }, $query->sql);

            Log::info('[sql] ' . $sql);
        });
    }
}