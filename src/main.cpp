#include <Arduino.h>
#define ST(A) #A
#define STR(A) ST(A)

// Storage
#include <SPIFFS.h>
#include <FS.h>

// event queues
#include "eventQueueBase.hpp"

// Audio
//  only for passive beeper
//  #include "beeper.h"
//  extern beeper beepr;
//  extern eventHandler<uint8_t> beeperEvents;

// Display
#include <TFT_eSPI.h>
TFT_eSPI tft = TFT_eSPI(135, 240); // Invoke custom library
#include <qrcode_espi.h>
QRcode_eSPI qrcode = QRcode_eSPI(&tft);

#include "DisplayDriver.h" //display handler task
DisplayDriver DisplayDriverInstance(&tft, TFT_BL, &qrcode);
void displayRun(void *args)
{
    while (true)
    {
        DisplayDriverInstance.loop();
        vTaskDelay(10);
    }
}

// provisioning
#include "esp_wifi.h"
#include <WiFi.h>
#include <WiFiManager.h> // https://github.com/tzapu/WiFiManager
WiFiManager wifiManager;
WiFiManagerParameter backendServer("backendServer", "Server URL", "https://example.com/api/backend", 260);
WiFiManagerParameter token("token", "Token", "ABCDEF12345678", 26);

unsigned int timeout = 180; // seconds to run for
unsigned int startTime = millis();
bool portalRunning = false;
bool startAP = false; // start AP and webserver if true, else start only webserver
bool drawnConfigQR = false;

void configModeCallback(WiFiManager *myWiFiManager)
{
    String ssid = myWiFiManager->getConfigPortalSSID();
    String security = "WPA2";                    // "WEP", "WPA", "WPA2", "WPA3" or "nopass" for open
    String password = WiFi.macAddress().c_str(); // Password, ignored if security is "nopass"

    // create qr code and show config
    DisplayDriverInstance.drawQR("WIFI:S:" + ssid + ";T:" + security + ";P:" + password + ";;");
    WiFi.setHostname("Bus-Rider-Reader");
}

// buttons
#include "Button2.h"
#define BUTTON_1 35
#define BUTTON_2 0
Button2 btn1(BUTTON_1);
Button2 btn2(BUTTON_2);

void button_init()
{
    btn1.setPressedHandler([](Button2 &b)
                           { 
                            Serial.println("Btn 1 short pressed"); 
                            wifiManager.startConfigPortal("Bus-Reader", WiFi.macAddress().c_str()); 
                            // create qr code and show config
                            DisplayDriverInstance.drawQR("WIFI:S:" + wifiManager.getConfigPortalSSID() + ";T:WAP2;P:" + WiFi.macAddress().c_str() + ";;");
                            WiFi.setHostname("Bus-Rider-Reader");
                            startTime = millis();
                            portalRunning = true; });
    btn2.setPressedHandler([](Button2 &b)
                           {
                            Serial.println("Btn 1 long pressed"); 
        if (!portalRunning)
        {
            if (startAP)
            {
                Serial.println("Button Pressed, Starting Config Portal");
                wifiManager.setConfigPortalBlocking(false);
                wifiManager.startConfigPortal("Bus-Reader", WiFi.macAddress().c_str());
            }
            else
            {
                Serial.println("Button Pressed, Starting Web Portal");
                wifiManager.startWebPortal();
            }
            portalRunning = true;
            startTime = millis();
        } });
}

// Helpers
#include <ArduinoJson.h>
#include <WiFiClient.h>
#include <WiFiClientSecure.h>
#include <HTTPClient.h>

// config
struct Config
{
    char server[64];
    char token[17];
};
const char *configFilename = "/config.txt";
Config config;

void loadConfiguration()
{
    File configFile = SPIFFS.open(configFilename);
    if (!configFile)
    {
        Serial.println(F("Failed to open config file"));
        return;
    }
    JsonDocument doc;
    DeserializationError error = deserializeJson(doc, configFile);
    strlcpy(config.server,
            doc["server"] | "example.com",
            sizeof(config.server));
    strlcpy(config.token,
            doc["token"] | "ABCDEF123456789",
            sizeof(config.token));
    configFile.close();
}

