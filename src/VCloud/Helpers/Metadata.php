<?php

namespace VCloud\Helpers;

class Metadata
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

    protected static function doesEntryMatch(\VMware_VCloud_API_MetadataEntryType $entry, $metadataName, $metadataValue)
    {
        return $entry->getKey() === $metadataName
            && ($metadataValue === null
                || $entry->getTypedValue() && $metadataValue === $entry->getTypedValue()->getValue()
            );
    }

    protected static function doesObjectMatch($object, $metadataName, $metadataValue)
    {
        foreach ($object->getMetadata()->getMetadataEntry() as $entry) {
            if (self::doesEntryMatch($entry, $metadataName, $metadataValue)) {
                return true;
            }
        }
        return false;
    }

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
