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

class GitkitClientTest extends PHPUnit_Framework_TestCase {

  private $config;
  private $rpcStubBuilder;

  public function setUp() {
    $this->config = array(
      'clientId' => '924226504183.apps.googleusercontent.com',
      'widgetUrl' => 'http://example.com/widget'
    );
    $this->rpcStubBuilder = $this->getMockBuilder('Gitkit_RpcHelper')
      ->disableOriginalConstructor();
  }

  public function testVerifyToken() {
    $rpcStub = $this->rpcStubBuilder->setMethods(array('getGitkitCerts'))
        ->getMock();

    $rpcStub->expects($this->once())
        ->method('getGitkitCerts')
        ->will($this->returnValue(TestData::getCerts()));

    $gitkitClient = Gitkit_Client::createFromConfig($this->config, $rpcStub);
    $user = $gitkitClient->validateToken(TestData::getToken());
    $this->assertNotNull($user);
  }

  public function testGetAllUsers() {
    $rpcStub = $this->rpcStubBuilder->setMethods(array('downloadAccount'))
        ->getMock();

    $page1 = array(
        'nextPageToken' => 'page2_token',
        'users' => array($this->userRecord(1), $this->userRecord(2)));
    $page2 = array(
        'users' => array($this->userRecord(3)));

    $rpcStub->expects($this->any())
        ->method('downloadAccount')
        ->withConsecutive(
            array($this->equalTo(null), $this->equalTo(10)),
            array($this->equalTo('page2_token'), $this->equalTo(10)))
        ->will($this->onConsecutiveCalls($page1, $page2));

    $gitkitClient = Gitkit_Client::createFromConfig($this->config, $rpcStub);
    $iterator = $gitkitClient->getAllUsers(10);
    $index = 0;
    while ($iterator->valid()) {
      $index ++;
      $user = $iterator->current();
      $expectedUser = $this->userRecord($index);
      $this->assertEquals($expectedUser['localId'], $user->getUserId());
      $iterator->next();
    }
    // 3 accounts are downloaded in total
    $this->assertEquals(3, $index);
  }

  public function testGetUserByEmail() {
    $testUser = $this->userRecord(0);
    $rpcStub = $this->rpcStubBuilder->setMethods(array('getAccountInfoByEmail'))
        ->getMock();
    $rpcStub->expects($this->any())
        ->method('getAccountInfoByEmail')
        ->with($testUser['email'])
        ->will($this->returnValue($testUser));
    $gitkitClient = Gitkit_Client::createFromConfig($this->config, $rpcStub);
    $this->assertEquals(
        $testUser['localId'],
        $gitkitClient->getUserByEmail($testUser['email'])->getUserId());
  }

  public function testOobForResetPassword() {
    $rpcStub = $this->rpcStubBuilder->setMethods(array('getOobCode'))
        ->getMock();
    $rpcStub->expects($this->any())
        ->method('getOobCode')
        ->will($this->returnValue('oob-code'));
    $gitkitClient = Gitkit_Client::createFromConfig($this->config, $rpcStub);
    $oobReq = array(
        'action' => 'resetPassword',
        'email' => 'user@example.com',
        'response' => '100');

    $oobResult = $gitkitClient->getOobResults($oobReq, '1.1.1.1');

    $this->assertEquals($oobReq['email'], $oobResult['email']);
    $this->assertEquals('RESET_PASSWORD', $oobResult['action']);
    $this->assertEquals(
        'http://example.com/widget?mode=resetPassword&oobCode=oob-code',
        $oobResult['oobLink']);
  }

  public function testGetEmailVerificationLink() {
    $rpcStub = $this->rpcStubBuilder->setMethods(array('getOobCode'))
        ->getMock();
    $rpcStub->expects($this->any())
        ->method('getOobCode')
        ->will($this->returnValue('oob-code'));
    $gitkitClient = Gitkit_Client::createFromConfig($this->config, $rpcStub);

    $verifyLink = $gitkitClient->getEmailVerificationLink('user@example.com');

    $this->assertEquals(
        'http://example.com/widget?mode=verifyEmail&oobCode=oob-code',
        $verifyLink);
  }

  public function testOobWithException() {
    $rpcStub = $this->rpcStubBuilder->setMethods(array('getOobCode'))
        ->getMock();
    $rpcStub->expects($this->any())
        ->method('getOobCode')
        ->will($this->throwException(new Gitkit_ClientException('error-msg')));
    $gitkitClient = Gitkit_Client::createFromConfig($this->config, $rpcStub);
    $oobReq = array(
        'action' => 'resetPassword',
        'email' => 'user@example.com',
        'response' => '100');

    try {
      $gitkitClient->getOobResults('http://example.com/oob',
          $oobReq, '1.1.1.1');
    } catch (Gitkit_ClientException $e) {
      $this->assertEquals('error-msg', $e->getMessage());
    }
  }

  private function userRecord($index) {
    return array('email' => 'email-' . $index, 'localId' => 'user-' . $index);
  }
}
