<?php

ini_set('memory_limit', '-1'); // This also needs to be increased in some cases. Can be changed to a higher value as per need)
ini_set('max_execution_time', 900);

// Debug
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Constants
require_once dirname(__FILE__) . '/defines.inc.php';

// Include helpers files
include dirname(__FILE__) . '/helpers/Tools.php';
include dirname(__FILE__) . '/helpers/Crypto.php';
include dirname(__FILE__) . '/helpers/Log.php';

// Include CORS HEADERS file (Add / Change domain if you want)
include dirname(__FILE__) . '/header.php';

// Autoload
require_once __DIR__ . '/vendor/autoload.php';

// Include services files
require_once dirname(__FILE__) . '/services/Auth.php';
require_once dirname(__FILE__) . '/services/WS_Synchronization.php';

/* ------------- Drupal ---------------- */

use Drupal\Core\DrupalKernel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

// Specify relative path to the drupal root.
$drupalRoot = dirname(dirname(dirname(__FILE__))); // e.g. /home/edhec/public_html | ($_SERVER["DOCUMENT_ROOT"] not working on cli)
$autoloader = require $drupalRoot . '/vendor/autoload.php';

// Common functions that many Drupal modules will need to reference.
$commonPath = $drupalRoot . '/web/core/includes/common.inc';

if (file_exists($commonPath)) {
    require_once $commonPath;
}

// User registration and login system.
$userModulePath = $drupalRoot . '/web/core/modules/user/user.module';

if (file_exists($userModulePath)) {
    require_once $userModulePath;
}

try {
    $request = Request::createFromGlobals();
    $kernel = DrupalKernel::createFromRequest($request, $autoloader, 'prod');
    // $kernel->prepareLegacyRequest($request); // For Drupal 8
    $kernel->boot();
    $kernel->preHandle($request);
} catch (HttpExceptionInterface $e) {
    $response = new Response('', $e->getStatusCode());
    $response->prepare($request)->send();
    exit;
}
/* ------------- End Drupal ---------------- */

class API
{
    public function __construct()
    {
        // Check if the directory is exist
        if (!file_exists(dirname(EDHEC_LOG_FILENAME))) {
            mkdir(dirname(EDHEC_LOG_FILENAME), 0777, true);
        }

        // Check if the file already exists
        if (!is_file(EDHEC_LOG_FILENAME)) {
            // Init empty file
            file_put_contents(EDHEC_LOG_FILENAME, '');
        }

        // Request Routing

        try {
            $this->route();
        } catch (\Throwable $exc) {
            $code = 80;
            $msg = $exc->getMessage() . " >>> CODE: $code";
            EdhecLog::saveLogToFile(date("Y-m-d h:i:s") . " >>> " . $msg, "error");

            EdhecTools::setResponse($code, "error", null, $msg, Response::HTTP_INTERNAL_SERVER_ERROR);
            //throw $exc;
        }
    }

    public function route()
    {
        // If php is running at the command line or by cron
        if (EdhecTools::isCli() || EdhecTools::isCron()) {
            $synchronization = new WS_Synchronization();
            $synchronization->execute();
            exit;
        }

        if (isset($_GET['action'])) {
            $action = $_GET['action'];
        } else {
            $action = $_POST['action'];
        }

        // Authenticate the user by email & password
        if ($action == "login") {
            Auth::login();
        }
        // updown
        elseif ($action == "updown") {
            //
        } else /* Authenticate the user by token */{
            // Retrieve token from header | POST | GET
            $token = Auth::retrieveToken();

            // Succcess Authentication | valid token
            if (Auth::validateToken($token) || (isset($_GET['debug']) && $_GET['debug'] == EDHEC_SECURE_KEY)) {
                switch ($action) {
                    case 'profile':
                        Auth::getProfile();
                        break;
                    case 'sync-start':
                        $synchronization = new WS_Synchronization();
                        $synchronization->execute();
                        break;
                    default:
                        break;
                }
            } else { // Failed Authentication | Invalid token
                EdhecTools::setResponse(100, "error", null, 'Invalid token.', Response::HTTP_BAD_REQUEST);
            }
        }
    }
}

$api = new API();