// Saves the configuration to a file
void saveConfiguration()
{
    // get values from wifi manager
    strlcpy(config.server, backendServer.getValue(), _min((unsigned int)backendServer.getValueLength(), sizeof(config.server)));
    strlcpy(config.token, token.getValue(), _min((unsigned int)token.getValueLength(), sizeof(config.token)));

    SPIFFS.remove(configFilename);
    File configFile = SPIFFS.open(configFilename, FILE_WRITE);
    if (!configFile)
    {
        Serial.println(F("Failed to open config file"));
        return;
    }
    JsonDocument doc;
    doc["server"] = config.server;
    doc["token"] = config.token;

    if (serializeJson(doc, configFile) == 0)
    {
        Serial.println(F("Failed to write to file"));
    }
    configFile.close();
    DisplayDriverInstance.setTimeout(10, true);
}

// Time
#include "time.h"
const long gmtOffset_sec = 3600;
const int daylightOffset_sec = 3600;

// card readers
#include <PN532_HSU.h>
#include <PN532.h>
#include "Timer.h"
Timer readerTimer;
uint16_t TimerEventPN532 = 0;
uint16_t TimerEventEM4100 = 0;
uint16_t TimerTimezone = 0;
uint16_t TimerEventClear = 0;
PN532_HSU pn532hsu(Serial1);
PN532 nfc(pn532hsu);
void pn532Scan();
void em4100Scan();
void sendReaderState(char *msg);
namespace cardReaders
{
    enum reader
    {
        NONE = 99,
        PN532 = 0,
        MRFC522 = 1,
        RDM6300 = 2,
        metraTecISO15693 = 10
    };
} // namespace cardReaders

typedef struct
{
    cardReaders::reader source;
    char id[33];
} card_descriptor;

card_descriptor temp_card;
card_descriptor last_card;
bool readerActive = false;

void resetLastCard();
void sendCard();

void pn532Scan()
{
    nfc.setRFField(0, 1);                  // switch field on
    uint8_t uid[] = {0, 0, 0, 0, 0, 0, 0}; // Buffer to store the returned UID
    uint8_t uidLength;                     // Length of the UID (4 or 7 bytes depending on ISO14443A card type)
    if (nfc.readPassiveTargetID(PN532_MIFARE_ISO14443A, &uid[0], &uidLength, 100))
    {
        temp_card.source = cardReaders::PN532;
        memset(temp_card.id, 0, sizeof(temp_card.id));
        for (uint8_t i = 0; i < uidLength; i++)
        {
            char buff[3];
            sprintf(buff, "%02X", uid[i]);
            strcat(temp_card.id, buff);
        }
        if (!memcmp(&last_card, &temp_card, sizeof(card_descriptor)))
        {
            readerTimer.stop(TimerEventClear);
            readerTimer.stop(TimerEventEM4100);
            readerTimer.stop(TimerEventPN532);
            TimerEventClear = readerTimer.after(1000, resetLastCard);
        }
        else
        {
            // save new card
            memcpy(&last_card, &temp_card, sizeof(card_descriptor));
            sendCard();
            TimerEventClear = readerTimer.after(1000, resetLastCard);
        }
    }
    nfc.setRFField(0, 0); // switch field off
}

void em4100Scan()
{
    char response[128];
    uint response_len = 0;

    // Serial2.readBytesUntil(0x20, response, response_len);
    for (response_len = 0; Serial2.available() && response_len < sizeof(response); response_len++)
    {
        char readByte = Serial2.read();
        if (readByte == ' ')
        {
            break;
        }
        response[response_len] = readByte;
    }
    response[response_len++] = '\0';
    while (Serial2.available())
    {
        Serial2.read();
    }
    temp_card.source = cardReaders::RDM6300;
    String respString(response);

    memset(temp_card.id, 0, sizeof(temp_card.id));
    respString.trim();
    if (response_len > 4)
    {
        strcpy(temp_card.id, respString.c_str());
        if (!memcmp(&last_card, &temp_card, sizeof(card_descriptor)))
        {
            readerTimer.stop(TimerEventClear);
            readerTimer.stop(TimerEventPN532);
            readerTimer.stop(TimerEventEM4100);
            TimerEventClear = readerTimer.after(1000, resetLastCard);
        }
        else
        {
            // save new card
            memcpy(&last_card, &temp_card, sizeof(card_descriptor));
            sendCard();
            TimerEventClear = readerTimer.after(1000, resetLastCard);
        }
    }
}

