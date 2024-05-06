<?php

namespace Rider\Entity;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;

class CardRepository extends EntityRepository
{
    public function getCardsForEmployee(?Employee $employee = null)
    {
        $cardCriteria = new Criteria();
        $cardCriteria->where(
            Criteria::expr()->andX(
                Criteria::expr()->eq("status", Card::STATUS_ACTIVE),
                Criteria::expr()->eq("employee", null)
            )
        );
        if (!\is_null($employee)) {
            $cardCriteria->orWhere(Criteria::expr()->eq("employee", $employee));
        }
        return $this->_em->getRepository(Card::class)->matching($cardCriteria);
    }

    /**
     * one field for every contract, when its been last processed
     * check for each kind of interval in one go
     */
}
