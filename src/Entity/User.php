<?php

namespace App\Entity;

use DateTime;
use DateTimeZone;
use App\Entity\Site;
use App\Entity\Zone;
use DateTimeImmutable;
use App\Entity\Enterprise;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\UserRepository;
use Doctrine\ORM\Mapping\PreUpdate;
use Doctrine\ORM\Mapping\PrePersist;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

/**
 * @ORM\Entity(repositoryClass=UserRepository::class)
 * @ORM\HasLifecycleCallbacks()
 * @UniqueEntity(
 *  fields={"email"},
 *  message="Désolé un autre utilisateur est déjà enregistré avec cet adresse email, veuillez le modifier"
 * )
 */
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=180, unique=true)
     * @Assert\NotBlank(message="Adresse Email obligatoire")
     * @Assert\Email(message="Veuillez renseigner une adresse email valide !")
     */
    private $email;

    /**
     * @ORM\Column(type="json")
     */
    private $roles = [];

    /**
     * @var string The hashed password
     * @ORM\Column(type="string")
     * @Assert\NotBlank(message="Mot de passe obligatoire")
     * @Assert\Length(min=8, minMessage="Votre mot de passe doit contenir au moins 8 caractères !")
     */
    private $password;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    private $avatar;

    /**
     * @ORM\Column(type="datetime_immutable")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $editedAt;

    /**
     * @ORM\Column(type="string", length=20)
     * @Assert\NotBlank(message="Prénom obligatoire")
     * @Assert\Length(min=3, minMessage="Votre prénom doit contenir au moins 3 caractères !", maxMessage="Votre nom doit contenir au max 80 caractères !")
     */
    private $firstName;

    /**
     * @ORM\Column(type="string", length=50)
     * @Assert\NotBlank(message="Nom obligatoire")
     * @Assert\Length(min=3, minMessage="Votre nom doit contenir au moins 3 caractères !", maxMessage="Votre nom doit contenir au max 80 caractères !")
     */
    private $lastName;

    /**
     * @ORM\Column(type="string", length=20)
     * @Assert\NotBlank(message="Numéro de téléphone obligatoire")
     * @Assert\Length(min=9, minMessage="Votre numéro de téléphone doit contenir au moins 9 caractères !", maxMessage="Votre numéro de téléphone doit contenir au max 20 caractères !")
     */
    private $phoneNumber;

    /**
     * @ORM\Column(type="string", length=10)
     */
    private $countryCode;

    /**
     * @ORM\Column(type="string", length=10, nullable=true)
     */
    private $verificationCode;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isVerified;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isActive;

    /**
     * @ORM\ManyToOne(targetEntity=Enterprise::class, inversedBy="users")
     */
    private $enterprise;

    /**
     * @ORM\ManyToMany(targetEntity=Site::class, mappedBy="users")
     */
    private $sites;

    /**
     * @ORM\ManyToMany(targetEntity=Zone::class, mappedBy="users")
     */
    private $zones;

    /**
     * @ORM\OneToMany(targetEntity=Contacts::class, mappedBy="user")
     */
    private $contacts;

    public function __construct()
    {
        $this->sites = new ArrayCollection();
        $this->zones = new ArrayCollection();
        $this->contacts = new ArrayCollection();
    }

    /**
     * Permet d'initialiser la date de création de l'utilisateur
     *
     * @ORM\PrePersist
     * 
     * @return void
     */
    public function initializeCreatedAt()
    {
        $this->createdAt = new DateTimeImmutable(date('Y-m-d H:i:s'), new DateTimeZone('Africa/Douala'));
    }

    /**
     * Permet de mettre à jour la date de modification de l'utilisateur
     *
     * @ORM\PreUpdate
     * 
     * @return void
     */
    public function updateEditedAt()
    {
        $this->editedAt = new DateTime(date('Y-m-d H:i:s'), new DateTimeZone('Africa/Douala'));
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @deprecated since Symfony 5.3, use getUserIdentifier instead
     */
    public function getUsername(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Returning a salt is only needed, if you are not using a modern
     * hashing algorithm (e.g. bcrypt or sodium) in your security.yaml.
     *
     * @see UserInterface
     */
    public function getSalt(): ?string
    {
        return null;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(?string $avatar): self
    {
        $this->avatar = $avatar;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getEditedAt(): ?\DateTimeInterface
    {
        return $this->editedAt;
    }

    public function setEditedAt(?\DateTimeInterface $editedAt): self
    {
        $this->editedAt = $editedAt;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(string $phoneNumber): self
    {
        $this->phoneNumber = $phoneNumber;

        return $this;
    }

    public function getCountryCode(): ?string
    {
        return $this->countryCode;
    }

    public function setCountryCode(string $countryCode): self
    {
        $this->countryCode = $countryCode;

        return $this;
    }

    public function getVerificationCode(): ?string
    {
        return $this->verificationCode;
    }

    public function setVerificationCode(?string $verificationCode): self
    {
        $this->verificationCode = $verificationCode;

        return $this;
    }

    public function getIsVerified(): ?bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): self
    {
        $this->isVerified = $isVerified;

        return $this;
    }

    public function getIsActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getEnterprise(): ?Enterprise
    {
        return $this->enterprise;
    }

    public function setEnterprise(?Enterprise $enterprise): self
    {
        $this->enterprise = $enterprise;

        return $this;
    }

    /**
     * @return Collection|Site[]
     */
    public function getSites(): Collection
    {
        return $this->sites;
    }

    public function addSite(Site $site): self
    {
        if (!$this->sites->contains($site)) {
            $this->sites[] = $site;
            $site->addUser($this);
        }

        return $this;
    }

    public function removeSite(Site $site): self
    {
        if ($this->sites->removeElement($site)) {
            $site->removeUser($this);
        }

        return $this;
    }

    /**
     * @return Collection|Zone[]
     */
    public function getZones(): Collection
    {
        return $this->zones;
    }

    public function addZone(Zone $zone): self
    {
        if (!$this->zones->contains($zone)) {
            $this->zones[] = $zone;
            $zone->addUser($this);
        }

        return $this;
    }

    public function removeZone(Zone $zone): self
    {
        if ($this->zones->removeElement($zone)) {
            $zone->removeUser($this);
        }

        return $this;
    }

    /**
     * Permet de récupérer le nom complet de l'utilisateur
     *
     * @return string|null
     */
    public function getFullName(): ?string
    {
        return "{$this->firstName} {$this->lastName}";
    }

    public function getFullPhone(): ?string
    {
        return "{$this->countryCode}{$this->phoneNumber}";
    }

    /**
     * @return Collection|Contacts[]
     */
    public function getContacts(): Collection
    {
        return $this->contacts;
    }

    public function addContact(Contacts $contact): self
    {
        if (!$this->contacts->contains($contact)) {
            $this->contacts[] = $contact;
            $contact->setUser($this);
        }

        return $this;
    }

    public function removeContact(Contacts $contact): self
    {
        if ($this->contacts->removeElement($contact)) {
            // set the owning side to null (unless already changed)
            if ($contact->getUser() === $this) {
                $contact->setUser(null);
            }
        }

        return $this;
    }
}
