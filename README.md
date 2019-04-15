# VK Coin PHP
Библиотека для работы с VK Coin API.

# Правки от @nazbav

1. Снижен PHP уровень языка с 7.* до 5.4 (рекомендуется 5.6), для старых\ограниченных хостингов.
1. Внесены мелкие коррективы в код
1. Добавлены обработчик ошибок.
1. Добавлен языковой файл.
1. Настроен Composer.
1. generatePayLink -> getPayLink
1. Упрощена (усложнена) логика при вызове request() внутри методов.
1. Результат вывода функций изменен

## Формат ответа

При вызове функции `getTransactions()` или `sendTransfer()` возвращается массив с двумя полями, либо false.

| Имя поля     | Тип    |  Описание                                                                          |
|--------------|--------|------------------------------------------------------------------------------------|
| status       | bool   | `true`, если запрос успешен. `false`, если произошла ошибка                        |
| response     | array  | **Возвращается только если `status` == `true`.** Массив, содержащий ответ API.     |
| error        | string | **Возвращается только если `status` == `false`.** Строка, описывающая ошибку CURL. |

Если что-то пошло не так, вернётся значение `false`. Проверить можно так:
```php
$result = $vkcoin->getTransactions();
if($result['status']) {
	// запрос выполнен успешно
} else {
	// обработка ошибки
}
```

## Инициализация

Пример:
```php
include './lib.php';

$vkcoin = new VKCoinClient(305360617, 'cNwFTVP7Y33M5TxgZMhLQmdcNrb6qu72mNCTeRdX9PVEqbJPpe');
```

| Параметр     | Тип    | Обязательный?     | Описание                                             |
|--------------|--------|-------------------|------------------------------------------------------|
| merchant_id  | int    | **yes**           | ID странички, для которой был получен платёжный ключ |
| apikey       | string | **yes**           | Платёжный ключ                                       |

## Получение списка транзакций

Пример:
```php
$vkcoin->getTransactions();
$vkcoin->getTransactions(2);
$vkcoin->getTransactions(1, 200);
```

| Параметр     | Тип    | Обязательный? | Описание                                                                                      |
|--------------|--------|---------------|-----------------------------------------------------------------------------------------------|
| tx_type      | int    | no            | Описано в [документации](https://vk.com/@hs-marchant-api?anchor=poluchenie-spiska-tranzaktsy) |
| last_tx      | int    | no            | Номер последней транзакции                                                                    |

## Перевод

Пример:
```php
$vkcoin->sendTransfer($to_id, 15000);
```

| Параметр     | Тип    | Обязательный?     | Описание                                                                             |
|--------------|--------|-------------------|--------------------------------------------------------------------------------------|
| to_id        | int    | **yes**           | ID пользователя, которому будет отправлен перевод                                    |
| amount       | int    | **yes**           | Сумма перевода в тысячных долях _(если указать 15, то будет отправлено 0,015 коина)_ |

## Получение ссылки на оплату

Пример:
```php
$vkcoin->getPayLink(); // вернет ссылку в виде vk.com/coin#t{merchantId}
$vkcoin->getPayLink(15000);
$vkcoin->getPayLink(15000, 123456);
$vkcoin->getPayLink(15000, 0, false);
```

| Параметр     | Тип    | Обязательный?   | Описание                                                                                                             |
|--------------|--------|-----------------|----------------------------------------------------------------------------------------------------------------------|
| sum          | int    | **yes**         | Сумма перевода                                                                                                       |
| payload      | int    | no              | полезная нагрузка, любое число от -2000000000 до 2000000000. Если равно нулю, то будет сгенерировано рандомное число |
| fixed_sum    | bool   | no              | Сумма фиксирована или нет? [Документация](https://vk.com/@hs-marchant-api?anchor=ssylka-na-oplatu)                   |
| use_hex_link | bool   | no              | Генерация ссылки с hex-значениями или нет                                                                            |
