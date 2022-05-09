<?php

namespace App\Service;

use DateTime;
use DatePeriod;
use DateInterval;
use App\Entity\Site;
use App\Entity\SmartMod;
use App\Service\SiteProDataService;
use Doctrine\ORM\EntityManagerInterface;

class SiteProDataAnalyticService
{
    /**
     * Prix du kWh en F CFA
     *
     * @var float
     */
    private $kWhPrice = 99;

    /**
     * Tableau des tarifs en fonction de la tranche horaire pour 
     * les abonnés BT de type Résidentiel
     *
     * @var array
     */
    private $tranchesResidential = [
        '0-110'   => 50,
        '111-400' => 79,
        '401-800' => 94,
        '800+'    => 99,
    ];

    /**
     * Tableau des tarifs en fonction de la tranche horaire pour 
     * les abonnés BT de type Non Résidentiel
     *
     * @var array
     */
    private $tranchesNonResidential = [
        '0-110'   => 84,
        '111-400' => 92,
        '401+'    => 99,
    ];

    /**
     * Tableau des tarifs en fonction du nbre d'heure d'utilisation de Psous
     * pour les abonnés MT régime normal
     *
     * @var array
     */
    private $NHU_Psous = [
        '0-200'   => 70,
        '201-400' => 65,
        '401+'    => 60,
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
     * Multiple de Watt
     *
     * @var integer
     */
    private $power_unit = 1;

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
     * Module GRID du site
     *
     * @var SmartMod
     */
    private $gridMod;

    /**
     * Module Load Site
     *
     * @var SmartMod
     */
    private $loadSiteMod;

    /**
     * Module GENSET
     *
     * @var SmartMod
     */
    private $gensetMod;

    /**
     * Interval de temps d'envoi des données du module GRID
     *
     * @var float
     */
    private $gridIntervalTime = 5.0/60.0;

    /**
     * Interval de temps d'envoi des données du module GENSET
     *
     * @var float
     */
    private $gensetIntervalTime = 5.0/60.0;

    /**
     * Interval de temps d'envoi des données du module Load Site
     *
     * @var float
     */
    private $loadSiteIntervalTime = 5.0/60.0;

    private $siteProDataService;

    private $siteDashboardDataService;

    private $gensetModService;

    private $currentMonthStringDate = '';

    public function __construct(EntityManagerInterface $manager, SiteProDataService $siteProDataService, SiteDashboardDataService $siteDashboardDataService, GensetModService $gensetModService)
    {
        $this->manager                      = $manager;
        $this->siteProDataService           = $siteProDataService;
        $this->siteDashboardDataService     = $siteDashboardDataService;
        $this->gensetModService             = $gensetModService;
        $this->currentMonthStringDate       = date('Y-m') . '%';
    }

    public function getDataAnalysis()
    {
        $lastStartDate = new DateTime($this->startDate->format('Y-m-d H:i:s'));
        $lastStartDate->sub(new DateInterval('P1M'));

        $lastEndDate = new DateTime($this->endDate->format('Y-m-d H:i:s'));
        $lastEndDate->sub(new DateInterval('P1M'));

        //Tableau pour les graphes des Histogrammes de consommation
        $histogramConsoData = [];
        $histogramConsoData_ = [];
        $histogramConsoDate = [];
        $lastHistogramConsoData = [];
        $lastHistogramConsoData_ = [];
        $lastHistogramConsoDate = [];

        //Tableau pour les graphes des Histogrammes de Pic de puissance
        $histogramHighPowerData = [];
        $histogramHighPowerData_ = [];
        $histogramHighPowerDate = [];
        $lastHistogramHighPowerData = [];
        $lastHistogramHighPowerData_ = [];
        $lastHistogramHighPowerDate = [];

        // ========= Détermination de la longueur de la datetime =========
        $length = 10; //Si endDate > startDate => regoupement des données par jour de la fenêtre de date
        if ($this->endDate->format('Y-m-d') == $this->startDate->format('Y-m-d')) $length = 13; //Si endDate == startDate => regoupement des données par heure du jour choisi
        //dump('length = ' . $length);

        if ($this->endDate->format('Y-m-d') > $this->startDate->format('Y-m-d')) { //Sinon si la fenêtre de date n'est pas journalière
            // ========= Initialisation des tableaux dédiés aux graphes en fonction de la fenêtre de date ========= 
            $period = new DatePeriod(
                new DateTime($this->startDate->format('Y-m-d')),
                new DateInterval('P1D'),
                new DateTime($this->endDate->format('Y-m-d'))
            );

            foreach ($period as $key => $value) {
                //dump($value->format('Y-m-d'));
                //$date[]     = $value->format('Y-m-d');
                $histogramConsoDate[]     = $value->format('Y-m-d');
                $histogramConsoData_[$value->format('Y-m-d')]   = 0.0;

                $histogramHighPowerDate[]     = $value->format('Y-m-d');
                $histogramHighPowerData_[$value->format('Y-m-d')]   = 0.0;
            }

            //$date[]     = $this->endDate->format('Y-m-d');
            $histogramConsoDate[]     = $this->endDate->format('Y-m-d');
            $histogramConsoData_[$this->endDate->format('Y-m-d')]   = 0.0;

            $histogramHighPowerDate[]     = $this->endDate->format('Y-m-d');
            $histogramHighPowerData_[$this->endDate->format('Y-m-d')]   = 0.0;

            // ========= Initialisation des tableaux dédiés aux graphes en fonction de la fenêtre de date - 1 ========= 
            $period = new DatePeriod(
                new DateTime($lastStartDate->format('Y-m-d')),
                new DateInterval('P1D'),
                new DateTime($lastEndDate->format('Y-m-d'))
            );

            foreach ($period as $key => $value) {
                //dump($value->format('Y-m-d'));
                $lastHistogramConsoDate[]     = $value->format('Y-m-d');
                $lastHistogramConsoData_[$value->format('Y-m-d')]   = 0.0;

                $lastHistogramHighPowerDate[]     = $value->format('Y-m-d');
                $lastHistogramHighPowerData_[$value->format('Y-m-d')]   = 0.0;
            }

            $lastHistogramConsoDate[]     = $lastEndDate->format('Y-m-d');
            $lastHistogramConsoData_[$lastEndDate->format('Y-m-d')]   = 0.0;

            $lastHistogramHighPowerDate[]     = $lastEndDate->format('Y-m-d');
            $lastHistogramHighPowerData_[$lastEndDate->format('Y-m-d')]   = 0.0;
            //dump($histogramConsoDate);

        } else if ($this->endDate->format('Y-m-d') == $this->startDate->format('Y-m-d')) { //Sinon si la fenêtre de date est journalière
            for ($hour = 0; $hour < 24; $hour++) {
                $strHour_ = $hour < 10 ? '0' . $hour : strval($hour);
                $strHour = $this->startDate->format('Y-m-d') . ' ' . $strHour_ . ':00:00';
                $lastStrHour = $lastStartDate->format('Y-m-d') . ' ' . $strHour_ . ':00:00';
                $histogramConsoDate[]     = $strHour;
                $histogramConsoData_[$strHour]   = 0.0;
                $lastHistogramConsoDate[]     = $lastStrHour;
                $lastHistogramConsoData_[$lastStrHour]   = 0.0;

                $histogramHighPowerDate[]     = $strHour;
                $histogramHighPowerData_[$strHour]   = 0.0;
                $lastHistogramHighPowerDate[]     = $lastStrHour;
                $lastHistogramHighPowerData_[$lastStrHour]   = 0.0;
            }
        }
        // ========= Détermination de la consommation moyenne d'énergie active =========  
        $consoMoy = $this->getAverageConsumption($length, $this->startDate->format('Y-m-d H:i:s'), $this->endDate->format('Y-m-d H:i:s'));
        $lastConsoMoy = $this->getAverageConsumption($length, $lastStartDate->format('Y-m-d H:i:s'), $lastEndDate->format('Y-m-d H:i:s'));
        $consoMoyProgress = ($lastConsoMoy > 0) ? ($consoMoy - $lastConsoMoy) * 100 / $lastConsoMoy : 'INF';

        // ========= Détermination des jours les plus et mois consommateur =========  
        $consoQuery = $this->manager->createQuery("SELECT SUBSTRING(d.dateTime,1,:length_) AS jour, SUM(d.pmoy)*:time AS kWh
                                                FROM App\Entity\LoadEnergyData d
                                                JOIN d.smartMod sm
                                                WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='Load Meter' AND stm.levelZone=1)
                                                AND d.dateTime BETWEEN :startDate AND :endDate
                                                GROUP BY jour
                                                ORDER BY kWh DESC")
            ->setParameters(array(
                'length_'    => $length,
                'time'       => $this->loadSiteIntervalTime,
                'startDate'  => $this->startDate->format('Y-m-d H:i:s'),
                'endDate'    => $this->endDate->format('Y-m-d H:i:s'),
                'siteId'     => $this->site->getId(),
            ))
            ->getResult();

        $lastConsoQuery = $this->manager->createQuery("SELECT SUBSTRING(d.dateTime,1,:length_) AS jour, SUM(d.pmoy)*:time AS kWh
                                                FROM App\Entity\LoadEnergyData d
                                                JOIN d.smartMod sm
                                                WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='Load Meter' AND stm.levelZone=1)
                                                AND d.dateTime BETWEEN :lastStartDate AND :lastEndDate
                                                GROUP BY jour
                                                ORDER BY kWh DESC")
            ->setParameters(array(
                'time'       => $this->loadSiteIntervalTime,
                'lastStartDate'  => $lastStartDate->format('Y-m-d H:i:s'),
                'lastEndDate'    => $lastEndDate->format('Y-m-d H:i:s'),
                'siteId'         => $this->site->getId(),
                'length_'        => $length,
            ))
            ->getResult();

        $strPlusForteConso = "-";
        $PlusForteConsoProgress = "INF";
        $strPlusFaibleConso = "-";
        $PlusFaibleConsoProgress = "INF";
        $consoTotale = 0.0;
        $lastConsoTotale = 0.0;
        $consoTotaleProgress = "INF";
        $lowConso = [];
        $highConso = [];
        $lastLowConso = [];
        $lastHighConso = [];
        if (!empty($consoQuery)) {

            $lowConso      = end($consoQuery);
            $highConso     = reset($consoQuery);
            if ($this->endDate->format('Y-m-d') == $this->startDate->format('Y-m-d')) {
                $lowConsoDate  = new DateTime($lowConso['jour'] . ':00:00');
                $highConsoDate = new DateTime($highConso['jour'] . ':00:00');
                $strPlusForteConso  = $highConso != null ? number_format((float) $highConso['kWh'], 2, '.', ' ') . ' kWh @ ' . $highConsoDate->format('d M Y H') . 'h' : '-';
                $strPlusFaibleConso = $lowConso != null ? number_format((float) $lowConso['kWh'], 2, '.', ' ') . ' kWh @ ' . $lowConsoDate->format('d M Y H') . 'h' : '-';

                // ======== Récupération des données pour le tracé de l'histogramme de consommation ======== 
                foreach ($consoQuery as $data) {
                    $hour = new DateTime($data['jour'] . ':00:00');
                    $histogramConsoData_[$hour->format('Y-m-d H:i:s')] = floatval(number_format((float) $data['kWh'], 2, '.', ''));

                    $consoTotale += $data['kWh'];
                }

                // ======== Récupération des données pour le tracé de l'histogramme de consommation n - 1 ======== 
                foreach ($lastConsoQuery as $data) {
                    $hour = new DateTime($data['jour'] . ':00:00');
                    $lastHistogramConsoData_[$hour->format('Y-m-d H:i:s')] = floatval(number_format((float) $data['kWh'], 2, '.', ''));

                    $lastConsoTotale += $data['kWh'];
                }
            } else if ($this->endDate->format('Y-m-d') > $this->startDate->format('Y-m-d')) {
                $lowConsoDate  = new DateTime($lowConso['jour']);
                $highConsoDate = new DateTime($highConso['jour']);
                $strPlusForteConso  = $highConso != null ? number_format((float) $highConso['kWh'], 2, '.', ' ') . ' kWh @ ' . $highConsoDate->format('d M Y') : '-';
                $strPlusFaibleConso = $lowConso != null ? number_format((float) $lowConso['kWh'], 2, '.', ' ') . ' kWh @ ' . $lowConsoDate->format('d M Y') : '-';

                // ======== Récupération des données pour le tracé de l'histogramme de consommation ======== 
                foreach ($consoQuery as $data) {
                    $histogramConsoData_[$data['jour']] = floatval(number_format((float) $data['kWh'], 2, '.', ''));

                    $consoTotale += $data['kWh'];
                }

                // ======== Récupération des données pour le tracé de l'histogramme de consommation n - 1 ======== 
                foreach ($lastConsoQuery as $data) {
                    $lastHistogramConsoData_[$data['jour']] = floatval(number_format((float) $data['kWh'], 2, '.', ''));

                    $lastConsoTotale += $data['kWh'];
                }
            }
            //dump($lowConso);
            //dump($highConso);
            //number_format((float) $d['kW'], 2, '.', '')
            foreach ($histogramConsoData_ as $key => $value) {
                $histogramConsoData[] = $value;
            }
            //dump($histogramConsoData);
            if (!empty($lastConsoQuery)) {
                $lastLowConso      = end($lastConsoQuery);
                $lastHighConso     = reset($lastConsoQuery);
                //dump($lastLowConso);
                $PlusForteConsoProgress  = $lastHighConso != null ? (floatval($lastHighConso['kWh']) > 0 ? (floatval($highConso['kWh']) - floatval($lastHighConso['kWh'])) * 100 / floatval($lastHighConso['kWh']) : 'INF') : 'INF';
                $PlusFaibleConsoProgress = $lastLowConso != null ? (floatval($lastLowConso['kWh']) > 0 ? (floatval($lowConso['kWh']) - floatval($lastLowConso['kWh'])) * 100 / floatval($lastLowConso['kWh']) : 'INF') : 'INF';

                // // ======== Récupération des données pour le tracé de l'histogramme de consommation n - 1 ======== 
                // foreach ($lastConsoQuery as $data) {
                //     $lastHistogramConsoData_[$data['jour']] = floatval(number_format((float) $data['kWh'], 2, '.', ''));
                //     //$lastHistogramConsoDate[] = $data['jour'];

                //     $lastConsoTotale += $data['kWh'];
                // }
            }
            foreach ($lastHistogramConsoData_ as $key => $value) {
                $lastHistogramConsoData[] = $value;
            }
            //dump($lastHistogramConsoData);

            $consoTotaleProgress = ($lastConsoTotale > 0) ? ($consoTotale - $lastConsoTotale) * 100 / $lastConsoTotale : 'INF';
        }

        // ========= Détermination des Pic et Talon de puissance =========  
        $powerQuery = $this->manager->createQuery("SELECT DISTINCT d.dateTime AS jour, NULLIF(d.pmoy/:power_unit, 0) AS kW 
                                            FROM App\Entity\LoadEnergyData d
                                            JOIN d.smartMod sm
                                            WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='Load Meter' AND stm.levelZone=1)
                                            AND d.dateTime BETWEEN :startDate AND :endDate
                                            ORDER BY kW ASC")
            ->setParameters(array(
                'startDate'    => $this->startDate->format('Y-m-d H:i:s'),
                'endDate'      => $this->endDate->format('Y-m-d H:i:s'),
                'siteId'       => $this->site->getId(),
                'power_unit'   => $this->power_unit,
            ))
            ->getResult();

        $lastPowerQuery = $this->manager->createQuery("SELECT DISTINCT d.dateTime AS jour, NULLIF(d.pmoy/:power_unit, 0) AS kW 
                                            FROM App\Entity\LoadEnergyData d
                                            JOIN d.smartMod sm
                                            WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='Load Meter' AND stm.levelZone=1)
                                            AND d.dateTime BETWEEN :lastStartDate AND :lastEndDate
                                            ORDER BY kW ASC")
            ->setParameters(array(
                'lastStartDate'  => $lastStartDate->format('Y-m-d H:i:s'),
                'lastEndDate'    => $lastEndDate->format('Y-m-d H:i:s'),
                'siteId'         => $this->site->getId(),
                'power_unit'     => $this->power_unit,
            ))
            ->getResult();

        $strPic = '-';
        $PicProgress = 'INF';
        $strTalon = '-';
        $TalonProgress = 'INF';
        if (!empty($powerQuery)) {

            //$lowPower = reset($powerQuery);
            $highPower = end($powerQuery);
            $lowPower = null;
            $array = $powerQuery;
            $min = 0;
            //Recherche de la puissance minimale non nulle
            foreach ($array as $v) {
                if ($v['kW'] > $min) {
                    $lowPower = $v;
                    //dump($v);
                    break;
                }
            }

            $powerUnitStr = '';
            // $powerUnitStr = $this->power_unit === 1000 ? 'MW' : 'kW';
            switch ($this->power_unit) {
                case 1000:
                    $powerUnitStr = 'MW';
                    break;

                default:
                    $powerUnitStr = 'kW';
                    break;
            }

            //dump($lowPower);
            // $strTalon = $lowPower != null ? number_format((float) ($lowPower['kW']), 2, '.', ' ') . ' W @ ' . $lowPower['jour']->format('d M Y H:i:s') : '-';
            $strTalon = $lowPower != null ? number_format((float) ($lowPower['kW']), 2, '.', ' ') . ' ' . $powerUnitStr . ' @ ' . $lowPower['jour']->format('d M Y H:i:s') : '-';
            $strPic   = $highPower != null ? number_format((float) ($highPower['kW']), 2, '.', ' ') . ' ' . $powerUnitStr . ' @ ' . $highPower['jour']->format('d M Y H:i:s') : '-';

            if (!empty($lastPowerQuery)) {

                //$lowPower = reset($lastPowerQuery);
                $lastHighPower = end($lastPowerQuery);
                $lastLowPower = null;
                $array = $lastPowerQuery;
                $min = 0;
                //Recherche de la puissance minimale non nulle
                foreach ($array as $v) {
                    if ($v['kW'] > $min) {
                        $lastLowPower = $v;
                        break;
                    }
                }

                $PicProgress   = $lastHighPower != null ? (floatval($lastHighPower['kW']) > 0 ? (floatval($highPower['kW']) - floatval($lastHighPower['kW'])) * 100 / floatval($lastHighPower['kW']) : 'INF') : 'INF';
                $TalonProgress = $lastLowPower != null ? (floatval($lastLowPower['kW']) > 0 ? (floatval($lowPower['kW']) - floatval($lastLowPower['kW'])) * 100 / floatval($lastLowPower['kW']) : 'INF') : 'INF';
            }
        }

        // ========= Détermination des consommations d'énergie active par tranche horaire =========  
        $consokWhPerHoursRangeQuery = $this->manager->createQuery("SELECT SUM(CASE 
                                                                    WHEN SUBSTRING(d.dateTime, 12) BETWEEN '00:00:00' AND '07:59:59' THEN d.pmoy
                                                                    ELSE 0
                                                                END)*:time AS Tranche00_08,
                                                                SUM(CASE 
                                                                    WHEN SUBSTRING(d.dateTime, 12) BETWEEN '08:00:00' AND '17:59:59' THEN d.pmoy
                                                                    ELSE 0
                                                                END)*:time AS Tranche08_18, 
                                                                SUM(CASE 
                                                                    WHEN SUBSTRING(d.dateTime, 12) BETWEEN '18:00:00' AND '23:59:59' THEN d.pmoy
                                                                    ELSE 0
                                                                END)*:time AS Tranche18_00 
                                                                FROM App\Entity\LoadEnergyData d
                                                                JOIN d.smartMod sm
                                                                WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='Load Meter' AND stm.levelZone=1)
                                                                AND d.dateTime BETWEEN :startDate AND :endDate
                                                                ")
            ->setParameters(array(
                'time'       => $this->loadSiteIntervalTime,
                'startDate'  => $this->startDate->format('Y-m-d H:i:s'),
                'endDate'    => $this->endDate->format('Y-m-d H:i:s'),
                'siteId'     => $this->site->getId()
            ))
            ->getResult();

        $lastMonthConsokWhPerHoursRangeQuery = $this->manager->createQuery("SELECT SUM(CASE 
                                                                           WHEN SUBSTRING(d.dateTime, 12) BETWEEN '00:00:00' AND '07:59:59' THEN d.pmoy
                                                                           ELSE 0
                                                                END)*:time AS Tranche00_08,
                                                                SUM(CASE 
                                                                    WHEN SUBSTRING(d.dateTime, 12) BETWEEN '08:00:00' AND '17:59:59' THEN d.pmoy
                                                                    ELSE 0
                                                                END)*:time AS Tranche08_18, 
                                                                SUM(CASE 
                                                                    WHEN SUBSTRING(d.dateTime, 12) BETWEEN '18:00:00' AND '23:59:59' THEN d.pmoy
                                                                    ELSE 0
                                                                END)*:time AS Tranche18_00 
                                                                FROM App\Entity\LoadEnergyData d
                                                                JOIN d.smartMod sm
                                                                WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='Load Meter' AND stm.levelZone=1)
                                                                AND d.dateTime BETWEEN :lastStartDate AND :lastEndDate
                                                                ")
            ->setParameters(array(
                'time'           => $this->loadSiteIntervalTime,
                'lastStartDate'  => $lastStartDate->format('Y-m-d H:i:s'),
                'lastEndDate'    => $lastEndDate->format('Y-m-d H:i:s'),
                'siteId'         => $this->site->getId()
            ))
            ->getResult();

