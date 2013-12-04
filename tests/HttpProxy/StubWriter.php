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
                $this->excludeRequestHeaders,
                $this->hosts
            );

            $response = self::getResponseAsString(
                $event['data']->getStatus(),
                $event['data']->getReasonPhrase(),
                $event['data']->getHeader(),
                $event['data']->getBody(),
                $this->excludeResponseHeaders,
                $this->hosts
            );

            $filename = self::hash($request);

            if (!is_dir($this->directory)) {
                mkdir($this->directory, 0770, true);
            }
            file_put_contents($this->directory . DIRECTORY_SEPARATOR . $filename . '-request.json', $request);
            file_put_contents($this->directory . DIRECTORY_SEPARATOR . $filename . '.json', $response);
        }
    }

    public static function getRequestAsString($url, $method, $headers, $body, $excludeHeaders, $hosts)
    {
        $realHeaders = array();
        foreach ($headers as $name => $value) {
            if (!in_array(strtolower($name), $excludeHeaders)) {
                $realHeaders[ strtolower($name) ] = $value;
            }
        }

        foreach ($excludeHeaders as $header) {
            unset($realHeaders[ strtolower($header) ]);
        }

        foreach ($hosts as $host => $replacement) {
            $url = str_replace($host, $replacement, $url);
        }

        // Remove password from Authorization header, so password in config.php
        // and config.php.dist don't have do be the same
        if (array_key_exists('authorization', $realHeaders)) {
            if (preg_match('/^Basic (.*)$/', $realHeaders['authorization'], $authMatches)) {
                preg_match('/^(.*):(.*)$/', base64_decode($authMatches[1]), $credentials);
                $realHeaders['authorization'] = 'Basic ' . base64_encode($credentials[1] . ":");
            }
        }

        return Json::prettyPrint(
            Json::encode(array($url, $method, $realHeaders, $body))
        );
    }

    public static function getResponseAsString($status, $reasonPhrase, $headers, $body, $excludeHeaders, $hosts)
    {
        $realHeaders = array();
        foreach ($headers as $name => $value) {
            if (!in_array(strtolower($name), $excludeHeaders)) {
                $realHeaders[ strtolower($name) ] = $value;
            }
        }

        foreach ($hosts as $host => $replacement) {
            $body = str_replace($host, $replacement, $body);
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
