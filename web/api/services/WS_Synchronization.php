<?php

/**
 * @package   Edhec Connector
 * @copyright Copyright (c)2007-2022 Aurone <https://aurone.com>
 * @license   GNU General Public License version 3, or later
 *
 * Manage website database (e.g., Get data from WS and save it into website database.)
 */

use Curl\Curl;
use Symfony\Component\HttpFoundation\Response;
use \Drupal\Core\File\FileSystemInterface;
use \Drupal\file\Entity\File;
use \Drupal\file\FileInterface;
use \Drupal\media\entity\Media;

class WS_Synchronization
{
    // Limit of data to get from WS
    const LIMIT_PER_EXEC = 100;
    const SEPARATOR = "#SEP#";

    // WS URLs
    public static $wsExternalAuthorsUrl = EDHEC_REMOTE_API_URL . '/ws/externals_auth';
    public static $wsResearchersUrl = EDHEC_REMOTE_API_URL . '/ws/researchers';
    public static $wsPublicationsUrl = EDHEC_REMOTE_API_URL . '/ws/publications';

    // Connector Status
    const CONNECTOR_STATUS_INSERTED = 'inserted'; // New node | inserted by WS | Not yet synchronized in website
    const CONNECTOR_STATUS_UPDATED = 'updated'; // e.g., Updated node | updated by WS | Not yet synchronized in website
    const CONNECTOR_STATUS_SYNCHRONIZED = 'synchronized'; // e.g., Synchronized success in website
    const CONNECTOR_STATUS_FAILED = 'failed'; // e.g., Synchronization is failed in website

    // Cron user (created in BO)
    private $cronUser = null;

    /**
     * Constructor
     */
    public function __construct()
    {
        $changedDate = EdhecTools::getLastXDays(6);

        self::$wsExternalAuthorsUrl .= '?changed=' . $changedDate;
        self::$wsResearchersUrl .= '?changed=' . $changedDate;
        self::$wsPublicationsUrl .= '?changed=' . $changedDate;
    }

    /**
     * Execute the synchronization
     *
     */
    public function execute()
    {
        // Save log
        EdhecLog::saveLogToFile(date("Y-m-d h:i:s") . " >>> ***** WS Synchronization initialization *****", "info");

        // Get the cron user
        $this->cronUser = user_load_by_name(CRON_USER);

        /* ------------------ EXTERNAL AUTHORS ------------------- */

        // Get external authors from WS
        $externalAuthors = $this->getExternalAuthors();

        // Save external authors into website database
        if ($externalAuthors) {
           $this->saveExternalAuthors($externalAuthors);
        }

        /* ---------------- END EXTERNAL AUTHORS ----------------- */

        /* ------------------ RESEARCHERS ------------------- */

        // Get researchers from WS
        $researchers = $this->getResearchers();

        // Save researchers into website database
        if ($researchers) {
           $this->saveResearchers($researchers);
        }

        /* ---------------- END RESEARCHERS ----------------- */

        /* ------------------ PUBLICATIONS ------------------- */

        // Get publications from WS
        $publications = $this->getPublications();

        // Save publications into website database
        if ($publications) {
            $this->savePublications($publications);
        }

        /* ---------------- END PUBLICATIONS ----------------- */

        dump("Synchronization completed");
        die;
    }

    /**
     * Get external authors from WS
     *
     * @return mixed ( array of objects | boolean )
     */
    public function getExternalAuthors()
    {
        // Save log
        EdhecLog::saveLogToFile(date("Y-m-d h:i:s") . " >>> Get external authors from WS...", "info");

        return $this->getWsData(self::$wsExternalAuthorsUrl);
    }

