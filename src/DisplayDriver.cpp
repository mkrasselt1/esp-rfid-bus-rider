#include "DisplayDriver.h"

/////////////////////////////////////////////////////////////////
// constructor
DisplayDriver::DisplayDriver(TFT_eSPI *TFT, byte attachTo, QRcode_eSPI *qrcode)
{
    _TFT = TFT;
    _BackgroundPin = attachTo;
    _qrcode = qrcode;

    pinMode(_BackgroundPin, OUTPUT);
    digitalWrite(_BackgroundPin, TFT_BACKLIGHT_ON);

    _TFT->init();
    _TFT->setRotation(1);
    _TFT->fillScreen(TFT_BLACK);
    _TFT->setTextColor(TFT_GREEN);
    _TFT->setCursor(0, 0);
    _TFT->setTextDatum(MC_DATUM);
    _TFT->setTextSize(1);
    _TFT->setSwapBytes(true);

    SPIFFS.begin(true);
}

void DisplayDriver::loop()
{
    if (nextBlank_ms && nextBlank_ms <= millis())
    {
        nextBlank_ms = 0;
        _TFT->fillScreen(TFT_BLACK);
        if (screenOffBlank)
        {
            bgOff();
        }
    }
}

void DisplayDriver::drawQR(String message)
{
    _qrcode->init();
    _qrcode->create(message);
}

void DisplayDriver::bgOn()
{
    digitalWrite(_BackgroundPin, TFT_BACKLIGHT_ON);
}

void DisplayDriver::bgOff()
{
    digitalWrite(_BackgroundPin, !TFT_BACKLIGHT_ON);
}

void DisplayDriver::setTimeout(unsigned long timeout, bool screenOff)
{
    nextBlank_ms = millis() + timeout;
    screenOffBlank = screenOff;
}

void DisplayDriver::drawBmp(const char *filename, int16_t x, int16_t y)
{
    bgOn();
    if ((x >= _TFT->width()) || (y >= _TFT->height()))
        return;

    fs::File bmpFS;

    // Open requested file on SD card
    bmpFS = SPIFFS.open(filename, "r");

    if (!bmpFS)
    {
        Serial.print("File not found");
        return;
    }

    uint32_t seekOffset;
    uint16_t w, h, row, col;
    uint8_t r, g, b;

    uint32_t startTime = millis();

    if (read16(bmpFS) == 0x4D42)
    {
        read32(bmpFS);
        read32(bmpFS);
        seekOffset = read32(bmpFS);
        read32(bmpFS);
        w = read32(bmpFS);
        h = read32(bmpFS);

        if ((read16(bmpFS) == 1) && (read16(bmpFS) == 24) && (read32(bmpFS) == 0))
        {
            y += h - 1;

            bool oldSwapBytes = _TFT->getSwapBytes();
            _TFT->setSwapBytes(true);
            bmpFS.seek(seekOffset);

            uint16_t padding = (4 - ((w * 3) & 3)) & 3;
            uint8_t lineBuffer[w * 3 + padding];

            for (row = 0; row < h; row++)
            {

                bmpFS.read(lineBuffer, sizeof(lineBuffer));
                uint8_t *bptr = lineBuffer;
                uint16_t *tptr = (uint16_t *)lineBuffer;
                // Convert 24 to 16-bit colours
                for (uint16_t col = 0; col < w; col++)
                {
                    b = *bptr++;
                    g = *bptr++;
                    r = *bptr++;
                    *tptr++ = ((r & 0xF8) << 8) | ((g & 0xFC) << 3) | (b >> 3);
                }

                // Push the pixel row to screen, pushImage will crop the line if needed
                // y is decremented as the BMP image is drawn bottom up
                _TFT->pushImage(x, y--, w, 1, (uint16_t *)lineBuffer);
            }
            _TFT->setSwapBytes(oldSwapBytes);
            // Serial.print("Loaded in ");
            // Serial.print(millis() - startTime);
            // Serial.println(" ms");
        }
        else
            Serial.println("BMP format not recognized.");
    }
    bmpFS.close();
    bgOn();
}

// These read 16- and 32-bit types from the SD card file.
// BMP data is stored little-endian, Arduino is little-endian too.
// May need to reverse subscript order if porting elsewhere.

uint16_t DisplayDriver::read16(fs::File &f)
{
    uint16_t result;
    ((uint8_t *)&result)[0] = f.read(); // LSB
    ((uint8_t *)&result)[1] = f.read(); // MSB
    return result;
}

uint32_t DisplayDriver::read32(fs::File &f)
{
    uint32_t result;
    ((uint8_t *)&result)[0] = f.read(); // LSB
    ((uint8_t *)&result)[1] = f.read();
    ((uint8_t *)&result)[2] = f.read();
    ((uint8_t *)&result)[3] = f.read(); // MSB
    return result;
}
