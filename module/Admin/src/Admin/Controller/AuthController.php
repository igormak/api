<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Admin\Controller;

use Application\Controller\BaseController as BaseController;

use Zend\View\Model\JsonModel;
use Zend\View\Model\ViewModel;

use Admin\Form\AuthLoginForm;

use Main\Entity\Content;
use Main\Entity\Articles;


class AuthController extends BaseController
{
    function transliterate($input){
        $gost = array(
            "А"=>"A","а"=>"a",
            "Б"=>"B","б"=>"b",
            "В"=>"V","в"=>"v",
            "Г"=>"G","г"=>"g",
            "Д"=>"D","д"=>"d",
            "Е"=>"E","е"=>"e",
            "Ё"=>"E","ё"=>"e",
            "Ж"=>"Zh","ж"=>"zh",
            "З"=>"Z","з"=>"z",
            "И"=>"I","и"=>"i",
            "Й"=>"I","й"=>"i",
            "К"=>"K","к"=>"k",
            "Л"=>"L","л"=>"l",
            "М"=>"M","м"=>"m",
            "Н"=>"N","н"=>"n",
            "О"=>"O","о"=>"o",
            "П"=>"P","п"=>"p",
            "Р"=>"R","р"=>"r",
            "С"=>"S","с"=>"s",
            "Т"=>"T","т"=>"t",
            "У"=>"U","у"=>"u",
            "Ф"=>"F","ф"=>"f",
            "Х"=>"Kh","х"=>"kh",
            "Ц"=>"Tc","ц"=>"tc",
            "Ч"=>"Ch","ч"=>"ch",
            "Ш"=>"Sh","ш"=>"sh",
            "Щ"=>"Shch","щ"=>"shch",
            "Ы"=>"Y","ы"=>"y",
            "Э"=>"E","э"=>"e",
            "Ю"=>"Iu","ю"=>"iu",
            "Я"=>"Ia","я"=>"ia",
            "ъ"=>"","ь"=>""
        );

        return strtr($input, $gost);
    }

    public function indexAction()
    {
        $auth = $this->identity();
        if(isset($auth))
            return $this->redirect()->toRoute('admin');
        $form = new AuthLoginForm();
        $status = $message = '';
        $em = $this->getEntityManager();

        //print_r($auth = $this->identity());

        $messages = null;
        $request = $this->getRequest();

        if($request->isPost()){
            $form->setData($request->getPost());
            if($form->isValid()){
                $data = $form->getData();

                if(preg_match('@^[a-zA-Z0-9](.[a-zA-Z0-9_-]*)$@u',$data['password'])){
                    $auth = $em->getRepository('Main\Entity\Users')->findOneBy(array('username' => $data['username']));
                }

                if(isset($auth) AND $auth->getPassword() == md5($data['password'].$auth->getSalt())){
                    $sm = $this->getServiceLocator();
                    $authService = $sm->get('Zend\Authentication\AuthenticationService');
                    $authService->getStorage()->write($auth);

                    /*if( $auth->getType() == 1 AND geoip_country_code_by_name($_SERVER['REMOTE_ADDR']) == 'UA'
                        AND ($_SERVER['REMOTE_ADDR'] != '178.216.190.38'
                            OR $_SERVER['REMOTE_ADDR'] != '178.216.190.38'
                        )
                    ){
                        $sessionS = new \Zend\Session\SessionManager();
                        $sessionS->rememberMe(mt_rand(1,100));
                    }*/
                    return $this->redirect()->toRoute('admin');

                } else {
                    $status = 'error';
                    $message = 'Неправильно заполнены поля логин и/или пароль!';
                }
            }else{
                $status = 'error';
                $message = 'Неправильно заполнены поля логин и/или пароль!';
            }
        } else {
            return array('form' => $form);
        }

        if($message){
            $this->flashMessenger()
                ->setNamespace($status)
                ->addMessage($message);
        }
        return $this->redirect()->toUrl('/admin/auth/');
    }


}


