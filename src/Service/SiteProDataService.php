<?php

namespace App\Service;

use DateTime;
use DatePeriod;
use DateInterval;
use App\Entity\Site;
use App\Entity\SmartMod;
use Doctrine\ORM\EntityManagerInterface;

class SiteProDataService
{
    /**
     * Prix du kWh en F CFA
     *
     * @var float
     */
    private $kWhPrice = 99;

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

    private $gensetModService;

    /**
     * Module GRID du site
     *
     * @var SmartMod
     */
    private $gridMod;

    /**
     * Module Load Site
     *
     * @var SmartMod
     */
    private $loadSiteMod;

    /**
     * Module GENSET
     *
     * @var SmartMod
     */
    private $gensetMod;

    /**
     * Interval de temps d'envoi des données du module GRID
     *
     * @var float
     */
    private $gridIntervalTime = 5.0/60.0;

    /**
     * Interval de temps d'envoi des données du module GENSET
     *
     * @var float
     */
    private $gensetIntervalTime = 5.0/60.0;

    /**
     * Interval de temps d'envoi des données du module Load Site
     *
     * @var float
     */
    private $loadSiteIntervalTime = 5.0/60.0;

    private $currentMonthStringDate = '';

    public function __construct(EntityManagerInterface $manager, GensetModService $gensetModService)
    {
        $this->manager                = $manager;
        $this->gensetModService       = $gensetModService;
        $this->currentMonthStringDate = date('Y-m') . '%';
    }

