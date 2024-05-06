<?php

namespace Rider\Fieldset;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Laminas\Hydrator\DoctrineObject as DoctrineHydrator;
use Doctrine\ORM\EntityManager;
use DoctrineModule\Form\Element\ObjectSelect;
use Laminas\Filter\StringTrim;
use Laminas\Filter\StripNewlines;
use Laminas\Filter\StripTags;
use Laminas\Form\Element\Hidden;
use Laminas\Form\Element\Select;
use Laminas\Form\Element\Text;
use Laminas\Form\Fieldset;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Validator\StringLength;
use Rider\Entity\BusRoute;
use Rider\Entity\BusStop;
use Rider\Entity\Card;
use Rider\Entity\Company;
use Rider\Entity\Employee;

class EmployeeFieldset extends Fieldset implements InputFilterProviderInterface
{
    /**
     * Entity Manager
     * @var EntityManager
     */
    private $entityManager = null;

    /**
     * @var Employee
     */
    private $employee = null;

    public function __construct(string $name, EntityManager $entityManager, ?Employee $employee = null)
    {
        parent::__construct($name);
        $this->entityManager = $entityManager;
        $this->employee = $employee;
        $this->setHydrator(new DoctrineHydrator($this->entityManager))
            ->setObject(new Employee());

        $this->init();
    }


    public function init()
    {
        $this->add([
            'type' => Hidden::class,
            'name' => 'id',
        ]);

        $this->add([
            'type' => Text::class,
            'name' => 'name',
            'attributes' => [
                'class' => 'form-control',
                'title' => 'name of the rider',
            ],
            'options' => [
                'label' => 'name'
            ]
        ]);

        $this->add([
            'type' => Select::class,
            'name' => 'status',
            'options' => [
                'label' => 'status',
                // 'empty_option' => Employee::STATUS_LIST[Employee::STATUS_UNINITIATED],
                'value_options' => Employee::STATUS_LIST,
            ],
            'attributes' => [
                'readonly' => false,
                'class' => 'form-control'
            ]
        ]);

        $this->add([
            'type' => ObjectSelect::class,
            'attributes' => [
                'class' => 'form-control',
            ],
            'options' => [
                'label' => 'assign company',
                'object_manager' => $this->entityManager,
                'target_class'   => Company::class,
                //'property'       => 'id',
                'label_generator' => function (Company $company) {
                    return $company->getName();
                },
                'is_method'      => true,
                'find_method'    => [
                    'name'   => 'findBy',
                    'params' => [
                        'criteria' => [
                            'status' => Company::STATUS_ACTIVE,
                        ]
                    ],
                ],
                'display_empty_item' => true,
                'empty_item_label'   => '-no company assigned-',
            ],
            'name' => 'company'
        ]);

        $this->add([
            'type' => ObjectSelect::class,
            'attributes' => [
                'class' => 'form-control'
            ],
            'options' => [
                'label' => 'assign bus stop',
                'object_manager' => $this->entityManager,
                'target_class'   => BusStop::class,
                //'property'       => 'id',
                'option_attributes' => [
                    'style' => function (BusStop $busStop) {
                        if (!$busStop->isActive()) {
                            return "color: red; text-decoration: line-through;";
                        }
                        return "";
                    },
                ],
                'label_generator' => function (BusStop $busStop) {
                    if (!$busStop->isActive())
                        return \mb_convert_encoding("\xCC\xB6" . implode("\xCC\xB6", str_split($busStop->getName())), "UTF-8");
                    return $busStop->getName();
                },
                'is_method'      => true,
                'find_method'    => [
                    'name'   => 'findBy',
                    'params' => [
                        'criteria' => [
                            'status' => [
                                BusStop::STATUS_ACTIVE,
                                BusStop::STATUS_INACTIVE
                            ],
                            'busRoute' => $this->employee->getCompany()->getBusRoutes()->map(fn (BusRoute $br) => $br->getId())->toArray()
                        ],
                        'orderBy'  => ['busRoute' => 'ASC'],
                    ],
                ],
                'optgroup_identifier' => "busRouteName",
                'display_empty_item' => true,
                'empty_item_label'   => '-no busStop assigned-',
            ],
            'name' => 'busStop'
        ]);

        $this->add([
            'type' => ObjectSelect::class,
            'attributes' => [
                'class' => 'form-control',
                "multiple" => true,
            ],
            'options' => [
                'label' => 'assign cards',
                'object_manager' => $this->entityManager,
                'target_class'   => Card::class,
                //'property'       => 'id',
                'label_generator' => function (Card $card) {
                    return $card->getNumber();
                },
                'is_method'      => true,
                'find_method'    => [
                    'name'   => 'getCardsForEmployee',
                    'params' => [
                        "employee" => $this->employee,
                    ],
                ],
                'display_empty_item' => true,
                'empty_item_label'   => '-no card assigned-',
            ],
            'name' => 'cards'
        ]);
    }
    public function getInputFilterSpecification()
    {
        return [
            'id' => [
                'required' => false,
            ],
            'name' => [
                'required' => true,
                'filters'  => [
                    ['name' => StringTrim::class],
                    ['name' => StripTags::class],
                    ['name' => StripNewlines::class],
                ],
                'validators' => [
                    [
                        'name' => StringLength::class,
                        'options' => [
                            'min' => 5,
                            'max' => 25
                        ],
                    ],
                ],
            ],
            'status' => [
                'required' => true,
            ],
        ];
    }
}
