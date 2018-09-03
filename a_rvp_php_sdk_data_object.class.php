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

require_once(dirname(__FILE__).'/a_rvp_php_sdk_object.class.php');   // Get our base class.

/****************************************************************************************************************************/
/**
 This is an abstract base class for various data objects provided by the SDK.
 
 It deals with some of the common functionality, like the payload, location data, location obfuscation, and child IDs.
 */
abstract class A_RVP_PHP_SDK_Data_Object extends A_RVP_PHP_SDK_Object {
    /************************************************************************************************************************/    
    /*#################################################### INTERNAL METHODS ################################################*/
    /************************************************************************************************************************/
    /***********************/
    /**
    \returns true, if the save was successful.
     */
    protected function _save_data(  $in_args = '',              ///< OPTIONAL: Default is an empty string. This is any previous arguments. This will be appeneded to the end of the list, so it should begin with an ampersand (&), and be url-encoded.
                                    $in_payload = NULL,         ///< OPTIONAL: Any payload to be asociated with this object. Must be an associative array (['data' => data, 'type' => MIME Type string]).
                                    $in_new_child_ids = NULL    ///< OPTIONAL: If provided, then this is an array of new child IDs (array of integer).
                                ) {
        $owner_id = isset($this->_object_data->owner_id) ? intval($this->_object_data->owner_id) : 0;
        $latitude = isset($this->_object_data->raw_latitude) ? floatval($this->_object_data->raw_latitude) : (isset($this->_object_data->latitude) ? floatval($this->_object_data->latitude) : NULL);
        $longitude = isset($this->_object_data->raw_longitude) ? floatval($this->_object_data->raw_longitude) : (isset($this->_object_data->longitude) ? floatval($this->_object_data->longitude) : NULL);
        $fuzz_factor = isset($this->_object_data->fuzz_factor) ? floatval($this->_object_data->fuzz_factor) : NULL;
        $can_see_through_the_fuzz = isset($this->_object_data->can_see_through_the_fuzz) ? intval($this->_object_data->can_see_through_the_fuzz) : NULL;
        
        $put_args = '&owner_id='.$owner_id.(isset($latitude) ? ('&latitude='.$latitude) : '').(isset($longitude) ? ('&longitude='.$longitude) : '').(isset($fuzz_factor) ? '&fuzz_factor='.$fuzz_factor : '').(isset($can_see_through_the_fuzz) ? '&can_see_through_the_fuzz='.$can_see_through_the_fuzz : '');
        
        if (isset($in_new_child_ids) && is_array($in_new_child_ids) && count($in_new_child_ids)) {
            $in_new_child_ids = array_filter(array_map('intval', $in_new_child_ids), function($i) { return 0 != intval($i); });
            if (count($in_new_child_ids)) {
                $put_args .= '&child_ids='.implode(',', $in_new_child_ids);
            }
        }
        
        $payload = isset($in_payload) ? $in_payload : NULL;
        
        $ret = parent::_save_data($put_args.$in_args, $payload);
        
        return $ret;
    }
    
    /************************************************************************************************************************/    
    /*#################################################### PUBLIC METHODS ##################################################*/
    /************************************************************************************************************************/
    
    /***********************/
    /**
    The basic constructor for the class. You have the option of "priming" the object with information.
     */
    function __construct(   $in_sdk_object,                 ///< REQUIRED: The "owning" SDK object.
                            $in_id,                         ///< REQUIRED: The server ID of the object. An integer.
                            $in_data = NULL,                ///< OPTIONAL: Parsed JSON Data for the object. Default is NULL.
                            $in_detailed_data = false,      ///< OPTIONAL: Ignored if $in_data is NULL. Default is false. If true, then the data sent in was in "detailed" format.
                            $in_plugin_path = 'baseline'    ///< OPTIONAL: This is a path that is added to the server, to fetch data. Default is "baseline."
                        ) {
        parent::__construct($in_sdk_object, $in_id, $in_data, $in_detailed_data, $in_plugin_path);
    }
    
    /***********************/
    /**
    This requires a load, but not a "detailed" load.
    
    \returns an associative array ('latitude' => float, 'longitude' => float), with the long/lat coordinates of the object, or NULL, if there are no long/lat coordinates.
     */
    function coords() {
        $ret = NULL;
        
        $this->_load_data();
        
        if (isset($this->_object_data) && isset($this->_object_data->coords)) {
            $temp = explode(',', $this->_object_data->coords);
            if (isset($temp) && is_array($temp) && (1 < count($temp))) {
                $ret = [];
                $ret['latitude'] = floatval($temp[0]);
                $ret['longitude'] = floatval($temp[1]);
            }
        }
                
        return $ret;
    }
    
