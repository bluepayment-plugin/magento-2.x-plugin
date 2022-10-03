<?php

namespace BlueMedia\BluePayment\Gateway\Request;

use LogicException;
use Magento\Payment\Gateway\Request\BuilderInterface;

class InitializeRequestBuilder extends AbstractRequest implements BuilderInterface
{
    /**
     * Build payment data
     *
     * @param array $buildSubject
     *
     * @return array
     * @throws LogicException
     */
    public function build(array $buildSubject)
    {
        parent::build($buildSubject);

        return [];
    }
}
