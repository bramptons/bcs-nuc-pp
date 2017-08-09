<?php

/**
 * @author Guido Gybels
 * @copyright 2015
 * @project BCS CRM Solution
 * @description This unit provides server side datatable functionality
 */

require_once("initialise.inc");

//Initialise request and response
$request = $_GET;

/*if(defined('__DEBUGMODE') && __DEBUGMODE)
{
    file_put_contents(IncTrailingPathDelimiter(sys_get_temp_dir())."Nucleus.datatable.php.request.txt", print_r($request, TRUE));
}*/
$response = array(
    "sEcho" => intval($request['sEcho']),
    "iTotalRecords" => 0,
    "iTotalDisplayRecords" => 0,
    "aaData" => array(),
);
Authenticate();
if($AUTHENTICATION['Authenticated'] || !empty($request['allowguest']))
{
    //Prepare
    $colcount = intval($request['iColumns']);
    $fieldnames = array();
    for($i = 0; $i < $colcount; $i++)
    {
        $fieldnames[$i] = (isset($request['mDataProp_'.$i]) ? $request['mDataProp_'.$i] : $i);
    }
    $QUERIES = null;
    //$WHERE is used in the first query to find pertinent records (and ignores SEARCHES), while $WHERE and $SEARCHES are to be combined
    //in the actual data retrieval second SQL statement
    $WHERE = array();
    $HAVING = array();
    $SEARCHES = array();
    $SEARCHTERM = '';
    $BUTTONGROUP = array();
    $ALLOWED = null;
    //Do not start search unless there are at least 2 characters to search for 
    if(!empty($request['sSearch']) && (strlen($request['sSearch']) > 1))
    {
        $SEARCHTERM = mysqli_real_escape_string($SYSTEM_SETTINGS["Database"], PREG_REPLACE("/[^0-9a-zA-Z\pL\s\+\(\)\.\-\#\_\/@']/iu", '', $request['sSearch']));
    }
    //Default ORDERS use the fieldnames given, or a default `COLUMN_` alias
    $ORDERS = array();
    if(!empty($request['iSortingCols']))
    {
        $sortColCount = intval($request['iSortingCols']);
        for ($i = 0; $i < $sortColCount; $i++)
        {
            $whichcol = intval($request['iSortCol_'.$i]);
            if(!empty($request['bSortable_'.$whichcol]))
            {
                $direction = strtoupper(PickValue($request, 'sSortDir_'.$i, 'asc', null, array('asc', 'desc')));
                $ORDERS[(is_string($fieldnames[$whichcol]) ? $fieldnames[$whichcol] : 'COLUMN_'.$whichcol)] = $direction;
            }
        }
    }
    $LIMITCLAUSE = (isset($request['iDisplayStart']) && isset($request['iDisplayLength']) && (intval($request['iDisplayLength']) > 0)
                    ? "LIMIT ".intval($request['iDisplayStart']).", ".intval($request['iDisplayLength']) : "");
    if(!empty($request['inc']))
    {
        //load a file from the include directory to perform the query
        require(IdentifierStr($request['inc']));
    }
    //The included file will set $QUERIES to two SQL statements, one for the total number of records, the second to the actual data query
    if(!empty($QUERIES))
    {
        //First query gives us iTotalRecords, second query the data; add 3rd query for iTotalDisplayRecords via SELECT FOUND_ROWS()
        $QUERIES = trim($QUERIES);
        $QUERIES .= (substr($QUERIES, -1) <> ';' ? ";\n" : "\n")."SELECT FOUND_ROWS();\n";
//        file_put_contents("d:\\temp\\queries.txt", print_r($QUERIES, TRUE));
        //Now execute all three the queries
        if (mysqli_multi_query($SYSTEM_SETTINGS["Database"], $QUERIES))
        {
            //First query = total number of records
            $qry = mysqli_use_result($SYSTEM_SETTINGS["Database"]);
            $data = mysqli_fetch_row($qry);
            $response['iTotalRecords'] = $data[0];
            mysqli_free_result($qry);
            //Second query = the actual data
            mysqli_next_result($SYSTEM_SETTINGS["Database"]) or Halt(mysqli_error($SYSTEM_SETTINGS["Database"]), __FILE__, __LINE__);
            $qry = mysqli_use_result($SYSTEM_SETTINGS["Database"]);
            //We can either hand over the creation of the results to a function specified in fnresults, or create a table directly from the query
            if(!empty($request['fnrow']))
            {
                while($data = mysqli_fetch_array($qry))
                {
                    //Prepare the BUTTONGROUP first: parse all the url/script/modal properties of the button and replace any &xxx% entries with the relevant
                    //field data. Buttons are disabled if the field data is empty
                    $buttongroup = $BUTTONGROUP;
                    UpdateItemsParamsFromFields($data, $buttongroup);
                    //Now call the row getter function
                    $row = call_user_func($request['fnrow'], $data, $request, $buttongroup, $ALLOWED);
                    if(!empty($request['rowid']) && isset($data[$request['rowid']]))
                    {
                        $row['DT_RowId'] = $data[$request['rowid']];
                    }
                    $response['aaData'][] = $row;
                }
            }
            else
            {
                while($data = mysqli_fetch_array($qry))
                {
                    $row = array();
                    for($i = 0; $i < $colcount; $i++)
                    {
                        $row[$fieldnames[$i]] = (isset($data[$fieldnames[$i]]) ? $data[$fieldnames[$i]] : '');
                    }
                    if(!empty($request['rowid']) && isset($data[$request['rowid']]))
                    {
                        $row['DT_RowId'] = $data[$request['rowid']];
                    }
                    $response['aaData'][] = $row;
                }
            }
            mysqli_free_result($qry);
            //Finally, the last query with total number of records to show (this is the outcome of the SELECT FOUND_ROWS() we have added above)
            mysqli_next_result($SYSTEM_SETTINGS["Database"]) or Halt(mysqli_error($SYSTEM_SETTINGS["Database"]), __FILE__, __LINE__);
            $qry = mysqli_use_result($SYSTEM_SETTINGS["Database"]);
            $data = mysqli_fetch_row($qry);
            $response['iTotalDisplayRecords'] = $data[0];
            mysqli_free_result($qry);
        }
        else
        {
            Halt(mysqli_error($SYSTEM_SETTINGS["Database"]), __FILE__, __LINE__);
        }
    }
}
//Send the response
if(defined('__DEBUGMODE') && __DEBUGMODE)
{
//    file_put_contents(IncTrailingPathDelimiter(sys_get_temp_dir())."Nucleus.datatable.php.response.txt", print_r($response, TRUE));
}
echo json_encode($response);

//This function can be used to replace the generic sort statements (COLUMN_1 etc) by specific statements for given column indexes
//Takes an array of format columnindex => statement
function ReplGenSort($replacements)
{
    global $ORDERS;
    foreach($replacements AS $colindex => $replacement)
    {
        if(is_numeric($colindex) && isset($ORDERS['COLUMN_'.intval($colindex)]))
        {
            $ORDERS['COLUMN_'.intval($colindex)] = $replacement;
        }
        elseif(isset($ORDERS[$colindex]))
        {
            $ORDERS[$colindex] = $replacement;
        }
    }
    return;
}

?>