<?php

namespace Lain\LaravelOperator;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class PreloadJson
{
    // model集合
    private Collection $modelCollection;

    // 同数据源的ids json字段
    private array $jsonFields;

    // 获取json字段的方法
    private $handlerGet;

    /**
     * @param Collection|Model|array $modelCollection
     */
    public function __construct(Collection|Model|array $modelCollection)
    {
        // 转换为collection
        if (!$modelCollection instanceof Collection) {
            $modelCollection = Collection::make(is_array($modelCollection) ? $modelCollection : [$modelCollection]);
        }
        $this->modelCollection = $modelCollection;
        $this->jsonFields = [];
        // 设置默认获取方法
        $this->handlerGet = fn($model, $jsonField) => $model->$jsonField ?? [];
    }

    /**
     * 设置同数据源的ids json字段
     *
     * @param string ...$fields
     * @return $this
     */
    public function setJsonFields(string ...$fields): PreloadJson
    {
        $this->jsonFields = $fields;
        return $this;
    }

    /**
     * 设置获取json字段的方法
     *
     * @param callable $handler
     * @return $this
     */
    public function setHandlerGet(callable $handler): PreloadJson
    {
        $this->handlerGet = $handler;
        return $this;
    }

    /**
     * 为每个model增加预加载属性
     *
     * @param Builder $preloadModel
     * @param string $preloadName
     * @param string ...$select
     * @return void
     */
    public function preload(Builder $preloadModel, string $preloadName, string ...$select): void
    {
        $fields = array_merge($select, ['id']);
        $itemIds = $this->getPreloadingIds();
        //$items = $preloadModel->whereIn('id', $itemIds)->select($fields)->get()->toArray();
        $items = [
            ['id' => 1, 'name' => 'testA'],
            ['id' => 2, 'name' => 'testB'],
        ];
        $storedItems = [];
        foreach ($items as $item) {
            $storedItems[$item['id']] = $item;
        }

        foreach ($this->modelCollection as $model) {
            $modelPreloadingIds = array_flip($this->getModelPreloadingIds($model));
            $model->setAttribute($preloadName, array_intersect_key($storedItems, $modelPreloadingIds));
        }
    }

    /**
     * 获取预加载ids
     *
     * @return array
     */
    private function getPreloadingIds(): array
    {
        if (!is_callable($this->handlerGet)) {
            return [];
        }

        $loadingIds = [];
        collect($this->modelCollection)->each(function ($model) use (&$loadingIds) {
            foreach ($this->jsonFields as $jsonField) {
                $ids = ($this->handlerGet)($model, $jsonField);
                if ($ids) {
                    $loadingIds = array_merge($loadingIds, $ids);
                }
            }
        });

        return array_flip(array_flip($loadingIds));
    }

    /**
     * 获取单个model的预加载ids
     *
     * @param Model $model
     * @return array
     */
    private function getModelPreloadingIds(Model $model): array
    {
        $loadingIds = [];
        foreach ($this->jsonFields as $jsonField) {
            $ids = ($this->handlerGet)($model, $jsonField);
            if ($ids) {
                $loadingIds = array_merge($loadingIds, $ids);
            }
        }

        return $loadingIds;
    }

    /**
     * 获取预加载值
     *
     * @param $storedItems
     * @param $fieldIds
     * @return array
     */
    public static function fetchValue($storedItems, $fieldIds): array
    {
        if (!$storedItems) {
            return [];
        }

        if (is_string($fieldIds)) {
            $fieldIds = explode(',', $fieldIds);
        }

        if (!$fieldIds) {
            return [];
        }

        return array_intersect_key($storedItems, array_flip($fieldIds));
    }
}
