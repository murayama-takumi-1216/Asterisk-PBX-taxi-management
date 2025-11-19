<?php
ob_start();

class dbgrid {

    var $link;

    var $table;

    var $caption;

    var $condition = " WHERE 1=1 ";

    var $join = "";

    var $search = "";

    var $per_page = 20;

    var $fields="*";

    var $orderby="";

    var $sortdir="ASC";

    var $res_request=array();

    var $structure=array();

    var $force_import_field=array();

    var $page=1;

    var $pagination_anchors = "";

    var $pagination_total = "";

    var $field_name = array();

    var $field_hide = array();

    var $field_search = array();

    var $field_no_edit = array();

    var $field_edit_condition = array();

    var $validation_callbacks = array();

    var $validation_type = array();

    var $delete_callbacks = array();

    var $insert_callbacks = array();

    var $update_callbacks = array();

    var $debug=true;

    var $stopnow=false;

    var $pk="";

    var $dbgrid_action;

    var $modify_pk=false;

    var $allow_view=true;

    var $allow_add=true;

    var $allow_delete=true;

    var $allow_edit=true;

    var $allow_export=true;

    var $allow_import=true;

    var $allow_search=false;

    var $salt="superSecret";

    var $notice=array();

    var $error=array();

    var $user_directory="./uploads/";
    
    var $name_upload;

    var $old_name_upload;

   /**
     * Constructor
     *
     * @param string $link
     * @return void
     */
    function dbgrid($link) {
        $this->link    = $link;
        $this->get_request();
        return;
    }

    /**
     * Destructor
     *
     * @return void
     */
    function __destruct() {
        // nada
        return false;
    }

    function get_request() {
        $get_post = array_merge($_GET,$_POST);


/*
        echo "<pre>";
        print_r($_GET);
        print_r($get_post);
        print_r($_POST);
        print_r($_REQUEST);
        echo "</pre>";
*/

        $cancel="";
        $ok="";
        foreach($get_post as $key=>$val) {
            if($key=='dbgrid_page') {
                $this->page = intval($val);
            } elseif($key=='per_page') {
                $this->per_page = intval($val);
            } elseif($key=='dbgrid_sort') {
                $this->orderby=$val;
            } elseif($key=='dbgrid_sortdir') {
                $this->sortdir=$val;
            } elseif($key=='dbgrid_action'){
                $this->dbgrid_action=$val;
            } elseif($key=='dbgrid_notice'){
                $this->notice[]=$val;
            } elseif($key=='dbgrid_error'){
                $this->error[]=$val;
            } elseif($key=='dbgrid_search'){
                $this->search=$val;
            }
            if($key<>"button_ok" && $key <>"button_cancel" && 
               $key<>"dbgrid_notice" && $key<>"dbgrid_error" && $key<>'dbgrid_pkhash') {
                $this->res_request[$key] = $val;
            }
            if($key=="button_cancel") {
                $cancel=$val;
            }
            if($key=="button_ok") {
                $ok=$val;
            }
        }
        if($cancel<>"" || $ok<>"" ) {
            if($cancel<>"") {
                $req = $this->set_request("dbgrid_action","list",true);
                $this->dbgrid_action="list";
            }
            // Si cancelamos algun form, hay que borar todos los campos de la tabla
            $table = ($cancel<>"")?$cancel:$this->table;
//            echo "mi tabla es $table<br>";
            $this->set_table($table);

            foreach ($this->structure as $field => $value){
                if(array_key_exists($field,$this->res_request)) {
                    if($ok<>"" && $field == $this->pk) {
                       // no borramos el id del request
                    } else {
                        unset($this->res_request[$field]);
                    }
                }
            }
        }
    }
    function del_request($param) {
        unset($this->res_request[$param]);
        $ret = array();
        $return = "";
        foreach($this->res_request as $key=>$val) {
            $ret[]="$key=$val";
        }
        $return = implode("&",$ret);
        if($return<>"") {
            $return ="?".$return;
        }
        return $return;
    }
    function set_request($param,$value,$store=true) {
        $ret = array();
        if($store) {
            $this->res_request[$param]=$value;
        }
        
        $return = "";
        $estaba=0;
        foreach($this->res_request as $key=>$val) {
            if((!$store) && $param == $key) {
                $estaba=1;
                $ret[]="$param=$value";
            } else {
                $ret[]="$key=$val";
            }
        }
        if($estaba==0) {
            if(!array_key_exists($param,$this->res_request)) {
                $ret[]="$param=$value";
            }
        }
        $return = implode("&",$ret);
        if($return<>"") {
            $return ="?".$return;
        }
        return $return;
    }

    //Permite habilitar la modificacion de PK
    function change_pk($modify){
        $this->modify_pk=$modify;
    }

    //Permite setear un directorio para administrar archivos
    function set_user_directory($directory){
        $this->user_directory=$directory;
    }

    //Permite habilitar el boton agregar
    function allow_add($allow_add){
        $this->allow_add=$allow_add;
    }

    //Permite habilitar el boton ver
    function allow_view($allow_view){
        $this->allow_view=$allow_view;
    }


    //Permite habilitar el boton borrar
    function allow_delete($allow_delete){
        $this->allow_delete=$allow_delete;
    }

    //Permite habilitar el boton editar 
    function allow_edit($allow_edit){
        $this->allow_edit=$allow_edit;
    }

    //Permite habilitar el boton exportar 
    function allow_export($allow_export){
        $this->allow_export=$allow_export;
    }

    //Permite habilitar el boton importar
    function allow_import($allow_import){
        $this->allow_import=$allow_import;
    }

    //Buscar
    function allow_search($allow_search){
       $this->allow_search=$allow_search;
    }

    function salt($salt){
        $this->salt=$salt;
    }

    function table_row($datos,$extraclass='',$key) {
        if($extraclass<>'') {
            $extraclass=" class='$extraclass'";
        }
        $ret = "<tr$extraclass>\n";
        $ret.="<td style='width: 80px;'>";

        $req=$this->set_request($this->pk,$key,true);
        if($this->allow_view) {
            $req=$this->set_request('dbgrid_action','show',true);
            $ret.="<a href='".SELF."$req' style='float:left;'><div class='icon_show'></div></a></div>";
        }

        if($this->allow_edit) {
            $req=$this->set_request('dbgrid_action','edit',true);
            $ret.="<a href='".SELF."$req' style='float:left;'><div class='icon_edit'></div></a></div>";
        }

        if($this->allow_delete) {
            $req=$this->set_request('dbgrid_action','delete',true);
            $req=$this->set_request('dbgrid_pkhash',md5($this->salt.$key),false);
            $ret.="<a href='".SELF."$req' style='float:left;' onclick='return confirm(\"".trans("Are you sure?")."\");'><div class='icon_delete'></div></a></div>";
        }

        $this->del_request("dbgrid_action");
        $this->del_request($this->pk);

        $ret.="</td>";
        $ret.="<th class='tcheck'><input type=checkbox id='dbgrid_checkbox_".$key."'></th>";

        if(is_array($datos)) {
            $this->apply_display_filters($datos);
            foreach($datos as $campo=>$valor) {
                if (empty($this->structure[$campo]['display_filter'])) {
                    $valor = $this->clean_entities($valor);
                }
                $ret.="<td style='width:auto;'>$valor</td>";
            }
        } else {
            $datos = $this->clean_entities($datos);
            $ret.="<td>$datos</td>";
        }
        $ret .= "\n</tr>\n";
        return $ret;
    }

    
    function table_head($datos,$orden) {
        $ret = "<tr>\n";
        $ret.="<th class='tcontrol'>&nbsp;</th>";
        $ret.="<th class='tcheck'><input type=checkbox id='checkall' onclick='checkAll()'></th>";
        if(is_array($datos)) {
            $cont=0;
            foreach($datos as $valor) {
                $valor = $this->clean_entities($valor);
                $req_uri = $this->set_request("dbgrid_sort",$orden[$cont],false);
                if($orden[$cont]==$this->orderby) {
                    $newdir = ($this->sortdir == "ASC")?"DESC":"ASC";
                    $arrow = ($this->sortdir == "ASC")?"sort_des":"sort_asc";
                    $req_uri = $this->set_request("dbgrid_sortdir",$newdir,false);
                    $add_arrow="<div class='$arrow'>&nbsp;</div>";
                } else {
//                    $req_uri = $this->del_request("dir");
                    $add_arrow="";
                }
                $ret.="<th>$add_arrow<div style='float:left;'><a href='".SELF."$req_uri'>$valor</a></div></th>";
                $cont++;
            }
        } else {
            $datos = $this->clean_entities($datos);
            $ret.="<th><a href='".SELF."'>$datos</a></th>";
        }
        $ret .= "\n</tr>\n";
        return $ret;
    }


