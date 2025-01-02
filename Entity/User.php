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
use Ok99\PrivateZoneBundle\Entity\ContactGroup;
use Ok99\PrivateZoneBundle\Entity\Event;
use Ok99\PrivateZoneBundle\Entity\EventCup;
use Ok99\PrivateZoneBundle\Entity\EventDiscipline;
use Ok99\PrivateZoneBundle\Entity\EventLevel;
use Ok99\PrivateZoneBundle\Entity\EventSport;
use Ok99\PrivateZoneBundle\Entity\EventSportidentType;
use Ok99\PrivateZoneBundle\Entity\Message;
use Ok99\PrivateZoneBundle\Entity\PerformanceGroup;
use Ok99\PrivateZoneBundle\Entity\RemoteControl;
use Ok99\PrivateZoneBundle\Entity\TrainingGroup;
use Ok99\PrivateZoneBundle\Entity\UserPrivacyPolicy;
use Ok99\PrivateZoneBundle\Entity\Wallet;
use Ok99\PrivateZoneBundle\Entity\WalletFinancialStatementBalance;
use Ok99\PrivateZoneCore\ClassificationBundle\Entity\Category;
use Sonata\UserBundle\Model\UserInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @ORM\Table(name="fos_user_user",indexes={
 *     @ORM\Index(name="IDX_REGNUM", columns={"regnum"})
 * })
 * @ORM\Entity(repositoryClass="Ok99\PrivateZoneCore\UserBundle\Entity\Repository\UserRepository")
 * @UniqueEntity(fields="usernameCanonical", errorPath="username", message="fos_user.username.already_used")
 * @ORM\AttributeOverrides({
 *      @ORM\AttributeOverride(name="email", column=@ORM\Column(type="string", name="email", length=255, unique=false, nullable=true)),
 *      @ORM\AttributeOverride(name="emailCanonical", column=@ORM\Column(type="string", name="email_canonical", length=255, unique=false, nullable=true))
 * })
 */
class User extends BaseUser implements UserInterface
{
    const ID_HANDLER = 'User/id';

    public static $phoneAliases = [
        'phone',
        'phone2',
        'phone3',
    ];

    public static $sportidentAliases = [
        'sportident',
        'sportident2',
        'sportident3',
    ];

    const DEFAULT_COUNTRY = 'CZ';

