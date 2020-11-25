<?php

declare(strict_types=1);

namespace Sarvan\Printful\Cache;

use DateInterval;
use DateTimeImmutable;
use Exception;
use JsonSerializable;
use InvalidArgumentException;

/**
 * Class CacheItem
 *
 * @package Cache
 */
class CacheItem implements JsonSerializable
{
    /**
     * @var mixed $value Actual value to be cached.
     */
    protected $value;

    /**
     * @var string $valueType Indication of the type of value resolved during instantiation using gettype()
     */
    protected string $valueType;

    /**
     * @var int|null Timestamp at which the data is considered expired. If null, it does not expire.
     */
    protected ?int $expiresAt;

    /**
     * @var int $createdAt Timestamp at which the data was created.
     */
    protected int $createdAt;

    /**
     * @var int Indication of the CacheItem version. Used to mark specific item version in cache,
     * so it can considered obsolete in case of CacheItem version upgrade.
     */
    public const VERSION = 0;

    /**
     * @var string Key under which the value will be stored.
     */
    public const ARRAY_KEY_VALUE = 'value';
    /**
     * @var string Key under which the value type will be stored.
     */
    public const ARRAY_KEY_VALUE_TYPE = 'value_type';
    /**
     * @var string Key under which the expiration indication will be stored.
     */
    public const ARRAY_KEY_EXPIRES_AT = 'expires_at';
    /**
     * @var string Key under which the creation timestamp will be stored.
     */
    public const ARRAY_KEY_CREATED_AT = 'created_at';
    /**
     * @var string Key under which the CacheItem version will be stored.
     */
    public const ARRAY_KEY_VERSION = 'version';

    /**
     * @var array<string> List of required parameters for cache item to be considered valid.
     */
    public const ARRAY_REQUIRED_PARAMETERS = [
        self::ARRAY_KEY_VALUE,
        self::ARRAY_KEY_VALUE_TYPE,
        self::ARRAY_KEY_EXPIRES_AT,
        self::ARRAY_KEY_CREATED_AT,
        self::ARRAY_KEY_VERSION
    ];


    /**
     * CacheItem constructor.
     *
     * @param  $value
     * @param null $ttl
     * @throws Exception
     */
    public function __construct($value, $ttl = null)
    {
        $this->value = $this->normalizeValue($value);
        $this->valueType = gettype($value);
        $this->expiresAt = $this->resolveTtl($ttl);
        $this->createdAt = time();
    }

    /**
     * {@inheritDoc}
     * @throws     InvalidArgumentException
     */
    public function jsonSerialize(): array
    {
        return $this->getItemArray();
    }

    /**
     * Get current item as an array.
     *
     * @return array
     * @throws InvalidArgumentException In case the item array is invalid.
     */
    public function getItemArray(): array
    {
        $item = [
            self::ARRAY_KEY_VALUE => $this->value,
            self::ARRAY_KEY_VALUE_TYPE => $this->valueType,
            self::ARRAY_KEY_EXPIRES_AT => $this->expiresAt,
            self::ARRAY_KEY_CREATED_AT => $this->createdAt,
            self::ARRAY_KEY_VERSION => self::VERSION,
        ];

        self::validateItemArray($item);

        return $item;
    }

    /**
     * @param array $item Item array to validate
     * @throws InvalidArgumentException
     * @see    CacheItem::ARRAY_REQUIRED_PARAMETERS
     */
    public static function validateItemArray(array $item): void
    {
        if (!self::isValidItemArray($item)) {
            throw new InvalidArgumentException('Cache item array is not valid.');
        }
    }

    /**
     * @param  $item
     *
     * @return bool True if valid, else false.
     *
     * @see    CacheItem::ARRAY_REQUIRED_PARAMETERS
     */
    public static function isValidItemArray(array $item): bool
    {
        if (array_diff_key(array_flip(self::ARRAY_REQUIRED_PARAMETERS), $item)) {
            return false;
        }

        if ($item[self::ARRAY_KEY_VERSION] !== self::VERSION) {
            return false;
        }

        $expiresAt = $item[self::ARRAY_KEY_EXPIRES_AT];
        if ((!is_null($expiresAt)) && (!is_int($expiresAt))) {
            return false;
        }

        return true;
    }


    /**
     * @param array $item
     * @return CacheItem
     * @throws Exception
     */
    public static function fromItemArray(array $item): CacheItem
    {
        self::validateItemArray($item);

        $value = $item[self::ARRAY_KEY_VALUE];
        if ($item[self::ARRAY_KEY_VALUE_TYPE] === 'object') {
            $value = unserialize($value);
        }

        $ttl = $item[self::ARRAY_KEY_EXPIRES_AT];
        if (!is_null($ttl)) {
            $ttl -= time();
        }

        return new CacheItem($value, $ttl);
    }

    /**
     * @param mixed $value Value to be normalized.
     * @return mixed If value is object, the value will be serialized. Else, the value will be returned as is.
     * @throws InvalidArgumentException If the value type is not supported (resource or unknown type).
     */
    protected function normalizeValue($value)
    {
        $type = gettype($value);

        $invalidTypes = [
            'resource',
            'resource (closed)',
            'unknown type'
        ];

        if (in_array($type, $invalidTypes)) {
            throw new InvalidArgumentException('Value not valid (resource)');
        }

        if ($type === 'object') {
            $value = serialize($value);
        }

        return $value;
    }

    /**
     * Get current value.
     *
     * @param mixed $default Default value to return if the real value is expired.
     * @return mixed
     */
    public function getValue($default = null)
    {
        if ($this->isExpired()) {
            return $default;
        }

        if ($this->valueType === 'object') {
            return unserialize($this->value);
        }

        return $this->value;
    }

    /**
     * @return bool True if expired, else false.
     */
    public function isExpired(): bool
    {
        return $this->expiresAt !== null && $this->expiresAt < time();
    }


    /**
     * @param  $ttl
     * @return int|null
     * @throws Exception
     */
    protected function resolveTtl($ttl)
    {
        if (is_null($ttl)) {
            return null;
        }

        if (is_int($ttl)) {
            return time() + $ttl;
        }

        /**
         * @psalm-suppress RedundantConditionGivenDocblockType We are checking user input, so this is necessary.
         */
        if ($ttl instanceof DateInterval) {
            return (new DateTimeImmutable())->add($ttl)->getTimestamp();
        }

        throw new InvalidArgumentException('TTL value is not valid.');
    }
}
