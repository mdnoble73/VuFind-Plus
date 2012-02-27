<?php
/**
 * Oracle support code for VTLS Virtua Driver
 *
 * PHP version 5
 *
 * Copyright (C) University of Southern Queensland 2008.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @category VuFind
 * @package  Support_Classes
 * @author   Greg Pendlebury <vufind-tech@lists.sourceforge.net>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/system_classes Wiki
 */

/**
 * Oracle support code for VTLS Virtua Driver
 *
 * @category VuFind
 * @package  Support_Classes
 * @author   Greg Pendlebury <vufind-tech@lists.sourceforge.net>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/system_classes Wiki
 */
class Oracle_Connection
{
    // Database Handle
    private $_dbHandle;

    // Error information
    private $_lastError;
    private $_lastErrorType;
    private $_lastErrorFields;
    private $_lastSql;

    /**
     * Constructor -- connect to database.
     *
     * @param string $username Username for connection
     * @param string $password Password for connection
     * @param string $tns      TNS specification for connection
     *
     * @access public
     */
    public function __construct($username, $password, $tns)
    {
        $this->_clearError();
        $tmp = error_reporting(1);
        if ($this->_dbHandle = @oci_connect($username, $password, $tns)) {
            error_reporting($tmp);
            $this->audit_id = 0;
            $this->detail_id = 0;
        } else {
            error_reporting($tmp);
            $this->_handleError('connect', oci_error());
            return false;
        }
    }

    /**
     * Get access to the Oracle handle.
     *
     * @return resource
     * @access public
     */
    public function getHandle()
    {
        return $this->_dbHandle;
    }

    /**
     * Destructor
     *
     * @return void
     * @access public
     */
    public function __destruct()
    {
        // Close the OCI connection unless we failed to establish it:
        if ($this->_dbHandle !== false) {
            oci_close($this->_dbHandle);
        }
    }

    /**
     * Wrapper around oci_parse.
     *
     * @param string $sql SQL statement to prepare.
     *
     * @return mixed      SQL resource on success, boolean false otherwise.
     * @access public
     */
    public function prepare($sql)
    {
        if ($parsed = @oci_parse($this->_dbHandle, $sql)) {
            return $parsed;
        } else {
            $this->_handleError('parsing', oci_error($this->_dbHandle), $sql);
            return false;
        }
    }

    /**
     * Wrapper around oci_new_descriptor.
     *
     * @return mixed New descriptor on success, boolean false otherwise.
     * @access public
     */
    public function prepRowId()
    {
        if ($new_id = @oci_new_descriptor($this->_dbHandle, OCI_D_ROWID)) {
            return $new_id;
        } else {
            $this->_handleError('new_descriptor', oci_error($this->_dbHandle));
            return false;
        }
    }

    /**
     * Wrapper around oci_bind_by_name.
     *
     * @param resource $parsed       Result returned by prepare() method.
     * @param string   $place_holder The colon-prefixed bind variable placeholder
     * used in the statement.
     * @param string   $data         The PHP variable to be associatd with
     * $place_holder
     * @param string   $data_type    The type of $data (string, integer, float,
     * long, date, row_id, clob, or blob)
     * @param int      $length       Sets the maximum length for the data. If you
     * set it to -1, this function will use the current length of variable to set
     * the maximum length.
     *
     * @return bool
     * @access public
     */
    public function bindParam(
        $parsed, $place_holder, $data, $data_type = 'string', $length = -1
    ) {
        switch ($data_type) {
        case 'string':
            $oracle_data_type = SQLT_CHR;
            break;
        case 'integer':
            $oracle_data_type = SQLT_INT;
            break;
        case 'float':
            $oracle_data_type = SQLT_FLT;
            break;
        case 'long':
            $oracle_data_type = SQLT_LNG;
            break;
        // Date is redundant since default is varchar,
        //  but it's here for clarity.
        case 'date':
            $oracle_data_type = SQLT_CHR;
            break;
        case 'row_id':
            $oracle_data_type = SQLT_RDD;
            break;
        case 'clob':
            $oracle_data_type = SQLT_CLOB;
            break;
        case 'blob':
            $oracle_data_type = SQLT_BLOB;
            break;
        default:
            $oracle_data_type = SQLT_CHR;
            break;
        }

        if (@oci_bind_by_name(
            $parsed, $place_holder, $data, $length, $oracle_data_type
        )) {
            return true;
        } else {
            $this->_handleError('binding', oci_error());
            return false;
        }
    }

