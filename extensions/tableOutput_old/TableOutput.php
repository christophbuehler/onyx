<?php

/**
 * Created by PhpStorm.
 * User: Christoph
 * Date: 31.08.14
 * Time: 22:18.
 */
class TableOutput
{
    public function __construct($view)
    {
        $this->db = $view->model->db;

        // this id increments for each new table instance
        // and acts as an identifier
        $this->tableId = 1;
    }

    public function show($args)
    {
        ob_start(); // make sure there is no output

        $this->id = isset($args['id']) ? $args['id'] : 'id';
        $this->idAlias = isset($args['idAlias']) ? $args['idAlias'] : $this->id;
        $this->table = $args['table'];
        $this->fieldString = $args['fields'];
        $this->fields = explode(',', str_replace(' ', '', $args['fields']));
        $this->headers = explode(',', trim($args['headers']));
        $this->query = $args['query'];
        $this->links = isset($args['links']) ? $args['links'] : array();
        $this->predefined = isset($args['predefined']) ? $args['predefined'] : array();
        $this->hidden = isset($args['hidden']) ? explode(',', str_replace(' ', '', $args['hidden'])) : array();
        $this->suggestion = isset($args['suggestion']) ? $args['suggestion'] : array();
        $this->hrefs = isset($args['hrefs']) ? $args['hrefs'] : array();
        $this->orientation = isset($args['orientation']) ? $args['orientation'] : 'vertical';
        $this->singlePage = isset($args['singlePage']) ? $args['singlePage'] : false;
        $this->pageRecords = isset($args['pageRecords']) ? $args['pageRecords'] : 20;
        $this->allowFilter = isset($args['filter']) ? $args['filter'] : true;
        $this->orderBy = isset($args['orderBy']) ? $args['orderBy'] : $this->get_order_by();
        $this->filter = ($this->allowFilter) ? $this->get_filter() : array();
        $this->delete = isset($args['delete']) ? $args['delete'] : true;
        $this->edit = isset($args['edit']) ? $args['edit'] : true;
        $this->append = $this->orientation == 'horizontal' ? false : (isset($args['append']) ? $args['append'] : true);
        $this->select = $this->orientation == 'horizontal' ? false : (isset($args['select']) ? $args['select'] : true);
        $this->autocomplete = array();
        $this->independent = isset($args['independent']) ? explode(',', str_replace(' ', '', $args['independent'])) : array();

        ++$this->tableId;

        // each non-link field gets an autocomplete field
        foreach ($this->fields as $field) {
            if (isset($this->links[$field])) {
                continue;
            }
            $this->autocomplete[$field] = 'SELECT '.$field.' FROM '.$this->table.' WHERE '.$field.' = :value';
        }

        $this->save_configuration_to_session();

        return "<div class='table-output-bounding-box ".$this->orientation."'>".$this->show_content($this->compose_select_query(0, 20), true, true).'</div>';
    }

    public function get_order_by()
    {
        if (!isset($_SESSION['tblOutput'.$this->tableId])) {
            return '';
        }

        $this->orderBy = '';

        $sess = json_decode($_SESSION['tblOutput'.$this->tableId], true);
        $metas = $this->get_meta($this->compose_select_query(0, 1, true));

        $orderBy = $sess['orderBy'];

        foreach ($metas as $col) {
            if ($col['name'] == $orderBy) {
                return $orderBy;
            }
        }

        return '';
    }

    public function get_filter()
    {
        if (!isset($_SESSION['tblOutput'.$this->tableId])) {
            return array();
        }

        $sess = json_decode($_SESSION['tblOutput'.$this->tableId], true);

        if (!isset($sess['filter'])) {
            return array();
        }

        return $sess['filter'];
    }

    public function show_content($query, $printPageActions, $printTableHead = true)
    {
        $this->horizontalRows = array();

        for ($i = 0; $i < count($this->headers); ++$i) {
            $this->headers[$i] = trim($this->headers[$i]);
        }

        $table = '';

        if ($printTableHead) {
            $table = "<table class=':classestable-output".($this->select ? ' selectable' : '')."' data-table-output-id='".$this->tableId."'><thead>".$this->get_table_head().'</thead><tbody>';
        }

        $sth = $this->db->prepare($query);

        $sth->execute($this->get_filter_values());

        while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
            $table .= $this->get_table_row($row);
        }

