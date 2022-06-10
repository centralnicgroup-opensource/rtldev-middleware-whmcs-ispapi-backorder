<?php

namespace WHMCS\Module\Addon\ispapibackorder\Xhr;

use WHMCS\Module\Registrar\Ispapi\Ispapi;

require __DIR__ . "/vendor/autoload.php";
const DBDIR = __DIR__ . "/data";
const DBCFG =  [
    "auto_cache" => true,
    "cache_lifetime" => null,
    "timeout" => false,
    "primary_key" => "_id",
    /*"search" => [
      "min_length" => 2,
      "mode" => "or",
      "score_key" => "scoreKey",
      "algorithm" => Query::SEARCH_ALGORITHM["hits"]
    ]*/
];
$pdlStore = new \SleekDB\Store("pendingdeletiondomains", DBDIR, DBCFG);

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
    public function list($vars)
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