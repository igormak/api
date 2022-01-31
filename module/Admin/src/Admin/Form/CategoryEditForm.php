<?php
namespace Admin\Form;

use Zend\Form\Form;
//use Zend\InputFilter\Factory as InputFactory;
//use Zend\InputFilter\InputFilter;

//use \Admin\Filter\AdminAddFilter;

class CategoryEditForm extends Form
{
    public function __construct($name = null)
    {
        parent::__construct('CategoryEditForm');
        $this->setAttribute('method', 'post');
        $this->setAttribute('class', 'bs-example form-horizontal');

        //$this->setInputFilter(new AdminAddInputFilter());

        $this->add(array(
            'name' => 'name',
            'type' => 'Text',
            'options' => array(
                'min' => 3,
                'max' => 100,
                'label' => 'Название',
            ),
            'attributes' => array(
                'class' => 'form-control',
                'required'  => 'required',
            ),
        ));

        $this->add(array(
            'name' => 'idMain',
            'type' => 'Select',
            'options' => array(
                'label' => 'Относиться к катигорий',
                'value_options' => array(
                    '0' => 'Основная категория',
                    //'1' => 'Включено',
                ),
            ),

            'attributes' => array(
                'class' => 'form-control',
                'required'  => 'required',
            ),
        ));

        $this->add(array(
            'name' => 'status',
            'type' => 'Select',
            'options' => array(
                'label' => 'Статус',
                'value_options' => array(
                    '1' => 'Включено',
                    '0' => 'Отключено',
                ),
            ),

            'attributes' => array(
                'class' => 'form-control',
                'required'  => 'required',
            ),
        ));

        $this->add(array(
            'name' => 'photo',
            'type' => 'File',
            'options' => array(
                'label' => 'Фото',
            ),
            'attributes' => array(
                //'class' => 'form-control',
                'accept' => 'image/*',
                //'multiple' => 'multiple',
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