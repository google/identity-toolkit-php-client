<?php
/*
 * Copyright 2014 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

require_once 'RpcHelper.php';
require_once 'DownloadIterator.php';

/**
 * Google Identity Toolkit PHP client library.
 * https://developers.google.com/identity-toolkit/v3/‎
 */
class Gitkit_Client {
  private static $GITKIT_API_BASE =
      'https://www.googleapis.com/identitytoolkit/v3/relyingparty/';
  private static $GTIKIT_TOKEN_ISSUER = 'https://identitytoolkit.google.com/';
  private static $DEFAULT_COOKIE_NAME = 'gtoken';
  private $oauth2Client;
  private $clientId;
  private $widgetUrl;
  private $cookieName;
  private $rpcHelper;

  /**
   * Constructs the Gitkit client.
   *
   * @param string $clientId Google OAuth2 web client id
   * @param string $widgetUrl the url hosting the Gitkit widget
   * @param string $cookieName cookie name for Gitkit token
   * @param Gitkit_RpcHelper $rpcHelper the rpc helper
   */
  public function __construct($clientId, $widgetUrl, $cookieName, $rpcHelper) {
    $this->clientId = $clientId;
    $this->widgetUrl = $widgetUrl;
    $this->cookieName = $cookieName;
    $this->oauth2Client = new Google_Auth_OAuth2(new Google_Client());
    $this->rpcHelper = $rpcHelper;
  }

  /**
   * Creates a Gitkit client from a config file (json format).
   *
   * @param string $file file name of the json config file
   * @return Gitkit_Client created Gitkit client
   */
  public static function createFromFile($file) {
    $jsonConfig = json_decode(file_get_contents($file), true);
    return self::createFromConfig($jsonConfig);
  }

  /**
   * Creates a Gitkit client from the config array.
   *
   * @param array $config config parameters
   * @param null|Gitkit_RpcHelper $rpcHelper Gitkit Rpc helper object
   * @return Gitkit_Client created Gitkit client
   * @throws Gitkit_ClientException if required config is missing
   */
  public static function createFromConfig($config, $rpcHelper = null) {
    if (!isset($config['clientId'])) {
      throw new Gitkit_ClientException("\"clientId\" should be configured");
    }
    if (!isset($config['widgetUrl'])) {
      throw new Gitkit_ClientException("\"widgetUrl\" should be configured");
    }
    if (isset($config["cookieName"])) {
      $cookieName = $config['cookieName'];
    } else {
      $cookieName = self::$DEFAULT_COOKIE_NAME;
    }
    if (!$rpcHelper) {
      if (!isset($config['serviceAccountEmail'])) {
        throw new Gitkit_ClientException(
            "\"serviceAccountEmail\" should be configured");
      }
      if (!isset($config['serviceAccountPrivateKeyFile'])) {
        throw new Gitkit_ClientException(
            "\"serviceAccountPrivateKeyFile\" should be configured");
      }
      $p12Key = file_get_contents($config["serviceAccountPrivateKeyFile"]);
      if ($p12Key === false) {
        throw new Gitkit_ClientException(
            "Can not read file " . $config["serviceAccountPrivateKeyFile"]);
      }
      if (isset($config['serverApiKey'])) {
        $serverApiKey = $config['serverApiKey'];
      } else {
        $serverApiKey = null;
      }
      $rpcHelper = new Gitkit_RpcHelper(
          $config["serviceAccountEmail"],
          $p12Key,
          self::$GITKIT_API_BASE,
          new Google_Auth_OAuth2(new Google_Client()),
          $serverApiKey);
    }
    return new Gitkit_Client($config['clientId'], $config['widgetUrl'],
        $cookieName, $rpcHelper);
  }

  /**
   * Validates a Gitkit token. User info is extracted from the token only.
   *
   * @param string $gitToken token to be checked
   * @return Gitkit_Account|null Gitkit user corresponding to the token, null
   * for invalid token
   */
  public function validateToken($gitToken) {
    if ($gitToken) {
      $loginTicket = $this->oauth2Client->verifySignedJwtWithCerts(
          $gitToken,
          $this->getCerts(),
          $this->clientId,
          self::$GTIKIT_TOKEN_ISSUER,
          180 * 86400)->getAttributes();
      $jwt = $loginTicket["payload"];
      if ($jwt) {
        $user = new Gitkit_Account();
        $user->setUserId($jwt["user_id"]);
        $user->setEmail($jwt["email"]);
        if (isset($jwt["provider_id"])) {
          $user->setProviderId($jwt["provider_id"]);
        } else {
          $user->setProviderId(null);
        }
        $user->setEmailVerified($jwt["verified"]);
        if (isset($jwt["display_name"])) {
          $user->setDisplayName($jwt["display_name"]);
        }
        if (isset($jwt["photo_url"])) {
          $user->setPhotoUrl($jwt["photo_url"]);
        }
        return $user;
      }
    }
    return null;
  }

