<?php
// прочитать гугл пикчерз хайджек
// разобраться, может быть имеет смысл публиковать не после каждого ответа а только после вопроса картинки
// проверить наличие и создать альбом 

// скрипт выкладывает информацию в blogger через API

// определеяем переменные
// имя блога - имя локального подкаталога с контентом, например toyotajdm.blogspot.com
// $blogName = "hondaownersclub"; // honda
// $blogName = "mazdafaq"; // mazda
// $blogName = "nissanjdm"; // nissan 
$blogName = "mitsubishifaq"; // mitsubishi
// $blogName = "subarufaq"; // subaru
// $blogName = "suzukifaq"; // subaru
// $blogName = "toyotajdm"; // toyota

// запускаем таймер выполнения скрипта
set_time_limit(0);
ini_set('memory_limit', '512M');
$mtime = microtime(true);
echo "<pre>";

// вероятность создания нового файла 25%
$newFileVeroyatnost = 25;
// счетчик файлов и картинок
$fileCounter = 50000001;
$picCounter0 = 50000001;
// атрибут первого файла, чтобы не читать пустой
$firstFile = 1;
// счетчики публикаций
$publishedPostsNumber = 0;
$uploadedFlickrNumber = 0;

// подключаем библиотеки
require_once 'Zend/Loader.php';
Zend_Loader::loadClass('Zend_Gdata');
Zend_Loader::loadClass('Zend_Gdata_Query');
Zend_Loader::loadClass('Zend_Gdata_ClientLogin');
Zend_Loader::loadClass('Zend_Gdata_Photos');
Zend_Loader::loadClass('Zend_Gdata_AuthSub');

// аутентификация блоггер
$user = 'user@gmail.com';
$pass = 'pass';
$service = 'blogger';

// аутентификация Flickr
$serviceNameFlickr = Zend_Gdata_Photos::AUTH_SERVICE_NAME;
$clientFlickr = Zend_Gdata_ClientLogin::getHttpClient($user, $pass, $serviceNameFlickr);
$gpFlickr = new Zend_Gdata_Photos($clientFlickr, "Google-DevelopersGuide-1.0");

// задаем путь к csv
$path = getcwd()."/$blogName";
echo "<h3>Path: $path</h3>";
// запускаем итератор
if ($handle = opendir("$path")) {
    while (false !== ($file = readdir($handle))) { 
        if ($file != "." && $file != "..") { 
            // echo "<br />$file"; 
			// очистим массивы
			$tempOutput = "";
			$tempOutput2 = "";
			// получим атрибуты текущего объекта - файл и расширение
			// $file = $object->getFilename();
			$ext = strtolower(array_pop(explode(".", $file)));
			// если это csv-файл, парсим его
			if ($ext == "csv") {
				$filename = basename($file, ".csv");
				// $content = file_get_contents("$file");
				$tag = explode(" ", $filename);
				$tag = array_slice($tag, 1);
				$tag = implode(" ", $tag);
				echo "<h3>" . $filename . " = " . $tag . "</h3>";
				
				// если альбом не существует, создадим его
				$picCounter = $picCounter0;
				$albumId = $filename;
				$albumId = str_ireplace(" ", "", $albumId);
				$albumId = str_ireplace("-", "", $albumId);
				$show = showAlbumsFlickr($gpFlickr);
				if (in_array("$albumId", $show)) {
					echo "<br />Уже создан: " . $albumId;
					}
				else {
					echo "<br />Создадим: " . $albumId;
					$create = createAlbumFlickr($albumId, $gpFlickr);
					}
				
				// прочитаем файл
				$fp = fopen("$path/$filename.csv", 'r');
				while (($data = fgetcsv($fp, 0, "\t")) !== FALSE) {
					$tempOutput[] = $data;
					}
				// уберем первые два значения - ид вопроса и кол-во ответов
				foreach ($tempOutput as $item) {
					$tempOutput2[] = array_slice($item, 2); 
					}
				fclose($fp);
				
				// запишем контент в файл (массив многомерен!)
				echo "<br />$filename--$fileCounter";
				foreach ($tempOutput2 as $item) {
					// определим вероятность создания нового файла, если он создан, прошлый закачиваем в блоггер
					$veroyatnost = rand(1,100);
					// echo "<br />вероятность: $veroyatnost, fileCounter: $fileCounter";
					// если создаем новый файл, предварительно загружаем старый в блоггер
					if ($veroyatnost<$newFileVeroyatnost) {
						$publishedPostsNumber ++;
						$fileCounter++;
						echo "<br />$filename--$fileCounter";
						}
					$filenameFlickr = $path . "/$filename/$filename$picCounter.jpg";
					// если файл существует, загрузим фотку на фликр и получим урл к ней
					if (file_exists($filenameFlickr)) {
						$mtimeNow = microtime(true);
						$photoName = "$filename$picCounter";
						$photoCaption = "$filename";
						$flickrURL = uploadFlickr($gpFlickr, $filenameFlickr, $photoName, $photoCaption, $tag, $albumId);
						$uploadedFlickrNumber ++;
						echo "<br />uploading $filenameFlickr... " . round((microtime(true) - $mtimeNow) * 1, 4);
						// задержка
						$pause = (rand (1,3));
						sleep($pause);
						$sumpause += $pause;
						echo " + " . $pause;
						ob_flush(); flush();
						$picCounter++;
						$item[1] = $item[1] . "\n\r<br />" . '<img title="Picture: ' . $item[0] . '" alt="Image: ' . $item[0] . '" src="' . $flickrURL . '" /> <!--more-->';
						} 
					else {
						echo "<br />отсутствует " . $filenameFlickr;
						$item[1] = $item[1] . "\n\r<br /><!--more-->";
						$picCounter++;
						}
					
					// добавим картинку после первого ответа и сделаем вопрос подзаголовком h2
					$item[0] = "<h2>" . $item[0] . "</h2>";
					foreach ($item as $quiz) {
						// если элемент (вопрос или ответ) не пуст - запишем его в файл
						if ($quiz != null) {
							// запишем контент и урл фотки предварительно загруженной
							file_put_contents ("$path/_ready2publish/$tag--$fileCounter.html",'<p>' . $quiz . '</p>',FILE_APPEND);
							$publishedElementsNumber ++;
							}
						// если элемент пустой - пропустим его
						else {
							// echo "<br />пустой элемент!!!";
							}
						}
					}
				}
			} 
		}
	closedir($handle); 
	}

