<?php

namespace Rider\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\EntityRepository;

/**
 * This class represents a registered employee.
 * @ORM\Entity()
 * @ORM\Table(name="employee")
 */
class Employee extends EntityRepository
{
    // User status constants.
    const STATUS_UNINITIATED  = 0; // Not yet existing employee.
    const STATUS_ACTIVE       = 1; // active employee.
    const STATUS_INACTIVE     = 2; // inactive employee.
    const STATUS_DISABLED     = 3; // disabled employee.
    const STATUS_RETIRED      = 4; // retired employee.

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
     * @ORM\Column(name="name")  
     */
    protected $name;

    /** 
     * @ORM\Column(name="status", type="smallint")  
     */
    protected $status;

    /**
     * @var DateTime
     * @ORM\Column(name="date_created", type="datetime", nullable=false)
     */
    protected $dateCreated;

    /**
     * @ORM\Column(name="dateModified", type="datetime", nullable=false, options={"default": "CURRENT_TIMESTAMP"})
     */
    protected $dateModified;

    /**
     * @ORM\ManyToOne(targetEntity="Employee", cascade={"all"})
     */
    protected $cards;

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
        $this->setStatus(self::STATUS_ACTIVE);
        $this->setToken(bin2hex(random_bytes(32)));
        $this->cards = new ArrayCollection();
    }
    /**
     * Returns user ID.
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Sets user ID. 
     * @param int $id    
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Returns token.     
     * @return string
     */
    public function getToken()
    {
        return $this->uid;
    }

    /**
     * Sets token.     
     * @param string $token
     */
    public function setToken($token)
    {
        $this->uid = $token;
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
    public function setName($name):self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * returns associates cards.
     * @return ArrayCollection
     */
    public function getCards()
    {
        return $this->cards;
    }

    /**
     * Sets associated cards.
     * @param Employee $employee
     */
    public function setCards(Collection $employees)
    {
        $this->cards = $employees;
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
        return [
            self::STATUS_UNINITIATED => 'Nicht Initialisiert',
            self::STATUS_ACTIVE => 'Aktiv',
            self::STATUS_INACTIVE => 'Inaktiv',
            self::STATUS_DISABLED => 'Deaktiviert',
            self::STATUS_RETIRED => 'Beendet',
        ];
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