void resetLastCard()
{
    Serial.println("reset last Card");
    readerTimer.stop(TimerEventClear);
    readerTimer.every(200, pn532Scan);
    readerTimer.every(200, em4100Scan);

    // clear last known card
    last_card.source = cardReaders::NONE;
    memset(last_card.id, 0, sizeof(last_card.id));
    // clear reader result
    temp_card.source = cardReaders::NONE;
    memset(temp_card.id, 0, sizeof(temp_card.id));
}

void sendReaderState(char *msg)
{
    JsonDocument doc;
    doc["message"] = msg;
    doc["active"] = readerActive;

    serializeJson(doc, Serial);
    Serial.println();
}

void sendCard()
{
    // Beep
    digitalWrite(12, HIGH);
    delay(500);
    digitalWrite(12, LOW);
    DisplayDriverInstance.drawBmp("/login.bmp", 0, 0);

    memcpy(&last_card, &temp_card, sizeof(card_descriptor));
    Serial.println("sending card");
    // Timestamp for JSON
    struct tm timeinfo;
    time_t now;
    time(&now);

    // New Card Found:
    JsonDocument doc;

    doc["source"] = last_card.source;
    doc["id"] = last_card.id;
    doc["timestamp"] = now;

    // send to webserver
    WiFiClientSecure client;
    client.setInsecure();
    HTTPClient http;
    http.useHTTP10(true);
    // http.setFollowRedirects(HTTPC_FORCE_FOLLOW_REDIRECTS);
    char url[128] = {0};
    strcat(url, config.server);
    strcat(url, "/action-card?token=");
    strcat(url, config.token);

    http.begin(client, url);

    String json;
    serializeJson(doc, json);
    Serial.println(json);
    int httpResponseCode = http.POST(json);
    if (httpResponseCode == 200)
    {
        Serial.println(http.getString());
        // JsonDocument docResult;
        // deserializeJson(docResult, http.getStream());
        // // Read values from the result
        // Serial.println(docResult.getData());
        // Serial.println(docResult["time"].as<long>());
        // Serial.println(docResult["message"].as<String>());
        // draw first logo
        delay(1000);
        DisplayDriverInstance.drawBmp("/logout.bmp", 0, 0);
        DisplayDriverInstance.setTimeout(1000, true);
    }
    else
    {
        DisplayDriverInstance.drawBmp("/save.bmp", 0, 0);
        DisplayDriverInstance.setTimeout(1000, true);

        // TODO save data to file and ram
        Serial.print("HTTP Response code: ");
        Serial.println(httpResponseCode);
    }
    // Free resources
    http.end();
}

// Serial Console Command Line Interface
#include <SimpleCLI.h>

// Create CLI Object
SimpleCLI cli;

// Commands
Command help;
Command wifi;
Command reboot;
Command iconUpdate;
Command download;
Command show;
Command test;

// Callback function for help command
void helpCallback(cmd *c)
{
    Command cmd(c);
    Serial.println("wifi\t\t\t\t\t|WiFi commands");
    Serial.println("\tstatus\t\t\t\t|WiFi state");
    Serial.println("\tconnect *SSID* *PASSWD*\t\t|connect to wifi");
    Serial.println("\tdisconnect \t\t\t|disconnect wifi");
    Serial.println("iconUpdate\t\t\t\t\t|refresh logo image");
    Serial.println("download\t*url*\t*file\t\t\t|download url to file");
    Serial.println("show\t*file*\t\t\t\t|show image on screen");
}

