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

use Admin\Form\CommentEditForm;

use Main\Entity\Comments;


class CommentController extends BaseController
{
    public function indexAction()
    {
        $sort = $_GET['sort'] == '' ? 'id' : $_GET['sort'];
        $order = $_GET['order'] == '' ? 'ASC' : $_GET['order'];

        $comments = $this->getEntityManager()->createQueryBuilder();
        $comments
            ->select('a')
            ->from('Main\Entity\Comments', 'a')
            //->where("a.id != '1'");
            ->orderBy('a.id', 'DESC');

        $adapter = new DoctrineAdapter(new ORMPaginator($comments));

        $paginator = new Paginator($adapter);
        $paginator->setDefaultItemCountPerPage($_GET['limit'] == '' ? 15 : $_GET['limit']);
        $paginator->setCurrentPageNumber((int) $this->params()->fromQuery('page', 1));

        return array('comments' => $paginator);
    }

    public function editAction()
    {
        $form = new CommentEditForm;
        $status = $message = '';
        $em = $this->getEntityManager();

        $id = (int) $this->params()->fromRoute('id', 0);

        $comment = $em->find('Main\Entity\Comments', $id);
        if(empty($comment)){
            $status = 'error';
            $message = 'Коментарий не найден';
            $this->flashMessenger()
                ->setNamespace($status)
                ->addMessage($message);
            return $this->redirect()->toRoute('admin/default', array('controller' => 'comment'));
        }

        $form->bind($comment);

        $request = $this->getRequest();

        if($request->isPost())
        {
            $date = $request->getPost();
            $form->setData($date);
            if($form->isValid())
            {
                $comment->setDescription($_POST['comment']);

                $em->persist($comment);
                $em->flush();

                $status = 'success';
                $message = 'Коментарий Обновлен';
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
            return array('form' => $form, 'id' => $id, 'comment' => $comment);
        }

        if($message){
            $this->flashMessenger()
                ->setNamespace($status)
                ->addMessage($message);
        }
        return $this->redirect()->toRoute('admin/default', array('controller' => 'comment'));
    }

    public function removeAction()
    {
        $id = (int) $this->params()->fromRoute('id', 0);
        $em = $this->getEntityManager();

        $status = 'success';
        $message = 'Comment удален';

        try{
            $repository = $em->getRepository('Main\Entity\Comments');
            $comment = $repository->find($id);
            $em->remove($comment);
            $em->flush();
        }
        catch(\Exception $ex){
            $status = 'error';
            $message = 'Ошибка удаление заказа: ' . $ex->getMessage();
        }

        $this->flashMessenger()
            ->setNamespace($status)
            ->addMessage($message);

        return $this->redirect()->toRoute('admin/default', array('controller' => 'comment'));
    }
}


