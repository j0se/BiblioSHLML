<?php

namespace SHLML\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use SHLML\CoreBundle\Form\DocumentType;
use SHLML\CoreBundle\Entity\Document;

class DocumentController extends Controller
{
    public function uploadAction()
    {
        $document = new Document();
        $form = $this->createForm(new DocumentType(), $document);

        if ($this->getRequest()->isMethod('POST')) {
            $form->handleRequest($this->getRequest());
            if ($form->isValid()) {
                $em = $this->getDoctrine()->getManager();

                $em->persist($document);
                $em->flush();

                //$this->redirect($this->generateUrl(...));
            }
        }

        return $this->render('SHLMLCoreBundle:Document:upload.html.twig', array("form"=>$form));
    }

    public function product($array1,$array2){
        $res = array();
        for($i=0;$i<sizeof($array2);$i++){
            for($j=0;$j<sizeof($array1);$j++){
                array_push($res,$array1[$j]." ".$array2[$i]);
            }
        }
        return($res);
    }

    public function searchAction($term){
        $finder = $this->container->get('fos_elastica.finder.shlml.word');
        $terms = explode(" ",$term);
        var_dump($terms);
        $found = array();
        $query = new \Elastica\Query\Fuzzy();

        $query->setField("content",$terms[0]);
        $query->setFieldOption("fuzziness",2);
        array_push($found,$finder->find($query));
        for($i=1;$i<sizeof($terms);$i++){
            $query->getParams()['content']['value'] = $terms[$i];
            array_push($found,$finder->find($query));
        }

        $combinations = array();
        for($i=0;$i<sizeof($found[0]);$i++){
            array_push($combinations,$found[0][$i]->getContent());
        }

        for($i=1;$i<sizeof($found);$i++){
            $temp = array();
            for($j=0;$j<sizeof($found[$i]);$j++){
                for($k=0;$k<sizeof($combinations);$k++){
                    array_push($temp,$combinations[$k]." ".$found[$i][$j]->getContent());
                }
            }
            $combinations = $temp;
        }

        $finder = $this->container->get('fos_elastica.finder.shlml.document');
        $results = array();

        for ($i = 0; $i < sizeof($combinations); $i++) {
            $query->getParams()['content']['value'] = $combinations[$i];
            $docs = $finder->find($query);
            if ($docs != null) {
                $results[$combinations[$i]] = array();
                for ($j = 0; $j < sizeof($docs); $j++) {
                    if($this->get('security.context')->isGranted('ROLE_USER') || $docs[$j]->getPublic()) {
                        array_push($results[$combinations[$i]], $docs[$j]->getPath());
                    }
                }
            }
        }



        $words = $finder->find($query);
        var_dump($query);
        var_dump($words);
        //return $this->render('blog/results.html.twig', array('result'=>$results));
    }



}
