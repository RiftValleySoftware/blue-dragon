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
require_once(dirname(__FILE__).'/common.php');

/****************************************************************************************************************************/
/**
This is the English localization class.
 */
class RVP_Locale_en extends A_RVP_Locale {
    /***********************/
    /**
    \returns the default (unknown) error message.
     */
    protected static function _get_default_error_message() {
        return 'Unknown Error';
    }
    
    /***********************/
    /**
    \returns an associative array of error messages, resolved by numerical code.
     */
    protected static function _get_error_table() {
        return  [
                   'message_'._ERR_INVALID_SERVER_URI__         =>  'Invalid Server URL',
                   'message_'._ERR_INVALID_LOGIN__              =>  'Invalid Login Credentials',
                   'message_'._ERR_INVALID_AUTHENTICATION__     =>  'Invalid Authentication',
                   'message_'._ERR_NOT_AUTHORIZED__             =>  'Not Authorized to Perform This Operation',
                   'message_'._ERR_NO_RESULTS__                 =>  'No Results',
                   'message_'._ERR_PREV_LOGIN__                 =>  'There Is Already A Login',
                   'message_'._ERR_COMM_ERR__                   =>  'There Was A Communication Error With the Server',
                   'message_'._ERR_INTERNAL_ERR__               =>  'There Was An Internal Program Error',
                   'message_'._ERR_NOT_LOGGED_IN__              =>  'Attempted to Log Out When No Login present',
                   'message_'._ERR_INVALID_PARAMETERS__         =>  'The parameters provided were incorrect or out of bounds',
                   'message_'._ERR_LOGIN_HAS_USER__             =>  'The login association failed, because the login is already associated with a different user',
                ];
    }
}
?>