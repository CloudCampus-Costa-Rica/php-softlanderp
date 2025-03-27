<?php

namespace SoftlandERP;

class MSSQLDB
{

    private static $instance = null;
    private $host;
    private $username;
    private $password;
    private $dbname;

    private $dbh = null, $table, $columns, $sql, $bindValues, $getSQL,
        $where, $orWhere, $whereCount = 0, $isOrWhere = false, $joins,
        $rowCount = 0, $limit, $orderBy, $lastIDInserted = 0;

    // Initial values for pagination array
    private $pagination = ['previousPage' => null, 'currentPage' => 1, 'nextPage' => null, 'lastPage' => null, 'totalRows' => null];

    /**
     * @param Config $config
     */
    private function __construct($config)
    {
        try {

            $this->host = $config->get('DB_HOST');
            $this->username = $config->get('DB_USERNAME');
            $this->password = $config->get('DB_PASSWORD');
            $this->dbname = $config->get('DB_DATABASE');

            $this->dbh = new \PDO("sqlsrv:Server=$this->host;Database=$this->dbname", $this->username, $this->password, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_OBJ,
                PDO::SQLSRV_ATTR_DIRECT_QUERY => true,
                PDO::SQLSRV_ATTR_DIRECT_QUERY => true
            ]);
            //$this->dbh->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            //$this->dbh->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_OBJ);
        } catch (\Exception $e) {
            die("Error establishing a database connection: " . $e->getMessage() . " - " . $e->getTraceAsString());
        }
    }

    /**
     * @param Config $config
     */
    public static function getInstance($config)
    {
        if (!self::$instance) {
            self::$instance = new MSSQLDB($config);
        }
        return self::$instance;
    }

    /**
     * @return \PDO $dbh
     */
    public function getConnection()
    {
        return $this->dbh;
    }

    public function query($query, $args = [], $quick = false)
    {
        $this->resetQuery();
        $query = trim($query);
        $this->getSQL = $query;
        $this->bindValues = $args;

        if ($quick == true) {
            $stmt = $this->dbh->prepare($query);
            $stmt->execute($this->bindValues);
            $this->rowCount = $stmt->rowCount();
            return $stmt->fetchAll();
        } else {
            if (strpos(strtoupper($query), "SELECT") === 0) {
                $stmt = $this->dbh->prepare($query);
                $stmt->execute($this->bindValues);
                $this->rowCount = $stmt->rowCount();

                $rows = $stmt->fetchAll(\PDO::FETCH_CLASS, 'SoftlandERP\MareiObj');
                $collection = [];
                $collection = new MareiCollection;
                $x = 0;
                foreach ($rows as $key => $row) {
                    $collection->offsetSet($x++, $row);
                }

                return $collection;
            } else {
                $this->getSQL = $query;
                $stmt = $this->dbh->prepare($query);
                $stmt->execute($this->bindValues);
                return $stmt->rowCount();
            }
        }
    }

    public function exec()
    {
        //assimble query
        $this->sql .= $this->where;
        $this->getSQL = $this->sql;
        $stmt = $this->dbh->prepare($this->sql);
        $stmt->execute($this->bindValues);
        return $stmt->rowCount();
    }

    private function resetQuery()
    {
        $this->table = null;
        $this->columns = null;
        $this->sql = null;
        $this->bindValues = null;
        $this->limit = null;
        $this->orderBy = null;
        $this->getSQL = null;
        $this->where = null;
        $this->orWhere = null;
        $this->whereCount = 0;
        $this->isOrWhere = false;
        $this->rowCount = 0;
        $this->lastIDInserted = 0;
    }

    public function delete($table_name, $id = null, $debug = false)
    {
        $this->resetQuery();

        $this->sql = "DELETE FROM {$table_name}";

        if (isset($id)) {
            // if there is an ID
            if (is_numeric($id)) {
                $this->sql .= " WHERE id = ?";
                $this->bindValues[] = $id;
                // if there is an Array
            } elseif (is_array($id)) {
                $arr = $id;
                $count_arr = count($arr);
                $x = 0;

                foreach ($arr as $param) {
                    if ($x == 0) {
                        $this->where .= " WHERE ";
                        $x++;
                    } else {
                        if ($this->isOrWhere) {
                            $this->where .= " Or ";
                        } else {
                            $this->where .= " AND ";
                        }

                        $x++;
                    }
                    $count_param = count($param);

                    if ($count_param == 1) {
                        $this->where .= "id = ?";
                        $this->bindValues[] = $param[0];
                    } elseif ($count_param == 2) {
                        $operators = explode(',', "=,>,<,>=,>=,<>");
                        $operatorFound = false;

                        foreach ($operators as $operator) {
                            if (strpos($param[0], $operator) !== false) {
                                $operatorFound = true;
                                break;
                            }
                        }

                        if ($operatorFound) {
                            $this->where .= $param[0] . " ?";
                        } else {
                            $this->where .= trim($param[0]) . " = ?";
                        }

                        $this->bindValues[] = $param[1];
                    } elseif ($count_param == 3) {
                        $this->where .= trim($param[0]) . " " . $param[1] . " ?";
                        $this->bindValues[] = $param[2];
                    }
                }
                //end foreach
            }
            // end if there is an Array
            $this->sql .= $this->where;

            $this->getSQL = $this->sql;
            if ($debug) {
                json_encode(["sql" => $this->getSQL, "params" => $this->bindValues]);
            }
            $stmt = $this->dbh->prepare($this->sql);
            $stmt->execute($this->bindValues);
            return $stmt->rowCount();
        } // end if there is an ID or Array
        // $this->getSQL = "<b>Attention:</b> This Query will update all rows in the table, luckily it didn't execute yet!, use exec() method to execute the following query :<br>". $this->sql;
        // $this->getSQL = $this->sql;
        return $this;
    }

    public function update($table_name, $fields = [], $id = null, $debug = FALSE)
    {
        $this->resetQuery();
        $set = '';
        $x = 1;

        foreach ($fields as $field => $value) {
            $set .= "$field = ?";
            $this->bindValues[] = $value;
            if ($x < count($fields)) {
                $set .= ", ";
            }
            $x++;
        }

        $this->sql = "UPDATE {$table_name} SET $set";

        if (isset($id)) {
            // if there is an ID
            if (is_numeric($id)) {
                $this->sql .= " WHERE id = ?";
                $this->bindValues[] = $id;
                // if there is an Array
            } elseif (is_array($id)) {
                $arr = $id;
                $count_arr = count($arr);
                $x = 0;

                foreach ($arr as $param) {
                    if ($x == 0) {
                        $this->where .= " WHERE ";
                        $x++;
                    } else {
                        if ($this->isOrWhere) {
                            $this->where .= " Or ";
                        } else {
                            $this->where .= " AND ";
                        }

                        $x++;
                    }
                    $count_param = count($param);

                    if ($count_param == 1) {
                        $this->where .= "id = ?";
                        $this->bindValues[] = $param[0];
                    } elseif ($count_param == 2) {
                        $operators = explode(',', "=,>,<,>=,>=,<>");
                        $operatorFound = false;

                        foreach ($operators as $operator) {
                            if (strpos($param[0], $operator) !== false) {
                                $operatorFound = true;
                                break;
                            }
                        }

                        if ($operatorFound) {
                            $this->where .= $param[0] . " ?";
                        } else {
                            $this->where .= trim($param[0]) . " = ?";
                        }

                        $this->bindValues[] = $param[1];
                    } elseif ($count_param == 3) {
                        $this->where .= trim($param[0]) . " " . $param[1] . " ?";
                        $this->bindValues[] = $param[2];
                    }
                }
                //end foreach
            }
            // end if there is an Array
            $this->sql .= $this->where;

            $this->getSQL = $this->sql;
            $stmt = $this->dbh->prepare($this->sql);

            if ($debug) {
                var_dump(["sql" => $this->getSQL, "args" => $this->bindValues]);
            }

            $stmt->execute($this->bindValues);
            return $stmt->rowCount();
        } // end if there is an ID or Array
        // $this->getSQL = "<b>Attention:</b> This Query will update all rows in the table, luckily it didn't execute yet!, use exec() method to execute the following query :<br>". $this->sql;
        // $this->getSQL = $this->sql;
        return $this;
    }

    public function insert($table_name, $fields = [])
    {
        $this->resetQuery();

        $keys = implode(', ', array_keys($fields));
        $values = '';
        $x = 1;
        foreach ($fields as $field => $value) {
            $values .= '?';
            $this->bindValues[] = $value;
            if ($x < count($fields)) {
                $values .= ', ';
            }
            $x++;
        }

        $this->sql = "INSERT INTO {$table_name} ({$keys}) VALUES ({$values})";
        $this->getSQL = $this->sql;
        $stmt = $this->dbh->prepare($this->sql);
        $stmt->execute($this->bindValues);
        $this->lastIDInserted = $this->dbh->lastInsertId();

        return $this->lastIDInserted;
    } //End insert function

    public function lastId()
    {
        return $this->lastIDInserted;
    }

    public function table($table_name)
    {
        $this->resetQuery();
        $this->table = $table_name;
        return $this;
    }

    public function join($table, $condition, $type = 'INNER')
    {
        $this->joins .= " $type JOIN $table ON $condition";
        return $this;
    }

    public function select($columns)
    {
        $columns = explode(',', $columns);
        foreach ($columns as $key => $column) {
            $columns[$key] = trim($column);
        }

        $columns = implode(', ', $columns);


        $this->columns = "{$columns}";
        return $this;
    }

    public function where()
    {
        if ($this->whereCount == 0) {
            $this->where .= " WHERE ";
            $this->whereCount += 1;
        } else {
            $this->where .= " AND ";
        }

        $this->isOrWhere = false;

        // call_user_method_array('where_orWhere', $this, func_get_args());
        //Call to undefined function call_user_method_array()
        //echo print_r(func_num_args());
        $num_args = func_num_args();
        $args = func_get_args();
        if ($num_args == 1) {
            if (is_numeric($args[0])) {
                $this->where .= "id = ?";
                $this->bindValues[] = $args[0];
            } elseif (is_array($args[0])) {
                $arr = $args[0];
                $count_arr = count($arr);
                $x = 0;

                foreach ($arr as $param) {
                    if ($x == 0) {
                        $x++;
                    } else {
                        if ($this->isOrWhere) {
                            $this->where .= " Or ";
                        } else {
                            $this->where .= " AND ";
                        }

                        $x++;
                    }
                    $count_param = count($param);
                    if ($count_param == 1) {
                        $this->where .= "id = ?";
                        $this->bindValues[] = $param[0];
                    } elseif ($count_param == 2) {
                        $operators = explode(',', "=,>,<,>=,>=,<>");
                        $operatorFound = false;

                        foreach ($operators as $operator) {
                            if (strpos($param[0], $operator) !== false) {
                                $operatorFound = true;
                                break;
                            }
                        }

                        if ($operatorFound) {
                            $this->where .= $param[0] . " ?";
                        } else {
                            $this->where .= trim($param[0]) . " = ?";
                        }

                        $this->bindValues[] = $param[1];
                    } elseif ($count_param == 3) {
                        $this->where .= trim($param[0]) . " " . $param[1] . " ?";
                        $this->bindValues[] = $param[2];
                    }
                }
            }
            // end of is array
        } elseif ($num_args == 2) {
            $operators = explode(',', "=,>,<,>=,>=,<>");
            $operatorFound = false;
            foreach ($operators as $operator) {
                if (strpos($args[0], $operator) !== false) {
                    $operatorFound = true;
                    break;
                }
            }

            if ($operatorFound) {
                $this->where .= $args[0] . " ?";
            } else {
                $this->where .= trim($args[0]) . " = ?";
            }

            $this->bindValues[] = $args[1];
        } elseif ($num_args == 3) {

            $this->where .= trim($args[0]) . " " . $args[1] . " ?";
            $this->bindValues[] = $args[2];
        }
        return $this;
    }

    public function whereNull()
    {
        if ($this->whereCount == 0) {
            $this->where .= " WHERE ";
            $this->whereCount += 1;
        } else {
            $this->where .= " AND ";
        }

        $this->isOrWhere = false;

        // call_user_method_array('where_orWhere', $this, func_get_args());
        //Call to undefined function call_user_method_array()
        //echo print_r(func_num_args());
        $num_args = func_num_args();
        $args = func_get_args();
        if ($num_args == 1) {
            if (is_array($args[0])) {
                $arr = $args[0];
                $count_arr = count($arr);
                $x = 0;

                foreach ($arr as $param) {
                    if ($x == 0) {
                        $x++;
                    } else {
                        if ($this->isOrWhere) {
                            $this->where .= " Or ";
                        } else {
                            $this->where .= " AND ";
                        }

                        $x++;
                    }
                    $count_param = count($param);
                    if ($count_param == 1) {
                        $this->where .= sprintf("%s IS NULL", $param);
                    } elseif ($count_param == 2) {
                        $operators = explode(',', "=,>,<,>=,>=,<>");
                        $operatorFound = false;

                        foreach ($operators as $operator) {
                            if (strpos($param[0], $operator) !== false) {
                                $operatorFound = true;
                                break;
                            }
                        }

                        if ($operatorFound) {
                            $this->where .= $param[0] . " ?";
                            $this->bindValues[] = $param[1];
                        } else {
                            $this->where .= trim($param[0]) . " IS NULL";
                        }
                    } elseif ($count_param == 3) {
                        $this->where .= trim($param[0]) . " " . $param[1] . " ?";
                        $this->bindValues[] = $param[2];
                    }
                }
            } else {
                $this->where .= sprintf("%s IS NULL", $args[0]);
            }
            // end of is array
        } elseif ($num_args == 2) {
            $operators = explode(',', "=,>,<,>=,>=,<>");
            $operatorFound = false;
            foreach ($operators as $operator) {
                if (strpos($args[0], $operator) !== false) {
                    $operatorFound = true;
                    break;
                }
            }

            if ($operatorFound) {
                $this->where .= $args[0] . " ?";
                $this->bindValues[] = $args[1];
            } else {
                $this->where .= trim($args[0]) . " IS NULL";
            }
        } elseif ($num_args == 3) {
            $this->where .= trim($args[0]) . " " . $args[1] . " IS NULL";
        }
        return $this;
    }

    public function orWhere()
    {
        if ($this->whereCount == 0) {
            $this->where .= " WHERE ";
            $this->whereCount += 1;
        } else {
            $this->where .= " OR ";
        }
        $this->isOrWhere = true;
        // call_user_method_array ( 'where_orWhere' , $this ,  func_get_args() );

        $num_args = func_num_args();
        $args = func_get_args();
        if ($num_args == 1) {
            if (is_numeric($args[0])) {
                $this->where .= "id = ?";
                $this->bindValues[] = $args[0];
            } elseif (is_array($args[0])) {
                $arr = $args[0];
                $count_arr = count($arr);
                $x = 0;

                foreach ($arr as $param) {
                    if ($x == 0) {
                        $x++;
                    } else {
                        if ($this->isOrWhere) {
                            $this->where .= " Or ";
                        } else {
                            $this->where .= " AND ";
                        }

                        $x++;
                    }
                    $count_param = count($param);
                    if ($count_param == 1) {
                        $this->where .= "id = ?";
                        $this->bindValues[] = $param[0];
                    } elseif ($count_param == 2) {
                        $operators = explode(',', "=,>,<,>=,>=,<>");
                        $operatorFound = false;

                        foreach ($operators as $operator) {
                            if (strpos($param[0], $operator) !== false) {
                                $operatorFound = true;
                                break;
                            }
                        }

                        if ($operatorFound) {
                            $this->where .= $param[0] . " ?";
                        } else {
                            $this->where .= trim($param[0]) . " = ?";
                        }

                        $this->bindValues[] = $param[1];
                    } elseif ($count_param == 3) {
                        $this->where .= trim($param[0]) . " " . $param[1] . " ?";
                        $this->bindValues[] = $param[2];
                    }
                }
            }
            // end of is array
        } elseif ($num_args == 2) {
            $operators = explode(',', "=,>,<,>=,>=,<>");
            $operatorFound = false;
            foreach ($operators as $operator) {
                if (strpos($args[0], $operator) !== false) {
                    $operatorFound = true;
                    break;
                }
            }

            if ($operatorFound) {
                $this->where .= $args[0] . " ?";
            } else {
                $this->where .= trim($args[0]) . " = ?";
            }

            $this->bindValues[] = $args[1];
        } elseif ($num_args == 3) {

            $this->where .= trim($args[0]) . " " . $args[1] . " ?";
            $this->bindValues[] = $args[2];
        }

        return $this;
    }

    // private function where_orWhere()
    // {

    // }

    public function get($debug = false)
    {
        $this->assimbleQuery();
        $this->getSQL = $this->sql;
        if ($debug) {
            var_dump(["sql" => $this->getSQL, "args" => $this->bindValues]);
        }
        $stmt = $this->dbh->prepare($this->sql);
        $stmt->execute($this->bindValues);
        $this->rowCount = $stmt->rowCount();

        $rows = $stmt->fetchAll(\PDO::FETCH_CLASS, 'SoftlandERP\MareiObj');
        $collection = [];
        $collection = new MareiCollection;
        $x = 0;
        foreach ($rows as $key => $row) {
            $collection->offsetSet($x++, $row);
        }

        return $collection;
    }

    // Quick get
    public function QGet()
    {
        $this->assimbleQuery();
        $this->getSQL = $this->sql;

        $stmt = $this->dbh->prepare($this->sql);
        $stmt->execute($this->bindValues);
        $this->rowCount = $stmt->rowCount();

        return $stmt->fetchAll();
    }


    private function assimbleQuery()
    {
        if ($this->columns !== null) {
            $select = $this->columns;
        } else {
            $select = "*";
        }

        $this->sql = "SELECT $select FROM $this->table";

        if ($this->joins !== null) {
            // Add joins if any
            $this->sql .= $this->joins;
        }

        if ($this->where !== null) {
            $this->sql .= $this->where;
        }

        if ($this->orderBy !== null) {
            $this->sql .= $this->orderBy;
        } else {
            $this->sql .= " ORDER BY 1 ASC";
        }

        if ($this->limit !== null) {
            $this->sql .= $this->limit;
        }
    }

    public function limit($limit, $offset = null)
    {
        if ($offset == null) {
            $this->limit = " FETCH NEXT {$limit} ROWS ONLY";
        } else {
            $this->limit = "OFFSET {$offset} ROWS FETCH NEXT {$limit} ROWS ONLY";
        }

        return $this;
    }

    /**
     * Sort result in a particular order according to a column name
     * @param string $field_name The column name which you want to order the result according to.
     * @param string $order it determins in which order you wanna view your results whether 'ASC' or 'DESC'.
     * @return object             it returns DB object
     */
    public function orderBy($field_name, $order = 'ASC')
    {
        $field_name = trim($field_name);

        $order = trim(strtoupper($order));

        // validate it's not empty and have a proper valuse
        if ($field_name !== null && ($order == 'ASC' || $order == 'DESC')) {
            if ($this->orderBy == null) {
                $this->orderBy = " ORDER BY $field_name $order";
            } else {
                $this->orderBy .= ", $field_name $order";
            }
        }

        return $this;
    }

    public function paginate($page, $limit)
    {
        // Start assimble Query
        $countSQL = "SELECT COUNT(*) FROM $this->table";
        if ($this->where !== null) {
            $countSQL .= $this->where;
        }
        // Start assimble Query

        $stmt = $this->dbh->prepare($countSQL);
        $stmt->execute($this->bindValues);
        $totalRows = $stmt->fetch(\PDO::FETCH_NUM)[0];
        // echo $totalRows;

        $offset = ($page - 1) * $limit;
        // Refresh Pagination Array
        $this->pagination['currentPage'] = $page;
        $this->pagination['lastPage'] = ceil($totalRows / $limit);
        $this->pagination['nextPage'] = $page + 1;
        $this->pagination['previousPage'] = $page - 1;
        $this->pagination['totalRows'] = $totalRows;
        // if last page = current page
        if ($this->pagination['lastPage'] == $page) {
            $this->pagination['nextPage'] = null;
        }
        if ($page == 1) {
            $this->pagination['previousPage'] = null;
        }
        if ($page > $this->pagination['lastPage']) {
            return [];
        }

        $this->assimbleQuery();

        $sql = $this->sql . " OFFSET {$offset} ROWS FETCH NEXT {$limit} ROWS ONLY";
        $this->getSQL = $sql;

        $stmt = $this->dbh->prepare($sql);
        $stmt->execute($this->bindValues);
        $this->rowCount = $stmt->rowCount();


        $rows = $stmt->fetchAll(\PDO::FETCH_CLASS, 'SoftlandERP\MareiObj');
        $collection = [];
        $collection = new MareiCollection;
        $x = 0;
        foreach ($rows as $key => $row) {
            $collection->offsetSet($x++, $row);
        }

        return $collection;
    }

    public function count()
    {
        // Start assimble Query
        $countSQL = "SELECT COUNT(*) FROM $this->table";

        if ($this->where !== null) {
            $countSQL .= $this->where;
        }

        if ($this->limit !== null) {
            $countSQL .= $this->limit;
        }
        // End assimble Query

        $stmt = $this->dbh->prepare($countSQL);
        $stmt->execute($this->bindValues);

        $this->getSQL = $countSQL;

        return $stmt->fetch(\PDO::FETCH_NUM)[0];
    }


    public function QPaginate($page, $limit)
    {
        // Start assimble Query
        $countSQL = "SELECT COUNT(*) FROM $this->table";
        if ($this->where !== null) {
            $countSQL .= $this->where;
        }
        // Start assimble Query

        $stmt = $this->dbh->prepare($countSQL);
        $stmt->execute($this->bindValues);
        $totalRows = $stmt->fetch(\PDO::FETCH_NUM)[0];
        // echo $totalRows;

        $offset = ($page - 1) * $limit;
        // Refresh Pagination Array
        $this->pagination['currentPage'] = $page;
        $this->pagination['lastPage'] = ceil($totalRows / $limit);
        $this->pagination['nextPage'] = $page + 1;
        $this->pagination['previousPage'] = $page - 1;
        $this->pagination['totalRows'] = $totalRows;
        // if last page = current page
        if ($this->pagination['lastPage'] == $page) {
            $this->pagination['nextPage'] = null;
        }
        if ($page == 1) {
            $this->pagination['previousPage'] = null;
        }
        if ($page > $this->pagination['lastPage']) {
            return [];
        }

        $this->assimbleQuery();

        $sql = $this->sql . " OFFSET {$offset} ROWS FETCH NEXT {$limit} ROWS ONLY";
        $this->getSQL = $sql;

        $stmt = $this->dbh->prepare($sql);
        $stmt->execute($this->bindValues);
        $this->rowCount = $stmt->rowCount();

        return $stmt->fetchAll();
    }

    public function PaginationInfo()
    {
        return $this->pagination;
    }

    public function getSQL()
    {
        return $this->getSQL;
    }

    public function getCount()
    {
        return $this->rowCount;
    }

    public function rowCount()
    {
        return $this->rowCount;
    }
}