        if ($this->orientation == 'horizontal') {
            foreach ($this->horizontalRows as $row) {
                $table .= $row.'</tr>';
            }
        }

        if ($printTableHead) {
            $table .= '</tbody></table>';

            $addClasses = array();

            if ($this->singlePage) {
                array_push($addClasses, 'single-page');
            }
        }

        $str = '';
        if ($printPageActions && $this->orientation == 'vertical' && ($this->append || !$this->singlePage || $this->delete)) {
            $str .= ($this->append ? "<button class='btn new-btn'>neu</button>" : '').'<div class="page-actions-container">'.($this->delete ? "<button class='btn-blue delete-btn'>L&ouml;schen</button>" : '').($this->edit ? "<button class='btn-blue edit-btn'>Bearbeiten</button>" : '').'</div>';
        } elseif ($printTableHead) {
            array_push($addClasses, 'no-page-actions');
        }

        if ($printPageActions) {
            $str .= "<div class='table-output-content'>";
        }

        if ($printTableHead) {
            $str .= str_replace(':classes', (count($addClasses) > 0 ? implode(' ', $addClasses).' ' : ''), $table);
        } else {
            $str .= $table;
        }

        if ($printPageActions) {
            $str .= '</div>';
        }

        if ($printPageActions && !$this->singlePage) {
            $str .= "<div class='page-nav-container'><div class='prev-btn entypo-left-open'></div><div class='overflow-btns'>".$this->get_page_buttons()."</div><div class='next-btn entypo-right-open'></div></div>";
        }

