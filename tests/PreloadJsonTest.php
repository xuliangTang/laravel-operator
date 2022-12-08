<?php

use Illuminate\Database\Eloquent\Model;
use Lain\LaravelOperator\PreloadJson;

class SongModel extends Model {}

it('preload', function () {
    $song = new SongModel();
    $preloaded = new PreloadJson($song);
    $preloaded->setJsonFields('artist_ids');
    // $preloaded->preload(Model::query(), 'storedArtists', 'name', 'avatar');

    $getValue = PreloadJson::fetchValue([], [1,2,3]);
    expect($getValue)->toBeArray();
});