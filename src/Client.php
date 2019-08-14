<?php namespace WebSms;

use GuzzleHttp\RequestOptions;
use WebSms\Exception\ApiException;
use WebSms\Exception\AuthorizationFailedException;
use WebSms\Exception\HttpConnectionException;
use WebSms\Exception\ParameterValidationException;
use WebSms\Exception\UnknownResponseException;

class Client
{

    /**
     * @var string
     */
    private $VERSION = "1.0.6";
    /**
     * @var string
     */
    private $username;
    /**
     * @var string
     */
    private $accessToken;
    /**
     * @var int
     */
    private $mode;
    /**
     * @var string|null
     */
    private $password;
    /**
     * @var string
     */
    private $url;
    /**
     * @var string
     */
    private $path;
    /**
     * @var int
     */
    private $port;
    /**
     * @var string
     */
    private $scheme;
    /**
     * @var string
     */
    private $host;
    /**
     * @var bool
     */
    private $verbose = false;
    /**
     * @var int
     */
    private $connectionTimeout = 10;
    /**
     * @var string
     */
    private $endpointJson = "/json/smsmessaging/";
    /**
     * @var string
     */
    private $endpointText = "text";
    /**
     * @var string
     */
    private $endpointBinary = "binary";
    /**
     * @var bool
     */
    private $sslVerifyHost = true;


