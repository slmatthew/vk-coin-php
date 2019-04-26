<?php
/**
 * VKCoinClient (for old PHP versions)
 * @author slmatthew (Matvey Vishnevsky)
 * @version 1.1
 */
class VKCoinClient
{
    const API_HOST = 'https://coin-without-bugs.vkforms.ru/merchant';
    private $apikey = "";
    private $merchant_id = 0;

    /**
     * Конструктор
     *
     * @param int $merchant_id ID пользователя, для которого получен платёжный ключ
     * @param string $apikey Платёжный ключ
     */
    public function __construct($merchant_id, $apikey)
    {
        $this->merchant_id = $merchant_id;
        $this->apikey = $apikey;
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
    public function generatePayLink($sum, $payload = null, $fixed_sum = null, $use_hex_link = null)
    {
        /** Поддержка старых версий PHP **/
        if ($payload === null) {
            $payload = 0;
        }
        if ($fixed_sum === null) {
            $fixed_sum = true;
        }
        if ($use_hex_link === null) {
            $use_hex_link = true;
        }
        $payload = $payload == 0 ? rand(-2000000000, 2000000000) : $payload;
        if ($use_hex_link) {
            $merchant_id = dechex($this->merchant_id);
            $sum = dechex($sum);
            $payload = dechex($payload);
            $link = "vk.com/coin#m{$merchant_id}_{$sum}_{$payload}" . ($fixed_sum ? "" : "_1");
        } else {
            $merchant_id = $this->merchant_id;
            $link = "vk.com/coin#x{$merchant_id}_{$sum}_{$payload}" . ($fixed_sum ? "" : "_1");
        }
        return $link;
    }

    /**
     * Получение списка транзакций
     *
     * @param int $tx_type Документация: https://vk.com/@hs-marchant-api?anchor=poluchenie-spiska-tranzaktsy
     * @param int $last_tx Номер последней транзакции, всё описано в документации. По умолчанию не включён в запрос
     * @return array|bool
     */
    public function getTransactions($tx_type = null, $last_tx = null)
    {
        /** Поддержка старых версий PHP **/
        if ($tx_type === null) {
            $tx_type = 1;
        }
        if ($last_tx === null) {
            $last_tx = -1;
        }
        $params = array();
        $params['tx'] = array($tx_type);
        if ($last_tx != -1) {
            $params['lastTx'] = $last_tx;
        }
        return $this->request('tx', $params);
    }

    /**
     * @param $method
     * @param $body
     * @return array|bool
     */
    public function request($method, $body)
    {
        $body['merchantId'] = $this->merchant_id;
        $body['key'] = $this->apikey;

        return $this->_request($method, $body);
    }

    /**
     * Функция request, используется для запросов к API
     *
     * @param string $method
     * @param string $body
     * @return array | bool
     */
    private function _request($method, $body)
    {
        if (extension_loaded('curl')) {
            $ch = curl_init();
            curl_setopt_array($ch, array(
                CURLOPT_URL => self::API_HOST . '/' . $method . '/',
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($body, JSON_UNESCAPED_UNICODE),
                CURLOPT_HTTPHEADER => array('Content-Type: application/json')
            ));
            $response = curl_exec($ch);
            $err = curl_error($ch);
            curl_close($ch);
            if ($err) {
                return array('status' => false, 'error' => $err);
            } else {
                $response = json_decode($response, true);
                return array('status' => true, 'response' => isset($response['response']) ? $response['response'] : $response);
            }
        }
        return false;
    }

    /**
     * Перевод
     *
     * @param int $to_id ID пользователя, которому будет отправлен перевод
     * @param int $amount Сумма перевода в тысячных долях (если укажите 15, то придёт 0,015 коина)
     * @return array|bool
     */
    public function sendTransfer($to_id, $amount)
    {
        $params = array();
        $params['toId'] = $to_id;
        $params['amount'] = $amount;

        return $this->request('send', $params);
    }

    /**
     * Получение баланса
     *
     * @param array $user_ids ID пользователей
     * @return array|bool
     */
    public function getBalance(array $user_ids = null)
    {
        if ($user_ids === null) {
            $user_ids = [$this->merchant_id];
        }
        $params = array();
        $params['userIds'] = $user_ids;

        return $this->request('score', $params);
    }

    /**
     * Изменение названия магазина
     *
     * @param string $name Название магазина
     * @return array|bool
     */
    public function changeName($name)
    {
        $params = array();
        $params['name'] = $name;

        return $this->request('set', $params);
    }

    /**
     * Добавление Callback API сервера
     *
     * @param string $url Адрес
     * @return array|bool
     */
    public function addWebhook($url)
    {
        $params = array();
        $params['callback'] = $url;

        return $this->request('set', $params);
    }

    /**
     * Удаление Callback API сервера
     */
    public function deleteWebhook()
    {
        $params = array();
        $params['callback'] = null;

        return $this->request('set', $params);
    }

    /**
     * Получение логов неудачных запросов
     */
    public function getWebhookLogs()
    {
        $params = array();
        $params['status'] = 1;

        return $this->request('set', $params);
    }

    /**
     * Проверка подлинности ключа
     *
     * @param array $params Данные запроса, декодированные через json_decode(file_get_contents('php://input'), true)
     * @return bool
     */
    public function isKeyValid(array $params)
    {
        if (isset($params['id']) && isset($params['from_id']) && isset($params['amount']) && isset($params['payload']) && isset($params['key'])) {
            $key = md5(implode(';', array($params['id'], $params['from_id'], $params['amount'], $params['payload'], $this->apikey)));
            return $key === $params['key'];
        }
        return false;
    }
}
