<?php

namespace Omnipay\Paynl\Message;

class FetchTransactionResponse extends AbstractResponse
{


    /**
     * {@inheritdoc}
     */
    public function isSuccessful()
    {
        return parent::isSuccessful();
    }

    /**
     * @return boolean
     */
    public function isOpen()
    {
        return isset($this->data['paymentDetails']['stateName']) && 'PENDING' === $this->data['paymentDetails']['stateName'];
    }

    /**
     * @return boolean
     */
    public function isCancelled()
    {
        return isset($this->data['paymentDetails']['stateName']) && 'CANCEL' === $this->data['paymentDetails']['stateName'];
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
    public function isExpired()
    {
        return isset($this->data['paymentDetails']['stateName']) && 'EXPIRED' === $this->data['paymentDetails']['stateName'];
    }

    /**
     * @return string|null
     */
    public function getTransactionReference()
    {
        return isset($this->data['transaction']['transactionId']) ? $this->data['transaction']['transactionId'] : null;
    }

    /**
     * @return string|null
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
        return isset($this->data['paymentDetails']['paidAmount']) ? $this->data['paymentDetails']['paidAmount'] / 100 : 0;
    }
    
    /**
     * @return float|0
     */
    public function getStornoAmount()
    {
        return (isset($this->data['stornoDetails']['stornoAmount'])&&($this->data['stornoDetails']['stornoAmount'] != '')) ? $this->data['stornoDetails']['stornoAmount'] / 100 : 0;
    }
    
    /**
     * @return bool
     */
    public function isPending()
    {
        if (isset($this->data['paymentDetails']['stateName'])) {
            $state = $this->data['paymentDetails']['stateName'];

            return $state === 'PENDING' || $state === 'VERIFY';
        } else {
            return false;
        }
    }
}
