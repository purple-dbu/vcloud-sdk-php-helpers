<?php

namespace Test\HttpProxy;

class Client extends \VMware_VCloud_SDK_Http_Client
{
    public function getRequest()
    {
        return $this->request;
    }
}
