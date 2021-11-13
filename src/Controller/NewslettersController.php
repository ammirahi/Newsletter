<?php

namespace App\Controller;

use App\Entity\Newsletters\Newsletters;
use App\Entity\Newsletters\Users;
use App\Form\NewsletterEditUsersType;
use App\Form\NewslettersType;
use App\Form\NewslettersUsersType;
use App\Message\SendNewsletterMessage;
use App\Repository\Newsletters\NewslettersRepository;
use App\Service\SendNewsletterService;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/newsletters", name="newsletters_")
 */
class NewslettersController extends AbstractController
{
    /**
     * @Route("/", name="home")
     */
    public function index(Request $request, MailerInterface $mailer): Response
    {
        $user = new Users();
        $form = $this->createForm(NewslettersUsersType::class, $user);

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $token = hash('sha256', uniqid());

            $user->setValidationToken($token);

            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();

            $data = $form->get('categories')->getData();
            foreach ($data as $element) {
                $newsletter = $element;
                $email = (new TemplatedEmail())
                ->from('newsletter@site.fr')
                ->to($user->getEmail())
                ->subject('Votre inscription à la newsletter')
                ->htmlTemplate('emails/inscription.html.twig')
                ->context(compact('user', 'newsletter', 'token'));

                $mailer->send($email);
            }

            $this->addFlash('message', 'Vous vous êtes inscrit avec succès, vous avez reçu un e-mail, veuillez vérifier votre boîte mail.');
            return $this->redirectToRoute('app_home');$em->flush();
        }

        return $this->render('newsletters/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/update/{id}/{token}", name="update")
     */
    public function confirm(Request $request, Users $user, $token): Response
    {
        if($user->getValidationToken() != $token){
            throw $this->createNotFoundException('Page non trouvée');
        }

        $form = $this->createForm(NewsletterEditUsersType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();

            $this->addFlash('message', 'Vous avez mettre à jour votre informations avec succès.');
            return $this->redirectToRoute('home');$em->flush();
        }

        return $this->render('newsletters/edit.html.twig', array(
            'user' => $user,
            'form' => $form->createView(),
        ));
    }

    // /**
    //  * @Route("/confirm/{id}/{token}", name="confirm")
    //  */
    // public function confirm(Users $user, $token): Response
    // {
    //     if($user->getValidationToken() != $token){
    //         throw $this->createNotFoundException('Page non trouvée');
    //     }

    //     $user->setIsValid(true);

    //     $em = $this->getDoctrine()->getManager();
    //     $em->persist($user);
    //     $em->flush();

    //     $this->addFlash('message', 'Compte activé');

    //     return $this->redirectToRoute('app_home');
    // }

    /**
     * @Route("/redact", name="redact")
     */
    public function redact(Request $request): Response
    {
        $newsletter = new Newsletters();
        $form = $this->createForm(NewslettersType::class, $newsletter);

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            $em = $this->getDoctrine()->getManager();
            $em->persist($newsletter);
            $em->flush();

            return $this->redirectToRoute('newsletters_list');
        }

        return $this->render('newsletters/redact.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/list", name="list")
     */
    public function list(NewslettersRepository $newsletters): Response
    {
        return $this->render('newsletters/list.html.twig', [
            'newsletters' => $newsletters->findAll()
        ]);
    }


    /**
     * @Route("/send/{id}", name="send")
     */
    public function send(Newsletters $newsletter, SendNewsletterService $sv, MessageBusInterface $messageBus): Response
    {
        $users = $newsletter->getCategories()->getUsers();

        foreach($users as $user){
            if($user->getIsValid()){
                $sv->send($user, $newsletter);
                // $messageBus->dispatch(new SendNewsletterMessage($user->getId(), $newsletter->getId()));
            } 
        }
        return $this->redirectToRoute('newsletters_list');
    }

    /**
     * @Route("/unsubscribe/{id}/{token}", name="unsubscribe")
     */
    public function unsubscribe(Users $user, $token): Response
    {
        if($user->getValidationToken() != $token){
            throw $this->createNotFoundException('Page non trouvée');
        }

        $em = $this->getDoctrine()->getManager();
        $em->remove($user);
        $em->flush();

        $this->addFlash('message', 'Vous vous désinscrivez de notre newsletter, vous ne recevrez plus aucun email.');

        return $this->redirectToRoute('home');
    }
}
