<?php
include_once 'SDK-BT-config.php';
function makeHTTPRequest($token, $method, $datas = [])
{
    $url = "https://api.telegram.org/bot" . $token . "/" . $method;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HTTPHEADER, Array("Content-Type:multipart/form-data"));
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $datas);
//    $info = curl_getinfo($ch);
//    var_dump($info);
    echo $res = curl_exec($ch);
    if (curl_error($ch)) {
        var_dump(curl_error($ch));
    } else {
        return $res;

//            $file = 'data2.txt';
//            $current = file_get_contents($file);
//            $current .= "\n return".$res;
//            file_put_contents($file, $current);
    }
}

function result($result){
    $result = json_decode($result);
    $result_param = [];
    if (isset($result->message)) {
        $result_param['date_results'] = $result->message->date;
        $result_param['chat_id'] = $result->message->from->id;
        $result_param['message_id'] = $result->message->message_id;
        $result_param['username_results'] = $result->message->from->username;
        $result_param['first_name'] = $result->message->from->first_name;
        $result_param['last_name'] = $result->message->from->last_name;
        $result_param['full_name'] = $result_param['first_name'] . " " . $result_param['last_name'];

        if (isset($result->message->caption)) {
            $result_param['text_result'] = $result->message->caption;
        }
        if (isset($result->message->text)) {
            $result_param['text_result'] = $result->message->text;
        }
        if (isset($result->message->reply_to_message)) {
            $result_param['is_reply_to_message'] = true;
            $result_param['text_result'] = $result->message->text;
            $result_param['text_rtm'] = $result->message->reply_to_message->text;

            $result_param['chat_id_RMF'] = $result->message->reply_to_message->from->id;
            $result_param['chat_id_RMC'] = $result->message->reply_to_message->chat->id;
            $result_param['message_id_RM'] = $result->message->reply_to_message->message_id;
            $result_param['result_date_RM'] = $result->message->reply_to_message->date;

        }
    }
    if (isset($result->callback_query)) {
        $result_param['is_callback_query'] = true;
        $result_param['date_results'] = microtime();
        $result_param['chat_id'] = $result->callback_query->from->id;
        $result_param['username_results'] = $result->callback_query->from->username;

        $result_param['chat_instance'] = $result->callback_query->chat_instance;
        $result_param['id_CQ'] = $result->callback_query->callback_query->id;
        $result_param['data_CQ'] = $result->callback_query->data;

        $result_param['text_CQ'] = $result->callback_query->message->text;
        $result_param['chat_id_CQ'] = $result->message->callback_query->from->id;
        $result_param['$chat_id_RM_CQ'] = $result->message->callback_query->chat->id;
        $result_param['message_id_CQ'] = $result->message->callback_query->message->message_id;
        $result_param['result_date_CQ'] = $result->message->callback_query->message->date;

    }
    if (isset($result->inline_query)) {
        $result_param['is_inline_query'] = true;
        $result_param['date_results'] = microtime();
        $result_param['chat_id'] = $result->inline_query->from->id;
        $result_param['username_results'] = $result->inline_query->from->username;
        $result_param['id_IQ'] = $result->inline_query->id;
        $result_param['text_result'] = $result->inline_query->query;
        $result_param['query_IQ'] = $result->inline_query->query;
        $result_param['offset_IQ'] = $result->inline_query->offset;
    }
    if (isset($result->channel_post)) {
        $result_param['chat_id'] = $result->channel_post->sender_chat->id;
        $result_param['message_id'] = $result->channel_post->message_id;
        $result_param['text_result'] = $result->channel_post->text;
    }

    return json_decode(json_encode($result_param));
}

