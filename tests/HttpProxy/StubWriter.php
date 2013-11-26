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

            $url = clone $subject->getUrl();
            if (array_key_exists($url->getHost(), $this->hosts)) {
                $url->setHost($this->hosts[$url->getHost()]);
            }

            $request = self::getRequestAsString(
                $url->__toString(),
                $subject->getMethod(),
                $subject->getHeaders(),
                null,
                $this->excludeRequestHeaders
            );

            $response = self::getResponseAsString(
                $event['data']->getStatus(),
                $event['data']->getReasonPhrase(),
                $event['data']->getHeader(),
                $event['data']->getBody(),
                $this->excludeResponseHeaders
            );

            $filename = self::hash($request);

            if (!is_dir($this->directory)) {
                mkdir($this->directory, 0770, true);
            }
            file_put_contents($this->directory . DIRECTORY_SEPARATOR . $filename . '-request.json', $request);
            file_put_contents($this->directory . DIRECTORY_SEPARATOR . $filename . '.json', $response);
        }
    }

    public static function getRequestAsString($url, $method, $headers, $body, $excludeHeaders)
    {
        foreach ($excludeHeaders as $header) {
            unset($headers[$header]);
        }

        $realHeaders = array();
        foreach ($headers as $name => $value) {
            $realHeaders[ strtolower($name) ] = $value;
        }

        return Json::prettyPrint(
            Json::encode(array($url, $method, $realHeaders, $body))
        );
    }

    public static function getResponseAsString($status, $reasonPhrase, $headers, $body, $excludeHeaders)
    {
        foreach ($excludeHeaders as $header) {
            unset($headers[$header]);
        }

        $realHeaders = array();
        foreach ($headers as $name => $value) {
            $realHeaders[ strtolower($name) ] = $value;
        }

        return Json::prettyPrint(
            Json::encode(array($status, $reasonPhrase, $realHeaders, $body))
        );
    }

    public static function hash($requestString)
    {
        return md5($requestString);
    }
}
