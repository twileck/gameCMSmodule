<?php

// FoxyPay
if (isset($_GET['foxypay']) && $_GET['foxypay'] === 'pay') {
	$payMethod = 'foxypay';
	$currentCurrency = 'UAH'; // Валюта сайту фіксована

	try {
		$source = file_get_contents('php://input');
		$requestData = json_decode($source, true);

		$requiredFields = ["code", "amount", "currency", "info", "sign"];
		foreach ($requiredFields as $field) {
			if (empty($requestData[$field])) {
				throw new Exception('Missing required data');
			}
		}

		// Перевірка підпису
		$payload = $requestData["code"] . '.' . $requestData["amount"] . '.' . $requestData["currency"] . '.' . $requestData["info"];
		$signature = hash_hmac('sha256', $payload, $merchantsSettings->foxypay_token);
		if (strtoupper($requestData["sign"]) !== strtoupper($signature)) {
			throw new Exception('Invalid signature');
		}

		// Дозволяється лише гривня
		if ($requestData["currency"] !== "UAH") {
			throw new Exception('Only UAH is supported');
		}

		$amount = clean($requestData["amount"] / 100, 'float'); // Копійки -> гривні
		$payNumber = clean($requestData["code"], 'varchar');
		$userId = clean($requestData["info"], 'int');

		$userInfo = $Pm->getUser($pdo, $userId);
		if (empty($userInfo->id)) {
			if (!registerUserIfNotExists($pdo, $userId)) {
				throw new Exception('Unknown user');
			}
			$userInfo = $Pm->getUser($pdo, $userId);
			if (empty($userInfo->id)) {
				throw new Exception('User registration failed');
			}
		}

		if ($Pm->issetPay($pdo, $payMethod, $payNumber)) {
			exit('Old');
		}

		$Pm->doPayAction($pdo, $userInfo, $amount, configs()->bank, $payMethod, $payNumber, 'UAH');
		exit('OK');

	} catch (Exception $e) {
		$Pm->paymentLog($payMethod, $e->getMessage(), $pdo, $userId ?? 0, 2);
		http_response_code(500);
		exit($e->getMessage());
	}
}

// Функція для реєстрації користувача
function registerUserIfNotExists($pdo, $userId) {
	$stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
	$stmt->execute([$userId]);
	if ($stmt->fetch()) return true;

	$username = 'User_' . $userId;
	$email = $userId . '@auto.local';
	$password = password_hash(bin2hex(random_bytes(6)), PASSWORD_DEFAULT);
	$stmt = $pdo->prepare("INSERT INTO users (id, username, email, password, reg_date) VALUES (?, ?, ?, ?, NOW())");
	return $stmt->execute([$userId, $username, $email, $password]);
}
