<?php
namespace epii\template\engine;

use epii\template\i\IEpiiViewEngine;


/**
 * Created by PhpStorm.
 * User: mrren
 * Date: 2018/12/25
 * Time: 1:19 PM
 */
class EpiiViewEngine implements IEpiiViewEngine
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
            $__in_epii_view_engine = 1;
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

            $function = str_replace("\\,", "__dou__", $function);

            $function_array = explode(",", $function);

            $function = $function_array[0];
            unset($function_array[0]);
            if (!$function_array)
                $function_array = ["\$0"];
            foreach ($function_array as $key => $value) {
                $function_array[$key] = $this->stringToPhpData($value);
            }
            $function = $function . "(" . implode(",", $function_array) . ")";
            $function = str_replace("__dou__", ",", $function);
            $outstring = str_replace("\$0", $outstring, $function);
        }

        return $outstring;
    }

    private function stringToYuFaStart($fun_name, $arg_string)
    {

        $arg_string = trim($arg_string);
        $args = array_filter(explode(" ", $arg_string), function ($item) {
            return $item !== "";
        });

        if ($fun_name == "loop" || $fun_name == "foreach") {
            if (!isset($args[1])) {
                $args[1] = "\$key=>\$value";
            }
            return "foreach({$args[0]} as {$args[1]}):";
        } else if ($fun_name == "if" || $fun_name == "else" || $fun_name == "elseif") {
            return "$fun_name({$args[0]}):";
        }
        return "";
    }

    private function stringToYuFaEnd($fun_name)
    {

        if ($fun_name == "/loop" || $fun_name == "/foreach") {

            return "endforeach;";
        } else if ($fun_name == "/if") {
            return "endif;";
        } else if ($fun_name == "else") {
            return "else:";
        }
        return "";
    }

    private function parse_tpl(string $tmpfile, string $compile_file)
    {
        if (!is_file($tmpfile)) {

            return false;
        }


        $txt = preg_replace_callback("/" . $this->config["tpl_begin"] . "\\$(.*?)" . $this->config["tpl_end"] . "/is", function ($match) {
            $string = $this->stringToPhpData("\$" . $match[1]);
            return "<?php echo $string; ?>";
        }, file_get_contents($tmpfile));
        $txt = preg_replace_callback("/" . $this->config["tpl_begin"] . ":(.*?)" . $this->config["tpl_end"] . "/is", function ($match) {
            $string = $this->stringToPhpData("|" . $match[1]);
            return "<?php echo $string; ?>";
        }, $txt);


        $txt = preg_replace_callback("/" . $this->config["tpl_begin"] . "(.*?)" . $this->config["tpl_end"] . "/is", function ($match1) {

            $match1[1] = rtrim(trim($match1[1]), ";");

            if (($pox = stripos($match1[1], " ")) > 0) {
                $string = $this->stringToYuFaStart(substr($match1[1], 0, $pox), substr($match1[1], $pox));
            } else {
                $string = $this->stringToYuFaEnd($match1[1]);
            }


            return "<?php   $string  ?>";
        }, $txt);


        if (!is_dir($todir = dirname($compile_file))) {
            mkdir($todir, 0777, true);
        }

        $txt = "<?php if(!isset(\$__in_epii_view_engine)){exit;} ?>" . $txt;

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