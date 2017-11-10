<?php

namespace Ok99\PrivateZoneCore\UserBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\DNSCheckValidation;
use Egulias\EmailValidator\Validation\MultipleValidationWithAnd;
use Egulias\EmailValidator\Validation\RFCValidation;
use FOS\UserBundle\Model\GroupInterface;
use FOS\UserBundle\Model\UserInterface as FOSUserInterface;
use FOS\UserBundle\Model\User as BaseUser;
use Gedmo\Mapping\Annotation as Gedmo;
use Ok99\PrivateZoneBundle\Entity\Message;
use Ok99\PrivateZoneBundle\Entity\RemoteControl;
use Ok99\PrivateZoneBundle\Entity\TrainingGroup;
use Ok99\PrivateZoneBundle\Entity\Wallet;
use Sonata\UserBundle\Model\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @ORM\Table(name="fos_user_user",indexes={
 *     @ORM\Index(name="IDX_REGNUM", columns={"regnum"})
 * })
 * @ORM\Entity(repositoryClass="Ok99\PrivateZoneCore\UserBundle\Entity\Repository\UserRepository")
 */
class User extends BaseUser implements UserInterface
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
     * @ORM\Column(name="oris_id", type="integer", nullable=true)
     */
    protected $orisId;

    /**
     * @var integer
     *
     * @ORM\Column(name="oris_clubuser_id", type="integer", nullable=true)
     */
    protected $orisClubuserId;

    /**
     * @var string
     *
     * @ORM\Column(name="username", type="string", length=255)
     */
    protected $username;

    /**
     * @var string
     *
     * @ORM\Column(name="username_canonical", type="string", length=255, unique=true)
     */
    protected $usernameCanonical;

    /**
     * @var string
     *
     * @ORM\Column(name="regnum", type="string", length=5, unique=true)
     * @Assert\NotBlank(message="Registrační číslo musí být zadáno.")
     */
    protected $regnum;

    /**
     * @var string
     *
     * @ORM\Column(name="club_shortcut", type="string", length=8)
     */
    protected $clubShortcut;

    /**
     * @var string
     *
     * @ORM\Column(name="licence", type="string", length=1, nullable=true)
     */
    protected $licence;

    /**
     * @var integer
     *
     * @ORM\Column(name="sportident", type="string", length=16, nullable=true)
     */
    protected $sportident;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_of_birth", type="datetime", nullable=true)
     * @Assert\NotBlank(message="Datum narození musí být zadáno.")
     */
    protected $dateOfBirth;

    /**
     * @var string
     *
     * @ORM\Column(name="firstname", type="string", length=64)
     */
    protected $firstname;

    /**
     * @var string
     *
     * @ORM\Column(name="lastname", type="string", length=64)
     */
    protected $lastname;

    /**
     * @var string
     *
     * @ORM\Column(name="nickname", type="string", length=255, nullable=true)
     */
     protected $nickname;

    /**
     * @var string
     *
     * @ORM\Column(name="gender", type="string", length=1, nullable=true)
     */
    protected $gender = UserInterface::GENDER_UNKNOWN;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=255, nullable=true)
     * @Assert\Callback(
     *     callback={"Ok99\PrivateZoneCore\UserBundle\Entity\User","validateEmail"},
     *     groups={"Registration","Profile"}
     * )
     */
    protected $email;

    /**
     * @var string
     *
     * @ORM\Column(name="email_canonical", type="string", length=255, nullable=true)
     */
    protected $emailCanonical;

    /**
     * @var string
     *
     * @ORM\Column(name="email_parent", type="string", length=255, nullable=true)
     * @Assert\Callback(
     *     callback={"Ok99\PrivateZoneCore\UserBundle\Entity\User","validateEmailParent"},
     *     groups={"Registration","Profile"}
     * )
     */
    protected $emailParent;

    /**
     * @var string
     *
     * @ORM\Column(name="phone", type="string", length=64, nullable=true)
     */
    protected $phone;

    /**
     * @var string
     *
     * @ORM\Column(name="phone_parent", type="string", length=64, nullable=true)
     */
    protected $phoneParent;

    /**
     * @var string
     *
     * @ORM\Column(name="street", type="string", length=128, nullable=true)
     */
    protected $street;

    /**
     * @var string
     *
     * @ORM\Column(name="city", type="string", length=64, nullable=true)
     */
    protected $city;

    /**
     * @var string
     *
     * @ORM\Column(name="zip", type="string", length=16, nullable=true)
     */
    protected $zip;

    /**
     * @var string
     *
     * @ORM\Column(name="avatar", type="string", length=64, nullable=true)
     */
    private $avatar;

    /**
     * @var string
     *
     * @ORM\Column(name="photo", type="string", length=64, nullable=true)
     */
    private $photo;

    /**
     * @var string
     *
     * @ORM\Column(name="skin_color", type="string", length=16, nullable=true)
     */
    private $skinColor;

    /**
     * @var boolean
     *
     * @ORM\Column(name="menu_sidebar_collapsed", type="boolean")
     */
    protected $menuSidebarCollapsed = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="menu_sidebar_expand_on_hover", type="boolean")
     */
    protected $menuSidebarExpandOnHover = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="control_sidebar_light_skin", type="boolean")
     */
    protected $controlSidebarLightSkin = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="suggest_event_classes", type="boolean")
     */
    protected $suggestEventClasses = true;

    /**
     * @ORM\OneToMany(targetEntity="\Ok99\PrivateZoneBundle\Entity\Message", mappedBy="recipient", cascade={"persist"}, orphanRemoval=true)
     */
    private $messages;

    private $messagesOrdered = null;
    private $messagesUnread = null;

    /**
     * @ORM\OneToMany(targetEntity="\Ok99\PrivateZoneBundle\Entity\RemoteControl", mappedBy="applicant", cascade={"persist"}, orphanRemoval=true)
     */
    private $remoteControlRequests;

    private $remoteUsers = null;
    private $teamMembers = null;
    private $familyMembers = null;

    /**
     * @ORM\ManyToMany(targetEntity="Group")
     * @ORM\JoinTable(name="user_group",
     *      joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="group_id", referencedColumnName="id")})
     */
    protected $groups;

    /**
     * @ORM\ManyToMany(targetEntity="Ok99\PrivateZoneBundle\Entity\TrainingGroup", inversedBy="members")
     * @ORM\JoinTable(name="user_training_group")
     */
    protected $trainingGroups;

    /**
     * @ORM\OneToMany(targetEntity="Ok99\PrivateZoneBundle\Entity\Wallet", mappedBy="user", cascade={"persist"}, orphanRemoval=true)
     */
    protected $wallet;

    /**
     * @var array
     *
     * @ORM\Column(name="roles", type="array")
     */
    protected $roles;

    /**
     * @var string
     *
     * @ORM\Column(name="locale", type="string", length=8, nullable=true)
     */
    protected $locale;

    /**
     * @var string
     *
     * @ORM\Column(name="timezone", type="string", length=64, nullable=true)
     */
    protected $timezone;

    /**
     * @var string
     *
     * @ORM\Column(name="token", type="string", length=255, nullable=true)
     */
    protected $token;

    /**
     * Random string sent to the user email address in order to verify it
     *
     * @var string
     *
     * @ORM\Column(name="confirmation_token", type="string", nullable=true)
     */
    protected $confirmationToken;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="password_requested_at", type="datetime", nullable=true)
     */
    protected $passwordRequestedAt;

    /**
     * @var string
     *
     * @ORM\Column(name="two_step_code", type="string", length=255, nullable=true)
     */
    protected $twoStepVerificationCode;

    /**
     * The salt to use for hashing
     *
     * @var string
     *
     * @ORM\Column(name="salt", type="string")
     */
    protected $salt;

    /**
     * Encrypted password. Must be persisted.
     *
     * @var string
     *
     * @ORM\Column(name="password", type="string")
     */
    protected $password;

    /**
     * Plain password. Used for model validation. Must not be persisted.
     *
     * @var string
     */
    protected $plainPassword;

    /**
     * @var boolean
     *
     * @ORM\Column(name="sponsor", type="boolean")
     */
    protected $sponsor = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="enabled", type="boolean")
     */
    protected $enabled = true;

    /**
     * @var boolean
     *
     * @ORM\Column(name="locked", type="boolean")
     */
    protected $locked = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="expired", type="boolean")
     */
    protected $expired = false;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="expires_at", type="datetime", nullable=true)
     */
    protected $expiresAt;

    /**
     * @var boolean
     *
     * @ORM\Column(name="credentials_expired", type="boolean")
     */
    protected $credentialsExpired = false;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="credentials_expire_at", type="datetime", nullable=true)
     */
    protected $credentialsExpireAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="last_login", type="datetime", nullable=true)
     */
    protected $lastLogin;

    /**
     * @var \DateTime
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * @var \DateTime
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(name="updated_at", type="datetime")
     */
    private $updatedAt;

    private $walletAmount = null;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->salt = base_convert(sha1(uniqid(mt_rand(), true)), 16, 36);

        $this->roles = array();

        $this->groups = new \Doctrine\Common\Collections\ArrayCollection();
        $this->trainingGroups = new \Doctrine\Common\Collections\ArrayCollection();
        $this->messages = new \Doctrine\Common\Collections\ArrayCollection();
        $this->remoteControlRequests = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Returns a string representation
     *
     * @return string
     */
    public function __toString()
    {
        return sprintf('%s %s%s', $this->getName(), $this->getClubShortcut(), $this->getRegnum());
    }

    /**
     * @param string $name
     * @return mixed|null
     */
    public function __get($name)
    {
        if (strpos($name, 'walletAmount_') !== false) {
            list($field, $month, $year) = explode('_', $name);
            return $this->getWalletAmountByMonth($year, $month);
        }
        return null;
    }

    /**
     * @param string $value
     * @param ExecutionContextInterface $context
     */
    public static function validateEmail($value, ExecutionContextInterface $context)
    {
        if ($value) {
            $validator = new EmailValidator();
            $multipleValidations = new MultipleValidationWithAnd([
                new RFCValidation(),
                new DNSCheckValidation()
            ]);

            if (!$validator->isValid($value, $multipleValidations)) {
                $context->buildViolation('fos_user.email.wrong')
                    ->atPath('email')
                    ->addViolation();
            }
        }
    }

    /**
     * @param string $value
     * @param ExecutionContextInterface $context
     */
    public static function validateEmailParent($value, ExecutionContextInterface $context)
    {
        if ($value) {
            $validator = new EmailValidator();
            $multipleValidations = new MultipleValidationWithAnd([
                new RFCValidation(),
                new DNSCheckValidation()
            ]);

            if (strpos($value, ',') !== false) {
                $emails = array_map(function($email){ return trim($email); }, explode(',', $value));
            } else {
                $emails = [$value];
            }

            foreach($emails as $email) {
                if (!$validator->isValid($email, $multipleValidations)) {
                    $context->buildViolation('fos_user.emails.wrong')
                        ->atPath('emailParent')
                        ->addViolation();
                }
            }
        }
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
     * @return int
     */
    public function getOrisId()
    {
        return $this->orisId;
    }

    /**
     * @param int $orisId
     */
    public function setOrisId($orisId)
    {
        $this->orisId = $orisId;
    }

    /**
     * @return int
     */
    public function getOrisClubuserId()
    {
        return $this->orisClubuserId;
    }

    /**
     * @param int $orisClubuserId
     */
    public function setOrisClubuserId($orisClubuserId)
    {
        $this->orisClubuserId = $orisClubuserId;
    }

    /**
     * @param string $username
     * @return $this
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param string $usernameCanonical
     * @return $this
     */
    public function setUsernameCanonical($usernameCanonical)
    {
        $this->usernameCanonical = $usernameCanonical;

        return $this;
    }

    public function getUsernameCanonical()
    {
        return $this->usernameCanonical;
    }

    /**
     * @param $regnum
     * @return $this
     */
    public function setRegnum($regnum)
    {
        $this->regnum = $regnum;

        return $this;
    }

    /**
     * @return string
     */
    public function getRegnum()
    {
        return $this->regnum;
    }

    /**
     * @param $clubShortcut
     * @return $this
     */
    public function setClubShortcut($clubShortcut)
    {
        $this->clubShortcut = $clubShortcut;

        return $this;
    }

    /**
     * @return string
     */
    public function getClubShortcut()
    {
        return $this->clubShortcut;
    }

    /**
     * @param $licence
     * @return $this
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

    /**
     * @return string
     */
    public function getSportident()
    {
        return $this->sportident;
    }

    /**
     * @param $sportident
     * @return $this
     */
    public function setSportident($sportident)
    {
        $this->sportident = $sportident;

        return $this;
    }

    public function getSalt()
    {
        return $this->salt;
    }

    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function setEmailCanonical($emailCanonical)
    {
        $this->emailCanonical = $emailCanonical;

        return $this;
    }

    public function getEmailCanonical()
    {
        return $this->emailCanonical;
    }

    /**
     * @return string
     */
    public function getEmailParent()
    {
        return $this->emailParent;
    }

    /**
     * @param $emailParent
     * @return $this
     */
    public function setEmailParent($emailParent)
    {
        $this->emailParent = $emailParent;

        return $this;
    }

    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Gets the encrypted password.
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param string $password
     * @return $this
     */
    public function setPlainPassword($password)
    {
        $this->plainPassword = $password;

        return $this;
    }

    /**
     * @return string
     */
    public function getPlainPassword()
    {
        return $this->plainPassword;
    }

    /**
     * @param \DateTime|null $date
     * @return $this
     */
    public function setPasswordRequestedAt(\DateTime $date = null)
    {
        $this->passwordRequestedAt = $date;

        return $this;
    }

    /**
     * Gets the timestamp that the user requested a password reset.
     *
     * @return null|\DateTime
     */
    public function getPasswordRequestedAt()
    {
        return $this->passwordRequestedAt;
    }

    /**
     * @param int $ttl
     * @return bool
     */
    public function isPasswordRequestNonExpired($ttl)
    {
        return $this->getPasswordRequestedAt() instanceof \DateTime &&
        $this->getPasswordRequestedAt()->getTimestamp() + $ttl > time();
    }

    /**
     * @param \DateTime $time
     * @return $this
     */
    public function setLastLogin(\DateTime $time)
    {
        $this->lastLogin = $time;

        return $this;
    }

    /**
     * Gets the last login time.
     *
     * @return \DateTime
     */
    public function getLastLogin()
    {
        return $this->lastLogin;
    }

    /**
     * @param string $confirmationToken
     * @return $this
     */
    public function setConfirmationToken($confirmationToken)
    {
        $this->confirmationToken = $confirmationToken;

        return $this;
    }

    public function getConfirmationToken()
    {
        return $this->confirmationToken;
    }

    /**
     * @param \DateTime $dateOfBirth
     */
    public function setDateOfBirth($dateOfBirth)
    {
        $this->dateOfBirth = $dateOfBirth;
    }

    /**
     * @return \DateTime
     */
    public function getDateOfBirth()
    {
        return $this->dateOfBirth;
    }

    /**
     * @param string $firstname
     */
    public function setFirstname($firstname)
    {
        $this->firstname = $firstname;
    }

    /**
     * @return string
     */
    public function getFirstname()
    {
        return $this->firstname;
    }

    /**
     * @param string $lastname
     */
    public function setLastname($lastname)
    {
        $this->lastname = $lastname;
    }

    /**
     * @return string
     */
    public function getLastname()
    {
        return $this->lastname;
    }

    /**
     * @param string $nickname
     */
    public function setNickname($nickname)
    {
        $this->nickname = $nickname;
    }

    /**
     * @return string
     */
    public function getNickname()
    {
        return $this->nickname;
    }

    /**
     * @param string $gender
     */
    public function setGender($gender)
    {
        $this->gender = $gender;
    }

    /**
     * @return string
     */
    public function getGender()
    {
        return $this->gender;
    }

    /**
     * @param string $phone
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;
    }

    /**
     * @return string
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * @return string
     */
    public function getPhoneParent()
    {
        return $this->phoneParent;
    }

    /**
     * @param string $phoneParent
     */
    public function setPhoneParent($phoneParent)
    {
        $this->phoneParent = $phoneParent;
    }

    /**
     * Set avatar
     *
     * @param string $avatar
     * @return User
     */
    public function setAvatar($avatar = null)
    {
        $this->avatar = $avatar;

        return $this;
    }

    /**
     * Get avatar
     *
     * @return string
     */
    public function getAvatar()
    {
        return $this->avatar;
    }

    /**
     * Get avatar pathname
     *
     * @return string
     */
    public function getAvatarPathname()
    {
        if ($this->getAvatar()) {
            return $this->getAvatar();
        } else {
            $pathnameFormat = '/img/avatar_%s.jpg';
            if ($this->getRegnum() > 9999) {
                return sprintf($pathnameFormat, 'admin');
            } else {
                switch($this->getGender()) {
                    case self::GENDER_MALE:
                        return sprintf($pathnameFormat, 'male');
                        break;
                    case self::GENDER_FEMALE:
                        return sprintf($pathnameFormat, 'female');
                        break;
                }
            }
        }
    }

    /**
     * Set photo
     *
     * @param string $photo
     * @return User
     */
    public function setPhoto($photo = null)
    {
        $this->photo = $photo;

        return $this;
    }

    /**
     * Get photo
     *
     * @return string
     */
    public function getPhoto()
    {
        return $this->photo;
    }

    /**
     * Add groups
     *
     * @param \Ok99\PrivateZoneCore\UserBundle\Entity\Group $groups
     * @return User
     */
    public function addGroup(GroupInterface $groups)
    {
        $this->groups[] = $groups;

        return $this;
    }

    /**
     * Remove groups
     *
     * @param \Ok99\PrivateZoneCore\UserBundle\Entity\Group $groups
     */
    public function removeGroup(GroupInterface $groups)
    {
        $this->groups->removeElement($groups);
    }

    /**
     * Get groups
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getGroups()
    {
        return $this->groups ?: $this->groups = new ArrayCollection();
    }

    /**
     * @return array
     */
    public function getGroupNames()
    {
        $names = array();
        foreach ($this->getGroups() as $group) {
            $names[] = $group->getName();
        }

        return $names;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasGroup($name)
    {
        return in_array($name, $this->getGroupNames());
    }

    /**
     * Add role
     *
     * @param string $role
     * @return User
     */
    public function addRole($role)
    {
        $role = strtoupper($role);
        if ($role === static::ROLE_DEFAULT) {
            return $this;
        }

        if (!in_array($role, $this->roles, true)) {
            $this->roles[] = $role;
        }

        return $this;
    }

    /**
     * @param array $roles
     * @return $this
     */
    public function setRoles(array $roles)
    {
        $this->roles = array();

        foreach ($roles as $role) {
            $this->addRole($role);
        }

        return $this;
    }

    /**
     * Returns the user roles
     *
     * @return array The roles
     */
    public function getRoles()
    {
        $roles = $this->roles;

        foreach ($this->getGroups() as $group) {
            $roles = array_merge($roles, $group->getRoles());
        }

        // we need to make sure to have at least one role
        $roles[] = static::ROLE_DEFAULT;

        return array_unique($roles);
    }

    /**
     * Never use this to check if this user has access to anything!
     *
     * Use the SecurityContext, or an implementation of AccessDecisionManager
     * instead, e.g.
     *
     *         $securityContext->isGranted('ROLE_USER');
     *
     * @param string $role
     *
     * @return boolean
     */
    public function hasRole($role)
    {
        return in_array(strtoupper($role), $this->getRoles(), true);
    }

    /**
     * Remove role
     *
     * @param string $role
     * @return User
     */
    public function removeRole($role)
    {
        if (false !== $key = array_search(strtoupper($role), $this->roles, true)) {
            unset($this->roles[$key]);
            $this->roles = array_values($this->roles);
        }

        return $this;
    }

    /**
     * @param string $locale
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
    }

    /**
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * @param string $timezone
     */
    public function setTimezone($timezone)
    {
        $this->timezone = $timezone;
    }

    /**
     * @return string
     */
    public function getTimezone()
    {
        return $this->timezone;
    }

    /**
     * @param string $token
     */
    public function setToken($token)
    {
        $this->token = $token;
    }

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param \DateTime $date
     *
     * @return User
     */
    public function setExpiresAt(\DateTime $date)
    {
        $this->expiresAt = $date;

        return $this;
    }

    /**
     * Returns the expiration date
     *
     * @return \DateTime|null
     */
    public function getExpiresAt()
    {
        return $this->expiresAt;
    }

    /**
     * Returns the credentials expiration date
     *
     * @return \DateTime
     */
    public function getCredentialsExpireAt()
    {
        return $this->credentialsExpireAt;
    }

    /**
     * Sets the credentials expiration date
     *
     * @param \DateTime|null $date
     * @return User
     */
    public function setCredentialsExpireAt(\DateTime $date = null)
    {
        $this->credentialsExpireAt = $date;

        return $this;
    }

    /**
     * Sets the two-step verification code
     *
     * @param string $twoStepVerificationCode
     */
    public function setTwoStepVerificationCode($twoStepVerificationCode)
    {
        $this->twoStepVerificationCode = $twoStepVerificationCode;
    }

    /**
     * Returns the two-step verification code
     *
     * @return string
     */
    public function getTwoStepVerificationCode()
    {
        return $this->twoStepVerificationCode;
    }

    /**
     * Sets the creation date
     *
     * @param \DateTime|null $createdAt
     */
    public function setCreatedAt(\DateTime $createdAt = null)
    {
        $this->createdAt = $createdAt;
    }

    /**
     * Returns the creation date
     *
     * @return \DateTime|null
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Sets the last update date
     *
     * @param \DateTime|null $updatedAt
     */
    public function setUpdatedAt(\DateTime $updatedAt = null)
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * Returns the last update date
     *
     * @return \DateTime|null
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * Returns full name
     *
     * @return string
     */
    public function getName()
    {
        return sprintf("%s %s", $this->getLastname(), $this->getFirstname());
    }

        /**
     * @return array
     */
    public function getRealRoles()
    {
        return $this->roles;
    }

    /**
     * @param array $roles
     */
    public function setRealRoles(array $roles)
    {
        $this->setRoles($roles);
    }

    /**
     * Returns the gender list
     *
     * @return array
     */
    public static function getGenderList()
    {
        return array(
            UserInterface::GENDER_FEMALE  => 'gender_female',
            UserInterface::GENDER_MALE    => 'gender_male',
        );
    }

    /**
     * Removes sensitive data from the user.
     */
    public function eraseCredentials()
    {
        $this->plainPassword = null;
    }

    /**
     * @return bool
     */
    public function isAccountNonExpired()
    {
        if (true === $this->expired) {
            return false;
        }

        if (null !== $this->expiresAt && $this->expiresAt->getTimestamp() < time()) {
            return false;
        }

        return true;
    }

    /**
     * @return bool
     */
    public function isAccountNonLocked()
    {
        return !$this->locked;
    }

    /**
     * @return bool
     */
    public function isCredentialsNonExpired()
    {
        if (true === $this->credentialsExpired) {
            return false;
        }

        if (null !== $this->credentialsExpireAt && $this->credentialsExpireAt->getTimestamp() < time()) {
            return false;
        }

        return true;
    }

    /**
     * @param boolean $boolean
     *
     * @return User
     */
    public function setCredentialsExpired($boolean)
    {
        $this->credentialsExpired = $boolean;

        return $this;
    }

    /**
     * @return bool
     */
    public function isCredentialsExpired()
    {
        return !$this->isCredentialsNonExpired();
    }

    /**
     * @param bool $boolean
     * @return $this
     */
    public function setEnabled($boolean)
    {
        $this->enabled = (Boolean) $boolean;

        return $this;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * Sets this user to expired.
     *
     * @param Boolean $boolean
     *
     * @return User
     */
    public function setExpired($boolean)
    {
        $this->expired = (Boolean) $boolean;

        return $this;
    }

    /**
     * @return bool
     */
    public function isExpired()
    {
        return !$this->isAccountNonExpired();
    }

    /**
     * @param bool $boolean
     * @return $this
     */
    public function setLocked($boolean)
    {
        $this->locked = $boolean;

        return $this;
    }

    /**
     * @return bool
     */
    public function isLocked()
    {
        return !$this->isAccountNonLocked();
    }

    /**
     * @param bool $boolean
     * @return $this
     */
    public function setSuperAdmin($boolean)
    {
        if (true === $boolean) {
            $this->addRole(static::ROLE_SUPER_ADMIN);
        } else {
            $this->removeRole(static::ROLE_SUPER_ADMIN);
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function isSuperAdmin()
    {
        return $this->hasRole(static::ROLE_SUPER_ADMIN);
    }

    /**
     * @param FOSUserInterface|null $user
     * @return bool
     */
    public function isUser(FOSUserInterface $user = null)
    {
        return null !== $user && $this->getId() === $user->getId();
    }

    /**
     * Serializes the user.
     *
     * The serialized data have to contain the fields used by the equals method and the username.
     *
     * @return string
     */
    public function serialize()
    {
        return serialize(array(
            $this->password,
            $this->salt,
            $this->usernameCanonical,
            $this->username,
            $this->expired,
            $this->locked,
            $this->credentialsExpired,
            $this->enabled,
            $this->id,
        ));
    }

    /**
     * Unserializes the user.
     *
     * @param string $serialized
     */
    public function unserialize($serialized)
    {
        $data = unserialize($serialized);
        // add a few extra elements in the array to ensure that we have enough keys when unserializing
        // older data which does not include all properties.
        $data = array_merge($data, array_fill(0, 2, null));

        list(
            $this->password,
            $this->salt,
            $this->usernameCanonical,
            $this->username,
            $this->expired,
            $this->locked,
            $this->credentialsExpired,
            $this->enabled,
            $this->id
            ) = $data;
    }

    /**
     * @return string
     */
    public function getStreet()
    {
        return $this->street;
    }

    /**
     * @param string $street
     */
    public function setStreet($street)
    {
        $this->street = $street;
    }

    /**
     * @return mixed
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * @param mixed $city
     */
    public function setCity($city)
    {
        $this->city = $city;
    }

    /**
     * @return string
     */
    public function getZip()
    {
        return $this->zip;
    }

    /**
     * @param string $zip
     */
    public function setZip($zip)
    {
        $this->zip = $zip;
    }

    /**
     * @return string
     */
    public function getAddress()
    {
        if ($this->getStreet() && $this->getCity()) {
            return sprintf('%s, %s', $this->getStreet(), $this->getCity());
        } elseif ($this->getStreet()) {
            return $this->getStreet();
        } elseif ($this->getCity()) {
            return $this->getCity();
        } else {
            return '';
        }
    }

    /**
     * @return boolean
     */
    public function getSuggestEventClasses()
    {
        return $this->suggestEventClasses;
    }

    /**
     * @return boolean
     */
    public function suggestEventClasses()
    {
        return $this->getSuggestEventClasses();
    }

    /**
     * @param boolean $suggestEventClasses
     */
    public function setSuggestEventClasses($suggestEventClasses)
    {
        $this->suggestEventClasses = $suggestEventClasses;
    }

    /**
     * @param boolean $boolean
     * @return User
     */
    public function setSponsor($boolean)
    {
        $this->sponsor = $boolean;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getSponsor()
    {
        return (boolean)$this->sponsor;
    }

    /**
     * @return boolean
     */
    public function isSponsor()
    {
        return $this->getSponsor();
    }

    /**
     * @return int
     */
    public function getAge()
    {
        $yearOfBorn = substr($this->getRegnum(), 0, 2);
        $yearOfBorn += $yearOfBorn < date('y') ? 2000 : 1900;
        return date('Y') - $yearOfBorn;
    }

    /**
     * @return int
     */
    public function getWalletAmount()
    {
        if (!is_null($this->walletAmount)) return $this->walletAmount;

        $amount = 0;

        /** @var Wallet $payment */
        foreach ($this->wallet as $payment) {
            if ($payment->getIsConfirmed() && !$payment->getIsClubPayment()) {
                $amount += $payment->getAmount();
            }
        }

        $this->walletAmount = $amount;

        return $amount;
    }

    /**
     * @param int $year
     * @param int $month
     * @return int
     */
    public function getWalletAmountByMonth($year, $month)
    {
        $amount = 0;

        /** @var Wallet $payment */
        foreach ($this->wallet as $payment) {
            if ($payment->getIsConfirmed() && !$payment->getIsClubPayment()) {
                if ($payment->getPaymentDate()->format('Y') < $year || $payment->getPaymentDate()->format('n') <= $month) {
                    $amount += $payment->getAmount();
                }
            }
        }

        return $amount;
    }

    /**
     * Get family members
     *
     * @return User[]
     */
    public function getFamilyMembers()
    {
        if (!is_null($this->familyMembers)) return $this->familyMembers;

        $this->familyMembers = [];
        foreach($this->remoteControlRequests as $request) {
            if ($request->getIsConfirmed() && $request->getRemoteControlGroup()->getCode() == RemoteControl::CODE_FAMILY) {
                $this->familyMembers[] = $request->getRecipient();
            }
        }

        $collator = new \Collator('cs_CZ');
        $collator->sort($this->familyMembers);

        return $this->familyMembers;
    }

    /**
     * Has family member
     * @param User $user
     * @return bool
     */
    public function hasFamilyMember(User $user)
    {
        foreach($this->getFamilyMembers() as $member) {
            if ($member->getId() == $user->getId()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Has family members
     * @param User|null $excludedUser
     * @return bool
     */
    public function hasFamilyMembers(User $excludedUser = null)
    {
        if (!$excludedUser) {
            return $this->getFamilyMembers() && count($this->getFamilyMembers()) > 0;
        } else {
            foreach($this->getFamilyMembers() as $member) {
                if ($member->getId() != $excludedUser->getId()) {
                    return true;
                }
            }
            return false;
        }
    }

    /**
     * Get team members
     *
     * @return User[]
     */
    public function getTeamMembers()
    {
        if (!is_null($this->teamMembers)) return $this->teamMembers;

        $this->teamMembers = [];
        foreach($this->remoteControlRequests as $request) {
            if ($request->getIsConfirmed() && $request->getRemoteControlGroup()->getCode() == RemoteControl::CODE_TEAM) {
                $this->teamMembers[] = $request->getRecipient();
            }
        }

        $collator = new \Collator('cs_CZ');
        $collator->sort($this->teamMembers);

        return $this->teamMembers;
    }

    /**
     * Has team members
     * @param User|null $excludedUser
     * @return bool
     */
    public function hasTeamMembers(User $excludedUser = null)
    {
        if (!$excludedUser) {
            return $this->getTeamMembers() && count($this->getTeamMembers()) > 0;
        } else {
            foreach($this->getTeamMembers() as $member) {
                if ($member->getId() != $excludedUser->getId()) {
                    return true;
                }
            }
            return false;
        }
    }

    /**
     * Add message
     *
     * @param Message $message
     * @return User
     */
    public function addMessage(Message $message)
    {
        $this->messages->add($message);

        return $this;
    }

    /**
     * Remove message
     *
     * @param Message $message
     */
    public function removeMessage(Message $message)
    {
        $this->messages->removeElement($message);
    }

    /**
     * Returns messages ordered by ID in descending order
     *
     * @param null $limit
     * @return array
     */
    public function getMessages($limit = null)
    {
        if (!is_null($this->messagesOrdered)) {
            if ($limit && count($this->messagesOrdered) > $limit) {
                return array_slice($this->messagesOrdered, 0, $limit);
            } else {
                return $this->messagesOrdered;
            }
        }

        $this->messagesOrdered = array();
        foreach($this->messages as $message) {
            $this->messagesOrdered[] = $message;
        }

        usort($this->messagesOrdered, function($a, $b){
            return $a->getId() < $b->getId() ? 1 : -1;
        });

        if ($limit) {
            return array_slice($this->messagesOrdered, 0, $limit);
        } else {
            return $this->messagesOrdered;
        }
    }

    /**
     * Get unread messages
     *
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getUnreadMessages()
    {
        if (!is_null($this->messagesUnread)) return $this->messagesUnread;

        $this->messagesUnread = new ArrayCollection();
        foreach($this->getMessages() as $message) {
            if (!$message->getIsOpened()) {
                $this->messagesUnread->add($message);
            }
        }

        return $this->messagesUnread;
    }

    /**
     * Has team members
     *
     * @return boolean
     */
    public function hasUnreadMessages()
    {
        return $this->getUnreadMessages()->count() > 0;
    }

    /**
     * Add training group
     *
     * @param \Ok99\PrivateZoneBundle\Entity\TrainingGroup $trainingGroup
     * @return User
     */
    public function addTrainingGroup(TrainingGroup $trainingGroup)
    {
        $this->trainingGroups[] = $trainingGroup;

        return $this;
    }

    /**
     * Remove training group
     *
     * @param \Ok99\PrivateZoneBundle\Entity\TrainingGroup $trainingGroup
     */
    public function removeTrainingGroup(TrainingGroup $trainingGroup)
    {
        $this->trainingGroups->removeElement($trainingGroup);
    }

    /**
     * Get training groups
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTrainingGroups()
    {
        return $this->trainingGroups ?: $this->trainingGroups = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function getSkinColor()
    {
        return $this->skinColor;
    }

    /**
     * @param string $skinColor
     * @return User
     */
    public function setSkinColor($skinColor)
    {
        $this->skinColor = $skinColor;

        return $this;
    }

    /**
     * @return bool
     */
    public function isMenuSidebarCollapsed()
    {
        return $this->menuSidebarCollapsed;
    }

    /**
     * @return bool
     */
    public function getMenuSidebarCollapsed()
    {
        return $this->menuSidebarCollapsed;
    }

    /**
     * @param bool $menuSidebarCollapsed
     * @return User
     */
    public function setMenuSidebarCollapsed($menuSidebarCollapsed)
    {
        $this->menuSidebarCollapsed = $menuSidebarCollapsed;

        return $this;
    }

    /**
     * @return bool
     */
    public function isMenuSidebarExpandOnHover()
    {
        return $this->menuSidebarExpandOnHover;
    }

    /**
     * @return bool
     */
    public function getMenuSidebarExpandOnHover()
    {
        return $this->menuSidebarExpandOnHover;
    }

    /**
     * @param bool $menuSidebarExpandOnHover
     * @return User
     */
    public function setMenuSidebarExpandOnHover($menuSidebarExpandOnHover)
    {
        $this->menuSidebarExpandOnHover = $menuSidebarExpandOnHover;

        return $this;
    }

    /**
     * @return bool
     */
    public function isControlSidebarLightSkin()
    {
        return $this->controlSidebarLightSkin;
    }

    /**
     * @return bool
     */
    public function getControlSidebarLightSkin()
    {
        return $this->controlSidebarLightSkin;
    }

    /**
     * @param bool $controlSidebarLightSkin
     * @return User
     */
    public function setControlSidebarLightSkin($controlSidebarLightSkin)
    {
        $this->controlSidebarLightSkin = $controlSidebarLightSkin;

        return $this;
    }

    /**
     * Get all remote users
     *
     * @return User[]
     */
    public function getRemoteUsers()
    {
        if (is_null($this->remoteUsers)) {
            $this->remoteUsers = array_merge($this->getFamilyMembers(), $this->getTeamMembers());
            $this->remoteUsers = array_unique($this->remoteUsers);
            (new \Collator('cs_CZ'))->sort($this->remoteUsers);
        }

        return $this->remoteUsers;
    }

    /**
     * Has remote users
     * @param User|null $excludedUser
     * @return bool
     */
    public function hasRemoteUsers(User $excludedUser = null)
    {
        if (!$excludedUser) {
            return $this->getRemoteUsers() && count($this->getRemoteUsers()) > 0;
        } else {
            foreach($this->getRemoteUsers() as $member) {
                if ($member->getId() != $excludedUser->getId()) {
                    return true;
                }
            }
            return false;
        }
    }

    /**
     * Has remote user
     * @param User $user
     * @return bool
     */
    public function hasRemoteUser(User $user)
    {
        foreach($this->getRemoteUsers() as $member) {
            if ($member->getId() == $user->getId()) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $properties = array();
        foreach($this as $key => $value) {
            $properties[$key] = $value;
        }
        return $properties;
    }
}
