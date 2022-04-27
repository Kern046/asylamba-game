<?php

// ############### #
// Benchmark class #
// ############### #
//
// Use this class to know script's time execution with precision
//
// @author Gil Clavien
// @version 0.2

namespace App\Classes\Library;

class Benchtime
{
    // time storage
    protected $name;
    protected $time = [];

    // params variables
    public const TXT = 1;
    public const HTML = 2;
    public const PHP = 3;

    public function __construct($name = 'Default')
    {
        $this->name = $name;
        $this->makePoint('Start');
    }

    // démarre un nouveau temps
    public function makePoint($name = false)
    {
        $this->time[] = [
            'name' => ucfirst($name ? $name : 'Breakpoint'),
            'time' => $this->getMicrotime(),
        ];
    }

    public function getResult($mode = false)
    {
        $interval = $this->transform();
        $mode = $mode ? $mode : self::HTML;
        $ret = '';

        if (self::TXT == $mode) {
            $ret .= $this->name."\r\n";
            $ret .= '-------------------'."\r\n";
            $ret .= '   | time  | name  '."\r\n";
            $ret .= '---|-------|-------'."\r\n";

            foreach ($interval as $k => $v) {
                $ret .= '#'.($k + 1).' | '.number_format($v['time'], 3, '.', ' ').' | '.$v['name']."\r\n";
            }
        } elseif (self::HTML == $mode) {
            $ret .= '<table>';
            $ret .= '<tr>';
            $ret .= '<th></th>';
            $ret .= '<th>time</th>';
            $ret .= '<th>name</th>';
            $ret .= '</tr>';

            foreach ($interval as $k => $v) {
                $ret .= '<tr>';
                $ret .= '<td>#'.($k + 1).'</td>';
                $ret .= '<td>'.number_format($v['time'], 3, '.', ' ').'</td>';
                $ret .= '<td>'.$v['name'].'</td>';
                $ret .= '</tr>';
            }
            $ret .= '</table>';
        } elseif (self::PHP == $mode) {
            $ret = $interval;
        }

        return $ret;
    }

    protected function transform()
    {
        $interval = [];

        for ($i = 1; $i < count($this->time); ++$i) {
            $interval[] = [
                'name' => $this->time[$i]['name'],
                'time' => $this->time[$i]['time'] - $this->time[$i - 1]['time'],
            ];
        }

        return $interval;
    }

    protected function getMicrotime()
    {
        list($usec, $sec) = explode(' ', microtime());

        return (float) $usec + (float) $sec;
    }
}
