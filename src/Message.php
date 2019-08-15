<?php namespace WebSms;

use WebSms\Exception\ParameterValidationException;

abstract class Message
{

    /**
     * @var array
     */
    static $availableSenderAddressType = [
        'national',
        'international',
        'alphanumeric',
        'shortcode'
    ];

    /**
     * @var array
     */
    protected $recipientAddressList = [];
    /**
     * @var string
     */
    protected $senderAddress;
    /**
     * @var string
     */
    protected $senderAddressType;
    /**
     * @var bool
     */
    protected $sendAsFlashSms;
    /**
     * @var string
     */
    protected $notificationCallbackUrl;
    /**
     * @var string
     */
    protected $clientMessageId;
    /**
     * @var int
     */
    protected $priority;


    /**
     * Returns array of set recipients
     *
     * @return array
     */
    public function getRecipientAddressList()
    {
        return $this->recipientAddressList;
    }

    /**
     * set array of recipients (array of strings containing full international MSISDNs)
     *
     * @param array $recipientAddressList
     */
    public function setRecipientAddressList(array $recipientAddressList)
    {
        $this->recipientAddressList = $recipientAddressList;
    }

    /**
     * Returns set senderAddress
     *
     * @return string
     */
    public function getSenderAddress()
    {
        return $this->senderAddress;
    }

    /**
     * set string of sender address msisdn or alphanumeric
     * sender address is dependend on user account
     *
     * @param string $senderAddress
     */
    public function setSenderAddress(string $senderAddress)
    {
        $this->senderAddress = $senderAddress;
    }

    /**
     * Returns set sender address type
     *
     * @return string
     */
    public function getSenderAddressType()
    {
        return $this->senderAddressType;
    }

    /**
     * Depending on account settings this can be set to
     * 'national', 'international', 'alphanumeric' or 'shortcode'
     *
     * @param string $senderAddressType
     *
     * @throws ParameterValidationException
     */
    public function setSenderAddressType(string $senderAddressType)
    {
        if (!in_array($senderAddressType, self::$availableSenderAddressType)) {
            throw new ParameterValidationException("given senderAddressType '$senderAddressType' is invalid");
        } else {
            $this->senderAddressType = $senderAddressType;
        }
    }

    /**
     * Returns set SendAsFlashSms flag
     *
     * @return bool
     */
    public function getSendAsFlashSms()
    {
        return $this->sendAsFlashSms;
    }

    /**
     * Set send as flash sms flag true or false
     * (SMS is not saved on SIM, but shown directly on screen)
     *
     * @param bool $sendAsFlashSms
     */
    public function setSendAsFlashSms(bool $sendAsFlashSms)
    {
        $this->sendAsFlashSms = $sendAsFlashSms;
    }

    /**
     * @return string
     */
    public function getNotificationCallbackUrl()
    {
        return $this->notificationCallbackUrl;
    }

    /**
     * Set string og notification callback url
     * customers url that listens for delivery report notifications
     * or replies for this message
     *
     * @param string $notificationCallbackUrl
     */
    public function setNotificationCallbackUrl(string $notificationCallbackUrl)
    {
        $this->notificationCallbackUrl = $notificationCallbackUrl;
    }

    /**
     * Returns set clientMessageId
     *
     * @return string
     */
    public function getClientMessageId()
    {
        return $this->clientMessageId;
    }

    /**
     * Set message id for this message, returned with response
     * and used for notifications
     *
     * @param string $clientMessageId
     */
    public function setClientMessageId(string $clientMessageId)
    {
        $this->clientMessageId = $clientMessageId;
    }

    /**
     * Returns set message priority
     *
     * @return int
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * Sets message priority as integer (1 to 9)
     * (if supported by account settings)
     *
     * @param int $priority
     */
    public function setPriority(int $priority)
    {
        $this->priority = $priority;
    }

    /**
     * Used to build json array of message for json encoding
     *
     * @return array
     */
    public function getJsonData()
    {
        $object_vars = get_object_vars($this);
        $result = [];
        foreach ($object_vars as $key => $value) {
            if (is_object($value) && method_exists($value, 'getJsonData')) {
                $value = $value->getJsonData();
            }
            if (!is_null($value)) {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * Used to check validity of array
     *
     * @param array $recipientAddressList
     *
     * @throws ParameterValidationException
     */
    public function checkRecipientAddressList(array $recipientAddressList)
    {
        if (count($recipientAddressList) == 0) {
            throw new ParameterValidationException("Missing recipients in message");
        }
        
        foreach ($recipientAddressList as $value) {
            if (!is_numeric($value)) {
                throw new ParameterValidationException("Recipient '" . $value . "' is invalid. (must be numeric)");
            }
            if (is_string($value) && (!preg_match('/^\d{1,15}$/', $value) || preg_match('/^0/', $value))) {
                throw new ParameterValidationException("Recipient '" . $value . "' is invalid. (max. 15 digits full international MSISDN. Example: 4367612345678)");
            }
            unset($value);
        }
    }

}