<?php

use GuzzleHttp\Client;
use Dotenv\Dotenv;
use Symfony\Component\HttpFoundation\Request;

require __DIR__.'/../vendor/autoload.php';

if (file_exists(__DIR__.'/../.env')) {
    Dotenv::create(__DIR__ . '/../')->load();
} else {
    file_put_contents('log.txt', 'no .env found');
}

$httpClient = new Client();

$request = Request::createFromGlobals();
$payloadString = $request->getContent();

file_put_contents('receive.txt', $payloadString);

$parsed = [];
parse_str($payloadString, $parsed);
$payload = json_decode($parsed['payload'], true);

file_put_contents('receive_parse.txt', var_export($payload, true));


if ($payload['type'] === 'message_action') {
    $dialogData = [
        'trigger_id' => $payload['trigger_id'],
        'dialog' => json_encode([
            'callback_id' => 'div_development_create_lisket_support_issue',
            'title' => 'サポートissue化',
            'submit_label' => 'issue作成',
            'state' => 'Limo',
            'elements' => [
                [
                    'type' => 'text',
                    'label' => 'issueタイトル',
                    'name' => 'issue_title',
                ],
            ],
        ]),
    ];

    // open dialog
    $response = $httpClient->post('https://slack.com/api/dialog.open', [
        'body' => json_encode($dialogData),
        'headers' => [
            'content-type' => 'application/json; charset=utf-8',
            'authorization' => 'Bearer '.getenv('SLACK_OAUTH_TOKEN'),
        ],
    ]);
    file_put_contents('response.txt', $response->getBody()->getContents());
} elseif ($payload['type'] === 'dialog_submission') {
   // gather info to create issue
    $submission = $payload['submission'];
    $messageBody = $payload['message']['text'];
    list($ts1, $ts2) = explode('.', $payload['message_ts']);
    $url = sprintf('https://quartetcom.slack.com/archives/%s/p%s%s', $payload['channel']['id'], $ts1, $ts2);
    // generate title & desc
    $title = $submission['issue_title'];
    $description = implode("\n", [$messageBody, $url]);
    // post to github
}

