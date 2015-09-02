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

    protected $orderFactory;

    /**
     * Get payment instructions text from config
     *
     * @return string
     */
    public function getInstructions()
    {
        return trim($this->getConfigData('instructions'));
    }

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Framework\Model\Resource\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []){
        $this->orderFactory = $orderFactory;
        parent::__construct($context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data);
    }


    //@param \Magento\Framework\Object|\Magento\Payment\Model\InfoInterface $payment
    public function getAmount($orderId)//\Magento\Framework\Object $payment)
    {   //\Magento\Sales\Model\OrderFactory
        $orderFactory=$this->orderFactory;
        /** @var \Magento\Sales\Model\Order $order */
        // $order = $payment->getOrder();
        // $order->getIncrementId();
        /* @var $order \Magento\Sales\Model\Order */

            $order = $orderFactory->create()->loadByIncrementId($orderId);
            //$payment= $order->getPayment();

        // return $payment->getAmount();
        return $order->getGrandTotal();
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


    public function generateHash($login,$sum,$pass,$id=null)
    {
        
        $hashData = array(
            "MrchLogin" => $login,
            "OutSum" => $sum,
            
            "InvId" => $id,
            //"OutSumCurrency" => "RUB",
            "pass" => $pass,
        );

        $hash = strtoupper(md5(implode(":", $hashData)));
        return $hash;
    }

    public function getPostData($orderId)
    {   //TODO: add curency
        //OutSumCurrency
        $PostData=[];
        $PostData['OutSum']=round($this->getAmount($orderId), 2);
        $PostData['InvId']=$orderId;
        $PostData['MerchantLogin']=$this->getConfigData('merchant_id');
        $PostData['Description']="Test payment";
        $PostData['SignatureValue']=$this->generateHash($PostData['MerchantLogin'],$PostData['OutSum'],$this->getConfigData('pass_word'),$orderId);
        return $PostData;

    }

}