  /**
   * Validates the token in the http request cookie
   *
   * @return Gitkit_Account|null Gitkit user corresponding to the token, null
   * for invalid token
   */
  public function validateTokenInRequest() {
    return $this->validateToken($_COOKIE[$this->cookieName], $this->clientId);
  }

  /**
   * Gets raw token string in the http request.
   *
   * @return mixed token string
   */
  public function getTokenString() {
    if (isset($_COOKIE[$this->cookieName])) {
      return $_COOKIE[$this->cookieName];
    }
    return null;
  }

  /**
   * Gets GitkitUser for the http request. Complete user info is retrieved from
   * Gitkit server.
   *
   * @return Gitkit_Account|null Gitkit user at Gitkit server, null for invalid
   * token
   */
  public function getUserInRequest() {
    if (isset($_COOKIE[$this->cookieName])) {
      $user = $this->validateToken($_COOKIE[$this->cookieName],
          $this->clientId);
      if ($user) {
        $accountInfo = $this->getUserById($user->getUserId());
        $accountInfo->setProviderId($user->getProviderId());
        return $accountInfo;
      }
    }
    return null;
  }

  /**
   * Gets user info by email.
   *
   * @param string $email user email
   * @return Gitkit_Account user account info
   */
  public function getUserByEmail($email) {
    return new Gitkit_Account($this->rpcHelper->getAccountInfoByEmail($email));
  }

  /**
   * Gets user info by user identifier at Gitkit.
   *
   * @param string $id user identifier
   * @return Gitkit_Account user account info
   */
  public function getUserById($id) {
    return new Gitkit_Account($this->rpcHelper->getAccountInfoById($id));
  }

  /**
   * Sets user info at Gitkit server.
   *
   * @param Gitkit_Account $gitkitAccount user info to be updated
   * @return mixed server response
   */
  public function updateUser($gitkitAccount) {
    return $this->rpcHelper->updateAccount($gitkitAccount);
  }

  /**
   * Deletes a user account at Gitkit server.
   *
   * @param string $id user identifier to be deleted
   * @return mixed server response
   */
  public function deleteUser($id) {
    return $this->rpcHelper->deleteAccount($id);
  }

  /**
   * Uploads multiple accounts info to Gitkit server.
   *
   * @param string $hashAlgorithm password hash algorithm. See Gitkit doc for
   *                              supported names.
   * @param string $hashKey raw key for the algorithm
   * @param array $accounts array of Gitkit_Account to be uploaded
   * @param null|int $rounds Rounds of the hash function
   * @param null|int $memoryCost Memory cost of the hash function
   * @throws Gitkit_ServerException if error happens
   */
  public function uploadUsers($hashAlgorithm, $hashKey, $accounts,
      $rounds = null, $memoryCost = null) {
    $this->rpcHelper->uploadAccount($hashAlgorithm, $hashKey,
        $this->toJsonRequest($accounts), $rounds, $memoryCost);
  }

  /**
   * Downloads all user account from Gitkit server.
   *
   * Usage:
   *  $iterator = $gitkitClient->getAllUsers(10);
   *  while ($iterator->valid()) {
   *    // do something with ($iterator->current());
   *    $iterator->next();
   *  }
   *
   * @param int $maxResults max results per request
   * @return Gitkit_DownloadIterator iterator to fetch all user accounts
   */
  public function getAllUsers($maxResults = null) {
    return new Gitkit_DownloadIterator($this->rpcHelper, $maxResults);
  }

