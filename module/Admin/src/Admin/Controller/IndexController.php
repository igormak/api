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

use Zend\View\Model\JsonModel;
use Zend\View\Model\ViewModel;

use Main\Entity\ProductLang;
use Main\Entity\Product;


class IndexController extends BaseController
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
        $em = $this->getEntityManager();
        $orders = $em->getRepository('Main\Entity\Orders')->findBy(array('status'=>'Ожидание '));
        $comments = $em->getRepository('Main\Entity\Comments')->findBy(array('status'=>'-1'));

        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb
            ->select('a')
            ->from('Main\Entity\Product', 'a')
            ->where("a.amount < '5' AND a.status = 1")
            ->setMaxResults(5)
            ->orderBy('a.amount', 'ASC');

        $query = $qb->getQuery();
        $products = $query->execute();

        foreach($products as $item)
        {
            $item->getPhoto() == '' ? $item->setPhoto('logo.jpg') : $item->getPhoto();
        }

        return array('orders' => $orders, 'comments' => $comments, 'products' => $products);
    }

    public function parserAction()
    {
        $em = $this->getEntityManager();

        if(isset($_POST['html'])){
            $data = $_POST['html'];

            $patern1 = '/(?<=title>)(.*)(?=<\/title)/sU';
            preg_match_all($patern1, $data, $title);
            $t_title = $title[0][0];
            print_r($title);

            $patern2 = '/(?<=description" content=")(.*)(?=">)/sU';
            preg_match_all($patern2, $data, $tegdesc);
            $t_tegdesc = $tegdesc[0][0];
            print_r($tegdesc);

            $patern3 = '/(?<=keywords" content=")(.*)(?=">)/sU';
            preg_match_all($patern3, $data, $tegkey);
            $t_tegkey = $tegkey[0][0];
            print_r($tegkey);

            $patern4 = '/(?<=<h1 itemprop="name">)(.*)(?=<\/h1>)/sU';
            preg_match_all($patern4, $data, $name);
            $t_name = $name[0][0];
            print_r($name);

            $patern5 = '/(?<=Код товара: <\/strong>)(.*)(?=<\/span>)/sU';
            preg_match_all($patern5, $data, $article);
            $t_article = $article[0][0];
            print_r($article);

            $patern6 = '/(?<=Бренд:<\/strong>)(.*)(?=<\/span>)/sU';
            preg_match_all($patern6, $data, $brand);
            $t_brand = $brand[0][0];
            print_r($brand);

            $patern7 = '/(?<=Цена:)(.*)(?=<\/strong>)/sU';
            preg_match_all($patern7, $data, $data_price);
            print_r($data_price);

            $patern8 = '/(?<=dTruePrice">)(.*)(?=грн)/sU';
            preg_match_all($patern8, $data_price[0][0], $price);
            $t_price = $price[0][0];
            print_r($price);

            $patern9 = '/(?<=<\/span>\/)(.*)(?= )/sU';
            preg_match_all($patern9, $data_price[0][0], $ed);
            $t_ed = $ed[0][0];
            print_r($ed);

            $patern10 = '/(?<=<div class="tovar_text" itemprop="description">)(.*)(?=<\/div>)/sU';
            preg_match_all($patern10, $data, $k_desc);
            $t_k_desc = $k_desc[0][0];
            print_r($k_desc);

            $patern11 = '/(?<=<div class="desc_wrapper">)(.*)(?=<\/div>)/sU';
            preg_match_all($patern11, $data, $p_desc);
            $t_p_desc = $p_desc[0][0];
            print_r($p_desc);

            $t_desc = $t_k_desc .'<br>'. $t_p_desc;

            $patern12 = '/(?<=<div class="tovar_bfoto">)(.*)(?=<\/div><\/div>)/sU';
            preg_match_all($patern12, $data, $photo);
            print_r($photo);

            $patern13 = '/(?<=href=")(.*)(?=")/sU';
            preg_match_all($patern13, $photo[0][0], $photo);
            $t_photo = $photo[0][0];
            print_r($photo);

            $patern14 = '/(?<=роизводитель: <\/strong>)(.*)(?=<\/span>)/sU';
            preg_match_all($patern14, $data, $manufacturer);
            $t_manufacturer = $manufacturer[0][0];
            print_r($manufacturer);


            $t_photo = '';
            $img = time();
            if(strripos($photo[0][0], '.jpg') !== false OR strripos($photo[0][0], '.jpeg') !== false){
                $first_img = imagecreatefromjpeg($photo[0][0]);
                imagejpeg($first_img,"./uploads/product/".$img.'.jpg');
                $t_photo = $img.'.jpg';
            }
            if(strripos($photo[0][0], '.gif') !== false){
                $first_img = imagecreatefromgif($photo[0][0]);
                imagegif($first_img,"./uploads/product/".$img.'.gif');
                $t_photo = $img.'.gif';
            }
            if(strripos($photo[0][0], '.png') !== false){
                $first_png = imagecreatefrompng($photo[0][0]);
                imagepng($first_png,"./uploads/product/".$img.'.png');
                $t_photo = $img.'.png';
            }


            $t_category = $_POST['category'];

            $query = mysql_query("INSERT INTO product (name, description, amount, article, price, availability, status, manufacturer, category, unit, photo, teg_title, teg_description, teg_key_words)
                                            VALUES ('$t_name','$t_desc','1','$t_article','$t_price','Нет в наличии','1','$t_manufacturer','$t_category','$t_ed','$t_photo','$t_title','$t_tegdesc','$t_tegkey')");
            $id = mysql_insert_id ();

            $product = new Product();

            $uploaddir = '/home/r/rakostde/nt-k.ru/public_html/images/clients/';
            //$uploaddir = '/domains/site17/public/images/clients/';
            $uploadfile = $uploaddir.basename($_FILES['photo']['name']);




            $em->persist($product);
            $em->flush();

            print_r($query);

            for($i=1; $i<count($photo[0]); $i++){

                sleep(2);
                $img = time();
                if(strripos($photo[0][0], '.jpg') !== false OR strripos($photo[0][0], '.jpeg') !== false){
                    $first_img = imagecreatefromjpeg($photo[0][0]);
                    imagejpeg($first_img,"./uploads/product/".$img.'.jpg');
                    $t_photo_d = $img.'.jpg';
                }
                if(strripos($photo[0][0], '.gif') !== false){
                    $first_img = imagecreatefromgif($photo[0][0]);
                    imagegif($first_img,"./uploads/product/".$img.'.gif');
                    $t_photo_d = $img.'.gif';
                }
                if(strripos($photo[0][0], '.png') !== false){
                    $first_png = imagecreatefrompng($photo[0][0]);
                    imagepng($first_png,"./uploads/product/".$img.'.png');
                    $t_photo_d = $img.'.png';
                }

                $query = mysql_query("INSERT INTO product_image (product_id, url ) VALUES ('$id',' $t_photo_d')");
            }
        }
    }

    public function dbAction()
    {
        set_time_limit(0);
        $em = $this->getEntityManager();
        $product = $em->getRepository('Main\Entity\Product')->findAll();
        $category = $em->getRepository('Main\Entity\Category')->findAll();

        $time = time();

        foreach($product as $item)
        {
            $time++;
            /*$product_lang = $em->getRepository('Main\Entity\ProductLang')->findOneBy(array('productId'=>$item->getId()));//new ProductLang();

            $product_lang->setProduct($item);
            $product_lang->setLang('ru');
            $product_lang->setName($item->getName());
            $product_lang->setDescription($item->getDescription());
            $product_lang->setTegTitle($item->getName());

            $em->persist($product_lang);
            $em->flush();*/

            foreach($category as $item_c)
            {
                if($item->getCategory() == $item_c->getName())
                {
                    $item->setCategory($item_c->getId());
                }
            }

            /*$item->setDescription(NULL);
            $item->setAmount(5);
            $item->setAvailability('В наличии');
            $item->setTegTitle(NULL);
            $item->setTegKeywords(NULL);
            $item->setTegDescription(NULL);
            $item->setDate($time);
            $item->setUrl(NULL);*/

            $em->persist($item);
            $em->flush();
        }
        echo 'ok';
    }

    public function uploadImgAction()
    {
        if($_FILES['upload'])
        {
            if (($_FILES['upload'] == "none") OR (empty($_FILES['upload']['name'])) )
            {
                $message = "Вы не выбрали файл";
            }
            else if ($_FILES['upload']["size"] == 0 OR $_FILES['upload']["size"] > 2050000)
            {
                $message = "Размер файла не соответствует нормам";
            }
            else if (($_FILES['upload']["type"] != "image/jpeg") AND ($_FILES['upload']["type"] != "image/jpeg") AND ($_FILES['upload']["type"] != "image/png"))
            {
                $message = "Допускается загрузка только картинок JPG и PNG.";
            }
            else if (!is_uploaded_file($_FILES['upload']["tmp_name"]))
            {
                $message = "Что-то пошло не так. Попытайтесь загрузить файл ещё раз.";
            }
            else{
                $name =rand(1, 1000).'-'.md5($_FILES['upload']['name']).'.'.end(explode(".", $_FILES['upload']['name']));
                move_uploaded_file($_FILES['upload']['tmp_name'], $_SERVER['DOCUMENT_ROOT'] ."/uploads/img/".$name);
                $full_path = '/uploads/img/'.$name;
                $message = "Файл ".$_FILES['upload']['name']." загружен";
                $size=@getimagesize($_SERVER['DOCUMENT_ROOT'] ."/uploads/img/".$name);
                if($size[0]<50 OR $size[1]<50){
                    unlink($_SERVER['DOCUMENT_ROOT'] ."/uploads/img/".$name);
                    $message = "Файл не является допустимым изображением";
                    $full_path="";
                }
            }
            $callback = $_REQUEST['CKEditorFuncNum'];
            echo '<script type="text/javascript">window.parent.CKEDITOR.tools.callFunction("'.$callback.'", "'.$full_path.'", "'.$message.'" );</script>';
            //return new JsonModel(array('<script type="text/javascript">window.parent.CKEDITOR.tools.callFunction("'.$callback.'", "'.$full_path.'", "'.$message.'" );</script>'));
        }
    }
}


