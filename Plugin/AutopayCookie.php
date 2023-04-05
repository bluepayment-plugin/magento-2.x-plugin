<?php

declare(strict_types=1);

namespace BlueMedia\BluePayment\Plugin;

use BlueMedia\BluePayment\Model\Autopay\ShowAutopay;
use Magento\Framework\App\ActionInterface;
use Psr\Log\LoggerInterface;

class AutopayCookie
{
    /** @var ShowAutopay */
    private $showAutopay;

    /** @var LoggerInterface */
    private $log;

    /**
     * AutopayCookie plugin constructor.
     *
     * @param ShowAutopay $showAutopay
     * @param LoggerInterface $log
     */
    public function __construct(
        ShowAutopay $showAutopay,
        LoggerInterface $log
    ) {
        $this->showAutopay = $showAutopay;
    }

    /**
     * Check if Autopay should be shown by request key and set cookie.
     *
     * @param ActionInterface $subject
     * @return array
     */
    public function beforeExecute(ActionInterface $subject): array
    {
        try {
            $this->showAutopay->checkRequest();
        } catch (\Exception $e) {
            $this->log->error($e->getMessage());
        }

        return [];
    }
}