// ******************** добавить фотку ************************
function uploadFlickr($gpFlickr, $filenameFlickr, $photoName, $photoCaption, $photoTags, $albumId = "default")
{
// $filenameFlickr = "toyotajdm/Toyota Allion/Toyota Allion50000001.jpg";
// $photoName = "My Test Photo";
// $photoCaption = "The first photo I uploaded to Picasa Web Albums via PHP.";
// $photoTags = "honda, civic";

// We use the albumId of 'default' to indicate that we'd like to upload
// this photo into the 'drop box'.  This drop box album is automatically 
// created if it does not already exist.
// $albumId = "default";
// на самом деле под переменной albumID скрывается albumName (имя альбома БЕЗПРОБЕЛОВ)
// $albumId = "ToyotaJapanDomesticMarket";

$fd = $gpFlickr->newMediaFileSource($filenameFlickr);
$fd->setContentType("image/jpeg");

// Create a PhotoEntry
$photoEntry = $gpFlickr->newPhotoEntry();
$photoEntry->setMediaSource($fd);
$photoEntry->setTitle($gpFlickr->newTitle($photoName));
$photoEntry->setSummary($gpFlickr->newSummary($photoCaption));

// add some tags
$keywords = new Zend_Gdata_Media_Extension_MediaKeywords();
$keywords->setText($photoTags);
$photoEntry->mediaGroup = new Zend_Gdata_Media_Extension_MediaGroup();
$photoEntry->mediaGroup->keywords = $keywords;

// We use the AlbumQuery class to generate the URL for the album
$albumQuery = $gpFlickr->newAlbumQuery();
$albumQuery->setUser($username);
// так можно было указывать ИД альбома, но я пользуюсь именем
// $albumQuery->setAlbumId($albumId);
$albumQuery->setAlbumName($albumId);

// We insert the photo, and the server returns the entry representing
// that photo after it is uploaded
$insertedEntry = $gpFlickr->insertPhotoEntry($photoEntry, $albumQuery->getQueryUrl()); 
// print_r( $insertedEntry->getLink());
$mediaGroup = $insertedEntry->getMediaGroup();
$content = $mediaGroup->getContent();
$content = $content[0];
$url = $content->getUrl();
// print_r($url);
// echo '<img src="' . $url . '" />';
// echo '<br />' . $url;
return $url;
}


// ************** вывод альбомов ****************
function showAlbumsFlickr($gpFlickr)
{
try {
    $userFeed = $gpFlickr->getUserFeed("default");
    foreach ($userFeed as $userEntry) {
        // echo "<br />" . $userEntry->title->text;
		$arrayAlbums[] = $userEntry->title->text;
    }
} catch (Zend_Gdata_App_HttpException $e) {
    echo "Error: " . $e->getMessage() . "<br />\n";
    if ($e->getResponse() != null) {
        echo "Body: <br />\n" . $e->getResponse()->getBody() . 
             "<br />\n"; 
    }
} catch (Zend_Gdata_App_Exception $e) {
    echo "Error: " . $e->getMessage() . "<br />\n"; 
}
return $arrayAlbums;
}

// ************** создание альбома ****************
function createAlbumFlickr($albumName, $gpFlickr)
{
// ***************************** добавить альбом ***********************
$entry = new Zend_Gdata_Photos_AlbumEntry();
$entry->setTitle($gpFlickr->newTitle("$albumName"));
$entry->setSummary($gpFlickr->newSummary("$albumName"));
$createdEntry = $gpFlickr->insertAlbumEntry($entry);
}

// подсчитываем время работы
$timer = round((microtime(true) - $mtime) * 1, 2);
echo "<br /><br />Сформировано постов: $publishedPostsNumber";
echo "<br />Сформировано элементов: $publishedElementsNumber";
echo "<br />Загружено картинок: $uploadedFlickrNumber";
echo "<br />Время работы скрипта: " . $timer . " с.";
echo "<br />Задержки: " . ($sumpause) . " с.";
echo "</pre>";
?>