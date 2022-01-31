<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Api\Controller;

use Application\Controller\BaseApiController as BaseController;
use Main\Entity\Company;
use Zend\View\Model\JsonModel;
use Main\Entity\User;


class UserController extends BaseController
{
    public function indexAction()
    {
        return new JsonModel(array());
    }

    public function registerAction()
    {
        $em = $this->getEntityManager();
        $request = $this->getRequest();

        if($request->isPost()){

            $data = $request->getPost();

            if(preg_match('@^[a-zA-Z0-9](.[a-zA-Z0-9_-]*)$@u',$data['password'])){
                $user = $em->getRepository('Main\Entity\User')->findOneBy(array('email' => $data['email']));
            }
            if(empty($user)){
                $user = new User();

                $user->setFirstName($data['first_name']);
                $user->setLastName($data['last_name']);
                $user->setEmail($data['email']);
                $user->setSalt(time());
                $user->setPassword(md5($data['password'].$user->getSalt()));
                $user->setPhone($data['phone']);

                $sm = $this->getServiceLocator();
                $authService = $sm->get('Zend\Authentication\AuthenticationService');
                $authService->getStorage()->write($user);

                $status = 'success';
                $token = md5(rand(1000, 10000) . $user->getPassword() . rand(1000, 10000));
                $user->setToken($token);

                $em->persist($user);
                $em->flush();

                return new JsonModel(array(
                    'status' => $status,
                    'token' => $token
                ));
            } else {
                return new JsonModel(array(
                    'status' => 'error',
                    'message' => 'Такой Email сушествует!'
                ));
            }
        }
        return new JsonModel(array());
    }

    public function signInAction()
    {
        $em = $this->getEntityManager();
        $request = $this->getRequest();

        if($request->isPost()){

            $data = $request->getPost();

            if(preg_match('@^[a-zA-Z0-9](.[a-zA-Z0-9_-]*)$@u',$data['password'])){
                $user = $em->getRepository('Main\Entity\User')->findOneBy(array('email' => $data['email']));
            }
            if(isset($user) AND $user->getPassword() == md5($data['password'].$user->getSalt())){
                $sm = $this->getServiceLocator();
                $authService = $sm->get('Zend\Authentication\AuthenticationService');
                $authService->getStorage()->write($user);

                $status = 'success';
                $token = md5(rand(1000, 10000) . $user->getPassword() . rand(1000, 10000));
                $user->setToken($token);

                $em->persist($user);
                $em->flush();

                return new JsonModel(array(
                    'status' => $status,
                    'token' => $token
                ));
            } else {
                return new JsonModel(array(
                    'status' => 'error',
                    'message' => 'Неправильно заполнены поля Логин и/или пароль!'
                ));
            }
        }
        return new JsonModel(array());
    }

    public function recoverPasswordAction()
    {
        $em = $this->getEntityManager();
        $request = $this->getRequest();

        if($request->isPost()){

            $data = $request->getPost();

            if(preg_match('@^[a-zA-Z0-9](.[a-zA-Z0-9_-]*)$@u',$data['token'])){
                $user = $em->getRepository('Main\Entity\User')->findOneBy(array('token' => $data['token'], 'email' => $data['email']));
            }
            if(isset($user)){

                $user->setSalt(time());
                $user->setPassword(md5($data['password'].$user->getSalt()));

                $sm = $this->getServiceLocator();
                $authService = $sm->get('Zend\Authentication\AuthenticationService');
                $authService->getStorage()->write($user);

                $status = 'success';
                $token = md5(rand(1000, 10000) . $user->getPassword() . rand(1000, 10000));
                $user->setToken($token);

                $em->persist($user);
                $em->flush();

                return new JsonModel(array(
                    'status' => $status,
                    'token' => $token
                ));
            } else {
                return new JsonModel(array(
                    'status' => 'error',
                    'message' => 'Такой Email не сушествует!'
                ));
            }
        }
        return new JsonModel(array());
    }

    public function companiesAction()
    {
        $this->getUser();
        $em = $this->getEntityManager();
        $request = $this->getRequest();

        if($request->isPost()) {

            $data = $request->getPost();

            $company = new Company();

            $company->setUser($this->user);
            $company->setName($data['name']);
            $company->setPhone($data['phone']);
            $company->setDescription($data['description']);

            $em->persist($company);
            $em->flush();
        } else {
            $qb = $this->getEntityManager()->createQueryBuilder();
            $qb
                ->select('c')
                ->from('Main\Entity\Company', 'c')
                ->where("c.userId = " . $this->user->getId());


            $company = $qb->getQuery()->getScalarResult();
            foreach ($company as $key => $item) {
                if (empty($item['i_id']))
                    unset($company[$key]);
            }

            $data['status'] = 'success';
            $data['company'] = $company;
        }

        return new JsonModel($data);
    }
}


