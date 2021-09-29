<?php

namespace App\Controller;

use Faker\Factory;
use App\Entity\Enterprise;
use App\Entity\PasswordUpdate;
use App\Repository\UserRepository;
use Symfony\Component\Form\FormError;
use App\Message\UserNotificationMessage;
use Doctrine\ORM\EntityManagerInterface;
use App\Controller\ApplicationController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Messenger\MessageBusInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AccountConrollerController extends ApplicationController
{
    /**
     * Permet d'afficher et de traiter le formulaire de modification de profil
     *
     * @Route("/account/profile/{id<\d+>}", name="account_profile")
     * 
     * @Security("is_granted('ROLE_USER')", message = "Vous n'avez pas le droit d'accéder à cette ressource")
     * 
     * 
     * @return Response
     */
    //@IsGranted("ROLE_USER")
    public function profile(
        $id,
        Request $request,
        EntityManagerInterface $manager,
        UserRepository $repoUsers
    ) {
        $user = $repoUsers->findOneBy(['id' => $id]);


        //$lastAvatar = $user->getAvatar();

        //$filesystem = new Filesystem();

        //$slugify = new Slugify();

        //dump($user);
        $form = $this->createForm(AccountType::class, $user);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $manager->persist($user);
            $manager->flush();

            $this->addFlash(
                'success',
                "Profile changes have been successfully saved"
            );

            return $this->redirectToRoute('home_page');
        }


        return $this->render('account/profile.html.twig', [
            'form'           => $form->createView(),
            //'passwordUpdate' => $passwordUpdate,
            'user'           => $user,
        ]);
    }

    /**
     * Permet de modifier le mot de passe
     * 
     * @Route("/account/password-update/{id<\d+>}", name="account_password")
     * 
     * @Security("(is_granted('ROLE_USER') and user.id == id) or is_granted('ROLE_ADMIN') or is_granted('ROLE_SUPER_ADMIN')", message = "Vous n'avez pas le droit d'accéder à cette ressource")
     * 
     *
     * @return Response
     */
    public function updatePassword(
        $id,
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $manager
    ) {

        $user = $this->getUser();

        $passwordUpdate = new PasswordUpdate();
        //dump($user);
        //$form = $this->createForm(AccountType::class, $user);

        $formPassword = $this->createForm(PasswordUpdateType::class, $passwordUpdate);

        $formPassword->handleRequest($request);
        //dump($request);
        //dump($formPassword->isSubmitted());
        //dump($passwordUpdate);
        if ($formPassword->isSubmitted() && $formPassword->isValid()) {
            //1. Vérifier que le oldpassword soit le même que celui de l'user
            if (!password_verify($passwordUpdate->getOldPassword(), $user->getHash())) {
                //Gérer l'erreur
                $formPassword->get('oldPassword')->addError(new FormError("The password entered is not your current password"));
                //return $this->redirectToRoute($this->get('router')->generate('account_profile', ['_fragment' => 'password']));
                return $this->redirectToRoute('account_password', [
                    'id'  => $id,
                ]);
            } else {
                $newPassword = $passwordUpdate->getNewPassword();
                $hash = $passwordHasher->hashPassword($user, $newPassword);

                $user->setHash($hash);

                $manager->persist($user);
                $manager->flush();

                $this->addFlash(
                    'success',
                    "Your password has been successfully changed"
                );

                //return $this->redirectToRoute('smart_home_page', ['cleverbox'   => $cleverbox]);
            }
        } else if ($request->request->has('password_update')) {
            //dump($request->request);
        }

        return $this->render('account/password.html.twig', [
            'user'           => $user,
            'formPassword'   => $formPassword->createView(),

        ]);
    }

    /**
     * Permet d'envoyer un code réinitialisation de mot de passe d'un utilisateur à son adresse email
     * 
     * @Route("/account/recover/password", name="account_recoverpw")
     *
     * @return Response
     */
    public function recoverPassword()
    {
        return $this->render('account/recoverpw.html.twig');
    }

    /**
     * Permet de vérifier le code réinitialisation de mot de passe d'un utilisateur
     * 
     * @Route("/account/recover/password/code-verification", name="account_codeverification")
     *
     * @return void
     */
    public function codeVerification()
    {
        return $this->render('account/codeverification.html.twig');
    }

    /**
     * Permet de vérifier si l'adresse email entrer pour la réinitialisation de mot appartient à un utilisateur du site
     *
     * @Route("/account/recover/password/user-verification", name="account_userverification")
     * 
     * @param Request $request
     * @param UserRepository $userRepo
     * @param EntityManagerInterface $manager
     * @return JsonResponse
     * 
     */
    public function userVerification(Request $request, UserRepository $userRepo, EntityManagerInterface $manager, MessageBusInterface $messageBus)
    {
        $id = "";
        $paramJSON = $this->getJSONRequest($request->getContent());
        // dump($request->getContent());
        $email = $paramJSON['email'];
        // //dump($email);
        $user = $userRepo->findOneBy(['email' => $email]);
        if ($user != null) {
            $status = 200;
            $mess   = 'User exists';
            $faker = Factory::create('fr_FR');
            $codeVerification = $faker->randomNumber($nbDigits = 5, $strict = false);
            $user->setVerificationcode($codeVerification)
                ->setIsVerified(false);
            $manager->persist($user);
            $manager->flush();
            $code = 'MEC-' . $codeVerification . $user->getId();
            // //dump($code);
            //$object = "PASSWORD RESET";
            $message = 'Your verification code is ' . $code;
            $message .= "\nWe heard that you lost your password. Sorry about that !

But don’t worry ! You can use the following code to reset your password : " . $code . "

Thanks,
My Energy Clever Team";
            //$this->sendEmail($mailer, $email, $object, $message);
            $messageBus->dispatch(new UserNotificationMessage($user->getId(), $message, 'Reset', ''));
        } else if ($paramJSON['codeVerif'] != null) {
            $Verificationcode = $paramJSON['codeVerif'];
            $id = substr($Verificationcode, 5);
            $Verificationcode = substr($Verificationcode, 0, 5);
            $user = $userRepo->findOneBy(['id' => $id]);
            // //dump($id);
            // //dump($Verificationcode);
            // //dump($user);
            if ($user != null && $user->getIsVerified() == false) {
                $userCode = $user->getVerificationcode();
                if ($userCode == $Verificationcode) {
                    $status = 200;
                    $mess   = $id;
                }
            } else {
                $status = 403;
                $mess   = $Verificationcode;
            }
        } else if ($paramJSON['codeVerif'] == null) {
            $status = 403;
            $mess   = "User don't exists";
        }
        //$status = 200;
        //$mess = 'received email : ' . $email;
        return $this->json(
            [
                'code'    => $status,
                'message' => $mess,
                'id'    => $user->getIsVerified()
            ],
            200
        );
    }

    /**
     * Permet de vérifier si l'adresse email entrer pour la réinitialisation de mot appartient à un utilisateur du site
     *
     * @Route("/account/recover/password/reset", name="account_passwordReset")
     * 
     * @param Request $request
     * @param UserRepository $userRepo
     * @param EntityManagerInterface $manager
     * @return Response
     * 
     */
    public function passwordReset(Request $request, UserRepository $userRepo, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $manager)
    {
        $passwordUpdate = new PasswordUpdate();

        $user = $this->getUser();
        $id = $request->query->get('d');
        // //dump($id);
        $user = $userRepo->findOneBy(['id' => $id]);
        // //dump($user);

        $form = $this->createForm(PasswordUpdateType::class, $passwordUpdate);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($user->getIsVerified() == false) {
                $newPassword = $passwordUpdate->getNewPassword();
                $hash = $passwordHasher->hashPassword($user, $newPassword);

                $user->setPassword($hash)
                    ->setVerificationcode(null)
                    ->setIsVerified(true);

                $manager->persist($user);
                $manager->flush();

                /*$this->addFlash(
                    'success',
                    "Your password has been changed"
                );*/

                return $this->redirectToRoute('app_login');
            } else {
                $this->addFlash(
                    'danger',
                    "Unauthorized Modification"
                );
                /*return $this->render('account/resetpassword.html.twig', [
                    'form' => $form->createView()
                ]);*/
            }
        }

        return $this->render('account/passwordReset.html.twig', [
            'form' => $form->createView(),
            'user' => $user
        ]);
    }
}
