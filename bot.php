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

		###Getting UAH exchange rates with privat24 API: usd, eur, rur, btc 	
		case 'currency':
			$answer = 'Currency Buy Sell';

			#get and decode response from p24api
			$response = file_get_contents('https://api.privatbank.ua/p24api/pubinfo?json&exchange&coursid=5');
			$response = json_decode($response, true);

			#build an answer with html tags
			foreach ($response as $currencyKey => $currency) {
				$answer .= "\n" . '<b>' . $currency['ccy'] . '</b>' . ' ' . $currency['buy'] . ' ' . $currency['sale'];
			}

			$method = 'sendMessage';
			$sendData = [
				'text' => $answer,
				'parse_mode' => 'HTML'
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

#check is empty photo
if (!empty($data['message']['photo'])) {

	#pop photo info from data array
	$photo = array_pop($data['message']['photo']);

	#get basic info about file
	$res = sendTelegram(
		$method = 'getFile', 
		$sendData = [
			'file_id' => $photo['file_id']
					]
	);
	
	#get result response
	$res = json_decode($res, true);

	if ($res['ok']) {

		#saving photo to images dir
		$src = 'https://api.telegram.org/file/bot' . TOKEN . '/' . $res['result']['file_path'];
		$dir = __DIR__ . '/images/' . time() . '-' . basename($src);
 
 		#answer to user photo saved
		if (copy($src, $dir)) {
			sendTelegram(
				$method = 'sendMessage', 
				$sendData = [
					'chat_id' => $data['message']['chat']['id'],
					'text' => 'Photo saved'
							]
			);
		}
	}

	exit();	
}

#check is empty file
if (!empty($data['message']['document'])) {

	#get basic info about file
	$res = sendTelegram(
		$method = 'getFile', 
		$sendData = [
			'file_id' => $data['message']['document']['file_id']
					]
	);
	
	#get result response
	$res = json_decode($res, true);

	if ($res['ok']) {

		#saving file to files dir
		$src = 'https://api.telegram.org/file/bot' . TOKEN . '/' . $res['result']['file_path'];
		$dest = __DIR__ . '/files/' . time() . '-' . $data['message']['document']['file_name'];
 
 		#answer to user file saved
		if (copy($src, $dest)) {
			sendTelegram(
				$method = 'sendMessage', 
				$sendData = [
					'chat_id' => $data['message']['chat']['id'],
					'text' => 'File saved'
							]
			);	
		}
	}
	
	exit();	
}