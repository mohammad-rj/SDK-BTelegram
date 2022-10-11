<?php
require_once '../src/SDK-BTelegram.php';

$bot_token = '989904240:AAGS4Ki97spa1Qqs1cHg4YpXnJJDiE447w8';
//SetWebhook('bot1',$bot_token); //just run once

$re = result(file_get_contents('php://input'));

$SendMessage = new BT_SendMessage();
$SendMessage->botToken = $bot_token;
$SendMessage->chat_id = $re->chat_id;
$SendMessage->text = $re->text_result;
$SendMessage->SendMessage();