<?php

declare(strict_types=1);

namespace Sarvan\Printful\Cache\Services;

use Sarvan\Printful\Cache\CacheItem;
use Sarvan\Printful\Cache\Exceptions\CacheException;
use Sarvan\Printful\Cache\Interfaces\CacheInterface;
use Sarvan\Printful\Cache\Interfaces\FileSystemServiceInterface;
use Exception;
use Throwable;
use InvalidArgumentException;

use DateInterval;

/**
 * Class FileCacheService
 * @package Sarvan\Printful\Cache\Services
 */
class FileCacheService implements CacheInterface
{
    /**
     * @var string $storagePath Path which can be used to create a cache file.
     */
    protected string $storagePath;

    /**
     * @var string $cacheName Name of the file which will hold cached data.
     */
    protected string $cacheName;

    /**
     * @var string
     */
    protected string $fileExtension = '.json';

    /**
     * @var FileSystemServiceInterface $fileSystemService Service used to interact with the filesystem.
     */
    protected FileSystemServiceInterface $fileSystemService;

    /**
     * SimpleFileCache constructor.
     *
     * @param string $storagePath Path to writable folder used to store the cache files
     * @param string $cacheName Cache name, may contain up to 64 chars: a-zA-Z0-9_-
     * @param FileSystemServiceInterface|null $fileSystemService
     * @throws CacheException
     */
    public function __construct(
        string $storagePath,
        string $cacheName = 'simple-file-cache',
        ?FileSystemServiceInterface $fileSystemService = null
    ) {
        $this->fileSystemService = $fileSystemService ?? new FileSystemService();
        $this->cacheName = $cacheName;
        $this->validateStoragePath($storagePath);
        $this->storagePath = $storagePath;
        $this->ensureCachePathExistence();
    }

    /**
     * @param string $key
     * @throws InvalidArgumentException
     */
    public function validateCacheKey(string $key): void
    {
        if (!preg_match('/^[a-zA-Z0-9_.]{1,64}$/', $key)) {
            throw new InvalidArgumentException('Cache key is not valid.');
        }
    }

    /**
     * @param string $cacheName
     * @throws InvalidArgumentException
     */
    public function validateCacheName(string $cacheName): void
    {
        if (!preg_match('/^[a-zA-Z0-9_-]{1,64}$/', $cacheName)) {
            throw new InvalidArgumentException('Cache name is not valid.');
        }
    }

    /**
     * @param string $storagePath
     * @throws CacheException If storage path is not writable.
     */
    protected function validateStoragePath(string $storagePath): void
    {
        if (!$this->fileSystemService->isWritableDir($storagePath)) {
            throw new CacheException('Provided cache storage path is not writable.');
        }
    }

    /**
     * @throws CacheException If cache path does not exist or could not be created.
     */
    protected function ensureCachePathExistence(): void
    {
        try {
            if (!$this->fileSystemService->dirExists($this->getCachePath())) {
                $this->fileSystemService->createDir($this->getCachePath());
            }
        } catch (Throwable $exception) {
            throw new CacheException($exception->getMessage());
        }
    }

    /**
     * @param string $filePath
     * @param array $data
     * @return bool True if data was saved, else false.
     * @throws CacheException If file system service throws.
     */
    protected function persistData(string $filePath, array $data): bool
    {
        try {
            return (bool) $this->fileSystemService->storeDataToFile($filePath, json_encode($data));
        } catch (Throwable $exception) {
            throw new CacheException($exception->getMessage());
        }
    }

    /**
     * @return string Full path to cache folder.
     */
    public function getCachePath(): string
    {
        return $this->storagePath . DIRECTORY_SEPARATOR . $this->getCacheName();
    }

    /**
     * @return string
     */
    public function getCacheName(): string
    {
        return $this->cacheName;
    }

