<?php

namespace App\Controller;

use App\Entity\Enterprise;
use App\Form\EnterpriseType;
use App\Repository\EnterpriseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Service\EnterpriseDashboardDataService;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @Route("/enterprise")
 */
class EnterpriseController extends AbstractController
{
    /**
     * @Route("/", name="enterprise_index", methods={"GET"})
     */
    public function index(EnterpriseRepository $enterpriseRepository): Response
    {
        return $this->render('enterprise/index.html.twig', [
            'enterprises' => $enterpriseRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="enterprise_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $enterprise = new Enterprise();
        $form = $this->createForm(EnterpriseType::class, $enterprise);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($enterprise);
            $entityManager->flush();

            return $this->redirectToRoute('enterprise_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('enterprise/new.html.twig', [
            'enterprise' => $enterprise,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{slug<[a-zA-Z0-9-_]+>}/home", name="enterprise_show", methods={"GET"})
     */
    public function show(Enterprise $enterprise, EnterpriseDashboardDataService $dash): Response
    {
        $dash->setEnterprise($enterprise);

        $sitesParams  = $dash->getCurrentMonthSiteParams();
        $currentConso = $dash->getCurrentMonthkWhConsumption();

        return $this->render('enterprise/home.html.twig', [
            'enterprise' => $enterprise,
            //'lastDatetimeData'        => $dash->getLastDatetimeData(),
            'currentMonthkWh'         => $currentConso['currentConsokWh'],
            'currentMonthkWhProgress' => $currentConso['currentConsokWhProgress'],
            //'currentMonthXAF'         => $currentConso['currentConsoXAF'],
            'currentMonthGasEmission' => $currentConso['currentGasEmission'],
            //'budget'                  => $this->getCurrentBudget($site, $manager),
            'dayBydayConsoData'       => $dash->getDayByDayConsumptionForCurrentMonth(),
            //'loadChartData'           => $dash->getLoadChartDataForCurrentMonth(),
            'sitesParams'             => $sitesParams,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="enterprise_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Enterprise $enterprise): Response
    {
        $form = $this->createForm(EnterpriseType::class, $enterprise);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('enterprise_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('enterprise/edit.html.twig', [
            'enterprise' => $enterprise,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}", name="enterprise_delete", methods={"POST"})
     */
    public function delete(Request $request, Enterprise $enterprise): Response
    {
        if ($this->isCsrfTokenValid('delete' . $enterprise->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($enterprise);
            $entityManager->flush();
        }

        return $this->redirectToRoute('enterprise_index', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * Permet la MAJ des données de l'interface dashboard d'un site
     * 
     * @Route("/{slug<[a-zA-Z0-9-_]+>}/overview/update", name="enterprise_overview_update_data")
     *
     * @param Enterprise $enterprise
     * @param SiteDashboardDataService $overview
     * @param EntityManagerInterface $manager
     * @param Request $request
     * @return JsonResponse
     */
    public function updateOverview(Enterprise $enterprise, EnterpriseDashboardDataService $overview): JsonResponse
    {
        //Récupération et vérification des paramètres au format JSON contenu dans la requête
        //$paramJSON = $this->getJSONRequest($request->getContent());
        //if (array_key_exists("startDate", $paramJSON) && array_key_exists("endDate", $paramJSON)) {
        //Initialisation du service
        $overview->setEnterprise($enterprise);

        $sitesParams  = $overview->getCurrentMonthSiteParams();
        $currentConso = $overview->getCurrentMonthkWhConsumption();

        $currentConso = $overview->getCurrentMonthkWhConsumption();
        return $this->json([
            'code'                    => 200,
            //'lastDatetimeData'        => $overview->getLastDatetimeData(),
            'currentMonthkWh'         => $currentConso['currentConsokWh'],
            'currentMonthkWhProgress' => $currentConso['currentConsokWhProgress'],
            //'currentMonthXAF'         => $currentConso['currentConsoXAF'],
            'currentMonthGasEmission' => $currentConso['currentGasEmission'],
            //'budget'                  => $this->getCurrentBudget($site, $manager),
            'dayBydayConsoData'       => $overview->getDayByDayConsumptionForCurrentMonth(),
            //'loadChartData'           => $overview->getLoadChartDataForCurrentMonth(),
            'sitesParams'             => $sitesParams,
        ], 200);
        //}

        /*return $this->json([
            'code' => 403,
            'message' => 'Empty Array or Not exists !',
        ], 403);*/
    }
}
