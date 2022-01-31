<?php
namespace Auth\Form;

use Zend\Form\Form;
//use Zend\InputFilter\Factory as InputFactory;
//use Zend\InputFilter\InputFilter;

//use \Admin\Filter\AdminAddFilter;

class AuthLoginForm extends Form
{
    public function __construct($name = null)
    {
        parent::__construct('AuthLoginForm');
        $this->setAttribute('method', 'post');
        //$this->setAttribute('class', 'bs-example form-horizontal');

        //$this->setInputFilter(new AdminAddInputFilter());

        $this->add(array(
            'name' => 'username',
            'type' => 'text',
            'options' => array(
                'min' => 3,
                'max' => 100,
                'label' => 'Логин:',
            ),
            'attributes' => array(
                'class' => 'form-control',
                'required'  => 'required',
                'placeholder' => 'Логин:'
            ),
        ));

        $this->add(array(
            'name' => 'password',
            'type' => 'Password',
            'options' => array(
                'min' => 3,
                'max' => 100,
                'label' => 'Пароль:',
            ),
            'attributes' => array(
                'class' => 'form-control',
                'required'  => 'required',
                'placeholder' => 'Пароль:'
            ),
        ));

        $this->add(array(
            'name' => 'checkbox',
            'type' => 'Checkbox',
            'options' => array(
                'label' => ' Я ознакомился с договором и принимаю условия ипользования сайта.',
            ),
            'attributes' => array(
                //'class' => 'form-control',
                'required'  => 'required',
            ),
        ));

        $this->add(array(
            'name' => 'submit',
            'type' => 'Submit',
            'options' => array(
                'value' => 'Обновить',
            ),
            'attributes' => array(
                'id' => 'btn_submit',
                'class' => 'btn btn-primary',
            ),
        ));

    }
}