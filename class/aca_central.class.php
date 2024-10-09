<?php
/**-----------------------------------------------------------------------------
 * License GNU/GPL - October 2024
 * Vincent Blavet - vincent@phpconcept.net
 * http://www.phpconcept.net
 * -----------------------------------------------------------------------------
 */
  
  define('ACA_CENTRAL_API_URL', 'https://internal-apigw.central.arubanetworks.com/');
  //define('ACA_CENTRAL_API_URL', 'https://apigw-eucentral3.central.arubanetworks.com/');

  define('ACA_AUTH_SAVE_DEFAULT_FILENAME', 'aca_tokens.json');
  

  /**---------------------------------------------------------------------------
   * Class : AcaCentral
   * Description :
   *   
   * ---------------------------------------------------------------------------
   */
  class AcaCentral 
  {
    /* 
      $this->conf['api_url'] = '';
      $this->conf['auth_loaded'] = false;
      $this->conf['auth_save_mode'] = '';  // '', 'file', 'callback'
      $this->conf['auth_save_filename'] = '';  // filename if mode='file'
      $this->conf['auth_save_encrypt'] = false;
      $this->conf['auth_save_callback_fct'] = '';  // link to callback function
     */
    private $conf = array();

    /* 
      $this->auth['access_token'] = '';
      $this->auth['access_token_expire_ts'] = 0;
      $this->auth['refresh_token'] = '';
      $this->auth['refresh_token_ts'] = 0;
      $this->auth['client_id'] = '';
      $this->auth['client_secret'] = '';
     */
    private $auth = array();

    /* 
      $this->session_data['auth_pin_code'] = '';
      $this->session_data['log_level'] = 'off';   // 'off', 'info', 'warning', 'error', 'debug'
     */
    private $session_data = array();

    /**-------------------------------------------------------------------------
     * Method : __construct()
     * Description :
     *   
     * -------------------------------------------------------------------------
     */  
    function __construct()
    {
      // ----- Default conf attributes
      $this->conf['api_url'] = '';
      $this->conf['auth_save_mode'] = '';
      $this->conf['auth_save_filename'] = '';
      $this->conf['auth_save_encrypt'] = false;
      $this->conf['auth_save_callback_fct'] = '';

      // ----- Default auth attributes
      $this->conf['auth_loaded'] = false;
      $this->auth['access_token'] = '';
      $this->auth['access_token_expire_ts'] = 0;
      $this->auth['refresh_token'] = '';
      $this->auth['refresh_token_ts'] = 0;
      $this->auth['client_id'] = '';
      $this->auth['client_secret'] = '';
      
      // ----- Default session data
      $this->session_data['auth_pin_code'] = '';
      $this->session_data['log_level'] = 'off';
      
    }
    /* -- function -----------------------------------------------------------*/    
    
    /**-------------------------------------------------------------------------
     * Method : 
     * Description :
     *   Available attributes :
     *   log_level : 'off', 'info', 'warning', 'error', 'debug', 'all' or combinaison: 'error,debug'
     *   pin_code
     *   save_mode : '', 'file', 'callback'
     *   save_filename
     *   save_callback_fct
     *   refresh_token
     *   client_id
     *   client_secret
     * -------------------------------------------------------------------------
     */  
    function init($p_attributes=array())
    {
      // ----- Look for log_level
//      if (isset($p_attributes['log_level']) && in_array($p_attributes['log_level'], ['off','info','warning','error','debug'])) {
      if (isset($p_attributes['log_level'])) {
        $this->session_data['log_level'] = $p_attributes['log_level'];
      }
      
      $v_change_flag = false;
      
      // ----- Look for info to load auth saved data (if any available)
      if (isset($p_attributes['pin_code'])) {
        $this->session_data['auth_pin_code'] = $this->_agv($p_attributes, 'pin_code');
        $this->conf['auth_save_encrypt'] = ($this->session_data['auth_pin_code'] != '');
        $v_change_flag = true;
      }
      
      if (isset($p_attributes['save_mode'])) {
        $v_save_mode = $this->_agv($p_attributes, 'save_mode');
        if (($v_save_mode == '') || in_array($v_save_mode, ['file','callback'])) {
          $this->conf['auth_save_mode'] = $v_save_mode;
          $v_change_flag = true;
        }
      }
      
      if (isset($p_attributes['save_filename'])) {
        $this->conf['auth_save_filename'] = $this->_agv($p_attributes, 'save_filename');
        $v_change_flag = true;
      }
      
      // TBC : should check that callback_fct is valid ? here or elsewhere ?
      if (isset($p_attributes['save_callback_fct'])) {
        $this->conf['auth_save_callback_fct'] = $this->_agv($p_attributes, 'save_callback_fct');
        $v_change_flag = true;
      }
      
      // ----- Load auth data if needed data
      if ($v_change_flag) {
        $this->authLoadAttributes(true);
      }
      
      // ----- Look for token refresh data
      $v_change_flag = false;
      $v_refresh_token = '';
      $v_client_id='';
      $v_client_secret='';
      if (isset($p_attributes['refresh_token'])) {
        $v_change_flag = true;
        $v_refresh_token = $this->_agv($p_attributes, 'refresh_token');
      }
      if (isset($p_attributes['client_id'])) {
        $v_change_flag = true;
        $v_client_id = $this->_agv($p_attributes, 'client_id');
      }
      if (isset($p_attributes['client_secret'])) {
        $v_change_flag = true;
        $v_client_secret = $this->_agv($p_attributes, 'client_secret');
      }
      
      if ($v_change_flag) {
        $this->authSetRefreshToken($v_refresh_token, $v_client_id, $v_client_secret);
      }
    }
    /* -- function -----------------------------------------------------------*/    

    
    /**-------------------------------------------------------------------------
     * Method : log()
     * Description :
     *   
     * -------------------------------------------------------------------------
     */  
    function log($p_level, $p_text)
    {
      if ($this->session_data['log_level'] == 'off') return;
      
      if (   ($this->session_data['log_level'] == 'all')
          || (strpos($this->session_data['log_level'], $p_level) !== false)) {
        $v_display = true;
      }
      else {
        $v_display = false;
      }

      if ($v_display) {
        if (is_array($p_text)) {
          $p_text = print_r($p_text, true);
        }
        echo "[".$p_level."] : ".$p_text."<br>";
      }
    }
    /* -- function -----------------------------------------------------------*/    

    
    /**-------------------------------------------------------------------------
     * Method : 
     * Description :
     *   
     * -------------------------------------------------------------------------
     */  
    function authSetRefreshToken($p_refresh_token, $p_client_id='', $p_client_secret='')
    {
      $this->auth['refresh_token'] = $p_refresh_token;
      $this->auth['refresh_token_ts'] = 0;
      if ($p_client_id != '') {
        $this->auth['client_id'] = $p_client_id;
      }
      if ($p_client_secret != '') {
        $this->auth['client_secret'] = $p_client_secret;
      }
      
      $this->authSaveAttributes();
    }
    /* -- function -----------------------------------------------------------*/    

    
    /**-------------------------------------------------------------------------
     * Method : 
     * Description :
     *   
     * -------------------------------------------------------------------------
     */  
    function authSetAccessToken($p_access_token, $p_access_token_expire_ts=0)
    {
      $this->auth['access_token'] = $p_access_token;
      $this->auth['access_token_expire_ts'] = $p_access_token_expire_ts;
    }
    /* -- function -----------------------------------------------------------*/    

    
    /**-------------------------------------------------------------------------
     * Method : 
     * Description :
     *   
     * -------------------------------------------------------------------------
     */  
    function authLoadAttributes($p_force=false)
    {
      if ($this->conf['auth_loaded'] && !$p_force) {
        return(true);
      }
      
      // ----- Look for auth save mode
      if ($this->conf['auth_save_mode'] == 'file') {
        if ($this->conf['auth_save_filename'] != '') {
          $v_filename = $this->conf['auth_save_filename'];
        }
        else {
          $v_filename = ACA_AUTH_SAVE_DEFAULT_FILENAME;
        }
        
        if (($fd = @fopen($v_filename, 'r')) === false) {
          $this->log('debug', "Fail to open filename : '".$v_filename."' in read mode");
          return(false);
        }
        
        if (($v_auth_text = fread($fd, filesize($v_filename))) === false) {
          return(false);
        }

        if ($this->conf['auth_save_encrypt']) {
          $v_auth_text = $this->_tool_decrypt($v_auth_text, $this->session_data['auth_pin_code']);
        }
                   
        // ----- Check that JSON is valid
        try {
          $v_auth_data = json_decode($v_auth_text, true);
          if (json_last_error() !== JSON_ERROR_NONE) {
            $this->log('error', 'authLoadAttributes(): JSON bad format');
            return(false);
          }
        }
        catch (Exception $e) {
          $this->log('error', 'authLoadAttributes(): JSON decode error');
          return(false);
        }

        $this->log('debug', 'Auth JSON : '.print_r($v_auth_data, true));
        
        $this->auth = $v_auth_data;
      }
      
      else if ($this->conf['auth_save_mode'] == 'callback') {
      }
      
      else {
      }
      
      return(true);
    }
    /* -- function -----------------------------------------------------------*/    

    
    /**-------------------------------------------------------------------------
     * Method : 
     * Description :
     *   
     * -------------------------------------------------------------------------
     */  
    function authSaveAttributes()
    {
      // ----- Look for auth save mode
      if ($this->conf['auth_save_mode'] == 'file') {
        if ($this->conf['auth_save_filename'] != '') {
          $v_filename = $this->conf['auth_save_filename'];
        }
        else {
          $v_filename = ACA_AUTH_SAVE_DEFAULT_FILENAME;
        }
        
        if (($fd = @fopen($v_filename, 'w')) === false) {
          $this->log('debug', "Fail to open filename : '".$v_filename."' in write mode");
          return(false);
        }
        
        $v_auth_text = json_encode($this->auth);
        
        if ($this->conf['auth_save_encrypt']) {
          $v_auth_text = $this->_tool_encrypt($v_auth_text, $this->session_data['auth_pin_code']);
        }
        
        fwrite($fd, $v_auth_text);
        fclose($fd);
      }
      
      else if ($this->conf['auth_save_mode'] == 'callback') {
      }
      
      else {
      }
      
      return(true);
    }
    /* -- function -----------------------------------------------------------*/    

    
    /**-------------------------------------------------------------------------
     * Method : 
     * Description :
     *   
     * -------------------------------------------------------------------------
     */  
    function authGetAccessToken($p_force=false)
    {
      $v_access_token = $this->_agv($this->auth, 'access_token');
      $v_access_token_expire_ts = $this->_agv($this->auth, 'access_token_expire_ts');
      //$v_access_token_expire_ts = $this->auth['access_token_expire_ts'];
      
      $this->log('debug', "access_token: '".$v_access_token."'");
      $this->log('debug', "access_token expire timestamp: ".date('Y-m-d H:i:s', $v_access_token_expire_ts)."");

      // ----- Check if active token
      // On ne force pas le refresh
      // et il n'est pas nul
      // et le expire_ts est à zéro (ce qui veut dire fixé manuellement)
      //    ou il est supérieur au temps actuel d'au moins 30 secondes.
      if (   (!$p_force)
          && ($v_access_token != '')
          && (($v_access_token_expire_ts == 0)
              || ($v_access_token_expire_ts > (time()+30))) ) {
        $this->log('debug', "access_token is valid.");
        return($v_access_token);
      }
      
      $this->log('debug', "Look for access_token refresh :");
      
      // ----- Load 

      $v_client_id = $this->_agv($this->auth, 'client_id');
      $v_client_secret = $this->_agv($this->auth, 'client_secret');
      $v_refresh_token = $this->_agv($this->auth, 'refresh_token');
      $v_refresh_token_ts = $this->_agv($this->auth, 'refresh_token_ts');
      
      $this->log('debug', "client_id: ".$v_client_id."");
      $this->log('debug', "client_secret: ".$v_client_secret."");
      $this->log('debug', "refresh_token: ".$v_refresh_token."");
      $this->log('debug', "refresh_token_ts: ".date('Y-m-d H:i:s', $v_refresh_token_ts)."");

      // ----- Check if active token
      if (   (!$p_force)
          && ($v_access_token != '')
          && ($v_refresh_token_ts != 0)
          && ($v_refresh_token_ts > (time()+30)) ) {
        $this->log('debug', " -> access_token is not expired");
        return($this->access_token);
      }
      
      $this->log('debug',  " -> access_token need refresh ...");

      // ----- Get new auth file
      $v_access_token = $this->authRefreshAllTokens($v_refresh_token, $v_client_id, $v_client_secret);
      
      if ($v_access_token == '') {
        return('');
      }

      // ----- Return value      
      return($v_access_token);
    }
    /* -- function -----------------------------------------------------------*/    
    
    /**-------------------------------------------------------------------------
     * Method : authRefreshAllTokens()
     * Description :
     *   
     * From Central documentation : 
        (https://www.arubanetworks.com/techdocs/central/latest/content/nms/api/access_tokn_bstprcts.htm?Highlight=refresh%20token)
        The access token generated must be stored safely.
        The access token generated must be used to execute all REST API call. To view the list of APIs managed through HPE Aruba Networking Central, see Viewing Swagger Interface.
        You must use the same access token to execute an API call without generating new access tokens multiple times. For every API call you must not create new access token.
        You must save the latest refresh token generated. Once the validity of the access token expires, renew the access token using the saved refresh token.
        You must refresh the access token when it is invalid or at least once within 15 days so that HPE Aruba Networking Central can honor refreshing the token and does not revoke it.
     * -------------------------------------------------------------------------
     */  
    function authRefreshAllTokens($p_refresh_token, $p_client_id, $p_client_secret)
    {   
      $v_result = '';
      
      $v_refresh_token = $p_refresh_token;
      $v_client_id = $p_client_id;
      $v_client_secret = $p_client_secret;
      
      // https://internal-apigw.central.arubanetworks.com/oauth2/token?client_id=xx&grant_type=refresh_token&refresh_token=xxx&client_secret=xxx      

      //$v_url = "https://apigw-eucentral3.central.arubanetworks.com/";
      $v_url = $this->_agv($this->conf, 'api_url');
      if ($v_url == '') $v_url = ACA_CENTRAL_API_URL;
      
      // ----- CURL request
      //  $p_request['url'] : mandatory
      //  $p_request['access_token'] : bearer token for user authentication. optional
      //  $p_request['method'] : HTTP Method. Default is "GET". optional
      //  $p_request['login'] & $p_request['password'] : basic HTTP auth. optional.
      $p_request = array();
      $p_request['url'] = $v_url."/oauth2/token?grant_type=refresh_token&client_id=".$v_client_id."&client_secret=".$v_client_secret."&refresh_token=".$v_refresh_token."";
      $p_request['method'] = "POST";

      // ----- Prepare POST data 
      $p_post_array = array();
      /*
      $p_post_array['id'] = '5659313586569216';
        */
                                   
      $p_content=array();
      $p_header =array();

      if ($this->_request_curl($p_request, $p_post_array, $p_content, $p_header) !== 1) {
        $v_result = '';
        return($v_result);
      }
      
      $this->log('debug',  "authtext new: ".$p_content."");
      
      // ----- Check that JSON is valid
      try {
        $v_json_data = json_decode($p_content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
          $this->log('error', 'authRefreshAllTokens(): JSON bad format');
          return('');
        }
      }
      catch (Exception $e) {
        $this->log('error', 'authRefreshAllTokens(): JSON decode error');
        return('');
      }
      
      /*
        Expected response format :
        {"refresh_token":"xxx","token_type":"bearer","access_token":"xxx","expires_in":7200}
      */
      if (!isset($v_json_data['access_token']) ||
          !isset($v_json_data['refresh_token']) ||
          !isset($v_json_data['token_type']) ||
          !isset($v_json_data['expires_in'])) {
          $this->log('error', 'authRefreshAllTokens(): Missing arguments in JSON');
          return('');
      }
      
      // ----- Set values
      $this->auth['access_token'] = $v_json_data['access_token'];
      $this->auth['access_token_expire_ts'] = time()+7200;
      $this->auth['refresh_token'] = $v_json_data['refresh_token'];
      $this->auth['refresh_token_ts'] = time()+$v_json_data['expires_in'];

      $this->log('debug', "access_token: ".$this->auth['access_token']."");
      $this->log('debug', "refresh_token: ".$this->auth['refresh_token']."");
      $this->log('debug', "refresh_token_ts: ".date('Y-m-d H:i:s', $this->auth['refresh_token_ts'])."");
      
      $this->authSaveAttributes();

      return($this->auth['access_token']);
    }
    /* -- function -----------------------------------------------------------*/    

    
    /**-------------------------------------------------------------------------
     * Method : 
     * Description :
     *   
     * -------------------------------------------------------------------------
     */  
    function centralApiGet($p_url, $p_args=array())
    {
    /*
      // ----- Look for multiple gets needed
      if ($this->_agv($p_args, '_splitt_large_list') != '') {
        return($this->centralApiGetMultiple($p_url, $p_args));
      }
      */
              
      //$v_url = "https://eu-apigw.central.arubanetworks.com/";
      //$v_url = "https://apigw-eucentral3.central.arubanetworks.com/";
      $v_url = $this->_agv($this->conf, 'api_url');
      if ($v_url == '') $v_url = ACA_CENTRAL_API_URL;

      $p_request = array();
      $p_request['method'] = 'GET';
      $p_request['access_token'] = $this->authGetAccessToken();
      
      $v_url_args = '';
      foreach ($p_args as $v_key => $v_arg) {
        if ($v_url_args != '') {
          $v_url_args .= '&';
        }
        $v_url_args .= $v_key.'='.urlencode($v_arg);
      }

      $p_request['url'] = $v_url.'/'.$p_url;
      if ($v_url_args != '') {
        $p_request['url'] .= '?'.$v_url_args;
      }
      
      $this->log('debug', 'API url :'.$p_request['url']);

      $p_post_array = array();     
      $p_content = array();
      $p_header = array();
            
      if ($this->_request_curl_json($p_request, $p_post_array, $p_content, $p_header) != 1) {
        $this->log('error', 'CURL request error.');
        return(null);
      }
      
      if (isset($p_content['error'])) {
        $this->log('error', 'API request error : '.print_r($p_content, true));
        return(null);
      }
            
      return($p_content);
    }
    /* -- function -----------------------------------------------------------*/    


    /**-------------------------------------------------------------------------
     * Method : 
     * Description :
     *   
     * -------------------------------------------------------------------------
     */  
    function centralApiGetMultiple($p_url, $p_list_keyword='', $p_args=null, $p_limit=0)
    {
      if ($p_list_keyword == '') {
        return(null);
      }
      
      $v_list = array();
      
      //$v_url = "https://eu-apigw.central.arubanetworks.com/";
      //$v_url = "https://apigw-eucentral3.central.arubanetworks.com/";
      $v_url = $this->_agv($this->conf, 'api_url');
      if ($v_url == '') $v_url = ACA_CENTRAL_API_URL;

      $p_request = array();
      
      $p_request['method'] = 'GET';
      //ob_start();
      $p_request['access_token'] = $this->authGetAccessToken();
      //$v_str = ob_get_contents();
      //ob_end_clean();

      $v_url_args = '';
      foreach ($p_args as $v_key => $v_arg) {
        if (($v_key == 'limit') || ($v_key == 'offset')) continue;
        
        if ($v_url_args != '') {
          $v_url_args .= '&';
        }
        $v_url_args .= $v_key.'='.urlencode($v_arg);
      }
      
      $v_offset = 0;
      if ($p_limit <= 0) {
        $v_limit = 200;
      }
      else if ($p_limit >= 1000) {
        $v_limit = 1000;
      }
      else {
        $v_limit = $p_limit;
      }
      
      $v_stop = false;
      $v_save_flag = 0;
      
      while (!$v_stop) {
        $v_save_flag++;
        if ($v_save_flag > 15) {
          $this->log('error', 'centralApiGetMultiple() : infinite loop, abort');
          return(null);
        }
                
        $p_request['url'] = $v_url.'/'.$p_url.'?limit='.$v_limit.'&offset='.$v_offset;
        if ($v_url_args != '') {
          $p_request['url'] .= '&'.$v_url_args;
        }
        
        $this->log('debug', 'API url :'.$p_request['url']);
  
        $p_post_array = array();     
        $p_content = array();
        $p_header = array();
              
        $this->_request_curl_json($p_request, $p_post_array, $p_content, $p_header);
        
        if (isset($p_content['error'])) {
          $this->log('error', 'API request error : '.print_r($p_content, true));
          return(null);
        }
        
        if (!isset($p_content[$p_list_keyword])) {
          $this->log('error', 'invalid list keyword : '.$p_list_keyword);
          return(null);
        }
        
        $v_list = array_merge($v_list, $p_content[$p_list_keyword]);
                
        if ($p_content['count'] == $v_limit) {
          $v_offset += $v_limit;
        }
        else {
          $v_stop = true;
        }
        

      }
      
      return($v_list);
    }
    /* -- function -----------------------------------------------------------*/    
            

    /**-------------------------------------------------------------------------
     * Method : 
     * Description :
     *   
     * -------------------------------------------------------------------------
     */  
    function centralApiGetSwitches($p_args=array())
    {
      $v_list = $this->centralApiGetMultiple('monitoring/v1/switches', 'switches', $p_args);
      return($v_list);
    }
    /* -- function -----------------------------------------------------------*/    
            

    /**-------------------------------------------------------------------------
     * Method : 
     * Description :
     *   
     * -------------------------------------------------------------------------
     */  
    function centralApiGetClientsWireless($p_args=array())
    {
      $v_list = $this->centralApiGetMultiple('monitoring/v1/clients/wireless', 'clients', $p_args);
      return($v_list);
    }
    /* -- function -----------------------------------------------------------*/    

    
    /**-------------------------------------------------------------------------
     * Method : 
     * Description :
     *   
     * -------------------------------------------------------------------------
     */  
    function _request_curl_json($p_request, $p_post_array, &$p_content, &$p_header)
    {
      $this->_request_curl($p_request, $p_post_array, $p_content, $p_header);
      
      // ----- Get result in an array
      try {
        $p_content = json_decode($p_content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
          $this->log('error', '_request_curl_json(): JSON bad format');
          return(0);
        }
      }
      catch (Exception $e) {
        $this->log('error', '_request_curl_json(): JSON decode error');
        return(0);
      }

      return(1);
    }
    /* -- function -----------------------------------------------------------*/    
    
    /**-------------------------------------------------------------------------
     * Method : 
     * Description :
     *   
     * -------------------------------------------------------------------------
     */  
    function _request_curl($p_request, $p_post_array, &$p_content, &$p_header)
    {
      $v_result = 1;

      $p_content = array();
      $p_header = array();

      // ----- Create URL
      $v_url = $p_request['url'];

      // ----- Init CURL
      $ch = curl_init();

      // ----- Custom HTTP Header
      $header = array();

      if (!isset($p_request['method'])) {
        curl_setopt($ch, CURLOPT_POST, false);
      }
      else if ($p_request['method'] == 'GET') {
        curl_setopt($ch, CURLOPT_POST, false);
      }
      else if ($p_request['method'] == 'POST') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');

        // ----- Create the JSON from the array
        $p_post_json = json_encode($p_post_array);

        $header[] = "Content-Type: application/json";
        $header[] = 'Content-Length: '.strlen($p_post_json);

        curl_setopt($ch, CURLOPT_POSTFIELDS, $p_post_json);
      }
      else {
        // TBC : Should not be used ... ?
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $p_request['method']);
      }

      if (isset($p_request['access_token'])) {
        $header[] = "Authorization: Bearer ".$p_request['access_token'];
      }

      curl_setopt($ch, CURLOPT_HEADER, true);
      curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

      curl_setopt($ch, CURLOPT_URL, $v_url);

      // ----- Look for basic login/password auth
      if (   (isset($p_request['login']))
          && (isset($p_request['password']))
          && ($p_request['login']!='')
          && ($p_request['password']!='')) {
        //login:pass ~ gets base64 encoded by curl
        curl_setopt($ch, CURLOPT_USERPWD, $p_request['login'].":".$p_request['password']);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
      }

      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

      // ----- True permet de renvoyer le resultat dans un string
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

      // ----- errors reporting
      curl_setopt($ch, CURLOPT_VERBOSE, false);
      //curl_setopt($ch, CURLOPT_PROGRESS, true);
      //curl_setopt($ch, CURLOPT_MUTE, 0);

      // ----- Execute curl
      $v_response_content = curl_exec($ch);
      if ($v_response_content === false) {
        // ----- No content in response
        $v_response_content = '';
      }

