# UNIGAMECMS
# Встановлення каси
1. Завантажте та розпакуйте архів:
 - Для UnigameCMS: папка UnigameCMS

2. Завантажте вміст архіву на свій сайт у кореневий каталог.

- Потрібно вказати валюту вашого сайту, приклад:
- Налаштування валют `https://site/admin/bank`
<p align="center">
 <img width="400px" src="./uni_.jpg" alt="UNI"/>
</p>
- Налаштування валюти каси (для вибірки валюти каси, вам потрібно в налаштуваннях магазину FoxyPay вибрати ідентичну валюту):
<p align="center">
 <img width="400px" src="./foxypay_payments.png" alt="UNI"/>
</p>
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


 6.2 - Після `->set("{fp}", $bankConf->fowpay)`
 - Вставляємо
 - `->set("{foxypay}", $bankConf->foxypay)`

7. Імпортуйте до бази `base.sql` (це додасть потрібні колонки).

8. Редагуємо `inc\classes\class.payments.php`:
 - Знайдіть масив, наприклад:
	
 ```php
[
	'slug' => 'fowpay',
	'name' => 'FowPay'
]
```
> Увага! повинно вийти так
```php
[
	'slug' => 'fowpay',
	'name' => 'FowPay'
],
[
	'slug' => 'foxypay',
	'name' => 'foxypay'
]
```


9. Адмін-центр


- Відкриваємо `modules\admin\payments.php`
-Знаходимо
```php
$tpl->set("{qwact2}", $qwact[1]);
```

- Додаємо
```php
$tpl->set("{site_currency}", $bank_conf->site_currency);
$tpl->set("{foxypay_currency}", $bank_conf->foxypay_currency);
$tpl->set("{foxypay_token}", $bank_conf->foxypay_token);
$tpl->set("{foxypay_pay}", $bank_conf->foxypay);
```



10. - actions_panel
- Відкриваємо `ajax\actions_panel.php`
- Вставляємо код у самий низ
```php
if(isset($_POST['editFoxyPaySystem'])) {
	$foxypay_token = check(trim($_POST['foxypay_token']), null);

	if(empty($foxypay_token)) {
		exit('<p class="text-danger">Ви заповнили не усі поля!</p>');
	}

	$STH = $pdo->prepare("UPDATE config__bank SET foxypay_token=:foxypay_token LIMIT 1");
	write_log("Отредактирован FoxyPay");

	$STH->execute([':foxypay_token' => $foxypay_token]);
	exit('<p class="text-success">Налаштування змінено!</p>');
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
	if ($safe_mode == 1) {
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
		$STH = $pdo->prepare("UPDATE `$table` SET `$attr`=:value");
		$STH->execute([':value' => $value]);
	} else {
		$STH = $pdo->prepare("UPDATE `$table` SET `$attr`=:value WHERE `id`='$id' LIMIT 1");
		$STH->execute([':value' => $value]);
	}
	exit();
}
```

#11 js
- Відкриваємо `ajax\performers\acp.min.js` і в самий низ вставляємо


```javascript
function editFoxyPaySystem() {
    var data = {};
    data["editFoxyPaySystem"] = "1";
    data["foxypay_token"] = $("#foxypay_token").val();
    $.ajax({
        type: "POST",
        url: "../ajax/actions_panel.php",
        data: create_material(data),
        success: function (html) {
            $("#edit_foxypay_result").html(html);
        },
    });
}
```

## Шаблон
- Відкриємо `templates/standart/tpl/home/purse.tpl`

- знаходимо `{/if}` і після нього вставляємо код
```html
{if('{foxypay}' == '1')}
    <div class="custom-block">
        <div class="block">
            <div class="block_head">
                FoxyPay - оплата картками України та криптовалютою.
            </div>
            <div class="image-container">
                <label for="number_foxypay">
                    <img src="../files/merchants/foxypay.png" alt="foxypay">
                </label>
            </div>
            <input class="form-control" id="number_foxypay" placeholder="Укажите сумму" value="{price}">
            <div id="balance_result_foxypay" class="mt-3"></div>
            <button class="btn btn-outline-primary btn-xl" onclick="refill_balance('foxypay');">Поповнити баланс</button>
        </div>
    </div>
{/if}

<style>
    .image-container {
        display: flex;
        justify-content: center;
    }

    .image-container label {
        display: flex;
        justify-content: center;
        width: 100%;
    }

    .image-container img {
        max-width: 100%;
        height: auto;
    }
</style>
```

## Шаблон для адмін панелі
- Відкриємо `templates/admin/tpl/payments.tpl`
- Гортаємо в самий низ і бачимо 2 </div>
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