function SetWebhook($botname,$bot_token){
    global $nginx_path,$nginx_BT_config,$cert_path;

    $ip_address = getPublicIP();
    $main_path = debug_backtrace()[0]['file'];
    $main_path_split = explode("/",$main_path);
    $file_path = end($main_path_split);
    $root_path = str_replace("/$file_path",'',$main_path);

    if (!file_exists("$cert_path")) {
        rmkdir($cert_path);
    }
    if (!file_exists("$cert_path/dhparam.pem")) {
        exec("openssl dhparam -out $cert_path/dhparam.pem 2048");
    }
    if (!file_exists("$cert_path/$botname.crt")) {
        exec("openssl req -x509 -nodes -days 365 -newkey rsa:2048 -sha256 -keyout $cert_path/bot1.key -out $cert_path/bot1.crt -subj '/C=US/ST=New York/L=NYC/O=Tbot/CN=$ip_address'");
    }
    if (!file_exists($nginx_BT_config)) {
        if (!file_exists($nginx_path)) {
            exec(" bash <(curl -s https://gist.githubusercontent.com/mohammad-rj/895b0d5fdf64cd06f33fcdd29a1683a3/raw/install-LEMP.sh)");
        }


        $BT_config = 'server {
        listen         8443 http2 ssl;
        listen         [::]:8443 http2 ssl;
        server_name    _;
        root           '.$root_path.';
    
        ssl on;
        ssl_certificate 	'."$cert_path/$botname".'.crt;
        ssl_certificate_key '."$cert_path/$botname".'.key;
        ssl_dhparam         '."$cert_path/".'dhparam.pem;
    
        ssl_protocols TLSv1 TLSv1.1 TLSv1.2;
        ssl_prefer_server_ciphers on;
        ssl_ciphers "EECDH+AESGCM:EDH+AESGCM:AES256+EECDH:AES256+EDH";
        ssl_ecdh_curve secp384r1;
        ssl_session_cache shared:SSL:10m;
        resolver 8.8.8.8 8.8.4.4 valid=300s;
        resolver_timeout 5s;
        # Disable preloading HSTS for now.  You can use the commented out header line that includes
        # the "preload" directive if you understand the implications.
        #add_header Strict-Transport-Security "max-age=63072000; includeSubdomains; preload";
        add_header Strict-Transport-Security "max-age=63072000; includeSubdomains";
        add_header X-Frame-Options DENY;
        add_header X-Content-Type-Options nosniff;
    
        location / {
            try_files $uri $uri/ =404;
        }
        
        location ~* \.php$ {
            try_files $uri =404;
            fastcgi_pass unix:/run/php-fpm/www.sock;
            fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
            include fastcgi_params;
            fastcgi_buffering off;
            fastcgi_keep_conn on; # < solution
            gzip off;
            fastcgi_read_timeout 86400;
        }
    }';

        file_put_contents($nginx_BT_config,$BT_config);
        exec("systemctl restart nginx");
    }

    exec("curl -F 'url=https://$ip_address:8443/$file_path' -F 'certificate=@$cert_path/$botname.crt' https://api.telegram.org/bot$bot_token/setWebhook > /dev/null 2>&1 &");

}

function rmkdir($path) {
    $path = str_replace("\\", "/", $path);
    $path = explode("/", $path);

    $rebuild = '';
    foreach($path AS $p) {

        if(strstr($p, ":") != false) {
//            echo "\nExists : in $p\n";
            $rebuild = $p;
            continue;
        }
        $rebuild .= "/$p";
//        echo "Checking: $rebuild\n";
        if(!is_dir($rebuild)) mkdir($rebuild);
    }
}

function getPublicIP() {
    // create & initialize a curl session
    $curl = curl_init();

    // set our url with curl_setopt()
    curl_setopt($curl, CURLOPT_URL, "http://httpbin.org/ip");

    // return the transfer as a string, also with setopt()
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

    // curl_exec() executes the started curl session
    // $output contains the output string
    $output = curl_exec($curl);

    // close curl resource to free up system resources
    // (deletes the variable made by curl_init)
    curl_close($curl);

    $ip = json_decode($output, true);

    return $ip['origin'];
}

class BT_DownloadFile1 //save directly on hard direve
{
    public $file_url;
    public $save_to;

    function DownloadFile()
    {

        $fp = fopen($this->save_to, 'w');
        $ch = curl_init($this->file_url);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_exec($ch);
        curl_close($ch);
        fclose($fp);

//        return
    }
};

