# Printful PHP Client API
This library available interface allowing developers access to Prinftul dataset.

Note:
This library works on php  7.4

Installation using Composer:

`composer install`

# Usage example

See example.php

```php
<?php
require_once 'vendor/autoload.php';

use Sarvan\Printful\API\Printful;
use Sarvan\Printful\API\Service\RestService;
use Sarvan\Printful\API\PrintfulCached;
use Sarvan\Printful\Cache\Exceptions\CacheException;
use Sarvan\Printful\Cache\Services\FileCacheService;
use GuzzleHttp\Client;

$apiKey = base64_encode('77qn9aax-qrrm-idki:lnh0-fm2nhmp0yca7');
$body = [
    'recipient' => [
        'address1' => '11025 Westlake Dr',
        'city' => 'Charlotte',
        'country_code' => 'US',
        'state_code' => 'CA',
        'zip' => 28273,
    ],
    'items' => [
        [
            'quantity' => 2,
            'variant_id' => 7679,
        ],
    ],
];

$service = new RestService($apiKey, new Client());
$prinful = new Printful($service);

try {
    $filecache = new FileCacheService(getcwd(), 'tmp');
} catch (CacheException $e) {
    echo $e->getMessage();
}

$cachedPrintful = new PrintfulCached($service, $filecache, 5 * 60);

$response = $cachedPrintful->getShippingRates(
    json_encode($body)
);

var_dump($response);

```
