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

    private $currentMonthStringDate = '';

    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager  = $manager;
        $this->currentMonthStringDate = date('Y-m') . '%';
    }

    public function getCurrentMonthkWhConsumption()
    {
        $currentConsoQuery = $this->manager->createQuery("SELECT SUM(d.ea) AS EA
                                            FROM App\Entity\LoadEnergyData d
                                            JOIN d.smartMod sm
                                            WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='GRID')
                                            AND d.dateTime LIKE :currentMonth")
            ->setParameters(array(
                'currentMonth'  => $this->currentMonthStringDate,
                'siteId'        => $this->site->getId()
            ))
            ->getResult();

        // dump($currentConsoQuery);
        $lastConsokWh = $this->getLastMonthkWhConsumption();
        //dump($lastConsokWh);

        $currentConsokWh         = count($currentConsoQuery) > 0 ? $currentConsoQuery[0]['EA'] ?? 0 : 0;
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
                'currentMonth'  => $this->currentMonthStringDate,
                'siteId'        => $this->site->getId()
            ))
            ->getResult();
        //dump($currentConsoQuery);

        $currentConsokW     = count($currentConsoQuery) > 0 ? $currentConsoQuery[0]['kW'] : 0;
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
                                            AND d.dateTime <= :lastNowDate
                                            ")
            ->setParameters(array(
                'lastMonth'     => $date->format('Y-m') . '%',
                'lastNowDate'   => $date->format('Y-m-d H:i:s'),
                'siteId'        => $this->site->getId()
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
                'currentMonth'  => $this->currentMonthStringDate,
                'siteId'    => $this->site->getId()
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
                'currentMonth' => $this->currentMonthStringDate,
                'siteId'       => $this->site->getId()
            ))
            ->getResult();
        //dump($consoQuery);
        $strPlusForteConso = "-";
        $strPlusFaibleConso = "-";
        if (!empty($consoQuery)) {

            $lowConso      = end($consoQuery);
            $highConso     = reset($consoQuery);
            $lowConsoDate  = new DateTime($lowConso['jour']);
            $highConsoDate = new DateTime($highConso['jour']);
            //dump($lowConso);
            //dump($highConso);
            //number_format((float) $d['kW'], 2, '.', '')
            $strPlusForteConso  = $highConso != null ? number_format((float) $highConso['kWh'], 2, '.', ' ') . ' kWh @ ' . $highConsoDate->format('d M Y') : '-';
            $strPlusFaibleConso = $lowConso != null ? number_format((float) $lowConso['kWh'], 2, '.', ' ') . ' kWh @ ' . $lowConsoDate->format('d M Y') : '-';
        }

        $powerQuery = $this->manager->createQuery("SELECT DISTINCT d.dateTime AS jour, NULLIF(d.pmoy, 0) AS kW 
                                            FROM App\Entity\LoadEnergyData d
                                            JOIN d.smartMod sm
                                            WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='GRID')
                                            AND d.dateTime LIKE :currentMonth
                                            GROUP BY jour
                                            ORDER BY kW ASC")
            ->setParameters(array(
                'currentMonth' => $this->currentMonthStringDate,
                'siteId'       => $this->site->getId()
            ))
            ->getResult();
        //dump($powerQuery);

        $strPic = '-';
        $strTalon = '-';
        if (!empty($powerQuery)) {

            //$lowPower = reset($powerQuery);
            $highPower = end($powerQuery);
            $lowPower = null;
            $array = $powerQuery;
            $min = 0;

            foreach ($array as $v) if ($v['kW'] > $min) {
                $lowPower = $v;
                break;
            }

            //dump($lowPower);
            // $strTalon = $lowPower != null ? number_format((float) ($lowPower['kW']), 2, '.', ' ') . ' W @ ' . $lowPower['jour']->format('d M Y H:i:s') : '-';
            $strTalon = $lowPower != null ? number_format((float) ($lowPower['kW']), 2, '.', ' ') . ' W @ ' . $lowPower['jour']->format('d M Y H:i:s') : '-';
            $strPic   = $highPower != null ? number_format((float) ($highPower['kW']), 2, '.', ' ') . ' W @ ' . $highPower['jour']->format('d M Y H:i:s') : '-';
        }
        $consoMoy      = $this->getAverageConsumptionWithLimit(10, date('Y-m') . '%', date('Y-m-d H:i:s') . '%');
        // $testDate = new DateTime('2021-10-15 17:10:00');
        // $consoMoy = $this->getAverageConsumptionWithLimit(10, $testDate->format('Y-m'), $testDate->format('Y-m-d H:i:s'));
        $now      = new DateTime('now');
        $lastMonthDate = $now;
        //$date = $testDate;
        $lastMonthDate->sub(new DateInterval('P1M'));
        $lastConsokWh = $this->getAverageConsumptionWithLimit(10, $lastMonthDate->format('Y-m') . '%', $lastMonthDate->format('Y-m-d H:i:s') . '%');
        //dump($lastConsokWh);

        $consoMoyProgress = ($lastConsokWh !== 0) ? ($consoMoy - $lastConsokWh) / $lastConsokWh : 'INF';

        $currentMonthConsokWhPerHoursRangeQuery = $this->manager->createQuery("SELECT SUM(CASE 
                                                                           WHEN SUBSTRING(d.dateTime, 12) BETWEEN '00:00:00' AND '05:59:59' THEN d.ea
                                                                           ELSE 0
                                                                END) AS Tranche00_06,
                                                                SUM(CASE 
                                                                    WHEN SUBSTRING(d.dateTime, 12) BETWEEN '06:00:00' AND '17:59:59' THEN d.ea
                                                                    ELSE 0
                                                                END) AS Tranche06_18, 
                                                                SUM(CASE 
                                                                    WHEN SUBSTRING(d.dateTime, 12) BETWEEN '18:00:00' AND '23:59:59' THEN d.ea
                                                                    ELSE 0
                                                                END) AS Tranche18_00 
                                                                FROM App\Entity\LoadEnergyData d
                                                                JOIN d.smartMod sm
                                                                WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='GRID')
                                                                AND d.dateTime LIKE :currentMonth
                                                                ")
            ->setParameters(array(
                'currentMonth' => $this->currentMonthStringDate,
                'siteId'       => $this->site->getId()
            ))
            ->getResult();

        $currentConso00_06kWh = $currentMonthConsokWhPerHoursRangeQuery[0]['Tranche00_06'] ?? 0;
        $currentConso06_18kWh = $currentMonthConsokWhPerHoursRangeQuery[0]['Tranche06_18'] ?? 0;
        $currentConso18_00kWh = $currentMonthConsokWhPerHoursRangeQuery[0]['Tranche18_00'] ?? 0;

        $lastMonthConsokWhPerHoursRangeQuery = $this->manager->createQuery("SELECT SUM(CASE 
                                                                           WHEN SUBSTRING(d.dateTime, 12) BETWEEN '00:00:00' AND '05:59:59' THEN d.ea
                                                                           ELSE 0
                                                                END) AS Tranche00_06,
                                                                SUM(CASE 
                                                                    WHEN SUBSTRING(d.dateTime, 12) BETWEEN '06:00:00' AND '17:59:59' THEN d.ea
                                                                    ELSE 0
                                                                END) AS Tranche06_18, 
                                                                SUM(CASE 
                                                                    WHEN SUBSTRING(d.dateTime, 12) BETWEEN '18:00:00' AND '23:59:59' THEN d.ea
                                                                    ELSE 0
                                                                END) AS Tranche18_00 
                                                                FROM App\Entity\LoadEnergyData d
                                                                JOIN d.smartMod sm
                                                                WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='GRID')
                                                                AND d.dateTime LIKE :lastMonth
                                                                AND d.dateTime <= :lastMonthSupDate
                                                                ")
            ->setParameters(array(
                'lastMonth'        => $lastMonthDate->format('Y-m') . '%',
                'lastMonthSupDate' => $lastMonthDate->format('Y-m-d H:i:s') . '%',
                'siteId'           => $this->site->getId()
            ))
            ->getResult();

        $lastConso00_06kWh = $lastMonthConsokWhPerHoursRangeQuery[0]['Tranche00_06'] ?? 0;
        $lastConso06_18kWh = $lastMonthConsokWhPerHoursRangeQuery[0]['Tranche06_18'] ?? 0;
        $lastConso18_00kWh = $lastMonthConsokWhPerHoursRangeQuery[0]['Tranche18_00'] ?? 0;

        $currentConso00_06kWhProgress = floatval($lastConso00_06kWh) > 0 ? (floatval($currentConso00_06kWh) - floatval($lastConso00_06kWh)) / floatval($lastConso00_06kWh) : 'INF';
        $currentConso06_18kWhProgress = floatval($lastConso06_18kWh) > 0 ? (floatval($currentConso06_18kWh) - floatval($lastConso06_18kWh)) / floatval($lastConso06_18kWh) : 'INF';
        $currentConso18_00kWhProgress = floatval($lastConso18_00kWh) > 0 ? (floatval($currentConso18_00kWh) - floatval($lastConso18_00kWh)) / floatval($lastConso18_00kWh) : 'INF';

        return array(
            //'consoMoy'     => $this->getAverageConsumption(10, $this->currentMonthStringDate),
            'consoMoy'             => $consoMoy,
            'consoMoyProgress'     => $consoMoyProgress,
            'variation'            => $this->getVariation(),
            '+forteConso'          => $strPlusForteConso,
            '+faibleConso'         => $strPlusFaibleConso,
            'Talon'                => $strTalon,
            'Pic'                  => $strPic,
            'conso-00-06'          => $currentConso00_06kWh,
            'conso-00-06Progress'  => $currentConso00_06kWhProgress,
            'conso-06-18'          => $currentConso06_18kWh,
            'conso-06-18Progress'  => $currentConso06_18kWhProgress,
            'conso-18-00'          => $currentConso18_00kWh,
            'conso-18-00Progress'  => $currentConso18_00kWhProgress,
        );
    }

    public function getMonthByMonthDataTableForCurrentYear()
    {
        $consoMonthByMonthForCurrentYearQuery = $this->manager->createQuery("SELECT SUBSTRING(d.dateTime,1,7) AS dt, SUM(d.ea) AS EA, d.dateTime AS day_
                                            FROM App\Entity\LoadEnergyData d
                                            JOIN d.smartMod sm
                                            WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='GRID')
                                            AND d.dateTime LIKE :currentYear
                                            GROUP BY dt
                                            ORDER BY dt ASC
                                            ")
            ->setParameters(array(
                'currentYear'  => date('Y') . '%',
                'siteId'       => $this->site->getId()
            ))
            ->getResult();
        //dump($consoMonthByMonthForCurrentYearQuery);
        $tab = [];
        foreach ($consoMonthByMonthForCurrentYearQuery as $monthData) {
            $tab[$monthData['day_']->format('F')] = [
                //'EA'       => floatval($monthData['EA']),
                'Date'     => $monthData['day_'],
                'nbDay'    => 0,
                'consoMoy' => 0,
            ];
        }
        //dump($tab);

        $nbDayMonthByMonthForCurrentYearQuery = $this->manager->createQuery("SELECT SUBSTRING(d.dateTime,1,10) AS dt, d.dateTime AS day_
                                            FROM App\Entity\LoadEnergyData d
                                            JOIN d.smartMod sm
                                            WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='GRID')
                                            AND d.dateTime LIKE :currentYear
                                            GROUP BY dt
                                            ORDER BY dt ASC
                                            ")
            ->setParameters(array(
                'currentYear'  => date('Y') . '%',
                'siteId'       => $this->site->getId()
            ))
            ->getResult();
        //dump($nbDayMonthByMonthForCurrentYearQuery);
        foreach ($nbDayMonthByMonthForCurrentYearQuery as $dayByMonth) {
            $tab[$dayByMonth['day_']->format('F')]['nbDay']++;
        }
        //dump($tab);

        $monthByMonthDataQuery = $this->manager->createQuery("SELECT SUBSTRING(d.dateTime,1,7) AS dt, SUM(d.ea) AS EA, SUM(d.ea)*:kgCO2 AS kgCO2, MAX(d.pmoy) AS Pmax,
                                            MIN(NULLIF(d.pmoy, 0)) AS talon, d.dateTime AS day_ 
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
        //dump($monthByMonthDataQuery);
        $monthByMonthData = array_merge($tab);
        //dump($monthByMonthData);
        foreach ($monthByMonthDataQuery as $monthData) {
            $monthByMonthData[$monthData['day_']->format('F')]['EA'] = floatval($monthData['EA']);
            $monthByMonthData[$monthData['day_']->format('F')]['kgCO2'] = floatval($monthData['kgCO2']);
            $monthByMonthData[$monthData['day_']->format('F')]['Pmax'] = floatval($monthData['Pmax']);
            $monthByMonthData[$monthData['day_']->format('F')]['talon'] = floatval($monthData['talon']);
            $monthByMonthData[$monthData['day_']->format('F')]['consoMoy'] = $monthByMonthData[$monthData['day_']->format('F')]['nbDay'] > 0 ? $monthByMonthData[$monthData['day_']->format('F')]['EA'] / $monthByMonthData[$monthData['day_']->format('F')]['nbDay'] : 0;;
        }
        //dump($monthByMonthData);

        //dump($monthByMonthData);
        return $monthByMonthData;
    }

    public function getLoadChartDataForCurrentMonth()
    {
        $loadChartData = $this->manager->createQuery("SELECT DISTINCT d.dateTime AS jour, d.pmoy AS kW
                                            FROM App\Entity\LoadEnergyData d
                                            JOIN d.smartMod sm
                                            WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='GRID')
                                            AND d.dateTime LIKE :currentMonth
                                            GROUP BY jour
                                            ORDER BY jour ASC")
            ->setParameters(array(
                'currentMonth'  => $this->currentMonthStringDate,
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
        //dump($kW);
        $Pnow = count($kW) > 0 ? end($kW) : 0;
        //dump($Pnow);
        return array(
            "dateP"  => $dateP,
            "kW"     => $kW,
            "Pnow"   => $Pnow
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
        $loadChartData = $this->manager->createQuery("SELECT DISTINCT d.dateTime AS dt, d.pmoy AS kW
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
        $daterangeConsoData = $this->manager->createQuery("SELECT DISTINCT SUBSTRING(d.dateTime,1,10) AS dt, SUM(d.ea) AS EA, SUM(d.ea)*:kgCO2 AS kgCO2
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
            $kWh[] = floatval(number_format((float) $d['EA'], 2, '.', ''));
            $kgCO2[] = floatval(number_format((float) $d['kgCO2'], 2, '.', ''));
        }

        return array(
            "dateConso" => $dateConso,
            "kWh"       => $kWh,
            "kgCO2"     => $kgCO2
        );
    }

    public function getLastDatetimeData()
    {
        $lastDatetimeData = $this->manager->createQuery("SELECT MAX(d.dateTime) AS lastDate
                                            FROM App\Entity\LoadEnergyData d
                                            JOIN d.smartMod sm
                                            WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='GRID')
                                            AND d.dateTime LIKE :currentMonth
                                            ")
            ->setParameters(array(
                'currentMonth'  => $this->currentMonthStringDate,
                'siteId'        => $this->site->getId()
            ))
            ->getResult();
        //dump($lastDatetimeData);
        return count($lastDatetimeData) > 0 ? ($lastDatetimeData[0]['lastDate'] !== null ? $lastDatetimeData[0]['lastDate'] : '') : '';
    }

    public function getAverageConsumption(int $length = 10, $strLike = '')
    {
        $averageConsumption = [];
        //Longueur de la sous-chaîne de date utilisé pour grouper les données dans la DQL
        //2021-09-28

        //Chaîne utilisée dans le filtre LIKE de la DQL 
        $strLike_ = $strLike ?? $this->currentMonthStringDate;
        $averageConsumption = $this->manager->createQuery("SELECT SUBSTRING(d.dateTime,1,:length_) AS dt, SUM(d.ea) AS EA
                                            FROM App\Entity\LoadEnergyData d
                                            JOIN d.smartMod sm
                                            WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='GRID')
                                            AND d.dateTime LIKE :str_
                                            GROUP BY dt
                                            ORDER BY dt ASC")
            ->setParameters(array(
                'str_'      => $strLike_,
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
                        'currentYear'  => date('Y'),
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

    public function getAverageConsumptionWithLimit(int $length = 10, $strLike = '', $strSupDateTime = '')
    {
        $averageConsumption = [];
        //Longueur de la sous-chaîne de date utilisé pour grouper les données dans la DQL
        //2021-09-28

        //Chaîne utilisée dans le filtre LIKE de la DQL 
        $strLike_ = $strLike ?? $this->currentMonthStringDate;
        $averageConsumption = $this->manager->createQuery("SELECT SUBSTRING(d.dateTime,1,:length_) AS dt, SUM(d.ea) AS EA
                                            FROM App\Entity\LoadEnergyData d
                                            JOIN d.smartMod sm
                                            WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='GRID')
                                            AND d.dateTime LIKE :strLike_
                                            AND d.dateTime <= :strSupDateTime
                                            GROUP BY dt
                                            ORDER BY dt ASC")
            ->setParameters(array(
                'strLike_'        => $strLike_,
                'strSupDateTime'  => $strSupDateTime,
                'length_'         => $length,
                'siteId'          => $this->site->getId()
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
                        'currentYear'  => date('Y'),
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
                'currentMonth'  => $this->currentMonthStringDate,
                'siteId'    => $this->site->getId()
            ))
            ->getResult();
        // dump($consumptionQuery);
        // dump(count($consumptionQuery));
        $variation = 0;
        $moyenne = 0;
        if (count($consumptionQuery) > 0) {
            $arrayConsoDayByDay = [];
            foreach ($consumptionQuery as $conso) {
                $arrayConsoDayByDay[] = floatval($conso['EA']);
            }
            // dump($arrayConsoDayByDay);
            $moyenne = array_sum($arrayConsoDayByDay) / (count($arrayConsoDayByDay) * 1.0);
            $variation = $this->ecart_type($arrayConsoDayByDay);
        }

        return $moyenne != 0 ? ($variation / $moyenne) * 100 : 0.0;
    }

    private function ecart_type(array $donnees)
    {
        //0 - Nombre d’éléments dans le tableau
        $population = count($donnees);
        // dump($donnees);
        // dump('population = ' . $population);
        if ($population != 0) {
            //1 - somme du tableau
            $somme_tableau = array_sum($donnees);
            // dump('somme_tableau = ' . $somme_tableau);
            //2 - Calcul de la moyenne
            $moyenne = ($somme_tableau * 1.0) / $population;
            // dump('moyenne = ' . $moyenne);
            //3 - écart pour chaque valeur
            $ecart = [];
            for ($i = 0; $i < $population; $i++) {
                //écart entre la valeur et la moyenne
                $ecart_donnee = $donnees[$i] - $moyenne;
                // dump('ecart_donnee ' . $i . ' = ' . $ecart_donnee);
                //carré de l'écart
                $ecart_donnee_carre = pow($ecart_donnee, 2);
                // dump('ecart_donnee_carre ' . $i . ' = ' . $ecart_donnee_carre);
                //Insertion dans le tableau
                array_push($ecart, $ecart_donnee_carre);
            }
            // dump($ecart);
            //4 - somme des écarts
            $somme_ecart = array_sum($ecart);
            // dump('somme_ecart = ' . $somme_ecart);
            //5 - division de la somme des écarts par la population
            $division = $somme_ecart / $population;
            // dump('division = ' . $division);
            //6 - racine carrée de la division
            $ecart_type = sqrt($division);
        } else {
            $ecart_type = 0; //"Le tableau est vide";
        }
        // dump('ecart_type = ' . $ecart_type);
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
