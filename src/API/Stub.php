<?php

declare(strict_types=1);

namespace Sarvan\Printful\API;

use Sarvan\Printful\API\Interfaces\ServiceInterface;

/**
 * Class Stub
 *
 * @package Sarvan\Printful\API\Interfaces
 */
class Stub implements ServiceInterface
{

    /**
     * Retrieve list of available shipping options for given values
     *
     * @param null $body
     * @return mixed
     */
    public function getShippingRates($body = null)
    {
        $json = '{"code":200,"result":[{"id":"STANDARD","name":"Flat Rate (Estimated delivery: Dec 04⁠–Dec 09) ","rate":"5.24","currency":"USD","minDeliveryDays":4,"maxDeliveryDays":7}],"extra":[]}';
        return $this->format($json);
    }

    /**
     * @param string $result
     *
     * @return mixed
     */
    private function format($result)
    {
        return json_decode($result, true);
    }
}
