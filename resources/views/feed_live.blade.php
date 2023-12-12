<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>貓院 - 直播餵食</title>
    <link rel="stylesheet" href="{{ asset("css/button-effects.css") }}">
    <link rel="stylesheet" href="{{ asset("css/feed_live.css") }}">
</head>
<body>

<div id="app">
    <div id="chat-container">
        <div id="iframe-container">
            <iframe id="iframe" src="{{ env("FEED_LIVE_URL", "http://127.0.0.1:8000") }}"></iframe>
        </div>
        <div id="chat-text"></div>
        <div id="feed-button"><button id="btnSend" class="btnEf"> Feed </button></div>
    </div>
</div>

<script src="{{ Vite::asset('resources/js/app.js') }}"></script>
<script type="module">
    let chatId = "{{ $chatId?? 1 }}";
    let txtInput = document.getElementById("txtInput");
    let txtShow = "餵食了貓貓";
    let name = "{{ $username?? '神秘人' }}";

    // 送出留言
    document.getElementById("btnSend").addEventListener("click", function(e) {
        Echo.connector.pusher.send_event('Send_Message', {
            username: name,
            massage: txtShow,
            channel: `private-chat.${chatId}`,
        });
    });

    // 開啟WebSocket
    Echo.private(`chat.${chatId}`);

    // 接收到廣播的後續處理
    Echo.connector.pusher.connection.bind('message',function (e) {
        if (e.event==='Send_Message') {
            let username = e.data.username;
            let message = e.data.massage;
            sendMessage(`${username} : ${message}`);
            sendMessage(`系統 : 15秒後方可再餵食`, 'red');
            sendPost("/api/feed/cat", {"iot_cat_box_id": "{{ $iotId }}" })
        }
    })
</script>

<script>
    updateWindowSize();

    /**
     * 動態抓取螢幕
     */
    window.addEventListener('resize', updateWindowSize);
    function sendMessage(message = null, color = null) {
        if (message !== '') {
            const chatText = document.getElementById('chat-text');
            const messageElement = document.createElement('div');
            messageElement.textContent = message;
            if (color !== null) {
                messageElement.style = `color: ${color}; box-shadow: 0 1px;`;
            }
            chatText.appendChild(messageElement);
        }
    }

    /**
     * 依照螢幕大小縮放按鈕移動位置
     * 沒有百分百完全一致
     */
    function updateWindowSize() {
        const windowWidth = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth;
        const windowHeight = window.innerHeight || document.documentElement.clientHeight || document.body.clientHeight;
        const height = (windowWidth  * 0.5625) - 100;
        document.getElementById('iframe').style = `height: ${windowHeight}px; width: ${windowWidth}px`;
        document.getElementById('feed-button').style = `position: absolute; top: ${(windowWidth  * 0.5625)/2}px; right: 30px;`;

        document.getElementById('chat-text').style.top = `${height - 20}px`;
        document.getElementById('chat-text').style.fontSize = `${windowWidth / 40}px`;
        document.getElementById('btnSend').style.width = `${(windowWidth  * 0.5625) / 3}px`;
        document.getElementById('btnSend').style.fontSize = `${(windowWidth  * 0.5625) / 10}px`;
    }

    /**
     * 發送POST
     *
     * @param route
     * @param jsonBody
     */
    function sendPost(route, jsonBody) {
        fetch("{{ env("APP_URL") }}" + route, {
            method: 'post',
            headers: {
                "Content-Type": "application/json",
                "Authorization": "Bearer {{ $authToken?? false }}"
            },
            body: JSON.stringify(jsonBody)
        }).then( (httpResponse) => {
            if (httpResponse.ok) {
                return httpResponse.json();
            } else {
                return Promise.reject("Fetch did not succeed");
            }
        }).catch(err => console.log(err));
    }
</script>
</body>
</html>
