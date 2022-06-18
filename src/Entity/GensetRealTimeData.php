<?php

namespace App\Entity;

use App\Repository\GensetRealTimeDataRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=GensetRealTimeDataRepository::class)
 */
class GensetRealTimeData
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $l12G;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $l13G;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $l23G;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $l1N;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $l2N;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $l3N;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $l12M;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $l13M;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $l23M;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $freq;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $fuelLevel;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $waterLevel;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $oilLevel;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $waterTemperature;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $coolerTemperature;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $battVoltage;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $hoursToMaintenance;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $gensetRunning;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $cg;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $mainsPresence;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $cr;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $maintenanceRequest;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $lowFuel;

    /**
     * @ORM\OneToOne(targetEntity=SmartMod::class, inversedBy="gensetRealTimeData", cascade={"persist", "remove"})
     */
    private $smartMod;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $dateTime;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $p;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $q;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $s;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $battState;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $battEnergy;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getL12G(): ?float
    {
        return $this->l12G;
    }

    public function setL12G(?float $l12G): self
    {
        $this->l12G = $l12G;

        return $this;
    }

    public function getL13G(): ?float
    {
        return $this->l13G;
    }

    public function setL13G(?float $l13G): self
    {
        $this->l13G = $l13G;

        return $this;
    }

    public function getL23G(): ?float
    {
        return $this->l23G;
    }

    public function setL23G(?float $l23G): self
    {
        $this->l23G = $l23G;

        return $this;
    }

    public function getL1N(): ?float
    {
        return $this->l1N;
    }

    public function setL1N(?float $l1N): self
    {
        $this->l1N = $l1N;

        return $this;
    }

    public function getL2N(): ?float
    {
        return $this->l2N;
    }

    public function setL2N(?float $l2N): self
    {
        $this->l2N = $l2N;

        return $this;
    }

    public function getL3N(): ?float
    {
        return $this->l3N;
    }

    public function setL3N(float $l3N): self
    {
        $this->l3N = $l3N;

        return $this;
    }

    public function getL12M(): ?float
    {
        return $this->l12M;
    }

    public function setL12M(?float $l12M): self
    {
        $this->l12M = $l12M;

        return $this;
    }

    public function getL13M(): ?float
    {
        return $this->l13M;
    }

    public function setL13M(?float $l13M): self
    {
        $this->l13M = $l13M;

        return $this;
    }

    public function getL23M(): ?float
    {
        return $this->l23M;
    }

    public function setL23M(?float $l23M): self
    {
        $this->l23M = $l23M;

        return $this;
    }

    public function getFreq(): ?float
    {
        return $this->freq;
    }

    public function setFreq(?float $freq): self
    {
        $this->freq = $freq;

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

    public function getWaterLevel(): ?int
    {
        return $this->waterLevel;
    }

    public function setWaterLevel(?int $waterLevel): self
    {
        $this->waterLevel = $waterLevel;

        return $this;
    }

    public function getOilLevel(): ?int
    {
        return $this->oilLevel;
    }

    public function setOilLevel(?int $oilLevel): self
    {
        $this->oilLevel = $oilLevel;

        return $this;
    }

    public function getWaterTemperature(): ?float
    {
        return $this->waterTemperature;
    }

    public function setWaterTemperature(?float $waterTemperature): self
    {
        $this->waterTemperature = $waterTemperature;

        return $this;
    }

    public function getCoolerTemperature(): ?float
    {
        return $this->coolerTemperature;
    }

    public function setCoolerTemperature(?float $coolerTemperature): self
    {
        $this->coolerTemperature = $coolerTemperature;

        return $this;
    }

    public function getBattVoltage(): ?float
    {
        return $this->battVoltage;
    }

    public function setBattVoltage(?float $battVoltage): self
    {
        $this->battVoltage = $battVoltage;

        return $this;
    }

    public function getHoursToMaintenance(): ?float
    {
        return $this->hoursToMaintenance;
    }

    public function setHoursToMaintenance(?float $hoursToMaintenance): self
    {
        $this->hoursToMaintenance = $hoursToMaintenance;

        return $this;
    }

    public function getGensetRunning(): ?int
    {
        return $this->gensetRunning;
    }

    public function setGensetRunning(?int $gensetRunning): self
    {
        $this->gensetRunning = $gensetRunning;

        return $this;
    }

    public function getCg(): ?int
    {
        return $this->cg;
    }

    public function setCg(?int $cg): self
    {
        $this->cg = $cg;

        return $this;
    }

    public function getMainsPresence(): ?int
    {
        return $this->mainsPresence;
    }

    public function setMainsPresence(?int $mainsPresence): self
    {
        $this->mainsPresence = $mainsPresence;

        return $this;
    }

    public function getCr(): ?int
    {
        return $this->cr;
    }

    public function setCr(?int $cr): self
    {
        $this->cr = $cr;

        return $this;
    }

    public function getMaintenanceRequest(): ?int
    {
        return $this->maintenanceRequest;
    }

    public function setMaintenanceRequest(?int $maintenanceRequest): self
    {
        $this->maintenanceRequest = $maintenanceRequest;

        return $this;
    }

    public function getLowFuel(): ?int
    {
        return $this->lowFuel;
    }

    public function setLowFuel(?int $lowFuel): self
    {
        $this->lowFuel = $lowFuel;

        return $this;
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

    public function getDateTime(): ?\DateTimeInterface
    {
        return $this->dateTime;
    }

    public function setDateTime(?\DateTimeInterface $dateTime): self
    {
        $this->dateTime = $dateTime;

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

    public function getQ(): ?float
    {
        return $this->q;
    }

    public function setQ(?float $q): self
    {
        $this->q = $q;

        return $this;
    }

    public function getS(): ?float
    {
        return $this->s;
    }

    public function setS(?float $s): self
    {
        $this->s = $s;

        return $this;
    }

    public function getBattState(): ?bool
    {
        return $this->battState;
    }

    public function setBattState(?bool $battState): self
    {
        $this->battState = $battState;

        return $this;
    }

    public function getBattEnergy(): ?float
    {
        return $this->battEnergy;
    }

    public function setBattEnergy(?float $battEnergy): self
    {
        $this->battEnergy = $battEnergy;

        return $this;
    }
}
