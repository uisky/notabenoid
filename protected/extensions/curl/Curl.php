<?php
/*

* Yii extension CURL

* This is a base class for procesing CURL REQUEST
*
* @author Igor Ivanović
* @version 0.1
* @creation date: 15-12-2010
* @filesource CURL.php
*
1. You have to download and upload files to /protected/extensions/curl/.
2. Include extension in config/main.php


'CURL' =>array(
'class' => 'application.extensions.curl.Curl',
     //you can setup timeout,http_login,proxy,proxylogin,cookie, and setOPTIONS

     //eg.
   'options'=>array(
        'timeout'=>0,

        'cookie'=>array(
			'set'=>'cookie'
		),

		'login'=>array(
			'username'=>'myuser',
			'password'=>'mypass'
		),

		'proxy'=>array(
			'url'=>'someproxy.com',
			'port'=>80,
		),

		'proxylogin'=>array(
			'username'=>'someuser',
			'password'=>'somepasswords'
		),

        HIRE you have to pay attention,
        you have to use CURL_CONSTANTS
        eg.

		'setOptions'=>array(
            CURLOPT_UPLOAD => true,
            CURLOPT_USERAGENT => Yii::app()->params['agent'];
		),
   ),
),
*/

class Curl extends CApplicationComponent{


        protected $url;
        protected $ch;

	public $options = array();
	public $info = array();
    	public $error_code = 0;
    	public $error_string = '';



	protected $validOptions = array(
        'timeout'=>array('type'=>'integer'),
	'login'=>array('type'=>'array'),
	'proxy'=>array('type'=>'array'),
	'proxylogin'=>array('type'=>'array'),
	'setOptions'=>array('type'=>'array'),
	);


	/**
	 * Initialize the extension
	 * check to see if CURL is enabled and the format used is a valid one
	 */
	public function init(){
		if( !function_exists('curl_init') )
		throw new CException( Yii::t('Curl', 'You must have CURL enabled in order to use this extension.') );

	}

         /**
        * Setter
        * @set the option
        */
        protected function setOption($key,$value){
        curl_setopt($this->ch,$key, $value);
        }


	/**
	* Formats Url if http:// dont exist
	* set http://
	*/
        public function setUrl($url){
	if(!preg_match('!^\w+://! i', $url)) {
	$url = 'http://'.$url;
	}
        $this->url = $url;
        }



	 /*
	* Set Url Cookie
	*/
        public function setCookies($values){
        if (!is_array($values))
        throw new CException(Yii::t('Curl', 'options must be an array'));
        else
        $params = $this->cleanPost($values);
        $this->setOption(CURLOPT_COOKIE, $params);
        }

	/*
	@LOGIN REQUEST
	sets login option
	If is not setted , return false
	*/
        public function setHttpLogin($username = '', $password = '') {
        $this->setOption(CURLOPT_USERPWD, $username.':'.$password);
        }
        /*
	@PROXY SETINGS
	sets proxy settings withouth username

	*/

        public function setProxy($url,$port = 80){
	$this->setOption(CURLOPT_HTTPPROXYTUNNEL, TRUE);
        $this->setOption(CURLOPT_PROXY, $url.':'.$port);
	}

