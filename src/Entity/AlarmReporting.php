<?php

namespace App\Entity;

use App\Repository\AlarmReportingRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=AlarmReportingRepository::class)
 */
class AlarmReporting
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="datetime_immutable")
     */
    private $createdAt;

    /**
     * @ORM\ManyToOne(targetEntity=Alarm::class, inversedBy="alarmReportings")
     * @ORM\JoinColumn(nullable=false)
     */
    private $alarm;

    /**
     * @ORM\ManyToOne(targetEntity=SmartMod::class, inversedBy="alarmReportings")
     * @ORM\JoinColumn(nullable=false)
     */
    private $smartMod;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getAlarm(): ?Alarm
    {
        return $this->alarm;
    }

    public function setAlarm(?Alarm $alarm): self
    {
        $this->alarm = $alarm;

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
}
