<?php

use GuzzleHttp\Client;

class BiteshipApi
{
    const ORIGIN_AREA_ID = 'IDNP9IDNC74IDND6754IDZ16164';

    private static $baseUrl = 'https://api.biteship.com';
    private static $serverKey = 'biteship_live.eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJuYW1lIjoiVG9rb0lidSIsInVzZXJJZCI6IjY0YjBiZDhmYWFhOThjNzJlMzQ4NGU0ZSIsImlhdCI6MTY4OTMwODg1NH0.iG-jSPXmg5Ke6EGHQA72NhoSJxCOeyFCC9xCuxzxjwQ'; // Do not commit server key
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
                'Authorization' => self::$serverKey,
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
