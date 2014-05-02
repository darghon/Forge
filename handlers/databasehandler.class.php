<?php
namespace Forge;
class DatabaseHandler {
    protected $query = null;
    protected $prefix = null;
    protected $eof = null;
    protected $rs = array();
    protected $numRows = null;
    protected $recordposition = null;
    protected $usecounter = 0;
    protected $recordcounter = 0;
    protected $hasRecords = null;
    protected $sqlcollection = null;
	protected $query_queue = array();
    protected $query_time = 0;
    public function __construct($prefix = null) {
    //Construction needs a connection, which is stored locally
        $this->prefix = $prefix;
        $this->usecounter = 0;
        $this->sqlcollection = "";
    }

    public function getPrefix() {
        return $this->prefix;
    }

    public function setPrefix($prefix = null) {
        $this->prefix = $prefix;
    }

    public function getUseCounter() {
    //Reporting tool to check number of sql statements executed
        return $this->usecounter;
    }

    public function getRecordCounter() {
    //Reporting tool to check number of records processed
        return $this->recordcounter;
    }

    public function getSqlCollection() {
        return $this->sqlcollection;
    }

    /**
     * @param String|Query $sql
     * @return DatabaseHandler $this
     */
    public function setQuery($sql) {
    //Setting the query or sql statement that needs to be executed
        $this->query = $sql;
		return $this;
    }
	
	/**
	 * Public function that adds a statement to a statement queue, this can later be batch executed.
	 * This function is primarily used to buffer indexes until after all tables have been created
	 * @param String|Query $sql
	 */
	public function queueQuery($sql){
		$this->query_queue[] = $sql;
	}
	
	public function processQueue($return = null){
		foreach($this->query_queue as $key => &$value){
			$this->setQuery($value);
			$this->execute();
			unset($this->query_queue[$key]);
			if($return !== null){ echo $return; flush(); }
		}
	}

    public function getQuery() {
    //Retrieving the query
        return $this->query;
    }

    public function getTables() {
        $this->query = 'SHOW TABLES';
        $this->execute();
        $return = array();
        if($this->hasRecords()) {
            foreach($this->rs as $record) {
                $return[] = str_replace($this->prefix,"",implode(",",$record));
            }
        }
        return $return;
    }
	
	public function escape($value){
		return mysqli_real_escape_string($this->_getConnection(),$value);
	}

    public function getLastInsertID() {
    //Getting the autonumber ID of the last insert statement
    //is used to set a objectID when new objects are persisted to the database
        return mysqli_insert_id($this->_getConnection());
    }
    
    public function getAffectedRows(){
    	return mysqli_affected_rows($this->_getConnection());
    }

    public function prepareStatement($sql) {
    //Analysing a prepared statement
        $lastpos = 0;
        $i = 0;
        while($lastpos > -1) {
            $lastpos = strpos($sql,"?",$lastpos + 1);
            if($lastpos > -1) $i ++;
        }
        $this->prepvars = $i;
        $this->prepquery = $sql;
    }

    public function addValue($value) {
    //Replace the first ? in the prepared statement with a string
        $this->prepquery = substr($this->prepquery, 0, strpos($this->prepquery,"?"))."'".$value."'".substr($this->prepquery, strpos($this->prepquery,"?")+1);
        $this->prepvars --;
    }

    public function getPreparedStatement() {
    //Retrieve the prepared statement
        return $this->prepquery;
    }

