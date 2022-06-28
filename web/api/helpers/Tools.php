<?php

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * @package   Edhec Connector API
 * @author Aurone <info@aurone.com>
 * @copyright Copyright (c)2007-2022 Aurone <https://aurone.com>
 * @license   GNU General Public License version 3, or later
 *
 * Helper functions
 */
class EdhecTools
{

    /**
     * Removes special chars
     *
     * @param string $str
     *
     * @return string
     */
    public static function clean($str)
    {
        return preg_replace('/[^A-Za-z0-9\-]/', ' ', $str);
    }

    /**
     * Sort multi-dimensional array by specific key
     *
     * @param multi-dimensional array $array
     * @param string $orderby specific key
     *
     * @return multi-dimensional array
     */
    public static function multisort($array, $orderby)
    {
        $sortArray = array();

        foreach ($array as $person) {
            foreach ($person as $key => $value) {
                if (!isset($sortArray[$key])) {
                    $sortArray[$key] = array();
                }
                $sortArray[$key][] = $value;
            }
        }

        array_multisort($sortArray[$orderby], SORT_DESC, $array);
        return $array;
    }

    /**
     * Get current link
     *
     * @return string
     */
    public static function getCurrentLink()
    {
        return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    }

    /**
     * Set response
     *
     * @param int  $code | code of operation
     * @param type $status | e.g., success, error, warning, info
     * @param type $data | e.g., ["name" => "Anwer"] (optional)
     * @param type $message | e.g., "Failed Authentication" (optional)
     * @param int  $status  The response status code
     */
    public static function setResponse($code, $status, $data = null, $message = "", $httpResponseCode = 200)
    {
        $data = [
            "status" => $status,
            "code" => $code,
            "message" => $message,
            "myData" => $data,
            "httpResponseCode" => $httpResponseCode,
        ];

        $response = new JsonResponse($data, $httpResponseCode);
        $response->send();
        exit;
    }

    /**
     * Get header Authorization
     *
     */
    public static function getAuthorizationHeader()
    {
        $headers = null;

        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER["Authorization"]);
        } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) { // Nginx or fast CGI
            $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
        } elseif (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
            $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
            //print_r($requestHeaders);
            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }

        return $headers;
    }

    /**
     * get access token from header
     *
     */
    public static function getBearerToken()
    {
        $headers = self::getAuthorizationHeader();

        // HEADER: Get the access token from the header
        if (!empty($headers)) {
            if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    /**
     * Include all files in specific directory
     *
     * @param string $directoryPath | e.g., /home/projec/
     */
    public static function includeFiles($directoryPath)
    {
        foreach (scandir($directoryPath) as $filename) {
            if ($filename != "." && $filename != "..") {
                $path = $directoryPath . $filename;

                if (is_file($path)) {
                    require_once $path;
                }
            }
        }
    }

    /**
     * Get all data received: params from $_GET, $_POST, php://input
     *
     * @return Associative array
     */
    public static function getAllDataReceived()
    {
        $decodedJson = self::jsonToArray(file_get_contents("php://input"));
        $decodedJson = is_array($decodedJson) ? $decodedJson : [];

        $data = array_merge($_GET, $_POST, $decodedJson);

        // Convert json to array (if exists) @TODO
        //$data = array_map('self::jsonToArray', $data);

        // Delete action param
        unset($data['action']);

        return $data;
    }

    /**
     * Convert json to array
     *
     * @return Associative array
     */
    public static function jsonToArray($json)
    {
        $data = [];

        try {
            if (is_string($json)) {
                $data = json_decode($json, true);
            }

        } catch (\Throwable $th) {
            //throw $th;
        }

        return $data;
    }

    /**
     * Add text into string separated by character e.g., "word1 | word2 | word3"
     *
     * @param String $newString e.g., "word4"
     * @param String $oldString e.g., "word1 | word2 | word3"
     *
     * @return String
     */
    public static function addToImplodeString($newString, $oldString = "", $delimiter = ' | ')
    {
        $pieces = array_filter(explode($delimiter, $oldString));
        $pieces[] = $newString;

        return implode($delimiter, $pieces);
    }

    /**
     * Remove spaces from a string (multiple values - array)
     *
     * @param array $array (array of strings)
     *
     * @return array
     */
    public static function trimArrayOfString(&$array, $function = 'trim')
    {
        foreach ($array as $k => &$val) {
            if (is_array($val)) {
                self::trimArrayOfString($val);
            } else {
                if (is_string($val)) {
                    $val = $function($val);
                }
            }
        }

        return $array;
    }

    /**
     * Determine if php is running at the command line
     *
     * @return bool
     */
    public static function isCli()
    {
        if (php_sapi_name() == 'cli' && empty($_SERVER['REMOTE_ADDR'])) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Determine if php is running by cron
     *
     * @return bool
     */
    public static function isCron()
    {
        if (php_sapi_name() == 'cli') {
            if (!isset($_SERVER['TERM'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the contents of the JSON file
     *
     * @param string $file (filename)
     *
     * @return string (json)
     */
    public static function getDataFromFile($file)
    {
        return file_get_contents($file);
    }

    /**
     * Write data to a file
     *
     * @param array of objects $data
     *
     * @param string           $file (filename)
     *
     * @return void
     */
    public static function addDataToFile($data, $file)
    {
        // LOCK_EX flag to prevent anyone else writing to the file at the same time
        file_put_contents($file, json_encode($data), LOCK_EX);
    }

    /**
     * Reset file data (Clearing content of file)
     *
     * @param string $file (filename)
     */
    public static function resetFileData($file)
    {
        file_put_contents($file, "");
    }

    /**
     * Get last x days from today
     *
     * @param int $days
     *
     * @return object
     */
    public static function getLastXDays($days, $format = 'Y-m-d')
    {
        $date = new \DateTime();
        $date->modify('-' . $days . ' day');

        return $date->format($format);
    }

    /**
     * Sanitize data e.g., remove spaces, decode html tags, etc.
     *
     * @param array $data
     * @param boolean $returnObject
     *
     * @return array
     */
    public static function sanitizeData($data, $returnObject = false)
    {
        // Remove spaces from a string
        $data = self::trimArrayOfString($data);

        if (is_object($data)) {
            $data = (array) $data;
        }

        // Decode html tags html_entity_decode
        $data = array_map(
            function ($array) {
                return html_entity_decode($array, ENT_QUOTES | ENT_HTML401, 'UTF-8');
            }, $data
        );

        return $returnObject ? (object) $data : $data;
    }

    /**
     * Extract the text between tags
     *
     * @param string $string
     * @param string $tagname 
     *
     * @return string
     */
    public static function getTextBetweenTags($string, $tagname)
    {
        $pattern = "/<$tagname ?.*>(.*)<\/$tagname>/";

        preg_match($pattern, $string, $matches);

        return $matches[1];
    }
}
