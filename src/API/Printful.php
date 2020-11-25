<?php

declare(strict_types=1);

namespace Sarvan\Printful\API;

use Sarvan\Printful\API\Exceptions\ServiceException;

use Sarvan\Printful\API\Interfaces\ServiceInterface;

/**
 * Class Printful
 *
 * @package API
 */
class Printful
{
    /**
     * @var ServiceInterface
     */
    protected ServiceInterface $service;

    /**
     * Printful constructor.
     *
     * @param ServiceInterface $service
     */
    public function __construct(ServiceInterface $service)
    {
        $this->service = $service;
    }

    /**
     * @param mixed $body
     * @return ServiceInterface
     */
    public function getShippingRates($body)
    {
        try {
            return $this->service->getShippingRates($body);
        } catch (ServiceException $exception) {
            throw new ServiceException('[SERVICE] ' . $exception->getMessage());
        }
    }
}
