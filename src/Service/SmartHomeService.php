<?php

namespace App\Service;

use App\Entity\Box;
use App\Entity\CleverBox;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;


/**
 * Classe de récupération de données de nos controllers
 * 
 * Elle nécessite après instanciation qu'on lui passe l'entité Box sur laquelle on souhaite travailler
 */
class SmartHomeService
{
    /**
     * La box Clever avec laquelle on va travailler
     *
     * @var CleverBox
     */
    private $cleverBox;

    /**
     * Le nom de la route que l'on veut utiliser pour les boutons de la navigation
     *
     * @var string
     */
    private $route;

    /**
     * Le manager de Doctrine qui nous permet notamment de trouver le repository dont on a besoin
     *
     * @var ObjectManager
     */
    private $manager;

    /**
     * Constructeur du service qui sera appelé par Symfony
     * 
     * @param ObjectManager $manager
     * @param RequestStack $request
     */
    public function __construct(EntityManagerInterface $manager, RequestStack $request)
    {
        $this->route        = $request->getCurrentRequest()->attributes->get('_route');
        $this->manager      = $manager;
    }

    /**
     * Clever Box
     *
     * @return CleverBox|null
     */
    public function getCleverBox(): ?CleverBox
    {
        return $this->cleverBox;
    }

    /**
     * Permet de spécifier la Box Clever
     *
     * @param CleverBox $cleverBox La Box Clever à utiliser
     * @return self
     */
    public function setCleverBox($cleverBox): self
    {
        $this->cleverBox = $cleverBox;

        return $this;
    }

    /**
     * Numbers of Camera
     *
     * @return integer|null
     */
    public function getNumberOfCamera(): ?int
    {
        return count($this->manager->getRepository('App:SmartDevice')->findBy(['type' => 'Camera', 'cleverBox'   => $this->cleverBox->getId()]));
    }

    public function getCameraDevice()
    {
        return $this->manager->getRepository('App:SmartDevice')->findBy(['type' => 'Camera', 'cleverBox'  => $this->cleverBox->getId()]);
    }

    /**
     * Number of Motion Sensor 
     *
     * @return integer|null
     */
    public function getNumberOfMotionSensor(): ?int
    {
        return count($this->manager->getRepository('App:SmartDevice')->findBy(['type' => 'Sensor', 'alerte' => 'Intrusion', 'cleverBox'   => $this->cleverBox->getId()]));
    }

    public function getMotionSensorDevice()
    {
        return $this->manager->getRepository('App:SmartDevice')->findBy(['type' => 'Sensor', 'alerte' => 'Intrusion', 'cleverBox' => $this->cleverBox->getId()]);
    }

    /**
     * Number of Fire Sensor 
     *
     * @return integer|null
     */
    public function getNumberOfFireSensor(): ?int
    {
        return count($this->manager->getRepository('App:SmartDevice')->findBy(['type' => 'Sensor', 'alerte' => 'Fire', 'cleverBox'   => $this->cleverBox->getId()]));
    }

    public function getFireSensorDevice()
    {
        return $this->manager->getRepository('App:SmartDevice')->findBy(['type' => 'Sensor', 'alerte' => 'Fire', 'cleverBox'  => $this->cleverBox->getId()]);
    }

    /**
     * Number of Flood Sensor
     *
     * @return integer|null
     */
    public function getNumberOfFloodSensor(): ?int
    {
        return count($this->manager->getRepository('App:SmartDevice')->findBy(['type' => 'Sensor', 'alerte' => 'Flood', 'cleverBox'   => $this->cleverBox->getId()]));
    }

    public function getFloodSensorDevice()
    {
        return $this->manager->getRepository('App:SmartDevice')->findBy(['type' => 'Sensor', 'alerte' => 'Flood', 'cleverBox' => $this->cleverBox->getId()]);
    }

    /**
     * Number of Door Sensor
     *
     * @return integer|null
     */
    public function getNumberOfDoorSensor(): ?int
    {
        return count($this->manager->getRepository('App:SmartDevice')->findBy(['type' => 'Sensor', 'alerte' => 'Opening', 'cleverBox'   => $this->cleverBox->getId()]));
    }

