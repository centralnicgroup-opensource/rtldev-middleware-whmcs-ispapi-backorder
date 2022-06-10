<?php

namespace WHMCS\Module\Addon\ispapibackorder\Client;

use WHMCS\Module\Registrar\Ispapi\Ispapi;

/**
 * Client Area Controller
 */
class Controller
{
    /**
     * checkdomains action.
     *
     * @param array $vars Module configuration parameters
     * @return array
     */
    public function list()
    {
        global $perf;
        $perf["controller"] = [
            "start" => microtime(true)
        ];
        ignore_user_abort(false);

        $perf["controller"]["end"] = microtime(true);
        $perf["controller"]["rt"] = $perf["controller"]["end"] - $perf["controller"]["start"];
        return [];
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