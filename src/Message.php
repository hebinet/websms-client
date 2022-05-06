<?php

namespace WebSms;

use WebSms\Exception\ParameterValidationException;

abstract class Message
{
    protected static array $availableSenderAddressType = [
        'national',
        'international',
        'alphanumeric',
        'shortcode',
    ];

    protected array $recipientAddressList = [];

    protected string $senderAddress;

    protected string $senderAddressType;

    protected bool $sendAsFlashSms;

    protected string $notificationCallbackUrl;

    protected string $clientMessageId;

    protected int $priority;

    public function getRecipientAddressList(): array
    {
        return $this->recipientAddressList;
    }

    public function recipientAddressList(array $recipientAddressList): static
    {
        $this->recipientAddressList = $recipientAddressList;

        return $this;
    }

    public function getSenderAddress(): string
    {
        return $this->senderAddress;
    }

    /**
     * set string of sender address msisdn or alphanumeric
     * sender address is dependend on user account
     */
    public function senderAddress(string $senderAddress): static
    {
        $this->senderAddress = $senderAddress;

        return $this;
    }

    public function getSenderAddressType(): string
    {
        return $this->senderAddressType;
    }

    /**
     * Depending on account settings this can be set to
     * 'national', 'international', 'alphanumeric' or 'shortcode'
     */
    public function senderAddressType(string $senderAddressType): static
    {
        if ( ! in_array($senderAddressType, self::$availableSenderAddressType, true)) {
            throw new ParameterValidationException("Given senderAddressType '$senderAddressType' is invalid");
        }

        $this->senderAddressType = $senderAddressType;

        return $this;
    }

    public function getSendAsFlashSms(): bool
    {
        return $this->sendAsFlashSms;
    }

    /**
     * Set send as flash sms flag true or false
     * (SMS is not saved on SIM, but shown directly on screen)
     */
    public function sendAsFlashSms(bool $sendAsFlashSms): static
    {
        $this->sendAsFlashSms = $sendAsFlashSms;

        return $this;
    }

    public function getNotificationCallbackUrl(): string
    {
        return $this->notificationCallbackUrl;
    }

    /**
     * Set string og notification callback url
     * customers url that listens for delivery report notifications
     * or replies for this message
     */
    public function notificationCallbackUrl(string $notificationCallbackUrl): static
    {
        $this->notificationCallbackUrl = $notificationCallbackUrl;

        return $this;
    }

    public function getClientMessageId(): string
    {
        return $this->clientMessageId;
    }

    /**
     * Set message id for this message, returned with response
     * and used for notifications
     */
    public function setClientMessageId(string $clientMessageId): static
    {
        $this->clientMessageId = $clientMessageId;

        return $this;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    /**
     * Sets message priority as integer (1 to 9)
     * (if supported by account settings)
     */
    public function priority(int $priority): static
    {
        $this->priority = $priority;

        return $this;
    }

    public function getJsonData(): array
    {
        $object_vars = get_object_vars($this);
        $result = [];
        foreach ($object_vars as $key => $value) {
            if (is_object($value) && method_exists($value, 'getJsonData')) {
                $value = $value->getJsonData();
            }
            if ($value !== null) {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    public function checkRecipientAddressList(array $recipientAddressList): void
    {
        if (empty($recipientAddressList)) {
            throw new ParameterValidationException("Missing recipients in message");
        }

        foreach ($recipientAddressList as $value) {
            if ( ! is_numeric($value)) {
                throw new ParameterValidationException("Recipient '".$value."' is invalid. (must be numeric)");
            }
            if (is_string($value) && (str_starts_with($value, '0') || ! preg_match('/^\d{1,15}$/', $value))) {
                throw new ParameterValidationException("Recipient '".$value."' is invalid. (max. 15 digits full international MSISDN. Example: 4367612345678)");
            }
        }
    }

}
