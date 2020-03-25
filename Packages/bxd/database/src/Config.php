<?php

namespace BxdFramework\DataBase;

use BxdFramework\Base\Conf;

class Config
{
    // conn.db.trade.master.0.{'host':'','port':'',...}
    static private $data;

    const SEPARATOR = '.';

    static private function load ()
    {
        self::$data = ['conn' => []];
        foreach (Conf::get('mysql') as $db => $keys) {
            self::$data['conn'][$db] = [];
            foreach ($keys as $key => $cfgs) {
                self::$data['conn'][$db][$key] = [];
                foreach ($cfgs as $cfg) {
                    self::$data['conn'][$db][$key][] = [
                        'host' => $cfg['host'] ?? 'localhost',
                        'port' => $cfg['port'] ?? '80',
                        'user' => $cfg['user'] ?? '',
                        'pass' => $cfg['password'] ?? '',
                        'persistent' => $cfg['persistent'] ?? 0,
                        'expire' => $cfg['expire'] ?? null,
                    ];
                }
            }
        }
    }

    static public function get ($key)
    {
        if (empty($key)) return false;
        if (empty(self::$data)) {
            self::load();
        }
        $keyList = explode(self::SEPARATOR, $key);
        switch (count($keyList)) {
            case 3:
                // db.trade.master
                $ki = $keyList[0];
                $kj = $keyList[1];
                $kk = $keyList[2];
                $info = self::$data[$ki][$kj][$kk] ?? null;
                break;
            case 2:
                // db.trade
                $ki = $keyList[0];
                $kj = $keyList[1];
                $info = self::$data[$ki][$kj] ?? null;
                break;
            case 1:
                // db
                $ki = $keyList[0];
                $info = self::$data[$ki] ?? null;
                break;
            default:
                $info = null;
                break;
        }
        return $info;
    }
}