class BT_DownloadFile2 // first save on ram
{
    public $file_url;
    public $save_to;

    function DownloadFile()
    {

//        $ch = curl_init($this->file_url);
//        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//        $data = ($ch);
//        curl_close($ch);
//        file_put_contents($this->save_to, $data);
//

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 0);
        curl_setopt($ch,CURLOPT_URL,$this->file_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $downloaded_file = fopen($this->save_to, 'w');
        curl_setopt($ch, CURLOPT_FILE, $downloaded_file);
        curl_exec ($ch);
        curl_close ($ch);
        fclose($downloaded_file);


//        return
    }
};

class BT_ReplyKeyboardMarkup

{
    public $keyboard = [];
    public $resize_keyboard = true;
    public $one_time_keyboard = false;
    public $selective = false;
    private $temp_keyboard = [];

    public function addButton($text, $request_contact = false, $request_location = false)
    {
        array_push($this->temp_keyboard, [text => $text, request_contact => $request_contact]);
    }

    public function addRow()
    {
        array_push($this->keyboard, $this->temp_keyboard);
        $this->temp_keyboard = [];
    }
}

class BT_inlineKeyboardMarkup

{
    public $inline_keyboard = [];
    public $temp_keyboard = [];

    public function addButton($text, $callback_data = "", $url = '')
    {
//        $myarry=array(text=>$text,callback_data=>"ASDFG");
        $myarry = array(text => $text);
        if ($callback_data != "")
            $myarry[callback_data] = $callback_data;
        else
            $myarry[url] = $url;
        array_push($this->temp_keyboard, $myarry);
    }

    public function addRow()
    {
        array_push($this->inline_keyboard, $this->temp_keyboard);
        $this->temp_keyboard = [];
    }
}

//_________________________________________
class BT_getUpdates

{
    public $botToken;
    public $coffset;    //Yes
    public $limit;
    public $timeout;
    public $allowed_updates;


    public function getUpdates()//,$chat_id,$text,$reply_markup=null)
    {
        $params = array(
            'coffset' => $this->coffset,
            'limit' => $this->limit,
            'timeout' => $this->timeout,
            'allowed_updates' => $this->allowed_updates,
        );

        return makeHTTPRequest($this->botToken, 'getUpdates', $params);
    }

}

//_________________________________________
class BT_SendMessage

{
    public $botToken;
    public $chat_id;    //Yes
    public $text;       //Yes
    public $parse_mode;
    public $disable_web_page_preview;
    public $disable_notification;
    public $reply_to_message_id;
    public $reply_markup;

    public function SendMessage()//,$chat_id,$text,$reply_markup=null)

    {
        $params = array(
            'chat_id' => $this->chat_id,
            'text' => $this->text,
            'parse_mode' => $this->parse_mode,
            'disable_web_page_preview' => $this->disable_web_page_preview,
            'disable_notification' => $this->disable_notification,
            'reply_to_message_id' => $this->reply_to_message_id,
        );

        if (!is_null($this->reply_markup)) {
            $params['reply_markup'] = json_encode($this->reply_markup);
        } else {

        }

//        $callclass = new SendMessageToTelegram();
//        $callclass->makeHTTPRequest($this->botToken,'sendMessage', $params);

        return makeHTTPRequest($this->botToken, 'sendMessage', $params);
    }

}

class BT_Main
{
    public $botToken;
    public function SendMessage1( $chat_id, $text, $parse_mode = null, $disable_web_page_preview = null, $disable_notification = null, $reply_to_message_id = null, $reply_markup = null)
    {
        $params = array(
            'chat_id' => $chat_id,
            'text' => $text,
            'parse_mode' => $parse_mode,
            'disable_web_page_preview' => $disable_web_page_preview,
            'disable_notification' => $disable_notification,
            'reply_to_message_id' => $reply_to_message_id,
        );

//    if (!is_null($this->reply_markup)) {
//        $params['reply_markup'] = json_encode($reply_markup);
//    }
        return makeHTTPRequest($this->botToken, 'sendMessage', $params);

    }
}

