<?php

/**
 * @package   Edhec Connector API
 * @author Aurone <info@edhec.com>
 * @copyright Copyright (c)2007-2022 Aurone <https://edhec.com>
 * @license   GNU General Public License version 3, or later
 *
 */

use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\ValidationData;
use Symfony\Component\HttpFoundation\Response;

class Auth
{
    protected static $tokenExpiresAt = 80000; // seconds

    /**
     * Authenticate the user by email & password
     *
     * @param string $email
     * @param string $plainTextPassword
     */
    public static function login()
    {
        $loginData = EdhecTools::getAllDataReceived();

        if (isset($loginData["username"]) && !empty($loginData["username"]) && isset($loginData["password"]) && !empty($loginData["password"])) {
            $username = $loginData["username"];
            $password = $loginData["password"];

            $uid = \Drupal::service('user.auth')->authenticate($username, $password);

            // success
            if ($uid) {
                $user = \Drupal\user\Entity\User::load($uid);

                if ($user->get('status')->value) {
                    if (in_array('administrator', $user->getRoles())) {
                        $data = self::makeUserData($user);
                        $data->token = self::generateToken($data);

                        EdhecTools::setResponse(102, "success", $data, 'Authentication succeed.');
                    } else {
                        EdhecTools::setResponse(103, "error", null, 'You do not have administrator permissions to use this System.', Response::HTTP_BAD_REQUEST);
                    }
                } else {
                    EdhecTools::setResponse(105, "error", null, 'Your account is disabled.', Response::HTTP_BAD_REQUEST);
                }
            } else {
                // failed
                EdhecTools::setResponse(101, "error", null, 'Authentication failed.', Response::HTTP_NOT_FOUND);
            }
        } else {
            // failed
            EdhecTools::setResponse(104, "error", null, 'Invalid email / password value', Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Get user profile
     *
     * @return mixed
     */
    public static function getProfile($return = false)
    {
        global $parsedToken;

        $claims = $parsedToken->getClaims();
        $uid = $claims["data"]->getValue()->id;
        $user = \Drupal\user\Entity\User::load($uid);

        if ($user->get('status')->value) {
            $currentUser = self::makeUserData($user);

            if ($return) {
                return $currentUser;
            }

            EdhecTools::setResponse(106, "success", $currentUser, 'User profile.');
        } else {
            EdhecTools::setResponse(105, "error", null, 'Your account is disabled.', Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Make a user object
     *
     * @param mixed $data
     *
     * @return object
     */
    private static function makeUserData($user)
    {
        $data = new stdClass();
        $data->id = (int) $user->get('uid')->value;
        // $data->firstname           = $user->get('name')->value;
        // $data->lastname            = $user->get('name')->value;
        $data->email = $user->get('mail')->value;
        $data->username = $user->get('name')->value;
        $data->langCode = $user->get('langcode')->value;
        $data->timezone = $user->get('timezone')->value;
        $data->timezone = $user->get('timezone')->value;
        $data->roles = $user->get('roles')->target_id;
        $data->picture = $user->get('user_picture')->value; // @TODO

        return $data;
    }

    /**
     * Generate a token (jwt)
     *
     * @param mixed $data (optional)
     * @return string
     */
    public static function generateToken($data = null)
    {
        $signer = new Sha256();
        $time = time();

        $token = (new Builder())->issuedBy(EDHEC_API_NAME) // Configures the issuer (iss claim)
            ->permittedFor(EDHEC_API_NAME) // Configures the audience (aud claim)
            ->identifiedBy(EDHEC_SECURE_KEY, true) // Configures the id (jti claim), replicating as a header item
            ->issuedAt($time) // Configures the time that the token was issue (iat claim)
        //->canOnlyBeUsedAfter($time + 60) // Configures the time that the token can be used (nbf claim)
            ->expiresAt($time + self::$tokenExpiresAt) // Configures the expiration time of the token (exp claim)
            ->withClaim('data', $data) // Configures a new claim, called "uid"
            ->getToken($signer, new Key(EDHEC_SECURE_KEY)); // Retrieves the generated token

        return (string) $token; // The string representation of the object is a JWT string (pretty easy, right?)
    }

    /**
     * Validate a token (jwt)
     *
     * @param string $token
     * @return boolean
     */
    public static function validateToken($token)
    {
        $isValid = false;
        global $parsedToken;

        try {
            $parsedToken = self::getParsedToken($token); // Parses from a string

            $data = new ValidationData();
            $data->setIssuer(EDHEC_API_NAME);
            $data->setAudience(EDHEC_API_NAME);
            $data->setId(EDHEC_SECURE_KEY);

            $isValid = $parsedToken->validate($data);
        } catch (Exception $exc) {
            //echo $exc->getTraceAsString();
        }

        return $isValid;
    }

    /**
     * Extract a token
     *
     * @param string $token
     *
     * @return object
     */
    public static function getParsedToken($token)
    {
        $token = (new Parser())->parse((string) $token); // Parses from a string
        //$token->getHeaders(); // Retrieves the token header
        //$token->getClaims(); // Retrieves the token claims

        return $token;
    }

    /**
     * Retrieve token from header | POST | GET
     *
     * @return string
     */
    public static function retrieveToken()
    {
        if (isset($_GET['jwt'])) {
            $token = $_GET['jwt'];
        } elseif (isset($_POST['jwt'])) {
            $token = $_POST['jwt'];
        } else {
            $token = EdhecTools::getBearerToken();
        }

        return $token;
    }
}
