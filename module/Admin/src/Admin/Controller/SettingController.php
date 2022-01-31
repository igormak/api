<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Admin\Controller;

use Application\Controller\BaseAdminController as BaseController;

use DoctrineORMModule\Paginator\Adapter\DoctrinePaginator as DoctrineAdapter;
use Doctrine\ORM\Tools\Pagination\Paginator as ORMPaginator;
use Zend\Paginator\Paginator;

use Zend\View\Model\JsonModel;
use Zend\View\Model\ViewModel;

use Admin\Form\SettingEditCurrencyForm;

use Main\Entity\Users;


class SettingController extends BaseController
{
    public function indexAction()
    {
        return new ViewModel();
    }

    public function addAction()
    {
        $form = new UserAddForm;
        $status = $message = '';
        $em = $this->getEntityManager();

        $request = $this->getRequest();
        if($request->isPost())
        {
            $form->setData($request->getPost());
            if($form->isValid())
            {
                $user = $em->getRepository('Main\Entity\Users')->findOneBy(array('email' => $_POST['email']));
                if(!empty($user)){
                    $this->flashMessenger()->addErrorMessage('Пользователь с таким E-Mail существует - '.$user->getEmail());
                    return $this->redirect()->toRoute('admin/default', array('controller' => 'user', 'action' => 'add'));
                }
                $user = new Users();
                $user->exchangeArray($form->getData());

                $salt = time();
                $user->setSalt($salt);
                $user->setPassword(md5($_POST['password'].$salt));

                $em->persist($user);
                $em->flush();

                $status = 'success';
                $message = 'Пользователь добавлен';
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
        return $this->redirect()->toRoute('admin/default', array('controller' => 'user'));
    }

    public function editAction()
    {
        $status = $message = '';
        $em = $this->getEntityManager();

        $id = (int) $this->params()->fromRoute('id', 0);

        $page = $em->find('Main\Entity\Page', $id);
        if(empty($page)){
            $status = 'error';
            $message = 'Страница не найдена';
            $this->flashMessenger()
                ->setNamespace($status)
                ->addMessage($message);
            return $this->redirect()->toRoute('admin/default', array('controller' => 'index'));
        }
        $page_lang = $em->getRepository('Main\Entity\PageLang')->findBy(array('pageId' => $id));

        $request = $this->getRequest();

        if($request->isPost())
        {
            $date = $request->getPost();
            if(1)
            {

                $lang_mas = array('en', 'it', 'ru');
                foreach($lang_mas as $item){
                    $page_lang = $em->getRepository('Main\Entity\PageLang')->findOneBy(array('pageId' => $id, 'lang' => $item));

                    $page_lang->setTegTitle($_POST["meta_title_$item"]);
                    $page_lang->setDescription($_POST["description_$item"]);
                    $page_lang->setTegKeywords($_POST["meta_keywords_$item"]);
                    //$page_lang->setTegDescription($_POST["meta_description_$item"]);

                    $em->persist($page_lang);
                    $em->flush();
                }

                $em->persist($page);
                $em->flush();

                $status = 'success';
                $message = $page->getName().' Обновлен';
            }else{
                $status = 'error';
                $message = 'Ошибка параметров';
            }
        }else{
            return array('page' => $page, 'page_lang' => $page_lang);
        }

        if($message){
            $this->flashMessenger()
                ->setNamespace($status)
                ->addMessage($message);
        }
        return $this->redirect()->toRoute('admin/default', array('controller' => 'index'));
    }

    public function removeAction()
    {
        $id = (int) $this->params()->fromRoute('id', 0);
        $em = $this->getEntityManager();

        $status = 'success';
        $message = 'Пользователь удален';

        try{
            $repository = $em->getRepository('Main\Entity\Users');
            $user = $repository->find($id);
            $em->remove($user);
            $em->flush();
        }
        catch(\Exception $ex){
            $status = 'error';
            $message = 'Ошибка удаление пользователя: ' . $ex->getMessage();
        }

        $this->flashMessenger()
            ->setNamespace($status)
            ->addMessage($message);

        return $this->redirect()->toRoute('admin/default', array('controller' => 'user'));
    }


    public function editCurrencyAction()
    {
        $form = new SettingEditCurrencyForm();
        $status = $message = '';
        $em = $this->getEntityManager();

        $id = (int) $this->params()->fromRoute('id', 0);

        $currency = $em->find('Main\Entity\Currency', $id);
        if(empty($currency)){
            $status = 'error';
            $message = 'Страница не найдена';
            $this->flashMessenger()
                ->setNamespace($status)
                ->addMessage($message);
            return $this->redirect()->toRoute('admin/default', array('controller' => 'index'));
        }

        $form->bind($currency);
        $request = $this->getRequest();

        if($request->isPost())
        {
            $date = $request->getPost();
            $form->setData($date);
            if($form->isValid())
            {
                $em->persist($currency);
                $em->flush();


                $status = 'success';
                $message = 'Параметр Обновлен';
            }else{
                $status = 'error';
                $message = 'Ошибка параметров';
                foreach($form->getInputFilter()->getInvalidInput() as $errors){
                    foreach($errors->getMessages() as $error){
                        $message .= ' '.$error;
                    }
                }
            }
        }else{
            return array('form' => $form, 'id' => $id);
        }

        if($message){
            $this->flashMessenger()
                ->setNamespace($status)
                ->addMessage($message);
        }
        return $this->redirect()->toRoute('admin/default', array('controller' => 'index'));
    }
}


