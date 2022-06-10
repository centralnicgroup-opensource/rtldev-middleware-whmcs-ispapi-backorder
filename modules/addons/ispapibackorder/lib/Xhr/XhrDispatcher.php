<?php

namespace WHMCS\Module\Addon\ispapibackorder\Xhr;

require_once(implode(DIRECTORY_SEPARATOR, [__DIR__, "Controller.php"]));

/**
 * XHR Dispatch Handler
 */
class XhrDispatcher
{

    /**
     * Dispatch request.
     *
     * @param string $action
     * @param array $parameters
     *
     * @return string
     */
    public function dispatch($action, $data)
    {
        global $perf;
        $perf["dispatcher"] = ["start" => microtime(true)];
        if ($action) {
            $action = str_replace("-", "", $action);
        }

        $controller = new Controller();

        // Verify requested action is valid and callable
        if (is_callable([$controller, $action])) {
            $perf["dispatcher"]["end"] = microtime(true);
            $perf["dispatcher"]["rt"] = $perf["dispatcher"]["end"] - $perf["dispatcher"]["start"];
            return $controller->$action($data);
        }
        // action error
        return [
            "success" => false,
            "error" => "Invalid Xhr Action."
        ];
    }
}