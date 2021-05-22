<?php
/**
 * Created by PhpStorm.
 * User: tomas.vitek
 * Date: 16.05.2021
 * Time: 18:37
 */

namespace Ok99\PrivateZoneCore\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ok99\PrivateZoneBundle\Entity\EventSport;

/**
 * @ORM\Table(name="user_sport_licences")
 * @ORM\Entity
 */
class UserSportLicence
{
    /**
     * @var integer $id
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var integer
     *
     * @ORM\ManyToOne(targetEntity="Ok99\PrivateZoneCore\UserBundle\Entity\User", inversedBy="sportLicences")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="RESTRICT", nullable=false)
     */
    private $user;

    /**
     * @var integer
     *
     * @ORM\ManyToOne(targetEntity="Ok99\PrivateZoneBundle\Entity\EventSport")
     * @ORM\JoinColumn(name="event_sport_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     */
    private $eventSport;

    /**
     * @var string|null
     *
     * @ORM\Column(name="licence", type="string", length=1, nullable=true)
     */
    protected $licence;


    /**
     * UserSportLicence constructor.
     * @param User $user
     * @param EventSport $eventSport
     * @param string|null $licence
     */
    public function __construct(
        User $user,
        EventSport $eventSport,
        $licence = null
    )
    {
        $this->user = $user;
        $this->eventSport = $eventSport;
        $this->licence = $licence;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        if ($this->licence) {
            return $this->getEventSport()->getName() . ': ' . $this->getLicence();
        }
        return $this->getEventSport()->getName();
    }

    /**
     * Get id
     *
     * @return integer $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set user
     *
     * @param User $user
     * @return UserSportLicence
     */
    public function setUser(User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return User|int
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set eventSport
     *
     * @param EventSport $eventSport
     * @return UserSportLicence
     */
    public function setEventSport(EventSport $eventSport = null)
    {
        $this->eventSport = $eventSport;

        return $this;
    }

    /**
     * Get eventSport
     *
     * @return EventSport|int
     */
    public function getEventSport()
    {
        return $this->eventSport;
    }

    /**
     * @param $licence
     * @return UserSportLicence
     */
    public function setLicence($licence)
    {
        $this->licence = strtoupper($licence);

        return $this;
    }

    /**
     * @return string
     */
    public function getLicence()
    {
        return $this->licence;
    }
}
