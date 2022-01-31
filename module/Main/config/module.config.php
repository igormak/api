<?php

return array(

    'doctrine' => array(
        'driver' => array(
            // defines an annotation driver with two paths, and names it `my_annotation_driver`
            'main_entity' => array(
                'class' => 'Doctrine\ORM\Mapping\Driver\AnnotationDriver',
                'cache' => 'array',
                'paths' => array(
                    __DIR__.'/../src/Main/Entity',
                ),
            ),

            // default metadata driver, aggregates all other drivers into a single one.
            // Override `orm_default` only if you know what you're doing
            'orm_default' => array(
                'drivers' => array(
                    // register `my_annotation_driver` for any entity under namespace `My\Namespace`
                    'Main\Entity' => 'main_entity'
                )
            )
        )
    ),

    'controllers' => array(
        'invokables' => array(
            'Main\Controller\Index' => 'Main\Controller\IndexController',
            'category' => 'Main\Controller\CategoryController',
        ),
    ),

    'router' => array(
        'routes' => array(
            'main' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/[:action/][:id/]',
                    'constraints' => array(
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]*',
                    ),
                    'defaults' => array(
                        //'__NAMESPACE__' => 'Main\Controller',
                        'controller'    => 'Main\Controller\Index',
                        'action'        => 'index',
                    ),
                ),

                'may_terminate' => true,

            ),
        ),
    ),

    'view_manager' => array(
        'template_path_stack' => array(
            __DIR__ . '/../view',
        ),
        'template_map' => array(
            'pagination_control_main' => __DIR__ . '/../view/layout/pagination_control.phtml',
            'layout/product_pop'          => __DIR__ . '/../view/layout/product_pop.phtml',
        ),
        'strategies' => array(
            'ViewJsonStrategy',
        ),
    ),

    'module_layouts' => array(
        'Main' => array(
            'default' => 'layout/layout',
            'index' => 'layout/index',
            'realty' => 'layout/index',
        )
    ),
);