<?php
ob_start();

class dbgrid {

    private $link;

    private $table;

    private $caption;

    private $dbgrid_action;

    private $name_upload;

    private $old_name_upload;

    private $condition = ' WHERE 1=1 ';

    private $join     = '';

    private $groupby  = '';

    private $search   = '';

    private $per_page = 20;

    private $page     = 1;

    private $fields   = '*';

    private $fields_edit   = '*';

    private $orderby  = '';

    private $sortdir  = '';

    private $salt               = 'superSecret';

    private $user_directory     = './uploads/';

    private $pagination_anchors = '';

    private $pagination_total   = '';

    private $pk                 = '';

    private $foreignpk          = '';

    private $res_request          = array();
    
    private $colwidths            = array();

    private $structure            = array();

    private $force_import_field   = array();

    private $field_name           = array();

    private $field_hide           = array();

    private $field_search         = array();

    private $field_no_edit        = array();

    private $field_edit_condition = array();

    private $validation_callbacks = array();

    private $validation_type      = array();

    private $extra_attributes     = array();

    private $delete_callbacks     = array();

    private $insert_callbacks     = array();

    private $update_callbacks     = array();

    private $custom_toolbar       = array();

    private $custom_action        = array();

    private $notice               = array();

    private $error                = array();

    // Add Several Joins to do multiple level associations/relations
    private $joins                = array();

    private $foreign              = array();

    private $debug        = true;

    private $stopnow      = false;

    private $nocheckbox   = false;

    private $modify_pk    = false;

    private $allow_view   = true;

    private $allow_add    = true;

    private $allow_delete = true;

    private $allow_edit   = true;

    private $allow_export = true;

    private $allow_import = true;

    private $allow_search = false;

    /**
     * Constructor
     *
     * @param string $link
     * @return void
     */
    public function __construct($link) {
        $this->link    = $link;
        $this->get_request();
        return;
    }

    /**
     * Destructor
     *
     * @return void
     */
    public function __destruct() {
        return false;
    }

