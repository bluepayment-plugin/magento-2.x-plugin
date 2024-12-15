<?php

namespace BlueMedia\BluePayment\Api;

interface RefundStatusUpdaterInterface
{
    /**
     * Aktualizuje statusy zwrotów
     *
     * @return void
     */
    public function updateRefundStatuses(): void;
}
