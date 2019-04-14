<?php

/**
 * VKCoinClient
 * @author slmatthew (Matvey Vishnevsky)
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
		$this->merchant_id = $merchant_id;
		$this->apikey = $apikey;
	}

	/**
	 * Функция request, используется для запросов к API
	 */
	private function request(string $method, string $body) {
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
			return array('status' => true, 'response' => json_decode($response, true));
		}

		return false;
	}

	/**
	 * Получение ссылки на оплату
	 * 
	 * @param int $sum Сумма перевода
	 * @param int $payload Полезная нагрузка. Если равно нулю, то будет сгенерировано рандомное число
	 * @param bool $fixed_sum Фиксированная сумма, по умолчанию true
	 */
	public function generatePayLink(int $sum, int $payload = 0, bool $fixed_sum = true) {
		$payload = $payload == 0 ? rand(-2000000000, 2000000000) : $payload;

		$merchant_id = dechex($this->merchant_id);
		$sum = dechex($sum);
		$payload = dechex($payload);

		$link = "vk.com/coin#m{$merchant_id}_{$sum}_{$payload}".($fixed_sum ? "" : "_1");

		return $link;
	}

	/**
	 * Получение списка транзакций
	 * 
	 * @param int $tx_type Документация: https://vk.com/@hs-marchant-api?anchor=poluchenie-spiska-tranzaktsy
	 * @param int $last_tx Номер последней транзакции, всё описано в документации. По умолчанию не включён в запрос
	 */
	public function getTransactions(int $tx_type = 1, int $last_tx = -1) {
		$params = array();

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
	 */
	public function sendTransfer(int $to_id, int $amount) {
		$params = array();

		$params['merchantId'] = $this->merchant_id;
		$params['key'] = $this->apikey;
		$params['toId'] = $to_id;
		$params['amount'] = $amount;

		return $this->request('send', json_encode($params, JSON_UNESCAPED_UNICODE)); 
	}
}

?>