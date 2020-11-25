<?php

namespace Tests\Unit\Sarvan\Printful\Cache\Services;


use Exception;
use InvalidArgumentException;
use Sarvan\Printful\Cache\CacheItem;
use Sarvan\Printful\Cache\Exceptions\CacheException;

use Sarvan\Printful\Cache\Services\FileCacheService;
use PHPUnit\Framework\TestCase;
use Sarvan\Printful\Cache\Services\FileSystemService;

/**
 * Class FileCacheServiceTest.
 *
 * @covers \Sarvan\Printful\Cache\Services\FileCacheService
 */
class FileCacheServiceTest extends TestCase
{
    /**
     * @var string
     */
    protected static string $testCachePath;

    /**
     * @var string
     */
    protected static string $testKey = 'testKey';
    /**
     * @var string
     */
    protected static string $testValue = 'testValue';
    /**
     * @var string
     */
    protected static string $testCacheName = 'test-cache';

    /**
     * @var array
     */
    protected static array $testItemArray;

    /**
     * @throws Exception
     */
    public function setUp(): void
    {
        self::$testCachePath = dirname(__DIR__, 1) . '/tmp/cache-test';

        $sampleObj = new CacheItem('sample');

        self::$testItemArray = [
            self::$testKey => [
                CacheItem::ARRAY_KEY_VALUE => self::$testValue,
                CacheItem::ARRAY_KEY_VALUE_TYPE => gettype(self::$testValue),
                CacheItem::ARRAY_KEY_EXPIRES_AT => null,
                CacheItem::ARRAY_KEY_CREATED_AT => time(),
                CacheItem::ARRAY_KEY_VERSION => CacheItem::VERSION
            ],
            'expired' => [
                CacheItem::ARRAY_KEY_VALUE => self::$testValue,
                CacheItem::ARRAY_KEY_VALUE_TYPE => gettype(self::$testValue),
                CacheItem::ARRAY_KEY_EXPIRES_AT => time() - 1,
                CacheItem::ARRAY_KEY_CREATED_AT => time(),
                CacheItem::ARRAY_KEY_VERSION => CacheItem::VERSION
            ],
            'oldVersion' => [
                CacheItem::ARRAY_KEY_VALUE => self::$testValue,
                CacheItem::ARRAY_KEY_VALUE_TYPE => gettype(self::$testValue),
                CacheItem::ARRAY_KEY_EXPIRES_AT => null,
                CacheItem::ARRAY_KEY_CREATED_AT => time(),
                CacheItem::ARRAY_KEY_VERSION => -1
            ],
            'object' => [
                CacheItem::ARRAY_KEY_VALUE => serialize($sampleObj),
                CacheItem::ARRAY_KEY_VALUE_TYPE => gettype($sampleObj),
                CacheItem::ARRAY_KEY_EXPIRES_AT => null,
                CacheItem::ARRAY_KEY_CREATED_AT => time(),
                CacheItem::ARRAY_KEY_VERSION => CacheItem::VERSION
            ],
            'resource' => [
                CacheItem::ARRAY_KEY_VALUE => 'resource',
                CacheItem::ARRAY_KEY_VALUE_TYPE => 'resource',
                CacheItem::ARRAY_KEY_EXPIRES_AT => null,
                CacheItem::ARRAY_KEY_CREATED_AT => time(),
                CacheItem::ARRAY_KEY_VERSION => CacheItem::VERSION
            ],
        ];

        mkdir(self::$testCachePath, 0764, true);
    }

    /**
     *
     */
    public function tearDown(): void
    {
        self::rmdirRecursive(self::$testCachePath);
    }


    /**
     * @throws CacheException
     */
    public function testConstructWithInvalidPathThrows(): void
    {
        $this->expectException(CacheException::class);

        new FileCacheService('invalid-path');
    }


    /**
     * @throws CacheException
     */
    public function testConstructWithCustomCacheName(): void
    {
        $cache = new FileCacheService(self::$testCachePath, self::$testCacheName);
        $this->assertSame(self::$testCacheName, $cache->getCacheName());
        $this->assertTrue(is_dir($cache->getCachePath()));
    }


    /**
     * @throws CacheException
     */
    public function testGetWithInvalidKeyThrows(): void
    {
        $cache = new FileCacheService(self::$testCachePath);

        $this->expectException(InvalidArgumentException::class);

        $cache->get('a b');
    }