        $conso00_08kWh = $consokWhPerHoursRangeQuery[0]['Tranche00_08'] ?? 0;
        $conso08_18kWh = $consokWhPerHoursRangeQuery[0]['Tranche08_18'] ?? 0;
        $conso18_00kWh = $consokWhPerHoursRangeQuery[0]['Tranche18_00'] ?? 0;

        $lastConso00_08kWh = $lastMonthConsokWhPerHoursRangeQuery[0]['Tranche00_08'] ?? 0;
        $lastConso08_18kWh = $lastMonthConsokWhPerHoursRangeQuery[0]['Tranche08_18'] ?? 0;
        $lastConso18_00kWh = $lastMonthConsokWhPerHoursRangeQuery[0]['Tranche18_00'] ?? 0;

        $conso00_08kWhProgress = floatval($lastConso00_08kWh) > 0 ? (floatval($conso00_08kWh) - floatval($lastConso00_08kWh)) * 100 / floatval($lastConso00_08kWh) : 'INF';
        $conso08_18kWhProgress = floatval($lastConso08_18kWh) > 0 ? (floatval($conso08_18kWh) - floatval($lastConso08_18kWh)) * 100 / floatval($lastConso08_18kWh) : 'INF';
        $conso18_00kWhProgress = floatval($lastConso18_00kWh) > 0 ? (floatval($conso18_00kWh) - floatval($lastConso18_00kWh)) * 100 / floatval($lastConso18_00kWh) : 'INF';

        $consoVariation = $this->getVariation($length, $this->startDate, $this->endDate);
        $lastConsoVariation = $this->getVariation($length, $lastStartDate, $lastEndDate);
        $consoVariationProgress = ($lastConsoVariation > 0) ? ($consoVariation - $lastConsoVariation) * 100 / $lastConsoVariation : 'INF';

        // ========= Récupération des données pour le tracé des Histogrammes de Pic de Puissance =========  
        $histoHighPowerQuery = $this->manager->createQuery("SELECT SUBSTRING(d.dateTime,1,:length_) AS jour, MAX(d.pmoy)/:power_unit AS kW
                                                FROM App\Entity\LoadEnergyData d
                                                JOIN d.smartMod sm
                                                WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='Load Meter' AND stm.levelZone=1)
                                                AND d.dateTime BETWEEN :startDate AND :endDate
                                                GROUP BY jour
                                                ORDER BY jour ASC")
            ->setParameters(array(
                'length_'    => $length,
                'startDate'  => $this->startDate->format('Y-m-d H:i:s'),
                'endDate'    => $this->endDate->format('Y-m-d H:i:s'),
                'siteId'     => $this->site->getId(),
                'power_unit' => $this->power_unit,
            ))
            ->getResult();

        $lastHistoHighPowerQuery = $this->manager->createQuery("SELECT SUBSTRING(d.dateTime,1,:length_) AS jour, MAX(d.pmoy)/:power_unit AS kW
                                                FROM App\Entity\LoadEnergyData d
                                                JOIN d.smartMod sm
                                                WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='Load Meter' AND stm.levelZone=1)
                                                AND d.dateTime BETWEEN :lastStartDate AND :lastEndDate
                                                GROUP BY jour
                                                ORDER BY jour ASC")
            ->setParameters(array(
                'lastStartDate'  => $lastStartDate->format('Y-m-d H:i:s'),
                'lastEndDate'    => $lastEndDate->format('Y-m-d H:i:s'),
                'siteId'         => $this->site->getId(),
                'length_'        => $length,
                'power_unit'     => $this->power_unit,
            ))
            ->getResult();
        if ($this->endDate->format('Y-m-d') == $this->startDate->format('Y-m-d')) {
            foreach ($histoHighPowerQuery as $data) {
                $hour = new DateTime($data['jour'] . ':00:00');
                $histogramHighPowerData_[$hour->format('Y-m-d H:i:s')] = floatval(number_format((float) $data['kW'], 2, '.', ''));
            }

            // ======== Récupération des données pour le tracé de l'histogramme de de Pic de Puissance n - 1 ======== 
            foreach ($lastHistoHighPowerQuery as $data) {
                $hour = new DateTime($data['jour'] . ':00:00');
                $lastHistogramHighPowerData_[$hour->format('Y-m-d H:i:s')] = floatval(number_format((float) $data['kW'], 2, '.', ''));
            }
        } else if ($this->endDate->format('Y-m-d') > $this->startDate->format('Y-m-d')) {
            foreach ($histoHighPowerQuery as $data) {
                $histogramHighPowerData_[$data['jour']] = floatval(number_format((float) $data['kW'], 2, '.', ''));
            }

            // ======== Récupération des données pour le tracé de l'histogramme de de Pic de Puissance n - 1 ======== 
            foreach ($lastHistoHighPowerQuery as $data) {
                $lastHistogramHighPowerData_[$data['jour']] = floatval(number_format((float) $data['kW'], 2, '.', ''));
            }
        }
        $psous = $this->site->getPowerSubscribed();
        $Psous = [];
        foreach ($histogramHighPowerData_ as $key => $value) {
            $histogramHighPowerData[] = $value;
            $Psous[] = $psous;
        }
        //dump($lastHistogramHighPowerData_);
        foreach ($lastHistogramHighPowerData_ as $key => $value) {
            $lastHistogramHighPowerData[] = $value;
        }

