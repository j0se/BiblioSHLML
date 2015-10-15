<?php

namespace SHLML\CoreBundle\Controller;

use Elastica\Transport\Null;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use SHLML\CoreBundle\Form\DocumentType;
use SHLML\CoreBundle\Entity\Document;
use Symfony\Component\HttpFoundation\Request;
use Elastica\Query;

mb_internal_encoding('UTF-8');
ini_set('memory_limit', '1024M');

class DocumentController extends Controller
{

    /*
     * @searchedTerm
     * @selectedTerm
     */
    public function searchWithElasticSearch($searchedTerm, $selectedTerm, $selectedDocument=NULL){

	       function auto_fuzzy($term){
            if (strlen($term)<=2) $fuzzy_int=0;
            elseif (strlen($term)<=7) $fuzzy_int=1;
            elseif (strlen($term)<=12) $fuzzy_int=2;
            elseif (strlen($term)<=17) $fuzzy_int=3;
            else $fuzzy_int=4;
            return $fuzzy_int;
        }

		$terms=array();
        $finder = $this->container->get('fos_elastica.finder.shlml.word');

        // On explose le string d'entrée en tableau, en séparant les mots espacés par un espace
        $terms = explode(" ",$searchedTerm);

        // Je ne retiens que les trois premiers mots
        $terms = array_slice($terms, 0, 3);

        // Je trouve les mots "similaires" grâce à Elasticsearch
        $found = array();
        for($i=0;$i<sizeof($terms);$i++){
            $query = new \Elastica\Query\Fuzzy();
            $query->setField("content",$terms[$i]);
            $fuzzy_int = auto_fuzzy($terms[$i]);
            $query->setFieldOption("fuzziness",$fuzzy_int);
            array_push($found,$finder->find($query,10000000));
        }

        // Choisir le type de recherche en fonction du nombre de combinaisons pour éviter l'explosion combinatoire
        $nb_combi=1;
        foreach($found as $tab){
            $nb_combi = $nb_combi * sizeof($tab);
        }

        $combinations = array();
        if ($nb_combi < 1000) {
            for ($i = 0; $i < sizeof($found[0]); $i++) {
                array_push($combinations, $found[0][$i]->getContent());
            }
            for ($i = 1; $i < sizeof($found); $i++) {
                $temp = array();
                for ($j = 0; $j < sizeof($found[$i]); $j++) {
                    for ($k = 0; $k < sizeof($combinations); $k++) {
                        array_push($temp, $combinations[$k]." ".$found[$i][$j]->getContent());
                    }
                }
                $combinations = $temp;
            }
        } else {

            for($i = 0; $i < sizeof($found); $i++){
                for ($j = 0; $j < sizeof($found[$i]); $j++) {
                    $temp="";
                    for($k = 0; $k < sizeof($found); $k++) {
                        if($i!=$k){
                            $temp = $temp . " " . $terms[$k];
                        } else {
                            $temp = $temp . " " . $found[$k][$j]->getContent();
                        }
                    }
                    array_push($combinations,substr($temp,1));
                }
            }
        }


        $finder = $this->container->get('fos_elastica.finder.shlml.document');
        $results = array();
        for ($i = 0; $i < sizeof($combinations); $i++) {
            $query = new \Elastica\Query\Match();
            $query->setFieldQuery('content', $combinations[$i]);
            $query->setFieldType('content','phrase');
			if ($combinations[$i]==$searchedTerm){
				$docs = $finder->find($query,50);
			} else if ($combinations[$i]==$selectedTerm){
                $docs = $finder->find($query,50);
            } else {
				$docs = $finder->find($query,1);
			}

            if ($docs != null) {
                $results[$combinations[$i]] = array();
                for ($j = 0; $j < sizeof($docs); $j++) {
                  if($this->get('security.context')->isGranted('ROLE_USER') || $docs[$j]['public']){  //->getPublic()) {
                        array_push($results[$combinations[$i]], $docs[$j]['name']);//->getPath());
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

        $wordList = array_keys($results);
        $selectedDocList=null;

		//var_dump($results);
        if (empty($results)){
            $selectedDocList=array("mot_introuvable.pdf");
            $wordList=array($searchedTerm);
			if ($searchedTerm != $selectedTerm){
				array_push($wordList, $selectedTerm);
			}
        } else {
            if (array_search($searchedTerm, $wordList) !== false) {
                unset($wordList[array_search($searchedTerm, $wordList)]);
                array_unshift($wordList, $searchedTerm);
                $selectedDocList = $results[$searchedTerm];
            } else {
                array_unshift($wordList, $searchedTerm);
            }
			

            if (array_search($selectedTerm, $wordList) !== false) {
                $selectedDocList = $results[$selectedTerm];
            }  else {
                array_push($wordList, $selectedTerm);
            }

            if ($selectedDocList==null){
                $selectedDocList=array("mot_introuvable.pdf");
            }
        }

        $res=array($selectedDocList,$wordList);
        return $res;

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

        $allDocumentList = \SHLML\CoreBundle\Controller\DocumentController::searchWithElasticSearch($selectedWord,$selectedDocument);
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

        if (isset($_POST['searchedWord'])) {
            if (mb_check_encoding($_POST['searchedWord'],'UTF-8')) $searchedWord =  $_POST['searchedWord'];
            else $searchedWord = iconv('ISO-8859-1', 'UTF-8', $_POST['searchedWord']);
        } else $searchedWord =  "nancy";

        if (isset($_POST['selectedWord'])){
            if (mb_check_encoding($_POST['selectedWord'],'UTF-8')) $selectedWord =  $_POST['selectedWord'];
            else $selectedWord = iconv('ISO-8859-1', 'UTF-8', $_POST['selectedWord']);
        } else $selectedWord = $searchedWord;


        $result = \SHLML\CoreBundle\Controller\DocumentController::searchWithElasticSearch($searchedWord,$selectedWord);
        $docList = $result[0];
        $wordList=$result[1];

        $selectedIndex = array_search($selectedWord,$wordList);

        return $this->render(
            'SHLMLCoreBundle:SearchDocument:multipleSearch.html.twig',
            array(
                "searchedWord" => $searchedWord,
                "selectedWord" => $selectedWord,
                "selectedIndex" => $selectedIndex,
                "wordList" => $wordList,
                "documentList" => $docList)
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


    /*
      $test=array();
      $finder = $this->container->get('fos_elastica.finder.shlml.document');
      $query = new \Elastica\Query\Match();
      $query->setFieldQuery('content',$term);
      $query->setFieldType('content','phrase');
      //$query->setFieldOperator('content','and');
      //$fuzzy_int = auto_fuzzy($term);
      //$query->setFieldFuzziness('content',$fuzzy_int);
      $test=$finder->find($query,2000);

      var_dump($test);
      return $test;
      */

    //$fuzzy_int = auto_fuzzy($term);
    //$query->setFieldFuzziness('content',$fuzzy_int);
    // $query->setFieldBoost('content',100.0);
    //$query->setField('fields','name');
    //$query->setFieldType('content','phrase');
    // $query->setAnalyzer('and');
    //$query->setDefaultField('content');
    //$query->setFieldZeroTermsQuery('content');
    //$query->setFieldOption("fields",'name');
    //var_dump($query);


}
