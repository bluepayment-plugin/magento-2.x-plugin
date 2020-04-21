<?php

namespace BlueMedia\BluePayment\Api;

use BlueMedia\BluePayment\Exception\ResponseException;
use SimpleXMLElement;

/**
 * Interface ClientInterface
 */
interface ClientInterface
{
    /**
     * @param string $endPoint
     * @param array $data
     *
     * @return SimpleXMLElement
     * @throws ResponseException
     */
    public function call($endPoint, $data);
}
