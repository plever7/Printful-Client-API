<?php

namespace Tests\Unit\Sarvan\Printful\Cache\Services;

use Sarvan\Printful\Cache\Exceptions\CacheException;
use Sarvan\Printful\Cache\Services\FileSystemService;
use PHPUnit\Framework\TestCase;
use Exception;

/**
 * Class FileSystemServiceTest.
 *
 * @covers \Sarvan\Printful\Cache\Services\FileSystemService
 */
class FileSystemServiceTest extends TestCase
{
    /**
     * @var FileSystemService
     */
    protected static FileSystemService $fileSystemService;

    /**
     * @var string
     */
    protected static string $testPath;

    /**
     * @var string
     */
    protected static string $testFileName = 'sample-file.tmp';

    /**
     * @var array
     */
    protected static array $testData = ['test' => 'data'];

    /**
     *
     */
    public static function setUpBeforeClass(): void
    {
        self::$testPath = dirname(__DIR__, 4) . '/tmp';
        self::$fileSystemService = new FileSystemService();
    }

    /**
     *
     */
    public static function tearDownAfterClass(): void
    {
        self::rmdirRecursive(self::$testPath);
    }

    /**
     *
     */
    public function tearDown(): void
    {
        // Used to disable throwing exceptions for file_get_contents stub.
        $_ENV = [];
    }

    /**
     *
     */
    public function testCreateDir(): void
    {
        $this->assertFalse(is_dir(self::$testPath));
        try {
            self::$fileSystemService->createDir(self::$testPath);
        } catch (CacheException $e) {
            $this->fail('test failed');
        }
        $this->assertTrue(is_dir(self::$testPath));
    }

    /**
     * @depends testCreateDir
     * @throws CacheException
     */
    public function testCreateExistingDir(): void
    {
        $this->expectException(Exception::class);
        self::$fileSystemService->createDir(self::$testPath);
    }

    /**
     * @depends testCreateDir
     */
    public function testIsWritableDir(): void
    {
        $this->assertFalse(self::$fileSystemService->isWritableDir('invalid-path'));
        $this->assertTrue(self::$fileSystemService->isWritableDir(self::$testPath));
    }

    /**
     * @throws Exception
     *
     * @depends testCreateDir
     */
    public function testCreateFile(): void
    {
        $filePath = self::$testPath . '/' . self::$testFileName;
        $this->assertFalse(self::$fileSystemService->fileExists($filePath));
        self::$fileSystemService->createFile($filePath);
        $this->assertTrue(self::$fileSystemService->fileExists($filePath));
    }


    /**
     * @throws CacheException
     * @depends testCreateDir
     * @depends testCreateFile
     */
    public function testGetDataFromFileThrowsOnFileGetContentsError(): void
    {
        $filePath = self::$testPath . '/' . self::$testFileName;
        $this->expectException(Exception::class);
        self::$fileSystemService->getDataFromFile($filePath, __NAMESPACE__ . '\FileSystemServiceTest::fileGetContents');
    }

    /**
     * @depends testCreateDir
     * @depends testCreateFile
     * @throws CacheException
     */
    public function testStoreDataToFile(): void
    {
        $filePath = self::$testPath . '/' . self::$testFileName;
        $data = json_decode(self::$fileSystemService->getDataFromFile($filePath), true);
        $this->assertNotSame(self::$testData, $data);
        self::$fileSystemService->storeDataToFile($filePath, json_encode($data));
        $data = json_decode(self::$fileSystemService->getDataFromFile($filePath), true);
        $this->assertNotSame(self::$testData, $data);
    }

    /**
     * @depends testCreateFile
     */
    public function testIsWritableFile(): void
    {
        $filePath = self::$testPath . '/' . self::$testFileName;
        $this->assertTrue(self::$fileSystemService->isWritableFile($filePath));

        $this->assertFalse(self::$fileSystemService->isWritableFile(self::$testPath . '/invalid'));
    }

    /**
     * @depends testCreateFile
     */
    public function testIsReadableFile(): void
    {
        $filePath = self::$testPath . '/' . self::$testFileName;
        $this->assertTrue(self::$fileSystemService->isReadableFile($filePath));

        $this->assertFalse(self::$fileSystemService->isReadableFile(self::$testPath . '/invalid'));
    }

    /**
     * @depends testCreateDir
     * @throws CacheException
     */
    public function testRmDirRecursive(): void
    {
        $sampleDir = self::$testPath . '/recursive/dir/test';
        self::$fileSystemService->createDir($sampleDir);
        $this->assertTrue(self::$fileSystemService->dirExists($sampleDir));
        $fileName = $sampleDir . '/foo';
        touch($fileName);
        $this->assertTrue(self::$fileSystemService->fileExists($fileName));
        self::$fileSystemService->rmDirRecursive(self::$testPath . '/recursive');
        $this->assertFalse(self::$fileSystemService->fileExists($fileName));
        $this->assertFalse(self::$fileSystemService->dirExists($sampleDir));
    }

    /**
     * @throws Exception
     *
     * @depends testCreateDir
     */
    public function testDeleteNonExistentFileThrows(): void
    {
        $this->expectException(Exception::class);
        self::$fileSystemService->deleteFile(self::$testPath . '/' . 'non-existent');
    }

    /**
     * @throws Exception
     *
     * @depends testCreateDir
     */
    public function testDelete(): void
    {
        $filePath = self::$testPath . '/' . 'to-be-deleted.json';
        self::$fileSystemService->createFile($filePath);
        $this->assertTrue(self::$fileSystemService->fileExists($filePath));
        self::$fileSystemService->deleteFile($filePath);
        $this->assertFalse(self::$fileSystemService->fileExists($filePath));
    }

    /**
     * @throws CacheException
     */
    public function testgetDataFromFileThrowsForInvalidCallback(): void
    {
        $this->expectException(Exception::class);
        self::$fileSystemService->getDataFromFile('some-file', 'invalid-callback');

    }

    /**
     * Mock function for file_get_contents to simulate exception throw.
     * @param string $filePath
     * @return bool
     */
    public static function fileGetContents(string $filePath): bool
    {
        return false;
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
