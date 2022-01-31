<?php

return array(

    'controllers' => array(
        'invokables' => array(
            'Api\Controller\User'       => 'Api\Controller\UserController',
        ),
    ),

    'router' => array(
        'routes' => array(
            'api' => array(
                'type' => 'literal',
                'options' => array(
                    'route' => '/api/',

                    'defaults' => array(
                        '__NAMESPACE__' => 'Api\Controller',
                        'controller'    => 'Api\Controller\Index',
                        'action'        => 'index',
                    ),
                ),

                'may_terminate' => true,

                'child_routes' => array(
                    'default' => array(
                        'type'      => 'segment',
                        'options'   => array(
                            'route'     => '[:controller/][:action/][:id/]',
                            'constraints' => array(
                                'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'id'         => '[0-9]*'
                            ),
                            'defaults' => array(
                            ),
                        ),
                    ),
                ),// << child_routes*/
            ),
        ),
    ),

    'view_manager' => array(
        'strategies' => array(
            'ViewJsonStrategy',
        ),
        'display_not_found_reason' => true,
        'display_exceptions'       => true,
        'doctype'                  => 'HTML5',
        'not_found_template'       => 'error/404',
        'exception_template'       => 'error/index',
        'template_map' => array(
            'layout/api'              => __DIR__ . '/../view/layout/layout.phtml',
            //'layout/adminEdit'          => __DIR__ . '/../view/layout/albumEdit.phtml',
            //'error/404'               => __DIR__ . '/../view/error/404.phtml',
            //'error/index'             => __DIR__ . '/../view/error/index.phtml',
            'pagination_control' => __DIR__ . '/../view/layout/pagination_control.phtml',
        ),
        'template_path_stack' => array(
            __DIR__ . '/../view',
        ),
    ),


    'module_layouts' => array(
        'Api' => array(
            'default' => 'layout/api',
            //'edit'    => 'layout/adminEdit',
        )
    ),


    'doctrine' => array(
        'authentication' => array(
            'orm_default' => array(
                'identity_class' => '\Main\Entity\User',
                'identity_property' => 'login',
                'credential_property' => 'password',
                'credential_callable' => function(\Main\Entity\User $auth, $password){
                    if($auth->getEmail() == $password){
                        return true;
                    }else{
                        return false;
                    }
                },
            ),
        ),
    ),
);