    /**
     * Client constructor.
     *
     * @param string $url
     * @param string $usernameOrAccessToken
     * @param string|null $password
     * @param int $mode
     *
     * @throws ParameterValidationException
     */
    function __construct(
        string $url,
        string $usernameOrAccessToken,
        string $password = null,
        int $mode = AuthenticationMode::USER_PW
    ) {
        $this->initUrl($url);

        if ((strlen($this->host) < 4)) {
            if ($mode === AuthenticationMode::USER_PW && (!$usernameOrAccessToken || !$password) || $mode === AuthenticationMode::ACCESS_TOKEN && !$usernameOrAccessToken) {
                throw new ParameterValidationException("Invalid call of sms.at gateway class. Check arguments.");
            }
        }
        if (!$this->port) {
            throw new ParameterValidationException("Invalid url when calling sms.at gateway class. Missing Port.");
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

    /**
     * @param string $url
     */
    private function initUrl(string $url)
    {
        // remove trailing slashes from url
        $this->url = preg_replace('/\/+$/', '', $url);

        $parsedUrl = parse_url($this->url);
        $this->host = $parsedUrl['host'] ?? '';
        $this->path = $parsedUrl['path'] ?? '';

        $this->scheme = $parsedUrl['scheme'] ?? 'http';
        $this->port = $parsedUrl['port'] ?? '';

        if (!$this->port) {
            $this->port = 80;
            if ($this->scheme == 'https') {
                $this->port = 443;
            }
        }
    }

    /**
     * @param Message $message message object of type WebSmsCom\TextMessage or BinaryMessage
     * @param int|null $maxSmsPerMessage
     * @param bool $test sms will not be sent when true
     *
     * @return Response
     *
     * @throws ApiException
     * @throws AuthorizationFailedException
     * @throws HttpConnectionException
     * @throws ParameterValidationException
     * @throws UnknownResponseException
     */
    function send(Message $message, int $maxSmsPerMessage = null, bool $test = false)
    {
        if (count($message->getRecipientAddressList()) < 1) {
            throw new ParameterValidationException("Missing recipients in message");
        }

        if (!is_null($maxSmsPerMessage) && $maxSmsPerMessage <= 0) {
            throw new ParameterValidationException("maxSmsPerMessage cannot be less or equal to 0, try null.");
        }

        return $this->doRequest($message, $maxSmsPerMessage, $test);
    }

    /**
     * @param Message $message
     * @param int $maxSmsPerMessage
     * @param bool $test
     *
     * @return Response
     *
     * @throws ApiException
     * @throws AuthorizationFailedException
     * @throws HttpConnectionException
     * @throws UnknownResponseException
     */
    private function doRequest(Message $message, int $maxSmsPerMessage, bool $test)
    {
        $client = new \GuzzleHttp\Client([
            // Base URI is used with relative requests
            'base_uri' => "{$this->scheme}://{$this->host}{$this->endpointJson}",
            'timeout' => $this->connectionTimeout,
        ]);

        $headers = [
            'User-Agent' => "PHP SDK Client with Guzzle (v" . $this->VERSION . ", PHP" . phpversion() . ")"
        ];
        $options = [];

        if ($this->mode === AuthenticationMode::USER_PW) {
            $options['auth'] = [
                $this->username,
                $this->password
            ];
        } else {
            // add access token in header
            $headers['Authorization'] = "Bearer {$this->accessToken}";
        }

        $data = $message->getJsonData();
        if ($maxSmsPerMessage > 0) {
            $data['maxSmsPerMessage'] = $maxSmsPerMessage;
        }
        if (is_bool($test)) {
            $data['test'] = $test;
        }

        $options[RequestOptions::JSON] = $data;
        $options[RequestOptions::HEADERS] = $headers;
        if (!$this->sslVerifyHost) {
            // Defaults to true so we need only check for false.
            // Guzzle Docs: Disable validation entirely (don't do this!).
            $options[RequestOptions::VERIFY] = false;
        }
        if ($this->verbose) {
            $options[RequestOptions::DEBUG] = true;
        }

        $path = $message instanceof BinaryMessage ? $this->endpointBinary : $this->endpointText;
        $response = $client->post($path, $options);

        if ($response->getStatusCode() != 200) {
            if ($response->getStatusCode() < 1) {
                throw new HttpConnectionException("Couldn't connect to remote server");
            }
            if ($response->getStatusCode() == 401) {
                if ($this->mode === AuthenticationMode::ACCESS_TOKEN) {
                    throw new AuthorizationFailedException("Authentication failed. Invalid access token.");
                }
                throw new AuthorizationFailedException("Basic Authentication failed. Check given username and password. (Account has to be active)");
            }
            throw new HttpConnectionException(
                "Response HTTP Status: {$response->getStatusCode()}\n{$response->getBody()}",
                $response->getStatusCode());
        }

        if (strpos($response->getContentType(), 'json') === false) {
            throw new UnknownResponseException(
                "Received unknown content type '{$response->getContentType()}'. Content: {$response->getBody()}"
            );
        }

        $apiResult = json_decode($response->getBody());
        if ($apiResult->statusCode < 2000 || $apiResult->statusCode > 2001) {
            throw new ApiException($apiResult->statusMessage, $apiResult->statusCode);
        }

        return new Response($apiResult, $response);
    }

    /**
     * Returns version string of this WebSmsCom client
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->VERSION;
    }

    /**
     * Returns username set at WebSmsCom client creation
     *
     * @return string
     */
    function getUsername()
    {
        return $this->username;
    }

    /**
     * Returns url set at WebSmsCom client creation
     *
     * @return string
     */
    function getUrl()
    {
        return $this->url;
    }

    /**
     * Returns timeout in seconds
     *
     * @return int
     */
    function getConnectionTimeout()
    {
        return $this->connectionTimeout;
    }

    /**
     * Set time in seoncds for curl or fopen connection
     *
     * @param int $connectionTimeout
     */
    function setConnectionTimeout(int $connectionTimeout)
    {
        $this->connectionTimeout = $connectionTimeout;
    }

    /**
     * Set verbose to see more information about request (echoes)
     *
     * @param bool $value
     */
    function setVerbose(bool $value)
    {
        $this->verbose = $value;
    }

    /**
     * Ignore ssl host security
     *
     * @param bool $value
     */
    function setSslVerifyHost(bool $value)
    {
        $this->sslVerifyHost = $value;
    }


}