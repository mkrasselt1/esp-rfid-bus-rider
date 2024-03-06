#include "beeper.h"
#include "sound.h"
#include "eventQueueBase.hpp"

#define freq        1000
#define channel     0
#define resolution  8
#define PIN_BUZZ    12

extern eventHandler<uint8_t> beeperEvents;
extern beeper beepr;

bool beeper::init()
{
    ledcSetup(channel, freq, resolution);
    ledcAttachPin(PIN_BUZZ, channel);
    return true;
}

bool beeper::start()
{
    xTaskCreatePinnedToCore(beeperThread, "beeper", 1024, NULL, 1, NULL, 1);
    return true;
}

void playnote(double frequ, uint32_t duration, uint32_t pause)
{
    #ifndef NOBEEP
    ledcWrite(channel, 125);
    ledcWriteTone(channel, frequ);
    delay(duration);
    ledcWrite(channel, 0);
    delay(pause);
    #endif
}

void playtrack(const sound_t* track, size_t size)
{
    for (size_t i = 0; i < size; i++)
    {
        playnote(track[i].frequ*10, track[i].duration, track[i].pause);
    }
    
}

void beeper::playSound(uint8_t soundNum)
{
    
    switch (soundNum)
    {
    case 0: //Connected Sound
        playtrack(sound_connected, sizeof(sound_connected) / sizeof(sound_t));
        break;

    case 1: //Login Sound
        playtrack(sound_login, sizeof(sound_login) / sizeof(sound_t));
        break;

    case 2: //Logout Sound
        playtrack(sound_logout, sizeof(sound_logout) / sizeof(sound_t));
        break;

    case 3: //ERR Sound
        playtrack(sound_error, sizeof(sound_error) / sizeof(sound_t));
        break;
    
    case 4: //Start Sound
        playtrack(sound_startup, sizeof(sound_startup) / sizeof(sound_t));
        break;
    
    case 5: //Login sound for new Users
        playtrack(sound_login_blank, sizeof(sound_login_blank) / sizeof(sound_t));
        break;
    
    default:
        break;
    }
}

void beeper::beeperThread(void* args)
{
    while (true)
    {
        uint8_t tmp;
        uint8_t eventType;
        if(beeperEvents.popEvent(&tmp, eventType)){
            switch (eventType)
            {     
                case BEEPER_EVENTS::PLAY_SOUND:
                    beepr.playSound(tmp);
                    break;
            }
        }  

        vTaskDelay(portMAX_DELAY);        
    }    
}

beeper beepr;
