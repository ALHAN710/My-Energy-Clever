<?php

namespace App\Controller;

use DateTime;
use DateInterval;
use App\Entity\Site;
use App\Entity\Budget;
use App\Form\SiteType;
use App\Repository\SiteRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Controller\ApplicationController;
use App\Service\SiteDashboardDataService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
//use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @Route("/installation")
 */
class SiteController extends ApplicationController
{
    /**
     * @Route("/", name="site_index", methods={"GET"})
     */
    public function index(SiteRepository $siteRepository): Response
    {
        return $this->render('site/index.html.twig', [
            'sites' => $siteRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="site_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $site = new Site();
        $form = $this->createForm(SiteType::class, $site);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($site);
            $entityManager->flush();

            return $this->redirectToRoute('site_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('site/new.html.twig', [
            'site' => $site,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{slug<[a-zA-Z0-9-_]+>}", name="site_show", methods={"GET"})
     */
    public function show(Site $site, EntityManagerInterface $manager, SiteDashboardDataService $dash): Response
    {
        $dash->setSite($site);

        // $dash->getCurrentMonthConsumption();
        // $dash->getLastMonthConsumption();
        // $dash->getDayByDayConsumptionForCurrentMonth();
        // $dash->getLoadChartDataForCurrentMonth();
        // $dash->getCurrentActivePower();
        // $dash->getAverageConsumptionForCurrentMonth();
        // $dash->getAverageConsumptionMonthByMonthForCurrentYear();
        // dump($dash->getDayByDayConsumptionForCurrentMonth());
        $currentConso = $dash->getCurrentMonthkWhConsumption();
        return $this->render('site/home.html.twig', [
            'site'                    => $site,
            'lastDatetimeData'        => $dash->getLastDatetimeData(),
            'currentMonthkWh'         => $currentConso['currentConsokWh'],
            'currentMonthkWhProgress' => $currentConso['currentConsokWhProgress'],
            'currentMonthXAF'         => $currentConso['currentConsoXAF'],
            'currentMonthGasEmission' => $currentConso['currentGasEmission'],
            'budget'                  => $this->getCurrentBudget($site, $manager),
            'dayBydayConsoData'       => $dash->getDayByDayConsumptionForCurrentMonth(),
            'loadChartData'           => $dash->getLoadChartDataForCurrentMonth(),
            'currentMonthDataTable'   => $dash->getCurrentMonthDataTable(),
            'MonthByMonthDataTable'   => $dash->getMonthByMonthDataTableForCurrentYear(),
        ]);
    }

    /**
     * @Route("/{slug<[a-zA-Z0-9-_]+>}/edit", name="site_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Site $site): Response
    {
        $form = $this->createForm(SiteType::class, $site);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('site_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('site/edit.html.twig', [
            'site' => $site,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{slug<[a-zA-Z0-9-_]+>}", name="site_delete", methods={"POST"})
     */
    public function delete(Request $request, Site $site): Response
    {
        if ($this->isCsrfTokenValid('delete' . $site->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($site);
            $entityManager->flush();
        }

        return $this->redirectToRoute('site_index', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * @Route("/{slug<[a-zA-Z0-9-_]+>}/define/budget", name="site_budget", methods={"POST"})
     * @param Request $request
     * @param Site $site
     * @param EntityManagerInterface $manager
     * @return JsonResponse
     */
    public function setBudgetSite(Site $site, Request $request, EntityManagerInterface $manager): JsonResponse
    {
        $paramJSON = $this->getJSONRequest($request->getContent());

        if (array_key_exists("amount", $paramJSON)) {
            $currentBudget = $manager->createQuery("SELECT b 
                                                    FROM App\Entity\Budget b
                                                    JOIN b.site s
                                                    WHERE b.date LIKE :nowDate
                                                    AND s.id = :siteId
                                                  ")
                ->setParameters(array(
                    'nowDate' => date('Y-m') . '%',
                    'siteId'  => $site->getId(),
                ))
                ->getResult();
            //dump($currentBudget);
            $budget = count($currentBudget) > 0 ? $currentBudget[0] : null;
            if ($budget) $budget->setAmount(floatval($paramJSON['amount']));
            else {
                $budget = new Budget();
                $budget->setSite($site)
                    ->setAmount($paramJSON['amount'])
                    ->setDate(new DateTime('now'));
            }

            $manager->persist($budget);
            $manager->flush();
            return $this->json([
                'code' => 200,
                'received' => $paramJSON,
            ], 200);
        }

        return $this->json([
            'code' => 403,
            'message' => 'Empty Array or Not exists !',
        ], 403);
    }

    /**
     * Permet d'obtenir le budget défini pour le mois en cours du site
     *
     * @return float
     */
    private function getCurrentBudget(Site $site, EntityManagerInterface $manager): float
    {
        $budget = 0;

        $currentBudget = $manager->createQuery("SELECT b.amount AS XAF 
                                                FROM App\Entity\Budget b
                                                JOIN b.site s
                                                WHERE b.date LIKE :nowDate
                                                AND s.id = :siteId
                                                ")
            ->setParameters(array(
                'nowDate' => date('Y-m') . '%',
                'siteId'  => $site->getId(),
            ))
            ->getResult();
        //dump($currentBudget);
        $budget = count($currentBudget) > 0 ? $currentBudget[0]['XAF'] : null;

        if ($budget === null) {
            $date = new DateTime('now');
            $date->sub(new DateInterval('P1M'));

            $currentBudget = $manager->createQuery("SELECT b.amount AS XAF 
                                                FROM App\Entity\Budget b
                                                JOIN b.site s
                                                WHERE b.date LIKE :nowDate
                                                AND s.id = :siteId
                                                ")
                ->setParameters(array(
                    'nowDate' => $date->format('Y-m') . '%',
                    'siteId'  => $site->getId(),
                ))
                ->getResult();
            dump($currentBudget);
            $budget = count($currentBudget) > 0 ? $currentBudget[0]['XAF'] : 0;
            $new_budget = new Budget();
            $new_budget->setSite($site)
                ->setAmount($budget)
                ->setDate(new DateTime('now'));

            $manager->persist($new_budget);
            $manager->flush();
        }
        //$budget = number_format((float) $budget, 0, '.', ' ');
        return $budget;
    }

    /**
     * Permet la MAJ des données de l'interface dashboard d'un site
     * 
     * @Route("/{slug<[a-zA-Z0-9-_]+>}/overview/update", name="site_overview_update_data")
     *
     * @param Site $site
     * @param SiteDashboardDataService $overview
     * @param EntityManagerInterface $manager
     * @param Request $request
     * @return JsonResponse
     */
    public function updateOverview(Site $site, SiteDashboardDataService $overview, EntityManagerInterface $manager, Request $request): JsonResponse
    {
        //Récupération et vérification des paramètres au format JSON contenu dans la requête
        //$paramJSON = $this->getJSONRequest($request->getContent());
        //if (array_key_exists("startDate", $paramJSON) && array_key_exists("endDate", $paramJSON)) {
        //Initialisation du service
        $overview->setSite($site);

        $currentConso = $overview->getCurrentMonthkWhConsumption();
        return $this->json([
            'code'                    => 200,
            'lastDatetimeData'        => $overview->getLastDatetimeData(),
            'currentMonthkWh'         => $currentConso['currentConsokWh'],
            'currentMonthkWhProgress' => $currentConso['currentConsokWhProgress'],
            'currentMonthXAF'         => $currentConso['currentConsoXAF'],
            'currentMonthGasEmission' => $currentConso['currentGasEmission'],
            'budget'                  => $this->getCurrentBudget($site, $manager),
            'dayBydayConsoData'       => $overview->getDayByDayConsumptionForCurrentMonth(),
            'loadChartData'           => $overview->getLoadChartDataForCurrentMonth(),
            'currentMonthDataTable'   => $overview->getCurrentMonthDataTable(),
        ], 200);
        //}

        return $this->json([
            'code' => 403,
            'message' => 'Empty Array or Not exists !',
        ], 403);
    }

    /**
     * Permet la MAJ des historiques de courbes
     * 
     * @Route("/{slug<[a-zA-Z0-9-_]+>}/histo-graphs/update", name="site_histo_update")
     *
     * @param Site $site
     * @param SiteDashboardDataService $histo
     * @param EntityManagerInterface $manager
     * @param Request $request
     * @return JsonResponse
     */
    public function updateSiteHistoGraphs(Site $site, SiteDashboardDataService $histo, EntityManagerInterface $manager, Request $request): JsonResponse
    {
        //Récupération et vérification des paramètres au format JSON contenu dans la requête
        $paramJSON = $this->getJSONRequest($request->getContent());
        if (array_key_exists("startDate", $paramJSON) && array_key_exists("endDate", $paramJSON)) {
            //Initialisation du service
            $histo->setSite($site)
                ->setStartDate(new DateTime($paramJSON['startDate']))
                ->setEndDate(new DateTime($paramJSON['endDate']));

            return $this->json([
                'code'         => 200,
                'Mixed_Conso'  => [
                    'date'  => $histo->updateHistoGraphs()['consoChart_Data']['dateConso'],
                    'conso' => [$histo->updateHistoGraphs()['consoChart_Data']['kWh'], $histo->updateHistoGraphs()['consoChart_Data']['kgCO2']]
                ],
                'Load_Chart'   => $histo->updateHistoGraphs()['loadChart_Data'],
            ], 200);
        }

        return $this->json([
            'code' => 403,
            'message' => 'Empty Array or Not exists !',
        ], 403);
    }
}
