<?php

namespace App\Controller;

use App\Entity\SmartDevice;
use App\Form\SmartDeviceType;
use Doctrine\ORM\EntityManagerInterface;
use App\Controller\ApplicationController;
use App\Repository\SmartDeviceRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
//use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @Route("/smart/device")
 */
class SmartDeviceController extends ApplicationController
{
    /**
     * @Route("/", name="smart_device_index", methods={"GET"})
     */
    public function index(SmartDeviceRepository $smartDeviceRepository): Response
    {
        return $this->render('smart_device/index.html.twig', [
            'smart_devices' => $smartDeviceRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="smart_device_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $smartDevice = new SmartDevice();
        $form = $this->createForm(SmartDeviceType::class, $smartDevice);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($smartDevice);
            $entityManager->flush();

            return $this->redirectToRoute('smart_device_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('smart_device/new.html.twig', [
            'smart_device' => $smartDevice,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}", name="smart_device_show", methods={"GET"})
     */
    public function show(SmartDevice $smartDevice): Response
    {
        return $this->render('smart_device/show.html.twig', [
            'smart_device' => $smartDevice,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="smart_device_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, SmartDevice $smartDevice): Response
    {
        $form = $this->createForm(SmartDeviceType::class, $smartDevice);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('smart_device_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('smart_device/edit.html.twig', [
            'smart_device' => $smartDevice,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}", name="smart_device_delete", methods={"POST"})
     */
    public function delete(Request $request, SmartDevice $smartDevice): Response
    {
        if ($this->isCsrfTokenValid('delete' . $smartDevice->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($smartDevice);
            $entityManager->flush();
        }

        return $this->redirectToRoute('smart_device_index', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * Permet le CRUD du programme d'un SmartDevice
     *
     * @Route("/programming/{slug<[a-zA-Z0-9-_]+>?''}", name="smart_device_prog")
     * 
     * @param SmartDevice $device
     * @param Request $request
     * @param EntityManagerInterface $manager
     * @return JsonResponse
     */
    public function deviceSetProg(SmartDevice $device, Request $request, EntityManagerInterface $manager): JsonResponse
    {
        //Récupération et vérification des paramètres au format JSON contenu dans la requête
        $paramJSON = $this->getJSONRequest($request->getContent());
        //dump($paramJSON);
        //$devicesRepo = $manager->getRepository('App:SmartDevice');
        $arr = json_decode($paramJSON['prog'], true);
        // $action   = $paramJSON['action'];
        // if ($action == 'save') {
        //$arr = $this->getJSONRequest($paramJSON['prog']);
        //dump($arr);
        //Recherche du device dans la BDD

        $device->setProgramming($arr);
        $manager->persist($device);
        $manager->flush();

        return $this->json([
            'code'     => 200,
            'received' => $paramJSON,
            'success'  => 1,
        ], 200);

        // }
    }

    /**
     * Permet d'envoyer le programme d'un Device
     *
     * @Route("/{moduleId<[a-zA-Z0-9-_]+>?''}/get/prog", name="smart_device_get_prog")
     * 
     * @param SmartDevice $device
     * @param Request $request
     * @param EntityManagerInterface $manager
     * @return void
     */
    public function deviceGetProg(SmartDevice $device, Request $request, EntityManagerInterface $manager)
    {
        //Récupération et vérification des paramètres au format JSON contenu dans la requête
        $paramJSON = $this->getJSONRequest($request->getContent());
        //dump($paramJSON);
        $action   = $paramJSON['action'];
        if ($action == 'get prog') {

            //$arr = $this->getJSONRequest($paramJSON['prog']);

            //dump($device);

            $jsonProg = json_encode($device->getProgramming());
            //dump($jsonProg);
            return $this->json([
                'code'     => 200,
                'prog'     => $jsonProg,
                'success'  => ($jsonProg == null ? 0 : 1),
            ], 200);
        }
    }
}
