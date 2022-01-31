<?php

namespace Auth;

return array(


    'controllers' => array(
        'invokables' => array(
            'Auth\Controller\Index' => 'Auth\Controller\IndexController'
        ),
    ),

    'router' => array(
        'routes' => array(
            'auth' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/auth/[:action]/[:id/]',
                    'constraints' => array(
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]*'
                    ),

                    'defaults' => array(
                        '__NAMESPACE__' => 'Auth\Controller',
                        'controller'    => 'Auth\Controller\Index',
                        'action'        => 'index',
                    ),
                ),
            ),
        ),
    ),

    'view_manager' => array(
        'template_path_stack' => array(
            __DIR__ . '/../view',
        ),

        'display_exceptions' => true,
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