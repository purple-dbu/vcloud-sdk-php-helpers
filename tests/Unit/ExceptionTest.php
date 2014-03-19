<?php

namespace Test\VCloud\Helpers\Unit;

class ExceptionTestCase extends \PHPUnit_Framework_TestCase
{
    protected $config;
    protected $services;
    protected $e;
    protected $id;

    public function setUp()
    {
        global $services, $config;
        $this->config = $config;
        $this->services = $services;
        $this->id = $this->config->unknownOrganization;

        $reference = new \VMware_VCloud_API_ReferenceType();
        $reference->set_href('https://' . $this->config->host . '/api/org/' . $this->id);
        $reference->set_type('application/vnd.vmware.vcloud.org+xml');

        $unknownOrganization = $services['cloudAdministrator']->createSdkObj($reference);

        try {
            $unknownOrganization->getOrg();
            throw new \RuntimeException(
                'Failed generating SDK Exception during setup, organization "'
                . $this->id . '" exists where it shouldn\'t'
            );
        } catch (\VMware_VCloud_SDK_Exception $e) {
            $this->e1 = $e;
        }

        $this->e2 = new \VMware_VCloud_SDK_Exception(
            'POST https://... failed, return code: 406, error: No valid API version can be selected.'
        );
    }

    public function testGetOriginalException()
    {
        $this->assertEquals($this->e1, \VCloud\Helpers\Exception::create($this->e1)->getOriginalException());
        $this->assertEquals($this->e2, \VCloud\Helpers\Exception::create($this->e2)->getOriginalException());
    }

    public function testGetMessage()
    {
        $this->assertEquals(
            'The VCD entity com.vmware.vcloud.entity.org:' . $this->id . ' does not exist.',
            \VCloud\Helpers\Exception::create($this->e1)->getMessage()
        );
        $this->assertEquals(
            'POST https://... failed, return code: 406, error: No valid API version can be selected.',
            \VCloud\Helpers\Exception::create($this->e2)->getMessage()
        );
    }

    public function testGetMajorErrorCode()
    {
        $this->assertEquals(
            '403',
            \VCloud\Helpers\Exception::create($this->e1)->getMajorErrorCode()
        );
        $this->assertEquals(
            '',
            \VCloud\Helpers\Exception::create($this->e2)->getMajorErrorCode()
        );
    }

    public function testGetMinorErrorCode()
    {
        $this->assertEquals(
            'ACCESS_TO_RESOURCE_IS_FORBIDDEN',
            \VCloud\Helpers\Exception::create($this->e1)->getMinorErrorCode()
        );
        $this->assertEquals(
            '',
            \VCloud\Helpers\Exception::create($this->e2)->getMinorErrorCode()
        );
    }

    public function testGetStackTrace()
    {
        $this->assertEquals(
            158,
            count(explode("\n", \VCloud\Helpers\Exception::create($this->e1)->getStackTrace()))
        );
        $this->assertEquals(
            1,
            count(explode("\n", \VCloud\Helpers\Exception::create($this->e2)->getStackTrace()))
        );
    }
}
