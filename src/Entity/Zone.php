<?php

namespace App\Entity;

use DateTime;
use DateTimeZone;
use DateTimeImmutable;
use Cocur\Slugify\Slugify;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\ZoneRepository;
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
 * @ORM\Entity(repositoryClass=ZoneRepository::class)
 * @ORM\HasLifecycleCallbacks()
 * @UniqueEntity(
 *  fields={"name","site"},
 *  message="Désolé une zone est déjà enregistrée dans ce site avec ce nom, veuillez le modifier s'il vous plaît !!!"
 * )
 */
class Zone
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
     * @Assert\Length(min=3, minMessage="Le nom de la zone doit contenir au moins 3 caractères !", maxMessage="Le nom de la zone doit contenir au max 10 caractères !")
     */
    private $name;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $psous;

    /**
     * @ORM\ManyToOne(targetEntity=Site::class, inversedBy="zones")
     */
    private $site;

    /**
     * @ORM\ManyToMany(targetEntity=User::class, inversedBy="zones")
     */
    private $users;

    /**
     * @ORM\OneToMany(targetEntity=CleverBox::class, mappedBy="zone")
     */
    private $cleverBox;

    /**
     * @ORM\ManyToMany(targetEntity=SmartMod::class, mappedBy="zones")
     */
    private $smartMods;

    /**
     * @ORM\Column(type="datetime_immutable")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $editedAt;

    /**
     * @ORM\Column(type="string", length=150)
     */
    private $slug;

    public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->cleverBox = new ArrayCollection();
        $this->smartMods = new ArrayCollection();
    }

    /**
     * Permet d'initialiser la date de création de la zone
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
     * Permet de mettre à jour la date de modification de la zone
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

    public function getPsous(): ?float
    {
        return $this->psous;
    }

    public function setPsous(?float $psous): self
    {
        $this->psous = $psous;

        return $this;
    }

    public function getSite(): ?Site
    {
        return $this->site;
    }

    public function setSite(?Site $site): self
    {
        $this->site = $site;

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
    public function getCleverBox(): Collection
    {
        return $this->cleverBox;
    }

    public function addCleverBox(CleverBox $cleverBox): self
    {
        if (!$this->cleverBox->contains($cleverBox)) {
            $this->cleverBox[] = $cleverBox;
            $cleverBox->setZone($this);
        }

        return $this;
    }

    public function removeCleverBox(CleverBox $cleverBox): self
    {
        if ($this->cleverBox->removeElement($cleverBox)) {
            // set the owning side to null (unless already changed)
            if ($cleverBox->getZone() === $this) {
                $cleverBox->setZone(null);
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
            $smartMod->addZone($this);
        }

        return $this;
    }

    public function removeSmartMod(SmartMod $smartMod): self
    {
        if ($this->smartMods->removeElement($smartMod)) {
            $smartMod->removeZone($this);
        }

        return $this;
    }

    /**
     * Get the value of createdAt
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set the value of createdAt
     *
     * @return  self
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get the value of editedAt
     */
    public function getEditedAt()
    {
        return $this->editedAt;
    }

    /**
     * Set the value of editedAt
     *
     * @return  self
     */
    public function setEditedAt($editedAt)
    {
        $this->editedAt = $editedAt;

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
}
