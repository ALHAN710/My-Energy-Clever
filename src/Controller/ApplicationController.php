<?php

namespace App\Controller;

use Symfony\Component\Mime\Email;
use Symfony\Component\Notifier\Message\SmsMessage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ApplicationController extends AbstractController
{
    public function getJSONRequest($content): array
    {
        //$content = $request->getContent();
        //$content = $this->getContentAsArray($request);

        if (empty($content)) {
            throw new BadRequestHttpException("Content is empty");
            /*return $this->json([
                'code' => 400,
                'error' => "Content is empty"
            ], 400);*/
        }

        //$var = $this->json_validate($content);

        $paramJSON = json_decode($content, true); //json_decode("{\"date\":\"2020-03-20\",\"sa\":1.5}", true); 

        // switch and check possible JSON errors
        switch (json_last_error()) {
            case JSON_ERROR_NONE:
                $error = ''; // JSON is valid // No error has occurred
                break;
            case JSON_ERROR_DEPTH:
                $error = 'The maximum stack depth has been exceeded.';
                break;
            case JSON_ERROR_STATE_MISMATCH:
                $error = 'Invalid or malformed JSON.';
                break;
            case JSON_ERROR_CTRL_CHAR:
                $error = 'Control character error, possibly incorrectly encoded.';
                break;
            case JSON_ERROR_SYNTAX:
                $error = 'Syntax error, malformed JSON.';
                break;
                // PHP >= 5.3.3
            case JSON_ERROR_UTF8:
                $error = 'Malformed UTF-8 characters, possibly incorrectly encoded.';
                break;
                // PHP >= 5.5.0
            case JSON_ERROR_RECURSION:
                $error = 'One or more recursive references in the value to be encoded.';
                break;
                // PHP >= 5.5.0
            case JSON_ERROR_INF_OR_NAN:
                $error = 'One or more NAN or INF values in the value to be encoded.';
                break;
            case JSON_ERROR_UNSUPPORTED_TYPE:
                $error = 'A value of a type that cannot be encoded was given.';
                break;
            default:
                $error = 'Unknown JSON error occured.';
                break;
        }
        if (!empty($error)) {
            throw new BadRequestHttpException($error);
            /*return $this->json([
                'code' => 400,
                'error' => $error

            ], 400);*/
        }

        return  $paramJSON;
    }

    public function getRepoDevice($repoDevices): array
    {
        $devicesNb = $repoDevices->getNumberOfEachDevice();
        $devicesTab = $repoDevices->getDevices();
        //dump($devicesNb);
        //dump($devicesTab);

        $devices = array('devicesNb' => $devicesNb, 'devicesTab' => $devicesTab);

        return $devices;
    }

    /**
     * Undocumented function
     *
     * @param [MailerInterface] $mailer
     * @param [string] $addressMail
     * @param  $object
     * @param [string] $mess
     * @return void
     */
    public function sendEmail($mailer, $addressMail, $object, $mess)
    {
        $email = (new Email())
            ->from('alhadoumpascal@gmail.com')
            ->to($addressMail)
            //->addTo('cabrelmbakam@gmail.com')
            //->cc('cabrelmbakam@gmail.com')
            //->bcc('bcc@example.com')
            //->replyTo('fabien@example.com')
            //->priority(Email::PRIORITY_HIGH)
            ->subject($object)
            ->text($mess);
        //->html('<p>See Twig integration for better HTML integration!</p>');

        $mailer->send($email);

        // ...
    }

    /**
     * Undocumented function
     *
     * @param [TexterInterface] $texter
     * @param  $phoneNumber
     * @param  $mess
     * @return void
     */
    public function sendSMS($texter, $phoneNumber, $mess)
    {
        $sms = new SmsMessage(
            // the phone number to send the SMS message to
            $phoneNumber,
            // the message
            $mess
        );

        $texter->send($sms);

        // ...
    }
}