/*
      echo '<br><br><br><br><br><br><br><br>Content:<pre>';
      var_dump($v_response_content);
      echo '</pre>';
*/

      // ----- Manage curl errors
      $cErr = curl_errno($ch);
      if ($cErr != '') {
        $err = 'cURL ERROR: '.curl_error($ch).' (error number '.$cErr.')<br>';
        /*
        foreach(curl_getinfo($ch) as $k => $v){
            $err .= "$k: $v<br>";
        }
        */
        $this->log('error', $err);
        //curl_close($ch);
        $v_result = 0;
        return($v_result);
      }


      // ----- Get curl info
      $v_response_info = curl_getinfo($ch);
      //$v_response_info = curl_getinfo($ch, CURLINFO_HTTP_CODE);

      //$p_header['http_code'] = $v_response_info['http_code'];
      $p_header = $v_response_info;

/*
      echo '<br><br><br><br><br><br><br><br><pre>';
      var_dump($v_response_info);
      echo '</pre>';
*/

      // ----- Remove header from string
      $v_http_content = substr($v_response_content, $v_response_info['header_size']);

/*
      echo '<br><br><br><br><br><br><br><br>json:<pre>';
      var_dump($v_http_content);
      echo '</pre>';
*/

      curl_close($ch);

      // ----- Get result in an array
      $p_content = $v_http_content;

      $v_result = 1;
      return($v_result);
    }
    /* -- function -----------------------------------------------------------*/    
    

    /**-------------------------------------------------------------------------
     * Method : _tool_encrypt()
     * Description :
     *   
     * -------------------------------------------------------------------------
     */  
    function _tool_encrypt($p_plaintext, $p_key)
    {
      $v_cipher_algo = "aes-256-cbc";
      if (in_array($v_cipher_algo, openssl_get_cipher_methods())) {
        $v_salt = "8+6VDMcCvsRCXvQDvIX9Xg==";
        $v_ciphertext = openssl_encrypt($p_plaintext, $v_cipher_algo, $p_key, $options=0, base64_decode($v_salt));
        return($v_ciphertext);
      }  
      return("");  
    }
    /* -- function -----------------------------------------------------------*/    
      
    /**-------------------------------------------------------------------------
     * Method : _tool_decrypt()
     * Description :
     *   
     * -------------------------------------------------------------------------
     */  
    function _tool_decrypt($p_ciphertext, $p_key)
    {
      $v_cipher_algo = "aes-256-cbc";
      if (in_array($v_cipher_algo, openssl_get_cipher_methods())) {
        $v_salt = "8+6VDMcCvsRCXvQDvIX9Xg==";
        $v_plaintext = openssl_decrypt($p_ciphertext, $v_cipher_algo, $p_key, $options=0, base64_decode($v_salt));
        return($v_plaintext);
      }  
      return("");  
    }
    /* -- function -----------------------------------------------------------*/    
      
        
    /**-------------------------------------------------------------------------
     * Method : _agv()   - ArrayGetValue
     * Description :
     *   
     * -------------------------------------------------------------------------
     */  
    function _agv($p_item, $p_key)
    {
      if (is_array($p_item) && isset($p_item[$p_key])) {
        return($p_item[$p_key]);
      }
      return('');
    }
    /* -- function -----------------------------------------------------------*/    
    
       
  }
  /* -- class ----------------------------------------------------------------*/


?>
