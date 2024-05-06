<?php

namespace Rider\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\EntityRepository;

/**
 * This class represents a registered card.
 * @ORM\Entity(repositoryClass="CardRepository")
 * @ORM\Table(name="card")
 */
class Card extends EntityRepository
{
    // Card status constants.
    const STATUS_UNINITIATED  = 0; // Not yet existing card.
    const STATUS_ACTIVE       = 1; // active card.
    const STATUS_INACTIVE     = 2; // inactive card.
    const STATUS_DISABLED     = 3; // disabled card.
    const STATUS_RETIRED      = 4; // retired card.

    const STATUS_LIST = [
        self::STATUS_UNINITIATED => 'not initialized',
        self::STATUS_ACTIVE => 'active',
        self::STATUS_INACTIVE => 'inactive',
        self::STATUS_DISABLED => 'deactivated',
        self::STATUS_RETIRED => 'retired',
    ];

    /**
     * @var integer
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    protected $id;

    /** 
     * @ORM\Column(name="uid", type="string", length=70)  
     */
    protected $uid;

    /** 
     * @ORM\Column(name="number")  
     */
    protected $number;

    /** 
     * @ORM\Column(name="name")  
     */
    protected $name;

    /** 
     * @ORM\Column(name="status", type="smallint")  
     */
    protected $status;

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
     * @ORM\ManyToOne(targetEntity="Employee", inversedBy="cards", cascade={"all"})
     * @ORM\JoinColumn(name="employee", referencedColumnName="id")
     */
    protected $employee;

    /**
     * @ORM\OneToMany(targetEntity="Ride", mappedBy="card", cascade={"all"})
     */
    protected $rides;


    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */

    public function updatedTimestamps(): void
    {
        $this->setDateModified(new \DateTime('now'));
        $this->rides = new ArrayCollection();
    }

    public function __construct()
    {
        $this->setDateCreated();
        $this->setDateModified();
        $this->setStatus(self::STATUS_ACTIVE);
        $this->setUID(bin2hex(random_bytes(32)));
        $this->rides = new ArrayCollection();
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
     * Returns number on that card
     * @return string
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * Sets the number printed on that card
     * @param string $number
     */
    public function setNumber($number)
    {
        $this->number = $number;
    }

    /**
     * Returns uid of card
     * @return string
     */
    public function getUID()
    {
        return $this->uid;
    }

    /**
     * Sets uid of card
     * @param string $uid
     */
    public function setUID($uid)
    {
        $this->uid = $uid;
    }

    /**
     * Returns full name.
     * @return string     
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets full name.
     * @param string $name
     */
    public function setName($name): self
    {
        $this->name = $name;
        return $this;
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
     * Get all rides made with that card
     * @return Collection 
     */
    public function getRides(): Collection
    {
        return $this->rides;
    }

    /**
     * sets all rides at once
     * @param Collection $rides
     * @return self
     */
    public function setRides(Collection $rides)
    {
        $this->rides = $rides;
        return $this;
    }

    /**
     * adds a single ride to the card
     * @param Ride $ride 
     * @return self 
     */
    public function addRide(Ride $ride)
    {
        $this->getRides()->add($ride);
        return $this;
    }

    /**
     * removes a single ride from the card
     * @param Ride $ride 
     * @return self
     */
    public function removeRide(Ride $ride)
    {
        $this->getRides()->removeElement($ride);
        return $this;
    }

    /**
     * Returns status.
     * @return int     
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Returns true if status is active
     * @return bool
     */
    public function isActive()
    {
        return $this->status == self::STATUS_ACTIVE;
    }

    /**
     * Returns true if status is paused
     * @return bool
     */
    public function isPaused()
    {
        return $this->status == self::STATUS_INACTIVE;
    }

    /**
     * Returns true if status is disabled
     * @return bool
     */
    public function isDisabled()
    {
        return $this->status == self::STATUS_DISABLED;
    }

    public function isUninitiated(): bool
    {
        return $this->getStatus() == Self::STATUS_UNINITIATED;
    }

    /**
     * Returns possible statuses as array.
     * @return array
     */
    public static function getStatusList()
    {
        return self::STATUS_LIST;
    }

    /**
     * Returns user status as string.
     * @return string
     */
    public function getStatusAsString()
    {
        $list = self::getStatusList();
        if (isset($list[$this->status]))
            return $list[$this->status];

        return 'Unbekannt';
    }

    /**
     * Sets status.
     * @param int $status     
     */
    public function setStatus($status)
    {
        $this->status = $status;
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
     * Returns the date of last reader change
     * @return DateTime     
     */
    public function getDateModified(): \DateTime
    {
        return $this->dateModified;
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
