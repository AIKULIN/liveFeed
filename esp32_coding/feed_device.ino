#include <WiFiManager.h>
#include <HTTPClient.h>
#include <WiFiClient.h>
#include <WiFi.h>
#include "nvs_flash.h"
#include <ArduinoJson.h>
#include "arduino_base64.hpp"

// 清除wifi 設定宣告
unsigned long buttonPressedTime = 0;
bool buttonPressed = false;

// 初始AP
WiFiManager wifiManager;

// ESP
const uint64_t chipid = ESP.getEfuseMac(); // 取得MAC位址的低6位元組
String chipID;

// api 服務
const char* apiUrl = "http://127.0.0.1:8000"; // 使用域名會出錯，故使用ip + port
const char* apiKey = "1234567890"; // 修改與ENV 的IOT_KEY 相同

// 設定定時器初始值
int timerSetPostData = 5000000; // 5秒

hw_timer_t * timer0 = NULL;
volatile bool timer0Elapsed = false;
void IRAM_ATTR onTimer0() {
  timer0Elapsed = true;
}

String response = "";
StaticJsonDocument<200> doc; // 全局 JSON 對象

#define RESET_THRESHOLD 10000 // 按鈕按壓閾值（毫秒）
#define CLASE_WIFI_BUTTON_PIN 0 // 清除wifi 設定按鈕腳位
#define RED_LED_BUILTIN 33 // esp32 板子紅燈腳位
#define PIN_FEED_ON 13 // 觸發NPN餵食器

void setup() {
    Serial.begin(115200);
    // 取得ESP32的晶片ID
    getChipID();
    Serial.println("chipID: " + String(chipID));
    // wifi 連線管理頁設定
    wifiConnectSetup();
    // 伺服器讀取設定
    reloadSetting();
    // 初始化定時器
    timerSetup();
    pinMode(CLASE_WIFI_BUTTON_PIN, INPUT_PULLUP);
    pinMode(RED_LED_BUILTIN, OUTPUT);

    Serial.println("載入完成");
}

/**
* 抓取遠端設定值。設備若未註冊，將自動新增
**/
void reloadSetting() {
    String request[] = {"iotKey", "chipId"};
    String data[] = {apiKey, chipID};
    String route = "/api/feed/iot/setting";
    postToApi(route, request, data, 1); // 2是陣列的長度

    parseJson();
    timerSetPostData = doc["timer_0_time"];
}

/**
* 初始化定時器
**/
void timerSetup() {
  // 初始化並啟動定時器0
  timer0 = timerBegin(0, 80, true); // 使用第一個定時器，預分頻80
  timerAttachInterrupt(timer0, &onTimer0, true);
  timerAlarmWrite(timer0, timerSetScreen, false);
  timerAlarmEnable(timer0);
}

/**
* 運行第一定時器 顯示wifi 強弱
**/
void timer0Run() {
  if (timer0Elapsed) {
    postFeedToCat();
    timer0Elapsed = false;
  }
}

/**
* 取得ESP32的晶片ID
**/
void getChipID() {
    chipID = String((uint32_t)(chipid >> 32), HEX); // 將32位元的高位轉換為十六進位字串
    chipID += String((uint32_t)chipid, HEX); // 將32位元的低位元轉換為十六進位字串，並拼接
    chipID.toUpperCase();
    Serial.println(chipID);
}

/**
* wifi 連線管理頁設定
**/
void wifiConnectSetup() {
      wifiManager.setConnectTimeout(30); // 設定連線超時時間為180秒
      wifiManager.setDebugOutput(false); // 停用輸出除錯訊息
      wifiManager.setScanDispPerc(true); // 設定顯示wifi 訊號百分比
      wifiManager.setMinimumSignalQuality(10); // 設定最低訊號強度百分比
      //自訂存取點 IP 配置
      wifiManager.setAPStaticIPConfig(IPAddress(10,0,1,1), IPAddress(10,0,1,1), IPAddress(255,255,255,0));
      String apName = String(".Feed_Device") + "_" + String(chipID);
      wifiManager.autoConnect(apName.c_str());

      // 獲取連線後訊號強度
      int rssi = WiFi.RSSI();

    if (rssi != 0) {
      Serial.println("已連接到WiFi:" + WiFi.localIP().toString());
      digitalWrite(RED_LED_BUILTIN, LOW); // 連線亮紅燈
    }
}

