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

use Main\Entity\Articles;


class BlogController extends BaseController
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
        $articles = $this->getEntityManager()->createQueryBuilder();
        $articles
            ->select('a')
            ->from('Main\Entity\Articles', 'a');
            //->where("a.id != '1'");
            //->orderBy('a.'.$sort, $order);

        $adapter = new DoctrineAdapter(new ORMPaginator($articles));

        $paginator = new Paginator($adapter);
        $paginator->setDefaultItemCountPerPage($_GET['limit'] == '' ? 15 : $_GET['limit']);
        $paginator->setCurrentPageNumber((int) $this->params()->fromQuery('page', 1));

        return array('articles' => $paginator);
    }

    public function addAction()
    {
        $em = $this->getEntityManager();
        $status = $message = '';

        if(isset($_POST['submit']))
        {
            $symbol     = array('.', ',', ';', ':', '[', ']', '(', ')', '*', '?', '<', '>', '|', ' ', '"', "'"); // /\/|\\|\:|\*|\?|\<|\>|\|/
            $symbol_ok  = array('',  '',  '',  '',  '',  '',  '',  '',  '',  '',  '',  '',  '',  '-', '',  '');

            $url = strtolower(str_replace($symbol, $symbol_ok, $this->transliterate($_POST['name'])));

            $articles = $em->getRepository('Main\Entity\Articles')->findAll();
            $temp=0;
            foreach($articles as $item){
                if(stripos(" ".$item->getUrl()." ", $url) !== FALSE)
                    $temp = 1;
            }
            if($temp == 1)
                $url .= "-".time();

            $article = new Articles();

            $article->setName($_POST['name']);
            $article->setUrl($url);
            $article->setTegTitle($_POST['title']);
            $article->setTegKeyWords($_POST['keywords']);
            $article->setTegDescription($_POST['description']);
            $article->setDescription($_POST['text']);
            $article->setDate(time());

            $em->persist($article);
            $em->flush();

            if($message){
                $this->flashMessenger()
                    ->setNamespace($status)
                    ->addMessage($message);
            }

            return $this->redirect()->toRoute('admin/default', array('controller' => 'blog'));
        }

    }

    public function editAction()
    {
        $em = $this->getEntityManager();

        $id = (int) $this->params()->fromRoute('id', 0);
        $article = $em->find('Main\Entity\Articles', $id);

        if(isset($_POST['submit']))
        {
            $article->setName($_POST['name']);
            $article->setTegTitle($_POST['title']);
            $article->setTegKeyWords($_POST['keywords']);
            $article->setTegDescription($_POST['description']);
            $article->setDescription($_POST['text']);

            $em->persist($article);
            $em->flush();

            return $this->redirect()->toRoute('admin/default', array('controller' => 'blog'));
        }

        return array('article' => $article);
    }

    public function removeAction()
    {
        $em = $this->getEntityManager();
        $id = (int) $this->params()->fromRoute('id', 0);

        $repository = $em->getRepository('Main\Entity\Articles');
        $content = $repository->find($id);

        $em->remove($content);
        $em->flush();

        return $this->redirect()->toRoute('admin/default', array('controller' => 'blog'));
    }

}


