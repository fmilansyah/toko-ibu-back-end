<?php

use GuzzleHttp\Client;

class MidtransApi
{
    const TYPE_API = 'core-api';
    const TYPE_SNAP = 'snap';

    private static $baseUrl;
    private static $serverKey = 'SB-Mid-server-B10Vf9nLcjUPI9PTAxP0ndye'; // Do not commit server key
    private static $client;
    private static $type;

    private static function setClient($data = null)
    {
        self::getBaseUrl();
        self::$client = new Client([
            'base_uri' => self::$baseUrl,
            'timeout' => 30,
            'headers' => [
                'Content-Type' => 'application/json',
                'Content-Length' => strlen(json_encode($data, 1)),
                'Accept' => 'application/json',
                'Authorization' => 'Basic '. base64_encode(self::$serverKey) .':',
            ],
        ]);
    }

    private static function getBaseUrl()
    {
        if (self::$type === self::TYPE_API) {
            self::$baseUrl = 'https://api.sandbox.midtrans.com';
        }

        if (self::$type === self::TYPE_SNAP) {
            self::$baseUrl = 'https://app.sandbox.midtrans.com';
        }
    }

    public static function request($type, $method = 'GET', $url = null, $data = [])
    {
        try {
            self::$type = $type;
            self::setClient($data);
            $response = self::$client->request($method, $url, [
                'body' => json_encode($data, 1),
                'verify' => false,
                'return_transfer' => true,
                'timeout' => 0,
            ]);
            $decode = json_decode($response->getBody(), true);

            return [
                'code' => $response->getStatusCode(),
                'body' => $decode,
                'error' => false,
            ];
        } catch (\Exception $e) {
            $response = $e->getResponse();
            $decode = json_decode($response->getBody()->getContents() ?? '');

            return [
                'code' => $response->getStatusCode(),
                'body' => $decode ?? $e->getMessage(),
                'error' => true,
            ];
        }
    }
}
