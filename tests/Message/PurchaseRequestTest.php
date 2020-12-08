<?php

namespace Omnipay\Paynl\Test\Message;


use Omnipay\Common\CreditCard;
use Omnipay\Common\Message\RedirectResponseInterface;
use Omnipay\Paynl\Message\Request\PurchaseRequest;
use Omnipay\Paynl\Message\Response\PurchaseResponse;
use Omnipay\Tests\TestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;

class PurchaseRequestTest extends TestCase
{
    /**
     * @var PurchaseRequest
     */
    protected $request;

    public function testSendSuccess()
    {
        $this->setMockHttpResponse('PurchaseSuccess.txt');

        $this->request->setAmount(1);
        $this->request->setClientIp('10.0.0.5');
        $this->request->setReturnUrl('https://www.pay.nl');

        $response = $this->request->send();
        $this->assertInstanceOf(PurchaseResponse::class, $response);

        $this->assertFalse($response->isSuccessful());
        $this->assertTrue($response->isRedirect());

        $this->assertInternalType('string', $response->getTransactionReference());
        $this->assertInternalType('string', $response->getRedirectUrl());
        $this->assertInternalType('string', $response->getAcceptCode());

        $this->assertEquals('GET', $response->getRedirectMethod());
        $this->assertNull($response->getRedirectData());
        $this->assertInstanceOf(RedirectResponseInterface::class, $response);
        $this->assertInstanceOf(RedirectResponse::class, $response->getRedirectResponse());
    }

    public function testCardEnduser()
    {

        $this->request->setAmount(1);
        $this->request->setClientIp('10.0.0.5');
        $this->request->setReturnUrl('https://www.pay.nl');

        $card = $this->getValidCard();
        $objCard = new CreditCard($card);
        $this->request->setCard($objCard);

        $data = $this->request->getData();

        $this->assertNotEmpty($data['enduser']);
        $enduser = $data['enduser'];

        $this->assertEquals($objCard->getFirstName(), $enduser['initials']);
        $this->assertEquals($objCard->getLastName(), $enduser['lastName']);
        $this->assertEquals($objCard->getBirthday('Y-m-d'), $enduser['dob']);
        $this->assertEquals($objCard->getPhone(), $enduser['phoneNumber']);
        $this->assertEquals($objCard->getEmail(), $enduser['emailAddress']);
    }

    public function testCardAddress()
    {
        $this->request->setAmount(1);
        $this->request->setClientIp('10.0.0.5');
        $this->request->setReturnUrl('https://www.pay.nl');

        $card = $this->getValidCard();
        $objCard = new CreditCard($card);
        $this->request->setCard($objCard);

        $data = $this->request->getData();

        $this->assertNotEmpty($data['enduser']);
        $enduser = $data['enduser'];
        $this->assertNotEmpty($enduser['address']);
        $address = $enduser['address'];
        $this->assertNotEmpty($enduser['invoiceAddress']);

        $strAddress = $objCard->getShippingAddress1() . ' ' . $objCard->getShippingAddress2();
        $arrAddressParts = $this->request->getAddressParts($strAddress);

        if (isset($arrAddressParts[0])) $this->assertEquals($arrAddressParts[0], $address['streetName']);
        if (isset($arrAddressParts[1])) $this->assertEquals($arrAddressParts[1], $address['streetNumber']);
        if (isset($arrAddressParts[2])) $this->assertEquals($arrAddressParts[2], $address['streetNumberExtension']);

        $this->assertEquals($objCard->getShippingPostcode(), $address['zipCode']);
        $this->assertEquals($objCard->getShippingCity(), $address['city']);
        $this->assertEquals($objCard->getShippingCountry(), $address['countryCode']);
        $this->assertEquals($objCard->getShippingState(), $address['regionCode']);

    }

    public function testStatsData()
    {
        $this->request->setAmount(1);
        $this->request->setClientIp('10.0.0.5');
        $this->request->setReturnUrl('https://www.pay.nl');

        $statsData = [
            'promotorId' => uniqid(),
            'info' => uniqid(),
            'tool' => uniqid(),
            'extra1' => uniqid(),
            'extra2' => uniqid(),
            'extra3' => uniqid()
        ];

        $this->request->setStatsData($statsData);

        $data = $this->request->getData();

        $this->assertArrayHasKey('statsData', $data);
        $this->assertEquals($statsData['promotorId'], $data['statsData']['promotorId']);
        $this->assertEquals($statsData['info'], $data['statsData']['info']);
        $this->assertEquals($statsData['tool'], $data['statsData']['tool']);
        $this->assertEquals($statsData['extra1'], $data['statsData']['extra1']);
        $this->assertEquals($statsData['extra2'], $data['statsData']['extra2']);
        $this->assertEquals($statsData['extra3'], $data['statsData']['extra3']);
    }

    public function testDates()
    {
        $this->request->setAmount(1);
        $this->request->setClientIp('10.0.0.5');
        $this->request->setReturnUrl('https://www.pay.nl');

        $invoiceDate = new \DateTime('now');
        $deliveryDate = new \DateTime('tomorrow');
        $expireDate = new \DateTime('now + 4 hours');

        $invoiceDate = $invoiceDate->format('d-m-Y');
        $deliveryDate = $deliveryDate->format('d-m-Y');
        $expireDate = $expireDate->format('d-m-Y H:i:s');

        $this->request->setInvoiceDate($invoiceDate);
        $this->request->setDeliveryDate($deliveryDate);
        $this->request->setExpireDate($expireDate);

        $data = $this->request->getData();

        $this->assertArrayHasKey('saleData', $data);
        $this->assertArrayHasKey('invoiceDate', $data['saleData']);
        $this->assertArrayHasKey('deliveryDate', $data['saleData']);
        $this->assertArrayHasKey('expireDate', $data['transaction']);
        $this->assertEquals($invoiceDate, $data['saleData']['invoiceDate']);
        $this->assertEquals($deliveryDate, $data['saleData']['deliveryDate']);
        $this->assertEquals($expireDate, $data['transaction']['expireDate']);
    }