    function set_table($table) {
        if($this->table) { return; }
        if(!$table) { return; }
        $this->table = $table;
        //Tipos de datos en mysql        
        $basetypes = 'real|double|float|decimal|numeric|tinyint|smallint|mediumint|int|bigint|date|time|timestamp|datetime|char|varchar|tinytext|text|mediumtext|longtext|enum|set|tinyblob|blob|mediumblob|longblob';
        $extra     = 'unsigned|zerofill|binary|ascii|unicode| ';
     
        $res = $this->link->consulta("DESC $table");

        if(!$res) {
            $this->add_error(mysql_real_escape_string($this->link->error()));
            $this->stopnow = true;
            $this->print_grid();
            
            die();
        }

        while($row = $this->link->fetch_assoc()) {
            preg_match("#^($basetypes)(\([^)]+\))?($extra)*$#i", $row['Type'], $matches);
                // Tipo de dato?
            switch ($matches[1]) {
                case 'smalltext':
                case 'mediumtext':
                case 'text':
                case 'longtext':
                    $this->add_structure($row['Field'], 'textarea');
                    break;
                case 'enum':
                    $type   = substr($row['Type'], 6, -2);
                    $values = array_flip(preg_split("#','#", $type));
                    foreach ($values as $k => $v) {
                        $values[$k] = $k;
                    }
                    $this->add_structure($row['Field'], 'select', $values,$row['Default']);
                    break;
                case 'date':
                    $this->add_structure($row['Field'], 'date', null, date('Y-m-d'));
                    break;
                case 'time':
                    $this->add_structure($row['Field'], 'time', null, date('H:i:s'));
                    break;
                case 'datetime':
                    $this->add_structure($row['Field'], 'datetime', null, date('Y-m-d H:i:s'));
                    break;
                default:
                    $this->add_structure($row['Field'], 'text',null,$row['Default']);
           }
            //$this->structure[$row['Field']]=$row;
            if ($row['Key'] == 'PRI'){
                $this->pk = $row['Field'];
            }
        }
    }

    function force_import_field($field,$value){
        $this->force_import_field[$field] = $value;
    }

    function add_structure($name, $inputType, $values = null, $default = null, $instructions = null){
        $this->structure[$name] = array ('display'        => $name,
                                         'input'          => $inputType,
                                         'values'         => $values,
                                         'display_filter' => array(),
                                         'edit_filter'    => array(),
                                         'instructions'   => $instructions,
                                         'default'        => $default);
    }

    function set_caption($caption) {
        $this->caption = $caption;
    }

    function set_orderby($orderby) {
        if(!$this->orderby) {
            $this->orderby = $orderby;
        }
    }

    function set_join($join) {
        $this->join = $join;
    }

    function set_condition($condition) {
        $this->condition = "WHERE ".$condition." ";
    }

    function set_search($search) {
        $this->search = $search;
    }

    function set_input_type($campo,$tipo,$valores='') {
        if(array_key_exists($campo,$this->structure)) {
            $this->structure[$campo]['input']=$tipo;
            if(isset($valores)) {
                $this->structure[$campo]['values']=$valores;
            } 
        }
    }
    function set_fields($fields) {
        $this->fields = $fields;
    }

    function set_per_page($perpage) {
        $this->per_page = $perpage;
    }

    function clean_entities($param) {
        return is_array($param) ? array_map('clean_entities', $param) : htmlspecialchars($param, ENT_QUOTES);
    }

    function construct_count_query() {
        $query = "SELECT count(*) FROM ".$this->table." ";
        if($this->condition <> "") {
            $query .= $this->condition;
        }
        if($this->search <> "") {
            $query_parts=array();

            foreach ($this->field_search as $key=>$val ){
                $query_parts[]= "$key LIKE '%%".$this->search."%%'";
            }
            if(count($query_parts) > 0) {
                $searchquery=implode(" OR ",$query_parts);
                $query .= " AND ( ".$searchquery.") ";
            }

        }
        return $query;
    }

    function construct_query($limit=true) {
        $vars   = Array();
        $return = Array();

        if($this->stopnow) {
            $return[] = "";
            $return[] = $vars;
            return $return;
        } 

        $query = "SELECT ".$this->fields." FROM ".$this->table." ";
//        $vars[]=$this->fields;
//        $vars[]=$this->table;

        if($this->join <> "") {
           $query .= " ".$this->join." ";
        }

        if($this->condition <> "") {
           $query .= $this->condition;
        }

        if($this->search <> "") {
            $query_parts=array();
            foreach ($this->field_search as $key=>$val ){
                $query_parts[]= "$key LIKE '%%".$this->search."%%'";
            }
            if(count($query_parts) > 0) {
                $searchquery=implode(" OR ",$query_parts);
                $query .= " AND (".$searchquery.") ";
            }
        }

        if(array_key_exists('dbgrid_sort',$this->res_request)) {
               $query .= " ORDER BY %s %s ";
               $vars[]=$this->orderby;
               $vars[]=$this->sortdir;
        } else {
            if($this->orderby <> "") {
                $query .= " ORDER BY %s %s ";
                $vars[]=$this->orderby;
                $vars[]=$this->sortdir;
            }
        }
        if($limit) {
            $start_record = ($this->page * $this->per_page) - $this->per_page ;
            $query .= " LIMIT %s,%s ";
            $vars[]=$start_record;
            $vars[]=$this->per_page;
        }

        $return[] = $query;
        $return[] = $vars;

        return $return;
    }

