<?php

namespace App\Controller;

use DateTime;
use DateInterval;
use App\Entity\Site;
use App\Entity\Budget;
use App\Form\SiteType;
use App\Entity\SmartMod;
use App\Repository\SiteRepository;
use App\Service\SiteProDataService;
use Doctrine\ORM\EntityManagerInterface;
use App\Controller\ApplicationController;
use App\Service\SiteDashboardDataService;
use App\Service\SiteProDataAnalyticService;
use App\Service\SiteProDashboardDataService;
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
     * @Route("/{slug<[a-zA-Z0-9-_]+>}/pro", name="site_pro_show", methods={"GET"})
     */
    public function show_site_pro(Site $site, SiteProDataService $siteDash): Response
    {
        $startDate = new DateTime(date("Y-m-01", strtotime(date('Y-m-d'))) . '00:00:00');
        $endDate   = new DateTime(date("Y-m-t", strtotime(date('Y-m-d'))) . '23:59:59');

        $siteDash->setSite($site)
            ->setPower_unit(1)
            ->setStartDate($startDate)
            ->setEndDate($endDate);
        $overViewData = $siteDash->getOverviewData();
        dump($overViewData);
        $smartMods = $site->getSmartMods();
        $gensetMod  = null;
        foreach ($smartMods as $smartMod) {
            if ($smartMod->getModType() === 'GENSET') $gensetMod = $smartMod;
        }
        // dump($gensetMod);
        return $this->render('site/home_pro.html.twig', [
            'site'            => $site,
            'gensetId'        => $gensetMod->getId() ?? 0,
            'loadSiteData'    => $overViewData['loadSiteData'],
            'gridData'        => $overViewData['gridData'],
            'gensetData'      => $overViewData['gensetData'],
            'hasgensetMod'    => $overViewData['hasgensetMod'],
            'contriGensetKWh' => $overViewData['contriGensetKWh'],
            'kgCO2'           => $overViewData['kgCO2'],
        ]);
    }

    /**
     * @Route("/{slug<[a-zA-Z0-9-_]+>}/historical-analytic", name="historical_analytic_site_pro_show", methods={"GET"})
     */
    public function show_historical_analytic_site_pro(Site $site, SiteProDataAnalyticService $siteAnalytic): Response
    {
        $startDate = new DateTime(date("Y-m-01", strtotime(date('Y-m-d'))) . '00:00:00');
        $endDate   = new DateTime(date("Y-m-t", strtotime(date('Y-m-d'))) . '23:59:59');
        // dump($startDate);
        // dump($endDate);

        $siteAnalytic->setSite($site)
            ->setPower_unit(1)
            ->setStartDate($startDate)
            ->setEndDate($endDate);
        $dataAnalysis = $siteAnalytic->getDataAnalysis();
        dump($dataAnalysis);
        return $this->render('site/historical_analytic.html.twig', [
            'site'            => $site,
            'dataAnalysis'    => $dataAnalysis,
            // 'loadSiteData'    => $dataAnalysis['loadSiteData'],
            // 'gridData'        => $dataAnalysis['gridData'],
            // 'gensetData'      => $dataAnalysis['gensetData'],
            // 'hasgensetMod'    => $dataAnalysis['hasgensetMod'],
            // 'contriGensetKWh' => $dataAnalysis['contriGensetKWh'],
            // 'kgCO2'           => $dataAnalysis['kgCO2'],
        ]);
    }

    /**
     * @Route("/{slug<[a-zA-Z0-9-_]+>}/data/monitoring", name="site_data_monitoring_show", methods={"GET"})
     */
    public function show_site_data_monitoring(Site $site, SiteDashboardDataService $siteDash): Response
    {
        // $siteDash->setSite($site)
        //     ->setPower_unit(1000);

        // $siteDash->getCurrentMonthConsumption();
        // $siteDash->getLastMonthConsumption();
        // $siteDash->getDayByDayConsumptionForCurrentMonth();
        // $siteDash->getLoadChartDataForCurrentMonth();
        // $siteDash->getCurrentActivePower();
        // $siteDash->getAverageConsumptionForCurrentMonth();
        // $siteDash->getAverageConsumptionMonthByMonthForCurrentYear();
        // dump($siteDash->getDayByDayConsumptionForCurrentMonth());
        // $currentConso = $siteDash->getCurrentMonthkWhConsumption();
        return $this->render('site/home_data_monitoring.html.twig', [
            'site'                    => $site,
            // 'lastDatetimeData'        => $siteDash->getLastDatetimeData(),
            // 'currentMonthkWh'         => $currentConso['currentConsokWh'],
            // 'currentMonthkWhProgress' => $currentConso['currentConsokWhProgress'],
            // 'currentMonthXAF'         => $currentConso['currentConsoXAF'],
            // 'currentMonthGasEmission' => $currentConso['currentGasEmission'],
            //-'budget'                  => $this->getCurrentBudget($site, $manager),
            // 'dayBydayConsoData'       => $siteDash->getDayByDayConsumptionForCurrentMonth(),
            // 'loadChartData'           => $siteDash->getLoadChartDataForCurrentMonth(),
            // 'currentMonthDataTable'   => $siteDash->getCurrentMonthDataTable(),
            //-'MonthByMonthDataTable'   => $siteDash->getMonthByMonthDataTableForCurrentYear(),
            // 'gridBillData'            => $siteDash->getGridBillData(),
            // 'fuelBillData'            => $siteDash->getFuelBillData(),
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
    public function updateSiteOverview(Site $site, SiteDashboardDataService $overview, EntityManagerInterface $manager, Request $request): JsonResponse
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
     * Permet la MAJ des données de l'interface dashboard d'un site pro
     * 
     * @Route("/{slug<[a-zA-Z0-9-_]+>}/pro/overview/update", name="site_pro_overview_update_data")
     *
     * @param Site $site
     * @param SiteProDashboardDataService $overview
     * @param EntityManagerInterface $manager
     * @param Request $request
     * @return JsonResponse
     */
    public function updateSiteProOverview(Site $site, SiteProDataService $overview, EntityManagerInterface $manager): JsonResponse
    {
        $startDate = new DateTime(date("Y-m-01", strtotime(date('Y-m-d'))) . '00:00:00');
        $endDate   = new DateTime(date("Y-m-t", strtotime(date('Y-m-d'))) . '23:59:59');
        // dump($startDate);
        // dump($endDate);

        $overview->setSite($site)
            ->setPower_unit(1)
            ->setStartDate($startDate)
            ->setEndDate($endDate);

        $overViewData = $overview->getOverviewData();
        //dump($overViewData);

        return $this->json([
            'code'            => 200,
            'loadSiteData'    => $overViewData['loadSiteData'],
            'gridData'        => $overViewData['gridData'],
            'gensetData'      => $overViewData['gensetData'],
            'hasgensetMod'    => $overViewData['hasgensetMod'],
            'contriGensetKWh' => $overViewData['contriGensetKWh'],
            'kgCO2'           => $overViewData['kgCO2'],
        ], 200);
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
            $updateHistoGraphs = $histo->updateHistoGraphs();

            return $this->json([
                'code'         => 200,
                'Mixed_Conso'  => [
                    'date'  => $updateHistoGraphs['consoChart_Data']['dateConso'],
                    'conso' => [
                        $updateHistoGraphs['consoChart_Data']['kWh'],
                        $updateHistoGraphs['consoChart_Data']['kgCO2']
                    ]
                ],
                'Load_Chart'   => $updateHistoGraphs['loadChart_Data'],
            ], 200);
        }

        return $this->json([
            'code' => 403,
            'message' => 'Empty Array or Not exists !',
        ], 403);
    }

    /**
     * Permet la MAJ des historiques des sites pro
     * 
     * @Route("/{slug<[a-zA-Z0-9-_]+>}/pro/histo-graphs/update", name="site_pro_histo_update")
     *
     * @param Site $site
     * @param SiteProDashboardDataService $histo
     * @param EntityManagerInterface $manager
     * @param Request $request
     * @return JsonResponse
     */
    public function updateSiteProHisto(Site $site, SiteProDataService $histo, Request $request): JsonResponse
    {
        //Récupération et vérification des paramètres au format JSON contenu dans la requête
        $paramJSON = $this->getJSONRequest($request->getContent());
        if (array_key_exists("startDate", $paramJSON) && array_key_exists("endDate", $paramJSON)) {
            //Initialisation du service
            $histo->setSite($site)
                ->setStartDate(new DateTime($paramJSON['startDate']))
                ->setEndDate(new DateTime($paramJSON['endDate']));
            $updateHistoPro = $histo->getChartDataForDateRange();

            return $this->json([
                'code'         => 200,
                'totalkWh'     => $updateHistoPro['totalkWh'],
                'Mixed_Conso'  => [
                    'date'  => $updateHistoPro['consoDate'],
                    'conso' => $updateHistoPro['consoData']
                ],
                'pieChart'   => $updateHistoPro['dataPie'],
            ], 200);
        }

        return $this->json([
            'code' => 403,
            'message' => 'Empty Array or Not exists !',
        ], 403);
    }

    /**
     * Permet la MAJ des historiques des sites pro
     * 
     * @Route("/{slug<[a-zA-Z0-9-_]+>}/pro/historical-analytic/update", name="site_pro_historical_analytic_update")
     *
     * @param Site $site
     * @param SiteProDataAnalyticService $siteAnalytic
     * @param EntityManagerInterface $manager
     * @param Request $request
     * @return JsonResponse
     */
    public function update_historical_analytic_site_pro(Site $site, SiteProDataAnalyticService $siteAnalytic, Request $request): JsonResponse
    {
        //Récupération et vérification des paramètres au format JSON contenu dans la requête
        $paramJSON = $this->getJSONRequest($request->getContent());
        if (array_key_exists("startDate", $paramJSON) && array_key_exists("endDate", $paramJSON)) {
            //Initialisation du service
            $siteAnalytic->setSite($site)
                ->setPower_unit(1)
                ->setStartDate(new DateTime($paramJSON['startDate']))
                ->setEndDate(new DateTime($paramJSON['endDate']));
            $dataAnalysis = $siteAnalytic->getDataAnalysis();
            return $this->json([
                'code'         => 200,
                'dataAnalysis'    => $dataAnalysis,
            ], 200);
        }

        return $this->json([
            'code' => 403,
            'message' => 'Empty Array or Not exists !',
        ], 403);
    }
}
