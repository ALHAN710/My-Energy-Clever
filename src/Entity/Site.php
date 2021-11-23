<?php

namespace App\Entity;

use DateTime;
use DateTimeZone;
use DateTimeImmutable;
use Cocur\Slugify\Slugify;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\SiteRepository;
use Doctrine\ORM\Mapping\PreUpdate;
use Doctrine\ORM\Mapping\PrePersist;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass=SiteRepository::class)
 * @ORM\HasLifecycleCallbacks()
 * @UniqueEntity(
 *  fields={"name","enterprise"},
 *  message="Désolé un site est déjà enregistrée dans cette entreprise avec ce nom, veuillez le modifier s'il vous plaît !!!"
 * )
 */
class Site
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=100)
     * @Assert\NotBlank(message="Nom obligatoire")
     * @Assert\Length(min=3, minMessage="Le nom du Site doit contenir au moins 3 caractères !", maxMessage="Le nom du Site doit contenir au max 20 caractères !")
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=150)
     */
    private $slug;

    /**
     * @ORM\Column(type="datetime_immutable")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $editedAt;

    /**
     * @ORM\Column(type="float")
     */
    private $powerSubscribed; // en kW

    /**
     * @ORM\Column(type="string", length=10)
     */
    private $currency;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $latitude;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $longitude;

    /**
     * @ORM\Column(type="string", length=10)
     */
    private $subscription; //[ MT, BT]

    /**
     * @ORM\Column(type="string", length=10)
     */
    private $subscriptionType; //[ MONOPHASE, TRIPHASE ]

    /**
     * @ORM\ManyToOne(targetEntity=Enterprise::class, inversedBy="sites")
     * @ORM\JoinColumn(nullable=false)
     */
    private $enterprise;

    /**
     * @ORM\ManyToMany(targetEntity=User::class, inversedBy="sites")
     */
    private $users;

    /**
     * @ORM\OneToMany(targetEntity=CleverBox::class, mappedBy="site")
     */
    private $cleverBoxes;

    /**
     * @ORM\OneToMany(targetEntity=Zone::class, mappedBy="site")
     */
    private $zones;

    /**
     * @ORM\OneToMany(targetEntity=SmartMod::class, mappedBy="site")
     */
    private $smartMods;

    /**
     * @ORM\Column(type="string", length=30)
     */
    private $activityArea;

    /**
     * @ORM\OneToMany(targetEntity=Budget::class, mappedBy="site", orphanRemoval=true)
     */
    private $budgets;

    /**
     * @ORM\Column(type="string", length=100)
     */
    private $subscriptionUsage;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $hasOneSmartMod; // [Residentiel, Non Residentiel ]

    public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->cleverBoxes = new ArrayCollection();
        $this->zones = new ArrayCollection();
        $this->smartMods = new ArrayCollection();
        $this->budgets = new ArrayCollection();
    }

    /**
     * Permet d'initialiser la date de création du Site
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
     * Permet de mettre à jour la date de modification du Site
     *
     * @ORM\PreUpdate
     * 
     * @return void
     */
    public function updateEditedAt()
    {
        $this->editedAt = new DateTime(date('Y-m-d H:i:s'), new DateTimeZone('Africa/Douala'));
    }

    /**
     * Permet d'initialiser ou mettre à jour le slug !
     *
     * @ORM\PrePersist
     * @ORM\PreUpdate
     * 
     * @return void
     */
    public function initializeSlug()
    {
        $slugify = new Slugify();
        $this->slug = $slugify->slugify($this->name . ' ' . $this->id);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

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

    public function getPowerSubscribed(): ?float
    {
        return $this->powerSubscribed;
    }

    public function setPowerSubscribed(float $powerSubscribed): self
    {
        $this->powerSubscribed = $powerSubscribed;

        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): self
    {
        $this->currency = $currency;

        return $this;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function setLatitude(?float $latitude): self
    {
        $this->latitude = $latitude;

        return $this;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function setLongitude(?float $longitude): self
    {
        $this->longitude = $longitude;

        return $this;
    }

    public function getSubscription(): ?string
    {
        return $this->subscription;
    }

    public function setSubscription(string $subscription): self
    {
        $this->subscription = $subscription;

        return $this;
    }

    public function getSubscriptionType(): ?string
    {
        return $this->subscriptionType;
    }

    public function setSubscriptionType(string $subscriptionType): self
    {
        $this->subscriptionType = $subscriptionType;

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
     * @return Collection|User[]
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): self
    {
        if (!$this->users->contains($user)) {
            $this->users[] = $user;
        }

        return $this;
    }

    public function removeUser(User $user): self
    {
        $this->users->removeElement($user);

        return $this;
    }

    /**
     * @return Collection|CleverBox[]
     */
    public function getCleverBoxes(): Collection
    {
        return $this->cleverBoxes;
    }

    public function addCleverBox(CleverBox $cleverBox): self
    {
        if (!$this->cleverBoxes->contains($cleverBox)) {
            $this->cleverBoxes[] = $cleverBox;
            $cleverBox->setSite($this);
        }

        return $this;
    }

    public function removeCleverBox(CleverBox $cleverBox): self
    {
        if ($this->cleverBoxes->removeElement($cleverBox)) {
            // set the owning side to null (unless already changed)
            if ($cleverBox->getSite() === $this) {
                $cleverBox->setSite(null);
            }
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
            $zone->setSite($this);
        }

        return $this;
    }

    public function removeZone(Zone $zone): self
    {
        if ($this->zones->removeElement($zone)) {
            // set the owning side to null (unless already changed)
            if ($zone->getSite() === $this) {
                $zone->setSite(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|SmartMod[]
     */
    public function getSmartMods(): Collection
    {
        return $this->smartMods;
    }

    public function addSmartMod(SmartMod $smartMod): self
    {
        if (!$this->smartMods->contains($smartMod)) {
            $this->smartMods[] = $smartMod;
            $smartMod->setSite($this);
        }

        return $this;
    }

    public function removeSmartMod(SmartMod $smartMod): self
    {
        if ($this->smartMods->removeElement($smartMod)) {
            // set the owning side to null (unless already changed)
            if ($smartMod->getSite() === $this) {
                $smartMod->setSite(null);
            }
        }

        return $this;
    }

    public function getActivityArea(): ?string
    {
        return $this->activityArea;
    }

    public function setActivityArea(string $activityArea): self
    {
        $this->activityArea = $activityArea;

        return $this;
    }

    /**
     * @return Collection|Budget[]
     */
    public function getBudgets(): Collection
    {
        return $this->budgets;
    }

    public function addBudget(Budget $budget): self
    {
        if (!$this->budgets->contains($budget)) {
            $this->budgets[] = $budget;
            $budget->setSite($this);
        }

        return $this;
    }

    public function removeBudget(Budget $budget): self
    {
        if ($this->budgets->removeElement($budget)) {
            // set the owning side to null (unless already changed)
            if ($budget->getSite() === $this) {
                $budget->setSite(null);
            }
        }

        return $this;
    }

    public function getSubscriptionUsage(): ?string
    {
        return $this->subscriptionUsage;
    }

    public function setSubscriptionUsage(string $subscriptionUsage): self
    {
        $this->subscriptionUsage = $subscriptionUsage;

        return $this;
    }

    public function getHasOneSmartMod(): ?bool
    {
        return $this->hasOneSmartMod;
    }

    public function setHasOneSmartMod(?bool $hasOneSmartMod): self
    {
        $this->hasOneSmartMod = $hasOneSmartMod;

        return $this;
    }
}
