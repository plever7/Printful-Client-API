<?php

namespace Tests\Unit\Sarvan\Printful\API;

use PHPUnit\Framework\MockObject\MockObject;
use Sarvan\Printful\API\Interfaces\ServiceInterface;
use Sarvan\Printful\API\PrintfulCached;
use Sarvan\Printful\Cache\Interfaces\CacheInterface;
use PHPUnit\Framework\TestCase;

/**
 * Class PrintfulCachedTest.
 *
 * @covers \Sarvan\Printful\API\PrintfulCached
 */
class PrintfulCachedTest extends TestCase
{
    /**
     * @var PrintfulCached
     */
    protected PrintfulCached $printfulCached;

    /**
     * @var ServiceInterface|MockObject
     */
    protected $service;

    /**
     * @var CacheInterface|MockObject
     */
    protected $cache;

    /**
     * @var int
     */
    protected $ttl;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this
            ->getMockBuilder('Sarvan\Printful\API\Service\RestService')
            ->disableOriginalConstructor()
            ->getMock();
        $this->cache = $this
            ->getMockBuilder('Sarvan\Printful\Cache\Services\FileCacheService')
            ->disableOriginalConstructor()
            ->getMock();
        $this->ttl = 42;
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->service);
        unset($this->cache);
        unset($this->ttl);
    }

    /**
     *
     */
    public function testCacheIsSearchedWIthCorrectParameters()
    {
        $this->cache
            ->expects($this->once())
            ->method('get')
            ->with(
                sha1('some-options')
            );
        $this->service
            ->expects($this->once())
            ->method('getShippingRates')
            ->with('some-options')
            ->will($this->returnValue('response'));

        $cachedApiCaller = new PrintfulCached($this->service, $this->cache, $this->ttl);
        $cachedApiCaller->getShippingRates('some-options');
    }

    /**
     *
     */
    public function testValidResponseIsCached()
    {
        $this->cache
            ->expects($this->once())
            ->method('get')
            ->with(
                sha1('some-options')
            )
            ->will($this->returnValue(null));

        $this->service
            ->expects($this->once())
            ->method('getShippingRates')
            ->will($this->returnValue(json_decode('{"result": [], "code":200}')));


        $this->cache
            ->expects($this->once())
            ->method('set')
            ->with(
                sha1('some-options'),
                json_decode('{"result": [], "code":200}'),
                $this->ttl
            );

        $cachedApiCaller = new PrintfulCached($this->service, $this->cache, $this->ttl);
        $cachedApiCaller->getShippingRates('some-options');
    }
}
