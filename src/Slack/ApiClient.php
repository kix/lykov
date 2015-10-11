<?php
/**
 * Created by PhpStorm.
 * User: kix
 * Date: 02/10/15
 * Time: 15:16
 */

namespace Lykov\Slack;

use Guzzle\Http\Client;
use Guzzle\Http\Message\Request;

class ApiClient
{
    const API_BASE_URL = 'https://slack.com/api/';

    public function __construct($token)
    {
        $client = new Client();
        $request = new Request(
            'POST',
            self::API_BASE_URL . 'rtm.start?' . http_build_query(['token' => $token]),
            ['Content-Type' => 'application/x-www-form-urlencoded']
        );

        $response = $client->send($request);
        var_dump(json_decode($response->getBody(true)));
    }
}