<?php
// скрипт выкладывает информацию в blogger через API
// прочитать гугл пикчерз хайджек

// определеяем переменные

// имя блога - имя локального подкаталога с контентом, например toyotajdm.blogspot.com
// ИД блога на блогспот
// $blogName = "hondaownersclub"; // honda
// $blogID= urlencode("712930048376301389"); // honda
// $blogName = "mazdafaq"; // mazda
// $blogID= urlencode("9189307661471216962"); // mazda
// $blogName = "mitsubishifaq"; // mitsubishi
// $blogID= urlencode("3762223375292324282"); //mitsubishi
// $blogName = "nissanjdm"; //nissan
// $blogID= urlencode("3229063885907718780"); //nissan
// $blogName = "subarufaq"; // subaru
// $blogID= urlencode("5005679780722497941"); //subaru
// $blogName = "suzukifaq"; // subaru
// $blogID= urlencode("3346348191515775176"); //suzuki
// $blogName = "toyotajdm"; //toyota
// $blogID= urlencode("5337366677889130345"); //toyota





// ИД блога на блогспот

// запускаем таймер выполнения скрипта
set_time_limit(0);
ini_set('memory_limit', '512M');
$mtime = microtime(true);
echo "<pre>";

// счетчики публикаций
$publishedPostsNumber = 0;
$uploadedFlickrNumber = 0;

// подключаем библиотеки
require_once 'Zend/Loader.php';
Zend_Loader::loadClass('Zend_Gdata');
Zend_Loader::loadClass('Zend_Gdata_Query');
Zend_Loader::loadClass('Zend_Gdata_ClientLogin');

// аутентификация блоггер
$user = 'user@gmail.com';
$pass = 'pass';
$service = 'blogger';
$clientBlogger = Zend_Gdata_ClientLogin::getHttpClient($user, $pass, $service, null,
		Zend_Gdata_ClientLogin::DEFAULT_SOURCE, null, null, 
		Zend_Gdata_ClientLogin::CLIENTLOGIN_URI, 'GOOGLE');
$gdClientBlogger = new Zend_Gdata($clientBlogger); 

// задаем путь к csv
$path = getcwd()."/$blogName";
echo "<h3>Path: $path</h3>";

// запускаем итератор
if ($handle = opendir("$path/2publish")) {
    while (false !== ($file = readdir($handle))) { 
        if ($file != "." && $file != "..") { 
			echo "<br />$file";
            $filename = basename($file, ".html");
			$tag = explode("--", $filename);
			$tag = $tag[0];
			// echo "<h3>" . $filename . " = " . $tag . "</h3>";
			// echo "<br />читаем файл $path/2publish/$file";
			$contentHTML = file_get_contents("$path/2publish/$file");
			// echo "<br />$contentHTML";
			// получим тайтл
			preg_match("'<h2>(.*?)</h2>'",$contentHTML, $titleHTML);
			// print_r($titleHTML);
			$titleHTML = $titleHTML[1];
			echo "<br />заголовок: $titleHTML\t";
			// обращаемся к функции публикации
			$publishPost = createPublishedPost($titleHTML, $contentHTML, $gdClientBlogger, $blogID, $tag);
			$publishedPostsNumber ++;
			// echo "<br /><strong>$publishPost</strong>";
			ob_flush(); flush();
			$pause = (rand (1,5));
			sleep($pause);
			$sumpause += $pause;
			echo "<br />файл: $file\tфайлнейм: $filename\t $pause";
			}
		}
	closedir($handle); 
	}

function createPublishedPost($title='', $content='', $gdClientBlogger, $blogID, $tags)
{
$uri = 'http://www.blogger.com/feeds/' . $blogID . '/posts/default';
$entry = $gdClientBlogger->newEntry();
$entry->title = $gdClientBlogger->newTitle($title);
$entry->content = $gdClientBlogger->newContent($content);
$entry->content->setType('text');

// создаем метки/ярлыки/лейблы
$labels = $entry->getCategory(); 
$newLabel = $gdClientBlogger->newCategory($tags, 'http://www.blogger.com/atom/ns#'); 
$labels[] = $newLabel; // Append the new label to the list of labels. 
$entry->setCategory($labels); 

$createdPost = $gdClientBlogger->insertEntry($entry, $uri);
$idText = explode('-', $createdPost->id->text);
$newPostID = $idText[2]; 

return $newPostID; 
echo "<br />$newPostID";
}


// переименуем каталог
rename("$path/2publish", "$path/published-" . date("Ymd-Hi"));

// создадим пустой 2publish
mkdir("$path/2publish");

// подсчитываем время работы
$timer = round((microtime(true) - $mtime) * 1, 2);
echo "<br /><br />Опубликовано постов: $publishedPostsNumber";
echo "<br />Время работы скрипта: " . $timer . " с.";
echo "<br />Задержки: " . ($sumpause) . " с.";
echo "</pre>";
?>