void printWiFiStatus()
{
    switch (WiFi.status())
    {
    case WL_NO_SSID_AVAIL:
        Serial.println("[WiFi] SSID not found");
        break;
    case WL_CONNECT_FAILED:
        Serial.print("[WiFi] Failed - WiFi not connected! Reason: ");
        return;
        break;
    case WL_CONNECTION_LOST:
        Serial.println("[WiFi] Connection was lost");
        break;
    case WL_SCAN_COMPLETED:
        Serial.println("[WiFi] Scan is completed");
        break;
    case WL_DISCONNECTED:
        Serial.println("[WiFi] WiFi is disconnected");
        break;
    case WL_CONNECTED:
        Serial.println("[WiFi] WiFi is connected!");
        Serial.print("[WiFi] IP address: ");
        Serial.println(WiFi.localIP());
        break;
    default:
        Serial.print("[WiFi] WiFi Status: ");
        Serial.println(WiFi.status());
        break;
    }
}

// Callback function for wifi command
void wifiCallback(cmd *c)
{
    Command cmd(c); // Create wrapper object
    int numArgs = cmd.countArgs();
    if (!numArgs)
    {
        Serial.println("zu wenige Argumente");
        return;
    }
    Argument argSubCmd = cmd.getArg(0);
    String subCmd = argSubCmd.getValue();
    if (!strcmp(subCmd.c_str(), "status"))
    {
        printWiFiStatus();
    }
    else if (!strcmp(subCmd.c_str(), "connect") && numArgs > 2)
    {
        WiFi.disconnect(false, true);
        Argument argSSID = cmd.getArg(1);
        String wiFiName = argSSID.getValue();

        Argument argPw = cmd.getArg(2);
        String wiFiPassword = argPw.getValue();

        WiFi.begin(wiFiName.c_str(), wiFiPassword.c_str());
        int tryDelay = 1000;
        int numberOfTries = 10;

        // Wait for the WiFi event
        while (true)
        {
            printWiFiStatus();
            if (WiFi.status() == WL_CONNECTED)
            {
                return;
            }
            delay(tryDelay);

            if (numberOfTries <= 0)
            {
                Serial.print("[WiFi] Failed to connect to WiFi!");
                // Use disconnect function to force stop trying to connect
                WiFi.disconnect();
                return;
            }
            else
            {
                numberOfTries--;
            }
        }
    }
    else if (!strcmp(subCmd.c_str(), "disconnect"))
    {
        WiFi.disconnect(false, true);
    }
}

//  Callback function for reboot command
void rebootCallback(cmd *c)
{
    ESP.restart();
}

// Callback in case of an error
void errorCallback(cmd_error *e)
{
    CommandError cmdError(e); // Create wrapper object

    Serial.print("ERROR: ");
    Serial.println(cmdError.toString());

    if (cmdError.hasCommand())
    {
        Serial.print("Did you mean \"");
        Serial.print(cmdError.getCommand().toString());
        Serial.println("\"?");
    }
}

bool downloadFile2SPIFFS(const char *filename, const char *url)
{
    File logout_image_file = SPIFFS.open(filename, FILE_WRITE);
    if (logout_image_file)
    {
        Serial.print("downloading from: ");
        Serial.print(url);
        WiFiClientSecure client;
        client.setInsecure();
        HTTPClient http;
        http.useHTTP10(true);
        // http.setFollowRedirects(HTTPC_FORCE_FOLLOW_REDIRECTS);

        http.begin(client, url);
        int httpCode = http.GET();
        if (httpCode > 0)
        {
            if (httpCode == HTTP_CODE_OK)
            {
                http.writeToStream(&logout_image_file);
            }
            Serial.println(" successful");
        }
        else
        {
            Serial.printf("failed, error: %s\n\r", http.errorToString(httpCode).c_str());
        }
        http.end();
        logout_image_file.close();
        return true;
    }
    else
    {
        Serial.println("download cant open local file as download destination");
    }
    return false;
}

