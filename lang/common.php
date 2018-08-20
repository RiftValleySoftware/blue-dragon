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

define('_ERR_INVALID_SERVER_URI__',     1);     // This means that the server pointed to by the SDK URI is somehow invalid.
define('_ERR_INVALID_LOGIN__',          2);     // This means that the login/password combination sent to the server was not recognized.
define('_ERR_INVALID_AUTHENTICATION__', 3);     // This means that the authentication sent to the server was not valid.
define('_ERR_NOT_AUTHORIZED__',         4);     // This means that the attempted operations was not permitted.
define('_ERR_NO_RESULTS__',             5);     // The last operation had no results, and results were expected.
define('_ERR_PREV_LOGIN__',             6);     // There is a current login, so you cannot attempt a new login.
define('_ERR_COMM_ERR__',               7);     // There was some kind of communication error with the server.
define('_ERR_INTERNAL_ERR__',           8);     // There was some kind of internal program error.
define('_ERR_NOT_LOGGED_IN__',          9);     // A Logout Attempt Was Made Where No Login Was Present.
define('_ERR_INVALID_PARAMETERS__',     10);    // The parameters provided to a method were incorrect or out of bounds.
define('_ERR_LOGIN_HAS_USER__',         11);    // This means that an attempt to add a login to another user failed, because the login still has a different user associated..

/****************************************************************************************************************************/
/**
This abstract base class is the template for localizations.

All localizations should derive from this class, presenting their localized strings in the indicated table functions.
 */
abstract class A_RVP_Locale {
    /***********************/
    /**
    \returns the default (unknown) error message.
     */
    abstract protected static function _get_default_error_message();
    
    /***********************/
    /**
    \returns an associative array of error messages, resolved by numerical code.
     */
    abstract protected static function _get_error_table();
    
    /***********************/
    /**
    \returns a string, based upon the given error code.
     */
    static function get_error_message(  $in_code    ///< REQUIRED: The error code to translate. An integer.
                                        ) {
        $key = "message_$in_code";
        $ret = static::_get_default_error_message();
        $table = static::_get_error_table();
        
        if (isset($table[$key]) && $table[$key]) {
            $ret = $table[$key];
        }
        
        return $ret;
    }
}
?>