<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Auth\Controller;

//use Application\Controller\BaseController as BaseController;
use Application\Controller\BaseTempController as BaseController;

use Main\Entity\Users;
use DoctrineORMModule\Form\Annotation\AnnotationBuilder;
use DoctrineModule\Stdlib\Hydrator\DoctrineObject as DoctrineHydrator;

use Doctrine\Common\Persistence\ObjectRepository;
use DoctrineModule\Validator\ObjectExists;

use Auth\Form\AuthRegForm;
use Auth\Form\AuthLoginForm;

use Zend\Session\Config\SessionConfig;
use Zend\Session\Container;
use Zend\Session\Session;
use Zend\View\Model\ViewModel;


class IndexController extends BaseController
{


    /*public function indexAction()
    {
       $em = $this->getEntityManager();
       $auths = $em->getRepository('Main\Entity\Auth')->findAll();

        return array('auths' => $auths);
    }*/

    public function loginAction()
    {
        $auth = $this->identity();
        if(isset($auth))
            return $this->redirect()->toRoute('main');
        $form = new AuthLoginForm();
        $status = $message = '';
        $em = $this->getEntityManager();

        /*$page = $em->find('Main\Entity\Pages', 8);

        $renderer = $this->getServiceLocator()->get(
            'Zend\View\Renderer\PhpRenderer');
        $renderer->headTitle($page->getTegTitle());
        $renderer->headMeta()->appendName('keywords', $page->getTegKeywords());
        $renderer->headMeta()->appendName('description', $page->getTegDescription());*/

        //print_r($auth = $this->identity());
        
        $messages = null;
        $request = $this->getRequest();

        if($request->isPost()){
            $form->setData($request->getPost());
            if($form->isValid()){
                $data = $form->getData();

                if(preg_match('@^[a-zA-Z0-9](.[a-zA-Z0-9_-]*)$@u',$data['password'])){
                    $auth = $em->getRepository('Main\Entity\User')->findOneBy(array('login' => $data['username']));
                }
                if(isset($auth) AND $auth->getPassword() == md5($data['password'].$auth->getSalt())){
                    $sm = $this->getServiceLocator();
                    $authService = $sm->get('Zend\Authentication\AuthenticationService');
                    $authService->getStorage()->write($auth);


                    return $this->redirect()->toRoute('main');

                } else {
                    $status = 'error';
                    $message = 'Неправильно заполнены поля Логин и/или пароль!';
                }
            }else{
                $status = 'error';
                $message = 'Неправильно заполнены поля Логин и/или пароль!';
            }
        } else {
            return array('form' => $form);
        }

        if($message){
            $this->flashMessenger()
                ->setNamespace($status)
                ->addMessage($message);
        }
        return $this->redirect()->toUrl('/auth/login/');
    }

    public function logoutAction()
    {
        $auth = $this->getServiceLocator()->get('Zend\Authentication\AuthenticationService');

        if($auth->hasIdentity()){
            $identity = $auth->getIdentity();
        }
        $auth->clearIdentity();
        $sessionManager = new \Zend\Session\SessionManager();
        $sessionManager->forgetMe();

        return $this->redirect()->toRoute('auth', array('action' => 'login'));
    }