class BT_forwardMessage
{
    public $botToken;
    public $chat_id;               //Yes
    public $from_chat_id;          //Yes
    public $disable_notification;
    public $message_id;            //Yes


    public function forwardMessage()
    {
        $params = array(
            'chat_id' => $this->chat_id,
            'from_chat_id' => $this->from_chat_id,
            'disable_notification' => $this->disable_notification,
            'message_id' => $this->message_id,
        );

        return makeHTTPRequest($this->botToken, 'forwardMessage', $params);
    }
}

class BT_SendPhoto
{
    public $botToken;    //Yes
    public $chat_id;     //Yes
    public $photo;
    public $caption;
    public $parse_mode;
    public $disable_web_page_preview = false;
    public $disable_notification = false;
    public $reply_to_message_id = 0;
    public $reply_markup;


    public function SendPhoto()//($token,$chat_id,$photo)
    {
        if ((substr($this->photo, 0, 4)) !== ("http")) {
            if (substr($this->photo, -4, 1) === (".")) {
                $this->photo = new CURLFile(realpath($this->photo));;
            }
        }
        $params = array(
            'chat_id' => $this->chat_id,
            'photo' => $this->photo,
            'caption' => $this->caption,
            'disable_web_page_preview' => $this->disable_web_page_preview,
            'disable_notification' => $this->disable_notification,
            'reply_to_message_id' => $this->reply_to_message_id,
        );

        if (!is_null($this->reply_markup)) {
            $params['reply_markup'] = json_encode($this->reply_markup);
        }


        return makeHTTPRequest($this->botToken, 'sendPhoto', $params);
    }

}

//_________________________________________  new
class BT_sendAudio
{
    public $botToken;
    public $chat_id;    //Yes
    public $audio;      //Yes
    public $caption;
    public $duration;
    public $performer;
    public $title;
    public $disable_notification;
    public $reply_to_message_id;
    public $reply_markup;

    public function sendAudio()
    {
        $params = array(
            'chat_id' => $this->chat_id,
            'audio' => $this->audio,
            'caption' => $this->caption,
            'duration' => $this->duration,
            'performer' => $this->performer,
            'title' => $this->title,
            'disable_notification' => $this->disable_notification,
            'reply_to_message_id' => $this->reply_to_message_id,
        );

        if (!is_null($this->reply_markup)) {
            $params['reply_markup'] = json_encode($this->reply_markup);
        }

        return makeHTTPRequest($this->botToken, 'sendAudio', $params);
    }

}

class BT_sendDocument
{
    public $botToken;
    public $chat_id;    //Yes
    public $document;      //Yes
    public $caption;
    public $duration;
    public $disable_notification;
    public $reply_to_message_id;
    public $reply_markup;

    public function sendDocument()
    {
        $params = array(
            'chat_id' => $this->chat_id,
            'document' => $this->document,
            'caption' => $this->caption,
            'disable_notification' => $this->disable_notification,
            'reply_to_message_id' => $this->reply_to_message_id,
        );

        if (!is_null($this->reply_markup)) {
            $params['reply_markup'] = json_encode($this->reply_markup);
        }

        return makeHTTPRequest($this->botToken, 'sendDocument', $params);
    }

}

class BT_sendVideo
{
    public $botToken;
    public $chat_id;    //Yes
    public $video;      //Yes
    public $duration;
    public $width;
    public $height;
    public $caption;
    public $disable_notification;
    public $reply_to_message_id;
    public $reply_markup;

    public function sendVideo()
    {
        $params = array(

            'chat_id' => $this->chat_id,
            'video' => $this->video,
            'duration' => $this->duration,
            'height' => $this->height,
            'width' => $this->width,
            'caption' => $this->caption,
            'disable_notification' => $this->disable_notification,
            'reply_to_message_id' => $this->reply_to_message_id,
        );

        if (!is_null($this->reply_markup)) {
            $params['reply_markup'] = json_encode($this->reply_markup);
        }


        return makeHTTPRequest($this->botToken, 'sendVideo', $params);
    }

}

