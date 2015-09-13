<?php

namespace SHLML\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Word
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="SHLML\CoreBundle\Repository\WordRepository")
 * @UniqueEntity("content")
 */
class Word
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="content", type="string", length=255)
     */
    private $content;

    ///**
    // * @ORM\OneToMany(targetEntity="SHLML\CoreBundle\Entity\Document")
    // */
    //private $documents;


    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set content
     *
     * @param string $content
     * @return Word
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content
     *
     * @return string 
     */
    public function getContent()
    {
        return $this->content;
    }

    ///**
    // * Add documents
    // *
    // * @param \SHLML\CoreBundle\Entity\Document $documents
    // * @return Book
    // */
    //public function addDocument(\SHLML\CoreBundle\Entity\Document $documents)
    //{
    //    $this->documents[] = $documents;

    //    return $this;
    //}

    ///**
    // * Remove documents
    // *
    // * @param \SHLML\CoreBundle\Entity\Document $documents
    // */
    //public function removeDocument(\SHLML\CoreBundle\Entity\Document $documents)
    //{
    //    $this->documents->removeElement($documents);
    //}

    ///**
    // * Get documents
    // *
    // * @return \Doctrine\Common\Collections\Collection
    // */
    //public function getDocuments()
    //{
    //    return $this->documents;
    //}
}
