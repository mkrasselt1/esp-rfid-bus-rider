<?php

namespace Site\View\Helper;

use Doctrine\ORM\EntityManager;
use Laminas\View\Helper\AbstractHelper;
use Rider\Entity\Company;

class CompanyMenu extends AbstractHelper
{

    /**
     * @var EntityManager
     */
    private $entityManager = null;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function __invoke(string $template, string $urlTemplate)
    {
        /**
         * @var Company[]
         */
        $companies = $this->entityManager->getRepository(Company::class)->findBy([
            "status" => Company::STATUS_ACTIVE
        ]);

        foreach ($companies as $x => $company) {
            $url = \str_replace("ID", $company->getId(), $urlTemplate);
            $namePrefix = '<i class="bi bi-' .
                //turn array into set of icons
                \implode(
                    '-square"></i><i class="bi bi-',
                    //turn into to array
                    str_split(
                        //fill with zeros to the left
                        str_pad($x + 1, count($companies) / 10, "0", \STR_PAD_LEFT)
                    )
                ) . '-square"></i>';
            echo \str_replace(["{{NAME}}", "{{URL}}"], [$namePrefix . $company->getName(), $url], $template);
        }
    }
}
