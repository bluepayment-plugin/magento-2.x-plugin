<?php

namespace BlueMedia\BluePayment\Api;

use BlueMedia\BluePayment\Exception\ResponseException;

/**
 * Class Client
 * @package BlueMedia\BluePayment\Model\Api
 */
class Client implements ClientInterface
{
    const RESPONSE_TIMEZONE = 'Europe/Warsaw';

    /**
     * @param $endPoint
     * @param $data
     *
     * @return \SimpleXMLElement
     * @throws \BlueMedia\BluePayment\Exception\ResponseException
     */
    public function call($endPoint, $data)
    {
        $fields = $this->buildFields($data);

        $curl = curl_init($endPoint);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
        $curlResponse = curl_exec($curl);
        curl_close($curl);
        if ($curlResponse == 'ERROR') {
            throw new ResponseException();
        }

        return simplexml_load_string($curlResponse);
    }

    /**
     * @param string|array $data
     *
     * @return string
     */
    protected function buildFields($data): string
    {
        return (is_array($data)) ? http_build_query($data) : (string)$data;
    }

}