        return $str;
    }

    public function remote_get_page_buttons()
    {
        $tableId = $_POST['tableOutputId'];
        $this->get_configuration_from_session($tableId);

        return $this->get_page_buttons();
    }

    public function get_page_buttons()
    {
        $str = '';
        $h = 1;
        $sth = $this->db->prepare($this->compose_select_query(0, 999999999));
        $sth->execute($this->get_filter_values());
        $rows = $sth->fetchAll();
        $orderBy = $this->orderBy ? $this->orderBy  : 0;

        for ($i = 0; $i < $sth->rowCount(); $i += $this->pageRecords) {
            $str .= '<div class="nav-btn"><span class="num">'.$h.'</span><span class="desc"><span class="l">'.$this->get_link_value($orderBy, $rows[$i][$orderBy]).'</span> to <span class="l">'.$this->get_link_value($orderBy, $rows[min($i + $this->pageRecords, $sth->rowCount()) - 1][$orderBy]).'</span></span></div>';
            ++$h;
        }

        return $str.'<div class="total-count">'.$sth->rowCount().' Eintr&auml;ge</div>';
    }

    public function remote_get_filter_definitions()
    {
        $this->tableId = $_POST['tableOutputId'];
        $this->get_configuration_from_session($this->tableId);

        $filter = $this->get_filter();

        return count($filter) == 0 ? json_decode('{}') : $filter;
    }

    public function remote_get_page()
    {
        $this->tableId = $_POST['tableOutputId'];
        $start = $_POST['start'];
        $limit = $_POST['limit'];
        $printPageActions = isset($_POST['printPageActions']) && $_POST['printPageActions'] == 'false' ? false : true;
        $printTableHead = isset($_POST['printTableHead']) && $_POST['printTableHead'] == 'false' ? false : true;

        $this->get_configuration_from_session($this->tableId);

        if (isset($_POST['orderBy'])) {
            $this->orderBy = stripslashes($_POST['orderBy']);
            $this->save_configuration_to_session();
        }

        if (isset($_POST['filter'])) {
            $this->filter = $_POST['filter'];
            $this->save_configuration_to_session();
        }

        return $this->show_content($this->compose_select_query($start, $limit), $printPageActions, $printTableHead);
    }

    public function get_relevant_field_string()
    {
        $fieldStringArr = explode(',', $this->fieldString);
        $relevantFieldStringArr = array($this->id.' as '.$this->idAlias);

        foreach ($fieldStringArr as $field) {
            $foundIndependent = false;
            foreach ($this->independent as $independent) {
                if ($independent == trim($field)) {
                    $foundIndependent = true;
                }
            }
            if ($foundIndependent) {
                continue;
            }
            array_push($relevantFieldStringArr, $field);
        }

        return implode(',', $relevantFieldStringArr);
    }

    public function save_configuration_to_session()
    {
        $_SESSION['tblOutput'.$this->tableId] = json_encode(array(
            'id' => $this->id,
            'idAlias' => $this->idAlias,
            'table' => $this->table,
            'fieldString' => $this->fieldString,
            'fields' => $this->fields,
            'headers' => $this->headers,
            'predefined' => $this->predefined,
            'suggestion' => $this->suggestion,
            'hidden' => $this->hidden,
            'query' => $this->query,
            'links' => $this->links,
            'hrefs' => $this->hrefs,
            'orientation' => $this->orientation,
            'delete' => $this->delete,
            'edit' => $this->edit,
            'append' => $this->append,
            'select' => $this->select,
            'orderBy' => $this->orderBy,
            'filter' => $this->filter,
            'pageRecords' => $this->pageRecords,
            'singlePage' => $this->singlePage,
            'allowFilter' => $this->allowFilter,
            'autocomplete' => $this->autocomplete,
            'independent' => $this->independent,
        ));
    }

    public function get_configuration_from_session($id)
    {
        if (!isset($_SESSION['tblOutput'.$id])) {
            return false;
        }

        $sess = json_decode($_SESSION['tblOutput'.$id], true);

        $this->id = $sess['id'];
        $this->idAlias = $sess['idAlias'];
        $this->table = $sess['table'];
        $this->fieldString = $sess['fieldString'];
        $this->fields = $sess['fields'];
        $this->headers = $sess['headers'];
        $this->predefined = $sess['predefined'];
        $this->suggestion = $sess['suggestion'];
        $this->hidden = $sess['hidden'];
        $this->query = $sess['query'];
        $this->links = $sess['links'];
        $this->hrefs = $sess['hrefs'];
        $this->orientation = $sess['orientation'];
        $this->delete = $sess['delete'];
        $this->edit = $sess['edit'];
        $this->append = $sess['append'];
        $this->select = $sess['select'];
        $this->orderBy = $sess['orderBy'];
        $this->filter = $sess['filter'];
        $this->pageRecords = $sess['pageRecords'];
        $this->singlePage = $sess['singlePage'];
        $this->allowFilter = $sess['allowFilter'];
        $this->autocomplete = $sess['autocomplete'];
        $this->independent = $sess['independent'];

        return true;
    }

    public function get_table_head()
    {
        $metas = $this->get_meta($this->compose_select_query(0, 1, true));

        if ($this->orientation == 'horizontal') {
            $i = 0;

            $this->horizontalRows[0] = '<tr>';

            foreach ($this->headers as $header) {
                $hdn = ($this->is_hidden($metas[$i]['name'])) ? ' class="hidden"' : '';
                $str = '<tr'.$hdn.'>';
                $this->horizontalRows[$i + 1] = $str;
                ++$i;
            }

            return;
        }

        $str = "<tr class='table-head'>";
        $independentOffset = 0;

        for ($i = 0; $i < count($this->headers); ++$i) {
            if ($this->is_independent_index($i)) {
                $str .= '<th>'.$this->headers[$i].'</th>';
                ++$independentOffset;
                continue;
            }
			
			var_dump($this->compose_select_query(0, 1, true));
			
            $hdn = ($this->is_hidden($metas[$i - $independentOffset]['name'])) ? ' class="hidden"' : '';
            $str .= '<th'.$hdn."><div data-order-by='".($this->orderBy == $metas[$i - $independentOffset]['name'] ? 'true' : 'false')."' class='sticky' data-type='".$metas[$i - $independentOffset]['native_type']."' data-display-name='".$this->headers[$i - $independentOffset]."' data-name='".$metas[$i - $independentOffset]['name']."'><span>".$this->headers[$i - $independentOffset].'</span>'.($this->allowFilter ? '<div data-active="'.(isset($this->filter[$metas[$i - $independentOffset]['name']]) ? 'true' : 'false').'" class="filter fontawesome-filter"></div>' : '').'</div></th>';
        }

        $str .= '</tr>';

        return $str;
    }

    public function is_independent_index($index)
    {
        return $this->is_independent($this->fields[$index]);
    }

    public function is_independent($fieldName)
    {
        foreach ($this->independent as $independent) {
            if ($independent == $fieldName) {
                return true;
            }
        }

        return false;
    }

    public function get_table_row($row)
    {
        $metas = $this->get_meta($this->compose_select_query(0, 1, true));

        // Offset of one because of id field.
        $i = 1;

        $values = array();

        foreach ($this->fields as $field) {
            if ($this->is_independent($field)) {
                continue;
            }
            $values[':'.$field] = $row[$field];
        }

        if ($this->orientation == 'vertical') {
            $str = "<tr data-row-id='";
            $str .= $row[$this->idAlias];
            $str .= "'>";

            foreach ($this->fields as $field) {
                if ($this->is_independent($field)) {
                    $str .= '<td>';
                    $str .=  $this->get_link_value($field, '', $values);
                    $str .= '</td>';
                    continue;
                }

                $hdn = ($this->is_hidden($metas[$i]['name'])) ? 'class="hidden" ' : '';
                $content = $this->validate_content($metas[$i]['native_type'], $this->get_link_value($field, $row[$field], $values));
                $str .= '<td '.$hdn."data-name='".$field."'".(($content != $row[$field]) ? " data-content='".$content."'" : '')." data-type='".$metas[$i]['native_type']."' data-value='".$row[$field]."'>";
                $content = $this->get_href_value($field, $row[$field], $row, $content);

                $str .= $content;
                $str .= '</td>';
                ++$i;
            }

            $str .= '</tr>';

            return $str;
        }

        // horizontal table
        $i = 0;

        $this->horizontalRows[0] = "<th data-row-id='";
        $this->horizontalRows[0] .= $row[$this->idAlias].',';
        $this->horizontalRows[0] = trim($this->horizontalRows[0], ',');
        $this->horizontalRows[0] .= "'></th>";

        foreach ($this->fields as $field) {
            $hdn = ($this->is_hidden($metas[$i]['name'])) ? 'class="hidden" ' : '';
            $content = $this->validate_content($metas[$i]['native_type'], $this->get_link_value($field, $row[$field]));

            $str = '<td '.$hdn."data-name='".$field."'".(($content != $row[$field]) ? " data-content='".$content."'" : '')." data-type='".$metas[$i]['native_type']."' data-value='".$row[$field]."'>";
            $content = $this->get_href_value($field, $row[$field], $row, $content);

            $str .= $content;
            $str .= '</td>';

            $this->horizontalRows[$i + 1] .= $str;

            ++$i;
        }

        return '';
    }

    public function validate_content($type, $content)
    {
        switch ($type) {
            case 'TINY':
                return $content == '1' ? 'Ja' : 'Nein';
            case 'DATE':
                if (trim($content) == '') {
                    return '';
                }

                return date('d.m.Y', strtotime($content));
            default:
                return $content;
        }
    }

    public function get_link_value($field, $val, $values = array())
    {
        if (!isset($this->links[$field])) {
            return $val;
        }

        $link = $this->links[$field];
        $containedValues = array();

        $values[':value'] = $val;

        foreach ($values as $key => $value) {
            if (strpos($link, $key) !== false) {
                $containedValues[$key] = $value;
            }
        }

        $sth = $this->db->prepare($link);

        $sth->execute($containedValues);

        return $sth->fetch()[0];
    }

    public function get_autocomplete_value($field, $val)
    {
        if (isset($this->links[$field])) {
            return $this->get_link_value($field, $val);
        }

        if (!isset($this->autocomplete[$field])) {
            return $val;
        }

        $sth = $this->db->prepare($this->autocomplete[$field]);
        $sth->execute(array(
            ':value' => $val,
        ));

        return $sth->fetch()[0];
    }

    public function get_href_value($field, $val, $allValues, $linkVal)
    {

        // No href found. Maybe, we can treat it as a web link or email address?
        if (!isset($this->hrefs[$field])) {
            return $this->get_email_and_website_links($val, $linkVal);
        }

        $str = $this->hrefs[$field];

        foreach ($allValues as $key => $value) {
            $str = str_replace(':'.$key, $value, $str);
        }

        return '<a href="'.str_replace(':value', $val, $str).'">'.$linkVal.'</a>';
    }

    public function get_email_and_website_links($val, $linkVal)
    {

        // It's a valid email address.
        if (filter_var($linkVal, FILTER_VALIDATE_EMAIL)) {
            return '<a href="mailto: '.$linkVal.'">'.$linkVal.'</a>';
        }

        // It's a valid URL.
        if (preg_match('/((([A-Za-z]{3,9}:(?:\/\/)?)(?:[-;:&=\+\$,\w]+@)?[A-Za-z0-9.-]+|(?:www.|[-;:&=\+\$,\w]+@)[A-Za-z0-9.-]+)((?:\/[\+~%\/.\w-_]*)?\??(?:[-\+=&;%@.\w_]*)#?(?:[\w]*))?)/', $linkVal)) {
            return '<a href="'.$linkVal.'">'.$linkVal.'</a>';
        }

        return $linkVal;
    }

    public function compose_select_query($start, $limit, $withoutFilter = false)
    {
        if (!$this->allowFilter) {
            $withoutFilter = true;
        }

        return 'SELECT '.$this->get_relevant_field_string().' FROM '.$this->table.' WHERE '.$this->id.' IN ('.$this->query.')'.($withoutFilter ? '' : $this->get_filter_sql()).($this->orderBy != '' ? ' ORDER BY '.$this->orderBy : '').' LIMIT '.($this->singlePage ? 0 : $start).', '.($this->singlePage ? $this->pageRecords : $limit);
    }

    public function get_filter_sql()
    {
        $metas = $this->get_meta($this->compose_select_query(0, 1, true));
        $str = '';

        foreach ($this->filter as $key => $filter) {
            if ($filter['apply'] == 0) {
                continue;
            }

            $is = $filter['is'];
            if (strlen($is) > 0) {
                $str .= ' AND '.$key.' = :FILTER_IS_'.$key;
                continue;
            }

            foreach ($metas as $meta) {
                if ($meta['name'] != $key) {
                    continue;
                }

                switch ($meta['native_type']) {
                    case 'VAR_STRING':
                        if (isset($filter['includes'])) {
                            if (trim($filter['includes']) != '') {
                                $str .= ' AND '.$meta['name'].' LIKE :FILTER_INCLUDE_'.$meta['name'];
                            }
                        }

                        if (isset($filter['excludes'])) {
                            if (trim($filter['excludes']) != '') {
                                $str .= ' AND '.$meta['name'].' NOT LIKE :FILTER_EXCLUDE_'.$meta['name'];
                            }
                        }
                        break;
                    case 'TINY':
                        $str .= ' AND '.$meta['name'].' = :FILTER_VALUE_'.$meta['name'];
                        break;
                    case 'LONG':
                        if (isset($filter['from'])) {
                            $str .= ' AND '.$meta['name'].' >= :FILTER_FROM_'.$meta['name'];
                        }
                        if (isset($filter['to'])) {
                            $str .= ' AND '.$meta['name'].' <= :FILTER_TO_'.$meta['name'];
                        }
                        break;
                    case 'DATE':
                        if (isset($filter['from'])) {
                            $str .= ' AND '.$meta['name'].' >= :FILTER_FROM_'.$meta['name'];
                        }
                        if (isset($filter['to'])) {
                            $str .= ' AND '.$meta['name'].' <= :FILTER_TO_'.$meta['name'];
                        }
                        break;
                }
            }
        }

        return $str;
    }

    public function get_filter_values()
    {
        $metas = $this->get_meta($this->compose_select_query(0, 1, true));
        $values = array();

        foreach ($this->filter as $key => $filter) {
            if ($filter['apply'] == 0) {
                continue;
            }

            $is = $filter['is'];
            if (strlen($is) > 0) {
                $values[':FILTER_IS_'.$key] = $filter['is'];
                continue;
            }

            foreach ($metas as $meta) {
                if ($meta['name'] != $key) {
                    continue;
                }

                switch ($meta['native_type']) {
                    case 'VAR_STRING':
                        if (isset($filter['includes'])) {
                            if (trim($filter['includes']) != '') {
                                $values[':FILTER_INCLUDE_'.$meta['name']] = '%'.$filter['includes'].'%';
                            }
                        }

                        if (isset($filter['excludes'])) {
                            if (trim($filter['excludes']) != '') {
                                $values[':FILTER_EXCLUDE_'.$meta['name']] = '%'.$filter['excludes'].'%';
                            }
                        }
                        break;
                    case 'TINY':
                        if (isset($filter['value'])) {
                            $values[':FILTER_VALUE_'.$meta['name']] = $filter['value'];
                        }
                        break;
                    case 'LONG':
                        if (isset($filter['from'])) {
                            $values[':FILTER_FROM_'.$meta['name']] = $filter['from'];
                        }
                        if (isset($filter['to'])) {
                            $values[':FILTER_TO_'.$meta['name']] = $filter['to'];
                        }
                        break;
                    case 'DATE':
                        if (isset($filter['from'])) {
                            $values[':FILTER_FROM_'.$meta['name']] = $filter['from'];
                        }
                        if (isset($filter['to'])) {
                            $values[':FILTER_TO_'.$meta['name']] = $filter['to'];
                        }
                        break;
                }
            }
        }

        return $values;
    }

    public function compose_delete_query()
    {
        return 'DELETE FROM '.$this->table.' WHERE '.$this->id.' = :id';
    }

    public function compose_insert_query()
    {
        $str = 'INSERT INTO '.$this->table.'(';

        foreach ($this->fields as $field) {
            $str .= $field.',';
        }

        $str = trim($str, ',').') VALUES (';

        foreach ($this->fields as $field) {
            $str .= ':'.$field.',';
        }

        $str = trim($str, ',').')';

        return  $str;
    }

    public function compose_update_query()
    {
        $str = 'UPDATE '.$this->table.' SET ';

        foreach ($this->fields as $field) {
            $str .= $field.'=:'.$field.',';
        }

        $str = trim($str, ',');

        $str .= ' WHERE '.$this->idAlias.' = :id';

        return  $str;
    }

    public function remote_delete()
    {
        $rowId = $_POST['rowId'];

        // the table output session id
        $tableOutputId = $_POST['tableOutputId'];

        $this->get_configuration_from_session($tableOutputId);

        if (!$this->delete) {
            return array(
            'code' => 1,
            'msg' => 'Not allowed to delete an entry.',
        );
        }

        $sth = $this->db->prepare($this->compose_delete_query());

        if ($sth->execute(array(
            ':id' => $rowId,
        )) && $sth->rowCount() == 1) return array(
            'code' => 0,
            'msg' => 'Entry successfully deleted.',
        );
        return array(
          'code' => 1,
          'msg' => 'Entry Could not be deleted.',
          'error' => $sth->errorInfo(),
        );
    }

    public function remote_get_filter()
    {
        $field = $_POST['field'];
        $tableOutputId = $_POST['tableOutputId'];

        $this->get_configuration_from_session($tableOutputId);
        $metas = $this->get_meta($this->compose_select_query(0, 1, true));
        $type = '';

        $link = isset($this->links[$field]);

        if (isset($this->filter[$field])) {
            $values = $this->filter[$field];
        } else {
            $values = array();
        }

        foreach ($metas as $meta) {
            if ($meta['name'] != $field) {
                continue;
            }
            $type = $meta['native_type'];
            break;
        }

        if ($link) {
            return array(
                array(
                    'type' => 'VAR_STRING',
                    'caption' => 'Entspricht',
                    'name' => 'is',
                    'linkId' => '../tableOutput/link?k='.$field.'&tableOutputId='.$tableOutputId,
                    'content' => isset($values['is']) ? $this->get_autocomplete_value($field, $values['is']) : '',
                    'value' => isset($values['is']) ? $values['is'] : 'NULL',
                ), array(
                    'type' => 'VAR_STRING',
                    'caption' => 'Beinhaltet',
                    'name' => 'includes',
                    'value' => isset($values['includes']) ? $values['includes'] : '',
                ), array(
                    'type' => 'VAR_STRING',
                    'caption' => 'Beinhaltet nicht',
                    'name' => 'excludes',
                    'value' => isset($values['excludes']) ? $values['excludes'] : '',
            ), );
        }

        $arr = array();

        array_push($arr, array(
            'type' => 'VAR_STRING',
            'caption' => 'Entspricht',
            'name' => 'is',
            'linkId' => '../tableOutput/link?a='.$field.'&tableOutputId='.$tableOutputId,
            'content' => isset($values['is']) ? $this->get_autocomplete_value($field, $values['is']) : '',
            'value' => isset($values['is']) ? $values['is'] : 'NULL',
        ));

        switch ($type) {
            case 'VAR_STRING':
                array_push($arr, array(
                    'type' => 'VAR_STRING',
                    'caption' => 'Beinhaltet',
                    'name' => 'includes',
                    'value' => isset($values['includes']) ? $values['includes'] : '',
                ), array(
                    'type' => 'VAR_STRING',
                    'caption' => 'Beinhaltet nicht',
                    'name' => 'excludes',
                    'value' => isset($values['excludes']) ? $values['excludes'] : '',
                ));
                break;
            case 'TINY':
                array_push($arr, array(
                    'type' => 'bool',
                    'caption' => 'Wert',
                    'name' => 'value',
                    'value' => isset($values['value']) ? $values['value'] : '',
                ));
                break;
            case 'LONG':
                array_push($arr, array(
                    'type' => 'LONG',
                    'caption' => 'Von',
                    'name' => 'from',
                    'value' => isset($values['from']) ? $values['from'] : '',
                ), array(
                    'type' => 'LONG',
                    'caption' => 'Bis',
                    'name' => 'to',
                    'value' => isset($values['to']) ? $values['to'] : '',
                ));
                break;
            case 'DATE':
                array_push($arr, array(
                    'type' => 'DATE',
                    'caption' => 'Datum von',
                    'name' => 'from',
                    'value' => isset($values['from']) ? $values['from'] : '',
                ), array(
                    'type' => 'DATE',
                    'caption' => 'Datum bis',
                    'name' => 'to',
                    'value' => isset($values['to']) ? $values['to'] : '',
                ));
                break;
            default:
                return $type;
        }

        return $arr;
    }

    public function remote_insert()
    {
        $fieldValues = $_POST['values'];

        // the table output session id
        $tableOutputId = $_POST['tableOutputId'];

        $this->get_configuration_from_session($tableOutputId);

        if (!$this->append) {
            return array(
            'code' => 1,
            'msg' => 'Not allowed to create an entry.',
        );
        }

        $sth = $this->db->prepare($this->compose_insert_query());

        $valArr = array();

        foreach ($this->fields as $field) {

            // value not passed
            if (!isset($fieldValues[$field])) {
                return array(
                    'code' => 1,
                    'msg' => 'A value for the field "'.$field.'" is required.',
                );
            }

            $valArr[':'.$field] = ($fieldValues[$field] == '' ? null : $fieldValues[$field]);
        }

        if ($sth->execute($valArr)) {
            return array(
            'code' => 0,
            'msg' => 'Entry successfully added.',
        );
        }

        return array(
            'code' => 1,
            'msg' => $sth->errorInfo(),
        );
    }

    public function remote_edit()
    {
        $rowId = $_POST['rowId'];

        $fieldValues = $_POST['values'];

        // the table output session id
        $tableOutputId = $_POST['tableOutputId'];

        $this->get_configuration_from_session($tableOutputId);

        if (!$this->edit) {
            return array(
            'code' => 1,
            'msg' => 'Not allowed to edit an entry.',
        );
        }

        $sth = $this->db->prepare($this->compose_update_query());

        $valArr = array(
            ':id' => $rowId,
        );

        foreach ($this->fields as $field) {

            // value not passed
            if (!isset($fieldValues[$field])) {
                return array(
                    'code' => 1,
                    'msg' => 'A value for the field "'.$field.'" is required.',
                );
            }

            $valArr[':'.$field] = $fieldValues[$field];
        }

        if ($sth->execute($valArr)) {
            return array(
            'code' => 0,
            'msg' => 'Entry successfully edited.',
        );
        }

        return array(
            'code' => 1,
            'msg' => $sth->errorInfo(),
        );
    }

    public function get_meta($query)
    {
        $sth = $this->db->prepare($query);
        $sth->execute();

        $metas = array();

        for ($i = 0; $i < $sth->columnCount(); ++$i) {
            array_push($metas, $sth->getColumnMeta($i));
        }

        return $metas;
    }

    /*
    auto complete for links
    */
    public function remote_link()
    {

        // return string
        $str = '';

        // the table output session id
        $tableOutputId = $_GET['tableOutputId'];

        if (!$this->get_configuration_from_session($tableOutputId)) {
            return array(
            'code' => 1,
            'msg' => 'Table was not found.',
        );
        }

        if (isset($_GET['k']) && isset($this->links[$_GET['k']])) {
            $linkQuery = $this->links[$_GET['k']];
        } elseif (isset($_GET['a']) && isset($this->autocomplete[$_GET['a']])) {
            $linkQuery = $this->autocomplete[$_GET['a']];
        } else {
            return array(
            'code' => 1,
            'msg' => 'No matching link was found.',
        );
        }

        // letter
        $l = $_POST['l'];

        $linkField = $this->get_link_field($linkQuery);
        $linkTable = $this->get_link_table($linkQuery);

        $filterQuery = 'SELECT id, '.$linkField.' as text FROM '.$linkTable.' WHERE '.$linkField.' LIKE :letter LIMIT 10';

        $sth = $this->db->prepare($filterQuery);

        $sth->execute(array(
                ':letter' => '%'.$l.'%',
            ));

        while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
            $str .= '<li data-value="'.$row['id'].'">'.$row['text'].'</li>';
        }

        return array(
            'code' => 0,
            'msg' => $str,
            'error' => $sth->errorInfo(),
        );
    }

    public function get_link_field($str)
    {
        return trim(explode('FROM', explode('SELECT', $str)[1])[0]);
    }

    public function get_link_table($str)
    {
        return trim(explode('WHERE', explode('FROM', $str)[1])[0]);
    }

    /*
    get the fields for a new row
    */
    public function remote_get_new_fields($tableId)
    {

        // the table output session id
        $tableOutputId = $_POST['tableOutputId'];

        $this->get_configuration_from_session($tableOutputId);

        $metas = $this->get_meta($this->compose_select_query(0, 1, true));

        $arr = array();

        foreach ($metas as $meta) {
            if (in_array('auto_increment', $meta['flags'])) {
                continue;
            }

            array_push($arr, array(
                'type' => $meta['native_type'],
                'caption' => $this->get_caption($meta['name']),
                'name' => $meta['name'],
            ));

            // required fields
            $arr[count($arr) - 1]['required'] = $this->is_not_null($meta);

            // set suggestions
            foreach ($this->suggestion as $key => $sugg) {
                if ($key != $meta['name']) {
                    continue;
                }
                $arr[count($arr) - 1]['value'] = $sugg;
            }

            // replace link
            foreach ($this->links as $key => $link) {
                if ($key != $meta['name']) {
                    continue;
                }
                $arr[count($arr) - 1]['type'] = 'VAR_STRING';
                $arr[count($arr) - 1]['linkId'] = '../tableOutput/link?k='.$key.'&tableOutputId='.$tableOutputId;
                break;
            }

            // set predefinitions, which override suggestions, as well as links
            foreach ($this->predefined as $key => $def) {
                if ($key != $meta['name']) {
                    continue;
                }
                $arr[count($arr) - 1]['disabled'] = 'true';
                $arr[count($arr) - 1]['value'] = $def;

                // set hidden fields. Only predefined fields can be hidden
                if (!$this->is_hidden($meta['name'])) {
                    continue;
                }
                $arr[count($arr) - 1]['type'] = 'hidden';
            }
        }

        return $arr;
    }

    public function is_not_null($arr)
    {
        foreach ($arr['flags'] as $flag) {
            if ($flag == 'not_null') {
                return true;
            }
        }

        return false;
    }

    public function is_hidden($name)
    {
        for ($i = 0; $i < count($this->hidden); ++$i) {
            if ($this->hidden[$i] != $name) {
                continue;
            }

            return true;
        }

        return false;
    }

    public function get_caption($field)
    {
        for ($i = 0; $i < count($this->fields); ++$i) {
            if ($this->fields[$i] != $field) {
                continue;
            }

            return $this->headers[$i];
        }

        return 'no caption found exception';
    }

    public function execute_query($sql)
    {
        $sth = $this->db->prepare($sql);
        $sth->execute(array());

        return $sth;
    }
}
