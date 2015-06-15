<?php
namespace Soliant\SimpleFM;

use Soliant\SimpleFM\Exception\ReservedWordException;

class StringUtils
{
    /**
     * @todo verify if an SPL function can be used instead
     * Can't use native http_build_query because it drops args with empty values like &-find
     * @param $string
     * @return array
     */
    public static function explodeNameValueString($string)
    {
        $array = explode('&', $string);
        $resultArray = [];
        if (count($array) < 2) {
            $nameValue = explode('=', $array[0], 2);
            if (count($nameValue) < 2) {
                $resultArray = array($string);
            } else {
                $resultArray = array($nameValue[0] => $nameValue[1]);
            }
        } else {
            foreach ($array as $item) {
                $nameValue = explode('=', $item, 2);
                $name = $nameValue[0];
                $value = @$nameValue[1];
                $resultArray[$name] = $value;
            }
        }
        return $resultArray;
    }

    /**
     * Can't use native http_build_query because it drops args with empty values like &-find
     * @param array $commandArray
     * @return string
     */
    public static function repackCommandString(array $commandArray)
    {
        $amp = '';
        $commandString = '';
        if (!empty($commandArray)) {
            foreach ($commandArray as $name => $value) {
                if ($value instanceof \Datetime) {
                    $value = $value->format('m/d/Y H:i:s');
                }
                $commandString .= ($value === null || $value === '') ? $amp . urlencode($name) : $amp . urlencode($name) . '=' . urlencode($value);
                $amp = '&';
            }
        }
        return $commandString;
    }

    /**
     * @param string $fieldName
     * @throws ReservedWordException
     * @return boolean
     */
    public static function fieldNameIsValid($fieldName)
    {
        $reservedNames = array('index', 'recid', 'modid');
        if (in_array($fieldName, $reservedNames)) {
            throw new ReservedWordException(
                'SimpleFM Exception: "' . $fieldName .
                '" is a reserved word and cannot be used as a field name on any FileMaker layout used with SimpleFM.',
                $fieldName
            );
        }
        return true;
    }

    /**
     * @param $libxmlError
     * @param $xml
     * @return string
     */
    public static function displayXmlError($libxmlError, $xml)
    {
        $return = $xml[$libxmlError->line - 1] . "\n";
        $return .= str_repeat('-', $libxmlError->column) . "^\n";

        switch ($libxmlError->level) {
            case LIBXML_ERR_WARNING:
                $return .= "Warning $libxmlError->code: ";
                break;
            case LIBXML_ERR_ERROR:
                $return .= "Error $libxmlError->code: ";
                break;
            case LIBXML_ERR_FATAL:
                $return .= "Fatal Error $libxmlError->code: ";
                break;
        }

        $return .= trim($libxmlError->message) .
            "\n  Line: $libxmlError->line" .
            "\n  Column: $libxmlError->column";

        if ($libxmlError->file) {
            $return .= "\n  File: $libxmlError->file";
        }

        return "$return\n\n--------------------------------------------\n\n";
    }

    /**
     * @param string $httpErrorString
     * @return mixed
     */
    public static function extractErrorFromPhpMessage($httpErrorString)
    {
        $matches = array();
        // most common message to expect:
        // file_get_contents(http://10.0.0.13:80/fmi/xml/fmresultset.xml) [function.file-get-contents]: failed to open stream: HTTP request failed! HTTP/1.1 401 Unauthorized

        // grab the error from the end (if there is one)
        $message = preg_match('/HTTP\/[A-Za-z0-9\s\.]+/', $httpErrorString, $matches);
        if (!empty($matches)) {
            // strip off the header prefix
            $matches = trim(str_replace('HTTP/1.1 ', '', $matches[0]));
            $result = explode(' ', $matches, 2);
            // normal case will yield an http error code in location 0 and a message in location 1
            if ((int)$result[0] != 0) {
                $return['error'] = (int)$result[0];
                $return['errortext'] = (string)$result[1];
                $return['errortype'] = 'HTTP';
            } else {
                $return['error'] = null;
                $return['errortext'] = $matches;
                $return['errortype'] = 'HTTP';
            }
            return $return;
        } else {
            // example: file_get_contents throws an error if hostname does not resolve with dns
            $return['error'] = 7;
            $return['errortext'] = $httpErrorString;
            $return['errortype'] = 'PHP';
            return $return;
        }
    }

