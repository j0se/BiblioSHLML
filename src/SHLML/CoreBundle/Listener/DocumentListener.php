<?php
/**
 * Created by PhpStorm.
 * User: emdou
 * Date: 14/09/2015
 * Time: 00:52
 */

namespace SHLML\CoreBundle\Listener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use SHLML\CoreBundle\Entity\Word;
use SHLML\CoreBundle\Entity\Document;

class DocumentListener
{
    public function postPersist(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        $em = $args->getEntityManager();
        $repository = $em->getRepository('SHLMLCoreBundle:Word');
        var_dump($entity);

        if ($entity instanceof Document) {
            var_dump(strtolower($entity->getContent()));
            $stripped = preg_replace("#[^a-zA-Z-0-9|é|è|à|ù|û|ê|â|ô|ë|'|\s]#", '', strtolower($entity->getContent()));
            var_dump(mb_convert_encoding($stripped,'UTF-8'));
            file_put_contents("C:\wamp\www\test.txt",$stripped,FILE_APPEND );

            $words = explode(" ",$stripped);
            foreach($words as $word){
                if($repository->findByContent($word) == null) {
                    $w = new Word();
                    $w->setContent($word);
                    $em->persist($w);
                    $em->flush();
                }
            }
            $em->flush();
        }
    }
}