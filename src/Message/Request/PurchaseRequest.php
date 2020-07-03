<?php

namespace Omnipay\Paynl\Message\Request;


use Omnipay\Common\Item;
use Omnipay\Paynl\Message\Response\PurchaseResponse;

/**
 * Class PurchaseRequest
 * @package Omnipay\Paynl\Message\Request
 *
 * @method PurchaseResponse send()
 */
class PurchaseRequest extends AbstractPaynlRequest
{
    /**
     * Regex to find streetname, housenumber and suffix out of a street string
     * @var string
     */
    private $addressRegex = '#^([a-z0-9 [:punct:]\']*) ([0-9]{1,5})([a-z0-9 \-/]{0,})$#i';

    /**
     * @return array
     * @throws \Omnipay\Common\Exception\InvalidRequestException
     */
    public function getData()
    {
        $this->validate('tokenCode', 'apiToken', 'serviceId', 'amount', 'clientIp', 'returnUrl');

        // Mandatory fields
        $data = [
            'serviceId' => $this->getServiceId(),
            'amount' => $this->getAmountInteger(),
            'ipAddress' => $this->getClientIp(),
            'finishUrl' => $this->getReturnUrl(),
        ];

        $data['transaction'] = [];
        $data['transaction']['description'] = $this->getDescription() ?: null;
        $data['transaction']['currency'] = !empty($this->getCurrency()) ? $this->getCurrency() : 'EUR';
        $data['transaction']['orderExchangeUrl'] = !empty($this->getNotifyUrl()) ? $this->getNotifyUrl() : null;
        $data['transaction']['orderNumber'] = !empty($this->getOrderNumber()) ? $this->getOrderNumber() : null;

        $data['testMode'] = $this->getTestMode() ? 1 : 0;
        $data['paymentOptionId'] = !empty($this->getPaymentMethod()) ? $this->getPaymentMethod() : null;
        $data['paymentOptionSubId'] = !empty($this->getIssuer()) ? $this->getIssuer() : null;

        $data['enduser'] = [];
        if ($card = $this->getCard()) {
            $billingAddressParts = $this->getAddressParts($card->getBillingAddress1() . ' ' . $card->getBillingAddress2());
            $shippingAddressParts = ($card->getShippingAddress1() ? $this->getAddressParts($card->getShippingAddress1() . ' ' . $card->getShippingAddress2()) : $billingAddressParts);

            $data['enduser'] = [
                'initials' => $card->getFirstName(), //Pay has no support for firstName, but some methods require full name. Conversion to initials is handled by Pay.nl based on the payment method.
                'lastName' => $card->getLastName(),
                'gender' => $card->getGender(), //Should be inserted in the CreditCard as M/F
                'dob' => $card->getBirthday('d-m-Y'),
                'phoneNumber' => $card->getPhone(),
                'emailAddress' => $card->getEmail(),
                'language' => substr($card->getCountry(), 0, 2),
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
            ];
        }

        if (!empty($this->getCustomerReference())) {
            $data['enduser']['customerReference'] = $this->getCustomerReference();
        }

        if (is_numeric($this->getCustomerTrust())) {
            $data['enduser']['customerTrust'] = $this->getCustomerTrust();
        }

        $data['saleData'] = [];

        if ($items = $this->getItems()) {
            $data['saleData'] = [
                'orderData' => array_map(function ($item) {
                    /** @var Item $item */
                    $data = [
                        'description' => $item->getName() ?: $item->getDescription(),
                        'price' => round($item->getPrice() * 100),
                        'quantity' => $item->getQuantity(),
                        'vatCode' => 0,
                    ];
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
            ];
        }

        if ($statsData = $this->getStatsData()) {
            // Could be someone erroneously not set an array
            if (is_array($statsData)) {
                $allowableParams = ["promotorId", "info", "tool", "extra1", "extra2", "extra3", "transferData"];
                $data['statsData'] = array_filter($statsData, function($k) use ($allowableParams) {
                    return in_array($k, $allowableParams);
                }, ARRAY_FILTER_USE_KEY);
              $data['statsData']['object'] = 'omnipay';
            }
        }

        if (!empty($this->getInvoiceDate())) {
            $data['saleData']['invoiceDate'] = $this->getInvoiceDate();
        }
        if (!empty($this->getDeliveryDate())) {
            $data['saleData']['deliveryDate'] = $this->getDeliveryDate();
        }


        return $data;
    }

    /**
     * @param array $data
     * @return \Omnipay\Common\Message\ResponseInterface|PurchaseResponse
     */
    public function sendData($data)
    {
        $responseData = $this->sendRequest('start', $data);

        return $this->response = new PurchaseResponse($this, $responseData);
    }

    /**
     * @param $value array
     * @return $this
     */
    public function setStatsData($value)
    {
        return $this->setParameter('statsData', $value);
    }

    /**
     * @return array
     */
    public function getStatsData()
    {
        return $this->getParameter('statsData');
    }

    /**
     * Set the ordernumber
     *
     * @param $value array
     * @return $this
     */
    public function setOrderNumber($value)
    {
      return $this->setParameter('orderNumber', $value);
    }

    /**
     * @return mixed
     */
    public function getOrderNumber()
    {
      return $this->getParameter('orderNumber');
    }

    /**
     * @param $value string
     * @return $this
     */
    public function setInvoiceDate($value)
    {
        return $this->setParameter('invoiceDate', $value);
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
     * @return $this
     */
    public function setDeliveryDate($value)
    {
        return $this->setParameter('deliveryDate', $value);
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
     * @return $this
     */
    public function setCustomerReference($value)
    {
        return $this->setParameter('customerReference', $value);
    }

    public function getCustomerReference()
    {
        return $this->getParameter('customerReference');
    }

    /**
     * @param $value int Between -10 and 10
     * @return $this
     */
    public function setCustomerTrust($value)
    {
        return $this->setParameter('customerTrust', $value);
    }

    /**
     * @return int
     */
    public function getCustomerTrust()
    {
        return $this->getParameter('customerTrust');
    }

    /**
     * Get the parts of an address
     * @param string $address
     * @return array
     */
    public function getAddressParts($address)
    {
        $addressParts = [];
        preg_match($this->addressRegex, trim($address), $addressParts);
        return array_filter($addressParts, 'trim');
    }
}