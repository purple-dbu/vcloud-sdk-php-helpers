<?php

namespace Test\VCloud\Helpers\Unit;

class RightTest extends \PHPUnit_Framework_TestCase
{
    protected $config;
    protected $services;

    public function setUp()
    {
        global $services, $config;
        $this->config = $config;
        $this->services = $services;
    }

    public function testIsCurrentUserOrganizationAdminOnCloudAdmininistrator()
    {
        $this->assertTrue(
            \VCloud\Helpers\Right::create($this->services['cloudAdministrator'])
                ->isCurrentUserOrganizationAdmin()
        );
    }

    public function testIsCurrentUserOrganizationAdminOnOrganizationAdmininistrator()
    {
        $this->assertTrue(
            \VCloud\Helpers\Right::create($this->services['organizationAdministrator'])
                ->isCurrentUserOrganizationAdmin()
        );
    }

    public function testIsCurrentUserOrganizationAdminOnVAppAuthor()
    {
        $this->assertFalse(
            \VCloud\Helpers\Right::create($this->services['vAppAuthor'])
                ->isCurrentUserOrganizationAdmin()
        );
    }

    public function testIsCurrentUserOrganizationAdminOnConsoleOnly()
    {
        $this->assertFalse(
            \VCloud\Helpers\Right::create($this->services['consoleOnly'])
                ->isCurrentUserOrganizationAdmin()
        );
    }

    public function testIsCurrentUserOrganizationAdminOnSandboxServiceUser()
    {
        $this->assertFalse(
            \VCloud\Helpers\Right::create($this->services['sandboxServiceUser'])
                ->isCurrentUserOrganizationAdmin()
        );
    }

    public function testIsCurrentUserOrganizationAdminOnLdapOrganizationAdmin()
    {
        $this->assertTrue(
            \VCloud\Helpers\Right::create($this->services['ldapOrganizationAdmin'])
                ->isCurrentUserOrganizationAdmin()
        );
    }

    /**
     * @expectedException Exception
     */
    public function testGetUnexistingRight()
    {
        \VCloud\Helpers\Right::create($this->services['cloudAdministrator'])
            ->getRightByName($this->config['right']['unexistingRightName']);
    }
}