class BT_sendVoice
{
    public $botToken;
    public $chat_id;    //Yes
    public $voice;      //Yes
    public $caption;
    public $disable_notification;
    public $reply_to_message_id;
    public $reply_markup;

    public function sendVoice()
    {
        $params = array(

            'chat_id' => $this->chat_id,
            'voice' => $this->voice,
            'caption' => $this->caption,
            'disable_notification' => $this->disable_notification,
            'reply_to_message_id' => $this->reply_to_message_id,
        );

        if (!is_null($this->reply_markup)) {
            $params['reply_markup'] = json_encode($this->reply_markup);
        }

        return makeHTTPRequest($this->botToken, 'sendVoice', $params);
    }

}

class BT_sendVideoNote
{
    public $botToken;
    public $chat_id;    //Yes
    public $video_note;      //Yes
    public $duration;
    public $length;
    public $disable_notification;
    public $reply_to_message_id;
    public $reply_markup;

    public function sendVideoNote()
    {
        $params = array(

            'chat_id' => $this->chat_id,
            'video_note' => $this->video_note,
            'duration' => $this->duration,
            'length' => $this->length,
            'disable_notification' => $this->disable_notification,
            'reply_to_message_id' => $this->reply_to_message_id,
        );

        if (!is_null($this->reply_markup)) {
            $params['reply_markup'] = json_encode($this->reply_markup);
        }

        return makeHTTPRequest($this->botToken, 'sendVideoNote', $params);
    }

}

class BT_sendLocation
{
    public $botToken;
    public $chat_id;    //Yes
    public $latitude;      //Yes
    public $longitude;      //Yes
    public $disable_notification;
    public $reply_to_message_id;
    public $reply_markup;

    public function sendLocation()
    {
        $params = array(

            'chat_id' => $this->chat_id,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'disable_notification' => $this->disable_notification,
            'reply_to_message_id' => $this->reply_to_message_id,
        );

        if (!is_null($this->reply_markup)) {
            $params['reply_markup'] = json_encode($this->reply_markup);
        }

        return makeHTTPRequest($this->botToken, 'sendLocation', $params);
    }

}

//____________________________________________ new above are checked and be okay
class BT_sendVenue
{
    public $botToken;
    public $chat_id;    //Yes
    public $latitude;      //Yes
    public $longitude;      //Yes
    public $title;     //Yes
    public $address;     //Yes
    public $foursquare_id;
    public $disable_notification;
    public $reply_to_message_id;
    public $reply_markup;

    public function sendVenue()
    {
        $params = array(

            'chat_id' => $this->chat_id,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'title' => $this->title,
            'address' => $this->address,
            'foursquare_id' => $this->foursquare_id,
            'disable_notification' => $this->disable_notification,
            'reply_to_message_id' => $this->reply_to_message_id,
        );

        if (!is_null($this->reply_markup)) {
            $params['reply_markup'] = json_encode($this->reply_markup);
        }

        return makeHTTPRequest($this->botToken, 'sendVenue', $params);
    }

}

class BT_sendChatAction
{
    public $botToken;
    public $chat_id;    //Yes
    public $action;    //Yes

    public function sendChatAction()
    {
        $params = array(

            'chat_id' => $this->chat_id,
            'action' => $this->action,
        );

        return makeHTTPRequest($this->botToken, 'sendChatAction', $params);
    }

}

//_________________________________________
class BT_sendContact
{
    public $botToken;
    public $chat_id;    //Yes
    public $phone_number;    //Yes
    public $first_name;      //Yes
    public $last_name;
    public $disable_notification;
    public $reply_to_message_id;
    public $reply_markup;

    public function sendContact()
    {
        $params = array(

            'chat_id' => $this->chat_id,
            'phone_number' => $this->phone_number,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'disable_notification' => $this->disable_notification,
            'reply_to_message_id' => $this->reply_to_message_id,
            'reply_markup' => $this->reply_markup,
        );

        return makeHTTPRequest($this->botToken, 'sendContact', $params);
    }

}

