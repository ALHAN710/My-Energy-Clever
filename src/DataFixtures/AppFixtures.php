<?php

namespace App\DataFixtures;

use Faker\Factory;
use App\Entity\Site;
use App\Entity\User;
use App\Entity\Zone;
use DateTimeImmutable;
use App\Entity\SmartMod;
use App\Entity\CleverBox;
use App\Entity\Enterprise;
use Cocur\Slugify\Slugify;
use App\Entity\SmartDevice;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager)
    {
        $faker = Factory::create('fr_FR');
        $faker->seed(1337);

        $genders = ['male', 'female'];

        $enterprise = new Enterprise();
        $enterprise->setSocialReason('ST DIGITAL CAMEROUN')
            ->setAddress('75 rue Alliance Française – Imm Entrelec Bali BP 32 Douala')
            ->setPhoneNumber('(+ 237) 243702420 / (+ 237) 696963163')
            ->setCountry('Cameroon')
            ->setIsActive(true)
            ->setEmail('info@st.digital');
        $manager->persist($enterprise);

        $superAdminUser1 = new User();
        $superAdminUser2 = new User();
        $adminUser = new User();
        // $date = new DateTimeImmutable(date('Y-m-d H:i:s'));

        $superAdminUser1->setFirstName('Pascal')
            ->setLastName('ALHADOUM')
            ->setEmail('alhadoumpascal@gmail.com')
            ->setPassword($this->passwordHasher->hashPassword($superAdminUser1, 'password'))
            ->setCountryCode('+237')
            ->setPhoneNumber('690442311')
            //->setCreatedAt($date)
            ->setIsVerified(true)
            ->setIsActive(true)
            ->setRoles(['ROLE_SUPER_ADMIN']);

        $manager->persist($superAdminUser1);

        // $date = new DateTimeImmutable(date('Y-m-d H:i:s'));

        $superAdminUser2->setFirstName('Cabrel')
            ->setLastName('MBAKAM')
            ->setEmail('cabrelmbakam@gmail.com')
            ->setPassword($this->passwordHasher->hashPassword($superAdminUser2, 'password'))
            ->setCountryCode('+237')
            ->setPhoneNumber('690304593')
            //->setCreatedAt($date)
            ->setIsVerified(true)
            ->setIsActive(true)
            ->setRoles(['ROLE_SUPER_ADMIN']);

        $manager->persist($superAdminUser2);

        // $date = new DateTimeImmutable(date('Y-m-d H:i:s'));

        $adminUser = new User();
        $adminUser->setEmail('jean-francis@st.digital')
            ->setFirstName('Jean-francis')
            ->setLastName('AHANDA')
            ->setPassword($this->passwordHasher->hashPassword($superAdminUser1, 'password'))
            ->setRoles(['ROLE_ADMIN'])
            ->setEnterprise($enterprise)
            ->setIsVerified(true)
            ->setPhoneNumber('695385802')
            ->setIsActive(true)
            ->setCountryCode('+237');

        $manager->persist($adminUser);

        //Nous gérons les Sites
        $sites = [];

        //Site dataCenter du client STD
        $dataCenter = new Site();
        $dataCenter->setName('Datacenter Services Douala')
            ->setSubscription('MT')
            ->setSubscriptionUsage('Non Residential')
            ->setSubscriptionType('TRIPHASE')
            ->setActivityArea('Datacenter')
            ->setCurrency('XAF')
            ->setPowerSubscribed(245)
            ->setEnterprise($enterprise);
        $sites[] = $dataCenter;
        $manager->persist($dataCenter);

        //Site Head Office du client STD
        $headOffice = new Site();
        $headOffice->setName('ST DIGITAL Head Office Douala')
            ->setSubscription('MT')
            ->setSubscriptionUsage('Non Residential')
            ->setSubscriptionType('MONOPHASE')
            ->setActivityArea('Administration')
            ->setCurrency('XAF')
            ->setPowerSubscribed(245)
            ->setEnterprise($enterprise);
        $sites[] = $headOffice;
        $manager->persist($headOffice);

        //Nous gérons les Zones

        //Zone Salle Serveur du site dataCenter
        $salleServeur = new Zone();
        $salleServeur->setName('Salle serveur')
            ->setPsous(50)
            ->setSite($dataCenter);
        $manager->persist($salleServeur);

        //Nous gérons les CleverBox et les équipements connectées
        $cleverBoxName = ['Datacenter CleverBox', 'Head Office CleverBox'];
        //$cleverBoxes    = [];
        $slugify  = new Slugify();

        $devicesTypes  = ['Light', 'Appliance', 'Climate'];
        $lightTypes    = ['Interior', 'Exterior'];
        $lightName     = ['ECL Bureau DG', 'ECL Secrétariat', 'ECL Parking', 'ECL Clôture'];
        $applianceName = ['Prise TV DG', 'Prise TV DGA'];
        $climatName    = ['CLIM DG', 'CLIM Sécrétariat DG'];
        $names         = [$lightName, $applianceName, $climatName];

        for ($i = 0; $i < 2; $i++) {
            $cleverBox = new CleverBox();

            $cleverBox->setName($cleverBoxName[$i])
                ->setBoxId('' . $faker->unique()->randomNumber($nbDigits = 8, $strict = false))
                ->setSlug($slugify->slugify($cleverBoxName[$i]))
                ->setSite($sites[$i]);
            // if ($i == 0) $cleverBox->setZone($salleServeur);
            // else $cleverBox->setSite($sites[$i]);
            if ($i === 1) {
                $j = 0;
                foreach ($devicesTypes as $deviceType) {

                    for ($i = 0; $i < count($names[$j]); $i++) {
                        $smartDevice = new SmartDevice();

                        $smartDevice->setModuleId('' . $faker->unique()->randomNumber($nbDigits = 8, $strict = false))
                            ->setType($deviceType)
                            ->setCleverBox($cleverBox)
                            ->setName($faker->unique()->randomElement($names[$j]));
                        if ($deviceType === 'Light') $smartDevice->setSpecification($faker->randomElement($lightTypes));
                        else if ($deviceType === 'Climate') $smartDevice->setSpecification('CLIM');

                        $manager->persist($smartDevice);
                    }
                    $j++;
                }
            }
            $manager->persist($cleverBox);
            //$cleverBoxes[] = $cleverBox;
        }

        //Nous gérons les SmartMods
        foreach ($sites as $site) {
            $smartModGRID = new SmartMod();
            $smartModGRID->setName('Livraison ENEO')
                ->setModuleId($faker->unique()->randomNumber($nbDigits = 8, $strict = false))
                ->setNbPhases(3)
                ->setModType('GRID')
                ->setSite($site);
            $manager->persist($smartModGRID);

            $smartModFUEL = new SmartMod();
            $smartModFUEL->setName('Livraison Groupe Electrogène')
                ->setModuleId($faker->unique()->randomNumber($nbDigits = 8, $strict = false))
                ->setNbPhases(3)
                ->setModType('FUEL')
                ->setSite($site)
                ->setPower(50)
                ->setFuelPrice(576);
            $manager->persist($smartModFUEL);
        }

        $manager->flush();
    }
}