    /**
     * Save external authors into website database
     *
     * @param associative array $data
     *
     * @return void
     */
    public function saveExternalAuthors($externalAuthors)
    {
        // Save log
        EdhecLog::saveLogToFile(date("Y-m-d h:i:s") . " >>> Save external authors into website database...", "info");

        $contentType = 'auteur_externe';

        foreach ($externalAuthors as $wsData) {
            try {

                if (!isset($wsData->title)) {
                    continue;
                }

                // WS nid
                $ws_nid = (int) $wsData->nid;
                $title = $wsData->title;

                // Set the node data
                $data = [
                    'type' => 'auteur_externe',
                    'langcode' => 'fr',
                    'uid' => $this->cronUser ? $this->cronUser->id() : 1,
                    'title' => $title,
                    'field_nom' => $title,
                    'field_poste' => isset($wsData->field_affiliation) ? $wsData->field_affiliation : '',
                    'field_ws_nid' => $ws_nid,
                    'changed' => time()
                ];

                // Find node and (create|update) it if (not found|found)
                $node = $this->saveNode($contentType, $data, 'field_ws_nid');
            } catch (Exception $e) {
                // throw new Exception($e->getMessage());

                $msg = 'A problem occurred! | Save external authors | Excep: ' . $e->getMessage() . " >>> CODE: 1000 (WS NID: ##" . $ws_nid . "##)";

                dump($msg);

                EdhecLog::saveLogToFile(date("Y-m-d h:i:s") . " >>> " . $msg, "error");
            }
        }

    }

    /**
     * Get researchers from WS
     *
     * @return mixed ( array of objects | boolean )
     */
    public function getResearchers()
    {
        // Save log
        EdhecLog::saveLogToFile(date("Y-m-d h:i:s") . " >>> Get researchers from WS...", "info");

        return $this->getWsData(self::$wsResearchersUrl);
    }