class BT_getUserProfilePhotos
{
    public $user_id;
    public $offset;
    public $limit;
    public $botToken;

    public function getUserProfilePhotos()
    {


        $params = array(
            'user_id' => $this->user_id,
            'offset' => $this->offset,
            'limit' => $this->limit,
        );

        return makeHTTPRequest($this->botToken, 'getUserProfilePhotos', $params);
//        $update = json_decode(makeHTTPRequest($this->botToken,'getUserProfilePhotos', $params));


        $photos = $update->result->photos;

        if (is_array($photos)) {

            foreach ($photos as $photo) {
                $count = count($photo) - 1;
                $file_id = $photo[$count]->file_id;

                $SendPhoto = new SendPhoto();
                $SendPhoto->botToken = $this->botToken;
                $SendPhoto->chat_id = $this->user_id;
                $SendPhoto->photo = $file_id;
                $SendPhoto->SendPhoto();


            }
        }
    }
}

class BT_getFile
{
    public $botToken;
    public $file_id;

    public function getFile()
    {
//        $photo = $this->results->message->photo;
//        if (is_array($photo)) {
//            $count = count($photo) - 1;
//
//            $params = array(
//                'file_id' => $photo[$count]->file_id
//            );

        $params = array(
            'file_id' => $this->file_id
        );

        return (makeHTTPRequest($this->botToken, 'getFile', $params));
//            $update = json_decode(makeHTTPRequest($this->botToken, 'getFile', $params));
//            $json = $update->result->file_path;
//            $UrlForDl = "https://api.telegram.org/file/bot" . $this->botToken . "/" . $json;
//            file_put_contents($json, fopen($UrlForDl, 'r'));
//            return "http://rahimi.atiehsazan.ir/".$json;


    }
}

//__________________________________________ empty
class BT_banChatMember
{
    public $chat_id;
    public $user_id;
    public $until_date;
    public $revoke_messages;
    public $botToken;

    public function banChatMember()
    {
        $params = array(
            'chat_id' => $this->chat_id,
            'user_id' => $this->user_id,
            'until_date' => $this->until_date,
            'revoke_messages' => $this->revoke_messages,
        );
        return makeHTTPRequest($this->botToken, 'banChatMember', $params);
    }
}
class BT_unbanChatMember
{
    public $chat_id;
    public $user_id;
    public $until_date;
    public $revoke_messages;
    public $botToken;

    public function unbanChatMember()
    {
        $params = array(
            'chat_id' => $this->chat_id,
            'user_id' => $this->user_id,
            'until_date' => $this->until_date,
            'revoke_messages' => $this->revoke_messages,
        );
        return makeHTTPRequest($this->botToken, 'unbanChatMember', $params);
    }
}

class BT_restrictChatMember
{

}

class BT_promoteChatMember
{

}

class BT_exportChatInviteLink
{

}

class BT_setChatPhoto
{

}

class BT_deleteChatPhoto
{

}

class BT_setChatTitle
{

}

class BT_setChatDescription
{

}

class BT_pinChatMessage
{

}

class BT_unpinChatMessage
{

}

class BT_leaveChat
{

}

class BT_getChat
{
    public $botToken;
    public $chat_id;    //Yes

    public function getChat()
    {
        $params = array(

            'chat_id' => $this->chat_id,
        );

        return makeHTTPRequest($this->botToken, 'getChat', $params);
    }
}

class BT_getChatAdministrators
{

}

class BT_getChatMembersCount
{


}

class BT_getChatMember
{
    public $botToken;
    public $chat_id;    //Yes
    public $user_id;    //Yes

    public function getChatMember()
    {
        $params = array(

            'chat_id' => $this->chat_id,
            'user_id' => $this->user_id,
        );

        return makeHTTPRequest($this->botToken, 'getChatMember', $params);
    }
}

class BT_answerCallbackQuery
{
    public $callback_query_id;
    public $text;
    public $show_alert;
    public $url;
    public $cache_time;
    public $botToken;