    public static $countries = [
        'AF' => 'Afghánistán',
        'AL' => 'Albánie',
        'DZ' => 'Alžírsko',
        'AS' => 'Americká Samoa',
        'VI' => 'Americké Panenské ostrovy',
        'AD' => 'Andorra',
        'AO' => 'Angola',
        'AI' => 'Anguilla',
        'AQ' => 'Antarktika',
        'AG' => 'Antigua a Barbuda',
        'AR' => 'Argentina',
        'AM' => 'Arménie',
        'AW' => 'Aruba',
        'AU' => 'Austrálie',
        'AZ' => 'Ázerbajdžán',
        'BS' => 'Bahamy',
        'BH' => 'Bahrajn',
        'BD' => 'Bangladéš',
        'BB' => 'Barbados',
        'MM' => 'Barma',
        'BE' => 'Belgie',
        'BZ' => 'Belize',
        'BY' => 'Bělorusko',
        'BJ' => 'Benin',
        'BM' => 'Bermudy',
        'BT' => 'Bhútán',
        'BO' => 'Bolívie',
        'BA' => 'Bosna a Hercegovina',
        'BW' => 'Botswana',
        'BV' => 'Bouvetův ostrov',
        'BR' => 'Brazílie',
        'IO' => 'Britské indickooceánské teritorium',
        'VG' => 'Britské Panenské ostrovy',
        'BN' => 'Brunej',
        'BG' => 'Bulharsko',
        'BF' => 'Burkina Faso',
        'BI' => 'Burundi',
        'CK' => 'Cookovy ostrovy',
        'TD' => 'Čad',
        'CZ' => 'Česká republika',
        'CN' => 'Čína',
        'DK' => 'Dánsko',
        'DM' => 'Dominika',
        'DO' => 'Dominikánská republika',
        'DJ' => 'Džibuti',
        'EG' => 'Egypt',
        'EC' => 'Ekvádor',
        'ER' => 'Eritrea',
        'EE' => 'Estonsko',
        'ET' => 'Etiopie',
        'FO' => 'Faerské ostrovy',
        'FK' => 'Falklandy',
        'FJ' => 'Fidži',
        'PH' => 'Filipíny',
        'FI' => 'Finsko',
        'FR' => 'Francie',
        'GF' => 'Francouzská Guyana',
        'PF' => 'Francouzská Polynézie',
        'GA' => 'Gabun',
        'GM' => 'Gambie',
        'GH' => 'Ghana',
        'GI' => 'Gibraltar',
        'GD' => 'Grenada',
        'GL' => 'Grónsko',
        'GE' => 'Gruzie',
        'GP' => 'Guadeloupe',
        'TF' => 'Guam',
        'GU' => 'Guam',
        'GT' => 'Guatemala',
        'GN' => 'Guinea',
        'GW' => 'Guinea-Bissau',
        'GY' => 'Guyana',
        'HT' => 'Haiti',
        'HM' => 'Heardův ostrov a McDonaldovy ostrovy',
        'HN' => 'Honduras',
        'HK' => 'Hongkong',
        'CL' => 'Chile',
        'HR' => 'Chorvatsko',
        'IN' => 'Indie',
        'ID' => 'Indonézie',
        'IQ' => 'Irák',
        'IR' => 'Írán',
        'IE' => 'Irsko',
        'IS' => 'Island',
        'IT' => 'Itálie',
        'IL' => 'Izrael',
        'JM' => 'Jamajka',
        'JP' => 'Japonsko',
        'YE' => 'Jemen',
        'ZA' => 'Jihoafrická republika',
        'GS' => 'Jižní Georgie a Jižní Sandwichovy ostrovy',
        'JO' => 'Jordánsko',
        'KY' => 'Kajmanské ostrovy',
        'KH' => 'Kambodža',
        'CM' => 'Kamerun',
        'CA' => 'Kanada',
        'CV' => 'Kapverdy',
        'QA' => 'Katar',
        'KZ' => 'Kazachstán',
        'KE' => 'Keňa',
        'KI' => 'Kiribati',
        'CC' => 'Kokosové ostrovy',
        'CO' => 'Kolumbie',
        'KM' => 'Komory',
        'CG' => 'Kongo',
        'CD' => 'Kongo (Demokratická republika Kongo)',
        'KP' => 'Korea (Korejská lidově demokratická republika)',
        'KR' => 'Korea (Korejská republika)',
        'CR' => 'Kostarika',
        'CU' => 'Kuba',
        'KW' => 'Kuvajt',
        'CY' => 'Kypr',
        'KG' => 'Kyrgyzstán',
        'LA' => 'Laos',
        'LS' => 'Lesotho',
        'LB' => 'Libanon',
        'LR' => 'Libérie',
        'LY' => 'Libye',
        'LI' => 'Lichtenštejnsko',
        'LT' => 'Litva',
        'LV' => 'Lotyšsko',
        'LU' => 'Lucembursko',
        'MG' => 'Madagaskar',
        'HU' => 'Maďarsko',
        'MO' => 'Makao',
        'MK' => 'Makedonie',
        'MY' => 'Malajsie',
        'MW' => 'Malawi',
        'MV' => 'Maledivy',
        'ML' => 'Mali',
        'MT' => 'Malta',
        'MA' => 'Maroko',
        'MH' => 'Marshallovy ostrovy',
        'MQ' => 'Martinik',
        'MR' => 'Mauretánie',
        'MU' => 'Mauricius',
        'YT' => 'Mayotte',
        'MX' => 'Mexiko',
        'FM' => 'Mikronésie',
        'MD' => 'Moldavsko',
        'MC' => 'Monako',
        'MN' => 'Mongolsko',
        'MS' => 'Montserrat',
        'MZ' => 'Mosambik',
        'NA' => 'Namíbie',
        'NR' => 'Nauru',
        'DE' => 'Německo',
        'NP' => 'Nepál',
        'NE' => 'Niger',
        'NG' => 'Nigérie',
        'NI' => 'Nikaragua',
        'NU' => 'Niue',
        'NL' => 'Nizozemí',
        'AN' => 'Nizozemské Antily',
        'NF' => 'Norfolk',
        'NO' => 'Norsko',
        'NC' => 'Nová Kaledonie',
        'NZ' => 'Nový Zéland',
        'OM' => 'Omán',
        'UM' => 'Ostrovy USA v Tichém oceánu',
        'PK' => 'Pákistán',
        'PW' => 'Palau',
        'PS' => 'Palestina',
        'PA' => 'Panama',
        'PG' => 'Papua Nová Guinea',
        'PY' => 'Paraguay',
        'PE' => 'Peru',
        'PN' => 'Pitcairnovy ostrovy',
        'CI' => 'Pobřeží slonoviny',
        'PL' => 'Polsko',
        'PR' => 'Portoriko',
        'PT' => 'Portugalsko',
        'AT' => 'Rakousko',
        'RE' => 'Réunion',
        'GQ' => 'Rovníková Guinea',
        'RO' => 'Rumunsko',
        'RU' => 'Rusko',
        'RW' => 'Rwanda',
        'GR' => 'Řecko',
        'SV' => 'Salvador',
        'WS' => 'Samoa',
        'SM' => 'San Marino',
        'SA' => 'Saúdská Arábie',
        'SN' => 'Senegal',
        'MP' => 'Severní Mariany',
        'SC' => 'Seychely',
        'SL' => 'Sierra Leone',
        'SG' => 'Singapur',
        'SK' => 'Slovensko',
        'SI' => 'Slovinsko',
        'SO' => 'Somálsko',
        'AE' => 'Spojené arabské emiráty',
        'US' => 'Spojené státy americké',
        'CS' => 'Srbsko a Černá hora',
        'LK' => 'Srí Lanka',
        'CF' => 'Středoafrická republika',
        'SD' => 'Súdán',
        'SR' => 'Surinam',
        'SH' => 'Svatá Helena',
        'LC' => 'Svatá Lucie',
        'KN' => 'Svatý Kryštof a Nevis',
        'PM' => 'Svatý Petr a Mikelon',
        'ST' => 'Svatý Tomáš a Princův ostrov',
        'VC' => 'Svatý Vincenc a Grenadiny',
        'SZ' => 'Svazijsko',
        'SY' => 'Sýrie',
        'SB' => 'Šalomounovy ostrovy',
        'ES' => 'Španělsko',
        'SJ' => 'Špicberky a Jan Mayen',
        'SE' => 'Švédsko',
        'CH' => 'Švýcarsko',
        'TJ' => 'Tádžikistán',
        'TZ' => 'Tanzánie',
        'TH' => 'Thajsko',
        'TW' => 'Tchajwan',
        'TG' => 'Togo',
        'TK' => 'Tokelau',
        'TO' => 'Tonga',
        'TT' => 'Trinidad a Tobago',
        'TN' => 'Tunisko',
        'TR' => 'Turecko',
        'TM' => 'Turkmenistán',
        'TC' => 'Turks a Caicos',
        'TV' => 'Tuvalu',
        'UG' => 'Uganda',
        'UA' => 'Ukrajina',
        'UY' => 'Uruguay',
        'UZ' => 'Uzbekistán',
        'CX' => 'Vánoční ostrov (v Indickém oceánu)',
        'VU' => 'Vanuatu',
        'VA' => 'Vatikán',
        'GB' => 'Velká Británie a Severní Irsko',
        'VE' => 'Venezuela',
        'VN' => 'Vietnam',
        'WF' => 'Wallisovy ostrovy',
        'ZM' => 'Zambie',
        'EH' => 'Západní Sahara',
        'ZW' => 'Zimbabwe',
    ];

    /**
     * @var integer $id
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var integer|null
     *
     * @ORM\Column(name="oris_id", type="integer", nullable=true)
     */
    protected $orisId;

    /**
     * @var integer|null
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
     * @ORM\Column(name="regnum", type="string", length=5)
     * @Assert\Callback(
     *      callback={"Ok99\PrivateZoneCore\UserBundle\Entity\User","validateRegnum"},
     *  )
     */
    protected $regnum;

    /**
     * @var string
     *
     * @ORM\Column(name="club_shortcut", type="string", length=8)
     */
    protected $clubShortcut;

    /**
     * @var integer
     *
     * @ORM\ManyToOne(targetEntity="Ok99\PrivateZoneBundle\Entity\EventSport")
     * @ORM\JoinColumn(name="sportident_sport_id", referencedColumnName="id", onDelete="SET NULL", nullable=true)
     */
    private $sportidentSport;

    /**
     * @var integer
     *
     * @ORM\ManyToOne(targetEntity="Ok99\PrivateZoneBundle\Entity\EventSportidentType")
     * @ORM\JoinColumn(name="sportident_type_id", referencedColumnName="id", onDelete="SET NULL", nullable=true)
     */
    private $sportidentType;

