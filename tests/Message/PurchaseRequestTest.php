<?php


namespace Omnipay\Paynl\Message;


use Mockery as m;
use Omnipay\Common\CreditCard;
use Omnipay\Common\Item as PlainItem;
use Omnipay\Paynl\Common\Item;
use Omnipay\Tests\TestCase;

class PurchaseRequestTest extends TestCase
{
    /**
     * @var PurchaseRequest
     */
    private $request;

    public function testWithItems()
    {
        $arrItems = array();

        $item = new Item();
        $item->setProductId('SKU01234')
             ->setProductType(Item::PRODUCT_TYPE_ARTICLE)
             ->setVatPercentage(21)
             ->setDescription('Description')
             ->setName('Pay item')
             ->setPrice('2.50')
             ->setQuantity(4);
        $arrItems[] = $item;

        $item = new PlainItem();
        $item->setDescription('Description')
             ->setName('Plain item')
             ->setPrice('2.50')
             ->setQuantity(4);
        $arrItems[] = $item;


        $this->request->setItems($arrItems);

        $data = $this->request->getData();
        $this->assertArrayHasKey('saleData', $data);
        $this->assertArrayHasKey('orderData', $data['saleData']);
    }

    public function testCurrency(){
        $this->request->setCurrency('USD');
        $data = $this->request->getData();

        $this->assertArrayHasKey('transaction',$data);
        $this->assertArrayHasKey('currency', $data['transaction']);
        $this->assertEquals('USD', $data['transaction']['currency']);
    }

    public function testLanguage(){
        $this->request->setLanguage('NL');
        $data = $this->request->getData();

        $this->assertArrayHasKey('enduser', $data);
        $this->assertEquals('NL', $data['enduser']['language']);
    }
    public function testStatsData()
    {
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
        $invoiceDate = new \DateTime('now');
        $deliveryDate = new \DateTime('tomorrow');
        $invoiceDate = $invoiceDate->format('d-m-Y');
        $deliveryDate = $deliveryDate->format('d-m-Y');

        $this->request->setInvoiceDate($invoiceDate);
        $this->request->setDeliveryDate($deliveryDate);

        $data = $this->request->getData();

        $this->assertArrayHasKey('saleData', $data);
        $this->assertArrayHasKey('invoiceDate', $data['saleData']);
        $this->assertArrayHasKey('deliveryDate', $data['saleData']);
        $this->assertEquals($invoiceDate, $data['saleData']['invoiceDate']);
        $this->assertEquals($deliveryDate, $data['saleData']['deliveryDate']);
    }
    public function testCustomerData()
    {
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

    public function testWithCardOnlyShipping(){
        $arrCard = $this->getValidCard();

        unset($arrCard['billingAddress1']);
        unset($arrCard['billingAddress2']);
        unset($arrCard['billingCity']);
        unset($arrCard['billingPostcode']);
        unset($arrCard['billingState']);
        unset($arrCard['billingCountry']);
        unset($arrCard['billingPhone']);

        $card = new CreditCard($arrCard);


        $this->request->setCard($card);
        $this->assertEquals($card, $this->request->getCard());

        $data = $this->request->getData();
        $this->assertEquals($arrCard['shippingCity'], $data['enduser']['address']['city']);
        $this->assertEquals($arrCard['shippingPostcode'], $data['enduser']['address']['zipCode']);
        $this->assertEquals($arrCard['shippingCountry'], $data['enduser']['address']['countryCode']);
        $this->assertEquals($arrCard['shippingState'], $data['enduser']['address']['regionCode']);

    }
    public function testWithCardOnlyBilling(){
        $arrCard = $this->getValidCard();

        unset($arrCard['shippingAddress1']);
        unset($arrCard['shippingAddress2']);
        unset($arrCard['shippingCity']);
        unset($arrCard['shippingPostcode']);
        unset($arrCard['shippingState']);
        unset($arrCard['shippingCountry']);
        unset($arrCard['shippingPhone']);

        $card = new CreditCard($arrCard);


        $this->request->setCard($card);
        $this->assertEquals($card, $this->request->getCard());

        $data = $this->request->getData();

        $this->assertEquals($arrCard['billingCity'], $data['enduser']['invoiceAddress']['city']);
        $this->assertEquals($arrCard['billingPostcode'], $data['enduser']['invoiceAddress']['zipCode']);
        $this->assertEquals($arrCard['billingCountry'], $data['enduser']['invoiceAddress']['countryCode']);
        $this->assertEquals($arrCard['billingState'], $data['enduser']['invoiceAddress']['regionCode']);
    }

    public function testSuccessIdeal()
    {
        $this->setMockHttpResponse('PurchaseSuccess.txt');
        $this->request->setPaymentMethod(10);
        $this->request->setIssuer(1);

        $response = $this->request->send();

        $this->assertFalse($response->isSuccessful());
        $this->assertTrue($response->isRedirect());
        $this->assertNotEmpty($response->getRedirectUrl());
        $this->assertEquals('GET', $response->getRedirectMethod());
        $this->assertNull($response->getRedirectData());
        $this->assertEmpty($response->getMessage());
        $this->assertNotEmpty($response->getTransactionReference());
    }

    public function testErrorIdeal()
    {
        $this->setMockHttpResponse('PurchaseError.txt');
        $this->request->setPaymentMethod(999); // non existent payment method
        $this->request->setIssuer(1);

        $response = $this->request->send();

        $this->assertFalse($response->isSuccessful());
        $this->assertFalse($response->isRedirect());
        $this->assertEmpty($response->getRedirectUrl());

        $this->assertNotEmpty($response->getMessage());
    }

    protected function setUp()
    {
        parent::setUp();

        $arguments = array($this->getHttpClient(), $this->getHttpRequest());

        $this->request = m::mock('Omnipay\Paynl\Message\PurchaseRequest[getEndpoint]', $arguments);

        $arrCard = $this->getValidCard();

        $card = new CreditCard($arrCard);
        $this->request->setCard($card);
        $this->request->setApitoken('token');
        $this->request->setAmount('10.00');
        $this->request->setDescription('description');
        $this->request->setServiceId('SL-1234-5678');
        $this->request->setReturnUrl('http://localhost/return');
        $this->request->setNotifyUrl('http://localhost/notify');

    }
}