    /**
     * Save researchers into website database
     *
     * @param associative array $data
     *
     * @return void
     */
    public function saveResearchers($researchers)
    {
        // Save log
        EdhecLog::saveLogToFile(date("Y-m-d h:i:s") . " >>> Save researchers into website database...", "info");

        $contentType = 'cv';

        foreach ($researchers as $wsData) {
            try {
                if (!isset($wsData->title)) {
                    continue;
                }

                // WS nid
                $ws_nid = (int) $wsData->nid;
                $title = $wsData->title;

                /* ----------------------- DISCIPLINE ----------------------- */
                // Field name of the ws tid
                $ws_tid_key = 'field_ws_disp_tid';

                // Term vocabulary name
                $vid = 'discipline';

                // Find terms by field (ws tid) and vocabulary name and create them if not found
                $disciplines = $this->findTermsAndCreateIfNotExist($wsData->field_discipline, $ws_tid_key, $vid);
                /* ----------------------- END DISCIPLINE ----------------------- */

                /* ----------------------- FACULTY ----------------------- */
                // Field name of the ws tid
                $ws_tid_key = 'field_ws_fac_tid';

                // Term vocabulary name
                $vid = 'faculte';

                // Find terms by field (ws tid) and vocabulary name and create them if not found
                $faculties = $this->findTermsAndCreateIfNotExist($wsData->field_faculty, $ws_tid_key, $vid);

                /* ----------------------- END FACULTY ----------------------- */

                /* ----------------------- CAMPUS ----------------------- */
                // Field name of the ws tid
                $ws_tid_key = 'field_ws_camp_tid';

                // Term vocabulary name
                $vid = 'campus';

                // Find terms by field (ws tid) and vocabulary name and create them if not found
                $campuses = $this->findTermsAndCreateIfNotExist($wsData->field_campus, $ws_tid_key, $vid);
                /* ----------------------- END CAMPUS ----------------------- */

                /* ----------------------- PHOTO ----------------------- */
                if ($wsData->field_photo) {
                    //$directory = DRUPAL_ROOT . '/sites/default/files/media'; // 'public://media' invalid path | @TODO: fix this
                    $directory = 'public://media';

                    $url = EDHEC_REMOTE_API_URL . $wsData->field_photo;

                    if (\Drupal::service('file_system')->prepareDirectory($directory, \Drupal\Core\File\FileSystemInterface::CREATE_DIRECTORY)) {
                        $file = $this->system_retrieve_file(trim($url), $directory, true, FileSystemInterface::EXISTS_REPLACE);
                    }

                    // Create media entity if not exist with saved file.
                    $media = $this->createMediaIfNotExist($file);

                    // Photo field value
                    $photo = [
                        'target_id' => (int) $file->id(),
                    ];
                } else {
                    $photo = null;
                }
                /* ----------------------- END PHOTO ----------------------- */

                /* ----------------------- CV ----------------------- */
                if ($wsData->field_files) {
                    $directory = 'public://cv';

                    $url = EDHEC_REMOTE_API_URL . $wsData->field_files;

                    if (\Drupal::service('file_system')->prepareDirectory($directory, \Drupal\Core\File\FileSystemInterface::CREATE_DIRECTORY)) {
                        $file = $this->system_retrieve_file(trim($url), $directory, true, FileSystemInterface::EXISTS_REPLACE);
                    }

                    // CV field value
                    $cv = [
                        'target_id' => (int) $file->id(),
                    ];
                } else {
                    $cv = null;
                }
                /* ----------------------- END CV ----------------------- */

                // Set the node data
                $data = [
                    'type' => 'cv',
                    'langcode' => 'fr',
                    'uid' => $this->cronUser ? $this->cronUser->id() : 1,
                    'title' => $title,
                    'field_titre' => $title,
                    'field_prenom' => isset($wsData->field_firstname) ? $wsData->field_firstname : '',
                    'field_nom' => isset($wsData->field_lastname) ? $wsData->field_lastname : '',
                    'field_email' => isset($wsData->field_email) ? $wsData->field_email : '',
                    'field_poste' => isset($wsData->field_job_title) ? $wsData->field_job_title : '',
                    'field_poste_detaille' => isset($wsData->field_job_detailled) ? $wsData->field_job_detailled : '',
                    'field_expertise' => isset($wsData->field_expertise) ? $wsData->field_expertise : '',
                    'field_principales_contributions' => isset($wsData->field_principales_contributions) ? $wsData->field_principales_contributions : '',
                    'body' => isset($wsData->field_short_bio) ? $wsData->field_short_bio : '',
                    'field_linkedin' => isset($wsData->field_linkedin) ? $wsData->field_linkedin : '',
                    'field_site_de_publication' => isset($wsData->field_website) ? $wsData->field_website : '',
                    'field_discipline' => $disciplines,
                    'field_faculte' => $faculties,
                    'field_campus' => $campuses,
                    'field_photo' => $photo,
                    'field_pdf' => $cv,
                    'field_ws_nid' => $ws_nid,
                    'changed' => time()
                ];

                // Find node and (create|update) it if (not found|found)
                $node = $this->saveNode($contentType, $data, 'field_ws_nid');

            } catch (\Throwable $e) {
                // throw new Exception($e->getMessage());

                $msg = 'A problem occurred! | Save researchers | Excep: ' . $e->getMessage() . " >>> CODE: 1001 (WS NID: ##" . $ws_nid . "##)";

                dump($msg);

                EdhecLog::saveLogToFile(date("Y-m-d h:i:s") . " >>> " . $msg, "error");
            }
        }
    }

    /**
     * Get publications from WS
     *
     * @return mixed ( array of objects | boolean )
     */
    public function getPublications()
    {
        // Save log
        EdhecLog::saveLogToFile(date("Y-m-d h:i:s") . " >>> Get publications from WS...", "info");

        return $this->getWsData(self::$wsPublicationsUrl);
    }

