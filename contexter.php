<?php header('Content-Type: text/html; charset=utf-8'); 
//shows the context articles linked to one given article mention it by printing one sentence where it is used
include("shared_inc/wiki_functions.inc.php");

$article = $_REQUEST['article'];
$articleenc = name_in_url($article);
$lang = "de";
$project = "wikipedia";

$needle = $_REQUEST['needle'];
$listonly = $_REQUEST['listonly'];
$allNamespaces = $_REQUEST['all'];

if($needle=="")
{
	$needle=str_replace('_', ' ', $article);
}

$server = "$lang.$project.org";

$limit=$_REQUEST['limit'];
if($limit=="")
{
	$limit = 50;
}

echo "<h1>Contexts for <a href=\"https://$server/wiki/$article\">$article</a></h1>";
echo "[<a href=\"contexter.php?article=$article&language=$lang&listonly=true&all=$allNamespaces\">list these as javascript array</a>]<br />";
$pages = get_linked_pages($articleenc);


if($listonly!="true")
{
	foreach ($pages AS $linking_article)
	{
		// echo "<hr>$linking_artile<hr>";
		$ex_sentences = extract_sentences(name_in_url($linking_article));
		
		$sentences_cont = find_sentence_with($ex_sentences, $needle);
		
		if(count($sentences_cont)>0)
		{
			echo "<h2><a href=\"http://$server/wiki/$linking_article\">$linking_article</a></h2>";
			echo "<small><a href=\"http://$server/w/index.php?title=$linking_article&action=edit&summary=Link%20auf%20BKL%20%5B%5B".$needle."%5D%5D%20pr%C3%A4zisiert\" target=\"_blank\">[edit]</a></small>\n";
			//echo "<small><a href=\"javascript:window.open('http://$server/w/index.php?title=$linking_article&action=edit').find()\">[edit-JS]</a></small>\n";
			
			foreach($sentences_cont as $s)
			{
				echo "$s.<br><br>";
			}
		}
	}
}
else
{
	echo "<textarea cols=\"150\" rows=\"80\">";
	foreach ($pages AS $linking_article)
	{
		echo "\"".trim($linking_article)."\", ";;
	}
	echo "</textarea>";
}


function get_linked_pages($articleenc)
{
	//echo "entering get_linked_pages";
	global $server, $limit, $allNamespaces;
	//$page = "http://".$server."/wiki/Spezial:Verweisliste/".;
	
	$page = "https://de.wikipedia.org/w/index.php?title=Spezial:Linkliste/$articleenc&limit=$limit&from=0&hideredirs=1";
	if($allNamespaces=="")
	{
		$page.="&namespace=0";
	}
	echo $page;
	$linked_list = file_get_contents ($page);
	
	// echo "<hr>$linked_list <hr>";
	
	$list_begins = strpos($linked_list, '<li>');
	
	$linked_list = substr($linked_list, $list_begins);
	
	$list_ends = strpos($linked_list, '</ul>');
	$list_len = strlen($linked_list);
	
	
	$linked_list = substr($linked_list, 0, $list_ends);
	$linked_list = strip_tags($linked_list);
	$linked_list = str_replace("(← Links)", '', $linked_list); //Problemzeichen
	return explode("\n", str_replace("  ‎", "", trim($linked_list)));
}

function extract_sentences ($article)
{
	global $server;
	$page = "https://".$server."/w/index.php?action=raw&title=".$article;
	
	$art_text = file_get_contents($page);

	// echo "<h1>art_text</h1> $art_text <hr>";
	// $plain_text = removeheaders($art_text);
	$plain_text = $art_text;
	// echo "<h1>plaintext</h1> $plain_text <hr>";

	$paragraphs = explode("\n", $plain_text);
	$sentences;
	
	foreach($paragraphs as $p)
	{
		$sentences_p = explode('.', $p);
		foreach($sentences_p as $sp)
		{
			$sentences[]=$sp;
		}
	}
	return $sentences;
}

function find_sentence_with($sentences, $needle)
{
	$hits;
	$i;
	foreach($sentences as $sen)
	{
		//echo "<br>Satz $i: $sen - Suche nach $needle";
		if(stristr($sen, $needle))
		{
			//echo "<br>Treffer bei $i";
			$hits[]=str_replace($needle, "<b>$needle</b>", $sen);
		}
		$i++;
		
	}
	//echo "$i Stze analysiert und dabei ".count($hits)."Fundestellen <br><br>";
	return $hits;
}
?>
<hr>
<a href="Benutzer:Flominator/Contexter">Anleitung</a> - <a href="https://github.com/FlominatorTM/wikipedia_contexter/issues">Anregungen und Probleme</a> - by <a href="https://de.wikipedia.org/wiki/Benutzer:Flominator">Flominator</a>