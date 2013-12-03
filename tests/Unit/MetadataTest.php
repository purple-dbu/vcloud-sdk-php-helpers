<?php

namespace Test\VCloud\Helpers\Unit;

class MetadataTest extends \PHPUnit_Framework_TestCase
{
    protected $config;
    protected $services;

    public function setUp()
    {
        global $services, $config;
        $this->config = $config;
        $this->services = $services;
    }

    public function testGetObjectsWithoutValue()
    {
        $this->assertEquals(
            $this->config->metadata->countWithoutValue,
            count(
                \VCloud\Helpers\Metadata::create($this->services['cloudAdministrator'])->getObjects(
                    $this->config->metadata->type,
                    $this->config->metadata->name
                )
            )
        );
    }

    public function testGetObjectsWithValue()
    {
        $this->assertEquals(
            $this->config->metadata->countWithValue,
            count(
                \VCloud\Helpers\Metadata::create($this->services['cloudAdministrator'])->getObjects(
                    $this->config->metadata->type,
                    $this->config->metadata->name,
                    $this->config->metadata->value
                )
            )
        );
    }

    public function testGetObjectWithoutValue()
    {
        $this->assertEquals(
            'VMware_VCloud_SDK_VAppTemplate',
            get_class(
                \VCloud\Helpers\Metadata::create($this->services['cloudAdministrator'])->getObject(
                    $this->config->metadata->type,
                    $this->config->metadata->name
                )
            )
        );
    }

    public function testGetObjectWithValue()
    {
        $this->assertEquals(
            'VMware_VCloud_SDK_VAppTemplate',
            get_class(
                \VCloud\Helpers\Metadata::create($this->services['cloudAdministrator'])->getObject(
                    $this->config->metadata->type,
                    $this->config->metadata->name,
                    $this->config->metadata->value
                )
            )
        );
    }

    public function testGetObjectUnknown()
    {
        $this->assertEquals(
            false,
            \VCloud\Helpers\Metadata::create($this->services['cloudAdministrator'])->getObject(
                $this->config->metadata->type,
                $this->config->metadata->name,
                $this->config->metadata->unknownValue
            )
        );
    }
}
