<?php

namespace BlueMedia\BluePayment\Logger;

use Magento\Framework\Filesystem\DriverInterface;
use Magento\Framework\Logger\Handler\Base;

class Handler extends Base
{
    const LOG_FILE_NAME_PREFIX = 'BlueMedia';
    const LOG_MAIN_DIR         = '/var/log/BlueMedia/';
    const LOG_FILE_DATE_FORMAT = 'Y-m-d';

    /**
     * Logging level
     *
     * @var int
     */
    public $loggerType = 200;

    /**
     * @var string
     */
    public $fileName = '';

    /**
     * Handler constructor.
     *
     * @param DriverInterface $filesystem
     */
    public function __construct(DriverInterface $filesystem)
    {
        $this->setLogFileName();

        parent::__construct($filesystem);
    }

    /**
     * @return void
     */
    public function setLogFileName()
    {
        $this->fileName = self::LOG_MAIN_DIR.'/'.self::LOG_FILE_NAME_PREFIX.'_'.$this->getFileSuffixAsDate().'.log';
    }

    /**
     * @return false|string
     */
    public function getFileSuffixAsDate()
    {
        return date(self::LOG_FILE_DATE_FORMAT);
    }
}
