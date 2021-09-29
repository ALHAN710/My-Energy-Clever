<?php

namespace App\Entity;

use DateTime;
use DateTimeZone;
use DateTimeImmutable;
use Cocur\Slugify\Slugify;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\PreUpdate;
use Doctrine\ORM\Mapping\PrePersist;
use App\Repository\CleverBoxRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass=CleverBoxRepository::class)
 * @ORM\HasLifecycleCallbacks()
 * @UniqueEntity(
 *  fields={"name","site"},
 *  message="Désolé une CleverBox est déjà enregistrée dans ce site( ou cette zone) avec ce nom, veuillez le modifier s'il vous plaît !!!"
 * )
 */
class CleverBox
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
     * @Assert\Length(min=3, minMessage="Le nom de la CleverBox doit contenir au moins 3 caractères !", maxMessage="Le nom de la CleverBox doit contenir au max 20 caractères !")
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=20)
     * @Assert\NotBlank(message="Identifiant de la CleverBox obligatoire")
     * @Assert\Length(min=8, minMessage="L'Identifiant de la CleverBox doit contenir au moins 3 caractères !", maxMessage="L'Identifiant de la CleverBox doit contenir au max 20 caractères !")
     */
    private $boxId;

    /**
     * @ORM\Column(type="string", length=150)
     */
    private $slug;

    /**
     * @ORM\ManyToOne(targetEntity=Site::class, inversedBy="cleverBoxes")
     * 
     */
    private $site; //ORM\JoinColumn(nullable=false)

    /**
     * @ORM\ManyToOne(targetEntity=Zone::class, inversedBy="cleverBox")
     */
    private $zone;

    /**
     * @ORM\OneToMany(targetEntity=SmartDevice::class, mappedBy="cleverBox")
     */
    private $smartDevices;

    /**
     * @ORM\Column(type="datetime_immutable")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $editedAt;

    public function __construct()
    {
        $this->smartDevices = new ArrayCollection();
    }

    /**
     * Permet d'initialiser la date de création de la CleverBox
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
     * Permet de mettre à jour la date de modification de la CleverBox
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

    public function getBoxId(): ?string
    {
        return $this->boxId;
    }

    public function setBoxId(string $boxId): self
    {
        $this->boxId = $boxId;

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

    public function getSite(): ?Site
    {
        return $this->site;
    }

    public function setSite(?Site $site): self
    {
        $this->site = $site;

        return $this;
    }

    public function getZone(): ?Zone
    {
        return $this->zone;
    }

    public function setZone(?Zone $zone): self
    {
        $this->zone = $zone;

        return $this;
    }

    /**
     * @return Collection|SmartDevice[]
     */
    public function getSmartDevices(): Collection
    {
        return $this->smartDevices;
    }

    public function addSmartDevice(SmartDevice $smartDevice): self
    {
        if (!$this->smartDevices->contains($smartDevice)) {
            $this->smartDevices[] = $smartDevice;
            $smartDevice->setCleverBox($this);
        }

        return $this;
    }

    public function removeSmartDevice(SmartDevice $smartDevice): self
    {
        if ($this->smartDevices->removeElement($smartDevice)) {
            // set the owning side to null (unless already changed)
            if ($smartDevice->getCleverBox() === $this) {
                $smartDevice->setCleverBox(null);
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
}
