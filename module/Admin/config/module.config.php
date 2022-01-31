<?php

return array(

    'controllers' => array(
        'invokables' => array(
            'Admin\Controller\Index'    => 'Admin\Controller\IndexController',
            'Admin\Controller\Auth'     => 'Admin\Controller\AuthController',
            'Admin\Controller\Blog'     => 'Admin\Controller\BlogController',
            'Admin\Controller\User'     => 'Admin\Controller\UserController',
            'Admin\Controller\Product'  => 'Admin\Controller\ProductController',
            'Admin\Controller\Order'    => 'Admin\Controller\OrderController',
            'Admin\Controller\Page'     => 'Admin\Controller\PageController',
            'Admin\Controller\Comment'  => 'Admin\Controller\CommentController',
            'Admin\Controller\Setting'  => 'Admin\Controller\SettingController',
            'Admin\Controller\Parser'   => 'Admin\Controller\ParserController',
            'Admin\Controller\Album'    => 'Admin\Controller\AlbumController',
            'Admin\Controller\Category' => 'Admin\Controller\CategoryController',
        ),
    ),

    'router' => array(
        'routes' => array(
            'admin' => array(
                'type' => 'literal',
                'options' => array(
                    'route' => '/admin/',/*[:action/][:id/]',
                    'constraints' => array(
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',

                    ),*/
                    'defaults' => array(
                        '__NAMESPACE__' => 'Admin\Controller',
                        'controller'    => 'Admin\Controller\Index',
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
        'display_not_found_reason' => true,
        'display_exceptions'       => true,
        'doctype'                  => 'HTML5',
        'not_found_template'       => 'error/404',
        'exception_template'       => 'error/index',
        'template_map' => array(
            'layout/admin'              => __DIR__ . '/../view/layout/layout.phtml',
            //'layout/adminEdit'          => __DIR__ . '/../view/layout/albumEdit.phtml',
            'error/404'               => __DIR__ . '/../view/error/404.phtml',
            'error/index'             => __DIR__ . '/../view/error/index.phtml',
            'pagination_control' => __DIR__ . '/../view/layout/pagination_control.phtml',
        ),
        'template_path_stack' => array(
            __DIR__ . '/../view',
        ),
    ),


    'module_layouts' => array(
        'Admin' => array(
            'default' => 'layout/admin',
            //'edit'    => 'layout/adminEdit',
        )
    ),


    'doctrine' => array(
        'authentication' => array(
            'orm_default' => array(
                'identity_class' => '\Main\Entity\Users',
                'identity_property' => 'email',
                'credential_property' => 'password',
                'credential_callable' => function(\Main\Entity\Users $auth, $password){
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