<?php

namespace Application\Controller;

class BaseUserController extends BaseController
{
    public function onDispatch(\Zend\Mvc\MvcEvent  $e)
    {
        if(! $this->identity()){
            return $this->redirect()->toRoute('auth', array('action' => 'login'));
        }
        return parent::onDispatch($e);
    }
}