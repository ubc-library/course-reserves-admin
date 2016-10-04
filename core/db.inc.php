<?php
    
    /**
     * The DB class adds some convenience methods to the PHP PDO class
     */
    class DB extends PDO {
        
        /**
         * Collects prepared PDO statements
         *
         * @var array
         */
        private $_prep = [];
        
        /**
         * Constructor -- pass args to parent PDO object, then set
         * fetch mode to PDO::ASSOC
         */
        public function __construct ()
        {
            $args = func_get_args ();
            call_user_func_array ([
                                      $this,
                                      'parent::__construct',
                                  ], $args);
            $this->setAttribute (PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        }
        
        /**
         * Prepares and stores SQL query if we haven't seen it before,
         * then executes the query and returns the result as a PDOStatement.
         *
         * Note $bind may be null, a simple type, or an array
         *
         * @param string $sql
         * @param mixed  $bind
         *
         * @return PDOStatement
         */
        public function execute ($sql, $bind = null, $die_on_error = true)
        {
            if (!isset($this->_prep[$sql])) {
                $this->_prep[$sql] = $this->prepare ($sql);
            }
            $stmt = $this->_prep[$sql];
            if (is_null ($bind)) {
                $stmt->execute ();
            } else {
                if (!is_array ($bind)) {
                    $bind = [$bind];
                }
                //var_export($bind);
                $stmt->execute ($bind);
            }
            if (Config::get ('dbdebug')) {
                echo '<div class="debug"><pre>' . $sql . '</pre>';
                if (!is_null ($bind)) {
                    echo '<div class="bind">Bind vars: ';
                    foreach ($bind as $bv => $b) {
                        echo '<span>' . $bv . ' => ' . htmlspecialchars ($b) . '</span>';
                    }
                    echo '</div>';
                }
                $first = true;
                if ($stmt->rowCount () > 0) {
                    while ($row = $stmt->fetch (PDO::FETCH_ASSOC)) {
                        if ($first) {
                            $first = false;
                            echo '<table><thead><tr><th>';
                            echo implode ('</th><th>', array_keys ($row));
                            echo '</th></thead><tbody>';
                        }
                        echo '<tr>';
                        foreach ($row as $val) {
                            echo '<td>' . htmlspecialchars ($val) . '</td>';
                        }
                        echo '</tr>';
                    }
                    echo '</tbody></table>';
                } else {
                    echo '<p><em>No result</em></p>';
                }
                if ($stmt->errorCode () != '00000') {
                    $err = $stmt->errorInfo ();
                    echo '<pre>' . implode ("\n", $err) . '</pre>';
                    if ($die_on_error) {
                        die();
                    }
                }
                echo '</div>';
                if (is_null ($bind)) {
                    $stmt->execute ();
                } else if (is_array ($bind)) {
                    $stmt->execute ($bind);
                } else {
                    $stmt->execute ([$bind]);
                }
            } elseif ($die_on_error && ($stmt->errorCode () != '00000')) {
                $fh  = fopen (dirname (__FILE__) . '/dberr.txt', 'a');
                $err = $stmt->errorInfo ();
                fwrite ($fh, date ('Y-m-d H:i:s') . "\n" . $sql . "\n" . implode ("\n", $err) . "\n\n");
                if ($bind) {
                    fwrite ($fh, "Bind:\n" . var_export ($bind, true));
                }
                fwrite ($fh, var_dump (debug_backtrace ()));
                fclose ($fh);
                exit('Database error, check dberr.txt');
            }
            
            return $stmt;
        }
        
        /**
         * Return one result from a query
         *
         * @param string $sql
         * @param mixed  $bind
         *
         * @return array
         */
        public function queryOneRow ($sql, $bind = null)
        {
            $res = $this->execute ($sql, $bind);
            
            return $res->fetch ();
        }
        
        /**
         * Return all rows from query in an array
         *
         * @param string $sql
         * @param mixed  $bind
         *
         * @return array
         */
        public function queryRows ($sql, $bind = null)
        {
            $res = $this->execute ($sql, $bind);
            
            return $res->fetchAll ();
        }
        
        /**
         * Return only one value
         * e.g. queryOneVal('SELECT `name` FROM `user` WHERE `user_id`=?',1)
         * returns string 'Admin'
         *
         * @param string $sql
         * @param mixed  $bind
         *
         * @return string
         */
        public function queryOneVal ($sql, $bind = null)
        {
            $res = $this->execute ($sql, $bind);
            $res = $res->fetch (PDO::FETCH_NUM);
            
            return $res[0];
        }
        
        /**
         * Take a column of results and implode them on a glue character
         *
         * @param string $sql
         * @param mixed  $bind
         * @param string $glue
         *
         * @return string
         */
        public function queryImplode ($sql, $bind = null, $glue = ',')
        {
            $res = $this->execute ($sql, $bind);
            $out = [];
            while ($line = $res->fetch (PDO::FETCH_NUM)) {
                $out[] = $line[0];
            }
            
            return implode ($glue, $out);
        }
        
        /**
         * Organize resultset into an associative array using the indexCol
         * for key values
         *
         * @param string $sql
         * @param mixed  $bind
         *
         * @return array
         */
        public function queryAssoc ($sql, $indexCol, $bind = null)
        {
            //use first column as index
            $res = $this->execute ($sql, $bind);
            $out = [];
            while ($line = $res->fetch ()) {
                $index = $line[$indexCol];
                unset($line[$indexCol]);
                $out[$index] = $line;
            }
            
            return $out;
        }
        
        public function queryOneColumn ($sql, $bind = null)
        {
            $res = $this->execute ($sql, $bind);
            
            return $res->fetchAll (PDO::FETCH_COLUMN, 0);
        }
        
    }
    
    class DBFinder {
        
        private $dbhost      = false;
        private $environment = '';
        private $dbuser      = '';
        private $dbname      = '';
        private $dbpass      = '';
        private $dbconn      = false;
        private $cachefile   = '';
        private $dbbrand     = 'mysql';
        
        public function __construct ($dbuser = false, $dbpass = false, $dbname = false, $dbbrand = 'mysql')
        {
            $this->dbname      = $dbname ? $dbname : Config::get ('dbname');
            $this->dbuser      = $dbuser ? $dbuser : Config::get ('dbuser');
            $this->dbpass      = $dbpass ? $dbpass : Config::get ('dbpass');
            $this->dbbrand     = $dbbrand ? $dbbrand : Config::get ('dbbrand');
            $this->environment = Config::get ('environment');
            $this->cachefile   = Config::get ('approot') . '/cache/dbfinder.' . $this->dbname . '.' . $this->environment;
            if (file_exists ($this->cachefile)) {
                $this->dbhost = file_get_contents ($this->cachefile);
            }
        }
        
        public function getDB ()
        {
            if ($this->dbconn) {
                return $this->dbconn;
            }
            if ($this->dbhost) {
                try {
                    $this->dbconn = new DB('mysql:host=' . $this->dbhost . ';dbname=' . $this->dbname, $this->dbuser, $this->dbpass);
                } catch (PDOException $p) {
                    $this->dbhost = false;
                }
            }
            if (!$this->dbconn) {
                $this->dbhost = json_decode (
                    file_get_contents (Config::get ('dbfinder_api')
                                       . $this->dbname
                                       . '&environment='
                                       . $this->environment
                    )
                )->host;
                try {
                    $this->dbconn = new DB($this->dbbrand . ':host=' . $this->dbhost . ';dbname=' . $this->dbname, $this->dbuser, $this->dbpass);
                } catch (PDOException $p) {
                    $this->dbhost = false;
                    echo "Failed to connect to database.\n";
                    //var_dump($p);
                    exit();
                }
                file_put_contents ($this->cachefile, $this->dbhost);
            }
            
            return $this->dbconn;
        }
    }
