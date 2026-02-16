<?php
class Database {
	public function connect() {
		global $sql_server, $sql_user, $sql_password, $sql_database;
		mysql_connect($sql_server, $sql_user, $sql_password) or die('Cannot connect to MySQL server.');
		mysql_select_db($sql_database) or log_sql(mysql_errno().' : '.mysql_error());
	}

	public function fetch_field() {
		return mysql_fetch_field($this->resourse);
	}

	public function fetch_row() {
		return mysql_fetch_row($this->resourse);
	}

	public function fetch_array() {
		return mysql_fetch_array($this->resourse, MYSQL_ASSOC);
	}

	public function free_result() {
		mysql_free_result($this->resourse);
	}

	public function num_rows() {
		$res = $this->resourse ? intval(mysql_num_rows($this->resourse)) : false;
		return $res;
	}

	public function num_fields() {
		return intval(mysql_num_fields($this->resourse));
	}

	public function insert_id() {
		return mysql_insert_id();
	}

	public function affected_rows() {
		return mysql_affected_rows();
	}

	public function query($query) {
		$this->resourse = mysql_query($query) or log_sql($query.'; '.mysql_errno().' : '.mysql_error());
	}

	public function field($query) {
		$result = $this->row($query);
		return is_array($result)?current($result):false;
	}

	public function row($query) {
		$this->query($query." LIMIT 1");
		if ($this->resourse) {
			$result = $this->fetch_array();
			$this->free_result();
			return $result;
		}
	}

	public function all($query) {
		$this->query($query);
		$result = array();
		if ($this->resourse) {
			while ($array = $this->fetch_array()) {
				$result[] = $array;
	  		}

			$this->free_result();
		}

		return $result;
	}

	public function hash($query, $column = false, $is_multirow = true, $only_first = false) {
		$result = array();
		$is_multicolumn = false;
		if ($p_result = $this->query($query)) {
			if ($column === false) {
				$c = $this->fetch_field($p_result);
				$column = $c->name;
			} elseif (is_array($column)) {
				if (count($column) == 1)
					$column = current($column);
				else
					$is_multicolumn = true;
			}

			while ($row = $this->fetch_array($p_result)) {
				if ($is_multicolumn) {
					$keys = array();
					foreach ($column as $c) {
						$keys[] = $row[$c];
						unset($row[$c]);
					}

					$keys = implode('"]["', $keys);
				} else {
					$key = $row[$column];
					unset($row[$column]);
				}

				if ($only_first)
					$row = array_shift($row);

				if ($is_multicolumn) {
					if ($is_multirow)
						eval('$result["'.$keys.'"][] = $row;');
					else {
						eval('$is = isset($result["'.$keys.'"]);');
						if (!$is)
							eval('$result["'.$keys.'"] = $row;');
					}
				} elseif ($is_multirow)
					$result[$key][] = $row;
				elseif (!isset($result[$key]))
					$result[$key] = $row;
			}

				$this->free_result();
		}

		return $result;
	}

	public function column($query, $column = 0) {
		$this->query($query);
    	$result = array();
	    $fetch_func = is_int($column)
        	? 'fetch_row'
    	    : 'fetch_array';
	    if ($this->resourse) {
	        while ($row = $this->$fetch_func())
            	$result[] = $row[$column];

			$this->free_result();
    	}

	    return $result;
	}

	public function array2insert($tbl, $arr, $is_replace = false) {
		$query = $is_replace ? 'REPLACE' : 'INSERT';
		$arr_keys = array_keys($arr);
	    $delims = array_fill_keys($arr_keys, "'");
		foreach ($arr_keys as $k => $v) {
			if (!preg_match('/^`.*`$/Si', $v, $out)) {
        	    $arr_keys[$k] = "`" . $v . "`";
	        }
    	}

	    foreach ($arr as $k => $v) {
            $arr[$k] = $delims[$k] . addslashes($v) . $delims[$k];
	    }

	    $arr_values = array_values($arr);
	    $query .= ' INTO ' . $tbl . ' (' . implode(', ', $arr_keys) . ') VALUES (' . implode(', ', $arr_values) . ')';
	    $this->query($query);
	    if ($this->resourse) {
			$this->free_result();
	        return $this->insert_id();
	    }

		$this->free_result();
	    return false;
	}

	public function array2update ($tbl, $arr, $where = '') {
	    $fields = array();
	    foreach ($arr as $k => $v) {
	    	$v = addslashes($v);
	        if (!(
	            ($k[0] == '`')
    	        && ($k[strlen($k) - 1] == '`')
        	    )
	        ) {
    	        $k = "`$k`";
        	}

   	        $v = "'" . $v . "'";
	        $fields[] = $k . "=" . $v;
    	}

	    $query = 'UPDATE ' . $tbl . ' SET ' . implode(', ', $fields) . ($where ? ' WHERE ' . $where : '');

    	$this->query($query);
		$this->free_result();
	}
}