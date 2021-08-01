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
    const RESPONSE_TIMEZONE = 'Europe/Warsaw';

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
     * @param string $endPoint
     * @param array $data
     *
     * @return SimpleXMLElement|false
     * @throws ResponseException
     */
    public function call($endPoint, $data)
    {
        $this->curl->post($endPoint, $data);
        $response = $this->curl->getBody();

        if ($response == 'ERROR') {
            throw new ResponseException();
        }

        return simplexml_load_string($response);
    }

    /**
     * @param string $uri
     * @param array $params
     *
     * @return mixed
     * @throws ResponseException
     */
    public function callJson($uri, $params)
    {
        $this->curl->addHeader('BmHeader', 'pay-bm');
        $this->curl->addHeader('Content-Type', 'application/json');
        $this->curl->post($uri, json_encode($params));
        $response = $this->curl->getBody();

        if ($response == 'ERROR') {
            throw new ResponseException();
        }

        return json_decode($response);
    }
}
