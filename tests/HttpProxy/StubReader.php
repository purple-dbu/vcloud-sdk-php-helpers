<?php

namespace Test\HttpProxy;

use Zend\Json\Json;

class StubReader extends \VMware_VCloud_SDK_Http_Client
{
    protected $directory;
    protected $hosts;
    protected $excludeRequestHeaders;
    protected $excludeResponseHeaders;

    public function __construct($config)
    {
        $this->directory = $config['directory'];
        $this->hosts = $config['hosts'];
        $this->excludeRequestHeaders = $config['excludeRequestHeaders'];
        $this->excludeResponseHeaders = $config['excludeResponseHeaders'];
    }

    protected function sendRequest($url, $method, $headers = null, $body = null)
    {
        $headers['Accept'] = \VMware_VCloud_SDK_Constants::VCLOUD_ACCEPT_HEADER . ';' . 'version=' . $this->apiVersion;

        $url = new \Net_URL2($url);
        if (array_key_exists($url->getHost(), $this->hosts)) {
            $url->setHost($this->hosts[$url->getHost()]);
        }

        if ($this->authToken) {
            $headers[\VMware_VCloud_SDK_Constants::VCLOUD_AUTH_TOKEN] = $this->authToken;
        }

        $request = StubWriter::getRequestAsString(
            $url->__toString(),
            $method,
            $headers,
            $body,
            $this->excludeRequestHeaders
        );

        $filename = StubWriter::hash($request);
        $fullPath = $this->directory . DIRECTORY_SEPARATOR . $filename . '.json';
        if (!file_exists($fullPath)) {
            throw new \Exception(
                'Cannot find file ' . $fullPath . ' for ' . $method . ' ' . $url . ':' . "\n"
                . $request
            );
        }

        $responseArray = Json::decode(file_get_contents($fullPath), Json::TYPE_ARRAY);

        if (!$this->authToken) {
            $this->authToken = $responseArray[2][strtolower(\VMware_VCloud_SDK_Constants::VCLOUD_AUTH_TOKEN)];
        }

        $response = new \HTTP_Request2_Response('HTTP/1.1 ' . $responseArray[0] . ' ' . $responseArray[1]);
        foreach ($responseArray[2] as $name => $value) {
            $response->parseHeaderLine($name . ': ' . $value);
        }
        $response->appendBody($responseArray[3]);
        return $response;
    }
}
