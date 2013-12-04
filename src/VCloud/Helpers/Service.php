<?php
/**
 * vCloud PHP SDK Helpers
 * @link (Github, https://github.com/amercier/vcloud-sdk-php)
 */

namespace VCloud\Helpers;

/**
 * The Right Helper provides various helper methods to facilitate using the
 * vCloud Director SDK's service objects.
 */
class Service
{
    /**
     * @var \VMware_VCloud_SDK_Service vCloud Director SDK Service
     */
    protected $service;

    /**
     * @var string Regular expression to extract UUID from any string
     */
    const PATTERN_UUID = '[0-9a-z]{8}-[0-9a-z]{4}-[0-9a-z]{4}-[0-9a-z]{4}-[0-9a-z]{12}';

    /**
     * Create a new Service Helper
     * @param \VMware_VCloud_SDK_Service $service The vCloud Director SDK Service
     * @return Service Returns a new Service Handler
     */
    public function __construct(\VMware_VCloud_SDK_Service $service)
    {
        $this->service = $service;
    }

    /**
     * Create a new Service Helper and returns it without modifications. This
     * form allow chaining in ALL versions of PHP:
     *
     *     \VCloud\Helpers\Service::create($service)->queryRecords(...)
     *
     * Since PHP 5.4, Class member access on instantiation is allowed:
     *
     *     new (\VCloud\Helpers\Service($service))->queryRecords(...)
     *
     * @param \VMware_VCloud_SDK_Service $service The vCloud Director SDK Service
     */
    public static function create(\VMware_VCloud_SDK_Service $service)
    {
        return new self($service);
    }

    /**
     * Create a vCloud SDK for PHP reference
     * @param string $type Object type
     * @param string $href Href of the object
     * @param string $name Name of the object
     */
    public function createReference($type, $href, $name = null)
    {
        $object = new \VMware_VCloud_API_ReferenceType();
        $object->set_href($href);

        if ($name != null) {
            $object->set_name($name);
        }

        if (preg_match('/^admin(.*)$/', $type, $matches)) {
            $object->set_type('application/vnd.vmware.admin.' . lcfirst($matches[1]) . '+xml');
        } else {
            $object->set_type('application/vnd.vmware.vcloud.' . $type . '+xml');
        }

        return $object;
    }

    /**
     * Get the UUID from any vCloud Director object. The id is extracted from
     * the href, so the given object must have a `get_href` method.
     *
     * @param  mixed  $object The source object
     * @return string         Returns the given object's UUID
     */
    public function getId($object)
    {
        return preg_replace('/.*(' . self::PATTERN_UUID . ')$/', '$1', $object->get_href());
    }

    /**
     * Get the currently logged user's name
     *
     * @return string Returns name as it's returned by vCloud Director (not
     * lowercased neither uppercased).
     */
    public function getCurrentUserName()
    {
        return $this->service->getSession()->get_user();
    }

    /**
     * Get the currently logged user's organization name
     *
     * @return string Returns name as it's returned by vCloud Director (not
     * lowercased neither uppercased).
     */
    public function getCurrentOrganizationName()
    {
        return $this->service->getSession()->get_org();
    }

    /**
     * Get the currently logged user's organization
     *
     * @return \VMware_VCloud_SDK_AdminOrg Returns the currently logged user's
     * organization
     */
    public function getCurrentOrganization()
    {
        $orgs = $this->service->createSDKAdminObj()->getAdminOrgs($this->getCurrentOrganizationName());
        return $this->service->createSDKObj($orgs[0]);
    }

    /**
     * Get the currently logged user
     *
     * @return \VMware_VCloud_SDK_User Returns the currently logged user
     */
    public function getCurrentUser()
    {
        $users = $this->getCurrentOrganization()->getUsers($this->getCurrentUserName());
        return $this->service->createSDKObj($users[0]);
    }
}
