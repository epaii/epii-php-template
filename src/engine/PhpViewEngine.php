<?php
namespace epii\template\engine;

use epii\template\i\IEpiiViewEngine;


/**
 * Created by PhpStorm.
 * User: mrren
 * Date: 2018/12/25
 * Time: 1:19 PM
 */
class PhpViewEngine implements IEpiiViewEngine
{
    private $config = [];

    public function init(Array $config)
    {
        // TODO: Implement init() method.

        $this->config = $config;
        $this->config["tpl_dir"] = rtrim($this->config["tpl_dir"], DIRECTORY_SEPARATOR);

        if (!is_dir($this->config["cache_dir"])) {
            mkdir($this->config["cache_dir"], 0777, true);
        }

        if (!isset($this->config["tpl_begin"])) {
            $this->config["tpl_begin"] = "\\{";
        }
        if (!isset($this->config["tpl_end"])) {
            $this->config["tpl_end"] = "\\}";
        }


    }


    public function fetch(string $file, Array $args = null)
    {
        $tmpfile = $this->config["tpl_dir"] . DIRECTORY_SEPARATOR . $file . ".php";
        if (!file_exists($tmpfile)) {
            return "";
        } else {
            ob_start();
            if ($args !== null)
                extract($args);
            include_once $this->get_compile_file($tmpfile);
            $content = ob_get_contents();
            ob_clean();
            return $content;
        }


    }


    private function get_compile_file(string $tmpfile)
    {
        if (isset($this->config["just_php"]) && $this->config["just_php"]) {
            return $tmpfile;
        }
        $compile_file = $this->config["cache_dir"] . DIRECTORY_SEPARATOR . md5($tmpfile) . ".php";
        if (file_exists($compile_file) && (filemtime($compile_file) > filemtime($tmpfile))) {

        } else {
            $this->parse_tpl($tmpfile, $compile_file);

        }
        return $compile_file;
    }


    private function stringToPhpData(string $string)
    {
        $string = trim($string);
        $string = trim($string, ";");

        $tmep_arr = explode("|", $string);
        $string = $tmep_arr[0];
        if (isset($tmep_arr[1])) {
            $function = $tmep_arr[1];
        } else {
            $function = null;
        }


        $array = explode(".", $string);

        $outstring = null;

        foreach ($array as $value) {
            if (stripos($value, "\$") === 0) {
                if ($outstring === null)
                    $outstring = $value;
                else
                    $outstring .= "[" . $value . "]";
            } else {
                if ($outstring === null)
                    $outstring = "\"" . $value . "\"";
                else
                    $outstring .= "[\"" . $value . "\"]";
            }
        }
        if ($outstring === null)
            $outstring = "";
        if ($function) {
            $function_array = explode(",", $function);

            $function = $function_array[0];
            unset($function_array[0]);
            if (!$function_array)
                $function_array = ["\$0"];
            foreach ($function_array as $key => $value) {
                $function_array[$key] = $this->stringToPhpData($value);
            }
            $function = $function . "(" . implode(",", $function_array) . ")";
            $outstring = str_replace("\$0", $outstring, $function);
        }

        return $outstring;
    }

    private function parse_tpl(string $tmpfile, string $compile_file)
    {
        if (!is_file($tmpfile)) {

            return false;
        }


        $txt = preg_replace_callback("/" . $this->config["tpl_begin"] . "([\${+}].*?)" . $this->config["tpl_end"] . "/is", function ($match) {
            $string = $this->stringToPhpData($match[1]);
            return "<?php echo $string; ?>";
        }, file_get_contents($tmpfile));
        $txt = preg_replace_callback("/" . $this->config["tpl_begin"] . ":(.*?)" . $this->config["tpl_end"] . "/is", function ($match) {
            $string = $this->stringToPhpData("|" . $match[1]);
            return "<?php echo $string; ?>";
        }, $txt);

        if (!is_dir($todir = dirname($compile_file))) {
            mkdir($todir, 0777, true);
        }
        if (file_put_contents($compile_file, $txt)) {
            return $compile_file;

        } else {
            return false;

        }


    }

    public static function require_config_keys()
    {
        return ["tpl_dir", "cache_dir"];
    }
}