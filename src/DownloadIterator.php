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

require_once 'GitkitClient.php';
require_once 'RpcHelper.php';

/**
 * Iterator for paginated DownloadAccount response.
 */
class Gitkit_DownloadIterator {

  private $nextPageToken;
  private $iterator;
  private $rpcHelper;
  private $maxResults;
  private $isLastPage;

  public function __construct($rpcHelper, $maxResults) {
    $this->rpcHelper = $rpcHelper;
    $this->maxResults = $maxResults;
    $this->isLastPage = false;
    $this->getPaginatedResults();
  }

  public function valid() {
    if (!$this->iterator->valid()) {
      $this->getPaginatedResults();
    }
    return $this->iterator->valid();
  }

  public function next() {
    if ($this->iterator->valid()) {
      $this->iterator->next();
    } else {
      throw new Gitkit_ClientException("invalid download account iterator");
    }
  }

  public function current() {
    if ($this->iterator->valid()) {
      return new Gitkit_Account($this->iterator->current());
    } else {
      throw new Gitkit_ClientException("invalid download account iterator");
    }
  }

  private function getPaginatedResults() {
    if ($this->isLastPage) {
      $this->iterator = new EmptyIterator();
    } else {
      $response = $this->rpcHelper->downloadAccount($this->nextPageToken,
          $this->maxResults);
      if (isset($response['nextPageToken'])) {
        $this->nextPageToken = $response['nextPageToken'];
      } else {
        $this->isLastPage = true;
      }
      if (isset($response['users'])) {
        $arr = new ArrayObject($response['users']);
        $this->iterator = new NoRewindIterator($arr->getIterator());
      } else {
        $this->iterator = new EmptyIterator();
      }
    }
  }
}