    /**
     * @return bool|\mysqli_result|null
     */
    public function execute() {
    //Executing sql
    //Clear all previous data
        $this->rs = null;
        $this->rs = array();
        $this->numRows = 0;
        $this->recordposition = 0;
        $this->hasRecords = false;
        $this->eof = false;
        $result = null;
        //Check if query is set, if so execute query
        if($this->query != null) {
            $this->usecounter ++;
            $this->sqlcollection[] = (string)$this->query;
            Debug::startTimer('query');
            $result = mysqli_query( $this->_getConnection(), (string)$this->query) OR $this->showError();
            Debug::stopTimer('query');
        }
        //Check if preparedStatement is set, and fully completed
        //If so, execute statement
        elseif($this->prepquery != null && $this->prepvars == 0) {
            $this->usecounter ++;
            $this->sqlcollection[] = (string)$this->prepquery;
            Debug::startTimer('query');
            $result = mysqli_query($this->_getConnection(),(string)$this->prepquery) OR $this->showError();
            Debug::stopTimer('query');
        }
		
        $this->query_time += Debug::showTimer('query');

        //Clear queries
        $this->query = null;
        $this->prepquery = null;
        $this->prepvars = 0;
        
        if($result !== null){
	        //If the result returns a false or true (on an update, delete and insert statement)
	        //the result is returned directly to the caller
	        //checking with 3x = means that the variable will be type checked on false or true
	        //so a 0 or a 1 will not be seen as false or true
	        if($result === false || $result === true) {
	            $this->eof = true;
	            return $result;
	        }
	        else {
	        //If a result set is returned, start processing the data
	            $this->processdata($result);
	            mysqli_free_result($result);
	        }
        }
        return true;
    }

    private function processdata($rs) {
    //dubblecheck the result
        if($rs !== false && $rs !== true) {
        //Set the found number of records
            $this->numRows = mysqli_num_rows($rs);
            //Set reporting for records processed
            $this->recordcounter += $this->numRows;
            if($this->numRows > 0) {
            //hasRecords == true if records were returned
                $this->hasRecords = true;
                //Add every record to the recordset collection
                for($i = 0;$i < $this->numRows; $i++) {
                    $this->rs[$i] = mysqli_fetch_assoc($rs);
                }

            }
        }
    }

    public function getNumRows() {
    //retrieve numbers of records of the resultset
        return $this->numRows;
    }

    public function hasRecords() {
    //retrieve is resultset has records or not
        return $this->hasRecords;
    }

    public function getQueryTime() {
        return $this->query_time;
    }

    public function clearResult() {
    //force clear the result set
        $this->rs = null;
        $this->rs = array();
        $this->numRows = 0;
        $this->recordposition = 0;
        $this->hasRecords = false;
        $this->eof = false;
    }

    public function & getRecord() {
    //return the record at the current recordposition and increase it
        $return = false;
        //If not end of file
        if($this->eof == false) {
            $return = &$this->rs[$this->recordposition];
            $this->moveNext();
            if($this->recordposition == $this->numRows) {
            //If position is past the last record, end of file flag is triggered
                $this->eof = true;
            }
        }
        return $return;
    }

    public function moveNext() {
    //Increase position by one
        $this->recordposition ++;
    }
    public function movePrevious() {
    //Decrease position by one
        $this->recordposition --;
    }
    public function moveLast() {
    //Set position to last object in collection
        $this->recordposition = $this->numRows-1;

    }
    public function moveFirst() {
    //Set position to first object in collection
        $this->recordposition = 0;
    }
	
	public function hasIndex($table, $index){
		$this->query = sprintf('SHOW INDEX FROM %s WHERE Key_name = "%s";',$table,$index);
		$this->execute();
		return $this->hasRecords;
	}
	
	private function showError(){
		//Check application mode to display the error
		switch(Config::getMode()){
			case Config::CLI:
				$string = 'Statement Failed: %s'.PHP_EOL.'Error: %s';
				break;
			case Config::WEB:
				$string = '<h2>Mysql returned error while executing statement:</h2>
						<p>The following statement raised an error: %s</p>
						<p>Error: %s</p>';
				break;
		}
		throw new \Exception(printf($string, $this->query, mysqli_error($this->_getConnection())));
	}

    public function __destroy() {
        $this->query = null;
        $this->prepquery = null;
        $this->prepvars = null;
        $this->rs = null;
        $this->numRows = null;
        $this->recordposition = null;
        $this->usecounter = null;
        $this->sqlcollection = null;
        $this->hasRecords = null;
    }

    /**
     * @return mixed
     */
    protected function _getConnection()
    {
        return Forge::Connection()->getConnection();
    }
}
?>