    public function getDoorSensorDevice()
    {
        return $this->manager->getRepository('App:SmartDevice')->findBy(['type' => 'Sensor', 'alerte' => 'Opening', 'cleverBox'   => $this->cleverBox->getId()]);
    }

    /**
     * Number of Alarm Devices 
     *
     * @return integer|null
     */
    public function getNumberOfAlarm(): ?int
    {
        return count($this->manager->getRepository('App:SmartDevice')->findBy(['type' => 'Alarm', 'cleverBox'   => $this->cleverBox->getId()]));
    }

    public function getAlarmDevice()
    {
        return $this->manager->getRepository('App:SmartDevice')->findBy(['type' => 'Alarm', 'cleverBox'   => $this->cleverBox->getId()]);
    }

    /**
     * Number of Interior Lights Devices 
     *
     * @return integer|null
     */
    public function getNumberOfLightInt(): ?int
    {
        return count($this->manager->getRepository('App:SmartDevice')->findBy(['type' => 'Light', 'specification' => 'Interior', 'cleverBox'   => $this->cleverBox->getId()]));
    }

    public function getLightIntDevice()
    {
        return $this->manager->getRepository('App:SmartDevice')->findBy(['type' => 'Light', 'specification' => 'Interior', 'cleverBox'    => $this->cleverBox->getId()]);
    }

    /**
     * Number of Exterior Lights Devices 
     *
     * @return integer|null
     */
    public function getNumberOfLightExt(): ?int
    {
        return count($this->manager->getRepository('App:SmartDevice')->findBy(['type' => 'Light', 'specification' => 'Exterior', 'cleverBox'   => $this->cleverBox->getId()]));
    }

    public function getLightExtDevice()
    {
        return $this->manager->getRepository('App:SmartDevice')->findBy(['type' => 'Light', 'specification' => 'Exterior', 'cleverBox'    => $this->cleverBox->getId()]);
    }

    /**
     * Number of Appliance Tv Devices 
     *
     * @return integer|null
     */
    public function getNumberOfTv(): ?int
    {
        return count($this->manager->getRepository('App:SmartDevice')->findBy(['type' => 'Appliance', 'specification' => 'Tv', 'cleverBox'   => $this->cleverBox->getId()]));
    }

    public function getTvDevice()
    {
        return $this->manager->getRepository('App:SmartDevice')->findBy(['type' => 'Appliance', 'specification' => 'Tv', 'cleverBox'  => $this->cleverBox->getId()]);
    }

    /**
     * Number of Climate Fan Devices 
     *
     * @return integer|null
     */
    public function getNumberOfFan(): ?int
    {
        return count($this->manager->getRepository('App:SmartDevice')->findBy(['type' => 'Climate', 'specification' => 'Fan', 'cleverBox'   => $this->cleverBox->getId()]));
    }

    public function getFanDevice()
    {
        return $this->manager->getRepository('App:SmartDevice')->findBy(['type' => 'Climate', 'specification' => 'Fan', 'cleverBox'   => $this->cleverBox->getId()]);
    }

    /**
     * Number of Climate Clim Devices 
     *
     * @return integer|null
     */
    public function getNumberOfClim(): ?int
    {
        return count($this->manager->getRepository('App:SmartDevice')->findBy(['type' => 'Climate', 'specification' => 'CLIM', 'cleverBox'   => $this->cleverBox->getId()]));
    }

    public function getClimDevice()
    {
        return $this->manager->getRepository('App:SmartDevice')->findBy(['type' => 'Climate', 'specification' => 'CLIM', 'cleverBox'  => $this->cleverBox->getId()]);
    }

    /**
     * Number of Appliance WashMachine Devices 
     *
     * @return integer|null
     */
    public function getNumberOfWashMachine(): ?int
    {
        return count($this->manager->getRepository('App:SmartDevice')->findBy(['type' => 'Appliance', 'specification' => 'Wash Machine', 'cleverBox'   => $this->cleverBox->getId()]));
    }

    public function getWashMachineDevice()
    {
        return $this->manager->getRepository('App:SmartDevice')->findBy(['type' => 'Appliance', 'specification' => 'Wash Machine', 'cleverBox'    => $this->cleverBox->getId()]);
    }

