<?php
/**
 * Created by PhpStorm.
 * User: Назым
 * Date: 15.04.2019
 * Time: 18:08
 */

namespace VkCoin;

/**
 * VKCoinClient
 * @author slmatthew (Matvey Vishnevsky)
 */
class VKCoinClient
{

    const API_HOST = 'coin-without-bugs.vkforms.ru/merchant';
    private $apikey = "";
    private $merchant_id = 0;
    private $language;

    /**
     * Конструктор
     *
     * @param int $merchant_id ID пользователя, для которого получен платёжный ключ
     * @param string $apikey Платёжный ключ
     * @throws \Exception
     */
    public function __construct($merchant_id, $apikey)
    {
        $this->merchant_id = $merchant_id;
        $this->apikey = $apikey;
        $this->language = include 'language.php';
    }

    /**
     * Получение ссылки на оплату
     *
     * @param int $sum Сумма перевода
     * @param int $payload Полезная нагрузка. Если равно нулю, то будет сгенерировано рандомное число
     * @param bool $fixed_sum Фиксированная сумма, по умолчанию true
     * @param bool $use_hex_link Генерировать ссылку с hex-параметрами или нет
     * @return string
     * @throws \Exception
     */
    public function getPayLink($sum = 0, $payload = 0, $fixed_sum = true, $use_hex_link = true)
    {
        $payload = $payload == 0 ? rand(-2000000000, 2000000000) : $payload;
        if($sum) {
            if ($use_hex_link) {
                $merchant_id = dechex($this->merchant_id);
                $sum = dechex($sum);
                $payload = dechex($payload);

                $link = sprintf('vk.com/coin#m%s_%s_%s', $merchant_id, $sum, $payload) . ($fixed_sum ? "" : "_1");
            } else {
                $merchant_id = $this->merchant_id;

                $link = sprintf('vk.com/coin#x%s_%s_%s', $merchant_id, $sum, $payload) . ($fixed_sum ? "" : "_1");
            }
        } else {
            //TODO: возможно работает)
            $merchant_id = $this->merchant_id;
            $link = sprintf('vk.com/coin#t%s', $merchant_id);
        }
        return $link;
    }
    /**
     * Получение списка транзакций
     *
     * @param int $tx_type Документация: https://vk.com/@hs-marchant-api?anchor=poluchenie-spiska-tranzaktsy
     * @param int $last_tx Номер последней транзакции, всё описано в документации. По умолчанию не включён в запрос
     * @return array|bool
     * @throws \Exception
     */
    public function getTransactions($tx_type = 1, $last_tx = -1)
    {
        $params = [];

        $params['merchantId'] = $this->merchant_id;
        $params['key'] = $this->apikey;
        $params['tx'] = [$tx_type];

        if ($last_tx != -1) {
            $params['lastTx'] = $last_tx;
        }

        return $this->request('tx', json_encode($params, JSON_UNESCAPED_UNICODE));
    }

    /**
     * Функция request, используется для запросов к API
     * @param string $method
     * @param string $body
     * @return array
     * @throws \Exception
     */
    private function request($method, $body)
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => sprintf('https://%s/%s/', self::API_HOST, $method),
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
        //Разбираем данные
        $response = json_decode($response, true);

        $return = ['status' => true];
        if ($err) {
            $return['status'] = false;
            $return['error']['code'] = 100;
            $return['error']['message'] = $err;
            return $return;
        } else {
            if (isset($response['error'])) {
                $return['status'] = false;
                $return['error'] = $response['error'];
            } else {
                $return['response'] = $response['response'];
            }
            //Ловим ошибки и получаем ответ
            return $this->getResponse($return);
        }
    }

    /**
     * @param $response
     * @return array
     * @throws \Exception
     */
    private function getResponse($response)
    {
        if (is_array($response)) {
            if ($response['status'] == false && isset($response['error'])) {
              //  var_dump($response);//Галя, харе тестит чебуреки там на кухне!
                switch ($response['error']['code']) {
                    case 422:
                        $error = $this->language['responseParamError'];
                        break;
                    case 100:
                        $error = $response['error']['message'];
                        break;
                    default:
                        $error = $this->language['responseParamError'];
                        break;
                }
                throw new \Exception($error);
            }
            return $response;
        } else  throw new \Exception($this->language['fatal']);
    }

    /**
     * Перевод
     *
     * @param int $to_id ID пользователя, которому будет отправлен перевод
     * @param int $amount Сумма перевода в тысячных долях (если укажите 15, то придёт 0,015 коина)
     * @return array|bool
     * @throws \Exception
     */
    public function sendTransfer($to_id, $amount)
    {
        //Не обязательно.
        //   $this->variableCheck($to_id, 'int')->variableCheck($amount, 'int');
        $params = [];

        $params['merchantId'] = $this->merchant_id;
        $params['key'] = $this->apikey;
        $params['toId'] = $to_id;
        $params['amount'] = $amount;

        return $this->request('send', json_encode($params, JSON_UNESCAPED_UNICODE));
    }
}
