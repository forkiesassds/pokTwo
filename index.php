<?php
namespace pokTwo;
require('lib/common.php');

$twig = twigloader();

echo $twig->render('index.twig', [
	'videos' => Videos::getVideos("RAND()", 5),
	'tags' => Tags::getListOfTags("latestUse DESC", 50),
]);