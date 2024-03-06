#ifndef GLOBAL_EVENT_QUEUE_H
#define GLOBAL_EVENT_QUEUE_H

#include <Arduino.h>

template <class mType>
class eventHandler
{
    private:
        QueueHandle_t eventQueue;
        typedef struct s_dataobj{
            uint8_t eventID;
            mType data;
        }dataObj;

        dataObj tmp_tx;
        dataObj tmp_rx;

    public:
        

        void addEvent(mType* data, uint8_t eventID = 0)
        {            
            tmp_tx.eventID = eventID;
            memcpy(&(tmp_tx.data), data, sizeof(mType));
            xQueueGenericSend(eventQueue, &tmp_tx, portMAX_DELAY, queueSEND_TO_BACK);
        }

        void addEvent(uint8_t eventID = 0)
        {
            tmp_tx.eventID = eventID;
            xQueueGenericSend(eventQueue, &tmp_tx, portMAX_DELAY, queueSEND_TO_BACK);
        }

        bool popEvent(mType* event, uint8_t &eventID)
        {
            bool res = false;
            res = (xQueueReceive(eventQueue, &tmp_rx, 0) == pdTRUE);
            if(res){
                eventID = tmp_rx.eventID;
                memcpy(event, &(tmp_rx.data), sizeof(mType));
            }
            return res;            
        }

        bool popEvent(mType* event)
        {
            bool res = false;
            res = (xQueueReceive(eventQueue, &tmp_rx, 0) == pdTRUE);
            if(res){
                memcpy(event, &(tmp_rx.data), sizeof(mType));
            }
            return res;            
        }

        void init(int size = 10)
        {
            eventQueue = xQueueCreate(size, sizeof(dataObj));
        }

        bool popEventWait(uint8_t &eventID, mType* event, TickType_t timeout = 0)
        {
            if(timeout == 0) 
                timeout = portMAX_DELAY;
            else
                timeout = (timeout/portTICK_PERIOD_MS);

            bool res = false;
            res = (xQueueReceive(eventQueue, &tmp_rx, timeout) == pdTRUE);
            if(res){
                eventID = tmp_rx.eventID;
                memcpy(event, &(tmp_rx.data), sizeof(mType));
            }
            return res;      
        }

        bool popEventWait(mType* event, TickType_t timeout = 0)
        {
            if(timeout == 0) 
                timeout = portMAX_DELAY;
            else
                timeout = (timeout/portTICK_PERIOD_MS);

            bool res = false;
            res = (xQueueReceive(eventQueue, &tmp_rx, timeout) == pdTRUE);
            if(res){
                memcpy(event, &(tmp_rx.data), sizeof(mType));
            }
            return res;      
        }
};
#endif