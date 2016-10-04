<?php
////////////////SUMMON////////////////////
if(!defined(SUMMON_API_ID)){
define('SUMMON_API_ID','ubc');
define('SUMMON_API_KEY','9v18i52d05R379K2zd92pW8lW2WFFzXw');
define('SUMMON_SERVER','http://api.summon.serialssolutions.com');
}
class Model_summon{

function findInSummonByTitle($value,$types=false){
	if(!$types) $types=array('Book','eBook','Microform');
	return $this->findInSummon(
		array(
			array('Title',$value)
		),
		$types
	);
}

function findInSummonByIsbn($value,$types=false){
	if(!$types) $types=array('Book','eBook','Microform');
	return $this->findInSummon(
		array(
			array('ISBN',$value)
		),
		$types
	);
}

function findInSummonByCallno($value,$types=false){
	return $this->findInSummon(
		array(
			array('LCCallNum',$value),
		)
	);
}

function findInSummonById($idtype,$value){
//just journals -- doi,pmid
	$field=strtoupper($idtype);
	return $this->findInSummon(
		array(
			array($field,$value)
		),
		array('Journal Article','Newspaper Article')
	);
}

function findJAInSummon($journal,$article){
	return $this->findInSummon(
		array(
			array('PublicationTitle',$journal),
			array('Title',$article)
		),
		array('Journal Article','Newspaper Article','Book Review')
	);
}

function findInSummon($queries,$contentTypes=array()){
	$queryParams=array(
		's.ho=true' //in holdings
		,'s.hl=false' //no highlight
    ,'s.ps=50'
	);
	foreach($queries as $q){ // $q=array(field,value)
    if($q[0]){
      $queryParams[]='s.q='.$q[0].':'.str_replace(':','\:',$q[1]);
    }else{
      $queryParams[]='s.q='.str_replace(':','\:',$q[1]);
    }
	}
	foreach($contentTypes as $ct){
		$queryParams[]='s.fvf=ContentType,'.$ct;
	}
	$res=summonRequest($queryParams);
	if(!$res) return array();
	$res=json_decode($res,true);
	if(!$res['documents']) return array();
	return $res;
}

function summonRequest($queryParams){
  /* WARNING: very sensitive code
    see http://api.summon.serialssolutions.com/help/api/search
    Note that the sample PHP code given there is terrible
    and doesn't even work
  */
  
	sort($queryParams);
	$queryStringAuth=implode('&',$queryParams);
	$queryString=array();
	foreach($queryParams as $qp){
		list($k,$v)=explode('=',$qp);
		$queryString[]=$k.'='.urlencode($v);
	}
	$queryString=implode('&',$queryString);
	$headers = array(
		'Accept' => 'application/json',
		'x-summon-date' => gmdate('D, d M Y H:i:s ').'GMT',
		'Host' => 'api.summon.serialssolutions.com',
	);
	$authstring=implode($headers,"\n")."\n"
			."/2.0.0/search\n"
			.urldecode($queryStringAuth)."\n";

	$headers['Authorization']=
		'Summon '
    .SUMMON_API_ID
    .';'
		.$this->hmacsha1(
			SUMMON_API_KEY,
			$authstring
		);
	$curlheaders=array();
	foreach($headers as $headername=>$headerval){
		$curlheaders[]="$headername: $headerval";
	}
	$ch=curl_init(SUMMON_SERVER.'/2.0.0/search?'.$queryString);
	curl_setopt($ch,CURLOPT_HTTPHEADER,$curlheaders);
	curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
	$res=curl_exec($ch);
	return $res;
}

private function hmacsha1($key,$data){
  if(function_exists('hash_hmac')){
    return base64_encode(hash_hmac('sha1',$data,$key,true));
  }
	$hashfunc='sha1';
	if (strlen($key)>$blocksize) {
		$key=pack('H*', $hashfunc($key));
	}
	$key=str_pad($key,$blocksize,chr(0x00));
	$ipad=str_repeat(chr(0x36),$blocksize);
	$opad=str_repeat(chr(0x5c),$blocksize);
	$hmac = pack(
				'H*',$hashfunc(
					($key^$opad).pack(
						'H*',$hashfunc(
							($key^$ipad).$data
						)
					)
				)
			);
	return base64_encode($hmac);
}
}
