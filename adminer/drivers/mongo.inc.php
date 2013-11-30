<?php
$drivers["mongo"] = "MongoDB";

if (isset($_GET["mongo"])) {
  $possible_drivers = array("mongo");
  define("DRIVER", "mongo");

  if (class_exists('MongoDB')) {
    class Min_DB {
      var $extension = "Mongo", $error, $_link, $_db;

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

      function query($query) {
        $query = str_replace("\r\n","\n", trim($query, ' ;'));
        $this->error = "";
        $pre = 'db.';

        if( substr( $query, 0, strlen($pre)) != $pre ) {
          $query = $pre . $query;
        }
        $result = $this->cmd($query);
        return $result;
      }

      function multi_query($query) {
        return $this->_result = $this->query($query);
      }

      function store_result() {
        return $this->_result;
      }

      function next_result() {
        return false;
      }

      function update($table, $set, $condition=array(), $limit=1) {
        $json = '';
        if( empty($condition) ) {
          $json .= '{}';
        } else {
          $json .= '{';
          foreach($condition as $key => $value) {
            if( $key == '_id' ) {
              $value = 'ObjectId(' . $value . ')';
            }
            $json .= $key . ':' . $value . ',';
          }
          $json = rtrim($json, ',') . '}';
        }

        $json .= ',{';
        foreach($set as $key => $value) {
          if( $key == '_id' ) continue;
          $json .= $key . ':' . $value . ',';
        }
        $json = rtrim($json, ',') . '}';
        $query = $table.'.update(' . $json . ')';
        $result = $this->query($query);
        return $result;
      }

      function dumpData($table='', $condition='') {
        $query = 'db.'.$table.'.find().toArray()';
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

      function quote($string) {
        return "'" . str_replace("'", "''", $string) . "'";
      }

    }

    class Min_Result {
      var $num_rows, $_rows = array(), $_offset = 0, $_charset = array();

      function Min_Result($result) {
        $single = !is_array(current($result))?true:false;

        foreach ($result as $tkey => $item) {
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
        $pos = strpos($where[0], '_id');
        if( $pos !== false && !$pos ) {
          $where[0] = preg_replace("/_id \= \'([0-9a-z]+)\'/", '$1', $where[0]);
          $query = 'findOne({_id: ObjectId("' . $where[0] . '")})';
        } else {
          $query = 'find()' . $skip.$order.$limit . '.toArray()';
        }
      } else {
        $query = 'find()' . $skip.$order.$limit . '.toArray()';
      }
      $cmd = 'db.'.$table.'.'.$query;
      $result = $connection->query($cmd);
      return $result;

      foreach ($connection->_db->selectCollection($table)->find(array(), $select) as $val) {
        $return[] = $val;
      }
      return new Min_Result($return);
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
    $cmd = 'db.'.$table.'.'.$query;

    $result = $connection->query($cmd);

    $result_array = array("_id" => array(
      "field" => "_id",
      "auto_increment" => true,
      "privileges" => array("select" => 1, "insert" => 1, "update" => 1),
    ));

    if( empty($result)) return $result_array;

    $result = $result->fetch_assoc();

    foreach($result as $key => $val ) {
      if( $key == '_id' ) continue;
      $result_array[$key] = array(
        "field" => $key,
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

  function alter_table($table, $name, $fields, $foreign, $comment, $engine, $collation, $auto_increment, $partitioning) {
    global $connection;
    if ($table == "") {
      $connection->_db->createCollection($name);
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
    return preg_match("~database|table|columns|sql|indexes|dump|drop_col~", $feature);
  }

  $jush = "mongo";
  $operators = array("=");
  $functions = array();
  $grouping = array();
  $edit_functions = array(
    array(
      "json"
    ),
  );
}