#include <Arduino.h>
#define ST(A) #A
#define STR(A) ST(A)

// Display
#include <TFT_eSPI.h>
#include <qrcode_espi.h>
TFT_eSPI tft = TFT_eSPI(135, 240); // Invoke custom library
QRcode_eSPI qrcode(&tft);

// provisioning
#include "esp_wifi.h"
#include <WiFi.h>
#include <WiFiManager.h> // https://github.com/tzapu/WiFiManager
WiFiManager wifiManager;
WiFiManagerParameter backendServer("backendServer", "Server URL", "http://example.com/api/backend?token=ABCDEF12345678", 260);

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
    // create qrcode and show config
    qrcode.init();
    qrcode.create("WIFI:S:" + ssid + ";T:" + security + ";P:" + password + ";;");
    WiFi.setHostname("Bus-Rider-Reader");
    tft.setTextDatum(MC_DATUM);
    tft.setTextSize(2);
    tft.setTextColor(TFT_BLACK);
    tft.drawString("Configure", 0, 10);
    tft.drawString("using Wifi", 0, 25);
}

void saveParamsCallback()
{
    Serial.println("Get Params:");
    Serial.print(backendServer.getID());
    Serial.print(" : ");
    Serial.println(backendServer.getValue());
}

// Helpers
#include <ArduinoJson.h>
#include <WiFiClient.h>
#include <WiFiClientSecure.h>
#include <HTTPClient.h>

// Time
#include "time.h"
const long gmtOffset_sec = 3600;
const int daylightOffset_sec = 3600;

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
                            Serial.println("Btn 1 pressed"); 
                            wifiManager.startConfigPortal("Bus-Reader", WiFi.macAddress().c_str()); 
                            portalRunning = true; });
    btn2.setPressedHandler([](Button2 &b)
                           {
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

// card readers
#include <PN532_HSU.h>
#include <PN532.h>
#include "Timer.h"
Timer readerTimer;
uint16_t TimerEventPN532 = 0;
uint16_t TimerEventEM4100 = 0;
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
            TimerEventClear = readerTimer.after(2000, resetLastCard);
        }
        else
        {
            // save new card
            memcpy(&last_card, &temp_card, sizeof(card_descriptor));
            sendCard();
            TimerEventClear = readerTimer.after(2000, resetLastCard);
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
            TimerEventClear = readerTimer.after(2000, resetLastCard);
        }
        else
        {
            // save new card
            memcpy(&last_card, &temp_card, sizeof(card_descriptor));
            sendCard();
            TimerEventClear = readerTimer.after(2000, resetLastCard);
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
    memcpy(&last_card, &temp_card, sizeof(card_descriptor));

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
    http.begin(client, backendServer.getValue());
    String json;
    serializeJson(doc, json);
    Serial.println(json);
    int httpResponseCode = http.POST(json);

    if (httpResponseCode == 200)
    {
        Serial.println(http.getString());
        // JsonDocument docResult;
        // deserializeJson(docResult, http.getStream());
        // // Read values
        // Serial.println(docResult.getData());
        // Serial.println(docResult["time"].as<long>());
    }
    else
    {
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

// Callback function for help command
void helpCallback(cmd *c)
{
    Command cmd(c);
    Serial.println("wifi\t\t\t\t\t|WLAN Kommandos");
    Serial.println("\tstatus\t\t\t\t|WLAN Zustand");
    Serial.println("\tconnect *SSID* *PASSWD*\t\t|verbinde mit WLAN Netzwerk");
    Serial.println("\tdisconnect \t\t\t|trenne WLAN Netzwerk");
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

void setup()
{
    Serial.begin(115200);
    // Serial Port for PN532
    Serial1.begin(115200, SERIAL_8N1, 33, 32);
    Serial1.setTimeout(250);
    // Serial Port for Metratec
    Serial2.begin(9600, SERIAL_8N1, 27, 26);
    Serial2.setTimeout(500);

    Serial.println("Booting");
    Serial.print("ESP-MDB-Master Interface vers.:");
    Serial.println(STR(CODE_VERSION));

    cli.setOnError(errorCallback); // Set error Callback
    help = cli.addCommand("help", helpCallback);
    wifi = cli.addBoundlessCommand("wifi", wifiCallback);
    reboot = cli.addCommand("reboot", rebootCallback);

    // Buttons
    button_init();

    // display
    tft.init();
    tft.setRotation(1);
    tft.fillScreen(TFT_BLACK);
    tft.setTextSize(2);
    tft.setTextColor(TFT_GREEN);
    tft.setCursor(0, 0);
    tft.setTextDatum(MC_DATUM);
    tft.setTextSize(1);

    /*
    if (TFT_BL > 0) {                           // TFT_BL has been set in the TFT_eSPI library in the User Setup file TTGO_T_Display.h
        pinMode(TFT_BL, OUTPUT);                // Set backlight pin to output mode
        digitalWrite(TFT_BL, TFT_BACKLIGHT_ON); // Turn backlight on. TFT_BACKLIGHT_ON has been set in the TFT_eSPI library in the User Setup file TTGO_T_Display.h
    }
    */
    tft.setSwapBytes(true);
    // tft.pushImage(0, 0, 240, 135, ttgo);
    // espDelay(5000);

    tft.setRotation(0);

    // WiFi
    wifiManager.setAPCallback(configModeCallback);
    wifiManager.setSaveParamsCallback(saveParamsCallback);
    wifiManager.addParameter(&backendServer);
    wifiManager.setConfigPortalBlocking(false);
    wifiManager.setConfigPortalTimeout(180);
    wifiManager.setConnectRetries(5);
    wifiManager.setConnectTimeout(30);
    wifiManager.setHostname("Bus-Rider-Reader");
    wifiManager.setEnableConfigPortal(false);
    wifiManager.autoConnect();

    // start Readers

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

    readerActive = true;
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

    // timezone
    struct tm timeinfo;
    getLocalTime(&timeinfo);
    time_t now;
    time(&now);
    if (now <= 10000)
    {
        configTime(gmtOffset_sec, daylightOffset_sec, "0.cz.pool.ntp.org", "1.cz.pool.ntp.org", "2.cz.pool.ntp.org");
    }

    // put your main code here, to run repeatedly:
    if (portalRunning)
    {
        wifiManager.process(); // do processing

        // check for timeout
        if ((millis() - startTime) > (timeout * 1000))
        {
            Serial.println("portaltimeout");
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
                qrcode.create("http://" + WiFi.softAPIP().toString()); // config url
            }
        }
    }
}