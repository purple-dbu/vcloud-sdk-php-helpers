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

    public function testCreateUserObjectReferenceWithoutName()
    {
        $reference = \VCloud\Helpers\Service::create($this->service)->createReference(
            $this->config->data->service->sampleUserObject->type,
            $this->config->data->service->sampleUserObject->href
        );

        $this->assertEquals('VMware_VCloud_API_ReferenceType', get_class($reference));
        $this->assertEquals('VMware_VCloud_SDK_Org', get_class($this->service->createSDKObj($reference)));
    }

    public function testCreateUserObjectReferenceWithName()
    {
        $reference = \VCloud\Helpers\Service::create($this->service)->createReference(
            $this->config->data->service->sampleUserObject->type,
            $this->config->data->service->sampleUserObject->href,
            $this->config->data->service->sampleUserObject->name
        );

        $this->assertEquals('VMware_VCloud_API_ReferenceType', get_class($reference));
        $this->assertEquals($this->config->data->service->sampleUserObject->name, $reference->get_name());
        $this->assertEquals('VMware_VCloud_SDK_Org', get_class($this->service->createSDKObj($reference)));
    }

    public function testCreateAdminObjectReferenceWithoutName()
    {
        $reference = \VCloud\Helpers\Service::create($this->service)->createReference(
            $this->config->data->service->sampleAdminObject->type,
            $this->config->data->service->sampleAdminObject->href
        );

        $this->assertEquals('VMware_VCloud_API_ReferenceType', get_class($reference));
        $this->assertEquals('VMware_VCloud_SDK_User', get_class($this->service->createSDKObj($reference)));
    }

    public function testCreateAdminObjectReferenceWithName()
    {
        $reference = \VCloud\Helpers\Service::create($this->service)->createReference(
            $this->config->data->service->sampleAdminObject->type,
            $this->config->data->service->sampleAdminObject->href,
            $this->config->data->service->sampleAdminObject->name
        );

        $this->assertEquals('VMware_VCloud_API_ReferenceType', get_class($reference));
        $this->assertEquals($this->config->data->service->sampleAdminObject->name, $reference->get_name());
        $this->assertEquals('VMware_VCloud_SDK_User', get_class($this->service->createSDKObj($reference)));
    }
}
