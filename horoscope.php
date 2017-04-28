<?php

define("FILE_CACHE", "./.cached.json");

function getSignFromMmdd($mmdd) {
	if ($mmdd < 120)
		return "capricorn";
	if ($mmdd < 219)
		return "aquarius";
	if ($mmdd < 321)
		return "pisces";
	if ($mmdd < 420)
		return "aries";
	if ($mmdd < 521)
		return "taurus";
	if ($mmdd < 621)
		return "gemini";
	if ($mmdd < 723)
		return "cancer";
	if ($mmdd < 823)
		return "leo";
	if ($mmdd < 923)
		return "virgo";
	if ($mmdd < 1023)
		return "libra";
	if ($mmdd < 1122)
		return "scorpio";
	if ($mmdd < 1222)
		return "sagittarius";
	if ($mmdd <= 1231)
		return "capricorn";
	return null;
}

function dateToSign($date) {
    $day = substr($date, 0, 2);
    $month = substr($date, 2, 2);
    if (is_numeric($day) && is_numeric($month)) {
		$mmdd = $month .$day;
		$sign = getSignFromMmdd($mmdd);
		if ($sign)
			return $sign;
    }
    return $date;
}

function isValidSign($sign) {
	$signs = [ 
		"capricorn",
		"aquarius",
		"pisces",
		"aries",
		"taurus",
		"gemini",
		"cancer",
		"leo",
		"virgo",
		"libra",
		"scorpio",
		"sagittarius"
	];
	return (array_search($sign, $signs, true)) !== FALSE;
}

function getUrlForSign($sign) {
	return "http://www.ganeshaspeaks.com/{$sign}/{$sign}-daily-horoscope.action";
}

function queryHoroscope($sign) {
	$site = new DOMDocument();
	if (!@$site->loadHTMLFile(getUrlForSign($sign)))
		return FALSE;
	$entries = (new DOMXPath($site))->query('//*[@id="main-wrapper"]/div[4]/div/div[1]/section/div[2]/div[1]/div/div[1]/span');
	foreach ($entries as $entry)
		return $entry->textContent;
	return FALSE;
}

function doGetHoroscope($existing, $sign) {
	$text = queryHoroscope($sign);
	if ($text === FALSE)
		return FALSE;
	$existing->{$sign} = $text;
	file_put_contents(FILE_CACHE, json_encode($existing));
	return $text;
}

function lazyGetHoroscope($sign) {
	$cached = file_get_contents(FILE_CACHE);
	$todayymmdd = date("Ymd");
	if ($cached === FALSE) {
		$cachedContent = new StdClass();
		$cachedContent->{"yymmdd"} = $todayymmdd;
	} else {
		$cachedContent = json_decode($cached);
		if ($cachedContent->{"yymmdd"} !== $todayymmdd) {
			$cachedContent = new StdClass();
			$cachedContent->{"yymmdd"} = $todayymmdd;
		}
	}
	if (!isset($cachedContent->{$sign}))
		return doGetHoroscope($cachedContent, $sign);
	return $cachedContent->{$sign};
}

$sign = dateToSign(isset($_POST["text"]) ? $_POST["text"] : $_GET["text"]);
if (!isValidSign($sign)) {
	header("400 Bad Request");
	die("Unknown sign {$sign}");
}
$text = lazyGetHoroscope($sign);
if ($text === FALSE) {
	header("500 Internal server error");
	die();
}
header('Content-Type: application/json');
echo json_encode(array(
	"response_type" => "in_channel",
	"attachments" => array(array(
		"fallback" => $text,
		"footer" => "{$sign} - " .getUrlForSign($sign),
		"text" => $text
	))
));

?>
