<?php

namespace App\Classes\Container;

class Params
{
    public const LIST_ALL_FLEET = 1;
    public const SHOW_MAP_MINIMAP = 2;
    public const SHOW_MAP_RC = 3;
    public const SHOW_MAP_ANTISPY = 4;
    public const SHOW_MAP_FLEETOUT = 5;
    public const SHOW_MAP_FLEETIN = 6;
    public const SHOW_ATTACK_REPORT = 7;
    public const SHOW_REBEL_REPORT = 8;
    public const REDIRECT_CHAT = 9;

    /** @var array * */
    public static $params = [
        self::LIST_ALL_FLEET => true,
        self::SHOW_MAP_MINIMAP => true,
        self::SHOW_MAP_RC => true,
        self::SHOW_MAP_ANTISPY => true,
        self::SHOW_MAP_FLEETOUT => true,
        self::SHOW_MAP_FLEETIN => true,
        self::SHOW_ATTACK_REPORT => true,
        self::SHOW_REBEL_REPORT => true,
        self::REDIRECT_CHAT => false,
    ];

    /**
     * @return array
     */
    public static function getParams()
    {
        return self::$params;
    }
}
