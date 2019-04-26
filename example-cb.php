<?php

include './lib.php';

$data = json_decode(file_get_contents('php://input'), true);
if(!is_null($data)) {
	$vkcoin = new VKCoinClient(305360617, 'cNwFTVP7Y33M5TxgZMhLQmdcNrb6qu72mNCTeRdX9PVEqbJPpe');
	if($vkcoin->isKeyValid($data)) {
		// обработка транзакции
	}
}

?>