    public function getOverviewData($onlySrc = false)
    {
        $lastStartDate = new DateTime($this->startDate->format('Y-m-d H:i:s'));
        $lastStartDate->sub(new DateInterval('P1M'));

        $lastEndDate = new DateTime($this->endDate->format('Y-m-d H:i:s'));
        $lastEndDate->sub(new DateInterval('P1M'));

        // ========= Détermination de la longueur de la datetime =========
        $length = 10; //Si endDate > startDate => regoupement des données par jour de la fenêtre de date
        if ($this->endDate->format('Y-m-d') == $this->startDate->format('Y-m-d')) $length = 13; //Si endDate == startDate => regoupement des données par heure du jour choisi

        $gridData = [
            'EA'    => 0.0,
            'ER'    => 0.0,
            'FP'    => 0.0,
            'Pmax'    => 0.0,
            'contrib'   => 0.0,
            'chart'   => [
                'date'  => [],
                'data'  => []
            ],
        ];
        $totalER  = 0.0;
        $kW       = [];
        $kgCO2    = 0.0;
        $totalKWh = 0.0;

        // ============== GRID data ==============
        $gridDataQuery = $this->manager->createQuery("SELECT SUBSTRING(d.dateTime,1,:length_) AS jour, MAX(d.pmax) AS Pmax, SUM(d.pmoy)*:time AS EA, SUM(d.qmoy)*:time AS ER, SUM(d.depassement) AS Depassement
                                                FROM App\Entity\LoadEnergyData d
                                                JOIN d.smartMod sm
                                                WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='GRID')
                                                AND d.dateTime BETWEEN :startDate AND :endDate
                                                GROUP BY jour
                                                ORDER BY jour ASC")
            ->setParameters(array(
                'length_'      => $length,
                'time'         => $this->gridIntervalTime,
                'startDate'    => $this->startDate->format('Y-m-d H:i:s'),
                'endDate'      => $this->endDate->format('Y-m-d H:i:s'),
                'siteId'       => $this->site->getId()
            ))
            ->getResult();
        //dump($gridDataQuery);
        $totalGridKWh = 0.0;
        $totalGridER  = 0.0;
        $gridFP       = 0.0;
        $gridDate     = [];
        $gridkWh      = [];
        $gridPmax     = 0.0;
        $gridNbDepassement = 0;

        foreach ($gridDataQuery as $d) {
            $totalGridKWh += floatval($d['EA']);
            $totalGridER  += floatval($d['ER']);
            $gridDate[]    = $d['jour'];
            $gridkWh[]     = floatval(number_format((float) $d['EA'], 2, '.', ''));
            $kW[]          = floatval(number_format((float) $d['Pmax'], 2, '.', ''));
            $gridNbDepassement  += intval($d['Depassement']);
        }
        
        if(count($kW) > 0) $gridPmax = max($kW);
        $totalKWh += $totalGridKWh;

        $denom = sqrt(($totalGridKWh * $totalGridKWh) + ($totalGridER * $totalGridER));
        if ($denom > 0) $gridFP = $totalGridKWh / $denom;
        $gridFP  = floatval(number_format((float) $gridFP, 2, '.', ''));

        $kgCO2         = $totalGridKWh * 0.4;
        $totalGridKWh  = floatval(number_format((float) $totalGridKWh, 2, '.', ''));
        $totalGridER   = floatval(number_format((float) $totalGridER, 2, '.', ''));

        $gridPowerDataQuery = $this->manager->createQuery("SELECT d.dateTime AS jour, d.pmoy/:power_unit AS Pmoy
                                                FROM App\Entity\LoadEnergyData d
                                                JOIN d.smartMod sm
                                                WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='GRID')
                                                AND d.dateTime BETWEEN :startDate AND :endDate
                                                ORDER BY jour ASC")
            ->setParameters(array(
                //'length_'      => $length,
                'startDate'    => $this->startDate->format('Y-m-d H:i:s'),
                'endDate'      => $this->endDate->format('Y-m-d H:i:s'),
                'siteId'       => $this->site->getId(),
                'power_unit'   => $this->power_unit
            ))
            ->getResult();
        // dump($gridPowerDataQuery);

        $gridkW     = [];
        $gridkWDate = [];
        foreach ($gridPowerDataQuery as $d) {
            $gridkWDate[] = $d['jour']->format('Y-m-d H:i:s');
            $gridkW[]     = floatval(number_format((float) $d['Pmoy'], 2, '.', ''));
        }
        
        //if (count($gridkW) > 0) $gridPmax = max($gridkW);
        // ============== GENSET data ==============
        //get Genset Data
        $gensetData = [];
        $hasgensetMod = false;


        if ($this->gensetMod) { //Si le site possède un gensetMod fictif(pour les real time data) ou non(genset data)
            $gensetData = $this->gensetModService->getGensetDataForSiteProDashBoard();

            //Si le site possède un gensetMod et le site est configuré coe ayant GRID+GENSET 
            if (!$this->site->getHasOneSmartMod()) $hasgensetMod = true;
        }
        //dump($gensetData);

        if ($hasgensetMod) {
            $totalKWh += floatval($gensetData['currentTEP']);
            $kgCO2 += floatval($gensetData['currentTEP']) * 0.75;
        }
        $kgCO2     = floatval(number_format((float) $kgCO2, 2, '.', ''));

        $contriGridKWh  = $totalKWh > 0 ? ($totalGridKWh * 100) / $totalKWh : 0.0;
        $contriGridKWh  = floatval(number_format((float) $contriGridKWh, 2, '.', ''));

        $contriGensetKWh = $hasgensetMod ? ($totalKWh > 0 ? (floatval($gensetData['currentTEP']) * 100) / $totalKWh : 0.0) : 0;
        $contriGensetKWh = floatval(number_format((float) $contriGensetKWh, 2, '.', ''));

        $gridData = array(
            "date"            => $gridDate,
            "kWh"             => $gridkWh,
            "dateP"           => $gridkWDate,
            "kW"              => $gridkW,
            "Pmax"            => $gridPmax,
            "totalKWh"        => $totalGridKWh,
            "totalER"         => $totalGridER,
            "contrib"         => $contriGridKWh,
            "FP"              => $gridFP,
            "NbDepassement"   => $gridNbDepassement
        );

        $gensetData["contriGensetKWh"] = $contriGensetKWh;

        if ($onlySrc) {
            return array(
                "kgCO2"           => $kgCO2,
                'gridData'        => $gridData,
                'gensetData'      => $gensetData,
                "contriGensetKWh" => $contriGensetKWh,
                'hasgensetMod'    => $hasgensetMod,
            );
        }
        // ============== LOAD data ==============
        $loadSiteData = $this->manager->createQuery("SELECT SUBSTRING(d.dateTime,1,:length_) AS jour, SUM(d.pmoy)*:time AS EA, SUM(d.qmoy)*:time AS ER, MAX(d.pmax) AS Pmax
                                                FROM App\Entity\LoadEnergyData d
                                                JOIN d.smartMod sm
                                                WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='Load Meter' AND stm.levelZone=1)
                                                AND d.dateTime BETWEEN :startDate AND :endDate
                                                GROUP BY jour
                                                ORDER BY jour ASC")
            ->setParameters(array(
                'length_'    => $length,
                'time'       => $this->loadSiteIntervalTime,
                'startDate'  => $this->startDate->format('Y-m-d H:i:s'),
                'endDate'    => $this->endDate->format('Y-m-d H:i:s'),
                'siteId'     => $this->site->getId()
            ))
            ->getResult();
        //dump($loadSiteData);
        $totalLoadSiteKWh = 0.0;
        $loadSiteFP       = 0.0;
        $loadSiteDate     = [];
        $loadSitekWh      = [];
        $loadSitePmax     = 0.0;
        $totalER          = 0.0;
        $denom            = 0.0;
        $kW               = [];

        foreach ($loadSiteData as $d) {
            $totalLoadSiteKWh += floatval($d['EA']);
            $totalER          += floatval($d['ER']);
            $loadSiteDate[]    = $d['jour'];
            $loadSitekWh[]     = floatval(number_format((float) $d['EA'], 2, '.', ''));
            $kW[]              = floatval(number_format((float) $d['Pmax'], 2, '.', ''));
        }

        if (count($kW) > 0) $loadSitePmax = max($kW);

        $denom = sqrt(($totalLoadSiteKWh * $totalLoadSiteKWh) + ($totalER * $totalER));
        if ($denom > 0.0) $loadSiteFP = $totalLoadSiteKWh / $denom;
        $loadSiteFP  = floatval(number_format((float) $loadSiteFP, 2, '.', ''));

        $totalLoadSiteKWh  = floatval(number_format((float) $totalLoadSiteKWh, 2, '.', ''));

        $loadSitePowerData = $this->manager->createQuery("SELECT d.dateTime AS jour, d.pmoy/:power_unit AS Pmoy
                                                FROM App\Entity\LoadEnergyData d
                                                JOIN d.smartMod sm
                                                WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='Load Meter' AND stm.levelZone=1)
                                                AND d.dateTime BETWEEN :startDate AND :endDate
                                                ORDER BY jour ASC")
            ->setParameters(array(
                //'length_'    => $length,
                'startDate'  => $this->startDate->format('Y-m-d H:i:s'),
                'endDate'    => $this->endDate->format('Y-m-d H:i:s'),
                'siteId'        => $this->site->getId(),
                'power_unit'    => $this->power_unit
            ))
            ->getResult();
        //dump($loadSitePowerData);
        $loadSiteDateP    = [];
        $kW               = [];

        foreach ($loadSitePowerData as $d) {
            $loadSiteDateP[]   = $d['jour']->format('Y-m-d H:i:s');
            $kW[]              = floatval(number_format((float) $d['Pmoy'], 2, '.', ''));
        }

        //if (count($kW) > 0) $loadSitePmax = max($kW);

        $loadSiteData = array(
            "date"         => $loadSiteDate,
            "kWh"          => $loadSitekWh,
            "dateP"        => $loadSiteDateP,
            "kW"           => $kW,
            "Pmax"         => $loadSitePmax,
            "totalKWh"     => $totalLoadSiteKWh,
            "FP"           => $loadSiteFP
        );

        return array(
            "kgCO2"           => $kgCO2,
            'loadSiteData'    => $loadSiteData,
            'gridData'        => $gridData,
            'gensetData'      => $gensetData,
            "contriGensetKWh" => $contriGensetKWh,
            'hasgensetMod'    => $hasgensetMod,
        );
    }

