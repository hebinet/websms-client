<?php

namespace WebSms;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;
use WebSms\Exception\ApiException;
use WebSms\Exception\AuthorizationFailedException;
use WebSms\Exception\HttpConnectionException;
use WebSms\Exception\ParameterValidationException;
use WebSms\Exception\UnknownResponseException;

class Client
{
    protected string $VERSION = '1.0.8';

    protected string $username;

    protected string $accessToken;

    protected int $mode;

    protected ?string $password;

    protected string $url;

    protected string $path;

    protected string $port;

    protected string $scheme;

    protected string $host;

    protected bool $verbose = false;

    protected int $connectionTimeout = 10;

    protected string $endpointJson = '/json/smsmessaging/';

    protected string $endpointText = 'text';

    protected string $endpointBinary = 'binary';

    protected bool $sslVerifyHost = true;

    protected array $guzzleOptions = [];

    protected bool $testMode = false;

    public function __construct(
        string $url,
        string $usernameOrAccessToken,
        ?string $password,
        int $mode = AuthenticationMode::USER_PW
    ) {
        $this->initUrl($url);

        if ((strlen($this->host) < 4)) {
            throw new ParameterValidationException(
                "Invalid call of sms.at gateway class. Hostname in wrong format: {$this->url}"
            );
        }

        if ($this->checkAuth($mode, $usernameOrAccessToken, $password)) {
            throw new ParameterValidationException(
                'Invalid call of sms.at gateway class. Check username/password or token.'
            );
        }

        $this->mode = $mode;

        // username & password authentication
        if ($this->mode === AuthenticationMode::USER_PW) {
            $this->username = $usernameOrAccessToken;
            $this->password = $password;
        } else { // access token authentication
            $this->accessToken = $usernameOrAccessToken;
        }
    }

    public function send(Message $message, ?int $maxSmsPerMessage = null): ?Response
    {
        if ($maxSmsPerMessage !== null && $maxSmsPerMessage <= 0) {
            throw new ParameterValidationException('maxSmsPerMessage cannot be less or equal to 0, try null instead.');
        }

        return $this->doRequest($message, $maxSmsPerMessage);
    }

    protected function initUrl(string $url): void
    {
        // remove trailing slashes from url
        $this->url = rtrim($url, '/');
        if (! str_starts_with($this->url, 'http')) {
            $this->url = 'https://'.$this->url;
        }

        $parsedUrl = parse_url($this->url);
        $this->host = $parsedUrl['host'] ?? '';
        $this->path = $parsedUrl['path'] ?? '';

        $this->scheme = $parsedUrl['scheme'] ?? 'https';
        $this->port = $parsedUrl['port'] ?? 443;

        if ($this->scheme === 'http') {
            $this->port = 80;
        }
    }

    protected function doRequest(Message $message, int $maxSmsPerMessage): ?Response
    {
        $client = new \GuzzleHttp\Client(
            array_merge(
                $this->guzzleOptions,
                [
                    // Base URI is used with relative requests
                    'base_uri' => "{$this->scheme}://{$this->host}{$this->endpointJson}",
                    'timeout' => $this->connectionTimeout,
                ]
            )
        );

        $headers = [
            'User-Agent' => 'PHP SDK Client with Guzzle (v'.$this->VERSION.', PHP'.phpversion().')',
        ];
        $options = [];

        if ($this->mode === AuthenticationMode::USER_PW) {
            $options['auth'] = [
                $this->username,
                $this->password,
            ];
        } else {
            // add access token in header
            $headers['Authorization'] = "Bearer {$this->accessToken}";
        }

        $data = $message->getJsonData();
        if ($maxSmsPerMessage > 0) {
            $data['maxSmsPerMessage'] = $maxSmsPerMessage;
        }

        $data['test'] = $this->testMode;

        $options[RequestOptions::JSON] = $data;
        $options[RequestOptions::HEADERS] = $headers;
        if (! $this->sslVerifyHost) {
            // Defaults to true so we need to only check for false.
            // Guzzle Docs: Disable validation entirely (don't do this!).
            $options[RequestOptions::VERIFY] = false;
        }
        if ($this->verbose) {
            $options[RequestOptions::DEBUG] = true;
        }

        $path = $message instanceof BinaryMessage ? $this->endpointBinary : $this->endpointText;
        try {
            $response = $client->post($path, $options);

            if ($response->getStatusCode() > 200) {
                throw new HttpConnectionException(
                    "Response HTTP Status: {$response->getStatusCode()}\n{$response->getBody()}",
                    $response->getStatusCode()
                );
            }

            if (! str_contains($response->getHeaderLine('Content-Type'), 'application/json')) {
                throw new UnknownResponseException(
                    "Received unknown content type '{$response->getHeaderLine('Content-Type')}'. Content: {$response->getBody()}"
                );
            }

            $apiResult = json_decode($response->getBody()->getContents(), false, 512, JSON_THROW_ON_ERROR);
            if ($apiResult->statusCode < 2000 || $apiResult->statusCode > 2001) {
                throw new ApiException($apiResult->statusMessage, $apiResult->statusCode);
            }

            return new Response($apiResult, $response);
        } catch (RequestException $e) {
            if ($e instanceof ConnectException) {
                throw new HttpConnectionException("Couldn't connect to remote server");
            }

            if ($e->getCode() === 401) {
                if ($this->mode === AuthenticationMode::ACCESS_TOKEN) {
                    throw new AuthorizationFailedException('Authentication failed. Invalid access token.');
                }
                throw new AuthorizationFailedException(
                    'Basic Authentication failed. Check given username and password. (Account has to be active)'
                );
            }

            throw new HttpConnectionException(
                "Response HTTP Status: {$e->getCode()}\n{$e->getMessage()}",
                $e->getCode()
            );
        }
    }

    public function test(bool $test = true): static
    {
        $this->testMode = $test;

        return $this;
    }

    public function getVersion(): string
    {
        return $this->VERSION;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getConnectionTimeout(): int
    {
        return $this->connectionTimeout;
    }

    public function setConnectionTimeout(int $connectionTimeout): Client
    {
        $this->connectionTimeout = $connectionTimeout;

        return $this;
    }

    public function setVerbose(bool $value): Client
    {
        $this->verbose = $value;

        return $this;
    }

    public function setSslVerifyHost(bool $value): Client
    {
        $this->sslVerifyHost = $value;

        return $this;
    }

    public function setGuzzleOptions(array $guzzleOptions): Client
    {
        $this->guzzleOptions = $guzzleOptions;

        return $this;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function getPort(): int
    {
        return $this->port;
    }

    public function getScheme(): string
    {
        return $this->scheme;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    protected function checkAuth(int $mode, string $usernameOrAccessToken, ?string $password): bool
    {
        if ($mode === AuthenticationMode::USER_PW && (empty($usernameOrAccessToken) || empty($password))) {
            return true;
        }

        return $mode === AuthenticationMode::ACCESS_TOKEN && empty($usernameOrAccessToken);
    }
}
