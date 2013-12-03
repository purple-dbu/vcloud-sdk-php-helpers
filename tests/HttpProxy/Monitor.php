<?php

namespace Test\HttpProxy;

use Zend\Json\Json;

class Monitor implements \SplObserver
{
    protected $currentRequestStartTime;
    protected $requests;
    protected $report;
    protected $lineCount;

    public function __construct($lineCount = 5)
    {
        $this->requests = array();
        $this->lineCount = $lineCount;
    }

    public function update(\SplSubject $subject)
    {
        $event = $subject->getLastEvent();

        // Record time on request sent and log on request receive
        if ($event['name'] === 'sentHeaders' || $event['name'] === 'sentBody') {
            $this->currentRequestStartTime = microtime(true);
        } elseif ($event['name'] === 'receivedBody') {
            array_push(
                $this->requests,
                array(
                    'url' => preg_replace('/^https?:\/\/[^\/]+(.*)$/', '$1', $subject->getUrl()->__toString()),
                    'method' => $subject->getMethod(),
                    'time' => microtime(true) - $this->currentRequestStartTime,
                )
            );
        }
    }

    protected function getReport()
    {
        if ($this->report === null) {

            $this->report = array(
                'count' => 0,
                'time' => 0,
                'requests' => array(),
            );

            foreach ($this->requests as $request) {
                $id = str_pad($request['method'], 4) . ' ' . $request['url'];

                if (!array_key_exists($id, $this->report['requests'])) {
                    $this->report['requests'][$id] = array(
                        'id' => $id,
                        'count' => 0,
                        'time' => 0,
                    );
                }

                $this->report['count']++;
                $this->report['time'] += $request['time'];

                $this->report['requests'][$id]['count']++;
                $this->report['requests'][$id]['time'] += $request['time'];
            }

            usort($this->report['requests'], function ($a, $b) {
                return $a['time'] === $b['time'] ? 0 : ($a['time'] < $b['time'] ? 1 : -1);
            });
        }
        return $this->report;
    }

    public function displayReport()
    {
        echo "================================ HTTP Requests =================================\n";
        $report = $this->getReport();
        $requests = $report['requests'];
        $keys = array_keys($requests);
        for ($i = 0; ($this->lineCount === 0 || $i <= $this->lineCount) && $i < count($requests); $i++) {
            $id = $keys[$i];
            echo str_pad(round(1000 * $requests[$i]['time']), 5, ' ', STR_PAD_LEFT). ' ms   '
                . str_pad($requests[$i]['count'], 3, ' ', STR_PAD_LEFT) . ' × '
                . str_pad(round(1000 * $requests[$i]['time']/$requests[$i]['count']), 4, ' ', STR_PAD_LEFT) . ' ms   '
                . $requests[$i]['id'] . "\n";
        }
        echo "--------------------------------------------------------------------------------\n";
        echo str_pad(round(1000 * $report['time']), 5, ' ', STR_PAD_LEFT). ' ms   '
            . str_pad($report['count'], 3, ' ', STR_PAD_LEFT) . ' × '
            . str_pad(round(1000 * $report['time']/$report['count']), 4, ' ', STR_PAD_LEFT) . ' ms   '
            . "TOTAL\n";
        echo "================================================================================\n";
    }

    public function __destruct()
    {
        $this->displayReport(5);
    }
}
