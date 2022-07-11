<?php
/*************************************************************************
    class.export.php

    Exports stuff (details to follow)

    Jared Hancock <jared@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

class Export {

    // XXX: This may need to be moved to a print-specific class
    static $paper_sizes = array(
        /* @trans */ 'Letter',
        /* @trans */ 'Legal',
        'A4',
        'A3',
    );

    static function dumpQuery($sql, $headers, $how='csv', $options=array()) {
        $exporters = array(
            'csv' => CsvResultsExporter,
            'json' => JsonResultsExporter
        );
        $exp = new $exporters[$how]($sql, $headers, $options);
        return $exp->dump();
    }

    # XXX: Think about facilitated exporting. For instance, we might have a
    #      TicketExporter, which will know how to formulate or lookup a
    #      format query (SQL), and cooperate with the output process to add
    #      extra (recursive) information. In this funciton, the top-level
    #      SQL is exported, but for something like tickets, we will need to
    #      export attached messages, reponses, and notes, as well as
    #      attachments associated with each, ...
    static function dumpTickets($sql, $how='csv',$who) {
        // Add custom fields to the $sql statement
        $cdata = $fields = $select = array();
        if ($who==15){
        $matrice = array(
                'polo' =>       "Polo",
                'm1b' =>        "M1 bloccante",
                'm1b_f' =>      "",
                'm1nb' =>       "M1 non bloccante",
                'm1nb_f' =>     "",
                'm2' =>         "M2",
                'm2_f' =>       "",
                'mx' =>         "Mx (giorno precedente)",
                'mx_f' =>       "",
                'm5a' =>        "M5 stessa tipologia",
                'm5a_f' =>      "",
                'm5b' =>        "M5 a prescindere dal guasto",
                'm5b_f' =>      "",
                's1'  =>        "S1",
                'data_rilevazione' => "Data rilevazione");

                return self::dumpQuery($sql,
            $matrice,
            $how
            );


          }

        if ($who==50){
        $matrice = array(
                'number' =>       "Numero del ticket",
                'ref_num' =>      "Problem",
                'created' =>        "Data creazione",
                'duedate' =>       "Data scadenza",
                'closed' =>      "Data chiusura",
                'isoverdue' => "Scaduto",
                'category_sym' =>      "Problem area",
                'affected_resource_zz_wam_string1' =>      "PT number",
                'customer_location_l_addr1' =>      "Provincia ufficio",
                'rejected' =>      "Riassegnato",
                'ritardo' =>      "Ritardo",
                'ripetizioni' => "Ripetizioni",
                'giorno_riferimento' => "Data riferimento");

                return self::dumpQuery($sql,
            $matrice,
            $how
            );


          }


        if (in_array($who,array(2,23,27,48,76,77,80,81,82,83,84,85,86,87,88,89,90,91,92,93,94,95,96,97,98,99))){

		foreach (TicketForm::getInstance()->getFields() as $f) {
            // Ignore core fields
            if (in_array($f->get('name'), array('subject',
            'priority',
            //'cr',
            'type',
            'customer_first_name',
            'customer_location_l_addr4',
            'ref_num',
            'zz_dt_in_tv_op',
            'zz_dt_fn_tv_op',
            'zz_data_inizio_intervento_man',
            'zz_date6',
            'zz_guasto_riscontrato',
            'zz_intervento_manutentore',
            'zz_ricambi_sostituiti',
            //'zz_esito_op',
            //'zz_desc_op_eff',
            'zz_mgnote',
            'active',
            'zz_dt_callagt',
            'zz_dt_recall',
            'zz_tecreason',
            'zz_dt_restart',
            'customer_zz_top_sp_ap_lun',
            'customer_zz_top_sp_ch_lun',
            'customer_zz_top_sp_ap_mar',
            'customer_zz_top_sp_ch_mar',
            'customer_zz_top_sp_ap_mer',
            'customer_zz_top_sp_ch_mer',
            'customer_zz_top_sp_ap_gio',
            'customer_zz_top_sp_ch_gio',
            'customer_zz_top_sp_ap_ven',
            'customer_zz_top_sp_ch_ven',
            'customer_zz_top_sp_ap_sab',
            'customer_zz_top_sp_ch_sab',
            'customer_zz_top_sp_ap_dom',
            'customer_zz_top_sp_ch_dom',
            'zz_ci_ptinterv',
            'customer_zz_tcl_s_patrono',
            'zz_ci_ptswap',
            'pln_alpha',
            //'group_last_name',
            'comm_id',
            'pc_flag',
            'pc_sn',
            'customer_last_name',
            'group_admin_org_name',
            'open_date',
            'zz_block_id_sym',
            'imac',
            'customer_address5',
            'customer_zz_dominio',
            'customer_vendor_sym',
            'affected_resource_zz_wam_string8',
            'affected_resource_zz_wam_string9',
            'affected_resource_zz_wam_string15',
            'status_sym',
            'zz_esito_op',
            'group_last_name',
            'ref_contatto',
            'analisi_guasto',
            'rejected',
            'old_duration',
            'days_after_reject',
            'rejected_date',
            'tec_contatto',
            'tec_contatto_email',
            'data_partenza',
            'area_descrizione_intervento',
            'desc_statocomponente',
            'cod_intervento',
            'type',
            'costo_ext',
            'costo_int',
            'prezzo')))

                continue;
            // Ignore non-data fields
            elseif (!$f->hasData() || $f->isPresentationOnly())
                continue;

            $name = $f->get('name') ? $f->get('name') : 'field_'.$f->get('id');
            $key = '__field_'.$f->get('id');
            $cdata[$key] = $f->get('label');
            $fields[$key] = $f;
            $select[] = "cdata.`$name` AS __field_".$f->get('id');
        }
		}else{
      //mail("marco.salmi89@gmail.com","EXPORT",$who);
        foreach (TicketForm::getInstance()->getFields() as $f) {
            // Ignore core fields
            if (in_array($f->get('name'), array('subject',
            'priority',
            //'cr',
            'type',
            'customer_first_name',
            'customer_location_l_addr4',
            'ref_num',
            'zz_dt_in_tv_op',
            'zz_dt_fn_tv_op',
            'zz_data_inizio_intervento_man',
            'zz_date6',
            'zz_guasto_riscontrato',
            'zz_intervento_manutentore',
            'zz_ricambi_sostituiti',
            //'zz_esito_op',
            //'zz_desc_op_eff',
            'zz_mgnote',
            'active',
            'zz_dt_callagt',
            'zz_dt_recall',
            //'zz_tecreason',
            'zz_dt_restart',
            'customer_zz_top_sp_ap_lun',
            'customer_zz_top_sp_ch_lun',
            'customer_zz_top_sp_ap_mar',
            'customer_zz_top_sp_ch_mar',
            'customer_zz_top_sp_ap_mer',
            'customer_zz_top_sp_ch_mer',
            'customer_zz_top_sp_ap_gio',
            'customer_zz_top_sp_ch_gio',
            'customer_zz_top_sp_ap_ven',
            'customer_zz_top_sp_ch_ven',
            'customer_zz_top_sp_ap_sab',
            'customer_zz_top_sp_ch_sab',
            'customer_zz_top_sp_ap_dom',
            'customer_zz_top_sp_ch_dom',
            'zz_ci_ptinterv',
            'customer_zz_tcl_s_patrono',
            'zz_ci_ptswap',
            'pln_alpha',
            //'group_last_name',
            'comm_id',
            'pc_flag',
            'pc_sn',
            'customer_last_name',
            'group_admin_org_name',
            'open_date',
            'zz_block_id_sym',
            'imac',
            'customer_address5',
            'customer_zz_dominio',
            'customer_vendor_sym',
            'affected_resource_zz_wam_string8',
            'affected_resource_zz_wam_string9',
            'affected_resource_zz_wam_string15',
            'status_sym',
            'zz_esito_op',
            'group_last_name',
            'ref_contatto',
            'analisi_guasto',
            'rejected',
            'old_duration',
            'days_after_reject',
            'rejected_date',
            'tec_contatto',
            'tec_contatto_email',
            'data_partenza',
            'area_descrizione_intervento',
            'desc_statocomponente',
            'cod_intervento',
            'type'//,
            //'customer_middle_name'
            )))

                continue;
            // Ignore non-data fields
            elseif (!$f->hasData() || $f->isPresentationOnly())
                continue;

            $name = $f->get('name') ? $f->get('name') : 'field_'.$f->get('id');
            $key = '__field_'.$f->get('id');
            $cdata[$key] = $f->get('label');
            $fields[$key] = $f;
            $select[] = "cdata.`$name` AS __field_".$f->get('id');
        }
	}

        $cdata += array('tracking' => 'Tracking');

        if ($select)
            $select[] = "tempi.in_sla,TIME_FORMAT(SEC_TO_TIME(tempi.inter_time),'%Hh %im') as durata_intervento";
            $select[] = "cdata.zz_intervento_manutentore as zz_intervento_manutentore";
            $select[] = "group_concat(concat(th.created,': ',th.title,' -> ',th.body) separator '\n') as tracking "; //punto 15
            //$select[] = "banche.nome as banca";

            $sql = str_replace(' FROM ', ',' . implode(',', $select) . ' FROM ', $sql);

            ###punto 15 del papiro
            $sql = str_replace('(pri.priority_id = cdata.priority)', ' (pri.priority_id = cdata.priority) LEFT JOIN ost_ticket_thread th ON (th.ticket_id = ticket.ticket_id) ', $sql);
            $sql = str_replace('ORDER BY', ' GROUP BY ticket.ticket_id ORDER BY ', $sql);

            #####
            //print_r($sql);
            //die;
            if (in_array($who,array(2,23,27,48,76,80,81))){
            $matrice = array(
                'number' =>         __('Ticket Number'),
                'ref_num' =>       "Problem",
                'ticket_created' => "Data di acquisizione",
                //'subject' =>        __('Subject'),
                //'name' =>           "Cliente",
                //'priority_desc' =>  __('Priority'),
                //'dept_name' =>      __('Department'),
                //'helptopic' =>      __('Help Topic'),
                //'source' =>         __('Source'),
                'status' =>         __('Current Status'),
                'effective_date' => __('Last Updated'),
                'duedate' =>        __('Due Date'),
                'isoverdue' =>      __('Overdue'),
                //'isanswered' =>     __('Answered'),
                //'in_sla' =>       "In sla",
                //'durata_intervento' =>       "Durata intervento",
                //'zz_intervento_manutentore' =>       "Descrizione operazioni effettuate",
                //'assigned' =>       __('Assigned To'),
                'nomeregione' =>    "Regione",
                'banca' =>    "Banca",
            ) + $cdata;
		}else{

		     $matrice = array(
                'number' =>         __('Ticket Number'),
                'ref_num' =>       "Ordine",
                'ticket_created' => "Data di acquisizione",
                //'subject' =>        __('Subject'),
                //'name' =>           "Cliente",
                //'priority_desc' =>  __('Priority'),
                //'dept_name' =>      __('Department'),
                'helptopic' =>      __('Help Topic'),
                //'source' =>         __('Source'),
                'status' =>         __('Current Status'),
                //'effective_date' => __('Last Updated'),
                'duedate' =>        __('Due Date'),
                'isoverdue' =>      __('Overdue'),
                //'isanswered' =>     __('Answered'),
                //'in_sla' =>       "In sla",
                //'durata_intervento' =>       "Durata intervento",
                //'zz_intervento_manutentore' =>       "Descrizione operazioni effettuate",
                'assigned' =>       __('Assigned To'),
                'nomeregione' =>    "Regione",
                'banca' =>    "Banca",
                ) + $cdata;
		}

            //ksort($matrice);

        return self::dumpQuery($sql,
            $matrice,
            $how,
            array('modify' => function(&$record, $keys) use ($fields) {
                foreach ($fields as $k=>$f) {
                    if (($i = array_search($k, $keys)) !== false) {
                        $record[$i] = $f->export($f->to_php($record[$i]));
                    }
                }
                return $record;
            })
            );
    }

    /* static */ function saveTickets($sql, $filename, $how='csv', $who=null) {
        ob_start();
        self::dumpTickets($sql, $how, $who);
        $stuff = ob_get_contents();
        ob_end_clean();
        if ($stuff)
            Http::download($filename, "text/$how", $stuff);

        return false;
    }

    static function saveUsers($sql, $filename, $how='csv') {

        $exclude = array('name', 'email');
        $form = UserForm::getUserForm();
        $fields = $form->getExportableFields($exclude);

        // Field selection callback
        $fname = function ($f) {
            return 'cdata.`'.$f->getSelectName().'` AS __field_'.$f->get('id');
        };

        $sql = substr_replace($sql,
                ','.implode(',', array_map($fname, $fields)).' ',
                strpos($sql, 'FROM '), 0);

        $sql = substr_replace($sql,
                'LEFT JOIN ('.$form->getCrossTabQuery($form->type, 'user_id', $exclude).') cdata
                    ON (cdata.user_id = user.id) ',
                strpos($sql, 'WHERE '), 0);

        $cdata = array_combine(array_keys($fields),
                array_values(array_map(
                        function ($f) { return $f->get('label'); }, $fields)));

        ob_start();
        echo self::dumpQuery($sql,
                array(
                    'name'  =>          __('Name'),
                    'organization' =>   __('Organization'),
                    'email' =>          __('Email'),
                    ) + $cdata,
                $how,
                array('modify' => function(&$record, $keys) use ($fields) {
                    foreach ($fields as $k=>$f) {
                        if ($f && ($i = array_search($k, $keys)) !== false) {
                            $record[$i] = $f->export($f->to_php($record[$i]));
                        }
                    }
                    return $record;
                    })
                );
        $stuff = ob_get_contents();
        ob_end_clean();

        if ($stuff)
            Http::download($filename, "text/$how", $stuff);

        return false;
    }

    static function saveOrganizations($sql, $filename, $how='csv') {

        $exclude = array('name');
        $form = OrganizationForm::getDefaultForm();
        $fields = $form->getExportableFields($exclude);

        // Field selection callback
        $fname = function ($f) {
            return 'cdata.`'.$f->getSelectName().'` AS __field_'.$f->get('id');
        };

        $sql = substr_replace($sql,
                ','.implode(',', array_map($fname, $fields)).' ',
                strpos($sql, 'FROM '), 0);

        $sql = substr_replace($sql,
                'LEFT JOIN ('.$form->getCrossTabQuery($form->type, '_org_id', $exclude).') cdata
                    ON (cdata._org_id = org.id) ',
                strpos($sql, 'WHERE '), 0);

        $cdata = array_combine(array_keys($fields),
                array_values(array_map(
                        function ($f) { return $f->get('label'); }, $fields)));

        $cdata += array('account_manager' => 'Account Manager', 'users' => 'Users');

        ob_start();
        echo self::dumpQuery($sql,
                array(
                    'name'  =>  'Name',
                    ) + $cdata,
                $how,
                array('modify' => function(&$record, $keys) use ($fields) {
                    foreach ($fields as $k=>$f) {
                        if ($f && ($i = array_search($k, $keys)) !== false) {
                            $record[$i] = $f->export($f->to_php($record[$i]));
                        }
                    }
                    return $record;
                    })
                );
        $stuff = ob_get_contents();
        ob_end_clean();

        if ($stuff)
            Http::download($filename, "text/$how", $stuff);

        return false;
    }

}

