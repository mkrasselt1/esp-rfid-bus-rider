<?php

namespace Reader\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Doctrine\ORM\EntityManager;
use Laminas\Http\Request;
use Laminas\Http\Response;
use Laminas\View\Model\JsonModel;
use Reader\Entity\Reader;
use Rider\Entity\Card;
use Rider\Entity\Ride;
use Rider\Service\CardService;

class BackendController extends AbstractActionController
{
    /**
     * Constructor is used for injecting dependencies into the controller.
     */
    public function __construct(
        EntityManager $entityManager,
        CardService $cardService
    ) {
        $this->entityManager = $entityManager;
        $this->cardService = $cardService;
    }

    /**
     * Entity manager.
     * @var EntityManager 
     */
    public $entityManager;

    /**
     * Card Manager
     * @var CardService 
     */
    public $cardService;

    public function indexAction()
    {
        $this->layout()->setTemplate('layout/api');

        // $message = json_decode(file_get_contents("php://input"), true);
        $message = $this->getHTTPRequest()->getContent();
        $message = \json_decode($this->getHTTPRequest()->getContent(), true);
        $message = [
            "login"
        ];
        return new JsonModel(
            $message
        );
    }

    public function cardAction()
    {
        $this->layout()->setTemplate('layout/api');
        $token = $this->getHTTPRequest()->getQuery("token", 0);
        $readers = $this->entityManager->getRepository(Reader::class)->findBy([
            "token" => $token
        ]);
        //check authentication by result size
        if (!count($readers) || count($readers) > 1) {
            $this->getHTTPResponse()->setStatusCode(401);
            return new JsonModel(["message" => "unauthorized"]);
        }
        /** @var Reader */
        $reader = \array_shift($readers);

        // $message = json_decode(file_get_contents("php://input"), true);
        $message = $this->getHTTPRequest()->getContent();
        $message = \json_decode($this->getHTTPRequest()->getContent());

        /** @var Card */
        $card = $this->entityManager->getRepository(Card::class)->findOneBy([
            "uid" => $message->id
        ]);
        //create new cards if not existing
        if (\is_null($card)) {
            $card = $this->createCard(
                uid: $message->id,
                timestamp: $message->timestamp
            );
        }
        
        if (is_null($card->getEmployee())) {
            $this->getHTTPResponse()->setStatusCode(412);
            return new JsonModel(["message" => "not assigned"]);
        }

        //all prerequisites fulfilled

        $newRide = Ride::fromCard($card);
        $this->entityManager->persist($newRide);
        $this->entityManager->flush();

        return new JsonModel(
            [
                $card->getId()
            ]
        );
    }

    public function logoAction()
    {
        //RegioBus
        // $imgSrc = "https://www.regiobus.com/fileadmin/tmpl/images/logo.png";
        // $myImage = \imagecreatefrompng($imgSrc);


        // DVB Dresden
        // $imgSrc = "https://www.dresden.de/media/bilder/wirtschaft/DVB_Logo_CS6.png";
        // $myImage = \imagecreatefrompng($imgSrc);

        // Rheinbahn
        $imgSrc = "https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRaSDgDLBAN92hABNT3KDWncgvRIjOuNktelA&usqp=CAU";
        $myImage = \imagecreatefrompng($imgSrc);

        //city bahn
        // $imgSrc = "https://www.ris-sachsen.eu/wp-content/uploads/2019/02/city-bahn_chemnitz_logo-300x143.png";
        // $myImage = \imagecreatefrompng($imgSrc);

        //hyundai
        // $imgSrc = "https://d1csarkz8obe9u.cloudfront.net/posterpreviews/hyundai-logo-design-template-1a9962af404b34993c5ab8dfc25228ba_screen.jpg?ts=1698373830";
        // $myImage = \imagecreatefromjpeg($imgSrc);

        //vw
        // $imgSrc = "https://uploads.vw-mms.de/system/production/images/vwn/030/145/images/7a0d84d3b718c9a621100e43e581278433114c82/DB2019AL01950_web_1600.jpg?1649155356";
        // $myImage = \imagecreatefromjpeg($imgSrc);

        // copying the part into thumbnail
        $xNew = 240;
        $yNew = 135;
        $thumb = imagecreatetruecolor($xNew, $yNew);

        //getting the image dimensions
        list($width, $height) = getimagesize($imgSrc);
        imagecopyresampled($thumb, $myImage, 0, 0, 0, 0, $xNew, $yNew, $width, $height);

        header('Content-type: image/bmp');
        imagebmp($thumb);
        exit;
    }

    public function iconAction()
    {
        $iconName = $this->params()->fromRoute('image', "ban");

        $imgSrc = "https://cdn1.iconfinder.com/data/icons/heroicons-ui/24/" . $iconName . "-256.png";
        $myImage = \imagecreatefrompng($imgSrc);

        // copying the part into thumbnail
        $xNew = 240;
        $yNew = 135;
        $thumb = imagecreatetruecolor($xNew, $yNew);

        //getting the image dimensions
        list($width, $height) = getimagesize($imgSrc);
        imagecopyresampled($thumb, $myImage, 0, 0, 0, 0, $xNew, $yNew, $width, $height);

        header('Content-type: image/bmp');
        imagebmp($thumb);
        exit;
    }

    private function createCard(string $uid, int $timestamp)
    {
        $newCard = new Card();
        $newCard->setUID($uid);
        $newCard->setDateCreated((new \DateTime())->setTimestamp($timestamp));
        $newCard->setName("new card");
        $newCard->setNumber(\hexdec($uid));
        $this->entityManager->persist($newCard);
        $this->entityManager->flush();
    }

    private function getHTTPRequest(): Request
    {
        return $this->getRequest();
    }

    private function getHttpResponse(): Response
    {
        return $this->getResponse();
    }
}
