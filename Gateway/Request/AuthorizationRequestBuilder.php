<?php

namespace BlueMedia\Autopay\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;

/**
 * Class AuthorizationRequest
 * @package PayU\PaymentGateway\Gateway\Request
 */
class AuthorizationRequestBuilder extends AbstractRequest implements BuilderInterface
{
    /**
     * {@inheritdoc}
     */
    public function build(array $buildSubject)
    {
        parent::build($buildSubject);

        return [];
    }
}
