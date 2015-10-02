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
 * Holder of a Gitkit user account.
 */
class Gitkit_Account {
  private $localId;
  private $email;
  private $providerId;
  private $providerInfo;
  private $displayName;
  private $photoUrl;
  private $emailVerified;
  private $passwordHash;
  private $salt;

  public function __construct($apiResponse = array()) {
    if (isset($apiResponse['localId'])) {
      $this->localId = $apiResponse['localId'];
    } else if (isset($apiResponse['user_id'])) {
      $this->localId = $apiResponse['user_id'];
    }
    if (isset($apiResponse['email'])) {
      $this->email = $apiResponse['email'];
    }
    if (isset($apiResponse['displayName'])) {
      $this->displayName = $apiResponse['displayName'];
    }
    if (isset($apiResponse['photoUrl'])) {
      $this->photoUrl = $apiResponse['photoUrl'];
    }
    if (isset($apiResponse['emailVerified'])) {
      $this->emailVerified = $apiResponse['emailVerified'];
    }
    if (isset($apiResponse['providerUserInfo'])) {
      $this->providerInfo = $apiResponse['providerUserInfo'];
    }
  }

  public function getUserId() {
    return $this->localId;
  }

  public function setUserId($localId) {
    $this->localId = $localId;
  }

  public function getEmail() {
    return $this->email;
  }

  public function setEmail($email) {
    $this->email = $email;
  }

  public function getProviderId() {
    return $this->providerId;
  }
  
  public function setProviderId($providerId) {
    $this->providerId = $providerId;
  }
  
  public function getProviderInfo() {
    return $this->providerInfo;
  }
  
  public function getDisplayName() {
    return $this->displayName;
  }

  public function setDisplayName($displayName) {
    $this->displayName = $displayName;
  }

  public function getPhotoUrl() {
    return $this->photoUrl;
  }

  public function setPhotoUrl($photoUrl) {
    $this->photoUrl = $photoUrl;
  }

  public function isEmailVerified() {
    return $this->emailVerified;
  }

  public function setEmailVerified($verified) {
    $this->emailVerified = $verified;
  }

  public function getPasswordHash() {
    return $this->passwordHash;
  }

  public function setPasswordHash($passwordHash) {
    $this->passwordHash = $passwordHash;
  }

  public function getSalt() {
    return $this->salt;
  }

  public function setSalt($salt) {
    $this->salt = $salt;
  }
}
