# What is it?
A lightweight PHP-client-library for using websms.com SMS services.
Reduces the complexity of network-communication between client and SMS gateway, 
to help business-customer save time and money for focusing on their business logic.

# Why this fork
Rewrote complete SDK to meet the newest coding standards and utilize modern libraries.

Added the following improvements:
* Raised minimum required PHP version to 8.0+ (For PHP <8.0 use v1.0.7.7)
* Use Namespaces
* Switched from kebab-case to CamelCase for properties and methods
* Used famous and well tested GuzzleHttp library instead of plain Curl or fsockopen
* Added PHPDoc style comments
* Used parameter type hints in methods to ensure certain var types and removed unnecessary type checking
* Removed JSON.phps dependency and used native php json extension instead
* Raised client version to that one mentioned in the original readme file

But most of all, I did it for fun :) 

# Installation

composer require hebinet/websmscom-php

# Example

```php
// First create the WebSms client

// via username/password
$client = new WebSms\Client($gatewayUrl, $username, $password);
// or via TokenAuth
$client = new WebSms\Client($gatewayUrl, $authToken, null, AuthenticationMode::ACCESS_TOKEN);


// Then create an Message object with the recipients and the message
$message = new WebSms\TextMessage(['4366412345678'], 'Test Message');

// To send the message, just call the send method on the client
$response = $client->send($message);
```

and here the content of the original send_sms.php file "translated" to the new classes

```php
<?php
use WebSms\AuthenticationMode;
use WebSms\BinaryMessage;
use WebSms\Client;
use WebSms\Exception\ApiException;
use WebSms\Exception\AuthorizationFailedException;
use WebSms\Exception\HttpConnectionException;
use WebSms\Exception\ParameterValidationException;
use WebSms\Exception\UnknownResponseException;
use WebSms\TextMessage;

# Modify these values to your needs
$username = 'your_username';
$password = 'your_password';
// OR (optional)
$accessToken = 'your_access_token';
$gatewayUrl = 'https://api.websms.com/';

$recipientAddressList = array("4367612345678");
$utf8MessageText = "Willkommen zur BusinessPlatform SDK von websms.com! Diese Nachricht enthält 160 Zeichen. Sonderzeichen: äöüß. Eurozeichen: €. Das Ende wird nun ausgezählt43210";

$maxSmsPerMessage = 1;
$test = false; // true: do not send sms for real, just test interface

try {

    // 1.) -- create sms client (once) ------
    $smsClient = new Client($gatewayUrl, $username, $password);

    // 1.) -- Alternatively authenticate over access token
    // $smsClient = new Client($gateway_url, $accessToken, null, AuthenticationMode::ACCESS_TOKEN);

    //$smsClient->setVerbose(true);
    //$smsClient->setSslVerifyHost(false); // needed if you want to disable the SSL check completely. (Default: true)

    // 2.) -- create text message ----------------
    $message = new TextMessage($recipientAddressList, $utf8MessageText);
    //$message = binarySmsSample($recipientAddressList);
    //$maxSmsPerMessage = null;  //needed if binary messages should be send

    // 3.) -- send message ------------------
    $response = $smsClient->send($message, $maxSmsPerMessage, $test);
    // $response is now a class with all the api specific methods and maps all other methods magically to the Guzzle Response object
    

    // show success
    print_r([
        "Status          : " . $response->getApiStatusCode(),
        "StatusMessage   : " . $response->getApiStatusMessage(),
        "TransferId      : " . $response->getTransferId(),
        "ClientMessageId : " . (($response->getClientMessageId()) ?
            $response->getClientMessageId() : '<NOT SET>'),
    ]);

    // catch everything that's not a successfully sent message
} catch (ParameterValidationException $e) {
    exit("ParameterValidationException caught: " . $e->getMessage() . "\n");

} catch (AuthorizationFailedException $e) {
    exit("AuthorizationFailedException caught: " . $e->getMessage() . "\n");

} catch (ApiException $e) {
    echo $e; // possibility to handle API status codes $e->getCode()
    exit("ApiException Exception\n");

} catch (HttpConnectionException $e) {
    exit("HttpConnectionException caught: " . $e->getMessage() . "HTTP Status: " . $e->getCode() . "\n");

} catch (UnknownResponseException $e) {
    exit("UnknownResponseException caught: " . $e->getMessage() . "\n");

} catch (Exception $e) {
    exit("Exception caught: " . $e->getMessage() . "\n");
}

//-- end of main

//-----------------------------------------------
// binary_message_content
//-----------------------------------------------
function binarySmsSample($recipientAddressList)
{

    //| Working messageContent sample of PDU sms containing content "Zusammengefügt."
    //| sent as 2 SMS segments: ("Zusammen","gefügt.").
    //| First 6 Bytes per segment are sample UDH. See http://en.wikipedia.org/wiki/Concatenated_SMS

    //$messageContentSegments = array(
    //    "BQAD/AIBWnVzYW1tZW4=", // 0x05,0x00,0x03,0xfc,0x02,0x01, 0x5a,0x75,0x73,0x61,0x6d,0x6d,0x65,0x6e
    //    "BQAD/AICZ2Vmw7xndC4="  // 0x05,0x00,0x03,0xfc,0x02,0x02, 0x67,0x65,0x66,0xc3,0xbc,0x67,0x74,0x2e
    //);

    // bytewise rebuilt sample. (identical to above):
    $messageContentSegments = [
        base64_encode(pack("c*", 0x05, 0x00, 0x03, 0xfc, 0x02, 0x01, 0x5a, 0x75, 0x73, 0x61, 0x6d, 0x6d, 0x65, 0x6e)),
        base64_encode(pack("c*", 0x05, 0x00, 0x03, 0xfc, 0x02, 0x02, 0x67, 0x65, 0x66, 0xc3, 0xbc, 0x67, 0x74, 0x2e))
    ];
    $userDataHeaderPresent = true; # Binary Data includes UserDataHeader for e.G.: PDU sms (Concatenation)

    $message = new BinaryMessage($recipientAddressList, $messageContentSegments, $userDataHeaderPresent);

    return $message;
}

```

# Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

# License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
