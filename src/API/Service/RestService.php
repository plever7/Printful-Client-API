<?php

declare(strict_types=1);

namespace Sarvan\Printful\API\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;
use Sarvan\Printful\API\Interfaces\ServiceInterface;
use Sarvan\Printful\Config;

/**
 * Class RestService
 *
 * @package Sarvan\Printful\API\Service
 */
class RestService implements ServiceInterface
{
    /**
     * Printful  API key
     *
     * @var string
     */
    protected string $apiKey = '';

    /**
     * @var Client
     */
    protected Client $httpClient;

    /**
     * Printful constructor.
     *
     * @param string $apiKey Api key
     * @param Client $httpClient Guzzle HttpClient
     */
    public function __construct(string $apiKey, Client $httpClient)
    {
        $this->apiKey = $apiKey;
        $this->httpClient = $httpClient;
    }

    /**
     * Retrieve list of available shipping options for given values
     *
     * @param mixed $body
     * @return mixed
     *
     * @throws GuzzleException
     */
    public function getShippingRates($body)
    {
        return $this->request('POST', Config::getRoute('shipping.rates'), $body);
    }

    /**
     * Get an API request response and handle possible exceptions.
     *
     * @param string $method
     * @param string $uri
     *
     * @param mixed $body
     * @return array|string
     * @throws GuzzleException
     */
    protected function request(string $method, $uri = '', $body = null)
    {
        $path = Config::getHost() . $uri;
        $options['headers'] = ['authorization' => "Basic $this->apiKey"];
        $options['body'] = $body;
        try {
            $response = $this->httpClient->request($method, $path, $options);
            return $this->getResult($response);
        } catch (BadResponseException $badResponseException) {
            return json_decode($badResponseException->getResponse()->getBody()->getContents());
        }
    }

    /**
     * @param ResponseInterface $response response of rest api client
     *
     * @return array|mixed
     */
    protected function getResult(ResponseInterface $response)
    {
        $status = $response->getStatusCode();
        if ($status === 200) {
            return json_decode($response->getBody()->getContents());
        }
        return (object)[$status => $response->getReasonPhrase()];
    }
}
