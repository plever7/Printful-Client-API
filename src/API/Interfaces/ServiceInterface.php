<?php

declare(strict_types=1);

namespace Sarvan\Printful\API\Interfaces;

/**
 * Interface Printful
 *
 * @author  Sarvan Ibishov
 *
 * @package API\Interfaces
 */
interface ServiceInterface
{
    /**
     * Retrieve list of available shipping options for given values
     *
     * @param mixed $body
     *
     * @return mixed
     */
    public function getShippingRates($body);
}
