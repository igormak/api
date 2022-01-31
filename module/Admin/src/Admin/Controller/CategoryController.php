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

use Main\Entity\Category;
use Main\Entity\Product;

use Admin\Form\CategoryEditForm;


class CategoryController extends BaseController
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
        $max_size = 80;

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
        //$em = $this->getEntityManager();
        //$category = $em->getRepository('Main\Entity\Category')->findAll();

        $category = $this->getEntityManager()->createQueryBuilder();
        $category
            ->select('a')
            ->from('Main\Entity\Category', 'a')
            //->where("a.id != '1'");
            //->orderBy('a.'.$sort, $order);
            ->orderBy('a.id', 'ASC');

        $adapter = new DoctrineAdapter(new ORMPaginator($category));

        $paginator = new Paginator($adapter);
        $paginator->setDefaultItemCountPerPage(!isset($_GET['limit']) ? 10 : $_GET['limit']);
        $paginator->setCurrentPageNumber((int) $this->params()->fromQuery('page', 1));

        return array('category' => $paginator);

        //return array('category' => $category);
    }

    public function addAction()
    {
        $form = new CategoryEditForm();
        $status = $message = '';
        $em = $this->getEntityManager();

        $value_opt = $form->get('idMain')->getValueOptions();

        $category_sql = $em->getRepository('Main\Entity\Category')->findBy(array('status' => 1));
        foreach($category_sql as $item){
            $value_opt[$item->getId()] = $item->getName();
        }

        $form->get('idMain')->setValueOptions($value_opt);
        //print_r($form->get('idMain')->getValueOptions());

        $request = $this->getRequest();
        if($request->isPost())
        {
            $form->setData($request->getPost());
            if($form->isValid())
            {
                $category = new Category();
                $category->exchangeArray($form->getData());

                $em->persist($category);
                $em->flush();

                $uploaddir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/category/';
                if(!empty($_FILES['photo']['name'])){
                    $uploadfile = $uploaddir.basename($_FILES['photo']['name']);

                    // Копируем файл из каталога для временного хранения файлов:
                    copy($_FILES['photo']['tmp_name'], $uploadfile);

                    $photo_name = $category->getId()."_".$this->transliterate($_FILES['photo']['name']);
                    rename($uploaddir . $_FILES['photo']['name'], $uploaddir . $photo_name);
                    $this->resize($uploaddir . $photo_name);
                    //$this->cropImage($uploaddir . $photo_name, $uploaddir . $photo_name, 780, 780);
                    //$this->cropImage($uploaddir . $photo_name, $uploaddir .'min_'. $photo_name, 490, 470);

                    $category->setPhoto($photo_name);
                }

                $em->persist($category);
                $em->flush();

                $status = 'success';
                $message = 'Запись добавлена';
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
        return $this->redirect()->toRoute('admin/default', array('controller' => 'category'));
    }

    public function editAction()
    {
        $form = new CategoryEditForm();
        $status = $message = '';
        $em = $this->getEntityManager();

        $id = (int) $this->params()->fromRoute('id', 0);

        $category = $em->find('Main\Entity\Category', $id);
        if(empty($category)){
            $status = 'error';
            $message = 'Запись не найдена';
            $this->flashMessenger()
                ->setNamespace($status)
                ->addMessage($message);
            return $this->redirect()->toRoute('admin/default', array('controller' => 'category'));
        }

        $value_opt = $form->get('idMain')->getValueOptions();

        $category_sql = $em->getRepository('Main\Entity\Category')->findBy(array('status' => 1));
        foreach($category_sql as $item){
            if($item->getId() != $id)
                $value_opt[$item->getId()] = $item->getName();
        }

        $form->get('idMain')->setValueOptions($value_opt);
        //print_r($form->get('idMain')->getValueOptions());

        $form->bind($category);

        $request = $this->getRequest();

        if($request->isPost())
        {
            $date = $request->getPost();
            $form->setData($date);
            if($form->isValid())
            {
                $uploaddir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/category/';
                if(!empty($_FILES['photo']['name'])){
                    $uploadfile = $uploaddir.basename($_FILES['photo']['name']);

                    // Копируем файл из каталога для временного хранения файлов:
                    copy($_FILES['photo']['tmp_name'], $uploadfile);

                    $photo_name = $category->getId()."_".$this->transliterate($_FILES['photo']['name']);
                    rename($uploaddir . $_FILES['photo']['name'], $uploaddir . $photo_name);
                    $this->resize($uploaddir . $photo_name);
                    //$this->cropImage($uploaddir . $photo_name, $uploaddir . $photo_name, 780, 780);
                    //$this->cropImage($uploaddir . $photo_name, $uploaddir .'min_'. $photo_name, 490, 470);

                    unlink($uploaddir.$category->getPhoto());
                    $category->setPhoto($photo_name);
                }

                $em->persist($category);
                $em->flush();

                $status = 'success';
                $message = 'Запись Обновлена';
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
            return array('form' => $form, 'id' => $id, 'category' => $category);
        }

        if($message){
            $this->flashMessenger()
                ->setNamespace($status)
                ->addMessage($message);
        }
        return $this->redirect()->toRoute('admin/default', array('controller' => 'category'));
    }

    public function removeAction()
    {
        $id = (int) $this->params()->fromRoute('id', 0);
        $em = $this->getEntityManager();
        $uploaddir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/product/';

        $status = 'success';
        $message = 'Запись удалена';

        try{
            $repository = $em->getRepository('Main\Entity\Category');
            $product = $repository->find($id);

            $em->remove($product);
            $em->flush();
        }
        catch(\Exception $ex){
            $status = 'error';
            $message = 'Ошибка удаление записи: ' . $ex->getMessage();
        }

        $this->flashMessenger()
            ->setNamespace($status)
            ->addMessage($message);

        return $this->redirect()->toRoute('admin/default', array('controller' => 'category'));
    }
}

