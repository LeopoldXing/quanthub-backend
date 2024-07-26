<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;

class RedisService
{
    /**
     * @param $articleId
     * @return void
     */
    public function increaseViews($articleId): void {
        /*  add viewing data into redis using atomic operation  */
        $key = $articleId;
        $script = "
                        if redis.call('EXISTS', KEYS[1]) == 0 then
                            redis.call('SET', KEYS[1], 0)
                        else
                            redis.call('INCR', KEYS[1])
                        end
                       ";
        $result = Redis::eval($script, 1, $key);
    }

    /**
     * @param $articleId
     * @return mixed
     */
    public function getViews($articleId): mixed {
        return Redis::get($articleId);
    }
}
