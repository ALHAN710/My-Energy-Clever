<?php

namespace App\Controller;

use Faker;
use DateTime;
use DateInterval;
use App\Entity\Site;
use App\Entity\SmartMod;
use App\Entity\GensetData;
use App\Entity\AlarmReporting;
use App\Service\GensetModService;
use App\Entity\GensetRealTimeData;
use App\Message\UserNotificationMessage;
use Doctrine\ORM\EntityManagerInterface;
use App\Controller\ApplicationController;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
//use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class GensetController extends ApplicationController
{
    /**
     * @Route("/installation/{slug<[a-zA-Z0-9-_]+>}/genset/{id<\d+>}", name="genset_home")
     * 
     * 
     * 
     */
    public function index($slug, SmartMod $genset, EntityManagerInterface $manager, GensetModService $gensetModService): Response
    { //@Security( "is_granted('ROLE_SUPER_ADMIN') or ( is_granted('ROLE_NOC_SUPERVISOR') and id.getSite().getEnterprise() === user.getEnterprise() )" )
        // dump($slug);
        // dump($genset);

        $startDate = new DateTime(date("Y-m-01", strtotime(date('Y-m-d'))) . '00:00:00');
        $endDate   = new DateTime(date("Y-m-t", strtotime(date('Y-m-d'))) . '23:59:59');

        $site = $manager->getRepository(Site::class)->findOneBy(['slug' => $slug]);
        // dump($site);
        $gensetModService->setGensetMod($genset)
            ->setStartDate($startDate)
            ->setEndDate($endDate);

        $overViewData = $gensetModService->getDashboardData();
        dump($overViewData);
        // $siteDash->setSite($site)
        //     ->setPower_unit(1000);

        return $this->render('site/home_data_monitoring.html.twig', [
            'site'                    => $site,
            'genset'                  => $genset,
            'overviewData'            => $overViewData,
        ]);
    }


    /**
     * Permet de mettre à jour l'affichage des données temps réel d'un module genset
     *
     * @Route("/update/genset/mod/{id<\d+>}/display/",name="update_genset_display_data")
     * 
     * 
     * @param [interger] $id
     * @param EntityManagerInterface $manager
     * @return Response
     */
    public function updateDisplayGensetRealTimeData(SmartMod $genset, EntityManagerInterface $manager, Request $request, GensetModService $gensetModService): Response
    {
        //@Security( "is_granted('ROLE_SUPER_ADMIN') or ( is_granted('ROLE_NOC_SUPERVISOR') and id.getSite().getEnterprise() === user.getEnterprise() )" )

        /*SELECT * 
            FROM `datetime_data` 
            WHERE `id` = (SELECT max(`id`) FROM `datetime_data` WHERE `date_time` LIKE '2021-05-21%')*/

        $startDate = new DateTime(date("Y-m-01", strtotime(date('Y-m-d'))) . '00:00:00');
        $endDate   = new DateTime(date("Y-m-t", strtotime(date('Y-m-d'))) . '23:59:59');
        // dump($startDate);
        // dump($endDate);

        //dump($overViewData);

        $gensetModService->setGensetMod($genset)
            ->setStartDate($startDate)
            ->setEndDate($endDate);

        $overViewData = $gensetModService->getDashboardData();

        return $this->json([
            'code'            => 200,
        ], 200);
    }

    /**
     * Permet de mettre à jour les graphes liés aux données d'un module genset
     *
     * @Route("/update/genset/mod/{smartMod<\d+>}/graphs/", name="update_genset_graphs")
     * 
     * 
     * 
     * @param [SmartMod] $smartMod
     * @param EntityManagerInterface $manager
     * @return Response
     */
    public function updateGensetGraphs(SmartMod $smartMod, EntityManagerInterface $manager, Request $request): Response
    {
        //@Security( "is_granted('ROLE_SUPER_ADMIN') or ( is_granted('ROLE_NOC_SUPERVISOR') and smartMod.getSite().getEnterprise() === user.getEnterprise() )" )

        //Récupération et vérification des paramètres au format JSON contenu dans la requête
        $paramJSON = $this->getJSONRequest($request->getContent());

        //$smartModRepo = $this->getDoctrine()->getRepository(SmartModRepository::class);
        //$smartMod = $smartModRepo->find($id);
        // //dump($smartModRepo);
        // //dump($smartMod->getModType());
        //$temps = DateTime::createFromFormat("d-m-Y H:i:s", "120");
        // //dump($temps);
        //die();
        $date       = [];
        $P          = [];
        $S          = [];
        $Cosfi      = [];
        $TRH        = [];
        $TEP        = [];
        $FC         = [];
        $dateE      = [];

        // $dateparam = $request->get('selectedDate'); // Ex : %2020-03-20%
        //$dateparam = $paramJSON['selectedDate']; // Ex : %2020-03-20%
        //$startDate = DateTime::createFromFormat('Y-m-d H:i:s', $paramJSON['startDate']); // Ex : %2020-03-20%
        $startDate = new DateTime($paramJSON['startDate']); // Ex : %2020-03-20%
        //$endDate = DateTime::createFromFormat('Y-m-d H:i:s', $paramJSON['endDate']); // Ex : %2020-03-20%
        $endDate = new DateTime($paramJSON['endDate']); // Ex : %2020-03-20%
        // dump($startDate->format('Y-m-d H:i:s'));
        // dump($endDate->format('Y-m-d H:i:s'));
        //$dat = "2020-02"; //'%' . $dat . '%'
        //$dat = substr($dateparam, 0, 8); // Ex : %2020-03
        // //dump($dat);
        //die();
        //$dat = $dat . '%';

        $Energy = $manager->createQuery("SELECT SUBSTRING(d.dateTime, 1, 10) AS jour, MAX(d.totalRunningHours) - MIN(NULLIF(d.totalRunningHours, 0)) AS TRH, 
                                        MAX(d.totalEnergy) - MIN(NULLIF(d.totalEnergy, 0)) AS TEP, AVG(NULLIF(d.fuelInstConsumption, 0))*( MAX(d.totalRunningHours) - MIN(NULLIF(d.totalRunningHours, 0)) ) AS FC
                                        FROM App\Entity\GensetData d
                                        JOIN d.smartMod sm 
                                        WHERE d.dateTime BETWEEN :startDate AND :endDate
                                        AND sm.id = :smartModId
                                        GROUP BY jour
                                        ORDER BY jour ASC                       
                                        ")
            ->setParameters(array(
                //'selDate'      => $dat,
                'startDate'    => $startDate->format('Y-m-d H:i:s'),
                'endDate'    => $endDate->format('Y-m-d H:i:s'),
                'smartModId'   => $smartMod->getId()
            ))
            ->getResult();
        // dump($Energy);
        //die();
        foreach ($Energy as $d) {
            $dateE[] = $d['jour'];
            $TRH[] = number_format((float) $d['TRH'], 2, '.', '');
            $TEP[] = number_format((float) $d['TEP'], 2, '.', '');
            $FC[] = number_format((float) $d['FC'], 2, '.', '') ?? 0;
        }


        /*
        SELECT d.dateTime as dat, d.va, d.vb, d.vc, d.sa, d.sb, d.sc, d.s3ph
                                                FROM App\Entity\DataMod d, App\Entity\SmartMod sm 
                                                WHERE d.dateTime LIKE :selDate
                                                AND sm.id = :modId
                                                ORDER BY dat ASC
        */

        $data = $manager->createQuery("SELECT d.dateTime as dat, d.p, (d.s*100.0)/:genpower as s, d.cosfi
                                        FROM App\Entity\GensetData d 
                                        JOIN d.smartMod sm
                                        WHERE d.dateTime BETWEEN :startDate AND :endDate
                                        AND sm.id = :smartModId
                                        ORDER BY dat ASC
                                        
                                        ")
            ->setParameters(array(
                //'selDate'      => $dateparam,
                'startDate'   => $startDate,
                'endDate'     => $endDate,
                'genpower'  => $smartMod->getPower(),
                'smartModId'  => $smartMod->getId()
            ))
            ->getResult();


        // dump($data);
        foreach ($data as $d) {
            $date[]    = $d['dat']->format('Y-m-d H:i:s');
            //$P[]       = number_format((float) $d['p'], 2, '.', '');
            $S[]    = number_format((float) $d['s'], 2, '.', '');
            //$Cosfi[]   = number_format((float) $d['cosfi'], 2, '.', '');
        }

        return $this->json([
            'code'         => 200,
            //'startDate'    => $startDate,
            //'endDate'      => $endDate,
            'date'         => $date,
            'Mix1'            => [$TRH, $TEP, $FC],
            //'Mix2'            => [$S, $P, $Cosfi],
            'Load_Level'    => $S,
            // 'S3ph'         => $S3ph,
            'dateE'           => $dateE,
            // 'kWh'          => $kWh,
            // 'kVarh'        => $kVarh,
        ], 200);
    }

    /**
     * Permet de mettre à jour la BDD des GensetRealTimeData
     *
     * @Route("/update/mod/{modId<[a-zA-Z0-9_-]+>}/realtime/data",name="update_realtimedata")
     * 
     * @param [interger] $id
     * @param EntityManagerInterface $manager
     * @return Response
     */
    public function updateGensetRealTimeData($modId, EntityManagerInterface $manager, Request $request): Response
    {
        //Récupération et vérification des paramètres au format JSON contenu dans la requête
        $paramJSON = $this->getJSONRequest($request->getContent());
        // //dump($paramJSON);
        // //dump($content);
        //die();

        //Recherche du module dans la BDD
        $smartMod = $manager->getRepository('App:SmartMod')->findOneBy(['moduleId' => $modId]);


        if ($smartMod != null) { // Test si le module existe dans notre BDD

            //Paramétrage des champs de la nouvelle dataMod aux valeurs contenues dans la requête du module
            //$dataMod->setVamin($paramJSON['Va'][0]);

            if ($smartMod->getModType() == 'GENSET') {
                if (array_key_exists("date1", $paramJSON)) {
                    $isNew = false;
                    $oldData = null;
                    $mess = "";
                    //$response = new ResponseInterface();
                    $dataMod = $smartMod->getGensetRealTimeData();
                    if (!$dataMod) {
                        $dataMod = new GensetRealTimeData();
                        $dataMod->setSmartMod($smartMod);
                        $isNew = true;
                    } else {
                        $oldData = clone $smartMod->getGensetRealTimeData();
                    }


                    $date = DateTime::createFromFormat('Y-m-d H:i:s', $paramJSON['date1']);
                    //$date = new DateTime('now');

                    /*$dataMod->setL12G($paramJSON['L12G'])
                        ->setL13G($paramJSON['L13G'])
                        ->setL23G($paramJSON['L23G'])
                        ->setL1N($paramJSON['L1N'])
                        ->setL2N($paramJSON['L2N'])
                        ->setL3N($paramJSON['L3N'])
                        ->setL12M($paramJSON['L12M'])
                        ->setL13M($paramJSON['L13M'])
                        ->setL23M($paramJSON['L23M'])
                        ->setI1($paramJSON['I1N'])
                        ->setI2($paramJSON['I2N'])
                        ->setI3($paramJSON['I3N'])
                        ->setFreq($paramJSON['Freq'])
                        ->setIDiff($paramJSON['Idiff'])
                        ->setFuelLevel($paramJSON['FuelLevel'])
                        ->setWaterLevel($paramJSON['WaterLevel'])
                        ->setOilLevel($paramJSON['OilLevel'])
                        ->setAirPressure($paramJSON['AirPressure'])
                        ->setOilPressure($paramJSON['OilPressure'])
                        ->setWaterTemperature($paramJSON['WaterTemperature'])
                        ->setCoolerTemperature($paramJSON['CoolerTemperature'])
                        ->setEngineSpeed($paramJSON['EngineSpeed'])
                        ->setBattVoltage($paramJSON['BattVoltage'])
                        ->setHoursToMaintenance($paramJSON['HTM'])
                        ->setCg($paramJSON['CG'])
                        ->setCr($paramJSON['CR'])
                        ->setGensetRunning($paramJSON['GensetRun'])
                        ->setMainsPresence($paramJSON['MainsPresence'])
                        ->setPresenceWaterInFuel($paramJSON['PresenceWaterInFuel'])
                        ->setMaintenanceRequest($paramJSON['MaintenanceRequest'])
                        ->setLowFuel($paramJSON['LowFuel'])
                        ->setOverspeed($paramJSON['Overspeed'])
                        ->setMaxFreq($paramJSON['MaxFreq'])
                        ->setMinFreq($paramJSON['MinFreq'])
                        ->setMaxVolt($paramJSON['MaxVolt'])
                        ->setMinVolt($paramJSON['MinVolt'])
                        ->setMaxBattVolt($paramJSON['MaxBattVolt'])
                        ->setMinBattVolt($paramJSON['MinBattVolt'])
                        ->setOverload($paramJSON['Overload'])
                        ->setShortCircuit($paramJSON['ShortCircuit'])
                        ->setMainsIncSeq($paramJSON['MainsIncSeq'])
                        ->setGensetIncSeq($paramJSON['GensetIncSeq'])
                        ->setDifferentialIntervention($paramJSON['DiffIntervention'])
                        //->setSmartMod($smartMod)
                    ;*/
                    $dataMod->setDateTime($date);
                    if (array_key_exists("L12", $paramJSON)) {
                        $dataMod->setL12G($paramJSON['L12']);
                    }
                    if (array_key_exists("L13", $paramJSON)) {
                        $dataMod->setL13G($paramJSON['L13']);
                    }
                    if (array_key_exists("L23", $paramJSON)) {
                        $dataMod->setL23G($paramJSON['L23']);
                    }
                    if (array_key_exists("L1", $paramJSON)) {
                        $dataMod->setL1N($paramJSON['L1']);
                    }
                    if (array_key_exists("L2", $paramJSON)) {
                        $dataMod->setL2N($paramJSON['L2']);
                    }
                    if (array_key_exists("L3", $paramJSON)) {
                        $dataMod->setL3N($paramJSON['L3']);
                    }
                    if (array_key_exists("L12M", $paramJSON)) {
                        $dataMod->setL12M($paramJSON['L12M']);
                    }
                    if (array_key_exists("L13M", $paramJSON)) {
                        $dataMod->setL13M($paramJSON['L13M']);
                    }
                    if (array_key_exists("L23M", $paramJSON)) {
                        $dataMod->setL23M($paramJSON['L23M']);
                    }
                    if (array_key_exists("I1", $paramJSON)) {
                        $dataMod->setI1($paramJSON['I1']);
                    }
                    if (array_key_exists("I2", $paramJSON)) {
                        $dataMod->setI2($paramJSON['I2']);
                    }
                    if (array_key_exists("I3", $paramJSON)) {
                        $dataMod->setI3($paramJSON['I3']);
                    }
                    /*if (array_key_exists("Fr", $paramJSON)) {
                        $dataMod->setFreq($paramJSON['Fr']);
                    }
                    if (array_key_exists("Id", $paramJSON)) {
                        $dataMod->setIDiff($paramJSON['Id']);
                    }*/
                    if (array_key_exists("FL", $paramJSON)) {
                        $dataMod->setFuelLevel($paramJSON['FL']);
                    }
                    /*if (array_key_exists("WL", $paramJSON)) {
                        $dataMod->setWaterLevel($paramJSON['WL']);
                    }
                    if (array_key_exists("OL", $paramJSON)) {
                        $dataMod->setOilLevel($paramJSON['OL']);
                    }
                    if (array_key_exists("AP", $paramJSON)) {
                        $dataMod->setAirPressure($paramJSON['AP']);
                    }
                    if (array_key_exists("OP", $paramJSON)) {
                        $dataMod->setOilPressure($paramJSON['OP']);
                    }
                    if (array_key_exists("WT", $paramJSON)) {
                        $dataMod->setWaterTemperature($paramJSON['WT']);
                    }
                    if (array_key_exists("CT", $paramJSON)) {
                        $dataMod->setCoolerTemperature($paramJSON['CT']);
                    }
                    if (array_key_exists("ESD", $paramJSON)) {
                        $dataMod->setEngineSpeed($paramJSON['ESD']);
                    }*/
                    if (array_key_exists("BV", $paramJSON)) {
                        $dataMod->setBattVoltage($paramJSON['BV']);
                    }
                    if (array_key_exists("HTM", $paramJSON)) {
                        $dataMod->setHoursToMaintenance($paramJSON['HTM']);
                    }
                    if (array_key_exists("CG", $paramJSON)) {
                        $dataMod->setCg($paramJSON['CG']);
                    }
                    if (array_key_exists("CR", $paramJSON)) {
                        $dataMod->setCr($paramJSON['CR']);
                    }
                    if (array_key_exists("GenRun", $paramJSON)) {
                        $dataMod->setGensetRunning($paramJSON['GenRun']);
                    }
                    /*if (array_key_exists("MainsPresence", $paramJSON)) {
                        $dataMod->setMainsPresence($paramJSON['MainsPresence']);
                    }
                    if (array_key_exists("PWF", $paramJSON)) {
                        $dataMod->setPresenceWaterInFuel($paramJSON['PWF']);
                    }
                    if (array_key_exists("MRqst", $paramJSON)) {
                        $dataMod->setMaintenanceRequest($paramJSON['MRqst']);
                    }
                    if (array_key_exists("LowFuel", $paramJSON)) {
                        $dataMod->setLowFuel($paramJSON['LowFuel']);
                    }
                    if (array_key_exists("Overspeed", $paramJSON)) {
                        $dataMod->setOverspeed($paramJSON['Overspeed']);
                    }
                    if (array_key_exists("MaxFr", $paramJSON)) {
                        $dataMod->setMaxFreq($paramJSON['MaxFr']);
                    }
                    if (array_key_exists("MinFr", $paramJSON)) {
                        $dataMod->setMinFreq($paramJSON['MinFr']);
                    }
                    if (array_key_exists("MaxVolt", $paramJSON)) {
                        $dataMod->setMaxVolt($paramJSON['MaxVolt']);
                    }
                    if (array_key_exists("MinVolt", $paramJSON)) {
                        $dataMod->setMinVolt($paramJSON['MinVolt']);
                    }
                    if (array_key_exists("MaxBV", $paramJSON)) {
                        $dataMod->setMaxBattVolt($paramJSON['MaxBV']);
                    }
                    if (array_key_exists("MinBV", $paramJSON)) {
                        $dataMod->setMinBattVolt($paramJSON['MinBV']);
                    }
                    if (array_key_exists("Overload", $paramJSON)) {
                        $dataMod->setOverload($paramJSON['Overload']);
                    }
                    if (array_key_exists("SC", $paramJSON)) {
                        $dataMod->setShortCircuit($paramJSON['SC']);
                    }
                    if (array_key_exists("MIS", $paramJSON)) {
                        $dataMod->setMainsIncSeq($paramJSON['MIS']);
                    }
                    if (array_key_exists("GIS", $paramJSON)) {
                        $dataMod->setGensetIncSeq($paramJSON['GIS']);
                    }
                    if (array_key_exists("DIT", $paramJSON)) {
                        $dataMod->setDifferentialIntervention($paramJSON['DIT']);
                    }*/
                    $dataMod->setSmartMod($smartMod);


                    /*if (!$isNew) {
                        $BATT = "MINB"; // 0
                        $MAINAB = "MAIAB"; // 1
                        $MAINPR = "MAIPR"; // 1
                        $SPEED = "OVSPD"; // 2
                        $LOAD = "OVLOD"; // 3
                        $VOLT = "MINV"; // 4
                        $FREQ = "MINF"; // 5
                        $GENRUN = "GENR"; // 6
                        $FUEL = "LOFL"; // 7
                        $DIFFC = "DIFFC"; // 8
                        $WATFL = "WATFL"; // 9

                        if ($oldData->getMinBattVolt()  === 0 && $paramJSON['MinBV']  === 1) {
                            $mess = "{\"code\":\"{$BATT}\",\"date\":\"{$date->format('Y-m-d H:i:s')}\"}";
                            //$mess = "{\"code\":\"{$BATT}\",\"date\":\"{$paramJSON['date1']}\"}";

                            $response = $this->forward(
                                'App\Controller\GensetController::sendToAlarmController',
                                [
                                    'mess' => $mess,
                                    'modId'  => $smartMod->getModuleId(),
                                ]
                            );
                        }
                        if ($oldData->getCr()  === 1 && $paramJSON['CR']  === 0) {
                            $mess = "{\"code\":\"{$MAINAB}\",\"date\":\"{$date->format('Y-m-d H:i:s')}\"}";
                            //$mess = "{\"code\":\"{$MAINAB}\",\"date\":\"{$paramJSON['date1']}\"}";

                            $response = $this->forward(
                                'App\Controller\GensetController::sendToAlarmController',
                                [
                                    'mess'   => $mess,
                                    'modId'  => $smartMod->getModuleId(),
                                ]
                            );
                        }
                        if ($oldData->getCr()  === 0 && $paramJSON['CR']  === 1) {
                            $mess = "{\"code\":\"{$MAINPR}\",\"date\":\"{$date->format('Y-m-d H:i:s')}\"}";
                            //$mess = "{\"code\":\"{$MAINPR}\",\"date\":\"{$paramJSON['date1']}\"}";

                            $response = $this->forward(
                                'App\Controller\GensetController::sendToAlarmController',
                                [
                                    'mess'   => $mess,
                                    'modId'  => $smartMod->getModuleId(),
                                ]
                            );
                        }
                        if ($oldData->getOverspeed() === 0 && $paramJSON['Overspeed']  === 1) {
                            $mess = "{\"code\":\"{$SPEED}\",\"date\":\"{$date->format('Y-m-d H:i:s')}\"}";
                            //$mess = "{\"code\":\"{$SPEED}\",\"date\":\"{$paramJSON['date1']}\"}";

                            $response = $this->forward(
                                'App\Controller\GensetController::sendToAlarmController',
                                [
                                    'mess' => $mess,
                                    'modId'  => $smartMod->getModuleId(),
                                ]
                            );
                        }
                        if ($oldData->getOverload() === 0 && $paramJSON['Overload'] === 1) {
                            $mess = "{\"code\":\"{$LOAD}\",\"date\":\"{$date->format('Y-m-d H:i:s')}\"}";
                            //$mess = "{\"code\":\"{$LOAD}\",\"date\":\"{$paramJSON['date1']}\"}";

                            $response = $this->forward(
                                'App\Controller\GensetController::sendToAlarmController',
                                [
                                    'mess' => $mess,
                                    'modId'  => $smartMod->getModuleId(),
                                ]
                            );
                        }
                        if ($oldData->getMinVolt() === 0 && $paramJSON['MinVolt'] === 1) {
                            $mess = "{\"code\":\"{$VOLT}\",\"date\":\"{$date->format('Y-m-d H:i:s')}\"}";
                            //$mess = "{\"code\":\"{$VOLT}\",\"date\":\"{$paramJSON['date1']}\"}";

                            $response = $this->forward(
                                'App\Controller\GensetController::sendToAlarmController',
                                [
                                    'mess' => $mess,
                                    'modId'  => $smartMod->getModuleId(),
                                ]
                            );
                        }
                        if ($oldData->getMinFreq() === 0 && $paramJSON['MinFr'] === 1) {
                            $mess = "{\"code\":\"{$FREQ}\",\"date\":\"{$date->format('Y-m-d H:i:s')}\"}";
                            //$mess = "{\"code\":\"{$FREQ}\",\"date\":\"{$paramJSON['date1']}\"}";

                            $response = $this->forward(
                                'App\Controller\GensetController::sendToAlarmController',
                                [
                                    'mess' => $mess,
                                    'modId'  => $smartMod->getModuleId(),
                                ]
                            );
                        }
                        if ($oldData->getGensetRunning() === 0 && $paramJSON['GenRun'] === 1) {
                            $mess = "{\"code\":\"{$GENRUN}\",\"date\":\"{$date->format('Y-m-d H:i:s')}\"}";
                            //$mess = "{\"code\":\"{$GENRUN}\",\"date\":\"{$paramJSON['date1']}\"}";

                            $response = $this->forward('App\Controller\GensetController::sendToAlarmController', [
                                'mess'   => $mess,
                                'modId'  => $smartMod->getModuleId(),
                            ]);
                        }
                        if ($oldData->getLowFuel() === 0 && $paramJSON['LowFuel'] === 1) {
                            $mess = "{\"code\":\"{$FUEL}\",\"date\":\"{$date->format('Y-m-d H:i:s')}\"}";
                            //$mess = "{\"code\":\"{$FUEL}\",\"date\":\"{$paramJSON['date1']}\"}";

                            $response = $this->forward('App\Controller\GensetController::sendToAlarmController', [
                                'mess' => $mess,
                                'modId'  => $smartMod->getModuleId(),
                            ]);
                        }
                        if ($oldData->getDifferentialIntervention() === 0 && $paramJSON['DIT'] === 1) {
                            $mess = "{\"code\":\"{$DIFFC}\",\"date\":\"{$date->format('Y-m-d H:i:s')}\"}";
                            //$mess = "{\"code\":\"{$DIFFC}\",\"date\":\"{$paramJSON['date1']}\"}";

                            $response = $this->forward('App\Controller\GensetController::sendToAlarmController', [
                                'mess' => $mess,
                                'modId'  => $smartMod->getModuleId(),
                            ]);
                        }
                        if ($oldData->getPresenceWaterInFuel() === 0 && $paramJSON['PWF'] === 1) {
                            $mess = "{\"code\":\"{$WATFL}\",\"date\":\"{$date->format('Y-m-d H:i:s')}\"}";
                            //$mess = "{\"code\":\"{$WATFL}\",\"date\":\"{$paramJSON['date1']}\"}";

                            $response = $this->forward('App\Controller\GensetController::sendToAlarmController', [
                                'mess' => $mess,
                                'modId'  => $smartMod->getModuleId(),
                            ]);
                        }
                    }*/
                    // //dump($dataMod);
                    //die();
                    //Insertion de la nouvelle dataMod dans la BDD
                    $manager->persist($dataMod);
                    $manager->flush();

                    return $this->json([
                        'code' => 200,
                        //'received' => $paramJSON,
                        'date'  => $oldData->getDateTime()
                        // 'status'   => $response->getStatusCode(),
                        // 'content' => $response->getContent(),
                        //'contentType' => $response->getHeaders()['content-type'][0],
                        //'old'   => $oldData->getGensetRunning(),
                        //'new'   => $paramJSON['GenRun'],
                        //'Url'   => "http://127.0.0.1/index.php/alarm/notification/{$smartMod->getModuleId()}",
                        //'mess'   => $mess

                    ], 200);
                }
            }

            return $this->json([
                'code' => 200,
                'message' => "Data don't save",
                'received' => $paramJSON

            ], 200);
        }
        return $this->json([
            'code' => 403,
            'message' => "SmartMod don't exist",
            'received' => $paramJSON

        ], 403);
    }

    /**
     * Permet de surcharger les données GensetData des modules FUEL dans la BDD
     *
     * @Route("/genset/data/mod/{modId<[a-zA-Z0-9_-]+>}/add", name="gensetdata_add") 
     * 
     * @param SmartMod $smartMod
     * @param EntityManagerInterface $manager
     * @param Request $request
     * @return void
     */
    public function GensetData_add($modId, EntityManagerInterface $manager, Request $request)
    {
        //Récupération et vérification des paramètres au format JSON contenu dans la requête
        $paramJSON = $this->getJSONRequest($request->getContent());
        // //dump($paramJSON);
        // //dump($content);
        //die();

        $GensetData = new GensetData();

        //Recherche du module dans la BDD
        $smartMod = $manager->getRepository('App:SmartMod')->findOneBy(['moduleId' => $modId]);


        if ($smartMod != null) { // Test si le module existe dans notre BDD
            //data:{"date": "2020-03-20 12:15:00", "sa": 1.2, "sb": 0.7, "sc": 0.85, "va": 225, "vb": 230, "vc": 231, "s3ph": 2.75, "kWh": 1.02, "kvar": 0.4}
            // //dump($smartMod);//Affiche le module
            //die();

            //$date = new DateTime($paramJSON['date']);
            if (array_key_exists("date", $paramJSON)) {
                //Récupération de la date dans la requête et transformation en object de type Date au format date SQL
                //$date = DateTime::createFromFormat('Y-m-d H:i:s', $paramJSON['date']);
                $date = new DateTime('now');
                // //dump($date);
                //die();

                if ($smartMod->getModType() == 'GENSET') {
                    //Paramétrage des champs de la nouvelle GensetData aux valeurs contenues dans la requête du module
                    $GensetData->setDateTime($date)
                        ->setSmartMod($smartMod);
                    if (array_key_exists("P3ph", $paramJSON)) {
                        //$GensetData->setPmax3ph($paramJSON['P3ph'][0])
                    }
                    if (array_key_exists("P", $paramJSON)) {
                        $GensetData->setP($paramJSON['P']);
                    }
                    /*if (array_key_exists("Q3ph", $paramJSON)) {
                        //$GensetData->setQmax3ph($paramJSON['Q3ph'][0])
                    }
                    if (array_key_exists("Q", $paramJSON)) {
                        $GensetData->setQ($paramJSON['Q']);
                    }
                    if (array_key_exists("S", $paramJSON)) {
                        $GensetData->setS($paramJSON['S']);
                    }*/
                    if (array_key_exists("Cosfi", $paramJSON)) {
                        $GensetData->setCosfi($paramJSON['Cosfi']);
                    }
                    if (array_key_exists("EL", $paramJSON)) {
                        $GensetData->setTotalEnergy($paramJSON['EL']);
                    }
                    /*if (array_key_exists("FuelInstConsumption", $paramJSON)) {
                        $GensetData->setFuelInstConsumption($paramJSON['FuelInstConsumption'] / 256.0);
                    }*/
                    if (array_key_exists("NPS", $paramJSON)) {
                        $GensetData->setNbPerformedStartUps($paramJSON['NPS']);
                    }
                    if (array_key_exists("NMI", $paramJSON)) {
                        $GensetData->setNbMainsInterruption($paramJSON['NMI']);
                    }
                    if (array_key_exists("TRH", $paramJSON)) {
                        $GensetData->setTotalRunningHours($paramJSON['TRH']);
                    }
                    if (array_key_exists("FL", $paramJSON)) {
                        $GensetData->setFuelLevel($paramJSON['FL']);
                    }
                }

                // //dump($GensetData);
                //die();
                //Insertion de la nouvelle GensetRealTimeData dans la BDD
                $manager->persist($GensetData);
                $manager->flush();
            }

            return $this->json([
                'code' => 200,
                'received' => $paramJSON

            ], 200);
        }
        return $this->json([
            'code' => 403,
            'message' => "SmartMod don't exist",
            'received' => $paramJSON

        ], 403);
    }

    public function sendToAlarmController($mess, $modId, EntityManagerInterface $manager, HttpClientInterface $client, MessageBusInterface $messageBus)
    {
        /*return $this->json([
            'mess'    => $mess,
            'modId' => $modId,
        ], 200);*/
        /*$paramJSON = $this->getJSONRequest($mess);
        $smartMod = $manager->getRepository('App:SmartMod')->findOneBy(['moduleId' => $modId]);
        if ($smartMod) {
            $alarmCode = $manager->getRepository('App:Alarm')->findOneBy(['code' => $paramJSON['code']]);
            if ($alarmCode) {
                //$date = new DateTime('now');
                $date = DateTime::createFromFormat('Y-m-d H:i:s', $paramJSON['date']) !== false ? DateTime::createFromFormat('Y-m-d H:i:s', $paramJSON['date']) : new DateTime('now');
                //$date = DateTime::createFromFormat('Y-m-d H:i:s', $paramJSON['date']);
                $alarmReporting = new AlarmReporting();
                $alarmReporting->setSmartMod($smartMod)
                    ->setAlarm($alarmCode)
                    ->setCreatedAt($date);


                if ($smartMod->getSite()) $site = $smartMod->getSite();
                else {
                    foreach ($smartMod->getZones() as $zone) {
                        $site = $zone->getSite();
                        if ($site) break;
                    }
                }

                if ($alarmCode->getType() !== 'FUEL') $message = $alarmCode->getLabel() . ' sur <<' . $smartMod->getName() . '>> du site ' . $site->getName() . ' survenu(e) le ' . $date->format('d/m/Y à H:i:s');
                else if ($alarmCode->getType() === 'FUEL') {
                    $data = clone $smartMod->getNoGensetRealTimeData();
                    if ($alarmCode->getCode() === 'GENR') $message = $alarmCode->getLabel() . ' du site ' . $site->getName() . ' survenu(e) le ' . $date->format('d/m/Y à H:i:s') . ' avec un niveau de Fuel de ' . $data->getFuelLevel() . '%';
                    else $message = $alarmCode->getLabel() . ' du site ' . $site->getName() . ' survenu(e) le ' . $date->format('d/m/Y à H:i:s');
                }

                foreach ($site->getContacts() as $contact) {
                    $messageBus->dispatch(new UserNotificationMessage($contact->getId(), $message, $alarmCode->getMedia(), $alarmCode->getAlerte()));
                    //$messageBus->dispatch(new UserNotificationMessage($contact->getId(), $message, 'SMS', ''));
                }

                $adminUsers = [];
                $Users = $manager->getRepository('App:User')->findAll();
                foreach ($Users as $user) {
                    if ($user->getRoles()[0] === 'ROLE_SUPER_ADMIN') $adminUsers[] = $user;
                }
                foreach ($adminUsers as $user) {
                    $messageBus->dispatch(new UserNotificationMessage($user->getId(), $message, 'Email', $alarmCode->getAlerte()));
                }
                //$messageBus->dispatch(new UserNotificationMessage(1, $message, 'Email', $alarmCode->getAlerte()));
                //$messageBus->dispatch(new UserNotificationMessage(2, $message, 'Email', $alarmCode->getAlerte()));
                $manager->persist($alarmReporting);
                $manager->flush();
                return $this->json([
                    'code'    => 200,
                    'alarmCode'  => "{$alarmCode->getMedia()}",
                    'date'  => $date->format('d F Y H:i:s')
                ], 200);
            }
            return $this->json([
                'code'    => 200,
                'smartMod'  => "{$smartMod->getModuleId()}",
                //'date'  => $date->format('d F Y H:i:s')
            ], 200);
        }*/

        return $this->json([
            'code'         => 500,
        ], 500);
    }
}