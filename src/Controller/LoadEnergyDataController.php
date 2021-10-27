<?php

namespace App\Controller;

use DateTime;
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
     * @Route("/load-energy-data/mod/{modId<[a-zA-Z0-9]+>}/add", name="loadEnergyData_add") 
     * 
     * @param SmartMod $smartMod
     * @param EntityManagerInterface $manager
     * @param Request $request
     * @return void
     */
    public function loadDataEnergy_add($modId, EntityManagerInterface $manager, Request $request)
    {
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
