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
        $found = array();

        foreach($terms as $term){
            $query = new \Elastica\Query\Fuzzy();
            $query->setField("content",$term);
            $query->setFieldOption("fuzziness",2);
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

        foreach($combinations as $c) {
            $query = new \Elastica\Query\Fuzzy();
            $query->setField("content",$c);
            $query->setFieldOption("fuzziness",2);
            $docs = $finder->find($query);
            //var_dump($docs);
            if ($docs != null) {
                $results[$c] = array();
                foreach($docs as $doc) {
                    if($this->get('security.context')->isGranted('ROLE_USER') || $doc->getPublic()) {
                        array_push($results[$c], $doc->getPath());
                    }
                }
            }
        }
        echo "result";
        var_dump($results);
        //return $this->render('blog/results.html.twig', array('result'=>$results));
    }



}
