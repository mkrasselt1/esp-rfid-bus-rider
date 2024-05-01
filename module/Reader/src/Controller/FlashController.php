<?php

namespace Reader\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Doctrine\ORM\EntityManager;
use Laminas\View\Model\JsonModel;

class FlashController extends AbstractActionController
{
    /**
     * Constructor is used for injecting dependencies into the controller.
     */
    public function __construct(
        EntityManager $entityManager,
    ) {
        $this->entityManager = $entityManager;
    }

    /**
     * Entity manager.
     * @var EntityManager 
     */
    public $entityManager;

    public function indexAction()
    {
        return [];
    }


    public function manifestAction()
    {
        $version = "1.0";
        $urlFirmware = $this->url()->fromRoute("reader/flash", ["action" => "download", "version" => "firmware"], ['force_canonical' => true]);
        $urlBootloader = $this->url()->fromRoute("reader/flash", ["action" => "download", "version" => "bootloader"], ['force_canonical' => true]);
        $urlPartitions = $this->url()->fromRoute("reader/flash", ["action" => "download", "version" => "partitions"], ['force_canonical' => true]);
        $urlSPIFFS = $this->url()->fromRoute("reader/flash", ["action" => "download", "version" => "spiifs"], ['force_canonical' => true]);
        $manifest = json_decode('{
            "name": "Busrider-Reader-Software",
                "version": "' . $version . '",
                "new_install_prompt_erase": true,
                "builds": [
                    {
                        "chipFamily": "ESP32",
                        "improv": false,
                        "parts": [
                            { "path": "' . $urlBootloader . '", "offset": 4096 },
                            { "path": "' . $urlFirmware . '", "offset": 65536 },
                            { "path": "' . $urlPartitions . '", "offset": 32768 },
                            { "path": "' . $urlSPIFFS . '", "offset": 2686976 }
                        ],
                        "unused":[
                            { "path": "' . $urlFirmware . '", "offset": 1376256 }
                        ]
                    }
                ]
            }');
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($manifest);
        exit;
    }

    public function downloadAction()
    {
        $file = $this->params()->fromRoute('version', "firmware");
        header('Content-type: text/plain; charset=utf8', true);
        echo \file_get_contents("./firmware/" . $file . ".bin");
        exit;
    }
}
