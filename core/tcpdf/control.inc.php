<?php
//receive an fileed file
 
require_once(Config::get('approot').'/core/resolver.inc.php');
require_once(Config::get('approot').'/core/idboxapi.inc.php');

function control_file_form(){
  
  $referrer = $_REQUEST['HTTP_REFERER'];
  
  //First see if we can grab a user PUID
  //else make them login using ldap
  
  // the /u/var in the URL
  $sessionID = gv('u');
  $puid = '';
  
  if(isset($sessionID)){
	//someome can type rubbish in that url though
	$puid = resolveSession($sessionID);
  }
  
  if (!isset($sessionID) || $puid === '') {
	//if nothing is set then we can defer to view to enforce a modal login
	//require_login('ldap');
	ssv('user',NULL);
	return array('display'=>'form','name'=>$name);
  }
  else {
	//need to call this since you didn't go through ldap
	//to sssv stuff that will be needed to prevent system
	//from kicking you out.
	
	//TODO  determine, are we ssv(user) as the puid as well? If not, need to add a GetCWLfromPUID function to idbox.api@kemano to get the user CWL.
	authenticate_granted($puid,$puid);
  }
  
  $name='n'.rand(10000,99999).md5(date('U'));
  ssv('file_name',$name);

  
  return array('display'=>'form','name'=>$name, 'referrer'=>$referrer);
}


