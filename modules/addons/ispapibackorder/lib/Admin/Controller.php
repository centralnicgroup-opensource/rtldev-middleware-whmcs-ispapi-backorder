<?php

namespace WHMCS\Module\Addon\ispapibackorder\Admin;

use WHMCS\Module\Registrar\Ispapi\Ispapi as Ispapi;
use Illuminate\Database\Capsule\Manager as DB;

// TODO tooldomains

/**
 * Client Area Controller
 */
class Controller
{
    /**
     * checkdomains action.
     *
     * @param array $vars Module configuration parameters
     * @param Smarty $smarty smarty instance
     * @return string
     */
    public function list($vars, $smarty)
    {
        global $perf;
        $perf["controller"] = [
            "start" => microtime(true)
        ];
        $perf["controller"]["end"] = microtime(true);
        $perf["controller"]["rt"] = $perf["controller"]["end"] - $perf["controller"]["start"];
    
        $smarty->fetch("list.tpl");
    }

    /**
     * order action.
     *
     * @param array $vars Module configuration parameters
     * @return array
     */
    public function order($vars)
    {
        global $perf;
        $perf["controller"] = [
            "start" => microtime(true)
        ];
        ignore_user_abort(false);

        $perf["controller"]["end"] = microtime(true);
        $perf["controller"]["rt"] = $perf["controller"]["end"] - $perf["controller"]["start"];
        return [
            "success" => true,
            "appid" => 123131
        ];
    }

    /**
     * remove action.
     *
     * @param array $vars Module configuration parameters
     * @return array
     */
    public function remove($vars)
    {
        global $perf;
        $perf["controller"] = [
            "start" => microtime(true)
        ];
        ignore_user_abort(false);

        $perf["controller"]["end"] = microtime(true);
        $perf["controller"]["rt"] = $perf["controller"]["end"] - $perf["controller"]["start"];
        return [
            "success" => true
        ];
    }
}