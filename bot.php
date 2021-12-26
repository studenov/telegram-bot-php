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

#send answer within method and data
function sendTelegram($method, $response)
{

	#create a new cURL resource
	$ch = curl_init('https://api.telegram.org/bot' . TOKEN . '/' . $method);  

	#set other appropriate options
	curl_setopt($ch, CURLOPT_POST, 1);  
	curl_setopt($ch, CURLOPT_POSTFIELDS, $response);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HEADER, false);

	#grab URL and pass it
	$res = curl_exec($ch);

	#close cURL resource, and free up system resources
	curl_close($ch);
 
	return $res;
}

#check is empty message
if (!empty($data['message']['text'])) {

	$text = $data['message']['text'];

	switch ($text) {

		case 'hello':
			$method = 'sendMessage';
			$sendData = [
				'text' => 'Hi, ' . $data['message']['chat']['first_name'] . '!'
						];
			break;

		case 'photo':
			$method = 'sendPhoto';
			$sendData = [
				'photo' => curl_file_create(__DIR__ . '/images/' . 'cat.jpg')
						];
			break;

		case 'file':
			$method = 'sendDocument';
			$sendData = [
				'document' => curl_file_create(__DIR__ . '/files/' . 'example.txt')
						];
			break;

		default:
			$method = 'sendMessage';
			$sendData = [
				'text' => 'Repeat, please'
						];
			break;
	}

	$sendData['chat_id'] = $data['message']['chat']['id'];

	sendTelegram($method, $sendData);

}
