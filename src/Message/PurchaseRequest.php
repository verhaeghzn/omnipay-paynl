<?php

namespace Omnipay\Paynl\Message;

use Omnipay\Common\Item;

/**
 * Paynl Purchase Request
 *
 * @method \Omnipay\Paynl\Message\PurchaseResponse send()
 */
class PurchaseRequest extends AbstractRequest
{

    /**
     * Regex to find streetname, housenumber and suffix out of a street string
     * @var string
     */
    private $addressRegex = '#^([a-z0-9 [:punct:]\']*) ([0-9]{1,5})([a-z0-9 \-/]{0,})$#i';

    /**
     * Return the data formatted for PAY.nl
     * @return array
     */
    public function getData()
    {
        $this->validate('apitoken', 'serviceId', 'amount', 'description', 'returnUrl');

        $data['amount'] = round($this->getAmount() * 100);
        $data['transaction']['description'] = $this->getDescription();
        $data['finishUrl'] = $this->getReturnUrl();
        $data['ipAddress'] = $this->getClientIp();
        if ($this->getPaymentMethod()) {
            $data['paymentOptionId'] = $this->getPaymentMethod();
            if ($this->getPaymentMethod() == 10 && $this->getIssuer()) {
                $data['paymentOptionSubId'] = $this->getIssuer();
            }
        }

        if ($this->getNotifyUrl()) {
            $data['transaction']['orderExchangeUrl'] = $this->getNotifyUrl();
        }

        if ($this->getCurrency()) {
            $data['transaction']['currency'] = $this->getCurrency();
        }
        $data['enduser'] = array();
        if ($card = $this->getCard()) {
            $billingAddressParts = $this->getAddressParts($card->getBillingAddress1());
            $shippingAddressParts = ($card->getShippingAddress1() ? $this->getAddressParts($card->getShippingAddress1()) : $billingAddressParts);

            $data['enduser'] = array(
                'initials' => $card->getFirstName(), //Pay has no support for firstName, but some methods require full name. Conversion to initials is handled by Pay.nl based on the payment method.
                'lastName' => $card->getLastName(),
                'gender' => $card->getGender(), //Should be inserted in the CreditCard as M/F
                'dob' => $card->getBirthday('d-m-Y'),
                'phoneNumber' => $card->getPhone(),
                'emailAddress' => $card->getEmail(),
                'language' => $this->getLanguage() ? $this->getLanguage() : $card->getBillingCountry(),
                'address' => array(
                    'streetName' => isset($shippingAddressParts[1]) ? $shippingAddressParts[1] : null,
                    'streetNumber' => isset($shippingAddressParts[2]) ? $shippingAddressParts[2] : null,
                    'streetNumberExtension' => isset($shippingAddressParts[3]) ? $shippingAddressParts[3] : null,
                    'zipCode' => $card->getShippingPostcode(),
                    'city' => $card->getShippingCity(),
                    'countryCode' => $card->getShippingCountry(),
                    'regionCode' => $card->getShippingState()
                ),
                'invoiceAddress' => array(
                    'initials' => $card->getBillingFirstName(),
                    'lastName' => $card->getBillingLastName(),
                    'streetName' => isset($billingAddressParts[1]) ? $billingAddressParts[1] : null,
                    'streetNumber' => isset($billingAddressParts[2]) ? $billingAddressParts[2] : null,
                    'streetNumberExtension' => isset($billingAddressParts[3]) ? $billingAddressParts[3] : null,
                    'zipCode' => $card->getBillingPostcode(),
                    'city' => $card->getBillingCity(),
                    'countryCode' => $card->getBillingCountry(),
                    'regionCode' => $card->getBillingState()
                )
            );
        }
        if(!empty($this->getCustomerReference())){
            $data['enduser']['customerReference'] = $this->getCustomerReference();
        }
        if(is_numeric($this->getCustomerTrust())){
            $data['enduser']['customerTrust'] = $this->getCustomerTrust();
        }

        $data['saleData'] = array();
        if ($items = $this->getItems()) {
            $data['saleData'] = array(
                'orderData' => array_map(function ($item) {
                    /** @var Item $item */
                    $data = array(
                        'description' => $item->getDescription(),
                        'price' => ($item->getPrice() * 100), //convert the price from a double into a string
                        'quantity' => $item->getQuantity(),
                        'vatCode' => 0,
                    );

                    if (method_exists($item, 'getProductId')) {
                        $data['productId'] = $item->getProductId();
                    } else {
                        $data['productId'] = substr($item->getName(), 0, 25);
                    }

                    if (method_exists($item, 'getProductType')) {
                        $data['productType'] = $item->getProductType();
                    }

                    if (method_exists($item, 'getVatPercentage')) {
                        $data['vatPercentage'] = $item->getVatPercentage();
                    }

                    return $data;
                }, $items->all()),
            );
        }

        if(!empty($this->getInvoiceDate())){
            $data['saleData']['invoiceDate'] = $this->getInvoiceDate();
        }
        if(!empty($this->getDeliveryDate())){
            $data['saleData']['deliveryDate'] = $this->getDeliveryDate();
        }

        if ($statsData = $this->getStatsData()) {
            // Could be someone erroneously not set an array
            if (is_array($statsData)) {
                $allowableParams = ["promotorId", "info", "tool", "extra1", "extra2", "extra3", "transferData"];
                $data['statsData'] = array_filter($statsData, function ($k) use ($allowableParams) {
                    return in_array($k, $allowableParams);
                }, ARRAY_FILTER_USE_KEY);
            }
        }

        $data['testMode'] = $this->getTestMode() ? 1 : 0;
        return $data;
    }