    /**
     * Save publications into website database
     *
     * @param associative array $data
     *
     * @return void
     */
    public function savePublications($publications)
    {
        // Save log
        EdhecLog::saveLogToFile(date("Y-m-d h:i:s") . " >>> Save publications into website database...", "info");

        $contentType = 'publication';

        foreach ($publications as $wsData) {
            try {

                if (!isset($wsData->title)) {
                    continue;
                }

                // WS nid
                $ws_nid = (int) $wsData->nid;
                $title = $wsData->title;

                /* ----------------------- AUTHORS ----------------------- */
                // Field name of the ws nid
                $ws_nid_key = 'field_ws_nid';

                // Find nodes by string of one or more ws nids separated by characters e.g., "Yenee Kim+9410+#SEP#Ramia El Agamy+8134+#SEP#Olivier de Richoufftz+8135+"
                $authors = $this->findNodesByWsNID($contentType, $wsData->field_authors, $ws_nid_key);
                /* ----------------------- END AUTHORS ----------------------- */

                /* ----------------------- FACULTY ----------------------- */
                // Field name of the ws tid
                $ws_tid_key = 'field_ws_fac_tid';

                // Term vocabulary name
                $vid = 'faculte';

                // Find terms by field (ws tid) and vocabulary name and create them if not found
                $faculties = $this->findTermsAndCreateIfNotExist($wsData->field_faculty, $ws_tid_key, $vid);
                /* ----------------------- END FACULTY ----------------------- */

                /* ----------------------- DOMAIN ----------------------- */
                // Field name of the ws tid
                $ws_tid_key = 'field_ws_dom_tid';

                // Term vocabulary name
                $vid = 'domaine';

                // Find terms by field (ws tid) and vocabulary name and create them if not found
                $domains = $this->findTermsAndCreateIfNotExist($wsData->field_domain, $ws_tid_key, $vid);
                /* ----------------------- END DOMAIN ----------------------- */

                /* ----------------------- PUBLICATION TYPE ----------------------- */
                // Field name of the ws tid
                $ws_tid_key = 'field_ws_pubtype_tid';

                // Term vocabulary name
                $vid = 'publication';

                // Find terms by field (ws tid) and vocabulary name and create them if not found
                $pubTypes = $this->findTermsAndCreateIfNotExist($wsData->field_category, $ws_tid_key, $vid);
                /* ----------------------- END PUBLICATION TYPE ----------------------- */

                // Date publication
                $publicationDate = EdhecTools::getTextBetweenTags($wsData->field_publication_date, 'time');
                $publicationDate = date('Y-m-d', strtotime($publicationDate));

                // Set the node data
                $data = [
                    'type' => 'publication',
                    'langcode' => 'fr',
                    'uid' => $this->cronUser ? $this->cronUser->id() : 1,
                    'title' => $title,
                    'body' => isset($wsData->field_short_description) ? $wsData->field_short_description : '',
                    'field_date_de_publication' => isset($wsData->field_publication_date) ? $publicationDate : '',
                    'field_publication_lien' => isset($wsData->field_url) ? $wsData->field_url : '',
                    'field_auteurs' => $authors,
                    'field_domaine' => $domains,
                    'field_faculte' => $faculties,
                    'field_type' => $pubTypes,
                    'field_ws_nid' => $ws_nid,
                    'changed' => time()
                ];

                // Find node and (create|update) it if (not found|found)
                $node = $this->saveNode($contentType, $data, 'field_ws_nid');
            } catch (Exception $e) {
                // throw new Exception($e->getMessage());

                $msg = 'A problem occurred! | Save publications | Excep: ' . $e->getMessage() . " >>> CODE: 1002 (WS NID: ##" . $ws_nid . "##)";

                dump($msg);

                EdhecLog::saveLogToFile(date("Y-m-d h:i:s") . " >>> " . $msg, "error");
            }
        }
    }

    /**
     * Get researchers from WS
     *
     * @param string $url
     *
     * @return mixed ( array of objects | boolean )
     */
    public function getWsData($url)
    {
        $curl = new Curl();
        $curl->get($url);

        // Error
        if ($curl->error) {
            $msg = date("Y-m-d h:i:s") . " >>> " . $curl->errorMessage . " >>> CODE: " . $curl->errorCode;
            EdhecLog::saveLogToFile($msg, "error");

            return false;
        }

        // JSON_UNESCAPED_UNICODE is required for UTF-8 characters in JSON string e.g., "Financi\u00e8re" => "Financière" (@TODO: check if it's still required)
        $response = json_decode($curl->getRawResponse(), false, 512, JSON_UNESCAPED_UNICODE);

        // Sanitize data e.g., remove spaces, decode html tags, etc. | @TODO
        foreach ($response as $key => &$value) {
            $value = EdhecTools::sanitizeData($value, true);
        }

        // success
        return $response;
    }

