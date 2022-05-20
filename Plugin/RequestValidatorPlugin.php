<?php

namespace BlueMedia\BluePayment\Plugin;

use Magento\Framework\Webapi\Exception as WebapiException;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Webapi\Controller\Rest\RequestValidator;
use Magento\Webapi\Controller\Rest\Router;

class RequestValidatorPlugin
{
    /** @var Router  */
    private $router;

    /** @var Request */
    private $request;

    public function __construct(
        Router $router,
        Request $request
    ) {
       $this->router = $router;
       $this->request = $request;
    }

    /**
     * @param  RequestValidator  $subject
     * @param  null  $result
     *
     * @return void
     * @throws WebapiException
     */
    public function afterValidate(RequestValidator $subject, $result)
    {
        if (str_starts_with($this->request->getUri()->getPath(), 'V1/autopay')) {
            $params = $this->request->getBodyParams();

            if (!isset($params['hash']) || $params['hash'] !== 'test') {
                throw new WebapiException(__('Token validation failed'));
            }



        }
    }
}
