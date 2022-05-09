<?php

namespace BlueMedia\BluePayment\Api;

use BlueMedia\BluePayment\Exception\ResponseException;
use Magento\Framework\HTTP\Client\Curl;
use SimpleXMLElement;

/**
 * BM API Client
 */
class Client implements ClientInterface
{
    public const RESPONSE_TIMEZONE = 'Europe/Warsaw';

    /**
     * @var Curl
     */
    private $curl;

    /**
     * @param Curl $curl
     */
    public function __construct(Curl $curl)
    {
        $this->curl = $curl;
    }

    /**
     * @param  string  $uri
     * @param  array  $params
     *
     * @return SimpleXMLElement|false
     * @throws ResponseException
     */
    public function call(string $uri, array $params)
    {
        $this->curl->addHeader('BmHeader', 'pay-bm');
        $this->curl->post($uri, $params);
        $response = $this->curl->getBody();

        if ($response === 'ERROR') {
            throw new ResponseException();
        }

        return simplexml_load_string($response);
    }

    /**
     * @param  string  $uri
     * @param  array  $params
     *
     * @return mixed
     * @throws ResponseException
     */
    public function callJson(string $uri, array $params)
    {
        $this->curl->addHeader('BmHeader', 'pay-bm');
        $this->curl->addHeader('Content-Type', 'application/json');
        $this->curl->post($uri, json_encode($params));
        $response = $this->curl->getBody();

        if ($response === 'ERROR') {
            throw new ResponseException();
        }

        return json_decode($response, true);
    }
}
