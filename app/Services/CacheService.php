<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Description of CacheService
 *
 * @author ander
 */
class CacheService {

    public function verifyCache($keyCache) {
        $keyCache = hash('sha256', $keyCache);
        if (Cache::has($keyCache)) {

            return Cache::get($keyCache);
        } else {
            return false;
        }
    }

    public function createCache($keyCache, $content, $duration) {
        $keyCache = hash('sha256', $keyCache);

        if (Cache::has($keyCache)) {
            Cache::put($keyCache, $content, $duration);
        } else {
            Cache::add($keyCache, $content, $duration);
        }
    }

}
