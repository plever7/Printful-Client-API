<?php

declare(strict_types=1);
namespace Sarvan\Printful;

/**
 * Class Config
 */
class Config
{
    /**
     * @var string
     */
    public static string $host = 'https://api.printful.com/';

    /**
     * @return string
     */
    public static function getHost(): string
    {
        return self::$host;
    }

    /**
     * Returns route for given key
     *
     * @param $key
     * @return string
     */
    public static function getRoute($key): ?string
    {
        return self::routeList()[$key] ?? null;
    }

    /**
     * Returns list of routes
     *
     * @return array
     */
    private static function routeList()
    {
        return [
            'shipping.rates' => 'shipping/rates',
        ];
    }
}
