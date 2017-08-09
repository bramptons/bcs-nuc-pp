<?php

/**
 * @author Guido Gybels
 * @copyright 2016
 * @project BCS CRM Solution
 * @description This unit contains the external connectivity API; it has no visual properties
 */

require_once("initialise.inc");
require_once('person.inc');
require_once('organisation.inc');
//use Aws\S3\S3Client;

$API_AUTH_TOKEN = '';
$APIResponse = array('success' => FALSE, 'errorcode' => -1, 'errormessage' => 'Invalid request', 'data' => array());
try {
    if(StartTransaction($SYSTEM_SETTINGS["Database"])) {
        if(!empty($_GET['do'])) {
            $do = IdentifierStr($_GET['do']);
            $accesskeyInfo = GetAccessKeyInfo(array('accesskey' => (!empty($_GET['accesskey']) ? IdentifierStr($_GET['accesskey']) : null)));
            if($accesskeyInfo['valid']) {
                $APIRequest = array(
                    'cmd' => $do,
                    'remoteip' => IPAddress((!empty($_GET['remoteip']) ? $_GET['remoteip'] : (!empty($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : getHostByName(getHostName())))),
                    'received' => gmdate('Y-m-d H:i:s'),
                );
                //TOKEN provided?
                $API_AUTH_TOKEN = (!empty($_GET['token']) ? $_GET['token'] : (!empty($_SERVER['HTTP_X_NUCLEUS_TOKEN']) ? $_SERVER['HTTP_X_NUCLEUS_TOKEN'] : (!empty($_POST['token']) ? $_POST['token'] : (!empty($_GET['Token']) ? $_GET['Token'] : ''))));
//                file_put_contents("D:\\temp\\token.txt", print_r($API_AUTH_TOKEN, TRUE));
                Authenticate();
                if($AUTHENTICATION['Authenticated']) {
                    $PERSON = new crmPerson('eventsPerson', $SYSTEM_SETTINGS['Database'], $AUTHENTICATION['Person']['PersonID'], $SYSTEM_SETTINGS["Membership"]);
                    $APIResponse['authenticated'] = TRUE;
                    $APIResponse['authuser'] = $PERSON->GetRecord();
                    $APIResponse['hasmsapplication'] = $PERSON->HasOpenApplication();
                    $APIResponse['administrator'] = HasPermission('adm_portal'); 
                }
//                file_put_contents("D:\\temp\\auth.txt", print_r($AUTHENTICATION, TRUE));
                switch($do) {
                    case 'login':
                        if(CheckRequiredParams(array('user' => FALSE, 'password' => FALSE), $_GET, FALSE)) {
                            $sql =
                            "SELECT tbllogin.LoginID, 
                                    COALESCE(tbllogin.Salt, CAST(tblperson.PersonID AS CHAR)) AS `Salt`, 
                                    COALESCE(tbllogin.PWHash, tblperson.PWHash) AS `PWHash`,
                                    COALESCE(tbllogin.FailCount, tblperson.PWFailCount) AS `FailCount`,
                                    COALESCE(tbllogin.LastAttempt, tblperson.LastPWAttempt) AS `LastAttempt`,
                                    tblemail.Email,
                                    tblperson.PersonID, tblperson.Firstname, tblperson.Lastname, tblperson.Middlenames, tblperson.Title,
                                    COALESCE(tbllogin.LastChanged, tblperson.LastPWChanged) AS `LastChanged`,
                                    ABS(TIMESTAMPDIFF(DAY, UTC_TIMESTAMP(), COALESCE(tbllogin.LastChanged, tblperson.LastPWChanged))) AS `PasswordAge`,
                                    GROUP_CONCAT(DISTINCT tblpermission.Mnemonic SEPARATOR ';') AS `Permissions`,
                                    GROUP_CONCAT(DISTINCT tbllogintopermissiongroup.PermissionGroupID SEPARATOR ';') AS `GroupIDs`
                             FROM tblperson
                             LEFT JOIN tbllogin ON tbllogin.PersonID = tblperson.PersonID
                             LEFT JOIN tblemail ON tblemail.PersonID = tblperson.PersonID
                             LEFT JOIN tbllogintopermissiongroup ON tbllogintopermissiongroup.LoginID = tbllogin.LoginID
                             LEFT JOIN tblpermissiongroup ON tblpermissiongroup.PermissionGroupID = tbllogintopermissiongroup.PermissionGroupID
                             LEFT JOIN tblpermissiongrouptopermission ON tblpermissiongrouptopermission.PermissionGroupID = tblpermissiongroup.PermissionGroupID 
                             LEFT JOIN tbllogintopermission ON tbllogintopermission.LoginID = tbllogin.LoginID
                             LEFT JOIN tblpermission ON (tblpermission.PermissionID = tblpermissiongrouptopermission.PermissionID) OR (tbllogintopermission.PermissionID = tblpermission.PermissionID)
                             WHERE (".(IsValidEmailAddress($_GET['user']) ? "tblemail.Email" : "tblperson.MSNumber")." = '".mysqli_real_escape_string($SYSTEM_SETTINGS["Database"], $_GET['user'])."') 
                                            AND
                                   ((tbllogin.Expires IS NULL ) OR (tbllogin.Expires > UTC_TIMESTAMP()))
                                            AND
                                   ((COALESCE(tbllogin.FailCount, tblperson.PWFailCount) < ".intval($SYSTEM_SETTINGS['Security']['MaxFailCount']).") OR (FIND_IN_SET('noautolock', tbllogin.Flags)))
                             GROUP BY tblperson.PersonID";
                            $data = SingleRecord($SYSTEM_SETTINGS["Database"], $sql);
                            if(!empty($data) && ($data['PasswordAge'] <= $SYSTEM_SETTINGS["Security"]['PasswordExpiry']) && (hash('sha512', $data['Salt'].$_GET['password'], FALSE) == $data['PWHash'])) {
                                SetAPIError(-5, 'Unable to complete authentication');
                                if(empty($data['LoginID'])) {
                                    $sql = "UPDATE tblperson SET LastPWAttempt = UTC_TIMESTAMP(), PWFailCount = 0 WHERE PersonID = ".$data['PersonID'];
                                } else {
                                    $sql = "UPDATE tbllogin SET LastAttempt = UTC_TIMESTAMP(), FailCount = 0, SuccessCount = SuccessCount + 1 WHERE PersonID = ".$data['PersonID'];
                                }
                                if (mysqli_query($SYSTEM_SETTINGS["Database"], $sql)) {
                                    do {
                                        $APIResponse['token'] = RandomString(64);
                                        $expires = gmdate('Y-m-d H:i:s', strtotime('+12 HOUR'));
                                        $APIResponse['timeout'] = $expires;
                                        $sql = "INSERT INTO tblauth (Token, Expires, PersonID) VALUES ('{$APIResponse['token']}', '{$expires}', {$data['PersonID']})";
                                        mysqli_query($SYSTEM_SETTINGS["Database"], $sql);
                                    } while (mysqli_affected_rows($SYSTEM_SETTINGS["Database"]) == 0);
                                    $PERSON = new crmPerson('eventsPerson', $SYSTEM_SETTINGS['Database'], $data['PersonID'], $SYSTEM_SETTINGS["Membership"]);
                                    $APIResponse['authenticated'] = TRUE;
                                    $APIResponse['authuser'] = $PERSON->GetRecord();
                                    $APIResponse['hasmsapplication'] = $PERSON->HasOpenApplication();
                                    $APIResponse['administrator'] = (stripos(';'.$data['Permissions'].';', ';adm_portal;') !== FALSE ? TRUE : FALSE);
                                    SetAPISuccess();
                                    if(defined('__DEBUGMODE') && __DEBUGMODE) {
                                        AddToSysLog(array('Caption' => 'Logon completed', 'PersonID' => $data['PersonID'], 'Description' => 'The user has logged on remotely', 'Expiry' => $SYSTEM_SETTINGS["ExpiryPolicies"]['Logon'], 'Data' => array('Request' => $APIRequest, 'Username' => $data['Email'], 'Token' => $APIResponse['token'])));
                                    }
                                }
                            } else {
                                SetAPIError(-3, 'Incorrect username or password');
                            }
                        }
                        break;
                    case 'newuser':
                        if(CheckRequiredParams(array('firstname' => FALSE, 'lastname' => FALSE, 'email' => FALSE), $_GET, FALSE)) {
                            if(IsValidEmailAddress($_GET['email'])) {
                                $sql = "SELECT EmailID FROM tblemail WHERE Email = '".mysqli_real_escape_string($SYSTEM_SETTINGS["Database"], $_GET['email'])."'";
                                $data = SingleRecord($SYSTEM_SETTINGS["Database"], $sql);
                                if(empty($data)) {
                                    $countries = new crmCountries($SYSTEM_SETTINGS['Database']);
                                    $setSQL = new stmtSQL('INSERT', 'tblperson', $SYSTEM_SETTINGS["Database"]);
                                    $setSQL->addField('Firstname', 'string', NameStr($_GET['firstname']));
                                    $setSQL->addField('Lastname', 'string', NameStr($_GET['lastname']));
                                    $setSQL->addField('ISO3166', 'string', $countries->DefCountry);
                                    $setSQL->addField('ISO4217', 'string', $countries->DefCurrency);
                                    $setSQL->addField('NationalityID', 'integer', $countries->DefNationality);
                                    $response = ExecuteSQL($setSQL);
                                    if($response['success']) {
                                        $personid = mysqli_insert_id($SYSTEM_SETTINGS["Database"]);
                                        $setSQL = new stmtSQL('UPDATE', 'tblperson', $SYSTEM_SETTINGS["Database"]);
                                        $setSQL->addWhere('PersonID', 'integer', $personid);
                                        $NewPW = RandomString($SYSTEM_SETTINGS['Security']['MinPasswordLength']);
                                        $PWHash = hash('sha512', strval($personid).$NewPW, FALSE);
                                        $setSQL->addField('PWHash', 'string', $PWHash);
                                        $setSQL->addField('PWFailCount', 'integer', 0);
                                        $setSQL->addFieldStmt('LastPWChanged', 'UTC_TIMESTAMP()');
                                        $response = ExecuteSQL($setSQL);
                                        if($response['success']) {
                                            $setSQL = new stmtSQL('INSERT', 'tblemail', $SYSTEM_SETTINGS["Database"]);
                                            $setSQL->addField('Email', 'string', $_GET['email']);
                                            $setSQL->addField('PersonID', 'integer', $personid);
                                            $response = ExecuteSQL($setSQL);
                                            if($response['success']) {
                                                $PERSON = new crmPerson('eventsPerson', $SYSTEM_SETTINGS['Database'], $personid, $SYSTEM_SETTINGS["Membership"]);
                                                $personal = $PERSON->GetRecord("personal");
                                                $issent = SendEmailTemplate(
                                                    'users_new',
                                                    array($personal, array('Password' => $NewPW)),
                                                    array('hide' => $NewPW)
                                                );
                                                APIHistory(array(
                                                    'type' => 'edit', 'description' => 'Record created'.($issent ? '' : ' (unable to send confirmation)'), 'flags' => ($issent ? 'success' : 'warning'),
                                                    'PersonID' => $PERSON->PersonID, 'author' => $PERSON->PersonID,
                                                ), $response['_affectedrows']);
                                                AddToSysLog(array('Caption' => 'Record created', 'PersonID' => $PERSON->PersonID, 'Description' => 'New record created via portal: '.NameStr($personal['Firstname']).' '.NameStr($personal['Lastname']) , 'Data' => array_merge($_GET, array('PersonID' => $PERSON->PersonID))));
                                                SetAPISuccess(array('confirmationsent' => $issent, 'password' => $NewPW));
                                            } else {
                                                SetAPIError($response['errorcode'], $response['errormessage']);
                                            }
                                        } else {
                                            SetAPIError($response['errorcode'], $response['errormessage']);
                                        }
                                    } else {
                                        SetAPIError($response['errorcode'], $response['errormessage']);
                                    }
                                } else {
                                    SetAPIError(-7, 'There is already a registered account for this email address.');
                                }
                            } else {
                                SetAPIError(-6, 'The email address is invalid.');
                            }
                        }
                        break;
                    case 'logout':
                        $sql = "DELETE FROM tblauth WHERE Token = '".mysqli_real_escape_string($SYSTEM_SETTINGS["Database"], $API_AUTH_TOKEN)."'";
                        if (mysqli_query($SYSTEM_SETTINGS["Database"], $sql)) {
                            SetAPISuccess();
                        } else {
                            SetAPIError(-4, 'Unable to complete logout');
                        }
                        break;
                    case 'resetpw':
                        if(CheckRequiredParams(array('user' => FALSE), $_GET, FALSE)) {
                            $sql =
                            "SELECT tbllogin.LoginID, 
                                    COALESCE(tbllogin.FailCount, tblperson.PWFailCount) AS `FailCount`,
                                    COALESCE(tbllogin.LastAttempt, tblperson.LastPWAttempt) AS `LastAttempt`,
                                    tblemail.Email,
                                    tblperson.PersonID, tblperson.Firstname, tblperson.Lastname, tblperson.Middlenames, tblperson.Title,
                                    COALESCE(tbllogin.LastChanged, tblperson.LastPWChanged) AS `LastChanged`
                             FROM tblperson
                             LEFT JOIN tbllogin ON tbllogin.PersonID = tblperson.PersonID
                             LEFT JOIN tblemail ON tblemail.PersonID = tblperson.PersonID
                             WHERE (".(IsValidEmailAddress($_GET['user']) ? "tblemail.Email" : "tblperson.MSNumber")." = '".mysqli_real_escape_string($SYSTEM_SETTINGS["Database"], $_GET['user'])."')"; 
                            $data = SingleRecord($SYSTEM_SETTINGS["Database"], $sql);
                            if(!empty($data)) {
                                $PERSON = new crmPerson('eventsPerson', $SYSTEM_SETTINGS['Database'], $data['PersonID'], $SYSTEM_SETTINGS["Membership"]);
                                if(!empty($data['LoginID'])) {
                                    //User with a system account
                                    $setSQL = new stmtSQL('UPDATE', 'tbllogin', $SYSTEM_SETTINGS["Database"]);
                                    $setSQL->addWhere('LoginID', 'integer', $data['LoginID']);
                                    $Salt = safe_b64encode(mcrypt_create_iv(48, MCRYPT_DEV_URANDOM));
                                    $setSQL->addField('Salt', 'string', $Salt);
                                    $NewPW = RandomString($SYSTEM_SETTINGS['Security']['MinPasswordLength']);
                                    $PWHash = hash('sha512', $Salt.$NewPW, FALSE);
                                    $setSQL->addField('PWHash', 'string', $PWHash);
                                    $setSQL->addField('FailCount', 'integer', 0);
                                    $setSQL->addFieldStmt('LastChanged', 'UTC_TIMESTAMP()');
                                    $response = ExecuteSQL($setSQL);
                                    if($response['success']) {
                                        $issent = SendEmailTemplate(
                                            'sys_pwreset',
                                            array($PERSON->GetRecord("personal", TRUE), array('Password' => $NewPW)),
                                            array('hide' => $NewPW)
                                        );
                                        APIHistory(array(
                                            'type' => 'security', 'description' => 'Nucleus password reset via portal'.($issent ? '' : ' (unable to send confirmation)'), 'flags' => ($issent ? 'success' : 'warning'),
                                            'PersonID' => $PERSON->PersonID, 'author' => $PERSON->PersonID,
                                        ), $response['_affectedrows']);
                                        SetAPISuccess(array('confirmationsent' => $issent));
                                    } else {
                                        SetAPIError($response['errorcode'], $response['errormessage']);
                                    }
                                } else {
                                    //Non-system user
                                    $setSQL = new stmtSQL('UPDATE', 'tblperson', $SYSTEM_SETTINGS["Database"]);
                                    $setSQL->addWhere('PersonID', 'integer', $PERSON->PersonID);
                                    $NewPW = RandomString($SYSTEM_SETTINGS['Security']['MinPasswordLength']);
                                    $PWHash = hash('sha512', strval($PERSON->PersonID).$NewPW, FALSE);
                                    $setSQL->addField('PWHash', 'string', $PWHash);
                                    $setSQL->addField('PWFailCount', 'integer', 0);
                                    $setSQL->addFieldStmt('LastPWChanged', 'UTC_TIMESTAMP()');
                                    $response = ExecuteSQL($setSQL);
                                    if($response['success']) {
                                        $issent = SendEmailTemplate(
                                            'users_pwreset',
                                            array($PERSON->GetRecord("personal", TRUE), array('Password' => $NewPW)),
                                            array('hide' => $NewPW)
                                        );
                                        APIHistory(array(
                                            'type' => 'security', 'description' => 'Password reset via portal'.($issent ? '' : ' (unable to send confirmation)'), 'flags' => ($issent ? 'success' : 'warning'),
                                            'PersonID' => $PERSON->PersonID, 'author' => $PERSON->PersonID,
                                        ), $response['_affectedrows']);
                                        SetAPISuccess(array('confirmationsent' => $issent, 'password' => $NewPW));
                                    } else {
                                        SetAPIError($response['errorcode'], $response['errormessage']);
                                    }                                    
                                }
                            } else {
                                SetAPIError(-5, 'The email address or membership number was not found.');
                            }
                        }
                        break;
                    case 'echo':
                        SetAPISuccess(array('GET' => $_GET, 'POST' => $_POST));
                        break;
                    case 'nothing':
                        SetAPISuccess();
                        break;
                    case 'loadoptions':
                        if(CheckRequiredParams(array('for' => FALSE), $_GET, FALSE)) {
                            switch($_GET['for']) {
                                case 'ISO3166':
                                    $countries = new crmCountries($SYSTEM_SETTINGS['Database']);
                                    SetAPISuccess($countries->Countries);
                                    break;
                                case 'NationalityID':
                                    $countries = new crmCountries($SYSTEM_SETTINGS['Database']);
                                    SetAPISuccess($countries->Nationalities);
                                    break;
                                case 'SubjectIDs':
                                    $subjects = new crmSubjects($SYSTEM_SETTINGS['Database']);
                                    SetAPISuccess($subjects->Subjects);
                                    break;
                            }
                        }
                        break;
                    case 'msgrades':
                        $grades = new crmMSGrades($SYSTEM_SETTINGS["Database"]);
                        SetAPISuccess($grades->GetGrades());
                        break;
                    case 'eligiblegrades':
                        if(CheckRequiredParams(array('Graduation' => TRUE, 'NOY' => FALSE), $_POST, FALSE)) {
                            $graduationdate = strtotime(ValidDateStr($_POST['Graduation']));
                            $noy = max(1, intval($_POST['NOY']));
                            $discountid = null;
                            if(!empty($_POST['DiscountID'])) {
                                $discountid = intval($_POST['DiscountID']);
                            } elseif(!empty($_POST['DiscountCode'])) {
                                $discountid = SingleValue($SYSTEM_SETTINGS["Database"], "SELECT DiscountID FROM tbldiscount WHERE DiscountCode = '".IdentifierStr($_POST['DiscountCode'])."'");
                            }
                            $grades = new crmMSGrades($SYSTEM_SETTINGS["Database"]);
                            $Result = array();
                            foreach($grades->RawData() AS $grade) {
                                //Check eligibility first, based on graduation date
                                $permitted = FALSE;
                                if(!empty($grade['ApplyOnline'])) {
                                    if(empty($_POST['ReferenceDate'])) {
                                        $referencedate = date('Y-m-d');
                                    } elseif(is_numeric($_POST['ReferenceDate'])) {
                                        $referencedate = date('Y-m-d', $_POST['ReferenceDate']); 
                                    } else {
                                        $referencedate = ValidDateStr($_POST['ReferenceDate']);
                                    }
                                    if(empty($_POST['Graduation']) && (!empty($grade['GraduationFrom']) || !empty($grade['GraduationUntil']))) {

                                    } else {
                                        $permitted = TRUE;
                                        if(!empty($grade['GraduationFrom'])) {
                                            $from = strtotime($referencedate.' '.$grade['GraduationFrom']);
                                            if($graduationdate > $from) {
                                                $permitted = FALSE;
                                            }
                                        }
                                        if($permitted) {
                                            if(!empty($grade['GraduationUntil'])) {
                                                $until = strtotime($referencedate.' '.$grade['GraduationUntil']);
                                                if($graduationdate <= $until) {
                                                    $permitted = FALSE;
                                                }
                                            }
                                            //If multi-year, need to check also if valid for the period
                                            if($noy > 1) {
                                                if(!empty($grade['GraduationFrom'])) {
                                                    $from = strtotime($referencedate.'+'.($noy-1).'YEAR '.$grade['GraduationFrom']);
                                                    if($graduationdate > $from) {
                                                        $permitted = FALSE;
                                                    }
                                                }
                                                if($permitted) {
                                                    if(!empty($grade['GraduationUntil'])) {
                                                        $until = strtotime($referencedate.'+'.($noy-1).'YEAR '.$grade['GraduationUntil']);
                                                        if($graduationdate <= $until) {
                                                            $permitted = FALSE;
                                                        }
                                                    }                                                
                                                }
                                            }
                                        }
                                    }
                                }
                                if($permitted) {
                                    $msfees = new crmMSFees($SYSTEM_SETTINGS['Database'], $SYSTEM_SETTINGS['Membership']['GradeCaption']);
                                    $params = array(
                                        'ISO4217' => (!empty($_POST['ISO4217']) ? $_POST['ISO4217'] : null),
                                        'MSGradeID' => $grade['MSGradeID'],
                                        'ISO3166' => (!empty($_POST['ISO3166']) ? $_POST['ISO3166'] : null),
                                        'NOY' => $noy,
                                        'DiscountID' => $discountid,
                                    );
                                    $fee = $msfees->CalculateFee($params);
                                    if(!$fee->HasError) {
                                        $grade['Price'] = $fee->Price->AsString();
                                    }
                                    $Result[] = $grade;
                                }
                            }
                            SetAPISuccess($Result);
                        }
                        break;
                    case 'personprofilesections':
                        $genders = new crmGenders($SYSTEM_SETTINGS['Customise']['GenderRequired']);
                        $types = new crmAddressTypes;
                        $countries = new crmCountries($SYSTEM_SETTINGS['Database']);
                        $phonetypes = new crmPhoneTypes($SYSTEM_SETTINGS['Database']);
                        $onlinecategories = new crmOnlineCategories($SYSTEM_SETTINGS['Database']);
                        $workroles = new crmWorkRoles($SYSTEM_SETTINGS['Database']);
                        $placesofstudy = new crmPlacesOfStudy($SYSTEM_SETTINGS['Database']);
                        $placesofwork = new crmPlacesOfWork($SYSTEM_SETTINGS['Database']);
                        $sections = array(
                            'personal' => array(
                                'title' => 'Personal Details',
                                'sectiontype' => 'form',
                                'icon' => 'gi-user',
                                'api' => array(
                                    'get' => array(
//                                        'cmd' => 'getpersonal',
//                                        'method' => 'GET',
                                        'method' => 'authuser',
                                        'identifiers' => 'PersonID',
                                    ),
                                    'set' => array(
                                        'cmd' => 'setpersonal',
                                        'method' => 'POST',
                                        'identifiers' => 'PersonID',
                                    ),
                                ),
                                'fields' => array(
                                    TitleField(),
                                    //array('fieldname' => 'Title', 'caption' => 'Title', 'fieldtype' => 'string', 'required' => TRUE, 'size' => 4, 'hint' => 'Mr, Ms, Mrs, Dr, Professor,...'),
                                    array('fieldname' => 'Firstname', 'caption' => 'First name', 'fieldtype' => 'string', 'required' => TRUE, 'size' => 6),
                                    array('fieldname' => 'Middlenames', 'caption' => 'Middle name(s)', 'fieldtype' => 'string', 'size' => 6),
                                    array('fieldname' => 'Lastname', 'caption' => 'Last name', 'fieldtype' => 'string', 'required' => TRUE, 'size' => 6),
                                    array('fieldname' => 'Gender', 'caption' => 'Gender', 'fieldtype' => 'combo', 'options' => $genders->Genders, 'required' => $SYSTEM_SETTINGS["Customise"]["GenderRequired"], 'mustnotmatch' => ($SYSTEM_SETTINGS["Customise"]["GenderRequired"] ? 'unknown' : null), 'size' => 4),
                                    array('fieldname' => 'DOB', 'caption' => 'Date of birth', 'fieldtype' => 'date', 'required' => $SYSTEM_SETTINGS["Customise"]["DOBRequired"], 'showage' => true),
                                    array('fieldname' => 'NationalityID', 'caption' => 'Nationality', 'fieldtype' => 'advcombo', 'allowempty' => TRUE, 'size' => 6, 'loadoptions' => TRUE, 'default' => $countries->DefNationality),
                                    array('fieldname' => 'ISO3166', 'caption' => 'Country of Residence', 'kind' => 'control', 'fieldtype' => 'advcombo', 'required' => TRUE, 'allowempty' => FALSE, 'size' => 6, 'loadoptions' => TRUE, 'default' => $countries->DefCountry),
                                    array('fieldname' => 'Graduation', 'caption' => 'Graduation', 'kind' => 'control', 'fieldtype' => 'date', 'showage' => true, 'hint' => "If you haven't graduated yet, please provide the estimated date"),
                                ),
                            ),
/*                            'contact' => array(
                                'title' => 'Contact Details',
                                'sectiontype' => 'collectiongroup',
                                'icon' => 'gi-vcard',
                                'collections' => array(
                                
                                ),
                                
                            ),*/
                            'address' => array(
                                'title' => 'Postal Address',
                                'sectiontype' => 'collection',
                                'icon' => 'fa-envelope-o',
                                'api' => array(
                                    'get' => array(
                                        'cmd' => 'getaddresses',
                                        'method' => 'GET',
                                        'identifiers' => 'PersonID',
                                    ),
                                    'set' => array(
                                        'cmd' => 'setaddress',
                                        'method' => 'POST',
                                        'identifiers' => array('PersonID', 'AddressID', 'AddressToPersonID'),
                                    ),
                                    'del' => array(
                                        'cmd' => 'deladdress',
                                        'identifiers' => 'AddressID',
                                    ),
                                ),
                                'fields' => array(
                                    array('fieldname' => 'AddressType', 'caption' => 'Type', 'fieldtype' => 'list', 'options' => $types->Types, 'size' => 4),
                                    array('fieldname' => 'Lines', 'caption' => 'Address Lines', 'fieldtype' => 'memo', 'required' => TRUE, 'rows' => 4),
                                    array('fieldname' => 'Postcode', 'caption' => 'Postcode', 'fieldtype' => 'string', 'required' => TRUE),
                                    array('fieldname' => 'Town', 'caption' => 'Town', 'fieldtype' => 'string', 'required' => TRUE),
                                    array('fieldname' => 'County', 'caption' => 'County', 'fieldtype' => 'string'),
                                    array('fieldname' => 'Region', 'caption' => 'Region', 'fieldtype' => 'string'),
                                    array('fieldname' => 'ISO3166', 'caption' => 'Country', 'fieldtype' => 'advcombo', 'required' => TRUE, 'allowempty' => FALSE, 'loadoptions' => TRUE, 'default' => $countries->DefCountry),                                        
                                ),
                            ),
                            'contact' => array(
                                'title' => 'Contact Details',
                                'sectiontype' => 'collectiongroup',
                                'icon' => 'gi-vcard',
                                'collections' => array(
                                    'email' => array(
                                        'title' => 'Email',
                                        'required' => TRUE,
                                        'sectiontype' => 'collection',
                                        'icon' => 'fa-envelope',
                                        'api' => array(
                                            'get' => array(
                                                'cmd' => 'getemail',
                                                'method' => 'GET',
                                                'identifiers' => 'PersonID',
                                            ),
                                            'set' => array(
                                                'cmd' => 'setemail',
                                                'method' => 'POST',
                                                'identifiers' => array('PersonID', 'EmailID'),
                                            ),
                                            'del' => array(
                                                'cmd' => 'delemail',
                                                'identifiers' => 'EmailID',
                                            ),
                                        ),
                                        'fields' => array(
                                            array('fieldname' => 'Email', 'caption' => 'Email Address', 'fieldtype' => 'email', 'required' => 'Enter a valid email address'),
                                        ),
                                    ),
                                    'phone' => array(
                                        'title' => 'Phone',
                                        'sectiontype' => 'collection',
                                        'icon' => 'fa-phone',
                                        'api' => array(
                                            'get' => array(
                                                'cmd' => 'getphone',
                                                'method' => 'GET',
                                                'identifiers' => 'PersonID',
                                            ),
                                            'set' => array(
                                                'cmd' => 'setphone',
                                                'method' => 'POST',
                                                'identifiers' => array('PersonID', 'PersonToPhoneID'),
                                            ),
                                            'del' => array(
                                                'cmd' => 'delphone',
                                                'identifiers' => 'PersonToPhoneID',
                                            ),
                                        ),
                                        'fields' => array(
                                            array('fieldname' => 'PhoneTypeID', 'caption' => 'Type', 'fieldtype' => 'advcombo', 'allowempty' => FALSE, 'options' => $phonetypes->GetTypes(), 'required' => TRUE),
                                            array('fieldname' => 'PhoneNo', 'caption' => 'Phone Number', 'fieldtype' => 'phone', 'required' => TRUE),
                                        ),                                        
                                    ),
                                    'online' => array(
                                        'title' => 'Web and Social',
                                        'sectiontype' => 'collection',
                                        'icon' => 'gi-global',
                                        'api' => array(
                                            'get' => array(
                                                'cmd' => 'getonline',
                                                'method' => 'GET',
                                                'identifiers' => 'PersonID',
                                            ),
                                            'set' => array(
                                                'cmd' => 'setonline',
                                                'method' => 'POST',
                                                'identifiers' => array('PersonID', 'PersonToOnlineID'),
                                            ),
                                            'del' => array(
                                                'cmd' => 'delonline',
                                                'identifiers' => 'PersonToOnlineID',
                                            ),
                                        ),
                                        'fields' => array(
                                            array('fieldname' => 'OnlineID', 'caption' => 'Type', 'fieldtype' => 'advcombo', 'allowempty' => FALSE, 'options' => $onlinecategories->GetTypes(), 'required' => TRUE),
                                            array('fieldname' => 'URL', 'caption' => 'URL', 'fieldtype' => 'url', 'required' => 'Enter a valid URL'),
                                        ),                                        
                                    
                                    ),
                                )
                            ),
                            'profile' => array(
                                'sectiontype' => 'form',
                                'icon' => 'gi-nameplate_alt',
                                'api' => array(
                                    'get' => array(
                                        'cmd' => 'getprofile',
                                        'method' => 'GET',
                                        'identifiers' => 'PersonID',
                                    ),
                                    'set' => array(
                                        'cmd' => 'setprofile',
                                        'method' => 'POST',
                                        'identifiers' => 'PersonID',
                                    ),
                                ),
                                'fieldsets' => array(
                                    'study' => array(
                                        'title' => 'Study',
                                        'fields' => array(
                                            array('fieldname' => 'PlaceOfStudyID', 'caption' => 'Place of Study', 'kind' => 'control', 'options' => $placesofstudy->GetPlacesOfStudy(), 'fieldtype' => 'advcombo', 'allowempty' => TRUE),
                                            array('fieldname' => 'StudyInstitution', 'caption' => 'Institution', 'kind' => 'control', 'fieldtype' => 'string'),
                                            array('fieldname' => 'StudyDepartment', 'caption' => 'Department', 'kind' => 'control', 'fieldtype' => 'string'),
                                        )
                                    ),
                                    'employment' => array(
                                        'title' => 'Employment',
                                        'fields' => array(
                                            array('fieldname' => 'PlaceOfWorkID', 'caption' => 'Place of Employment', 'fieldtype' => 'groupcombo', 'options' => $placesofwork->GetPlacesOfWork(), 'allowempty' => TRUE),
                                            array('fieldname' => 'WorkRoleID', 'caption' => 'Primary Work Role', 'fieldtype' => 'advcombo', 'options' => $workroles->GetRoles(), 'allowempty' => TRUE),
                                            array('fieldname' => 'EmployerName', 'caption' => 'Employer', 'fieldtype' => 'string'),
                                            array('fieldname' => 'JobTitle', 'caption' => 'Job Title', 'fieldtype' => 'string'),
                                        ),
                                    ),
                                    'expertise' => array(
                                        'title' => 'Expertise',
                                        'fields' => array(
                                            array('fieldname' => 'SubjectIDs', 'caption' => 'Subjects', 'fieldtype' => 'multigroup', 'loadoptions' => TRUE),
                                            array('fieldname' => 'Keywords', 'caption' => 'Keywords', 'fieldtype' => 'memo'),
                                        ),
                                    ),
                                )
                            )
                        );
                        $groups = new crmActionGroups($SYSTEM_SETTINGS['Database']);
                        if($groups->Count > 0) {
                            $sections['actiongroups'] = array(
                                'title' => 'Working with us',
                                'sectiontype' => 'formgroup',
                                'permissions' => 'member',
                                'icon' => 'gi-link',
                                'forms' => array(
                                ),
/*                                'api' => array(
                                    'get' => array(
                                        'cmd' => 'getactiongroups',
                                        'method' => 'GET',
                                        'identifiers' => 'PersonID',
                                    ),
                                    'set' => array(
                                        'cmd' => 'setactiongroup',
                                        'method' => 'POST',
                                        'identifiers' => 'PersonID',
                                    ),
                                ),
                                'fieldsets' => array(
                                )*/
                            );
                            foreach($groups->Groups AS $group) {
                                $sections['actiongroups']['forms'][$group->ActionGroupID] = array(
                                    'title' => $group->Name,
                                    'sectiontype' => 'form',
                                    'intro' => $group->Description,
                                    'api' => array(
                                        'get' => array(
                                            'cmd' => 'getactiongroup',
                                            'method' => 'GET',
                                            'identifiers' => array('PersonID', 'FormID'),
                                        ),
                                        'set' => array(
                                            'cmd' => 'setactiongroup',
                                            'method' => 'POST',
                                            'identifiers' => array('PersonID', 'FormID'),
                                        ),
                                    ),
                                    'fields' => array(
                                            array('fieldname' => 'ActionGroupItemID[]', 'caption' => 'Make a selection:', 'kind' => 'control', 'fieldtype' => 'multi', 'options' => $group->GetItems(), 'allowempty' => TRUE),
                                    ),                                        
                                );
/*                                $sections['actiongroups']['fieldsets'][$group->ActionGroupID] = array(
                                    'title' => $group->Name,
                                    'fields' => array(
                                        array('fieldname' => 'ActionGroupIDs[]', 'caption' => 'Make a selection:', 'kind' => 'control', 'fieldtype' => 'multi', 'options' => $group->GetItems(), 'allowempty' => TRUE),
                                    )
                                );*/
                            }
                        };
                        SetAPISuccess($sections);
/*                        $fieldsets[] = array('fields' => array(
                            array('name' => 'PersonID', 'kind' => 'hidden'),
                            array('name' => 'Title', 'caption' => 'Title', 'kind' => 'control', 'type' => 'string', 'required' => TRUE, 'size' => 4, 'hint' => 'Mr, Ms, Mrs, Dr, Professor,...'),
                            array('name' => 'Firstname', 'caption' => 'First name', 'kind' => 'control', 'type' => 'string', 'required' => TRUE, 'size' => 6),
                            array('name' => 'Middlenames', 'caption' => 'Middle name(s)', 'kind' => 'control', 'type' => 'string', 'size' => 6),
                            array('name' => 'Lastname', 'caption' => 'Last name', 'kind' => 'control', 'type' => 'string', 'required' => TRUE, 'size' => 6),
                            array('name' => 'Gender', 'caption' => 'Gender', 'kind' => 'control', 'type' => 'combo', 'options' => $PERSON->Genders->Genders, 'size' => 4),
                            array('name' => 'DOB', 'caption' => 'Date of birth', 'kind' => 'control', 'type' => 'date', 'required' => $SYSTEM_SETTINGS["Customise"]["DOBRequired"], 'showage' => true),
                            array('name' => 'ExtPostnominals', 'caption' => 'Postnominals', 'kind' => 'control', 'type' => 'string', 'size' => 4, 'hint' => 'Any postnominals not automatically generated. Do not include '.$SYSTEM_SETTINGS["General"]['OrgShortName'].' postnominals.'),
                            array('name' => 'NationalityID', 'caption' => 'Nationality', 'kind' => 'control', 'type' => 'advcombo', 'allowempty' => TRUE, 'size' => 6, 'options' => $PERSON->Countries->Nationalities),
                            array('name' => 'ISO3166', 'caption' => 'Country of Residence', 'kind' => 'control', 'type' => 'advcombo', 'required' => TRUE, 'allowempty' => FALSE, 'size' => 6, 'options' => $PERSON->Countries->Countries),
                            array('name' => 'ISO4217', 'caption' => 'Invoicing Currency', 'kind' => 'control', 'type' => 'advcombo', 'required' => TRUE, 'allowempty' => FALSE, 'size' => 4, 'options' => $PERSON->Countries->Currencies),
                            array('name' => 'Graduation', 'caption' => 'Graduation', 'kind' => 'control', 'type' => 'date', 'showage' => true),
                            array('name' => 'PaidEmployment', 'caption' => 'Paid employment since', 'kind' => 'control', 'type' => 'date', 'showage' => TRUE),
                            array('name' => 'Deceased', 'caption' => 'Deceased', 'kind' => 'control', 'type' => 'date', 'readonly' => !empty($personal['Deceased'])),
                        ));
                        $formitem = array(
                            'id' => 'frmPersonPersonal', 'style' => 'standard', 'spinner' => TRUE,
                            'onsubmit' => "submitForm( 'frmPersonPersonal', '/syscall.php?do=savePersonPersonal', { parseJSON: true, defErrorDlg: true, cbSuccess: function() { ReloadTab('tab-personal', 'sidebar_membership', 'tab-membership' ); } } ); return false;",
                            'datasource' => $personal, 'buttons' => DefFormButtons("Save Changes"),
                            'fieldsets' => $fieldsets, 'borders' => TRUE
                        );*/
                        break;
                    case 'onlinepayment':
                        //file_put_contents("D:\\temp\\get.txt", print_r($_GET, TRUE));
                        //file_put_contents("D:\\temp\\post.txt", print_r($_POST, TRUE));
                        if(CheckRequiredParams(array('InvoiceID' => FALSE, 'Gateway' => FALSE), $_GET, FALSE)) {
                            try {
                                if(StartTransaction($SYSTEM_SETTINGS["Database"])) {
                                    $INVOICE = new crmInvoice('eventsFinance', $SYSTEM_SETTINGS["Database"], $_GET['InvoiceID'], InvoiceSettings());
                                    $setSQL = new stmtSQL('INSERT', 'tblmoney', $SYSTEM_SETTINGS["Database"]);
                                    $setSQL->addField('TransactionTypeID', 'integer', $SYSTEM_SETTINGS['Finance']['Gateway']['TransactionType']);
                                    $setSQL->addFieldStmt('Received', 'UTC_TIMESTAMP()');
                                    $setSQL->addField('ReceivedFrom', 'string', $_GET['Gateway']);
                                    $setSQL->addField('ISO4217', 'string', $INVOICE->Invoice['ISO4217']);
                                    switch(strtolower($_GET['Gateway'])) {
                                        case 'worldpay':
                                            if(!empty($_GET['Success'])) {
                                                if($INVOICE->Invoice['ISO4217'] == $_POST['GWData']['authCurrency']) {
                                                    $minrec = $INVOICE->MinReceivable();
                                                    if(($INVOICE->Invoice['NonZeroItemCount'] > 0) && ($minrec['ItemTotal'] <= $_POST['ReceivedAmount'])) {
                                                        $setSQL->addField('ReceivedAmount', 'integer', $_POST['ReceivedAmount']);
                                                        $setSQL->addField('TransactionReference', 'string', $_GET['TransactionReference']);
                                                        $setSQL->addField('AddInfo', 'text', print_r($_POST['AddInfo'], TRUE));
                                                    } else
                                                        throw new crmException("Unable to process payment: received amount (".ScaledIntegerAsString($_POST['ReceivedAmount'], "money", 100, TRUE, $INVOICE->Invoice['Symbol']).") mismatch with minimum receivable (".ScaledIntegerAsString($minrec['ItemTotal'], "money", 100, TRUE, $INVOICE->Invoice['Symbol']).")", 3);
                                                } else 
                                                    throw new crmException("Unable to process payment: payment currency ({$_POST['GWData']['authCurrency']}) does not match invoice currency ({$INVOICE->Invoice['ISO4217']})", 2);
                                            }
                                            break;
                                        default:
                                            throw new crmException('Unable to process payment: unknown gateway', 1);
                                    }
                                    $response = ExecuteSQL($setSQL);
                                    if($response['success']) {
                                        $moneyid = mysqli_insert_id($SYSTEM_SETTINGS["Database"]);
                                        $allocated = $INVOICE->Invoice['Outstanding'];
                                        APIHistory(array('type' => 'transaction', 'flags' => 'system', 'description' => "Monies received: ".ScaledIntegerAsString($allocated, "money", 100, TRUE, $INVOICE->Invoice['Symbol']).', '.$INVOICE->Invoice['InvoiceCaption'], 'PersonID' => $INVOICE->Invoice['PersonID'], 'OrganisationID' => $INVOICE->Invoice['OrganisationID']));
                                        $INVOICE->AllocateMoney($moneyid);
                                        SetAPISuccess(array('InvoiceID' => $INVOICE->InvoiceID, 'MoneyID' => $moneyid));
                                    } else {
                                        throw new crmException('Unable to create monies received record: '.$response['errormessage'], $response['errorcode']);
                                    }
                                    mysqli_commit($SYSTEM_SETTINGS["Database"]);
                                } else {
                                    throw new crmException('Unable to start transaction: '.mysqli_error($SYSTEM_SETTINGS["Database"]), mysqli_errno($SYSTEM_SETTINGS["Database"]));
                                }
                            } catch( Exception $e ) {
                                mysqli_rollback($SYSTEM_SETTINGS["Database"]);
                                SetAPIError($e->getCode(), $e->getMessage());                                
                                AddToSysLog(array('EntryKind' => 'error', 'IsSystem' => TRUE, 'Expiry' => $SYSTEM_SETTINGS['ExpiryPolicies']['LogErrors'], 'Caption' => 'API', 'Description' => $e->getMessage().' ('.$e->getCode().')', 'Data' => array('GET' => $_GET, 'POST' => $_POST)));
                            }
                        }                      
                        break;
                    default:
                        //All these commands require authenticated users
                        if(!empty($APIResponse['authenticated'])) {
                            switch($do) {
                                case 'newddi':
                                    if(CheckRequiredParams(array('InstructionScope' => FALSE, 'AccountHolder' => FALSE, 'SortCode' => FALSE, 'AccountNo' => FALSE, 'BankName' => $SYSTEM_SETTINGS['Finance']['DirectDebit']['BankNameReq']), $_POST, FALSE)) {
                                        try {
                                            if(StartTransaction($SYSTEM_SETTINGS["Database"])) {
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
                                                $setSQL->addField('InstructionType', 'enum', 'auddisonline');
                                                $setSQL->addField('AUDDIS', 'string', '0N');
                                                $setSQL->addField('PersonID', 'integer', $AUTHENTICATION['Person']['PersonID']);
                                                if($SYSTEM_SETTINGS['Finance']['DirectDebit']['ReferenceReq']) {
                                                    $setSQL->addField('DDReference', 'string', $SYSTEM_SETTINGS["General"]['OrgShortName'].time().'-'.$AUTHENTICATION['Person']['PersonID'], null, TRUE);
                                                }
                                                $setSQL->addField('TransactionCount', 'integer', 0);
                                                $setSQL->addFieldStmt('LastUsed', 'NULL');
                                                $setSQL->addField('AccountHolder', 'string', $_POST['AccountHolder']);
                                                $setSQL->addField('BankName', 'string', $_POST['BankName'], null, TRUE);
                                                $setSQL->addField('AccountNo', 'string', $_POST['AccountNo'], null, TRUE, TRUE);
                                                $setSQL->addField('SortCode', 'string', $_POST['SortCode'], null, TRUE, TRUE);
                                                $setSQL->addField('InstructionStatus', 'enum', 'setup');
                                                $response = ExecuteSQL($setSQL);
                                                if($response['success']) {
                                                    $ddiid = mysqli_insert_id($SYSTEM_SETTINGS["Database"]);
                                                    if($SYSTEM_SETTINGS["Finance"]['DirectDebit']['RequireMSNumber']) {
                                                        AssignMSNumber((!empty($PERSON) ? $PERSON : $_POST[$OwnerIDField]));
                                                    }
                                                    if(empty($instrScope) || (stripos($instrScope, 'members') !== FALSE)) {
                                                        if($SYSTEM_SETTINGS['Membership']['PaidOnDDI']) {
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
//                                                                            'Description' => '%TypeName%, '.$renewal['GradeCaption'],
                                                                            'Explain' => json_encode($fee->Explanation),
                                                                        ), $renewal);
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }
                                                    APIHistory(array(
                                                        'type' => 'transaction', 'description' => 'Direct Debit Instruction created',
                                                        'DDIID' => $ddiid, 'PersonID' => $AUTHENTICATION['Person']['PersonID'], 'flags' => 'system'
                                                    ));
                                                    $ddi = new crmDirectDebitInstruction($SYSTEM_SETTINGS['Database'], $ddiid);
                                                    $ddis = $PERSON->Reload('ddi');
                                                    $ddis = $PERSON->DDIs;
                                                    $scopes = new crmDirectDebitScopes;
                                                    SetAPISuccess(array(
                                                        'DDI' => $ddi->DDI,
                                                        'DDIs' => array_filter($ddis, function($t){ return ($t['InstructionStatus'] <> 'cancelled'); }),
                                                        'ValidDDI' => array(
                                                            'members' => $PERSON->HasValidDDI(null, 'members')
                                                        ),
                                                        'Policies' => array(
                                                            'reqbankname' => $SYSTEM_SETTINGS['Finance']['DirectDebit']['BankNameReq']
                                                        ),
                                                        'New' => array(
                                                            'scopes' => $scopes->Scopes,
                                                        )
                                                    ));
                                                } else{
                                                    throw new crmException('Unable to create new instruction: '.$response['errormessage'], $response['errorcode']);
                                                }
                                                mysqli_commit($SYSTEM_SETTINGS["Database"]);
                                            } else {
                                                throw new crmException('Unable to start transaction: '.mysqli_error($SYSTEM_SETTINGS["Database"]), mysqli_errno($SYSTEM_SETTINGS["Database"]));
                                            }
                                        } catch( Exception $e ) {
                                            mysqli_rollback($SYSTEM_SETTINGS["Database"]);
                                            SetAPIError($e->getCode(), $e->getMessage());                                
                                            AddToSysLog(array('EntryKind' => 'error', 'IsSystem' => TRUE, 'Expiry' => $SYSTEM_SETTINGS['ExpiryPolicies']['LogErrors'], 'Caption' => 'API', 'Description' => $e->getMessage().' ('.$e->getCode().')', 'Data' => array('GET' => $_GET, 'POST' => $_POST)));
                                        }
                                    }
                                    break;
                                case 'changepassword':
                                    if($SYSTEM_SETTINGS['Security']['AllowPasswordChange']) {
                                        $record = $PERSON->GetRecord();
                                        if(empty($record['LoginID'])) {
                                            if (!empty($_POST['NewPassword1']) && !empty($_POST['NewPassword2'])) {
                                                if($_POST['NewPassword1'] == $_POST['NewPassword2']) {
                                                    if (PREG_REPLACE("/[^0-9a-zA-Z\pL\pP\s\$".preg_quote(',.;:"\'!?*(){}[]/^#%&_=<>+-', '/')."]/iu", '', $_POST['NewPassword1']) <> $_POST['NewPassword1']) {
                                                        SetAPIError(6, "The new password contains invalid characters.");
                                                    } else {
                                                        $NewPW = $_POST['NewPassword1'];
                                                        if(strlen($NewPW) >= $SYSTEM_SETTINGS['Security']['MinPortalPasswordLength']) {
                                                            $meetsreq = TRUE;
                                                            if(!preg_match("#[0-9]+#", $NewPW)) {
                                                                SetAPIError(7, "The new password must contain at least one number.");
                                                                $meetsreq = FALSE;
                                                            } elseif(!preg_match("#[a-z]+#", $NewPW)) {
                                                                SetAPIError(8, "The new password must contain at least one lowercase character.");
                                                                $meetsreq = FALSE;
                                                            } elseif(!preg_match("#[A-Z]+#", $NewPW)) {
                                                                SetAPIError(9, "The new password must contain at least one uppercase character.");
                                                                $meetsreq = FALSE;
                                                            }
                                                            if($meetsreq || !$SYSTEM_SETTINGS['Security']['EnforcePWComplexity']) {
                                                                $setSQL = new stmtSQL('UPDATE', 'tblperson', $SYSTEM_SETTINGS["Database"]);
                                                                $setSQL->addWhere('PersonID', 'integer', $PERSON->PersonID);
                                                                $PWHash = hash('sha512', strval($PERSON->PersonID).$NewPW, FALSE);
                                                                $setSQL->addField('PWHash', 'string', $PWHash);
                                                                $setSQL->addField('PWFailCount', 'integer', 0);
                                                                $setSQL->addFieldStmt('LastPWChanged', 'UTC_TIMESTAMP()');
                                                                $response = ExecuteSQL($setSQL);
                                                                if($response['success']) {
                                                                    $issent = SendEmailTemplate(
                                                                        'users_pwchanged',
                                                                        array($PERSON->GetRecord("personal", TRUE))
                                                                    );
                                                                    APIHistory(array(
                                                                        'type' => 'security', 'description' => 'Password changed via portal'.($issent ? '' : ' (unable to send confirmation)'), 'flags' => ($issent ? 'success' : 'warning'),
                                                                        'PersonID' => $PERSON->PersonID, 'author' => $PERSON->PersonID,
                                                                    ), $response['_affectedrows']);
                                                                    SetAPISuccess(array('confirmationsent' => $issent));
                                                                } else {
                                                                    SetAPIError($response['errorcode'], $response['errormessage']);
                                                                }
                                                            }
                                                        } else {
                                                            SetAPIError(5, "The new password must be at least {$SYSTEM_SETTINGS['Security']['MinPortalPasswordLength']} characters long.");
                                                        }
                                                    }
                                                } else {
                                                    SetAPIError(4, "The new password is different from the confirmation.");
                                                }
                                            } else {
                                                SetAPIError(3, "No new password has been specified.");
                                            }
                                        } else {
                                            SetAPIError(2, "For security reasons, Nucleus users cannot change their password.");
                                        }
                                    } else {
                                        SetAPIError(1, "Password changes are not allowed."); 
                                    }
                                    break;
                                case 'getpersonal':
                                    SetAPISuccess($PERSON->GetRecord());
                                    break;
                                case 'setpersonal':
                                    APISaveData(
                                        'tblperson',
                                        array(
                                            'fieldname' => 'PersonID',
                                            'fieldtype' => 'integer',
                                            'value' => $AUTHENTICATION['Person']['PersonID'] ,
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
                                            'NationalityID' => array(
                                                'fieldtype' => 'integer',
                                                'emptyasnull' => TRUE,
                                            ),
                                            'ISO3166' => 'string',
                                            'Graduation' => array(
                                                'fieldtype' => 'date',
                                                'emptyasnull' => TRUE,
                                            ), 
/*                                            'ExtPostnominals' => 'string',
                                            'ISO4217' => 'string',
                                            'NationalityID' => array(
                                                'fieldtype' => 'integer',
                                                'emptyasnull' => TRUE,
                                            ),
                                            'PaidEmployment' => array(
                                                'fieldtype' => 'date',
                                                'emptyasnull' => TRUE,
                                            ),*/
                                        ),
                                        'Personal details updated'
                                    );
                                    break;
                                case 'setaddress':
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
                                        SetAPISuccess($_POST);
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
                                        if(isset($_POST['ApplicationID']) && isset($_POST['_sectionname'])) {
                                            ExecuteSQL("UPDATE tblapplication SET ConfComponents = CONCAT_WS(',', ConfComponents, '".IdentifierStr($_POST['_sectionname'])."') WHERE ApplicationID = ".intval($_POST['ApplicationID']));
                                        }
                                        APIHistory(array(
                                            'type' => 'edit', 'description' => 'Postal address '.(empty($_POST['AddressID']) ? 'added' : 'updated'),
                                            'PersonID' => (!empty($_POST['PersonID']) ? intval($_POST['PersonID']) : null),
                                            'OrganisationID' => (!empty($_POST['OrganisationID']) ? intval($_POST['OrganisationID']) : null),
                                        ), $response['_affectedrows']);
                                    } else {
                                        SetAPIError($response['errorcode'], $response['errormessage']);
                                    }
                                    break;
                                case 'deladdress':
                                    APIDeleteRecord('tbladdress', array('AddressID'), OwnerIDField(), "Postal address deleted");
                                    break;
                                case 'markcomplete':
                                    if(isset($_POST['ApplicationID'])) {
                                        if(isset($_POST['_sectionname'])) {
                                            $response = ExecuteSQL("UPDATE tblapplication SET ConfComponents = CONCAT_WS(',', ConfComponents, '".IdentifierStr($_POST['_sectionname'])."') WHERE ApplicationID = ".intval($_POST['ApplicationID']));
                                        } elseif(isset($_POST['_componentname'])) {
                                            $str = IdentifierStr($_POST['_componentname']);
                                            $response = ExecuteSQL("UPDATE tblapplication SET OtherComponents = TRIM(LEADING ',' FROM CONCAT_WS(',', OtherComponents, '{$str}')) WHERE (ApplicationID = ".intval($_POST['ApplicationID']).") AND (CONCAT(',', OtherComponents, ',') NOT LIKE '%,{$str},%')");
                                        } else {
                                            $response = null;
                                        }
                                        if(is_null($response)) {
                                            SetAPIError(1, 'The request is invalid');
                                        } elseif($response['success']) {
                                            SetAPISuccess($_POST);
                                        } else {
                                            SetAPIError($response['errorcode'], $response['errormessage']);
                                        }
                                    } else {
                                        SetAPIError(1, 'The request is invalid');
                                    }
                                    break;
                                case 'setexpertise':
                                    $result = APISaveData(
                                        'tblperson',
                                        array(
                                            'fieldname' => 'PersonID',
                                            'fieldtype' => 'integer',
                                            'value' => $AUTHENTICATION['Person']['PersonID'],
                                        ),
                                        array(
                                            'Keywords' => array(
                                                'fieldtype' => 'memo',
                                                'emptyasnull' => TRUE,
                                            ),
                                        ),
                                        'Keywords updated'
                                    );
                                    if($result['success']) {
                                        $sql = "DELETE FROM tblpersontosubject WHERE PersonID = ".intval($AUTHENTICATION['Person']['PersonID']);
                                        mysqli_query($SYSTEM_SETTINGS["Database"], $sql); 
                                        if(!empty($_POST['SubjectIDs'])) {
                                            $sql = "INSERT INTO tblpersontosubject (PersonID, SubjectID) VALUES";
                                            $count = 0;
                                            foreach($_POST['SubjectIDs'] AS $subjectid) {
                                                $sql .= ($count > 0 ? "," : "")." ({$AUTHENTICATION['Person']['PersonID']}, ".intval($subjectid).")";
                                                $count++;
                                            }
                                            $result = ExecuteSQL($sql);
                                            if($result['success']) {
                                                APIHistory(array(
                                                    'type' => 'edit',
                                                    'description' => 'Subjects updated',
                                                    'PersonID' => $AUTHENTICATION['Person']['PersonID']
                                                ), $result['_affectedrows']);
                                            } else {
                                                SetAPIError($result['errorcode'], $result['errormessage']);        
                                            }
                                        }
                                    }
                                    break;
                                case 'getprofile':
                                    SetAPISuccess($PERSON->GetRecord('profile'));
                                    break;
                                case 'setstudy':
                                    $result = APISaveData(
                                        'tblperson',
                                        array(
                                            'fieldname' => 'PersonID',
                                            'fieldtype' => 'integer',
                                            'value' => $AUTHENTICATION['Person']['PersonID'],
                                        ),
                                        array(
                                            'PlaceOfStudyID' => array(
                                                'fieldtype' => 'integer',
                                                'emptyasnull' => TRUE,
                                            ),
                                            'StudyInstitution' => 'string',
                                            'StudyDepartment' => 'string',
                                        ),
                                        'Study details updated'
                                    );
                                    if($result['success']) {
                                        SetAPISuccess($PERSON->GetRecord('profile', TRUE));
                                    } else {
                                        SetAPIError($result['errorcode'], $result['errormessage']);
                                    }
                                    break;
                                case 'setprofile':
                                    $result = APISaveData(
                                        'tblperson',
                                        array(
                                            'fieldname' => 'PersonID',
                                            'fieldtype' => 'integer',
                                            'value' => $AUTHENTICATION['Person']['PersonID'],
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
                                        ),
                                        'Profile details updated'
                                    );
                                    if($result['success']) {
                                        $sql = "DELETE FROM tblpersontosubject WHERE PersonID = ".intval($AUTHENTICATION['Person']['PersonID']);
                                        mysqli_query($SYSTEM_SETTINGS["Database"], $sql); 
                                        if(!empty($_POST['SubjectIDs'])) {
                                            $sql = "INSERT INTO tblpersontosubject (PersonID, SubjectID) VALUES";
                                            $count = 0;
                                            foreach($_POST['SubjectIDs'] AS $subjectid) {
                                                $sql .= ($count > 0 ? "," : "")." ({$AUTHENTICATION['Person']['PersonID']}, ".intval($subjectid).")";
                                                $count++;
                                            }
                                            $result = ExecuteSQL($sql);
                                            if($result['success']) {
                                                APIHistory(array(
                                                    'type' => 'edit',
                                                    'description' => 'Subjects updated',
                                                    'PersonID' => $AUTHENTICATION['Person']['PersonID']
                                                ), $result['_affectedrows']);
                                            } else {
                                                SetAPIError($result['errorcode'], $result['errormessage']);        
                                            }
                                        }
                                    }
                                    break;
                                case 'getaddresses':
                                    SetAPISuccess($PERSON->GetRecord('address'));
                                    break;
                                case 'getemail':
                                    SetAPISuccess($PERSON->GetRecord('email'));
                                    break;
                                case 'delemail':
                                    APIDeleteRecord('tblemail', array('EmailID'), OwnerIDField(), "Email address deleted: {Email}");
                                    break;
                                case 'setemail':
                                    $sql = "SELECT tblemail.EmailID, tblemail.PersonID FROM tblemail WHERE tblemail.Email = '".mysqli_real_escape_string($SYSTEM_SETTINGS['Database'], $_POST['Email'])."'";
                                    $record = SingleRecord($SYSTEM_SETTINGS['Database'], $sql);
                                    if(!empty($record)) {
                                        SetAPIError(-6, "This email address is already in use.");
                                    } else {
                                        $OwnerIDField = OwnerIDField();
                                        APISaveRecord('tblemail', 'EmailID', $OwnerIDField, array('Email' => 'email'), "Email address {action}: {Email}", null, "contact");
                                    }
                                    break;
                                case 'getonline':
                                    SetAPISuccess($PERSON->GetRecord('online'));
                                    break;
                                case 'getdirsettings':
                                    $selector = (!empty($_GET['selector']) ? IdentifierStr($_GET['selector']) : 'members');
                                    $elements = new crmDirectoryElements($selector);
                                    switch($selector) {
                                        case 'members':
                                            SetAPISuccess(array_merge($PERSON->GetRecord('msdirectory'), array('Elements' => $elements->Elements)));
                                            break;
                                        default:
                                            SetAPIError(-4, 'Unknown selector');
                                    }
                                    break;
                                case 'setdirsettings':
                                    $selector = (!empty($_GET['selector']) ? IdentifierStr($_GET['selector']) : 'members');
/*                                    $_POST['WSCategoryID'] = 1;
                                    $_POST['Elements'] = array(
                                        0 => 'name',
                                        1 => 'email',
                                        2 => 'online'
                                    );*/
                                    $wscategoryid = intval($_POST['WSCategoryID']);
                                    $sql = "DELETE FROM tblpersontodirectory WHERE PersonID = {$AUTHENTICATION['Person']['PersonID']}";
                                    $result = ExecuteSQL($sql);
                                    if($result['success']) {
                                        if(!empty($_POST['Elements'])) {
                                            if(!in_array('name', $_POST['Elements'])) {
                                                $_POST['Elements'][] = 'name';
                                            }
                                            $elements = array_unique($_POST['Elements']);
                                            $sql = "INSERT INTO tblpersontodirectory (PersonID, WSCategoryID, ShowElement) VALUES";
                                            $count = 0;
                                            foreach($elements AS $element) {
                                                $sql .= ($count > 0 ? "," : "")." ({$AUTHENTICATION['Person']['PersonID']}, {$wscategoryid}, '".IdentifierStr($element)."')";
                                                $count++;
                                            }
                                            $result = ExecuteSQL($sql);
                                            if(!$result['success']) {
                                                SetAPIError($result['errorcode'], $result['errormessage']);
                                                break;
                                            }
                                        }
                                        APIHistory(array(
                                            'type' => 'edit',
                                            'description' => 'Membership directory settings updated',
                                            'PersonID' => $AUTHENTICATION['Person']['PersonID']
                                        ));
                                        $elements = new crmDirectoryElements($selector);
                                        SetAPISuccess(array_merge($PERSON->GetRecord('msdirectory', TRUE), array('Elements' => $elements->Elements)));
                                    }
                                    break;
                                case 'ddinfo':
                                    $ddis = $PERSON->DDIs;
                                    $scopes = new crmDirectDebitScopes;
                                    SetAPISuccess(array(
                                        'DDIs' => array_filter($ddis, function($t){ return ($t['InstructionStatus'] <> 'cancelled'); }),
                                        'ValidDDI' => array(
                                            'members' => $PERSON->HasValidDDI(null, 'members')
                                        ),
                                        'Policies' => array(
                                            'reqbankname' => $SYSTEM_SETTINGS['Finance']['DirectDebit']['BankNameReq']
                                        ),
                                        'New' => array(
                                            'scopes' => $scopes->Scopes,
                                        )
                                    ));
                                    break;
                                case 'setonline':
                                    $lti = LinkTableInfo('online');
                                    APISaveRecord($lti['table'], $lti['linkfield'], OwnerIDField(), array('OnlineID' => 'integer', 'URL' => 'url'), "{CategoryName} {action}: {URL}");
                                    break;
                                case 'delonline':
                                    $lti = LinkTableInfo('online');
                                    APIDeleteRecord($lti['table'], array($lti['linkfield'], OwnerIDField()), OwnerIDField(), "{CategoryName} deleted: {URL}");                    
                                    break;
                                case 'getphone':
                                    SetAPISuccess($PERSON->GetRecord('phone'));
                                    break;
                                case 'setphone':
                                    $lti = LinkTableInfo('phone');
                                    APISaveRecord($lti['table'], $lti['linkfield'], OwnerIDField(), array('PhoneTypeID' => 'integer', 'PhoneNo' => 'phone', 'Description' => array('fieldtype' => 'string', 'ignorenotset' => TRUE)), "Phone number {action}: {PhoneNo}");
                                    break;
                                case 'delphone':
                                    $lti = LinkTableInfo('phone');
                                    APIDeleteRecord($lti['table'], array($lti['linkfield'], OwnerIDField()), OwnerIDField(), "Phone number deleted: {PhoneNo}");                    
                                    break;
                                case 'getapplication':
                                    $selector = (!empty($_GET['selector']) ? IdentifierStr($_GET['selector']) : 'members');
                                    $wsCategory = GetWSCategory($selector);
                                    $data = array(
                                        'Application' => $PERSON->GetOpenApplication($selector),
                                        'HasOpenApplication' => $PERSON->HasOpenApplication($selector),
                                        'CanApply' => (!$PERSON->HasOpenApplication($selector) && !$PERSON->HasOpenRejoin($selector) && !$PERSON->HasOpenTransfer($selector)),
                                        'Category' => $wsCategory,
                                        'selector' => $selector,
                                    );
                                    $data['ApplicationSections'] = (isset($data['Application']['ApplComponents']) ? GetApplicationSections($data['Application']['ApplComponents'], $selector, $data['Application']) : array());
                                    switch($selector) {
                                        case 'members':
                                            $msgrades = new crmMSGrades($SYSTEM_SETTINGS['Database']);
                                            $data['MSGrades'] = $msgrades->GetOnlineGrades();
                                            break;
                                    }
                                    SetAPISuccess($data);
                                    break;
                                case 'getproposerreferee':
                                    $selector = (!empty($_GET['selector']) ? IdentifierStr($_GET['selector']) : 'members');
                                    $wsCategory = GetWSCategory('$selector');
                                    $application = $PERSON->GetOpenApplication($selector);
                                    if(!empty($application)) {
                                        $component = (isset($application['ApplComponents']['referee']) ? 'referee' : 'proposer');
                                        $name = ucfirst($component);
/*                                        $compdata = array();
                                        foreach(array('MSNumber', 'Email', 'Name', 'Affiliation') AS $suffix) {
                                            $compdata["{$name}".$suffix] = $application["{$name}".$suffix];
                                        }
                                        $data = array(
                                            'Application' => $application,
                                            'HasOpenApplication' => TRUE,
                                            'CanApply' => FALSE,
                                            'Category' => $wsCategory,
                                            'selector' => $selector,
                                            $component => $compdata,
                                        );*/
                                        $data = array();
                                        if($component == 'referee') {
                                            $data['RefereeTypeID'] = $application['RefereeTypeID'];
                                        }
                                        foreach(array('MSNumber', 'Email', 'Name', 'Affiliation') AS $suffix) {
                                            $data["{$name}".$suffix] = $application["{$name}".$suffix];
                                        }
                                        SetAPISuccess($data);
                                    } else {
                                        SetAPIError(1, 'No application found.');
                                    }
                                    break;
                                case 'setproposerreferee':
                                    new crmMSApplicationModel($SYSTEM_SETTINGS['Database'], $SYSTEM_SETTINGS['Membership']);
                                    $application = $PERSON->GetApplicationByID($_POST['ApplicationID']);
                                    if(!empty($application) && $application['IsOpen']) {
                                        $setSQL = new stmtSQL('UPDATE', 'tblapplication', $SYSTEM_SETTINGS["Database"]);
                                        $setSQL->addWhere('ApplicationID', 'integer', $application['ApplicationID']);
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
                                            $name = (isset($application['ApplComponents']['referee']) ? 'Referee' : 'Proposer');
                                            APIHistory(array('type' => 'edit', 'description' => $application['CategoryName']." application: {$name} updated", 'PersonID' => $AUTHENTICATION['Person']['PersonID']), $response['_affectedrows']);
                                            if(isset($_POST['ApplicationID']) && isset($_POST['_sectionname'])) {
                                                ExecuteSQL("UPDATE tblapplication SET ConfComponents = CONCAT_WS(',', ConfComponents, '".IdentifierStr($_POST['_sectionname'])."') WHERE ApplicationID = ".intval($_POST['ApplicationID']));
                                            }
                                            SetAPISuccess($_POST);
                                        } else {
                                            SetAPIError($result['errorcode'], $result['errormessage']);
                                        }
                                    } else {
                                        SetAPIError(1, 'No application found.');
                                    }
                                    break;
                                case 'validateproposerreferee':
                                
                                    break;
                                case 'locatediscount':
                                    if(CheckRequiredParams(array('selector' => FALSE), $_GET, FALSE)) {
                                        $selector = IdentifierStr($_GET['selector']);
                                        $mnemonic = (isset($_GET['mnemonic']) ? : null);
                                        $invitemtypeid = (isset($_GET['invoiceitemtypeid']) ? intval($_GET['invoiceitemtypeid']) : null);
                                        $discount = LocateDiscount(array('PersonID' => $AUTHENTICATION['Person']['PersonID'], 'CategorySelector' => $selector, 'Mnemonic' => $mnemonic, 'InvoiceItemTypeID' => $invitemtypeid), FALSE);
                                        if(!empty($discount)) {
                                            SetAPISuccess(array_merge($discount, array('Found' => TRUE)));
                                        } else {
                                            SetAPISuccess(array('Found' => FALSE));
                                        }
                                    }
                                    break;
                                case 'startapplication':
                                    $selector = (!empty($_POST['selector']) ? IdentifierStr($_POST['selector']) : 'members');
                                    if(!$PERSON->HasOpenApplication($selector)) {
                                        $msgradeid = intval($_POST['MSGradeID']);
                                        $countrycode = (!empty($_POST['ISO3166']) ? IdentifierStr($_POST['ISO3166']) : 'GB');
                                        $sql = 
                                        "SELECT tblwscategory.WSCategoryID, tblwscategory.CategoryName, tblapplicationstage.ApplicationStageID, tblapplicationstage.StageName,
                                                tblmsgrade.MSGradeID, tblmsgrade.GradeCaption, 
                                                COALESCE(tblcountry.ISO3166, 'GB') AS `ISO3166`, 
                                                COALESCE(tblcountry.ISO4217, 'GBP') AS `ISO4217`
                                         FROM tblapplicationstage
                                         LEFT JOIN tblwscategory ON tblwscategory.CategorySelector = '{$selector}'
                                         LEFT JOIN tblmsgrade ON tblmsgrade.MSGradeID = {$msgradeid}
                                         LEFT JOIN tblcountry ON tblcountry.ISO3166 = '{$countrycode}'
                                         ORDER BY tblapplicationstage.StageOrder
                                         LIMIT 1
                                        ";
                                        $appsettings = SingleRecord($SYSTEM_SETTINGS['Database'], $sql);
                                        $sql = "UPDATE tblperson SET ISO4217 = '{$appsettings['ISO4217']}', ISO3166 = '{$appsettings['ISO3166']}'".(isset($_POST['Graduation']) ? ", Graduation = DATE('".ValidDateStr($_POST['Graduation'])."')" : "")." WHERE PersonID = {$AUTHENTICATION['Person']['PersonID']}";
                                        if (mysqli_query($SYSTEM_SETTINGS["Database"], $sql)) {
                                            APIHistory(array('type' => 'edit', 'description' => "Country set to {$appsettings['ISO3166']}, Currency to {$appsettings['ISO4217']}", 'author' => $AUTHENTICATION['Person']['PersonID'], 'PersonID' => $AUTHENTICATION['Person']['PersonID']), mysqli_affected_rows($SYSTEM_SETTINGS["Database"]));
                                            $discountid = null;
                                            if(!empty($_POST['DiscountID'])) {
                                                $discountid = intval($_POST['DiscountID']);
                                            } elseif(!empty($_POST['DiscountCode'])) {
                                                $sql = 
                                                "SELECT tbldiscount.DiscountID, tbldiscount.DiscountCode, tbldiscount.Description, tbldiscount.CategorySelector, tbldiscount.InvoiceItemTypeID,
                                                        tbldiscount.Discount, tbldiscount.RefCount, tbldiscount.ValidFrom, tbldiscount.ValidUntil,
                                                        tblinvoiceitemtype.Mnemonic, tblinvoiceitemtype.TypeName,
                                                        IF(((tbldiscount.ValidFrom IS NULL) OR (tbldiscount.ValidFrom <= UTC_TIMESTAMP()))
                                                                AND
                                                           ((tbldiscount.ValidUntil IS NULL) OR (tbldiscount.ValidUntil >= UTC_TIMESTAMP())),
                                                        1, 0) AS `IsValidForDate`,
                                                        IF(((tbldiscount.CategorySelector IS NULL) OR (tbldiscount.CategorySelector = 'members'))
                                                                AND
                                                           ((tblinvoiceitemtype.Mnemonic = 'ms_new') OR (tblinvoiceitemtype.InvoiceItemTypeID IS NULL)),
                                                        1, 0) AS `IsValidForTransaction`
                                                 FROM tbldiscount
                                                 LEFT JOIN tblinvoiceitemtype ON tblinvoiceitemtype.InvoiceItemTypeID = tbldiscount.InvoiceItemTypeID
                                                 WHERE tbldiscount.DiscountCode = '".IdentifierStr($_POST['DiscountCode'])."'";
                                                //file_put_contents("D:\\temp\\sql.txt", $sql);
                                                if($query = mysqli_query($SYSTEM_SETTINGS["Database"], $sql)) {
                                                    if(mysqli_num_rows($query) > 0) {
                                                        while($row = mysqli_fetch_assoc($query)) {
                                                            if($row['IsValidForDate'] && $row['IsValidForTransaction']) {
                                                                $discountid = $row['DiscountID'];
                                                                $sql =
                                                                "INSERT INTO tbldiscounttoperson (DiscountID, PersonID, RefCount)
                                                                 SELECT tbldiscount.DiscountID, {$AUTHENTICATION['Person']['PersonID']}, tbldiscount.RefCount
                                                                 FROM tbldiscount
                                                                 WHERE tbldiscount.DiscountID = {$discountid}
                                                                ";
                                                                mysqli_query($SYSTEM_SETTINGS["Database"], $sql);
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                            //file_put_contents("D:\\temp\\discountid.txt", $discountid);
                                            $sql = 
                                            "INSERT INTO tblapplication (WSCategoryID, ApplicationStageID, MSGradeID, `Created`, LastModified, DiscountID, NOY, WhereDidYouHear)
                                             VALUES ({$appsettings['WSCategoryID']}, {$appsettings['ApplicationStageID']}, {$appsettings['MSGradeID']}, UTC_TIMESTAMP(), UTC_TIMESTAMP(), ".(!empty($discountid) ? intval($discountid) : 'NULL').", ".(max(1, intval($_POST['NOY']))).", ".(!empty($_POST['WhereDidYouHear']) ? "'".PunctuatedTextStr($_POST['WhereDidYouHear'])."'" : 'NULL').")
                                            ";
                                            if (mysqli_query($SYSTEM_SETTINGS["Database"], $sql)) {
                                                $applicationid = mysqli_insert_id( $SYSTEM_SETTINGS["Database"]);
                                                AdjustDiscountRefCount(array('PersonID' => $AUTHENTICATION['Person']['PersonID'], 'DiscountID' => $discountid));
                                                $sql = "INSERT INTO tblapplicationtoperson (ApplicationID, PersonID) VALUES ({$applicationid}, {$AUTHENTICATION['Person']['PersonID']})";
                                                if (mysqli_query($SYSTEM_SETTINGS["Database"], $sql)) {
                                                    $data = array(
                                                        'Application' => $PERSON->GetOpenApplication($selector, TRUE),
                                                        'HasOpenApplication' => $PERSON->HasOpenApplication($selector),
                                                        'CanApply' => FALSE,
                                                        'Category' => $appsettings,
                                                        'selector' => $selector,
                                                    );
                                                    $data['ApplicationSections'] = GetApplicationSections($data['Application']['ApplComponents'], $selector, $data['Application']);
                                                    switch($selector) {
                                                        case 'members':
                                                            $msgrades = new crmMSGrades($SYSTEM_SETTINGS['Database']);
                                                            $data['MSGrades'] = $msgrades->GetOnlineGrades();
                                                            break;
                                                    }
                                                    SetAPISuccess($data);
                                                    APIHistory(array('type' => 'edit', 'description' => $appsettings['CategoryName'].' application started: '.$appsettings['GradeCaption'].(!empty($_POST['DiscountCode']) && !empty($discountid) ? ", discount code ".IdentifierStr($_POST['DiscountCode']) : ""), 'author' => $AUTHENTICATION['Person']['PersonID'], 'PersonID' => $AUTHENTICATION['Person']['PersonID']));
                                                } else {
                                                    SetAPIError(mysqli_errno($SYSTEM_SETTINGS["Database"]), mysqli_error($SYSTEM_SETTINGS["Database"]));
                                                }
                                            } else {
                                                SetAPIError(mysqli_errno($SYSTEM_SETTINGS["Database"]), mysqli_error($SYSTEM_SETTINGS["Database"]));
                                            }
                                        } else {
                                            SetAPIError(mysqli_errno($SYSTEM_SETTINGS["Database"]), mysqli_error($SYSTEM_SETTINGS["Database"]));
                                        }
                                    } else {
                                        SetAPIError(1, 'There is already an open application.');
                                    }
                                    break;
                                case 'cancelapplication':
                                    $selector = (!empty($_POST['selector']) ? IdentifierStr($_POST['selector']) : 'members');
                                    if($PERSON->HasOpenApplication($selector)) {
                                        $application = $PERSON->GetOpenApplication($selector);
                                        $setSQL = new stmtSQL('UPDATE', 'tblapplication', $SYSTEM_SETTINGS["Database"]);
                                        $setSQL->addWhere('ApplicationID', 'integer', $application['ApplicationID']);
                                        $setSQL->addField('IsOpen', 'integer', 0);
                                        $setSQL->addFieldStmt('Cancelled', 'UTC_TIMESTAMP()');
                                        $setSQL->addFieldStmt('LastModified', 'UTC_TIMESTAMP()');
                                        $response = ExecuteSQL($setSQL);
                                        //file_put_contents("D:\\temp\\response.txt", print_r($response, TRUE));
                                        if($response['success']) {
                                            SetAPISuccess();
                                            if(!empty($application['PersonID'])) {
                                                AddHistory(array('type' => 'delete', 'flags' => 'danger', 'description' => $application['CategoryName'].' application cancelled', 'author' => $AUTHENTICATION['Person']['PersonID'], 'PersonID' => $application['PersonID']), $response['_affectedrows']);
                                            }
                                            if($application['HasTransaction']) {
                                                CancelInvoiceItem($application['InvoiceItemID']);
                                            }
                                        } else {
                                            SetAPIError(mysqli_errno($SYSTEM_SETTINGS["Database"]), mysqli_error($SYSTEM_SETTINGS["Database"]));
                                        }
                                    } else {
                                        SetAPIError(1, 'Application not found.');
                                    }
                                    //file_put_contents("D:\\temp\\apiresponse.txt", print_r($APIResponse, TRUE));
                                    break;
                                case 'submitapplication':
                                    if(!empty($_POST['Agreed'])) {
                                        $application = new crmApplication($SYSTEM_SETTINGS["Database"], $_POST['ApplicationID'], $SYSTEM_SETTINGS["Membership"]);
                                        if($application->Found) {
                                            try {
                                                if(StartTransaction($SYSTEM_SETTINGS["Database"])) {
                                                    SetApplicationStage($application, 'submission');
                                                    if($application->Application['AutoElect']) {
                                                        CascadeToCompletionStage($application);
                                                    }
                                                    mysqli_commit($SYSTEM_SETTINGS["Database"]);
                                                    $application->Reload();
                                                    $data = array(
                                                        'Application' => $application->Application,
                                                        'HasOpenApplication' => ($application->Application['IsOpen'] ? TRUE : FALSE),
                                                        'CanApply' => FALSE,
                                                        'Category' => $application->Application['CategoryName'],
                                                        'selector' => $application->Application['CategorySelector'],
                                                    );
                                                    SetAPISuccess($data);
                                                } else
                                                    throw new crmException('Unable to start transaction: '.mysqli_error($SYSTEM_SETTINGS["Database"]), mysqli_errno($SYSTEM_SETTINGS["Database"]));
                                            } catch( Exception $e ) {
                                                mysqli_rollback($SYSTEM_SETTINGS["Database"]);
                                                SetAPIError($e->getCode(), $e->getMessage());                                
                                                AddToSysLog(array('EntryKind' => 'error', 'IsSystem' => TRUE, 'Expiry' => $SYSTEM_SETTINGS['ExpiryPolicies']['LogErrors'], 'Caption' => 'API', 'Description' => $e->getMessage().' ('.$e->getCode().')', 'Data' => array('GET' => $_GET, 'POST' => $_POST)));
                                            }
                                        } else 
                                            SetAPIError(2, 'Application not found.');
/*                                        if($PERSON->HasOpenApplication($selector)) {
                                            try {
                                                if(StartTransaction($SYSTEM_SETTINGS["Database"])) {
                                                    $application = $PERSON->GetOpenApplication($selector);
/*                                                    $sql = 
                                                    "SELECT tblapplicationstage.ApplicationStageID, tblapplicationstage.StageName
                                                     FROM tblapplicationstage
                                                     WHERE (tblapplicationstage.SubmissionStage = 0) AND (tblapplicationstage.CategorySelector = '{$selector}')
                                                     ORDER BY tblapplicationstage.StageOrder
                                                     LIMIT 1
                                                    ";
                                                    $stage = SingleRecord($SYSTEM_SETTINGS['Database'], $sql);
                                                    SetApplicationStage($application, $stage['ApplicationStageID']);*/
/*                                                    SetApplicationStage($application, 'submission');
                                                    if($application['AutoElect']) {
                                                        CascadeToCompletionStage($application);
                                                    }
                                                    mysqli_commit($SYSTEM_SETTINGS["Database"]);
                                                    $application = $PERSON->GetApplicationByID($application['ApplicationID'], TRUE);
                                                    $data = array(
                                                        'Application' => $application,
                                                        'HasOpenApplication' => ($application['IsOpen'] ? TRUE : FALSE),
                                                        'CanApply' => FALSE,
                                                        'Category' => $application['CategoryName'],
                                                        'selector' => $selector,
                                                    );
                                                    SetAPISuccess($data);
                                                } else
                                                    throw new crmException('Unable to start transaction: '.mysqli_error($SYSTEM_SETTINGS["Database"]), mysqli_errno($SYSTEM_SETTINGS["Database"]));
                                            } catch( Exception $e ) {
                                                mysqli_rollback($SYSTEM_SETTINGS["Database"]);
                                                SetAPIError($e->getCode(), $e->getMessage());                                
                                                AddToSysLog(array('EntryKind' => 'error', 'IsSystem' => TRUE, 'Expiry' => $SYSTEM_SETTINGS['ExpiryPolicies']['LogErrors'], 'Caption' => 'API', 'Description' => $e->getMessage().' ('.$e->getCode().')', 'Data' => array('GET' => $_GET, 'POST' => $_POST)));
                                            }
/*                                            $sql = "UPDATE tblapplication SET ApplicationStageID = {$stage['ApplicationStageID']}, LastModified = UTC_TIMESTAMP() WHERE ApplicationID = {$application['ApplicationID']}";
                                            $response = ExecuteSQL($sql);
                                            if($response['success']) {
                                                if($SYSTEM_SETTINGS['Membership']['EarlyMSNumberAssign']) {
                                                    AssignMSNumber($application['PersonID']);
                                                }
                                                $application = $PERSON->GetApplicationByID($application['ApplicationID'], TRUE);
                                                CreateApplicationTransaction($application);
                                                $data = array(
                                                    'Application' => $PERSON->GetApplicationByID($application['ApplicationID'], TRUE),
                                                    'HasOpenApplication' => TRUE,
                                                    'CanApply' => FALSE,
                                                    'Category' => $application['CategoryName'],
                                                    'selector' => $selector,
                                                );
                                                SetAPISuccess($data);
                                            } else {
                                                SetAPIError(mysqli_errno($SYSTEM_SETTINGS["Database"]), mysqli_error($SYSTEM_SETTINGS["Database"]));
                                            }*/
/*                                        } else {
                                            SetAPIError(2, 'Application not found.');
                                        }*/
                                    } else {
                                        SetAPIError(1, 'You must activate the switch to indicate that you agree with the declaration before you can submit your application.');
                                    }
                                    break;
                                case 'getactiongroup':
                                    $actiongroupid = intval($_GET['FormID']);
                                    $group = new crmActionGroup($SYSTEM_SETTINGS['Database'], $actiongroupid);
                                    SetAPISuccess(array('ActionGroupItemID[]' => $group->GetSelected($AUTHENTICATION['Person']['PersonID'], FALSE)));
                                    break;
                                case 'setactiongroup':
                                    if(CheckRequiredParams(array('FormID' => FALSE, ), $_POST, FALSE)) {
                                        $actiongroupid = intval($_POST['FormID']);
                                        $sql = "SELECT ActionGroupName FROM tblactiongroup WHERE ActionGroupID = {$actiongroupid}";
                                        $actiongroupname = SingleValue($SYSTEM_SETTINGS['Database'], $sql);
                                        $sql = 
                                        "DELETE tblpersontoactiongroupitem 
                                         FROM tblpersontoactiongroupitem
                                         LEFT JOIN tblactiongroupitem ON tblactiongroupitem.ActionGroupItemID = tblpersontoactiongroupitem.ActionGroupItemID
                                         WHERE (tblpersontoactiongroupitem.PersonID = {$AUTHENTICATION['Person']['PersonID']}) AND (tblactiongroupitem.ActionGroupID = {$actiongroupid})";
                                        $response = ExecuteSQL($sql);
                                        if($response['success']) {
/*                                            if(!is_array($_POST['ActionGroupItemID'])) {
                                                $_POST['ActionGroupItemID'] = array($_POST['ActionGroupItemID']);
                                            }*/
                                            $items = array_unique($_POST['ActionGroupItemID']);
                                            $sql = "INSERT INTO tblpersontoactiongroupitem (PersonID, ActionGroupItemID) VALUES";
                                            $count = 0;
                                            foreach($items AS $item) {
                                                $sql .= ($count > 0 ? "," : "")." ({$AUTHENTICATION['Person']['PersonID']}, {$item})";
                                                $count++;
                                            }
                                            $response = ExecuteSQL($sql);
                                            if($response['success']) {
                                                APIHistory(array('type' => 'edit', 'description' => "{$actiongroupname} settings updated", 'PersonID' => $AUTHENTICATION['Person']['PersonID']));
                                                if(isset($_POST['ApplicationID']) && isset($_POST['_sectionname'])) {
                                                    ExecuteSQL("UPDATE tblapplication SET ConfComponents = CONCAT_WS(',', ConfComponents, '".IdentifierStr($_POST['_sectionname'])."') WHERE ApplicationID = ".intval($_POST['ApplicationID']));
                                                }
                                                SetAPISuccess();
                                            } else {
                                                SetAPIError($result['errorcode'], $result['errormessage']);
                                            }
                                        } else {
                                            SetAPIError($result['errorcode'], $result['errormessage']);
                                        }
                                    }
                                    break;
                                case 'listinvoices':
                                    $from = (empty($_GET['startdate']) ? null : gmdate('Y-m-d H:i:s', strtotime($_GET['startdate'])));
                                    $until = (empty($_GET['enddate']) ? null : gmdate('Y-m-d H:i:s', strtotime($_GET['enddate'])));
                                    $sql =
                                    "SELECT tblinvoicetoperson.InvoiceToPersonID, tblinvoice.InvoiceID, tblinvoice.InvoiceType, tblinvoice.InvoiceDate, tblinvoice.InvoiceDue,
                                            tblinvoice.InvoiceNo, tblinvoice.InvoiceFrom, tblinvoice.InvoiceTo, tblinvoice.VATNumber, tblinvoice.ReminderCount, tblinvoice.LastReminder, tblinvoice.CustomerRef,
                                            tblcurrency.ISO4217, tblcurrency.Currency, tblcurrency.`Decimals`, tblcurrency.Symbol,
                                            IF(tblinvoice.InvoiceNo IS NULL, 1, 0) AS `ProForma`,
                                            IF(tblinvoice.InvoiceType = 'creditnote', 'Credit Note', 'Invoice') AS `InvoiceTypeText`,
                                            CONCAT_WS(' ', IF(tblinvoice.InvoiceNo IS NULL, IF(tblinvoice.InvoiceType = 'creditnote', 'Draft', 'Pro Forma'), ''),
                                                           IF(tblinvoice.InvoiceType = 'creditnote', 'Credit Note', IF(tblinvoice.InvoiceNo IS NULL, CONCAT('#', CAST(tblinvoice.InvoiceID AS CHAR)), CONCAT_WS(' ', 'Invoice', tblinvoice.InvoiceNo)))
                                            ) AS `InvoiceCaption`,
                                            COUNT(DISTINCT tblinvoiceitem.InvoiceItemID) AS `ItemCount`,
                                            COUNT(DISTINCT IF(tblinvoiceitem.ItemNet+tblinvoiceitem.ItemVAT <> 0, tblinvoiceitem.InvoiceItemID, NULL)) AS `NonZeroItemCount`,
                                            COALESCE(SUM(tblinvoiceitem.ItemNet), 0) AS `Net`,
                                            COALESCE(SUM(tblinvoiceitem.ItemVAT), 0) AS `VAT`,
                                            COALESCE(SUM(tblinvoiceitem.ItemVAT+tblinvoiceitem.ItemNet), 0) AS `Total`,
                                            COALESCE(tblmoney.AllocatedAmount, 0) AS `AllocatedAmount`,
                                            COALESCE(tblmoney.AllocatedCount, 0) AS `AllocatedCount`,
                                            COALESCE(SUM(tblinvoiceitem.ItemVAT+tblinvoiceitem.ItemNet), 0)-COALESCE(tblmoney.AllocatedAmount, 0) AS `Outstanding`,
                                            IF((tblinvoiceitem.ItemVAT+tblinvoiceitem.ItemNet = tblmoney.AllocatedAmount) OR ((tblinvoice.InvoiceType = 'creditnote') AND (tblinvoice.InvoiceNo IS NOT NULL)), 1, 0) AS `Settled`,
                                            IF(tblinvoice.InvoiceNo IS NULL,
                                               'Open',
                                               IF((tblinvoiceitem.ItemVAT+tblinvoiceitem.ItemNet = tblmoney.AllocatedAmount) OR (tblinvoice.InvoiceType = 'creditnote'),
                                                  '<b>Settled</b>', 'Open')
                                            ) AS `StatusText`,
                                            IF(tblinvoice.InvoiceNo IS NULL,
                                               'info',
                                               IF((tblinvoiceitem.ItemVAT+tblinvoiceitem.ItemNet = tblmoney.AllocatedAmount) OR (tblinvoice.InvoiceType = 'creditnote'),
                                                  'success', 
                                                   IF(tblinvoice.InvoiceDue > UTC_TIMESTAMP(), 'info', 'warning'))
                                            ) AS `StatusColour`
                                     FROM tblinvoicetoperson
                                     INNER JOIN tblinvoice ON tblinvoice.InvoiceID = tblinvoicetoperson.InvoiceID        
                                     LEFT JOIN tblcurrency ON tblcurrency.ISO4217 = tblinvoice.ISO4217
                                     LEFT JOIN tblinvoiceitem ON tblinvoiceitem.InvoiceID = tblinvoice.InvoiceID
                                     LEFT JOIN (SELECT tblmoneytoinvoice.MoneyToInvoiceID, tblmoneytoinvoice.InvoiceID,
                                                       SUM(IF(tblmoney.Reversed IS NULL, tblmoneytoinvoice.AllocatedAmount, 0)) AS `AllocatedAmount`,
                                                       COUNT(DISTINCT tblmoneytoinvoice.MoneyToInvoiceID) AS `AllocatedCount`
                                                FROM tblmoneytoinvoice
                                                INNER JOIN tblmoney ON tblmoney.MoneyID = tblmoneytoinvoice.MoneyID
                                                GROUP BY tblmoneytoinvoice.InvoiceID 
                                     ) AS tblmoney ON tblmoney.InvoiceID = tblinvoice.InvoiceID
                                     WHERE (tblinvoicetoperson.PersonID = {$AUTHENTICATION['Person']['PersonID']})
                                          ".(!empty($from) ? "AND (tblinvoice.InvoiceDate IS NULL OR (tblinvoice.InvoiceDate >= '{$from}')) ": "")."
                                          ".(!empty($until) ? "AND (tblinvoice.InvoiceDate IS NULL OR (tblinvoice.InvoiceDate <= '{$from}')) ": "")."
                                     GROUP BY tblinvoice.InvoiceID
                                     ORDER BY tblinvoice.InvoiceDate DESC";
                                    if ($query = mysqli_query($SYSTEM_SETTINGS["Database"], $sql)) {
                                        SetAPISuccess(array(
                                            'startdate' => $from,
                                            'enddate' => $until,
                                            'Invoices' => mysqli_fetch_all($query, MYSQLI_ASSOC)
                                        ));
                                    } else {
                                        SetAPIError(mysqli_errno($SYSTEM_SETTINGS["Database"]), mysqli_error($SYSTEM_SETTINGS["Database"]));
                                    }
                                    break;
                                case 'getinvoice':
                                    $invoice = new crmInvoice('eventsFinance', $SYSTEM_SETTINGS['Database'], $_GET['InvoiceID'], InvoiceSettings());
                                    if($invoice->Invoice['PersonID'] == $AUTHENTICATION['Person']['PersonID']) {
                                        SetAPISuccess(array(
                                            'Invoice' => $invoice->Invoice,
                                            'InvoiceItems' => $invoice->InvoiceItems
                                        ));
                                    } else {
                                        SetAPIError(4, "Document not found");
                                    }
                                    break;
                                case 'invoicepdf':
                                    $invoice = new crmInvoice('eventsFinance', $SYSTEM_SETTINGS['Database'], $_GET['InvoiceID'], InvoiceSettings());
                                    if($invoice->Invoice['PersonID'] == $AUTHENTICATION['Person']['PersonID']) {
                                        SetAPISuccess(array(
                                            'Data' => safe_b64encode($invoice->PDF($SYSTEM_SETTINGS["Templates"]['letterhead'])),
                                            'Filename' => FilenameStr($invoice->Invoice['InvoiceCaption'].'.pdf'),
                                            'MimeType' => 'application/pdf',
                                        ));
                                    }
                                    break;
                                case 'getpublications':
                                    $sql = 
                                    "SELECT tblpublication.PublicationID, tblpublication.Title, tblpublication.PublicationType, tblpublication.PublicationScope, tblpublication.Description, tblpublication.Flags,
	                                        tblpublicationtoperson.PublicationToPersonID, tblpublicationtoperson.PublicationToPersonID AS `SubscriptionID`, tblpublicationtoperson.Suspended,
                                            tblpublicationtoperson.Complimentary, tblpublicationtoperson.CustomerReference, tblpublicationtoperson.Qty, tblpublicationtoperson.StartDate, tblpublicationtoperson.EndDate,
                                            tblpublicationtoperson.LastReminder,
                                            IF(((tblpublication.PublicationScope = 'public') AND FIND_IN_SET('autosubscribe', tblpublication.Flags))
                                                                    OR
                                               ((tblpublication.PublicationScope = 'members') AND NOT FIND_IN_SET('optin', tblpublication.Flags)),
                                            1, 0) AS `AutoManaged`,
	                                        IF(FIND_IN_SET('optin', tblpublication.Flags), 1, 0) AS `Optin`,
                                            IF(FIND_IN_SET('autosubscribe', tblpublication.Flags), 1, 0) AS `AutoSubscribe`,
                                            COUNT(DISTINCT(tblpublicationrule.PublicationRuleID)) AS `RuleCount`,
                                            IF((tblpublicationtoperson.PublicationToPersonID IS NOT NULL) AND (tblpublicationtoperson.Qty = 0), 1, 0) AS `OptedOut`
                                     FROM tblpublication
                                     LEFT JOIN tblperson ON tblperson.PersonID = {$AUTHENTICATION['Person']['PersonID']}
                                     LEFT JOIN tblpublicationtoperson ON (tblpublicationtoperson.PublicationID = tblpublication.PublicationID) AND (tblpublicationtoperson.PersonID = tblperson.PersonID)
                                     LEFT JOIN tblpersonms ON (tblpersonms.PersonID = tblperson.PersonID) AND (tblpersonms.BeginDate <= UTC_TIMESTAMP()) AND ((tblpersonms.EndDate IS NULL) OR (tblpersonms.EndDate >= UTC_TIMESTAMP()))
                                     LEFT JOIN tblmsstatus ON tblmsstatus.MSStatusID = tblpersonms.MSStatusID
                                     LEFT JOIN tblmsgrade ON (tblperson.Deceased IS NULL) AND (tblmsgrade.MSGradeID = tblpersonms.MSGradeID) AND (FIND_IN_SET('ismember', tblmsstatus.MSStatusFlags))
                                     LEFT JOIN tblpublicationrule ON (tblpublicationrule.PublicationID = tblpublication.PublicationID) 
										                                          AND
                                                                     (tblpublicationrule.RuleScope = IF(tblperson.Deceased IS NULL AND FIND_IN_SET('ismember', tblmsstatus.MSStatusFlags), 'indmember', 'indnonmember'))
                                                                                  AND
                                                                     ((tblpublicationrule.RuleFilter = 'none') OR ((tblpublicationrule.RuleFilter = 'grade') AND (tblpublicationrule.FilterValueInt = tblpersonms.MSGradeID)))
                                     GROUP BY tblpublication.PublicationID
                                     HAVING (tblpublicationtoperson.PublicationToPersonID IS NOT NULL) OR (tblpublication.PublicationScope = 'public') OR (RuleCount > 0)
                                     ORDER BY tblpublication.Title";
                                    $data = array();
                                    if($query = mysqli_query($SYSTEM_SETTINGS['Database'], $sql)) {
                                        $data = mysqli_fetch_all($query, MYSQLI_ASSOC);
                                        foreach($data AS $key => $row) {
                                            $cansubscribe = (empty($row['SubscriptionID']) || !empty($row['OptedOut']));
                                            $canunsubscribe = (!empty($row['SubscriptionID']) && empty($row['Suspended']) && empty($row['OptedOut']));
                                            $data[$key]['CanSubscribe'] = $cansubscribe;
                                            $data[$key]['CanUnsubscribe'] = $canunsubscribe;
                                        }
                                        SetAPISuccess($data);
                                    } else {
                                        SetAPIError(mysqli_errno($SYSTEM_SETTINGS["Database"]), mysqli_error($SYSTEM_SETTINGS["Database"]));
                                    }
                                    break;
                                case 'subscribe':
                                    if (CheckRequiredParams(array('PublicationID' => FALSE), $_POST)) {
                                        $sql = 
                                        "SELECT tblpublication.PublicationID, tblpublication.Title, tblpublication.PublicationType, tblpublication.PublicationScope, tblpublication.Flags,
	                                            tblpublicationtoperson.PublicationToPersonID, tblpublicationtoperson.PublicationToPersonID AS `SubscriptionID`, tblpublicationtoperson.Suspended,
                                                IF(((tblpublication.PublicationScope = 'public') AND FIND_IN_SET('autosubscribe', tblpublication.Flags))
                                                                    OR
                                                   ((tblpublication.PublicationScope = 'members') AND NOT FIND_IN_SET('optin', tblpublication.Flags)),
                                                1, 0) AS `AutoManaged`,
	                                            IF(FIND_IN_SET('optin', tblpublication.Flags), 1, 0) AS `Optin`,
                                                IF(FIND_IN_SET('autosubscribe', tblpublication.Flags), 1, 0) AS `AutoSubscribe`,
                                                COUNT(DISTINCT(tblpublicationrule.PublicationRuleID)) AS `RuleCount`,
                                                IF((tblpublicationtoperson.PublicationToPersonID IS NOT NULL) AND (tblpublicationtoperson.Qty = 0), 1, 0) AS `OptedOut`
                                         FROM tblpublication
                                         LEFT JOIN tblperson ON tblperson.PersonID = {$AUTHENTICATION['Person']['PersonID']}
                                         LEFT JOIN tblpublicationtoperson ON (tblpublicationtoperson.PublicationID = tblpublication.PublicationID) AND (tblpublicationtoperson.PersonID = tblperson.PersonID)
                                         LEFT JOIN tblpersonms ON (tblpersonms.PersonID = tblperson.PersonID) AND (tblpersonms.BeginDate <= UTC_TIMESTAMP()) AND ((tblpersonms.EndDate IS NULL) OR (tblpersonms.EndDate >= UTC_TIMESTAMP()))
                                         LEFT JOIN tblmsstatus ON tblmsstatus.MSStatusID = tblpersonms.MSStatusID
                                         LEFT JOIN tblmsgrade ON (tblperson.Deceased IS NULL) AND (tblmsgrade.MSGradeID = tblpersonms.MSGradeID) AND (FIND_IN_SET('ismember', tblmsstatus.MSStatusFlags))
                                         LEFT JOIN tblpublicationrule ON (tblpublicationrule.PublicationID = tblpublication.PublicationID) 
										                                                    AND
                                                                         (tblpublicationrule.RuleScope = IF(tblperson.Deceased IS NULL AND FIND_IN_SET('ismember', tblmsstatus.MSStatusFlags), 'indmember', 'indnonmember'))
                                                                                            AND
                                                                         ((tblpublicationrule.RuleFilter = 'none') OR ((tblpublicationrule.RuleFilter = 'grade') AND (tblpublicationrule.FilterValueInt = tblpersonms.MSGradeID)))
                                         WHERE tblpublication.PublicationID = ".intval($_POST['PublicationID'])."
                                         GROUP BY tblpublication.PublicationID
                                        ";
                                        $pub = SingleRecord($SYSTEM_SETTINGS['Database'], $sql);
                                        if(!empty($pub)) {
                                            //Previously opted out or not yet a subscription
                                            if($pub['OptedOut'] || empty($pub['SubscriptionID'])) {
                                                if(($pub['PublicationScope'] == 'public') || ($pub['RuleCount'] > 0)) {
                                                    if(empty($pub['SubscriptionID'])) {
                                                        $setSQL = new stmtSQL('INSERT', 'tblpublicationtoperson', $SYSTEM_SETTINGS["Database"]);
                                                         $setSQL->addField('PublicationID', 'integer', $pub['PublicationID']);
                                                         $setSQL->addField('PersonID', 'integer', $AUTHENTICATION['Person']['PersonID']);
                                                    } else {
                                                        $setSQL = new stmtSQL('UPDATE', 'tblpublicationtoperson', $SYSTEM_SETTINGS["Database"]);
                                                        $setSQL->addWhere('PublicationToPersonID', 'integer', $pub['PublicationToPersonID']);
                                                    }
                                                    $setSQL->addField('Qty', 'integer', 1);
                                                    $response = ExecuteSQL($setSQL);
                                                    if($response['success']) {
                                                        SetAPISuccess();
                                                        APIHistory(array(
                                                            'type' => 'edit',
                                                            'description' => 'Subscribed: '.$pub['Title'],
                                                            'PersonID' => $AUTHENTICATION['Person']['PersonID'],
                                                            ), $response['_affectedrows']);
                                                    } else {
                                                        SetAPIError($result['errorcode'], $result['errormessage']);
                                                    }
                                                } else {
                                                    SetAPIError(3, "You are not entitled to subscribe to this publication");
                                                }
                                            } else {
                                                SetAPIError(2, "A subscription for this publication already exists.");
                                            }
                                        } else {
                                            SetAPIError(1, "This publication was not found.");
                                        }
                                    }
                                    break;
                                case 'unsubscribe':
                                    if (CheckRequiredParams(array('PublicationID' => FALSE), $_POST)) {
                                        $sql = 
                                        "SELECT tblpublication.PublicationID, tblpublication.Title, tblpublication.PublicationType, tblpublication.PublicationScope, tblpublication.Flags,
	                                            tblpublicationtoperson.PublicationToPersonID, tblpublicationtoperson.PublicationToPersonID AS `SubscriptionID`, tblpublicationtoperson.Suspended,
                                                IF(((tblpublication.PublicationScope = 'public') AND FIND_IN_SET('autosubscribe', tblpublication.Flags))
                                                                    OR
                                                   ((tblpublication.PublicationScope = 'members') AND NOT FIND_IN_SET('optin', tblpublication.Flags)),
                                                1, 0) AS `AutoManaged`,
	                                            IF(FIND_IN_SET('optin', tblpublication.Flags), 1, 0) AS `Optin`,
                                                IF(FIND_IN_SET('autosubscribe', tblpublication.Flags), 1, 0) AS `AutoSubscribe`
                                         FROM tblpublication
                                         LEFT JOIN tblperson ON tblperson.PersonID = {$AUTHENTICATION['Person']['PersonID']}
                                         LEFT JOIN tblpublicationtoperson ON (tblpublicationtoperson.PublicationID = tblpublication.PublicationID) AND (tblpublicationtoperson.PersonID = tblperson.PersonID)
                                         WHERE tblpublication.PublicationID = ".intval($_POST['PublicationID'])."
                                         GROUP BY tblpublication.PublicationID
                                        ";
                                        $pub = SingleRecord($SYSTEM_SETTINGS['Database'], $sql);
                                        if(!empty($pub['SubscriptionID'])) {
                                            if($pub['Optin'] || empty($pub['AutoManaged'])) {
                                                //For opt-in publications or those not auto managed, we just need to remove the entry since it won't automatically be recreated
                                                $sql = "DELETE FROM tblpublicationtoperson WHERE PublicationToPersonID = ".$pub['PublicationToPersonID'];
                                                $response = ExecuteSQL($sql);
                                                if($response['success']) {
                                                    APIHistory(array(
                                                        'type' => 'delete',
                                                        'description' => 'Unsubscribed: '.$pub['Title'],
                                                        'PersonID' => $AUTHENTICATION['Person']['PersonID'],
                                                    ), $response['_affectedrows']);
                                                    SetAPISuccess();
                                                } else {
                                                    SetAPIError($result['errorcode'], $result['errormessage']);
                                                }
                                            } else {
                                                $setSQL = new stmtSQL('UPDATE', 'tblpublicationtoperson', $SYSTEM_SETTINGS["Database"]);
                                                $setSQL->addWhere('PublicationToPersonID', 'integer', $pub['PublicationToPersonID']);
                                                $setSQL->addField('Qty', 'integer', 0);
                                                $response = ExecuteSQL($setSQL);
                                                if($response['success']) {
                                                    APIHistory(array(
                                                        'type' => 'delete',
                                                        'description' => 'Opted out: '.$pub['Title'],
                                                        'PersonID' => $AUTHENTICATION['Person']['PersonID'],
                                                    ), $response['_affectedrows']);
                                                    SetAPISuccess();
                                                } else {
                                                    SetAPIError($result['errorcode'], $result['errormessage']);
                                                }
                                            }
                                        } else {
                                            SetAPIError(1, "No current subscription found for this publication.");
                                        }
                                    }
                                    break;
                                case 'directory':
                                    $selector = (!empty($_GET['selector']) ? IdentifierStr($_GET['selector']) : 'members');
                                    $wsCategory = GetWSCategory('members');  
                                    $RequestedPage = (isset($_GET['page']) ? max(intval($_GET['page']), 1) : 1);
                                    $ItemsPerPage = (isset($_GET['size']) ? max(intval($_GET['size']), 5) : 25);
                                    $StartRecord = ($RequestedPage-1) * $ItemsPerPage;
                                    $MinSearchLength = max(1, (!empty($_GET['minsearchlength']) ? intval($_GET['minsearchlength']) : 0));
                                    $SEARCHTERM = null;
                                    $SEARCHES = array();
                                    $WHERE = array();
                                    if(!empty($_GET['search'])) {
                                        //ENUM('name','membership','email','online','job','study','expertise')
                                        $SEARCHTERM = TextStr($_GET['search']);
                                        if(strlen($SEARCHTERM) >= $MinSearchLength) {
                                            if(IsValidEmailAddress($SEARCHTERM) || ($SEARCHTERM[0] == '@')) {
                                                $SEARCHES[] = "(tblemail.Email LIKE '%{$SEARCHTERM}') AND (tblpersontodirectorysearch.ShowElement = 'email')";
                                            } elseif(is_numeric($SEARCHTERM)) {
//                                                  $SEARCHES[] = "tblperson.PersonID = {$SEARCHTERM}";
//                                                  $SEARCHES[] = "YEAR(tblperson.DOB) = {$SEARCHTERM}";
                                            } else {
                                                $SEARCHES[] = "(tblemail.Email LIKE '%{$SEARCHTERM}%') AND (tblpersontodirectorysearch.ShowElement = 'email')";
                                                $SEARCHES[] = "(tblsubject.`Subject` LIKE '{$SEARCHTERM}%') AND (tblpersontodirectorysearch.ShowElement = 'expertise')";
                                                $SEARCHES[] = "(tblpersontoonline.URL LIKE '%{$SEARCHTERM}%') AND (tblpersontodirectorysearch.ShowElement = 'online')";
                                                $SEARCHES[] = "(tblonline.CategoryName LIKE '{$SEARCHTERM}%') AND (tblpersontodirectorysearch.ShowElement = 'online')";
                                                $SEARCHES[] = "(tblworkrole.WorkRole = '{$SEARCHTERM}') AND (tblpersontodirectorysearch.ShowElement = 'job')";
                                                $SEARCHES[] = "(tblperson.EmployerName LIKE '%{$SEARCHTERM}%') AND (tblpersontodirectorysearch.ShowElement = 'job')";
                                                $SEARCHES[] = "(tblperson.JobTitle LIKE '%{$SEARCHTERM}%') AND (tblpersontodirectorysearch.ShowElement = 'job')";
                                                $SEARCHES[] = "(tblplaceofwork.PlaceOfWorkDesc = '{$SEARCHTERM}') AND (tblpersontodirectorysearch.ShowElement = 'study')";
                                                $SEARCHES[] = "(tblplaceofstudy.PlaceOfStudyDesc = '{$SEARCHTERM}') AND (tblpersontodirectorysearch.ShowElement = 'study')";
                                                $SEARCHES[] = "(tblperson.StudyInstitution LIKE '%{$SEARCHTERM}%') AND (tblpersontodirectorysearch.ShowElement = 'study')";
                                                $SEARCHES[] = "(tblperson.StudyDepartment LIKE '%{$SEARCHTERM}%') AND (tblpersontodirectorysearch.ShowElement = 'study')";
                                                //Names are by default included
                                                $SEARCHES[] = "CONCAT_WS(' ', TRIM(tblperson.Firstname), TRIM(tblperson.Lastname)) LIKE \"{$SEARCHTERM}%\"";
                                                $SEARCHES[] = "CONCAT_WS(' ', TRIM(tblperson.Firstname), TRIM(tblperson.Middlenames), TRIM(tblperson.Lastname)) LIKE \"{$SEARCHTERM}%\"";
                                                $SEARCHES[] = "CONCAT_WS(' ', TRIM(tblperson.Firstname), TRIM(tblperson.Middlenames), TRIM(tblperson.Lastname)) LIKE \"%{$SEARCHTERM}\"";
                                                $SEARCHES[] = "CONCAT_WS(' ', TRIM(tblperson.Firstname), TRIM(tblperson.Middlenames)) LIKE \"{$SEARCHTERM}%\"";
                                                $SEARCHES[] = "CONCAT_WS(' ', LEFT(TRIM(tblperson.Firstname), 1), TRIM(tblperson.Lastname)) LIKE \"{$SEARCHTERM}%\"";
                                                $SEARCHES[] = "TRIM(tblperson.Lastname) LIKE \"{$SEARCHTERM}%\"";
//                                                    $SEARCHES[] = "tblperson.DOB LIKE '{$SEARCHTERM}%'";
                                            }
                                        } else {
                                            $WHERE[] = "tblperson.PersonID IS NULL";
                                        }
                                    } else {
                                        if(!empty($_GET['searchrequired'])) {
                                            $WHERE[] = "tblperson.PersonID IS NULL";
                                        }
                                    }
                                    $WHERE[] = "tblperson.Deceased IS NULL";
                                    $WHERE[] = "FIND_IN_SET('ismember', tblmsstatus.MSStatusFlags)";
                                    $sql = 
                                    "SELECT SQL_CALC_FOUND_ROWS tblperson.PersonID, tblperson.Firstname, tblperson.Middlenames, tblperson.Lastname, tblperson.Title, tblperson.ExtPostnominals,
                                            CONCAT_WS(' ', tblperson.Title, tblperson.Firstname, tblperson.Middlenames, tblperson.Lastname) AS `Fullname`,
                                            ".SortName().",
                                            tblworkrole.WorkRoleID, tblworkrole.WorkRole, tblperson.EmployerName, tblperson.JobTitle, tblperson.Keywords,
                                            tblplaceofstudy.PlaceOfStudyID, tblplaceofstudy.PlaceOfStudyDesc, tblperson.StudyInstitution, tblperson.StudyDepartment,
                                            tblplaceofwork.PlaceOfWorkID, tblplaceofwork.PlaceOfWorkDesc,
                                            tblperson.MSMemberSince,
                                            tblmsstatus.MSStatusID, tblmsstatus.MSStatusCaption, tblmsstatus.MSStatusFlags,
                                            tblmsgrade.MSGradeID, tblmsgrade.GradeCaption,
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
                                            GROUP_CONCAT(DISTINCT tblpersontodirectory.ShowElement SEPARATOR ',') AS `ShowElements`,
                                            GROUP_CONCAT(DISTINCT tblemail.Email SEPARATOR ',') AS `Emails`,
                                            GROUP_CONCAT(DISTINCT tblsubject.`Subject` SEPARATOR ',') AS `Subjects`,
                                            GROUP_CONCAT(DISTINCT CONCAT_WS('\\t', tblpersontoonline.OnlineID, tblpersontoonline.URL, tblonline.CategoryIcon, tblonline.CategoryName) SEPARATOR '\\n') AS `Online`
                                     FROM tblperson
                                     LEFT JOIN tblpersonms ON (tblpersonms.PersonID = tblperson.PersonID) AND (tblpersonms.BeginDate <= UTC_TIMESTAMP()) AND ((tblpersonms.EndDate IS NULL) OR (tblpersonms.EndDate >= UTC_TIMESTAMP()))
                                     LEFT JOIN tblmsstatus ON (tblmsstatus.MSStatusID = tblpersonms.MSStatusID)
                                     LEFT JOIN tblmsgrade ON (tblmsgrade.MSGradeID = tblpersonms.MSGradeID)
                                     LEFT JOIN tblworkrole ON (tblworkrole.WorkRoleID = tblperson.WorkRoleID)
                                     LEFT JOIN tblplaceofstudy ON (tblplaceofstudy.PlaceOfStudyID = tblperson.PlaceOfStudyID)
                                     LEFT JOIN tblplaceofwork ON (tblplaceofwork.PlaceOfWorkID = tblperson.PlaceOfWorkID)
                                     INNER JOIN tblpersontodirectory ON (tblpersontodirectory.WSCategoryID = ".(empty($wsCategory) ? 'NULL' : intval($wsCategory['WSCategoryID'])).") AND (tblpersontodirectory.PersonID = tblperson.PersonID) 
                                     LEFT JOIN tblpersontodirectory AS tblpersontodirectorysearch ON (tblpersontodirectorysearch.WSCategoryID = ".(empty($wsCategory) ? 'NULL' : intval($wsCategory['WSCategoryID'])).") AND (tblpersontodirectorysearch.PersonID = tblperson.PersonID)
                                     LEFT JOIN tblemail ON tblemail.PersonID = tblperson.PersonID
                                     LEFT JOIN tblpersontosubject ON tblpersontosubject.PersonID = tblperson.PersonID
                                     LEFT JOIN tblsubject ON tblsubject.SubjectID = tblpersontosubject.SubjectID
                                     LEFT JOIN tblpersontoonline ON tblpersontoonline.PersonID = tblperson.PersonID
                                     LEFT JOIN tblonline ON tblonline.OnlineID = tblpersontoonline.OnlineID
                                    ".WhereMulti(array(array('conjunction' => 'AND', 'conditions' => $WHERE),
                                                       array('conjunction' => 'OR', 'conditions' => $SEARCHES))
                                                )."
                                     GROUP BY tblperson.PersonID
                                     ORDER BY `_Sortname`
                                     LIMIT {$StartRecord}, {$ItemsPerPage};
                                     SELECT FOUND_ROWS();
                                    ";
                                    //file_put_contents("d:\\temp\\apidir.txt", $sql);
                                    if (mysqli_multi_query($SYSTEM_SETTINGS["Database"], $sql)) {
                                        $query = mysqli_use_result($SYSTEM_SETTINGS["Database"]);
                                        $data = array();
                                        while($row = mysqli_fetch_array($query, MYSQLI_ASSOC)) {
                                            $show = explode(',', $row['ShowElements']);
                                            if($selector == 'members') {
                                                $show[] = 'membership';
                                            }
                                            foreach(array(
                                                'email' => 'Emails',
                                                'online' => 'Online',
                                                'membership' => array('MSMemberSince', 'MSStatusID', 'MSStatusCaption', 'MSStatusFlags', 'MSGradeID', 'GradeCaption', 'MSFmt', 'MSColour', 'MSText', 'IsMember'),
                                                'job' => array('PlaceOfWorkID', 'PlaceOfWorkDesc', 'WorkRoleID', 'WorkRole', 'EmployerName', 'JobTitle'),
                                                'study' => array('PlaceOfStudyID', 'PlaceOfStudyDesc', 'StudyInstitution', 'StudyDepartment'),
                                                'expertise' => array('Subjects', 'Keywords'),
                                            ) AS $element => $toremove) {
                                                if(!in_array($element, $show)) {
                                                    if(is_array($toremove)) {
                                                        foreach($toremove AS $akey) {
                                                            unset($row[$akey]);
                                                        }
                                                    } else {
                                                        unset($row[$toremove]);
                                                    }
                                                }
                                            }
                                            if(!empty($row['Emails'])) {
                                                $row['Emails'] = explode(',', $row['Emails']);
                                            }
                                            if(!empty($row['Subjects'])) {
                                                $row['Subjects'] = explode(',', $row['Subjects']);
                                            }
                                            if(isset($row['Online'])) {
                                                $onlines = explode("\n", $row['Online']);
                                                $row['Online'] = array();
                                                foreach($onlines AS $online) {
                                                    $item = explode("\t", $online);
                                                    if(count($item) > 3) {
                                                        $row['Online'][intval($item[0])] = array(
                                                            'OnlineID' => $item[0],
                                                            'URL' => $item[1],
                                                            'CategoryIcon' => $item[2],
                                                            'CategoryName' => $item[3],
                                                        ); 
                                                    }
                                                }
                                            }
                                            $data[] = $row;
                                        }
                                        mysqli_free_result($query);
                                        if (mysqli_next_result($SYSTEM_SETTINGS["Database"])) {
                                            $query = mysqli_use_result($SYSTEM_SETTINGS["Database"]);
                                            $record = mysqli_fetch_row($query);
                                            mysqli_free_result($query);
                                            $onlinecats = new crmOnlineCategories($SYSTEM_SETTINGS['Database']);                                            
                                            SetAPISuccess(array(
                                                'selector' => $selector,
                                                'search' => $SEARCHTERM,
                                                'Directory' => $data,
                                                'Options' => array(
                                                    'Online' => $onlinecats->GetTypes(TRUE),
                                                ),
                                                'Paging' => array(
                                                    'page' => $RequestedPage,
                                                    'pagecount' => ceil($record[0] / $ItemsPerPage),
                                                    'size' => $ItemsPerPage,
                                                    'recordcount' => $record[0],
                                                )
                                            ));
                                        } else {
                                            SetAPIError(mysqli_errno($SYSTEM_SETTINGS["Database"]), mysqli_error($SYSTEM_SETTINGS["Database"]));
                                        }
                                    } else {
                                        SetAPIError(mysqli_errno($SYSTEM_SETTINGS["Database"]), mysqli_error($SYSTEM_SETTINGS["Database"]));
                                    }
                                    break;
                                default:
                                    SetAPIError(-4, 'Invalid Command');
                            }
                            //The API call may have made changes to the user record; update the user details
                            if(isset($PERSON) && isset($APIResponse['authuser'])) {
                                $APIResponse['authuser'] = $PERSON->GetRecord();
                            }
                        } else {
                            SetAPIError(-3, 'Not authenticated');
                        }
                }
            } else {
                SetAPIError(-2, 'Invalid Access Key');
            }
        }
        mysqli_commit($SYSTEM_SETTINGS["Database"]);
    } else {
        throw new crmException('Unable to start API transaction: '.mysqli_error($SYSTEM_SETTINGS["Database"]), mysqli_errno($SYSTEM_SETTINGS["Database"]));
    }
} catch( Exception $e ) {
    mysqli_rollback($SYSTEM_SETTINGS["Database"]);
    SetAPIError($e->getCode(), $e->getMessage());
    unset($_GET['password']);
    unset($_POST['password']);
    AddToSysLog(array('EntryKind' => 'error', 'IsSystem' => TRUE, 'Expiry' => $SYSTEM_SETTINGS['ExpiryPolicies']['LogErrors'], 'Caption' => 'API', 'Description' => $APIResponse['errormessage'].' ['.$APIResponse['errorcode'].']', 'Data' => array('GET' => $_GET, 'POST' => $_POST)));
} 
echo json_encode($APIResponse);
die();    

function SetAPIError($errorcode, $errormessage) {
    global $APIResponse;
    $APIResponse['data'] = array();
    $APIResponse['errorcode'] = intval($errorcode);
    $APIResponse['errormessage'] = (empty($errormessage) ? 'Unknown error' : PunctuatedTextStr($errormessage));
    $APIResponse['success'] = FALSE;
}

function SetAPISuccess($data = null) {
    global $APIResponse;
    if(is_array($data)) {
        $APIResponse['data'] = $data;
    }
    $APIResponse['errorcode'] = 0;
    $APIResponse['errormessage'] = '';
    $APIResponse['success'] = TRUE;
}

function GetApplicationSections($components, $selector, $application)
{
    global $SYSTEM_SETTINGS;
    $Result = array();
    $genders = new crmGenders($SYSTEM_SETTINGS['Customise']['GenderRequired']);
    $types = new crmAddressTypes;
    $countries = new crmCountries($SYSTEM_SETTINGS['Database']);
    $phonetypes = new crmPhoneTypes($SYSTEM_SETTINGS['Database']);
    $onlinecategories = new crmOnlineCategories($SYSTEM_SETTINGS['Database']);
    $workroles = new crmWorkRoles($SYSTEM_SETTINGS['Database']);
    $placesofstudy = new crmPlacesOfStudy($SYSTEM_SETTINGS['Database']);
    $placesofwork = new crmPlacesOfWork($SYSTEM_SETTINGS['Database']);
    if(is_a($application, 'crmApplication')) {
        $APPL = $application->Application;
    } elseif(is_numeric($application)) {
        $application = new crmApplication($SYSTEM_SETTINGS['Database'], $application, $SYSTEM_SETTINGS['Membership']);
        $APPL = $application->Application;
    } elseif(!is_array($application)) {
        $APPL = array();
    } else {
        $APPL = $application;
    }
    //file_put_contents("D:\\temp\\application.txt", print_r($application, TRUE));
    //file_put_contents("D:\\temp\\APPL.txt", print_r($APPL, TRUE));
    foreach($components AS $component => $order) {
        switch($component) {
            case 'personal':
                $Result[$component] = array(
                    'order' => $order,
                    'title' => 'Personal Details',
                    'sectiontype' => 'form',
                    'icon' => 'gi-user',
                    'api' => array(
                        'get' => array(
                            'cmd' => 'getpersonal',
                            'method' => 'GET',
                            'identifiers' => 'PersonID',
                        ),
                        'set' => array(
                            'cmd' => 'setpersonal',
                            'method' => 'POST',
                            'identifiers' => array('PersonID', 'ApplicationID'),
                        ),
                    ),
                    'fields' => array(
                        TitleField(),
//                        array('fieldname' => 'Title', 'caption' => 'Title', 'fieldtype' => 'string', 'required' => TRUE, 'size' => 4, 'hint' => 'Mr, Ms, Mrs, Dr, Professor,...'),
                        array('fieldname' => 'Firstname', 'caption' => 'First name', 'fieldtype' => 'string', 'required' => TRUE, 'size' => 6),
                        array('fieldname' => 'Middlenames', 'caption' => 'Middle name(s)', 'fieldtype' => 'string', 'size' => 6),
                        array('fieldname' => 'Lastname', 'caption' => 'Last name', 'fieldtype' => 'string', 'required' => TRUE, 'size' => 6),
                        array('fieldname' => 'Gender', 'caption' => 'Gender', 'fieldtype' => 'combo', 'options' => $genders->Genders, 'required' => $SYSTEM_SETTINGS["Customise"]["GenderRequired"], 'mustnotmatch' => ($SYSTEM_SETTINGS["Customise"]["GenderRequired"] ? 'unknown' : null), 'size' => 4),
                        array('fieldname' => 'DOB', 'caption' => 'Date of birth', 'fieldtype' => 'date', 'required' => $SYSTEM_SETTINGS["Customise"]["DOBRequired"], 'showage' => true),
                        array('fieldname' => 'NationalityID', 'caption' => 'Nationality', 'fieldtype' => 'advcombo', 'allowempty' => TRUE, 'size' => 6, 'loadoptions' => TRUE, 'default' => $countries->DefNationality),
                        array('fieldname' => 'ISO3166', 'caption' => 'Country of Residence', 'kind' => 'control', 'fieldtype' => 'advcombo', 'required' => TRUE, 'allowempty' => FALSE, 'size' => 6, 'loadoptions' => TRUE, 'default' => $countries->DefCountry),
                        array('fieldname' => 'Graduation', 'caption' => 'Graduation', 'kind' => 'control', 'fieldtype' => 'date', 'showage' => true, 'hint' => "If you haven't graduated yet, please provide the estimated date"),
                    ),
                );
                break;
            case 'proposer':
            case 'referee':
                $name = ucfirst($component);
                $Result[$component] = array(
                    'order' => $order,
                    'title' => $name,
                    'sectiontype' => 'form',
                    'icon' => 'fa-user',
                    'api' => array(
                        'get' => array(
                            'cmd' => 'getproposerreferee',
                            'method' => 'GET',
                            'identifiers' => 'PersonID',
                        ),
                        'set' => array(
                            'cmd' => 'setproposerreferee',
                            'method' => 'POST',
                            'identifiers' => array('PersonID', 'ApplicationID'),
                        ),
                    ),                    
                    'fields' => array(
                        array('fieldname' => "{$name}MSNumber", 'caption' => "MS Number {$name}", 'kind' => 'control', 'fieldtype' => 'string', 'hint' => "If known, enter the Membership number of your {$name}"),
                        array('fieldname' => "{$name}Email", 'caption' => "{$name} Email", 'kind' => 'control', 'fieldtype' => 'email'),
                        array('fieldname' => "{$name}Name", 'caption' => "{$name} Name", 'kind' => 'control', 'fieldtype' => 'string', 'required' => TRUE),
                        array('fieldname' => "{$name}Affiliation", 'caption' => "{$name} Affiliation", 'kind' => 'control', 'fieldtype' => 'string', 'required' => TRUE),
                    ),
                );
                if($component == 'referee') {
                    $refereetypes = new crmRefereeTypes($SYSTEM_SETTINGS['Database']); 
                    array_unshift($Result[$component]['fields'],
                        array('fieldname' => "RefereeTypeID", 'caption' => "Referee Type", 'kind' => 'control', 'fieldtype' => 'combo', 'options' => $refereetypes->GetRefereeTypes())
                    );    
                }
                break;
            case 'expertise':
                $Result[$component] = array(
                    'order' => $order,
                    'title' => 'Areas of Interest/Expertise',
                    'intro' => "<p>Please indicate your areas of expertise and interest by selecting one or more subjects from the list below. You should select at least one subject. Additionally, you can add one or more keywords to your profile.",
                    'sectiontype' => 'form',
                    'api' => array(
                        'get' => array(
                            'cmd' => 'getprofile',
                            'method' => 'GET',
                            'identifiers' => 'PersonID',
                        ),
                        'set' => array(
                            'cmd' => 'setexpertise',
                            'method' => 'POST',
                            'identifiers' => array('PersonID', 'ApplicationID'),
                        ),
                    ),                    
                    'fields' => array(
                        array('fieldname' => 'SubjectIDs', 'caption' => 'Subjects', 'kind' => 'control', 'fieldtype' => 'multigroup', 'loadoptions' => TRUE, 'allowempty' => FALSE, 'required' => TRUE),
                        array('fieldname' => 'Keywords', 'caption' => 'Keywords', 'kind' => 'control', 'fieldtype' => 'memo'),
                    ),                    
                );
                break;
            case 'study':
                $Result[$component] = array(
                    'order' => $order,
                    'title' => 'Your Study',
                    'sectiontype' => 'form',
                    'api' => array(
                        'get' => array(
                            'cmd' => 'getprofile',
                            'method' => 'GET',
                            'identifiers' => 'PersonID',
                        ),
                        'set' => array(
                            'cmd' => 'setstudy',
                            'method' => 'POST',
                            'identifiers' => array('PersonID', 'ApplicationID'),
                        ),
                    ),                    
                    'fields' => array(
                        array('fieldname' => 'PlaceOfStudyID', 'caption' => 'Place of Study', 'kind' => 'control', 'required' => TRUE, 'options' => $placesofstudy->GetPlacesOfStudy(), 'fieldtype' => 'advcombo', 'allowempty' => FALSE),
                        array('fieldname' => 'StudyInstitution', 'caption' => 'Institution', 'kind' => 'control', 'required' => TRUE, 'fieldtype' => 'string'),
                        array('fieldname' => 'StudyDepartment', 'caption' => 'Department', 'kind' => 'control', 'required' => TRUE, 'fieldtype' => 'string'),
                    ),                    
                );
                break;
            case 'bylaws':
                $Result[$component] = array(
                    'order' => $order,
                    'title' => 'Submit Application',
                    'sectiontype' => 'none',                    
                );
                break;
            case 'address':
                $Result[$component] = array(
                    'order' => $order,
                    'title' => 'Postal Address',
                    'required' => TRUE,
                    'sectiontype' => 'collection',
                    'icon' => 'fa-envelope-o',
                    'api' => array(
                        'get' => array(
                            'cmd' => 'getaddresses',
                            'method' => 'GET',
                            'identifiers' => 'PersonID',
                        ),
                        'set' => array(
                            'cmd' => 'setaddress',
                            'method' => 'POST',
                            'identifiers' => array('PersonID', 'AddressID', 'AddressToPersonID', 'ApplicationID'),
                        ),
                        'del' => array(
                            'cmd' => 'deladdress',
                            'identifiers' => 'AddressID',
                        ),
                    ),
                    'fields' => array(
                        array('fieldname' => 'AddressType', 'caption' => 'Type', 'fieldtype' => 'list', 'options' => $types->Types, 'size' => 4),
                        array('fieldname' => 'Lines', 'caption' => 'Address Lines', 'fieldtype' => 'memo', 'required' => TRUE, 'rows' => 4),
                        array('fieldname' => 'Postcode', 'caption' => 'Postcode', 'fieldtype' => 'string', 'required' => TRUE),
                        array('fieldname' => 'Town', 'caption' => 'Town', 'fieldtype' => 'string', 'required' => TRUE),
                        array('fieldname' => 'County', 'caption' => 'County', 'fieldtype' => 'string'),
                        array('fieldname' => 'Region', 'caption' => 'Region', 'fieldtype' => 'string'),
                        array('fieldname' => 'ISO3166', 'caption' => 'Country', 'fieldtype' => 'advcombo', 'required' => TRUE, 'allowempty' => FALSE, 'loadoptions' => TRUE, 'default' => $countries->DefCountry),                                        
                    ),
                );
                break;
            case 'profile':
                $Result[$component] = array(
                    'order' => $order,
                    'sectiontype' => 'form',
                    'icon' => 'gi-nameplate_alt',
                    'api' => array(
                        'get' => array(
                            'cmd' => 'getprofile',
                            'method' => 'GET',
                            'identifiers' => 'PersonID',
                        ),
                        'set' => array(
                            'cmd' => 'setprofile',
                            'method' => 'POST',
                            'identifiers' => array('PersonID', 'ApplicationID'),
                        ),
                    ),
                    'fieldsets' => array(
                        'study' => array(
                            'title' => 'Study',
                            'fields' => array(
                                array('fieldname' => 'PlaceOfStudyID', 'caption' => 'Place of Study', 'kind' => 'control', 'options' => $placesofstudy->GetPlacesOfStudy(), 'fieldtype' => 'advcombo', 'allowempty' => TRUE),
                                array('fieldname' => 'StudyInstitution', 'caption' => 'Institution', 'kind' => 'control', 'fieldtype' => 'string'),
                                array('fieldname' => 'StudyDepartment', 'caption' => 'Department', 'kind' => 'control', 'fieldtype' => 'string'),
                            ),
                        ),
                        'employment' => array(
                            'title' => 'Employment',
                            'fields' => array(
                                array('fieldname' => 'PlaceOfWorkID', 'caption' => 'Place of Employment', 'fieldtype' => 'groupcombo', 'options' => $placesofwork->GetPlacesOfWork(), 'required' => TRUE, 'allowempty' => FALSE),
                                array('fieldname' => 'WorkRoleID', 'caption' => 'Primary Work Role', 'fieldtype' => 'advcombo', 'required' => TRUE, 'options' => $workroles->GetRoles(), 'allowempty' => FALSE),
                                array('fieldname' => 'EmployerName', 'caption' => 'Employer', 'required' => TRUE, 'fieldtype' => 'string'),
                                array('fieldname' => 'JobTitle', 'caption' => 'Job Title', 'required' => TRUE, 'fieldtype' => 'string'),
                            ),
                        ),
                        'expertise' => array(
                            'title' => 'Expertise',
                            'fields' => array(
                                array('fieldname' => 'SubjectIDs', 'caption' => 'Subjects', 'fieldtype' => 'multigroup', 'loadoptions' => TRUE),
                                array('fieldname' => 'Keywords', 'caption' => 'Keywords', 'fieldtype' => 'memo'),
                            ),
                        ),
                    )
                );
                break;
            case 'contact':
                $Result[$component] = array(
                    'order' => $order,
                    'title' => 'Contact Details',
                    'sectiontype' => 'collectiongroup',
                    'icon' => 'gi-vcard',
                    'collections' => array(
                        'email' => array(
                            'title' => 'Email',
                            'sectiontype' => 'collection',
                            'icon' => 'fa-envelope',
                            'required' => TRUE,
                            'api' => array(
                                'get' => array(
                                    'cmd' => 'getemail',
                                    'method' => 'GET',
                                    'identifiers' => 'PersonID',
                                ),
                                'set' => array(
                                    'cmd' => 'setemail',
                                    'method' => 'POST',
                                    'identifiers' => array('PersonID', 'EmailID', 'ApplicationID'),
                                ),
                                'del' => array(
                                    'cmd' => 'delemail',
                                    'identifiers' => 'EmailID',
                                ),
                            ),
                            'fields' => array(
                                array('fieldname' => 'Email', 'caption' => 'Email Address', 'fieldtype' => 'email', 'required' => 'Enter a valid email address'),
                            ),
                        ),
                        'phone' => array(
                            'title' => 'Phone',
                            'sectiontype' => 'collection',
                            'icon' => 'fa-phone',
                            'required' => TRUE,
                            'api' => array(
                                'get' => array(
                                    'cmd' => 'getphone',
                                    'method' => 'GET',
                                    'identifiers' => 'PersonID',
                                ),
                                'set' => array(
                                    'cmd' => 'setphone',
                                    'method' => 'POST',
                                    'identifiers' => array('PersonID', 'PersonToPhoneID'),
                                ),
                                'del' => array(
                                    'cmd' => 'delphone',
                                    'identifiers' => 'PersonToPhoneID',
                                ),
                            ),
                            'fields' => array(
                                array('fieldname' => 'PhoneTypeID', 'caption' => 'Type', 'fieldtype' => 'advcombo', 'allowempty' => FALSE, 'options' => $phonetypes->GetTypes(), 'required' => TRUE),
                                array('fieldname' => 'PhoneNo', 'caption' => 'Phone Number', 'fieldtype' => 'phone', 'required' => TRUE),
                            ),                                        
                        ),
                        'online' => array(
                            'title' => 'Web and Social',
                            'sectiontype' => 'collection',
                            'icon' => 'gi-global',
                            'api' => array(
                                'get' => array(
                                    'cmd' => 'getonline',
                                    'method' => 'GET',
                                    'identifiers' => 'PersonID',
                                ),
                                'set' => array(
                                    'cmd' => 'setonline',
                                    'method' => 'POST',
                                    'identifiers' => array('PersonID', 'PersonToOnlineID'),
                                ),
                                'del' => array(
                                    'cmd' => 'delonline',
                                    'identifiers' => 'PersonToOnlineID',
                                ),
                            ),
                            'fields' => array(
                                array('fieldname' => 'OnlineID', 'caption' => 'Type', 'fieldtype' => 'advcombo', 'allowempty' => FALSE, 'options' => $onlinecategories->GetTypes(), 'required' => TRUE),
                                array('fieldname' => 'URL', 'caption' => 'URL', 'fieldtype' => 'url', 'required' => 'Enter a valid URL'),
                            ),                                        
                        ),
                    )
                );
                break;
            case 'actiongroups':
                $Result[$component] = array(
                    'order' => $order,
                    'title' => 'Working with us',
                    'sectiontype' => 'formgroup',
                    'icon' => 'gi-link',                    
                    'forms' => array(),
                );
                $groups = new crmActionGroups($SYSTEM_SETTINGS['Database']);
                foreach($groups->Groups AS $group) {
                    $Result[$component]['forms'][$group->ActionGroupID] = array(
                        'title' => $group->Name,
                        'sectiontype' => 'form',
                        'intro' => $group->Description,
                        'api' => array(
                            'get' => array(
                                'cmd' => 'getactiongroup',
                                'method' => 'GET',
                                'identifiers' => array('PersonID', 'FormID'),
                            ),
                            'set' => array(
                                'cmd' => 'setactiongroup',
                                'method' => 'POST',
                                'identifiers' => array('PersonID', 'FormID'),
                            ),
                        ),
                        'fields' => array(
                            array('fieldname' => 'ActionGroupItemID[]', 'caption' => 'Make a selection:', 'kind' => 'control', 'fieldtype' => 'multi', 'options' => $group->GetItems(), 'allowempty' => TRUE),
                        ),
                    );
                }
                break;
        }
        if(!empty($Result[$component])) {
            $Result[$component]['selector'] = $selector;
            $Result[$component]['applicationid'] = (isset($APPL['ApplicationID']) ? $APPL['ApplicationID'] : null);
            $Result[$component]['completed'] = (isset($APPL['ConfComponents']) && is_array($APPL['ConfComponents']) ? array_key_exists($component, $APPL['ConfComponents']) : FALSE);   
        }
    }
    return $Result;
}

function APISaveData($tablename, $where, $mappings, $histtext, $datasource = null, $type = "edit") {
    global $APIResponse, $APIRequest;
    if(is_null($datasource)) {
        $datasource = $_POST;
    }
    $datasource[$where['fieldname']] = $where['value'];
    $setSQL = SimpleUpdateSQL(
        $tablename,
        $where,
        $mappings
    );
//    file_put_contents("d:\\temp\\post.txt", print_r($datasource, TRUE));
//    file_put_contents("d:\\temp\\setsql.txt", print_r($setSQL, TRUE));
    $result = ExecuteSQL($setSQL);
//    file_put_contents("d:\\temp\\result.txt", print_r($result, TRUE));
    if($result['success']) {
        if(!empty($histtext)) {
            ResolveEmbeddedCodes($histtext, array($datasource));
            $array = array(
                'type' => $type,
                'description' => $histtext.(!empty($APIRequest['remoteip']) ? " [via {$APIRequest['remoteip']}]" : ""),
                $where['fieldname'] => $where['value']
            );
            AddHistory($array, $result['_affectedrows']);
        }
        if(isset($datasource['ApplicationID']) && isset($datasource['_sectionname'])) {
            ExecuteSQL("UPDATE tblapplication SET ConfComponents = CONCAT_WS(',', ConfComponents, '".IdentifierStr($datasource['_sectionname'])."') WHERE ApplicationID = ".intval($datasource['ApplicationID']));
        }
        SetAPISuccess($datasource);
    } else {
        SetAPIError($result['errorcode'], $result['errormessage']);
    }
    return $result;
}

function APISaveRecord($tablename, $existingidfield, $newrecordfield, $mappings, $historytext = null, $datasource = null, $sectionname = null) {
    global $APIResponse, $APIRequest;
    if(!empty($historytext)) {
        $historytext .= (!empty($APIRequest['remoteip']) ? " [via {$APIRequest['remoteip']}]" : "");
    }
    $result = SimpleSaveRecord($tablename, $existingidfield, $newrecordfield, $mappings, $historytext, $datasource);
    if($result) {
        SetAPISuccess($datasource);
        if(!is_null($sectionname)) {
            $datasource['_sectionname'] = IdentifierStr($sectionname);
        }
        if(isset($datasource['ApplicationID']) && isset($datasource['_sectionname'])) {
            ExecuteSQL("UPDATE tblapplication SET ConfComponents = CONCAT_WS(',', ConfComponents, '".IdentifierStr($datasource['_sectionname'])."') WHERE ApplicationID = ".intval($datasource['ApplicationID']));
        }
    } else {
        SetAPIError($result['errorcode'], $result['errormessage']);
    }
}

function APIDeleteRecord($tablename, $where, $affectedrecordfield = null, $historytext = null, $datasource = null)
{
    global $SYSTEM_SETTINGS, $APIResponse, $APIRequest;
    $actions = array();
    if(is_null($datasource)) {
        $datasource = $_POST;
    }
    $setSQL = new stmtSQL('DELETE', $tablename, $SYSTEM_SETTINGS["Database"]);
    foreach($where AS $fieldname => $field) {
        if(is_string($field)) {
            $setSQL->addWhere(
                $field,
                (is_numeric($datasource[$field]) ? 'integer' : 'string'),
                $datasource[$field]
            );
        } else {
            $setSQL->addWhere($field['name'], $field['type'], $field['value']);
        }
    }
    $result = ExecuteSQL($setSQL);
    if($result['success']) {
        if(!empty($historytext)) {
            ResolveEmbeddedCodes($historytext, array($datasource));
            AddHistory(array(
                'type' => 'delete',
                'description' => $historytext.(!empty($APIRequest['remoteip']) ? " [via {$APIRequest['remoteip']}]" : ""),
                $affectedrecordfield => $datasource[$affectedrecordfield]
            ), $result['_affectedrows']);
        }
        SetAPISuccess($datasource);
    } else {
        SetAPIError($result['errorcode'], $result['errormessage']);
    }
}

function APIHistory($histitem, $execute = TRUE) {
    global $APIResponse, $APIRequest;
    if(!empty($histitem['description'])) {
        $histitem['description'] .= (!empty($APIRequest['remoteip']) ? " [via {$APIRequest['remoteip']}]" : "");
        AddHistory($histitem, $execute);
    }
}

?>