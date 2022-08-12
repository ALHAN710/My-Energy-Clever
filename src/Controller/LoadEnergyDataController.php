<?php

namespace App\Controller;

use DateTime;
use DateTimeImmutable;
use App\Entity\GensetData;
use App\Entity\AlarmReporting;
use App\Entity\LoadEnergyData;
use App\Message\UserNotificationMessage;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use App\Controller\ApplicationController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
//use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class LoadEnergyDataController extends ApplicationController
{
    /**
     * @Route("/load/energy/data", name="load_energy_data")
     */
    public function index(): Response
    {
        return $this->render('load_energy_data/index.html.twig', [
            'controller_name' => 'LoadEnergyDataController',
        ]);
    }

    /**
     * Permet de surcharger les données LoadDataEnergy des modules load dans la BDD
     *
     * @Route("/load-energy-data/mod/{modId<[a-zA-Z0-9_-]+>}/add", name="loadEnergyData_add", schemes={"http"}) 
     * 
     * @param SmartMod $smartMod
     * @param EntityManagerInterface $manager
     * @param Request $request
     * @return void
     */
    public function loadDataEnergy_add($modId, EntityManagerInterface $manager, Request $request)
    { //
        //Récupération et vérification des paramètres au format JSON contenu dans la requête
        $paramJSON = $this->getJSONRequest($request->getContent());
        // //dump($paramJSON);
        // //dump($content);
        //die();

        $datetimeData = new LoadEnergyData();

        //Recherche du module dans la BDD
        $smartMod = $manager->getRepository('App:SmartMod')->findOneBy(['moduleId' => $modId]);


        if ($smartMod != null) { // Test si le module existe dans notre BDD
            //data:{"date": "2020-03-20 12:15:00", "sa": 1.2, "sb": 0.7, "sc": 0.85, "va": 225, "vb": 230, "vc": 231, "s3ph": 2.75, "kWh": 1.02, "kvar": 0.4}
            // //dump($smartMod);//Affiche le module
            //die();

            //$date = new DateTime($paramJSON['date']);

            // //dump($date);
            //die();

            if ($smartMod->getModType() == 'Load Meter' || $smartMod->getModType() == 'GRID') {
                //Paramétrage des champs de la nouvelle LoadDataEnergy aux valeurs contenues dans la requête du module
                if (array_key_exists("date", $paramJSON)) {

                    //Récupération de la date dans la requête et transformation en object de type Date au format date SQL
                    $date = DateTime::createFromFormat('Y-m-d H:i:s', $paramJSON['date']);

                    //Test si un enregistrement correspond à cette date pour ce module
                    $data = $manager->getRepository('App:LoadEnergyData')->findOneBy(['dateTime' => $date, 'smartMod' => $smartMod->getId()]);
                    if ($data) {
                        return $this->json([
                            'code'    => 200,
                            'message' => 'data already saved'

                        ], 200);
                    }
                    $datetimeData->setDateTime($date)
                        ->setSmartMod($smartMod);
                    if ($smartMod->getNbPhases() === 1) {
                        if (array_key_exists("Cosfi", $paramJSON)) {
                            /*if ($paramJSON['Cosfi'] == 0 && $paramJSON['Va'] > 380) {
                                return $this->json([
                                    'code' => 200,
                                    'received' => $paramJSON,
                                    'message'  => 'Bad'

                                ], 200);
                            }*/
                            $datetimeData->setCosfi($paramJSON['Cosfi']);
                        }
                        if (array_key_exists("Cosfimin", $paramJSON)) {
                            $datetimeData->setCosfimin($paramJSON['Cosfimin']); // 
                                
                        }
                        if (array_key_exists("Va", $paramJSON)) {
                            $datetimeData->setVamoy($paramJSON['Va']);
                        }

                        if (array_key_exists("P", $paramJSON)) {
                            $datetimeData->setPmoy($paramJSON['P']); // En Watts
                        }
                        if (array_key_exists("Pmax", $paramJSON)) {
                            $datetimeData->setPmax($paramJSON['Pmax']); // En kW
                        }
                        if (array_key_exists("Q", $paramJSON)) {
                            $datetimeData->setQmoy($paramJSON['Q']); // En kVAr
                        }
                        if (array_key_exists("Qmax", $paramJSON)) {
                            $datetimeData->setQmax($paramJSON['Qmax']); // En kVAr
                        }
                        if (array_key_exists("S", $paramJSON)) {
                            $datetimeData->setSmoy($paramJSON['S']); // En VA
                        }
                        if (array_key_exists("Smax", $paramJSON)) {
                            $datetimeData->setSmax($paramJSON['Smax']); // En kVA
                        }
                        if (array_key_exists("Ea", $paramJSON)) {
                            $datetimeData->setEa($paramJSON['Ea']); // En kWh
                        }
                        if (array_key_exists("Er", $paramJSON)) {
                            $datetimeData->setEr($paramJSON['Er']); // En kVARh
                        }
                    } else if ($smartMod->getNbPhases() === 3) {
                        if (array_key_exists("Va", $paramJSON)) {
                            $datetimeData->setVamoy($paramJSON['Va']);
                        }
                        if (array_key_exists("Vb", $paramJSON)) {
                            $datetimeData->setVbmoy($paramJSON['Vb']);
                        }
                        if (array_key_exists("Vc", $paramJSON)) {
                            $datetimeData->setVcmoy($paramJSON['Vc']);
                        }
                        if (array_key_exists("Pa", $paramJSON)) {
                            $datetimeData->setPamoy($paramJSON['Pa']); // En kW
                        }
                        if (array_key_exists("Pamax", $paramJSON)) {
                            $datetimeData->setPamax($paramJSON['Pamax']); // En kW
                        }
                        if (array_key_exists("Pb", $paramJSON)) {
                            $datetimeData->setPbmoy($paramJSON['Pb']); // En kW
                        }
                        if (array_key_exists("Pbmax", $paramJSON)) {
                            $datetimeData->setPbmax($paramJSON['Pbmax']); // En kW
                        }
                        if (array_key_exists("Pc", $paramJSON)) {
                            $datetimeData->setPcmoy($paramJSON['Pc']); // En kW
                        }
                        if (array_key_exists("Pcmax", $paramJSON)) {
                            $datetimeData->setPcmax($paramJSON['Pcmax']); // En kW
                        }
                        if (array_key_exists("P", $paramJSON)) {
                            $datetimeData->setPmoy($paramJSON['P']); // En kW
                        }
                        if (array_key_exists("Pmax", $paramJSON)) {
                            $datetimeData->setPmax($paramJSON['Pmax']); // En kW
                        }
                        if (array_key_exists("Sa", $paramJSON)) {
                            $datetimeData->setSamoy($paramJSON['Sa']); // En kVA
                        }
                        if (array_key_exists("Samax", $paramJSON)) {
                            $datetimeData->setSamax($paramJSON['Samax']); // En kVA
                        }
                        if (array_key_exists("Sb", $paramJSON)) {
                            $datetimeData->setSbmoy($paramJSON['Sb']); // En kVA
                        }
                        if (array_key_exists("Sbmax", $paramJSON)) {
                            $datetimeData->setSbmax($paramJSON['Sbmax']); // En kVA
                        }
                        if (array_key_exists("Sc", $paramJSON)) {
                            $datetimeData->setScmoy($paramJSON['Sc']); // En kVA
                        }
                        if (array_key_exists("Scmax", $paramJSON)) {
                            $datetimeData->setScmax($paramJSON['Scmax']); // En kVA
                        }
                        if (array_key_exists("S", $paramJSON)) {
                            $datetimeData->setSmoy($paramJSON['S']); // En kVA
                        }
                        if (array_key_exists("Smax", $paramJSON)) {
                            $datetimeData->setSmax($paramJSON['Smax']); // En kVA
                        }
                        if (array_key_exists("Qa", $paramJSON)) {
                            $datetimeData->setQamoy($paramJSON['Qa']); // En kVAR
                        }
                        if (array_key_exists("Qamax", $paramJSON)) {
                            $datetimeData->setQamax($paramJSON['Qamax']); // En kVAr
                        }
                        if (array_key_exists("Qb", $paramJSON)) {
                            $datetimeData->setQbmoy($paramJSON['Qb']); // En kVAR
                        }
                        if (array_key_exists("Qbmax", $paramJSON)) {
                            $datetimeData->setQbmax($paramJSON['Qbmax']); // En kVAr
                        }
                        if (array_key_exists("Qc", $paramJSON)) {
                            $datetimeData->setQcmoy($paramJSON['Qc']); // En kVAR
                        }
                        if (array_key_exists("Qcmax", $paramJSON)) {
                            $datetimeData->setQcmax($paramJSON['Qcmax']); // En kVAr
                        }
                        if (array_key_exists("Q", $paramJSON)) {
                            $datetimeData->setQmoy($paramJSON['Q']); // En kVAR
                        }
                        if (array_key_exists("Qmax", $paramJSON)) {
                            $datetimeData->setQmax($paramJSON['Qmax']); // En kVAr
                        }
                        if (array_key_exists("Cosfia", $paramJSON)) {
                            $datetimeData->setCosfia($paramJSON['Cosfia']);
                        }
                        if (array_key_exists("Cosfiamin", $paramJSON)) {
                            $datetimeData->setCosfiamin($paramJSON['Cosfiamin']); // 
                               
                        }
                        if (array_key_exists("Cosfib", $paramJSON)) {
                            $datetimeData->setCosfib($paramJSON['Cosfib']);
                        }
                        if (array_key_exists("Cosfibmin", $paramJSON)) {
                            $datetimeData->setCosfibmin($paramJSON['Cosfibmin']); // 
                               
                        }
                        if (array_key_exists("Cosfic", $paramJSON)) {
                            $datetimeData->setCosfic($paramJSON['Cosfic']);
                        }
                        if (array_key_exists("Cosficmin", $paramJSON)) {
                            $datetimeData->setCosficmin($paramJSON['Cosficmin']); // 
                               
                        }
                        if (array_key_exists("Cosfi", $paramJSON)) {
                            $datetimeData->setCosfi($paramJSON['Cosfi']);
                        }
                        if (array_key_exists("Cosfimin", $paramJSON)) {
                            $datetimeData->setCosfimin($paramJSON['Cosfimin']); // 
                               
                        }
                        if (array_key_exists("Eaa", $paramJSON)) {
                            $datetimeData->setEaa($paramJSON['Eaa']); // En kWh
                        }
                        if (array_key_exists("Eab", $paramJSON)) {
                            $datetimeData->setEab($paramJSON['Eab']); // En kWh
                        }
                        if (array_key_exists("Eac", $paramJSON)) {
                            $datetimeData->setEac($paramJSON['Eac']); // En kWh
                        }
                        if (array_key_exists("Ea", $paramJSON)) {
                            $datetimeData->setEa($paramJSON['Ea']); // En kWh
                        }
                        if (array_key_exists("Era", $paramJSON)) {
                            $datetimeData->setEra($paramJSON['Era']); // En kVARh
                        }
                        if (array_key_exists("Erb", $paramJSON)) {
                            $datetimeData->setErb($paramJSON['Erb']); // En kVARh
                        }
                        if (array_key_exists("Erc", $paramJSON)) {
                            $datetimeData->setErc($paramJSON['Erc']); // En kVARh
                        }
                        if (array_key_exists("Er", $paramJSON)) {
                            $datetimeData->setEr($paramJSON['Er']); // En kVARh
                        }
                    }

                    $manager->persist($datetimeData);
                    $manager->flush();
                }

                return $this->json([
                    'code' => 200,
                    'received' => $paramJSON

                ], 200);
            }
            else if ($smartMod->getModType() == 'Inverter') {
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
                    if($paramJSON['date'] !== '2000-01-01 00:00:00') $date = DateTime::createFromFormat('Y-m-d H:i:s', $paramJSON['date']);
                    else $date = new DateTime('now');
                    // dd($date);
                    // $date = DateTime::createFromFormat('Y-m-d H:i:s', $paramJSON['date']);

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

            // //dump($datetimeData);
            //die();
            //Insertion de la nouvelle datetimeData dans la BDD

        }
        return $this->json([
            'code'     => 403,
            'message'  => "SmartMod don't exist",
            'received' => $paramJSON,
            'modId'    => $modId

        ], 403);
    }

    /**
     * Permet de surcharger les données LoadDataEnergy des modules load dans la BDD
     *
     * @Route("/inverter-energy-data/mod/{modId<[a-zA-Z0-9_-]+>}/add", name="inverterEnergyData_add", schemes={"http"}) 
     * 
     * @param SmartMod $smartMod
     * @param EntityManagerInterface $manager
     * @param Request $request
     * @return void
     */
    public function InverterDataEnergy_add($modId, EntityManagerInterface $manager, Request $request)
    { //
        //Récupération et vérification des paramètres au format JSON contenu dans la requête
        $paramJSON = $this->getJSONRequest($request->getContent());
        // //dump($paramJSON);
        // //dump($content);
        //die();

        //Recherche du module dans la BDD
        $smartMod = $manager->getRepository('App:SmartMod')->findOneBy(['moduleId' => $modId]);


        if ($smartMod != null) { // Test si le module existe dans notre BDD
            //data:{"date": "2020-03-20 12:15:00", "sa": 1.2, "sb": 0.7, "sc": 0.85, "va": 225, "vb": 230, "vc": 231, "s3ph": 2.75, "kWh": 1.02, "kvar": 0.4}
            // //dump($smartMod);//Affiche le module
            //die();

            //$date = new DateTime($paramJSON['date']);

            // //dump($date);
            //die();

            if ($smartMod->getModType() == 'Inverter') {
                //Recherche des modules dans la BDD
                $gridMod = $manager->getRepository('App:SmartMod')->findOneBy(['moduleId' => $smartMod->getModuleId() . '_0']);
                $gensetMod = $manager->getRepository('App:SmartMod')->findOneBy(['moduleId' => $smartMod->getModuleId() . '_1']);
                $loadSiteMod = $manager->getRepository('App:SmartMod')->findOneBy(['moduleId' => $smartMod->getModuleId() . '_2']);
                $loadId = $loadSiteMod !== null ? $loadSiteMod->getId() : 0;
                
                $gridData = new LoadEnergyData();
                $loadSiteData = new LoadEnergyData();
                $GensetData = new GensetData();
                //Paramétrage des champs de la nouvelle LoadDataEnergy aux valeurs contenues dans la requête du module
                if (array_key_exists("date", $paramJSON)) {

                    //Récupération de la date dans la requête et transformation en object de type Date au format date SQL
//                    $date = DateTime::createFromFormat('Y-m-d H:i:s', $paramJSON['date']);
                    if($paramJSON['date'] !== '2000-01-01 00:00:00') $date = DateTime::createFromFormat('Y-m-d H:i:s', $paramJSON['date']);
                    else $date = new DateTime('now', new DateTimeZone('Africa/Douala'));

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

                    $oldPRecord = $manager->createQuery("SELECT d.pmoy AS Pmoy
                                                FROM App\Entity\LoadEnergyData d
                                                JOIN d.smartMod sm 
                                                WHERE d.dateTime =  (SELECT max(d1.dateTime) FROM App\Entity\LoadEnergyData d1 JOIN d1.smartMod sm1 WHERE sm1.id = :smartModId)
                                                AND sm.id = :smartModId                   
                                                ")
                    ->setParameters(array(
                        'smartModId'   => $loadId
                    ))
                    ->getResult();

                    $oldPmoy = null;
                    $oldPmoy = count($oldPRecord) > 0 ? $oldPRecord[0]['Pmoy'] : null;
                    
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
                                dd($smartMod->getSite()->getPowerSubscribed());
                                if($oldPmoy !== null && $smartMod->getSite()->getPowerSubscribed()){
                                    $Psous = $smartMod->getSite()->getPowerSubscribed();
                                    if($paramJSON['P'][2] > $Psous && $oldPmoy < $Psous){
                                        dump($paramJSON['P'][2]);
                                        dd($oldPmoy);
                                    }
                                }
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

                                //dd($smartMod->getSite()->getPowerSubscribed());
                                if($oldPmoy !== null && $smartMod->getSite()->getPowerSubscribed()){
                                    $Psous = $smartMod->getSite()->getPowerSubscribed();
                                    if($paramJSON['P'][2] > $Psous && $oldPmoy < $Psous){
                                        $PSOV = "PSOV"; // 1
                                        
                                        $mess = "{\"code\":\"{$PSOV}\",\"date\":\"{$date->format('Y-m-d H:i:s')}\"}";
                                        
                                        $response = $this->forward(
                                            'App\Controller\LoadEnergyDataController::sendToAlarmController',
                                            [
                                                'mess'   => $mess,
                                                'modId'  => $loadSiteMod->getModuleId(),
                                                ]
                                            );
                                        // dump($loadSiteMod->getModuleId());
                                        // dump($paramJSON['P'][2]);
                                        // dd($oldPmoy);
                                    }
                                }
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
                    'server Time' => $date->format('Y-m-d H:i:s'),
                    'received' => $paramJSON

                ], 200);
            }
            else if ($smartMod->getModType() == 'Inverter DC') {
                //Recherche des modules dans la BDD
                $gridMod = $manager->getRepository('App:SmartMod')->findOneBy(['moduleId' => $smartMod->getModuleId() . '_0']);
                $gensetMod = $manager->getRepository('App:SmartMod')->findOneBy(['moduleId' => $smartMod->getModuleId() . '_1']);
                $loadSiteMod = $manager->getRepository('App:SmartMod')->findOneBy(['moduleId' => $smartMod->getModuleId() . '_2']);
                $InvMod = $manager->getRepository('App:SmartMod')->findOneBy(['moduleId' => $smartMod->getModuleId() . '_3']);
                $loadId = $loadSiteMod !== null ? $loadSiteMod->getId() : 0;

                $gridData = new LoadEnergyData();
                $loadSiteData = new LoadEnergyData();
                $GensetData = new GensetData();
                $InvData = new LoadEnergyData();

                //Paramétrage des champs de la nouvelle LoadDataEnergy aux valeurs contenues dans la requête du module
                if (array_key_exists("date", $paramJSON)) {

                    //Récupération de la date dans la requête et transformation en object de type Date au format date SQL
//                    $date = DateTime::createFromFormat('Y-m-d H:i:s', $paramJSON['date']);
                    if($paramJSON['date'] !== '2000-01-01 00:00:00') $date = DateTime::createFromFormat('Y-m-d H:i:s', $paramJSON['date']);
                    else $date = new DateTime('now', new DateTimeZone('Africa/Douala'));

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
                    $InvData->setDateTime($date);

                    $oldPRecord = $manager->createQuery("SELECT d.pmoy AS Pmoy
                                                FROM App\Entity\LoadEnergyData d
                                                JOIN d.smartMod sm 
                                                WHERE d.dateTime =  (SELECT max(d1.dateTime) FROM App\Entity\LoadEnergyData d1 JOIN d1.smartMod sm1 WHERE sm1.id = :smartModId)
                                                AND sm.id = :smartModId                   
                                                ")
                        ->setParameters(array(
                            'smartModId'   => $loadId
                        ))
                        ->getResult();

                    $oldPmoy = null;
                    $oldPmoy = count($oldPRecord) > 0 ? $oldPRecord[0]['Pmoy'] : null;

                    if ($smartMod->getNbPhases() === 1) {
                        if (array_key_exists("Cosfi", $paramJSON)) {
                            if (count($paramJSON['Cosfi']) >= 4) {
                                $gridData->setCosfi($paramJSON['Cosfi'][0]);
                                $GensetData->setCosfi($paramJSON['Cosfi'][1]);
                                $loadSiteData->setCosfi($paramJSON['Cosfi'][2]);
                                $InvData->setCosfi($paramJSON['Cosfi'][3]);
                            }
                        }
                        if (array_key_exists("Cosfimin", $paramJSON)) {
                            if (count($paramJSON['Cosfimin']) >= 4) {
                                $gridData->setCosfimin($paramJSON['Cosfimin'][0]); // En kW
                                $GensetData->setCosfimin($paramJSON['Cosfimin'][1]); // En kW
                                $loadSiteData->setCosfimin($paramJSON['Cosfimin'][2]); // En kW
                                $InvData->setCosfimin($paramJSON['Cosfimin'][3]); // En kW
                            }
                        }
                        if (array_key_exists("Va", $paramJSON)) {
                            if (count($paramJSON['Va']) >= 4) {
                                $gridData->setVamoy($paramJSON['Va'][0]);
                                // $GensetData->setVamoy($paramJSON['Va'][1]);
                                $loadSiteData->setVamoy($paramJSON['Va'][2]);
                                $InvData->setVamoy($paramJSON['Va'][3]);
                            }
                        }

                        if (array_key_exists("P", $paramJSON)) {
                            if (count($paramJSON['P']) >= 4) {
                                $gridData->setPmoy($paramJSON['P'][0]); // En kWatts
                                $GensetData->setP($paramJSON['P'][1]); // En kWatts
                                $loadSiteData->setPmoy($paramJSON['P'][2]); // En kWatts
                                $InvData->setPmoy($paramJSON['P'][3]); // En kWatts
//                                dd($smartMod->getSite()->getPowerSubscribed());
                                if($oldPmoy !== null && $smartMod->getSite()->getPowerSubscribed()){
                                    $Psous = $smartMod->getSite()->getPowerSubscribed();
                                    if($paramJSON['P'][2] > $Psous && $oldPmoy < $Psous){
//                                        dump($paramJSON['P'][2]);
//                                        dd($oldPmoy);
                                    }
                                }
                            }
                        }
                        if (array_key_exists("Pmax", $paramJSON)) {
                            if (count($paramJSON['Pmax']) >= 4) {
                                $gridData->setPmax($paramJSON['Pmax'][0]); // En kW
                                $GensetData->setPmax($paramJSON['Pmax'][1]); // En kW
                                $loadSiteData->setPmax($paramJSON['Pmax'][2]); // En kW
                                $InvData->setPmax($paramJSON['Pmax'][3]); // En kW
                            }
                        }
                        if (array_key_exists("Q", $paramJSON)) {
                            if (count($paramJSON['Q']) >= 4) {
                                $gridData->setQmoy($paramJSON['Q'][0]); // En kVAR
                                // $GensetData->setQmoy($paramJSON['Q'][1]); // En kVAR
                                $loadSiteData->setQmoy($paramJSON['Q'][2]); // En kVAR
                                $InvData->setQmoy($paramJSON['Q'][3]); // En kVAR

                            }
                        }
                        if (array_key_exists("S", $paramJSON)) {
                            if (count($paramJSON['S']) >= 4) {
                                $gridData->setSmoy($paramJSON['S'][0]); // En kVA
                                $GensetData->setSmoy($paramJSON['S'][1]); // En kVA
                                $loadSiteData->setSmoy($paramJSON['S'][2]); // En kVA
                                $InvData->setSmoy($paramJSON['S'][3]); // En kVA
                            }
                        }
                        if (array_key_exists("Ea", $paramJSON)) {
                            if (count($paramJSON['Ea']) >= 4) {
                                $gridData->setEa($paramJSON['Ea'][0]); // En kWh
                                $GensetData->setTotalEnergy($paramJSON['Ea'][1]); // En kWh
                                $loadSiteData->setEa($paramJSON['Ea'][2]); // En kWh
                                $InvData->setEa($paramJSON['Ea'][3]); // En kWh

                            }
                        }
                        if (array_key_exists("Er", $paramJSON)) {
                            if (count($paramJSON['Er']) >= 4) {
                                $gridData->setEr($paramJSON['Er'][0]); // En kVARh
                                // $GensetData->setEr($paramJSON['Er'][1]); // En kVARh
                                $loadSiteData->setEr($paramJSON['Er'][2]); // En kVARh
                                $InvData->setEr($paramJSON['Er'][3]); // En kVARh

                            }
                        }
                    }
                    else if ($smartMod->getNbPhases() === 3) {
                        if (array_key_exists("Va", $paramJSON)) {
                            if (count($paramJSON['Va']) >= 4) {
                                $gridData->setVamoy($paramJSON['Va'][0]);
                                $GensetData->setVa($paramJSON['Va'][1]);
                                $loadSiteData->setVamoy($paramJSON['Va'][2]);
                                $InvData->setVamoy($paramJSON['Va'][3]);
                            }
                        }
                        if (array_key_exists("Vb", $paramJSON)) {
                            if (count($paramJSON['Vb']) >= 4) {
                                $gridData->setVbmoy($paramJSON['Vb'][0]);
                                $GensetData->setVb($paramJSON['Vb'][1]);
                                $loadSiteData->setVbmoy($paramJSON['Vb'][2]);
                                $InvData->setVbmoy($paramJSON['Vb'][3]);
                            }
                        }
                        if (array_key_exists("Vc", $paramJSON)) {
                            if (count($paramJSON['Vc']) >= 4) {
                                $gridData->setVcmoy($paramJSON['Vc'][0]);
                                $GensetData->setVc($paramJSON['Vc'][1]);
                                $loadSiteData->setVcmoy($paramJSON['Vc'][2]);
                                $InvData->setVcmoy($paramJSON['Vc'][3]);
                            }
                        }
                        if (array_key_exists("Pa", $paramJSON)) {
                            if (count($paramJSON['Pa']) >= 4) {
                                $gridData->setPamoy($paramJSON['Pa'][0]); // En kW
                                $GensetData->setPamoy($paramJSON['Pa'][1]); // En kW
                                $loadSiteData->setPamoy($paramJSON['Pa'][2]); // En kW
                                $InvData->setPamoy($paramJSON['Pa'][3]); // En kW
                            }
                        }
                        if (array_key_exists("Pb", $paramJSON)) {
                            if (count($paramJSON['Pb']) >= 4) {
                                $gridData->setPbmoy($paramJSON['Pb'][0]); // En kW
                                $GensetData->setPbmoy($paramJSON['Pb'][1]); // En kW
                                $loadSiteData->setPbmoy($paramJSON['Pb'][2]); // En kW
                                $InvData->setPbmoy($paramJSON['Pb'][3]); // En kW
                            }
                        }
                        if (array_key_exists("Pc", $paramJSON)) {
                            if (count($paramJSON['Pc']) >= 4) {
                                $gridData->setPcmoy($paramJSON['Pc'][0]); // En kW
                                $GensetData->setPcmoy($paramJSON['Pc'][1]); // En kW
                                $loadSiteData->setPcmoy($paramJSON['Pc'][2]); // En kW
                                $InvData->setPcmoy($paramJSON['Pc'][3]); // En kW
                            }
                        }
                        if (array_key_exists("P", $paramJSON)) {
                            if (count($paramJSON['P']) >= 4) {
                                $gridData->setPmoy($paramJSON['P'][0]); // En kW
                                $GensetData->setP($paramJSON['P'][1]); // En kW
                                $loadSiteData->setPmoy($paramJSON['P'][2]); // En kW
                                $InvData->setPmoy($paramJSON['P'][3]); // En kW

                                //dd($smartMod->getSite()->getPowerSubscribed());
                                if($oldPmoy !== null && $smartMod->getSite()->getPowerSubscribed()){
                                    $Psous = $smartMod->getSite()->getPowerSubscribed();
                                    if($paramJSON['P'][2] > $Psous && $oldPmoy < $Psous){
                                        $PSOV = "PSOV"; // 1

                                        $mess = "{\"code\":\"{$PSOV}\",\"date\":\"{$date->format('Y-m-d H:i:s')}\"}";

                                        $response = $this->forward(
                                            'App\Controller\LoadEnergyDataController::sendToAlarmController',
                                            [
                                                'mess'   => $mess,
                                                'modId'  => $loadSiteMod->getModuleId(),
                                            ]
                                        );
                                        // dump($loadSiteMod->getModuleId());
                                        // dump($paramJSON['P'][2]);
                                        // dd($oldPmoy);
                                    }
                                }
                            }
                        }
                        if (array_key_exists("Pamax", $paramJSON)) {
                            if (count($paramJSON['Pamax']) >= 4) {
                                $gridData->setPamax($paramJSON['Pamax'][0]); // En kW
                                $GensetData->setPamax($paramJSON['Pamax'][1]); // En kW
                                $loadSiteData->setPamax($paramJSON['Pamax'][2]); // En kW
                                $InvData->setPamax($paramJSON['Pamax'][3]); // En kW
                            }
                        }
                        if (array_key_exists("Pbmax", $paramJSON)) {
                            if (count($paramJSON['Pbmax']) >= 4) {
                                $gridData->setPbmax($paramJSON['Pbmax'][0]); // En kW
                                $GensetData->setPbmax($paramJSON['Pbmax'][1]); // En kW
                                $loadSiteData->setPbmax($paramJSON['Pbmax'][2]); // En kW
                                $InvData->setPbmax($paramJSON['Pbmax'][3]); // En kW
                            }
                        }
                        if (array_key_exists("Pcmax", $paramJSON)) {
                            if (count($paramJSON['Pcmax']) >= 4) {
                                $gridData->setPcmax($paramJSON['Pcmax'][0]); // En kW
                                $GensetData->setPcmax($paramJSON['Pcmax'][1]); // En kW
                                $loadSiteData->setPcmax($paramJSON['Pcmax'][2]); // En kW
                                $InvData->setPcmax($paramJSON['Pcmax'][3]); // En kW
                            }
                        }
                        if (array_key_exists("Pmax", $paramJSON)) {
                            if (count($paramJSON['Pmax']) >= 4) {
                                $gridData->setPmax($paramJSON['Pmax'][0]); // En kW
                                $GensetData->setPmax($paramJSON['Pmax'][1]); // En kW
                                $loadSiteData->setPmax($paramJSON['Pmax'][2]); // En kW
                                $InvData->setPmax($paramJSON['Pmax'][3]); // En kW
                            }
                        }
                        if (array_key_exists("Sa", $paramJSON)) {
                            if (count($paramJSON['Sa']) >= 4) {
                                $gridData->setSamoy($paramJSON['Sa'][0]); // En kVA
                                $GensetData->setSamoy($paramJSON['Sa'][1]); // En kVA
                                $loadSiteData->setSamoy($paramJSON['Sa'][2]); // En kVA
                                $InvData->setSamoy($paramJSON['Sa'][3]); // En kVA
                            }
                        }
                        if (array_key_exists("Sb", $paramJSON)) {
                            if (count($paramJSON['Sb']) >= 4) {
                                $gridData->setSbmoy($paramJSON['Sb'][0]); // En kVA
                                $GensetData->setSbmoy($paramJSON['Sb'][1]); // En kVA
                                $loadSiteData->setSbmoy($paramJSON['Sb'][2]); // En kVA
                                $InvData->setSbmoy($paramJSON['Sb'][3]); // En kVA
                            }
                        }
                        if (array_key_exists("Sc", $paramJSON)) {
                            if (count($paramJSON['Sc']) >= 4) {
                                $gridData->setScmoy($paramJSON['Sc'][0]); // En kVA
                                $GensetData->setScmoy($paramJSON['Sc'][1]); // En kVA
                                $loadSiteData->setScmoy($paramJSON['Sc'][2]); // En kVA
                                $InvData->setScmoy($paramJSON['Sc'][3]); // En kVA
                            }
                        }
                        if (array_key_exists("S", $paramJSON)) {
                            if (count($paramJSON['S']) >= 4) {
                                $gridData->setSmoy($paramJSON['S'][0]); // En kVA
                                $GensetData->setSmoy($paramJSON['S'][1]); // En kVA
                                $loadSiteData->setSmoy($paramJSON['S'][2]); // En kVA
                                $InvData->setSmoy($paramJSON['S'][3]); // En kVA
                            }
                        }
                        if (array_key_exists("Samax", $paramJSON)) {
                            if (count($paramJSON['Samax']) >= 4) {
                                $gridData->setSamax($paramJSON['Samax'][0]); // En kVA
                                $GensetData->setSamax($paramJSON['Samax'][1]); // En kVA
                                $loadSiteData->setSamax($paramJSON['Samax'][2]); // En kVA
                                $InvData->setSamax($paramJSON['Samax'][3]); // En kVA
                            }
                        }
                        if (array_key_exists("Sbmax", $paramJSON)) {
                            if (count($paramJSON['Sbmax']) >= 4) {
                                $gridData->setSbmax($paramJSON['Sbmax'][0]); // En kVA
                                $GensetData->setSbmax($paramJSON['Sbmax'][1]); // En kVA
                                $loadSiteData->setSbmax($paramJSON['Sbmax'][2]); // En kVA
                                $InvData->setSbmax($paramJSON['Sbmax'][3]); // En kVA
                            }
                        }
                        if (array_key_exists("Scmax", $paramJSON)) {
                            if (count($paramJSON['Scmax']) >= 4) {
                                $gridData->setScmax($paramJSON['Scmax'][0]); // En kVA
                                $GensetData->setScmax($paramJSON['Scmax'][1]); // En kVA
                                $loadSiteData->setScmax($paramJSON['Scmax'][2]); // En kVA
                                $InvData->setScmax($paramJSON['Scmax'][3]); // En kVA
                            }
                        }
                        if (array_key_exists("Smax", $paramJSON)) {
                            if (count($paramJSON['Smax']) >= 4) {
                                $gridData->setSmax($paramJSON['Smax'][0]); // En kVA
                                $GensetData->setSmax($paramJSON['Smax'][1]); // En kVA
                                $loadSiteData->setSmax($paramJSON['Smax'][2]); // En kVA
                                $InvData->setSmax($paramJSON['Smax'][3]); // En kVA
                            }
                        }
                        if (array_key_exists("Qa", $paramJSON)) {
                            if (count($paramJSON['Qa']) >= 4) {
                                $gridData->setQamoy($paramJSON['Qa'][0]); // En kVAR
                                $GensetData->setQa($paramJSON['Qa'][1]); // En kVAR
                                $loadSiteData->setQamoy($paramJSON['Qa'][2]); // En kVAR
                                $InvData->setQamoy($paramJSON['Qa'][3]); // En kVAR
                            }
                        }
                        if (array_key_exists("Qb", $paramJSON)) {
                            if (count($paramJSON['Qb']) >= 4) {
                                $gridData->setQbmoy($paramJSON['Qb'][0]); // En kVAR
                                $GensetData->setQb($paramJSON['Qb'][1]); // En kVAR
                                $loadSiteData->setQbmoy($paramJSON['Qb'][2]); // En kVAR
                                $InvData->setQbmoy($paramJSON['Qb'][3]); // En kVAR
                            }
                        }
                        if (array_key_exists("Qc", $paramJSON)) {
                            if (count($paramJSON['Qc']) >= 4) {
                                $gridData->setQcmoy($paramJSON['Qc'][0]); // En kVAR
                                $GensetData->setQc($paramJSON['Qc'][1]); // En kVAR
                                $loadSiteData->setQcmoy($paramJSON['Qc'][2]); // En kVAR
                                $InvData->setQcmoy($paramJSON['Qc'][3]); // En kVAR
                            }
                        }
                        if (array_key_exists("Q", $paramJSON)) {
                            if (count($paramJSON['Q']) >= 4) {
                                $gridData->setQmoy($paramJSON['Q'][0]); // En kVAR
                                $GensetData->setQ($paramJSON['Q'][1]); // En kVAR
                                $loadSiteData->setQmoy($paramJSON['Q'][2]); // En kVAR
                                $InvData->setQmoy($paramJSON['Q'][3]); // En kVAR
                            }
                        }
                        if (array_key_exists("Qamax", $paramJSON)) {
                            if (count($paramJSON['Qamax']) >= 4) {
                                $gridData->setQamax($paramJSON['Qamax'][0]); // En kVAR
                                $GensetData->setQamax($paramJSON['Qamax'][1]); // En kVAR
                                $loadSiteData->setQamax($paramJSON['Qamax'][2]); // En kVAR
                                $InvData->setQamax($paramJSON['Qamax'][3]); // En kVAR
                            }
                        }
                        if (array_key_exists("Qbmax", $paramJSON)) {
                            if (count($paramJSON['Qbmax']) >= 4) {
                                $gridData->setQbmax($paramJSON['Qbmax'][0]); // En kVAR
                                $GensetData->setQbmax($paramJSON['Qbmax'][1]); // En kVAR
                                $loadSiteData->setQbmax($paramJSON['Qbmax'][2]); // En kVAR
                                $InvData->setQbmax($paramJSON['Qbmax'][3]); // En kVAR
                            }
                        }
                        if (array_key_exists("Qcmax", $paramJSON)) {
                            if (count($paramJSON['Qcmax']) >= 4) {
                                $gridData->setQcmax($paramJSON['Qcmax'][0]); // En kVAR
                                $GensetData->setQcmax($paramJSON['Qcmax'][1]); // En kVAR
                                $loadSiteData->setQcmax($paramJSON['Qcmax'][2]); // En kVAR
                                $InvData->setQcmax($paramJSON['Qcmax'][3]); // En kVAR
                            }
                        }
                        if (array_key_exists("Qmax", $paramJSON)) {
                            if (count($paramJSON['Qmax']) >= 4) {
                                $gridData->setQmax($paramJSON['Qmax'][0]); // En kVAR
                                $GensetData->setQmax($paramJSON['Qmax'][1]); // En kVAR
                                $loadSiteData->setQmax($paramJSON['Qmax'][2]); // En kVAR
                                $InvData->setQmax($paramJSON['Qmax'][3]); // En kVAR
                            }
                        }
                        if (array_key_exists("Cosfia", $paramJSON)) {
                            if (count($paramJSON['Cosfia']) >= 4) {
                                $gridData->setCosfia($paramJSON['Cosfia'][0]);
                                $GensetData->setCosfia($paramJSON['Cosfia'][1]);
                                $loadSiteData->setCosfia($paramJSON['Cosfia'][2]);
                                $InvData->setCosfia($paramJSON['Cosfia'][3]);
                            }
                        }
                        if (array_key_exists("Cosfib", $paramJSON)) {
                            if (count($paramJSON['Cosfib']) >= 4) {
                                $gridData->setCosfib($paramJSON['Cosfib'][0]);
                                $GensetData->setCosfib($paramJSON['Cosfib'][1]);
                                $loadSiteData->setCosfib($paramJSON['Cosfib'][2]);
                                $InvData->setCosfib($paramJSON['Cosfib'][3]);
                            }
                        }
                        if (array_key_exists("Cosfic", $paramJSON)) {
                            if (count($paramJSON['Cosfic']) >= 4) {
                                $gridData->setCosfic($paramJSON['Cosfic'][0]);
                                $GensetData->setCosfic($paramJSON['Cosfic'][1]);
                                $loadSiteData->setCosfic($paramJSON['Cosfic'][2]);
                                $InvData->setCosfic($paramJSON['Cosfic'][3]);
                            }
                        }
                        if (array_key_exists("Cosfi", $paramJSON)) {
                            if (count($paramJSON['Cosfi']) >= 4) {
                                $gridData->setCosfi($paramJSON['Cosfi'][0]);
                                $GensetData->setCosfi($paramJSON['Cosfi'][1]);
                                $loadSiteData->setCosfi($paramJSON['Cosfi'][2]);
                                $InvData->setCosfi($paramJSON['Cosfi'][3]);
                            }
                        }
                        if (array_key_exists("Cosfiamin", $paramJSON)) {
                            if (count($paramJSON['Cosfiamin']) >= 4) {
                                $gridData->setCosfiamin($paramJSON['Cosfiamin'][0]);
                                $GensetData->setCosfiamin($paramJSON['Cosfiamin'][1]);
                                $loadSiteData->setCosfiamin($paramJSON['Cosfiamin'][2]);
                                $InvData->setCosfiamin($paramJSON['Cosfiamin'][3]);
                            }
                        }
                        if (array_key_exists("Cosfibmin", $paramJSON)) {
                            if (count($paramJSON['Cosfibmin']) >= 4) {
                                $gridData->setCosfibmin($paramJSON['Cosfibmin'][0]);
                                $GensetData->setCosfibmin($paramJSON['Cosfibmin'][1]);
                                $loadSiteData->setCosfibmin($paramJSON['Cosfibmin'][2]);
                                $InvData->setCosfibmin($paramJSON['Cosfibmin'][3]);
                            }
                        }
                        if (array_key_exists("Cosficmin", $paramJSON)) {
                            if (count($paramJSON['Cosficmin']) >= 4) {
                                $gridData->setCosficmin($paramJSON['Cosficmin'][0]);
                                $GensetData->setCosficmin($paramJSON['Cosficmin'][1]);
                                $loadSiteData->setCosficmin($paramJSON['Cosficmin'][2]);
                                $InvData->setCosficmin($paramJSON['Cosficmin'][3]);
                            }
                        }
                        if (array_key_exists("Cosfimin", $paramJSON)) {
                            if (count($paramJSON['Cosfimin']) >= 4) {
                                $gridData->setCosfimin($paramJSON['Cosfimin'][0]);
                                $GensetData->setCosfimin($paramJSON['Cosfimin'][1]);
                                $loadSiteData->setCosfimin($paramJSON['Cosfimin'][2]);
                                $InvData->setCosfimin($paramJSON['Cosfimin'][3]);
                            }
                        }
                        if (array_key_exists("Eaa", $paramJSON)) {
                            if (count($paramJSON['Eaa']) >= 4) {
                                $gridData->setEaa($paramJSON['Eaa'][0]); // En kWh
                                $GensetData->setEaa($paramJSON['Eaa'][1]); // En kWh
                                $loadSiteData->setEaa($paramJSON['Eaa'][2]); // En kWh
                                $InvData->setEaa($paramJSON['Eaa'][3]); // En kWh
                            }
                        }
                        if (array_key_exists("Eab", $paramJSON)) {
                            if (count($paramJSON['Eab']) >= 4) {
                                $gridData->setEab($paramJSON['Eab'][0]); // En kWh
                                $GensetData->setEab($paramJSON['Eab'][1]); // En kWh
                                $loadSiteData->setEab($paramJSON['Eab'][2]); // En kWh
                                $InvData->setEab($paramJSON['Eab'][3]); // En kWh
                            }
                        }
                        if (array_key_exists("Eac", $paramJSON)) {
                            if (count($paramJSON['Eac']) >= 4) {
                                $gridData->setEac($paramJSON['Eac'][0]); // En kWh
                                $GensetData->setEac($paramJSON['Eac'][1]); // En kWh
                                $loadSiteData->setEac($paramJSON['Eac'][2]); // En kWh
                                $InvData->setEac($paramJSON['Eac'][3]); // En kWh
                            }
                        }
                        if (array_key_exists("Ea", $paramJSON)) {
                            if (count($paramJSON['Ea']) >= 4) {
                                $gridData->setEa($paramJSON['Ea'][0]); // En kWh
                                $GensetData->setTotalEnergy($paramJSON['Ea'][1]); // En kWh
                                $loadSiteData->setEa($paramJSON['Ea'][2]); // En kWh
                                $InvData->setEa($paramJSON['Ea'][3]); // En kWh
                            }
                        }
                        if (array_key_exists("Era", $paramJSON)) {
                            if (count($paramJSON['Era']) >= 4) {
                                $gridData->setEra($paramJSON['Era'][0]); // En kVARh
                                $GensetData->setEra($paramJSON['Era'][1]); // En kVARh
                                $loadSiteData->setEra($paramJSON['Era'][2]); // En kVARh
                                $InvData->setEra($paramJSON['Era'][3]); // En kVARh
                            }
                        }
                        if (array_key_exists("Erb", $paramJSON)) {
                            if (count($paramJSON['Erb']) >= 4) {
                                $gridData->setErb($paramJSON['Erb'][0]); // En kVARh
                                $GensetData->setErb($paramJSON['Erb'][1]); // En kVARh
                                $loadSiteData->setErb($paramJSON['Erb'][2]); // En kVARh
                                $InvData->setErb($paramJSON['Erb'][3]); // En kVARh
                            }
                        }
                        if (array_key_exists("Erc", $paramJSON)) {
                            if (count($paramJSON['Erc']) >= 4) {
                                $gridData->setErc($paramJSON['Erc'][0]); // En kVARh
                                $GensetData->setErc($paramJSON['Erc'][1]); // En kVARh
                                $loadSiteData->setErc($paramJSON['Erc'][2]); // En kVARh
                                $InvData->setErc($paramJSON['Erc'][3]); // En kVARh
                            }
                        }
                        if (array_key_exists("Er", $paramJSON)) {
                            if (count($paramJSON['Er']) >= 4) {
                                $gridData->setEr($paramJSON['Er'][0]); // En kVARh
                                $GensetData->setEr($paramJSON['Er'][1]); // En kVARh
                                $loadSiteData->setEr($paramJSON['Er'][2]); // En kVARh
                                $InvData->setEr($paramJSON['Er'][3]); // En kVARh
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
                    if ($InvMod) {
                        $InvData->setSmartMod($InvMod);
                        $manager->persist($InvData);
                    }
                    $manager->flush();
                }

                return $this->json([
                    'code' => 200,
                    'server Time' => $date->format('Y-m-d H:i:s'),
                    'received' => $paramJSON

                ], 200);
            }
            else if ($smartMod->getModType() == 'Inverter BITERNE') {
                //Recherche des modules dans la BDD
                $firstMod = $manager->getRepository('App:SmartMod')->findOneBy(['moduleId' => $smartMod->getModuleId() . '_0']);
                $secondMod = $manager->getRepository('App:SmartMod')->findOneBy(['moduleId' => $smartMod->getModuleId() . '_1']);
                //$gensetMod = $manager->getRepository('App:SmartMod')->findOneBy(['moduleId' => $smartMod->getModuleId() . '_1']);
                $loadSiteMod = $manager->getRepository('App:SmartMod')->findOneBy(['moduleId' => $smartMod->getModuleId() . '_2']);
                $loadId = $loadSiteMod !== null ? $loadSiteMod->getId() : 0;

                $firstData = new LoadEnergyData();
                $secondData = new LoadEnergyData();
                $loadSiteData = new LoadEnergyData();
                //$GensetData = new GensetData();

                //Paramétrage des champs de la nouvelle LoadDataEnergy aux valeurs contenues dans la requête du module
                if (array_key_exists("date", $paramJSON)) {

                    //Récupération de la date dans la requête et transformation en object de type Date au format date SQL
//                    $date = DateTime::createFromFormat('Y-m-d H:i:s', $paramJSON['date']);
                    if($paramJSON['date'] !== '2000-01-01 00:00:00') $date = DateTime::createFromFormat('Y-m-d H:i:s', $paramJSON['date']);
                    else $date = new DateTime('now', new DateTimeZone('Africa/Douala'));

                    //Test si un enregistrement correspond à cette date pour ce module
                    /*$data = $manager->getRepository('App:LoadEnergyData')->findOneBy(['dateTime' => $date, 'smartMod' => $smartMod->getId()]);
                    if ($data) {
                        return $this->json([
                            'code'    => 200,
                            'message' => 'data already saved'

                        ], 200);
                    }*/
                    $firstData->setDateTime($date);
                    $secondData->setDateTime($date);
                    $loadSiteData->setDateTime($date);
                    //$GensetData->setDateTime($date);

                    $oldPRecord = $manager->createQuery("SELECT d.pmoy AS Pmoy
                                                FROM App\Entity\LoadEnergyData d
                                                JOIN d.smartMod sm 
                                                WHERE d.dateTime = (SELECT max(d1.dateTime) FROM App\Entity\LoadEnergyData d1 JOIN d1.smartMod sm1 WHERE sm1.id = :smartModId)
                                                AND sm.id = :smartModId                   
                                                ")
                        ->setParameters(array(
                            'smartModId'   => $loadId
                        ))
                        ->getResult();

                    $oldPmoy = null;
                    $oldPmoy = count($oldPRecord) > 0 ? $oldPRecord[0]['Pmoy'] : null;

                    if ($smartMod->getNbPhases() === 1) {
                        if (array_key_exists("Cosfi", $paramJSON)) {
                            if (count($paramJSON['Cosfi']) >= 3) {
                                $firstData->setCosfi($paramJSON['Cosfi'][0]);
                                $secondData->setCosfi($paramJSON['Cosfi'][1]);
                                //$GensetData->setCosfi($paramJSON['Cosfi'][1]);
                                $loadSiteData->setCosfi($paramJSON['Cosfi'][2]);
                            }
                        }
                        if (array_key_exists("Cosfimin", $paramJSON)) {
                            if (count($paramJSON['Cosfimin']) >= 3) {
                                $firstData->setCosfimin($paramJSON['Cosfimin'][0]); // En kW
                                $secondData->setCosfimin($paramJSON['Cosfimin'][1]); // En kW
                                //$GensetData->setCosfimin($paramJSON['Cosfimin'][1]); // En kW
                                $loadSiteData->setCosfimin($paramJSON['Cosfimin'][2]); // En kW
                            }
                        }
                        if (array_key_exists("Va", $paramJSON)) {
                            if (count($paramJSON['Va']) >= 3) {
                                $firstData->setVamoy($paramJSON['Va'][0]);
                                $secondData->setVamoy($paramJSON['Va'][1]);
                                //// $GensetData->setVamoy($paramJSON['Va'][1]);
                                $loadSiteData->setVamoy($paramJSON['Va'][2]);
                            }
                        }

                        if (array_key_exists("P", $paramJSON)) {
                            if (count($paramJSON['P']) >= 3) {
                                $firstData->setPmoy($paramJSON['P'][0]); // En kWatts
                                $secondData->setPmoy($paramJSON['P'][1]); // En kWatts
                                //$GensetData->setP($paramJSON['P'][1]); // En kWatts
                                $loadSiteData->setPmoy($paramJSON['P'][2]); // En kWatts
//                                dd($smartMod->getSite()->getPowerSubscribed());
                                if($oldPmoy !== null && $smartMod->getSite()->getPowerSubscribed()){
                                    $Psous = $smartMod->getSite()->getPowerSubscribed();
                                    if($paramJSON['P'][2] > $Psous && $oldPmoy < $Psous){
//                                        dump($paramJSON['P'][2]);
//                                        dd($oldPmoy);
                                    }
                                }
                            }
                        }
                        if (array_key_exists("Pmax", $paramJSON)) {
                            if (count($paramJSON['Pmax']) >= 3) {
                                $firstData->setPmax($paramJSON['Pmax'][0]); // En kW
                                $secondData->setPmax($paramJSON['Pmax'][1]); // En kW
                                //$GensetData->setPmax($paramJSON['Pmax'][1]); // En kW
                                $loadSiteData->setPmax($paramJSON['Pmax'][2]); // En kW
                            }
                        }
                        if (array_key_exists("Q", $paramJSON)) {
                            if (count($paramJSON['Q']) >= 3) {
                                $firstData->setQmoy($paramJSON['Q'][0]); // En kVAR
                                $secondData->setQmoy($paramJSON['Q'][1]); // En kVAR
                                //// $GensetData->setQmoy($paramJSON['Q'][1]); // En kVAR
                                $loadSiteData->setQmoy($paramJSON['Q'][2]); // En kVAR

                            }
                        }
                        if (array_key_exists("S", $paramJSON)) {
                            if (count($paramJSON['S']) >= 3) {
                                $firstData->setSmoy($paramJSON['S'][0]); // En kVA
                                $secondData->setSmoy($paramJSON['S'][1]); // En kVA
                                //$GensetData->setSmoy($paramJSON['S'][1]); // En kVA
                                $loadSiteData->setSmoy($paramJSON['S'][2]); // En kVA
                            }
                        }
                        if (array_key_exists("Ea", $paramJSON)) {
                            if (count($paramJSON['Ea']) >= 3) {
                                $firstData->setEa($paramJSON['Ea'][0]); // En kWh
                                $secondData->setEa($paramJSON['Ea'][1]); // En kWh
                                //$GensetData->setTotalEnergy($paramJSON['Ea'][1]); // En kWh
                                $loadSiteData->setEa($paramJSON['Ea'][2]); // En kWh

                            }
                        }
                        if (array_key_exists("Er", $paramJSON)) {
                            if (count($paramJSON['Er']) >= 3) {
                                $firstData->setEr($paramJSON['Er'][0]); // En kVARh
                                $secondData->setEr($paramJSON['Er'][1]); // En kVARh
                                // $GensetData->setEr($paramJSON['Er'][1]); // En kVARh
                                $loadSiteData->setEr($paramJSON['Er'][2]); // En kVARh

                            }
                        }
                    }
                    else if ($smartMod->getNbPhases() === 3) {
                        if (array_key_exists("Va", $paramJSON)) {
                            if (count($paramJSON['Va']) >= 3) {
                                $firstData->setVamoy($paramJSON['Va'][0]);
                                $secondData->setVamoy($paramJSON['Va'][1]);
                                //$GensetData->setVa($paramJSON['Va'][1]);
                                $loadSiteData->setVamoy($paramJSON['Va'][2]);
                            }
                        }
                        if (array_key_exists("Vb", $paramJSON)) {
                            if (count($paramJSON['Vb']) >= 3) {
                                $firstData->setVbmoy($paramJSON['Vb'][0]);
                                $secondData->setVbmoy($paramJSON['Vb'][1]);
                                //$GensetData->setVb($paramJSON['Vb'][1]);
                                $loadSiteData->setVbmoy($paramJSON['Vb'][2]);
                            }
                        }
                        if (array_key_exists("Vc", $paramJSON)) {
                            if (count($paramJSON['Vc']) >= 3) {
                                $firstData->setVcmoy($paramJSON['Vc'][0]);
                                $secondData->setVcmoy($paramJSON['Vc'][1]);
                                //$GensetData->setVc($paramJSON['Vc'][1]);
                                $loadSiteData->setVcmoy($paramJSON['Vc'][2]);
                            }
                        }
                        if (array_key_exists("Pa", $paramJSON)) {
                            if (count($paramJSON['Pa']) >= 3) {
                                $firstData->setPamoy($paramJSON['Pa'][0]); // En kW
                                $secondData->setPamoy($paramJSON['Pa'][1]); // En kW
                                //$GensetData->setPamoy($paramJSON['Pa'][1]); // En kW
                                $loadSiteData->setPamoy($paramJSON['Pa'][2]); // En kW
                            }
                        }
                        if (array_key_exists("Pb", $paramJSON)) {
                            if (count($paramJSON['Pb']) >= 3) {
                                $firstData->setPbmoy($paramJSON['Pb'][0]); // En kW
                                $secondData->setPbmoy($paramJSON['Pb'][1]); // En kW
                                //$GensetData->setPbmoy($paramJSON['Pb'][1]); // En kW
                                $loadSiteData->setPbmoy($paramJSON['Pb'][2]); // En kW
                            }
                        }
                        if (array_key_exists("Pc", $paramJSON)) {
                            if (count($paramJSON['Pc']) >= 3) {
                                $firstData->setPcmoy($paramJSON['Pc'][0]); // En kW
                                $secondData->setPcmoy($paramJSON['Pc'][1]); // En kW
                                //$GensetData->setPcmoy($paramJSON['Pc'][1]); // En kW
                                $loadSiteData->setPcmoy($paramJSON['Pc'][2]); // En kW
                            }
                        }
                        if (array_key_exists("P", $paramJSON)) {
                            if (count($paramJSON['P']) >= 3) {
                                $firstData->setPmoy($paramJSON['P'][0]); // En kW
                                $secondData->setPmoy($paramJSON['P'][1]); // En kW
                                //$GensetData->setP($paramJSON['P'][1]); // En kW
                                $loadSiteData->setPmoy($paramJSON['P'][2]); // En kW

                                //dd($smartMod->getSite()->getPowerSubscribed());
                                if($oldPmoy !== null && $smartMod->getSite()->getPowerSubscribed()){
                                    $Psous = $smartMod->getSite()->getPowerSubscribed();
                                    if($paramJSON['P'][2] > $Psous && $oldPmoy < $Psous){
                                        $PSOV = "PSOV"; // 1

                                        $mess = "{\"code\":\"{$PSOV}\",\"date\":\"{$date->format('Y-m-d H:i:s')}\"}";

                                        $response = $this->forward(
                                            'App\Controller\LoadEnergyDataController::sendToAlarmController',
                                            [
                                                'mess'   => $mess,
                                                'modId'  => $loadSiteMod->getModuleId(),
                                            ]
                                        );
                                        // dump($loadSiteMod->getModuleId());
                                        // dump($paramJSON['P'][2]);
                                        // dd($oldPmoy);
                                    }
                                }
                            }
                        }
                        if (array_key_exists("Pamax", $paramJSON)) {
                            if (count($paramJSON['Pamax']) >= 3) {
                                $firstData->setPamax($paramJSON['Pamax'][0]); // En kW
                                $secondData->setPamax($paramJSON['Pamax'][1]); // En kW
                                //$GensetData->setPamax($paramJSON['Pamax'][1]); // En kW
                                $loadSiteData->setPamax($paramJSON['Pamax'][2]); // En kW
                            }
                        }
                        if (array_key_exists("Pbmax", $paramJSON)) {
                            if (count($paramJSON['Pbmax']) >= 3) {
                                $firstData->setPbmax($paramJSON['Pbmax'][0]); // En kW
                                $secondData->setPbmax($paramJSON['Pbmax'][1]); // En kW
                                //$GensetData->setPbmax($paramJSON['Pbmax'][1]); // En kW
                                $loadSiteData->setPbmax($paramJSON['Pbmax'][2]); // En kW
                            }
                        }
                        if (array_key_exists("Pcmax", $paramJSON)) {
                            if (count($paramJSON['Pcmax']) >= 3) {
                                $firstData->setPcmax($paramJSON['Pcmax'][0]); // En kW
                                $secondData->setPcmax($paramJSON['Pcmax'][1]); // En kW
                                //$GensetData->setPcmax($paramJSON['Pcmax'][1]); // En kW
                                $loadSiteData->setPcmax($paramJSON['Pcmax'][2]); // En kW
                            }
                        }
                        if (array_key_exists("Pmax", $paramJSON)) {
                            if (count($paramJSON['Pmax']) >= 3) {
                                $firstData->setPmax($paramJSON['Pmax'][0]); // En kW
                                $secondData->setPmax($paramJSON['Pmax'][1]); // En kW
                                //$GensetData->setPmax($paramJSON['Pmax'][1]); // En kW
                                $loadSiteData->setPmax($paramJSON['Pmax'][2]); // En kW
                            }
                        }
                        if (array_key_exists("Sa", $paramJSON)) {
                            if (count($paramJSON['Sa']) >= 3) {
                                $firstData->setSamoy($paramJSON['Sa'][0]); // En kVA
                                $secondData->setSamoy($paramJSON['Sa'][1]); // En kVA
                                //$GensetData->setSamoy($paramJSON['Sa'][1]); // En kVA
                                $loadSiteData->setSamoy($paramJSON['Sa'][2]); // En kVA
                            }
                        }
                        if (array_key_exists("Sb", $paramJSON)) {
                            if (count($paramJSON['Sb']) >= 3) {
                                $firstData->setSbmoy($paramJSON['Sb'][0]); // En kVA
                                $secondData->setSbmoy($paramJSON['Sb'][1]); // En kVA
                                //$GensetData->setSbmoy($paramJSON['Sb'][1]); // En kVA
                                $loadSiteData->setSbmoy($paramJSON['Sb'][2]); // En kVA
                            }
                        }
                        if (array_key_exists("Sc", $paramJSON)) {
                            if (count($paramJSON['Sc']) >= 3) {
                                $firstData->setScmoy($paramJSON['Sc'][0]); // En kVA
                                $secondData->setScmoy($paramJSON['Sc'][1]); // En kVA
                                //$GensetData->setScmoy($paramJSON['Sc'][1]); // En kVA
                                $loadSiteData->setScmoy($paramJSON['Sc'][2]); // En kVA
                            }
                        }
                        if (array_key_exists("S", $paramJSON)) {
                            if (count($paramJSON['S']) >= 3) {
                                $firstData->setSmoy($paramJSON['S'][0]); // En kVA
                                $secondData->setSmoy($paramJSON['S'][1]); // En kVA
                                //$GensetData->setSmoy($paramJSON['S'][1]); // En kVA
                                $loadSiteData->setSmoy($paramJSON['S'][2]); // En kVA
                            }
                        }
                        if (array_key_exists("Samax", $paramJSON)) {
                            if (count($paramJSON['Samax']) >= 3) {
                                $firstData->setSamax($paramJSON['Samax'][0]); // En kVA
                                $secondData->setSamax($paramJSON['Samax'][1]); // En kVA
                                //$GensetData->setSamax($paramJSON['Samax'][1]); // En kVA
                                $loadSiteData->setSamax($paramJSON['Samax'][2]); // En kVA
                            }
                        }
                        if (array_key_exists("Sbmax", $paramJSON)) {
                            if (count($paramJSON['Sbmax']) >= 3) {
                                $firstData->setSbmax($paramJSON['Sbmax'][0]); // En kVA
                                $secondData->setSbmax($paramJSON['Sbmax'][1]); // En kVA
                                //$GensetData->setSbmax($paramJSON['Sbmax'][1]); // En kVA
                                $loadSiteData->setSbmax($paramJSON['Sbmax'][2]); // En kVA
                            }
                        }
                        if (array_key_exists("Scmax", $paramJSON)) {
                            if (count($paramJSON['Scmax']) >= 3) {
                                $firstData->setScmax($paramJSON['Scmax'][0]); // En kVA
                                $secondData->setScmax($paramJSON['Scmax'][1]); // En kVA
                                //$GensetData->setScmax($paramJSON['Scmax'][1]); // En kVA
                                $loadSiteData->setScmax($paramJSON['Scmax'][2]); // En kVA
                            }
                        }
                        if (array_key_exists("Smax", $paramJSON)) {
                            if (count($paramJSON['Smax']) >= 3) {
                                $firstData->setSmax($paramJSON['Smax'][0]); // En kVA
                                $secondData->setSmax($paramJSON['Smax'][1]); // En kVA
                                //$GensetData->setSmax($paramJSON['Smax'][1]); // En kVA
                                $loadSiteData->setSmax($paramJSON['Smax'][2]); // En kVA
                            }
                        }
                        if (array_key_exists("Qa", $paramJSON)) {
                            if (count($paramJSON['Qa']) >= 3) {
                                $firstData->setQamoy($paramJSON['Qa'][0]); // En kVAR
                                $secondData->setQamoy($paramJSON['Qa'][1]); // En kVAR
                                //$GensetData->setQa($paramJSON['Qa'][1]); // En kVAR
                                $loadSiteData->setQamoy($paramJSON['Qa'][2]); // En kVAR
                            }
                        }
                        if (array_key_exists("Qb", $paramJSON)) {
                            if (count($paramJSON['Qb']) >= 3) {
                                $firstData->setQbmoy($paramJSON['Qb'][0]); // En kVAR
                                $secondData->setQbmoy($paramJSON['Qb'][1]); // En kVAR
                                //$GensetData->setQb($paramJSON['Qb'][1]); // En kVAR
                                $loadSiteData->setQbmoy($paramJSON['Qb'][2]); // En kVAR
                            }
                        }
                        if (array_key_exists("Qc", $paramJSON)) {
                            if (count($paramJSON['Qc']) >= 3) {
                                $firstData->setQcmoy($paramJSON['Qc'][0]); // En kVAR
                                $secondData->setQcmoy($paramJSON['Qc'][1]); // En kVAR
                                //$GensetData->setQc($paramJSON['Qc'][1]); // En kVAR
                                $loadSiteData->setQcmoy($paramJSON['Qc'][2]); // En kVAR
                            }
                        }
                        if (array_key_exists("Q", $paramJSON)) {
                            if (count($paramJSON['Q']) >= 3) {
                                $firstData->setQmoy($paramJSON['Q'][0]); // En kVAR
                                $secondData->setQmoy($paramJSON['Q'][1]); // En kVAR
                                //$GensetData->setQ($paramJSON['Q'][1]); // En kVAR
                                $loadSiteData->setQmoy($paramJSON['Q'][2]); // En kVAR
                            }
                        }
                        if (array_key_exists("Qamax", $paramJSON)) {
                            if (count($paramJSON['Qamax']) >= 3) {
                                $firstData->setQamax($paramJSON['Qamax'][0]); // En kVAR
                                $secondData->setQamax($paramJSON['Qamax'][1]); // En kVAR
                                //$GensetData->setQamax($paramJSON['Qamax'][1]); // En kVAR
                                $loadSiteData->setQamax($paramJSON['Qamax'][2]); // En kVAR
                            }
                        }
                        if (array_key_exists("Qbmax", $paramJSON)) {
                            if (count($paramJSON['Qbmax']) >= 3) {
                                $firstData->setQbmax($paramJSON['Qbmax'][0]); // En kVAR
                                $secondData->setQbmax($paramJSON['Qbmax'][1]); // En kVAR
                                //$GensetData->setQbmax($paramJSON['Qbmax'][1]); // En kVAR
                                $loadSiteData->setQbmax($paramJSON['Qbmax'][2]); // En kVAR
                            }
                        }
                        if (array_key_exists("Qcmax", $paramJSON)) {
                            if (count($paramJSON['Qcmax']) >= 3) {
                                $firstData->setQcmax($paramJSON['Qcmax'][0]); // En kVAR
                                $secondData->setQcmax($paramJSON['Qcmax'][1]); // En kVAR
                                //$GensetData->setQcmax($paramJSON['Qcmax'][1]); // En kVAR
                                $loadSiteData->setQcmax($paramJSON['Qcmax'][2]); // En kVAR
                            }
                        }
                        if (array_key_exists("Qmax", $paramJSON)) {
                            if (count($paramJSON['Qmax']) >= 3) {
                                $firstData->setQmax($paramJSON['Qmax'][0]); // En kVAR
                                $secondData->setQmax($paramJSON['Qmax'][1]); // En kVAR
                                //$GensetData->setQmax($paramJSON['Qmax'][1]); // En kVAR
                                $loadSiteData->setQmax($paramJSON['Qmax'][2]); // En kVAR
                            }
                        }
                        if (array_key_exists("Cosfia", $paramJSON)) {
                            if (count($paramJSON['Cosfia']) >= 3) {
                                $firstData->setCosfia($paramJSON['Cosfia'][0]);
                                $secondData->setCosfia($paramJSON['Cosfia'][1]);
                                //$GensetData->setCosfia($paramJSON['Cosfia'][1]);
                                $loadSiteData->setCosfia($paramJSON['Cosfia'][2]);
                            }
                        }
                        if (array_key_exists("Cosfib", $paramJSON)) {
                            if (count($paramJSON['Cosfib']) >= 3) {
                                $firstData->setCosfib($paramJSON['Cosfib'][0]);
                                $secondData->setCosfib($paramJSON['Cosfib'][1]);
                                //$GensetData->setCosfib($paramJSON['Cosfib'][1]);
                                $loadSiteData->setCosfib($paramJSON['Cosfib'][2]);
                            }
                        }
                        if (array_key_exists("Cosfic", $paramJSON)) {
                            if (count($paramJSON['Cosfic']) >= 3) {
                                $firstData->setCosfic($paramJSON['Cosfic'][0]);
                                $secondData->setCosfic($paramJSON['Cosfic'][1]);
                                //$GensetData->setCosfic($paramJSON['Cosfic'][1]);
                                $loadSiteData->setCosfic($paramJSON['Cosfic'][2]);
                            }
                        }
                        if (array_key_exists("Cosfi", $paramJSON)) {
                            if (count($paramJSON['Cosfi']) >= 3) {
                                $firstData->setCosfi($paramJSON['Cosfi'][0]);
                                $secondData->setCosfi($paramJSON['Cosfi'][1]);
                                //$GensetData->setCosfi($paramJSON['Cosfi'][1]);
                                $loadSiteData->setCosfi($paramJSON['Cosfi'][2]);
                            }
                        }
                        if (array_key_exists("Cosfiamin", $paramJSON)) {
                            if (count($paramJSON['Cosfiamin']) >= 3) {
                                $firstData->setCosfiamin($paramJSON['Cosfiamin'][0]);
                                $secondData->setCosfiamin($paramJSON['Cosfiamin'][1]);
                                //$GensetData->setCosfiamin($paramJSON['Cosfiamin'][1]);
                                $loadSiteData->setCosfiamin($paramJSON['Cosfiamin'][2]);
                            }
                        }
                        if (array_key_exists("Cosfibmin", $paramJSON)) {
                            if (count($paramJSON['Cosfibmin']) >= 3) {
                                $firstData->setCosfibmin($paramJSON['Cosfibmin'][0]);
                                $secondData->setCosfibmin($paramJSON['Cosfibmin'][1]);
                                //$GensetData->setCosfibmin($paramJSON['Cosfibmin'][1]);
                                $loadSiteData->setCosfibmin($paramJSON['Cosfibmin'][2]);
                            }
                        }
                        if (array_key_exists("Cosficmin", $paramJSON)) {
                            if (count($paramJSON['Cosficmin']) >= 3) {
                                $firstData->setCosficmin($paramJSON['Cosficmin'][0]);
                                $secondData->setCosficmin($paramJSON['Cosficmin'][1]);
                                //$GensetData->setCosficmin($paramJSON['Cosficmin'][1]);
                                $loadSiteData->setCosficmin($paramJSON['Cosficmin'][2]);
                            }
                        }
                        if (array_key_exists("Cosfimin", $paramJSON)) {
                            if (count($paramJSON['Cosfimin']) >= 3) {
                                $firstData->setCosfimin($paramJSON['Cosfimin'][0]);
                                $secondData->setCosfimin($paramJSON['Cosfimin'][1]);
                                //$GensetData->setCosfimin($paramJSON['Cosfimin'][1]);
                                $loadSiteData->setCosfimin($paramJSON['Cosfimin'][2]);
                            }
                        }
                        if (array_key_exists("Eaa", $paramJSON)) {
                            if (count($paramJSON['Eaa']) >= 3) {
                                $firstData->setEaa($paramJSON['Eaa'][0]); // En kWh
                                $secondData->setEaa($paramJSON['Eaa'][1]); // En kWh
                                //$GensetData->setEaa($paramJSON['Eaa'][1]); // En kWh
                                $loadSiteData->setEaa($paramJSON['Eaa'][2]); // En kWh
                            }
                        }
                        if (array_key_exists("Eab", $paramJSON)) {
                            if (count($paramJSON['Eab']) >= 3) {
                                $firstData->setEab($paramJSON['Eab'][0]); // En kWh
                                $secondData->setEab($paramJSON['Eab'][1]); // En kWh
                                //$GensetData->setEab($paramJSON['Eab'][1]); // En kWh
                                $loadSiteData->setEab($paramJSON['Eab'][2]); // En kWh
                            }
                        }
                        if (array_key_exists("Eac", $paramJSON)) {
                            if (count($paramJSON['Eac']) >= 3) {
                                $firstData->setEac($paramJSON['Eac'][0]); // En kWh
                                $secondData->setEac($paramJSON['Eac'][1]); // En kWh
                                //$GensetData->setEac($paramJSON['Eac'][1]); // En kWh
                                $loadSiteData->setEac($paramJSON['Eac'][2]); // En kWh
                            }
                        }
                        if (array_key_exists("Ea", $paramJSON)) {
                            if (count($paramJSON['Ea']) >= 3) {
                                $firstData->setEa($paramJSON['Ea'][0]); // En kWh
                                $secondData->setEa($paramJSON['Ea'][1]); // En kWh
                                //$GensetData->setTotalEnergy($paramJSON['Ea'][1]); // En kWh
                                $loadSiteData->setEa($paramJSON['Ea'][2]); // En kWh
                            }
                        }
                        if (array_key_exists("Era", $paramJSON)) {
                            if (count($paramJSON['Era']) >= 3) {
                                $firstData->setEra($paramJSON['Era'][0]); // En kVARh
                                $secondData->setEra($paramJSON['Era'][1]); // En kVARh
                                //$GensetData->setEra($paramJSON['Era'][1]); // En kVARh
                                $loadSiteData->setEra($paramJSON['Era'][2]); // En kVARh
                            }
                        }
                        if (array_key_exists("Erb", $paramJSON)) {
                            if (count($paramJSON['Erb']) >= 3) {
                                $firstData->setErb($paramJSON['Erb'][0]); // En kVARh
                                $secondData->setErb($paramJSON['Erb'][1]); // En kVARh
                                //$GensetData->setErb($paramJSON['Erb'][1]); // En kVARh
                                $loadSiteData->setErb($paramJSON['Erb'][2]); // En kVARh
                            }
                        }
                        if (array_key_exists("Erc", $paramJSON)) {
                            if (count($paramJSON['Erc']) >= 3) {
                                $firstData->setErc($paramJSON['Erc'][0]); // En kVARh
                                $secondData->setErc($paramJSON['Erc'][1]); // En kVARh
                                //$GensetData->setErc($paramJSON['Erc'][1]); // En kVARh
                                $loadSiteData->setErc($paramJSON['Erc'][2]); // En kVARh
                            }
                        }
                        if (array_key_exists("Er", $paramJSON)) {
                            if (count($paramJSON['Er']) >= 3) {
                                $firstData->setEr($paramJSON['Er'][0]); // En kVARh
                                $secondData->setEr($paramJSON['Er'][1]); // En kVARh
                                //$GensetData->setEr($paramJSON['Er'][1]); // En kVARh
                                $loadSiteData->setEr($paramJSON['Er'][2]); // En kVARh
                            }
                        }
                    }

                    if ($firstMod) {
                        $firstData->setSmartMod($firstMod);
                        $manager->persist($firstData);
                    }
                    if ($secondMod) {
                        $secondData->setSmartMod($secondMod);
                        $manager->persist($secondData);
                    }
                    if ($loadSiteMod) {
                        $loadSiteData->setSmartMod($loadSiteMod);
                        $manager->persist($loadSiteData);
                    }
                    //dd($paramJSON);
                    $manager->flush();
                }

                return $this->json([
                    'code' => 200,
                    'server Time' => $date->format('Y-m-d H:i:s'),
                    'received' => $paramJSON

                ], 200);
            }
            else if ($smartMod->getModType() == 'Central Meter_X6') {
                //Recherche des modules dans la BDD
                $mod = [];
                $data = [];
                for ($i = 0; $i < 6; $i++) {
                    $mod[] = $manager->getRepository('App:SmartMod')->findOneBy(['moduleId' => $smartMod->getModuleId() . "_{$i}"]);
                    $data[] = new LoadEnergyData();
                }

                /*$mod0 = $manager->getRepository('App:SmartMod')->findOneBy(['moduleId' => $smartMod->getModuleId() . '_0']);
                $mod1 = $manager->getRepository('App:SmartMod')->findOneBy(['moduleId' => $smartMod->getModuleId() . '_1']);
                $mod2 = $manager->getRepository('App:SmartMod')->findOneBy(['moduleId' => $smartMod->getModuleId() . '_2']);
                $mod3 = $manager->getRepository('App:SmartMod')->findOneBy(['moduleId' => $smartMod->getModuleId() . '_3']);
                $mod4 = $manager->getRepository('App:SmartMod')->findOneBy(['moduleId' => $smartMod->getModuleId() . '_4']);
                $mod5 = $manager->getRepository('App:SmartMod')->findOneBy(['moduleId' => $smartMod->getModuleId() . '_5']);

                $data0 = new LoadEnergyData();
                $data1 = new LoadEnergyData();
                $data2 = new LoadEnergyData();
                $data3 = new LoadEnergyData();
                $data4 = new LoadEnergyData();
                $data5 = new LoadEnergyData();*/

                //Paramétrage des champs de la nouvelle LoadDataEnergy aux valeurs contenues dans la requête du module
                if (array_key_exists("date", $paramJSON)) {

                    //Récupération de la date dans la requête et transformation en object de type Date au format date SQL
//                    $date = DateTime::createFromFormat('Y-m-d H:i:s', $paramJSON['date']);
                    if($paramJSON['date'] !== '2000-01-01 00:00:00') $date = DateTime::createFromFormat('Y-m-d H:i:s', $paramJSON['date']);
                    else $date = new DateTime('now', new DateTimeZone('Africa/Douala'));

                    for ($i = 0; $i < 6; $i++) {
                        $data[$i]->setDateTime($date);
                    }
                    /*$data0->setDateTime($date);
                    $data1->setDateTime($date);
                    $data2->setDateTime($date);
                    $data3->setDateTime($date);
                    $data4->setDateTime($date);
                    $data5->setDateTime($date);*/

                    if ($smartMod->getNbPhases() === 1) {
                        if (array_key_exists("Cosfi", $paramJSON)) {
                            if (count($paramJSON['Cosfi']) >= 3) {
                                $firstData->setCosfi($paramJSON['Cosfi'][0]);
                                $secondData->setCosfi($paramJSON['Cosfi'][1]);
                            }
                        }
                        if (array_key_exists("Cosfimin", $paramJSON)) {
                            if (count($paramJSON['Cosfimin']) >= 3) {
                                $firstData->setCosfimin($paramJSON['Cosfimin'][0]); // En kW
                                $secondData->setCosfimin($paramJSON['Cosfimin'][1]); // En kW
                                //$GensetData->setCosfimin($paramJSON['Cosfimin'][1]); // En kW
                                $loadSiteData->setCosfimin($paramJSON['Cosfimin'][2]); // En kW
                            }
                        }
                        if (array_key_exists("Va", $paramJSON)) {
                            if (count($paramJSON['Va']) >= 3) {
                                $firstData->setVamoy($paramJSON['Va'][0]);
                                $secondData->setVamoy($paramJSON['Va'][1]);
                                //// $GensetData->setVamoy($paramJSON['Va'][1]);
                                $loadSiteData->setVamoy($paramJSON['Va'][2]);
                            }
                        }

                        if (array_key_exists("P", $paramJSON)) {
                            if (count($paramJSON['P']) >= 3) {
                                $firstData->setPmoy($paramJSON['P'][0]); // En kWatts
                                $secondData->setPmoy($paramJSON['P'][1]); // En kWatts
                                //$GensetData->setP($paramJSON['P'][1]); // En kWatts
                                $loadSiteData->setPmoy($paramJSON['P'][2]); // En kWatts
//                                dd($smartMod->getSite()->getPowerSubscribed());
                                if($oldPmoy !== null && $smartMod->getSite()->getPowerSubscribed()){
                                    $Psous = $smartMod->getSite()->getPowerSubscribed();
                                    if($paramJSON['P'][2] > $Psous && $oldPmoy < $Psous){
//                                        dump($paramJSON['P'][2]);
//                                        dd($oldPmoy);
                                    }
                                }
                            }
                        }
                        if (array_key_exists("Pmax", $paramJSON)) {
                            if (count($paramJSON['Pmax']) >= 3) {
                                $firstData->setPmax($paramJSON['Pmax'][0]); // En kW
                                $secondData->setPmax($paramJSON['Pmax'][1]); // En kW
                                //$GensetData->setPmax($paramJSON['Pmax'][1]); // En kW
                                $loadSiteData->setPmax($paramJSON['Pmax'][2]); // En kW
                            }
                        }
                        if (array_key_exists("Q", $paramJSON)) {
                            if (count($paramJSON['Q']) >= 3) {
                                $firstData->setQmoy($paramJSON['Q'][0]); // En kVAR
                                $secondData->setQmoy($paramJSON['Q'][1]); // En kVAR
                                //// $GensetData->setQmoy($paramJSON['Q'][1]); // En kVAR
                                $loadSiteData->setQmoy($paramJSON['Q'][2]); // En kVAR

                            }
                        }
                        if (array_key_exists("S", $paramJSON)) {
                            if (count($paramJSON['S']) >= 3) {
                                $firstData->setSmoy($paramJSON['S'][0]); // En kVA
                                $secondData->setSmoy($paramJSON['S'][1]); // En kVA
                                //$GensetData->setSmoy($paramJSON['S'][1]); // En kVA
                                $loadSiteData->setSmoy($paramJSON['S'][2]); // En kVA
                            }
                        }
                        if (array_key_exists("Ea", $paramJSON)) {
                            if (count($paramJSON['Ea']) >= 3) {
                                $firstData->setEa($paramJSON['Ea'][0]); // En kWh
                                $secondData->setEa($paramJSON['Ea'][1]); // En kWh
                                //$GensetData->setTotalEnergy($paramJSON['Ea'][1]); // En kWh
                                $loadSiteData->setEa($paramJSON['Ea'][2]); // En kWh

                            }
                        }
                        if (array_key_exists("Er", $paramJSON)) {
                            if (count($paramJSON['Er']) >= 3) {
                                $firstData->setEr($paramJSON['Er'][0]); // En kVARh
                                $secondData->setEr($paramJSON['Er'][1]); // En kVARh
                                // $GensetData->setEr($paramJSON['Er'][1]); // En kVARh
                                $loadSiteData->setEr($paramJSON['Er'][2]); // En kVARh

                            }
                        }
                    }
                    else if ($smartMod->getNbPhases() === 3) {
                        if (array_key_exists("Va", $paramJSON)) {
                            if (count($paramJSON['Va']) >= 6) {
                                for ($i = 0; $i < 6; $i++) {
                                    $data[$i]->setVamoy($paramJSON['Va'][$i]);
                                }

                                /*$data0->setVamoy($paramJSON['Va'][0]);
                                $data1->setVamoy($paramJSON['Va'][1]);
                                $data2->setVamoy($paramJSON['Va'][2]);
                                $data3->setVamoy($paramJSON['Va'][3]);
                                $data4->setVamoy($paramJSON['Va'][4]);
                                $data5->setVamoy($paramJSON['Va'][5]);*/

                            }
                        }
                        if (array_key_exists("Vb", $paramJSON)) {
                            if (count($paramJSON['Vb']) >= 6) {
                                for ($i = 0; $i < 6; $i++) {
                                    $data[$i]->setVbmoy($paramJSON['Vb'][$i]);
                                }

                                /*$data0->setVbmoy($paramJSON['Vb'][0]);
                                $data1->setVbmoy($paramJSON['Vb'][1]);
                                $data2->setVbmoy($paramJSON['Vb'][2]);
                                $data3->setVbmoy($paramJSON['Vb'][3]);
                                $data4->setVbmoy($paramJSON['Vb'][4]);
                                $data5->setVbmoy($paramJSON['Vb'][5]);*/

                            }
                        }
                        if (array_key_exists("Vc", $paramJSON)) {
                            if (count($paramJSON['Vc']) >= 6) {
                                for ($i = 0; $i < 6; $i++) {
                                    $data[$i]->setVcmoy($paramJSON['Vc'][$i]);
                                }

                                /*$data0->setVcmoy($paramJSON['Vc'][0]);
                                $data1->setVcmoy($paramJSON['Vc'][1]);
                                $data2->setVcmoy($paramJSON['Vc'][2]);
                                $data3->setVcmoy($paramJSON['Vc'][3]);
                                $data4->setVcmoy($paramJSON['Vc'][4]);
                                $data5->setVcmoy($paramJSON['Vc'][5]);*/

                            }
                        }
                        if (array_key_exists("Pa", $paramJSON)) {
                            if (count($paramJSON['Pa']) >= 6) {
                                for ($i = 0; $i < 6; $i++) {
                                    $data[$i]->setPamoy($paramJSON['Pa'][$i]); // En kW
                                }

                                /*$data0->setPamoy($paramJSON['Pa'][0]); // En kW
                                $data1->setPamoy($paramJSON['Pa'][1]); // En kW
                                $data2->setPamoy($paramJSON['Pa'][2]); // En kW
                                $data3->setPamoy($paramJSON['Pa'][3]); // En kW
                                $data4->setPamoy($paramJSON['Pa'][4]); // En kW
                                $data5->setPamoy($paramJSON['Pa'][5]); // En kW*/

                            }
                        }
                        if (array_key_exists("Pb", $paramJSON)) {
                            if (count($paramJSON['Pb']) >= 6) {
                                for ($i = 0; $i < 6; $i++) {
                                    $data[$i]->setPbmoy($paramJSON['Pb'][$i]); // En kW
                                }

                                /*$data0->setPbmoy($paramJSON['Pb'][0]); // En kW
                                $data1->setPbmoy($paramJSON['Pb'][1]); // En kW
                                $data2->setPbmoy($paramJSON['Pb'][2]); // En kW
                                $data3->setPbmoy($paramJSON['Pb'][3]); // En kW
                                $data4->setPbmoy($paramJSON['Pb'][4]); // En kW
                                $data5->setPbmoy($paramJSON['Pb'][5]); // En kW*/

                            }
                        }
                        if (array_key_exists("Pc", $paramJSON)) {
                            if (count($paramJSON['Pc']) >= 6) {
                                for ($i = 0; $i < 6; $i++) {
                                    $data[$i]->setPcmoy($paramJSON['Pc'][$i]); // En kW
                                }

                            }
                        }
                        if (array_key_exists("P", $paramJSON)) {
                            if (count($paramJSON['P']) >= 6) {
                                for ($i = 0; $i < 6; $i++) {
                                    $data[$i]->setPmoy($paramJSON['P'][$i]); // En kW
                                }

                            }
                        }
                        if (array_key_exists("Pamax", $paramJSON)) {
                            if (count($paramJSON['Pamax']) >= 6) {
                                for ($i = 0; $i < 6; $i++) {
                                    $data[$i]->setPamax($paramJSON['Pamax'][$i]); // En kW
                                }

                            }
                        }
                        if (array_key_exists("Pbmax", $paramJSON)) {
                            if (count($paramJSON['Pbmax']) >= 6) {
                                for ($i = 0; $i < 6; $i++) {
                                    $data[$i]->setPbmax($paramJSON['Pbmax'][$i]); // En kW
                                }

                            }
                        }
                        if (array_key_exists("Pcmax", $paramJSON)) {
                            if (count($paramJSON['Pcmax']) >= 6) {
                                for ($i = 0; $i < 6; $i++) {
                                    $data[$i]->setPcmax($paramJSON['Pcmax'][$i]); // En kW
                                }
                            }
                        }
                        if (array_key_exists("Pmax", $paramJSON)) {
                            if (count($paramJSON['Pmax']) >= 6) {
                                for ($i = 0; $i < 6; $i++) {
                                    $data[$i]->setPmax($paramJSON['Pmax'][$i]); // En kW
                                }

                            }
                        }
                        if (array_key_exists("Sa", $paramJSON)) {
                            if (count($paramJSON['Sa']) >= 6) {
                                for ($i = 0; $i < 6; $i++) {
                                    $data[$i]->setSamoy($paramJSON['Sa'][$i]); // En kVA
                                }

                            }
                        }
                        if (array_key_exists("Sb", $paramJSON)) {
                            if (count($paramJSON['Sb']) >= 6) {
                                for ($i = 0; $i < 6; $i++) {
                                    $data[$i]->setSbmoy($paramJSON['Sb'][$i]); // En kVA
                                }
                            }
                        }
                        if (array_key_exists("Sc", $paramJSON)) {
                            if (count($paramJSON['Sc']) >= 6) {
                                for ($i = 0; $i < 6; $i++) {
                                    $data[$i]->setScmoy($paramJSON['Sc'][$i]); // En kVA
                                }
                            }
                        }
                        if (array_key_exists("S", $paramJSON)) {
                            if (count($paramJSON['S']) >= 6) {
                                for ($i = 0; $i < 6; $i++) {
                                    $data[$i]->setSmoy($paramJSON['S'][$i]); // En kVA
                                }
                            }
                        }
                        if (array_key_exists("Samax", $paramJSON)) {
                            if (count($paramJSON['Samax']) >= 6) {
                                for ($i = 0; $i < 6; $i++) {
                                    $data[$i]->setSamax($paramJSON['Samax'][$i]); // En kVA
                                }
                            }
                        }
                        if (array_key_exists("Sbmax", $paramJSON)) {
                            if (count($paramJSON['Sbmax']) >= 6) {
                                for ($i = 0; $i < 6; $i++) {
                                    $data[$i]->setSbmax($paramJSON['Sbmax'][$i]); // En kVA
                                }

                            }
                        }
                        if (array_key_exists("Scmax", $paramJSON)) {
                            if (count($paramJSON['Scmax']) >= 6) {
                                for ($i = 0; $i < 6; $i++) {
                                    $data[$i]->setScmax($paramJSON['Scmax'][$i]); // En kVA
                                }
                            }
                        }
                        if (array_key_exists("Smax", $paramJSON)) {
                            if (count($paramJSON['Smax']) >= 6) {
                                for ($i = 0; $i < 6; $i++) {
                                    $data[$i]->setSmax($paramJSON['Smax'][$i]); // En kVA
                                }

                            }
                        }
                        if (array_key_exists("Qa", $paramJSON)) {
                            if (count($paramJSON['Qa']) >= 6) {
                                for ($i = 0; $i < 6; $i++) {
                                    $data[$i]->setQamoy($paramJSON['Qa'][$i]); // En kVAR
                                }
                            }
                        }
                        if (array_key_exists("Qb", $paramJSON)) {
                            if (count($paramJSON['Qb']) >= 6) {
                                for ($i = 0; $i < 6; $i++) {
                                    $data[$i]->setQbmoy($paramJSON['Qb'][$i]); // En kVAR
                                }
                            }
                        }
                        if (array_key_exists("Qc", $paramJSON)) {
                            if (count($paramJSON['Qc']) >= 6) {
                                for ($i = 0; $i < 6; $i++) {
                                    $data[$i]->setQcmoy($paramJSON['Qc'][$i]); // En kVAR
                                }
                            }
                        }
                        if (array_key_exists("Q", $paramJSON)) {
                            if (count($paramJSON['Q']) >= 6) {
                                for ($i = 0; $i < 6; $i++) {
                                    $data[$i]->setQmoy($paramJSON['Q'][$i]); // En kVAR
                                }
                            }
                        }
                        if (array_key_exists("Qamax", $paramJSON)) {
                            if (count($paramJSON['Qamax']) >= 6) {
                                for ($i = 0; $i < 6; $i++) {
                                    $data[$i]->setQamax($paramJSON['Qamax'][$i]); // En kVAR
                                }
                            }
                        }
                        if (array_key_exists("Qbmax", $paramJSON)) {
                            if (count($paramJSON['Qbmax']) >= 6) {
                                for ($i = 0; $i < 6; $i++) {
                                    $data[$i]->setQbmax($paramJSON['Qbmax'][$i]); // En kVAR
                                }
                            }
                        }
                        if (array_key_exists("Qcmax", $paramJSON)) {
                            if (count($paramJSON['Qcmax']) >= 6) {
                                for ($i = 0; $i < 6; $i++) {
                                    $data[$i]->setQcmax($paramJSON['Qcmax'][$i]); // En kVAR
                                }
                            }
                        }
                        if (array_key_exists("Qmax", $paramJSON)) {
                            if (count($paramJSON['Qmax']) >= 6) {
                                for ($i = 0; $i < 6; $i++) {
                                    $data[$i]->setQmax($paramJSON['Qmax'][$i]); // En kVAR
                                }
                            }
                        }
                        if (array_key_exists("Cosfia", $paramJSON)) {
                            if (count($paramJSON['Cosfia']) >= 6) {
                                for ($i = 0; $i < 6; $i++) {
                                    $data[$i]->setCosfia($paramJSON['Cosfia'][$i]);
                                }
                            }
                        }
                        if (array_key_exists("Cosfib", $paramJSON)) {
                            if (count($paramJSON['Cosfib']) >= 6) {
                                for ($i = 0; $i < 6; $i++) {
                                    $data[$i]->setCosfib($paramJSON['Cosfib'][$i]);
                                }
                            }
                        }
                        if (array_key_exists("Cosfic", $paramJSON)) {
                            if (count($paramJSON['Cosfic']) >= 6) {
                                for ($i = 0; $i < 6; $i++) {
                                    $data[$i]->setCosfic($paramJSON['Cosfic'][$i]);
                                }
                            }
                        }
                        if (array_key_exists("Cosfi", $paramJSON)) {
                            if (count($paramJSON['Cosfi']) >= 6) {
                                for ($i = 0; $i < 6; $i++) {
                                    $data[$i]->setCosfi($paramJSON['Cosfi'][$i]);
                                }
                            }
                        }
                        if (array_key_exists("Cosfiamin", $paramJSON)) {
                            if (count($paramJSON['Cosfiamin']) >= 6) {
                                for ($i = 0; $i < 6; $i++) {
                                    $data[$i]->setCosfiamin($paramJSON['Cosfiamin'][$i]);
                                }
                            }
                        }
                        if (array_key_exists("Cosfibmin", $paramJSON)) {
                            if (count($paramJSON['Cosfibmin']) >= 6) {
                                for ($i = 0; $i < 6; $i++) {
                                    $data[$i]->setCosfibmin($paramJSON['Cosfibmin'][$i]);
                                }
                            }
                        }
                        if (array_key_exists("Cosficmin", $paramJSON)) {
                            if (count($paramJSON['Cosficmin']) >= 6) {
                                for ($i = 0; $i < 6; $i++) {
                                    $data[$i]->setCosficmin($paramJSON['Cosficmin'][$i]);
                                }
                            }
                        }
                        if (array_key_exists("Cosfimin", $paramJSON)) {
                            if (count($paramJSON['Cosfimin']) >= 6) {
                                for ($i = 0; $i < 6; $i++) {
                                    $data[$i]->setCosfimin($paramJSON['Cosfimin'][$i]);
                                }
                            }
                        }
                        if (array_key_exists("Eaa", $paramJSON)) {
                            if (count($paramJSON['Eaa']) >= 6) {
                                for ($i = 0; $i < 6; $i++) {
                                    $data[$i]->setEaa($paramJSON['Eaa'][$i]); // En kWh
                                }
                            }
                        }
                        if (array_key_exists("Eab", $paramJSON)) {
                            if (count($paramJSON['Eab']) >= 6) {
                                for ($i = 0; $i < 6; $i++) {
                                    $data[$i]->setEab($paramJSON['Eab'][$i]); // En kWh
                                }
                            }
                        }
                        if (array_key_exists("Eac", $paramJSON)) {
                            if (count($paramJSON['Eac']) >= 6) {
                                for ($i = 0; $i < 6; $i++) {
                                    $data[$i]->setEac($paramJSON['Eac'][$i]); // En kWh
                                }
                            }
                        }
                        if (array_key_exists("Ea", $paramJSON)) {
                            if (count($paramJSON['Ea']) >= 6) {
                                for ($i = 0; $i < 6; $i++) {
                                    $data[$i]->setEa($paramJSON['Ea'][$i]); // En kWh
                                }
                            }
                        }
                        if (array_key_exists("Era", $paramJSON)) {
                            if (count($paramJSON['Era']) >= 6) {
                                for ($i = 0; $i < 6; $i++) {
                                    $data[$i]->setEra($paramJSON['Era'][$i]); // En kVARh
                                }
                            }
                        }
                        if (array_key_exists("Erb", $paramJSON)) {
                            if (count($paramJSON['Erb']) >= 6) {
                                for ($i = 0; $i < 6; $i++) {
                                    $data[$i]->setErb($paramJSON['Erb'][$i]); // En kVARh
                                }
                            }
                        }
                        if (array_key_exists("Erc", $paramJSON)) {
                            if (count($paramJSON['Erc']) >= 6) {
                                for ($i = 0; $i < 6; $i++) {
                                    $data[$i]->setErc($paramJSON['Erc'][$i]); // En kVARh
                                }
                            }
                        }
                        if (array_key_exists("Er", $paramJSON)) {
                            if (count($paramJSON['Er']) >= 6) {
                                for ($i = 0; $i < 6; $i++) {
                                    $data[$i]->setEr($paramJSON['Er'][$i]); // En kVARh
                                }
                            }
                        }
                    }

                    for ($i = 0; $i < 6; $i++) {
                        if ($mod[$i]) {
                            $data[$i]->setSmartMod($mod[$i]);
                            $manager->persist($data[$i]);
                        }
                    }

                    if ($this->getParameter('app.env') === "dev") dd($paramJSON);
                    $manager->flush();
                }

                return $this->json([
                    'code' => 200,
                    'server Time' => $date->format('Y-m-d H:i:s'),
                    'received' => $paramJSON

                ], 200);
            }

            // //dump($datetimeData);
            //die();
            //Insertion de la nouvelle datetimeData dans la BDD

        }
        return $this->json([
            'code'     => 403,
            'message'  => "SmartMod don't exist",
            'received' => $paramJSON,
            'modId'    => $modId

        ], 403);
    }

    public function sendToAlarmController($mess, $modId, EntityManagerInterface $manager, HttpClientInterface $client, MessageBusInterface $messageBus)
    {
        /*return $this->json([
            'mess'    => $mess,
            'modId' => $modId,
        ], 200);*/
        $paramJSON = $this->getJSONRequest($mess);
        // dump($modId);
        $smartMod = $manager->getRepository('App:SmartMod')->findOneBy(['moduleId' => $modId]);
        if ($smartMod) {
            // dump($smartMod);
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

                if ($alarmCode->getType() !== 'FUEL') $message = $alarmCode->getLabel() . ' du site ' . $site->getName() . " ({$site->getEnterprise()->getSocialReason()})" . ' survenu(e) le ' . $date->format('d/m/Y à H:i:s');
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

                foreach ($site->getContacts() as $contact) {
                    $messageBus->dispatch(new UserNotificationMessage($contact->getId(), $message, $alarmCode->getMedia(), $alarmCode->getAlerte()));
                    //$messageBus->dispatch(new UserNotificationMessage($contact->getId(), $message, 'SMS', ''));
                }

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
