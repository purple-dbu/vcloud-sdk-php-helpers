<?php

namespace Test\VCloud\Helpers\Unit;

class ExceptionTestCase extends \PHPUnit_Framework_TestCase
{
    protected $config;
    protected $service;
    protected $e;
    protected $id;

    public function setUp()
    {
        global $service, $config;
        $this->config = $config;
        $this->service = $service;
        $this->id = $this->config->unknownOrganization;

        $reference = new \VMware_VCloud_API_ReferenceType();
        $reference->set_href('https://' . $this->config->host . '/api/org/' . $this->id);
        $reference->set_type('application/vnd.vmware.vcloud.org+xml');

        $unknownOrganization = $service->createSdkObj($reference);

        try {
            $unknownOrganization->getOrg();
            throw new \RuntimeException(
                'Failed generating SDK Exception during setup, organization "'
                . $this->id . '" exists where it shouldn\'t'
            );
        } catch (\VMware_VCloud_SDK_Exception $e) {
            $this->e = $e;
        }
    }

    public function testGetOriginalException()
    {
        $this->assertEquals($this->e, \VCloud\Helpers\Exception::create($this->e)->getOriginalException());
    }

    public function testGetMessage()
    {
        $this->assertEquals(
            'The VCD entity com.vmware.vcloud.entity.org:' . $this->id . ' does not exist.',
            \VCloud\Helpers\Exception::create($this->e)->getMessage()
        );
    }

    public function testGetMajorErrorCode()
    {
        $this->assertEquals(
            '403',
            \VCloud\Helpers\Exception::create($this->e)->getMajorErrorCode()
        );
    }

    public function testGetMinorErrorCode()
    {
        $this->assertEquals(
            'ACCESS_TO_RESOURCE_IS_FORBIDDEN',
            \VCloud\Helpers\Exception::create($this->e)->getMinorErrorCode()
        );
    }

    public function testGetStackTrace()
    {
        $this->assertEquals(
            158,
            count(explode("\n", \VCloud\Helpers\Exception::create($this->e)->getStackTrace()))
        );
    }
}