    /**
     * Same as bindParam(), but variable is parsed by reference to allow for correct
     * functioning of the 'RETURNING' sql statement. Annoying, but putting it in two
     * separate functions allows the user to pass string literals into bindParam
     * without a fatal error.
     *
     * @param resource $parsed       Result returned by prepare() method.
     * @param string   $place_holder The colon-prefixed bind variable placeholder
     * used in the statement.
     * @param string   &$data        The PHP variable to be associatd with
     * $place_holder
     * @param string   $data_type    The type of $data (string, integer, float,
     * long, date, row_id, clob, or blob)
     * @param int      $length       Sets the maximum length for the data. If you
     * set it to -1, this function will use the current length of variable to set
     * the maximum length.
     *
     * @return bool
     * @access public
     */
    public function returnParam(
        $parsed, $place_holder, &$data, $data_type = 'string', $length = -1
    ) {
        switch ($data_type) {
        case 'string':
            $oracle_data_type = SQLT_CHR;
            break;
        case 'integer':
            $oracle_data_type = SQLT_INT;
            break;
        case 'float':
            $oracle_data_type = SQLT_FLT;
            break;
        case 'long':
            $oracle_data_type = SQLT_LNG;
            break;
        // Date is redundant since default is varchar,
        //  but it's here for clarity.
        case 'date':
            $oracle_data_type = SQLT_CHR;
            break;
        case 'row_id':
            $oracle_data_type = SQLT_RDD;
            break;
        case 'clob':
            $oracle_data_type = SQLT_CLOB;
            break;
        case 'blob':
            $oracle_data_type = SQLT_BLOB;
            break;
        default:
            $oracle_data_type = SQLT_CHR;
            break;
        }

        if (@oci_bind_by_name(
            $parsed, $place_holder, $data, $length, $oracle_data_type
        )) {
            return true;
        } else {
            $this->_handleError('binding', oci_error());
            return false;
        }
    }

    /**
     * Wrapper around oci_execute.
     *
     * @param resource $parsed Result returned by prepare() method.
     *
     * @return bool
     * @access public
     */
    public function exec($parsed)
    {
        // OCI_DEFAULT == DO NOT COMMIT!!!
        if (@oci_execute($parsed, OCI_DEFAULT)) {
            return true;
        } else {
            $this->_handleError('executing', oci_error($parsed));
            return false;
        }
    }

    /**
     * Wrapper around oci_commit.
     *
     * @return bool
     * @access public
     */
    public function commit()
    {
        if (@oci_commit($this->_dbHandle)) {
            return true;
        } else {
            $this->_handleError('commit', oci_error($this->_dbHandle));
            return false;
        }
    }

    /**
     * Wrapper around oci_rollback.
     *
     * @return bool
     * @access public
     */
    public function rollback()
    {
        if (@oci_rollback($this->_dbHandle)) {
            return true;
        } else {
            $this->_handleError('rollback', oci_error($this->_dbHandle));
            return false;
        }
    }

    /**
     * Wrapper around oci_free_statement.
     *
     * @param resource $parsed Result returned by prepare() method.
     *
     * @return bool
     * @access public
     */
    public function free($parsed)
    {
        if (@oci_free_statement($parsed)) {
            return true;
        } else {
            $this->_handleError('free', oci_error($this->_dbHandle));
            return false;
        }
    }

    /**
     * Execute a SQL statement and return the results.
     *
     * @param string $sql    SQL to execute
     * @param array  $fields Bind parameters (optional)
     *
     * @return array|bool    Results on success, false on error.
     * @access public
     */
    public function simpleSelect($sql, $fields = array())
    {
        $stmt = $this->prepare($sql);
        foreach ($fields as $field => $datum) {
            list($column, $type) = explode(":", $field);
            $this->bindParam($stmt, ":".$column, $datum, $type);
        }

        if ($this->exec($stmt)) {
            oci_fetch_all($stmt, $return_array, 0, -1, OCI_FETCHSTATEMENT_BY_ROW);
            $this->free($stmt);
            return $return_array;
        } else {
            $this->_lastErrorFields = $fields;
            $this->free($stmt);
            return false;
        }
    }

    /**
     * Delete row(s) from a table.
     *
     * @param string $table  Table to update.
     * @param array  $fields Fields to use to match rows to delete.
     *
     * @return bool
     * @access public
     */
    public function simpleDelete($table, $fields = array())
    {
        $types   = array();
        $data    = array();
        $clauses = array();

        // Split all the fields up into arrays
        foreach ($fields as $field => $datum) {
            list($column, $type) = explode(":", $field);
            $types[$column] = $type;
            $data[$column]  = $datum;
            $clauses[]      = "$column = :$column";
        }

        // Prepare the SQL for child table - turn the columns in placeholders for
        // the bind
        $sql  = "DELETE FROM $table WHERE ".join(" AND ", $clauses);
        $delete = $this->prepare($sql);

        // Bind Variables
        foreach (array_keys($data) as $column) {
            $this->bindParam($delete, ":".$column, $data[$column], $types[$column]);
        }

        // Execute
        if ($this->exec($delete)) {
            $this->commit();
            $this->free($delete);
            return true;
        } else {
            $this->_lastErrorFields = $fields;
            $this->free($delete);
            return false;
        }
    }

