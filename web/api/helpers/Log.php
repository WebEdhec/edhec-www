<?php

/**
 * @package   Edhec Connector
 * @author    Aurone <info@aurone.com>
 * @copyright Copyright (c)2007-2022 Aurone <https://aurone.com>
 * @license   GNU General Public License version 3, or later
 *
 */
class EdhecLog
{
    /* ----------------------- Realtime log ----------------------- */

    /**
     * Save the log to a file
     *
     * @param string $message
     *
     * @return void
     */
    public static function saveLogToFile($message, $status)
    {
        // Get the contents of the JSON file & convert to array
        $logFileContent = EdhecTools::getDataFromFile(EDHEC_LOG_FILENAME);
        $logData = json_decode($logFileContent, true);

        if (!is_array($logData)) {
            $logData = [];
        } else {
            $count = count($logData);
            $max = 500; // @TODO

            // Remove old log
            if (count($logData) >= $max) {
                $count_remove = ($count - $max) + 1;
                $logData = array_slice($logData, $count_remove);
            }
        }

        $logData[] = ['message' => $message, 'status' => $status];

        EdhecTools::addDataToFile($logData, EDHEC_LOG_FILENAME);
    }

    /**
     * Get Realtime log
     *
     * @return mixed
     */
    public static function getRealtimeLog()
    {
        try {
            $params = EdhecTools::getAllDataReceived();
            $offset = isset($params["offset"]) ? $params["offset"] : 0;
            $limit = isset($params["limit"]) ? $params["limit"] : 20;

            $encodedData = EdhecTools::getDataFromFile(EDHEC_LOG_FILENAME);
            $data = json_decode($encodedData);

            if (!is_array($data)) {
                $logData = [];
            }

            // Return an array with elements in reverse order
            $data = array_reverse($data);
            // Extract a slice of the array
            $logData = array_slice($data, $offset, $limit);

            EdhecTools::setResponse(263, "success", $logData, 'Realtime Log');
        } catch (Exception $e) {
            EdhecTools::setResponse(264, "error", [], 'A problem occurred! | Realtime log' . ' | Excep: ' . $e->getMessage());
        }
    }

    /**
     * Remove Realtime logs
     *
     *
     * @return mixed
     */
    public static function removeRealtimeLogs()
    {
        try {
            EdhecTools::addDataToFile('', EDHEC_LOG_FILENAME);

            EdhecTools::setResponse(282, "success", [], 'Remove Realtime Logs');
        } catch (Exception $e) {
            EdhecTools::setResponse(283, "error", [], 'A problem occurred! | Remove Realtime Logs' . ' | Excep: ' . $e->getMessage());
        }
    }

    /* ----------------------- End Realtime log ----------------------- */
}
