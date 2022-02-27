<?php

namespace App\Service;

use DateTime;
use DateInterval;
use App\Entity\Site;
use Doctrine\ORM\EntityManagerInterface;

class SiteDashboardDataService
{
    /**
     * Prix du kWh en F CFA
     *
     * @var float
     */
    private $kWhPrice = 99;

    /**
     * Tableau des tarifs en fonction de la tranche horaire pour 
     * les abonnés BT de type Résidentiel
     *
     * @var array
     */
    private $tranchesResidential = [
        '0-110'   => 50,
        '111-400' => 79,
        '401-800' => 94,
        '800+'    => 99,
    ];

    /**
     * Tableau des tarifs en fonction de la tranche horaire pour 
     * les abonnés BT de type Non Résidentiel
     *
     * @var array
     */
    private $tranchesNonResidential = [
        '0-110'   => 84,
        '111-400' => 92,
        '401+'    => 99,
    ];

    /**
     * Tableau des tarifs en fonction du nbre d'heure d'utilisation de Psous
     * pour les abonnés MT régime normal
     *
     * @var array
     */
    private $NHU_Psous = [
        '0-200'   => 70,
        '201-400' => 65,
        '401+'    => 60,
    ];

    private $currentConsokWh = 0.0;
    private $EAHP = 0.0;
    private $EAP  = 0.0;
    private $EA   = 0.0;
    private $ERHP = 0.0;
    private $ERP  = 0.0;
    private $ER   = 0.0;
    private $FPHP = 0.0;
    private $FPP  = 0.0;
    private $FP   = 0.0;
    private $amountHT   = 0.0;
    private $currentNbDepassement = 0;
    private $DUG = 0;
    private $TUG = 0.0;

    /**
     * Quantité de gaz à effet de serre(en kgCO2) émis par kWh
     *
     * @var float
     */
    private $CO2PerkWh = 0.207;

    private $manager;

    private $site;

    /**
     * Multiple de Watt
     *
     * @var integer
     */
    private $power_unit = 1;

    /**
     * Date de début de la fenêtre de date choisie par l'utilisateur
     *
     * @var DateTime
     */
    private $startDate;

    /**
     * Date de fin de la fenêtre de date choisie par l'utilisateur
     *
     * @var DateTime
     */
    private $endDate;

