<?php

namespace Rider\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\EntityRepository;

/**
 * This class represents a bus stop on a bus line
 * @ORM\Entity()
 * @ORM\Table(name="BusStop")
 */
class BusStop extends EntityRepository
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
     * @ORM\Column(name="name")  
     */
    protected $name;

    /** 
     * @ORM\Column(name="status", type="smallint")  
     */
    protected $status;

    /**
     * @ORM\ManyToOne(targetEntity="BusRoute", inversedBy="busStops", cascade={"all"})
     * @ORM\JoinColumn(name="busRoute", referencedColumnName="id")
     */
    protected $busRoute;

    /**
     * @ORM\OneToMany(targetEntity="Employee", mappedBy="busStop", cascade={"all"})
     */
    protected $employees;

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
        $this->employees = new ArrayCollection();
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
     * returns the name.
     * @return String
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets the name
     * @param String $name
     */
    public function setName(String $name)
    {
        $this->name = $name;
    }

    /**
     * returns associates bus route
     * @return BusRoute
     */
    public function getBusRoute()
    {
        return $this->busRoute;
    }

    /**
     * Sets associated bus route
     * @param BusRoute $busRoute
     */
    public function setBusRoute(BusRoute $busRoute)
    {
        $this->busRoute = $busRoute;
    }

    /**
     * returns associated bus route name
     * @return String
     */
    public function getBusRouteName()
    {
        return $this->getBusRoute()?->getName() ?? "-/-";
    }

    /**
     * returns associates employees.
     * @return ArrayCollection
     */
    public function getEmployees()
    {
        return $this->employees;
    }

    /**
     * Sets associated employees.
     * @param company $company
     */
    public function setEmployees(Collection $employees)
    {
        $this->employees = $employees;
    }

    /**
     * add selected employee.
     * @param company $company
     */
    public function addEmployees(Employee $employee)
    {
        $this->getEmployees()->add($employee);
    }

    /**
     * remove selected employee.
     * @param company $company
     */
    public function removeEmployee(Collection $employee)
    {
        $this->getEmployees()->removeElement($employee);
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