class ResultSetExporter {
    var $output;

    function ResultSetExporter($sql, $headers, $options=array()) {
        $this->headers = array_values($headers);
        if ($s = strpos(strtoupper($sql), ' LIMIT '))
            $sql = substr($sql, 0, $s);
        # TODO: If $filter, add different LIMIT clause to query
        $this->options = $options;
        $this->output = $options['output'] ?: fopen('php://output', 'w');

        $this->_res = db_query($sql, true, true);
        if ($row = db_fetch_array($this->_res)) {
            $query_fields = array_keys($row);
            $this->headers = array();
            $this->keys = array();
            $this->lookups = array();
            foreach ($headers as $field=>$name) {
                if (array_key_exists($field, $row)) {
                    $this->headers[] = $name;
                    $this->keys[] = $field;
                    # Remember the location of this header in the query results
                    # (column-wise) so we don't have to do hashtable lookups for every
                    # column of every row.
                    $this->lookups[] = array_search($field, $query_fields);
                }
            }
            db_data_reset($this->_res);
        }
    }

    function getHeaders() {
        return $this->headers;
    }

    function next() {
        if (!($row = db_fetch_row($this->_res)))
            return false;

        $record = array();
        foreach ($this->lookups as $idx){
            /*if (is_numeric($row[$idx])){ //aggiungo apici se la stringa contiene solo numeri (per excel)
            $record[] = "'".$row[$idx]."'";
		    }else{*/
			$record[] = $row[$idx];
		    //}
        }
        if (isset($this->options['modify']) && is_callable($this->options['modify']))
            $record = $this->options['modify']($record, $this->keys);

        return $record;
    }

