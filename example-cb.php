<?php

include './lib.php';

$data = json_decode(file_get_contents('php://input'), true); // если вам нужен объект, то не указывайте второй параметр в json_decode()
if(!is_null($data)) {
	$vkcoin = new VKCoinClient(305360617, 'cNwFTVP7Y33M5TxgZMhLQmdcNrb6qu72mNCTeRdX9PVEqbJPpe');
	if($vkcoin->isKeyValid($data)) {
		// обработка транзакции
	}
}

?>