<?php

declare(strict_types=1);

namespace BlueMedia\BluePayment\Model;

use BlueMedia\BluePayment\Api\GatewayRepositoryInterface;
use Magento\Sales\Api\Data\OrderInterface;

class CustomFieldResolver
{
    /** @var GatewayRepositoryInterface */
    private $gatewayRepository;

    public function __construct(
        GatewayRepositoryInterface $gatewayRepository
    ) {
        $this->gatewayRepository = $gatewayRepository;
    }

    public function resolve(
        int $gatewayId,
        OrderInterface $order,
        $params = []
    ) {
        $gateway = $this->gatewayRepository->getByGatewayIdAndStoreId(
            (int) $gatewayId,
            (int) $order->getStoreId()
        );

        if ($gateway && $gateway->getRequiredParams()) {
            foreach ($gateway->getRequiredParams() as $requiredParam) {
                if ($requiredParam === 'accountHolderName') {
                    $params['AccountHolderName'] = $order->getCustomerFirstname() . ' ' . $order->getCustomerLastname();
                } elseif ($requiredParam === 'nip') {
                    // Add NIP to params, if exists in address
                    $address = $order->getBillingAddress();

                    $nip = $address->getVatId();
                    if (! $nip) {
                        $nip = $order->getCustomerTaxvat();
                    }

                    if ($nip) {
                        // Only digits, max 10 chars
                        $nip = preg_replace('/[^0-9]/', '', $nip);
                        $nip = substr($nip, 0, 10);

                        if (!empty($nip)) {
                            $params['Nip'] = substr($nip, 0, 10);
                        }
                    }
                }
            }
        }

        return $params;
    }
}
