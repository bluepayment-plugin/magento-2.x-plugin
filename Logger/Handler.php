<?php

namespace BlueMedia\BluePayment\Logger;

use Magento\Framework\Filesystem\DriverInterface;

/**
 * Class Handler
 *
 * @package BlueMedia\BluePayment\Logger
 */
class Handler extends \Magento\Framework\Logger\Handler\Base
{
    const LOG_FILE_NAME_PREFIX = 'BlueMedia';
    const LOG_MAIN_DIR         = '/var/log';
    const LOG_FILE_DATE_FORMAT = 'Y-m-d';

    /**
     * Logging level
     *
     * @var int
     */
    protected $loggerType = 200;

    /**
     * @var string
     */
    protected $fileName = '';

    /**
     * Handler constructor.
     *
     * @param DriverInterface $filesystem
     */
    public function __construct(DriverInterface $filesystem/*, $filePath*/)
    {
        $this->setLogFileName();

        parent::__construct($filesystem/*, $filePath*/);
    }

    /**
     * @return void
     */
    protected function setLogFileName()
    {
        $this->fileName = self::LOG_MAIN_DIR . '/' . self::LOG_FILE_NAME_PREFIX . '_' . $this->getFileSuffixAsDate() . '.log';
    }

    /**
     * @return false|string
     */
    protected function getFileSuffixAsDate()
    {
        return date(self::LOG_FILE_DATE_FORMAT);
    }
}
