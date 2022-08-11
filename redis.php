<?php

function setRPC(string $key, int $ttl): void
{
    $increment = getRedis()->incrBy($key, 1);
    if (1 === $increment) {
        getRedis()->expire($key, $ttl);
    }
}

function getRedis(): Redis
{
    $redis = new Redis();
    $redis->pconnect('localhost', 6379, 0.0);

    return $redis;
}
