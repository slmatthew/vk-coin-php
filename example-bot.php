<?php

/**
 * Пример использования lib.php в боте
 * Мне было лень расписывать все функции, здесь показан минимальный пример использования
 * 
 * Не забудьте поменять ACCESS_TOKEN, CONFIRMATION_TOKEN и SECRET_KEY (если нужно)!
 */

include './lib.php';

define("API_VERSION", "5.95");
define("ACCESS_TOKEN", "z69wphsbj9ngxc37v7rkdnshwp8hkta58hpxsa8yrr6vptggd494kawv9q76vdt6h9x5z4vkeeryr9yunhze8");
define("CONFIRMATION_TOKEN", "qHmX3KvW");
// define("SECRET_KEY", "");

function vkapi($m, $p = []) {
	if(!isset($p['lang'])) $p['lang'] = 'ru';
	if(!isset($p['access_token'])) $p['access_token'] = ACCESS_TOKEN;
	if(!isset($p['v'])) $p['v'] = API_VERSION;

	$ch = curl_init();
	curl_setopt_array($ch, [
		CURLOPT_URL => "https://api.vk.com/method/{$m}",
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_POST => true,
		CURLOPT_POSTFIELDS => $p
	]);
	$json = curl_exec($ch);
	curl_close($ch);

	return json_decode($json, true);
}

function send($peer_id, $message) {
	$p['peer_id'] = $peer_id;
	$p['message'] = $message;
	$p['random_id'] = 0;

	$r = vkapi('messages.send', $p);
	if(isset($response['error'])) return $response['error'];

	return true;
}

$data = json_decode(file_get_contents('php://input'), true);
if(!$data) { // if(!$data || $data['secret'] != SECRET_KEY)
	return;
}

switch($data['type']) {
	case 'confirmation':
		echo CONFIRMATION_TOKEN;
		break;

	case 'message_new':
		echo 'ok';

		$vkcoin = new VKCoinClient(305360617, 'cNwFTVP7Y33M5TxgZMhLQmdcNrb6qu72mNCTeRdX9PVEqbJPpe');

		$peer_id = $data['object']['peer_id'];
		$user_id = $data['object']['from_id'];

		$message = mb_strtolower($data['object']['text'], 'UTF-8');
		$message_exp = explode(' ', $message);

		switch($message_exp[0]) {
			case 'баланс':
				$balance = str_replace('.', ',', $vkcoin->getBalance([$user_id])['response'][$user_id] / 1000);
				send($peer_id, "Твой баланс: {$balance}");
				break;

			default:
				send($peer_id, "Шо? Такой команды нет!");
				break;
		}
		break;
}

?>