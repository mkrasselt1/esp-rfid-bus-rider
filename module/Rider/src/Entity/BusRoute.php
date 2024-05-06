<?php

namespace Rider\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\EntityRepository;

/**
 * This class represents a bus line
 * @ORM\Entity()
 * @ORM\Table(name="BusRoute")
 */
class BusRoute extends EntityRepository
{
    // company status constants.
    const STATUS_UNINITIATED  = 0; // Not yet existing company.
    const STATUS_ACTIVE       = 1; // active company.
    const STATUS_INACTIVE     = 2; // inactive company.
    const STATUS_DISABLED     = 3; // disabled company.
    const STATUS_RETIRED      = 4; // retired company.

    const STATUS_LIST = [
        self::STATUS_UNINITIATED  => "Not yet existing",
        self::STATUS_ACTIVE       => "active",
        self::STATUS_INACTIVE     => "inactive",
        self::STATUS_DISABLED     => "disabled",
        self::STATUS_RETIRED      => "retired",
    ];

    /**
     * @var integer
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @ORM\Column(name="name", type="string")
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
     * @ORM\OneToMany(targetEntity="BusStop", mappedBy="busRoute", cascade={"all"})
     */
    protected $busStops;

    /**
     * @ORM\ManyToOne(targetEntity="Company", cascade={"all"})
     */
    protected $company;

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
        $this->busStops = new ArrayCollection();
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
     * returns associates company.
     * @return Company
     */
    public function getCompany()
    {
        return $this->company;
    }

    /**
     * Sets associated company
     * @param Company $company
     */
    public function setCompany(Company $company)
    {
        $this->company = $company;
    }

    /**
     * returns bus stops for this route
     * @return Collection of BusStops
     */
    public function getBusStops(): Collection
    {
        return $this->busStops;
    }

    /**
     * Sets associated bus stops for the route
     * @param BussStop[] $busStops
     */
    public function setBusStops(Collection $busStops)
    {
        $this->busStops = $busStops;
    }

    /**
     * add bus stops to the route
     * @param BussStop[] $busStops
     */
    public function addBusStop(BusStop $busStop)
    {
        $this->getBusStops()->add($busStop);
    }

    /**
     * remove bus stops from the route
     * @param BussStop $busStop
     */
    public function removeBusStop(BusStop $busStop)
    {
        $this->getBusStops()->add($busStop);
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
     * Returns company status as string.
     * @return string
     */
    public function getStatusAsString()
    {
        $list = self::getStatusList();
        if (isset($list[$this->status]))
            return $list[$this->status];

        return self::STATUS_LIST[self::STATUS_UNINITIATED];
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
