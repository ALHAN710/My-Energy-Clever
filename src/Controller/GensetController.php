<?php

namespace App\Controller;

use Faker;
use DateTime;
use DateInterval;
use App\Entity\Site;
use App\Entity\User;
use DateTimeImmutable;
use App\Entity\SmartMod;
use App\Entity\GensetData;
use App\Entity\AlarmReporting;
use App\Entity\LoadEnergyData;
use App\Service\GensetModService;
use App\Entity\GensetRealTimeData;
use App\Service\SiteProDataService;
use App\Message\UserNotificationMessage;
use App\Security\LoginFormAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use App\Controller\ApplicationController;
use App\Service\SiteProDataAnalyticService;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Security\Guard\GuardAuthenticatorHandler;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
//use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class GensetController extends ApplicationController
{
    private $projectDirectory;
    private $manager;

    public function __construct(EntityManagerInterface $manager, string $projectDirectory)
    {
        $this->manager = $manager;
        $this->projectDirectory = $projectDirectory;
    }

    /**
     * @Route("/installation/{slug<[a-zA-Z0-9-_]+>}/genset/{id<\d+>}", name="genset_home")
     *
     * @IsGranted("ROLE_USER")
     * @param $slug
     * @param SmartMod $genset
     * @param EntityManagerInterface $manager
     * @param GensetModService $gensetModService
     * @return Response
     * @throws \Exception
     */
    public function index($slug, SmartMod $genset, EntityManagerInterface $manager, GensetModService $gensetModService): Response
    { //@Security( "is_granted('ROLE_SUPER_ADMIN') or ( is_granted('ROLE_NOC_SUPERVISOR') and id.getSite().getEnterprise() === user.getEnterprise() )" )
        // dump($slug);
        // dump($genset);

        $startDate = new DateTime(date("Y-m-01", strtotime(date('Y-m-d'))) . '00:00:00');
        $endDate   = new DateTime(date("Y-m-t", strtotime(date('Y-m-d'))) . '23:59:59');
        // $startDate = new DateTime(date("2022-02-01", strtotime(date('2022-02-d'))) . '00:00:00');
        // $endDate   = new DateTime(date("2022-02-t", strtotime(date('2022-02-d'))) . '23:59:59');

        $site = $manager->getRepository(Site::class)->findOneBy(['slug' => $slug]);
        // dump($site);
        $gensetModService->setGensetMod($genset)
            ->setStartDate($startDate)
            ->setEndDate($endDate);

        $overViewData = $gensetModService->getDashboardData();
        // dump($overViewData);
        // $siteDash->setSite($site)
        //     ->setPower_unit(1000);

        $monthDataTable = $gensetModService->getDataForMonthDataTable();

        return $this->render('genset/home_data_monitoring.html.twig', [
            'site'            => $site,
            'genset'          => $genset,
            'overviewData'    => $overViewData,
            'monthDataTable'  => $monthDataTable,
        ]);
    }

    /**
     * Fonction test pour le reporting GE
     * 
     * @Route("/installation/{slug<[a-zA-Z0-9-_]+>}/genset-report/{id<\d+>}", name="genset_report")
     * 
     * @param string $slug
     * @param SmartMod $genset
     * @param EntityManagerInterface $manager
     * @param GensetModService $gensetModService
     * @return Response
     */
    public function weeklyReport($slug, SmartMod $genset, EntityManagerInterface $manager, GensetModService $gensetModService, SiteProDataAnalyticService $siteProAnalytic): Response
    {
        $site = $manager->getRepository(Site::class)->findOneBy(['slug' => $slug]);

        $date = new DateTime('now');
        $date->modify('-6 days');
        $week = $date->format("W");
        $year = $date->format("Y");
        // dump("Week Number : $week");

        $dates=$this->getStartAndEndDate($week,$year);
        // dump($dates);

        $startDate = new DateTime($dates['start_date'] . '00:00:00');
        $endDate   = new DateTime($dates['end_date'] . '23:59:59');
        // $startDate = new DateTime(date("Y-m-01", strtotime(date('Y-m-d'))) . '00:00:00');
        // $endDate   = new DateTime(date("Y-m-t", strtotime(date('Y-m-d'))) . '23:59:59');
        // dump($startDate);
        // dump($endDate);

        // Example of how to obtain an user:
        $users = $this->getDoctrine()->getManager()->getRepository(User::class)->findBy(array('enterprise' => $site->getEnterprise()));
        $user = null;
        foreach($users as $user_)
        {
            if($user_->getRoles()[0] === 'ROLE_ADMIN') {
                $user = $user_;
                break;
            }
        }
        $this->loginAction($user);

        /*$gensetModService->setGensetMod($genset)
            ->setStartDate($startDate)
            ->setEndDate($endDate);

        $dataReport = $gensetModService->getDashboardData();*/

        $siteProAnalytic->setSite($site)
                    ->setPower_unit(1)
                    ->setStartDate($startDate)
                    ->setEndDate($endDate);

        $dataAnalysis = $siteProAnalytic->getDataAnalysis();

        return $this->render('email/data-analysis-report.html.twig', [
            'site'            => $site,
            'genset'          => $genset,
            'dataAnalysis'    => $dataAnalysis,
            //'dataReport'      => $dataReport,
            'dir'             => $this->projectDirectory,
        ]);
    }

    public function loginAction($user)
    {
        // $user = /*The user needs to be registered */;#
        // Example of how to obtain an user:
        // $user = $this->getDoctrine()->getManager()->getRepository(User::class)->findOneBy(array('email' => "alhadoumpascal@gmail.com"));
        // dump($user);

        // dd($this);

        //Handle getting or creating the user entity likely with a posted form
        // The third parameter "main" can change according to the name of your firewall in security.yml
        $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
        $this->get('security.token_storage')->setToken($token);

        // If the firewall name is not main, then the set value would be instead:
        // $this->get('session')->set('_security_XXXFIREWALLNAMEXXX', serialize($token));
        $this->get('session')->set('_security_main', serialize($token));
        
        // Fire the login event manually
        // $event = new InteractiveLoginEvent($request, $token);
        // $this->dispatcher->dispatch("security.interactive_login", $event);
        // $this->get("event_dispatcher")->dispatch("security.interactive_login", $event);
        
        // dd($this->getUser());
        /*
         * Now the user is authenticated !!!! 
         * Do what you need to do now, like render a view, redirect to route etc.
         */
    }
    
    function getStartAndEndDate($week, $year) {
        $dateTime = new DateTime();
        $dateTime->setISODate($year, $week);
        $result['start_date'] = $dateTime->format('Y-m-d');
        $dateTime->modify('+6 days');
        $result['end_date'] = $dateTime->format('Y-m-d');
        return $result;
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
        // $startDate = new DateTime(date("2022-02-01", strtotime(date('2022-02-d'))) . '00:00:00');
        // $endDate   = new DateTime(date("2022-02-t", strtotime(date('2022-02-d'))) . '23:59:59');
        
        // dump($startDate);
        // dump($endDate);
        
        $gensetModService->setGensetMod($genset)
        ->setStartDate($startDate)
        ->setEndDate($endDate);
        
        $overViewData = $gensetModService->getDashboardData();
        // dump($overViewData);
        
        return $this->json([
            'code'            => 200,
            'overviewData'    => $overViewData,
        ], 200);
    }

    /**
     * Permet de mettre à jour l'historique des graphes liés aux données d'un module genset Modbus ou Fuel
     *
     * @Route("/update/genset/mod/{smartMod<\d+>}/graphs/", name="update_genset_graphs")
     * 
     * 
     * 
     * @param [SmartMod] $genset
     * @param EntityManagerInterface $manager
     * @return Response
     */
    public function updateGensetGraphs(SmartMod $smartMod, EntityManagerInterface $manager, Request $request, GensetModService $gensetModService): Response
    {
        // @IsGranted("USER")
        //@Security( "is_granted('ROLE_SUPER_ADMIN') or ( is_granted('ROLE_NOC_SUPERVISOR') and smartMod.getSite().getEnterprise() === user.getEnterprise() )" )

        //Récupération et vérification des paramètres au format JSON contenu dans la requête
        $paramJSON = $this->getJSONRequest($request->getContent());

        // $dateparam = $request->get('selectedDate'); // Ex : %2020-03-20%
        //$dateparam = $paramJSON['selectedDate']; // Ex : %2020-03-20%
        //$startDate = DateTime::createFromFormat('Y-m-d H:i:s', $paramJSON['startDate']); // Ex : %2020-03-20%
        $startDate = new DateTime($paramJSON['startDate']); // Ex : %2020-03-20%
        //$endDate = DateTime::createFromFormat('Y-m-d H:i:s', $paramJSON['endDate']); // Ex : %2020-03-20%
        $endDate = new DateTime($paramJSON['endDate']); // Ex : %2020-03-20%

        $gensetModService->setGensetMod($smartMod)
            ->setStartDate($startDate)
            ->setEndDate($endDate);
        
        // ######## Récupération des données de consommation et d'approvisionnement de Fuel
        $fuelData = $gensetModService->getConsoFuelData();
        // dump($fuelData);
        $NPSstats = $gensetModService->getNPSstats();
        // dump($NPSstats);
        return $this->json([
            'code'         => 200,
            //'startDate'    => $startDate,
            //'endDate'      => $endDate,
            // 'date'         => $date,
            'Mixed_Conso'            => [
                'date'  => $fuelData['dayBydayConsoData']['dateConso'],
                'conso' => [$fuelData['dayBydayConsoData']['consoFuel'], $fuelData['dayBydayConsoData']['approFuel'], $fuelData['dayBydayConsoData']['duree']]
            ],
            'dataFL'    => [
                'date' => $fuelData['dataFL']['date'],
                'FL'   => $fuelData['dataFL']['FL'],
                'XAF'  => $fuelData['dataFL']['XAF']
            ],
            'statsDureeFonctionnement' => $fuelData['statsDureeFonctionnement'],
            'NPSchart' => $NPSstats['NPSchart'],
            'statsNPS' => $NPSstats['statsNPS'],
            //'Mix2'            => [$S, $P, $Cosfi],
            // 'Load_Level'    => $S,
            // 'S3ph'         => $S3ph,
            // 'dateE'           => $dateE,
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

                    if($paramJSON['date1'] !== '2000-01-01 00:00:00') $date = DateTime::createFromFormat('Y-m-d H:i:s', $paramJSON['date1']);
                    else $date = new DateTime('now');
                    // dd($date);
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
                    if (array_key_exists("P", $paramJSON)) {
                        $dataMod->setP($paramJSON['P']);
                    }
                    if (array_key_exists("S", $paramJSON)) {
                        $dataMod->setS($paramJSON['S']);
                    }
                    if (array_key_exists("Q", $paramJSON)) {
                        $dataMod->setQ($paramJSON['Q']);
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


                    if (!$isNew) {
                        $BATT = "MINB"; // 0
                        $MAINAB = "MAIAB"; // 1
                        $MAINPR = "MAIPR"; // 1
                        $SPEED = "OVSPD"; // 2
                        $LOAD = "OVLOD"; // 3
                        $VOLT = "MINV"; // 4
                        $FREQ = "MINF"; // 5
                        $GENRUN = "GENR"; // 6
                        $GENST = "GENST"; // 6
                        $GOTL = "GOTL"; // 6
                        $GNOTL = "GNOTL"; // 6
                        $FUEL = "LOFL"; // 7
                        $DIFFC = "DIFFC"; // 8
                        $WATFL = "WATFL"; // 9
                        $SFL50 = "SFL50"; // 11
                        $SFL20 = "SFL20"; // 12
                        $SNPW = "SNPW"; // 12

                        /*if ($oldData->getMinBattVolt()  === 0 && $paramJSON['MinBV']  === 1) {
                            $mess = "{\"code\":\"{$BATT}\",\"date\":\"{$date->format('Y-m-d H:i:s')}\"}";
                            //$mess = "{\"code\":\"{$BATT}\",\"date\":\"{$paramJSON['date1']}\"}";

                            $response = $this->forward(
                                'App\Controller\GensetController::sendToAlarmController',
                                [
                                    'mess' => $mess,
                                    'modId'  => $smartMod->getModuleId(),
                                ]
                            );
                        }*/
                        if (array_key_exists("CR", $paramJSON)) {
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
                        }
                        /*if ($oldData->getOverspeed() === 0 && $paramJSON['Overspeed']  === 1) {
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
                        }*/
                        if (array_key_exists("CG", $paramJSON)) {
                         if (($oldData->getCg() === 0 && $paramJSON['CG'] === 1)) {
                             $mess = "{\"code\":\"{$GOTL}\",\"date\":\"{$date->format('Y-m-d H:i:s')}\"}";
                             //$mess = "{\"code\":\"{$GENRUN}\",\"date\":\"{$paramJSON['date1']}\"}";

                             $response = $this->forward('App\Controller\GensetController::sendToAlarmController', [
                                 'mess'   => $mess,
                                 'modId'  => $smartMod->getModuleId(),
                             ]);
                         }
                         if (($oldData->getCg() === 1 && $paramJSON['CG'] === 0)) {
                             $mess = "{\"code\":\"{$GNOTL}\",\"date\":\"{$date->format('Y-m-d H:i:s')}\"}";
                             //$mess = "{\"code\":\"{$GENRUN}\",\"date\":\"{$paramJSON['date1']}\"}";

                             $response = $this->forward('App\Controller\GensetController::sendToAlarmController', [
                                 'mess'   => $mess,
                                 'modId'  => $smartMod->getModuleId(),
                             ]);
                         }
                        }
                        if (array_key_exists("GenRun", $paramJSON)) {
                         if (($oldData->getGensetRunning() === 0 && $paramJSON['GenRun'] === 1)) {
                             $mess = "{\"code\":\"{$GENRUN}\",\"date\":\"{$date->format('Y-m-d H:i:s')}\"}";
                             //$mess = "{\"code\":\"{$GENRUN}\",\"date\":\"{$paramJSON['date1']}\"}";

                             $response = $this->forward('App\Controller\GensetController::sendToAlarmController', [
                                 'mess'   => $mess,
                                 'modId'  => $smartMod->getModuleId(),
                             ]);
                         }
                         if (($oldData->getGensetRunning() === 1 && $paramJSON['GenRun'] === 0)) {
                             $mess = "{\"code\":\"{$GENST}\",\"date\":\"{$date->format('Y-m-d H:i:s')}\"}";
                             //$mess = "{\"code\":\"{$GENST}\",\"date\":\"{$paramJSON['date1']}\"}";

                             $response = $this->forward('App\Controller\GensetController::sendToAlarmController', [
                                 'mess'   => $mess,
                                 'modId'  => $smartMod->getModuleId(),
                             ]);
                         }
                        }

                        if (array_key_exists("CR", $paramJSON) && array_key_exists("CG", $paramJSON) && array_key_exists("P", $paramJSON)) {
                            if (($oldData->getP()  > 1 && $paramJSON['P']  === 0) && ($paramJSON['CR'] === 0)&& ($paramJSON['CG'] === 0)) {
                                $mess = "{\"code\":\"{$SNPW}\",\"date\":\"{$date->format('Y-m-d H:i:s')}\"}";
   
                                $response = $this->forward('App\Controller\GensetController::sendToAlarmController', [
                                    'mess'   => $mess,
                                    'modId'  => $smartMod->getModuleId(),
                                ]);
                            }
                        }
                        if (array_key_exists("LowFuel", $paramJSON)) {
                         if ($oldData->getLowFuel() === 0 && $paramJSON['LowFuel'] === 1) {
                             $mess = "{\"code\":\"{$FUEL}\",\"date\":\"{$date->format('Y-m-d H:i:s')}\"}";
                             //$mess = "{\"code\":\"{$FUEL}\",\"date\":\"{$paramJSON['date1']}\"}";

                             $response = $this->forward('App\Controller\GensetController::sendToAlarmController', [
                                 'mess' => $mess,
                                 'modId'  => $smartMod->getModuleId(),
                             ]);
                         }
                        }
                        if (array_key_exists("FL", $paramJSON)) {
                            if ($oldData->getFuelLevel() > 50 && $paramJSON['FL'] <= 50) {
                                $mess = "{\"code\":\"{$SFL50}\",\"date\":\"{$date->format('Y-m-d H:i:s')}\"}";
                                //$mess = "{\"code\":\"{$SFL50}\",\"date\":\"{$paramJSON['date1']}\"}";
        
                                $response = $this->forward('App\Controller\GensetController::sendToAlarmController', [
                                    'mess' => $mess,
                                    'modId'  => $smartMod->getModuleId(),
                                ]);
                            }
                            if ($oldData->getFuelLevel() > 20 && $paramJSON['FL'] <= 20) {
                                $mess = "{\"code\":\"{$SFL20}\",\"date\":\"{$date->format('Y-m-d H:i:s')}\"}";
                                //$mess = "{\"code\":\"{$SFL20}\",\"date\":\"{$paramJSON['date1']}\"}";
        
                                $response = $this->forward('App\Controller\GensetController::sendToAlarmController', [
                                    'mess' => $mess,
                                    'modId'  => $smartMod->getModuleId(),
                                ]);
                            }
                        }
                        /*if ($oldData->getDifferentialIntervention() === 0 && $paramJSON['DIT'] === 1) {
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
                        }*/
                    }
                    // //dump($dataMod);
                    //die();
                    //Insertion de la nouvelle dataMod dans la BDD
                    $manager->persist($dataMod);
                    $manager->flush();

                    return $this->json([
                        'code' => 200,
                        //'received' => $paramJSON,
                        'date'  => $oldData->getDateTime(),
                        // 'status'   => $response->getStatusCode(),
                        // 'content' => $response->getContent(),
                        //'contentType' => $response->getHeaders()['content-type'][0],
                        'oldCG'   => $oldData->getCg(),
                        'oldCR'   => $oldData->getCr(),
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
                if($paramJSON['date'] !== '2000-01-01 00:00:00') $date = DateTime::createFromFormat('Y-m-d H:i:s', $paramJSON['date']);
                else $date = new DateTime('now');
                // dd($date);
                // $date = DateTime::createFromFormat('Y-m-d H:i:s', $paramJSON['date']);
                //$date = new DateTime('now');
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
                } else if ($smartMod->getModType() == 'Inverter') {
                    //Recherche des modules dans la BDD
                    $gridMod = $manager->getRepository('App:SmartMod')->findOneBy(['moduleId' => $smartMod->getModuleId() . '_0']);
                    $gensetMod = $manager->getRepository('App:SmartMod')->findOneBy(['moduleId' => $smartMod->getModuleId() . '_1']);
                    $loadSiteMod = $manager->getRepository('App:SmartMod')->findOneBy(['moduleId' => $smartMod->getModuleId() . '_2']);

                    $gridData = new LoadEnergyData();
                    $loadSiteData = new LoadEnergyData();
                    $GensetData = new GensetData();
                    //Paramétrage des champs de la nouvelle LoadDataEnergy aux valeurs contenues dans la requête du module
                    if (array_key_exists("date", $paramJSON)) {

                        //Récupération de la date dans la requête et transformation en object de type Date au format date SQL
                        $date = DateTime::createFromFormat('Y-m-d H:i:s', $paramJSON['date']);

                        //Test si un enregistrement correspond à cette date pour ce module
                        /*$data = $manager->getRepository('App:LoadEnergyData')->findOneBy(['dateTime' => $date, 'smartMod' => $smartMod->getId()]);
                    if ($data) {
                        return $this->json([
                            'code'    => 200,
                            'message' => 'data already saved'

                        ], 200);
                    }*/
                        $gridData->setDateTime($date);
                        $loadSiteData->setDateTime($date);
                        $GensetData->setDateTime($date);

                        if ($smartMod->getNbPhases() === 1) {
                            if (array_key_exists("Cosfi", $paramJSON)) {
                                if (count($paramJSON['Cosfi']) >= 3) {
                                    $gridData->setCosfi($paramJSON['Cosfi'][0]);
                                    $GensetData->setCosfi($paramJSON['Cosfi'][1]);
                                    $loadSiteData->setCosfi($paramJSON['Cosfi'][2]);
                                }
                            }
                            if (array_key_exists("Cosfimin", $paramJSON)) {
                                if (count($paramJSON['Cosfimin']) >= 3) {
                                    $gridData->setCosfimin($paramJSON['Cosfimin'][0]); // En kW
                                    $GensetData->setCosfimin($paramJSON['Cosfimin'][1]); // En kW
                                    $loadSiteData->setCosfimin($paramJSON['Cosfimin'][2]); // En kW
                                }
                            }
                            if (array_key_exists("Va", $paramJSON)) {
                                if (count($paramJSON['Va']) >= 3) {
                                    $gridData->setVamoy($paramJSON['Va'][0]);
                                    // $GensetData->setVamoy($paramJSON['Va'][1]);
                                    $loadSiteData->setVamoy($paramJSON['Va'][2]);
                                }
                            }

                            if (array_key_exists("P", $paramJSON)) {
                                if (count($paramJSON['P']) >= 3) {
                                    $gridData->setPmoy($paramJSON['P'][0]); // En kWatts
                                    $GensetData->setP($paramJSON['P'][1]); // En kWatts
                                    $loadSiteData->setPmoy($paramJSON['P'][2]); // En kWatts
                                }
                            }
                            if (array_key_exists("Pmax", $paramJSON)) {
                                if (count($paramJSON['Pmax']) >= 3) {
                                    $gridData->setPmax($paramJSON['Pmax'][0]); // En kW
                                    $GensetData->setPmax($paramJSON['Pmax'][1]); // En kW
                                    $loadSiteData->setPmax($paramJSON['Pmax'][2]); // En kW
                                }
                            }
                            if (array_key_exists("Q", $paramJSON)) {
                                if (count($paramJSON['Q']) >= 3) {
                                    $gridData->setQmoy($paramJSON['Q'][0]); // En kVAR
                                    // $GensetData->setQmoy($paramJSON['Q'][1]); // En kVAR
                                    $loadSiteData->setQmoy($paramJSON['Q'][2]); // En kVAR

                                }
                            }
                            if (array_key_exists("S", $paramJSON)) {
                                if (count($paramJSON['S']) >= 3) {
                                    $gridData->setSmoy($paramJSON['S'][0]); // En kVA
                                    $GensetData->setSmoy($paramJSON['S'][1]); // En kVA
                                    $loadSiteData->setSmoy($paramJSON['S'][2]); // En kVA
                                }
                            }
                            if (array_key_exists("Ea", $paramJSON)) {
                                if (count($paramJSON['Ea']) >= 3) {
                                    $gridData->setEa($paramJSON['Ea'][0]); // En kWh
                                    $GensetData->setTotalEnergy($paramJSON['Ea'][1]); // En kWh
                                    $loadSiteData->setEa($paramJSON['Ea'][2]); // En kWh

                                }
                            }
                            if (array_key_exists("Er", $paramJSON)) {
                                if (count($paramJSON['Er']) >= 3) {
                                    $gridData->setEr($paramJSON['Er'][0]); // En kVARh
                                    // $GensetData->setEr($paramJSON['Er'][1]); // En kVARh
                                    $loadSiteData->setEr($paramJSON['Er'][2]); // En kVARh

                                }
                            }
                        } else if ($smartMod->getNbPhases() === 3) {
                            if (array_key_exists("Va", $paramJSON)) {
                                if (count($paramJSON['Va']) >= 3) {
                                    $gridData->setVamoy($paramJSON['Va'][0]);
                                    $GensetData->setVa($paramJSON['Va'][1]);
                                    $loadSiteData->setVamoy($paramJSON['Va'][2]);
                                }
                            }
                            if (array_key_exists("Vb", $paramJSON)) {
                                if (count($paramJSON['Vb']) >= 3) {
                                    $gridData->setVbmoy($paramJSON['Vb'][0]);
                                    $GensetData->setVb($paramJSON['Vb'][1]);
                                    $loadSiteData->setVbmoy($paramJSON['Vb'][2]);
                                }
                            }
                            if (array_key_exists("Vc", $paramJSON)) {
                                if (count($paramJSON['Vc']) >= 3) {
                                    $gridData->setVcmoy($paramJSON['Vc'][0]);
                                    $GensetData->setVc($paramJSON['Vc'][1]);
                                    $loadSiteData->setVcmoy($paramJSON['Vc'][2]);
                                }
                            }
                            if (array_key_exists("Pa", $paramJSON)) {
                                if (count($paramJSON['Pa']) >= 3) {
                                    $gridData->setPamoy($paramJSON['Pa'][0]); // En kW
                                    $GensetData->setPamoy($paramJSON['Pa'][1]); // En kW
                                    $loadSiteData->setPamoy($paramJSON['Pa'][2]); // En kW
                                }
                            }
                            if (array_key_exists("Pb", $paramJSON)) {
                                if (count($paramJSON['Pb']) >= 3) {
                                    $gridData->setPbmoy($paramJSON['Pb'][0]); // En kW
                                    $GensetData->setPbmoy($paramJSON['Pb'][1]); // En kW
                                    $loadSiteData->setPbmoy($paramJSON['Pb'][2]); // En kW
                                }
                            }
                            if (array_key_exists("Pc", $paramJSON)) {
                                if (count($paramJSON['Pc']) >= 3) {
                                    $gridData->setPcmoy($paramJSON['Pc'][0]); // En kW
                                    $GensetData->setPcmoy($paramJSON['Pc'][1]); // En kW
                                    $loadSiteData->setPcmoy($paramJSON['Pc'][2]); // En kW
                                }
                            }
                            if (array_key_exists("P", $paramJSON)) {
                                if (count($paramJSON['P']) >= 3) {
                                    $gridData->setPmoy($paramJSON['P'][0]); // En kW
                                    $GensetData->setP($paramJSON['P'][1]); // En kW
                                    $loadSiteData->setPmoy($paramJSON['P'][2]); // En kW
                                }
                            }
                            if (array_key_exists("Pamax", $paramJSON)) {
                                if (count($paramJSON['Pamax']) >= 3) {
                                    $gridData->setPamax($paramJSON['Pamax'][0]); // En kW
                                    $GensetData->setPamax($paramJSON['Pamax'][1]); // En kW
                                    $loadSiteData->setPamax($paramJSON['Pamax'][2]); // En kW
                                }
                            }
                            if (array_key_exists("Pbmax", $paramJSON)) {
                                if (count($paramJSON['Pbmax']) >= 3) {
                                    $gridData->setPbmax($paramJSON['Pbmax'][0]); // En kW
                                    $GensetData->setPbmax($paramJSON['Pbmax'][1]); // En kW
                                    $loadSiteData->setPbmax($paramJSON['Pbmax'][2]); // En kW
                                }
                            }
                            if (array_key_exists("Pcmax", $paramJSON)) {
                                if (count($paramJSON['Pcmax']) >= 3) {
                                    $gridData->setPcmax($paramJSON['Pcmax'][0]); // En kW
                                    $GensetData->setPcmax($paramJSON['Pcmax'][1]); // En kW
                                    $loadSiteData->setPcmax($paramJSON['Pcmax'][2]); // En kW
                                }
                            }
                            if (array_key_exists("Pmax", $paramJSON)) {
                                if (count($paramJSON['Pmax']) >= 3) {
                                    $gridData->setPmax($paramJSON['Pmax'][0]); // En kW
                                    $GensetData->setPmax($paramJSON['Pmax'][1]); // En kW
                                    $loadSiteData->setPmax($paramJSON['Pmax'][2]); // En kW
                                }
                            }
                            if (array_key_exists("Sa", $paramJSON)) {
                                if (count($paramJSON['Sa']) >= 3) {
                                    $gridData->setSamoy($paramJSON['Sa'][0]); // En kVA
                                    $GensetData->setSamoy($paramJSON['Sa'][1]); // En kVA
                                    $loadSiteData->setSamoy($paramJSON['Sa'][2]); // En kVA
                                }
                            }
                            if (array_key_exists("Sb", $paramJSON)) {
                                if (count($paramJSON['Sb']) >= 3) {
                                    $gridData->setSbmoy($paramJSON['Sb'][0]); // En kVA
                                    $GensetData->setSbmoy($paramJSON['Sb'][1]); // En kVA
                                    $loadSiteData->setSbmoy($paramJSON['Sb'][2]); // En kVA
                                }
                            }
                            if (array_key_exists("Sc", $paramJSON)) {
                                if (count($paramJSON['Sc']) >= 3) {
                                    $gridData->setScmoy($paramJSON['Sc'][0]); // En kVA
                                    $GensetData->setScmoy($paramJSON['Sc'][1]); // En kVA
                                    $loadSiteData->setScmoy($paramJSON['Sc'][2]); // En kVA
                                }
                            }
                            if (array_key_exists("S", $paramJSON)) {
                                if (count($paramJSON['S']) >= 3) {
                                    $gridData->setSmoy($paramJSON['S'][0]); // En kVA
                                    $GensetData->setSmoy($paramJSON['S'][1]); // En kVA
                                    $loadSiteData->setSmoy($paramJSON['S'][2]); // En kVA
                                }
                            }
                            if (array_key_exists("Samax", $paramJSON)) {
                                if (count($paramJSON['Samax']) >= 3) {
                                    $gridData->setSamax($paramJSON['Samax'][0]); // En kVA
                                    $GensetData->setSamax($paramJSON['Samax'][1]); // En kVA
                                    $loadSiteData->setSamax($paramJSON['Samax'][2]); // En kVA
                                }
                            }
                            if (array_key_exists("Sbmax", $paramJSON)) {
                                if (count($paramJSON['Sbmax']) >= 3) {
                                    $gridData->setSbmax($paramJSON['Sbmax'][0]); // En kVA
                                    $GensetData->setSbmax($paramJSON['Sbmax'][1]); // En kVA
                                    $loadSiteData->setSbmax($paramJSON['Sbmax'][2]); // En kVA
                                }
                            }
                            if (array_key_exists("Scmax", $paramJSON)) {
                                if (count($paramJSON['Scmax']) >= 3) {
                                    $gridData->setScmax($paramJSON['Scmax'][0]); // En kVA
                                    $GensetData->setScmax($paramJSON['Scmax'][1]); // En kVA
                                    $loadSiteData->setScmax($paramJSON['Scmax'][2]); // En kVA
                                }
                            }
                            if (array_key_exists("Smax", $paramJSON)) {
                                if (count($paramJSON['Smax']) >= 3) {
                                    $gridData->setSmax($paramJSON['Smax'][0]); // En kVA
                                    $GensetData->setSmax($paramJSON['Smax'][1]); // En kVA
                                    $loadSiteData->setSmax($paramJSON['Smax'][2]); // En kVA
                                }
                            }
                            if (array_key_exists("Qa", $paramJSON)) {
                                if (count($paramJSON['Qa']) >= 3) {
                                    $gridData->setQamoy($paramJSON['Qa'][0]); // En kVAR
                                    $GensetData->setQa($paramJSON['Qa'][1]); // En kVAR
                                    $loadSiteData->setQamoy($paramJSON['Qa'][2]); // En kVAR
                                }
                            }
                            if (array_key_exists("Qb", $paramJSON)) {
                                if (count($paramJSON['Qb']) >= 3) {
                                    $gridData->setQbmoy($paramJSON['Qb'][0]); // En kVAR
                                    $GensetData->setQb($paramJSON['Qb'][1]); // En kVAR
                                    $loadSiteData->setQbmoy($paramJSON['Qb'][2]); // En kVAR
                                }
                            }
                            if (array_key_exists("Qc", $paramJSON)) {
                                if (count($paramJSON['Qc']) >= 3) {
                                    $gridData->setQcmoy($paramJSON['Qc'][0]); // En kVAR
                                    $GensetData->setQc($paramJSON['Qc'][1]); // En kVAR
                                    $loadSiteData->setQcmoy($paramJSON['Qc'][2]); // En kVAR
                                }
                            }
                            if (array_key_exists("Q", $paramJSON)) {
                                if (count($paramJSON['Q']) >= 3) {
                                    $gridData->setQmoy($paramJSON['Q'][0]); // En kVAR
                                    $GensetData->setQ($paramJSON['Q'][1]); // En kVAR
                                    $loadSiteData->setQmoy($paramJSON['Q'][2]); // En kVAR
                                }
                            }
                            if (array_key_exists("Qamax", $paramJSON)) {
                                if (count($paramJSON['Qamax']) >= 3) {
                                    $gridData->setQamax($paramJSON['Qamax'][0]); // En kVAR
                                    $GensetData->setQamax($paramJSON['Qamax'][1]); // En kVAR
                                    $loadSiteData->setQamax($paramJSON['Qamax'][2]); // En kVAR
                                }
                            }
                            if (array_key_exists("Qbmax", $paramJSON)) {
                                if (count($paramJSON['Qbmax']) >= 3) {
                                    $gridData->setQbmax($paramJSON['Qbmax'][0]); // En kVAR
                                    $GensetData->setQbmax($paramJSON['Qbmax'][1]); // En kVAR
                                    $loadSiteData->setQbmax($paramJSON['Qbmax'][2]); // En kVAR
                                }
                            }
                            if (array_key_exists("Qcmax", $paramJSON)) {
                                if (count($paramJSON['Qcmax']) >= 3) {
                                    $gridData->setQcmax($paramJSON['Qcmax'][0]); // En kVAR
                                    $GensetData->setQcmax($paramJSON['Qcmax'][1]); // En kVAR
                                    $loadSiteData->setQcmax($paramJSON['Qcmax'][2]); // En kVAR
                                }
                            }
                            if (array_key_exists("Qmax", $paramJSON)) {
                                if (count($paramJSON['Qmax']) >= 3) {
                                    $gridData->setQmax($paramJSON['Qmax'][0]); // En kVAR
                                    $GensetData->setQmax($paramJSON['Qmax'][1]); // En kVAR
                                    $loadSiteData->setQmax($paramJSON['Qmax'][2]); // En kVAR
                                }
                            }
                            if (array_key_exists("Cosfia", $paramJSON)) {
                                if (count($paramJSON['Cosfia']) >= 3) {
                                    $gridData->setCosfia($paramJSON['Cosfia'][0]);
                                    $GensetData->setCosfia($paramJSON['Cosfia'][1]);
                                    $loadSiteData->setCosfia($paramJSON['Cosfia'][2]);
                                }
                            }
                            if (array_key_exists("Cosfib", $paramJSON)) {
                                if (count($paramJSON['Cosfib']) >= 3) {
                                    $gridData->setCosfib($paramJSON['Cosfib'][0]);
                                    $GensetData->setCosfib($paramJSON['Cosfib'][1]);
                                    $loadSiteData->setCosfib($paramJSON['Cosfib'][2]);
                                }
                            }
                            if (array_key_exists("Cosfic", $paramJSON)) {
                                if (count($paramJSON['Cosfic']) >= 3) {
                                    $gridData->setCosfic($paramJSON['Cosfic'][0]);
                                    $GensetData->setCosfic($paramJSON['Cosfic'][1]);
                                    $loadSiteData->setCosfic($paramJSON['Cosfic'][2]);
                                }
                            }
                            if (array_key_exists("Cosfi", $paramJSON)) {
                                if (count($paramJSON['Cosfi']) >= 3) {
                                    $gridData->setCosfi($paramJSON['Cosfi'][0]);
                                    $GensetData->setCosfi($paramJSON['Cosfi'][1]);
                                    $loadSiteData->setCosfi($paramJSON['Cosfi'][2]);
                                }
                            }
                            if (array_key_exists("Cosfiamin", $paramJSON)) {
                                if (count($paramJSON['Cosfiamin']) >= 3) {
                                    $gridData->setCosfiamin($paramJSON['Cosfiamin'][0]);
                                    $GensetData->setCosfiamin($paramJSON['Cosfiamin'][1]);
                                    $loadSiteData->setCosfiamin($paramJSON['Cosfiamin'][2]);
                                }
                            }
                            if (array_key_exists("Cosfibmin", $paramJSON)) {
                                if (count($paramJSON['Cosfibmin']) >= 3) {
                                    $gridData->setCosfibmin($paramJSON['Cosfibmin'][0]);
                                    $GensetData->setCosfibmin($paramJSON['Cosfibmin'][1]);
                                    $loadSiteData->setCosfibmin($paramJSON['Cosfibmin'][2]);
                                }
                            }
                            if (array_key_exists("Cosficmin", $paramJSON)) {
                                if (count($paramJSON['Cosficmin']) >= 3) {
                                    $gridData->setCosficmin($paramJSON['Cosficmin'][0]);
                                    $GensetData->setCosficmin($paramJSON['Cosficmin'][1]);
                                    $loadSiteData->setCosficmin($paramJSON['Cosficmin'][2]);
                                }
                            }
                            if (array_key_exists("Cosfimin", $paramJSON)) {
                                if (count($paramJSON['Cosfimin']) >= 3) {
                                    $gridData->setCosfimin($paramJSON['Cosfimin'][0]);
                                    $GensetData->setCosfimin($paramJSON['Cosfimin'][1]);
                                    $loadSiteData->setCosfimin($paramJSON['Cosfimin'][2]);
                                }
                            }
                            if (array_key_exists("Eaa", $paramJSON)) {
                                if (count($paramJSON['Eaa']) >= 3) {
                                    $gridData->setEaa($paramJSON['Eaa'][0]); // En kWh
                                    $GensetData->setEaa($paramJSON['Eaa'][1]); // En kWh
                                    $loadSiteData->setEaa($paramJSON['Eaa'][2]); // En kWh
                                }
                            }
                            if (array_key_exists("Eab", $paramJSON)) {
                                if (count($paramJSON['Eab']) >= 3) {
                                    $gridData->setEab($paramJSON['Eab'][0]); // En kWh
                                    $GensetData->setEab($paramJSON['Eab'][1]); // En kWh
                                    $loadSiteData->setEab($paramJSON['Eab'][2]); // En kWh
                                }
                            }
                            if (array_key_exists("Eac", $paramJSON)) {
                                if (count($paramJSON['Eac']) >= 3) {
                                    $gridData->setEac($paramJSON['Eac'][0]); // En kWh
                                    $GensetData->setEac($paramJSON['Eac'][1]); // En kWh
                                    $loadSiteData->setEac($paramJSON['Eac'][2]); // En kWh
                                }
                            }
                            if (array_key_exists("Ea", $paramJSON)) {
                                if (count($paramJSON['Ea']) >= 3) {
                                    $gridData->setEa($paramJSON['Ea'][0]); // En kWh
                                    $GensetData->setTotalEnergy($paramJSON['Ea'][1]); // En kWh
                                    $loadSiteData->setEa($paramJSON['Ea'][2]); // En kWh
                                }
                            }
                            if (array_key_exists("Era", $paramJSON)) {
                                if (count($paramJSON['Era']) >= 3) {
                                    $gridData->setEra($paramJSON['Era'][0]); // En kVARh
                                    $GensetData->setEra($paramJSON['Era'][1]); // En kVARh
                                    $loadSiteData->setEra($paramJSON['Era'][2]); // En kVARh
                                }
                            }
                            if (array_key_exists("Erb", $paramJSON)) {
                                if (count($paramJSON['Erb']) >= 3) {
                                    $gridData->setErb($paramJSON['Erb'][0]); // En kVARh
                                    $GensetData->setErb($paramJSON['Erb'][1]); // En kVARh
                                    $loadSiteData->setErb($paramJSON['Erb'][2]); // En kVARh
                                }
                            }
                            if (array_key_exists("Erc", $paramJSON)) {
                                if (count($paramJSON['Erc']) >= 3) {
                                    $gridData->setErc($paramJSON['Erc'][0]); // En kVARh
                                    $GensetData->setErc($paramJSON['Erc'][1]); // En kVARh
                                    $loadSiteData->setErc($paramJSON['Erc'][2]); // En kVARh
                                }
                            }
                            if (array_key_exists("Er", $paramJSON)) {
                                if (count($paramJSON['Er']) >= 3) {
                                    $gridData->setEr($paramJSON['Er'][0]); // En kVARh
                                    $GensetData->setEr($paramJSON['Er'][1]); // En kVARh
                                    $loadSiteData->setEr($paramJSON['Er'][2]); // En kVARh
                                }
                            }
                        }

                        if ($gridMod) {
                            $gridData->setSmartMod($gridMod);
                            $manager->persist($gridData);
                        }
                        if ($gensetMod) {
                            $GensetData->setSmartMod($gensetMod);
                            $manager->persist($GensetData);
                        }
                        if ($loadSiteMod) {
                            $loadSiteData->setSmartMod($loadSiteMod);
                            $manager->persist($loadSiteData);
                        }
                        $manager->flush();
                    }

                    return $this->json([
                        'code' => 200,
                        'received' => $paramJSON

                    ], 200);
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
        $paramJSON = $this->getJSONRequest($mess);
        $smartMod = $manager->getRepository('App:SmartMod')->findOneBy(['moduleId' => $modId]);
        if ($smartMod) {
            $alarmCode = $manager->getRepository('App:Alarm')->findOneBy(['code' => $paramJSON['code']]);
            if ($alarmCode) {
                //$date = new DateTime('now');
                $date = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $paramJSON['date']) !== false ? DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $paramJSON['date']) : new DateTimeImmutable('now');
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
                    $data = clone $smartMod->getGensetRealTimeData();
                    $fuelStr = $data->getFuelLevel() != null ? ' avec un niveau de Fuel de ' . $data->getFuelLevel() . '%' : ''; 
                    if ($alarmCode->getCode() === 'GENR') $message = $alarmCode->getLabel() . ' du site ' . $site->getName() . ' survenu(e) le ' . $date->format('d/m/Y à H:i:s') . $fuelStr;
                    else if ($alarmCode->getCode() === 'GENST') {
                        $message = $alarmCode->getLabel() . " du site " . $site->getName() . " survenu le " . $date->format('d/m/Y à H:i:s') . $fuelStr;
                    } else if ($alarmCode->getCode() === 'SFL50' ) {
                        $message = $alarmCode->getLabel() . " dans le réservoir du groupe électrogène du site " . $site->getName() . " détecté le " . $date->format('d/m/Y à H:i:s') . ". Nous vous prions de bien vouloir effectuer une opération de ravitaillement. Niveau de Fuel Actuel : " . $data->getFuelLevel() . '%';
                    }  else if ($alarmCode->getCode() === 'SFL20') {
                        $message = $alarmCode->getLabel() . " dans le réservoir du groupe électrogène du site " . $site->getName() . " détecté le " . $date->format('d/m/Y à H:i:s') . '. Niveau de Fuel de ' . $data->getFuelLevel() . '%';
                    } else if ($alarmCode->getCode() === 'GOTL') {
                        $message = $alarmCode->getLabel() . ' ' . $site->getName() . " depuis le " . $date->format('d/m/Y à H:i:s') . $fuelStr;
                    } else if ($alarmCode->getCode() === 'GNOTL') {
                        $message = $alarmCode->getLabel() . ' du site ' . $site->getName() . " survenue le " . $date->format('d/m/Y à H:i:s');
                    } else if ($alarmCode->getCode() === 'SNPW') {
                        $message = $alarmCode->getLabel() . $site->getName() . " n'est pas alimenté depuis le " . $date->format('d/m/Y à H:i:s');
                    } else $message = $alarmCode->getLabel() . ' du site ' . $site->getName() . ' survenu(e) le ' . $date->format('d/m/Y à H:i:s');
                }

                /*foreach ($site->getContacts() as $contact) {
                    $messageBus->dispatch(new UserNotificationMessage($contact->getId(), $message, $alarmCode->getMedia(), $alarmCode->getAlerte()));
                    //$messageBus->dispatch(new UserNotificationMessage($contact->getId(), $message, 'SMS', ''));
                }*/

                //$adminUsers = [];
                $Users = $manager->getRepository('App:User')->findAll();
                foreach ($Users as $user) {
                    if ($user->getRoles()[0] === 'ROLE_SUPER_ADMIN') {
                        //$adminUsers[] = $user;
                        $messageBus->dispatch(new UserNotificationMessage($user->getId(), $message, 'Email', $alarmCode->getAlerte()));
                    }
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
        }

        return $this->json([
            'code'         => 500,
        ], 500);
    }

}
