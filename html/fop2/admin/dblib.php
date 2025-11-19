<?php
class dbcon {

    private $host, $usuario, $clave, $dbname;

    private $link;

    private $result;

    public $debug=false;

    public $engine='mysql';

   /**
     * Constructor
     *
     * @param string $host host de la base de datos
     * @param string $usuario usuario de la base de datos
     * @param string $clave clave de la base de datos
     * @param string $dbname nombre de la base de datos
     * @param boolean $persistente si la conexion es persistente
     * @param  boolean $conectar_ya conectar ahora mismo
     * @return void
     */
    public function __construct($host, $usuario='', $clave='', $dbname='fop2', $persistente = true, $conectar_ya = true, $force_utf8 = false) {
        global $SQLDEBUG;
  
        $this->host    = $host;
        $this->usuario = $usuario;
        $this->clave   = $clave;
        $this->dbname  = $dbname;

        if ($conectar_ya) {
            $this->connect($persistente, $force_utf8);
        }

        $this->debug=isset($SQLDEBUG)?$SQLDEBUG:0;
       
        return;
    }

    /**
     * Destructor
     *
     * @return void
     */
    public function __destruct() {
        if($this->is_connected()) {
            $this->close($this->link);
        }
    }

    public function set_debug($dbg) {
        if($dbg) { $this->debug=true; } else {$this->debug=false; }
    }

    public function connect($persist = true, $force_utf8 = false) {

        if(preg_match("/sqlite/",$this->host)) {
            $file_db = new PDO($this->host);
            $file_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->link = $file_db;
            $this->engine = "sqlite";
            return true;
        } else {
            $link = new mysqli($this->host, $this->usuario, $this->clave, $this->dbname);

            if (!$link) {
                $this->link = false;
                trigger_error('No pudo conectar a la base de datos.', E_USER_ERROR);
            } else {
                if($force_utf8==true) {
                   $link->set_charset("utf8mb4");
                }
                $this->link = $link;
                return true;
            }
            $this->link = false;
            return false;
        }
    }

    /**
     * Consulta la base de datos
     *
     * @param string $query SQL query 
     * @return resource database result set
     */
    public function consulta($query) {

        $numargs = func_num_args();
        $args    = func_get_args();

        if ($numargs > 1){
            $query = $this->securize_query($args);
        }

        if($this->engine=='sqlite') {
            $result = $this->link->query($query);
            $this->result = $result;
            return $this->result;
        } else {
            $result = mysqli_query($this->link, $query);

            $this->result = $result;

            if ($result == false) {
                trigger_error('SQL Error: '.$query."\n" . $this->error() , E_USER_ERROR);
            } else if($this->debug) {
                $this->echodebug($query);
            } 

            return $this->result;
        }
    }