        // ========= Récupération des données pour le tracé des Profils de puissance des jour le plus et moins consommateur =========  
        $highDayConsoPowerProfilDate      = [];
        $highDayConsoPowerProfilData      = [];
        $highDayConsoPowerProfilPsousData = [];
        $lastHighDayConsoPowerProfilData_ = [];
        if (!empty($highConso)) {
            $powerProfilHighDayConsoQuery = $this->manager->createQuery("SELECT d.dateTime AS jour, d.pmoy/:power_unit AS kW
                                                    FROM App\Entity\LoadEnergyData d
                                                    JOIN d.smartMod sm
                                                    WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='Load Meter' AND stm.levelZone=1)
                                                    AND d.dateTime LIKE :highDayConsoDate
                                                    ORDER BY jour ASC")
                ->setParameters(array(
                    'highDayConsoDate'  => $highConso['jour'] . '%',
                    'siteId'            => $this->site->getId(),
                    'power_unit'        => $this->power_unit,
                ))
                ->getResult();

            foreach ($powerProfilHighDayConsoQuery as $data) {
                $highDayConsoPowerProfilDate[]      = $data['jour']->format('Y-m-d H:i:s');
                $highDayConsoPowerProfilData[]      = floatval(number_format((float) $data['kW'], 2, '.', ''));
                $highDayConsoPowerProfilPsousData[] = $psous;

                if (!empty($lastHighConso)) {
                    if ($this->endDate->format('Y-m-d') > $this->startDate->format('Y-m-d')) { //Sinon si la fenêtre de date n'est pas journalière
                        $lastDate = new DateTime($lastHighConso['jour'] . ' ' . $data['jour']->format('H:i:s'));
                        // $lastDate->sub(new DateInterval('P1M'));
                        $lastHighDayConsoPowerProfilData_[$lastDate->format('Y-m-d H:i:s')] = 0.0;
                    } else if ($this->endDate->format('Y-m-d') == $this->startDate->format('Y-m-d')) { //Sinon si la fenêtre de date est journalière
                        $lastDate = new DateTime($lastHighConso['jour'] . $data['jour']->format(':i:s'));
                        // $lastDate->sub(new DateInterval('P1M'));
                        $lastHighDayConsoPowerProfilData_[$lastDate->format('Y-m-d H:i:s')] = 0.0;
                    }
                }
            }
        }
        $lastHighDayConsoPowerProfilData = [];
        // $lastHighDayConsoPowerProfilDate = [];
        // $lastHighDayConsoPowerProfilPsousData = [];
        if (!empty($lastHighConso)) {
            $lastPowerProfilHighDayConsoQuery = $this->manager->createQuery("SELECT d.dateTime AS jour, d.pmoy/:power_unit AS kW
                                                    FROM App\Entity\LoadEnergyData d
                                                    JOIN d.smartMod sm
                                                    WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='Load Meter' AND stm.levelZone=1)
                                                    AND d.dateTime LIKE :lastHighDayDate
                                                    ORDER BY jour ASC")
                ->setParameters(array(
                    'lastHighDayDate' => $lastHighConso['jour'] . '%',
                    'siteId'          => $this->site->getId(),
                    'power_unit'      => $this->power_unit,
                ))
                ->getResult();

            foreach ($lastPowerProfilHighDayConsoQuery as $data) {
                if (array_key_exists($data['jour']->format('Y-m-d H:i:s'), $lastHighDayConsoPowerProfilData_)) {
                    $lastHighDayConsoPowerProfilData_[$data['jour']->format('Y-m-d H:i:s')] = floatval(number_format((float) $data['kW'], 2, '.', ''));
                }
            }
            foreach ($lastHighDayConsoPowerProfilData_ as $key => $value) {
                $lastHighDayConsoPowerProfilData[] = $value;
            }
        }

        $lowDayConsoPowerProfilDate      = [];
        $lowDayConsoPowerProfilData      = [];
        $lowDayConsoPowerProfilPsousData = [];
        $lastLowDayConsoPowerProfilData_ = [];
        if (!empty($lowConso)) {
            $powerProfilLowDayConsoQuery = $this->manager->createQuery("SELECT d.dateTime AS jour, d.pmoy/:power_unit AS kW
                                                    FROM App\Entity\LoadEnergyData d
                                                    JOIN d.smartMod sm
                                                    WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='Load Meter' AND stm.levelZone=1)
                                                    AND d.dateTime LIKE :lowDayConsoDate
                                                    ORDER BY jour ASC")
                ->setParameters(array(
                    'lowDayConsoDate'   => $lowConso['jour'] . '%',
                    'siteId'            => $this->site->getId(),
                    'power_unit'        => $this->power_unit,
                ))
                ->getResult();

            foreach ($powerProfilLowDayConsoQuery as $data) {
                $lowDayConsoPowerProfilDate[]      = $data['jour']->format('Y-m-d H:i:s');
                $lowDayConsoPowerProfilData[]      = floatval(number_format((float) $data['kW'], 2, '.', ''));
                $lowDayConsoPowerProfilPsousData[] = $psous;

                if (!empty($lastLowConso)) {
                    if ($this->endDate->format('Y-m-d') > $this->startDate->format('Y-m-d')) { //Sinon si la fenêtre de date n'est pas journalière
                        $lastDate_ = new DateTime($lastLowConso['jour'] . ' ' . $data['jour']->format('H:i:s'));
                        //$lastDate_->sub(new DateInterval('P1M'));
                        $lastLowDayConsoPowerProfilData_[$lastDate_->format('Y-m-d H:i:s')] = 0.0;
                    } else if ($this->endDate->format('Y-m-d') == $this->startDate->format('Y-m-d')) { //Sinon si la fenêtre de date est journalière
                        $lastDate_ = new DateTime($lastLowConso['jour'] . $data['jour']->format(':i:s'));
                        //$lastDate_->sub(new DateInterval('P1M'));
                        $lastLowDayConsoPowerProfilData_[$lastDate_->format('Y-m-d H:i:s')] = 0.0;
                    }
                }
            }
        }

        $lastLowDayConsoPowerProfilData = [];
        // $lastLowDayConsoPowerProfilDate = [];
        // $lastLowDayConsoPowerProfilPsousData = [];
        if (!empty($lastLowConso)) {
            $lastPowerProfilLowDayConsoQuery = $this->manager->createQuery("SELECT d.dateTime AS jour, d.pmoy/:power_unit AS kW
                                                    FROM App\Entity\LoadEnergyData d
                                                    JOIN d.smartMod sm
                                                    WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='Load Meter' AND stm.levelZone=1)
                                                    AND d.dateTime LIKE :lastLowDayDate
                                                    ORDER BY jour ASC")
                ->setParameters(array(
                    'lastLowDayDate' => $lastLowConso['jour'] . '%',
                    'siteId'         => $this->site->getId(),
                    'power_unit'     => $this->power_unit,
                ))
                ->getResult();

            foreach ($lastPowerProfilLowDayConsoQuery as $data) {
                $lastLowDayConsoPowerProfilData_[$data['jour']->format('Y-m-d H:i:s')] = floatval(number_format((float) $data['kW'], 2, '.', ''));
                if (array_key_exists($data['jour']->format('Y-m-d H:i:s'), $lastLowDayConsoPowerProfilData_)) {
                }
            }
            //dump($lastLowDayConsoPowerProfilData_);
            foreach ($lastLowDayConsoPowerProfilData_ as $key => $value) {
                $lastLowDayConsoPowerProfilData[] = $value;
            }
        }

        // ========= Récupération des données pour le tracé de la Courbe de charge =========  
        $loadChartDataQuery = $this->manager->createQuery("SELECT d.dateTime AS jour, d.pmoy/:power_unit AS kW, d.pmax/:power_unit AS Pmax
                                                FROM App\Entity\LoadEnergyData d
                                                JOIN d.smartMod sm
                                                WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='Load Meter' AND stm.levelZone=1)
                                                AND d.dateTime BETWEEN :startDate AND :endDate
                                                ORDER BY jour ASC")
            ->setParameters(array(
                'startDate'    => $this->startDate->format('Y-m-d H:i:s'),
                'endDate'      => $this->endDate->format('Y-m-d H:i:s'),
                'siteId'       => $this->site->getId(),
                'power_unit'   => $this->power_unit,
            ))
            //->setMaxResults(2000)
            ->getResult();
        $loadChartDataDate      = [];
        $loadChartDataPmoy      = [];
        $loadChartDataPsous     = [];
        $loadChartDataPmax      = [];
        foreach ($loadChartDataQuery as $data) {
            $loadChartDataDate[]      = $data['jour']->format('Y-m-d H:i:s');
            $loadChartDataPmoy[]      = floatval(number_format((float) $data['kW'], 2, '.', ''));
            $loadChartDataPmax[]      = floatval(number_format((float) $data['Pmax'], 2, '.', ''));
            $loadChartDataPsous[]     = $psous;
        }

        // ========= Récupération des données pour le tracé du Monotone de Puissance (Puissance ordonnée par ordre décroissant) et le Calcul de données Statistiques =========  
        $monotonePowerDataQuery = $this->manager->createQuery("SELECT d.pmax/:power_unit AS Pmax
                                                FROM App\Entity\LoadEnergyData d
                                                JOIN d.smartMod sm
                                                WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='Load Meter' AND stm.levelZone=1)
                                                AND d.dateTime BETWEEN :startDate AND :endDate
                                                ORDER BY Pmax DESC")
            ->setParameters(array(
                'startDate'    => $this->startDate->format('Y-m-d H:i:s'),
                'endDate'      => $this->endDate->format('Y-m-d H:i:s'),
                'siteId'       => $this->site->getId(),
                'power_unit'   => $this->power_unit,
            ))
            ->setMaxResults(2000)
            ->getResult();
        //dump($monotonePowerDataQuery);
        $monotonePowerOrder     = [];
        $monotonePowerPmax      = [];
        $order = 0;
        foreach ($monotonePowerDataQuery as $data) {
            $monotonePowerOrder[]     = $order++;
            $monotonePowerPmax[]      = floatval(number_format((float) $data['Pmax'], 2, '.', ''));
        }

        $meanPower   = $this->mmmrv($monotonePowerPmax, 'mean');
        $medianPower = $this->mmmrv($monotonePowerPmax, 'median');
        $n = count($monotonePowerPmax);
        $maxPower    = $n > 0 ? $monotonePowerPmax[0] : 0;
        $minPower    = $n > 0 ? $monotonePowerPmax[$n - 1] : 0;
        $nbDepassement = 0;
        $prang10 = 0.0;
        foreach ($monotonePowerPmax as $index => $value) {
            if ($index === 9) $prang10 = $value;
            if ($value > $psous) $nbDepassement++;
        }

        $lastMonotonePowerDataQuery = $this->manager->createQuery("SELECT d.pmax/:power_unit AS Pmax
                                                FROM App\Entity\LoadEnergyData d
                                                JOIN d.smartMod sm
                                                WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='Load Meter' AND stm.levelZone=1)
                                                AND d.dateTime BETWEEN :startDate AND :endDate
                                                ORDER BY Pmax DESC")
            ->setParameters(array(
                'startDate'    => $lastStartDate->format('Y-m-d H:i:s'),
                'endDate'      => $lastEndDate->format('Y-m-d H:i:s'),
                'siteId'       => $this->site->getId(),
                'power_unit'   => $this->power_unit,
            ))
            ->setMaxResults(2000)
            ->getResult();
        //dump($lastMonotonePowerDataQuery);
        //$lastMonotonePowerOrder     = [];
        $lastMonotonePowerPmax      = [];
        //$lastOrder = 0;
        foreach ($lastMonotonePowerDataQuery as $data) {
            //$lastMonotonePowerOrder[]     = $lastOrder++;
            $lastMonotonePowerPmax[]      = floatval(number_format((float) $data['Pmax'], 2, '.', ''));
        }

