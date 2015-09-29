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

/**
 * Helper for Gitkit RPCs.
 */
class Gitkit_RpcHelper {

  private static $GITKIT_SCOPE =
      'https://www.googleapis.com/auth/identitytoolkit';
  private $p12Key;
  private $serviceAccountEmail;
  private $gitkitApisUrl;
  private $apiKey;
  private $oauth2Client;

  /**
   * Constructs the helper.
   *
   * @param string $serviceAccountEmail Google service account email
   * @param string $p12Key Google service account private p12 key
   * @param string $gitkitApiUrl Gitkit API endpoint
   * @param Google_Auth_OAuth2 $oauth2Client Google oauth2 client
   * @param string|null $serverApiKey Google server-side Api key
   */
  public function __construct($serviceAccountEmail, $p12Key, $gitkitApiUrl,
      $oauth2Client, $serverApiKey) {
    $this->p12Key = $p12Key;
    $this->serviceAccountEmail = $serviceAccountEmail;
    $this->gitkitApisUrl = $gitkitApiUrl;
    $this->apiKey = $serverApiKey;
    $this->oauth2Client = $oauth2Client;
    $credentials = new Google_Auth_AssertionCredentials(
      $this->serviceAccountEmail,
      self::$GITKIT_SCOPE,
      $this->p12Key
    );
    $this->oauth2Client->setAssertionCredentials($credentials);
  }

  /**
   * Downloads Gitkit public certs.
   *
   * @return array|string certs
   */
  public function getGitkitCerts() {
    $certUrl = $this->gitkitApisUrl . 'publicKeys';
    if ($this->apiKey) {
      // try server-key first
      return $this->oauth2Client->retrieveCertsFromLocation(
          $certUrl . '?key=' . $this->apiKey);
    } else {
      // fallback to service account
      $httpRequest = new Google_Http_Request($certUrl);
      $response = $this->oauth2Client->authenticatedRequest($httpRequest)
          ->getResponseBody();
      return json_decode($response);
    }
  }

  /**
   * Invokes the GetAccountInfo API with email.
   *
   * @param string $email user email
   * @return array user account info
   */
  public function getAccountInfoByEmail($email) {
    $data = array('email' => array ($email));
    $result = $this->invokeGitkitApiWithServiceAccount('getAccountInfo', $data);
    return $result['users'][0];
  }

  /**
   * Invokes the GetAccountInfo API with user id.
   *
   * @param string $userId user identifier
   * @return array user account info
   */
  public function getAccountInfoById($userId) {
    $data = array('localId' => array ($userId));
    $result = $this->invokeGitkitApiWithServiceAccount('getAccountInfo', $data);
    return $result['users'][0];
  }

  /**
   * Invokes the SetAccountInfo API.
   *
   * @param Gitkit_Account $gitkitAccount account info to be updated
   * @return array updated account info
   */
  public function updateAccount($gitkitAccount) {
    $data = array(
      'email' => $gitkitAccount->getEmail(),
      'localId' => $gitkitAccount->getUserId(),
      'displayName' => $gitkitAccount->getDisplayName(),
      'emailVerified' => $gitkitAccount->isEmailVerified(),
      'photoUrl' => $gitkitAccount->getPhotoUrl()
    );
    return $this->invokeGitkitApiWithServiceAccount('setAccountInfo', $data);
  }

  /**
   * Invokes the DeleteAccount API.
   *
   * @param string $userId user id
   * @return array server response
   */
  public function deleteAccount($userId) {
    $data = array('localId' => $userId);
    return $this->invokeGitkitApiWithServiceAccount('deleteAccount', $data);
  }

  /**
   * Invokes the UploadAccount API.
   *
   * @param string $hashAlgorithm password hash algorithm. See Gitkit doc for
   *                              supported names.
   * @param string $hashKey raw key for the algorithm
   * @param array $accounts array of account info to be uploaded
   * @param null|int $rounds Rounds of the hash function
   * @param null|int $memoryCost Memory cost of the hash function
   */
  public function uploadAccount($hashAlgorithm, $hashKey, $accounts,
      $rounds, $memoryCost) {
    $data = array(
      'hashAlgorithm' => $hashAlgorithm,
      'signerKey' => Google_Utils::urlSafeB64Encode($hashKey),
      'users' => $accounts
    );
    if ($rounds) {
      $data['rounds'] = $rounds;
    }
    if ($memoryCost) {
      $data['memoryCost'] = $memoryCost;
    }
    $this->invokeGitkitApiWithServiceAccount('uploadAccount', $data);
  }

  /**
   * Invokes the DownloadAccount API.
   *
   * @param string|null $nextPageToken next page token to download the next
   *                                   pagination.
   * @param int $maxResults max results per request
   * @return array of accounts info and nextPageToken
   */
  public function downloadAccount($nextPageToken = null, $maxResults = 10) {
    $data = array();
    if ($nextPageToken) {
      $data['nextPageToken'] = $nextPageToken;
    }
    $data['maxResults'] = $maxResults;
    return $this->invokeGitkitApiWithServiceAccount('downloadAccount', $data);
  }

  /**
   * Invokes the GetOobConfirmationCode API.
   *
   * @param array $param parameters for the request
   * @return string the out-of-band code
   * @throws Gitkit_ClientException
   */
  public function getOobCode($param) {
    $response = $this->invokeGitkitApiWithServiceAccount(
        'getOobConfirmationCode', $param);
    if (isset($response['oobCode'])) {
      return $response['oobCode'];
    } else {
      throw new Gitkit_ClientException("can not get oob-code");
    }
  }

  /**
   * Sends the authenticated request to Gitkit API. The request contains an
   * OAuth2 access_token generated from service account.
   *
   * @param string $method the API method name
   * @param array $data http post data for the api
   * @return array server response
   * @throws Gitkit_ClientException if input is invalid
   * @throws Gitkit_ServerException if there is server error
   */
  public function invokeGitkitApiWithServiceAccount($method, $data) {
    $httpRequest = new Google_Http_Request(
        $this->gitkitApisUrl . $method,
        'POST',
        null,
        json_encode($data));
    $contentTypeHeader = array();
    $contentTypeHeader['content-type'] = 'application/json; charset=UTF-8';
    $httpRequest->setRequestHeaders($contentTypeHeader);
    $response = $this->oauth2Client->authenticatedRequest($httpRequest)
        ->getResponseBody();
    return $this->checkGitkitError(json_decode($response, true));
  }

  /**
   * Checks the error in the response.
   *
   * @param array $response server response to be checked
   * @return array the response if there is no error
   * @throws Gitkit_ClientException if input is invalid
   * @throws Gitkit_ServerException if there is server error
   */
  public function checkGitkitError($response) {
    if (isset($response['error'])) {
      $error = $response['error'];
      if (!isset($error['code'])) {
        throw new Gitkit_ServerException('null error code from Gitkit server');
      } else {
        $code = $error['code'];
        if (strpos($code, '4') === 0) {
          throw new Gitkit_ClientException($error['message']);
        } else {
          throw new Gitkit_ServerException($error['message']);
        }
      }
    } else {
      return $response;
    }
  }
}
