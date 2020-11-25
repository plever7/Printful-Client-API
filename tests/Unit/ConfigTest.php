<?php

namespace Tests\Unit\Sarvan\Printful;

use Sarvan\Printful\Config;
use Tests\TestCase;

/**
 * Class ConfigTest.
 *
 * @covers \Sarvan\Printful\Config
 */
class ConfigTest extends TestCase
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        /** @todo Correctly instantiate tested object to use it. */
        $this->config = new Config();
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->config);
    }

    public function testGetRoute(): void
    {
        /** @todo This test is incomplete. */
        $this->markTestIncomplete();
    }
}
