<?php

namespace Test\VCloud\Helpers\Unit;

class ServiceTest extends \PHPUnit_Framework_TestCase
{
    protected $config;
    protected $services;

    public function setUp()
    {
        global $services, $config;
        $this->config = $config;
        $this->services = $services;
    }

    public function testServicesAreNotIdentical()
    {
        $names = array_keys($this->services);
        foreach ($names as $a) {
            foreach ($names as $b) {
                if ($a !== $b) {
                    $this->assertNotEquals($this->services[$a], $this->services[$b]);
                }
            }
        }
    }

    public function testCreateUserObjectReferenceWithoutName()
    {
        $reference = \VCloud\Helpers\Service::create($this->services['cloudAdministrator'])->createReference(
            $this->config->data->service->sampleUserObject->type,
            $this->config->data->service->sampleUserObject->href
        );

        $this->assertEquals('VMware_VCloud_API_ReferenceType', get_class($reference));
        $this->assertEquals(
            'VMware_VCloud_SDK_Org',
            get_class($this->services['cloudAdministrator']->createSDKObj($reference))
        );
    }

    public function testCreateUserObjectReferenceWithName()
    {
        $reference = \VCloud\Helpers\Service::create($this->services['cloudAdministrator'])->createReference(
            $this->config->data->service->sampleUserObject->type,
            $this->config->data->service->sampleUserObject->href,
            $this->config->data->service->sampleUserObject->name
        );

        $this->assertEquals('VMware_VCloud_API_ReferenceType', get_class($reference));
        $this->assertEquals($this->config->data->service->sampleUserObject->name, $reference->get_name());
        $this->assertEquals(
            'VMware_VCloud_SDK_Org',
            get_class($this->services['cloudAdministrator']->createSDKObj($reference))
        );
    }

    public function testCreateAdminObjectReferenceWithoutName()
    {
        $reference = \VCloud\Helpers\Service::create($this->services['cloudAdministrator'])->createReference(
            $this->config->data->service->sampleAdminObject->type,
            $this->config->data->service->sampleAdminObject->href
        );

        $this->assertEquals('VMware_VCloud_API_ReferenceType', get_class($reference));
        $this->assertEquals(
            'VMware_VCloud_SDK_User',
            get_class($this->services['cloudAdministrator']->createSDKObj($reference))
        );
    }

    public function testCreateAdminObjectReferenceWithName()
    {
        $reference = \VCloud\Helpers\Service::create($this->services['cloudAdministrator'])->createReference(
            $this->config->data->service->sampleAdminObject->type,
            $this->config->data->service->sampleAdminObject->href,
            $this->config->data->service->sampleAdminObject->name
        );

        $this->assertEquals('VMware_VCloud_API_ReferenceType', get_class($reference));
        $this->assertEquals($this->config->data->service->sampleAdminObject->name, $reference->get_name());
        $this->assertEquals(
            'VMware_VCloud_SDK_User',
            get_class($this->services['cloudAdministrator']->createSDKObj($reference))
        );
    }

    public function testGetCurrentUserName()
    {
        foreach ($this->config['users'] as $name => $userConfig) {
            $this->assertEquals(
                strtolower($userConfig['username']),
                strtolower(\VCloud\Helpers\Service::create($this->services[$name])->getCurrentUserName())
            );
        }
    }

    public function testGetCurrentOrganizationName()
    {
        foreach ($this->config['users'] as $name => $userConfig) {
            $this->assertEquals(
                strtolower($userConfig['organization']),
                strtolower(\VCloud\Helpers\Service::create($this->services[$name])->getCurrentOrganizationName())
            );
        }
    }

    public function testGetCurrentUser()
    {
        foreach ($this->config['users'] as $name => $userConfig) {
            $this->assertEquals(
                strtolower($userConfig['username']),
                strtolower(
                    \VCloud\Helpers\Service::create($this->services[$name])
                        ->getCurrentUser()->getUser()->get_name()
                )
            );
        }
    }

    public function testGetCurrentOrganization()
    {
        foreach ($this->config['users'] as $name => $userConfig) {
            $this->assertEquals(
                strtolower($userConfig['organization']),
                strtolower(
                    \VCloud\Helpers\Service::create($this->services[$name])
                        ->getCurrentOrganization()->getOrg()->get_name()
                )
            );
        }
    }
}
