<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  +----------------------------------------------------------------------+
  | Issabel version 4.0                                                  |
  | http://www.issabel.org                                               |
  +----------------------------------------------------------------------+
  | Copyright (c) 2018 Issabel Foundation                                |
  +----------------------------------------------------------------------+
  | This program is free software: you can redistribute it and/or modify |
  | it under the terms of the GNU General Public License as published by |
  | the Free Software Foundation, either version 3 of the License, or    |
  | (at your option) any later version.                                  |
  |                                                                      |
  | This program is distributed in the hope that it will be useful,      |
  | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
  | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the        |
  | GNU General Public License for more details.                         |
  |                                                                      |
  | You should have received a copy of the GNU General Public License    |
  | along with this program.  If not, see <http://www.gnu.org/licenses/> |
  +----------------------------------------------------------------------+
  | The Initial Developer of the Original Code is Issabel LLC            |
  +----------------------------------------------------------------------+
  $Id: dahdichanneldids.php, Tue 04 Sep 2018 09:54:43 AM EDT, nicolas@issabel.com
*/

class dahdichanneldids extends rest {

    protected $table           = "dahdichandids";
    protected $id_field        = 'channel';
    protected $name_field      = 'description';
    protected $extension_field = '';
    protected $list_fields     = array('did');
    protected $search_field    = 'description';

    protected $provides_destinations = false;
    protected $category              = 'DAHDI Channel DIDs';

    protected $field_map = array(
        'did'             => 'did',
    );

    function post($f3,$from_child) {

        // As the channel field is a primary key and not auto increment, we cannot use the SQL Mapper 
        // inherited from the rest class for insertion

        $errors = array();

        $db  = $f3->get('DB');

        $loc = $f3->get('REALM');

        if($f3->get('PARAMS.id')<>'') {
            $errors[]=array('status'=>'400','detail'=>'We refuse to insert a record if a resource id is passed. For update use the PUT method instead.');
            $this->dieWithErrors($errors);
        }

        $input = $this->parseInputData($f3);
        $input = $this->flatten($input);
        $this->checkRequiredFields($input);

        // Transform values passed if needed
        $input = $this->transformValues($f3,$input);
        $input = $this->validateValues($f3,$input);

        // Set default values if not passed via request, defaults uses the mapped/human readable field name
        $input = $this->setDefaults($f3,$input);

        // Set real table field names
        $field_map_reverse = array_flip($this->field_map);
        foreach($input as $key=>$val) {
            if(array_key_exists($key,$field_map_reverse)) {
                unset($input[$key]);
                $input[$field_map_reverse[$key]]=$val;
            }
        }
        $query = "INSERT INTO dahdichandids (channel,description,did) VALUES (?,?,?)";

        try {
            $db->exec($query,array($input['id'],$input['description'],$input['did']));
            $this->applyChanges($input);
            // 201 CREATED
            header("Location: $loc/".$input['id'], true, 201);
            die();
        } catch(\PDOException $e) {
            $err = $e->errorInfo;
            $msg = $e->getMessage();

            if ($e->getCode() != 23000) {
                // when trying to insert duplicate
                $errors[]=array('status'=>'409','detail'=>$msg);
            } else {
                // on other errors
                $errors[]=array('status'=>'400','detail'=>$msg);
            }
            $this->dieWithErrors($errors);
        }

    }
}


