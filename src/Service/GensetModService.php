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

    private $intervalTime = 5.0/60.0 ;

    private $fuelCapacity = 100.0;
    
    private $fuelprice = 575.0;

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
                'smartModId'    => $this->gensetMod->getId()
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

        $npsProgress = ($nps_lastmonth > 0) ? ($npsm - $nps_lastmonth) / $nps_lastmonth : 'INF';

        // ######## Récupération des données de consommation et d'approvisionnement de Fuel
        $fuelData = $this->getConsoFuelData();
        $consoFuelXAF = $fuelData['currentConsoFuel'] * $this->gensetMod->getFuelPrice();
        $consoFuelXAF = floatval(number_format($consoFuelXAF, 2, '.', ''));

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
            'currentConsoFuel'            => $fuelData['currentConsoFuel'],
            'currentConsoFuelXAF'         => $consoFuelXAF,
            'currentConsoFuelProgress'    => $fuelData['currentConsoFuelProgress'],
            'currentApproFuel'            => $fuelData['currentApproFuel'],
            'currentApproFuelProgress'    => $fuelData['currentApproFuelProgress'],
            'TUG'                         => $fuelData['TUG'],
            'TUGProgress'                 => $fuelData['TUGProgress'],
            'dureeFonctionnement'         => $fuelData['dureeFonctionnement'],
            'dureeFonctionnementProgress' => $fuelData['dureeFonctionnementProgress'],
            'dayBydayConsoData' => [
                'dateConso'   => $fuelData['dayBydayConsoData']['dateConso'],
                "consoFuel"   => $fuelData['dayBydayConsoData']['consoFuel'],
                "approFuel"   => $fuelData['dayBydayConsoData']['approFuel'],
                "duree"       => $fuelData['dayBydayConsoData']['duree']
            ],
            'statsDureeFonctionnement' => $fuelData['statsDureeFonctionnement'],
            'TRH'  => [$trhm, $trh_lastmonth],
            'NPS'  => [$npsm, $npsProgress],
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
        // dump($length);

        $currentConsoFuel = 0;
        $currentApproFuel = 0;

        $consoFuelDayByDay = [];
        $approFuelDayByDay = [];

        $dureeDayByDay  = [];
        $dataOrderByDay = []; //Tableau des valeurs jour après jour

        $lastConsoFuel = 0;
        $lastApproFuel = 0;
        $lastDuree     = 0;

        $day = [];
        $date   = [];
        $data   = [];
        $dataFL = [];
        $dataFLXAF = [];

        // ######## Récupération des données de courbe pour le mois en cours ########
        if($this->gensetMod->getSubType() !== 'Inv'){
            $dataQuery = $this->manager->createQuery("SELECT d.dateTime as dat, d.fuelLevel as FL, d.totalRunningHours as TRH
                                            FROM App\Entity\GensetData d 
                                            JOIN d.smartMod sm
                                            WHERE d.dateTime BETWEEN :startDate AND :endDate
                                            AND sm.id = :smartModId
                                            AND d.fuelLevel IS NOT NULL
                                            ORDER BY dat ASC
                                            ")
                ->setParameters(array(
                    //'length'     => $length,
                    'startDate'  => $this->startDate->format('Y-m-d H:i:s'),
                    'endDate'    => $this->endDate->format('Y-m-d H:i:s'),
                    'smartModId' => $this->gensetMod->getId()
                ))
                ->getResult();
        
            /*else if($this->gensetMod->getSubType() !== 'ModBus'){
                
                $dataQuery = $this->manager->createQuery("SELECT d.dateTime as dat, SUM(d.fuelLevel) as FL, COUNT(d.p) as TRH
                                            FROM App\Entity\GensetData d
                                            JOIN d.smartMod sm 
                                            WHERE d.dateTime BETWEEN :startDate AND :endDate
                                            AND sm.id = :smartModId         
                                            AND d.fuelLevel IS NOT NULL
                                            AND d.p > 1
                                            GROUP BY dat
                                            ")
                    ->setParameters(array(
                        'startDate'    => $this->startDate->format('Y-m-d H:i:s'),
                        'endDate'      => $this->endDate->format('Y-m-d H:i:s'),
                        'smartModId'   => $this->gensetMod->getId()
                    ))
                    ->getResult();
            }*/
            // dump($dataQuery);
            // $FL   = [];
            // $TRH  = [];
            
            foreach ($dataQuery as $d) {
                $date[]    = $d['dat']->format('Y-m-d H:i:s');
                $dataFL[]  = $d['FL'];
                $dataFLXAF[]  = (($d['FL']* $this->fuelCapacity)/100.0) * $this->fuelPrice;
                // $TRH[]     = $d['TRH'];
                $data[$d['dat']->format('Y-m-d H:i:s')] = [
                    'FL'    => $d['FL'],
                    'TRH'   => $d['TRH']
                ];
                //$Cosfi[]   = number_format((float) $d['cosfi'], 2, '.', '');
            }
            // dump($data);
            $dayRecord = $this->manager->createQuery("SELECT SUBSTRING(d.dateTime,1,10) as dat
                                            FROM App\Entity\GensetData d 
                                            JOIN d.smartMod sm
                                            WHERE d.dateTime BETWEEN :startDate AND :endDate
                                            AND sm.id = :smartModId
                                            GROUP BY dat
                                            ORDER BY dat ASC
                                            ")
                ->setParameters(array(
                    'startDate'  => $this->startDate->format('Y-m-d H:i:s'),
                    'endDate'    => $this->endDate->format('Y-m-d H:i:s'),
                    'smartModId' => $this->gensetMod->getId(),
                ))
                ->getResult();

            // dump($dayRecord);
            
            foreach ($dayRecord as $d) {
                $day[]    = $d['dat'];
            }

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

            // dump($dataOrderByDay);

            foreach ($dataOrderByDay as $key => $value) {
                $consoFuel_ = 0;
                $approFuel_ = 0;

                $T_Appro  = [] ; //Tableau des instants d’approvisionnement
                $T_Appro[0] = 0 ; 
                $j = 1 ; 
                            
                //Données de la courbe de durée de fonctionnement jour après jour
                if (array_key_exists('TRH', $value)) {
                    if (end($value['TRH']) !== false && reset($value['TRH']) !== false) {
                        // $dureeDayByDay[$key] = abs(end($value['TRH']) - reset($value['TRH']));
                        $dureeDay = abs(end($value['TRH']) - reset($value['TRH']));
                        
                        // dump($key); 
                                            
                        if($dureeDay <= 0.0){
                            if($this->gensetMod->getSubType() == 'Inv' || $this->gensetMod->getSubType() == 'Inv+FL'){
                                $dataQuery = $this->manager->createQuery("SELECT SUM(d.p)*:time AS TRH
                                                        FROM App\Entity\GensetData d
                                                        JOIN d.smartMod sm 
                                                        WHERE d.dateTime LIKE :day_
                                                        AND sm.id = :smartModId         
                                                        AND d.p > 0.05
                                                        ")
                                ->setParameters(array(
                                    'day_'         => $key . '%',
                                    'time'         => $this->intervalTime,
                                    'smartModId'   => $this->gensetMod->getId()
                                ))
                                ->getResult();
                                
                                // dump($dataQuery);
                                if(count($dataQuery) > 0){
                                    
                                    $dureeDay = floatval($dataQuery[0]['TRH']);
                                    
                                    if($dureeDay <= 0.0){
                                        if($this->gensetMod->getSubType() == 'Inv+FL'){
                                            
                                            $dataQuery = $this->manager->createQuery("SELECT MAX(NULLIF(d.totalRunningHours,0)) - MIN(NULLIF(d.totalRunningHours,0)) AS TRH
                                                                        FROM App\Entity\GensetData d
                                                                        JOIN d.smartMod sm 
                                                                        WHERE d.dateTime LIKE :day_
                                                                        AND sm.id = :smartModId
                                                                        AND d.totalRunningHours IS NOT NULL
                                                                        ")
                                                ->setParameters(array(
                                                    'day_'    => $key .'%',
                                                    'smartModId'   => $this->gensetMod->getId()
                                                ))
                                                ->getResult();
                                            
                                                // dump($dataQuery);
                                                
                                            $dureeDay = floatval($dataQuery[0]['TRH']);
                                            // dump("======== Inv+FL ========"); 
                                            
                                        } 
                                    }
                                    // else dump("======== Inv ========"); 
                                            
                                    
                                }
                            }
                            // else dump("======== Modbus || FL ========"); 
                        }
                        // dump($dureeDay); 
                                            
                        $dureeDayByDay[] = floatval(number_format((float) $dureeDay, 2, '.', ''));
                    }
                }

                //Données des courbe de consommation et approvisionnement jour après jour
                if (array_key_exists('FL', $value)) {
                    $temp = $value['FL']; //Tableau tampon
                    if (count($temp) > 0) {
                        if($this->gensetMod->getSubType() == 'ModBus'){
                            for ($i = 0; $i < count($temp) - 1; $i++) {
                                $diff = abs($temp[$i + 1] - $temp[$i]);
                                if ($temp[$i + 1] >= $temp[$i]) {
                                    $approFuel_ += $diff;
                                } else {
                                    $consoFuel_ += $diff;
                                }
                                // if ($temp[$i + 1] - 5 >= $temp[$i]) {
                                //     $approFuel_ += $diff;
                                // } else if ($temp[$i] - $temp[$i + 1] >= 5 ){
                                    //     $consoFuel_ += $diff;
                                    // }
                                    
                            }
                        }else { //if($this->gensetMod->getSubType() !== 'ModBus')
                            // dump($temp[0]) ;
                            $N = count($temp);
                            for ($i=0 ; $i < $N - 3 ; $i ++){ // N est le volume de données sur la fenêtre de temps choisie = size(temp[])
                                
                                if  ( ($temp[$i+1]  - $temp[$i]) > 5 && $temp[$i+2] - $temp[$i] > 5 && ($temp[$i+3] - $temp[$i]) > 5  ){ // On compare avec les trois valeurs suivantes pour éviter les valeurs aberrantes 
                                    
                                    $T_Appro[$j] = $i ; // On enregistre tous les instants d’approvisionnement
                                    $j = $j + 1 ; 
                                    $approFuel_ = $approFuel_ + $temp[$i+1] - $temp[$i] ; // On calcul le volume d’approvisionnement
                                }
                                        
                            }
                            // dump(count($T_Appro));
                            // dump($j) ; 
                            if ( count($T_Appro) > 1 ){
                                
                                if ( $temp[0]  - $temp[$T_Appro[1]] > 2){
                                    $consoFuel_ = $consoFuel_ + $temp[0]  - $temp[$T_Appro[1]] ;
                                }
                                if ( count($T_Appro) > 2 ){
                                    for ($i=0 ; $i < count($T_Appro) - 2 ; $i++){ // N est le volume de données sur la fenêtre de temps choisie
                                        if ( $temp[$T_Appro[$i+1]+1]  - $temp[$T_Appro[$i+2]] > 2){
                                            $consoFuel_ = $consoFuel_ + $temp[$T_Appro[$i+1]+1]  - $temp[$T_Appro[$i+2]] ;
                                        } 	
                                    
                                    }
                                }
                                
                                if ( $temp[$T_Appro[$j-1]+1]  - $temp[$N-1] > 2){
                                    $consoFuel_ = $consoFuel_ + $temp[$T_Appro[$j-1]+1]   - $temp[$N-1] ;
                                } 
                            }
                            else{
                                if ( $temp[0]  - $temp[$N-1] > 2){
                                    $consoFuel_ = $consoFuel_ + $temp[0]  - $temp[$N-1] ;
                                } 
                            }
                        }
                    }
                }

                $currentConsoFuel += $consoFuel_;
                $currentApproFuel += $approFuel_;

                // $consoFuelDayByDay[$key] = $consoFuel_;
                // $approFuelDayByDay[$key] = $approFuel_;
                $consoFuelDayByDay[] = floatval(number_format((float) ($consoFuel_* $this->fuelCapacity)/100.0, 2, '.', ''));
                $approFuelDayByDay[] = floatval(number_format((float) ($approFuel_* $this->fuelCapacity)/100.0, 2, '.', ''));
            }

            // dump($dureeDayByDay);

            $currentConsoFuel = ($currentConsoFuel * $this->fuelCapacity)/100.0;
            $currentApproFuel = ($currentApproFuel * $this->fuelCapacity)/100.0;

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

            $lastConsoFuel = ($lastConsoFuel * $this->fuelCapacity)/100.0;
            $lastApproFuel = ($lastApproFuel * $this->fuelCapacity)/100.0;
        }else if($this->gensetMod->getSubType() == 'Inv' || $this->gensetMod->getSubType() == 'Inv+FL'){
            $dataQuery = $this->manager->createQuery("SELECT SUBSTRING(d.dateTime,1,10) as dat, COUNT(d.p) AS NB_Mins
                                    FROM App\Entity\GensetData d
                                    JOIN d.smartMod sm 
                                    WHERE d.dateTime BETWEEN :startDate AND :endDate
                                    AND sm.id = :smartModId         
                                    AND d.p > 0.05
                                    GROUP BY dat
                                    ORDER BY dat ASC
                                    ")
            ->setParameters(array(
                'startDate'    => $this->startDate->format('Y-m-d H:i:s'),
                'endDate'      => $this->endDate->format('Y-m-d H:i:s'),
                'smartModId'   => $this->gensetMod->getId()
            ))
            ->getResult();
            
            // dump($dataQuery);
            if(count($dataQuery) > 0){
                //if(array_key_exists("NB_Mins", $dataQuery[0])){
                    $dureeDayByDay = [];
                    foreach ($dataQuery as $dat) {
                        // $dureeDayByDay[] = intval($dat['NB_Mins']) * $this->intervalTime;
                        $dureeDayByDay[] = floatval(number_format((float) intval($dat['NB_Mins']) * $this->intervalTime, 2, '.', ''));
                        $day[]    = $dat['dat'];
                    }
                    // $duree = $data[0]['NB_Mins'] * $this->intervalTime;
                    if(count($dureeDayByDay) > 0) $duree = array_sum($dureeDayByDay);
                    
                    // dump($dureeDayByDay); 
                    
                    if($duree <= 0.0){
                        dump($duree); 
                        if($this->gensetMod->getSubType() == 'Inv+FL'){
                            
                            $dataQuery = $this->manager->createQuery("SELECT SUBSTRING(d.dateTime,1,10) as dat, 
                                                        MAX(NULLIF(d.totalRunningHours,0)) - MIN(NULLIF(d.totalRunningHours,0)) AS TRH
                                                        FROM App\Entity\GensetData d
                                                        JOIN d.smartMod sm 
                                                        WHERE d.dateTime BETWEEN :startDate AND :endDate
                                                        AND sm.id = :smartModId
                                                        AND d.totalRunningHours IS NOT NULL
                                                        GROUP BY dat
                                                        ORDER BY dat ASC
                                                        ")
                                ->setParameters(array(
                                    'startDate'    => $this->startDate->format('Y-m-d H:i:s'),
                                    'endDate'      => $this->endDate->format('Y-m-d H:i:s'),
                                    'smartModId'   => $this->gensetMod->getId()
                                ))
                                ->getResult();
                            
                            dump($dataQuery);
                            $dureeDayByDay = [];
                            foreach ($dataQuery as $dat) {
                                // $dureeDayByDay[] = floatval($dat['TRH']);
                                $dureeDayByDay[] = floatval(number_format((float) floatval($dat['TRH']), 2, '.', ''));
                        
                            }
                            // dump($dureeDayByDay);
                            if(count($dureeDayByDay) > 0){
                                // dump($dureeDayByDay[0]['TRH']);
                                // $duree = abs(end($dureeDayByDay) - reset($dureeDayByDay));
                                $duree = array_sum($dureeDayByDay);
                            }
                        } 
                    }
                //}
            }
        }
        // ========== Détermination du temps total de fonctionnement du GE sur la période de date passée en paramètre et le last période correspondant
                    
        $workingTimeQuery = $this->manager->createQuery("SELECT COUNT(DISTINCT d.dateTime)/:time AS WT
                                            FROM App\Entity\GensetData d
                                            JOIN d.smartMod sm
                                            WHERE sm.id = :smartModId
                                            AND d.dateTime BETWEEN :startDate AND :endDate")
            ->setParameters(array(
                'time'       => $this->intervalTime,
                'startDate'  => $this->startDate->format('Y-m-d H:i:s'),
                'endDate'    => $this->endDate->format('Y-m-d H:i:s'),
                'smartModId' => $this->gensetMod->getId()
            ))
            ->getResult();
        // dump($workingTimeQuery);
        $totalWorkingHours = count($workingTimeQuery) > 0 ? $workingTimeQuery[0]['WT'] ?? 0 : 0;
        $totalWorkingHours = floatval($totalWorkingHours);

        $lastWorkingTimeQuery = $this->manager->createQuery("SELECT COUNT(DISTINCT d.dateTime)/:time AS WT
                                            FROM App\Entity\GensetData d
                                            JOIN d.smartMod sm
                                            WHERE sm.id = :smartModId
                                            AND d.dateTime BETWEEN :lastStartDate AND :lastEndDate")
            ->setParameters(array(
                'time'          => $this->intervalTime,
                'lastStartDate' => $lastStartDate->format('Y-m') . '%',
                'lastEndDate'   => $lastEndDate->format('Y-m-d H:i:s'),
                'smartModId'    => $this->gensetMod->getId()
            ))
            ->getResult();
        // dump($lastWorkingTimeQuery);
        $lastTotalWorkingHours = count($lastWorkingTimeQuery) > 0 ? $lastWorkingTimeQuery[0]['WT'] ?? 0 : 0;
        $lastTotalWorkingHours = floatval($lastTotalWorkingHours);
        
        $duree = -0.0;
        if(count($dureeDayByDay) > 0) $duree = array_sum($dureeDayByDay);
        // dump($dureeDayByDay);        
        // if($this->gensetMod->getSubType() !== 'ModBus' || !strpos($this->gensetMod->getSubType(), 'FL') !== false){
        //if($this->gensetMod->getSubType() !== 'ModBus' || $this->gensetMod->getSubType() !== 'FL'){
        /*if($duree <= 0.0){
            if($this->gensetMod->getSubType() == 'Inv' || $this->gensetMod->getSubType() == 'Inv+FL'){
                $dataQuery = $this->manager->createQuery("SELECT SUBSTRING(d.dateTime,1,10) as dat, COUNT(d.p) AS NB_Mins
                                        FROM App\Entity\GensetData d
                                        JOIN d.smartMod sm 
                                        WHERE d.dateTime BETWEEN :startDate AND :endDate
                                        AND sm.id = :smartModId         
                                        AND d.p > 0.05
                                        GROUP BY dat
                                        ORDER BY dat ASC
                                        ")
                ->setParameters(array(
                    'startDate'    => $this->startDate->format('Y-m-d H:i:s'),
                    'endDate'      => $this->endDate->format('Y-m-d H:i:s'),
                    'smartModId'   => $this->gensetMod->getId()
                ))
                ->getResult();
                
                dump($dataQuery);
                if(count($dataQuery) > 0){
                    //if(array_key_exists("NB_Mins", $dataQuery[0])){
                        $dureeDayByDay = [];
                        foreach ($dataQuery as $dat) {
                            $dureeDayByDay[] = intval($dat['NB_Mins']) * $this->intervalTime;
                        }
                        // $duree = $data[0]['NB_Mins'] * $this->intervalTime;
                        if(count($dureeDayByDay) > 0) $duree = array_sum($dureeDayByDay);
                        
                        // dump($dureeDayByDay); 
                        
                        if($duree <= 0.0){
                            dump($duree); 
                            if($this->gensetMod->getSubType() == 'Inv+FL'){
                                
                                $dataQuery = $this->manager->createQuery("SELECT SUBSTRING(d.dateTime,1,10) as dat, 
                                                            MAX(NULLIF(d.totalRunningHours,0)) - MIN(NULLIF(d.totalRunningHours,0)) AS TRH
                                                            FROM App\Entity\GensetData d
                                                            JOIN d.smartMod sm 
                                                            WHERE d.dateTime BETWEEN :startDate AND :endDate
                                                            AND sm.id = :smartModId
                                                            AND d.totalRunningHours IS NOT NULL
                                                            GROUP BY dat
                                                            ORDER BY dat ASC
                                                            ")
                                    ->setParameters(array(
                                        'startDate'    => $this->startDate->format('Y-m-d H:i:s'),
                                        'endDate'      => $this->endDate->format('Y-m-d H:i:s'),
                                        'smartModId'   => $this->gensetMod->getId()
                                    ))
                                    ->getResult();
                                
                                dump($dataQuery);
                                $dureeDayByDay = [];
                                foreach ($dataQuery as $dat) {
                                    $dureeDayByDay[] = floatval($dat['TRH']);
                                }
                                // dump($dureeDayByDay);
                                if(count($dureeDayByDay) > 0){
                                    // dump($dureeDayByDay[0]['TRH']);
                                    // $duree = abs(end($dureeDayByDay) - reset($dureeDayByDay));
                                    $duree = array_sum($dureeDayByDay);
                                }
                            } 
                        }
                    //}
                }
            }
        }*/
                
        $currentConsoFuelProgress = ($lastConsoFuel > 0) ? ($lastConsoFuel - $lastConsoFuel) / $lastConsoFuel : 'INF';
        $currentApproFuelProgress = ($lastApproFuel > 0) ? ($currentApproFuel - $lastApproFuel) / $lastApproFuel : 'INF';
        $dureeProgress            = ($lastDuree > 0) ? ($duree - $lastDuree) / $lastDuree : 'INF';
        
        $TUG         = $totalWorkingHours > 0 ? ($duree / $totalWorkingHours)*100 : 0;
        $lastTUG     = $lastTotalWorkingHours > 0 ? ($lastDuree / $lastTotalWorkingHours)*100 : 0;
        $TUGProgress = ($lastTUG > 0) ? ($TUG - $lastTUG) / $lastTUG : 'INF';
        
        // dump($this->hoursandmins(2.4996)); 
        
        $duree       = $this->hoursandmins($duree);

        //Données de la courbe de durée de fonctionnement jour après jour
        $dureeTotale   = -0.0;
        $dureeMoyenne  = -0.0;
        $dureeMediane  = -0.0;
        $dureeMax      = -0.0;
        $dureeMin      = -0.0;
        $dureeQ1       = -0.0;
        $dureeQ3       = -0.0;

        // dump($duree); 
        // dump($dureeDayByDay); 
        $dureeDayByDayFilter = [];
        if (count($dureeDayByDay) > 0) {
            $dureeDayByDayFilter = array_filter($dureeDayByDay); //Filtrage du tableau pour supprimer les valeurs nulles
            // dump($dureeDayByDayFilter); 
            if (count($dureeDayByDayFilter) > 0) {
                sort($dureeDayByDayFilter);
                $n = count($dureeDayByDayFilter) - 1;
                $dureeTotale = array_sum($dureeDayByDayFilter);
                $dureeMin = min($dureeDayByDayFilter);
                // $q1 = floor(($n + 3) / 4) + 1;
                // $q2 = floor(($n + 1) / 2) + 1;
                // $q2 = ceil($n / 2);
                $dureeMoyenne = $this->mmmrv($dureeDayByDayFilter, 'mean');
                $dureeMediane = $this->mmmrv($dureeDayByDayFilter, 'median');
                // $q3 = floor(($n + 1) / 4) + 1;
                $dureeMax = max($dureeDayByDayFilter);
                $q1 = ceil($n / 4);
                $dureeQ1 = $dureeDayByDayFilter[$q1];
                $q3 = ceil((3 * $n) / 4);
                $dureeQ3 = $dureeDayByDayFilter[$q3];
                $dQ = $dureeQ3 - $dureeQ1;
            }
        }
        
        $dureeTotale  = $this->hoursandmins($dureeTotale);
        $dureeMoyenne = $this->hoursandmins($dureeMoyenne);
        $dureeMediane = $this->hoursandmins($dureeMediane);
        $dureeMin     = $this->hoursandmins($dureeMin);
        $dureeMax     = $this->hoursandmins($dureeMax);
        $dureeQ1      = $this->hoursandmins($dureeQ1);
        $dureeQ3      = $this->hoursandmins($dureeQ3);
        // dump($dureeDayByDayFilter);
        
        return array(
            'currentConsoFuel'              => $currentConsoFuel,
            'dureeFonctionnement'           => $duree,
            'dureeFonctionnementProgress'   => $dureeProgress,
            'currentConsoFuelProgress'      => floatval(number_format((float) $currentConsoFuelProgress, 2, '.', '')),
            'currentApproFuel'              => $currentApproFuel,
            'currentApproFuelProgress'      => floatval(number_format((float) $currentApproFuelProgress, 2, '.', '')),
            'TUG'                           => floatval(number_format((float) $TUG, 2, '.', '')),
            'TUGProgress'                   => floatval(number_format((float) $TUGProgress, 2, '.', '')),
            'dayBydayConsoData' => [
                'dateConso'   => $day,
                "consoFuel"   => $consoFuelDayByDay,
                "approFuel"   => $approFuelDayByDay,
                "duree"       => $dureeDayByDay,
            ],
            'dataFL'    => [
                'date'  => $date,
                'FL'    => $dataFL,
                'XAF'   => $dataFLXAF
            ],
            'statsDureeFonctionnement' => [
                'totale' => $dureeTotale, 
                'mean'   => $dureeMoyenne, 
                'median' => $dureeMediane, 
                'min'    => $dureeMin, 
                'max'    => $dureeMax, 
                'Q1'     => $dureeQ1, 
                'Q3'     => $dureeQ3
            ]
        );
    }

    public function getNPSstats()
    {
        // ========= Détermination de la longueur de la datetime =========
        $length = 10; //Si endDate > startDate => regoupement des données par jour de la fenêtre de date
        if ($this->endDate->format('Y-m-d') == $this->startDate->format('Y-m-d')) $length = 13; //Si endDate == startDate => regoupement des données par heure du jour choisi
        // dump($length);

        $date          = [];
        $NPSDayByDay   = [];
        
        $NPSDayByDayRecordQuery = $this->manager->createQuery("SELECT SUBSTRING(d.dateTime,1,10) AS dat, MAX(NULLIF(d.nbPerformedStartUps,0)) - MIN(NULLIF(d.nbPerformedStartUps,0)) AS NPS
                                        FROM App\Entity\GensetData d
                                        JOIN d.smartMod sm 
                                        WHERE d.dateTime BETWEEN :startDate AND :endDate
                                        AND sm.id = :smartModId    
                                        AND d.nbPerformedStartUps IS NOT NULL               
                                        GROUP BY dat
                                        ORDER BY dat ASC")
            ->setParameters(array(
                //'length'     => $length,
                'startDate'    => $this->startDate->format('Y-m-d H:i:s'),
                'endDate'      => $this->endDate->format('Y-m-d H:i:s'),
                'smartModId'   => $this->gensetMod->getId()
            ))
            ->getResult();
        
        // dump($NPSDayByDayRecordQuery);

        foreach ($NPSDayByDayRecordQuery as $d) {
            $date[]  = $d['dat'];
            $NPSDayByDay[]  = intval($d['NPS']) ?? 0;
        }

        // dump($date);
        // dump($NPSDayByDay);

        //Données de la courbe de durée de fonctionnement jour après jour
        $NPSTotal    = 0;
        $NPSMoyenne  = 0;
        $NPSMediane  = 0;
        $NPSMax      = 0;
        $NPSMin      = 0;
        $NPSQ1       = 0;
        $NPSQ3       = 0;

        $NPSDayByDayFilter = [];
        if (count($NPSDayByDay) > 0) {
            $NPSDayByDayFilter = $NPSDayByDay;
            // $NPSDayByDayFilter = array_map(function ($object) { return clone $object; }, $NPSDayByDay);
            if (count($NPSDayByDayFilter) > 0) {
                sort($NPSDayByDayFilter);
                // dump($NPSDayByDayFilter);
                // dump($NPSDayByDay);
                $n = count($NPSDayByDayFilter) - 1;
                $NPSTotal = array_sum($NPSDayByDayFilter);
                $NPSMin = min($NPSDayByDayFilter);
                // $q1 = floor(($n + 3) / 4) + 1;
                // $q2 = floor(($n + 1) / 2) + 1;
                // $q2 = ceil($n / 2);
                $NPSMoyenne = $this->mmmrv($NPSDayByDayFilter, 'mean');
                $NPSMediane = $this->mmmrv($NPSDayByDayFilter, 'median');
                // $q3 = floor(($n + 1) / 4) + 1;
                $NPSMax = max($NPSDayByDayFilter);
                $q1 = ceil($n / 4);
                $NPSQ1 = $NPSDayByDayFilter[$q1];
                $q3 = ceil((3 * $n) / 4);
                $NPSQ3 = $NPSDayByDayFilter[$q3];
                $dQ = $NPSQ3 - $NPSQ1;
            }
        }

        return array(
            'NPSchart' => [
                'date' => $date,
                'NPS'  => $NPSDayByDay
            ],
            'statsNPS' => [
                'total'  => $NPSTotal, 
                'mean'   => floatval(number_format((float) $NPSMoyenne, 2, '.', '')), 
                'median' => $NPSMediane, 
                'min'    => $NPSMin, 
                'max'    => $NPSMax, 
                'Q1'     => $NPSQ1, 
                'Q3'     => $NPSQ3
            ]
        );
    } 

    public function dataReport()
    {
        $fuelData = $this->getConsoFuelData();

        $npsStats = $this->getNPSstats();

//        $consoTotale  = '-';
        $consoMin     = '-';
        $consoMoyenne = '-';
        $consoMediane = '-';
        $consoMax     = '-';

//        $approTotale  = '-';
        $approMin     = '-';
        $approMoyenne = '-';
        $approMediane = '-';
        $approMax     = '-';

        if (count($fuelData['dayBydayConsoData']['consoFuel']) > 0) {
            $consoFilter = array_filter($fuelData['dayBydayConsoData']['consoFuel']); //Filtrage du tableau pour supprimer les valeurs nulles
            // dump($consoFilter);
            if (count($consoFilter) > 0) {
                sort($consoFilter);
                $n = count($consoFilter) - 1;
//                $consoTotale = array_sum($consoFilter);
                $consoMin = min($consoFilter);
                $consoMoyenne = $this->mmmrv($consoFilter, 'mean');
                $consoMediane = $this->mmmrv($consoFilter, 'median');
                $consoMax = max($consoFilter);
            }
        }

        if (count($fuelData['dayBydayConsoData']['approFuel']) > 0) {
            $approFilter = array_filter($fuelData['dayBydayConsoData']['approFuel']); //Filtrage du tableau pour supprimer les valeurs nulles
            // dump($approFilter);
            if (count($approFilter) > 0) {
                sort($approFilter);
                $n = count($approFilter) - 1;
//                $approTotale = array_sum($approFilter);
                $approMin = min($approFilter);
                $approMoyenne = $this->mmmrv($approFilter, 'mean');
                $approMediane = $this->mmmrv($approFilter, 'median');
                $approMax = max($approFilter);
            }
        }

        $Stats = [
            'total' => [
                'conso' => $fuelData['currentConsoFuel'],
                'appro' => $fuelData['currentApproFuel'],
                'trh'   => $fuelData['dureeFonctionnement'],
                'nps'   => $npsStats['statsNPS']['total'],
            ],
            'moyenne' => [
                'conso' => $consoMoyenne,
                'appro' => $approMoyenne,
                'trh'   => $fuelData['statsDureeFonctionnement']['mean'],
                'nps'   => $npsStats['statsNPS']['mean'],
            ],
            'médiane' => [
                'conso' => $consoMediane,
                'appro' => $approMediane,
                'trh'   => $fuelData['statsDureeFonctionnement']['median'],
                'nps'   => $npsStats['statsNPS']['median'],
            ],
            'min' => [
                'conso' => $consoMin,
                'appro' => $approMin,
                'trh'   => $fuelData['statsDureeFonctionnement']['min'],
                'nps'   => $npsStats['statsNPS']['min'],
            ],
            'max' => [
                'conso' => $consoMax,
                'appro' => $approMax,
                'trh'   => $fuelData['statsDureeFonctionnement']['max'],
                'nps'   => $npsStats['statsNPS']['max'],
            ],
        ];

        $dayData = [
            'Lundi'   => [
                'consoFuel' => "-",
                'approFuel' => "-",
                'TRH'       => "-",
                'NPS'       => "-",
            ],
            'Mardi'   => [
                'consoFuel' => "-",
                'approFuel' => "-",
                'TRH'       => "-",
                'NPS'       => "-",
            ],
            'Mercredi'      => [
                'consoFuel' => "-",
                'approFuel' => "-",
                'TRH'       => "-",
                'NPS'       => "-",
            ],
            'Jeudi'     => [
                'consoFuel' => "-",
                'approFuel' => "-",
                'TRH'       => "-",
                'NPS'       => "-",
            ],
            'Vendredi'       => [
                'consoFuel' => "-",
                'approFuel' => "-",
                'TRH'       => "-",
                'NPS'       => "-",
            ],
            'Samedi'      => [
                'consoFuel' => "-",
                'approFuel' => "-",
                'TRH'       => "-",
                'NPS'       => "-",
            ],
            'Dimanche'   => [
                'consoFuel' => "-",
                'approFuel' => "-",
                'TRH'       => "-",
                'NPS'       => "-",
            ],
        ];
//        $day = [];
//        dump($fuelData['dayBydayConsoData']);
        if (count($fuelData['dayBydayConsoData']) > 0) {
//            $day[]    = $fuelData['dayBydayConsoData']['dateConso'];

            foreach ($fuelData['dayBydayConsoData']['dateConso'] as $index => $value) {
                $consoFuel = $fuelData['dayBydayConsoData']['consoFuel'][$index];
                $approFuel = $fuelData['dayBydayConsoData']['approFuel'][$index];
//                dd($fuelData['dayBydayConsoData']['duree']);
                $duree     = $this->hoursandmins($fuelData['dayBydayConsoData']['duree'][$index]);
                $nps       = $npsStats['NPSchart']['NPS'][$index];

                switch (date('w', strtotime($value))) {
                    case 1:
                        $dayData['Lundi']['consoFuel'] = $consoFuel;
                        $dayData['Lundi']['approFuel'] = $approFuel;
                        $dayData['Lundi']['TRH']       = $duree;
                        $dayData['Lundi']['NPS']       = $nps;

                        break;
                    case 2:
                        $dayData['Mardi']['consoFuel'] = $consoFuel;
                        $dayData['Mardi']['approFuel'] = $approFuel;
                        $dayData['Mardi']['TRH']       = $duree;
                        $dayData['Mardi']['NPS']       = $nps;

                        break;
                    case 3:
                        $dayData['Mercredi']['consoFuel'] = $consoFuel;
                        $dayData['Mercredi']['approFuel'] = $approFuel;
                        $dayData['Mercredi']['TRH']       = $duree;
                        $dayData['Mercredi']['NPS']       = $nps;

                        break;
                    case 4:
                        $dayData['Jeudi']['consoFuel'] = $consoFuel;
                        $dayData['Jeudi']['approFuel'] = $approFuel;
                        $dayData['Jeudi']['TRH']       = $duree;
                        $dayData['Jeudi']['NPS']       = $nps;

                        break;
                    case 5:
                        $dayData['Vendredi']['consoFuel'] = $consoFuel;
                        $dayData['Vendredi']['approFuel'] = $approFuel;
                        $dayData['Vendredi']['TRH']       = $duree;
                        $dayData['Vendredi']['NPS']       = $nps;

                        break;
                    case 6:
                        $dayData['Samedi']['consoFuel'] = $consoFuel;
                        $dayData['Samedi']['approFuel'] = $approFuel;
                        $dayData['Samedi']['TRH']       = $duree;
                        $dayData['Samedi']['NPS']       = $nps;

                        break;
                    case 0:
                        $dayData['Dimanche']['consoFuel'] = $consoFuel;
                        $dayData['Dimanche']['approFuel'] = $approFuel;
                        $dayData['Dimanche']['TRH']       = $duree;
                        $dayData['Dimanche']['NPS']       = $nps;

                        break;
                    default:
                        break;
                }
            }
        }
        $consoFuelXAF = $fuelData['currentConsoFuel'] * $this->gensetMod->getFuelPrice();
        $consoFuelXAF = floatval(number_format($consoFuelXAF, 2, '.', ''));
        $approFuelXAF = $fuelData['currentApproFuel'] * $this->gensetMod->getFuelPrice();
        $approFuelXAF = floatval(number_format($approFuelXAF, 2, '.', ''));
        return array([
            'consoFuel'            => $fuelData['currentConsoFuel'],
            'consoFuelXAF'         => $consoFuelXAF,
            'approFuel'            => $fuelData['currentApproFuel'],
            'approFuelXAF'         => $approFuelXAF,
            'dureeFonctionnement'  => $fuelData['dureeFonctionnement'],

            'dayData' => $dayData,
            'stats'   => $Stats
        ]);

    }

    public function getDataForMonthDataTable()
    {
        $dataQuery = $this->manager->createQuery("SELECT d.dateTime as dat, d.fuelLevel as FL, d.totalRunningHours as TRH
                                            FROM App\Entity\GensetData d 
                                            JOIN d.smartMod sm
                                            WHERE d.dateTime LIKE :thisYear
                                            AND sm.id = :smartModId
                                            AND d.fuelLevel IS NOT NULL
                                            ORDER BY dat ASC
                                            ")
                ->setParameters(array(
                    //'length'     => $length,
                    'thisYear'   => date('Y') . '%',
                    'smartModId' => $this->gensetMod->getId()
                ))
                ->getResult();
        $date   = [];
        $data   = [];
        $dataFL = [];

        foreach ($dataQuery as $d) {
            $date[]    = $d['dat']->format('Y-m-d H:i:s');
            $dataFL[]  = $d['FL'];
            // $TRH[]     = $d['TRH'];
            $data[$d['dat']->format('Y-m-d H:i:s')] = [
                'FL'    => $d['FL'],
                'TRH'   => $d['TRH']
            ];
            //$Cosfi[]   = number_format((float) $d['cosfi'], 2, '.', '');
        }

        $totalRecordMonthByMonth = [];
        // $totalRecordMonthByMonth = count($date) * $this->intervalTime;
                    
        // dump($data);
        $monthRecord = $this->manager->createQuery("SELECT SUBSTRING(d.dateTime,1,7) as mois, COUNT(d.dateTime) AS Nb_Record
                                        FROM App\Entity\GensetData d 
                                        JOIN d.smartMod sm
                                        WHERE d.dateTime LIKE :thisYear
                                        AND sm.id = :smartModId
                                        AND d.fuelLevel IS NOT NULL
                                        GROUP BY mois
                                        ORDER BY mois ASC
                                        ")
            ->setParameters(array(
                'thisYear'   => date('Y') . '%',
                'smartModId' => $this->gensetMod->getId(),
            ))
            ->getResult();

        // dump($monthRecord);
        $month = [];
        foreach ($monthRecord as $d) {
            $month[]    = $d['mois'];
            $totalRecordMonthByMonth[$d['mois']] = $d['Nb_Record'];
        }
        // dump($month);
        // dump($totalRecordMonthByMonth);

        $dataOrderByMonth = []; //Tableau des valeurs mois après mois
        foreach ($data as $key => $value) {
            // dump($key);
            foreach ($month as $index => $val) {
                //dump($val);
                if (strpos($key, $val) !== false) { // On vérifie si le la sous-chaîne du mois est contenue dans la date
                    $dataOrderByMonth[$val]['FL'][]  = $value['FL'];
                    $dataOrderByMonth[$val]['TRH'][] = $value['TRH'];
                }
            }
        }

        // dump($dataOrderByMonth);

        // $currentConsoFuel = 0;
        // $currentApproFuel = 0;

        $monthData = [
            'Janvier'   => [
                'consoFuel' => "-", 
                'approFuel' => "-", 
                'TRH'       => "-", 
                'Tx'        => "-", // Taux d'utilisation du GE
            ],
            'Février'   => [
                'consoFuel' => "-", 
                'approFuel' => "-", 
                'TRH'       => "-", 
                'Tx'        => "-", 
            ],
            'Mars'      => [
                'consoFuel' => "-", 
                'approFuel' => "-", 
                'TRH'       => "-", 
                'Tx'        => "-", 
            ],
            'Avril'     => [
                'consoFuel' => "-", 
                'approFuel' => "-", 
                'TRH'       => "-", 
                'Tx'        => "-", 
            ],
            'Mai'       => [
                'consoFuel' => "-", 
                'approFuel' => "-", 
                'TRH'       => "-", 
                'Tx'        => "-", 
            ],
            'Juin'      => [
                'consoFuel' => "-", 
                'approFuel' => "-", 
                'TRH'       => "-", 
                'Tx'        => "-", 
            ],
            'Juillet'   => [
                'consoFuel' => "-", 
                'approFuel' => "-", 
                'TRH'       => "-", 
                'Tx'        => "-", 
            ],
            'Août'      => [
                'consoFuel' => "-", 
                'approFuel' => "-", 
                'TRH'       => "-", 
                'Tx'        => "-", 
            ],
            'Septembre' => [
                'consoFuel' => "-", 
                'approFuel' => "-", 
                'TRH'       => "-", 
                'Tx'        => "-", 
            ],
            'Octobre'   => [
                'consoFuel' => "-", 
                'approFuel' => "-", 
                'TRH'       => "-", 
                'Tx'        => "-", 
            ],
            'Novembre'  => [
                'consoFuel' => "-", 
                'approFuel' => "-", 
                'TRH'       => "-", 
                'Tx'        => "-", 
            ],
            'Décembre'  => [
                'consoFuel' => "-", 
                'approFuel' => "-", 
                'TRH'       => "-", 
                'Tx'        => "-", 
            ],
        ];
        
        foreach ($dataOrderByMonth as $key => $value) {
            $consoFuelMonth = 0.0;
            $approFuelMonth = 0.0;
    
            $dureeMonth = 0.0;
            $consoFuel_ = 0;
            $approFuel_ = 0;

            $T_Appro  = [] ; //Tableau des instants d’approvisionnement
            $T_Appro[0] = 0 ; 
            $j = 1 ; 
                        
            //Données de la courbe de durée de fonctionnement jour après jour
            if (array_key_exists('TRH', $value)) {
                if (end($value['TRH']) !== false && reset($value['TRH']) !== false) {
                    $dureeMonth = abs(end($value['TRH']) - reset($value['TRH']));
                    // $dureeMonthByMonth[] = abs(end($value['TRH']) - reset($value['TRH']));
                    
                    // dump($key); 
                                        
                    if($dureeMonth <= 0.0){
                        if($this->gensetMod->getSubType() == 'Inv' || $this->gensetMod->getSubType() == 'Inv+FL'){
                            $dataQuery = $this->manager->createQuery("SELECT SUM(d.p)*:time AS TRH
                                                    FROM App\Entity\GensetData d
                                                    JOIN d.smartMod sm 
                                                    WHERE d.dateTime LIKE :month_
                                                    AND sm.id = :smartModId         
                                                    AND d.p > 0.05
                                                    ")
                            ->setParameters(array(
                                'month_'       => $key . '%',
                                'time'         => $this->intervalTime,
                                'smartModId'   => $this->gensetMod->getId()
                            ))
                            ->getResult();
                            
                            // dump($dataQuery);
                            if(count($dataQuery) > 0){
                                
                                $dureeMonth = floatval($dataQuery[0]['TRH']);
                                
                                if($dureeMonth <= 0.0){
                                    if($this->gensetMod->getSubType() == 'Inv+FL'){
                                        
                                        $dataQuery = $this->manager->createQuery("SELECT MAX(NULLIF(d.totalRunningHours,0)) - MIN(NULLIF(d.totalRunningHours,0)) AS TRH
                                                                    FROM App\Entity\GensetData d
                                                                    JOIN d.smartMod sm 
                                                                    WHERE d.dateTime LIKE :month_
                                                                    AND sm.id = :smartModId
                                                                    AND d.totalRunningHours IS NOT NULL
                                                                    ")
                                            ->setParameters(array(
                                                'month_'       => $key .'%',
                                                'smartModId'   => $this->gensetMod->getId()
                                            ))
                                            ->getResult();
                                        
                                            // dump($dataQuery);
                                            
                                        $dureeMonth = floatval($dataQuery[0]['TRH']);
                                        // dump("======== Inv+FL ========"); 
                                        
                                    } 
                                }
                                // else dump("======== Inv ========"); 
                                        
                                
                            }
                        }
                        // else dump("======== Modbus || FL ========"); 
                    }
                    // dump($dureeMonth);
                }
            }

            //Données des courbe de consommation et approvisionnement jour après jour
            if (array_key_exists('FL', $value)) {
                $temp = $value['FL']; //Tableau tampon
                if (count($temp) > 0) {
                    if($this->gensetMod->getSubType() == 'ModBus'){
                        for ($i = 0; $i < count($temp) - 1; $i++) {
                            $diff = abs($temp[$i + 1] - $temp[$i]);
                            if ($temp[$i + 1] >= $temp[$i]) {
                                $approFuel_ += $diff;
                            } else {
                                $consoFuel_ += $diff;
                            }
                            // if ($temp[$i + 1] - 5 >= $temp[$i]) {
                            //     $approFuel_ += $diff;
                            // } else if ($temp[$i] - $temp[$i + 1] >= 5 ){
                                //     $consoFuel_ += $diff;
                                // }
                                
                        }
                    }else { //if($this->gensetMod->getSubType() !== 'ModBus')
                        // dump($temp[0]) ;
                        $N = count($temp);
                        for ($i=0 ; $i < $N - 3 ; $i ++){ // N est le volume de données sur la fenêtre de temps choisie = size(temp[])
                            
                            if  ( ($temp[$i+1]  - $temp[$i]) > 5 && $temp[$i+2] - $temp[$i] > 5 && ($temp[$i+3] - $temp[$i]) > 5  ){ // On compare avec les trois valeurs suivantes pour éviter les valeurs aberrantes 
                                
                                $T_Appro[$j] = $i ; // On enregistre tous les instants d’approvisionnement
                                $j = $j + 1 ; 
                                $approFuel_ = $approFuel_ + $temp[$i+1] - $temp[$i] ; // On calcul le volume d’approvisionnement
                            }
                                    
                        }
                        // dump(count($T_Appro));
                        // dump($j) ; 
                        if ( count($T_Appro) > 1 ){
                            
                            if ( $temp[0]  - $temp[$T_Appro[1]] > 2){
                                $consoFuel_ = $consoFuel_ + $temp[0]  - $temp[$T_Appro[1]] ;
                            }
                            if ( count($T_Appro) > 2 ){
                                for ($i=0 ; $i < count($T_Appro) - 2 ; $i++){ // N est le volume de données sur la fenêtre de temps choisie
                                    if ( $temp[$T_Appro[$i+1]+1]  - $temp[$T_Appro[$i+2]] > 2){
                                        $consoFuel_ = $consoFuel_ + $temp[$T_Appro[$i+1]+1]  - $temp[$T_Appro[$i+2]] ;
                                    } 	
                                
                                }
                            }
                            
                            if ( $temp[$T_Appro[$j-1]+1]  - $temp[$N-1] > 2){
                                $consoFuel_ = $consoFuel_ + $temp[$T_Appro[$j-1]+1]   - $temp[$N-1] ;
                            } 
                        }
                        else{
                            if ( $temp[0]  - $temp[$N-1] > 2){
                                $consoFuel_ = $consoFuel_ + $temp[0]  - $temp[$N-1] ;
                            } 
                        }
                    }
                }
            }

            // $currentConsoFuel += $consoFuel_;
            // $currentApproFuel += $approFuel_;

            $consoFuelMonth = ($consoFuel_* $this->fuelCapacity)/100.0;
            $approFuelMonth = ($approFuel_* $this->fuelCapacity)/100.0;
            // $consoFuelMonthByMonth[] = ($consoFuel_* $this->fuelCapacity)/100.0;
            // $approFuelMonthByMonth[] = ($approFuel_* $this->fuelCapacity)/100.0;

            switch (date('n', strtotime($key))) {
                case 1:
                    $monthData['Janvier']['consoFuel'] = floatval(number_format((float) $consoFuelMonth, 2, '.', ''));
                    $monthData['Janvier']['approFuel'] = floatval(number_format((float) $approFuelMonth, 2, '.', ''));
                    $monthData['Janvier']['TRH']       = $this->hoursandmins($dureeMonth);

                    $tx          = $totalRecordMonthByMonth[$key] > 0 ? ($dureeMonth / $totalRecordMonthByMonth[$key])*100.0 : 0.0;
                    $monthData['Janvier']['Tx'] = floatval(number_format((float) $tx, 2, '.', ''));
                    break;
                case 2:
                    $monthData['Février']['consoFuel'] = floatval(number_format((float) $consoFuelMonth, 2, '.', ''));
                    $monthData['Février']['approFuel'] = floatval(number_format((float) $approFuelMonth, 2, '.', ''));
                    $monthData['Février']['TRH']       = $this->hoursandmins($dureeMonth);

                    $tx          = $totalRecordMonthByMonth[$key] > 0 ? ($dureeMonth / $totalRecordMonthByMonth[$key])*100.0 : 0.0;
                    $monthData['Février']['Tx'] = floatval(number_format((float) $tx, 2, '.', ''));
                    break;
                case 3:
                    $monthData['Mars']['consoFuel'] = floatval(number_format((float) $consoFuelMonth, 2, '.', ''));
                    $monthData['Mars']['approFuel'] = floatval(number_format((float) $approFuelMonth, 2, '.', ''));
                    $monthData['Mars']['TRH']       = $this->hoursandmins($dureeMonth);

                    $tx          = $totalRecordMonthByMonth[$key] > 0 ? ($dureeMonth / $totalRecordMonthByMonth[$key])*100.0 : 0.0;
                    $monthData['Mars']['Tx'] = floatval(number_format((float) $tx, 2, '.', ''));
                    break;
                case 4:
                    $monthData['Avril']['consoFuel'] = floatval(number_format((float) $consoFuelMonth, 2, '.', ''));
                    $monthData['Avril']['approFuel'] = floatval(number_format((float) $approFuelMonth, 2, '.', ''));
                    $monthData['Avril']['TRH']       = $this->hoursandmins($dureeMonth);

                    $tx          = $totalRecordMonthByMonth[$key] > 0 ? ($dureeMonth / $totalRecordMonthByMonth[$key])*100.0 : 0.0;
                    $monthData['Avril']['Tx'] = floatval(number_format((float) $tx, 2, '.', ''));
                    break;
                case 5:
                    $monthData['Mai']['consoFuel'] = floatval(number_format((float) $consoFuelMonth, 2, '.', ''));
                    $monthData['Mai']['approFuel'] = floatval(number_format((float) $approFuelMonth, 2, '.', ''));
                    $monthData['Mai']['TRH']       = $this->hoursandmins($dureeMonth);

                    $tx          = $totalRecordMonthByMonth[$key] > 0 ? ($dureeMonth / $totalRecordMonthByMonth[$key])*100.0 : 0.0;
                    $monthData['Mai']['Tx'] = floatval(number_format((float) $tx, 2, '.', ''));
                    break;
                case 6:
                    $monthData['Juin']['consoFuel'] = floatval(number_format((float) $consoFuelMonth, 2, '.', ''));
                    $monthData['Juin']['approFuel'] = floatval(number_format((float) $approFuelMonth, 2, '.', ''));
                    $monthData['Juin']['TRH']       = $this->hoursandmins($dureeMonth);

                    $tx          = $totalRecordMonthByMonth[$key] > 0 ? ($dureeMonth / $totalRecordMonthByMonth[$key])*100.0 : 0.0;
                    $monthData['Juin']['Tx'] = floatval(number_format((float) $tx, 2, '.', ''));
                    break;
                case 7:
                    $monthData['Juillet']['consoFuel'] = floatval(number_format((float) $consoFuelMonth, 2, '.', ''));
                    $monthData['Juillet']['approFuel'] = floatval(number_format((float) $approFuelMonth, 2, '.', ''));
                    $monthData['Juillet']['TRH']       = $this->hoursandmins($dureeMonth);

                    $tx          = $totalRecordMonthByMonth[$key] > 0 ? ($dureeMonth / $totalRecordMonthByMonth[$key])*100.0 : 0.0;
                    $monthData['Juillet']['Tx'] = floatval(number_format((float) $tx, 2, '.', ''));
                    break;
                case 8:
                    $monthData['Août']['consoFuel'] = floatval(number_format((float) $consoFuelMonth, 2, '.', ''));
                    $monthData['Août']['approFuel'] = floatval(number_format((float) $approFuelMonth, 2, '.', ''));
                    $monthData['Août']['TRH']       = $this->hoursandmins($dureeMonth);

                    $tx          = $totalRecordMonthByMonth[$key] > 0 ? ($dureeMonth / $totalRecordMonthByMonth[$key])*100.0 : 0.0;
                    $monthData['Août']['Tx'] = floatval(number_format((float) $tx, 2, '.', ''));
                    break;
                case 9:
                    $monthData['Septembre']['consoFuel'] = floatval(number_format((float) $consoFuelMonth, 2, '.', ''));
                    $monthData['Septembre']['approFuel'] = floatval(number_format((float) $approFuelMonth, 2, '.', ''));
                    $monthData['Septembre']['TRH']       = $this->hoursandmins($dureeMonth);

                    $tx          = $totalRecordMonthByMonth[$key] > 0 ? ($dureeMonth / $totalRecordMonthByMonth[$key])*100.0 : 0.0;
                    $monthData['Septembre']['Tx'] = floatval(number_format((float) $tx, 2, '.', ''));
                    break;
                case 10:
                    $monthData['Octobre']['consoFuel'] = floatval(number_format((float) $consoFuelMonth, 2, '.', ''));
                    $monthData['Octobre']['approFuel'] = floatval(number_format((float) $approFuelMonth, 2, '.', ''));
                    $monthData['Octobre']['TRH']       = $this->hoursandmins($dureeMonth);

                    $tx          = $totalRecordMonthByMonth[$key] > 0 ? ($dureeMonth / $totalRecordMonthByMonth[$key])*100.0 : 0.0;
                    $monthData['Octobre']['Tx'] = floatval(number_format((float) $tx, 2, '.', ''));
                    break;
                case 11:
                    $monthData['Novembre']['consoFuel'] = floatval(number_format((float) $consoFuelMonth, 2, '.', ''));
                    $monthData['Novembre']['approFuel'] = floatval(number_format((float) $approFuelMonth, 2, '.', ''));
                    $monthData['Novembre']['TRH']       = $this->hoursandmins($dureeMonth);

                    $tx          = $totalRecordMonthByMonth[$key] > 0 ? ($dureeMonth / $totalRecordMonthByMonth[$key])*100.0 : 0.0;
                    $monthData['Novembre']['Tx'] = floatval(number_format((float) $tx, 2, '.', ''));
                    break;
                case 12:
                    $monthData['Décembre']['consoFuel'] = floatval(number_format((float) $consoFuelMonth, 2, '.', ''));
                    $monthData['Décembre']['approFuel'] = floatval(number_format((float) $approFuelMonth, 2, '.', ''));
                    $monthData['Décembre']['TRH']       = $this->hoursandmins($dureeMonth);

                    $tx          = $totalRecordMonthByMonth[$key] > 0 ? ($dureeMonth / $totalRecordMonthByMonth[$key])*100.0 : 0.0;
                    $monthData['Décembre']['Tx'] = floatval(number_format((float) $tx, 2, '.', ''));
                    break;
                    
                default:
                    break;
            }
        }

        // dump($monthData);

        /*foreach ($dataQuery as $data) {
            switch (date('w', strtotime($data['jour']))) {
                case 0:
                    $monthData['Dimanche'][] = $data['EA'];
                    break;
                case 1:
                    $monthData['Lundi'][] = $data['EA'];
                    break;
                case 2:
                    $monthData['Mardi'][] = $data['EA'];
                    break;
                case 3:
                    $monthData['Mercredi'][] = $data['EA'];
                    break;
                case 4:
                    $monthData['Jeudi'][] = $data['EA'];
                    break;
                case 5:
                    $monthData['Vendredi'][] = $data['EA'];
                    break;
                case 6:
                    $monthData['Samedi'][] = $data['EA'];
                    break;
                
                default:
                    break;
            }
        }*/
        return $monthData;
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
        $TEPdata = [];
        $totalTEP = 0.0;

        if($this->gensetMod->getSubType() === 'ModBus' ){ //Si le module GENSET est de type Modbus 
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

        } else if(strpos($this->gensetMod->getSubType(), 'Inv') !== false ) { //Si le module GENSET est de type Inverter 
            $config = json_decode($this->gensetMod->getConfiguration(), true);
            $intervalTime = array_key_exists("Frs", $config) ? $config['Frs']/60.0 : 5.0/60.0 ;//Temps en minutes converti en heure
            // dump($intervalTime);

            $TEPdata = $this->manager->createQuery("SELECT SUM(d.p)*:time AS TEP
                                        FROM App\Entity\GensetData d
                                        JOIN d.smartMod sm 
                                        WHERE d.dateTime BETWEEN :startDate AND :endDate
                                        AND sm.id = :smartModId         
                                        ")
                ->setParameters(array(
                    'time'         => $intervalTime,
                    'startDate'    => $this->startDate->format('Y-m-d H:i:s'),
                    'endDate'      => $this->endDate->format('Y-m-d H:i:s'),
                    'smartModId'   => $this->gensetMod->getId()
                ))
                ->getResult();

            $totalTEP = $TEPdata[0]['TEP'] ?? 0;

            $TEPdata = $this->manager->createQuery("SELECT SUBSTRING(d.dateTime,1,10) as dat, SUM(d.p)*:time AS TEP
                        FROM App\Entity\GensetData d
                        JOIN d.smartMod sm 
                        WHERE d.dateTime BETWEEN :startDate AND :endDate
                        AND sm.id = :smartModId         
                        GROUP BY dat
                        ORDER BY dat ASC")
            ->setParameters(array(
                'time'         => $intervalTime,
                'startDate'    => $this->startDate->format('Y-m-d H:i:s'),
                'endDate'      => $this->endDate->format('Y-m-d H:i:s'),
                'smartModId'   => $this->gensetMod->getId()
            ))
            ->getResult();
        }

        $totalTEP = floatval(number_format((float) $totalTEP, 2, '.', ''));


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

    private function mmmrv($array, $output = 'mean')
    {
        if (!is_array($array)) {
            return FALSE;
        } else {
            $total = 0.0;
            if (count($array) <= 0) return $total;
            switch ($output) {
                case 'mean':
                    $total = array_sum($array) / count($array);
                    break;
                case 'median':
                    $count = count($array); //total numbers in array
                    $middleval = floor(($count - 1) / 2); // find the middle value, or the lowest middle value
                    if ($count % 2) { // odd number, middle is the median
                        $total = $array[$middleval];
                    } else { // even number, calculate avg of 2 medians
                        $low = $array[$middleval];
                        $high = $array[$middleval + 1];
                        $total = (($low + $high) / 2);
                    }
                    break;
                case 'mode':
                    $v = array_count_values($array);
                    arsort($v);
                    foreach ($v as $k => $v) {
                        $total = $k;
                        break;
                    }
                    break;
                case 'range':
                    sort($array);
                    $sml = $array[0];
                    rsort($array);
                    $lrg = $array[0];
                    $total = $lrg - $sml;
                    break;
                case 'variation':
                    $variation = 0.0;
                    $moyenne = 0.0;
                    if (count($array) > 0) {
                        $moyenne = array_sum($array) / (count($array) * 1.0);
                        $variation = $this->ecart_type($array);
                    }

                    $total = $moyenne != 0.0 ? ($variation / $moyenne) * 100 : 0.0;
            }
            return $total;
        }
    }

    function hoursandmins($time, $format = '%02d:%02d:%02d')
    {
        if ($time < 0) {
            return '-:-:-';
        }
        /*$hours = floor($time);
        $minutes = floor(($time - $hours) / 60);
        $seconds = $time - ($hours) - ($minutes * 60);*/

        $seconds = $time * 60 * 60;
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds / 60) % 60);
        $seconds = $seconds % 60;
        return sprintf($format, $hours, $minutes, $seconds);
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
        if($this->gensetMod){
            $config = json_decode($this->gensetMod->getConfiguration(), true);
            if($config) $this->intervalTime = array_key_exists("Frs", $config) ? $config['Frs']/60.0 : 5.0/60.0 ;//Temps en minutes converti en heure
            else $this->intervalTime = 5.0/60.0;

            $this->fuelCapacity = $this->gensetMod->getFuelCapacity() ?? 100.0;
            $this->fuelPrice    = $this->gensetMod->getFuelPrice() ?? 575.0;
        }

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
