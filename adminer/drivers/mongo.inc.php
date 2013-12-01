<?php
$drivers["mongo"] = "MongoDB";

/*
BSON Data Types
http://docs.mongodb.org/manual/reference/operator/query/type/

Double 	1
String 	2
Object 	3
Array 	4
Binary data 	5
Undefined (deprecated) 	6
Object id 	7
Boolean 	8
Date 	9
Null 	10
Regular Expression 	11
JavaScript 	13
Symbol 	14
JavaScript (with scope) 	15
32-bit integer 	16
Timestamp 	17
64-bit integer 	18
Min key 	255
Max key 	127
*/

if (isset($_GET["mongo"])) {
  $possible_drivers = array("mongo");
  define("DRIVER", "mongo");

  if (class_exists('MongoDB')) {
    class Min_DB {
      var $extension = "Mongo", $error, $_link, $_db, $mbuffer='';
      var $field_types = array(
        1 => array('name' => 'Double'),
        3 => array('name' => 'Object'),
        4 => array('name' => 'Array'),
        5 => array('name' => 'Binary Data'),
        6 => array('name' => 'undefined'),
        7 => array('name' => 'ObjectId'),
        8 => array('name' => 'Boolean'),
        9 => array('name' => 'Date'),
        10 => array('name' => 'Null'),
        11 => array('name' => 'Regular Expression'),
        13 => array('name' => 'Javascript'),
        14 => array('name' => 'Symbol'),
        15 => array('name' => 'Javascript scoped'),
        16 => array('name' => '32bit Integer'),
        17 => array('name' => 'Timestamp'),
        18 => array('name' => '64bit Integer'),
        255 => array('name' => 'Min Key'),
        127 => array('name' => 'Max Key'),
        2 => array('name' => 'String'),
      );

      var $field_to_php = array(
       'Double' => 'float',
       'Object' => 'object',
       'Array' => 'array',
       'Boolean' => 'boolean',
       'Null' => 'null',
       '32bit Integer' => 'integer',
       '64bit Integer' => 'integer',
       'String' => 'string',
      );

      function connect($server, $username, $password) {
        global $adminer;
        $db = $adminer->database();
        $options = array();
        if ($username != "") {
          $options["username"] = $username;
          $options["password"] = $password;
        }
        if ($db != "") {
          $options["db"] = $db;
        }
        try {
          $this->_link = new MongoClient("mongodb://$server", $options);
          return true;
        } catch (Exception $ex) {
          $this->error = $ex->getMessage();
          return false;
        }
      }

      function cmd($cmd) {
        $result = false;
        $data = $this->_db->command(array('$eval' => $cmd, 'nolock' => true));

        if( $data['ok'] && isset($data['retval']) ) {
          $result = new Min_Result($data['retval']);
        } else {
          $this->errno = $data['code'];
          $this->error = $data['errmsg'];
        }
        return $result;
      }

      function query($query, $multi=false) {
        $process_cmd = false;

        if( substr(trim($query), -1) == ';' || !$multi ) {
          $process_cmd = true;
        }

        $query = str_replace("\r\n","\n", trim($query, ' ;'));
        $this->error = "";
        $pre = $multi?'':'db.';

        if( !empty($pre) && substr( $query, 0, strlen($pre)) != $pre ) {
          $query = $pre . $query;
        }

        $this->mbuffer .= $query . "\n";

        if( $process_cmd ) {
          $result = $this->cmd(trim($this->mbuffer));
          $this->mbuffer = '';
          return $result;
        } else {
          return false;
        }
      }

      function multi_query($query) {
        return $this->_result = $this->query($query, true);
      }

      function store_result() {
        return $this->_result;
      }

      function next_result() {
        return false;
      }

      function update($table, $set, $condition=array(), $limit=1) {
        $json = $this->conditionToJson($condition);
        $json .= ',{';

        foreach($set as $key => $value) {
          if( $key == '_id' ) continue;
          $type = $this->scan_type($table, $key);
          if($type != 'undefined' && isset($this->field_to_php[$type]) ) {
            $value = tep_convert_type($value, $this->field_to_php[$type]);
          }
          $json .= $key . ':' . $value . ',';
        }

        $json = rtrim($json, ',') . '}';
        $query = $table.'.update(' . $json . ')';
        $result = $this->query($query);
        return $result;
      }

      function delete($table, $condition='', $limit=1) {
        $json = $this->conditionToJson($condition);
        $query = $table.'.remove(' . $json . ')';
        $result = $this->query($query);
        return $result;
      }

      function dumpData($table, $condition=array(), $limit=0) {
        $json = $this->conditionToJson($condition, true);
        $query = $table.'.find(' . $json . ').toArray()';
        $result = $this->query($query);
        return $result;
      }

      function select_db($database) {
        try {
          $this->_db = $this->_link->selectDB($database);
          return true;
        } catch (Exception $ex) {
          $this->error = $ex->getMessage();
          return false;
        }
      }

      function conditionToJson($condition, $leave_empty=false) {
        $json = '';
        if( empty($condition) && $leave_empty ) return $json;
        if( empty($condition) ) return '{}';

        $opMap = array(
          'OR' => '$or',
          'AND' => '$and',
          'NO' => ''
        );
        $json .= '{';
        foreach($condition as $op => $entries) {
          $opc = $opMap[$op];
          if( !empty($opc) ) {
            $json .= $opc .': [';
          } else {
            $entries = array($entries);
          }
          for($i=0, $j=count($entries); $i<$j; $i++) {
            $key = key($entries[$i]);
            $value = current($entries[$i]);
            if( $key == '_id' ) {
              $value = 'ObjectId(' . $value . ')';
            }
            if( !empty($opc) ) {
              $json .= '{' . $key . ':' . $value . '},';
            } else {
              $json .= $key . ':' . $value . ',';
            }
          }
          if( !empty($opc) ) {
            $json = rtrim($json, ',');
            $json .= ']';
          }
        }
        $json = rtrim($json, ',') . '}';
        return $json;
      }

      function scan_type($table, $field_name) {
        foreach($this->field_types as $key => $value) {
          $query = 'find({' . $field_name . ': {$exists: true, $type: ' . $key . '}}, {' . $field_name . ': 1}).limit(1).toArray()';
          $cmd = $table.'.'.$query;

          $result = $this->query($cmd);
          if( !$result || !$result->num_rows || !is_array($result->_rows[0]) ) continue;
          return $value['name'];
        }
        return $this->field_types[6]['name'];
      }

      function quote($string) {
        return "'" . str_replace("'", "''", $string) . "'";
      }
    }

    class Min_Result {
      var $num_rows, $_rows = array(), $_offset = 0, $_charset = array();

      function Min_Result($result) {

        if( !is_array($result) && !is_object($result) ) $result = array($result);

        $single = !is_array(current($result))?true:false;

        foreach( $result as $tkey => $item ) {
          $row = array();

          if( !is_array($item) ) $item = array($tkey => $item);

          foreach ($item as $key => $val) {

            if (is_a($val, 'MongoBinData')) {
              $this->_charset[$key] = 63;
            }

            $row[$key] =
              (is_a($val, 'MongoId') ? $val->{'$id'} :
              (is_a($val, 'MongoDate') ? gmdate("Y-m-d H:i:s", $val->sec) . " GMT" :
              (is_a($val, 'MongoBinData') ? $val->bin : //! allow downloading
              (is_a($val, 'MongoRegex') ? strval($val) :
              (is_object($val) ? get_class($val) : // MongoMinKey, MongoMaxKey
              $val
            )))));
          }

          $this->_rows[] = $row;

          foreach ($row as $key => $val) {
            if( $single ) {
              $this->_rows[0][$key] = $val;
            } elseif( !isset($this->_rows[0][$key]) ) {
              $this->_rows[0][$key] = null;
            }
          }
        }
        if( $single ) $this->_rows = array($this->_rows[0]);
        $this->num_rows = count($this->_rows);
      }

      function fetch_assoc() {
        $row = current($this->_rows);
        if (!$row) {
          return $row;
        }
        $return = array();
        foreach ($this->_rows[0] as $key => $val) {
          if( is_array($row[$key]) ) {
            $return[$key] = json_encode($row[$key]);
          } else {
            $return[$key] = $row[$key];
          }
        }
        next($this->_rows);
        return $return;
      }

      function fetch_row() {
        $return = $this->fetch_assoc();
        if (!$return) {
          return $return;
        }
        return array_values($return);
      }

      function fetch_field() {
        $keys = array_keys($this->_rows[0]);
        $name = $keys[$this->_offset++];
        return (object) array(
          'name' => $name,
          'charsetnr' => $this->_charset[$name],
        );
      }
    }
  }


  class Min_Driver extends Min_SQL {
    function select($table, $select, $where, $group, $order, $limit, $page) {
      global $connection;

      if ($select == array("*")) {
        $select = array();
      } else {
        $select = array_fill_keys($select, true);
      }

      $return = array();
      $tmp = explode(' ', $order[0]);

      $order = empty($order)?'':'.sort({'. $tmp[0] . ':' . (($tmp[1] == 'DESC')?-1:1) . '})';
      $skip = !($page*$limit)?'':'.skip(' . (int)$page*$limit . ')';
      $limit = empty($limit)?'':'.limit(' . (int)$limit . ')';

      if( !empty($where) ) {
        $where = tep_convert_where_to_array($where[0], '');
        $where = $connection->conditionToJson($where);

        $pos = strpos($where, '_id');
        if( $pos !== false && !$pos ) {
          $id = preg_replace("/_id \= \'([0-9a-z]+)\'/", '$1', $where);
          $query = 'findOne({_id: ObjectId("' . $id . '")})';
        } else {
          $json = $where;
          $query = 'find(' . $json . ')' . $skip.$order.$limit . '.toArray()';
        }
      } else {
        $cols = '{}';
        if( !empty($select) ) {
          $cols = json_encode($select);
        }
        $query = 'find({}, ' . $cols . ')' . $skip.$order.$limit . '.toArray()';
      }
      $cmd = $table.'.'.$query;
      $result = $connection->query($cmd);
      return $result;
    }
  }

  function connect() {
    global $adminer;
    $connection = new Min_DB;
    $credentials = $adminer->credentials();
    if ($connection->connect($credentials[0], $credentials[1], $credentials[2])) {
      return $connection;
    }
    return $connection->error;
  }

  function error() {
    global $connection;
    return h($connection->error);
  }

  function logged_user() {
    global $adminer;
    $credentials = $adminer->credentials();
    return $credentials[1];
  }

  function get_databases($flush) {
    global $connection;
    $return = array();
    $dbs = $connection->_link->listDBs();
    foreach ($dbs['databases'] as $db) {
      $return[] = $db['name'];
    }
    return $return;
  }

  function collations() {
    return array();
  }

  function db_collation($db, $collations) {
  }

  function count_tables($databases) {
    return array();
  }

  function tables_list() {
    global $connection;
    return array_fill_keys($connection->_db->getCollectionNames(true), 'table');
  }

  function table_status($name = "", $fast = false) {
    $return = array();
    foreach (tables_list() as $table => $type) {
      $return[$table] = array("Name" => $table);
      if ($name == $table) {
        return $return[$table];
      }
    }
    return $return;
  }

  function information_schema() {
  }

  function is_view($table_status) {
  }

  function drop_databases($databases) {
    global $connection;
    foreach ($databases as $db) {
      $response = $connection->_link->selectDB($db)->drop();
      if (!$response['ok']) {
        return false;
      }
    }
    return true;
  }

	function auto_increment() {
    return '';
  }

  function indexes($table, $connection2 = null) {
    global $connection;
    $return = array();
    foreach ($connection->_db->selectCollection($table)->getIndexInfo() as $index) {
      $descs = array();
      foreach ($index["key"] as $column => $type) {
        $descs[] = ($type == -1 ? '1' : null);
      }
      $return[$index["name"]] = array(
        "type" => ($index["name"] == "_id_" ? "PRIMARY" : ($index["unique"] ? "UNIQUE" : "INDEX")),
        "columns" => array_keys($index["key"]),
        "descs" => $descs,
      );
    }
    return $return;
  }

  function fields($table) {
    global $connection;
    $id = isset($_GET['where']) && isset($_GET['where']['_id'])?$_GET['where']['_id']:'';

    if( !empty($id) ) {
      $query = 'findOne({_id: ObjectId("' . $id . '")})';
    } else {
      $query = 'find().toArray()';
    }
    $cmd = $table.'.'.$query;
    $result = $connection->query($cmd);

    $result_array = array("_id" => array(
      "field" => "_id",
      "type" => 'ObjectId',
      "length" => 64,
      "auto_increment" => true,
      "privileges" => array("select" => 1, "insert" => 1, "update" => 1),
    ));

    if( empty($result)) return $result_array;

    $result = $result->fetch_assoc();
    if( empty($result) ) return array();

    $val = '';
    foreach($result as $key => $val ) {
      if( $key == '_id' ) continue;

      $field_type = $connection->scan_type($table, $key);
      $result_array[$key] = array(
        "field" => $key,
        "type" => $field_type,
        "privileges" => array("select" => 1, "insert" => 1, "update" => 1),
      );
    }
    return $result_array;
  }

  function convert_field($field) {
  }

  function unconvert_field($field, $return) {
    return $return;
  }

  function foreign_keys($table) {
    return array();
  }

  function fk_support($table_status) {
  }

  function engines() {
    return array();
  }

  function found_rows($table_status, $where) {
    return null;
  }

	/** Get user defined types
	* @return array
	*/
	function types() {
		return array();
	}

  function alter_table($table, $name, $fields, $foreign, $comment, $engine, $collation, $auto_increment, $partitioning) {
    global $connection;
    if( empty($table) ) {
      $table = $name;
      $query = $table.'.insert({})';
      $result = $connection->query($query);
      if( !$result ) return false;
    }

    foreach($fields as $field_data) {
      $new_field = false;
      $control = '$set';
      $field_name = $field_data[0];

      if( empty($field_name) && isset($field_data[1][0]) ) {
        $new_field = true;
        $field_name = $field_data[1][0];
      }
      $field_type = trim($field_data[1][1]);

      if( isset($field_data[1][0]) && $field_name != $field_data[1][0] ) {
        $field_name = $field_data[1][0];
        $control = '$rename';
      }

      if( !empty($field_name) && !isset($field_data[1][0]) ) {
        $json = '{' . $field_name . ': {$exists: true}}, {$unset: {"' . $field_name. '": true}}';
        $query = $table.'.update(' . $json . ', false, true)';
        $result = $connection->query($query);
        if( !$result ) return false;
        continue;
      }

      if( $control == '$rename' ) {
        $json = '{}, { $rename: {"' . $field_data[0]. '": "' . $field_name . '"}}';
        $query = $table.'.update(' . $json . ', false, true)';
        $result = $connection->query($query);
        if( !$result ) return false;
        $control = '$set';
      }

      if( !isset($connection->field_to_php[$field_type]) ) continue;

      $query = 'find({' . $field_name . ': {$exists: true}}).toArray()';
      $cmd = $table.'.'.$query;
      $result = $connection->query($cmd);

      if( !$result ) return false;

      $default = $field_data[1][3];
      if( !empty($default) ) {
        $tmp_array = explode(' DEFAULT ', $default);
        $default = trim($tmp_array[1]);
      }

      foreach($result->_rows as $entry) {
        if( empty($entry) ) {
          $entry = array($field_name => $default);
        }

        if( $new_field ) {
          $json = '{},';
        } else {
          $json = '{' . $field_name . ':' . tep_convert_type($entry[$field_name]) . '},';
        }
        $value = $entry[$field_name];
        $json .= '{ $set: {' . $field_name . ': ' . tep_convert_type($value, $connection->field_to_php[$field_type]) . '}}';
        $query = $table.'.update(' . $json . ')';
        $result = $connection->query($query);
      }
      return true;
    }
  }

  function drop_tables($tables) {
    global $connection;
    foreach ($tables as $table) {
      $response = $connection->_db->selectCollection($table)->drop();
      if (!$response['ok']) {
        return false;
      }
    }
    return true;
  }

  function truncate_tables($tables) {
    global $connection;
    foreach ($tables as $table) {
      $response = $connection->_db->selectCollection($table)->remove();
      if (!$response['ok']) {
        return false;
      }
    }
    return true;
  }

  function table($idf) {
    return $idf;
  }

  function idf_escape($idf) {
    return $idf;
  }

  function support($feature) {
//    return preg_match('~^(database|table|columns|sql|indexes|view|trigger|variables|status|dump|move_col|drop_col)$~', $feature);
    return preg_match("~database|table|columns|type|sql|indexes|dump|drop_col~", $feature);
  }

  $jush = "mongo";
	$types = array(); ///< @var array ($type => $maximum_unsigned_length, ...)
	$structured_types = array(); ///< @var array ($description => array($type, ...), ...)
	foreach (array(
		lang('Strings') => array("String" => 16777215, "ObjectId" => 64),
		lang('Numbers') => array("Double" => 16, "32bit Integer" => 8, "64bit Integer" => 16),
		lang('Date and time') => array("ISODate" => 255),
		lang('Lists') => array("Array" => 16777215, "Javascript" => 16777215),
		lang('Binary') => array("BinData" => 16777215),
	) as $key => $val) {
		$types += $val;
		$structured_types[$key] = array_keys($val);
	}

  $operators = array("=");
  $functions = array();
  $grouping = array();
  $edit_functions = array(
    array(
      "json"
    ),
  );
}