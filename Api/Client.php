<?php

namespace BlueMedia\BluePayment\Api;

use BlueMedia\BluePayment\Exception\ResponseException;
use SimpleXMLElement;

/**
 * BM API Client
 */
class Client implements ClientInterface
{
    const RESPONSE_TIMEZONE = 'Europe/Warsaw';

    /**
     * @var \Magento\Framework\HTTP\Client\Curl
     */
    private $curl;

    public function __construct(\Magento\Framework\HTTP\Client\Curl $curl)
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
     * @param string $endPoint
     * @param array $data
     *
     * @return mixed
     * @throws ResponseException
     */
    public function callJson($endPoint, $data)
    {
        $this->curl->addHeader('BmHeader', 'pay-bm');
        $this->curl->post($endPoint, $data);
        $response = $this->curl->getBody();

        if ($response == 'ERROR') {
            throw new ResponseException();
        }

        return json_decode($response);
    }

    /**
     * @param string|array $data
     *
     * @return string
     */
    private function buildFields($data)
    {
        return (is_array($data)) ? http_build_query($data) : (string)$data;
    }
}
