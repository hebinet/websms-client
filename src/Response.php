<?php
namespace WebSms;

use Psr\Http\Message\ResponseInterface;
use stdClass;

class Response
{
    protected stdClass $apiResult;

    protected ResponseInterface|\GuzzleHttp\Psr7\Response $httpResponse;

    public function __construct(stdClass $apiResult, ResponseInterface $httpResponse)
    {
        $this->apiResult = $apiResult;
        $this->httpResponse = $httpResponse;
    }

    public function getRawContent(): string
    {
        return (string)$this->httpResponse->getBody();
    }

    public function getApiStatusCode(): int
    {
        return $this->apiResult->statusCode;
    }

    public function getApiStatusMessage(): string
    {
        return $this->apiResult->statusMessage;
    }

    public function getTransferId(): string
    {
        return $this->apiResult->transferId;
    }

    public function getClientMessageId(): ?string
    {
        return $this->apiResult->clientMessageId ?? null;
    }

    public function __call(string $method, array $arguments)
    {
        return call_user_func_array([$this->httpResponse, $method], $arguments);
    }
}
