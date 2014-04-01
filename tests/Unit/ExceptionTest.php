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

        $this->e3 = new \VMware_VCloud_SDK_Exception(
            'POST https://vcd-demo.ecocenter.fr/api/vdc/1bb8f0da-7c38-492c-b3e7-82cb3141f106/media failed,'
            . ' return code: 400, error:'
            . ' <?xml version="1.0" encoding="UTF-8"?>'
            . ' <Error xmlns="http://www.vmware.com/vcloud/v1.5"'
            . ' minorErrorCode="DUPLICATE_NAME"'
            . ' message="The VCD entity MY-MEDIA already exists."'
            . ' majorErrorCode="400"'
            . ' xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"'
            . ' xsi:schemaLocation="http://www.vmware.com/vcloud/v1.5'
            . ' http://vcd-demo.ecocenter.fr/api/v1.5/schema/master.xsd"></Error>'
            . ' , request data:'
            . ' <Media xmlns="http://www.vmware.com/vcloud/v1.5"'
            . ' xmlns:vcloud="http://www.vmware.com/vcloud/v1.5"'
            . ' xmlns:ns12="http://www.vmware.com/vcloud/v1.5"'
            . ' xmlns:ovf="http://schemas.dmtf.org/ovf/envelope/1"'
            . ' xmlns:ovfenv="http://schemas.dmtf.org/ovf/environment/1"'
            . ' xmlns:vmext="http://www.vmware.com/vcloud/extension/v1.5"'
            . ' xmlns:cim="http://schemas.dmtf.org/wbem/wscim/1/common"'
            . ' xmlns:rasd="http://schemas.dmtf.org/wbem/wscim/1/cim-schema/2/CIM_ResourceAllocationSettingData"'
            . ' xmlns:vssd="http://schemas.dmtf.org/wbem/wscim/1/cim-schema/2/CIM_VirtualSystemSettingData"'
            . ' xmlns:vmw="http://www.vmware.com/schema/ovf"'
            . ' xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"'
            . ' name="MY-MEDIA"'
            . ' imageType="iso"'
            . ' size="101687296"/>'
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
        $this->assertEquals(
            'The VCD entity MY-MEDIA already exists.',
            \VCloud\Helpers\Exception::create($this->e3)->getMessage()
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
        $this->assertEquals(
            '400',
            \VCloud\Helpers\Exception::create($this->e3)->getMajorErrorCode()
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
        $this->assertEquals(
            'DUPLICATE_NAME',
            \VCloud\Helpers\Exception::create($this->e3)->getMinorErrorCode()
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
        $this->assertEquals(
            1,
            count(explode("\n", \VCloud\Helpers\Exception::create($this->e3)->getStackTrace()))
        );
    }
}