    private function get_request() {

        $get_post = array_merge($_GET,$_POST);

        $cancel="";

        $ok="";

        foreach($get_post as $key=>$val) {
            if(substr($key,0,4)=="amp;") {
               $key=substr($key,4);
            }
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
                $val = rtrim($val);
                $val = ltrim($val);
                $this->search=urlencode($val);
                $this->res_request[$key] = urlencode($val); //nico
            }
            if($key<>"button_ok" && $key <>"button_cancel" && 
               $key<>"dbgrid_notice" && $key<>"dbgrid_error" && !preg_match("/_insert_/",$key)) {
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

    private function del_request($param) {
        unset($this->res_request[$param]);
        $ret = array();
        $return = "";
        foreach($this->res_request as $key=>$val) {
            $ret[]="$key=$val";
        }
        $return = implode("&amp;",$ret);
        if($return<>"") {
            $return ="?".$return;
        }
        return $return;
    }

    public function set_nocheckbox($val=true) {
        $this->nocheckbox=$val;
    }

    private function set_request($param,$value,$store=true) {

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
        // quiero que el dbbrid_search este siempre ultimo por caracteres especiales #
        $ret2=array();
        $salvame="";
        foreach($ret as $valor) {
            if(preg_match("/^dbgrid_search/",$valor)) {
                $salvame=$valor;
            } else {
                $ret2[]=$valor;
            }
        }
        if($salvame<>"") { $ret2[] = $salvame; }

        $return = implode("&amp;",$ret2);

        if($return<>"") {
            $return ="?".$return;
        }

        return $return;
    }

    //Permite habilitar la modificacion de PK
    private function change_pk($modify){
        $this->modify_pk=$modify;
    }

    public function set_pk($field){
        $this->pk=$field;
    }

    //Permite setear un directorio para administrar archivos
    public function set_user_directory($directory){
        $this->user_directory=$directory;
    }

    //Permite habilitar el boton agregar
    public function allow_add($allow_add){
        $this->allow_add=$allow_add;
    }

    //Permite habilitar el boton ver
    public function allow_view($allow_view){
        $this->allow_view=$allow_view;
    }


    //Permite habilitar el boton borrar
    public function allow_delete($allow_delete){
        $this->allow_delete=$allow_delete;
    }

    //Permite habilitar el boton editar 
    public function allow_edit($allow_edit){
        $this->allow_edit=$allow_edit;
    }

    //Permite habilitar el boton exportar 
    public function allow_export($allow_export){
        $this->allow_export=$allow_export;
    }

    //Permite habilitar el boton importar
    public function allow_import($allow_import){
        $this->allow_import=$allow_import;
    }

    //Buscar
    public function allow_search($allow_search){
       $this->allow_search=$allow_search;
    }

    public function salt($salt){
        $this->salt=$salt;
    }

    private function table_row($datos, $extraclass, $key, $rawdata, $contid) {

        $colnumber=0;
        $retaction='';

        if($extraclass<>'') {
            $extraclass=" class='$extraclass'";
        }

        $ret = "<tr $extraclass id='datarow_$contid'>\n";

        $sumacolspan=0;
        $cont=0;
        if ($this->allow_edit) {
            $cont++;
        }
        if ($this->allow_delete) {
            $cont++;
        }
        if($this->allow_view) {
            $cont++;
        }

        if($cont>0) {
            $colnumber =1;
            $sumacolspan=1;

        if($this->dbgrid_action!='pdf' ) {
            // No queremos acciones si exportamos a pdf
            $retaction= "<td>";
            $retaction.= "<div class='btn-group'>";

            $req=$this->set_request($this->pk,$key,true);

            foreach($this->custom_action as $texto) {
                if (is_callable($texto)) {
                $retaction .= call_user_func($texto, $rawdata);
                }
            }

            if($this->allow_view) {
                $req=$this->set_request('dbgrid_action','show',true);
                $retaction.="<button class='btn' data-toggle='tooltip' data-placement='top' onClick='window.location=\"".SELF."$req\"' title='".trans('View Record')."' ><i class='glyphicon glyphicon-eye-open'></i></button>";
            }

            if($this->allow_edit) {
                $req=$this->set_request('dbgrid_action','edit',true);
                $retaction.="<button class='btn' data-toggle='tooltip' data-placement='top' onClick='window.location=\"".SELF."$req\"' title='".trans('Edit Record')."'><i class='glyphicon glyphicon-edit'></i></button>";
            }

            if($this->allow_delete) {
                $req=$this->set_request('dbgrid_action','delete',true);
                $req=$this->set_request('dbgrid_pkhash',md5($this->salt.$key),false);
                $retaction.="<button class='btn jconf' data-toggle='tooltip' data-placement='top' id=\"".SELF."$req\" title='".trans('Delete')."'><i class='glyphicon glyphicon-trash'></i></button>";
            }

            $retaction.="</div>";
            $retaction.="</td>\n";
        }

        $this->del_request("dbgrid_action");
        $this->del_request($this->pk);
        }
        $cont=0;
        if($this->nocheckbox != 1) {
           if($this->dbgrid_action!='pdf') {
               $ret.="<td style='overflow:hidden; width:30px;'><input type=checkbox onclick='clearCheckAll(this)' id='dbgrid_checkbox_".$key."' ></td>\n";
               $colnumber++;
           }
        } 

        if(is_array($datos)) {

            $this->apply_display_filters($datos,$rawdata);

            foreach($datos as $campo=>$valor) {
                if (empty($this->structure[$campo]['display_filter'])) {
                   
                    $valor = $this->clean_entities($valor);
                }
                $columnStyle=isset($this->structure[$campo]['columnstyle'])?$this->structure[$campo]['columnstyle']:'';
                if($valor=='') { $valor='&nbsp;'; }
                $ret.="<td style='";
                if($this->dbgrid_action!='pdf') {
                     $ret.="text-overflow:ellipsis; white-space: nowrap; overflow:hidden; ";
                }
                $ret.="$columnStyle'>$valor</td>\n";
                $colnumber++;
            }

        } else {
            $datos = $this->clean_entities($datos);
            $ret.="<td>$datos</td>\n";
            $colnumber++;
    }
    $ret .=$retaction;
        $ret .= "\n</tr>\n";
        if($this->dbgrid_action!='pdf') {
            $ret .= "<tr id='datarow_{$contid}_drill' style='display:none;'><td id='datarow_{$contid}_drill_td' colspan='$colnumber'></td></tr>\n";
    }

        return $ret;
    }

    
    private function table_head($datos,$orden=array()) {

        $ret = "<tr>\n";
        $retaction='';

        $cont=0;
        if ($this->allow_edit) {
            $cont++;
        }
        if ($this->allow_delete) {
            $cont++;
        }
        if($this->allow_view) {
            $cont++;
        }

        if($cont>0) {
            if($this->dbgrid_action!='pdf') {
                // No queremos accion ni checkbox si exportamos a pdf
                $retaction="<th>&nbsp;</th>\n";
            }
        }
        $cont=0;

        if($this->dbgrid_action!='pdf') {
            if($this->nocheckbox != 1) {
                $ret.="<th class='tcheck'><input type=checkbox id='checkall' onclick='checkAll(this,\"maintable_".$this->table."\")' ></th>\n";
            }
        }

        $colnum=0;
        foreach($this->colwidths as $width) {
            if($width=="*") { 
                $ancho[$colnum]='30px'; 
            } else {
            $ancho[$colnum]="{$width}px";
                //$ancho[$colnum]='30px'; 
            }
            $colnum++;
    }

        if(is_array($datos)) {

            $cont=0;

            foreach($datos as $valor) {

                //$valor = $this->clean_entities($valor);
                if(!isset($orden[$cont])) { $orden[$cont]=''; }

                $req_uri = $this->set_request("dbgrid_sort",$orden[$cont],true);

                $newdir="";
                $custom_sort_dir="";

                if($orden[$cont]==$this->orderby) {
 
                    $custom_sort_dir = $_REQUEST['dbgrid_sortdir'];

                    if($custom_sort_dir == "") {
                        $newdir = "ASC";
                    } else if($custom_sort_dir == "DESC") {
                        $newdir = "";
                        $req_uri = $this->del_request("dbgrid_sort",false);
                        $req_uri = $this->del_request("dbgrid_sortdir",false);
                    } else {
                        $newdir = "DESC";
                    }

                    $req_uri = $this->set_request("dbgrid_sortdir",$newdir,false);
                    $arrow = ($custom_sort_dir == "ASC")?"glyphicon glyphicon-arrow-up":"glyphicon glyphicon-arrow-down";
                    $add_arrow="<i class='$arrow'></i>";
                } else {
                    $req_uri = $this->set_request("dbgrid_sortdir","ASC",false);
                    $add_arrow="";
                } 

                $ret.="<th  "; 
                if($this->dbgrid_action=='pdf') {
                    $ret.=" width:".$ancho[$cont].";";
                }
                $ret.=">";
                if($this->dbgrid_action!='pdf') {
                    $ret.="<a class='btn' href='".SELF."$req_uri'>";
                }
                $ret.=$valor;

                if($this->dbgrid_action!='pdf') {
                    $ret.="</a> $add_arrow";
                }
                $ret.="</th>\n";
                $cont++;
            }
            if(isset($_REQUEST['dbgrid_sort'])) {
                $req_uri = $this->set_request("dbgrid_sort",$_REQUEST['dbgrid_sort'],true);
            } else { 
                $req_uri = $this->del_request("dbgrid_sort",true);
            }
            if(isset($_REQUEST['dbgrid_sortdir'])) {
                $req_uri = $this->set_request("dbgrid_sortdir",$_REQUEST['dbgrid_sortdir'],true);
            }
        } else {
            //$datos = $this->clean_entities($datos);
            $ret.="<th><a class='btn' href='".SELF."'>$datos</a></th>\n";
        }
        $ret .= "$retaction\n</tr>\n\n";
        return $ret;
    }


    public function set_table($table) {

        if($this->table) { return; }
        if(!$table) { return; }
        $this->table = $table;

        //Tipos de datos en mysql        
        $basetypes = 'real|double|float|decimal|numeric|tinyint|smallint|mediumint|int|bigint|date|time|timestamp|datetime|char|varchar|tinytext|text|mediumtext|longtext|enum|set|tinyblob|blob|mediumblob|longblob';
        $extra     = 'unsigned|zerofill|binary|ascii|unicode| ';
     
        $this->link->consulta("SET NAMES 'UTF8'");
        $res = $this->link->consulta("DESC $table");

        if(!$res) {
            $this->add_error(mysqli_real_escape_string($this->link->error()));
            $this->stopnow = true;
            $this->print_grid();
            die();
        }

        while($row = $this->link->fetch_assoc($res)) {

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

    public function force_import_field($field,$value){
        $this->force_import_field[$field] = $value;
    }

    public function add_structure($name, $inputType, $values = null, $default = null, $instructions = null, $foreigntable = null, $foreignkey = null){
        $this->structure[$name] = array ('display'        => $name,
                                         'input'          => $inputType,
                                         'values'         => $values,
                                         'display_filter' => array(),
                                         'edit_filter'    => array(),
                                         'instructions'   => $instructions,
                                         'foreigntable'   => $foreigntable,
                                         'foreignkey'     => $foreignkey,
                                         'default'        => $default);
    }

    public function set_caption($caption) {
        $this->caption = $caption;
    }

    public function set_orderby($orderby) {
        if(!$this->orderby) {
            $this->orderby = $orderby;
        }
    }

    public function set_orderdirection($orderby) {
        if(!$this->sortdir) {
            $this->sortdir = $orderby;
        } 
    }

    public function add_join_table($foreigntable,$foreignfield,$mainpk,$foreignName='',$fields='') {
        $this->joins[] = "LEFT JOIN $foreigntable ON $foreignfield=$mainpk";
        $this->foreign[$foreigntable]['foreignfield'] = $foreignfield;
        $this->foreign[$foreigntable]['mainpk']       = $mainpk;
        $this->foreign[$foreigntable]['name']         = $foreignName;
        $this->foreign[$foreigntable]['fields']       = $fields;

        $res = $this->link->consulta("DESC $foreigntable");
        while($row = $this->link->fetch_assoc($res)) {
            if ($row['Key'] == 'PRI'){
                $this->foreign[$foreigntable]['pk'] = $row['Field'];
            }
        }
    }

    public function set_groupby($groupby) {
        $this->groupby = $groupby;
    }

    public function set_condition($condition) {
        $this->condition = "WHERE ".$condition." ";
    }

    public function set_search($search) {
        $this->search = urlencode($search);
    }

    public function set_column_widths($widths) {
        $this->colwidths = $widths;
    }

    public function set_column_style($campo,$style) {
        if(array_key_exists($campo,$this->structure)) {
            $this->structure[$campo]['columnstyle']=$style;
        }
    }

    public function set_input_style($campo,$style) {
        if(array_key_exists($campo,$this->structure)) {
            $this->structure[$campo]['style']=$style;
        }
    }

    public function set_input_parent_style($campo,$style) {
        if(array_key_exists($campo,$this->structure)) {
            $this->structure[$campo]['parentstyle']=$style;
        }
    }

    public function set_field_explode($campo) {
        if(array_key_exists($campo,$this->structure)) {
            $this->structure[$campo]['explode']=1;
        } else {
           print_r($this->structure);
        }
    }

    public function set_input_type($campo,$tipo,$valores='') {
        if(array_key_exists($campo,$this->structure)) {
            $this->structure[$campo]['input']=$tipo;
            if(isset($valores)) {
                $this->structure[$campo]['values']=$valores;
            } 
        }
    }

    public function set_fields($fields) {
        $this->fields = $fields;
    }

    public function set_fields_edit($fields) {
        $this->fields_edit = $fields;
    }

    public function set_per_page($perpage) {
        $this->per_page = $perpage;
    }

    private function clean_entities($param) {
        return is_array($param) ? array_map('clean_entities', $param) : htmlspecialchars($param, ENT_QUOTES);
    }

    private function construct_count_query() {
        $query = "SELECT count(*) FROM ".$this->table." ";
        if(count($this->joins)>0) {
            foreach($this->joins as $idx=>$jointable) {
               $query .= $jointable." ";
            }
        }
        if($this->condition <> "") {
            $query .= " ".$this->condition;
        }
        if($this->search <> "") {
            $query_parts=array();
            foreach ($this->field_search as $key=>$val ){
                $searchstring = urldecode($this->search);
                if(preg_match("/\|\|/",$searchstring)) {
                   $partes_search = preg_split("/\|\|/",$searchstring);
                   $qstringpart = array();
                   foreach($partes_search as $itemsearch) {
                        $itemsearch=ltrim($itemsearch);
                        $itemsearch=rtrim($itemsearch);
                        $qstringpart[] = preg_replace("/\%/","\\%%",addslashes($itemsearch)."%%'");
                   }
                   $qstring_parts[] = "( ".implode($qstringpart).") ";
                } else {
                    $qstring = preg_replace("/\%/","\\%%",addslashes(urldecode($this->search)));
                    $query_parts[]= "$key LIKE '%%".$qstring."%%'";
                }
            }
            if(count($query_parts) > 0) {
                $searchquery=implode(" OR ",$query_parts);
                $query .= " AND ( ".$searchquery.") ";
            }
        }
        if($this->groupby <> "") {
           $query .= " ".$this->groupby;
        }
        return $query;
    }


    private function construct_query($limit=true,$group=true) {

        $vars   = Array();
        $return = Array();

        if($this->stopnow) {
            $return[] = "";
            $return[] = $vars;
            return $return;
        } 

        $query = "SELECT ".$this->fields." FROM ".$this->table." ";

        if(count($this->joins)>0) {
            foreach($this->joins as $idx=>$jointable) {
               $query .= $jointable." ";
            }
        }

        if($this->condition <> "") {
           $query .= $this->condition;
        }

        if($this->search <> "") {
            $query_parts=array();
            foreach ($this->field_search as $key=>$val ){
                $searchstring = urldecode($this->search);
                if(preg_match("/\|\|/",$searchstring)) {
                   $partes_search = preg_split("/\|\|/",$searchstring);
                   $qstringpart = array();
                   foreach($partes_search as $itemsearch) {
                        $itemsearch=ltrim($itemsearch);
                        $itemsearch=rtrim($itemsearch);
                        $qstring = preg_replace("/\%/","\\%%",addslashes($itemsearch));
                        $qstringpart[] = " ($key LIKE '%%".$qstring."%%') ";
                   }
                   $query_parts[] = "( ".implode(" OR ",$qstringpart).") ";
                } else {
                    $qstring = preg_replace("/\%/","\\%%",addslashes(urldecode($this->search)));
                    $query_parts[]= "$key LIKE '%%".$qstring."%%'";
                }
            }
            if(count($query_parts) > 0) {
                $searchquery=implode(" OR ",$query_parts);
                $query .= " AND ( ".$searchquery.") ";
            }
        }
 
        if($group) {
            if($this->groupby <> "") {
                $query .= $this->groupby;
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

    private function set_pagination() {

        $query   = $this->construct_count_query();
        $rst     = $this->link->consulta($query);

        if($this->groupby == "") {
            list($numrows) = $this->link->fetch_row($rst);
        } else {
            $numrows = $this->link->num_rows($rst);
        }
        $anc     = '';

        $next  = $this->page+1;
        $var   = ((intval($numrows/$this->per_page))-1)*$this->per_page;
        $last  = ceil($numrows/$this->per_page);

        $previous = $this->page-1;

        $anc = "<div class='text-center center-block'><ul class='pagination'>"; 

        if($previous <= 0){
            $anc .= "<li class='disabled'><a class='btn' title='".trans('First')."'>&laquo;</a></li><li class='disabled'><a class='btn' title='".trans('Previous')."'>&lsaquo;</a></li>";
        }else{
            $req_uri = $this->set_request('dbgrid_page',1,false);
            $anc .= "<li><a href='".SELF."$req_uri' class='btn' title='".trans('First')."'>&laquo;</a></li>\n";
            $req_uri = $this->set_request('dbgrid_page',$previous,false);
            $anc .= "<li><a href='".SELF."$req_uri' class='btn' title='".trans('Previous')."'>&lsaquo;</li></a>";
        }
        
        $norepeat = 4; // numero de paginas a mostrar a izq y der
        $anch     = "";
        $j = 1;

        $break=0;
        for($i=$this->page; $i>1; $i--){
            $page = $i-1;
            $req_uri = $this->set_request('dbgrid_page',$page,false);
            $anch = "<li><a href='".SELF."$req_uri' class='btn'>$page</a></li>".$anch;
            if($j == $norepeat) { $break=1; break; };
            $j++;
        }
        $anc .= $anch;

        $antes = $norepeat + 1 - $j;

        if($break==0) {
            $norepeat+=$antes;
        }

        $anc .= "<li class='active'><a class='btn'>".$this->page."</a></li>\n";

        $j = 1;

        for($i=$this->page; $i<$last; $i++){
            $page = $i+1;
            $req_uri = $this->set_request('dbgrid_page',$page,false);
            $anc .= "<li><a href='".SELF."$req_uri' class='btn'>$page</a></li>\n";
            if($j==$norepeat) break;
            $j++;
        }
        
        if($this->page >= $last){
            $anc .= "<li class='disabled'><a class='btn' title='".trans('Next')."'>&rsaquo;</a></li><li class='disabled'><a class='btn' title='".trans('Last')."'>&raquo;</a></li>\n";
        }else{
            $req_uri = $this->set_request('dbgrid_page',$next,false);
            $anc .= "<li><a href='".SELF."$req_uri' class='btn' title='".trans('Next')."'>&rsaquo;</a></li>";

            $req_uri = $this->set_request('dbgrid_page',$last,false);
            $anc .= "<li><a href='".SELF."$req_uri' class='btn' title='".trans('Last')."'>&raquo;</a></li>";
        }
        $anc.="</ul></div>\n";

        $this->pagination_anchors = $anc;

        $this->pagination_total = "<div style='text-align:center;' class='gray' >".trans('Page')." : $this->page <i> ".trans('of')."  </i> $last . ".trans('Total records found').": <span id='numrows_".$this->table."'>$numrows</span></div>";
    }

    public function hide_field($field) {
        if(is_array($field)) {
           foreach($field as $campo) {
               $this->field_hide[$campo]=1;
           }
        } else {
            $this->field_hide[$field]=1;
        }
    }

    public function set_default_values($fields,$values) {
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

    public function no_edit_field($field){
        if(is_array($field)) {
           foreach($field as $campo) {
               $this->field_no_edit[$campo]=1;
           }
        } else {
            $this->field_no_edit[$field]=1;
        }
    }

    public function edit_field_condition($field,$conditionfield,$cond,$value) {
        $this->field_edit_condition[$field]="$conditionfield|$cond|$value";
    }

    public function set_display_name($field,$newname) {
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
    public function set_search_fields($fields) {
        if(is_array($fields)) {
            foreach($fields as $campo) {
                $this->field_search[$campo]=1;
            }
        } else {
            $this->field_search[$fields]=1;
        }
     }

    public function add_error($error) {
        $this->error[]=$error;
    }

    public function add_notice($error) {
        $this->notice[]=$error;
    }

    public function add_custom_action($texto) {
         $this->custom_action[] = $texto;
    }
 
    public function add_custom_toolbar($texto) {
         $this->custom_toolbar[] = $texto;
    }
   /**
    * Adds an delete callback. Gets called when a row is deleted. All
    * row data is passed as an array to the callback function.
    *
    * @param callback $callback The callback to be used
    */
    public function add_delete_callback($callback) {
       if (is_callable($callback)) {
           $this->delete_callbacks[] = $callback;
       } else {
           $this->error[] = "Fallo al agregar delete callback - callback no valido";
       }
    }

    public function add_insert_callback($callback) {
       if (is_callable($callback)) {
           $this->insert_callbacks[] = $callback;
       } else {
           $this->error[] = "Fallo al agregar insert callback - callback no valido";
       }
    }
 
    public function add_update_callback($callback) {
       if (is_callable($callback)) {
           $this->update_callbacks[] = $callback;
       } else {
           $this->error[] = "Fallo al agregar update callback - callback no valido";
       }
    }

    public function add_validation_callback($field, $callback) {
        if (!is_callable($callback)) {
           $this->error[] = "Fallo al agregar validation callback - callback no valido";
        } else {
            if (!empty($this->structure[$field]) AND empty($this->field_no_edit[$field])) {
                $this->validation_callbacks[$field][] = $callback;
            }
        }
    }

    public function add_extra_attribute($field, $attr) {
        $this->extra_attributes[$field] = $attr;
    }

    public function add_validation_type($field, $type) {
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

    public function add_display_filter($field, $callback) {

        if (is_callable($callback) AND isset($this->structure[$field])) {
            $this->structure[$field]['display_filter'][] = $callback;

        } else if (is_callable($callback)) {
            $this->error[] = "Unknown field: $field";
            //$this->structure[$field]['display_filter'][] = $callback;

        } else {
            $this->error[] = "Fallo al agregar display filter - callback no valido";
        }
    }

    /**
    * Applys display filters to a single row of data. Does htmlspecialchars() first.
    *
    * @param array &$results Data from table
     */
    private function apply_display_filters(&$raw,&$row) {
        foreach ($row as $field => $value) {
            if (!empty($this->structure[$field]['display_filter'])) {
                foreach ($this->structure[$field]['display_filter'] as $f) {
                    $value = call_user_func($f, $value, $row);
                }
                $raw[$field] = $value;
            }
        }
    }

    public function add_edit_filter($field, $callback) {
        if (is_callable($callback) AND isset($this->structure[$field])) {
            $this->structure[$field]['edit_filter'][] = $callback;

        } else if (is_callable($callback)) {
            $this->error[] = "Unknown field: $field";

        } else {
            $this->error[] = "Fallo al agregar edit filter - callback no valido";
        }
    }

    private function get_edit_filtered($field,$value) {
        if (!empty($this->structure[$field]['edit_filter'])) {
            foreach ($this->structure[$field]['edit_filter'] as $f) {
                $value = call_user_func($f, $value);
            }
        } 
        return $value;
    }

    private function check_validation_type($field,$value) {
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

    public function show_grid() {
        switch($this->dbgrid_action) {
            case 'csv':
                if(isset($_REQUEST['dbgrid_search'])) {
                    $this->search=urlencode($_REQUEST['dbgrid_search']);
                }
                $this->export_csv();
                break;
            case 'pdf':
                if(isset($_REQUEST['dbgrid_search'])) {
                    $this->search=urlencode($_REQUEST['dbgrid_search']);
                }
                $this->export_pdf();
                break;
            case 'import':
                $this->import_csv();
                break;
            case 'delete_marked':
                $this->delete_marked();
                break;
            case 'foreignDelete':
                $this->ajax_foreign_delete();
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
                    $this->show_form($this->res_request[$this->pk]);
                } else {
                    print_r($_REQUEST);
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

    private function print_toolbar() {

        $buttons='';

        // CAPTION
        if($this->caption <> "") {
            $buttons.= "<h3 style='background:#da2;'>".$this->caption."</h3>\n";
        }

        $buttons.= "<div class='col-lg-8 col-md-8 col-sm-6 col-xs-6'>";

        if ($this->allow_add) {
            $req=$this->set_request('dbgrid_action','add',true);
            $buttons.= "
            <a href='#' class='btn btn-default' onclick='javascript:location=\"".SELF.$req."\"'><i class='glyphicon glyphicon-plus'></i> ".trans('Add')."</a>";
        }

        if ($this->allow_delete) {
            $req=$this->set_request('dbgrid_action','delete_marked',false);

            $buttons.="
                        <a class='btn btn-danger' disabled='disabled' id='erasemarked'><i class='glyphicon glyphicon-trash icon-white'></i> ".trans('Delete Marked')."</a>";

        }

        if ($this->allow_export) {
            $req=$this->set_request('dbgrid_action','csv',false);
            $buttons.="<div class='btn-group' style='margin-left:4px;'>
                    <a class='btn dropdown-toggle' data-toggle='dropdown'><i class='glyphicon glyphicon-upload'></i> ".trans('Export')." <span class='caret'></span></a>
                    <ul class='dropdown-menu'>
                       <li><a onclick='javascript:location=\"".SELF.$req."\"' >CSV</a></li>";
            $req=$this->set_request('dbgrid_action','pdf',false);
            $buttons.="
                       <li><a onclick='javascript:location=\"".SELF.$req."\"' >PDF</a></li>
                    </ul></div>
            ";

        }

        if ($this->allow_import) {
            $req=$this->set_request('dbgrid_import','',false);
            $params = explode("&amp;",$req);
            $buttons.="<div class='btn-group' style='margin-left:10px;'>
                <a class='btn dropdown-toggle' data-toggle='dropdown'><i class='glyphicon glyphicon-download'></i> ".trans('Import')." <span class='caret'></span></a>
                <ul class='dropdown-menu'><li>
                <form method='post' id='dbgrid_importmyfile' action='".SELF."' enctype='multipart/form-data' class='well form-inline form-horizontal'>
                <input type='hidden' name='dbgrid_action' value='import' >
                <input type='file' name='dbgrid_import' onChange='document.forms.dbgrid_importmyfile.submit();'>
                </form>
                </ul>
                </div>
            ";
        }

        // Custom toolbar
        foreach($this->custom_toolbar as $texto) {
            $buttons.=$texto;
        }

        $buttons.= "</div><div class='col-lg-4 col-md-4 col-sm-6 col-xs-6'>";

        if ($this->allow_search) {
            $req=$this->set_request('dbgrid_action','search',true);
            $req=$this->del_request('dbgrid_search');
            $req=$this->set_request('dbgrid_page',1,false);
            $params = explode("&",$req);
            $buttons.="
                 <form method='post' action='".SELF.$req."' class='form-search' style='float:right;'>
                 <div class='btn-group'><input type='text' id='dbgrid_search' name='dbgrid_search' class='form-control input-medium search-query' style='padding-right:20px;' value='".urldecode($this->search)."' >";
            $buttons.="<span id='dbgrid_search_x' class='inside-input glyphicon glyphicon-remove-circle'></span></div>";

            foreach($params as $v) {
               list($key,$val)=preg_split("/=/",$v);
               if ($key=='dbgrid_sortdir' || $key=='dbgrid_sort'){
                  $buttons.="<input type=hidden name='$key' value='$val'>\n";
               }
            }
            $buttons.="
                 <button type='submit' class='btn'><i class='glyphicon glyphicon-search'></i> ".trans('Search')."</button>
                  </form>
            ";
        }
        $buttons.="</div><div class='clearfix'></div>";
        $this->del_request("dbgrid_action");
        $this->set_request("dbgrid_search",$this->search,true);
        echo $buttons;

        echo "<div id='markAllWarning' style='display:none;'></div>";
    }

    private function import_csv(){

        $valid_field = Array();

        foreach ($this->structure as $field => $value){
            if(trim($field)<>"id") {
                $valid_field[]=trim($field);
            }
        }

        $arrFile = $_FILES['dbgrid_import'];
        $file = $arrFile['tmp_name'];

        if ($arrFile['size']>0 && !empty($file)) {
            if(!preg_match("/\.csv$/",$arrFile['name'])) {
                $this->add_error(trans('Could not upload file'));
            } else {
                if (is_uploaded_file($file)) {
                    if (copy ($file, $this->user_directory."importCSV-".$arrFile['name'])) {
                        $this->name_upload="importCSV-".$arrFile['name'];
                    }else{
                        $this->add_error(trans('Could not copy uploaded file'));
                    }
                }else{
                    $this->add_error(trans('Could not upload file'));
                }
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
                        if(!array_key_exists(trim($campo),$this->force_import_field)) {
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
                    unlink($mifilename);
                    die("no valid fields to import");
                }

                foreach ($this->force_import_field as $key=>$val) {
                    $parasql[]=$key;
                    $forcedvalues[]=mysql_real_escape_string($val);
                }

                $columnsql=implode(",",$parasql);

                if(count($forcedvalues)>0) {
                    $forceval=",'".implode("','",$forcedvalues)."'";
                } else {
                    $forceval="";
                }

                foreach($importar as $linea) {
                    $linea = trim($linea);
                    if($linea=="") { $cuantos--; continue; };
                    $misdatos = Array();
                    $columns=explode(",",$linea);
                    foreach($valid_field_number as $nro) {
                        $misdatos[]=mysql_real_escape_string(trim($columns[$nro]));
                    }

                    $valuesql=implode("','",$misdatos);
                    $query = "INSERT INTO $this->table ($columnsql) VALUES ('$valuesql'$forceval)";
                    $this->link->consulta($query);
                }
                $cuantos--;
                $this->add_notice($cuantos." ".trans('Records imported'));
            }
        }
        $this->print_grid();
    }

    private function delete_marked(){
        $myids = $this->res_request["dbgrid_delete_id"];
        $this->del_request("dbgrid_action");
        $this->del_request("dbgrid_delete_id");
        if($myids <> "") {
            if($myids == "wholeSelection") {
                $this->delete_rows('todos','marked');
            } else {
                $ids_a_borrar = explode(",",$myids);
                $this->delete_rows($ids_a_borrar,'marked');
            }
        } else {
            $this->print_grid();
        }
    }

    private function ajax_foreign_delete(){
        $id_to_delete = $_POST['foreignId'];
        $tableForeign = $_POST['foreignTable'];

        if(md5($this->salt.$id_to_delete) <> $_POST['dbgrid_pkhash']) {
            die('Trampa con el formulario');
        } else {

            $query = "DELETE FROM %s WHERE %s='%s'";
            $params = array($tableForeign, $this->foreign[$tableForeign]['pk'], $id_to_delete);
            $this->link->consulta($query,$params);

            if($this->link->affected_rows()>0) {
                //header("HTTP/1.0 404 Not Found");
            } else {
                header("HTTP/1.0 404 Not Found");
            }
            die();
        }
    }

    private function export_pdf() {

        list ($query,$vars) = $this->construct_query(false);

        $res = $this->link->consulta($query,$vars);
        if($this->link->num_rows($res)==0) {
            $this->add_error(trans('There are no records to export'));
            $this->print_grid();
            return;
        }
        @ob_clean();

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

        echo "<html><head>";
        echo "</head> ";
        echo "<body style='_width:560px; _padding:0;'>\n";

        echo "<table class='first' cellpadding='5' cellspacing='2' border=0>\n";

        if($this->link->num_rows($res)>0) {
            echo "\n<thead>";
            echo  $this->table_head($headings_final);
            echo "</thead>\n";
        }
        echo "<tbody>\n";
        $cont=1;
        if ($this->link->num_rows($res) > 0) {

            while ($r = $this->link->fetch_assoc($res)) {
                // oculta los campos hide
                if(is_callable("array_diff_key")) {
                    $j = array_diff_key($r,$this->field_hide);
                } else {
                    $j = PHP4_array_diff_key($r,$this->field_hide);
                }
                $class = $cont%2?'':'active';
                echo $this->table_row($j,$class,$r[$this->pk],$r,$cont);
                $cont++;
            }

        }
        echo "</tbody>\n";
        echo "</table>\n";
        echo "</body>\n</html>";

        // DOMPDF
        require_once(dirname(__FILE__)."/dompdf/dompdf_config.inc.php");
        $dompdf = new DOMPDF();
        $dompdf->set_paper('A4','landscape');
        $dompdf->load_html(utf8_decode(ob_get_contents()));
        $dompdf->render();
        @ob_end_clean();

        $report_name = urldecode($this->table).".pdf";

        $dompdf->stream($report_name);

        die();
    }

    private function Unaccent($string) {
        return preg_replace('~&([a-z]{1,2})(acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml);~i', '$1', htmlentities($string, ENT_QUOTES, 'UTF-8'));
    }

    private function export_csv() {

        global $CONFIG;

        if(isset($CONFIG['csv_delimiter'])) {
            $delimiter = $CONFIG['csv_delimiter'][''];
        } else {
            $delimiter=",";
        }

        list ($query,$vars) = $this->construct_query(false);

        $res = $this->link->consulta($query,$vars);

        if($this->link->num_rows($res)==0) {
            $this->add_error(trans('There are no records to export'));
            $this->print_grid();
            return;
        }
        @ob_end_clean();

        $filename = $this->table.".csv";
        header('Content-Type: text/csv');
        header("Content-Disposition: attachment; filename=$filename");
        $cont=1;
        while ($row = $this->link->fetch_assoc($res)) {
            if($cont==1) {
                $columnas = Array();
                foreach($row as $columna=>$valor) {
                    $columnas[] = $columna;
                }
                $header = implode($delimiter,$columnas);
                echo "$header\n";
            } 
            $milinea = Array();
            foreach($row as $columna=>$valor) {
                $milinea[] = $valor;
            }
            $linea = implode($delimiter,$milinea);
            echo "$linea\n";
            $cont++;
        }
        die();
    }

    private function print_grid() {

        // construye la consulta en base a modificadores, etc.
        list ($query,$vars) = $this->construct_query();

        $this->print_javascript();

        if($this->link->error()) {
            $this->print_errors();
            return;
        }

        // realiza la consulta
        $res = $this->link->consulta($query,$vars);

        // comienza a imprimir la tabla
        $this->print_errors();

        echo "<input type='hidden' id='dbgrid_selectAll' name='dbgrid_selectAll' value='0'>\n";
        echo "<div class='xcontainer-fluid'>\n";
        echo "<div class='row-fluid'>\n";

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

        $this->print_toolbar();
        echo "</div>\n";

        echo "<div class='row-fluid'>";
        echo "<div class='col-lg-12'>";
        echo "<table class='table' id='maintable_".$this->table."'>\n";

        $countcontrol=0;
        if ($this->allow_edit) {
            $countcontrol++;
        }
        if ($this->allow_view) {
            $countcontrol++;
        }
        if ($this->allow_delete) {
            $countcontrol++;
        }

        $acciones = count($this->custom_action);
        $countcontrol+=intval($acciones);

        $controlwidth = ($countcontrol * 52) + 14;

        $contcol=0;
        if($countcontrol>0) {
            $contcol++;
            //        echo "<col width='$controlwidth'>";
        }

        if($this->nocheckbox != 1) {
            echo "<col width='36'>";
            $contcol++;
        }

        foreach($this->colwidths as $width) {
            echo "<col width='{$width}'>";
            $contcol++;
        }

        if($this->link->num_rows($res)>0) {
            echo "<thead>";
            echo  $this->table_head($headings_final,$orden);
            echo "</thead>";
        }
        echo "<tbody>\n";

        $sumacolspan=0;
        $cont=0;
        if ($this->allow_edit) {
            $cont++;
        }
        if ($this->allow_delete) {
            $cont++;
        }
        if($this->allow_view) {
            $cont++;
        }

        if($cont>0) {
            $sumacolspan=1;
        }
        $cont=0;

        $colspan = count($headings_final)+1+$sumacolspan;
        if($contcol>$colspan) { $colspan = $contcol; }

        if($this->nocheckbox == 1) {
            $colspan--;
        }

        $cont=0;

        if ($this->link->num_rows($res) > 0) {

            while ($r = $this->link->fetch_assoc($res)) {
                // oculta los campos hide
                if(is_callable("array_diff_key")) {
                    $j = array_diff_key($r,$this->field_hide);
                } else {
                    $j = PHP4_array_diff_key($r,$this->field_hide);
                }
                $class = $cont%2?'':'active';
                echo $this->table_row($j,$class,$r[$this->pk],$r,$cont);
                $cont++;
            }

        } else {
            //echo "<tr><td colspan='$colspan'><div style='text-align:center; font-weight: bold;'>".trans('No records found')."</div></td></tr>";
            echo "<tr><td><div style='text-align:center; font-weight: bold;'>".trans('No records found')."</div></td></tr>";
        }
        //echo "</form></tbody>";
        echo "</tbody>";
        echo "</table>\n";

        if ($this->link->num_rows($res) > 0) {
            // FOOTER
            echo "<table id='foottable' style='table-layout:fixed; text-align:center; width:100%;'>\n";
            echo "<tfoot><tr><th colspan='".$colspan."'>";
            $this->set_pagination();
            echo $this->pagination_anchors;
            echo $this->pagination_total;
            echo "</th></tr></tfoot>";
        }

        // END
        //echo "</table>\n";
        echo "</div>\n";
        echo "</div>\n";
        //echo "</form>";


        return false;    

    }

    private function add_form() {

        if (!$this->allow_add) {
            return;
        }

        $req = substr($this->set_request('dbgrid_action','addSave',false),1);
        $params = explode("&amp;",$req);

        $this->print_javascript('add');
        $this->print_errors();

        echo "<div class='xcontainer'>\n";
        echo "<div class='row-fluid'>\n";
        echo "<div class='span12'>\n";
        echo "<form method='post' class='well form-horizontal' dbgrid_action='".SELF."' enctype='multipart/form-data' id='dbgrid-edit-form'>\n";

        // comienza a imprimir la tabla
        foreach($params as $v) {
            list($key,$val)=preg_split("/=/",$v);
            echo "<input type=hidden name='$key' value='$val'>\n";
        }
        echo "<fieldset>\n";
        echo "<legend>".trans('Add Record')."</legend>\n";
        echo "\n";
        foreach ($this->structure as $field => $value){
            if(isset($this->field_no_edit[$field]) && $field!=$this->pk) {
                continue;
            }
            if ($field!=$this->pk)    {
                if($this->structure[$field]['foreigntable']=='') {
                    $this->create_body_form($field,$this->structure[$field]['default'],"add");    
                }
            }

        }

        if(count($this->foreign)>0) {
            foreach ($this->foreign as $ftable=>$datis) {
                $campos = $datis['fields'];
                if($campos<>"") {
                    echo "</fieldset>\n<fieldset id='foreignFieldset_$ftable' >\n<legend>".$datis['name']."</legend>\n";
                    // Tengo configurado campos en add_join_table, es para editar/agregar en formularios
                    echo "<div id='foreignAdd_$ftable'></div>";

                    echo "<div id='foreignNextBlock_$ftable' style='display:none;'>";
                    foreach(explode(",",$campos) as $field) {
                        $this->create_body_form($field,'',"edit",'x');
                    }
                    echo "</div>";

                    echo "<div class='form-actions pull-right'>";
                    echo "<input type='button' class='btn btn-primary' onclick='cloneForeignBlock(\"$ftable\")' value='".trans('Add')."'>";
                    echo "</div>";
                }
            }
        }
        echo "</fieldset>\n";

        $req = $this->set_request('dbgrid_action','list',true);
        $req = $this->del_request($this->pk);

        echo "<div class='form-actions'>
            <input type='submit' class='btn btn-primary' name='button_ok' value='".trans('Save')."'>
            <input type='submit' class='btn btn-warning' name='button_cancel' value='".trans('Cancel')."' onClick='javascript:location=\"".SELF."$req\"; return false;'>
            </div>";
        echo "</form></div></div>";

    }

    private function insert_row() {

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
        $foreign_fields = array();

        foreach ($this->structure as $field => $value){

            if (array_key_exists($field,$_POST)){
                // Procesa validacion
                if (!empty($this->validation_type[$field])) {
                    $this->check_validation_type($field,$_POST[$field]);
                }

                // Procesa callback de validacion
                if (!empty($this->validation_callbacks[$field])) {
                    foreach ($this->validation_callbacks[$field] as $c) {
                        $_POST[$field] = call_user_func($c, $this, $_POST[$field], $_POST);
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

        // Me fijo si los campos son inserciones de tablas foreign
        foreach ($_POST as $field => $value){
            if(preg_match("/x_insert_/",$field)) {
                $partes = preg_split("/_x_insert_/",$field);
                list ($tabla, $campo) = preg_split("/_/",$partes[0]);

                // Validaciones de campo foreign tabla.campo
                $ffield = $tabla.".".$campo;
                if (!empty($this->validation_type[$ffield])) {
                    $this->check_validation_type($field,$_POST[$field]);
                }
                if (!empty($this->validation_callbacks[$ffield])) {
                    foreach ($this->validation_callbacks[$ffield] as $c) {
                        $_POST[$field] = call_user_func($c, $this, $_POST[$field], $_POST);
                    }
                }
                $foreign_fields[$tabla][$campo]=$_POST[$field];
            }
        }

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
                } else if($this->structure[$field]['input']=='bitmask') {
                    $totbit=0;
                    foreach($value as $indv) {
                        $totbit+=$indv;
                    }
                    $value=$totbit;
                } else if($this->structure[$field]['input']=='multiselect') {
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
            if (sizeof($name_temp)>0) {
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

            foreach($foreign_fields as $uptable => $ffields) {
                if(count($ffields)>0) {
                    $query = "INSERT INTO %s SET ";
                    $query_parts = array();
                    $queryval    = array();
                    $queryval[]  = $uptable;
                    foreach ($ffields as $field => $value){
                        $query_parts[]= "%s='%s'";
                        $queryval[]=$field;
                        $queryval[]=$value;
                    }
                    // Insertamos foreign key
                    $query_parts[]= "%s='%s'";
                    $queryval[]=$this->foreign[$uptable]['foreignfield'];
                    $queryval[]=$insert_id;
                    $query.=implode(",",$query_parts);
                    $this->link->consulta($query,$queryval);
                }
            }

            if($callbacks) {
                $add_fields[$this->pk]=$insert_id;
                foreach ($this->insert_callbacks as $c) {
                    call_user_func($c, $add_fields);
                }
            }

        } else {
           $this->set_request('dbgrid_error',trans('Error inserting record:<br>').$this->link->error(),true);
           // borrar archivo subido
        }

        $this->del_request("button_ok");
        $req=$this->set_request('dbgrid_action','list',true);

        header("Location: ".SELF."$req"); 
    }

    private function print_javascript($accion='') {

        $req=$this->set_request('dbgrid_action','delete_marked',true);
        $req=$this->set_request('dbgrid_pkhash',md5($this->salt.'marked'),false);

         echo "

    <script>

    \$(document).ready(function() {

        \$(\"#maintable_".$this->table."\").colResizable({});

        \$('.datePick').datetimepicker({format: 'YYYY-MM-DD', showTodayButton: true});

        \$(\".chz\").chosen();
        \$('.chosen-drop').css({minWidth: '100%', width: 'auto'});

        $(function () {
           $('[data-toggle=\"tooltip\"]').tooltip({container:'body'})
        })

        setjconf();

        \$('#dbgrid_search').keyup(function() {
            if (\$.trim($('#dbgrid_search').val()) == '') {
                \$('#dbgrid_search_x').fadeOut();
            } else {
                \$('#dbgrid_search_x').fadeIn();
            }
        });

        $('#dbgrid_search_x').click(function() {
            \$('#dbgrid_search').val('');
            \$(this).hide();
        });

        if (\$.trim($('#dbgrid_search').val()) != '') {
            \$('#dbgrid_search_x').fadeIn();
        }

        setTimeout(function() { \$(\".alert\").fadeOut(1000); },5000);

    ";

        if($accion=="edit" || $accion=="add") {

            echo "
        \$(\".dateTime\").datetimeEntry({spinnerImage: '', datetimeFormat: 'Y-O-D H:M:S'});;

        $('#dbgrid-edit-form').validator({messageClass:'span alert alert-error pad4'});

    ";
        }

        echo "

});  // end of document ready

function setjconf(elm) {
    if(elm===undefined) {
        \$('.jconf').jConf( {
            'sText': '".trans('Are you sure?')."',
            'okBtn': '".trans('Yes')."',
            'noBtn': '".trans('No')."',
            'callResult': function(data){
                if(data.inputVal=='jconfBtnOK') {
                    if(data.oElem.attr('id')=='erasemarked') {
                        prepare_marked();
                    } else if(data.oElem.attr('id').indexOf('deleteForeign')==0) {
                        partes = data.oElem.attr('id').split('_');
                        table_to_delete = partes[1];
                        id_to_delete = partes[2];
                        sechash = partes[3];
                        foreignDelete(table_to_delete,id_to_delete,sechash);
                    } else {
                        window.location=data.oElem.attr('id'); 
                    }
                }   
            }
        });
    } else {
        if(elm.attr('isjconf')===undefined) {
            elm.attr('isjconf','1');
            elm.jConf( {
               'sText': '".trans('Are you sure?')."',
               'okBtn': '".trans('Yes')."',
               'noBtn': '".trans('No')."',
               'callResult': function(data){
                   if(data.inputVal=='jconfBtnOK') {
                       if(data.oElem.attr('id')=='erasemarked') {
                           prepare_marked();
                       } else if(data.oElem.attr('id').indexOf('deleteForeign')==0) {
                           partes = data.oElem.attr('id').split('_');
                           table_to_delete = partes[1];
                           id_to_delete = partes[2];
                           sechash = partes[3];
                           foreignDelete(table_to_delete,id_to_delete,sechash);
                       } else {
                           window.location=data.oElem.attr('id'); 
                       }
                   }   
               }
            });
        }
    }

}

function cloneForeignBlock(table) {
    var clone = $('#foreignNextBlock_'+table).clone('deepWithDataAndEvents').removeAttr('id').appendTo('#foreignAdd_'+table).show();
    var cont=0;
    $('#foreignAdd_'+table).find(':input').each(function() {
        if($(this).attr('name')!==undefined) {
            var original_name = $(this).attr('name');
            if(original_name.indexOf('_insert_')>1) {
                cont++;
            }
        }
    });
    clone.find(':input').each(function() { 
        var original_name = $(this).attr('name');
        $(this).attr('name',original_name+'_insert_'+cont);
    });
    clone.find('select').each(function() { $(this).chosen(); });
}

function foreignDelete(table,id,sechash) {
    page = window.location.pathname.substring(window.location.pathname.lastIndexOf('/')+1);
    $.ajax({
        url: page,
        type: 'POST',
        data: 'dbgrid_action=foreignDelete&foreignId='+id+'&foreignTable='+table+'&dbgrid_pkhash='+sechash,
        statusCode: {
            404: function() {
                alert('error');
            },
            200: function() {
                $('#foreign_row_'+table+'_'+id).remove();
                $('#foreign_row_delete_'+table+'_'+id).remove();
            }
        }
    });
};

function prepare_marked() {
    var inputs = document.getElementsByTagName('input');
    var totnumero='';
    for (var i=0; i < inputs.length; i++) {
        if(inputs[i].id.indexOf('dbgrid_checkbox')==0) {
            if(inputs[i].checked) {
                var minumero = inputs[i].id.substring(16);
                totnumero+=minumero+',';
            }
        }
    }
    if(totnumero.length>0) {
        totnumero = totnumero.substring(0,totnumero.length-1);
        if($('#dbgrid_selectAll').val()==1) {
            location = '".SELF.$req."&amp;dbgrid_delete_id=wholeSelection';
        } else {
            location = '".SELF.$req."&amp;dbgrid_delete_id='+totnumero;
        }
    }
}

function markCheckBox(n,state) {
  cont=0;
  n.find('input[type=checkbox]').each(function(index) {
      if($(this).attr('id').indexOf('dbgrid_checkbox')==0) {
          $(this).prop('checked',state);
          cont++;
      }
  });
  return cont;
}

function clearCheckAll(elm) {
   if($(elm).is(':checked')) {
   } else {
       $('#markAllWarning').fadeOut(); 
       $('#checkall').prop('checked',false);
       $('#dbgrid_selectAll').val(0);
       $('#erasemarked').prop('disabled',true);
       $('#erasemarked').unbind('click');
   } 

   var enableCheck=0;
   $('#maintable_".$this->table."').find('input[type=checkbox]').each(function(index) {
      if($(this).attr('id').indexOf('dbgrid_checkbox')==0) {
          if($(this).is(':checked')) {
              enableCheck++;
          }
      }
   });
   debug(enableCheck);

   if(enableCheck>0) {
       $('#erasemarked').removeAttr('disabled');
       setjconf($('#erasemarked'));
   } else {
       $('#erasemarked').attr('disabled','disabled');
       $('#erasemarked').unbind('click');
   }

}

function checkAll(elm,table) {

    field = $('#'+table);

    if($(elm).is(':checked')) {
       state=true;
       $('#erasemarked').removeAttr('disabled');
       setjconf($('#erasemarked'));
    } else {
       state=false;
       $('#markAllWarning').html('').fadeOut(); 
       $('#dbgrid_selectAll').val(0);
       $('#erasemarked').attr('disabled','disabled');
       $('#erasemarked').unbind('click');
    }

    var countChecked = markCheckBox(field,state);
    var num = parseInt($('#numrows_".$this->table."').html(),10);

    if(num > countChecked) {
        // Hay mas registros que marcados, ofrecemos
        if(state===true) {
            var mensaje='<div class=\'alert alert-danger alert-dismissible fade in\' style=\'text-align:center;\'><button type=\'button\' class=\'close\' data-dismiss=\'alert\' aria-label=\'Close\'><span aria-hidden=\'true\'>×</span></button><span id=\'dbgridmsg1\'>All '+countChecked+' recors on this page are selected.</span> <a href=\'#\' onclick=\"setSelectAll(1,\''+table+'\'); event.returnValue=false; return false;\"><span id=\'dbgridmsg2\'>Select all '+num+' records.</a></div>';
            $('#markAllWarning').html(mensaje).hide(); 
            $('#dbgridmsg1').load('lib/translate.php','string=All %s records on this page are selected.&var1='+countChecked);
            $('#dbgridmsg2').load('lib/translate.php','string=Select all %s records.&var1='+num);
            $('#markAllWarning').fadeIn(); 
        } 
    } 
}

function setSelectAll(value,table) {
   field = $('#'+table);
   $('#dbgrid_selectAll').val(value);
   var num = parseInt($('#numrows_".$this->table."').html(),10);
   if(value==1) {
       var mensaje='<div class=\'alert alert-info\' style=\'text-align:center;\'><span id=\'dbgridmsg3\'>All '+num+' records are selected.</span> <a href=\'#\' onclick=\"setSelectAll(0,\''+table+'\'); event.returnValue=false; return false;\"><span id=\'dbgridmsg4\'>Clear Selection</span></a></div>';
       $('#markAllWarning').html(mensaje);
       $('#dbgridmsg3').load('lib/translate.php','string=All %s records are selected.&var1='+num);
       $('#dbgridmsg4').load('lib/translate.php','string=Clear Selection');
       $('#markAllWarning').fadeIn(); 
   } else {
       $('#markAllWarning').fadeOut(); 
       markCheckBox(field,false);
       $('#checkall').attr('checked',false);
       $('#dbgrid_selectAll').val(0);
       $('#erasemarked').attr('disabled','disabled');
       $('#erasemarked').unbind('click');
   }
}

function hidediv(id) {
    $('#'+id).fadeOut('slow')
}

";

        echo "</script>\n";
    }

    private function print_errors() {

        global $jsnotifications;

        if($jsnotifications==1) {

            echo "<script type='text/javascript'>\n";

            if(count($this->error)>0) {
                foreach($this->error as $error) {
                    $error = addslashes($error);
                    echo "noty({
                    title: '".trans('An error has occurred')."',
                        type: 'error',
                        text: '".$error."' });";
                }
            } else if (count($this->notice)>0) {
                foreach($this->notice as $notice) {
                    $notice = addslashes($notice);
                    echo "noty({
                        title: '".trans('Success!')."',
                            type: 'success',
                            text: '".$notice."' });";
                }
            }
            echo "</script>\n";

        } else {

            if(count($this->notice)>0) {
                echo "<div class='alert alert-success'>\n";
                echo "  <a class='close' data-dismiss='alert'>x</a>\n";
                echo "  <strong>".trans('Success!')."</strong> \n";
                foreach($this->notice as $notice) {
                    echo "<p>$notice</p>";
                }
                echo "</div>\n";
            }

            if(count($this->error)>0) {
                echo "<div class='alert alert-error' id='errorbox'>\n";
                echo "  <a class='close' data-dismiss='alert'>x</a>\n";
                echo "  <strong>".trans('An error has occurred')."</strong> \n";

                foreach($this->error as $error) {
                    echo "  <p>$error</p>\n";
                }
                echo "</div>\n";
            }
        }
    }

    private function edit_form($id) {

        if (!$this->allow_edit) {
            return;
        }

        echo "<div class='xcontainer'>\n";
        echo "<div class='row-fluid'>\n";
        echo "<div class='span12'>\n";

        $params = array( $this->fields_edit, $this->table, $this->table.'.'.$this->pk, $id );
        $query = "SELECT %s FROM %s WHERE %s = '%s'";

        // realiza la consulta
        $res = $this->link->consulta($query, $params);
        $this->set_request('dbgrid_action','editSave',true);
        $req = substr($this->set_request($this->pk,$id,false),1);

        $params = explode("&amp;",$req);

        $this->print_errors();
        $this->print_javascript('edit');

        echo "<form method='post' action='".SELF."' class='well form-horizontal' enctype='multipart/form-data' id='dbgrid-edit-form'>\n";

        // comienza a imprimir la tabla
        foreach($params as $v) {
            list($key,$val)=preg_split("/=/",$v);
            echo "<input type=hidden name='$key' value='$val'>\n";
            if($this->pk == $key) {
                echo "<input type=hidden name='dbgrid_pkhash' value='".md5($this->salt.$val)."'>";
            }
        }

        echo "<fieldset>\n";
        echo "<legend>".trans('Edit Record')."</legend>\n";

        if ($this->link->num_rows($res) > 0) {

            $cont=0;

            $rowresult = $this->link->fetch_assoc($res);

            foreach ($rowresult as $field=>$value) {
                $valor_de[$field]=$value;
            }

            foreach ($rowresult as $field=>$value) {
                if (isset($this->field_edit_condition[$field])){
                    if ($this->field_edit_condition[$field]){
                        list($condfield,$condcond,$condvalue) = preg_split("/\|/",$this->field_edit_condition[$field]);
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
                if($this->structure[$field]['foreigntable']=='') {
                    // los campos foregin los editamos mas adelante
                    $this->create_body_form($field,$value,"edit");
                } 
            }

            if(count($this->foreign)>0) {
                foreach ($this->foreign as $ftable=>$datis) {
                    $campos = $datis['fields'];
                    if($campos<>"") {
                        echo "</fieldset>\n<fieldset id='foreignFieldset_$ftable' >\n<legend>".$datis['name']."</legend>\n";
                        // Tengo configurado campos en add_join_table, es para editar/agregar en formularios
                        $ras = $this->link->consulta("SELECT %s,%s FROM %s WHERE %s=%s",$datis['pk'],$campos,$ftable, $datis['foreignfield'], $rowresult[$this->pk]);
                        $cont=1;

                        while($row = $this->link->fetch_assoc($ras)) {
                            $cont++;
                            $mipk = array_shift($row);

                            $sechash = md5($this->salt.$mipk);
                            echo "<div class='controls pull-right' id='foreign_row_delete_{$ftable}_{$mipk}'><a id='deleteForeign_{$ftable}_{$mipk}_{$sechash}' class='btn btn-warning jconf' data-toggle='tooltip' data-placement='top'  title='".trans("Delete")."'><i class='glyphicon glyphicon-trash'></i></a></div>";
                            echo "<div id='foreign_row_{$ftable}_{$mipk}'>";
                            foreach ($row as $field=>$value) {
                                $this->create_body_form($ftable.".".$field,$value,"edit",$mipk);
                            }
                            echo "</div>";
                        }
                        echo "<div id='foreignAdd_$ftable'></div>";

                        echo "<div id='foreignNextBlock_$ftable' style='display:none;'>";
                        foreach(explode(",",$campos) as $field) {
                            $this->create_body_form($field,'',"edit",'x');
                        }
                        echo "</div>";

                        echo "<div class='form-actions pull-right'>";
                        echo "<input type='button' class='btn btn-primary' onclick='cloneForeignBlock(\"$ftable\")' value='".trans('Add')."'>";
                        echo "</div>";
                    }
                }
            }

            $req = $this->set_request('dbgrid_action','list',true);
            $req = $this->del_request($this->pk);
            echo "</fieldset>\n";
            echo "<div class='form-actions'>
                <input type='submit' class='btn btn-primary' name='button_ok' value='".trans('Save')."'>
                <input type='submit' class='btn btn-warning' name='button_cancel' value='".trans('Cancel')."' onClick='javascript:location=\"".SELF."$req\"; return false;'>
                </div>";
        //Fin de Row > 0 
        }
        echo "</form>";
        echo "</div>";
    }

    private function update_row($id) {

        $status="OK";
        $callbacks = !empty($this->update_callbacks);
        $update_fields=array();

        if(md5($this->salt.$id) <> $_POST['dbgrid_pkhash']) {
            $this->add_error("Trampa con el formulario");
        }

        $extra_query=Array();
        $insertForeign = Array();
        $insertForeignData = Array();

        foreach ($this->structure as $field => $value) {
            if (!(array_key_exists($field,$this->field_no_edit))) {
                if(isset($this->structure[$field]['foreigntable'])) {
                    $uptable = $this->structure[$field]['foreigntable'];
                    $foreignpk = $this->foreign[$uptable]['pk'];
                    foreach($_POST as $mkey=>$mval) {
                        if(preg_match("/^$field/",$mkey)) {
                            $fkey_partes = preg_split("/_/",$mkey);
                            $fkey = array_pop($fkey_partes);
                            if(preg_match("/_x_insert_/",$mkey)) {
                                $insertForeign[$uptable][$fkey][]="%s='%s'";
                                $insertForeignData[$uptable][$fkey][]=$field;
                                $insertForeignData[$uptable][$fkey][]=$mval;
                            } else {
                                if($fkey!="x") {
                                    // exclude cloned div
                                    $extra_query[] = $this->link->securize_query(array("UPDATE %s SET %s='%s' WHERE %s=%s",$uptable,$field,$mval,$foreignpk,$fkey));
                                }
                            }
                        }
                    }
                } else {
                    if (array_key_exists($field,$_POST)){

                        // Procesa validacion
                        if (!empty($this->validation_type[$field])) {
                            $this->check_validation_type($field,$_POST[$field]);
                        }

                        // Procesa callback de validacion
                        if (!empty($this->validation_callbacks[$field])) {
                            foreach ($this->validation_callbacks[$field] as $c) {
                                $_POST[$field] = call_user_func($c, $this, $_POST[$field],$_POST);
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
        }

        foreach($insertForeign as $uptable=>$insertArray) {
            if(count($insertArray)>0) {
                foreach($insertArray as $discard=>$newdata) {
                    $insertForeign[$uptable][$discard][]="%s='%s'";
                    $insertForeignData[$uptable][$discard][]=$this->foreign[$uptable]['foreignfield'];
                    $insertForeignData[$uptable][$discard][]=$id;
                    $totalarray = array_merge( array("INSERT INTO ".$uptable." SET ".implode(",",$insertForeign[$uptable][$discard])),
                        $insertForeignData[$uptable][$discard]);
                    $extra_query[] = $this->link->securize_query($totalarray);;
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
                //echo "$field = $value<br>";
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

        foreach($extra_query as $qq) {
            $res = $this->link->consulta($qq);
        }

        $res = $this->link->consulta($query,$queryvars);

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
            $this->add_error(trans('Error updating record'));
            $this->unlink_files($files_added);
        }

        $this->del_request($this->pk);
        $this->del_request('button_ok');
        $req=$this->set_request('dbgrid_action','list',true);
        $this->print_grid();
    }

    private function show_form($id) {

        if (!$this->allow_view) {
            return;
        }

        $this->print_javascript('show');

        $joinstring="";
        if(count($this->joins)>0) {
            foreach($this->joins as $idx=>$jointable) {
                $joinstring .= $jointable." ";
            }
        }

        $res = $this->link->consulta("SELECT $this->fields FROM %s %s WHERE %s = '%s' %s",$this->table,$joinstring,$this->table.".".$this->pk,$id, $this->groupby);

        $primer_foreign=0;

        echo "<div class='xcontainer'>\n";
        echo "<div class='row-fluid'>\n";
        echo "<div class='span12'>\n";
        echo "<form class='form-horizontal m-t'><fieldset>\n";
        echo "<legend>".trans('View Record')."</legend>\n";

        if ($this->link->num_rows($res) > 0) {

            $row = $this->link->fetch_assoc($res);
            foreach ($row as $field=>$value) {
                if( !array_key_exists($field, $this->field_no_edit)) {
                    if(isset($this->structure[$field])) {
                        if($this->structure[$field]['foreigntable']=='') {
                            $this->create_body_form($field,$value,"show");
                        }
                    }
                }
            }

            if(count($this->foreign)>0) {

                foreach ($this->foreign as $ftable=>$datis) {

                    $campos = $datis['fields'];
                    if($campos<>"") {
                        echo "</fieldset>\n<fieldset id='foreignFieldset_$ftable' >\n<legend>".$datis['name']."</legend>\n";
                        // Tengo configurado campos en add_join_table, es para editar/agregar en formularios
                        $ras = $this->link->consulta("SELECT %s,%s FROM %s WHERE %s=%s",$datis['pk'],$campos,$ftable, $datis['foreignfield'], $row[$this->pk]);
                        while($row2 = $this->link->fetch_assoc($ras)) {
                            $mipk = array_shift($row2);
                            $sechash = md5($this->salt.$mipk);
                            echo "<div id='foreign_row_{$ftable}_{$mipk}'>";
                            foreach ($row2 as $field=>$value) {
                                $this->create_body_form($ftable.".".$field,$value,"show",$mipk);
                            }
                            echo "</div>";
                        }

                    }

                }
            }
            echo "</fieldset>\n";

            $req = $this->set_request('dbgrid_action','list',false);
            echo "<div class='form-actions'>";
            echo "<input type='submit' class='btn btn-warning' name='button_cancel' onClick='javascript:location=\"".SELF."$req\"; return false;' value='".trans('Cancel')."'></div>";
            //Fin de Row > 0 
        }
        echo "</form></div>";
    }

    // Esta funcion borra UN, VARIOS o TODOS los registros de la tabla
    // Ejecuta los callbacks, remueve archivos, etc.
    private function delete_rows($ids,$customsalt='') {

        $deletingAll=0;
        if(is_array($ids)) {
            // Se pasa uno o varios registros
            // $cuantos_a_borrar = count($ids);
            $ids = implode(',', $ids);
            $querysubselect = "";
            $querydelete = "";

        } else {
            // Tenemos que borrar todos de todos, hago la consulta con condiciones y search
            $deletingAll=1;
        }

        $querysubselect = "SELECT $this->pk FROM ".$this->table." ";
        $querydelete    = "DELETE FROM ".$this->table." ";

        if($this->condition <> "") {
            $querysubselect .= $this->condition;
            $querydelete .= $this->condition;
        }

        if($this->search <> "") {
            $query_parts=array();
            foreach ($this->field_search as $key=>$val) {
                $qstring = preg_replace("/%/","\\%%",addslashes(urldecode($this->search))); 
                $query_parts[]= "$key LIKE '%%".$qstring."%%'";
            }
            if(count($query_parts) > 0) {
                $searchquery=implode(" OR ",$query_parts);
                $querysubselect .= " AND (".$searchquery.") ";
                $querydelete .= " AND (".$searchquery.") ";
            }
        }

        if($customsalt<>'') {
            $mihash = md5($this->salt.$customsalt);
        } else {
            $mihash = md5($this->salt.$ids);
        }
        $callbacks = !empty($this->delete_callbacks);
        $data = array();

        if($mihash <> $this->res_request['dbgrid_pkhash']) {
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

        $joinstring="";
        if(count($this->joins)>0) {
            foreach($this->joins as $idx=>$jointable) {
                $joinstring .= $jointable." ";
            }
        }

        if($deletingAll==0) {
            // Selecciono uno o algunos
            $resdata = $this->link->consulta("SELECT ".$this->fields." FROM %s %s WHERE %s IN (".$ids.") %s",$this->table,$joinstring,$this->table.".".$this->pk,$this->groupby);
        } else {
            // Selecciono todos 
            $resdata = $this->link->consulta($querysubselect);
        }
        $cuantos_a_borrar = $this->link->num_rows();

        while($row = $this->link->fetch_assoc($resdata)) {
            foreach($row as $field=>$val) {
                if ($this->structure[$field]['input']=="img"){
                    if(trim($val)!="") {
                        $files_to_remove[] = $val;
                    }
                }
            }
            if($callbacks) {
                foreach ($this->delete_callbacks as $c) {
                    call_user_func($c, $row);
                }
            }
        }

        if(count($this->foreign)>0) {
            foreach($this->foreign as $jointable=>$datatable) {
                if($deletingAll==0) {
                    $this->link->consulta("DELETE FROM %s WHERE ".$datatable['foreignfield']." IN (".$ids.")",$jointable);
                } else {
                    $this->link->consulta("DELETE FROM %s WHERE ".$datatable['foreignfield']." IN (".$querysubselect.")",$jointable);
                }
            }
        }

        if($deletingAll==0) {
            // Borro uno o algunos
            $res = $this->link->consulta("DELETE FROM %s WHERE %s IN (".$ids.")",$this->table,$this->pk);
        } else {
            // Borro todos
            $res = $this->link->consulta($querydelete);
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
        $req = $this->set_request('dbgrid_action','list',true);

        header("Location: ".SELF."$req");

    }

    private function create_body_form($field,$value,$type,$pkindex='') {

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

        $cadavalor=Array();
        if(isset($this->structure[$field]['explode'])) {
            $cadavalor=explode(",",$value);
        } else {
            $cadavalor[] = $value;
        }

        $cont=1;
        foreach ($cadavalor as $value) {

            if($cont>1) {
                $fieldp = $field.$cont;
                $display_name_p = $display_name." ".$cont;
            } else {
                $fieldp=$field;
                $display_name_p = $display_name;
            }
            if($pkindex!="") {
                $fieldp = $field."_$pkindex";
            }
            $chzclass=" class=\"chz\" ";
            if($pkindex=="x") {
                $pkindex=0;
                $chzclass="";
            }
            if ($this->structure[$field]['input'] != 'hidden' ) {
                $parentstyle = isset($this->structure[$field]['parentstyle'])?$this->structure[$field]['parentstyle']:'';
                printf("\n<div class='form-group %s'>",$parentstyle);
            }
            if ($this->structure[$field]['input'] != 'hidden' && $this->structure[$field]['input'] != 'img'){
                printf("<label class='control-label col-sm-2' for='%s'>%s</label>",$fieldp,$display_name_p);
            }

            $style = isset($this->structure[$field]['style'])?$this->structure[$field]['style']:'';
            switch ($this->structure[$field]['input']){
            case 'textarea':
                printf('<div class="col-sm-9"><textarea %s name="%s" class="form-control input-xlarge" %s>%s</textarea></div>',$style,$fieldp,$atribute,$value);
                break;
            case 'multiselect':
                printf('<div class="col-sm-9"><select data-placeholder="%s" %s name="%s[]" %s size="7" class="chz form-control" multiple>',trans("Select some options"),$style,$fieldp,$atribute);
                foreach ($this->structure[$field]['values'] as $k => $v) {
                    $coco = preg_split("/,/",html_entity_decode($value));
                    $caca = html_entity_decode($k);
                    $vprint = $this->get_edit_filtered($field,$v);
                    printf('<option value="%s" %s>%s</option>',$k,in_array($caca,$coco) ? 'selected' : '',$vprint);
                }
                echo '</select></div>';
                break;

            case 'select':
                printf('<div class="col-sm-9"><select data-placeholder="%s" %s name="%s" class="form-control" %s %s>',trans("Select an option"),$style,$fieldp,$chzclass,$atribute);
                foreach ($this->structure[$field]['values'] as $k => $v) {
                    $vprint = $this->get_edit_filtered($field,$v);
                    printf('<option value="%s" %s>%s</option>',$k,(string)$k === $value ? 'selected' : '',$vprint);
                }
                echo '</select></div>';
                break;
            case 'bitmask':
                printf('<div class="col-sm-9"><select data-placeholder="%s" class="chz form-control" name="%s[]" %s  size="7" multiple>',trans("Select some options"),$fieldp,$atribute);
                foreach ($this->structure[$field]['values'] as $k => $v) {
                    printf("<option value=\"%s\" %s>%s</option>\n",$k,$k & $value ? 'selected' : '',$v);
                }
                echo '</select></div>';
                break;
            case 'datetime':
                if($value=='CURRENT_TIMESTAMP') {
                    $value=date('Y-m-d H:i:s');
                }
                if($value=='0000-00-00 00:00:00') {
                    $value=date('Y-m-d H:i:s');
                }
                $vprint = $this->get_edit_filtered($field,$value);
                printf('<div class="col-sm-4"><div class="input-group"><input type="text" name="%s" class="form-control dateTime" xdata-datepicker="datepicker" value="%s" %s ><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>',$fieldp,$vprint,$atribute);

                break;
            case 'date':
                $vprint = $this->get_edit_filtered($field,$value);

                printf('<div class="col-sm-4"><div class="input-group datePick"><input type="text" name="%s" class="form-control" value="%s" %s ><span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span></div></div>',$fieldp,$vprint,$atribute);
                break;
                /*
                    case 'time':
                        //printf('<input type="text" name="%s" value="%s"  %s> <a href="javascript: void(document.forms[0].elements[\'%s\'].value = currentTime())" onclick="enableApply()" title="Click to set current time">Now</a>',
                        $field,
                        $value,
                        (($field == $this->pk && !$this->modify_pk) || ( array_key_exists($field, $this->field_no_edit) && $this->field_no_edit[$field] == 1 )) ? 'disabled': '',
                        $field);
                        break;
                 */
            case 'password':
                printf('<div class="col-sm-9"><input type="password" class="from-control" name="%s" value="%s" %s ><input type="password" name="%s_confirm" class="form-control" value="%s" %s> <i>(confirmar)</i><label class="checkbox"><input type="checkbox" value="1" name="%s_blank" id="%s_blank">Dejar clave en blanco?</label></div>',
                    $fieldp,$value,$atribute,$fieldp,$value,$atribute,$fieldp,$fieldp,$fieldp);
                break;
            case 'hidden':
                printf('<input type="hidden" name="%s" value="%s" >',
                    $fieldp,$value);
                break;
            case 'img':
                printf('<div class="inputfile">');
                printf('<input type="file" onChange="document.getElementById(\'text-%s\').value=this.value;" name="%s" id="%s" %s>',$fieldp,$fieldp,$fieldp,$atribute);
                printf("<label for='text-%s'>%s</label>",$fieldp,$display_name_p);
                printf('<input class="fakefile" type="text" name="text-%s" id="text-%s" %s></div>',$fieldp,$fieldp,$atribute);
                if ($value != ""){
                    printf('<p><img class="img-responsive" src="%s" alt="%s" title="%s"></p>',$this->user_directory.$value,$value,$value);
                }     
                break;
            default:
                $required='';
                if (isset($this->validation_type[$field])) {
                    if(in_array('required',$this->validation_type[$field])) {
                        $required='required="required"';
                    } 
                }
                $extra_attributes='';
                if (isset($this->extra_attributes[$field])) {
                    $extra_attributes = $this->extra_attributes[$field];
                }
                printf('<div class="col-sm-9"><input class="form-control %s" type="text" name="%s" value="%s" %s %s %s></div>',$style,$fieldp,$value,$atribute,$required,$extra_attributes);
                break;
            }
            if ($this->structure[$field]['input'] != 'hidden' ) {
                echo "</div>\n";
            }
            $cont++;
        }
    }

    private function db_quote($str) {

        if (is_null($str)) {
            return 'null';
        }

        /**
         * handle magic_quotes_gpc
         */
        if (ini_get('magic_quotes_gpc')) {
            $str = stripslashes($str);
        }

        return "'" . $this->link->escape_string($str) . "'";
    }

    private function unlink_files($files){
        if(is_array($files)) {
            foreach($files as $file) {
                unlink($this->user_directory.$file);
            }
        } else {
            unlink($this->user_directory.$files);
        }
    }

    private function upload_img($id,$arrFile,$field=""){
        $file = $arrFile['tmp_name'];
        if ($arrFile['size']>0 && !empty($file)) {
            if (is_uploaded_file($file)) {
                if (copy ($file, $this->user_directory.$id."-".$arrFile['name'])) {
                    $res = $this->link->consulta("SELECT %s FROM %s WHERE %s='%s' LIMIT 1",$field,$this->table,$this->pk,$id);
                    if($res) {
                        $row = $this->link->fetch_assoc($res);
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
                $row = $this->link->fetch_assoc($res);
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
    private static function isEmail($email, $test_mx = false){
        if($email=="") { return true; }
        if(preg_match("/^([_a-z0-9-]+)(\.[_a-z0-9-]+)*@([a-z0-9-]+)(\.[a-z0-9-]+)*(\.[a-z]{2,4})$/i", $email)) {
            if($test_mx) {
                list( , $domain) = preg_split("/@/", $email);
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
    private static function isURL($value) {
        if (preg_match("/^http(s)?:\/\/([\w-]+\.)+[\w-]+(\/[\w- .\/?%&=]*)?$/i", $value)) {
            return true;
        } else {
            return false;
        }
    }

    private static function isAlfaNumeric($value) {
        if (preg_match("/^[A-Za-z0-9 ]+$/", $value)) {
            return true;
        } else {
            return false;
        }
    }

    public static function isAlfa($value, $allow = '') {
        if (preg_match('/^[a-zA-Z' . $allow . ']+$/', $value)) {
            return true;
        } else {
            return false;
        }
    }

    private function search_rows(){
        $this->del_request('button_ok');
        $req=$this->set_request('dbgrid_action','list',true);
        $this->print_grid();
    }


//Fin de clase
}
