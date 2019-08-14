<?php namespace WebSms;

use WebSms\Exception\ParameterValidationException;

class TextMessage extends Message
{
    /**
     * @var string
     */
    protected $messageContent;

    /**
     * TextMessage constructor.
     *
     * @param array $recipientAddressList
     * @param string $messageContent
     *
     * @throws ParameterValidationException
     */
    function __construct(array $recipientAddressList, string $messageContent)
    {
        $this->checkRecipientAddressList($recipientAddressList);

        $this->messageContent = $messageContent;
        $this->recipientAddressList = $recipientAddressList;

    }

    /**
     * Returns set messageContent
     *
     * @return string
     */
    public function getMessageContent()
    {
        return $this->messageContent;
    }

    /**
     * Set utf8 string message text
     *
     * @param string $messageContent
     */
    public function setMessageContent(string $messageContent)
    {
        $this->messageContent = $messageContent;
    }
}