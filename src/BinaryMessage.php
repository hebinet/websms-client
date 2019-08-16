<?php namespace WebSms;

use WebSms\Exception\ParameterValidationException;

class BinaryMessage extends Message
{

    /**
     * @var array
     */
    protected $messageContent;
    /**
     * @var bool
     */
    protected $userDataHeaderPresent;

    /**
     * BinaryMessage constructor.
     *
     * @param array $recipientAddressList
     * @param array $messageContent
     * @param bool $userDataHeaderPresent
     *
     * @throws ParameterValidationException
     */
    public function __construct(array $recipientAddressList, array $messageContent, bool $userDataHeaderPresent)
    {
        $this->checkRecipientAddressList($recipientAddressList);

        $this->messageContent = $messageContent;
        $this->userDataHeaderPresent = $userDataHeaderPresent;
        $this->recipientAddressList = $recipientAddressList;
    }

    /**
     * Returns set messageContent segments (array)
     *
     * @return array
     */
    public function getMessageContent()
    {
        return $this->messageContent;
    }

    /**
     * Set binary message content (array of base64 encoded binary strings)
     *
     * @param $messageContent
     */
    public function setMessageContent(array $messageContent)
    {
        $this->messageContent = $messageContent;
    }

    /**
     * Returns set UserDataHeaderPresent flag
     *
     * @return bool
     */
    public function getUserDataHeaderPresent()
    {
        return $this->userDataHeaderPresent;
    }

    /**
     * Set boolean userDataHeaderPresent flag
     * When set to true, messageContent segments are expected
     * to contain a UserDataHeader
     *
     * @param bool $userDataHeaderPresent
     */
    public function setUserDataHeaderPresent(bool $userDataHeaderPresent)
    {
        $this->userDataHeaderPresent = $userDataHeaderPresent;
    }
}