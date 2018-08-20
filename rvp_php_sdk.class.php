<?php
/***************************************************************************************************************************/
/**
    BLUE DRAGON PHP SDK
    
    © Copyright 2018, Little Green Viper Software Development LLC.
    
    MIT License
    
    Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation
    files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy,
    modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the
    Software is furnished to do so, subject to the following conditions:

    The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

    THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
    OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
    IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF
    CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
    
    Little Green Viper Software Development: https://littlegreenviper.com
*/
defined( 'RVP_PHP_SDK_ACCESS' ) or die ( 'Cannot Execute Directly' );	// Makes sure that this file is in the correct context.
require_once(dirname(__FILE__).'/rvp_php_sdk_login.class.php');
require_once(dirname(__FILE__).'/rvp_php_sdk_user.class.php');
require_once(dirname(__FILE__).'/rvp_php_sdk_place.class.php');
require_once(dirname(__FILE__).'/rvp_php_sdk_thing.class.php');
require_once(dirname(__FILE__).'/lang/common.php');

define('__SDK_VERSION__', '1.0.0.0000');

/****************************************************************************************************************************/
/**
This is the central SDK class for connecting a PHP application to a BAOBAB server.

Upon instantiation, a valid server base URI and secret need to be provided.

Optionally, you can also provide login credentials.

This object should be used to manage all connections to the server.
 */
class RVP_PHP_SDK {
    protected   $_server_uri;           ///< This is the URI of the BAOBAB server.
    protected   $_sdk_lang;             ///< The language specified for this SDK instance. Default is "en" (English).
    protected   $_server_secret;        ///< This is the "server secret" that is specified by the admin of the BAOBAB server.
    protected   $_api_key;              ///< This is the current session API key.
    protected   $_login_timeout;        ///< The actual timeout value that was originally passed in.
    protected   $_login_time_limit;     ///< If >0, then this is the maximum time at which the current login is valid.
    protected   $_error;                ///< This is supposed to be NULL. However, if we have an error, it will contain an integer code.
    protected   $_my_login_info;        ///< This will contain any login information for the current login (NULL if not logged in).
    protected   $_my_user_info;         ///< This will contain any login information for the current login (NULL if not logged in).
    protected   $_last_response_code;   ///< This will contain any response code from the last cURL call.
    protected   $_available_plugins;    ///< This will be an array of string, with available plugins on the server.
    protected   $_localizations;        ///< An array of string, with the available localizations. The first will always be the default localization.
    protected   $_localized_errors;     ///< This will be an associative array, with the loaded RVP_Local_Error_* instances for display of error messages.
    
    /************************************************************************************************************************/    
    /*################################################ INTERNAL STATIC METHODS #############################################*/
    /************************************************************************************************************************/
    /***********************/
    /**
    \returns an associative array, with a matching table for short names to their "search_*" equivalents.
     */
    protected static function _get_string_match_table() {
        return  [
                    'name'              =>  'search_name',
                    'tag0'              =>  'search_tag0',
                    'tag1'              =>  'search_tag1',
                    'tag2'              =>  'search_tag2',
                    'tag3'              =>  'search_tag3',
                    'tag4'              =>  'search_tag4',
                    'tag5'              =>  'search_tag5',
                    'tag6'              =>  'search_tag6',
                    'tag7'              =>  'search_tag7',
                    'tag8'              =>  'search_tag8',
                    'tag9'              =>  'search_tag9',
                    'description'       =>  'search_description',
                    'surname'           =>  'search_surname',
                    'middle_name'       =>  'search_middle_name',
                    'given_name'        =>  'search_given_name',
                    'nickname'          =>  'search_nickname',
                    'prefix'            =>  'search_prefix',
                    'suffix'            =>  'search_suffix',
                    'venue'             =>  'search_venue',
                    'street'            =>  'search_street_address',
                    'street_address'    =>  'search_street_address',
                    'extra_information' =>  'search_extra_information',
                    'city'              =>  'search_town',
                    'town'              =>  'search_town',
                    'county'            =>  'search_county',
                    'state'             =>  'search_state',
                    'province'          =>  'search_state',
                    'postal_code'       =>  'search_postal_code',
                    'zip_code'          =>  'search_postal_code',
                    'nation'            =>  'search_nation',
                ];
    }
    
    /***********************/
    /**
    \returns the "search_*" equivalent of the short name that is presented.
     */
    protected static function _get_tag_match(   $in_string  ///< REQUIRED: The name of the field that is to be translated to a tag.
                                            ) {
        $ret = $in_string;
        $table = static::_get_string_match_table();
        
        if (isset($table[$ret]) && $table[$ret]) {
            $ret = $table[$ret];
        }
        
        return $ret;
    }

    /************************************************************************************************************************/    
    /*#################################################### INTERNAL METHODS ################################################*/
    /************************************************************************************************************************/
    /***********************/
    /**
    This is the function that is used by the SDK to make REST calls to the BAOBAB server.

    \returns the resulting transfer from the server, as a string of bytes.
     */
    protected function _call_REST_API(  $method,                /**< REQUIRED:  This is the method to call. It should be one of:
                                                                    - 'GET'     This is considered the default, but should be provided anyway, in order to ensure that the intent is clear.
                                                                    - 'POST'    This means that the resource needs to be created.
                                                                    - 'PUT'     This means that the resource is to be modified.
                                                                    - 'DELETE'  This means that the resource is to be deleted.
                                                                */
                                        $url_extension,         ///< REQIRED:   This is the query section of the URL for the call. It can be empty, but you probably won't get much, if it is.
                                        $data_input = NULL,     ///< OPTIONAL:  Default is NULL. This is an associative array, containing a collection of data, and a MIME type ("data" and "type") to data to be uploaded to the server, along with the URL. This will be Base64-encoded, so it is not necessary for it to be already encoded.
                                        $display_log = false    ///< OPTIONAL:  Default is false. If true, then the function will echo detailed debug information.
                                        ) {
    
        $method = strtoupper(trim($method));            // Make sure the method is always uppercase.
    
        // Initialize function local variables.
        $file = NULL;               // This will be a file handle, for uploads.
        $content_type = NULL;       // This is used to signal the content-type for uploaded files.
        $file_size = 0;             // This is the size, in bytes, of uploaded files.
        $temp_file_name = NULL;     // This is a temporary file that is used to hold files before they are sent to the server.
        $file_data = NULL;
        
        // If data is provided by the caller, we read it into a temporary location, and Base64-encode it.
        if ($data_input) {
            $file_data = base64_encode($data_input['data']);
        
            $temp_file_name = tempnam(sys_get_temp_dir(), 'RVP');
    
            $file = fopen($temp_file_name, 'w');
    
            fwrite($file, $file_data, strlen($file_data));
    
            fclose($file);
    
            $content_type = $data_input['type'].':base64';
            $file_size = filesize($temp_file_name);
    
            $file = fopen($temp_file_name, 'rb');
        }

        $curl = curl_init();                    // Initialize the cURL handle.
        
        // Different methods require different ways of dealing with any file that has been passed in.
        // The file is ignored for GET and DELETE.
        // We ask the server not to send us EXPECT (HTTP 100) calls for POST and PUT.
        switch ($method) {
            case "POST":
                curl_setopt($curl, CURLOPT_POST, true);
            
                // POST sends the file as a standard multipart/form-data item.
                if ($file) {
                    curl_setopt($curl, CURLOPT_SAFE_UPLOAD, true);
                    curl_setopt($curl, CURLOPT_HTTPHEADER, ['Expect:', 'Content-type: multipart/form-data']);
                    $post = Array('payload'=> curl_file_create($temp_file_name, $content_type));
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
                } else {
                    curl_setopt($curl, CURLOPT_HTTPHEADER, ['Expect:']);
                }
                break;
            
            case "PUT":
                curl_setopt($curl, CURLOPT_HTTPHEADER, ['Expect:']);
                curl_setopt($curl, CURLOPT_PUT, true);
            
                // PUT requires a direct inline file transfer.
                if ($file) {
                    curl_setopt($curl, CURLOPT_SAFE_UPLOAD, true);
                    curl_setopt($curl, CURLOPT_INFILE, $file);
                    curl_setopt($curl, CURLOPT_INFILESIZE, $file_size);
                }
                break;
            
            case "DELETE":
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
        }
    
        // Authentication. We provide the Server Secret and the API key here.
        if (isset($this->_server_secret) && isset($this->_api_key)) {
            curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($curl, CURLOPT_USERPWD, $this->_server_secret.':'.$this->_api_key);
            
            // This is because some servers may intercept the auth headers, so we also supply the credentials as URL query arguments.
            if (isset($url_extension) && (false !== strpos($url_extension, '?'))) {  // See iff we need to append, or begin a new query.
                $url_extension .= '&';
            } else {
                $url_extension .= '?';
            }
            
            $url_extension .= 'login_server_secret='.urlencode($this->_server_secret).'&login_api_key='.urlencode($this->_api_key);
        }

        curl_setopt($curl, CURLOPT_HEADER, false);          // Do not return any headers, please.
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);   // Please return to sender as a function response.
        curl_setopt($curl, CURLOPT_VERBOSE, false);         // Let's keep this thing simple.
        $url = $this->_server_uri.'/'.trim($url_extension, '/');
        curl_setopt($curl, CURLOPT_URL, $url);  // This is the URL we are calling.

