<?php

declare(strict_types=1);

namespace Sarvan\Printful\API;

use Sarvan\Printful\API\Interfaces\ServiceInterface;
use Sarvan\Printful\Cache\Interfaces\CacheInterface;

/**
 * Class PrintfulCached
 *
 * @package Sarvan\Printful\API
 */
final class PrintfulCached extends Printful
{

    /**
     * @var CacheInterface
     */
    protected CacheInterface $cache;

    /**
     * @var int
     */
    protected int $ttl;

    /**
     * PrintfulCached constructor.
     *
     * @param ServiceInterface $service Printful
     * @param CacheInterface $cache CacheInterface
     * @param int $ttl cache expiration time
     */
    public function __construct(ServiceInterface $service, CacheInterface $cache, int $ttl)
    {
        parent::__construct($service);
        $this->cache = $cache;
        $this->ttl = $ttl;
    }

    /**
     * This methods check cache file, if is it present retrieve data from cache otherwise retrieve from api
     *
     * @param mixed $body
     * @return mixed|void
     */
    public function getShippingRates($body)
    {
        $cacheKey = sha1($body);
        $response = $this->cache->get($cacheKey);
        if (is_null($response)) {
            $response = $this->service->getShippingRates($body);
            if ($this->isCachable($response)) {
                $this->cache->set($cacheKey, $response, $this->ttl);
            }
        }
        return $response;
    }

    /**
     * This methods check result of Printful API, if everything going well returns true
     *
     * @param $results
     *
     * @return bool
     */
    private function isCachable($results)
    {
        if (!$results) {
            return false;
        }

        if ($results
            && 200 !== (
                $results->code ?? 0
            )
        ) {
            return false;
        }
        return true;
    }
}
