<?php
class dbcon {

    var $host, $usuario, $clave, $dbname;

    var $link;

    var $result;

    var $debug=false;

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
    function dbcon($host, $usuario, $clave, $dbname, $persistente = true, $conectar_ya = true) {
        $this->host    = $host;
        $this->usuario = $usuario;
        $this->clave   = $clave;
        $this->dbname  = $dbname;

        if ($conectar_ya) {
            $this->connect($persistente);
        }

        return;
    }

    /**
     * Destructor
     *
     * @return void
     */
    function __destruct() {
        $this->close();
    }

    function connect($persist = true) {
        if ($persist) {
            $link = mysql_pconnect($this->host, $this->usuario, $this->clave);
        } else {
            $link = mysql_connect($this->host, $this->usuario, $this->clave);
        }
        if (!$link) {
            trigger_error('No pudo conectar a la base de datos.', E_USER_ERROR);
        } else {
            $this->link = $link;
            if (mysql_select_db($this->dbname, $link)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Consulta la base de datos
     *
     * @param string $query SQL query 
     * @return resource database result set
     */
    function consulta($query) {

        $numargs = func_num_args();
        $args    = func_get_args();

        if ($numargs > 1){
            $query = $this->securize_query($args);
        }
        
        $result = mysql_query($query, $this->link);

        $this->result = $result;

	if ($result == false) {
	    if($this->debug) {
	        trigger_error('Error en la consulta SQL:<br/><br/><h3>'.$query.'</h3><br/>"' . $this->error() . '"', E_USER_ERROR);
	    }
        } else if($this->debug) {
            $this->echodebug($query);
        }

        return $this->result;
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
    function update($values, $table, $where = false, $limit = false) {
        if (count($values) < 0) {
            return false;
        }

        $fields = array();

        foreach($values as $field => $val) {
            $fields[] = "`" . $field . "` = '" . $this->escape_string($val) . "'";
        }

        $where = ($where) ? " WHERE " . $where : '';
        $limit = ($limit) ? " LIMIT " . $limit : '';

        if ($this->consulta("UPDATE `" . $table . "` SET " . implode($fields, ", ") . $where . $limit)) {
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
    function insert($values, $table) {
        if (count($values) < 0) {
            return false;
        }

        foreach($values as $field => $val) {
            $values[$field] = $this->escape_string($val);
        }

        if ($this->consulta("INSERT INTO `" . $table . "`(`" . implode(array_keys($values), "`, `") . "`) VALUES ('" . implode($values, "', '") . "')")) {
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
    function select($fields, $table, $joins = false, $where = false, $orderby = false, $limit = false) {

        if (is_array($fields)) {
            $fields = "`" . implode($fields, "`, `") . "`";
        }

        $orderby = ($orderby) ? " ORDER BY " . $orderby : '';
        $joins = ($joins) ? $joins : '';
        $where = ($where) ? " WHERE " . $where : '';
        $limit = ($limit) ? " LIMIT " . $limit : '';

        $this->consulta("SELECT " . $fields . " FROM `" . $table . "`" . $joins. ' ' . $where . $orderby . $limit);

        if ($this->num_rows() > 0) {
            $rows = array();
            while ($r = $this->fetch_assoc()) {
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
    function select_one($fields, $table, $joins = false, $where = false, $orderby = false) {
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
    function select_one_value($field, $table, $joins = false, $where = false, $orderby = false) {
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
    function delete($table, $where = false, $limit = 1) {
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
    function fetch_assoc($query = false) {
        $this->res_type($query);
        return mysql_fetch_assoc($query);
    }

    /**
     * Fetch results by enumerated array
     *
     * @param mixed $query Select query or database result
     * @return array Row
     */
    function fetch_row($query = false) {
        $this->res_type($query);
        return mysql_fetch_row($query);
    }

    /**
     * Fetch one row
     *
     * @param mixed $query Select query or database result
     * @return array
     */
    function fetch_one($query = false) {
        list($result) = $this->fetch_row($query);
        return $result;
    }

    /**
     * Fetch a field name in a result
     *
     * @param mixed $query Select query or database result
     * @param int $offset Field offset
     * @return string Field name
     */
    function field_name($query = false, $offset) {
        $this->res_type($query);
        return mysql_field_name($query, $offset);
    }
   /**
     * Fetch all field names in a result
     *
     * @param mixed $query Select query or database result
     * @return array Field names
     */
    function field_name_array($query = false) {
        $names = array();
        $field = $this->count_fields($query);
        for ( $i = 0; $i < $field; $i++ ) {
            $names[] = $this->field_name($query, $i);
        }
        return $names;
    }

    /**
     * Free result memory
     *
     * @return boolean
     */
    function free_result() {
        return mysql_free_result($this->result);
    }

    /**
     * Add escape characters for importing data
     *
     * @param string $str String to parse
     * @return string
     */
    function escape_string($str) {
        return mysql_real_escape_string($str, $this->link);
    }

    function securize_query($args) {
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


    /**
     * Count number of rows in a result
     *
     * @param mixed $result Select query or database result
     * @return int Number of rows
     */
    function num_rows($result = false) {
        $args = func_get_args();
        $this->res_type($result,$args);
        return (int) mysql_num_rows($result);
    }

    /**
     * Count number of fields in a result
     *
     * @param mixed $result Select query or database result
     * @return int Number of fields
     */
    function count_fields($result = false) {
        $this->res_type($result);
        return (int) mysql_num_fields($result);
    }

    /**
     * Get last inserted id of the last query
     *
     * @return int Inserted in
     */
    function insert_id() {
        return (int) mysql_insert_id($this->link);
    }

    /**
     * Get number of affected rows of the last query
     *
     * @return int Affected rows
     */
    function affected_rows() {
        return (int) mysql_affected_rows($this->link);
    }

    /**
     * Get the error description from the last query
     *
     * @return string
     */
    function error() {
        return mysql_error($this->link);
    }

   /**
     * Dump database info to page
     *
     * @return void
     */
    function dump_info() {
        echo mysql_info($this->link);
    }

    /**
     * Close the link connection
     *
     * @return boolean
     */
    function close() {
        return mysql_close($this->link);
    }

    /**
     * Determine the data type of a query
     *
     * @param mixed $result Query string or database result set
     * @return void
     */
    function res_type(&$result,$args=array()) {
        if ($result == false)
            $result = $this->result;
        else {
            if (gettype($result) != 'resource') {
                $query = $this->securize_query($args);
                $result = $this->consulta($query);
            }
        }
        return;
    }

    function echodebug($str,$extraclass='') {
        if(!$this->debug) return;
    
        $numargs = func_num_args();
        $args    = func_get_args();
    
        if(strlen($str)==0) {
            return;
        }
    
        echo "<div class='debug $extraclass'>$extraclass";
        $format = $this->charset_decode_utf8(array_shift($args));
    
        if ($numargs > 1){
            echo vsprintf($format, $args);
        } else {
            echo $format;
        }

        echo "</div>";
    }

    function charset_decode_utf8($string) {
        if (! ereg("[\200-\237]", $string) and ! ereg("[\241-\377]", $string))
            return $string;

        $string = preg_replace("/([\340-\357])([\200-\277])([\200-\277])/e",
        "'&#'.((ord('\\1')-224)*4096 + (ord('\\2')-128)*64 + (ord('\\3')-128)).';'",
        $string);

        $string = preg_replace("/([\300-\337])([\200-\277])/e",
        "'&#'.((ord('\\1')-192)*64+(ord('\\2')-128)).';'",
        $string);

        return $string;
    }

}