    /**
     * Find node by field
     *
     * @param string $contentType
     * @param string $field e.g., field_ws_au_nid
     * @param int $value e.g., 123
     *
     * @return mixed (node object | boolean)
     */
    public function findNodeByField($contentType, $field, $value)
    {
        $node = \Drupal::entityTypeManager()
            ->getStorage('node')
            ->loadByProperties([
                // 'type' => $contentType, // @TODO: check if it's still required
                $field => $value,
            ]);

        return reset($node);
    }

    /**
     * Find nodes by string of one or more ws nids separated by characters e.g., "Yenee Kim+9410+#SEP#Ramia El Agamy+8134+#SEP#Olivier de Richoufftz+8135+"
     *
     * @param string $contentType
     * @param string $namesWithId e.g., "Yenee Kim+9410+#SEP#Ramia El Agamy+8134+#SEP#Olivier de Richoufftz+8135+"
     * @param string $field e.g., 'field_ws_au_nid'
     *
     * @return array (array of nodes objects)
     */
    public function findNodesByWsNID($contentType, $namesWithId, $field)
    {
        $nodes = [];

        // If $namesWithId is empty, return empty array
        if (empty(trim($namesWithId))) {
            return $nodes;
        }

        // Get disciplines names (Multiple values possible)
        $namesWithId = explode(self::SEPARATOR, $namesWithId);

        foreach ($namesWithId as $nameWithId) {
            if (empty($nameWithId)) {
                continue;
            }

            // Extract the node id e.g., 'Accounting+123+' => '123'
            $nodeId = $this->getIdFromString($nameWithId);

            // Find the node in the database
            $node = $this->findNodeByField($contentType, $field, $nodeId);

            if ($node) {
                $nodes[] = $node;
            }
        }

        return $nodes;
    }

    /**
     * Find term by field and vocabulary name
     *
     * @param string $field e.g., 'field_ws_dis_tid'
     * @param string $fieldValue e.g., 123
     * @param string $vocabulary e.g., 'discipline'
     *
     * @return mixed (term object | boolean)
     */
    public function getTermByField($field, $fieldValue, $vid)
    {
        $term = \Drupal::entityTypeManager()
            ->getStorage('taxonomy_term')
            ->loadByProperties([
                $field => $fieldValue,
                'vid' => $vid,
            ]);

        return reset($term);
    }

    /**
     * Find terms by field and vocabulary name and (create|update) them if (not found|found)
     *
     * @param string $termsNamesWithId e.g., 'Accounting+1234+#SEP#Banque+4252+'
     * @param string $ws_tid_key e.g., 'field_ws_dis_tid'
     * @param string $vid e.g., 'discipline'
     *
     * @return array (array of term objects)
     */
    public function findTermsAndCreateIfNotExist($termsNamesWithId, $ws_tid_key, $vid)
    {
        $terms = [];

        // If $termsNamesWithId is empty, return empty array
        if (empty(trim($termsNamesWithId))) {
            return $terms;
        }

        // Get disciplines names (Multiple values possible)
        $termsNamesWithId = explode(self::SEPARATOR, $termsNamesWithId);

        foreach ($termsNamesWithId as $termNameWithId) {
            if (empty($termNameWithId)) {
                continue;
            }

            // Extract the term name without ID e.g., 'Accounting+123+' => 'Accounting'
            $termName = $this->getWordFromStringWithoutId($termNameWithId);
            // Extract the term id e.g., 'Accounting+123+' => '123'
            $termId = $this->getIdFromString($termNameWithId);

            // Find term by ws tid
            $term = $this->getTermByField($ws_tid_key, $termId, $vid);

            // If the term doesn't exist, find by name
            if (!$term) {
                $term = $this->getTermByField('name', $termName, $vid);
            }

            // Term data
            $data = [
                'vid' => $vid,
                'name' => $termName,
                $ws_tid_key => $termId,
            ];

            // If the term doesn't exist
            if (!$term) {
                $term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->create($data);
                $term->enforceIsNew();
                $term->save();
            }
            // If the term already exists
            else {
                foreach ($data as $key => $value) {
                    $term->set($key, $value);
                }
            }

            $term->save();

            if ($term) {
                $terms[] = $term;
            }
        }

        return $terms;
    }

