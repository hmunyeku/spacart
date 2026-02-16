<?php
class Database {
	public function connect() {
		global $sql_server, $sql_user, $sql_password, $sql_database;
		$this->mysqli = mysqli_connect($sql_server, $sql_user, $sql_password, $sql_database);
		if (!$this->mysqli) {
		    echo "Error: cannot connect with MySQL." . PHP_EOL;
		    echo "Error code: " . mysqli_connect_errno() . PHP_EOL;
		    echo "Error text: " . mysqli_connect_error() . PHP_EOL;
	    	exit;
		}

		if ($this->mysqli->connect_error) {
		    die('Cannot connect to MySQL server.');
		}
	}

	public function query($query) {
		$this->resourse = $this->mysqli->query($query) or log_sql($query.'; '.mysqli_errno($this->mysqli).' : '.mysqli_error($this->mysqli));
	}

	public function setUTF8() {
		mysqli_set_charset($this->mysqli, "utf8");
	}

	public function fetch_field() {
		return mysqli_fetch_field($this->resourse);
	}

	public function fetch_row() {
		return mysqli_fetch_row($this->resourse);
	}

	public function fetch_array() {
		return mysqli_fetch_array($this->resourse, 1);
	}

	public function free_result() {

	}

	public function num_rows() {
		$res = $this->resourse ? intval(mysqli_num_rows($this->resourse)) : false;
		return $res;
	}

	public function num_fields() {
		return intval(mysqli_num_fields($this->resourse));
	}

	public function insert_id() {
		return mysqli_insert_id($this->mysqli);
	}

	public function affected_rows() {
		return mysqli_affected_rows($this->mysqli);
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