    public function answerCallbackQuery()
    {
        $params = array(
            'callback_query_id' => $this->callback_query_id,
            'text' => $this->text,
            'show_alert' => $this->show_alert,
            'url' => $this->url,
            'cache_time' => $this->cache_time,
        );

        return makeHTTPRequest($this->botToken, 'answerCallbackQuery', $params);
    }
}

//Updating messages ______________
class BT_editMessageText
{
    public $chat_id;
    public $message_id;
    public $inline_message_id;
    public $text;
    public $parse_mode;
    public $disable_web_page_preview = false;
    public $reply_markup;
    public $botToken;

    public function editMessageText()//,$chat_id,$text,$reply_markup=null)
    {
        $params = array(
            'chat_id' => $this->chat_id,
            'message_id' => $this->message_id,
            'inline_message_id' => $this->inline_message_id,
            'text' => $this->text,
            'parse_mode' => $this->parse_mode,
            'disable_web_page_preview' => $this->disable_web_page_preview,
        );

        if (!is_null($this->reply_markup)) {
            $params['reply_markup'] = json_encode($this->reply_markup);
        }

        return makeHTTPRequest($this->botToken, 'editMessageText', $params);
    }
}

class BT_editMessageCaption
{
    public $chat_id;
    public $message_id;
    public $inline_message_id;
    public $caption;
    public $reply_markup;
    public $botToken;


    public function editMessageCaption()
    {
        $params = array(
            'chat_id' => $this->chat_id,
            'message_id' => $this->message_id,
            'inline_message_id' => $this->inline_message_id,
            'caption' => $this->caption,
        );

        if (!is_null($this->reply_markup)) {
            $params['reply_markup'] = json_encode($this->reply_markup);
        }

        return makeHTTPRequest($this->botToken, 'editMessageCaption', $params);
    }
}

class BT_editMessageReplyMarkup
{
    public $chat_id;
    public $message_id;
    public $inline_message_id;
    public $reply_markup;
    public $botToken;

    public function editMessageReplyMarkup()//($token,$chat_id,$photo)
    {
        $params = array(
            'chat_id' => $this->chat_id,
            '$message_id' => $this->message_id,
            //'$inline_message_id'=> $this->inline_message_id,
            'reply_markup' => json_encode($this->reply_markup)

        );
        return makeHTTPRequest($this->botToken, 'editMessageReplyMarkup', $params);
    }
}

class BT_deleteMessage
{
    public $chat_id;
    public $message_id;
    public $botToken;


    public function deleteMessage()
    {
        $params = array(
            'chat_id' => $this->chat_id,
            'message_id' => $this->message_id,
        );

        return makeHTTPRequest($this->botToken, 'deleteMessage', $params);
    }
}

//Stickers ________________________ khali
class BT_Sticker
{

}

class BT_StickerSet
{

}

class BT_MaskPosition
{

}

class BT_sendSticker
{
    public $chat_id;
    public $sticker;
    public $disable_notification;
    public $reply_to_message_id;
    public $reply_markup;
    public $botToken;

    public function sendSticker()
    {
        $params = array(
            'chat_id' => $this->chat_id,
            'sticker' => $this->sticker,
            'disable_notification' => $this->disable_notification,
            'reply_to_message_id' => $this->reply_to_message_id,
        );
        if (!is_null($this->reply_markup)) {
            $params['reply_markup'] = json_encode($this->reply_markup);
        }

        return makeHTTPRequest($this->botToken, 'sendSticker', $params);
    }
}

class BT_getStickerSet
{

}

class BT_uploadStickerFile
{

}

class BT_createNewStickerSet
{

}

class BT_addStickerToSet
{

}

class BT_setStickerPositionInSet
{

}

class BT_deleteStickerFromSet
{

}

//Inline mode _____________________ khali
class BT_answerInlineQuery
{
    public $botToken;
    public $inline_query_id;
    public $results;
    public $cache_time;
    public $is_personal;
    public $next_offset;
    public $switch_pm_text;
    public $switch_pm_parameter;

