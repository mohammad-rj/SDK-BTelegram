# SDK-BTelegram

In June 2017 when telegram itself haven't had even good docs about api bots on it site, right a day after i try php for the first time. there was a software development company that wanted to send a Photo on Telegram with local file, but they couldn't, so I wrote this SDK :d.

it's useful, easy and fast with *Native PHP*  base of *official Telegram Bot API*, 

> Welcome to join in and feel free to contribute.
#### FULL AUTO | Installation requirement and environment 
buy a *VPS* server linux *centos9* and insert this code in terminal:

```
mkdir -p /var/www/html && cd $_
bash <(curl -s https://gist.githubusercontent.com/mohammad-rj/daad5f355cd1c90f96f6e0ff90378dd5/raw/install-base.sh)
composer require mohammad-rj/sdk-btelegram
cd vendor/mohammad-rj/sdk-btelegram/tests/
vi test.php #replace your bot_token
php test.php
```

#### MANUALLY | Installation
create virtual host on port 8443 or 443 with ssl self sertificate

```
composer require mohammad-rj/sdk-btelegram
openssl req -x509 -nodes -days 365 -newkey rsa:2048 -sha256 -keyout $cert_path/bot1.key -out $cert_path/bot1.crt -subj '/C=US/ST=New York/L=NYC/O=Tbot/CN=$ip_address
curl -F 'url=https://$ip_address:8443$file_path' -F 'certificate=@$cert_path/$botname.crt' https://api.telegram.org/bot$bot_token/setWebhook
```



### examples

SendMessage
```php
require_once '../src/SDK-BTelegram.php';
$re = result(file_get_contents('php://input'));
$SendMessage = new BT_SendMessage();
$SendMessage->botToken = $bot_token;
$SendMessage->chat_id = $re->chat_id;
$SendMessage->text = $re->text_result;
$SendMessage->SendMessage();
```

I will add some complete examples soon ..