  /**
   * Gets out-of-band results for ResetPassword, ChangeEmail operations etc.
   *
   * @param null|array $param http post body
   * @param null|string $user_ip end user IP address
   * @param null|string $gitkit_token Gitkit token in the request
   * @return array out-of-band results:
   * array(
   *   'email' => email of the user,
   *   'oldEmail' => old email (for ChangeEmail only),
   *   'newEmail' => new email (for ChangeEmail only),
   *   'oobLink' => url for user click to finish the operation,
   *   'action' => 'RESET_PASSWORD', or 'CHANGE_EMAIL',
   *   'response_body' => http response to be sent back to Gitkit widget
   * )
   */
  public function getOobResults($param = null,
      $user_ip = null, $gitkit_token = null) {
    if (!$param) {
      $param = $_POST;
    }
    if (!$user_ip) {
      $user_ip = $_SERVER['REMOTE_ADDR'];
    }
    if (!$gitkit_token) {
      $gitkit_token = $this->getTokenString();
    }
    if (isset($param['action'])) {
      try {
        if ($param['action'] == 'resetPassword') {
          $oob_link = $this->buildOobLink(
              $this->passwordResetRequest($param, $user_ip),
              $param['action']);
          return $this->passwordResetResponse($oob_link, $param);
        } else if ($param['action'] == 'changeEmail') {
          if (!$gitkit_token) {
            return $this->failureOobMsg('login is required');
          }
          $oob_link = $this->buildOobLink(
              $this->changeEmailRequest($param, $user_ip, $gitkit_token),
              $param['action']);
          return $this->emailChangeResponse($oob_link, $param);
        }
      } catch (Gitkit_ClientException $error) {
        return $this->failureOobMsg($error->getMessage());
      }
    }
    return $this->failureOobMsg('unknown action type');
  }

  /**
   * Gets verification url to verify user's email.
   *
   * @param string $email user's email to be verified
   * @return string url for user click to verify the email
   */
  public function getEmailVerificationLink($email) {
    $param = array(
      'email' => $email,
      'requestType' => 'VERIFY_EMAIL'
    );
    return $this->buildOobLink($param, 'verifyEmail');
  }

  /**
   * Gets Gitkit public certs.
   *
   * @return array certs in the format of {keyId => cert}.
   */
  protected function getCerts() {
    return $this->rpcHelper->getGitkitCerts();
  }

  /**
   * Converts Gitkit account array to json request.
   *
   * @param array $accounts Gitkit account array
   * @return array json request
   */
  private function toJsonRequest($accounts) {
    $jsonUsers = array();
    foreach($accounts as $account) {
      $user = array(
        'email' => $account->getEmail(),
        'localId' => $account->getUserId(),
        'emailVerified' => $account->isEmailVerified(),
        'displayName' => $account->getDisplayName(),
        'passwordHash' => Google_Utils::urlSafeB64Encode(
            $account->getPasswordHash()),
        'salt' => Google_Utils::urlSafeB64Encode($account->getSalt())
      );
      array_push($jsonUsers, $user);
    }
    return $jsonUsers;
  }

  /**
   * Builds the url of out-of-band confirmation.
   *
   * @param array $param oob request param
   * @param string $action 'RESET_PASSWORD' or 'CHANGE_EMAIL'
   * @return string the oob url
   */
  private function buildOobLink($param, $action) {
    $code = $this->rpcHelper->getOobCode($param);
    $separator = parse_url($this->widgetUrl, PHP_URL_QUERY) ? '&' : '?';
    return $this->widgetUrl . $separator  .
        http_build_query(array('mode' => $action, 'oobCode' => $code));
  }

  private function passwordResetRequest($param, $user_ip) {
    return array(
        'email' => $param['email'],
        'userIp' => $user_ip,
        'captchaResp' => $param['response'],
        'requestType' => 'PASSWORD_RESET');
  }

  private function passwordResetResponse($oob_link, $param) {
    return array(
        'email' => $param['email'],
        'oobLink' => $oob_link,
        'action' => 'RESET_PASSWORD',
        'response_body' => json_encode(array('success' => true)));
  }

  private function changeEmailRequest($param, $user_ip, $gitkit_token) {
    return array(
        'email' => $param['oldEmail'],
        'newEmail' => $param['newEmail'],
        'userIp' => $user_ip,
        'idToken' => $gitkit_token,
        'requestType' => 'NEW_EMAIL_ACCEPT');
  }

  private function emailChangeResponse($oob_link, $param) {
    return array(
        'oldEmail' => $param['oldEmail'],
        'newEmail' => $param['newEmail'],
        'oobLink' => $oob_link,
        'action' => 'CHANGE_EMAIL',
        'response_body' => json_encode(array('success' => true)));
  }

  private function failureOobMsg($string) {
    return json_encode(array('response_body' => array('error' => $string)));
  }
}
