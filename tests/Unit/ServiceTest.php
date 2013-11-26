<?php

namespace Test\VCloud\Helpers\Unit;

class ServiceTest extends \PHPUnit_Framework_TestCase
{
    protected $config;
    protected $service;

    public function setUp()
    {
        global $service, $config;
        $this->config = $config;
        $this->service = $service;
    }

    public function testCreateReferenceWithoutName()
    {
        $reference = \VCloud\Helpers\Service::create($this->service)->createReference(
            $this->config->data->service->sampleObject->type,
            $this->config->data->service->sampleObject->href
        );

        $this->assertEquals('VMware_VCloud_API_ReferenceType', get_class($reference));
        $this->assertEquals('VMware_VCloud_SDK_User', get_class($this->service->createSDKObj($reference)));
    }

    public function testCreateReferenceWithName()
    {
        $reference = \VCloud\Helpers\Service::create($this->service)->createReference(
            $this->config->data->service->sampleObject->type,
            $this->config->data->service->sampleObject->href,
            $this->config->data->service->sampleObject->name
        );

        $this->assertEquals('VMware_VCloud_API_ReferenceType', get_class($reference));
        $this->assertEquals($this->config->data->service->sampleObject->name, $reference->get_name());
        $this->assertEquals('VMware_VCloud_SDK_User', get_class($this->service->createSDKObj($reference)));
    }
}
