<?php

#get and decode request
$data = json_decode(file_get_contents('php://input'), true);

#write data to log file
file_put_contents('logs.txt', print_r($data, 1) . "\n", FILE_APPEND | LOCK_EX);

#check user id
if (empty($data['message']['chat']['id'])) {
	exit();
}

#constants
define('TOKEN', '');
define('NGROK', '');

#webhook
$webHookUrl = 'https://api.telegram.org/bot' . TOKEN . '/setWebhook?url=' . NGROK;

$message = $data['message']['text'];

$params = [
	'chat_id' => $data['message']['chat']['id'],
	'text' => 'Hello World!'
];

file_get_contents('https://api.telegram.org/bot' . TOKEN . '/sendMessage?' . http_build_query($params));
