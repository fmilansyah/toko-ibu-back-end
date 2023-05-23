<?php

use GuzzleHttp\Client;

class MidtransApi
{
    private static $baseUrl = 'https://api.sandbox.midtrans.com';
    private static $serverKey = ''; // Do not commit server key
    private static $client;

    private static function setClient($data = null)
    {
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

    public static function request($method = 'GET', $url = null, $data = [])
    {
        try {
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