    /**
     * Number of Appliance Fridge Devices 
     *
     * @return integer|null
     */
    public function getNumberOfFridge(): ?int
    {
        return count($this->manager->getRepository('App:SmartDevice')->findBy(['type' => 'Appliance', 'specification' => 'Fridge', 'cleverBox'   => $this->cleverBox->getId()]));
    }

    public function getFridgeDevice()
    {
        return $this->manager->getRepository('App:SmartDevice')->findBy(['type' => 'Appliance', 'specification' => 'Fridge', 'cleverBox'  => $this->cleverBox->getId()]);
    }
    /**
     * Number of Energy Smart Meter Devices 
     *
     * @return integer|null
     */
    public function getNumberOfEnergySmartMeter(): ?int
    {
        return count($this->manager->getRepository('App:SmartDevice')->findBy(['type' => 'Smart Meter', 'cleverBox'   => $this->cleverBox->getId()]));
    }

    public function getEnergySmartMeterDevice()
    {
        return $this->manager->getRepository('App:SmartDevice')->findBy(['type' => 'Smart Meter', 'cleverBox' => $this->cleverBox->getId()]);
    }

    public function getNumberOfEmergencyBtn(): ?int
    {
        return count($this->manager->getRepository('App:SmartDevice')->findBy(['type' => 'Emergency', 'cleverBox'   => $this->cleverBox->getId()]));
    }

    public function getEmergencyBtnDevice()
    {
        return $this->manager->getRepository('App:SmartDevice')->findBy(['type' => 'Emergency', 'cleverBox'   => $this->cleverBox->getId()]);
    }

    public function getNumberOfEachDevice(): ?array
    {

        $tab = [];
        // $tab['NbCamera']            = $this->getNumberOfCamera();
        // $tab['NbMotionSensor']      = $this->getNumberOfMotionSensor();
        // $tab['NbFireSensor']        = $this->getNumberOfFireSensor();
        // $tab['NbFloodSensor']       = $this->getNumberOfFloodSensor();
        // $tab['NbDoorSensor']        = $this->getNumberOfDoorSensor();
        // $tab['NbEmergency']         = $this->getNumberOfEmergencyBtn();
        // $tab['NbAlarm']             = $this->getNumberOfAlarm();
        $tab['NbLightInt']          = $this->getNumberOfLightInt();
        $tab['NbLightExt']          = $this->getNumberOfLightExt();
        $tab['NbTv']                = $this->getNumberOfTv();
        // $tab['NbFan']               = $this->getNumberOfFan();
        $tab['NbClim']              = $this->getNumberOfClim();
        $tab['NbWashMachine']       = $this->getNumberOfWashMachine();
        $tab['NbEnergySmartMeter']  = $this->getNumberOfEnergySmartMeter();
        $tab['NbFridge']            = $this->getNumberOfFridge();


        return $tab;
    }

    public function getDevices(): ?array
    {

        $tabDevices = [];

        // $tabDevices['Camera']      = $this->getCameraDevice();
        // $tabDevices['Motion']      = $this->getMotionSensorDevice();
        // $tabDevices['Fire']        = $this->getFireSensorDevice();
        // $tabDevices['Flood']       = $this->getFloodSensorDevice();
        // $tabDevices['Door']        = $this->getDoorSensorDevice();
        // $tabDevices['Emergency']   = $this->getEmergencyBtnDevice();
        // $tabDevices['Alarm']       = $this->getAlarmDevice();
        $tabDevices['LightInt']    = $this->getLightIntDevice();
        $tabDevices['LightExt']    = $this->getLightExtDevice();
        $tabDevices['Tv']          = $this->getTvDevice();
        // $tabDevices['Fan']         = $this->getFanDevice();
        $tabDevices['Clim']        = $this->getClimDevice();
        $tabDevices['WashMachine'] = $this->getWashMachineDevice();
        $tabDevices['Fridge']      = $this->getFridgeDevice();
        $tabDevices['EnergySmartMeter']      = $this->getEnergySmartMeterDevice();

        return $tabDevices;
    }
}
