<?php

namespace SHLML\CoreBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;

class DocumentAdmin extends Admin
{
    // Fields to be shown on create/edit forms
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('file','file', array('label' => 'Fichier', 'required' => false))
            ->add('name', null, array('label' => 'Nom'))
            ->add('content', null, array('label' => 'Contenu'))
            ->add('book', 'sonata_type_model', array('label' => 'Volume'))
        ;
    }

    // Fields to be shown on filter forms
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('name', null, array('label' => 'Nom'))
            ->add('book', null, array('label' => 'Volume'))
        ;
    }

    // Fields to be shown on lists
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->add('name', null, array('label' => 'Nom'))
            ->add('book', null, array('label' => 'Volume'))
        ;
    }

    public function prePersist($document) {
        $this->manageFileUpload($document);
    }

    public function preUpdate($document) {
        $this->manageFileUpload($document);
    }

    private function manageFileUpload($document) {
        if ($document->file) {
            $document->refreshUpdated();
        }
    }
}