    /**
     * @var string
     *
     * @ORM\Column(name="sportident", type="string", length=16, nullable=true)
     * @Assert\Regex(pattern="/^\d*$/", message="fos_user.sportident.wrong")
     */
    protected $sportident;

    /**
     * @var integer
     *
     * @ORM\ManyToOne(targetEntity="Ok99\PrivateZoneBundle\Entity\EventSport")
     * @ORM\JoinColumn(name="sportident2_sport_id", referencedColumnName="id", onDelete="SET NULL", nullable=true)
     */
    private $sportident2Sport;

    /**
     * @var integer
     *
     * @ORM\ManyToOne(targetEntity="Ok99\PrivateZoneBundle\Entity\EventSportidentType")
     * @ORM\JoinColumn(name="sportident2_type_id", referencedColumnName="id", onDelete="SET NULL", nullable=true)
     */
    private $sportident2Type;

    /**
     * @var string
     *
     * @ORM\Column(name="sportident2", type="string", length=16, nullable=true)
     * @Assert\Regex(pattern="/^\d*$/", message="fos_user.sportident.wrong")
     */
    protected $sportident2;

    /**
     * @var integer
     *
     * @ORM\ManyToOne(targetEntity="Ok99\PrivateZoneBundle\Entity\EventSport")
     * @ORM\JoinColumn(name="sportident3_sport_id", referencedColumnName="id", onDelete="SET NULL", nullable=true)
     */
    private $sportident3Sport;

    /**
     * @var integer
     *
     * @ORM\ManyToOne(targetEntity="Ok99\PrivateZoneBundle\Entity\EventSportidentType")
     * @ORM\JoinColumn(name="sportident3_type_id", referencedColumnName="id", onDelete="SET NULL", nullable=true)
     */
    private $sportident3Type;

    /**
     * @var string
     *
     * @ORM\Column(name="sportident3", type="string", length=16, nullable=true)
     * @Assert\Regex(pattern="/^\d*$/", message="fos_user.sportident.wrong")
     */
    protected $sportident3;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_of_birth", type="datetime", nullable=true)
     */
    protected $dateOfBirth;

    /**
     * @var string
     *
     * @ORM\Column(name="birth_registration_number", type="string", length=64, nullable=true)
     */
    protected $birthRegistrationNumber;

    /**
     * @var string
     *
     * @ORM\Column(name="identity_card_number", type="string", length=64, nullable=true)
     */
    protected $identityCardNumber;

    /**
     * @var string|null
     *
     * @ORM\Column(name="iof_id", type="string", length=64, nullable=true)
     */
    protected $iofId;

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
     * @var string|null
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
     protected $description;

    /**
     * @var string
     *
     * @ORM\Column(name="gender", type="string", length=1, nullable=true)
     */
    protected $gender = UserInterface::GENDER_UNKNOWN;

    /**
     * @var string|null
     *
     * @ORM\Column(name="email", type="string", length=255, nullable=true, unique=false)
     * @Assert\Callback(
     *     callback={"Ok99\PrivateZoneCore\UserBundle\Entity\User","validateEmail"},
     * )
     */
    protected $email;

    /**
     * @var string|null
     *
     * @ORM\Column(name="email_canonical", type="string", length=255, nullable=true, unique=false)
     */
    protected $emailCanonical;

    /**
     * @var string
     *
     * @ORM\Column(name="email_parent", type="string", length=255, nullable=true)
     * @Assert\Callback(
     *     callback={"Ok99\PrivateZoneCore\UserBundle\Entity\User","validateEmailParent"},
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
     * @ORM\Column(name="phone_name", type="string", length=128, nullable=true)
     */
    protected $phoneName;

    /**
     * @var string
     *
     * @ORM\Column(name="phone2", type="string", length=64, nullable=true)
     */
    protected $phone2;

    /**
     * @var string
     *
     * @ORM\Column(name="phone2_name", type="string", length=128, nullable=true)
     */
    protected $phone2Name;

    /**
     * @var string
     *
     * @ORM\Column(name="phone3", type="string", length=64, nullable=true)
     */
    protected $phone3;

    /**
     * @var string
     *
     * @ORM\Column(name="phone3_name", type="string", length=128, nullable=true)
     */
    protected $phone3Name;

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
     * @var string|null
     *
     * @ORM\Column(name="country", type="string", length=16, nullable=true)
     */
    protected $country;

    /**
     * @var float|null
     *
     * @ORM\Column(name="address_latitude", type="float", nullable=true)
     */
    private $addressLatitude;

    /**
     * @var float|null
     *
     * @ORM\Column(name="address_longitude", type="float", nullable=true)
     */
    private $addressLongitude;

