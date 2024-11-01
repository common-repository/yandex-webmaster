<?php

/*  Copyright 2012 Tesliuk Igor  (email : tigor@tigor.org.ua)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

class YWM {
public $hostlist;
public $message;

private $hostname;
private $token;
private $href;
private $error;
private $uid;
private $host_id;
private $virused;
private $last_access;
private $updated;
private $verification_state;
private $verification_details;
private $indexed;
private $indexed_details;
private $tyc;
private $index_count;
private $url_count;
private $last_update;
private $uin;


private function yget($url){
	// This function GETs document, with url on server webmaster.yandex.ru
	$ch = curl_init();
	
	$header = array(
            "GET ".$url." HTTP/1.1",
            "Host: webmaster.yandex.ru",
            "Authorization: OAuth ".$this->token,
        ); 
	curl_setopt($ch, CURLOPT_URL, 'https://webmaster.yandex.ru'.$url);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	
	$respond = curl_exec($ch);

	
	if(curl_errno($ch))
    {
		$return = false;
		$this->error = 'Curl error:'.curl_errno($ch).' ->'.$this->error;
    } else {
		$http_code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
		if ($http_code != 200)
		{
			$this->error = 'Bad Http code: '.$http_code.' ->'.$this->error;
			$return = false;
		} else {
			$return = new SimpleXMLElement($respond);
		}
		
	}
	
	return $return;
}


public function get_url_count() {
	return $this->url_count;
}

public function YWM(){
	$this->error = '';
}

public function is_virused() {

	if ('false' == $this->virused)
		{
		return false;
		} else {
		return true;
		}
}

public function accessed(){
	return $this->last_access;
}


public function auth($token){
	if ($token != ''){	
			$this->token = $token;
			return true;
		} else {
			$this->error = 'Empty token!'.' ->'.$this->error;
		}
}
	
public function is_ok() {
	if (($this->error == '') and ($this->token != ''))
		{
		return true;
		}else{
		return false;
		}
	
}
	
public function is_verified() {
	if ( 'VERIFIED' == $this->verification_state) 
		{return true;} else {return false;}
}

public function verification_info() {
	$verify = $this->yget($this->href.'/verify');
	if ($verify)
	{
		$this->uin = (string)$verify->verification->uin;
	
	} else {
		$this->uin = false;
	}
	
	return 'UIN:'.$this->uin.'; Details:'.$this->verification_details;
}
	
public function set_ids() {
	$return = true;
	$url = '/api/me';
	$ch = curl_init();
	$header = array(
        "GET ".$url." HTTP/1.1",
        "Host: webmaster.yandex.ru",
        "Authorization: OAuth ".$this->token,
    ); 
	
	curl_setopt($ch, CURLOPT_URL, 'https://webmaster.yandex.ru'.$url);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
	
	curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	
	if ((ini_get('open_basedir') == '') AND (ini_get('safe_mode' == 'Off'))){
	
	
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	$respond = curl_exec($ch);
	
	} else {
	
	
	$respond = $this->curl_redir_exec($ch);
	
	}
	
	if(curl_errno($ch))
    {
		$this->error = 'Error in set_id():'.curl_errno($ch).' ->'.$this->error;
		return false;
	} else {
		$response = curl_getinfo($ch);
		$this->uid = substr($response['url'],32);
		return true;
	}
}

public function clear() {
	$hostlist = '';
	$token = '';
	$href = '';
	$error = '';
	$uid = '';
	$host_id = '';
	$virused = '';
	$last_access = '';
	$updated = 0;
}

public function set_hostid($my_host) {
	$return = false;
	$this->hostlist = '';

	$service = $this->yget('/api/'.$this->uid);
	// var_dump($service );
	if ($service)
	{
		$url = $service->workspace->collection->attributes()->href;
		$url = substr($url,27);
		$hostlist = $this->yget($url);
		if ($hostlist)
		{
			
			foreach($hostlist->children() as $host)
			{
				
				
				if ((string)$host->name == $my_host)
				{	
					
					$return = true;
					
					$this->hostname = (string)$host->name;
					$this->href = (string)$host->attributes()->href;
					$this->virused = (string)$host->virused;
					$this->last_access = (string)$host->{'last-access'};
					$this->verification_state = (string)$host->verification->attributes()->state;
					$this->verification_details = (string)$host->verification->details;
					$this->indexed = (string)$host->crawling->attributes()->state;
					$this->indexed_details = (string)$host->crawling->details;
					$this->tyc = (string)$host->tcy;
					$this->index_count = (string)$host->{'index-count'};
					$this->url_count = (string)$host->{'url-count'};
					$this->last_update = time();
					
					
					$this->hostlist[(string)$host->name] = (string)$host->name;
					
				} else {
				$this->hostlist[(string)$host->name] = (string)$host->name;
				}
			}
			
			return $return;
		} else {
			$this->error = 'Failed to get Hostlist -> '.$this->error;
			return false;
		}
	
	} else {
		$this->error = 'Failed to get Service document->'.$this->error;
		return false;
	}
	
}
public function get_tyc(){
	return $this->tyc;
}

public function get_index_count() {
	return $this->index_count;
}

public function is_indexed() {

	if ('INDEXED' == $this->indexed)
	{
	return true;
	}else {
	return false;
	}
	
}

public function verify_by_html_file() {
	$root = $_SERVER['DOCUMENT_ROOT'];
	$file_name = 'yandex_'.$this->uin.'.html';
	$file_content = '<html>
   <head>
      <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
   </head>
   <body>Verification: '.$this->uin.'</body>
</html>';
	
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, '');
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 2);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	
	$respond = curl_exec($curl);
	$http_code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
	if ((200 == $http_code)and($file_content == $respond))
	{
	return true;
	
	} else {
	
		if (200 != $http_code)
		{
			$file = fopen($root.$file_name, w);
			if ($file)
			{
			
			
			} else {
			$this->message = 'Failed to open file for writing.';
			return false;
			}
		} else {
			$this->message = 'File exists on server, but content does not match.';
			return false;
		}
	}
	
}


public function time_since_last_update() {
	return (time()-$this->last_update);
}



public function indexed_info() {
	return $this->indexed_details;
}

public function get_error(){
	return $this->error;
	
}

public function update() {
	 $this->set_hostid($this->hostname);
	 return true;
	 
}

private function curl_redir_exec($ch) {
    static $curl_loops = 0;
    static $curl_max_loops = 20;
    
	
	
    if ($curl_loops++ >=  $curl_max_loops) 
    {
		$curl_loops = 0;
		return FALSE;
    }
     
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $data = curl_exec($ch);
    list($header, $data) = explode("\n\n", $data, 2);
	$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
	if ($http_code == 301 || $http_code == 302)
    {
		$matches = array();
		preg_match('/Location:(.*?)\n/', $header, $matches);
		$url = @parse_url(trim(array_pop($matches)));
     
		if (!$url)
		{
			//couldn't process the url to redirect to
			$curl_loops = 0;
			return $data;
     
		}
     
		$last_url = parse_url(curl_getinfo($ch, CURLINFO_EFFECTIVE_URL));
     
		if (!$url['scheme'])
			$url['scheme'] = $last_url['scheme'];
     
		if (!$url['host'])     
			$url['host'] = $last_url['host'];
     
		if (!$url['path'])     
			$url['path'] = $last_url['path'];
     
		$new_url = $url['scheme'] . '://' . $url['host'] . $url['path'] . ($url['query']?'?'.$url['query']:'');
     
		curl_setopt($ch, CURLOPT_URL, $new_url);
     
		return $this->curl_redir_exec($ch);
     
    } else {
     
		$curl_loops=0;
     
		return $data;
     
    }
     
}
	
}	
?>