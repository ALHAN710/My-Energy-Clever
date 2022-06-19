<?php

namespace App\Service;

use DateTime;
use DateInterval;
use App\Entity\Site;
use App\Entity\SmartMod;
use App\Entity\GensetRealTimeData;
use Doctrine\ORM\EntityManagerInterface;

class DCModService
{
    /**
     * Smart Module de type DC
     *
     * @var SmartMod
     */
    private $dcMod;

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

        // ######## Récupération des données de consommation et d'approvisionnement de Fuel
//        $fuelData = $this->getConsoFuelData();
//        $consoFuelXAF = $fuelData['currentConsoFuel'] * $this->dcMod->getFuelPrice();
//        $consoFuelXAF = floatval(number_format($consoFuelXAF, 2, '.', ''));

        // ######## Récupération des données temps réel du module Genset
        $dcRealTimeData = $this->manager->getRepository(GensetRealTimeData::class)->findOneBy(['smartMod' => $this->dcMod->getId()]) ?? new GensetRealTimeData();

        return array(
            'BattVolt'  => $dcRealTimeData->getBattVoltage() ?? 0,
            'BattState'  => $dcRealTimeData->getBattState() ?? 0,
            'BattEnergy' => $dcRealTimeData->getBattEnergy() ?? 0,
            'Date' => $dcRealTimeData->getDateTime() ?? '',
            /*'currentConsoFuel'            => $fuelData['currentConsoFuel'],
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
            ],*/

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

    public function getDCRealTimeData()
    {
        $date = [];

        $dcRealTimeData = $this->manager->getRepository(GensetRealTimeData::class)->findOneBy(['id' => $this->gensetMod->getId()]) ?? new GensetRealTimeData();


        return array(
            'BattVoltage'   => $dcRealTimeData->getBattVoltage() ?? 0,
            'BattState'     => $dcRealTimeData->getBattVoltage() ?? 0,
            'BattEnergy'    => $dcRealTimeData->getBattEnergy() ?? 0,
            'Date1'         => $dcRealTimeData->getDateTime() ?? '',
            'date'          => $date,
        );
    }

    public function getDcDataForSiteProDashBoard()
    {
        $dcVoltDataQuery = $this->manager->createQuery("SELECT d.dateTime as dat, d.battVoltage AS Volt
                                        FROM App\Entity\GensetData d
                                        JOIN d.smartMod sm 
                                        WHERE d.dateTime BETWEEN :startDate AND :endDate
                                        AND sm.id = :smartModId         
                                        ")
            ->setParameters(array(
                'startDate'    => $this->startDate->format('Y-m-d H:i:s'),
                'endDate'      => $this->endDate->format('Y-m-d H:i:s'),
                'smartModId'   => $this->dcMod->getId()
            ))
            ->getResult();
        // dump($dcVoltDataQuery);
        $dcVolt     = [];
        $dcVoltDate = [];
        foreach ($dcVoltDataQuery as $d) {
            $dcVoltDate[] = $d['dat']->format('Y-m-d H:i:s');
            $dcVolt[]     = floatval(number_format((float) $d['Volt'], 2, '.', ''));
        }

        // ######## Récupération des données de consommation et d'approvisionnement de Fuel
//        $fuelData = $this->getConsoFuelData();
        //dump($fuelData);

        // ######## Récupération des données temps réel du module Genset
        $dcRealTimeData = $this->manager->getRepository(GensetRealTimeData::class)->findOneBy(['smartMod' => $this->dcMod->getId()]) ?? new GensetRealTimeData();
        //dump($gensetRealTimeData);
        $last_update = $dcRealTimeData->getDateTime() ?? new DateTime('now');
        $target = new DateTime('now');
        $interval = $last_update->diff($target);
//        dump(intval($interval->format('%r%h')));
        $last_update = intval($interval->format('%r%d')) >= 1 ? $target->format('d M Y H:i:s') : $dcRealTimeData->getDateTime()->format('d M Y H:i:s');
        return array(
            'BattVolt'  => $dcRealTimeData->getBattVoltage() ?? 0,
            'BattState'  => $dcRealTimeData->getBattState() ?? 0,
            'BattEnergy' => $dcRealTimeData->getBattEnergy() ?? 0,
            'last_update' => $last_update,
//            'currentTEP'        => $totalTEP,
//            'currentConsoFuel'  => $fuelData['currentConsoFuel'],
//            'dureeFonctionnement'  => $fuelData['dureeFonctionnement'],
//            'dayBydayTEPData' => [
//                'date'  => $date,
//                "TEP"   => $TEP
//            ],
            'voltageProfileData' => [
                'date' => $dcVoltDate,
                "volt"   => $dcVolt
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
     * Get smart Module de type DC
     *
     * @return  SmartMod
     */
    public function getDcMod()
    {
        return $this->dcMod;
    }

    /**
     * Set smart Module de type DC
     *
     * @param  SmartMod  $dcMod  Smart Module de type DC
     *
     * @return  self
     */
    public function setDcMod(SmartMod $dcMod)
    {
        $this->dcMod = $dcMod;
        if($this->dcMod){
            $config = json_decode($this->dcMod->getConfiguration(), true);
            if($config) $this->intervalTime = array_key_exists("Frs", $config) ? $config['Frs']/60.0 : 5.0/60.0 ;//Temps en minutes converti en heure
            else $this->intervalTime = 5.0/60.0;

//            $this->fuelCapacity = $this->dcMod->getFuelCapacity() ?? 100.0;
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
