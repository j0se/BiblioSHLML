<?php

namespace SHLML\CoreBundle\Controller;

use Elastica\Transport\Null;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use SHLML\CoreBundle\Form\DocumentType;
use SHLML\CoreBundle\Entity\Document;
use Symfony\Component\HttpFoundation\Request;

mb_internal_encoding('UTF-8');


class DocumentController extends Controller
{



    /*
     *@term
     */
    public function searchWithElasticSearch($term, $selectedDocument=NULL){

        function auto_fuzzy($term){
            if (strlen($term)<=5) $fuzzy_int=1;
            elseif (strlen($term)<=8) $fuzzy_int=2;
            elseif (strlen($term)<=11) $fuzzy_int=3;
            elseif (strlen($term)<=14) $fuzzy_int=4;
            else $fuzzy_int=5;
            return $fuzzy_int;
        }

        $terms=array();
        $finder = $this->container->get('fos_elastica.finder.shlml.word');
        $terms = explode(" ",$term);


        $found = array();
        for($i=0;$i<sizeof($terms);$i++){
            $query = new \Elastica\Query\Fuzzy();
            $query->setField("content",$terms[$i]);
            $fuzzy_int = auto_fuzzy($terms[$i]);
            $query->setFieldOption("fuzziness",$fuzzy_int);
            $query->setFieldOption("max_expansions",10);
            array_push($found,$finder->find($query,1000000));
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

            $query = new \Elastica\Query\Match();
            $query->setFieldQuery('content', $combinations[$i]);
            $query->setFieldType('content','phrase');
            $docs = $finder->find($query,1000000);

            if ($docs != null) {
                $results[$combinations[$i]] = array();
                for ($j = 0; $j < sizeof($docs); $j++) {
                    if($this->get('security.context')->isGranted('ROLE_USER') || $docs[$j]->getPublic()) {
                        array_push($results[$combinations[$i]], $docs[$j]->getPath());
                    }
                }
            }
        }

        if ($selectedDocument!=NULL) {
            foreach ($results as $res) {
                if (!in_array($selectedDocument, $res)) {
                    unset($results[key($res)]);
                }
            }
        }
        return $results;
    }





    public function indexAction()
    {
        return $this->render('SHLMLCoreBundle:SearchDocument:index.html.twig');
    }







    public function singleSearchPageAction(Request $request)
    {

        $selectedDocument = 'SHLML_WIENER_A_03-part-0.pdf';
        $wordList=array();

        if (isset($_POST['searchedWord'])) {
            if (isset($_POST['soughtWord'])) {
                if ($_POST['soughtWord']!=$_POST['searchedWord']){
                    $searchedWord = strtolower($_POST['searchedWord']);
                    $selectedWord = strtolower($_POST['searchedWord']);
                } else {
                    $searchedWord = $_POST['searchedWord'];
                    if (isset($_POST['selectedWord'])) {
                        $selectedWord = strtolower($_POST['selectedWord']);
                    } else $selectedWord = $searchedWord;
                }
            }
        } else {
            $searchedWord = "nancy";
            if (isset($_POST['selectedWord'])) {
                $selectedWord = strtolower($_POST['selectedWord']);
            } else $selectedWord = $searchedWord;
        }

        $allDocumentList = \SHLML\CoreBundle\Controller\DocumentController::searchWithElasticSearch($searchedWord,$selectedDocument);
        $allWordList=array_keys($allDocumentList);

        foreach ($allWordList as $word){
            if (in_array($selectedDocument,$allDocumentList[$word])){
                array_push($wordList,$word);
            }
        }
        if (array_key_exists($selectedWord,$allDocumentList)) {
            $documentList = $allDocumentList[$selectedWord];
        }
        if (!array_key_exists($searchedWord,$allDocumentList)) {
            array_unshift($wordList,$searchedWord);
        }

        $selectedIndex = array_search($selectedWord,$wordList);;

        return $this->render(
            'SHLMLCoreBundle:SearchDocument:singleSearch.html.twig',
            array(
                "searchedWord" => $searchedWord,
                "selectedWord" => $selectedWord,
                "selectedIndex" => $selectedIndex,
                "selectedDocument" => $selectedDocument,
                "wordList" => $wordList,
            )
        ); //, array('posts' => $posts));
    }








    public function multipleSearchPageAction(Request $request)
    {
        //var_dump(strtolower($_POST['searchedWord']));
       //if (isset($_POST['searchedWord'])) $searchedWord = mb_convert_encoding( strtolower($_POST['searchedWord']), "UTF-8");
        //if (isset($_POST['searchedWord'])) $searchedWord = strtolower($_POST['searchedWord']);
       if (isset($_POST['searchedWord'])) {
            if (mb_check_encoding($_POST['searchedWord'],'UTF-8')) $searchedWord = iconv('UTF-8', 'UTF-8', $_POST['searchedWord']);
            else $searchedWord = iconv('ISO-8859-1', 'UTF-8', $_POST['searchedWord']);
       } else $searchedWord =  "nancy";

        if (isset($_POST['selectedWord'])){
            if (mb_check_encoding($_POST['selectedWord'],'UTF-8')) $selectedWord = iconv('UTF-8', 'UTF-8', $_POST['selectedWord']);
            else $selectedWord = iconv('ISO-8859-1', 'UTF-8', $_POST['selectedWord']);
        } else $selectedWord = $searchedWord;


        $allDocumentList = \SHLML\CoreBundle\Controller\DocumentController::searchWithElasticSearch($searchedWord);
        $wordList=array_keys($allDocumentList);

        /*
        foreach ($wordList as $word){
            $word = mb_convert_encoding($word, "UTF-8");
        }
        */

        if (array_key_exists($selectedWord,$allDocumentList)) {
            $documentList = $allDocumentList[$selectedWord];
        } else {
            $documentList = array("mot_introuvable.pdf");
        }

        if (!array_key_exists($searchedWord,$allDocumentList)) {
            array_unshift($wordList,$searchedWord);
        }

        $selectedIndex = array_search($selectedWord,$wordList);

        return $this->render(
            'SHLMLCoreBundle:SearchDocument:multipleSearch.html.twig',
            array(
                "searchedWord" => $searchedWord,
                "selectedWord" => $selectedWord,
                "selectedIndex" => $selectedIndex,
                "wordList" => $wordList,
                "documentList" => $documentList)
        );
    }








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



}