    private $currentMonthStringDate = '';

    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager  = $manager;
        $this->currentMonthStringDate = date('Y-m') . '%';
    }

    /**
     * Permet d'avoir les données de conso en kWh et XAF, 
     * ainsi que le taux d'émission de CO2 relatif à cette conso(en kWh) sur le mois en cours pour un site donné
     *
     */
    public function getCurrentMonthkWhConsumption()
    {
        if ($this->site->getSubscription() === 'MT') { //Pour les Sites abonnés en MT
            $lastConsokWh = $this->getLastMonthkWhConsumption();
            $currentConsoQuery = [];
            $currentConsokWh         = 0.0;
            $currentConsokWh         = 0.0;
            $currentGasEmission      = 0.0;

            if ($this->site->getHasOneSmartMod() == false) { //Site à smartMeter GRID et FUEL séparé
                /* $currentConsoQuery = $this->manager->createQuery("SELECT SUM(d.ea) AS EA,  SUM(d.depassement) AS Nb_Depassement,
                                            SUM(d.ea)/SQRT( (SUM(d.ea)*SUM(d.ea)) + (SUM(d.er)*SUM(d.er)) ) AS PF
                                            FROM App\Entity\LoadEnergyData d
                                            JOIN d.smartMod sm
                                            WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='GRID')
                                            AND d.dateTime LIKE :currentMonth")
                    ->setParameters(array(
                        'currentMonth'  => $this->currentMonthStringDate,
                        'siteId'        => $this->site->getId()
                    ))
                    ->getResult();

                $lastConsokWh = $this->getLastMonthkWhConsumption();
                //dump($lastConsokWh);

                $currentConsokWh         = count($currentConsoQuery) > 0 ? $currentConsoQuery[0]['EA'] ?? 0.0 : 0.0;
                $currentGasEmission      = $currentConsokWh * $this->CO2PerkWh; //?????????


                $currentMonthConsokWhPerRangeQuery = $this->manager->createQuery("SELECT SUM(CASE 
                                                                        WHEN SUBSTRING(d.dateTime, 12) BETWEEN '23:00:00' AND '23:59:59' OR SUBSTRING(d.dateTime, 12) BETWEEN '00:00:00' AND '17:59:59' THEN d.ea
                                                                        ELSE 0
                                                                    END) AS EAHP,
                                                                    SUM(CASE 
                                                                        WHEN (d.pmoy >= :psous) AND (SUBSTRING(d.dateTime, 12) BETWEEN '23:00:00' AND '23:59:59' OR SUBSTRING(d.dateTime, 12) BETWEEN '00:00:00' AND '17:59:59') THEN 1
                                                                        ELSE 0
                                                                    END) AS EAHP_Hours,
                                                                    SUM(CASE 
                                                                        WHEN SUBSTRING(d.dateTime, 12) BETWEEN '18:00:00' AND '22:59:59' THEN d.ea
                                                                        ELSE 0
                                                                    END) AS EAP, 
                                                                    SUM(CASE 
                                                                        WHEN SUBSTRING(d.dateTime, 12) BETWEEN '23:00:00' AND '23:59:59' OR SUBSTRING(d.dateTime, 12) BETWEEN '00:00:00' AND '17:59:59' THEN d.er
                                                                        ELSE 0
                                                                    END) AS ERHP,
                                                                    SUM(CASE 
                                                                        WHEN SUBSTRING(d.dateTime, 12) BETWEEN '18:00:00' AND '22:59:59' THEN d.er
                                                                        ELSE 0
                                                                    END) AS ERP, 
                                                                    SUM(CASE 
                                                                        WHEN (d.pmoy >= :psous) AND (SUBSTRING(d.dateTime, 12) BETWEEN '18:00:00' AND '22:59:59') THEN 1
                                                                        ELSE 0
                                                                    END) AS EAP_Hours 
                                                                    FROM App\Entity\LoadEnergyData d
                                                                    JOIN d.smartMod sm
                                                                    WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='GRID')
                                                                    AND d.dateTime LIKE :currentMonth
                                                                    ")
                    ->setParameters(array(
                        'currentMonth' => $this->currentMonthStringDate,
                        'siteId'       => $this->site->getId(),
                        'psous'        => $this->site->getPowerSubscribed(),
                    ))
                    ->getResult();
                //dump($currentMonthConsokWhPerRangeQuery);
              */
            } else { //Site à smartMeter GRID et FUEL en un
                //Requête de détermination de la durée de fonctionnement (en heures) sur le mois en cours
                /*$workingTimeQuery = $this->manager->createQuery("SELECT COUNT(DISTINCT d.dateTime)/12 AS WT
                                            FROM App\Entity\LoadEnergyData d
                                            JOIN d.smartMod sm
                                            WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='GRID_FUEL')
                                            AND d.dateTime LIKE :currentMonth")
                    ->setParameters(array(
                        'currentMonth'  => $this->currentMonthStringDate,
                        'siteId'        => $this->site->getId()
                    ))
                    ->getResult();
                dump($workingTimeQuery);
                $currentMonthWorkingHours = count($workingTimeQuery) > 0 ? $workingTimeQuery[0]['WT'] ?? 0 : 0;
                $currentMonthWorkingHours = floatval($currentMonthWorkingHours);*/

                $gensetQuery = $this->manager->createQuery("SELECT SUM(d.workingGenset)/12 AS DUG
                                            FROM App\Entity\LoadEnergyData d
                                            JOIN d.smartMod sm
                                            WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='GRID_FUEL')
                                            AND d.workingGenset = 1
                                            AND d.dateTime LIKE :currentMonth")
                    ->setParameters(array(
                        'currentMonth'  => $this->currentMonthStringDate,
                        'siteId'        => $this->site->getId()
                    ))
                    ->getResult();
                //dump($gensetQuery);
                $this->DUG = count($gensetQuery) > 0 ? $gensetQuery[0]['DUG'] ?? 0.0 : 0.0;
                $this->DUG = floatval($this->DUG);
                $this->TUG = $this->DUG / 720;
                // $this->TUG = $currentMonthWorkingHours > 0 ? $this->DUG / $currentMonthWorkingHours : 0;

                //Consommation en kWh 
                $currentConsoQuery = $this->manager->createQuery("SELECT SUM(d.ea) AS EA, SUM(d.depassement) AS Nb_Depassement,
                                            SUM(d.ea)/SQRT( (SUM(d.ea)*SUM(d.ea)) + (SUM(d.er)*SUM(d.er)) ) AS PF
                                            FROM App\Entity\LoadEnergyData d
                                            JOIN d.smartMod sm
                                            WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='GRID_FUEL')
                                            AND d.dateTime LIKE :currentMonth")
                    ->setParameters(array(
                        'currentMonth'  => $this->currentMonthStringDate,
                        'siteId'        => $this->site->getId()
                    ))
                    ->getResult();

                //dump($currentConsoQuery);
                //dump($this->TUG);
                //$TUGrid       = 1 - $this->TUG; //Taux d'utilisation du GRID

                //$lastConsokWh = $lastConsokWh * $TUGrid;
                //dump($lastConsokWh);

                $currentConsokWh         = count($currentConsoQuery) > 0 ? $currentConsoQuery[0]['EA'] ?? 0.0 : 0.0;
                $currentConsokWh         = floatval($currentConsokWh);
                $currentGasEmission      = $currentConsokWh * ($this->TUG * 0.35 + 0.4);
                //$currentConsokWh         = $currentConsokWh * $TUGrid;


                $currentMonthConsokWhPerRangeQuery = $this->manager->createQuery("SELECT SUM(CASE 
                                                                        WHEN (d.workingGenset = 0) AND SUBSTRING(d.dateTime, 12) BETWEEN '23:00:00' AND '23:59:59' OR SUBSTRING(d.dateTime, 12) BETWEEN '00:00:00' AND '17:59:59' THEN d.ea
                                                                        ELSE 0
                                                                    END) AS EAHP,
                                                                    SUM(CASE 
                                                                        WHEN (d.workingGenset = 0) AND (d.pmoy >= :psous) AND (SUBSTRING(d.dateTime, 12) BETWEEN '23:00:00' AND '23:59:59' OR SUBSTRING(d.dateTime, 12) BETWEEN '00:00:00' AND '17:59:59') THEN 1
                                                                        ELSE 0
                                                                    END) AS EAHP_Hours,
                                                                    SUM(CASE 
                                                                        WHEN (d.workingGenset = 0) AND SUBSTRING(d.dateTime, 12) BETWEEN '18:00:00' AND '22:59:59' THEN d.ea
                                                                        ELSE 0
                                                                    END) AS EAP, 
                                                                    SUM(CASE 
                                                                        WHEN (d.workingGenset = 0) AND SUBSTRING(d.dateTime, 12) BETWEEN '23:00:00' AND '23:59:59' OR SUBSTRING(d.dateTime, 12) BETWEEN '00:00:00' AND '17:59:59' THEN d.er
                                                                        ELSE 0
                                                                    END) AS ERHP,
                                                                    SUM(CASE 
                                                                        WHEN (d.workingGenset = 0) AND SUBSTRING(d.dateTime, 12) BETWEEN '18:00:00' AND '22:59:59' THEN d.er
                                                                        ELSE 0
                                                                    END) AS ERP, 
                                                                    SUM(CASE 
                                                                        WHEN (d.workingGenset = 0) AND (d.pmoy >= :psous) AND (SUBSTRING(d.dateTime, 12) BETWEEN '18:00:00' AND '22:59:59') THEN 1
                                                                        ELSE 0
                                                                    END) AS EAP_Hours 
                                                                    FROM App\Entity\LoadEnergyData d
                                                                    JOIN d.smartMod sm
                                                                    WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='GRID_FUEL')
                                                                    AND d.dateTime LIKE :currentMonth
                                                                    ")
                    ->setParameters(array(
                        'currentMonth' => $this->currentMonthStringDate,
                        'siteId'       => $this->site->getId(),
                        'psous'        => $this->site->getPowerSubscribed(),
                    ))
                    ->getResult();
                //dump($currentMonthConsokWhPerRangeQuery);

            }

            $currentPF               = count($currentConsoQuery) > 0 ? $currentConsoQuery[0]['PF'] ?? 0.0 : 0.0;
            $currentConsokWhProgress = ($lastConsokWh !== 0) ? ($currentConsokWh - $lastConsokWh) / $lastConsokWh : 'INF';

            $this->currentNbDepassement    = count($currentConsoQuery) > 0 ? $currentConsoQuery[0]['Nb_Depassement'] ?? 0 : 0;

            $this->EAHP   = $currentMonthConsokWhPerRangeQuery[0]['EAHP'] ?? 0;
            $this->EAHP   = floatval($this->EAHP);
            $this->EAP    = $currentMonthConsokWhPerRangeQuery[0]['EAP'] ?? 0;
            $this->EAP    = floatval($this->EAP);
            $this->EA     = $this->EAHP + $this->EAP;

            $currentConsoHPHours = $currentMonthConsokWhPerRangeQuery[0]['EAHP_Hours'] ?? 0;
            $currentConsoHPHours = intval($currentConsoHPHours) * 2;
            // $currentConsoPHours  = $currentMonthConsokWhPerRangeQuery[0]['EAP_Hours'] ?? 0;
            // $currentConsoPHours  = $currentConsoPHours * 2;

            $this->ERHP   = $currentMonthConsokWhPerRangeQuery[0]['ERHP'] ?? 0;
            $this->ERHP   = floatval($this->ERHP);
            $this->ERP    = $currentMonthConsokWhPerRangeQuery[0]['ERP'] ?? 0;
            $this->ERP    = floatval($this->ERP);
            $this->ER     = $this->ERHP + $this->ERP;

            $denom        = SQRT(($this->EAHP * $this->EAHP) + ($this->ERHP * $this->ERHP));
            $this->FPHP   = $denom > 0 ? $this->EAHP / $denom : 0.0;
            $denom        = SQRT(($this->EAP * $this->EAP) + ($this->ERP * $this->ERP));
            $this->FPP    = $denom > 0 ? $this->EAP / $denom : 0.0;
            $denom        = SQRT(($this->EA * $this->EA) + ($this->ER * $this->ER));
            $this->FP     = $denom > 0 ? $this->EA / $denom : 0.0;


            $this->amountHT     = $this->getConsumptionXAF($currentConsokWh, array(
                'EAP'        => $this->EAP,
                'EAHP'       => $this->EAHP,
                'EAHP_Hours' => $currentConsoHPHours
            ));

            $this->currentConsokWh = $currentConsokWh;

            return array(
                'currentConsokWh'         => $currentConsokWh,
                'currentConsokWhProgress' => $currentConsokWhProgress,
                'currentConsoXAF'         => $this->amountHT,
                'currentGasEmission'      => $currentGasEmission,
                'lastConsokWh'            => $lastConsokWh,
                'currentPF'               => $currentPF,
            );
        }

        //Pour les Sites abonnés en BT
        $currentConsoQuery = $this->manager->createQuery("SELECT SUM(d.ea) AS EA, 
                                            SUM(d.ea)/SQRT( (SUM(d.ea)*SUM(d.ea)) + (SUM(d.er)*SUM(d.er)) ) AS PF
                                            FROM App\Entity\LoadEnergyData d
                                            JOIN d.smartMod sm
                                            WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='GRID')
                                            AND d.dateTime LIKE :currentMonth")
            ->setParameters(array(
                'currentMonth'  => $this->currentMonthStringDate,
                'siteId'        => $this->site->getId()
            ))
            ->getResult();

        // dump($currentConsoQuery);
        $lastConsokWh = $this->getLastMonthkWhConsumption();
        //dump($lastConsokWh);

        $currentConsokWh         = count($currentConsoQuery) > 0 ? $currentConsoQuery[0]['EA'] ?? 0.0 : 0.0;
        $currentGasEmission      = $currentConsokWh * $this->CO2PerkWh;
        $currentPF               = count($currentConsoQuery) > 0 ? $currentConsoQuery[0]['PF'] ?? 0.0 : 0.0;
        $currentConsokWhProgress = ($lastConsokWh !== 0) ? ($currentConsokWh - $lastConsokWh) / $lastConsokWh : 'INF';
        $currentConsoXAF  = $this->getConsumptionXAF($currentConsokWh);

        return array(
            'currentConsokWh'         => $currentConsokWh,
            'currentConsokWhProgress' => $currentConsokWhProgress,
            'currentConsoXAF'         => $currentConsoXAF,
            'currentGasEmission'      => $currentGasEmission,
            'lastConsokWh'            => $lastConsokWh,
            'currentPF'               => $currentPF,
        );
    }

    public function getCurrentMonthkWConsumption()
    {
        $currentConsoQuery = $this->manager->createQuery("SELECT SUM(d.pmoy) AS kW
                                            FROM App\Entity\LoadEnergyData d
                                            JOIN d.smartMod sm
                                            WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='GRID')
                                            AND d.dateTime LIKE :currentMonth")
            ->setParameters(array(
                'currentMonth'  => $this->currentMonthStringDate,
                'siteId'        => $this->site->getId()
            ))
            ->getResult();
        //dump($currentConsoQuery);

        $currentConsokW     = count($currentConsoQuery) > 0 ? $currentConsoQuery[0]['kW'] : 0;
        $currentGasEmission = $currentConsokW * $this->CO2PerkWh;

        return array(
            'currentConsokW'     => $currentConsokW,
            'currentGasEmission' => $currentGasEmission
        );
    }

    /**
     * Permet de récupérer la puissance active actuelle d'un site
     *
     */
    public function getLastkWForCurrentMonth()
    {
        $lastPower = $this->manager->createQuery("SELECT d.pmoy/:power_unit AS kW
                                            FROM App\Entity\LoadEnergyData d
                                            JOIN d.smartMod sm
                                            WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='GRID')
                                            AND d.dateTime LIKE :currentMonth
                                            AND d.dateTime =  (SELECT max(d1.dateTime) FROM App\Entity\LoadEnergyData d1 WHERE d1.dateTime LIKE :currentMonth)
                                            ")
            ->setParameters(array(
                'currentMonth'  => $this->currentMonthStringDate,
                'siteId'        => $this->site->getId(),
                'power_unit'    => $this->power_unit
            ))
            ->getResult();
        //dump($lastPower);
        return count($lastPower) > 0.0 ? $lastPower[0]['kW'] : 0.0;
    }

    /**
     * Permet d'avoir la conso (en kWh) mensuelle du mois n - 1 à la même date en cours
     *
     */
    public function getLastMonthkWhConsumption()
    {
        //$now  = new DateTime('now');
        $now  = new DateTime('2021-11-30 16:20:00');
        $date = $now;
        $date->sub(new DateInterval('P1M'));

        if ($this->site->getSubscription() === 'MT') { //Pour les Sites abonnés en MT
            if ($this->site->getHasOneSmartMod() == true) { //Site à smartMeter GRID et FUEL en un
                $lastConsoQuery = $this->manager->createQuery("SELECT SUM(d.ea) AS EA
                                            FROM App\Entity\LoadEnergyData d
                                            JOIN d.smartMod sm
                                            WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='GRID_FUEL')
                                            AND d.dateTime LIKE :lastMonth
                                            AND d.dateTime <= :lastNowDate
                                            ")
                    ->setParameters(array(
                        'lastMonth'     => $date->format('Y-m') . '%',
                        'lastNowDate'   => $date->format('Y-m-d H:i:s'),
                        'siteId'        => $this->site->getId()
                    ))
                    ->getResult();

                //dump($lastConsoQuery);
                $lastConso = count($lastConsoQuery) > 0 ? $lastConsoQuery[0]['EA'] ?? 0 : 0;
                //dump('lastConso = ' . $lastConso);

                return $lastConso;
            }
        }

        //Pour les Sites abonnés en BT
        $lastConsoQuery = $this->manager->createQuery("SELECT SUM(d.ea) AS EA
                                            FROM App\Entity\LoadEnergyData d
                                            JOIN d.smartMod sm
                                            WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='GRID')
                                            AND d.dateTime LIKE :lastMonth
                                            AND d.dateTime <= :lastNowDate
                                            ")
            ->setParameters(array(
                'lastMonth'     => $date->format('Y-m') . '%',
                'lastNowDate'   => $date->format('Y-m-d H:i:s'),
                'siteId'        => $this->site->getId()
            ))
            ->getResult();

        //dump($lastConsoQuery);
        $lastConso = count($lastConsoQuery) > 0 ? $lastConsoQuery[0]['EA'] ?? 0 : 0;
        //dump('lastConso = ' . $lastConso);

        return $lastConso;
    }

    /**
     * Permet d'obtenir les données de conso (en kWh) et d'émission de CO2
     * jour après jour pour un site donné
     *
     * @return array
     */
    public function getDayByDayConsumptionForCurrentMonth()
    {
        if ($this->site->getSubscription() === 'MT') { //Pour les Sites abonnés en MT
            if ($this->site->getHasOneSmartMod() == true) { //Site à smartMeter GRID et FUEL en un
                $dayByDayConsoData = $this->manager->createQuery("SELECT SUBSTRING(d.dateTime,1,10) AS jour, SUM(d.ea) AS EA,
                                            SUM(CASE 
                                                WHEN d.workingGenset = 0 THEN d.ea*0.4
                                                ELSE d.ea*0.75
                                            END) AS kgCO2
                                            FROM App\Entity\LoadEnergyData d
                                            JOIN d.smartMod sm
                                            WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='GRID_FUEL')
                                            AND d.dateTime LIKE :currentMonth
                                            GROUP BY jour
                                            ORDER BY jour ASC")
                    ->setParameters(array(
                        'currentMonth'  => $this->currentMonthStringDate,
                        'siteId'    => $this->site->getId()
                    ))
                    ->getResult();
                //dump($dayByDayConsoData);

            } else { //Site à smartMeter GRID et FUEL séparé

            }
        } else {
            $dayByDayConsoData = $this->manager->createQuery("SELECT SUBSTRING(d.dateTime,1,10) AS jour, SUM(d.ea) AS EA, SUM(d.ea)*:kgCO2 AS kgCO2
                                                FROM App\Entity\LoadEnergyData d
                                                JOIN d.smartMod sm
                                                WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='GRID')
                                                AND d.dateTime LIKE :currentMonth
                                                GROUP BY jour
                                                ORDER BY jour ASC")
                ->setParameters(array(
                    'kgCO2'        => $this->CO2PerkWh,
                    'currentMonth'  => $this->currentMonthStringDate,
                    'siteId'    => $this->site->getId()
                ))
                ->getResult();
            //dump($dayByDayConsoData);
        }

        $dateConso = [];
        $kWh       = [];
        $kgCO2     = [];
        foreach ($dayByDayConsoData as $d) {
            $dateConso[] = $d['jour'];
            $kWh[]       = floatval(number_format((float) $d['EA'], 2, '.', ''));
            $kgCO2[]     = floatval(number_format((float) $d['kgCO2'], 2, '.', ''));
        }

        return array(
            "dateConso" => $dateConso,
            "kWh"       => $kWh,
            "kgCO2"     => $kgCO2
        );
    }

    public function getCurrentMonthDataTable()
    {
        // $testDate = new DateTime('2021-10-15 17:10:00');
        // $consoMoy = $this->getAverageConsumptionWithLimit(10, $testDate->format('Y-m'), $testDate->format('Y-m-d H:i:s'));
        //$now      = new DateTime('now');
        $now      = new DateTime('2021-11-30 16:20:00');
        $lastMonthDate = $now;
        //$date = $testDate;
        $lastMonthDate->sub(new DateInterval('P1M'));

        if ($this->site->getSubscription() === 'MT') { //Pour les Sites abonnés en MT
            if ($this->site->getHasOneSmartMod() == true) { //Site à smartMeter GRID et FUEL en un
                $consoQuery = $this->manager->createQuery("SELECT SUBSTRING(d.dateTime,1,10) AS jour, SUM(d.ea) AS kWh
                                                FROM App\Entity\LoadEnergyData d
                                                JOIN d.smartMod sm
                                                WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='GRID_FUEL')
                                                AND d.dateTime LIKE :currentMonth
                                                GROUP BY jour
                                                ORDER BY kWh DESC")
                    ->setParameters(array(
                        'currentMonth' => $this->currentMonthStringDate,
                        'siteId'       => $this->site->getId()
                    ))
                    ->getResult();
                //dump($consoQuery);

                $powerQuery = $this->manager->createQuery("SELECT DISTINCT d.dateTime AS jour, NULLIF(d.pmoy/:power_unit, 0) AS kW 
                                            FROM App\Entity\LoadEnergyData d
                                            JOIN d.smartMod sm
                                            WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='GRID_FUEL')
                                            AND d.dateTime LIKE :currentMonth
                                            GROUP BY jour
                                            ORDER BY kW ASC")
                    ->setParameters(array(
                        'currentMonth' => $this->currentMonthStringDate,
                        'siteId'       => $this->site->getId(),
                        'power_unit'   => $this->power_unit,
                    ))
                    ->getResult();
                //dump($powerQuery);

                $currentMonthConsokWhPerHoursRangeQuery = $this->manager->createQuery("SELECT SUM(CASE 
                                                                    WHEN SUBSTRING(d.dateTime, 12) BETWEEN '00:00:00' AND '05:59:59' THEN d.ea
                                                                    ELSE 0
                                                                END) AS Tranche00_06,
                                                                SUM(CASE 
                                                                    WHEN SUBSTRING(d.dateTime, 12) BETWEEN '06:00:00' AND '17:59:59' THEN d.ea
                                                                    ELSE 0
                                                                END) AS Tranche06_18, 
                                                                SUM(CASE 
                                                                    WHEN SUBSTRING(d.dateTime, 12) BETWEEN '18:00:00' AND '23:59:59' THEN d.ea
                                                                    ELSE 0
                                                                END) AS Tranche18_00 
                                                                FROM App\Entity\LoadEnergyData d
                                                                JOIN d.smartMod sm
                                                                WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='GRID_FUEL')
                                                                AND d.dateTime LIKE :currentMonth
                                                                ")
                    ->setParameters(array(
                        'currentMonth' => $this->currentMonthStringDate,
                        'siteId'       => $this->site->getId()
                    ))
                    ->getResult();

                $lastMonthConsokWhPerHoursRangeQuery = $this->manager->createQuery("SELECT SUM(CASE 
                                                                           WHEN SUBSTRING(d.dateTime, 12) BETWEEN '00:00:00' AND '05:59:59' THEN d.ea
                                                                           ELSE 0
                                                                END) AS Tranche00_06,
                                                                SUM(CASE 
                                                                    WHEN SUBSTRING(d.dateTime, 12) BETWEEN '06:00:00' AND '17:59:59' THEN d.ea
                                                                    ELSE 0
                                                                END) AS Tranche06_18, 
                                                                SUM(CASE 
                                                                    WHEN SUBSTRING(d.dateTime, 12) BETWEEN '18:00:00' AND '23:59:59' THEN d.ea
                                                                    ELSE 0
                                                                END) AS Tranche18_00 
                                                                FROM App\Entity\LoadEnergyData d
                                                                JOIN d.smartMod sm
                                                                WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='GRID_FUEL')
                                                                AND d.dateTime LIKE :lastMonth
                                                                AND d.dateTime <= :lastMonthSupDate
                                                                ")
                    ->setParameters(array(
                        'lastMonth'        => $lastMonthDate->format('Y-m') . '%',
                        'lastMonthSupDate' => $lastMonthDate->format('Y-m-d H:i:s') . '%',
                        'siteId'           => $this->site->getId()
                    ))
                    ->getResult();
            } else { //Site à smartMeter GRID et FUEL séparé

            }
        } else { //Pour les Sites abonnés en BT
            $consoQuery = $this->manager->createQuery("SELECT SUBSTRING(d.dateTime,1,10) AS jour, SUM(d.ea) AS kWh
                                                FROM App\Entity\LoadEnergyData d
                                                JOIN d.smartMod sm
                                                WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='GRID')
                                                AND d.dateTime LIKE :currentMonth
                                                GROUP BY jour
                                                ORDER BY kWh DESC")
                ->setParameters(array(
                    'currentMonth' => $this->currentMonthStringDate,
                    'siteId'       => $this->site->getId()
                ))
                ->getResult();
            //dump($consoQuery);

            $powerQuery = $this->manager->createQuery("SELECT DISTINCT d.dateTime AS jour, NULLIF(d.pmoy/:power_unit, 0) AS kW 
                                            FROM App\Entity\LoadEnergyData d
                                            JOIN d.smartMod sm
                                            WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='GRID')
                                            AND d.dateTime LIKE :currentMonth
                                            GROUP BY jour
                                            ORDER BY kW ASC")
                ->setParameters(array(
                    'currentMonth' => $this->currentMonthStringDate,
                    'siteId'       => $this->site->getId(),
                    'power_unit'   => $this->power_unit,
                ))
                ->getResult();
            //dump($powerQuery);

            $currentMonthConsokWhPerHoursRangeQuery = $this->manager->createQuery("SELECT SUM(CASE 
                                                                    WHEN SUBSTRING(d.dateTime, 12) BETWEEN '00:00:00' AND '05:59:59' THEN d.ea
                                                                    ELSE 0
                                                                END) AS Tranche00_06,
                                                                SUM(CASE 
                                                                    WHEN SUBSTRING(d.dateTime, 12) BETWEEN '06:00:00' AND '17:59:59' THEN d.ea
                                                                    ELSE 0
                                                                END) AS Tranche06_18, 
                                                                SUM(CASE 
                                                                    WHEN SUBSTRING(d.dateTime, 12) BETWEEN '18:00:00' AND '23:59:59' THEN d.ea
                                                                    ELSE 0
                                                                END) AS Tranche18_00 
                                                                FROM App\Entity\LoadEnergyData d
                                                                JOIN d.smartMod sm
                                                                WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='GRID')
                                                                AND d.dateTime LIKE :currentMonth
                                                                ")
                ->setParameters(array(
                    'currentMonth' => $this->currentMonthStringDate,
                    'siteId'       => $this->site->getId()
                ))
                ->getResult();

            $lastMonthConsokWhPerHoursRangeQuery = $this->manager->createQuery("SELECT SUM(CASE 
                                                                           WHEN SUBSTRING(d.dateTime, 12) BETWEEN '00:00:00' AND '05:59:59' THEN d.ea
                                                                           ELSE 0
                                                                END) AS Tranche00_06,
                                                                SUM(CASE 
                                                                    WHEN SUBSTRING(d.dateTime, 12) BETWEEN '06:00:00' AND '17:59:59' THEN d.ea
                                                                    ELSE 0
                                                                END) AS Tranche06_18, 
                                                                SUM(CASE 
                                                                    WHEN SUBSTRING(d.dateTime, 12) BETWEEN '18:00:00' AND '23:59:59' THEN d.ea
                                                                    ELSE 0
                                                                END) AS Tranche18_00 
                                                                FROM App\Entity\LoadEnergyData d
                                                                JOIN d.smartMod sm
                                                                WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='GRID')
                                                                AND d.dateTime LIKE :lastMonth
                                                                AND d.dateTime <= :lastMonthSupDate
                                                                ")
                ->setParameters(array(
                    'lastMonth'        => $lastMonthDate->format('Y-m') . '%',
                    'lastMonthSupDate' => $lastMonthDate->format('Y-m-d H:i:s') . '%',
                    'siteId'           => $this->site->getId()
                ))
                ->getResult();
        }

        $strPlusForteConso = "-";
        $strPlusFaibleConso = "-";
        if (!empty($consoQuery)) {

            $lowConso      = end($consoQuery);
            $highConso     = reset($consoQuery);
            $lowConsoDate  = new DateTime($lowConso['jour']);
            $highConsoDate = new DateTime($highConso['jour']);
            //dump($lowConso);
            //dump($highConso);
            //number_format((float) $d['kW'], 2, '.', '')
            $strPlusForteConso  = $highConso != null ? number_format((float) $highConso['kWh'], 2, '.', ' ') . ' kWh @ ' . $highConsoDate->format('d M Y') : '-';
            $strPlusFaibleConso = $lowConso != null ? number_format((float) $lowConso['kWh'], 2, '.', ' ') . ' kWh @ ' . $lowConsoDate->format('d M Y') : '-';
        }

        $strPic = '-';
        $strTalon = '-';
        if (!empty($powerQuery)) {

            //$lowPower = reset($powerQuery);
            $highPower = end($powerQuery);
            $lowPower = null;
            $array = $powerQuery;
            $min = 0;
            //Recherche de la puissance minimale non nulle
            foreach ($array as $v) if ($v['kW'] > $min) {
                $lowPower = $v;
                break;
            }

            $powerUnitStr = $this->power_unit === 1000 ? 'kW' : 'W';

            //dump($lowPower);
            // $strTalon = $lowPower != null ? number_format((float) ($lowPower['kW']), 2, '.', ' ') . ' W @ ' . $lowPower['jour']->format('d M Y H:i:s') : '-';
            $strTalon = $lowPower != null ? number_format((float) ($lowPower['kW']), 2, '.', ' ') . ' ' . $powerUnitStr . ' @ ' . $lowPower['jour']->format('d M Y H:i:s') : '-';
            $strPic   = $highPower != null ? number_format((float) ($highPower['kW']), 2, '.', ' ') . ' ' . $powerUnitStr . ' @ ' . $highPower['jour']->format('d M Y H:i:s') : '-';
        }
        //$consoMoy      = $this->getAverageConsumptionWithLimit(10, date('Y-m') . '%', date('Y-m-d H:i:s') . '%');
        $consoMoy      = $this->getAverageConsumptionWithLimit(10, date('Y-m') . '%', '2021-11-30 16:20:00' . '%');
        $lastConsokWh  = $this->getAverageConsumptionWithLimit(10, $lastMonthDate->format('Y-m') . '%', $lastMonthDate->format('Y-m-d H:i:s') . '%');
        //dump($lastConsokWh);

        $consoMoyProgress = ($lastConsokWh > 0) ? ($consoMoy - $lastConsokWh) / $lastConsokWh : 'INF';

        $currentConso00_06kWh = $currentMonthConsokWhPerHoursRangeQuery[0]['Tranche00_06'] ?? 0;
        $currentConso06_18kWh = $currentMonthConsokWhPerHoursRangeQuery[0]['Tranche06_18'] ?? 0;
        $currentConso18_00kWh = $currentMonthConsokWhPerHoursRangeQuery[0]['Tranche18_00'] ?? 0;

        $lastConso00_06kWh = $lastMonthConsokWhPerHoursRangeQuery[0]['Tranche00_06'] ?? 0;
        $lastConso06_18kWh = $lastMonthConsokWhPerHoursRangeQuery[0]['Tranche06_18'] ?? 0;
        $lastConso18_00kWh = $lastMonthConsokWhPerHoursRangeQuery[0]['Tranche18_00'] ?? 0;

        $currentConso00_06kWhProgress = floatval($lastConso00_06kWh) > 0 ? (floatval($currentConso00_06kWh) - floatval($lastConso00_06kWh)) / floatval($lastConso00_06kWh) : 'INF';
        $currentConso06_18kWhProgress = floatval($lastConso06_18kWh) > 0 ? (floatval($currentConso06_18kWh) - floatval($lastConso06_18kWh)) / floatval($lastConso06_18kWh) : 'INF';
        $currentConso18_00kWhProgress = floatval($lastConso18_00kWh) > 0 ? (floatval($currentConso18_00kWh) - floatval($lastConso18_00kWh)) / floatval($lastConso18_00kWh) : 'INF';

        return array(
            //'consoMoy'     => $this->getAverageConsumption(10, $this->currentMonthStringDate),
            'consoMoy'             => $consoMoy,
            'consoMoyProgress'     => $consoMoyProgress,
            'variation'            => $this->getVariation(),
            '+forteConso'          => $strPlusForteConso,
            '+faibleConso'         => $strPlusFaibleConso,
            'Talon'                => $strTalon,
            'Pic'                  => $strPic,
            'conso-00-06'          => $currentConso00_06kWh,
            'conso-00-06Progress'  => $currentConso00_06kWhProgress,
            'conso-06-18'          => $currentConso06_18kWh,
            'conso-06-18Progress'  => $currentConso06_18kWhProgress,
            'conso-18-00'          => $currentConso18_00kWh,
            'conso-18-00Progress'  => $currentConso18_00kWhProgress,
        );
    }

    public function getMonthByMonthDataTableForCurrentYear()
    {
        $consoMonthByMonthForCurrentYearQuery = $this->manager->createQuery("SELECT SUBSTRING(d.dateTime,1,7) AS dt, SUM(d.ea) AS EA, d.dateTime AS day_
                                            FROM App\Entity\LoadEnergyData d
                                            JOIN d.smartMod sm
                                            WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='GRID')
                                            AND d.dateTime LIKE :currentYear
                                            GROUP BY dt
                                            ORDER BY dt ASC
                                            ")
            ->setParameters(array(
                'currentYear'  => date('Y') . '%',
                'siteId'       => $this->site->getId()
            ))
            ->getResult();
        //dump($consoMonthByMonthForCurrentYearQuery);
        $tab = [];
        foreach ($consoMonthByMonthForCurrentYearQuery as $monthData) {
            $tab[$monthData['day_']->format('F')] = [
                //'EA'       => floatval($monthData['EA']),
                'Date'     => $monthData['day_'],
                'nbDay'    => 0,
                'consoMoy' => 0,
            ];
        }
        //dump($tab);

        $nbDayMonthByMonthForCurrentYearQuery = $this->manager->createQuery("SELECT SUBSTRING(d.dateTime,1,10) AS dt, d.dateTime AS day_
                                            FROM App\Entity\LoadEnergyData d
                                            JOIN d.smartMod sm
                                            WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='GRID')
                                            AND d.dateTime LIKE :currentYear
                                            GROUP BY dt
                                            ORDER BY dt ASC
                                            ")
            ->setParameters(array(
                'currentYear'  => date('Y') . '%',
                'siteId'       => $this->site->getId()
            ))
            ->getResult();
        //dump($nbDayMonthByMonthForCurrentYearQuery);
        foreach ($nbDayMonthByMonthForCurrentYearQuery as $dayByMonth) {
            $tab[$dayByMonth['day_']->format('F')]['nbDay']++;
        }
        //dump($tab);

        $monthByMonthDataQuery = $this->manager->createQuery("SELECT SUBSTRING(d.dateTime,1,7) AS dt, SUM(d.ea) AS EA, SUM(d.ea)*:kgCO2 AS kgCO2, MAX(d.pmoy) AS Pmax,
                                            MIN(NULLIF(d.pmoy, 0)) AS talon, d.dateTime AS day_ 
                                            FROM App\Entity\LoadEnergyData d
                                            JOIN d.smartMod sm
                                            WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='GRID')
                                            AND d.dateTime LIKE :currentYear
                                            GROUP BY dt
                                            ORDER BY dt ASC")
            ->setParameters(array(
                'kgCO2'        => $this->CO2PerkWh,
                'currentYear'  => date('Y') . '%',
                'siteId'       => $this->site->getId()
            ))
            ->getResult();
        //dump($monthByMonthDataQuery);
        $monthByMonthData = array_merge($tab);
        //dump($monthByMonthData);
        foreach ($monthByMonthDataQuery as $monthData) {
            $monthByMonthData[$monthData['day_']->format('F')]['EA'] = floatval($monthData['EA']);
            $monthByMonthData[$monthData['day_']->format('F')]['kgCO2'] = floatval($monthData['kgCO2']);
            $monthByMonthData[$monthData['day_']->format('F')]['Pmax'] = floatval($monthData['Pmax']);
            $monthByMonthData[$monthData['day_']->format('F')]['talon'] = floatval($monthData['talon']);
            $monthByMonthData[$monthData['day_']->format('F')]['consoMoy'] = $monthByMonthData[$monthData['day_']->format('F')]['nbDay'] > 0 ? $monthByMonthData[$monthData['day_']->format('F')]['EA'] / $monthByMonthData[$monthData['day_']->format('F')]['nbDay'] : 0;;
        }
        //dump($monthByMonthData);

        //dump($monthByMonthData);
        return $monthByMonthData;
    }

    public function getLoadChartDataForCurrentMonth()
    {
        $dateP = [];
        $FP    = [];
        $kW    = [];
        $WG    = [];
        $loadChartData = [];

        if ($this->site->getSubscription() === 'MT') { //Pour les Sites abonnés en MT
            $loadChartData = $this->manager->createQuery("SELECT DISTINCT d.dateTime AS jour, d.pmoy/:power_unit AS kW, 
                                            SUM(d.ea)/SQRT( (SUM(d.ea)*SUM(d.ea)) + (SUM(d.er)*SUM(d.er)) ) AS PF,
                                            d.workingGenset AS supply
                                            FROM App\Entity\LoadEnergyData d
                                            JOIN d.smartMod sm
                                            WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='Load Meter' AND stm.levelZone=1)
                                            AND d.dateTime LIKE :currentMonth
                                            GROUP BY jour
                                            ORDER BY jour ASC")
                ->setParameters(array(
                    'currentMonth'  => $this->currentMonthStringDate,
                    'siteId'        => $this->site->getId(),
                    'power_unit'    => $this->power_unit,
                ))
                ->getResult();
            if ($this->site->getHasOneSmartMod() == true) { //Site à smartMeter GRID et FUEL en un
                //dump($dayByDayConsoData);

            } else { //Site à smartMeter GRID et FUEL séparé

            }
        } else { //Pour les Sites abonnés en BT
            $loadChartData = $this->manager->createQuery("SELECT DISTINCT d.dateTime AS jour, d.pmoy/:power_unit AS kW,
                                                SUM(d.ea)/SQRT( (SUM(d.ea)*SUM(d.ea)) + (SUM(d.er)*SUM(d.er)) ) AS PF
                                                FROM App\Entity\LoadEnergyData d
                                                JOIN d.smartMod sm
                                                WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='GRID')
                                                AND d.dateTime LIKE :currentMonth
                                                ORDER BY jour ASC")
                ->setParameters(array(
                    'currentMonth'  => $this->currentMonthStringDate,
                    'siteId'    => $this->site->getId(),
                    'power_unit'    => $this->power_unit,
                ))
                ->getResult();
        }
        //dump($loadChartData);

        foreach ($loadChartData as $d) {
            $dateP[] = $d['jour']->format('Y-m-d H:i:s');
            $kW[]    = floatval(number_format((float) $d['kW'], 2, '.', ''));
            $FP[]    = array_key_exists('PF', $d) ? floatval(number_format((float) $d['PF'], 2, '.', '')) : 0;
            $WG[]    = array_key_exists('supply', $d) ? $d['supply'] : 0;
        }
        //dump($kW);
        $Pnow  = count($kW) > 0 ? end($kW) : 0;
        $FPnow = count($FP) > 0 ? end($FP) : 0;
        //dump($Pnow);
        $isGenset = count($WG) > 0 ? end($WG) : 0;
        //dump($isGenset);
        return array(
            "dateP"    => $dateP,
            "kW"       => $kW,
            "Pnow"     => $Pnow,
            "FPnow"    => $FPnow,
            "isGenset" => $isGenset,
        );
    }

    public function updateHistoGraphs()
    {
        return array(
            'loadChart_Data'  => $this->getLoadChartDataForDateRange(),
            'consoChart_Data' => $this->getConsumptionkWhChartDataForDateRange(),
        );
    }

    public function getLoadChartDataForDateRange()
    {
        $loadChartData = $this->manager->createQuery("SELECT DISTINCT d.dateTime AS dt, d.pmoy AS kW
                                            FROM App\Entity\LoadEnergyData d
                                            JOIN d.smartMod sm
                                            WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='GRID')
                                            AND d.dateTime BETWEEN :startDate AND :endDate
                                            GROUP BY dt
                                            ORDER BY dt ASC")
            ->setParameters(array(
                'startDate'  => $this->startDate->format('Y-m-d H:i:s'),
                'endDate'    => $this->endDate->format('Y-m-d H:i:s'),
                'siteId'     => $this->site->getId()
            ))
            ->getResult();
        //dump($loadChartData);
        $dateP = [];
        $kW = [];

        foreach ($loadChartData as $d) {
            $dateP[] = $d['dt']->format('Y-m-d H:i:s');
            $kW[] = floatval(number_format((float) $d['kW'], 2, '.', ''));
        }

        return array(
            "dateP"  => $dateP,
            "kW"     => $kW,
            //"Pnow"   => end($kW) ?? 0
        );
    }

    public function getConsumptionkWhChartDataForDateRange()
    {
        $daterangeConsoData = $this->manager->createQuery("SELECT DISTINCT SUBSTRING(d.dateTime,1,10) AS dt, SUM(d.ea) AS EA, SUM(d.ea)*:kgCO2 AS kgCO2
                                            FROM App\Entity\LoadEnergyData d
                                            JOIN d.smartMod sm
                                            WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='GRID')
                                            AND d.dateTime BETWEEN :startDate AND :endDate
                                            GROUP BY dt
                                            ORDER BY dt ASC")
            ->setParameters(array(
                'kgCO2'      => $this->CO2PerkWh,
                'startDate'  => $this->startDate->format('Y-m-d H:i:s'),
                'endDate'    => $this->endDate->format('Y-m-d H:i:s'),
                'siteId'     => $this->site->getId()
            ))
            ->getResult();
        //dump($daterangeConsoData);

        $dateConso = [];
        $kWh = [];
        $kgCO2 = [];
        foreach ($daterangeConsoData as $d) {
            $dateConso[] = $d['dt'];
            $kWh[] = floatval(number_format((float) $d['EA'], 2, '.', ''));
            $kgCO2[] = floatval(number_format((float) $d['kgCO2'], 2, '.', ''));
        }

        return array(
            "dateConso" => $dateConso,
            "kWh"       => $kWh,
            "kgCO2"     => $kgCO2
        );
    }

    public function getLastDatetimeData()
    {
        $lastDatetimeData = [];

        if ($this->site->getSubscription() === 'MT') { //Pour les Sites abonnés en MT
            if ($this->site->getHasOneSmartMod() == true) { //Site à smartMeter GRID et FUEL en un
                $lastDatetimeData = $this->manager->createQuery("SELECT MAX(d.dateTime) AS lastDate
                                                    FROM App\Entity\LoadEnergyData d
                                                    JOIN d.smartMod sm
                                                    WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='GRID_FUEL')
                                                    AND d.dateTime LIKE :currentMonth
                                                    ")
                    ->setParameters(array(
                        'currentMonth'  => $this->currentMonthStringDate,
                        'siteId'        => $this->site->getId()
                    ))
                    ->getResult();
            } else { //Site à smartMeter GRID et FUEL séparé

            }
        } else { //Pour les Sites abonnés en BT
            $lastDatetimeData = $this->manager->createQuery("SELECT MAX(d.dateTime) AS lastDate
                                                FROM App\Entity\LoadEnergyData d
                                                JOIN d.smartMod sm
                                                WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='GRID')
                                                AND d.dateTime LIKE :currentMonth
                                                ")
                ->setParameters(array(
                    'currentMonth'  => $this->currentMonthStringDate,
                    'siteId'        => $this->site->getId()
                ))
                ->getResult();
        }
        //dump($lastDatetimeData);
        return count($lastDatetimeData) > 0 ? ($lastDatetimeData[0]['lastDate'] !== null ? $lastDatetimeData[0]['lastDate'] : '') : '';
    }

    public function getAverageConsumption(int $length = 10, $strLike = '')
    {
        $averageConsumption = [];
        //Longueur de la sous-chaîne de date utilisé pour grouper les données dans la DQL
        //2021-09-28

        //Chaîne utilisée dans le filtre LIKE de la DQL 
        $strLike_ = $strLike ?? $this->currentMonthStringDate;
        $averageConsumption = $this->manager->createQuery("SELECT SUBSTRING(d.dateTime,1,:length_) AS dt, SUM(d.ea) AS EA
                                            FROM App\Entity\LoadEnergyData d
                                            JOIN d.smartMod sm
                                            WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='GRID')
                                            AND d.dateTime LIKE :str_
                                            GROUP BY dt
                                            ORDER BY dt ASC")
            ->setParameters(array(
                'str_'      => $strLike_,
                'length_'   => $length,
                'siteId'    => $this->site->getId()
            ))
            ->getResult();

        /*$averageConsumption = $this->manager->createQuery("SELECT SUBSTRING(d.dateTime,1,7) AS dt, SUM(d.ea) AS EA
                                            FROM App\Entity\LoadEnergyData d
                                            JOIN d.smartMod sm
                                            WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='GRID')
                                            AND d.dateTime LIKE :currentYear
                                            GROUP BY dt
                                            ORDER BY dt ASC")
                    ->setParameters(array(
                        'currentYear'  => date('Y'),
                        'siteId'    => $this->site->getId()
                    ))
                    ->getResult();*/

        // dump($averageConsumption);
        // dump(count($averageConsumption));
        $consoMoy = 0;
        if (count($averageConsumption) > 0) {
            foreach ($averageConsumption as $average) {
                $consoMoy += floatval($average['EA']);
            }

            $consoMoy = $consoMoy / (count($averageConsumption) * 1.0);
            // dump($consoMoy);
            //$consoMoy = number_format((float) $consoMoy, 2, '.', ' ');

        }

        return $consoMoy;
    }

    public function getAverageConsumptionWithLimit(int $length = 10, $strLike = '', $strSupDateTime = '')
    {
        $averageConsumption = [];
        //Longueur de la sous-chaîne de date utilisé pour grouper les données dans la DQL
        //2021-09-28

        //Chaîne utilisée dans le filtre LIKE de la DQL 
        $strLike_ = $strLike ?? $this->currentMonthStringDate;
        if ($this->site->getSubscription() === 'MT') { //Pour les Sites abonnés en MT
            if ($this->site->getHasOneSmartMod() == true) { //Site à smartMeter GRID et FUEL en un
                $averageConsumption = $this->manager->createQuery("SELECT SUBSTRING(d.dateTime,1,:length_) AS dt, SUM(d.ea) AS EA
                                                    FROM App\Entity\LoadEnergyData d
                                                    JOIN d.smartMod sm
                                                    WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='GRID_FUEL')
                                                    AND d.dateTime LIKE :strLike_
                                                    AND d.dateTime <= :strSupDateTime
                                                    GROUP BY dt
                                                    ORDER BY dt ASC")
                    ->setParameters(array(
                        'strLike_'        => $strLike_,
                        'strSupDateTime'  => $strSupDateTime,
                        'length_'         => $length,
                        'siteId'          => $this->site->getId()
                    ))
                    ->getResult();

                /*$averageConsumption = $this->manager->createQuery("SELECT SUBSTRING(d.dateTime,1,7) AS dt, SUM(d.ea) AS EA
                                                    FROM App\Entity\LoadEnergyData d
                                                    JOIN d.smartMod sm
                                                    WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='GRID')
                                                    AND d.dateTime LIKE :currentYear
                                                    GROUP BY dt
                                                    ORDER BY dt ASC")
                            ->setParameters(array(
                                'currentYear'  => date('Y'),
                                'siteId'    => $this->site->getId()
                            ))
                            ->getResult();*/

                // dump($averageConsumption);
                // dump(count($averageConsumption));

            } else { //Site à smartMeter GRID et FUEL séparé

            }
        } else {
            $averageConsumption = $this->manager->createQuery("SELECT SUBSTRING(d.dateTime,1,:length_) AS dt, SUM(d.ea) AS EA
                                                FROM App\Entity\LoadEnergyData d
                                                JOIN d.smartMod sm
                                                WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='GRID')
                                                AND d.dateTime LIKE :strLike_
                                                AND d.dateTime <= :strSupDateTime
                                                GROUP BY dt
                                                ORDER BY dt ASC")
                ->setParameters(array(
                    'strLike_'        => $strLike_,
                    'strSupDateTime'  => $strSupDateTime,
                    'length_'         => $length,
                    'siteId'          => $this->site->getId()
                ))
                ->getResult();

            /*$averageConsumption = $this->manager->createQuery("SELECT SUBSTRING(d.dateTime,1,7) AS dt, SUM(d.ea) AS EA
                                                FROM App\Entity\LoadEnergyData d
                                                JOIN d.smartMod sm
                                                WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='GRID')
                                                AND d.dateTime LIKE :currentYear
                                                GROUP BY dt
                                                ORDER BY dt ASC")
                        ->setParameters(array(
                            'currentYear'  => date('Y'),
                            'siteId'    => $this->site->getId()
                        ))
                        ->getResult();*/

            // dump($averageConsumption);
            // dump(count($averageConsumption));

        }
        $consoMoy = 0;
        if (count($averageConsumption) > 0) {
            foreach ($averageConsumption as $average) {
                $consoMoy += floatval($average['EA']);
            }

            $consoMoy = $consoMoy / (count($averageConsumption) * 1.0);
            // dump($consoMoy);
            //$consoMoy = number_format((float) $consoMoy, 2, '.', ' ');

        }

        return $consoMoy;
    }

    private function getConsumptionXAF($consokWh_ = 0.0, $arrConso = [])
    {
        if ($this->site->getSubscription() === 'BT') {
            $tranches = [
                'Residential'     => $this->tranchesResidential,
                'Non Residential' => $this->tranchesNonResidential,
            ];
            $consokWh = floatval($consokWh_);
            $siteTranches = $tranches[$this->site->getSubscriptionUsage()];
            //dump($siteTranches);
            $consoXAF = 0;
            if ($consokWh <= 110) {
                //dump($consokWh);
                //dump($consokWh * $siteTranches['0-110']);
                $consoXAF = $consokWh * $siteTranches['0-110'];
            } else if ($consokWh >= 111 && $consokWh <= 400) $consoXAF = $consokWh * $siteTranches['111-400'];
            else if ($consokWh >= 401 && $consokWh <= 800 && $this->site->getSubscriptionUsage() === 'Residential') $consoXAF = $consokWh * $siteTranches['401-800'];
            else if ($consokWh >= 401 && $this->site->getSubscriptionUsage() === 'Non Residential') $consoXAF = $consokWh * $siteTranches['401+'];
            else if ($consokWh > 800 && $this->site->getSubscriptionUsage() === 'Residential') $consoXAF = $consokWh * $siteTranches['800+'];

            return $consoXAF;
        } else if ($this->site->getSubscription() === 'MT') {
            // dump($arrConso);
            // return 1000.0;
            $NHU_Psous_HP = intval($arrConso['EAHP_Hours']) / 60.0; //Conversion du nombre d'heure d'utilisation en heure
            if ($NHU_Psous_HP < 200) $tarifHP = $this->NHU_Psous['0-200'];
            else if ($NHU_Psous_HP > 200 && $NHU_Psous_HP < 400) $tarifHP = $this->NHU_Psous['201-400'];
            else if ($NHU_Psous_HP > 400) $tarifHP = $this->NHU_Psous['401+'];

            if ($this->site->getPowerSubscribed() < 1000) { //Si Psous < 1MW
                return ($arrConso['EAHP'] * $tarifHP) + ($arrConso['EAP'] * 85);
            } else { //Sinon Si Psous >= 1MW
                return ($arrConso['EAHP'] + $arrConso['EAP']) * 99;
            }
        }
    }

    public function getVariation()
    {
        if ($this->site->getSubscription() === 'MT') { //Pour les Sites abonnés en MT
            if ($this->site->getHasOneSmartMod() == true) { //Site à smartMeter GRID et FUEL en un
                $consumptionQuery = $this->manager->createQuery("SELECT SUBSTRING(d.dateTime,1,10) AS dt, SUM(d.ea) AS EA
                                                FROM App\Entity\LoadEnergyData d
                                                JOIN d.smartMod sm
                                                WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='GRID_FUEL')
                                                AND d.dateTime LIKE :currentMonth
                                                GROUP BY dt
                                                ORDER BY dt ASC")
                    ->setParameters(array(
                        'currentMonth'  => $this->currentMonthStringDate,
                        'siteId'    => $this->site->getId()
                    ))
                    ->getResult();
                // dump($consumptionQuery);
                // dump(count($consumptionQuery));
            } else { //Site à smartMeter GRID et FUEL séparé

            }
        } else { //Pour les Sites abonnés en BT
            $consumptionQuery = $this->manager->createQuery("SELECT SUBSTRING(d.dateTime,1,10) AS dt, SUM(d.ea) AS EA
                                                FROM App\Entity\LoadEnergyData d
                                                JOIN d.smartMod sm
                                                WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='GRID')
                                                AND d.dateTime LIKE :currentMonth
                                                GROUP BY dt
                                                ORDER BY dt ASC")
                ->setParameters(array(
                    'currentMonth'  => $this->currentMonthStringDate,
                    'siteId'    => $this->site->getId()
                ))
                ->getResult();
            // dump($consumptionQuery);
            // dump(count($consumptionQuery));

        }
        $variation = 0;
        $moyenne = 0;
        if (count($consumptionQuery) > 0) {
            $arrayConsoDayByDay = [];
            foreach ($consumptionQuery as $conso) {
                $arrayConsoDayByDay[] = floatval($conso['EA']);
            }
            // dump($arrayConsoDayByDay);
            $moyenne = array_sum($arrayConsoDayByDay) / (count($arrayConsoDayByDay) * 1.0);
            $variation = $this->ecart_type($arrayConsoDayByDay);
        }

        return $moyenne != 0 ? ($variation / $moyenne) * 100 : 0.0;
    }

    private function ecart_type(array $donnees)
    {
        //0 - Nombre d’éléments dans le tableau
        $population = count($donnees);
        // dump($donnees);
        // dump('population = ' . $population);
        if ($population != 0) {
            //1 - somme du tableau
            $somme_tableau = array_sum($donnees);
            // dump('somme_tableau = ' . $somme_tableau);
            //2 - Calcul de la moyenne
            $moyenne = ($somme_tableau * 1.0) / $population;
            // dump('moyenne = ' . $moyenne);
            //3 - écart pour chaque valeur
            $ecart = [];
            for ($i = 0; $i < $population; $i++) {
                //écart entre la valeur et la moyenne
                $ecart_donnee = $donnees[$i] - $moyenne;
                // dump('ecart_donnee ' . $i . ' = ' . $ecart_donnee);
                //carré de l'écart
                $ecart_donnee_carre = pow($ecart_donnee, 2);
                // dump('ecart_donnee_carre ' . $i . ' = ' . $ecart_donnee_carre);
                //Insertion dans le tableau
                array_push($ecart, $ecart_donnee_carre);
            }
            // dump($ecart);
            //4 - somme des écarts
            $somme_ecart = array_sum($ecart);
            // dump('somme_ecart = ' . $somme_ecart);
            //5 - division de la somme des écarts par la population
            $division = $somme_ecart / $population;
            // dump('division = ' . $division);
            //6 - racine carrée de la division
            $ecart_type = sqrt($division);
        } else {
            $ecart_type = 0; //"Le tableau est vide";
        }
        // dump('ecart_type = ' . $ecart_type);
        //7 - renvoi du résultat
        return $ecart_type;
    }

    public function getGridBillData()
    { //Fonction à appeler après la méthode getCurrentMonthkWhConsumption()
        if ($this->site->getSubscription() === 'MT') {

            $primeFixe   = $this->site->getPowerSubscribed() * 3700;
            $totalAmount = $this->amountHT + $primeFixe;

            return array(
                'EAP'          => number_format((float) ($this->EAP), 2, '.', ' '),
                'EAHP'         => number_format((float) ($this->EAHP), 2, '.', ' '),
                'EA'           => number_format((float) ($this->EA), 2, '.', ' '),
                'ERP'          => number_format((float) ($this->ERP), 2, '.', ' '),
                'ERHP'         => number_format((float) ($this->ERHP), 2, '.', ' '),
                'ER'           => number_format((float) ($this->ER), 2, '.', ' '),
                'FPP'          => number_format((float) ($this->FPP), 2, '.', ' '),
                'FPHP'         => number_format((float) ($this->FPHP), 2, '.', ' '),
                'FP'           => number_format((float) ($this->FP), 2, '.', ' '),
                'amountHT'     => number_format((float) ($this->amountHT), 0, '.', ' '),
                'primeFixe'    => number_format((float) ($primeFixe), 0, '.', ' '),
                'totalAmount'  => number_format((float) ($totalAmount), 0, '.', ' '),
                'currentNbDepassement'    => $this->currentNbDepassement,
            );
        }
    }

    public function getFuelBillData()
    { //Fonction à appeler après la méthode getCurrentMonthkWhConsumption()
        if ($this->site->getSubscription() === 'MT') { //Pour les Sites abonnés en MT
            if ($this->site->getHasOneSmartMod() == true) { //Site à smartMeter GRID et FUEL en un
                $smartMods = $this->manager->getRepository('App:SmartMod')->findBy(['site' => $this->site, 'modType' => 'GRID_FUEL']);
                $smartMod  = $smartMods[0];
                //dump($smartMod);
                $consoFuelQuery = $this->manager->createQuery("SELECT SUM(CASE 
                                                WHEN (d.smoy/:genpower) >= 0 AND (d.smoy/:genpower) <= 0.25 THEN (:rate_25)
                                                WHEN (d.smoy/:genpower) > 0.25 AND (d.smoy/:genpower) <= 0.5 THEN (:rate_50)
                                                WHEN (d.smoy/:genpower) > 0.5 AND (d.smoy/:genpower) <= 0.75 THEN (:rate_75)
                                                WHEN (d.smoy/:genpower) > 0.75 AND (d.smoy/:genpower) <= 1 THEN (:rate_100)
                                                ELSE 0
                                            END) AS FUEL
                                            FROM App\Entity\LoadEnergyData d
                                            JOIN d.smartMod sm
                                            WHERE sm.id = :smId
                                            AND d.dateTime LIKE :currentMonth
                                            AND d.workingGenset = 1
                                            ")
                    ->setParameters(array(
                        'currentMonth' => $this->currentMonthStringDate,
                        'smId'         => $smartMod->getId(),
                        'genpower'     => $smartMod->getPower() * $this->power_unit,
                        'rate_25'      => $smartMod->getConsoFuelRate()['rate-25'] / 12,
                        'rate_50'      => $smartMod->getConsoFuelRate()['rate-50'] / 12,
                        'rate_75'      => $smartMod->getConsoFuelRate()['rate-75'] / 12,
                        'rate_100'     => $smartMod->getConsoFuelRate()['rate-100'] / 12,
                    ))
                    ->getResult();

                //dump($consoFuelQuery);
                $currentConsoFuel = count($consoFuelQuery) > 0 ? $consoFuelQuery[0]['FUEL'] ?? 0.0 : 0.0;
                $currentConsoFuel = floatval($currentConsoFuel);
                $currentConsoFuelAmount = $currentConsoFuel * $smartMod->getFuelPrice();

                $DUG_hour = floor($this->DUG);
                $DUG_mins = ($this->DUG - $DUG_hour) * 60;
                $DUG_mins = number_format((float) ($DUG_mins), 0, '.', ' ');
                return array(
                    'DUG'                    => number_format((float) ($DUG_hour), 0, '.', ' ') . ':' . $DUG_mins,
                    'TUG'                    => number_format((float) ($this->TUG * 100), 1, '.', ' '),
                    'EA_prod'                => number_format((float) ($this->currentConsokWh * $this->TUG), 2, '.', ' '),
                    'currentConsoFuel'       => number_format((float) ($currentConsoFuel), 1, '.', ' '),
                    'currentConsoFuelAmount' => number_format((float) ($currentConsoFuelAmount), 0, '.', ' '),
                );
            } else { //Site à smartMeter GRID et FUEL séparé

            }
        }
    }
    /**
     * Get the value of site
     *
     * @return Site|null
     */
    public function getSite(): ?Site
    {
        return $this->site;
    }

    /**
     * Set the value of site
     *
     * @return  self
     */
    public function setSite(Site $site)
    {
        $this->site = $site;

        return $this;
    }

    /**
     * Get prix du kWh en F CFA
     *
     * @return  float
     */
    public function getKWhPrice()
    {
        return $this->kWhPrice;
    }

    /**
     * Set prix du kWh en F CFA
     *
     * @param  float  $kWhPrice  Prix du kWh en F CFA
     *
     * @return  self
     */
    public function setKWhPrice(float $kWhPrice)
    {
        $this->kWhPrice = $kWhPrice;

        return $this;
    }

    /**
     * Get quantité de gaz à effet de serre(en kgCO2) émis par kWh
     *
     * @return  float
     */
    public function getCO2PerkWh()
    {
        return $this->CO2PerkWh;
    }

    /**
     * Set quantité de gaz à effet de serre(en kgCO2) émis par kWh
     *
     * @param  float  $CO2PerkWh  Quantité de gaz à effet de serre(en kgCO2) émis par kWh
     *
     * @return  self
     */
    public function setCO2PerkWh(float $CO2PerkWh)
    {
        $this->CO2PerkWh = $CO2PerkWh;

        return $this;
    }

    /**
     * Get date de début de la fenêtre de date choisie par l'utilisateur
     *
     * @return  DateTime
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * Set date de début de la fenêtre de date choisie par l'utilisateur
     *
     * @param  DateTime  $startDate  Date de début de la fenêtre de date choisie par l'utilisateur
     *
     * @return  self
     */
    public function setStartDate(DateTime $startDate)
    {
        $this->startDate = $startDate;

        return $this;
    }

    /**
     * Get date de fin de la fenêtre de date choisie par l'utilisateur
     *
     * @return  DateTime
     */
    public function getEndDate()
    {
        return $this->endDate;
    }

    /**
     * Set date de fin de la fenêtre de date choisie par l'utilisateur
     *
     * @param  DateTime  $endDate  Date de fin de la fenêtre de date choisie par l'utilisateur
     *
     * @return  self
     */
    public function setEndDate(DateTime $endDate)
    {
        $this->endDate = $endDate;

        return $this;
    }

    /**
     * Get multiple de Watt
     *
     * @return  integer
     */
    public function getPower_unit()
    {
        return $this->power_unit;
    }

    /**
     * Set multiple de Watt
     *
     * @param  integer  $power_unit  Multiple de Watt
     *
     * @return  self
     */
    public function setPower_unit($power_unit)
    {
        $this->power_unit = $power_unit;

        return $this;
    }
}
