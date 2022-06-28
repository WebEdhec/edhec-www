<?php

function CORS_HEADERS_HANDLER()
{
    if (!EdhecTools::isCli()) {

        $defaultSite = 'http://localhost';
        header('Access-Control-Allow-Origin: ' . $defaultSite);

        if (isset($_SERVER['HTTP_ORIGIN'])) {
            $urlParts = parse_url($_SERVER['HTTP_ORIGIN']);
            // remove www
            $domain = preg_replace('/^www\./', '', $urlParts['host']);

            switch ($domain) {
                case 'edhec.aurone.dev':
                    header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
                    break;
                default:
                    break;
            }
        }

        header("Access-Control-Allow-Methods: HEAD, GET, POST, PUT, PATCH, DELETE, OPTIONS");
        header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method,Access-Control-Request-Headers, Authorization");
        header('Access-Control-Allow-Credentials: true');
        // header('Content-Type: application/json');

        $method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : '';

        if ($method == "OPTIONS") {
            header("HTTP/1.1 200 OK");
            die();
        }
    }
}

CORS_HEADERS_HANDLER();