    /**
     * @param string $filePath
     * @return array CacheItem array.
     * @throws CacheException
     */
    protected function readCacheItemArray(string $filePath): array
    {
        try {
            return json_decode($this->fileSystemService->getDataFromFile($filePath), true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $exception) {
            throw new CacheException('Error reading cache item array. ' . $exception->getMessage());
        }
    }

    /**
     * Fetches a value from the cache.
     *
     * @param string $key The unique key of this item in the cache.
     * @param mixed $default Default value to return if the key does not exist.
     *
     * @return mixed The value of the item from the cache, or $default in case of cache miss.
     *
     * @throws CacheException If data could not be read
     * @throws InvalidArgumentException If the $key string is not a legal value
     *
     * @noinspection PhpMissingParamTypeInspection because of interface implementation
     */
    public function get($key, $default = null)
    {
        return $this->getSingleFromData($key, $default);
    }

    /**
     * @param string $key
     * @param null $default
     * @return mixed|null
     * @throws CacheException
     */
    protected function getSingleFromData(string $key, $default = null)
    {
        $cacheItemFilePath = $this->resolveCacheItemFilePath($key);

        if (!$this->fileSystemService->isReadableFile($cacheItemFilePath)) {
            return $default;
        }

        $item = $this->readCacheItemArray($cacheItemFilePath);

        if ($this->isInvalidOrExpiredCacheItemArray($item)) {
            $this->delete($key);
            return $default;
        }

        try {
            return CacheItem::fromItemArray($item)->getValue($default);
        } catch (Throwable $exception) {
            throw new CacheException($exception->getMessage());
        }
    }

    /**
     * @param array $item Should represent cache item array.
     * @return bool True if invalid or expired, else false.
     * @throws CacheException
     */
    public function isInvalidOrExpiredCacheItemArray(array $item): bool
    {
        try {
            return CacheItem::fromItemArray($item)->isExpired();
        } catch (Throwable $exception) {
            throw new CacheException($exception->getMessage());
        }
    }

    /**
     * Persists data in the cache, uniquely referenced by a key with an optional expiration TTL time.
     *
     * @param string $key The key of the item to store.
     * @param mixed $value The value of the item to store, must be serializable.
     * @param int|DateInterval|null $ttl Optional. The TTL value of this item. If no value is sent, it will be null
     *                                     meaning that it will be stored indefinitely.
     *
     * @return bool True on success and false on failure.
     *
     * @throws CacheException If the item could not be stored.
     * @throws InvalidArgumentException If the $key string is not a legal value.
     *
     * @noinspection PhpMissingParamTypeInspection because of interface implementation
     */
    public function set($key, $value, $ttl = null)
    {
        return $this->setSingle($key, $value, $ttl);
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param int|DateInterval|null $ttl
     * @return bool
     * @throws CacheException|InvalidArgumentException
     */
    protected function setSingle(string $key, $value, $ttl = null): bool
    {
        $cacheItemFileName = $this->generateCacheItemFileName($key);
        $cacheItemSubDir = $this->resolveCacheItemSubDir($cacheItemFileName);

        if (!$this->fileSystemService->dirExists($cacheItemSubDir)) {
            $this->fileSystemService->createDir($cacheItemSubDir);
        }

        $this->validateCacheName($key);

        $cacheItemFilePath = $this->prepareFileNamePath($cacheItemSubDir, $cacheItemFileName);

        if (!$this->fileSystemService->fileExists($cacheItemFilePath)) {
            $this->fileSystemService->createFile($cacheItemFilePath);
        }
        try {
            return $this->persistData($cacheItemFilePath, $this->prepareCacheItemArray($value, $ttl));
        } catch (Throwable $exception) {
            throw new CacheException($exception->getMessage());
        }
    }

    /**
     * Delete an item from the cache by its unique key.
     *
     * @param string $key The unique cache key of the item to delete.
     *
     * @return bool True if the item was successfully removed. False if there was an error.
     *
     * @throws InvalidArgumentException If the $key string is not a legal value.
     * @throws CacheException
     *
     * @noinspection PhpMissingParamTypeInspection because of interface implementation
     */
    public function delete($key)
    {
        $cacheItemFilePath = $this->resolveCacheItemFilePath($key);

        if ($this->fileSystemService->fileExists($cacheItemFilePath)) {
            return $this->fileSystemService->deleteFile($cacheItemFilePath);
        }

        return true;
    }

    /**
     * Wipes clean the entire cache's keys.
     *
     * @return bool True on success and false on failure.
     *
     * @throws CacheException
     */
    public function clear()
    {
        return $this->fileSystemService->rmDirRecursive($this->getCachePath()) &&
            $this->ensureCachePathExistence();
    }


    /**
     * @param  $value
     * @param null $ttl
     * @return array
     * @throws Exception
     */
    protected function prepareCacheItemArray($value, $ttl = null): array
    {
        return (new CacheItem($value, $ttl))->getItemArray();
    }

    /**
     * @param string $key
     * @return string
     * @throws InvalidArgumentException
     */
    protected function resolveCacheItemFilePath(string $key): string
    {
        $cacheItemFileName = $this->generateCacheItemFileName($key);

        return $this->prepareFileNamePath(
            $this->resolveCacheItemSubDir($cacheItemFileName),
            $cacheItemFileName
        );
    }

    /**
     * @param string $dirPath
     * @param string $fileName
     * @return string
     */
    protected function prepareFileNamePath(string $dirPath, string $fileName): string
    {
        return $dirPath . DIRECTORY_SEPARATOR . $fileName;
    }

    /**
     * @param string $fileName
     * @return string
     */
    protected function resolveCacheItemSubDir(string $fileName): string
    {
        $cacheItemSubFolder = $this->generateCacheItemSubFolder($fileName);

        return $this->getCachePath() . DIRECTORY_SEPARATOR .
            $cacheItemSubFolder;
    }

    /**
     * Generate a unique hash value for the provided $key. This value is to be used as the actual file name.
     *
     * @param string $key
     * @return string
     * @throws InvalidArgumentException
     */
    protected function generateCacheItemFileName(string $key): string
    {
        $this->validateCacheKey($key);
        return hash('sha256', $key) . $this->fileExtension;
    }

    /**
     * Generate sub-folders which will contain the actual cache files. This is to prevent filesystem to be overwhelmed
     * with to many files in single folder.
     *
     * @param string $fileName
     * @return string
     */
    protected function generateCacheItemSubFolder(string $fileName): string
    {
        $lettersNum = 1;
        $subFoldersNum = 0;
        $totalChars = $lettersNum * $subFoldersNum;

        return implode(
            DIRECTORY_SEPARATOR,
            str_split(mb_substr($fileName, 0, $totalChars), $lettersNum)
        );
    }
}
