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

use Admin\Form\ProductAddForm;

use Zend\View\Model\ViewModel;

use Main\Entity\Product;
use Main\Entity\ProductLang;
use Main\Entity\ProductImage;


class ProductController extends BaseController
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
            "ъ"=>"","ь"=>"",
            " "=>"_"
        );

        return strtr($input, $gost);
    }

    function cropString($s)
    {
        if(iconv_strlen($s) > 45)
        {
            return substr($s, 0, 45).".jpg";
        }

        return $s;
    }

    function resize($file)
    {
        // Ограничение по ширине в пикселях
        $max_size = 780;

        // Качество изображения по умолчанию
        $quality = 75;

        // Cоздаём исходное изображение на основе исходного файла
        if (exif_imagetype($file) == IMAGETYPE_JPEG)
            $source = imagecreatefromjpeg($file);
        elseif (exif_imagetype($file) == IMAGETYPE_PNG)
            $source = imagecreatefrompng($file);
        elseif (exif_imagetype($file) == IMAGETYPE_GIF)
            $source = imagecreatefromgif($file);
        else
            return false;

        $src = $source;

        // Определяем ширину и высоту изображения
        $w_src = imagesx($src);
        $h_src = imagesy($src);

        // В зависимости от типа (эскиз или большое изображение) устанавливаем ограничение по ширине.
        $w = $max_size;

        // Если ширина больше заданной
        if ($w_src > $w)
        {
            // Вычисление пропорций
            $ratio = $w_src/$w;
            $w_dest = round($w_src/$ratio);
            $h_dest = round($h_src/$ratio);

            // Создаём пустую картинку
            $dest = imagecreatetruecolor($w_dest, $h_dest);

            // Копируем старое изображение в новое с изменением параметров
            imagecopyresampled($dest, $src, 0, 0, 0, 0, $w_dest, $h_dest, $w_src, $h_src);

            // Вывод картинки и очистка памяти
            imagejpeg($dest, $file, $quality);
            imagedestroy($dest);
            imagedestroy($src);

            return $file;
        }
        else
        {
            // Вывод картинки и очистка памяти
            imagejpeg($src, $file, $quality);
            imagedestroy($src);

            return $file;
        }
    }

    function cropImage($aInitialImageFilePath, $aNewImageFilePath, $aNewImageWidth, $aNewImageHeight) {
        /**
         * @param string $aInitialImageFilePath - строка, представляющая путь к обрезаемому изображению
         * @param string $aNewImageFilePath - строка, представляющая путь куда нахо сохранить выходное обрезанное изображение
         * @param int $aNewImageWidth - ширина выходного обрезанного изображения
         * @param int $aNewImageHeight - высота выходного обрезанного изображения
         */

        if (($aNewImageWidth < 0) || ($aNewImageHeight < 0)) {
            return false;
        }

        // Массив с поддерживаемыми типами изображений
        $lAllowedExtensions = array(1 => "gif", 2 => "jpeg", 3 => "png");

        // Получаем размеры и тип изображения в виде числа
        list($lInitialImageWidth, $lInitialImageHeight, $lImageExtensionId) = getimagesize($aInitialImageFilePath);

        if (!array_key_exists($lImageExtensionId, $lAllowedExtensions)) {
            return false;
        }
        $lImageExtension = $lAllowedExtensions[$lImageExtensionId];

        // Получаем название функции, соответствующую типу, для создания изображения
        $func = 'imagecreatefrom' . $lImageExtension;
        // Создаём дескриптор исходного изображения
        $lInitialImageDescriptor = $func($aInitialImageFilePath);

        // Определяем отображаемую область
        $lCroppedImageWidth = 0;
        $lCroppedImageHeight = 0;
        $lInitialImageCroppingX = 0;
        $lInitialImageCroppingY = 0;
        if ($aNewImageWidth / $aNewImageHeight > $lInitialImageWidth / $lInitialImageHeight) {
            $lCroppedImageWidth = floor($lInitialImageWidth);
            $lCroppedImageHeight = floor($lInitialImageWidth * $aNewImageHeight / $aNewImageWidth);
            $lInitialImageCroppingY = floor(($lInitialImageHeight - $lCroppedImageHeight) / 2);
        } else {
            $lCroppedImageWidth = floor($lInitialImageHeight * $aNewImageWidth / $aNewImageHeight);
            $lCroppedImageHeight = floor($lInitialImageHeight);
            $lInitialImageCroppingX = floor(($lInitialImageWidth - $lCroppedImageWidth) / 2);
        }

        // Создаём дескриптор для выходного изображения
        $lNewImageDescriptor = imagecreatetruecolor($aNewImageWidth, $aNewImageHeight);
        imagecopyresampled($lNewImageDescriptor, $lInitialImageDescriptor, 0, 0, $lInitialImageCroppingX, $lInitialImageCroppingY, $aNewImageWidth, $aNewImageHeight, $lCroppedImageWidth, $lCroppedImageHeight);
        $func = 'image' . $lImageExtension;

        // сохраняем полученное изображение в указанный файл
        return $func($lNewImageDescriptor, $aNewImageFilePath);
    }

    public function indexAction()
    {
        $sort = !isset($_GET['sort']) ? 'id' : $_GET['sort'];
        $order = !isset($_GET['order']) ? 'ASC' : $_GET['order'];

        $products = $this->getEntityManager()->createQueryBuilder();
        $products
            ->select('a')
            ->from('Main\Entity\Product', 'a')
            //->where("a.id != '1'");
            //->orderBy('a.'.$sort, $order);
            ->orderBy('a.id', 'DESC');

        $adapter = new DoctrineAdapter(new ORMPaginator($products));

        $paginator = new Paginator($adapter);
        $paginator->setDefaultItemCountPerPage(!isset($_GET['limit']) ? 25 : $_GET['limit']);
        $paginator->setCurrentPageNumber((int) $this->params()->fromQuery('page', 1));

        foreach($paginator as $item)
        {
            $item->getPhoto() == '' ? $item->setPhoto('logo.jpg') : $item->getPhoto();
        }

        return array('products' => $paginator);
    }

    public function addAction()
    {
        $form = new ProductAddForm;
        $status = $message = '';
        $em = $this->getEntityManager();


        $value_opt = $form->get('category')->getValueOptions();

        $category_sql = $em->getRepository('Main\Entity\Category')->findBy(array('status' => 1));
        foreach($category_sql as $item){
            $value_opt[$item->getId()] = $item->getName();
        }

        $form->get('category')->setValueOptions($value_opt);


        $request = $this->getRequest();
        if($request->isPost())
        {
            $form->setData($request->getPost());
            if($form->isValid())
            {
                $product = new Product();
                $product->exchangeArray($form->getData());

                $product->setDate(time());

                $em->persist($product);
                $em->flush();

                $uploaddir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/product/';
                if(!empty($_FILES['photo']['name'])){
                    $uploadfile = $uploaddir.basename($_FILES['photo']['name']);

                    // Копируем файл из каталога для временного хранения файлов:
                    copy($_FILES['photo']['tmp_name'], $uploadfile);

                    $photo_name = $this->cropString($product->getId()."_".$this->transliterate($_FILES['photo']['name']));
                    rename($uploaddir . $_FILES['photo']['name'], $uploaddir . $photo_name);
                    $this->resize($uploaddir . $photo_name);
                    $this->cropImage($uploaddir . $photo_name, $uploaddir . $photo_name, 780, 780);
                    //$this->cropImage($uploaddir . $photo_name, $uploaddir .'min_'. $photo_name, 490, 470);

                    $product->setPhoto($photo_name);
                }
                if(!empty($_FILES['photoAll']['name'][0])){
                    for($i=0; $i<count($_FILES['photoAll']['name']); $i++){
                        $uploadfile = $uploaddir.basename($_FILES['photoAll']['name'][$i]);

                        // Копируем файл из каталога для временного хранения файлов:
                        copy($_FILES['photoAll']['tmp_name'][$i], $uploadfile);

                        $photo_name = $this->cropString($product->getId()."_".$this->transliterate($_FILES['photoAll']['name'][$i]));
                        rename($uploaddir . $_FILES['photoAll']['name'][$i], $uploaddir . $photo_name);
                        $this->resize($uploaddir . $photo_name);
                        $this->cropImage($uploaddir . $photo_name, $uploaddir . $photo_name, 780, 780);
                        //$this->cropImage($uploaddir . $photo_name, $uploaddir .'min_'. $photo_name, 490, 470);

                        $product_iamge = new ProductImage();

                        $product_iamge->setProduct($product);
                        $product_iamge->setUrl($photo_name);

                        $em->persist($product_iamge);
                        $em->flush();
                    }
                }

                $em->persist($product);
                $em->flush();

                $lang_mas = array(/*'en', 'it',*/ 'ru');
                foreach($lang_mas as $item){
                    $product_lang = new ProductLang();

                    $post_str = $_POST["description_$item"];
                    /*$post_str = "{".$_POST["description_$item"]."};{".$_POST["description_info_product_$item"]."};{"
                        .$_POST["description_info_quality_$item"]."};{".$_POST["description_packaging_$item"]."};{"
                        .$_POST["description_manual_$item"]."};";*/

                    $product_lang->setLang($item);
                    $product_lang->setName($_POST["name_$item"]);
                    $product_lang->setDescription($post_str);
                    $product_lang->setProduct($product);
                    $product_lang->setTegTitle($_POST["meta_title_$item"]);
                    $product_lang->setTegKeywords($_POST["meta_keywords_$item"]);
                    $product_lang->setTegDescription($_POST["meta_description_$item"]);

                    $em->persist($product_lang);
                    $em->flush();
                }

                $status = 'success';
                $message = 'Продукт добавлен';
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
        return $this->redirect()->toRoute('admin/default', array('controller' => 'product'));
    }

    public function editAction()
    {
        $form = new ProductAddForm;
        $status = $message = '';
        $em = $this->getEntityManager();

        $id = (int) $this->params()->fromRoute('id', 0);

        $product = $em->find('Main\Entity\Product', $id);
        if(empty($product)){
            $status = 'error';
            $message = 'Продукт не найден';
            $this->flashMessenger()
                ->setNamespace($status)
                ->addMessage($message);
            return $this->redirect()->toRoute('admin/default', array('controller' => 'product'));
        }
        $product_lang = $em->getRepository('Main\Entity\ProductLang')->findBy(array('productId' => $id));
        $product_image = $em->getRepository('Main\Entity\ProductImage')->findBy(array('productId' => $id));

        $value_opt = $form->get('category')->getValueOptions();

        $category_sql = $em->getRepository('Main\Entity\Category')->findBy(array('status' => 1));
        foreach($category_sql as $item){
            $temp = 0;
            foreach($category_sql as $item_2){
                if($item->getId() == $item_2->getIdMain())
                    $temp = 1;
            }
            if($temp == 0)
                $value_opt[$item->getId()] = $item->getName();
        }

        $form->get('category')->setValueOptions($value_opt);


        $form->bind($product);
        $request = $this->getRequest();

        if($request->isPost())
        {
            $date = $request->getPost();
            $form->setData($date);
            if($form->isValid())
            {

                $uploaddir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/product/';
                if(!empty($_FILES['photo']['name'])){
                    $uploadfile = $uploaddir.basename($_FILES['photo']['name']);

                    // Копируем файл из каталога для временного хранения файлов:
                    copy($_FILES['photo']['tmp_name'], $uploadfile);

                    $photo_name = $this->cropString($product->getId()."_".$this->transliterate($_FILES['photo']['name']));
                    rename($uploaddir . $_FILES['photo']['name'], $uploaddir . $photo_name);
                    $this->resize($uploaddir . $photo_name);
                    $this->cropImage($uploaddir . $photo_name, $uploaddir . $photo_name, 780, 780);
                    //$this->cropImage($uploaddir . $photo_name, $uploaddir .'min_'. $photo_name, 490, 470);

                    unlink($uploaddir.$product->getPhoto());
                    $product->setPhoto($photo_name);
                }
                if(!empty($_FILES['photoAll']['name'][0])){
                    for($i=0; $i<count($_FILES['photoAll']['name']); $i++){
                        $uploadfile = $uploaddir.basename($_FILES['photoAll']['name'][$i]);

                        // Копируем файл из каталога для временного хранения файлов:
                        copy($_FILES['photoAll']['tmp_name'][$i], $uploadfile);

                        $photo_name = $this->cropString($product->getId()."_".$this->transliterate($_FILES['photoAll']['name'][$i]));
                        rename($uploaddir . $_FILES['photoAll']['name'][$i], $uploaddir . $photo_name);
                        $this->resize($uploaddir . $photo_name);
                        $this->cropImage($uploaddir . $photo_name, $uploaddir . $photo_name, 780, 780);

                        $product_iamge = new ProductImage();

                        $product_iamge->setProduct($product);
                        $product_iamge->setUrl($photo_name);

                        $em->persist($product_iamge);
                        $em->flush();
                    }
                }

                $em->persist($product);
                $em->flush();

                $lang_mas = array(/*'en', 'it',*/ 'ru');
                foreach($lang_mas as $item){
                    $product_lang = $em->getRepository('Main\Entity\ProductLang')->findOneBy(array('productId' => $id, 'lang' => $item));

                    $post_str = $_POST["description_$item"];
                    /*$post_str = "{".$_POST["description_$item"]."};{".$_POST["description_info_product_$item"]."};{"
                        .$_POST["description_info_quality_$item"]."};{".$_POST["description_packaging_$item"]."};{"
                        .$_POST["description_manual_$item"]."};";*/

                    $product_lang->setName($_POST["name_$item"]);
                    $product_lang->setDescription($post_str);
                    $product_lang->setTegTitle($_POST["meta_title_$item"]);
                    $product_lang->setTegKeywords($_POST["meta_keywords_$item"]);
                    $product_lang->setTegDescription($_POST["meta_description_$item"]);

                    $em->persist($product_lang);
                    $em->flush();
                }

                $status = 'success';
                $message = 'Продукт Обновлен';
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
            return array('form' => $form, 'product'=>$product, 'product_lang'=>$product_lang, 'product_image'=>$product_image);
        }

        if($message){
            $this->flashMessenger()
                ->setNamespace($status)
                ->addMessage($message);
        }
        return $this->redirect()->toRoute('admin/default', array('controller' => 'product'));
    }

    public function addSizeAction()
    {
        $em = $this->getEntityManager();

        $id = (int) $this->params()->fromRoute('id', 0);
        $product = $em->find('Main\Entity\Product', $id);
        if(empty($product)){
            $status = 'error';
            $message = 'Продукт не найден';
            $this->flashMessenger()
                ->setNamespace($status)
                ->addMessage($message);
            return $this->redirect()->toRoute('admin/default', array('controller' => 'product'));
        }
        $product_lang = $em->getRepository('Main\Entity\ProductLang')->findBy(array('productId' => $id));
        $product_image = $em->getRepository('Main\Entity\ProductImage')->findBy(array('productId' => $id));

        $product_new = new Product();

        $product_new->setName($product->getName());
        $product_new->setDescription($product->getDescription());
        $product_new->setMiniDesc($product->getMiniDesc());
        $product_new->setModel($product->getModel());
        $product_new->setCategory($product->getCategory());
        $product_new->setUnit($product->getUnit());
        $product_new->setDate(time());
        $product_new->setUrl($product->getUrl());
        $product_new->setPhoto($product->getPhoto());

        $em->persist($product_new);
        $em->flush();


        if(!empty($product_image)){
            foreach($product_image as $item)
            {
                $product_image_new = new ProductImage();

                $product_image_new->setProduct($product_new);
                $product_image_new->setUrl($item->getUrl());

                $em->persist($product_image_new);
                $em->flush();
            }
        }

        $lang_mas = array('en', 'it', 'ru');
        foreach($product_lang as $item)
        {
            $product_lang_new = new ProductLang();

            $product_lang_new->setProduct($product_new);
            $product_lang_new->setLang($item->getLang());
            $product_lang_new->setName($item->getName());
            $product_lang_new->setDescription($item->getDescription());
            $product_lang_new->setTegTitle($item->getTegTitle());
            $product_lang_new->setTegKeywords($item->getTegKeywords());
            $product_lang_new->setTegDescription($item->getTegDescription());

            $em->persist($product_lang_new);
            $em->flush();
        }

        return $this->redirect()->toRoute('admin/default', array('controller' => 'product', 'action' => 'edit', 'id' => $product_new->getId()));
    }

    public function removeAction()
    {
        $id = (int) $this->params()->fromRoute('id', 0);
        $em = $this->getEntityManager();
        $uploaddir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/product/';

        $status = 'success';
        $message = 'Продукт удален';

        try{
            $repository = $em->getRepository('Main\Entity\Product');
            $product = $repository->find($id);

            $product_image = $em->getRepository('Main\Entity\ProductImage')->findBy(array('productId' => $id));
            foreach($product_image as $item){
                unlink($uploaddir.$item->getUrl());
                //$em->remove($item);
                //$em->flush();
            }
            $photo = $product->getPhoto();
            if(!empty($photo))
                unlink($uploaddir.$product->getPhoto());

            $em->remove($product);
            $em->flush();
        }
        catch(\Exception $ex){
            $status = 'error';
            $message = 'Ошибка удаление ппродукта: ' . $ex->getMessage();
        }

        $this->flashMessenger()
            ->setNamespace($status)
            ->addMessage($message);

        return $this->redirect()->toRoute('admin/default', array('controller' => 'product'));
    }

    public function removeImgAction()
    {
        $id = (int) $this->params()->fromRoute('id', 0);
        $em = $this->getEntityManager();
        $uploaddir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/product/';

        try{
            $repository = $em->getRepository('Main\Entity\ProductImage');
            $product_image = $repository->find($id);

            unlink($uploaddir.$product_image->getUrl());
            $em->remove($product_image);
            $em->flush();
        }
        catch(\Exception $ex){
            $status = 'error';
            $message = 'Ошибка удаление: ' . $ex->getMessage();
        }

        return new JsonModel(array('ok'));
    }


    public function renameAction()
    {
        $em = $this->getEntityManager();

        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb
            ->select('a')
            ->from('Main\Entity\ProductLang', 'a')
            ->where("LOCATE('Московский инжиниринговый центр', a.description) != ''");
        //->setMaxResults(8)
        //->orderBy('a.popular', 'DESC');

        $query = $qb->getQuery();
        $product = $query->execute();

        foreach($product as $item){
            $item->setDescription(str_replace('Московский Инжиниринговый Центр', 'fourseasonsrussia.com', $item->getDescription()));

            $em->persist($item);
            $em->flush();
        }
        echo 'ok';
        exit;
    }
}


