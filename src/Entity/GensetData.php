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
     * @ORM\Column(type="float", nullable=true)
     */
    private $totalRunningHours;

    /**
     * @ORM\Column(type="float", nullable=true)
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

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $nbPerformedStartUps;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $nbStop;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $va;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $vb;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $vc;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $pamax;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $pbmax;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $pcmax;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $pmax;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $qa;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $qamax;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $qb;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $qbmax;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $qc;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $qcmax;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $q;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $samax;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $sbmax;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $scmax;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $smax;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $cosfiamin;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $cosfibmin;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $cosficmin;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $cosfimin;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $eaa;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $eab;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $eac;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $era;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $erb;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $erc;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $er;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $qmax;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $battVoltage;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $battEnergy;

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

    public function getTotalRunningHours(): ?float
    {
        return $this->totalRunningHours;
    }

    public function setTotalRunningHours(?float $totalRunningHours): self
    {
        $this->totalRunningHours = $totalRunningHours;

        return $this;
    }

    public function getTotalEnergy(): ?float
    {
        return $this->totalEnergy;
    }

    public function setTotalEnergy(?float $totalEnergy): self
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

    public function getNbPerformedStartUps(): ?int
    {
        return $this->nbPerformedStartUps;
    }

    public function setNbPerformedStartUps(?int $nbPerformedStartUps): self
    {
        $this->nbPerformedStartUps = $nbPerformedStartUps;

        return $this;
    }

    public function getNbStop(): ?int
    {
        return $this->nbStop;
    }

    public function setNbStop(?int $nbStop): self
    {
        $this->nbStop = $nbStop;

        return $this;
    }

    public function getVa(): ?float
    {
        return $this->va;
    }

    public function setVa(?float $va): self
    {
        $this->va = $va;

        return $this;
    }

    public function getVb(): ?float
    {
        return $this->vb;
    }

    public function setVb(?float $vb): self
    {
        $this->vb = $vb;

        return $this;
    }

    public function getVc(): ?float
    {
        return $this->vc;
    }

    public function setVc(?float $vc): self
    {
        $this->vc = $vc;

        return $this;
    }

    public function getPamax(): ?float
    {
        return $this->pamax;
    }

    public function setPamax(?float $pamax): self
    {
        $this->pamax = $pamax;

        return $this;
    }

    public function getPbmax(): ?float
    {
        return $this->pbmax;
    }

    public function setPbmax(?float $pbmax): self
    {
        $this->pbmax = $pbmax;

        return $this;
    }

    public function getPcmax(): ?float
    {
        return $this->pcmax;
    }

    public function setPcmax(?float $pcmax): self
    {
        $this->pcmax = $pcmax;

        return $this;
    }

    public function getPmax(): ?float
    {
        return $this->pmax;
    }

    public function setPmax(?float $pmax): self
    {
        $this->pmax = $pmax;

        return $this;
    }

    public function getQa(): ?float
    {
        return $this->qa;
    }

    public function setQa(?float $qa): self
    {
        $this->qa = $qa;

        return $this;
    }

    public function getQamax(): ?float
    {
        return $this->qamax;
    }

    public function setQamax(?float $qamax): self
    {
        $this->qamax = $qamax;

        return $this;
    }

    public function getQb(): ?float
    {
        return $this->qb;
    }

    public function setQb(?float $qb): self
    {
        $this->qb = $qb;

        return $this;
    }

    public function getQbmax(): ?float
    {
        return $this->qbmax;
    }

    public function setQbmax(?float $qbmax): self
    {
        $this->qbmax = $qbmax;

        return $this;
    }

    public function getQc(): ?float
    {
        return $this->qc;
    }

    public function setQc(?float $qc): self
    {
        $this->qc = $qc;

        return $this;
    }

    public function getQcmax(): ?float
    {
        return $this->qcmax;
    }

    public function setQcmax(?float $qcmax): self
    {
        $this->qcmax = $qcmax;

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

    public function getSamax(): ?float
    {
        return $this->samax;
    }

    public function setSamax(?float $samax): self
    {
        $this->samax = $samax;

        return $this;
    }

    public function getSbmax(): ?float
    {
        return $this->sbmax;
    }

    public function setSbmax(?float $sbmax): self
    {
        $this->sbmax = $sbmax;

        return $this;
    }

    public function getScmax(): ?float
    {
        return $this->scmax;
    }

    public function setScmax(?float $scmax): self
    {
        $this->scmax = $scmax;

        return $this;
    }

    public function getSmax(): ?float
    {
        return $this->smax;
    }

    public function setSmax(?float $smax): self
    {
        $this->smax = $smax;

        return $this;
    }

    public function getCosfiamin(): ?float
    {
        return $this->cosfiamin;
    }

    public function setCosfiamin(?float $cosfiamin): self
    {
        $this->cosfiamin = $cosfiamin;

        return $this;
    }

    public function getCosfibmin(): ?float
    {
        return $this->cosfibmin;
    }

    public function setCosfibmin(?float $cosfibmin): self
    {
        $this->cosfibmin = $cosfibmin;

        return $this;
    }

    public function getCosficmin(): ?float
    {
        return $this->cosficmin;
    }

    public function setCosficmin(?float $cosficmin): self
    {
        $this->cosficmin = $cosficmin;

        return $this;
    }

    public function getCosfimin(): ?float
    {
        return $this->cosfimin;
    }

    public function setCosfimin(?float $cosfimin): self
    {
        $this->cosfimin = $cosfimin;

        return $this;
    }

    public function getEaa(): ?float
    {
        return $this->eaa;
    }

    public function setEaa(?float $eaa): self
    {
        $this->eaa = $eaa;

        return $this;
    }

    public function getEab(): ?float
    {
        return $this->eab;
    }

    public function setEab(?float $eab): self
    {
        $this->eab = $eab;

        return $this;
    }

    public function getEac(): ?float
    {
        return $this->eac;
    }

    public function setEac(?float $eac): self
    {
        $this->eac = $eac;

        return $this;
    }

    public function getEra(): ?float
    {
        return $this->era;
    }

    public function setEra(?float $era): self
    {
        $this->era = $era;

        return $this;
    }

    public function getErb(): ?float
    {
        return $this->erb;
    }

    public function setErb(?float $erb): self
    {
        $this->erb = $erb;

        return $this;
    }

    public function getErc(): ?float
    {
        return $this->erc;
    }

    public function setErc(?float $erc): self
    {
        $this->erc = $erc;

        return $this;
    }

    public function getEr(): ?float
    {
        return $this->er;
    }

    public function setEr(?float $er): self
    {
        $this->er = $er;

        return $this;
    }

    public function getQmax(): ?float
    {
        return $this->qmax;
    }

    public function setQmax(?float $qmax): self
    {
        $this->qmax = $qmax;

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