    /**
     * @throws CacheException
     */
    public function testGetDefaultValue(): void
    {
        $cache = new FileCacheService(self::$testCachePath);

        $default = 'default';
        $this->assertSame($default, $cache->get('nonexistand', $default));
    }

    /**
     * @throws CacheException
     */
    public function testEnsureCachePathExistenceThrows(): void
    {
        new FileCacheService(self::$testCachePath, self::$testCacheName);

        $fileServiceStub = $this->createStub(FileSystemService::class);
        $fileServiceStub->method('isWritableDir')
            ->willReturn(true);
        $fileServiceStub->method('dirExists')
            ->willReturn(false);
        $fileServiceStub->method('createDir')
            ->will($this->throwException(new Exception('Test error')));

        $this->expectException(CacheException::class);
        new FileCacheService(self::$testCachePath, self::$testCacheName, $fileServiceStub);
    }


    /**
     * @throws CacheException
     */
    public function testPersistDataThrows(): void
    {
        // Ensure cache files...
        new FileCacheService(self::$testCachePath, self::$testCacheName);

        $fileServiceStub = $this->createStub(FileSystemService::class);
        $fileServiceStub->method('isWritableDir')
            ->willReturn(true);
        $fileServiceStub->method('fileExists')
            ->willReturn(true);
        $fileServiceStub->method('storeDataToFile')
            ->will($this->throwException(new Exception()));

        $this->expectException(CacheException::class);

        $cache = new FileCacheService(self::$testCachePath, self::$testCacheName, $fileServiceStub);
        $cache->set('test', 'test');
    }


    /**
     * @throws CacheException
     */
    public function testReadCacheItemArrayThrows(): void
    {
        // Ensure cache files...
        new FileCacheService(self::$testCachePath, self::$testCacheName);

        $fileServiceStub = $this->createStub(FileSystemService::class);
        $fileServiceStub->method('isWritableDir')
            ->willReturn(true);
        $fileServiceStub->method('isReadableFile')
            ->willReturn(true);
        $fileServiceStub->method('getDataFromFile')
            ->will($this->throwException(new Exception()));

        $this->expectException(CacheException::class);

        $cache = new FileCacheService(self::$testCachePath, self::$testCacheName, $fileServiceStub);
        $cache->get('test', 'test');
    }


    /**
     * @throws CacheException
     */
    public function testDelete(): void
    {
        $cache = new FileCacheService(self::$testCachePath, self::$testCacheName);
        $cache->delete('non_existent');
        $cache->set('foo', 'bar');
        $this->assertSame('bar', $cache->get('foo'));
        $cache->delete('foo');
        $this->assertNull($cache->get('foo'));
    }

    /**
     * @throws CacheException
     */
    public function testClear(): void
    {
        $cache = new FileCacheService(self::$testCachePath, self::$testCacheName);
        try {
            $cache->set('foo', 'bar');
        } catch (CacheException $e) {
        }
        $this->assertSame('bar', $cache->get('foo'));
        try {
            $cache->clear();
        } catch (CacheException $e) {
        }
        $this->assertNull($cache->get('foo'));
    }

    /**
     * @throws CacheException
     */
    public function testGetExpired(): void
    {
        $cache = new FileCacheService(self::$testCachePath, self::$testCacheName);
        $cache->set('foo', 'bar', -1);
        $this->assertNull($cache->get('foo'));
    }

    /**
     * @throws CacheException
     */
    public function testStoreGetObject(): void
    {
        $cache = new FileCacheService(self::$testCachePath, self::$testCacheName);
        $sampleObj = new CacheItem('sample');
        $cache->set('sampleObj', $sampleObj);
        $cachedObj = $cache->get('sampleObj');
        $this->assertInstanceOf(CacheItem::class, $cachedObj);
        $this->assertSame($sampleObj->getValue(), $cachedObj->getValue());
    }


    /**
     * @param string $message
     */
    private static function showMessage(string $message): void
    {
        fwrite(STDERR, print_r($message . "\n", true));
    }

    /**
     * @param string $dir
     */
    private static function rmdirRecursive(string $dir): void
    {
        foreach (scandir($dir) as $file) {
            if ('.' === $file || '..' === $file) {
                continue;
            }
            if (is_dir("$dir/$file")) {
                self::rmdirRecursive("$dir/$file");
            } else {
                unlink("$dir/$file");
            }
        }

        rmdir($dir);
    }
}

