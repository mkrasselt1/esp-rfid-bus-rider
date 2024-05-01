<?php

namespace Rider\Service;

use Doctrine\ORM\EntityManager;
use Rider\Entity\Card;

/**
 * This service is responsible for adding/editing cards
 */
class CardService
{

    /**
     * Doctrine entity manager.
     * @var EntityManager
     */
    private $entityManager;

    /**
     * Constructs the service.
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getCard(int $id): ?Card
    {
        return $this->entityManager->find(Card::class, $id);
    }

    /**
     * @param string $cardId
     * @return Card
     */
    public function getCardForCardId(string $cardId)
    {
        $cards = $this->entityManager->getRepository(Card::class)->findBy([
            "uid" => $cardId
        ]);
        return $cards;
    }

}