        // This is if we want to see a display log (echoed directly).
        if (isset($display_log) && $display_log) {
            curl_setopt($curl, CURLOPT_HEADERFUNCTION, function ( $curl, $header_line ) {
                                                                                            echo "<pre>$header_line</pre>";
                                                                                            return strlen($header_line);
                                                                                        });
            echo('<div style="margin:1em">');
            echo("<h4>Sending REST $method CALL:</h4>");
            echo('<div>URL: <code>'.htmlspecialchars($url).'</code></div>');

            if ($this->_api_key) {
                echo('<div>API KEY:<pre>'.htmlspecialchars($this->_api_key).'</pre></div>');
            } else {
                echo('<div>NO API KEY</div>');
            }
        }
    
        $result = curl_exec($curl); // Do it to it.
    
        $this->_last_response_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);  // Bye, now.
    
        // More reportage.
        if (isset($display_log) && $display_log) {
            if (isset($file_data)) {
                echo('<p><strong>ADDITIONAL DATA SHA:</strong> <big><code>'.sha1($file_data).'</code></big></p>');
            }
            
            if (isset($httpCode) && $httpCode) {
                echo('<div>HTTP CODE:<code>'.htmlspecialchars($httpCode, true).'</code></div>');
            }
        
            if ((2048 * 1024) <= strlen($result)) { // Anything over 2MB gets spewed to a file.
                $integer = 1;
                $original_file_name = dirname(dirname(__FILE__)).'/text-dump-result';
                $file_name = $original_file_name.'.txt';
                while(file_exists($file_name)) {
                    $file_name = $original_file_name.'-'.$integer.'.txt';
                    $integer++;
                }
                $file_handle = fopen($file_name, 'w');
                fwrite($file_handle, $result);
                fclose($file_handle);
                echo('<div>RESULT SAVED TO FILE" '.$file_name.'.</div>');
            } else {
                echo('<div>RESULT:<pre>'.htmlspecialchars(print_r(chunk_split($result, 1024), true)).'</pre></div>');
            }
            echo("</div>");
        }
    
        // If we had a file open for transfer, we close it now.
        if ($file) {
            fclose($file);
        }

        return $result;
    }
    
    /***********************/
    /**
    \returns an array of unresolved objects (of any kind) that meet the ID requirements. NOTE: If the current user does not have permission to view resources, or the resources don't exist, they will not be returned.
     */
    protected function _decode_handlers (   $in_handlers    ///< REQUIRED: An associative array ('people' => array of int, 'places' => array of int, 'things' => array of int), with lists of IDs for various resources.
                                        ) {
        $ret = NULL;
        $plugin_list = [];
        if (isset($in_handlers->people) && is_array($in_handlers->people) && count($in_handlers->people)) {
            $plugin_list['people'] = $in_handlers->people;
        }
        if (isset($in_handlers->places) && is_array($in_handlers->places) && count($in_handlers->places)) {
            $plugin_list['places'] = $in_handlers->places;
        }
        if (isset($in_handlers->things) && is_array($in_handlers->things) && count($in_handlers->things)) {
            $plugin_list['things'] = $in_handlers->things;
        }
        
        if (isset($plugin_list) && is_array($plugin_list) && count($plugin_list)) {
            $ret = [];
            foreach ($plugin_list as $plugin => $list) {
                sort($list);
                foreach ($list as $id) {
                    $id = intval($id);
                
                    if (1 < $id) {
                        switch ($plugin) {
                            case 'people':
                                $new_object = new RVP_PHP_SDK_User($this, $id);
                                if (isset($new_object) && ($new_object instanceof RVP_PHP_SDK_User)) {
                                    $ret[] = $new_object;
                                } else {
                                    $this->set_error(_ERR_INTERNAL_ERR__);
                                    return NULL;
                                }
                                break;
                            
                            case 'places':
                                $new_object = new RVP_PHP_SDK_Place($this, $id);
                                if (isset($new_object) && ($new_object instanceof RVP_PHP_SDK_Place)) {
                                    $ret[] = $new_object;
                                } else {
                                    $this->set_error(_ERR_INTERNAL_ERR__);
                                    return NULL;
                                }
                                break;
                            
                            case 'things':
                                $new_object = new RVP_PHP_SDK_Thing($this, $id);
                                if (isset($new_object) && ($new_object instanceof RVP_PHP_SDK_Thing)) {
                                    $ret[] = $new_object;
                                } else {
                                    $this->set_error(_ERR_INTERNAL_ERR__);
                                    return NULL;
                                }
                                break;  
                        }
                    }
                }
            }
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    This loads the localization objects for this instance.
    
    \returns an array of string, with each of the localizations loaded.
     */
    protected function _load_localizations() {
        $locale_dir = dirname(__FILE__).'/lang';
        $locale_name_array = [];
        foreach (new DirectoryIterator($locale_dir) as $fileInfo) {
            if (($fileInfo->getExtension() === 'php') && ('index.php' != $fileInfo->getBasename()) && ('common.php' != $fileInfo->getBasename())) {
                $locale_name_array[] = $fileInfo->getBasename('.php');
            }
        }
        
        $this->_localizations = [];
        
        // Read each available file, and add it to our list.
        foreach ($locale_name_array as $locale) {
            if ($locale != $this->_sdk_lang) {
                $this->_localizations[] = $locale;
            }
        }
        
        sort($this->_localizations); // Simple alpha-sort.
        array_unshift($this->_localizations, $this->_sdk_lang); // Make sure the first one is always our default.
    }
    
    /***********************/
    /**
    \returns an associative array ('login' => login JSON object, 'user' => user JSON object), with the current information for any valid login.
     */
    protected function _get_my_info() {
        $ret = NULL;
        
        if ($this->is_logged_in()) {
            $info = $this->fetch_data('json/people/logins/my_info');
            if ($info) {
                $temp = json_decode($info);
                if (isset($temp) && isset($temp->people) && isset($temp->people->logins) && isset($temp->people->logins->my_info)) {
                    $login_info = $temp->people->logins->my_info;
                    if (isset($login_info->user_object_id) && (1 < intval($login_info->user_object_id))) {
                        $ret = ['login' => $login_info];
                        $info = $this->fetch_data('json/people/people/my_info');
                        if ($info) {
                            $temp = json_decode($info);
                            if (isset($temp) && isset($temp->people) && isset($temp->people->people) && isset($temp->people->people->my_info)) {
                                $user_info = $temp->people->people->my_info;
                                $ret['user'] = $user_info;
                            } else {
                                $this->set_error(_ERR_COMM_ERR__);
                            }
                        } else {
                            $this->set_error(_ERR_NO_RESULTS__);
                        }
                    } else {
                        $ret = ['login' => $login_info];
                    }
                }
            } else {
                $this->set_error(_ERR_NO_RESULTS__);
            }
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    \returns an array of string, with all the available plugins on the server.
     */
    protected function _get_plugins() {
        $ret = NULL;
        
        $info = $this->fetch_data('json/baseline');
        if ($info) {
            $temp = json_decode($info);
            if (isset($temp) && isset($temp->baseline) && isset($temp->baseline->plugins) && is_array($temp->baseline->plugins)) {
                return $temp->baseline->plugins;
            } else {
                $this->set_error(_ERR_COMM_ERR__);
            }
        } else {
            $this->set_error(_ERR_NO_RESULTS__);
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    This sets up the internal "my info" objects.
    
    \returns true, if the operation succeeded.
     */
    protected function _set_up_login_info() {
        if ($this->_api_key) {
            $this->_my_login_info = NULL;
            $this->_my_user_info = NULL;
        
            $info = $this->_get_my_info();
        
            if (!$this->get_error()) {
                if (isset($info['login'])) {
                    $this->_my_login_info = new RVP_PHP_SDK_Login($this, $info['login']->id, $info['login'], true);
            
                    if (!($this->_my_login_info instanceof RVP_PHP_SDK_Login)) {
                        $this->set_error(_ERR_INTERNAL_ERR__);
                        $this->logout();
                        $this->_api_key = NULL;
                        $this->_login_time_limit = -1;
                        $this->_my_login_info = NULL;
                        $this->_my_user_info = NULL;
                        return false;
                    }
                }
        
                if (isset($info['user'])) {
                    $this->_my_user_info = new RVP_PHP_SDK_User($this, $info['user']->id, $info['user'], true);
            
                    if (!($this->_my_user_info instanceof RVP_PHP_SDK_User)) {
                        $this->set_error(_ERR_INTERNAL_ERR__);
                        $this->logout();
                        $this->_api_key = NULL;
                        $this->_login_time_limit = -1;
                        $this->_my_login_info = NULL;
                        $this->_my_user_info = NULL;
                        return false;
                    }
                }
        
                return true;
            }
            
            $this->_api_key = NULL;
        }
        
        return false;
    }

    /************************************************************************************************************************/    
    /*#################################################### PUBLIC METHODS ##################################################*/
    /************************************************************************************************************************/
    /***********************/
    /**
    The basic constructor, which includes a validity test and a possible login.
     */
    function __construct(   $in_server_uri,         ///< REQUIRED: The URI of the BAOBAB Server
                            $in_server_secret,      ///< REQUIRED: The "server secret" for the BAOBAB Server.
                            $in_username = NULL,    ///< OPTIONAL: The Login Username, if we are doing an immediate login.
                            $in_password = NULL,    ///< OPTIONAL: The password, if we are doing an immediate login.
                            $in_login_timeout = 0   ///< OPTIONAL/REQUIRED: The login timeout, in seconds (integer). This must be provided if there is a login/password.
                        ) {
        $this->_server_uri = trim($in_server_uri, '/'); // This is the server's base URI.
        $this->_server_secret  = $in_server_secret;     // This is the secret that we need to provide with authentication.
        $this->_api_key = NULL;                         // If we log in, this will be non-NULL, and will contain the active API key for this instance.
        $this->_login_time_limit = -1;                  // No timeout to start.
        $this->_login_timeout = $in_login_timeout;      // Save this for posterity.
        $this->_my_login_info = NULL;                   // If we have logged in, we have the info for our login here.
        $this->_my_user_info = NULL;                    // If we have logged in, we have the info for our user (if available) here.
        
        $this->clear_error();                           // Start off clean.
        $this->set_lang('en');                          // Set to default (English). The implementor should call this after instantiation to change.
        
        $this->_available_plugins = $this->_get_plugins();
        
        if ($this->valid()) {
            if ($in_username && $in_password && $in_login_timeout) {
                $this->login($in_username, $in_password, $in_login_timeout);
            }
        }
    }
    
    /***********************/
    /**
    The basic destructor. We make sure that we log out.
     */
    function __destruct() {
        $this->logout();    // Don't bother checking for current login. Just call logout().
    }
    
    /***********************/
    /**
    This simply sets the SDK language, and also reloads the localizations.
     */
    function set_lang(  $in_lang    ///< REQUIRED: The lang code to set as the default for the SDK instance.
                    ) {
        $this->_sdk_lang = $in_lang;
        
        $this->_load_localizations();
    }
    
    /***********************/
    /**
    This executes a login to the server.
    
    Upon successful login, the "my_info" queries are made to the login, and, if applicable, the user.
    
    \returns true, if the login was successful.
     */
    function login( $in_username,           ///< REQUIRED: The Login Username
                    $in_password,           ///< REQUIRED: The password.
                    $in_login_timeout = -1  ///< OPTIONAL: If we have a known login timeout, we provide it here. Default is -1 (no timeout).
                    ) {
        if (!$this->_api_key && $this->valid()) {
            $this->_login_time_limit = (0 < $in_login_timeout) ? (floatval($in_login_timeout) + microtime(true)) : -1;
            $api_key = $this->fetch_data('login', 'login_id='.urlencode($in_username).'&password='.urlencode($in_password));
            
            if (isset($api_key) && $api_key) {  // If we logged in, then we get our info.
                $this->_api_key = $api_key;
                return $this->_set_up_login_info();
            } else {
                $this->set_error(_ERR_INVALID_LOGIN__);
                $this->_api_key = NULL;
                $this->_login_time_limit = -1;
                $this->_my_login_info = NULL;
                $this->_my_user_info = NULL;
            }
        } else {
            $this->set_error(_ERR_PREV_LOGIN__);
        }
        
        return false;
    }
    
    /***********************/
    /**
    Logs the current user out, and resets the object to a "connection-only" state.
    
    \returns true, if the logout was successful.
     */
    function logout() {
        if ($this->is_logged_in()) {
            $this->_call_REST_API('GET', 'logout', NULL);
            if (205 == intval($this->_last_response_code)) {
                $this->_api_key = NULL;
                $this->_login_time_limit = -1;
                $this->_my_login_info = NULL;
                $this->_my_user_info = NULL;
                return true;
            } else {
                $this->set_error(_ERR_COMM_ERR__);
            }
        } else {
            $this->set_error(_ERR_NOT_LOGGED_IN__);
            $this->_api_key = NULL;
            $this->_login_time_limit = -1;
            $this->_my_login_info = NULL;
            $this->_my_user_info = NULL;
        }
        
        return false;
    }
    
    /***********************/
    /**
    \returns any error we may have, in an associative array ('code' => integer, 'message' => string). The localization will be dependent upon the SDK localization. NULL, if no error.
     */
    function get_error() {
        $ret = NULL;
        
        if ($this->_error) {
            $message_class = 'RVP_Locale_'.$this->_sdk_lang;
            require_once(dirname(__FILE__).'/lang/'.$this->_sdk_lang.'.php');
            $ret = ['code' => intval($this->_error)];
            $ret['message'] = $message_class::get_error_message($ret['code']);
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    Sets the internal error code.
     */
    function set_error( $in_code    ///< REQUIRED: This is an integer error code. It can be NULL or 0 to clear the error.
                        ) {
        $this->_error = isset($in_code) && (0 != intval($in_code)) ? intval($in_code) : NULL;
    }
    
    /***********************/
    /**
    Resets the internal error to NULL.
     */
    function clear_error() {
        $this->set_error(NULL);
    }
    
    /***********************/
    /**
    \returns true, if we are pointing at a valid server.
     */
    function valid() {
        return isset($this->_available_plugins) && is_array($this->_available_plugins) && (3 < count($this->_available_plugins));
    }
 
    /***********************/
    /**
     */
    function force_reload() {
        return $this->_set_up_login_info();
    }
    
    /***********************/
    /**
    \returns true, if we are currently logged in.
     */
    function is_logged_in() {
        return isset($this->_api_key) && (0 < $this->login_time_left());
    }
    
    /***********************/
    /**
    \returns true, if we are currently logged in as a manager.
     */
    function is_manager() {
        if ($this->is_logged_in() && isset($this->_my_login_info)) {
            return $this->_my_login_info->is_manager();
        }
        
        return false;
    }
    
    /***********************/
    /**
    \returns true, if we are currently logged in as a main admin.
     */
    function is_main_admin() {
        if ($this->is_manager() && isset($this->_my_login_info)) {
            return $this->_my_login_info->is_main_admin();
        }
        
        return false;
    }
    
    /***********************/
    /**
    \returns an integer, with the current login ID. NULL, if not logged in.
     */
    function current_login_id() {
        if ($this->is_logged_in() && isset($this->_my_login_info)) {
            return $this->_my_login_info->id();
        }
        
        return NULL;
    }
    
    /***********************/
    /**
    \returns a string, with the current login ID. NULL, if not logged in.
     */
    function current_login_id_string() {
        if ($this->is_logged_in() && isset($this->_my_login_info)) {
            return $this->_my_login_info->login_id();
        }
        
        return NULL;
    }
    
    /***********************/
    /**
    \returns a string, with the current login ID. NULL, if not logged in.
     */
    function current_login_object() {
        $ret = NULL;
        
        if ($this->is_logged_in() && isset($this->_my_login_info)) {
            $ret = new RVP_PHP_SDK_Login($this, $this->_my_login_info->login_id());
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    If we logged in with a known time limit, we report how mucg time we have left.
    
    \returns the number of seconds (float) we have left in our current login.
     */
    function login_time_left() {
        if (0 < $this->_login_time_limit) {
            return floor(100 * max($this->_login_time_limit - microtime(true), 0)) / 100;
        }
        
        return 0;
    }
    
    /***********************/
    /**
    \returns an associative array ('login' => login object, 'user' => user object), with our information.
     */
    function my_info() {
        if ($this->is_logged_in() && isset($this->_my_login_info)) {
            $ret = ['login' => $this->_my_login_info];
            
            if (isset($this->_my_user_info)) {
                $ret['user'] = $this->_my_user_info;
            }
            
            return $ret;
        } else {
            return NULL;
        }
    }
    
    /***********************/
    /**
    \returns an array of integer, with the current tokens "owned" by this login (including 1, and the login ID). NULL, if not logged in.
     */
    function my_tokens() {
        $ret = NULL;
        if ($this->is_logged_in() && isset($this->_my_login_info)) {
            $ret = $this->_my_login_info->security_tokens();
        }
   
        return $ret;
    }
    
    /***********************/
    /**
    \returns an array of strings, with the available plugins.
     */
    function plugins() {
        $ret = [];
        
        if ($this->valid()) {
            $ret = $this->_available_plugins;
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    Allows a logged-in user to change their password.
    The "God" user cannot change their password.
    This will re-log in after changing the password (which logs you out).
    
    \returns true, if the change was successful.
     */
    function change_my_password_to( $in_new_password    ///< REQUIRED: The new password, in cleartext. It must be at least the minimum password length
                                    ) {
        $ret = NULL;
        
        if ($this->is_logged_in() && !$this->is_main_admin()) {
            $my_login_id = $this->_my_login_info->login_id();
            $result = $this->put_data('json/people/logins/my_info', 'password='.urlencode($in_new_password));
            
            if (isset($result)) {
                $result = json_decode($result);
                // The reason for this crazy Fabergé egg, is because it's easier to debug a nested set of comparisons.
                if (isset($result)) {
                    if (isset($result->people)) {
                        if (isset($result->people->logins)) {
                            if (isset($result->people->logins->changed_logins)) {
                                if (is_array($result->people->logins->changed_logins)) {
                                    if ((1 == count($result->people->logins->changed_logins))) {
                                        if (isset($result->people->logins->changed_logins[0])) {
                                            if (isset($result->people->logins->changed_logins[0]->after)) {
                                                if (isset($result->people->logins->changed_logins[0]->after->password)) {
                                                    if (($in_new_password == $result->people->logins->changed_logins[0]->after->password)) {
                                                        $this->_api_key = NULL;
                                                        $ret = $this->login($my_login_id, $in_new_password, $this->_login_timeout);
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    This method will initiate and complete a data GET connection to the server. It takes care of any authentication.
    
    \returns whatever data was returned. Usually JSON.
     */
    function fetch_data(    $in_plugin_path,            ///< REQUIRED: The plugin path to append to the base URI. This is a string.
                            $in_query_args = NULL       ///< OPTIONAL: Any query arguments to be attached after a question mark. This is a string.
                        ) {
        if (isset($in_query_args) && trim($in_query_args)) {
            $in_plugin_path .= '?'.ltrim($in_query_args, '&');
        }
        
        $response = $this->_call_REST_API('GET', $in_plugin_path);
        
        return $response;
    }
    
    /***********************/
    /**
    This method will initiate and complete a data PUT connection to the server. It takes care of any authentication.
    You must be logged in to perform this operation.
    
    \returns whatever data was returned. Usually JSON.
     */
    function put_data(  $in_plugin_path,        ///< REQUIRED: The plugin path to append to the base URI. This is a string, and should include the resource designation.
                        $in_query_args,         ///< REQUIRED: Any query arguments to be attached after a question mark. This is a string.
                        $in_data_object = NULL  ///< OPTIONAL: If supplied, this will be attached payload data. It should not be base64-encoded.
                    ) {
        $response = NULL;

        if ($this->is_logged_in() && isset($in_plugin_path) && trim($in_plugin_path) && isset($in_query_args) && trim($in_query_args)) {
            $in_plugin_path .= '?'.ltrim($in_query_args, '&');
            $response = $this->_call_REST_API('PUT', $in_plugin_path, $in_data_object);
        } elseif ($this->is_logged_in()) {
            $this->set_error(_ERR_NOT_AUTHORIZED__);
        } else {
            $this->set_error(_ERR_INVALID_PARAMETERS__);
        }
        
        return $response;
    }
    
    /***********************/
    /**
    This method will initiate and complete a data POST connection to the server. It takes care of any authentication.
    You must be logged in to perform this operation.
    You cannot select a resource for this. The plugin should be specified.
    
    \returns whatever data was returned. Usually JSON.
     */
    function post_data( $in_plugin_path,        ///< REQUIRED: The plugin path to append to the base URI. This is a string, and should NOT include any resource designation.
                        $in_query_args = NULL,  ///< OPTIONAL: Any query arguments to be attached after a question mark. This is a string.
                        $in_data_object = NULL  ///< OPTIONAL: If supplied, this will be attached payload data. It should not be base64-encoded.
                        ) {
        $response = NULL;
        
        if ($this->is_logged_in() && isset($in_plugin_path) && trim($in_plugin_path)) {
            if (isset($in_query_args) && trim($in_query_args)) {
                $in_plugin_path .= '?'.ltrim($in_query_args, '&');
            }
            
            $response = $this->_call_REST_API('POST', $in_plugin_path, $in_data_object);
        } elseif ($this->is_logged_in()) {
            $this->set_error(_ERR_NOT_AUTHORIZED__);
        } else {
            $this->set_error(_ERR_INVALID_PARAMETERS__);
        }
        
        return $response;
    }
    
    /***********************/
    /**
    This method will initiate and complete a data DELETE connection to the server. It takes care of any authentication.
    You must be logged in to perform this operation.
    
    \returns whatever data was returned. Usually JSON.
     */
    function delete_data(   $in_plugin_path ///< REQUIRED: The plugin path to append to the base URI. This is a string, and should include the resource designation.
                        ) {
        $response = NULL;
        
        if ($this->is_logged_in() && isset($in_plugin_path) && trim($in_plugin_path)) {
            $response = $this->_call_REST_API('DELETE', $in_plugin_path);
        } elseif ($this->is_logged_in()) {
            $this->set_error(_ERR_NOT_AUTHORIZED__);
        } else {
            $this->set_error(_ERR_INVALID_PARAMETERS__);
        }
        
        return $response;
    }
    
    /***********************/
    /**
    \returns new user, place and/or thing objects (or NULL) for the given integer ID[s]. These will be "unresolved" objects, sorted by ID.
     */
    function get_objects() {
        $args = array_map('intval', func_get_args());
        $ret = NULL;
        $plugin_list = [];
        $handlers = $this->fetch_data('json/baseline/handlers/'.implode(',', $args));
        if (isset($handlers)) {
            $handlers = json_decode($handlers);
            if (isset($handlers) && isset($handlers->baseline)) {
                $ret = $this->_decode_handlers($handlers->baseline);
            }
        } else {
            $this->set_error(_ERR_COMM_ERR__);
            return NULL;
        }
        
        if (isset($ret) && is_array($ret) && (1 < count($ret))) {
            usort($ret, function($a, $b) {
                            if ($a->id() == $b->id()) {
                                return 0;
                            }
                        
                            if ($a->id() < $b->id()) {
                                return -1;
                            }
                        
                            return 1;
                        }
            );
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    \returns a new user object (or NULL) for the given integer ID.
     */
    function get_user_info( $in_user_id ///< REQUIRED: The integer ID of the user we want to examine. If we don't have rights to the user, or the user does not exist, we get nothing.
                            ) {
        $ret = NULL;
        
        if ($this->is_logged_in()) {
            $info = $this->fetch_data('json/people/people/'.intval($in_user_id), 'show_details');
            if ($info) {
                $temp = json_decode($info);
                if (isset($temp) && isset($temp->people) && isset($temp->people->people) && isset($temp->people->people[0])) {
                    $ret = new RVP_PHP_SDK_User($this, $temp->people->people[0]->id, $temp->people->people[0], true);
                    if (!isset($ret) || !($ret instanceof RVP_PHP_SDK_User)) {
                        $this->set_error(_ERR_INTERNAL_ERR__);
                        $ret = NULL;
                    }
                }
            } else {
                $this->set_error(_ERR_COMM_ERR__);
            }
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    \returns a new login object (or NULL) for the given integer ID.
     */
    function get_login_info(    $in_login_id    ///< REQUIRED: The integer ID of the login we want to examine. If we don't have rights to the login, or the login does not exist, we get nothing.
                            ) {
        $ret = NULL;
        
        if ($this->is_logged_in()) {
            $info = $this->fetch_data('json/people/logins/'.intval($in_login_id), 'show_details');
            if ($info) {
                $temp = json_decode($info);
                if (isset($temp) && isset($temp->people) && isset($temp->people->logins) && isset($temp->people->logins[0])) {
                    $ret = new RVP_PHP_SDK_Login($this, $temp->people->logins[0]->id, $temp->people->logins[0], true);
                    if (!isset($ret) || !($ret instanceof RVP_PHP_SDK_Login)) {
                        $this->set_error(_ERR_INTERNAL_ERR__);
                        $ret = NULL;
                    }
                }
            } else {
                $this->set_error(_ERR_COMM_ERR__);
            }
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    \returns a new place object (or NULL) for the given integer ID.
     */
    function get_place_info(    $in_place_id    ///< REQUIRED: The integer ID of the place we want to examine. If we don't have rights to the place, or the place does not exist, we get nothing.
                            ) {
        $ret = NULL;
        
        $info = $this->fetch_data('json/places/'.intval($in_place_id), 'show_details');
        if ($info) {
            $temp = json_decode($info);
            if (isset($temp) && isset($temp->places) && isset($temp->places->results) && is_array($temp->places->results) && isset($temp->places->results[0])) {
                $ret = new RVP_PHP_SDK_Place($this, $temp->places->results[0]->id, $temp->places->results[0], true);
                if (!isset($ret) || !($ret instanceof RVP_PHP_SDK_Place)) {
                    $this->set_error(_ERR_INTERNAL_ERR__);
                    $ret = NULL;
                }
            }
        } else {
            $this->set_error(_ERR_COMM_ERR__);
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    \returns a new thing object (or NULL) for the given integer ID.
     */
    function get_thing_info(    $in_thing_id    ///< REQUIRED: The integer ID, or string key, of the thing we want to examine. If we don't have rights to the thing, or the thing does not exist, we get nothing.
                            ) {
        $ret = NULL;
        
        $info = $this->fetch_data('json/things/'.urlencode($in_thing_id), 'show_details');
        if ($info) {
            $temp = json_decode($info);
            if (isset($temp) && isset($temp->things) && isset($temp->things[0])) {
                $ret = new RVP_PHP_SDK_Thing($this, $temp->things[0]->id, $temp->things[0], true);
                if (!isset($ret) || !($ret instanceof RVP_PHP_SDK_Thing)) {
                    $this->set_error(_ERR_INTERNAL_ERR__);
                    $ret = NULL;
                }
            }
        } else {
            $this->set_error(_ERR_COMM_ERR__);
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    This is a baseline plugin text search.
    
    The searched columns are the "object_name" column, or tags 0-9.
    \returns an array of objects (of any kind) that have the requested text in the fields supplied. SQL-style wildcards (%) are applicable.
     */
    function general_search(    $in_text_array = [],    /**< OPTIONAL:  An associative array, laying out which text fields to search, and the search text.
                                                                    The key is the name of the field to search, and the value is the text to search for.
                                                                    You can use SQL-style wildcards (%).
                                                                    Available keys:
                                                                        - 'name'            Searches the 'object_name' column.
                                                                        - 'tag0' - 'tag9'   Searches the tag indicated. It should be noted that different plugins use these tags for different fixed purposes.
                                                            */
                                $in_location = NULL,    ///< OPTIONAL: An associative array ('latitude' => float, 'longitude' => float, 'radius' => float), with the long/lat (in degrees), and the radius of the location search (in Kilometers).
                                $in_writeable = false   ///< OPTIONAL: If true, then only places the current login can edit are returned. Ignored if not logged in.
                                ) {
        $ret = NULL;
        
        $added_parameters = '';
        
        if (is_array($in_text_array) && count($in_text_array)) {
            foreach ($in_text_array as $key => $value) {
                $added_parameters .= urlencode(self::_get_tag_match($key)).'='.urlencode($value);
            }
        }
        
        if ($in_writeable && $this->is_logged_in()) {   // We ignore writeable if we are not logged in.
            $added_parameters .= '&writeable';
        }
        
        if (NULL !== $in_location) {
            $added_parameters .= '&search_latitude='.floatval($in_location['latitude']).'&search_longitude='.floatval($in_location['longitude']).'&search_radius='.floatval($in_location['radius']);
        }
        
        $handlers = $this->fetch_data('json/baseline/search/', $added_parameters);
        if (isset($handlers)) {
            $handlers = json_decode($handlers);
            if (isset($handlers) && isset($handlers->baseline)) {
                $ret = $this->_decode_handlers($handlers->baseline);
        
                if (isset($ret) && is_array($ret) && (0 < count($ret))) {
                    usort($ret, function($a, $b) {
                                    if ($a->id() == $b->id()) {
                                        return 0;
                                    }
                        
                                    if ($a->id() < $b->id()) {
                                        return -1;
                                    }
                        
                                    return 1;
                                }
                    );
                }
            }
        } else {
            $this->set_error(_ERR_COMM_ERR__);
            return NULL;
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    This is a people plugin text search.
    
    The searched columns are the "object_name" column, or tags 0-9 (since this is a fixed-purpose plugin, these will be accessed by name, not tag name).
    
    \returns an array of people objects that have the requested text in the fields supplied. SQL-style wildcards (%) are applicable.
     */
    function people_search(     $in_text_array = [],    /**< OPTIONAL:  An associative array, laying out which text fields to search, and the search text.
                                                                    The key is the name of the field to search, and the value is the text to search for.
                                                                    You can use SQL-style wildcards (%).
                                                                    Available keys:
                                                                        - 'name'        Searches the 'object_name' column.
                                                                        - 'surname'     Searches the surname tag.
                                                                        - 'middle_name' Searches the middle name tag.
                                                                        - 'given_name'  Searches the first (given) name tag.
                                                                        - 'nickname'    Searches the first (given) name tag.
                                                                        - 'prefix'      Searches the prefix tag.
                                                                        - 'suffix'      Searches the suffix tag.
                                                                        - 'tag7'        Searches tag 7.
                                                                        - 'tag8'        Searches tag 8.
                                                                        - 'tag9'        Searches tag 9.
                                                            */
                                $in_location = NULL,            ///< OPTIONAL: An associative array ('latitude' => float, 'longitude' => float, 'radius' => float), with the long/lat (in degrees), and the radius of the location search (in Kilometers).
                                $in_get_logins_only = false,    ///< OPTIONAL: If true (Default is false), then only login objects associated with the user objects that fall within the search will be returned.
                                $in_writeable = false           ///< OPTIONAL: If true, then only places the current login can edit are returned. Ignored if not logged in.
                                ) {
        $ret = NULL;
        
        $added_parameters = '';
        
        if (is_array($in_text_array) && count($in_text_array)) {
            foreach ($in_text_array as $key => $value) {
                $added_parameters .= urlencode(self::_get_tag_match($key)).'='.urlencode($value);
            }
        }

        if ($in_get_logins_only) {
            $added_parameters .= '&login_user';
        }
        
        if ($in_writeable && $this->is_logged_in()) {   // We ignore writeable if we are not logged in.
            $added_parameters .= '&writeable';
        }
        
        if (NULL !== $in_location) {
            $added_parameters .= '&search_latitude='.floatval($in_location['latitude']).'&search_longitude='.floatval($in_location['longitude']).'&search_radius='.floatval($in_location['radius']);
        }
        
        $response = $this->fetch_data('json/people/people/', $added_parameters);
        if (isset($response)) {
            $response = json_decode($response);
            if (isset($response) && isset($response->people) && isset($response->people->people)) {
                $ret = [];
                $people = (array)$response->people->people;
                foreach ($people as $person) {
                    if (isset($person->id)) {
                        if ($in_get_logins_only && isset($person->associated_login)) {
                            $new_object = new RVP_PHP_SDK_Login($this, $person->associated_login->id, $person->associated_login, true);
                            if (isset($new_object) && ($new_object instanceof RVP_PHP_SDK_Login)) {
                                $ret[] = $new_object;
                            } else {
                                $this->set_error(_ERR_INTERNAL_ERR__);
                                return NULL;
                            }
                        } elseif (!$in_get_logins_only) {
                            $new_object = new RVP_PHP_SDK_User($this, $person->id, $person);
                            if (isset($new_object) && ($new_object instanceof RVP_PHP_SDK_User)) {
                                $ret[] = $new_object;
                            } else {
                                $this->set_error(_ERR_INTERNAL_ERR__);
                                return NULL;
                            }
                        }
                    }
                }
        
                if (isset($ret) && is_array($ret) && (0 < count($ret))) {
                    usort($ret, function($a, $b) {
                                    if ($a->id() == $b->id()) {
                                        return 0;
                                    }
                        
                                    if ($a->id() < $b->id()) {
                                        return -1;
                                    }
                        
                                    return 1;
                                }
                    );
                }
            }
        } else {
            $this->set_error(_ERR_COMM_ERR__);
            return NULL;
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    This is a places plugin text search.
    
    The searched columns are the "object_name" column, or tags 0-9 (since this is a fixed-purpose plugin, these will be accessed by name, not tag name).
    
    \returns an array of place objects that have the requested text in the fields supplied. SQL-style wildcards (%) are applicable.
     */
    function places_search(     $in_text_array = [],    /**< OPTIONAL:  An associative array, laying out which text fields to search, and the search text.
                                                                    The key is the name of the field to search, and the value is the text to search for.
                                                                    You can use SQL-style wildcards (%).
                                                                    Available keys:
                                                                        - 'name'                        Searches the 'object_name' column.
                                                                        - 'venue'                       Searches the venue name tag.
                                                                        - 'street_address'              Searches the street address tag.
                                                                        - 'extra_information'           Searches the extra information tag.
                                                                        - 'city' or 'town'              Searches the town tag.
                                                                        - 'county'                      Searches the county tag.
                                                                        - 'state' or 'province'         Searches the state tag.
                                                                        - 'postal_code' or 'zip_code'   Searches the zip code field.
                                                                        - 'nation'                      Searches the nation tag.
                                                                        - 'tag8'                        Searches tag 8.
                                                                        - 'tag9'                        Searches tag 9.
                                                            */
                                $in_location = NULL,    ///< OPTIONAL: An associative array ('latitude' => float, 'longitude' => float, 'radius' => float), with the long/lat (in degrees), and the radius of the location search (in Kilometers).
                                $in_writeable = false   ///< OPTIONAL: If true, then only places the current login can edit are returned. Ignored if not logged in.
                                ) {
        $ret = NULL;
        
        $added_parameters = '';
        
        if (is_array($in_text_array) && count($in_text_array)) {
            foreach ($in_text_array as $key => $value) {
                $added_parameters .= urlencode(self::_get_tag_match($key)).'='.urlencode($value);
            }
        }
        
        if ($in_writeable && $this->is_logged_in()) {   // We ignore writeable if we are not logged in.
            $added_parameters .= '&writeable';
        }
        
        if (NULL !== $in_location) {
            $added_parameters .= '&search_latitude='.floatval($in_location['latitude']).'&search_longitude='.floatval($in_location['longitude']).'&search_radius='.floatval($in_location['radius']);
        }
        
        $response = $this->fetch_data('json/places/', $added_parameters);
        if (isset($response)) {
            $response = json_decode($response);
            if (isset($response) && isset($response->places) && isset($response->places->results) && is_array($response->places->results) && count($response->places->results)) {
                $ret = [];
                foreach ($response->places->results as $place) {
                    $new_object = new RVP_PHP_SDK_Place($this, $place->id, $place);
                    if (isset($new_object) && ($new_object instanceof RVP_PHP_SDK_Place)) {
                        $ret[] = $new_object;
                    } else {
                        $this->set_error(_ERR_INTERNAL_ERR__);
                        return NULL;
                    }
                }
        
                if (isset($ret) && is_array($ret) && (0 < count($ret))) {
                    usort($ret, function($a, $b) {
                                    if ($a->id() == $b->id()) {
                                        return 0;
                                    }
                        
                                    if ($a->id() < $b->id()) {
                                        return -1;
                                    }
                        
                                    return 1;
                                }
                    );
                }
            }
        } else {
            $this->set_error(_ERR_COMM_ERR__);
            return NULL;
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    This is a things plugin text search.
    
    The searched columns are the "object_name" column, or tags 0-9 (since this is a fixed-purpose plugin, a couple of these will be accessed by name, not tag name).
    
    \returns an array of thing objects that have the requested text in the fields supplied. SQL-style wildcards (%) are applicable.
     */
    function things_search(     $in_text_array = [],    /**< OPTIONAL:  An associative array, laying out which text fields to search, and the search text.
                                                                    The key is the name of the field to search, and the value is the text to search for.
                                                                    You can use SQL-style wildcards (%).
                                                                    Available keys:
                                                                        - 'name'            Searches the 'object_name' column.
                                                                        - 'description'     Searches the thing description tag.
                                                                        - 'tag2' - 'tag9'   Searches the tag indicated.
                                                            */
                                $in_location = NULL,    ///< OPTIONAL: An associative array ('latitude' => float, 'longitude' => float, 'radius' => float), with the long/lat (in degrees), and the radius of the location search (in Kilometers).
                                $in_writeable = false   ///< OPTIONAL: If true, then only places the current login can edit are returned. Ignored if not logged in.
                                ) {
        $ret = NULL;
        
        $added_parameters = '';
        
        if (is_array($in_text_array) && count($in_text_array)) {
            foreach ($in_text_array as $key => $value) {
                $added_parameters .= urlencode(self::_get_tag_match($key)).'='.urlencode($value);
            }
        }
        
        if ($in_writeable && $this->is_logged_in()) {   // We ignore writeable if we are not logged in.
            $added_parameters .= '&writeable';
        }
        
        if (NULL !== $in_location) {
            $added_parameters .= '&search_latitude='.floatval($in_location['latitude']).'&search_longitude='.floatval($in_location['longitude']).'&search_radius='.floatval($in_location['radius']);
        }
        
        $response = $this->fetch_data('json/things/', $added_parameters);
        if (isset($response)) {
            $response = json_decode($response);
            if (isset($response) && isset($response->things) && is_array($response->things) && count($response->things)) {
                $ret = [];
                foreach ($response->things as $thing) {
                    $new_object = new RVP_PHP_SDK_Thing($this, $thing->id, $thing);
                    if (isset($new_object) && ($new_object instanceof RVP_PHP_SDK_Thing)) {
                        $ret[] = $new_object;
                    } else {
                        $this->set_error(_ERR_INTERNAL_ERR__);
                        return NULL;
                    }
                }
        
                if (isset($ret) && is_array($ret) && (0 < count($ret))) {
                    usort($ret, function($a, $b) {
                                    if ($a->id() == $b->id()) {
                                        return 0;
                                    }
                        
                                    if ($a->id() < $b->id()) {
                                        return -1;
                                    }
                        
                                    return 1;
                                }
                    );
                }
            }
        } else {
            $this->set_error(_ERR_COMM_ERR__);
            return NULL;
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    \returns an array of objects (of any kind) that fall within the search radius. NOTE: If the objects don't have an assigned long/lat, they will not be returned in this search.
     */
    function general_location_search(   $in_location    ///< REQUIRED: An associative array ('latitude' => float, 'longitude' => float, 'radius' => float), with the long/lat (in degrees), and the radius of the location search (in Kilometers).
                                    ) {
        return $this->general_search(NULL, $in_location);
    }
    
    /***********************/
    /**
    \returns an array of user (or login) objects that fall within the search radius. NOTE: If the objects don't have an assigned long/lat, they will not be returned in this search.
     */
    function people_location_search(    $in_location,               ///< REQUIRED: An associative array ('latitude' => float, 'longitude' => float, 'radius' => float), with the long/lat (in degrees), and the radius of the location search (in Kilometers).
                                        $in_get_logins_only = false ///< OPTIONAL: If true (Default is false), then only login objects associated with the user objects that fall within the search will be returned.
                                    ) {
        return $this->people_search(NULL, $in_location, $in_get_logins_only);
    }
    
    /***********************/
    /**
    \returns an array of place objects that fall within the search radius. NOTE: If the objects don't have an assigned long/lat, they will not be returned in this search.
     */
    function place_location_search( $in_location    ///< REQUIRED: An associative array ('latitude' => float, 'longitude' => float, 'radius' => float), with the long/lat (in degrees), and the radius of the location search (in Kilometers).
                                    ) {
        return $this->places_search(NULL, $in_location);
    }
    
    /***********************/
    /**
    \returns an array of thing objects that fall within the search radius. NOTE: If the objects don't have an assigned long/lat, they will not be returned in this search.
     */
    function thing_location_search( $in_location    ///< REQUIRED: An associative array ('latitude' => float, 'longitude' => float, 'radius' => float), with the long/lat (in degrees), and the radius of the location search (in Kilometers).
                                    ) {
        return $this->things_search(NULL, $in_location);
    }
    
    /***********************/
    /**
    This is an "auto-radius" search. The way that it works, is that you specify a center point, and any search criteria.
    You specify the minimum number of resources that you want to find, what types of resources you want, any string filters, as well as the search step size and maximum (give up) radius.
    This can be a lengthy process.
    The way it works, is that successive radius search queries are made, using the filters and types, until AT LEAST the number of requested results are returned.
    Each successive search widens the radius by the step size.
    The first radius is one step size, and the last radius is the width of the "give up" threshold, or less, if the last step was beyond the threshold.
    
    \returns an array of thing objects that fall within the search radius. NOTE: If the objects don't have an assigned long/lat, they will not be returned in this search.
     */
    function auto_radius_search(    $in_center_point,                   ///< REQUIRED: This is the starting (center) point of the auto-radius search. It is an associative array ('longitude' => float, 'latitude' => float).
                                    $in_target_number = 10,             ///< OPTIONAL: An integer. The minimum number of resources to find. Default is 10.
                                    $in_search_type = 'all',            /**< OPTIONAL: The type of search. It can be:
                                                                                - 'all' (or NULL/blank).    This is all types of resources. This is the default.
                                                                                - 'users' or 'people'       This is user objects.
                                                                                - 'logins'                  This is logins, associated with users (you cannot search for standalone logins this way).
                                                                                - 'places'                  This is places.
                                                                                - 'things'                  This is thing objects.
                                                                        */
                                    $in_search_string_criteria = NULL,  ///< OPTIONAL: This is an associative array (keys are field names, and values are what you are looking for. You can use SQL-style wildcards "%").
                                    $in_step_size_in_km = 0.5,          ///< OPTIONAL: This is the size of steps that we will take in the search. Default is 0.5 km (500m).
                                    $in_max_width_in_km = 100,          ///< OPTIONAL: The maximum radius in kilometers. Default is 100km.
                                    $step_callback = NULL               /**< OPTIONAL: This is a lmbda/closure/callback function that you provide, and will be called after each step, with the current results.
                                                                                       This can either be a global-scope function, or an array, with the first element being an object instance, and the second element being the name of the method.
                                                                                       The signature for the function/method is:
                                                                                            function callback(  $in_sdk_instance,   // The SDK instance (this)
                                                                                                                $in_results,        // The current results array (of instances).
                                                                                                                $in_type,           // The search type ('all', 'users', 'logins, 'places', 'things').
                                                                                                                $in_target_number,  // This is the number of results (minimum) that will satisfy the search.
                                                                                                                $in_step_size,      // The step size, in kilometers.
                                                                                                                $in_max_radius,     // The maximum radius for the search, in kilometers.
                                                                                                                $in_location,       // The current location (associative array ['latitude' => float, 'longitude' => float, 'radius' => float]).
                                                                                                                $in_search_criteria // An associative array with teh current text filter search criteria.
                                                                                                            );
                                                                                            The function should return either true, or false. If it returns true, then the search should be stopped at that point, and the current results returned.
                                                                        */
                                ) {
        $radius = 0.0;
        $results = [];
        $location = ['latitude' => floatval($in_center_point['latitude']), 'longitude' => floatval($in_center_point['longitude']), 'radius' => $radius];
    
        while (($in_target_number > count($results)) && ($in_max_width_in_km >= ($radius + $in_step_size_in_km))) {
            $radius += floatval($in_step_size_in_km);
            $location['radius'] = $radius;
            switch (strtolower(trim($in_search_type))) {
                case    'people':
                case    'users':
                    $in_search_type = 'users';
                case    'logins':
                    $results = $this->people_search($in_search_string_criteria, $location, ('logins' == $in_search_type));
                    break;
            
                case    'places':
                    $results = $this->places_search($in_search_string_criteria, $location);
                    break;
            
                case    'things':
                    $results = $this->things_search($in_search_string_criteria, $location);
                    break;
            
                default:
                    $in_search_type = 'all';
                    $results = $this->general_search($in_search_string_criteria, $location);
                    break;
            }
            
            // The user can provide a callback that gets a report, and can abort the search.
            if (isset($step_callback)) {
                $abort = false;
                
                if (is_array($step_callback) && (2 == count($step_callback))) {
                    $object = $step_callback[0];
                    $method = $step_callback[1];
                    if (method_exists($object, $method)) {
                        $abort = $object->$method($this, $results, strtolower(trim($in_search_type)), $in_target_number, $in_step_size_in_km, $in_max_width_in_km, $location, $in_search_string_criteria);
                    }
                } elseif (function_exists($step_callback)) {
                    $abort = $step_callback($this, $results, strtolower(trim($in_search_type)), $in_target_number, $in_step_size_in_km, $in_max_width_in_km, $location, $in_search_string_criteria);
                }
                
                if ($abort) {
                    break;
                }
            }
        }
        
        return $results;
    }
                                    
    /***********************/
    /**
    This is the "bulk-import" for the BAOBAB server. It requires that the API key be for a "God" admin, and that the CO_Config::$enable_bulk_upload flag be set to true on the server.
    
    You supply a CSV file, in the following format:
    
    id,api_key,login_id,access_class,last_access,read_security_id,write_security_id,object_name,access_class_context,owner,longitude,latitude,tag0,tag1,tag2,tag3,tag4,tag5,tag6,tag7,tag8,tag9,ids,payload
    
    Depending on the class in the 'access_class' column, either the security or data databes will be affected by a given row. Note that columns correspond to BOTH databases, so some columns will be ignored.
    
    If you put 'NULL' in as a column value, that will be translated to NULL in the database.
    
    \returns a list of translations; from the integer 'id' column in the data file, to the id used in the server. remember that we are dealing with two databases here, so it is up to you to understand which database is being affected.
     */
    function bulk_upload(   $in_csv_data    ///< REQUIRED: This is the CSV content (in our required schema) to be uploaded to the server.
                        ) {
        if ($this->is_main_admin()) {
            return json_decode($this->post_data('json/baseline/bulk-loader', NULL, ['data' => $in_csv_data, 'type' => 'text/csv']));
        } else {
            $this->set_error(_ERR_NOT_AUTHORIZED__);
            return NULL;
        }
    }
    
    /***********************/
    /**
    This method is only available to "God" logins. It fetches the entire database as a CSV string.
    
    \returns the entire database, in a bulk-upload-format CSV string. NULL, if not authorized.
     */
    function backup() {
        if ($this->is_main_admin()) {
            return $this->fetch_data('csv/baseline/backup');
        } else {
            $this->set_error(_ERR_NOT_AUTHORIZED__);
            return NULL;
        }
    }
    
    /***********************/
    /**
    This method is only available to "God" logins. It fetches the server information structure.
    
    \returns the serverinfo object.
     */
    function get_serverinfo() {
        if ($this->is_main_admin()) {
            $result = json_decode($this->fetch_data('json/baseline/serverinfo'));
            if (isset($result->baseline) && isset($result->baseline->serverinfo)) {
                return $result->baseline->serverinfo;
            }
        } else {
            $this->set_error(_ERR_NOT_AUTHORIZED__);
        }
        
        return NULL;
    }
    
    /***********************/
    /**
    This is a test of resource IDs or security tokens. It returns Login IDs (security DB), not User IDs (data DB).
    You give it the ID of a resource (data DB), and what you get back is a list of the login IDs that can see that resource, and those that can modify it (each is listed in a separate array).
    If you set the second (optional) parameter to true, then the ID that you send in is interpreted as a security token, and the response contains the IDs of logins that have that token.
    It should be noted that only login IDs that the current user can see will be returned. Additionally, the current user must have at least read permission for any resource ID, and must have access to the token.
    
    \returns either a straight-up simple array of integer ($in_is_token is true), containing the IDs of logins that have the token, or an associative array ('read_login_ids' => array of int, 'write_login_ids' => array of int), with the IDs of the logins with access to the resource, and what kind of access they have. Write access also grants read. NULL if no login IDs are available.
     */
    function test_visibility(   $in_id,                 ///< REQUIRED: The ID or token to test. This is an integer. This should be 1 or greater (for tokens), or 2 or greater (for IDs).
                                $in_is_token = false    ///< OPTIONAL: If true (Default is false), then the ID is actually a security token.
                            ) {
        $ret = NULL;
        
        $in_id = intval($in_id);    // Make sure that we're an integer.
        
        // We have our basic standards.
        if ((1 < $in_id) || ($in_is_token && (0 <= $in_id))) {
            $uri = 'json/baseline/visibility/'. ($in_is_token ? 'token/' : '');
            $uri .= $in_id;
            $response = $this->fetch_data($uri);
            if (isset($response)) {
                $response = json_decode($response);
                if (isset($response) && isset($response->baseline)) {
                    $response = $response->baseline;
                    if (isset($response->token) && isset($response->token->login_ids) && is_array($response->token->login_ids) && count($response->token->login_ids)) {
                        $ret = $response->token->login_ids;
                    } elseif (isset($response->id) && isset($response->id->id)) {
                        $response = $response->id;
                        $ret = ['id' => $response->id];
                        if (isset($response->writeable)) {
                            $ret['writeable'] = true;
                        }
                        if (isset($response->read_login_ids) && is_array($response->read_login_ids) && count($response->read_login_ids)) {
                            $ret['read_login_ids'] = $response->read_login_ids;
                        }
                        if (isset($response->write_login_ids) && is_array($response->write_login_ids) && count($response->write_login_ids)) {
                            $ret['write_login_ids'] = $response->write_login_ids;
                        }
                    }
                }
            } else {
            }
        } else {
            $this->set_error(_ERR_INVALID_PARAMETERS__);
            return NULL;
        }
        
        return $ret;
    }
};