    /**
     * Insert a row into a table.
     *
     * @param string $table  Table to append to.
     * @param array  $fields Data to write to table.
     *
     * @return bool
     * @access public
     */
    public function simpleInsert($table, $fields = array())
    {
        $types   = array();
        $data    = array();
        $columns = array();
        $values  = array();

        // Split all the fields up into arrays
        foreach ($fields as $field => $datum) {
            $tmp = explode(":", $field);
            $column = array_shift($tmp);

            // For binding
            $types[$column] = array_shift($tmp);
            $data[$column]  = $datum;

            // For building the sql
            $columns[]      = $column;
            // Dates are special
            if (count($tmp) > 0 && !is_null($datum)) {
                $values[] = "TO_DATE(:$column, '".join(":", $tmp)."')";
            } else {
                $values[] = ":$column";
            }
        }

        $sql  = "INSERT INTO $table (".join(", ", $columns).") VALUES (".
            join(", ", $values).")";
        $insert = $this->prepare($sql);

        // Bind Variables
        foreach (array_keys($data) as $column) {
            $this->bindParam($insert, ":".$column, $data[$column], $types[$column]);
        }

        // Execute
        if ($this->exec($insert)) {
            $this->commit();
            $this->free($insert);
            return true;
        } else {
            $this->_lastErrorFields = $fields;
            $this->free($insert);
            return false;
        }
    }

    /**
     * Execute a simple SQL statement.
     *
     * @param string $sql    SQL to execute
     * @param array  $fields Bind parameters (optional)
     *
     * @return bool
     * @access public
     */
    public function simpleSql($sql, $fields = array())
    {
        $stmt = $this->prepare($sql);
        foreach ($fields as $field => $datum) {
            list($column, $type) = explode(":", $field);
            $this->bindParam($stmt, ":".$column, $datum, $type);
        }
        if ($this->exec($stmt)) {
            $this->commit();
            $this->free($stmt);
            return true;
        } else {
            $this->_lastErrorFields = $fields;
            $this->free($stmt);
            return false;
        }
    }

    /**
     * Clear out internal error tracking details.
     *
     * @return void
     * @access private
     */
    private function _clearError()
    {
        $this->_lastError       = null;
        $this->_lastErrorType   = null;
        $this->_lastErrorFields = null;
        $this->_lastSql         = null;
    }

    /**
     * Store information about an error.
     *
     * @param string $type  Type of error
     * @param string $error Detailed error message
     * @param string $sql   SQL statement that caused error
     *
     * @return void
     * @access private
     */
    private function _handleError($type, $error, $sql = '')
    {
        // All we are doing at the moment is storing it
        $this->_lastError       = $error;
        $this->_lastErrorType   = $type;
        $this->_lastSql         = $sql;
    }

    /**
     * Error Retrieval -- last error message.
     *
     * @return string
     * @access public
     */
    public function getLastError()
    {
        return $this->_lastError;
    }

    /**
     * Error Retrieval -- last error type.
     *
     * @return string
     * @access public
     */
    public function getLastErrorType()
    {
        return $this->_lastErrorType;
    }

    /**
     * Error Retrieval -- SQL that triggered last error.
     *
     * @return string
     * @access public
     */
    public function getLastSql()
    {
        return $this->_lastSql;
    }

    /**
     * Error Retrieval -- full details formatted as HTML.
     *
     * @return string
     * @access public
     */
    public function getHtmlError()
    {
        if ($this->_lastError == null) {
            return "No error found!";
        }

        // Generic stuff
        $output  = "<b>ORACLE ERROR</b><br/>\n";
        $output .= "Oracle '".$this->_lastErrorType."' Error<br />\n";
        $output .= "=============<br />\n";
        foreach ($this->_lastError as $key => $value) {
            $output .= "($key) => $value<br />\n";
        }

        // Anything special for this error type?
        switch ($this->_lastErrorType) {
        case 'parsing':
            $output .= "=============<br />\n";
            $output .= "Offset into SQL:<br />\n";
            $output .=
                substr($this->_lastError['sqltext'], $this->_lastError['offset']).
                "\n";
            break;
        case 'executing':
            $output .= "=============<br />\n";
            $output .= "Offset into SQL:<br />\n";
            $output .=
                substr($this->_lastError['sqltext'], $this->_lastError['offset']).
                "<br />\n";
            if (count($this->_lastErrorFields) > 0) {
                $output .= "=============<br />\n";
                $output .= "Bind Variables:<br />\n";
                foreach ($this->_lastErrorFields as $k => $l) {
                    if (is_array($l)) {
                        $output .= "$k => (".join(", ", $l).")<br />\n";
                    } else {
                        $output .= "$k => $l<br />\n";
                    }
                }
            }
            break;
        }

        $this->_clearError();
        return $output;
    }
}
?>
