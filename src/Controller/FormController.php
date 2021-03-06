<?php

namespace App\Controller;

use App\Entity\Form;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Form\FormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\BrowserKit\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\String\Slugger\SluggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

class FormController extends AbstractController
{
    /**
     * @Route("/", name="form")
     */
    public function index(Request $request, EntityManagerInterface $em, SluggerInterface $slugger)
    {
        $fileForm = new Form();
        $form = $this->createForm(FormType::class, $fileForm);

        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            $fileForm->setTitle($form->get('title')->getData());
            $file = $form->get('file')->getData();
            if ($file) {

                $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();

                $file->move(
                    $this->getParameter('file_directory'),
                    $newFilename
                );

                $fileForm->setFile($newFilename);
            }
            $em->persist($fileForm);
            $em->flush();

            $this->addFlash(
                'notice', 'Form submitted!'
            );

            return $this->redirectToRoute('form');
        }

        return $this->render('form/index.html.twig', [
            'controller_name' => 'FormController',
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/forms", name="forms")
     * @IsGranted("ROLE_ADMIN")
     */
    public function forms(EntityManagerInterface $em)
    {
        $forms = $em->getRepository(Form::class)->findAll();

        return $this->render('form/forms.html.twig', [
            'forms' => $forms
        ]);
    }
}
