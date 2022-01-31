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

use Admin\Form\UserAddForm;
use Admin\Form\UserEditForm;

use Main\Entity\Users;


class UserController extends BaseController
{
    public function indexAction()
    {
        $sort = $_GET['sort'] == '' ? 'id' : $_GET['sort'];
        $order = $_GET['order'] == '' ? 'ASC' : $_GET['order'];

        $users = $this->getEntityManager()->createQueryBuilder();
        $users
            ->select('a')
            ->from('Main\Entity\Users', 'a')
            ->where("a.id != '1'");
            //->orderBy('a.'.$sort, $order);

        $adapter = new DoctrineAdapter(new ORMPaginator($users));

        $paginator = new Paginator($adapter);
        $paginator->setDefaultItemCountPerPage($_GET['limit'] == '' ? 15 : $_GET['limit']);
        $paginator->setCurrentPageNumber((int) $this->params()->fromQuery('page', 1));

        return array('users' => $paginator);
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
        $form = new UserEditForm;
        $status = $message = '';
        $em = $this->getEntityManager();

        $id = (int) $this->params()->fromRoute('id', 0);

        $user = $em->find('Main\Entity\Users', $id);
        if(empty($user)){
            $status = 'error';
            $message = 'Пользователь не найден';
            $this->flashMessenger()
                ->setNamespace($status)
                ->addMessage($message);
            return $this->redirect()->toRoute('admin/default', array('controller' => 'user'));
        }
        $pass = $user->getPassword();

        $form->bind($user);

        $request = $this->getRequest();

        if($request->isPost())
        {
            $date = $request->getPost();
            $form->setData($date);
            if($form->isValid())
            {
                $user_new = $em->getRepository('Main\Entity\Users')->findOneBy(array('email' => $_POST['email']));
                if(!empty($user_new) AND $user_new->getId() != $id){
                    $this->flashMessenger()->addErrorMessage('Пользователь с таким E-Mail существует - '.$user_new->getEmail());
                    return $this->redirect()->toRoute('admin/default', array('controller' => 'user', 'action' => 'edit', 'id' => $id));
                }

                $user->setPassword($pass);
                if(!empty($_POST['password']))
                    $user->setPassword(md5($_POST['password'].$user->setSalt()));

                $em->persist($user);
                $em->flush();

                $status = 'success';
                $message = 'Пользователь Обновлен';
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
        return $this->redirect()->toRoute('admin/default', array('controller' => 'user'));
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

}


