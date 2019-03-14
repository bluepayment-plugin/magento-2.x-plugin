<?php
/**
 * @package     BlueMedia_BluePayment
 * @copyright   Copyright ⓒ 2017 BOLD BRAND COMMERCE SP. Z O.O. (http://bold.net.pl/)
 * @author      Piotr Koziol <piotr.koziol@bold.net.pl>
 */

namespace BlueMedia\BluePayment\Helper;

use Magento\Backend\Model\Auth\Session;
use Magento\Framework\App\Area;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Email
 *
 * @package BlueMedia\BluePayment\Helper
 */
class Email extends AbstractHelper
{
    const XML_PATH_DISABLED_GATEWAYS_NOTIFICATION_ACTIVE_FIELD       = 'payment/bluepayment/disabled_gateways_notification_active';
    const XML_PATH_DISABLED_GATEWAYS_NOTIFICATION_SENDER_NAME_FIELD  = 'payment/bluepayment/disabled_gateways_notification_sender_name';
    const XML_PATH_DISABLED_GATEWAYS_NOTIFICATION_SENDER_EMAIL_FIELD = 'trans_email/ident_general/email';
    const XML_PATH_DISABLED_GATEWAYS_NOTIFICATION_RECEIVERS_FIELD    = 'payment/bluepayment/disabled_gateways_notification_receivers';
    const XML_PATH_DISABLED_GATEWAYS_NOTIFICATION_TEMPLATE_FIELD     = 'payment/bluepayment/disabled_gateways_notification_template';

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var StateInterface
     */
    protected $inlineTranslation;

    /**
     * @var TransportBuilder
     */
    protected $_transportBuilder;

    /**
     * @var Session
     */
    protected $_authSession;

    /**
     * Logger
     *
     * @var \Zend\Log\Logger
     */
    protected $_logger;

    /**
     * Email constructor.
     *
     * @param Context               $context
     * @param StoreManagerInterface $storeManager
     * @param StateInterface        $inlineTranslation
     * @param TransportBuilder      $transportBuilder
     * @param Session               $authSession
     */
    public function __construct(
        Context               $context,
        StoreManagerInterface $storeManager,
        StateInterface        $inlineTranslation,
        TransportBuilder      $transportBuilder,
        Session               $authSession
    ) {
        parent::__construct($context);
        $this->_storeManager     = $storeManager;
        $this->inlineTranslation = $inlineTranslation;
        $this->_transportBuilder = $transportBuilder;
        $this->_authSession      = $authSession;

        $writer        = new \Zend\Log\Writer\Stream(BP . '/var/log/bluemedia_notificator.log');
        $this->_logger = new \Zend\Log\Logger();
        $this->_logger->addWriter($writer);
    }

    /**
     * @param string $path
     * @param int    $storeId
     *
     * @return mixed
     */
    protected function getConfigValue($path, $storeId)
    {
        return $this->scopeConfig->getValue(
            $path,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get Store
     *
     * @return \Magento\Store\Api\Data\StoreInterface
     */
    public function getStore()
    {
        return $this->_storeManager->getStore();
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
        $senderInfo   = [
            'name'  => $this->getSenderName(),
            'email' => $this->getSenderEmail(),
        ];
        $templateId   = $this->getTemplateId();
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
        if (!is_null($currentUser)) {
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
                    'source'   => $source,
                ],
                $senderInfo,
                $receiverInfo
            );

            $transport = $this->_transportBuilder->getTransport();
            $transport->sendMessage();
            $this->inlineTranslation->resume();

            return true;
        } catch (\Exception $e) {
            $this->_logger->info('Error has occurred during sending an email. Message: ' . $e->getMessage());
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isSendingEnabled()
    {
        return $this->getConfigValue(
            self::XML_PATH_DISABLED_GATEWAYS_NOTIFICATION_ACTIVE_FIELD,
            $this->getStore()->getStoreId()
        );
    }

    /**
     * Returns email receiver array
     *
     * @return array
     */
    public function geEmailReceivers()
    {
        $result       = [];
        $unserialized = unserialize(
            $this->getConfigValue(
                self::XML_PATH_DISABLED_GATEWAYS_NOTIFICATION_RECEIVERS_FIELD,
                $this->getStore()->getStoreId()
            )
        );
        if ($unserialized) {
            foreach ($unserialized as $row) {
                $result[] = [
                    'name'  => $row['name'],
                    'email' => $row['email'],
                ];
            }
        }

        return $result;
    }

    /**
     * Get configured sender name
     *
     * @return string
     */
    public function getSenderName()
    {
        return $this->getConfigValue(
            self::XML_PATH_DISABLED_GATEWAYS_NOTIFICATION_SENDER_NAME_FIELD,
            $this->getStore()->getStoreId()
        );
    }

    /**
     * Get configured sender email
     *
     * @return string
     */
    public function getSenderEmail()
    {
        return $this->getConfigValue(
            self::XML_PATH_DISABLED_GATEWAYS_NOTIFICATION_SENDER_EMAIL_FIELD,
            $this->getStore()->getStoreId()
        );
    }

    /**
     * Return template id according to store
     *
     * @return int
     */
    public function getTemplateId()
    {
        return $this->getConfigValue(self::XML_PATH_DISABLED_GATEWAYS_NOTIFICATION_TEMPLATE_FIELD, $this->getStore()->getStoreId());
    }

    /**
     * @param int          $templateId
     * @param array        $emailTemplateVariables
     * @param array|string $senderInfo
     * @param array|string $receiverInfo
     *
     * @return void
     */
    public function generateTemplate($templateId, $emailTemplateVariables, $senderInfo, $receiverInfo)
    {
        $this->_transportBuilder->setTemplateIdentifier($templateId)
            ->setTemplateOptions(
                [
                    'area'  => Area::AREA_FRONTEND,
                    'store' => $this->_storeManager->getStore()->getId(),
                ]
            )
            ->setTemplateVars($emailTemplateVariables)
            ->setFrom($senderInfo);
        foreach ($receiverInfo as $receiver) {
            if (isset($receiver['email']) && isset($receiver['name'])) {
                $this->_transportBuilder->addTo($receiver['email'], $receiver['name']);
            }
        }
        $this->_transportBuilder->getMessageText();
    }

    /**
     * @return \Magento\User\Model\User|null
     */
    public function getCurrentUser()
    {
        return $this->_authSession->getUser();
    }
}