   /**
     * Update the database
     *
     * @param array $values 3D array of fields and values to be updated
     * @param string $table Table to update
     * @param string $where Where condition
     * @param string $limit Limit condition
     * @return boolean Result
     */
    public function update($values, $table, $where = false, $limit = false) {
        if (count($values) < 0) {
            return false;
        }

        $fields = array();

        foreach($values as $field => $val) {
            $fields[] = "`" . $field . "` = '" . $this->escape_string($val) . "'";
        }

        $where = ($where) ? " WHERE " . $where : '';
        $limit = ($limit) ? " LIMIT " . $limit : '';

        if ($this->consulta("UPDATE `" . $table . "` SET " . implode(", ",$fields) . $where . $limit)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Insert one new row
     *
     * @param array $values 3D array of fields and values to be inserted
     * @param string $table Table to insert
     * @return boolean Result
     */
    public function insert($values, $table) {
        if (count($values) < 0) {
            return false;
        }

        foreach($values as $field => $val) {
            $values[$field] = $this->escape_string($val);
        }

        if ($this->consulta("INSERT INTO `" . $table . "`(`" . implode("`, `",array_keys($values)) . "`) VALUES ('" . implode("', '",$values) . "')")) {
            return true;
        } else {
            return false;
        }
    }
   /**
     * Select
     *
     * @param mixed $fields Array or string of fields to retrieve
     * @param string $table Table to retrieve from
     * @param string $where Where condition
     * @param string $orderby Order by clause
     * @param string $limit Limit condition
     * @return array Array of rows
     */
    public function select($fields, $table, $joins = false, $where = false, $orderby = false, $limit = false) {

        if (is_array($fields)) {
            $fields = "`" . implode("`, `",$fields) . "`";
        }

        $orderby = ($orderby) ? " ORDER BY " . $orderby : '';
        $joins = ($joins) ? $joins : '';
        $where = ($where) ? " WHERE " . $where : '';
        $limit = ($limit) ? " LIMIT " . $limit : '';

        $res = $this->consulta("SELECT " . $fields . " FROM " . $table . ' ' . $joins. ' ' . $where . $orderby . $limit);

        if ($this->num_rows($res) > 0) {
            $rows = array();
            while ($r = $this->fetch_assoc($res)) {
                $rows[] = $r;
            }
            return $rows;
        } else {
            return false;
        }
    }

    /**
     * Selects one row
     *
     * @param mixed $fields Array or string of fields to retrieve
     * @param string $table Table to retrieve from
     * @param string $where Where condition
     * @param string $orderby Order by clause
     * @return array Row values
     */
    public function select_one($fields, $table, $joins = false, $where = false, $orderby = false) {
        $result = $this->select($fields, $table, $joins, $where, $orderby, '1');
        return $result[0];
    }
    /**
     * Selects one value from one row
     *
     * @param mixed $field Name of field to retrieve
     * @param string $table Table to retrieve from
     * @param string $where Where condition
     * @param string $orderby Order by clause
     * @return array Field value
     */
    public function select_one_value($field, $table, $joins = false, $where = false, $orderby = false) {
        $result = $this->select_one($field, $table, $joins, $where, $orderby);
        return $result[$field];
    }

    /**
     * Delete rows
     *
     * @param string $table Table to delete from
     * @param string $where Where condition
     * @param string $limit Limit condition
     * @return boolean Result
     */
    public function delete($table, $where = false, $limit = 1) {
        $where = ($where) ? " WHERE " . $where : '';
        $limit = ($limit) ? " LIMIT " . $limit : '';

        if ($this->consulta("DELETE FROM `" . $table . "`" . $where . $limit)) {
            return true;
        } else {
            return false;
        }
    }
    /**
     * Fetch results by associative array
     *
     * @param mixed $query Select query or database result
     * @return array Row
     */
    public function fetch_assoc($res) {
        //$this->res_type($query);
        if($this->engine=='sqlite') {
            return $res->fetch(PDO::FETCH_ASSOC);
        } else {
            if(gettype($res)=='object') {
                return mysqli_fetch_assoc($res);
            }
        }
    }

    /**
     * Fetch results by enumerated array
     *
     * @param mixed $query Select query or database result
     * @return array Row
     */
    public function fetch_row($res) {
        //$this->res_type($query);
        if(!isset($res)) { $res = $this->result; }
        return mysqli_fetch_row($res);
    }

    /**
     * Fetch one row
     *
     * @param mixed $query Select query or database result
     * @return array
     */
    public function fetch_one($res = false) {
        if(!isset($res)) { $res = $this->result; }
        list($result) = $this->fetch_row($res);
        return $result;
    }

    /**
     * Fetch a field name in a result
     *
     * @param mixed $query Select query or database result
     * @param int $offset Field offset
     * @return string Field name
     */
    public function field_name($res, $offset) {
        if(!isset($res)) { $res = $this->result; }
        return mysqli_fetch_field_direct($res, $offset)->name;
    }
   /**
     * Fetch all field names in a result
     *
     * @param mixed $query Select query or database result
     * @return array Field names
     */
    public function field_name_array() {
        $names = array();
        $field = $this->count_fields($this->result);
        for ( $i = 0; $i < $field; $i++ ) {
            $names[] = $this->field_name($this->result, $i);
        }
        return $names;
    }

    /**
     * Free result memory
     *
     * @return boolean
     */
    public function free_result() {
        return mysqli_free_result($this->result);
    }

    /**
     * Add escape characters for importing data
     *
     * @param string $str String to parse
     * @return string
     */
    public function escape_string($str) {
        if($this->engine=='sqlite') {
            return $this->link->quote($str);
        } else {
            return mysqli_real_escape_string($this->link, $str);
        }
    }

    public function securize_query($args) {

        $query = array_shift($args);
        
        if (count($args) > 0){
            $newval = array();
            foreach($args as $oldval) {
               if(is_array($oldval)) { 
                   foreach($oldval as $oldval_element) {
                       $newval[] = $this->escape_string($oldval_element);
                   }
               } else {
                   $newval[] = $this->escape_string($oldval);
               }
            }
            $query = vsprintf($query, $newval);
        }

        return $query;
    }


    private function populate_fields_table($table) {

        global $table_fields_cache;

        if(!$this->is_connected()) {
            return 0;
        }

        if(!isset($table_fields_cache)) { $table_fields_cache=array(); }

        if(isset($table_fields_cache[$table])) { 
           return $table_fields_cache; 
        }

        if($this->engine=='mysql') {
             $query = "SHOW tables LIKE '$table'";
             $res = $this->consulta($query);
             if($this->num_rows($res)>0) {
                $ris = $this->consulta("DESC $table");
             } else {
                $ris = $this->consulta("select '' from (select now() A) B where 1=0;");
             }
        } else if($this->engine=='postgres') {
             $ris = $this->consulta("SELECT column_name AS Field FROM information_schema.columns WHERE table_name='$table'");
        }

        while($row = $this->fetch_assoc($ris)) {
            $table_fields_cache[$table][$row['Field']]=1;

        }

        return $table_fields_cache;


    }

    public function table_exists($table) {

        $table_fields = $this->populate_fields_table($table);

        if(!$this->is_connected()) {
            return 0;
        }

        if(isset($table_fields[$table])) { return 1; } else { return 0; }

    }

    public function column_exists($table,$column) {

        $table_fields = $this->populate_fields_table($table);

        if(!$this->is_connected()) {
            return 0;
        }

        if(isset($table_fields[$table][$column])) { return 1; } else { return 0; }

    }

    /**
     * Count number of rows in a result
     *
     * @param mixed $result Select query or database result
     * @return int Number of rows
     */
    public function num_rows($result = false) {
        if($this->result) {
            return (int) mysqli_num_rows($this->result);
        } else {
            return 0;
        }
    }

    /**
     * Count number of fields in a result
     *
     * @param mixed $result Select query or database result
     * @return int Number of fields
     */
    public function count_fields() {
        return (int) mysqli_field_count($this->link);
    }


    /**
     * Get last inserted id of the last query
     *
     * @return int Inserted in
     */
    public function insert_id() {
        return (int) mysqli_insert_id($this->link);
    }

    /**
     * Get number of affected rows of the last query
     *
     * @return int Affected rows
     */
    public function affected_rows() {
        return (int) mysqli_affected_rows($this->link);
    }

    /**
     * Get the error description from the last query
     *
     * @return string
     */
    public function error() {
        return mysqli_error($this->link);
    }

   /**
     * Dump database info to page
     *
     * @return void
     */
    public function dump_info() {
        echo mysqli_info($this->link);
    }

    /**
     * Close the link connection
     *
     * @return boolean
     */
    public function close() {
        if($this->engine=='sqlite') {
            return;
        } else {
            return;
        }
    }

    /**
     * Determine the data type of a query
     *
     * @param mixed $result Query string or database result set
     * @return void
     */
    private function res_type(&$result,$args=array()) {
        if ($result == false) {
            $result = $this->result;
        } else {
            if (gettype($result) != 'resource') {
                $query = $this->securize_query($args);
                $result = $this->consulta($query);
            }
        }
        return;
    }

    public function seek($res,$pos) {
        mysqli_data_seek($res,$pos);
    }

    public function is_connected() {
          if(mysqli_connect_error()=='') {
              return true;
          }  else {
              return false;
          }
    }

    private function echodebug($str,$extraclass='DB') {

        if(!$this->debug) return;

        $numargs = func_num_args();
        $args    = func_get_args();

        if(strlen($str)==0) {
            return;
        }

        $text = '';
        $format = $this->charset_decode_utf8(array_shift($args));

        if ($numargs > 1){
            $text.= vsprintf($format, $args);
        } else {
            $text.= $format;
        }

        if (php_sapi_name() == 'cli' ) { 
            echo "$extraclass: $text\n"; 
        } else {
            j($extraclass.": ".$text);
        }

    }

    private function cback_utf($m) {
        return '&#'.((ord($m[1])-224)*4096 + (ord($m[2])-128)*64 + (ord($m[3])-128)).';';#
    }

    private function cback_utf2($m) {
        return '&#'.((ord($m[1])-192)*64+(ord($m[2])-128)).';';
    }

    private function charset_decode_utf8($string) {
        if (! preg_match("/[\200-\237]/", $string) and ! preg_match("/[\241-\377]/", $string)) {
            return $string;
        }

        $string = preg_replace_callback("/([\340-\357])([\200-\277])([\200-\277])/", array($this, "cback_utf"), $string);

        $string = preg_replace_callback("/([\300-\337])([\200-\277])/", array($this, "cback_utf2"), $string);

        return $string;
    }


}