        $last_meanPower   = $this->mmmrv($lastMonotonePowerPmax, 'mean');
        // dump('$meanPower = ' . $meanPower . ', $last_meanPower = ' . $last_meanPower);
        $last_medianPower = $this->mmmrv($lastMonotonePowerPmax, 'median');
        // dump('$medianPower = ' . $medianPower . ', $last_medianPower = ' . $last_medianPower);
        $n = count($lastMonotonePowerPmax);
        $last_maxPower    = $n > 0 ? $lastMonotonePowerPmax[0] : 0;
        // dump('$maxPower = ' . $maxPower . ', $last_maxPower = ' . $last_maxPower);
        $last_minPower    = $n > 0 ? $lastMonotonePowerPmax[$n - 1] : 0;
        // dump('$minPower = ' . $minPower . ', $last_minPower = ' . $last_minPower);
        $last_nbDepassement = 0;
        $last_prang10 = 0.0;
        foreach ($lastMonotonePowerPmax as $index => $value) {
            if ($index === 9) $last_prang10 = $value;
            if ($value > $psous) $last_nbDepassement++;
        }
        // dump('$prang10 = ' . $prang10 . ', $last_prang10 = ' . $last_prang10);
        // dump('$nbDepassement = ' . $nbDepassement . ', $last_nbDepassement = ' . $last_nbDepassement);

        $meanPowerProgress      = ($last_meanPower > 0) ? ($meanPower - $last_meanPower) * 100 / $last_meanPower : 'INF';
        $medianPowerProgress    = ($last_medianPower > 0) ? ($medianPower - $last_medianPower) / $last_medianPower : 'INF';
        $maxPowerProgress       = ($last_maxPower > 0) ? ($maxPower - $last_maxPower) * 100 / $last_maxPower : 'INF';
        $minPowerProgress       = ($last_minPower > 0) ? ($minPower - $last_minPower) * 100 / $last_minPower : 'INF';
        $nbDepassementProgress  = ($last_nbDepassement > 0) ? ($nbDepassement - $last_nbDepassement) / $last_nbDepassement : 'INF';
        $prang10Progress        = ($last_prang10 > 0) ? ($prang10 - $last_prang10) * 100 / $last_prang10 : 'INF';

        // ========= Récupération des données pour le tracé du Minima de Cosfi et le Calcul de données Statistiques =========  
        $minimaCosfiDataQuery = $this->manager->createQuery("SELECT d.dateTime AS jour, d.cosfimin AS Cosfi
                                                FROM App\Entity\LoadEnergyData d
                                                JOIN d.smartMod sm
                                                WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='Load Meter' AND stm.levelZone=1)
                                                AND d.dateTime BETWEEN :startDate AND :endDate
                                                AND d.cosfimin >= 0.01
                                                ORDER BY jour ASC")
            ->setParameters(array(
                'startDate'    => $this->startDate->format('Y-m-d H:i:s'),
                'endDate'      => $this->endDate->format('Y-m-d H:i:s'),
                'siteId'       => $this->site->getId(),
            ))
            ->setMaxResults(2000)
            ->getResult();
        // dump($minimaCosfiDataQuery);
        $minimaCosfiDate  = [];
        $minimaCosfiData  = [];
        foreach ($minimaCosfiDataQuery as $data) {
            $minimaCosfiDate[]      = $data['jour']->format('Y-m-d H:i:s');
            $minimaCosfiData[]      = floatval(number_format((float) $data['Cosfi'], 2, '.', ''));

        }

        $minimaCosfiData_ = $minimaCosfiData;
        $meanCosfi   = $this->mmmrv($minimaCosfiData_, 'mean');
        $minimaCosfiData_ = $minimaCosfiData;
        $medianCosfi = $this->mmmrv($minimaCosfiData_, 'median');
        $n = count($minimaCosfiData);
        $maxCosfi    = $n > 0 ? max($minimaCosfiData) : 0.0;
        $minCosfi    = $n > 0 ? min($minimaCosfiData) : 0.0;
        $nbInsuffisance = 0;

        foreach ($minimaCosfiData as $index => $value) {
            //dump($value);
            if ($value < 0.86) $nbInsuffisance++;
        }

        $lastMinimaCosfiDataQuery = $this->manager->createQuery("SELECT d.dateTime AS jour, d.cosfimin AS Cosfi
                                                FROM App\Entity\LoadEnergyData d
                                                JOIN d.smartMod sm
                                                WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='Load Meter' AND stm.levelZone=1)
                                                AND d.dateTime BETWEEN :startDate AND :endDate
                                                AND d.cosfimin >= 0.01
                                                ORDER BY jour ASC")
            ->setParameters(array(
                'startDate'    => $lastStartDate->format('Y-m-d H:i:s'),
                'endDate'      => $lastEndDate->format('Y-m-d H:i:s'),
                'siteId'       => $this->site->getId(),
            ))
            ->setMaxResults(2000)
            ->getResult();
        //dump($lastMinimaCosfiDataQuery);
        //$lastMinimaCosfiDate  = [];
        $lastMinimaCosfiData  = [];
        foreach ($lastMinimaCosfiDataQuery as $data) {
            //$lastMinimaCosfiDate[]      = $data['jour']->format('Y-m-d H:i:s');
            $lastMinimaCosfiData[]      = floatval(number_format((float) $data['Cosfi'], 2, '.', ''));
        }

        $lastMinimaCosfiData_ = $lastMinimaCosfiData;
        $last_meanCosfi   = $this->mmmrv($lastMinimaCosfiData_, 'mean');
        $lastMinimaCosfiData_ = $lastMinimaCosfiData;
        $last_medianCosfi = $this->mmmrv($lastMinimaCosfiData_, 'median');
        $n = count($lastMinimaCosfiData);
        $last_maxCosfi    = $n > 0 ? max($lastMinimaCosfiData) : 0.0;
        $last_minCosfi    = $n > 0 ? min($lastMinimaCosfiData) : 0.0;
        $last_nbInsuffisance = 0;

        foreach ($lastMinimaCosfiData as $index => $value) {
            if ($value < 0.86) $last_nbInsuffisance++;
        }

        $meanCosfiProgress      = ($last_meanCosfi > 0) ? ($meanCosfi - $last_meanCosfi) * 100 / $last_meanCosfi : 'INF';
        $medianCosfiProgress    = ($last_medianCosfi > 0) ? ($medianCosfi - $last_medianCosfi) * 100 / $last_medianCosfi : 'INF';
        $maxCosfiProgress       = ($last_maxCosfi > 0) ? ($maxCosfi - $last_maxCosfi) * 100 / $last_maxCosfi : 'INF';
        $minCosfiProgress       = ($last_minCosfi > 0) ? ($minCosfi - $last_minCosfi) * 100 / $last_minCosfi : 'INF';
        $nbInsuffisanceProgress = ($last_nbInsuffisance > 0) ? ($nbInsuffisance - $last_nbInsuffisance) * 100 / $last_nbInsuffisance : 'INF';

        $CosfiDataQuery = $this->manager->createQuery("SELECT SUM(d.pmoy)/SQRT( (SUM(d.pmoy)*SUM(d.pmoy)) + (SUM(d.qmoy)*SUM(d.qmoy)) ) AS PF
                                                FROM App\Entity\LoadEnergyData d
                                                JOIN d.smartMod sm
                                                WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='Load Meter' AND stm.levelZone=1)
                                                AND d.dateTime BETWEEN :startDate AND :endDate
                                                ")
            ->setParameters(array(
                'startDate'    => $this->startDate->format('Y-m-d H:i:s'),
                'endDate'      => $this->endDate->format('Y-m-d H:i:s'),
                'siteId'       => $this->site->getId(),
            ))
            //->setMaxResults(2000)
            ->getResult();
        //dump($minimaCosfiDataQuery);
        $cosfiEnergy = 0.0;
        foreach ($CosfiDataQuery as $data) {
            $cosfiEnergy      = floatval(number_format((float) $data['PF'], 2, '.', ''));
        }

        $lastCosfiDataQuery = $this->manager->createQuery("SELECT SUM(d.pmoy)/SQRT( (SUM(d.pmoy)*SUM(d.pmoy)) + (SUM(d.qmoy)*SUM(d.qmoy)) ) AS PF
                                                FROM App\Entity\LoadEnergyData d
                                                JOIN d.smartMod sm
                                                WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='Load Meter' AND stm.levelZone=1)
                                                AND d.dateTime BETWEEN :startDate AND :endDate
                                                ")
            ->setParameters(array(
                'startDate'    => $this->startDate->format('Y-m-d H:i:s'),
                'endDate'      => $this->endDate->format('Y-m-d H:i:s'),
                'siteId'       => $this->site->getId(),
            ))
            //->setMaxResults(2000)
            ->getResult();
        //dump($minimaCosfiDataQuery);
        $last_cosfiEnergy = 0.0;
        foreach ($lastCosfiDataQuery as $data) {
            $last_cosfiEnergy      = floatval(number_format((float) $data['PF'], 2, '.', ''));
        }
        $cosfiEnergyProgress   = ($last_cosfiEnergy > 0) ? ($cosfiEnergy - $last_cosfiEnergy) * 100 / $last_cosfiEnergy : 'INF';

        $recapProd = [];
        $amountBill = 0.0;
        $mix = [];
        $trhData = [];
        $powerProfilSupply = [];

        // ========= Dispersion des Consommations et des Pic de Puissance =========  
        // Dispersion des Consommations
        /*$dowConsoDataQuery = $this->manager->createQuery("SELECT CASE 
                                                            WHEN DAYOFWEEK(d.dateTime) =  1 THEN d.pmoy*:time
                                                            ELSE 'no'
                                                END AS Dim,
                                                CASE 
                                                    WHEN DAYOFWEEK(d.dateTime) =  2 THEN d.pmoy*:time
                                                    ELSE 'no'
                                                END AS Lun,
                                                CASE 
                                                    WHEN DAYOFWEEK(d.dateTime) =  3 THEN d.pmoy*:time
                                                    ELSE 'no'
                                                END AS Mar,
                                                CASE 
                                                    WHEN DAYOFWEEK(d.dateTime) =  4 THEN d.pmoy*:time
                                                    ELSE 'no'
                                                END AS Mer,
                                                CASE 
                                                    WHEN DAYOFWEEK(d.dateTime) =  5 THEN d.pmoy*:time
                                                    ELSE 'no'
                                                END AS Jeu,
                                                CASE 
                                                    WHEN DAYOFWEEK(d.dateTime) =  6 THEN d.pmoy*:time
                                                    ELSE 'no'
                                                END AS Ven,
                                                CASE 
                                                    WHEN DAYOFWEEK(d.dateTime) =  7 THEN d.pmoy*:time
                                                    ELSE 'no'
                                                END AS Sam
                                                FROM App\Entity\LoadEnergyData d
                                                JOIN d.smartMod sm
                                                WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='Load Meter' AND stm.levelZone=1)
                                                AND d.dateTime BETWEEN :startDate AND :endDate
                                                ")
            ->setParameters(array(
                'time'       => $this->loadSiteIntervalTime,
                'startDate'    => $this->startDate->format('Y-m-d H:i:s'),
                'endDate'      => $this->endDate->format('Y-m-d H:i:s'),
                'siteId'       => $this->site->getId(),
            ))
            //->setMaxResults(2000)
            ->getResult();*/

        $dowConsoDataQuery = $this->manager->createQuery("SELECT SUBSTRING(d.dateTime,1,10) AS jour, SUM(d.pmoy)*:time AS EA
                                                FROM App\Entity\LoadEnergyData d
                                                JOIN d.smartMod sm
                                                WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='Load Meter' AND stm.levelZone=1)
                                                AND d.dateTime BETWEEN :startDate AND :endDate
                                                GROUP BY jour
                                                ORDER BY jour ASC")
            ->setParameters(array(
                'time'       => $this->loadSiteIntervalTime,
                'startDate'    => $this->startDate->format('Y-m-d H:i:s'),
                'endDate'      => $this->endDate->format('Y-m-d H:i:s'),
                'siteId'       => $this->site->getId(),
            ))
            //->setMaxResults(2000)
            ->getResult();
        // dump($dowConsoDataQuery);

        $dowConsoData = [
            'Dimanche'   => [],
            'Lundi'   => [],
            'Mardi'   => [],
            'Mercredi'   => [],
            'Jeudi'   => [],
            'Vendredi'   => [],
            'Samedi'   => [],
        ];
        foreach ($dowConsoDataQuery as $data) {
            switch (date('w', strtotime($data['jour']))) {
                case 0:
                    $dowConsoData['Dimanche'][] = $data['EA'];
                    break;
                case 1:
                    $dowConsoData['Lundi'][] = $data['EA'];
                    break;
                case 2:
                    $dowConsoData['Mardi'][] = $data['EA'];
                    break;
                case 3:
                    $dowConsoData['Mercredi'][] = $data['EA'];
                    break;
                case 4:
                    $dowConsoData['Jeudi'][] = $data['EA'];
                    break;
                case 5:
                    $dowConsoData['Vendredi'][] = $data['EA'];
                    break;
                case 6:
                    $dowConsoData['Samedi'][] = $data['EA'];
                    break;
                
                default:
                    break;
            }
            /*$dowConsoData['Dimanche'][] = $data['Dim'];
            $dowConsoData['Lundi'][] = $data['Lun'];
            $dowConsoData['Mardi'][] = $data['Mar'];
            $dowConsoData['Mercredi'][] = $data['Mer'];
            $dowConsoData['Jeudi'][] = $data['Jeu'];
            $dowConsoData['Vendredi'][] = $data['Ven'];
            $dowConsoData['Samedi'][] = $data['Sam'];*/
            
        }

