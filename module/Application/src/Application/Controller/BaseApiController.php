<?php

namespace Application\Controller;

use Zend\View\Model\JsonModel;

class BaseApiController extends BaseController
{
    protected  $user;

    public function onDispatch(\Zend\Mvc\MvcEvent  $e)
    {
        return parent::onDispatch($e);
    }

    public function getUser()
    {
        $em = $this->getEntityManager();
        $post = $this->getRequest()->getPost();
        $token = isset($post['token']) ? $post['token'] : 0;

        $user = $em->getRepository('Main\Entity\User')->findOneBy(array('token' => $token));
        if(empty($user)){
            $data['message'] = '';
            $data['status'] = 'error';

            echo json_encode($data);
            header("HTTP/1.x 404");
            exit();
        }

        $this->user = $user;
    }
}