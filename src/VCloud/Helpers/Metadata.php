<?php

namespace VCloud\Helpers;

/**
 * The Metadata Helper gives you the ability to manipulate metadata on vCloud
 * objects with ease. It helps finding objects with a particular metadata (to
 * either one particular value, or any value).
 */
class Metadata
{
    /**
     * @var \VMware_VCloud_SDK_Service vCloud Director SDK Service
     */
    protected $service;

    /**
     * Create a new Metadata Helper
     * @param \VMware_VCloud_SDK_Service $service The vCloud Director SDK Service
     */
    public function __construct(\VMware_VCloud_SDK_Service $service)
    {
        $this->service = $service;
    }

    /**
     * Create a new Metadata Helper and returns it without modifications. This
     * form allow chaining in ALL versions of PHP:
     *
     *     \VCloud\Helpers\Metadata::create($service)->queryRecords(...)
     *
     * Since PHP 5.4, Class member access on instantiation is allowed:
     *
     *     new (\VCloud\Helpers\Metadata($service))->queryRecords(...)
     *
     * @param \VMware_VCloud_SDK_Service $service The vCloud Director SDK Service
     */
    public static function create(\VMware_VCloud_SDK_Service $service)
    {
        return new self($service);
    }

    /**
     * Determine whether a metadata entry has a given name, and optionally, a given value.
     *
     * @param \VMware_VCloud_API_MetadataEntryType $entry         The metadata entry to test
     * @param string                               $metadataName  The expected name
     * @param string                               $metadataValue The expected value (optional)
     * @return boolean Returns `true` if the entry has the expected name (and the expected value, if given)
     */
    protected static function doesEntryMatch(
        \VMware_VCloud_API_MetadataEntryType $entry,
        $metadataName,
        $metadataValue = null
    ) {
        return $entry->getKey() === $metadataName
            && ($metadataValue === null
                || $entry->getTypedValue() && $metadataValue === $entry->getTypedValue()->getValue()
            );
    }

    /**
     * Determine whether a vCloud object contains a metadata having a given name,
     * and optionally, a given value.
     *
     * @param mixed  $object        The vCloud object to test
     * @param string $metadataName  The expected name
     * @param string $metadataValue The expected value (optional)
     * @return boolean Returns `true` if the object contains a metadata with the
     * expected name (and the expected value, if given)
     */
    protected static function doesObjectMatch($object, $metadataName, $metadataValue)
    {
        foreach ($object->getMetadata()->getMetadataEntry() as $entry) {
            if (self::doesEntryMatch($entry, $metadataName, $metadataValue)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get all object of a given type containing a metadata having a given name,
     * and optionally, a given value.
     *
     * @param string $type           The expected query type, see Query helper
     * @param string $metadataName   The expected name
     * @param string $metadataValue  The expected value (optional)
     * @return array Returns all objects containing a metadata with the expected
     * name (and the expected value, if given)
     */
    public function getObjects($type, $metadataName, $metadataValue = null)
    {
        $objects = array();
        $queryHelper = new Query($this->service->getQueryService());
        foreach ($queryHelper->queryReferences($type) as $reference) {
            $object = $this->service->createSDKObj($reference);
            if (self::doesObjectMatch($object, $metadataName, $metadataValue)) {
                array_push($objects, $object);
            }
        }
        return $objects;
    }

    /**
     * Get the first object of a given type containing a metadata having a given
     * name, and optionally, a given value.
     *
     * @param string $type           The expected query type, see Query helper
     * @param string $metadataName   The expected name
     * @param string $metadataValue  The expected value (optional)
     * @return mixed Returns the first objects containing a metadata with the
     * expected name (and the expected value, if given)
     */
    public function getObject($type, $metadataName, $metadataValue = null)
    {
        $queryHelper = new Query($this->service->getQueryService());
        foreach ($queryHelper->queryReferences($type) as $reference) {
            $object = $this->service->createSDKObj($reference);
            if (self::doesObjectMatch($object, $metadataName, $metadataValue)) {
                return $object;
            }
        }
        return false;
    }
}
