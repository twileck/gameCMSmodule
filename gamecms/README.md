# GAMECMS

# ВАЖЛИВО
## Для роботи каси потрібні розширення для PHP (ЗВИЧАЙНО ВОНИ ВСТАНОВЛЕНІ ЗА УМОВЧЕННЯМ)
- ### cURL
- ### SimpleXML
### Встановити їх можна через SHELL-клієнт (якщо у вас VDS або VPS), якщо у вас хостинг, то подивіться як увімкнути розширення для PHP через ISP або CPanel

- ### Якщо у вас VDS або VPS, то використовуйте такі команди:
```shell
//Установка тільки з правами SUDO або ROOT,
//Дивимося версію php
php -v
//У нас версія php7.4, якщо у вас інша змінюємо свою
apt install php7.4-xml
//Якщо apache
systemctl restart apache2
//NGINX
systemctl reload nginx
```
# Встановлення каси

1. Завантажте та розпакуйте архів:
 - Для ліцензійної GameCMS: папка GameCMS

2. Завантажте вміст архіву на свій сайт у кореневий каталог.

Модуль підтримує без калькулятора курсу 3 валюти: USD, UAH, EUR.
ЯКЩО У ВАС ВКАЗАНА ВАЛЮТА САЙТУ USD, UAH, EUR, ТО ВАЛЮТА САЙТУ МАЄ БУТИ ТАКА, ЯК І ВАЛЮТА КАСИ.
- Приклад: Валюта сайту: UAH, то й валюта каси має приймати лише UAH.

3. Відкрийте файл на шляху `ajax/actions_m.php`.

4. Знайдіть `break;`.

5. Після `break;` вставте наступний код:
```php
case 'foxypay':
	if (empty($cashierSettings->foxypay_token)) {
	    throw new Exception('Спосіб оплати не налаштований');
	}
	
	$data = [
	    'amount' => $amount * 100,
	    'description' => $orderDesc,
	    'webhook_url' => $full_site_host.'purse?foxypay=pay',
	    'success_url' => $full_site_host.'purse/success',
	    'fail_url' => $full_site_host.'purse/fail',
	    'info' => user()->id,
	];
	
	$curl = new Curl();
	$curl->setHeader('token', $cashierSettings->foxypay_token);
	$curl->post('https://foxypay.net/api/payment', $data);
	$response = json_decode($curl->rawResponse, true);
	if (false === $response['success']) {
	    throw new Exception($response['err']);
	}
	
	Payments::showLink($response['redirect_url']);
	
	break;

```



6. Виправлення файлу `modules/purse/index.php`:

 6.1 - Вставте перед `$fail = '';
```php
include_once (__DIR__.'/modules_foxypay.php');
```






7. Імпортуйте до бази `base.sql` (це додасть потрібні колонки).

8. Редагуємо `inc/merchants.php`:
 - Знайдіть масив, наприклад: або будь-який інший
	
 ```php
'ps' => [
   'title' => 'Paysera',
   'name'  => 'Paysera',
   'image' => 'paysera.jpg'
],
```
> Після вставляємо код
```php
'foxypay' => [
   'title' => 'Миттєве зарахування коштів у баланс.',
   'name'  => 'FoxyPay',
   'image' => 'foxypay.png'
],
```


9. Адмін-центр

- Відкриваємо `ajax\actions_panel.php`
- Вставляємо код у самий низ
```php
if(isset($_POST['editFoxyPaySystem'])) {
	$foxypay_token = check(trim($_POST['foxypay_token']), null);
	
	if(empty($foxypay_token)) {
		exit('<p class="text-danger">Вы заполнили не все поля!</p>');
	}
	
	$STH = $pdo->prepare("UPDATE config__bank SET foxypay_token=:foxypay_token LIMIT 1");
	write_log("Отредактирован FoxyPay");
	
	$STH->execute([':foxypay_token' => $foxypay_token]);
	exit('<p class="text-success">Настройки изменены!</p>');
}

