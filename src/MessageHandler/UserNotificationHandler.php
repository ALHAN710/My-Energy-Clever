<?php

namespace App\MessageHandler;

use Exception;
use App\Entity\User;
//use App\Entity\User;
use Twig\Environment;
//use Twilio\Rest\Client;
use App\Entity\Contacts;
use Symfony\Component\Mime\Email;
use App\Message\UserNotificationMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Notifier\TexterInterface;
use Symfony\Component\Notifier\Message\SmsMessage;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class UserNotificationHandler implements MessageHandlerInterface
{
    /**
     * @var EntityManagerInterface 
     */
    private $em;

    /**
     * @var Environment 
     */
    private $twig;

    /**
     * @var MailerInterface
     */
    private $mailer;

    private $client;

    public function __construct(EntityManagerInterface $em, HttpClientInterface $client, Environment $twig, MailerInterface $mailer)
    {
        $this->em = $em;
        $this->twig = $twig;
        // $this->texter = $texter; TexterInterface $texter, 
        //$this->fromNumber = $fromNumber;
        $this->mailer = $mailer;
        $this->client = $client;
    }

    public function __invoke(UserNotificationMessage $notifMessage)
    {
        if ($notifMessage->getMedia() !== 'Reset')  $contact = $this->em->find(Contacts::class, $notifMessage->getUserId());
        else $contact = $this->em->find(User::class, $notifMessage->getUserId());

        if (!$contact) $contact = $this->em->find(User::class, $notifMessage->getUserId());

        if ($contact) {
            if ($notifMessage->getMedia() === 'Email') {
                $object = 'Alerte ' . $notifMessage->getObject();
                $to = $contact->getEmail();
                //if ($to) {
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
                    ->text($notifMessage->getMessage());
                //->html('<p>See Twig integration for better HTML integration!</p>');

                //sleep(10);
                $this->mailer->send($email);
                //}
            } else if ($notifMessage->getMedia() === 'Reset') {
                $object = "PASSWORD RESET";
                $to = $contact->getEmail();
                //if ($to) {
                $email = (new Email())
                    // ->from('noc@datacenter-services.net')
                    ->from('noreply@portal-myenergyclever.com')
                    ->to($to)
                    //->addTo('cabrelmbakam@gmail.com')
                    //->cc('cabrelmbakam@gmail.com')
                    //->bcc('bcc@example.com')
                    //->replyTo('fabien@example.com')
                    //->priority(Email::PRIORITY_HIGH)
                    ->subject($object)
                    ->text($notifMessage->getMessage());
                //->html('<p>See Twig integration for better HTML integration!</p>');

                //sleep(10);
                $this->mailer->send($email);
                //}
            } else if ($notifMessage->getMedia() === 'SMS') {
                $phoneNumber = $contact->getPhoneNumber();
                $phoneNumber = $contact->getCountryCode() . $phoneNumber;
                $message = "=== Alerte {$notifMessage->getObject()} ===%0A%0ASalut M. {$contact->getFirstName()}, {$notifMessage->getMessage()}";
                //throw new \Exception("Pas Possible");
                //dump($message);
                $this->client->request(
                    'POST',
                    "http://smsgw.gtsnetwork.cloud:22293/message?user=STDigital&pass=56@oAyWF&from=STDTechMon&to={$phoneNumber}&tag=GSM&text={$message}&id=1&dlrreq=0"
                );
                /*$phoneNumber = $contact->getUser() !== null ? $contact->getUser()->getPhoneNumber() : $contact->getPhoneNumber();
                $phoneNumber = $contact->getCountryCode() . $phoneNumber;
                $firstName = $contact->getUser() !== null ? $contact->getUser()->getFirstName() : $contact->getFirstName();
                $mess = "Hi {$firstName}, Alerte !!!. " . $notifMessage->getMessage();
                $sms = new SmsMessage(
                    // the phone number to send the SMS message to
                    $phoneNumber,
                    // the message
                    $mess
                );
                //sleep(10);
                $this->texter->send($sms);*/

                /*$toName = $user->getFirstName();
                $toNumber = $user->getPhoneNumber();
        
                $this->twilio->messages->create($toNumber, [
                    'from' => $this->fromNumber,
                    'body' => "Hi $toName! Test async send sms"
                ]);*/
            }
        }
    }
}
