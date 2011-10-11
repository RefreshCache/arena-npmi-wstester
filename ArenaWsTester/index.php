<?php
require_once("class.arenawsclient.php");

// REGISTER YOUR ENVIRONMENTS HERE
$environments = array();
$environments['dev'] = new ArenaWsClient ('http://dev.myarenaurl.com/api.svc/',
										'493f0a10-62ac-4a68-83ee-9fbb8a49c200',
										'9233b3f9-9a6d-4012-949b-a160bef8ad44' );
	
$environments['prod'] =  new ArenaWsClient ('http://.myarenaurl.com/api.svc/',
											 '493f0a10-62ac-4a68-83ee-9fbb8a49c200',
											 '9233b3f9-9a6d-4012-949b-a160bef8ad44');

$environments['refreshcache'] =  new ArenaWsClient ('http://arena.refreshcache.com/Arena/api.svc/',
											 '493f0a10-62ac-4a68-83ee-9fbb8a49c200',
											 '9233b3f9-9a6d-4012-949b-a160bef8ad44');

// global variable to stuff in the POST values and other custom data so the form can auto-populate
$formvalues = array();

function callArena($params) {
	global $environments;
	global $formvalues;
		
	$html = '';
	try {
		$start = microtime(true);
		$ws = $environments [ $params['environment'] ];
		
		$sid = null;
		$personXml = null;
		if (isset($params['session_id']) && $params['session_id'] != '') {
			$sid = $params['session_id'];
		} else {
			$personXml = $ws->login($params['user'],$params['password']);
			$sid = (string) $personXml->ArenaSessionID;	
			$formvalues['session_id'] = $sid;
		}	
		
	
		$uri = $params['ws_uri'];
		if ($uri{0} == "/") $uri = substr($uri,1);
		$args = null;
		if ($uri == 'login') {
			$returnXml = $personXml;
		} else {
			$args = array ('api_session' => $sid );
			if (isset($params['ws_param_name'])) {
				for ($i=0;$i<count($params['ws_param_name']);$i++) {
					$args[ $params['ws_param_name'][$i] ] = $params['ws_param_value'][$i];
				}
			}
			$returnXml = $ws->_getIt($uri,$args);	
		}
		
		$end = microtime(true);
        $runtime = ( $end - $start ) / 1000;
		
		$html = '<h3>Arena WS Call Successful</h3>
			<p>
			<strong>Server-side Execution Time: </strong>'.number_format($runtime,3,'.','').' ms</strong><br />
			<strong>Web Services Base URL: </strong>'.$ws->baseUrl.'</strong><br />
			<strong>Web Services URI: </strong>'.$uri.'</strong><br />
			<strong>Web Services Params: </strong><pre>'.print_r($args,true).'</pre><br />
			<strong>Session ID: </strong>'.$sid.'</strong></p>
		    <pre>'.xmlpp($returnXml->asXML(),true).'</pre>';
		
	} catch (ArenaWSException $ae) {
		$html = "EXCEPTION: ".$ae->getMessage."\nXML[".$ae->xmlRs."]\n";
	} catch (Exception $e) {
		$html = "EXCEPTION: ".$e->getMessage."\n";
	}
	
	return $html;
}

// found this on google: http://gdatatips.blogspot.com/2008/11/xml-php-pretty-printer.html
/** Prettifies an XML string into a human-readable and indented work of art 
 *  @param string $xml The XML as a string 
 *  @param boolean $html_output True if the output should be escaped (for use in HTML) 
 */  
function xmlpp($xml, $html_output=false) {  
    $xml_obj = new SimpleXMLElement($xml);  
    $level = 4;  
    $indent = 0; // current indentation level  
    $pretty = array();  
      
    // get an array containing each XML element  
    $xml = explode("\n", preg_replace('/>\s*</', ">\n<", $xml_obj->asXML()));  
  
    // shift off opening XML tag if present  
    if (count($xml) && preg_match('/^<\?\s*xml/', $xml[0])) {  
      $pretty[] = array_shift($xml);  
    }  
  
    foreach ($xml as $el) {  
      if (preg_match('/^<([\w])+[^>\/]*>$/U', $el)) {  
          // opening tag, increase indent  
          $pretty[] = str_repeat(' ', $indent) . $el;  
          $indent += $level;  
      } else {  
        if (preg_match('/^<\/.+>$/', $el)) {              
          $indent -= $level;  // closing tag, decrease indent  
        }  
        if ($indent < 0) {  
          $indent += $level;  
        }  
        $pretty[] = str_repeat(' ', $indent) . $el;  
      }  
    }     
    $xml = implode("\n", $pretty);     
    return ($html_output) ? htmlentities($xml) : $xml;  
}  