    public function testCustomerData()
    {
        $this->request->setAmount(1);
        $this->request->setClientIp('10.0.0.5');
        $this->request->setReturnUrl('https://www.pay.nl');

        $customerReference = uniqid();
        $customerTrust = rand(-10, 10);

        $this->request->setCustomerReference($customerReference);
        $this->request->setCustomerTrust($customerTrust);

        $data = $this->request->getData();

        $this->assertArrayHasKey('enduser', $data);
        $this->assertArrayHasKey('customerReference', $data['enduser']);
        $this->assertArrayHasKey('customerTrust', $data['enduser']);
        $this->assertEquals($customerReference, $data['enduser']['customerReference']);
        $this->assertEquals($customerTrust, $data['enduser']['customerTrust']);
    }


    public function testCardInvoiceAddress()
    {
        $this->request->setAmount(1);
        $this->request->setClientIp('10.0.0.5');
        $this->request->setReturnUrl('https://www.pay.nl');

        $card = $this->getValidCard();
        $objCard = new CreditCard($card);
        $this->request->setCard($objCard);

        $data = $this->request->getData();

        $this->assertNotEmpty($data['enduser']);
        $enduser = $data['enduser'];
        $this->assertNotEmpty($enduser['invoiceAddress']);
        $address = $enduser['invoiceAddress'];

        $strAddress = $objCard->getBillingAddress1() . ' ' . $objCard->getBillingAddress2();
        $arrAddressParts = $this->request->getAddressParts($strAddress);

        $this->assertEquals($objCard->getBillingFirstName(), $address['initials']);
        $this->assertEquals($objCard->getBillingLastName(), $address['lastName']);

        if (isset($arrAddressParts[0])) $this->assertEquals($arrAddressParts[0], $address['streetName']);
        if (isset($arrAddressParts[1])) $this->assertEquals($arrAddressParts[1], $address['streetNumber']);
        if (isset($arrAddressParts[2])) $this->assertEquals($arrAddressParts[2], $address['streetNumberExtension']);

        $this->assertEquals($objCard->getBillingPostcode(), $address['zipCode']);
        $this->assertEquals($objCard->getBillingCity(), $address['city']);
        $this->assertEquals($objCard->getBillingCountry(), $address['countryCode']);
        $this->assertEquals($objCard->getBillingState(), $address['regionCode']);

    }

    public function testPaynlItem()
    {
        $this->request->setAmount(1);
        $this->request->setClientIp('10.0.0.5');
        $this->request->setReturnUrl('https://www.pay.nl');
        $this->request->setNotifyUrl('https://www.pay.nl/exchange');


        $name = uniqid();
        $price = rand(1, 1000) / 100;
        $quantity = rand(1, 10);
        $productId = uniqid();
        $vatPercentage = rand(0, 21);

        $objItem = new \Omnipay\Paynl\Common\Item([
            'name' => $name,
            'price' => $price,
            'quantity' => $quantity,
            'productId' => $productId,
            'productType' => \Omnipay\Paynl\Common\Item::PRODUCT_TYPE_ARTICLE,
            'vatPercentage' => $vatPercentage
        ]);

        $this->request->setItems([$objItem]);

        $data = $this->request->getData();

        $this->assertNotEmpty($data['saleData']['orderData'][0]);
        $item = $data['saleData']['orderData'][0];

        $this->assertEquals($objItem->getProductId(), $item['productId']);
        $this->assertEquals($objItem->getName(), $item['description']);
        $this->assertEquals(round($objItem->getPrice() * 100), $item['price']);
        $this->assertEquals($objItem->getQuantity(), $item['quantity']);
        $this->assertEquals($objItem->getProductType(), $item['productType']);
        $this->assertEquals($objItem->getVatPercentage(), $item['vatPercentage']);
    }

    public function testStockItem()
    {
        $this->request->setAmount(1);
        $this->request->setClientIp('10.0.0.5');
        $this->request->setReturnUrl('https://www.pay.nl');
        $this->request->setNotifyUrl('https://www.pay.nl/exchange');

        $name = uniqid();
        $price = rand(1, 1000) / 100;
        $quantity = rand(1, 10);

        $objItem = new \Omnipay\Common\Item([
            'name' => $name,
            'price' => $price,
            'quantity' => $quantity,
        ]);

        $this->request->setItems([$objItem]);

        $data = $this->request->getData();

        $this->assertNotEmpty($data['saleData']['orderData'][0]);
        $item = $data['saleData']['orderData'][0];

        $this->assertEquals($objItem->getName(), $item['description']);
        $this->assertEquals(round($objItem->getPrice() * 100), $item['price']);
        $this->assertEquals($objItem->getQuantity(), $item['quantity']);

    }

    protected function setUp()
    {
        $this->request = new PurchaseRequest($this->getHttpClient(), $this->getHttpRequest());

        $this->request->initialize([
            'tokenCode' => 'AT-1234-5678',
            'apiToken' => 'some-token',
            'serviceId' => 'SL-1234-5678'
        ]);
    }
}