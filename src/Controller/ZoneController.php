<?php

namespace App\Controller;

use App\Entity\Zone;
use App\Service\SmartHomeService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ZoneController extends AbstractController
{
    /**
     * @Route("/zone", name="zone")
     */
    public function index(): Response
    {
        return $this->render('zone/index.html.twig', [
            'controller_name' => 'ZoneController',
        ]);
    }

    /**
     * Permet de gérer la commande des équipements
     *
     * @Route("/zone/{slug<[a-zA-Z0-9-_]+>}/equipment", name="zone_equipement", schemes={"http"})
     * @IsGranted("ROLE_USER")
     */
    public function equipmentControl(Zone $zone, SmartHomeService $smartHome)
    {
        // dump($zone->getCleverBox()[0]);
        if (count($zone->getCleverBox()) > 0) {
            $smartHome->setCleverBox($zone->getCleverBox()[0]);

            return $this->render('zone/equipment_control.html.twig', [
                'zone' => $zone,
                'smartHome' => $smartHome,
            ]);
        } else {
            //Erreur zone ne contient pas de CleverBox
            throw new NotFoundHttpException("Erreur zone ne contient pas de CleverBox");
        }
    }
}
