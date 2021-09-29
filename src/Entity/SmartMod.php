<?php

namespace App\Entity;

use DateTime;
use DateTimeZone;
use DateTimeImmutable;
use Cocur\Slugify\Slugify;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\PreUpdate;
use Doctrine\ORM\Mapping\PrePersist;
use App\Repository\SmartModRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass=SmartModRepository::class)
 * @ORM\HasLifecycleCallbacks()
 * @UniqueEntity(
 *  fields={"moduleId"},
 *  message="Désolé un << Smart Module >> est déjà enregistrée avec cet identifiant, veuillez le modifier s'il vous plaît !!!"
 * )
 */
class SmartMod
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
     * @Assert\Length(min=3, minMessage="Le nom du << Smart Module >> doit contenir au moins 3 caractères !", maxMessage="Le nom du << Smart Module >> doit contenir au max 20 caractères !")
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=20)
     * @Assert\NotBlank(message="Identifiant du << Smart Module >> obligatoire")
     * @Assert\Length(min=8, minMessage="L'Identifiant du << Smart Module >> doit contenir au moins 3 caractères !", maxMessage="L'Identifiant du << Smart Module >> doit contenir au max 20 caractères !")
     */
    private $moduleId;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private $modType;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $fuelPrice;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $levelZone;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $nbPhases;

    /**
     * @ORM\Column(type="string", length=20, nullable=true)
     */
    private $subType;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $power;

    /**
     * @ORM\ManyToOne(targetEntity=Site::class, inversedBy="smartMods")
     */
    private $site;

    /**
     * @ORM\ManyToMany(targetEntity=Zone::class, inversedBy="smartMods")
     */
    private $zones;

    /**
     * @ORM\OneToMany(targetEntity=LoadEnergyData::class, mappedBy="smartMod")
     */
    private $loadEnergyData;

    /**
     * @ORM\OneToOne(targetEntity=GensetRealTimeData::class, mappedBy="smartMod", cascade={"persist", "remove"})
     */
    private $gensetRealTimeData;

    /**
     * @ORM\OneToMany(targetEntity=GensetData::class, mappedBy="smartMod")
     */
    private $gensetData;

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
        $this->zones = new ArrayCollection();
        $this->loadEnergyData = new ArrayCollection();
        $this->gensetData = new ArrayCollection();
    }

    /**
     * Permet d'initialiser la date de création du << Smart Module >>
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
     * Permet de mettre à jour la date de modification du << Smart Module >>
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
        $this->slug = $slugify->slugify($this->name);
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

    public function getModuleId(): ?string
    {
        return $this->moduleId;
    }

    public function setModuleId(string $moduleId): self
    {
        $this->moduleId = $moduleId;

        return $this;
    }

    public function getModType(): ?string
    {
        return $this->modType;
    }

    public function setModType(string $modType): self
    {
        $this->modType = $modType;

        return $this;
    }

    public function getFuelPrice(): ?float
    {
        return $this->fuelPrice;
    }

    public function setFuelPrice(?float $fuelPrice): self
    {
        $this->fuelPrice = $fuelPrice;

        return $this;
    }

    public function getLevelZone(): ?int
    {
        return $this->levelZone;
    }

    public function setLevelZone(?int $levelZone): self
    {
        $this->levelZone = $levelZone;

        return $this;
    }

    public function getNbPhases(): ?int
    {
        return $this->nbPhases;
    }

    public function setNbPhases(?int $nbPhases): self
    {
        $this->nbPhases = $nbPhases;

        return $this;
    }

    public function getSubType(): ?string
    {
        return $this->subType;
    }

    public function setSubType(?string $subType): self
    {
        $this->subType = $subType;

        return $this;
    }

    public function getPower(): ?float
    {
        return $this->power;
    }

    public function setPower(?float $power): self
    {
        $this->power = $power;

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
        }

        return $this;
    }

    public function removeZone(Zone $zone): self
    {
        $this->zones->removeElement($zone);

        return $this;
    }

    /**
     * @return Collection|LoadEnergyData[]
     */
    public function getLoadEnergyData(): Collection
    {
        return $this->loadEnergyData;
    }

    public function addLoadEnergyData(LoadEnergyData $loadEnergyData): self
    {
        if (!$this->loadEnergyData->contains($loadEnergyData)) {
            $this->loadEnergyData[] = $loadEnergyData;
            $loadEnergyData->setSmartMod($this);
        }

        return $this;
    }

    public function removeLoadEnergyData(LoadEnergyData $loadEnergyData): self
    {
        if ($this->loadEnergyData->removeElement($loadEnergyData)) {
            // set the owning side to null (unless already changed)
            if ($loadEnergyData->getSmartMod() === $this) {
                $loadEnergyData->setSmartMod(null);
            }
        }

        return $this;
    }

    public function getGensetRealTimeData(): ?GensetRealTimeData
    {
        return $this->gensetRealTimeData;
    }

    public function setGensetRealTimeData(?GensetRealTimeData $gensetRealTimeData): self
    {
        // unset the owning side of the relation if necessary
        if ($gensetRealTimeData === null && $this->gensetRealTimeData !== null) {
            $this->gensetRealTimeData->setSmartMod(null);
        }

        // set the owning side of the relation if necessary
        if ($gensetRealTimeData !== null && $gensetRealTimeData->getSmartMod() !== $this) {
            $gensetRealTimeData->setSmartMod($this);
        }

        $this->gensetRealTimeData = $gensetRealTimeData;

        return $this;
    }

    /**
     * @return Collection|GensetData[]
     */
    public function getGensetData(): Collection
    {
        return $this->gensetData;
    }

    public function addGensetData(GensetData $gensetData): self
    {
        if (!$this->gensetData->contains($gensetData)) {
            $this->gensetData[] = $gensetData;
            $gensetData->setSmartMod($this);
        }

        return $this;
    }

    public function removeGensetData(GensetData $gensetData): self
    {
        if ($this->gensetData->removeElement($gensetData)) {
            // set the owning side to null (unless already changed)
            if ($gensetData->getSmartMod() === $this) {
                $gensetData->setSmartMod(null);
            }
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