    /**
     * @param int $errorNum
     * @return string
     */
    public static function errorToEnglish($errorNum = '-1')
    {
        $error = array(
            -1 => 'Unknown error',
            0 => 'No error',
            1 => 'User canceled action',
            2 => 'Memory error',
            3 => 'Command is unavailable (for example, wrong operating system, wrong mode, etc.)',
            4 => 'Command is unknown',
            5 => 'Command is invalid (for example, a Set Field script step does not have a calculation specified)',
            6 => 'File is read-only',
            7 => 'Running out of memory',
            8 => 'Empty result',
            9 => 'Insufficient privileges',
            10 => 'Requested data is missing',
            11 => 'Name is not valid',
            12 => 'Name already exists',
            13 => 'File or object is in use',
            14 => 'Out of range',
            15 => 'Can\'t divide by zero',
            16 => 'Operation failed, request retry (for example, a user query)',
            17 => 'Attempt to convert foreign character set to UTF-16 failed',
            18 => 'Client must provide account information to proceed',
            19 => 'String contains characters other than A-Z, a-z, 0-9 (ASCII)',
            20 => 'Command/operation cancelled by triggered script',
            26 => 'The file path specified is not a valid file path',
            100 => 'File is missing',
            101 => 'Record is missing',
            102 => 'Field is missing',
            103 => 'Relationship is missing',
            104 => 'Script is missing',
            105 => 'Layout is missing',
            106 => 'Table is missing',
            107 => 'Index is missing',
            108 => 'Value list is missing',
            109 => 'Privilege set is missing',
            110 => 'Related tables are missing',
            111 => 'Field repetition is invalid',
            112 => 'Window is missing',
            113 => 'Function is missing',
            114 => 'File reference is missing',
            115 => 'Specified menu set is not present',
            116 => 'Specified layout object is not present',
            117 => 'Specified data source is not present',
            130 => 'Files are damaged or missing and must be reinstalled',
            131 => 'Language pack files are missing (such as template files)',
            200 => 'Record access is denied',
            201 => 'Field cannot be modified',
            202 => 'Field access is denied',
            203 => 'No records in file to print, or password doesn\'t allow print access',
            204 => 'No access to field(s) in sort order',
            205 => 'User does not have access privileges to create new records; import will overwrite existing data',
            206 => 'User does not have password change privileges, or file is not modifiable',
            207 => 'User does not have sufficient privileges to change database schema, or file is not modifiable',
            208 => 'Password does not contain enough characters',
            209 => 'New password must be different from existing one',
            210 => 'User account is inactiveUser account is inactive',
            211 => 'Password has expired',
            212 => 'Invalid user account and/or password. Please try again',
            213 => 'User account and/or password does not exist',
            214 => 'Too many login attempts',
            215 => 'Administrator privileges cannot be duplicated',
            216 => 'Guest account cannot be duplicated',
            217 => 'User does not have sufficient privileges to modify administrator accountUser does not have sufficient privileges to modify administrator account',
            218 => 'Password and verify password do not match (iPhone)',
            300 => 'File is locked or in use',
            301 => 'Record is in use by another user',
            302 => 'Table is in use by another user',
            303 => 'Database schema is in use by another user',
            304 => 'Layout is in use by another user',
            306 => 'Record modification ID does not match',
            307 => 'Lost connection to the host and the transaction could not relock',
            400 => 'Find criteria are empty',
            401 => 'No records match the request',
            402 => 'Selected field is not a match field for a lookup',
            403 => 'Exceeding maximum record limit for trial version of FileMaker Pro',
            404 => 'Sort order is invalid',
            405 => 'Number of records specified exceeds number of records that can be omitted',
            406 => 'Replace/Reserialize criteria are invalid',
            407 => 'One or both match fields are missing (invalid relationship)',
            408 => 'Specified field has inappropriate data type for this operation',
            409 => 'Import order is invalid',
            410 => 'Export order is invalid',
            412 => 'Wrong version of FileMaker Pro used to recover file',
            413 => 'Specified field has inappropriate field type',
            414 => 'Layout cannot display the result',
            415 => 'One or more required related records are not available',
            416 => 'Primary key required from data source table',
            417 => 'Database is not supported for ODBC operations',
            418 => 'The base directory specified in the CREATE TABLE ... field blob EXTERNAL \'path\' not found',
            500 => 'Date value does not meet validation entry options',
            501 => 'Time value does not meet validation entry options',
            502 => 'Number value does not meet validation entry options',
            503 => 'Value in field is not within the range specified in validation entry options',
            504 => 'Value in field is not unique as required in validation entry options',
            505 => 'Value in field is not an existing value in the database file as required in validation entry options',
            506 => 'Value in field is not listed on the value list specified in validation entry option',
            507 => 'Value in field failed calculation test of validation entry option',
            508 => 'Invalid value entered in Find mode',
            509 => 'Field requires a valid value',
            510 => 'Related value is empty or unavailable',
            511 => 'Value in field exceeds maximum field size',
            512 => 'Record was already modified by another user',
            513 => 'Record must have a value in some field to be created',
            600 => 'Print error has occurred',
            601 => 'Combined header and footer exceed one page',
            602 => 'Body doesn\'t fit on a page for current column setup',
            603 => 'Print connection lost',
            700 => 'File is of the wrong file type for import',
            706 => 'EPSF file has no preview image',
            707 => 'Graphic translator cannot be found',
            708 => 'Can\'t import the file or need color monitor support to import file',
            709 => 'QuickTime movie import failed',
            710 => 'Unable to update QuickTime reference because the database file is read-only',
            711 => 'Import translator cannot be found',
            714 => 'Password privileges do not allow the operation',
            715 => 'Specified Excel worksheet or named range is missing',
            716 => 'A SQL query using DELETE, INSERT, or UPDATE is not allowed for ODBC import',
            717 => 'There is not enough XML/XSL information to proceed with the import or export',
            718 => 'Error in parsing XML file (from Xerces)',
            719 => 'Error in transforming XML using XSL (from Xalan)',
            720 => 'Error when exporting; intended format does not support repeating fields',
            721 => 'Unknown error occurred in the parser or the transformer',
            722 => 'Cannot import data into a file that has no fields',
            723 => 'You do not have permission to add records to or modify records in the target table',
            724 => 'You do not have permission to add records to the target table',
            725 => 'You do not have permission to modify records in the target table',
            726 => 'There are more records in the import file than in the target table. Not all records were imported',
            727 => 'There are more records in the target table than in the import file. Not all records were updated',
            729 => 'Errors occurred during import. Records could not be imported',
            730 => 'Unsupported Excel version. (Convert file to Excel 7.0 (Excel 95), Excel 97, 2000, or XP format and try again)',
            731 => 'The file you are importing from contains no data',
            732 => 'This file cannot be inserted because it contains other files',
            733 => 'A table cannot be imported into itself',
            734 => 'This file type cannot be displayed as a picture',
            735 => 'This file type cannot be displayed as a picture. It will be inserted and displayed as a file',
            736 => 'Too much data to export to this format. It will be truncated',
            737 => 'Bento table is reported as missed when trying to import it',
            800 => 'Unable to create file on disk',
            801 => 'Unable to create temporary file on System disk',
            802 => 'Unable to open file. This error can be cause by one or more of the following: Invalid database name; File is closed in FileMaker Server; Invalid permission.',
            803 => 'File is single user or host cannot be found',
            804 => 'File cannot be opened as read-only in its current state',
            805 => 'File is damaged; use Recover command',
            806 => 'File cannot be opened with this version of FileMaker Pro',
            807 => 'File is not a FileMaker Pro file or is severely damaged',
            808 => 'Cannot open file because access privileges are damaged',
            809 => 'Disk/volume is full',
            810 => 'Disk/volume is locked',
            811 => 'Temporary file cannot be opened as FileMaker Pro file',
            813 => 'Record Synchronization error on network',
            814 => 'File(s) cannot be opened because maximum number is open',
            815 => 'Couldn\'t open lookup file',
            816 => 'Unable to convert file',
            817 => 'Unable to open file because it does not belong to this solution',
            819 => 'Cannot save a local copy of a remote file',
            820 => 'File is in the process of being closed',
            821 => 'Host forced a disconnect',
            822 => 'FMI files not found; reinstall missing files',
            823 => 'Cannot set file to single-user, guests are connected',
            824 => 'File is damaged or not a FileMaker file',
            825 => 'File is not authorized to reference the protected file',
            850 => 'This path is not valid (for the platform it represents)',
            851 => 'The external file can not be deleted from disk. Do you want to delete the reference to the file anyway?',
            852 => 'Can not write file to the external storage.',
            900 => 'General spelling engine error',
            901 => 'Main spelling dictionary not installed',
            902 => 'Could not launch the Help system',
            903 => 'Command cannot be used in a shared file',
            905 => 'No active field selected; command can only be used if there is an active field',
            906 => 'Current file must be shared in order to use this command',
            920 => 'Can\'t initialize the spelling engine',
            921 => 'User dictionary cannot be loaded for editing',
            922 => 'User dictionary cannot be found',
            923 => 'User dictionary is read-only',
            951 => 'An unexpected error occurred (CWP)',
            954 => 'Unsupported XML grammar (CWP)',
            955 => 'No database name (CWP)',
            956 => 'Maximum number of database sessions exceeded (CWP)',
            957 => 'Conflicting commands (CWP)',
            958 => 'Parameter missing (CWP)',
            959 => 'Custom Web Publishing technology disabled (CWP)',
            960 => 'Parameter is invalid (CWP)',
            1200 => 'Generic calculation error',
            1201 => 'Too few parameters in the function',
            1202 => 'Too many parameters in the function',
            1203 => 'Unexpected end of calculation',
            1204 => 'Number, text constant, field name or "(" expected',
            1205 => 'Comment is not terminated with "*/"',
            1206 => 'Text constant must end with a quotation mark',
            1207 => 'Unbalanced parenthesis',
            1208 => 'Operator missing, function not found or "(" not expected',
            1209 => 'Name (such as field name or layout name) is missing',
            1210 => 'Plug-in function has already been registered',
            1211 => 'List usage is not allowed in this function',
            1212 => 'An operator (for example, +, -, *) is expected here',
            1213 => 'This variable has already been defined in the Let function',
            1214 => 'AVERAGE, COUNT, EXTEND, GETREPETITION, MAX, MIN, NPV, STDEV, SUM and GETSUMMARY: expression found where a field alone is needed',
            1215 => 'This parameter is an invalid Get function parameter',
            1216 => 'Only Summary fields allowed as first argument in GETSUMMARY',
            1217 => 'Break field is invalid',
            1218 => 'Cannot evaluate the number',
            1219 => 'A field cannot be used in its own formula',
            1220 => 'Field type must be normal or calculated',
            1221 => 'Data type must be number, date, time, or timestamp',
            1222 => 'Calculation cannot be stored',
            1223 => 'The function is not implemented',
            1224 => 'The function is not defined',
            1225 => 'The function is not supported in this context',
            1300 => 'The specified name can\'t be used',
            1400 => 'ODBC driver initialization failed; make sure the ODBC drivers are properly installed',
            1401 => 'Failed to allocate environment (ODBC)',
            1402 => 'Failed to free environment (ODBC)',
            1403 => 'Failed to disconnect (ODBC)',
            1404 => 'Failed to allocate connection (ODBC)',
            1405 => 'Failed to free connection (ODBC)',
            1406 => 'Failed check for SQL API (ODBC)',
            1407 => 'Failed to allocate statement (ODBC)',
            1408 => 'Extended error (ODBC)',
            1409 => 'Error (ODBC)',
            1413 => 'Failed communication link (ODBC)',
            1414 => 'ODBC/SQL Statement Too Long',
            1450 => 'Action requires PHP privilege extension (CWP)',
            1451 => 'Action requires that current file be remote',
            1501 => 'The authentication Failed.',
            1502 => 'The connection was refused by the SMTP server.',
            1503 => 'There was an error with SSL.',
            1504 => 'The server required the connection to be encrypted.',
            1505 => 'The specified authentication is not supported by the SMTP server.',
            1506 => 'Email(s) could not be sent successfully.',
            1507 => 'Unable to login into the SMTP Server.',
            1550 => 'The file isn\'t a plugin, or can\'t load for some reason',
            1551 => 'Can\'t delete existing plugin, can\'t write to the folder, can\'t put on disk for some reason',
            1626 => 'The protocol is not supported',
            1627 => 'The authentication Failed.',
            1628 => 'There was an error with SSL.',
            1629 => 'The connection timed out',
            1630 => 'The url format is incorrect',
            1631 => 'The connection failed',
            2021 => 'plug-ins configuration disallowed',
            2046 => 'This command or action cannot be performed because that functionality is no longer supported',
            2047 => 'Bento 2 (or later) is not presented on the system',
            2048 => 'The selected work book is not excel 2007/2008 format',
            2056 => 'This script step cannot be performed because this window is in a modal state.',
            3000 => 'Action never occurred because script was triggered',
            3001 => 'Set when a step returns but is not really finished (probably due to having to switch threads and keep engine thread running)',
            3002 => 'The external file can not be deleted from disk. Do you want to delete the reference to the file anyway?',
            3003 => 'Can not write file to the external storage.',
            3004 => 'Directory Cant Edit',
            3005 => 'Directory Cant Delete',
            3219 => 'Convert Global To Remote Warning',
            3220 => 'Directory Not Accessible Warning',
            3316 => 'Wrning before clearing out existing find requests',
            3317 => 'Wrning before attempting to restore files from hibernation',
            3956 => 'The total size of all base directory paths cannot exceed ^1 bytes.',
            3957 => 'At least one filter must remain.',
            4103 => 'File path is invalid or cannot be resolved during file transfer',
            4104 => 'File i/o issue during file transfer',
            4106 => 'The target base directory \1770 is not valid.',
            4107 => 'The target base directory \1770 could not be created.',
            4603 => 'Spell Export Complete',
            7100 => 'Data Deferred',
            8404 => 'An installed OnTimer script could not be found or could not be run with current access privileges',
            8213 => 'Too many temporary objects created, can\'t create any more.',
            8498 => 'Stale Import Order To Be Updated',
            8499 => 'Import Match May Be Invalid',
            20413 => 'Too Many Files',
            20605 => 'No network connection is available',
        );

        if (array_key_exists($errorNum, $error)) {
            return $error[$errorNum];
        } else {
            return 'Undefined';
        }
    }
}