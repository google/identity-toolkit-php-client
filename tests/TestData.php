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

class TestData {

  private static $PEM_CERT = <<<CERT
-----BEGIN CERTIFICATE-----
MIIDDzCCAfegAwIBAgIJAMKLYPybcIAZMA0GCSqGSIb3DQEBBQUAMB4xHDAaBgNV
BAMME0dvb2dsZSBBdXRoIFRvb2xraXQwHhcNMTMwNDI1MTUyMDExWhcNMTQwNDI1
MTUyMDExWjAeMRwwGgYDVQQDDBNHb29nbGUgQXV0aCBUb29sa2l0MIIBIjANBgkq
hkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAyVZ3j4Uovsspa6dCiTZAC/SndulGDKYf
mVr95ea+u4k0XMvvd7w9k0wq4d1xagMIKHZhAnYLvYfW0O5D8+d58/+UJq4vrlY9
zOcTOsOoZ5tX325TMIJmn7IzMMpds1tA2MfWNiMkf/+AFZfxg14jyBeRdk4LVZWa
FxMz9Fs/23pTuNBYwGzM3xyZajgEhJ9gp3k95qlQPq00bIMa69YiAcmyr4RVYpgW
qd+WPdROEZvRLsCaIGTeehLR6zceUPrTofbOo82JI3/PTfJ+bm+IzXRq5Ogynfw6
f4z0pJ/YuUlmGD+rrm5Dfja/V3QTPyqzFpQSPXND7OdpT63MryKHtQIDAQABo1Aw
TjAdBgNVHQ4EFgQUwGCN266hsEwDjx2aNQ4cdPSjmJMwHwYDVR0jBBgwFoAUwGCN
266hsEwDjx2aNQ4cdPSjmJMwDAYDVR0TBAUwAwEB/zANBgkqhkiG9w0BAQUFAAOC
AQEABDl3G5Ao3ZTXdeNoeF8knWl//6pyxz/Jhv1/PApA9NQpyhqijmGyDMvCLt0F
02HVTqg/MYG5zwUCroV9daraEdn5302sx8kh1Ei8SBCKzoDa7B8wSd2/KrEd6zsX
/7ZVzSNx37xk5Jhzz6EmXfY7z22DmFWggxyeTYGgR5YgKkuslbIxxEKjVhK5YK60
1pyRhl0tqe2xt+FMn0tvLdkCfVCvyDj2cD7g5XBVXZS4rqwfy1XpzQfSuU4sQcgn
VpgjVOtnax48yJFXeNTrOoTPiQV2AZQSrGuKoJ8GojM6oZuEv5S2moB3IMKyU5F3
RQ1NcLfJHhAz2ccdbaBXJaP4Hw==
-----END CERTIFICATE-----
CERT;

  public static function getCerts() {
    return array('40QoZg' => self::$PEM_CERT);
  }

  public static function getToken() {
    return
        'eyJhbGciOiJSUzI1NiIsImtpZCI6IjQwUW9aZyJ9.eyJpc3MiOiJodHRwczovL2dpdG' .
        'tpdC5nb29nbGUuY29tLyIsImF1ZCI6IjkyNDIyNjUwNDE4My5hcHBzLmdvb2dsZXVzZ' .
        'XJjb250ZW50LmNvbSIsImlhdCI6MTM5OTAwMTI0MywiZXhwIjoxNDAwMjEwODQzLCJ1' .
        'c2VyX2lkIjoiMTIzNCIsImVtYWlsIjoiMTIzNEBleGFtcGxlLmNvbSIsInZlcmlmaWV' .
        'kIjpmYWxzZX0.Gqe7jSu5f61-JujzfCGrr-mp8ZDjUaZit432pKLL-zJ8tbaBkVEpHK' .
        'SIiQA1GoA7ettx6T3w2ETze0ECIeOaUTUWkwZS7bft53Wty8eGr8erIHVdKp4roh5jT' .
        '2ksMZywwrQSKRYkgME1I75CQRhG9LPHl0JdI1amqUYBFGgnIIFZ0nGcJ-j5DNXteQT4' .
        'Yt1FC-Gedub0LUoD51ZclPAb3zT-r5oA6d-uBIw6dgD5U8liHuZ1xXEqkZ12bhVYF6c' .
        'RC8hlIpeuxjajrOUmtT9sMpJSUIAU8NFgFrE1SkxZ2ss6Q8zn-lKiu8EFBw03ZzF53f' .
        '9OrwwvDrJTUZuGyu2Ssg';
  }
}
