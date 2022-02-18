<?php

namespace App\Service;

use DateTime;
use App\Entity\Enterprise;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\SiteDashboardDataService;

class EnterpriseDashboardDataService
{
    /**
     * Prix du kWh en F CFA
     *
     * @var float
     */
    private $kWhPrice = 99;

    private $tranchesResidential = [
        '0-110'   => 50,
        '111-400' => 79,
        '401-800' => 94,
        '800+'    => 99,
    ];

    private $tranchesNonResidential = [
        '0-110'   => 84,
        '111-400' => 92,
        '401+'    => 99,
    ];

    /**
     * Quantité de gaz à effet de serre(en kgCO2) émis par kWh
     *
     * @var float
     */
    private $CO2PerkWh = 0.207;

    /**
     * Variable qui contient l'entity manager interface
     *
     */
    private $manager;

    /**
     * Variable qui contient le service de gestion du dashboard d'un Site
     *
     * @var SiteDashboardDataService
     */
    private $siteDashService;

    /**
     * Variable qui contient l'entreprise courante
     *
     * @var Enterprise
     */
    private $enterprise;

    /**
     * Date de début de la fenêtre de date choisie par l'utilisateur
     *
     * @var DateTime
     */
    private $startDate;

    /**
     * Date de fin de la fenêtre de date choisie par l'utilisateur
     *
     * @var DateTime
     */
    private $endDate;

    /**
     * Variable qui contient le motif du mois en cours
     * utilisée dans les conditions 'LIKE' des requêtes DQL
     *
     * @var string
     */
    private $currentMonthStringDate = '';

    /**
     * Conso du mois en cours (en kWh) d'une entreprise donnée (Somme des conso de tous les sites de ladite entreprise)
     *
     * @var float
     */
    private $currentConsokWh    = 0.0;

    /**
     * Conso du mois n - 1 (en kWh)  d'une entreprise donnée (Somme des conso de tous les sites de ladite entreprise)
     *
     * @var float
     */
    private $lastConsokWh       = 0.0;