    function set_pagination() {
        $query   = $this->construct_count_query();
        $rst     = $this->link->consulta($query);
        list($numrows) = $this->link->fetch_row();
        $anc     = '';

        $next  = $this->page+1;
        $var   = ((intval($numrows/$this->per_page))-1)*$this->per_page;
        $last  = ceil($numrows/$this->per_page);

        $previous = $this->page-1;

        $anc = "<div class='pagination'>"; 

        if($previous <= 0){
            $anc .= "<span class='disabled'>&laquo; ".trans('First')."</span><span class='disabled'>&lsaquo;".trans('Previous')."</span>";
        }else{
            $req_uri = $this->set_request('dbgrid_page',1,false);
            $anc .= "<a href='".SELF."$req_uri'>&laquo; ".trans('First')."</a>\n";
            $req_uri = $this->set_request('dbgrid_page',$previous,false);
            $anc .= "<a href='".SELF."$req_uri'>&lsaquo; ".trans('Previous')."</a>";
        }
        

        $norepeat = 4; // numero de paginas a mostrar a izq y der
        $anch     = "";
        $j = 1;
        for($i=$this->page; $i>1; $i--){
            $page = $i-1;
            $req_uri = $this->set_request('dbgrid_page',$page,false);
            $anch = "<a href='".SELF."$req_uri'>$page</a>".$anch;
            if($j == $norepeat) break;
            $j++;
        }
        $anc .= $anch;

        $anc .= "<span class='current'>".$this->page."</span>\n";
        $j = 1;
        for($i=$this->page; $i<$last; $i++){
            $page = $i+1;
            $req_uri = $this->set_request('dbgrid_page',$page,false);
            $anc .= "<a href='".SELF."$req_uri'>$page</a>\n";
            if($j==$norepeat) break;
            $j++;
        }
        
        if($this->page >= $last){
            $anc .= "<span class='disabled'>".trans('Next')." &rsaquo;</span><span class='disabled'>".trans('Last')." &raquo;</span>\n";
        }else{
            $req_uri = $this->set_request('dbgrid_page',$next,false);
            $anc .= "<a href='".SELF."$req_uri'>".trans('Next')." &rsaquo;</a>";

            $req_uri = $this->set_request('dbgrid_page',$last,false);
            $anc .= "<a href='".SELF."$req_uri'>".trans('Last')." &raquo;</a>";
        }
        $anc.="</div>\n";
        $this->pagination_anchors = $anc;
        
        $this->pagination_total = "<div style='text-align:center;'>".trans('Page')." : $this->page <i> ".trans('of')."  </i> $last . ".trans('Total records found').": $numrows</div>";
    }

    function hide_field($field) {
        if(is_array($field)) {
           foreach($field as $campo) {
               $this->field_hide[$campo]=1;
           }
        } else {
            $this->field_hide[$field]=1;
        }
    }

    function set_default_values($fields,$values) {
        $cont=0;
        if(is_array($fields)) {
           foreach($fields as $campo) {
               if(array_key_exists($campo,$this->structure)) {
                   $this->stucture[$campo]['default']=$values[$cont];
               }
               $cont++;
           }
        } else {
            if(array_key_exists($fields,$this->structure)) {
                $this->structure[$fields]['default']=$values;
            } 
        }
    }

    function no_edit_field($field){
        if(is_array($field)) {
           foreach($field as $campo) {
               $this->field_no_edit[$campo]=1;
           }
        } else {
            $this->field_no_edit[$field]=1;
        }
    }

    function edit_field_condition($field,$conditionfield,$cond,$value) {
            $this->field_edit_condition[$field]="$conditionfield|$cond|$value";
    }

    function set_display_name($field,$newname) {
        if(is_array($field)) {
           $cont=0;
           foreach($field as $campo) {
               $this->field_name[$campo]=$newname[$cont];
               $cont++;
           }
        } else {
            $this->field_name[$field]=$newname;
        }
    }

       //Buscar
    function set_search_fields($fields) {
        if(is_array($fields)) {
            foreach($fields as $campo) {
                $this->field_search[$campo]=1;
            }
        } else {
            $this->field_search[$fields]=1;
        }
     }

    function add_error($error) {
        $this->error[]=$error;
    }
    function add_notice($error) {
        $this->notice[]=$error;
    }

   /**
    * Adds an delete callback. Gets called when a row is deleted. All
    * row data is passed as an array to the callback function.
    *
    * @param callback $callback The callback to be used
    */
    function add_delete_callback($callback) {
       if (is_callable($callback)) {
           $this->delete_callbacks[] = $callback;
       } else {
           $this->error[] = "Fallo al agregar delete callback - callback no valido";
       }
    }

    function add_insert_callback($callback) {
       if (is_callable($callback)) {
           $this->insert_callbacks[] = $callback;
       } else {
           $this->error[] = "Fallo al agregar insert callback - callback no valido";
       }
    }
 
    function add_update_callback($callback) {
       if (is_callable($callback)) {
           $this->update_callbacks[] = $callback;
       } else {
           $this->error[] = "Fallo al agregar update callback - callback no valido";
       }
    }

    function add_validation_callback($field, $callback) {
        if (!is_callable($callback)) {
           $this->error[] = "Fallo al agregar validation callback - callback no valido";
        } else {
            if (!empty($this->structure[$field]) AND empty($this->field_no_edit[$field])) {
                $this->validation_callbacks[$field][] = $callback;
            }
        }
    }

    function add_validation_type($field, $type) {
        // Tipos validos:
        // required
        // alfanumeric
        // text
        // numeric
        // email
        // url 
        if (!empty($this->structure[$field]) AND empty($this->field_no_edit[$field])) {
            $this->validation_type[$field][] = $type;
        }
    }

    function add_display_filter($field, $callback) {
        if (is_callable($callback) AND isset($this->structure[$field])) {
            $this->structure[$field]['display_filter'][] = $callback;

        } else if (is_callable($callback)) {
            $this->error[] = "Unknown field: $field";

        } else {
            $this->error[] = "Fallo al agregar display filter - callback no valido";
        }
    }

    /**
    * Applys display filters to a single row of data. Does htmlspecialchars() first.
    *
	* @param array &$results Data from table
	 */
    function apply_display_filters(&$row) {
        foreach ($row as $field => $value) {
            if (!empty($this->structure[$field]['display_filter'])) {
                foreach ($this->structure[$field]['display_filter'] as $f) {
                    $value = call_user_func($f, $value);
                }

                $row[$field] = $value;
            }
        }
    }

    function add_edit_filter($field, $callback) {
        if (is_callable($callback) AND isset($this->structure[$field])) {
            $this->structure[$field]['edit_filter'][] = $callback;

        } else if (is_callable($callback)) {
            $this->error[] = "Unknown field: $field";

        } else {
            $this->error[] = "Fallo al agregar edit filter - callback no valido";
        }
    }

    function get_edit_filtered($field,$value) {
        if (!empty($this->structure[$field]['edit_filter'])) {
            foreach ($this->structure[$field]['edit_filter'] as $f) {
                $value = call_user_func($f, $value);
            }
        }
        return $value;
    }

    function check_validation_type($field,$value) {
        foreach ($this->validation_type[$field] as $type) {
            $fieldname = isset($this->field_name[$field])?$this->field_name[$field]:$field;
            switch($type) {
                case 'required':
                    if(strlen($value)==0) {
                        $this->add_error("El campo $fieldname es obligatorio");
                    }
                    break;
                case 'alfanumeric':
                    if(!$this->isAlfaNumeric($value)) {
                        $this->add_error("El campo $fieldname solo puede contener letras y numeros");
                    }
                    break;
                case 'text':
                    if(!$this->isAlfa($value)) {
                        $this->add_error("El campo $fieldname solo puede contener letras");
                    }
                    break;
                case 'numeric':
                    if(!is_numeric($value)) {
                        $this->add_error("El campo $fieldname debe ser numerico");
                    }
                    break;
                case 'email':
                    if(!$this->isEmail($value)) {
                        $this->add_error("El campo $fieldname no es un email valido");
                    }
                    break;
                case 'url':
                    if(!$this->isURL($value)) {
                        $this->add_error("El campo $fieldname no es un url valido");
                    }
                    break;
            }
        }
    }

