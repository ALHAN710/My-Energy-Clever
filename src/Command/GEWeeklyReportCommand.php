<?php
// src/Command/EndSubscriptionNotificationCommand.php
namespace App\Command;

use Api2Pdf\Api2Pdf;
use DateTime;
use Twig\Environment;
use App\Entity\SmartMod;
use App\Entity\Enterprise;
use Symfony\Component\Mime\Email;
use App\Message\UserNotificationMessage;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\SiteProDataAnalyticService;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class GEWeeklyReportCommand extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'app:GE-weekly-report';

    /**
     * EntityManagerInterface variable
     *
     * @var EntityManagerInterface
     */
    private $manager;

    /**
     * MessageBusInterface variable
     *
     * @var MessageBusInterface
     */
    private $messageBus;

    private $twig;

    private $siteProAnalytic;
    private $projectDirectory;
    private $router;

    /**
     * @var MailerInterface
     */
    private $mailer;

    public function __construct(string $projectDirectory, EntityManagerInterface $manager, MessageBusInterface $messageBus, SiteProDataAnalyticService $siteProAnalytic, Environment $twig, MailerInterface $mailer, UrlGeneratorInterface $router)
    {
        // best practices recommend to call the parent constructor first and
        // then set your own properties. That wouldn't work in this case
        // because configure() needs the properties set in this constructor
        parent::__construct();

        $this->manager         = $manager;
        $this->messageBus      = $messageBus;
        $this->twig            = $twig;
        $this->siteProAnalytic = $siteProAnalytic;
        $this->projectDirectory = $projectDirectory;
        $this->mailer = $mailer;
        $this->router = $router;

        
    }

    protected function configure(): void
    {
        // ...
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // ... put here the code to create the user
        // outputs multiple lines to the console (adding "\n" at the end of each line)
        

        $persist = false;
        // $enterprises = $this->manager->getRepository(Enterprise::class)->findAll();
        // ==== Recherche des dates de début et Fin de la semaine
        $nowDate = date('now');
        // echo "Now date : $nowDate";
        // dump("Now date : " . $nowDate);
        $date = new DateTime('now');
        $date->modify('-6 days');
        $week = $date->format("W");
        $year = $date->format("Y");
        // dump("Week Number : $week");

        $dates=$this->getStartAndEndDate($week,$year);
        // dump($dates);

        $startDate = new DateTime($dates['start_date'] . '00:00:00');
        $endDate   = new DateTime($dates['end_date'] . '23:59:59');
        // $startDate = new DateTime(date("Y-m-01", strtotime(date('Y-m-d'))) . '00:00:00');
        // $endDate   = new DateTime(date("Y-m-t", strtotime(date('Y-m-d'))) . '23:59:59');
//        dump($startDate);
//        dump($endDate);

        $smartMods = $this->manager->getRepository(SmartMod::class)->findAll();

        //On boucle sur les SmartMeter installées
        foreach ($smartMods as $smartMod) {
            if($smartMod->getSubType() == 'ModBus' || strpos($smartMod->getSubType(), 'FL') !== false){
                // dump($smartMod->getName());
                $site = $smartMod->getSite();
                // dump($site->getName());

                $url = $this->router->generate('genset_report', [
                    'slug' => $site->getSlug(),
                    'id'   => $smartMod->getId(),
                ]);
                // 
//                 dd($url);
//                $url = 'http://localhost:8000' . $url;
                $url = 'https://portal-myenergyclever.com' . $url;

                // In this case, we want to write the file in the public directory
                $publicDirectory = $this->projectDirectory . '/public';
                $pdfFilepath =  $publicDirectory . "/".$site->getName()."-GE-weekly-".$startDate->format('Y-m-d')."-report.pdf";
                
                // Write file to the desired path
                // file_put_contents($pdfFilepath, $pdf);

//                $fileName = $site->getName()."-Analysis-weekly-".$startDate->format('Y-m-d')."-report.pdf";
                $fileName = $publicDirectory . "/".$site->getName()."-GE-weekly-".$startDate->format('Y-m-d')."-report.pdf";

                $apiClient = new Api2Pdf('890e0976-3434-45e3-8180-af8a6ac17dfa');
//                Convert URL to PDF (load PDF in browser window and specify a file name)
                $options = [
                    "landscape" => true,
                    "delay" => 5000
                ];

                $result = $apiClient->chromeUrlToPdf($url, $inline = false, $filename = $fileName, $options = $options);
//                $result = $apiClient->chromeUrlToPdf('https://localhost:8000/installation/neptune-oil-dt-6/genset-report/10', $inline = false, $filename = $fileName, $options = $options);
                $file = $result->getFile();
//                dump($result);
                dump($file);
                // Initialize a file URL to the variable
//                $url = "https://storage.googleapis.com/a2p-v2-storage/1fb9f8f7-b5ee-40a5-a88e-a8b9782f3b69";
                $url = $file;

                // Use basename() function to return the base name of file
                $file_name = $publicDirectory . "/" . basename($url) . ".pdf";
//                $file_name = $publicDirectory . "/" . $fileName . ".pdf";

                // Use file_get_contents() function to get the file
                // from url and use file_put_contents() function to
                // save the file by using base name
                if (file_put_contents($file_name, file_get_contents($url)))
                {
                    echo "File downloaded successfully\n";
                    if(rename( $file_name, $fileName))
                    {
                        echo "Successfully Renamed $file_name to $fileName" ;

                        $object = "Rapport d'activité Hebdomadaire du Groupe électrogène";
                        $content = "Le " . date('d/m/Y H:i:s') . " GMT+001
        
Cher(e) Client(e),

Ci-joint le rapport d'activité de la semaine allant du " . $startDate->format('d/m/Y') . " Au " . $endDate->format('d/m/Y') . " du groupe électrogène du site " . $site->getName() . "

Cordialement,
L'équipe My Energy CLEVER";
                        /*foreach ($site->getEnterprise()->getUsers() as $user) {
                            if ($user->getRoles()[0] === 'ROLE_ADMIN') {
                                $to = $user->getEmail();
                                $email = (new Email())
                                    // ->from('stdigital.powermon.alerts@gmail.com')
                                    // ->from('noc@datacenter-services.net')
                                    ->from('noreply@portal-myenergyclever.com')
                                    ->to($to)
                                    //->addTo('cabrelmbakam@gmail.com')
                                    //->cc('cabrelmbakam@gmail.com')
                                    //->bcc('bcc@example.com')
                                    //->replyTo('fabien@example.com')
                                    //->priority(Email::PRIORITY_HIGH)
                                    ->subject($object)
                                    ->text($content)
                                    ->attachFromPath($fileName);
                                //                   ->attach($pdf, sprintf('%s-GE-weekly-%s-report.pdf', $site->getName(), $startDate->format('Y-m-d')));
                                //->html('<p>See Twig integration for better HTML integration!</p>');

                                //sleep(10);
                                $this->mailer->send($email);
                            }
                            //$messageBus->dispatch(new UserNotificationMessage($user->getId(), $message, 'SMS', ''));
                        }*/

                        //$adminUsers = [];
                        $Users = $this->manager->getRepository('App:User')->findAll();
                        foreach ($Users as $user) {
                            if ($user->getRoles()[0] === 'ROLE_SUPER_ADMIN') {
                                //$adminUsers[] = $user;
                                $to = $user->getEmail();
                                $email = (new Email())
                                    // ->from('stdigital.powermon.alerts@gmail.com')
                                    // ->from('noc@datacenter-services.net')
                                    ->from('noreply@portal-myenergyclever.com')
                                    ->to($to)
                                    //->addTo('cabrelmbakam@gmail.com')
                                    //->cc('cabrelmbakam@gmail.com')
                                    //->bcc('bcc@example.com')
                                    //->replyTo('fabien@example.com')
                                    //->priority(Email::PRIORITY_HIGH)
                                    ->subject($object)
                                    ->text($content)
                                    ->attachFromPath($fileName);
                                //                   ->attach($pdf, sprintf('%s-GE-weekly-%s-report.pdf', $site->getName(), $startDate->format('Y-m-d')));
                                //->html('<p>See Twig integration for better HTML integration!</p>');

                                //sleep(10);
                                $this->mailer->send($email);
                            }
                        }

                    }
                    else
                    {
                        echo "A File With The Same Name Already Exists" ;
                    }
                }
                else
                {
                    echo "File downloading failed.";
                }
//                file_put_contents($pdfFilepath, $file);

//                break;
            }
        }


        // this method must return an integer number with the "exit status code"
        // of the command. You can also use these constants to make code more readable

        // return this if there was no problem running the command
        // (it's equivalent to returning int(0))
        return Command::SUCCESS;

        // or return this if some error happened during the execution
        // (it's equivalent to returning int(1))
        // return Command::FAILURE;

        // or return this to indicate incorrect command usage; e.g. invalid options
        // or missing arguments (it's equivalent to returning int(2))
        // return Command::INVALID
    }

    function getStartAndEndDate($week, $year) {
        $dateTime = new DateTime();
        $dateTime->setISODate($year, $week);
        $result['start_date'] = $dateTime->format('Y-m-d');
        $dateTime->modify('+6 days');
        $result['end_date'] = $dateTime->format('Y-m-d');
        return $result;
    }

    /**
     * Permet d'ajouter la Notification à la file d'attente d'envoi de notification SMS/EMAIL
     *
     * @param Enterprise $enterprise
     * @param bool $isAlert
     * @return void
     */
    private function addNotifToQueue(Enterprise $enterprise, $isAlert = false)
    {
                
        $object  = 'FIN ABONNEMENT';
        $message = "Le " . date('d/m/Y H:i:s') . " GMT+000

Cher(e) Client(e),

Ci-joint le rapport d'activité du groupe électrogène du site "."


L'équipe My Energy CLEVER";
        

        foreach ($enterprise->getUsers() as $user) {
            if ($user->getRoles()[0] === 'ROLE_ADMIN') {
                $this->messageBus->dispatch(new UserNotificationMessage($user->getId(), $message, 'Email', $object));
            }
            //$messageBus->dispatch(new UserNotificationMessage($user->getId(), $message, 'SMS', ''));
        }

        //$adminUsers = [];
        $Users = $this->manager->getRepository('App:User')->findAll();
        foreach ($Users as $user) {
            if ($user->getRoles()[0] === 'ROLE_SUPER_ADMIN') {
                //$adminUsers[] = $user;
                $this->messageBus->dispatch(new UserNotificationMessage($user->getId(), $message, 'Email', $object));
            }
        }
    }
}
