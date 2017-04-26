<?php
include 'SpellCorrector.php';
//increase default memory to account for big.txt
ini_set('memory_limit','2048M');
//echo SpellCorrector::correct("octabr");
//it will output *october*
// make sure browsers see this page as utf-8 encoded HTML
$flag=0;
header('Content-Type: text/html; charset=utf-8');
$limit = 10;
$query = isset($_GET["q"]) ? $_GET["q"] : false; 
$choice = isset($_GET["choose"]) ? $_GET["choose"] : false;
//visited flag determines if spell check has already been done once
$visited = isset($_GET["visited"]) ? $_GET["visited"] : false;
$results = false;
if ($visited){
//hide the div
?>
	<style type="text/css">
	#modified{
		display:none;
	}
	</style>
<?php
}
if ($query)
{
	//split the string into multiple word array
	$words = explode(" ", $query);
	$temp=$words;
	//get the correct spelling of each word of the query into array corrected
	$corrected=array();
	foreach($words as $item){
		$term=SpellCorrector::correct($item);
		$s=array_push($corrected,$term);
	}

	$result = array_udiff($words, $corrected, 'strcasecmp'); 
	if (!empty($result) && $visited===false){
	//display modified webpage, make visibilty of search instead div true
	?>
		<style type="text/css">
		#modified{
			display:block;
			}
	</style>
	<?php
	$query=implode(" ",$corrected);
	}
	else{
	?>
		<style type="text/css">
		#modified{
			display:none;
		}
		</style>
	<?php
	}

// The Apache Solr Client library should be on the include path 
// which is usually most easily accomplished by placing in the
// same directory as this script ( . or current directory is a default 
// php include path entry in the php.ini) 
require_once('Apache/Solr/Service.php');
// create a new solr service instance - host, port, and corename
// path (all defaults in this example)
$solr = new Apache_Solr_Service('localhost', 8984, '/solr/myexample');
//if magic quotes is enabled then stripslashes will be needed
if (get_magic_quotes_gpc() == 1) {
	$query = stripslashes($query); 
}
// in production code you'll always want to use a try /catch for any 
// possible exceptions emitted by searching (i.e. connection
// problems or a query parsing error)
try{
	//to use PageRank to solve the results instead of Lucence
	$additionalParameters=array(
	'sort'=> 'PageRankFile desc'
	);
	if ($choice=="lucene"){
		$results = $solr->search($query, 0, $limit);
	}
	else if ($choice=="page_rank"){
		$results = $solr->search($query, 0, $limit, $additionalParameters);
	}
}
catch (Exception $e)
{
	// in production you'd probably log or email this error to an admin
	// and then show a special message to the user but for this example
	// we're going to show the full exception
	die("<html><head><title>SEARCH EXCEPTION</title><body><pre>{$e->__toString()}</pre></body></html>");
} 



}

?> 
<html>
<head>
<!-- css for styling autocomplete dropdown box -->
<style>
.ui-helper-hidden-accessible{
	display:none;
}
.ui-menu{
	background-color: white;
	border-style: solid;
	border-color: orange;
	border-width:1px;
	list-style-type: none;
	margin: 5px;
    	padding: 5px;
	display: block;
	
}

</style>

<!-- jquery autocomplete plugin -->
<script src="http://code.jquery.com/jquery-1.10.2.js"></script>
<script src="http://code.jquery.com/ui/1.10.4/jquery-ui.js"></script>
<script>
	$(function() {
		$("#q").autocomplete({
			autoFocus:true,
			minlength: 1,
    			source: function( request, response ) {
				var rest="";
				var s=$("#q").val();
				//only the last word is send for autocompletion, 
				//concatenated with the rest of the string and displayed.
				if (s.indexOf(' ') >= 0){
					var res=s.split(/[ ,]+/);
					s=res[res.length-1];
					s=s.toLowerCase();
					var rem=res.slice(0,res.length-1);
					rest=rem.join(" ")+" ";
				}
				else{
					s=s.toLowerCase();
				}
				var URL= 'http://localhost:8984/solr/myexample/suggest?indent=on&q='+s+'&wt=json';
        			$.ajax({
            				dataType: 'jsonp',
					jsonp : 'json.wrf',
            				type : 'GET',
            				url: URL,
            				success: function(data) {
						var obj=JSON.parse(JSON.stringify(data.suggest.suggest));
						for(var value in obj){
							value=obj[value].suggestions;
                					response( $.map(value, function(item, key) {
							var temp=item.term;
							var re = /[^A-Za-z0-9]/;
							if (temp.match(re)){
								return;
							}
							else{
								return {
									label: rest+item.term
									}
							}
                					}));
            					};
					}
            
        			});
    			}
		})
	});

</script>


<title>PHP Solr Client Example</title>
</head>
<body>