    /**
     * @return array
     */
    public function getStatsData()
    {
        return $this->getParameter('statsData');
    }

    /**
     * @param $value array
     * @return \Omnipay\Common\Message\AbstractRequest
     */
    public function setStatsData($value)
    {
        return $this->setParameter('statsData', $value);
    }

    /**
     * @return string
     */
    public function getInvoiceDate()
    {
        return $this->getParameter('invoiceDate');
    }

    /**
     * @param $value string
     * @return \Omnipay\Common\Message\AbstractRequest
     */
    public function setInvoiceDate($value)
    {
        return $this->setParameter('invoiceDate', $value);
    }

    /**
     * @return string
     */
    public function getDeliveryDate()
    {
        return $this->getParameter('deliveryDate');
    }

    /**
     * @param $value string
     * @return \Omnipay\Common\Message\AbstractRequest
     */
    public function setDeliveryDate($value)
    {
        return $this->setParameter('deliveryDate', $value);
    }

    /**
     * @return string
     */
    public function getCustomerReference()
    {
        return $this->getParameter('customerReference');
    }

    /**
     * @param $value string
     * @return \Omnipay\Common\Message\AbstractRequest
     */
    public function setCustomerReference($value)
    {
        return $this->setParameter('customerReference', $value);
    }

    /**
     * @return integer
     */
    public function getCustomerTrust()
    {
        return $this->getParameter('customerTrust');
    }

    /**
     * @param $value integer
     * @return \Omnipay\Common\Message\AbstractRequest
     */
    public function setCustomerTrust($value)
    {
        return $this->setParameter('customerTrust', $value);
    }

    /**
     * Send the data
     * @param array $data
     * @return AbstractResponse
     */
    public function sendData($data)
    {
        $httpResponse = $this->sendRequest('POST', 'transaction/start', $data);
        return $this->response = new PurchaseResponse($this, $httpResponse->json());
    }

    /**
     * Get the parts of an address
     * @param string $address
     * @return array
     */
    public function getAddressParts($address)
    {
        $addressParts = [];
        preg_match($this->addressRegex, $address, $addressParts);
        return array_filter($addressParts, 'trim');
    }
}
