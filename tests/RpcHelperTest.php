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

require_once 'TestData.php';

class RpcHelperTest extends PHPUnit_Framework_TestCase {

  private $rpcHelper;
  public function setUp() {
    $this->rpcHelper = new Gitkit_RpcHelper(
        'service-email',
        'TestKey.p12',
        'api-url',
        new Google_Auth_OAuth2(new Google_Client()),
        'server-api-key');
  }

  public function testClientError() {
    $errorMessage = 'error-msg';
    $clientErrorResponse = array(
        'error' => array(
            'code' => 400,
            'message' => $errorMessage));
    try {
      $this->rpcHelper->checkGitkitError($clientErrorResponse);
    } catch (Gitkit_ClientException $e) {
      $this->assertEquals($errorMessage, $e->getMessage());
    }
  }

  public function testServerError() {
    $errorMessage = 'error-msg';
    $clientErrorResponse = array(
      'error' => array(
        'code' => 500,
        'message' => $errorMessage));
    try {
      $this->rpcHelper->checkGitkitError($clientErrorResponse);
    } catch (Gitkit_ServerException $e) {
      $this->assertEquals($errorMessage, $e->getMessage());
    }
  }
}