function control_file_autofill(){
  
  $areshandle = connectToAres();
  $query = $areshandle->prepare('
	SELECT    Items.ItemID, Items.ClassID, Items.Username, Items.ActiveDate, Items.InactiveDate, Items.Title,
			  Items.Author, Items.Publisher, Items.PubPlace,Items.PubDate, Items.Edition, Items.ISXN,
			  Items.JournalYear, Items.JournalMonth, Items.DOI, Items.ArticleTitle, Items.Volume, Items.Issue, 
              Items.Pages
	FROM      Items
	WHERE ClassID = ? AND ItemID = ?'); 
  $query->bindParam(1, gv('aresclassid'), PDO::PARAM_INT);
  //$query->bindParam(1, '138', PDO::PARAM_STR);
  $query->bindParam(2, gv('aid'), PDO::PARAM_INT);
  $query->execute();
  
  $row = $query->fetch(PDO::FETCH_ASSOC);

  unset($areshandle);
  unset($query);
  
  return array('display'=>'api','clf'=>false, 'json'=>json_encode($row));
  
}


function control_file_metadata(){
  
  $meta=pv('meta',array());
  foreach($meta as $k=>$v){
    $meta[$k]=trim($v);
  }
  
  //check here for required metadata
  if($meta['source']=='ares'){
    if(empty($meta['itemtype'])){
	  trigger_error('Missing required item metadata');
      return array('controller_error'=>'Missing required itemtype metadata');
    }
    switch($meta['itemtype']){
      case 'bookchapter':
        $required=array('classid','main-title','author','inclusive-pages');
        $optional=array('ChapterTitle','Publisher','PublicationPlace','PublicationDate','Edition','ISBN','Notes','Tags','StartDate','EndDate');
        break;
      case 'article':
		//TODO: change case of vairables to lower
        $required=array('ClassID','PublicationTitle','ArticleTitle','Author','InclusivePages');
        $optional=array('Volume','IssueOrNumber','Month','Year','ISSN','DOI','Notes','Tags','StartDate','EndDate');
        break;
      case 'other':
		//TODO: change case of vairables to lower
        $required=array('ClassID','Title','Author','InclusivePages','ItemProvenance');
        $optional=array('Publisher','PublicationPlace','PublicationDate','ISBN','Notes','Tags','StartDate','EndDate');
        break;
      default:
        return array('controller_error'=>'Unknown itemtype "'.$meta['itemtype'].'" specified.');
    }
    foreach($required as $rfield){
	  //changed from a meta check to a $_POST check
      if(!(pv($rfield))){
		trigger_error('Missing required item metadata for '.$rfield);
        return array('controller_error'=>'Missing required metadata field "'.$rfield.'" for itemtype "'.$meta['itemtype'].'".');
      }
    }
    foreach($optional as $ofield){
      if(empty($meta[$ofield])){
        $meta[$ofield]='';
      }
    }
  }
  return array('display'=>'success','access_url'=>Config::get('baseurl').'/download.get/id/'.$hash);
}


  




function control_file_receive(){
  
  $docstoredirectory = Config::get('docstore_docs');
  
  // create new directory with 777 permissions if it does not exist yet
  // owner will be the user/group the PHP script is run under
  if ( !file_exists($docstoredirectory) ) {
	if (!@mkdir($docstoredirectory, 0777)) {
    $error = error_get_last();
    return array('display'=>'api', 'clf'=>false, 'json'=>json_encode($error['message']));;
	}
  }
  
  // the /u/var in the URL
  $sessionID = gv('u');
  $puid = '';
  
  if(isset($sessionID)){
	//someome can type rubbish in that url though
	$puid = resolveSession($sessionID);
  }
  
  if (!isset($sessionID) || $puid === '') {
	//if nothing is set then we can defer to view to enforce a modal login
	//require_login('ldap');
	ssv('user',NULL);
	$errormessage = "Login credentials missing";
	return array('display'=>'api', 'clf'=>false, 'json'=>json_encode($errormessage));
  }
  else {
	//need to call this since you didn't go through ldap
	//to sssv stuff that will be needed to prevent system
	//from kicking you out.
	
	//TODO  determine, are we ssv(user) as the puid as well? If not, need to add a GetCWLfromPUID function to idbox.api@kemano to get the user CWL.
	authenticate_granted($puid,$puid);
  }
  
  if (is_uploaded_file($_FILES['uploadfile']['tmp_name'])) {
	//return array('display'=>'api', 'clf'=>false, 'json'=>json_encode($_FILES['uploadfile']['tmp_name']));
  }
  
  if($_FILES['uploadfile']['error']) {
	$errormessage = "An error has occured";
	switch ($_FILES['uploadfile']['error']) { 
            case UPLOAD_ERR_INI_SIZE: 
                $errormessage = "The uploaded file exceeds the maximum file size allowed (php.ini)";
                break; 
            case UPLOAD_ERR_FORM_SIZE: 
                $errormessage = "The uploaded file exceeds the maximum file size allowed for uploads (Form MAX_SIZE field)"; 
				break; 
            case UPLOAD_ERR_PARTIAL: 
                $errormessage = "The uploaded file was only partially uploaded"; 
				break; 
            case UPLOAD_ERR_NO_FILE: 
                $errormessage = "No file was uploaded"; 
				break; 
            case UPLOAD_ERR_NO_TMP_DIR: 
                $errormessage = "Missing a temporary folder"; 
				break; 
            case UPLOAD_ERR_CANT_WRITE: 
                $errormessage = "Failed to write file to disk"; 
				break; 
            case UPLOAD_ERR_EXTENSION: 
                $errormessage = "File upload stopped by extension"; 
				break; 
            default: 
                $errormessage = "Unknown Error"; ; 
				break; 
        }
	return array('display'=>'api', 'clf'=>false, 'json'=>json_encode($errormessage));
  }
  
  $filename=$_FILES['uploadfile']['name'];
  
  $savefile=md5($filename.'this is a salt value to thwart hackers').'.file';
  
  
  $meta=pv('meta',array());
  foreach($meta as $k=>$v){
    $meta[$k]=trim($v);
  }
  $referrer_match='';

  $referrer_match='ares\.library\.ubc\.ca.*?ClassID='.gv('classid');

  $hash=nonsense();
  if(file_exists($savefile)){
    $messages[]='Existing file was replaced.';
    //notify interested parties if uploader is faculty
    //if($meta['uploader']=='Ares'){
      send_email(
        Config::get('copyright_emails')
        ,'Document store: file replaced'
        ,'The file "'.$filename.'" has been replaced by Ares user '.$puid.'. for ClassID '.$meta['ClassID']."\n"
          .'Access link: '.Config::get('baseurl').'/file/get/'.$hash
      );
    //}
  }
  else {
	//File does not exist
	
	//echo "File does not exist. First time writing file.";
  }

  if(move_uploaded_file($_FILES['uploadfile']['tmp_name'],Config::get('docstore_docs').$savefile)){
  }
  else {
	$errormessage = "Writing file failed";
	return array('display'=>'api', 'clf'=>false, 'json'=>json_encode($errormessage));
  }
  
  //TODO - this should be a set() or update() in model, that controller will call.
  $db=(new DBFinder())->getDB();
  
  /*	TODO - Use these (PHP's PDO:) statements if you can't finish DB wrapper in time for DocStore V1
   *
   *	$docdata = array ($hash, $savefile, $referrer_match, sv('puid'), date_format(date_create(pv('item-start')),"Y-m-d"), date_format(date_create(pv('item-stop')),"Y-m-d"), date_format(date_create(pv('item-stop')),"Y-m-d"));
   *
   *	$sql= $db->prepare("INSERT INTO document (`hash`,`filename`,`referrer_match`,`uploaded_by`,`modified`,`available`,`unavailable`,`purge`) VALUES (?,?,?,?,NOW(),?,?,?)");
   *
   *	$sql->execute($docdata);
   *
   *	
   **/
  
  $docdata = array ($hash, md5($filename.'this is a salt value to thwart hackers'), $referrer_match, $puid, date_format(date_create($meta['item-start']),"Y-m-d"), date_format(date_create($meta['item-stop']),"Y-m-d"), date_format(date_create($meta['item-stop']),"Y-m-d"));
  
  $sql= $db->prepare("INSERT INTO document (`hash`,`filename`,`referrer_match`,`uploaded_by`,`modified`,`available`,`unavailable`,`purge`) VALUES (?,?,?,?,NOW(),?,?,?)");
  $sql->execute($docdata);
  
  $qry  = "INSERT INTO metadata (`hash`,`field`,`value`) VALUES ";
  $qry .= "(?, 'doctype', ?),";
  $qry .= "(?, 'title', ?),";
  $qry .= "(?, 'classid',?);";
  
  $docmetdata = array ($hash, $meta['document-type'], $hash, $meta['title'], $hash, $meta['classid']);
  $sql= $db->prepare($qry);
  $sql->execute($docmetdata);  
   
  
  $db = NULL;
  
  
  return array('display'=>'api', 'clf'=>false, 'json'=>json_encode(array('savedFile' => $savefile, 'access_url'=>Config::get('baseurl').'/download.get/id/'.$hash, 'metadata' => $meta)));
 
}


















//This will allow ARES to request what type of DocStore file was stored
function control_file_type(){
  
  // the /u/var in the URL
  $sessionID = gv('u');
  $puid = '';
  
  if(isset($sessionID)){
	//someome can type rubbish in that url though
	$puid = resolveSession($sessionID);
  }
  
  if (!isset($sessionID) || $puid === '') {
	//if nothing is set then we can defer to view to enforce a modal login
	//require_login('ldap');
	ssv('user',NULL);
	$errormessage = "Login credentials missing";
	return array('display'=>'api', 'clf'=>false, 'json'=>json_encode($errormessage));
  }
  else {
	//need to call this since you didn't go through ldap
	//to sssv stuff that will be needed to prevent system
	//from kicking you out.
	
	//TODO  determine, are we ssv(user) as the puid as well? If not, need to add a GetCWLfromPUID function to idbox.api@kemano to get the user CWL.
	authenticate_granted($puid,$puid);
  }
  
  
  $did=gv('did');

  
  //TODO - this should be a set() or update() in model, that controller will call.
  $db=(new DBFinder())->getDB();
  
  /*	TODO - Use these (PHP's PDO:) statements if you can't finish DB wrapper in time for DocStore V1
   *
   *	$docdata = array ($hash, $savefile, $referrer_match, sv('puid'), date_format(date_create(pv('item-start')),"Y-m-d"), date_format(date_create(pv('item-stop')),"Y-m-d"), date_format(date_create(pv('item-stop')),"Y-m-d"));
   *
   *	$sql= $db->prepare("INSERT INTO document (`hash`,`filename`,`referrer_match`,`uploaded_by`,`modified`,`available`,`unavailable`,`purge`) VALUES (?,?,?,?,NOW(),?,?,?)");
   *
   *	$sql->execute($docdata);
   *
   *	
   **/
  
  $docdata = array ($did);
  
  $sql= $db->prepare("SELECT value FROM metadata WHERE field = 'doctype' and hash = ?");
  $sql->execute($docdata);
  
  $row = $sql->fetch(PDO::FETCH_OBJ);
  
  $db = NULL;
  
  
  return array('display'=>'api', 'clf'=>false, 'json'=>json_encode(array('doctype' => $row->value, 'hash' => $did)));
 
}






























//need to implement
function control_file_browse (){
	//TODO

	trigger_error('Empty method, please define "control_file_browse()"');
	echo '<script type="text/javascript">console.log("get rid of me! controller..file..control")</script>';

}

//is this unique??
function nonsense(){
  $chars='bcdfghjklmnpqrstvwxzBCDFGHJKLMNPQRSTVWXZ23456789-=_.';
  $chars=str_split($chars);
  //Note: rand() is inclusive
  $len=count($chars)-1;
  $nonsense='';
  for($i=0;$i<60;$i++){
    $nonsense.=$chars[rand(0,$len)];
  }
  return $nonsense;
}
