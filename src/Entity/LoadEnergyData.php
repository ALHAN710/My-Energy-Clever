<?php

namespace App\Entity;

use App\Repository\LoadEnergyDataRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=LoadEnergyDataRepository::class)
 */
class LoadEnergyData
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="datetime")
     */
    private $dateTime;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $vamoy;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $vbmoy;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $vcmoy;

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
    private $pmoy;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $pmax;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $qamoy;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $qbmoy;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $qcmoy;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $qmoy;

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
    private $ea;

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
     * @ORM\ManyToOne(targetEntity=SmartMod::class, inversedBy="loadEnergyData")
     * @ORM\JoinColumn(nullable=false)
     */
    private $smartMod;

    /**
     * @ORM\Column(type="smallint", nullable=true)
     */
    private $workingGenset;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $depassement;

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
    private $qamax;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $qbmax;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $qcmax;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $qmax;

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

    public function getId(): ?int
    {
        return $this->id;
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

    public function getVamoy(): ?float
    {
        return $this->vamoy;
    }

    public function setVamoy(?float $vamoy): self
    {
        $this->vamoy = $vamoy;

        return $this;
    }

    public function getVbmoy(): ?float
    {
        return $this->vbmoy;
    }

    public function setVbmoy(?float $vbmoy): self
    {
        $this->vbmoy = $vbmoy;

        return $this;
    }

    public function getVcmoy(): ?float
    {
        return $this->vcmoy;
    }

    public function setVcmoy(?float $vcmoy): self
    {
        $this->vcmoy = $vcmoy;

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

    public function getPmoy(): ?float
    {
        return $this->pmoy;
    }

    public function setPmoy(?float $pmoy): self
    {
        $this->pmoy = $pmoy;

        return $this;
    }

    public function getQamoy(): ?float
    {
        return $this->qamoy;
    }

    public function setQamoy(?float $qamoy): self
    {
        $this->qamoy = $qamoy;

        return $this;
    }

    public function getQbmoy(): ?float
    {
        return $this->qbmoy;
    }

    public function setQbmoy(?float $qbmoy): self
    {
        $this->qbmoy = $qbmoy;

        return $this;
    }

    public function getQcmoy(): ?float
    {
        return $this->qcmoy;
    }

    public function setQcmoy(?float $qcmoy): self
    {
        $this->qcmoy = $qcmoy;

        return $this;
    }

    public function getQmoy(): ?float
    {
        return $this->qmoy;
    }

    public function setQmoy(?float $qmoy): self
    {
        $this->qmoy = $qmoy;

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

    public function getEa(): ?float
    {
        return $this->ea;
    }

    public function setEa(?float $ea): self
    {
        $this->ea = $ea;

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

    public function getSmartMod(): ?SmartMod
    {
        return $this->smartMod;
    }

    public function setSmartMod(?SmartMod $smartMod): self
    {
        $this->smartMod = $smartMod;

        return $this;
    }

    public function getWorkingGenset(): ?int
    {
        return $this->workingGenset;
    }

    public function setWorkingGenset(?int $workingGenset): self
    {
        $this->workingGenset = $workingGenset;

        return $this;
    }

    public function getDepassement(): ?int
    {
        return $this->depassement;
    }

    public function setDepassement(?int $depassement): self
    {
        $this->depassement = $depassement;

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

    public function getCosfimin(): ?float
    {
        return $this->cosfimin;
    }

    public function setCosfimin(?float $cosfimin): self
    {
        $this->cosfimin = $cosfimin;

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

    public function getQamax(): ?float
    {
        return $this->qamax;
    }

    public function setQamax(?float $qamax): self
    {
        $this->qamax = $qamax;

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

    public function getQcmax(): ?float
    {
        return $this->qcmax;
    }

    public function setQcmax(?float $qcmax): self
    {
        $this->qcmax = $qcmax;

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
}
