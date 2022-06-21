<?php

namespace BlueMedia\BluePayment\Plugin;

use BlueMedia\BluePayment\Logger\Logger;
use BlueMedia\BluePayment\Model\Autopay\ConfigProvider;
use Magento\Framework\Webapi\Exception as WebapiException;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Webapi\Controller\Rest\RequestValidator;

class RequestValidatorPlugin
{
    public const AVAILABLE_ALGHORITMS = [
        'HmacMD5' => 'md5',
        'HmacSHA1' => 'sha1',
        'HmacSHA256' => 'sha256',
        'HmacSHA512' => 'sha512',
    ];

    /** @var Request */
    private $request;

    /** @var ConfigProvider */
    private $configProvider;

    /** @var Logger */
    private $logger;

    public function __construct(
        Request $request,
        ConfigProvider $configProvider,
        Logger $logger
    ) {
       $this->request = $request;
       $this->configProvider = $configProvider;
       $this->logger = $logger;
    }

    /**
     * @param  RequestValidator  $subject
     * @param  mixed  $result
     *
     * @return mixed $result
     * @throws WebapiException
     */
    public function afterValidate(RequestValidator $subject, $result)
    {
        if (str_contains($this->request->getUri()->getPath(), 'V1/autopay')) {
            $body = $this->request->getContent();
            $secretKey = $this->configProvider->getSecretKey();

            $alghoritm = $this->getAlghoritm();

            $this->logger->info('RequestValidatorPlugin:' . __LINE__, [
                'alghoritm' => $alghoritm,
                'header' => $this->request->getHeader('X-API-SIGNATURE-ALG'),
            ]);

            if (!$alghoritm) {
                throw new WebapiException(__('Token validation failed'));
            }

            $remoteHash = $this->request->getHeader('Bm-Signature');
            $localHash = hash_hmac($alghoritm, $body, $secretKey);

            $this->logger->info('RequestValidatorPlugin:' . __LINE__, [
                'remoteHash' => $remoteHash,
                'localHash' => $localHash,
            ]);

            if (strtolower($localHash) !== strtolower($remoteHash)) {
                throw new WebapiException(__('Token validation failed'));
            }
        }

        return $result;
    }

    private function getAlghoritm() {
        $header = $this->request->getHeader('X-API-SIGNATURE-ALG');

        if (array_key_exists($header, self::AVAILABLE_ALGHORITMS)) {
            return self::AVAILABLE_ALGHORITMS[$header];
        }

        return false;
    }
}