// Callback for Logo Update
void iconUpdateCallback(cmd *c)
{
    // Download Logo
    char url[128] = {0};
    strcat(url, config.server);
    strcat(url, "/action-logo?token=");
    strcat(url, config.token);
    if (downloadFile2SPIFFS("/logo.bmp", url))
    {
        DisplayDriverInstance.drawBmp("/logo.bmp", 0, 0);
    }

    // login login
    memset(url, 0, sizeof(url));
    strcat(url, config.server);
    strcat(url, "/action-icon/image-login?token=");
    strcat(url, config.token);
    downloadFile2SPIFFS("/login.bmp", url);

    // logout logout
    memset(url, 0, sizeof(url));
    strcat(url, config.server);
    strcat(url, "/action-icon/image-logout?token=");
    strcat(url, config.token);
    downloadFile2SPIFFS("/logout.bmp", url);

    // save logout
    memset(url, 0, sizeof(url));
    strcat(url, config.server);
    strcat(url, "/action-icon/image-save?token=");
    strcat(url, config.token);
    downloadFile2SPIFFS("/save.bmp", url);
}

void downloadCallback(cmd *c)
{
    Command cmd(c); // Create wrapper object
    int numArgs = cmd.countArgs();
    if (numArgs < 2)
    {
        Serial.println("zu wenige Argumente, url and /filename required");
        return;
    }
    Argument argUrl = cmd.getArg(0);
    String url = argUrl.getValue();

    Argument argFilename = cmd.getArg(1);
    String filename = argFilename.getValue();

    char charFilename[20];
    filename.toCharArray(charFilename, sizeof(charFilename));
    downloadFile2SPIFFS(charFilename, url.c_str());
}

void showCallback(cmd *c)
{
    Command cmd(c); // Create wrapper object
    int numArgs = cmd.countArgs();
    if (!numArgs)
    {
        Serial.println("zu wenige Argumente");
        return;
    }
    Argument argFilename = cmd.getArg(0);
    String filename = argFilename.getValue();

    char charFilename[20];
    filename.toCharArray(charFilename, sizeof(charFilename));
    DisplayDriverInstance.drawBmp(charFilename, 0, 0);
}

bool backGroundLight = false;
// Callback for Test function
void testCallback(cmd *c)
{
    if (TFT_BL > 0)
    {                                          // TFT_BL has been set in the TFT_eSPI library in the User Setup file TTGO_T_Display.h
        digitalWrite(TFT_BL, backGroundLight); // Turn backlight on. TFT_BACKLIGHT_ON has been set in the TFT_eSPI library in the User Setup file TTGO_T_Display.h
        backGroundLight = !backGroundLight;
    }
}

