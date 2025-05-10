<?php

// FoxyPay
if (isset($_GET['foxypay']) && $_GET['foxypay'] === 'pay') {
	$payMethod = 'foxypay'; 												// Устанавливаем метод оплаты
	$currentCurrency = Payments::getCashierCurrency($payMethod); 			// Получаем текущую валюту кассира

	try {
		$source = file_get_contents('php://input');
		$requestData = json_decode($source, true);

		$requiredFields = ["code", "amount", "currency", "info", "sign"];	// Проверяем наличие необходимых данных в массиве $requestData
		foreach ($requiredFields as $field) {
			if (empty($requestData[$field])) {
				throw new Exception('Missing required data');
			}
		}

		$payload = $requestData["code"] . '.' . $requestData["amount"] . '.' . $requestData["currency"] . '.' . $requestData["info"];
		$signature = hash_hmac('sha256', $payload, $merchantsSettings->foxypay_token);

		// Проверяем соответствие подписи
		if (strtoupper($requestData["sign"]) !== strtoupper($signature)) {
			throw new Exception('Invalid signature');
		}

		$siteCurrency = $merchantsSettings->site_currency;
		$amountInCents = $requestData["amount"];    // Сумма котора пришла
		$amountCurrency = $requestData["currency"];  // Код валюты

		$amountInSiteCurrency = round((int)$requestData["amount"] / 100);

		$amount = clean($amountInSiteCurrency, 'float');
		$payNumber = clean($requestData["code"], 'varchar');
		$userId = clean($requestData["info"], 'int');

		$userInfo = $Pm->getUser($pdo, $userId);				// Получаем информацию о пользователе
		if (empty($userInfo->id)) {								// Проверяем существование пользователя
			if (!registerUserIfNotExists($pdo, $userId)) {
				throw new Exception('Unknown user');
			}
			$userInfo = $Pm->getUser($pdo, $userId); 			// Повторно получаем информацию после создания
			if (empty($userInfo->id)) {
				throw new Exception('User registration failed');
			}
		}

		// Проверяем, был ли уже обработан этот платеж
		if ($Pm->issetPay(pdo(), $payMethod, $payNumber)) {
			exit('Old');
		}

		// Выполняем действия по обработке платежа
		$Pm->doPayAction(pdo(), $userInfo, $amount, configs()->bank, $payMethod, $payNumber, $requestData["currency"]);
		exit('OK');

	} catch (Exception $e) {
		// Логируем ошибку и возвращаем ее клиенту
		$userId = isset($userId) ? $userId : 0;
		$Pm->paymentLog($payMethod, $e->getMessage(), $pdo, $userId, 2);

		http_response_code(500);
		exit($e->getMessage());
	}
}

// Функция для регистрации пользователя, если его нет
function registerUserIfNotExists($pdo, $userId) {
	$stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
	$stmt->execute([$userId]);
	if ($stmt->fetch()) {
		return true;
	}

	$username = 'User_' . $userId;
	$email = $userId . '@auto.local';
	$password = password_hash(bin2hex(random_bytes(6)), PASSWORD_DEFAULT); // случайный пароль
	$stmt = $pdo->prepare("INSERT INTO users (id, username, email, password, reg_date) VALUES (?, ?, ?, ?, NOW())");
	return $stmt->execute([$userId, $username, $email, $password]);
}