    function nextArray() {
        if (!($row = $this->next()))
            return false;
        return array_combine($this->keys, $row);
    }

    function dump() {
        # Useful for debug output
        while ($row=$this->nextArray()) {
            var_dump($row);
        }
    }
}

class CsvResultsExporter extends ResultSetExporter {

    function dump() {

        if (!$this->output)
             $this->output = fopen('php://output', 'w');
        fputs($this->output, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($this->output, $this->getHeaders(), ';');
        while ($row=$this->next())
            fputcsv($this->output, $row, ';');

        fclose($this->output);

    }
}

class JsonResultsExporter extends ResultSetExporter {
    function dump() {
        require_once(INCLUDE_DIR.'class.json.php');
        $exp = new JsonDataEncoder();
        $rows = array();
        while ($row=$this->nextArray()) {
            $rows[] = $row;
        }
        echo $exp->encode($rows);
    }
}

require_once INCLUDE_DIR . 'class.json.php';
require_once INCLUDE_DIR . 'class.migrater.php';
require_once INCLUDE_DIR . 'class.signal.php';

define('OSTICKET_BACKUP_SIGNATURE', 'osTicket-Backup');
define('OSTICKET_BACKUP_VERSION', 'B');

class DatabaseExporter {

    var $stream;
    var $options;
    var $tables = array(CONFIG_TABLE, SYSLOG_TABLE, FILE_TABLE,
        FILE_CHUNK_TABLE, STAFF_TABLE, DEPT_TABLE, TOPIC_TABLE, GROUP_TABLE,
        GROUP_DEPT_TABLE, TEAM_TABLE, TEAM_MEMBER_TABLE, FAQ_TABLE,
        FAQ_TOPIC_TABLE, FAQ_CATEGORY_TABLE, DRAFT_TABLE,
        CANNED_TABLE, TICKET_TABLE, ATTACHMENT_TABLE,
        TICKET_THREAD_TABLE, TICKET_ATTACHMENT_TABLE, TICKET_PRIORITY_TABLE,
        TICKET_LOCK_TABLE, TICKET_EVENT_TABLE, TICKET_EMAIL_INFO_TABLE,
        EMAIL_TABLE, EMAIL_TEMPLATE_TABLE, EMAIL_TEMPLATE_GRP_TABLE,
        FILTER_TABLE, FILTER_RULE_TABLE, SLA_TABLE, API_KEY_TABLE,
        TIMEZONE_TABLE, SESSION_TABLE, PAGE_TABLE,
        FORM_SEC_TABLE, FORM_FIELD_TABLE, LIST_TABLE, LIST_ITEM_TABLE,
        FORM_ENTRY_TABLE, FORM_ANSWER_TABLE, USER_TABLE, USER_EMAIL_TABLE,
        PLUGIN_TABLE, TICKET_COLLABORATOR_TABLE,
        USER_ACCOUNT_TABLE, ORGANIZATION_TABLE, NOTE_TABLE, KPI_TABLE
    );