void setup()
{
    Serial.begin(115200);
    // Serial Port for PN532
    Serial1.begin(115200, SERIAL_8N1, 33, 32);
    Serial1.setTimeout(250);
    // Serial Port for EM4100
    Serial2.begin(9600, SERIAL_8N1, 27, 26);
    Serial2.setTimeout(500);

    Serial.println("Booting");
    Serial.print("Bus Rider Reader vers.:");
    Serial.println(STR(CODE_VERSION));

    cli.setOnError(errorCallback); // Set error Callback
    help = cli.addCommand("help", helpCallback);
    wifi = cli.addBoundlessCommand("wifi", wifiCallback);
    reboot = cli.addCommand("reboot", rebootCallback);
    iconUpdate = cli.addCommand("iconUpdate", iconUpdateCallback);
    download = cli.addBoundlessCommand("download", downloadCallback);
    show = cli.addBoundlessCommand("show", showCallback);
    test = cli.addCommand("test", testCallback);

    // Storage
    Serial.println("start storage");
    if(!SPIFFS.begin()){
        Serial.println("not formatted - starting formatting");
        SPIFFS.format();
    }
    Serial.println("SPIFFS Info:");
    Serial.printf("Total Bytes: %u\n", SPIFFS.totalBytes());
    Serial.printf("Used Bytes: %u\n", SPIFFS.usedBytes());
    Serial.printf("Free Bytes: %u\n", SPIFFS.totalBytes() - SPIFFS.usedBytes());

    // Buttons
    Serial.println("buttons");
    button_init();

    // display
    xTaskCreatePinnedToCore(displayRun, "Display Task", 4096, NULL, 1, NULL, 0);

    // WiFi
    loadConfiguration();
    Serial.println("wifi manager");
    wifiManager.setAPCallback(configModeCallback);
    wifiManager.setSaveParamsCallback(saveConfiguration);
    wifiManager.addParameter(&backendServer);
    wifiManager.addParameter(&token);
    wifiManager.setConfigPortalBlocking(false);
    wifiManager.setConfigPortalTimeout(timeout);
    wifiManager.setConnectRetries(3);
    wifiManager.setConnectTimeout(10);
    wifiManager.setHostname("Bus-Rider-Reader");
    wifiManager.setEnableConfigPortal(false);
    wifiManager.autoConnect();

    // start Readers
    Serial.println("start readers");
    //  PN532 start
    nfc.begin();
    if (!nfc.getFirmwareVersion())
    {
        Serial.print("didn't find PN53x module");
        return;
    }
    nfc.SAMConfig();
    TimerEventPN532 = readerTimer.every(200, pn532Scan);
    // PN532 done

    // em4100 start
    TimerEventEM4100 = readerTimer.every(200, em4100Scan);
    // em4100 end

    TimerTimezone = readerTimer.every(2000, []()
                                      {
                                        // timezone
                                        struct tm timeInfo;
                                        getLocalTime(&timeInfo);
                                        time_t now;
                                        time(&now);
                                        if (now <= 10000)
                                        {
                                            configTime(gmtOffset_sec, daylightOffset_sec, "0.cz.pool.ntp.org", "1.cz.pool.ntp.org", "2.cz.pool.ntp.org");
                                            readerTimer.stop(TimerTimezone);
                                        } });

    readerActive = true;

    // draw first logo
    Serial.println("draws logo");
    DisplayDriverInstance.drawBmp("/logo.bmp", 0, 0);

    // setup audio
    // passive buzzer
    // beeperEvents.init();
    // beepr.init();
    // beepr.start();
    //
    // // start sound
    // uint8_t sound = 0;
    // beeperEvents.addEvent(&sound, BEEPER_EVENTS::PLAY_SOUND);
    // active buzzer
    pinMode(12, OUTPUT);
    digitalWrite(12, HIGH);
    delay(300);
    digitalWrite(12, LOW);
    delay(300);
    digitalWrite(12, HIGH);
    delay(300);
    digitalWrite(12, LOW);
}

uint8_t newByte = '\0';
String input = "";

void loop()
{
    // Button loop
    btn1.loop();
    btn2.loop();

    // loop reader
    readerTimer.update();

    // Serial Console
    if (Serial.available())
    {
        newByte = Serial.read();

        if (newByte == '\n')
        {
            Serial.println();
            cli.parse(input);

            input = ""; // reset
            Serial.print("#");
        }
        else if (newByte != '\r' && isPrintable(newByte))
        {
            input += (char)newByte;
            Serial.print((char)newByte);
        }
    }
    if (cli.errored())
    {
        CommandError cmdError = cli.getError();

        Serial.print("ERROR: ");
        Serial.println(cmdError.toString());

        if (cmdError.hasCommand())
        {
            Serial.print("Did you mean \"");
            Serial.print(cmdError.getCommand().toString());
            Serial.println("\"?");
        }
    }

    // put your main code here, to run repeatedly:
    if (portalRunning)
    {
        wifiManager.process(); // do processing

        // check for timeout
        if ((millis() - startTime) > (timeout * 1000))
        {
            Serial.println("portal timeout");
            portalRunning = false;
            if (startAP)
            {
                wifiManager.stopConfigPortal();
            }
            else
            {
                wifiManager.stopWebPortal();
            }
        }
        if (!startAP)
        {
            wifi_sta_list_t wifi_sta_list;
            tcpip_adapter_sta_list_t adapter_sta_list;
            esp_wifi_ap_get_sta_list(&wifi_sta_list);
            tcpip_adapter_get_sta_list(&wifi_sta_list, &adapter_sta_list);

            if (adapter_sta_list.num && !drawnConfigQR)
            {
                drawnConfigQR = true;
                DisplayDriverInstance.drawQR("http://" + WiFi.softAPIP().toString()); // config url
            }
        }
    }
}