    function show_grid() {
        switch($this->dbgrid_action) {
            case 'csv':
                $this->export_csv();
                break;
            case 'import':
                $this->import_csv();
                break;
            case 'delete_marked':
                $this->delete_marked();
                break;
            case 'list':
                $this->print_grid();
                break;
            case 'edit':
                if(array_key_exists($this->pk,$this->res_request)) {
                    $this->edit_form($this->res_request[$this->pk]);
                }
                break;
            case 'editSave':
                if(array_key_exists($this->pk,$this->res_request)) {
                    $this->update_row($this->res_request[$this->pk]);
                }                
                break;
            case 'show':
                if(array_key_exists($this->pk,$this->res_request)) {
                    $this->show_row($this->res_request[$this->pk]);
                }
                break;
            case 'delete':
                if(array_key_exists($this->pk,$this->res_request)) {
                    $this->delete_rows(array($this->res_request[$this->pk]));
                }
                break;
            case 'add':
                $this->add_form();
                break;
            case 'addSave':
                $this->insert_row();
                break;
            case 'search':
                $this->search_rows();
                break;
            default:
                $this->print_grid();
        }        
    }

    function print_toolbar(){

        $sep=0;
        $buttons= "
        <table class='toolbarTable' style='width: 100%;' cellpadding='0' cellspacing='0'>
            <thead>
                <tr>
                    <td class='tb'>";
        if ($this->allow_add) {
            $req=$this->set_request('dbgrid_action','add',true);
            $buttons.= "
                    <div class='fbutton'>
                        <span class='tbImgLink icon_add'>&nbsp;</span>
                        <span class='btnText' onclick='javascript:location=\"".SELF.$req."\"'>".trans('Add')."</span>
                    </div>\n";
            $sep=1;
        }

        if ($this->allow_delete) {
            $req=$this->set_request('dbgrid_action','delete_marked',false);

            if($sep==1) { $buttons.=" <div class='btnseparator'></div>"; }

            $buttons.="
                    <div class='fbutton'>
                        <span class='tbImgLink icon_deleteall'>&nbsp;</span>
                        <span class='btnText' onclick='prepare_marked()'>".trans('Delete Marked')."</span>
                    </div>
            ";
            $sep=1;

        }

        if ($this->allow_export) {
            $req=$this->set_request('dbgrid_action','csv',false);
            if($sep==1) { $buttons.=" <div class='btnseparator'></div>"; }

            $buttons.="
                    <div class='fbutton'>
                        <span class='tbImgLink icon_export'>&nbsp;</span>
                        <span class='btnText' onclick='javascript:location=\"".SELF.$req."\"'>".trans('Export')."</span>
                    </div>
            ";
            $sep=1;
        }

        if ($this->allow_import) {
            $req=$this->set_request('dbgrid_import','',false);
            if($sep==1) { $buttons.=" <div class='btnseparator'></div>"; }
            $params = explode("&",$req);
            $buttons.="
                    <div class='fbutton'>
                        <span class='tbImgLink icon_import'>&nbsp;</span>
                        <span class='btnText' onclick='javascript:ImportFile();'>".trans('Import')."</span>
                    </div>
                    <div id='importfile' style='display:none; float:left;' >
                         <form method='post' id='dbgrid_importmyfile' action='".SELF."' enctype='multipart/form-data'>
                         <input type='hidden' name='dbgrid_action' value='import'>
                         <input type='file' name='dbgrid_import' value=''>
                         </form>
                    </div> 
            ";
            $sep=1;
        }


        if ($this->allow_search) {
            $req=$this->set_request('dbgrid_action','search',true);
            $req=$this->set_request('dbgrid_page',1,false);
            if($sep==1) { $buttons.=" <div class='btnseparator'></div>"; }
            $params = explode("&",$req);
            $buttons.="
                     <div>
                         <form method='post' action='".SELF.$req."' id='dbgrid_sform'>
                         <input type='text' name='dbgrid_search' style='width: 100px; float:left;' value='".$this->search."'>";
                               foreach($params as $v) {
                                   list($key,$val)=split("=",$v);
                                   if ($key=='dbgrid_sortdir' || $key=='dbgrid_sort'){
                                      $buttons.="<input type=hidden name='$key' value='$val'>\n";
                                   }
                               }
            $buttons.="<div class='fbutton'>
                         <span class='tbImgLink icon_search'>&nbsp;</span>
                         <span class='btnText' onclick='javascript:document.forms.dbgrid_sform.submit();'>".trans('Search')."</span>
                       </div>
                          </form>
                       <div>
            ";
            $sep=1;
        }
       

        $buttons.="
                        </td>
                    </tr>
                </thead>
            </table>";
            $this->del_request("dbgrid_action");
            echo $buttons;
    }

    function import_csv(){

        $valid_field = Array();

        foreach ($this->structure as $field => $value){
            $valid_field[]=trim($field);
        }

        $arrFile = $_FILES['dbgrid_import'];
        $file = $arrFile['tmp_name'];

        if ($arrFile['size']>0 && !empty($file)) {
            if (is_uploaded_file($file)) {
                if (copy ($file, $this->user_directory."importCSV-".$arrFile['name'])) {
                    $this->name_upload="importCSV-".$arrFile['name'];
                }else{
                    $this->add_error(trans('Could not copy uploaded file'));
                }
            }else{
                $this->add_error(trans('Could not upload file'));
            }
        }else{
            $this->add_error(trans('Could not upload file'));
        }
     
        if($this->name_upload == "") {
            $this->add_error(trans('Empty file?'));
        } else {

        $mifilename = $this->user_directory.$this->name_upload;
        $importar = file($mifilename);
        unlink($this->user_directory.$this->name_upload);

        $cuantos = count($importar);
        if($cuantos>0) {
            $head = array_shift($importar);
            $columns=explode(",",$head);
            $parasql = Array();
            $forcedvalues = Array();
            $valid_field_number = Array();
            $count=0;

            foreach($columns as $campo) {
                if(in_array(trim($campo),$valid_field)) {
                    if(!in_array(trim($campo),$this->force_import_field)) {
                        $valid_field_number[] = $count;
                        $parasql[]=trim($campo);
                    }
                } else {
                    $this->add_error(trans('Ignoring field ').$campo);
                }
                $count++;
            }

            if(count($valid_field_number)==0) {
                $this->add_error(trans('No valid fields to import!'));
                $this->print_grid();
                die();
            }

            foreach ($this->force_import_field as $key=>$val) {
                $parasql[]=$key;
                $forcedvalues[]=mysql_real_escape_string($val);
            }

            $columnsql=implode(",",$parasql);
            $forceval=implode("','",$forcedvalues);

            foreach($importar as $linea) {
                $linea = trim($linea);
                if($linea=="") { $cuantos--; continue; };
                $misdatos = Array();
                $columns=explode(",",$linea);
                foreach($valid_field_number as $nro) {
                    $misdatos[]=mysql_real_escape_string(trim($columns[$nro]));
                }

                $valuesql=implode("','",$misdatos);
                $query = "INSERT INTO $this->table ($columnsql) VALUES ('$valuesql','$forceval')";
                $this->link->consulta($query);
            }
            $cuantos--;
            $this->add_notice($cuantos." ".trans('Records imported'));
        }
        }
        $this->print_grid();
    }

    function delete_marked(){
        $this->del_request("dbgrid_action");
        $this->del_request("dbgrid_delete_id");
        if($_GET['dbgrid_delete_id'] <> "") {
            $ids_a_borrar = explode(",",$_GET['dbgrid_delete_id']);
            $this->delete_rows($ids_a_borrar,'marked');
        } else {
            $this->print_grid();
        }
    }

