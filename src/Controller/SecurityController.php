<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    /**
     * Page d'accueil de l'application
     * 
     * @Route("/", name="home_page")
     * 
     */
    public function home()
    {

        $user = $this->getUser();
        //dump($request->headers->get('X-AUTH-TOKEN'));
        //dump($user);
        //dump($error);
        //dump($username);
        if ($user !== NULL) {
            if ($user->getEnterprise()) {
                $enterprise = $user->getEnterprise();
                if ($enterprise->getAccountType() === 'PLUS') {
                    return $this->redirectToRoute('enterprise_show', ['slug' => $enterprise->getSlug()]);
                } else if ($enterprise->getAccountType() === 'PERSONNAL') {
                    //Si le nombre de site de l'entreprise de l'utilisateur connecté est > 1
                    if (count($enterprise->getSites()) > 0) {
                        return $this->redirectToRoute('site_show', ['slug' => $enterprise->getSites()[0]->getSlug()]);
                    }
                }
            }
        } else {
            return $this->redirectToRoute('app_login');
        }
    }

    /**
     * @Route("/login", name="app_login")
     */
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // if ($this->getUser()) {
        //     return $this->redirectToRoute('target_path');
        // }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'hasError' => $error]);
    }

    /**
     * @Route("/logout", name="app_logout")
     */
    public function logout()
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