    public function getChartDataForDateRange()
    {
        $date        = [];
        $GridkWh     = [];
        $GensetkWh   = [];
        $SolarkWh    = [];
        $BattkWh     = [];
        $dayGridkWh     = [];
        $dayGensetkWh   = [];
        $daySolarkWh    = [];
        $dayBattkWh     = [];

        $totalGridkWh     = 0.0;
        $totalGensetkWh   = 0.0;
        $totalSolarkWh    = 0.0;
        $totalBattkWh     = 0.0;

        $period = new DatePeriod(
            new DateTime($this->startDate->format('Y-m-d')),
            new DateInterval('P1D'),
            new DateTime($this->endDate->format('Y-m-d'))
        );

        foreach ($period as $key => $value) {
            //dump($value->format('Y-m-d'));
            $date[]     = $value->format('Y-m-d');
            $dayGridkWh[$value->format('Y-m-d')]   = 0.0;
            $dayGensetkWh[$value->format('Y-m-d')] = 0.0;
            $daySolarkWh[$value->format('Y-m-d')]  = 0.0;
            $dayBattkWh[$value->format('Y-m-d')]   = 0.0;
        }

        $date[]     = $this->endDate->format('Y-m-d');
        $dayGridkWh[$this->endDate->format('Y-m-d')]   = 0.0;
        $dayGensetkWh[$this->endDate->format('Y-m-d')] = 0.0;
        $daySolarkWh[$this->endDate->format('Y-m-d')]  = 0.0;
        $dayBattkWh[$this->endDate->format('Y-m-d')]   = 0.0;

        // ========= Détermination de la longueur de la datetime =========
        $length = 10; //Si endDate > startDate => regoupement des données par jour de la fenêtre de date
        if ($this->endDate->format('Y-m-d') == $this->startDate->format('Y-m-d')) {
            $length = 13; //Si endDate == startDate => regoupement des données par heure du jour choisi
            $date        = [];
            $dayGridkWh     = [];
            $dayGensetkWh   = [];
            $daySolarkWh    = [];
            $dayBattkWh     = [];
            for ($h = 0; $h < 24; $h++) {
                $strHour = $h < 10 ? '0' . $h : $h;
                $date[]     = $this->endDate->format('Y-m-d') . ' ' . $strHour;
                $dayGridkWh[$this->endDate->format('Y-m-d') . ' ' . $strHour]   = 0.0;
                $dayGensetkWh[$this->endDate->format('Y-m-d') . ' ' . $strHour] = 0.0;
                $daySolarkWh[$this->endDate->format('Y-m-d') . ' ' . $strHour]  = 0.0;
                $dayBattkWh[$this->endDate->format('Y-m-d') . ' ' . $strHour]   = 0.0;
            }
        }

        $consoChartData = $this->manager->createQuery("SELECT DISTINCT SUBSTRING(d.dateTime,1,:length_) AS dt, SUM(d.pmoy)*:time AS kWh
                                            FROM App\Entity\LoadEnergyData d
                                            JOIN d.smartMod sm
                                            WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='GRID')
                                            AND d.dateTime BETWEEN :startDate AND :endDate
                                            GROUP BY dt
                                            ORDER BY dt ASC")
            ->setParameters(array(
                'length_'    => $length,
                'time'       => $this->gridIntervalTime,
                'startDate'  => $this->startDate->format('Y-m-d H:i:s'),
                'endDate'    => $this->endDate->format('Y-m-d H:i:s'),
                'siteId'     => $this->site->getId()
            ))
            ->getResult();
        // dump($consoChartData);

        foreach ($consoChartData as $d) {
            // $date[]     = $d['dt'];
            $dayGridkWh[$d['dt']] = floatval(number_format((float) $d['kWh'], 2, '.', ''));
            // $dayGensetkWh[$d['dt']] = 0.0;
            // $daySolarkWh[$d['dt']]  = 0.0;
            // $dayBattkWh[$d['dt']]   = 0.0;
            $totalGridkWh     += floatval($d['kWh']);
        }
        foreach ($dayGridkWh as $key => $value) {
            $GridkWh[] = $value;

            $SolarkWh[] = 0.0;
            $BattkWh[]  = 0.0;
        }

        $consoChartData = [];
        if($this->gensetMod->getSubType() === 'ModBus'){//Si le module GENSET est de type Modbus 
            $consoChartData = $this->manager->createQuery("SELECT DISTINCT SUBSTRING(d.dateTime,1,:length_) AS dt, 
                                                MAX(d.totalEnergy) - MIN(NULLIF(d.totalEnergy,0)) AS TEP
                                                FROM App\Entity\GensetData d
                                                JOIN d.smartMod sm
                                                WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='GENSET')
                                                AND d.dateTime BETWEEN :startDate AND :endDate
                                                GROUP BY dt
                                                ORDER BY dt ASC")
                ->setParameters(array(
                    'length_'    => $length,
                    'startDate'  => $this->startDate->format('Y-m-d H:i:s'),
                    'endDate'    => $this->endDate->format('Y-m-d H:i:s'),
                    'siteId'     => $this->site->getId()
                ))
                ->getResult();
        }
        else if(strpos($this->gensetMod->getSubType(), 'Inv') !== false ) { //Si le module GENSET est de type Inverter 
            $consoChartData = $this->manager->createQuery("SELECT DISTINCT SUBSTRING(d.dateTime,1,:length_) AS dt, 
                                                SUM(d.p)*:time AS TEP
                                                FROM App\Entity\GensetData d
                                                JOIN d.smartMod sm
                                                WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='GENSET')
                                                AND d.dateTime BETWEEN :startDate AND :endDate
                                                GROUP BY dt
                                                ORDER BY dt ASC")
                ->setParameters(array(
                    'length_'    => $length,
                    'time'       => $this->gensetIntervalTime,
                    'startDate'  => $this->startDate->format('Y-m-d H:i:s'),
                    'endDate'    => $this->endDate->format('Y-m-d H:i:s'),
                    'siteId'     => $this->site->getId()
                ))
                ->getResult();
        }
        
        //dump($consoChartData);
        foreach ($consoChartData as $d) {
            //$date[]     = $d['dt'];
            //$GridkWh[]  = floatval(number_format((float) $d['kWh'], 2, '.', ''));
            $dayGensetkWh[$d['dt']]  = floatval(number_format((float) $d['TEP'], 2, '.', ''));
            //$totalGridkWh     += floatval($d['kWh']);
            $totalGensetkWh   += floatval($d['TEP']);
        }

        foreach ($dayGensetkWh as $key => $value) {
            $GensetkWh[] = $value;
        }

        $totalkWh        = $totalGridkWh + $totalGensetkWh;
        $totalkWh        = floatval(number_format((float) $totalkWh, 2, '.', ''));
        $totalGridkWh    = floatval(number_format((float) $totalGridkWh, 2, '.', ''));
        $totalGensetkWh  = floatval(number_format((float) $totalGensetkWh, 2, '.', ''));

        return array(
            "totalkWh"   => $totalkWh,
            "consoDate"  => $date,
            "consoData"  => [$GridkWh, $GensetkWh, $SolarkWh, $BattkWh],
            "dataPie"    => [$totalGridkWh, $totalGensetkWh, $totalSolarkWh, $totalBattkWh],
        );
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

        $smartMods = $this->site->getSmartMods();
        
        foreach ($smartMods as $smartMod) {
            if ($smartMod->getModType() === 'GRID') {
                $this->setGridMod($smartMod);

                $config = json_decode($this->gridMod->getConfiguration(), true);
                $intervalTime = array_key_exists("Frs", $config) ? $config['Frs']/60.0 : 5.0/60.0 ;//Temps en minutes converti en heure
                // dump($intervalTime);
                $this->setGridIntervalTime($intervalTime);
            }
            if ($smartMod->getModType() === 'GENSET') {
                $this->setGensetMod($smartMod);

                $config = json_decode($this->gensetMod->getConfiguration(), true);
                $intervalTime = array_key_exists("Frs", $config) ? $config['Frs']/60.0 : 5.0/60.0 ;//Temps en minutes converti en heure
                // dump($intervalTime);
                $this->setGridIntervalTime($intervalTime);
            }
            if ($smartMod->getModType() === 'Load Meter') {
                $this->setLoadSiteMod($smartMod);

                $config = json_decode($this->loadSiteMod->getConfiguration(), true);
                $intervalTime = array_key_exists("Frs", $config) ? $config['Frs']/60.0 : 5.0/60.0 ;//Temps en minutes converti en heure
                // dump($intervalTime);
                $this->setLoadSiteIntervalTime($intervalTime);
            }
        }

        if ($this->gensetMod) $this->gensetModService->setGensetMod($this->gensetMod);

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
        if ($this->gensetMod) $this->gensetModService->setStartDate($this->startDate);
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
        if ($this->gensetMod) $this->gensetModService->setEndDate($this->endDate);
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

    /**
     * Get module Genset
     *
     * @return  SmartMod
     */
    public function getGensetMod()
    {
        return $this->gensetMod;
    }

    /**
     * Set module Genset
     *
     * @param  SmartMod  $gensetMod  Module Genset
     *
     * @return  self
     */
    public function setGensetMod(SmartMod $gensetMod)
    {
        $this->gensetMod = $gensetMod;

        return $this;
    }

    /**
     * Get module GRID du site
     *
     * @return  SmartMod
     */ 
    public function getGridMod()
    {
        return $this->gridMod;
    }

    /**
     * Set module GRID du site
     *
     * @param  SmartMod  $gridMod  Module GRID du site
     *
     * @return  self
     */ 
    public function setGridMod(SmartMod $gridMod)
    {
        $this->gridMod = $gridMod;

        return $this;
    }

    /**
     * Get module Load Site
     *
     * @return  SmartMod
     */ 
    public function getLoadSiteMod()
    {
        return $this->loadSiteMod;
    }

    /**
     * Set module Load Site
     *
     * @param  SmartMod  $loadSiteMod  Module Load Site
     *
     * @return  self
     */ 
    public function setLoadSiteMod(SmartMod $loadSiteMod)
    {
        $this->loadSiteMod = $loadSiteMod;

        return $this;
    }

    /**
     * Get interval de temps d'envoi des données du module GRID
     *
     * @return  float
     */ 
    public function getGridIntervalTime()
    {
        return $this->gridIntervalTime;
    }

    /**
     * Set interval de temps d'envoi des données du module GRID
     *
     * @param  float  $gridIntervalTime  Interval de temps d'envoi des données du module GRID
     *
     * @return  self
     */ 
    public function setGridIntervalTime(float $gridIntervalTime)
    {
        $this->gridIntervalTime = $gridIntervalTime;

        return $this;
    }

    /**
     * Get interval de temps d'envoi des données du module GENSET
     *
     * @return  float
     */ 
    public function getGensetIntervalTime()
    {
        return $this->gensetIntervalTime;
    }

    /**
     * Set interval de temps d'envoi des données du module GENSET
     *
     * @param  float  $gensetIntervalTime  Interval de temps d'envoi des données du module GENSET
     *
     * @return  self
     */ 
    public function setGensetIntervalTime(float $gensetIntervalTime)
    {
        $this->gensetIntervalTime = $gensetIntervalTime;

        return $this;
    }

    /**
     * Get interval de temps d'envoi des données du module Load Site
     *
     * @return  float
     */ 
    public function getLoadSiteIntervalTime()
    {
        return $this->loadSiteIntervalTime;
    }

    /**
     * Set interval de temps d'envoi des données du module Load Site
     *
     * @param  float  $loadSiteIntervalTime  Interval de temps d'envoi des données du module Load Site
     *
     * @return  self
     */ 
    public function setLoadSiteIntervalTime(float $loadSiteIntervalTime)
    {
        $this->loadSiteIntervalTime = $loadSiteIntervalTime;

        return $this;
    }
}
