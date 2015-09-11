<?php

namespace SHLML\AdminBundle\Controller;

use SHLML\UserBundle\Entity\User;
use SHLML\UserBundle\Form\UserType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use SHLML\CoreBundle\Entity\Document;
use SHLML\CoreBundle\Form\DocumentType;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AdminController extends Controller
{
    public function showDocumentsAction()
    {
        return $this->render('SHLMLAdminBundle:Admin:showDocuments.html.twig', array(
                // ...
            ));    }

    public function newDocumentAction()
    {
        $document = new Document();
        $form = $this->createForm(new DocumentType(), $document);

        if ($this->getRequest()->isMethod('POST')) {
            $form->handleRequest($this->getRequest());
            if ($form->isValid()) {
                $em = $this->getDoctrine()->getManager();

                $em->persist($document);
                $em->flush();

                $this->redirect($this->generateUrl('new_document'));
            }
        }
        return $this->render('SHLMLAdminBundle:Admin:newDocument.html.twig', array(
            'form' => $form->createView(),
            ));    }

    public function showUsersAction()
    {
        return $this->render('SHLMLAdminBundle:Admin:showUsers.html.twig', array(
                // ...
            ));    }

    public function newUserAction()
    {
        $user = new User();
        $form = $this->createForm(new UserType(), $user);

        if ($this->getRequest()->isMethod('POST')) {
            $form->handleRequest($this->getRequest());
            if ($form->isValid()) {
                $userManager = $this->get('fos_user.user_manager');
                $exists = $userManager->findUserBy(array('email' => $user->getEmail()));
                if ($exists instanceof User) {
                    throw new HttpException(409, 'Email already taken');
                }
                $user->setEnabled(true);
                $userManager->updateUser($user);

                $this->redirect($this->generateUrl('new_user'));
            }
        }

        return $this->render('SHLMLAdminBundle:Admin:newUser.html.twig', array(
            'form' => $form->createView(),
            ));    }

}
