<?php
/**
 * Created by PhpStorm.
 * User: Назым
 * Date: 15.04.2019
 * Time: 18:08
 */

use VkCoin\VKCoinClient;

define("VK_COIN_ENDPOINT", "https://coin-without-bugs.vkforms.ru/merchant/");

include "../vendor/autoload.php";

try {
    $vkcoin = new VKCoinClient(211984675, 'а_тут_сверх_секретный_ключ');

    var_dump('getTransactions', $vkcoin->getTransactions());
    var_dump('getTransactions 2', $vkcoin->getTransactions(2));
    var_dump('getTransactions 1, 200', $vkcoin->getTransactions(1, 200));
    var_dump('sendTransfer 211984675, 15000', $vkcoin->sendTransfer(211984675, 15000));//305360617

    var_dump($vkcoin->getPayLink()); // вернет ссылку в виде vk.com/coin#t{merchantId}
    var_dump($vkcoin->getPayLink(15000));
    var_dump($vkcoin->getPayLink(15000, 123456));
    var_dump($vkcoin->getPayLink(15000, 0, false));

} catch (Exception $e) {
    echo $e;
}