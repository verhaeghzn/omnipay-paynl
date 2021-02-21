<?php

namespace Omnipay\Paynl\Message\Response;


class FetchTransactionResponse extends AbstractPaynlResponse
{
    /**
     * @return bool
     */
    public function isCancelled()
    {
        return isset($this->data['paymentDetails']['stateName']) && 'CANCEL' === $this->data['paymentDetails']['stateName'];
    }

    /**
     * @return bool
     */
    public function isPending()
    {
        return
            isset($this->data['paymentDetails']['stateName']) &&
            (strpos('PENDING', strtoupper($this->data['paymentDetails']['stateName'])) !== false);
    }

    /**
     * @return bool
     */
    public function isOpen()
    {
        return $this->isPending();
    }

    /**
     * @return bool
     */
    public function isVerify()
    {
        return
            isset($this->data['paymentDetails']['stateName']) &&
            strtoupper($this->data['paymentDetails']['stateName']) == 'VERIFY';
    }

    /**
     * @return bool
     */
    public function isExpired()
    {
        return isset($this->data['paymentDetails']['stateName']) && 'EXPIRED' === $this->data['paymentDetails']['stateName'];
    }

    /**
     * @return null|string
     */
    public function getTransactionReference()
    {
        return $this->request->getTransactionReference();
    }

    /**
     * @return null
     */
    public function getStatus()
    {
        return isset($this->data['paymentDetails']['stateName']) ? $this->data['paymentDetails']['stateName'] : null;
    }

    /**
     * @return float|0
     */
    public function getAmount()
    {
        return isset($this->data['paymentDetails']['currenyAmount']) ? $this->data['paymentDetails']['currenyAmount'] / 100 : 0;
    }

    /**
     * @return float|null
     */
    public function getStornoAmount()
    {
        return (isset($this->data['stornoDetails']['stornoAmount'])&&($this->data['stornoDetails']['stornoAmount'] != '')) ? $this->data['stornoDetails']['stornoAmount'] / 100 : 0;
    }
    
    /**
     * @return string|null The paid currency
     */
    public function getCurrency()
    {
        return isset($this->data['paymentDetails']['paidCurrency']) ? $this->data['paymentDetails']['paidCurrency'] : null;
    }

    /**
     * @return boolean
     */
    public function isPaid()
    {
        return isset($this->data['paymentDetails']['stateName']) && in_array($this->data['paymentDetails']['stateName'],
                array('PAID', 'AUTHORIZE'));
    }

    /**
     * @return boolean
     */
    public function isAuthorized()
    {
        return isset($this->data['paymentDetails']['stateName']) && $this->data['paymentDetails']['stateName'] == 'AUTHORIZE';
    }
}