    function DatabaseExporter($stream, $options=array()) {
        $this->stream = $stream;
        $this->options = $options;
    }

    function write_block($what) {
        fwrite($this->stream, JsonDataEncoder::encode($what));
        fwrite($this->stream, "\n");
    }

    function dump_header() {
        $header = array(
            array(OSTICKET_BACKUP_SIGNATURE, OSTICKET_BACKUP_VERSION),
            array(
                'version'=>THIS_VERSION,
                'table_prefix'=>TABLE_PREFIX,
                'salt'=>SECRET_SALT,
                'dbtype'=>DBTYPE,
                'streams'=>DatabaseMigrater::getUpgradeStreams(
                    UPGRADE_DIR . 'streams/'),
            ),
        );
        $this->write_block($header);
    }

    function dump($error_stream) {
        // Allow plugins to change the tables exported
        Signal::send('export.tables', $this, $this->tables);
        $this->dump_header();

        foreach ($this->tables as $t) {
            if ($error_stream) $error_stream->write("$t\n");

            // Inspect schema
            $table = array();
            $res = db_query("select column_name from information_schema.columns
                where table_schema=DATABASE() and table_name='$t'");
            while (list($field) = db_fetch_row($res))
                $table[] = $field;

            if (!$table) {
                if ($error_stream) $error_stream->write(
                    sprintf(__("%s: Cannot export table with no fields\n"), $t));
                die();
            }
            $this->write_block(
                array('table', substr($t, strlen(TABLE_PREFIX)), $table));

            db_query("select * from $t");

            // Dump row data
            while ($row = db_fetch_row($res))
                $this->write_block($row);

            $this->write_block(array('end-table'));
        }
    }

    function transfer($destination, $query, $callback=false, $options=array()) {
        $header_out = false;
        $res = db_query($query, true, false);
        $i = 0;
        while ($row = db_fetch_array($res)) {
            if (is_callable($callback))
                $callback($row);
            if (!$header_out) {
                $fields = array_keys($row);
                $this->write_block(
                    array('table', $destination, $fields, $options));
                $header_out = true;

            }
            $this->write_block(array_values($row));
        }
        $this->write_block(array('end-table'));
    }

    function transfer_array($destination, $array, $keys, $options=array()) {
        $this->write_block(
            array('table', $destination, $keys, $options));
        foreach ($array as $row) {
            $this->write_block(array_values($row));
        }
        $this->write_block(array('end-table'));
    }
}
