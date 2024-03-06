#pragma once

#ifndef DisplayDriver_h
#define DisplayDriver_h

//includes
#include "Arduino.h"
#include <SPIFFS.h>
#include <FS.h>
#include <TFT_eSPI.h>
// #include <iostream>
#include <qrcode_espi.h>

// using namespace std;

class DisplayDriver {
  private:
    unsigned int lastEventType = 0;
    unsigned long lastupdate_ms;
    unsigned long nextBlank_ms = 0;
    bool screenOffBlank = false;
    bool screenOn = false;
    uint16_t read16(fs::File &f);
    uint32_t read32(fs::File &f);
    QRcode_eSPI * _qrcode;
    TFT_eSPI * _TFT;
    byte _BackgroundPin;
    // typedef void (*CallbackFunction) (Button2&);

    // CallbackFunction pressed_cb = NULL;
    
  public:
    DisplayDriver(TFT_eSPI * tft, byte attachTo, QRcode_eSPI * qrcode); //{} //constructor
    void loop();
    void bgOn();
    void bgOff();
    void drawQR(String message);
    void setTimeout(unsigned long timeout, bool screenOff);

    void drawBmp(const char *filename, int16_t x, int16_t y);
    // DisplayDriver(byte attachTo, byte buttonMode = INPUT_PULLUP, unsigned int debounceTimeout = DEBOUNCE_MS);
    // void setChangedHandler(CallbackFunction f);
    
};
#endif