function genForm() { 
	global $environments;
	global $formvalues;
?>
				<form id="theform" name="theform" method="post">
					<table>
						<tr>
							<th>Environment:</th>
							<td><?php 
								foreach ($environments as $k => $e) {
									
									echo '<label for="env-'.$k.'">'.$k.'</label><input type="radio" name="environment" value="'.$k.'" id="env-'.$k.'" '. ( (isset($formvalues['environment']) && $formvalues['environment'] == $k) ? 'checked="checked" ' : '' ) . '/>';
								}
							?>
							</td>
						</tr>
						<tr>
							<th>User ID:</th>
							<td><input type="text" name="user" id="user" size="40" /></td>
						</tr>
						<tr>
							<th>Password:</th>
							<td><input type="password" name="password" id="password" size="40" /></td>
						</tr>
						<tr>
							<td colspan="2">-- OR, if already logged in, enter session id below --</td>
						</tr>
						<tr>
							<th>SessionID:</th>
							<td><input type="text" name="session_id" id="session_id" size="40" value="<?php echo $formvalues['session_id']; ?>" /></td>
						</tr>
						<tr>
							<td colspan="2">Web Service Call Info</td>
						</tr>
						<tr>
							<th>URI:</th>
							<td><input type="text" name="ws_uri" id="ws_uri" size="40" /></td>
						</tr>
						<tr>
							<th>Parameters</th>
							<td><a href="javascript:void(0);" id="addparam">add param</a></td>
						</tr>
					</table>
					<div>
						<input type="submit" value="submit" name="submitme" />
					</div>
				</form>	
<script type="text/javascript">
var addedAny = false;
$(document).ready(function() {
	$("a#addparam").click(function(evt) {
		if (!addedAny) {
			$("#theform table").append( $('<tr>').append( $('<th>').html('param name') ).append( $('<th>').html('param value') ) );
		}
		$tr = $('<tr>');
		$tr.append( $('<th>').html( '<input type="text" name="ws_param_name[]" size="20" />' ) );
		$tr.append( $('<td>').html( '<input type="text" name="ws_param_value[]" size="60" />' ) );
		$("#theform table").append( $tr );
		addedAny = true;
	});
});
</script>		

<?php
}

if (isset($_POST['submitme']) && $_POST['submitme'] == 'submit') { 
	$formvalues = $_POST;
	$html = callArena($_POST);

?>
<html>
	<head>
		<title>Arena WS Tester - Result</title>
		<style type="text/css">
		body { font-family: Verdana, Arial, sans-serif; background-color: #efefef; }
		#wrapper { width: 960px; margin: 0px auto; }
		#content { padding: 10px; }
		#result { border: thin solid #6c6c6c; padding: 10px; background-color: #ffffff; color: #333333; }
		a#doform { float:right; }
		.clear { clear:both; line-height: 1px; }
		form { border: thin solid #6c6c6c; padding:10px; }
		form table, form div { width: 600px; margin: 10px auto; }
		form table tr th { text-align: left; }
		</style>
		<script type="text/javascript" src="http://code.jquery.com/jquery-latest.min.js"></script>
<script type="text/javascript">
$(document).ready(function() {
	$("a#doform").click(function(evt) {
		$("#form").html( $("#formContent").html() );
	});
});
</script>
	</head>
	<body>
		<div id="wrapper">
			<h1>Arena WS Tester - Result</h1>
			<div id="content">
				<h2 style="float:left">Web Services Results</h2>
				<a href="javascript:void(0)" id="doform">go again</a>
				<div class="clear">&nbsp;</div>
				<div id="form"></div>
				<div id="result">
					<?php echo $html; ?>
				</div>	
			</div>
		</div>
		<div id="formContent" style="display:none;">
			<?php echo genForm(); ?>
		</div>
	</body>
</html>
<?php
} else {
?>
<html>
	<head>
		<title>Arena WS Tester</title>
		<style type="text/css">
		body { font-family: Verdana, Arial, sans-serif; background-color: #efefef; }
		#wrapper { width: 960px; margin: 0px auto; }
		form { border: thin solid #6c6c6c; padding:10px; }
		form table, form div { width: 600px; margin: 10px auto; }
		form table tr th { text-align: left; }
		</style>
		<script type="text/javascript" src="http://code.jquery.com/jquery-latest.min.js"></script>
	</head>
	<body>
		<div id="wrapper">
			<h1>Arena WS Tester</h1>
			<div id="content">
				<?php echo genForm(); ?>
			</div>
		</div>
	</body>
</html>
<?php 
}
?>
