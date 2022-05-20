<?php

namespace BlueMedia\BluePayment\Model\Authorization;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Webapi\Controller\Rest\Router;

/**
 * Guest user context
 */
class AutopayUserContext implements UserContextInterface
{
    /** @var Request */
    private $request;

    public function __construct(
        Request $request
    ) {
        $this->request = $request;
    }

    /**
     * {@inheritdoc}
     */
    public function getUserId()
    {
        if (str_contains($this->request->getUri()->getPath(), 'V1/autopay')) {
            return 0;
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getUserType()
    {
        return UserContextInterface::USER_TYPE_INTEGRATION;
    }
}
