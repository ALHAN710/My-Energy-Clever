<?php

namespace App\Subscriber;

use Symfony\Component\Mime\Email;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;

class FailedMessageSubscriber implements EventSubscriberInterface
{
    /**
     * @var MailerInterface   
     */
    private $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }
    public static function getSubscribedEvents()
    {
        return [
            WorkerMessageFailedEvent::class => 'onMessageFailed'
        ];
    }

    public function onMessageFailed(WorkerMessageFailedEvent $event)
    {
        $message = get_class($event->getEnvelope()->getMessage());
        $trace = $event->getThrowable()->getTraceAsString();

        $email = (new Email())
            ->from('stdigital.powermon.alerts@gmail.com')
            ->to('alhadoumpascal@gmail.com')
            //->addTo('cabrelmbakam@gmail.com')
            //->cc('cabrelmbakam@gmail.com')
            //->bcc('bcc@example.com')
            //->replyTo('fabien@example.com')
            //->priority(Email::PRIORITY_HIGH)
            ->subject("STD Technical Monitoring Web Portal Task Error")
            ->text(<<<TEXT
Une erreur est survenue lors du traitement d'une tâche asynchrone

{$message}

{$trace}
TEXT);
        //->html('<p>See Twig integration for better HTML integration!</p>');

        //sleep(10);
        $this->mailer->send($email);
    }
}
