<?php

namespace Tests\Unit\Sarvan\Printful\API\Service;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\MockObject\MockObject;
use Sarvan\Printful\API\Interfaces\ServiceInterface;
use Sarvan\Printful\API\Service\RestService;
use PHPUnit\Framework\TestCase;
use Sarvan\Printful\Config;

/**
 * Class RestServiceTest.
 *
 * @covers \Sarvan\Printful\API\Service\RestService
 */
class RestServiceTest extends TestCase
{

    /**
     * @var ServiceInterface|RestService
     */
    protected ServiceInterface $service;
    /**
     * @var string
     */
    protected string $apiKey;

    /**
     * @var Client|MockObject
     */
    protected MockObject $httpClient;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->apiKey = '21';
        $this->httpClient = $this->getMockBuilder('\GuzzleHttp\Client')
            ->disableOriginalConstructor()
            ->setMethods(['request'])
            ->getMock();
        $this->service = new RestService(base64_encode($this->apiKey), new Client());
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->apiKey);
        unset($this->httpClient);
    }

    /**
     *
     * @throws Exception
     */
    public function testGetShippingRates()
    {
        $restMock = $this->httpClient;
        $path = Config::$host . Config::getRoute('shipping.rates');
        $restMock->expects($this->once())->method('request')
            ->with($this->equalTo('POST'), $this->equalTo($path))
            ->will($this->returnValue(new Response(200, [], '{"response": 1}')));

        $service = new RestService($this->apiKey, $restMock);
        try {
            $output = $service->getShippingRates('some-options');
            $this->assertObjectHasAttribute('response', $output);
        } catch (GuzzleException $guzzleException) {
            $this->fail('This test should not have failed');
        }
    }

    public function testReturnsWrongResponseCodeIfWrongApiKeyRequested()
    {
        $body = [
            'recipient' => [
                'address1' => 'xxx',
                'city' => 'xxx',
                'country_code' => 'xx',
                'state_code' => 'xx',
                'zip' => 1111,
            ],
            'items' => [
                [
                    'quantity' => 1111,
                    'variant_id' => 1111,
                ],
            ],
        ];
        $service = new RestService('xxxxxxxx-xxxx-xxxx:xxxx-xxxxxxxxxxxx', new Client());
        try {
            $this->assertEquals('400', $service->getShippingRates(json_encode($body))->code);
        } catch (GuzzleException $e) {
            $this->fail('This test should not have failed');
        }
    }
}
