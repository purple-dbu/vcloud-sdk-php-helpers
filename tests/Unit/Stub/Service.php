<?php

namespace Test\VCloud\Helpers\Unit\Stub;

class Service extends \VMware_VCloud_SDK_Service
{
    public function __construct()
    {
    }

    public function getQueryService()
    {
        return new QueryService();
    }
}
