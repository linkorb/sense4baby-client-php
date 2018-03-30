<?php

namespace Sense4Baby;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Cookie\CookieJar;
use RuntimeException;

class Client
{
    protected $guzzleClient;
    protected $baseUrl = 'https://sense4baby.nl';

    protected function __construct()
    {

    }

    public static function fromCredentials($username, $password, $deviceId)
    {
        $client = new self();
        $client->username = $username;
        $client->password = $password;
        $client->deviceId = $deviceId;
       
        return $client;
    }

    public function download($outputPath)
    {
        $this->guzzleclient = new GuzzleClient();

        // Auth phase 1
        $data = [
            'deviceid' => $this->deviceId,
            'username' => $this->username,
            'password' => $this->password
        ];
        //print_r($data);
        
        $url = $this->baseUrl . '/service/auth/login';
        $res = $this->guzzleclient->request(
            'POST',
            $url,
            [
                'headers' => [
                    'Accept'     => 'application/json',
                ],
                'json' => $data,
            ]
        );
        $body = $res->getBody();
        $data = json_decode((string)$body, true);
        //print_r($data);

        $sessId = $data['sessid'];
        $sessionName = $data['session_name'];

        // Auth phase 2
        $data = [
            'deviceid' => $this->deviceId,
            'username' => $this->username,
            'password' => $this->password
        ];

        $parsedUrl = parse_url($this->baseUrl);
        $host = $parsedUrl['host'];
        $cookieJar = CookieJar::fromArray(
            [
                $sessionName => $sessId
            ],
            $host
        );
        
        //print_r($data);
        
        $url = $this->baseUrl . '/service/auth/login';
        $res = $this->guzzleclient->request(
            'POST',
            $url,
            [
                'headers' => [
                    'Accept'     => 'application/json',
                ],
                'json' => $data,
                'cookies' => $cookieJar,
            ]
        );
        $body = $res->getBody();
        $data = json_decode((string)$body, true);
        //print_r($data);

        if ($data['sessid'] != $sessId) {
            throw new RuntimeException("Auth failed: sessid doesn't match in auth phase 2");
        }
        if ($data['session_name'] != $sessionName) {
            throw new RuntimeException("Auth failed: session_name doesn't match in auth phase 2");
        }

        echo "AUTH OK" . PHP_EOL;
        
        // Get session data
        $url = $this->baseUrl . '/service/session/getSessionLists';
        $data = [
            'since' => time() - (60 * 60 * 24 * 365)
        ];
        $res = $this->guzzleclient->request(
            'POST',
            $url,
            [
                'headers' => [
                    'Accept'     => 'application/json',
                ],
                'json' => $data,
                'cookies' => $cookieJar,
            ]
        );
        $body = $res->getBody();
        $data = json_decode((string)$body, true);
        //print_r($data);

        $i = 0;
        foreach ($data['QRD'] as $sessionData) {
            $pid = $sessionData['PID'];
            $uri = $sessionData['OBX'];
            $portalStamp = $sessionData['OBR'];
            $performedStamp = $sessionData['OBX_TS'];
            echo "PID: $pid " . date('c', $portalStamp) . ' ' . $uri . PHP_EOL;

            // ==== FETCH PDF
            $url = $this->baseUrl . '/service/session/getSessionPDF';
            $data2 = [
                'uri' => $uri,
                'created' => $portalStamp
            ];
            $res = $this->guzzleclient->request(
                'POST',
                $url,
                [
                    'headers' => [
                        'Accept'     => 'application/json',
                    ],
                    'json' => $data2,
                    'cookies' => $cookieJar,
                ]
            );
            $body = $res->getBody();
            file_put_contents($outputPath . '/' . $i . '.pdf', $body);

            // ==== CSV

            $url = $this->baseUrl . '/service/session/getSessionCSV';
            $data2 = [
                'uri' => $uri,
                'created' => $portalStamp
            ];
            $res = $this->guzzleclient->request(
                'POST',
                $url,
                [
                    'headers' => [
                        'Accept'     => 'application/json',
                    ],
                    'json' => $data2,
                    'cookies' => $cookieJar,
                ]
            );
            $body = $res->getBody();
            file_put_contents($outputPath . '/' . $i . '.csv', $body);

            $i++;
            // $data = json_decode((string)$body, true);


        }
    }
}