<?php
/* Проверка на обязательность включения */
if (!defined("LEGAL")) {
	header("HTTP/1.1 404 Not Found");
	exit();
}
/**
 * @param  $text string
 * @return bool, string
 */
function bwfilter( $text ){
    $bad_words = Array(
        'AES_DECRYPT',      //	Decrypt using AES
        'AES_ENCRYPT',      //  Encrypt using AES
        'BENCHMARK',	    //  Repeatedly execute an expression
        'BIN',	            //  Return a string representation of the argument
        'BINARY',	        //  Cast a string to a binary string
        'CHAR',	            //  Return the character for each integer passed
        'COMPRESS',	        //  Return result as a binary string
        'CONNECTION_ID',	//  Return the connection ID (thread ID) for the connection
        'CRC32',	        //  Compute a cyclic redundancy check value
        'CURRENT_USER',	    //  The authenticated user name and host name
        'DATABASE',	        //  Return the default (current) database name
        'DECODE',	        //  Decodes a string encrypted using ENCODE()
        'DES_DECRYPT',	    //  Decrypt a string
        'DES_ENCRYPT',	    //  Encrypt a string
        'ENCODE',	        //  Encode a string
        'ENCRYPT',	        //  Encrypt a string
        'EXPORT_SET',	    // 	Return a string such that for every bit set in the value bits, you get an on string and for every unset bit, you get an off string
        'ExtractValue',	    //  Extracts a value from an XML string using XPath notation
        'FOUND_ROWS',	    //  For a SELECT with a LIMIT clause, the number of rows that would be returned were there no LIMIT clause
        'GET_LOCK',	        //  Get a named lock
    ///**/'HEX',	        //  Return a hexadecimal representation of a decimal or string value
        'IS_FREE_LOCK',	    //  Checks whether the named lock is free
        'IS_USED_LOCK',	    //  Checks whether the named lock is in use. Return connection identifier if true.
        'LAST_INSERT_ID',   //  Value of the AUTOINCREMENT column for the last INSERT
        'LOAD_FILE',	    //  Load the named file
        'MASTER_POS_WAIT',	//  Block until the slave has read and applied all updates up to the specified position
        'MD5',	            //  Calculate MD5 checksum
        'OLD_PASSWORD',	    //  Return the value of the pre-4.1 implementation of PASSWORD
        'PASSWORD',	        //  Calculate and return a password string
        'PROCEDURE ANALYSE',//  Analyze the results of a query
        'QUOTE',	        //  Escape the argument for use in an SQL statement
        'REGEXP',	        //  Pattern matching using regular expressions
        'RELEASE_LOCK',	    //  Releases the named lock
        'RLIKE',	        //  Synonym for REGEXP
        'ROW_COUNT',	    //  The number of rows updated
        'SCHEMA',	        // 	A synonym for DATABASE()
        'SESSION_USER',	    // 	Synonym for USER()
        'SLEEP',	        //  Sleep for a number of seconds
        'SYSTEM_USER',	    //  Synonym for USER()
        'UNCOMPRESS',	    //  Uncompress a string compressed
        'UNCOMPRESSED_LENGTH',//Return the length of a string before compression
        'UNHEX',	        //  Convert each pair of hexadecimal digits to a character
        'UpdateXML',	    //  Return replaced XML fragment
        'USER',	            //  The user name and host name provided by the client
        'UUID_SHORT',	    //  Return an integer-valued universal identifier
        'UUID',	            //  Return a Universal Unique Identifier (UUID)
        'VALUES',	        //  Defines the values to be used during an INSERT
        'VERSION',	        //  Returns a string that indicates the MySQL server version
        'INFORMATION_SCHEMA',//  table data
        'INSERT',
        'UPDATE',
        'DELETE',
        'FILE',
        'CREATE',
        'ALTER',
        'INDEX',
        'DROP',
        'SHOW VIEW',
        'EXECUTE',
        'EVENT',
        'TRIGGER',
        'GRANT',
        'SUPER',
        'PROCESS',
        'RELOAD',
        'SHUTDOWN',
        'SHOW DATABASES',
        'LOCK TABLES',
        'REFERENCES',
        'REPLICATION',
        //'EXPLAIN',
        'sqlcc'
    );
    //$text = strtolower($text);
    foreach($bad_words As $key => $val){
        if (preg_match('/(\W|^)'.$val.'(\W|$)/i', $text)){
            return $val;
        }
    }
    return false;
}

?>