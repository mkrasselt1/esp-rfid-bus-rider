<?php

namespace Reader\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\EntityRepository;

/**
 * This class represents a ride registering reader
 * @ORM\Entity()
 * @ORM\Table(name="Reader")
 */
class Reader extends EntityRepository
{
    // Reader status constants.
    const STATUS_UNINITIATED  = 0; // Not yet existing Reader.
    const STATUS_ACTIVE       = 1; // active Reader.
    const STATUS_INACTIVE     = 2; // inactive Reader.
    const STATUS_DISABLED     = 3; // disabled Reader.
    const STATUS_RETIRED      = 4; // retired Reader.

    const STATUS_LIST = [
        self::STATUS_UNINITIATED => 'not initialized',
        self::STATUS_ACTIVE => 'active',
        self::STATUS_INACTIVE => 'inactive',
        self::STATUS_DISABLED => 'disabled',
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
    protected $token;

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
        $this->setToken(implode('-', str_split(substr(strtolower(md5(microtime() . rand(1000, 9999))), 0, 30), 6)));
    }
    /**
     * Returns Reader id.
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Sets Reader id. 
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
        return $this->token;
    }

    /**
     * Sets token.     
     * @param string $token
     */
    public function setToken($token)
    {
        $this->token = $token;
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
     * Returns reader status as string.
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
     * Returns the date of reader creation.
     * @return DateTime     
     */
    public function getDateCreated(): \DateTime
    {
        return $this->dateCreated;
    }

    /**
     * Sets the date when this reader was created.
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
     * Sets the date when this reader was changed
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