    /**
     * Emission du mois en cours (en kgC02) d'une entreprise donnée (Somme des émissions de tous les sites de ladite entreprise)
     *
     * @var float
     */
    private $currentGasEmission = 0.0;

    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager  = $manager;
        $this->siteDashService = new SiteDashboardDataService($this->manager);
        $this->currentMonthStringDate = date('Y-m') . '%';
    }

    /**
     * Permet d'avoir les données de conso en kWh, facteur de puissance ainsi que le taux d'émission de CO2
     * relatif à cette conso(en kWh) sur le mois en cours pour les différents sites d'une entreprise donné
     *
     * @return array
     */
    public function getCurrentMonthSiteParams()
    {
        $this->currentConsokWh     = 0.0;
        $this->lastConsokWh        = 0.0;
        $this->currentGasEmission  = 0.0;
        $siteCurrentMonthParams    = [];

        foreach ($this->enterprise->getSites() as $site) {
            $this->siteDashService->setSite($site)
                ->setPower_unit(1000);

            $temp                      = $this->siteDashService->getCurrentMonthkWhConsumption();
            $this->currentConsokWh    += $temp['currentConsokWh'];
            $this->lastConsokWh       += $temp['lastConsokWh'];
            $this->currentGasEmission += $temp['currentGasEmission'];

            $loadData = $this->siteDashService->getLoadChartDataForCurrentMonth();
            $siteCurrentMonthParams['' . $site->getSlug()] = [
                // 'Power'                    => $this->siteDashService->getLastkWForCurrentMonth(),
                'Power'                    => number_format((float) $loadData['Pnow'], 2, '.', ' '),
                'isGenset'                 => $loadData['isGenset'],
                'currentConsokWh'          => number_format((float) $temp['currentConsokWh'], 2, '.', ''),
                'currentConsokWhProgress'  => number_format((float) $temp['currentConsokWhProgress'], 2, '.', ' '),
                //'currentConsoXAF'          => number_format((float) $temp['currentConsoXAF'], 2, '.', ' '),
                'currentGasEmission'       => number_format((float) $temp['currentGasEmission'], 2, '.', ''),
                'currentPF'                => number_format((float) $temp['currentPF'], 2, '.', ' '),
                'lastDatetimeData'         => $this->siteDashService->getLastDatetimeData(),
            ];
        }

        return $siteCurrentMonthParams;
    }

    /**
     * Permet d'avoir les données de conso en kWh, ainsi que le taux d'émission de CO2 relatif 
     * à cette conso(en kWh) sur le mois en cours pour une entreprise donné
     * 
     * N.B: Méthode à appeler après la méthode getCurrentMonthSiteParams()
     * 
     * @return array
     */
    public function getCurrentMonthkWhConsumption()
    {

        $currentConsokWhProgress = ($this->lastConsokWh > 0) ? ($this->currentConsokWh - $this->lastConsokWh) / $this->lastConsokWh : 'INF';

        return array(
            'currentConsokWh'          => number_format((float) $this->currentConsokWh, 2, '.', ' '),
            'currentConsokWhProgress'  => number_format((float) $currentConsokWhProgress, 2, '.', ' '),
            //'currentConsoXAF'          => $temp['currentConsoXAF'],
            'currentGasEmission'       => number_format((float) $this->currentGasEmission, 2, '.', ' '),
        );
    }

    /**
     * Permet d'obtenir les données de conso (en kWh) et d'émission de CO2
     * jour après jour pour un site donné
     *
     * @return array
     */
    public function getDayByDayConsumptionForCurrentMonth()
    {
        $dayByDayConsoData = $this->manager->createQuery("SELECT SUBSTRING(d.dateTime,1,10) AS jour, SUM(d.ea) AS EA, SUM(d.ea)*:kgCO2 AS kgCO2
                                            FROM App\Entity\LoadEnergyData d
                                            JOIN d.smartMod sm
                                            WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s JOIN s.enterprise e WHERE e.id = :enterpriseId AND stm.modType='GRID')
                                            AND d.dateTime LIKE :currentMonth
                                            GROUP BY jour
                                            ORDER BY jour ASC")
            ->setParameters(array(
                'kgCO2'         => $this->CO2PerkWh,
                'currentMonth'  => $this->currentMonthStringDate,
                'enterpriseId'  => $this->enterprise->getId()
            ))
            ->getResult();
        //dump($dayByDayConsoData);
        $dateConso = [];
        $kWh       = [];
        $kgCO2     = [];
        foreach ($dayByDayConsoData as $d) {
            $dateConso[] = $d['jour'];
            $kWh[]       = floatval(number_format((float) $d['EA'], 2, '.', ''));
            $kgCO2[]     = floatval(number_format((float) $d['kgCO2'], 2, '.', ''));
        }

        return array(
            "dateConso" => $dateConso,
            "kWh"       => $kWh,
            "kgCO2"     => $kgCO2
        );
    }

    /**
     * Get the value of enterprise
     *
     * @return Enterprise|null
     */
    public function getEnterprise(): ?Enterprise
    {
        return $this->enterprise;
    }

    /**
     * Set the value of enterprise
     *
     * @return  self
     */
    public function setEnterprise(Enterprise $enterprise)
    {
        $this->enterprise = $enterprise;

        return $this;
    }

    /**
     * Get prix du kWh en F CFA
     *
     * @return  float
     */
    public function getKWhPrice()
    {
        return $this->kWhPrice;
    }

    /**
     * Set prix du kWh en F CFA
     *
     * @param  float  $kWhPrice  Prix du kWh en F CFA
     *
     * @return  self
     */
    public function setKWhPrice(float $kWhPrice)
    {
        $this->kWhPrice = $kWhPrice;

        return $this;
    }

    /**
     * Get quantité de gaz à effet de serre(en kgCO2) émis par kWh
     *
     * @return  float
     */
    public function getCO2PerkWh()
    {
        return $this->CO2PerkWh;
    }

    /**
     * Set quantité de gaz à effet de serre(en kgCO2) émis par kWh
     *
     * @param  float  $CO2PerkWh  Quantité de gaz à effet de serre(en kgCO2) émis par kWh
     *
     * @return  self
     */
    public function setCO2PerkWh(float $CO2PerkWh)
    {
        $this->CO2PerkWh = $CO2PerkWh;

        return $this;
    }

    /**
     * Get date de début de la fenêtre de date choisie par l'utilisateur
     *
     * @return  DateTime
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * Set date de début de la fenêtre de date choisie par l'utilisateur
     *
     * @param  DateTime  $startDate  Date de début de la fenêtre de date choisie par l'utilisateur
     *
     * @return  self
     */
    public function setStartDate(DateTime $startDate)
    {
        $this->startDate = $startDate;

        return $this;
    }

    /**
     * Get date de fin de la fenêtre de date choisie par l'utilisateur
     *
     * @return  DateTime
     */
    public function getEndDate()
    {
        return $this->endDate;
    }

    /**
     * Set date de fin de la fenêtre de date choisie par l'utilisateur
     *
     * @param  DateTime  $endDate  Date de fin de la fenêtre de date choisie par l'utilisateur
     *
     * @return  self
     */
    public function setEndDate(DateTime $endDate)
    {
        $this->endDate = $endDate;

        return $this;
    }
}
