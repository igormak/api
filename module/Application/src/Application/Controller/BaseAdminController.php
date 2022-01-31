<?php

namespace Application\Controller;

class BaseAdminController extends BaseController
{
    public function onDispatch(\Zend\Mvc\MvcEvent  $e)
    {
        if(! $this->identity()){
            return $this->redirect()->toUrl('/admin/auth/');
        }
        if($this->identity()->getType() < 255){
            return $this->redirect()->toRoute('main');
        }
        return parent::onDispatch($e);
    }
}