    /**
     * Find node and (create|update) it if (not found|found)
     *
     * @param string $contentType
     * @param array $data (node data)
     * @param string $ws_nid_key (field name of WS nid)
     *
     * @return mixed (node object | boolean)
     */
    public function saveNode($contentType, $data, $ws_nid_key)
    {
        // Find node by ws nid
        $node = $this->findNodeByField($contentType, $ws_nid_key, $data[$ws_nid_key]);

        // If the node doesn't exist, find by title
        /*if (!$node) {
        $node = $this->findNodeByField($contentType, 'title', $data['title']);
        }*/

        // If the node doesn't exist
        if (!$node) {
            $node = \Drupal::entityTypeManager()->getStorage('node')->create($data);
        }
        // If the node already exists
        else {
            foreach ($data as $key => $value) {
                $node->set($key, $value);
            }
        }

        $node->save();

        return $node;
    }

    /**
     * Get id with regex from string start with '+' and end with '+' and return it without special characters
     *
     * @param string $string e.g., 'Test +123+'
     *
     * @return string e.g., '123'
     */
    public function getIdFromString($string)
    {
        $regex = '/\+[0-9]+\+/';
        preg_match($regex, $string, $matches);

        return (int) str_replace(['+'], '', $matches[0]);
    }

    /**
     * Extract the word from a string without a special character
     *
     * @param string $string e.g., 'Word+123+'
     *
     * @return string e.g., 'Word'
     */
    public function getWordFromStringWithoutId($string)
    {
        $regex = '/\+[0-9]+\+/';
        preg_match($regex, $string, $matches);

        return trim(str_replace($matches[0], '', $string));
    }

    /**
     * Create media entity if not exist with saved file.
     *
     * @param FileSystemInterface $file
     *
     * @return Media entity
     */
    public function createMediaIfNotExist($file)
    {
        // Find media by file id
        $mediaEntities = \Drupal::entityTypeManager()->getStorage('media')->loadByProperties([
            'field_media_image' => $file->id(),
        ]);

        $media = is_array($mediaEntities) ? array_pop($mediaEntities) : null;

        if (!$media) {
            // Create media entity with saved file.
            $media = Media::create([
                'bundle' => 'image',
                'uid' => $this->cronUser ? $this->cronUser->id() : 1,
                'langcode' => \Drupal::languageManager()->getDefaultLanguage()->getId(),
                'status' => 1,
                'field_media_image' => [
                    'target_id' => $file->id(),
                    // 'alt' => t('Placeholder image'),
                    // 'title' => t('Placeholder image'),
                ],
            ]);

            $media->save();
        }

        return $media;
    }

