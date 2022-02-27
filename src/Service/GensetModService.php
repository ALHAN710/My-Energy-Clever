<?php

namespace App\Service;

use DateTime;
use DateInterval;
use App\Entity\Site;
use App\Entity\SmartMod;
use App\Entity\GensetRealTimeData;
use Doctrine\ORM\EntityManagerInterface;

class GensetModService
{
    /**
     * Smart Module de type Genset
     *
     * @var SmartMod
     */
    private $gensetMod;

    /**
     * The Entity Manager Interface object
     *
     * @var EntityManagerInterface
     */
    private $manager;

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

    public function getDashboardData()
    {
        // $now  = new DateTime('2021-11-30 16:20:00');
        $now  = new DateTime('now');
        $lastMonthDate = new DateTime('now');
        $lastMonthDate->sub(new DateInterval('P1M'));

        // ######## Récupération des données TRH, Nb de démarrage et nb d'arrêt pour le mois en cours ########
        /*$firstGensetRealTimeDataMonthRecord = $this->manager->createQuery("SELECT d.totalRunningHours AS TRH, d.totalEnergy AS TEP,
                                        d.nbPerformedStartUps AS NPS, MIN(NULLIF(d.nbStop,0)) AS NPST
                                        FROM App\Entity\GensetData d
                                        JOIN d.smartMod sm 
                                        WHERE d.dateTime =  (SELECT min(d1.dateTime) FROM App\Entity\GensetData d1 WHERE d1.dateTime LIKE :thisMonth)
                                        AND sm.id = :smartModId                   
                                        ")
            ->setParameters(array(
                'thisMonth'    => date("Y-m") . "%",
                'smartModId'   => $this->gensetMod->getId()
            ))
            ->getResult();*/
        $firstGensetRealTimeDataMonthRecord = $this->manager->createQuery("SELECT MIN(NULLIF(d.totalRunningHours,0)) AS TRH,
                                        MIN(NULLIF(d.nbPerformedStartUps,0)) AS NPS, MIN(NULLIF(d.nbStop,0)) AS NPST
                                        FROM App\Entity\GensetData d
                                        JOIN d.smartMod sm 
                                        WHERE d.dateTime LIKE :thisMonth
                                        AND sm.id = :smartModId                   
                                        ")
            ->setParameters(array(
                'thisMonth'    => date("Y-m") . "%",
                'smartModId'   => $this->gensetMod->getId()
            ))
            ->getResult();
        // // dump($firstGensetRealTimeDataMonthRecord);
        /*$lastGensetRealTimeDataMonthRecord = $this->manager->createQuery("SELECT d.totalRunningHours AS TRH, d.totalEnergy AS TEP,
                                        d.nbPerformedStartUps AS NPS, MIN(NULLIF(d.nbStop,0)) AS NPST
                                        FROM App\Entity\GensetData d
                                        JOIN d.smartMod sm 
                                        WHERE d.dateTime =  (SELECT max(d1.dateTime) FROM App\Entity\GensetData d1 WHERE d1.dateTime LIKE :thisMonth)
                                        AND sm.id = :smartModId                   
                                        ")
            ->setParameters(array(
                'thisMonth'    => date("Y-m") . "%",
                'smartModId'   => $this->gensetMod->getId()
            ))
            ->getResult();*/
        $lastGensetRealTimeDataMonthRecord = $this->manager->createQuery("SELECT MAX(d.totalRunningHours) AS TRH, MAX(d.nbPerformedStartUps) AS NPS,
                                        MAX(NULLIF(d.nbStop,0)) AS NPST
                                        FROM App\Entity\GensetData d
                                        JOIN d.smartMod sm 
                                        WHERE d.dateTime LIKE :thisMonth
                                        AND sm.id = :smartModId                   
                                        ")
            ->setParameters(array(
                'thisMonth'    => date("Y-m") . "%",
                'smartModId'   => $this->gensetMod->getId()
            ))
            ->getResult();
        // // dump($lastGensetRealTimeDataMonthRecord);
        $npsm  = 0;
        $npstm = 0;
        $trhm  = 0;
        //$tepm = 0;
        if (count($firstGensetRealTimeDataMonthRecord) && count($lastGensetRealTimeDataMonthRecord)) {
            $npsm  = intval($lastGensetRealTimeDataMonthRecord[0]['NPS']) - intval($firstGensetRealTimeDataMonthRecord[0]['NPS']);
            $npstm = intval($lastGensetRealTimeDataMonthRecord[0]['NPST']) - intval($firstGensetRealTimeDataMonthRecord[0]['NPST']);
            $trhm  = intval($lastGensetRealTimeDataMonthRecord[0]['TRH']) - intval($firstGensetRealTimeDataMonthRecord[0]['TRH']);
            //$tepm = intval($lastGensetRealTimeDataMonthRecord[0]['TEP']) - intval($firstGensetRealTimeDataMonthRecord[0]['TEP']);
            // // dump($npsm);
            // // dump($trhm);
            // // dump($tepm);
        }

        // ######## Récupération des données TRH, Nb de démarrage et nb d'arrêt pour le mois (n - 1) ########
        /*$firstGensetRealTimeDataMonthRecord = $this->manager->createQuery("SELECT d.totalRunningHours AS TRH, d.totalEnergy AS TEP,
                                        d.nbPerformedStartUps AS NPS, MIN(NULLIF(d.nbStop,0)) AS NPST
                                        FROM App\Entity\GensetData d
                                        JOIN d.smartMod sm 
                                        WHERE d.dateTime =  (SELECT min(d1.dateTime) FROM App\Entity\GensetData d1 WHERE d1.dateTime LIKE :lastMonthDate AND d.dateTime <= :lastNowDate)
                                        AND sm.id = :smartModId                   
                                        ")
            ->setParameters(array(
                'lastMonthDate' => $lastMonthDate->format('Y-m') . '%',
                'lastNowDate'   => $lastMonthDate->format('Y-m-d H:i:s'),
                'smartModId'   => $this->gensetMod->getId()
            ))
            ->getResult();*/
        $firstGensetRealTimeDataMonthRecord = $this->manager->createQuery("SELECT MIN(NULLIF(d.totalRunningHours,0)) AS TRH,
                                        MIN(NULLIF(d.nbPerformedStartUps,0)) AS NPS, MIN(NULLIF(d.nbStop,0)) AS NPST
                                        FROM App\Entity\GensetData d
                                        JOIN d.smartMod sm 
                                        WHERE d.dateTime LIKE :lastMonthDate
                                        AND d.dateTime <= :lastNowDate
                                        AND sm.id = :smartModId                   
                                        ")
            ->setParameters(array(
                'lastMonthDate' => $lastMonthDate->format('Y-m') . '%',
                'lastNowDate'   => $lastMonthDate->format('Y-m-d H:i:s'),
                'smartModId'   => $this->gensetMod->getId()
            ))
            ->getResult();
        // // dump($firstGensetRealTimeDataMonthRecord);
        /*$lastGensetRealTimeDataMonthRecord = $this->manager->createQuery("SELECT d.totalRunningHours AS TRH, d.totalEnergy AS TEP,
                                        d.nbPerformedStartUps AS NPS, MIN(NULLIF(d.nbStop,0)) AS NPST
                                        FROM App\Entity\GensetData d
                                        JOIN d.smartMod sm 
                                        WHERE d.dateTime =  (SELECT max(d1.dateTime) FROM App\Entity\GensetData d1 WHERE d1.dateTime LIKE :lastMonthDate AND d.dateTime <= :lastNowDate)
                                        AND sm.id = :smartModId                   
                                        ")
            ->setParameters(array(
                'lastMonthDate' => $lastMonthDate->format('Y-m') . '%',
                'lastNowDate'   => $lastMonthDate->format('Y-m-d H:i:s'),
                'smartModId'   => $this->gensetMod->getId()
            ))
            ->getResult();*/
        $lastGensetRealTimeDataMonthRecord = $this->manager->createQuery("SELECT MAX(d.totalRunningHours) AS TRH, MAX(d.nbPerformedStartUps) AS NPS,
                                        MAX(NULLIF(d.nbStop,0)) AS NPST
                                        FROM App\Entity\GensetData d
                                        JOIN d.smartMod sm 
                                        WHERE d.dateTime LIKE :lastMonthDate
                                        AND d.dateTime <= :lastNowDate
                                        AND sm.id = :smartModId                   
                                        ")
            ->setParameters(array(
                'lastMonthDate' => $lastMonthDate->format('Y-m') . '%',
                'lastNowDate'   => $lastMonthDate->format('Y-m-d H:i:s'),
                'smartModId'   => $this->gensetMod->getId()
            ))
            ->getResult();
        // // dump($lastGensetRealTimeDataMonthRecord);
        $nps_lastmonth  = 0;
        $npst_lastmonth = 0;
        $trh_lastmonth  = 0;
        //$tepm = 0;
        if (count($firstGensetRealTimeDataMonthRecord) && count($lastGensetRealTimeDataMonthRecord)) {
            $nps_lastmonth  = intval($lastGensetRealTimeDataMonthRecord[0]['NPS']) - intval($firstGensetRealTimeDataMonthRecord[0]['NPS']);
            $npst_lastmonth = intval($lastGensetRealTimeDataMonthRecord[0]['NPST']) - intval($firstGensetRealTimeDataMonthRecord[0]['NPST']);
            $trh_lastmonth  = intval($lastGensetRealTimeDataMonthRecord[0]['TRH']) - intval($firstGensetRealTimeDataMonthRecord[0]['TRH']);
            //$tep_lastmonth = intval($lastGensetRealTimeDataMonthRecord[0]['TEP']) - intval($firstGensetRealTimeDataMonthRecord[0]['TEP']);
            // // dump($nps_lastmonth);
            // // dump($trh_lastmonth);
            // // dump($tep_lastmonth);
        }

        // ######## Récupération des données de consommation et d'approvisionnement de Fuel
        $fuelData = $this->getConsoFuelData();

        // ######## Récupération des données temps réel du module Genset
        $gensetRealTimeData = $this->manager->getRepository(GensetRealTimeData::class)->findOneBy(['smartMod' => $this->gensetMod->getId()]) ?? new GensetRealTimeData();

        return array(
            //'Vcg'     => [$gensetRealTimeData->getL12G() ?? 0, $gensetRealTimeData->getL13G() ?? 0, $gensetRealTimeData->getL23G() ?? 0],
            //'Vsg'     => [$gensetRealTimeData->getL1N() ?? 0, $gensetRealTimeData->getL2N() ?? 0, $gensetRealTimeData->getL3N() ?? 0],
            //'Vcm'     => [$gensetRealTimeData->getL12M() ?? 0, $gensetRealTimeData->getL13M() ?? 0, $gensetRealTimeData->getL23M() ?? 0],
            //'I'       => [$gensetRealTimeData->getI1() ?? 0, $gensetRealTimeData->getI2() ?? 0, $gensetRealTimeData->getI3() ?? 0],
            //'Freq'    => $gensetRealTimeData->getFreq() ?? 0,
            //'Idiff'   => $gensetRealTimeData->getIDiff() ?? 0,
            'Level'       => [$gensetRealTimeData->getFuelLevel() ?? 0, $gensetRealTimeData->getWaterLevel() ?? 0, $gensetRealTimeData->getOilLevel() ?? 0],
            // 'Pressure'       => [$gensetRealTimeData->getAirPressure() ?? 0, $gensetRealTimeData->getOilPressure() ?? 0],
            //'Temp'       => [$gensetRealTimeData->getWaterTemperature() ?? 0, $gensetRealTimeData->getCoolerTemperature() ?? 0],
            // 'EngineSpeed' => $gensetRealTimeData->getEngineSpeed() ?? 0,
            //'BattVolt' => $gensetRealTimeData->getBattVoltage() ?? 0,
            'HTM' => $gensetRealTimeData->getHoursToMaintenance() ?? 0,
            'CGCR'       => [
                'CG'    =>  $gensetRealTimeData->getCg() ?? 0,
                'CR'    =>  $gensetRealTimeData->getCr() ?? 0
            ],
            'Gensetrunning' => $gensetRealTimeData->getGensetRunning() ?? 0,
            //'MainsPresence' => $gensetRealTimeData->getMainsPresence() ?? 0,
            //'MaintenanceRequest' => $gensetRealTimeData->getMaintenanceRequest() ?? 0,
            // 'LowFuel' => $gensetRealTimeData->getLowFuel() ?? 0,
            // 'PresenceWaterInFuel' => $gensetRealTimeData->getPresenceWaterInFuel() ?? 0,
            // 'Overspeed' => $gensetRealTimeData->getOverspeed() ?? 0,
            // 'FreqAlarm'       => [$gensetRealTimeData->getMaxFreq() ?? 0, $gensetRealTimeData->getMinFreq() ?? 0],
            // 'VoltAlarm'       => [$gensetRealTimeData->getMaxVolt() ?? 0, $gensetRealTimeData->getMinVolt() ?? 0],
            // 'BattVoltAlarm'       => [$gensetRealTimeData->getMaxBattVolt() ?? 0, $gensetRealTimeData->getMinBattVolt() ?? 0],
            // 'Overload' => $gensetRealTimeData->getOverload() ?? 0,
            // 'ShortCircuit' => $gensetRealTimeData->getShortCircuit() ?? 0,
            // 'IncSeq'       => [$gensetRealTimeData->getMainsIncSeq() ?? 0, $gensetRealTimeData->getGensetIncSeq() ?? 0],
            // 'DifferentialIntervention' => $gensetRealTimeData->getDifferentialIntervention() ?? 0,
            'Date' => $gensetRealTimeData->getDateTime() ?? '',
            'currentConsoFuel'         => $fuelData['currentConsoFuel'],
            'currentConsoFuelProgress' => $fuelData['currentConsoFuelProgress'],
            'currentApproFuel'         => $fuelData['currentApproFuel'],
            'currentApproFuelProgress' => $fuelData['currentApproFuelProgress'],
            'dureeFonctionnement'         => $fuelData['dureeFonctionnement'],
            'dureeFonctionnementProgress' => $fuelData['dureeFonctionnementProgress'],
            'dayBydayConsoData' => [
                'dateConso'   => $fuelData['dayBydayConsoData']['dateConso'],
                "consoFuel"   => $fuelData['dayBydayConsoData']['consoFuel'],
                "approFuel"   => $fuelData['dayBydayConsoData']['approFuel']
            ],
            'TRH'  => [$trhm, $trh_lastmonth],
            'NPS'  => [$npsm, $nps_lastmonth],
            'NPST' => [$npstm, $npst_lastmonth],
        );
    }

    public function getConsoFuelData()
    {
        $lastStartDate = new DateTime($this->startDate->format('Y-m-d H:i:s'));
        $lastStartDate->sub(new DateInterval('P1M'));

        $lastEndDate = new DateTime($this->endDate->format('Y-m-d H:i:s'));
        $lastEndDate->sub(new DateInterval('P1M'));

        // ========= Détermination de la longueur de la datetime =========
        $length = 10; //Si endDate > startDate => regoupement des données par jour de la fenêtre de date
        if ($this->endDate->format('Y-m-d') == $this->startDate->format('Y-m-d')) $length = 13; //Si endDate == startDate => regoupement des données par heure du jour choisi

        // ######## Récupération des données de courbe pour le mois en cours ########
        $dataQuery = $this->manager->createQuery("SELECT d.dateTime as dat, d.fuelLevel as FL, d.totalRunningHours as TRH
                                        FROM App\Entity\GensetData d 
                                        JOIN d.smartMod sm
                                        WHERE d.dateTime BETWEEN :startDate AND :endDate
                                        AND sm.id = :smartModId
                                        ORDER BY dat ASC
                                        ")
            ->setParameters(array(
                //'selDate'      => $dateparam,
                'startDate'  => $this->startDate->format('Y-m-d H:i:s'),
                'endDate'    => $this->endDate->format('Y-m-d H:i:s'),
                'smartModId' => $this->gensetMod->getId()
            ))
            ->getResult();
        // dump($data);
        // $FL   = [];
        // $TRH  = [];
        // $date = [];
        $data = [];
        foreach ($dataQuery as $d) {
            // $date[]    = $d['dat']->format('Y-m-d H:i:s');
            // $FL[]      = $d['FL'];
            // $TRH[]     = $d['TRH'];
            $data[$d['dat']->format('Y-m-d H:i:s')] = [
                'FL'    => $d['FL'],
                'TRH'   => $d['TRH']
            ];
            //$Cosfi[]   = number_format((float) $d['cosfi'], 2, '.', '');
        }
        //dump($data);
        $dayRecord = $this->manager->createQuery("SELECT SUBSTRING(d.dateTime,1,:length_) as dat
                                        FROM App\Entity\GensetData d 
                                        JOIN d.smartMod sm
                                        WHERE d.dateTime BETWEEN :startDate AND :endDate
                                        AND sm.id = :smartModId
                                        GROUP BY dat
                                        ORDER BY dat ASC
                                        ")
            ->setParameters(array(
                'length_'    => $length,
                'startDate'  => $this->startDate->format('Y-m-d H:i:s'),
                'endDate'    => $this->endDate->format('Y-m-d H:i:s'),
                'smartModId' => $this->gensetMod->getId(),
            ))
            ->getResult();

        // dump($dayRecord);
        $day = [];
        foreach ($dayRecord as $d) {
            $day[]    = $d['dat'];
        }

        $dataOrderByDay = []; //Tableau des valeurs jour après jour
        foreach ($data as $key => $value) {
            // dump($key);
            foreach ($day as $index => $val) {
                //dump($val);
                if (strpos($key, $val) !== false) { // On vérifie si le la sous-chaîne du jour est contenue dans la date
                    $dataOrderByDay[$val]['FL'][]  = $value['FL'];
                    $dataOrderByDay[$val]['TRH'][] = $value['TRH'];
                }
            }
        }

        $currentConsoFuel = 0;
        $currentApproFuel = 0;

        $consoFuelDayByDay = [];
        $approFuelDayByDay = [];

        $dureeDayByDay = [];

        foreach ($dataOrderByDay as $key => $value) {
            $consoFuel_ = 0;
            $approFuel_ = 0;

            //Données de la courbe de durée de fonctionnement jour après jour
            if (array_key_exists('TRH', $value)) {
                if (end($value['TRH']) !== false && reset($value['TRH']) !== false) {
                    $dureeDayByDay[$key] = abs(end($value['TRH']) - reset($value['TRH']));
                }
            }

            //Données des courbe de consommation et approvisionnement jour après jour
            if (array_key_exists('FL', $value)) {
                $temp = $value['FL']; //Tableau tampon
                if (count($temp) > 0) {
                    for ($i = 0; $i < count($temp) - 1; $i++) {
                        $diff = abs($temp[$i + 1] - $temp[$i]);
                        if ($temp[$i + 1] >= $temp[$i]) {
                            $approFuel_ += $diff;
                        } else {
                            $consoFuel_ += $diff;
                        }
                    }
                }
            }

            $currentConsoFuel += $consoFuel_;
            $currentApproFuel += $approFuel_;

            $consoFuelDayByDay[$key] = $consoFuel_;
            $approFuelDayByDay[$key] = $approFuel_;
        }

        // ######## Récupération des données de courbe pour le mois (n - 1) ########
        $lastData = $this->manager->createQuery("SELECT d.dateTime as dat, d.fuelLevel as FL, d.totalRunningHours as TRH
                                        FROM App\Entity\GensetData d 
                                        JOIN d.smartMod sm
                                        WHERE d.dateTime BETWEEN :lastStartDate AND :lastEndDate
                                        AND sm.id = :smartModId
                                        ORDER BY dat ASC
                                        ")
            ->setParameters(array(
                //'selDate'      => $dateparam,
                'lastStartDate' => $lastStartDate->format('Y-m') . '%',
                'lastEndDate'   => $lastEndDate->format('Y-m-d H:i:s'),
                'smartModId'    => $this->gensetMod->getId()
            ))
            ->getResult();
        // dump($lastData);
        $FL   = [];
        $TRH  = [];
        foreach ($lastData as $d) {
            // $date[]    = $d['dat']->format('Y-m-d H:i:s');
            $FL[]      = $d['FL'];
            $TRH[]     = $d['TRH'];
        }

        $lastConsoFuel = 0;
        $lastApproFuel = 0;
        $lastDuree  = array_sum($TRH);

        if (count($FL) > 0) {
            for ($i = 0; $i < count($FL) - 1; $i++) {
                $diff = abs($FL[$i + 1] - $FL[$i]);
                if ($FL[$i + 1] >= $FL[$i]) {
                    $lastApproFuel += $diff;
                } else {
                    $lastConsoFuel += $diff;
                }
            }
        }

        $duree = array_sum($dureeDayByDay);

        $currentConsoFuelProgress = ($lastConsoFuel !== 0) ? ($currentConsoFuel - $lastConsoFuel) / $lastConsoFuel : 'INF';
        $currentApproFuelProgress = ($lastApproFuel !== 0) ? ($currentApproFuel - $lastApproFuel) / $lastApproFuel : 'INF';
        $dureeProgress = ($lastDuree !== 0) ? ($duree - $lastDuree) / $lastDuree : 'INF';

        return array(
            'currentConsoFuel'              => $currentConsoFuel,
            'dureeFonctionnement'           => $duree,
            'dureeFonctionnementProgress'   => $dureeProgress,
            'currentConsoFuelProgress'      => floatval(number_format((float) $currentConsoFuelProgress, 2, '.', '')),
            'currentApproFuel'              => $currentApproFuel,
            'currentApproFuelProgress'      => floatval(number_format((float) $currentApproFuelProgress, 2, '.', '')),
            'dayBydayConsoData' => [
                'dateConso'   => $day,
                "consoFuel"   => $consoFuelDayByDay,
                "approFuel"   => $approFuelDayByDay
            ]
        );
    }

    public function getDataForGraph()
    {
    }

    public function getGensetRealTimeData()
    {
        $date = [];
        $S = [];
        $P = [];
        $Cosfi = [];

        /*$lastRecord = $this->manager->createQuery("SELECT d.p AS P, d.q AS Q, d.s AS S, d.cosfi AS Cosfi, d.totalRunningHours AS TRH,
                                        d.totalEnergy AS TEP, d.fuelInstConsumption AS FC, d.dateTime
                                        FROM App\Entity\GensetData d
                                        JOIN d.smartMod sm 
                                        WHERE d.dateTime =  (SELECT max(d1.dateTime) FROM App\Entity\GensetData d1 WHERE d1.dateTime LIKE :nowDate)
                                        AND sm.id = :smartModId                   
                                        ")
            ->setParameters(array(
                'nowDate'      => date("Y-m-d") . "%",
                'smartModId'   => $this->gensetMod->getId()
            ))
            ->getResult();*/

        /*$data = $this->manager->createQuery("SELECT d.dateTime as dat, d.p, (d.s*100.0)/:genpower as s, d.cosfi
                                        FROM App\Entity\GensetData d 
                                        JOIN d.smartMod sm
                                        WHERE d.dateTime LIKE :nowDate
                                        AND sm.id = :smartModId
                                        ORDER BY dat ASC
                                        
                                        ")
            ->setParameters(array(
                //'selDate'      => $dateparam,
                'nowDate'     => date("Y-m-d") . "%",
                'genpower'  => $this->gensetMod->getPower(),
                'smartModId'  => $this->gensetMod->getId()
            ))
            ->getResult();


        // dump($data);
        foreach ($data as $d) {
            $date[]    = $d['dat']->format('Y-m-d H:i:s');
            //$P[]       = number_format((float) $d['p'], 2, '.', '');
            $S[]    = number_format((float) $d['s'], 2, '.', '');
            //$Cosfi[]   = number_format((float) $d['cosfi'], 2, '.', '');
        }*/

        $NMIDay = $this->manager->createQuery("SELECT SUM(d.nbMainsInterruption) AS NMID
                                        FROM App\Entity\GensetData d
                                        JOIN d.smartMod sm 
                                        WHERE d.dateTime LIKE :nowDate
                                        AND sm.id = :smartModId                   
                                        ")
            ->setParameters(array(
                'nowDate'      => date("Y-m-d") . "%",
                'smartModId'   => $this->gensetMod->getId()
            ))
            ->getResult();
        // //dump($NMIDay);
        $NMIMonth = $this->manager->createQuery("SELECT SUM(d.nbMainsInterruption) AS NMIM
                                        FROM App\Entity\GensetData d
                                        JOIN d.smartMod sm 
                                        WHERE d.dateTime LIKE :nowDate
                                        AND sm.id = :smartModId                   
                                        ")
            ->setParameters(array(
                'nowDate'      => date("Y-m") . "%",
                'smartModId'   => $this->gensetMod->getId()
            ))
            ->getResult();
        // //dump($NMIMonth);
        $NMIYear = $this->manager->createQuery("SELECT SUM(d.nbMainsInterruption) AS NMIY
                                        FROM App\Entity\GensetData d
                                        JOIN d.smartMod sm 
                                        WHERE d.dateTime LIKE :nowDate
                                        AND sm.id = :smartModId                   
                                        ")
            ->setParameters(array(
                'nowDate'      => date("Y") . "%",
                'smartModId'   => $this->gensetMod->getId()
            ))
            ->getResult();
        // //dump($NMIYear);

        /*$firstGensetRealTimeDataDayRecord = $this->manager->createQuery("SELECT d.totalRunningHours AS TRH, d.totalEnergy AS TEP,
                                        d.nbPerformedStartUps AS NPS
                                        FROM App\Entity\GensetData d
                                        JOIN d.smartMod sm 
                                        WHERE d.dateTime =  (SELECT min(d1.dateTime) FROM App\Entity\GensetData d1 WHERE d1.dateTime LIKE :nowDate)
                                        AND sm.id = :smartModId                   
                                        ")
            ->setParameters(array(
                'nowDate'      => date("Y-m-d") . "%",
                'smartModId'   => $this->gensetMod->getId()
            ))
            ->getResult();*/
        $firstGensetRealTimeDataDayRecord = $this->manager->createQuery("SELECT MIN(NULLIF(d.totalRunningHours,0)) AS TRH, MIN(NULLIF(d.totalEnergy,0)) AS TEP,
                                        MIN(NULLIF(d.nbPerformedStartUps,0)) AS NPS
                                        FROM App\Entity\GensetData d
                                        JOIN d.smartMod sm 
                                        WHERE d.dateTime LIKE :nowDate
                                        AND sm.id = :smartModId                   
                                        ")
            ->setParameters(array(
                'nowDate'      => date("Y-m-d") . "%",
                'smartModId'   => $this->gensetMod->getId()
            ))
            ->getResult();
        // // dump($firstGensetRealTimeDataDayRecord);
        /*$lastGensetRealTimeDataDayRecord = $this->manager->createQuery("SELECT d.totalRunningHours AS TRH, d.totalEnergy AS TEP,
                                        d.nbPerformedStartUps AS NPS
                                        FROM App\Entity\GensetData d
                                        JOIN d.smartMod sm 
                                        WHERE d.dateTime =  (SELECT max(d1.dateTime) FROM App\Entity\GensetData d1 WHERE d1.dateTime LIKE :nowDate)
                                        AND sm.id = :smartModId                   
                                        ")
            ->setParameters(array(
                'nowDate'      => date("Y-m-d") . "%",
                'smartModId'   => $this->gensetMod->getId()
            ))
            ->getResult();*/
        $lastGensetRealTimeDataDayRecord = $this->manager->createQuery("SELECT MAX(d.totalRunningHours) AS TRH, MAX(d.totalEnergy) AS TEP,
                                        MAX(d.nbPerformedStartUps) AS NPS
                                        FROM App\Entity\GensetData d
                                        JOIN d.smartMod sm 
                                        WHERE d.dateTime LIKE :nowDate
                                        AND sm.id = :smartModId                   
                                        ")
            ->setParameters(array(
                'nowDate'      => date("Y-m-d") . "%",
                'smartModId'   => $this->gensetMod->getId()
            ))
            ->getResult();
        // // dump($lastGensetRealTimeDataDayRecord);
        $npsd = 0;
        $trhd = 0;
        $tepd = 0;
        if (count($firstGensetRealTimeDataDayRecord) && count($lastGensetRealTimeDataDayRecord)) {
            $npsd = intval($lastGensetRealTimeDataDayRecord[0]['NPS']) - intval($firstGensetRealTimeDataDayRecord[0]['NPS']);
            $trhd = intval($lastGensetRealTimeDataDayRecord[0]['TRH']) - intval($firstGensetRealTimeDataDayRecord[0]['TRH']);
            $tepd = intval($lastGensetRealTimeDataDayRecord[0]['TEP']) - intval($firstGensetRealTimeDataDayRecord[0]['TEP']);
            // // dump($npsd);
            // // dump($trhd);
            // // dump($tepd);
        }

        $firstGensetRealTimeDataMonthRecord = $this->manager->createQuery("SELECT MIN(NULLIF(d.totalRunningHours,0)) AS TRH, MIN(NULLIF(d.totalEnergy,0)) AS TEP,
                                        MIN(NULLIF(d.nbPerformedStartUps,0)) AS NPS
                                        FROM App\Entity\GensetData d
                                        JOIN d.smartMod sm 
                                        WHERE d.dateTime LIKE :nowDate
                                        AND sm.id = :smartModId                   
                                        ")
            ->setParameters(array(
                'nowDate'      => date("Y-m") . "%",
                'smartModId'   => $this->gensetMod->getId()
            ))
            ->getResult();
        // // dump($firstGensetRealTimeDataMonthRecord);

        /*$lastGensetRealTimeDataMonthRecord = $this->manager->createQuery("SELECT d.totalRunningHours AS TRH, d.totalEnergy AS TEP,
                                        d.nbPerformedStartUps AS NPS
                                        FROM App\Entity\GensetData d
                                        JOIN d.smartMod sm 
                                        WHERE d.dateTime =  (SELECT max(d1.dateTime) FROM App\Entity\GensetData d1 WHERE d1.dateTime LIKE :nowDate)
                                        AND sm.id = :smartModId                   
                                        ")
            ->setParameters(array(
                'nowDate'      => date("Y-m") . "%",
                'smartModId'   => $this->gensetMod->getId()
            ))
            ->getResult();*/
        $lastGensetRealTimeDataMonthRecord = $this->manager->createQuery("SELECT MAX(d.totalRunningHours) AS TRH, MAX(d.totalEnergy) AS TEP,
                                        MAX(d.nbPerformedStartUps) AS NPS
                                        FROM App\Entity\GensetData d
                                        JOIN d.smartMod sm 
                                        WHERE d.dateTime LIKE :nowDate
                                        AND sm.id = :smartModId                   
                                        ")
            ->setParameters(array(
                'nowDate'      => date("Y-m") . "%",
                'smartModId'   => $this->gensetMod->getId()
            ))
            ->getResult();

        // // dump($lastGensetRealTimeDataMonthRecord);
        $npsm = 0;
        $trhm = 0;
        $tepm = 0;
        if (count($firstGensetRealTimeDataMonthRecord) && count($lastGensetRealTimeDataMonthRecord)) {
            $npsm = intval($lastGensetRealTimeDataMonthRecord[0]['NPS']) - intval($firstGensetRealTimeDataMonthRecord[0]['NPS']);
            $trhm = intval($lastGensetRealTimeDataMonthRecord[0]['TRH']) - intval($firstGensetRealTimeDataMonthRecord[0]['TRH']);
            $tepm = intval($lastGensetRealTimeDataMonthRecord[0]['TEP']) - intval($firstGensetRealTimeDataMonthRecord[0]['TEP']);
            // // dump($npsm);
            // // dump($trhm);
            // // dump($tepm);
        }

        /*$firstGensetRealTimeDataYearRecord = $this->manager->createQuery("SELECT d.totalRunningHours AS TRH, d.totalEnergy AS TEP,
                                        d.nbPerformedStartUps AS NPS
                                        FROM App\Entity\GensetData d
                                        JOIN d.smartMod sm 
                                        WHERE d.dateTime =  (SELECT min(d1.dateTime) FROM App\Entity\GensetData d1 WHERE d1.dateTime LIKE :nowDate)
                                        AND sm.id = :smartModId                   
                                        ")
            ->setParameters(array(
                'nowDate'      => date("Y") . "%",
                'smartModId'   => $this->gensetMod->getId()
            ))
            ->getResult();*/
        $firstGensetRealTimeDataYearRecord = $this->manager->createQuery("SELECT MIN(NULLIF(d.totalRunningHours,0)) AS TRH, MIN(NULLIF(d.totalEnergy,0)) AS TEP,
                                        MIN(NULLIF(d.nbPerformedStartUps,0)) AS NPS
                                        FROM App\Entity\GensetData d
                                        JOIN d.smartMod sm 
                                        WHERE d.dateTime LIKE :nowDate
                                        AND sm.id = :smartModId                   
                                        ")
            ->setParameters(array(
                'nowDate'      => date("Y") . "%",
                'smartModId'   => $this->gensetMod->getId()
            ))
            ->getResult();

        // //dump($firstGensetRealTimeDataYearRecord);
        /*$lastGensetRealTimeDataYearRecord = $this->manager->createQuery("SELECT d.totalRunningHours AS TRH, d.totalEnergy AS TEP,
                                        d.nbPerformedStartUps AS NPS
                                        FROM App\Entity\GensetData d
                                        JOIN d.smartMod sm 
                                        WHERE d.dateTime =  (SELECT max(d1.dateTime) FROM App\Entity\GensetData d1 WHERE d1.dateTime LIKE :nowDate)
                                        AND sm.id = :smartModId                   
                                        ")
            ->setParameters(array(
                'nowDate'      => date("Y") . "%",
                'smartModId'   => $this->gensetMod->getId()
            ))
            ->getResult();*/
        $lastGensetRealTimeDataYearRecord = $this->manager->createQuery("SELECT MAX(d.totalRunningHours) AS TRH, MAX(d.totalEnergy) AS TEP,
                                        MAX(d.nbPerformedStartUps) AS NPS
                                        FROM App\Entity\GensetData d
                                        JOIN d.smartMod sm 
                                        WHERE d.dateTime LIKE :nowDate
                                        AND sm.id = :smartModId                   
                                        ")
            ->setParameters(array(
                'nowDate'      => date("Y") . "%",
                'smartModId'   => $this->gensetMod->getId()
            ))
            ->getResult();
        // //dump($lastGensetRealTimeDataYearRecord);

        $npsy = 0;
        $trhy = 0;
        $tepy = 0;
        if (count($firstGensetRealTimeDataYearRecord) && count($lastGensetRealTimeDataYearRecord)) {
            $npsy = intval($lastGensetRealTimeDataYearRecord[0]['NPS']) - intval($firstGensetRealTimeDataYearRecord[0]['NPS']);
            $trhy = intval($lastGensetRealTimeDataYearRecord[0]['TRH']) - intval($firstGensetRealTimeDataYearRecord[0]['TRH']);
            $tepy = intval($lastGensetRealTimeDataYearRecord[0]['TEP']) - intval($firstGensetRealTimeDataYearRecord[0]['TEP']);
            // // dump($npsy);
            // // dump($trhy);
            // // dump($tepy);
        }
        // //dump($lastRecord);

        $poe = [];
        $FCD = $this->manager->createQuery("SELECT AVG(NULLIF(COALESCE(d.fuelInstConsumption,0), 0)) AS FC
                                    FROM App\Entity\GensetData d
                                    JOIN d.smartMod sm 
                                    WHERE d.dateTime LIKE :nowDate
                                    AND sm.id = :smartModId                 
                                    ")
            ->setParameters(array(
                //'selDate'      => $dat,
                'nowDate'      => date("Y-m-d") . "%",
                'smartModId'   => $this->gensetMod->getId()
            ))
            ->getResult();
        // //dump($FCD);
        if ($tepd > 0) $poe[] = ($FCD[0]['FC'] * 1.0) / $tepd;
        else $poe[] = 0;

        $FCM = $this->manager->createQuery("SELECT AVG(NULLIF(COALESCE(d.fuelInstConsumption,0), 0)) AS FC
                                    FROM App\Entity\GensetData d
                                    JOIN d.smartMod sm 
                                    WHERE d.dateTime LIKE :nowDate
                                    AND sm.id = :smartModId                 
                                    ")
            ->setParameters(array(
                //'selDate'      => $dat,
                'nowDate'      => date("Y-m") . "%",
                'smartModId'   => $this->gensetMod->getId()
            ))
            ->getResult();
        // // dump($FCM);
        if ($tepm > 0) $poe[] = ($FCM[0]['FC'] * 1.0) / $tepm;
        else $poe[] = 0;

        $FCY = $this->manager->createQuery("SELECT AVG(NULLIF(d.fuelInstConsumption, 0)) AS FC
                                    FROM App\Entity\GensetData d
                                    JOIN d.smartMod sm 
                                    WHERE d.dateTime LIKE :nowDate
                                    AND sm.id = :smartModId                 
                                    ")
            ->setParameters(array(
                //'selDate'      => $dat,
                'nowDate'      => date("Y") . "%",
                'smartModId'   => $this->gensetMod->getId()
            ))
            ->getResult();
        // // dump($FCY);
        if ($tepy > 0) $poe[] = ($FCY[0]['FC'] * 1.0) / $tepy;
        else $poe[] = 0;

        $gensetRealTimeData = $this->manager->getRepository(GensetRealTimeData::class)->findOneBy(['id' => $this->gensetMod->getId()]) ?? new GensetRealTimeData();
        $yesterday = new DateTime('now');
        $interval = new DateInterval('P1D'); //P10D P1M
        $yesterday->sub($interval);
        // dump($yesterday);
        $lastMonth = new DateTime('now');
        $interval = new DateInterval('P1M'); //P10D P1M
        $lastMonth->sub($interval);
        // dump($lastMonth);
        $lastYear = new DateTime('now');
        $interval = new DateInterval('P1Y'); //P10D P1M
        $lastYear->sub($interval);
        // dump($lastYear);
        /*  $precDayLastTEPRecord = $this->manager->createQuery("SELECT d.totalEnergy AS TEP
                                        FROM App\Entity\GensetData d
                                        JOIN d.smartMod sm 
                                        WHERE d.dateTime =  (SELECT max(d1.dateTime) FROM App\Entity\GensetData d1 WHERE d1.dateTime LIKE :nowDate)
                                        AND sm.id = :smartModId                   
                                        ")
            ->setParameters(array(
                'nowDate'      => $yesterday->format('Y-m-d') . "%",
                'smartModId'   => $this->gensetMod->getId()
            ))
            ->getResult();
        $precDayFirstTEPRecord = $this->manager->createQuery("SELECT d.totalRunningHours AS TRH, d.totalEnergy AS TEP,
                                        d.nbPerformedStartUps AS NPS
                                        FROM App\Entity\GensetData d
                                        JOIN d.smartMod sm 
                                        WHERE d.dateTime =  (SELECT min(d1.dateTime) FROM App\Entity\GensetData d1 WHERE d1.dateTime LIKE :nowDate)
                                        AND sm.id = :smartModId                   
                                        ")
            ->setParameters(array(
                'nowDate'      => $yesterday->format('Y-m-d') . "%",
                'smartModId'   => $this->gensetMod->getId()
            ))
            ->getResult();
        $prevMonthFirstTEPRecord = $this->manager->createQuery("SELECT d.totalEnergy AS TEP
                                        FROM App\Entity\GensetData d
                                        JOIN d.smartMod sm 
                                        WHERE d.dateTime =  (SELECT min(d1.dateTime) FROM App\Entity\GensetData d1 WHERE d1.dateTime LIKE :nowDate)
                                        AND sm.id = :smartModId                   
                                        ")
            ->setParameters(array(
                'nowDate'      => $lastMonth->format("Y-m") . "%",
                'smartModId'   => $this->gensetMod->getId()
            ))
            ->getResult();
        $prevMonthLastTEPRecord = $this->manager->createQuery("SELECT d.totalEnergy AS TEP
                                        FROM App\Entity\GensetData d
                                        JOIN d.smartMod sm 
                                        WHERE d.dateTime =  (SELECT max(d1.dateTime) FROM App\Entity\GensetData d1 WHERE d1.dateTime LIKE :nowDate)
                                        AND sm.id = :smartModId                   
                                        ")
            ->setParameters(array(
                'nowDate'      => $lastMonth->format("Y-m") . "%",
                'smartModId'   => $this->gensetMod->getId()
            ))
            ->getResult();
        $prevYearFirstTEPRecord = $this->manager->createQuery("SELECT d.totalEnergy AS TEP
                                        FROM App\Entity\GensetData d
                                        JOIN d.smartMod sm 
                                        WHERE d.dateTime =  (SELECT min(d1.dateTime) FROM App\Entity\GensetData d1 WHERE d1.dateTime LIKE :nowDate)
                                        AND sm.id = :smartModId                   
                                        ")
            ->setParameters(array(
                'nowDate'      => $lastYear->format("Y") . "%",
                'smartModId'   => $this->gensetMod->getId()
            ))
            ->getResult();
        $prevYearLastTEPRecord = $this->manager->createQuery("SELECT d.totalEnergy AS TEP
                                        FROM App\Entity\GensetData d
                                        JOIN d.smartMod sm 
                                        WHERE d.dateTime =  (SELECT max(d1.dateTime) FROM App\Entity\GensetData d1 WHERE d1.dateTime LIKE :nowDate)
                                        AND sm.id = :smartModId                   
                                        ")
            ->setParameters(array(
                'nowDate'      => $lastYear->format("Y") . "%",
                'smartModId'   => $this->gensetMod->getId()
            ))
            ->getResult();
     */



        $precDayLastTEPRecord = $this->manager->createQuery("SELECT MAX(d.totalEnergy) AS TEP
                                        FROM App\Entity\GensetData d
                                        JOIN d.smartMod sm 
                                        WHERE d.dateTime LIKE :nowDate
                                        AND sm.id = :smartModId                   
                                        ")
            ->setParameters(array(
                'nowDate'      => $yesterday->format('Y-m-d') . "%",
                'smartModId'   => $this->gensetMod->getId()
            ))
            ->getResult();
        $precDayFirstTEPRecord = $this->manager->createQuery("SELECT MIN(NULLIF(d.totalRunningHours,0)) AS TRH, MIN(NULLIF(d.totalEnergy,0)) AS TEP,
                                        d.nbPerformedStartUps AS NPS
                                        FROM App\Entity\GensetData d
                                        JOIN d.smartMod sm 
                                        WHERE d.dateTime LIKE :nowDate
                                        AND sm.id = :smartModId                   
                                        ")
            ->setParameters(array(
                'nowDate'      => $yesterday->format('Y-m-d') . "%",
                'smartModId'   => $this->gensetMod->getId()
            ))
            ->getResult();
        $prevMonthFirstTEPRecord = $this->manager->createQuery("SELECT MIN(NULLIF(d.totalEnergy,0)) AS TEP
                                        FROM App\Entity\GensetData d
                                        JOIN d.smartMod sm 
                                        WHERE d.dateTime LIKE :nowDate
                                        AND sm.id = :smartModId                   
                                        ")
            ->setParameters(array(
                'nowDate'      => $lastMonth->format("Y-m") . "%",
                'smartModId'   => $this->gensetMod->getId()
            ))
            ->getResult();
        $prevMonthLastTEPRecord = $this->manager->createQuery("SELECT MAX(d.totalEnergy) AS TEP
                                        FROM App\Entity\GensetData d
                                        JOIN d.smartMod sm 
                                        WHERE d.dateTime LIKE :nowDate
                                        AND sm.id = :smartModId                   
                                        ")
            ->setParameters(array(
                'nowDate'      => $lastMonth->format("Y-m") . "%",
                'smartModId'   => $this->gensetMod->getId()
            ))
            ->getResult();
        $prevYearFirstTEPRecord = $this->manager->createQuery("SELECT MIN(NULLIF(d.totalEnergy,0)) AS TEP
                                        FROM App\Entity\GensetData d
                                        JOIN d.smartMod sm 
                                        WHERE d.dateTime LIKE :nowDate
                                        AND sm.id = :smartModId                   
                                        ")
            ->setParameters(array(
                'nowDate'      => $lastYear->format("Y") . "%",
                'smartModId'   => $this->gensetMod->getId()
            ))
            ->getResult();
        $prevYearLastTEPRecord = $this->manager->createQuery("SELECT MAX(d.totalEnergy) AS TEP
                                        FROM App\Entity\GensetData d
                                        JOIN d.smartMod sm 
                                        WHERE d.dateTime LIKE :nowDate
                                        AND sm.id = :smartModId                   
                                        ")
            ->setParameters(array(
                'nowDate'      => $lastYear->format("Y") . "%",
                'smartModId'   => $this->gensetMod->getId()
            ))
            ->getResult();

        $prev_tepd = 0;

        if (count($precDayLastTEPRecord) && count($precDayFirstTEPRecord)) {
            $prev_tepd = intval($precDayFirstTEPRecord[0]['TEP']) - intval($precDayLastTEPRecord[0]['TEP']);
            // dump($prev_tepd);
        }
        $prev_tepm = 0;
        if (count($prevMonthFirstTEPRecord) && count($prevMonthLastTEPRecord)) {
            $prev_tepm = intval($prevMonthLastTEPRecord[0]['TEP']) - intval($prevMonthFirstTEPRecord[0]['TEP']);
            // dump($prev_tepm);
        }
        $prev_tepy = 0;
        if (count($prevYearFirstTEPRecord) && count($prevYearLastTEPRecord)) {
            $prev_tepy = intval($prevYearLastTEPRecord[0]['TEP']) - intval($prevYearFirstTEPRecord[0]['TEP']);
            // dump($prev_tepy);
        }

        // $prev_poe = [];
        // if ($prev_tepd > 0) $prev_poe[] = ($FCD[0]['FC'] * 1.0) / $prev_tepd;
        // else $prev_poe[] = 0;
        // if ($prev_tepm > 0) $prev_poe[] = ($FCM[0]['FC'] * 1.0) / $prev_tepm;
        // else $prev_poe[] = 0;
        // if ($prev_tepy > 0) $prev_poe[] = ($FCY[0]['FC'] * 1.0) / $prev_tepy;
        // else $prev_poe[] = 0;

        return array(
            'Vcg'     => [$gensetRealTimeData->getL12G() ?? 0, $gensetRealTimeData->getL13G() ?? 0, $gensetRealTimeData->getL23G() ?? 0],
            //'Vsg'     => [$gensetRealTimeData->getL1N() ?? 0, $gensetRealTimeData->getL2N() ?? 0, $gensetRealTimeData->getL3N() ?? 0],
            'Vcm'     => [$gensetRealTimeData->getL12M() ?? 0, $gensetRealTimeData->getL13M() ?? 0, $gensetRealTimeData->getL23M() ?? 0],
            //'I'       => [$gensetRealTimeData->getI1() ?? 0, $gensetRealTimeData->getI2() ?? 0, $gensetRealTimeData->getI3() ?? 0],
            //'Power'   => [$lastRecord[0]['P'] ?? 0, $lastRecord[0]['Q'] ?? 0, $lastRecord[0]['S'] ?? 0],
            //'Cosfi'    => $lastRecord[0]['Cosfi'] ?? 0,
            // 'NMI'     => [$NMIDay[0]['NMID'] ?? 0, $NMIMonth[0]['NMIM'] ?? 0, $NMIYear[0]['NMIY'] ?? 0],
            'NPS'     => [$npsd, $npsm, $npsy],
            // 'TEP'     => [$lastRecord[0]['TEP'] ?? 0, $tepd, $tepm, $tepy],
            'TRH'     => [$lastRecord[0]['TRH'] ?? 0, $trhd, $trhm, $trhy],
            // 'FC'      => [$FCD[0]['FC'] ?? 0, $FCM[0]['FC'] ?? 0, $FCY[0]['FC'] ?? 0],
            // 'POE'     => $poe,
            // 'prevPOE' => $prev_poe,
            //'Freq'    => $gensetRealTimeData->getFreq() ?? 0,
            //'Idiff'   => $gensetRealTimeData->getIDiff() ?? 0,
            'Level'       => [$gensetRealTimeData->getFuelLevel() ?? 0, $gensetRealTimeData->getWaterLevel() ?? 0, $gensetRealTimeData->getOilLevel() ?? 0],
            //'Pressure'       => [$gensetRealTimeData->getAirPressure() ?? 0, $gensetRealTimeData->getOilPressure() ?? 0],
            'Temp'       => [$gensetRealTimeData->getWaterTemperature() ?? 0, $gensetRealTimeData->getCoolerTemperature() ?? 0],
            //'EngineSpeed' => $gensetRealTimeData->getEngineSpeed() ?? 0,
            //'BattVolt' => $gensetRealTimeData->getBattVoltage() ?? 0,
            'HTM' => $gensetRealTimeData->getHoursToMaintenance() ?? 0,
            //'CGCR'       => [$gensetRealTimeData->getCg() ?? 0, $gensetRealTimeData->getCr() ?? 0],
            'Gensetrunning' => $gensetRealTimeData->getGensetRunning() ?? 0,
            //'MainsPresence' => $gensetRealTimeData->getMainsPresence() ?? 0,
            'MaintenanceRequest' => $gensetRealTimeData->getMaintenanceRequest() ?? 0,
            // 'LowFuel' => $gensetRealTimeData->getLowFuel() ?? 0,
            // 'PresenceWaterInFuel' => $gensetRealTimeData->getPresenceWaterInFuel() ?? 0,
            // 'Overspeed' => $gensetRealTimeData->getOverspeed() ?? 0,
            // 'FreqAlarm'       => [$gensetRealTimeData->getMaxFreq() ?? 0, $gensetRealTimeData->getMinFreq() ?? 0],
            // 'VoltAlarm'       => [$gensetRealTimeData->getMaxVolt() ?? 0, $gensetRealTimeData->getMinVolt() ?? 0],
            // 'BattVoltAlarm'       => [$gensetRealTimeData->getMaxBattVolt() ?? 0, $gensetRealTimeData->getMinBattVolt() ?? 0],
            // 'Overload' => $gensetRealTimeData->getOverload() ?? 0,
            // 'ShortCircuit' => $gensetRealTimeData->getShortCircuit() ?? 0,
            // 'IncSeq'       => [$gensetRealTimeData->getMainsIncSeq() ?? 0, $gensetRealTimeData->getGensetIncSeq() ?? 0],
            // 'DifferentialIntervention' => $gensetRealTimeData->getDifferentialIntervention() ?? 0,
            'Date1' => $gensetRealTimeData->getDateTime() ?? '',
            'date' => $date,
            //'Mix_PSCosfi'            => [$S, $P, $Cosfi],
            // 'Load_Level'    => $S
            //'ActivePower'            => $P,
            //'Apparent Power'         => $S,
            //'Cosfi'            => $Cosfi,

        );
    }

    public function getGensetDataForSiteProDashBoard()
    {
        $TEPdata = $this->manager->createQuery("SELECT MAX(d.totalEnergy) - MIN(NULLIF(d.totalEnergy,0)) AS TEP
                                        FROM App\Entity\GensetData d
                                        JOIN d.smartMod sm 
                                        WHERE d.dateTime BETWEEN :startDate AND :endDate
                                        AND sm.id = :smartModId         
                                        ")
            ->setParameters(array(
                'startDate'    => $this->startDate->format('Y-m-d H:i:s'),
                'endDate'      => $this->endDate->format('Y-m-d H:i:s'),
                'smartModId'   => $this->gensetMod->getId()
            ))
            ->getResult();
        $totalTEP = $TEPdata[0]['TEP'] ?? 0;

        if ($totalTEP === 0) {
            $TEPdata = $this->manager->createQuery("SELECT SUM(d.eaa + d.eab + d.eac) AS TEP
                                        FROM App\Entity\GensetData d
                                        JOIN d.smartMod sm 
                                        WHERE d.dateTime BETWEEN :startDate AND :endDate
                                        AND sm.id = :smartModId         
                                        ")
                ->setParameters(array(
                    'startDate'    => $this->startDate->format('Y-m-d H:i:s'),
                    'endDate'      => $this->endDate->format('Y-m-d H:i:s'),
                    'smartModId'   => $this->gensetMod->getId()
                ))
                ->getResult();

            $totalTEP = $TEPdata[0]['TEP'] ?? 0;
        }

        $totalTEP = floatval(number_format((float) $totalTEP, 2, '.', ''));

        $TEPdata = $this->manager->createQuery("SELECT SUBSTRING(d.dateTime,1,10) as dat, MAX(d.totalEnergy) - MIN(NULLIF(d.totalEnergy,0)) AS TEP
                                        FROM App\Entity\GensetData d
                                        JOIN d.smartMod sm 
                                        WHERE d.dateTime BETWEEN :startDate AND :endDate
                                        AND sm.id = :smartModId   
                                        GROUP BY dat
                                        ORDER BY dat ASC                
                                        ")
            ->setParameters(array(
                'startDate'    => $this->startDate->format('Y-m-d H:i:s'),
                'endDate'      => $this->endDate->format('Y-m-d H:i:s'),
                'smartModId'   => $this->gensetMod->getId()
            ))
            ->getResult();

        $date = [];
        $TEP  = [];
        foreach ($TEPdata as $d) {
            $date[]    = $d['dat'];
            $TEP[]     = $d['TEP'];
        }

        $LoadMaxdata = $this->manager->createQuery("SELECT MAX(d.smoy) AS Smax
                                        FROM App\Entity\GensetData d
                                        JOIN d.smartMod sm 
                                        WHERE d.dateTime BETWEEN :startDate AND :endDate
                                        AND sm.id = :smartModId        
                                        ")
            ->setParameters(array(
                'startDate'    => $this->startDate->format('Y-m-d H:i:s'),
                'endDate'      => $this->endDate->format('Y-m-d H:i:s'),
                'smartModId'     => $this->gensetMod->getId()
            ))
            ->getResult();
        $loadMax = 0.0;
        if (count($LoadMaxdata) > 0 && $this->gensetMod->getPower() > 0) $loadMax = ($LoadMaxdata[0]['Smax'] * 100.0) / $this->gensetMod->getPower();

        $loadMax = floatval(number_format((float) $loadMax, 2, '.', ''));

        $gensetkWDataQuery = $this->manager->createQuery("SELECT d.dateTime as dat, d.p AS Pmoy
                                        FROM App\Entity\GensetData d
                                        JOIN d.smartMod sm 
                                        WHERE d.dateTime BETWEEN :startDate AND :endDate
                                        AND sm.id = :smartModId         
                                        ")
            ->setParameters(array(
                'startDate'    => $this->startDate->format('Y-m-d H:i:s'),
                'endDate'      => $this->endDate->format('Y-m-d H:i:s'),
                'smartModId'   => $this->gensetMod->getId()
            ))
            ->getResult();
        // dump($gensetkWDataQuery);
        $gensetkW     = [];
        $gensetkWDate = [];
        foreach ($gensetkWDataQuery as $d) {
            $gensetkWDate[] = $d['dat']->format('Y-m-d H:i:s');
            $gensetkW[]     = floatval(number_format((float) $d['Pmoy'], 2, '.', ''));
        }

        // ######## Récupération des données de consommation et d'approvisionnement de Fuel
        $fuelData = $this->getConsoFuelData();
        //dump($fuelData);

        // ######## Récupération des données temps réel du module Genset
        $gensetRealTimeData = $this->manager->getRepository(GensetRealTimeData::class)->findOneBy(['smartMod' => $this->gensetMod->getId()]) ?? new GensetRealTimeData();
        //dump($gensetRealTimeData);
        $last_update = $gensetRealTimeData->getDateTime() ? $gensetRealTimeData->getDateTime()->format('d M Y H:i:s') : '-';
        return array(
            'Power'   => $gensetRealTimeData->getP() ?? 0,
            'last_update' => $last_update,
            // 'Level'       => [$gensetRealTimeData->getFuelLevel() ?? 0, $gensetRealTimeData->getWaterLevel() ?? 0, $gensetRealTimeData->getOilLevel() ?? 0],
            'CGCR'       => [
                'CG'    =>  $gensetRealTimeData->getCg() ?? 0,
                'CR'    =>  $gensetRealTimeData->getCr() ?? 0
            ],
            'loadMax'           => $loadMax,
            'currentTEP'        => $totalTEP,
            'currentConsoFuel'  => $fuelData['currentConsoFuel'],
            'dureeFonctionnement'  => $fuelData['dureeFonctionnement'],
            'dayBydayTEPData' => [
                'date'  => $date,
                "TEP"   => $TEP
            ],
            'loadProfileData' => [
                'date' => $gensetkWDate,
                "kW"   => $gensetkW
            ]
        );
    }
    /**
     * Get smart Module de type Genset
     *
     * @return  SmartMod
     */
    public function getGensetMod()
    {
        return $this->gensetMod;
    }

    /**
     * Set smart Module de type Genset
     *
     * @param  SmartMod  $gensetMod  Smart Module de type Genset
     *
     * @return  self
     */
    public function setGensetMod(SmartMod $gensetMod)
    {
        $this->gensetMod = $gensetMod;

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
}
