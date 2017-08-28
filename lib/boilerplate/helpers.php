<?php
function varDumpPre($expression) {
    echo '<pre>';
    var_dump($expression);
    echo '</pre>';
}

// see https://secure.php.net/manual/en/function.var-dump.php#51119
function varDumpReturn($expression) : string {
    ob_start();
    var_dump($expression);
    $return = ob_get_contents();
    ob_end_clean();
    return $return;
}

function printRPre($expression) {
    echo '<pre>' . print_r($expression, true) . '</pre>';
}

function route(string $route_name, array $parameters = array(), $relative = true) : string {
    return \boilerplate\Core\Router::getRouteUrl($route_name, $parameters, $relative);
}
