<?php

namespace BlueMedia\BluePayment\Api;

/**
 * Interface ClientInterface
 * @package BlueMedia\BluePayment\Model\Api
 */
interface ClientInterface
{
    /**
     * @param $endPoint
     * @param $data
     *
     * @return \SimpleXMLElement
     * @throws \BlueMedia\BluePayment\Exception\ResponseException
     */
    public function call($endPoint, $data);
}