<?php
function curl($url, $post = '', $headers = array()) {
	if ($curl = curl_init()) {
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		$out = curl_exec($curl);
		curl_close($curl);
		return $out;
	}
}
function yandex($file)
{
	global $api_key;
	$lang_model = 'queries';
	$lang = 'ru-RU';
	$uuid = md5(time());
	return curl(
		'https://asr.yandex.net/asr_xml?uuid=' . $uuid . '&key=' . $api_key . '&topic=' . $lang_model . '&lang=' . $lang,
		file_get_contents($file),
		array(
			'Content-Type: audio/ogg;codecs=opus',
			'Transfer-Encoding: chunked'
		)
	);
}
function vkApi($method, $param = null, $post = null)
{
	global $vkToken;
	$param['access_token'] = $vkToken;
	$url = 'https://api.vk.com/method/' . $method;
	return json_decode(curl($url, $param), 1);
}
function sendMessage($to, $text) {
	return vkApi('messages.send', array(
		'user_id' => $to,
		'message' => $text
	));
}
$api_key = '';
$vkToken = '';
$vkConfirmationKey = '';
$data = json_decode(file_get_contents('php://input'), true);
switch ($data['type']) {
	case 'confirmation':
	    echo $vkConfirmationKey;
		break;
	case 'message_new':
		$link = $data['object']['attachments'][0]['doc']['preview']['audio_msg']['link_ogg'];
		$duration = $data['object']['attachments'][0]['doc']['preview']['audio_msg']['duration'];
		if ($link) {
			if ($duration > 20){
				sendMessage($data['object']['user_id'], 'Голосовое сообщение слишком длинное. Попробуй до 20 секунд :(');
			}else {
				$text = '';
				$max = '';
				$yandex = yandex($link);
				$xml = simplexml_load_string($yandex);
				foreach($xml->variant as $variant) {
					if (strval($variant->attributes()->confidence) > $max) {
						$max = $variant->attributes();
						$text = $variant;
					}
				}
				if ($text){
					sendMessage($data['object']['user_id'], $text);
				}else{
					sendMessage($data['object']['user_id'], 'Голосовое сообщение не распознано :(');
				}
			}
		}else{
			sendMessage($data['object']['user_id'], 'Пришли мне голосовое сообщение, а я его распознаю!<br><br>С любовью, Никита :)');
		}
		echo('ok');
		break;
}