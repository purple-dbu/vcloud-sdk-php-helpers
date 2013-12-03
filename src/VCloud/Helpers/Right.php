<?php

namespace VCloud\Helpers;

class Right
{
    const CATALOG_ADD_VAPP_FROM_MY_CLOUD = 'Catalog: Add vApp from My Cloud';
    const CATALOG_CHANGE_OWNER = 'Catalog: Change Owner';
    const CATALOG_CREATE_DELETE_A_CATALOG = 'Catalog: Create / Delete a Catalog';
    const CATALOG_EDIT_PROPERTIES = 'Catalog: Edit Properties';
    const CATALOG_PUBLISH = 'Catalog: Publish';
    const CATALOG_SHARING = 'Catalog: Sharing';
    const CATALOG_VIEW_PRIVATE_AND_SHARED_CATALOGS = 'Catalog: View Private and Shared Catalogs';
    const CATALOG_VIEW_PUBLISHED_CATALOGS = 'Catalog: View Published Catalogs';
    const DISK_CREATE = 'Disk: Create';
    const DISK_DELETE = 'Disk: Delete';
    const DISK_EDIT_PROPERTIES = 'Disk: Edit Properties';
    const DISK_VIEW_PROPERTIES = 'Disk: View Properties';
    const GENERAL_ADMINISTRATOR_CONTROL = 'General: Administrator Control';
    const GENERAL_ADMINISTRATOR_VIEW = 'General: Administrator View';
    const GENERAL_SEND_NOTIFICATION = 'General: Send Notification';
    const GROUP_USER_VIEW = 'Group / User: View';
    const ORGANIZATION_NETWORK_EDIT_PROPERTIES = 'Organization Network: Edit Properties';
    const ORGANIZATION_NETWORK_VIEW = 'Organization Network: View';
    const ORGANIZATION_VDC_GATEWAY_CONFIGURE_SERVICES = 'Organization vDC Gateway: Configure Services';
    const ORGANIZATION_VDC_NETWORK_EDIT_PROPERTIES = 'Organization vDC Network: Edit Properties';
    const ORGANIZATION_VDC_NETWORK_VIEW = 'Organization vDC Network: View';
    const ORGANIZATION_VDC_STORAGE_PROFILE_SET_DEFAULT = 'Organization vDC Storage Profile: Set Default';
    const ORGANIZATION_VDC_VIEW = 'Organization vDC: View';
    const ORGANIZATION_EDIT_FEDERATION_SETTINGS = 'Organization: Edit Federation Settings';
    const ORGANIZATION_EDIT_LEASES_POLICY = 'Organization: Edit Leases Policy';
    const ORGANIZATION_EDIT_PASSWORD_POLICY = 'Organization: Edit Password Policy';
    const ORGANIZATION_EDIT_PROPERTIES = 'Organization: Edit Properties';
    const ORGANIZATION_EDIT_QUOTAS_POLICY = 'Organization: Edit Quotas Policy';
    const ORGANIZATION_EDIT_SMTP_SETTINGS = 'Organization: Edit SMTP Settings';
    const ORGANIZATION_VIEW = 'Organization: View';
    const VAPP_TEMPLATE_MEDIA_COPY = 'vApp Template / Media: Copy';
    const VAPP_TEMPLATE_MEDIA_CREATE_UPLOAD = 'vApp Template / Media: Create / Upload';
    const VAPP_TEMPLATE_MEDIA_EDIT = 'vApp Template / Media: Edit';
    const VAPP_TEMPLATE_MEDIA_VIEW = 'vApp Template / Media: View';
    const VAPP_TEMPLATE_CHECKOUT = 'vApp Template: Checkout';
    const VAPP_TEMPLATE_DOWNLOAD = 'vApp Template: Download';
    const VAPP_CHANGE_OWNER = 'vApp: Change Owner';
    const VAPP_COPY = 'vApp: Copy';
    const VAPP_CREATE_RECONFIGURE = 'vApp: Create / Reconfigure';
    const VAPP_DELETE = 'vApp: Delete';
    const VAPP_EDIT_PROPERTIES = 'vApp: Edit Properties';
    const VAPP_EDIT_VM_CPU = 'vApp: Edit VM CPU';
    const VAPP_EDIT_VM_HARD_DISK = 'vApp: Edit VM Hard Disk';
    const VAPP_EDIT_VM_MEMORY = 'vApp: Edit VM Memory';
    const VAPP_EDIT_VM_NETWORK = 'vApp: Edit VM Network';
    const VAPP_EDIT_VM_PROPERTIES = 'vApp: Edit VM Properties';
    const VAPP_MANAGE_VM_PASSWORD_SETTINGS = 'vApp: Manage VM Password Settings';
    const VAPP_POWER_OPERATIONS = 'vApp: Power Operations';
    const VAPP_SHARING = 'vApp: Sharing';
    const VAPP_SNAPSHOT_OPERATIONS = 'vApp: Snapshot Operations';
    const VAPP_USE_CONSOLE = 'vApp: Use Console';

