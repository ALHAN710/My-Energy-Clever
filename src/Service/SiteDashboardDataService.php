<?php

namespace App\Service;

use DateTime;
use DateInterval;
use App\Entity\Site;
use Doctrine\ORM\EntityManagerInterface;

class SiteDashboardDataService
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

    private $manager;

    private $site;

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

    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager  = $manager;
    }

    public function getCurrentMonthkWhConsumption()
    {
        $currentConsoQuery = $this->manager->createQuery("SELECT SUM(d.ea) AS EA
                                            FROM App\Entity\LoadEnergyData d
                                            JOIN d.smartMod sm
                                            WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='GRID')
                                            AND d.dateTime LIKE :currentMonth")
            ->setParameters(array(
                'currentMonth'  => date('Y-m') . '%',
                'siteId'    => $this->site->getId()
            ))
            ->getResult();

        $lastConsokWh = $this->getLastMonthkWhConsumption();
        //dump($lastConsokWh);

        $currentConsokWh         = count($currentConsoQuery) > 0 ? $currentConsoQuery[0]['EA'] : 0;
        $currentConsokWhProgress = ($lastConsokWh !== 0) ? ($currentConsokWh - $lastConsokWh) / $lastConsokWh : 'INF';
        $currentConsoXAF         = $this->getConsumptionXAF($this->site, $currentConsokWh);
        $currentGasEmission      = $currentConsokWh * $this->CO2PerkWh;

        return array(
            'currentConsokWh'         => $currentConsokWh,
            'currentConsokWhProgress' => $currentConsokWhProgress,
            'currentConsoXAF'         => $currentConsoXAF,
            'currentGasEmission'      => $currentGasEmission
        );
    }

    public function getCurrentMonthkWConsumption()
    {
        $currentConsoQuery = $this->manager->createQuery("SELECT SUM(d.pmoy) AS kW
                                            FROM App\Entity\LoadEnergyData d
                                            JOIN d.smartMod sm
                                            WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='GRID')
                                            AND d.dateTime LIKE :currentMonth")
            ->setParameters(array(
                'currentMonth'  => date('Y-m') . '%',
                'siteId'    => $this->site->getId()
            ))
            ->getResult();
        dump($currentConsoQuery);

        $currentConsokW = count($currentConsoQuery) > 0 ? $currentConsoQuery[0]['kW'] : 0;
        $currentGasEmission = $currentConsokW * $this->CO2PerkWh;

        return array(
            'currentConsokW'     => $currentConsokW,
            'currentGasEmission' => $currentGasEmission
        );
    }

    public function getLastMonthkWhConsumption()
    {
        $now = new DateTime('now');
        $date = $now;
        $date->sub(new DateInterval('P1M'));

        $lastConsoQuery = $this->manager->createQuery("SELECT SUM(d.ea) AS EA
                                            FROM App\Entity\LoadEnergyData d
                                            JOIN d.smartMod sm
                                            WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='GRID')
                                            AND d.dateTime LIKE :lastMonth
                                            AND d.dateTime <= :nowDate
                                            ")
            ->setParameters(array(
                'lastMonth' => $date->format('Y-m') . '%',
                'nowDate'   => $now->format('Y-m-d H:i:s'),
                'siteId'    => $this->site->getId()
            ))
            ->getResult();

        //dump($lastConsoQuery);
        $lastConso = count($lastConsoQuery) > 0 ? $lastConsoQuery[0]['EA'] ?? 0 : 0;
        //dump('lastConso = ' . $lastConso);

        return $lastConso;
    }

    public function getDayByDayConsumptionForCurrentMonth()
    {
        $dayByDayConsoData = $this->manager->createQuery("SELECT SUBSTRING(d.dateTime,1,10) AS jour, SUM(d.ea) AS EA, SUM(d.ea)*:kgCO2 AS kgCO2
                                            FROM App\Entity\LoadEnergyData d
                                            JOIN d.smartMod sm
                                            WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='GRID')
                                            AND d.dateTime LIKE :currentMonth
                                            GROUP BY jour
                                            ORDER BY jour ASC")
            ->setParameters(array(
                'kgCO2'        => $this->CO2PerkWh,
                'currentMonth'  => date('Y-m') . '%',
                'siteId'    => $this->site->getId()
            ))
            ->getResult();
        //dump($dayByDayConsoData);
        $dateConso = [];
        $kWh = [];
        $kgCO2 = [];
        foreach ($dayByDayConsoData as $d) {
            $dateConso[] = $d['jour'];
            $kWh[] = floatval(number_format((float) $d['EA'], 2, '.', ''));
            $kgCO2[] = floatval(number_format((float) $d['kgCO2'], 2, '.', ''));
        }

        return array(
            "dateConso" => $dateConso,
            "kWh"       => $kWh,
            "kgCO2"     => $kgCO2
        );
    }

    public function getCurrentMonthDataTable()
    {
        $consoQuery = $this->manager->createQuery("SELECT SUBSTRING(d.dateTime,1,10) AS jour, SUM(d.ea) AS kWh
                                            FROM App\Entity\LoadEnergyData d
                                            JOIN d.smartMod sm
                                            WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='GRID')
                                            AND d.dateTime LIKE :currentMonth
                                            GROUP BY jour
                                            ORDER BY kWh DESC")
            ->setParameters(array(
                'currentMonth' => date('Y-m') . '%',
                'siteId'       => $this->site->getId()
            ))
            ->getResult();
        //dump($consoQuery);
        $strPlusForteConso = "-";
        $strPlusFaibleConso = "-";
        if (!empty($consoQuery)) {

            $lowConso = end($consoQuery);
            $highConso = reset($consoQuery);
            //dump($lowConso);
            //dump($highConso);
            //number_format((float) $d['kW'], 2, '.', '')

            $strPlusForteConso  = $highConso != null ? number_format((float) $highConso['kWh'], 2, '.', ' ') . ' kWh | ' . $highConso['jour'] : '-';
            $strPlusFaibleConso = $lowConso != null ? number_format((float) $lowConso['kWh'], 2, '.', ' ') . ' kWh | ' . $lowConso['jour'] : '-';
        }

        $powerQuery = $this->manager->createQuery("SELECT DISTINCT d.dateTime AS jour, NULLIF(d.pmoy, 0) AS kW 
                                            FROM App\Entity\LoadEnergyData d
                                            JOIN d.smartMod sm
                                            WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='GRID')
                                            AND d.dateTime LIKE :currentMonth
                                            GROUP BY jour
                                            ORDER BY kW ASC")
            ->setParameters(array(
                'currentMonth' => date('Y-m') . '%',
                'siteId'       => $this->site->getId()
            ))
            ->getResult();
        //dump($powerQuery);

        $strPic = '-';
        $strTalon = '-';
        if (!empty($powerQuery)) {

            $lowPower = reset($powerQuery);
            $highPower = end($powerQuery);
            //dump($lowPower);
            $strTalon = $lowPower != null ? number_format((float) ($lowPower['kW'] * 1000), 2, '.', ' ') . ' W | ' . $lowPower['jour']->format('d-m-Y H:i:s') : '-';
            $strPic   = $highPower != null ? number_format((float) ($highPower['kW'] * 1000), 2, '.', ' ') . ' W | ' . $highPower['jour']->format('d-m-Y H:i:s') : '-';
        }

        return array(
            'consoMoy'     => $this->getAverageConsumption(10, date('Y-m') . '%'),
            'variation'    => $this->getVariation(),
            '+forteConso'  => $strPlusForteConso,
            '+faibleConso' => $strPlusFaibleConso,
            'Talon'        => $strTalon,
            'Pic'          => $strPic,
        );
    }

    public function getMonthByMonthDataTableForCurrentYear()
    {
        $dataMonthsForCurrentYearQuery = $this->manager->createQuery("SELECT SUBSTRING(d.dateTime,1,7) AS dt, MIN(d.dateTime) AS min_, MAX(d.dateTime) AS max_
                                            FROM App\Entity\LoadEnergyData d
                                            JOIN d.smartMod sm
                                            WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='GRID')
                                            AND d.dateTime LIKE :currentYear
                                            GROUP BY dt
                                            ")
            ->setParameters(array(
                'currentYear'  => date('Y') . '%',
                'siteId'       => $this->site->getId()
            ))
            ->getResult();
        dump($dataMonthsForCurrentYearQuery);

        $monthByMonthDataQuery = $this->manager->createQuery("SELECT SUBSTRING(d.dateTime,1,7) AS dt, SUM(d.ea) AS EA, SUM(d.ea)*:kgCO2 AS kgCO2, MAX(d.pmoy) AS Pmax,
                                            MIN(NULLIF(d.pmoy, 0)) AS talon 
                                            FROM App\Entity\LoadEnergyData d
                                            JOIN d.smartMod sm
                                            WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='GRID')
                                            AND d.dateTime LIKE :currentYear
                                            GROUP BY dt
                                            ORDER BY dt ASC")
            ->setParameters(array(
                'kgCO2'        => $this->CO2PerkWh,
                'currentYear'  => date('Y') . '%',
                'siteId'       => $this->site->getId()
            ))
            ->getResult();
        dump($monthByMonthDataQuery);
        return $monthByMonthDataQuery;
        foreach ($monthByMonthDataQuery as $monthByMonthDataQuery) {
        }
    }

    public function getLoadChartDataForCurrentMonth()
    {
        $loadChartData = $this->manager->createQuery("SELECT DISTINCT d.dateTime AS jour, d.pmoy*1000 AS kW
                                            FROM App\Entity\LoadEnergyData d
                                            JOIN d.smartMod sm
                                            WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='GRID')
                                            AND d.dateTime LIKE :currentMonth
                                            GROUP BY jour
                                            ORDER BY jour ASC")
            ->setParameters(array(
                'currentMonth'  => date('Y-m') . '%',
                'siteId'    => $this->site->getId()
            ))
            ->getResult();
        //dump($loadChartData);

        $dateP = [];
        $kW = [];

        foreach ($loadChartData as $d) {
            $dateP[] = $d['jour']->format('Y-m-d H:i:s');
            $kW[] = floatval(number_format((float) $d['kW'], 2, '.', ''));
        }

        return array(
            "dateP"  => $dateP,
            "kW"     => $kW,
            "Pnow"   => end($kW) ?? 0
        );
    }

    public function updateHistoGraphs()
    {
        return array(
            'loadChart_Data'  => $this->getLoadChartDataForDateRange(),
            'consoChart_Data' => $this->getConsumptionkWhChartDataForDateRange(),
        );
    }

    public function getLoadChartDataForDateRange()
    {
        $loadChartData = $this->manager->createQuery("SELECT DISTINCT d.dateTime AS dt, d.pmoy*1000 AS kW
                                            FROM App\Entity\LoadEnergyData d
                                            JOIN d.smartMod sm
                                            WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='GRID')
                                            AND d.dateTime BETWEEN :startDate AND :endDate
                                            GROUP BY dt
                                            ORDER BY dt ASC")
            ->setParameters(array(
                'startDate'  => $this->startDate->format('Y-m-d H:i:s'),
                'endDate'    => $this->endDate->format('Y-m-d H:i:s'),
                'siteId'     => $this->site->getId()
            ))
            ->getResult();
        //dump($loadChartData);
        $dateP = [];
        $kW = [];

        foreach ($loadChartData as $d) {
            $dateP[] = $d['dt']->format('Y-m-d H:i:s');
            $kW[] = floatval(number_format((float) $d['kW'], 2, '.', ''));
        }

        return array(
            "dateP"  => $dateP,
            "kW"     => $kW,
            //"Pnow"   => end($kW) ?? 0
        );
    }

    public function getConsumptionkWhChartDataForDateRange()
    {
        $daterangeConsoData = $this->manager->createQuery("SELECT DISTINCT d.dateTime AS dt, d.ea AS EA, d.ea*:kgCO2 AS kgCO2
                                            FROM App\Entity\LoadEnergyData d
                                            JOIN d.smartMod sm
                                            WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='GRID')
                                            AND d.dateTime BETWEEN :startDate AND :endDate
                                            GROUP BY dt
                                            ORDER BY dt ASC")
            ->setParameters(array(
                'kgCO2'      => $this->CO2PerkWh,
                'startDate'  => $this->startDate->format('Y-m-d H:i:s'),
                'endDate'    => $this->endDate->format('Y-m-d H:i:s'),
                'siteId'     => $this->site->getId()
            ))
            ->getResult();
        //dump($daterangeConsoData);

        $dateConso = [];
        $kWh = [];
        $kgCO2 = [];
        foreach ($daterangeConsoData as $d) {
            $dateConso[] = $d['dt'];
            $kWh[] = floatval(number_format((float) $d['EA'], 4, '.', ''));
            $kgCO2[] = floatval(number_format((float) $d['kgCO2'], 6, '.', ''));
        }

        return array(
            "dateConso" => $dateConso,
            "kWh"       => $kWh,
            "kgCO2"     => $kgCO2
        );
    }

    public function getCurrentActivePower()
    {
        $currentActivePower = $this->manager->createQuery("SELECT d.dateTime AS dt, d.pmoy, d.id
                                            FROM App\Entity\LoadEnergyData d
                                            JOIN d.smartMod sm
                                            WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='GRID')
                                            AND d.dateTime = (SELECT MAX(d1.dateTime) FROM App\Entity\LoadEnergyData d1 WHERE d1.dateTime LIKE :currentMonth)
                                            GROUP BY dt
                                            ORDER BY dt ASC")
            ->setParameters(array(
                'currentMonth'  => date('Y-m') . '%',
                'siteId'    => $this->site->getId()
            ))
            ->getResult();
        dump($currentActivePower);
    }

    public function getAverageConsumption(int $length = 10, $strLike = '')
    {
        $averageConsumption = [];
        //Longueur de la sous-chaîne de date utilisé pour grouper les données dans la DQL
        //2021-09-28

        //Chaîne utilisée dans le filtre LIKE de la DQL 
        $strLike_ = $strLike ?? date('Y-m') . '%';
        $averageConsumption = $this->manager->createQuery("SELECT SUBSTRING(d.dateTime,1,:length_) AS dt, SUM(d.ea) AS EA
                                            FROM App\Entity\LoadEnergyData d
                                            JOIN d.smartMod sm
                                            WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='GRID')
                                            AND d.dateTime LIKE :str_
                                            GROUP BY dt
                                            ORDER BY dt ASC")
            ->setParameters(array(
                'str_'  => $strLike_,
                'length_'   => $length,
                'siteId'    => $this->site->getId()
            ))
            ->getResult();

        /*$averageConsumption = $this->manager->createQuery("SELECT SUBSTRING(d.dateTime,1,7) AS dt, SUM(d.ea) AS EA
                                            FROM App\Entity\LoadEnergyData d
                                            JOIN d.smartMod sm
                                            WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='GRID')
                                            AND d.dateTime LIKE :currentYear
                                            GROUP BY dt
                                            ORDER BY dt ASC")
                    ->setParameters(array(
                        'currentYear'  => date('Y') . '%',
                        'siteId'    => $this->site->getId()
                    ))
                    ->getResult();*/

        // dump($averageConsumption);
        // dump(count($averageConsumption));
        $consoMoy = 0;
        if (count($averageConsumption) > 0) {
            foreach ($averageConsumption as $average) {
                $consoMoy += floatval($average['EA']);
            }

            $consoMoy = $consoMoy / (count($averageConsumption) * 1.0);
            // dump($consoMoy);
            //$consoMoy = number_format((float) $consoMoy, 2, '.', ' ');

        }

        return $consoMoy;
    }

    private function getConsumptionXAF($site, $consokWh_)
    {
        $tranches = [
            'Residential'     => $this->tranchesResidential,
            'Non Residential' => $this->tranchesNonResidential,
        ];
        $consokWh = floatval($consokWh_);
        $siteTranches = $tranches[$site->getSubscriptionUsage()];
        //dump($siteTranches);
        $consoXAF = 0;
        if ($consokWh <= 110) {
            //dump($consokWh);
            //dump($consokWh * $siteTranches['0-110']);
            $consoXAF = $consokWh * $siteTranches['0-110'];
        } else if ($consokWh >= 111 && $consokWh <= 400) $consoXAF = $consokWh * $siteTranches['111-400'];
        else if ($consokWh >= 401 && $consokWh <= 800 && $site->getSubscriptionUsage() === 'Residantial') $consoXAF = $consokWh * $siteTranches['401-800'];
        else if ($consokWh >= 401 && $site->getSubscriptionUsage() === 'Non Residantial') $consoXAF = $consokWh * $siteTranches['401+'];
        else if ($consokWh > 800 && $site->getSubscriptionUsage() === 'Residantial') $consoXAF = $consokWh * $siteTranches['800+'];

        return $consoXAF;
    }

    public function getVariation()
    {
        $consumptionQuery = $this->manager->createQuery("SELECT SUBSTRING(d.dateTime,1,10) AS dt, SUM(d.ea) AS EA
                                            FROM App\Entity\LoadEnergyData d
                                            JOIN d.smartMod sm
                                            WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='GRID')
                                            AND d.dateTime LIKE :currentMonth
                                            GROUP BY dt
                                            ORDER BY dt ASC")
            ->setParameters(array(
                'currentMonth'  => date('Y-m') . '%',
                'siteId'    => $this->site->getId()
            ))
            ->getResult();

        $variation = 0;
        if (count($consumptionQuery) > 0) {
            $arrayConsoDayByDay = [];
            foreach ($consumptionQuery as $conso) {
                $arrayConsoDayByDay[] = floatval($conso['EA']);
            }
            $variation = $this->ecart_type($arrayConsoDayByDay);
        }

        return $variation * 100.0;
    }

    private function ecart_type(array $donnees)
    {
        //0 - Nombre d’éléments dans le tableau
        $population = count($donnees);
        if ($population != 0) {
            //1 - somme du tableau
            $somme_tableau = array_sum($donnees);
            //2 - Calcul de la moyenne
            $moyenne = ($somme_tableau * 1.0) / $population;
            //3 - écart pour chaque valeur
            $ecart = [];
            for ($i = 0; $i < $population; $i++) {
                //écart entre la valeur et la moyenne
                $ecart_donnee = $donnees[$i] - $moyenne;
                //carré de l'écart
                $ecart_donnee_carre = pow($ecart_donnee, 2);
                //Insertion dans le tableau
                array_push($ecart, $ecart_donnee_carre);
            }
            //4 - somme des écarts
            $somme_ecart = array_sum($ecart);
            //5 - division de la somme des écarts par la population
            $division = $somme_ecart / $population;
            //6 - racine carrée de la division
            $ecart_type = sqrt($division);
        } else {
            $ecart_type = 0; //"Le tableau est vide";
        }
        //7 - renvoi du résultat
        return $ecart_type;
    }

    /**
     * Get the value of site
     *
     * @return Site|null
     */
    public function getSite(): ?Site
    {
        return $this->site;
    }

    /**
     * Set the value of site
     *
     * @return  self
     */
    public function setSite(Site $site)
    {
        $this->site = $site;

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
