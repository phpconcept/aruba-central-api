<?php
/**-----------------------------------------------------------------------------
 * License GNU/GPL - October 2024
 * Vincent Blavet - vincent@phpconcept.net
 * http://www.phpconcept.net
 * -----------------------------------------------------------------------------
 */

  echo "Starting --- ".date('Y-m-d H:i:s', time())."<br><br>";

  require_once(dirname(__FILE__).'/../class/aca_central.class.php');


  $v_central = new AcaCentral();
  $v_central->init(['log_level'=>'error,debug',
                    'pin_code'=>'my_secret_key', 
                    'save_mode'=>'file',
                    'save_filename'=>dirname(__FILE__).'/aca_central_tokens.json']);
  
  /*
  $v_central->init(['pin_code'=>'my_secret_key', 
                    'save_mode'=>'file',
                    'save_filename'=>dirname(__FILE__).'/aca_central_tokens.json',
                    'refresh_token'=>'eRjRlBwrFVXZnuX0c0nxIjYBYYczB551',
                    'client_id'=>'1Yc3XGMgo2skhg87VjEg2AGIVOG4Hrbz',
                    'client_secret'=>'MO84ui3nGaq89uZQRhjWGdujFW9HcImD']);
  */
  
  //$v_central->authSetRefreshToken('qAyNBdFDTzFIFxGpdnis5eDeiOQHBVDh','1Yc3XGMgo2skhg87VjEg2AGIVOG4Hrbz','MO84ui3nGaq89uZQRhjWGdujFW9HcImD');  
  //$v_central->authSetAccessToken('TVZsbbtNvLY5VeriT1PM96POiB3hoZjV');
  
  echo "<pre>";
  $v_list = $v_central->centralApiGetClientsWireless(['fields'=>'name,ip_address']);

  //$v_list = $v_central->centralApiGetSwitches();

  //$v_list = $v_central->centralApiGet('monitoring/v1/clients/wireless', ['fields'=>'name,ip_address']);
  
  //$v_list = $v_central->centralApiGetMultiple('monitoring/v1/clients/wireless', 'clients', ['fields'=>'name,ip_address'], 2);
  //$v_list = $v_central->centralApiGetMultiple('monitoring/v1/clients/wireless', 'clients', ['fields'=>'name,ip_address']);
  
  echo print_r($v_list, true);
  echo "</pre>";
  
  echo "<pre>";
  echo "</pre>";
  
  //phpinfo();  
?>