    /**
     * OVERRIDE FUNCTION | Problem with GuzzleHttp | Utils::chooseHandler() not defined (conflit with GuzzleHttp in drupal vendor) | @TODO: remove this function | /web/core/modules/system/system.module
     *
     * Attempts to get a file using Guzzle HTTP client and to store it locally.
     *
     * @param string $url
     *   The URL of the file to grab.
     * @param string $destination
     *   Stream wrapper URI specifying where the file should be placed. If a
     *   directory path is provided, the file is saved into that directory under
     *   its original name. If the path contains a filename as well, that one will
     *   be used instead.
     *   If this value is omitted, the site's default files scheme will be used,
     *   usually "public://".
     * @param bool $managed
     *   If this is set to TRUE, the file API hooks will be invoked and the file is
     *   registered in the database.
     * @param int $replace
     *   Replace behavior when the destination file already exists:
     *   - FileSystemInterface::EXISTS_REPLACE: Replace the existing file.
     *   - FileSystemInterface::EXISTS_RENAME: Append _{incrementing number} until
     *     the filename is unique.
     *   - FileSystemInterface::EXISTS_ERROR: Do nothing and return FALSE.
     *
     * @return mixed
     *   One of these possibilities:
     *   - If it succeeds and $managed is FALSE, the location where the file was
     *     saved.
     *   - If it succeeds and $managed is TRUE, a \Drupal\file\FileInterface
     *     object which describes the file.
     *   - If it fails, FALSE.
     */
    public function system_retrieve_file($url, $destination = null, $managed = false, $replace = FileSystemInterface::EXISTS_RENAME)
    {
        $parsed_url = parse_url($url);
        /** @var \Drupal\Core\File\FileSystemInterface $file_system */
        $file_system = \Drupal::service('file_system');

        if (!isset($destination)) {
            $path = $file_system->basename($parsed_url['path']);
            $path = \Drupal::config('system.file')->get('default_scheme') . '://' . $path;
            $path = \Drupal::service('stream_wrapper_manager')->normalizeUri($path);
        } else {
            // 'public://media' invalid path | @TODO: fix this
            if (is_dir($file_system->realpath($destination))) {
                // Prevent URIs with triple slashes when glueing parts together.
                $path = str_replace('///', '//', "$destination/") . \Drupal::service('file_system')->basename($parsed_url['path']);
            } else {
                $path = $destination;
            }
        }

        try {
            // @TODO - problem with GuzzleHttp | Utils::chooseHandler() not defined (conflit with GuzzleHttp in drupal vendor)
            // $data = (string) \Drupal::httpClient()
            //     ->get($url)
            //     ->getBody();

            /* ---------------------- Replaced by the following code ------------------ */
            $curl = new Curl();
            $curl->get($url);
            $data = $curl->response;

            // Error
            if ($curl->error) {
                \Drupal::messenger()->addError(t('Failed to fetch file due to error "%error"', ['%error' => $curl->error]));
                return false;
            }

            /* ---------------------- End custom code ------------------ */

            if ($managed) {
                /** @var \Drupal\file\FileRepositoryInterface $file_repository */
                $file_repository = \Drupal::service('file.repository');
                $local = $file_repository->writeData($data, $path, $replace);
            } else {
                $local = $file_system->saveData($data, $path, $replace);
            }
        } catch (TransferException $exception) {
            \Drupal::messenger()->addError(t('Failed to fetch file due to error "%error"', ['%error' => $exception->getMessage()]));
            return false;
        } catch (FileException | InvalidStreamWrapperException $e) {
            \Drupal::messenger()->addError(t('Failed to save file due to error "%error"', ['%error' => $e->getMessage()]));
            return false;
        }

        if (!$local) {
            \Drupal::messenger()->addError(t('@remote could not be saved to @path.', ['@remote' => $url, '@path' => $path]));
        }

        return $local;
    }

    /**
     * {@inheritdoc}
     *
     * OVERRIDE web/core/modules/file/src/FileRepository.php
     *
     * @param \Drupal\file\FileRepositoryInterface $file_repository
     */
    /*public function writeData($file_repository, string $data, string $destination, int $replace = FileSystemInterface::EXISTS_RENAME): FileInterface
    {
    $file_system = \Drupal::service('file_system');

    $uri = $file_system->saveData($data, $destination, $replace);

    return $this->createOrUpdate($file_repository, $file_system, $uri, $destination, $replace === FileSystemInterface::EXISTS_RENAME);
    }*/

    /**
     * OVERRIDE web/core/modules/file/src/FileRepository.php
     *
     * Create a file entity or update if it exists.
     *
     * @param \Drupal\file\FileRepositoryInterface $file_repository
     *
     * @param \Drupal\Core\File\FileSystemInterface $file_system
     *
     * @param string $uri
     *   The file URI.
     * @param string $destination
     *   The destination URI.
     * @param bool $rename
     *   Whether to rename the file.
     *
     * @return \Drupal\file\Entity\File|\Drupal\file\FileInterface
     *   The file entity.
     *
     * @throws \Drupal\Core\Entity\EntityStorageException
     *   Thrown when there is an error saving the file.
     */
    /*protected function createOrUpdate($file_repository, $file_system, string $uri, string $destination, bool $rename): FileInterface
{
$file = $file_repository->loadByUri($uri);

if ($file === null) {
$file = File::create(['uri' => $uri]);
$file->setOwnerId($this->cronUser ? $this->cronUser->id() : 1);
}

if ($rename && is_file($destination)) {
$file->setFilename($file_system->basename($destination));
}

$file->setPermanent();
$file->save();

return $file;
}*/
}
