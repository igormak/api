<?php
namespace Admin\Form;

use Zend\Form\Form;
//use Zend\InputFilter\Factory as InputFactory;
//use Zend\InputFilter\InputFilter;

//use \Admin\Filter\AdminAddFilter;

class PageEditCategoryForm extends Form
{
    public function __construct($name = null)
    {
        parent::__construct('PageEditForm');
        $this->setAttribute('method', 'post');
        $this->setAttribute('class', 'bs-example form-horizontal');

        //$this->setInputFilter(new AdminAddInputFilter());

        $this->add(array(
            'name' => 'tegTitle',
            'type' => 'Text',
            'options' => array(
                'min' => 3,
                'max' => 100,
                'label' => 'SEO Title',
            ),
            'attributes' => array(
                'class' => 'form-control',
                'required'  => 'required',
            ),
        ));

        $this->add(array(
            'name' => 'tegDescription',
            'type' => 'Text',
            'options' => array(
                'min' => 3,
                'max' => 100,
                'label' => 'SEO Teg Description',
            ),
            'attributes' => array(
                'class' => 'form-control',
                //'required'  => 'required',
            ),
        ));

        $this->add(array(
            'name' => 'photo',
            'type' => 'File',
            'options' => array(
                'label' => 'Главное фото',
            ),
            'attributes' => array(
                //'class' => 'form-control',
                'accept' => 'image/*',
                //'multiple' => 'multiple',
            ),
        ));

        $this->add(array(
            'name' => 'tegKeyWords',
            'type' => 'Text',
            'options' => array(
                'min' => 3,
                'max' => 100,
                'label' => 'SEO Teg KeyWord',
            ),
            'attributes' => array(
                'class' => 'form-control',
                //'required'  => 'required',
            ),
        ));

        $this->add(array(
            'name' => 'submit',
            'type' => 'Submit',
            'attributes' => array(
                'value' => 'Сохранить',
                'id' => 'btn_submit',
                'class' => 'btn btn-primary',
            ),
        ));
    }
}