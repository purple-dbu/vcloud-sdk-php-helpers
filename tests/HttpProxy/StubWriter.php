<?php

namespace Test\HttpProxy;

use Zend\Json\Json;

class StubWriter implements \SplObserver
{
    protected $directory;
    protected $hosts;
    protected $excludeRequestHeaders;
    protected $excludeResponseHeaders;

    public function __construct($directory, $hosts, $excludeRequestHeaders, $excludeResponseHeaders)
    {
        $this->directory = $directory;
        $this->hosts = $hosts;
        $this->excludeRequestHeaders = $excludeRequestHeaders;
        $this->excludeResponseHeaders = $excludeResponseHeaders;
    }

    public function update(\SplSubject $subject)
    {
        $event = $subject->getLastEvent();
        if ($event['name'] === 'receivedBody') {

            $requestHeaders = $subject->getHeaders();
            foreach ($this->excludeRequestHeaders as $header) {
                unset($requestHeaders[$header]);
            }

            $url = clone $subject->getUrl();
            if (array_key_exists($url->getHost(), $this->hosts)) {
                $url->setHost($this->hosts[$url->getHost()]);
            }

            $request = $this->getRequestAsString(
                $url->__toString(),
                $subject->getMethod(),
                $requestHeaders,
                null
            );

            $responseHeaders = $event['data']->getHeader();
            foreach ($this->excludeResponseHeaders as $header) {
                unset($responseHeaders[$header]);
            }

            $response = $this->getResponseAsString(
                $event['data']->getStatus(),
                $event['data']->getReasonPhrase(),
                $responseHeaders,
                $event['data']->getBody()
            );

            $filename = $this->hash($request);

            if (!is_dir($this->directory)) {
                mkdir($this->directory, 0770, true);
            }
            file_put_contents($this->directory . DIRECTORY_SEPARATOR . $filename . '-request.json', $request);
            file_put_contents($this->directory . DIRECTORY_SEPARATOR . $filename . '.json', $response);
        }
    }

    public function getRequestAsString($url, $method, $headers, $body)
    {
        return Json::prettyPrint(
            Json::encode(array($url, $method, $headers, $body))
        );
    }

    public function getResponseAsString($status, $reasonPhrase, $headers, $body)
    {
        return Json::prettyPrint(
            Json::encode(array($status, $reasonPhrase, $headers, $body))
        );
    }

    public function hash($requestString)
    {
        return md5($requestString);
    }
}
