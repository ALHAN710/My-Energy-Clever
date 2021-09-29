<?php

namespace App\Controller;

use App\Entity\Zone;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

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
     * @Route("/zone/{slug<[a-zA-Z0-9-_]+>}/equipment", name="zone_equipement")
     * 
     */
    public function equipmentControl(Zone $zone)
    {
        return $this->render('zone/equipment_control.html.twig', [
            'zone' => $zone,
        ]);
    }
}
