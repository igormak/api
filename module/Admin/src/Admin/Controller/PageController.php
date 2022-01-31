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

use Admin\Form\PageEditForm;
use Admin\Form\PageEditCategoryForm;

use Main\Entity\Pages;



class PageController extends BaseController
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

    function resize($file)
    {
        // Ограничение по ширине в пикселях
        $max_size = 620;

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
       return new ViewModel();
    }

    public function editAction()
    {
        $form = new PageEditForm;
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

        $form->bind($page);
        $request = $this->getRequest();

        if($request->isPost())
        {
            $date = $request->getPost();
            $form->setData($date);
            if($form->isValid())
            {

                $lang_mas = array(/*'en', 'it',*/ 'ru');
                foreach($lang_mas as $item){
                    $page_lang = $em->getRepository('Main\Entity\PageLang')->findOneBy(array('pageId' => $id, 'lang' => $item));

                    $page_lang->setTegTitle($_POST["meta_title_$item"]);
                    $page_lang->setDescription($_POST["description_$item"]);
                    $page_lang->setTegKeywords($_POST["meta_keywords_$item"]);
                    $page_lang->setTegDescription($_POST["meta_description_$item"]);

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
                foreach($form->getInputFilter()->getInvalidInput() as $errors){
                    foreach($errors->getMessages() as $error){
                        $message .= ' '.$error;
                    }
                }
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

    public function editCategoryAction()
    {
        $form = new PageEditCategoryForm;
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

        $form->bind($page);
        $request = $this->getRequest();

        if($request->isPost())
        {
            $date = $request->getPost();
            $form->setData($date);
            if($form->isValid())
            {
                $photo_name = '';
                $uploaddir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/img/';
                if(!empty($_FILES['photo']['name'])){
                    $uploadfile = $uploaddir.basename($_FILES['photo']['name']);

                    // Копируем файл из каталога для временного хранения файлов:
                    copy($_FILES['photo']['tmp_name'], $uploadfile);

                    $photo_name = $page->getId()."_".$this->transliterate($_FILES['photo']['name']);
                    rename($uploaddir . $_FILES['photo']['name'], $uploaddir . $photo_name);
                    $this->resize($uploaddir . $photo_name);
                    //$this->cropImage($uploaddir . $photo_name, $uploaddir . $photo_name, 780, 470);
                    //$this->cropImage($uploaddir . $photo_name, $uploaddir .'min_'. $photo_name, 490, 470);

                    //$product->setPhoto($photo_name);
                }

                $lang_mas = array('en', 'it', 'ru');
                foreach($lang_mas as $item){
                    $page_lang = $em->getRepository('Main\Entity\PageLang')->findOneBy(array('pageId' => $id, 'lang' => $item));

                    $photo_name = $photo_name == '' ? str_replace('{', '', explode("};", $page_lang->getDescription())[1]) : $photo_name;

                    $page_lang->setTegTitle($_POST["meta_title_$item"]);
                    $page_lang->setDescription("{".$_POST["description_$item"]."};{".$photo_name."};");
                    $page_lang->setTegKeywords($_POST["meta_keywords_$item"]);
                    $page_lang->setTegDescription($_POST["meta_description_$item"]);

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
                foreach($form->getInputFilter()->getInvalidInput() as $errors){
                    foreach($errors->getMessages() as $error){
                        $message .= ' '.$error;
                    }
                }
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
}


