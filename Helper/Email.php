<?php

namespace BlueMedia\BluePayment\Helper;

use BlueMedia\BluePayment\Logger\Logger;
use Exception;
use Magento\Backend\Model\Auth\Session;
use Magento\Framework\App\Area;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\User\Model\User;

class Email extends AbstractHelper
{
    const XML_PATH_DISABLED_GATEWAYS_NOTIFICATION_ACTIVE_FIELD
        = 'payment/bluepayment/disabled_gateways_notification_active';
    const XML_PATH_DISABLED_GATEWAYS_NOTIFICATION_SENDER_NAME_FIELD
        = 'payment/bluepayment/disabled_gateways_notification_sender_name';
    const XML_PATH_DISABLED_GATEWAYS_NOTIFICATION_SENDER_EMAIL_FIELD
        = 'trans_email/ident_general/email';
    const XML_PATH_DISABLED_GATEWAYS_NOTIFICATION_RECEIVERS_FIELD
        = 'payment/bluepayment/disabled_gateways_notification_receivers';
    const XML_PATH_DISABLED_GATEWAYS_NOTIFICATION_TEMPLATE_FIELD
        = 'payment/bluepayment/disabled_gateways_notification_template';

    /** @var StoreManagerInterface */
    public $storeManager;

    /** @var StateInterface */
    public $inlineTranslation;

    /** @var TransportBuilder */
    public $transportBuilder;

    /** @var Session */
    public $authSession;

    /** @var Logger */
    public $logger;

    /**
     * Email constructor.
     *
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param StateInterface $inlineTranslation
     * @param TransportBuilder $transportBuilder
     * @param Session $authSession
     * @param Logger $logger
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        StateInterface $inlineTranslation,
        TransportBuilder $transportBuilder,
        Session $authSession,
        Logger $logger
    )
    {
        parent::__construct($context);
        $this->storeManager = $storeManager;
        $this->inlineTranslation = $inlineTranslation;
        $this->transportBuilder = $transportBuilder;
        $this->authSession = $authSession;
        $this->logger = $logger;
    }

    /**
     * Sends email notification about disabled gateways
     *
     * @param array $disabledGateways
     *
     * @return bool
     */
    public function sendGatewayDeactivationEmail(array $disabledGateways = [])
    {
        $receiverInfo = $this->geEmailReceivers();
        $senderInfo = [
            'name' => $this->getSenderName(),
            'email' => $this->getSenderEmail(),
        ];
        $templateId = $this->getTemplateId();
        if (empty($senderInfo)
            || empty($receiverInfo)
            || empty($disabledGateways)
            || !$this->isSendingEnabled()
            || !$templateId
        ) {
            return false;
        }

        $disabledGatewaysMsg = "";
        foreach ($disabledGateways as $disabled) {
            if (isset($disabled['gateway_id']) && isset($disabled['gateway_name'])) {
                $disabledGatewaysMsg .= __("Gateway ID: %1 ", $disabled['gateway_id']);
                $disabledGatewaysMsg .= __("Name: %1 ", $disabled['gateway_name']);
                $disabledGatewaysMsg .= "\r\n";
            }
        }

        $currentUser = $this->getCurrentUser();
        if ($currentUser !== null) {
            $source = $currentUser->getFirstName() . ' ' . $currentUser->getLastName();
        } else {
            $source = __('CRON Service');
        }

        try {
            $this->inlineTranslation->suspend();
            $this->generateTemplate(
                $templateId,
                [
                    'gateways' => $disabledGatewaysMsg,
                    'source' => $source,
                ],
                $senderInfo,
                $receiverInfo
            );

            $transport = $this->transportBuilder->getTransport();
            $transport->sendMessage();
            $this->inlineTranslation->resume();

            return true;
        } catch (Exception $e) {
            $this->logger->info('Error has occurred during sending an email. Message: ' . $e->getMessage());
        }

        return false;
    }

    /**
     * Returns email receiver array
     *
     * @return array
     */
    public function geEmailReceivers()
    {
        $result = [];
        $unserialized = $this->unserialize(
            $this->getConfigValue(
                self::XML_PATH_DISABLED_GATEWAYS_NOTIFICATION_RECEIVERS_FIELD,
                $this->getStore()->getStoreId()
            )
        );
        if ($unserialized) {
            foreach ($unserialized as $row) {
                $result[] = [
                    'name' => $row['name'],
                    'email' => $row['email'],
                ];
            }
        }

        return $result;
    }

    /**
     * Backward compatibility for unserializer
     *
     * @param string $data
     * @return mixed
     */
    private function unserialize($data)
    {
        // For Magento 2.2+
        $objectManager = ObjectManager::getInstance();
        $serializer = $objectManager->create(SerializerInterface::class);
        return $serializer->unserialize($data);
    }

    /**
     * @param string $path
     * @param int $storeId
     *
     * @return mixed
     */
    protected function getConfigValue($path)
    {
        return $this->scopeConfig->getValue(
            $path,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get Store
     *
     * @return Store
     */
    public function getStore()
    {
        /** @var Store $store */
        $store = $this->storeManager->getStore();

        return $store;
    }

    /**
     * Get configured sender name
     *
     * @return string
     */
    public function getSenderName()
    {
        return $this->getConfigValue(self::XML_PATH_DISABLED_GATEWAYS_NOTIFICATION_SENDER_NAME_FIELD);
    }

    /**
     * Get configured sender email
     *
     * @return string
     */
    public function getSenderEmail()
    {
        return $this->getConfigValue(self::XML_PATH_DISABLED_GATEWAYS_NOTIFICATION_SENDER_EMAIL_FIELD);
    }

    /**
     * Return template id according to store
     *
     * @return int
     */
    public function getTemplateId()
    {
        return $this->getConfigValue(self::XML_PATH_DISABLED_GATEWAYS_NOTIFICATION_TEMPLATE_FIELD);
    }

    /**
     * @return bool
     */
    public function isSendingEnabled()
    {
        return $this->getConfigValue(self::XML_PATH_DISABLED_GATEWAYS_NOTIFICATION_ACTIVE_FIELD);
    }

    /**
     * @return User|null
     */
    public function getCurrentUser()
    {
        return $this->authSession->getUser();
    }

    /**
     * @param int $templateId
     * @param array $emailTemplateVariables
     * @param array|string $senderInfo
     * @param array $receiverInfo
     *
     * @return void
     */
    public function generateTemplate($templateId, $emailTemplateVariables, $senderInfo, $receiverInfo)
    {
        $this->transportBuilder->setTemplateIdentifier((string)$templateId)
            ->setTemplateOptions(
                [
                    'area' => Area::AREA_FRONTEND,
                    'store' => $this->storeManager->getStore()->getId(),
                ]
            )
            ->setTemplateVars($emailTemplateVariables)
            ->setFrom($senderInfo);

        foreach ($receiverInfo as $receiver) {
            if (isset($receiver['email']) && isset($receiver['name'])) {
                $this->transportBuilder->addTo($receiver['email'], $receiver['name']);
            }
        }
    }
}