    /**
     * @var string|null
     *
     * @ORM\Column(name="address_checksum", type="string", nullable=true)
     */
    private $addressChecksum;

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
     * @var boolean
     *
     * @ORM\Column(name="dont_display_personal_data", type="boolean")
     */
    protected $dontDisplayPersonalData = false;

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
     *      joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="group_id", referencedColumnName="id", onDelete="CASCADE")})
     */
    protected $groups;

    /**
     * @ORM\ManyToMany(targetEntity="Ok99\PrivateZoneBundle\Entity\PerformanceGroup", mappedBy="members")
     * @ORM\JoinTable(name="performance_group_members",
     *      joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="performance_group_id", referencedColumnName="id", onDelete="CASCADE")})
     */
    private $performanceGroups;

    /**
     * @ORM\ManyToMany(targetEntity="Ok99\PrivateZoneBundle\Entity\TrainingGroup", mappedBy="membersSupported")
     * @ORM\JoinTable(name="training_group_members_supported",
     *      joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="training_group_id", referencedColumnName="id", onDelete="CASCADE")})
     */
    private $trainingGroupsSupported;

    /**
     * @ORM\ManyToMany(targetEntity="Ok99\PrivateZoneBundle\Entity\TrainingGroup", mappedBy="membersNotSupported")
     * @ORM\JoinTable(name="training_group_members_not_supported",
     *      joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="training_group_id", referencedColumnName="id", onDelete="CASCADE")})
     */
    private $trainingGroupsNotSupported;

    /**
     * @ORM\ManyToMany(targetEntity="Ok99\PrivateZoneBundle\Entity\ContactGroup", mappedBy="members")
     * @ORM\JoinTable(name="contact_group_members",
     *      joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="contact_group_id", referencedColumnName="id", onDelete="CASCADE")})
     */
    private $contactGroups;

    /**
     * @ORM\OneToMany(targetEntity="Ok99\PrivateZoneBundle\Entity\Wallet", mappedBy="user", cascade={"persist"}, orphanRemoval=true)
     */
    protected $wallet;

    /**
     * @ORM\OneToMany(targetEntity="Ok99\PrivateZoneCore\UserBundle\Entity\UserSportLicence", mappedBy="user", cascade={"persist"}, orphanRemoval=true)
     */
    protected $sportLicences;

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
     * @ORM\Column(name="guest", type="boolean")
     */
    protected $guest = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="deenabled_manually", type="boolean")
     */
    protected $deenabledManually = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="deactivated", type="boolean")
     */
    protected $deactivated = false;

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

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="last_data_update_at", type="datetime", nullable=true)
     */
    private $lastDataUpdateAt;

    /**
     * @ORM\OneToMany(targetEntity="Ok99\PrivateZoneBundle\Entity\UserPrivacyPolicy", mappedBy="user", cascade={"persist"}, orphanRemoval=true)
     */
    protected $privacyPolicyAgreements;

    /*** NOTIFY ENTRY TERMS ***/

    /**
     * @var boolean
     *
     * @ORM\Column(name="notify_event_entry_dates", type="boolean")
     */
    private $notifyEventEntryDates = true;

    /**
     * @var boolean
     *
     * @ORM\Column(name="notify_club_event_entry_dates", type="boolean")
     */
    private $notifyClubEventEntryDates = true;

    /**
     * @var boolean
     *
     * @ORM\Column(name="notify_first_event_entry_date_only", type="boolean")
     */
    private $notifyFirstEventEntryDateOnly = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="notify_documents", type="boolean")
     */
    private $notifyDocuments = false;

    /**
     * @ORM\ManyToMany(targetEntity="Ok99\PrivateZoneCore\ClassificationBundle\Entity\Category", mappedBy="notifyRecipients")
     * @ORM\JoinTable(name="user_notify_document_categories",
     *      joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="document_category_id", referencedColumnName="id")})
     */
    private $notifyDocumentCategories;

    /**
     * @var integer
     *
     * @ORM\Column(name="amount_of_days_before_event_entry_date_to_notify", type="integer", nullable=true)
     */
    private $amountOfDaysBeforeEventEntryDateToNotify;

    /**
     * @ORM\ManyToMany(targetEntity="Ok99\PrivateZoneBundle\Entity\EventSport")
     * @ORM\JoinTable(name="user_notify_event_sports",
     *      joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="event_sport_id", referencedColumnName="id")})
     */
    private $notifyEventSports;

    /**
     * @ORM\ManyToMany(targetEntity="Ok99\PrivateZoneBundle\Entity\EventLevel")
     * @ORM\JoinTable(name="user_notify_event_levels",
     *      joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="event_level_id", referencedColumnName="id")})
     */
    private $notifyEventLevels;

    /**
     * @ORM\ManyToMany(targetEntity="Ok99\PrivateZoneBundle\Entity\EventDiscipline")
     * @ORM\JoinTable(name="user_notify_event_disciplines",
     *      joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="event_discipline_id", referencedColumnName="id")})
     */
    private $notifyEventDisciplines;

    /**
     * @ORM\ManyToMany(targetEntity="Ok99\PrivateZoneBundle\Entity\EventCup")
     * @ORM\JoinTable(name="user_notify_event_cups",
     *      joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="event_cup_id", referencedColumnName="id")})
     */
    private $notifyEventCups;

    /**
     * @ORM\OneToMany(targetEntity="\Ok99\PrivateZoneBundle\Entity\WalletFinancialStatementBalance", mappedBy="user", cascade={"persist"}, orphanRemoval=true)
     */
    private $financialStatementBalances;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->salt = base_convert(sha1(uniqid(mt_rand(), true)), 16, 36);

        $this->roles = [];
        $this->sportLicences = new ArrayCollection();
        $this->groups = new ArrayCollection();

        $this->performanceGroups = new ArrayCollection();
        $this->trainingGroupsSupported = new ArrayCollection();
        $this->trainingGroupsNotSupported = new ArrayCollection();
        $this->contactGroups = new ArrayCollection();

        $this->messages = new ArrayCollection();
        $this->remoteControlRequests = new ArrayCollection();

        $this->privacyPolicyAgreements = new ArrayCollection();

        $this->notifyEventSports = new ArrayCollection();
        $this->notifyEventLevels = new ArrayCollection();
        $this->notifyEventDisciplines = new ArrayCollection();
        $this->notifyEventCups = new ArrayCollection();

        $this->notifyDocumentCategories = new ArrayCollection();

        $this->financialStatementBalances = new ArrayCollection();
    }

    /**
     * Returns a string representation
     *
     * @return string
     */
    public function __toString()
    {
        return sprintf('%s %s', $this->getName(), $this->getFullRegnum());
    }

    /**
     * @param string $name
     * @return mixed|null
     */
    public function __get($name)
    {
        if (strpos($name, 'walletAmount_') !== false) {
            list($field, $month, $year, $hasFinancialStatements) = explode('_', $name);
            return $this->getWalletAmountByMonth($year, $month, (bool)$hasFinancialStatements);
        }
        return null;
    }

    /**
     * @Assert\Callback
     */
    public function validate(\Symfony\Component\Validator\Context\ExecutionContextInterface $context)
    {
        if (!$this->getId() && !$this->getPlainPassword()) {
            $context->buildViolation('fos_user.password.blank')
                ->atPath('plainPassword')
                ->addViolation();
        }
    }

    /**
     * @param string $value
     * @param ExecutionContextInterface $context
     */
    public static function validateRegnum($value, ExecutionContextInterface $context)
    {
        if ($value && !is_numeric($value)) {
            $context->buildViolation('fos_user.regnum.non-numeric')
                ->atPath('regnum')
                ->addViolation();
        }
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
     * @return int|null
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
     * @return int|null
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

    public function isOrisAcceptable(): bool
    {
        return
            $this->getOrisId() !== null &&
            $this->getOrisClubuserId() !== null;
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
     * @return string
     */
    public function getFullRegnum()
    {
        $fullRegnum = '';

        if ($this->clubShortcut !== null) {
            $fullRegnum .= strtoupper($this->clubShortcut);
        }

        $fullRegnum .= $this->regnum;

        return $fullRegnum;
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

    public function getSalt()
    {
        return $this->salt;
    }

    public function setEmail($email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmailCanonical($emailCanonical): self
    {
        $this->emailCanonical = $emailCanonical;

        return $this;
    }

    public function getEmailCanonical(): ?string
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
     * @return string[]
     */
    public function getParentEmails()
    {
        $emails = explode(',', $this->getEmailParent());
        $emails = array_map(function ($email){
            return trim($email);
        }, $emails);
        return $emails;
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
     * @param string|null $birthRegistrationNumber
     */
    public function setBirthRegistrationNumber($birthRegistrationNumber)
    {
        $this->birthRegistrationNumber = $birthRegistrationNumber;
    }

    /**
     * @return string|null
     */
    public function getBirthRegistrationNumber()
    {
        return $this->birthRegistrationNumber;
    }

    /**
     * @param string|null $identityCardNumber
     */
    public function setIdentityCardNumber($identityCardNumber)
    {
        $this->identityCardNumber = $identityCardNumber;
    }

    /**
     * @return string|null
     */
    public function getIdentityCardNumber()
    {
        return $this->identityCardNumber;
    }

    public function setIofId(?string $iofId): self
    {
        $this->iofId = $iofId;

        return $this;
    }

    public function getIofId(): ?string
    {
        return $this->iofId;
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
        if (
            $this->gender !== null &&
            in_array($this->gender, [
                self::GENDER_FEMALE,
                self::GENDER_MALE,
            ])
        ) {
            return $this->gender;
        }

        $regnum = $this->getRegnum();
        $serialNumber = (int) substr($regnum, 2, 2);

        if ($serialNumber >= 50) {
            return self::GENDER_FEMALE;
        } else {
            return self::GENDER_MALE;
        }
    }

    /**
     * @param string|null $phone
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;
    }

    /**
     * @return string|null
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * @param string|null $phoneName
     */
    public function setPhoneName($phoneName)
    {
        $this->phoneName = $phoneName;
    }

    /**
     * @return string|null
     */
    public function getPhoneName()
    {
        return $this->phoneName;
    }

    /**
     * @param string|null $phone2
     */
    public function setPhone2($phone2)
    {
        $this->phone2 = $phone2;
    }

    /**
     * @return string|null
     */
    public function getPhone2()
    {
        return $this->phone2;
    }

    /**
     * @param string|null $phone2Name
     */
    public function setPhone2Name($phone2Name)
    {
        $this->phone2Name = $phone2Name;
    }

    /**
     * @return string|null
     */
    public function getPhone2Name()
    {
        return $this->phone2Name;
    }

    /**
     * @param string|null $phone3
     */
    public function setPhone3($phone3)
    {
        $this->phone3 = $phone3;
    }

    /**
     * @return string|null
     */
    public function getPhone3()
    {
        return $this->phone3;
    }

    /**
     * @param string|null $phone3Name
     */
    public function setPhone3Name($phone3Name)
    {
        $this->phone3Name = $phone3Name;
    }

    /**
     * @return string|null
     */
    public function getPhone3Name()
    {
        return $this->phone3Name;
    }

    /**
     * @param string $phoneParent
     */
    public function setPhoneParent($phoneParent)
    {
        $this->phoneParent = $phoneParent;
    }

    /**
     * @return string
     */
    public function getPhoneParent()
    {
        return $this->phoneParent;
    }

    /**
     * @return string[]
     */
    public function getParentPhones()
    {
        $phones = explode(',', $this->getPhoneParent());
        $phones = array_map(function ($phone){
            return trim($phone);
        }, $phones);
        return $phones;
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
                    case self::GENDER_FEMALE:
                        return sprintf($pathnameFormat, 'female');
                        break;
                    default:
                        return sprintf($pathnameFormat, 'male');
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
     * Sets the last update date
     *
     * @param \DateTime|null $lastDataUpdateAt
     */
    public function setLastDataUpdateAt(\DateTime $lastDataUpdateAt = null)
    {
        $this->lastDataUpdateAt = $lastDataUpdateAt;
    }

    /**
     * Returns the last update date
     *
     * @return \DateTime|null
     */
    public function getLastDataUpdateAt()
    {
        return $this->lastDataUpdateAt;
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

    public function setGuest($guest): self
    {
        $this->guest = (boolean) $guest;

        return $this;
    }

    public function isGuest(): bool
    {
        return $this->guest;
    }

    /**
     * @param bool $boolean
     * @return $this
     */
    public function setDeenabledManually($boolean)
    {
        $this->deenabledManually = (Boolean) $boolean;

        return $this;
    }

    /**
     * @return bool
     */
    public function isDeenabledManually()
    {
        return $this->deenabledManually;
    }

    /**
     * @param bool $boolean
     * @return $this
     */
    public function setDeactivated($boolean)
    {
        $this->deactivated = (Boolean) $boolean;

        return $this;
    }

    /**
     * @return bool
     */
    public function isDeactivated()
    {
        return $this->deactivated;
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

    public function setCountry(?string $country): self
    {
        $this->country = $country;

        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    /**
     * @return string
     */
    public function getAddress(bool $containsCountry = false)
    {
        $chunks = [];
        if ($this->getStreet()) {
            $chunks[] = $this->getStreet();
        }
        if ($this->getCity()) {
            $chunks[] = $this->getCity();
        }
        if ($this->getZip()) {
            $chunks[] = $this->getZip();
        }
        if (
            $this->getCountry() !== null &&
            (
                $containsCountry ||
                $this->getCountry() !== self::DEFAULT_COUNTRY
            )
        ) {
            $chunks[] = self::$countries[$this->getCountry()];
        }
        return implode(', ', $chunks);
    }

    public function getAddressLatitude(): ?float
    {
        return $this->addressLatitude;
    }

    public function setAddressLatitude(?float $addressLatitude): void
    {
        $this->addressLatitude = $addressLatitude;
    }

    public function getAddressLongitude(): ?float
    {
        return $this->addressLongitude;
    }

    public function setAddressLongitude(?float $addressLongitude): void
    {
        $this->addressLongitude = $addressLongitude;
    }

    public function getAddressChecksum(): ?string
    {
        return $this->addressChecksum;
    }

    public function setAddressChecksum(?string $addressChecksum): void
    {
        $this->addressChecksum = $addressChecksum;
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
    public function setSuggestEventClasses($suggestEventClasses): self
    {
        $this->suggestEventClasses = $suggestEventClasses;

        return $this;
    }

    public function isDontDisplayPersonalData(): bool
    {
        return $this->dontDisplayPersonalData;
    }

    public function getDontDisplayPersonalData(): bool
    {
        return $this->dontDisplayPersonalData;
    }

    public function setDontDisplayPersonalData(bool $dontDisplayPersonalData): self
    {
        $this->dontDisplayPersonalData = $dontDisplayPersonalData;

        return $this;
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
        $amount = 0;

        /** @var Wallet $payment */
        foreach ($this->wallet as $payment) {
            if ($payment->getIsConfirmed() && !$payment->getIsClubPayment()) {
                $amount += $payment->getAmount();
            }
        }

        return $amount;
    }

    /**
     * @param int $year
     * @param int $month
     * @param bool $hasFinancialStatements
     * @return int
     */
    public function getWalletAmountByMonth($year, $month, $hasFinancialStatements = false)
    {
        $lastStatementBalance = null;

        $amount = 0;

        if ($hasFinancialStatements) {
            $balances = [];

            // find statements less or equal requested date
            /** @var WalletFinancialStatementBalance $balance */
            foreach($this->getFinancialStatementBalances()->getValues() as $balance) {
                if (
                    $balance->getFinancialStatement()->getYear() < $year
                    ||
                    (
                        $balance->getFinancialStatement()->getYear() == $year
                        &&
                        $balance->getFinancialStatement()->getQuarter() * 3 <= $month
                    )
                ) {
                    $balances[] = $balance;
                }
            }

            if ($balances) {
                // sort statements by date descendantly
                usort($balances, function (WalletFinancialStatementBalance $a, WalletFinancialStatementBalance $b){
                    return
                        $a->getFinancialStatement()->getYear() < $b->getFinancialStatement()->getYear()
                        ||
                        (
                            $a->getFinancialStatement()->getYear() == $b->getFinancialStatement()->getYear()
                            &&
                            $a->getFinancialStatement()->getQuarter() < $b->getFinancialStatement()->getQuarter()
                        )
                    ;
                });

                // get last balance
                $lastStatementBalance = $balances[0];
                $amount = $lastStatementBalance->getBalance();
            }
        }

        /** @var Wallet $payment */
        foreach ($this->wallet as $payment) {
            if (
                $payment->getIsConfirmed()
                && !$payment->getIsClubPayment()
                &&
                (
                    !$hasFinancialStatements
                    ||
                    !$payment->getFinancialStatement()
                    ||
                    !$lastStatementBalance
                    ||
                    $payment->getFinancialStatement()->getCreatedAt() > $lastStatementBalance->getFinancialStatement()->getCreatedAt()
                )
            ) {
                if (
                    $payment->getPaymentDate()->format('Y') < $year
                    ||
                    (
                        $payment->getPaymentDate()->format('Y') == $year
                        &&
                        $payment->getPaymentDate()->format('n') <= $month
                    )
                ) {
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
        /** @var RemoteControl $request */
        foreach($this->remoteControlRequests as $request) {
            if (
                $request->getIsConfirmed()
                && $request->getRemoteControlGroup()->getCode() == RemoteControl::CODE_FAMILY
                && $request->getRecipient()->isEnabled()
            ) {
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
        /** @var RemoteControl $request */
        foreach($this->remoteControlRequests as $request) {
            if (
                $request->getIsConfirmed()
                && $request->getRemoteControlGroup()->getCode() == RemoteControl::CODE_TEAM
                && $request->getRecipient()->isEnabled()
            ) {
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
            $this->remoteUsers = array_filter($this->remoteUsers, function(User $user) { return $user->getId() != $this->getId(); });
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

    /**
     * Hook on pre-persist operations
     */
    public function prePersist()
    {
        $this->createdAt = new \DateTime;
        $this->updatedAt = new \DateTime;
    }

    /**
     * Hook on pre-update operations
     */
    public function preUpdate()
    {
        $this->updatedAt = new \DateTime;
    }

    /**
     * @return EventSport|int
     */
    public function getSportidentSport()
    {
        return $this->sportidentSport;
    }

    /**
     * @param EventSport $sportidentSport
     * @return $this
     */
    public function setSportidentSport($sportidentSport)
    {
        $this->sportidentSport = $sportidentSport;

        return $this;
    }

    /**
     * @return EventSportidentType|int
     */
    public function getSportidentType()
    {
        return $this->sportidentType;
    }

    /**
     * @param EventSportidentType $sportidentType
     * @return $this
     */
    public function setSportidentType($sportidentType)
    {
        $this->sportidentType = $sportidentType;

        return $this;
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

    /**
     * @return EventSport|int
     */
    public function getSportident2Sport()
    {
        return $this->sportident2Sport;
    }

    /**
     * @param EventSport $sportident2Sport
     * @return $this
     */
    public function setSportident2Sport($sportident2Sport)
    {
        $this->sportident2Sport = $sportident2Sport;

        return $this;
    }

    /**
     * @return EventSportidentType|int
     */
    public function getSportident2Type()
    {
        return $this->sportident2Type;
    }

    /**
     * @param EventSportidentType $sportident2Type
     * @return $this
     */
    public function setSportident2Type($sportident2Type)
    {
        $this->sportident2Type = $sportident2Type;

        return $this;
    }

    /**
     * @return string
     */
    public function getSportident2()
    {
        return $this->sportident2;
    }

    /**
     * @param $sportident2
     * @return $this
     */
    public function setSportident2($sportident2)
    {
        $this->sportident2 = $sportident2;

        return $this;
    }

    /**
     * @return EventSport|int
     */
    public function getSportident3Sport()
    {
        return $this->sportident3Sport;
    }

    /**
     * @param EventSport $sportident3Sport
     * @return $this
     */
    public function setSportident3Sport($sportident3Sport)
    {
        $this->sportident3Sport = $sportident3Sport;

        return $this;
    }

    /**
     * @return EventSportidentType|int
     */
    public function getSportident3Type()
    {
        return $this->sportident3Type;
    }

    /**
     * @param EventSportidentType $sportident3Type
     * @return $this
     */
    public function setSportident3Type($sportident3Type)
    {
        $this->sportident3Type = $sportident3Type;

        return $this;
    }

    /**
     * @return string
     */
    public function getSportident3()
    {
        return $this->sportident3;
    }

    /**
     * @param $sportident3
     * @return $this
     */
    public function setSportident3($sportident3)
    {
        $this->sportident3 = $sportident3;

        return $this;
    }

    /**
     * @return array
     */
    public function getSportidents()
    {
        $sportidents = [];
        foreach (self::$sportidentAliases as $alias) {
            if ($this->{$alias}) {
                $sportidents[] = $this->{$alias};
            }
        }
        return $sportidents;
    }

    /**
     * @param string $generalLabel
     * @param string|null $locale
     * @return array
     */
    public function getAllSportidents($generalLabel, $locale = null)
    {
        $sportidents = $_sportidents = [];

        foreach (self::$sportidentAliases as $alias) {
            if ($this->{$alias}) {
                $typeOrisId = $this->{$alias.'Type'} ? $this->{$alias.'Type'}->getOrisId() : 0;

                if (!isset($_sportidents[$typeOrisId])) {
                    $_sportidents[$typeOrisId] = [];
                }

                $_sportidents[$typeOrisId][] = [
                    'key' => $this->{$alias},
                    'label' => sprintf(
                        '%s (%s)',
                        $this->{$alias.'Type'} ? $this->{$alias.'Type'}->getName($locale) : $generalLabel,
                        $this->{$alias}
                    )
                ];
            }
        }

        if ($_sportidents) {
            krsort($_sportidents);

            foreach($_sportidents as $typeSportidents) {
                foreach($typeSportidents as $sportident) {
                    $sportidents[] = $sportident;
                }
            }
        }

        return $sportidents;
    }

    /**
     * @param Event $event
     * @param string $generalLabel
     * @param string|null $locale
     * @return array
     */
    public function getSportidentsByEvent(Event $event, $generalLabel, $locale = null)
    {
        $sportidents = $_sportidents = [];

        foreach (self::$sportidentAliases as $alias) {
            if (
                $this->{$alias}
                &&
                (
                    !$this->{$alias.'Sport'}
                    ||
                    $this->{$alias.'Sport'}->getId() == $event->getEventSport()->getId()
                )
                &&
                (
                    !$this->{$alias.'Type'}
                    ||
                    !$event->getEventSportidentType()
                    ||
                    $this->{$alias.'Type'}->getOrisId() <= $event->getEventSportidentType()->getOrisId()
                )
            ) {
                $typeOrisId = $this->{$alias.'Type'} ? $this->{$alias.'Type'}->getOrisId() : 0;

                if (!isset($_sportidents[$typeOrisId])) {
                    $_sportidents[$typeOrisId] = [];
                }

                $_sportidents[$typeOrisId][] = [
                    'key' => $this->{$alias},
                    'label' => sprintf(
                        '%s (%s)',
                        $this->{$alias.'Type'} ? $this->{$alias.'Type'}->getName($locale) : $generalLabel,
                        $this->{$alias}
                    )
                ];
            }
        }

        if ($_sportidents) {
            krsort($_sportidents);

            foreach($_sportidents as $typeSportidents) {
                foreach($typeSportidents as $sportident) {
                    $sportidents[] = $sportident;
                }
            }
        }

        return $sportidents;
    }

    /**
     * @param $performanceGroups
     */
    public function setPerformanceGroups($performanceGroups)
    {
    }

    /**
     * Get performanceGroups
     *
     * @return PerformanceGroup[]
     */
    public function getPerformanceGroups()
    {
        return $this->performanceGroups->toArray();
    }

    /**
     * @param $trainingGroups
     */
    public function setTrainingGroups($trainingGroups)
    {
    }

    /**
     * Get trainingGroupsSupported
     *
     * @return TrainingGroup[]
     */
    public function getTrainingGroups()
    {
        return array_merge(
            $this->getTrainingGroupsSupported(),
            $this->getTrainingGroupsNotSupported()
        );
    }

    /**
     * Get trainingGroupsSupported
     *
     * @return TrainingGroup[]
     */
    public function getTrainingGroupsSupported()
    {
        return $this->trainingGroupsSupported->toArray();
    }

    /**
     * Get trainingGroupsNotSupported
     *
     * @return TrainingGroup[]
     */
    public function getTrainingGroupsNotSupported()
    {
        return $this->trainingGroupsNotSupported->toArray();
    }

    /**
     * Get contactGroups
     *
     * @return ContactGroup[]
     */
    public function getContactGroups()
    {
        return $this->contactGroups->toArray();
    }

    /**
     * @return UserPrivacyPolicy[]|ArrayCollection
     */
    public function getPrivacyPolicyAgreements()
    {
        return $this->privacyPolicyAgreements;
    }

    /**
     * Set notifyEventEntryDates
     *
     * @param boolean $notifyEventEntryDates
     * @return User
     */
    public function setNotifyEventEntryDates($notifyEventEntryDates)
    {
        $this->notifyEventEntryDates = $notifyEventEntryDates;

        return $this;
    }

    /**
     * Get notifyEventEntryDates
     *
     * @return boolean
     */
    public function getNotifyEventEntryDates()
    {
        return $this->notifyEventEntryDates;
    }

    /**
     * Set notifyClubEventEntryDates
     *
     * @param boolean $notifyClubEventEntryDates
     * @return User
     */
    public function setNotifyClubEventEntryDates($notifyClubEventEntryDates)
    {
        $this->notifyClubEventEntryDates = $notifyClubEventEntryDates;

        return $this;
    }

    /**
     * Get notifyClubEventEntryDates
     *
     * @return boolean
     */
    public function getNotifyClubEventEntryDates()
    {
        return $this->notifyClubEventEntryDates;
    }

    /**
     * @param boolean $notifyFirstEventEntryDateOnly
     * @return User
     */
    public function setNotifyFirstEventEntryDateOnly($notifyFirstEventEntryDateOnly)
    {
        $this->notifyFirstEventEntryDateOnly = $notifyFirstEventEntryDateOnly;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getNotifyFirstEventEntryDateOnly()
    {
        return $this->notifyFirstEventEntryDateOnly;
    }

    /**
     * Set notifyDocuments
     *
     * @param boolean $notifyDocuments
     * @return User
     */
    public function setNotifyDocuments($notifyDocuments)
    {
        $this->notifyDocuments = $notifyDocuments;

        return $this;
    }

    /**
     * Get notifyDocuments
     *
     * @return boolean
     */
    public function getNotifyDocuments()
    {
        return $this->notifyDocuments;
    }

    /**
     * Set amountOfDaysBeforeEventEntryDateToNotify
     *
     * @param integer $amountOfDaysBeforeEventEntryDateToNotify
     * @return User
     */
    public function setAmountOfDaysBeforeEventEntryDateToNotify($amountOfDaysBeforeEventEntryDateToNotify)
    {
        $this->amountOfDaysBeforeEventEntryDateToNotify = $amountOfDaysBeforeEventEntryDateToNotify;

        return $this;
    }

    /**
     * Get amountOfDaysBeforeEventEntryDateToNotify
     *
     * @return integer
     */
    public function getAmountOfDaysBeforeEventEntryDateToNotify()
    {
        return $this->amountOfDaysBeforeEventEntryDateToNotify;
    }

    /**
     * Add notifyEventSport
     *
     * @param EventSport $notifyEventSport
     * @return User
     */
    public function addNotifyEventSports(EventSport $notifyEventSport)
    {
        $this->notifyEventSports[] = $notifyEventSport;

        return $this;
    }

    /**
     * Remove notifyEventSport
     *
     * @param EventSport $notifyEventSport
     */
    public function removeNotifyEventSports(EventSport $notifyEventSport)
    {
        $this->notifyEventSports->removeElement($notifyEventSport);
    }

    /**
     * Get notifyEventSports
     *
     * @return EventSport[]|ArrayCollection
     */
    public function getNotifyEventSports()
    {
        return $this->notifyEventSports;
    }

    /**
     * Add notifyEventLevel
     *
     * @param EventLevel $notifyEventLevel
     * @return User
     */
    public function addNotifyEventLevels(EventLevel $notifyEventLevel)
    {
        $this->notifyEventLevels[] = $notifyEventLevel;

        return $this;
    }

    /**
     * Remove notifyEventLevel
     *
     * @param EventLevel $notifyEventLevel
     */
    public function removeNotifyEventLevels(EventLevel $notifyEventLevel)
    {
        $this->notifyEventLevels->removeElement($notifyEventLevel);
    }

    /**
     * Get notifyEventLevels
     *
     * @return EventLevel[]|ArrayCollection
     */
    public function getNotifyEventLevels()
    {
        return $this->notifyEventLevels;
    }

    /**
     * Add notifyEventDiscipline
     *
     * @param EventDiscipline $notifyEventDiscipline
     * @return User
     */
    public function addNotifyEventDisciplines(EventDiscipline $notifyEventDiscipline)
    {
        $this->notifyEventDisciplines[] = $notifyEventDiscipline;

        return $this;
    }

    /**
     * Remove notifyEventDiscipline
     *
     * @param EventDiscipline $notifyEventDiscipline
     */
    public function removeNotifyEventDisciplines(EventDiscipline $notifyEventDiscipline)
    {
        $this->notifyEventDisciplines->removeElement($notifyEventDiscipline);
    }

    /**
     * Get notifyEventDisciplines
     *
     * @return EventDiscipline[]|ArrayCollection
     */
    public function getNotifyEventDisciplines()
    {
        return $this->notifyEventDisciplines;
    }

    /**
     * Add notifyEventCup
     *
     * @param EventCup $notifyEventCup
     * @return User
     */
    public function addNotifyEventCups(EventCup $notifyEventCup)
    {
        $this->notifyEventCups[] = $notifyEventCup;

        return $this;
    }

    /**
     * Remove notifyEventCup
     *
     * @param EventCup $notifyEventCup
     */
    public function removeNotifyEventCups(EventCup $notifyEventCup)
    {
        $this->notifyEventCups->removeElement($notifyEventCup);
    }

    /**
     * Get notifyEventCups
     *
     * @return EventCup[]|ArrayCollection
     */
    public function getNotifyEventCups()
    {
        return $this->notifyEventCups;
    }

    /**
     * @return ArrayCollection|WalletFinancialStatementBalance[]
     */
    public function getFinancialStatementBalances()
    {
        return $this->financialStatementBalances;
    }

    /**
     * Add notifyDocumentCategory
     *
     * @param Category $notifyDocumentCategory
     * @return User
     */
    public function addNotifyDocumentCategories(Category $notifyDocumentCategory)
    {
        $this->notifyDocumentCategories[] = $notifyDocumentCategory;

        return $this;
    }

    /**
     * Remove notifyDocumentCategory
     *
     * @param Category $notifyDocumentCategory
     */
    public function removeNotifyDocumentCategories(Category $notifyDocumentCategory)
    {
        $this->notifyDocumentCategories->removeElement($notifyDocumentCategory);
    }

    /**
     * Get notifyDocumentCategories
     *
     * @return Category[]|ArrayCollection
     */
    public function getNotifyDocumentCategories()
    {
        return $this->notifyDocumentCategories;
    }

    /**
     * @param Category $category
     * @return bool
     */
    public function hasDocumentCategoryNotificationEnabled(Category $category)
    {
        foreach($this->getNotifyDocumentCategories() as $_category) {
            if ($category == $_category) {
                return true;
            }
        }
        return false;
    }

    /**
     * Add sport
     *
     * @param EventSport $eventSport
     * @param string|null $licence
     * @return User
     */
    public function addSportLicence(EventSport $eventSport, $licence)
    {
        $this->sportLicences[] = new UserSportLicence(
            $this,
            $eventSport,
            $licence ?? null
        );

        return $this;
    }

    /**
     * @param EventSport $eventSport
     * @param string|null $licence
     * @return bool
     */
    public function isSportLicenceUpdatable(EventSport $eventSport, $newLicence)
    {
        $sportLicence = $this->getSportLicence($eventSport);
        if (!$sportLicence) {
            return false;
        }

        return $sportLicence->getLicence() != $newLicence;
    }

    /**
     * @param EventSport $eventSport
     * @param string|null $licence
     */
    public function updateSportLicence(EventSport $eventSport, $licence)
    {
        $sportLicence = $this->getSportLicence($eventSport);
        if (!$sportLicence) {
            return;
        }

        $sportLicence->setLicence($licence);
    }

    /**
     * Remove sport
     *
     * @param EventSport $eventSport
     */
    public function removeSportLicence(EventSport $eventSport)
    {
        $sportLicence = $this->getSportLicence($eventSport);
        if (!$sportLicence) {
            return;
        }

        $this->sportLicences->removeElement($sportLicence);
    }

    /**
     * @param EventSport $eventSport
     * @return bool
     */
    public function hasSportLicence(EventSport $eventSport)
    {
        return $this->getSportLicence($eventSport) !== null;
    }

    /**
     * Get sports
     *
     * @return UserSportLicence[]
     */
    public function getSportLicences()
    {
        return $this->sportLicences->toArray();
    }

    /**
     * Get sports sorted
     *
     * @return UserSportLicence[]
     */
    public function getSportLicencesSorted()
    {
        $sportLicences = $this->getSportLicences();

        usort($sportLicences, function (UserSportLicence $sportLicence1, UserSportLicence $sportLicence2) {
            return $sportLicence1->getEventSport()->getOrisId() - $sportLicence2->getEventSport()->getOrisId();
        });

        return $sportLicences;
    }

    public function setSportLicencesSorted($data)
    {
        // do nothing
    }

    /**
     * Get sport names
     *
     * @return string
     */
    public function getSportLicencesDecorated()
    {
        return implode(', ', array_map(function(UserSportLicence $sportLicence) {
            return (string) $sportLicence;
        }, $this->getSportLicencesSorted()));
    }

    /**
     * @param EventSport $eventSport
     * @return UserSportLicence|null
     */
    public function getSportLicence(EventSport $eventSport)
    {
        /** @var UserSportLicence $sportLicence */
        foreach($this->getSportLicences() as $sportLicence) {
            if ($sportLicence->getEventSport() == $eventSport) {
                return $sportLicence;
            }
        }
        return null;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }
}
