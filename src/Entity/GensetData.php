<?php

namespace App\Entity;

use App\Repository\GensetDataRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=GensetDataRepository::class)
 */
class GensetData
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=SmartMod::class, inversedBy="gensetData")
     */
    private $smartMod;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $p;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $pamoy;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $pbmoy;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $pcmoy;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $samoy;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $sbmoy;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $scmoy;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $smoy;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $cosfia;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $cosfib;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $cosfic;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $cosfi;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $totalRunningHours;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $totalEnergy;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $nbMainsInterruption;

    /**
     * @ORM\Column(type="datetime")
     */
    private $dateTime;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $fuelLevel;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSmartMod(): ?SmartMod
    {
        return $this->smartMod;
    }

    public function setSmartMod(?SmartMod $smartMod): self
    {
        $this->smartMod = $smartMod;

        return $this;
    }

    public function getP(): ?float
    {
        return $this->p;
    }

    public function setP(?float $p): self
    {
        $this->p = $p;

        return $this;
    }

    public function getPamoy(): ?float
    {
        return $this->pamoy;
    }

    public function setPamoy(?float $pamoy): self
    {
        $this->pamoy = $pamoy;

        return $this;
    }

    public function getPbmoy(): ?float
    {
        return $this->pbmoy;
    }

    public function setPbmoy(?float $pbmoy): self
    {
        $this->pbmoy = $pbmoy;

        return $this;
    }

    public function getPcmoy(): ?float
    {
        return $this->pcmoy;
    }

    public function setPcmoy(?float $pcmoy): self
    {
        $this->pcmoy = $pcmoy;

        return $this;
    }

    public function getSamoy(): ?float
    {
        return $this->samoy;
    }

    public function setSamoy(?float $samoy): self
    {
        $this->samoy = $samoy;

        return $this;
    }

    public function getSbmoy(): ?float
    {
        return $this->sbmoy;
    }

    public function setSbmoy(?float $sbmoy): self
    {
        $this->sbmoy = $sbmoy;

        return $this;
    }

    public function getScmoy(): ?float
    {
        return $this->scmoy;
    }

    public function setScmoy(?float $scmoy): self
    {
        $this->scmoy = $scmoy;

        return $this;
    }

    public function getSmoy(): ?float
    {
        return $this->smoy;
    }

    public function setSmoy(?float $smoy): self
    {
        $this->smoy = $smoy;

        return $this;
    }

    public function getCosfia(): ?float
    {
        return $this->cosfia;
    }

    public function setCosfia(?float $cosfia): self
    {
        $this->cosfia = $cosfia;

        return $this;
    }

    public function getCosfib(): ?float
    {
        return $this->cosfib;
    }

    public function setCosfib(?float $cosfib): self
    {
        $this->cosfib = $cosfib;

        return $this;
    }

    public function getCosfic(): ?float
    {
        return $this->cosfic;
    }

    public function setCosfic(?float $cosfic): self
    {
        $this->cosfic = $cosfic;

        return $this;
    }

    public function getCosfi(): ?float
    {
        return $this->cosfi;
    }

    public function setCosfi(?float $cosfi): self
    {
        $this->cosfi = $cosfi;

        return $this;
    }

    public function getTotalRunningHours(): ?int
    {
        return $this->totalRunningHours;
    }

    public function setTotalRunningHours(?int $totalRunningHours): self
    {
        $this->totalRunningHours = $totalRunningHours;

        return $this;
    }

    public function getTotalEnergy(): ?int
    {
        return $this->totalEnergy;
    }

    public function setTotalEnergy(?int $totalEnergy): self
    {
        $this->totalEnergy = $totalEnergy;

        return $this;
    }

    public function getNbMainsInterruption(): ?int
    {
        return $this->nbMainsInterruption;
    }

    public function setNbMainsInterruption(?int $nbMainsInterruption): self
    {
        $this->nbMainsInterruption = $nbMainsInterruption;

        return $this;
    }

    public function getDateTime(): ?\DateTimeInterface
    {
        return $this->dateTime;
    }

    public function setDateTime(\DateTimeInterface $dateTime): self
    {
        $this->dateTime = $dateTime;

        return $this;
    }

    public function getFuelLevel(): ?int
    {
        return $this->fuelLevel;
    }

    public function setFuelLevel(?int $fuelLevel): self
    {
        $this->fuelLevel = $fuelLevel;

        return $this;
    }
}
