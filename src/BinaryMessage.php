<?php
namespace WebSms;

use WebSms\Exception\ParameterValidationException;

class BinaryMessage extends Message
{
    protected array $messageContent;

    protected bool $userDataHeaderPresent;

    public function __construct(array $recipientAddressList, array $messageContent, bool $userDataHeaderPresent)
    {
        $this->checkRecipientAddressList($recipientAddressList);

        $this->messageContent = $messageContent;
        $this->userDataHeaderPresent = $userDataHeaderPresent;
        $this->recipientAddressList = $recipientAddressList;
    }

    public function getMessageContent(): array
    {
        return $this->messageContent;
    }

    /**
     * Set binary message content (array of base64 encoded binary strings)
     */
    public function messageContent(array $messageContent): static
    {
        $this->messageContent = $messageContent;

        return $this;
    }

    public function getUserDataHeaderPresent(): bool
    {
        return $this->userDataHeaderPresent;
    }

    /**
     * Set boolean userDataHeaderPresent flag
     * When set to true, messageContent segments are expected
     * to contain a UserDataHeader
     */
    public function userDataHeaderPresent(bool $userDataHeaderPresent): static
    {
        $this->userDataHeaderPresent = $userDataHeaderPresent;

        return $this;
    }
}
