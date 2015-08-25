## Technical feature

[system.xml](etc/adminhtml/system.xml) makes our module configurable in the admin panel.
[Configuration](etc/config.xml) 'registers' [Payinstore model](Model/Payinstore.php) as a payment method.
[Payinstore class](Model/Payinstore.php) extends AbstractMethod. This class is used to set new order state to Pending Payment.
Virtual class Magento\SamplePaymentProvider\Block\Form\Payinstore declared in [di.xml](etc/di.xml) along [template](view/frontend/templates/form/payinstore.phtml) used to display Payment Instructions.

## Installation

This module is intended to be installed using composer.  After including this component and enabling it, you can verify it is installed by going the backend at:

STORES -> Configuration -> ADVANCED/Advanced ->  Disable Modules Output

Once there check that the module name shows up in the list to confirm that it was installed correctly.

TODO: add webapi.xml
TODO: add persistence layer
TODO: review config file