    function export_csv(){
        list ($query,$vars) = $this->construct_query(false);
        $this->link->consulta($query,$vars);
        if($this->link->num_rows()==0) {
            $this->add_error(trans('There are no records to export'));
            $this->print_grid();
            return;
        }
        @ob_end_clean();
        //ob_start();
        //header("Content-Type: application/csv-tab-delimited-table");
        //header("Content-disposition: filename=data.csv");
        header('Content-Type: text/csv');
        header("Content-Disposition: attachment; filename=data.csv");
        $cont=1;
        while ($row = $this->link->fetch_assoc()) {
            if($cont==1) {
                $columnas = Array();
                foreach($row as $columna=>$valor) {
                    $columnas[] = $columna;
                }
                $header = implode(",",$columnas);
                echo "$header\n";
            } 
            $milinea = Array();
            foreach($row as $columna=>$valor) {
                $milinea[] = $valor;
            }
            $linea = implode(",",$milinea);
            echo "$linea\n";
            $cont++;
        }
        //ob_end_flush();
        die();
    }

    function print_grid(){
        // construye la consulta en base a modificadores, etc.
        list ($query,$vars) = $this->construct_query();

        if($this->link->error()) {
            $this->print_errors();
            return;
        }

        // realiza la consulta
        $this->link->consulta($query,$vars);

        // comienza a imprimir la tabla
        $this->print_errors();

        $this->print_javascript();

        echo "<div class='stable' style='margin:auto;'>\n";
        echo "<table class='stable'>\n";

        // CAPTION
        if($this->caption <> "") {
            echo "<caption>".$this->caption."</caption>\n";
        }

        // HEADING
        // recupera los nombres de los campos y los guarda en un array
        $headings = $this->link->field_name_array();

        $headings_final  = array();
        $orden           = array();

        $cont=0;
        foreach($headings as $h) {
            $cont++;
            if(array_key_exists($h,$this->field_hide)) {
                // Salteamos los campos hide
                continue;
            }
            $orden[] = $cont;
            if(array_key_exists($h,$this->field_name)) {
                $headings_final[] = $this->field_name[$h];
            } else {
                $headings_final[] = $h;
            }
        }
        echo "<thead>\n";
        echo "<tr><td>";
        $this->print_toolbar();
        echo "</td></tr>";
        echo "</thead>\n";
        echo "</table>";

        echo "<table class='stable'>\n";
        echo "<thead>";

        echo $this->table_head($headings_final,$orden);
        echo "</thead>";
        echo "<tbody><form name='edit'>\n";

        $colspan = count($headings_final)+2;

        if ($this->link->num_rows() > 0) {
            $cont=0;
            while ($r = $this->link->fetch_assoc()) {
                // oculta los campos hide
                if(is_callable("array_diff_key")) {
                    $j = array_diff_key($r,$this->field_hide);
                } else {
                    $j = PHP4_array_diff_key($r,$this->field_hide);
                }
                $class = $cont%2?'':'odd';
                echo $this->table_row($j,$class,$r[$this->pk]);
                $cont++;
            }
        } else {
            echo "<tr><td colspan='$colspan'><div style='text-align:center; font-weight: bold;'>".trans('No records found')."</div></td></tr>";
        }
        echo "</form></tbody>";
        // FOOTER
        echo "<tfoot><tr><th colspan='".$colspan."'>";
        $this->set_pagination();

        echo $this->pagination_anchors;
        echo $this->pagination_total;

        echo "</td></tr></tfoot>";

        // END
        echo "</table>\n";
        echo "</div>\n";


        return false;    
    
    }

    function add_form(){

        if (!$this->allow_add) {
            return;
        }

        $req = substr($this->set_request('dbgrid_action','addSave',false),1);
        $params = explode("&",$req);

        $this->print_errors();

        echo "<form method='post' dbgrid_action='".SELF."' enctype='multipart/form-data'>\n";
        // comienza a imprimir la tabla
        foreach($params as $v) {
            list($key,$val)=split("=",$v);
            echo "<input type=hidden name='$key' value='$val'>\n";
        }
        echo "<fieldset>\n";
        echo "<legend>".trans('Add Record')."</legend>\n";
        echo "<ul>\n";
        foreach ($this->structure as $field => $value){
            if ($field!=$this->pk)    {
                $this->create_body_form($field,$this->structure[$field]['default'],"add");    
            }
        }
        $req = $this->set_request('dbgrid_action','list',true);
        $req = $this->del_request($this->pk);
        echo "<li>
            <input type='submit' class='submit' name='button_ok' value='".trans('Ok')."'>
            <input type='submit' class='submit' name='button_cancel' value='".trans('Cancel')."' onClick='javascript:location=\"".SELF."$req\"; return false;'></li>";
        echo "</ul></fieldset></form>";
    }

    function insert_row() {


        if (!$this->allow_add) {
            return;
        }

        $status      = "OK";
        $random      = "DBGRID-".rand();
        $callbacks   = !empty($this->insert_callbacks);
        $add_fields  = array();
        $name_temp   = array();
        $query_parts = array();
        $queryval    = array();

        print_r($this->structure);

        foreach ($this->structure as $field => $value){
                if (array_key_exists($field,$_POST)){

                    // Procesa validacion
                    if (!empty($this->validation_type[$field])) {
                        $this->check_validation_type($field,$_POST[$field]);
                    }

                    // Procesa callback de validacion
                    if (!empty($this->validation_callbacks[$field])) {
                        foreach ($this->validation_callbacks[$field] as $c) {
                            $_POST[$field] = call_user_func($c, $this, $_POST[$field]);
                        }
                    }

                    if ($field!=$this->pk){
                        $add_fields[$field]=$_POST[$field];
                    }
                }
                if (array_key_exists($field,$_FILES)){
                    if ($field!=$this->pk) {
                        $add_fields[$field]=$_FILES[$field];
                    }
                }
        }


//        echo "<pre>";
//        print_r($add_fields);
//        echo "</pre>";

        if (sizeof($add_fields)>0){

            $query = "INSERT INTO $this->table SET ";

            foreach ($add_fields as $field => $value){
                if ($this->structure[$field]['input']=="img"){
                    if($_FILES[$field]['name']<>"") {
                        $name_temp[]=array('field'=>$field,'temp_name'=>$random."-".$value['name'],'name_file'=>$value['name']);
                        $this->upload_img($random,$_FILES[$field],$field);
                    }
                    $value="";
                    if(count($this->error)>0) { break; }
                }
                else if($this->structure[$field]['input']=='bitmask') {
                     $totbit=0;
                     foreach($value as $indv) {
                        $totbit+=$indv;
                     }
                     $value=$totbit;
                } 
                else if($this->structure[$field]['input']=='multiselect') {
                     $finvalue = implode(",",$value);
                     $value    = $finvalue;
                }

                $query_parts[]= "%s='%s'";
                $queryval[]=$field;
                $queryval[]=$value;
                
                $this->del_request($field);
            }
        }

        $query.=implode(",",$query_parts);

        if(count($this->error)>0) {
            $this->add_form();
            return;
        }

        $res  = $this->link->consulta($query,$queryval);

        if($res) {
            $this->set_request('dbgrid_notice',trans('Record inserted'),true);
            $insert_id = $this->link->insert_id();

            // Manipulamos nombre de archivo en base
            if (sizeof($name_temp)>0){
                foreach ($name_temp as $key => $value){
                    //print "nametemp $key = $value<br>";
                    if (rename($this->user_directory.$value['temp_name'], $this->user_directory.$insert_id."-".$value['name_file'])){
                        $this->set_request('dbgrid_notice',trans('File was uploaded successfully'),true);
                        $temp_field=$value['field'];
                        $temp_name_file=$insert_id."-".$value['name_file'];
                        $query="UPDATE $this->table SET $temp_field = '$temp_name_file' WHERE $this->pk = $insert_id";
                        $this->link->consulta($query);
                    }
                    else{
                        $this->set_request('dbgrid_error',trans('Problems with the file upload. Check permissions on the upload directory.'),true);
                        echo "fallo rename ".$this->user_directory.$value['temp_name']."<br>";
                    }
                }
            }
            if($callbacks) {
                $add_fields[$this->pk]=$insert_id;
                foreach ($this->insert_callbacks as $c) {
                    call_user_func($c, $add_fields);
               }
           }
        } else {
		   $this->set_request('dbgrid_error',trans('Error inserting record').': <br>'.$this->link->error(),true);
           // borrar archivo subido
        }

        $this->del_request("button_ok");
        $req=$this->set_request('dbgrid_action','list',true);
        //$this->print_grid();

        header("Location: ".SELF."$req"); 
    }

