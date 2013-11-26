<?php

namespace VCloud\Helpers;

class Service
{
    protected $service;

    public function __construct(\VMware_VCloud_SDK_Service $service)
    {
        $this->service = $service;
    }

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
        $object->set_type('application/vnd.vmware.' . $type . '+xml');
        return $object;
    }
}