        $Q_conso = [
            'Lundi'      => [],
            'Mardi'      => [],
            'Mercredi'   => [],
            'Jeudi'      => [],
            'Vendredi'   => [],
            'Samedi'     => [],
            'Dimanche'   => [],
            'chart'      => []
        ];
        foreach ($dowConsoData as $key => $value) {
            $dowConsoData[$key] = array_filter($dowConsoData[$key], fn ($val) => $val !== 'no');
            if (count($dowConsoData[$key]) > 0) {
                sort($dowConsoData[$key]);
                $n = count($dowConsoData[$key]) - 1;
                $min = min($dowConsoData[$key]);
                $min = floatval(number_format((float)$min, 2, '.', ''));
                // $q1 = floor(($n + 3) / 4) + 1;
                $q1 = ceil($n / 4);
                $Q1 = $dowConsoData[$key][$q1];
                // $q2 = floor(($n + 1) / 2) + 1;
                // $q2 = ceil($n / 2);
                $Q2 = $this->mmmrv($dowConsoData[$key], 'median');
                $Q2 = floatval(number_format((float)$Q2, 2, '.', ''));
                // $q3 = floor(($n + 1) / 4) + 1;
                $q3 = ceil((3 * $n) / 4);
                $Q3 = $dowConsoData[$key][$q3];
                $max = max($dowConsoData[$key]);
                $max = floatval(number_format((float)$max, 2, '.', ''));
                $dQ = $Q3 - $Q1;
                $dQ = floatval(number_format((float)$dQ, 2, '.', ''));
                $Q1 = floatval(number_format((float)$Q1, 2, '.', ''));
                $Q3 = floatval(number_format((float)$Q3, 2, '.', ''));
                $cV = $this->mmmrv($dowConsoData[$key], 'variation');
                $cV = floatval(number_format((float)$cV, 2, '.', ''));
                $Q_conso['chart'][] = json_encode([
                    'x' => $key,
                    'y' => [$min, $Q1, $Q2, $Q3, $max]
                ], true);
                $Q_conso[$key] = [$Q1, $Q2, $Q3, $dQ, $cV];
                /*$Q_conso[$key] = [$min, $Q1, $Q2, $Q3, $max, $dQ, $cV];
                $Q_conso[$key] = [
                    'chart' => [$min, $Q1, $Q2, $Q3, $max],
                    'stats' => [$Q1, $Q2, $Q3, $dQ, $cV]
                ];*/
            }
        }
        // dump($Q_conso);

        // Dispersion des Pics de puissance
        /*$dowHighPowerDataQuery = $this->manager->createQuery("SELECT CASE 
                                                            WHEN DAYOFWEEK(d.dateTime) =  1 THEN d.pmax/:power_unit
                                                            ELSE 'no'
                                                END AS Dim,
                                                CASE 
                                                    WHEN DAYOFWEEK(d.dateTime) =  2 THEN d.pmax/:power_unit
                                                    ELSE 'no'
                                                END AS Lun,
                                                CASE 
                                                    WHEN DAYOFWEEK(d.dateTime) =  3 THEN d.pmax/:power_unit
                                                    ELSE 'no'
                                                END AS Mar,
                                                CASE 
                                                    WHEN DAYOFWEEK(d.dateTime) =  4 THEN d.pmax/:power_unit
                                                    ELSE 'no'
                                                END AS Mer,
                                                CASE 
                                                    WHEN DAYOFWEEK(d.dateTime) =  5 THEN d.pmax/:power_unit
                                                    ELSE 'no'
                                                END AS Jeu,
                                                CASE 
                                                    WHEN DAYOFWEEK(d.dateTime) =  6 THEN d.pmax/:power_unit
                                                    ELSE 'no'
                                                END AS Ven,
                                                CASE 
                                                    WHEN DAYOFWEEK(d.dateTime) =  7 THEN d.pmax/:power_unit
                                                    ELSE 'no'
                                                END AS Sam
                                                FROM App\Entity\LoadEnergyData d
                                                JOIN d.smartMod sm
                                                WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='Load Meter' AND stm.levelZone=1)
                                                AND d.dateTime BETWEEN :startDate AND :endDate
                                                ")
            ->setParameters(array(
                'startDate'    => $this->startDate->format('Y-m-d H:i:s'),
                'endDate'      => $this->endDate->format('Y-m-d H:i:s'),
                'siteId'       => $this->site->getId(),
                'power_unit'    => $this->power_unit
            ))
            //->setMaxResults(2000)
            ->getResult();*/
        $dowHighPowerDataQuery = $this->manager->createQuery("SELECT SUBSTRING(d.dateTime,1,10) AS jour, MAX(d.pmax)/:power_unit AS Pmax
                                                FROM App\Entity\LoadEnergyData d
                                                JOIN d.smartMod sm
                                                WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='Load Meter' AND stm.levelZone=1)
                                                AND d.dateTime BETWEEN :startDate AND :endDate
                                                GROUP BY jour
                                                ORDER BY jour ASC")
            ->setParameters(array(
                'startDate'    => $this->startDate->format('Y-m-d H:i:s'),
                'endDate'      => $this->endDate->format('Y-m-d H:i:s'),
                'siteId'       => $this->site->getId(),
                'power_unit'   => $this->power_unit
            ))
            //->setMaxResults(2000)
            ->getResult();
        // dump($dowHighPowerDataQuery);
        $dowHighPowerData = [
            'Dimanche'   => [],
            'Lundi'   => [],
            'Mardi'   => [],
            'Mercredi'   => [],
            'Jeudi'   => [],
            'Vendredi'   => [],
            'Samedi'   => [],
        ];
        foreach ($dowHighPowerDataQuery as $data) {
            switch (date('w', strtotime($data['jour']))) {
                case 0:
                    $dowHighPowerData['Dimanche'][] = $data['Pmax'];
                    break;
                case 1:
                    $dowHighPowerData['Lundi'][] = $data['Pmax'];
                    break;
                case 2:
                    $dowHighPowerData['Mardi'][] = $data['Pmax'];
                    break;
                case 3:
                    $dowHighPowerData['Mercredi'][] = $data['Pmax'];
                    break;
                case 4:
                    $dowHighPowerData['Jeudi'][] = $data['Pmax'];
                    break;
                case 5:
                    $dowHighPowerData['Vendredi'][] = $data['Pmax'];
                    break;
                case 6:
                    $dowHighPowerData['Samedi'][] = $data['Pmax'];
                    break;
                
                default:
                    break;
            }
            /*$dowHighPowerData['Dimanche'][] = $data['Dim'];
            $dowHighPowerData['Lundi'][] = $data['Lun'];
            $dowHighPowerData['Mardi'][] = $data['Mar'];
            $dowHighPowerData['Mercredi'][] = $data['Mer'];
            $dowHighPowerData['Jeudi'][] = $data['Jeu'];
            $dowHighPowerData['Vendredi'][] = $data['Ven'];
            $dowHighPowerData['Samedi'][] = $data['Sam'];*/
        }

        $Q_highPower = [
            'Lundi'      => [],
            'Mardi'      => [],
            'Mercredi'   => [],
            'Jeudi'      => [],
            'Vendredi'   => [],
            'Samedi'     => [],
            'Dimanche'   => [],
            'chart'      => []
        ];
        foreach ($dowHighPowerData as $key => $value) {
            $dowHighPowerData[$key] = array_filter($dowHighPowerData[$key], fn ($val) => $val !== 'no');
            if (count($dowHighPowerData[$key]) > 0) {
                sort($dowHighPowerData[$key]);
                $n = count($dowHighPowerData[$key]) - 1;
                $min = min($dowHighPowerData[$key]);
                $min = floatval(number_format((float)$min, 2, '.', ''));
                // $q1 = floor(($n + 3) / 4) + 1;
                $q1 = ceil($n / 4);
                $Q1 = $dowHighPowerData[$key][$q1];
                // $q2 = floor(($n + 1) / 2) + 1;
                // $q2 = ceil($n / 2);
                $Q2 = $this->mmmrv($dowHighPowerData[$key], 'median');
                $Q2 = floatval(number_format((float)$Q2, 2, '.', ''));
                // $q3 = floor(($n + 1) / 4) + 1;
                $q3 = ceil((3 * $n) / 4);
                $Q3 = $dowHighPowerData[$key][$q3];
                $max = max($dowHighPowerData[$key]);
                $max = floatval(number_format((float)$max, 2, '.', ''));
                $dQ = $Q3 - $Q1;
                $dQ = floatval(number_format((float)$dQ, 2, '.', ''));
                $Q1 = floatval(number_format((float)$Q1, 2, '.', ''));
                $Q3 = floatval(number_format((float)$Q3, 2, '.', ''));
                $cV = $this->mmmrv($dowHighPowerData[$key], 'variation');
                $cV = floatval(number_format((float)$cV, 2, '.', ''));
                $Q_highPower['chart'][] = json_encode([
                    'x' => $key,
                    'y' => [$min, $Q1, $Q2, $Q3, $max]
                ], true);
                $Q_highPower[$key] = [$Q1, $Q2, $Q3, $dQ, $cV];
                /*$Q_highPower[$key] = [
                    //'chart' => [$min, $Q1, $Q2, $Q3, $max],
                    'stats' => [$Q1, $Q2, $Q3, $dQ, $cV]
                ];*/
            }
        }
        // dump($dowHighPowerData);
        // dump($Q_highPower);

        // dump($trhData);

