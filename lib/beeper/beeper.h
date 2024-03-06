#ifndef BR_BEEPER_H
#define BR_BEEPER_H

#include <Arduino.h>
#include "beeperEvents.h"

class beeper {
    private:
        static void beeperThread(void* args); 
        void playSound(uint8_t soundNum);
    public:    
        bool start();
        bool init();
};

#endif