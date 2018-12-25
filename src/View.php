<?php
namespace epii\template;

use epii\template\engine\PhpViewEngine;
use epii\template\i\IEpiiViewEngine;


/**
 * Created by PhpStorm.
 * User: mrren
 * Date: 2018/12/25
 * Time: 1:02 PM
 */
class View
{

    /*
     * @IEpiiViewEngine
     */
    private static $engine = null;
    private static $config = [];

    public static function setEngine(Array $config, string $engine = null)
    {
        self::$engine = $engine == null ? PhpViewEngine::class : $engine;
        if (!class_exists(self::$engine)) {
            echo "tmplate engine not exists!";
            exit();
        }


        self::$config = $config;
        $keys = (self::$engine)::require_config_keys();
        foreach ($keys as $name) {
            if (!isset($config[$name])) {
                echo "tmplate config need require $name!\n";
                exit();
            }
        }



    }


    public static function display(string $file, Array $args = null, string $engine = null)
    {
        echo self::fetch($file, $args, $engine);
        exit;
    }

    public static function fetch(string $file, Array $args = null, string $engine = null)
    {
        if ($engine === null) {
            $engine = self::$engine;
        }
        if (!class_exists($engine)) {
            echo "tmplate engine not exists!";
            exit();
        }

        $engine_mod = new $engine();
        if ($engine_mod instanceof IEpiiViewEngine) {
            $engine_mod->init(self::$config);
            return $engine_mod->fetch($file, $args);
        } else {
            echo "It is not a right tmplate engine!";
            exit();
        }

    }
}