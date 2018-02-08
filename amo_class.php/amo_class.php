<?php

class AMO {
	public $subdomain;
	public $acc;
	public $last_code;
	private $cookie_name;
	public $logged;
	public $CONTACT_EMAIL_ID;
	public $CONTACT_PHONE_ID;
	public $last_error;
	public function __construct($user,$API,$subdomain)
	{
		$this->cookie_name="amo_cookie.txt";
		$this->logged=false;
		$this->subdomain=$subdomain;

		$res=$this->auth($API,$user);

		if(isset($res['response']['auth'])&&$res['response']['auth']==1){
			$this->logged=true;}
			else {
				die("Авторизация в АМО не удалась\n");}

				$this->acc=array();
				$this->acc=$this->get_acc();
				if(isset($this->acc))
				{
					$this->acc=$this->acc['_embedded'];
					$this->CONTACT_EMAIL_ID=array_values($this->acc['custom_fields']['contacts'])[2]['id'];
					$this->CONTACT_PHONE_ID=array_values($this->acc['custom_fields']['contacts'])[1]['id'];
				}


			}

	public function sendPOST($link,$var,$auth=false)
	{
	    $curl=curl_init(); #Сохраняем дескриптор сеанса cURL
	    #Устанавливаем необходимые опции для сеанса cURL
	    curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
	    curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-API-client/2.0');
	    curl_setopt($curl,CURLOPT_URL,$link);
	    curl_setopt($curl,CURLOPT_CUSTOMREQUEST,'POST');
	    curl_setopt($curl,CURLOPT_POSTFIELDS,json_encode($var));
	    curl_setopt($curl,CURLOPT_HTTPHEADER,array('Content-Type: application/json'));
	    curl_setopt($curl,CURLOPT_HEADER,false);
	    if($auth)curl_setopt($curl,CURLOPT_COOKIEJAR,$this->cookie_name);
	    	else
			curl_setopt($curl,CURLOPT_COOKIEFILE,$this->cookie_name); #PHP>5.3.6 dirname(__FILE__) -> __DIR__

	    curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,0);
	    curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,0);
	    $out=curl_exec($curl); #Инициируем запрос к API и сохраняем ответ в переменную
	    $code=curl_getinfo($curl,CURLINFO_HTTP_CODE); #Получим HTTP-код ответа сервера
	    $this->last_code=$code;
	    if($code!=200)$this->last_error=$out;
		if($code==429)sleep(5);
	    curl_close($curl); #Завершаем сеанс cURL
	    $Response=json_decode($out,true);
	    return $Response;
	}

	public function sendGET($link,$var=array())
	{
		$link=$link.http_build_query($var);
		$curl=curl_init(); #Сохраняем дескриптор сеанса cURL
	    #Устанавливаем необходимые опции для сеанса cURL
		curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
		curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-API-client/2.0');
		curl_setopt($curl,CURLOPT_URL,$link);
		curl_setopt($curl,CURLOPT_CUSTOMREQUEST,'GET');
		curl_setopt($curl,CURLOPT_HTTPHEADER,array('Content-Type: application/json'));
		if(isset($var['if-modified-since']))curl_setopt($curl,CURLOPT_HTTPHEADER,array('Content-Type: application/json','if-modified-since: '.$var['if-modified-since']));
		curl_setopt($curl,CURLOPT_HEADER,false);
	    curl_setopt($curl,CURLOPT_COOKIEFILE,$this->cookie_name); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
	    curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,0);
	    curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,0);
	    $out=curl_exec($curl); #Инициируем запрос к API и сохраняем ответ в переменную
	    $code=curl_getinfo($curl,CURLINFO_HTTP_CODE); #Получим HTTP-код ответа сервера
	    $this->last_code=$code;
	    if($code!=200)$this->last_error=$out;
	if($code==429)sleep(5);
	    curl_close($curl); #Завершаем сеанс cURL
	    $Response=json_decode($out,true);
	    return $Response;

	}

	public function auth($API,$user)
	{
		$link='https://'.$this->subdomain.'.amocrm.ru/private/api/auth.php?type=json';
		$user_info=array(
    	'USER_LOGIN'=>$user, #Ваш логин (электронная почта)
    	'USER_HASH'=>$API #Хэш для доступа к API (смотрите в профиле пользователя)
    	);
		$res=$this->sendPOST($link,$user_info,1);
		return $res;
	}

	public function get_acc()
	{
		if(!$this->logged) return -1;
		if(count($this->acc)>0)return $this->acc;
		$link='https://'.$this->subdomain.'.amocrm.ru/api/v2/account?with=custom_fields,users,pipelines,groups,note_types,task_types';
		$res=$this->sendGET($link);
		return $res;
	}

	public function get_contacts($param)
	{
		if(!$this->logged) return -1;
		$link='https://'.$this->subdomain.'.amocrm.ru/api/v2/contacts?';
		$res=$this->sendGET($link,$param);
		if($this->last_code!=200)return -1;
		return $res['_embedded']['items'];
	}

	public function get_emails($contact)
	{

		$result=array();
		$f=$contact['custom_fields'];
		foreach ($f as $key => $value) {
			if($value['id']==$this->CONTACT_EMAIL_ID){
				foreach ($value['values'] as $key1 => $value1) {
					$result[]=strtolower($value1['value']);
				}
			}
		}
		return $result;
	}

