<?php

/**
 * @author Guido Gybels
 * @copyright 2015
 * @project BCS CRM Solution
 * @description This unit contains system functions with no visual properties
 */

require_once("initialise.inc");
require_once('person.inc');
require_once('organisation.inc');
use Aws\S3\S3Client;

$do = (!empty($_GET['do']) ? IdentifierStr($_GET['do']) : null);
$response = array('success' => FALSE, 'errorcode' => 0, 'errormessage' => '');
//Ensure that this is an ajax request from this system
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && (strcasecmp($_SERVER['HTTP_X_REQUESTED_WITH'], 'XMLHttpRequest') == 0) && (strpos($_SERVER['HTTP_REFERER'], $_SERVER['SERVER_NAME']) == 8))
{
    //Syscall functions that do not require authentication
    switch($do)
    {
        case 'login':
            if (CheckRequiredParams(array('username' => FALSE, 'password' => FALSE), $_GET))
            {
                $response['errormessage'] = "Login failed. The username or password are not correct.";
                $response['errorcode'] = 1;
                $username = filter_var($_GET['username'], FILTER_VALIDATE_EMAIL);
                $password = $_GET['password'];
                $sql =
                "SELECT tbllogin.Salt, tbllogin.PWHash, tbllogin.LastAttempt,
                        tblperson.PersonID, tblperson.Firstname, tblperson.Lastname, tblperson.Middlenames, tblperson.Title,
                        tbllogin.LastChanged, ABS(TIMESTAMPDIFF(DAY, UTC_TIMESTAMP(), tbllogin.LastChanged)) AS `PasswordAge`
                 FROM tbllogin
                 INNER JOIN tblperson ON tblperson.PersonID = tbllogin.PersonID
                 INNER JOIN tblemail ON tblemail.PersonID = tblperson.PersonID
                 WHERE (tblemail.Email = '{$username}') AND ((tbllogin.Expires IS NULL ) OR (tbllogin.Expires > UTC_TIMESTAMP())) AND ((tbllogin.FailCount < ".intval($SYSTEM_SETTINGS['Security']['MaxFailCount']).") OR (FIND_IN_SET('noautolock', tbllogin.Flags)))";
                $data = SingleRecord($SYSTEM_SETTINGS["Database"], $sql);
                if(!empty($data))
                {
                    if($data['PasswordAge'] <= $SYSTEM_SETTINGS["Security"]['PasswordExpiry']) {
                        if(hash('sha512', $data['Salt'].$password, FALSE) == $data['PWHash']) {
                            $sql = "UPDATE tbllogin SET LastAttempt = UTC_TIMESTAMP(), FailCount = 0, SuccessCount = SuccessCount + 1 WHERE PersonID = ".$data['PersonID'];
                            mysqli_query($SYSTEM_SETTINGS["Database"], $sql);
                            do {
                                $token = RandomString(64);
                                $sql = "SELECT tblauth.Token FROM tblauth WHERE tblauth.Token = '{$token}'";
                                $existing = SingleValue($SYSTEM_SETTINGS["Database"], $sql);
                                if(empty($existing))
                                {
                                    //This token is not yet in use
                                    $sql = "INSERT INTO tblauth (Token, Expires, PersonID) VALUES ('{$token}', DATE_ADD(UTC_TIMESTAMP(), INTERVAL 12 HOUR), {$data['PersonID']})";
                                    if (mysqli_query($SYSTEM_SETTINGS["Database"], $sql)) {
                                        setcookie(CONSTAuthCookie, json_encode(array('token' => $token)), strtotime('+12 HOUR'), '', '', TRUE);
                                        $response['success'] = TRUE;
                                        unset($data['Salt']);
                                        unset($data['PWHash']);
                                        foreach($data AS $key => $value) {
                                            $response[strtolower($key)] = $value;
                                        }
                                        $response['errorcode'] = 0;
                                        $response['errormessage'] = '';
                                        if(defined('__DEBUGMODE') && __DEBUGMODE) {
                                            AddToSysLog(array('Caption' => 'Logon completed', 'PersonID' => $data['PersonID'], 'Description' => 'The user has logged on', 'Expiry' => $SYSTEM_SETTINGS["ExpiryPolicies"]['Logon'], 'Data' => array('Username' => $username, 'Token' => $token)));
                                        }
                                        if(defined('__DEBUGMODE') && __DEBUGMODE) {
                                            AddNotification(array(
                                                'type' => 'warning',
                                                'messages' => array(
                                                    array(
                                                        'caption' => '<warning><b>Debug Mode</b></warning> The system is running in debug mode.',
                                                        'icon' => 'fa-bolt',
                                                    )
                                                ),
                                            ), $token);
                                        }
                                        if($SYSTEM_SETTINGS["System"]['Email']['Paused']) {
                                            AddNotification(array(
                                                'type' => 'warning',
                                                'messages' => array(
                                                    array(
                                                        'caption' => '<warning><b>Email Queue</b></warning> The email sending queue is paused.',
                                                        'icon' => 'fa-exclamation-triangle',
                                                    )
                                                ),
                                            ), $token);
                                        }
                                        //Test if timezone tables are loaded
                                        $sql = "SELECT CONVERT_TZ('2010-01-01 23:00:00','UTC','Europe/London')";
                                        $converted = SingleValue($SYSTEM_SETTINGS['Database'], $sql);
                                        if(empty($converted)) {
                                            AddNotification(array(
                                                'type' => 'error',
                                                'messages' => array(
                                                    array(
                                                        'caption' => '<error><b>Timezone</b></error> The timezone tables have not been loaded to the database.',
                                                        'icon' => 'fa-times-circle',
                                                    )
                                                ),
                                            ), $token);
                                        }
                                        $daystoPWExpiry = ($SYSTEM_SETTINGS["Security"]['PasswordExpiry'] - $data['PasswordAge']);
                                        if($daystoPWExpiry < 15) {
                                            $type = ($daystoPWExpiry < 4 ? "danger" : ($daystoPWExpiry < 8 ? "warning" : "info"));
                                            AddNotification(array(
                                                'type' => $type,
                                                'messages' => array(
                                                    array(
                                                        'caption' => "<{$type}><b>Password</b></{$type}> Your password will expire ".($daystoPWExpiry > 0 ? "in ".SinPlu($daystoPWExpiry, 'day') : "today").".",
                                                        'icon' => 'fa-key',
                                                    )
                                                ),
                                            ), $token);
                                        }
                                        break;
                                    } else {
                                        $response['errormessage'] = "Login failed. Unable to insert authentication token.";
                                        $response['errorcode'] = 1;
                                        AddToSysLog(array('Caption' => 'Logon failed', 'PersonID' => $data['PersonID'], 'Description' => $response['errormessage'], 'Data' => array('errormessage' => mysqli_error($SYSTEM_SETTINGS["Database"]), 'errorcode' => mysqli_errno($SYSTEM_SETTINGS["Database"]))));
                                        break;
                                    }
                                }
                            } while(TRUE);
                        } else {
                            $sql = "UPDATE tbllogin SET LastAttempt = UTC_TIMESTAMP(), FailCount = FailCount + 1 WHERE PersonID = ".$data['PersonID'];
                            AddToSysLog(array('Caption' => 'Logon failed', 'PersonID' => $data['PersonID'], 'Description' => $response['errormessage']));
                            mysqli_query($SYSTEM_SETTINGS["Database"], $sql);
                        }
                    } else {
                        $response['errormessage'] = "Login failed. Your password has expired.";
                        $response['errorcode'] = 2;                        
                        AddToSysLog(array('Caption' => 'Logon failed', 'PersonID' => $data['PersonID'], 'Description' => 'The password has expired.'));
                    }
                }
            }
            break;
        case 'logout':
            if(isset($_COOKIE[CONSTAuthCookie]))
            {
                $CookieData = json_decode($_COOKIE[CONSTAuthCookie], TRUE);
                if(isset($CookieData['token']))
                {
                    $lookfor = IdentifierStr($CookieData['token']);
                    //Clear all existing tokens for the user
                    $sql =
                    "SELECT tblperson.PersonID
                     FROM tblauth
                     INNER JOIN tblperson ON tblperson.PersonID = tblauth.PersonID
                     WHERE (tblauth.Token = '{$lookfor}')";
                     $personid = SingleValue($SYSTEM_SETTINGS["Database"], $sql);
                     $sql = "DELETE FROM tblauth WHERE PersonID = ".intval($personid);
                     if (mysqli_query($SYSTEM_SETTINGS["Database"], $sql)) {
                        if(defined('__DEBUGMODE') && __DEBUGMODE) {
                            AddToSysLog(array('Caption' => 'Logoff completed', 'PersonID' => $personid, 'Description' => 'The user has logged out', 'Expiry' => $SYSTEM_SETTINGS["ExpiryPolicies"]['Logon'], 'Data' => array('Token' => $lookfor)));
                        }
                     }
                }
                //Remove the authentication cookie
                setcookie(CONSTAuthCookie, "", -1, '', '', TRUE);
                unset($_COOKIE[CONSTAuthCookie]);
                //Remove cookies that save table states
                foreach($_COOKIE AS $cookiename => $cookie) {
                    $response[$cookiename] = setcookie($cookiename, "", time()); 
                }
            }
            $response['success'] = TRUE;
            break;
        case 'resetaccountpw':
            if (CheckRequiredParams(array('personid' => FALSE), $_GET)) {
/*                $username = filter_var($_GET['username'], FILTER_VALIDATE_EMAIL);
                $sql =
                "SELECT tbllogin.LoginID, tbllogin.LastAttempt, tbllogin.LastChanged,
                        tblperson.PersonID, tblperson.Firstname, tblperson.Lastname, tblperson.Middlenames, tblperson.Title
                 FROM tbllogin
                 INNER JOIN tblperson ON tblperson.PersonID = tbllogin.PersonID
                 INNER JOIN tblemail ON tblemail.PersonID = tblperson.PersonID
                 WHERE (tblemail.Email = '{$username}')";*/
                $personid = intval($_GET['personid']);
                $sql = "SELECT tbllogin.LoginID, tbllogin.LastAttempt, tbllogin.LastChanged, GROUP_CONCAT(DISTINCT tblemail.Email SEPARATOR ';') AS `Emails`,
                        tblperson.PersonID, tblperson.Firstname, tblperson.Lastname, tblperson.Middlenames, tblperson.Title
                 FROM tbllogin
                 INNER JOIN tblperson ON tblperson.PersonID = tbllogin.PersonID
                 INNER JOIN tblemail ON tblemail.PersonID = tblperson.PersonID
                 WHERE tblperson.PersonID = {$personid}
                 GROUP BY tblperson.PersonID";
                $data = SingleRecord($SYSTEM_SETTINGS["Database"], $sql);
                if(!empty($data)) {
                    $setSQL = new stmtSQL('UPDATE', 'tbllogin', $SYSTEM_SETTINGS["Database"]);
                    $setSQL->addWhere('LoginID', 'integer', $data['LoginID']);
                    $Salt = safe_b64encode(mcrypt_create_iv(48, MCRYPT_DEV_URANDOM));
                    $setSQL->addField('Salt', 'string', $Salt);
                    $NewPW = RandomString($SYSTEM_SETTINGS['Security']['MinPasswordLength'], 'abcdefghijkmnpqrstuvwxyzABCDEFGHIJKLMNPQRSTUVWXYZ123456789');
                    $PWHash = hash('sha512', $Salt.$NewPW, FALSE);
                    $setSQL->addField('PWHash', 'string', $PWHash);
                    $setSQL->addField('FailCount', 'integer', 0);
                    $setSQL->addFieldStmt('LastChanged', 'UTC_TIMESTAMP()');
                    $response = ExecuteSQL($setSQL);
                    if($response['success']) {
                        if(defined('__DEBUGMODE') && __DEBUGMODE) {
                            file_put_contents(IncTrailingPathDelimiter(sys_get_temp_dir())."accounts.txt", $data['Emails'].'='.$NewPW."\r\n", FILE_APPEND);
                        }
                        $PERSON = new crmPerson('eventsPerson', $SYSTEM_SETTINGS['Database'], $data['PersonID'], $SYSTEM_SETTINGS["Membership"]);
                        $issent = SendEmailTemplate(
                            'sys_pwreset',
                            array($PERSON->GetRecord("personal", TRUE), array('Password' => $NewPW)),
                            array('hide' => $NewPW)
                        );
                        AddHistory(array(
                            'type' => 'security', 'description' => 'Nucleus password reset'.($issent ? '' : ' (unable to send confirmation)'), 'flags' => ($issent ? 'success' : 'warning'),
                            'PersonID' => $PERSON->PersonID,
                        ), $response['_affectedrows']);
                        if(!$issent) {
                            AddToSysLog(array('Caption' => 'Security', 'EntryKind' => 'warning', 'IsSystem' => TRUE, 'Description' => 'Unable to send password reset confirmation email', 'Data' => $data));
                        }
                    }
                } else {
                    $response['errormessage'] = "Unable to complete reset: account not found";
                    $response['errorcode'] = 2;
                    AddToSysLog(array('Caption' => 'Security', 'EntryKind' => 'warning', 'IsSystem' => TRUE, 'Description' => 'The user was not found.', 'Data' => $_GET));
                }
            }
            break;
        case 'calcprice':
            $price = new crmScaledPrice($SYSTEM_SETTINGS['Database'], (empty($_POST['ISO4217']) ? 'GBP' : $_POST['ISO4217']));
            $response['qty'] = (isset($_POST['Qty']) ? intval($_POST['Qty']) : 1);
            $price->Net = $_POST['Net']*$response['qty'];
            $price->VATRate = $_POST['VATRate'];
            $response['values'] = array();
            $response['strings'] = array();
            foreach(array('Net', 'VAT', 'VATRate', 'Value') AS $field) {
                $response['values'][strtolower($field)] = $price->$field;
                $response['strings'][strtolower($field)] = $price->AsString($field, FALSE);
            }
            $response['Symbol'] = $price->Symbol;
            $response['ISO4217'] = $price->ISO4217;
            $response['success'] = TRUE;
            break;
        case 'getpublications':
            $sql = 
            "SELECT tblpublication.PublicationID, tblpublication.Title, tblpublication.PublicationType, tblpublication.PublicationScope, tblpublication.Description, tblpublication.Flags
             FROM tblpublication
             ORDER BY tblpublication.Title";
            $query = mysqli_query($SYSTEM_SETTINGS['Database'], $sql);
            if($query) {
                $response['publications'] = array();
                while($row = mysqli_fetch_assoc($query)) {
                    $response['publications'][$row['PublicationID']] = array(
                        'PublicationID' => $row['PublicationID'],
                        'Title' => $row['Title'],
                        'Description' => $row['Description'],
                        'PublicationType' => $row['PublicationType'],
                        'PublicationScope' => $row['PublicationScope'],
                    );
                }
                $response['success'] = TRUE;
                $response['errormessage'] = '';
                $response['errorcode'] = 0;
            } else {
                $response['errormessage'] =  mysqli_error($SYSTEM_SETTINGS["Database"]);
                $response['errorcode'] = mysqli_errno($SYSTEM_SETTINGS["Database"]);
            }
            break;            
        default:
            //Syscall functions that are only available to authenticated users
            if(Authenticate()) {
                if(defined('__DEBUGMODE') && __DEBUGMODE) {
                    $response['__debug'] = array(
                        'authenticated' => $AUTHENTICATION['Authenticated'],
                    );
                }
                switch($do) {
                    case 'addPerson':
                        $setSQL = new stmtSQL('INSERT', 'tblperson', $SYSTEM_SETTINGS["Database"]);
                        foreach(array('Firstname' => 'string',
                                      'Middlenames' => array(
                                          'fieldtype' => 'string',
                                          'emptyasnull' => TRUE,
                                      ),
                                      'Lastname' => 'string',
                                      'Title' => 'string',
                                      'Gender' => 'enum',
                                      'DOB' => 'date',
                                      'ISO3166' => 'string',
                                      'ISO4217' => 'string',
                                    ) AS $fieldname => $field) {
                            if(is_string($field)) {
                                $setSQL->addField($fieldname, $field, $_POST[$fieldname]);
                            } elseif(is_array($field)) {
                                $setSQL->addField($fieldname, $field['fieldtype'], $_POST[$fieldname], (empty($field['table']) ? null : $field['table']), (empty($field['emptyasnull']) ? FALSE : $field['emptyasnull']));
                            }
                        }
                        $sql = "SELECT NationalityID FROM tblnationality WHERE ISO3166 = '".IdentifierStr($_POST['ISO3166'])."'";
                        $nationalityid = SingleValue($SYSTEM_SETTINGS['Database'], $sql);
                        $setSQL->addField('NationalityID', "integer", $nationalityid);
                        $response = ExecuteSQL($setSQL);
                        if($response['success']) {
                            $response['personid'] = mysqli_insert_id($SYSTEM_SETTINGS["Database"]);
                            AddHistory(array('type' => 'edit', 'description' => 'Record created', 'PersonID' => $response['personid'], 'author' => $AUTHENTICATION['Person']['PersonID'], 'flags' => 'success'));
                            AddToSysLog(array('Caption' => 'Record created', 'PersonID' => $AUTHENTICATION['Person']['PersonID'], 'Description' => 'New record created: '.NameStr($_POST['Firstname']).' '.NameStr($_POST['Lastname']) , 'Data' => array_merge($_POST, array('PersonID' => $response['personid']))));
                        }
                        break;
                    case 'addOrganisation':
                        $setSQL = new stmtSQL('INSERT', 'tblorganisation', $SYSTEM_SETTINGS["Database"]);
                        foreach(array('Name' => 'string',
                                      'Ringgold' => array(
                                          'fieldtype' => 'string',
                                          'emptyasnull' => TRUE,
                                      ),
                                      'VATNumber' => array(
                                          'fieldtype' => 'string',
                                          'emptyasnull' => TRUE,
                                      ),
                                      'CharityReg' => array(
                                          'fieldtype' => 'string',
                                          'emptyasnull' => TRUE,
                                      ),
                                    ) AS $fieldname => $field) {
                            if(is_string($field)) {
                                $setSQL->addField($fieldname, $field, $_POST[$fieldname]);
                            } elseif(is_array($field)) {
                                $setSQL->addField($fieldname, $field['fieldtype'], $_POST[$fieldname], (empty($field['table']) ? null : $field['table']), (empty($field['emptyasnull']) ? FALSE : $field['emptyasnull']));
                            }
                        }
                        $response = ExecuteSQL($setSQL);
                        if($response['success']) {
                            $response['organisationid'] = mysqli_insert_id($SYSTEM_SETTINGS["Database"]);
                            AddHistory(array('type' => 'edit', 'description' => 'Record created', 'OrganisationID' => $response['organisationid'], 'author' => $AUTHENTICATION['Person']['PersonID'], 'flags' => 'success'));
                            AddToSysLog(array('Caption' => 'Record created', 'PersonID' => $AUTHENTICATION['Person']['PersonID'], 'Description' => 'New record created: '.NameStr($_POST['Name']), 'Data' => array_merge($_POST, array('OrganisationID' => $response['organisationid']))));
                        }
                        break;
                    case 'createDDJob':
                        try {
                            if(StartTransaction($SYSTEM_SETTINGS["Database"])) {
                                $setSQL = new stmtSQL('INSERT', 'tbldirectdebitjob', $SYSTEM_SETTINGS["Database"]);
                                $setSQL->addField('PersonID', 'integer', $AUTHENTICATION['Person']['PersonID']);
                                $setSQL->addFieldStmt('Created', 'UTC_TIMESTAMP()');
                                $setSQL->addField('PlannedSubmission', 'utc', $_POST['PlannedSubmission']);
                                $setSQL->addField('Description', 'text', $_POST['Description']);
                                $response = ExecuteSQL($setSQL);
                                if($response['success']) {
                                    $directdebitjobid = mysqli_insert_id($SYSTEM_SETTINGS["Database"]);
                                    if(!empty($_POST['InclDDI'])) {
                                        //Add new instructions
                                        $sql = 
                                        "INSERT INTO tbldirectdebitjobitem (DirectDebitJobID, DDIID)
                                         SELECT {$directdebitjobid}, DDIID
                                         FROM tblddi
                                         WHERE AUDDIS IS NOT NULL
                                        ".(defined('__DEBUGMODE') && __DEBUGMODE ? "LIMIT 10": "");
                                        if(!mysqli_query($SYSTEM_SETTINGS["Database"], $sql)) {
                                            throw new crmException('Error adding DDI job items: '.mysqli_error($SYSTEM_SETTINGS["Database"]), mysqli_errno($SYSTEM_SETTINGS["Database"]));
                                        }
                                    }
                                    if(!empty($_POST['InclInvoices'])) {
                                        //Add new instructions
                                        $sql = 
                                        "INSERT INTO tbldirectdebitjobitem (DirectDebitJobID, InvoiceID)
                                         SELECT {$directdebitjobid}, tblinvoice.InvoiceID
                                         FROM tblinvoice
                                         LEFT JOIN tblinvoiceitem ON tblinvoiceitem.InvoiceID = tblinvoice.InvoiceID
                                         LEFT JOIN tblinvoicetoperson ON tblinvoicetoperson.InvoiceID = tblinvoice.InvoiceID
                                         LEFT JOIN tblperson ON tblperson.PersonID = tblinvoicetoperson.PersonID
                                         LEFT JOIN tblinvoiceitemtype ON tblinvoiceitemtype.InvoiceItemTypeID = tblinvoiceitem.InvoiceItemTypeID
                                         LEFT JOIN tblcurrency ON tblinvoice.ISO4217 = tblcurrency.ISO4217
                                         LEFT JOIN tblddi ON (tblddi.PersonID = tblperson.PersonID) AND (tblcurrency.ISO4217 = 'GBP') AND (tblddi.InstructionScope = tblinvoiceitemtype.CategorySelector)
                                         WHERE (tblinvoice.InvoiceType = 'invoice') AND (tblinvoice.InvoiceNo IS NULL) AND (tblperson.Deceased IS NULL) AND (tblddi.InstructionStatus = 'active') AND (tblddi.ValidFrom <= UTC_TIMESTAMP) 
                                         GROUP BY tblinvoice.InvoiceID
                                         HAVING (COUNT(DISTINCT tblinvoiceitemtype.InvoiceItemTypeID) = 1)
                                        ".(defined('__DEBUGMODE') && __DEBUGMODE ? "LIMIT 10": "");
                                        if(!mysqli_query($SYSTEM_SETTINGS["Database"], $sql)) {
                                            throw new crmException('Error adding Invoice job items: '.mysqli_error($SYSTEM_SETTINGS["Database"]), mysqli_errno($SYSTEM_SETTINGS["Database"]));
                                        }
                                    }
                                    $dp = AddDPEntry(array(
                                        'SourcePersonID' => $AUTHENTICATION['Person']['PersonID'],
                                        'ActionType' => 'directdebit',
                                        'Description' => 'Direct Debit Job',
                                        'Purpose' => $_POST['Description'],
                                        'ThirdPartyName' => $SYSTEM_SETTINGS["Finance"]['DirectDebit']['ProcessorName'],
                                    ));
                                    $sql = "INSERT INTO tbldataprotectiontodirectdebit (DataProtectionID, DirectDebitJobID) VALUES ({$dp}, {$directdebitjobid})";
                                    $response = ExecuteSQL($sql);
                                    if($response['success']) {
                                        AddHistory(array('type' => 'edit', 'description' => 'Direct Debit Job created: '.(empty($_POST['Description']) ? '#'.$directdebitjobid : TextEllipsis($_POST['Description'], 60)), 'DirectDebitJobID' => $directdebitjobid, 'PersonID' => $AUTHENTICATION['Person']['PersonID'], 'author' => $AUTHENTICATION['Person']['PersonID'], 'flags' => 'success'));
                                        $response['directdebitjobid'] = $directdebitjobid;
                                    }
                                }
                                if(!$response['success']) {
                                    throw new crmException('Unable to create new direct debit job: '.$response['errormessage'], $response['errorcode']);
                                }                                
                                mysqli_commit($SYSTEM_SETTINGS["Database"]);
                            } else {
                                throw new crmException('Unable to start transaction: '.mysqli_error($SYSTEM_SETTINGS["Database"]), mysqli_errno($SYSTEM_SETTINGS["Database"]));
                            }
                        } catch( Exception $e ) {
                            mysqli_rollback($SYSTEM_SETTINGS["Database"]);
                            $response['success'] = FALSE;
                            $response['errormessage'] = $e->getMessage();
                            $response['errorcode'] = $e->getCode();                            
                            AddToSysLog(array('EntryKind' => 'error', 'IsSystem' => TRUE, 'Expiry' => $SYSTEM_SETTINGS['ExpiryPolicies']['LogErrors'], 'Caption' => 'Direct Debit', 'Description' => 'Unable to create new direct debit job: '.$response['errormessage'].' ('.$response['errorcode'].')', 'Data' => array('File' => __FILE__, 'Line' => __LINE__)));
                        }
                        break;
                    case 'delDDJobItem':
                        $sql = "SELECT DirectDebitJobItemID, DirectDebitJobID FROM tbldirectdebitjobitem WHERE DirectDebitJobItemID = ".$_POST['DirectDebitJobItemID'];
                        $data = SingleRecord($SYSTEM_SETTINGS['Database'], $sql);
                        if(!empty($data['DirectDebitJobID'])) {
                            $JOB = new crmDirectDebitJob('eventsFinance', $SYSTEM_SETTINGS['Database'], $data['DirectDebitJobID'], $SYSTEM_SETTINGS['Finance']['DirectDebit']);
                            try {
                                $JOB->Lock();
                                $item = $JOB->GetItem($data['DirectDebitJobItemID']);
                                if(!empty($item)) {
                                    if ($JOB->DeleteItem($data['DirectDebitJobItemID'])) {
                                        AddHistory(array('type' => 'delete', 'description' => $item['JobItemDescription'].' removed from Direct Debit Job #'.$item['DirectDebitJobID'], 'PersonID' => $item['PersonID'], 'author' => $AUTHENTICATION['Person']['PersonID']));
                                        AddHistory(array('type' => 'delete', 'description' => $item['JobItemDescription'].' removed from Direct Debit Job: '.$item['Fullname'], 'DirectDebitJobID' => $item['DirectDebitJobID'], 'author' => $AUTHENTICATION['Person']['PersonID']));
                                    }
                                }
                                $response['success'] = TRUE;
                            } catch ( Exception $e ) {
                            }
                            $JOB->Unlock();
                        }
                        break;
                    case 'setlock':
                        if(!empty($_POST['DirectDebitJobID'])) {
                            $setSQL = new stmtSQL('UPDATE', 'tbldirectdebitjob', $SYSTEM_SETTINGS["Database"]);
                            $setSQL->addWhere('DirectDebitJobID', 'integer', $_POST['DirectDebitJobID']);
                            $setSQL->addFieldStmt('Locked', (empty($_POST['Locked']) ? 'GREATEST(Locked-1, 0)' : 'Locked+1'));
                            $response = ExecuteSQL($setSQL);
                        }
                        break;
                    case 'ddNotifyEmail':
                        ignore_user_abort(true);
                        set_time_limit($SYSTEM_SETTINGS['System']['TimeLimitExport']);
                        $directdebitjobid = intval($_POST['DirectDebitJobID']);
                        if(!empty($directdebitjobid)) {
                            $JOB = new crmDirectDebitJob('eventsFinance', $SYSTEM_SETTINGS['Database'], $directdebitjobid, $SYSTEM_SETTINGS['Finance']['DirectDebit']);
                            $response['job'] = print_r($JOB->Job, TRUE);
                            try {
                                $setSQL = new stmtSQL('UPDATE', 'tbldirectdebitjob', $SYSTEM_SETTINGS["Database"]);
                                $setSQL->addWhere('DirectDebitJobID', 'integer', $directdebitjobid);
                                $setSQL->addField('PlannedSubmission', 'utc', $_POST['SubmissionDate']);                                    
                                ExecuteSQL($setSQL);
                                $response = $JOB->Notify('DDEmailNotify');
                                if($response['success']) {
                                    $sql = "UPDATE tbldirectdebitjob SET EmailNotifications = UTC_TIMESTAMP WHERE DirectDebitJobID = {$directdebitjobid}";
                                    ExecuteSQL($sql);
                                    $tempfilename = tempnam(sys_get_temp_dir(), 'ddr');
                                    file_put_contents($tempfilename, implode("<br>\r\n", $response['report']));
                                    AddFile($tempfilename, array(
                                        'filename' => 'DirectDebit_Email_Notification_Report_'.gmdate('Ymd').'_'.time().'.html',
                                        'title' => 'Report on Direct Debit Email Notifications for Job '.$directdebitjobid,
                                        'DirectDebitJobID' => $directdebitjobid,
                                        'PersonID' => $AUTHENTICATION['Person']['PersonID'],
                                        'mimetype' => 'application/octet-stream'
                                    ));
                                    unlink($tempfilename);
                                    AddHistory(array(
                                        'type' => 'edit',
                                        'description' => 'Direct Debit Job: '.SinPlu($response['notificationscount'], 'email notification').' sent'.($response['warningcount'] > 0 ? ' (with warnings)' : ''),
                                        'DirectDebitJobID' => $directdebitjobid,
                                        'PersonID' => $AUTHENTICATION['Person']['PersonID'],
                                        'author' => $AUTHENTICATION['Person']['PersonID'], 
                                        'flags' => ($response['warningcount'] > 0 ? 'warning' : 'success')
                                    ));
                                }
                            } catch( Exception $e ) {
                                
                            }
                            $JOB->Unlock(); //The locking was done prior to calling this function
                        }
                        break;
                    case 'createddsubmission':
                        ignore_user_abort(true);
                        set_time_limit($SYSTEM_SETTINGS['System']['TimeLimitExport']);
                        $directdebitjobid = intval($_POST['DirectDebitJobID']);
                        if(!empty($directdebitjobid)) {
                            $JOB = new crmDirectDebitJob('eventsFinance', $SYSTEM_SETTINGS['Database'], $directdebitjobid, $SYSTEM_SETTINGS['Finance']['DirectDebit']);
                            try {
                                $response = $JOB->CreateSubmission();
                                if($response['success']) {
                                    $setSQL = new stmtSQL('UPDATE', 'tbldirectdebitjob', $SYSTEM_SETTINGS["Database"]);
                                    $setSQL->addWhere('DirectDebitJobID', 'integer', $directdebitjobid);
                                    $setSQL->addField('Submitted', 'utc', $_POST['SubmissionDate']);                                    
                                    ExecuteSQL($setSQL);
                                    //TODO: Processor name
                                    if($response['collectioncount'] > 0) {
                                        AddFile($response['collections'], array(
                                            'filename' => $SYSTEM_SETTINGS["General"]['OrgShortName'].'_C_'.gmdate('Ymd').'_'.time().'.txt',
                                            'title' => 'Direct Debit Collections Job '.$directdebitjobid,
                                            'DirectDebitJobID' => $directdebitjobid,
                                            'PersonID' => $AUTHENTICATION['Person']['PersonID'],
                                            'mimetype' => 'application/octet-stream'
                                        ));
                                    }
                                    if($response['instructioncount'] > 0) {
                                        AddFile($response['instructions'], array(
                                            'filename' => $SYSTEM_SETTINGS["General"]['OrgShortName'].'_I_'.gmdate('Ymd').'_'.time().'.txt',
                                            'title' => 'Direct Debit Instructions '.$directdebitjobid,
                                            'DirectDebitJobID' => $directdebitjobid,
                                            'PersonID' => $AUTHENTICATION['Person']['PersonID'],
                                            'mimetype' => 'application/octet-stream'
                                        ));
                                    }
                                    AddHistory(array('type' => 'edit', 'description' => 'Direct Debit Job Submission created', 'DirectDebitJobID' => $directdebitjobid, 'PersonID' => $AUTHENTICATION['Person']['PersonID'], 'author' => $AUTHENTICATION['Person']['PersonID'], 'flags' => 'success'));
                                }
                            } catch( Exception $e ) {
                                
                            }
                            $JOB->Unlock(); //The locking was done prior to calling this function
                        }
                        break;
                    case 'processddsubmission':
                        ignore_user_abort(true);
                        set_time_limit($SYSTEM_SETTINGS['System']['TimeLimitExport']);
                        $directdebitjobid = intval($_POST['DirectDebitJobID']);
                        if(!empty($directdebitjobid)) {
                            $JOB = new crmDirectDebitJob('eventsFinance', $SYSTEM_SETTINGS['Database'], $directdebitjobid, $SYSTEM_SETTINGS['Finance']['DirectDebit']);
                            try {
                                $response = $JOB->ProcessResults(explode("\n", $_POST['Failed']), InvoiceSettings());
                                $sql = "UPDATE tbldirectdebitjob SET `ResultsProcessed` = UTC_TIMESTAMP WHERE DirectDebitJobID = {$directdebitjobid}";
                                ExecuteSQL($sql);
                                $tempfilename = tempnam(sys_get_temp_dir(), 'ddr');
                                file_put_contents($tempfilename, implode("<br>\r\n", $response['report']));
                                AddFile($tempfilename, array(
                                    'filename' => 'DirectDebit_Processing_Report_'.gmdate('Ymd').'_'.time().'.html',
                                    'title' => 'Report on Direct Debit Results Processing for Job '.$directdebitjobid,
                                    'DirectDebitJobID' => $directdebitjobid,
                                    'PersonID' => $AUTHENTICATION['Person']['PersonID'],
                                    'mimetype' => 'application/octet-stream'
                                ));
                                unlink($tempfilename);
                                $haserrors = (($response['failedcollectioncount']+$response['failedinstructioncount'])>0);
                                AddHistory(array(
                                    'type' => 'edit',
                                    'description' => 'Direct Debit Job results processed '.($haserrors ? '(with errors)' : '(no errors)'),
                                    'DirectDebitJobID' => $directdebitjobid,
                                    'PersonID' => $AUTHENTICATION['Person']['PersonID'],
                                    'author' => $AUTHENTICATION['Person']['PersonID'], 
                                    'flags' => ($haserrors ? 'warning' : 'success')
                                ));
                            } catch( Exception $e ) {
                                
                            }
                            $JOB->Unlock(); //The locking was done prior to calling this function
                        }
                        break;
                    case 'calculatemsfee':
/*                        $msgradeid = intval($_POST['MSGradeID']);
                        $sql = 
                        "SELECT tblwscategory.WSCategoryID, tblwscategory.CategoryName, tblapplicationstage.ApplicationStageID, tblapplicationstage.StageName,
                                tblmsgrade.MSGradeID, tblmsgrade.GradeCaption
                         FROM tblapplicationstage
                         LEFT JOIN tblwscategory ON tblwscategory.CategorySelector = 'members'
                         LEFT JOIN tblmsgrade ON tblmsgrade.MSGradeID = {$msgradeid}
                         ORDER BY tblapplicationstage.StageOrder
                         LIMIT 1
                        ";
                        $appsettings = SingleRecord($SYSTEM_SETTINGS['Database'], $sql);*/
                        $msfees = new crmMSFees($SYSTEM_SETTINGS['Database'], $SYSTEM_SETTINGS['Membership']['GradeCaption']);
                        $params = array(
                            'ISO4217' => IdentifierStr($_POST['ISO4217']),
                            'MSGradeID' => intval($_POST['MSGradeID']),
                            'ISO3166' => IdentifierStr($_POST['ISO3166']),
//                            'ForDate' => date('Y-m-d H:i:s', strtotime($_POST['ForDate'].' UTC')),
                            'IsDD' => !empty($_POST['IsDD']),
                            'IsGroup' => !empty($_POST['IsGroup']),
                            'NOY' => (!empty($_POST['NOY']) ? intval($_POST['NOY']) : 1),
                        );
                        if(!empty($_POST['ForDate'])) {
                            $params['ForDate'] = date('Y-m-d H:i:s', strtotime($_POST['ForDate'].' UTC'));
                        }
                        $fee = $msfees->CalculateFee($params);
                        $response['success'] = TRUE;
                        $response['explain'] = array();
                        $response['fee'] = $fee;
                        foreach($fee->Explanation AS $line) {
                            $response['explain'][] = FmtText($line);
                        }
                        break;
                    case 'getmsfeevalue':
                        $msfees = new crmMSFees($SYSTEM_SETTINGS['Database'], $SYSTEM_SETTINGS['Membership']['GradeCaption']);
                        $params = array(
                            'ISO4217' => IdentifierStr($_POST['ISO4217']),
                            'MSGradeID' => intval($_POST['MSGradeID']),
                            'ISO3166' => IdentifierStr($_POST['ISO3166']),
//                            'ForDate' => date('Y-m-d H:i:s', strtotime($_POST['ForDate'].' UTC')),
                            'IsDD' => !empty($_POST['IsDD']),
                            'IsGroup' => !empty($_POST['IsGroup']),
                            'NOY' => (!empty($_POST['NOY']) ? intval($_POST['NOY']) : 1),
                        );
                        if(!empty($_POST['ForDate'])) {
                            $params['ForDate'] = date('Y-m-d H:i:s', strtotime($_POST['ForDate'].' UTC'));
                        }
                        $fee = $msfees->CalculateFee($params);
                        $response['values'] = array();
                        $response['strings'] = array();
                        foreach(array('Net', 'VAT', 'VATRate', 'Value') AS $field) {
                            $response['values'][strtolower($field)] = $fee->Price->$field;
                            $response['strings'][strtolower($field)] = $fee->Price->AsString($field, FALSE);
                        }
                        $response['Symbol'] = $fee->Price->Symbol;
                        $response['ISO4217'] = $fee->Price->ISO4217;
                        $response['success'] = TRUE;
                        break;
                    case 'getmsfeedifference':
                        $msfees = new crmMSFees($SYSTEM_SETTINGS['Database'], $SYSTEM_SETTINGS['Membership']['GradeCaption']);
                        $params = array(
                            'ISO4217' => IdentifierStr($_POST['ISO4217']),
                            'MSGradeID' => intval($_POST['MSGradeID']),
                            'ISO3166' => IdentifierStr($_POST['ISO3166']),
//                            'ForDate' => date('Y-m-d H:i:s', strtotime($_POST['ForDate'].' UTC')),
                            'IsDD' => !empty($_POST['IsDD']),
                            'IsGroup' => !empty($_POST['IsGroup']),
                            'NOY' => (!empty($_POST['NOY']) ? intval($_POST['NOY']) : 1),
                        );
                        if(!empty($_POST['ForDate'])) {
                            $params['ForDate'] = date('Y-m-d H:i:s', strtotime($_POST['ForDate'].' UTC'));
                        }
                        $currentfee = $msfees->CalculateFee($params);
                        $params['MSGradeID'] = intval($_POST['NewMSGradeID']);
                        $newfee = $msfees->CalculateFee($params);
                        $net = max(0, ($newfee->Net)-($currentfee->Net));
                        $price = new crmScaledPrice($SYSTEM_SETTINGS['Database'], $newfee->ISO4217);
                        $price->Net = $net;
                        $price->VATRate = $newfee->VATRate;
                        $response['values'] = array();
                        $response['strings'] = array();
                        foreach(array('Net', 'VAT', 'VATRate', 'Value') AS $field) {
                            $response['values'][strtolower($field)] = $price->$field;
                            $response['strings'][strtolower($field)] = $price->AsString($field, FALSE);
                        }
                        $response['Symbol'] = $price->Symbol;
                        $response['ISO4217'] = $price->ISO4217;
                        $response['success'] = TRUE;
                        break;
                    case 'saveddjobsettings':
                        $directdebitjobid = intval($_POST['DirectDebitJobID']);
                        if(!empty($directdebitjobid)) {
                            $setSQL = new stmtSQL('UPDATE', 'tbldirectdebitjob', $SYSTEM_SETTINGS["Database"]);
                            $setSQL->addWhere('DirectDebitJobID', 'integer', $directdebitjobid);
                            $setSQL->addField('PlannedSubmission', 'utc', $_POST['PlannedSubmission']);
                            $setSQL->addField('Description', 'text', $_POST['Description']);
                            $response = ExecuteSQL($setSQL);
                            if($response['success']) {
                                AddHistory(array('type' => 'edit', 'description' => 'Direct Debit Job settings updated: '.(empty($_POST['Description']) ? '#'.$directdebitjobid : TextEllipsis($_POST['Description'], 60)), 'DirectDebitJobID' => $directdebitjobid, 'PersonID' => $AUTHENTICATION['Person']['PersonID'], 'author' => $AUTHENTICATION['Person']['PersonID'], 'flags' => 'success'));
                            }
                        }
                        break;
                    case 'delddjob':
                        if (CheckRequiredParams(array('DirectDebitJobID' => FALSE), $_POST)) {
                            try {
                                $ddjob = new crmDirectDebitJob('eventsFinance', $SYSTEM_SETTINGS['Database'], intval($_POST['DirectDebitJobID']), $SYSTEM_SETTINGS['Finance']['DirectDebit']);
                                if(StartTransaction($SYSTEM_SETTINGS["Database"])) {
                                    if($ddjob->Found) {
                                        if($ddjob->Job['CanDelete']) {
                                            $sql = "DELETE FROM tbldirectdebitjob WHERE DirectDebitJobID = ".$ddjob->DirectDebitJobID;
                                            $response = ExecuteSQL($sql);
                                            if($response['success']) {
                                                $histStr = (empty($ddjob->Job['Description']) ? '#'.$ddjob->DirectDebitJobID : TextEllipsis($ddjob->Job['Description'], 60));
                                                if (AddToSysLog(array('EntryKind' => 'warning', 'Caption' => 'Direct Debit', 'PersonID' => $AUTHENTICATION['Person']['PersonID'], 'Description' => 'Direct Debit job deleted: '.$histStr, 'Data' => array_merge($_POST, array('Job' => print_r($ddjob, TRUE)))))) {
                                                    $setSQL = new stmtSQL('UPDATE', 'tbldataprotection', $SYSTEM_SETTINGS["Database"]);
                                                    $setSQL->addWhere('DataProtectionID', 'integer', $ddjob->Job['DataProtectionID']);
                                                    $setSQL->addFieldStmt('Closed', 'UTC_TIMESTAMP');
                                                    $setSQL->addField('ClosedPersonID', 'integer', $AUTHENTICATION['Person']['PersonID']);
                                                    $setSQL->addField('Resolution', 'string', 'The job has been deleted');
                                                    $response = ExecuteSQL($setSQL);
                                                    if($response['success']) {
                                                        AddHistory(array('type' => 'delete', 'flags' => 'warning', 'description' => 'Direct Debit Job deleted: '.$histStr, 'PersonID' => $AUTHENTICATION['Person']['PersonID'], 'author' => $AUTHENTICATION['Person']['PersonID']));
                                                    }
                                                }
                                            }
                                        } else {
                                            $response['errormessage'] = 'This job has already been processed';
                                            $response['errorcode'] = 2;
                                        }
                                    } else {
                                            $response['errormessage'] = 'Job not found';
                                            $response['errorcode'] = 1;
                                    }
                                    if(!$response['success']) {
                                        throw new crmException('Unable to delete direct debit job: '.$response['errormessage'], $response['errorcode']);
                                    }
                                    mysqli_commit($SYSTEM_SETTINGS["Database"]);
                                } else {
                                    throw new crmException('Unable to start transaction: '.mysqli_error($SYSTEM_SETTINGS["Database"]), mysqli_errno($SYSTEM_SETTINGS["Database"]));
                                }
                            } catch( Exception $e ) {
                                mysqli_rollback($SYSTEM_SETTINGS["Database"]);
                                $response['success'] = FALSE;
                                $response['errormessage'] = $e->getMessage();
                                $response['errorcode'] = $e->getCode();                            
                                AddToSysLog(array('EntryKind' => 'error', 'IsSystem' => TRUE, 'Expiry' => $SYSTEM_SETTINGS['ExpiryPolicies']['LogErrors'], 'Caption' => 'Direct Debit', 'Description' => 'Deletion failed: '.$response['errormessage'].' ('.$response['errorcode'].')', 'Data' => array('File' => __FILE__, 'Line' => __LINE__)));
                            }
                        }
                        break;
                    case 'savePersonPersonal':
                        if (CheckRequiredParams(array('PersonID' => FALSE), $_POST)) {
                            try {
                                if(StartTransaction($SYSTEM_SETTINGS["Database"])) {
                                    $personid = intval($_POST['PersonID']);
                                    $PERSON = new crmPerson('eventsPerson', $SYSTEM_SETTINGS['Database'], $personid, $SYSTEM_SETTINGS["Membership"]);
                                    $personal = $PERSON->GetRecord('personal');
                                    if($PERSON->Found) {
                                        //Special handling for setting persons deceased
                                        if(empty($personal['Deceased']) && !empty($_POST['Deceased']) && !empty($_POST['ConfirmedDeceased'])) {
                                            $ddate = ValidDateStr($_POST['Deceased']);
                                            $sql = "UPDATE tblperson SET Deceased = '{$ddate}' WHERE PersonID = {$personid}";
                                            if (mysqli_query($SYSTEM_SETTINGS["Database"], $sql)) {
                                                $PERSON->Reload('personal');
                                                AddHistory(array(
                                                    'type' => 'edit',
                                                    'description' => 'Deceased: '.date('j F Y', strtotime($ddate)),
                                                    'PersonID' => $personid, 'author' => $AUTHENTICATION['Person']['PersonID'], 'flags' => 'danger'
                                                ));
                                                //Terminate Membership
                                                $sql = 
                                                "SELECT tblpersonms.*, FIND_IN_SET('ismember', tblmsstatus.MSStatusFlags) AS `IsMember`
                                                 FROM tblpersonms
                                                 LEFT JOIN tblmsstatus ON tblmsstatus.MSStatusID = tblpersonms.MSStatusID
                                                 WHERE PersonID = {$personid}
                                                 ORDER BY BeginDate DESC
                                                 LIMIT 1";
                                                $lastmshistrecord = SingleRecord($SYSTEM_SETTINGS['Database'], $sql);
                                                if(!empty($lastmshistrecord) && ($lastmshistrecord['IsMember'])) {
                                                    $sql = "SELECT MSStatusID, MSStatusCaption FROM bcscrm.tblmsstatus WHERE FIND_IN_SET('deceased', MSStatusFlags) ORDER BY MSStatusID ASC LIMIT 1";
                                                    $status = SingleRecord($SYSTEM_SETTINGS['Database'], $sql);
                                                    if(!empty($status)) {
                                                        $deceaseddate = gmdate('Y-m-d H:i:s', strtotime($ddate));
                                                        $setSQL = new stmtSQL('UPDATE', 'tblpersonms', $SYSTEM_SETTINGS["Database"]);
                                                        $setSQL->addWhere('PersonMSID', 'integer', $lastmshistrecord['PersonMSID']);
                                                        $setSQL->addFieldStmt('EndDate', "DATE_SUB('$deceaseddate', INTERVAL 1 SECOND)");
                                                        if (mysqli_query($SYSTEM_SETTINGS["Database"], $setSQL->SQL())) {
                                                            $setSQL = new stmtSQL('INSERT', 'tblpersonms', $SYSTEM_SETTINGS["Database"]);
                                                            $setSQL->addField('PersonID', 'integer', $personid);
                                                            $setSQL->addFieldStmt('BeginDate', "'$deceaseddate'");
                                                            $setSQL->addField('MSStatusID', 'integer', $status['MSStatusID']);
                                                            if (mysqli_query($SYSTEM_SETTINGS["Database"], $setSQL->SQL())) {
                                                                AddHistory(array('type' => 'edit', 'description' => 'Membership status changed to: '.$status['MSStatusCaption'], 'author' => $AUTHENTICATION['Person']['PersonID'], 'PersonID' => $personid));
                                                            } else {
                                                                throw new crmException('Unable to add membership history record: '.mysqli_error($SYSTEM_SETTINGS["Database"]), mysqli_errno($SYSTEM_SETTINGS["Database"]));
                                                            }
                                                        } else {
                                                            throw new crmException('Unable to update membership history record: '.mysqli_error($SYSTEM_SETTINGS["Database"]), mysqli_errno($SYSTEM_SETTINGS["Database"]));
                                                        }
                                                    } else {
                                                        throw new crmException('Unable to obtain membership status value for deceased record');
                                                    }
                                                }
                                                
                                                
                                                
                                                //Send notification email
                                                
                                                //
                                                $reloadpage = TRUE;
                                            } else {
                                                throw new crmException('Error while setting deceased date: '.mysqli_error($SYSTEM_SETTINGS["Database"]), mysqli_errno($SYSTEM_SETTINGS["Database"]));
                                            }
                                        }
                                        $setSQL = SimpleUpdateSQL(
                                            'tblperson',
                                            array(
                                                'fieldname' => 'PersonID',
                                                'fieldtype' => 'integer',
                                                'value' => $personid,
                                            ),
                                            array(
                                                'Firstname' => 'string',
                                                'Middlenames' => array(
                                                    'fieldtype' => 'string',
                                                    'emptyasnull' => TRUE,
                                                ),
                                                'Lastname' => 'string',
                                                'Title' => 'string',
                                                'Gender' => 'enum',
                                                'DOB' => 'date',
                                                'ExtPostnominals' => 'string',
                                                'ISO3166' => 'string',
                                                'ISO4217' => 'string',
                                                'NationalityID' => array(
                                                    'fieldtype' => 'integer',
                                                    'emptyasnull' => TRUE,
                                                ),
                                                'Graduation' => array(
                                                    'fieldtype' => 'date',
                                                    'emptyasnull' => TRUE,
                                                ), 
                                                'PaidEmployment' => array(
                                                    'fieldtype' => 'date',
                                                    'emptyasnull' => TRUE,
                                                ),
                                            )
                                        );
                                        $response = ExecSQLandHistory($setSQL, 'PersonID', 'Personal details updated', $_POST);
                                        $personal = $PERSON->GetRecord('personal');
                                        $response['Deceased'] = $personal['Deceased'];
                                    } else {
                                        $response['errormessage'] = "The record could not be found.";
                                    }
                                    if(!$response['success']) {
                                        throw new crmException('Error while saving data: '.$response['errormessage'], $response['errorcode']);
                                    }
                                    mysqli_commit($SYSTEM_SETTINGS["Database"]);
                                } else {
                                    throw new crmException('Unable to start transaction: '.mysqli_error($SYSTEM_SETTINGS["Database"]), mysqli_errno($SYSTEM_SETTINGS["Database"]));
                                }                                    
                                    
                            } catch( Exception $e ) {
                                mysqli_rollback($SYSTEM_SETTINGS["Database"]);
                                $response['success'] = FALSE;
                                $response['errormessage'] = $e->getMessage();
                                $response['errorcode'] = $e->getCode();                            
                                AddToSysLog(array('EntryKind' => 'error', 'IsSystem' => TRUE, 'Expiry' => $SYSTEM_SETTINGS['ExpiryPolicies']['LogErrors'], 'Caption' => 'Person Record', 'Description' => 'Unable to save changes: '.$response['errormessage'].' ('.$response['errorcode'].')' ));
                            }
                        }
                        break;
                    case 'saveSimpleField':
                        if (CheckRequiredParams(array('_TableName' => FALSE, '_FieldName' => FALSE, '_idFieldName' => FALSE, '_idValue' => FALSE), $_POST)) {
                            $tablename = VarnameStr($_POST['_TableName']);
                            $fieldname = VarnameStr($_POST['_FieldName']);
                            $caption = (!empty($_POST['_Caption']) ? PunctuatedTextStr($_POST['_Caption']) : $fieldname);
                            $idfieldname = VarnameStr($_POST['_idFieldName']);
                            $idfieldvalue = (is_numeric($_POST['_idValue']) ? intval($_POST['_idValue']) : "'".IdentifierStr($_POST['_idValue'])."'");
                            $fieldtype = (!empty($_POST['_FieldType']) ? VarnameStr($_POST['_FieldType']) : 'string');
                            $setSQL = SimpleUpdateSQL(
                                $tablename,
                                array(
                                    'fieldname' => $idfieldname,
                                    'fieldtype' => (is_integer($idfieldvalue) ? 'integer' : 'string'),
                                    'value' => $idfieldvalue,                                
                                ),
                                array(
                                    $fieldname => $fieldtype,
                                )
                            );
                            $response = ExecuteSQL($setSQL);
                            if($response['success']) {
                                AddHistory(array(
                                    'type' => 'edit', 'description' => $caption.' modified',
                                    $idfieldname => $idfieldvalue,
                                ), $response['_affectedrows']);
                            }
                        }
                        break;
                    case 'savePersonProfile':
                        if (CheckRequiredParams(array('PersonID' => FALSE), $_POST)) {
                            $personid = intval($_POST['PersonID']);
                            $setSQL = SimpleUpdateSQL(
                                'tblperson',
                                array(
                                    'fieldname' => 'PersonID',
                                    'fieldtype' => 'integer',
                                    'value' => $personid,
                                ),
                                array(
                                    'PlaceOfStudyID' => array(
                                        'fieldtype' => 'integer',
                                        'emptyasnull' => TRUE,
                                    ),
                                    'StudyInstitution' => 'string',
                                    'StudyDepartment' => 'string',
                                    'PlaceOfWorkID' => array(
                                        'fieldtype' => 'integer',
                                        'emptyasnull' => TRUE,
                                    ),
                                    'WorkRoleID' => array(
                                        'fieldtype' => 'integer',
                                        'emptyasnull' => TRUE,
                                    ),
                                    'EmployerName' => 'string',
                                    'JobTitle' => 'string',
                                    'Keywords' => array(
                                        'fieldtype' => 'memo',
                                        'emptyasnull' => TRUE,
                                    ),
                                )
                            );
                            $response = ExecSQLandHistory($setSQL, 'PersonID', 'Profile details updated', $_POST);
                            //Subjects
                            $sql = "DELETE FROM tblpersontosubject WHERE PersonID = {$personid}";
                            mysqli_query($SYSTEM_SETTINGS["Database"], $sql);                            
                            if(!empty($_POST['SubjectIDs'])) {
                                $sql = "INSERT INTO tblpersontosubject (PersonID, SubjectID) VALUES";
                                $count = 0;
                                foreach($_POST['SubjectIDs'] AS $subjectid) {
                                    $sql .= ($count > 0 ? "," : "")." ({$personid}, ".intval($subjectid).")";
                                    $count++;
                                }
                                $response = ExecSQLandHistory($sql, 'PersonID', 'Subjects updated', $_POST);
                            }
                        }
                        break;
                    case 'savePersonMSDirectory':
//                        file_put_contents("D:\\temp\\post.txt", print_r($_POST, true));
                        if (CheckRequiredParams(array('PersonID' => FALSE), $_POST)) {
                            $personid = intval($_POST['PersonID']);
                            $wscategoryid = intval($_POST['WSCategoryID']);
                            $sql = "DELETE FROM tblpersontodirectory WHERE PersonID = {$personid}";
                            $response = ExecuteSQL($sql);
                            if(!empty($_POST['Elements'])) {
                                if(!in_array('name', $_POST['Elements'])) {
                                    $_POST['Elements'][] = 'name';
                                }
                                $elements = array_unique($_POST['Elements']);
                                $sql = "INSERT INTO tblpersontodirectory (PersonID, WSCategoryID, ShowElement) VALUES";
                                $count = 0;
                                foreach($elements AS $element) {
                                    $sql .= ($count > 0 ? "," : "")." ({$personid}, {$wscategoryid}, '".IdentifierStr($element)."')";
                                    $count++;
                                }
                                $response = ExecSQLandHistory($sql, 'PersonID', 'Membership directory settings updated', $_POST);
                            }
                        }
                        break;
                    case 'savePersonActionGroupItems':
                        if (CheckRequiredParams(array('PersonID' => FALSE, 'ActionGroupID' => FALSE), $_POST)) {
                            $personid = intval($_POST['PersonID']);
                            $actiongroupid = intval($_POST['ActionGroupID']);
                            if(empty($_POST['ActionGroupName'])) {
                                $sql = "SELECT ActionGroupName FROM tblactiongroup WHERE ActionGroupID = {$actiongroupid}";
                                $actiongroupname = SingleValue($SYSTEM_SETTINGS['Database'], $sql);
                            } else {
                                $actiongroupname = $_POST['ActionGroupName'];
                            }
                            $sql = 
                            "DELETE tblpersontoactiongroupitem 
                             FROM tblpersontoactiongroupitem
                             LEFT JOIN tblactiongroupitem ON tblactiongroupitem.ActionGroupItemID = tblpersontoactiongroupitem.ActionGroupItemID
                             WHERE (tblpersontoactiongroupitem.PersonID = {$personid}) AND (tblactiongroupitem.ActionGroupID = {$actiongroupid})";
                            $response = ExecuteSQL($sql);
                            if(!empty($_POST['ActionGroupItemID'])) {
                                $items = array_unique($_POST['ActionGroupItemID']);
                                $sql = "INSERT INTO tblpersontoactiongroupitem (PersonID, ActionGroupItemID) VALUES";
                                $count = 0;
                                foreach($items AS $item) {
                                    $sql .= ($count > 0 ? "," : "")." ({$personid}, {$item})";
                                    $count++;
                                }
                                $response = ExecuteSQL($sql);
                            }
                            AddHistory(array(
                                'type' => 'edit',
                                'description' => "{$actiongroupname} settings updated",
                                'PersonID' => $_POST['PersonID']
                            ));                                
                        }
                        break;
                    case 'saveOrganisationGeneral':
                        if (CheckRequiredParams(array('OrganisationID' => FALSE), $_POST)) {
                            $organisationid = intval($_POST['OrganisationID']);
                            $ORGANISATION = new crmOrganisation($SYSTEM_SETTINGS['Database'], $organisationid);
                            $general = $ORGANISATION->GetRecord('general');
                            if($ORGANISATION->Found) {
                                //Special handling for marking dissolving orgs
                                if(empty($general['Dissolved']) && !empty($_POST['Dissolved']) && !empty($_POST['ConfirmedDissolved'])) {
                                
                                }
                                $setSQL = SimpleUpdateSQL(
                                    'tblorganisation',
                                    array(
                                        'fieldname' => 'OrganisationID',
                                        'fieldtype' => 'integer',
                                        'value' => $organisationid,
                                    ),
                                    array(
                                        'Name' => 'string',
                                        'Ringgold' => array(
                                            'fieldtype' => 'string',
                                            'emptyasnull' => TRUE,
                                        ),
                                        'VATNumber' => array(
                                            'fieldtype' => 'string',
                                            'emptyasnull' => TRUE,
                                        ),
                                        'CharityReg' => array(
                                            'fieldtype' => 'string',
                                            'emptyasnull' => TRUE,
                                        ),
                                    )
                                );
                                $response = ExecSQLandHistory($setSQL, 'OrganisationID', 'General details updated', $_POST);
                            } else {
                                $response['errormessage'] = "The record could not be found.";
                            }
                        }
                        break;
                    case 'saveaddress':
                        if(!empty($_POST['AddressID'])) {
                            $setSQL = new stmtSQL('UPDATE', 'tbladdress', $SYSTEM_SETTINGS["Database"]);
                            $setSQL->addWhere('AddressID', 'integer', $_POST['AddressID']);
                        } else {
                            $setSQL = new stmtSQL('INSERT', 'tbladdress', $SYSTEM_SETTINGS["Database"]);
                        }
                        foreach(array('Lines' => 'memo', 'Postcode' => 'string', 'Town' => 'string', 'County' => 'string', 'Region' => 'string', 'ISO3166' => 'string') AS $fieldname => $fieldtype) {
                            if(isset($_POST[$fieldname])) {
                                $setSQL->addField($fieldname, $fieldtype, $_POST[$fieldname]);        
                            }
                        }
                        $response = ExecuteSQL($setSQL);
                        if($response['success']) {
                            if(empty($_POST['AddressID'])) {
                                $newaddressid = mysqli_insert_id( $SYSTEM_SETTINGS["Database"]);
                                foreach(array('PersonID' => 'tbladdresstoperson', 'OrganisationID' => 'tbladdresstoorganisation') AS $fieldname => $tablename) {
                                    if(isset($_POST[$fieldname])) {
                                        $id = intval($_POST[$fieldname]);
                                        $sql = "INSERT INTO {$tablename} (AddressID, {$fieldname}, AddressType) VALUES ({$newaddressid}, {$id}, '".IdentifierStr($_POST['AddressType'])."')";
                                        mysqli_query($SYSTEM_SETTINGS["Database"], $sql);
                                    }
                                }
                            } else {
                                foreach(array('AddressToPersonID' => 'tbladdresstoperson', 'AddressToOrganisationID' => 'tbladdresstoorganisation') AS $fieldname => $tablename) {
                                    if(isset($_POST[$fieldname])) {
                                        $sql = "UPDATE {$tablename} SET AddressType = '".IdentifierStr($_POST['AddressType'])."' WHERE {$fieldname} = ".intval($_POST[$fieldname]);
                                        mysqli_query($SYSTEM_SETTINGS["Database"], $sql);
                                    }
                                }
                            }
                            AddHistory(array(
                                'type' => 'edit', 'description' => 'Postal address '.(empty($_POST['AddressID']) ? 'added' : 'updated'),
                                'PersonID' => (!empty($_POST['PersonID']) ? intval($_POST['PersonID']) : null),
                                'OrganisationID' => (!empty($_POST['OrganisationID']) ? intval($_POST['OrganisationID']) : null),
                            ), $response['_affectedrows']);
                        }                    
                        break;
                    case 'saveworkflowitem':
                        if(!empty($_POST['WorkflowItemID'])) {
                            $workflowitemid = intval($_POST['WorkflowItemID']);
                            $sql = "DELETE FROM tblworkflowitemtocategory WHERE WorkflowItemID = {$workflowitemid}";
                            mysqli_query($SYSTEM_SETTINGS["Database"], $sql);
                            $setSQL = new stmtSQL('UPDATE', 'tblworkflowitem', $SYSTEM_SETTINGS["Database"]);
                            $setSQL->addWhere('WorkflowItemID', 'integer', $workflowitemid);
                        } else {
                            $setSQL = new stmtSQL('INSERT', 'tblworkflowitem', $SYSTEM_SETTINGS["Database"]);
                            $setSQL->addFieldStmt('Created', 'UTC_TIMESTAMP()');
                        }
                        $setSQL->addFieldStmt('LastModified', 'UTC_TIMESTAMP()');
                        $setSQL->addField('Priority', 'integer', $_POST['Priority']);
                        $setSQL->addField('PersonID', 'integer', $_POST['AssignedID'], null, TRUE);
                        if(!empty($_POST['AssignedID'])) {
                            $setSQL->addFieldStmt('LastAssignment', 'UTC_TIMESTAMP()');
                        }
                        $response = ExecuteSQL($setSQL);
                        if($response['success']) {
                            if(empty($workflowitemid)) {
                                $workflowitemid = mysqli_insert_id( $SYSTEM_SETTINGS["Database"]);
                            }
                            $Result['workflowitemid'] = $workflowitemid;
                            if(!empty($_POST['Categories'])) {
                                $sql = "INSERT INTO tblworkflowitemtocategory (WorkflowItemID, WSCategoryID) VALUES ";
                                $count = 0;
                                foreach($_POST['Categories'] AS $categoryid) {
                                    $sql .= ($count == 0 ? "" : ", ")."({$workflowitemid}, ".intval($categoryid).")";
                                    $count++;
                                }
                                $response = ExecuteSQL($sql);
                            }
                            foreach(array('PersonID' => 'tblworkflowitemtoperson', 'OrganisationID' => 'tblworkflowitemtoorganisation') AS $fieldname => $tablename) {
                                if(isset($_POST[$fieldname])) {
                                    $id = intval($_POST[$fieldname]);
                                    $response[strtolower($fieldname)] = $id;
                                    if(empty($_POST['WorkflowItemID'])) {
                                        $sql = "INSERT INTO {$tablename} (WorkflowItemID, {$fieldname}) VALUES ({$workflowitemid}, {$id})";
                                        mysqli_query($SYSTEM_SETTINGS["Database"], $sql);                                    
                                    }
                                }
                            }
                            AddHistory(array(
                                'type' => 'edit', 'description' => (empty($_POST['WorkflowItemID']) ? 'Added to workflow' : 'Workflow item updated'),
                                'PersonID' => (!empty($_POST['PersonID']) ? intval($_POST['PersonID']) : null),
                                'OrganisationID' => (!empty($_POST['OrganisationID']) ? intval($_POST['OrganisationID']) : null),
                            ), $response['_affectedrows']);
                        }
                        break;
                    case 'saverenewal':
                        if (CheckRequiredParams(array('PersonID' => FALSE), $_POST)) {
                            try {
                                if(StartTransaction($SYSTEM_SETTINGS["Database"])) {
                                    $personid = intval($_POST['PersonID']);
                                    $PERSON = new crmPerson('eventsPerson', $SYSTEM_SETTINGS['Database'], $personid, $SYSTEM_SETTINGS["Membership"]);
                                    $personal = $PERSON->GetRecord();
                                    $cancelRenewal = FALSE;
                                    $personSQL = null;
                                    //deal with renewal date and currency first
                                    if($personal['MSNextRenewal'] <> $_POST['MSNextRenewal']) {
                                        $personSQL = new stmtSQL('UPDATE', 'tblperson', $SYSTEM_SETTINGS["Database"]);
                                        $personSQL->addWhere('PersonID', 'integer', $_POST['PersonID']);
                                        $personSQL->addField('MSNextRenewal', 'date', $_POST['MSNextRenewal']);
                                        AddHistory(array(
                                            'type' => 'edit', 'description' => 'MS Renewal date changed to '.$_POST['MSNextRenewal'],
                                            'PersonID' => $PERSON->PersonID
                                        ));
                                    }
                                    $changeoffreestatus = ((empty($personal['MSFree']) && (!empty($_POST['MSFree']))) || (!empty($personal['MSFree']) && (empty($_POST['MSFree']))) ? TRUE : FALSE);
                                    //Should we cancel the pending renewal?
                                    if (strtotime($_POST['MSNextRenewal'].' -'.$SYSTEM_SETTINGS['Membership']['RenewalCycleStart'].' DAY') > time()) {
                                        //Yes, cancel it if there are no changes to grade etc
                                        if(($personal['MSGradeID'] == $_POST['MSGradeID']) && (empty($_POST['ISO4217']) || ($_POST['ISO4217'] == $personal['ISO4217'])) && !$changeoffreestatus) {
                                            $cancelRenewal = TRUE;
                                        }
                                    }
                                    $renewal = $PERSON->RenewalSettings();
                                    if(!empty($_POST['ISO4217']) && ($renewal['ISO4217'] <> $_POST['ISO4217'])) {
                                        if(is_null($personSQL)) {
                                            $personSQL = new stmtSQL('UPDATE', 'tblperson', $SYSTEM_SETTINGS["Database"]);
                                            $personSQL->addWhere('PersonID', 'integer', $_POST['PersonID']);
                                        }
                                        $overrideCurrency = IdentifierStr($_POST['ISO4217']);
                                        $personSQL->addField('ISO4217', 'string', $overrideCurrency);
                                        AddHistory(array(
                                            'type' => 'edit', 'description' => 'Renewal currency changed to '.$overrideCurrency,
                                            'PersonID' => $PERSON->PersonID
                                        ));
                                    }
                                    if(!is_null($personSQL)) {
                                        $response = ExecuteSQL($personSQL);
                                        if(!$response['success']) {
                                            throw new crmException('Unable to process renewal settings: '.$response['errormessage'], $response['errorcode']);
                                        }
                                    }
                                    $discountchanged = ($renewal['DiscountID'] <> (empty($_POST['DiscountID']) ? null : intval($_POST['DiscountID'])));
                                    if($cancelRenewal) {
                                        //The renewal and corresponding transaction need to be cancelled
                                        if(!empty($_POST['RenewalID'])) {
                                            $sql = "DELETE FROM tblrenewal WHERE RenewalID = ".intval($_POST['RenewalID']);
                                            $response = ExecuteSQL($sql);
                                            if($response['success']) {
                                                if(!empty($_POST['InvoiceItemID'])) {
                                                    CancelInvoiceItem($_POST['InvoiceItemID']);
                                                }
                                                AddHistory(array(
                                                    'type' => 'delete', 'description' => 'MS Renewal transaction has been cancelled',
                                                    'PersonID' => $PERSON->PersonID
                                                ), $response['_affectedrows']);
                                            } else {
                                                throw new crmException('Unable to delete renewal transaction: '.$response['errormessage'], $response['errorcode']);
                                            }
                                        } else {
                                            $response['success'] = TRUE;
                                        }
                                        //A discount has been signalled - store it against the record if it does not as yet exist, so it will be picked up by the next renewal cycle
                                        if(!empty($_POST['DiscountID'])) {
                                            $sql =
                                            "INSERT INTO tbldiscounttoperson (DiscountID, PersonID, RefCount)
                                             SELECT tbldiscount.DiscountID, {$PERSON->PersonID}, tbldiscount.RefCount
                                             FROM tbldiscount
                                             LEFT JOIN tbldiscounttoperson ON (tbldiscounttoperson.PersonID = {$PERSON->PersonID}) AND (tbldiscounttoperson.DiscountID = tbldiscount.DiscountID) AND (tbldiscounttoperson.RefCount > 0) 
                                             WHERE (tbldiscount.DiscountID = ".intval($_POST['DiscountID']).") AND (tbldiscounttoperson.DiscountToPersonID IS NULL)";
//                                            mysqli_query($SYSTEM_SETTINGS["Database"], $sql);
                                            $response = ExecuteSQL($sql);
                                            if($response['success'] && ($response['_affectedrows'] > 0)) {
                                                $discount = new crmDiscountCode($SYSTEM_SETTINGS["Database"], $_POST['DiscountID']);
                                                AddHistory(array(
                                                    'type' => 'edit', 'description' => 'Discount Code attached (via Renewal Settings): '.$discount->DiscountCode.', '.$discount->Description,
                                                    'PersonID' => $PERSON->PersonID,
                                                ));
                                            }
                                        }
                                    } else {
                                        //We keep the renewal; but the pricing may have to change
                                        if(!empty($_POST['RenewalID'])) {
                                            $renewalid = intval($_POST['RenewalID']);
                                            $setSQL = new stmtSQL('UPDATE', 'tblrenewal', $SYSTEM_SETTINGS["Database"]);
                                            $setSQL->addWhere('RenewalID', 'integer', $renewalid);
                                        } else {
                                            $setSQL = new stmtSQL('INSERT', 'tblrenewal', $SYSTEM_SETTINGS["Database"]);
                                            $setSQL->addField('PersonID', 'integer', $_POST['PersonID']);
                                            $setSQL->addField('WSCategoryID', 'integer', $_POST['WSCategoryID']);
                                            $setSQL->addFieldStmt('Created', 'UTC_TIMESTAMP()');
                                        }
                                        $setSQL->addFieldStmt('LastModified', 'UTC_TIMESTAMP()');
                                        $setSQL->addField('MSGradeID', 'integer', $_POST['MSGradeID']);
                                        $setSQL->addField('RenewFlags', 'set', (empty($_POST['MSFree']) ? '' : 'free'));
                                        if($discountchanged || !empty($_POST['MSFree'])) {
                                            if(!empty($renewal['DiscountID'])) {
                                                //Remove the discount code previously attached if there is a change or if the renewal is free
                                                $sql = "DELETE FROM tbldiscounttoperson WHERE (tbldiscounttoperson.DiscountID = {$renewal['DiscountID']}) AND (tbldiscounttoperson.PersonID = {$PERSON->PersonID})";
                                                mysqli_query($SYSTEM_SETTINGS['Database'], $sql);
                                            }
                                        }
                                        if(empty($_POST['MSFree'])) {
                                            if($discountchanged) {
                                                //Attach the new code
                                                if(!empty($_POST['DiscountID'])) {
                                                    $sql =
                                                    "INSERT INTO tbldiscounttoperson (DiscountID, PersonID, RefCount)
                                                     SELECT tbldiscount.DiscountID, {$PERSON->PersonID}, tbldiscount.RefCount
                                                     FROM tbldiscount
                                                     WHERE tbldiscount.DiscountID = ".intval($_POST['DiscountID']);
                                                    mysqli_query($SYSTEM_SETTINGS["Database"], $sql);
                                                }
                                            }
                                            $setSQL->addField('DiscountID', 'integer', $_POST['DiscountID'], null, TRUE);
                                        } else {
                                            $setSQL->addNullField('DiscountID');                                            
                                        }
                                        $response = ExecuteSQL($setSQL);
                                        //file_put_contents("d:\\temp\\sql.txt", $setSQL->SQL());
                                        if($response['success']) {
                                            if($setSQL->IsInsert) {
                                                $renewalid = mysqli_insert_id( $SYSTEM_SETTINGS["Database"]);
                                            }
                                            $renewal = $PERSON->RenewalSettings();
                                            if($discountchanged) {
                                                AddHistory(array('type' => 'edit', 'description' => 'Discount '.(empty($renewal['DiscountID']) ? 'removed from ' : 'code '.$renewal['DiscountCode'].' applied to ').$renewal['CategoryName'].' renewal', 'PersonID' => $PERSON->PersonID));
                                            }
                                            if(!empty($overrideCurrency)) {
                                                $renewal['ISO4217'] = $overrideCurrency;
                                            }
                                            $msfees = new crmMSFees($SYSTEM_SETTINGS['Database'], $SYSTEM_SETTINGS['Membership']['GradeCaption']);
/*                                            $discount = LocateDiscount(array(
                                                'PersonID' => $PERSON->PersonID,
                                                'CategorySelector' => 'members',
                                                'Mnemonic' => 'ms_renewal',
                                            ), FALSE);*/
                                            $params = array(
                                                'ISO4217' => $renewal['ISO4217'],
                                                'MSGradeID' => $renewal['MSGradeID'],
                                                'ISO3166' => $renewal['ISO3166'],
                                                'ForDate' => $renewal['MSNextRenewal'],
                                                'IsDD' => (!empty($renewal['DDIID'])),
                                                'Free' => $renewal['MSFree'],
                                                'DiscountID' => (!empty($renewal['DiscountID']) ? $renewal['DiscountID'] : null),
                                            );
                                            $fee = $msfees->CalculateFee($params);
                                            if(!$fee->HasError) {
                                                //Existing transaction for the same amount?
                                                if(!empty($_POST['InvoiceItemID'])) {
                                                    $data = InvoiceItemToInvoice($_POST['InvoiceItemID']);
                                                    $invoice = new crmInvoice('eventsFinance', $SYSTEM_SETTINGS['Database'], $data['InvoiceID'], InvoiceSettings());
                                                    $invoiceitem = $invoice->InvoiceItem($_POST['InvoiceItemID']);
                                                    if(($invoice->ISO4217 == $renewal['ISO4217']) && ($invoiceitem['ItemNet'] == $fee->Net) && ($invoiceitem['ItemVATRate'] == $fee->VATRate) && !$discountchanged) {
                                                        //Same currency and price
                                                        $setSQL = new stmtSQL('UPDATE', 'tblinvoiceitem', $SYSTEM_SETTINGS["Database"]);
                                                        $setSQL->addWhere('InvoiceItemID', 'integer', $_POST['InvoiceItemID']);
                                                        $setSQL->addField('LinkedID', 'integer', $renewalid);
                                                        if($invoice->Invoice['Draft']) {
                                                            $setSQL->addField('Description', 'string', $invoiceitem['TypeName'].', '.$renewal['GradeCaption']);
                                                            $setSQL->addField('Explain', 'json', json_encode($fee->Explanation));
                                                            $setSQL->addField('ItemDate', 'date', $renewal['MSNextRenewal']);
                                                        }
                                                        $response = ExecuteSQL($setSQL);
                                                        if($response['success']) {
                                                            
                                                        } else {
                                                            throw new crmException('Unable to adjust invoice item: '.$response['errormessage'], $response['errorcode']);
                                                        }
                                                    } else {
                                                        //Different pricing and/or currency
                                                        CancelInvoiceItem($_POST['InvoiceItemID']);
                                                        if($fee->Net <> 0) {
                                                            $invoice = GetProForma(array('ISO4217' => $renewal['ISO4217'], 'PersonID' => $PERSON->PersonID));
                                                            $invoice->NewItem(array(
                                                                'Mnemonic' => 'ms_renewal',
                                                                'LinkedID' => $renewalid,
                                                                'ItemNet' => $fee->Net,
                                                                'ItemVATRate' => $fee->VATRate,
                                                                'DiscountID' => (!empty($renewal['DiscountID']) ? $renewal['DiscountID'] : null),
                                                                'ItemDate' => $renewal['MSNextRenewal'],
//                                                                'Description' => $invoiceitem['TypeName'].', '.$renewal['GradeCaption'],
                                                                'Explain' => json_encode($fee->Explanation),
                                                            ), $renewal);
                                                        }
                                                    }
                                                } else {
                                                    //No transaction yet
                                                    if($fee->Net <> 0) {
                                                        $invoice = GetProForma(array('ISO4217' => $renewal['ISO4217'], 'PersonID' => $PERSON->PersonID));
                                                        $invoice->NewItem(array(
                                                            'Mnemonic' => 'ms_renewal',
                                                            'LinkedID' => $renewalid,
                                                            'ItemNet' => $fee->Net,
                                                            'ItemVATRate' => $fee->VATRate,
                                                            'DiscountID' => (!empty($renewal['DiscountID']) ? $renewal['DiscountID'] : null),
                                                            'ItemDate' => $renewal['MSNextRenewal'],
//                                                            'Description' => '%TypeName%, '.$renewal['GradeCaption'],
                                                            'Explain' => json_encode($fee->Explanation),
                                                        ), $renewal);
                                                    }
                                                }
                                            } else {
                                                throw new crmException('Unable to calculate renewal fee', 1);
                                            }
                                        } else {
                                            throw new crmException('Unable to create renewal record: '.$response['errormessage'], $response['errorcode']);
                                        }
                                    }
                                    mysqli_commit($SYSTEM_SETTINGS["Database"]);
                                } else {
                                    throw new crmException('Unable to process renewal settings: '.mysqli_error($SYSTEM_SETTINGS["Database"]), mysqli_errno($SYSTEM_SETTINGS["Database"]));
                                }
                            } catch( Exception $e ) {
                                mysqli_rollback($SYSTEM_SETTINGS["Database"]);
                                $response['success'] = FALSE;
                                $response['errormessage'] = $e->getMessage();
                                $response['errorcode'] = $e->getCode();                            
                                AddToSysLog(array('EntryKind' => 'error', 'IsSystem' => TRUE, 'Expiry' => $SYSTEM_SETTINGS['ExpiryPolicies']['LogErrors'], 'Caption' => 'Membership', 'Description' => $response['errormessage'].' ['.$response['errorcode'].']', 'Data' => $_POST));
                            }
                        }
                        break;
                    case 'sendemail':
                        if (CheckRequiredParams(array('Subject' => FALSE, 'Body' => 'FALSE'), $_POST)) {
                            $message = array(
                                'Subject' => $_POST['Subject'],
                                'Body' => $_POST['Body']
                            );
                            if(!empty($_POST['From'])) {
                                $apos = strrpos($_POST['From'], ' (');
                                if($apos > 0) {
                                    $name = substr($_POST['From'], 0, $apos);
                                    $email = substr($_POST['From'], $apos+2, strlen($_POST['From'])-$apos-3);
                                    if(!empty($name)) {
                                        $message['FromName'] = $name;
                                    }
                                    if(IsValidEmailAddress($email)) {
                                        $message['FromEmail'] = $email;
                                    }
                                }
                            }
                            foreach(array('To', 'CC', 'BCC') AS $key) {
                                if(!empty($_POST[$key])) {
                                    $message[$key] = explode(',', str_replace(';', ',', $_POST[$key]));
                                }
                            }
                            foreach(array('PersonID', 'OrganisationID') AS $key) {
                                if(!empty($_POST[$key])) {
                                    $message[$key] = $_POST[$key];
                                }
                            }
                            if (AddToEmailQueue($message)) {
                                $response['success'] = TRUE;
                                SaveNote();
                            } else {
                                $response['errormessage'] = 'There was an error while posting the message.';
                            }
                        } else {
                            $response['errormessage'] = 'The email was missing a subject and/or message body';
                        }
                        break;
                    case 'savenote':
                        $response = SaveNote();
/*                        if(!empty($_POST['NoteID'])) {
                            $noteid = intval($_POST['NoteID']);
                            $sql = "DELETE FROM tblnotetowscategory WHERE NoteID = {$noteid}";
                            mysqli_query($SYSTEM_SETTINGS["Database"], $sql);
                            $setSQL = new stmtSQL('UPDATE', 'tblnote', $SYSTEM_SETTINGS["Database"]);
                            $setSQL->addWhere('NoteID', 'integer', $noteid);
                        } else {
                            $setSQL = new stmtSQL('INSERT', 'tblnote', $SYSTEM_SETTINGS["Database"]);
                            $setSQL->addFieldStmt('Created', 'UTC_TIMESTAMP()');
                        }
                        $setSQL->addFieldStmt('LastModified', 'UTC_TIMESTAMP()');
                        $setSQL->addFieldStmt('Expires', (empty($_POST['NoteNoExpiry']) ? 'DATE_ADD(UTC_TIMESTAMP(), INTERVAL '.$SYSTEM_SETTINGS["ExpiryPolicies"]['Notes'].' MONTH)' : "NULL"));
                        $setSQL->addField('NoteText', 'fmttext', $_POST['NoteText']);
                        $setSQL->addField('PersonID', 'integer', $AUTHENTICATION['Person']['PersonID']);
                        $response = ExecuteSQL($setSQL);
                        if($response['success']) {
                            if(empty($noteid)) {
                                $noteid = mysqli_insert_id( $SYSTEM_SETTINGS["Database"]);
                            }
                            AddHistory(array(
                                'type' => 'edit', 'description' => 'Note '.(empty($_POST['NoteID']) ? 'added' : 'updated').': '.TextEllipsis(HTML2Plain($_POST['NoteText']), 60),
                                'PersonID' => (!empty($_POST['PersonID']) ? intval($_POST['PersonID']) : null),
                                'OrganisationID' => (!empty($_POST['OrganisationID']) ? intval($_POST['OrganisationID']) : null),
                                'author' => $AUTHENTICATION['Person']['PersonID'],
                            ), $response['_affectedrows']);
                            if(!empty($_POST['Categories'])) {
                                $sql = "INSERT INTO tblnotetowscategory (NoteID, WSCategoryID) VALUES ";
                                $count = 0;
                                foreach($_POST['Categories'] AS $categoryid) {
                                    $sql .= ($count == 0 ? "" : ", ")."({$noteid}, ".intval($categoryid).")";
                                    $count++;
                                }
                                $response = ExecuteSQL($sql);
                            }
                            foreach(array('PersonID' => 'tblnotetoperson', 'OrganisationID' => 'tblnotetoorganisation') AS $fieldname => $tablename) {
                                if(isset($_POST[$fieldname])) {
                                    $id = intval($_POST[$fieldname]);
                                    $response[strtolower($fieldname)] = $id;
                                    if(empty($_POST['NoteID'])) {
                                        $sql = "INSERT INTO {$tablename} (NoteID, {$fieldname}) VALUES ({$noteid}, {$id})";
                                        mysqli_query($SYSTEM_SETTINGS["Database"], $sql);                                    
                                    }
                                }
                            }
                        }*/
                        break;
                    case 'delnote':
                        $noteid = intval($_POST['NoteID']);
                        $note = new crmNote($SYSTEM_SETTINGS['Database'], $noteid);
//                        $sql = "SELECT NoteText FROM tblnote WHERE NoteID = {$noteid}";
//                        $data = SingleRecord($SYSTEM_SETTINGS['Database'], $sql);
                        $response = SimpleDeleteRecord('tblnote', array('NoteID'), OwnerIDField($note->Note), "Note deleted: ".TextEllipsis(HTML2Plain($note->Note['NoteText']), 60), $note->Note);
                        break;
                    case 'delworkflowitem':
                        $workflowitemid = intval($_POST['WorkflowItemID']);
                        $wfitem = new crmWorkflowItem($SYSTEM_SETTINGS['Database'], $workflowitemid);
                        $response = SimpleDeleteRecord('tblworkflowitem', array('WorkflowItemID'), OwnerIDField($wfitem->WorkflowItem), "Removed from workflow", $wfitem->WorkflowItem);
                        break;
                    case 'deladdress':
                        $response = SimpleDeleteRecord('tbladdress', array('AddressID'), OwnerIDField(), "Postal address deleted");
                        break;
                    case 'savegrade':
                        $response = SimpleSaveRecord('tblmsgrade', 'MSGradeID', null, array(
                            'GradeCaption' => 'string', 'Available' => 'boolean', 'ApplyOnline' => 'boolean', 'AutoElect' => 'boolean', 'IsRetired' => 'boolean',
                            'GraduationFrom' => array('fieldtype' => 'string', 'emptyasnull' => TRUE),
                            'GraduationUntil' => array('fieldtype' => 'string', 'emptyasnull' => TRUE),
                            'DisplayOrder' => array('fieldtype' => 'integer', 'ignorenotset' => TRUE),
                            'ApplComponents' => 'set'
                        ), null);
                        if($response['success']) {
                            AddToSysLog(array('EntryKind' => 'warning', 'Caption' => 'Administration', 'Description' => $SYSTEM_SETTINGS['Membership']['GradeCaption'].(empty($_POST['MSGradeID']) ? ' created' : ' updated').': '.$_POST['GradeCaption'], 'Data' => $_POST));
                        }
                        break;
                    case 'saveplaceofwork':
                        if(isset($_POST['PlaceOfWorkParentID'])) {
                            $sql = "SELECT MAX(PlaceOfWorkOrder) FROM tblplaceofwork WHERE PlaceOfWorkParentID = ".intval($_POST['PlaceOfWorkParentID']);
                            $max = SingleValue($SYSTEM_SETTINGS['Database'], $sql);
                            $setSQL = new stmtSQL('INSERT', 'tblplaceofwork', $SYSTEM_SETTINGS["Database"]);
                            $setSQL->addField('PlaceOfWorkParentID', 'integer', $_POST);
                            $setSQL->addField('PlaceOfWorkDesc', 'string', $_POST);
                            $setSQL->addField('PlaceOfWorkOrder', 'integer', $max+1);
                            $response = ExecuteSQL($setSQL);
                            if($response['success']) {
                                AddToSysLog(array('EntryKind' => 'warning', 'Caption' => 'Administration', 'Description' => 'Child Place of Employment created: '.$_POST['PlaceOfWorkDesc'], 'Data' => $_POST));
                            }
                        } else {
                            if(empty($_POST['PlaceOfWorkID'])) {
                                $sql = "SELECT MAX(PlaceOfWorkOrder) FROM tblplaceofwork WHERE PlaceOfWorkParentID IS NULL";
                                $max = SingleValue($SYSTEM_SETTINGS['Database'], $sql);
                                $setSQL = new stmtSQL('INSERT', 'tblplaceofwork', $SYSTEM_SETTINGS["Database"]);
                                $setSQL->addField('PlaceOfWorkOrder', 'integer', $max+1);
                            } else {
                                $setSQL = new stmtSQL('UPDATE', 'tblplaceofwork', $SYSTEM_SETTINGS["Database"]);
                                $setSQL->addWhere('PlaceOfWorkID', 'integer', $_POST['PlaceOfWorkID']);
                            }
                            $setSQL->addField('PlaceOfWorkDesc', 'string', $_POST);
                            $response = ExecuteSQL($setSQL);
                            if($response['success']) {
                                AddToSysLog(array('EntryKind' => 'warning', 'Caption' => 'Administration', 'Description' => 'Root Place of Employment'.(empty($_POST['PlaceOfWorkID']) ? ' created' : ' updated').': '.$_POST['PlaceOfWorkDesc'], 'Data' => $_POST));
                            }                            
                        }
                        break;
                    case 'saveeditorproperties':
                        $EDITORITEM = editorItemFactory::create($SYSTEM_SETTINGS['Database'], $_POST, $SYSTEM_SETTINGS);
                        if(!empty($EDITORITEM)) {
                            $EDITORITEM->SaveProperties($_POST);
                            $response['success'] = TRUE;
                            $response['errormessage'] = "";
                            $response['errorcode'] = 0;
                            AddToSysLog(array('EntryKind' => 'info', 'Caption' => 'Templates', 'Description' => 'Properties updated: '.$EDITORITEM->Descriptor(), 'Data' => $EDITORITEM->Properties));
                        }
                        break;
                    case 'delstdrecord':
                        if(!empty($_POST['_tablename'])) {
                            $table = VarnameStr($_POST['_tablename']);
                            $sql = "SELECT * FROM {$table} LIMIT 1";
                            $qry = mysqli_query($SYSTEM_SETTINGS['Database'], $sql);
                            if($qry !== FALSE) {
                                $pkfield = null;
                                $data = array();
                                $firsttxtfield = null;
                                $fields = mysqli_fetch_fields($qry);
                                foreach($fields AS $field) {
                                    if(($field->flags & 2) && isset($_POST[$field->name])) {
                                        $pkfield = $field->name;
                                    } else {
                                        $data[$field->name] = $_POST[$field->name];
                                        if(is_null($firsttxtfield) && (($field->type == 253) || ($field->type == 254))) {
                                            $firsttxtfield = $field->name;
                                        }
                                    }
                                }
                                if(!empty($pkfield)) {
                                    $response = SimpleDeleteRecord($table, array($pkfield));
                                    if($response['success']) {
                                        if(!empty($_POST['_title'])) {
                                            AddToSysLog(array('Caption' => 'Administration', 'Description' => $_POST['_title'].' deleted'.(!is_null($firsttxtfield) && isset($_POST[$firsttxtfield]) ? ": ".$_POST[$firsttxtfield] : ""), 'Data' => $data));
                                        }
                                    }
                                }
                            }
                        }
                        break;
                    case 'savestdrecord':
                        if(!empty($_POST['_tablename'])) {
                            $table = VarnameStr($_POST['_tablename']);
                            $sql = "SELECT * FROM {$table} LIMIT 1";
                            $qry = mysqli_query($SYSTEM_SETTINGS['Database'], $sql);
                            if($qry !== FALSE) {
                                $mappings = array();
                                $pkfield = null;
                                $firsttxtfield = null;
                                $data = array();
                                $fields = mysqli_fetch_fields($qry);
                                foreach($fields AS $field) {
                                    if($field->flags & 2) {
                                        $pkfield = $field->name;
                                        if(isset($_POST[$field->name])) {
                                            $data[$field->name] = $_POST[$field->name];
                                        }
                                    } elseif(isset($_POST[$field->name])) {
                                        $data[$field->name] = $_POST[$field->name];
                                        $item = array();
                                        switch($field->type) {
                                            case MYSQLI_TYPE_BIT:
                                            case MYSQLI_TYPE_LONG:
                                            case MYSQLI_TYPE_TINY:
                                            case MYSQLI_TYPE_SHORT:
                                            case MYSQLI_TYPE_INT24:
                                            case MYSQLI_TYPE_LONGLONG:
                                                $item['fieldtype'] = 'integer';
                                                break;
                                            case MYSQLI_TYPE_FLOAT:
                                            case MYSQLI_TYPE_DOUBLE:
                                            case 246:
                                                $item['fieldtype'] = 'float';
                                                break;
                                            case MYSQLI_TYPE_DATETIME:
                                            case MYSQLI_TYPE_TIMESTAMP:
                                                $item['fieldtype'] = 'datetime';
                                                break;
                                            case 253:
                                            case 254:
                                                if(is_null($firsttxtfield)) {
                                                    $firsttxtfield = $field->name;
                                                }
                                            default:
                                                $item['fieldtype'] = 'raw';
                                        }
                                        if(($field->flags & 1) == 0) {
                                            $item['emptyasnull'] = TRUE;
                                        }
                                        $mappings[$field->name] = $item;
                                    }
                                }
                                //file_put_contents("D:\\temp\\save.txt", print_r(array('table' => $table, 'pkfield' => $pkfield, 'mappings' => $mappings), TRUE));
                                $response = SimpleSaveRecord($table, $pkfield, null, $mappings);
                                if(!empty($_POST['_title'])) {
                                    AddToSysLog(array('Caption' => 'Administration', 'Description' => $_POST['_title'].(empty($_POST[$pkfield]) ? ' created' : ' updated').(!is_null($firsttxtfield) && isset($_POST[$firsttxtfield]) ? ": ".$_POST[$firsttxtfield] : ""), 'Data' => $data));
                                }
                            }
                        }
                        break;
                    case 'saveonline':
                        $lti = LinkTableInfo('online');                    
                        $response = SimpleSaveRecord($lti['table'], $lti['linkfield'], OwnerIDField(), array('OnlineID' => 'integer', 'URL' => 'url'), "{CategoryName} {action}: {URL}");
                        break;
                    case 'savephone':
                        $lti = LinkTableInfo('phone');                    
                        $response = SimpleSaveRecord($lti['table'], $lti['linkfield'], OwnerIDField(), array('PhoneTypeID' => 'integer', 'PhoneNo' => 'phone', 'Description' => array('fieldtype' => 'string', 'ignorenotset' => TRUE)), "Phone number {action}: {PhoneNo}");
                        break;
                    case 'saveemail':
                        $sql = 
                        "SELECT tblemail.EmailID, tblemail.PersonID, tblperson.MSNumber,
                                CONCAT_WS(' ', tblperson.Title, tblperson.Firstname, tblperson.Middlenames, tblperson.Lastname) AS `Fullname`
                         FROM tblemail
                         LEFT JOIN tblperson ON tblperson.PersonID = tblemail.PersonID
                         WHERE tblemail.Email = '".mysqli_real_escape_string($SYSTEM_SETTINGS['Database'], $_POST['Email'])."'";
                        $record = SingleRecord($SYSTEM_SETTINGS['Database'], $sql);
                        if(!empty($record)) {
                            $response['errormessage'] = "This email address is already in use ({$record['Fullname']}".(!empty($record['MSNumber']) ? ", ".$record['MSNumber'] : "").").";
                        } else {
                            $OwnerIDField = OwnerIDField();
                            $response = SimpleSaveRecord('tblemail', 'EmailID', $OwnerIDField, array('Email' => 'email'), "Email address {action}: {Email}");
                        }
                        break;
                    case 'savegroup':
                        $sql = "SELECT GroupName FROM tblpersongroup WHERE PersonGroupID = ".intval($_POST['PersonGroupID']);
                        $groupname = SingleValue($SYSTEM_SETTINGS['Database'], $sql);
                        if(!empty($groupname)) {
                            $response = SimpleSaveRecord(
                                'tblpersontopersongroup', 'PersonToPersonGroupID', 'PersonID', 
                                array('PersonGroupID' => 'integer', 'Comment' => 'string'),
                                (empty($_POST['PersonToPersonGroupID']) ? "Added to group: {$groupname}" : "Group entry updated: {$groupname}")
                            );
                        } else {
                            $response['errormessage'] = "The Group entry was not found."; 
                        }
                        break;
                    case 'savepersongrant':
                        $sql = "SELECT Title FROM tblgrant WHERE GrantID = ".intval($_POST['GrantID']);
                        $grantname = SingleValue($SYSTEM_SETTINGS['Database'], $sql);
                        if(!empty($grantname)) {
                            $response = SimpleSaveRecord(
                                'tblpersontogrant', 'PersonToGrantID', 'PersonID', 
                                array('GrantID' => 'integer', 'Awarded' => 'utc', 'Comment' => 'string'),
                                "Grant record {action}: {$grantname} (".gmdate('Y-m-d', strtotime($_POST['Awarded'])).")"
                            );
                        } else {
                            $response['errormessage'] = "The Grant record was not found."; 
                        }
                        break;
                    case 'delpersongrant':
                        $sql = 
                        "SELECT tblgrant.Title, tblpersontogrant.Awarded
                         FROM tblpersontogrant
                         INNER JOIN tblgrant ON tblgrant.GrantID = tblpersontogrant.GrantID
                         WHERE (tblpersontogrant.PersonToGrantID = ".intval($_POST['PersonToGrantID']).") AND (tblpersontogrant.PersonID = ".intval($_POST['PersonID']).")
                        ";
                        $record = SingleRecord($SYSTEM_SETTINGS['Database'], $sql);
                        if(!empty($record)) {
                            $response = SimpleDeleteRecord('tblpersontogrant', array('PersonToGrantID', 'PersonID'), 'PersonID', "Grant record deleted: {$record['Title']} (".date('Y-m-d', strtotime($record['Awarded'].' UTC')).")");
                        } else {
                            $response['errormessage'] = "The Grant record was not found."; 
                        }
                        break;
                    case 'removefromgroup':
                        $sql = 
                        "SELECT tblpersongroup.GroupName
                         FROM  tblpersontopersongroup
                         INNER JOIN tblpersongroup ON tblpersongroup.PersonGroupID = tblpersontopersongroup.PersonGroupID
                         WHERE (tblpersontopersongroup.PersonToPersonGroupID = ".intval($_POST['PersonToPersonGroupID']).") AND (tblpersontopersongroup.PersonID = ".intval($_POST['PersonID']).")";
                        $groupname = SingleValue($SYSTEM_SETTINGS['Database'], $sql);
                        if(!empty($groupname)) {
                            $response = SimpleDeleteRecord('tblpersontopersongroup', array('PersonToPersonGroupID', 'PersonID'), 'PersonID', "Removed from group: {$groupname}");
                        } else {
                            $response['errormessage'] = "The Group entry was not found.";
                        }
                        break;
                    case 'emptygroup':
                        $sql = "SELECT GroupName FROM tblpersongroup WHERE PersonGroupID = ".intval($_POST['PersonGroupID']);
                        $groupname = SingleValue($SYSTEM_SETTINGS['Database'], $sql);
                        if(!empty($groupname)) {
                            $histid = AddHistory(array(
                                'type' => 'delete',
                                'description' => 'Removed from group: '.$groupname,
//                                'PersonGroupID' => intval($_POST['PersonGroupID'])
                            ));
                            $sql = 
                            "INSERT INTO tblhistorytoperson (HistoryID, PersonID) 
                             SELECT {$histid}, PersonID FROM tblpersontopersongroup
                             WHERE tblpersontopersongroup.PersonGroupID = ".intval($_POST['PersonGroupID']);
                            $response = ExecuteSQL($sql);
                            if($response['success']) {
                                $sql = "DELETE FROM tblpersontopersongroup WHERE tblpersontopersongroup.PersonGroupID = ".intval($_POST['PersonGroupID']);
                                $response = ExecuteSQL($sql);
                                if($response['success']) {
                                    AddHistory(array(
                                        'type' => 'delete',
                                        'description' => 'Group emptied',
                                        'PersonGroupID' => intval($_POST['PersonGroupID'])
                                    ));
                                }
                            }
                        } else {
                            $response['errormessage'] = "The Group entry was not found."; 
                        }
                        break;
                    case 'moveplaceofworkup':
                    case 'moveplaceofworkdown':
                        $offset = ($do == 'moveplaceofworkup' ? -1 : 1);
                        $thisorder = intval($_POST['PlaceOfWorkOrder']);
                        $parent = (isset($_POST['PlaceOfWorkParentID']) && is_numeric($_POST['PlaceOfWorkParentID']) ? "= ".intval($_POST['PlaceOfWorkParentID']) : "IS NULL");
                        $sql = 
                        "UPDATE tblplaceofwork SET PlaceOfWorkOrder = {$thisorder} WHERE (PlaceOfWorkOrder = ".($thisorder+$offset).") AND (PlaceOfWorkParentID {$parent});
                         UPDATE tblplaceofwork SET PlaceOfWorkOrder = ".($thisorder+$offset)." WHERE (PlaceOfWorkID = ".$_POST['PlaceOfWorkID'].")";
                        //file_put_contents("D:\\temp\\sql.txt", $sql);
                        MultiQueryExecute($SYSTEM_SETTINGS['Database'], $sql);
                        $Result['success'] = TRUE;
                        $Result['errorcode'] = 0;
                        $Result['errormessage'] = '';
                        break;
                    case 'moveup':
                    case 'movedown':
                        foreach(array('MSGradeID' => 'tblmsgrade') AS $fieldname => $settings) {
                            if(isset($_POST[$fieldname])) {
                                $idfield = $fieldname;
                                $table = (is_array($settings) ? $settings['table'] : $settings);
                                $fieldname = (is_array($settings) && isset($settings['fieldname']) ? $settings['fieldname'] : "DisplayOrder");
                                break;
                            }
                        }
/*                        $fieldname = "DisplayOrder";
                        if(isset($_POST['MSGradeID'])) {
                            $table = "tblmsgrade";
                            $idfield = "MSGradeID";
                        } elseif(isset($_POST['PlaceOfWorkID'])) {
                            $table = "tblplaceofwork";
                            $idfield = "PlaceOfWorkID";
                            $fieldname = "PlaceOfWorkOrder";
                        }*/
                        $offset = ($do == 'moveup' ? -1 : 1);
                        if(!empty($table)) {
                            $thisorder = intval($_POST[$fieldname]); 
                            $sql = 
                            "UPDATE {$table} SET {$fieldname} = {$thisorder} WHERE {$fieldname} = ".($thisorder+$offset).";
                             UPDATE {$table} SET {$fieldname} = ".($thisorder+$offset)." WHERE {$idfield} = ".$_POST[$idfield];
                            MultiQueryExecute($SYSTEM_SETTINGS['Database'], $sql);
                            $Result['success'] = TRUE;
                            $Result['errorcode'] = 0;
                            $Result['errormessage'] = '';
                        }
                        break;
                    case 'delhistory':
                        $response = SimpleDeleteRecord('tblhistory', array('HistoryID'));
                        break;
                    case 'delemail':
                        $response = SimpleDeleteRecord('tblemail', array('EmailID', 'PersonID'), 'PersonID', "Email address deleted: {Email}");
                        break;
                    case 'delcommitteerole':
                        $response = SimpleDeleteRecord('tblcommitteerole', 'CommitteeRoleID');
                        if($response['success']) {
                            AddToSysLog(array('Caption' => 'Administration', 'Description' => $_POST['_title'].(empty($_POST[$pkfield]) ? ' created' : ' updated').(!is_null($firsttxtfield) && isset($_POST[$firsttxtfield]) ? ": ".$_POST[$firsttxtfield] : ""), 'Data' => $data));
                        }
                        break;
                    case 'delonline':
                        $lti = LinkTableInfo('online');                    
                        $response = SimpleDeleteRecord($lti['table'], array($lti['linkfield'], OwnerIDField()), OwnerIDField(), "{CategoryName} deleted: {URL}");
                        break;
                    case 'delphone':
                        $lti = LinkTableInfo('phone');                    
                        $response = SimpleDeleteRecord($lti['table'], array($lti['linkfield'], OwnerIDField()), OwnerIDField(), "Phone number deleted: {PhoneNo}");
                        break;
                    case 'addoptout':
                        if (CheckRequiredParams(array('SubscriptionID' => FALSE, 'PublicationID' => FALSE), $_POST)) {
                            $lti = LinkTableInfo('pubsubscription');
                            $sql = 
                            "UPDATE {$lti['table']} 
                             SET Qty = 0, Complimentary = 0, CustomerReference = NULL, StartDate = NULL, EndDate = NULL, LastReminder = NULL
                             WHERE ({$lti['linkfield']} = ".intval($_POST['SubscriptionID']).") AND ({$lti['owneridfield']} = ".intval($_POST[$lti['owneridfield']]).")";
                            $response = ExecuteSQL($sql);
                            if($response['success'] && ($response['_affectedrows'] > 0)) {
                                $sql = "SELECT Title FROM tblpublication WHERE PublicationID = ".intval($_POST['PublicationID']);
                                $title = SingleValue($SYSTEM_SETTINGS['Database'], $sql);
                                AddHistory(array(
                                    'type' => 'delete',
                                    'description' => 'Opted out: '.$title,
                                    $lti['owneridfield'] => intval($_POST[$lti['owneridfield']])
                                ));
                            }
                        }
                        break;
                    case 'deloptout':
                        if (CheckRequiredParams(array('SubscriptionID' => FALSE, 'PublicationID' => FALSE), $_POST)) {
                            $lti = LinkTableInfo('pubsubscription');
                            $sql = "DELETE FROM {$lti['table']} WHERE ({$lti['linkfield']} = ".intval($_POST['SubscriptionID']).") AND ({$lti['owneridfield']} = ".intval($_POST[$lti['owneridfield']]).")";
                            $response = ExecuteSQL($sql);
                            if($response['success'] && ($response['_affectedrows'] > 0)) {
                                $sql = "SELECT Title FROM tblpublication WHERE PublicationID = ".intval($_POST['PublicationID']);
                                $title = SingleValue($SYSTEM_SETTINGS['Database'], $sql);
                                AddHistory(array(
                                    'type' => 'edit',
                                    'description' => 'Opt-out removed: '.$title,
                                    $lti['owneridfield'] => intval($_POST[$lti['owneridfield']])
                                ));
                                ApplyPublicationRules(array($lti['owneridfield'] => intval($_POST[$lti['owneridfield']])));
                            }
                        }                        
                        break;
                    case 'delsubscription':
                        if (CheckRequiredParams(array('SubscriptionID' => FALSE, 'PublicationID' => FALSE), $_POST)) {
                            $lti = LinkTableInfo('pubsubscription');
                            $sql = "DELETE FROM {$lti['table']} WHERE ({$lti['linkfield']} = ".intval($_POST['SubscriptionID']).") AND ({$lti['owneridfield']} = ".intval($_POST[$lti['owneridfield']]).")";
                            $response = ExecuteSQL($sql);
                            if($response['success'] && ($response['_affectedrows'] > 0)) {
                                $sql = "SELECT Title FROM tblpublication WHERE PublicationID = ".intval($_POST['PublicationID']);
                                $title = SingleValue($SYSTEM_SETTINGS['Database'], $sql);
                                AddHistory(array(
                                    'type' => 'edit',
                                    'description' => 'Subscription deleted: '.$title,
                                    $lti['owneridfield'] => intval($_POST[$lti['owneridfield']])
                                ));
                                ApplyPublicationRules(array($lti['owneridfield'] => intval($_POST[$lti['owneridfield']])));
                            }
                        }                        
                        break;
                    case 'changecommitteedate':
                        if (CheckRequiredParams(array('CommitteeID' => FALSE, 'ForDate' => FALSE), $_POST)) {
                            $response['urlparams'] = "{\"committeeid\":".intval($_POST['CommitteeID']).",\"fordate\":\"".$_POST['ForDate']."\"}";
                            $response['success'] = TRUE;
                        }
                        break;
                    case 'savecommittee':
                        if(!empty($_POST['CommitteeID'])) {
                            $committeeid = intval($_POST['CommitteeID']);
                            $setSQL = new stmtSQL('UPDATE', 'tblcommittee', $SYSTEM_SETTINGS["Database"]);
                            $setSQL->addWhere('CommitteeID', 'integer', $committeeid);
                        } else {
                            $setSQL = new stmtSQL('INSERT', 'tblcommittee', $SYSTEM_SETTINGS["Database"]);
                        }
                        $setSQL->addField('CommitteeName', 'string', $_POST['CommitteeName']);
                        $setSQL->addField('Description', 'text', $_POST['Description']);
                        $response = ExecuteSQL($setSQL);
                        if($response['success'] && ($response['_affectedrows'] > 0)) {
                            if(empty($committeeid)) {
                                $committeeid = mysqli_insert_id($SYSTEM_SETTINGS["Database"]);
                            }
                            AddHistory(array(
                                'type' => 'edit', 'description' => 'Committee '.(empty($_POST['CommitteeID']) ? 'created' : 'updated').': '.TextEllipsis(HTML2Plain($_POST['CommitteeName']), 60),
                                'CommitteeID' => $committeeid,
                                'author' => $AUTHENTICATION['Person']['PersonID'],
                            ));
                        }
                        break;
                    case 'savepersongroup':
                        if(!empty($_POST['PersonGroupID'])) {
                            $persongroupid = intval($_POST['PersonGroupID']);
                            $setSQL = new stmtSQL('UPDATE', 'tblpersongroup', $SYSTEM_SETTINGS["Database"]);
                            $setSQL->addWhere('PersonGroupID', 'integer', $persongroupid);
                        } else {
                            $setSQL = new stmtSQL('INSERT', 'tblpersongroup', $SYSTEM_SETTINGS["Database"]);
                        }
                        $setSQL->addField('GroupName', 'string', $_POST['GroupName']);
                        $setSQL->addField('Expires', 'utc', $_POST['Expires']);
                        $response = ExecuteSQL($setSQL);
                        if($response['success'] && ($response['_affectedrows'] > 0)) {
                            if(empty($persongroupid)) {
                                $persongroupid = mysqli_insert_id($SYSTEM_SETTINGS["Database"]);
                            }
                            AddHistory(array(
                                'type' => 'edit', 'description' => 'Group '.(empty($_POST['PersonGroupID']) ? 'added' : 'updated').': '.TextEllipsis(HTML2Plain($_POST['GroupName']), 60),
                                'PersonGroupID' => $persongroupid,
                                'author' => $AUTHENTICATION['Person']['PersonID'],
                            ));
                        }
                        break;
                    case 'savecommitteeitem':
                        if(!empty($_POST['PersonToCommitteeID'])) {
                            $persontocommitteeid = intval($_POST['PersonToCommitteeID']);
                            $setSQL = new stmtSQL('UPDATE', 'tblpersontocommittee', $SYSTEM_SETTINGS["Database"]);
                            $setSQL->addWhere('PersonToCommitteeID', 'integer', $persontocommitteeid);
                        } else {
                            $setSQL = new stmtSQL('INSERT', 'tblpersontocommittee', $SYSTEM_SETTINGS["Database"]);
                            $setSQL->addField('CommitteeID', 'integer', $_POST['CommitteeID']);
                            $setSQL->addField('PersonID', 'integer', $_POST['PersonID']);
                        }
                        if(!empty($_POST['CommitteeRoleID'])) {
                            $setSQL->addField('CommitteeRoleID', 'integer', $_POST['CommitteeRoleID']);
                            $setSQL->addNullField('Role');
                        } else {
                            $setSQL->addNullField('CommitteeRoleID');
                            $setSQL->addField('Role', 'string', $_POST['Role']);
                        }
                        $setSQL->addField('StartDate', 'utc', $_POST['StartDate']);
                        $setSQL->addField('EndDate', 'utc', $_POST['EndDate']);
                        $response = ExecuteSQL($setSQL);
                        if($response['success'] && ($response['_affectedrows'] > 0)) {
                            $sql = "SELECT CommitteeID, CommitteeName FROM tblcommittee WHERE CommitteeID = ".intval($_POST['CommitteeID']);
                            $data = SingleRecord($SYSTEM_SETTINGS['Database'], $sql);
                            AddHistory(array(
                                'type' => 'edit', 'description' => 'Committee entry '.(empty($_POST['PersonToCommitteeID']) ? 'added' : 'updated').': '.TextEllipsis($data['CommitteeName'], 60),
                                'CommitteeID' => $data['CommitteeID'],
                                'PersonID' => $_POST['PersonID'],
                                'author' => $AUTHENTICATION['Person']['PersonID'],
                            ));
                        }
                        break;
                    case 'delcommitteeitem':
                        $data = GetCommitteeEntry($_POST['PersonToCommitteeID']);
                        if(!empty($data)) {
                            $sql = "DELETE FROM tblpersontocommittee WHERE PersonToCommitteeID = ".$data['PersonToCommitteeID'];
                            $response = ExecuteSQL($sql);
                            if($response['success'] && ($response['_affectedrows'] > 0)) {
                                AddHistory(array(
                                    'type' => 'delete',
                                    'description' => 'Committee entry deleted: '.TextEllipsis($data['RoleText'].', '.$data['CommitteeName'], 60),
                                    'PersonID' => $data['PersonID'],
                                    'CommitteeID' => $data['CommitteeID'],
                                ));
                            }
                        }
                        break;
                    case 'endcommitteeterm':
                        $data = GetCommitteeEntry($_POST['PersonToCommitteeID']);
                        if(!empty($data)) {
                            $setSQL = new stmtSQL('UPDATE', 'tblpersontocommittee', $SYSTEM_SETTINGS["Database"]);
                            $setSQL->addWhere('PersonToCommitteeID', 'integer', $data['PersonToCommitteeID']);
                            $setSQL->addField('EndDate', 'utc', $_POST['EndDate']);
                            $response = ExecuteSQL($setSQL);
                            if($response['success'] && ($response['_affectedrows'] > 0)) {
                                AddHistory(array(
                                    'type' => 'edit',
                                    'description' => 'Term updated: '.TextEllipsis($data['RoleText'].', '.$data['CommitteeName'], 60),
                                    'PersonID' => $data['PersonID'],
                                    'CommitteeID' => $data['CommitteeID'],
                                ));
                            }
                        }                    
                        break;
                    case 'changecommitteerole':
                        try {
                            if(StartTransaction($SYSTEM_SETTINGS["Database"])) {
                                $data = GetCommitteeEntry($_POST['PersonToCommitteeID']);
                                if(!empty($data)) {
                                    $setSQL = new stmtSQL('UPDATE', 'tblpersontocommittee', $SYSTEM_SETTINGS["Database"]);
                                    $setSQL->addWhere('PersonToCommitteeID', 'integer', $data['PersonToCommitteeID']);
                                    $setSQL->addFieldStmt('EndDate', "DATE_SUB('".gmdate('Y-m-d H:i:s', strtotime($_POST['StartDate'].' UTC'))."', INTERVAL 1 SECOND)");
                                    $response = ExecuteSQL($setSQL);
                                    if($response['success']) {
                                        $setSQL = new stmtSQL('INSERT', 'tblpersontocommittee', $SYSTEM_SETTINGS["Database"]);
                                        $setSQL->addField('CommitteeID', 'integer', $data['CommitteeID']);
                                        $setSQL->addField('PersonID', 'integer', $data['PersonID']);
                                        if(!empty($_POST['CommitteeRoleID'])) {
                                            $setSQL->addField('CommitteeRoleID', 'integer', $_POST['CommitteeRoleID']);
                                            $setSQL->addNullField('Role');
                                        } else {
                                            $setSQL->addNullField('CommitteeRoleID');
                                            $setSQL->addField('Role', 'string', $_POST['Role']);
                                        }
                                        $setSQL->addField('StartDate', 'utc', $_POST['StartDate']);
                                        $setSQL->addField('EndDate', 'utc', $_POST['EndDate']);
                                        $response = ExecuteSQL($setSQL);
                                        if($response['success']) {
                                            AddHistory(array(
                                                'type' => 'edit', 'description' => 'Committee role change: '.TextEllipsis($data['RoleText'].', '.$data['CommitteeName'], 60),
                                                'CommitteeID' => $data['CommitteeID'],
                                                'PersonID' => $data['PersonID'],
                                                'author' => $AUTHENTICATION['Person']['PersonID'],
                                            ));
                                        } else {
                                            throw new crmException('Unable to create new committee entry: '.mysqli_error($SYSTEM_SETTINGS["Database"]), mysqli_errno($SYSTEM_SETTINGS["Database"]));
                                        }
                                    } else {
                                        throw new crmException('Unable to close current committee entry: '.mysqli_error($SYSTEM_SETTINGS["Database"]), mysqli_errno($SYSTEM_SETTINGS["Database"]));
                                    }
                                }
                                mysqli_commit($SYSTEM_SETTINGS["Database"]);
                            } else {
                                throw new crmException('Unable to start transaction: '.mysqli_error($SYSTEM_SETTINGS["Database"]), mysqli_errno($SYSTEM_SETTINGS["Database"]));
                            }
                        } catch( Exception $e ) {
                            mysqli_rollback($SYSTEM_SETTINGS["Database"]);
                            $response['success'] = FALSE;
                            $response['errormessage'] = $e->getMessage();
                            $response['errorcode'] = $e->getCode();                            
                            AddToSysLog(array('EntryKind' => 'error', 'IsSystem' => TRUE, 'Expiry' => $SYSTEM_SETTINGS['ExpiryPolicies']['LogErrors'], 'Caption' => 'Committees', 'Description' => $response['errormessage'].' ('.$response['errorcode'].')', 'Data' => array('File' => __FILE__, 'Line' => __LINE__)));
                        }                       
                        break;
                    case 'savegrant':
                        if(!empty($_POST['GrantID'])) {
                            $grantid = intval($_POST['GrantID']);
                            $setSQL = new stmtSQL('UPDATE', 'tblgrant', $SYSTEM_SETTINGS["Database"]);
                            $setSQL->addWhere('GrantID', 'integer', $grantid);
                        } else {
                            $setSQL = new stmtSQL('INSERT', 'tblgrant', $SYSTEM_SETTINGS["Database"]);
                        }
                        $setSQL->addField('Title', 'string', $_POST['Title']);
                        $setSQL->addField('Description', 'memo', $_POST['Description']);
                        $response = ExecuteSQL($setSQL);
                        if($response['success'] && ($response['_affectedrows'] > 0)) {
                            if(empty($grantid)) {
                                $grantid = mysqli_insert_id($SYSTEM_SETTINGS["Database"]);
                            }
                            AddHistory(array(
                                'type' => 'edit', 'description' => 'Grant '.(empty($_POST['GrantID']) ? 'added' : 'updated').': '.TextEllipsis(HTML2Plain($_POST['Title']), 60),
                                'GrantID' => $grantid,
                                'author' => $AUTHENTICATION['Person']['PersonID'],
                            ));
                        }
                        break;
                    case 'savepublication':
                        if(!empty($_POST['PublicationID'])) {
                            $publicationid = intval($_POST['PublicationID']);
                            $setSQL = new stmtSQL('UPDATE', 'tblpublication', $SYSTEM_SETTINGS["Database"]);
                            $setSQL->addWhere('PublicationID', 'integer', $publicationid);
                        } else {
                            $setSQL = new stmtSQL('INSERT', 'tblpublication', $SYSTEM_SETTINGS["Database"]);
                        }
                        $setSQL->addField('Title', 'string', $_POST['Title']);
                        $setSQL->addField('Description', 'fmttext', $_POST['Description']);
                        $setSQL->addField('PublicationType', 'enum', $_POST['PublicationType']);
                        $setSQL->addField('PublicationScope', 'enum', $_POST['PublicationScope']);
                        $setSQL->addField('Flags[]', 'set', (isset($_POST['Flags']) ? $_POST['Flags'] : null), null, TRUE);
                        $response = ExecuteSQL($setSQL);
                        if($response['success'] && ($response['_affectedrows'] > 0)) {
                            if(empty($publicationid)) {
                                $publicationid = mysqli_insert_id($SYSTEM_SETTINGS["Database"]);
                            }
                            AddHistory(array(
                                'type' => 'edit', 'description' => 'Publication '.(empty($_POST['PublicationID']) ? 'added' : 'updated').': '.TextEllipsis(HTML2Plain($_POST['Title']), 60),
                                'PublicationID' => $publicationid,
                                'author' => $AUTHENTICATION['Person']['PersonID'],
                            ));
                        }
                        break;
                    case 'assignmsnumber':
                        if(!empty($_POST['PersonID'])) {
                            AssignMSNumber(intval($_POST['PersonID']));
                            $response['success'] = TRUE;
                        }
                        break;
                    case 'cancelddi':
                        if (CheckRequiredParams(array('DDIID' => FALSE), $_POST)) {
                            $ddi = new crmDirectDebitInstruction($SYSTEM_SETTINGS['Database'], $_POST['DDIID']);
                            if(!empty($ddi)) {
                                $setSQL = new stmtSQL('UPDATE', 'tblddi', $SYSTEM_SETTINGS["Database"]);
                                $setSQL->addWhere('DDIID', 'integer', $ddi->DDI['DDIID']);
                                $setSQL->addField('AUDDIS', 'string', '0C');
                                $setSQL->addField('InstructionStatus', 'enum', 'cancelled');
                                $response = ExecuteSQL($setSQL);
                                if($response['success'] && ($response['_affectedrows'] > 0)) {
                                    $OwnerIDField = OwnerIDField($ddi->DDI);
                                    AddHistory(array(
                                        'type' => 'transaction', 'flags' => 'warning', 'description' => 'Direct Debit Instruction cancelled: '.$ddi->DDI['DDReference'],
                                        'DDIID' => $ddi->DDI['DDIID'], $OwnerIDField => $ddi->DDI[$OwnerIDField],
                                        'author' => $AUTHENTICATION['Person']['PersonID'],
                                    ));
                                    DDIRemoved(array($OwnerIDField => $ddi->DDI[$OwnerIDField]));
                                }
                            }
                        }
                        break;
                    case 'delddi':
                        $ddi = new crmDirectDebitInstruction($SYSTEM_SETTINGS['Database'], $_POST['DDIID']);
                        if($ddi->Found) {
                            $response = SimpleDeleteRecord('tblddi', array('DDIID'), OwnerIDField($ddi->DDI), "Direct Debit Instruction deleted: ".$ddi->DDI['InformalReference'], $ddi->DDI);
                            $data = $ddi->DDI;
                            unset($data['SortCode']);
                            unset($data['AccountNo']);
                            AddToSysLog(array('EntryKind' => 'danger', 'IsSystem' => TRUE, 'Caption' => 'Finance', 'Description' => "Direct Debit Instruction deleted: ".$ddi->DDI['InformalReference'], 'Data' => $data));
                            $OwnerIDField = OwnerIDField($ddi->DDI);
                            DDIRemoved(array($OwnerIDField => $ddi->DDI[$OwnerIDField]));
                        } else {
                            $response['errormessage'] = "The Direct Debit Instruction was not found.";
                        }
                        break;
                    case 'attachdiscount':
                        if (CheckRequiredParams(array('DiscountID' => FALSE), $_POST)) {
                            $discount = new crmDiscountCode($SYSTEM_SETTINGS["Database"], $_POST['DiscountID']);
                            if($discount->Found) {
                                $OwnerIDField = OwnerIDField();
                                if($OwnerIDField == 'OrganisationID') {
                                    $setSQL = new stmtSQL('INSERT', 'tbldiscounttoorganisation', $SYSTEM_SETTINGS["Database"]);
                                } else {
                                    $setSQL = new stmtSQL('INSERT', 'tbldiscounttoperson', $SYSTEM_SETTINGS["Database"]);
                                }
                                $setSQL->addField($OwnerIDField, 'integer', $_POST[$OwnerIDField]);
                                $setSQL->addField('DiscountID', 'integer', $discount->DiscountID);
                                $setSQL->addField('RefCount', 'integer', $discount->RefCount);
                                if(!empty($_POST['Expires'])) {
                                    $setSQL->addField('Expires', 'utc', $_POST['Expires']);
                                }
                                $response = ExecuteSQL($setSQL);
                                if($response['success'] && ($response['_affectedrows'] > 0)) {
                                    AddHistory(array(
                                        'type' => 'edit', 'description' => 'Discount Code attached: '.$discount->DiscountCode.', '.$discount->Description,
                                        $OwnerIDField => $_POST[$OwnerIDField],
                                        'author' => $AUTHENTICATION['Person']['PersonID'],
                                    ));                                
                                }
                            }
                        }
                        break;
                    case 'removediscount':
                        if(isset($_POST['DiscountToPersonID'])) {
                            $sql = 
                            "SELECT tbldiscounttoperson.DiscountToPersonID, tbldiscounttoperson.PersonID, tbldiscount.DiscountID, tbldiscount.DiscountCode, tbldiscount.Description
                             FROM tbldiscounttoperson
                             LEFT JOIN tbldiscount ON tbldiscount.DiscountID = tbldiscounttoperson.DiscountID
                             WHERE tbldiscounttoperson.DiscountToPersonID = ".intval($_POST['DiscountToPersonID']);
                            $discount = SingleRecord($SYSTEM_SETTINGS["Database"], $sql);
                            if(!empty($discount)) {
                                $response = SimpleDeleteRecord('tbldiscounttoperson', array('DiscountToPersonID'), 'PersonID', "Discount code removed: ".$discount['DiscountCode'].', '.$discount['Description'], $discount);
                            } else {
                                $response['errormessage'] = "The Discount Code was not found.";
                            }
                        } elseif(isset($_POST['DiscountToOrganisationID'])) {
                            $sql = 
                            "SELECT tbldiscounttoorganisation.DiscountToOrganisationID, tbldiscounttoperson.PersonID, tbldiscount.DiscountID, tbldiscount.DiscountCode, tbldiscount.Description
                             FROM tbldiscounttoorganisation
                             LEFT JOIN tbldiscount ON tbldiscount.DiscountID = tbldiscounttoorganisation.DiscountID
                             WHERE tbldiscounttoorganisation.DiscountToOrganisationID = ".intval($_POST['DiscountToOrganisationID']);
                            $discount = SingleRecord($SYSTEM_SETTINGS["Database"], $sql);
                            if(!empty($discount)) {
                                $response = SimpleDeleteRecord('tbldiscounttoorganisation', array('DiscountToOrganisationID'), 'OrganisationID', "Discount code removed: ".$discount['DiscountCode'].', '.$discount['Description'], $discount);
                            } else {
                                $response['errormessage'] = "The Discount Code was not found.";
                            }
                        }
                        break;
                    case 'deldiscount':
                        $discount = new crmDiscountCode($SYSTEM_SETTINGS['Database'], $_POST['DiscountID']);
                        if($discount->Found) {
                            $response = SimpleDeleteRecord('tbldiscount', array('DiscountID'), null, null, $discount->Discount);
                            if($response['success'] && ($response['_affectedrows'] > 0)) {
                                AddToSysLog(array('EntryKind' => 'danger', 'Caption' => 'Discount deleted', 'Description' => 'Discount code deleted: '.NameStr($discount->DiscountCode).', '.TextStr($discount->Discount['Discount']) , 'Data' => $_POST));
                            }
                        } else {
                            $response['errormessage'] = "The Discount Code was not found."; 
                        }
                        break;
                    case 'savediscount':
                        try {
                            if(StartTransaction($SYSTEM_SETTINGS["Database"])) {
                                if(!empty($_POST['DiscountID'])) {
                                    $setSQL = new stmtSQL('UPDATE', 'tbldiscount', $SYSTEM_SETTINGS["Database"]);
                                    $discount = new crmDiscountCode($SYSTEM_SETTINGS['Database'], $_POST['DiscountID']);
                                    $setSQL->addWhere('DiscountID', 'integer', $discount->DiscountID);
                                } else {
                                    $setSQL = new stmtSQL('INSERT', 'tbldiscount', $SYSTEM_SETTINGS["Database"]);
                                    $setSQL->addField('DiscountCode', 'string', $_POST['DiscountCode']);
                                }
                                $setSQL->addField('Description', 'string', $_POST['Description']);
                                $setSQL->addField('CategorySelector', 'string', $_POST['CategorySelector'], null, TRUE);
                                $setSQL->addField('InvoiceItemTypeID', 'integer', $_POST['InvoiceItemTypeID'], null, TRUE);
                                $setSQL->addField('Discount', 'string', $_POST['Discount']);
                                $setSQL->addField('RefCount', 'integer', max(1, intval($_POST['RefCount'])));
                                if(empty($_POST['ValidFrom'])) {
                                    $setSQL->addFieldStmt('ValidFrom', 'UTC_TIMESTAMP()');
                                } else {
                                    $setSQL->addField('ValidFrom', 'utc', $_POST['ValidFrom']);
                                }
                                $setSQL->addField('ValidUntil', 'utc', $_POST['ValidUntil'], null, TRUE);
                                $response = ExecuteSQL($setSQL);
                                if($response['success'] && ($response['_affectedrows'] > 0)) {
                                    if($setSQL->IsInsert) {
                                        AddToSysLog(array('EntryKind' => 'info', 'Caption' => 'Discount created', 'Description' => 'New discount code created: '.NameStr($_POST['DiscountCode']).', '.TextStr($_POST['Discount']) , 'Data' => $_POST));
                                    } else {
                                        AddToSysLog(array('EntryKind' => 'warning', 'Caption' => 'Discount edit', 'Description' => 'Discount code amended: '.NameStr($discount->DiscountCode).', '.TextStr($_POST['Discount']) , 'Data' => $_POST));
                                    }
                                }
                                mysqli_commit($SYSTEM_SETTINGS["Database"]);
                            } else {
                                throw new crmException('Unable to start transaction: '.mysqli_error($SYSTEM_SETTINGS["Database"]), mysqli_errno($SYSTEM_SETTINGS["Database"]));
                            }
                        } catch( Exception $e ) {
                            mysqli_rollback($SYSTEM_SETTINGS["Database"]);
                            $response['success'] = FALSE;
                            $response['errormessage'] = $e->getMessage();
                            $response['errorcode'] = $e->getCode();                            
                            AddToSysLog(array('EntryKind' => 'error', 'IsSystem' => TRUE, 'Expiry' => $SYSTEM_SETTINGS['ExpiryPolicies']['LogErrors'], 'Caption' => 'Finance', 'Description' => $response['errormessage'].' ('.$response['errorcode'].')', 'Data' => array('File' => __FILE__, 'Line' => __LINE__)));
                        }
                        break;
                    case 'saveddi':
                        try {
                            if(StartTransaction($SYSTEM_SETTINGS["Database"])) {
                                $OwnerIDField = OwnerIDField();
                                if(!empty($_POST['DDIID'])) {
                                    $ddiid = intval($_POST['DDIID']);
                                    $ddi = new crmDirectDebitInstruction($SYSTEM_SETTINGS['Database'], $ddiid);
                                    $setSQL = new stmtSQL('UPDATE', 'tblddi', $SYSTEM_SETTINGS["Database"]);
                                    $setSQL->addWhere('DDIID', 'integer', $ddiid);
                                    $setSQL->addField('AUDDIS', 'string', ($ddi->DDI['InstructionStatus'] == 'active' ? 'MODFY' : '0N'));
                                    $instrScope = $ddi['InstructionScope'];
                                } else {
                                    $setSQL = new stmtSQL('INSERT', 'tblddi', $SYSTEM_SETTINGS["Database"]);
                                    $setSQL->addFieldStmt('Created', 'UTC_TIMESTAMP()');
                                    if(empty($_POST['InstructionScope'])) {
                                        $instrScope = null;
                                    } elseif(is_array($_POST['InstructionScope'])) {
                                        $instrScope = '';
                                        $count = 0;
                                        foreach($_POST['InstructionScope'] AS $item) {
                                            $instrScope .= ($count > 0 ? ',' : '').VarnameStr($item);
                                            $count++;
                                        }
                                    } else {
                                        $instrScope = IdentifiersStr($_POST['InstructionScope']);
                                    }
                                    $setSQL->addField('InstructionScope[]', 'set', $instrScope, null, TRUE);
                                    $setSQL->addField('InstructionType', 'enum', 'auddisoffline');
                                    $setSQL->addField('AUDDIS', 'string', '0N');
                                    $setSQL->addField($OwnerIDField, 'integer', $_POST[$OwnerIDField]);
/*                                    if(empty($_POST['DDReference'])) {
                                        $_POST['DDReference']=$SYSTEM_SETTINGS["General"]['OrgShortName'].time().'-'.$_POST[OwnerIDField()];
                                    }*/
                                    $setSQL->addField('DDReference', 'string', $_POST['DDReference'], null, TRUE);
                                }
                                $setSQL->addField('TransactionCount', 'integer', 0);
                                $setSQL->addFieldStmt('LastUsed', 'NULL');
                                $setSQL->addField('AccountHolder', 'string', $_POST['AccountHolder']);
                                $setSQL->addField('BankName', 'string', $_POST['BankName'], null, TRUE);
                                $setSQL->addField('AccountNo', 'string', $_POST['AccountNo'], null, TRUE, TRUE);
                                $setSQL->addField('SortCode', 'string', $_POST['SortCode'], null, TRUE, TRUE);
                                $setSQL->addField('InstructionStatus', 'enum', 'setup');
                                $response = ExecuteSQL($setSQL);
                                if($response['success'] && ($response['_affectedrows'] > 0)) {
                                    if(empty($ddiid)) {
                                        //New DDI
                                        $ddiid = mysqli_insert_id($SYSTEM_SETTINGS["Database"]);
                                        if($SYSTEM_SETTINGS["Finance"]['DirectDebit']['RequireMSNumber']) {
                                            AssignMSNumber((!empty($PERSON) ? $PERSON : $_POST[$OwnerIDField]));
                                        }                                        
                                        //New instruction relates to Membership
                                        if(empty($instrScope) || (stripos($instrScope, 'members') !== FALSE)) {
                                            if(($OwnerIDField == 'PersonID') && $SYSTEM_SETTINGS['Membership']['PaidOnDDI']) {
                                                $PERSON = new crmPerson('eventsPerson', $SYSTEM_SETTINGS['Database'], $_POST[$OwnerIDField], $SYSTEM_SETTINGS['Membership']);
                                                //Adjust any open MS applications for DD payment
                                                $msapplication = $PERSON->GetOpenApplication();
                                                if(!empty($msapplication)) {
                                                    $msapplication = new crmApplication($SYSTEM_SETTINGS['Database'], $msapplication['ApplicationID'], $SYSTEM_SETTINGS['Membership']);
                                                    if(!$msapplication->Application['Paid'] && $msapplication->Application['HasTransaction']) {
                                                        CancelInvoiceItem($msapplication->Application['InvoiceItemID']);
                                                        $msapplication->Reload();
                                                        CreateApplicationTransaction($msapplication->Application);
                                                    }
                                                    MarkMSApplicationAsPaid($msapplication, TRUE);
                                                }
                                                //Any open renewals?
                                                $renewal = $PERSON->RenewalSettings();
                                                if(!empty($renewal) && $renewal['HasTransaction'] && empty($renewal['Processed'])) {
                                                    $params = array(
                                                        'ISO4217' => $renewal['ISO4217'],
                                                        'MSGradeID' => $renewal['MSGradeID'],
                                                        'ISO3166' => $renewal['ISO3166'],
                                                        'ForDate' => $renewal['MSNextRenewal'],
                                                        'IsDD' => (!empty($renewal['DDIID'])),
                                                        'Free' => $renewal['MSFree'],
                                                        'DiscountID' => $renewal['DiscountID'],
                                                    );
                                                    $fee = $msfees->CalculateFee($params);
                                                    if($fee->Net <> $renewal['ItemNet']) {
                                                        //Different amount, recreate the transaction
                                                        CancelInvoiceItem($renewal['InvoiceItemID']);
                                                        if($fee->Net <> 0) {
                                                            $invoice = GetProForma(array('ISO4217' => $renewal['ISO4217'], 'PersonID' => $PERSON->PersonID));
                                                            $invoice->NewItem(array(
                                                                'Mnemonic' => 'ms_renewal',
                                                                'LinkedID' => $renewal['LinkedID'],
                                                                'ItemNet' => $fee->Net,
                                                                'ItemVATRate' => $fee->VATRate,
                                                                'DiscountID' => $renewal['DiscountID'],
                                                                'ItemDate' => $renewal['MSNextRenewal'],
//                                                                'Description' => '%TypeName%, '.$renewal['GradeCaption'],
                                                                'Explain' => json_encode($fee->Explanation),
                                                            ), $renewal);
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    } else {
                                        if($SYSTEM_SETTINGS["Finance"]['DirectDebit']['RequireMSNumber']) {
                                            AssignMSNumber((!empty($PERSON) ? $PERSON : $_POST[$OwnerIDField]));
                                        }
                                    }
                                    AddHistory(array(
                                        'type' => 'transaction', 'description' => 'Direct Debit Instruction '.(empty($_POST['DDIID']) ? 'created' : 'updated: '.$ddi->DDI['InformalReference'] ),
                                        'DDIID' => $ddiid, $OwnerIDField => $_POST[$OwnerIDField],
                                        'author' => $AUTHENTICATION['Person']['PersonID'],
                                    ));
                                    //New DDI - save the paper document
                                    if(empty($_POST['DDIID'])) {
                                        if (isset($_FILES['File']) && ($_FILES['File']['size'] > 0)) {
                                            if (is_uploaded_file($_FILES['File']['tmp_name'])) {
                                                $objectname=time().'_'.RandomString(32).'_'.$_FILES['File']['name'];
                                                $finfo = FileinfoFromExt($_FILES['File']['name']);
                                                $tempfilename = IncTrailingPathDelimiter(sys_get_temp_dir()).$objectname;
                                                if (move_uploaded_file($_FILES['File']['tmp_name'], $tempfilename)) {
                                                    $stored = FALSE;
                                                    if($SYSTEM_SETTINGS["System"]["FileStore"] == 'S3') {
                                                        try {
                                                            $s3 = S3Client::factory(array(
                                                                'credentials' => array('key' => $SYSTEM_SETTINGS['Credentials']['AWS']['S3']['AccessKey'],
                                                                                       'secret' => Decrypt($SYSTEM_SETTINGS['Credentials']['AWS']['S3']['SecretKey']),
                                                                ),
                                                                'region'  => $SYSTEM_SETTINGS['Storage']['Region']
                                                            ));
                                                            $response = $s3->putObject(array(
                                                                'Bucket'     => $SYSTEM_SETTINGS['Storage']['Bucket'],
                                                                'Key'        => $objectname,
                                                                'SourceFile' => $tempfilename,
                                                            ));
                                                            $stored = true;
                                                        } catch( Exception $e ) {
                                                            $response['errormessage'] = $e->getMessage();
                                                            $response['errorcode'] = $e->getCode();
                                                            AddToSysLog(array('EntryKind' => 'warning', 'IsSystem' => TRUE, 'Expiry' => $SYSTEM_SETTINGS['ExpiryPolicies']['LogWarnings'], 'Caption' => 'S3Client error', 'Description' => $response['errormessage'].' ('.$response['errorcode'].') Reverting to database storage.', 'Data' => array('File' => __FILE__, 'Line' => __LINE__)));
                                                            throw $e; //rethrow the exception
                                                        }
                                                    }
                                                    $docTitle = 'Direct Debit Instruction '.PunctuatedTextStr($_POST['DDReference']);
                                                    $sql =
                                                    "INSERT INTO tbldocument (LastModified, DocTitle, `Filename`, `Mimetype`, Bucket, Objectname, Data)
                                                     VALUES (UTC_TIMESTAMP(),
                                                            '".mysqli_real_escape_string($SYSTEM_SETTINGS['Database'], $docTitle)."',
                                                            '".mysqli_real_escape_string($SYSTEM_SETTINGS['Database'], $_FILES['File']['name'])."',
                                                            '{$finfo['MimeType']}',
                                                            ".($stored && ($SYSTEM_SETTINGS["System"]["FileStore"] == 'S3') ? "'".mysqli_real_escape_string($SYSTEM_SETTINGS['Database'], $SYSTEM_SETTINGS['Storage']['Bucket'])."'" : "NULL").",
                                                            ".($stored && ($SYSTEM_SETTINGS["System"]["FileStore"] == 'S3') ? "'".mysqli_real_escape_string($SYSTEM_SETTINGS['Database'], $objectname)."'" : "NULL").",
                                                            ".(!$stored ? "LOAD_FILE(\"".mysqli_real_escape_string($SYSTEM_SETTINGS["Database"], $tempfilename)."\")" : "NULL")."
                                                     )";
                                                    $response = ExecuteSQL($sql);
                                                    if($response['success']) {
                                                        $documentid = mysqli_insert_id($SYSTEM_SETTINGS["Database"]);
                                                        $sql = "INSERT INTO tbldocumenttoddi (DocumentID, DDIID) VALUES ({$documentid}, {$ddiid})";
                                                        $response = ExecuteSQL($sql);
                                                        if($response['success']) {
                                                            foreach(array('PersonID' => 'tbldocumenttoperson', 'OrganisationID' => 'tbldocumenttoorganisation') AS $fieldname => $tablename) {
                                                                if(isset($_POST[$fieldname])) {
                                                                    $id = intval($_POST[$fieldname]);
                                                                    $sql = "INSERT INTO {$tablename} (DocumentID, {$fieldname}) VALUES ({$documentid}, {$id})";
                                                                    $response = ExecuteSQL($sql);
                                                                    if($response['success']) {
                                                                        $idfield = OwnerIDField();
                                                                        AddHistory(array('type' => 'edit', 'description' => "Document uploaded: ".$docTitle, 'author' => $AUTHENTICATION['Person']['PersonID'], $idfield => $_POST[$idfield]));
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }
                                                    unlink($tempfilename);
                                                } else {
                                                    $response['errormessage'] = 'Unable to create temporary file.';
                                                    $response['errorcode'] = 3;
                                                }
                                            } else {
                                                $response['errormessage'] = 'The file is invalid.';
                                                $response['errorcode'] = 2;
                                            }
                                        } else {
                                            $response['errormessage'] = 'The file is empty.';
                                            $response['errorcode'] = 1;
                                        }
                                        if(!$response['success']) {
                                            throw new crmException('Unable to save direct debit instruction: '.$response['errormessage'], $response['errorcode']);
                                        }
                                    }
                                } else {
                                    throw new crmException('Unable to store direct debit instruction: '.$response['errormessage'], $response['errorcode']);
                                }
                                SaveNote();
                                mysqli_commit($SYSTEM_SETTINGS["Database"]);
                            } else {
                                throw new crmException('Unable to start transaction: '.mysqli_error($SYSTEM_SETTINGS["Database"]), mysqli_errno($SYSTEM_SETTINGS["Database"]));
                            }
                        } catch( Exception $e ) {
                            mysqli_rollback($SYSTEM_SETTINGS["Database"]);
                            $response['success'] = FALSE;
                            $response['errormessage'] = $e->getMessage();
                            $response['errorcode'] = $e->getCode();                            
                            AddToSysLog(array('EntryKind' => 'error', 'IsSystem' => TRUE, 'Expiry' => $SYSTEM_SETTINGS['ExpiryPolicies']['LogErrors'], 'Caption' => 'Direct Debit', 'Description' => $response['errormessage'].' ('.$response['errorcode'].')', 'Data' => array('File' => __FILE__, 'Line' => __LINE__)));
                        }
                        break;
                    case 'closeInvoice':
                        if(isset($_POST['InvoiceID'])) {
                            $INVOICE = new crmInvoice('eventsFinance', $SYSTEM_SETTINGS['Database'], $_POST['InvoiceID'], InvoiceSettings());
                            $INVOICE->Close();
                            $response['success'] = TRUE;
                        }
                        break;
                    case 'delinvoice':
                        if(isset($_POST['InvoiceID'])) {
                            $INVOICE = new crmInvoice('eventsFinance', $SYSTEM_SETTINGS['Database'], $_POST['InvoiceID'], InvoiceSettings());
                            if($INVOICE->ProForma) {
                                $sql = "DELETE FROM tblinvoice WHERE InvoiceID = ".intval($_POST['InvoiceID']);
                                $response = ExecuteSQL($sql);
                                if($response['success']) {
                                    AddHistory(array(
                                        'type' => 'delete',
                                        'description' => $INVOICE->Invoice['InvoiceCaption'].' deleted',
                                        'author' => $AUTHENTICATION['Person']['PersonID'],
                                        'PersonID' => (!empty($INVOICE->Invoice['PersonID']) ? $INVOICE->Invoice['PersonID'] : null),
                                        'OrganisationID' => (!empty($INVOICE->Invoice['OrganisationID']) ? $INVOICE->Invoice['OrganisationID'] : null),
                                    ));
                                    $data = array();
                                    foreach(array('InvoiceID', 'InvoiceCaption', 'InvoiceType', 'InvoiceDate', 'InvoiceTo', 'CustNo', 'CustomerRef', 'EDISent', 'EDIData', 'ISO4217', 'NonZeroItemCount', 'Net', 'VAT', 'Total', 'AllocatedAmount', 'Outstanding', 'Settled') AS $key) {
                                        $data[$key] = $INVOICE->Invoice[$key];
                                    }
                                    AddToSysLog(array('EntryKind' => 'danger', 'IsSystem' => TRUE, 'Caption' => 'Finance', 'Description' => $INVOICE->Invoice['InvoiceCaption'].' deleted', 'Data' => $data));
                                }
                            } else {
                                $response['success'] = FALSE;
                                $response['errormessage'] = "This is a final document. It cannot be deleted.";
                                $response['errorcode'] = 1;                            
                            }
                        }
                        break;
                    case 'delinvoiceitem':
                        if(isset($_POST['InvoiceItemID'])) {
                            $itemdata = InvoiceItemToInvoice($_POST['InvoiceItemID']);
                            if(!empty($itemdata['InvoiceItemID'])) {
                                $sql = "DELETE FROM tblinvoiceitem WHERE InvoiceItemID = ".intval($itemdata['InvoiceItemID']);
                                $response = ExecuteSQL($sql);
                                if($response['success']) {
                                    AddHistory(array(
                                        'type' => 'delete',
                                        'description' => $itemdata['InvoiceCaption']." item deleted: ".$itemdata['InvoiceItemDescription'],
                                        'author' => $AUTHENTICATION['Person']['PersonID'],
                                        'PersonID' => $itemdata['PersonID'],
                                        'OrganisationID' => $itemdata['OrganisationID']
                                    ));
                                }
                            }
                        }
                        break;
                    case 'saveInvoiceItem':
                        if(isset($_POST['InvoiceItemID']) || isset($_POST['InvoiceItemTypeID'])) {
                            if(!empty($_POST['InvoiceItemID'])) {
                                $itemdata = InvoiceItemToInvoice($_POST['InvoiceItemID']);
                                $setSQL = new stmtSQL('UPDATE', 'tblinvoiceitem', $SYSTEM_SETTINGS["Database"]);
                                $setSQL->addWhere('InvoiceItemID', 'integer', $itemdata['InvoiceItemID']);
                            } else {
                                $INVOICE = new crmInvoice('eventsFinance', $SYSTEM_SETTINGS['Database'], $_POST['InvoiceID'], InvoiceSettings());
                                $itemdata = $INVOICE->Invoice;
                                $setSQL = new stmtSQL('INSERT', 'tblinvoiceitem', $SYSTEM_SETTINGS["Database"]);
                                $setSQL->addField('InvoiceID', 'integer', $INVOICE->InvoiceID);
                            }
                            if(isset($_POST['InvoiceItemTypeID'])) {
                                $setSQL->addField('InvoiceItemTypeID', 'integer', $_POST['InvoiceItemTypeID']);
                            }
                            $price = new crmScaledPrice($SYSTEM_SETTINGS["Database"], $itemdata['ISO4217']);
                            $qty = max(intval($_POST['ItemQty']), 0);
                            $setSQL->addField('ItemQty', 'integer', $qty);
                            $unitprice = intval(round(floatvalExt(str_replace(array(utf8_encode('£'), '$', utf8_encode('€'), ',', '%'), '', $_POST['ItemUnitPrice']))*100));
                            $setSQL->addField('ItemUnitPrice', 'integer', $unitprice);
                            $price->Net = $unitprice*$qty;
                            $setSQL->addField('ItemNet', 'integer', $price->Net);
                            $vatrate = intval(round(floatvalExt(str_replace(array(utf8_encode('£'), '$', utf8_encode('€'), ',', '%'), '', $_POST['ItemVATRate']))));
                            $price->VATRate = $vatrate;
                            $setSQL->addField('ItemVATRate', 'integer', $price->VATRate);
                            $setSQL->addField('ItemVAT', 'integer', $price->VAT);
                            $setSQL->addField('Description', 'string', $_POST['Description'], null, TRUE);
                            $setSQL->addField('ItemDate', 'utc', $_POST['ItemDate']);
                            $response = ExecuteSQL($setSQL);
                            if($response['success']) {
                                $desc = PunctuatedTextStr($_POST['Description']);
                                if(empty($desc)) {
                                    $itemtypes = new crmInvoiceItemTypes($SYSTEM_SETTINGS["Database"]);
                                    $desc = $itemtypes->NameFromID(isset($itemdata['InvoiceItemTypeID']) ? $itemdata['InvoiceItemTypeID'] : (isset($_POST['InvoiceItemTypeID']) ? $_POST['InvoiceItemTypeID'] : null));
                                }
                                AddHistory(array(
                                    'type' => 'edit',
                                    'description' => $itemdata['InvoiceCaption']." item ".($setSQL->IsInsert ? 'added' : 'modified').": ".$desc,
                                    'author' => $AUTHENTICATION['Person']['PersonID'],
                                    'PersonID' => $itemdata['PersonID'],
                                    'OrganisationID' => $itemdata['OrganisationID']
                                ));
                            }
                        } elseif(isset($_POST['InvoiceID'])) {
                            $INVOICE = new crmInvoice('eventsFinance', $SYSTEM_SETTINGS['Database'], $_POST['InvoiceID'], InvoiceSettings());
                            $setSQL = new stmtSQL('UPDATE', 'tblinvoice', $SYSTEM_SETTINGS["Database"]);
                            $setSQL->addWhere('InvoiceID', 'integer', $INVOICE->InvoiceID);
                            $list = array();
                            foreach(array('AddInfo' => 'text', 'Payable' => 'text') AS $fieldname => $fieldtype) {
                                if(isset($_POST[$fieldname])) {
                                    $setSQL->addField($fieldname, $fieldtype, $_POST[$fieldname]);
                                    $list[] = $fieldname;
                                }
                            }
                            $response = ExecuteSQL($setSQL);
                            if($response['success']) {
                                AddHistory(array(
                                    'type' => 'edit',
                                    'description' => "Amended: ".$INVOICE->Invoice['InvoiceCaption'].' ('.implode(', ', $list).')',
                                    'author' => $AUTHENTICATION['Person']['PersonID'],
                                    'PersonID' => $INVOICE->Invoice['PersonID'], 
                                    'OrganisationID' => $INVOICE->Invoice['OrganisationID']
                                ));
                            }                            
                        }
                        break;
                    case 'savepersonmsitem':
                        if(isset($_POST['PersonMSID'])) {
                            $setSQL = new stmtSQL('UPDATE', 'tblpersonms', $SYSTEM_SETTINGS["Database"]);
                            $setSQL->addWhere('PersonMSID', 'integer', $_POST['PersonMSID']);
                            $setSQL->addField('BeginDate', 'datetime', $_POST['BeginDate'], null, TRUE);
                            $setSQL->addField('EndDate', 'datetime', $_POST['EndDate'], null, TRUE);
                            $setSQL->addField('MSStatusID', 'integer', $_POST['MSStatusID']);
                            $setSQL->addField('MSGradeID', 'integer', $_POST['MSGradeID'], null, TRUE);
                            $setSQL->addField('MSFlags', 'set', (empty($_POST['MSFlags']) ? '' : $_POST['MSFlags']));
                            $response = ExecuteSQL($setSQL);
                            if($response['success']) {
                                $sql = "SELECT PersonID FROM tblpersonms WHERE PersonMSID = ".intval($_POST['PersonMSID']);
                                $personid = SingleRecord($SYSTEM_SETTINGS['Database'], $sql);
                                AddHistory(array('type' => 'edit', 'description' => "Membership history entry manually modified", 'author' => $AUTHENTICATION['Person']['PersonID'], 'PersonID' => $personid));
                            }
                        }
                        break;
                    case 'delpersonmsitem':
                        $response = SimpleDeleteRecord('tblpersonms', array('PersonMSID'), 'PersonID', "Membership history entry deleted");
                        break;
                    case 'saveMoney':
                        try {
                            if(StartTransaction($SYSTEM_SETTINGS["Database"])) {
                                if(!empty($_POST['InvoiceID'])) {
                                    $INVOICE = new crmInvoice('eventsFinance', $SYSTEM_SETTINGS["Database"], $_POST['InvoiceID'], InvoiceSettings());
                                    //$INVOICE->Close();
                                }
                                $setSQL = new stmtSQL('INSERT', 'tblmoney', $SYSTEM_SETTINGS["Database"]);
                                $setSQL->addField('TransactionTypeID', 'integer', $_POST['TransactionTypeID']);
                                $setSQL->addField('Received', 'utc', $_POST['Received']);
                                $setSQL->addField('ReceivedAmount', 'money', $_POST['ReceivedAmount']);
                                if(!empty($INVOICE)) {
                                    $setSQL->addField('ISO4217', 'string', $INVOICE->Invoice['ISO4217']);
                                } else {
                                    $setSQL->addField('ISO4217', 'string', $_POST['ISO4217']);
                                }
                                $setSQL->addField('ReceivedFrom', 'string', $_POST['ReceivedFrom']);
                                $setSQL->addField('TransactionReference', 'string', $_POST['TransactionReference']);
                                $setSQL->addField('AddInfo', 'text', $_POST['AddInfo']);
                                $response = ExecuteSQL($setSQL);
                                if($response['success']) {
                                    $moneyid = mysqli_insert_id($SYSTEM_SETTINGS["Database"]);
                                    if(!empty($INVOICE)) {
                                        $allocated = min($INVOICE->Invoice['Outstanding'], round(floatvalExt($_POST['ReceivedAmount'])*100,0), round(floatvalExt($_POST['AllocatedAmount'])*100, 0));
                                        $proceed = FALSE;
                                        if($INVOICE->Invoice['Outstanding'] <= $allocated) {
                                            $proceed = TRUE;
                                        } else {
                                            $minrec = $INVOICE->MinReceivable();
                                            $writeoff = new crmScaledPrice($SYSTEM_SETTINGS['Database'], $INVOICE->ISO4217);
                                            if(!empty($_POST['WrittenOff']) && !empty($_POST['WrittenOffType'])) {
                                                $writeoff->VATRate = $minrec['ItemVATRate'];
                                                $writeoff->Value = -abs(intval(round(floatvalExt(str_replace(array(utf8_encode('£'), '$', utf8_encode('€'), ',', '%'), '', $_POST['WrittenOff']))*100)));
                                                if(($INVOICE->Invoice['Outstanding']+$writeoff->Value) <= $allocated) {
                                                    $invitemtypes = new crmInvoiceItemTypes($SYSTEM_SETTINGS["Database"]);
                                                    $writeofftype = $invitemtypes->ByID($_POST['WrittenOffType']);
                                                    //Add the write-off to the document
                                                    $gmdate = gmdate('Y-m-d H:i:s', strtotime($_POST['Received']));
                                                    $date = date('Y-m-d', strtotime($_POST['Received']));
                                                    if(empty($INVOICE->Invoice['EDISent'])) {
                                                        $sql =
                                                        "INSERT INTO tblinvoiceitem (InvoiceItemTypeID, InvoiceID, ItemQty, ItemUnitPrice, ItemVATRate, ItemNet, ItemVAT, ItemDate, Processed)
                                                         VALUES ({$writeofftype['InvoiceItemTypeID']}, {$INVOICE->InvoiceID}, 1, {$writeoff->Net}, {$writeoff->VATRate}, {$writeoff->Net}, {$writeoff->VAT}, DATE('{$date}'), '{$gmdate}')";
                                                        if (mysqli_query($SYSTEM_SETTINGS["Database"], $sql)) {
                                                            AddHistory(array('type' => 'transaction', 'description' => "Adjusted: ".ScaledIntegerAsString($writeoff->Value, "money", 100, TRUE, $INVOICE->Invoice['Symbol']).', '.$INVOICE->Invoice['InvoiceCaption'], 'author' => $AUTHENTICATION['Person']['PersonID'], 'PersonID' => $INVOICE->Invoice['PersonID'], 'OrganisationID' => $INVOICE->Invoice['OrganisationID']));
                                                            $INVOICE->Reload();
                                                            $INVOICE->Reload('items');
                                                        } else {
                                                            throw new crmException('Unable to create invoice adjustment item: '.mysqli_error($SYSTEM_SETTINGS["Database"]), mysqli_errno($SYSTEM_SETTINGS["Database"]));
                                                        }
                                                    } else {
                                                        $creditnote = CreditNoteFromInvoice($INVOICE);
                                                        $sql =
                                                        "INSERT INTO tblinvoiceitem (InvoiceItemTypeID, InvoiceID, ItemQty, ItemUnitPrice, ItemVATRate, ItemNet, ItemVAT, ItemDate, Processed)
                                                         VALUES ({$writeofftype['InvoiceItemTypeID']}, {$creditnote->InvoiceID}, 1, -{$writeoff->Net}, {$writeoff->VATRate}, -{$writeoff->Net}, -{$writeoff->VAT}, DATE('{$date}'), '{$gmdate}')";
                                                        if (mysqli_query($SYSTEM_SETTINGS["Database"], $sql)) {
                                                            AddHistory(array('type' => 'transaction', 'description' => "Credited: ".ScaledIntegerAsString(-$writeoff->Value, "money", 100, TRUE, $INVOICE->Invoice['Symbol']).', '.$INVOICE->Invoice['InvoiceCaption'], 'author' => $AUTHENTICATION['Person']['PersonID'], 'PersonID' => $INVOICE->Invoice['PersonID'], 'OrganisationID' => $INVOICE->Invoice['OrganisationID']));
                                                            $INVOICE->Reload();
                                                            $INVOICE->Reload('items');
                                                        } else {
                                                            throw new crmException('Unable to create credit note item: '.mysqli_error($SYSTEM_SETTINGS["Database"]), mysqli_errno($SYSTEM_SETTINGS["Database"]));
                                                        }
                                                    }
                                                    $proceed = TRUE;
                                                }
                                            } else {
                                                //Cannot be processed: the amount given is too low
                                                throw new crmException('Unable to process money item: the allocated amount does not match the minimum receivable', 1);
                                            }
                                        }
                                        if($proceed) {
                                            AddHistory(array('type' => 'transaction', 'description' => "Monies received: ".ScaledIntegerAsString($allocated, "money", 100, TRUE, $INVOICE->Invoice['Symbol']).', '.$INVOICE->Invoice['InvoiceCaption'], 'author' => $AUTHENTICATION['Person']['PersonID'], 'PersonID' => $INVOICE->Invoice['PersonID'], 'OrganisationID' => $INVOICE->Invoice['OrganisationID']));
                                            $INVOICE->AllocateMoney($moneyid);
                                        }
/*                                        $minrec = $INVOICE->MinReceivable();
                                        if ($minrec['Found'] && ($minrec['ItemTotal'] > $allocated)) {
                                            $writeoff = new crmScaledPrice($SYSTEM_SETTINGS['Database'], $INVOICE->ISO4217);
                                            $writeoff->VATRate = $minrec['ItemVATRate'];
                                            $writeoff->Value = $minrec['ItemTotal']-$allocated;
                                            //write off the difference
                                            if(empty($INVOICE->Invoice['EDISent'])) {
                                                //Not yet communicated to the accounting system, so add to invoice itself
                                                $gmdate = gmdate('Y-m-d H:i:s', strtotime($_POST['Received']));
                                                $date = date('Y-m-d', strtotime($_POST['Received']));
                                                $sql =
                                                "INSERT INTO tblinvoiceitem (InvoiceItemTypeID, InvoiceID, Description, ItemQty, ItemUnitPrice, ItemVATRate, ItemNet, ItemVAT, ItemDate, Processed)
                                                 (SELECT tblinvoiceitemtype.InvoiceItemTypeID, {$INVOICE->InvoiceID}, tblinvoiceitemtype.TypeName, 1, -{$writeoff->Net}, {$writeoff->VATRate}, -{$writeoff->Net}, -{$writeoff->VAT}, DATE('{$date}'), '{$gmdate}' 
                                                  FROM tblinvoiceitemtype
                                                  WHERE tblinvoiceitemtype.Mnemonic = 'write_off'
                                                  LIMIT 1)";
                                                if (mysqli_query($SYSTEM_SETTINGS["Database"], $sql)) {
                                                    AddHistory(array('type' => 'transaction', 'description' => "Written off: ".ScaledIntegerAsString($writeoff->Value, "money", 100, TRUE, $INVOICE->Invoice['Symbol']).', '.$INVOICE->Invoice['InvoiceCaption'], 'author' => $AUTHENTICATION['Person']['PersonID'], 'PersonID' => $INVOICE->Invoice['PersonID'], 'OrganisationID' => $INVOICE->Invoice['OrganisationID']));
                                                    $INVOICE->Reload();
                                                    $INVOICE->Reload('items');
                                                } else {
                                                    throw new crmException('Unable to create write-off item: '.mysqli_error($SYSTEM_SETTINGS["Database"]), mysqli_errno($SYSTEM_SETTINGS["Database"]));
                                                }
                                            } else {
                                                //Communicated to accounting system, so raise a credit note for the difference
                                                
                                            }
                                        }
                                        AddHistory(array('type' => 'transaction', 'description' => "Monies received: ".ScaledIntegerAsString($allocated, "money", 100, TRUE, $INVOICE->Invoice['Symbol']).', '.$INVOICE->Invoice['InvoiceCaption'], 'author' => $AUTHENTICATION['Person']['PersonID'], 'PersonID' => $INVOICE->Invoice['PersonID'], 'OrganisationID' => $INVOICE->Invoice['OrganisationID']));
                                        $INVOICE->AllocateMoney($moneyid);*/
                                        
                                        
/*                                        foreach(array('PersonID' => 'tblmoneytoperson', 'OrganisationID' => 'tblmoneytoorganisation') AS $fieldname => $tablename) {
                                            if(!empty($INVOICE->Invoice[$fieldname])) {
                                                $sql = "INSERT INTO {$tablename} (MoneyID, {$fieldname}) VALUES ({$moneyid}, {$INVOICE->Invoice[$fieldname]})";
                                                mysqli_query($this->Database, $sql);
                                            }
                                        }*/
/*                                        $sql = "INSERT INTO tblmoneytoinvoice (MoneyID, InvoiceID, AllocatedAmount) VALUES ({$moneyid}, {$INVOICE->InvoiceID}, {$allocated})";
                                        $response = ExecuteSQL($sql);
                                        if($response['success']) {
                                            AddHistory(array('type' => 'transaction', 'description' => "Monies received: ".ScaledIntegerAsString($allocated, "money", 100, TRUE, $INVOICE->Invoice['Symbol']).', '.$INVOICE->Invoice['InvoiceCaption'], 'author' => $AUTHENTICATION['Person']['PersonID'], 'PersonID' => $INVOICE->Invoice['PersonID'], 'OrganisationID' => $INVOICE->Invoice['OrganisationID']));
                                            foreach(array('PersonID' => 'tblmoneytoperson', 'OrganisationID' => 'tblmoneytoorganisation') AS $fieldname => $tablename) {
                                                if(!empty($INVOICE->Invoice[$fieldname])) {
                                                    $sql = "INSERT INTO {$tablename} (MoneyID, {$fieldname}) VALUES ({$moneyid}, {$INVOICE->Invoice[$fieldname]})";
                                                    $response = ExecuteSQL($sql);
                                                    if(!$response['success']) {
                                                        break;
                                                    }
                                                }
                                            }
                                        }*/
                                    }
                                }
                                if(!$response['success']) {
                                    throw new crmException('Unable to process monies received: '.$response['errormessage'], $response['errorcode']);
                                }
                                mysqli_commit($SYSTEM_SETTINGS["Database"]);
                            }
                        } catch( Exception $e ) {
                            mysqli_rollback($SYSTEM_SETTINGS["Database"]);
                            $response['success'] = FALSE;
                            $response['errormessage'] = $e->getMessage();
                            $response['errorcode'] = $e->getCode();                            
                            AddToSysLog(array('EntryKind' => 'error', 'IsSystem' => TRUE, 'Expiry' => $SYSTEM_SETTINGS['ExpiryPolicies']['LogErrors'], 'Caption' => 'Finance', 'Description' => $response['errormessage'].' ('.$response['errorcode'].')', 'Data' => $_POST));
                        }
                        break;
                    case 'savepubsubscription':
                        if (CheckRequiredParams(array('PublicationID' => FALSE), $_POST)) {
                            $lti = LinkTableInfo('pubsubscription');
                            if(!empty($lti)) {
                                if(empty($_POST['SubscriptionID'])) {
                                    if(empty($_POST['Qty'])) {
                                        $sql =
                                        "INSERT INTO {$lti['table']} (PublicationID, {$lti['owneridfield']}, Qty, Complimentary, CustomerReference, StartDate, EndDate) VALUES (
                                            ".intval($_POST['PublicationID']).",
                                            ".intval($_POST[$lti['owneridfield']]).", 0, 0, NULL, NULL, NULL
                                         )";
                                        $histtext = 'Opted out: ';
                                    } else {
                                        $sql = 
                                        "INSERT INTO {$lti['table']} (PublicationID, {$lti['owneridfield']}, Qty, Complimentary, CustomerReference, StartDate, EndDate) VALUES (
                                            ".intval($_POST['PublicationID']).",
                                            ".intval($_POST[$lti['owneridfield']]).",
                                            ".max(1, intval($_POST['Qty'])).",
                                            ".(!empty($_POST['Complimentary']) ? 1 : 0).",
                                            ".(empty($_POST['CustomerReference']) ? "NULL" :  "'".mysqli_real_escape_string($SYSTEM_SETTINGS['Database'], PunctuatedTextStr($_POST['CustomerReference']))."'").",
                                            ".(empty($_POST['StartDate']) ? "NULL" :  "'".mysqli_real_escape_string($SYSTEM_SETTINGS['Database'], ValidDateStr($_POST['StartDate']))."'").",
                                            ".(empty($_POST['EndDate']) ? "NULL" :  "'".mysqli_real_escape_string($SYSTEM_SETTINGS['Database'], ValidDateStr($_POST['EndDate']))."'")."
                                         )";
                                        $histtext = 'Subscription added: ';
                                    }
                                } elseif(empty($_POST['Qty'])) {
                                    //Same as addoptout
                                    $sql = 
                                    "UPDATE {$lti['table']} 
                                     SET Qty = 0, Complimentary = 0, CustomerReference = NULL, StartDate = NULL, EndDate = NULL, LastReminder = NULL
                                     WHERE ({$lti['linkfield']} = ".intval($_POST['SubscriptionID']).") AND ({$lti['owneridfield']} = ".intval($_POST[$lti['owneridfield']]).")";
                                     $histtext = 'Opted out: ';
                                } else {
                                    $sql = 
                                    "UPDATE {$lti['table']}
                                     SET Qty = ".max(1, intval($_POST['Qty'])).",
                                         Complimentary = ".(!empty($_POST['Complimentary']) ? 1 : 0).",
                                         CustomerReference = ".(empty($_POST['CustomerReference']) ? "NULL" :  "'".mysqli_real_escape_string($SYSTEM_SETTINGS['Database'], PunctuatedTextStr($_POST['CustomerReference']))."'").",
                                         StartDate = ".(empty($_POST['StartDate']) ? "NULL" :  "'".mysqli_real_escape_string($SYSTEM_SETTINGS['Database'], ValidDateStr($_POST['StartDate']))."'").",
                                         EndDate = ".(empty($_POST['EndDate']) ? "NULL" :  "'".mysqli_real_escape_string($SYSTEM_SETTINGS['Database'], ValidDateStr($_POST['EndDate']))."'").",
                                         LastReminder = NULL
                                     WHERE ({$lti['linkfield']} = ".intval($_POST['SubscriptionID']).") AND ({$lti['owneridfield']} = ".intval($_POST[$lti['owneridfield']]).")";
                                     $histtext = 'Subscription modified: ';
                                }
/*            'OrganisationID' => array('table' => 'tblpublicationtoorganisation', 'linkfield' => 'PublicationToOrganisationID', 'owneridfield' => 'OrganisationID', 'addresstable' => 'tbladdresstoorganisation'),
            'PersonID' => array('table' => 'tblpublicationtoperson', 'linkfield' => 'PublicationToPersonID', 'owneridfield' => 'PersonID', 'addresstable' => 'tbladdresstoperson'),*/
                                $response = ExecuteSQL($sql);
                                if($response['success']) {
                                    $sql =
                                    "SELECT tblpublication.Title, {$lti['table']}.{$lti['linkfield']}
                                     FROM tblpublication
                                     LEFT JOIN {$lti['table']} ON ({$lti['table']}.PublicationID = tblpublication.PublicationID) AND ({$lti['table']}.{$lti['owneridfield']} = ".intval($_POST[$lti['owneridfield']]).")
                                     WHERE tblpublication.PublicationID = ".intval($_POST['PublicationID']);
                                    $data = SingleRecord($SYSTEM_SETTINGS['Database'], $sql);
                                    AddHistory(array(
                                        'type' => (empty($_POST['Qty']) ? 'delete' : 'edit'),
                                        'description' => $histtext.$data['Title'],
                                        $lti['owneridfield'] => intval($_POST[$lti['owneridfield']])
                                    ), $response['_affectedrows']);
                                    if(!empty($_POST['DiffDeliveryAddress']) && !empty($_POST['Lines'])) {
                                        //There is a different delivery address
                                        if(!empty($_POST['AddressID'])) {
                                            $addressid = intval($_POST['AddressID']);
                                            $setSQL = new stmtSQL('UPDATE', 'tbladdress', $SYSTEM_SETTINGS["Database"]);
                                            $setSQL->addWhere('AddressID', 'integer', $addressid);
                                        } else {
                                            $setSQL = new stmtSQL('INSERT', 'tbladdress', $SYSTEM_SETTINGS["Database"]);
                                        }
                                        foreach(array('Lines' => 'memo', 'Postcode' => 'string', 'Town' => 'string', 'County' => 'string', 'Region' => 'string', 'ISO3166' => 'string') AS $fieldname => $fieldtype) {
                                            if(isset($_POST[$fieldname])) {
                                                $setSQL->addField($fieldname, $fieldtype, $_POST[$fieldname]);        
                                            }
                                        }
                                        $response = ExecuteSQL($setSQL);
                                        if($response['success']) {
                                            if(empty($_POST['AddressID'])) {
                                                $addressid = mysqli_insert_id($SYSTEM_SETTINGS["Database"]);
                                                //Link to owner field
                                                $sql = "INSERT INTO {$lti['addresslinktable']} (AddressID, {$lti['owneridfield']}, AddressType) VALUES ({$addressid}, ".intval($_POST[$lti['owneridfield']]).", 'other')";
                                                mysqli_query($SYSTEM_SETTINGS["Database"], $sql);
                                                $response['insert'] = $sql;
                                            }
                                            AddHistory(array(
                                                'type' => 'edit', 'description' => 'Delivery address '.(empty($_POST['AddressID']) ? 'added' : 'updated').' for '.$data['Title'],
                                                $lti['owneridfield'] => intval($_POST[$lti['owneridfield']])
                                            ), $response['_affectedrows']);
                                            if(!empty($_POST['AddressToPublicationID'])) {
                                                $setSQL = new stmtSQL('UPDATE', 'tbladdresstopublication', $SYSTEM_SETTINGS["Database"]);
                                                $setSQL->addWhere('AddressToPublicationID', 'integer', intval($_POST['AddressToPublicationID']));                                                
                                            } else {
                                                $setSQL = new stmtSQL('INSERT', 'tbladdresstopublication', $SYSTEM_SETTINGS["Database"]);
                                            }
                                            $setSQL->addField('AddressID', 'integer', $addressid);
                                            $setSQL->addField($lti['linkfield'], 'integer', $data[$lti['linkfield']]);
                                            ExecuteSQL($setSQL);
                                        }
                                    } else {
                                        //Use the default contact address
                                        $sql = 
                                        "DELETE tbladdresstopublication, tbladdress
                                         FROM tbladdresstopublication
                                         LEFT JOIN tbladdress ON tbladdress.AddressID = tbladdresstopublication.AddressID
                                         WHERE {$lti['linkfield']} = ".$data[$lti['linkfield']];
                                        if (mysqli_query($SYSTEM_SETTINGS["Database"], $sql)) {
                                            AddHistory(array(
                                                'type' => 'delete', 'description' => 'Delivery address removed for '.$data['Title'],
                                                $lti['owneridfield'] => intval($_POST[$lti['owneridfield']])
                                            ), mysqli_affected_rows($SYSTEM_SETTINGS['Database']));
                                        }
                                    }
                                }
                            }
                        }
                        break;
                    case 'setavatar':
                        if (CheckRequiredParams(array('PersonID' => FALSE), $_POST)) {
                            $personid = intval($_POST['PersonID']);
                            if (preg_match('/data:([^;]*);base64,(.*)/', $_POST['imgData'], $matches))
                            {
                                $response['filename'] = $personid.'.jpg';
                                $response['path'] = 'img'.DIRECTORY_SEPARATOR.'avatar'.DIRECTORY_SEPARATOR;
                                $source = imagecreatefromstring(base64_decode($matches[2]));
                                $sourcewidth = imagesx($source);
                                if($sourcewidth >= 128)
                                {
                                    //If the source image is large enough, we can produce a x2 version
                                    $destination = imagecreatetruecolor(128, 128);
                                    imagecopyresampled($destination, $source, 0, 0, 0, 0, 64, 64, $sourcewidth, imagesy($source));
                                    $filename = $personid.'@2x.jpg';
                                    imagecopyresampled($destination, $source, 0, 0, 0, 0, 128, 128, $sourcewidth, imagesy($source));
                                    imagejpeg($destination, $response['path'].$filename, 100);
                                    $source = imagecreatefromstring(base64_decode($matches[2]));
                                    $sourcewidth = imagesx($source);
                                }
                                $destination = imagecreatetruecolor(64, 64);
                                imagecopyresampled($destination, $source, 0, 0, 0, 0, 64, 64, $sourcewidth, imagesy($source));
                                imagejpeg($destination, $response['path'].$response['filename'], 100);
                                AddHistory(array('type' => 'edit', 'description' => 'Avatar uploaded', 'PersonID' => $personid));
                                $response['success'] = TRUE;
                            } else {
                                $response['errormessage'] = "The request data was corrupted.";                                
                            }
                        };
                        break;
                    case 'clearavatar':
                        if (CheckRequiredParams(array('PersonID' => FALSE), $_POST)) {
                            $personid = intval($_POST['PersonID']);
                            $response['filename'] = $personid.'.jpg';
                            $response['path'] = 'img'.DIRECTORY_SEPARATOR.'avatar'.DIRECTORY_SEPARATOR;
                            unlink ( $response['path'].$response['filename'] );
                            unlink ( $response['path'].$personid.'@2x.jpg' );
                            AddHistory(array('type' => 'delete', 'description' => 'Avatar deleted', 'PersonID' => $personid));
                            $response['success'] = TRUE;
                        }
                        break;
                    case 'deletenotification':
//                        Authenticate();
                        if (CheckRequiredParams(array('notificationid' => FALSE), $_GET) && $AUTHENTICATION['Authenticated']) {
                            $sql = "DELETE FROM tblnotification WHERE NotificationID = ".intval(PREG_REPLACE("/[^0-9]/i", '', $_GET['notificationid']))." AND Token = '".$AUTHENTICATION['Token']."'";
                            if (mysqli_query($SYSTEM_SETTINGS["Database"], $sql)) {
                                $response['success'] = TRUE;
                            } else {
                                $response['errormessage'] =  mysqli_error($SYSTEM_SETTINGS["Database"]);
                                $response['errorcode'] = mysqli_errno($SYSTEM_SETTINGS["Database"]);
                            }
                        }
                        break;
                    case 'unlockportalpw':
                    case 'lockportalpw':
                        if (CheckRequiredParams(array('personid' => FALSE), $_GET) && $AUTHENTICATION['Authenticated']) {
                            $personid = intval($_GET['personid']);
                            $sql = "UPDATE tblperson SET PWFailCount = ".($do == 'lockportalpw' ? intval($SYSTEM_SETTINGS['Security']['MaxFailCount']) : 0) ." WHERE PersonID = {$personid}";
                            $response = ExecuteSQL($sql);
                            if($response['success']) {
                                AddHistory(array('type' => 'security', 'flags' => ($do == 'lockportalpw' ? 'warning' : 'success'), 'description' => 'Portal account '.($do == 'lockportalpw' ? 'locked' : 'unlocked'), 'author' => $AUTHENTICATION['Person']['PersonID'], 'PersonID' => $personid));
                            }
                        }
                        break;
                    case 'resetportalpw':
                        if (CheckRequiredParams(array('personid' => FALSE), $_GET) && $AUTHENTICATION['Authenticated']) {
                            $PERSON = new crmPerson('eventsPerson', $SYSTEM_SETTINGS['Database'], intval($_GET['personid']), $SYSTEM_SETTINGS["Membership"]);
                            $setSQL = new stmtSQL('UPDATE', 'tblperson', $SYSTEM_SETTINGS["Database"]);
                            $setSQL->addWhere('PersonID', 'integer', $PERSON->PersonID);
                            $NewPW = RandomString($SYSTEM_SETTINGS['Security']['MinPasswordLength']);
                            $PWHash = hash('sha512', strval($PERSON->PersonID).$NewPW, FALSE);
                            $setSQL->addField('PWHash', 'string', $PWHash);
                            $setSQL->addField('PWFailCount', 'integer', 0);
                            $setSQL->addFieldStmt('LastPWChanged', 'UTC_TIMESTAMP()');
                            $response = ExecuteSQL($setSQL);
                            if($response['success']) {
                                SendEmailTemplate(
                                    'users_pwreset',
                                    array($PERSON->GetRecord("personal", TRUE), array('Password' => $NewPW)),
                                    array('hide' => $NewPW)
                                );
                                AddHistory(array('type' => 'security', 'flags' => 'success', 'description' => 'Password for portal account reset', 'author' => $AUTHENTICATION['Person']['PersonID'], 'PersonID' => $PERSON->PersonID));
                            }
                        }                    
                        break;
                    case 'unlockaccount':
                    case 'lockaccount':
                        if (CheckRequiredParams(array('loginid' => FALSE, 'personid' => FALSE), $_GET) && $AUTHENTICATION['Authenticated']) {
                            $personid = intval($_GET['personid']);
                            $loginid = intval($_GET['loginid']);
                            $sql = "UPDATE tbllogin SET FailCount = ".($do == 'lockaccount' ? intval($SYSTEM_SETTINGS['Security']['MaxFailCount']) : 0) .", SuccessCount = 0 WHERE LoginID = {$loginid}";
                            $response = ExecuteSQL($sql);
                            if($response['success']) {
                                AddHistory(array('type' => 'security', 'flags' => ($do == 'lockaccount' ? 'danger' : 'success'), 'description' => 'System account '.($do == 'lockaccount' ? 'locked' : 'unlocked'), 'author' => $AUTHENTICATION['Person']['PersonID'], 'PersonID' => $personid));
                            }
                        }
                        break;
                    case 'createaccount':
                        if (CheckRequiredParams(array('personid' => FALSE), $_GET) && $AUTHENTICATION['Authenticated']) {
                            $personid = intval($_GET['personid']);
                            $sql = 
                            "SELECT tblperson.PersonID, GROUP_CONCAT(tblemail.Email SEPARATOR ';') AS `Emails`,
                                    tbllogin.LoginID
                             FROM tblperson
                             LEFT JOIN tbllogin ON tbllogin.PersonID = tblperson.PersonID
                             LEFT JOIN tblemail ON tblemail.PersonID = tblperson.PersonID
                             WHERE tblperson.PersonID = {$personid}
                             GROUP BY tblperson.PersonID";
                            $data = SingleRecord($SYSTEM_SETTINGS["Database"], $sql);
                            if(empty($data['LoginID'])) {
                                if(!empty($data['Emails'])) {
                                    $setSQL = new stmtSQL('INSERT', 'tbllogin', $SYSTEM_SETTINGS["Database"]);
                                    $setSQL->addField('PersonID', 'integer', $personid);
                                    $Salt = safe_b64encode(mcrypt_create_iv(48, MCRYPT_DEV_URANDOM));
                                    $setSQL->addField('Salt', 'string', $Salt);
                                    $NewPW = RandomString($SYSTEM_SETTINGS['Security']['MinPasswordLength']);
                                    $PWHash = hash('sha512', $Salt.$NewPW, FALSE);
                                    $setSQL->addField('PWHash', 'string', $PWHash);
                                    $response = ExecuteSQL($setSQL);
                                    if($response['success']) {
                                        if(defined('__DEBUGMODE') && __DEBUGMODE) {
                                            file_put_contents(IncTrailingPathDelimiter(sys_get_temp_dir())."accounts.txt", $data['Emails'].'='.$NewPW."\r\n", FILE_APPEND);
                                        }
                                        AddHistory(array('type' => 'security', 'flags' => 'success', 'description' => 'System account created', 'author' => $AUTHENTICATION['Person']['PersonID'], 'PersonID' => $personid));
                                    }
                                } else {
                                    $response['errormessage'] = 'This record does not have an email address.';
                                    $response['errorcode'] = 2;
                                }
                            } else {
                                $response['errormessage'] = 'An account for this record already exists.';
                                $response['errorcode'] = 1;
                            }
                        }
                        break;
                    case 'startapplication':
                        if (CheckRequiredParams(array('SelectorID' => FALSE), $_POST) && $AUTHENTICATION['Authenticated']) {
                            if(!empty($_POST['PersonID'])) {
                                //Start an application for a person
                                $selector = IdentifierStr($_POST['SelectorID']);
                                $personid = intval($_POST['PersonID']);
                                $msgradeid = intval($_POST['MSGradeID']);
                                $sql = 
                                "SELECT tblwscategory.WSCategoryID, tblwscategory.CategoryName, tblapplicationstage.ApplicationStageID, tblapplicationstage.StageName,
                                        tblmsgrade.MSGradeID, tblmsgrade.GradeCaption
                                 FROM tblapplicationstage
                                 LEFT JOIN tblwscategory ON tblwscategory.CategorySelector = '{$selector}'
                                 LEFT JOIN tblmsgrade ON tblmsgrade.MSGradeID = {$msgradeid}
                                 ORDER BY tblapplicationstage.StageOrder
                                 LIMIT 1
                                ";
                                $appsettings = SingleRecord($SYSTEM_SETTINGS['Database'], $sql);
                                if(!empty($appsettings)) {
                                    $sql = "UPDATE tblperson SET ISO4217 = '".IdentifierStr($_POST['ISO4217'])."' WHERE PersonID = {$personid}";
                                    $response = ExecuteSQL($sql);
                                    AddHistory(array('type' => 'edit', 'description' => 'Currency changed to '.$_POST['ISO4217'], 'author' => $AUTHENTICATION['Person']['PersonID'], 'PersonID' => $personid), $response['_affectedrows']);
                                    if($response['success']) {
                                        //Get signalled discount
                                        if(!empty($_POST['DiscountToPersonID'])) {
                                            //Discount already on file for this record
                                            $discountID = intval($_POST['DiscountID']);
                                        } else {
                                            //New discount not yet on the record, so add it
                                            $discountID = intval($_POST['DiscountID']);
                                            $sql =
                                            "INSERT INTO tbldiscounttoperson (DiscountID, PersonID, RefCount, Expires)
                                             SELECT tbldiscount.DiscountID, {$personid}, tbldiscount.RefCount, DATE_ADD(UTC_TIMESTAMP, INTERVAL {$SYSTEM_SETTINGS['Finance']['RecordDiscountCodeExpiry']} DAY)
                                             FROM tbldiscount
                                             WHERE tbldiscount.DiscountID = {$discountID}
                                            ";
                                            mysqli_query($SYSTEM_SETTINGS["Database"], $sql);
                                        }
                                        //Check if any applicable discount code has been added to this person record
                                        $sql = 
                                        "INSERT INTO tblapplication (WSCategoryID, ApplicationStageID, MSGradeID, `Created`, LastModified, DiscountID, NOY)
                                         VALUES ({$appsettings['WSCategoryID']}, {$appsettings['ApplicationStageID']}, {$appsettings['MSGradeID']}, UTC_TIMESTAMP(), UTC_TIMESTAMP(), ".(!empty($discountID) ? $discountID : 'NULL').", ".(max(1, intval($_POST['NOY']))).")
                                        ";
                                        $response = ExecuteSQL($sql);
                                        if($response['success']) {
                                            $response['applicationid'] = mysqli_insert_id( $SYSTEM_SETTINGS["Database"]);
                                            $response['personid'] = $personid;
                                            $sql = "INSERT INTO tblapplicationtoperson (ApplicationID, PersonID) VALUES ({$response['applicationid']}, {$personid})";
                                            if (mysqli_query($SYSTEM_SETTINGS["Database"], $sql)) {
                                                $application = new crmApplication($SYSTEM_SETTINGS['Database'], $response['applicationid'], $SYSTEM_SETTINGS['Membership']);
                                                AddHistory(array('type' => 'edit', 'description' => $application->Application['CategoryName'].' application started: '.$application->Application['GradeCaption'].(!empty($application->Application['DiscountID']) ? ", discount code ".$application->Application['DiscountCode'] : ""), 'author' => $AUTHENTICATION['Person']['PersonID'], 'PersonID' => $personid));
                                                AdjustDiscountRefCount(array('PersonID' => $personid, 'DiscountID' => $discountID));
                                            } else {
                                                $response['success'] = FALSE;
                                                $response['errormessage'] =  mysqli_error($SYSTEM_SETTINGS["Database"]);
                                                $response['errorcode'] = mysqli_errno($SYSTEM_SETTINGS["Database"]);                                        
                                            }
                                        }
                                    }
                                } else {
                                    $response['errormessage'] = 'Unable to determine new application parameters.';
                                    $response['errorcode'] = 1;                                    
                                }
                            }
                        }
                        break;
                    case 'starttransfer':
                        if (CheckRequiredParams(array('PersonID' => FALSE, 'MSGradeID' => FALSE, 'NewMSGradeID' => FALSE, 'SelectorID' => FALSE), $_POST) && $AUTHENTICATION['Authenticated']) {
                            try {
                                if(StartTransaction($SYSTEM_SETTINGS["Database"])) {
                                    $selector = IdentifierStr($_POST['SelectorID']);
                                    $personid = intval($_POST['PersonID']);
                                    $msgradeid = intval($_POST['MSGradeID']);
                                    $newmsgradeid = intval($_POST['NewMSGradeID']);
                                    $sql = 
                                    "SELECT tblwscategory.WSCategoryID, tblwscategory.CategoryName,
                                            tblmsgrade.MSGradeID, tblmsgrade.GradeCaption,
                                            tblnewmsgrade.MSGradeID AS `NewMSGradeID`, tblnewmsgrade.GradeCaption AS `NewGradeCaption`
                                     FROM tblwscategory
                                     LEFT JOIN tblmsgrade ON tblmsgrade.MSGradeID = {$msgradeid}
                                     LEFT JOIN tblmsgrade AS tblnewmsgrade ON tblnewmsgrade.MSGradeID = {$newmsgradeid}
                                     WHERE tblwscategory.CategorySelector = '{$selector}'
                                    ";
                                    $transfersettings = SingleRecord($SYSTEM_SETTINGS['Database'], $sql);
                                    $setSQL = new stmtSQL('INSERT', 'tbltransfer', $SYSTEM_SETTINGS["Database"]);
                                    $setSQL->addField('MSGradeID', 'integer', $msgradeid);
                                    $setSQL->addField('NewMSGradeID', 'integer', $newmsgradeid);
                                    $setSQL->addField('WSCategoryID', 'integer', $transfersettings['WSCategoryID']);
                                    $setSQL->addFieldStmt('Created', 'UTC_TIMESTAMP()');
                                    $setSQL->addFieldStmt('LastModified', 'UTC_TIMESTAMP()');
                                    $setSQL->addField('ISO4217', 'string', $_POST['ISO4217']);
                                    $setSQL->addField('ItemNet', 'money', $_POST['ItemNet']);
                                    $setSQL->addField('ItemVATRate', 'percent', $_POST['ItemVATRate']);
                                    $response = ExecuteSQL($setSQL->SQL());
                                    if($response['success']) {
                                        $response['transferid'] = mysqli_insert_id( $SYSTEM_SETTINGS["Database"]);
                                        $sql = "INSERT INTO tbltransfertoperson (TransferID, PersonID) VALUES ({$response['transferid']}, {$personid})";
                                        if (mysqli_query($SYSTEM_SETTINGS["Database"], $sql)) {
                                            $TRANSFER = new crmTransfer($SYSTEM_SETTINGS["Database"], $response['transferid']);
                                            //file_put_contents("D:\\temp\\rejoin.txt", print_r($TRANSFER, TRUE));
                                            AddHistory(array('type' => 'edit', 'description' => $transfersettings['CategoryName'].' transfer started: '.$transfersettings['NewGradeCaption'], 'author' => $AUTHENTICATION['Person']['PersonID'], 'PersonID' => $personid));
                                            if($TRANSFER->Transfer['ItemNet'] <> 0) {
                                                CreateTransferTransaction($TRANSFER->Transfer);
                                            } else {
                                                //Complete the rejoin
                                                CompleteMSTransfer($TRANSFER);
                                            }
                                        } else {
                                            throw new crmException('Unable to create transfer to person record: '.mysqli_error($SYSTEM_SETTINGS["Database"]), mysqli_errno($SYSTEM_SETTINGS["Database"]));
                                        }
                                    }
                                    mysqli_commit($SYSTEM_SETTINGS["Database"]);
                                } else {
                                    throw new crmException('Unable to start transaction: '.mysqli_error($SYSTEM_SETTINGS["Database"]), mysqli_errno($SYSTEM_SETTINGS["Database"]));
                                }
                            } catch ( Exception $e ) {
                                mysqli_rollback($SYSTEM_SETTINGS["Database"]);
                                $response['success'] = FALSE;
                                $response['errormessage'] = $e->getMessage();
                                $response['errorcode'] = $e->getCode();                            
                                AddToSysLog(array('EntryKind' => 'error', 'IsSystem' => TRUE, 'Expiry' => $SYSTEM_SETTINGS['ExpiryPolicies']['LogErrors'], 'Caption' => 'Transfer Error', 'Description' => $response['errormessage'].' ('.$response['errorcode'].')', 'Data' => array('POST' => print_r($_POST, TRUE))));
                            }
                        }
                        break;
                    case 'startrejoin':
                        if (CheckRequiredParams(array('PersonID' => FALSE, 'MSGradeID' => FALSE, 'SelectorID' => FALSE), $_POST) && $AUTHENTICATION['Authenticated']) {
                            try {
                                if(StartTransaction($SYSTEM_SETTINGS["Database"])) {
                                    $selector = IdentifierStr($_POST['SelectorID']);
                                    $personid = intval($_POST['PersonID']);
                                    $msgradeid = intval($_POST['MSGradeID']);
                                    $sql = 
                                    "SELECT tblwscategory.WSCategoryID, tblwscategory.CategoryName,
                                            tblmsgrade.MSGradeID, tblmsgrade.GradeCaption
                                     FROM tblwscategory
                                     LEFT JOIN tblmsgrade ON tblmsgrade.MSGradeID = {$msgradeid}
                                     WHERE tblwscategory.CategorySelector = '{$selector}'
                                    ";
                                    $rejoinsettings = SingleRecord($SYSTEM_SETTINGS['Database'], $sql);
                                    $setSQL = new stmtSQL('INSERT', 'tblrejoin', $SYSTEM_SETTINGS["Database"]);
                                    $setSQL->addField('MSGradeID', 'integer', $msgradeid);
                                    $setSQL->addField('WSCategoryID', 'integer', $rejoinsettings['WSCategoryID']);
                                    $setSQL->addFieldStmt('Created', 'UTC_TIMESTAMP()');
                                    $setSQL->addFieldStmt('LastModified', 'UTC_TIMESTAMP()');
                                    $setSQL->addField('MSNextRenewal', 'date', $_POST['MSNextRenewal']);
                                    $setSQL->addField('RejoinDate', 'datetime', $_POST['RejoinDate']);
                                    $setSQL->addField('ISO4217', 'string', $_POST['ISO4217']);
                                    $setSQL->addField('ItemNet', 'money', $_POST['ItemNet']);
                                    $setSQL->addField('ItemVATRate', 'percent', $_POST['ItemVATRate']);
                                    $setSQL->addField('AnchorPersonMSID', 'integer', $_POST['AnchorPersonMSID'], null, TRUE);
                                    $response = ExecuteSQL($setSQL->SQL());
                                    if($response['success']) {
                                        $response['rejoinid'] = mysqli_insert_id( $SYSTEM_SETTINGS["Database"]);
                                        $sql = "INSERT INTO tblrejointoperson (RejoinID, PersonID) VALUES ({$response['rejoinid']}, {$personid})";
                                        if (mysqli_query($SYSTEM_SETTINGS["Database"], $sql)) {
                                            $REJOIN = new crmRejoin($SYSTEM_SETTINGS["Database"], $response['rejoinid']);
                                            //file_put_contents("D:\\temp\\rejoin.txt", print_r($REJOIN, TRUE));
                                            AddHistory(array('type' => 'edit', 'description' => $rejoinsettings['CategoryName'].' rejoin started: '.$rejoinsettings['GradeCaption'], 'author' => $AUTHENTICATION['Person']['PersonID'], 'PersonID' => $personid));
                                            if($REJOIN->Rejoin['ItemNet'] <> 0) {
                                                CreateRejoinTransaction($REJOIN->Rejoin);
                                            } else {
                                                //Complete the rejoin
                                                CompleteMSRejoin($REJOIN);
                                            }
                                        } else {
                                            throw new crmException('Unable to create rejoin to person record: '.mysqli_error($SYSTEM_SETTINGS["Database"]), mysqli_errno($SYSTEM_SETTINGS["Database"]));
                                        }
                                    }
                                    mysqli_commit($SYSTEM_SETTINGS["Database"]);
                                } else {
                                    throw new crmException('Unable to start transaction: '.mysqli_error($SYSTEM_SETTINGS["Database"]), mysqli_errno($SYSTEM_SETTINGS["Database"]));
                                }
                            } catch ( Exception $e ) {
                                mysqli_rollback($SYSTEM_SETTINGS["Database"]);
                                $response['success'] = FALSE;
                                $response['errormessage'] = $e->getMessage();
                                $response['errorcode'] = $e->getCode();                            
                                AddToSysLog(array('EntryKind' => 'error', 'IsSystem' => TRUE, 'Expiry' => $SYSTEM_SETTINGS['ExpiryPolicies']['LogErrors'], 'Caption' => 'Rejoin Error', 'Description' => $response['errormessage'].' ('.$response['errorcode'].')', 'Data' => array('POST' => print_r($_POST, TRUE))));
                            }
                        }
                        break;
                    case 'completeelection':
                        if (CheckRequiredParams(array('ApplicationID' => FALSE), $_POST) && $AUTHENTICATION['Authenticated']) {
                            try {
                                if(StartTransaction($SYSTEM_SETTINGS["Database"])) {
                                    $msappmodel = new crmMSApplicationModel($SYSTEM_SETTINGS['Database'], $SYSTEM_SETTINGS['Membership']);
                                    $msapplication = $msappmodel->GetApplicationByID(intval($_POST['ApplicationID']));
                                    $PERSON = new crmPerson('eventsPerson', $SYSTEM_SETTINGS['Database'], $msapplication->Application['PersonID'], $SYSTEM_SETTINGS["Membership"]);
                                    $electionsettings = array(
                                        'MSGradeID' => $msapplication->Application['MSGradeID'],
                                        'ElectionDate' => $_POST['ElectionDate'],
                                        'NOY' => $msapplication->Application['NOY'],
                                    );
                                    if($msapplication->Application['Free']) {
                                        $electionsettings['Free'] = TRUE;
                                    }
                                    if(CompleteMSElection($PERSON, $electionsettings)) {
                                        $response['success'] = TRUE;
                                    } else {
                                        throw new crmException('Unable to complete election');
                                    }
                                    mysqli_commit($SYSTEM_SETTINGS["Database"]);
                                } else {
                                    throw new crmException('Unable to start transaction: '.mysqli_error($SYSTEM_SETTINGS["Database"]), mysqli_errno($SYSTEM_SETTINGS["Database"]));
                                }
                            } catch ( Exception $e ) {
                                mysqli_rollback($SYSTEM_SETTINGS["Database"]);
                                $response['success'] = FALSE;
                                $response['errormessage'] = $e->getMessage();
                                $response['errorcode'] = $e->getCode();                            
                                AddToSysLog(array('EntryKind' => 'error', 'IsSystem' => TRUE, 'Expiry' => $SYSTEM_SETTINGS['ExpiryPolicies']['LogErrors'], 'Caption' => 'Application Error', 'Description' => $response['errormessage'].' ('.$response['errorcode'].')', 'Data' => array('POST' => print_r($_POST, TRUE))));
                            }                            
                        }
                        break;
                    case 'changeappstage':
                        if (CheckRequiredParams(array('ApplicationID' => FALSE, 'ApplicationStageID' => FALSE), $_POST) && $AUTHENTICATION['Authenticated']) {
                            try {
                                if(StartTransaction($SYSTEM_SETTINGS["Database"])) {
                                    $application = new crmApplication($SYSTEM_SETTINGS["Database"], $_POST['ApplicationID'], $SYSTEM_SETTINGS["Membership"]);
                                    SetApplicationStage($application, $_POST['ApplicationStageID']);
/*                                    $sql =
                                    "SELECT tblapplication.ApplicationID, tblapplication.ApplicationStageID, tblapplication.Created, tblapplication.LastModified,
                                            tblapplication.Flags, FIND_IN_SET('paid', tblapplication.Flags) AS `Paid`, FIND_IN_SET('free', tblapplication.Flags) AS `Free`,
                                            (FIND_IN_SET('paid', tblapplication.Flags) AND FIND_IN_SET('directdebit', tblapplication.Flags)) AS `DDPaid`,
                                            IF(tblapplication.Cancelled IS NOT NULL, 0, tblapplication.IsOpen) AS `IsOpen`, tblapplication.Cancelled,
                                            COALESCE( tblinvoiceitem.DiscountID, tblapplication.DiscountID) AS `DiscountID`, GREATEST(tblapplication.NOY, 1) AS `NOY`,
	                                        tblmsgrade.MSGradeID, tblmsgrade.GradeCaption, tblmsgrade.AutoElect,
	                                        tblwscategory.WSCategoryID, tblwscategory.CategoryName, tblwscategory.CategoryIcon,
                                            tblperson.PersonID, tblperson.ISO3166, tblperson.ISO4217, tblddi.DDIID,
                                            tblcurrentapplicationstage.ApplicationStageID AS `CurrentApplicationStageID`, tblcurrentapplicationstage.StageOrder AS `CurrentStageOrder`, 
                                            tblcurrentapplicationstage.StageName AS `CurrentStageName`, tblcurrentapplicationstage.SubmissionStage AS `CurrentSubmissionStage`,
                                            tblcurrentapplicationstage.PaymentRequired AS `CurrentPaymentRequired`, tblcurrentapplicationstage.StageColour AS `CurrentStageColour`,
                                            tblcurrentapplicationstage.IsCompletionStage AS `CurrentIsCompletionStage`, tblcurrentapplicationstage.IsElectionStage AS `CurrentIsElectionStage`,
                                            tblcurrentapplicationstage.CategorySelector AS `CurrentCategorySelector`,
                                            tblnewapplicationstage.ApplicationStageID AS `NewApplicationStageID`, tblnewapplicationstage.StageOrder AS `NewStageOrder`, 
                                            tblnewapplicationstage.StageName AS `NewStageName`, tblnewapplicationstage.SubmissionStage AS `NewSubmissionStage`,
                                            tblnewapplicationstage.PaymentRequired AS `NewPaymentRequired`, tblnewapplicationstage.StageColour AS `NewStageColour`,
                                            tblnewapplicationstage.IsCompletionStage AS `NewIsCompletionStage`, tblnewapplicationstage.IsElectionStage AS `NewIsElectionStage`,
                                            COALESCE(tblnewapplicationstage.SubmissionStage, tblcurrentapplicationstage.SubmissionStage) AS `SubmissionStage`, 
                                            tblinvoiceitem.InvoiceItemID, tblinvoiceitem.LinkedID, tblinvoiceitem.ItemQty, tblinvoiceitem.ItemUnitPrice, tblinvoiceitem.ItemVATRate, tblinvoiceitem.ItemNet,
                                            tblinvoiceitem.ItemVAT, (tblinvoiceitem.ItemNet+tblinvoiceitem.ItemVAT) AS `ItemTotal`, tblinvoiceitem.ItemDate, tblinvoiceitem.Description, 
                                            tblinvoiceitem.CategorySelector, tblinvoiceitem.Mnemonic, tblinvoiceitem.InvoiceID, tblinvoiceitem.InvoiceDate, tblinvoiceitem.InvoiceDue,
                                            tblinvoiceitem.InvoiceCaption, tblinvoiceitem.InvoiceNo,
                                            IF(tblinvoiceitem.InvoiceItemID IS NOT NULL, 1, 0) AS `HasTransaction`, COALESCE(tblinvoiceitem.CanExplain, 0) AS `CanExplain`
                                     FROM bcscrm.tblapplication
                                     LEFT JOIN tblapplicationtoperson ON tblapplicationtoperson.ApplicationID = tblapplication.ApplicationID
                                     LEFT JOIN tblperson ON tblperson.PersonID = tblapplicationtoperson.PersonID
                                     LEFT JOIN tblmsgrade ON tblmsgrade.MSGradeID = tblapplication.MSGradeID
                                     LEFT JOIN tblapplicationstage AS tblcurrentapplicationstage ON tblcurrentapplicationstage.ApplicationStageID = tblapplication.ApplicationStageID
                                     LEFT JOIN tblapplicationstage AS tblnewapplicationstage ON tblnewapplicationstage.ApplicationStageID = ".intval($_POST['ApplicationStageID'])."
                                     LEFT JOIN tblwscategory ON tblwscategory.WSCategoryID = tblapplication.WSCategoryID
                                     LEFT JOIN (SELECT tblinvoiceitem.InvoiceItemID, tblinvoiceitem.LinkedID, tblinvoiceitem.ItemQty, tblinvoiceitem.ItemUnitPrice, tblinvoiceitem.ItemVATRate,
    	                                               tblinvoiceitem.ItemNet, tblinvoiceitem.ItemVAT, tblinvoiceitem.ItemDate, tblinvoiceitem.DiscountID,
                                                       tblinvoice.InvoiceNo,
                                                       IF(tblinvoiceitem.`Explain` IS NOT NULL, 1, 0) AS `CanExplain`,
                                                       COALESCE(tblinvoiceitem.Description, tblinvoiceitemtype.TypeName) AS `Description`,
                                                       tblinvoiceitemtype.CategorySelector, tblinvoiceitemtype.Mnemonic,
                                                       tblinvoice.InvoiceID, tblinvoice.ISO4217, tblinvoice.InvoiceDate, tblinvoice.InvoiceDue,
	   		                                           CONCAT_WS(' ', IF(tblinvoice.InvoiceNo IS NULL, IF(tblinvoice.InvoiceType = 'creditnote', 'Draft', 'Pro Forma'), ''),
                                                                      IF(tblinvoice.InvoiceType = 'creditnote', 'Credit Note', IF(tblinvoice.InvoiceNo IS NULL, CONCAT('#', CAST(tblinvoice.InvoiceID AS CHAR)), CONCAT('Invoice ', tblinvoice.InvoiceNo)))
                                                       ) AS `InvoiceCaption`
		                                        FROM tblinvoiceitem
                                                INNER JOIN tblinvoice ON (tblinvoice.InvoiceID = tblinvoiceitem.InvoiceID) AND (tblinvoice.InvoiceType = 'invoice')
                                                INNER JOIN tblinvoiceitemtype ON (tblinvoiceitemtype.InvoiceItemTypeID = tblinvoiceitem.InvoiceItemTypeID)
                                     ) AS `tblinvoiceitem` ON (tblinvoiceitem.Mnemonic = 'ms_new') AND (tblinvoiceitem.LinkedID = tblapplication.ApplicationID) 
                                     LEFT JOIN tblddi ON (tblddi.PersonID = tblperson.PersonID) AND (tblperson.ISO4217 = 'GBP') AND (tblddi.InstructionScope = tblcurrentapplicationstage.CategorySelector) AND ".CondValidDDI()."
                                     WHERE tblapplication.ApplicationID = ".intval($_POST['ApplicationID']);
                                    $application = SingleRecord($SYSTEM_SETTINGS['Database'], $sql);
                                    if(!empty($application)) {
                                        if($application['IsOpen']) {
                                            $sql = "UPDATE tblapplication SET ApplicationStageID = {$application['NewApplicationStageID']}, LastModified = UTC_TIMESTAMP() WHERE ApplicationID = {$application['ApplicationID']}";
                                            $response = ExecuteSQL($sql);
                                            if($response['success']) {
                                                if($application['NewSubmissionStage'] >= 0) {
                                                    CreateApplicationTransaction($application);
                                                    if($SYSTEM_SETTINGS['Membership']['EarlyMSNumberAssign']) {
                                                        AssignMSNumber($application['PersonID']);
                                                    }
                                                }
                                                if(($application['NewIsCompletionStage']) && ($application['AutoElect'])) {
                                                    $electionsettings = array(
                                                        'MSGradeID' => $application['MSGradeID'],
                                                        'NOY' => $application['NOY'],
                                                    );
                                                    if($application['Free']) {
                                                        $electionsettings['Free'] = TRUE;
                                                    }
                                                    if(CompleteMSElection($application['PersonID'], $electionsettings)) {
                
                                                    } else {
                                                        throw new crmException('Unable to complete auto-election');
                                                    }
                                                }*/
/*                                                if(($application['NewSubmissionStage'] >= 0) && (!$application['HasTransaction']) && (!$application['Paid'])) {
                                                    //Create a transaction for this application
                                                    $msfees = new crmMSFees($SYSTEM_SETTINGS['Database'], $SYSTEM_SETTINGS['Membership']['GradeCaption']);
                                                    $params = array(
                                                        'ISO4217' => $application['ISO4217'],
                                                        'MSGradeID' => $application['MSGradeID'],
                                                        'ISO3166' => $application['ISO3166'],
                                                        'IsDD' => (!empty($application['DDIID'])),
                                                        'DiscountID' => (!empty($application['DiscountID']) ? $application['DiscountID'] : null),
                                                    );
                                                    $fee = $msfees->CalculateFee($params);
                                                    if(!$fee->HasError) {
                                                        if($fee->Net <> 0) {
                                                            $INVOICE = GetProForma(array('ISO4217' => $application['ISO4217'], 'PersonID' => $application['PersonID']));
                                                            $INVOICE->NewItem(array(
                                                                'Mnemonic' => 'ms_new',
                                                                'LinkedID' => $application['ApplicationID'],
                                                                'ItemNet' => $fee->Net,
                                                                'ItemVATRate' => $fee->VATRate,
                                                                'DiscountID' => (!empty($fee->Discount->Net) ? $application['DiscountID'] : null),
                                                                'Description' => 'Joining Fee, '.$application['GradeCaption'],
                                                                'Explain' => json_encode($fee->Explanation),
                                                            ));
                                                        } else {
                                                            //No fee required - mark as paid
                                                            $sql = "UPDATE tblapplication SET Flags = CONCAT_WS(',', Flags, 'paid') WHERE ApplicationID = {$application['ApplicationID']}";
                                                            if (mysqli_query($SYSTEM_SETTINGS['Database'], $sql) === FALSE) {
                                                                throw new crmException('Unable to mark the application as paid.', 1);
                                                            }
                                                        }
                                                    } else {
                                                        throw new crmException('Unable to calculate the fee due.', 1);
                                                    }
                                                }*/
/*                                                if(!empty($application['PersonID'])) {
                                                    AddHistory(array('type' => 'edit', 'description' => $application['CategoryName'].' application status changed to '.$application['NewStageName'], 'author' => $AUTHENTICATION['Person']['PersonID'], 'PersonID' => $application['PersonID']), $response['_affectedrows']);
                                                }
                                            }
                                            if(!$response['success']) {
                                                throw new crmException('Unable to change application status: '.$response['errormessage'], $response['errorcode']);
                                            }
                                        } else {
                                            $response['errormessage'] = 'Unable to change status: this application is closed.';
                                            $response['errorcode'] = 1;                                      
                                        }
                                    }*/
                                    mysqli_commit($SYSTEM_SETTINGS["Database"]);
                                    $response['success'] = TRUE;
                                } else {
                                    throw new crmException('Unable to start transaction: '.mysqli_error($SYSTEM_SETTINGS["Database"]), mysqli_errno($SYSTEM_SETTINGS["Database"]));
                                }
                            } catch ( Exception $e ) {
                                mysqli_rollback($SYSTEM_SETTINGS["Database"]);
                                $response['success'] = FALSE;
                                $response['errormessage'] = $e->getMessage();
                                $response['errorcode'] = $e->getCode();                            
                                AddToSysLog(array('EntryKind' => 'error', 'IsSystem' => TRUE, 'Expiry' => $SYSTEM_SETTINGS['ExpiryPolicies']['LogErrors'], 'Caption' => 'Application Error', 'Description' => $response['errormessage'].' ('.$response['errorcode'].')', 'Data' => array('POST' => print_r($_POST, TRUE))));
                            }
                        }
                        break;
                    case 'saveproposerreferee':
                        if (CheckRequiredParams(array('ApplicationID' => FALSE), $_POST) && $AUTHENTICATION['Authenticated']) {
                            $msappmodel = new crmMSApplicationModel($SYSTEM_SETTINGS['Database'], $SYSTEM_SETTINGS['Membership']);
                            $msapplication = $msappmodel->GetApplicationByID(intval($_POST['ApplicationID']));
                            if($msapplication->Application['IsOpen']) {
                                $setSQL = new stmtSQL('UPDATE', 'tblapplication', $SYSTEM_SETTINGS["Database"]);
                                $setSQL->addWhere('ApplicationID', 'integer', $msapplication->Application['ApplicationID']);
                                foreach(array(
                                    'RefereeTypeID' => 'integer',
                                    'RefereeMSNumber' => 'string', 'RefereeEmail' => 'email', 'RefereeName' => 'string', 'RefereeAffiliation' => 'string',
                                    'ProposerMSNumber' => 'string', 'ProposerEmail' => 'email', 'ProposerName' => 'string', 'ProposerAffiliation' => 'string',
                                ) AS $fieldname => $fieldtype) {
                                    if(isset($_POST[$fieldname])) {
                                        $setSQL->addField($fieldname, $fieldtype, $_POST[$fieldname], null, TRUE);
                                    }
                                }
                                $response = ExecuteSQL($setSQL);
                                if($response['success']) {
                                    $name = (isset($msapplication->Application['ApplComponents']['referee']) ? 'Referee' : 'Proposer');
                                    AddHistory(array('type' => 'edit', 'description' => $msapplication->Application['CategoryName']." application: {$name} updated", 'author' => $AUTHENTICATION['Person']['PersonID'], 'PersonID' => $msapplication->Application['PersonID']), $response['_affectedrows']);
                                }
                            } else {
                                $response['errormessage'] = 'Unable to save changes: this application is closed.';
                                $response['errorcode'] = 1;                                      
                            }
                        }                    
                        break;
                    case 'changeapplication':
                        if (CheckRequiredParams(array('ApplicationID' => FALSE), $_POST) && $AUTHENTICATION['Authenticated']) {
                            try {
                                if(StartTransaction($SYSTEM_SETTINGS["Database"])) {
                                    $application = new crmApplication($SYSTEM_SETTINGS['Database'], $_POST['ApplicationID'], $SYSTEM_SETTINGS['Membership']);
                                    if($application->Found) {
                                        if($application->Application['IsOpen']) {
                                            $setSQL = new stmtSQL('UPDATE', 'tblapplication', $SYSTEM_SETTINGS["Database"]);
                                            $setSQL->addWhere('ApplicationID', 'integer', $application->ApplicationID);
                                            $setSQL->addField('ApplicationStageID', 'integer', $_POST['ApplicationStageID']);
                                            $setSQL->addField('MSGradeID', 'integer', $_POST['MSGradeID']);
                                            $setSQL->addField('DiscountID', 'integer', $_POST['DiscountID'], null, TRUE);
                                            $noy = max(1, intval($_POST['NOY']));
                                            $setSQL->addField('NOY', 'integer', $noy);
                                            $setSQL->addFieldStmt('LastModified', 'UTC_TIMESTAMP()');
                                            if(!$application->Application['HasTransaction']) {
                                                if(empty($_POST['Paid'])) {
                                                    $setSQL->addFieldStmt('Flags', "TRIM(BOTH ',' FROM REPLACE(CONCAT(',', Flags, ','), CONCAT(',', 'paid', ','), ','))");
                                                } else {
                                                    $setSQL->addFieldStmt('Flags', "CONCAT_WS(',', Flags, 'paid')");
                                                }
                                            }
                                            $response = ExecuteSQL($setSQL);
                                            if($response['success']) {
                                                $discountchanged = ($application->Application['DiscountID'] <> (empty($_POST['DiscountID']) ? null : intval($_POST['DiscountID'])));
                                                if($discountchanged) {
                                                    if(!empty($application->Application['DiscountID'])) {
                                                        //Remove the discount code previously attached;
                                                        $sql = "DELETE FROM tbldiscounttoperson WHERE (tbldiscounttoperson.DiscountID = {$application->Application['DiscountID']}) AND (tbldiscounttoperson.PersonID = {$application->Application['PersonID']})";
                                                        mysqli_query($SYSTEM_SETTINGS['Database'], $sql);
                                                    }
                                                    //Attach the new code
                                                    if(!empty($_POST['DiscountID'])) {
                                                        $sql =
                                                        "INSERT INTO tbldiscounttoperson (DiscountID, PersonID, RefCount, Expires)
                                                        SELECT tbldiscount.DiscountID, {$application->Application['PersonID']}, tbldiscount.RefCount-1, DATE_ADD(UTC_TIMESTAMP, INTERVAL {$SYSTEM_SETTINGS['Finance']['RecordDiscountCodeExpiry']} DAY)
                                                        FROM tbldiscount
                                                        WHERE tbldiscount.DiscountID = ".intval($_POST['DiscountID']);
                                                        mysqli_query($SYSTEM_SETTINGS["Database"], $sql);
                                                    }
                                                }
                                                if(!empty($application->Application['PersonID'])) {
                                                    AddHistory(array('type' => 'edit', 'description' => $application->Application['CategoryName'].' application modified', 'author' => $AUTHENTICATION['Person']['PersonID'], 'PersonID' => $application->Application['PersonID']), $response['_affectedrows']);
                                                }
                                                if($application->Application['HasTransaction'] && (($application->Application['MSGradeID'] <> intval($_POST['MSGradeID'])) || $discountchanged || ($application->Application['MSGradeID'] <> $noy)) ) {
                                                    CancelInvoiceItem($application->Application['InvoiceItemID']);
                                                    if($application->Application['Paid'] && (empty($application->Application['DDPaid']))) {
                                                        $setSQL = new stmtSQL('UPDATE', 'tblapplication', $SYSTEM_SETTINGS["Database"]);
                                                        $setSQL->addWhere('ApplicationID', 'integer', $application->ApplicationID);
                                                        $setSQL->addFieldStmt('Flags', "TRIM(BOTH ',' FROM REPLACE(CONCAT(',', Flags, ','), CONCAT(',', 'paid', ','), ','))");
                                                        $setSQL->addField('NOY', 'integer', $noy);
//file_put_contents("d:\\temp\\clearpaidflag.txt", $setSQL->SQL());
                                                        $response = ExecuteSQL($setSQL);
                                                        if(!$response['success']) {
                                                            throw new crmException('Unable to clear paid flag.', 3);
                                                        }
                                                    }
                                                }
                                                $application->Reload();
                                                if($discountchanged && !empty($application->Application['PersonID'])) {
                                                    AddHistory(array('type' => 'edit', 'description' => 'Discount '.(empty($application->Application['DiscountID']) ? 'removed from ' : 'code '.$application->Application['DiscountCode'].' applied to ').$application->Application['CategoryName'].' application', 'author' => $AUTHENTICATION['Person']['PersonID'], 'PersonID' => $application->Application['PersonID']), $response['_affectedrows']);
                                                }
                                                CreateApplicationTransaction($application->Application);
                                            } else {
                                                throw new crmException('SQL error: '.$response['errormessage'], $response['errorcode']);
                                            }
                                        } else {
                                            $response['errormessage'] = 'This application is closed.';
                                            $response['errorcode'] = 2;                                            
                                        }
                                    } else {
                                        $response['errormessage'] = 'Application not found.';
                                        $response['errorcode'] = 1;                                        
                                    }
                                    if(!$response['success']) {
                                        throw new crmException('Unable to change application: '.$response['errormessage'], $response['errorcode']);
                                    }
                                    mysqli_commit($SYSTEM_SETTINGS["Database"]);
                                } else {
                                    throw new crmException('Unable to start transaction: '.mysqli_error($SYSTEM_SETTINGS["Database"]), mysqli_errno($SYSTEM_SETTINGS["Database"]));
                                }
                            } catch ( Exception $e ) {
                                mysqli_rollback($SYSTEM_SETTINGS["Database"]);
                                $response['success'] = FALSE;
                                $response['errormessage'] = $e->getMessage();
                                $response['errorcode'] = $e->getCode();                            
                                AddToSysLog(array('EntryKind' => 'error', 'IsSystem' => TRUE, 'Expiry' => $SYSTEM_SETTINGS['ExpiryPolicies']['LogErrors'], 'Caption' => 'Application Error', 'Description' => $response['errormessage'].' ('.$response['errorcode'].')', 'Data' => array('POST' => print_r($_POST, TRUE))));
                            }
/*                            $sql = 
                            "SELECT tblapplication.ApplicationID, tblapplication.ApplicationStageID, tblapplication.Created, tblapplication.LastModified,
                                    tblapplication.Flags, FIND_IN_SET('paid', tblapplication.Flags) AS `Paid`,
                                    IF(tblapplication.Cancelled IS NOT NULL, 0, tblapplication.IsOpen) AS `IsOpen`, tblapplication.Cancelled,
	                                tblmsgrade.MSGradeID, tblmsgrade.GradeCaption,
	                                tblwscategory.WSCategoryID, tblwscategory.CategoryName, tblwscategory.CategoryIcon,
                                    tblperson.PersonID
                             FROM bcscrm.tblapplication
                             LEFT JOIN tblapplicationtoperson ON tblapplicationtoperson.ApplicationID = tblapplication.ApplicationID
                             LEFT JOIN tblperson ON tblperson.PersonID = tblapplicationtoperson.PersonID
                             LEFT JOIN tblmsgrade ON tblmsgrade.MSGradeID = tblapplication.MSGradeID
                             LEFT JOIN tblapplicationstage ON tblapplicationstage.ApplicationStageID = tblapplication.ApplicationStageID
                             LEFT JOIN tblwscategory ON tblwscategory.WSCategoryID = tblapplication.WSCategoryID
                             WHERE tblapplication.ApplicationID = ".intval($_POST['ApplicationID']);
                            $application = SingleRecord($SYSTEM_SETTINGS['Database'], $sql);
                            if(!empty($application)) {
                                if($application['IsOpen']) {
                                    $setSQL = new stmtSQL('UPDATE', 'tblapplication', $SYSTEM_SETTINGS["Database"]);
                                    $setSQL->addWhere('ApplicationID', 'integer', $application['ApplicationID']);
                                    $setSQL->addField('ApplicationStageID', 'integer', $_POST['ApplicationStageID']);
                                    $setSQL->addField('MSGradeID', 'integer', $_POST['MSGradeID']);
                                    $setSQL->addFieldStmt('LastModified', 'UTC_TIMESTAMP()');
                                    if(empty($_POST['Paid'])) {
                                        $setSQL->addFieldStmt('Flags', "TRIM(BOTH ',' FROM REPLACE(CONCAT(',', Flags, ','), CONCAT(',', 'paid', ','), ','))");
                                    } else {
                                        $setSQL->addFieldStmt('Flags', "CONCAT_WS(',', Flags, 'paid')");
                                    }
                                    $response = ExecuteSQL($setSQL);
                                    if($response['success']) {
                                        AddHistory(array('type' => 'edit', 'description' => $application['CategoryName'].' application settings updated', 'author' => $AUTHENTICATION['Person']['PersonID'], 'PersonID' => $application['PersonID']), $response['_affectedrows']);
                                    }
                                } else {
                                    $response['errormessage'] = 'Unable to change status: this application is closed.';
                                    $response['errorcode'] = 1;
                                }
                            }*/
                        }
                        break;
                    case 'cancelrejoin':
                        if (CheckRequiredParams(array('RejoinID' => FALSE), $_POST) && $AUTHENTICATION['Authenticated']) {
                            try {
                                if(StartTransaction($SYSTEM_SETTINGS["Database"])) {
                                    $REJOIN = new crmRejoin($SYSTEM_SETTINGS['Database'], $_POST['RejoinID']);
                                    if($REJOIN->Found) {
                                        if($REJOIN->Rejoin['IsOpen']) {
                                            $setSQL = new stmtSQL('UPDATE', 'tblrejoin', $SYSTEM_SETTINGS["Database"]);
                                            $setSQL->addWhere('RejoinID', 'integer', $REJOIN->Rejoin['RejoinID']);
                                            $setSQL->addField('IsOpen', 'integer', 0);
                                            $setSQL->addFieldStmt('Cancelled', 'UTC_TIMESTAMP()');
                                            $setSQL->addFieldStmt('LastModified', 'UTC_TIMESTAMP()');
                                            $response = ExecuteSQL($setSQL);
                                            if($response['success']) {
                                                SaveNote($REJOIN->Rejoin);
                                                if(!empty($REJOIN->Rejoin['PersonID'])) {
                                                    AddHistory(array('type' => 'delete', 'flags' => 'danger', 'description' => $REJOIN->Rejoin['CategoryName'].' rejoin cancelled', 'author' => $AUTHENTICATION['Person']['PersonID'], 'PersonID' => $REJOIN->Rejoin['PersonID']), $response['_affectedrows']);
                                                }
                                                if($REJOIN->Rejoin['HasTransaction']) {
                                                    CancelInvoiceItem($REJOIN->Rejoin['InvoiceItemID']);
                                                }
                                            } else {
                                                throw new crmException('SQL error: '.$response['errormessage'], $response['errorcode']);                                                
                                            }
                                        } else {
                                            $response['errormessage'] = 'This rejoin request is closed.';
                                            $response['errorcode'] = 2;                                            
                                        }                                        
                                    } else {
                                        $response['errormessage'] = 'Rejoin request not found.';
                                        $response['errorcode'] = 1;                                        
                                    }
                                    if(!$response['success']) {
                                        throw new crmException('Unable to cancel rejoin: '.$response['errormessage'], $response['errorcode']);
                                    }
                                    mysqli_commit($SYSTEM_SETTINGS["Database"]);
                                } else {
                                    throw new crmException('Unable to start transaction: '.mysqli_error($SYSTEM_SETTINGS["Database"]), mysqli_errno($SYSTEM_SETTINGS["Database"]));
                                }
                            } catch ( Exception $e ) {
                                mysqli_rollback($SYSTEM_SETTINGS["Database"]);
                                $response['success'] = FALSE;
                                $response['errormessage'] = $e->getMessage();
                                $response['errorcode'] = $e->getCode();                            
                                AddToSysLog(array('EntryKind' => 'error', 'IsSystem' => TRUE, 'Expiry' => $SYSTEM_SETTINGS['ExpiryPolicies']['LogErrors'], 'Caption' => 'Rejoin Error', 'Description' => $response['errormessage'].' ('.$response['errorcode'].')', 'Data' => array('POST' => print_r($_POST, TRUE))));
                            }                            
                        }
                        break;
                    case 'canceltransfer':
                        if (CheckRequiredParams(array('TransferID' => FALSE), $_POST) && $AUTHENTICATION['Authenticated']) {
                            try {
                                if(StartTransaction($SYSTEM_SETTINGS["Database"])) {
                                    $TRANSFER = new crmTransfer($SYSTEM_SETTINGS['Database'], $_POST['TransferID']);
                                    if($TRANSFER->Found) {
                                        if($TRANSFER->Transfer['IsOpen']) {
                                            $setSQL = new stmtSQL('UPDATE', 'tbltransfer', $SYSTEM_SETTINGS["Database"]);
                                            $setSQL->addWhere('TransferID', 'integer', $TRANSFER->Transfer['TransferID']);
                                            $setSQL->addField('IsOpen', 'integer', 0);
                                            $setSQL->addFieldStmt('Cancelled', 'UTC_TIMESTAMP()');
                                            $setSQL->addFieldStmt('LastModified', 'UTC_TIMESTAMP()');
                                            $response = ExecuteSQL($setSQL);
                                            if($response['success']) {
                                                SaveNote($TRANSFER->Transfer);
                                                if(!empty($TRANSFER->Transfer['PersonID'])) {
                                                    AddHistory(array('type' => 'delete', 'flags' => 'danger', 'description' => $TRANSFER->Transfer['CategoryName'].' transfer cancelled', 'author' => $AUTHENTICATION['Person']['PersonID'], 'PersonID' => $TRANSFER->Transfer['PersonID']), $response['_affectedrows']);
                                                }
                                                if($TRANSFER->Transfer['HasTransaction']) {
                                                    CancelInvoiceItem($TRANSFER->Transfer['InvoiceItemID']);
                                                }
                                            } else {
                                                throw new crmException('SQL error: '.$response['errormessage'], $response['errorcode']);                                                
                                            }
                                        } else {
                                            $response['errormessage'] = 'This transfer request is closed.';
                                            $response['errorcode'] = 2;                                            
                                        }                                        
                                    } else {
                                        $response['errormessage'] = 'Transfer request not found.';
                                        $response['errorcode'] = 1;                                        
                                    }
                                    if(!$response['success']) {
                                        throw new crmException('Unable to cancel transfer: '.$response['errormessage'], $response['errorcode']);
                                    }
                                    mysqli_commit($SYSTEM_SETTINGS["Database"]);
                                } else {
                                    throw new crmException('Unable to start transaction: '.mysqli_error($SYSTEM_SETTINGS["Database"]), mysqli_errno($SYSTEM_SETTINGS["Database"]));
                                }
                            } catch ( Exception $e ) {
                                mysqli_rollback($SYSTEM_SETTINGS["Database"]);
                                $response['success'] = FALSE;
                                $response['errormessage'] = $e->getMessage();
                                $response['errorcode'] = $e->getCode();                            
                                AddToSysLog(array('EntryKind' => 'error', 'IsSystem' => TRUE, 'Expiry' => $SYSTEM_SETTINGS['ExpiryPolicies']['LogErrors'], 'Caption' => 'Transfer Error', 'Description' => $response['errormessage'].' ('.$response['errorcode'].')', 'Data' => array('POST' => print_r($_POST, TRUE))));
                            }                            
                        }
                        break;
                    case 'cancelapplication':
                        if (CheckRequiredParams(array('ApplicationID' => FALSE), $_POST) && $AUTHENTICATION['Authenticated']) {
                            try {
                                if(StartTransaction($SYSTEM_SETTINGS["Database"])) {
                                    $application = new crmApplication($SYSTEM_SETTINGS['Database'], $_POST['ApplicationID'], $SYSTEM_SETTINGS['Membership']);
                                    if($application->Found) {
                                        if($application->Application['IsOpen']) {
                                            $setSQL = new stmtSQL('UPDATE', 'tblapplication', $SYSTEM_SETTINGS["Database"]);
                                            $setSQL->addWhere('ApplicationID', 'integer', $application->ApplicationID);
                                            $setSQL->addField('IsOpen', 'integer', 0);
                                            $setSQL->addFieldStmt('Cancelled', 'UTC_TIMESTAMP()');
                                            $setSQL->addFieldStmt('LastModified', 'UTC_TIMESTAMP()');
                                            $response = ExecuteSQL($setSQL);
                                            if($response['success']) {
                                                SaveNote($application->Application);
                                                if(!empty($application->Application['PersonID'])) {
                                                    AddHistory(array('type' => 'delete', 'flags' => 'danger', 'description' => $application->Application['CategoryName'].' application cancelled', 'author' => $AUTHENTICATION['Person']['PersonID'], 'PersonID' => $application->Application['PersonID']), $response['_affectedrows']);
                                                }
                                                if($application->Application['HasTransaction']) {
                                                    CancelInvoiceItem($application->Application['InvoiceItemID']);
                                                }
                                            } else {
                                                throw new crmException('SQL error: '.$response['errormessage'], $response['errorcode']);
                                            }
                                        } else {
                                            $response['errormessage'] = 'This application is closed.';
                                            $response['errorcode'] = 2;                                            
                                        }
                                    } else {
                                        $response['errormessage'] = 'Application not found.';
                                        $response['errorcode'] = 1;                                        
                                    }
                                    if(!$response['success']) {
                                        throw new crmException('Unable to cancel application: '.$response['errormessage'], $response['errorcode']);
                                    }
                                    mysqli_commit($SYSTEM_SETTINGS["Database"]);
                                } else {
                                    throw new crmException('Unable to start transaction: '.mysqli_error($SYSTEM_SETTINGS["Database"]), mysqli_errno($SYSTEM_SETTINGS["Database"]));
                                }
                            } catch ( Exception $e ) {
                                mysqli_rollback($SYSTEM_SETTINGS["Database"]);
                                $response['success'] = FALSE;
                                $response['errormessage'] = $e->getMessage();
                                $response['errorcode'] = $e->getCode();                            
                                AddToSysLog(array('EntryKind' => 'error', 'IsSystem' => TRUE, 'Expiry' => $SYSTEM_SETTINGS['ExpiryPolicies']['LogErrors'], 'Caption' => 'Application Error', 'Description' => $response['errormessage'].' ('.$response['errorcode'].')', 'Data' => array('POST' => print_r($_POST, TRUE))));
                            }                            
/*                            $sql = 
                            "SELECT tblapplication.ApplicationID, tblapplication.ApplicationStageID, tblapplication.Created, tblapplication.LastModified,
                                    tblapplication.Flags, FIND_IN_SET('paid', tblapplication.Flags) AS `Paid`,
                                    IF(tblapplication.Cancelled IS NOT NULL, 0, tblapplication.IsOpen) AS `IsOpen`, tblapplication.Cancelled,
	                                tblmsgrade.MSGradeID, tblmsgrade.GradeCaption,
	                                tblwscategory.WSCategoryID, tblwscategory.CategoryName, tblwscategory.CategoryIcon,
                                    tblperson.PersonID
                             FROM bcscrm.tblapplication
                             LEFT JOIN tblapplicationtoperson ON tblapplicationtoperson.ApplicationID = tblapplication.ApplicationID
                             LEFT JOIN tblperson ON tblperson.PersonID = tblapplicationtoperson.PersonID
                             LEFT JOIN tblmsgrade ON tblmsgrade.MSGradeID = tblapplication.MSGradeID
                             LEFT JOIN tblapplicationstage ON tblapplicationstage.ApplicationStageID = tblapplication.ApplicationStageID
                             LEFT JOIN tblwscategory ON tblwscategory.WSCategoryID = tblapplication.WSCategoryID
                             WHERE tblapplication.ApplicationID = ".intval($_POST['ApplicationID']);
                            $application = SingleRecord($SYSTEM_SETTINGS['Database'], $sql);
                            if(!empty($application)) {
                                if($application['IsOpen']) {
                                    $setSQL = new stmtSQL('UPDATE', 'tblapplication', $SYSTEM_SETTINGS["Database"]);
                                    $setSQL->addWhere('ApplicationID', 'integer', $application['ApplicationID']);
                                    $setSQL->addField('IsOpen', 'integer', 0);
                                    $setSQL->addFieldStmt('Cancelled', 'UTC_TIMESTAMP()');
                                    $setSQL->addFieldStmt('LastModified', 'UTC_TIMESTAMP()');
                                    $response = ExecuteSQL($setSQL);
                                    SaveNote($application);
                                    if($response['success'] && !empty($application['PersonID'])) {
                                        AddHistory(array('type' => 'delete', 'flags' => 'danger', 'description' => $application['CategoryName'].' application cancelled', 'author' => $AUTHENTICATION['Person']['PersonID'], 'PersonID' => $application['PersonID']), $response['_affectedrows']);
                                    }
                                } else {
                                    $response['errormessage'] = 'Unable to cancel: this application is already closed.';
                                    $response['errorcode'] = 1;
                                }
                            }*/
                        }
                        break;
                    case 'changeflag':
                        if (CheckRequiredParams(array('table' => FALSE, 'fieldname' => FALSE, 'flag' => FALSE, 'value' => TRUE), $_POST) && $AUTHENTICATION['Authenticated']) {
                            $table = VarnameStr($_POST['table']);
                            $fieldname = VarnameStr($_POST['fieldname']);
                            $flag = VarnameStr($_POST['flag']);
                            $setSQL = new stmtSQL('UPDATE', $table, $SYSTEM_SETTINGS["Database"]);
                            $found = FALSE;
                            foreach(array('ApplicationID' => 'integer', 'PersonID' => 'integer', 'OrganisationID' => 'integer') AS $wherefield => $fieldtype) {
                                if(isset($_POST[$wherefield])) {
                                    $setSQL->addWhere($wherefield, $fieldtype, $_POST[$wherefield]);
                                    $found = TRUE;
                                    break;
                                }
                            }
                            if($found) {
                                if($_POST['value']) {
                                    //flag is set
                                    $setSQL->addFieldStmt($fieldname, "CONCAT_WS(',', {$fieldname}, '{$flag}')" , $table);
                                } else {
                                    //flag is unset
                                    $setSQL->addFieldStmt($fieldname, "TRIM(BOTH ',' FROM REPLACE(CONCAT(',', {$fieldname}, ','), CONCAT(',', '{$flag}', ','), ','))" , $table);
                                }
                                $response = ExecuteSQL($setSQL);
                                if($response['success']) {
                                    AddHistory(array(
                                        'type' => 'edit', 'flags' => (!empty($_POST['hist_flag']) ? IdentifiersStr($_POST['hist_flag']) : 'success'),
                                        'description' => (!empty($_POST['history_desc']) ? PunctuatedTextStr($_POST['history_desc']) : "Flag {$flag}").' '.($_POST['value'] ? 'enabled': 'disabled'),
                                        'author' => $AUTHENTICATION['Person']['PersonID'],
                                        'PersonID' => (!empty($_POST['PersonID']) ? intval($_POST['PersonID']) : null),
                                        'OrganisationID' => (!empty($_POST['OrganisationID']) ? intval($_POST['OrganisationID']) : null)),
                                        $response['_affectedrows']
                                    );
                                }
                            } else {
                                $response['errormessage'] = 'Target record data is missing.';
                                $response['errorcode'] = 1;
                            }
                        }
                        break;
                    case 'changeboolean':
                        if (CheckRequiredParams(array('table' => FALSE, 'fieldname' => FALSE, 'value' => TRUE), $_POST) && $AUTHENTICATION['Authenticated']) {
                            $table = VarnameStr($_POST['table']);
                            $fieldname = VarnameStr($_POST['fieldname']);
                            $setSQL = new stmtSQL('UPDATE', $table, $SYSTEM_SETTINGS["Database"]);
                            $found = FALSE;
                            foreach(array('PersonID' => 'integer', 'OrganisationID' => 'integer') AS $wherefield => $fieldtype) {
                                if(isset($_POST[$wherefield])) {
                                    $setSQL->addWhere($wherefield, $fieldtype, $_POST[$wherefield]);
                                    $found = TRUE;
                                    break;
                                }
                            }
                            if($found) {
                                $setSQL->addField($fieldname, 'integer', ($_POST['value'] ? 1 : 0));
                                $response = ExecuteSQL($setSQL);
                                if($response['success']) {
                                    AddHistory(array(
                                        'type' => 'edit', 'flags' => (!empty($_POST['hist_flag']) ? IdentifiersStr($_POST['hist_flag']) : 'success'),
                                        'description' => (!empty($_POST['history_desc']) ? PunctuatedTextStr($_POST['history_desc']) : (!empty($_POST['caption']) ? PunctuatedTextStr($_POST['caption']) : NameStr($_POST['fieldname'])).' '.($_POST['value'] ? 'enabled': 'disabled')),
                                        'author' => $AUTHENTICATION['Person']['PersonID'],
                                        'PersonID' => (!empty($_POST['PersonID']) ? intval($_POST['PersonID']) : null),
                                        'OrganisationID' => (!empty($_POST['OrganisationID']) ? intval($_POST['OrganisationID']) : null)),
                                        $response['_affectedrows']
                                    );
                                }
                            } else {
                                $response['errormessage'] = 'Target record data is missing.';
                                $response['errorcode'] = 1;
                            }
                        }
                        break;
                    case 'toggleboolean':
                        if (CheckRequiredParams(array('table' => FALSE, 'fieldname' => FALSE, 'currentvalue' => TRUE), $_POST) && $AUTHENTICATION['Authenticated']) {
                            $table = VarnameStr($_POST['table']);
                            $fieldname = VarnameStr($_POST['fieldname']);
                            $setSQL = new stmtSQL('UPDATE', $table, $SYSTEM_SETTINGS["Database"]);
                            $found = FALSE;
                            foreach(array('PersonID' => 'integer', 'OrganisationID' => 'integer') AS $wherefield => $fieldtype) {
                                if(isset($_POST[$wherefield])) {
                                    $setSQL->addWhere($wherefield, $fieldtype, $_POST[$wherefield]);
                                    $found = TRUE;
                                    break;
                                }
                            }
                            if($found) {
                                $newvalue = ($_POST['currentvalue'] ? 0 : 1);
                                $setSQL->addField($fieldname, 'integer', $newvalue);
                                $response = ExecuteSQL($setSQL);
                                if($response['success']) {
                                    if(!empty($_POST['history_desc_on']) && ($newvalue)) {
                                        $histtext = PunctuatedTextStr($_POST['history_desc_on']);
                                    } elseif(!empty($_POST['history_desc_off']) && (!$newvalue)) {
                                        $histtext = PunctuatedTextStr($_POST['history_desc_off']);
                                    } elseif(!empty($_POST['history_desc'])) {
                                        $histtext = PunctuatedTextStr($_POST['history_desc']);
                                    } elseif(!empty($_POST['caption'])) {
                                        $histtext = NameStr($_POST['caption']).' '.($newvalue ? 'enabled' : 'disabled');
                                    }
                                    if(!empty($histtext)) {
                                        AddHistory(array(
                                            'type' => 'edit', 'flags' => (!empty($_POST['hist_flag']) ? IdentifiersStr($_POST['hist_flag']) : 'success'),
                                            'description' => $histtext,
                                            'author' => $AUTHENTICATION['Person']['PersonID'],
                                            'PersonID' => (!empty($_POST['PersonID']) ? intval($_POST['PersonID']) : null),
                                            'OrganisationID' => (!empty($_POST['OrganisationID']) ? intval($_POST['OrganisationID']) : null)),
                                            $response['_affectedrows']
                                        );
                                    }
                                }
                            } else {
                                $response['errormessage'] = 'Target record data is missing.';
                                $response['errorcode'] = 1;
                            }
                        }
                        break;
                    case 'createinvdoc':
                        if((!empty($_POST['PersonID']) || !empty($_POST['OrganisationID'])) && !empty($_POST['InvoiceType']) && $AUTHENTICATION['Authenticated']) {
/*                            if(!empty($_POST['PersonID'])) {
                                $join = "tblinvoicetoperson ON (tblinvoicetoperson.InvoiceId = tblinvoice.InvoiceID) AND (tblinvoicetoperson.PersonID = ".intval($_POST['PersonID']).")";
                            } else {
                                $join = "tblinvoicetoorganisation ON (tblinvoicetoorganisation.InvoiceId = tblinvoice.InvoiceID) AND (tblinvoicetoorganisation.OrganisationID = ".intval($_POST['OrganisationID']).")";
                            }
                            $sql = 
                            "SELECT tblinvoice.InvoiceID
                             FROM tblinvoice
                             INNER JOIN {$join}
                             WHERE (tblinvoice.InvoiceType = '".IdentifierStr($_POST['InvoiceType'])."') 
                                            AND
                                   (tblinvoice.ISO4217 = '".(empty($_POST['ISO4217']) ? 'GBP' : strtoupper(IdentifierStr($_POST['ISO4217'])))."')
                                            AND
                                   (tblinvoice.InvoiceNo IS NULL)
                                            AND
                                   (DATEDIFF(UTC_TIMESTAMP(), tblinvoice.InvoiceDate) < 7)";
                            $invoiceid = SingleValue($SYSTEM_SETTINGS['Database'], $sql);*/
                            $INVOICE = new crmInvoice(
                                'eventsFinance', 
                                $SYSTEM_SETTINGS['Database'],
                                array(
                                    'invoicetype' => IdentifierStr($_POST['InvoiceType']),
                                    (!empty($_POST['OrganisationID']) ? 'OrganisationID' : 'PersonID') => intval((!empty($_POST['OrganisationID']) ? $_POST['OrganisationID'] : $_POST['PersonID'])),
                                    'ISO4217' => (empty($_POST['ISO4217']) ? 'GBP' : strtoupper(IdentifierStr($_POST['ISO4217']))),
                                    'mssettings' => $SYSTEM_SETTINGS['Membership']
                                ),
                                InvoiceSettings()
                            );
                            AddHistory(array(
                                'type' => 'edit',
                                'description' => $INVOICE->Invoice['InvoiceCaption'].' created',
                                'author' => $AUTHENTICATION['Person']['PersonID'],
                                'PersonID' => (!empty($INVOICE->Invoice['PersonID']) ? $INVOICE->Invoice['PersonID'] : null),
                                'OrganisationID' => (!empty($INVOICE->Invoice['OrganisationID']) ? $INVOICE->Invoice['OrganisationID'] : null),
                            ));
                            $response['invoiceid'] = $INVOICE->InvoiceID;
                            $response['continueurl'] = '/record.php?rec=invoice&invoiceid='.$INVOICE->InvoiceID;
                            $response['success'] = TRUE;
                        }
                        break;
                    case 'requestcredit':
                        //file_put_contents("D:\\temp\\post.txt", print_r($_POST, true));
                        if (CheckRequiredParams(array('invoiceitemids' => FALSE, 'origin' => FALSE), $_POST) && $AUTHENTICATION['Authenticated']) {
                            //Define the source URL (for use in cancel etc)
                            $response['origindata'] = $_POST;
                            switch($_POST['origin']) {
                                case 'invoice':
                                    $response['sourceurl'] = "/record.php?rec=invoice&invoiceid=".intval($_POST['invoiceid']);
                                    break;
                            }
                            $RequiresUserIntervention = FALSE;
                            //Iterate through the invoiceitems, to check what needs to be processed for each of them
                            $invoiceitemids = explode(',', $_POST['invoiceitemids']);
                            $credititemids = array();
                            foreach($invoiceitemids AS $invoiceitemid) {
                                $invoicedata = InvoiceItemToInvoice($invoiceitemid);
                                if($invoicedata['success']) {
                                    //Ignore pro forma invoices and items of zero value
                                    if(($invoicedata['Draft'] == FALSE) && ($invoicedata['ItemTotal'] <> 0)) {
                                        $credititemids[] = $invoiceitemid;
                                        if(!empty($invoicedata['ReqUserIntervention'])) {
                                            $RequiresUserIntervention = TRUE;
                                        }
                                    }
                                }
                            }
                            $response['credititemids'] = $credititemids;
                            $response['credititemcount'] = count($credititemids);
                            $response['reqintervention'] = $RequiresUserIntervention;
                            if($response['credititemcount'] > 0) {
                                if($RequiresUserIntervention) {
                                    $response['continueurl'] = "/record.php?rec=reversal";
                                    foreach($response['origindata'] AS $key => $value) {
                                        $response['continueurl'] .= '&'.$key.'='.(strcasecmp($key, 'invoiceitemids') == 0 ? implode(',', $credititemids) : rawurlencode($value));
                                    }
                                } else {
                                    //Create the credit note and show it
                                    $CREDITNOTE = CreditNoteFromInvoiceItems($credititemids);
                                    $response['continueurl'] = "/record.php?rec=invoice&invoiceid=".$CREDITNOTE->InvoiceID;
                                }
                                $response['success'] = TRUE;
                            } else {
                                $response['errormessage'] = 'There are no items found for processing.';
                                $response['errorcode'] = 1;
                            }
                        }
                        break;
                    case 'execreversal':
                        //file_put_contents("D:\\temp\\post.txt", print_r($_POST, true));
                        if (CheckRequiredParams(array('invoiceitemids' => FALSE), $_POST) && $AUTHENTICATION['Authenticated']) {
                            try {
                                if(StartTransaction($SYSTEM_SETTINGS["Database"])) {
                                    $credititemids = array();
                                    foreach($_POST['invoiceitemids'] AS $invoiceitemid) {
                                        $invoicedata = InvoiceItemToInvoice($invoiceitemid);
                                        $INVOICE = new crmInvoice('eventsFinance', $SYSTEM_SETTINGS['Database'], $invoicedata['InvoiceID'], InvoiceSettings());
                                        $invoiceitem = $INVOICE->InvoiceItem($invoiceitemid);
                                        $PERSON = new crmPerson('eventsPerson', $SYSTEM_SETTINGS['Database'], $invoiceitem['PersonID'], $SYSTEM_SETTINGS["Membership"]);
                                        if($invoicedata['success']) {
                                            //Ignore pro forma invoices and items of zero value
                                            if(($invoicedata['Draft'] == FALSE) && ($invoicedata['ItemTotal'] <> 0)) {
                                                $credititemids[] = $invoiceitemid;
                                                switch($invoiceitem['Mnemonic']) {
                                                    case 'ms_new':
                                                    case 'ms_renewal':
                                                    case 'ms_rejoin':
                                                    case 'ms_transfer':
                                                        //Execute decision where this is not "no change"
                                                        if(!empty($_POST['decision_'.$invoiceitemid])) {
                                                            $decision = $_POST['decision_'.$invoiceitemid];
                                                            if(strcasecmp($decision, 'clear') == 0) {
                                                                //clear all person MS history items
                                                                $sql = "DELETE FROM tblpersonms WHERE PersonID = ".$PERSON->PersonID;
                                                                $outcome = ExecuteSQL($sql);
                                                                if($outcome['success']) {
                                                                    AddHistory(array(
                                                                        'type' => 'delete',
                                                                        'description' => 'Clearing Membership History, reversal invoice item #'.$invoiceitemid,
                                                                        'PersonID' => $PERSON->PersonID,
                                                                    ), ($outcome['_affectedrows'] > 0));
                                                                } else {
                                                                    throw new crmException('SQL error: '.$outcome['errormessage'], $outcome['errorcode']);
                                                                }
                                                            } elseif(is_numeric($decision)) {
                                                                //Revert to the given history item (the decision is the PersonMSID)
                                                                $record = $PERSON->ReinstateMSHistory($decision);
                                                                if(!empty($record)) {
                                                                    AddHistory(array(
                                                                        'type' => 'delete',
                                                                        'description' => 'Reverting Membership History to '.(!empty($record['IsMember']) ? $record['GradeCaption'] : $record['MSStatusCaption']).', reversal invoice item #'.$invoiceitemid,
                                                                        'PersonID' => $PERSON->PersonID,
                                                                    ));
                                                                } else {
                                                                    throw new crmException('Unable to restore previous state of Membership History: record not found', 1);
                                                                }
                                                            } else {
                                                                //Change to status
                                                                $statuses = new crmMSStatus($SYSTEM_SETTINGS['Database']);
                                                                $newstatus = $statuses->GetStatusByFlag($decision);
                                                                if(!empty($newstatus)) {
                                                                    $PERSON->UpdateMSHistory($newstatus['MSStatusID'], date('Y-m-d'));
                                                                }
                                                            }
                                                        }
                                                        if(!empty($_POST['MSNextRenewal'])) {
                                                            $newdate = ValidDateStr($_POST['MSNextRenewal']);
                                                            $sql = "UPDATE tblperson SET MSNextRenewal = '{$newdate}' WHERE PersonID = ".$PERSON->PersonID;
                                                            $outcome = ExecuteSQL($sql);
                                                            if($outcome['success']) {
                                                                AddHistory(array(
                                                                    'type' => 'delete',
                                                                    'description' => 'Changing Membership renewal date to '.$newdate.', reversal invoice item #'.$invoiceitemid,
                                                                    'PersonID' => $PERSON->PersonID,
                                                                ), ($outcome['_affectedrows'] > 0));
                                                            } else {
                                                                throw new crmException('SQL error: '.$outcome['errormessage'], $outcome['errorcode']);
                                                            }
                                                        }
                                                        break;
                                                }
                                            }
                                        }
                                    }
                                    $CREDITNOTE = CreditNoteFromInvoiceItems($credititemids);
                                    $response['continueurl'] = "/record.php?rec=invoice&invoiceid=".$CREDITNOTE->InvoiceID;
                                    $response['success'] = TRUE;
                                    mysqli_commit($SYSTEM_SETTINGS["Database"]);
                                } else {
                                    throw new crmException('Unable to start transaction: '.mysqli_error($SYSTEM_SETTINGS["Database"]), mysqli_errno($SYSTEM_SETTINGS["Database"]));
                                }
                            } catch ( Exception $e ) {
                                mysqli_rollback($SYSTEM_SETTINGS["Database"]);
                                $response['success'] = FALSE;
                                $response['errormessage'] = $e->getMessage();
                                $response['errorcode'] = $e->getCode();                            
                                AddToSysLog(array('EntryKind' => 'error', 'IsSystem' => TRUE, 'Expiry' => $SYSTEM_SETTINGS['ExpiryPolicies']['LogErrors'], 'Caption' => 'Reversal Error', 'Description' => $response['errormessage'].' ('.$response['errorcode'].')', 'Data' => array('POST' => print_r($_POST, TRUE))));
                            } 
                        }
                        break;
                    case 'savemsfee':
                        if(HasPermission('adm_membership')) {
                            try {
                                if(StartTransaction($SYSTEM_SETTINGS["Database"])) {
                                    if(!empty($_POST['MSFeeID'])) {
                                        $msfeeid = intval($_POST['MSFeeID']);
                                        $setSQL = new stmtSQL('UPDATE', 'tblmsfee', $SYSTEM_SETTINGS["Database"]);
                                        $setSQL->addWhere('MSFeeID', 'integer', $msfeeid);
                                    } else {
                                        //Create a new tariff entry
                                        $setSQL = new stmtSQL('INSERT', 'tblmsfee', $SYSTEM_SETTINGS["Database"]);
                                        $setSQL->addField('MSGradeID', 'integer', $_POST['MSGradeID']);
                                        //Close previous entries for this grade
                                        $sql = 
                                        "UPDATE tblmsfee 
                                         SET ValidUntil = DATE_SUB(".(empty($_POST['ValidFrom']) ? "CURRENT_DATE" : "'".ValidDateStr($_POST['ValidFrom'])."'").", INTERVAL 1 DAY)
                                         WHERE (MSGradeID = ".intval($_POST['MSGradeID']).")";
                                        $response = ExecuteSQL($sql);
                                        if(!$response['success']) {
                                            throw new crmException('Unable to update membership rate entry: '.$response['errormessage'], $response['errorcode']);    
                                        }
                                    }
                                    $setSQL->addField('ValidFrom', 'date', $_POST['ValidFrom'], null, TRUE);
                                    $setSQL->addField('ValidUntil', 'date', $_POST['ValidUntil'], null, TRUE);
                                    $response = ExecuteSQL($setSQL);
                                    if($response['success']) {
                                        if($setSQL->IsInsert) {
                                            $msfeeid = mysqli_insert_id($SYSTEM_SETTINGS["Database"]);
                                        }
                                        $fees = new crmMSFees($SYSTEM_SETTINGS['Database'], $SYSTEM_SETTINGS['Membership']['GradeCaption']);
                                        $fee = $fees->GetFeeByID($msfeeid);
                                        foreach($fee['_currencies'] AS $iso4217 => $currency) {
                                            $value1y = (isset($_POST['Value1Y_'.$iso4217]) && (strlen($_POST['Value1Y_'.$iso4217]) > 0) ? intval(round(floatvalExt(str_replace(array(utf8_encode('£'), '$', utf8_encode('€'), ',', '%'), '', $_POST['Value1Y_'.$iso4217]))*100)) : 'NULL');
                                            $groupvalue1y = (isset($_POST['GroupValue1Y_'.$iso4217]) && (strlen($_POST['GroupValue1Y_'.$iso4217]) > 0) ? intval(round(floatvalExt(str_replace(array(utf8_encode('£'), '$', utf8_encode('€'), ',', '%'), '', $_POST['GroupValue1Y_'.$iso4217]))*100)) : 'NULL');
                                            $value3y = (isset($_POST['Value3Y_'.$iso4217]) && (strlen($_POST['Value3Y_'.$iso4217]) > 0) ? intval(round(floatvalExt(str_replace(array(utf8_encode('£'), '$', utf8_encode('€'), ',', '%'), '', $_POST['Value3Y_'.$iso4217]))*100)) : 'NULL');
                                            $sql = 
                                            "INSERT INTO tblmsfeevalue (MSFeeID, ISO4217, Value1Y, GroupValue1Y, Value3Y)
                                            VALUES({$msfeeid}, '$iso4217', {$value1y}, {$groupvalue1y}, {$value3y})
                                            ON DUPLICATE KEY UPDATE Value1Y = {$value1y}, GroupValue1Y = {$groupvalue1y}, Value3Y = {$value3y}";
                                            $response = ExecuteSQL($sql);
                                            if(!$response['success']) {
                                                throw new crmException('Unable to save membership rate line item: '.$response['errormessage'], $response['errorcode']);    
                                            }
                                        }
                                        AddToSysLog(array(
                                            'EntryKind' => 'info',
                                            'Caption' => 'Membership',
                                            'Description' => 'Rate entry for '.$fee['GradeCaption'].' '.($setSQL->IsInsert ? 'created' : 'updated'),
                                            'Data' => $_POST,
                                        ));
                                    }
                                    if(!$response['success']) {
                                        throw new crmException('Unable to save membership rate: '.$response['errormessage'], $response['errorcode']);
                                    }
                                    mysqli_commit($SYSTEM_SETTINGS["Database"]);
                                } else {
                                    throw new crmException('Unable to start transaction: '.mysqli_error($SYSTEM_SETTINGS["Database"]), mysqli_errno($SYSTEM_SETTINGS["Database"]));
                                }
                            } catch ( Exception $e ) {
                                mysqli_rollback($SYSTEM_SETTINGS["Database"]);
                                $response['success'] = FALSE;
                                $response['errormessage'] = $e->getMessage();
                                $response['errorcode'] = $e->getCode();                            
                                AddToSysLog(array('EntryKind' => 'error', 'IsSystem' => TRUE, 'Expiry' => $SYSTEM_SETTINGS['ExpiryPolicies']['LogErrors'], 'Caption' => 'Membership', 'Description' => $response['errormessage'].' ('.$response['errorcode'].')', 'Data' => array('POST' => print_r($_POST, TRUE))));
                            }                             
                        }
                        break;
                    case 'delmsfee':
                        if(HasPermission('adm_membership')) {
                            try {
                                if(StartTransaction($SYSTEM_SETTINGS["Database"])) {
                                    $msfeeid = intval($_POST['MSFeeID']);
                                    $feerecord = SingleRecord(
                                        $SYSTEM_SETTINGS['Database'], 
                                        "SELECT tblmsfee.*, tblmsgrade.GradeCaption
                                         FROM tblmsfee
                                         LEFT JOIN tblmsgrade ON tblmsgrade.MSGradeID = tblmsfee.MSGradeID
                                         WHERE tblmsfee.MSFeeID = {$msfeeid}"
                                    );
                                    if(empty($feerecord)) {
                                        throw new crmException('Unable to load rate entry: '.mysqli_error($SYSTEM_SETTINGS["Database"]), mysqli_errno($SYSTEM_SETTINGS["Database"]));
                                    }
                                    //First step, delete line items
                                    $response = ExecuteSQL("DELETE FROM tblmsfeevalue WHERE tblmsfeevalue.MSFeeID = {$msfeeid}");
                                    if($response['success']) {
                                        //Delete the tariff
                                        $response = ExecuteSQL("DELETE FROM tblmsfee WHERE tblmsfee.MSFeeID = {$msfeeid}");
                                        if($response['success']) {
                                            //Reopen the most recent tariff
                                            $sql = "SELECT tblmsfee.MSFeeID FROM tblmsfee WHERE tblmsfee.MSGradeID = {$feerecord['MSGradeID']} ORDER BY ValidFrom DESC LIMIT 1";
                                            $lastfeerecord = SingleRecord($SYSTEM_SETTINGS['Database'], $sql);
                                            if(empty($feerecord)) {
                                                throw new crmException('Unable to load most recent rate entry: '.mysqli_error($SYSTEM_SETTINGS["Database"]), mysqli_errno($SYSTEM_SETTINGS["Database"]));
                                            }
                                            $response = ExecuteSQL("UPDATE tblmsfee SET ValidUntil = NULL WHERE tblmsfee.MSFeeID = {$lastfeerecord['MSFeeID']}");
                                            if($response['success']) {
                                                AddToSysLog(array(
                                                    'EntryKind' => 'danger',
                                                    'Caption' => 'Membership',
                                                    'Description' => 'Rate entry deleted for '.$feerecord['GradeCaption'],
                                                    'Data' => $feerecord,
                                                ));
                                            }
                                        }
                                    }
                                    if(!$response['success']) {
                                        throw new crmException('Unable to save membership rate: '.$response['errormessage'], $response['errorcode']);
                                    }
                                    mysqli_commit($SYSTEM_SETTINGS["Database"]);
                                } else {
                                    throw new crmException('Unable to start transaction: '.mysqli_error($SYSTEM_SETTINGS["Database"]), mysqli_errno($SYSTEM_SETTINGS["Database"]));
                                }
                            } catch ( Exception $e ) {
                                mysqli_rollback($SYSTEM_SETTINGS["Database"]);
                                $response['success'] = FALSE;
                                $response['errormessage'] = $e->getMessage();
                                $response['errorcode'] = $e->getCode();                            
                                AddToSysLog(array('EntryKind' => 'error', 'IsSystem' => TRUE, 'Expiry' => $SYSTEM_SETTINGS['ExpiryPolicies']['LogErrors'], 'Caption' => 'Membership', 'Description' => $response['errormessage'].' ('.$response['errorcode'].')', 'Data' => array('POST' => print_r($_POST, TRUE))));
                            }
                        }
                        break;
                    case 'clearsyslog':
                        if(HasPermission('adm_security')) {
                            $response = ExecuteSQL("DELETE FROM tblsyslog");
                            if($response['success']) {
                                AddToSysLog(array(
                                    'EntryKind' => 'warning',
                                    'PersonID' => $AUTHENTICATION['Person']['PersonID'],
                                    'Caption' => 'System Log',
                                    'Description' => 'The system log was cleared.'
                                ));
                            }
                        }
                        break;
                    case 'savesettings':
                        if(HasPermission(array('adm_syssettings', 'adm_security'))) {
                            switch($_POST['SYSTEM_SOURCE']) {
                                case 'frmGeneral':
                                    $fields = array(
                                        'General.OrgLongName' => 'punctuatedtext',
                                        'General.OrgShortName' => 'punctuatedtext',
                                        'General.Address.Lines' => 'text',
                                        'General.Address.Postcode' => 'name',
                                        'General.Address.Town' => 'name',
                                        'General.Address.County' => 'name',
                                        'General.Address.Region' => 'name',
                                        'General.Address.CountryCode' => array('fieldtype' => 'name', 'processfn' => @strtoupper),
                                        'General.Address.Country' => 'name',
                                        'General.Website' => 'url',
                                        'General.VATNumber' => 'string',
                                        'General.CharityNo' => 'string',
                                        'General.CompanyNo' => 'string',
                                    );
                                    break;
                                case 'frmSystem':
                                    $fields = array(
                                        'System.Timezone' => 'mimetype',
                                        'System.DebugMode' => 'boolean',
                                        'System.NoAvatarCaching' => 'boolean',
                                        'System.FileStore' => 'string',
                                        'System.BOM' => 'boolean',
                                        'System.ExcelCSV' => 'boolean',
                                        'System.TimeLimitExport' => array('fieldtype' => 'integer', 'default' => 14400, 'min' => 900),
                                        'System.DB.Host' => 'url',
                                        'System.DB.Port' => array('fieldtype' => 'integer', 'default' => 3306),
                                        'System.DB.Schema' => 'varname',
                                        'System.DB.Username' => 'name',
                                        'System.DB.Password' => array('fieldtype' => 'string', 'encrypted' => TRUE),
                                        'System.Email.Paused' => 'boolean',
                                        'System.Email.EmailMethod' => 'string',
                                        'System.Email.DebugToEmail' => 'email',
                                        'System.Email.Defaults.FromName' => 'punctuatedtext',
                                        'System.Email.Defaults.FromEmail' => 'email',
                                        'System.Email.SliceSize' => array('fieldtype' => 'integer', 'default' => 15),
                                        'System.Email.SMTP.Host' => 'string',
                                        'System.Email.SMTP.Port' => array('fieldtype' => 'integer', 'default' => 587),
                                        'System.Email.SMTP.Authenticate' => 'boolean',
                                        'System.Email.SMTP.Security' => 'string',
                                        'System.Email.SMTP.Helo' => array('fieldtype' => 'url', 'emptyasnull' => TRUE),
                                        'Credentials.SMTP.Username' => 'string',
                                        'Credentials.SMTP.Password' => array('fieldtype' => 'string', 'encrypted' => TRUE),
                                        'Credentials.AWS.S3.AccessKey' => 'name',
                                        'Credentials.AWS.S3.SecretKey' => array('fieldtype' => 'string', 'encrypted' => TRUE),
                                        'Storage.Region' => 'name',
                                        'Storage.Bucket' => 'name',
                                        'Credentials.AWS.SES.AccessKey' => 'name',
                                        'Credentials.AWS.SES.SecretKey' => array('fieldtype' => 'string', 'encrypted' => TRUE),
                                        'System.Email.Region' => 'name',
                                        'System.Email.MaxSendRate' => array('fieldtype' => 'integer', 'default' => 15),
                                    );
                                    if((!$SYSTEM_SETTINGS["System"]['Email']['Paused']) && (!empty($_POST['System_Email_Paused']))) {
                                        AddNotificationToAll(array(
                                            'type' => 'warning',
                                            'messages' => array(
                                                array(
                                                    'caption' => '<warning><b>Email Queue</b></warning> The email sending queue is paused.',
                                                    'icon' => 'fa-exclamation-triangle',
                                                )
                                            ),
                                        ));
                                    }
                                    break;
                                case 'frmServices':
                                    $fields = array(
                                        'Credentials.PCAPredict.APIKeys.DDI' => 'string'
                                    );
                                    break;
                                case 'frmFinance':
                                    $fields = array(
                                        'Finance.InvPrefix.Invoice' => 'string',
                                        'Finance.InvPrefix.CreditNote' => 'string',
                                        'Finance.InvDigits' => array('fieldtype' => 'integer', 'default' => 7, 'min' => 3, 'max' => 15),
                                        'Finance.InvoiceDue' => array('fieldtype' => 'integer', 'default' => 30, 'min' => 0, 'max' => 90),
                                        'Finance.OverdueRate' => array('fieldtype' => 'integer', 'default' => 3, 'min' => 0, 'max' => 20),
                                        'Finance.FinYear.Day' => array('fieldtype' => 'integer', 'default' => 1, 'min' => 1, 'max' => 31),
                                        'Finance.FinYear.Month' => array('fieldtype' => 'integer', 'default' => 1, 'min' => 1, 'max' => 12),
                                        'Finance.DirectDebit.ReferenceReq' => 'boolean',
                                        'Finance.DirectDebit.BankNameReq' => 'boolean',
                                        'Finance.DirectDebit.DocumentReq' => 'boolean',
                                        'Finance.Export.Enabled' => 'boolean',
                                        'Finance.Export.SettledOnly' => 'boolean',
                                        'Finance.Export.BOM' => 'boolean',
                                        'Finance.Export.ExcelCSV' => 'boolean',
                                        'Finance.Export.QuoteAll' => 'boolean',
                                        'Finance.Export.Header' => 'boolean',
                                        'Finance.Export.Testmode' => 'boolean',
                                    );
                                    break;
                                case 'frmSecurity':
                                    $fields = array(
                                        'Security.EncryptionKey' => 'filepath',
                                        'Security.TokenTimeout' => array('fieldtype' => 'integer', 'default' => 12),
                                        'Security.MaxFailCount' => array('fieldtype' => 'integer', 'default' => 10, 'min' => 3, 'max' => 50),
                                        'Security.AllowPasswordChange' => 'boolean',
                                        'Security.MinPasswordLength' => array('fieldtype' => 'integer', 'default' => 12),
                                        'Security.EnforcePWComplexity' => 'boolean',
                                    );
                                    break;
                                case 'frmCustomise':
                                    $fields = array(
                                        'General.SiteName' => 'string',
                                        'Customise.SidebarLogo' => 'boolean',
                                        'Customise.AnimatedHeader' => 'boolean',
                                        'Membership.GradeCaption' => 'string',
                                        'Membership.CompletionStageCaption' => 'string',
                                        'Customise.MaxRecentViewedCount' => array('fieldtype' => 'integer', 'default' => 5, 'min' => 0, 'max' => 25),
                                        'Customise.DOBRequired' => 'boolean',
                                        'Customise.GenderRequired' => 'boolean',
                                        'Customise.Title' => 'string'
                                    );
                                    break;
                                case 'frmExpiry':
                                    $fields = array(
                                        'ExpiryPolicies.SysLog' => array('fieldtype' => 'integer', 'min' => 0, 'default' => intval(round(15*30.4, 0))),
                                        'ExpiryPolicies.BGProc' => array('fieldtype' => 'integer', 'min' => 0, 'default' => 14),
                                        'ExpiryPolicies.LogErrors' => array('fieldtype' => 'integer', 'min' => 0, 'default' => 21),
                                        'ExpiryPolicies.LogWarnings' => array('fieldtype' => 'integer', 'min' => 0, 'default' => 21),
                                        'ExpiryPolicies.Notes' => array('fieldtype' => 'integer', 'min' => 0, 'default' => 18),
                                        'ExpiryPolicies.Export' => array('fieldtype' => 'integer', 'min' => 1, 'default' => 7),
                                    );
                                    break;
                            }
                            SaveSettings($fields, $SYSTEM_SETTINGS, CONSTConfigFile);
                            $response['success'] = TRUE;
                            $response['errorcode'] = 0;
                            $response['errormessage'] = '';
                        }
                        break;
                    case 'uploadfile':
                        if (isset($_FILES['File']) && ($_FILES['File']['size'] > 0)) {
                            if (is_uploaded_file($_FILES['File']['tmp_name'])) {
                                $objectname=time().'_'.RandomString(32).'_'.$_FILES['File']['name'];
                                $finfo = FileinfoFromExt($_FILES['File']['name']);
                                $tempfilename = IncTrailingPathDelimiter(sys_get_temp_dir()).$objectname;
                                if (move_uploaded_file($_FILES['File']['tmp_name'], $tempfilename)) {
                                    $stored = FALSE;
                                    if($SYSTEM_SETTINGS["System"]["FileStore"] == 'S3') {
                                        try {
                                            $s3 = S3Client::factory(array(
                                                'credentials' => array('key' => $SYSTEM_SETTINGS['Credentials']['AWS']['S3']['AccessKey'],
                                                                       'secret' => Decrypt($SYSTEM_SETTINGS['Credentials']['AWS']['S3']['SecretKey']),
                                                ),
                                                'region'  => $SYSTEM_SETTINGS['Storage']['Region']
                                            ));
                                            $response = $s3->putObject(array(
                                                'Bucket'     => $SYSTEM_SETTINGS['Storage']['Bucket'],
                                                'Key'        => $objectname,
                                                'SourceFile' => $tempfilename,
                                            ));
                                            // Poll the object until it is accessible
                                            $s3->waitUntil('ObjectExists', array(
                                                'Bucket' => $SYSTEM_SETTINGS['Storage']['Bucket'],
                                                'Key'    => $objectname
                                            ));
                                            $stored = true;
                                        } catch( Exception $e ) {
                                            $response['errormessage'] = $e->getMessage();
                                            $response['errorcode'] = $e->getCode();
                                            AddToSysLog(array('EntryKind' => 'warning', 'IsSystem' => TRUE, 'Expiry' => $SYSTEM_SETTINGS['ExpiryPolicies']['LogWarnings'], 'Caption' => 'S3Client error', 'Description' => $response['errormessage'].' ('.$response['errorcode'].') Reverting to database storage.', 'Data' => array('File' => __FILE__, 'Line' => __LINE__)));
                                        }
                                    }
                                    $sql =
                                    "INSERT INTO tbldocument (LastModified, DocTitle, `Filename`, `Mimetype`, Bucket, Objectname, Data)
                                     VALUES (UTC_TIMESTAMP(),
                                            ".(!empty($_POST['DocTitle']) ? "'".mysqli_real_escape_string($SYSTEM_SETTINGS['Database'], PunctuatedTextStr($_POST['DocTitle']))."'" : "NULL").",
                                            '".mysqli_real_escape_string($SYSTEM_SETTINGS['Database'], $_FILES['File']['name'])."',
                                            '{$finfo['MimeType']}',
                                            ".($stored && ($SYSTEM_SETTINGS["System"]["FileStore"] == 'S3') ? "'".mysqli_real_escape_string($SYSTEM_SETTINGS['Database'], $SYSTEM_SETTINGS['Storage']['Bucket'])."'" : "NULL").",
                                            ".($stored && ($SYSTEM_SETTINGS["System"]["FileStore"] == 'S3') ? "'".mysqli_real_escape_string($SYSTEM_SETTINGS['Database'], $objectname)."'" : "NULL").",
                                            ".(!$stored ? "LOAD_FILE(\"".mysqli_real_escape_string($SYSTEM_SETTINGS["Database"], $tempfilename)."\")" : "NULL")."
                                     )";
                                    $response = ExecuteSQL($sql);
                                    if($response['success']) {
                                        $documentid = mysqli_insert_id($SYSTEM_SETTINGS["Database"]);
                                        foreach(array('PersonID' => 'tbldocumenttoperson', 'OrganisationID' => 'tbldocumenttoorganisation') AS $fieldname => $tablename) {
                                            if(isset($_POST[$fieldname])) {
                                                $id = intval($_POST[$fieldname]);
                                                $sql = "INSERT INTO {$tablename} (DocumentID, {$fieldname}) VALUES ({$documentid}, {$id})";
                                                $response = ExecuteSQL($sql);
                                                if($response['success']) {
                                                    $idfield = OwnerIDField();
                                                    AddHistory(array('type' => 'edit', 'description' => "Document uploaded: ".(empty($_POST['DocTitle']) ? $_FILES['File']['name'] : PunctuatedTextStr($_POST['DocTitle'])), 'author' => $AUTHENTICATION['Person']['PersonID'], $idfield => $_POST[$idfield]));
                                                }
                                            }
                                        }
/*                                        if(!empty($_POST['PersonID'])) {
                                            $documentid = mysqli_insert_id($SYSTEM_SETTINGS["Database"]);
                                            $personid = intval($_POST['PersonID']);
                                            $sql = "INSERT INTO tbldocumenttoperson (DocumentID, PersonID) VALUES ({$documentid}, {$personid})";
                                            $response = ExecuteSQL($sql);
                                            if($response['success']) {
                                                AddHistory(array('type' => 'edit', 'description' => "Document uploaded: ".(empty($_POST['DocTitle']) ? $_FILES['File']['name'] : PunctuatedTextStr($_POST['DocTitle'])), 'author' => $AUTHENTICATION['Person']['PersonID'], 'PersonID' => $personid));                                                    
                                            }
                                        }*/
                                    }
                                    unlink($tempfilename);
                                } else {
                                    $response['errormessage'] = 'Unable to create temporary file.';
                                    $response['errorcode'] = 2;
                                }
                            }
                        } else {
                            $response['errormessage'] = 'The file is empty.';
                            $response['errorcode'] = 1;
                        }
                        break;
                    case 'saveeditor':
                        $EDITORITEM = editorItemFactory::create($SYSTEM_SETTINGS['Database'], $_GET, $SYSTEM_SETTINGS);
                        if(!empty($EDITORITEM)) {
/*                            $setSQL = SimpleUpdateSQL(
                                'tblemailtemplate',
                                array(
                                    'fieldname' => 'EmailTemplateID',
                                    'fieldtype' => 'integer',
                                    'value' => $_GET['EmailTemplateID'],
                                ),
                                array(
                                    'Body' => array(
                                        'fieldtype' => 'raw',
                                        'emptyasnull' => TRUE
                                    ),
                                    'LastModified' => array(
                                        'fieldtype' => 'datetime',
                                        'value' => gmdate('Y-m-d H:i:s', time()),
                                    ),
                                )
                            );*/
                            //file_put_contents("D:\\temp\\sql.txt", $setSQL->SQL());
                            //$response = ExecuteSQL($setSQL);
                            if($EDITORITEM->SetText($_POST['Body'])) {
                                AddToSysLog(array('EntryKind' => 'info', 'Caption' => 'Templates', 'Description' => $EDITORITEM->Descriptor().' updated', 'Data' => $EDITORITEM->Properties));
                            }
                            $response['success'] = TRUE;
                        }
                        break;
                    case 'downloadurl':
                        if(!empty($_GET['documentid'])) {
                            $documentid = intval($_GET['documentid']);
                            $sql = 
                            "SELECT tbldocument.DocumentID, tbldocument.`Filename`, tbldocument.`Mimetype`, tbldocument.DocTitle, tbldocument.Bucket, tbldocument.Objectname,
                                    IF((tbldocument.Bucket IS NOT NULL) AND (tbldocument.Objectname IS NOT NULL), 'S3', 'DB') AS `StorageType`,
                                    COALESCE(tbldocument.DocTitle, tbldocument.Filename) AS `DisplayName`,
                                    tbldocumenttoperson.PersonID,
                                    IF((tbldocument.`Mimetype` = 'text/html') OR (tbldocument.`Mimetype` = 'text/plain'), '_blank', NULL) AS `Target`
                             FROM tbldocument
                             LEFT JOIN tbldocumenttoperson ON tbldocumenttoperson.DocumentID = tbldocument.DocumentID
                             WHERE tbldocument.DocumentID = {$documentid}
                            ";
                            $filedata = SingleRecord($SYSTEM_SETTINGS["Database"], $sql);
                            if($AUTHENTICATION['Authenticated']) {
                                if(!empty($filedata)) {
                                    $sql = 
                                    "INSERT INTO tbldocumentdownload (DocumentID, PersonID, Downloaded) VALUES ({$filedata['DocumentID']}, ".intval($AUTHENTICATION['Person']['PersonID']).", UTC_TIMESTAMP())";
                                    mysqli_query($SYSTEM_SETTINGS["Database"],$sql);
                                    if($filedata['StorageType'] == 'S3') {
                                        //Download directly from S3
                                        try {
                                            $s3 = S3Client::factory(array(
                                                'credentials' => array('key' => $SYSTEM_SETTINGS['Credentials']['AWS']['S3']['AccessKey'],
                                                                       'secret' => Decrypt($SYSTEM_SETTINGS['Credentials']['AWS']['S3']['SecretKey']),
                                                ),
                                                'region'  => $SYSTEM_SETTINGS['Storage']['Region']
                                            ));
                                            $signedUrl = $s3->getObjectUrl(
                                                $filedata['Bucket'],
                                                $filedata['Objectname'],
                                                '+1 hour',
                                                array(
                                                    'ResponseContentDisposition' => "attachment; filename=\"{$filedata['Filename']}\"",
                                                    'ResponseContentType' => "attachment; filename=\"{$filedata['Mimetype']}\"",
                                                )
                                            );
                                            $response['url'] = $signedUrl;
                                        } catch( Exception $e ) {
                                            $response['errormessage'] = $e->getMessage();
                                            $response['errorcode'] = $e->getCode();
                                            AddToSysLog(array('EntryKind' => 'error', 'IsSystem' => TRUE, 'Expiry' => $SYSTEM_SETTINGS['ExpiryPolicies']['LogErrors'], 'Caption' => 'S3Client error', 'Description' => $response['errormessage'].' ('.$response['errorcode'].')', 'Data' => array('File' => __FILE__, 'Line' => __LINE__)));
                                        }                   
                                    } else {
                                        //Download from the CRM server
                                        //$response['url'] = $SYSTEM_SETTINGS['System']['ThisURL']."/syscall.php?do=downloadfile&documentid={$filedata['DocumentID']}";
                                        $response['url'] = "/syscall.php?do=downloadfile&documentid={$filedata['DocumentID']}";
                                        $response['target'] = $filedata['Target'];
                                    }
                                    $response['success'] = TRUE;
                                } else {
                                    $response['errormessage'] = 'The document could not be found.';
                                    $response['errorcode'] = 2;                                
                                }
                            } else {
                                $response['errormessage'] = 'Insufficient permissions!';
                                $response['errorcode'] = 1;                                
                            }
                        }
                        break;
                    case 'deldocument':
                        if (CheckRequiredParams(array('DocumentID' => FALSE), $_POST) && $AUTHENTICATION['Authenticated']) {
                            $documentid = intval($_POST['DocumentID']);
                            $sql = 
                            "SELECT tbldocument.DocumentID, tbldocument.`Filename`, tbldocument.`Mimetype`, tbldocument.DocTitle, tbldocument.Bucket, tbldocument.Objectname,
                                    IF((tbldocument.Bucket IS NOT NULL) AND (tbldocument.Objectname IS NOT NULL), 'S3', 'DB') AS `StorageType`,
                                    tbldocumenttoperson.PersonID, tbldocumenttoorganisation.OrganisationID
                             FROM tbldocument
                             LEFT JOIN tbldocumenttoperson ON tbldocumenttoperson.DocumentID = tbldocument.DocumentID
                             LEFT JOIN tbldocumenttoorganisation ON tbldocumenttoorganisation.DocumentID = tbldocument.DocumentID
                             WHERE tbldocument.DocumentID = {$documentid}
                            ";
                            $filedata = SingleRecord($SYSTEM_SETTINGS["Database"], $sql);
                            if(!empty($filedata)) {
                                if($filedata['StorageType'] == 'S3') {
                                    try {
                                        $s3 = S3Client::factory(array(
                                            'credentials' => array('key' => $SYSTEM_SETTINGS['Credentials']['AWS']['S3']['AccessKey'],
                                                                   'secret' => Decrypt($SYSTEM_SETTINGS['Credentials']['AWS']['S3']['SecretKey']),
                                            ),
                                            'region'  => $SYSTEM_SETTINGS['Storage']['Region']
                                        ));
                                        $result = $s3->deleteObject(array(
                                            'Bucket' => $filedata['Bucket'],
                                            'Key'    => $filedata['Objectname']
                                        ));
                                    } catch( Exception $e ) {
                                        AddToSysLog(array('EntryKind' => 'warning', 'IsSystem' => TRUE, 'Expiry' => $SYSTEM_SETTINGS['ExpiryPolicies']['LogWarnings'], 'Caption' => 'S3Client error', 'Description' => $response['errormessage'].' ('.$response['errorcode'].')', 'Data' => array('File' => __FILE__, 'Line' => __LINE__)));
                                    }
                                }
                                $sql = "DELETE FROM tbldocument WHERE tbldocument.DocumentID = ".$filedata['DocumentID'];
                                $response = ExecuteSQL($sql);
                                if($response['success']) {
                                    if(!empty($filedata['PersonID']) || !empty($filedata['OrganisationID'])) {
                                        AddHistory(array('type' => 'delete', 'description' => "Document deleted: ".(empty($filedata['DocTitle']) ? $filedata['Filename'] : PunctuatedTextStr($filedata['DocTitle'])), 'author' => $AUTHENTICATION['Person']['PersonID'], 'PersonID' => $filedata['PersonID'], 'OrganisationID' => $filedata['OrganisationID']), $response['_affectedrows']);
                                    }
                                }
                            } else {
                                $response['errormessage'] = 'The document could not be found.';
                                $response['errorcode'] = 2;
                            }
                        }
                        break;
                    case 'addTableToGroup':
                        if (CheckRequiredParams(array('inc' => FALSE), $_GET) && $AUTHENTICATION['Authenticated']) {
                            ignore_user_abort(true);
                            set_time_limit($SYSTEM_SETTINGS['System']['TimeLimitExport']);
                            $QUERIES = null;
                            $WHERE = array();
                            $HAVING = array();
                            $SEARCHES = array();
                            if(!empty($_GET['sSearch']) && (strlen($_GET['sSearch']) > 1)) {
                                $SEARCHTERM = mysqli_real_escape_string($SYSTEM_SETTINGS["Database"], PREG_REPLACE("/[^0-9a-zA-Z\pL\s\+\(\)\.\-\#\_\/@']/iu", '', $_GET['sSearch']));
                            } else {
                                $SEARCHTERM = '';
                            }
                            $BUTTONGROUP = array();
                            $ALLOWED = null;
                            $ORDERS = array();
                            $LIMITCLAUSE = "";
                            require(IdentifierStr($_GET['inc']));
                            if(!empty($QUERIES)) {
                                if (mysqli_multi_query($SYSTEM_SETTINGS["Database"], $QUERIES)) {
                                    //First query = total number of records
                                    $qry = mysqli_use_result($SYSTEM_SETTINGS["Database"]);
                                    $data = mysqli_fetch_row($qry);
                                    $response['recordcount'] = $data[0];
                                    mysqli_free_result($qry);
                                    //Second query = the actual data
                                    if (mysqli_next_result($SYSTEM_SETTINGS["Database"])) {
                                        $qry = mysqli_store_result($SYSTEM_SETTINGS["Database"]);
                                        $response['tablecount'] = mysqli_num_rows($qry);
                                        $response['addedcount'] = 0;
                                        $sql = "SELECT GroupName FROM tblpersongroup WHERE PersonGroupID = ".intval($_POST['PersonGroupID']);
                                        $groupname = SingleValue($SYSTEM_SETTINGS['Database'], $sql);
                                        if($response['tablecount'] > 0) {
                                            $response['success'] = TRUE;
                                            while($data = mysqli_fetch_array($qry)) {
                                                if(!empty($data['PersonID'])) {
                                                    $insertSQL = 
                                                    "INSERT INTO tblpersontopersongroup (PersonID, PersonGroupID, `Comment`)
                                                     SELECT tblperson.PersonID, ".intval($_POST['PersonGroupID']).", '".mysqli_real_escape_string($SYSTEM_SETTINGS["Database"], $_POST['Comment'])."'
                                                     FROM tblperson
                                                     LEFT JOIN tblpersongrouptomsgrade ON tblpersongrouptomsgrade.PersonGroupID = ".intval($_POST['PersonGroupID'])."
                                                     LEFT JOIN tblpersonms ON (tblpersonms.PersonID = tblperson.PersonID) AND (tblpersonms.BeginDate <= UTC_TIMESTAMP()) AND ((tblpersonms.EndDate IS NULL) OR (tblpersonms.EndDate >= UTC_TIMESTAMP()))
                                                     LEFT JOIN tblmsstatus ON (tblmsstatus.MSStatusID = tblpersonms.MSStatusID)
                                                     LEFT JOIN tblpersontopersongroup ON (tblpersontopersongroup.PersonID = tblperson.PersonID) AND (tblpersontopersongroup.PersonGroupID = ".intval($_POST['PersonGroupID']).")
                                                     WHERE (tblperson.PersonID = ".intval($data['PersonID']).")
                                                                    AND
                                                           (tblpersontopersongroup.PersonToPersonGroupID IS NULL)
                                                                    AND
                                                           ((tblpersongrouptomsgrade.MSGradeID IS NULL) OR ((tblpersongrouptomsgrade.MSGradeID = tblpersonms.MSGradeID) AND FIND_IN_SET('ismember', tblmsstatus.MSStatusFlags)))
                                                    ";
                                                    if (mysqli_query($SYSTEM_SETTINGS["Database"], $insertSQL)) {
                                                        $affectedrows = mysqli_affected_rows($SYSTEM_SETTINGS['Database']);
                                                        $response['addedcount'] += $affectedrows;
                                                        if($affectedrows > 0) {
                                                            AddHistory(array('type' => 'edit', 'flags' => 'system', 'description' => "Added to group: {$groupname}", 'author' => $AUTHENTICATION['Person']['PersonID'], 'PersonID' => $data['PersonID']));
                                                        }
                                                    } else {
                                                        $response['success'] = FALSE;
                                                        $response['errormessage'] =  mysqli_error($SYSTEM_SETTINGS["Database"]);
                                                        $response['errorcode'] = mysqli_errno($SYSTEM_SETTINGS["Database"]);
                                                        break;
                                                    }
                                                }
                                            }
                                            if($response['success']) {
                                                $fmt = ($response['addedcount'] > 0 ? 'success' : 'info');
                                                AddNotification(array(
                                                    'type' => $fmt,
                                                    'messages' => array(
                                                        array(
                                                            'caption' => "<{$fmt}><b>Task complete</b></{$fmt}> Add Table to Group: {$groupname}. ",
                                                            'icon' => 'fa-users',
                                                        ),
                                                        array(
                                                            'caption' => SinPlu($response['addedcount'], 'record').' added.',
                                                        ),
                                                    ),
                                                ), $AUTHENTICATION['Token']);                                                        
                                            }
                                        } else {
                                            AddNotification(array(
                                                'type' => 'warning',
                                                'messages' => array(
                                                    array(
                                                        'caption' => "<warning><b>Task aborted</b></warning>: Add to Group: {$groupname}. No data found.",
                                                        'icon' => 'fa-users',
                                                    ),
                                                ),
                                            ), $AUTHENTICATION['Token']);
                                            $response['success'] = TRUE;
                                            //No data to export
                                        }
                                        mysqli_free_result($qry);                                        
                                    } else {
                                        $response['errormessage'] =  mysqli_error($SYSTEM_SETTINGS["Database"]);
                                        $response['errorcode'] = mysqli_errno($SYSTEM_SETTINGS["Database"]);
                                    }                                        
                                } else {
                                    $response['errormessage'] =  mysqli_error($SYSTEM_SETTINGS["Database"]);
                                    $response['errorcode'] = mysqli_errno($SYSTEM_SETTINGS["Database"]);
                                }                                    
                            } else {
                                $response['errormessage'] = 'No query has been specified.';
                                $response['errorcode'] = 1;
                            }
                            if(!$response['success']) {
                                $caption = "Add Table to Group: ".$response['errormessage'];
                                AddToSysLog(array(
                                    'EntryKind' => 'error', 'PersonID' => $AUTHENTICATION['Person']['PersonID'],
                                    'Caption' => 'Task failed',
                                    'Data' => array_merge($_GET, array('QUERIES' => $QUERIES)),
                                    'Description' => $caption.' ['.$response['errorcode'].']',
                                    'Expiry' => $SYSTEM_SETTINGS['ExpiryPolicies']['LogErrors'],
                                ));
                                AddNotification(array(
                                    'type' => 'error',
                                    'messages' => array(
                                        array(
                                            'caption' => "<error><b>Task failed</b></error> ".TextEllipsis($caption, 70).' ['.$response['errorcode'].']',
                                            'icon' => 'fa-times-circle',
                                        ),
                                    ),
                                ), $AUTHENTICATION['Token']);                                 
                            }
                        }
                        break;
                    case 'docFromDatatable':
                        require_once("ggpdf.inc");
                        try {
                            if (CheckRequiredParams(array('inc' => FALSE), $_GET) && (CheckRequiredParams(array('PaperTemplateID' => FALSE), $_POST, FALSE) || CheckRequiredParams(array('PaperTemplateSelector' => FALSE), $_POST, FALSE)) && $AUTHENTICATION['Authenticated']) {
                                $TEMPLATE = new editorPaperTemplate($SYSTEM_SETTINGS['Database'], (!empty($_POST['PaperTemplateSelector']) ? $_POST['PaperTemplateSelector'] : $_POST['PaperTemplateID']) , $SYSTEM_SETTINGS);
                                $doctitle = "Merge document".(!empty($TEMPLATE->Properties['Title']) ? ": ".mysqli_real_escape_string($SYSTEM_SETTINGS["Database"], $TEMPLATE->Properties['Title']) : "");
                                //$docdesc = "Merge document".(!empty($_POST['description']) ? ": ".mysqli_real_escape_string($SYSTEM_SETTINGS["Database"], $_POST['description']) : "");
                                AddToSysLog(array(
                                    'EntryKind' => 'info', 'PersonID' => $AUTHENTICATION['Person']['PersonID'],
                                    'Caption' => 'Document Merge',
                                    'Data' => array_merge($_GET, $_POST),
                                    'Description' => 'Started: '.$doctitle,
                                    'Expiry' => $SYSTEM_SETTINGS['ExpiryPolicies']['Export'],
                                ));
                                ignore_user_abort(true);
                                set_time_limit($SYSTEM_SETTINGS['System']['TimeLimitExport']);
                                $QUERIES = null;
                                $WHERE = array();
                                $HAVING = array();
                                $SEARCHES = array();
                                if(!empty($_GET['sSearch']) && (strlen($_GET['sSearch']) > 1)) {
                                    $SEARCHTERM = mysqli_real_escape_string($SYSTEM_SETTINGS["Database"], PREG_REPLACE("/[^0-9a-zA-Z\pL\s\+\(\)\.\-\#\_\/@']/iu", '', $_GET['sSearch']));
                                } else {
                                    $SEARCHTERM = '';
                                }
                                $BUTTONGROUP = array();
                                $ALLOWED = null;
                                $ORDERS = array();
                                $LIMITCLAUSE = "";
                                //load a file from the include directory to perform the query
                                require(IdentifierStr($_GET['inc']));
                                if(!empty($QUERIES)) {
                                    if (mysqli_multi_query($SYSTEM_SETTINGS["Database"], $QUERIES)) {
                                        //First query = total number of records
                                        $qry = mysqli_use_result($SYSTEM_SETTINGS["Database"]);
                                        $data = mysqli_fetch_row($qry);
                                        $response['recordcount'] = $data[0];
                                        mysqli_free_result($qry);
                                        //Second query = the actual data
                                        if (mysqli_next_result($SYSTEM_SETTINGS["Database"])) {
                                            $qry = mysqli_store_result($SYSTEM_SETTINGS["Database"]);
                                            $response['exportcount'] = mysqli_num_rows($qry);
                                            if($response['exportcount'] > 0) {
                                                $pdf = new GGpdf('P','mm','A4', TRUE);
                                                //$pdf->LegacyBehaviour = 2;
                                                $pdf->SetFont('Arial', '', 10);
                                                if(!empty($TEMPLATE->Properties['PageTemplate'])) {
                                                    $pdf->SetModel($SYSTEM_SETTINGS['Templates'][$TEMPLATE->Properties['PageTemplate']]);
                                                }
                                                $pdf->BeginDocument();
                                                $pagecount = 0;
                                                if(empty($_POST['PaperTemplateSelector'])) {
                                                    $pagesource = $TEMPLATE->GetText();
                                                    $ishtml = $TEMPLATE->Properties['IsHTML'];
                                                }
                                                //file_put_contents("D:\\temp\\merge.txt", "");
                                                while($data = mysqli_fetch_array($qry)) {
                                                    if(empty($data['DoNotContact'])) {
                                                        if($pagecount > 0) {
                                                            $pdf->NewPage(TRUE, TRUE);
                                                            //file_put_contents("D:\\temp\\merge.txt", "\r\n---------------------------------------------------------------\r\n", FILE_APPEND);
                                                        }
                                                        if(!empty($_POST['PaperTemplateSelector'])) {
                                                            $page = null;
                                                            //Get the correct template for this record
                                                            $sql =
                                                            "SELECT PaperTemplateID, IsHTML, MSStatusID, MSGradeID, CategorySelector
                                                             FROM tblpapertemplates
                                                             WHERE ".(is_numeric($_POST['PaperTemplateSelector']) ? "PaperTemplateID = ".intval($_POST['PaperTemplateSelector']) : "Mnemonic = '".IdentifierStr($_POST['PaperTemplateSelector'])."'")."
                                                             ORDER BY -MSStatusID DESC, -MSGradeID DESC, -CategorySelector DESC, LastModified DESC, PaperTemplateID";
                                                            if(($query = mysqli_query($SYSTEM_SETTINGS['Database'], $sql))) {
                                                                $temp = null;
                                                                $foundcount = mysqli_num_rows($query);
                                                                if($foundcount > 0) {
                                                                    if($foundcount == 1) {
                                                                        $temp = mysqli_fetch_assoc($query);
                                                                    } else {
                                                                        $temps = mysqli_fetch_all($query, MYSQLI_ASSOC);
                                                                        $temps = array_filter($temps, function($t) use ($data) {
                                                                                $Keep = TRUE;
                                                                                foreach(array('MSStatusID', 'MSGradeID', 'CategorySelector') AS $testkey) {
                                                                                    if(!is_null($t[$testkey])) {
                                                                                        $v = FindInArrays(array($data), $testkey);
                                                                                        if(!is_null($v) && ($t[$testkey] != $v)) {
                                                                                            $Keep = FALSE;
                                                                                            break;
                                                                                        }
                                                                                    }
                                                                                }
                                                                                return $Keep;                                                                
                                                                            }
                                                                        );
                                                                        $temp = array_shift($temps);
                                                                    }
                                                                    if(!empty($temp)) {
                                                                        $templ = new editorPaperTemplate($SYSTEM_SETTINGS['Database'], $temp['PaperTemplateID'], $SYSTEM_SETTINGS);
                                                                        $page = $templ->GetText();
                                                                        $ishtml = $templ->Properties['IsHTML'];
                                                                    }
                                                                }
                                                            }
                                                        } else {
                                                            $page = $pagesource;
                                                        }
                                                        if(!is_null($page)) {
                                                            ResolveEmbeddedCodes($page, array($data));
                                                            //file_put_contents("D:\\temp\\merge.txt", print_r($data, TRUE), FILE_APPEND);
                                                            $pdf->PrepAndWrite($page, (!empty($ishtml) ? TRUE : FALSE));
                                                            $pagecount++;
                                                        }
                                                    }
                                                }
                                                $response['pagecount'] = $pagecount;
                                                if($response['pagecount'] > 0) {
                                                    $Document = $pdf->Output('', 'S');
                                                    $stored = FALSE;
                                                    if($SYSTEM_SETTINGS["System"]["FileStore"] == 'S3') {
                                                        try {
                                                            $s3 = S3Client::factory(array(
                                                                'credentials' => array(
                                                                    'key' => $SYSTEM_SETTINGS['Credentials']['AWS']['S3']['AccessKey'],
                                                                    'secret' => Decrypt($SYSTEM_SETTINGS['Credentials']['AWS']['S3']['SecretKey']),
                                                                ),
                                                                'region'  => $SYSTEM_SETTINGS['Storage']['Region']
                                                            ));
                                                            $objectname = RandomString(64).'.pdf';
                                                            while($s3->doesObjectExist($SYSTEM_SETTINGS['Storage']['Bucket'], $objectname)) {
                                                                $objectname = RandomString(64).'.pdf';
                                                            }
                                                            $s3->putObject(array(
                                                                    'Bucket' => $SYSTEM_SETTINGS['Storage']['Bucket'],
                                                                    'Key'    => $objectname,
                                                                    'Body'   => $Document,
                                                            ));
                                                            // Poll the object until it is accessible
                                                            $s3->waitUntil('ObjectExists', array(
                                                                'Bucket' => $SYSTEM_SETTINGS['Storage']['Bucket'],
                                                                'Key'    => $objectname
                                                            ));
                                                            $stored = true;
                                                        } catch( Exception $e ) {
                                                            $stored = false;
                                                            AddToSysLog(array('EntryKind' => 'warning', 'IsSystem' => TRUE, 'Expiry' => $SYSTEM_SETTINGS['ExpiryPolicies']['LogWarnings'], 'Caption' => 'S3Client error', 'Description' => $response['errormessage'].' ('.$response['errorcode'].') Reverting to database storage.', 'Data' => array('File' => __FILE__, 'Line' => __LINE__)));
                                                        }
                                                    }
                                                    $response['title'] = $doctitle;
                                                    $sql =
                                                    "INSERT INTO tbldocument (LastModified, DocTitle, `Filename`, `Mimetype`, Bucket, Objectname, Data, Expires)
                                                     VALUES (UTC_TIMESTAMP(),
                                                        '{$doctitle}',
                                                        '".(!empty($TEMPLATE->Properties['Title']) ? mysqli_real_escape_string($SYSTEM_SETTINGS["Database"], trim(FilenameStr($TEMPLATE->Properties['Title']))) : "document").".pdf',
                                                        'application/pdf',
                                                        ".($stored && ($SYSTEM_SETTINGS["System"]["FileStore"] == 'S3') ? "'".mysqli_real_escape_string($SYSTEM_SETTINGS['Database'], $SYSTEM_SETTINGS['Storage']['Bucket'])."'" : "NULL").",
                                                        ".($stored && ($SYSTEM_SETTINGS["System"]["FileStore"] == 'S3') ? "'".mysqli_real_escape_string($SYSTEM_SETTINGS['Database'], $objectname)."'" : "NULL").",
                                                        ".(!$stored ? "'".mysqli_real_escape_string($SYSTEM_SETTINGS["Database"], $Document)."'" : "NULL").",
                                                        DATE_ADD(UTC_TIMESTAMP(), INTERVAL ".max(1, intval($SYSTEM_SETTINGS["ExpiryPolicies"]['Export']))." DAY)
                                                     )";
                                                     $response2 = ExecuteSQL($sql);
                                                     if(!$response2['success']) {
                                                        throw new crmException($response2['errormessage'], $response2['errorcode']);
                                                     }
                                                     $documentid = mysqli_insert_id($SYSTEM_SETTINGS["Database"]);
                                                     $response['documentid'] = $documentid;
                                                     $sql = 
                                                     "INSERT INTO tbldocumenttoperson (DocumentID, PersonID) VALUES ({$documentid}, {$AUTHENTICATION['Person']['PersonID']});
                                                      INSERT INTO tblrecentfile (DocumentID, PersonID) VALUES ({$documentid}, {$AUTHENTICATION['Person']['PersonID']});
                                                     ";
                                                     MultiQueryExecute($SYSTEM_SETTINGS["Database"], $sql);
                                                     AddHistory(array('type' => 'edit', 'flags' => 'system', 'description' => "Document uploaded: {$doctitle}", 'PersonID' => $AUTHENTICATION['Person']['PersonID']));
                                                     AddDPEntry(array(
                                                        'ActionType' => 'merge',
                                                        'Description' => $doctitle,
                                                        'Purpose' => $_POST['Purpose'],
                                                        'ThirdPartyName' => (!empty($_POST['ThirdPartyName']) ? $_POST['ThirdPartyName'] : null),
                                                        'DocumentID' => $documentid
                                                     ));
                                                     AddNotification(array(
                                                        'type' => 'success',
                                                        'messages' => array(
                                                            array(
                                                                'caption' => "<success><b>Task complete</b></success> ".TextEllipsis($doctitle, 42, 16).". ",
                                                                'icon' => 'fa-file-pdf-o',
                                                            ),
                                                            array(
                                                                'caption' => SinPlu($response['pagecount'], 'page').' written.',
                                                                'script' => "DownloadDocument({$documentid});",
                                                            ),
                                                        ),
                                                     ), $AUTHENTICATION['Token']);                                                        
                                                     unset($Document);
                                                     $response['success'] = TRUE;
                                                } else {
                                                    AddNotification(array(
                                                        'type' => 'warning',
                                                        'messages' => array(
                                                            array(
                                                                'caption' => "<warning><b>Task ended</b></warning> ".TextEllipsis($doctitle, 42, 16).". The document is empty.",
                                                                'icon' => 'fa-file-pdf-o',
                                                            ),
                                                        ),
                                                    ), $AUTHENTICATION['Token']);
                                                    $response['success'] = TRUE;
                                                }
                                            } else {
                                                AddNotification(array(
                                                    'type' => 'warning',
                                                    'messages' => array(
                                                        array(
                                                            'caption' => "<warning><b>Task aborted</b></warning> ".TextEllipsis($doctitle, 42, 16).". No data found.",
                                                            'icon' => 'fa-file-pdf-o',
                                                        ),
                                                    ),
                                                ), $AUTHENTICATION['Token']);
                                                $response['success'] = TRUE;
                                                //No data to export
                                            }
                                            mysqli_free_result($qry);
//                                          $response['success'] = TRUE;
                                        } else {
                                            $response['errormessage'] =  mysqli_error($SYSTEM_SETTINGS["Database"]);
                                            $response['errorcode'] = mysqli_errno($SYSTEM_SETTINGS["Database"]);
                                        }
                                    } else {
                                        $response['errormessage'] =  mysqli_error($SYSTEM_SETTINGS["Database"]);
                                        $response['errorcode'] = mysqli_errno($SYSTEM_SETTINGS["Database"]);
                                    }
                                } else {
                                    $response['errormessage'] = 'No query has been specified.';
                                    $response['errorcode'] = 1;
                                }
                                //Notifications                                
                            }
                            if(!$response['success']) {
                                throw new crmException($response['errormessage'], $response['errorcode']);
                            }                                
                        } catch( Exception $e ) {
                            $response['success'] = FALSE;
                            $response['errormessage'] = $e->getMessage();
                            $response['errorcode'] = $e->getCode();                            
                            AddToSysLog(array('EntryKind' => 'error', 'IsSystem' => TRUE, 'Expiry' => $SYSTEM_SETTINGS['ExpiryPolicies']['LogErrors'], 'Caption' => 'Document Merge', 'Description' => 'The merge task has raised an exception: '.$response['errormessage'].' ('.$response['errorcode'].')', 'Data' => array('File' => __FILE__, 'Line' => __LINE__)));
                        }
                        break;
                    case 'bulksend':
                        try {
                            $EDITORITEM = editorItemFactory::create($SYSTEM_SETTINGS['Database'], $_GET, $SYSTEM_SETTINGS);
                            $EDITORITEM->SetText($_POST['Body']);
                            AddToSysLog(array(
                                'EntryKind' => 'info', 'PersonID' => $AUTHENTICATION['Person']['PersonID'],
                                'Caption' => 'Bulk Email',
                                'Data' => array_merge($_GET, $_POST),
                                'Description' => 'Started: '.$EDITORITEM->Properties['Subject'],
                                'Expiry' => $SYSTEM_SETTINGS['ExpiryPolicies']['Export'],
                            ));
                            ignore_user_abort(true);
                            set_time_limit($SYSTEM_SETTINGS['System']['TimeLimitExport']);
                            $QUERIES = null;
                            $WHERE = array();
                            $HAVING = array();
                            $SEARCHES = array();
                            //Do not start search unless there are at least 2 characters to search for 
                            if(!empty($_GET['sSearch']) && (strlen($_GET['sSearch']) > 1)) {
                                $SEARCHTERM = mysqli_real_escape_string($SYSTEM_SETTINGS["Database"], PREG_REPLACE("/[^0-9a-zA-Z\pL\s\+\(\)\.\-\#\_\/@']/iu", '', $_GET['sSearch']));
                            } else {
                                $SEARCHTERM = '';
                            }
                            $BUTTONGROUP = array();
                            $ALLOWED = null;
                            $ORDERS = array();
                            $LIMITCLAUSE = "";
                            //load a file from the include directory to perform the query
                            require(IdentifierStr($_GET['inc']));
                            if(!empty($QUERIES)) {
                                if (mysqli_multi_query($SYSTEM_SETTINGS["Database"], $QUERIES)) {
                                    //First query = total number of records
                                    $qry = mysqli_use_result($SYSTEM_SETTINGS["Database"]);
                                    $data = mysqli_fetch_row($qry);
                                    $response['recordcount'] = $data[0];
                                    mysqli_free_result($qry);
                                    if (mysqli_next_result($SYSTEM_SETTINGS["Database"])) {
                                        $qry = mysqli_store_result($SYSTEM_SETTINGS["Database"]);
                                        $response['exportcount'] = mysqli_num_rows($qry);
                                        if($response['exportcount'] > 0) {
                                            $emailcount = 0;
                                            $messagetemplate = array();
                                            foreach(array('FromName', 'FromEmail', 'Priority') AS $key) {
                                                $messagetemplate[$key] = $EDITORITEM->Properties[$key];
                                            }
                                            switch($EDITORITEM->Properties['Private']) {
                                                case 1:
                                                    $messagetemplate['Privacy'] = 'private';
                                                    break;
                                                case 2:
                                                    $messagetemplate['Privacy'] = 'confidential';
                                                    break;
                                            }
                                            //file_put_contents("D:\\temp\\emails.txt", "");
                                            while($data = mysqli_fetch_array($qry)) {
                                                $message = $messagetemplate;
                                                if(empty($data['DoNotContact'])) {
                                                    
                                                }
                                                $email = (!empty($data['Email']) ? $data['Email'] : (!empty($data['Emails']) ? $data['Emails'] : null));
                                                if(empty($email)) {
                                                    if(!empty($data['PersonID'])) {
                                                        $sql = "SELECT tblemail.Email FROM tblemail WHERE PersonID = ".intval($data['PersonID']);
                                                        $records = AllRecords($SYSTEM_SETTINGS['Database'], $sql);
                                                        foreach($records AS $record) {
                                                            $email[] = $record['Email'];
                                                        }
                                                    }
                                                }
                                                foreach(array('PersonID', 'OrganisationID') as $key) {
                                                    if(isset($data[$key])) {
                                                        $message[$key] = $data[$key];
                                                    }
                                                }
                                                //file_put_contents("D:\\temp\\emails.txt", print_r($email, TRUE), FILE_APPEND);
                                                if(!empty($email)) {
                                                    $tos = (is_array($email) ? $email : explode(',', $email));
                                                    foreach($tos AS $to) {
                                                        if(IsValidEmailAddress($to)) {
                                                            $message['To'] = $to;
                                                            $message['Subject'] = $EDITORITEM->Properties['Subject'];
                                                            $message['Body'] = $EDITORITEM->Properties['Body'];
                                                            ResolveEmbeddedCodes($message['Subject'], array($data));                                                            
                                                            ResolveEmbeddedCodes($message['Body'], array($data));
                                                            AddToEmailQueue($message);
                                                            $emailcount++;
                                                        }
                                                    }
                                                }
                                            }
                                            $response['emailcount'] = $emailcount;
                                            if($response['emailcount'] > 0) {
                                                AddNotification(array(
                                                    'type' => 'success',
                                                    'messages' => array(
                                                        array(
                                                            'caption' => "<success><b>Task complete</b></success> ".TextEllipsis($EDITORITEM->Properties['Subject'], 42, 16).". ",
                                                            'icon' => 'gi-message_new',
                                                        ),
                                                        array(
                                                            'caption' => SinPlu($response['emailcount'], 'email').' queued.',
                                                        ),
                                                    ),
                                                ), $AUTHENTICATION['Token']);
                                            } else {
                                                AddNotification(array(
                                                    'type' => 'warning',
                                                    'messages' => array(
                                                        array(
                                                            'caption' => "<warning><b>Task ended</b></warning> ".TextEllipsis($EDITORITEM->Properties['Subject'], 42, 16).". No email addresses found.",
                                                            'icon' => 'gi-message_new',
                                                        ),
                                                    ),
                                                ), $AUTHENTICATION['Token']);
                                            }                                               
                                        } else {
                                            AddNotification(array(
                                                'type' => 'warning',
                                                'messages' => array(
                                                    array(
                                                        'caption' => "<warning><b>Task aborted</b></warning> ".TextEllipsis($EDITORITEM->Properties['Subject'], 42, 16).". No data found.",
                                                        'icon' => 'gi-message_new',
                                                    ),
                                                ),
                                            ), $AUTHENTICATION['Token']);
                                        }                                            
                                        mysqli_free_result($qry);
                                        $response['success'] = TRUE;
                                    } else {
                                        $response['errormessage'] =  mysqli_error($SYSTEM_SETTINGS["Database"]);
                                        $response['errorcode'] = mysqli_errno($SYSTEM_SETTINGS["Database"]);
                                    }
                                } else {
                                    $response['errormessage'] =  mysqli_error($SYSTEM_SETTINGS["Database"]);
                                    $response['errorcode'] = mysqli_errno($SYSTEM_SETTINGS["Database"]);
                                }                                    
                            } else {
                                $response['errormessage'] = 'No query has been specified.';
                                $response['errorcode'] = 1;
                            }
                            if(!$response['success']) {
                                AddNotification(array(
                                    'type' => 'error',
                                    'messages' => array(
                                        array(
                                            'caption' => "<error><b>Task failed</b></error> ".TextEllipsis($EDITORITEM->Properties['Subject'], 42, 16).' ['.$response['errorcode'].']',
                                            'icon' => 'fa-times-circle',
                                        ),
                                    ),
                                ), $AUTHENTICATION['Token']);
                                throw new crmException($response['errormessage'], $response['errorcode']);                                 
                            }
                        } catch( Exception $e ) {
                            $response['success'] = FALSE;
                            $response['errormessage'] = $e->getMessage();
                            $response['errorcode'] = $e->getCode();                            
                            AddToSysLog(array('EntryKind' => 'error', 'IsSystem' => TRUE, 'Expiry' => $SYSTEM_SETTINGS['ExpiryPolicies']['LogErrors'], 'Caption' => 'Bulk Email', 'Description' => 'The bulk email task has raised an exception: '.$response['errormessage'].' ('.$response['errorcode'].')', 'Data' => array('File' => __FILE__, 'Line' => __LINE__)));
                        }
                        break;
                    case 'createTableEmailTask':
                        try {
                            if (CheckRequiredParams(array('Subject' => FALSE, 'Request' => false, 'From' => FALSE), $_POST) && $AUTHENTICATION['Authenticated']) {
                                $setSQL = new stmtSQL('INSERT', 'tblbulkemail', $SYSTEM_SETTINGS["Database"]);
                                $setSQL->addField('Subject', 'string', $_POST);
                                $setSQL->addField('Request', 'string', $_POST);
                                $setSQL->addField('SourceURL', 'string', $_POST);
                                $setSQL->addFieldStmt('Created', 'UTC_TIMESTAMP()');
                                $setSQL->addField('Description', 'string', $_POST);
                                $setSQL->addField('Priority', 'integer', min(10, intval($_POST['Priority'])));
                                $setSQL->addField('Private', 'integer', min(2, intval($_POST['Private'])));
                                if(!empty($_POST['From'])) {
                                    $apos = strrpos($_POST['From'], ' (');
                                    if($apos > 0) {
                                        $name = substr($_POST['From'], 0, $apos);
                                        $email = substr($_POST['From'], $apos+2, strlen($_POST['From'])-$apos-3);
                                        $setSQL->addField('FromName', 'string', (!empty($name) ? $name : $SYSTEM_SETTINGS['System']['Defaults']['FromName']));
                                        $setSQL->addField('FromEmail', 'string', (IsValidEmailAddress($email) ? $email : $SYSTEM_SETTINGS['System']['Defaults']['FromEmail']));
                                    }
                                } else {
                                    $setSQL->addField('FromName', 'string', $SYSTEM_SETTINGS['System']['Defaults']['FromName']);
                                    $setSQL->addField('FromEmail', 'string', $SYSTEM_SETTINGS['System']['Defaults']['FromEmail']);
                                }
                                $response = ExecuteSQL($setSQL);
                                $response['bulkemailid'] = mysqli_insert_id($SYSTEM_SETTINGS["Database"]);
                            }
                            if(!$response['success']) {
                                throw new crmException($response['errormessage'], $response['errorcode']);
                            }                                
                        } catch( Exception $e ) {
                            $response['success'] = FALSE;
                            $response['errormessage'] = $e->getMessage();
                            $response['errorcode'] = $e->getCode();                            
                            AddToSysLog(array('EntryKind' => 'error', 'IsSystem' => TRUE, 'Expiry' => $SYSTEM_SETTINGS['ExpiryPolicies']['LogErrors'], 'Caption' => 'Bulk Email', 'Description' => 'The bulk email task has raised an exception: '.$response['errormessage'].' ('.$response['errorcode'].')', 'Data' => array('File' => __FILE__, 'Line' => __LINE__)));
                        }
                        break;
                    case 'exportDatatable':
                        try {
                            if (CheckRequiredParams(array('inc' => FALSE), $_GET) && $AUTHENTICATION['Authenticated']) {
                                $doctitle = "Table export".(!empty($_POST['description']) ? ": ".mysqli_real_escape_string($SYSTEM_SETTINGS["Database"], $_POST['description']) : "");
                                AddToSysLog(array(
                                    'EntryKind' => 'info', 'PersonID' => $AUTHENTICATION['Person']['PersonID'],
                                    'Caption' => 'Data Export',
                                    'Data' => array_merge($_GET, $_POST),
                                    'Description' => 'Started: '.$doctitle,
                                    'Expiry' => $SYSTEM_SETTINGS['ExpiryPolicies']['Export'],
                                ));
                                ignore_user_abort(true);
                                set_time_limit($SYSTEM_SETTINGS['System']['TimeLimitExport']);
                                $QUERIES = null;
                                $WHERE = array();
                                $HAVING = array();
                                $SEARCHES = array();
                                if(!empty($_GET['sSearch']) && (strlen($_GET['sSearch']) > 1)) {
                                    $SEARCHTERM = mysqli_real_escape_string($SYSTEM_SETTINGS["Database"], PREG_REPLACE("/[^0-9a-zA-Z\pL\s\+\(\)\.\-\#\_\/@']/iu", '', $_GET['sSearch']));
                                } else {
                                    $SEARCHTERM = '';
                                }
                                $BUTTONGROUP = array();
                                $ALLOWED = null;
                                $ORDERS = array();
                                $LIMITCLAUSE = "";
                                //load a file from the include directory to perform the query
                                require(IdentifierStr($_GET['inc']));
                                if(!empty($QUERIES)) {
                                    if (mysqli_multi_query($SYSTEM_SETTINGS["Database"], $QUERIES)) {
                                        //First query = total number of records
                                        $qry = mysqli_use_result($SYSTEM_SETTINGS["Database"]);
                                        $data = mysqli_fetch_row($qry);
                                        $response['recordcount'] = $data[0];
                                        mysqli_free_result($qry);
                                        //Second query = the actual data
                                        if (mysqli_next_result($SYSTEM_SETTINGS["Database"])) {
                                            $qry = mysqli_store_result($SYSTEM_SETTINGS["Database"]);
                                            $response['exportcount'] = mysqli_num_rows($qry);
                                            if($response['exportcount'] > 0) {
                                                $filename = tempnam(sys_get_temp_dir(), 'exp');
                                                if(defined('__DEBUGMODE') && __DEBUGMODE) {
                                                    $response['tempfile'] = $filename;
                                                }
                                                $handle = fopen($filename, 'w');
                                                if ($handle) {
                                                    WriteBOM($handle);
                                                    //Write fieldnames first
                                                    $fields = mysqli_fetch_fields($qry);
                                                    $count = 0;
                                                    foreach($fields AS $field) {
                                                        if((strcasecmp($field->name, 'ID') == 0) && ($count == 0) && !empty($SYSTEM_SETTINGS['System']['ExcelCSV'])) {
                                                            $field->name = 'id';
                                                        }
                                                        if(CanOutput($field)) {
                                                            fwrite($handle, ($count > 0 ? ',' : ''). '"'.addcslashes($field->name, '"').'"');
                                                            $count++;
                                                        }
                                                    }
                                                    fwrite($handle, "\r\n");
                                                    //Now process the data
                                                    while($data = mysqli_fetch_array($qry)) {
                                                        //Iterate through all the fields of the record
                                                        $count = 0;
                                                        foreach($fields AS $field) {
                                                            if (strcasecmp(substr($data[$field->name],0, 10), '_FUNCTION:') == 0) {
                                                                //Process Function Call
                                                                $funcdata = substr($data[$field->name], 10);
                                                                $rawparams = explode(',', $funcdata);
                                                                $cmd = array_shift($rawparams);
                                                                $cookedparams = array();
                                                                foreach($rawparams AS $aparam) {
                                                                    if ($aparam == '_RECORD_') {
                                                                        $cookedparams[] = $data;
                                                                    } else {
                                                                        $cookedparams[] = $data[$aparam];
                                                                    }
                                                                }
                                                                $funcresult = call_user_func_array($cmd, $cookedparams);
                                                                $data[$field->name] = $funcresult;
                                                                if ($count > 0) {
                                                                    fwrite($handle, ',');
                                                                }
                                                                if (is_numeric($funcresult)) {
                                                                    fwrite($handle, $funcresult);
                                                                } else {
                                                                    fwrite($handle, '"'.addslashes($funcresult).'"');
                                                                }
                                                                $count++;
                                                            } else {
                                                                if(CanOutput($field)) {
                                                                    if ($count > 0) {
                                                                        fwrite($handle, ',');
                                                                    }
                                                                    switch($field->type) {
                                                                        case MYSQLI_TYPE_BIT:
                                                                        case MYSQLI_TYPE_LONG:
                                                                        case MYSQLI_TYPE_TINY:
                                                                        case MYSQLI_TYPE_SHORT:
                                                                        case MYSQLI_TYPE_INT24:
                                                                        case MYSQLI_TYPE_LONGLONG:
                                                                            fwrite($handle, intval($data[$field->name]));
                                                                            break;
                                                                        case MYSQLI_TYPE_FLOAT:
                                                                        case MYSQLI_TYPE_DOUBLE:
                                                                        case 246:
                                                                            fwrite($handle, floatvalExt($data[$field->name]));
                                                                            break;
                                                                        case MYSQLI_TYPE_DATETIME:
                                                                        case MYSQLI_TYPE_TIMESTAMP:
                                                                            fwrite($handle, '"'.date('c', strtotime($data[$field->name])).'"');
                                                                            break;
                                                                        default:
                                                                            if(is_numeric($data[$field->name]) && !empty($SYSTEM_SETTINGS['System']['ExcelCSV'])) {
                                                                                fwrite($handle, '"=""'.addcslashes($data[$field->name], '"').'"""');
                                                                            } else {
                                                                                fwrite($handle, '"'.addcslashes($data[$field->name], '"').'"');
                                                                            }
                                                                    }
                                                                    $count++;
                                                                }
                                                            }
                                                        }
                                                        fwrite($handle, "\r\n");
                                                    }
                                                    fclose($handle);
                                                    if($SYSTEM_SETTINGS["System"]["FileStore"] == 'S3') {
                                                        try {
                                                            $s3 = S3Client::factory(array(
                                                                'credentials' => array(
                                                                    'key' => $SYSTEM_SETTINGS['Credentials']['AWS']['S3']['AccessKey'],
                                                                    'secret' => Decrypt($SYSTEM_SETTINGS['Credentials']['AWS']['S3']['SecretKey']),
                                                                ),
                                                                'region'  => $SYSTEM_SETTINGS['Storage']['Region']
                                                            ));
                                                            $objectname = RandomString(64).'.csv';
                                                            while($s3->doesObjectExist($SYSTEM_SETTINGS['Storage']['Bucket'], $objectname)) {
                                                                $objectname = RandomString(64).'.csv';
                                                            }
                                                            $s3->putObject(array(
                                                                'Bucket'     => $SYSTEM_SETTINGS['Storage']['Bucket'],
                                                                'Key'        => $objectname,
                                                                'SourceFile' => $filename,
                                                            ));
                                                            // Poll the object until it is accessible
                                                            $s3->waitUntil('ObjectExists', array(
                                                                'Bucket' => $SYSTEM_SETTINGS['Storage']['Bucket'],
                                                                'Key'    => $objectname
                                                            ));
                                                            $stored = true;
                                                        } catch( Exception $e ) {
                                                            $stored = false;
                                                            AddToSysLog(array('EntryKind' => 'warning', 'IsSystem' => TRUE, 'Expiry' => $SYSTEM_SETTINGS['ExpiryPolicies']['LogWarnings'], 'Caption' => 'S3Client error', 'Description' => $response['errormessage'].' ('.$response['errorcode'].') Reverting to database storage.', 'Data' => array('File' => __FILE__, 'Line' => __LINE__)));
                                                        }
                                                    }
                                                    $response['title'] = $doctitle;
                                                    $sql =
                                                    "INSERT INTO tbldocument (LastModified, DocTitle, `Filename`, `Mimetype`, Bucket, Objectname, Data, Expires)
                                                     VALUES (UTC_TIMESTAMP(),
                                                        '{$doctitle}',
                                                        'table data".(!empty($_POST['description']) ? " ".mysqli_real_escape_string($SYSTEM_SETTINGS["Database"], FilenameStr($_POST['description'])) : "").".csv',
                                                        'text/csv',
                                                        ".($stored && ($SYSTEM_SETTINGS["System"]["FileStore"] == 'S3') ? "'".mysqli_real_escape_string($SYSTEM_SETTINGS['Database'], $SYSTEM_SETTINGS['Storage']['Bucket'])."'" : "NULL").",
                                                        ".($stored && ($SYSTEM_SETTINGS["System"]["FileStore"] == 'S3') ? "'".mysqli_real_escape_string($SYSTEM_SETTINGS['Database'], $objectname)."'" : "NULL").",
                                                        ".(!$stored ? "LOAD_FILE(\"".mysqli_real_escape_string($SYSTEM_SETTINGS["Database"], $filename)."\")" : "NULL").",
                                                        DATE_ADD(UTC_TIMESTAMP(), INTERVAL ".max(1, intval($SYSTEM_SETTINGS["ExpiryPolicies"]['Export']))." DAY)
                                                     )";
                                                    $response2 = ExecuteSQL($sql);
                                                    if($response2['success']) {
                                                        $documentid = mysqli_insert_id($SYSTEM_SETTINGS["Database"]);
                                                        $response['documentid'] = $documentid;
                                                        $sql = 
                                                        "INSERT INTO tbldocumenttoperson (DocumentID, PersonID) VALUES ({$documentid}, {$AUTHENTICATION['Person']['PersonID']});
                                                         INSERT INTO tblrecentfile (DocumentID, PersonID) VALUES ({$documentid}, {$AUTHENTICATION['Person']['PersonID']});
                                                         ";
                                                        MultiQueryExecute($SYSTEM_SETTINGS["Database"], $sql);
                                                        AddHistory(array('type' => 'edit', 'flags' => 'system', 'description' => "Document uploaded: {$doctitle}", 'PersonID' => $AUTHENTICATION['Person']['PersonID']));
                                                        $response['success'] = TRUE;
                                                        AddDPEntry(array(
                                                            'ActionType' => 'export',
                                                            'Description' => $doctitle,
                                                            'Purpose' => $_POST['Purpose'],
                                                            'ThirdPartyName' => (!empty($_POST['ThirdPartyName']) ? $_POST['ThirdPartyName'] : null),
                                                            'DocumentID' => $documentid
                                                        ));
                                                        AddNotification(array(
                                                            'type' => 'success',
                                                            'messages' => array(
                                                                array(
                                                                    'caption' => "<success><b>Task complete</b></success> ".TextEllipsis($doctitle, 42, 16).". ",
                                                                    'icon' => 'gi-cloud-upload',
                                                                ),
                                                                array(
                                                                    'caption' => SinPlu($response['exportcount'], 'record').' written.',
                                                                    'script' => "DownloadDocument({$documentid});",
                                                                ),
                                                            ),
                                                        ), $AUTHENTICATION['Token']);                                                        
                                                    } else {
                                                        $response['errormessage'] = $response2['errormessage'];
                                                        $response['errorcode'] = $response2['errorcode'];
                                                    }
                                                    unlink($filename);
                                                }
                                            } else {
                                                AddNotification(array(
                                                    'type' => 'warning',
                                                    'messages' => array(
                                                        array(
                                                            'caption' => "<warning><b>Task aborted</b></warning> ".TextEllipsis($doctitle, 42, 16).". No data found.",
                                                            'icon' => 'gi-cloud-upload',
                                                        ),
                                                    ),
                                                ), $AUTHENTICATION['Token']);
                                                $response['success'] = TRUE;
                                                //No data to export
                                            }
                                            mysqli_free_result($qry);
//                                          $response['success'] = TRUE;
                                        } else {
                                            $response['errormessage'] =  mysqli_error($SYSTEM_SETTINGS["Database"]);
                                            $response['errorcode'] = mysqli_errno($SYSTEM_SETTINGS["Database"]);
                                        }
                                    } else {
                                        $response['errormessage'] =  mysqli_error($SYSTEM_SETTINGS["Database"]);
                                        $response['errorcode'] = mysqli_errno($SYSTEM_SETTINGS["Database"]);
                                    }
                                } else {
                                    $response['errormessage'] = 'No query has been specified.';
                                    $response['errorcode'] = 1;
                                }
                                if(!$response['success']) {
                                    $caption = (!empty($doctitle) ? $doctitle : "Data export").": ".$response['errormessage'];
                                    AddToSysLog(array(
                                        'EntryKind' => 'error', 'PersonID' => $AUTHENTICATION['Person']['PersonID'],
                                        'Caption' => 'Task failed',
                                        'Data' => array_merge($_GET, array('QUERIES' => $QUERIES)),
                                        'Description' => $caption.' ['.$response['errorcode'].']',
                                        'Expiry' => $SYSTEM_SETTINGS['ExpiryPolicies']['LogErrors'],
                                    ));
                                    AddNotification(array(
                                        'type' => 'error',
                                        'messages' => array(
                                            array(
                                                'caption' => "<error><b>Task failed</b></error> ".TextEllipsis($caption, 70).' ['.$response['errorcode'].']',
                                                'icon' => 'fa-times-circle',
                                            ),
                                        ),
                                    ), $AUTHENTICATION['Token']);                                 
                                }
                            }
                            if(!$response['success']) {
                                throw new crmException($response['errormessage'], $response['errorcode']);
                            }                                
                        } catch( Exception $e ) {
                            $response['success'] = FALSE;
                            $response['errormessage'] = $e->getMessage();
                            $response['errorcode'] = $e->getCode();                            
                            AddToSysLog(array('EntryKind' => 'error', 'IsSystem' => TRUE, 'Expiry' => $SYSTEM_SETTINGS['ExpiryPolicies']['LogErrors'], 'Caption' => 'Data Export', 'Description' => 'The export task has raised an exception: '.$response['errormessage'].' ('.$response['errorcode'].')', 'Data' => array('File' => __FILE__, 'Line' => __LINE__)));
                        }
                        break;
                    case 'locateperson':
                        if ((!empty($_POST['Email']) || !empty($_POST['MSNumber'])) && $AUTHENTICATION['Authenticated']) {
                            $where = "";
                            if(!empty($_POST['MSNumber'])) {
                                $where .= (empty($where) ? "" : " OR ")."(tblperson.MSNumber = '".mysqli_real_escape_string($SYSTEM_SETTINGS["Database"], $_POST['MSNumber'])."')";
                            } 
                            if(!empty($_POST['Email'])) {
                                $where .= (empty($where) ? "" : " OR ")."(tblemail.Email = '".mysqli_real_escape_string($SYSTEM_SETTINGS["Database"], $_POST['Email'])."')";
                            }
                            $sql = 
                            "SELECT tblperson.PersonID
                             FROM tblperson
                             LEFT JOIN tblemail ON tblemail.PersonID = tblperson.PersonID
                             WHERE {$where}";
                            $query = mysqli_query($SYSTEM_SETTINGS["Database"], $sql);
                            if ($query) {
                                $response['success'] = TRUE;
                                $response['matchcount'] = 0;
                                $response['matches'] = array();
                                while($row = mysqli_fetch_assoc($query)) {
                                    $data = array();
                                    if(!empty($_POST['Data'])) {
                                        $person = new crmPerson('eventsPerson', $SYSTEM_SETTINGS['Database'], $row['PersonID'], $SYSTEM_SETTINGS["Membership"]);
                                        $sections = explode(',', $_POST['Data']);
                                        foreach($sections AS $section) {
                                            $data[$section] = $person->GetRecord($section);
                                        }
                                    }
                                    $response['matches'][$row['PersonID']] = $data;
                                    $response['matchcount']++;
                                }
                            } else {
                                $response['errormessage'] =  mysqli_error($SYSTEM_SETTINGS["Database"]);
                                $response['errorcode'] = mysqli_errno($SYSTEM_SETTINGS["Database"]);                            
                            }
                        }
                        break;
                    case 'quicksearch':
                        $where = null;
                        $response['errorcode'] = 1;
                        $response['errormessage'] = "No matching records were found!";
                        $searchterm = TRIM(PREG_REPLACE("/[^0-9a-zA-Z\pL\s\+\(\)\.\-\#\_\/@']/iu", '', $_POST['inputQuickSearch']));
                        if ((preg_match('/[0-3][0-9][\.\-\/][0-1][0-9][\.\-\/]([12][90])*([0-9]{2})/', $searchterm) > 0)
                                    ||
                            (preg_match('/([12][90])*([0-9]{2})[\.\-\/][0-1][0-9][\.\-\/][0-3][0-9]/', $searchterm) > 0)) {
                            //DOB
                            $response['searchtype'] = 'dob';
                            $searchterm = ValidDateStr($searchterm);
                            $where = "tblperson.DOB = ".ValueAsType($SYSTEM_SETTINGS["Database"], $searchterm, 'date');
                        } elseif ((strcasecmp($SYSTEM_SETTINGS["Membership"]["Numbering"]["Type"], "Structured") == 0) && (preg_match('/([pPCc]{1})([0-9]{6})/', $searchterm) > 0)) {
                            //Membership No (Structured Type)
                            $response['searchtype'] = 'msnumber';
                            $where = "tblperson.MSNumber = ".ValueAsType($SYSTEM_SETTINGS["Database"], $searchterm, 'string');
                        } elseif ((strcasecmp($SYSTEM_SETTINGS["Membership"]["Numbering"]["Type"], "NumericFixed") == 0) && is_numeric($searchterm) && (strlen($searchterm) == $SYSTEM_SETTINGS["Membership"]["Numbering"]["Length"])) {
                            //Membership No (Fixed length numeric type)
                            $response['searchtype'] = 'msnumber';
                            $where = "tblperson.MSNumber = ".ValueAsType($SYSTEM_SETTINGS["Database"], $searchterm, 'string');
                        } elseif (preg_match('/(\#)([0-9]+)/', $searchterm) > 0) {
                            //Record number
                            $response['searchtype'] = 'recnumber';
                            $searchterm = substr($searchterm, 1);
                            $where = "tblperson.PersonID = ".ValueAsType($SYSTEM_SETTINGS["Database"], $searchterm, 'integer');
                        } elseif (IsValidEmailAddress($searchterm)) {
                            //Email address
                            $response['searchtype'] = 'email';
                            $where = "tblemail.Email = ".ValueAsType($SYSTEM_SETTINGS["Database"], $searchterm, 'email');
                        } elseif (preg_match('/([a-z,A-Z]{1,2})([0-9]{1,2})([a-z,A-Z]*)(\s*)([0-9]{1})([a-z,A-Z]{2})/', $searchterm) > 0) {
                            //UK Postcode
                            $response['searchtype'] = 'postcode';
                            $where = "tbladdress.Postcode = ".ValueAsType($SYSTEM_SETTINGS["Database"], $searchterm, 'postcode');
                        } elseif (preg_match('/[\+0]+[0-9\s\-]{3,15}/', $searchterm) > 0) {
                            //Phone number
                            $response['searchtype'] = 'phone';
                            $searchterm = NormalisePhoneNumber($searchterm);
                            if (substr($searchterm, 0, 1) == '0') {
                                $searchterm = substr($searchterm, 1);
                            }
                            $where = "tblpersontophone.PhoneNo LIKE '%".mysqli_real_escape_string($SYSTEM_SETTINGS["Database"], $searchterm)."'";
                        } else {
                            $response['searchtype'] = 'string';
                            $where = "(CONCAT_WS(' ', TRIM(tblperson.Firstname), TRIM(tblperson.Lastname)) LIKE \"%{$searchterm}%\") OR (CONCAT_WS(' ', TRIM(tblperson.Firstname), TRIM(tblperson.Middlenames), TRIM(tblperson.Lastname)) LIKE \"%{$searchterm}%\") OR (CONCAT_WS(' ', TRIM(tblperson.Firstname), TRIM(tblperson.Middlenames), TRIM(tblperson.Lastname)) LIKE \"%{$searchterm}\") OR (CONCAT_WS(' ', TRIM(tblperson.Firstname), TRIM(tblperson.Middlenames)) LIKE \"{$searchterm}%\") OR (CONCAT_WS(' ', LEFT(TRIM(tblperson.Firstname), 1), TRIM(tblperson.Lastname)) = \"{$searchterm}\")";
                        }
                        $response['searchterm'] = $searchterm;
                        $sql = 
                        "SELECT tblperson.PersonID, tblperson.Firstname, tblperson.Middlenames, tblperson.Lastname, tblperson.Title, tblperson.Gender, tblperson.DOB, tblperson.ExtPostnominals,
                                CONCAT_WS(' ', tblperson.Title, tblperson.Firstname, tblperson.Middlenames, tblperson.Lastname) AS `Fullname`,
                                tblperson.Deceased, tblperson.MSNumber, tblperson.DoNotContact, tblperson.NoMarketing, tblperson.ISO3166, tblcountry.Country, tblcountry.MSFeeMultiplier,
                                tblpersonms.BeginDate, tblpersonms.EndDate, tblpersonms.MSFlags,
                                tblmsstatus.MSStatusID, tblmsstatus.MSStatusCaption, tblmsstatus.MSStatusFlags,
                                tblmsgrade.MSGradeID, tblmsgrade.GradeCaption,
                                COALESCE(tblmsstatus.MSStatusCaption, 'Not a Member') AS `MSStatusTxt`,
                                COALESCE(tblmsgrade.GradeCaption, '') AS `MSGradeText`,
                                        IF(tblperson.Deceased IS NOT NULL,
           '<muted><i>',
           IF(FIND_IN_SET('ismember', tblmsstatus.MSStatusFlags),
              IF(NOT FIND_IN_SET('msbenefits', tblmsstatus.MSStatusFlags),
                 '<warning>',
                 IF(tblperson.MSNextRenewal < CURRENT_DATE(),
                    '<warning>',
                    IF(CURRENT_DATE() >= DATE_SUB(tblperson.MSNextRenewal, INTERVAL {$SYSTEM_SETTINGS['Membership']['RenewalCycleStart']} DAY),
                       '<info>',
                       '<success>'
                    ) 
                 )
              ),
              ''
           )
        ) AS `MSFmt`,
        IF(tblperson.Deceased IS NOT NULL,
           'muted',
           IF(FIND_IN_SET('ismember', tblmsstatus.MSStatusFlags),
              IF(FIND_IN_SET('norenewal', tblperson.MSFlags) OR (NOT FIND_IN_SET('msbenefits', tblmsstatus.MSStatusFlags)),
                 'warning',
                 IF(tblperson.MSNextRenewal < CURRENT_DATE(),
                    'warning',
                    IF(CURRENT_DATE() >= DATE_SUB(tblperson.MSNextRenewal, INTERVAL {$SYSTEM_SETTINGS['Membership']['RenewalCycleStart']} DAY),
                       'info',
                       'success'
                    ) 
                 )
              ),
              'default'
           )
        ) AS `MSColour`,
        IF(tblperson.Deceased IS NOT NULL,
           'Deceased',
           IF(FIND_IN_SET('ismember', tblmsstatus.MSStatusFlags),
              CONCAT_WS(', ',
                        IF(NOT FIND_IN_SET('msbenefits', tblmsstatus.MSStatusFlags),
                             'Lapsing',
                             IF(tblperson.MSNextRenewal < CURRENT_DATE(),
                                'Renewal Overdue',
                                IF(CURRENT_DATE() >= DATE_SUB(tblperson.MSNextRenewal, INTERVAL {$SYSTEM_SETTINGS['Membership']['RenewalCycleStart']} DAY),
                                   'Renewal Pending',
                                   'Up-to-date'
                                )
                             )
                        ),
                        COALESCE(tblmsgrade.GradeCaption, '')
              ),
              'Not a Member'
           )
        ) AS `MSText`,

                                IF(FIND_IN_SET('ismember', tblmsstatus.MSStatusFlags), 1, 0) AS `IsMember`,
                                IF(FIND_IN_SET('msbenefits', tblmsstatus.MSStatusFlags), 1, 0) AS `MSBenefits`,
		                        CONCAT_WS(', ', COALESCE(tblmsstatus.MSStatusCaption, 'Not a Member'), IF(FIND_IN_SET('ismember',  tblmsstatus.MSStatusFlags), COALESCE(tblmsgrade.GradeCaption, ''), NULL)) AS `MSText`,
                                GROUP_CONCAT(DISTINCT tblemail.Email SEPARATOR ';') AS `Emails`,
                                GROUP_CONCAT(DISTINCT tblpersontophone.PhoneNo SEPARATOR ';') AS `Phones`
                        FROM tblperson
                        LEFT JOIN tblcountry ON tblcountry.ISO3166 = tblperson.ISO3166
                        LEFT JOIN tblpersonms ON (tblpersonms.PersonID = tblperson.PersonID) AND (tblpersonms.BeginDate <= UTC_TIMESTAMP()) AND ((tblpersonms.EndDate IS NULL) OR (tblpersonms.EndDate >= UTC_TIMESTAMP()))
                        LEFT JOIN tblmsstatus ON (tblmsstatus.MSStatusID = tblpersonms.MSStatusID)
                        LEFT JOIN tblmsgrade ON (tblmsgrade.MSGradeID = tblpersonms.MSGradeID)
                        LEFT JOIN tblemail ON tblemail.PersonID = tblperson.PersonID
                        LEFT JOIN tblpersontophone ON tblpersontophone.PersonID = tblperson.PersonID
                        LEFT JOIN tbladdresstoperson ON tbladdresstoperson.PersonID = tblperson.PersonID
                        LEFT JOIN tbladdress ON tbladdress.AddressID = tbladdresstoperson.AddressID
                        WHERE ({$where})
                        GROUP BY tblperson.PersonID
                        ORDER BY tblperson.Lastname, tblperson.Lastname, tblperson.Lastname, tblperson.DOB";
                        $queryid = CreateSessionQuery($sql);
                        $query = mysqli_query($SYSTEM_SETTINGS["Database"], $sql);
                        if ($query) {
                            $response['matchcount'] = mysqli_num_rows($query);
                            if($response['matchcount'] > 0) {
                                $response['success'] = TRUE;
                                if($response['matchcount'] == 1) {
                                    $data = $Result = mysqli_fetch_assoc($query);
                                    $response['recordtype'] = 'person';
                                    $response['personid'] = $data['PersonID'];
                                    DeleteSessionQuery($queryid);
                                } else {
                                    $response['queryid'] = $queryid;
                                }
                            }
                        } else {
                            $response['errormessage'] =  mysqli_error($SYSTEM_SETTINGS["Database"]);
                            $response['errorcode'] = mysqli_errno($SYSTEM_SETTINGS["Database"]);                            
                        }
                        break;
                    case 'savesearchcondition':
                        if(!empty($_GET['queryid'])) {
                            $conditions = GetSessionQueryData($_GET['queryid']);
                        } else {
                            $conditions = array();
                        }
                        $cond = array(
                            'Logic' => SqlKeyword($_POST['Logic']),
                            'Fieldname' => IdentifierStr($_POST['Fieldname']),
                            'Operator' => IdentifierStr($_POST['Operator']),
                            'SearchValue' => $_POST['SearchValue']
                        );
                        if(!empty($_POST['ConditionID'])) {
                            $cond['ConditionID'] = intval($_POST['ConditionID']);
                            $conditions[$cond['ConditionID']] = $cond;
                        } else {
                            $conditions[] = $cond;
                            end($conditions);
                            $cond['ConditionID'] = key($conditions);
                            $conditions[$cond['ConditionID']]['ConditionID'] = $cond['ConditionID'];
                        }
                        if(!empty($_GET['queryid'])) {
                            $sql = "UPDATE tblauthquery SET `Data` = '".mysqli_real_escape_string($SYSTEM_SETTINGS["Database"], json_encode($conditions))."' WHERE (AuthQueryID = ".intval($_GET['queryid']).") AND (Token = '".mysqli_real_escape_string($SYSTEM_SETTINGS["Database"], $AUTHENTICATION['Token'])."')";
                            $response['queryid'] = intval($_GET['queryid']);
                            if(mysqli_query($SYSTEM_SETTINGS["Database"], $sql)) {
                                $response['success'] = TRUE;
                            } else {
                                $response['errormessage'] =  mysqli_error($SYSTEM_SETTINGS["Database"]);
                                $response['errorcode'] = mysqli_errno($SYSTEM_SETTINGS["Database"]);                            
                            }
                        } else {
                            $response['queryid'] = CreateSessionQuery('', $conditions);
                            $response['success'] = TRUE;
                        }
                        $response['conditionid'] = $cond['ConditionID'];
                        break;
                    case 'search':
                        //file_put_contents("D:\\temp\\post.txt", print_r($_POST, TRUE));
                        switch($_POST['SYSTEM_SOURCE']) {
                            case 'frmQBE':
                                $WHERE = array();
                                //Record fields
                                foreach(array('Title' => 'string', 'Firstname' => 'string', 'Middlenames' => 'string', 'Lastname' => 'string', 'Gender' => 'string', 'DOB' => 'string',
                                    'ExtPostnominals' => '', 'NationalityID' => 'integer', 'ISO3166' => 'string', 'ISO4217' => 'string', 'Graduation' => 'string', 'PaidEmployment' => 'string',
                                    'Deceased' => 'string', 'MSNumber' => 'string', 'MSOldNumber' => 'string', 'MSNextRenewal' => 'string', 'MSMemberSince' => 'string'
                                ) AS $fieldname => $fieldtype) {
                                    if(!empty($_POST[$fieldname])) {
                                        AddSearchEntry($WHERE, ParseSearchTerm($_POST[$fieldname]), $fieldname, $fieldtype, 'tblperson');
                                    }
                                }
                                //Address fields
                                foreach(array('Lines' => 'string', 'Postcode' => 'string', 'Town' => 'string', 'County' => 'string', 'Region' => 'string', 'ISO3166_A' => 'string'
                                ) AS $fieldname => $fieldtype) {
                                    if(!empty($_POST[$fieldname])) {
                                        AddSearchEntry($WHERE, ParseSearchTerm($_POST[$fieldname]), TextUpTo($fieldname, '_', FALSE, TRUE), $fieldtype, 'tbladdress');
                                    }
                                }
                                //Contact fields - always string type
                                foreach(array('Email' => 'tblemail', 'PhoneNo' => 'tblpersontophone', 'URL' => 'tblemail') AS $fieldname => $tablename) {
                                    if(!empty($_POST[$fieldname])) {
                                        AddSearchEntry($WHERE, ParseSearchTerm($_POST[$fieldname]), $fieldname, 'string', $tablename);
                                    }
                                }
                                $sql = 
                                "SELECT tblperson.PersonID, tblperson.Firstname, tblperson.Middlenames, tblperson.Lastname, tblperson.Title, tblperson.Gender, tblperson.DOB, tblperson.ExtPostnominals,
                                        CONCAT_WS(' ', tblperson.Title, tblperson.Firstname, tblperson.Middlenames, tblperson.Lastname) AS `Fullname`,
                                        tblperson.Deceased, tblperson.MSNumber, tblperson.DoNotContact, tblperson.NoMarketing, tblperson.ISO3166, tblcountry.Country, tblcountry.MSFeeMultiplier,
                                        tblpersonms.BeginDate, tblpersonms.EndDate, tblpersonms.MSFlags,
                                        tblmsstatus.MSStatusID, tblmsstatus.MSStatusCaption, tblmsstatus.MSStatusFlags,
                                        tblmsgrade.MSGradeID, tblmsgrade.GradeCaption,
                                        COALESCE(tblmsstatus.MSStatusCaption, 'Not a Member') AS `MSStatusTxt`,
                                        COALESCE(tblmsgrade.GradeCaption, '') AS `MSGradeText`,
                                                IF(tblperson.Deceased IS NOT NULL,
           '<muted><i>',
           IF(FIND_IN_SET('ismember', tblmsstatus.MSStatusFlags),
              IF(NOT FIND_IN_SET('msbenefits', tblmsstatus.MSStatusFlags),
                 '<warning>',
                 IF(tblperson.MSNextRenewal < CURRENT_DATE(),
                    '<warning>',
                    IF(CURRENT_DATE() >= DATE_SUB(tblperson.MSNextRenewal, INTERVAL {$SYSTEM_SETTINGS['Membership']['RenewalCycleStart']} DAY),
                       '<info>',
                       '<success>'
                    ) 
                 )
              ),
              ''
           )
        ) AS `MSFmt`,
        IF(tblperson.Deceased IS NOT NULL,
           'muted',
           IF(FIND_IN_SET('ismember', tblmsstatus.MSStatusFlags),
              IF(FIND_IN_SET('norenewal', tblperson.MSFlags) OR (NOT FIND_IN_SET('msbenefits', tblmsstatus.MSStatusFlags)),
                 'warning',
                 IF(tblperson.MSNextRenewal < CURRENT_DATE(),
                    'warning',
                    IF(CURRENT_DATE() >= DATE_SUB(tblperson.MSNextRenewal, INTERVAL {$SYSTEM_SETTINGS['Membership']['RenewalCycleStart']} DAY),
                       'info',
                       'success'
                    ) 
                 )
              ),
              'default'
           )
        ) AS `MSColour`,
        IF(tblperson.Deceased IS NOT NULL,
           'Deceased',
           IF(FIND_IN_SET('ismember', tblmsstatus.MSStatusFlags),
              CONCAT_WS(', ',
                        IF(NOT FIND_IN_SET('msbenefits', tblmsstatus.MSStatusFlags),
                             'Lapsing',
                             IF(tblperson.MSNextRenewal < CURRENT_DATE(),
                                'Renewal Overdue',
                                IF(CURRENT_DATE() >= DATE_SUB(tblperson.MSNextRenewal, INTERVAL {$SYSTEM_SETTINGS['Membership']['RenewalCycleStart']} DAY),
                                   'Renewal Pending',
                                   'Up-to-date'
                                )
                             )
                        ),
                        COALESCE(tblmsgrade.GradeCaption, '')
              ),
              'Not a Member'
           )
        ) AS `MSText`,

                                        IF(FIND_IN_SET('ismember', tblmsstatus.MSStatusFlags), 1, 0) AS `IsMember`,
                                        IF(FIND_IN_SET('msbenefits', tblmsstatus.MSStatusFlags), 1, 0) AS `MSBenefits`,
		                                CONCAT_WS(', ', COALESCE(tblmsstatus.MSStatusCaption, 'Not a Member'), IF(FIND_IN_SET('ismember',  tblmsstatus.MSStatusFlags), COALESCE(tblmsgrade.GradeCaption, ''), NULL)) AS `MSText`,
                                        GROUP_CONCAT(DISTINCT tblemail.Email SEPARATOR ';') AS `Emails`,
                                        GROUP_CONCAT(DISTINCT tblpersontophone.PhoneNo SEPARATOR ';') AS `Phones`,
                                        GROUP_CONCAT(DISTINCT CONCAT_WS(', ', REPLACE(REPLACE(TRIM(tbladdress.`Lines`), '\\n', ', '), '\\r', ''), IF(LENGTH(tbladdress.Town)>0, tbladdress.Town, NULL), IF(LENGTH(tbladdress.County)>0, tbladdress.County, NULL), IF(LENGTH(tbladdress.Region)>0, tbladdress.Region, NULL), IF(tbladdresscountry.ISO3166<>'GB', tbladdresscountry.Country, NULL)) SEPARATOR '; ') AS `Addresses`
                                 FROM tblperson
                                 LEFT JOIN tblcountry ON tblcountry.ISO3166 = tblperson.ISO3166
                                 LEFT JOIN tblpersonms ON (tblpersonms.PersonID = tblperson.PersonID) AND (tblpersonms.BeginDate <= UTC_TIMESTAMP()) AND ((tblpersonms.EndDate IS NULL) OR (tblpersonms.EndDate >= UTC_TIMESTAMP()))
                                 LEFT JOIN tblmsstatus ON (tblmsstatus.MSStatusID = tblpersonms.MSStatusID)
                                 LEFT JOIN tblmsgrade ON (tblmsgrade.MSGradeID = tblpersonms.MSGradeID)
                                 LEFT JOIN tblemail ON tblemail.PersonID = tblperson.PersonID
                                 LEFT JOIN tblpersontophone ON tblpersontophone.PersonID = tblperson.PersonID
                                 LEFT JOIN tbladdresstoperson ON tbladdresstoperson.PersonID = tblperson.PersonID
                                 LEFT JOIN tbladdress ON tbladdress.AddressID = tbladdresstoperson.AddressID
                                 LEFT JOIN tblcountry AS tbladdresscountry ON tbladdresscountry.ISO3166 = tbladdress.ISO3166
                                 ".Where($WHERE, "AND", "WHERE", TRUE)."
                                 GROUP BY tblperson.PersonID
                                 ORDER BY tblperson.Lastname, tblperson.Lastname, tblperson.Lastname, tblperson.DOB";
                                break;
                        }
                        if(!empty($sql)) {
                            $queryid = CreateSessionQuery($sql, $_POST);
                            $query = mysqli_query($SYSTEM_SETTINGS["Database"], $sql);
                            if ($query) {
                                $response['queryid'] = $queryid;
                                $response['success'] = TRUE;
                            } else {
                                $response['errormessage'] =  mysqli_error($SYSTEM_SETTINGS["Database"]);
                                $response['errorcode'] = mysqli_errno($SYSTEM_SETTINGS["Database"]);                            
                            }
                        } else
                            header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request");
                        //file_put_contents("D:\\temp\\WHERE.txt", print_r($WHERE, TRUE));
                        break;
                    case 'loadtemplate':
                        LoadTemplate();
                        die();
                        break;
                    default:
                        header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request");
                        die();
                }
            } else {
                header($_SERVER["SERVER_PROTOCOL"]." 403 Forbidden");
                die();    
            }        
    }
    echo json_encode($response);
} else if (isset($_SERVER['HTTP_REFERER']) && (strpos($_SERVER['HTTP_REFERER'], $_SERVER['SERVER_NAME']) == 8)) {
    //Requests made without a X-Requested-With= XMLHttpRequest header
    //Syscall functions that do not require authentication
    switch($do)
    {
        case 'loadtemplate':
            LoadTemplate();
            die();
            break;
        case 'downloadfile':
            $response['found'] = TRUE;
            if(Authenticate() && $AUTHENTICATION['Authenticated']) {
                if(!empty($_GET['documentid'])) {
                    $documentid = intval($_GET['documentid']);
                    $sql = 
                    "SELECT tbldocument.DocumentID, tbldocument.`Filename`, tbldocument.`Mimetype`, tbldocument.DocTitle, tbldocument.Bucket,
                            tbldocument.Objectname, tbldocument.Data,
                            IF((tbldocument.Bucket IS NOT NULL) AND (tbldocument.Objectname IS NOT NULL), 'S3', 'DB') AS `StorageType`,
                            tbldocumenttoperson.PersonID
                     FROM tbldocument
                     LEFT JOIN tbldocumenttoperson ON tbldocumenttoperson.DocumentID = tbldocument.DocumentID
                     WHERE tbldocument.DocumentID = {$documentid}
                    ";
                    $filedata = SingleRecord($SYSTEM_SETTINGS["Database"], $sql);
                    if($filedata['StorageType'] == 'S3') {
                        try {
                            $s3 = S3Client::factory(array(
                                'credentials' => array('key' => $SYSTEM_SETTINGS['Credentials']['AWS']['S3']['AccessKey'],
                                                       'secret' => Decrypt($SYSTEM_SETTINGS['Credentials']['AWS']['S3']['SecretKey']),
                                ),
                                'region'  => $SYSTEM_SETTINGS['Storage']['Region']
                            ));
                            $filedata['Data'] = $s3->getObject(array(
                                'Bucket' => $filedata['Bucket'],
                                'Key' => $filedata['Objectname'],
                            ));
                        } catch( Exception $e ) {
                            AddToSysLog(array('EntryKind' => 'error', 'IsSystem' => TRUE, 'Expiry' => $SYSTEM_SETTINGS['ExpiryPolicies']['LogErrors'], 'Caption' => 'S3Client error', 'Description' => $response['errormessage'].' ('.$response['errorcode'].')', 'Data' => array('File' => __FILE__, 'Line' => __LINE__)));
                        }
                    }
                    header("Content-Type: {$filedata['Mimetype']}");
                    header("Content-Length: ".strlen($filedata['Data']));
                    header("Content-Disposition: inline; filename=\"{$filedata['Filename']}\"");
                    header('Cache-Control: private, max-age=0, must-revalidate');
                    header('Pragma: public');        
                    print $filedata['Data'];
                    die();
                }
            } else {
                header($_SERVER["SERVER_PROTOCOL"]." 403 Forbidden");
                die();
            }
            break;
        case 'getpdf':
            if(Authenticate() && $AUTHENTICATION['Authenticated']) {
                $pdf = array('filename' => '', 'data' => null);
                if(!empty($_GET['papertemplateid'])) {
                    $template = new editorPaperTemplate($SYSTEM_SETTINGS['Database'], intval($_GET['papertemplateid']), $SYSTEM_SETTINGS);
                    $PERSON = new crmPerson('eventsPerson', $SYSTEM_SETTINGS['Database'], $AUTHENTICATION['Person']['PersonID'], $SYSTEM_SETTINGS["Membership"]);
                    $pdf['data'] = $PERSON->PDF($template);
                    $pdf['filename'] = FilenameStr('Test '.$template->Properties['Title'].'.pdf');
                } elseif(!empty($_GET['invoiceid'])) {
                    $invoice = new crmInvoice('eventsFinance', $SYSTEM_SETTINGS['Database'], intval($_GET['invoiceid']), InvoiceSettings());
                    if($invoice->Found) {
                        $pdf['data'] = $invoice->PDF($SYSTEM_SETTINGS["Templates"]['letterhead']);
                        $pdf['filename'] = FilenameStr($invoice->Invoice['InvoiceCaption'].'.pdf');
                    }
                }
                if(!empty($pdf['data'])) {
                    header("Content-Type: application/pdf");
                    header("Content-Length: ".strlen($pdf['data']));
                    header("Content-Disposition: inline; filename=\"{$pdf['filename']}\"");
                    header('Cache-Control: private, max-age=0, must-revalidate');
                    header('Pragma: public');        
                    print $pdf['data'];
                } else {
                    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
                }
            } else {
                header($_SERVER["SERVER_PROTOCOL"]." 403 Forbidden");
            }
            die();
            break;
        default:
            header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request");
            die();
    }
    echo json_encode($response);    
} else {
    header($_SERVER["SERVER_PROTOCOL"]." 403 Forbidden");    
}

function SaveNote( $referencedata = null ) {
    global $SYSTEM_SETTINGS, $AUTHENTICATION;
    $response = array(
        'success' => TRUE,
        'errormessage' => '',
        'errorcode' => 0,
    );
    if(!empty($_POST['NoteText'])) {
        if(!empty($_POST['NoteID'])) {
            $noteid = intval($_POST['NoteID']);
            $sql = "DELETE FROM tblnotetowscategory WHERE NoteID = {$noteid}";
            mysqli_query($SYSTEM_SETTINGS["Database"], $sql);
            $setSQL = new stmtSQL('UPDATE', 'tblnote', $SYSTEM_SETTINGS["Database"]);
            $setSQL->addWhere('NoteID', 'integer', $noteid);
        } else {
            $setSQL = new stmtSQL('INSERT', 'tblnote', $SYSTEM_SETTINGS["Database"]);
            $setSQL->addFieldStmt('Created', 'UTC_TIMESTAMP()');
        }
        $setSQL->addFieldStmt('LastModified', 'UTC_TIMESTAMP()');
        $setSQL->addFieldStmt('Expires', (empty($_POST['NoteNoExpiry']) ? 'DATE_ADD(UTC_TIMESTAMP(), INTERVAL '.$SYSTEM_SETTINGS["ExpiryPolicies"]['Notes'].' MONTH)' : "NULL"));
        $setSQL->addField('NoteText', 'fmttext', $_POST['NoteText']);
        $setSQL->addField('PersonID', 'integer', $AUTHENTICATION['Person']['PersonID']);
        $response = ExecuteSQL($setSQL);
        if($response['success']) {
            if(empty($noteid)) {
                $noteid = mysqli_insert_id( $SYSTEM_SETTINGS["Database"]);
            }
            AddHistory(array(
                'type' => 'edit', 'description' => 'Note '.(empty($_POST['NoteID']) ? 'added' : 'updated').': '.TextEllipsis(HTML2Plain($_POST['NoteText']), 60),
                'PersonID' => (!empty($_POST['PersonID']) ? intval($_POST['PersonID']) : null),
                'OrganisationID' => (!empty($_POST['OrganisationID']) ? intval($_POST['OrganisationID']) : null),
                'author' => $AUTHENTICATION['Person']['PersonID'],
            ), $response['_affectedrows']);
            if(!empty($_POST['Categories'])) {
                $sql = "INSERT INTO tblnotetowscategory (NoteID, WSCategoryID) VALUES ";
                $count = 0;
                foreach($_POST['Categories'] AS $categoryid) {
                    $sql .= ($count == 0 ? "" : ", ")."({$noteid}, ".intval($categoryid).")";
                    $count++;
                }
                $response = ExecuteSQL($sql);
            }
            foreach(array('PersonID' => 'tblnotetoperson', 'OrganisationID' => 'tblnotetoorganisation') AS $fieldname => $tablename) {
                if(isset($_POST[$fieldname]) || isset($referencedata[$fieldname])) {
                    $id = intval((isset($_POST[$fieldname]) ? $_POST[$fieldname] : $referencedata[$fieldname])); 
//                    intval($_POST[$fieldname]);
                    $response[strtolower($fieldname)] = $id;
                    if(empty($_POST['NoteID'])) {
                        $sql = "INSERT INTO {$tablename} (NoteID, {$fieldname}) VALUES ({$noteid}, {$id})";
                        mysqli_query($SYSTEM_SETTINGS["Database"], $sql);                                    
                    }
                }
            }
        }
    }
    return $response;
}

function CanOutput($field) {
    $Result = TRUE;
    if(substr($field->name, 0, 1) == '_') {
        $Result = FALSE;
    } else {
        foreach(array('Salt', 'PWHash', 'Password') AS $fieldname) {
            if(strcasecmp($fieldname, $field->name) == 0) {
                $Result = FALSE;
                break;
            }
        }
    }
    return $Result;
}

function DDIRemoved($data) {
    global $SYSTEM_SETTINGS;
    if(!empty($data['PersonID'])) {
        $PERSON = new crmPerson('eventsPerson', $SYSTEM_SETTINGS['Database'], $data['PersonID'], $SYSTEM_SETTINGS["Membership"]);
        $msapplication = $PERSON->GetOpenApplication('members');
        //file_put_contents("D:\\temp\\application.txt", print_r($msapplication, TRUE));
        if(!empty($msapplication['DDPaid'])) {
            if($msapplication['HasTransaction']) {
                CancelInvoiceItem($msapplication['InvoiceItemID']);
            }
            $setSQL = new stmtSQL('UPDATE', 'tblapplication', $SYSTEM_SETTINGS["Database"]);
            $setSQL->addWhere('ApplicationID', 'integer', $msapplication['ApplicationID']);
            $setSQL->addFieldStmt('Flags', "TRIM(BOTH ',' FROM REPLACE(REPLACE(CONCAT(',', Flags, ','), CONCAT(',', 'paid', ','), ','), CONCAT(',', 'directdebit', ','), ','))");
            $response = ExecuteSQL($setSQL);
            //file_put_contents("D:\\temp\\sql.txt", $setSQL->SQL());
            if($response['success']) {
                $msapplication = $PERSON->GetOpenApplication('members', TRUE);
                CreateApplicationTransaction($msapplication);
            } else {
                throw new crmException('Unable to clear paid flag.', 3);
            }            
        }
        //Open renewals?
        $renewal = $PERSON->RenewalSettings();
        if(!empty($renewal) && $renewal['HasTransaction'] && empty($renewal['Processed'])) {
            $params = array(
                'ISO4217' => $renewal['ISO4217'],
                'MSGradeID' => $renewal['MSGradeID'],
                'ISO3166' => $renewal['ISO3166'],
                'ForDate' => $renewal['MSNextRenewal'],
                'IsDD' => (!empty($renewal['DDIID'])),
                'Free' => $renewal['MSFree'],
                'DiscountID' => $renewal['DiscountID'],
            );
            $msfees = new crmMSFees($SYSTEM_SETTINGS['Database'], $SYSTEM_SETTINGS['Membership']['GradeCaption']);
            $fee = $msfees->CalculateFee($params);
            if($fee->Net <> $renewal['ItemNet']) {
                //Different amount, recreate the transaction
                if($renewal['Draft']) {
                    $invoice = new crmInvoice('eventsFinance', $SYSTEM_SETTINGS['Database'], $renewal['InvoiceID'], InvoiceSettings()); 
                    $invoice->UpdateItem($renewal['InvoiceItemID'], array(
                        'LinkedID' => $renewal['LinkedID'],
                        'ItemNet' => $fee->Net,
                        'ItemVATRate' => $fee->VATRate,
                        'DiscountID' => $renewal['DiscountID'],
                        'ItemDate' => $renewal['MSNextRenewal'],
                        'Description' => '%TypeName%, '.$renewal['GradeCaption'],
                        'Explain' => json_encode($fee->Explanation),
                    ));
                } else {
                    CancelInvoiceItem($renewal['InvoiceItemID']);
                    if($fee->Net <> 0) {
                        $invoice = GetProForma(array('ISO4217' => $renewal['ISO4217'], 'PersonID' => $PERSON->PersonID));
                        $invoice->NewItem(array(
                            'Mnemonic' => 'ms_renewal',
                            'LinkedID' => $renewal['LinkedID'],
                            'ItemNet' => $fee->Net,
                            'ItemVATRate' => $fee->VATRate,
                            'DiscountID' => $renewal['DiscountID'],
                            'ItemDate' => $renewal['MSNextRenewal'],
//                            'Description' => '%TypeName%, '.$renewal['GradeCaption'],
                            'Explain' => json_encode($fee->Explanation),
                        ), $renewal);
                    }
                }
            }
        }
    }
    return;
}

function LoadTemplate() {
    global $AUTHENTICATION, $SYSTEM_SETTINGS;
    if(Authenticate() && $AUTHENTICATION['Authenticated']) {
        $EDITORITEM = editorItemFactory::create($SYSTEM_SETTINGS['Database'], $_GET, $SYSTEM_SETTINGS);
        if(!empty($EDITORITEM)) {
            print $EDITORITEM->GetText();
        } else {
            header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
        }
    } else {
        header($_SERVER["SERVER_PROTOCOL"]." 403 Forbidden");    
    }
    return;
}

?>