/**
* 抓取 nav flash 已儲存wifi ssid 名稱
* 無資料時為空
**/
String getNavFlashWifiSsid() {
   // 初始化 WiFi，設定為 STA 模式
  WiFi.mode(WIFI_STA);
  wifi_config_t wifiConfig;

  // 取得目前的 WiFi 配置
  if (esp_wifi_get_config(WIFI_IF_STA, &wifiConfig) == ESP_OK) {
    return String(reinterpret_cast<char*>(wifiConfig.sta.ssid));
  }
  return String();
}

/**
* 紅燈閃爍
**/
void redFlashing() {
    digitalWrite(RED_LED_BUILTIN, HIGH);
    delay(200);
    digitalWrite(RED_LED_BUILTIN, LOW);
}

/**
* 清除WiFi設定並重啟
**/
void clearWifiButton() {
    if (digitalRead(CLASE_WIFI_BUTTON_PIN) == LOW) {
        if (!buttonPressed) {
            buttonPressedTime = millis();
            buttonPressed = true;
        } else if (millis() - buttonPressedTime > RESET_THRESHOLD) {
            Serial.println("清除WiFi設定並重啟");
            WiFi.disconnect(true);
            redFlashing();
            delay(1000);
            nvs_flash_erase();
            esp_restart();
        }
    } else {
        buttonPressed = false;
    }
}

/**
* 讀取餵食資料
**/
void postFeedToCat() {
    String request[] = {"key"};
    String data[] = {chipID};
    String route = "/api/feed/iot/" + chipID;
    postToApi(route, request, data, 1);

    // 解析response
    String goFeed = base64Decrypt(response.c_str());
    // 確認是否開啟餵食
    if (goFeed == chipID + "goFeed") {
      runFeed();
      redFlashing();
    }
}

// 解碼
String base64Decrypt(const char* input) {
    uint8_t output[base64::decodeLength(input)]; // 確保輸出緩衝區足夠大
    base64::decode(input, output); // 進行 Base64 解碼

    return String((char*)output); // 將解碼後的數據轉換為 String
}

/**
* 將數據POST到API的函數
* route api 路徑
* request 要帶入參數
* data 參數內的資料
* length 共有幾個參數
**/
void postToApi(String route, String request[], String data[], int length) {
    HTTPClient http;
    WiFiClient client;
    delay(1000);
    http.begin(client, apiUrl + route);
    http.addHeader("Content-Type", "application/x-www-form-urlencoded");

    // 元素組合成一個查詢字符串
    String httpRequestData;
    for (int i = 0; i < length; i++) {
        httpRequestData += request[i] + "=" + data[i];
        if (i < length - 1) {
            httpRequestData += "&";
        }
    }
    int httpResponseCode = http.POST(httpRequestData);

    if (httpResponseCode > 0) {
        response = http.getString();
        Serial.println("HTTP Response code: " + String(httpResponseCode));
        Serial.println("Response: " + String(response));
    }
    else {
        Serial.println("httpRequestData: " + String(http.errorToString(httpResponseCode)));
    }
    http.end();
}

void parseJson() {
  DeserializationError error = deserializeJson(doc, response);
  if (error) {
    Serial.print(F("deserializeJson() 失敗: "));
    Serial.println(error.c_str());
  }
}

/**
* 運行餵食器開關
**/
void runFeed() {
  pinMode(PIN_FEED_ON, OUTPUT);
  delay(200);
  pinMode(PIN_FEED_ON, INPUT);
}


void loop() {
  // 長按10秒下按鈕 清除wifi
  clearWifiButton();
  // 訂時器
  timer0Run();
}
