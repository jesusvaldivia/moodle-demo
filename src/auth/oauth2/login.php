<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Open ID authentication. This file is a simple login entry point for OAuth identity providers.
 *
 * @package auth_oauth2
 * @copyright 2017 Damyon Wiese
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */

require_once('../../config.php');


//ieduca
function zoom_sps($correo) {
    global $CFG,$DB,$USER;
    // Primero obtenemos el usuario
    $user = $DB->get_record('user', ['email' => $correo]);
    $habilitar=true; // Variable: Necesita Habilitar
    if (!empty($user)) { // fieldid de IDZOOM = 1 en CERTUS
      $data_zoom_id = $DB->get_record('user_info_data', array ('userid' => $user->id, 'fieldid' => 1 ));
      if (!empty($data_zoom_id)) {
        if (!empty($data_zoom_id->data)) {
          $habilitar = false; // No necesito hacerle update/insert
        } else { // el registro esta vacio
          // Necesitara un Update
        }
      } else {
        // Necesitara un Insert
      }

      if ($habilitar) { // Necesito hacer insert o update
        $user = $DB->get_record('user', ['email' => $correo]);
        $mail = $correo;
        $curl = curl_init();
        curl_setopt_array($curl, array(
          CURLOPT_URL => 'https://api.zoom.us/v2/users',
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'POST',
          CURLOPT_POSTFIELDS =>'{
          "action": "create",
          "user_info": {
              "email": "'.$mail.'",
              "type": 1,
              "first_name": "'.$user->firstname.'",
              "last_name": "'.$user->lastname.'"
          }
          }',
          CURLOPT_HTTPHEADER => array(
              'Content-Type: application/json',
              'Authorization: Bearer eyJhbGciOiJIUzI1NiJ9.eyJhdWQiOm51bGwsImlzcyI6IlNGSHduTDRHVDNlOWo2bG50NHFpUEEiLCJleHAiOjE2NDEwMTMyMDAsImlhdCI6MTYyNzE2MDA4NH0.nceWAlfG7upUaxNhS6HNVCBodriDwL6F0cggI_IDzR0',
              'Cookie: _zm_lang=es-ES; _zm_mtk_guid=b57f6357b9af462bb617b35ba63d8716; _zm_csp_script_nonce=CxP4yoqARS6rGBCUiE2CaA; _zm_currency=USD; cred=DB59FDE0465E9ADA6EE133A36F4AB1C0'
          ),
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        $obj_decode = json_decode($response);
        if (!empty($obj_decode->{'id'})) { // Si Zoom creo el usuario y me devolvio un ID
            if (!empty($data_zoom_id)) { // Si ya tengo donde registrarlo
                if (empty($data_zoom_id->data)) {
                    $id_zoom_user = $obj_decode->{'id'};
                    $obj = (object)array('id' => $data_zoom_id->id, 'data' => $id_zoom_user );
                    $DB->update_record('user_info_data', $obj, $bulk=false);
                } else {
                  // Nada, ya está insertado $data_zoom_id->data
                }
            } else {
                $id_zoom_user = $obj_decode->{'id'};
                $obj = (object)array('userid' => $user->id, 'fieldid' => 1,'data' => $id_zoom_user, 'dataformat' => 0 );
                $obj->id = $DB->insert_record('user_info_data', $obj);
            }
        } else {
          // Fallo la creacion de la cuenta en Zoom
        }
        //return $response;
      } // Fin Habilitar
    } // Fin Obtener usuario (que en teoria siempre existe)
  }


$issuerid = required_param('id', PARAM_INT);
$wantsurl = new moodle_url(optional_param('wantsurl', '', PARAM_URL));

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/auth/oauth2/login.php', ['id' => $issuerid]));

require_sesskey();

if (!\auth_oauth2\api::is_enabled()) {
    throw new \moodle_exception('notenabled', 'auth_oauth2');
}

$issuer = new \core\oauth2\issuer($issuerid);

$returnparams = ['wantsurl' => $wantsurl, 'sesskey' => sesskey(), 'id' => $issuerid];
$returnurl = new moodle_url('/auth/oauth2/login.php', $returnparams);

$client = \core\oauth2\api::get_user_oauth_client($issuer, $returnurl);

if ($client) {
    if (!$client->is_logged_in()) {
        redirect($client->get_login_url());
    }

    $auth = new \auth_oauth2\auth();
	//ieduca
	if ($issuer->get('baseurl') === "https://accounts.google.com/" )
	{
		$user_ieduca = $client->get_userinfo();
		$user_ieduca_email = $user_ieduca['email'];
		zoom_sps(strtolower($user_ieduca_email));
	}
    //ieduca
    $auth->complete_login($client, $wantsurl);



} else {
    throw new moodle_exception('Could not get an OAuth client.');
}