public function get_phones($contact)
	{

		$result=array();
		$f=$contact['custom_fields'];
		foreach ($f as $key => $value) {
			if($value['id']==$this->CONTACT_PHONE_ID){
				foreach ($value['values'] as $key1 => $value1) {
					$result[]=strtolower($value1['value']);
				}
			}
		}
		return $result;
	}

	public    function get_field($contact,$field_id)
	{

		$result=array();
		$f=$contact['custom_fields'];
		foreach ($f as $key => $value) {
			if($value['id']==$field_id){
				foreach ($value['values'] as $key1 => $value1) {
					$result[]=$value1['value'];
				}
			}
		}
		return $result;
	}

	public   function get_field_enum($contact,$field_id)
	{

		$result=array();
		$f=$contact['custom_fields'];
		foreach ($f as $key => $value) {
			if($value['id']==$field_id){
				foreach ($value['values'] as $key1 => $value1) {
					$result[]=$value1['enum'];
				}
			}
		}
		return $result;
	}

	public function find_emails($email)
	{
		$email=strtolower($email);
		$param=array('query'=>$email);
		$contacts=$this->get_contacts($param);
		$c=array();
		if(empty($contacts))return $c;
		foreach ($contacts  as $value) {
			$e=$this->get_emails($value);
			if(in_array($email, $e))$c[]=$value;
		}
		return $c;
	}

	public function find_emails_lead($email)
	{
		$email=strtolower($email);
		$param=array('query'=>$email);
		$leads=$this->get_leads($param);
		$c=array();
		if(empty($leads))return $c;
		foreach ($leads  as $value) {
			$c[]=$value;
		}
		return $c;
	}


	public function find_phones($phone)
	{
		$phone=preg_replace("/[^0-9]/", "", $phone);
		$param=array('query'=>$phone);
		$contacts=$this->get_contacts($param);
		$c=array();
		if(empty($contacts))return $c;
		return $contacts;
	}


	public function get_leads($param)
	{
		if(!$this->logged) return -1;
		$link='https://'.$this->subdomain.'.amocrm.ru/api/v2/leads?';
		$res=$this->sendGET($link,$param);
		if($this->last_code!=200)return -1;
		return $res['_embedded']['items'];

	}

	public function find_lead($id)
	{
		$param=array('id'=>$id);
		$lead=$this->get_leads($param);
		if(!empty($lead))return $lead;
        return -1;

	}

	public function find_contact($id)
	{
		$param=array('id'=>$id);
		$contact=$this->get_contacts($param);
		if(!empty($contact))return $contact;
        return -1;

	}

	public function get_tasks($lead_id)
	{
		if(!$this->logged) return -1;
		$link='https://'.$this->subdomain.'.amocrm.ru/api/v2/tasks?';
		$param=array('type'=>'lead','element_id'=>$lead_id);
		$res=$this->sendGET($link,$param);
		if($this->last_code!=200)return -1;
		return $res['_embedded']['items'];

	}

	public function add_tasks($tasks)
	{
		$link='https://'.$this->subdomain.'.amocrm.ru/api/v2/tasks';
		$param['add']=$tasks;
		$res=$this->sendPOST($link,$param);
		if($this->last_code!=200)return -1;
		return $res;
	}

	public function add_tag($contact_id,$tag)
	{
		date_default_timezone_set( 'Europe/Moscow' );
		$link='https://'.$this->subdomain.'.amocrm.ru/api/v2/contacts';
		$param=array('id'=>$contact_id);
		$c=$this->get_contacts($param);
		if(empty($c))return -1;
		$old_tags=$c[0]['tags'];
		$tags=array();
		foreach ($old_tags as $value) {
			$tags[]=$value['name'];

		}
		$tags[]=$tag;
		$tags=implode(",", $tags);
		$param['update']=array(array('id'=>$contact_id,'tags'=>$tags,'last_modified'=>time()));
		$res=$this->sendPOST($link,$param);
		if($this->last_code!=200)return -1;
		return $res;
	}



	public function add_contact($contact)
	{

		$link='https://'.$this->subdomain.'.amocrm.ru/api/v2/contacts';
		$param['add']=array($contact);
		$res=$this->sendPOST($link,$param);
		if($this->last_code!=200)return -1;
		return $res['_embedded']['items'];
	}
    
	public function update_contact($contact)
	{

		$link='https://'.$this->subdomain.'.amocrm.ru/api/v2/contacts';
		$param['update']=array($contact);
		$res=$this->sendPOST($link,$param);
		if($this->last_code!=200)return -1;
		return $res['_embedded']['items'];
	}
	public function add_lead($lead)
	{
		$link='https://'.$this->subdomain.'.amocrm.ru/api/v2/leads';
		$param['add']=array($lead);
		$res=$this->sendPOST($link,$param);
		if($this->last_code!=200)return -1;
		return $res['_embedded']['items'];

	}

	public function edit_lead($lead,$id)
	{
		$link='https://'.$this->subdomain.'.amocrm.ru/api/v2/leads';
		$lead['id']=$id;
		$lead['updated_at']=time();
		$param['update']=array($lead);
		$res=$this->sendPOST($link,$param);
		if($this->last_code!=200)return -1;
		return $res['_embedded']['items'];

	}



	public function add_contact_info($name,$phone='',$email='',$linked_lead='')
	{
        $cont=array(
			'name'=>$name,
			'leads_id'=>$linked_lead,
			'custom_fields'=>array(
				!empty($phone)?array(
					'id'=>$this->CONTACT_PHONE_ID,
					'values'=>array(
						array(
							'value'=>$phone,
							'enum'=>'WORK'
							)
						)
					):array(),

				!empty($email)?array(
					'id'=>$this->CONTACT_EMAIL_ID,
					'values'=>array(
						array(
							'value'=>$email,
							'enum'=>'WORK'
							)
						)
					):array()
				)
			);
        if(!empty($email) || !empty($phone))
        {
            !empty($c=$this->find_emails($email))?$c:$c=find_phones($phone);
            if(!empty($c))
            {
                $cont['id']=$c[0]['id'];
                $cont['updated_at']=time();
                unset($cont['leads_id']);
                
            }
            $res=$this->update_contact($cont);
        }else 
            
		$res=$this->add_contact($cont);
		return $res;

	}

	
}



?>
