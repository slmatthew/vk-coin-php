<?php

/**
 * VKCoinClient
 * @author slmatthew (Matvey Vishnevsky)
 */
class VKCoinClient {

	const API_HOST = 'https://coin-without-bugs.vkforms.ru/merchant';

	private $apikey = "";
	private $merchant_id = 0;

	/**
	 * Конструктор
	 * 
	 * @param int $merchant_id ID пользователя, для которого получен платёжный ключ
	 * @param string $apikey Платёжный ключ
	 */
	public function __construct(int $merchant_id, string $apikey) {
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
			curl_setopt_array($ch, array(
				CURLOPT_URL => self::API_HOST.'/'.$method.'/',
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_POST => true,
				CURLOPT_POSTFIELDS => $body,
				CURLOPT_HTTPHEADER => array('Content-Type: application/json')
			));

			$response = curl_exec($ch);
			$err = curl_error($ch);

			curl_close($ch);

			if($err) {
				return array('status' => false, 'error' => $err);
			} else {
				$response = json_decode($response, true);
				return array('status' => true, 'response' => isset($response['response']) ? $response['response'] : $response);
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
	public function generatePayLink(int $sum, int $payload = null, bool $fixed_sum = null, bool $use_hex_link = null) {
		/** Поддержка старых версий PHP **/
		if($payload === null) {
			$payload = 0;
		}
		if($fixed_sum === null) {
			$fixed_sum = true;
		}
		if($use_hex_link === null) {
			$use_hex_link = true;
		}

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
	public function getTransactions(int $tx_type = null, int $last_tx = null) {
		/** Поддержка старых версий PHP **/
		if($tx_type === null) {
			$tx_type = 1;
		}
		if($tx_type === null) {
			$last_tx = -1;
		}

		$params = array();

		$params['merchantId'] = $this->merchant_id;
		$params['key'] = $this->apikey;
		$params['tx'] = array($tx_type);

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
	 */
	public function sendTransfer(int $to_id, int $amount) {
		$params = array();

		$params['merchantId'] = $this->merchant_id;
		$params['key'] = $this->apikey;
		$params['toId'] = $to_id;
		$params['amount'] = $amount;

		return $this->request('send', json_encode($params, JSON_UNESCAPED_UNICODE)); 
	}

	/**
	 * Получение баланса
	 * 
	 * @param array $user_ids ID пользователей
	 */
	public function getBalance(array $user_ids) {
		$params = array();

		$params['merchantId'] = $this->merchant_id;
		$params['key'] = $this->apikey;
		$params['userIds'] = $user_ids;

		return $this->request('score', json_encode($params, JSON_UNESCAPED_UNICODE));
	}
}

?>