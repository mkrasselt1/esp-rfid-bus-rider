<?php
namespace Rider\Service\Factory;

use Doctrine\ORM\EntityManager;
use Psr\Container\ContainerInterface;
use Rider\Service\CardService;

class CardServiceFactory {
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null) {
        /** @var EntityManager */
        $entityManager = $container->get('doctrine.entitymanager.orm_default');
        return new CardService( $entityManager );
    }
}