    /***********************/
    /**
    This sets the new long/lat value. This sets the "real" value (or "raw" value), if the record is "fuzzed," so subsequent checks of the regular long/lat may show different results.
    
    \returns true, if it worked.
     */
    function set_coords(    $in_latitude,   ///< REQUIRED: The new latitude value, in degrees.
                            $in_longitude   ///< REQUIRED: The new longitude value, in degrees.
                        ) {
        $ret = false;
        
        $this->_load_data(false, true);

        if (isset($this->_object_data)) {
            if ((isset($this->_object_data->raw_latitude) && isset($this->_object_data->raw_longitude)) || (isset($this->_object_data->fuzzy) && $this->_object_data->fuzzy)) {
                $this->_object_data->raw_latitude = $in_latitude;
                $this->_object_data->raw_longitude = $in_longitude;
            } else {
                $this->_object_data->latitude = $in_latitude;
                $this->_object_data->longitude = $in_longitude;
            }
            
            $ret = $this->save_data();
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    This requires a a "detailed" load.
    
    \returns the distance, if provided. Otherwise, it returns 0.
     */
    function distance() {
        $ret = 0;
        
        $this->_load_data(false, true);
        
        if (isset($this->_object_data) && isset($this->_object_data->distance_in_km)) {
            $ret = floatval($this->_object_data->distance_in_km);
        }
                
        return $ret;
    }
    
    /***********************/
    /**
    This requires a "detailed" load.
    
    \returns true, if the object declares that it is "fuzzy" (has location obfuscation).
     */
    function is_fuzzy() {
        $ret = false;
        
        $this->_load_data(false, true);
        
        if (isset($this->_object_data) && isset($this->_object_data->fuzzy) && $this->_object_data->fuzzy) {
            $ret = true;
        }
                
        return $ret;
    }
    
    /***********************/
    /**
    This requires a "detailed" load.
    
    \returns a floating-point value, with the "fuzz factor" (in kilometers). You need to be logged in as an ID that has either write or "can see through the fuzz" capability on this record, or you get 0.
     */
    function fuzz_factor() {
        $ret = 0;
        
        $this->_load_data(false, true);
        
        if (isset($this->_object_data) && isset($this->_object_data->fuzz_factor) && floatval($this->_object_data->fuzz_factor)) {
            $ret = floatval($this->_object_data->fuzz_factor);
        }
                
        return $ret;
    }
    
    /***********************/
    /**
    Sets a new "fuzz factor." Setting to 0 or NULL turns off location obfuscation. Any positive floating-point number is the "fuzz radius," in kilometers, of the obfuscation.
    Long/lat returned in the normal coords() call will be obfuscated.
    If the user has the rights to "see through the fuzz," calls to raw_coords() will return accurate results (otherwise, they will return NULL).
    
    \returns true, if it worked.
     */
    function set_fuzz_factor(   $in_new_factor  ///< REQUIRED: The new "fuzz factor" value. 0 or NULL will turn off location obfuscation.
                            ) {
        $this->_load_data(false, true);
        
        $this->_object_data->fuzz_factor = $in_new_factor;
        
        $ret = $this->save_data();
    
        if ($ret) { // We force a reload, because fuzz.
            $this->_load_data(true, true);
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    Sets a new "fuzz factor." Setting to 0 or NULL turns off location obfuscation. Any positive floating-point number is the "fuzz radius," in kilometers, of the obfuscation.
    Long/lat returned in the normal coords() call will be obfuscated.
    If the user has the rights to "see through the fuzz," calls to raw_coords() will return accurate results (otherwise, they will return NULL).
    
    \returns true, if it worked.
     */
    function set_can_see_through_the_fuzz(  $in_token_id    ///< REQUIRED: The new token for the "see clearly" IDs. 0 will clear this field.
                                        ) {
        $this->_load_data(false, true);
        
        $this->_object_data->can_see_through_the_fuzz = $in_token_id;
        
        $ret = $this->save_data();
    
        if ($ret) { // We force a reload, because fuzz.
            $this->_load_data(true, true);
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    This requires a "detailed" load.
    
    \returns the "raw" coordinates for a "fuzzy" location, assuming the current login has rights to them. If not, it returns NULL.
     */
    function raw_coords() {
        $ret = NULL;
        
        $this->_load_data(false, true);
        
        if (isset($this->_object_data) && isset($this->_object_data->raw_latitude) && isset($this->_object_data->raw_longitude)) {
            $ret = [];
            $ret['latitude'] = floatval($this->_object_data->raw_latitude);
            $ret['longitude'] = floatval($this->_object_data->raw_longitude);
        }
                
        return $ret;
    }
    
    /***********************/
    /**
    This requires a "detailed" load.
    
    \returns an associative array ('data' => binary data string, 'type' => string MIME type), containing the data in the payload, and its type. The data is not Base64-encoded.
     */
    function payload() {
        $ret = NULL;
        
        $this->_load_data(false, true);
        
        if (isset($this->_object_data) && isset($this->_object_data->payload)) {
            $payload_data = base64_decode($this->_object_data->payload);
            
            // We make sure that this was already Base64 before decoding.
            if (base64_encode(base64_decode($payload_data)) == $payload_data) {
                $payload_data = base64_decode($payload_data);
            }
            
            $ret = ['data' => $payload_data];
            
            if (isset($this->_object_data->payload_type)) {
                $ret['type'] = str_replace(';base64', '', $this->_object_data->payload_type);
            }
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    This adds (or removes) a payload from this object. It figures out the data type on its own.
    
    \returns true, if the operation succeeds.
     */
    function set_payload(   $in_payload_data    ///< REQUIRED (can be NULL). This is the data to set as the instance payload. It should NOT be Base64 encoded.
                        ) {
        $payload = NULL;
        $args = '';
        
        // We figure out the payload type by uploading to a file, then checking the file type.
        if ($in_payload_data) {
            $temp_file = tempnam(sys_get_temp_dir(), 'RVP');  
            file_put_contents($temp_file , $in_payload_data);
            $finfo = finfo_open(FILEINFO_MIME_TYPE);  
            $content_type = finfo_file($finfo, $temp_file);
            unlink($temp_file);
            $this->_object_data->payload_type = $content_type;
            $payload = ['data' => $in_payload_data, 'type' => $content_type];
        } else {
            $args = '&remove_payload';
        }
        
        // We circumvent our caller for this one.
        $ret = $this->_save_change_record(self::_save_data($args, $payload));
        
        if ($ret) {
            $ret = $this->_load_data(true, true);
        } else {
            $this->_load_data(true, true);
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    This requires a "detailed" load.
    
    \returns an associative array ('people' => integer array of IDs, 'places' => integer array of IDs, and 'things' => integer array of IDs), containing the IDs of any "child" objects for this object.
     */
    function children_ids() {
        $ret = [];
        
        $this->_load_data(false, true);
        
        if (isset($this->_object_data) && isset($this->_object_data->children)) {
            $child_data = (array)$this->_object_data->children;

            if (count($child_data)) {
                $ret = $child_data;
            }
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    This sets new child object IDs for this object.
    
    \returns true, if the operation succeeded.
     */
    function set_new_children_ids(  $in_child_ids   /**< REQUIRED:  The new children IDs. We do not separate these into different classes of ID. It's a simple integer array.
                                                                    This is a "delta" array. That means that what it contains are CHANGES.
                                                                    You ADD children by indicating positive integers.
                                                                    You DELETE children by indicating negative integers.
                                                                    If your login does not have the ability to read (write is not necessary) the child ID, then that ID is ignored.
                                                                    If the ID to be deleted is not in the record, then it is ignored.
                                                    */
                                ) {
        $ret = false;
        // We circumvent our caller for this one.
        $ret = $this->_save_change_record(self::_save_data('', NULL, $in_child_ids));
        
        if ($ret) {
            $ret = $this->force_reload();
        } else {    // If there was an error, then we don't send back the result (it may also be an error).
            $this->force_reload();
        }
        return $ret;
    }
    
    /***********************/
    /**
    This requires a "detailed and parents" load.
    
    **NOTE:** Calling this can incur a fairly significant performance penalty!
    
    \returns an associative array ('people' => integer array of IDs, 'places' => integer array of IDs, and 'things' => integer array of IDs), containing the IDs of any "parent" objects for this object.
     */
    function parent_ids() {
        $ret = NULL;
        
        $this->_load_data(false, true, true);
        
        if (isset($this->_object_data) && isset($this->_object_data->parents)) {
            $parent_data = $this->_object_data->parents;

            if (isset($parent_data) && is_array($parent_data) && count($parent_data)) {
                $ret = $parent_data;
            }
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    This requires a detailed data load.
    
    This returns a recursive hierarchy of instances for this object. It returns actual object instances; not IDs, using a simple tuple.
    
    \returns an associative array. One element will be 'object', and will refer to this object. If the object has child objects, then there will be a 'children' array of more of these nodes. Leaf nodes will contain only 'object' elements.
     */
    function get_hierarchy() {
        $ret = ['object' => $this];
        
        $this->_load_data(false, true);
        
        if (isset($this->_object_data) && isset($this->_object_data->children)) {
            $child_data = (array)$this->_object_data->children;
            
            if (count($child_data)) {
                $ret['children'] = [];
                foreach ($child_data as $plugin) {
                    if (count($plugin)) {
                        $objects = $this->_sdk_object->get_objects($plugin);
                
                        if (is_array($objects) && count($objects)) {
                            foreach ($objects as $object) {
                                if (method_exists($object, 'get_hierarchy')) {
                                    $ret['children'][] = $object->get_hierarchy();
                                }
                            }
                        }
                    }
                }
            }
        }
        
        return $ret;
    }
};