	/*
	@PROXY LOGIN SETINGS
	sets proxy login settings calls onley if is proxy setted

	*/
	public function setProxyLogin($username = '', $password = '') {
        $this->setOption(CURLOPT_PROXYUSERPWD, $username.':'.$password);
        }
	/*
	@VALID OPTION CHECKER
	*/
	protected static function checkOptions($value, $validOptions)
        {
        if (!empty($validOptions)) {
            foreach ($value as $key=>$val) {

                if (!array_key_exists($key, $validOptions)) {
                    throw new CException(Yii::t('Curl', '{k} is not a valid option', array('{k}'=>$key)));
                }
                $type = gettype($val);
                if ((!is_array($validOptions[$key]['type']) && ($type != $validOptions[$key]['type'])) || (is_array($validOptions[$key]['type']) && !in_array($type, $validOptions[$key]['type']))) {
                        throw new CException(Yii::t('Curl', '{k} must be of type {t}',
                        array('{k}'=>$key,'{t}'=>$validOptions[$key]['type'])));
                }

                if (($type == 'array') && array_key_exists('elements', $validOptions[$key])) {
                        self::checkOptions($val, $validOptions[$key]['elements']);
                    }
                }
            }
        }
        /*
	@DEFAULTS
	*/
        protected function defaults(){
            !isset($this->options['timeout'])  ?  $this->setOption(CURLOPT_TIMEOUT,30) : $this->setOption(CURLOPT_TIMEOUT,$this->options['timeout']);
            isset($this->options['setOptions'][CURLOPT_HEADER]) ? $this->setOption(CURLOPT_HEADER,$this->options['setOptions'][CURLOPT_HEADER]) : $this->setOption(CURLOPT_HEADER,FALSE);
            isset($this->options['setOptions'][CURLOPT_RETURNTRANSFER]) ? $this->setOption(CURLOPT_RETURNTRANSFER,$this->options['setOptions'][CURLOPT_RETURNTRANSFER]) : $this->setOption(CURLOPT_RETURNTRANSFER,TRUE);
	    isset($this->options['setOptions'][CURLOPT_FOLLOWLOCATION]) ? $this->setOption(CURLOPT_FOLLOWLOCATIO,$this->options['setOptions'][CURLOPT_FOLLOWLOCATION]) : $this->setOption(CURLOPT_FOLLOWLOCATION,TRUE);
            isset($this->options['setOptions'][CURLOPT_FAILONERROR]) ? $this->setOption(CURLOPT_FAILONERROR,$this->options['setOptions'][CURLOPT_FAILONERROR]) : $this->setOption(CURLOPT_FAILONERROR,TRUE);
        }

	/*
	@MAIN FUNCTION FOR PROCESSING CURL
	*/
	public function run($url,$GET = TRUE,$POSTSTRING = array()){
                $this->setUrl($url);
                if( !$this->url )
		throw new CException( Yii::t('Curl', 'You must set Url.') );
                $this->ch = curl_init();
		self::checkOptions($this->options,$this->validOptions);

                if($GET == TRUE){
                $this->setOption(CURLOPT_URL,$this->url);
                $this->defaults();
                }else if($GET == FALSE){
                $this->setOption(CURLOPT_URL,$this->url);
                $this->defaults();
                $this->setOption(CURLOPT_POST, TRUE);
                $this->setOption(CURLOPT_POSTFIELDS, $this->cleanPost($POSTSTRING));
                }

                if(isset($this->options['setOptions']))
		foreach($this->options['setOptions'] as $k=>$v)
		$this->setOption($k,$v);

                isset($this->options['login']) ?  $this->setHttpLogin($this->options['login']['username'],$this->options['login']['password']) :  null;
                isset($this->options['proxy']) ? $this->setProxy($this->options['proxy']['url'],$this->options['proxy']['port']) : null;

                if(isset($this->options['proxylogin'])){
		if(!isset($this->options['proxy']))
		throw new CException( Yii::t('Curl', 'If you use "proxylogin", you must define "proxy" with arrays.') );
		else
		$this->setProxyLogin($this->options['login']['username'],$this->options['login']['password']);
		}

                $return = curl_exec($this->ch);

		// Request failed
		if($return === FALSE)
		{
		$this->error_code = curl_errno($this->ch);
		$this->error_string = curl_error($this->ch);
		curl_close($this->ch);
		echo "Error code: ".$this->error_code."<br />";
		echo "Error string: ".$this->error_string;
		// Request successful
		} else {
		$this->info = curl_getinfo($this->ch);
	        curl_close($this->ch);
		return $return;
		}




      }









	/**
	 * Arrays are walked through using the key as a the name.  Arrays
	 * of Arrays are emitted as repeated fields consistent with such things
	 * as checkboxes.
	 * @desc Return data as a post string.
	 * @param mixed by reference data to be written.
	 * @param string [optional] name of the datum.
	 */

	protected function &cleanPost(&$string, $name = NULL)
	  {
		$thePostString = '' ;
		$thePrefix = $name ;

		if (is_array($string))
		{
		foreach($string as $k => $v)
		  {
			if ($thePrefix === NULL)
                    {
			  $thePostString .= '&' . self::cleanPost($v, $k) ;
                    }
		else
			{
			  $thePostString .= '&' . self::cleanPost($v, $thePrefix . '[' . $k . ']') ;
			}
                }

		}
		else
		{
		  $thePostString .= '&' . urlencode((string)$thePrefix) . '=' . urlencode($string) ;
		}

		$r =& substr($thePostString, 1) ;

		return $r ;
	  }

}//end of method