    public function answerInlineQuery()
    {
        $params = array(
            'inline_query_id' => $this->inline_query_id,
//            'results' => $this->results,
            'cache_time' => $this->cache_time,
            'is_personal' => $this->is_personal,
            'next_offset' => $this->next_offset,
            'switch_pm_text' => $this->switch_pm_text,
            'switch_pm_parameter' => $this->switch_pm_parameter

        );
        if (!is_null($this->results)) {
            $params['results'] = json_encode($this->results);
        }
        return makeHTTPRequest($this->botToken, 'answerInlineQuery', $params);
    }
}

//Inline mode _ InlineQueryResult ______________ kahli
class BT_InlineQueryResultArticle
{

}

class BT_InlineQueryResultPhoto
{

}

class BT_InlineQueryResultGif
{

}

class BT_InlineQueryResultMpeg4Gif
{

}

class BT_InlineQueryResultVideo
{

}

class BT_InlineQueryResultAudio
{

}

class BT_InlineQueryResultVoice
{

}

class BT_InlineQueryResultDocument
{

}

class BT_InlineQueryResultLocation
{

}

class BT_InlineQueryResultVenue
{

}

class BT_InlineQueryResultContact
{

}

class BT_InlineQueryResultGame
{

}

class BT_InlineQueryResultCachedPhoto
{

}

class BT_InlineQueryResultCachedGif
{

}

class BT_InlineQueryResultCachedMpeg4Gif
{

}

class BT_InlineQueryResultCachedSticker
{

}

class BT_InlineQueryResultCachedDocument
{

}

class BT_InlineQueryResultCachedVideo
{

}

class BT_InlineQueryResultCachedVoice
{

}

class BT_InlineQueryResultCachedAudio
{

}

//Inline mode _ InputMessageContent ________________ khali
class BT_InputTextMessageContent
{

}

class BT_InputLocationMessageContent
{

}

class BT_InputVenueMessageContent
{

}

class BT_InputContactMessageContent
{

}

class BT_ChosenInlineResult
{

}

//Payments __________________________ khali
class BT_sendInvoice
{

}

class BT_answerShippingQuery
{

}

class BT_answerPreCheckoutQuery
{

}

class BT_LabeledPrice
{

}

class BT_Invoice
{

}

class BT_ShippingAddress
{

}

class BT_OrderInfo
{

}

class BT_ShippingOption
{

}

class BT_SuccessfulPayment
{

}

class BT_ShippingQuery
{

}

class BT_PreCheckoutQuery
{

}

//Games _______________________ khali
class BT_sendGame
{

}

class BT_Game
{

}

class BT_Animation
{

}

//Games _ CallbackGame__________________
class BT_setGameScore
{

}

class BT_getGameHighScores
{

}

class BT_GameHighScore
{

}
class BT_approveChatJoinRequest
{
    public $chat_id;
    public $user_id;
    public $botToken;

    public function approveChatJoinRequest()
    {
        $params = array(
            'chat_id' => $this->chat_id,
            'user_id' => $this->user_id,
        );

        return makeHTTPRequest($this->botToken, 'approveChatJoinRequest', $params);
    }
}
class BT_declineChatJoinRequest
{
    public $chat_id;
    public $user_id;
    public $botToken;

    public function declineChatJoinRequest()
    {
        $params = array(
            'chat_id' => $this->chat_id,
            'user_id' => $this->user_id,
        );
        return makeHTTPRequest($this->botToken, 'declineChatJoinRequest', $params);
    }
}
class BT_createChatInviteLink
{
    public $chat_id;
    public $name;
    public $expire_date;
    public $member_limit;
    public $creates_join_request;
    public $botToken;

    public function createChatInviteLink()
    {
        $params = array(
            'chat_id' => $this->chat_id,
            'name' => $this->name,
            'expire_date' => $this->expire_date,
            'member_limit' => $this->member_limit,
            'creates_join_request' => $this->creates_join_request,
        );
        return makeHTTPRequest($this->botToken, 'createChatInviteLink', $params);
    }
}


?>