    protected $service;
    protected $currentUserRights;
    protected $serviceHelper;
    protected $queryHelper;

    public function __construct(\VMware_VCloud_SDK_Service $service)
    {
        $this->service = $service;
    }

    public static function create(\VMware_VCloud_SDK_Service $service)
    {
        return new self($service);
    }

    protected function getServiceHelper()
    {
        if ($this->serviceHelper === null) {
            $this->serviceHelper = Service::create($this->service);
        }
        return $this->serviceHelper;
    }

    protected function getQueryHelper()
    {
        if ($this->queryHelper === null) {
            $this->queryHelper = Query::create($this->service->getQueryService());
        }
        return $this->queryHelper;
    }

    public function isCurrentUserOrganizationAdmin()
    {
        return $this->hasCurrentUserRights(
            array(
                self::GENERAL_ADMINISTRATOR_CONTROL,
                self::GENERAL_ADMINISTRATOR_VIEW
            )
        );
    }

    public function getAllRights()
    {
        return $this->getQueryHelper()->queryRecords(\VMware_VCloud_SDK_Query_Types::RIGHT);
    }

    public function getRightByName($name)
    {
        foreach ($this->getAllRights() as $right) {
            if ($right->get_name() === $name) {
                return $right;
            }
        }

        $names = array();
        foreach ($this->getAllRights() as $right) {
            array_push($names, $right->get_name());
        }
        sort($names);
        throw new \Exception(
            'Right ' . $name . ' does not exist. Right: '
            . "\n - " . implode("\n - ", $names) . "\n"
        );
    }

    public function hasCurrentUserRights($rights)
    {
        foreach ($rights as $right) {
            if (!$this->hasCurrentUserRight($right)) {
                return false;
            }
        }
        return true;
    }

    public function hasCurrentUserRight($right)
    {
        try {
            if (is_string($right)) {
                $right = $this->getRightByName($right);
            }

            $id = $this->getServiceHelper()->getId($right);
            foreach ($this->getCurrentUserRightReferences() as $r) {
                if ($this->getServiceHelper()->getId($r) === $id) {
                    return true;
                }
            }

        // If we've got a ACCESS_TO_RESOURCE_IS_FORBIDDEN (either while retieving all rights), we're assuming that
        // we the user doesn't have this right
        } catch (\VMware_VCloud_SDK_Exception $e) {
            // if (Exception::create($e)->getMinorErrorCode() === 'ACCESS_TO_RESOURCE_IS_FORBIDDEN') {
            //     return false;
            // } else {
                throw $e; // propagating the exception if this is not the "expected" one
            // }
        }

        return false;
    }

    public function getCurrentUserGroupReferences()
    {
        return $this->getCurrentUser()->getUser()->getGroupReferences()->getReference();
    }

    public function getCurrentUserRoles()
    {
        $roles = array();
        $user = $this->getServiceHelper()->getCurrentUser();
        if ($user->getUser()->getIsGroupRole()) {
            foreach ($this->getCurrentUserGroups() as $group) {
                array_push(
                    $roles,
                    $this->service->createSDKObj($group->getGroup()->getRole())
                );
            }
        } else {
            array_push(
                $roles,
                $this->service->createSDKObj($user->getUser()->getRole())
            );
        }
        return $roles;
    }

    public function getCurrentUserRightReferences()
    {
        $rights = array();
        foreach ($this->getCurrentUserRoles() as $role) {
            $rights = array_merge(
                $rights,
                $role->getRole()->getRightReferences()->getRightReference()
            );
        }
        return $rights;
    }
}