    function print_javascript() {

        $req=$this->set_request('dbgrid_action','delete_marked',true);
        $req=$this->set_request('dbgrid_pkhash',md5($this->salt.'marked'),false);

         echo "

<script>


function prepare_marked() {
    field = document.edit;
    var totnumero='';
    for (i = 0; i < field.length; i++) {
        if(field[i].id.indexOf('dbgrid_checkbox')==0) {
            if(field[i].checked) {
                var minumero = field[i].id.substring(16);
                totnumero+=minumero+',';
            }
        }
    }
    if(totnumero.length>0) {
       var seguro = confirm(\"".trans("Are you sure?")."\");
       if(seguro===true) {
           totnumero = totnumero.substring(0,totnumero.length-1);
           location = '".SELF.$req."&dbgrid_delete_id='+totnumero;
       } else {
           return;
       }
    }
}

function checkAll() {
    field = document.edit;
    for (i = 0; i < field.length; i++) {
        if(field[i].id.indexOf('dbgrid_checkbox')==0) {
            field[i].checked = !field[i].checked;
        }
    }
}

function ImportFile(me) {
    var estavisible = isvisible('importfile');
    if(estavisible=='none') {
        showdiv('importfile');
    } else {
        hidediv('importfile');
        document.forms.dbgrid_importmyfile.submit();
    }
}

function isvisible(id) {
    if (document.getElementById) { 
       return document.getElementById(id).style.display;
    } else {
        if (document.layers) { 
            return document.id.display;
        } else {
            return document.all.id.style.display;
        }
    }
}

   
function hidediv(id) {
	//safe function to hide an element with a specified id
	if (document.getElementById) { // DOM3 = IE5, NS6
		document.getElementById(id).style.display = 'none';
	}
	else {
		if (document.layers) { // Netscape 4
			document.id.display = 'none';
		}
		else { // IE 4
			document.all.id.style.display = 'none';
		}
	}
}

function showdiv(id) {
	//safe function to show an element with a specified id
		  
	if (document.getElementById) { // DOM3 = IE5, NS6
		document.getElementById(id).style.display = 'block';
	}
	else {
		if (document.layers) { // Netscape 4
			document.id.display = 'block';
		}
		else { // IE 4
			document.all.id.style.display = 'block';
		}
	}
}
";
    
         echo "</script>\n";
    }
    function print_errors() {
        global $jsnotifications;

        if($jsnotifications==1) {

            echo "<script type='text/javascript'>\n";

            if (count($this->error)>0) {
                echo "var error_type='message_error';\n";
                echo "var error_text='";
                foreach($this->error as $error) {
                     echo "<p>$error</p>";
                }
                echo "';\n";
            } else 
                if (count($this->notice)>0) {
                    echo "var error_type='message_success';\n";
                    echo "var error_text='";
                    foreach($this->notice as $notice) {
                        echo "<p>$notice</p>";
                    }
                    echo "';\n";

            }
            echo "</script>\n";

        } else {
            if(count($this->notice)>0) {
                echo "<div class='successBox' id='noticebox'>\n";
                echo "  <div class='successIcon'></div>\n";
                echo "  <div class='msgBoxContent'>\n";
                echo "  <p>".trans('Success!')."</p> \n";
                foreach($this->notice as $notice) {
                    echo "<p>$notice</p>";
                }
                echo "  </div>\n";
                echo "</div>\n";
                echo "<script type='text/javascript'>\n";
                echo "window.setTimeout(\"hidediv('noticebox')\",3000);\n";
                echo "</script>\n";
            }

            if(count($this->error)>0) {
                echo "<div class='errorBox' id='errorbox'>\n";
                echo "  <div class='errorIcon'></div>\n";
                echo "  <div class='msgBoxContent'>\n";
                echo "  <p>".trans('An error has occurred')."</p> \n";

                foreach($this->error as $error) {
                    echo "  <p>$error</p>\n";
                }
                echo "  </div>\n";
                echo "</div>\n";
                echo "<script type='text/javascript'>\n";
                echo "window.setTimeout(\"hidediv('errorbox')\",3000);\n";
                echo "</script>\n";
            }
        }
    }
    
    function edit_form($id){
        if (!$this->allow_edit) {
            return;
        }
        $query = "SELECT * FROM $this->table WHERE $this->pk = $id";
        // realiza la consulta
        $this->link->consulta($query);
        $this->set_request('dbgrid_action','editSave',true);
        $req = substr($this->set_request($this->pk,$id,false),1);
        
        $params = explode("&",$req);

        $this->print_errors();

        echo "<form method='post' action='".SELF."' enctype='multipart/form-data'>\n";
        // comienza a imprimir la tabla
        foreach($params as $v) {
            list($key,$val)=split("=",$v);
            echo "<input type=hidden name='$key' value='$val'>\n";
            if($this->pk == $key) {
                echo "<input type=hidden name='dbgrid_pkhash' value='".md5($this->salt.$val)."'>";
            }
        }
        echo "<fieldset>\n";
        echo "<legend>".trans('Edit Record')."</legend>\n";
        echo "<ul>\n";

        if ($this->link->num_rows() > 0) {
            $cont=0;
            $rowresult = $this->link->fetch_assoc();
            foreach ($rowresult as $field=>$value) {
                $valor_de[$field]=$value;
            }
            foreach ($rowresult as $field=>$value) {
                if (isset($this->field_edit_condition[$field])){
                if ($this->field_edit_condition[$field]){
                     list($condfield,$condcond,$condvalue) = split("\|",$this->field_edit_condition[$field]);
                     if($condcond == "=") {
                         if($valor_de[$condfield]==$condvalue) {
                             $this->field_no_edit[$field] = 0;
                         } else {
                             $this->field_no_edit[$field] = 1;
                         }
                     } else {
                         if($valor_de[$condfield]<>$condvalue) {
                             $this->field_no_edit[$field] = 0;
                         } else {
                             $this->field_no_edit[$field] = 1;
                         }
                     }
                }
                }
            }
            foreach ($rowresult as $field=>$value) {
                //Verifica los campos que estan seteados con NO editar, para no mostrarlos.
                if (!(array_key_exists($field, $this->field_no_edit) && $this->field_no_edit[$field] == 1 )){
                    $this->create_body_form($field,$value,"edit");
                //Fin del if, verifacdor de campos no edit.
                }
            //Fin Foreach
            }
            $req = $this->set_request('dbgrid_action','list',true);
            $req = $this->del_request($this->pk);
            echo "<li>
                <input type='submit' class='submit' name='button_ok' value='".trans('Ok')."'>
                <input type='submit' class='submit' name='button_cancel' value='".trans('Cancel')."' onClick='javascript:location=\"".SELF."$req\"; return false;'>
                </li>";
        //Fin de Row > 0 
        }
        echo "</ul></fieldset></form>";
    }

    function update_row($id) {
        $status="OK";
        $callbacks = !empty($this->update_callbacks);
        $update_fields=array();

        if(md5($this->salt.$id) <> $_POST['dbgrid_pkhash']) {
            $this->add_error("Trampa con el formulario");
        }

        foreach ($this->structure as $field => $value){
            if (!(array_key_exists($field,$this->field_no_edit))){

                if (array_key_exists($field,$_POST)){

                    // Procesa validacion
                    if (!empty($this->validation_type[$field])) {
                        $this->check_validation_type($field,$_POST[$field]);
                    }

                    // Procesa callback de validacion
                    if (!empty($this->validation_callbacks[$field])) {
                        foreach ($this->validation_callbacks[$field] as $c) {
                            $_POST[$field] = call_user_func($c, $this, $_POST[$field]);
                        }
                    }
                    if ($field!=$this->pk) {
                        $update_fields[$field]=$_POST[$field];
                    }
                }
                if (array_key_exists($field,$_FILES)){
                    if ($field!=$this->pk) {
                        $update_fields[$field]=$_FILES[$field];
                    }
                }
            }
        }

        $files_to_remove = Array();
        $files_added = Array();

        $queryfields=Array();
        $queryvars=Array();

        if (sizeof($update_fields)>0){
            $query = "UPDATE $this->table SET ";
            foreach ($update_fields as $field => $value){
                if ($this->structure[$field]['input']=='img'){
                    $this->upload_img($id,$_FILES[$field],$field);
                    if(count($this->error)>0) { break; }
                    $value=$this->name_upload;
                    $files_added[] = $value;
                    if($this->old_name_upload <> '' && $this->old_name_upload<>$value ) {
                        $files_to_remove[] = $this->old_name_upload;
                    }
                } else if($this->structure[$field]['input']=='bitmask') {
                     $totbit=0;
                     foreach($value as $indv) {
                        $totbit+=$indv;
                     }
                     $value=$totbit;
                } 
                else if($this->structure[$field]['input']=='multiselect') {
                     $finvalue = implode(",",$value);
                     $value    = $finvalue;
                }
                $queryfields[]="%s='%s'";
                $queryvars[]=$field;
                $queryvars[]=$value;
                $this->del_request($field);
            }
            $query.=implode(",",$queryfields);
            $query.= " WHERE $this->pk = '%s' LIMIT 1";
            $queryvars[] = $id;
        }

        if(count($this->error)>0) {
            $this->edit_form( $id );
            return;
        }

        $res  = $this->link->consulta($query,$queryvars);

        if($res) {
           $this->add_notice(trans('Record updated'));
            if($callbacks) {
                $update_fields[$this->pk]=$id;
                foreach ($this->update_callbacks as $c) {
                    call_user_func($c, $update_fields);
                }
           }
           $this->unlink_files($files_to_remove);
        } else {
           // $this->set_request('dbgrid_error','Error al modificar registro',true);
           $this->add_error(trans('Error updating record'));
           $this->unlink_files($files_added);
        }

        $this->del_request($this->pk);
        $this->del_request('button_ok');
        $req=$this->set_request('dbgrid_action','list',true);
        //header("Location: ".SELF."$req");
        $this->print_grid();
    }

    function show_row($id){
        $query = "SELECT * FROM $this->table WHERE $this->pk = $id";
        $this->link->consulta($query);
        echo "<fieldset>\n";
        echo "<legend>".trans('View Record')."</legend>\n";
        echo "<ul>\n";

        if ($this->link->num_rows() > 0) {
            $cont=0;
            foreach ($this->link->fetch_assoc() as $field=>$value) {
                $this->create_body_form($field,$value,"show");
            //Fin Foreach
            }
            $req = $this->set_request('dbgrid_action','list',false);
            echo "<li><input type='submit' class='submit' name='button_cancel' onClick='javascript:location=\"".SELF."$req\"; return false;' value='".trans('Cancel')."'></li>";
        //Fin de Row > 0 
        }
        echo "</ul></fieldset>";
    }

    function delete_rows($ids,$customsalt=''){
        $cuantos_a_borrar = count($ids);
        $ids = implode(',', $ids);

        if($customsalt<>'') {
            $mihash = md5($this->salt.$customsalt);
        } else {
            $mihash = md5($this->salt.$ids);
        }
        $callbacks = !empty($this->delete_callbacks);
        $data = array();

        if($mihash <> $_GET['dbgrid_pkhash']) {
            $this->add_error("Trampa con el formulario ($ids)".$mihash);
        }

        if(count($this->error)>0)  {
            $this->del_request($this->pk);
            $this->set_request("dbgrid_action","list",true);
            $this->print_grid();
            return;
        }


        // Recupera datos para pasarle al delete callback y guarda nombre de archivos
        // a borrar si hubiera que borrarlos
        $files_to_remove = Array();

        $this->link->consulta("SELECT %s FROM %s %s WHERE %s IN (%s)",$this->fields,$this->table,$this->join,$this->table.".".$this->pk,$ids);

        while($row = $this->link->fetch_assoc()) {
            $data[]=$row;
            foreach($row as $field=>$val) {
                if ($this->structure[$field]['input']=="img"){
                    $files_to_remove[] = $val;
                }
            }
        };

        $res = $this->link->consulta("DELETE FROM %s WHERE %s IN ($ids)",$this->table,$this->pk,$ids);

        if ($res AND $callbacks) {
            foreach ($data as $row) {
                foreach ($this->delete_callbacks as $c) {
                    call_user_func($c, $row);
                }
            }
        }

        if($res) {

            $this->unlink_files($files_to_remove);

            if($cuantos_a_borrar==1) {
                $this->set_request('dbgrid_notice',trans('Record deleted'),true);
            } else if ($cuantos_a_borrar>1) {
                $this->set_request('dbgrid_notice',trans('%s records deleted',$cuantos_a_borrar),true);
            }
        } else {
            $this->set_request('dbgrid_error',trans('Could not delete records'),true);
        }
        $this->del_request($this->pk);
        $this->del_request("button_ok");
        $req=$this->set_request('dbgrid_action','list',true);
        header("Location: ".SELF."$req");
    }

    function create_body_form($field,$value,$type){
        switch ($type){
            case "edit":
                $atribute=(($field == $this->pk && !$this->modify_pk) || ( array_key_exists($field, $this->field_no_edit) && $this->field_no_edit[$field] == 1 )) ? 'disabled': '';
                break;
            case "show":
                $atribute="disabled";
                break;
            case "add":
                $atribute=($field == $this->pk && !$this->modify_pk) ? 'disabled': '';
                break;
            default:
                $atribute="disabled";
                break;
        }
        $value = $this->clean_entities($value);

        if(array_key_exists($field,$this->field_name)) {
            $display_name = $this->field_name[$field];
        }else{
            $display_name = $field;
        }
        echo "<li>\n";
        if ($this->structure[$field]['input'] != 'hidden'){
            printf("<label for='%s'>%s</label>",$field,$display_name);
        }
        switch ($this->structure[$field]['input']){
            case 'textarea':
                printf('<textarea name="%s" cols="50" rows="12"  %s>%s</textarea>',$field,$atribute,$value);
                break;
            case 'multiselect':
                printf('<select name="%s[]" %s size="7" multiple>', $field,$atribute);
                foreach ($this->structure[$field]['values'] as $k => $v) {
                    $coco = split(",",html_entity_decode($value));
                    $caca = html_entity_decode($k);
                    $vprint = $this->get_edit_filtered($field,$v);
                    printf('<option value="%s" %s>%s</option>',$k,in_array($caca,$coco) ? 'selected' : '',$vprint);
                }
                echo '</select>';
                break;
 
            case 'select':
                //printf('<select name="%s" %s><option value="">Select...</option>',
                printf('<select name="%s" %s>',
                $field,$atribute);
                foreach ($this->structure[$field]['values'] as $k => $v) {
                    $vprint = $this->get_edit_filtered($field,$v);
                    printf('<option value="%s" %s>%s</option>',$v,(string)$v === $value ? 'selected' : '',$vprint);
                }
                echo '</select>';
                break;
            case 'bitmask':
                printf('<select name="%s[]" %s  size="7" multiple>',$field,$atribute);
                foreach ($this->structure[$field]['values'] as $k => $v) {
                    printf("<option value=\"%s\" %s>%s</option>\n",$k,$k & $value ? 'selected' : '',$v);
                }
                echo '</select>';
                break;
            case 'date':
	            printf('<input type="text" name="%s" value="%s"  %s readonly/> <img src="img/calendario.gif" onclick="displayCalendar(document.forms[0].elements[\'%s\'],\'yyyy/mm/dd\',this)">',
                $field,$value,$atribute,$field);
                break;
				/*
                    case 'time':
                        //printf('<input type="text" name="%s" value="%s"  %s/> <a href="javascript: void(document.forms[0].elements[\'%s\'].value = currentTime())" onclick="enableApply()" title="Click to set current time">Now</a>',
                        $field,
                        $value,
                        (($field == $this->pk && !$this->modify_pk) || ( array_key_exists($field, $this->field_no_edit) && $this->field_no_edit[$field] == 1 )) ? 'disabled': '',
                        $field);
                        break;
                    case 'datetime':
                        //printf('<input type="text" name="%s" value="%s"  %s/> <a href="javascript: void(document.forms[0].elements[\'%s\'].value = currentDateTime())" onclick="enableApply()" title="Click to set current date and time">Now</a>',
                        $field,
                        $value,
                        (($field == $this->pk && !$this->modify_pk) || ( array_key_exists($field, $this->field_no_edit) && $this->field_no_edit[$field] == 1 )) ? 'disabled': '',
                        $field);
                        break;
                        */
             case 'password':
                 printf('<input type="password" name="%s" value="%s" %s /><li><label>&nbsp;</label><input type="password" name="%s_confirm" value="%s" %s> <i>(confirmar)</i></li><li><label>&nbsp;</label><input type="checkbox" value="1" name="%s_blank" id="%s_blank"> <label for="%s_blank">Dejar clave en blanco?</label></li>',
                $field,$value,$atribute,$field,$value,$atribute,$field,$field,$field);
                break;
             case 'hidden':
                printf('<input type="hidden" name="%s" value="%s" />',
                $field,$value);
                 break;
             case 'img':
                  printf('<input type="file" name="%s" id="%s" %s/>',$field,$field,$atribute);
                 if ($value != ""){
                    printf('<li><label>&nbsp;</label><img src="%s" alt="%s" title="%s"></li>',$this->user_directory.$value,$value,$value);
                 }     
                 break;
             default:
                printf('<input type="text" name="%s" value="%s"  %s />',$field,$value,$atribute);
                break;
        }
        echo "</li>";
    }
            
    function db_quote($str) {

            if (is_null($str)) {
                return 'NULL';
            }

            /**
            * Handle magic_quotes_gpc
            */
            if (ini_get('magic_quotes_gpc')) {
                $str = stripslashes($str);
            }

            return "'" . mysql_real_escape_string($str) . "'";
        }

    function unlink_files($files){
        if(is_array($files)) {
            foreach($files as $file) {
                unlink($this->user_directory.$file);
            }
        } else {
            unlink($this->user_directory.$files);
        }
    }

    function upload_img($id,$arrFile,$field=""){
        $file = $arrFile['tmp_name'];
        if ($arrFile['size']>0 && !empty($file)) {
            if (is_uploaded_file($file)) {
                if (copy ($file, $this->user_directory.$id."-".$arrFile['name'])) {
                    $res = $this->link->consulta("SELECT %s FROM %s WHERE %s='%s' LIMIT 1",$field,$this->table,$this->pk,$id);
                    if($res) {
                        $row = $this->link->fetch_assoc();
                        $this->old_name_upload=$row[$field];
                    }
                    $this->name_upload=$id."-".$arrFile['name'];
                }else{
                    $this->add_error("No se pudo copiar el archivo");
                }
            }else{
                $this->add_error("No se pudo subir el archivo");
            }
        }else{
            $res=$this->link->consulta("SELECT %s FROM %s WHERE %s='%s' LIMIT 1",$field,$this->table,$this->pk,$id);
            if($res) {
               $row = $this->link->fetch_assoc();
               $this->name_upload=$row[$field];
            } else {
               $this->name_upload="";
            }

        }
    }

   /**
     * Verifica si un email tiene formato válido y opcionalmente verifica los registros MX del dominio tambien
     * 
     * @param String $email
     * @param Boolean $test_mx Verificar los registros MX del dominio
     */
     function isEmail($email, $test_mx = false){
        if($email=="") { return true; }
        if(eregi("^([_a-z0-9-]+)(\.[_a-z0-9-]+)*@([a-z0-9-]+)(\.[a-z0-9-]+)*(\.[a-z]{2,4})$", $email)) {
            if($test_mx) {
                list( , $domain) = split("@", $email);
                return getmxrr($domain, $mxrecords);
            } else {
                return true;
            }
        } else {
            return false;
        }
    }

    /**
     * Checks for a valid internet URL
     *
     * @param string $value The value to check
     * @return boolean TRUE if the value is a valid URL, FALSE if not
     */
     function isURL($value) {
        if (preg_match("/^http(s)?:\/\/([\w-]+\.)+[\w-]+(\/[\w- .\/?%&=]*)?$/i", $value)) {
            return true;
        } else {
            return false;
        }
    }

     function isAlfaNumeric($value) {
        if (preg_match("/^[A-Za-z0-9 ]+$/", $value)) {
            return true;
        } else {
            return false;
        }
    }

     function isAlfa($value, $allow = '') {
        if (preg_match('/^[a-zA-Z' . $allow . ']+$/', $value)) {
            return true;
        } else {
            return false;
        }
    }

    function search_rows(){
        /*$search=$_REQUEST['dbgrid_search'];
        $query_parts=array();
        foreach ($this->field_search as $key=>$val ){
            $query_parts[]= "$key LIKE '%%$search%%'";
        }
        $this->condition=implode(" OR ",$query_parts);*/
        $this->del_request('button_ok');
        $req=$this->set_request('dbgrid_action','list',true);
        $this->print_grid();
    }


//Fin de clase
}
