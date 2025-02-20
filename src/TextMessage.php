<?php

namespace WebSms;

class TextMessage extends Message
{
    protected string $messageContent;

    public function __construct(array $recipientAddressList, string $messageContent)
    {
        $this->checkRecipientAddressList($recipientAddressList);

        $this->messageContent = $messageContent;
        $this->recipientAddressList = $recipientAddressList;
    }

    public function getMessageContent(): string
    {
        return $this->messageContent;
    }

    /**
     * Set utf8 string message text.
     */
    public function messageContent(string $messageContent): static
    {
        $this->messageContent = $messageContent;

        return $this;
    }

    public function getMessageCount(): int
    {
        $length = strlen(trim($this->getMessageContent()));
        if ($length > 160) {
            return (int) ($length / 153) + 1;
        }

        return 1;
    }
}
