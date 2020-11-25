<?php

namespace Tests\Unit\Sarvan\Printful\API;

use PHPUnit\Framework\MockObject\MockObject;
use Sarvan\Printful\API\Exceptions\ServiceException;
use Sarvan\Printful\API\Interfaces\ServiceInterface;
use Sarvan\Printful\API\Printful;
use PHPUnit\Framework\TestCase;

/**
 * Class PrintfulTest.
 *
 * @covers \Sarvan\Printful\API\Printful
 */
class PrintfulTest extends TestCase
{
    /**
     * @var Printful
     */
    protected Printful $printful;

    /**
     * @var ServiceInterface|MockObject
     */
    protected MockObject $service;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->getMockBuilder('Sarvan\Printful\API\Stub')
            ->disableOriginalConstructor()
            ->getMock();
        $this->printful = new Printful($this->service);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->printful);
        unset($this->service);
    }

    public function testGetShippingRates(): void
    {
        $this->service->expects($this->once())->method('getShippingRates')
            ->will($this->returnValue('output'));
        $body = '{}';
        $output = $this->printful->getShippingRates($body);

        $this->assertEquals('output', $output);
    }

    public function testGetShippingRatesException()
    {
        $this->expectException('Sarvan\Printful\API\Exceptions\ServiceException');

        $serviceMock = $this->service;
        $serviceMock->expects($this->once())->method('getShippingRates')
            ->will($this->throwException(new ServiceException));

        $client = new Printful($serviceMock);
        $client->getShippingRates('some-options');

    }
}