```
- Далі знаходимо рядок з if (isset($_POST['change_value'])) і змінюємо код на цей:
```php
if (isset($_POST['change_value'])) {
	$table = check($_POST['table'], null);
	$attr = check($_POST['attr'], null);
	$value = check($_POST['value'], null);
	$id = check($_POST['id'], "int");

	if (empty($attr)) {
		exit();
	}
	if (check_for_php($_POST['value'])) {
		exit();
	}
	if (ifSafeMode()) {
		if (($_POST['value'] != check($_POST['value'], "int")) && (!in_array($_POST['value'], ['USD', 'EUR', 'UAH']))) {
			exit();
		}
		if (
			!in_array(
				check($_POST['table'], null),
				['config', 'users', 'config__bank', 'config__secondary', 'config__email', 'config__prices']
			)
		) {
			exit();
		}
	}

	if (empty($value) && $value != 0) {
		$value = '';
	}

	if (empty($id)) {
		$STH = pdo()->prepare("UPDATE $table SET `$attr`=:value");
		$STH->execute([':value' => $value]);
	} else {
		$STH = pdo()->prepare("UPDATE $table SET `$attr`=:value WHERE `id`='$id' LIMIT 1");
		$STH->execute([':value' => $value]);
	}
	exit();
}
```
#11 js
- Відкриваємо `ajax/ajax-admin.js` і в самий низ вставляємо


```javascript
function editFoxyPaySystem() {
	let data = {};
	data['editFoxyPaySystem'] = '1';
	data['foxypay_token'] = $('#foxypay_token').val();
	$.ajax({
		type: "POST",
		url: "../ajax/actions_panel.php",
		data: create_material(data),
		success: function (html) {
			$("#edit_foxypay_result").html(html);
		}
	});
}
```

## Шаблон для адмін панелі
- Відкриємо `templates/admin/tpl/payments.tpl`
- Гартуємо в самий низ бачимо 2 </div>
```html
	</div>
</div>
```
- Між ними ставимо наступний код
```html
<div class="block">
	<div class="block_head">
		FoxyPay
	</div>
	<div class="form-group mb-10">
		<div class="btn-group" data-toggle="buttons">
			<label class="btn btn-default {if($merchants->foxypay == 1)} active {/if}"
			       onclick="change_value('config__bank','foxypay','1','1');">
				<input type="radio">
				Включить
			</label>
			<label class="btn btn-default {if($merchants->foxypay == 2)} active {/if}"
			       onclick="change_value('config__bank','foxypay','2','1');">
				<input type="radio">
				Выключить
			</label>
		</div>
	</div>
	<div class="input-group">
		<span class="input-group-btn">
			<button class="btn btn-default" type="button"
				onclick="editFoxyPayPaymentSystem();">
				Изменить
			</button>
		</span>
		<input type="text"
		       class="form-control"
		       id="foxypay_token"
		       maxlength="255"
		       autocomplete="off"
		       value="{{$merchants->foxypay_token}}"
		       placeholder="Токен">

	</div>
	<div id="edit_foxypay_result"></div>
	<div class="bs-callout bs-callout-info mt-10">
		<h5>
			<a target="_blank" href="https://github.com/twileck/gameCMSmodule">
				<span class="glyphicon glyphicon-link"></span> Нажмите для перехода к инструкции
			</a>
		</h5>
		<table>
			<tr>
				<td style="text-align: right">URL оповещения:</td>
				<td>&nbsp&nbsp<b>{full_site_host}purse?foxypay=pay</b>
				</td>
			</tr>
			<tr>
				<td style="text-align: right">URL успешной оплаты:</td>
				<td>&nbsp&nbsp<b>{full_site_host}purse/success</b>
				</td>
			</tr>
			<tr>
				<td style="text-align: right">URL неуспешной оплаты:</td>
				<td>&nbsp&nbsp<b>{full_site_host}purse/fail</b>
				</td>
			</tr>
		</table>
	</div>
</div>
```