<form accept-charset="utf-8" method="get">
<label for="q">Search:</label>
<input id="q" name="q" type="text" value="<?php echo htmlspecialchars($query, ENT_QUOTES, 'utf-8'); ?>"/> <br/>
<input type="radio" name="choose" value="lucene" <?php if ($_GET['choose'] == 'lucene') echo ' checked="checked"'; ?> /> Lucene 
<input type="radio" name="choose" value="page_rank" <?php if ($_GET['choose'] == 'page_rank')  echo ' checked="checked"'; ?> /> Page_Rank <br/>
<input type="submit"/> 
</form>

<?php

if ($results) {
$total = (int) $results->response->numFound; $start = min(1, $total);
$end = min($limit, $total);
$correctWord=implode(" ",$corrected);
$tempWord=implode(" ",$temp);
?>
<!-- div showing results for spell correction -->
<div id="modified"> Showing results for <a href="./Web.php?visited=true&q=<?php echo htmlspecialchars($correctWord, ENT_NOQUOTES, 'utf-8'); ?>&choose=<?php echo htmlspecialchars($choice, ENT_NOQUOTES, 'utf-8'); ?>"><?php echo htmlspecialchars($correctWord, ENT_NOQUOTES, 'utf-8'); ?></a><br/>

Search instead for <a href="./Web.php?visited=true&q=<?php echo htmlspecialchars($tempWord, ENT_NOQUOTES, 'utf-8'); ?>&choose=<?php echo htmlspecialchars($choice, ENT_NOQUOTES, 'utf-8'); ?>">
<?php echo htmlspecialchars($tempWord, ENT_NOQUOTES, 'utf-8'); ?></a></div>
<p></p>
<div>Results <?php echo $start; ?> - <?php echo $end;?> of <?php echo $total; ?>:</div>
<ol> 
<?php
// iterate result documents
foreach ($results->response->docs as $doc)
{ 
?>
<li>
<table style="border: 1px solid black; text-align: left">
<?php
$link="";
foreach ($doc as $field => $value)
{ 
	if ($field=="id"){
		$test= end(explode("/", $value));
		$myfile = fopen("mapNYTimesDataFile.csv", "r") or die("Unable to open file!");
		//get the url from the file name on disk
		while ($row = fgetcsv($myfile)) {
        		if ($row[0] == $test) {
				$link = $row[1];
				$file=fopen("./NYTimesDownloadData/".$row[0],"r") or die("Unable to open file!");
				//get the html file contents
				$contents = file_get_contents("./NYTimesDownloadData/".$row[0]);
 				$content=htmlspecialchars($contents, ENT_NOQUOTES, 'utf-8');
				$cont=strip_tags($content);
				//Partition the content into sentences separated at full stops
				$sentences = explode(".", $cont);
				fclose($file);
            			break;
			}
    		}
    		fclose($myfile);
		$id=$value;    
	}
	if ($field=="title"){
		$title=$value;
	}
	if ($field=="description"){
		$desc=$value;
	}		
}
$words = explode(" ", $query);
$answer="";
//title and desc can contain query terms too
array_push($sentences,$title,$desc);
//create regex pattern text which matches sentences containing all query terms
$text="/";
$start_delim="(?=.*?\b";
$end_delim="\b)";
foreach($words as $item){
	$text=$text.$start_delim.$item.$end_delim;
}
$text=$text."^.*$/i";
foreach($sentences as $sentence){
	// for each sentence search if pattern occurs and also that sentence doesn't contain html markup
	if (preg_match($text, $sentence)>0){
		if (preg_match("(&gt|&lt|\:|=|\/|\%|\&)",$sentence)  ){
			continue;
		}
		else{
			$answer=$sentence;
			break;
		}
	}
}
if ($answer==""){
	//if no sentence contains all query terms, look for sentence containing any one term
	foreach($words as $item){
		$pattern="/[A-Za-z0-9]* ".$item." [A-Za-z0-9]*/i";
		foreach($sentences as $sentence){
			if (preg_match($pattern, $sentence)>0){
				if (preg_match("(&gt|&lt|\:|=|\%|\&)",$sentence)  ){
					continue;
				}
				else{
					$answer=$sentence;
					break;
				}
			}
		}
		if ($answer!="")
			break;
	}
}
?>
<tr>
<td>
<a href="<?php echo htmlspecialchars($link, ENT_NOQUOTES, 'utf-8'); ?>">  <?php echo htmlspecialchars($title, ENT_NOQUOTES, 'utf-8'); ?> </a>
</td>
</tr> 

<tr>
<td>
<a href="<?php echo htmlspecialchars($link, ENT_NOQUOTES, 'utf-8'); ?>">  <?php echo htmlspecialchars($link, ENT_NOQUOTES, 'utf-8'); ?> </a>
</td>
</tr> 

<tr>
<td> <?php echo htmlspecialchars($id, ENT_NOQUOTES, 'utf-8'); ?></td>
</tr> 

<tr>
<td> <?php echo "desc: ".htmlspecialchars($desc, ENT_NOQUOTES, 'utf-8'); ?></td>
</tr> 

<tr>
<td> <?php echo "snippet: ".htmlspecialchars($answer, ENT_NOQUOTES, 'utf-8'); ?></td>
</tr> 

</table> </li>
<?php 
}
?> 
</ol>
<?php 
}
?>

</body> </html>










