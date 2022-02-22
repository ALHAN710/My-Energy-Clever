<?php

namespace App\Controller;

use DateTime;
use App\Entity\GensetData;
use App\Entity\LoadEnergyData;
use Doctrine\ORM\EntityManagerInterface;
use App\Controller\ApplicationController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
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
     * 
     * 
     * @param SmartMod $smartMod
     * @param EntityManagerInterface $manager
     * @param Request $request
     * @return void
     */
    public function loadDataEnergy_add($modId, EntityManagerInterface $manager, Request $request)
    { //@Route("/load-energy-data/mod/{modId<[a-zA-Z0-9_-]+>}/add", name="loadEnergyData_add", schemes={"http"}) 
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
                        if (array_key_exists("Va", $paramJSON)) {
                            $datetimeData->setVamoy($paramJSON['Va']);
                        }

                        if (array_key_exists("P", $paramJSON)) {
                            $datetimeData->setPmoy($paramJSON['P']); // En Watts
                        }
                        if (array_key_exists("Q", $paramJSON)) {
                            $datetimeData->setQmoy($paramJSON['Q']); // En VAR
                        }
                        if (array_key_exists("S", $paramJSON)) {
                            $datetimeData->setSmoy($paramJSON['S']); // En VA
                        }
                        if (array_key_exists("Ea", $paramJSON)) {
                            $datetimeData->setEa($paramJSON['Ea'] / 1000.0); // En kWh
                        }
                        if (array_key_exists("Er", $paramJSON)) {
                            $datetimeData->setEr($paramJSON['Er'] / 1000.0); // En kVARh
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
                        if (array_key_exists("Pb", $paramJSON)) {
                            $datetimeData->setPbmoy($paramJSON['Pb']); // En kW
                        }
                        if (array_key_exists("Pc", $paramJSON)) {
                            $datetimeData->setPcmoy($paramJSON['Pc']); // En kW
                        }
                        if (array_key_exists("P", $paramJSON)) {
                            $datetimeData->setPmoy($paramJSON['P']); // En kW
                        }
                        if (array_key_exists("Sa", $paramJSON)) {
                            $datetimeData->setSamoy($paramJSON['Sa']); // En kVA
                        }
                        if (array_key_exists("Sb", $paramJSON)) {
                            $datetimeData->setSbmoy($paramJSON['Sb']); // En kVA
                        }
                        if (array_key_exists("Sc", $paramJSON)) {
                            $datetimeData->setScmoy($paramJSON['Sc']); // En kVA
                        }
                        if (array_key_exists("S", $paramJSON)) {
                            $datetimeData->setSmoy($paramJSON['S']); // En kVA
                        }
                        if (array_key_exists("Qa", $paramJSON)) {
                            $datetimeData->setQamoy($paramJSON['Qa']); // En kVAR
                        }
                        if (array_key_exists("Qb", $paramJSON)) {
                            $datetimeData->setQbmoy($paramJSON['Qb']); // En kVAR
                        }
                        if (array_key_exists("Qc", $paramJSON)) {
                            $datetimeData->setQcmoy($paramJSON['Qc']); // En kVAR
                        }
                        if (array_key_exists("Q", $paramJSON)) {
                            $datetimeData->setQmoy($paramJSON['Q']); // En kVAR
                        }
                        if (array_key_exists("Cosfia", $paramJSON)) {
                            $datetimeData->setCosfia($paramJSON['Cosfia']);
                        }
                        if (array_key_exists("Cosfib", $paramJSON)) {
                            $datetimeData->setCosfib($paramJSON['Cosfib']);
                        }
                        if (array_key_exists("Cosfic", $paramJSON)) {
                            $datetimeData->setCosfic($paramJSON['Cosfic']);
                        }
                        if (array_key_exists("Cosfi", $paramJSON)) {
                            $datetimeData->setCosfi($paramJSON['Cosfi']);
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
     * 
     * 
     * @param SmartMod $smartMod
     * @param EntityManagerInterface $manager
     * @param Request $request
     * @return void
     */
    public function InverterDataEnergy_add($modId, EntityManagerInterface $manager, Request $request)
    { //@Route("/inverter-energy-data/mod/{modId<[a-zA-Z0-9_-]+>}/add", name="inverterEnergyData_add", schemes={"http"}) 
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
}
