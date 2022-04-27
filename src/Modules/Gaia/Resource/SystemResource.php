<?php

namespace App\Modules\Gaia\Resource;

class SystemResource
{
    public static function getInfo($id, $info)
    {
        if (in_array($info, ['id', 'frenchName'])) {
            return self::$systems[$id - 1][$info];
        } else {
            return false;
        }
    }

    private static $systems = [
        [
            'id' => 1,
            'frenchName' => 'Cimetière Spatial',
        ],
        [
            'id' => 2,
            'frenchName' => 'Nébuleuse',
        ],
        [
            'id' => 3,
            'frenchName' => 'Géante Bleue',
        ],
        [
            'id' => 4,
            'frenchName' => 'Naine Jaune',
        ],
        [
            'id' => 5,
            'frenchName' => 'Naine Rouge',
        ],
    ];
}
