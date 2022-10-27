<?php

namespace BlueMedia\BluePayment\Controller\Multishipping;

use Exception;
use Magento\Checkout\Api\AgreementsValidatorInterface;
use Magento\Checkout\Helper\Data;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\PaymentException;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Multishipping\Controller\Checkout;
use Magento\Multishipping\Model\Checkout\Type\Multishipping\State;
use Psr\Log\LoggerInterface;

/**
 * Class OverviewPost
 */
class OverviewPost extends Checkout implements HttpPostActionInterface
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var AgreementsValidatorInterface
     */
    protected $agreementsValidator;

    /**
     * @var SessionManagerInterface
     */
    private $session;

    /**
     * @var \Magento\Checkout\Api\PaymentProcessingRateLimiterInterface
     */
    private $paymentRateLimiter;

    /**
     * @param Context $context
     * @param Session $customerSession
     * @param CustomerRepositoryInterface $customerRepository
     * @param AccountManagementInterface $accountManagement
     * @param LoggerInterface $logger
     * @param AgreementsValidatorInterface $agreementValidator
     * @param SessionManagerInterface $session
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        CustomerRepositoryInterface $customerRepository,
        AccountManagementInterface $accountManagement,
        LoggerInterface $logger,
        AgreementsValidatorInterface $agreementValidator,
        SessionManagerInterface $session
    ) {
        $this->logger = $logger;
        $this->agreementsValidator = $agreementValidator;
        $this->session = $session;

        if (interface_exists(\Magento\Checkout\Api\PaymentProcessingRateLimiterInterface::class)) {
            // Support for Magento 2.3.*

            $this->paymentRateLimiter = ObjectManager::getInstance()
                ->get(\Magento\Checkout\Api\PaymentProcessingRateLimiterInterface::class);
        }

        parent::__construct(
            $context,
            $customerSession,
            $customerRepository,
            $accountManagement
        );
    }

    /**
     * Overview action
     *
     * @return void
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function execute()
    {
        try {
            if ($this->paymentRateLimiter) {
                $this->paymentRateLimiter->limit();
            }

            if (!$this->_validateMinimumAmount()) {
                return;
            }

            if (!$this->agreementsValidator->isValid(array_keys($this->getRequest()->getPost('agreement', [])))) {
                $this->messageManager->addErrorMessage(
                    __('Please agree to all Terms and Conditions before placing the order.')
                );
                $this->_redirect('*/*/billing');
                return;
            }

            $this->_getCheckout()->createOrders();
            $this->_getState()->setCompleteStep(State::STEP_OVERVIEW);

            if ($this->session->getAuthorizationRedirect()) {
                $this->_getState()->setActiveStep(State::STEP_OVERVIEW);
                $this->_redirect($this->session->getAuthorizationRedirect());
            } elseif ($this->session->getAddressErrors()) {
                $this->_getState()->setActiveStep(State::STEP_RESULTS);
                $this->_redirect('*/*/results');
            } else {
                $this->_getState()->setActiveStep(State::STEP_SUCCESS);
                $this->_getCheckout()->getCheckoutSession()->clearQuote();
                $this->_getCheckout()->getCheckoutSession()->setDisplaySuccess(true);
                $this->_redirect('*/*/success');
            }
        } catch (PaymentException $e) {
            $message = $e->getMessage();
            if (!empty($message)) {
                $this->messageManager->addError($message);
            }
            $this->_redirect('*/*/billing');
        } catch (\Magento\Checkout\Exception $e) {
            $this->_objectManager->get(
                Data::class
            )->sendPaymentFailedEmail(
                $this->_getCheckout()->getQuote(),
                $e->getMessage(),
                'multi-shipping'
            );
            $this->_getCheckout()->getCheckoutSession()->clearQuote();
            $this->messageManager->addError($e->getMessage());
            $this->_redirect('*/cart');
        } catch (LocalizedException $e) {
            $this->_objectManager->get(
                Data::class
            )->sendPaymentFailedEmail(
                $this->_getCheckout()->getQuote(),
                $e->getMessage(),
                'multi-shipping'
            );
            $this->messageManager->addError($e->getMessage());
            $this->_redirect('*/*/billing');
        } catch (Exception $e) {
            $this->logger->critical($e);
            try {
                $this->_objectManager->get(
                    Data::class
                )->sendPaymentFailedEmail(
                    $this->_getCheckout()->getQuote(),
                    $e->getMessage(),
                    'multi-shipping'
                );
            } catch (Exception $e) {
                $this->logger->error($e->getMessage());
            }
            $this->messageManager->addErrorMessage(__('Order place error'));
            $this->_redirect('*/*/billing');
        }
    }
}
