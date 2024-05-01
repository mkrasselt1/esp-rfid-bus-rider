<?php

namespace Rider\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\EntityRepository;

/**
 * This class represents a ride made by employee
 * @ORM\Entity()
 * @ORM\Table(name="ride")
 */
class Ride extends EntityRepository
{
    /**
     * @var integer
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @var DateTime
     * @ORM\Column(name="dateCreated", type="datetime", nullable=false)
     */
    protected $dateCreated;

    /**
     * @ORM\Column(name="dateModified", type="datetime", nullable=false, options={"default": "CURRENT_TIMESTAMP"})
     */
    protected $dateModified;

    /**
     * @ORM\ManyToOne(targetEntity="Employee", inversedBy="rides", cascade={"all"})
     * @ORM\JoinColumn(name="employee", referencedColumnName="id")
     */
    protected $employee;

    /**
     * @ORM\ManyToOne(targetEntity="Card", inversedBy="rides", cascade={"all"})
     * @ORM\JoinColumn(name="card", referencedColumnName="id")
     */
    protected $card;

    /**
     * @ORM\ManyToOne(targetEntity="BusStop", inversedBy="rides", cascade={"all"})
     * @ORM\JoinColumn(name="busstop", referencedColumnName="id")
     */
    protected $busstop;

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */

    public function updatedTimestamps(): void
    {
        $this->setDateModified(new \DateTime('now'));
    }

    public function __construct()
    {
        $this->setDateCreated();
        $this->setDateModified();
    }
    
    /**
     * Returns card id.
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Sets card id. 
     * @param int $id    
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * returns associates user.
     * @return Employee
     */
    public function getEmployee()
    {
        return $this->employee;
    }

    /**
     * Sets associated user.
     * @param Employee $employee
     */
    public function setEmployee($employee)
    {
        $this->employee = $employee;
    }

    /**
     * returns associates card
     * @return Card
     */
    public function getCard()
    {
        return $this->card;
    }

    /**
     * Sets associated card
     * @param Card $card
     */
    public function setCard($card)
    {
        $this->card = $card;
    }

    /**
     * returns associates bus stop
     * @return BusStop
     */
    public function getBusStop()
    {
        return $this->busstop;
    }

    /**
     * Sets associated bus stop
     * @param BussStop $busStop
     */
    public function setBusStop($busStop)
    {
        $this->busstop = $busStop;
    }

    /**
     * Returns the date of user creation.
     * @return DateTime     
     */
    public function getDateCreated(): \DateTime
    {
        return $this->dateCreated;
    }

    /**
     * Sets the date when this user was created.
     * @param DateTime $dateCreated     
     */
    public function setDateCreated(DateTime $dateCreated = null)
    {
        if (\is_null($dateCreated)) {
            $dateCreated = new DateTime();
        }
        $this->dateCreated = $dateCreated;
    }

    /**
     * Sets the date when this user was changed
     * @param DateTime $dateModified     
     */
    public function setDateModified(DateTime $dateModified = null)
    {
        if (\is_null($dateModified)) {
            $dateModified = new DateTime();
        }
        $this->dateModified = $dateModified;
    }

    /**
     * exchangeArray(array $data)
     * This method is needed for data exchange and hydrator (when binding forms) 
     * @return void 
     */
    public function exchangeArray($data = [])
    {
        foreach ($data as $key => $value) {
            $method = 'set' . ucfirst($key);
            if (method_exists($this, $method)) {
                $this->$method($value);
            }
        }
        return get_object_vars($this);
    }
}
