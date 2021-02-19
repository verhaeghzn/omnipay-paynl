<p align="center">
    <img src="https://www.pay.nl/uploads/1/brands/main_logo.png" />
</p>
<h1 align="center">PAY. Omnipay driver</h1>

# Description

PAY. driver for the Omnipay payment processing library

- [Description](#description)
- [Available payment methods](#available-payment-methods)
- [Requirements](#requirements)
- [Installation](#installation)
- [Update instructions](#update-instructions)
- [Usage](#usage)
- [Support](#support)


# Available payment methods

Bank Payments  | Creditcards | Gift cards & Vouchers | Pay by invoice | Others | 
:-----------: | :-----------: | :-----------: | :-----------: | :-----------: |
iDEAL + QR |Visa | VVV Cadeaukaart | AfterPay | PayPal |
Bancontact + QR |  Mastercard | Webshop Giftcard | Achteraf betalen via Billink | WeChatPay | 
Giropay |American Express | FashionCheque | Focum AchterafBetalen.nl | AmazonPay |
MyBank | Carte Bancaire | Podium Cadeaukaart | Capayable Achteraf Betalen | Cashly | 
SOFORT | PostePay | Gezondheidsbon | in3 keer betalen, 0% rente | Pay Fixed Price (phone) |
Maestro | Dankort | Fashion Giftcard | Klarna | Instore Payments (POS) |
Bank Transfer | Cartasi | GivaCard | SprayPay | Przelewy24 | 
| Tikkie | De Cadeaukaart | YourGift | Creditclick | Apple Pay | 
| Multibanco | | Paysafecard | | Payconiq
| | | Huis en Tuin Cadeau 


# Requirements

    PHP 5.6 or higher


# Installation
#### Installing

In command line, navigate to the installation directory of Omnipay

Enter the following command:

```
composer require league/omnipay:^3 paynl/omnipay-paynl
```

The plugin is now installed


##### Setup

1. Create a new php file
2. Use the following code:
```
# require autoloader
require_once('vendor/autoload.php');
 
use Omnipay\Omnipay;
 
# Setup payment gateway
$gateway = Omnipay::create('Paynl');
 
$gateway->setApiToken('abcdefgdjwaiodjwaodjaowidwad');
$gateway->setTokenCode('AT-0000-0000');
$gateway->setServiceId('SL-0000-0000');
```
3. Enter the TokenCode, API token and serviceID (these can be found in the PAY. Admin Panel --> https://admin.pay.nl/programs/programs
4. Save the file
5. Require the file where you wish to use the plugin.

Go to the *Manage* / *Services* tab in the PAY. Admin Panel to enable extra payment methods. 
  

#### Update instructions

In command line, navigate to the installation directory of Omnipay

Enter the following command:

```
composer update league/omnipay:^3 paynl/omnipay-paynl
```

The plugin has now been updated


# Usage


PAY. items
```
# Use PAY. Item class
use Omnipay\Paynl\Common\Item;

# Add items to transaction
$arrItems = array();
$item = new Item();
$item->setProductId('SKU01')
        ->setProductType('ARTICLE')
        ->setVatPercentage(21)
        ->setDescription('Description')
        ->setName('PAY. article')
        ->setPrice('10')
        ->setQuantity(4);
$arrItems[] = $item;

$item = new Item();
$item->setProductId('SHIP01')
        ->setProductType('SHIPPING')
        ->setVatPercentage(21)
        ->setDescription('Description')
        ->setName('PAY. shipping')
        ->setPrice('5')
        ->setQuantity(1);
$arrItems[] = $item;

$item = new Item();
$item->setProductId('SKU02')
        ->setProductType('DISCOUNT')
        ->setVatPercentage(21)
        ->setDescription('Description')
        ->setName('PAY. promotion')
        ->setPrice('1')
        ->setQuantity(1);
$arrItems[] = $item;
```

Start a transaction

```
# Send purchase request
$response = $gateway->purchase(
    [
        'amount' => '46.00',
        'currency' => 'USD',
        'transactionReference' => 'referenceID1',
        'clientIp' => '192.168.192.12',
        'returnUrl' => 'http://dev.local:8080/pay.php',
        'items' => $arrItems,
        'card' => array(
            'firstName' => 'Example',
            'lastName' => 'User',
            'gender' => 'M',
            'birthday' => '01-02-1992',
            'phone' => '1111111111111111',
            'email' => 'john@example.com',
            'country' => 'NL',

            'shippingAddress1' => 'Shippingstreet 1B',
            'shippingAddress2' => '',
            'shippingCity' => 'Shipingtown',
            'shippingPostcode' => '1234AB',
            'shippingState' => '',
            'country' => 'NL',

            'billingFirstName' => 'Billingexample',
            'billingLastName' => 'Billinguser',
            'billingAddress1' => 'Billingstreet 1B',
            'billingAddress2' => '',
            'billingCity' => 'Billingtown',
            'billingPostcode' => '1234AB',
            'billingState' => '',
            'country' => 'NL'                     
        )
    ]
)->send();
 
# Process response
if ($response->isSuccessful()) {
     
    # Payment was successful
    var_dump($response);
 
} elseif ($response->isRedirect()) {
     
    # Redirect to offsite payment gateway
    $response->redirect();
     
} else {
 
    # Payment failed
    echo $response->getMessage();
}
```

Refund a transaction
```
$response = $gateway->refund([
    'transactionReference' => "PAY. transactionId",
    'amount' => '46.00',
    'currency' => 'USD',
    'transactionId' => 765897
])->send();

if ($response->isSuccessful()) {

    # Refund was successful
    print_r($response);

} else {

    # Refund failed
    echo $response->getMessage();
}

```

Capture a transaction
```
$response = $gateway->capture([
    'transactionReference' => "PAY. transactionId",
    'items' => $arrItems
])->send();

if ($response->isSuccessful()) {

    # Capture was successful
    print_r($response);

} else {

    # Capture failed
    echo $response->getMessage();
}
```

# Support
https://www.pay.nl

Contact us: support@pay.nl
