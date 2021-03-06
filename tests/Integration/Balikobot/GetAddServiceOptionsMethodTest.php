<?php

namespace Inspirum\Balikobot\Tests\Integration\Balikobot;

use Inspirum\Balikobot\Definitions\ServiceType;
use Inspirum\Balikobot\Definitions\Shipper;

class GetAddServiceOptionsMethodTest extends AbstractBalikobotTestCase
{
    public function testValidRequest()
    {
        $service = $this->newBalikobot();

        $options = $service->getAddServiceOptions(Shipper::CP);

        $this->assertTrue(count($options) > 0);
        foreach ($options as $serviceType => $serviceOptions) {
            $this->assertTrue(is_string($serviceType));
            foreach ($serviceOptions as $code => $option) {
                $this->assertTrue(is_int($code) || is_string($code));
                $this->assertTrue(is_string($option));
            }
        }
    }

    public function testValidRequestWithService()
    {
        $service = $this->newBalikobot();

        $options = $service->getAddServiceOptions(Shipper::CP, ServiceType::CP_CE);

        $this->assertTrue(count($options) > 0);
        foreach ($options as $code => $option) {
            $this->assertTrue(is_int($code));
            $this->assertTrue(is_string($option));
        }
    }

    public function testValidRequestWithFullData()
    {
        $service = $this->newBalikobot();

        $options = $service->getAddServiceOptions(Shipper::CP, null, true);

        $this->assertTrue(count($options) > 0);
        foreach ($options as $serviceType => $serviceOptions) {
            $this->assertTrue(is_string($serviceType));
            foreach ($serviceOptions as $code => $option) {
                $this->assertTrue(is_array($option));
                $this->assertTrue(is_string($option['name']));
                $this->assertTrue($option['code'] === (string) $code);
            }
        }
    }

    public function testValidRequestWithServiceWithFullData()
    {
        $service = $this->newBalikobot();

        $options = $service->getAddServiceOptions(Shipper::CP, ServiceType::CP_CE, true);

        $this->assertTrue(count($options) > 0);
        foreach ($options as $code => $option) {
            $this->assertTrue(is_array($option));
            $this->assertTrue(is_string($option['name']));
            $this->assertTrue($option['code'] === (string) $code);
        }
    }
}
