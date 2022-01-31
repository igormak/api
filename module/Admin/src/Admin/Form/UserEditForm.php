<?php
namespace Admin\Form;

use Zend\Form\Form;
//use Zend\InputFilter\Factory as InputFactory;
//use Zend\InputFilter\InputFilter;

//use \Admin\Filter\AdminAddFilter;

class UserEditForm extends Form
{
    public function __construct($name = null)
    {
        parent::__construct('UserEditForm');
        $this->setAttribute('method', 'post');
        $this->setAttribute('class', 'bs-example form-horizontal');

        //$this->setInputFilter(new AdminAddInputFilter());

        $this->add(array(
            'name' => 'firstname',
            'type' => 'Text',
            'options' => array(
                'min' => 3,
                'max' => 100,
                'label' => 'Имя',
            ),
            'attributes' => array(
                'class' => 'form-control',
                'required'  => 'required',
            ),
        ));

        $this->add(array(
            'name' => 'lastname',
            'type' => 'Text',
            'options' => array(
                'min' => 3,
                'max' => 100,
                'label' => 'Фамилия',
            ),
            'attributes' => array(
                'class' => 'form-control',
                'required'  => 'required',
            ),
        ));

        $this->add(array(
            'name' => 'email',
            'type' => 'Email',
            'options' => array(
                'min' => 3,
                'max' => 100,
                'label' => 'E-Mail',
            ),
            'attributes' => array(
                'class' => 'form-control',
                'required'  => 'required',
            ),
        ));

        $this->add(array(
            'name' => 'password',
            'type' => 'Password',
            'options' => array(
                'min' => 3,
                'max' => 100,
                'label' => 'Пароль',
            ),
            'attributes' => array(
                'class' => 'form-control',
                //'required'  => 'required',
            ),
        ));

        $this->add(array(
            'name' => 'phone',
            'type' => 'text',
            'options' => array(
                'min' => 3,
                'max' => 100,
                'label' => 'Телефон',
            ),
            'attributes' => array(
                'class' => 'form-control',
                'required'  => 'required',
            ),
        ));

        $this->add(array(
            'name' => 'fax',
            'type' => 'text',
            'options' => array(
                'min' => 3,
                'max' => 100,
                'label' => 'Факс',
            ),
            'attributes' => array(
                'class' => 'form-control',
                //'required'  => 'required',
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
            'name' => 'newsletter',
            'type' => 'Select',
            'options' => array(
                'label' => 'Подписка',
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