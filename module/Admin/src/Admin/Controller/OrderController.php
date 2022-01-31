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
use Admin\Form\OrderEditForm;

use Main\Entity\Users;


class OrderController extends BaseController
{
    public function indexAction()
    {
        $sort = $_GET['sort'] == '' ? 'id' : $_GET['sort'];
        $order = $_GET['order'] == '' ? 'ASC' : $_GET['order'];

        $orders = $this->getEntityManager()->createQueryBuilder();
        $orders
            ->select('a')
            ->from('Main\Entity\Orders', 'a')
            //->where("a.id != '1'");
            ->orderBy('a.id', 'DESC');

        $adapter = new DoctrineAdapter(new ORMPaginator($orders));

        $paginator = new Paginator($adapter);
        $paginator->setDefaultItemCountPerPage($_GET['limit'] == '' ? 15 : $_GET['limit']);
        $paginator->setCurrentPageNumber((int) $this->params()->fromQuery('page', 1));

        return array('orders' => $paginator);
    }

    public function lookAction()
    {
        $status = $message = '';
        $em = $this->getEntityManager();

        $id = (int) $this->params()->fromRoute('id', 0);

        $order = $em->find('Main\Entity\Orders', $id);
        if(empty($order)){
            $status = 'error';
            $message = 'Заказ не найден';
            $this->flashMessenger()
                ->setNamespace($status)
                ->addMessage($message);
            return $this->redirect()->toRoute('admin/default', array('controller' => 'order'));
        }

        $temp=0;
        $price_all=0;
        $out_product = '';
        $out_product_all = '';
        foreach(explode('};', $order->getProduct()) as $item)
        {
            $mas = explode(';', $item);
            $mas[0] = str_replace('{', '', $mas[0]);
            if($mas[0] != '')
            {
                $i=1;
                $price=0;
                $temp = $mas[0];
                $out_product_all .= $out_product;
                $product = $em->find('Main\Entity\Product', $mas[0]);
                $price = $mas[2]*$mas[1];
                $out_product = '<tr>
                                        <td class="text-left">
                                            <a href="/product/'.$product->getId().'/">'.$product->getName().'</a>
                                        </td>
                                        <td class="text-left">'.$product->getModel().'</td>
                                        <td class="text-right">'.$mas[1].'</td>
                                        <td class="text-right">$'.$mas[2].' </td>
                                        <td class="text-right">$'.$mas[2]*$mas[1].' </td>
                                    </tr>';

                $price_all += $price;
            }
        }
        $out_product_all .= $out_product;
        $price_all = ceil($price_all/0.1) * 0.1;

        return array('order' => $order, 'product' => $out_product_all, 'price_all' => $price_all);
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
        $form = new OrderEditForm;
        $status = $message = '';
        $em = $this->getEntityManager();

        $id = (int) $this->params()->fromRoute('id', 0);

        $order = $em->find('Main\Entity\Orders', $id);
        if(empty($order)){
            $status = 'error';
            $message = 'Заказ не найден';
            $this->flashMessenger()
                ->setNamespace($status)
                ->addMessage($message);
            return $this->redirect()->toRoute('admin/default', array('controller' => 'order'));
        }

        $form->bind($order);

        $request = $this->getRequest();

        if($request->isPost())
        {
            $date = $request->getPost();
            $form->setData($date);
            if($form->isValid())
            {
                $order->setComment($_POST['comment']);

                $em->persist($order);
                $em->flush();

                $status = 'success';
                $message = 'Данные Обновлен';
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
            return array('form' => $form, 'id' => $id, 'comment' => $order->getComment());
        }

        if($message){
            $this->flashMessenger()
                ->setNamespace($status)
                ->addMessage($message);
        }
        return $this->redirect()->toRoute('admin/default', array('controller' => 'order', 'action' => 'edit-product', 'id' => $order->getId()));
    }

    public function editProductAction()
    {
        $status = $message = '';
        $em = $this->getEntityManager();

        $id = (int) $this->params()->fromRoute('id', 0);

        $order = $em->find('Main\Entity\Orders', $id);
        if(empty($order)){
            $status = 'error';
            $message = 'Заказ не найден';
            $this->flashMessenger()
                ->setNamespace($status)
                ->addMessage($message);
            return $this->redirect()->toRoute('admin/default', array('controller' => 'order'));
        }

        if(isset($_POST['id'])){
            $product_id = '';
            foreach($_POST['id'] as $item)
            {
                for($i=0; $i<$_POST['quantity'][$item]; $i++)
                    $product_id .= "$item;";
            }

            $temp=0;
            $product_id = explode(";", substr($product_id, 0, -1));
            for($i=0; $i<count($product_id); $i++)
            {
                for($j=0; $j<count($product_id); $j++)
                {
                    if($product_id[$i] < $product_id[$j])
                    {
                        $temp = $product_id[$i];
                        $product_id[$i] = $product_id[$j];
                        $product_id[$j] = $temp;
                    }
                }
            }

            $temp=0;
            $price_all=0;
            $out_product = '';
            $out_product_all = '';
            foreach($product_id as $item)
            {
                if($item != '')
                {
                    if($item != $temp){
                        $i=1;
                        $price=0;
                        $temp = $item;
                        $out_product_all .= $out_product;
                        $product = $em->find('Main\Entity\Product', $item);

                        foreach(explode('};', $order->getProduct()) as $item)
                        {
                            $mas = explode(';', $item);
                            $mas[0] = str_replace('{', '', $mas[0]);
                            if($mas[0] == $product->getId())
                                $product->setAmount($product->getAmount()+$mas[1]);
                        }

                        $out_product = "{".$product->getId().";".$i++." ;".$product->getPrice()."};";

                        $product->setAmount($product->getAmount()-1);
                    }else{
                        $out_product = "{".$product->getId().";".$i++.";".$product->getPrice()."};";

                        $product->setAmount($product->getAmount()-1);
                    }
                    $em->persist($product);
                    $em->flush();
                }
            }
            $out_product_all .= $out_product;

            /*if($temp == 0)
                return $this->redirect()->toRoute('checkout', array('action' => 'cart'));
*/
            $order->setProduct($out_product_all);

            $em->persist($order);
            $em->flush();

        }

        $temp=0;
        $price_all=0;
        $out_product = '';
        $out_product_all = '';
        foreach(explode('};', $order->getProduct()) as $item)
        {
            $mas = explode(';', $item);
            $mas[0] = str_replace('{', '', $mas[0]);
            $mas[1] = str_replace(' ', '', $mas[1]);
            if($mas[0] != '')
            {
                if($mas[0] != $temp){
                    $i=1;
                    $price=0;
                    $temp = $mas[0];
                    $out_product_all .= $out_product;
                    $product = $address = $em->find('Main\Entity\Product', $mas[0]);
                    $price = $mas[1]*$mas[2];
                    $photo = $product->getPhoto() == '' ? '/image/product.png' : '/uploads/product/'.$product->getPhoto();
                    $out_product = '<tr>
                                        <td class="text-center">
                                            <a href="/product/'.$product->getId().'/">
                                                <img src="'.$photo.'" alt="'.$product->getName().'" title="'.$product->getName().'" class="img-thumbnail img-product-57" >
                                            </a>
                                        </td>
                                        <td class="text-left">
                                            <a href="/product/'.$product->getId().'/">'.$product->getName().'</a>
                                            <br>
                                            <!--small>Призовые баллы: 700</small-->
                                        </td>
                                        <td class="text-left">'.$product->getModel().'</td>
                                        <td class="text-left">
                                            <div class="input-group btn-block" style="max-width: 200px;">
                                                <input type="hidden" name="id['.$product->getId().']" value="'.$product->getId().'">
                                                <input type="text" name="quantity['.$product->getId().']" value="'.$mas[1].'" size="1" class="form-control" pattern="[0-9]*">
                                                <span class="input-group-btn">
                                                    <button type="submit" data-toggle="tooltip" title="" class="btn btn-primary" data-original-title="Update"><span class="glyphicon glyphicon-refresh"></button>
                                                    <button type="button" data-toggle="tooltip" title="" class="btn btn-danger" onclick="remove('.$product->getId().');" data-original-title="Удалить"><i class="glyphicon glyphicon-remove-sign"></i></button>
                                                </span>
                                            </div>
                                        </td>
                                        <td class="text-right">$'.$product->getPrice().' </td>
                                        <td class="text-right">$'.$mas[1]*$mas[2].' </td>
                                    </tr>';
                }else{
                    $price += $product->getPrice();
                    $out_product = '<tr>
                                        <td class="text-center">
                                            <a href="/product/'.$product->getId().'/">
                                                <img src="'.$photo.'" alt="'.$product->getName().'" title="'.$product->getName().'" class="img-thumbnail img-product-57">
                                            </a>
                                        </td>
                                        <td class="text-left">
                                            <a href="/product/'.$product->getId().'/">'.$product->getName().'</a>
                                            <br>
                                            <!--small>Призовые баллы: 700</small-->
                                        </td>
                                        <td class="text-left">'.$product->getModel().'</td>
                                        <td class="text-left">
                                            <div class="input-group btn-block" style="max-width: 200px;">
                                                <input type="hidden" name="id['.$product->getId().']" value="'.$product->getId().'">
                                                <input type="text" name="quantity['.$product->getId().']" value="'.$mas[1].'" size="1" class="form-control" pattern="[0-9]*">
                                                <span class="input-group-btn">
                                                    <button type="submit" data-toggle="tooltip" title="" class="btn btn-primary" data-original-title="Обновить"><i class="fa fa-refresh"></i></button>
                                                    <button type="button" data-toggle="tooltip" title="" class="btn btn-danger" onclick="cart.remove('.$product->getId().');" data-original-title="Удалить"><i class="fa fa-times-circle"></i></button>
                                                </span>
                                            </div>
                                        </td>
                                        <td class="text-right">$'.$product->getPrice().' </td>
                                        <td class="text-right">$'.ceil($price/0.1) * 0.1.' </td>
                                    </tr>';
                }
                $price_all += $price;
            }
        }
        $out_product_all .= $out_product;
        $price_all = ceil($price_all/0.1) * 0.1;

        return array('cart' => $out_product_all, 'price' => $price_all, 'id' => $id);
    }

    public function removeAction()
    {
        $id = (int) $this->params()->fromRoute('id', 0);
        $em = $this->getEntityManager();

        $status = 'success';
        $message = 'Заказ удален';

        try{
            $repository = $em->getRepository('Main\Entity\Orders');
            $order = $repository->find($id);
            $em->remove($order);
            $em->flush();
        }
        catch(\Exception $ex){
            $status = 'error';
            $message = 'Ошибка удаление заказа: ' . $ex->getMessage();
        }

        $this->flashMessenger()
            ->setNamespace($status)
            ->addMessage($message);

        return $this->redirect()->toRoute('admin/default', array('controller' => 'order'));
    }
}


