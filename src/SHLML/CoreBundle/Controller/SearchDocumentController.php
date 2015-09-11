<?php
/**
 * Created by PhpStorm.
 * User: LOICK
 * Date: 11/09/2015
 * Time: 17:16
 */

namespace SHLML\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SearchDocumentController extends Controller
{
    public function indexAction()
    {
        return $this->render('SHLMLCoreBundle:SearchDocument:index.html.twig');
    }


    /*
     * CHOIX A FAIRE : UTILISER GET POUR PERMETTRE LE PARTAGE D'URL DE RECHERCHE OU UTILISER POST ?
     */

    public function singleSearchPageAction(Request $request)
    {
        $word = "Nancy";
        if (isset($_POST['searchedWord'])) {
            $word = $_POST['searchedWord'];
        }

        // Requête SQL. Trouver la liste de document (complete) ou réduite pa rapport à des critère SQL. (Tome, auteurs, date...)
        $documentList = array("document_patriote.pdf", "test.pdf", "these.pdf", "test2.pdf", "test3.pdf");
        $doc = "document_patriote.pdf";
        if (isset($_POST['SelectDoc'])) {
            $doc = $_POST['SelectDoc'];
        }

        // Utilisation de Elastic Search. Trouver les mots similaires au mot $word contenu dans le pdf $doc selectionné
        $wordList = array($word, "vous", "lous", "nous");

        return $this->render(
            'SHLMLCoreBundle:SearchDocument:singleSearch.html.twig',
            array(
                "searchedWord" => $word,
                "chosenDoc" => $doc,
                "wordList" => $wordList,
                "documentList" => $documentList
            )
        ); //, array('posts' => $posts));
    }


    public function multipleSearchPageAction(Request $request)
    {
        $word = "Nancy";
        if (isset($_POST['fullSearch'])) {
            $word = $_POST['fullSearch'];
        }

        // Utilisation de Elastic Search. Trouver les mots similaires au mot $word contenu dans l'ensemble des pdf
        $wordList = array($word, "vous", "lous", "nous");

        // Utilisation de Elastic Search. Trouver tous les documents contenant exactement le mot $word.
        $documentList = array("document_patriote.pdf", "test.pdf", "these.pdf", "test2.pdf", "test3.pdf");

        return $this->render(
            'SHLMLCoreBundle:SearchDocument:multipleSearch.html.twig',
            array("fullSearch" => $word, "wordList" => $wordList, "documentList" => $documentList)
        ); //, array('posts' => $posts));
    }
}