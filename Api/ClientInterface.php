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
     * @param  string  $uri
     * @param  array  $params
     *
     * @return SimpleXMLElement|false
     * @throws ResponseException
     */
    public function call(string $uri, array $params);
}