        if (!$this->site->getHasOneSmartMod()) {
            // ========= Récupération des données pour les Statistques Production, Mix énergie et Stats =========
            // Récapitulatif Production
            $recapProd  = $this->siteProDataService->getOverviewData($onlySrc = true);
            $amountBill = $this->estimatedBill();
            //dump($recapProd);

            // Mix Energie et stats
            $mix = $this->getMixEnergieData();
//            dump($mix);

            // ######## Récupération des données de Durée de Fonctionnement du GE
            $trhData = $this->gensetModService->getConsoFuelData();

            // ######## Récupération des données pour le tracé des profils de puissance par source
            $powerProfilSupply = $this->siteProDataService->getPowerChartDataForDateRange();

            return array(
                'recapProd' => $recapProd,
                'amount-conso-HT' => floatval(number_format((float)$amountBill, 2, '.', '')),
                'mix' => $mix,
                'consoTotal' => floatval(number_format((float)$consoTotale, 2, '.', '')),
                'consoTotalProgress' => $consoTotaleProgress !== 'INF' ? floatval(number_format((float)$consoTotaleProgress, 2, '.', '')) : 'INF',
                'consoMoy' => floatval(number_format((float)$consoMoy, 2, '.', '')),
                'consoMoyProgress' => $consoMoyProgress !== 'INF' ? floatval(number_format((float)$consoMoyProgress, 2, '.', '')) : 'INF',
                'consoVariation' => floatval(number_format((float)$consoVariation, 2, '.', '')),
                'consoVariationProgress' => $consoVariationProgress !== 'INF' ? floatval(number_format((float)$consoVariationProgress, 2, '.', '')) : 'INF',
                '+forteConso' => $strPlusForteConso,
                '+forteConsoProgress' => $PlusForteConsoProgress !== 'INF' ? floatval(number_format((float)$PlusForteConsoProgress, 2, '.', '')) : 'INF',
                '+faibleConso' => $strPlusFaibleConso,
                '+faibleConsoProgress' => $PlusFaibleConsoProgress !== 'INF' ? floatval(number_format((float)$PlusFaibleConsoProgress, 2, '.', '')) : 'INF',
                'Talon' => $strTalon,
                'TalonProgress' => $TalonProgress !== 'INF' ? floatval(number_format((float)$TalonProgress, 2, '.', '')) : 'INF',
                'Pic' => $strPic,
                'PicProgress' => $PicProgress !== 'INF' ? floatval(number_format((float)$PicProgress, 2, '.', '')) : 'INF',
                'conso-00-08' => floatval(number_format((float)$conso00_08kWh, 2, '.', '')),
                'conso-00-08Progress' => $conso00_08kWhProgress !== 'INF' ? floatval(number_format((float)$conso00_08kWhProgress, 2, '.', '')) : 'INF',
                'conso-08-18' => floatval(number_format((float)$conso08_18kWh, 2, '.', '')),
                'conso-08-18Progress' => $conso08_18kWhProgress !== 'INF' ? floatval(number_format((float)$conso08_18kWhProgress, 2, '.', '')) : 'INF',
                'conso-18-00' => floatval(number_format((float)$conso18_00kWh, 2, '.', '')),
                'conso-18-00Progress' => $conso18_00kWhProgress !== 'INF' ? floatval(number_format((float)$conso18_00kWhProgress, 2, '.', '')) : 'INF',

                'histoConso' => [
                    'date' => $histogramConsoDate,
                    'data' => [$histogramConsoData, $lastHistogramConsoData]
                ],
                'histoHighPower' => [
                    'date' => $histogramHighPowerDate,
                    'data' => [$histogramHighPowerData, $lastHistogramHighPowerData, $Psous]
                ],
                'powerProfilHighDayConso' => [
                    'date' => $highDayConsoPowerProfilDate,
                    'data' => [$highDayConsoPowerProfilData, $lastHighDayConsoPowerProfilData, $highDayConsoPowerProfilPsousData]
                ],
                'powerProfilLowDayConso' => [
                    'date' => $lowDayConsoPowerProfilDate,
                    'data' => [$lowDayConsoPowerProfilData, $lastLowDayConsoPowerProfilData, $lowDayConsoPowerProfilPsousData]
                ],
                'loadChart' => [
                    'date' => $loadChartDataDate,
                    'data' => [$loadChartDataPmoy, $loadChartDataPmax, $loadChartDataPsous]
                ],
                'monotonePower' => [
                    'chart' => [
                        'order' => $monotonePowerOrder,
                        'data' => $monotonePowerPmax
                    ],
                    'stats' => [
                        'mean' => floatval(number_format((float)$meanPower, 2, '.', '')),
                        'meanProgress' => $meanPowerProgress !== 'INF' ? floatval(number_format((float)$meanPowerProgress, 2, '.', '')) : 'INF',
                        'median' => $medianPower,
                        'medianProgress' => $medianPowerProgress !== 'INF' ? floatval(number_format((float)$medianPowerProgress, 2, '.', '')) : 'INF',
                        'min' => $minPower,
                        'minProgress' => $minPowerProgress !== 'INF' ? floatval(number_format((float)$minPowerProgress, 2, '.', '')) : 'INF',
                        'max' => $maxPower,
                        'maxProgress' => $maxPowerProgress !== 'INF' ? floatval(number_format((float)$maxPowerProgress, 2, '.', '')) : 'INF',
                        'nbDepassement' => $nbDepassement,
                        'nbDepassementProgress' => $nbDepassementProgress !== 'INF' ? floatval(number_format((float)$nbDepassementProgress, 2, '.', '')) : 'INF',
                        'Prang10' => $prang10,
                        'Prang10Progress' => $prang10Progress !== 'INF' ? floatval(number_format((float)$prang10Progress, 2, '.', '')) : 'INF'
                    ]
                ],
                'minimaCosfi' => [
                    'chart' => [
                        'date' => $minimaCosfiDate,
                        'data' => $minimaCosfiData
                    ],
                    'stats' => [
                        'mean' => floatval(number_format((float)$meanCosfi, 2, '.', '')),
                        'meanProgress' => $meanCosfiProgress !== 'INF' ? floatval(number_format((float)$meanCosfiProgress, 2, '.', '')) : 'INF',
                        'median' => $medianCosfi,
                        'medianProgress' => $medianCosfiProgress !== 'INF' ? floatval(number_format((float)$medianCosfiProgress, 2, '.', '')) : 'INF',
                        'min' => $minCosfi,
                        'minProgress' => $minCosfiProgress !== 'INF' ? floatval(number_format((float)$minCosfiProgress, 2, '.', '')) : 'INF',
                        'max' => $maxCosfi,
                        'maxProgress' => $maxCosfiProgress !== 'INF' ? floatval(number_format((float)$maxCosfiProgress, 2, '.', '')) : 'INF',
                        'nbInsuffisance' => $nbInsuffisance,
                        'nbInsuffisanceProgress' => $nbInsuffisanceProgress !== 'INF' ? floatval(number_format((float)$nbInsuffisanceProgress, 2, '.', '')) : 'INF',
                        'cosfiEnergy' => $cosfiEnergy,
                        'cosfiEnergyProgress' => $cosfiEnergyProgress !== 'INF' ? floatval(number_format((float)$cosfiEnergyProgress, 2, '.', '')) : 'INF'
                    ]
                ],
                'disperConso' => $Q_conso,
                'disperPic' => $Q_highPower,
                'powerProfilSupply' => $powerProfilSupply,
                'TRHchart' => [
                    'date' => $trhData['dayBydayConsoData']['dateConso'],
                    'trh' => $trhData['dayBydayConsoData']['duree']
                ],
                'statsDureeFonctionnement' => $trhData['statsDureeFonctionnement'],

            );
        }else {
            return array(
                'consoTotal' => floatval(number_format((float)$consoTotale, 2, '.', '')),
                'consoTotalProgress' => $consoTotaleProgress !== 'INF' ? floatval(number_format((float)$consoTotaleProgress, 2, '.', '')) : 'INF',
                'consoMoy' => floatval(number_format((float)$consoMoy, 2, '.', '')),
                'consoMoyProgress' => $consoMoyProgress !== 'INF' ? floatval(number_format((float)$consoMoyProgress, 2, '.', '')) : 'INF',
                'consoVariation' => floatval(number_format((float)$consoVariation, 2, '.', '')),
                'consoVariationProgress' => $consoVariationProgress !== 'INF' ? floatval(number_format((float)$consoVariationProgress, 2, '.', '')) : 'INF',
                '+forteConso' => $strPlusForteConso,
                '+forteConsoProgress' => $PlusForteConsoProgress !== 'INF' ? floatval(number_format((float)$PlusForteConsoProgress, 2, '.', '')) : 'INF',
                '+faibleConso' => $strPlusFaibleConso,
                '+faibleConsoProgress' => $PlusFaibleConsoProgress !== 'INF' ? floatval(number_format((float)$PlusFaibleConsoProgress, 2, '.', '')) : 'INF',
                'Talon' => $strTalon,
                'TalonProgress' => $TalonProgress !== 'INF' ? floatval(number_format((float)$TalonProgress, 2, '.', '')) : 'INF',
                'Pic' => $strPic,
                'PicProgress' => $PicProgress !== 'INF' ? floatval(number_format((float)$PicProgress, 2, '.', '')) : 'INF',
                'conso-00-08' => floatval(number_format((float)$conso00_08kWh, 2, '.', '')),
                'conso-00-08Progress' => $conso00_08kWhProgress !== 'INF' ? floatval(number_format((float)$conso00_08kWhProgress, 2, '.', '')) : 'INF',
                'conso-08-18' => floatval(number_format((float)$conso08_18kWh, 2, '.', '')),
                'conso-08-18Progress' => $conso08_18kWhProgress !== 'INF' ? floatval(number_format((float)$conso08_18kWhProgress, 2, '.', '')) : 'INF',
                'conso-18-00' => floatval(number_format((float)$conso18_00kWh, 2, '.', '')),
                'conso-18-00Progress' => $conso18_00kWhProgress !== 'INF' ? floatval(number_format((float)$conso18_00kWhProgress, 2, '.', '')) : 'INF',

                'histoConso' => [
                    'date' => $histogramConsoDate,
                    'data' => [$histogramConsoData, $lastHistogramConsoData]
                ],
                'histoHighPower' => [
                    'date' => $histogramHighPowerDate,
                    'data' => [$histogramHighPowerData, $lastHistogramHighPowerData, $Psous]
                ],
                'powerProfilHighDayConso' => [
                    'date' => $highDayConsoPowerProfilDate,
                    'data' => [$highDayConsoPowerProfilData, $lastHighDayConsoPowerProfilData, $highDayConsoPowerProfilPsousData]
                ],
                'powerProfilLowDayConso' => [
                    'date' => $lowDayConsoPowerProfilDate,
                    'data' => [$lowDayConsoPowerProfilData, $lastLowDayConsoPowerProfilData, $lowDayConsoPowerProfilPsousData]
                ],
                'loadChart' => [
                    'date' => $loadChartDataDate,
                    'data' => [$loadChartDataPmoy, $loadChartDataPmax, $loadChartDataPsous]
                ],
                'monotonePower' => [
                    'chart' => [
                        'order' => $monotonePowerOrder,
                        'data' => $monotonePowerPmax
                    ],
                    'stats' => [
                        'mean' => floatval(number_format((float)$meanPower, 2, '.', '')),
                        'meanProgress' => $meanPowerProgress !== 'INF' ? floatval(number_format((float)$meanPowerProgress, 2, '.', '')) : 'INF',
                        'median' => $medianPower,
                        'medianProgress' => $medianPowerProgress !== 'INF' ? floatval(number_format((float)$medianPowerProgress, 2, '.', '')) : 'INF',
                        'min' => $minPower,
                        'minProgress' => $minPowerProgress !== 'INF' ? floatval(number_format((float)$minPowerProgress, 2, '.', '')) : 'INF',
                        'max' => $maxPower,
                        'maxProgress' => $maxPowerProgress !== 'INF' ? floatval(number_format((float)$maxPowerProgress, 2, '.', '')) : 'INF',
                        'nbDepassement' => $nbDepassement,
                        'nbDepassementProgress' => $nbDepassementProgress !== 'INF' ? floatval(number_format((float)$nbDepassementProgress, 2, '.', '')) : 'INF',
                        'Prang10' => $prang10,
                        'Prang10Progress' => $prang10Progress !== 'INF' ? floatval(number_format((float)$prang10Progress, 2, '.', '')) : 'INF'
                    ]
                ],
                'minimaCosfi' => [
                    'chart' => [
                        'date' => $minimaCosfiDate,
                        'data' => $minimaCosfiData
                    ],
                    'stats' => [
                        'mean' => floatval(number_format((float)$meanCosfi, 2, '.', '')),
                        'meanProgress' => $meanCosfiProgress !== 'INF' ? floatval(number_format((float)$meanCosfiProgress, 2, '.', '')) : 'INF',
                        'median' => $medianCosfi,
                        'medianProgress' => $medianCosfiProgress !== 'INF' ? floatval(number_format((float)$medianCosfiProgress, 2, '.', '')) : 'INF',
                        'min' => $minCosfi,
                        'minProgress' => $minCosfiProgress !== 'INF' ? floatval(number_format((float)$minCosfiProgress, 2, '.', '')) : 'INF',
                        'max' => $maxCosfi,
                        'maxProgress' => $maxCosfiProgress !== 'INF' ? floatval(number_format((float)$maxCosfiProgress, 2, '.', '')) : 'INF',
                        'nbInsuffisance' => $nbInsuffisance,
                        'nbInsuffisanceProgress' => $nbInsuffisanceProgress !== 'INF' ? floatval(number_format((float)$nbInsuffisanceProgress, 2, '.', '')) : 'INF',
                        'cosfiEnergy' => $cosfiEnergy,
                        'cosfiEnergyProgress' => $cosfiEnergyProgress !== 'INF' ? floatval(number_format((float)$cosfiEnergyProgress, 2, '.', '')) : 'INF'
                    ]
                ],
                'disperConso' => $Q_conso,
                'disperPic' => $Q_highPower,

            );
        }
    }

    public function getMixEnergieData()
    {
        $lastStartDate = new DateTime($this->startDate->format('Y-m-d H:i:s'));
        $lastStartDate->sub(new DateInterval('P1M'));

        $lastEndDate = new DateTime($this->endDate->format('Y-m-d H:i:s'));
        $lastEndDate->sub(new DateInterval('P1M'));

        $gridData = [
            'EA'     => 0.0,
            'EAmoy'  => 0.0,
            'Pmoy'   => 0.0,
            'EAProgress'     => 0.0,
            'EAmoyProgress'  => 0.0,
            'PmoyProgress'   => 0.0,

        ];

        $gensetData = [
            'EA'     => 0.0,
            'EAmoy'  => 0.0,
            'Pmoy'   => 0.0,
            'EAProgress'     => 0.0,
            'EAmoyProgress'  => 0.0,
            'PmoyProgress'   => 0.0,

        ];

        $solarData = [
            'EA'     => 0.0,
            'EAmoy'  => 0.0,
            'Pmoy'   => 0.0,
            'EAProgress'     => 0.0,
            'EAmoyProgress'  => 0.0,
            'PmoyProgress'   => 0.0,

        ];

        $batteryData = [
            'EA'     => 0.0,
            'EAmoy'  => 0.0,
            'Pmoy'   => 0.0,
            'EAProgress'     => 0.0,
            'EAmoyProgress'  => 0.0,
            'PmoyProgress'   => 0.0,

        ];

        // Grid Data
        $gridProdDataQuery = $this->manager->createQuery("SELECT SUM(d.pmoy)*:time AS kWh, AVG(d.pmoy*:time) AS EAmoy, AVG(d.pmoy) AS Pmoy
                                            FROM App\Entity\LoadEnergyData d
                                            JOIN d.smartMod sm
                                            WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='GRID')
                                            AND d.dateTime BETWEEN :startDate AND :endDate
                                            ")
            ->setParameters(array(
                'time'       => $this->gridIntervalTime,
                'startDate'  => $this->startDate->format('Y-m-d H:i:s'),
                'endDate'    => $this->endDate->format('Y-m-d H:i:s'),
                'siteId'     => $this->site->getId()
            ))
            ->getResult();
        foreach ($gridProdDataQuery as $d) {
            $gridData['EA']     = floatval($d['kWh']);
            $gridData['EAmoy']  = floatval($d['EAmoy']);
            $gridData['Pmoy']   = floatval($d['Pmoy']);
        }
        $lastGridProdDataQuery = $this->manager->createQuery("SELECT SUM(d.pmoy)*:time AS kWh, AVG(d.pmoy*:time) AS EAmoy, AVG(d.pmoy) AS Pmoy
                                            FROM App\Entity\LoadEnergyData d
                                            JOIN d.smartMod sm
                                            WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='GRID')
                                            AND d.dateTime BETWEEN :lastStartDate AND :lastEndDate
                                            ")
            ->setParameters(array(
                'time'           => $this->gridIntervalTime,
                'lastStartDate'  => $lastStartDate->format('Y-m-d H:i:s'),
                'lastEndDate'    => $lastEndDate->format('Y-m-d H:i:s'),
                'siteId'         => $this->site->getId()
            ))
            ->getResult();

        //dump($lastGridProdDataQuery);
        $lastEA = 0.0;
        $lastEAmoy = 0.0;
        $lastPmoy = 0.0;
        foreach ($lastGridProdDataQuery as $d) {
            $lastEA     = floatval($d['kWh']);
            $lastEAmoy  = floatval($d['EAmoy']);
            $lastPmoy   = floatval($d['Pmoy']);
        }
        $gridData['EAProgress'] = ($lastEA > 0) ? ($gridData['EAProgress'] - $lastEA) * 100 / $lastEA : 'INF';
        $gridData['EAProgress'] = $gridData['EAProgress'] !== 'INF' ? floatval(number_format((float) $gridData['EAProgress'], 2, '.', '')) : 'INF';
        $gridData['EA']         = floatval(number_format((float) $gridData['EA'], 2, '.', ''));

        $gridData['EAmoyProgress'] = ($lastEAmoy > 0) ? ($gridData['EAmoyProgress'] - $lastEAmoy) * 100 / $lastEAmoy : 'INF';
        $gridData['EAmoyProgress'] = $gridData['EAmoyProgress'] !== 'INF' ? floatval(number_format((float) $gridData['EAmoyProgress'], 2, '.', '')) : 'INF';
        $gridData['EAmoy']         = floatval(number_format((float) $gridData['EAmoy'], 2, '.', ''));

        $gridData['PmoyProgress'] = ($lastPmoy > 0) ? ($gridData['PmoyProgress'] - $lastPmoy) * 100 / $lastPmoy : 'INF';
        $gridData['PmoyProgress'] = $gridData['PmoyProgress'] !== 'INF' ? floatval(number_format((float) $gridData['PmoyProgress'], 2, '.', '')) : 'INF';
        $gridData['Pmoy']         = floatval(number_format((float) $gridData['Pmoy'], 2, '.', ''));

        // Genset Data
        $gensetProdDataQuery = [];

        if($this->gensetMod->getSubType() === 'ModBus'){ //Si le module GENSET est de type Modbus 
        $gensetProdDataQuery = $this->manager->createQuery("SELECT DISTINCT SUBSTRING(d.dateTime,1,13) AS dt, 
                                            MAX(d.totalEnergy) - MIN(NULLIF(d.totalEnergy,0)) AS TEP
                                            FROM App\Entity\GensetData d
                                            JOIN d.smartMod sm
                                            WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='GENSET')
                                            AND d.dateTime BETWEEN :startDate AND :endDate
                                            GROUP BY dt
                                            ORDER BY dt ASC")
            ->setParameters(array(
                'startDate'  => $this->startDate->format('Y-m-d H:i:s'),
                'endDate'    => $this->endDate->format('Y-m-d H:i:s'),
                'siteId'     => $this->site->getId()
            ))
            ->getResult();
        } else if(strpos($this->gensetMod->getSubType(), 'Inv') !== false ) { //Si le module GENSET est de type Inverter 
        $gensetProdDataQuery = $this->manager->createQuery("SELECT DISTINCT SUBSTRING(d.dateTime,1,13) AS dt, 
                                            SUM(d.p)*:time AS TEP
                                            FROM App\Entity\GensetData d
                                            JOIN d.smartMod sm
                                            WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='GENSET')
                                            AND d.dateTime BETWEEN :startDate AND :endDate
                                            GROUP BY dt
                                            ORDER BY dt ASC")
            ->setParameters(array(
                'time'       => $this->gensetIntervalTime,
                'startDate'  => $this->startDate->format('Y-m-d H:i:s'),
                'endDate'    => $this->endDate->format('Y-m-d H:i:s'),
                'siteId'     => $this->site->getId()
            ))
            ->getResult();
        }
        // dump($gensetProdDataQuery);
        $EA = [];
        foreach ($gensetProdDataQuery as $d) {
            $EA[]     = floatval($d['TEP']);
            $gensetData['EA']  += floatval($d['TEP']);
        }
        $gensetData['EAmoy'] = count($EA) > 0 ? array_sum($EA) / count($EA) : 0.0;

        $gensetProdDataQuery = $this->manager->createQuery("SELECT AVG(d.p) AS Pmoy
                                            FROM App\Entity\GensetData d
                                            JOIN d.smartMod sm
                                            WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='GENSET')
                                            AND d.dateTime BETWEEN :startDate AND :endDate
                                            ")
            ->setParameters(array(
                'startDate'  => $this->startDate->format('Y-m-d H:i:s'),
                'endDate'    => $this->endDate->format('Y-m-d H:i:s'),
                'siteId'     => $this->site->getId()
            ))
            ->getResult();

        foreach ($gensetProdDataQuery as $d) {
            $gensetData['Pmoy']  = floatval($d['Pmoy']);
        }
        
        $lastGensetProdDataQuery = [];
        if($this->gensetMod->getSubType() === 'ModBus'){ //Si le module GENSET est de type Modbus 
        $lastGensetProdDataQuery = $this->manager->createQuery("SELECT DISTINCT SUBSTRING(d.dateTime,1,13) AS dt, 
                                            MAX(d.totalEnergy) - MIN(NULLIF(d.totalEnergy,0)) AS TEP
                                            FROM App\Entity\GensetData d
                                            JOIN d.smartMod sm
                                            WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='GENSET')
                                            AND d.dateTime BETWEEN :lastStartDate AND :lastEndDate
                                            GROUP BY dt
                                            ORDER BY dt ASC")
            ->setParameters(array(
                'lastStartDate'  => $lastStartDate->format('Y-m-d H:i:s'),
                'lastEndDate'    => $lastEndDate->format('Y-m-d H:i:s'),
                'siteId'         => $this->site->getId()
            ))
            ->getResult();
        } else if(strpos($this->gensetMod->getSubType(), 'Inv') !== false ) { //Si le module GENSET est de type Inverter 
        $lastGensetProdDataQuery = $this->manager->createQuery("SELECT DISTINCT SUBSTRING(d.dateTime,1,13) AS dt, 
                                            SUM(d.p)*:time AS TEP
                                            FROM App\Entity\GensetData d
                                            JOIN d.smartMod sm
                                            WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='GENSET')
                                            AND d.dateTime BETWEEN :lastStartDate AND :lastEndDate
                                            GROUP BY dt
                                            ORDER BY dt ASC")
            ->setParameters(array(
                'time'       => $this->gensetIntervalTime,
                'lastStartDate'  => $lastStartDate->format('Y-m-d H:i:s'),
                'lastEndDate'    => $lastEndDate->format('Y-m-d H:i:s'),
                'siteId'         => $this->site->getId()
            ))
            ->getResult();
        }
        $EA = [];
        $lastEA = 0.0;
        $lastEAmoy = 0.0;
        $lastPmoy = 0.0;
        foreach ($lastGensetProdDataQuery as $d) {
            $EA[]     = floatval($d['TEP']);
            $lastEA  += floatval($d['TEP']);
        }
        $lastEAmoy = count($EA) > 0 ? array_sum($EA) / count($EA) : 0.0;

        $lastGensetProdDataQuery = $this->manager->createQuery("SELECT AVG(d.p) AS Pmoy
                                            FROM App\Entity\GensetData d
                                            JOIN d.smartMod sm
                                            WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='GENSET')
                                            AND d.dateTime BETWEEN :lastStartDate AND :lastEndDate
                                            ")
            ->setParameters(array(
                'lastStartDate'  => $lastStartDate->format('Y-m-d H:i:s'),
                'lastEndDate'    => $lastEndDate->format('Y-m-d H:i:s'),
                'siteId'         => $this->site->getId()
            ))
            ->getResult();

        foreach ($lastGensetProdDataQuery as $d) {
            $lastPmoy  = floatval($d['Pmoy']);
        }


        $gensetData['EAProgress'] = ($lastEA > 0) ? ($gensetData['EAProgress'] - $lastEA) * 100 / $lastEA : 'INF';
        $gensetData['EAProgress'] = $gensetData['EAProgress'] !== 'INF' ? floatval(number_format((float) $gensetData['EAProgress'], 2, '.', '')) : 'INF';
        $gensetData['EA']         = floatval(number_format((float) $gensetData['EA'], 2, '.', ''));

        $gensetData['EAmoyProgress'] = ($lastEAmoy > 0) ? ($gensetData['EAmoyProgress'] - $lastEAmoy) * 100 / $lastEAmoy : 'INF';
        $gensetData['EAmoyProgress'] = $gensetData['EAmoyProgress'] !== 'INF' ? floatval(number_format((float) $gensetData['EAmoyProgress'], 2, '.', '')) : 'INF';
        $gensetData['EAmoy']         = floatval(number_format((float) $gensetData['EAmoy'], 2, '.', ''));

        $gensetData['PmoyProgress'] = ($lastPmoy > 0) ? ($gensetData['PmoyProgress'] - $lastPmoy) * 100 / $lastPmoy : 'INF';
        $gensetData['PmoyProgress'] = $gensetData['PmoyProgress'] !== 'INF' ? floatval(number_format((float) $gensetData['PmoyProgress'], 2, '.', '')) : 'INF';
        $gensetData['Pmoy']         = floatval(number_format((float) $gensetData['Pmoy'], 2, '.', ''));

        return array(
            'gridData'    => $gridData,
            'gensetData'  => $gensetData,
            'dataPie'     => [$gridData['EA'], $gensetData['EA'], $solarData['EA'], $batteryData['EA']]
        );
    }


    public function estimatedBill(){
        $gridAmountHT = 0.0;

        if ($this->site->getSubscription() === 'MT') {

            $currentConsoQuery = $this->manager->createQuery("SELECT SUM(d.ea) AS EA, SUM(d.depassement) AS Nb_Depassement,
                                            SUM(d.ea)/SQRT( (SUM(d.ea)*SUM(d.ea)) + (SUM(d.er)*SUM(d.er)) ) AS PF
                                            FROM App\Entity\LoadEnergyData d
                                            JOIN d.smartMod sm
                                            WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='Load Meter' AND stm.levelZone=1)
                                            AND d.dateTime BETWEEN :startDate AND :endDate")
                ->setParameters(array(
                    'startDate'  => $this->startDate->format('Y-m-d H:i:s'),
                    'endDate'    => $this->endDate->format('Y-m-d H:i:s'),
                    'siteId'     => $this->site->getId()
                ))
                ->getResult();
            
            $currentConsokWh   = count($currentConsoQuery) > 0 ? $currentConsoQuery[0]['EA'] ?? 0.0 : 0.0;

            $currentMonthConsokWhPerRangeQuery = $this->manager->createQuery("SELECT SUM(CASE 
                                                                            WHEN SUBSTRING(d.dateTime, 12) BETWEEN '23:00:00' AND '23:59:59' OR SUBSTRING(d.dateTime, 12) BETWEEN '00:00:00' AND '17:59:59' THEN d.pmoy
                                                                            ELSE 0
                                                                        END)*:time AS EAHP,
                                                                        SUM(CASE 
                                                                            WHEN (d.pmoy >= :psous) AND (SUBSTRING(d.dateTime, 12) BETWEEN '23:00:00' AND '23:59:59' OR SUBSTRING(d.dateTime, 12) BETWEEN '00:00:00' AND '17:59:59') THEN 1
                                                                            ELSE 0
                                                                        END) AS EAHP_Hours,
                                                                        SUM(CASE 
                                                                            WHEN SUBSTRING(d.dateTime, 12) BETWEEN '18:00:00' AND '22:59:59' THEN d.pmoy
                                                                            ELSE 0
                                                                        END)*:time AS EAP, 
                                                                        SUM(CASE 
                                                                            WHEN SUBSTRING(d.dateTime, 12) BETWEEN '23:00:00' AND '23:59:59' OR SUBSTRING(d.dateTime, 12) BETWEEN '00:00:00' AND '17:59:59' THEN d.qmoy
                                                                            ELSE 0
                                                                        END)*:time AS ERHP,
                                                                        SUM(CASE 
                                                                            WHEN SUBSTRING(d.dateTime, 12) BETWEEN '18:00:00' AND '22:59:59' THEN d.qmoy
                                                                            ELSE 0
                                                                        END)*:time AS ERP, 
                                                                        SUM(CASE 
                                                                            WHEN (d.pmoy >= :psous) AND (SUBSTRING(d.dateTime, 12) BETWEEN '18:00:00' AND '22:59:59') THEN 1
                                                                            ELSE 0
                                                                        END) AS EAP_Hours 
                                                                        FROM App\Entity\LoadEnergyData d
                                                                        JOIN d.smartMod sm
                                                                        WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='Load Meter' AND stm.levelZone=1)
                                                                        AND d.dateTime BETWEEN :startDate AND :endDate
                                                                        ")
                ->setParameters(array(
                    'time'       => $this->loadSiteIntervalTime,
                    'startDate'  => $this->startDate->format('Y-m-d H:i:s'),
                    'endDate'    => $this->endDate->format('Y-m-d H:i:s'),
                    'siteId'     => $this->site->getId(),
                    'psous'      => $this->site->getPowerSubscribed(),
                ))
                ->getResult();
            
            $EAHP   = $currentMonthConsokWhPerRangeQuery[0]['EAHP'] ?? 0;
            $EAHP   = floatval($EAHP);
            $EAP    = $currentMonthConsokWhPerRangeQuery[0]['EAP'] ?? 0;
            $EAP    = floatval($EAP);
            $EA     = $EAHP + $EAP;

            $currentConsoHPHours = $currentMonthConsokWhPerRangeQuery[0]['EAHP_Hours'] ?? 0;
            $currentConsoHPHours = intval($currentConsoHPHours) * $this->gridIntervalTime;

            $gridAmountHT = $this->siteDashboardDataService->getConsumptionXAF($currentConsokWh, array(
                'EAP'        => $EAP,
                'EAHP'       => $EAHP,
                'EAHP_Hours' => $currentConsoHPHours
            ));

        } else if ($this->site->getSubscription() === 'BT'){
            // dump('BT');
            $currentConsoQuery = $this->manager->createQuery("SELECT SUM(d.pmoy)*:time AS EA, 
                                                SUM(d.pmoy)/SQRT( (SUM(d.pmoy)*SUM(d.pmoy)) + (SUM(d.qmoy)*SUM(d.qmoy)) ) AS PF
                                                FROM App\Entity\LoadEnergyData d
                                                JOIN d.smartMod sm
                                                WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='GRID')
                                                AND d.dateTime BETWEEN :startDate AND :endDate")
                ->setParameters(array(
                'time'          => $this->gridIntervalTime,
                'startDate'     => $this->startDate->format('Y-m-d H:i:s'),
                'endDate'       => $this->endDate->format('Y-m-d H:i:s'),
                'siteId'        => $this->site->getId()
                ))
                ->getResult(); 
            
            $currentConsokWh  = count($currentConsoQuery) > 0 ? $currentConsoQuery[0]['EA'] ?? 0.0 : 0.0;

            $gridAmountHT = $this->siteDashboardDataService->getConsumptionXAF($currentConsokWh);
        }

        // dump($gridAmountHT);

        $amount = $gridAmountHT;

        $fuelData = $this->gensetModService->getConsoFuelData();
        $consoFuelXAF = $fuelData['currentConsoFuel'] * $this->gensetMod->getFuelPrice();
        //$consoFuelXAF = floatval(number_format($consoFuelXAF, 2, '.', ''));
        $amount += $consoFuelXAF;

        return $amount;
    }

    /**
     * Permet de calculer la consommation moyenne d'énergie active
     *
     * @param integer $length
     * @param string $startDate
     * @param string $endDate
     * @return float
     */
    public function getAverageConsumption(int $length = 10, $startDate, $endDate)
    {
        $averageConsumption = [];
        //Longueur de la sous-chaîne de date utilisé pour lengther les données dans la DQL
        //2021-09-28

        $averageConsumption = $this->manager->createQuery("SELECT SUBSTRING(d.dateTime,1,:length_) AS dt, SUM(d.pmoy)*:time AS EA
                                            FROM App\Entity\LoadEnergyData d
                                            JOIN d.smartMod sm
                                            WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='Load Meter' AND stm.levelZone=1)
                                            AND d.dateTime BETWEEN :startDate AND :endDate
                                            GROUP BY dt
                                            ORDER BY dt ASC")
            ->setParameters(array(
                'length_'      => $length,
                'time'       => $this->gridIntervalTime,
                'startDate'    => $startDate,
                'endDate'      => $endDate,
                'siteId'       => $this->site->getId()
            ))
            ->getResult();

        // dump($averageConsumption);
        // dump(count($averageConsumption));
        $consoMoy = 0.0;
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

    public function getVariation(int $length = 10, $startDate, $endDate)
    {

        $consumptionQuery = $this->manager->createQuery("SELECT SUBSTRING(d.dateTime,1,:length_) AS dt, SUM(d.pmoy)*:time AS EA
                                                FROM App\Entity\LoadEnergyData d
                                                JOIN d.smartMod sm
                                                WHERE sm.id IN (SELECT stm.id FROM App\Entity\SmartMod stm JOIN stm.site s WHERE s.id = :siteId AND stm.modType='Load Meter' AND stm.levelZone=1)
                                                AND d.dateTime BETWEEN :startDate AND :endDate
                                                GROUP BY dt
                                                ORDER BY dt ASC")
            ->setParameters(array(
                'length_'      => $length,
                'time'       => $this->gridIntervalTime,
                'startDate'    => $startDate,
                'endDate'      => $endDate,
                'siteId'       => $this->site->getId()
            ))
            ->getResult();
        // dump($consumptionQuery);
        // dump(count($consumptionQuery));


        $variation = 0;
        $moyenne = 0;
        if (count($consumptionQuery) > 0) {
            $arrayConso = [];
            foreach ($consumptionQuery as $conso) {
                $arrayConso[] = floatval($conso['EA']);
            }
            // dump($arrayConso);
            $moyenne = array_sum($arrayConso) / (count($arrayConso) * 1.0);
            $variation = $this->ecart_type($arrayConso);
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

    private function mmmrv($array, $output = 'mean')
    {
        if (!is_array($array)) {
            return FALSE;
        } else {
            $total = 0.0;
            if (count($array) <= 0) return $total;
            switch ($output) {
                case 'mean':
                    $total = array_sum($array) / count($array);
                    break;
                case 'median':
                    $count = count($array); //total numbers in array
                    $middleval = floor(($count - 1) / 2); // find the middle value, or the lowest middle value
                    if ($count % 2) { // odd number, middle is the median
                        $total = $array[$middleval];
                    } else { // even number, calculate avg of 2 medians
                        $low = $array[$middleval];
                        $high = $array[$middleval + 1];
                        $total = (($low + $high) / 2);
                    }
                    break;
                case 'mode':
                    $v = array_count_values($array);
                    arsort($v);
                    foreach ($v as $k => $v) {
                        $total = $k;
                        break;
                    }
                    break;
                case 'range':
                    sort($array);
                    $sml = $array[0];
                    rsort($array);
                    $lrg = $array[0];
                    $total = $lrg - $sml;
                    break;
                case 'variation':
                    $variation = 0.0;
                    $moyenne = 0.0;
                    if (count($array) > 0) {
                        $moyenne = array_sum($array) / (count($array) * 1.0);
                        $variation = $this->ecart_type($array);
                    }

                    $total = $moyenne != 0.0 ? ($variation / $moyenne) * 100 : 0.0;
            }
            return $total;
        }
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

        $this->siteProDataService->setSite($site);
        $this->siteDashboardDataService->setSite($site);
        // $smartMods = $this->site->getSmartMods();
        // foreach ($smartMods as $smartMod) {
        //     if ($smartMod->getModType() === 'GENSET') $this->setGensetMod($smartMod);
        // }

        // if ($this->gensetMod) $this->gensetModService->setGensetMod($this->gensetMod);
        
        $smartMods = $this->site->getSmartMods();
        
        foreach ($smartMods as $smartMod) {
            if ($smartMod->getModType() === 'GRID') {
                $this->setGridMod($smartMod);

                $config = json_decode($this->gridMod->getConfiguration(), true);
                if($config) $intervalTime = array_key_exists("Frs", $config) ? $config['Frs']/60.0 : 5.0/60.0 ;//Temps en minutes converti en heure
                else $intervalTime = 5.0/60.0;// dump($intervalTime);
                // dump($intervalTime);
                $this->setGridIntervalTime($intervalTime);
            }
            if ($smartMod->getModType() === 'GENSET') {
                $this->setGensetMod($smartMod);

                $this->gensetModService->setGensetMod($smartMod);
                
                $config = json_decode($this->gensetMod->getConfiguration(), true);
                if($config) $intervalTime = array_key_exists("Frs", $config) ? $config['Frs']/60.0 : 5.0/60.0 ;//Temps en minutes converti en heure
                else $intervalTime = 5.0/60.0;// dump($intervalTime);
                // dump($intervalTime);
                $this->setGridIntervalTime($intervalTime);
            }
            if ($smartMod->getModType() === 'Load Meter') {
                $this->setLoadSiteMod($smartMod);

                $config = json_decode($this->loadSiteMod->getConfiguration(), true);
                if($config) $intervalTime = array_key_exists("Frs", $config) ? $config['Frs']/60.0 : 5.0/60.0 ;//Temps en minutes converti en heure
                else $intervalTime = 5.0/60.0;// dump($intervalTime);
                // dump($intervalTime);
                $this->setLoadSiteIntervalTime($intervalTime);
            }
        }
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
        $this->siteProDataService->setKWhPrice($kWhPrice);
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
        $this->siteProDataService->setCO2PerkWh($CO2PerkWh);
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
        $this->siteProDataService->setStartDate($startDate);
        $this->gensetModService->setStartDate($startDate);
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
        $this->siteProDataService->setEndDate($endDate);
        $this->gensetModService->setEndDate($endDate);
        
        return $this;
    }

    /**
     * Get multiple de Watt
     *
     * @return  integer
     */
    public function getPower_unit()
    {
        return $this->power_unit;
    }

    /**
     * Set multiple de Watt
     *
     * @param  integer  $power_unit  Multiple de Watt
     *
     * @return  self
     */
    public function setPower_unit($power_unit)
    {
        $this->power_unit = $power_unit;
        $this->siteProDataService->setPower_unit($power_unit);
        return $this;
    }

    /**
     * Get module Genset
     *
     * @return  SmartMod
     */
    public function getGensetMod()
    {
        return $this->gensetMod;
    }

    /**
     * Set module Genset
     *
     * @param  SmartMod  $gensetMod  Module Genset
     *
     * @return  self
     */
    public function setGensetMod(SmartMod $gensetMod)
    {
        $this->gensetMod = $gensetMod;

        return $this;
    }

    /**
     * Get module GRID du site
     *
     * @return  SmartMod
     */ 
    public function getGridMod()
    {
        return $this->gridMod;
    }

    /**
     * Set module GRID du site
     *
     * @param  SmartMod  $gridMod  Module GRID du site
     *
     * @return  self
     */ 
    public function setGridMod(SmartMod $gridMod)
    {
        $this->gridMod = $gridMod;

        return $this;
    }

    /**
     * Get module Load Site
     *
     * @return  SmartMod
     */ 
    public function getLoadSiteMod()
    {
        return $this->loadSiteMod;
    }

    /**
     * Set module Load Site
     *
     * @param  SmartMod  $loadSiteMod  Module Load Site
     *
     * @return  self
     */ 
    public function setLoadSiteMod(SmartMod $loadSiteMod)
    {
        $this->loadSiteMod = $loadSiteMod;

        return $this;
    }

    /**
     * Get interval de temps d'envoi des données du module GRID
     *
     * @return  float
     */ 
    public function getGridIntervalTime()
    {
        return $this->gridIntervalTime;
    }

    /**
     * Set interval de temps d'envoi des données du module GRID
     *
     * @param  float  $gridIntervalTime  Interval de temps d'envoi des données du module GRID
     *
     * @return  self
     */ 
    public function setGridIntervalTime(float $gridIntervalTime)
    {
        $this->gridIntervalTime = $gridIntervalTime;

        return $this;
    }

    /**
     * Get interval de temps d'envoi des données du module GENSET
     *
     * @return  float
     */ 
    public function getGensetIntervalTime()
    {
        return $this->gensetIntervalTime;
    }

    /**
     * Set interval de temps d'envoi des données du module GENSET
     *
     * @param  float  $gensetIntervalTime  Interval de temps d'envoi des données du module GENSET
     *
     * @return  self
     */ 
    public function setGensetIntervalTime(float $gensetIntervalTime)
    {
        $this->gensetIntervalTime = $gensetIntervalTime;

        return $this;
    }

    /**
     * Get interval de temps d'envoi des données du module Load Site
     *
     * @return  float
     */ 
    public function getLoadSiteIntervalTime()
    {
        return $this->loadSiteIntervalTime;
    }

    /**
     * Set interval de temps d'envoi des données du module Load Site
     *
     * @param  float  $loadSiteIntervalTime  Interval de temps d'envoi des données du module Load Site
     *
     * @return  self
     */ 
    public function setLoadSiteIntervalTime(float $loadSiteIntervalTime)
    {
        $this->loadSiteIntervalTime = $loadSiteIntervalTime;

        return $this;
    }
}
