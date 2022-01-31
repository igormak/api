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

use Main\Entity\Product;
use Main\Entity\ProductLang;
use Main\Entity\ProductImage;


class ParserController extends BaseController
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

    function resize($file)
    {
        // Ограничение по ширине в пикселях
        $max_size = 458;

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
        $sort = $_GET['sort'] == '' ? 'id' : $_GET['sort'];
        $order = $_GET['order'] == '' ? 'ASC' : $_GET['order'];

        $products = $this->getEntityManager()->createQueryBuilder();
        $products
            ->select('a')
            ->from('Main\Entity\Product', 'a')
            //->where("a.id != '1'");
            //->orderBy('a.'.$sort, $order);
            ->orderBy('a.id', 'DESC');

        $adapter = new DoctrineAdapter(new ORMPaginator($products));

        $paginator = new Paginator($adapter);
        $paginator->setDefaultItemCountPerPage($_GET['limit'] == '' ? 25 : $_GET['limit']);
        $paginator->setCurrentPageNumber((int) $this->params()->fromQuery('page', 1));

        return array('products' => $paginator);
    }

    public function addAction()
    {
        $form = new ProductAddForm;
        $status = $message = '';
        $em = $this->getEntityManager();

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

                    $photo_name = $product->getId()."_".$this->transliterate($_FILES['photo']['name']);
                    rename($uploaddir . $_FILES['photo']['name'], $uploaddir . $photo_name);
                    $this->resize($uploaddir . $photo_name);
                    $this->cropImage($uploaddir . $photo_name, $uploaddir . $photo_name, 458, 458);

                    $product->setPhoto($photo_name);
                }
                if(!empty($_FILES['photoAll']['name'][0])){
                    for($i=0; $i<count($_FILES['photoAll']['name']); $i++){
                        $uploadfile = $uploaddir.basename($_FILES['photoAll']['name'][$i]);

                        // Копируем файл из каталога для временного хранения файлов:
                        copy($_FILES['photoAll']['tmp_name'][$i], $uploadfile);

                        $photo_name = $product->getId()."_".$this->transliterate($_FILES['photoAll']['name'][$i]);
                        rename($uploaddir . $_FILES['photoAll']['name'][$i], $uploaddir . $photo_name);
                        $this->resize($uploaddir . $photo_name);
                        $this->cropImage($uploaddir . $photo_name, $uploaddir . $photo_name, 458, 458);

                        $product_iamge = new ProductImage();

                        $product_iamge->setProduct($product);
                        $product_iamge->setUrl($photo_name);

                        $em->persist($product_iamge);
                        $em->flush();
                    }
                }

                $em->persist($product);
                $em->flush();

                $lang_mas = array('en', 'it', 'ru');
                foreach($lang_mas as $item){
                    $product_lang = new ProductLang();

                    $product_lang->setLang($item);
                    $product_lang->setName($_POST["name_$item"]);
                    $product_lang->setProduct($product);
                    $product_lang->setTegTitle($_POST["meta_title_$item"]);
                    $product_lang->setDescription($_POST["description_$item"]);
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

                    $photo_name = $product->getId()."_".$this->transliterate($_FILES['photo']['name']);
                    rename($uploaddir . $_FILES['photo']['name'], $uploaddir . $photo_name);
                    $this->resize($uploaddir . $photo_name);
                    $this->cropImage($uploaddir . $photo_name, $uploaddir . $photo_name, 458, 458);

                    $product->setPhoto($photo_name);
                }
                if(!empty($_FILES['photoAll']['name'][0])){
                    for($i=0; $i<count($_FILES['photoAll']['name']); $i++){
                        $uploadfile = $uploaddir.basename($_FILES['photoAll']['name'][$i]);

                        // Копируем файл из каталога для временного хранения файлов:
                        copy($_FILES['photoAll']['tmp_name'][$i], $uploadfile);

                        $photo_name = $product->getId()."_".$this->transliterate($_FILES['photoAll']['name'][$i]);
                        rename($uploaddir . $_FILES['photoAll']['name'][$i], $uploaddir . $photo_name);
                        $this->resize($uploaddir . $photo_name);
                        $this->cropImage($uploaddir . $photo_name, $uploaddir . $photo_name, 458, 458);

                        $product_iamge = new ProductImage();

                        $product_iamge->setProduct($product);
                        $product_iamge->setUrl($photo_name);

                        $em->persist($product_iamge);
                        $em->flush();
                    }
                }

                $em->persist($product);
                $em->flush();

                $lang_mas = array('en', 'it', 'ru');
                foreach($lang_mas as $item){
                    $product_lang = $em->getRepository('Main\Entity\ProductLang')->findOneBy(array('productId' => $id, 'lang' => $item));

                    $product_lang->setName($_POST["name_$item"]);
                    $product_lang->setTegTitle($_POST["meta_title_$item"]);
                    $product_lang->setDescription($_POST["description_$item"]);
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
            return array('form' => $form, 'product' => $product, 'product_lang' => $product_lang);
        }

        if($message){
            $this->flashMessenger()
                ->setNamespace($status)
                ->addMessage($message);
        }
        return $this->redirect()->toRoute('admin/default', array('controller' => 'product'));
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

            $product_image = $em->getRepository('Main\Entity\productImage')->findBy(array('productId' => $id));
            foreach($product_image as $item){
                unlink($uploaddir.$item->getUrl());
                $em->remove($item);
                $em->flush();
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

    public function piustyleAction()
    {
        set_time_limit(0);
        $em = $this->getEntityManager();

        echo '<table border="1px" style="width: 100%">
            <thead>
                <tr>
                    <th>
                        Цена
                    </th>
                    <th>
                        Название
                    </th>
                    <th>
                        category
                    </th>
                    <th>
                        url
                    </th>
                    <th>
                        описание
                    </th>
                    <th>
                        foto
                    </th>
                </tr>
            </thead>
            <tbody>';


        for($page=1; $page<=12; $page++){
            $html = file_get_contents("https://www.piustyle.com/it/catalog/genere/donna/scarpe/$page?sort=taxable&dir=asc");
            $html = str_replace(chr(10), '',  str_replace(chr(13), '', $html));

            $patern1 = '/(?<=col-sm-6 col-md-4 catalog-product)(.*)(?=div class="c)/U';
            preg_match_all($patern1, $html, $product);

            foreach($product[0] as $product_itam){
                echo '<tr>';
                $patern2 = '/(?<=price taxable-price"><span class="price--amount">)(.*)(?=<\/)/U';
                preg_match_all($patern2, $product_itam, $cost);

                if($cost[0][0] <= 119){
                    //print_r($cost);
                    echo "<td>".$cost[0][0]."</td>";

                    $patern3 = '/(?<=product-name">)(.*)(?=<\/div)/U';
                    preg_match_all($patern3, $product_itam, $name);

                    //print_r($name);
                    echo "<td>".$name[0][0]."</td>";

                    $patern4 = '/(?<=product-category">)(.*)(?=<\/div)/U';
                    preg_match_all($patern4, $product_itam, $category);

                    //print_r($category);
                    echo "<td>".$category[0][0]."</td>";

                    $patern5 = '/(?<=product-id"><a href=")(.*)(?=">)/U';
                    preg_match_all($patern5, $product_itam, $url);

                    //print_r($url);
                    echo "<td>".'https://www.piustyle.com'.$url[0][0]."</td>";

                    $product = $em->getRepository('Main\Entity\Product')->findOneBy(array('url' => 'https://www.piustyle.com'.$url[0][0]));
                    if(empty($product))
                    {
                        $product = new Product();

                        $product->setName($name[0][0]);
                        $product->setPrice($cost[0][0]);
                        $product->setUrl('https://www.piustyle.com'.$url[0][0]);
                        $product->setDate(time());

                        $em->persist($product);
                        $em->flush();

                        $html = file_get_contents('https://www.piustyle.com'.$url[0][0]);
                        $html = str_replace(chr(10), '',  str_replace(chr(13), '', $html));

                        ////print_r($html);

                        $patern5 = '/(?<=description">)(.*)(?=<\/div>)/U';
                        preg_match_all($patern5, $html, $desc);

                        //print_r($desc);
                        echo "<td>".$desc[0][0]."</td>";

                        // языки
                        $lang_mas = array('en', 'it', 'ru');
                        foreach($lang_mas as $item){
                            $product_lang = new ProductLang();

                            $product_lang->setLang($item);
                            $product_lang->setName($name[0][0]);
                            $product_lang->setProduct($product);
                            $product_lang->setTegTitle($name[0][0]);
                            $product_lang->setDescription($desc[0][0]);
                            //$product_lang->setTegKeywords($_POST["meta_keywords_$item"]);
                            //$product_lang->setTegDescription($_POST["meta_description_$item"]);

                            $em->persist($product_lang);
                            $em->flush();
                        }

                        $patern6 = '/(?<=galleria")(.*)(?=<\/div>)/U';
                        preg_match_all($patern6, $html, $img);

                        $patern7 = '/(?<=src=")(.*)(?=\?w)/U';
                        preg_match_all($patern7, $img[0][0], $img);

                        //print_r($img);
                        echo "<td>";
                        $uploaddir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/product/';
                        if(isset($img[0][0]))
                        {
                            echo $img[0][0]."<br>";
                            // сохраняем фаил
                            $url = 'https://www.piustyle.com'.$img[0][0];
                            $photo_name = $product->getId()."_".time().".jpg";
                            file_put_contents($uploaddir . $photo_name, file_get_contents($url));

                            $this->resize($uploaddir . $photo_name);
                            $this->cropImage($uploaddir . $photo_name, $uploaddir . $photo_name, 458, 458);

                            $product->setPhoto($photo_name);

                            $em->persist($product);
                            $em->flush();
                        }

                        for($i=1; $i<count($img[0]); $i++)
                        {
                            echo $img[0][$i]."<br>";
                            // сохраняем фаил
                            $url = 'https://www.piustyle.com'.$img[0][$i];
                            $photo_name = $product->getId()."_".time().".jpg";
                            file_put_contents($uploaddir . $photo_name, file_get_contents($url));

                            $this->resize($uploaddir . $photo_name);
                            $this->cropImage($uploaddir . $photo_name, $uploaddir . $photo_name, 458, 458);

                            $product_iamge = new ProductImage();

                            $product_iamge->setProduct($product);
                            $product_iamge->setUrl($photo_name);

                            $em->persist($product_iamge);
                            $em->flush();
                        }
                        echo "</td>";
                    }

                }
                echo '</tr>';
            }
        }


        //сумки
        for($page=1; $page<=7; $page++){
            $html = file_get_contents("https://www.piustyle.com/it/catalog/genere/donna/borse/$page?tag_1=armani&tag_1=byblos&tag_1=cavalli-class&tag_1=coccinelle&tag_1=La+Martina&tag_1=made-in-italia&tag_1=sparco&tag_1=The+Bridge+Wayfarer&tag_1=trussardi&tag_1=versace-1969-abbigliamento-sportivo&tag_1=versace-jeans&sort=taxable&dir=asc");
            $html = str_replace(chr(10), '',  str_replace(chr(13), '', $html));

            $patern1 = '/(?<=col-sm-6 col-md-4 catalog-product)(.*)(?=div class="c)/U';
            preg_match_all($patern1, $html, $product);

            foreach($product[0] as $product_itam){
                echo '<tr>';
                $patern2 = '/(?<=price taxable-price"><span class="price--amount">)(.*)(?=<\/)/U';
                preg_match_all($patern2, $product_itam, $cost);

                if($cost[0][0] <= 119){
                    //print_r($cost);
                    echo "<td>".$cost[0][0]."</td>";

                    $patern3 = '/(?<=product-name">)(.*)(?=<\/div)/U';
                    preg_match_all($patern3, $product_itam, $name);

                    //print_r($name);
                    echo "<td>".$name[0][0]."</td>";

                    $patern4 = '/(?<=product-category">)(.*)(?=<\/div)/U';
                    preg_match_all($patern4, $product_itam, $category);

                    //print_r($category);
                    echo "<td>".$category[0][0]."</td>";

                    $patern5 = '/(?<=product-id"><a href=")(.*)(?=">)/U';
                    preg_match_all($patern5, $product_itam, $url);

                    //print_r($url);
                    echo "<td>".'https://www.piustyle.com'.$url[0][0]."</td>";

                    $product = $em->getRepository('Main\Entity\Product')->findOneBy(array('url' => 'https://www.piustyle.com'.$url[0][0]));
                    if(empty($product))
                    {
                        $product = new Product();

                        $product->setName($name[0][0]);
                        $product->setPrice($cost[0][0]);
                        $product->setUrl('https://www.piustyle.com'.$url[0][0]);
                        $product->setDate(time());

                        $em->persist($product);
                        $em->flush();

                        $html = file_get_contents('https://www.piustyle.com'.$url[0][0]);
                        $html = str_replace(chr(10), '',  str_replace(chr(13), '', $html));

                        ////print_r($html);

                        $patern5 = '/(?<=description">)(.*)(?=<\/div>)/U';
                        preg_match_all($patern5, $html, $desc);

                        //print_r($desc);
                        echo "<td>".$desc[0][0]."</td>";

                        // языки
                        $lang_mas = array('en', 'it', 'ru');
                        foreach($lang_mas as $item){
                            $product_lang = new ProductLang();

                            $product_lang->setLang($item);
                            $product_lang->setName($name[0][0]);
                            $product_lang->setProduct($product);
                            $product_lang->setTegTitle($name[0][0]);
                            $product_lang->setDescription($desc[0][0]);
                            //$product_lang->setTegKeywords($_POST["meta_keywords_$item"]);
                            //$product_lang->setTegDescription($_POST["meta_description_$item"]);

                            $em->persist($product_lang);
                            $em->flush();
                        }

                        $patern6 = '/(?<=galleria")(.*)(?=<\/div>)/U';
                        preg_match_all($patern6, $html, $img);

                        $patern7 = '/(?<=src=")(.*)(?=\?w)/U';
                        preg_match_all($patern7, $img[0][0], $img);

                        //print_r($img);
                        echo "<td>";
                        $uploaddir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/product/';
                        if(isset($img[0][0]))
                        {
                            echo $img[0][0]."<br>";
                            // сохраняем фаил
                            $url = 'https://www.piustyle.com'.$img[0][0];
                            $photo_name = $product->getId()."_".time().".jpg";
                            file_put_contents($uploaddir . $photo_name, file_get_contents($url));

                            $this->resize($uploaddir . $photo_name);
                            $this->cropImage($uploaddir . $photo_name, $uploaddir . $photo_name, 458, 458);

                            $product->setPhoto($photo_name);

                            $em->persist($product);
                            $em->flush();
                        }

                        for($i=1; $i<count($img[0]); $i++)
                        {
                            echo $img[0][$i]."<br>";
                            // сохраняем фаил
                            $url = 'https://www.piustyle.com'.$img[0][$i];
                            $photo_name = $product->getId()."_".time().".jpg";
                            file_put_contents($uploaddir . $photo_name, file_get_contents($url));

                            $this->resize($uploaddir . $photo_name);
                            $this->cropImage($uploaddir . $photo_name, $uploaddir . $photo_name, 458, 458);

                            $product_iamge = new ProductImage();

                            $product_iamge->setProduct($product);
                            $product_iamge->setUrl($photo_name);

                            $em->persist($product_iamge);
                            $em->flush();
                        }
                        echo "</td>";
                    }

                }
                echo '</tr>';
            }
        }


        //ACCESSORI
        for($page=1; $page<=3; $page++){
            $html = file_get_contents("https://www.piustyle.com/it/catalog/genere/donna/accessori/$page?tag_1=armani&tag_1=chloe&tag_1=guess&tag_1=just-cavalli&tag_1=trussardi&tag_1=valentino&tag_1=versace-jeans&sort=taxable&dir=asc");
            $html = str_replace(chr(10), '',  str_replace(chr(13), '', $html));

            $patern1 = '/(?<=col-sm-6 col-md-4 catalog-product)(.*)(?=div class="c)/U';
            preg_match_all($patern1, $html, $product);

            foreach($product[0] as $product_itam){
                echo '<tr>';
                $patern2 = '/(?<=price taxable-price"><span class="price--amount">)(.*)(?=<\/)/U';
                preg_match_all($patern2, $product_itam, $cost);

                if($cost[0][0] <= 119){
                    //print_r($cost);
                    echo "<td>".$cost[0][0]."</td>";

                    $patern3 = '/(?<=product-name">)(.*)(?=<\/div)/U';
                    preg_match_all($patern3, $product_itam, $name);

                    //print_r($name);
                    echo "<td>".$name[0][0]."</td>";

                    $patern4 = '/(?<=product-category">)(.*)(?=<\/div)/U';
                    preg_match_all($patern4, $product_itam, $category);

                    //print_r($category);
                    echo "<td>".$category[0][0]."</td>";

                    $patern5 = '/(?<=product-id"><a href=")(.*)(?=">)/U';
                    preg_match_all($patern5, $product_itam, $url);

                    //print_r($url);
                    echo "<td>".'https://www.piustyle.com'.$url[0][0]."</td>";

                    $product = $em->getRepository('Main\Entity\Product')->findOneBy(array('url' => 'https://www.piustyle.com'.$url[0][0]));
                    if(empty($product))
                    {
                        $product = new Product();

                        $product->setName($name[0][0]);
                        $product->setPrice($cost[0][0]);
                        $product->setUrl('https://www.piustyle.com'.$url[0][0]);
                        $product->setDate(time());

                        $em->persist($product);
                        $em->flush();

                        $html = file_get_contents('https://www.piustyle.com'.$url[0][0]);
                        $html = str_replace(chr(10), '',  str_replace(chr(13), '', $html));

                        ////print_r($html);

                        $patern5 = '/(?<=description">)(.*)(?=<\/div>)/U';
                        preg_match_all($patern5, $html, $desc);

                        //print_r($desc);
                        echo "<td>".$desc[0][0]."</td>";

                        // языки
                        $lang_mas = array('en', 'it', 'ru');
                        foreach($lang_mas as $item){
                            $product_lang = new ProductLang();

                            $product_lang->setLang($item);
                            $product_lang->setName($name[0][0]);
                            $product_lang->setProduct($product);
                            $product_lang->setTegTitle($name[0][0]);
                            $product_lang->setDescription($desc[0][0]);
                            //$product_lang->setTegKeywords($_POST["meta_keywords_$item"]);
                            //$product_lang->setTegDescription($_POST["meta_description_$item"]);

                            $em->persist($product_lang);
                            $em->flush();
                        }

                        $patern6 = '/(?<=galleria")(.*)(?=<\/div>)/U';
                        preg_match_all($patern6, $html, $img);

                        $patern7 = '/(?<=src=")(.*)(?=\?w)/U';
                        preg_match_all($patern7, $img[0][0], $img);

                        //print_r($img);
                        echo "<td>";
                        $uploaddir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/product/';
                        if(isset($img[0][0]))
                        {
                            echo $img[0][0]."<br>";
                            // сохраняем фаил
                            $url = 'https://www.piustyle.com'.$img[0][0];
                            $photo_name = $product->getId()."_".time().".jpg";
                            file_put_contents($uploaddir . $photo_name, file_get_contents($url));

                            $this->resize($uploaddir . $photo_name);
                            $this->cropImage($uploaddir . $photo_name, $uploaddir . $photo_name, 458, 458);

                            $product->setPhoto($photo_name);

                            $em->persist($product);
                            $em->flush();
                        }

                        for($i=1; $i<count($img[0]); $i++)
                        {
                            echo $img[0][$i]."<br>";
                            // сохраняем фаил
                            $url = 'https://www.piustyle.com'.$img[0][$i];
                            $photo_name = $product->getId()."_".time().".jpg";
                            file_put_contents($uploaddir . $photo_name, file_get_contents($url));

                            $this->resize($uploaddir . $photo_name);
                            $this->cropImage($uploaddir . $photo_name, $uploaddir . $photo_name, 458, 458);

                            $product_iamge = new ProductImage();

                            $product_iamge->setProduct($product);
                            $product_iamge->setUrl($photo_name);

                            $em->persist($product_iamge);
                            $em->flush();
                        }
                        echo "</td>";
                    }

                }
                echo '</tr>';
            }
        }


        //UOMO SCARPE
        for($page=1; $page<=10; $page++){
            $html = file_get_contents("https://www.piustyle.com/it/catalog/genere/uomo/scarpe/$page?sort=taxable&dir=asc");
            $html = str_replace(chr(10), '',  str_replace(chr(13), '', $html));

            $patern1 = '/(?<=col-sm-6 col-md-4 catalog-product)(.*)(?=div class="c)/U';
            preg_match_all($patern1, $html, $product);

            foreach($product[0] as $product_itam){
                echo '<tr>';
                $patern2 = '/(?<=price taxable-price"><span class="price--amount">)(.*)(?=<\/)/U';
                preg_match_all($patern2, $product_itam, $cost);

                if($cost[0][0] <= 119){
                    //print_r($cost);
                    echo "<td>".$cost[0][0]."</td>";

                    $patern3 = '/(?<=product-name">)(.*)(?=<\/div)/U';
                    preg_match_all($patern3, $product_itam, $name);

                    //print_r($name);
                    echo "<td>".$name[0][0]."</td>";

                    $patern4 = '/(?<=product-category">)(.*)(?=<\/div)/U';
                    preg_match_all($patern4, $product_itam, $category);

                    //print_r($category);
                    echo "<td>".$category[0][0]."</td>";

                    $patern5 = '/(?<=product-id"><a href=")(.*)(?=">)/U';
                    preg_match_all($patern5, $product_itam, $url);

                    //print_r($url);
                    echo "<td>".'https://www.piustyle.com'.$url[0][0]."</td>";

                    $product = $em->getRepository('Main\Entity\Product')->findOneBy(array('url' => 'https://www.piustyle.com'.$url[0][0]));
                    if(empty($product))
                    {
                        $product = new Product();

                        $product->setName($name[0][0]);
                        $product->setPrice($cost[0][0]);
                        $product->setUrl('https://www.piustyle.com'.$url[0][0]);
                        $product->setDate(time());

                        $em->persist($product);
                        $em->flush();

                        $html = file_get_contents('https://www.piustyle.com'.$url[0][0]);
                        $html = str_replace(chr(10), '',  str_replace(chr(13), '', $html));

                        ////print_r($html);

                        $patern5 = '/(?<=description">)(.*)(?=<\/div>)/U';
                        preg_match_all($patern5, $html, $desc);

                        //print_r($desc);
                        echo "<td>".$desc[0][0]."</td>";

                        // языки
                        $lang_mas = array('en', 'it', 'ru');
                        foreach($lang_mas as $item){
                            $product_lang = new ProductLang();

                            $product_lang->setLang($item);
                            $product_lang->setName($name[0][0]);
                            $product_lang->setProduct($product);
                            $product_lang->setTegTitle($name[0][0]);
                            $product_lang->setDescription($desc[0][0]);
                            //$product_lang->setTegKeywords($_POST["meta_keywords_$item"]);
                            //$product_lang->setTegDescription($_POST["meta_description_$item"]);

                            $em->persist($product_lang);
                            $em->flush();
                        }

                        $patern6 = '/(?<=galleria")(.*)(?=<\/div>)/U';
                        preg_match_all($patern6, $html, $img);

                        $patern7 = '/(?<=src=")(.*)(?=\?w)/U';
                        preg_match_all($patern7, $img[0][0], $img);

                        //print_r($img);
                        echo "<td>";
                        $uploaddir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/product/';
                        if(isset($img[0][0]))
                        {
                            echo $img[0][0]."<br>";
                            // сохраняем фаил
                            $url = 'https://www.piustyle.com'.$img[0][0];
                            $photo_name = $product->getId()."_".time().".jpg";
                            file_put_contents($uploaddir . $photo_name, file_get_contents($url));

                            $this->resize($uploaddir . $photo_name);
                            $this->cropImage($uploaddir . $photo_name, $uploaddir . $photo_name, 458, 458);

                            $product->setPhoto($photo_name);

                            $em->persist($product);
                            $em->flush();
                        }

                        for($i=1; $i<count($img[0]); $i++)
                        {
                            echo $img[0][$i]."<br>";
                            // сохраняем фаил
                            $url = 'https://www.piustyle.com'.$img[0][$i];
                            $photo_name = $product->getId()."_".time().".jpg";
                            file_put_contents($uploaddir . $photo_name, file_get_contents($url));

                            $this->resize($uploaddir . $photo_name);
                            $this->cropImage($uploaddir . $photo_name, $uploaddir . $photo_name, 458, 458);

                            $product_iamge = new ProductImage();

                            $product_iamge->setProduct($product);
                            $product_iamge->setUrl($photo_name);

                            $em->persist($product_iamge);
                            $em->flush();
                        }
                        echo "</td>";
                    }

                }
                echo '</tr>';
            }
        }


        //потом возврат на главную по UOMO нажимешь BORSE и все сумки только надо выключить SPARTOO так как там чемоданы. они обьемные и мы их не будем выставлять.
        for($page=1; $page<=3; $page++){
            $html = file_get_contents("https://www.piustyle.com/it/catalog/genere/uomo/borse/$page?tag_1=La+Martina&tag_1=trussardi&tag_1=versace-1969-abbigliamento-sportivo");
            $html = str_replace(chr(10), '',  str_replace(chr(13), '', $html));

            $patern1 = '/(?<=col-sm-6 col-md-4 catalog-product)(.*)(?=div class="c)/U';
            preg_match_all($patern1, $html, $product);

            foreach($product[0] as $product_itam){
                echo '<tr>';
                $patern2 = '/(?<=price taxable-price"><span class="price--amount">)(.*)(?=<\/)/U';
                preg_match_all($patern2, $product_itam, $cost);

                if($cost[0][0] <= 119){
                    //print_r($cost);
                    echo "<td>".$cost[0][0]."</td>";

                    $patern3 = '/(?<=product-name">)(.*)(?=<\/div)/U';
                    preg_match_all($patern3, $product_itam, $name);

                    //print_r($name);
                    echo "<td>".$name[0][0]."</td>";

                    $patern4 = '/(?<=product-category">)(.*)(?=<\/div)/U';
                    preg_match_all($patern4, $product_itam, $category);

                    //print_r($category);
                    echo "<td>".$category[0][0]."</td>";

                    $patern5 = '/(?<=product-id"><a href=")(.*)(?=">)/U';
                    preg_match_all($patern5, $product_itam, $url);

                    //print_r($url);
                    echo "<td>".'https://www.piustyle.com'.$url[0][0]."</td>";

                    $product = $em->getRepository('Main\Entity\Product')->findOneBy(array('url' => 'https://www.piustyle.com'.$url[0][0]));
                    if(empty($product))
                    {
                        $product = new Product();

                        $product->setName($name[0][0]);
                        $product->setPrice($cost[0][0]);
                        $product->setUrl('https://www.piustyle.com'.$url[0][0]);
                        $product->setDate(time());

                        $em->persist($product);
                        $em->flush();

                        $html = file_get_contents('https://www.piustyle.com'.$url[0][0]);
                        $html = str_replace(chr(10), '',  str_replace(chr(13), '', $html));

                        ////print_r($html);

                        $patern5 = '/(?<=description">)(.*)(?=<\/div>)/U';
                        preg_match_all($patern5, $html, $desc);

                        //print_r($desc);
                        echo "<td>".$desc[0][0]."</td>";

                        // языки
                        $lang_mas = array('en', 'it', 'ru');
                        foreach($lang_mas as $item){
                            $product_lang = new ProductLang();

                            $product_lang->setLang($item);
                            $product_lang->setName($name[0][0]);
                            $product_lang->setProduct($product);
                            $product_lang->setTegTitle($name[0][0]);
                            $product_lang->setDescription($desc[0][0]);
                            //$product_lang->setTegKeywords($_POST["meta_keywords_$item"]);
                            //$product_lang->setTegDescription($_POST["meta_description_$item"]);

                            $em->persist($product_lang);
                            $em->flush();
                        }

                        $patern6 = '/(?<=galleria")(.*)(?=<\/div>)/U';
                        preg_match_all($patern6, $html, $img);

                        $patern7 = '/(?<=src=")(.*)(?=\?w)/U';
                        preg_match_all($patern7, $img[0][0], $img);

                        //print_r($img);
                        echo "<td>";
                        $uploaddir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/product/';
                        if(isset($img[0][0]))
                        {
                            echo $img[0][0]."<br>";
                            // сохраняем фаил
                            $url = 'https://www.piustyle.com'.$img[0][0];
                            $photo_name = $product->getId()."_".time().".jpg";
                            file_put_contents($uploaddir . $photo_name, file_get_contents($url));

                            $this->resize($uploaddir . $photo_name);
                            $this->cropImage($uploaddir . $photo_name, $uploaddir . $photo_name, 458, 458);

                            $product->setPhoto($photo_name);

                            $em->persist($product);
                            $em->flush();
                        }

                        for($i=1; $i<count($img[0]); $i++)
                        {
                            echo $img[0][$i]."<br>";
                            // сохраняем фаил
                            $url = 'https://www.piustyle.com'.$img[0][$i];
                            $photo_name = $product->getId()."_".time().".jpg";
                            file_put_contents($uploaddir . $photo_name, file_get_contents($url));

                            $this->resize($uploaddir . $photo_name);
                            $this->cropImage($uploaddir . $photo_name, $uploaddir . $photo_name, 458, 458);

                            $product_iamge = new ProductImage();

                            $product_iamge->setProduct($product);
                            $product_iamge->setUrl($photo_name);

                            $em->persist($product_iamge);
                            $em->flush();
                        }
                        echo "</td>";
                    }

                }
                echo '</tr>';
            }
        }


        //Возврат на главную заходим на ACCESSORI выбираем OCCHIALI DA SOLE и выбираем CARRERA DIESEL GUESS JUST CAVALLI LACOSTE WEB.
        for($page=1; $page<=3; $page++){
            $html = file_get_contents("https://www.piustyle.com/it/catalog/genere/donna/accessori/$page?tag_1=Carrera&tag_1=diesel&tag_1=guess&tag_1=just-cavalli&tag_1=Lacoste&tag_1=Web&tag_5=occhiali-da-sole");
            $html = str_replace(chr(10), '',  str_replace(chr(13), '', $html));

            $patern1 = '/(?<=col-sm-6 col-md-4 catalog-product)(.*)(?=div class="c)/U';
            preg_match_all($patern1, $html, $product);

            foreach($product[0] as $product_itam){
                echo '<tr>';
                $patern2 = '/(?<=price taxable-price"><span class="price--amount">)(.*)(?=<\/)/U';
                preg_match_all($patern2, $product_itam, $cost);

                if($cost[0][0] <= 119){
                    //print_r($cost);
                    echo "<td>".$cost[0][0]."</td>";

                    $patern3 = '/(?<=product-name">)(.*)(?=<\/div)/U';
                    preg_match_all($patern3, $product_itam, $name);

                    //print_r($name);
                    echo "<td>".$name[0][0]."</td>";

                    $patern4 = '/(?<=product-category">)(.*)(?=<\/div)/U';
                    preg_match_all($patern4, $product_itam, $category);

                    //print_r($category);
                    echo "<td>".$category[0][0]."</td>";

                    $patern5 = '/(?<=product-id"><a href=")(.*)(?=">)/U';
                    preg_match_all($patern5, $product_itam, $url);

                    //print_r($url);
                    echo "<td>".'https://www.piustyle.com'.$url[0][0]."</td>";

                    $product = $em->getRepository('Main\Entity\Product')->findOneBy(array('url' => 'https://www.piustyle.com'.$url[0][0]));
                    if(empty($product))
                    {
                        $product = new Product();

                        $product->setName($name[0][0]);
                        $product->setPrice($cost[0][0]);
                        $product->setUrl('https://www.piustyle.com'.$url[0][0]);
                        $product->setDate(time());

                        $em->persist($product);
                        $em->flush();

                        $html = file_get_contents('https://www.piustyle.com'.$url[0][0]);
                        $html = str_replace(chr(10), '',  str_replace(chr(13), '', $html));

                        ////print_r($html);

                        $patern5 = '/(?<=description">)(.*)(?=<\/div>)/U';
                        preg_match_all($patern5, $html, $desc);

                        //print_r($desc);
                        echo "<td>".$desc[0][0]."</td>";

                        // языки
                        $lang_mas = array('en', 'it', 'ru');
                        foreach($lang_mas as $item){
                            $product_lang = new ProductLang();

                            $product_lang->setLang($item);
                            $product_lang->setName($name[0][0]);
                            $product_lang->setProduct($product);
                            $product_lang->setTegTitle($name[0][0]);
                            $product_lang->setDescription($desc[0][0]);
                            //$product_lang->setTegKeywords($_POST["meta_keywords_$item"]);
                            //$product_lang->setTegDescription($_POST["meta_description_$item"]);

                            $em->persist($product_lang);
                            $em->flush();
                        }

                        $patern6 = '/(?<=galleria")(.*)(?=<\/div>)/U';
                        preg_match_all($patern6, $html, $img);

                        $patern7 = '/(?<=src=")(.*)(?=\?w)/U';
                        preg_match_all($patern7, $img[0][0], $img);

                        //print_r($img);
                        echo "<td>";
                        $uploaddir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/product/';
                        if(isset($img[0][0]))
                        {
                            echo $img[0][0]."<br>";
                            // сохраняем фаил
                            $url = 'https://www.piustyle.com'.$img[0][0];
                            $photo_name = $product->getId()."_".time().".jpg";
                            file_put_contents($uploaddir . $photo_name, file_get_contents($url));

                            $this->resize($uploaddir . $photo_name);
                            $this->cropImage($uploaddir . $photo_name, $uploaddir . $photo_name, 458, 458);

                            $product->setPhoto($photo_name);

                            $em->persist($product);
                            $em->flush();
                        }

                        for($i=1; $i<count($img[0]); $i++)
                        {
                            echo $img[0][$i]."<br>";
                            // сохраняем фаил
                            $url = 'https://www.piustyle.com'.$img[0][$i];
                            $photo_name = $product->getId()."_".time().".jpg";
                            file_put_contents($uploaddir . $photo_name, file_get_contents($url));

                            $this->resize($uploaddir . $photo_name);
                            $this->cropImage($uploaddir . $photo_name, $uploaddir . $photo_name, 458, 458);

                            $product_iamge = new ProductImage();

                            $product_iamge->setProduct($product);
                            $product_iamge->setUrl($photo_name);

                            $em->persist($product_iamge);
                            $em->flush();
                        }
                        echo "</td>";
                    }

                }
                echo '</tr>';
            }
        }


        //возврат назад в SOTTOCATEGORIA нажимаем CINTURE и берем все пояса. ; опять назад в SOTTOCATEGORIA -PORTAFOGLI берем все кошельки.
        for($page=1; $page<=3; $page++){
            $html = file_get_contents("https://www.piustyle.com/it/catalog/genere/donna/accessori/$page?tag_5=cinture&tag_5=portafogli");
            $html = str_replace(chr(10), '',  str_replace(chr(13), '', $html));

            $patern1 = '/(?<=col-sm-6 col-md-4 catalog-product)(.*)(?=div class="c)/U';
            preg_match_all($patern1, $html, $product);

            foreach($product[0] as $product_itam){
                echo '<tr>';
                $patern2 = '/(?<=price taxable-price"><span class="price--amount">)(.*)(?=<\/)/U';
                preg_match_all($patern2, $product_itam, $cost);

                if($cost[0][0] <= 119){
                    //print_r($cost);
                    echo "<td>".$cost[0][0]."</td>";

                    $patern3 = '/(?<=product-name">)(.*)(?=<\/div)/U';
                    preg_match_all($patern3, $product_itam, $name);

                    //print_r($name);
                    echo "<td>".$name[0][0]."</td>";

                    $patern4 = '/(?<=product-category">)(.*)(?=<\/div)/U';
                    preg_match_all($patern4, $product_itam, $category);

                    //print_r($category);
                    echo "<td>".$category[0][0]."</td>";

                    $patern5 = '/(?<=product-id"><a href=")(.*)(?=">)/U';
                    preg_match_all($patern5, $product_itam, $url);

                    //print_r($url);
                    echo "<td>".'https://www.piustyle.com'.$url[0][0]."</td>";

                    $product = $em->getRepository('Main\Entity\Product')->findOneBy(array('url' => 'https://www.piustyle.com'.$url[0][0]));
                    if(empty($product))
                    {
                        $product = new Product();

                        $product->setName($name[0][0]);
                        $product->setPrice($cost[0][0]);
                        $product->setUrl('https://www.piustyle.com'.$url[0][0]);
                        $product->setDate(time());

                        $em->persist($product);
                        $em->flush();

                        $html = file_get_contents('https://www.piustyle.com'.$url[0][0]);
                        $html = str_replace(chr(10), '',  str_replace(chr(13), '', $html));

                        ////print_r($html);

                        $patern5 = '/(?<=description">)(.*)(?=<\/div>)/U';
                        preg_match_all($patern5, $html, $desc);

                        //print_r($desc);
                        echo "<td>".$desc[0][0]."</td>";

                        // языки
                        $lang_mas = array('en', 'it', 'ru');
                        foreach($lang_mas as $item){
                            $product_lang = new ProductLang();

                            $product_lang->setLang($item);
                            $product_lang->setName($name[0][0]);
                            $product_lang->setProduct($product);
                            $product_lang->setTegTitle($name[0][0]);
                            $product_lang->setDescription($desc[0][0]);
                            //$product_lang->setTegKeywords($_POST["meta_keywords_$item"]);
                            //$product_lang->setTegDescription($_POST["meta_description_$item"]);

                            $em->persist($product_lang);
                            $em->flush();
                        }

                        $patern6 = '/(?<=galleria")(.*)(?=<\/div>)/U';
                        preg_match_all($patern6, $html, $img);

                        $patern7 = '/(?<=src=")(.*)(?=\?w)/U';
                        preg_match_all($patern7, $img[0][0], $img);

                        //print_r($img);
                        echo "<td>";
                        $uploaddir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/product/';
                        if(isset($img[0][0]))
                        {
                            echo $img[0][0]."<br>";
                            // сохраняем фаил
                            $url = 'https://www.piustyle.com'.$img[0][0];
                            $photo_name = $product->getId()."_".time().".jpg";
                            file_put_contents($uploaddir . $photo_name, file_get_contents($url));

                            $this->resize($uploaddir . $photo_name);
                            $this->cropImage($uploaddir . $photo_name, $uploaddir . $photo_name, 458, 458);

                            $product->setPhoto($photo_name);

                            $em->persist($product);
                            $em->flush();
                        }

                        for($i=1; $i<count($img[0]); $i++)
                        {
                            echo $img[0][$i]."<br>";
                            // сохраняем фаил
                            $url = 'https://www.piustyle.com'.$img[0][$i];
                            $photo_name = $product->getId()."_".time().".jpg";
                            file_put_contents($uploaddir . $photo_name, file_get_contents($url));

                            $this->resize($uploaddir . $photo_name);
                            $this->cropImage($uploaddir . $photo_name, $uploaddir . $photo_name, 458, 458);

                            $product_iamge = new ProductImage();

                            $product_iamge->setProduct($product);
                            $product_iamge->setUrl($photo_name);

                            $em->persist($product_iamge);
                            $em->flush();
                        }
                        echo "</td>";
                    }

                }
                echo '</tr>';
            }
        }


        echo '</tr>
            </tbody>
        </table>';

    }

    public function fratinardiAction()
    {
        set_time_limit(0);
        $em = $this->getEntityManager();

        echo '<table border="1px" style="width: 100%">
            <thead>
            <tr>
                <th>
                    Цена
                </th>
                <th>
                    Название
                </th>
                <!-- th>
                    category
                </th -->
                <th>
                    url
                </th>
                <th>
                    описание
                </th>
                <th>
                    foto
                </th>
            </tr>
            </thead>
            <tbody>';


        // OUTLET - DONNA- SCARPE- PREZZO 0-99 STAGIONE- PRIMAVERA - ESTATE там будет где-то 2 страницы
        for($page=1; $page<=3; $page++){
            $html = file_get_contents("https://www.fratinardi.it/it/outlet/donna/scarpe.html?p=$page&price=-100&stagionalita=366");
            $html = str_replace(chr(10), '',  str_replace(chr(13), '', $html));

            $patern1 = '/(?<=li class="tqty)(.*)(?=li>)/sU';
            preg_match_all($patern1, $html, $product);
            //print_r($product);

            //$product_itam = $product[0][0];
            foreach($product[0] as $product_itam){
                echo '<tr>';
                $patern2 = '/(?<=€)(.*)(?=<\/span>)/U';
                preg_match_all($patern2, $product_itam, $cost);

                $cost = $cost[0][1] == '' ? $cost[0][0] : $cost[0][1];

                if($cost <= 999){
                    //print_r($cost);
                    echo "<td>".$cost."</td>";

                    $patern3 = '/(?<=title=")(.*)(?=")/U';
                    preg_match_all($patern3, $product_itam, $name);

                    //print_r($name);
                    echo "<td>".$name[0][0]."</td>";

                    /*$patern4 = '/(?<=product-category">)(.*)(?=<\/div)/U';
                    preg_match_all($patern4, $product_itam, $category);

                    //print_r($category);
                    echo "<td>".$category[0][0]."</td>";*/

                    $patern5 = '/(?<=product-name"><a href=")(.*)(?=" title)/U';
                    preg_match_all($patern5, $product_itam, $url);

                    //print_r($url);
                    echo "<td>".$url[0][0]."</td>";

                    $product = $em->getRepository('Main\Entity\Product')->findOneBy(array('url' => $url[0][0]));
                    if(empty($product))
                    {
                        $product = new Product();

                        $product->setName($name[0][0]);
                        preg_match('/[0-9]+,[0-9]+/', $cost, $cost2);
                        $product->setPrice(str_replace(',', '.', $cost2[0]));
                        $product->setUrl($url[0][0]);
                        $product->setDate(time());

                        $em->persist($product);
                        $em->flush();

                        $html = file_get_contents($url[0][0]);
                        $html = str_replace(chr(10), '',  str_replace(chr(13), '', $html));

                        ////print_r($html);

                        $patern5 = '/(?<=std">)(.*)(?=<\/div>)/U';
                        preg_match_all($patern5, $html, $desc);

                        //print_r($desc);
                        echo "<td>".$desc[0][1]."</td>";

                        // языки
                        $lang_mas = array('en', 'it', 'ru');
                        foreach($lang_mas as $item){
                            $product_lang = new ProductLang();

                            $product_lang->setLang($item);
                            $product_lang->setName($name[0][0]);
                            $product_lang->setProduct($product);
                            $product_lang->setTegTitle($name[0][0]);
                            $product_lang->setDescription($desc[0][1]);
                            //$product_lang->setTegKeywords($_POST["meta_keywords_$item"]);
                            //$product_lang->setTegDescription($_POST["meta_description_$item"]);

                            $em->persist($product_lang);
                            $em->flush();
                        }

                        // all photo
                        $patern6 = '/(?<=ul-moreviews)(.*)(?=ul>)/U';
                        preg_match_all($patern6, $html, $img);

                        //print_r($img);

                        $patern7 = '/(?<=href=")(.*)(?=")/U';
                        preg_match_all($patern7, $img[0][0], $img);

                        //print_r($img);
                        echo "<td>";
                        $uploaddir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/product/';
                        if(isset($img[0][0])){
                            // сохраняем фаил
                            $url = $img[0][0];
                            $photo_name = $product->getId()."_".time().".jpg";
                            file_put_contents($uploaddir . $photo_name, file_get_contents($url));

                            $this->resize($uploaddir . $photo_name);
                            $this->cropImage($uploaddir . $photo_name, $uploaddir . $photo_name, 458, 458);

                            $product->setPhoto($photo_name);

                            $em->persist($product);
                            $em->flush();
                        }

                        for($i=1; $i<count($img[0]); $i++)
                        {
                            // сохраняем фаил
                            $url = $img[0][$i];
                            $photo_name = $product->getId()."_".time().".jpg";
                            file_put_contents($uploaddir . $photo_name, file_get_contents($url));

                            $this->resize($uploaddir . $photo_name);
                            $this->cropImage($uploaddir . $photo_name, $uploaddir . $photo_name, 458, 458);

                            $product_iamge = new ProductImage();

                            $product_iamge->setProduct($product);
                            $product_iamge->setUrl($photo_name);

                            $em->persist($product_iamge);
                            $em->flush();
                        }
                        echo "</td>";
                    }
                }
                echo '</tr>';
            }
        }


        // OUTLET - UOMO- SCARPE- PREZZO 0-99 здесь тоже 2 страницы
        for($page=1; $page<=3; $page++){
            $html = file_get_contents("https://www.fratinardi.it/it/outlet/uomo/scarpe.html?p=$page&price=-100");
            $html = str_replace(chr(10), '',  str_replace(chr(13), '', $html));

            $patern1 = '/(?<=li class="tqty)(.*)(?=li>)/sU';
            preg_match_all($patern1, $html, $product);
            //print_r($product);

            //$product_itam = $product[0][0];
            foreach($product[0] as $product_itam){
                echo '<tr>';
                $patern2 = '/(?<=€)(.*)(?=<\/span>)/U';
                preg_match_all($patern2, $product_itam, $cost);

                $cost = $cost[0][1] == '' ? $cost[0][0] : $cost[0][1];

                if($cost <= 999){
                    //print_r($cost);
                    echo "<td>".$cost."</td>";

                    $patern3 = '/(?<=title=")(.*)(?=")/U';
                    preg_match_all($patern3, $product_itam, $name);

                    //print_r($name);
                    echo "<td>".$name[0][0]."</td>";

                    /*$patern4 = '/(?<=product-category">)(.*)(?=<\/div)/U';
                    preg_match_all($patern4, $product_itam, $category);

                    //print_r($category);
                    echo "<td>".$category[0][0]."</td>";*/

                    $patern5 = '/(?<=product-name"><a href=")(.*)(?=" title)/U';
                    preg_match_all($patern5, $product_itam, $url);

                    //print_r($url);
                    echo "<td>".$url[0][0]."</td>";

                    $product = $em->getRepository('Main\Entity\Product')->findOneBy(array('url' => $url[0][0]));
                    if(empty($product))
                    {
                        $product = new Product();

                        $product->setName($name[0][0]);
                        preg_match('/[0-9]+,[0-9]+/', $cost, $cost2);
                        $product->setPrice(str_replace(',', '.', $cost2[0]));
                        $product->setUrl($url[0][0]);
                        $product->setDate(time());

                        $em->persist($product);
                        $em->flush();

                        $html = file_get_contents($url[0][0]);
                        $html = str_replace(chr(10), '',  str_replace(chr(13), '', $html));

                        ////print_r($html);

                        $patern5 = '/(?<=std">)(.*)(?=<\/div>)/U';
                        preg_match_all($patern5, $html, $desc);

                        //print_r($desc);
                        echo "<td>".$desc[0][1]."</td>";

                        // языки
                        $lang_mas = array('en', 'it', 'ru');
                        foreach($lang_mas as $item){
                            $product_lang = new ProductLang();

                            $product_lang->setLang($item);
                            $product_lang->setName($name[0][0]);
                            $product_lang->setProduct($product);
                            $product_lang->setTegTitle($name[0][0]);
                            $product_lang->setDescription($desc[0][1]);
                            //$product_lang->setTegKeywords($_POST["meta_keywords_$item"]);
                            //$product_lang->setTegDescription($_POST["meta_description_$item"]);

                            $em->persist($product_lang);
                            $em->flush();
                        }

                        // all photo
                        $patern6 = '/(?<=ul-moreviews)(.*)(?=ul>)/U';
                        preg_match_all($patern6, $html, $img);

                        //print_r($img);

                        $patern7 = '/(?<=href=")(.*)(?=")/U';
                        preg_match_all($patern7, $img[0][0], $img);

                        //print_r($img);
                        echo "<td>";
                        $uploaddir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/product/';
                        if(isset($img[0][0])){
                            // сохраняем фаил
                            $url = $img[0][0];
                            $photo_name = $product->getId()."_".time().".jpg";
                            file_put_contents($uploaddir . $photo_name, file_get_contents($url));

                            $this->resize($uploaddir . $photo_name);
                            $this->cropImage($uploaddir . $photo_name, $uploaddir . $photo_name, 458, 458);

                            $product->setPhoto($photo_name);

                            $em->persist($product);
                            $em->flush();
                        }

                        for($i=1; $i<count($img[0]); $i++)
                        {
                            // сохраняем фаил
                            $url = $img[0][$i];
                            $photo_name = $product->getId()."_".time().".jpg";
                            file_put_contents($uploaddir . $photo_name, file_get_contents($url));

                            $this->resize($uploaddir . $photo_name);
                            $this->cropImage($uploaddir . $photo_name, $uploaddir . $photo_name, 458, 458);

                            $product_iamge = new ProductImage();

                            $product_iamge->setProduct($product);
                            $product_iamge->setUrl($photo_name);

                            $em->persist($product_iamge);
                            $em->flush();
                        }
                        echo "</td>";
                    }
                }
                echo '</tr>';
            }
        }


        // UOMO- BORSE PREZZO 0-99
        for($page=1; $page<=3; $page++){
            $html = file_get_contents("https://www.fratinardi.it/it/uomo/accessori.html?p=$page&price=-100&utm_content=category_uomo&utm_medium=category_uomo&utm_redirect=category_uomo&utm_term=category_uomo_accessori");
            $html = str_replace(chr(10), '',  str_replace(chr(13), '', $html));

            $patern1 = '/(?<=li class="tqty)(.*)(?=li>)/sU';
            preg_match_all($patern1, $html, $product);
            //print_r($product);

            //$product_itam = $product[0][0];
            foreach($product[0] as $product_itam){
                echo '<tr>';
                $patern2 = '/(?<=€)(.*)(?=<\/span>)/U';
                preg_match_all($patern2, $product_itam, $cost);

                $cost = $cost[0][1] == '' ? $cost[0][0] : $cost[0][1];

                if($cost <= 999){
                    //print_r($cost);
                    echo "<td>".$cost."</td>";

                    $patern3 = '/(?<=title=")(.*)(?=")/U';
                    preg_match_all($patern3, $product_itam, $name);

                    //print_r($name);
                    echo "<td>".$name[0][0]."</td>";

                    /*$patern4 = '/(?<=product-category">)(.*)(?=<\/div)/U';
                    preg_match_all($patern4, $product_itam, $category);

                    //print_r($category);
                    echo "<td>".$category[0][0]."</td>";*/

                    $patern5 = '/(?<=product-name"><a href=")(.*)(?=" title)/U';
                    preg_match_all($patern5, $product_itam, $url);

                    //print_r($url);
                    echo "<td>".$url[0][0]."</td>";

                    $product = $em->getRepository('Main\Entity\Product')->findOneBy(array('url' => $url[0][0]));
                    if(empty($product))
                    {
                        $product = new Product();

                        $product->setName($name[0][0]);
                        preg_match('/[0-9]+,[0-9]+/', $cost, $cost2);
                        $product->setPrice(str_replace(',', '.', $cost2[0]));
                        $product->setUrl($url[0][0]);
                        $product->setDate(time());

                        $em->persist($product);
                        $em->flush();

                        $html = file_get_contents($url[0][0]);
                        $html = str_replace(chr(10), '',  str_replace(chr(13), '', $html));

                        ////print_r($html);

                        $patern5 = '/(?<=std">)(.*)(?=<\/div>)/U';
                        preg_match_all($patern5, $html, $desc);

                        //print_r($desc);
                        echo "<td>".$desc[0][1]."</td>";

                        // языки
                        $lang_mas = array('en', 'it', 'ru');
                        foreach($lang_mas as $item){
                            $product_lang = new ProductLang();

                            $product_lang->setLang($item);
                            $product_lang->setName($name[0][0]);
                            $product_lang->setProduct($product);
                            $product_lang->setTegTitle($name[0][0]);
                            $product_lang->setDescription($desc[0][1]);
                            //$product_lang->setTegKeywords($_POST["meta_keywords_$item"]);
                            //$product_lang->setTegDescription($_POST["meta_description_$item"]);

                            $em->persist($product_lang);
                            $em->flush();
                        }

                        // all photo
                        $patern6 = '/(?<=ul-moreviews)(.*)(?=ul>)/U';
                        preg_match_all($patern6, $html, $img);

                        //print_r($img);

                        $patern7 = '/(?<=href=")(.*)(?=")/U';
                        preg_match_all($patern7, $img[0][0], $img);

                        //print_r($img);
                        echo "<td>";
                        $uploaddir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/product/';
                        if(isset($img[0][0])){
                            // сохраняем фаил
                            $url = $img[0][0];
                            $photo_name = $product->getId()."_".time().".jpg";
                            file_put_contents($uploaddir . $photo_name, file_get_contents($url));

                            $this->resize($uploaddir . $photo_name);
                            $this->cropImage($uploaddir . $photo_name, $uploaddir . $photo_name, 458, 458);

                            $product->setPhoto($photo_name);

                            $em->persist($product);
                            $em->flush();
                        }

                        for($i=1; $i<count($img[0]); $i++)
                        {
                            // сохраняем фаил
                            $url = $img[0][$i];
                            $photo_name = $product->getId()."_".time().".jpg";
                            file_put_contents($uploaddir . $photo_name, file_get_contents($url));

                            $this->resize($uploaddir . $photo_name);
                            $this->cropImage($uploaddir . $photo_name, $uploaddir . $photo_name, 458, 458);

                            $product_iamge = new ProductImage();

                            $product_iamge->setProduct($product);
                            $product_iamge->setUrl($photo_name);

                            $em->persist($product_iamge);
                            $em->flush();
                        }
                        echo "</td>";
                    }
                }
                echo '</tr>';
            }
        }


        // UOMO -PORTAFOGLI - DESIGNER- ARMANI JEANS
        for($page=1; $page<=1; $page++){
            $html = file_get_contents("https://www.fratinardi.it/it/uomo/accessori/portafogli.html?designer=664");
            $html = str_replace(chr(10), '',  str_replace(chr(13), '', $html));

            $patern1 = '/(?<=li class="tqty)(.*)(?=li>)/sU';
            preg_match_all($patern1, $html, $product);
            //print_r($product);

            //$product_itam = $product[0][0];
            foreach($product[0] as $product_itam){
                echo '<tr>';
                $patern2 = '/(?<=€)(.*)(?=<\/span>)/U';
                preg_match_all($patern2, $product_itam, $cost);

                $cost = $cost[0][1] == '' ? $cost[0][0] : $cost[0][1];

                if($cost <= 999){
                    //print_r($cost);
                    echo "<td>".$cost."</td>";

                    $patern3 = '/(?<=title=")(.*)(?=")/U';
                    preg_match_all($patern3, $product_itam, $name);

                    //print_r($name);
                    echo "<td>".$name[0][0]."</td>";

                    /*$patern4 = '/(?<=product-category">)(.*)(?=<\/div)/U';
                    preg_match_all($patern4, $product_itam, $category);

                    //print_r($category);
                    echo "<td>".$category[0][0]."</td>";*/

                    $patern5 = '/(?<=product-name"><a href=")(.*)(?=" title)/U';
                    preg_match_all($patern5, $product_itam, $url);

                    //print_r($url);
                    echo "<td>".$url[0][0]."</td>";

                    $product = $em->getRepository('Main\Entity\Product')->findOneBy(array('url' => $url[0][0]));
                    if(empty($product))
                    {
                        $product = new Product();

                        $product->setName($name[0][0]);
                        preg_match('/[0-9]+,[0-9]+/', $cost, $cost2);
                        $product->setPrice(str_replace(',', '.', $cost2[0]));
                        $product->setUrl($url[0][0]);
                        $product->setDate(time());

                        $em->persist($product);
                        $em->flush();

                        $html = file_get_contents($url[0][0]);
                        $html = str_replace(chr(10), '',  str_replace(chr(13), '', $html));

                        ////print_r($html);

                        $patern5 = '/(?<=std">)(.*)(?=<\/div>)/U';
                        preg_match_all($patern5, $html, $desc);

                        //print_r($desc);
                        echo "<td>".$desc[0][1]."</td>";

                        // языки
                        $lang_mas = array('en', 'it', 'ru');
                        foreach($lang_mas as $item){
                            $product_lang = new ProductLang();

                            $product_lang->setLang($item);
                            $product_lang->setName($name[0][0]);
                            $product_lang->setProduct($product);
                            $product_lang->setTegTitle($name[0][0]);
                            $product_lang->setDescription($desc[0][1]);
                            //$product_lang->setTegKeywords($_POST["meta_keywords_$item"]);
                            //$product_lang->setTegDescription($_POST["meta_description_$item"]);

                            $em->persist($product_lang);
                            $em->flush();
                        }

                        // all photo
                        $patern6 = '/(?<=ul-moreviews)(.*)(?=ul>)/U';
                        preg_match_all($patern6, $html, $img);

                        //print_r($img);

                        $patern7 = '/(?<=href=")(.*)(?=")/U';
                        preg_match_all($patern7, $img[0][0], $img);

                        //print_r($img);
                        echo "<td>";
                        $uploaddir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/product/';
                        if(isset($img[0][0])){
                            // сохраняем фаил
                            $url = $img[0][0];
                            $photo_name = $product->getId()."_".time().".jpg";
                            file_put_contents($uploaddir . $photo_name, file_get_contents($url));

                            $this->resize($uploaddir . $photo_name);
                            $this->cropImage($uploaddir . $photo_name, $uploaddir . $photo_name, 458, 458);

                            $product->setPhoto($photo_name);

                            $em->persist($product);
                            $em->flush();
                        }

                        for($i=1; $i<count($img[0]); $i++)
                        {
                            // сохраняем фаил
                            $url = $img[0][$i];
                            $photo_name = $product->getId()."_".time().".jpg";
                            file_put_contents($uploaddir . $photo_name, file_get_contents($url));

                            $this->resize($uploaddir . $photo_name);
                            $this->cropImage($uploaddir . $photo_name, $uploaddir . $photo_name, 458, 458);

                            $product_iamge = new ProductImage();

                            $product_iamge->setProduct($product);
                            $product_iamge->setUrl($photo_name);

                            $em->persist($product_iamge);
                            $em->flush();
                        }
                        echo "</td>";
                    }
                }
                echo '</tr>';
            }
        }


        // UOMO- CINTURE
        for($page=1; $page<=1; $page++){
            $html = file_get_contents("https://www.fratinardi.it/it/uomo/accessori/cinture.html");
            $html = str_replace(chr(10), '',  str_replace(chr(13), '', $html));

            $patern1 = '/(?<=li class="tqty)(.*)(?=li>)/sU';
            preg_match_all($patern1, $html, $product);
            //print_r($product);

            //$product_itam = $product[0][0];
            foreach($product[0] as $product_itam){
                echo '<tr>';
                $patern2 = '/(?<=€)(.*)(?=<\/span>)/U';
                preg_match_all($patern2, $product_itam, $cost);

                $cost = $cost[0][1] == '' ? $cost[0][0] : $cost[0][1];

                if($cost <= 999){
                    //print_r($cost);
                    echo "<td>".$cost."</td>";

                    $patern3 = '/(?<=title=")(.*)(?=")/U';
                    preg_match_all($patern3, $product_itam, $name);

                    //print_r($name);
                    echo "<td>".$name[0][0]."</td>";

                    /*$patern4 = '/(?<=product-category">)(.*)(?=<\/div)/U';
                    preg_match_all($patern4, $product_itam, $category);

                    //print_r($category);
                    echo "<td>".$category[0][0]."</td>";*/

                    $patern5 = '/(?<=product-name"><a href=")(.*)(?=" title)/U';
                    preg_match_all($patern5, $product_itam, $url);

                    //print_r($url);
                    echo "<td>".$url[0][0]."</td>";

                    $product = $em->getRepository('Main\Entity\Product')->findOneBy(array('url' => $url[0][0]));
                    if(empty($product))
                    {
                        $product = new Product();

                        $product->setName($name[0][0]);
                        preg_match('/[0-9]+,[0-9]+/', $cost, $cost2);
                        $product->setPrice(str_replace(',', '.', $cost2[0]));
                        $product->setUrl($url[0][0]);
                        $product->setDate(time());

                        $em->persist($product);
                        $em->flush();

                        $html = file_get_contents($url[0][0]);
                        $html = str_replace(chr(10), '',  str_replace(chr(13), '', $html));

                        ////print_r($html);

                        $patern5 = '/(?<=std">)(.*)(?=<\/div>)/U';
                        preg_match_all($patern5, $html, $desc);

                        //print_r($desc);
                        echo "<td>".$desc[0][1]."</td>";

                        // языки
                        $lang_mas = array('en', 'it', 'ru');
                        foreach($lang_mas as $item){
                            $product_lang = new ProductLang();

                            $product_lang->setLang($item);
                            $product_lang->setName($name[0][0]);
                            $product_lang->setProduct($product);
                            $product_lang->setTegTitle($name[0][0]);
                            $product_lang->setDescription($desc[0][1]);
                            //$product_lang->setTegKeywords($_POST["meta_keywords_$item"]);
                            //$product_lang->setTegDescription($_POST["meta_description_$item"]);

                            $em->persist($product_lang);
                            $em->flush();
                        }

                        // all photo
                        $patern6 = '/(?<=ul-moreviews)(.*)(?=ul>)/U';
                        preg_match_all($patern6, $html, $img);

                        //print_r($img);

                        $patern7 = '/(?<=href=")(.*)(?=")/U';
                        preg_match_all($patern7, $img[0][0], $img);

                        //print_r($img);
                        echo "<td>";
                        $uploaddir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/product/';
                        if(isset($img[0][0])){
                            // сохраняем фаил
                            $url = $img[0][0];
                            $photo_name = $product->getId()."_".time().".jpg";
                            file_put_contents($uploaddir . $photo_name, file_get_contents($url));

                            $this->resize($uploaddir . $photo_name);
                            $this->cropImage($uploaddir . $photo_name, $uploaddir . $photo_name, 458, 458);

                            $product->setPhoto($photo_name);

                            $em->persist($product);
                            $em->flush();
                        }

                        for($i=1; $i<count($img[0]); $i++)
                        {
                            // сохраняем фаил
                            $url = $img[0][$i];
                            $photo_name = $product->getId()."_".time().".jpg";
                            file_put_contents($uploaddir . $photo_name, file_get_contents($url));

                            $this->resize($uploaddir . $photo_name);
                            $this->cropImage($uploaddir . $photo_name, $uploaddir . $photo_name, 458, 458);

                            $product_iamge = new ProductImage();

                            $product_iamge->setProduct($product);
                            $product_iamge->setUrl($photo_name);

                            $em->persist($product_iamge);
                            $em->flush();
                        }
                        echo "</td>";
                    }
                }
                echo '</tr>';
            }
        }

        echo ' </tbody>
        </table>';
    }
}


