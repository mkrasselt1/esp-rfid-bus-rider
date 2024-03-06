#ifndef BR_SOUND_H
#define BR_SOUND_H

#include <Arduino.h>


#define NOTE_C 32.7032
#define NOTE_CS 34.6478
#define NOTE_D 36.7081
#define NOTE_DS 38.8909
#define NOTE_E 41.2034
#define NOTE_F 43.6536
#define NOTE_FS 46.2493
#define NOTE_G 48.9994
#define NOTE_GS 51.9131
#define NOTE_A 55.0000
#define NOTE_AS 58.2705
#define NOTE_H 61.7354

typedef struct{
    double frequ;
    uint32_t duration;
    uint32_t pause;
} sound_t;


const sound_t sound_startup[] = {
    {NOTE_D, 250, 250},
};

const sound_t sound_connected[] = {
    {NOTE_G, 250, 0},
    {NOTE_H, 125, 0}
};

const sound_t sound_login[] = {
    {NOTE_D, 125, 125},
    {NOTE_H, 125, 125}
};

const sound_t sound_logout[] = {
    {NOTE_H, 125, 125},
    {NOTE_D, 125, 125}
};

const sound_t sound_error[] = {
    {NOTE_C*2, 125, 125},
    {NOTE_GS, 500, 0},
};


#endif