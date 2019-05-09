<?php

/**
 * VKCoinClient
 * @author slmatthew (Matvey Vishnevsky)
 * @version 1.2
 */
class VKCoinClient {

	protected const API_HOST = 'https://coin-without-bugs.vkforms.ru/merchant';

	private $apikey = "";
	private $merchant_id = 0;

	/**
	 * Конструктор
	 * 
	 * @param int $merchant_id ID пользователя, для которого получен платёжный ключ
	 * @param string $apikey Платёжный ключ
	 */
	public function __construct(int $merchant_id, string $apikey) {
		if(version_compare('7.0.0', phpversion()) === 1) {
			die('Ваша версия не поддерживает эту версию библиотеки, используйте lib-5.6.php');
		}

		$this->merchant_id = $merchant_id;
		$this->apikey = $apikey;
	}

	/**
	 * Функция request, используется для запросов к API
	 * 
	 * @param string $method
	 * @param string $body
	 * @return array | bool
	 */
	private function request(string $method, string $body) {
		if(extension_loaded('curl')) {
			$ch = curl_init();
			curl_setopt_array($ch, [
				CURLOPT_URL => self::API_HOST.'/'.$method.'/',
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_POST => true,
				CURLOPT_POSTFIELDS => $body,
				CURLOPT_HTTPHEADER => ['Content-Type: application/json']
			]);

			$response = curl_exec($ch);
			$err = curl_error($ch);

			curl_close($ch);

			if($err) {
				return ['status' => false, 'error' => $err];
			} else {
				$response = json_decode($response, true);
				return ['status' => true, 'response' => isset($response['response']) ? $response['response'] : $response];
			}
		}

		return false;
	}

	/**
	 * Получение ссылки на оплату
	 * 
	 * @param int $sum Сумма перевода
	 * @param int $payload Полезная нагрузка. Если равно нулю, то будет сгенерировано рандомное число
	 * @param bool $fixed_sum Фиксированная сумма, по умолчанию true
	 * @param bool $use_hex_link Генерировать ссылку с hex-параметрами или нет
	 * @return string
	 */
	public function generatePayLink(int $sum, int $payload = 0, bool $fixed_sum = true, bool $use_hex_link = true) {
		$payload = $payload == 0 ? rand(-2000000000, 2000000000) : $payload;

		if($use_hex_link) {
			$merchant_id = dechex($this->merchant_id);
			$sum = dechex($sum);
			$payload = dechex($payload);

			$link = "vk.com/coin#m{$merchant_id}_{$sum}_{$payload}".($fixed_sum ? "" : "_1");
		} else {
			$merchant_id = $this->merchant_id;

			$link = "vk.com/coin#x{$merchant_id}_{$sum}_{$payload}".($fixed_sum ? "" : "_1");
		}

		return $link;
	}

	/**
	 * Получение списка транзакций
	 * 
	 * @param int $tx_type Документация: https://vk.com/@hs-marchant-api?anchor=poluchenie-spiska-tranzaktsy
	 * @param int $last_tx Номер последней транзакции, всё описано в документации. По умолчанию не включён в запрос
	 */
	public function getTransactions(int $tx_type = 1, int $last_tx = -1) {
		$params = [];

		$params['merchantId'] = $this->merchant_id;
		$params['key'] = $this->apikey;
		$params['tx'] = [$tx_type];

		if($last_tx != -1) {
			$params['lastTx'] = $last_tx;
		}

		return $this->request('tx', json_encode($params, JSON_UNESCAPED_UNICODE));
	}

	/**
	 * Перевод
	 * 
	 * @param int $to_id ID пользователя, которому будет отправлен перевод
	 * @param int $amount Сумма перевода в тысячных долях (если укажите 15, то придёт 0,015 коина)
	 * @param bool $mark_as_merchant Отправить перевод от имени магазина или нет
	 */
	public function sendTransfer(int $to_id, int $amount, bool $mark_as_merchant = true) {
		$params = [];

		$params['merchantId'] = $this->merchant_id;
		$params['key'] = $this->apikey;
		$params['toId'] = $to_id;
		$params['amount'] = $amount;
		$params['markAsMerchant'] = $mark_as_merchant;

		return $this->request('send', json_encode($params, JSON_UNESCAPED_UNICODE)); 
	}

	/**
	 * Получение баланса
	 * 
	 * @param array $user_ids ID пользователей
	 */
	public function getBalance(array $user_ids = []) {
		if(empty($user_ids)) {
			$user_ids = [$this->merchant_id];
		}

		$params = [];

		$params['merchantId'] = $this->merchant_id;
		$params['key'] = $this->apikey;
		$params['userIds'] = $user_ids;

		return $this->request('score', json_encode($params, JSON_UNESCAPED_UNICODE));
	}

	/**
	 * Изменение названия магазина
	 * 
	 * @param string $name Название магазина
	 */
	public function changeName(string $name) {
		$params = [];

		$params['name'] = $name;
		$params['merchantId'] = $this->merchant_id;
		$params['key'] = $this->apikey;

		return $this->request('set', json_encode($params, JSON_UNESCAPED_UNICODE));
	}

	/**
	 * Добавление Callback API сервера
	 * 
	 * @param string $url Адрес
	 */
	public function addWebhook(string $url) {
		$params = [];

		$params['callback'] = $url;
		$params['merchantId'] = $this->merchant_id;
		$params['key'] = $this->apikey;

		return $this->request('set', json_encode($params, JSON_UNESCAPED_UNICODE));
	}

	/**
	 * Удаление Callback API сервера
	 */
	public function deleteWebhook() {
		$params = [];

		$params['callback'] = null;
		$params['merchantId'] = $this->merchant_id;
		$params['key'] = $this->apikey;

		return $this->request('set', json_encode($params, JSON_UNESCAPED_UNICODE));
	}

	/**
	 * Получение логов неудачных запросов
	 */
	public function getWebhookLogs() {
		$params = [];

		$params['status'] = 1;
		$params['merchantId'] = $this->merchant_id;
		$params['key'] = $this->apikey;

		return $this->request('set', json_encode($params, JSON_UNESCAPED_UNICODE));
	}

	/**
	 * Проверка подлинности ключа
	 * 
	 * @param array|object $params Данные запроса, декодированные через json_decode(file_get_contents('php://input'), true) или json_decode(file_get_contents('php://input'))
	 */
	public function isKeyValid($params) {
		if(is_array($params) || is_object($params)) {
			$p = [];
			if(is_object($params)) { // если объект, то превращаем его в ассоциативный массив
				foreach($params as $key => $value) {
					$p[$key] = $value;
				}
			} else {
				$p = $params;
			}

			if(isset($p['id'], $p['from_id'], $p['amount'], $p['payload'], $p['key'])) {
				$key = md5(implode(';', [$p['id'], $p['from_id'], $p['amount'], $p['payload'], $this->apikey]));
				return $key === $p['key'];
			}
		}

		return false;
	}
}

?>