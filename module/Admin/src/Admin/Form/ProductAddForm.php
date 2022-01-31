<?php
namespace Admin\Form;

use Zend\Form\Form;
//use Zend\InputFilter\Factory as InputFactory;
//use Zend\InputFilter\InputFilter;

//use \Admin\Filter\AdminAddFilter;

class ProductAddForm extends Form
{
    public function __construct($name = null)
    {
        parent::__construct('ProductAddForm');
        $this->setAttribute('method', 'post');
        $this->setAttribute('class', 'bs-example form-horizontal');

        //$this->setInputFilter(new AdminAddInputFilter());

        $this->add(array(
            'name' => 'name',
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

        /*$this->add(array(
            'name' => 'miniDesc',
            'type' => 'Text',
            'options' => array(
                'min' => 3,
                'max' => 100,
                'label' => 'Краткое Описание',
            ),
            'attributes' => array(
                'class' => 'form-control',
                'required'  => 'required',
            ),
        ));*/

        $this->add(array(
            'name' => 'price',
            'type' => 'Text',
            'options' => array(
                'min' => 1,
                'max' => 10,
                'label' => 'Цена',
            ),
            'attributes' => array(
                'class' => 'form-control',
                'required'  => 'required',
                'pattern' => '[0-9]*.[0-9]*',
                'placeholder' => '0.01',
            ),
        ));

        $this->add(array(
            'name' => 'priceSale',
            'type' => 'Text',
            'options' => array(
                'min' => 0,
                'max' => 10,
                'label' => 'Скидка',
            ),
            'attributes' => array(
                'class' => 'form-control',
                'required'  => 'required',
                'pattern' => '^[ 0-9]+$',
                'placeholder' => 'в процентах от 0-100',
            ),
        ));

        $this->add(array(
            'name' => 'unit',
            'type' => 'Select',
            'options' => array(
                'label' => 'Единица измерения',
                'value_options' => array(
                    'шт.' => 'шт.',
                    'кг.' => 'кг.',
                ),
            ),

            'attributes' => array(
                'class' => 'form-control',
                'required'  => 'required',
            ),
        ));

        $this->add(array(
            'name' => 'amount',
            'type' => 'Text',
            'options' => array(
                'min' => 1,
                'max' => 100,
                'label' => 'Количество на складе',
            ),
            'attributes' => array(
                'class' => 'form-control',
                'required' => 'required',
                'pattern' => '-*[0-9]*',
            ),
        ));

        $this->add(array(
            'name' => 'article',
            'type' => 'Text',
            'options' => array(
                'min' => 3,
                'max' => 100,
                'label' => 'Артикул',
            ),
            'attributes' => array(
                'class' => 'form-control',
                //'required'  => 'required',
            ),
        ));

        $this->add(array(
            'name' => 'category',
            'type' => 'Select',
            'options' => array(
                'label' => 'Категория',
                'value_options' => array(
                    '' => '',

                ),
            ),

            'attributes' => array(
                'class' => 'form-control',
                'required'  => 'required',
            ),
        ));

        $this->add(array(
            'name' => 'manufacturer',
            'type' => 'Text',
            'options' => array(
                'min' => 3,
                'max' => 100,
                'label' => 'Производитель',
            ),
            'attributes' => array(
                'class' => 'form-control',
                //'required'  => 'required',
            ),
        ));

        $this->add(array(
            'name' => 'model',
            'type' => 'Text',
            'options' => array(
                'min' => 3,
                'max' => 100,
                'label' => 'Модель',
            ),
            'attributes' => array(
                'class' => 'form-control',
                'required'  => 'required',
            ),
        ));

        /*$this->add(array(
            'name' => 'size',
            'type' => 'Text',
            'options' => array(
                'min' => 3,
                'max' => 100,
                'label' => 'Размер',
            ),
            'attributes' => array(
                'class' => 'form-control',
                'required'  => 'required',
                'placeholder' => '90х200',
            ),
        ));*/

        $this->add(array(
            'name' => 'availability',
            'type' => 'Select',
            'options' => array(
                'label' => 'Отсутствие на складе',
                'value_options' => array(
                    'В наличии' => 'В наличии',
                    'Предзаказ' => 'Предзаказ',
                    'Нет в наличии' => 'Нет в наличии',
                    'Ожидание 2-3 дня' => 'Ожидание 2-3 дня',
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
                'label' => 'Главное фото',
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