    /*public function registerAction()
    {
        $auth = $this->identity();
        if(isset($auth))
            return $this->redirect()->toRoute('account');
        $form = new AuthRegForm;
        $status = $message = '';
        $em = $this->getEntityManager();


        $page = $em->find('Main\Entity\Pages', 9);

        $renderer = $this->getServiceLocator()->get(
            'Zend\View\Renderer\PhpRenderer');
        $renderer->headTitle($page->getTegTitle());
        $renderer->headMeta()->appendName('keywords', $page->getTegKeywords());
        $renderer->headMeta()->appendName('description', $page->getTegDescription());


        $request = $this->getRequest();
        if($request->isPost())
        {
            $form->setData($request->getPost());
            if($form->isValid())
            {
                $data = $form->getData();

                //$query = $em->createQuery("SELECT u FROM Main\Entity\Users u WHERE u.user='$user_n'");
                $user = $em->getRepository('Main\Entity\Users')->findOneBy(array('email' => $data['email']));
                //$rows_user = $query->getResult();

                if (!empty($user)){
                    $status = 'error';
                    $message = 'Пользователь с таким E-mail существует - '.$data['email'];
                    if($message){
                        $this->flashMessenger()
                            ->setNamespace($status)
                            ->addMessage($message);
                    }
                    return $this->redirect()->toRoute('auth', array('controller' => 'index', 'action' => 'register'));
                }
                if($_POST['password'] == $_POST['confirm']){
                    $user = new Users();

                    $user->exchangeArray($form->getData());

                    $salt = time();
                    $user->setSalt($salt);
                    $user->setPassword(md5($_POST['password'].$salt));
                    $user->setStatus(1);
                    //$auth->setType('0');
                    //$auth->setValue('0');
                    //print_r($auth);

                    $em->persist($user);
                    $em->flush();

                    $sm = $this->getServiceLocator();
                    $authService = $sm->get('Zend\Authentication\AuthenticationService');
                    $authService->getStorage()->write($user);

                    $status = 'success';
                    $message = 'Учётная запись успешно создана!';

                    if($message){
                        $this->flashMessenger()
                            ->setNamespace($status)
                            ->addMessage($message);
                    }
                    if(isset($_POST['checkout']))
                        return $this->redirect()->toUrl('/account/address-add/?checkout=1');

                    return $this->redirect()->toRoute('account', array('action' => 'address-add'));
                }else{
                    $status = 'error';
                    $message = 'Пароль не совпадает';

                    if($message){
                        $this->flashMessenger()
                            ->setNamespace($status)
                            ->addMessage($message);
                    }
                    return $this->redirect()->toRoute('auth', array('controller' => 'index', 'action' => 'register'));
                }

            }else{
                $status = 'error';
                $message = 'Ошибка параметров';
            }
        }else{
            return array('form' => $form);
        }

        if($message){
            $this->flashMessenger()
                ->setNamespace($status)
                ->addMessage($message);
        }
        return $this->redirect()->toRoute('auth', array('controller' => 'index', 'action' => 'login'));
    }

    public function forgottenAction()
    {
        $em = $this->getEntityManager();
        $request = $this->getRequest();
        $status = $message = '';

        $page = $em->find('Main\Entity\Pages', 12);

        $renderer = $this->getServiceLocator()->get(
            'Zend\View\Renderer\PhpRenderer');
        $renderer->headTitle($page->getTegTitle());
        $renderer->headMeta()->appendName('keywords', $page->getTegKeywords());
        $renderer->headMeta()->appendName('description', $page->getTegDescription());

        if($request->isPost() AND $_POST['email'] != ''){

            $user = $em->getRepository('Main\Entity\Users')->findOneBy(array('email'=>$_POST['email']));
            if(empty($user)){
                $status = 'error';
                $message = 'E-Mail адрес не найден, проверьте и попробуйте ещё раз!';
                $this->flashMessenger()
                    ->setNamespace($status)
                    ->addMessage($message);

                return $this->redirect()->toRoute('auth', array('action' => 'forgotten'));
            }

            $chars="qazxswedcvfrtgbnhyujmkiolp1234567890QAZXSWEDCVFRTGBNHYUJMKIOLP";
            $max=10;
            $size=StrLen($chars)-1;
            $password=null;
            while($max--)
                $password.=$chars[rand(0,$size)];

            $user->setPassword(md5($password.$user->getSalt()));

            $em->persist($user);
            $em->flush();

            $from = 'info@site26.4nmv.ru';
            $tema = 'Восстановления пароля на site26.4nmv.ru';
            $mess = "Уважаемый пользователь!\r\n
                     Ваш новый пароль: ".$password." на сайте site26.4nmv.ru\r\n
                     Администрация site26.4nmv.ru\r\n";

            mail($user->getEmail(), $tema, $mess, 'From:'.$from);

            $status = 'success';
            $message = 'Новый пароль был выслан на ваш адрес электронной почты.';
            if($message){
                $this->flashMessenger()
                    ->setNamespace($status)
                    ->addMessage($message);
            }
            return $this->redirect()->toRoute('auth', array('action' => 'login'));
        }else{
            $status = 'error';
            $message = 'E-Mail адрес не найден, проверьте и попробуйте ещё раз!';
        }

        if($message){
            $this->flashMessenger()
                ->setNamespace($status)
                ->addMessage($message);
        }
        return array('mess' => '0');
    }*/
}


