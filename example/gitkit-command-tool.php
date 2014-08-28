<?php

require_once __DIR__ . '/../vendor/autoload.php';

$gitkitClient = Gitkit_Client::createFromFile(
    dirname(__FILE__) . '/gitkit-server-config.json');

// ---- upload account -----
//$hashKey = "\x01\x02\x03";
//$gitkitClient->uploadUsers('HMAC_SHA1', $hashKey, createNewUsers($hashKey));

// ---- get account info with service account ----
//$user = $gitkitClient->getUserById("1234");
//var_dump($user);

// ---- download account ----
//  $iterator = $gitkitClient->getAllUsers(3);
//  while ($iterator->valid()) {
//    var_dump($iterator->current());
//    $iterator->next();
//  }

// ---- delete account ----
// $gitkitClient->deleteUser('1234');

function createNewUsers($hashKey) {
  $allUsers = array();

  $gitkitUser = new Gitkit_Account();
  $gitkitUser->setEmail("1234@example.com");
  $gitkitUser->setUserId("1234");
  $salt = "\05\06\07";
  $password = '1111';
  $gitkitUser->setSalt($salt);
  $gitkitUser->setPasswordHash(hash_hmac('sha1', $password . $salt, $hashKey,
      true));
  array_push($allUsers, $gitkitUser);

  $gitkitUser = new Gitkit_Account();
  $gitkitUser->setEmail('5678@example.com');
  $gitkitUser->setUserId('5678');
  $salt = "\15\16\17";
  $password = '5555';
  $gitkitUser->setSalt($salt);
  $gitkitUser->setPasswordHash(hash_hmac('sha1', $password . $salt, $hashKey,
      true));
  array_push($allUsers, $gitkitUser);

  return $allUsers;
}
