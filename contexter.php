<?php header('Content-Type: text/html; charset=utf-8'); ?>
<script>
function checkBoxes(server, replace, replaceWith, user)
{
	allInputs = document.getElementsByTagName('input');
	for(var i =0; i<allInputs.length; i++)
	{
		if(allInputs[i].type=="checkbox" && allInputs[i].checked)
		{
			var lemma = "";
			try
			{
				lemma = allInputs[i].id;
				
				var url = 'https://'+server+'/w/index.php?title='+ lemma + '&action=edit&replace='+replace+'&replacewith='+ replaceWith+'&fixlinkstype=linklist&flauser='+user +'&flatime=' + generate_flatime_flo();
				window.open(url);
			}
			catch (e)
			{
				alert(lemma + e);
			}
		}
	}
}

/* copied from https://de.wikipedia.org/wiki/Benutzer:DerHexer/fixlinks.js */
function generate_flatime_flo () 
{
   var arbitrary_datestamp = Date.UTC(2008,10,1,0,0,0,0);
   var current_date = new Date();
   var current_timestamp = current_date.getTime();
 
   return ( Math.floor((current_timestamp - arbitrary_datestamp) / 1000) );
}

function unCheckAll()
{
   	allInputs = document.getElementsByTagName('input');
	for(var i =0; i<allInputs.length; i++)
	{
		if(allInputs[i].type=="checkbox")
		{
			allInputs[i].checked = false;
		}
	}
}</script>
<?
//shows the context articles linked to one given article mention it by printing one sentence where it is used
include("shared_inc/wiki_functions.inc.php");
$is_debug = ($_REQUEST['debug']=="on" || $_REQUEST['debug']=="true" );

$article = $_REQUEST['article'];
$articleenc = name_in_url($article);
$lang = "de";

if($_REQUEST['lang']!="")
{
    $lang=$_REQUEST['lang'];
}
$project = "wikipedia";

$needle = $_REQUEST['needle'];
$listonly = $_REQUEST['listonly'];
$allNamespaces = $_REQUEST['all'];
$userName = $_REQUEST['username'];
if($needle=="")
{
	$needle=str_replace('_', ' ', $article);
}

$server = "$lang.$project.org";
$summary =  $_REQUEST['summary'];

if($summary == "")
{
	$summary = "Link%20auf%20BKL%20%5B%5B".$needle."%5D%5D%20pr%C3%A4zisiert";
}

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
		$summary = str_replace('_ARTICLE_', $needle, $summary);
		if ($is_debug) var_dump($pages);
		foreach ($pages AS $linking_article)
		{
		if($is_debug)  echo "<hr>$linking_article<hr>";
			$ex_sentences = extract_sentences($linking_article);
			
			$sentences_cont = find_sentence_with($ex_sentences, $needle);
			
			if(count($sentences_cont)>0)
			{
				echo "<h2><a href=\"http://$server/wiki/$linking_article\">$linking_article</a></h2>";
			echo "<small><a href=\"http://$server/w/index.php?title=$linking_article&action=edit&summary=$summary\" target=\"_blank\">[edit]</a></small>\n";
			echo "<input type=\"checkbox\" id=\"".htmlspecialchars($linking_article)."\">";
			echo "<label for=\"$linking_article\">";
			
			if($is_debug)  echo "<small><a href=\"javascript:window.open('http://$server/w/index.php?title=$linking_article&action=edit').find()\">[edit-JS]</a></small>\n";
				
				foreach($sentences_cont as $s)
				{
					echo "$s.<br><br>";
				}
			}
			echo "</label>";
		}
	echo "<input id=\"target\" value=\"(Linkziel neu)\">\n";
	echo "<input type=\"button\" onclick=\"javascript:checkBoxes('$server', '$article', document.getElementById('target').value,'$userName')\" value=\"Unleash hell!\">\n";
	echo "<input type=\"button\" onclick=\"javascript:unCheckAll()\" value=\"Uncheck\">\n";
	
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
	global $server, $limit, $allNamespaces, $is_debug;
	if($is_debug)  echo "entering get_linked_pages";
	//$page = "http://".$server."/wiki/Spezial:Verweisliste/".;
	
	if($is_debug)
	{
		echo '$allNamespaces:'. $allNamespaces;
	}
	
	$page = "https://de.wikipedia.org/w/index.php?title=Spezial:Linkliste/$articleenc&limit=$limit&from=0&hideredirs=1";
	if($allNamespaces=="")
	{
		$page.="&namespace=0";
	}
	echo $page;
	$linked_list = file_get_contents ($page);
	
	// if($is_debug) echo "<hr>$linked_list <hr>";
	
	$list_begins = strpos($linked_list, '<li>');
	
	$linked_list = substr($linked_list, $list_begins);
	
	$list_ends = strpos($linked_list, '</ul>');
	$list_len = strlen($linked_list);
	
	
	$linked_list = substr($linked_list, 0, $list_ends);
	$linked_list = strip_tags($linked_list);
	
	if($is_debug) echo "<hr>linked_list before:<br>".$linked_list;
	
	$link_rows = explode("\n", $linked_list);
	
	for($i=0;$i<count($link_rows);$i++)
	{
		$end_of_link = strpos($link_rows[$i], "  ‎ (");
		$link_rows[$i] =  html_entity_decode(substr($link_rows[$i], 0, $end_of_link), ENT_QUOTES);
		if($is_debug) echo "<br>$link_rows[$i]";
	}
	
	//$linked_list = str_replace("(← Links", '', $linked_list); //Problemzeichen
	return $link_rows;
	// return explode("\n", str_replace("  ‎", "", trim($linked_list)));
}

function extract_sentences ($article)
{
	global $server, $is_debug;
	
	$page = "https://".$server."/w/index.php?action=raw&title=".urlencode($article);
	
	if($is_debug) echo "page=$page";
	$art_text = file_get_contents($page);

	// echo "<h1>art_text</h1> $art_text <hr>";
	// $plain_text = removeheaders($art_text);
	$plain_text = $art_text;
	// echo "<h1>plaintext</h1> $plain_text <hr>";

	$paragraphs = explode("\n", $plain_text);
	$sentences;
	
	foreach($paragraphs as $p)
	{
		if($is_debug) echo "<i>$p</i><br><br>";
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