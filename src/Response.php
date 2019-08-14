<?php namespace WebSMS;

use Psr\Http\Message\ResponseInterface;
use stdClass;

class Response
{
    /**
     * @var stdClass
     */
    private $apiResult;
    /**
     * @var \GuzzleHttp\Psr7\Response
     */
    private $httpResponse;


    /**
     * Response constructor.
     *
     * @param stdClass $apiResult
     * @param ResponseInterface $httpResponse
     */
    public function __construct(stdClass $apiResult, ResponseInterface $httpResponse)
    {
        $this->apiResult = $apiResult;
        $this->httpResponse = $httpResponse;
    }

    /**
     * Returns raw content of response
     *
     * @return string
     */
    public function getRawContent()
    {
        return (string)$this->httpResponse->getBody();
    }

    /**
     * Returns received StatusCode of API Response
     *
     * @return int
     */
    public function getApiStatusCode()
    {
        return $this->apiResult->statusCode;
    }

    /**
     * Returns received StatusMessage of API Response
     *
     * @return string
     */
    public function getApiStatusMessage()
    {
        return $this->apiResult->statusMessage;
    }

    /**
     * Returns received TransferId of API Response for successfully sent Message
     *
     * @return string
     */
    public function getTransferId()
    {
        return $this->apiResult->transferId;
    }

    /**
     * Returns received TransferId of API Response for successfully sent Message
     *
     * @return string|null
     */
    public function getClientMessageId()
    {
        return $this->apiResult->clientMessageId ?? null;
    }

    /**
     * Magic method to pipe all other method calls to the Guzzle Response Object
     *
     * @param string $method
     * @param array $arguments
     *
     * @return mixed
     */
    public function __call(string $method, array $arguments)
    {
        return call_user_func_array([$this->httpResponse, $method], $arguments);
    }
}