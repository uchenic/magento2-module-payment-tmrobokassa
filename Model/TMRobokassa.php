<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\TMRobokassa\Model;

use Magento\Quote\Api\Data\CartInterface;
use Magento\Payment\Model\Method\AbstractMethod;


/**
 * Pay In Store payment method model
 */
class TMRobokassa extends AbstractMethod
{
    /**
     * @var bool
     */
    protected $_isInitializeNeeded = true;

    /**
     * Payment code
     *
     * @var string
     */
    protected $_code = 'tmrobokassa';

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_isOffline = true;

    /**
     * Payment additional info block
     *
     * @var string
     */
    protected $_formBlockType = 'Magento\TMRobokassa\Block\Form\TMRobokassa';

    /**
     * Sidebar payment info block
     *
     * @var string
     */
    protected $_infoBlockType = 'Magento\Payment\Block\Info\Instructions';

    protected $_gateUrl = "https://merchant.roboxchange.com/Index.aspx";
    
    protected $_testUrl = "http://test.robokassa.ru/Index.aspx";

    protected $_test;

    /**
     * Get payment instructions text from config
     *
     * @return string
     */
    public function getInstructions()
    {
        return trim($this->getConfigData('instructions'));
    }

    //@param \Magento\Framework\Object|\Magento\Payment\Model\InfoInterface $payment
    public function getAmount()//\Magento\Framework\Object $payment)
    {   //\Magento\Sales\Model\OrderFactory
        $orderFactory=new \Magento\Sales\Model\OrderFactory(); //$this->orderFactory;
        /** @var \Magento\Sales\Model\Order $order */
        // $order = $payment->getOrder();
        // $order->getIncrementId();
        /* @var $order \Magento\Sales\Model\Order */

            $order = $orderFactory->create()->loadByIncrementId($orderFactory->create()->getIncrementId()-1);
            $payment= $order->getPayment();

        return $payment->getAmount();
    }

    /**
     * Set order state and status
     *
     * @param string $paymentAction
     * @param \Magento\Framework\Object $stateObject
     * @return void
     */
    public function initialize($paymentAction, $stateObject)
    {
        $state = $this->getConfigData('order_status');
        $this->_gateUrl=$this->getConfigData('cgi_url');
        $this->_testUrl=$this->getConfigData('cgi_url_test_mode');
        $this->_test=$this->getConfigData('test');
        $stateObject->setState($state);
        $stateObject->setStatus($state);
        $stateObject->setIsNotified(false);
    }

    /**
     * Check whether payment method can be used
     *
     * @param CartInterface|null $quote
     * @return bool
     */
    public function isAvailable($quote = null)
    {
        if ($quote === null) {
            return false;
        }
        return parent::isAvailable($quote) && $this->isCarrierAllowed(
            $quote->getShippingAddress()->getShippingMethod()
        );
    }

    public function getGateUrl(){
        if($this->_test){
            return $this->_testUrl;
        }else{
            return $this->_gateUrl;
        }
    }

    /**
     * Check whether payment method can be used with selected shipping method
     *
     * @param string $shippingMethod
     * @return bool
     */
    protected function isCarrierAllowed($shippingMethod)
    {
        return strpos($this->getConfigData('allowed_carrier'), $shippingMethod) !== false;
    }


    public function generateHash(\Magento\Framework\Object $order)
    {
        $outSum = $this->getAmount($order);
        $hashData = array(
            "MrchLogin" => $this->getConfigData('merchant_id'),
            "OutSum" => round($outSum, 2),
            //"InvId" => $order->getId(),
            "InvId" => $order->getIncrementId(),
            "pass" => $this->getConfigData('pass_word'),
        );

        $hash = strtoupper(md5(implode(":", $hashData)));
        return $hash;
    }

}
