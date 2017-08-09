<?php

/**
 * @author Guido Gybels
 * @copyright 2015
 * @project BCS CRM Solution
 * @description Loads visual content and dialogs
 */

require_once("initialise.inc");
require_once("bcswidgets.inc");
require_once('person.inc');
require_once('organisation.inc');
Authenticate();

if ($AUTHENTICATION['Authenticated'] && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && (strcasecmp($_SERVER['HTTP_X_REQUESTED_WITH'], 'XMLHttpRequest') == 0) && (strpos($_SERVER['HTTP_REFERER'], $_SERVER['SERVER_NAME']) == 8))
{
    $summaryTable = array(
        'header' => FALSE,
        'striped' => TRUE,
        'condensed' => TRUE,
        'borders' => 'none',
        'responsive' => FALSE,
        'valign' => 'centre',
        'margin' => TRUE,
        'columns' => array(
            array(
                'field' => array('name' => 'caption', 'type' => 'string'),
                'formatted' => TRUE, 'bold' => TRUE, 'class' => 'sideBarCaption'
            ),
            array(
                'field' => array('name' => 'value', 'type' => 'string'),
                'html' => TRUE
            ),
        ),
    );
    
    $do = (!empty($_GET['do']) ? IdentifierStr($_GET['do']) : null);
    switch($do)
    {
        case 'table_people':
            $menuitems = array();
            $menuitems[] = array('colour' => 'success', 'tooltip' => 'Create a new Person Record', 'caption' => 'New Record', 'script' => "OpenDialog( 'newperson', { large: true } )", 'icon' => 'gi-user_add');
//            $menuitems[] = array('colour' => 'info', 'tooltip' => 'Reload the table from the server', 'caption' => 'Refresh', 'script' => 'RefreshDataTable(dt_people);', 'icon' => 'fa-refresh');
            $submenuitems = array();
            $submenuitems[] = array('type' => 'header', 'caption' => 'Change View');
            $submenuitems[] = array('type' => 'item', 'tooltip' => "Include deceased people in the table", 'tooltipalign' => 'left', 'script' => "javascript:void(0)", 'caption' => "Incl. deceased");
            $menuitems[] = array('icon' => 'caret', 'colour' => 'default', 'menuitems' => $submenuitems, 'tooltip' => 'Change view', 'style' => 'alt');
            stdTitleBlock('People', $do, $menuitems, TRUE);
            Div(array('class' => array('block-content')), 8);
            
            Div(array('class' => 'add-bottom-margin-std'), 9);
            $buttongroup = qaButtons('people');
/*            array_unshift($buttongroup,
                array('icon' => 'gi-address_book', 'iconalign' => 'left', 'tooltip' => 'gi-address_book', 'caption' => 'Directory', 'type' => 'button', 'colour' => 'info', 'script' => "LoadContent('wsMain', '/load.php?do=table_msdir', { spinner: true } );")
            );*/
            array_unshift($buttongroup,
                array('icon' => 'fa-money', 'iconalign' => 'left', 'caption' => 'Grants', 'type' => 'button', 'colour' => 'info', 'script' => "LoadContent('wsMain', '/load.php?do=table_grants', { spinner: true } );")
            );            
            array_unshift($buttongroup,
                array('icon' => 'fa-gavel', 'iconalign' => 'left', 'caption' => 'Committees', 'type' => 'button', 'colour' => 'info', 'script' => "LoadContent('wsMain', '/load.php?do=table_committees', { spinner: true } );")
            );            
            array_unshift($buttongroup,
                array('icon' => 'fa-users', 'iconalign' => 'left', 'caption' => 'Groups', 'type' => 'button', 'colour' => 'info', 'script' => "LoadContent('wsMain', '/load.php?do=table_groups', { spinner: true } );")
            );            
            ButtonGroup($buttongroup, FALSE, null, 10);
            Div(null, 9);
            $table = array('id' => 'dt_people', 'ajaxsrc' => '/datatable.php',
                    'params' => array('inc' => 'dtPeople.php', 'fnrow' => 'dtGetRow'),
                    'GET' => array('incldeceased'),
                    'drawcallback' => "dtDefDrawCallBack( oSettings );",
                    'description' => 'People',
                    'responsive' => TRUE, 'hover' => FALSE, 'striped' => FALSE,
                    'columns' => array(
                        array('caption' => 'Name', 'fieldname' => '_Sortname', 'width' => '25%'),
                        array('caption' => 'DOB', 'fieldname' => 'DOB', 'hide' => array('xs')),
                        array('caption' => 'Gender', 'fieldname' => 'Gender', 'hide' => array('xs', 'sm')),
                        array('caption' => 'Membership', 'fieldname' => 'MSText'),
                        array('caption' => 'Info', 'fieldname' => 'Info', 'sortable' => FALSE, 'width' => '20%'),
//                        array('caption' => 'Actions', 'searchable' => FALSE, 'sortable' => FALSE)
                    ),
                    'sortby' => array('column' => '_Sortname', 'direction' => 'asc')
            );
            LoadDatatable($table);
/*            Datatable($table, array(), 9);
            jsInitDatatable($table, TRUE, 9);*/            
            Div(null, 8); //table content block
            Div(null, 7); //table block
            break;
        case 'table_organisations':
            $menuitems = array();
            $menuitems[] = array('colour' => 'success', 'tooltip' => 'Create a new Organisation Record', 'caption' => 'New Record', 'script' => "OpenDialog( 'neworganisation', { large: true } )", 'icon' => 'fa-plus-square');
//            $menuitems[] = array('colour' => 'info', 'tooltip' => 'Reload the table from the server', 'caption' => 'Refresh', 'script' => 'RefreshDataTable(dt_organisations);', 'icon' => 'fa-refresh');
            $submenuitems = array();
            $submenuitems[] = array('type' => 'header', 'caption' => 'Change View');
            $submenuitems[] = array('type' => 'item', 'tooltip' => "Include dissolved organisations in the table", 'tooltipalign' => 'left', 'script' => "javascript:void(0)", 'caption' => "Incl. dissolved");
            $menuitems[] = array('icon' => 'caret', 'colour' => 'default', 'menuitems' => $submenuitems, 'tooltip' => 'Change view', 'style' => 'alt');
            stdTitleBlock('Organisations', $do, $menuitems, TRUE);
            Div(array('class' => 'add-bottom-margin-std'), 9);
            $buttongroup = qaButtons('organisations');
            ButtonGroup($buttongroup, FALSE, null, 10);
            Div(null, 9);
            $table = array('id' => 'dt_organisations', 'ajaxsrc' => '/datatable.php',
                    'params' => array('inc' => 'dtOrganisations.php', 'fnrow' => 'dtGetRow'),
                    'GET' => array('incldissolved'),
                    'drawcallback' => "dtDefDrawCallBack( oSettings );",
                    'description' => 'Organisations',
                    'responsive' => TRUE, 'hover' => FALSE, 'striped' => FALSE,
                    'columns' => array(
                        array('caption' => 'Name', 'fieldname' => 'Name'),
                        array('caption' => 'Ringgold', 'fieldname' => 'Ringgold', 'hide' => array('xs'), 'width' => '10em'),
                        array('caption' => 'Info', 'fieldname' => 'Info', 'sortable' => FALSE, 'width' => '35%'),
//                        array('caption' => 'Actions', 'searchable' => FALSE, 'sortable' => FALSE)
                    ),
                    'sortby' => array('column' => 'Name', 'direction' => 'asc')
            );
            LoadDatatable($table);
            Div(null, 8); //table content block
            Div(null, 7); //table block
            break;
        case 'table_membership':
            $caption = '';
            if(!empty($_GET['MSStatusID'])) {
                $sql = "SELECT MSStatusCaption FROM tblmsstatus WHERE MSStatusID = ".intval($_GET['MSStatusID']);
                $caption = SingleValue($SYSTEM_SETTINGS['Database'], $sql);                
            } elseif(!empty($_GET['MSGradeID'])) {
                $sql = "SELECT GradeCaption FROM tblmsgrade WHERE MSGradeID = ".intval($_GET['MSGradeID']);
                $caption = SingleValue($SYSTEM_SETTINGS['Database'], $sql);                
            } elseif(!empty($_GET['IsMember'])) {
                $caption = "Membership";
            }
            if(isset($_GET['Free']) && !isset($_GET['RenewalCycle'])) {
                $caption .= " - ".(empty($_GET['Free']) ? 'Paid' : 'Free');
            } elseif(isset($_GET['RenewalCycle'])) {
                switch(intval($_GET['RenewalCycle'])) {
                    case 0:
                        $caption .= " - Up-to-date";
                        break;
                    case 1:
                        $caption .= " - Renewal Pending";
                        break;
                    case 2:
                        $caption .= " - Renewal Overdue";
                        break;
                }
            }
            if(!empty($caption)) {
                SimpleHeading('List: '.$caption, 4, 'sub', 9);        
            }
            $table = array('id' => 'dt_membership', 'ajaxsrc' => '/datatable.php',
                    'params' => array('inc' => 'dtMembership.php', 'fnrow' => 'dtGetRow'),
                    'GET' => array('incldeceased', 'MSGradeID', 'MSStatusID', 'RenewalCycle', 'Free', 'IsMember'),
                    'drawcallback' => "dtDefDrawCallBack( oSettings );",
                    'description' => $caption,
                    'responsive' => TRUE, 'hover' => FALSE, 'striped' => FALSE,
                    'columns' => array(
                        array('caption' => 'Name', 'fieldname' => '_Sortname'),
                        array('caption' => 'Number', 'fieldname' => 'MSNumber', 'textalign' => 'center', 'width' => '8em'),
                        array('caption' => 'DOB', 'fieldname' => 'DOB', 'textalign' => 'center', 'hide' => array('xs', 'sm', 'md'), 'width' => '9.5em'),
                        array('caption' => 'Since', 'fieldname' => 'Since', 'textalign' => 'center', 'hide' => array('xs', 'sm'), 'width' => '9.5em'),
                        array('caption' => 'Status', 'fieldname' => 'MSStatusTxt', 'width' => '9em'),
                        array('caption' => $SYSTEM_SETTINGS["Membership"]["GradeCaption"], 'fieldname' => 'MSGradeText', 'width' => '9em'),
//                        array('caption' => 'Actions', 'searchable' => FALSE, 'sortable' => FALSE)
                    ),
                    'sortby' => array('column' => '_Sortname', 'direction' => 'asc')
            );
            LoadDatatable($table);
            break;
        case 'table_publications':
            $menuitems = array();
            $menuitems[] = array('colour' => 'success', 'tooltip' => 'Create a new Publication', 'caption' => 'New Record', 'script' => "OpenDialog( 'editpublication', { large: true } )", 'icon' => 'fa-plus-square');
//            $menuitems[] = array('colour' => 'info', 'tooltip' => 'Reload the table from the server', 'caption' => 'Refresh', 'script' => 'RefreshDataTable(dt_publications);', 'icon' => 'fa-refresh');
/*            $submenuitems = array();
            $submenuitems[] = array('type' => 'header', 'caption' => 'Change View');
            $submenuitems[] = array('type' => 'item', 'tooltip' => "Include dissolved organisations in the table", 'tooltipalign' => 'left', 'script' => "javascript:void(0)", 'caption' => "Incl. dissolved");
            $menuitems[] = array('icon' => 'caret', 'colour' => 'default', 'menuitems' => $submenuitems, 'tooltip' => 'Change view', 'style' => 'alt');*/
            stdTitleBlock('Publications', $do, $menuitems, TRUE);
            Div(array('class' => 'add-bottom-margin-std'), 9);
            $buttongroup = qaButtons('publications');
            ButtonGroup($buttongroup, FALSE, null, 10);
            Div(null, 9);
            $table = array('id' => 'dt_publications', 'ajaxsrc' => '/datatable.php',
                    'params' => array('inc' => 'dtPublications.php', 'fnrow' => 'dtGetRow'),
                    'GET' => array('type', 'scope'),
                    'drawcallback' => "dtDefDrawCallBack( oSettings );",
                    'responsive' => TRUE, 'hover' => FALSE, 'striped' => FALSE,
                    'columns' => array(
                        array('caption' => 'Title', 'fieldname' => 'Title'),
                        array('caption' => 'Type', 'fieldname' => 'Type', 'hide' => array('xs')),
                        array('caption' => 'Settings', 'fieldname' => 'Settings', 'searchable' => FALSE, 'sortable' => FALSE, 'hide' => array('xs', 'sm')),
                        array('caption' => 'Subscribers', 'fieldname' => 'SubsCount', 'textalign' => 'center', 'searchable' => FALSE, 'width' => '9em'),
                        array('caption' => 'Opt-out', 'fieldname' => 'OptoutCount', 'textalign' => 'center', 'searchable' => FALSE, 'width' => '9em', 'hide' => array('xs', 'sm')),
                        array('caption' => 'Qty', 'fieldname' => 'QtyCount', 'textalign' => 'center', 'searchable' => FALSE, 'width' => '9em', 'hide' => array('xs')),
                        array('caption' => 'Actions', 'searchable' => FALSE, 'sortable' => FALSE)
                    ),
                    'sortby' => array('column' => 'Title', 'direction' => 'asc')
            );
            Datatable($table, array(), 9);
            jsInitDatatable($table, TRUE, 9);
            //Container for leading the membership list in            
            Div(array('id' => 'pubBreakdownList'), 9);
            
            Div(null, 9); //table div
            Div(null, 8); //table content block
            Div(null, 7); //table block
            break;
        case 'table_subscribers':
            $publication = new crmPublication($SYSTEM_SETTINGS['Database'], $_GET['PublicationID']);
            $caption = 'Subscribers: '.$publication->Publication['Title'];
            SimpleHeading($caption, 4, 'sub', 9);   
            $table = array('id' => 'dt_subscribers', 'ajaxsrc' => '/datatable.php',
                    'params' => array('inc' => 'dtSubscribers.php', 'fnrow' => 'dtGetRow'),
                    'GET' => array('PublicationID', 'OptedOut'),
                    'drawcallback' => "dtDefDrawCallBack( oSettings );",
                    'description' => $caption,
                    'responsive' => TRUE, 'hover' => FALSE, 'striped' => FALSE,
                    'columns' => array(
                        array('caption' => 'Name', 'fieldname' => '_Sortname', 'width' => '30%'),
                        array('caption' => 'Membership', 'fieldname' => 'MSText', 'hide' => array('xs')),
                        array('caption' => 'Info', 'fieldname' => 'Status', 'sortable' => FALSE),
                        array('caption' => 'Actions', 'searchable' => FALSE, 'sortable' => FALSE)
                    ),
                    'sortby' => array('column' => '_Sortname', 'direction' => 'asc')
            );
            LoadDatatable($table);            
            break;
        case 'table_groupmembers':
            $persongroupid = intval($_GET['PersonGroupID']);
            $sql = "SELECT GroupName FROM tblpersongroup WHERE PersonGroupID = ".$persongroupid;
            $groupname = SingleValue($SYSTEM_SETTINGS['Database'], $sql);
            $caption = 'Group Members: '.$groupname;
            SimpleHeading($caption, 4, 'sub', 9);   
            $table = array('id' => 'dt_groupmembers', 'ajaxsrc' => '/datatable.php',
                    'params' => array('inc' => 'dtGroupMembers.php', 'fnrow' => 'dtGetRow'),
                    'GET' => array('PersonGroupID'),
                    'drawcallback' => "dtDefDrawCallBack( oSettings );",
                    'description' => $caption,
                    'responsive' => TRUE, 'hover' => FALSE, 'striped' => FALSE,
                    'columns' => array(
                        array('caption' => 'Name', 'fieldname' => '_Sortname', 'width' => '30%'),
                        array('caption' => 'Comment', 'fieldname' => 'Comment'),
                        array('caption' => 'Membership', 'fieldname' => 'MSText', 'hide' => array('xs')),
                        array('caption' => 'Actions', 'searchable' => FALSE, 'sortable' => FALSE)
                    ),
                    'sortby' => array('column' => '_Sortname', 'direction' => 'asc')
            );
            LoadDatatable($table, array(), 9,
                defaultTablemenu($table, array('additems' => array('emptygroup' => array(
                    'icon' => 'fa-eraser', 'caption' => 'Empty Group', 'colour' => 'warning',
                    'script' => "confirmExecSyscall('Empty Group', 'Are you sure you want to delete all members of this group? This action cannot be undone.', '/syscall.php?do=emptygroup', { parseJSON: true, defErrorDlg: true, postparams: { PersonGroupID: {$persongroupid} }, cbSuccess: function(){ RefreshDataTable(dt_groups); RefreshDataTable(dt_groupmembers); } });",
                )))),
                TRUE 
            );
            break;            
        case 'table_grantpeople':
            $sql = "SELECT Title FROM tblgrant WHERE GrantID = ".$_GET['GrantID'];
            $grantname = SingleValue($SYSTEM_SETTINGS['Database'], $sql);
            $caption = 'Awards: '.$grantname;
            SimpleHeading($caption, 4, 'sub', 9);   
            $table = array('id' => 'dt_grantpeople', 'ajaxsrc' => '/datatable.php',
                    'params' => array('inc' => 'dtGrantPeople.php', 'fnrow' => 'dtGetRow'),
                    'GET' => array('GrantID'),
                    'drawcallback' => "dtDefDrawCallBack( oSettings );",
                    'description' => $grantname,
                    'responsive' => TRUE, 'hover' => FALSE, 'striped' => FALSE,
                    'columns' => array(
                        array('caption' => 'Name', 'fieldname' => '_Sortname', 'width' => '30%'),
                        array('caption' => 'Award', 'fieldname' => 'Awarded'),
                        array('caption' => 'Membership', 'fieldname' => 'MSText', 'hide' => array('xs')),
                        array('caption' => 'Actions', 'searchable' => FALSE, 'sortable' => FALSE)
                    ),
                    'sortby' => array('column' => 'Awarded', 'direction' => 'desc')
            );
            LoadDatatable($table);            
            break;            
        case 'table_applications':
            $caption = 'Applications';
            if(!empty($_GET['ApplicationStageID'])) {
                $sql = 
                "SELECT ApplicationStageID, StageName
                 FROM tblapplicationstage
                 WHERE (CategorySelector = '".IdentifierStr($_GET['CategorySelector'])."') AND (ApplicationStageID = ".intval($_GET['ApplicationStageID']).")";
                $stagerecord = SingleRecord($SYSTEM_SETTINGS['Database'], $sql);
                $caption .= ' - '.$stagerecord['StageName'];
            }            
            $caption = DescribeByRequestConditions(
                array(), $caption, null,
                array(
                    'IsOpen' => array('0' => '[Closed]'),
                    'Paid' => array('0' => '[Unpaid]', '1' => '[Paid]'),
                )
            );            
            if(!empty($caption)) {
                SimpleHeading('List: '.$caption, 4, 'sub', 9);        
            }
            $table = array('id' => 'dt_applications', 'ajaxsrc' => '/datatable.php',
                    'params' => array('inc' => ($_GET['CategorySelector'] == 'members' ? 'dtMSApplication.php' : '') , 'fnrow' => 'dtGetRow'),
                    'GET' => array('CategorySelector', 'ApplicationStageID', 'IsOpen', 'Paid'),
                    'drawcallback' => "dtDefDrawCallBack( oSettings );",
                    'description' => $caption,
                    'responsive' => TRUE, 'hover' => FALSE, 'striped' => FALSE,
                    'columns' => array(
                        array('caption' => 'Name', 'fieldname' => '_Sortname', 'width' => '20%'),
                        array('caption' => 'Status', 'fieldname' => 'StageName', 'width' => '15%'),
                        array('caption' => 'Updated', 'fieldname' => 'LastModified', 'hide' => array('xs', 'sm', 'md'), 'width' => '9.5em'),
                        array('caption' => 'Recent', 'fieldname' => 'Recent', 'hide' => array('xs', 'sm', 'md')),
                        array('caption' => 'Assigned To', 'fieldname' => 'AssignedTo'),
                        array('caption' => 'Actions', 'searchable' => FALSE, 'sortable' => FALSE)
                    ),
                    'sortby' => array('column' => '_Sortname', 'direction' => 'asc')
            );
            LoadDatatable($table);
            break;
        case 'table_workflow':
            $caption = 'Workflow items';
            if(!empty($caption)) {
                SimpleHeading('List: '.$caption, 4, 'sub', 9);
            }
            $table = array('id' => 'dt_workflow', 'ajaxsrc' => '/datatable.php',
                    'params' => array('inc' => 'dtWorkflowList.php', 'fnrow' => 'dtGetRow'),
                    'GET' => array('CategorySelector', 'Assigned', 'AssignedTo'),
                    'drawcallback' => "dtDefDrawCallBack( oSettings );",
                    'description' => $caption,
                    'responsive' => TRUE, 'hover' => FALSE, 'striped' => FALSE,
                    'columns' => array(
                        array('caption' => '#.', 'fieldname' => 'WorkflowItemID', 'textalign' => 'center', 'width' => '4em', 'hide' => array('xs')),
                        array('caption' => '', 'fieldname' => 'TotalPriority', 'searchable' => FALSE, 'textalign' => 'center'),
                        array('caption' => 'Subject', 'fieldname' => '_Sortname'),
                        array('caption' => 'Updated', 'fieldname' => 'LastUpdated', 'width' => '9.25em', 'hide' => array('xs')),
                        array('caption' => '', 'textalign' => 'center', 'fieldname' => 'Avatar', 'searchable' => FALSE, 'sortable' => FALSE, 'hide' => array('xs', 'sm', 'md')),
                        array('caption' => 'Assigned To', 'fieldname' => 'AssignedTo', 'sortable' => FALSE),
                        array('caption' => 'Recent', 'fieldname' => 'Recent', 'sortable' => FALSE, 'width' => '25%'),
                        array('caption' => 'Actions', 'searchable' => FALSE, 'sortable' => FALSE)
                    ),
                    'sortby' => array('column' => 'TotalPriority', 'direction' => 'desc')                    
            );
            LoadDatatable($table);
            break;
        case 'aag_people':
            $sql = 
            "SELECT COUNT(DISTINCT tblperson.PersonID) AS `PeopleCount`,
                    COUNT(DISTINCT IF(FIND_IN_SET('ismember', tblmsstatus.MSStatusFlags), tblperson.PersonID, NULL)) AS `MemberCount`,
                    COUNT(DISTINCT IF(tblperson.Deceased IS NULL, tblperson.PersonID, NULL)) AS `ActiveCount`
             FROM tblperson
             LEFT JOIN tblpersonms ON (tblpersonms.PersonID = tblperson.PersonID) AND (tblpersonms.BeginDate <= UTC_TIMESTAMP()) AND ((tblpersonms.EndDate IS NULL) OR (tblpersonms.EndDate >= UTC_TIMESTAMP()))
             LEFT JOIN tblmsstatus ON (tblmsstatus.MSStatusID = tblpersonms.MSStatusID)
             LEFT JOIN tblmsgrade ON (tblmsgrade.MSGradeID = tblpersonms.MSGradeID)";
            $data = SingleRecord($SYSTEM_SETTINGS['Database'], $sql);
            $actPct = (!empty($data['PeopleCount']) ?($data['ActiveCount'] / $data['PeopleCount'])*100 : 0);
            $memPct = (!empty($data['PeopleCount']) ? ($data['MemberCount'] / $data['PeopleCount'])*100 : 0);
            $widget = array(
                'sections' => array(
                    0 => array(
                        'type' => 'title',
                        'content' => array(
                            'title' => '<b>People</b>'
                        ),
                    ),
                    1 => array(
                        'type' => 'easypie',
                        'content' => array(
                            'autocaption' => TRUE,
                            'items' => array(
                                array(
                                    'title' => array(
                                        'caption' => 'Active'
                                    ),
                                    'colour' => ($actPct >= 90 ? '#27ae60' : '#eded47'),
                                    'value' => array(
                                        'percent' => $actPct,
                                    ),
                                ),
                                array(
                                    'title' => array(
                                        'caption' => 'Members'
                                    ),
                                    'colour' => ($memPct > 25 ? '#27ae60' : '#eded47'),
                                    'value' => array(
                                        'percent' => $memPct,
                                    ),                                
                                ),
                            ),
                        ),
                    ),
                    2 => array(
                        'type' => 'summary',
                        'content' => array(
                            'theme' => 'flatie',
                            'autobold' => TRUE,
                            'items' => array(
                                array(
                                    'caption' => number_format($data['PeopleCount'], 0, '.', ','),
                                    'strapline' => 'People',
                                ),
                                array(
                                    'caption' => number_format($data['ActiveCount'], 0, '.', ','),
                                    'strapline' => 'Active',
                                ),
                                array(
                                    'caption' => number_format($data['MemberCount'], 0, '.', ','),
                                    'strapline' => 'Members',
                                ),
                            ),
                        ),
                    ),
                ),
            );
            Widget($widget, 8);
            break;
        case 'aag_subscribers':
            $sql =
            "SELECT COUNT(DISTINCT tblperson.PersonID) AS `PeopleCount`,
	                COUNT(DISTINCT IF(FIND_IN_SET('ismember', tblmsstatus.MSStatusFlags), tblperson.PersonID, NULL)) AS `MemberCount`,
                    COUNT(DISTINCT IF(tblperson.Deceased IS NULL, tblperson.PersonID, NULL)) AS `ActiveCount`,
                    tblpublication.Title, tblpublication.PublicationType, tblpublication.PublicationScope,
                    COUNT(DISTINCT IF((tblpublicationtoperson.Qty > 0) AND (tblpublicationtoperson.Suspended = 0), tblpublicationtoperson.PublicationToPersonID, NULL)) AS `SubsCount`,
                    SUM(IF((tblpublicationtoperson.Qty > 0) AND (tblpublicationtoperson.Suspended = 0), tblpublicationtoperson.Qty, NULL)) AS `QtyCount`,
                    COUNT(DISTINCT IF((tblpublicationtoperson.Qty = 0) AND (tblpublicationtoperson.Suspended = 0), tblpublicationtoperson.PublicationToPersonID, NULL)) AS `OptoutCount`,               
                    COUNT(DISTINCT IF((tblpublicationtoperson.Qty > 0) AND (tblpublicationtoperson.Suspended > 0), tblpublicationtoperson.PublicationToPersonID, NULL)) AS `SuspendedCount`               
             FROM tblperson
             LEFT JOIN tblpersonms ON (tblpersonms.PersonID = tblperson.PersonID) AND (tblpersonms.BeginDate <= UTC_TIMESTAMP()) AND ((tblpersonms.EndDate IS NULL) OR (tblpersonms.EndDate >= UTC_TIMESTAMP()))
             LEFT JOIN tblmsstatus ON (tblmsstatus.MSStatusID = tblpersonms.MSStatusID)
             LEFT JOIN tblmsgrade ON (tblmsgrade.MSGradeID = tblpersonms.MSGradeID)
             INNER JOIN tblpublication ON tblpublication.PublicationID = ".intval($_GET['PublicationID'])."
             LEFT JOIN tblpublicationtoperson ON (tblpublicationtoperson.PersonID = tblperson.PersonID) AND (tblpublicationtoperson.PublicationID = tblpublication.PublicationID)";
            $data = SingleRecord($SYSTEM_SETTINGS['Database'], $sql);
            if(strcasecmp($data['PublicationScope'], 'members') == 0) {
                $total = array('Caption' => 'Members', 'Value' => $data['MemberCount']);
                $subs = array(
                    'Pct' => (!empty($data['MemberCount']) ? (($data['SubsCount'] / $data['MemberCount'])*100) : 0),
                    'OptoutPct' => (!empty($data['MemberCount']) ? (($data['OptoutCount'] / $data['MemberCount'])*100) : 0),
                    'OptoutPct' => (!empty($data['MemberCount']) ? (($data['OptoutCount'] / $data['MemberCount'])*100) : 0),
                    'SuspendedPct' => (!empty($data['SubsCount']) ? (($data['SuspendedCount'] / $data['SubsCount'])*100) : 0),
                );
            } else {
                $total = array('Caption' => 'People', 'Value' => $data['PeopleCount']);
                $subs = array(
                    'Pct' => (!empty($data['PeopleCount']) ? (($data['SubsCount'] / $data['PeopleCount'])*100) : 0),
                    'OptoutPct' => (!empty($data['PeopleCount']) ? (($data['OptoutCount'] / $data['PeopleCount'])*100) : 0),
                    'SuspendedPct' => (!empty($data['SubsCount']) ? (($data['SuspendedCount'] / $data['SubsCount'])*100) : 0),
                );
            }
            $widget = array(
                'sections' => array(
                    0 => array(
                        'type' => 'title',
                        'content' => array(
                            'title' => '<b>'.$data['Title'].'</b>'
                        ),
                    ),
                    1 => array(
                        'type' => 'easypie',
                        'content' => array(
                            'autocaption' => TRUE,
                            'items' => array(
                                array(
                                    'title' => array(
                                        'caption' => 'Subscribers'
                                    ),
                                    'colour' => ($subs['Pct'] >= 90 ? '#27ae60' : '#eded47'),
                                    'value' => array(
                                        'percent' => $subs['Pct'],
                                    ),
                                ),
                                array(
                                    'title' => array(
                                        'caption' => 'Suspended'
                                    ),
                                    'colour' => ($subs['SuspendedPct'] < 10 ? '#27ae60' : '#eded47'),
                                    'value' => array(
                                        'percent' => $subs['SuspendedPct'],
                                    ),                                
                                ),
                            ),
                        ),
                    ),
                    2 => array(
                        'type' => 'summary',
                        'content' => array(
                            'theme' => 'flatie',
                            'autobold' => TRUE,
                            'items' => array(
/*                                array(
                                    'caption' => number_format($total['Value'], 0, '.', ','),
                                    'strapline' => $total['Caption'],
                                ),*/
                                array(
                                    'caption' => number_format($data['SubsCount'], 0, '.', ','),
                                    'strapline' => 'Subscribers',
                                ),
                                array(
                                    'caption' => number_format($data['OptoutCount'], 0, '.', ','),
                                    'strapline' => 'Opt-out',
                                ),
                                array(
                                    'caption' => number_format($data['SuspendedCount'], 0, '.', ','),
                                    'strapline' => 'Suspended',
                                ),
                            ),
                        ),
                    ),
                ),
            );
            Widget($widget, 8);
            break;
        case 'table_members':
            $menuitems = array();
//            $menuitems[] = array('colour' => 'info', 'tooltip' => 'Reload the table from the server', 'caption' => 'Refresh', 'script' => 'RefreshDataTable(dt_people);', 'icon' => 'fa-refresh');
            stdTitleBlock('Membership', $do, $menuitems, TRUE);
            Div(array('class' => array('block-content')), 8);
            Div(array('class' => 'add-bottom-margin-std'), 9);
            $buttongroup = qaButtons('members');
            array_unshift($buttongroup,
                array('icon' => 'gi-address_book', 'iconalign' => 'left', 'tooltip' => 'gi-address_book', 'caption' => 'Directory', 'type' => 'button', 'colour' => 'info', 'script' => "LoadContent('wsMain', '/load.php?do=table_msdir', { spinner: true } );")
            );
            ButtonGroup($buttongroup, FALSE, null, 10);
            Div(null, 9);
            //MS Workflow Summary
            $sql =
            "-- MS Applications Summary
             SELECT tblapplicationstage.ApplicationStageID, tblapplicationstage.StageName, tblapplicationstage.SubmissionStage, tblapplicationstage.StageColour,
                    tblapplicationstage.PaymentRequired, tblapplicationstage.IsCompletionStage, tblapplicationstage.IsElectionStage,
                    COUNT(DISTINCT IF(FIND_IN_SET('paid', tblapplication.Flags), tblapplication.ApplicationID, NULL)) AS `PaidCount`,
                    COUNT(DISTINCT IF(FIND_IN_SET('paid', tblapplication.Flags), NULL, tblapplication.ApplicationID)) AS `UnpaidCount`,
                    tblapplicationstage.CategorySelector
             FROM tblapplicationstage
             LEFT JOIN tblapplication ON (tblapplication.ApplicationStageID = tblapplicationstage.ApplicationStageID) AND (tblapplication.IsOpen) AND (tblapplication.Cancelled IS NULL)
             WHERE tblapplicationstage.CategorySelector = 'members'
             GROUP BY tblapplicationstage.ApplicationStageID
             ORDER BY tblapplicationstage.StageOrder;
             -- MS Workflow Items
             SELECT COUNT(DISTINCT tblworkflowitem.WorkflowItemID) AS `TotalCount`,
	                COUNT(DISTINCT tblassignedperson.PersonID) AS `AssignedCount`,
	                COUNT(DISTINCT IF(tblassignedperson.PersonID IS NULL, tblworkflowitem.WorkflowItemID, NULL)) AS `NotAssignedCount`,
                    COUNT(DISTINCT IF(tblassignedperson.PersonID = {$AUTHENTICATION['Person']['PersonID']}, tblworkflowitem.WorkflowItemID, NULL)) AS `AssignedToMeCount`
             FROM tblworkflowitem
             INNER JOIN tblworkflowitemtocategory ON tblworkflowitemtocategory.WorkflowItemID = tblworkflowitem.WorkflowItemID
             INNER JOIN tblwscategory ON tblworkflowitemtocategory.WSCategoryID = tblwscategory.WSCategoryID
             LEFT JOIN tblperson AS tblassignedperson ON tblassignedperson.PersonID = tblworkflowitem.PersonID
             WHERE tblwscategory.CategorySelector = 'members';
            ";
            if(mysqli_multi_query($SYSTEM_SETTINGS['Database'], $sql)) {
                $query = mysqli_store_result($SYSTEM_SETTINGS['Database']);
                if($query) {
                    Div(array('class' => array('table-responsive'), 'id' => 'msapplications'), 9);
                    echo str_repeat("\t", 10)."<table class=\"table table-hover table-condensed\">\n";
                    echo str_repeat("\t", 11)."<thead>\n";
                    echo str_repeat("\t", 12)."<tr>\n";
                    echo str_repeat("\t", 13)."<th></th>\n";
                    echo str_repeat("\t", 13)."<th class=\"text-center\">Not paid</th>\n";
                    echo str_repeat("\t", 13)."<th class=\"text-center\">Paid</th>\n";
                    echo str_repeat("\t", 13)."<th class=\"text-center\"><span class=\"text-info\"><b>Total</b></span></th>\n";
                    echo str_repeat("\t", 12)."</tr>\n";
                    echo str_repeat("\t", 11)."</thead>\n";
                    echo str_repeat("\t", 11)."<tbody>\n";
                    $mstotals = array('Paid' => 0, 'Unpaid' => 0);
                    while($row = mysqli_fetch_assoc($query)) {
                        echo str_repeat("\t", 12)."<tr>\n";
                        echo str_repeat("\t", 13)."<td>".ApplicationTableLink($row, $row['StageName'], array('CategorySelector', 'ApplicationStageID'))."</td>\n";
                        if($row['SubmissionStage'] < 0) {
                            echo str_repeat("\t", 13)."<td colspan=\"2\" class=\"text-center\">".ApplicationTableLink($row, number_format($row['PaidCount']+$row['UnpaidCount'], 0, '.', ','), array('CategorySelector', 'ApplicationStageID'))."</td>\n";
                        } else {
                            echo str_repeat("\t", 13)."<td class=\"text-center\">";
                            if(empty($row['PaymentRequired'])) {
                                echo ApplicationTableLink(array_merge($row, array('Paid' => 0)), number_format($row['UnpaidCount'], 0, '.', ','), array('CategorySelector', 'ApplicationStageID', 'Paid')); 
                            }
                            echo "</td>\n";
                            echo str_repeat("\t", 13)."<td class=\"text-center\">".ApplicationTableLink(array_merge($row, array('Paid' => 1)), number_format($row['PaidCount'], 0, '.', ','), array('CategorySelector', 'ApplicationStageID', 'Paid'))."</td>\n";
                            echo str_repeat("\t", 13)."<td class=\"text-center\"><span class=\"text-info\"><b>".ApplicationTableLink($row, number_format($row['PaidCount']+$row['UnpaidCount'], 0, '.', ','), array('CategorySelector', 'ApplicationStageID'))."</b></span></td>\n";
                        }
                        $mstotals['Paid'] += $row['PaidCount'];
                        $mstotals['Unpaid'] += $row['UnpaidCount'];
                        echo str_repeat("\t", 12)."</tr>\n";
                    }
                    echo str_repeat("\t", 11)."</tbody>\n";
                    echo str_repeat("\t", 10)."</table>\n";
                    Div(null, 9); //table div
                    mysqli_free_result($query);
                } else {
                    SimpleAlertBox("error", "Unable to generate membership applications overview table.", 8);
                }
                //Get Workflow table items
                if (mysqli_next_result($SYSTEM_SETTINGS['Database'])) {
                    $query = mysqli_store_result($SYSTEM_SETTINGS['Database']);
                    if($query) {
                        $workflowsummary = mysqli_fetch_assoc($query);
                        if($workflowsummary['TotalCount'] > 0) {
                            Div(array('class' => array('table-responsive'), 'id' => 'msworkflow'), 9);
                            echo str_repeat("\t", 10)."<table class=\"table table-hover table-condensed table-borderless\">\n";
                            echo str_repeat("\t", 11)."<tbody>\n";
                            echo str_repeat("\t", 12)."<tr>\n";
                            echo str_repeat("\t", 13)."<td>";
                            echo LinkTo(
                                "There ".SinPlu($workflowsummary['TotalCount'], 'record', 'is')." in the membership workflow",
                                array('script' => "LoadContent('msBreakdownList', '/load.php?do=table_workflow', { spinner: true, hide: 'msataglance', urlparams: { CategorySelector: 'members' } });")
                            );
                            echo "</td>\n";
                            echo str_repeat("\t", 12)."</tr>\n";
                            if($workflowsummary['NotAssignedCount'] > 0) {
                                echo str_repeat("\t", 12)."<tr>\n";
                                echo str_repeat("\t", 13)."<td>";
                                echo LinkTo(
                                    SinPlu($workflowsummary['NotAssignedCount'], 'record').($workflowsummary['NotAssignedCount'] == 1 ? ' has': ' have')." not been assigned",
                                    array('script' => "LoadContent('msBreakdownList', '/load.php?do=table_workflow', { spinner: true, hide: 'msataglance', urlparams: { CategorySelector: 'members', Assigned: 0 } });")
                                );
                                echo "</td>\n";
                                echo str_repeat("\t", 12)."</tr>\n";
                            }
                            if($workflowsummary['AssignedToMeCount'] > 0) {
                                echo str_repeat("\t", 12)."<tr>\n";
                                echo str_repeat("\t", 13)."<td><span class=\"text-info\"><b>";
                                echo LinkTo(
                                    "There ".SinPlu($workflowsummary['AssignedToMeCount'], 'record', 'is')." assigned to me",
                                    array('script' => "LoadContent('msBreakdownList', '/load.php?do=table_workflow', { spinner: true, hide: 'msataglance', urlparams: { CategorySelector: 'members', AssignedTo: {$AUTHENTICATION['Person']['PersonID']} } });")
                                );
                                echo "</b></span></td>\n";
                                echo str_repeat("\t", 12)."</tr>\n";
                            }
                            echo str_repeat("\t", 11)."</tbody>\n";
                            echo str_repeat("\t", 10)."</table>\n";
                            Div(null, 9); //table div
                        }
                        mysqli_free_result($query);
                    } else {
                        SimpleAlertBox("error", "Unable to generate workflow overview table.", 8);
                    }
                } else {
                    SimpleAlertBox("error", "Unable to retrieve workflow overview data.", 8);
                }
            }
            if(empty($_GET['hideataglance'])) {
                //$query = mysqli_query($SYSTEM_SETTINGS['Database'], $sql); 
                //Summary table of Membership
                $sql = 
                "SELECT IF(FIND_IN_SET('ismember', tblmsstatus.MSStatusFlags), NULL, tblmsstatus.MSStatusID) AS `MSStatusID`, tblmsgrade.MSGradeID,
                        COALESCE(tblmsgrade.GradeCaption, '') AS `MSGradeText`,
                        COALESCE(tblmsstatus.MSStatusCaption, 'Not a Member') AS `MSStatusTxt`,
                        IF(FIND_IN_SET('ismember', tblmsstatus.MSStatusFlags), 1, 0) AS `IsMember`,
                        COUNT(DISTINCT IF(tblperson.Deceased IS NULL AND FIND_IN_SET('ismember', tblmsstatus.MSStatusFlags) AND FIND_IN_SET('free', tblpersonms.MSFlags), tblperson.PersonID, NULL)) AS `FreeCount`,
                        COUNT(DISTINCT IF(tblperson.Deceased IS NULL AND FIND_IN_SET('ismember', tblmsstatus.MSStatusFlags) AND (NOT FIND_IN_SET('free', tblpersonms.MSFlags)), tblperson.PersonID, NULL)) AS `PaidCount`,
                        COUNT(DISTINCT tblperson.PersonID) AS `PeopleCount`,
                        COUNT(DISTINCT IF(tblperson.Deceased IS NULL AND FIND_IN_SET('ismember', tblmsstatus.MSStatusFlags), tblperson.PersonID, NULL)) AS `MemberCount`,
                        COUNT(DISTINCT IF(tblperson.Deceased IS NULL AND FIND_IN_SET('ismember', tblmsstatus.MSStatusFlags) AND (NOT tblperson.MSNextRenewal < CURRENT_DATE()) AND (CURRENT_DATE() >= DATE_SUB(tblperson.MSNextRenewal, INTERVAL {$SYSTEM_SETTINGS["Membership"]["RenewalCycleStart"]} DAY)), tblperson.PersonID, NULL)) AS `RenewalPendingCount`,
                        COUNT(DISTINCT IF(tblperson.Deceased IS NULL AND FIND_IN_SET('ismember', tblmsstatus.MSStatusFlags) AND (tblperson.MSNextRenewal < CURRENT_DATE()), tblperson.PersonID, NULL)) AS `OverdueCount`
                 FROM tblperson
                 LEFT JOIN tblpersonms ON (tblpersonms.PersonID = tblperson.PersonID) AND (tblpersonms.BeginDate <= UTC_TIMESTAMP()) AND ((tblpersonms.EndDate IS NULL) OR (tblpersonms.EndDate >= UTC_TIMESTAMP()))
                 RIGHT JOIN tblmsstatus ON (tblmsstatus.MSStatusID = tblpersonms.MSStatusID)
                 LEFT JOIN tblmsgrade ON (tblmsgrade.MSGradeID = tblpersonms.MSGradeID)
                 GROUP BY IF(FIND_IN_SET('ismember', tblmsstatus.MSStatusFlags), tblmsgrade.MSGradeID, tblmsstatus.MSStatusID) ";
/*                "SELECT tblmsstatus.MSStatusID, tblmsgrade.MSGradeID,
                        COALESCE(tblmsgrade.GradeCaption, '') AS `MSGradeText`,
                        COALESCE(tblmsstatus.MSStatusCaption, 'Not a Member') AS `MSStatusTxt`,
                        IF(FIND_IN_SET('ismember', tblmsstatus.MSStatusFlags), 1, 0) AS `IsMember`,
                        COUNT(DISTINCT IF(tblperson.Deceased IS NULL AND FIND_IN_SET('ismember', tblmsstatus.MSStatusFlags) AND FIND_IN_SET('free', tblpersonms.MSFlags), tblperson.PersonID, NULL)) AS `FreeCount`,
                        COUNT(DISTINCT tblperson.PersonID) AS `PeopleCount`,
                        COUNT(DISTINCT IF(tblperson.Deceased IS NULL AND FIND_IN_SET('ismember', tblmsstatus.MSStatusFlags), tblperson.PersonID, NULL)) AS `MemberCount`,
                        COUNT(DISTINCT IF(tblperson.Deceased IS NULL AND FIND_IN_SET('ismember', tblmsstatus.MSStatusFlags) AND (NOT tblperson.MSNextRenewal < CURRENT_DATE()) AND (CURRENT_DATE() >= DATE_SUB(tblperson.MSNextRenewal, INTERVAL {$SYSTEM_SETTINGS["Membership"]["RenewalCycleStart"]} DAY)), tblperson.PersonID, NULL)) AS `RenewalPendingCount`,
                        COUNT(DISTINCT IF(tblperson.Deceased IS NULL AND FIND_IN_SET('ismember', tblmsstatus.MSStatusFlags) AND (tblperson.MSNextRenewal < CURRENT_DATE()), tblperson.PersonID, NULL)) AS `OverdueCount`
                 FROM tblperson
                 LEFT JOIN tblpersonms ON (tblpersonms.PersonID = tblperson.PersonID) AND (tblpersonms.BeginDate <= UTC_TIMESTAMP()) AND ((tblpersonms.EndDate IS NULL) OR (tblpersonms.EndDate >= UTC_TIMESTAMP()))
                 RIGHT JOIN tblmsstatus ON (tblmsstatus.MSStatusID = tblpersonms.MSStatusID)
                 LEFT JOIN tblmsgrade ON (tblmsgrade.MSGradeID = tblpersonms.MSGradeID)
                 WHERE FIND_IN_SET('msstats', tblmsstatus.MSStatusFlags)
                 GROUP BY tblmsstatus.MSStatusID, tblmsgrade.MSGradeID
                 ORDER BY tblmsstatus.MSStatusID DESC, tblmsgrade.MSGradeID";*/
                $query = mysqli_query($SYSTEM_SETTINGS['Database'], $sql);
                if($query) {
                    Div(array('id' => 'msataglance'), 9);
                    echo str_repeat("\t", 10)."<table class=\"table table-vcenter table-hover table-condensed\">\n";
                    echo str_repeat("\t", 11)."<thead>\n";
                    echo str_repeat("\t", 12)."<tr>\n";
                    echo str_repeat("\t", 13)."<th rowspan=\"2\"></th>\n";
                    echo str_repeat("\t", 13)."<th rowspan=\"2\" class=\"text-center\">Free</th>\n";
                    echo str_repeat("\t", 13)."<th colspan=\"4\" class=\"text-center\">Paid</th>\n";
                    echo str_repeat("\t", 13)."<th rowspan=\"2\" class=\"text-center\"><span class=\"text-info\"><b>Total</b></span></th>\n";
                    echo str_repeat("\t", 12)."</tr>\n";
                    echo str_repeat("\t", 12)."<tr>\n";
                    echo str_repeat("\t", 13)."<th class=\"text-center\"><small>Renewal Pending</small></th>\n";
                    echo str_repeat("\t", 13)."<th class=\"text-center\"><small>Renewal Overdue</small></th>\n";
                    echo str_repeat("\t", 13)."<th class=\"text-center\"><small>Up-to-date</small></th>\n";
                    echo str_repeat("\t", 13)."<th class=\"text-center\"><small><b>Subtotal</b></small></th>\n";
                    echo str_repeat("\t", 12)."</tr>\n";
                    echo str_repeat("\t", 11)."</thead>\n";
                    echo str_repeat("\t", 11)."<tbody>\n";
                    $mstotals = array(
                        'Free' => array('count' => 0, 'Fieldname' => 'FreeCount', 'urlparams' => array('Free' => 1), 'format' => '<info>'),
                        'Pending' => array('count' => 0, 'Fieldname' => 'RenewalPendingCount', 'urlparams' => array('RenewalCycle' => 1, 'Free' => 0), 'format' => '<warning>'),
                        'Overdue' => array('count' => 0, 'Fieldname' => 'OverdueCount', 'urlparams' => array('RenewalCycle' => 2, 'Free' => 0), 'format' => '<danger>'),
                        'Uptodate' => array('count' => 0, 'urlparams' => array('RenewalCycle' => 0, 'Free' => 0), 'format' => '<info>'),
                        'Paid' => array('count' => 0, 'Fieldname' => 'PaidCount', 'urlparams' => array('Free' => 0), 'format' => '<info>'),
                        'Total' => array('count' => 0, 'Fieldname' => 'MemberCount', 'urlparams' => array(), 'format' => '<b><info>'),
                    );
                    while($row = mysqli_fetch_assoc($query)) {
                        if(!empty($mstotals) && (!$row['IsMember'])) {
                            echo str_repeat("\t", 12)."<tr class=\"info\">\n";
                            echo str_repeat("\t", 13)."<td>".LinkTo('<b>Membership</b>', MSTableLink($mstotals, array('IsMember' => 1)))."</td>\n";
                            foreach($mstotals AS $key => $settings) {
                                echo str_repeat("\t", 13)."<td class=\"text-center\">";
                                echo LinkTo(
                                    $settings['format'].number_format($settings['count'], 0, '.', ',').CloseFormattingString($settings['format']), 
                                    MSTableLink($mstotals, array_merge($settings['urlparams'], array('IsMember' => 1)))
                                );
                                echo "</td>";
                            }
                            echo str_repeat("\t", 12)."</tr>\n";
                            $mstotals = null;
                        }
                        echo str_repeat("\t", 12)."<tr>\n";
                        if($row['IsMember']) {
                            echo str_repeat("\t", 13)."<td>".LinkTo($row['MSGradeText'], MSTableLink($row, array('IsMember' => 1, 'MSGradeID' => null)))."</td>\n";
                            foreach($mstotals AS $key => $settings) {
                                echo str_repeat("\t", 13)."<td class=\"text-center\">";
                                if(!empty($settings['Fieldname'])) {
                                    $value = $row[$settings['Fieldname']];
                                } else {
                                    $value = $row['PaidCount']-($row['RenewalPendingCount']+$row['OverdueCount']);
                                }
                                $mstotals[$key]['count'] += $value;
                                echo LinkTo(
                                    $settings['format'].number_format($value, 0, '.', ',').CloseFormattingString($settings['format']),
                                    MSTableLink($row, array_merge($settings['urlparams'], array('IsMember' => 1, 'MSGradeID' => null)))
                                );
                                echo "</td>";
                            }
                            //"<td>".MSTableLink($row, $row['MSGradeText'], array('MSStatusID', 'MSGradeID'))."</td>\n";
                        } else {
                            $link = MSTableLink($row, array('MSStatusID' => null));
                            echo str_repeat("\t", 13)."<td colspan=\"6\">".LinkTo($row['MSStatusTxt'], $link)."</td>\n";
                            echo str_repeat("\t", 13)."<td class=\"text-center\">";
                            echo LinkTo(number_format($row['PeopleCount'], 0, '.', ','), $link);
                            echo "</td>";                            
                        }
                        echo str_repeat("\t", 12)."</tr>\n";
                    }
                    echo str_repeat("\t", 11)."</tbody>\n";
                    echo str_repeat("\t", 10)."</table>\n";                    
                    Div(null, 9);
/*                    Div(array('class' => array('table-responsive'), 'id' => 'msataglance'), 9);
                    echo str_repeat("\t", 10)."<table class=\"table table-hover table-condensed\">\n";
                    echo str_repeat("\t", 11)."<thead>\n";
                    echo str_repeat("\t", 12)."<tr>\n";
                    echo str_repeat("\t", 13)."<th></th>\n";
                    echo str_repeat("\t", 13)."<th class=\"text-center\">Free</th>\n";
                    echo str_repeat("\t", 13)."<th class=\"text-center\">Paid</th>\n";
                    echo str_repeat("\t", 13)."<th class=\"text-center\">Renewal Pending</th>\n";
                    echo str_repeat("\t", 13)."<th class=\"text-center\">Renewal Overdue</th>\n";
                    echo str_repeat("\t", 13)."<th class=\"text-center\">Up-to-date</th>\n";
                    echo str_repeat("\t", 13)."<th class=\"text-center\"><span class=\"text-info\"><b>Total</b></span></th>\n";
                    echo str_repeat("\t", 12)."</tr>\n";
                    echo str_repeat("\t", 11)."</thead>\n";
                    echo str_repeat("\t", 11)."<tbody>\n";
                    $mstotals = array('MSStatusID' => null, 'MemberCount' => 0, 'RenewalPendingCount' => 0, 'OverdueCount' => 0, 'FreeCount' => 0, 'PaidCount' => 0);
                    while($row = mysqli_fetch_assoc($query)) {
                        if(!empty($mstotals) && (!$row['IsMember'])) {
                            //Print Membership totals
                            echo str_repeat("\t", 12)."<tr class=\"info\">\n";
                            echo str_repeat("\t", 13)."<td><b>".MSTableLink($mstotals, 'Membership', array('MSStatusID'))."</b></td>\n";
                            $str = "<info>".number_format($mstotals['FreeCount'], 0, '.', ',')."</info>";
                            echo str_repeat("\t", 13)."<td class=\"text-center\"><b>".MSTableLink(array_merge($mstotals, array('Free' => 1)), $str, array('MSStatusID', 'Free'))."</b></td>\n";

                            $str = number_format($mstotals['RenewalPendingCount'], 0, '.', ',');
                            if($mstotals['RenewalPendingCount'] > 0) {
                                $str = "<info>{$str}</info>";
                            }
                            echo str_repeat("\t", 13)."<td class=\"text-center\"><b>".MSTableLink(array_merge($mstotals, array('RenewalCycleTxt' => 'Renewal Pending')), $str, array('MSStatusID', 'RenewalCycleTxt'))."</b></td>\n";
                            $str = number_format($mstotals['OverdueCount'], 0, '.', ',');
                            if($mstotals['OverdueCount'] > 0) {
                                $str = "<danger>{$str}</danger>";
                            }
                            echo str_repeat("\t", 13)."<td class=\"text-center\"><b>".MSTableLink(array_merge($mstotals, array('RenewalCycleTxt' => 'Renewal Overdue')), $str, array('MSStatusID', 'RenewalCycleTxt'))."</b></td>\n";
                            echo str_repeat("\t", 13)."<td class=\"text-center\"><b>".MSTableLink(array_merge($mstotals, array('RenewalCycleTxt' => 'Up-to-date')), number_format($mstotals['MemberCount']-$mstotals['RenewalPendingCount']-$mstotals['OverdueCount'], 0, '.', ','), array('MSStatusID', 'RenewalCycleTxt'))."</b></td>\n";
                            echo str_repeat("\t", 13)."<td class=\"text-center\"><span class=\"text-info\"><b>".MSTableLink($mstotals, number_format($mstotals['MemberCount'], 0, '.', ','), array('MSStatusID'))."</b></span></td>\n";
                            echo str_repeat("\t", 12)."</tr>\n";
                            $mstotals = null;
                        }
                        echo str_repeat("\t", 12)."<tr>\n";
//                      echo str_repeat("\t", 13).($row['IsMember'] ? "<td>".$row['MSGradeText']."</td>" : "<td colspan=\"4\">".$row['MSStatusTxt']."</td>")."\n";
                        if($row['IsMember']) {
                            if(is_null($mstotals['MSStatusID'])) {
                                $mstotals['MSStatusID'] = $row['MSStatusID'];
                            }
//                            echo str_repeat("\t", 13)."<td>".LinkTo($row['MSGradeText'], array('script' => "LoadContent('msBreakdownList', '/load.php?do=table_membership', { spinner: true, urlparams: { MSStatusID: {$row['MSStatusID']}, MSGradeID: {$row['MSGradeID']} } })"))."</td>\n";
                            echo str_repeat("\t", 13)."<td>".MSTableLink($row, $row['MSGradeText'], array('MSStatusID', 'MSGradeID'))."</td>\n";
                            $str = "<info>".number_format($row['FreeCount'], 0, '.', ',')."</info>";
                            echo str_repeat("\t", 13)."<td class=\"text-center\">".MSTableLink(array_merge($row, array('Free' => 1)), $str, array('MSStatusID', 'MSGradeID', 'Free'))."</td>\n";
                            
                            $str = number_format($row['RenewalPendingCount'], 0, '.', ',');
                            if($row['RenewalPendingCount'] > 0) {
                                $str = "<warning>{$str}</warning>";
                            }
                            echo str_repeat("\t", 13)."<td class=\"text-center\">".MSTableLink(array_merge($row, array('RenewalCycleTxt' => 'Renewal Pending')), $str, array('MSStatusID', 'MSGradeID', 'RenewalCycleTxt'))."</td>\n";
                            $str = number_format($row['OverdueCount'], 0, '.', ',');
                            if($row['OverdueCount'] > 0) {
                                $str = "<danger>{$str}</danger>";
                            }
                            echo str_repeat("\t", 13)."<td class=\"text-center\">".MSTableLink(array_merge($row, array('RenewalCycleTxt' => 'Renewal Overdue')), $str, array('MSStatusID', 'MSGradeID', 'RenewalCycleTxt'))."</td>\n";
                            echo str_repeat("\t", 13)."<td class=\"text-center\">".MSTableLink(array_merge($row, array('RenewalCycleTxt' => 'Up-to-date')), number_format($row['MemberCount']-$row['RenewalPendingCount']-$row['OverdueCount'], 0, '.', ','), array('MSStatusID', 'MSGradeID', 'RenewalCycleTxt'))."</td>\n";
                            echo str_repeat("\t", 13)."<td class=\"text-center\"><span class=\"text-info\"><b>".MSTableLink($row, number_format($row['MemberCount'], 0, '.', ','), array('MSStatusID', 'MSGradeID'))."</b></span></td>\n";
                            $mstotals['RenewalPendingCount'] += $row['RenewalPendingCount'];
                            $mstotals['OverdueCount'] += $row['OverdueCount'];
                            $mstotals['MemberCount'] += $row['MemberCount'];
                            $mstotals['FreeCount'] += $row['FreeCount'];
                        } else {
                            echo str_repeat("\t", 13)."<td colspan=\"5\">".MSTableLink($row, $row['MSStatusTxt'], array('MSStatusID'))."</td>\n";
                            echo str_repeat("\t", 13)."<td class=\"text-center\"><b>".MSTableLink($row, number_format($row['PeopleCount'], 0, '.', ','), array('MSStatusID'))."</b></td>\n";
                        }
                        echo str_repeat("\t", 12)."</tr>\n";
                    }
                    echo str_repeat("\t", 11)."</tbody>\n";
                    echo str_repeat("\t", 10)."</table>\n";
                    Div(null, 9); //table div msataglance*/
                } else {
                    SimpleAlertBox("error", "Unable to calculate membership statistics.", 8);
                }
            }
/*            echo str_repeat("\t", 9)."<a".A_Attribs(array('script' => "LoadContent('wsMain', '/load.php?do=table_msdir', { spinner: true } );"), TRUE).">";
            echo AdvIcon(array('icon' => 'gi-address_book', 'colour' => 'primary', 'size' => 5, 'tooltip' => 'Membership Directory', 'ttplacement' => 'bottom'));
            echo "</a>\n";*/
            //Container for leading the membership list in            
            Div(array('id' => 'msBreakdownList'), 9);
            
            Div(null, 9); //table div msBreakdownList
            Div(null, 8); //content block
            Div(null, 7); //title block            
            break;
        case 'aag_members':
            $sql = 
            "SELECT COUNT(DISTINCT tblperson.PersonID) AS `PeopleCount`,
                    COUNT(DISTINCT IF(FIND_IN_SET('ismember', tblmsstatus.MSStatusFlags), tblperson.PersonID, NULL)) AS `MemberCount`,
                    COUNT(DISTINCT IF(tblperson.Deceased IS NULL, tblperson.PersonID, NULL)) AS `ActiveCount`,
                    COUNT(DISTINCT IF(FIND_IN_SET('ismember', tblmsstatus.MSStatusFlags) AND (tblperson.MSNextRenewal < CURRENT_DATE()), tblperson.PersonID, NULL)) AS `OverdueCount`
             FROM tblperson
             LEFT JOIN tblpersonms ON (tblpersonms.PersonID = tblperson.PersonID) AND (tblpersonms.BeginDate <= UTC_TIMESTAMP()) AND ((tblpersonms.EndDate IS NULL) OR (tblpersonms.EndDate >= UTC_TIMESTAMP()))
             LEFT JOIN tblmsstatus ON (tblmsstatus.MSStatusID = tblpersonms.MSStatusID)
             LEFT JOIN tblmsgrade ON (tblmsgrade.MSGradeID = tblpersonms.MSGradeID)
             WHERE tblperson.Deceased IS NULL";
            $data = SingleRecord($SYSTEM_SETTINGS['Database'], $sql);
            $actPct = ($data['PeopleCount'] == 0 ? 0 : ($data['ActiveCount'] / $data['PeopleCount'])*100);
            $memPct = ($data['PeopleCount'] == 0 ? 0 : ($data['MemberCount'] / $data['PeopleCount'])*100);
            $utdPct = ($data['MemberCount'] == 0 ? 0 : (($data['MemberCount']-$data['OverdueCount']) / $data['MemberCount'])*100);
            $duePct = ($data['MemberCount'] == 0 ? 0 : ($data['OverdueCount'] / $data['MemberCount'])*100);
            $widget = array(
                'sections' => array(
                    0 => array(
                        'type' => 'title',
                        'content' => array(
                            'title' => '<b>Members</b>'
                        ),
                    ),
                    1 => array(
                        'type' => 'easypie',
                        'content' => array(
                            'autocaption' => TRUE,
                            'items' => array(
                                array(
                                    'title' => array(
                                        'caption' => 'Up-to-date'
                                    ),
                                    'colour' => ($memPct > 89 ? '#27ae60' : '#eded47'),
                                    'value' => array(
                                        'percent' => $utdPct,
                                    ),                                
                                ),
                                array(
                                    'title' => array(
                                        'caption' => 'Overdue'
                                    ),
                                    'colour' => ($duePct < 10 ? '#eded47' : '#e11b1b'),
                                    'value' => array(
                                        'percent' => $duePct,
                                    ),                                
                                ),                                
                            ),
                        ),
                    ),
                    2 => array(
                        'type' => 'summary',
                        'content' => array(
                            'theme' => 'flatie',
                            'autobold' => TRUE,
                            'items' => array(
                                array(
                                    'caption' => number_format($data['MemberCount'], 0, '.', ','),
                                    'strapline' => 'Members',
                                ),
                                array(
                                    'caption' => number_format(($data['MemberCount']-$data['OverdueCount']), 0, '.', ','),
                                    'strapline' => 'Up-to-date',
                                ),
                                array(
                                    'caption' => number_format($data['OverdueCount'], 0, '.', ','),
                                    'strapline' => 'Overdue',
                                ),
                            ),
                        ),
                    ),
                ),
            );
            Widget($widget, 8);
            break;            
        case 'table_msdir':
            $menuitems = array();
//            $menuitems[] = array('colour' => 'info', 'tooltip' => 'Reload the table from the server', 'caption' => 'Refresh', 'script' => 'RefreshDataTable(dt_msdir);', 'icon' => 'fa-refresh');
            stdTitleBlock('Membership Directory', $do, $menuitems, TRUE);
            Div(array('class' => array('block-content')), 8);
            $table = array('id' => 'dt_msdir', 'ajaxsrc' => '/datatable.php',
                    'params' => array('inc' => 'dtMSDirectory.php', 'fnrow' => 'dtGetRow'),
                    'GET' => array('incldeceased'),
                    'drawcallback' => "dtDefDrawCallBack( oSettings );",
                    'description' => 'Membership Directory',
                    'responsive' => TRUE, 'hover' => FALSE, 'striped' => FALSE,
                    'columns' => array(
                        array('caption' => '', 'textalign' => 'center', 'fieldname' => 'Avatar', 'searchable' => FALSE, 'sortable' => FALSE),
                        array('caption' => 'Fullname', 'fieldname' => '_Sortname'),
                        array('caption' => 'Membership', 'fieldname' => 'MSText'),
                        array('caption' => 'Info', 'fieldname' => 'Info', 'sortable' => FALSE),
//                        array('caption' => 'Actions', 'searchable' => FALSE, 'sortable' => FALSE)
                    ),
                    'sortby' => array('column' => '_Sortname', 'direction' => 'asc')
            );
            LoadDatatable($table);
            Div(null, 8); //table content block
            Div(null, 7); //table block
            break;
        case 'table_committees':
            $menuitems = array();
            $menuitems[] = array('colour' => 'success', 'tooltip' => 'Create a new Committee', 'caption' => 'New Committee', 'script' => "OpenDialog( 'editcommittee', { large: false } )", 'icon' => 'fa-plus-square');
            stdTitleBlock('Committees', $do, $menuitems, TRUE);
            Div(array('class' => 'add-bottom-margin-std'), 9);
            $buttongroup = qaButtons('committees');
            ButtonGroup($buttongroup, FALSE, null, 10);
            Div(null, 9);
            $table = array('id' => 'dt_committees', 'ajaxsrc' => '/datatable.php',
                    'params' => array('inc' => 'dtCommittees.php', 'fnrow' => 'dtGetRow'),
                    'GET' => array(),
                    'drawcallback' => "dtDefDrawCallBack( oSettings );",
                    'responsive' => TRUE, 'hover' => FALSE, 'striped' => FALSE,
                    'columns' => array(
                        array('caption' => 'Name', 'fieldname' => 'CommitteeName'),
                        array('caption' => 'Chair', 'fieldname' => 'Chair', 'searchable' => FALSE, 'sortable' => FALSE, 'hide' => array('xs', 'sm')),
                        array('caption' => 'Members', 'fieldname' => 'MemberCount', 'textalign' => 'center', 'searchable' => FALSE, 'width' => '9em'),
                        array('caption' => 'Actions', 'searchable' => FALSE, 'sortable' => FALSE)
                    ),
                    'sortby' => array('column' => 'CommitteeName', 'direction' => 'asc')
            );
            Datatable($table, array(), 9);
            jsInitDatatable($table, TRUE, 9);
            Div(null, 8); //table content block
            Div(null, 7); //table block
            break;
        case 'table_groups':
            $menuitems = array();
            $menuitems[] = array('colour' => 'success', 'tooltip' => 'Create a new Group', 'caption' => 'New Group', 'script' => "OpenDialog( 'editpersongroup', { large: false } )", 'icon' => 'fa-plus-square');
            stdTitleBlock('Groups', $do, $menuitems, TRUE);
            Div(array('class' => 'add-bottom-margin-std'), 9);
            $buttongroup = qaButtons('groups');
            ButtonGroup($buttongroup, FALSE, null, 10);
            Div(null, 9);
            $table = array('id' => 'dt_groups', 'ajaxsrc' => '/datatable.php',
                'params' => array('inc' => 'dtGroups.php', 'fnrow' => 'dtGetRow'),
                'GET' => array(),
                'drawcallback' => "dtDefDrawCallBack( oSettings );",
                'responsive' => TRUE, 'hover' => FALSE, 'striped' => FALSE,
                'columns' => array(
                    array('caption' => 'Name', 'fieldname' => 'GroupName'),
                    array('caption' => 'Settings', 'fieldname' => 'Settings', 'searchable' => FALSE, 'sortable' => FALSE, 'hide' => array('xs', 'sm')),
                    array('caption' => 'Count', 'fieldname' => 'MemberCount', 'textalign' => 'center', 'searchable' => FALSE, 'width' => '9em'),
                    array('caption' => 'Actions', 'searchable' => FALSE, 'sortable' => FALSE)
                ),
                'sortby' => array('column' => 'GroupName', 'direction' => 'asc')
            );
            Datatable($table, array(), 9);
            jsInitDatatable($table, TRUE, 9);
            //Container for leading the membership list in
            Div(array('id' => 'groupBreakdownList'), 9);
            
            Div(null, 9); //table div
            Div(null, 8); //table content block
            Div(null, 7); //table block
            break;
        case 'table_grants':
            $menuitems = array();
            $menuitems[] = array('colour' => 'success', 'tooltip' => 'Define a new Grant', 'caption' => 'New Grant', 'script' => "OpenDialog( 'editgrant', { large: false } )", 'icon' => 'fa-plus-square');
            stdTitleBlock('Grants', $do, $menuitems, TRUE);
            Div(array('class' => 'add-bottom-margin-std'), 9);
            $buttongroup = qaButtons('grants');
            ButtonGroup($buttongroup, FALSE, null, 10);
            Div(null, 9);
            $table = array('id' => 'dt_grants', 'ajaxsrc' => '/datatable.php',
                'params' => array('inc' => 'dtGrants.php', 'fnrow' => 'dtGetRow'),
                'GET' => array(),
                'drawcallback' => "dtDefDrawCallBack( oSettings );",
                'responsive' => TRUE, 'hover' => FALSE, 'striped' => FALSE,
                'columns' => array(
                    array('caption' => 'Title', 'fieldname' => 'Title'),
                    array('caption' => 'Description', 'fieldname' => 'Description', 'hide' => array('xs', 'sm')),
                    array('caption' => 'Count', 'fieldname' => 'AwardCount', 'textalign' => 'center', 'searchable' => FALSE, 'width' => '9em'),
                    array('caption' => 'Actions', 'searchable' => FALSE, 'sortable' => FALSE)
                ),
                'sortby' => array('column' => 'Title', 'direction' => 'asc')
            );
            Datatable($table, array(), 9);
            jsInitDatatable($table, TRUE, 9);
            //Container for leading the membership list in
            Div(array('id' => 'grantBreakdownList'), 9);
            
            Div(null, 9); //table div
            Div(null, 8); //table content block
            Div(null, 7); //table block
            break;
        case 'part_ddjobbuttons':
            if (CheckRequiredParams(array('directdebitjobid' => FALSE), $_GET)) {
                $JOB = new crmDirectDebitJob('eventsFinance', $SYSTEM_SETTINGS['Database'], intval($_GET['directdebitjobid']), $SYSTEM_SETTINGS['Finance']['DirectDebit']);
                if($JOB->Found) {
                    if($JOB->Locked) {
                        AlertBox(array(
                            'type' => 'warning',
                            'title' => 'Please wait',
                            'items' => array(
                                array('type' => 'item', 'caption' => 'This job is locked while processing is taking place. Once the current operation has completed, the job will be unlocked.'),
                            ),
                        ), 9);
                    }
                    $reloadBtn = "function(){ LoadContent('divDDJobButtons', '/load.php?do=part_ddjobbuttons', { spinner: true, urlparams: objParams });  }";
                    $reloadBtnHist = "function(){ LoadContent('divDDJobButtons', '/load.php?do=part_ddjobbuttons', { spinner: true, urlparams: objParams }); LoadContent('wsSide', 'load.php?do=sidebar_ddjobhistory', { divid: 'sidebar_ddjobhistory', spinner: false, urlparams: objParams } ); }";
                    $buttongroup = array(
                        'editsettings' => array(
                            'icon' => 'fa-pencil', 'iconalign' => 'left', 'colour' => (empty($JOB->Job['Notified']) ? 'info' : 'warning'), 'caption' => 'Settings', 'tooltip' => 'Change description or planned submission date',
                            'visible' => empty($JOB->Job['Submitted']),
                            'script' => "OpenDialog('editddjobsettings', { large: true, urlparams: { directdebitjobid: {$JOB->DirectDebitJobID} } })",
                            'disabled' => $JOB->Locked
                        ),
                        'notifyemail' => array(
                            'icon' => 'gi-message_new', 'iconalign' => 'left', 'colour' => (empty($JOB->Job['EmailNotifications']) ? 'success' : 'warning'), 'type' => 'button', 'caption' => 'Email Notifications', 'tooltip' => 'Send Email Notifications',
                            'visible' => empty($JOB->Job['Submitted']),
                            'script' => "OpenDialog('startddemailnotification', { large: true, urlparams: { directdebitjobid: {$JOB->DirectDebitJobID} } })",
/*                            'script' => (empty($JOB->Job['EmailNotifications'])
                                         ? "execSyscall('/syscall.php?do=ddNotifyEmail', { parseJSON: true, defErrorDlg: true, postparams: { DirectDebitJobID: {$JOB->DirectDebitJobID} }, cbSuccess: {$reloadBtn} })"
                                         : "confirmExecSyscall('Notifications', '<b>Warning!</b> The email notifications for this job have already been sent. Are you sure you want to resend?', '/syscall.php?do=ddNotifyEmail', { parseJSON: true, defErrorDlg: true, postparams: { DirectDebitJobID: {$JOB->DirectDebitJobID} }, cbSuccess: {$reload} })"
                            ),*/
                            'disabled' => $JOB->Locked
                        ),
                        'pdfemail' => array(
                            'icon' => 'fa-print', 'iconalign' => 'left', 'colour' => (empty($JOB->Job['PDFNotifications']) ? 'success' : 'warning'), 'type' => 'button', 'caption' => 'PDF Notifications', 'tooltip' => 'Create PDF Notifications',
                            'visible' => empty($JOB->Job['Submitted']),
/*                            'script' => (empty($JOB->Job['EmailNotifications'])
                                         ? "execSyscall('/syscall.php?do=ddNotifyPDF', { parseJSON: true, defErrorDlg: true, postparams: { DirectDebitJobID: {$JOB->DirectDebitJobID} }, cbSuccess: {$reloadBtn} })"
                                         : "confirmExecSyscall('Notifications', '<b>Warning!</b> The pdf notifications for this job have already been generated and may have been posted. Are you sure you want to create the PDF notifications again?', '/syscall.php?do=ddNotifyPDF', { parseJSON: true, defErrorDlg: true, postparams: { DirectDebitJobID: {$JOB->DirectDebitJobID} }, cbSuccess: {$reload} })"
                            ),*/
                            'disabled' => $JOB->Locked
                        ),
                        'getsubmission' => array(
                            'icon' => 'gi-disk_save', 'iconalign' => 'left', 'colour' => (empty($JOB->Job['Notified']) || !empty($JOB->Job['Submitted']) ? 'warning' : 'success'), 'type' => 'button', 'caption' => 'Create Submission', 'tooltip' => 'Create the Submission file',
                            'visible' => empty($JOB->Job['ResultsProcessed']),
                            'script' => "OpenDialog('getddsubmission', { large: true, urlparams: { directdebitjobid: {$JOB->DirectDebitJobID} } })",
/*                            (empty($JOB->Job['Notified'])
                                         ? "confirmExecSyscall('Get Submission', '<b>Warning</b> You have not yet notified people of this pending direct debit run. You should notify people The submission file for this job has already been generated and you may therefore have already send it to your financial institution. If you continue, a new submission file will be generated.', '/syscall.php?do=ddgetsubmission', { parseJSON: true, defErrorDlg: true, postparams: { DirectDebitJobID: {$JOB->DirectDebitJobID} }, cbSuccess: {$reloadBtnHist} })"
                                         : (!empty($JOB->Job['Submitted'])
                                            ? "confirmExecSyscall('Get Submission', '<b>Warning</b> The submission file for this job has already been generated and you may therefore have already send it to your financial institution. If you continue, a new submission file will be generated.', '/syscall.php?do=ddgetsubmission', { parseJSON: true, defErrorDlg: true, postparams: { DirectDebitJobID: {$JOB->DirectDebitJobID} }, cbSuccess: {$reloadBtnHist} })"
                                            :
                            )),*/ 
                            'disabled' => $JOB->Locked
                        ),
                        'unlock' => array(
                            'icon' => 'fa-unlock-alt', 'iconalign' => 'left', 'colour' => 'warning', 'type' => 'button', 'caption' => 'Unlock', 'tooltip' => 'Manually unlock this job',
                            'visible' => $JOB->Locked && HasPermission('unlocking'),
                            'script' => "confirmExecSyscall('Unlock Job', '<b>Warning</b> Unlocking a job while processing takes place can cause the current operation to be aborted and may result in inconsistencies. <b>Do you want to unlock?</b>', '/syscall.php?do=setlock', { parseJSON: true, defErrorDlg: true, postparams: { DirectDebitJobID: {$JOB->DirectDebitJobID}, Locked: 0 }, cbSuccess: {$reloadBtnHist} })",
                            'disabled' => !$JOB->Locked
                        ),
                        'processresults' => array(
                            'icon' => 'gi-disk_save', 'iconalign' => 'left', 'colour' => (!empty($JOB->Job['ResultsProcessed']) ? 'warning' : 'success'), 'type' => 'button', 'caption' => 'Process Results', 'tooltip' => 'Process the Results for this job',
                            'visible' => (!empty($JOB->Job['Submitted']) && empty($JOB->Job['ResultsProcessed'])),
                            'script' => "OpenDialog('processddresults', { large: true, urlparams: { directdebitjobid: {$JOB->DirectDebitJobID} } })",
                            'disabled' => $JOB->Locked
                        ),
                    );
                    ButtonGroup($buttongroup, FALSE, null, 9);
                }
            }
            break;
        case 'part_ddsettings':
            if (CheckRequiredParams(array('directdebitjobid' => FALSE), $_GET)) {
                $JOB = new crmDirectDebitJob('eventsFinance', $SYSTEM_SETTINGS['Database'], intval($_GET['directdebitjobid']), $SYSTEM_SETTINGS['Finance']['DirectDebit']);
                if($JOB->Found) {
                    SimpleHeading('Settings', 4, 'sub', 10);
                    $datasource = array();
                    $datasource[] = array('caption' => 'Job created', 'value' => date('j M Y', strtotime($JOB->Job['Created'].' UTC')));
                    $datasource[] = array('caption' => 'Created by', 'value' => FmtText($JOB->Job['Fullname']));
                    $datasource[] = array('caption' => 'Description', 'value' => htmlspecialchars($JOB->Job['Description']));
//                    $datasource[] = array('caption' => 'Status', 'value' => AdvIcon(array('icon' => $JOB->Job['StatusIcon'], 'colour' => $JOB->Job['StatusColour'])).'&#8200;'.FmtText("<{$JOB->Job['StatusColour']}>".$JOB->Job['StatusText']."</{$JOB->Job['StatusColour']}>"));
                    StaticTable($datasource, $summaryTable, array(), 11);
                }
            }
            break;
        case 'part_ddsummary':
            if (CheckRequiredParams(array('directdebitjobid' => FALSE), $_GET)) {
                $JOB = new crmDirectDebitJob('eventsFinance', $SYSTEM_SETTINGS['Database'], intval($_GET['directdebitjobid']), $SYSTEM_SETTINGS['Finance']['DirectDebit']);
                if($JOB->Found) {
                    SimpleHeading('Summary', 4, 'sub', 10);
                    $summary = $JOB->Summary();
                    $datasource = array();
                    foreach(array('CountDDI' => 'Instruction Item', 'CountNewDDI' => 'New Instruction', 'CountCancelledDDI' => 'Cancelled Instruction',
                        'CountModifiedDDI' => 'Modified Instruction', 'CountCollection' => 'Collection Item') AS $key => $caption) {
                        if(!empty($summary[$key])) {
                            $datasource[] = array('caption' => $caption.($summary[$key]<>1 ? "s" : ""), 'value' => number_format($summary[$key], 0, '.', ','));
                        }
                    }
                    if($summary['CountCollection'] <> 0) {
                        $datasource[] = array('caption' => 'Total Value', 'value' => FmtText("<info><b>".ScaledIntegerAsString((!empty($JOB->Job['Submitted']) ? $summary['SubmittedValue'] : $summary['Outstanding']), "money", 100, FALSE, $summary['Symbol']).(!empty($JOB->Job['Submitted']) ? ' (as submitted)': '')."</b></info>"));
                    }
                    if(!empty($JOB->Job['EmailNotifications'])) {
                        $datasource[] = array('caption' => 'Email Notifications', 'value' => date('j M Y', strtotime($JOB->Job['EmailNotifications'].' UTC')));
                    }
                    if(!empty($JOB->Job['PDFNotifications'])) {
                        $datasource[] = array('caption' => 'PDF Notifications', 'value' => date('j M Y', strtotime($JOB->Job['PDFNotifications'].' UTC')));
                    }
                    if(!empty($JOB->Job['Submitted'])) {
                        $datasource[] = array('caption' => 'Submitted', 'value' => date('j M Y', strtotime($JOB->Job['Submitted'].' UTC')));
                    } else {
                        $datasource[] = array('caption' => 'Planned Submission', 'value' => date('j M Y', strtotime($JOB->Job['PlannedSubmission'].' UTC')));
                    }
                    if(!empty($JOB->Job['ResultsProcessed'])) {
                        $datasource[] = array('caption' => 'Completed', 'value' => date('j M Y', strtotime($JOB->Job['ResultsProcessed'].' UTC')));
                    }
                    StaticTable($datasource, $summaryTable, array(), 11);
                    if($JOB->Job['CanModify']) {
                        if($summary['CountDoNotContact'] <> 0) {
                            SimpleAlertBox('error', 'There '.SinPlu($summary['CountDoNotContact'], 'job item', 'are').' linked to records with <b>Do Not Contact</b> enabled. No notifications or other messages can be sent to these records.');
                        }
                        if($summary['CountDeceased'] <> 0) {
                            SimpleAlertBox('warning', 'There '.SinPlu($summary['CountDeceased'], 'job item', 'are').' linked to a record of a deceased person. These items will <b>not</b> be included in the submission file and no notifications will be sent for these records.');
                        }
                    }
                }
            }
            break;
        case 'record_ddjob':
            if (CheckRequiredParams(array('directdebitjobid' => FALSE), $_GET)) {
                $JOB = new crmDirectDebitJob('eventsFinance', $SYSTEM_SETTINGS['Database'], intval($_GET['directdebitjobid']), $SYSTEM_SETTINGS['Finance']['DirectDebit']);
                if($JOB->Found) {
                    $menuitems = array();
//                    $menuitems[] = array('colour' => 'primary', 'icon' => 'fa-print', 'url' => "/syscall.php?do=getpdf&invoiceid=".$INVOICE->Invoice['InvoiceID']);
//                    $menuitems[] = array('colour' => 'info', 'function' => 'toggle', 'icon' => 'fa-arrows-v', 'style' => 'alt');
                    stdTitleBlock('Direct Debit Job #'.$JOB->DirectDebitJobID, $do, $menuitems, TRUE);
                    CheckTZSupport(TRUE, 11);
                    Div(array('id' => 'divDDJobButtons'), 8); //Container for buttons
                    Div(null, 8);
                    Div(array('class' => array('row', 'block-section')), 8); //Settings and Summary row
                    Div(array('class' => 'col-md-6', 'id' => 'divDDSettings'), 9); //Settings column
                    Div(null, 9); //close settings column
                    Div(array('class' => 'col-md-6', 'id' => 'divDDSummary'), 9); //Summary column
                    Div(null, 9); //close settings column
                    Div(null, 8); //close settings and summary row
//                    Div(array('class' => array('row', 'block-section')), 8); //Transactions Row
                    SimpleHeading('Job Items', 4, 'sub', 8);
                    $table = array('id' => 'dt_ddjobitems', 'ajaxsrc' => '/datatable.php',
                        'params' => array('inc' => 'dtDDJobItems.php', 'fnrow' => 'dtGetRow'),
                        'GET' => array('directdebitjobid'),
                        'drawcallback' => "dtDefDrawCallBack( oSettings );",
                        'responsive' => TRUE, 'hover' => FALSE, 'striped' => FALSE,
                        'columns' => array(
                            array('caption' => '', 'fieldname' => 'ErrorText', 'textalign' => 'center', 'width' => '1em'),
                            array('caption' => '#', 'fieldname' => 'DirectDebitJobItemID', 'textalign' => 'center', 'width' => '5.5em'),
                            array('caption' => 'Type', 'fieldname' => 'JobItemTypeText'),
                            array('caption' => 'Name', 'fieldname' => '_Sortname'),
                            array('caption' => 'Description', 'fieldname' => 'JobItemDescription'),
                            array('caption' => 'Value', 'fieldname' => 'Outstanding'),
                            array('caption' => 'Actions', 'searchable' => FALSE, 'sortable' => FALSE)
                        ),
                        'sortby' => array('column' => 'DirectDebitJobItemID', 'direction' => 'asc')
                    );
                    Datatable($table, array(), 9);
                    jsInitDatatable($table, TRUE, 9);                    
//                    Div(null, 8); //close Transactions row
                    Div(null, 7); //close outer block
                }
            }
            break;
        case 'table_committeemembers':
            if (CheckRequiredParams(array('committeeid' => FALSE), $_GET)) {
                $COMMITTEE = new crmCommittee($SYSTEM_SETTINGS['Database'], intval($_GET['committeeid']));
                if($COMMITTEE->Found) {
                    $dateStr = (empty($_GET['fordate']) ? ', Current Constitution' : ' as per '.date('j F Y', strtotime($_GET['fordate'].' UTC')));
                    SimpleHeading('Members'.$dateStr, 4, 'sub', 9);   
                    $table = array('id' => 'dt_committeemembers', 'ajaxsrc' => '/datatable.php',
                        'params' => array('inc' => 'dtCommitteeMembers.php', 'fnrow' => 'dtGetRow'),
                        'GET' => array('committeeid', 'fordate'),
                        'drawcallback' => "dtDefDrawCallBack( oSettings );",
                        'description' => 'Members '.$COMMITTEE->Settings['CommitteeName'].$dateStr,
                        'responsive' => TRUE, 'hover' => FALSE, 'striped' => FALSE,
                        'columns' => array(
                            array('caption' => 'Name', 'fieldname' => '_Sortname', 'width' => '30%'),
                            array('caption' => 'Role', 'fieldname' => 'Role'),
                            array('caption' => 'Membership', 'fieldname' => 'MSText', 'hide' => array('xs')),
                            array('caption' => 'Term', 'fieldname' => 'Term', 'width' => '12em'),
                            array('caption' => 'Actions', 'searchable' => FALSE, 'sortable' => FALSE)
                        ),
                        'sortby' => array('column' => '_Sortname', 'direction' => 'asc')
                    );
                    LoadDatatable($table);
                }
            }
            break;
        case 'record_committee':
            if (CheckRequiredParams(array('committeeid' => FALSE), $_GET)) {
                $COMMITTEE = new crmCommittee($SYSTEM_SETTINGS['Database'], intval($_GET['committeeid']));
                if($COMMITTEE->Found) {
                    $menuitems = array();
                    stdTitleBlock($COMMITTEE->Settings['CommitteeName'], $do, $menuitems, TRUE);
                    if(!empty($COMMITTEE->Settings['Description'])) {
                        SimpleHeading($COMMITTEE->Settings['Description'], 5, 'default', 8);
                    }
                    Div(array('class' => 'add-bottom-margin-std', 'id' => 'divButtons'), 8);
                    $buttongroup = array();
                    $buttongroup[] = array(
                        'iconalign' => 'left', 'caption' => 'Committee Settings', 'icon' => 'fa-pencil', 'colour' => 'info', 'iconalign' => 'left',
                        'script' => "OpenDialog( 'committeesettings', { large: false, urlparams: { CommitteeID: {$COMMITTEE->CommitteeID} } })"
                    );
                    $buttongroup[] = array(
                        'iconalign' => 'left', 'caption' => 'Show for Date', 'icon' => 'fa-calendar', 'colour' => 'info', 'iconalign' => 'left',
                        'script' => "OpenDialog( 'selectcommitteedate', { urlparams: { CommitteeID: {$COMMITTEE->CommitteeID} } })"
                    );
                    $buttongroup[] = array(
                        'iconalign' => 'left', 'caption' => 'Show current Constitution', 'icon' => 'fa-calendar-check-o', 'colour' => 'success', 'iconalign' => 'left',
                        'script' => "LoadContent('divCommitteePeople', '/load.php?do=table_committeemembers', { spinner: true, urlparams: { committeeid: {$COMMITTEE->CommitteeID} } });"
                    );
                    ButtonGroup($buttongroup, FALSE, null, 9);
                    Div(null, 8);
                    Div(array('id' => 'divCommitteePeople'), 8);
                    
                    Div(null, 8);
                    Div(null, 7); //close outer block
                }
            }
            break;
        case 'sidebar_invmonies':
            if (CheckRequiredParams(array('invoiceid' => FALSE), $_GET)) {
                $INVOICE = new crmInvoice('eventsFinance', $SYSTEM_SETTINGS['Database'], intval($_GET['invoiceid']), InvoiceSettings());
                if($INVOICE->Found) {
                    stdTitleBlock('Monies', $do, stdSidebarMenuitems());
                    Div(array('class' => array('block-content')), 8);
                    $sql = 
                    "SELECT tblmoneytoinvoice.MoneyToInvoiceID, tblmoneytoinvoice.AllocatedAmount, tblmoney.MoneyID, tblmoney.TransactionTypeID, 
	                        tbltransactiontype.TransactionType, tblmoney.Received, tblmoney.ReceivedAmount, tblmoney.ReceivedFrom, 
                            tblmoney.TransactionReference, tblmoney.AddInfo, tblmoney.Reversed,
	                        tblcurrency.ISO4217, tblcurrency.Currency, tblcurrency.`Decimals`, tblcurrency.Symbol
                     FROM tblmoneytoinvoice
                     INNER JOIN tblmoney ON (tblmoney.MoneyID = tblmoneytoinvoice.MoneyID)
                     LEFT JOIN tblcurrency ON tblcurrency.ISO4217 = tblmoney.ISO4217
                     LEFT JOIN tbltransactiontype ON tbltransactiontype.TransactionTypeID = tblmoney.TransactionTypeID
                     WHERE tblmoneytoinvoice.InvoiceID = {$INVOICE->InvoiceID}";
                    $qry = mysqli_query($SYSTEM_SETTINGS["Database"], $sql);
                    $datasource = mysqli_fetch_all($qry, MYSQLI_ASSOC); 
                    $moniesTable = array(
                        'header' => FALSE,
                        'striped' => FALSE,
                        'condensed' => TRUE,
                        'borders' => 'none',
                        'responsive' => FALSE,
                        'valign' => 'centre',
                        'margin' => FALSE,
                        'larger' => TRUE,
                        'columns' => array(
                            array(
                                'field' => array('name' => 'direction', 'type' => 'string'),
                                'html' => TRUE, 'function' => 'MoniesTableItem', 'textalign' => 'center'
                            ),
                            array(
                                'field' => array('name' => 'date', 'type' => 'string'),
                                'html' => TRUE, 'function' => 'MoniesTableItem'
                            ),
                            array(
                                'field' => array('name' => 'value', 'type' => 'string'),
                                'html' => TRUE, 'function' => 'MoniesTableItem'
                            ),
                            array(
                                'field' => array('name' => 'info', 'type' => 'string'),
                                'html' => TRUE, 'function' => 'MoniesTableItem'
                            ),
                        ),
                    );
                    StaticTable($datasource, $moniesTable, array(), 11);
                    Div(null, 8); //content block
                    Div(null, 7); //container div            
                }
            }
            break;
        case 'record_invoice':
            if (CheckRequiredParams(array('invoiceid' => FALSE), $_GET)) {
                $INVOICE = new crmInvoice('eventsFinance', $SYSTEM_SETTINGS['Database'], intval($_GET['invoiceid']), InvoiceSettings());
                $customerurl = (!empty($INVOICE->Invoice['PersonID']) ? "/record.php?rec=person&personid={$INVOICE->Invoice['PersonID']}" : "/record.php?rec=organisation&organisationid={$INVOICE->Invoice['OrganisationID']}");                
                if($INVOICE->Found) {
                    $invTable = array(
                        'header' => FALSE,
                        'striped' => FALSE,
                        'condensed' => TRUE,
                        'borders' => 'none',
                        'responsive' => FALSE,
                        'valign' => 'centre',
                        'margin' => FALSE,
                        'columns' => array(
                            array(
                                'field' => array('name' => 'caption', 'type' => 'string'),
                                'formatted' => TRUE, 'bold' => TRUE, 'class' => 'sideBarCaption'
                            ),
                            array(
                                'field' => array('name' => 'value', 'type' => 'string'),
                                'html' => TRUE
                            ),
                        ),
                    );
                    $menuitems = array();
                    if(($INVOICE->Invoice['InvoiceType'] == 'creditnote') && ($INVOICE->ProForma == TRUE)) {
                        $menuitems[] = array('group' => 'full', 'colour' => 'success', 'icon' => 'fa-check', 'tooltip' => 'Close',
                        'script' => "confirmExecSyscall('Close Document', 'Are you sure you want to close this document? This action cannot be undone.', '/syscall.php?do=closeInvoice', { parseJSON: true, defErrorDlg: true, postparams: { InvoiceID: {$INVOICE->InvoiceID} }, cbSuccess: function(){ window.location.reload(); } })",
                        'disabled' => $INVOICE->Settled);
                    }
                    $menuitems[] = array('group' => 'full', 'colour' => 'info', 'icon' => 'fa-print', 'tooltip' => 'Download as PDF', 'url' => "/syscall.php?do=getpdf&invoiceid=".$INVOICE->Invoice['InvoiceID']);
                    if(!empty($customerurl)) {
                        $menuitems[] = array(
                            'group' => 'toponly', 'colour' => 'info',
                            'icon' => (!empty($INVOICE->Invoice['PersonID']) ? 'gi-user' : 'gi-building'),
                            'tooltip' => 'Go to '.(!empty($INVOICE->Invoice['PersonID']) ? 'Person' : 'Organisation').' record',
                            'url' => $customerurl,
                            'target' => (empty($AUTHENTICATION['Settings']['OpenNewWindow']) ? null : 'newwindow')
                        );
                    }
                    $menuitems[] = array('group' => 'toponly', 'colour' => ($INVOICE->Invoice['Draft'] ? 'primary' : 'warning'), 'icon' => 'fa-plus-square', 'tooltip' => 'Add line item', 'script' => "OpenDialog( 'editinvoiceitem', { large: true, urlparams: { InvoiceID: {$INVOICE->InvoiceID}, New: true } } )");
                    if($INVOICE->Invoice['InvoiceType'] != 'creditnote') {
                        if($INVOICE->ProForma == FALSE) {
                            $menuitems[] = array(
                                'group' => 'full', 'id' => 'btnCredit', 'colour' => 'warning', 'icon' => 'gi-restart', 'tooltip' => 'Credit selected Items',
//                              'script' => "var selItems = GetAllSelectedItems(); selItems.each(function(){ console.log($(this).data('invoiceitemid')) }); ",
                                'script' => "execSyscall('/syscall.php?do=requestcredit', { parseJSON: true, defErrorDlg: true, postparams: { origin: 'invoice', invoiceid: {$INVOICE->Invoice['InvoiceID']}, invoiceitemids: GetDataFromAllSelectedItems('invoiceitemid') }, cbSuccess: function( response ){ window.location.href=response.continueurl; } });",
                                'disabled' => TRUE
                            );
                        }
                        $menuitems[] = array('group' => 'full', 'colour' => 'success', 'icon' => 'fa-credit-card', 'tooltip' => 'Process Payment', 'script' => "OpenDialog('addmoneyInvoice', { large: true, urlparams: { invoiceid: {$INVOICE->Invoice['InvoiceID']} } });", 'disabled' => $INVOICE->Settled);
                    }
                    //$menuitems[] = array('colour' => 'info', 'function' => 'toggle', 'icon' => 'fa-arrows-v', 'style' => 'alt');
                    stdTitleBlock($INVOICE->Invoice['InvoiceCaption'], $do, $menuitems, TRUE);
                    if($INVOICE->Invoice['Draft'] && ($INVOICE->Invoice['InvoiceType'] == 'creditnote')) {
                        SimpleAlertBox('info', 'This is a '.$INVOICE->Invoice['InvoiceCaption'].'. You can make further edits to this document. Once you have completed your edits, make sure to <b>close</b> this document so that it becomes permanent and recorded in accounts.', 8);
                    }
                    Div(array('class' => array('row', 'block-section')), 8); //header row
                    Div(array('class' => array('col-sm-5')), 9); //left column header row
                    Div(array('class' => array('block')), 10); //invoice identification block
                        if($INVOICE->Invoice['Draft'] && ($INVOICE->Invoice['InvoiceType'] == 'invoice')) {
                            Label(array(
                                'caption' => '&#8192;This is not a VAT Invoice&#8192;',
                                'type' => 'label',
                                'colour' => 'warning',
                                'class' => 'smallcaps'
                            ), 10);
                        }
                        $datasource = array(
                            array('caption' => 'Customer No', 'value' => LinkTo($INVOICE->Invoice['CustNo'], array('url' => $customerurl, 'target' => (empty($AUTHENTICATION['Settings']['OpenNewWindow']) ? null : 'newwindow')))),
                            array('caption' => $INVOICE->Invoice['InvoiceTypeText'].' Date', 'value' => date('j F Y', strtotime($INVOICE->Invoice['InvoiceDate'].' UTC'))),
                        );
                        if(($INVOICE->Invoice['InvoiceType'] == 'invoice') && (!$INVOICE->Invoice['Draft'])) {
                            $datasource[] = array('caption' => $INVOICE->Invoice['InvoiceTypeText'].' Due', 'value' => date('j F Y', strtotime($INVOICE->Invoice['InvoiceDue'].' UTC')));
                        }
                        $datasource[] = array('caption' => 'Customer  Reference', 'value' => $INVOICE->Invoice['CustomerRef']);
                        StaticTable($datasource, $invTable, array(), 11);
                    Div(null, 10); //close invoice identification block
                    
                    Div(null, 9); //close left column header row
                    Div(array('class' => array('col-sm-2')), 9); //middle column header row
                    Div(null, 9); //close middle column header row
                    Div(array('class' => array('col-sm-5')), 9); //right column header row
                    Div(null, 9); //close right column header row
                    Div(null, 8); //close header row
                    Div(array('class' => array('row', 'block-section')), 8); //to/from row
                    Div(array('class' => array('col-sm-5')), 9); //left column to/from row
                    Div(array('class' => array('block')), 10);
                        BlockTitle(array('caption' => 'To'), 11);
                        $lines = explode("\n", $INVOICE->Invoice['InvoiceTo']);
                        if(!empty($INVOICE->Invoice['VATNumber'])) {
                            $line[] = '<b>VAT Number: </b>'.$INVOICE->Invoice['VATNumber'];
                        }
                        OutputLines($lines, $customerurl);
                    Div(null, 10); 
                    Div(null, 9); //close left column to/from row
                    Div(array('class' => array('col-sm-2')), 9); //middle column to/from row
                    Div(null, 9); //close middle column to/from row
                    Div(array('class' => array('col-sm-5')), 9); //right column to/from row
                    Div(array('class' => array('block')), 10);
                        BlockTitle(array('caption' => 'From'), 11);
                        $lines = explode("\n", $INVOICE->Invoice['InvoiceFrom']);
                        if(!empty($INVOICE->Invoice['VATNumber'])) {
                            $line[] = '<b>VAT Number: </b>'.$INVOICE->Invoice['VATNumber'];
                        }
                        OutputLines($lines);
                    Div(null, 10);
                    Div(null, 9); //close right column to/from row
                    Div(null, 8); //close to/from row
//                    Div(array('class' => array('row')), 8); //invoice body
                        Div(array('class' => 'table-responsive'), 9); //table container
                        echo str_repeat("\t", 10)."<table class=\"table table-vcenter\">\n";
                        echo str_repeat("\t", 11)."<thead>\n";
                        echo str_repeat("\t", 12)."<tr>\n";
                        echo str_repeat("\t", 13)."<th style=\"width: 2em;\"></th>\n";
                        echo str_repeat("\t", 13)."<th></th>\n";
                        echo str_repeat("\t", 13)."<th style=\"width: 60%;\">Description</th>\n";
                        echo str_repeat("\t", 13)."<th class=\"text-center\">VAT Rate</th>\n";
                        echo str_repeat("\t", 13)."<th class=\"text-right\">Cost</th>\n";
                        echo str_repeat("\t", 12)."</tr>\n";
                        echo str_repeat("\t", 11)."</thead>\n";
                        echo str_repeat("\t", 11)."<tbody>\n";
                        $items = $INVOICE->Items;
                        $itemno = 0;
                        $buttongroup = array(
                            'buttons' => array(
                                'edit' => array(
                                    'group' => 'draft', 'type' => 'button', 'icon' => 'fa-pencil', 'ttplacement' => 'right', 'tooltip' => 'Edit this item', 'colour' => 'info',
                                    'script' => "OpenDialog( 'editinvoiceitem', { large: true, urlparams: { InvoiceItemID: %InvoiceItemID% } } )",
                                ),
/*                                'credit' => array(
                                    'group' => 'final', 'type' => 'button', 'icon' => 'gi-restart', 'ttplacement' => 'right', 'tooltip' => 'Reverse this item', 'colour' => 'danger',
                                    'script' => "OpenDialog( 'reverseinvoiceitem', { large: true, urlparams: { InvoiceItemID: %InvoiceItemID% } } )",
                                ),*/
                                'select' => array(
                                    'group' => 'final', 'type' => 'button', 'icon' => 'gi-unchecked', 'ttplacement' => 'right', 'tooltip' => 'Select this item', 'colour' => 'muted', 'style' => 'alt',
                                    'script' => "ToggleSelect( $(this), function( newState ){ var selCount = GetSelectedItemCount(); $('[id^=btnCredit]').each(function(){ SetDisabled(this, (selCount >= 1 ? false : true)) }); } );",
                                    'data' => array('invoiceitemid' => '%InvoiceItemID%'),
                                    'visible' => ($INVOICE->Invoice['InvoiceType'] != 'creditnote')
                                ),
                                'del' => array(
                                    'group' => 'draft', 'type' => 'button', 'icon' => 'fa-times', 'ttplacement' => 'right', 'tooltip' => 'Remove this item', 'colour' => 'danger',
                                    'script' => "confirmExecSyscall('Delete Item', '<b>The normal process for removing invoice items is by amending or cancelling the corresponding request or transaction.</b> Are you sure you want to delete this invoice item? This will stop automatic processing for any related transactions or requests.', '/syscall.php?do=delinvoiceitem', { parseJSON: true, defErrorDlg: true, postparams: { InvoiceItemID: %InvoiceItemID% }, cbSuccess: function(){ LoadContent('wsMain', '/load.php?do=record_invoice', { spinner: true, urlparams: ".OutputArrayAsJSObject($_GET)." }); } });",
                                ),
                            ),
                            'sizeadjust' => -2,
                            'vertical' => TRUE,
                            'inline' => TRUE,
                            'groupfilter' => ($INVOICE->Invoice['Draft'] ? 'draft' : 'final'),
                        );
                        foreach($items AS $item) {
                            echo str_repeat("\t", 12)."<tr>\n";
                            $bgroup = $buttongroup;
                            UpdateItemsParamsFromFields($item, $bgroup['buttons']);                            
                            echo str_repeat("\t", 13)."<td>".AdvButtonGroup($bgroup, array(), 0)."</td>\n";
                            if(($item['ItemTotal'] == 0) && empty($item['InvoiceItemTypeID']) && empty($item['LinkedID'])) {
                                echo str_repeat("\t", 13)."<td colspan=\"3\"></td>\n";
                            } else {
                                $itemno++;
                                echo str_repeat("\t", 13)."<td class=\"text-center\">".htmlspecialchars($itemno.'.')."</td>\n";
                                $desc = $item['Description'];
                                echo str_repeat("\t", 13)."<td><h4>".FmtText($item['Description'])."</h4>";
                                if($item['ItemQty'] > 1) {
                                    echo $item['ItemQty']."x ".ScaledIntegerAsString($item['ItemUnitPrice'], "money", 100, TRUE, $item['Symbol']);
                                }
                                if(!empty($item['DiscountID'])) {
                                    echo Label(array(
                                        'type' => 'label',
                                        'colour' => 'info',
                                        'caption' => "&#8192;Discount code ".$item['DiscountCode']." (".$item['Discount'].")&#8192;",
                                    ), 0, TRUE);
                                }
                                echo "</td>\n";
                                echo str_repeat("\t", 13)."<td class=\"text-center\">".ScaledIntegerAsString($item['ItemVATRate'], "percent", 100, TRUE)."</td>\n";
                                echo str_repeat("\t", 13)."<td class=\"text-right\"><h4>".ScaledIntegerAsString($item['ItemNet'], "money", 100, FALSE, $item['Symbol'])."</h4></td>\n";
                            }
                            echo str_repeat("\t", 12)."</tr>\n";
                        }
                        echo str_repeat("\t", 12)."<tr class=\"active\">\n";
                        echo str_repeat("\t", 13)."<td colspan=\"4\" class=\"text-right\"><span class=\"h4\">SUBTOTAL</span></td>\n";
                        echo str_repeat("\t", 13)."<td class=\"text-right\"><span class=\"h4\">".ScaledIntegerAsString($INVOICE->Invoice['Net'], "money", 100, FALSE, $INVOICE->Invoice['Symbol'])."</span></td>\n";
                        echo str_repeat("\t", 12)."</tr>\n";
                        echo str_repeat("\t", 12)."<tr class=\"active\">\n";
                        echo str_repeat("\t", 13)."<td colspan=\"4\" class=\"text-right\"><span class=\"h4\">VAT".($INVOICE->Invoice['InvoiceType'] == 'invoice' ? " DUE" : "")."</span></td>\n";
                        echo str_repeat("\t", 13)."<td class=\"text-right\"><span class=\"h4\">".ScaledIntegerAsString($INVOICE->Invoice['VAT'], "money", 100, FALSE, $INVOICE->Invoice['Symbol'])."</span></td>\n";
                        echo str_repeat("\t", 12)."</tr>\n";
                        echo str_repeat("\t", 12)."<tr class=\"active\">\n";
                        echo str_repeat("\t", 13)."<td colspan=\"4\" class=\"text-right\"><span class=\"h4\"><strong>TOTAL".(($INVOICE->Invoice['InvoiceType'] == 'invoice') ? ($INVOICE->Invoice['Settled'] ? " PAID" : " DUE") : "")."</strong></span></td>\n";
                        echo str_repeat("\t", 13)."<td class=\"text-right\"><span class=\"h4\"><strong>".ScaledIntegerAsString($INVOICE->Invoice['Total'], "money", 100, FALSE, $INVOICE->Invoice['Symbol'])."</strong></span></td>\n";
                        echo str_repeat("\t", 12)."</tr>\n";
                        if($INVOICE->Invoice['AllocatedAmount'] <> 0) {
                            echo str_repeat("\t", 12)."<tr class=\"active\">\n";
                            echo str_repeat("\t", 13)."<td colspan=\"4\" class=\"text-right\"><span class=\"h4\"><strong>".(($INVOICE->Invoice['InvoiceType'] == 'invoice') ? "RECEIVED" : "REMITTED")."</strong></span></td>\n";
                            echo str_repeat("\t", 13)."<td class=\"text-right\"><span class=\"h4\">".ScaledIntegerAsString(-$INVOICE->Invoice['AllocatedAmount'], "money", 100, FALSE, $INVOICE->Invoice['Symbol'])."</span></td>\n";
                            echo str_repeat("\t", 12)."</tr>\n";
//                            if($INVOICE->Invoice['Outstanding'] <> 0) {
                                echo str_repeat("\t", 12)."<tr class=\"active\">\n";
                                echo str_repeat("\t", 13)."<td colspan=\"4\" class=\"text-right\"><span class=\"h4\"><strong>".($INVOICE->Invoice['Outstanding'] < 0 ? "CREDIT" : "REMAINDER")."</strong></span></td>\n";
                                echo str_repeat("\t", 13)."<td class=\"text-right\"><span class=\"h4\"><strong>".ScaledIntegerAsString($INVOICE->Invoice['Outstanding'], "money", 100, FALSE, $INVOICE->Invoice['Symbol'])."</strong></span></td>\n";
                                echo str_repeat("\t", 12)."</tr>\n";
//                            }
                        }
                        $buttongroup = array(
                            'buttons' => array(
                                'edit' => array(
                                    'group' => 'draft', 'type' => 'button', 'icon' => 'fa-pencil', 'ttplacement' => 'right', 'tooltip' => 'Edit this item', 'colour' => 'info',
                                    'script' => "OpenDialog( 'editinvoiceitem', { large: true, urlparams: { InvoiceID: {$INVOICE->InvoiceID}, Field: 'AddInfo' } } )",
                                ),
                            ),
                            'sizeadjust' => -2,
                            'vertical' => TRUE,
                            'inline' => TRUE,
                            'groupfilter' => ($INVOICE->Invoice['Draft'] ? 'draft' : 'final'),
                        );
                        echo str_repeat("\t", 12)."<tr>\n";
                        echo str_repeat("\t", 13)."<td>".AdvButtonGroup($buttongroup, array(), 0)."</td>\n";
                        echo str_repeat("\t", 13)."<td colspan=\"4\"><h5>".FmtText((empty($INVOICE->Invoice['AddInfo']) ? "<muted><i>(empty)</i></muted>" : $INVOICE->Invoice['AddInfo']))."</h5></td>\n";
                        echo str_repeat("\t", 12)."</tr>\n";
                        if(($INVOICE->Invoice['InvoiceType'] == 'invoice') && !$INVOICE->Invoice['Settled']) {
                            echo str_repeat("\t", 12)."<tr>\n";
                            //echo str_repeat("\t", 13)."<td></td>\n";
                            $buttongroup['buttons']['edit']['script'] = "OpenDialog( 'editinvoiceitem', { large: true, urlparams: { InvoiceID: {$INVOICE->InvoiceID}, Field: 'Payable' } } )";
                            echo str_repeat("\t", 13)."<td>".AdvButtonGroup($buttongroup, array(), 0)."</td>\n";
                            echo str_repeat("\t", 13)."<td colspan=\"4\">".FmtText($INVOICE->Invoice['Payable'])."</td>\n";
                            echo str_repeat("\t", 12)."</tr>\n";
                        }
                        echo str_repeat("\t", 12)."<tr>\n";
                        echo str_repeat("\t", 13)."<td></td>\n";
                        //echo str_repeat("\t", 13)."<td>".AdvButtonGroup($buttongroup, array(), 0)."</td>\n";
                        echo str_repeat("\t", 13)."<td colspan=\"4\">".FmtText($INVOICE->Invoice['Terms'])."</td>\n";
                        echo str_repeat("\t", 12)."</tr>\n";
                        echo str_repeat("\t", 11)."</tbody>\n";
                        echo str_repeat("\t", 10)."</table>\n";
                        Div(null, 9); //table container
                        $group = MenuToAdvButtonGroup($menuitems, array('groupfilter' => 'full'));
                        //print_r($group);
                        AdvButtonGroup($group, array(), 9);
//                    Div(null, 8); //close invoice body row
                    Div(null, 7); //close block
                }
            }
            break;
        case 'record_reversal':
            if (CheckRequiredParams(array('invoiceitemids' => FALSE, 'origin' => FALSE), $_GET)) {
                switch($_GET['origin']) {
                    case 'invoice':
                        $sourceurl = "/record.php?rec=invoice&invoiceid=".intval($_GET['invoiceid']);
                        break;
                }
                $menuitems = array();
                $menuitems['cancel'] = array('group' => 'full', 'colour' => 'warning', 'icon' => 'gi-ban', 'caption' => 'Cancel', 'tooltip' => 'Cancel, return to '.$_GET['origin'], 'url' => $sourceurl);
                stdTitleBlock('Credit items', $do, $menuitems, TRUE);
                AlertBox(
                    array(
                        'type' => 'info',
                        'title' => '<b>Transaction Reversals</b>',
                        'canhide' => TRUE,
                        'items' => array(
                            array(
                                'type' => 'item',
                                'caption' => '<b>Note:</b> You have selected (an) item(s) to credit that is transactional. When marked as paid, automated processing takes place that may change the status of a record, membership etc. You can use the controls below to reverse these transactions (to some degree). Please note that certain complex reversals may need to be done manually, especially if substantial time has passed between the original transaction and the reversal.'
                            )
                        )
                    )
                );
                $invoiceitemids = explode(',', $_GET['invoiceitemids']);
                $fieldsets = array();
                $datasource = array();
                $statuses = new crmMSStatus($SYSTEM_SETTINGS['Database']);
                foreach($invoiceitemids AS $invoiceitemid) {
                    $invoicedata = InvoiceItemToInvoice($invoiceitemid);
                    $INVOICE = new crmInvoice('eventsFinance', $SYSTEM_SETTINGS['Database'], $invoicedata['InvoiceID'], InvoiceSettings());
                    $invoiceitem = $INVOICE->InvoiceItem($invoiceitemid);
                    $PERSON = new crmPerson('eventsPerson', $SYSTEM_SETTINGS['Database'], $invoiceitem['PersonID'], $SYSTEM_SETTINGS["Membership"]);
                    $analysis = $PERSON->AnalyseMSHistory();
                    $fields = array();
                    $fields[] = array('name' => 'invoiceitemids[]', 'kind' => 'hidden', 'value' => $invoiceitemid);
                    $fields[] = array('name' => 'mnemonic_'.$invoiceitemid, 'kind' => 'hidden', 'value' => $invoiceitem['Mnemonic']);
                    if($invoiceitem['ReqUserIntervention']) {
                        switch($invoiceitem['Mnemonic']) {
                            case 'ms_new':
                                $options = array(0 => 'No change to Membership status');
                                if((count($analysis['Items']) == 1) && (!empty($analysis['CurrentMS']['MSID']))) {
                                    $options['clear'] = 'Revert to Not a Member';
                                }
                                if(!empty($analysis['LastEnd']['MSID'])) {
                                    $options[$analysis['LastEnd']['MSID']] = 'Revert to '.$analysis['LastEnd']['MSStatusCaption'];
                                } elseif(!empty($analysis['CurrentMS']['MSID'])) {
                                    $lapsed = $statuses->GetStatusByFlag('lapsed');
                                    if(!empty($lapsed)) {
                                        $options['lapsed'] = 'Change status to '.$lapsed['MSStatusCaption'];
                                    }
                                }
                                $fields[] = array(
                                    'name' => 'decision_'.$invoiceitemid, 'caption' => 'Membership Status', 'kind' => 'control', 'type' => 'combo',
                                    'introtext' => '#'.$invoiceitem['InvoiceItemID'].', '.$analysis['Fullname'].': '.$invoiceitem['Description'].', '.ScaledIntegerAsString($invoiceitem['ItemTotal'], "money", 100, TRUE, $invoiceitem['Symbol']),
                                    'options' => $options
                                );
                                break;
                            case 'ms_renewal':
                                $options = array(0 => 'No change to Membership status');
                                $lapsed = $statuses->GetStatusByFlag('lapsed');
                                if(!empty($lapsed)) {
                                    $options['lapsed'] = 'Change status to '.$lapsed['MSStatusCaption'];
                                }
/*                                if(!empty($analysis['LastEnd']['MSID'])) {
                                    $options[$analysis['LastEnd']['MSID']] = 'Revert to '.$analysis['LastEnd']['MSStatusCaption'];
                                }*/
                                $fields[] = array(
                                    'name' => 'decision_'.$invoiceitemid, 'caption' => 'Membership Status', 'kind' => 'control', 'type' => 'combo',
                                    'introtext' => '#'.$invoiceitem['InvoiceItemID'].', '.$analysis['Fullname'].': '.$invoiceitem['Description'].', '.ScaledIntegerAsString($invoiceitem['ItemTotal'], "money", 100, TRUE, $invoiceitem['Symbol']),
                                    'options' => $options
                                );
                                $fields[] = array(
                                    'name' => 'MSNextRenewal', 'caption' => 'Next Renewal', 'kind' => 'control', 'type' => 'date', 'allowempty' => FALSE, 'required' => TRUE, 'hint' => 'Enter a new renewal date'                                    
                                );
                                $personal = $PERSON->GetRecord('personal', TRUE);
                                $datasource['MSNextRenewal'] = date('Y-m-d', strtotime($personal['MSNextRenewal'].' -1 YEAR'));
                                break;
                            case 'ms_rejoin':
                                $options = array(0 => 'No change to Membership status');
                                if(!empty($analysis['LastEnd']['MSID'])) {
                                    $options[$analysis['LastEnd']['MSID']] = 'Revert to '.$analysis['LastEnd']['MSStatusCaption'];
                                }
                                $fields[] = array(
                                    'name' => 'decision_'.$invoiceitemid, 'caption' => 'Membership Status', 'kind' => 'control', 'type' => 'combo',
                                    'introtext' => '#'.$invoiceitem['InvoiceItemID'].', '.$analysis['Fullname'].': '.$invoiceitem['Description'].', '.ScaledIntegerAsString($invoiceitem['ItemTotal'], "money", 100, TRUE, $invoiceitem['Symbol']),
                                    'options' => $options
                                );
                                break;
                            case 'ms_transfer':
                                $options = array(0 => 'No change to Membership status');
                                if(!empty($analysis['PreviousMS']['MSID']) && empty($analysis['PreviousMS']['ComesAfterEnd'])) {
                                    $options[$analysis['PreviousMS']['MSID']] = 'Revert to '.$analysis['PreviousMS']['GradeCaption'];
                                }
                                $fields[] = array(
                                    'name' => 'decision_'.$invoiceitemid, 'caption' => 'Membership Status', 'kind' => 'control', 'type' => 'combo',
                                    'introtext' => '#'.$invoiceitem['InvoiceItemID'].', '.$analysis['Fullname'].': '.$invoiceitem['Description'].', '.ScaledIntegerAsString($invoiceitem['ItemTotal'], "money", 100, TRUE, $invoiceitem['Symbol']),
                                    'options' => $options
                                );
                                break;
                        }
                    } else {
                        
                    }
                    $fieldsets[] = array(
                        'fields' => $fields
                    );
                }
                $buttons = DefFormButtons("Proceed");
                $buttons[] = array('type' => 'button', 'id' => 'btncancel', 'icon' => $menuitems['cancel']['icon'], 'iconalign' => 'left', 'colour' => 'warning', 'caption' => $menuitems['cancel']['caption'], 'url' => $menuitems['cancel']['url']);
                $formitem = array(
                    'id' => 'frmReversal', 'style' => 'vertical',
                    'onsubmit' => "submitForm( 'frmReversal', '/syscall.php?do=execreversal', { parseJSON: true, defErrorDlg: true, cbSuccess: function(frmElement, jsonResponse){ window.location.href=jsonResponse.continueurl; } } ); return false;",
                    'datasource' => $datasource, 'buttons' => $buttons,
                    'fieldsets' => $fieldsets, 'borders' => TRUE
                );
                Form($formitem);
            }
            break;
        case 'record_person':
            if (CheckRequiredParams(array('personid' => FALSE), $_GET)) {
                if(!isset($PERSON)) {
                    $PERSON = new crmPerson('eventsPerson', $SYSTEM_SETTINGS['Database'], intval($_GET['personid']), $SYSTEM_SETTINGS["Membership"]);
                }
                $personal = $PERSON->GetRecord('personal');
                //print_r($personal);
                $menuitems = array();
                $dp = PriorityDisplay($personal['WorkflowStatus']);
                $menuitems[] = array('colour' => $dp['colour'], 'script' => (is_null($personal['WorkflowStatus']) ? "OpenDialog( '".(is_null($personal['WorkflowStatus'])? 'addworkflow' : 'editrecordworkflow')."', { urlparams: { PersonID: {$PERSON->PersonID} } }  )": "$('[href=#tab-workflow]').tab('show');"), 'icon' => $dp['icon'], 'tooltip' => (is_null($personal['WorkflowStatus']) ? "Add to workflow" : "View workflow for this record"), 'disabled' => ($personal['AllowInteraction'] ? FALSE : TRUE));
                $menuitems[] = array('colour' => 'primary', 'script' => "OpenDialog( 'sendemailrecord', { large: true, urlparams: { PersonID: {$PERSON->PersonID} } } )", 'icon' => 'gi-message_new', 'tooltip' => 'Send Email', 'disabled' => ($personal['AllowInteraction'] ? FALSE : TRUE));
                $menuitems[] = array('colour' => 'info', 'function' => 'toggle', 'icon' => 'fa-arrows-v', 'style' => 'alt');
                stdTitleBlock(Fullname($personal), $do, $menuitems, TRUE);
                //https://bcs.localhost:44443/record.php?rec=person&personid=12501
                AddRecent(Fullname($personal, array('middlenames' => FALSE, 'title' => FALSE, 'postnominals' => FALSE, 'html' => FALSE, 'tooltip' => FALSE)), $_SERVER['HTTP_REFERER']);
                if(!empty($personal['Deceased'])) {
                    AlertBox(
                        array(
                            'type' => 'danger',
                            'title' => '<b>Deceased!</b>',
                            'canhide' => FALSE,
                            'items' => array(
                                array(
                                    'type' => 'item',
                                    'caption' => '<b>Notice:</b> This person is deceased. Do not attempt to make contact. This record will not be included in any data exports or bulk email lists.'
                                )
                            )
                        )
                    );
                }                
                Div(array('class' => array('block-content')), 8);
                $tabitems = array();
                $tabitems['personal'] = array('id' => 'personal', 'icon' => 'gi-user', 'tooltip' => 'Personal Details');
//                $tabitems['personal'] = array('id' => 'personal', 'icon' => 'gi-user', 'tooltip' => 'Personal Details', 'FormLoader' => 'frmPersonPersonal');
                $tabitems['contact'] = array('id' => 'contact', 'icon' => 'gi-vcard', 'tooltip' => 'Contact');
                $tabitems['membership'] = array('id' => 'membership', 'icon' => 'fa-bolt', 'tooltip' => 'Membership');
                $tabitems['profile'] = array('id' => 'profile', 'icon' => 'gi-nameplate_alt', 'tooltip' => 'Profile');
                $tabitems['finance'] = array('id' => 'finance', 'icon' => 'gi-calculator', 'tooltip' => 'Finance');
                $tabitems['connections'] = array('id' => 'connections', 'icon' => 'hi-link', 'tooltip' => 'Engagement');
                $tabitems['documents'] = array('id' => 'documents', 'icon' => 'gi-file', 'tooltip' => 'Documents');
                $tabitems['notes'] = array('id' => 'notes', 'icon' => 'gi-tags', 'tooltip' => 'Notes', 'colour' => ($personal['RecentNoteCount'] > 0 ? 'warning' : null));
                $tabitems['workflow'] = array('id' => 'workflow', 'icon' => 'fa-gears', 'tooltip' => 'Workflow', 'colour' => (!is_null($personal['WorkflowStatus']) ? 'warning' : null));
                $tabitems['settings'] = array('id' => 'settings', 'icon' => 'gi-settings', 'tooltip' => 'Account and Settings');
                $tabitems['history'] = array('id' => 'history', 'icon' => 'gi-history', 'tooltip' => 'History');
                $tabitems['media'] = array('id' => 'media', 'icon' => 'gi-camera', 'tooltip' => 'Media', 'FormLoader' => 'frmPersonMedia');
                PrepareTabs($tabitems);
                Tabs($tabitems, 9);
                Div(array('class' => array('tab-content')), 9);
                foreach($tabitems AS $tabitem) {
                    if (OpenTabContent($tabitem, 10)) {
                        //SimpleHeading($tabitem['tooltip'], 4, 'sub', 11);
                        if(!empty($tabitem['FormLoader'])) {
                            call_user_func($tabitem['FormLoader'], $PERSON);
                        }
                        CloseTabContent($tabitem, 10);
                    }
                }
                Div(null, 9); //tab content block
                Div(null, 8); //record content block
                Div(null, 7); //record block
            }
            break;
        case 'record_organisation':
            if (CheckRequiredParams(array('organisationid' => FALSE), $_GET)) {
                if(!isset($ORGANISATION)) {
                    $ORGANISATION = new crmOrganisation($SYSTEM_SETTINGS['Database'], intval($_GET['organisationid']));
                }
                $general = $ORGANISATION->GetRecord('general');
                $menuitems = array();
                $dp = PriorityDisplay($general['WorkflowStatus']);
                $menuitems[] = array('colour' => $dp['colour'], 'script' => (is_null($general['WorkflowStatus']) ? "OpenDialog( '".(is_null($general['WorkflowStatus'])? 'addworkflow' : 'editrecordworkflow')."', { urlparams: { OrganisationID: {$ORGANISATION->OrganisationID} } }  )": "$('[href=#tab-workflow]').tab('show');"), 'icon' => $dp['icon'], 'tooltip' => (is_null($general['WorkflowStatus']) ? "Add to workflow" : "View workflow for this record"), 'disabled' => ($general['AllowInteraction'] ? FALSE : TRUE));
                //$menuitems[] = array('colour' => 'primary', 'script' => "OpenDialog( 'sendemail', { large: true } )", 'icon' => 'gi-message_new', 'tooltip' => 'Send Email', 'disabled' => ($general['AllowInteraction'] ? FALSE : TRUE));
                $menuitems[] = array('colour' => 'info', 'function' => 'toggle', 'icon' => 'fa-arrows-v', 'style' => 'alt');
                stdTitleBlock(Fullname($general), $do, $menuitems, TRUE);
                //https://bcs.localhost:44443/record.php?rec=person&personid=12501
                AddRecent(Fullname(array('Name' => TextEllipsis($general['Name'], 22), 'Dissolved' => $general['Dissolved']), array('html' => FALSE, 'tooltip' => FALSE)), $_SERVER['HTTP_REFERER']);
                if(!empty($general['Dissolved'])) {
                    AlertBox(
                        array(
                            'type' => 'danger',
                            'title' => '<b>Dissolved!</b>',
                            'canhide' => FALSE,
                            'items' => array(
                                array(
                                    'type' => 'item',
                                    'caption' => '<b>Notice:</b> This organisation is dissolved. This record will not be included in any data exports or bulk email lists.'
                                )
                            )
                        )
                    );
                }                
                Div(array('class' => array('block-content')), 8);
                $tabitems = array();
                $tabitems['general'] = array('id' => 'general', 'icon' => 'gi-user', 'tooltip' => 'Organisation Details', 'FormLoader' => 'frmOrganisationGeneral');
                $tabitems['contact'] = array('id' => 'contact', 'icon' => 'gi-vcard', 'tooltip' => 'Contact');
                $tabitems['finance'] = array('id' => 'finance', 'icon' => 'gi-calculator', 'tooltip' => 'Finance');
                $tabitems['connections'] = array('id' => 'connections', 'icon' => 'hi-link', 'tooltip' => 'Engagement');
                $tabitems['documents'] = array('id' => 'documents', 'icon' => 'gi-file', 'tooltip' => 'Documents');
                $tabitems['notes'] = array('id' => 'notes', 'icon' => 'gi-tags', 'tooltip' => 'Notes', 'colour' => ($general['RecentNoteCount'] > 0 ? 'warning' : null));
                $tabitems['workflow'] = array('id' => 'workflow', 'icon' => 'fa-gears', 'tooltip' => 'Workflow', 'colour' => (!is_null($general['WorkflowStatus']) ? 'warning' : null));
                //$tabitems['settings'] = array('id' => 'settings', 'icon' => 'gi-settings', 'tooltip' => 'Account and Settings');
                $tabitems['history'] = array('id' => 'history', 'icon' => 'gi-history', 'tooltip' => 'History');
                PrepareTabs($tabitems);
                Tabs($tabitems, 9);
                Div(array('class' => array('tab-content')), 9);
                foreach($tabitems AS $tabitem) {
                    if (OpenTabContent($tabitem, 10)) {
                        //SimpleHeading($tabitem['tooltip'], 4, 'sub', 11);
                        if(!empty($tabitem['FormLoader'])) {
                            call_user_func($tabitem['FormLoader'], $ORGANISATION);
                        }
                        CloseTabContent($tabitem, 10);
                    }
                }
                Div(null, 9); //tab content block
                Div(null, 8); //record content block
                Div(null, 7); //record block
            }
            break;        
            break;
        case 'tab_person':
            if (CheckRequiredParams(array('personid' => FALSE, 'tabid' => FALSE), $_GET)) {
                $personid = intval($_GET['personid']);
                $PERSON = new crmPerson('eventsPerson', $SYSTEM_SETTINGS['Database'], $personid, $SYSTEM_SETTINGS["Membership"]);
                //file_put_contents("D:\\temp\\person.txt", print_r($PERSON, TRUE));
                switch($_GET['tabid']) {
                    case 'tab-personal':
                        SimpleHeading('Personal Details', 4, 'sub', 11);    
                        $personal = $PERSON->GetRecord('personal');
                        if(!empty($personal['Deceased'])) {
                            $personal['ConfirmedDeceased'] = $personal['Deceased'];
                        }
                        $fieldsets = array();
                        $fieldsets[] = array('fields' => array(
                            array('name' => 'PersonID', 'kind' => 'hidden'),
                            TitleField(FALSE),
//                            array('name' => 'Title', 'caption' => 'Title', 'kind' => 'control', 'type' => 'string', 'required' => TRUE, 'size' => 4, 'hint' => 'Mr, Ms, Mrs, Dr, Professor,...'),
                            array('name' => 'Firstname', 'caption' => 'First name', 'kind' => 'control', 'type' => 'string', 'required' => TRUE, 'size' => 6),
                            array('name' => 'Middlenames', 'caption' => 'Middle name(s)', 'kind' => 'control', 'type' => 'string', 'size' => 6),
                            array('name' => 'Lastname', 'caption' => 'Last name', 'kind' => 'control', 'type' => 'string', 'required' => TRUE, 'size' => 6),
                            array('name' => 'Gender', 'caption' => 'Gender', 'kind' => 'control', 'type' => 'combo', 'required' => $SYSTEM_SETTINGS["Customise"]["GenderRequired"], 'mustnotmatch' => ($SYSTEM_SETTINGS["Customise"]["GenderRequired"] ? 'unknown' : null), 'options' => $PERSON->Genders->Genders, 'size' => 4),
                            array('name' => 'DOB', 'caption' => 'Date of birth', 'kind' => 'control', 'type' => 'date', 'required' => $SYSTEM_SETTINGS["Customise"]["DOBRequired"], 'showage' => true),
                            array('name' => 'ExtPostnominals', 'caption' => 'Postnominals', 'kind' => 'control', 'type' => 'string', 'size' => 4, 'hint' => 'Any postnominals not automatically generated. Do not include '.$SYSTEM_SETTINGS["General"]['OrgShortName'].' postnominals.'),
                            array('name' => 'NationalityID', 'caption' => 'Nationality', 'kind' => 'control', 'type' => 'advcombo', 'allowempty' => TRUE, 'size' => 6, 'options' => $PERSON->Countries->Nationalities),
                            array('name' => 'ISO3166', 'caption' => 'Country of Residence', 'kind' => 'control', 'type' => 'advcombo', 'required' => TRUE, 'allowempty' => FALSE, 'size' => 6, 'options' => $PERSON->Countries->Countries),
                            array('name' => 'ISO4217', 'caption' => 'Invoicing Currency', 'kind' => 'control', 'type' => 'advcombo', 'required' => TRUE, 'allowempty' => FALSE, 'size' => 4, 'options' => $PERSON->Countries->Currencies),
                            array('name' => 'Graduation', 'caption' => 'Graduation', 'kind' => 'control', 'type' => 'date', 'showage' => true),
                            array('name' => 'PaidEmployment', 'caption' => 'Paid employment since', 'kind' => 'control', 'type' => 'date', 'showage' => TRUE),
                            array('name' => 'Deceased', 'caption' => 'Deceased', 'kind' => 'control', 'type' => 'date', 'readonly' => !empty($personal['Deceased'])),
                            array('name' => 'ConfirmedDeceased', 'kind' => 'hidden'),
                        ));
                        $formitem = array(
                            'id' => 'frmPersonPersonal', 'style' => 'standard', 'spinner' => TRUE,
                            'onsubmit' => "confirmDeceased(".(empty($personal['Deceased']) ? "true" : "false")."); return false;", 
                            //"submitForm( 'frmPersonPersonal', '/syscall.php?do=savePersonPersonal', { parseJSON: true, defErrorDlg: true, cbSuccess: function(frmElement, jsonResponse){ if( jsonResponse.Deceased ){ window.location.href = window.location.href; } else { ReloadTab('tab-personal', 'sidebar_membership', 'tab-membership' ); } }".(empty($personal['Deceased']) ? ", validate: function(frmElement){ return confirmDeceased(frmElement); }" : "")." } ); return false;",
                            'datasource' => $personal, 'buttons' => DefFormButtons("Save Changes"),
                            'fieldsets' => $fieldsets, 'borders' => TRUE
                        );
                        //
                        Form($formitem);
                        jsFormValidation($formitem['id'], TRUE, 6);
                        break;
                    case 'tab-contact':
                        SimpleHeading('Contact', 4, 'sub', 11);
                        if(!$PERSON->Deceased) {
                            Div(array('class' => 'row'), 12);
                            Div(array('class' => 'col-sm-3'), 13);
                            $fieldsets = array();
                            $fieldsets[] = array('fields' => array(
                                array('name' => 'DoNotContact', 'tooltip' => 'Do not Contact', 'type' => 'switch', 'kind' => 'control', 'colour' => 'danger', 'hint' => 'Do not Contact',
                                      'script' => "execSyscall('/syscall.php?do=toggleboolean', { parseJSON: true, defErrorDlg: true, postparams: { PersonID: {$PERSON->PersonID}, table: 'tblperson', fieldname: 'DoNotContact', currentvalue: {$PERSON->DoNotContact}, caption: 'Do not Contact', hist_flag: 'warning' }, cbSuccess: function(){ ReloadTab('{$_GET['tabid']}', 'sidebar_warnings'); } } )"
                                )
                            ));
                            $formitem = array(
                                'id' => 'frmPersonDoNotContact', 'style' => 'vertical', 'spinner' => FALSE,
                                'datasource' => $PERSON->GetRecord('personal'), 'buttons' => array(),
                                'fieldsets' => $fieldsets, 'borders' => FALSE
                            );
                            Form($formitem);
                            Div(null, 13);
                            Div(array('class' => 'col-sm-3'), 13);
                            $fieldsets = array();
                            $fieldsets[] = array('fields' => array(
                                array('name' => 'NoMarketing', 'tooltip' => 'No Marketing', 'type' => 'switch', 'kind' => 'control', 'colour' => 'warning', 'hint' => 'No Marketing',
                                      'script' => "execSyscall('/syscall.php?do=toggleboolean', { parseJSON: true, defErrorDlg: true, postparams: { PersonID: {$PERSON->PersonID}, table: 'tblperson', fieldname: 'NoMarketing', currentvalue: {$PERSON->NoMarketing}, caption: 'No Marketing', hist_flag: 'warning' }, cbSuccess: function(){ ReloadTab('{$_GET['tabid']}', 'sidebar_warnings'); } } )"
                                )
                            ));
                            $formitem = array(
                                'id' => 'frmPersonNoMarketing', 'style' => 'vertical', 'spinner' => FALSE,
                                'datasource' => $PERSON->GetRecord('personal'), 'buttons' => array(),
                                'fieldsets' => $fieldsets, 'borders' => FALSE
                            );
                            Form($formitem);
                            Div(null, 13);
                            Div(array('class' => 'col-sm-3'), 13);
                            Div(null, 13);
                            Div(array('class' => 'col-sm-3'), 13);
                            Div(null, 13);
                            Div(null, 12);
                        }
                        Div(array('class' => 'row'), 12);
                        Div(array('class' => 'col-lg-6'), 13);
                        SimpleHeading('Email Address', 5, 'sub', 14);
                        $emails = $PERSON->GetRecord('email');
                        $table = array(
                            'prototype' => array(
                                'edit' => array(
                                    'dlgname' => 'editemail',
                                    'params' => array('PersonID' => $personid), 
                                ),
                                'sendemail' => array(
                                    'dlgname' => 'sendemailrecord',
                                    'params' => array(), 
                                    'large' => TRUE,
                                ),
                                'del' => array(
                                    'exec' => 'delemail',
                                    'params' => array('PersonID' => $personid), 
                                    'title' => 'Delete Email',
                                    'message' => 'Are you sure you want to delete this email?'
                                ),
                                'reload' => array('do' => $do, 'personid' => $personid, 'tabid' => $_GET['tabid']),
                            ),
                            'header' => FALSE,
                            'striped' => TRUE,
                            'condensed' => TRUE,
                            'borders' => 'none',
                            'responsive' => FALSE,
                            'valign' => 'centre',
                            'margin' => TRUE,
                            'columns' => array(
                                array(
                                    'field' => array('name' => 'Email', 'type' => 'email'),
                                    'function' => 'stdTableItem'
                                ),
                                array(
                                    'field' => array('name' => 'EmailActions', 'type' => 'control'),
                                    'function' => 'stdTableItem'
                                ),
                            ),
                        );
                        ButtonGroup(stdTableButtons( $table, 
                            array(
                                'edit' => array('icon' => 'fa-plus-square', 'caption' => 'Add Email', 'tooltip' => 'Add a new email address')
                            )), FALSE, null, 15, FALSE);
                        StaticTable($PERSON->GetRecord('email'), $table, array(), 15);
                        Div(null, 13);
                        Div(array('class' => 'col-lg-6'), 13);
                        SimpleHeading('Postal Address', 5, 'sub', 14);
                        $addresses = $PERSON->GetRecord('address');
                        $table = array(
                            'prototype' => array(
                                'edit' => array(
                                    'dlgname' => 'editaddress',
                                    'params' => array('PersonID' => $personid), 
                                ),
                                'label' => array(
                                    'dlgname' => 'labeladdress',
                                    'params' => array('PersonID' => $personid), 
                                ),
                                'del' => array(
                                    'exec' => 'deladdress',
                                    'params' => array('PersonID' => $personid), 
                                    'title' => 'Delete Address',
                                    'message' => 'Are you sure you want to delete this address?'
                                ),
                                'reload' => array('do' => $do, 'personid' => $personid, 'tabid' => $_GET['tabid']),
                            ),
                            'header' => FALSE,
                            'striped' => TRUE,
                            'condensed' => TRUE,
                            'borders' => 'none',
                            'responsive' => FALSE,
                            'valign' => 'centre',
                            'margin' => TRUE,
                            'columns' => array(
                                array(
                                    'field' => array('name' => 'Address', 'type' => 'address'),
                                    'function' => 'stdTableItem'
                                ),
                                array(
                                    'field' => array('name' => 'AddressActions', 'type' => 'control'),
                                    'function' => 'stdTableItem'
                                ),
                            ),
                        );                        
                        ButtonGroup(stdTableButtons( $table, 
                            array(
                                'edit' => array('icon' => 'fa-plus-square', 'caption' => 'Add Address', 'tooltip' => 'Add a new postal address')
                            )), FALSE, null, 15, FALSE);
                        StaticTable($addresses, $table, array(), 15);
                        Div(null, 13);
                        Div(null, 12);
                        Div(array('class' => 'row'), 12);
                        Div(array('class' => 'col-lg-6'), 13);
                        SimpleHeading('Phone', 5, 'sub', 14);
                        $phones = $PERSON->GetRecord('phone');
                        $table = array(
                            'prototype' => array(
                                'edit' => array(
                                    'dlgname' => 'editphone',
                                    'params' => array('PersonID' => $personid), 
                                ),
                                'del' => array(
                                    'exec' => 'delphone',
                                    'params' => array('PersonID' => $personid), 
                                    'title' => 'Delete Phone',
                                    'message' => 'Are you sure you want to delete this phone number?'
                                ),
                                'reload' => array('do' => $do, 'personid' => $personid, 'tabid' => $_GET['tabid']),
                            ),
                            'header' => FALSE,
                            'striped' => TRUE,
                            'condensed' => TRUE,
                            'borders' => 'none',
                            'responsive' => FALSE,
                            'valign' => 'centre',
                            'margin' => TRUE,
                            'columns' => array(
                                array(
                                    'field' => array('name' => 'PhoneNo', 'type' => 'phone'),
                                    'function' => 'stdTableItem'
                                ),
                                array(
                                    'field' => array('name' => 'PhoneActions', 'type' => 'control'),
                                    'function' => 'stdTableItem'
                                ),
                            ),
                        );
                        ButtonGroup(stdTableButtons($table, 
                            array(
                                'edit' => array('icon' => 'fa-plus-square', 'caption' => 'Add Phone', 'tooltip' => 'Add a new phone number')
                            )), FALSE, null, 15, FALSE);
                        StaticTable($phones, $table, array(), 15);
                        Div(null, 13);
                        Div(array('class' => 'col-lg-6'), 13);
                        SimpleHeading('Web and Social', 5, 'sub', 14);
                        $online = $PERSON->GetRecord('online');
                        $table = array(
                            'prototype' => array(
                                'edit' => array(
                                    'dlgname' => 'editonline',
                                    'params' => array('PersonID' => $personid), 
                                ),
                                'del' => array(
                                    'exec' => 'delonline',
                                    'params' => array('PersonID' => $personid), 
                                    'title' => 'Delete',
                                    'message' => 'Are you sure you want to delete this online item?'
                                ),
                                'reload' => array('do' => $do, 'personid' => $personid, 'tabid' => $_GET['tabid']),
                            ),
                            'header' => FALSE,
                            'striped' => TRUE,
                            'condensed' => TRUE,
                            'borders' => 'none',
                            'responsive' => FALSE,
                            'valign' => 'centre',
                            'margin' => TRUE,
                            'columns' => array(
                                array(
                                    'field' => array('name' => 'Online', 'type' => 'url'),
                                    'function' => 'stdTableItem'
                                ),
                                array(
                                    'field' => array('name' => 'OnlineActions', 'type' => 'control'),
                                    'function' => 'stdTableItem'
                                ),
                            ),
                        );
                        ButtonGroup(stdTableButtons($table, 
                            array(
                                'edit' => array('icon' => 'fa-plus-square', 'caption' => 'Add Item', 'tooltip' => 'Add a new item')
                            )), FALSE, null, 15, FALSE);
                        StaticTable($online, $table, array(), 15);
                        Div(null, 13);
                        Div(null, 12);
                        break;                    
                    case 'tab-membership':
                        $personal = $PERSON->GetRecord('personal', TRUE);
                        SimpleHeading('Membership', 4, 'sub', 11);
                        $buttonitems = array();
                        if($personal['IsMember']) {
                            $transfer = $PERSON->GetOpenTransfer('members');
                            if(!empty($transfer)) {
                                $buttonitems[] = array(
                                    'icon' => 'fa-ban', 'caption' => 'Cancel Transfer', 'iconalign' => 'left', 'type' => 'button', 'colour' => 'warning',
                                    'script' => "OpenDialog('canceltransferdlg', { large: true, parseJSON: true, defErrorDlg: true, urlparams: { TransferID: {$transfer['TransferID']} } } )",
                                );
                            } else {
                                $renewal = $PERSON->RenewalSettings();
                                $fv = ($personal['NoRenewal'] ? 0 : 1);
                                if(!$personal['NoRenewal']) {
                                    $buttonitems[] = array(
                                        'icon' => 'fa-bolt', 'caption' => 'Renewal Settings', 'iconalign' => 'left', 'type' => 'button', 'colour' => 'success',
                                        'script' =>  "OpenDialog('editrenewal', { parseJSON: true, defErrorDlg: true, large: true, urlparams: { PersonID: {$PERSON->PersonID}, SelectorID: 'members' } })",
                                    );
                                    if($renewal['HasTransaction'] && empty($renewal['Processed'])) {
                                        $buttonitems[] = array(
                                            'icon' => 'fa-money', 'caption' => 'Renewal Payment', 'iconalign' => 'left', 'type' => 'button', 'colour' => 'info',
                                            'script' => "OpenDialog('addmoneyMSTab', { large: true, urlparams: { invoiceid: {$renewal['InvoiceID']}, amount: {$renewal['ItemTotal']} } });",
                                        );
                                    }
                                }
                                if(!$renewal['HasRenewal'] && ($renewal['DaysToRenewal'] > ($SYSTEM_SETTINGS['Membership']['RenewalCycleStart']+$SYSTEM_SETTINGS['Membership']['TransferCutoff']))) {
                                    $buttonitems[] = array(
                                        'icon' => 'gi-sorting', 'caption' => 'Start Transfer', 'iconalign' => 'left', 'type' => 'button', 'colour' => 'success',
                                        'script' => "OpenDialog('msstarttransferdlg', { large: true, parseJSON: true, defErrorDlg: true, urlparams: { PersonID: {$PERSON->PersonID}, SelectorID: 'members' } } )",
                                    );
                                }
                                $buttonitems[] = array(
                                    'icon' => ($personal['NoRenewal'] ? 'fa-bolt' : 'gi-ban'), 'caption' => ($personal['NoRenewal'] ? 'Enable renewal' : 'Do not renew'), 'iconalign' => 'left', 'type' => 'button', 'colour' => ($personal['NoRenewal'] ? 'info' : 'warning'),
                                    'script' =>  "execSyscall('/syscall.php?do=changeflag', { parseJSON: true, defErrorDlg: true, postparams: { PersonID: {$PERSON->PersonID}, table: 'tblperson', fieldname: 'MSFlags', flag: 'norenewal', value: {$fv}, history_desc: 'Do not renew' }, cbSuccess: function(){ ReloadTab('{$_GET['tabid']}', 'sidebar_membership'); } } )",
                                );
                            }
                        } elseif(empty($personal['Deceased'])) {
                            $msapplication = $PERSON->GetOpenApplication();
                            if(!empty($msapplication)) {
                                //file_put_contents("D:\\temp\\application.txt", print_r($msapplication, TRUE));
                                $appModel = new crmMSApplicationModel($SYSTEM_SETTINGS['Database'], $SYSTEM_SETTINGS['Membership']);
                                $currentStage = $appModel->GetStage($msapplication['ApplicationStageID'], $msapplication['Paid']);
                                $previousStage = $appModel->GetStage($currentStage['PreviousStageID'], $msapplication['Paid']);
                                $nextStage = $appModel->GetStage($currentStage['NextStageID'], $msapplication['Paid']);
                                if($currentStage['SubmissionStage'] >= 0) {
                                    if(!$msapplication['Paid']) {
                                        if($msapplication['HasTransaction']) {
                                            $buttonitems[] = array(
                                                'icon' => 'fa-money', 'caption' => 'Process Payment', 'iconalign' => 'left', 'type' => 'button', 'colour' => 'info',
                                                'script' => "OpenDialog('addmoneyMSTab', { large: true, urlparams: { invoiceid: {$msapplication['InvoiceID']}, amount: {$msapplication['ItemTotal']} } });",
                                            );
                                        } else {
                                            $buttonitems[] = array(
                                                'icon' => 'fa-money', 'caption' => 'Mark as Paid', 'iconalign' => 'left', 'type' => 'button', 'colour' => 'info',
                                                'script' =>  "execSyscall('/syscall.php?do=changeflag', { parseJSON: true, defErrorDlg: true, postparams: { ApplicationID: {$msapplication['ApplicationID']}, PersonID: {$PERSON->PersonID}, table: 'tblapplication', fieldname: 'Flags', flag: 'paid', value: 1, history_desc: 'Membership application marked as paid' }, cbSuccess: function(){ ReloadTab('{$_GET['tabid']}', 'sidebar_membership'); } } )",
                                            );
                                        }
                                    }
                                }
                                $buttonitems[] = array(
                                    'icon' => 'fa-bolt', 'caption' => 'Application Settings', 'iconalign' => 'left', 'type' => 'button', 'colour' => ($msapplication['Paid'] ? 'warning' : 'info'),
                                    'script' => "OpenDialog('mschangeapplicationdlg', { parseJSON: true, defErrorDlg: true, urlparams: { ApplicationID: {$msapplication['ApplicationID']} } } )",
                                );
                                if(!empty($previousStage)) {
                                    $buttonitems[] = array(
                                        'icon' => 'fa-chevron-circle-left', 'caption' => 'Revert to '.$previousStage['StageCaption'], 'iconalign' => 'left', 'type' => 'button', 'colour' => 'warning',
                                        'script' => "execSyscall('/syscall.php?do=changeappstage', { parseJSON: true, defErrorDlg: true, postparams: { ApplicationID: {$msapplication['ApplicationID']}, ApplicationStageID: {$previousStage['ApplicationStageID']} }, cbSuccess: function(){ ReloadTab('{$_GET['tabid']}', 'sidebar_membership'); } })",
                                    );
                                }
                                if(!empty($nextStage)) {
                                    $buttonitems[] = array(
                                        'icon' => 'fa-chevron-circle-right', 'caption' => $nextStage['StageCaption'], 'iconalign' => 'left', 'type' => 'button', 'colour' => $nextStage['StageColour'],
                                        'script' => "execSyscall('/syscall.php?do=changeappstage', { parseJSON: true, defErrorDlg: true, postparams: { ApplicationID: {$msapplication['ApplicationID']}, ApplicationStageID: {$nextStage['ApplicationStageID']} }, cbSuccess: function(){ ReloadTab('{$_GET['tabid']}', 'sidebar_membership'); } })",
                                    );
                                }
                                if($currentStage['IsElectionStage']) {
                                    $buttonitems[] = array(
                                        'icon' => 'fa-bolt', 'caption' => 'Elect', 'iconalign' => 'left', 'type' => 'button', 'colour' => 'success',
                                        'script' => "OpenDialog('mselectpersondlg', { parseJSON: true, defErrorDlg: true, urlparams: { ApplicationID: {$msapplication['ApplicationID']} } } )",
                                    );
                                }
                                $buttonitems[] = array(
                                    'icon' => 'fa-times', 'caption' => 'Cancel Application', 'iconalign' => 'left', 'type' => 'button', 'colour' => 'danger',
                                    'script' => "OpenDialog('cancelapplicationdlg', { large: true, parseJSON: true, defErrorDlg: true, urlparams: { ApplicationID: {$msapplication['ApplicationID']} } } )",
                                );
                            } else {
                                if(empty($personal['FutureMS'])) {
                                    $rejoin = $PERSON->GetOpenRejoin('members');
                                    if(!empty($rejoin)) {
                                        $buttonitems[] = array(
                                            'icon' => 'fa-ban', 'caption' => 'Cancel Rejoin', 'iconalign' => 'left', 'type' => 'button', 'colour' => 'warning',
                                            'script' => "OpenDialog('cancelrejoindlg', { large: true, parseJSON: true, defErrorDlg: true, urlparams: { RejoinID: {$rejoin['RejoinID']} } } )",
                                        );
                                    } else {
                                        $buttonitems[] = array(
                                            'icon' => 'fa-bolt', 'caption' => 'Start Application', 'iconalign' => 'left', 'type' => 'button', 'colour' => 'success',
                                            'script' => "OpenDialog('msstartapplicationdlg', { parseJSON: true, defErrorDlg: true, urlparams: { PersonID: {$PERSON->PersonID}, SelectorID: 'members' } } )",
                                        );
                                        if(!empty($personal['MSMemberSince'])) {
                                            $buttonitems[] = array(
                                                'icon' => 'gi-undo', 'caption' => 'Start Rejoin', 'iconalign' => 'left', 'type' => 'button', 'colour' => 'success',
                                                'script' => "OpenDialog('msstartrejoindlg', { large: true, parseJSON: true, defErrorDlg: true, urlparams: { PersonID: {$PERSON->PersonID}, SelectorID: 'members' } } )",
                                            );
                                        }
                                    }
                                } else {
                                    SimpleAlertBox('info', 'The Membership status for this record will change on <b>'.date('j F Y', strtotime($personal['FutureMSChangeDate'].' UTC'))."</b>", 12);
                                }
                            }
                        }
                        if(empty($personal['MSNumber'])) {
                            $buttonitems[] = array(
                                'caption' => 'Assign MS Number', 'iconalign' => 'left', 'type' => 'button', 'colour' => 'info',
                                'script' => "execSyscall('/syscall.php?do=assignmsnumber', { parseJSON: true, defErrorDlg: true, postparams: { PersonID: {$PERSON->PersonID} }, cbSuccess: function(){ ReloadTab('{$_GET['tabid']}', 'sidebar_membership'); } })",
                            );
                        }
                        ButtonGroup($buttonitems, FALSE, NULL, 12);
                        Div(array('class' => 'row'), 12);
                        Div(array('class' => 'col-lg-6'), 13);
                            SimpleHeading('Summary', 5, 'sub', 14);
                            $datasource = array();
                            if($personal['IsMember']) {
                                $datasource[] = array('caption' => 'Status', 'value' => FmtText($personal['MSFmt'].$personal['MSText'].CloseFormattingString($personal['MSFmt'])));
//                                $datasource[] = array('caption' => 'Status', 'value' => MSTTText($personal));
                                if(!empty($personal['MSMemberSince'])) {
                                    $datasource[] = array(
                                        'caption' => 'Member Since',
                                        'value' => LinkTo(
                                            date('j F Y', strtotime($personal['MSMemberSince'])),
                                            array(
                                                'script' => "OpenDialog('editsimplefield', { parseJSON: true, defErrorDlg: true, urlparams: { tablename: 'tblperson', fieldname: 'MSMemberSince', fieldtype: 'date', caption: 'Member Since', idfieldname: 'PersonID', idvalue: {$PERSON->PersonID}, tabid: 'tab-membership', sidebars: 'sidebar_membership' } })",
                                            ) 
                                        ),
                                    );
                                }
/*                                if(!empty($personal['MSNextRenewal'])) {
                                    $datasource[] = array('caption' => 'Next Renewal', 'value' => date('j F Y', strtotime($personal['MSNextRenewal'])).(!empty($personal['RenewalCycle']) ? ", <span class=\"text-".($personal['RenewalCycle'] == 1 ? "warning" : "danger")."\">".$personal['RenewalCycleTxt']."</span>" : ''));
                                    if($personal['NoRenewal']) {
                                        $datasource[] = array('caption' => '', 'value' => FmtText('<warning><b>Do not renew</b></warning>'));
                                    }
                                }*/
                                //file_put_contents("d:\\temp\\personal.txt", print_r($personal, TRUE));
                                if($personal['NoRenewal']) {
                                    $datasource[] = array('caption' => '', 'value' => FmtText('<warning><b>Do not renew</b></warning>'));
                                } elseif(!empty($personal['RenewalCycle']) || $personal['HasRenewal']) {
                                    //$renewal = $PERSON->RenewalSettings();
                                    $datasource[] = array('caption' => 'Next Renewal', 'value' => date('j F Y', strtotime($personal['MSNextRenewal'])).(!empty($personal['RenewalCycle']) ? ", <span class=\"text-".($personal['RenewalCycle'] == 1 ? "warning" : "danger")."\">".$personal['RenewalCycleTxt']."</span>" : ''));
                                    if($renewal['ChangeOfGrade']) {
                                        $datasource[] = array('caption' => 'Renewal '.$SYSTEM_SETTINGS['Membership']['GradeCaption'], 'value' => FmtText('<b><info>'.$renewal['GradeCaption'].'</info></b>'));
                                    }
                                    if($renewal['MSFree'] <> $personal['MSFree']) {
                                        $datasource[] = array('caption' => '', 'value' => FmtText(($renewal['MSFree'] ? '<info>Will renew as <b>Free</b> Membership</info>' : '<warning>Will <b>not</b> renew as Free Membership</warning>')));
                                    }
                                    if($renewal['HasTransaction']) {
                                        $datasource[] = array('caption' => 'Invoiced', 'value' => FmtText('<b><info>'.InvoiceItemValueAsFmtString($renewal).'</info></b>, ').LinkTo($renewal['InvoiceCaption'], array('url' => "/record.php?rec=invoice&invoiceid=".$renewal['InvoiceID'], 'target' => (empty($AUTHENTICATION['Settings']['OpenNewWindow']) ? null : 'newwindow'))));
                                    }
                                    if(!empty($renewal['DiscountCode'])) {
                                        $datasource[] = array('caption' => 'Discount Code', 'value' => ToolTip($renewal['DiscountCode'], $renewal['DiscountDescription'], 'top', 'info'));
                                    }
                                } elseif(!empty($personal['MSNextRenewal'])) {
                                    $datasource[] = array('caption' => 'Next Renewal', 'value' => date('j F Y', strtotime($personal['MSNextRenewal'])).(!empty($personal['RenewalCycle']) ? ", <span class=\"text-".($personal['RenewalCycle'] == 1 ? "warning" : "danger")."\">".$personal['RenewalCycleTxt']."</span>" : " <span class=\"text-muted\">(A renewal record has not yet been created)</span>"));
                                }
                                if(!empty($personal['MSLastReminder'])) {
                                    $datasource[] = array('caption' => 'Last Reminder', 'value' => date('j F Y', strtotime($personal['MSLastReminder'])));
                                }
                            } else {
                                if(!empty($msapplication)) {
                                    $datasource[] = array('caption' => 'Status', 'value' => FmtText("<{$msapplication['StageColour']}>Application {$msapplication['StageName']}"."</{$msapplication['StageColour']}> (".$msapplication['GradeCaption'].($msapplication['Paid'] ? ", <b>Paid</b>": "").")"));
                                    $datasource[] = array('caption' => 'Created', 'value' => date('j F Y', strtotime($msapplication['Created'])));
                                    $datasource[] = array('caption' => 'Last Modified', 'value' => date('j F Y', strtotime($msapplication['LastModified'])));
                                    foreach(array('proposer', 'referee') AS $component) {
                                        if(isset($msapplication['ApplComponents'][$component])) {
                                            $name = ucfirst($component);
                                            $datasource[] = array(
                                                'caption' => $name,
                                                'value' => RequiredText(
                                                    $msapplication[$name],
                                                    array('script' => "OpenDialog('editproposerreferee', { parseJSON: true, defErrorDlg: true, urlparams: { ApplicationID: {$msapplication['ApplicationID']} } })")
                                                )
                                            );
                                        }
                                    }
                                    if($msapplication['HasTransaction']) {
                                        $datasource[] = array('caption' => 'Invoiced', 'value' => FmtText('<b><info>'.InvoiceItemValueAsFmtString($msapplication).'</info></b>, ').LinkTo($msapplication['InvoiceCaption'], array('url' => "/record.php?rec=invoice&invoiceid=".$msapplication['InvoiceID'], 'target' => (empty($AUTHENTICATION['Settings']['OpenNewWindow']) ? null : 'newwindow'))));
                                    } else {
                                        
                                    }
                                    if(!empty($msapplication['DiscountCode'])) {
                                        $datasource[] = array('caption' => 'Discount Code', 'value' => ToolTip($msapplication['DiscountCode'], $msapplication['DiscountDescription'], 'top', 'info'));
                                    }
                                } else {
                                    $datasource[] = array('caption' => 'Status', 'value' => FmtText($personal['MSFmt'].$personal['MSText'].CloseFormattingString($personal['MSFmt'])));    
                                    if(empty($personal['FutureMS'])) {
                                        if(!empty($rejoin)) {
                                            $datasource[] = array('caption' => '', 'value' => FmtText('<b><info>Rejoin pending</info></b>'));
                                        }
                                    } else {
                                        $datasource[] = array('caption' => 'Pending', 'value' => FmtText('Status change on <b>'.date('j F Y', strtotime($personal['FutureMSChangeDate'].' UTC'))."</b>"));
                                    }
                                }
                            }
                            $datasource[] = array(
                                'caption' => 'Payment Method',
                                'value' => ($PERSON->HasValidDDI() 
                                            ? ($personal['ISO4217'] <> 'GBP' ? ToolTip('Direct Debit', 'Invoicing currency is '.$personal['ISO4217'], 'top', 'warning') : FmtText('<success>Direct Debit</success>')) 
                                            : FmtText('<info>Standard</info>')
                                )
                            );
                            if(!empty($personal['MSNumber'])) {
                                $datasource[] = array('caption' => 'Number', 'value' => htmlspecialchars($personal['MSNumber']));
                            }
                            StaticTable($datasource, $summaryTable, array(), 9);                        
                        Div(null, 13);                        
                        Div(array('class' => 'col-lg-6'), 13);
                            if(empty($personal['Deceased'])) {
                                if(empty($renewal) || !$renewal['HasTransaction']) {
                                    SimpleHeading('Fee', 5, 'sub', 14);
                                    Para(array(), 14);
                                    if($personal['IsMember'] || !empty($msapplication)) {
                                        $msfees = new crmMSFees($SYSTEM_SETTINGS['Database'], $SYSTEM_SETTINGS['Membership']['GradeCaption']);
                                        $params = array(
                                            'ISO4217' => $personal['ISO4217'],
                                            'MSGradeID' => ($personal['IsMember'] ? $personal['MSGradeID'] : $msapplication['MSGradeID']),
                                            'ISO3166' => $personal['ISO3166'],
//                                            'ForDate' => ($personal['IsMember'] ? (!empty($personal['MSNextRenewal']) ? $personal['MSNextRenewal'] : null) : $msapplication['Created']),
                                            'ForDate' => ($personal['IsMember'] ? (!empty($personal['MSNextRenewal']) ? $personal['MSNextRenewal'] : null) : null),
                                            'IsDD' => $PERSON->HasValidDDI(),
                                            'DiscountID' => (!empty($msapplication['DiscountID']) ? $msapplication['DiscountID'] : null),
                                            'NOY' => (!empty($msapplication) ? $msapplication['NOY'] : 1), 
                                            'Free' => ($personal['MSFree'] ? TRUE : FALSE),
                                        );
                                        $fee = $msfees->CalculateFee($params);
                                        $count = 0;
                                        foreach($fee->Explanation AS $line) {
                                            echo ($count > 0 ? "<br>\n" : "").str_repeat("\t", 15).FmtText($line);
                                            $count++;
                                        }                                    
                                    }
                                    Para(null, 14);
                                }
                            }
                        Div(null, 13);                        
                        Div(null, 12);                        
                        Div(array('class' => 'row'), 12);
                        if($personal['IsMember'] || !empty($msapplication)) {
                            Div(array('class' => 'col-xs-12'), 13);
                            $msdirectory = $PERSON->GetRecord('msdirectory');
                            SimpleHeading('Membership Directory', 5, 'sub', 14);
                                $elements = new crmDirectoryElements('members');
                                $fieldsets = array();
                                $fieldsets[] = array('fields' => array(
                                    array('name' => 'PersonID', 'kind' => 'hidden'),
                                    array('name' => 'WSCategoryID', 'kind' => 'hidden'),
                                    array('name' => 'Elements[]', 'caption' => 'To include in Directory', 'kind' => 'control', 'type' => 'multi', 'options' => $elements->Elements, 'selected' => $msdirectory['ShowElements'], 'allowempty' => TRUE, 'placeholder' => 'Select elements for inclusion in the directory'),
                                ));
                                $formitem = array(
                                    'id' => 'frmPersonMSDirectory', 'style' => 'vertical', 'spinner' => TRUE,
                                    'onsubmit' => "submitForm( 'frmPersonMSDirectory', '/syscall.php?do=savePersonMSDirectory', { parseJSON: true, defSuccessDlg: true, defErrorDlg: true } ); return false;",
                                    'datasource' => $msdirectory, 'buttons' => DefFormButtons("Save Changes"),
                                    'fieldsets' => $fieldsets, 'borders' => FALSE
                                );
                                Form($formitem);
                            Div(null, 13);
                        }
                        Div(array('class' => 'col-xs-12'), 13);
                        SimpleHeading('Membership History', 5, 'sub', 14);
                            $table = array('id' => 'dt_person_mshistory', 'ajaxsrc' => '/datatable.php',
                                'params' => array('inc' => 'dtPersonMSHistory.php', 'fnrow' => 'dtGetRow'),
                                'GET' => array('personid'),
                                'drawcallback' => "dtDefDrawCallBack( oSettings );",
                                'responsive' => TRUE, 'hover' => FALSE, 'striped' => FALSE,
                                'columns' => array(
                                    array('caption' => 'From', 'fieldname' => 'BeginDate', 'width' => '9.25em'),
                                    array('caption' => 'Until', 'fieldname' => 'EndDate', 'width' => '9.25em'),
                                    array('caption' => 'Status', 'fieldname' => 'Status', 'sortable' => FALSE, 'searchable' => FALSE),
                                    array('caption' => 'Actions', 'searchable' => FALSE, 'sortable' => FALSE)
                                ),
                                'sortby' => array('column' => 'BeginDate', 'direction' => 'desc')
                            );
                            Datatable($table, array(), 9);
                            jsInitDatatable($table, TRUE, 9);                           
                        Div(null, 13);
                        Div(null, 12);                        
                        break;                    
                    case 'tab-profile':
                        SimpleHeading('Profile', 4, 'sub', 11);
                        $profile = $PERSON->GetRecord('profile');
                        $fieldsets = array();
                        $fieldsets[] = array('caption' => 'Study', 'fields' => array(
                            array('name' => 'PersonID', 'kind' => 'hidden'),
                            array('name' => 'PlaceOfStudyID', 'caption' => 'Place of Study', 'kind' => 'control', 'options' => $PERSON->PlacesOfStudy->GetPlacesOfStudy(), 'type' => 'advcombo', 'allowempty' => TRUE),
                            array('name' => 'StudyInstitution', 'caption' => 'Institution', 'kind' => 'control', 'type' => 'string'),
                            array('name' => 'StudyDepartment', 'caption' => 'Department', 'kind' => 'control', 'type' => 'string'),
                        ));
                        $fieldsets[] = array('caption' => 'Employment', 'fields' => array(
//                            array('name' => 'PersonID', 'kind' => 'hidden'),
                            array('name' => 'PlaceOfWorkID', 'caption' => 'Place of Employment', 'kind' => 'control', 'options' => $PERSON->PlacesOfWork->GetPlacesOfWork(), 'type' => 'groupcombo', 'allowempty' => TRUE),
                            array('name' => 'WorkRoleID', 'caption' => 'Primary Work Role', 'kind' => 'control', 'options' => $PERSON->WorkRoles->GetRoles(), 'type' => 'advcombo', 'allowempty' => TRUE),
                            array('name' => 'EmployerName', 'caption' => 'Employer', 'kind' => 'control', 'type' => 'string'),
                            array('name' => 'JobTitle', 'caption' => 'Job Title', 'kind' => 'control', 'type' => 'string'),
                        ));
                        $subjects = new crmSubjects($SYSTEM_SETTINGS['Database']);
                        $fieldsets[] = array('caption' => 'Expertise', 'fields' => array(
                            array('name' => 'SubjectIDs', 'caption' => 'Subjects', 'kind' => 'control', 'type' => 'multigroup', 'options' => $subjects->Subjects, 'selected' => $profile['SubjectIDs']),
                            array('name' => 'Keywords', 'caption' => 'Keywords', 'kind' => 'control', 'type' => 'memo'),
                        ));
                        $formitem = array(
                            'id' => 'frmPersonProfile', 'style' => 'standard', 'spinner' => TRUE,
                            'onsubmit' => "submitForm( 'frmPersonProfile', '/syscall.php?do=savePersonProfile', { parseJSON: true, defSuccessDlg: true, defErrorDlg: true } ); return false;",
                            'datasource' => $profile, 'buttons' => DefFormButtons("Save Changes"),
                            'fieldsets' => $fieldsets, 'borders' => TRUE
                        );
                        Form($formitem);
                        jsFormValidation($formitem['id'], TRUE, 6);
                        break;
                    case 'tab-finance':
                        SimpleHeading('Finance', 4, 'sub', 11);
                        SimpleHeading('Invoicing', 5, 'sub', 14);
                        INVTable($PERSON);
                        SimpleHeading('Direct Debit', 5, 'sub', 14);
                        DDITable($PERSON);
                        SimpleHeading('Discount Codes', 5, 'sub', 14);
                        DiscountTable($PERSON);
                        break;
                    case 'tab-connections':
                        SimpleHeading('Engagement', 4, 'sub', 11);
                        Div(array('class' => 'row'), 12);
                        Div(array('class' => 'col-lg-5'), 13);
                            SimpleHeading('Groups', 5, 'sub', 14);
                            $groups = $PERSON->GetRecord('groups');
                            $table = array(
                                'prototype' => array(
                                    'edit' => array(
                                        'dlgname' => 'editgroup',
                                        'params' => array('PersonID' => $personid), 
                                    ),
                                    'del' => array(
                                        'exec' => 'removefromgroup',
                                        'params' => array('PersonID' => $personid), 
                                        'title' => 'Remove from Group',
                                        'message' => 'Are you sure you want to remove this record from the group?'
                                    ),
                                    'reload' => array('do' => $do, 'personid' => $personid, 'tabid' => $_GET['tabid']),
                                ),
                                'header' => FALSE,
                                'striped' => TRUE,
                                'condensed' => TRUE,
                                'borders' => 'none',
                                'responsive' => FALSE,
                                'valign' => 'centre',
                                'margin' => TRUE,
                                'columns' => array(
                                    array(
                                        'field' => array('name' => 'Group', 'type' => 'string'),
                                        'function' => 'stdTableItem'
                                    ),
                                    array(
                                        'field' => array('name' => 'GroupActions', 'type' => 'control'),
                                        'function' => 'stdTableItem'
                                    ),
                                ),
                            );
                            ButtonGroup(stdTableButtons( $table, 
                                array(
                                    'edit' => array('icon' => 'fa-plus-square', 'caption' => 'Add to Group', 'tooltip' => 'Add to Group')
                                )), FALSE, null, 15, FALSE);
                            StaticTable($groups, $table, array(), 15);
                            
                            SimpleHeading('Grants', 5, 'sub', 14);
                            $grants = $PERSON->GetRecord('grants');
                            $table = array(
                                'prototype' => array(
                                    'edit' => array(
                                        'dlgname' => 'editpersongrant',
                                        'params' => array('PersonID' => $personid), 
                                    ),
                                    'del' => array(
                                        'exec' => 'delpersongrant',
                                        'params' => array('PersonID' => $personid), 
                                        'title' => 'Remove Grant entry',
                                        'message' => 'Are you sure you want to remove this grant record?'
                                    ),
                                    'reload' => array('do' => $do, 'personid' => $personid, 'tabid' => $_GET['tabid']),
                                ),
                                'header' => FALSE,
                                'striped' => TRUE,
                                'condensed' => TRUE,
                                'borders' => 'none',
                                'responsive' => FALSE,
                                'valign' => 'centre',
                                'margin' => TRUE,
                                'columns' => array(
                                    array(
                                        'field' => array('name' => 'GrantName', 'type' => 'string'),
                                        'function' => 'stdTableItem'
                                    ),
                                    array(
                                        'field' => array('name' => 'GrantAwarded', 'type' => 'string'),
                                        'function' => 'stdTableItem'
                                    ),
                                    array(
                                        'field' => array('name' => 'GrantActions', 'type' => 'control'),
                                        'function' => 'stdTableItem'
                                    ),
                                ),
                            );
                            ButtonGroup(stdTableButtons( $table, 
                                array(
                                    'edit' => array('icon' => 'fa-plus-square', 'caption' => 'Add Grant', 'tooltip' => 'Add Grant record')
                                )), FALSE, null, 15, FALSE);
                            StaticTable($grants, $table, array(), 15);                        
                        Div(null, 13);
                        Div(array('class' => 'col-lg-7'), 13);
                            SimpleHeading('Committees', 5, 'sub', 14);
                            $committees = $PERSON->GetRecord('committees');
                            $table = array(
                                'prototype' => array(
                                    'edit' => array(
                                        'dlgname' => 'editcommitteeitem',
                                        'params' => array('PersonID' => $personid), 
                                    ),
                                    'endterm' => array(
                                        'dlgname' => 'endcommitteeterm',
                                        'params' => array('PersonID' => $personid), 
                                    ),
                                    'changerole' => array(
                                        'dlgname' => 'changecommitteerole',
                                        'params' => array('PersonID' => $personid), 
                                    ),
                                    'del' => array(
                                        'exec' => 'delcommitteeitem',
                                        'params' => array('PersonID' => $personid), 
                                        'title' => 'Remove Committee entry',
                                        'message' => 'Only entries added in error should be removed from committees. To end a committee term, use the corresponding function instead. Do you want to continue?'
                                    ),
                                    'reload' => array('do' => $do, 'personid' => $personid, 'tabid' => $_GET['tabid']),
                                ),
                                'header' => !empty($committees),
                                'striped' => FALSE,
                                'condensed' => TRUE,
                                'borders' => 'none',
                                'responsive' => FALSE,
                                'valign' => 'centre',
                                'margin' => TRUE,
                                'columns' => array(
                                    array(
                                        'field' => array('name' => 'CommitteeName', 'type' => 'string'),
                                        'function' => 'committeeTableItem'
                                    ),
                                    array(
                                        'caption' => 'Role',
                                        'field' => array('name' => 'Role', 'type' => 'string'),
                                        'function' => 'committeeTableItem'
                                    ),
                                    array(
                                        'caption' => 'From',
                                        'field' => array('name' => 'StartDate', 'type' => 'date', 'format' => 'j M Y'),
                                        'function' => 'committeeTableItem'
                                    ),
                                    array(
                                        'caption' => 'Until',
                                        'field' => array('name' => 'EndDate', 'type' => 'date', 'format' => 'j M Y'),
                                        'function' => 'committeeTableItem'
                                    ),
                                    array(
                                        'caption' => 'Status',
                                        'field' => array('name' => 'Status', 'type' => 'string'),
                                        'function' => 'committeeTableItem'
                                    ),
                                    array(
                                        'caption' => 'Actions',
                                        'field' => array('name' => 'CommitteeActions', 'type' => 'control'),
                                        'function' => 'committeeTableItem'
                                    ),
                                ),
                            );
                            ButtonGroup(stdTableButtons( $table, 
                                array(
                                    'edit' => array('icon' => 'fa-plus-square', 'caption' => 'Add to Committee', 'tooltip' => 'Add to Committee')
                                )), FALSE, null, 15, FALSE);
                            StaticTable($committees, $table, array(), 15);                        
                        Div(null, 13);
                        Div(null, 12);
                        $actiongroups = new crmActionGroups($SYSTEM_SETTINGS['Database']);
                        if($actiongroups->Count > 0) {
                            Div(array('class' => 'row'), 12);
                            foreach($actiongroups->Groups AS $group) {
                                Div(array('class' => 'col-sm-4'), 13);
                                SimpleHeading($group->Name, 5, 'sub', 14);
                                $fieldsets = array(array('fields' => array(
                                    array('name' => 'PersonID', 'kind' => 'hidden'),
                                    array('name' => 'ActionGroupID', 'kind' => 'hidden'),
                                    array('name' => 'ActionGroupName', 'kind' => 'hidden'),
                                    array('name' => 'ActionGroupItemID[]', 'caption' => 'Make a selection:', 'kind' => 'control', 'type' => 'multi', 'options' => $group->GetItems(), 'selected' => $group->GetSelected($personid, TRUE), 'allowempty' => TRUE),
                                )));
                                $formid = 'frmActionGroup'.$group->ActionGroupID;
                                $formitem = array(
                                    'id' => $formid, 'style' => 'vertical',
                                    'onsubmit' => "submitForm( '{$formid}', '/syscall.php?do=savePersonActionGroupItems', { parseJSON: true, defSuccessDlg: true, defErrorDlg: true } ); return false;",
                                    'buttons' => DefFormButtons("Apply"),
                                    'datasource' => array(
                                        'PersonID' => $personid,
                                        'ActionGroupID' => $group->ActionGroupID,
                                        'ActionGroupName' => $group->Name
                                    ),
                                    'fieldsets' => $fieldsets, 'borders' => FALSE
                                );
                                Form($formitem);
                                jsFormValidation($formitem['id'], TRUE, 6);
                                Div(null, 13);
                            }
                            Div(null, 12);
                        }
                        Div(array('class' => 'row'), 12);
                        Div(array('class' => 'col-xs-12'), 13);
                        SimpleHeading('Publications', 5, 'sub', 14);
                        PublicationTable($PERSON, 14);
                        Div(null, 13);
                        Div(null, 12);                                                
                        break;
                    case 'tab-documents':
                        SimpleHeading('Documents', 4, 'sub', 11);
                        DocumentTable($PERSON);                       
                        break;
                    case 'tab-notes':
                        SimpleHeading('Notes', 4, 'sub', 11);
                        $buttonitems = array();
                        $buttonitems[] = array(
                            'icon' => 'fa-plus-square', 'caption' => 'Add new Note', 'colour' => 'primary', 'tooltip' => 'Add a new note to this record', 'iconalign' => 'left',
                            'script' => "OpenDialog('editnote', { large: true, urlparams: { PersonID: {$PERSON->PersonID} } } )"
                        );
                        ButtonGroup($buttonitems, FALSE, NULL, 11);
                        Div(array('id' => 'noteslist'), 11);
                        NotesList($PERSON);
                        Div(null, 11);
                        break;
                    case 'tab-workflow':
                        SimpleHeading('Workflow', 4, 'sub', 11);
                        WorkflowTable($PERSON);
                        break;
                    case 'tab-settings':
                        $personal = $PERSON->GetRecord('personal', TRUE);
                        SimpleHeading('Account and Settings', 4, 'sub', 11);
                        $datasource = array(
                            array('caption' => 'Record Created', 'value' => date('Y-m-d H:i:s', strtotime($personal['Created'].' UTC'))),
                        );
                        StaticTable($datasource, $summaryTable, array(), 12);
                        Div(array('class' => 'row'), 12);
                        Div(array('class' => 'col-md-6'), 13);
                        SimpleHeading('Nucleus User Account', 5, 'sub', 12);
                        if($personal['CanLogin']) {
                            $permissions = $PERSON->GetPermissionSummary();
                            $p = array_filter(array_merge($permissions['groups'], $permissions['permissions']));
                            $buttonitems = array();
                            $buttonitems[] = array(
                                'icon' => 'gi-message_lock', 'caption' => 'Reset Password', 'colour' => 'warning', 'tooltip' => 'Reset the password', 'iconalign' => 'left',
                                'script' => "execSyscall('/syscall.php?do=resetaccountpw', { parseJSON: true, defErrorDlg: true, urlparams: { personid: {$PERSON->PersonID} }, cbSuccess: function(){ ReloadTab('{$_GET['tabid']}', 'sidebar_recinfo'); dlgDataSaved('The password has been reset'); } } )",
                                'disabled' => !$PERSON->AllowInteraction
                            );
                            if($personal['FailCount'] >= intval($SYSTEM_SETTINGS['Security']['MaxFailCount'])) {
                                $buttonitems[] = array(
                                    'icon' => 'gi-unlock', 'caption' => 'Unlock Account', 'colour' => 'info', 'tooltip' => 'Unlock this Account', 'iconalign' => 'left',
                                    'script' => "execSyscall('/syscall.php?do=unlockaccount', { parseJSON: true, defErrorDlg: true, urlparams: { loginid: {$personal['LoginID']}, personid: {$PERSON->PersonID} }, cbSuccess: function(){ ReloadTab('{$_GET['tabid']}', 'sidebar_recinfo'); } } )",
                                    'disabled' => !$PERSON->AllowInteraction
                                );
                            } else {
                                $buttonitems[] = array(
                                    'icon' => 'gi-lock', 'caption' => 'Lock Account', 'colour' => 'danger', 'tooltip' => 'Lock this Account', 'iconalign' => 'left',
                                    'script' => "execSyscall('/syscall.php?do=lockaccount', { parseJSON: true, defErrorDlg: true, urlparams: { loginid: {$personal['LoginID']}, personid: {$PERSON->PersonID} }, cbSuccess: function(){ ReloadTab('{$_GET['tabid']}', 'sidebar_recinfo'); } } )",
                                    'disabled' => !$PERSON->AllowInteraction
                                );
                            }
                            ButtonGroup($buttonitems, FALSE, NULL, 13);
                            $datasource = array(
                                array(
                                    'caption' => 'Authentication',
                                    'value' => FmtText((!empty($personal['Deceased']) ? "<danger>Disabled</danger>"  
                                                                              : ucfirst($personal['Method'])
                                                                               .(strpos($personal['Flags'], 'noautolock') !== FALSE ? ", <primary>No Locking</primary>": "")
                                                                               .(($personal['FailCount'] >= intval($SYSTEM_SETTINGS['Security']['MaxFailCount'])) && (strpos($personal['Flags'], 'noautolock') === FALSE) ? "_<danger>(locked)</danger>" : "")
                                    ))
                                ),
                                array('caption' => 'Last seen', 'value' => FmtText((empty($personal['LastAttempt']) ? '<muted>Never</muted>' : date('Y-m-d H:i:s', strtotime($personal['LastAttempt'].' UTC'))))),
                                array('caption' => 'Last result', 'value' => FmtText((empty($personal['LastAttempt']) ? '<muted>N/A</muted>' : ($personal['FailCount'] > 0 ? "<danger>Failed</danger>" : "<success>Success</success>")))),
                                array('caption' => 'Password Age', 'value' => FmtText(SinPlu($personal['PasswordAge'], "day"))),
                                array('caption' => 'Last Password change', 'value' => FmtText(date('Y-m-d H:i:s', strtotime($personal['LastChanged'].' UTC')))),
                                array('caption' => 'Password expires', 'value' => FmtText(date('Y-m-d H:i:s', strtotime($personal['LastChanged'].' UTC +'.$SYSTEM_SETTINGS["Security"]['PasswordExpiry'].' DAY')))),
                                array('caption' => 'Permissions', 'value' => implode(', ', $p)),
                            );
                            if($personal['LoginID'] === $AUTHENTICATION['Person']['LoginID']) {
                                $datasource[] = array('caption' => 'Session expires', 'value' => FmtText(date('Y-m-d H:i:s', strtotime($AUTHENTICATION['Expires'].' UTC'))));
                            }
                            StaticTable($datasource, $summaryTable, array(), 9);
                        } else {
                            Para(array('well' => 'small'), 12);
                            echo str_repeat("\t", 13)."This person does not have a Nucleus account.";
                            Para(null, 12);
                            Button(array(
                                'caption' => 'Create Account',
                                'icon' => 'gi-power',
                                'iconalign' => 'left',
                                'colour' => 'info',
                                'script' => "execSyscall('/syscall.php?do=createaccount', { parseJSON: true, defErrorDlg: true, urlparams: { personid: {$PERSON->PersonID} }, cbSuccess: function(){ ReloadTab('{$_GET['tabid']}', 'sidebar_recinfo'); } } )",
                                'disabled' => !$PERSON->AllowInteraction 
                            ), FALSE, 13);
                        }
                        Div(null, 13);
                        Div(array('class' => 'col-md-6'), 13);
                        if(!$personal['CanLogin']) {
                            SimpleHeading('Portal Account', 5, 'sub', 12);
                            $buttonitems = array();
                            $buttonitems[] = array(
                                'icon' => 'gi-message_lock', 'caption' => 'Reset Password', 'colour' => 'warning', 'tooltip' => 'Reset the password', 'iconalign' => 'left',
                                'script' => "execSyscall('/syscall.php?do=resetportalpw', { parseJSON: true, defErrorDlg: true, urlparams: { personid: {$PERSON->PersonID} }, cbSuccess: function(){ ReloadTab('{$_GET['tabid']}'); dlgBGProcessStarted('The password has been reset'); } } )",
                                'disabled' => !$PERSON->AllowInteraction
                            );
                            if($personal['FailCount'] >= intval($SYSTEM_SETTINGS['Security']['MaxFailCount'])) {
                                $buttonitems[] = array(
                                    'icon' => 'gi-unlock', 'caption' => 'Unlock Account', 'colour' => 'info', 'tooltip' => 'Unlock this Account', 'iconalign' => 'left',
                                    'script' => "execSyscall('/syscall.php?do=unlockportalpw', { parseJSON: true, defErrorDlg: true, urlparams: { personid: {$PERSON->PersonID} }, cbSuccess: function(){ ReloadTab('{$_GET['tabid']}'); } } )",
                                    'disabled' => !$PERSON->AllowInteraction
                                );
                            } else {
                                $buttonitems[] = array(
                                    'icon' => 'gi-lock', 'caption' => 'Lock Account', 'colour' => 'danger', 'tooltip' => 'Lock this Account', 'iconalign' => 'left',
                                    'script' => "execSyscall('/syscall.php?do=lockportalpw', { parseJSON: true, defErrorDlg: true, urlparams: { personid: {$PERSON->PersonID} }, cbSuccess: function(){ ReloadTab('{$_GET['tabid']}'); } } )",
                                    'disabled' => !$PERSON->AllowInteraction
                                );
                            }
                            ButtonGroup($buttonitems, FALSE, NULL, 13);
                            $datasource = array(
                                array(
                                    'caption' => 'Status',
                                    'value' => FmtText((!empty($personal['Deceased']) 
                                                        ? "<danger>Disabled</danger>"  
                                                        : (($personal['FailCount'] >= intval($SYSTEM_SETTINGS['Security']['MaxFailCount'])) && (strpos($personal['Flags'], 'noautolock') === FALSE) ? "<danger>Locked</danger>" : "<info>Active</info>")
                                    ))
                                ),
                                //array('caption' => 'Last seen', 'value' => FmtText((empty($personal['LastAttempt']) ? '<muted>Never</muted>' : date('Y-m-d H:i:s', strtotime($personal['LastAttempt'].' UTC'))))),
                                array('caption' => 'Last seen', 'value' => FmtDate($personal['LastAttempt'])),
                                array('caption' => 'Last result', 'value' => FmtText((empty($personal['LastAttempt']) ? '<muted>N/A</muted>' : ($personal['FailCount'] > 0 ? "<danger>Failed</danger>" : "<success>Success</success>")))),
                                array('caption' => 'Password Age', 'value' => FmtText(SinPlu($personal['PasswordAge'], "day"))),
//                                array('caption' => 'Last Password change', 'value' => FmtText(date('Y-m-d H:i:s', strtotime($personal['LastChanged'].' UTC')))),
//                                array('caption' => 'Password expires', 'value' => FmtText(date('Y-m-d H:i:s', strtotime($personal['LastChanged'].' UTC +'.$SYSTEM_SETTINGS["Security"]['PasswordExpiry'].' DAY')))),
                                array('caption' => 'Last Password change', 'value' => FmtDate($personal['LastChanged'])),
                                array('caption' => 'Password expires', 'value' => FmtDate($personal['LastChanged'], array('interval' => $SYSTEM_SETTINGS["Security"]['PasswordExpiry'].' DAY', 'txtempty' => 'N/A'))),
                            );
                            StaticTable($datasource, $summaryTable, array(), 9);
                        }
                        Div(null, 13);
                        Div(null, 12);                        
                        break;                    
                    case 'tab-history':
                        SimpleHeading('History', 4, 'sub', 11);
                        ImportWarning($PERSON);
                        HistoryTable($PERSON);                        
                        break;
                    case 'tab-media':
                        call_user_func('frmPersonMedia', $PERSON);
                        break;
                }
            }
            break;
        case 'tab_organisation':
            if (CheckRequiredParams(array('organisationid' => FALSE, 'tabid' => FALSE), $_GET)) {
                $organisationid = intval($_GET['organisationid']);
                $ORGANISATION = new crmOrganisation($SYSTEM_SETTINGS['Database'], $organisationid);
                switch($_GET['tabid']) {
                    case 'tab-contact':
                        SimpleHeading('Contact', 4, 'sub', 11);
                        Div(array('class' => 'row'), 12);
                        Div(array('class' => 'col-lg-6'), 13);
                        //Email placeholder
                        Div(null, 13);
                        Div(array('class' => 'col-lg-6'), 13);
                        SimpleHeading('Postal Address', 5, 'sub', 14);
                        $addresses = $ORGANISATION->GetRecord('address');
                        $table = array(
                            'prototype' => array(
                                'edit' => array(
                                    'dlgname' => 'editaddress',
                                    'params' => array('OrganisationID' => $organisationid), 
                                ),
                                'del' => array(
                                    'exec' => 'deladdress',
                                    'params' => array('OrganisationID' => $organisationid), 
                                    'title' => 'Delete Address',
                                    'message' => 'Are you sure you want to delete this address?'
                                ),
                                'reload' => array('do' => $do, 'organisationid' => $organisationid, 'tabid' => $_GET['tabid']),
                            ),
                            'header' => FALSE,
                            'striped' => TRUE,
                            'condensed' => TRUE,
                            'borders' => 'none',
                            'responsive' => FALSE,
                            'valign' => 'centre',
                            'margin' => TRUE,
                            'columns' => array(
                                array(
                                    'field' => array('name' => 'Address', 'type' => 'address'),
                                    'function' => 'stdTableItem'
                                ),
                                array(
                                    'field' => array('name' => 'AddressActions', 'type' => 'control'),
                                    'function' => 'stdTableItem'
                                ),
                            ),
                        );                        
                        ButtonGroup(stdTableButtons( $table, 
                            array(
                                'edit' => array('icon' => 'fa-plus-square', 'caption' => 'Add Address', 'tooltip' => 'Add a new postal address')
                            )), FALSE, null, 15, FALSE);
                        StaticTable($addresses, $table, array(), 15);
                        Div(null, 13);
                        Div(null, 12);
                        Div(array('class' => 'row'), 12);
                        Div(array('class' => 'col-lg-6'), 13);
                        SimpleHeading('Phone', 5, 'sub', 14);
                        $phones = $ORGANISATION->GetRecord('phone');
                        $table = array(
                            'prototype' => array(
                                'edit' => array(
                                    'dlgname' => 'editphone',
                                    'params' => array('OrganisationID' => $organisationid), 
                                ),
                                'del' => array(
                                    'exec' => 'delphone',
                                    'params' => array('OrganisationID' => $organisationid), 
                                    'title' => 'Delete Phone',
                                    'message' => 'Are you sure you want to delete this phone number?'
                                ),
                                'reload' => array('do' => $do, 'organisationid' => $organisationid, 'tabid' => $_GET['tabid']),
                            ),
                            'header' => FALSE,
                            'striped' => TRUE,
                            'condensed' => TRUE,
                            'borders' => 'none',
                            'responsive' => FALSE,
                            'valign' => 'centre',
                            'margin' => TRUE,
                            'columns' => array(
                                array(
                                    'field' => array('name' => 'PhoneNo', 'type' => 'phone'),
                                    'function' => 'stdTableItem'
                                ),
                                array(
                                    'field' => array('name' => 'PhoneDescription', 'type' => 'string'),
                                    'function' => 'stdTableItem'
                                ),
                                array(
                                    'field' => array('name' => 'PhoneActions', 'type' => 'control'),
                                    'function' => 'stdTableItem'
                                ),
                            ),
                        );
                        ButtonGroup(stdTableButtons($table, 
                            array(
                                'edit' => array('icon' => 'fa-plus-square', 'caption' => 'Add Phone', 'tooltip' => 'Add a new phone number')
                            )), FALSE, null, 15, FALSE);
                        StaticTable($phones, $table, array(), 15);
                        Div(null, 13);
                        Div(array('class' => 'col-lg-6'), 13);
                        SimpleHeading('Web and Social', 5, 'sub', 14);
                        $online = $ORGANISATION->GetRecord('online');
                        $table = array(
                            'prototype' => array(
                                'edit' => array(
                                    'dlgname' => 'editonline',
                                    'params' => array('OrganisationID' => $organisationid), 
                                ),
                                'del' => array(
                                    'exec' => 'delonline',
                                    'params' => array('OrganisationID' => $organisationid), 
                                    'title' => 'Delete',
                                    'message' => 'Are you sure you want to delete this online item?'
                                ),
                                'reload' => array('do' => $do, 'organisationid' => $organisationid, 'tabid' => $_GET['tabid']),
                            ),
                            'header' => FALSE,
                            'striped' => TRUE,
                            'condensed' => TRUE,
                            'borders' => 'none',
                            'responsive' => FALSE,
                            'valign' => 'centre',
                            'margin' => TRUE,
                            'columns' => array(
                                array(
                                    'field' => array('name' => 'Online', 'type' => 'url'),
                                    'function' => 'stdTableItem'
                                ),
                                array(
                                    'field' => array('name' => 'OnlineActions', 'type' => 'control'),
                                    'function' => 'stdTableItem'
                                ),
                            ),
                        );
                        ButtonGroup(stdTableButtons($table, 
                            array(
                                'edit' => array('icon' => 'fa-plus-square', 'caption' => 'Add Item', 'tooltip' => 'Add a new item')
                            )), FALSE, null, 15, FALSE);
                        StaticTable($online, $table, array(), 15);
                        Div(null, 13);
                        Div(null, 12);
                        break;
                    case 'tab-finance':
                        SimpleHeading('Finance', 4, 'sub', 11);
                        break;
                    case 'tab-connections':
                        SimpleHeading('Engagement', 4, 'sub', 11);
                        break;
                    case 'tab-documents':
                        SimpleHeading('Documents', 4, 'sub', 11);
                        DocumentTable($ORGANISATION);
                        break;
                    case 'tab-notes':
                        SimpleHeading('Notes', 4, 'sub', 11);
                        $buttonitems = array();
                        $buttonitems[] = array(
                            'icon' => 'fa-plus-square', 'caption' => 'Add new Note', 'colour' => 'primary', 'tooltip' => 'Add a new note to this record', 'iconalign' => 'left',
                            'script' => "OpenDialog('editnote', { large: true, urlparams: { OrganisationID: {$ORGANISATION->OrganisationID} } } )"
                        );
                        ButtonGroup($buttonitems, FALSE, NULL, 11);
                        Div(array('id' => 'noteslist'), 11);
                        NotesList($ORGANISATION);
                        Div(null, 11);
                        break;
                    case 'tab-workflow':
                        SimpleHeading('Workflow', 4, 'sub', 11);
                        WorkflowTable($ORGANISATION);
                        break;
                    case 'tab-settings':
                        break;                    
                    case 'tab-history':
                        SimpleHeading('History', 4, 'sub', 11);
                        ImportWarning($ORGANISATION);
                        HistoryTable($ORGANISATION);
                        break;
                }
            }
            break;
        case 'reloadnoteslist':
            $RECORD = GetParentRecord();
            NotesList($RECORD);
            break;
        case 'sidebar_ddjobhistory':
            if (CheckRequiredParams(array('directdebitjobid' => FALSE), $_GET)) {
                stdTitleBlock('Job History', $do, stdSidebarMenuitems());
                Div(array('class' => array('block-content')), 8);
                $JOB = new crmDirectDebitJob('eventsFinance', $SYSTEM_SETTINGS['Database'], $_GET['directdebitjobid'], $SYSTEM_SETTINGS['Finance']['DirectDebit']);
                if($JOB->Found) {
                    $history = $JOB->History();
                    $datasource = array();
                    foreach($history AS $row) {
                        $datasource[] = array('caption' => date('Y-m-d H:i:s', strtotime($row['Recorded'].'UTC')), 'value' => ToolTip($row['Description'], $row['Source'], 'left'));
                    }
                    StaticTable($datasource, $summaryTable, array(), 9);
                }
                Div(null, 8); //content block
                Div(null, 7); //container div
            }
            break;
        case 'sidebar_ddjobfiles':
            if (CheckRequiredParams(array('directdebitjobid' => FALSE), $_GET)) {
                stdTitleBlock('Job Files', $do, stdSidebarMenuitems());
                Div(array('class' => array('block-content')), 8);
                $JOB = new crmDirectDebitJob('eventsFinance', $SYSTEM_SETTINGS['Database'], $_GET['directdebitjobid'], $SYSTEM_SETTINGS['Finance']['DirectDebit']);
                if($JOB->Found) {
                    $files = $JOB->Files();
                    $table = array(
                        'header' => FALSE,
                        'striped' => TRUE,
                        'condensed' => TRUE,
                        'borders' => 'none',
                        'responsive' => FALSE,
                        'valign' => 'centre',
                        'margin' => FALSE,
                        'columns' => array(
                            array(
                                'field' => array('name' => 'DisplayName', 'type' => 'control'),
                                'function' => 'docTableItem'
                            ),
                            array(
                                'field' => array('name' => 'DocumentActions', 'type' => 'control'),
                                'function' => 'docTableItem'
                            ),
                        ),
                    );
/*        'DisplayName' => $icon.'&#8200;'.LinkTo($data['DisplayName'], array('script' => "DownloadDocument({$data['DocumentID']});", 'urlcolour' => $data['TextColour']), $data['ToolTip']),
        'FileType' => (!empty($data['DocumentIcon']) ? AdvIcon(array('icon' => $data['DocumentIcon'], 'colour' => $data['TextColour'], 'tooltip' => $data['ToolTip'], 'fixedwidth' => TRUE)).'&#8200;' : "").ToolTip($data['FileType'], $data['ToolTip'], 'left', $data['TextColour']),
        '3' => ButtonGroup($BUTTONGROUP, FALSE, null, 0, TRUE),*/
                    StaticTable($files, $table, array(), 15);
                }                
                Div(null, 8); //content block
                Div(null, 7); //container div                
            }
            break;
        case 'sidebar_recentitems':
            stdTitleBlock('Most Recent', $do, stdSidebarMenuitems());
            Div(array('class' => array('block-content')), 8);
            
            Div(null, 8); //content block
            Div(null, 7); //container div            
            break;
        case 'sidebar_membership':
            if (CheckRequiredParams(array('personid' => FALSE), $_GET)) {
                if(!isset($PERSON)) {
                    $PERSON = new crmPerson('eventsPerson', $SYSTEM_SETTINGS['Database'], intval($_GET['personid']), $SYSTEM_SETTINGS["Membership"]);
                }
                $personal = $PERSON->GetRecord('personal');
                if(empty($personal['Deceased'])) {
                    if(empty($_GET['nochrome'])) {
                        stdTitleBlock('Membership', 'sidebar_membership', stdSidebarMenuitems());
                        Div(array('class' => array('block-content')), 8);
                    }
                    $datasource = array();
                    if($personal['IsMember']) {
                        $datasource[] = array('caption' => 'Status', 'value' => FmtText($personal['MSFmt'].$personal['MSText'].CloseFormattingString($personal['MSFmt'])));
                        if(!empty($personal['MSMemberSince'])) {
                            $datasource[] = array('caption' => 'Member Since', 'value' => date('j F Y', strtotime($personal['MSMemberSince'])));
                        }
/*                        if(!empty($personal['MSNextRenewal'])) {
                            $datasource[] = array('caption' => 'Next Renewal', 'value' => date('j F Y', strtotime($personal['MSNextRenewal'])).($personal['NoRenewal'] ? FmtText(', <warning><b>Do not renew</b></warning>') : (!empty($personal['RenewalCycle']) ? ", <span class=\"text-".($personal['RenewalCycle'] == 1 ? "warning" : "danger")."\">".$personal['RenewalCycleTxt']."</span>" : '')));
                        }
                        if(!empty($personal['MSLastReminder'])) {
                            $datasource[] = array('caption' => 'Last Reminder', 'value' => date('j F Y', strtotime($personal['MSLastReminder'])));
                        }*/
                        $transfer = $PERSON->GetOpenTransfer('members');
                        if(!empty($transfer)) {
                            $datasource[] = array('caption' => '', 'value' => FmtText('<b><info>Transfer pending</info></b>'));
                        }
                        if($personal['NoRenewal']) {
                            $datasource[] = array('caption' => '', 'value' => FmtText('<warning><b>Do not renew</b></warning>'));
                        } elseif(!empty($personal['RenewalCycle']) || $personal['HasRenewal']) {
                            $renewal = $PERSON->RenewalSettings();
                            $datasource[] = array('caption' => 'Next Renewal', 'value' => date('j F Y', strtotime($personal['MSNextRenewal'])).(!empty($personal['RenewalCycle']) ? ", <span class=\"text-".($personal['RenewalCycle'] == 1 ? "warning" : "danger")."\">".$personal['RenewalCycleTxt']."</span>" : ''));
                            if($renewal['ChangeOfGrade']) {
                                $datasource[] = array('caption' => 'Renewal '.$SYSTEM_SETTINGS['Membership']['GradeCaption'], 'value' => FmtText('<b><info>'.$renewal['GradeCaption'].'</info></b>'));
                            }
                            if($renewal['MSFree'] <> $personal['MSFree']) {
                                $datasource[] = array('caption' => '', 'value' => FmtText(($renewal['MSFree'] ? '<info>Will renew as <b>Free</b> Membership</info>' : '<warning>Will <b>not</b> renew as Free Membership</warning>')));
                            }
                            if($renewal['HasTransaction']) {
                                $datasource[] = array(
                                    'caption' => 'Invoiced',
                                    'value' => (empty($_GET['nochrome']) 
                                                ? '<b>'.FmtText(InvoiceItemValueAsFmtString($renewal)).'</b>'
                                                  .($renewal['CanExplain'] ? '&ensp;'.LinkTo('<info>(explain)</info>', array('script' => "OpenDialog('explainfee', { urlparams: { invoiceitemid: {$renewal['InvoiceItemID']} } });")) : '').', '
                                                  .LinkTo($renewal['InvoiceCaption'], array('url' => "/record.php?rec=invoice&invoiceid=".$renewal['InvoiceID'], 'target' => (empty($AUTHENTICATION['Settings']['OpenNewWindow']) ? null : 'newwindow')))
                                                : '<b>'.InvoiceItemValueAsFmtString($renewal).'</b>, '.$renewal['InvoiceCaption']
                                    ) 
                                );
                            } else {
                                $msfees = new crmMSFees($SYSTEM_SETTINGS['Database'], $SYSTEM_SETTINGS['Membership']['GradeCaption']);
                                $params = array(
                                    'ISO4217' => $renewal['ISO4217'],                        
                                    'MSGradeID' => $renewal['MSGradeID'],
                                    'ISO3166' => $renewal['ISO3166'],
                                    'ForDate' => (!empty($renewal['MSNextRenewal']) ? $renewal['MSNextRenewal'] : null),
                                    'IsDD' => $PERSON->HasValidDDI(),
                                    'Free' => ($renewal['MSFree'] ? TRUE : FALSE),
                                );
                                $fee = $msfees->CalculateFee($params);
                                $params['feeobject'] = 'crmMSFees';
                                $datasource[] = array('caption' => 'Fee', 'value' => ($fee->HasError ? FmtText('<danger>Error</danger>') : $fee->Price->AsString()).'&ensp;'.LinkTo('(explain)', array('script' => "OpenDialog('explainfee', { urlparams: ".OutputArrayAsJSObject($params)." } )")));
                            }
                        } elseif(!empty($personal['MSNextRenewal'])) {
                            $datasource[] = array('caption' => 'Next Renewal', 'value' => date('j F Y', strtotime($personal['MSNextRenewal'])).(!empty($personal['RenewalCycle']) ? ", <span class=\"text-".($personal['RenewalCycle'] == 1 ? "warning" : "danger")."\">".$personal['RenewalCycleTxt']."</span>" : ''));
                        }
                        if(!empty($personal['MSLastReminder'])) {
                            $datasource[] = array('caption' => 'Last Reminder', 'value' => date('j F Y', strtotime($personal['MSLastReminder'])));
                        }                        
                        if(empty($_GET['nochrome']) && empty($personal['RenewalCycle'])) {
                            $msfees = new crmMSFees($SYSTEM_SETTINGS['Database'], $SYSTEM_SETTINGS['Membership']['GradeCaption']);
                            $params = array(
                                'ISO4217' => $personal['ISO4217'],                        
                                'MSGradeID' => $personal['MSGradeID'],
                                'ISO3166' => $personal['ISO3166'],
                                'ForDate' => (!empty($personal['MSNextRenewal']) ? $personal['MSNextRenewal'] : null),
                                'IsDD' => $PERSON->HasValidDDI(),
                                'Free' => ($personal['MSFree'] ? TRUE : FALSE),
                            );
                            $fee = $msfees->CalculateFee($params);
                            $params['feeobject'] = 'crmMSFees';
                            $datasource[] = array('caption' => 'Fee', 'value' => ($fee->HasError ? FmtText('<danger>Error</danger>') : $fee->Price->AsString()).'&ensp;'.LinkTo('(explain)', array('script' => "OpenDialog('explainfee', { urlparams: ".OutputArrayAsJSObject($params)." } )")));
                        }
                    } else {
                        $msapplication = $PERSON->GetOpenApplication();
                        if(!empty($msapplication)) {
                            $datasource[] = array('caption' => 'Status', 'value' => FmtText("<{$msapplication['StageColour']}>Application {$msapplication['StageName']}"."</{$msapplication['StageColour']}> (".$msapplication['GradeCaption'].($msapplication['Paid'] ? ", <b>Paid</b>": "").")"));
                            if($msapplication['HasTransaction']) {
                                $datasource[] = array(
                                    'caption' => 'Invoiced',
                                    'value' => (empty($_GET['nochrome']) 
                                                ? '<b>'.FmtText(InvoiceItemValueAsFmtString($msapplication)).'</b>'
                                                  .($msapplication['CanExplain'] ? '&ensp;'.LinkTo('<info>(explain)</info>', array('script' => "OpenDialog('explainfee', { urlparams: { invoiceitemid: {$msapplication['InvoiceItemID']} } });")) : '').', '
                                                  .LinkTo($msapplication['InvoiceCaption'], array('url' => "/record.php?rec=invoice&invoiceid=".$msapplication['InvoiceID'], 'target' => (empty($AUTHENTICATION['Settings']['OpenNewWindow']) ? null : 'newwindow')))
                                                : '<b>'.InvoiceItemValueAsFmtString($msapplication).'</b>, '.$msapplication['InvoiceCaption']
                                    ) 
                                );
                            } else {
                                $msfees = new crmMSFees($SYSTEM_SETTINGS['Database'], $SYSTEM_SETTINGS['Membership']['GradeCaption']);
                                $params = array(
                                    'ISO4217' => $personal['ISO4217'],
                                    'MSGradeID' => $msapplication['MSGradeID'],
                                    'ISO3166' => $personal['ISO3166'],
                                    //'ForDate' => $msapplication['Created'],
                                    'IsDD' => $PERSON->HasValidDDI(),
                                    'NOY' => $msapplication['NOY'],
                                    'DiscountID' => (!empty($msapplication['DiscountID']) ? $msapplication['DiscountID'] : null),
                                );
                                $fee = $msfees->CalculateFee($params);
                                $params['feeobject'] = 'crmMSFees';
                                $datasource[] = array('caption' => 'Fee', 'value' => ($fee->HasError ? FmtText('<danger>Error</danger>') : $fee->Price->AsString()).'&ensp;'.LinkTo('(explain)', array('script' => "OpenDialog('explainfee', { urlparams: ".OutputArrayAsJSObject($params)." } )")));
                            }
                        } else {
                            $datasource[] = array('caption' => 'Status', 'value' => FmtText($personal['MSFmt'].$personal['MSText'].CloseFormattingString($personal['MSFmt'])));
                            if(empty($personal['FutureMS'])) {
                                $rejoin = $PERSON->GetOpenRejoin('members');
                                if(!empty($rejoin)) {
                                    $datasource[] = array('caption' => '', 'value' => FmtText('<b><info>Rejoin pending</info></b>'));
                                }
                            } else {
                                $datasource[] = array('caption' => 'Pending', 'value' => FmtText('Status change on <b>'.date('j F Y', strtotime($personal['FutureMSChangeDate'].' UTC'))."</b>"));
                            }
                        }
                    }
//                    $datasource[] = array('caption' => 'Payment Method', 'value' => FmtText(($PERSON->HasValidDDI() ? '<success>Direct Debit</success>' : '<info>Standard</info>')));
                    $datasource[] = array(
                        'caption' => 'Payment Method',
                        'value' => ($PERSON->HasValidDDI() 
                                    ? ($personal['ISO4217'] <> 'GBP' ? ToolTip('Direct Debit', 'Invoicing currency is '.$personal['ISO4217'], 'top', 'warning') : FmtText('<success>Direct Debit</success>')) 
                                    : FmtText('<info>Standard</info>')
                        )
                    );
                    if(!empty($personal['MSNumber'])) {
                        $datasource[] = array('caption' => 'Number', 'value' => htmlspecialchars($personal['MSNumber']));
                    }
                    StaticTable($datasource, $summaryTable, array(), 9);
                    if(empty($_GET['nochrome'])) {
                        Div(null, 8); //content block
                        Div(null, 7); //container div 
                    }
                }
            }
            break;
        case 'sidebar_recinfo':
            $RECORD = GetParentRecord();
            if(!empty($RECORD)) {
                $data = $RECORD->GetRecord();
                stdTitleBlock('Record Info', 'sidebar_recinfo', stdSidebarMenuitems());
                Div(array('class' => array('block-content')), 8);
                $datasource = array(
                    array('caption' => 'Record Created', 'value' => FmtText(date('Y-m-d H:i:s', strtotime($data['Created'].' UTC')))),
                );
                if(!empty($data['ImportedFrom'])) {
                    $datasource[] = array('caption' => 'Imported from', 'value' => FmtText($data['ImportedFrom']));
                }
                if (is_a($RECORD, 'crmOrganisation')) {
                    
                } elseif(is_a($RECORD, 'crmPerson')) {
                    $datasource[] = array('caption' => 'Nucleus User', 'value' => ($data['CanLogin'] ? '<success><b>Yes</b></success>' : '<danger><b>No</b></danger>'));
                    if($data['CanLogin']) {
                        $datasource[] = array(
                            'caption' => 'Authentication',
                            'value' => FmtText((!empty($data['Deceased']) ? "<danger>Disabled</danger>"  
                                                                  : ucfirst($data['Method'])
                                                                    .(strpos($data['Flags'], 'noautolock') !== FALSE ? ", <primary>No Locking</primary>": "")
                                                                    .(($data['FailCount'] >= intval($SYSTEM_SETTINGS['Security']['MaxFailCount'])) && (strpos($data['Flags'], 'noautolock') === FALSE) ? "_<danger>(locked)</danger>" : "")
                            ))
                        );
                        $datasource[] = array('caption' => 'Last seen', 'value' => FmtText((empty($data['LastAttempt']) ? '<muted>Never</muted>' : date('Y-m-d H:i:s', strtotime($data['LastAttempt'].' UTC')))));
                        if(!empty($data['LastAttempt'])) {
                            $datasource[] = array('caption' => 'Last result', 'value' => FmtText(($data['FailCount'] > 0 ? "<danger>Failed</danger>" : "<success>Success</success>")));
                        }
                    }
                    $discounts = $RECORD->Discounts;
                    $str = "";
                    if(!empty($discounts)) {
                        foreach($discounts AS $discount) {
                            $str .= (empty($str) ? "" : "; ").'<info>'.$discount['DiscountCode'].'&#8200;('.TextEllipsis($discount['Description']).')</info>';
                        }
                        $datasource[] = array('caption' => 'Queued discounts', 'value' => FmtText($str));
                    }
                }
                StaticTable($datasource, $summaryTable, array(), 9);
                Div(null, 8); //content block
                Div(null, 7); //container div                 
            }
            break;
        case 'sidebar_warnings':
            $RECORD = GetParentRecord();
            if(!empty($RECORD)) {
                $data = $RECORD->GetRecord();
                if(empty($data['Deceased']) && empty($data['Dissolved'])) {
                    if(!empty($data['DoNotContact'])) {
                        AlertBox(
                            array(
                                'type' => 'danger',
                                'title' => '<b>Do not Contact!</b>',
                                'class' => 'donotcontact',
                                'items' => array(
                                    array(
                                        'type' => 'item',
                                        'caption' => "<b>Notice:</b> This {$RECORD->Noun} has instructed us that they no longer want to be contacted by us."
                                    )
                                )
                            )
                        );
                    } else {
                        if(!empty($data['NoMarketing'])) {
                            AlertBox(array(
                                'type' => 'warning',
                                'title' => '<b>No Marketing!</b>',
                                'class' => 'nomarketing',
                                'items' => array(array(
                                        'type' => 'item',
                                        'caption' =>  "<b>Notice:</b> This {$RECORD->Noun} has the <b>No Marketing</b> flag enabled."
                                ))
                            ));
                        }
                        $cmstats = $RECORD->ContactMethodStats();
                        if (is_a($RECORD, 'crmOrganisation') && $cmstats['address'] == 0) {
                            AlertBox(array(
                                'type' => 'warning',
                                'title' => '<b>Missing Contact Details!</b>',
                                'items' => array(array(
                                        'type' => 'item',
                                        'caption' =>  '<b>Warning:</b> There is no postal address stored against this record.'
                                ))
                            ));
                        } elseif(is_a($RECORD, 'crmPerson') && (($cmstats['address'] == 0) || ($cmstats['email'] == 0))) {
                            AlertBox(array(
                                'type' => ($data['IsMember'] || $RECORD->HasOpenApplication(null) ? 'danger' : 'warning'),
                                'title' => '<b>Missing Contact Details!</b>',
                                'items' => array(array(
                                        'type' => 'item',
                                        'caption' =>  '<b>'.($data['IsMember'] || $RECORD->HasOpenApplication(null) ? 'Warning' : 'Notice').':</b> '
                                                           .($cmstats['methods'] == 0 ? 'There is no postal address or email address stored against this record.' : ($cmstats['email'] == 0 ? 'There is no email address stored against this record.' : 'There is no postal address stored against this record.'))
                                ))
                            ));
                        }
                    }
                }
            }
            break;
        case 'newperson':
            ModalHeader('Add Person');
            ModalBody(FALSE);
            $fieldsets = array();
            $genders = new crmGenders($SYSTEM_SETTINGS['Customise']['GenderRequired']);
            $countries = new crmCountries($SYSTEM_SETTINGS["Database"]);
            $fieldsets[] = array('fields' => array(
                array('name' => 'Title', 'caption' => 'Title', 'kind' => 'control', 'type' => 'string', 'required' => TRUE, 'size' => 4, 'hint' => 'Mr, Ms, Mrs, Dr, Professor,...'),
                array('name' => 'Firstname', 'caption' => 'First name', 'kind' => 'control', 'type' => 'string', 'required' => TRUE, 'size' => 6),
                array('name' => 'Middlenames', 'caption' => 'Middle name(s)', 'kind' => 'control', 'type' => 'string', 'size' => 6),
                array('name' => 'Lastname', 'caption' => 'Last name', 'kind' => 'control', 'type' => 'string', 'required' => TRUE, 'size' => 6),
                array('name' => 'Gender', 'caption' => 'Gender', 'kind' => 'control', 'type' => 'combo', 'options' => $genders->Genders, 'required' => $SYSTEM_SETTINGS["Customise"]["GenderRequired"], 'mustnotmatch' => ($SYSTEM_SETTINGS["Customise"]["GenderRequired"] ? 'unknown' : null), 'size' => 4),
                array('name' => 'DOB', 'caption' => 'Date of birth', 'kind' => 'control', 'type' => 'date', 'required' => $SYSTEM_SETTINGS["Customise"]["DOBRequired"], 'showage' => true),
                array('name' => 'ISO3166', 'caption' => 'Country of Residence', 'kind' => 'control', 'type' => 'advcombo', 'required' => TRUE, 'allowempty' => FALSE, 'size' => 6, 'options' => $countries->Countries),
                array('name' => 'ISO4217', 'caption' => 'Invoicing Currency', 'kind' => 'control', 'type' => 'advcombo', 'required' => TRUE, 'allowempty' => FALSE, 'size' => 4, 'options' => $countries->Currencies),
            ));
            $formitem = array(
                'id' => 'frmPersonNew', 'style' => 'standard',
                'datasource' => array('ISO3166' => $countries->DefCountry, 'ISO4217' => $countries->DefCurrency), 'buttons' => array(),
                'fieldsets' => $fieldsets, 'borders' => FALSE
            );
            Form($formitem);            
            ModalBody(TRUE);
            //ModalFooter("frmPersonNew", "/syscall.php?do=addPerson", "function( frmElement, jsonResponse ) { window.location.href = 'https://'+window.location.host.concat('/record.php?rec=person', '&personid=', jsonResponse.personid ); }", "Create Record");                
            NewModalFooter(array(
                'form' => $formitem,
                'url' => '/syscall.php?do=addPerson',
                'onsuccess' => "function( frmElement, jsonResponse ) { window.location.href = 'https://'+window.location.host.concat('/record.php?rec=person', '&personid=', jsonResponse.personid ); }",
                'savecaption' => "Create Record"
            ));
            break;
        case 'neworganisation':
            ModalHeader('Add Organisation');
            ModalBody(FALSE);
            $fieldsets = array();
            $fieldsets[] = array('fields' => array(
                array('name' => 'Ringgold', 'caption' => 'Ringgold ID', 'kind' => 'control', 'type' => 'string', 'size' => 6, 'rightaddon' => array('type' => 'button', 'colour' => 'primary', 'script' => 'RinggoldLookup();', 'icon' => 'fa-search'), 'hint' => 'Enter a Ringgold ID and click the lookup button to automatically retrieve organisational details'),
                array('name' => 'Name', 'caption' => 'Name', 'kind' => 'control', 'type' => 'string', 'required' => TRUE),
                array('name' => 'VATNumber', 'caption' => 'VAT Number', 'kind' => 'control', 'type' => 'string', 'size' => 4),
                array('name' => 'CharityReg', 'caption' => 'Charity Reg.', 'kind' => 'control', 'type' => 'string', 'size' => 4),
            ));
            $formitem = array(
                'id' => 'frmOrganisationNew', 'style' => 'standard',
                'datasource' => array(), 'buttons' => array(),
                'fieldsets' => $fieldsets, 'borders' => FALSE
            );
            Form($formitem);            
            ModalBody(TRUE);
            //ModalFooter("frmOrganisationNew", "/syscall.php?do=addOrganisation", "function( frmElement, jsonResponse ) { window.location.href = 'https://'+window.location.host.concat('/record.php?rec=organisation', '&organisationid=', jsonResponse.organisationid ); }", "Create Record");                
            NewModalFooter(array(
                'form' => $formitem,
                'url' => '/syscall.php?do=addOrganisation',
                'onsuccess' => "function( frmElement, jsonResponse ) { window.location.href = 'https://'+window.location.host.concat('/record.php?rec=organisation', '&organisationid=', jsonResponse.organisationid ); }",
                'savecaption' => "Create Record"
            ));
            break;
        case 'startddemailnotification':
            ModalHeader('Direct Debit Submission');
            ModalBody(FALSE);
            $job = new crmDirectDebitJob('eventsFinance', $SYSTEM_SETTINGS['Database'], intval($_GET['directdebitjobid']), $SYSTEM_SETTINGS['Finance']['DirectDebit']);
            if($job->Found) {
                CheckTZSupport(TRUE, 8);
                if(!empty($JOB->Job['Submitted'])) {
                    SimpleAlertBox('error', '<b>Error!</b> The submission file for this job has already been generated and may therefore have already been sent to your financial institution.');
                } else {
                    if(!empty($job->Job['EmailNotifications'])) {
                        SimpleAlertBox('warning', '<b>Important!</b> You have already notified people of this pending direct debit run. You should notify people once before submitting the file for processing. The Direct Debit process is subject to legal requirements.');
                    }
                    $fieldsets[] = array('fields' => array(
                        array('name' => 'DirectDebitJobID', 'kind' => 'hidden'),
                        array('name' => 'SubmissionDate', 'caption' => 'Submission Date', 'kind' => 'control', 'type' => 'date', 'utcsource' => TRUE, 'showage' => true, 'required' => TRUE, 'hint' => 'Confirm the date you will submit this file to your financial institution. This date will be used for the notifications.'),
                    ));
                    $formitem = array(
                        'id' => 'frmGetDDEmailNotifications', 'style' => 'standard',
                        'datasource' => array('DirectDebitJobID' => $job->DirectDebitJobID, 'SubmissionDate' => $job->Job['PlannedSubmission'], 'ProcessorName' => $SYSTEM_SETTINGS['Finance']['DirectDebit']['ProcessorName']),
                        'buttons' => array(), 'fieldsets' => $fieldsets, 'borders' => FALSE
                    );
                    Form($formitem);
                    ModalBody(TRUE);
                    ModalFooter(
                        null,
                        "LockAndExecute( { cbSuccess: function(){ LoadContent('divDDJobButtons', '/load.php?do=part_ddjobbuttons', { spinner: false, urlparams: objParams }); }, locked: true, form: 'frmGetDDEmailNotifications', modal: true, defErrorDlg: true }, '/syscall.php?do=ddNotifyEmail', { cbSuccess: function() { RefreshDDJob({$job->DirectDebitJobID}); RefreshDataTable(dt_ddjobitems); } } )",
                        null,
                        "Start"
                    );
                }
            } else {
                SimpleAlertBox('error', 'Job not found!');
                ModalBody(TRUE);
                ModalFooter(null);
            }
            break;
        case 'getddsubmission':
            ModalHeader('Direct Debit Submission');
            ModalBody(FALSE);
            $job = new crmDirectDebitJob('eventsFinance', $SYSTEM_SETTINGS['Database'], intval($_GET['directdebitjobid']), $SYSTEM_SETTINGS['Finance']['DirectDebit']);
            if($job->Found) {
                CheckTZSupport(TRUE, 8);
                if(!empty($JOB->Job['Submitted'])) {
                    SimpleAlertBox('warning', '<b>Important!</b> The submission file for this job has already been generated and you may therefore have already sent it to your financial institution. If you continue, a new submission file will be generated. If you submitted a previous file, this may cause discrepancies when processing the results.');
                } elseif(!$job->Job['Notified']) {
                    SimpleAlertBox('warning', '<b>Important!</b> You have not yet notified people of this pending direct debit run. You should notify people before submitting the file for processing. The Direct Debit process is subject to legal requirements.');
                }
                $fieldsets[] = array('fields' => array(
                    array('name' => 'DirectDebitJobID', 'kind' => 'hidden'),
                    array('name' => 'SubmissionDate', 'caption' => 'Submission Date', 'kind' => 'control', 'type' => 'date', 'utcsource' => TRUE, 'showage' => true, 'required' => TRUE, 'hint' => 'Enter the date you will submit this file to your financial institution. This should correspond to the date used for notifications.'),
                    array('name' => 'ProcessorName', 'caption' => 'Submitting to', 'kind' => 'control', 'type' => 'string', 'required' => TRUE, 'hint' => 'Confirm the name of the financial institution that will process this submission file.'),
                ));
                $formitem = array(
                    'id' => 'frmGetDDSubmission', 'style' => 'standard',
                    'datasource' => array('DirectDebitJobID' => $job->DirectDebitJobID, 'SubmissionDate' => $job->Job['PlannedSubmission'], 'ProcessorName' => $SYSTEM_SETTINGS['Finance']['DirectDebit']['ProcessorName']),
                    'buttons' => array(), 'fieldsets' => $fieldsets, 'borders' => FALSE
                );
                Form($formitem);
                ModalBody(TRUE);
/*                ModalFooter(
                    null,
                    "LockAndExecute( { cbSuccess: function(){ LoadContent('divDDJobButtons', '/load.php?do=part_ddjobbuttons', { spinner: false, urlparams: objParams }); }, locked: true, form: 'frmGetDDSubmission', modal: true, defErrorDlg: true }, '/syscall.php?do=createddsubmission', { cbSuccess: function() { RefreshDDJob({$job->DirectDebitJobID}); RefreshDataTable(dt_ddjobitems); } } )",
                    null,
                    "Start"
                );*/
                NewModalFooter(array(
                    'script' => "LockAndExecute( { cbSuccess: function(){ LoadContent('divDDJobButtons', '/load.php?do=part_ddjobbuttons', { spinner: false, urlparams: objParams }); }, locked: true, form: 'frmGetDDSubmission', modal: true, defErrorDlg: true }, '/syscall.php?do=createddsubmission', { cbSuccess: function() { RefreshDDJob({$job->DirectDebitJobID}); RefreshDataTable(dt_ddjobitems); } } )",
                    'savecaption' => "Start"
                ));
//                "function(){ LoadContent('divDDJobButtons', '/load.php?do=part_ddjobbuttons', { spinner: true, urlparams: objParams }); LoadContent('wsSide', 'load.php?do=sidebar_ddjobhistory', { divid: 'sidebar_ddjobhistory', spinner: false, urlparams: objParams }); }", "Start", "function(frmelement) {  };");
            } else {
                SimpleAlertBox('error', 'Job not found!');
                ModalBody(TRUE);
                ModalFooter(null);
            }
            break;
        case 'processddresults':
            ModalHeader('Process Direct Debit Results');
            ModalBody(FALSE);
            $job = new crmDirectDebitJob('eventsFinance', $SYSTEM_SETTINGS['Database'], intval($_GET['directdebitjobid']), $SYSTEM_SETTINGS['Finance']['DirectDebit']);
            if($job->Found) {
                CheckTZSupport(TRUE, 8);
                $fieldsets[] = array('fields' => array(
                    array('name' => 'DirectDebitJobID', 'kind' => 'hidden'),
                    array('name' => 'SubmissionDate', 'caption' => 'Submitted', 'kind' => 'static'),
                    array('name' => 'Failed', 'caption' => 'Failed Items', 'kind' => 'control', 'type' => 'memo', 'hint' => 'Enter references (DD Reference, Account holder, Membership Number) for any failed items, one reference per line'),
                ));
                $formitem = array(
                    'id' => 'frmProcessDDSubmission', 'style' => 'standard',
                    'datasource' => array('DirectDebitJobID' => $job->DirectDebitJobID, 'SubmissionDate' => $job->Job['PlannedSubmission'], 'ProcessorName' => $SYSTEM_SETTINGS['Finance']['DirectDebit']['ProcessorName']),
                    'buttons' => array(), 'fieldsets' => $fieldsets, 'borders' => FALSE
                );
                Form($formitem);
                ModalBody(TRUE);
                ModalFooter(
                    null,
                    "LockAndExecute( { cbSuccess: function(){ LoadContent('divDDJobButtons', '/load.php?do=part_ddjobbuttons', { spinner: false, urlparams: objParams }); }, locked: true, form: 'frmProcessDDSubmission', modal: true, defErrorDlg: true }, '/syscall.php?do=processddsubmission', { cbSuccess: function() { RefreshDDJob({$job->DirectDebitJobID}); RefreshDataTable(dt_ddjobitems); } } )",
                    null,
                    "Start"
                );
            } else {
                SimpleAlertBox('error', 'Job not found!');
                ModalBody(TRUE);
                ModalFooter(null);
            }
            break;
        case 'editddjobsettings':
            ModalHeader('Direct Debit Job');
            ModalBody(FALSE);
            $directdebitjobid = intval($_GET['directdebitjobid']);
            $job = new crmDirectDebitJob('eventsFinance', $SYSTEM_SETTINGS['Database'], $directdebitjobid, $SYSTEM_SETTINGS['Finance']['DirectDebit']);
            if($job->Found) {
                CheckTZSupport(TRUE, 8);
                $fieldsets[] = array('fields' => array(
                    array('name' => 'DirectDebitJobID', 'kind' => 'hidden'),
                    array('name' => 'Description', 'caption' => 'Description', 'kind' => 'control', 'type' => 'memo', 'hint' => 'Providing a description makes it easier to locate your direct debit job afterwards.'),
                    array('name' => 'PlannedSubmission', 'caption' => 'Planned Submission', 'kind' => 'control', 'type' => 'date', 'utcsource' => TRUE, 'showage' => true, 'required' => TRUE, 'hint' => 'This date will be used for the notification emails and letters.'),
                ));
                $formitem = array(
                    'id' => 'frmEditDirectDebitJob', 'style' => 'standard',
                    'datasource' => $job->Job, 'buttons' => array(),
                    'fieldsets' => $fieldsets, 'borders' => FALSE
                );
                Form($formitem);
                ModalBody(TRUE);
                ModalFooter("frmEditDirectDebitJob", "/syscall.php?do=saveddjobsettings", "function( frmElement, jsonResponse ) { RefreshDDJob( {$directdebitjobid} ) }");                
            } else {
                SimpleAlertBox('error', 'Job not found!');
                ModalBody(TRUE);
                ModalFooter(null);
            }
            break;
        case 'editpersonmsitem':
            ModalHeader('Edit Entry');
            ModalBody(FALSE);
            $item = SingleRecord($SYSTEM_SETTINGS['Database'], "SELECT * FROM tblpersonms WHERE PersonMSID = ".intval($_GET['personmsid']));
            if(!empty($item)) {
                $status = new crmMSStatus($SYSTEM_SETTINGS['Database']);
                $grades = new crmMSGrades($SYSTEM_SETTINGS['Database']);
                $flags = array(
                    'election' => 'Election',
                    'transfer' => 'Transfer',
                    'rejoin' => 'Rejoin',
                    'norenewal' => 'No Renewal'
                );
                SimpleAlertBox("warning", "The Membership History is an automated component. <b>It is strongly discouraged from manually editing it.</b> This can cause significant errors and inconsistencies.", 8);
                $fieldsets[] = array('fields' => array(
                    array('name' => 'PersonMSID', 'kind' => 'hidden'),
                    array('name' => 'BeginDate', 'caption' => 'From', 'kind' => 'control', 'type' => 'date', 'utcsource' => TRUE, 'required' => TRUE),
                    array('name' => 'EndDate', 'caption' => 'Until', 'kind' => 'control', 'type' => 'date', 'utcsource' => TRUE),
                    array('name' => 'MSStatusID', 'caption' => 'Status', 'kind' => 'control', 'type' => 'advcombo', 'allowempty' => FALSE, 'options' => $status->GetStatuses(), 'required' => TRUE),
                    array('name' => 'MSGradeID', 'caption' => $SYSTEM_SETTINGS['Membership']['GradeCaption'], 'kind' => 'control', 'type' => 'advcombo', 'allowempty' => TRUE, 'options' => $grades->GetGrades(TRUE, TRUE)),
                    array('name' => 'MSFlags[]', 'caption' => 'Flags', 'kind' => 'control', 'type' => 'multi', 'options' => $flags)
                ));
                $formitem = array(
                    'id' => 'frmEditPersonMSItem', 'style' => 'vertical',
                    'datasource' => $item, 'buttons' => array(),
                    'fieldsets' => $fieldsets, 'borders' => FALSE
                );
                Form($formitem);
                ModalBody(TRUE);
                ModalFooter("frmEditPersonMSItem", "/syscall.php?do=savepersonmsitem", "function( frmElement, jsonResponse ) { RefreshDataTable(dt_person_mshistory); }");                
            } else {
                SimpleAlertBox('error', 'Item not found!');
                ModalBody(TRUE);
                ModalFooter(null);
            }
            break;
        case 'newddjob':
            ModalHeader('New Direct Debit Job');
            ModalBody(FALSE);
            CheckTZSupport(TRUE, 8);
            if (defined('__DEBUGMODE') && __DEBUGMODE) {
                SimpleAlertBox("warning", "The system is running in debug mode. Direct Debit jobs generated in debug mode are intended for testing purposes only and limited to maximum 10 records.", 8);
            }
            $fieldsets[] = array('fields' => array(
                array('name' => 'Description', 'caption' => 'Description', 'kind' => 'control', 'type' => 'memo', 'hint' => 'Providing a description makes it easier to locate your direct debit job afterwards.'),
                array('name' => 'PlannedSubmission', 'caption' => 'Planned Submission', 'kind' => 'control', 'type' => 'date', 'utcsource' => TRUE, 'showage' => true, 'required' => TRUE, 'hint' => 'This date will be used for the notification emails and letters.'),
                array('name' => 'InclDDI', 'tooltip' => 'Include new and changed Direct Debit Instructions', 'type' => 'switch', 'kind' => 'control', 'colour' => 'info', 'hint' => 'Include new/changed Instructions'),
                array('name' => 'InclInvoices', 'tooltip' => 'Include eligible unpaid Invoices', 'type' => 'switch', 'kind' => 'control', 'colour' => 'info', 'hint' => 'Include open Invoices'),
            ));
            $formitem = array(
                'id' => 'frmNewDirectDebitJob', 'style' => 'standard',
                'datasource' => array(
                    'PlannedSubmission' => gmdate('Y-m-d H:i:s' ,strtotime('+14 DAY')),
                    'InclDDI' => 0,
                    'InclInvoices' => 0
                ),
                'buttons' => array(),
                'fieldsets' => $fieldsets, 'borders' => FALSE
            );
            Form($formitem);             
            ModalBody(TRUE);
            ModalFooter("frmNewDirectDebitJob", "/syscall.php?do=createDDJob", "function( frmElement, jsonResponse ) { RefreshDataTable(dt_ddjobs); }", "Create Job");                
            break;
        case 'export_table':
            ModalHeader('Export');
            ModalBody(FALSE);
            $url = "/syscall.php?do=exportDatatable&".substr($_GET['request'], strpos($_GET['request'], "?")+1);
            $fields = array(
                array('name' => 'description', 'kind' => 'hidden'),
                array('name' => 'Purpose', 'caption' => 'Purpose', 'kind' => 'control', 'type' => 'string', 'hint' => 'Explain the reason for exporting this data', 'required' => TRUE),
                array('name' => 'HasThirdParty', 'caption' => 'Third Party?', 'kind' => 'control', 'type' => 'radios', 'options' => array('N' => 'No', 'Y' => 'Yes'), 'hint' => 'Indicate whether or not this data is handed over to a third party', 'required' => TRUE),
                array('name' => 'ThirdPartyName', 'caption' => 'Name Third Party', 'kind' => 'control', 'type' => 'string', 'required' => TRUE),
            );
            $fieldsets[] = array('fields' => $fields);
            $init = array(
                "$('#frmTableExport').find(\"input[type='radio']\").on('change', function() {",
                "\t$('#frmTableExport\\\:ThirdPartyName').rules(($(this).val() == 'Y' ? 'add' : 'remove'), 'required');",
                "});",
            );
            $formitem = array(
                'id' => 'frmTableExport', 'style' => 'standard',
                'datasource' => array_merge($_GET, array('HasThirdParty' => 'Y')), 'buttons' => array(),
                'fieldsets' => $fieldsets, 'borders' => FALSE,
                'oninitialise' => $init,
            );
            Form($formitem);
            ModalBody(TRUE);
            //ModalFooter("frmTableExport", $url, "function( frmElement, jsonResponse ) { LoadNotifications(); }", "Export", "function( frmElement ) { dlgBGProcessStarted('Your export request has started. You will be notified once it has been completed.'); }");
            NewModalFooter(array(
                'form' => $formitem,
                'url' => $url,
                'onsuccess' => "function( frmElement, jsonResponse ) { LoadNotifications(); }",
                'savecaption' => "Export",
                'onpost' => "function( frmElement ) { dlgBGProcessStarted('Your export request has started. You will be notified once it has been completed.'); }" 
            ));
            break;
        case 'email_table':
            ModalHeader('Bulk Email');
            ModalBody(FALSE);
            $url = "/syscall.php?do=createTableEmailTask";
            $from = GetFromAddresses();
            $datasource = array('Priority' => 5, 'Request' => substr($_GET['request'], strpos($_GET['request'], "?")+1), 'SourceURL' => $_GET['sourceurl']);
            $fields = array(
                array('name' => 'Request', 'kind' => 'hidden'),
                array('name' => 'SourceURL', 'kind' => 'hidden'),
            );
            if(count($from['addresses']) > 0) {
                if(count($from['addresses']) == 1) {
                    $fields[] = array('name' => 'From', 'caption' => 'From:', 'kind' => 'static');
                    $datasource['From'] = FirstArrayItem($from['addresses']);
                } else {
                    $fields[] = array('name' => 'From', 'caption' => 'From', 'kind' => 'control', 'type' => 'advlist', 'options' => $from['addresses'], 'selected' => $from['default'], 'required' => TRUE);
                }
            }
            $fields = array_merge($fields, array(
                array('name' => 'Subject', 'caption' => 'Subject', 'type' => 'string', 'kind' => 'control', 'required' => TRUE),
                array('name' => 'Description', 'caption' => 'Description', 'kind' => 'control', 'type' => 'string', 'hint' => 'Explain the purpose or reason for this bulk email', 'required' => TRUE),
                array('name' => 'Priority', 'caption' => 'Priority', 'type' => 'integer', 'kind' => 'control', 'size' => 4, 'required' => TRUE),
                array('name' => 'Private', 'caption' => 'Sensitivity', 'type' => 'combo', 'kind' => 'control', 'options' => array(0 => 'Normal', 1 => 'Private', 2 => 'Confidential')),
            ));
            $fieldsets[] = array('fields' => $fields);
            $formitem = array(
                'id' => 'frmTableEmail', 'style' => 'standard',
                'datasource' => array_merge($_GET, $datasource), 'buttons' => array(),
                'fieldsets' => $fieldsets, 'borders' => FALSE,
            );
            Form($formitem);
            ModalBody(TRUE);
            ModalFooter("frmTableEmail", $url, "function( frmElement, jsonResponse ) { window.location.href = '/full.php?do=editor&BulkEmailID='+jsonResponse.bulkemailid }", "Continue");
            break;
        case 'export_paper':
            ModalHeader('Document merge');
            ModalBody(FALSE);
            $url = "/syscall.php?do=docFromDatatable&".substr($_GET['request'], strpos($_GET['request'], "?")+1);
            $fields = array();
            if(!empty($_GET['PaperTemplateID'])) {
                $fields[] = array('name' => 'PaperTemplateID', 'kind' => 'hidden');
            } else {
                $sql = "SELECT COALESCE(Mnemonic, CAST(PaperTemplateID AS CHAR)) AS `PaperTemplateSelector`, PaperTemplateID, Mnemonic, Title FROM tblpapertemplates ORDER BY Title";
                $qry = mysqli_query($SYSTEM_SETTINGS['Database'], $sql);
                $rows = mysqli_fetch_all($qry, MYSQLI_ASSOC);
                $options = array();
                foreach($rows AS $row) {
                    if(!empty($row['Mnemonic']) && !isset($options['Mnemonic'])) {
                        $options[$row['Mnemonic']] = $row['Title'];
                    } else {
                        $options[intval($row['PaperTemplateID'])] = $row['Title'];
                    }
                }
                asort($options);
                $fields[] = array('name' => 'PaperTemplateSelector', 'caption' => 'Template', 'kind' => 'control', 'type' => 'advcombo', 'options' => $options, 'allowempty' => FALSE, 'required' => TRUE);
            }
            $fields = array_merge($fields, array(
                //array('name' => 'description', 'kind' => 'hidden'),
                //array('name' => 'PaperTemplateID', 'kind' => 'hidden'),
                array('name' => 'Purpose', 'caption' => 'Purpose', 'kind' => 'control', 'type' => 'string', 'hint' => 'Explain the reason for this document merge', 'required' => TRUE),
                array('name' => 'HasThirdParty', 'caption' => 'Third Party?', 'kind' => 'control', 'type' => 'radios', 'options' => array('N' => 'No', 'Y' => 'Yes'), 'hint' => 'Indicate whether or not this document is handed over to a third party', 'required' => TRUE),
                array('name' => 'ThirdPartyName', 'caption' => 'Name Third Party', 'kind' => 'control', 'type' => 'string', 'required' => TRUE),
            ));
            $fieldsets[] = array('fields' => $fields);
            $init = array(
                "$('#frmTableToPaper').find(\"input[type='radio']\").on('change', function() {",
                "\t$('#frmTableToPaper\\\:ThirdPartyName').rules(($(this).val() == 'Y' ? 'add' : 'remove'), 'required');",
                "});",
            );
            $formitem = array(
                'id' => 'frmTableToPaper', 'style' => 'standard',
                'datasource' => array_merge($_GET, array('HasThirdParty' => 'Y')), 'buttons' => array(),
                'fieldsets' => $fieldsets, 'borders' => FALSE,
                'oninitialise' => $init,
            );
            Form($formitem);
            ModalBody(TRUE);
            ModalFooter("frmTableToPaper", $url, "function( frmElement, jsonResponse ) { LoadNotifications(); }", "Begin", "function( frmElement ) { dlgBGProcessStarted('Your document request has started. You will be notified once it has been completed.'); }");
            break;
        case 'table_to_group':
            ModalHeader('Add to Group');
            ModalBody(FALSE);
            $url = "/syscall.php?do=addTableToGroup&".substr($_GET['request'], strpos($_GET['request'], "?")+1);
            $sql = 
            "SELECT tblpersongroup.PersonGroupID, tblpersongroup.GroupName, tblpersongroup.Expires,
                    tblmsgrade.MSGradeID, tblmsgrade.GradeCaption
             FROM tblpersongroup
             LEFT JOIN tblpersongrouptomsgrade ON tblpersongrouptomsgrade.PersonGroupID = tblpersongroup.PersonGroupID
             LEFT JOIN tblmsgrade ON tblmsgrade.MSGradeID = tblpersongrouptomsgrade.MSGradeID
             WHERE ((tblpersongroup.Expires IS NULL) OR (tblpersongroup.Expires > UTC_TIMESTAMP()))
             ORDER BY tblpersongroup.GroupName";
            $query = mysqli_query($SYSTEM_SETTINGS['Database'], $sql);
            $data = mysqli_fetch_all($query, MYSQLI_ASSOC);
            foreach($data AS $item) {
                $options[$item['PersonGroupID']] = $item['GroupName'];
            }
            $fieldsets = array();
            $fieldsets[] = array('fields' => array(
                array('name' => 'PersonGroupID', 'caption' => 'Group', 'kind' => 'control', 'type' => 'advcombo', 'options' => $options, 'required' => 'Select a group'),
                array('name' => 'Comment', 'caption' => 'Comment', 'kind' => 'control', 'type' => 'string'),
            ));
            $formitem = array(
                'id' => 'frmAddTableToGroup', 'style' => 'standard', 
                'datasource' => array(), 'buttons' => array(),
                'fieldsets' => $fieldsets, 'borders' => FALSE
            );
            Form($formitem);
            ModalBody(TRUE);
            ModalFooter("frmAddTableToGroup", $url, "function( frmElement, jsonResponse ) { LoadNotifications(); }", "Continue", "function( frmElement ) { dlgBGProcessStarted('Your task request has started. You will be notified once it has been completed.'); }");
            break;
        case 'addmoney':
        case 'addmoneyMSAppList':
        case 'addmoneyRecord':
        case 'addmoneyMSTab':
        case 'addmoneyInvoice':
        case 'addmoneyInvList':
            ModalHeader('Monies Received');
            ModalBody(FALSE);
            $countries = new crmCountries($SYSTEM_SETTINGS['Database']);
            $transactiontypes = new crmTransactionTypes($SYSTEM_SETTINGS['Database']);
            $datasource = array();
            $fieldsets = array();
            $fields = array();
            if(isset($_GET['invoiceid'])) {
                $INVOICE = new crmInvoice('eventsFinance', $SYSTEM_SETTINGS['Database'], $_GET['invoiceid'], InvoiceSettings());
                $datasource['ISO4217'] = $INVOICE->Invoice['ISO4217'];
                $datasource['Symbol'] = $INVOICE->Invoice['Symbol']; 
                $datasource['InvoiceID'] = $INVOICE->InvoiceID; 
                $datasource['ReceivedAmount'] = $INVOICE->Invoice['Outstanding']; 
                $datasource['AllocatedAmount'] = (isset($_GET['amount']) ? intval($_GET['amount']) : $INVOICE->Invoice['Outstanding']);
                $datasource['SourceAllocatedAmount'] = $datasource['AllocatedAmount'];
                $minrec = $INVOICE->MinReceivable();
                if($minrec['Found']) {
                    foreach(array('InvoiceItemID', 'ItemNet', 'ItemVATRate', 'ItemVAT', 'ItemTotal') AS $key) {
                        $datasource['MinRecv_'.$key] = $minrec[$key];
                        $fields[] = array('name' => 'MinRecv_'.$key, 'kind' => 'hidden'); 
                    }
                }
            } else {
                $currency = new crmCurrency($SYSTEM_SETTINGS, 'GBP');
                $datasource['ISO4217'] = $currency->ISO4217;
                $datasource['Symbol'] = $currency->Symbol;
            }
            $datasource['Received'] = gmdate('Y-m-d H:i:s');
            Div(array('class' => 'row'), 7);
            Div(array('class' => 'col-xs-6'), 8);
                SimpleHeading('Monies', 5, 'sub');
                $fields[] = array('name' => 'Received', 'caption' => 'Received', 'kind' => 'control', 'type' => 'date', 'required' => TRUE);
                $fields[] = array('name' => 'Symbol', 'kind' => 'hidden');
                if(!empty($INVOICE)) {
                    $fields[] = array('name' => 'ISO4217', 'caption' => 'Currency', 'kind' => 'static', 'type' => 'string');
                } else {
                    $fields[] = array('name' => 'ISO4217', 'caption' => 'Currency', 'kind' => 'control', 'type' => 'advcombo', 'required' => TRUE, 'allowempty' => FALSE, 'size' => 6, 'options' => $countries->Currencies);
                }
                $fields[] = array('name' => 'TransactionTypeID', 'caption' => 'Type', 'kind' => 'control', 'type' => 'advcombo', 'required' => TRUE, 'allowempty' => FALSE, 'size' => 6, 'options' => $transactiontypes->Types);
                $fields[] = array('name' => 'ReceivedAmount', 'caption' => 'Amount Received', 'kind' => 'control', 'type' => 'money', 'required' => TRUE, 'currencysymbol' => (!empty($INVOICE) ? $INVOICE->Invoice['Symbol'] : null));
                $fields[] = array('name' => 'ReceivedFrom', 'caption' => 'Received From', 'kind' => 'control', 'type' => 'string', 'required' => TRUE);
                $fields[] = array('name' => 'TransactionReference', 'caption' => 'Transaction Reference', 'kind' => 'control', 'type' => 'string', 'required' => TRUE);
                $fields[] = array('name' => 'AddInfo', 'caption' => 'Additional information', 'kind' => 'control', 'type' => 'memo');
                if(!empty($INVOICE)) {
                    $fields[] = array('name' => 'InvoiceID', 'kind' => 'hidden');
                    //$fields[] = array('name' => 'MinReceivable', 'kind' => 'hidden');
                    $fields[] = array('name' => 'SourceAllocatedAmount', 'kind' => 'hidden');
                    $fields[] = array('name' => 'AllocatedAmount', 'caption' => 'Allocate to Invoice', 'kind' => 'control', 'type' => 'money', 'required' => TRUE, 'currencysymbol' => (!empty($INVOICE) ? $INVOICE->Invoice['Symbol'] : null));
                    $menuitems = array();
                    $itemtypes = new crmInvoiceItemTypes($SYSTEM_SETTINGS['Database']);
                    $firstitem = null;
                    foreach($itemtypes->WriteOffs AS $itemtypeid => $typename) {
                        $menuitems[] = array(
                            'type' => 'item', 'caption' => $typename,
//                            'script' => "SetDataAttribute($(this).closest('.form-group'), 'itemtype', {$itemtypeid}, '$typename');"
                            'script' => "SetHiddenFieldValue($('#frmEditMoney\\\\:WrittenOffType'), {$itemtypeid}, function(field, newValue){ $('#frmEditMoney\\\\:WrittenOff').parent().siblings('span.help-block').text('{$typename}'); } );"
                        );
                        if(is_null($firstitem)) {
                            $firstitem = array('id' => $itemtypeid, 'caption' => $typename);
                        }
                    }
                    $fields[] = array(
                        'name' => 'WrittenOff', 'caption' => 'Adjust Invoice', 'kind' => 'control', 'type' => 'money', 'required' => FALSE,
                        'currencysymbol' => (!empty($INVOICE) ? $INVOICE->Invoice['Symbol'] : null), 'hide' => TRUE, 'negative' => TRUE, 'bold' => TRUE, 'hint' => $firstitem['caption'],
//                        'adddata' => array('itemtype' => $firstitem['id']),
                        'rightaddon' => array(
                            'type' => 'button', 'icon' => 'fa-ellipsis-h', 'tooltip' => 'Change this allocation type', 'style' => 'alt',
                            'menuitems' => $menuitems, 'dropup' => TRUE,
                        ),
                    );
                    $datasource['WrittenOffType'] = $firstitem['id'];
                    $fields[] = array(
                        'name' => 'WrittenOffType', 'kind' => 'hidden'
                    );
                    //$fields[] = array('name' => 'WrittenOff', 'caption' => 'Write-off', 'kind' => 'static', 'type' => 'string', 'hide' => TRUE);
                }
                $fieldsets = array();
                $fieldsets[] = array('fields' => $fields);
                
                $init = array(
                    "$('#frmEditMoney\\\\:ReceivedAmount').on('change keyup paste cut', function() {\n",
                    "\tvar amount = $('#frmEditMoney\\\\:ReceivedAmount').val().replace(/[^\d.-]/g, '');\n",
                    "\tReceivedAmount = Math.floor(parseFloat(amount)*100);\n",
                    "\tamount = $('#frmEditMoney\\\\:SourceAllocatedAmount').val().replace(/[^\d.-]/g, '');\n",
                    "\tAllocatedAmount = Math.floor(parseFloat(amount));\n",
                    "\tconsole.log(ReceivedAmount);\n",
                    "\tconsole.log(AllocatedAmount);\n",
                    "\tif(ReceivedAmount < AllocatedAmount) {;\n",
                    "\t\t$('#frmEditMoney\\\\:AllocatedAmount').val($('#frmEditMoney\\\\:Symbol').val()+(ReceivedAmount/100).toFixed(2));\n",
                    "\t\t$('#frmEditMoney\\\\:AllocatedAmount').trigger('change');\n",
                    "\t} else if(ReceivedAmount > AllocatedAmount) {\n",
                    "\t\t$('#frmEditMoney\\\\:AllocatedAmount').val($('#frmEditMoney\\\\:Symbol').val()+(AllocatedAmount/100).toFixed(2));\n",
                    "\t\t$('#frmEditMoney\\\\:AllocatedAmount').trigger('change');\n",
                    "\t}\n",
                    "});",
                    "$('#frmEditMoney\\\\:AllocatedAmount').on('change keyup paste cut', function() {\n",
                    "\tvar amount = $('#frmEditMoney\\\\:MinRecv_ItemTotal').val();\n",
                    "\tMinReceivable = parseInt(amount);\n",
                    "\tif(MinReceivable > 0) {\n",
                    "\t\tamount = $(this).val().replace(/[^\d.-]/g, '');\n",
                    "\t\tAllocatedAmount = Math.floor(parseFloat(amount)*100);\n",
                    "\t\tif(AllocatedAmount < MinReceivable) {\n",                    
                    "\t\t\tWrittenOff = MinReceivable-AllocatedAmount;\n",
//                    "\t\t\t$('#frmEditMoney\\\\:WrittenOff').html('<span class=\"text-danger\"><b>'+$('#frmEditMoney\\\\:Symbol').val()+(WrittenOff/100).toFixed(2)+'</b></span>');\n",
                    "\t\t\t$('#frmEditMoney\\\\:WrittenOff').val($('#frmEditMoney\\\\:Symbol').val()+(-WrittenOff/100).toFixed(2));\n",
                    "\t\t\t$('#frmEditMoney\\\\:WrittenOff').closest('.form-group').show();\n",
                    "\t\t} else {\n",
                    "\t\t\t$('#frmEditMoney\\\\:WrittenOff').val('');\n",
                    "\t\t\t$('#frmEditMoney\\\\:WrittenOff').closest('.form-group').hide();\n",
                    "\t\t}\n",
                    "\t}\n",
//                    "\tconsole.log($(this).val());"
//                    "\tconsole.log($('#frmEditMoney\\\\:MinReceivable').val());",
//                    "\tvar vatrate = $('[name=\"ItemVATRate\"]').val();",
//                    "\tprice = CalculatePrice( 'ItemNet', vatrate, 1, '{$datasource['ISO4217']}' );\n",
//                    "\t$('#calcSubtotal').text(price.strings.net);\n",
//                    "\t$('#calcVATSubtotal').text(price.strings.vat);\n",
//                    "\t$('#calcTotal').text(price.strings.value);\n",
                    "});",
                );                
                
                $formitem = array(
                    'id' => 'frmEditMoney', 'style' => 'standard',
                    'datasource' => $datasource, 'buttons' => array(),
                    'fieldsets' => $fieldsets, 'borders' => FALSE,
                    'oninitialise' => $init,
                );
                Form($formitem);
            Div(null, 8); //close left column
            Div(array('class' => 'col-xs-6'), 8);
                if(!empty($INVOICE)) {
                    SimpleHeading($INVOICE->Invoice['InvoiceCaption'], 5, 'sub');
                    $invoicedata = array();
                    $invoicedata[] = array('caption' => 'Customer No', 'value' => $INVOICE->Invoice['CustNo']);
                    $invoicedata[] = array('caption' => 'Invoice Date', 'value' => date('j F Y', strtotime($INVOICE->Invoice['InvoiceDate'].' UTC')));
                    $invoicedata[] = array('caption' => 'Customer Reference', 'value' => $INVOICE->Invoice['CustomerRef']);
                    StaticTable($invoicedata, $summaryTable, array(), 11);
                    if(($INVOICE->Invoice['NonZeroItemCount'] > 0) && ($minrec['ItemTotal'] > 0)) {
                        SimpleAlertBox('info', 'The minimum receivable amount you can process against this invoice is <b>'.ScaledIntegerAsString($minrec['ItemTotal'], "money", 100, TRUE, $INVOICE->Invoice['Symbol'])."</b>. If you allocate less than this amount, the invoice will need to be adjusted.");
                    }
                    echo str_repeat("\t", 10)."<table class=\"table table-vcenter\">\n";
                    echo str_repeat("\t", 11)."<thead>\n";
                    echo str_repeat("\t", 12)."<tr>\n";
                    echo str_repeat("\t", 13)."<th class=\"text-center\"></th>\n";
                    echo str_repeat("\t", 13)."<th class=\"text-right\"></th>\n";
                    echo str_repeat("\t", 12)."</tr>\n";
                    echo str_repeat("\t", 11)."</thead>\n";
                    echo str_repeat("\t", 11)."<tbody>\n";
                    echo str_repeat("\t", 12)."<tr class=\"active\">\n";
                    echo str_repeat("\t", 13)."<td colspan=\"4\" class=\"text-right\"><span class=\"h4\">SUBTOTAL</span></td>\n";
                    echo str_repeat("\t", 13)."<td class=\"text-right\"><span class=\"h4\">".ScaledIntegerAsString($INVOICE->Invoice['Net'], "money", 100, FALSE, $INVOICE->Invoice['Symbol'])."</span></td>\n";
                    echo str_repeat("\t", 12)."</tr>\n";
                    echo str_repeat("\t", 12)."<tr class=\"active\">\n";
                    echo str_repeat("\t", 13)."<td colspan=\"4\" class=\"text-right\"><span class=\"h4\">VAT</span></td>\n";
                    echo str_repeat("\t", 13)."<td class=\"text-right\"><span class=\"h4\">".ScaledIntegerAsString($INVOICE->Invoice['VAT'], "money", 100, FALSE, $INVOICE->Invoice['Symbol'])."</span></td>\n";
                    echo str_repeat("\t", 12)."</tr>\n";
                    echo str_repeat("\t", 12)."<tr class=\"active\">\n";
                    echo str_repeat("\t", 13)."<td colspan=\"4\" class=\"text-right\"><span class=\"h4\"><strong>INVOICE TOTAL</strong></span></td>\n";
                    echo str_repeat("\t", 13)."<td class=\"text-right\"><span class=\"h4\"><strong>".ScaledIntegerAsString($INVOICE->Invoice['Total'], "money", 100, FALSE, $INVOICE->Invoice['Symbol'])."</strong></span></td>\n";
                    echo str_repeat("\t", 12)."</tr>\n";
                    if($INVOICE->Invoice['Total'] <> $INVOICE->Invoice['Outstanding']) {
                        echo str_repeat("\t", 12)."</tr>\n";
                        echo str_repeat("\t", 12)."<tr class=\"active\">\n";
                        echo str_repeat("\t", 13)."<td colspan=\"4\" class=\"text-right\"><span class=\"h4\"><strong>OUTSTANDING</strong></span></td>\n";
                        echo str_repeat("\t", 13)."<td class=\"text-right\"><span class=\"h4\"><strong>".ScaledIntegerAsString($INVOICE->Invoice['Total'], "money", 100, FALSE, $INVOICE->Invoice['Symbol'])."</strong></span></td>\n";
                        echo str_repeat("\t", 12)."</tr>\n";
                    }
                    echo str_repeat("\t", 11)."</tbody>\n";
                    echo str_repeat("\t", 10)."</table>\n";
                }
            Div(null, 8); //close right column
            Div(null, 7);
            ModalBody(TRUE);
            switch($do) {
                case 'addmoneyInvoice':
                    $cbSuccess = "function(){ LoadContent('wsMain', '/load.php?do=record_invoice', { spinner: true, urlparams: { invoiceid: {$INVOICE->InvoiceID} } }); }";
                    break;
                case 'addmoneyRecord':
                    $cbSuccess = "function(){ RefreshDataTable(dt_record_invoices); InvalidateTab( 'tab-membership'); LoadContent('wsSide', 'load.php?do=sidebar_membership', { divid: 'sidebar_membership', spinner: false, urlparams: { personid: {$INVOICE->Invoice['PersonID']} } } ); }";
                    break;
                case 'addmoneyMSAppList':
                    $cbSuccess = "function(){ RefreshDataTable(dt_applications); }";
                    break;
                case 'addmoneyMSTab':
                    $cbSuccess = "function(){ ReloadTab('tab-membership', 'sidebar_membership');  }";
                    break;
                case 'addmoneyInvList':
                    $cbSuccess = "function(){ RefreshDataTable(dt_invoices); }";
                    break;
                case 'addmoney':
                default:
                    $cbSuccess = "";
            }
            ModalFooter("frmEditMoney", "/syscall.php?do=saveMoney", $cbSuccess, "Process");
            break;
        case 'editsimplefield':
            ModalHeader('Edit');
            ModalBody(FALSE);
            $tablename = VarnameStr($_GET['tablename']);
            $fieldname = VarnameStr($_GET['fieldname']);
            $idfieldname = VarnameStr($_GET['idfieldname']);
            $idfieldvalue = (is_numeric($_GET['idvalue']) ? intval($_GET['idvalue']) : "'".IdentifierStr($_GET['idvalue'])."'");
            $fieldtype = (!empty($_GET['fieldtype']) ? VarnameStr($_GET['fieldtype']) : 'string');
            $caption = (!empty($_GET['caption']) ? PunctuatedTextStr($_GET['caption']) : 'Value');
            $required = (!empty($_GET['required']) ? $_GET['required'] : FALSE);
            $sql = "SELECT {$fieldname} FROM {$tablename} WHERE {$idfieldname} = {$idfieldvalue}";
            $datasource = array(
                '_TableName' => $tablename,
                '_FieldName' => $fieldname,
                '_idFieldName' => $idfieldname,
                '_idValue' => $idfieldvalue,
                '_FieldType' => $fieldtype,
                '_Caption' => $caption,
                $fieldname => SingleValue($SYSTEM_SETTINGS['Database'], $sql)
            );
            $fieldsets = array();
            $fieldsets[] = array('fields' => array(
                array('name' => '_TableName', 'kind' => 'hidden'),
                array('name' => '_FieldName', 'kind' => 'hidden'),
                array('name' => '_idFieldName', 'kind' => 'hidden'),
                array('name' => '_idValue', 'kind' => 'hidden'),
                array('name' => '_FieldType', 'kind' => 'hidden'),
                array('name' => '_Caption', 'kind' => 'hidden'),
                array('name' => $fieldname, 'caption' => $caption, 'kind' => 'control', 'type' => $fieldtype, 'required' => $required),
            ));
            $formitem = array(
                'id' => 'frmEditSimpleField', 'style' => 'standard',
                'datasource' => $datasource, 'buttons' => array(),
                'fieldsets' => $fieldsets, 'borders' => FALSE
            );
            Form($formitem);
            if(!empty($_GET['tabid'])) {
                $cbSuccess  = "function(){ "; 
                $cbSuccess .= "ReloadTab('".IdentifiersStr($_GET['tabid'])."'";
                if(!empty($_GET['sidebars'])) {
                    $cbSuccess .= ", '".IdentifiersStr($_GET['sidebars'])."'";
                }
                $cbSuccess .= ");";
                $cbSuccess .= " }";
            } else {
                $cbSuccess = '';
            }
            ModalBody(TRUE);
            ModalFooter("frmEditSimpleField", "/syscall.php?do=saveSimpleField", $cbSuccess);
            break;
        case 'cconvert':
            $base = strtoupper(IdentifierStr($_GET['base']));
            $value = intval($_GET['value']);
            switch($SYSTEM_SETTINGS['Finance']['CurrencyConverter']['Method']) {
                case 'Fixer.io':
                    $response = GetJSON("http://api.fixer.io/latest?base={$base}&symbols=USD,GBP,EUR");
                    if($response['success']) {
                        echo "<small><i>Rates as per ".date('j M Y', strtotime($response['response']['date'])).":</i></small><br>";
                        $count = 0;
                        foreach($response['response']['rates'] AS $iso4217 => $rate) {
                            $currency = new crmCurrency($SYSTEM_SETTINGS['Database'], $iso4217);
                            echo ($count > 0 ? "<br>" : "").'= '.FmtText('<info><b>'.ScaledIntegerAsString(round($value*$rate), 'money', 100, TRUE, $currency->Symbol).'</b></info>');
                            $count++;
                        }
                        echo "<br><small><i>Source: Fixer.io</i></small>";
                    } else {
                        echo "Unable to retrieve exchange rates.";
                    }
                    break;
                case 'OER':
                    if($SYSTEM_SETTINGS['Finance']['CurrencyConverter']['FreeAPI']) {
                        $response = GetJSON("https://openexchangerates.org/api/latest.json?app_id={$SYSTEM_SETTINGS['Credentials']['OER']['APIID']}");
                    } else {
                        $response = GetJSON("https://openexchangerates.org/api/latest.json?app_id={$SYSTEM_SETTINGS['Credentials']['OER']['APIID']}&base={$base}&symbols=USD,GBP,EUR");
                    }
                    if($response['success']) {
                        echo "<small><i>Rates as per ".date('j M Y', $response['response']['timestamp']).":</i></small><br>";
                        $count = 0;
                        if($SYSTEM_SETTINGS['Finance']['CurrencyConverter']['FreeAPI']) {
                            $basevalue = $value/$response['response']['rates'][$base];
                            foreach(array('GBP', 'EUR', 'USD') AS $iso4217) {
                                if(strcasecmp($iso4217, $base) <> 0){
                                    $currency = new crmCurrency($SYSTEM_SETTINGS['Database'], $iso4217);
                                    echo ($count > 0 ? "<br>" : "").'= '.FmtText('<info><b>'.ScaledIntegerAsString(round($basevalue*$response['response']['rates'][$iso4217]), 'money', 100, TRUE, $currency->Symbol).'</b></info>');
                                    $count++;
                                }
                            }
                        } else {
                            foreach($response['response']['rates'] AS $iso4217 => $rate) {
                                if(strcasecmp($iso4217, $base) <> 0){
                                    $currency = new crmCurrency($SYSTEM_SETTINGS['Database'], $iso4217);
                                    echo ($count > 0 ? "<br>" : "").'= '.FmtText('<info><b>'.ScaledIntegerAsString(round($value*$rate), 'money', 100, TRUE, $currency->Symbol).'</b></info>');
                                    $count++;
                                }
                            }
                        }
                        echo "<br><small><i>Source: Open Exchange Rates</i></small>";
                    } else {
                        echo "Unable to retrieve exchange rates.";
                    }
                    break;
                default:
                    echo "No currency converter method selected.";
            }
            break;
        case 'editinvoiceitem':
            ModalHeader((isset($_GET['InvoiceItemID']) ? 'Invoice Item': 'Edit Invoice'));
            ModalBody(FALSE);
            if(isset($_GET['InvoiceItemID'])) {
                $itemdata = InvoiceItemToInvoice($_GET['InvoiceItemID']);
                $INVOICE = new crmInvoice('eventsFinance', $SYSTEM_SETTINGS['Database'], $itemdata['InvoiceID'], InvoiceSettings());
                $INVOICE->Reload('items');
                $datasource = $INVOICE->Items[$itemdata['InvoiceItemID']];
            } else {
                $INVOICE = new crmInvoice('eventsFinance', $SYSTEM_SETTINGS['Database'], intval($_GET['InvoiceID']), InvoiceSettings());
                $datasource = $INVOICE->Invoice;
            }
            if($INVOICE->Found) {
                Div(array('class' => 'row'), 7);
                Div(array('class' => 'col-xs-6'), 8);
                    SimpleHeading($INVOICE->Invoice['InvoiceCaption'], 5, 'sub');
                    $invoicedata = array();
                    $invoicedata[] = array('caption' => 'Customer No', 'value' => $INVOICE->Invoice['CustNo']);
                    $invoicedata[] = array('caption' => 'Invoice Date', 'value' => date('j F Y', strtotime($INVOICE->Invoice['InvoiceDate'].' UTC')));
                    $invoicedata[] = array('caption' => 'Customer Reference', 'value' => $INVOICE->Invoice['CustomerRef']);
                    StaticTable($invoicedata, $summaryTable, array(), 11);
                Div(null, 8);
                Div(array('class' => 'col-xs-6'), 8);
                    if(($INVOICE->Invoice['InvoiceType'] == 'invoice') && $INVOICE->Invoice['Settled']) {
                        SimpleAlertBox('warning', '<b>Warning</b>: This invoice is settled. You should not make any changes to this document. If you continue, you may cause serious accounting discrepancies.');
                    } elseif(!empty($INVOICE->Invoice['EDISent'])) {
                        SimpleAlertBox('warning', '<b>Warning</b>: This invoice has already been recorded onto the accounting system. You should not make further changes to this document. If you continue, you may cause serious accounting discrepancies.');
                    }
                    if(!empty($datasource['LinkedID'])) {
                        SimpleAlertBox('warning', '<b>Important</b>: This invoice item is automatically managed by the system. Care should be taken before modifying it as this may affect processing afterwards. Normally, changes to automatically invoiced items should <b>not</b> be made here, but by amending the parent record or process instead.');
                    }
                Div(null, 8);
                Div(null, 7); //first row closure
                Div(array('class' => 'row'), 7);
                $fieldsets = array();
                $fields = array();
                if(isset($_GET['InvoiceItemID']) || !empty($_GET['New'])) {
                    Div(array('class' => 'col-xs-6'), 8);
/*                        if(!empty($datasource['LinkedID'])) {
                            SimpleAlertBox('warning', '<b>Important</b>: This invoice item is automatically managed by the system. Care should be taken before modifying it as this may have affect processing afterwards. Normally, changes to automatically invoiced items should <b>not</b> be made here, but by amending the parent record or process instead.');
                        }*/
                        $fields = array(
                            array('name' => 'InvoiceItemID', 'kind' => 'hidden'),
                            array('name' => 'InvoiceID', 'kind' => 'hidden'),
                        );
                        if((empty($datasource['InvoiceItemTypeID']) || !empty($datasource['AllowedManual'])) && empty($datasource['LinkedID'])) {
                            $invtypes = new crmInvoiceItemTypes($SYSTEM_SETTINGS['Database']);
                            $fields[] = array('name' => 'InvoiceItemTypeID', 'caption' => 'Item Type', 'kind' => 'control', 'type' => 'groupcombo', 'options' => $invtypes->GroupedTypes);
                        }
                        $fields = array_merge($fields, array(
                            array('name' => 'Description', 'caption' => 'Description', 'kind' => 'control', 'type' => 'string'),
                            array('class' => 'calculation', 'name' => 'ItemUnitPrice', 'caption' => 'Net Unit Price', 'kind' => 'control', 'type' => 'money', 'currencysymbol' => $INVOICE->Invoice['Symbol']),
                            array('class' => 'calculation', 'name' => 'ItemQty', 'caption' => 'Quantity', 'kind' => 'control', 'type' => 'integer'),
                            array('class' => 'calculation', 'name' => 'ItemVATRate', 'caption' => 'VAT Rate', 'kind' => 'control', 'type' => 'combo', 'options' => VATRates()),
                            array('name' => 'ItemDate', 'caption' => 'Item Date', 'kind' => 'control', 'type' => 'date', 'showage' => true),
                        ));
                        $fieldsets[] = array('fields' => $fields);
                        $init = array(
                            "$('.calculation').on('change keyup paste cut', function() {\n",
                            "\tvar vatrate = $('[name=\"ItemVATRate\"]').val();",
                            "\tprice = CalculatePrice( 'ItemUnitPrice', vatrate, 'ItemQty' );\n",
                            "\t$('#calcSubtotal').text(price.strings.net);\n",
                            "\t$('#calcVATSubtotal').text(price.strings.vat);\n",
                            "\t$('#calcTotal').text(price.strings.value);\n",
                            "});",
                        );
                        //$fieldsets[] = NoteFieldSet('finance');
                        $formitem = array(
                            'id' => 'frmEditInvoiceItem', 'style' => 'vertical',
                            'datasource' => $datasource, 'buttons' => array(),
                            'fieldsets' => $fieldsets, 'borders' => FALSE,
                            'oninitialise' => $init,
                        );
                        Form($formitem);                
                    Div(null, 8);
                    Div(array('class' => 'col-xs-6'), 8);
                        echo str_repeat("\t", 10)."<table class=\"table table-vcenter\">\n";
                        echo str_repeat("\t", 11)."<thead>\n";
                        echo str_repeat("\t", 12)."<tr>\n";
                        echo str_repeat("\t", 13)."<th class=\"text-center\"></th>\n";
                        echo str_repeat("\t", 13)."<th class=\"text-right\"></th>\n";
                        echo str_repeat("\t", 12)."</tr>\n";
                        echo str_repeat("\t", 11)."</thead>\n";
                        echo str_repeat("\t", 11)."<tbody>\n";
                        echo str_repeat("\t", 12)."<tr class=\"active\">\n";
                        echo str_repeat("\t", 13)."<td colspan=\"4\" class=\"text-right\"><span class=\"h4\">SUBTOTAL</span></td>\n";
                        echo str_repeat("\t", 13)."<td class=\"text-right\"><span class=\"h4\" id=\"calcSubtotal\">".ScaledIntegerAsString((isset($datasource['ItemNet']) ? $datasource['ItemNet'] : 0), "money", 100, FALSE, $datasource['Symbol'])."</span></td>\n";
                        echo str_repeat("\t", 12)."</tr>\n";
                        echo str_repeat("\t", 12)."<tr class=\"active\">\n";
                        echo str_repeat("\t", 13)."<td colspan=\"4\" class=\"text-right\"><span class=\"h4\">VAT</span></td>\n";
                        echo str_repeat("\t", 13)."<td class=\"text-right\"><span class=\"h4\" id=\"calcVATSubtotal\">".ScaledIntegerAsString((isset($datasource['ItemVAT']) ? $datasource['ItemVAT'] : 0), "money", 100, FALSE, $datasource['Symbol'])."</span></td>\n";
                        echo str_repeat("\t", 12)."</tr>\n";
                        echo str_repeat("\t", 12)."<tr class=\"active\">\n";
                        echo str_repeat("\t", 13)."<td colspan=\"4\" class=\"text-right\"><span class=\"h4\"><strong>ITEM TOTAL</strong></span></td>\n";
                        echo str_repeat("\t", 13)."<td class=\"text-right\"><span class=\"h4\"><strong><span id=\"calcTotal\">".ScaledIntegerAsString((isset($datasource['ItemTotal']) ? $datasource['ItemTotal'] : 0), "money", 100, FALSE, $datasource['Symbol'])."</span></strong></span></td>\n";
                        echo str_repeat("\t", 12)."</tr>\n";
                        echo str_repeat("\t", 11)."</tbody>\n";
                        echo str_repeat("\t", 10)."</table>\n";
                    Div(null, 8);
                } else {
                    Div(array('class' => 'col-xs-12'), 8);
                        $fields = array(
                            array('name' => 'InvoiceID', 'kind' => 'hidden'),
                        );
                        switch($_GET['Field']) {
                            case 'AddInfo':
                                $fields[] = array('name' => 'AddInfo', 'caption' => 'Additional Info', 'kind' => 'control', 'type' => 'memo');
                                break;
                            case 'Payable':
                                $fields[] = array('name' => 'Payable', 'caption' => 'Payable', 'kind' => 'control', 'type' => 'memo');
                                break;
                        }
                        $fieldsets[] = array('fields' => $fields);
                        //$fieldsets[] = NoteFieldSet('finance');
                        $formitem = array(
                            'id' => 'frmEditInvoiceItem', 'style' => 'vertical',
                            'datasource' => $datasource, 'buttons' => array(),
                            'fieldsets' => $fieldsets, 'borders' => FALSE
                        );
                        Form($formitem);                
                    Div(null, 8);
                }
                Div(null, 7); //Second row closure
            }
            ModalBody(TRUE);
            ModalFooter("frmEditInvoiceItem", "/syscall.php?do=saveInvoiceItem", "function( frmElement, jsonResponse ){ LoadContent('wsMain', '/load.php?do=record_invoice', { spinner: true, urlparams: { invoiceid: {$INVOICE->InvoiceID} } }); }");
            break;
        case 'attachdiscount':
            ModalHeader('Attach Discount');
            ModalBody(FALSE);
            $RECORD = GetParentRecord();
            $datasource = array();
            $datasource[$RECORD->IDField] = $RECORD->IDFieldValue;            
            $fieldsets = array();
            $fields = array();
            $discounts = ListDiscounts(array());
            $fields[] = array('name' => $RECORD->IDField, 'kind' => 'hidden');
            $fields[] = array('name' => 'DiscountID', 'caption' => 'Discount', 'kind' => 'control', 'type' => 'advcombo', 'options' => $discounts);
            $fields[] = array('name' => 'Expires', 'caption' => 'Expires', 'kind' => 'control', 'type' => 'date', 'showage' => true);
            $fieldsets[] = array('fields' => $fields);
            $fieldsets[] = NoteFieldSet('finance');
            $formitem = array(
                'id' => 'frmAttachDiscount', 'style' => 'standard', 'spinner' => TRUE,
                'datasource' => $datasource, 'buttons' => array(),
                'fieldsets' => $fieldsets, 'borders' => FALSE
            );
            Form($formitem);            
            ModalBody(TRUE);
            ModalFooter(
                "frmAttachDiscount",
                "/syscall.php?do=attachdiscount",
                "function( frmElement, jsonResponse ) { RefreshDataTable(dt_record_discounts); InvalidateTab(['tab-membership', 'tab-notes']); ReloadSidebar(['sidebar_membership', 'sidebar_recinfo']); }",
                "Attach"
            );
            break;
        case 'editdiscount':
            ModalHeader('Discount Code');
            ModalBody(FALSE);
            $fieldsets = array();
            $fields = array();
            $categories = new crmWorkflowCategories($SYSTEM_SETTINGS['Database']);
            $invtypes = new crmInvoiceItemTypes($SYSTEM_SETTINGS['Database']);
            if(!empty($_GET['DiscountID'])) {
                SimpleAlertBox('warning', 'Warning: you are about to modify an existing discount code. This may affect open transactions.');
                $fields[] = array('name' => 'DiscountID', 'kind' => 'hidden');
                $fields[] = array('name' => 'DiscountCode', 'caption' => 'Code', 'kind' => 'static', 'formatting' => '<info><b>');
                $discount = new crmDiscountCode($SYSTEM_SETTINGS['Database'], $_GET['DiscountID']);
                $datasource = $discount->Discount;
            } else {
                $datasource = array();
                $datasource['DiscountCode'] = RandomString(16, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789');
                $datasource['RefCount'] = 1;
                $fields[] = array('name' => 'DiscountCode', 'caption' => 'Code', 'kind' => 'control', 'type' => 'string', 'required' => TRUE, 'size' => 4);
            }
            $fields[] = array('name' => 'Description', 'caption' => 'Description', 'kind' => 'control', 'type' => 'string', 'required' => TRUE);
            $fields[] = array('name' => 'CategorySelector', 'caption' => 'Category', 'kind' => 'control', 'type' => 'combo', 'options' => $categories->GetSelectors(TRUE));
            $fields[] = array('name' => 'InvoiceItemTypeID', 'caption' => 'Item Type', 'kind' => 'control', 'type' => 'groupcombo', 'options' => $invtypes->GetGroupedTypes(TRUE, TRUE));
            $fields[] = array('name' => 'Discount', 'caption' => 'Discount', 'kind' => 'control', 'type' => 'string', 'required' => TRUE, 'size' => 4);
            $fields[] = array('name' => 'RefCount', 'caption' => 'Ref.Count', 'kind' => 'control', 'type' => 'integer', 'required' => TRUE, 'min' => 1, 'size' => 4);
            $fields[] = array('name' => 'ValidFrom', 'caption' => 'Valid From', 'kind' => 'control', 'type' => 'date', 'showage' => true);
            $fields[] = array('name' => 'ValidUntil', 'caption' => 'Valid Until', 'kind' => 'control', 'type' => 'date', 'showage' => true);
            $fieldsets[] = array('fields' => $fields);
            $formitem = array(
                'id' => 'frmEditDiscount', 'style' => 'standard', 'spinner' => TRUE,
                'datasource' => $datasource, 'buttons' => array(),
                'fieldsets' => $fieldsets, 'borders' => FALSE
            );
            Form($formitem);            
            ModalBody(TRUE);
            ModalFooter(
                "frmEditDiscount",
                "/syscall.php?do=savediscount",
                "function( frmElement, jsonResponse ) { RefreshDataTable(dt_discountcodes); }",
                "Save changes"
            );
            break;
        case 'editddi':
            ModalHeader('Direct Debit Instruction');
            ModalBody(FALSE);
            $fieldsets = array();
            $fields = array();
            $scopes = new crmDirectDebitScopes;
            if(!empty($_GET['DDIID'])) {
                SimpleAlertBox('warning', 'Warning: you are about to modify an existing Direct Debit Instruction. This is subject to legal obligations and you should ensure that the correct procedure is followed. A change to an existing instruction is subject to the same mandate clearing process and time period as new instructions.');
                $fields[] = array('name' => 'DDIID', 'kind' => 'hidden');
                $ddi = new crmDirectDebitInstruction($SYSTEM_SETTINGS['Database'], $_GET['DDIID']);
                $datasource = $ddi->DDI;
                $RECORD = GetParentRecord($datasource);
                $datasource['InstructionScope'] = $scopes->ScopeSetAsString($datasource['InstructionScope']); 
                $fields[] = array('name' => 'InstructionScope', 'caption' => 'Scope', 'kind' => 'static', 'type' => 'set', 'formatting' => '<b><info>');                    
            } else {
                $datasource = array('InstructionScope' => 'members');
                $RECORD = GetParentRecord();
                $datasource[$RECORD->IDField] = $RECORD->IDFieldValue;
                $fields[] = array('name' => 'InstructionScope[]', 'caption' => 'Scope', 'kind' => 'control', 'type' => 'multi', 'options' => $scopes->Scopes, 'required' => TRUE, 'allowempty' => FALSE);
            }
            $fields[] = array('name' => $RECORD->IDField, 'kind' => 'hidden');
            $fields[] = array('name' => 'AccountHolder', 'caption' => 'Account Holder', 'kind' => 'control', 'type' => 'string', 'required' => TRUE);
            $fields[] = array('name' => 'SortCode', 'caption' => 'Sort Code', 'kind' => 'control', 'type' => 'string', 'required' => TRUE, 'size' => 6, 'encrypted' => TRUE);
            $fields[] = array('name' => 'AccountNo', 'caption' => 'Account No', 'kind' => 'control', 'type' => 'string', 'required' => TRUE, 'size' => 6, 'encrypted' => TRUE);
            $fields[] = array('name' => 'BankName', 'caption' => 'Bank Name', 'kind' => 'control', 'type' => 'string', 'required' => $SYSTEM_SETTINGS['Finance']['DirectDebit']['BankNameReq']);
            if(empty($_GET['DDIID'])) {
                if($SYSTEM_SETTINGS['Finance']['DirectDebit']['ReferenceReq']) {
                    $datasource['DDReference']=$SYSTEM_SETTINGS["General"]['OrgShortName'].time().'-'.$RECORD->IDFieldValue;
                }
                $fields[] = array('name' => 'DDReference', 'caption' => 'Reference', 'kind' => 'control', 'type' => 'string', 'required' => $SYSTEM_SETTINGS['Finance']['DirectDebit']['ReferenceReq'], 'size' => 6);
                $fields[] = array('name' => 'File', 'caption' => 'Upload File', 'kind' => 'control', 'type' => 'file', 'required' => $SYSTEM_SETTINGS['Finance']['DirectDebit']['DocumentReq']);
            } else {
                $fields[] = array('name' => 'FormalDDReference', 'caption' => 'Reference', 'kind' => 'static', 'type' => 'string', 'formatting' => '<b><info>');
//                $fields[] = array('name' => 'BankName', 'caption' => 'Bank Name', 'kind' => 'static', 'type' => 'string', 'formatting' => '<b><info>');
            }
/*                if (is_a($RECORD, 'crmOrganisation')) {
                    $fields[] = array('name' => 'OrganisationID', 'kind' => 'hidden');
                } elseif(is_a($RECORD, 'crmPerson')) {
                    $fields[] = array('name' => 'PersonID', 'kind' => 'hidden');
                }*/
            $fieldsets[] = array('fields' => $fields);
            $fieldsets[] = NoteFieldSet('finance');
            $formitem = array(
                'id' => 'frmEditDDI', 'style' => 'standard', 'spinner' => TRUE,
                'datasource' => $datasource, 'buttons' => array(),
                'fieldsets' => $fieldsets, 'borders' => FALSE
            );
            Form($formitem);            
            ModalBody(TRUE);
            ModalFooter(
                "frmEditDDI",
                "/syscall.php?do=saveddi",
                "function( frmElement, jsonResponse ) { RefreshDataTable(dt_record_ddi); RefreshDataTable(dt_record_invoices); InvalidateTab(['tab-membership', 'tab-notes']); ReloadSidebar('sidebar_membership'); }",
                "Save changes",
                "",
                "function( frmElement ) { return ValidateBankForm(frmElement, { method: 'pcapredict', apikey: '{$SYSTEM_SETTINGS["Credentials"]['PCAPredict']['APIKeys']['DDI']}' }); }"
            );
            break;
        case 'addworkflow':
        case 'editrecordworkflow':
        case 'editworkflowlist':
//        case 'editworkflow':
            ModalHeader((!empty($_GET['WorkflowItemID']) ? 'Edit Workflow' : 'Add to Workflow'));
            ModalBody(FALSE);
            $RECORD = GetParentRecord();
            if(!empty($RECORD)) {
                $users = new crmSystemUsers($SYSTEM_SETTINGS['Database']);
                $data = $RECORD->GetRecord();
                if(!empty($_GET['WorkflowItemID'])) {
                    $wfitem = new crmWorkflowItem($SYSTEM_SETTINGS['Database'], $_GET['WorkflowItemID']);
                    $datasource = $wfitem->WorkflowItem;
                    $categories = $wfitem->WorkflowCategories;
                }
                if(empty($datasource)) {
                    $datasource = $_GET;
                    $categories = new crmWorkflowCategories($SYSTEM_SETTINGS['Database']);
                }
                if(empty($_GET['WorkflowItemID']) && !is_null($data['WorkflowStatus'])) {
                    SimpleAlertBox('warning', 'Note: This record is already included in one or more existing workflows. If you continue, an <b>additional</b> workflow item will be created.');
                }
                $fieldsets = array();
                $fields = array();
                if (is_a($RECORD, 'crmOrganisation')) {
                    $fields[] = array('name' => 'OrganisationID', 'kind' => 'hidden');
                } elseif(is_a($RECORD, 'crmPerson')) {
                    $fields[] = array('name' => 'PersonID', 'kind' => 'hidden');
                }
                $fields = array_merge($fields, array(
                    array('name' => 'WorkflowItemID', 'kind' => 'hidden'),
                    array('name' => 'Categories[]', 'caption' => 'Category', 'kind' => 'control', 'type' => 'multi', 'allowempty' => FALSE, 'options' => $categories->GetCategories(), 'selected' => (empty($datasource['WSCategories']) ? $categories->DefCategories : $datasource['WSCategories']), 'required' => TRUE),
                    array('name' => 'Priority', 'caption' => 'Priority', 'kind' => 'control', 'type' => 'radios', 'allowempty' => FALSE, 'options' => PriorityItems(), 'required' => TRUE),
                    array('name' => 'AssignedID', 'caption' => 'Assign To', 'kind' => 'control', 'type' => 'advcombo', 'allowempty' => FALSE, 'options' => $users->GetUsers(TRUE), 'required' => TRUE),
                ));
                $fieldsets[] = array('fields' => $fields);
                $formitem = array(
                    'id' => 'frmEditWorkflow', 'style' => 'standard',
                    'datasource' => $datasource, 'buttons' => array(),
                    'fieldsets' => $fieldsets, 'borders' => FALSE
                );
                Form($formitem);            
            } else {
                SimpleAlertBox('error', 'No parent record found.');
            }
            ModalBody(TRUE);
            switch($do) {
                case 'editrecordworkflow':
                    $cbSuccess = "function( frmElement, jsonResponse ) { RefreshDataTable(dt_record_workflow); }";
                    break;
                case 'editworkflowlist':
                    $cbSuccess = "function( frmElement, jsonResponse ) { RefreshDataTable(dt_workflow); }";
                    break;
                default:
                    $cbSuccess = "function( frmElement, jsonResponse ) { ReloadRecordFromResponse(frmElement, jsonResponse, 'workflow'); }";
            }
            ModalFooter("frmEditWorkflow", "/syscall.php?do=saveworkflowitem", $cbSuccess);
            break;
        case 'msstartapplicationdlg':
            ModalHeader('Start Application');
            ModalBody(FALSE);
            $grades = new crmMSGrades($SYSTEM_SETTINGS['Database']);
            $countries = new crmCountries($SYSTEM_SETTINGS['Database']);
            $PERSON = new crmPerson('eventsPerson', $SYSTEM_SETTINGS['Database'], intval($_GET['PersonID']), $SYSTEM_SETTINGS["Membership"]);
            $datasource = array_merge($PERSON->GetRecord(), array('SelectorID' => 'members', 'NOY' => 1));
            $fieldsets = array();
            $fields = array(
                array('name' => 'PersonID', 'kind' => 'hidden'),
                array('name' => 'SelectorID', 'kind' => 'hidden'),
                array('name' => 'MSGradeID', 'caption' => 'Select '.$SYSTEM_SETTINGS['Membership']['GradeCaption'], 'kind' => 'control', 'type' => 'advcombo', 'allowempty' => FALSE, 'options' => $grades->GetGrades(), 'required' => TRUE),
                array('name' => 'ISO4217', 'caption' => 'Inv. Currency', 'kind' => 'control', 'type' => 'advcombo', 'required' => TRUE, 'allowempty' => FALSE, 'size' => 6, 'options' => $PERSON->Countries->Currencies),
                array('name' => 'NOY', 'caption' => 'No of Years', 'kind' => 'control', 'type' => 'integer', 'size' => 4, 'min' => 1, 'required' => TRUE),
            );
            $discount = LocateDiscount(array('PersonID' => $PERSON->PersonID, 'CategorySelector' => 'members', 'Mnemonic' => 'ms_new'), FALSE);
            if(!empty($discount)) {
                //There is a stored discount available for this record
                foreach(array('DiscountToPersonID', 'DiscountID') AS $key) {
                    $fields[] = array('name' => $key, 'kind' => 'hidden');
                    $datasource[$key] = $discount[$key];
                }
                SimpleAlertBox('info', 'The following discount code is attached to the person record and will be applied to this application: '.$discount['Description'].' ['.$discount['DiscountCode'].']');
            } else {
                $discounts = ListDiscounts(array(
                    'CategorySelector' => 'members',
                    'InvoiceItemTypeID' => 'ms_new',
                    'InclNone' => TRUE,
                ));
                $fields[] = array('name' => 'DiscountID', 'caption' => 'Discount', 'kind' => 'control', 'type' => 'advcombo', 'options' => $discounts);
            }
            $fieldsets[] = array('fields' => $fields);
            $formitem = array(
                'id' => 'frmMSNewApplication', 'style' => 'standard',
                'datasource' => $datasource, 'buttons' => array(),
                'fieldsets' => $fieldsets, 'borders' => FALSE
            );
            Form($formitem);            
            ModalBody(TRUE);
            ModalFooter("frmMSNewApplication", "/syscall.php?do=startapplication", "function( frmElement, jsonResponse ){ ReloadTab('tab-membership', ['sidebar_membership', 'sidebar_recinfo'], 'tab-personal'); }", "Start Application");                
            break;
        case 'msstarttransferdlg':
            ModalHeader('Start Transfer');
            ModalBody(FALSE);
            $PERSON = new crmPerson('eventsPerson', $SYSTEM_SETTINGS['Database'], intval($_GET['PersonID']), $SYSTEM_SETTINGS["Membership"]);
            $renewal = $PERSON->RenewalSettings();
            if(($renewal['DaysToRenewal']-$SYSTEM_SETTINGS['Membership']['RenewalCycleStart']) <=($SYSTEM_SETTINGS['Membership']['TransferCutoff']*2)) {
                Div(array('class' => 'row'), 7);
                Div(array('class' => 'col-xs-12'), 8);
                    SimpleAlertBox('warning', 'A transfer request must be completed before the renewal cycle starts. Open transfer requests are cancelled when the renewal cycle for this record begins. <b>The renewal cycle for this record will begin on '.date('j F Y', strtotime($renewal['StartRenewalCycle'])).'.</b>');
                Div(null, 8);
                Div(null, 7);
            }
            Div(array('class' => 'row'), 7);
            Div(array('class' => 'col-xs-7'), 8);
                $grades = new crmMSGrades($SYSTEM_SETTINGS['Database']);
                $countries = new crmCountries($SYSTEM_SETTINGS['Database']);
                $currency = new crmCurrency($SYSTEM_SETTINGS['Database'], $renewal['ISO4217']);
                $datasource = array_merge($renewal, array('SelectorID' => 'members', 'Symbol' => $currency->Symbol));
                $datasource['NewMSGradeID'] = $grades->NextGradeID($datasource['MSGradeID'], FALSE, FALSE);
                $datasource['ItemNet'] = 0;
                $datasource['ItemVATRate'] = 0;
                
                $fieldsets = array();
                $fields = array(
                    array('name' => 'PersonID', 'kind' => 'hidden'),
                    array('name' => 'SelectorID', 'kind' => 'hidden'),
                    array('name' => 'DaysToRenewal', 'kind' => 'hidden'),
                    array('name' => 'ISO4217', 'kind' => 'hidden'),
                    array('name' => 'ISO3166', 'kind' => 'hidden'),
                    array('name' => 'MSGradeID', 'kind' => 'hidden'),
                    array('name' => 'GradeCaption', 'caption' => 'Current '.$SYSTEM_SETTINGS['Membership']['GradeCaption'], 'kind' => 'static', 'type' => 'string', 'formatting' => '<b><primary>'),
                    array('class' => 'calcvariables', 'name' => 'NewMSGradeID', 'caption' => 'New '.$SYSTEM_SETTINGS['Membership']['GradeCaption'], 'kind' => 'control', 'type' => 'advcombo', 'allowempty' => FALSE, 'options' => $grades->GetGrades(), 'required' => TRUE),
                    array('class' => 'calculation', 'name' => 'ItemNet', 'caption' => 'Net Value', 'kind' => 'control', 'type' => 'money', 'currencysymbol' => $datasource['Symbol'], 'required' => TRUE),
                    array('class' => 'calculation', 'name' => 'ItemVATRate', 'caption' => 'VAT Rate', 'kind' => 'control', 'type' => 'combo', 'options' => VATRates()),
                );
                $init = array(
                    "$('.calcvariables').on('change keyup paste cut', function() {\n",
                    "\tvar params = { MSGradeID: $(\"#frmMSTransfer input[name='MSGradeID']\").val(), NewMSGradeID: $(\"#frmMSTransfer select[name='NewMSGradeID']\").val(), DaysToRenewal: $(\"#frmMSTransfer input[name='DaysToRenewal']\").val(), ISO4217: $(\"#frmMSTransfer input[name='ISO4217']\").val(), ISO3166: $(\"#frmMSTransfer input[name='ISO3166']\").val() };\n",
                    "\texecSyscall('/syscall.php?do=getmsfeedifference', { parseJSON: true, postparams: params, cbSuccess: function(response){ $(\"#frmMSTransfer input[name='ItemNet']\").val( response.strings.net ); $(\"#frmMSTransfer select[name='ItemVATRate']\").val( response.values.vatrate ); $(\"#frmMSTransfer select[name='ItemVATRate']\").trigger('change'); } }) ;\n",
                    "});\n",
                    "$('.calculation').on('change keyup paste cut', function() {\n",
                    "\tvar vatrate = $('[name=\"ItemVATRate\"]').val();\n",
                    "\tprice = CalculatePrice( 'ItemNet', vatrate, 1, '{$datasource['ISO4217']}' );\n",
                    "\t$('#calcSubtotal').text(price.strings.net);\n",
                    "\t$('#calcVATSubtotal').text(price.strings.vat);\n",
                    "\t$('#calcTotal').text(price.strings.value);\n",
                    "});\n",
                    "\t$(\"#frmMSTransfer select[name='NewMSGradeID']\").trigger('change');\n",
                    
                );
                $fieldsets[] = array('fields' => $fields);
                $formitem = array(
                    'id' => 'frmMSTransfer', 'style' => 'standard',
                    'datasource' => $datasource, 'buttons' => array(),
                    'fieldsets' => $fieldsets, 'borders' => FALSE,
                    'oninitialise' => $init
                );
                Form($formitem);            
            Div(null, 8);
            Div(array('class' => 'col-xs-5'), 8);
                echo str_repeat("\t", 10)."<table class=\"table table-vcenter\">\n";
                echo str_repeat("\t", 11)."<thead>\n";
                echo str_repeat("\t", 12)."<tr>\n";
                echo str_repeat("\t", 13)."<th class=\"text-center\"></th>\n";
                echo str_repeat("\t", 13)."<th class=\"text-right\"></th>\n";
                echo str_repeat("\t", 12)."</tr>\n";
                echo str_repeat("\t", 11)."</thead>\n";
                echo str_repeat("\t", 11)."<tbody>\n";
                echo str_repeat("\t", 12)."<tr class=\"active\">\n";
                echo str_repeat("\t", 13)."<td colspan=\"4\" class=\"text-right\"><span class=\"h4\">SUBTOTAL</span></td>\n";
                echo str_repeat("\t", 13)."<td class=\"text-right\"><span class=\"h4\" id=\"calcSubtotal\">".ScaledIntegerAsString(0, "money", 100, FALSE, $datasource['Symbol'])."</span></td>\n";
                echo str_repeat("\t", 12)."</tr>\n";
                echo str_repeat("\t", 12)."<tr class=\"active\">\n";
                echo str_repeat("\t", 13)."<td colspan=\"4\" class=\"text-right\"><span class=\"h4\">VAT</span></td>\n";
                echo str_repeat("\t", 13)."<td class=\"text-right\"><span class=\"h4\" id=\"calcVATSubtotal\">".ScaledIntegerAsString(0, "money", 100, FALSE, $datasource['Symbol'])."</span></td>\n";
                echo str_repeat("\t", 12)."</tr>\n";
                echo str_repeat("\t", 12)."<tr class=\"active\">\n";
                echo str_repeat("\t", 13)."<td colspan=\"4\" class=\"text-right\"><span class=\"h4\"><strong>ITEM TOTAL</strong></span></td>\n";
                echo str_repeat("\t", 13)."<td class=\"text-right\"><span class=\"h4\"><strong><span id=\"calcTotal\">".ScaledIntegerAsString(0, "money", 100, FALSE, $datasource['Symbol'])."</span></strong></span></td>\n";
                echo str_repeat("\t", 12)."</tr>\n";
                echo str_repeat("\t", 11)."</tbody>\n";
                echo str_repeat("\t", 10)."</table>\n";
            Div(null, 8);
            Div(null, 7); //first row closure
            ModalBody(TRUE);
            ModalFooter("frmMSTransfer", "/syscall.php?do=starttransfer", "function( frmElement, jsonResponse ){ ReloadTab('tab-membership', 'sidebar_membership', 'tab-personal'); }", "Start Transfer");
            break;
        case 'msstartrejoindlg':
            ModalHeader('Start Rejoin');
            ModalBody(FALSE);
            Div(array('class' => 'row'), 7);
            Div(array('class' => 'col-xs-7'), 8);
                $grades = new crmMSGrades($SYSTEM_SETTINGS['Database']);
                $countries = new crmCountries($SYSTEM_SETTINGS['Database']);
                $PERSON = new crmPerson('eventsPerson', $SYSTEM_SETTINGS['Database'], intval($_GET['PersonID']), $SYSTEM_SETTINGS["Membership"]);
                $rejoininfo = $PERSON->GetRejoinInfo();
                $datasource = array_merge($PERSON->GetRecord(), array('SelectorID' => 'members'));
                if($rejoininfo['Found']) {
                    foreach(array('MSGradeID', 'RejoinDate', 'AgeText', 'Multiplier', 'MSNextRenewal') AS $key) {
                        $datasource[$key] = $rejoininfo[$key];
                    }
                }
                $datasource['AnchorPersonMSID'] = $rejoininfo['Anchor']['PersonMSID'];
                $msfee = new crmMSFees($SYSTEM_SETTINGS['Database'], $SYSTEM_SETTINGS['Membership']['GradeCaption']);
                $fee = $msfee->CalculateFee(array(
                    'ISO4217' => $datasource['ISO4217'],
                    'ISO3166' => $datasource['ISO3166'],
                    'MSGradeID' => $datasource['MSGradeID'],
                    'IsDD' => $PERSON->HasValidDDI(),
                    'Mnemonic' => 'ms_rejoin',
                    'NOY' => 1
                ));
                $fee->Price->Net = $fee->Price->Net*$rejoininfo['Multiplier'];
                foreach(array('ItemNet' => 'Net', 'ItemVATRate' => 'VATRate', 'ItemVAT' => 'VAT', 'ItemTotal' => 'Value', 'Symbol' => 'Symbol') AS $targetkey => $sourcekey) {
                    $datasource[$targetkey] = $fee->$sourcekey;
                }
/*                $datasource['ItemNet'] = ($fee->Net*$rejoininfo['Multiplier']);
                $datasource['ItemVATRate'] = ($fee->VATRate);
                $datasource['ItemVAT'] = ($fee->VAT);
                $datasource['ItemTotal'] = ($fee->Value);
                $datasource['Symbol'] = $fee->Symbol;*/
                $fieldsets = array();
                $fields = array(
                    array('name' => 'PersonID', 'kind' => 'hidden'),
                    array('name' => 'SelectorID', 'kind' => 'hidden'),
                    array('name' => 'AnchorPersonMSID', 'kind' => 'hidden'),
                    array('name' => 'Multiplier', 'kind' => 'hidden'),
                    array('name' => 'ISO4217', 'kind' => 'hidden'),
                    array('name' => 'ISO3166', 'kind' => 'hidden'),
                    array('name' => 'AgeText', 'caption' => 'Lapsed Time', 'kind' => 'static', 'type' => 'string', 'formatting' => '<b><primary>'),
                    array('name' => 'RejoinDate', 'caption' => 'Rejoin Date', 'kind' => 'control', 'type' => 'date', 'utcsource' => TRUE, 'allowempty' => FALSE, 'required' => TRUE),
                    array('name' => 'MSGradeID', 'caption' => 'Select '.$SYSTEM_SETTINGS['Membership']['GradeCaption'], 'kind' => 'control', 'type' => 'advcombo', 'allowempty' => FALSE, 'options' => $grades->GetGrades(), 'required' => TRUE),
                    array('class' => 'calculation', 'name' => 'ItemNet', 'caption' => 'Net Value', 'kind' => 'control', 'type' => 'money', 'currencysymbol' => $datasource['Symbol'], 'required' => TRUE),
                    array('class' => 'calculation', 'name' => 'ItemVATRate', 'caption' => 'VAT Rate', 'kind' => 'control', 'type' => 'combo', 'options' => VATRates()),
                    array('name' => 'MSNextRenewal', 'caption' => 'Next Renewal', 'kind' => 'control', 'type' => 'date', 'allowempty' => FALSE, 'required' => TRUE, 'hint' => 'Enter a new renewal date'),
                );
                $init = array(
                    "$('.calculation').on('change keyup paste cut', function() {\n",
                    "\tvar vatrate = $('[name=\"ItemVATRate\"]').val();",
                    "\tprice = CalculatePrice( 'ItemNet', vatrate, 1, '{$datasource['ISO4217']}' );\n",
                    "\t$('#calcSubtotal').text(price.strings.net);\n",
                    "\t$('#calcVATSubtotal').text(price.strings.vat);\n",
                    "\t$('#calcTotal').text(price.strings.value);\n",
                    "});",
                );
                $fieldsets[] = array('fields' => $fields);
                $formitem = array(
                    'id' => 'frmMSRejoin', 'style' => 'standard',
                    'datasource' => $datasource, 'buttons' => array(),
                    'fieldsets' => $fieldsets, 'borders' => FALSE,
                    'oninitialise' => $init
                );
                Form($formitem);            
            Div(null, 8);
            Div(array('class' => 'col-xs-5'), 8);
                echo str_repeat("\t", 10)."<table class=\"table table-vcenter\">\n";
                echo str_repeat("\t", 11)."<thead>\n";
                echo str_repeat("\t", 12)."<tr>\n";
                echo str_repeat("\t", 13)."<th class=\"text-center\"></th>\n";
                echo str_repeat("\t", 13)."<th class=\"text-right\"></th>\n";
                echo str_repeat("\t", 12)."</tr>\n";
                echo str_repeat("\t", 11)."</thead>\n";
                echo str_repeat("\t", 11)."<tbody>\n";
                echo str_repeat("\t", 12)."<tr class=\"active\">\n";
                echo str_repeat("\t", 13)."<td colspan=\"4\" class=\"text-right\"><span class=\"h4\">SUBTOTAL</span></td>\n";
                echo str_repeat("\t", 13)."<td class=\"text-right\"><span class=\"h4\" id=\"calcSubtotal\">".ScaledIntegerAsString($datasource['ItemNet'], "money", 100, FALSE, $datasource['Symbol'])."</span></td>\n";
                echo str_repeat("\t", 12)."</tr>\n";
                echo str_repeat("\t", 12)."<tr class=\"active\">\n";
                echo str_repeat("\t", 13)."<td colspan=\"4\" class=\"text-right\"><span class=\"h4\">VAT</span></td>\n";
                echo str_repeat("\t", 13)."<td class=\"text-right\"><span class=\"h4\" id=\"calcVATSubtotal\">".ScaledIntegerAsString($datasource['ItemVAT'], "money", 100, FALSE, $datasource['Symbol'])."</span></td>\n";
                echo str_repeat("\t", 12)."</tr>\n";
                echo str_repeat("\t", 12)."<tr class=\"active\">\n";
                echo str_repeat("\t", 13)."<td colspan=\"4\" class=\"text-right\"><span class=\"h4\"><strong>ITEM TOTAL</strong></span></td>\n";
                echo str_repeat("\t", 13)."<td class=\"text-right\"><span class=\"h4\"><strong><span id=\"calcTotal\">".ScaledIntegerAsString($datasource['ItemTotal'], "money", 100, FALSE, $datasource['Symbol'])."</span></strong></span></td>\n";
                echo str_repeat("\t", 12)."</tr>\n";
                echo str_repeat("\t", 11)."</tbody>\n";
                echo str_repeat("\t", 10)."</table>\n";
            Div(null, 8);
            Div(null, 7); //first row closure
            ModalBody(TRUE);
            ModalFooter("frmMSRejoin", "/syscall.php?do=startrejoin", "function( frmElement, jsonResponse ){ ReloadTab('tab-membership', 'sidebar_membership', 'tab-personal'); }", "Start Rejoin");
            break;
        case 'editproposerreferee':
            ModalHeader('Edit Application');
            ModalBody(FALSE);
            $msappmodel = new crmMSApplicationModel($SYSTEM_SETTINGS['Database'], $SYSTEM_SETTINGS['Membership']);
            $msapplication = $msappmodel->GetApplicationByID(intval($_GET['ApplicationID']));
            if($msapplication->Application['IsOpen']) {
                $component = (isset($msapplication->Application['ApplComponents']['referee']) ? 'referee' : 'proposer');
                $name = ucfirst($component);
                $fieldsets = array();
                $fields = array(
                    array('name' => 'PersonID', 'kind' => 'hidden'),
                    array('name' => 'ApplicationID', 'kind' => 'hidden')
                );
                if($component == 'referee') {
                    $refereetypes = new crmRefereeTypes($SYSTEM_SETTINGS['Database']);
                    $fields[] = array('name' => "RefereeTypeID", 'caption' => "Referee Type", 'kind' => 'control', 'type' => 'combo', 'options' => $refereetypes->GetRefereeTypes());
                }
                $fields = array_merge($fields, array(
                    array('name' => "{$name}MSNumber", 'caption' => "MS Number {$name}", 'kind' => 'control', 'type' => 'string'),
                    array('name' => "{$name}Email", 'caption' => "{$name} Email", 'kind' => 'control', 'type' => 'email'),
                    array('name' => "{$name}Name", 'caption' => "{$name} Name", 'kind' => 'control', 'type' => 'string',
                        'rightaddon' => array(
                            'type' => 'button', 'icon' => 'fa-search', 'tooltip' => 'Lookup from Membership number or Email',
                            'script' => "Lookup( this, '{$name}');"
                        )
                    ),
                    array('name' => "{$name}Affiliation", 'caption' => "{$name} Affiliation", 'kind' => 'control', 'type' => 'string'),
                ));
                $fieldsets[] = array('fields' => $fields);
                $formitem = array(
                    'id' => 'frmEditProposerReferee', 'style' => 'standard',
                    'datasource' => $msapplication->Application, 'buttons' => array(),
                    'fieldsets' => $fieldsets, 'borders' => FALSE
                );
                Form($formitem);
            } else {
                SimpleAlertBox('error', 'This application is closed.');
            }
            ModalBody(TRUE);
            ModalFooter("frmEditProposerReferee", "/syscall.php?do=saveproposerreferee", "function( frmElement, jsonResponse ){ ReloadTab('tab-membership', 'sidebar_membership'); }", "Save Changes");                
            break;
        case 'mselectpersondlg':
            ModalHeader('Complete Election');
            ModalBody(FALSE);
            $msappmodel = new crmMSApplicationModel($SYSTEM_SETTINGS['Database'], $SYSTEM_SETTINGS['Membership']);
            $msapplication = $msappmodel->GetApplicationByID(intval($_GET['ApplicationID']));
            $grades = new crmMSGrades($SYSTEM_SETTINGS['Database']);
            if($msapplication->Application['IsOpen']) {
                $fieldsets = array();
                $fields = array(
                    array('name' => 'ApplicationID', 'kind' => 'hidden'),
                    array('name' => 'GradeCaption', 'caption' => $SYSTEM_SETTINGS['Membership']['GradeCaption'], 'kind' => 'static', 'type' => 'string', 'formatting' => '<b><info>'),
                    array('name' => 'ElectionDate', 'caption' => 'Election Date', 'kind' => 'control', 'type' => 'date', 'utcsource' => TRUE, 'allowempty' => FALSE),
                );
                $fieldsets[] = array('fields' => $fields);
                $formitem = array(
                    'id' => 'frmElectPersonMS', 'style' => 'standard',
                    'datasource' => array_merge($msapplication->Application, array('ElectionDate' => CalculateMSElectionDate())), 'buttons' => array(),
                    'fieldsets' => $fieldsets, 'borders' => FALSE
                );
                Form($formitem);
            } else {
                SimpleAlertBox('error', 'This application is closed.');
            }
            ModalBody(TRUE);
            ModalFooter("frmElectPersonMS", "/syscall.php?do=completeelection", "function( frmElement, jsonResponse ){ ReloadTab('tab-membership', 'sidebar_membership'); }", "Apply Changes");                
            break;        
            break;
        case 'mschangeapplicationdlg':
            ModalHeader('Application Settings');
            ModalBody(FALSE);
            $msappmodel = new crmMSApplicationModel($SYSTEM_SETTINGS['Database'], $SYSTEM_SETTINGS['Membership']);
            $msapplication = $msappmodel->GetApplicationByID(intval($_GET['ApplicationID']));
            if($msapplication->Application['IsOpen']) {
                $grades = new crmMSGrades($SYSTEM_SETTINGS['Database']);
                $discounts = ListDiscounts(array(
                    'CategorySelector' => 'members',
                    'InvoiceItemTypeID' => 'ms_new',
                    'InclNone' => TRUE,
                ));
                $fieldsets = array();
                $fields = array(
                    array('name' => 'ApplicationID', 'kind' => 'hidden'),
                    array('name' => 'ApplicationStageID', 'caption' => 'Status', 'kind' => 'control', 'type' => 'advcombo', 'allowempty' => FALSE, 'options' => $msappmodel->GetStageItems(), 'required' => TRUE, 'hint' => 'It is not recommended to bypass the status via this dialogue'),
                    array('name' => 'MSGradeID', 'caption' => 'Select '.$SYSTEM_SETTINGS['Membership']['GradeCaption'], 'kind' => 'control', 'type' => 'advcombo', 'allowempty' => FALSE, 'options' => $grades->GetGrades(), 'required' => TRUE),
                    array('name' => 'NOY', 'caption' => 'No of Years', 'kind' => 'control', 'type' => 'integer', 'size' => 4, 'min' => 1, 'required' => TRUE),
                    array('name' => 'DiscountID', 'caption' => 'Discount', 'kind' => 'control', 'type' => 'advcombo', 'options' => $discounts),                    
                );
                if(!$msapplication->Application['HasTransaction']) {
                    $fields[] = array('name' => 'Paid', 'tooltip' => 'Paid', 'type' => 'switch', 'kind' => 'control', 'colour' => 'info', 'hint' => 'Application has been Paid'); 
                }
                $fieldsets[] = array('fields' => $fields);
                $formitem = array(
                    'id' => 'frmChangeMSApplication', 'style' => 'standard',
                    'datasource' => $msapplication->Application, 'buttons' => array(),
                    'fieldsets' => $fieldsets, 'borders' => FALSE
                );
                Form($formitem);
            } else {
                SimpleAlertBox('error', 'This application is closed.');
            }
            ModalBody(TRUE);
            ModalFooter("frmChangeMSApplication", "/syscall.php?do=changeapplication", "function( frmElement, jsonResponse ){ ReloadTab('tab-membership', ['sidebar_recinfo', 'sidebar_membership']); }", "Apply Changes");                
            break;
        case 'editrenewal':
            ModalHeader('Edit Renewal');
            ModalBody(FALSE);
            $selector = IdentifierStr($_GET['SelectorID']);
            if($selector == 'members') {
                $PERSON = new crmPerson('eventsPerson', $SYSTEM_SETTINGS['Database'], intval($_GET['PersonID']), $SYSTEM_SETTINGS["Membership"]);
                $personal = $PERSON->GetRecord();
                if($personal['IsMember']) {
                    if(!$personal['NoRenewal']) {
                        $renewal = $PERSON->RenewalSettings($selector);
                        Div(array('class' => 'row'), 7);
                        Div(array('class' => 'col-xs-6'), 8); //Top row, left column
                            SimpleHeading('Current Membership', 4, 'sub');
                            Div(array('id' => 'dialog_Membership'));
                            Div(null);
                        Div(null, 8);
                        Div(array('class' => 'col-xs-6'), 8); //Top row, right column
                            if($renewal['HasTransaction']) {
                                SimpleAlertBox('warning', 'This renewal record has been invoiced on '.$renewal['InvoiceCaption'].'. Making changes to the renewal setting may cause this invoice transaction to be modified.');
                            }
                            if(empty($renewal['HasRenewal'])) {
                                SimpleAlertBox('info', 'There is no renewal record yet. Discount codes are attached to the record until the renewal record has been set up.');
                            }
                        Div(null, 8);
                        Div(null, 7);
                        Div(array('class' => 'row'), 7);
                        Div(array('class' => 'col-xs-12'), 8); //Bottom row, single column
                            SimpleHeading('Renewal Settings', 4, 'sub');
                            $grades = new crmMSGrades($SYSTEM_SETTINGS['Database']);
                            $countries = new crmCountries($SYSTEM_SETTINGS['Database']);
                            $fieldsets = array();
                            $fields = array(
                                array('name' => 'PersonID', 'kind' => 'hidden'),
                                array('name' => 'RenewalID', 'kind' => 'hidden'),
                                array('name' => 'WSCategoryID', 'kind' => 'hidden'),
                                array('name' => 'InvoiceItemID', 'kind' => 'hidden'),
                                array('name' => 'MSNextRenewal', 'caption' => 'Next Renewal', 'kind' => 'control', 'type' => 'date', 'required' => TRUE, 'showage' => TRUE),
                                array('name' => 'MSGradeID', 'caption' => 'Renewal '.$SYSTEM_SETTINGS['Membership']['GradeCaption'], 'kind' => 'control', 'type' => 'advcombo', 'allowempty' => FALSE, 'options' => $grades->GetGrades(), 'required' => TRUE),
                            );
                            if(!$renewal['HasTransaction'] || empty($renewal['InvoiceNo'])) {
                                $countries = new crmCountries($SYSTEM_SETTINGS['Database']);
                                $fields[] = array('name' => 'ISO4217', 'caption' => 'Change Currency', 'kind' => 'control', 'type' => 'advcombo', 'allowempty' => FALSE, 'size' => 6, 'options' => $PERSON->Countries->Currencies);
                            }
                            $discounts = ListDiscounts(array(
                                'CategorySelector' => $selector,
                                'InvoiceItemTypeID' => 'ms_renewal',
                                'InclNone' => TRUE,
                            ));
                            if(empty($renewal['HasRenewal'])) {
                                //Check if a discount code has been attached to this record
                                $discount = LocateDiscount(array('PersonID' => $PERSON->PersonID, 'CategorySelector' => 'members', 'Mnemonic' => 'ms_renewal'), FALSE);
                                if(!empty($discount)) {
                                    $renewal['DiscountTxt'] = $discount['DiscountCode'].', '.$discount['Description'];
                                    $fields[] = array('name' => 'DiscountTxt', 'caption' => 'Discount Code', 'kind' => 'static', 'formatting' => '<info>');
                                } else {
                                    $fields[] = array('name' => 'DiscountID', 'caption' => 'Discount', 'kind' => 'control', 'type' => 'advcombo', 'options' => $discounts);
                                }
                            } else {
                                $fields[] = array('name' => 'DiscountID', 'caption' => 'Discount', 'kind' => 'control', 'type' => 'advcombo', 'options' => $discounts);
                            }
                            //$fields[] = array('name' => 'DiscountID', 'caption' => 'Discount', 'kind' => 'control', 'type' => 'advcombo', 'options' => $discounts, 'hint' => (empty($renewal['HasRenewal']) ? 'There is no renewal record yet. Discount codes are attached to the record until the renewal record is set up.' : null));
                            $fields[] = array('name' => 'MSFree', 'tooltip' => 'Free Membership', 'type' => 'switch', 'kind' => 'control', 'colour' => 'danger', 'hint' => 'Select to renew as Free membership'); 
                            $fieldsets[] = array('fields' => $fields);
                            $formitem = array(
                                'id' => 'frmEditRenewal', 'style' => 'standard',
                                'datasource' => $renewal, 'buttons' => array(),
                                'fieldsets' => $fieldsets, 'borders' => FALSE,
                                'oninitialise' => "LoadContent('dialog_Membership', 'load.php?do=sidebar_membership', { spinner: true, urlparams: {personid: {$PERSON->PersonID}, nochrome: true} } );",
                            );
                            Form($formitem);
                        Div(null, 8);
                        Div(null, 7);
                    } else {
                        SimpleAlertBox('error', 'This record has been set to Do not Renew');
                    }
                } else {
                    SimpleAlertBox('error', 'This person is not a member');
                }
            }
            ModalBody(TRUE);
            ModalFooter("frmEditRenewal", "/syscall.php?do=saverenewal", "function( frmElement, jsonResponse ){ ReloadTab('tab-membership', ['sidebar_membership', 'sidebar_recinfo'], ['tab-personal', 'tab-notes'] ); }");
            break;
        case 'cancelapplicationdlg':
            ModalHeader('Cancel Application');
            ModalBody(FALSE);
            $msappmodel = new crmMSApplicationModel($SYSTEM_SETTINGS['Database'], $SYSTEM_SETTINGS['Membership']);
            $msapplication = $msappmodel->GetApplicationByID(intval($_GET['ApplicationID']));
            if($msapplication->Application['IsOpen']) {
                $fieldsets = array();
                $fieldsets[] = array('fields' => array(
                    array('name' => 'ApplicationID', 'kind' => 'hidden'),
                    array('name' => 'DoNotContact', 'tooltip' => 'Do not Contact', 'type' => 'switch', 'kind' => 'control', 'colour' => 'danger', 'hint' => 'Do not Contact'),
                    array('name' => 'NoEmail', 'tooltip' => 'Skip Email', 'type' => 'switch', 'kind' => 'control', 'colour' => 'warning', 'hint' => 'Do not send an automatic email notice'),
//                    array('name' => 'NoteText', 'caption' => 'Add Note', 'kind' => 'control', 'type' => 'memo', 'rows' => 4, 'hint' => 'Optional, use to specify reasons or other relevant information'),
                ));
                $fieldsets[] = NoteFieldSet('members');
/*                if(!empty($SYSTEM_SETTINGS['ExpiryPolicies']['Notes'])) {
                    $fieldsets[0]['fields'][] = array('name' => 'NoteNoExpiry', 'tooltip' => 'Do not Expire', 'type' => 'switch', 'kind' => 'control', 'colour' => 'info', 'hint' => 'Do not automatically remove the note after '.SinPlu($SYSTEM_SETTINGS['ExpiryPolicies']['Notes'], 'month'));
                }*/
                $formitem = array(
                    'id' => 'frmCancelApplication', 'style' => 'standard',
                    'datasource' => $msapplication->Application, 'buttons' => array(),
                    'fieldsets' => $fieldsets, 'borders' => FALSE
                );
                Form($formitem);            
            } else {
                SimpleAlertBox('error', 'This application is already closed.');
            }
            ModalBody(TRUE);
            ModalFooter("frmCancelApplication", "/syscall.php?do=cancelapplication", "function( frmElement, jsonResponse ){ ReloadTab('tab-membership', 'sidebar_membership', 'tab-notes'); }", "Cancel Application");                
            break;
        case 'cancelrejoindlg':
            ModalHeader('Cancel Rejoin');
            ModalBody(FALSE);
            $REJOIN = new crmRejoin($SYSTEM_SETTINGS['Database'], $_GET['RejoinID']);
            if($REJOIN->Found && $REJOIN->Rejoin['IsOpen']) {
                $fieldsets = array();
                $fieldsets[] = array('fields' => array(
                    array('name' => 'RejoinID', 'kind' => 'hidden'),
                    array('name' => 'NoEmail', 'tooltip' => 'Skip Email', 'type' => 'switch', 'kind' => 'control', 'colour' => 'warning', 'hint' => 'Do not send an automatic email notice'),
                ));
                $fieldsets[] = NoteFieldSet('members');
                $formitem = array(
                    'id' => 'frmCancelRejoin', 'style' => 'standard',
                    'datasource' => $REJOIN->Rejoin, 'buttons' => array(),
                    'fieldsets' => $fieldsets, 'borders' => FALSE
                );
                Form($formitem);            
            } else {
                SimpleAlertBox('error', 'This rejoin request is closed.');
            }
            ModalBody(TRUE);
            ModalFooter("frmCancelRejoin", "/syscall.php?do=cancelrejoin", "function( frmElement, jsonResponse ){ ReloadTab('tab-membership', 'sidebar_membership', 'tab-notes'); }", "Cancel Rejoin");
            break;
        case 'canceltransferdlg':
            ModalHeader('Cancel Transfer');
            ModalBody(FALSE);
            $TRANSFER = new crmTransfer($SYSTEM_SETTINGS['Database'], $_GET['TransferID']);
            if($TRANSFER->Found && $TRANSFER->Transfer['IsOpen']) {
                $fieldsets = array();
                $fieldsets[] = array('fields' => array(
                    array('name' => 'TransferID', 'kind' => 'hidden'),
                    array('name' => 'NoEmail', 'tooltip' => 'Skip Email', 'type' => 'switch', 'kind' => 'control', 'colour' => 'warning', 'hint' => 'Do not send an automatic email notice'),
                ));
                $fieldsets[] = NoteFieldSet('members');
                $formitem = array(
                    'id' => 'frmCancelTransfer', 'style' => 'standard',
                    'datasource' => $TRANSFER->Transfer, 'buttons' => array(),
                    'fieldsets' => $fieldsets, 'borders' => FALSE
                );
                Form($formitem);            
            } else {
                SimpleAlertBox('error', 'This transfer request is closed.');
            }
            ModalBody(TRUE);
            ModalFooter("frmCancelTransfer", "/syscall.php?do=canceltransfer", "function( frmElement, jsonResponse ){ ReloadTab('tab-membership', 'sidebar_membership', 'tab-notes'); }", "Cancel Transfer");
            break;
        case 'editemail':
            ModalHeader('Email Address');
            ModalBody(FALSE);
            $fieldsets = array();
            $fieldsets[] = array('fields' => array(
                array('name' => 'EmailID', 'kind' => 'hidden'),
                array('name' => 'PersonID', 'kind' => 'hidden'),
                array('name' => 'Email', 'caption' => 'Email Address', 'kind' => 'control', 'type' => 'email', 'required' => 'Enter a valid email address'),
            ));
            $formitem = array(
                'id' => 'frmEditEmail', 'style' => 'standard', 
                'datasource' => $_GET, 'buttons' => array(),
                'fieldsets' => $fieldsets, 'borders' => FALSE
            );
            Form($formitem);
            ModalBody(TRUE);
            ModalFooter("frmEditEmail", "/syscall.php?do=saveemail", reloadCall($_GET));                
            break;
        case 'editcondsearchitem':
            ModalHeader('Search Condition');
            ModalBody(FALSE);
            $fields = new crmFields;
            $operators = new crmOperators;
            $fieldsets = array();
            $fieldsets[] = array('fields' => array(
                array('name' => 'ConditionID', 'kind' => 'hidden'),
                array('name' => 'Logic', 'caption' => 'Logic', 'kind' => 'control', 'type' => 'multichoice', 'options' => array('or' => 'Or', 'and' => 'And'), 'required' => TRUE),
                array('name' => 'Fieldname', 'caption' => 'Fieldname', 'kind' => 'control', 'type' => 'advgroupcombo', 'options' => $fields->Fields),
                array('name' => 'Operator', 'caption' => 'Operator', 'kind' => 'control', 'type' => 'advcombo', 'options' => $operators->Operators),
                array('name' => 'SearchValue', 'caption' => 'Search value', 'kind' => 'control', 'type' => 'string', 'required' => TRUE),
            ));
            $formitem = array(
                'id' => 'frmEditSearchCondition', 'style' => 'standard', 
                'datasource' => $_GET, 'buttons' => array(),
                'fieldsets' => $fieldsets, 'borders' => FALSE
            );
            Form($formitem);
            ModalBody(TRUE);
            NewModalFooter(array(
                'form' => $formitem,
                'url' => "/syscall.php?do=savesearchcondition".(!empty($_GET['queryid']) ? "&queryid=".intval($_GET['queryid']) : ""),
                'onsuccess' => "function( frmElement, jsonResponse ){ LoadContent('tab-condsearch', '/load.php?do=newsearchtab&tabid=tab-condsearch', { parseJSON: true, spinner: true, urlparams: { queryid: jsonResponse.queryid } } ); }",
            ));            
            break;
        case 'editgrantitem':
            ModalHeader('Grant');
            ModalBody(FALSE);
            $sql =
            "SELECT tblpersontogrant.PersonToGrantID, tblpersontogrant.GrantID, tblpersontogrant.Awarded, tblpersontogrant.Comment,
                    tblgrant.Title, tblgrant.Description,
                    CONCAT_WS(' ', tblperson.Title, tblperson.Firstname, tblperson.Middlenames, tblperson.Lastname) AS `Fullname`,
                    tblperson.PersonID
             FROM tblpersontogrant
             INNER JOIN tblperson ON tblperson.PersonID = tblpersontogrant.PersonID
             INNER JOIN tblgrant ON tblgrant.GrantID = tblpersontogrant.GrantID
             WHERE tblpersontogrant.PersonToGrantID = ".intval($_GET['PersonToGrantID']);
            $datasource = SingleRecord($SYSTEM_SETTINGS['Database'], $sql);
            $fieldsets = array();
            $fieldsets[] = array('fields' => array(
                array('name' => 'PersonToGrantID', 'kind' => 'hidden'),
                array('name' => 'GrantID', 'kind' => 'hidden'),
                array('name' => 'PersonID', 'kind' => 'hidden'),
                array('name' => 'Title', 'caption' => 'Grant', 'kind' => 'static', 'type' => 'string', 'formatting' => '<b><info>'),
                array('name' => 'Fullname', 'caption' => 'Name', 'kind' => 'static', 'type' => 'string', 'formatting' => '<info>'),
                array('name' => 'Awarded', 'caption' => 'Awarded', 'utcsource' => TRUE, 'kind' => 'control', 'type' => 'date', 'required' => TRUE),
                array('name' => 'Comment', 'caption' => 'Comment', 'kind' => 'control', 'type' => 'string'),
            ));
            $formitem = array(
                'id' => 'frmEditPersonGrantItem', 'style' => 'standard', 
                'datasource' => $datasource, 'buttons' => array(),
                'fieldsets' => $fieldsets, 'borders' => FALSE
            );
            Form($formitem);
            ModalBody(TRUE);
            ModalFooter("frmEditPersonGrantItem", "/syscall.php?do=savepersongrant", "function(){ RefreshDataTable(dt_grantpeople); }");                
            break;
        case 'editpersongrant':
            ModalHeader('Grant');
            ModalBody(FALSE);
            $sql = 
            "SELECT tblgrant.GrantID, tblgrant.Title, tblgrant.Description,
                    IF(tblpersontogrant.PersonToGrantID IS NULL, 0, 1) AS `Selected`,
                    tblperson.PersonID
             FROM tblgrant
             LEFT JOIN tblperson ON tblperson.PersonID = ".intval($_GET['PersonID'])."
             LEFT JOIN tblpersontogrant ON (tblpersontogrant.PersonID = tblperson.PersonID) AND (tblpersontogrant.GrantID = tblgrant.GrantID)
             ORDER BY tblgrant.Title";
            $query = mysqli_query($SYSTEM_SETTINGS['Database'], $sql);
            $options = array();
            $selected = null;
            $data = mysqli_fetch_all($query, MYSQLI_ASSOC);
            foreach($data AS $item) {
                $options[$item['GrantID']] = array('caption' => $item['Title'], 'tooltip' => $item['Description']);
                if($item['Selected']) {
                    $selected = $item['GrantID'];
                }
            }
            $fieldsets = array();
            $fieldsets[] = array('fields' => array(
                array('name' => 'PersonToGrantID', 'kind' => 'hidden'),
                array('name' => 'PersonID', 'kind' => 'hidden'),
                array('name' => 'GrantID', 'caption' => 'Grant', 'kind' => 'control', 'type' => 'advcombo', 'options' => $options, 'selected' => $selected, 'required' => 'Select a Grant'),
                array('name' => 'Awarded', 'caption' => 'Awarded', 'kind' => 'control', 'type' => 'date', 'required' => TRUE),
                array('name' => 'Comment', 'caption' => 'Comment', 'kind' => 'control', 'type' => 'string'),
            ));
            $formitem = array(
                'id' => 'frmEditPersonGrant', 'style' => 'standard', 
                'datasource' => $_GET, 'buttons' => array(),
                'fieldsets' => $fieldsets, 'borders' => FALSE
            );
            Form($formitem);
            ModalBody(TRUE);
            ModalFooter("frmEditPersonGrant", "/syscall.php?do=savepersongrant", reloadCall($_GET));                
            break;
        case 'editcommitteeitem':
            ModalHeader('Committee');
            ModalBody(FALSE);
            $fields = array();
            $fields[] = array('name' => 'PersonID', 'kind' => 'hidden');
            if(!empty($_GET['PersonToCommitteeID'])) {
                $existing = TRUE;
                $datasource = GetCommitteeEntry($_GET['PersonToCommitteeID']);
/*                $sql = 
                "SELECT tblpersontocommittee.PersonToCommitteeID, tblpersontocommittee.StartDate, tblpersontocommittee.EndDate, tblpersontocommittee.PersonID,
                        tblcommitteerole.CommitteeRoleID, tblpersontocommittee.Role,
                        IF( ((tblpersontocommittee.StartDate IS NULL) OR (tblpersontocommittee.StartDate <= UTC_TIMESTAMP())) AND ((tblpersontocommittee.EndDate IS NULL) OR (tblpersontocommittee.EndDate >= UTC_TIMESTAMP())), 1, 0) AS `IsCurrent`,
                        tblcommittee.CommitteeID, tblcommittee.CommitteeName
                 FROM tblpersontocommittee
                 INNER JOIN tblperson ON tblperson.PersonID = tblpersontocommittee.PersonID
                 INNER JOIN tblcommittee ON tblcommittee.CommitteeID = tblpersontocommittee.CommitteeID
                 LEFT JOIN tblcommitteerole ON tblcommitteerole.CommitteeRoleID = tblpersontocommittee.CommitteeRoleID
                 WHERE tblpersontocommittee.PersonToCommitteeID = ".intval($_GET['PersonToCommitteeID']);
                $datasource = SingleRecord($SYSTEM_SETTINGS['Database'], $sql);*/
                if(empty($datasource['IsCurrent'])) {
                    SimpleAlertBox('warning', 'You are editing a historic committee entry. You must ensure not to create entries with overlapping periods, as this may cause inconsistencies and errors.');
                } else {
                    SimpleAlertBox('warning', 'You are editing a committee entry. You must take care when making amendments not to create inconsistent or overlapping items. <b>When a committee member takes on a new role or comes to term end, you should <u>not</u> use this function.</b>');
                }
                $fields[] = array('name' => 'PersonToCommitteeID', 'kind' => 'hidden');
                $fields[] = array('name' => 'CommitteeID', 'kind' => 'hidden');
                $fields[] = array('name' => 'CommitteeName', 'caption' => 'Committee', 'kind' => 'static', 'type' => 'string', 'formatting' => '<b><info>');
            } else {
                $existing = FALSE;
                $datasource = array(
                    'PersonID' => intval($_GET['PersonID']),
                );
                $sql = 
                "SELECT tblcommittee.CommitteeID, tblcommittee.CommitteeName, tblcommittee.Description
                 FROM tblcommittee
                 ORDER BY CommitteeName";
                $query = mysqli_query($SYSTEM_SETTINGS['Database'], $sql);
                $data = mysqli_fetch_all($query, MYSQLI_ASSOC);
                $committees = array();
                foreach($data AS $item) {
                    $committees[$item['CommitteeID']] = array('caption' => $item['CommitteeName'], 'tooltip' => $item['Description']);
                }
                $fields[] = array('name' => 'CommitteeID', 'caption' => 'Committee', 'kind' => 'control', 'type' => 'advcombo', 'options' => $committees, 'required' => 'Select a Committee');
            }
            $sql = 
            "SELECT tblcommitteerole.CommitteeRoleID, tblcommitteerole.Role, tblcommitteerole.IsChair
             FROM tblcommitteerole
             ORDER BY tblcommitteerole.IsChair DESC, tblcommitteerole.Role ASC";
            $query = mysqli_query($SYSTEM_SETTINGS['Database'], $sql);
            $data = mysqli_fetch_all($query, MYSQLI_ASSOC);
            $roles = array(0 => '(Other)');
            foreach($data AS $item) {
                $roles[$item['CommitteeRoleID']] = ($item['IsChair'] ? '<b>'.$item['Role'].'</b>' : $item['Role']);
            }
            $fields[] = array('name' => 'CommitteeRoleID', 'caption' => 'Select Role', 'kind' => 'control', 'type' => 'advcombo', 'options' => $roles);
            $fields[] = array('name' => 'Role', 'kind' => 'control', 'type' => 'string');
            $fields[] = array('name' => 'StartDate', 'caption' => 'From', 'kind' => 'control', 'type' => 'date', 'utcsource' => TRUE, 'nullifempty' => TRUE, 'required' => TRUE, 'hint' => 'Enter the start date for this committee mandate');
            $fields[] = array('name' => 'EndDate', 'caption' => 'Until', 'kind' => 'control', 'type' => 'date', 'utcsource' => TRUE, 'nullifempty' => TRUE, 'hint' => 'Optional, enter the end date for this mandate or leave empty if not known or on-going');
            $fieldsets = array(array('fields' => $fields));
            $init = array(
                "$('#frmEditCommitteeItem\\\:CommitteeRoleID').on('change', function() {",
                "\tvar inputRole = $('#frmEditCommitteeItem\\\:Role');",
                "\tif( $('#frmEditCommitteeItem\\\:CommitteeRoleID').find(':selected').val() == 0 ) {",
                "\t\tinputRole.prop('disabled', false);",
                "\t\tinputRole.focus();",
                "\t} else {",
                "\t\tinputRole.val('');",
                "\t\tinputRole.prop('disabled', true);",
                "\t}",
                "});",
                "$('#frmEditCommitteeItem\\\:CommitteeRoleID').change();",
            );            
            $formitem = array(
                'id' => 'frmEditCommitteeItem', 'style' => 'standard', 
                'datasource' => $datasource, 'buttons' => array(),
                'fieldsets' => $fieldsets, 'borders' => FALSE,
                'oninitialise' => $init,
            );
            Form($formitem);
            switch($do) {
/*                case 'sendemailwflist':
                    $cbSuccess = "function(){ dlgDataSaved('Your message has been posted.'); RefreshDataTable(dt_workflow); }";
                    break;*/
                case 'editcommitteeitem':
                default:
                    $cbSuccess = reloadCall($_GET);
            }
            ModalBody(TRUE);
            ModalFooter("frmEditCommitteeItem", "/syscall.php?do=savecommitteeitem", $cbSuccess);                
            break;
        case 'endcommitteeterm':
            ModalHeader('End Term');
            ModalBody(FALSE);
            $fields = array(
                array('name' => 'PersonToCommitteeID', 'kind' => 'hidden'),
                array('name' => 'CommitteeID', 'kind' => 'hidden'),
                array('name' => 'PersonID', 'kind' => 'hidden'),
                array('name' => 'CommitteeName', 'caption' => 'Committee', 'kind' => 'static', 'type' => 'string', 'formatting' => '<b><info>'),
                array('name' => 'Fullname', 'caption' => 'Name', 'kind' => 'static', 'type' => 'string', 'formatting' => '<info>'),
                array('name' => 'RoleText', 'caption' => 'Role', 'kind' => 'static', 'type' => 'string', 'formatting' => '<info>'),
                array('name' => 'EndDate', 'caption' => 'End Date', 'kind' => 'control', 'type' => 'date', 'utcsource' => TRUE, 'nullifempty' => TRUE, 'required' => 'Enter the end date for this mandate'),
            );
            $datasource = GetCommitteeEntry($_GET['PersonToCommitteeID']);
            $fieldsets = array(array('fields' => $fields));
            $formitem = array(
                'id' => 'frmEndCommitteeTerm', 'style' => 'standard', 
                'datasource' => $datasource, 'buttons' => array(),
                'fieldsets' => $fieldsets, 'borders' => FALSE,
            );
            Form($formitem);
            switch($do) {
/*                case 'sendemailwflist':
                    $cbSuccess = "function(){ dlgDataSaved('Your message has been posted.'); RefreshDataTable(dt_workflow); }";
                    break;*/
                case 'endcommitteeterm':
                default:
                    $cbSuccess = reloadCall($_GET);
            }
            ModalBody(TRUE);
            ModalFooter("frmEndCommitteeTerm", "/syscall.php?do=endcommitteeterm", $cbSuccess);                
            break;
        case 'changecommitteerole':
            ModalHeader('Change Role');
            ModalBody(FALSE);
            $sql = 
            "SELECT tblcommitteerole.CommitteeRoleID, tblcommitteerole.Role, tblcommitteerole.IsChair
             FROM tblcommitteerole
             ORDER BY tblcommitteerole.IsChair DESC, tblcommitteerole.Role ASC";
            $query = mysqli_query($SYSTEM_SETTINGS['Database'], $sql);
            $data = mysqli_fetch_all($query, MYSQLI_ASSOC);
            $roles = array(0 => '(Other)');
            foreach($data AS $item) {
                $roles[$item['CommitteeRoleID']] = ($item['IsChair'] ? '<b>'.$item['Role'].'</b>' : $item['Role']);
            }
            $fields = array(
                array('name' => 'PersonToCommitteeID', 'kind' => 'hidden'),
                array('name' => 'CommitteeID', 'kind' => 'hidden'),
                array('name' => 'PersonID', 'kind' => 'hidden'),
                array('name' => 'CommitteeName', 'caption' => 'Committee', 'kind' => 'static', 'type' => 'string', 'formatting' => '<b><info>'),
                array('name' => 'Fullname', 'caption' => 'Name', 'kind' => 'static', 'type' => 'string', 'formatting' => '<info>'),
                array('name' => 'RoleText', 'caption' => 'Current Role', 'kind' => 'static', 'type' => 'string', 'formatting' => '<info>'),
                array('name' => 'CommitteeRoleID', 'caption' => 'New Role', 'kind' => 'control', 'type' => 'advcombo', 'options' => $roles),
                array('name' => 'Role', 'kind' => 'control', 'type' => 'string'),
                array('name' => 'StartDate', 'caption' => 'Change Date', 'kind' => 'control', 'type' => 'date', 'utcsource' => TRUE, 'nullifempty' => TRUE, 'required' => TRUE, 'hint' => 'Enter the start date for the new committee mandate'),
                array('name' => 'EndDate', 'caption' => 'Until', 'kind' => 'control', 'type' => 'date', 'utcsource' => TRUE, 'nullifempty' => TRUE, 'hint' => 'Optional, enter the end date for the new mandate or leave empty if not known or on-going'),
            );
            $datasource = GetCommitteeEntry($_GET['PersonToCommitteeID']);
            $datasource['StartDate'] = null;
            $datasource['EndDate'] = null;
            $fieldsets = array(array('fields' => $fields));
            $init = array(
                "$('#frmChangeCommitteeRole\\\:CommitteeRoleID').on('change', function() {",
                "\tvar inputRole = $('#frmChangeCommitteeRole\\\:Role');",
                "\tif( $('#frmChangeCommitteeRole\\\:CommitteeRoleID').find(':selected').val() == 0 ) {",
                "\t\tinputRole.prop('disabled', false);",
                "\t\tinputRole.focus();",
                "\t} else {",
                "\t\tinputRole.val('');",
                "\t\tinputRole.prop('disabled', true);",
                "\t}",
                "});",
                "$('#frmChangeCommitteeRole\\\:CommitteeRoleID').change();",
            );            
            $formitem = array(
                'id' => 'frmChangeCommitteeRole', 'style' => 'standard', 
                'datasource' => $datasource, 'buttons' => array(),
                'fieldsets' => $fieldsets, 'borders' => FALSE,
                'oninitialise' => $init,
            );
            Form($formitem);
            switch($do) {
/*                case 'sendemailwflist':
                    $cbSuccess = "function(){ dlgDataSaved('Your message has been posted.'); RefreshDataTable(dt_workflow); }";
                    break;*/
                case 'editcommitteeitem':
                default:
                    $cbSuccess = reloadCall($_GET);
            }
            ModalBody(TRUE);
            ModalFooter("frmChangeCommitteeRole", "/syscall.php?do=changecommitteerole", $cbSuccess);                
            break;
        case 'editgroupitem':
            ModalHeader('Group');
            ModalBody(FALSE);
            $sql = 
            "SELECT tblpersontopersongroup.PersonToPersonGroupID, tblpersontopersongroup.PersonID, tblpersontopersongroup.PersonGroupID, tblpersontopersongroup.Comment,
                    CONCAT_WS(' ', tblperson.Title, tblperson.Firstname, tblperson.Middlenames, tblperson.Lastname) AS `Fullname`,
                    tblpersongroup.PersonGroupID, tblpersongroup.GroupName
             FROM tblpersontopersongroup
             INNER JOIN tblperson ON tblperson.PersonID = tblpersontopersongroup.PersonID
             INNER JOIN tblpersongroup ON tblpersongroup.PersonGroupID = tblpersontopersongroup.PersonGroupID
             WHERE tblpersontopersongroup.PersonToPersonGroupID = ".intval($_GET['PersonToPersonGroupID']);
            $datasource = SingleRecord($SYSTEM_SETTINGS['Database'], $sql);
            $fieldsets = array();
            $fieldsets[] = array('fields' => array(
                array('name' => 'PersonToPersonGroupID', 'kind' => 'hidden'),
                array('name' => 'PersonID', 'kind' => 'hidden'),
                array('name' => 'PersonGroupID', 'kind' => 'hidden'),
                array('name' => 'GroupName', 'caption' => 'Group', 'kind' => 'static', 'type' => 'string', 'formatting' => '<b><info>'),
                array('name' => 'Fullname', 'caption' => 'Name', 'kind' => 'static', 'type' => 'string', 'formatting' => '<info>'),
                array('name' => 'Comment', 'caption' => 'Comment', 'kind' => 'control', 'type' => 'string'),
            ));
            $formitem = array(
                'id' => 'frmEditGroupItem', 'style' => 'standard', 
                'datasource' => $datasource, 'buttons' => array(),
                'fieldsets' => $fieldsets, 'borders' => FALSE
            );
            Form($formitem);
            ModalBody(TRUE);
            ModalFooter("frmEditGroupItem", "/syscall.php?do=savegroup", "function(){ RefreshDataTable(dt_groupmembers); }");                
            break;            
        case 'editgroup':
            ModalHeader('Group');
            ModalBody(FALSE);
            $sql = 
            "SELECT tblpersongroup.PersonGroupID, tblpersongroup.GroupName, tblpersongroup.Expires,
                    IF(tblpersontopersongroup.PersonToPersonGroupID IS NULL, 0, 1) AS `Selected`,
                    tblperson.PersonID
             FROM tblpersongroup
             LEFT JOIN tblpersongrouptomsgrade ON tblpersongrouptomsgrade.PersonGroupID = tblpersongroup.PersonGroupID
             LEFT JOIN tblperson ON tblperson.PersonID = ".intval($_GET['PersonID'])."
             LEFT JOIN tblpersonms ON (tblpersonms.PersonID = tblperson.PersonID) AND (tblpersonms.BeginDate <= UTC_TIMESTAMP()) AND ((tblpersonms.EndDate IS NULL) OR (tblpersonms.EndDate >= UTC_TIMESTAMP()))
             LEFT JOIN tblmsstatus ON (tblmsstatus.MSStatusID = tblpersonms.MSStatusID)
             LEFT JOIN tblpersontopersongroup ON (tblpersontopersongroup.PersonID = tblperson.PersonID) AND (tblpersontopersongroup.PersonGroupID = tblpersongroup.PersonGroupID)
             WHERE ((tblpersongroup.Expires IS NULL) OR (tblpersongroup.Expires > UTC_TIMESTAMP()))
                             AND
                   ((tblpersontopersongroup.PersonToPersonGroupID IS NULL)".(!empty($_GET['PersonToPersonGroupID']) ? " OR (tblpersontopersongroup.PersonToPersonGroupID = ".intval($_GET['PersonToPersonGroupID']).")": "").")   
			                 AND
	               ((tblpersongrouptomsgrade.MSGradeID IS NULL) OR ((tblpersongrouptomsgrade.MSGradeID = tblpersonms.MSGradeID) AND FIND_IN_SET('ismember', tblmsstatus.MSStatusFlags)))
             ORDER BY tblpersongroup.GroupName";
            $query = mysqli_query($SYSTEM_SETTINGS['Database'], $sql);
            $options = array();
            $selected = null;
            $data = mysqli_fetch_all($query, MYSQLI_ASSOC);
            foreach($data AS $item) {
                $options[$item['PersonGroupID']] = $item['GroupName'];
                if($item['Selected']) {
                    $selected = $item['PersonGroupID'];
                }
            }
            $fieldsets = array();
            $fieldsets[] = array('fields' => array(
                array('name' => 'PersonToPersonGroupID', 'kind' => 'hidden'),
                array('name' => 'PersonID', 'kind' => 'hidden'),
                array('name' => 'PersonGroupID', 'caption' => 'Group', 'kind' => 'control', 'type' => 'advcombo', 'options' => $options, 'selected' => $selected, 'required' => 'Select a group'),
                array('name' => 'Comment', 'caption' => 'Comment', 'kind' => 'control', 'type' => 'string'),
            ));
            $formitem = array(
                'id' => 'frmEditGroup', 'style' => 'standard', 
                'datasource' => $_GET, 'buttons' => array(),
                'fieldsets' => $fieldsets, 'borders' => FALSE
            );
            Form($formitem);
            ModalBody(TRUE);
            ModalFooter("frmEditGroup", "/syscall.php?do=savegroup", reloadCall($_GET));                
            break;
        case 'editphone':
            ModalHeader('Phone Number');
            ModalBody(FALSE);
            $phonetypes = new crmPhoneTypes($SYSTEM_SETTINGS['Database']);
            $fieldsets = array();
            $fields = array();
            if(isset($_GET['PersonID'])) {
                $fields[] = array('name' => 'PersonToPhoneID', 'kind' => 'hidden');
                $fields[] = array('name' => 'PersonID', 'kind' => 'hidden');
            } elseif(isset($_GET['OrganisationID'])) {
                $fields[] = array('name' => 'OrganisationToPhoneID', 'kind' => 'hidden');
                $fields[] = array('name' => 'OrganisationID', 'kind' => 'hidden');
            }
            $fields = array_merge($fields, array(
                array('name' => 'PhoneTypeID', 'caption' => 'Type', 'kind' => 'control', 'type' => 'advcombo', 'allowempty' => FALSE, 'options' => $phonetypes->GetTypes(), 'required' => TRUE),
                array('name' => 'PhoneNo', 'caption' => 'Phone Number', 'kind' => 'control', 'type' => 'phone', 'required' => TRUE),
            ));
            if(isset($_GET['OrganisationID'])) {
                $fields[] = array('name' => 'Description', 'caption' => 'Description', 'kind' => 'control', 'type' => 'string', 'required' => TRUE);
            }
            $fieldsets[] = array('fields' => $fields);
            $formitem = array(
                'id' => 'frmEditPhone', 'style' => 'standard', 
                'datasource' => $_GET, 'buttons' => array(),
                'fieldsets' => $fieldsets, 'borders' => FALSE
            );
            Form($formitem);
            ModalBody(TRUE);
            ModalFooter("frmEditPhone", "/syscall.php?do=savephone", reloadCall($_GET));                
            break;
        case 'sendemailmsapplist':
        case 'sendemailwflist':
        case 'sendemailwfrecord':
        case 'sendemailrecord':
        case 'sendemail':
            ModalHeader('Send Email');
            ModalBody(FALSE);
            $fieldsets = array();
            $fields = array();
            $RECORD = GetParentRecord();
            if(!empty($RECORD)) {
                $datasource = $RECORD->GetRecord();
                $emails = $RECORD->GetRecord('email', TRUE);
                $from = GetFromAddresses();
                if(!empty($emails)) {
                    if (is_a($RECORD, 'crmOrganisation')) {
                        $fields[] = array('name' => 'OrganisationID', 'kind' => 'hidden');
                    } elseif(is_a($RECORD, 'crmPerson')) {
                        $fields[] = array('name' => 'PersonID', 'kind' => 'hidden');
                    }
                    if(count($from['addresses']) > 0) {
                        if(count($from['addresses']) == 1) {
                            $fields[] = array('name' => 'From', 'caption' => 'From:', 'kind' => 'static');
                            $datasource['From'] = FirstArrayItem($from['addresses']);
                        } else {
                            $fields[] = array('name' => 'From', 'caption' => 'From', 'kind' => 'control', 'type' => 'advlist', 'options' => $from['addresses'], 'selected' => $from['default'], 'required' => TRUE);
                        }
                    }
                    $datasource['Fullname'] = Fullname($datasource);
                    $fields[] = array('name' => 'Fullname', 'caption' => 'Email to:', 'kind' => 'static', 'formatting' => '<info><b>');
                    $datasource['To'] = (isset($_GET['Email']) && IsValidEmailAddress($_GET['Email']) ? $_GET['Email'] : FirstArrayItem($emails, 'Email')); 
                    if(count($emails) == 1) {
                        $fields[] = array('name' => 'To', 'kind' => 'hidden');
                    } else {
                        $fields[] = array('name' => 'To', 'caption' => 'To', 'kind' => 'control', 'type' => 'list', 'options' => GetAllNamedValues($emails, 'Email'), 'required' => TRUE);
                    }
                    $fields[] = array('name' => 'CC', 'caption' => 'CC', 'kind' => 'control', 'type' => 'string');
                    $fields[] = array('name' => 'BCC', 'caption' => 'BCC', 'kind' => 'control', 'type' => 'string');
                    $fields[] = array('name' => 'Subject', 'caption' => 'Subject', 'kind' => 'control', 'type' => 'string', 'required' => TRUE);
                    $fields[] = array('name' => 'Body', 'caption' => 'Message', 'kind' => 'control', 'type' => 'fmttext', 'required' => TRUE, 'rows' => 8);
                    $fieldsets[] = array('caption' => 'Email Message', 'fields' => $fields);
                    $fieldsets[] = NoteFieldSet(array());
                    $formitem = array(
                        'id' => 'frmSendEmail', 'style' => 'standard', 
                        'datasource' => $datasource, 'buttons' => array(),
                        'fieldsets' => $fieldsets, 'borders' => FALSE
                    );
                    Form($formitem);
                } else {
                    SimpleAlertBox('error', 'No email address.');
                }
            } else {
                SimpleAlertBox('error', 'No parent record found.');
            }
            switch($do) {
                case 'sendemailwfrecord':
                    $cbSuccess = "function(){ dlgDataSaved('Your message has been posted.'); RefreshDataTable(dt_record_workflow); InvalidateTab( 'tab-notes' ); }";
                    break;
                case 'sendemailmsapplist':
                    $cbSuccess = "function(){ dlgDataSaved('Your message has been posted.'); RefreshDataTable(dt_applications); }";
                    break;
                case 'sendemailwflist':
                    $cbSuccess = "function(){ dlgDataSaved('Your message has been posted.'); RefreshDataTable(dt_workflow); }";
                    break;
                case 'sendemailrecord':
                    $cbSuccess = "function(){ dlgDataSaved('Your message has been posted.'); InvalidateTab( 'tab-notes' ); }";
                    break;
                default:
                    $cbSuccess = "function(){ dlgDataSaved('Your message has been posted.'); }";
            }
            ModalBody(TRUE);
            ModalFooter("frmSendEmail", "/syscall.php?do=sendemail", $cbSuccess, "Send");                
            break;
        case 'insertplaceofwork':
        case 'editplaceofwork':
            ModalHeader("Place of Employment");
            ModalBody(FALSE);
            $fieldsets = array();
            $fields = array();
            if($do == 'insertplaceofwork') {
                $fields[] = array('name' => 'PlaceOfWorkParentID', 'kind' => 'hidden');
                $_GET['PlaceOfWorkParentID'] = $_GET['PlaceOfWorkID'];
                $_GET['PlaceOfWorkDesc'] = '';
            } else {
                $fields[] = array('name' => 'PlaceOfWorkID', 'kind' => 'hidden');
            }
            $fields[] = array('name' => 'PlaceOfWorkDesc', 'caption' => 'Place of Employment', 'kind' => 'control', 'type' => 'string', 'required' => TRUE);
            $fieldsets[] = array('fields' => $fields);
            $formitem = array(
                'id' => 'frmEditPlaceOfWork', 'style' => "vertical", 
                'datasource' => $_GET, 'buttons' => array(),
                'fieldsets' => $fieldsets, 'borders' => FALSE
            );
            Form($formitem);
            ModalBody(TRUE);
            ModalFooter("frmEditPlaceOfWork", "/syscall.php?do=saveplaceofwork", reloadCall($_GET));                
            break;
        case 'editstdrecord':
            assert(strlen($_SERVER['REQUEST_URI']) < 2000);
            assert(isset($_GET['_fields']) && isset($_GET['_tablename']));
            ModalHeader((!empty($_GET['_title']) ? $_GET['_title'] : "Edit"));
            ModalBody(FALSE);
            $fieldsets = array();
            $fields = array(
                array('name' => '_tablename', 'kind' => 'hidden'),
                array('name' => '_title', 'kind' => 'hidden'),
            );
            parse_str($_GET['_fields'], $fieldmappings);
            $viscount = 0;
            foreach($fieldmappings AS $fieldname => $settings) {
                if(empty($settings)) {
                    $fields[] = array('name' => $fieldname, 'kind' => 'hidden');
                } elseif(is_array($settings)) {
                    $fields[] = array('name' => $fieldname, 'caption' => $settings['caption'], 'kind' => 'control', 'type' => $settings['type'], 'required' => !empty($settings['required']));
                    $viscount++;
                }
            }
            $fieldsets[] = array('fields' => $fields);
            $formitem = array(
                'id' => 'frmEditStdRecord', 'style' => ($viscount == 1 ? "vertical" : "standard"), 
                'datasource' => $_GET, 'buttons' => array(),
                'fieldsets' => $fieldsets, 'borders' => FALSE
            );
            Form($formitem);
            ModalBody(TRUE);
            ModalFooter("frmEditStdRecord", "/syscall.php?do=savestdrecord", reloadCall($_GET));                
            break;
        case 'editgrade':
            ModalHeader($SYSTEM_SETTINGS['Membership']['GradeCaption']);
            ModalBody(FALSE);
            $components = new crmApplicationComponents;
            $fieldsets = array();
            $fields = array(
                array('name' => 'MSGradeID', 'kind' => 'hidden'),
                array('name' => 'GradeCaption', 'caption' => $SYSTEM_SETTINGS['Membership']['GradeCaption'], 'kind' => 'control', 'type' => 'string', 'required' => TRUE),
                array('name' => 'Available', 'caption' => 'Available', 'kind' => 'control', 'colour' => 'warning', 'type' => 'switch'),
                array('name' => 'ApplyOnline', 'caption' => 'Apply online', 'kind' => 'control', 'colour' => 'info', 'type' => 'switch'),
                array('name' => 'ApplComponents[]', 'caption' => 'Application', 'kind' => 'control', 'type' => 'multi', 'options' => $components->Components, 'required' => TRUE, 'allowempty' => FALSE),
                array('name' => 'AutoElect', 'caption' => 'Auto-election', 'kind' => 'control', 'colour' => 'info', 'type' => 'switch'),
                array('name' => 'IsRetired', 'caption' => 'Retired '.$SYSTEM_SETTINGS['Membership']['GradeCaption'], 'kind' => 'control', 'colour' => 'warning', 'type' => 'switch'),
                array('name' => 'GraduationFrom', 'caption' => 'Graduation From', 'kind' => 'control', 'type' => 'string', 'size' => 6, 'Minimum offset from graduation date to allow this '.$SYSTEM_SETTINGS['Membership']['GradeCaption']),
                array('name' => 'GraduationUntil', 'caption' => 'Graduation Until', 'kind' => 'control', 'type' => 'string', 'size' => 6, 'Maximum time from graduation date to allow this '.$SYSTEM_SETTINGS['Membership']['GradeCaption']),
            );
            if(empty($_GET['MSGradeID'])) {
                $sql = "SELECT MAX(DisplayOrder) FROM tblmsgrade";
                $max = SingleValue($SYSTEM_SETTINGS['Database'], $sql);
                $_GET['DisplayOrder'] = $max+1;
                $fields[] = array('name' => 'DisplayOrder', 'kind' => 'hidden');
            }
            $fieldsets[] = array('fields' => $fields);
            $formitem = array(
                'id' => 'frmEditGrade', 'style' => 'standard', 
                'datasource' => $_GET, 'buttons' => array(),
                'fieldsets' => $fieldsets, 'borders' => FALSE
            );
            Form($formitem);
            ModalBody(TRUE);
            ModalFooter("frmEditGrade", "/syscall.php?do=savegrade", reloadCall($_GET));                
            break;
        case 'editstage':
            ModalHeader('Application Stage');
            ModalBody(FALSE);
            $categories = new crmWorkflowCategories($SYSTEM_SETTINGS['Database']);
            $colours = new crmColours();
            $fieldsets = array();
            $fields = array(
                array('name' => 'MSGradeID', 'kind' => 'hidden'),
                array('name' => 'CategorySelector', 'caption' => 'Category', 'kind' => 'control', 'type' => 'combo', 'options' => $categories->GetSelectors(TRUE)),
                array('name' => 'StageOrder', 'caption' => 'Stage Order', 'kind' => 'control', 'type' => 'integer', 'required' => TRUE, 'size' => 4),
                array('name' => 'StageName', 'caption' => 'Stage Name', 'kind' => 'control', 'type' => 'string', 'required' => TRUE, 'size' => 4),
                array('name' => 'StageColour', 'caption' => 'Colour', 'kind' => 'control', 'type' => 'list', 'options' => $colours->Colours),
                array('name' => 'SubmissionStage', 'caption' => 'Status', 'kind' => 'control', 'type' => 'combo', 'options' => array(-1 => 'Not yet submitted', 0 => 'Submit', 1 => 'Post submission'), 'required' => TRUE),
//                array('name' => 'SubmissionStage', 'caption' => 'Submission Stage', 'kind' => 'control', 'type' => 'integer', 'required' => TRUE, 'size' => 4),
                array('name' => 'PaymentRequired', 'caption' => 'Payment Required', 'kind' => 'control', 'colour' => 'info', 'type' => 'switch'),
                array('name' => 'IsCompletionStage', 'caption' => 'Completion Stage', 'kind' => 'control', 'colour' => 'info', 'type' => 'switch'),
                array('name' => 'IsElectionStage', 'caption' => 'Election Stage', 'kind' => 'control', 'colour' => 'primary', 'type' => 'switch'),
            );
            $fieldsets[] = array('fields' => $fields);
            $formitem = array(
                'id' => 'frmEditStage', 'style' => 'standard', 
                'datasource' => $_GET, 'buttons' => array(),
                'fieldsets' => $fieldsets, 'borders' => FALSE
            );
            Form($formitem);
            ModalBody(TRUE);
            ModalFooter("frmEditStage", "/syscall.php?do=savestage", reloadCall($_GET));                
            break;
        case 'editonline':
            ModalHeader('Web and Social');
            ModalBody(FALSE);
            $onlinecategories = new crmOnlineCategories($SYSTEM_SETTINGS['Database']);
            $fieldsets = array();
            $fields = array();
            if(isset($_GET['PersonID'])) {
                $fields[] = array('name' => 'PersonToOnlineID', 'kind' => 'hidden');
                $fields[] = array('name' => 'PersonID', 'kind' => 'hidden');
            } elseif(isset($_GET['OrganisationID'])) {
                $fields[] = array('name' => 'OrganisationToOnlineID', 'kind' => 'hidden');
                $fields[] = array('name' => 'OrganisationID', 'kind' => 'hidden');
            }
            $fields = array_merge($fields, array(
                array('name' => 'CategoryName', 'kind' => 'hidden'),
                array('name' => 'OnlineID', 'caption' => 'Type', 'kind' => 'control', 'type' => 'advcombo', 'allowempty' => FALSE, 'options' => $onlinecategories->GetTypes(), 'required' => TRUE),
                array('name' => 'URL', 'caption' => 'URL', 'kind' => 'control', 'type' => 'url', 'required' => 'Enter a valid URL'),
            ));
            $fieldsets[] = array('fields' => $fields);
/*            $fieldsets = array();
            $fieldsets[] = array('fields' => array(
                array('name' => 'PersonToOnlineID', 'kind' => 'hidden'),
                array('name' => 'PersonID', 'kind' => 'hidden'),
                array('name' => 'CategoryName', 'kind' => 'hidden'),
                array('name' => 'OnlineID', 'caption' => 'Type', 'kind' => 'control', 'type' => 'advcombo', 'allowempty' => FALSE, 'options' => $onlinecategories->GetTypes(), 'required' => TRUE),
                array('name' => 'URL', 'caption' => 'URL', 'kind' => 'control', 'type' => 'url', 'required' => TRUE),
            ));*/
            $formitem = array(
                'id' => 'frmEditOnline', 'style' => 'standard', 
                'datasource' => $_GET, 'buttons' => array(),
                'fieldsets' => $fieldsets, 'borders' => FALSE
            );
            Form($formitem);
            ModalBody(TRUE);
            ModalFooter("frmEditOnline", "/syscall.php?do=saveonline", reloadCall($_GET));                
            break;
        case 'editaddress':
            ModalHeader('Postal Address');
            ModalBody(FALSE);
            $types = new crmAddressTypes;
            $countries = new crmCountries($SYSTEM_SETTINGS['Database']);
            $fieldsets = array();
            $fields = array();
            if(isset($_GET['PersonID'])) {
                $fields[] = array('name' => 'AddressToPersonID', 'kind' => 'hidden');
                $fields[] = array('name' => 'PersonID', 'kind' => 'hidden');
            } elseif(isset($_GET['OrganisationID'])) {
                $fields[] = array('name' => 'AddressToOrganisationID', 'kind' => 'hidden');
                $fields[] = array('name' => 'OrganisationID', 'kind' => 'hidden');
            }
            $fields[] = array('name' => 'AddressID', 'kind' => 'hidden');
            $fields = array_merge($fields, array(
                array('name' => 'AddressType', 'caption' => 'Type', 'kind' => 'control', 'type' => 'list', 'options' => $types->Types, 'size' => 4),
                array('name' => 'Lines', 'caption' => 'Address Lines', 'kind' => 'control', 'type' => 'memo', 'required' => TRUE, 'rows' => 4),
                array('name' => 'Postcode', 'caption' => 'Postcode', 'kind' => 'control', 'type' => 'string', 'required' => TRUE),
                array('name' => 'Town', 'caption' => 'Town', 'kind' => 'control', 'type' => 'string', 'required' => TRUE),
                array('name' => 'County', 'caption' => 'County', 'kind' => 'control', 'type' => 'string'),
                array('name' => 'Region', 'caption' => 'Region', 'kind' => 'control', 'type' => 'string'),
                array('name' => 'ISO3166', 'caption' => 'Country', 'kind' => 'control', 'type' => 'advcombo', 'required' => TRUE, 'allowempty' => FALSE, 'options' => $countries->Countries),
            ));
            $fieldsets[] = array('fields' => $fields);
            if(!isset($_GET['ISO3166'])) {
                $_GET['ISO3166'] = $countries->DefCountry;
            }
            $formitem = array(
                'id' => 'frmEditAddress', 'style' => 'standard', 
                'datasource' => $_GET, 'buttons' => array(),
                'fieldsets' => $fieldsets, 'borders' => FALSE
            );
            Form($formitem);         
            ModalBody(TRUE);
            ModalFooter("frmEditAddress", "/syscall.php?do=saveaddress", reloadCall($_GET));                
            break;
        case 'labeladdress':
            ModalHeader('Address Label');
            ModalBody(FALSE);
            $PERSON = new crmPerson('eventsPerson', $SYSTEM_SETTINGS['Database'], $_GET['PersonID'], $SYSTEM_SETTINGS["Membership"]);
            $lines = explode("\n", AddressToMemo(
                $_GET, 
                Fullname(
                    $PERSON->GetRecord('personal'), 
                    array('middlenames' => FALSE, 'title' => TRUE, 'postnominals' => TRUE, 'html' => FALSE, 'tooltip' => FALSE)
                ),
                array('CountryUpperCase' => TRUE)
            ));
            Para(array('h4' => TRUE), 6);
            OutputLines($lines, null, FALSE, 7);
            Para(null, 6);
            ModalBody(TRUE);
            ModalFooter(null);
            break;
        case 'editpublication':
            ModalHeader('Publication');
            ModalBody(FALSE);
            $publication = new crmPublication($SYSTEM_SETTINGS['Database'], (!empty($_GET['PublicationID']) ? $_GET['PublicationID'] : null)); 
            $fields = array();
            $fields[] = array('name' => 'PublicationID', 'kind' => 'hidden');
            $fields[] = array('name' => 'Title', 'caption' => 'Title', 'kind' => 'control', 'type' => 'string', 'required' => TRUE);
            if($publication->Found) {
                $fields[] = array('name' => 'SubsCount', 'caption' => 'Subscribers', 'kind' => 'static', 'type' => 'integer', 'asstringfunction' => 'FmtNumber', 'formatting' => '<b><info>');
            }
            $fields[] = array('name' => 'Description', 'caption' => 'Description','kind' => 'control', 'type' => 'fmttext', 'rows' => 5);
            $fields[] = array('name' => 'PublicationType', 'caption' => 'Type', 'kind' => 'control', 'type' => 'list', 'options' => $publication->PublicationTypes->Types, 'size' => 4);
            $fields[] = array('name' => 'PublicationScope', 'caption' => 'Scope', 'kind' => 'control', 'type' => 'list', 'options' => $publication->PublicationScopes->Scopes, 'size' => 4);
            $fields[] = array('name' => 'Flags[]', 'caption' => 'Settings', 'kind' => 'control', 'type' => 'multi', 'options' => $publication->PublicationFlags->Items);
            $fieldsets[] = array('fields' => $fields);                
            $formitem = array(
                'id' => 'frmEditPublication', 'style' => 'standard', 
                'datasource' => $publication->Publication, 'buttons' => array(),
                'fieldsets' => $fieldsets, 'borders' => FALSE
            );
            Form($formitem);
            ModalBody(TRUE);        
            ModalFooter("frmEditPublication", "/syscall.php?do=savepublication", "function(){ RefreshDataTable(dt_publications); }");
            break;
        case 'committeesettings':
        case 'editcommittee':
            ModalHeader('Committee');
            ModalBody(FALSE);
            $committee = new crmCommittee ($SYSTEM_SETTINGS['Database'], (!empty($_GET['CommitteeID']) ? $_GET['CommitteeID'] : null));
            $fields = array();
            $fields[] = array('name' => 'CommitteeID', 'kind' => 'hidden');
            $fields[] = array('name' => 'CommitteeName', 'caption' => 'Name', 'kind' => 'control', 'type' => 'string', 'required' => TRUE);
            $fields[] = array('name' => 'Description', 'caption' => 'Description', 'kind' => 'control', 'type' => 'memo', 'rows' => 4);
            if($committee->Found) {
                $fields[] = array('name' => 'MemberCount', 'caption' => 'Members', 'kind' => 'static', 'type' => 'integer', 'asstringfunction' => 'FmtNumber', 'formatting' => '<b><info>');
            }
            $fieldsets[] = array('fields' => $fields);                
            $formitem = array(
                'id' => 'frmEditCommittee', 'style' => 'standard', 
                'datasource' => $committee->Settings, 'buttons' => array(),
                'fieldsets' => $fieldsets, 'borders' => FALSE
            );
            Form($formitem);
            ModalBody(TRUE);
            switch($do) {
                case 'editcommittee':
                    $cbSuccess = "function(){ RefreshDataTable(dt_committees); }";
                    break;
                case 'committeesettings':
                    $cbSuccess = "function(){ window.location.reload(true); }";
                    break;
            }
            ModalFooter("frmEditCommittee", "/syscall.php?do=savecommittee", $cbSuccess);
            break;
        case 'selectcommitteedate':
            ModalHeader('Change Date');
            ModalBody(FALSE);
            $committee = new crmCommittee ($SYSTEM_SETTINGS['Database'], $_GET['CommitteeID']);
            if($committee->Found) {
                $fields = array();
                $fields[] = array('name' => 'CommitteeID', 'kind' => 'hidden');
                $fields[] = array('name' => 'CommitteeName', 'caption' => 'Committee', 'kind' => 'static', 'type' => 'string', 'formatting' => '<b><info>');
//                $fields[] = array('name' => 'MemberCount', 'caption' => 'Members', 'kind' => 'static', 'type' => 'integer', 'asstringfunction' => 'FmtNumber', 'formatting' => '<b><info>');
                $fields[] = array('name' => 'ForDate', 'caption' => 'Select Date', 'kind' => 'control', 'type' => 'date', 'required' => TRUE, 'showage' => TRUE);
                $fieldsets[] = array('fields' => $fields);                
                $formitem = array(
                    'id' => 'frmSelectCommitteeDate', 'style' => 'standard', 
                    'datasource' => $committee->Settings, 'buttons' => array(),
                    'fieldsets' => $fieldsets, 'borders' => FALSE
                );
                Form($formitem);
            }
            ModalBody(TRUE);
            ModalFooter("frmSelectCommitteeDate", "/syscall.php?do=changecommitteedate", "function( frmElement, response ) { LoadContent('divCommitteePeople', '/load.php?do=table_committeemembers', { spinner: true, urlparams: $.parseJSON(response.urlparams) }); }");
            break;
        case 'editpersongroup':
            ModalHeader('Group');
            ModalBody(FALSE);
            $group = new crmPersonGroup($SYSTEM_SETTINGS['Database'], (!empty($_GET['PersonGroupID']) ? $_GET['PersonGroupID'] : null));
            $fields = array();
            $fields[] = array('name' => 'PersonGroupID', 'kind' => 'hidden');
            $fields[] = array('name' => 'GroupName', 'caption' => 'Name', 'kind' => 'control', 'type' => 'string', 'required' => TRUE);
            $fields[] = array('name' => 'Expires', 'caption' => 'Expiry Date', 'kind' => 'control', 'type' => 'date', 'hint' => 'If set, this group will automatically be removed after this date');
            if($group->Found) {
                $fields[] = array('name' => 'MemberCount', 'caption' => 'Members', 'kind' => 'static', 'type' => 'integer', 'asstringfunction' => 'FmtNumber', 'formatting' => '<b><info>');
            }
            $fieldsets[] = array('fields' => $fields);                
            $formitem = array(
                'id' => 'frmEditPersonGroup', 'style' => 'standard', 
                'datasource' => $group->Group, 'buttons' => array(),
                'fieldsets' => $fieldsets, 'borders' => FALSE
            );
            Form($formitem);
            ModalBody(TRUE);
            ModalFooter("frmEditPersonGroup", "/syscall.php?do=savepersongroup", "function(){ RefreshDataTable(dt_groups); }");
            break;
        case 'editgrant':
            ModalHeader('Grant');
            ModalBody(FALSE);
            $grant = new crmGrant($SYSTEM_SETTINGS['Database'], (!empty($_GET['GrantID']) ? $_GET['GrantID'] : null));
            $fields = array();
            $fields[] = array('name' => 'GrantID', 'kind' => 'hidden');
            $fields[] = array('name' => 'Title', 'caption' => 'Title', 'kind' => 'control', 'type' => 'string', 'required' => TRUE);
            $fields[] = array('name' => 'Description', 'caption' => 'Description', 'kind' => 'control', 'type' => 'memo', 'rows' => 3);
            if($grant->Found) {
                $fields[] = array('name' => 'AwardCount', 'caption' => 'Awards', 'kind' => 'static', 'type' => 'integer', 'asstringfunction' => 'FmtNumber', 'formatting' => '<b><info>');
            }
            $fieldsets[] = array('fields' => $fields);                
            $formitem = array(
                'id' => 'frmEditGrant', 'style' => 'standard', 
                'datasource' => $grant->Grant, 'buttons' => array(),
                'fieldsets' => $fieldsets, 'borders' => FALSE
            );
            Form($formitem);
            ModalBody(TRUE);        
            ModalFooter("frmEditGrant", "/syscall.php?do=savegrant", "function(){ RefreshDataTable(dt_grants); }");
            break;
        case 'editpubsubscription':
            ModalHeader('Publication');
            ModalBody(FALSE);
            $RECORD = GetParentRecord();
            if(!empty($RECORD)) {
                $datasource = $_GET;
                $fieldsets = array();
                if (is_a($RECORD, 'crmOrganisation')) {
                    $fields[] = array('name' => 'OrganisationID', 'kind' => 'hidden');
                } elseif(is_a($RECORD, 'crmPerson')) {
                    $fields[] = array('name' => 'PersonID', 'kind' => 'hidden');
                }
                if(isset($_GET['SubscriptionID'])) {
                    $fields[] = array('name' => 'SubscriptionID', 'kind' => 'hidden');
                    $datasource = $RECORD->GetSubscriptionByID($_GET['SubscriptionID']);
                    if(!empty($datasource['AutoManaged'])) {
                        SimpleAlertBox('warning', 'This is an automatically managed subscription. If you override this subscription it will no longer be automatically managed.');
                    }
                }
                if(!empty($_GET['PublicationID'])) {
                    $fields[] = array('name' => 'PublicationID', 'kind' => 'hidden');
                } else {
                    $sql = 
                    "SELECT tblpublication.PublicationID, 
                            CONCAT(tblpublication.Title, ' [', UCASE(LEFT(tblpublication.PublicationScope, 1)), SUBSTRING(tblpublication.PublicationScope, 2), ', ', UCASE(LEFT(tblpublication.PublicationType, 1)), SUBSTRING(tblpublication.PublicationType, 2), ']') AS `Title`,
                            tblpublication.PublicationType, tblpublication.PublicationScope, tblpublication.Description, tblpublication.Flags
                     FROM tblpublication
                     ORDER BY tblpublication.Title";
                    $query = mysqli_query($SYSTEM_SETTINGS['Database'], $sql);
                    $publications = array();
                    while($row = mysqli_fetch_assoc($query)) {
                        $publications[$row['PublicationID']] = array(
                            'caption' => $row['Title'],
                            'tooltip' => $row['Description'],
                            'data-PublicationType' => $row['PublicationType'],
                        );
                    }
                    $fields[] = array('name' => 'PublicationID', 'caption' => 'Publication', 'kind' => 'control', 'type' => 'combo', 'options' => $publications, 'required' => TRUE);
                    $datasource['Qty'] = 1;
                }
                $fields = array_merge($fields, array(
                    array('name' => 'CustomerReference', 'caption' => 'Customer Ref.', 'kind' => 'control', 'size' => 6, 'type' => 'string'),
                    array('name' => 'StartDate', 'caption' => 'From', 'kind' => 'control', 'type' => 'date', 'size' => 6, 'hint' => 'Optional, indicates the start date for this subscription'),
                    array('name' => 'EndDate', 'caption' => 'From', 'kind' => 'control', 'type' => 'date', 'size' => 6, 'hint' => 'If set, the subscription will end at this date'),
                    array('name' => 'Qty', 'caption' => 'Quantity', 'kind' => 'control', 'type' => 'integer', 'size' => 6, 'min' => 1),
                    array('name' => 'Complimentary', 'tooltip' => 'Complimentary subscription free of charge', 'type' => 'switch', 'kind' => 'control', 'colour' => 'info', 'hint' => 'Enable the switch to make this subscription complimentary. It will be free of charge and not subject to availability rules.'),
                ));
                if((empty($datasource['PublicationType'])) || ($datasource['PublicationType'] == 'paper')) {
                    $fields[] = array('name' => 'DiffDeliveryAddress', 'id' => 'swDeliveryAddress', 'script' => "$('#fsDeliveryAddress').toggle();", 'tooltip' => 'Send this publication to an address other than the contact address', 'type' => 'switch', 'kind' => 'control', 'colour' => 'info', 'hint' => 'Use different delivery address.'); 
                }
                $fieldsets[] = array('fields' => $fields);
                if((empty($datasource['PublicationType'])) || ($datasource['PublicationType'] == 'paper')) {
                    $countries = new crmCountries($SYSTEM_SETTINGS['Database']);
                    if(empty($datasource['ISO3166'])) {
                        $datasource['ISO3166'] = $countries->DefCountry;
                    }
                    $fields = array(
                        array('name' => 'AddressToPublicationID', 'kind' => 'hidden'),
                        array('name' => 'AddressID', 'kind' => 'hidden'),
                        array('name' => 'Lines', 'caption' => 'Address Lines', 'kind' => 'control', 'type' => 'memo', 'required' => TRUE, 'rows' => 4),
                        array('name' => 'Postcode', 'caption' => 'Postcode', 'kind' => 'control', 'type' => 'string', 'required' => TRUE),
                        array('name' => 'Town', 'caption' => 'Town', 'kind' => 'control', 'type' => 'string', 'required' => TRUE),
                        array('name' => 'County', 'caption' => 'County', 'kind' => 'control', 'type' => 'string'),
                        array('name' => 'Region', 'caption' => 'Region', 'kind' => 'control', 'type' => 'string'),
                        array('name' => 'ISO3166', 'caption' => 'Country', 'kind' => 'control', 'type' => 'advcombo', 'required' => TRUE, 'allowempty' => FALSE, 'options' => $countries->Countries),
                    );
                    $fieldsets[] = array('id' => 'fsDeliveryAddress', 'fields' => $fields);
                }
                //print_r($datasource);
                $init = array("if( !$('#frmEditPubSubscription\\\:DiffDeliveryAddress').is(':checked') ) {", "\t$('#fsDeliveryAddress').hide();", "}");
                if(empty($_GET['PublicationID'])) {
                    $init = array_merge($init, array(
                        "$('#frmEditPubSubscription\\\:PublicationID').on('change', function() {",
                        "\tif( $(this).find(':selected').data('publicationtype') == 'paper' ) {",
                        "\t\t$('#frmEditPubSubscription\\\:DiffDeliveryAddress').closest('div.form-group').show();",
                        "\t} else {",
                        "\t\t$('#frmEditPubSubscription\\\:DiffDeliveryAddress').removeAttr('checked');",
                        "\t\t$('#frmEditPubSubscription\\\:DiffDeliveryAddress').closest('div.form-group').hide();",
                        "\t\t$('#fsDeliveryAddress').hide();",
                        "\t}",
                        "});",
                        "$('#frmEditPubSubscription\\\:PublicationID').change();"
                    ));
                }
                $formitem = array(
                    'id' => 'frmEditPubSubscription', 'style' => 'standard', 
                    'datasource' => $datasource, 'buttons' => array(),
                    'fieldsets' => $fieldsets, 'borders' => FALSE,
                    'oninitialise' => $init,
                );
                Form($formitem);
                //jsFormValidation('frmEditPubSubscription');            
            } else {
                SimpleAlertBox('error', 'No parent record found.');
            }
            ModalBody(TRUE);        
            ModalFooter("frmEditPubSubscription", "/syscall.php?do=savepubsubscription", "function(){ RefreshDataTable(dt_record_publication); $('#tab-contact').data('loaded', false); }");
            break;
        case 'editnote':
            ModalHeader('Note');
            ModalBody(FALSE);
            $RECORD = GetParentRecord();
            if(!empty($RECORD)) {
                //$categories = new crmWorkflowCategories($SYSTEM_SETTINGS['Database']);
                $datasource = $_GET;
                if(isset($_GET['NoteID'])) {
                    $noteObj = new crmNote($SYSTEM_SETTINGS['Database'], intval($_GET['NoteID']));
                    $note = $noteObj->Note;
                    if($noteObj->Found) {
                        $datasource['NoteID'] = $note['NoteID'];
                        $datasource['NoteText'] = $note['NoteText'];
                        $datasource['WSCategories'] = $note['WSCategories'];
                        $datasource['NoteNoExpiry'] = (empty($note['Expires']) ? 1 : 0);
                    }
                }
                $fieldsets = array();
                $fields = array();
                if (is_a($RECORD, 'crmOrganisation')) {
                    $fields[] = array('name' => 'OrganisationID', 'kind' => 'hidden');
                } elseif(is_a($RECORD, 'crmPerson')) {
                    $fields[] = array('name' => 'PersonID', 'kind' => 'hidden');
                }
                AddNoteFields($fields, null, TRUE);
/*                $fields = array_merge($fields, array(
                    array('name' => 'NoteID', 'kind' => 'hidden'),
                    array('name' => 'NoteText', 'caption' => 'Enter Note', 'kind' => 'control', 'type' => 'fmttext', 'rows' => 6, 'required' => TRUE),
                    array('name' => 'Categories[]', 'caption' => 'Category', 'kind' => 'control', 'type' => 'multi', 'allowempty' => FALSE, 'options' => $categories->GetCategories(), 'selected' => (empty($datasource['WSCategories']) ? $categories->DefCategories : $datasource['WSCategories']), 'required' => TRUE),
                    array('name' => 'NoteNoExpiry', 'tooltip' => 'Do not Expire', 'type' => 'switch', 'kind' => 'control', 'colour' => 'info', 'hint' => 'Do not automatically remove the note after '.SinPlu($SYSTEM_SETTINGS['ExpiryPolicies']['Notes'], 'month')),
                ));*/                
                $fieldsets[] = array('fields' => $fields);                
                $formitem = array(
                    'id' => 'frmEditNote', 'style' => 'standard', 
                    'datasource' => $datasource, 'buttons' => array(),
                    'fieldsets' => $fieldsets, 'borders' => FALSE
                );
                Form($formitem);
//                jsFormValidation('frmEditNote');            
            } else {
                SimpleAlertBox('error', 'No parent record found.');
            }
            ModalBody(TRUE);
            ModalFooter("frmEditNote", "/syscall.php?do=savenote", "function(){ LoadContent('noteslist', 'load.php?do=reloadnoteslist', { urlparams: { ".(!empty($datasource['PersonID']) ? "personid: ".intval($datasource['PersonID']) : "organisationid: ".intval($datasource['OrganisationID']))." } } ); }");
//            ModalFooter("frmEditNote", "/syscall.php?do=savenote", "function() { LoadContent('wsMain', 'load.php?do=notes', { divid: 'notes', spinner: false, urlparams: ".OutputArrayAsJSObject($_GET)." } ); } ");
            break;
        case 'moneyitem':
            ModalHeader('Money');
            ModalBody(FALSE);
            $moneyid = intval($_GET['MoneyID']);
            $sql = 
            "SELECT tblmoney.MoneyID, tblmoney.Received, tblmoney.ReceivedAmount, tblmoney.ReceivedFrom, tblmoney.TransactionReference, tblmoney.ISO4217,
                    tblmoney.AddInfo, tblmoney.Reversed, tblmoney.ReversalReason, tblmoney.ReversalReference,
                    tbltransactiontype.TransactionTypeID, tbltransactiontype.TransactionType,
                    tblmoneytoinvoice.MoneyToInvoiceID, tblmoneytoinvoice.MoneyID, tblmoneytoinvoice.InvoiceID, tblmoneytoinvoice.AllocatedAmount,
                    tblinvoice.InvoiceID, tblinvoice.InvoiceType, tblinvoice.InvoiceDate, tblinvoice.InvoiceDue, tblinvoice.InvoiceNo,
                    tblinvoice.VATNumber, tblinvoice.ReminderCount, tblinvoice.LastReminder, tblinvoice.CustomerRef,
                    tblcurrency.Currency, tblcurrency.`Decimals`, tblcurrency.Symbol,
                    IF(tblinvoice.InvoiceNo IS NULL, 1, 0) AS `ProForma`,
                    IF(tblinvoice.InvoiceNo IS NULL, 1, 0) AS `Draft`,
                    IF(tblinvoice.InvoiceType = 'creditnote', 'Credit Note', 'Invoice') AS `InvoiceTypeText`,
                    CONCAT_WS(' ', IF(tblinvoice.InvoiceNo IS NULL, IF(tblinvoice.InvoiceType = 'creditnote', 'Draft', 'Pro Forma'), ''),
                                   IF(tblinvoice.InvoiceType = 'creditnote', 'Credit Note', IF(tblinvoice.InvoiceNo IS NULL, CONCAT('#', CAST(tblinvoice.InvoiceID AS CHAR)), CONCAT('Invoice ', tblinvoice.InvoiceNo)))
                    ) AS `InvoiceCaption`,
                    COUNT(DISTINCT tblinvoiceitem.InvoiceItemID) AS `ItemCount`,
                    COALESCE(SUM(tblinvoiceitem.ItemNet), 0) AS `Net`,
                    COALESCE(SUM(tblinvoiceitem.ItemVAT), 0) AS `VAT`,
                    COALESCE(SUM(tblinvoiceitem.ItemVAT+tblinvoiceitem.ItemNet), 0) AS `Total`
             FROM tblmoney
             LEFT JOIN tbltransactiontype ON tbltransactiontype.TransactionTypeID = tblmoney.TransactionTypeID
             LEFT JOIN tblmoneytoinvoice ON tblmoneytoinvoice.MoneyID = tblmoney.MoneyID
             LEFT JOIN tblinvoice ON tblinvoice.InvoiceID = tblmoneytoinvoice.InvoiceID
             LEFT JOIN tblcurrency ON tblcurrency.ISO4217 = tblmoney.ISO4217
             LEFT JOIN tblinvoiceitem ON tblinvoiceitem.InvoiceID = tblinvoice.InvoiceID
             WHERE tblmoney.MoneyID = {$moneyid}
             GROUP BY tblmoney.MoneyID, tblinvoice.InvoiceID";
            $query = mysqli_query($SYSTEM_SETTINGS['Database'], $sql);
            if(mysqli_num_rows($query) > 0) {
                $count = 0;
                $datasource = array();
                $invoices = array();
                $addinfo = null;
                while($row = mysqli_fetch_assoc($query)) {
                    if($count == 0) {
                        $datasource[] = array('caption' => 'ID #', 'value' => htmlspecialchars($row['MoneyID']));
                        $datasource[] = array('caption' => 'Received', 'value' => htmlspecialchars(date('j F Y', strtotime($row['Received'].' UTC'))));
                        $datasource[] = array('caption' => 'Received Amount', 'value' => ScaledIntegerAsString($row['ReceivedAmount'], 'money', 100, FALSE, $row['Symbol']));
                        foreach(array('TransactionType' => 'Transaction Type', 'TransactionReference' => 'Transaction Reference',
                            'ReceivedFrom' => 'Received From') AS $key => $caption) {
                            if(!empty($row[$key])) {
                                $datasource[] = array('caption' => $caption, 'value' => htmlspecialchars($row[$key]));
                            }
                        }
                        if(!empty($row['Reversed'])) {
                            $datasource[] = array('caption' => 'Reversed', 'value' => htmlspecialchars(date('j F Y', strtotime($row['Reversed'].' UTC'))));
                            foreach(array('ReversalReason' => 'Reversal Reason', 'ReversalReference' => 'Reversal Reference') AS $key => $caption) {
                                if(!empty($row[$key])) {
                                    $datasource[] = array('caption' => $caption, 'value' => htmlspecialchars($row[$key]));
                                }
                            }
                        }
                        $addinfo = $row['AddInfo'];
                    }
                    $invoices[] = $row['InvoiceCaption'].', '.ScaledIntegerAsString($row['AllocatedAmount'], 'money', 100, FALSE, $row['Symbol']);
                    $count++;
                }
                $datasource[] = array('caption' => 'Allocation', 'value' => implode('<br>', $invoices));
                StaticTable($datasource, $summaryTable, array(), 9);
                if(!empty($addinfo)) {
                    Div(array('class' => 'pull-down'), 9);
                    SimpleHeading('Additional Info', 5, "sub", 10);
                    echo str_repeat("\t", 10)."<pre class=\"simple\">";
                    echo htmlspecialchars($addinfo);
                    echo "</pre>\n";
                    Div(null, 9);
                }
            } else {
                SimpleAlertBox('error', 'No data.');
            }
            ModalBody(TRUE);
            ModalFooter(null);
            break;
        case 'logdata':
            ModalHeader('System Log Data');
            ModalBody(FALSE);
            Authenticate();
            if(HasPermission('adm_syssettings')) {
                if(!empty($_GET['SysLogID'])) {
                    $syslogid = intval($_GET['SysLogID']);
                    $sql = "SELECT Data FROM tblsyslog WHERE SysLogID = {$syslogid}";
                    $data = SingleValue($SYSTEM_SETTINGS['Database'], $sql);
                    if(!empty($data)) {
                        echo str_repeat("\t", 9)."<pre class=\"simple\">";
                        $value = json_decode($data, TRUE);
                        if(is_array($value) || is_object($value)) {
                            print_r($value);
                        } else {
                            echo $value;
                        }
                        echo "</pre>\n";
                    } else {
                        SimpleAlertBox('error', 'No data.');
                    }
                }
                
            } else {
                SimpleAlertBox('error', 'Access Denied.');
            }
            ModalBody(TRUE);
            ModalFooter(null);
            break;
        case 'test':
            ModalHeader('Test');
            ModalBody(FALSE);
            echo "<select id=\"example-multiple-select\" class=\"form-control\" multiple=\"\" size=\"5\" name=\"example-multiple-select\">\n";
            echo "<option value=\"1\">Option #1</option>\n";
            echo "<option value=\"2\">Option #1</option>\n";
            echo "<option value=\"3\">Option #1</option>\n";
            echo "<option value=\"4\">Option #1</option>\n";
            echo "<option value=\"5\">Option #1</option>\n";
            echo "<option value=\"6\">Option #1</option>\n";
            echo "<option value=\"7\">Option #1</option>\n";
            echo "</select>\n";
            echo "<script src=\"js/dual-list-box.js\"></script>\n";
            //echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"css/bootstrap-duallistbox.css\">\n";            
            ModalBody(TRUE);
            ModalFooter(null);
            break;
        case 'downloaddata':
            ModalHeader('Downloads');
            ModalBody(FALSE);
            if(!empty($_GET['DocumentID'])) {
                $document = new crmDocument($SYSTEM_SETTINGS['Database'], $_GET['DocumentID']);
                if($document->Found) {
                    SimpleHeading('File: '.$document->Document['DisplayName'], 5, 'default', 9);
                    Div(array('class' => array('table-responsive')), 9);
                    echo str_repeat("\t", 10)."<table class=\"table table-hover table-condensed table-borderless\">\n";
                    echo str_repeat("\t", 11)."<tbody>\n";
                    $downloads = $document->Downloads();
                    foreach($downloads AS $download) {
                        echo str_repeat("\t", 12)."<tr>\n";
                        echo str_repeat("\t", 13)."<td>";
                        echo str_replace(' ', '&nbsp;', date('Y-m-d H:i:s', strtotime($download['Downloaded'].' UTC')));
                        echo "</td>\n";
                        echo str_repeat("\t", 13)."<td>";
                        echo FmtText($download['FmtFullname']);
                        echo "</td>\n";
                        echo str_repeat("\t", 12)."</tr>\n";
                    }
                    echo str_repeat("\t", 12)."</tr>\n";
                    echo str_repeat("\t", 13)."<td colspan=\"2\">";
                    echo FmtText('<b>'.SinPlu(count($downloads), 'download').'</b>');
                    echo "</td>\n";
                    echo str_repeat("\t", 12)."</tr>\n";
                    echo str_repeat("\t", 11)."</tbody>\n";
                    echo str_repeat("\t", 10)."</table>\n";
                    Div(null, 9);
                } else {
                    SimpleAlertBox('error', 'Document not found', 9);
                }
            }
            ModalBody(TRUE);
            ModalFooter(null);
            break;
        case 'docinfo':
            ModalHeader('Information');
            ModalBody(FALSE);
            if(!empty($_GET['DocumentID'])) {
                $document = new crmDocument($SYSTEM_SETTINGS['Database'], $_GET['DocumentID']);
                if(!empty($document->Document['EmailStatusID'])) {
                    SimpleHeading('Email', 5, 'sub', 9);
                    $datasource = array(
                        array('caption' => 'Status', 'value' => AdvIcon(array('icon' => $document->Document['DocumentIcon'], 'colour' => $document->Document['TextColour'], 'fixedwidth' => TRUE)) 
                                                                .'&#8200;'.FmtText("<{$document->Document['TextColour']}><b>".$document->Document['ToolTip']."</b></{$document->Document['TextColour']}>")),
                        array('caption' => 'Method', 'value' => $document->Document['EmailMethod']),
                        array('caption' => 'Request ID', 'value' => $document->Document['RequestID']),
                        array('caption' => 'Message ID', 'value' => $document->Document['MessageID']),
                    );
                    StaticTable($datasource, $summaryTable, array(), 9);  
                }
                SimpleHeading('Storage Details', 5, 'sub', 9);
                $datasource = array(
                    array('caption' => 'Document ID', 'value' => FmtText('<info><b>#'.$document->Document['DocumentID'].'</b></info>')),
                    array('caption' => 'Last Modified', 'value' => date('Y-m-d H:i:s', strtotime($document->Document['LastModified'].' UTC'))),
                    array('caption' => 'Title', 'value' => $document->Document['DocTitle']),
                    array('caption' => 'Filename', 'value' => $document->Document['Filename']),
                    array('caption' => 'File Type', 'value' => $document->Document['FileType']),
                    array('caption' => 'Mime Type', 'value' => $document->Document['MimeType']),
                    array('caption' => 'Storage', 'value' => AdvIcon(array('icon' => $document->Document['StorageIcon'], 'colour' => $document->Document['StorageIconColour'], 'fixedwidth' => TRUE))
                                                            .'&#8200;<b>'.LinkTo($document->Document['StorageDescription'], array('script' => "DownloadDocument({$document->Document['DocumentID']});", 'urlcolour' => $document->Document['StorageIconColour']))."</b>"), 
                );
                foreach(array('Bucket' => 'Bucket', 'Objectname' => 'Object Name') AS $fieldname => $caption) {
                    if(!empty($document->Document[$fieldname])) {
                        $datasource[] = array('caption' => $caption, 'value' => $document->Document[$fieldname]);
                    }
                }
                $datasource = array_merge($datasource, array(
                    array('caption' => 'Downloads', 'value' => number_format($document->Document['DownloadCount'], 0, '.', ',')),
                ));
                StaticTable($datasource, $summaryTable, array(), 9);
            }            
            ModalBody(TRUE);
            ModalFooter(null);
            break;
        case 'dlgTemplateListProperties':
        case 'dlgEditorProperties':
            $EDITORITEM = editorItemFactory::create($SYSTEM_SETTINGS['Database'], $_GET, $SYSTEM_SETTINGS);
            ModalHeader('Properties');
            ModalBody(FALSE);
            SimpleHeading($EDITORITEM->Descriptor(TRUE), 4, 'sub', 9);
            $fieldsets = array();
            $fields = $EDITORITEM->PropDialog();
            $fieldsets[] = array('fields' => $fields);                
            $formitem = array(
                'id' => 'frmEditorProperties', 'style' => 'standard', 
                    'datasource' => $EDITORITEM->Properties, 'buttons' => array(),
                    'fieldsets' => $fieldsets, 'borders' => FALSE
            );
            Form($formitem);
            ModalBody(TRUE);
            switch($do) {
                case 'dlgTemplateListProperties':
                    $cbSuccess = "RefreshDataTable( ".(!empty($_GET['EmailTemplateID']) ? "dt_etemplates" : "dt_ptemplates")." )";
                    break;
                default:
                    $cbSuccess = "LoadContent('fullContainer', '/load.php?do=editorHeader&".$EDITORITEM->URLParam()."', { divid: 'hdrEditor', spinner: false } )";
            }
            ModalFooter("frmEditorProperties", "/syscall.php?do=saveeditorproperties", "function(){ {$cbSuccess}; }");
            break;
        case 'ckeditor':
            $EDITORITEM = editorItemFactory::create($SYSTEM_SETTINGS['Database'], $_GET, $SYSTEM_SETTINGS);
            Div(array('class' => 'row'), 6);
            Div(array('class' => 'col-xs-12'), 7);
            if($EDITORITEM->Uncooked) {
                //Use ACE for uncooked source
                echo str_repeat("\t", 8)."<div id=\"aceeditor\"></div>\n";
                echo str_repeat("\t", 8)."<script src=\"js/ace/ace.js\"></script>\n";
                echo str_repeat("\t", 8)."<script type=\"text/javascript\">\n";
                echo str_repeat("\t", 9)."jQuery(function($) {\n";
                echo str_repeat("\t", 10)."var divPageContent = $('#page-content');\n";
                echo str_repeat("\t", 10)."var divEditor = $('#aceeditor');\n";
                echo str_repeat("\t", 10)."divEditor.height(divPageContent.height() - divEditor.offset().top + divPageContent.offset().top);\n";
                echo str_repeat("\t", 10)."var editor = ace.edit('aceeditor');\n";
                echo str_repeat("\t", 10)."editor.getSession().setUseWrapMode(true);\n";
                echo str_repeat("\t", 10)."execSyscall('/syscall.php?do=loadtemplate&".$EDITORITEM->URLParam()."', { parseJSON: false, defErrorDlg: true, cbSuccess: function( data ){ editor.setValue(data); } });\n";
                echo str_repeat("\t", 10)."\n";
                echo str_repeat("\t", 10)."\n";
                echo str_repeat("\t", 9)."});\n";
                echo str_repeat("\t", 8)."</script>\n";
            } else {
                //Use CKEditor for HTML
                echo str_repeat("\t", 8)."<div id=\"ckeditor\"></div>\n";
                echo str_repeat("\t", 8)."<script src=\"js/ckeditor/ckeditor.js\"></script>\n";
                echo str_repeat("\t", 8)."<script type=\"text/javascript\">\n";
                echo str_repeat("\t", 9)."jQuery(function($) {\n";
                echo str_repeat("\t", 10)."console.log('CKEditor Version '+CKEDITOR.version);\n";
                echo str_repeat("\t", 10)."CKEDITOR.on('instanceCreated', function( event, data ) {\n";
                echo str_repeat("\t", 11)."var editor = event.editor;\n";
                echo str_repeat("\t", 11)."editor.name = $(editor.element).attr('id')\n";
                echo str_repeat("\t", 10)."});\n";
                echo str_repeat("\t", 10)."CKEDITOR.on('instanceLoaded', function( event ) {\n";
                echo str_repeat("\t", 11)."event.editor.maximizeHeightNow();\n";
                echo str_repeat("\t", 11)."CKEDITOR.ajax.load( '/syscall.php?do=loadtemplate&".$EDITORITEM->URLParam()."', function( data ) { event.editor.setData(data.toString('utf-8')); } );\n";
                echo str_repeat("\t", 10)."});\n";
                echo str_repeat("\t", 10)."var divPageContent = $('#page-content');\n";
                echo str_repeat("\t", 10)."var divEditor = $('#ckeditor');\n";
                echo str_repeat("\t", 10)."divEditor.height(divPageContent.height() - divEditor.offset().top + divPageContent.offset().top);\n";
                echo str_repeat("\t", 10)."var editor = CKEDITOR.appendTo( 'ckeditor', { customConfig: 'ckconfig.js' } );\n";
                echo str_repeat("\t", 9)."});\n";
                echo str_repeat("\t", 8)."</script>\n";
            }
            Div(null, 7);
            Div(null, 6); //row closure
            break;
        case 'bulkemailHeader':
            Div(array('class' => 'row'), 6);
            Div(array('class' => 'col-xs-12'), 7);
            $stdMenuitems = array();
//            $stdMenuitems[] = array('colour' => 'info', 'script' => "LoadContent('wsSide', '/load.php?do=sidebar_scheduler', { divid: 'sidebar_scheduler', spinner: false } );", 'icon' => 'fa-refresh');
            $stdMenuitems[] = array('colour' => 'info', 'function' => 'toggle', 'icon' => 'fa-arrows-v', 'style' => 'alt');
            stdTitleBlock('Bulk Email', $do, $stdMenuitems);
            Div(array('class' => array('block-content')), 8);
            Div(null, 8); //content block
            Div(null, 7); //recent block
            Div(null, 7);
            Div(null, 6); //row closure
            break;
        case 'editorHeader':
            $EDITORITEM = editorItemFactory::create($SYSTEM_SETTINGS['Database'], $_GET, $SYSTEM_SETTINGS);
            $buttons = $EDITORITEM->Buttons();
            $stdMenuitems = array();
            if($EDITORITEM->Uncooked) {
                $getdata = "ace.edit('aceeditor').getValue()";
            } else {
                $getdata = "CKEDITOR.instances.ckeditor.getData()";
            }
//            $stdMenuitems[] = array('colour' => 'info', 'script' => "LoadContent('wsSide', '/load.php?do=sidebar_scheduler', { divid: 'sidebar_scheduler', spinner: false } );", 'icon' => 'fa-refresh');
            if(array_key_exists('save', $buttons)) {
                $stdMenuitems[] = array(
                    'group' => 'full', 'colour' => 'success', 'icon' => 'fa-floppy-o', 'tooltip' => $buttons['save'],
                    'script' => "execSyscall('/syscall.php?do=saveeditor&".$EDITORITEM->URLParam()."', { parseJSON: true, defErrorDlg: true, postparams: { Body: {$getdata} }, cbSuccess: function(){ dlgDataSaved('The document has been saved.'); } });",
                );
            }
            if(array_key_exists('bulksend', $buttons)) {
                $stdMenuitems[] = array(
                    'group' => 'full', 'colour' => 'success', 'icon' => 'gi-send', 'tooltip' => $buttons['bulksend'],
                    'script' => "execSyscall('/syscall.php?do=bulksend&".$EDITORITEM->URLParam()."&".$EDITORITEM->Properties['Request']."', { parseJSON: true, defErrorDlg: true, postparams: { Body: {$getdata}, BulkEmailID: ".$EDITORITEM->Properties['BulkEmailID']." }, cbSuccess: function(){ }, cbPosted: function(){ bootbox.dialog({ message: 'Your bulk email task has started.', title: '<span class=\'text-success\'><i class=\'fa fa-check-circle\'></i> <strong>Bulk Email</strong></span>', buttons: { main: { label: 'OK', className: 'btn-success', callback: function( ) { window.location.href = '".$EDITORITEM->Properties['SourceURL']."' } } } }); } });", 
                );
            }
            if(array_key_exists('properties', $buttons)) {
                $stdMenuitems[] = array(
                    'group' => 'full', 'colour' => 'info', 'icon' => 'gi-settings', 'tooltip' => $buttons['properties'],
                    'script' => "OpenDialog('dlgEditorProperties&".$EDITORITEM->URLParam()."', { large: true });", 
                );
            }
//            $stdMenuitems[] = array('colour' => 'info', 'function' => 'toggle', 'icon' => 'fa-arrows-v', 'style' => 'alt');
            stdTitleBlock($EDITORITEM->Caption, $do, $stdMenuitems);
            Div(array('class' => 'block-content'), 8);
            Div(array('class' => 'row'), 9);
            Div(array('class' => 'col-xs-12 col-md-6'), 10);
                $datasource = array();
                foreach($EDITORITEM->Heading() AS $caption => $text) {
                    $datasource[] = array('caption' => $caption, 'value' => FmtText($text));
                }
                $table = $summaryTable;
                $table['margin'] = FALSE;
                StaticTable($datasource, $table, array(), 11);  
            Div(null, 10); //column with heading info
            Div(array('class' => array('col-md-6', 'hidden-xs', 'hidden-sm')), 10);
                $group = MenuToAdvButtonGroup($stdMenuitems, array('sizeadjust' => 2, 'groupfilter' => 'full'));
                //print_r($group);
                AdvButtonGroup($group, array(), 9);                    
            Div(null, 10); //column with buttons
            Div(null, 9);
            Div(null, 8);
            Div(null, 7); //header block closure
            break;
        case 'admin_panel':
            Authenticate();
            if(HasPermission(array('adm_syssettings', 'adm_security'))) {        
                switch($_GET['tabid']) {
                    case 'tab-general':
                        $fieldsets = array();
                        $fieldsets[] = array('caption' => 'Organisation', 'fields' => array(
                            array('name' => 'General.OrgLongName', 'caption' => 'Organisation', 'kind' => 'control', 'type' => 'string', 'required' => TRUE),
                            array('name' => 'General.OrgShortName', 'caption' => 'Abbreviation', 'kind' => 'control', 'type' => 'string', 'size' => 2, 'required' => TRUE),
                            array('name' => 'General.Address.Lines', 'caption' => 'Address', 'kind' => 'control', 'type' => 'memo', 'rows' => 4),
                            array('name' => 'General.Address.Postcode', 'caption' => 'Postcode', 'kind' => 'control', 'type' => 'string', 'size' => 3),
                            array('name' => 'General.Address.Town', 'caption' => 'Town', 'kind' => 'control', 'type' => 'string', 'size' => 6),
                            array('name' => 'General.Address.County', 'caption' => 'County', 'kind' => 'control', 'type' => 'string', 'size' => 6),
                            array('name' => 'General.Address.Region', 'caption' => 'Region', 'kind' => 'control', 'type' => 'string', 'size' => 6),
                            array('name' => 'General.Address.CountryCode', 'caption' => 'CountryCode', 'kind' => 'control', 'type' => 'string', 'size' => 2, 'asstringfunction' => @strtoupper),
                            array('name' => 'General.Address.Country', 'caption' => 'Country', 'kind' => 'control', 'type' => 'string', 'size' => 6),
                            array('name' => 'General.Website', 'caption' => 'Website', 'kind' => 'control', 'type' => 'url', 'required' => 'Enter a valid URL'),
                            array('name' => 'General.VATNumber', 'caption' => 'VAT No', 'kind' => 'control', 'type' => 'string', 'size' => 6),
                            array('name' => 'General.CharityNo', 'caption' => 'Charity No', 'kind' => 'control', 'type' => 'string', 'size' => 6),
                            array('name' => 'General.CompanyNo', 'caption' => 'Company No', 'kind' => 'control', 'type' => 'string', 'size' => 6),
                        ));
                        $formitem = array(
                            'id' => 'frmGeneral', 'style' => 'standard', 
                            'onsubmit' => "submitForm( 'frmGeneral', '/syscall.php?do=savesettings', { parseJSON: true, defSuccessDlg: true, defErrorDlg: true } ); return false;",
                            'datasource' => $SYSTEM_SETTINGS, 'buttons' => DefFormButtons("Apply Changes"),
                            'fieldsets' => $fieldsets, 'borders' => TRUE
                        );
                        Form($formitem, 9);
                        jsFormValidation('frmGeneral');
                        break;
                    case 'tab-system':
                        $fieldsets = array();
                        $fieldsets[] = array('caption' => 'System', 'fields' => array(
                            array('name' => 'System.Timezone', 'caption' => 'Timezone', 'kind' => 'control', 'type' => 'string', 'size' => 3),
                            array('name' => 'System.DebugMode', 'kind' => 'control', 'colour' => 'danger', 'type' => 'switch', 'tooltip' => 'Debug Mode',
                                  'hint' => 'Enable Debug Mode'),
                            array('name' => 'System.Email.DebugToEmail', 'caption' => 'Debug Email Address', 'kind' => 'control', 'type' => 'email', 'hint' => 'If set and debug mode enabled, all emails will be sent to this address'),
                            array('name' => 'System.NoAvatarCaching', 'kind' => 'control', 'colour' => 'warning', 'type' => 'switch', 'tooltip' => 'Disable Avatar Caching',
                                  'hint' => 'Disable Avatar Caching'),
                            array('name' => 'System.FileStore', 'caption' => 'File Store', 'kind' => 'control', 'type' => 'combo', 'options' => array('S3' => 'Amazon S3', 'DB' => 'Database'), 'size' => 6),
                            array('name' => 'System.TimeLimitExport', 'caption' => 'Export Max. Time', 'kind' => 'control', 'type' => 'integer', 'size' => 3, 'hint' => 'Max. execution time (s) for export tasks'),
                            array('name' => 'System.BOM', 'kind' => 'control', 'colour' => 'info', 'type' => 'switch', 'tooltip' => 'Byte Order Marker',
                                  'hint' => 'Include Byte Order Marker for UTF-8 files'),
                            array('name' => 'System.ExcelCSV', 'kind' => 'control', 'colour' => 'info', 'type' => 'switch', 'tooltip' => 'Excel CSVs',
                                  'hint' => 'Adjust CSV files for use in Excel, this may cause incompatibility with other applications'),
                        ));
                        $fieldsets[] = array('caption' => 'Database', 'fields' => array(
                            array('name' => 'System.DB.Host', 'caption' => 'Host', 'kind' => 'control', 'type' => 'string', 'size' => 3),
                            array('name' => 'System.DB.Port', 'caption' => 'Port', 'kind' => 'control', 'type' => 'integer', 'size' => 3),
                            array('name' => 'System.DB.Schema', 'caption' => 'Schema', 'kind' => 'control', 'type' => 'string', 'size' => 3),
                            array('name' => 'System.DB.Username', 'caption' => 'Username', 'kind' => 'control', 'type' => 'string', 'size' => 3),
                            array('name' => 'System.DB.Password', 'caption' => 'Password', 'kind' => 'control', 'type' => 'password', 'size' => 6, 'encrypted' => TRUE),
                        ));
                        $fieldsets[] = array('caption' => 'Email', 'fields' => array(
                            array('name' => 'System.Email.Paused', 'kind' => 'control', 'colour' => 'warning', 'type' => 'switch', 'tooltip' => 'Pause Email Queue',
                                  'hint' => 'Pause the Email Queue'),
                            array('name' => 'System.Email.EmailMethod', 'caption' => 'Email Method', 'kind' => 'control', 'type' => 'combo', 'options' => array('SES' => 'Amazon SES', 'SMTP' => 'SMTP', 'PHP' => 'PHP'), 'size' => 6),
                            array('name' => 'System.Email.Defaults.FromName', 'caption' => 'Default From Name', 'kind' => 'control', 'type' => 'string', 'size' => 6),
                            array('name' => 'System.Email.Defaults.FromEmail', 'caption' => 'Default From Email', 'kind' => 'control', 'type' => 'string', 'size' => 6),
                            array('name' => 'System.Email.SliceSize', 'caption' => 'Slice size', 'kind' => 'control', 'type' => 'integer', 'size' => 3, 'hint' => 'Set the number of messages that will be processed by each queue process cycle'),
                        ));
                        $fieldsets[] = array('caption' => 'SMTP', 'fields' => array(
                            array('name' => 'System.Email.SMTP.Host', 'caption' => 'Host', 'kind' => 'control', 'type' => 'string', 'size' => 3),
                            array('name' => 'System.Email.SMTP.Port', 'caption' => 'Port', 'kind' => 'control', 'type' => 'integer', 'size' => 3),
                            array('name' => 'System.Email.SMTP.Authenticate', 'kind' => 'control', 'colour' => 'success', 'type' => 'switch', 'tooltip' => 'Use SMTP Authentication',
                                  'hint' => 'Use SMTP Authentication'),
                            array('name' => 'System.Email.SMTP.Security', 'caption' => 'Security', 'kind' => 'control', 'type' => 'combo', 'options' => array('tls' => 'TLS', 'ssl' => 'SSL'), 'size' => 6),
                            array('name' => 'System.Email.SMTP.Helo', 'caption' => 'Helo Override', 'kind' => 'control', 'type' => 'string', 'hint' => 'Leave empty to use the host name default (recommended)', 'size' => 6),
                            array('name' => 'Credentials.SMTP.Username', 'caption' => 'Username', 'kind' => 'control', 'type' => 'string', 'size' => 3),
                            array('name' => 'Credentials.SMTP.Password', 'caption' => 'Password', 'kind' => 'control', 'type' => 'password', 'size' => 6, 'encrypted' => TRUE),
                        ));
                        $fieldsets[] = array('caption' => 'Amazon S3', 'fields' => array(
                            array('name' => 'Credentials.AWS.S3.AccessKey', 'caption' => 'Access Key', 'kind' => 'control', 'type' => 'string', 'size' => 6),
                            array('name' => 'Credentials.AWS.S3.SecretKey', 'caption' => 'Secret Key', 'kind' => 'control', 'type' => 'password', 'size' => 6, 'encrypted' => TRUE),
                            array('name' => 'Storage.Region', 'caption' => 'Region', 'kind' => 'control', 'type' => 'string', 'size' => 3),
                            array('name' => 'Storage.Bucket', 'caption' => 'Bucket', 'kind' => 'control', 'type' => 'string', 'size' => 3),
                            
                        ));
                        $fieldsets[] = array('caption' => 'Amazon SES', 'fields' => array(
                            array('name' => 'Credentials.AWS.SES.AccessKey', 'caption' => 'Access Key', 'kind' => 'control', 'type' => 'string', 'size' => 6),
                            array('name' => 'Credentials.AWS.SES.SecretKey', 'caption' => 'Secret Key', 'kind' => 'control', 'type' => 'password', 'size' => 6, 'encrypted' => TRUE),
                            array('name' => 'System.Email.Region', 'caption' => 'Region', 'kind' => 'control', 'type' => 'string', 'size' => 3),
                            array('name' => 'System.Email.MaxSendRate', 'caption' => 'Max. Rate', 'kind' => 'control', 'type' => 'integer', 'size' => 3),
                        ));
                        $formitem = array(
                            'id' => 'frmSystem', 'style' => 'standard',
                            'onsubmit' => "submitForm( 'frmSystem', '/syscall.php?do=savesettings', { parseJSON: true, defSuccessDlg: true, defErrorDlg: true } ); return false;",
                            'datasource' => $SYSTEM_SETTINGS, 'buttons' => DefFormButtons("Apply Changes"),
                            'fieldsets' => $fieldsets, 'borders' => TRUE
                        );
                        Form($formitem, 11);
                        jsFormValidation('frmSystem');
                        break;
                    case 'tab-services':
                        $fieldsets = array();
                        $fieldsets[] = array('caption' => 'PCA Predict', 'fields' => array(
                            array('name' => 'Credentials.PCAPredict.APIKeys.DDI', 'caption' => 'DDI Check API Key', 'kind' => 'control', 'type' => 'string', 'size' => 6),
                        ));
                        $buttons['btnsave']['script'] = "SaveSettings('frmServices');";
                        $formitem = array(
                            'id' => 'frmServices', 'style' => 'standard',
                            'onsubmit' => "submitForm( 'frmServices', '/syscall.php?do=savesettings', { parseJSON: true, defSuccessDlg: true, defErrorDlg: true } ); return false;",
                            'datasource' => $SYSTEM_SETTINGS, 'buttons' => DefFormButtons("Apply Changes"),
                            'fieldsets' => $fieldsets, 'borders' => TRUE
                        );
                        Form($formitem, 11);
                        jsFormValidation('frmServices');
                        break;
                    case 'tab-customise':
                        $fieldsets = array();
                        $fieldsets[] = array('caption' => 'General', 'fields' => array(
                            array('name' => 'General.SiteName', 'caption' => 'Site Name', 'kind' => 'control', 'type' => 'string', 'required' => TRUE, 'size' => 3),
                            array('name' => 'Customise.SidebarLogo', 'kind' => 'control', 'colour' => 'info', 'type' => 'switch', 'tooltip' => 'Show logo in sidebar',
                                  'hint' => 'Show logo in sidebar'
                            ),
                            array('name' => 'Customise.AnimatedHeader', 'kind' => 'control', 'type' => 'switch', 'tooltip' => 'Banner Animation',
                                'hint' => 'Use animation for the main banner'
                            ),
                            array('name' => 'Membership.GradeCaption', 'caption' => 'Grade Caption', 'kind' => 'control', 'type' => 'string', 'size' => 3),
                            array('name' => 'Membership.CompletionStageCaption', 'caption' => 'Completion Stage Caption', 'kind' => 'control', 'type' => 'string', 'size' => 3),
                            array('name' => 'Customise.MaxRecentViewedCount', 'caption' => 'Recently viewed', 'kind' => 'control', 'type' => 'integer', 'size' => 3, 'min' => 0, 'max' => 25),
                        ));
                        $fieldsets[] = array('caption' => 'Data Entry', 'fields' => array(
                            array('name' => 'Customise.DOBRequired', 'kind' => 'control', 'colour' => 'info', 'type' => 'switch', 'tooltip' => 'Date of birth required',
                                  'hint' => 'Require DOB'
                            ),
                            array('name' => 'Customise.GenderRequired', 'kind' => 'control', 'colour' => 'info', 'type' => 'switch', 'tooltip' => 'Gender required',
                                  'hint' => 'Require Gender'
                            ),
                            array('name' => 'Customise.Title', 'caption' => 'Title Field', 'kind' => 'control', 'type' => 'string', 'hint' => 'Comma separated list of allowed Title values. Leave empty for free form entry.'),
                            
                        ));
                        $buttons['btnsave']['script'] = "SaveSettings('frmCustomise');";
                        $formitem = array(
                            'id' => 'frmCustomise', 'style' => 'standard', 
                            'onsubmit' => "submitForm( 'frmCustomise', '/syscall.php?do=savesettings', { parseJSON: true, defSuccessDlg: true, defErrorDlg: true } ); return false;",
                            'datasource' => $SYSTEM_SETTINGS, 'buttons' => DefFormButtons("Apply Changes"),
                            'fieldsets' => $fieldsets, 'borders' => TRUE
                        );
                        Form($formitem, 11);
                        jsFormValidation('frmCustomise');
                        break;
                    case 'tab-finance':
                        $fieldsets = array();
                        $fieldsets[] = array('caption' => 'Invoicing', 'fields' => array(
                            array('name' => 'Finance.InvPrefix.Invoice', 'caption' => 'Invoice Prefix', 'kind' => 'control', 'type' => 'string', 'required' => TRUE, 'size' => 3),
                            array('name' => 'Finance.InvPrefix.CreditNote', 'caption' => 'Credit Note Prefix', 'kind' => 'control', 'type' => 'string', 'required' => TRUE, 'size' => 3),
                            array('name' => 'Finance.InvDigits', 'caption' => 'Inv. no. of Digits', 'kind' => 'control', 'type' => 'integer', 'required' => TRUE, 'size' => 3, 'min' => 3, 'hint' => 'Does not include prefix'),
                            array('name' => 'Finance.InvoiceDue', 'caption' => 'Invoice Due Interval', 'kind' => 'control', 'type' => 'integer', 'required' => TRUE, 'size' => 3),
                            array('name' => 'Finance.OverdueRate', 'caption' => 'Invoice Overdue rate', 'kind' => 'control', 'type' => 'integer', 'required' => TRUE, 'size' => 3),
                        ));
                        $fieldsets[] = array('caption' => 'Financial Year', 'fields' => array(
                            array('name' => 'Finance.FinYear.Day', 'caption' => 'Start Day', 'kind' => 'control', 'type' => 'integer', 'required' => TRUE, 'size' => 3),
                            array('name' => 'Finance.FinYear.Month', 'caption' => 'Start Month', 'kind' => 'control', 'type' => 'integer', 'required' => TRUE, 'size' => 3),
                        ));
                        $fieldsets[] = array('caption' => 'Direct Debit', 'fields' => array(
                            array('name' => 'Finance.DirectDebit.ReferenceReq', 'kind' => 'control', 'colour' => 'info', 'type' => 'switch', 'tooltip' => 'Instructions must include a Direct Debit Reference',
                                  'hint' => 'Reference required'
                            ),
                            array('name' => 'Finance.DirectDebit.BankNameReq', 'kind' => 'control', 'colour' => 'info', 'type' => 'switch', 'tooltip' => 'Instructions must include the name of the Bank',
                                  'hint' => 'Bank name required'
                            ),
                            array('name' => 'Finance.DirectDebit.DocumentReq', 'kind' => 'control', 'colour' => 'info', 'type' => 'switch', 'tooltip' => 'The paper instruction must be uploaded for new Instructions',
                                  'hint' => 'Paper Instruction required'
                            ),
                            array('name' => 'Finance.DirectDebit.ProcessorName', 'caption' => 'Name Processor', 'kind' => 'control', 'type' => 'string', 'required' => TRUE, 'hint' => 'The name of the organisation processing the Direct Debit submissions'),
                        ));
                        $fieldsets[] = array('caption' => 'Finance Export', 'fields' => array(
                            array('name' => 'Finance.Export.Enabled', 'kind' => 'control', 'colour' => 'info', 'type' => 'switch', 'tooltip' => 'Enable the Finance data export',
                                  'hint' => 'Enable Export'
                            ),
                            array('name' => 'Finance.Export.SettledOnly', 'kind' => 'control', 'colour' => 'info', 'type' => 'switch', 'tooltip' => 'Export only documents that have been settled',
                                  'hint' => 'Settled only'
                            ),
                            array('name' => 'Finance.Export.BOM', 'kind' => 'control', 'colour' => 'info', 'type' => 'switch', 'tooltip' => 'Byte Order Marker',
                                  'hint' => 'Include Byte Order Marker for UTF-8 files'),
                            array('name' => 'Finance.Export.ExcelCSV', 'kind' => 'control', 'colour' => 'info', 'type' => 'switch', 'tooltip' => 'Excel CSVs',
                                  'hint' => 'Adjust CSV files for use in Excel, this may cause incompatibility with other applications'),
                            array('name' => 'Finance.Export.QuoteAll', 'kind' => 'control', 'colour' => 'info', 'type' => 'switch', 'tooltip' => 'Quote all Fields',
                                  'hint' => 'If enabled, all fields are quoted; otherwise numeric values are not quoted'),
                            array('name' => 'Finance.Export.Header', 'kind' => 'control', 'colour' => 'info', 'type' => 'switch', 'tooltip' => 'Header',
                                  'hint' => 'Write a header with fieldnames'),
                            array('name' => 'Finance.Export.Testmode', 'kind' => 'control', 'colour' => 'warning', 'type' => 'switch', 'tooltip' => 'Test Mode',
                                  'hint' => 'Use test mode for finance exports'),
                        ));
                        $buttons['btnsave']['script'] = "SaveSettings('frmFinance');";
                        $formitem = array(
                            'id' => 'frmFinance', 'style' => 'standard', 
                            'onsubmit' => "submitForm( 'frmFinance', '/syscall.php?do=savesettings', { parseJSON: true, defSuccessDlg: true, defErrorDlg: true } ); return false;",
                            'datasource' => $SYSTEM_SETTINGS, 'buttons' => DefFormButtons("Apply Changes"),
                            'fieldsets' => $fieldsets, 'borders' => TRUE
                        );
                        Form($formitem, 11);
                        jsFormValidation('frmFinance');
                        break;
                    case 'tab-security':
                        $fieldsets = array();
                        $fieldsets[] = array('caption' => 'General', 'fields' => array(
                            array('name' => 'Security.EncryptionKey', 'caption' => 'Encryption Key', 'kind' => 'control', 'type' => 'string',
                                  'hint' => '<warning>Warning! changing the encryption key will invalidate all encrypted data, including bank account details and all credentials</warning>'
                            ),
                        ));
                        $fieldsets[] = array('caption' => 'Authentication', 'fields' => array(
                            array('name' => 'Security.TokenTimeout', 'caption' => 'Token timeout', 'kind' => 'control', 'type' => 'integer', 'size' => 3,
                                  'hint' => 'Maximum age of authentication tokens in <b>hours</b>'
                            ),
                            array('name' => 'Security.MaxFailCount', 'caption' => 'Max. failure count', 'kind' => 'control', 'type' => 'integer', 'size' => 3,
                                  'hint' => 'If the maximum count is exceeded, the account will be locked'
                            ),
                        ));
                        $fieldsets[] = array('caption' => 'Password Policy', 'fields' => array(
                            array('name' => 'Security.AllowPasswordChange', 'kind' => 'control', 'colour' => 'warning', 'type' => 'switch', 'tooltip' => 'Allow password changes',
                                  'hint' => 'Allow user to change their password'
                            ),
                            array('name' => 'Security.MinPasswordLength', 'caption' => 'Min. password length', 'kind' => 'control', 'type' => 'integer', 'size' => 3),
                            array('name' => 'Security.EnforcePWComplexity', 'kind' => 'control', 'colour' => 'warning', 'type' => 'switch', 'tooltip' => 'Enforce password complexity',
                                  'hint' => 'Enforce password complexity'
                            ),
                        ));
                        $buttons['btnsave']['script'] = "SaveSettings('frmSecurity');";
                        $formitem = array(
                            'id' => 'frmSecurity', 'style' => 'standard', 
                            'onsubmit' => "submitForm( 'frmSecurity', '/syscall.php?do=savesettings', { parseJSON: true, defSuccessDlg: true, defErrorDlg: true } ); return false;",
                            'datasource' => $SYSTEM_SETTINGS, 'buttons' => DefFormButtons("Apply Changes"),
                            'fieldsets' => $fieldsets, 'borders' => TRUE
                        );
                        Form($formitem, 11);
                        jsFormValidation('frmSecurity');
                        break;
                    case 'tab-expiry':
                        $fieldsets = array();
                        $fieldsets[] = array('caption' => 'System Log', 'fields' => array(
                            array('name' => 'ExpiryPolicies.SysLog', 'caption' => 'Default', 'kind' => 'control', 'type' => 'integer', 'required' => TRUE, 'min' => 1, 'hint' => 'In <b>days</b>'),
                            array('name' => 'ExpiryPolicies.BGProc', 'caption' => 'Background Processor', 'kind' => 'control', 'type' => 'integer', 'required' => TRUE, 'hint' => 'In <b>days</b>'),
                            array('name' => 'ExpiryPolicies.LogErrors', 'caption' => 'Logged Errors', 'kind' => 'control', 'type' => 'integer', 'required' => TRUE, 'hint' => 'In <b>days</b>'),
                            array('name' => 'ExpiryPolicies.LogWarnings', 'caption' => 'Logged Warnings', 'kind' => 'control', 'type' => 'integer', 'required' => TRUE, 'hint' => 'In <b>days</b>'),
                        ));
                        $fieldsets[] = array('caption' => 'Data Retention', 'fields' => array(
                            array('name' => 'ExpiryPolicies.Notes', 'caption' => 'Notes', 'kind' => 'control', 'type' => 'integer', 'required' => TRUE, 'hint' => 'In <b>months</b>'),
                        ));
                        $fieldsets[] = array('caption' => 'Data Protection', 'fields' => array(
                            array('name' => 'ExpiryPolicies.Export', 'caption' => 'Export files', 'kind' => 'control', 'type' => 'integer', 'required' => TRUE, 'hint' => 'In <b>days</b>'),
                        ));
                        $buttons['btnsave']['script'] = "SaveSettings('frmExpiry');";
                        $formitem = array(
                            'id' => 'frmExpiry', 'style' => 'standard', 
                            'onsubmit' => "submitForm( 'frmExpiry', '/syscall.php?do=savesettings', { parseJSON: true, defSuccessDlg: true, defErrorDlg: true } ); return false;",
                            'datasource' => $SYSTEM_SETTINGS, 'buttons' => DefFormButtons("Apply Changes"),
                            'fieldsets' => $fieldsets, 'borders' => TRUE
                        );
                        Form($formitem, 11);
                        jsFormValidation('frmExpiry');
                        break;                        
                }
            } else {
                SimpleAlertBox('error', 'Access Denied.');
            }
            break;
        case 'explainfee':
            $params = $_GET;
            unset($params['do']);
            unset($params['feeobject']);
            ModalHeader('Explain Fee');
            ModalBody(FALSE);
            if(!empty($_GET['invoiceitemid'])) {
                $sql = "SELECT `Explain` FROM tblinvoiceitem WHERE tblinvoiceitem.InvoiceItemID = ".intval($_GET['invoiceitemid']);
                $explanation = SingleValue($SYSTEM_SETTINGS['Database'], $sql);
                if(!empty($explanation)) {
                    $explanation = json_decode($explanation);
                    $count = 0;
                    foreach($explanation AS $line) {
                        echo ($count > 0 ? "<br>\n" : "").str_repeat("\t", 15).FmtText($line);
                        $count++;
                    }
                } else {
                    SimpleAlertBox('error', 'Unable to load explanation.');
                }
            } else {
                $feeobject = null;
                $fee = null;
                switch($_GET['feeobject']) {
                    case 'crmMSFees':
                        $feeobject = new crmMSFees($SYSTEM_SETTINGS['Database'], $SYSTEM_SETTINGS['Membership']['GradeCaption']);
                        $fee = $feeobject->CalculateFee($params);
                        break;
                }
                if(!empty($feeobject)) {
                    $count = 0;
                    foreach($fee->Explanation AS $line) {
                        echo ($count > 0 ? "<br>\n" : "").str_repeat("\t", 15).FmtText($line);
                        $count++;
                    }
                } else {
                    SimpleAlertBox('error', 'Unable to load fee object.');
                }
            }
            ModalBody(TRUE);
            ModalFooter(null);
            break;
        case 'xchangerates':
            ModalHeader('Exchange Rates');
            ModalBody(FALSE);
                $countries = new crmCountries($SYSTEM_SETTINGS['Database']);            
                $fieldsets = array();
                $fieldsets[] = array('fields' => array(
                    array('name' => 'ISO4217', 'caption' => 'Base Currency', 'kind' => 'control', 'type' => 'advcombo', 'required' => TRUE, 'allowempty' => FALSE, 'options' => $countries->Currencies),
                    array('name' => 'Value', 'caption' => 'Base Value', 'kind' => 'control', 'type' => 'money', 'required' => TRUE),
                ));
                $formitem = array(
                    'id' => 'frmXChangeRates', 'style' => 'vertical',
                    'datasource' => array(
                        'ISO4217' => $countries->DefCurrency,
                        'Value' => 10000,
                    ),
                    'buttons' => array(
                        array('type' => 'submit', 'id' => 'btnsubmit', 'colour' => 'success', 'icon' => 'fa-play', 'iconalign' => 'left', 'caption' => 'Convert', 'sizeadjust' => 1),
                    ),
                    'fieldsets' => $fieldsets, 'borders' => FALSE,
                    'onsubmit' => "LoadContent('divCalcResults', '/load.php?do=cconvert&base='+$('[name=\"ISO4217\"]').val()+'&value='+inputValueToScaledInteger('Value'), { spinner: true, spinnersize: 3 }); return false;",
                );
                Form($formitem);
                Div(array('id' => 'divCalcResults', 'class' => 'h4', 'style' => 'line-height: 1.5;'));
                echo str_repeat("\t", 7).FmtText("Select the currency and enter a base value, then click the Convert button.");
                Div(null);
            ModalBody(TRUE);
            ModalFooter(null);
            break;
        case 'msfeecalculator':
            ModalHeader('Membership Calculator');
            ModalBody(FALSE);
            Div(array('class' => 'row'), 7);
            Div(array('class' => 'col-xs-6'), 8);
                SimpleHeading('Parameters', 4, 'sub', 9);
                $grades = new crmMSGrades($SYSTEM_SETTINGS['Database']);
                $countries = new crmCountries($SYSTEM_SETTINGS['Database']);            
                $fieldsets = array();
                $fieldsets[] = array('fields' => array(
                    array('name' => 'ForDate', 'caption' => 'Pricing Date', 'kind' => 'control', 'type' => 'date', 'required' => TRUE),
                    array('name' => 'MSGradeID', 'caption' => 'Select '.$SYSTEM_SETTINGS['Membership']['GradeCaption'], 'kind' => 'control', 'type' => 'advcombo', 'allowempty' => FALSE, 'options' => $grades->GetGrades(), 'required' => TRUE),
                    array('name' => 'ISO3166', 'caption' => 'Country of Residence', 'kind' => 'control', 'type' => 'advcombo', 'required' => TRUE, 'allowempty' => FALSE, 'size' => 6, 'options' => $countries->Countries),
                    array('name' => 'ISO4217', 'caption' => 'Currency', 'kind' => 'control', 'type' => 'advcombo', 'required' => TRUE, 'allowempty' => FALSE, 'size' => 6, 'options' => $countries->Currencies),
                    array('name' => 'NOY', 'caption' => 'Term', 'kind' => 'control', 'type' => 'advcombo', 'required' => TRUE, 'allowempty' => FALSE, 'size' => 4, 'options' => array(1 => '1 Year', 3 => '3 Year')),
                    array('name' => 'IsDD', 'kind' => 'control', 'colour' => 'info', 'type' => 'switch', 'tooltip' => 'Direct Debit',
                          'hint' => 'Apply Direct Debit pricing'
                    ),
                    array('name' => 'IsGroup', 'kind' => 'control', 'colour' => 'info', 'type' => 'switch', 'tooltip' => 'Apply Group tariff',
                          'hint' => 'Use Group pricing tariff'
                    ),
                ));
                $formitem = array(
                    'id' => 'frmMSFeeCalculator', 'style' => 'vertical',
                    'datasource' => array(
                        'ForDate' => gmdate('Y-m-d H:i:s'),
                        'ISO3166' => $countries->DefCountry,
                        'ISO4217' => $countries->DefCurrency,
                    ),
                    'buttons' => array(
                        array('type' => 'submit', 'id' => 'btnsubmit', 'colour' => 'success', 'icon' => 'fa-play', 'iconalign' => 'left', 'caption' => 'Calculate', 'sizeadjust' => 1),
                    ),
                    'fieldsets' => $fieldsets, 'borders' => FALSE,
                    'onsubmit' => "ExecCalculateMSFee({ formid: 'frmMSFeeCalculator', divid: 'divFeeResults' }); return false;",
                );
                Form($formitem);
            Div(null);
            Div(array('class' => 'col-xs-6'), 7);
                SimpleHeading('Results', 4, 'sub', 9);
                Div(array('id' => 'divFeeResults', 'class' => 'h5', 'style' => 'line-height: 1.5;'));
                echo str_repeat("\t", 7).FmtText("Select the parameters for your calculation using the form above, then click the Calculate button. The results of your calculation will appear here.");
                Div(null);
            Div(null);
            Div(null);
            ModalBody(TRUE);
            ModalFooter(null);
            break;
        case 'notifications':
            if (Authenticate())
            {
                //Please note: in most tables, datetimes are stored as UTC timestamps (and compared to UTC_TIMESTAMP)
                //Because the notification table has a CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP instruction for the
                //(mandatory) Updated column, comparisons are done using CURRENT_TIMESTAMP instead. We cannot use UTC_TIMESTAMP
                //in the statement to set the default values of the Updated column, so we have to use CURRENT_TIMESTAMP instead.
                //CURRENT_TIMESTAMP uses server local timezone, whatever that is, as opposed to UTC
                //Since there is no need to compare notification times other than for calculating age (and no comparison is needed
                //with external columns or variables), this is the simplest solution. 
                $sql = 
                "SELECT tblnotification.NotificationID, tblnotification.Updated, tblnotification.Type, tblnotification.SeenBefore, 
                        tblnotificationitem.Caption, tblnotificationitem.URL, tblnotificationitem.Script, tblnotificationitem.Target,
                        tblnotificationitem.Icon, 
                        ABS(TIMESTAMPDIFF(SECOND, CURRENT_TIMESTAMP, tblnotification.Updated)) AS `Age`
                 FROM tblnotification
                 INNER JOIN tblnotificationitem ON tblnotificationitem.NotificationID = tblnotification.NotificationID
                 WHERE tblnotification.Token = '".IdentifierStr($AUTHENTICATION['Token'])."'
                 ORDER BY tblnotification.Updated DESC, tblnotification.NotificationID ASC, tblnotificationitem.NotificationItemID ASC
                ";
                $notseen = array();
                if($query = mysqli_query($SYSTEM_SETTINGS['Database'], $sql)) {
                    $currentitem = null;
                    $newitem = null;
                    while ($row = mysqli_fetch_assoc($query)) {
                        if($currentitem <> $row['NotificationID']) {
                            if(!is_null($newitem)) {
                                SBAlert($newitem);
                            }                            
                            $currentitem = $row['NotificationID'];
                            $newitem = array(
                                'type' => $row['Type'],
                                'messages' => array(),
                                'id' => '__notification_'.$row['NotificationID'],
                                'dismiss' => TRUE,
                            );
                            $minago = max(round($row['Age']/60), 1);
                            if($row['Age'] <45) {
                                $newitem['title'] = 'just now';
                            } elseif($minago < 56) {
                                $newitem['title'] = SinPlu($minago, 'minute').' ago';
                            } else {
                                $hourago = max(round($row['Age']/3600), 1);
                                if($hourago < 24) {
                                    $newitem['title'] = SinPlu($hourago, 'hour').' ago';
                                } else {
                                    $daysago = max(round($row['Age']/86400), 1);
                                    $newitem['title'] = SinPlu($daysago, 'day').' ago';
                                }
                            }
                            if ($row['Age'] < 45) {
                                $newitem['new'] = TRUE;
                            }
                            if (empty($row['SeenBefore'])) {
                                if($row['Age'] < 600) {
                                    $newitem['notify'] = TRUE;
                                }
                                $notseen[] = $row['NotificationID'];
                            }
                        }
                        $newitem['messages'][] = array(
                            'caption' => $row['Caption'],
                            'url' => $row['URL'],
                            'script' => $row['Script'],
                            'target' => $row['Target'],
                            'icon' => $row['Icon'],
                        );
                    }
                    if(!is_null($newitem)) {
                        SBAlert($newitem);
                    }
                    if(!empty($notseen)) {
                        $sql = "UPDATE tblnotification SET SeenBefore = 1 WHERE tblnotification.NotificationID IN (".implode(",", $notseen).")";
                        mysqli_query($SYSTEM_SETTINGS['Database'], $sql);
                    }
                    
                }
            }
            break;
        case 'finance_tab':
            switch($_GET['tabid']) {
                case 'tab-invoices':
                    SimpleHeading('Invoices', 4, 'sub', 11);
                    $filters = ParseTableFilters(
                        array(
                            array('getparam' => 'Draft', 'caption' => 'Pro Formas', 'value' => 1, 'radiogroup' => 1),
                            array('getparam' => 'Draft', 'caption' => 'Final Documents', 'value' => 0, 'radiogroup' => 1),
                            array('getparam' => 'Settled', 'caption' => 'Open only', 'value' => 0, 'radiogroup' => 2),
                            array('getparam' => 'Settled', 'caption' => 'Settled only', 'value' => 1, 'radiogroup' => 2),
                            array('getparam' => 'InvoiceType', 'caption' => 'Invoices', 'value' => 'invoice', 'radiogroup' => 3),
                            array('getparam' => 'InvoiceType', 'caption' => 'Credit Notes', 'value' => 'creditnote', 'radiogroup' => 3),
                        ), 
                        array('ClearAll' => TRUE, 'script' => "LoadContent('tab-invoices', '/load.php?%FILTER%', { spinner: true });")
                    );
                    //print_r($filters['menuitems']);
                    $table = array('id' => 'dt_invoices', 'ajaxsrc' => '/datatable.php',
                        'params' => array('inc' => 'dtInvoices.php', 'fnrow' => 'dtGetRow'),
                        'GET' => $filters['paramlist'],
                        'drawcallback' => "dtDefDrawCallBack( oSettings );",
                        'responsive' => TRUE, 'hover' => FALSE, 'striped' => FALSE,
                        'columns' => array(
                            array('caption' => '#', 'fieldname' => 'InvoiceID', 'width' => '7em', 'textalign' => 'center', 'hide' => array('xs')),
                            array('caption' => 'Type', 'fieldname' => 'InvoiceType', 'width' => '5em', 'textalign' => 'center', 'hide' => array('xs')),
                            array('caption' => 'No', 'fieldname' => 'InvoiceNo', 'textalign' => 'center'),
                            array('caption' => 'To', 'fieldname' => 'InvoiceTo', 'hide' => array('xs', 'sm')),
                            array('caption' => 'Date', 'fieldname' => 'InvoiceDate', 'width' => '7em'),
                            array('caption' => 'Due', 'fieldname' => 'InvoiceDue', 'width' => '7em', 'hide' => array('xs', 'sm', 'md')),
                            array('caption' => 'Status', 'fieldname' => 'StatusText', 'width' => '5em'),
                            array('caption' => 'Total', 'fieldname' => 'Total', 'textalign' => 'right', 'width' => '8em'),
                            array('caption' => 'Actions', 'searchable' => FALSE, 'textalign' => 'center', 'sortable' => FALSE)
                        ),
                        'sortby' => array('column' => 'InvoiceDate', 'direction' => 'desc')
                    );
                    LoadDatatable($table, array(), 11,
                        defaultTablemenu($table, array('export' => TRUE, 'merge' => FALSE, 'bulkemail' => FALSE, 'addtogroup' => FALSE, 'additems' => $filters['menuitems'])), TRUE
                    );
//                    Datatable($table, array(), 11);
//                    jsInitDatatable($table, TRUE, 11);
                    break;
                case 'tab-directdebit':
                    SimpleHeading('Direct Debit Jobs', 4, 'sub', 11);
                    CheckTZSupport(TRUE, 11);
                    Div(array('class' => 'add-bottom-margin-std'), 11);
                    $buttongroup = array();
                    $buttongroup[] = array('icon' => 'fa-plus-square', 'caption' => 'Create new Job', 'iconalign' => 'left', 'type' => 'button', 'script' => "OpenDialog( 'newddjob', { large: true } )", 'colour' => 'success', 'sizeadjust' => 1);
                    ButtonGroup($buttongroup, FALSE, null, 12);
                    Div(null, 11);
                    $table = array('id' => 'dt_ddjobs', 'ajaxsrc' => '/datatable.php',
                        'params' => array('inc' => 'dtDDJobs.php', 'fnrow' => 'dtGetRow'),
                        'GET' => array(),
                        'drawcallback' => "dtDefDrawCallBack( oSettings );",
                        'responsive' => TRUE, 'hover' => FALSE, 'striped' => FALSE,
                        'columns' => array(
                            array('caption' => '', 'width' => '1.5em', 'sortable' => FALSE, 'searchable' => FALSE, 'textalign' => 'center', 'hide' => array('xs')),
                            array('caption' => '#', 'fieldname' => 'DirectDebitJobID', 'width' => '4em', 'textalign' => 'center', 'hide' => array('xs', 'sm')),
                            array('caption' => 'Description', 'fieldname' => 'Description'),
                            array('caption' => 'Owner', 'fieldname' => '_Sortname', 'hide' => array('xs')),
                            array('caption' => 'Items', 'fieldname' => 'Items', 'searchable' => FALSE, 'sortable' => FALSE),
                            array('caption' => 'Status', 'fieldname' => 'StatusHistory', 'searchable' => FALSE, 'sortable' => FALSE),
                            array('caption' => 'Value', 'fieldname' => 'Total', 'textalign' => 'right', 'width' => '8em'),
                            array('caption' => 'Actions', 'searchable' => FALSE, 'textalign' => 'center', 'sortable' => FALSE)
                        ),
                        'sortby' => array('column' => 'DirectDebitJobID', 'direction' => 'desc')
                    );
                    Datatable($table, array(), 11);
                    jsInitDatatable($table, TRUE, 11);
                    break;
                case 'tab-discounts':
                    SimpleHeading('Discount Codes', 4, 'sub', 11);
                    Div(array('class' => 'add-bottom-margin-std'), 11);
                    $buttongroup = array();
                    $buttongroup[] = array('icon' => 'fa-plus-square', 'caption' => 'New Discount Code', 'iconalign' => 'left', 'type' => 'button', 'script' => "OpenDialog( 'editdiscount', { large: true } )", 'colour' => 'success', 'sizeadjust' => 1);
                    ButtonGroup($buttongroup, FALSE, null, 12);
                    Div(null, 11);
                    $table = array('id' => 'dt_discountcodes', 'ajaxsrc' => '/datatable.php',
                        'params' => array('inc' => 'dtDiscountCodes.php', 'fnrow' => 'dtGetRow'),
                        'GET' => array(),
                        'drawcallback' => "dtDefDrawCallBack( oSettings );",
                        'responsive' => TRUE, 'hover' => FALSE, 'striped' => FALSE,
                        'columns' => array(
                            array('caption' => '#', 'fieldname' => 'DiscountID', 'width' => '4em', 'textalign' => 'center', 'hide' => array('xs', 'sm')),
                            array('caption' => 'Code', 'fieldname' => 'DiscountCode'),
                            array('caption' => 'Description', 'fieldname' => 'Description'),
                            array('caption' => 'Discount', 'fieldname' => 'Discount', 'textalign' => 'center'),
                            array('caption' => 'Invoiced', 'fieldname' => 'InvCount', 'searchable' => FALSE, 'textalign' => 'center'),
                            array('caption' => 'Pending', 'fieldname' => 'Pending', 'searchable' => FALSE, 'textalign' => 'center', 'hide' => array('xs', 'sm')),
                            array('caption' => 'Add.Info', 'sortable' => FALSE),
                            array('caption' => 'Actions', 'searchable' => FALSE, 'textalign' => 'center', 'sortable' => FALSE)
                        ),
                        'sortby' => array('column' => 'DiscountCode', 'direction' => 'asc')
                    );
                    Datatable($table, array(), 11);
                    jsInitDatatable($table, TRUE, 11);
                    break;
                case 'tab-reporting':
                    SimpleHeading('Reports', 4, 'sub', 11);
                    break;
                case 'tab-search':
                    SimpleHeading('Search', 4, 'sub', 11);
                    break;
            }
            break;
        case 'finance':
            $menuitems = array();
            stdTitleBlock('Finance', $do, $menuitems, TRUE);
            Div(array('class' => array('block-content')), 8);
                $tabitems = array();
                $tabitems['invoices'] = array('id' => 'invoices', 'icon' => 'fa-file-text-o', 'tooltip' => 'Invoices');
                $tabitems['directdebit'] = array('id' => 'directdebit', 'icon' => 'fa-bank', 'tooltip' => 'Direct Debit');
                $tabitems['discounts'] = array('id' => 'discounts', 'icon' => 'fa-percent', 'tooltip' => 'Discount Codes');
                $tabitems['reporting'] = array('id' => 'reporting', 'icon' => 'fa-lightbulb-o', 'tooltip' => 'Reports');
                $tabitems['search'] = array('id' => 'search', 'icon' => 'fa-search', 'tooltip' => 'Search');
                PrepareTabs($tabitems, (!empty($_GET['activetab']) ? $_GET['activetab'] : null));
                Tabs($tabitems, 9);
                Div(array('class' => 'tab-content'), 9);
                //Invoices
                if (OpenTabContent($tabitems['invoices'], 10)) {
                    CloseTabContent($tabitems['invoices'], 10);
                }
                //DD
                if (OpenTabContent($tabitems['directdebit'], 10)) {
                    CloseTabContent($tabitems['directdebit'], 10);
                }
                //Discount Codes
                if (OpenTabContent($tabitems['discounts'], 10)) {
                    CloseTabContent($tabitems['discounts'], 10);
                }
                //Reports
                if (OpenTabContent($tabitems['reporting'], 10)) {
                    CloseTabContent($tabitems['reporting'], 10);
                }
                //Search
                if (OpenTabContent($tabitems['search'], 10)) {
                    CloseTabContent($tabitems['search'], 10);
                }
                Div(null, 9);
            Div(null, 8); //table content block
            Div(null, 7); //table block            
            break;
        case 'table_quicksearch':
            $menuitems = array();
            $menuitems[] = array('colour' => 'success', 'tooltip' => 'Create a new Person Record', 'caption' => 'New Record', 'script' => "OpenDialog( 'newperson', { large: true } )", 'icon' => 'gi-user_add');
//            $menuitems[] = array('colour' => 'info', 'tooltip' => 'Reload the table from the server', 'caption' => 'Refresh', 'script' => 'RefreshDataTable(dt_quicksearch);', 'icon' => 'fa-refresh');
            stdTitleBlock('Search Results', $do, $menuitems, TRUE);
            Div(array('class' => array('block-content')), 8);
            $table = array('id' => 'dt_search', 'ajaxsrc' => '/datatable.php',
                    'params' => array('inc' => 'dtSearch.php', 'fnrow' => 'dtGetRow'),
                    'GET' => array('queryid'),
                    'drawcallback' => "dtDefDrawCallBack( oSettings );",
                    'description' => 'Quick Search results',
                    'responsive' => TRUE, 'hover' => FALSE, 'striped' => FALSE,
                    'columns' => array(
                        array('caption' => 'Name', 'fieldname' => '_Sortname'),
                        array('caption' => 'DOB', 'fieldname' => 'DOB', 'hide' => array('xs')),
                        array('caption' => 'Gender', 'fieldname' => 'Gender', 'hide' => array('xs', 'sm')),
                        array('caption' => 'Membership', 'fieldname' => 'MSText'),
                        array('caption' => 'Contact Details', 'fieldname' => 'Info', 'sortable' => FALSE),
//                        array('caption' => 'Actions', 'searchable' => FALSE, 'sortable' => FALSE)
                    ),
                    'sortby' => array('column' => '_Sortname', 'direction' => 'asc')
            );
            LoadDatatable($table);
            Div(null, 8); //table content block
            Div(null, 7); //table block
            break;
        case 'searchresults':
            $menuitems = array();
            $menuitems[] = array(
                'group' => 'full', 'id' => 'btnNewSearch', 'colour' => 'info', 'icon' => 'gi-restart', 'tooltip' => 'New Search',
                'url' => "/workspace.php?ws=search",
            );
            if(isset($_GET['queryid'])) {
                $modifyurl = "/workspace.php?ws=search&queryid=".intval($_GET['queryid']);
                $data = GetSessionQueryData($_GET['queryid']);
                if(isset($data['SYSTEM_SOURCE'])) {
                    switch($data['SYSTEM_SOURCE']) {
                        
                    }
                }
                $menuitems[] = array(
                    'group' => 'full', 'id' => 'btnEditSearch', 'colour' => 'info', 'icon' => 'fa-search', 'tooltip' => 'Modify Search',
                    'url' => $modifyurl,
                );
            }
            stdTitleBlock('Search Results', $do, $menuitems, TRUE);
            Div(array('class' => array('block-content', 'add-bottom-margin-std')), 8);
            $table = array('id' => 'dt_search', 'ajaxsrc' => '/datatable.php',
                'params' => array('inc' => 'dtSearch.php', 'fnrow' => 'dtGetRow'),
                'GET' => array('queryid'),
                'drawcallback' => "dtDefDrawCallBack( oSettings );",
                'description' => 'Search results',
                'responsive' => TRUE, 'hover' => FALSE, 'striped' => FALSE,
                'columns' => array(
                    array('caption' => 'Name', 'fieldname' => '_Sortname'),
                    array('caption' => 'DOB', 'fieldname' => 'DOB', 'hide' => array('xs')),
                    array('caption' => 'Gender', 'fieldname' => 'Gender', 'hide' => array('xs', 'sm')),
                    array('caption' => 'Membership', 'fieldname' => 'MSText'),
                    array('caption' => 'Contact Details', 'fieldname' => 'Info', 'sortable' => FALSE),
//                    array('caption' => 'Actions', 'searchable' => FALSE, 'sortable' => FALSE)
                ),
                'sortby' => array('column' => '_Sortname', 'direction' => 'asc')
            );
            LoadDatatable($table);
            Div(null, 8); //table content block
            Div(array(), 8);
            $group = MenuToAdvButtonGroup($menuitems, array('groupfilter' => 'full'));
            AdvButtonGroup($group, array(), 9);                
            Div(null, 8); //table content block
            Div(null, 7); //table block        
            break;
        case 'newsearch':
            //$datasource = (!empty($_GET['queryid']) ? GetSessionQueryData($_GET['queryid']) : array());
            $menuitems = array();
            $menuitems[] = array(
                'group' => 'full', 'id' => 'btnNewSearch', 'colour' => 'info', 'icon' => 'gi-restart', 'tooltip' => 'Reset Search',
                'url' => "/workspace.php?ws=search",
            );
            stdTitleBlock('Search', $do, $menuitems, TRUE);
            Div(array('class' => array('block-content')), 8);
            $tabitems = array();
            $tabitems['qbe'] = array('id' => 'qbe', 'icon' => 'fa-clone', 'tooltip' => 'Query by Example');
            $tabitems['condsearch'] = array('id' => 'condsearch', 'icon' => 'fa-filter', 'tooltip' => 'Conditional Search');
            $tabitems['profilesearch'] = array('id' => 'profilesearch', 'icon' => 'gi-nameplate_alt', 'tooltip' => 'Profile Search');
            PrepareTabs($tabitems, (!empty($_GET['activetab']) ? $_GET['activetab'] : null));
            Tabs($tabitems, 9);
            Div(array('class' => 'tab-content'), 9);
                foreach($tabitems AS $tabitem) {
                    if (OpenTabContent($tabitem, 10)) {
                        CloseTabContent($tabitem, 10);
                    }
                }            
            Div(null, 9);
            Div(null, 8); //content block
            Div(null, 7); //search block            
            break;
        case 'newsearchtab':
            if (CheckRequiredParams(array('tabid' => FALSE), $_GET)) {
                $datasource = (!empty($_GET['queryid']) ? GetSessionQueryData($_GET['queryid']) : array());
                switch($_GET['tabid']) {
                    case 'tab-qbe':
                        $countries = new crmCountries($SYSTEM_SETTINGS['Database']);
                        $genders = new crmGenders(TRUE);
                        $fieldsets = array();
                        $fieldsets[] = array('caption' => 'Record', 'fields' => array(
                            array('name' => 'Title', 'caption' => 'Title', 'kind' => 'control', 'type' => 'string', 'size' => 4),
                            array('name' => 'Firstname', 'caption' => 'First name', 'kind' => 'control', 'type' => 'string', 'size' => 6),
                            array('name' => 'Middlenames', 'caption' => 'Middle name(s)', 'kind' => 'control', 'type' => 'string', 'size' => 6),
                            array('name' => 'Lastname', 'caption' => 'Last name', 'kind' => 'control', 'type' => 'string', 'size' => 6),
                            array('name' => 'Gender', 'caption' => 'Gender', 'kind' => 'control', 'type' => 'advcombo', 'options' => $genders->Genders, 'allowempty' => TRUE, 'size' => 4),
                            array('name' => 'DOB', 'caption' => 'Date of birth', 'kind' => 'control', 'type' => 'date'),
                            array('name' => 'ExtPostnominals', 'caption' => 'Postnominals', 'kind' => 'control', 'type' => 'string', 'size' => 4),
                            array('name' => 'NationalityID', 'caption' => 'Nationality', 'kind' => 'control', 'type' => 'advcombo', 'allowempty' => TRUE, 'size' => 6, 'options' => $countries->Nationalities),
                            array('name' => 'ISO3166', 'caption' => 'Country of Residence', 'kind' => 'control', 'type' => 'advcombo', 'allowempty' => TRUE, 'size' => 6, 'options' => $countries->Countries),
                            array('name' => 'ISO4217', 'caption' => 'Invoicing Currency', 'kind' => 'control', 'type' => 'advcombo', 'allowempty' => TRUE, 'size' => 4, 'options' => $countries->Currencies),
                            array('name' => 'Graduation', 'caption' => 'Graduation', 'kind' => 'control', 'type' => 'date'),
                            array('name' => 'PaidEmployment', 'caption' => 'Paid employment since', 'kind' => 'control', 'type' => 'date'),
                            array('name' => 'Deceased', 'caption' => 'Deceased', 'kind' => 'control', 'type' => 'date'),
                        ));
                        $fieldsets[] = array('caption' => 'Membership', 'fields' => array(
                            array('name' => 'MSNumber', 'caption' => 'MS Number', 'kind' => 'control', 'type' => 'string', 'size' => 4),
                            array('name' => 'MSOldNumber', 'caption' => 'Old MS Number', 'kind' => 'control', 'type' => 'string', 'size' => 4),
                            array('name' => 'MSNextRenewal', 'caption' => 'Next Renewal', 'kind' => 'control', 'type' => 'date'),
                            array('name' => 'MSMemberSince', 'caption' => 'Member Since', 'kind' => 'control', 'type' => 'date'),
                        ));
                        $fieldsets[] = array('caption' => 'Address', 'fields' => array(
                            array('name' => 'Lines', 'caption' => 'Address Lines', 'kind' => 'control', 'type' => 'memo', 'rows' => 4),
                            array('name' => 'Postcode', 'caption' => 'Postcode', 'kind' => 'control', 'type' => 'string'),
                            array('name' => 'Town', 'caption' => 'Town', 'kind' => 'control', 'type' => 'string'),
                            array('name' => 'County', 'caption' => 'County', 'kind' => 'control', 'type' => 'string'),
                            array('name' => 'Region', 'caption' => 'Region', 'kind' => 'control', 'type' => 'string'),
                            array('name' => 'ISO3166_A', 'caption' => 'Country', 'kind' => 'control', 'type' => 'advcombo', 'allowempty' => TRUE, 'options' => $countries->Countries),
                        ));
                        $fieldsets[] = array('caption' => 'Contact', 'fields' => array(
                            array('name' => 'Email', 'caption' => 'Email Address', 'kind' => 'control', 'type' => 'string'),
                            array('name' => 'PhoneNo', 'caption' => 'Phone Number', 'kind' => 'control', 'type' => 'string', 'size' => 4),
                            array('name' => 'URL', 'caption' => 'URL', 'kind' => 'control', 'type' => 'string'),
                        ));                    
                        $formitem = array(
                            'id' => 'frmQBE', 'style' => 'standard', 'spinner' => TRUE,
                            'datasource' => $datasource, 'matchdatasource' => TRUE,
                            'onsubmit' => "submitForm( 'frmQBE', '/syscall.php?do=search', { parseJSON: true, defErrorDlg: true, cbSuccess: function( frmElement, jsonResponse ){ window.location.href='/workspace.php?ws=search&exec=1&queryid='+jsonResponse.queryid } } ); return false;",
                            'buttons' => DefFormButtons("Search"),
                            'fieldsets' => $fieldsets, 'borders' => TRUE,
//                            'onsubmit' => "ExecCalculateMSFee({ formid: 'frmMSFeeCalculator', divid: 'divFeeResults' }); return false;",
                        );
                        Form($formitem);                    
                        break;
                    case 'tab-condsearch':
                        Div(array('id' => 'condcontrols'), 11);
                            $reload = array('do' => $do, 'tabid' => $_GET['tabid']);
                            if(!empty($_GET['queryid'])) {
                                $reload['queryid'] = intval($_GET['queryid']);
                            }
                            $table = array(
                                'prototype' => array(
                                    'edit' => array(
                                        'dlgname' => 'editcondsearchitem',
                                        'params' => (!empty($_GET['queryid']) ? array('queryid' => intval($_GET['queryid'])) : array()), 
                                    ),
/*                                    'sendemail' => array(
                                        'dlgname' => 'sendemailrecord',
                                        'params' => array(), 
                                        'large' => TRUE,
                                    ),*/
                                    'del' => array(
                                        'exec' => 'delcondsearchitem',
                                        'params' => array(), 
                                        'title' => 'Delete Condition',
                                        'message' => 'Are you sure you want to delete this condition?'
                                    ),
                                    'reload' => $reload,
                                ),
                                'header' => FALSE,
                                'striped' => TRUE,
                                'condensed' => TRUE,
                                'borders' => 'none',
                                'responsive' => FALSE,
                                'valign' => 'centre',
                                'margin' => TRUE,
                                'columns' => array(
                                    array(
                                        'field' => array('name' => 'CondSearchTerm', 'type' => 'email'),
                                        'function' => 'stdTableItem'
                                    ),
                                    array(
                                        'field' => array('name' => 'RecordActions', 'type' => 'control'),
                                        'function' => 'stdTableItem'
                                    ),
                                ),
                            );
                            ButtonGroup(stdTableButtons( $table, 
                                array(
                                    'edit' => array('icon' => 'fa-plus-square', 'caption' => 'Add Condition', 'tooltip' => 'Add a new search condition')
                                )), FALSE, null, 15, FALSE);
                        Div(null, 11); //controls block
                        Div(array('id' => 'condtable'), 11);
                            StaticTable($datasource, $table, array(), 12);                        
                        Div(null, 11); //table block                        
                        break;
                }
            }
            break;
        case 'system_settings':
            $menuitems = array();
/*            $menuitems[] = array('colour' => 'success', 'tooltip' => 'Create a new Person Record', 'caption' => 'New Record', 'script' => "OpenDialog( 'newperson', { large: true } )", 'icon' => 'fa-plus-square');
            $menuitems[] = array('colour' => 'info', 'tooltip' => 'Reload the table from the server', 'caption' => 'Refresh', 'script' => 'RefreshDataTable(dt_people);', 'icon' => 'fa-refresh');
            $submenuitems = array();
            $submenuitems[] = array('type' => 'header', 'caption' => 'Change View');
            $submenuitems[] = array('type' => 'item', 'tooltip' => "Include deceased people in the table", 'tooltipalign' => 'left', 'script' => "javascript:void(0)", 'caption' => "Incl. deceased");
            $menuitems[] = array('icon' => 'caret', 'colour' => 'default', 'menuitems' => $submenuitems, 'tooltip' => 'Change view', 'style' => 'alt');*/
            stdTitleBlock('System Settings', $do, $menuitems, TRUE);
            Div(array('class' => 'add-bottom-margin-std'), 8);
            $buttongroup = array();
/*            $buttongroup[] = array(
                'icon' => 'gi-inbox_out', 'caption' => 'Email Queue', 'iconalign' => 'left', 'type' => 'button',
                'script' => "execSyscall('/bg.php?mq=1', { parseJSON: false, defErrorDlg: true, cbPosted: function( options ){ dlgBGProcessStarted('A request has been sent to process the email queue') } } )", 
                'colour' => 'info', 'sizeadjust' => 1
            );*/
            $buttongroup[] = array(
                'icon' => 'hi-play-circle', 'caption' => 'Run Background Processor', 'iconalign' => 'left', 'type' => 'button',
                'script' => "execSyscall('/bg.php?bg=1', { parseJSON: false, defErrorDlg: true, cbPosted: function( options ){ dlgBGProcessStarted('A request has been sent to start the background processor') } } )", 
                'colour' => 'info', 'sizeadjust' => 1
            );
            $buttongroup[] = array(
                'icon' => 'gi-bin', 'caption' => 'Garbage Collection', 'iconalign' => 'left', 'type' => 'button',
                'script' => "execSyscall('/bg.php?gc=1', { parseJSON: false, defErrorDlg: true, cbPosted: function( options ){ dlgBGProcessStarted('A request has been sent to start garbage collection') } } )", 
                'colour' => 'warning', 'sizeadjust' => 1
            );
            $buttongroup[] = array(
                'icon' => 'gi-bin', 'caption' => 'Finance Export', 'iconalign' => 'left', 'type' => 'button',
                'script' => "execSyscall('/bg.php?fe=1', { parseJSON: false, defErrorDlg: true, cbPosted: function( options ){ dlgBGProcessStarted('The Finance Export has been started') } } )", 
                'colour' => 'warning', 'sizeadjust' => 1
            );
            $buttongroup[] = array('icon' => 'gi-inbox', 'caption' => 'Email Queue', 'iconalign' => 'left', 'type' => 'button', 'url' => "/workspace.php?ws=emailqueue", 'style' => 'alt', 'sizeadjust' => 1);
            $buttongroup[] = array('icon' => 'gi-server_flag', 'caption' => 'System Log', 'iconalign' => 'left', 'type' => 'button', 'url' => "/workspace.php?ws=syslog", 'style' => 'alt', 'sizeadjust' => 1);
            ButtonGroup($buttongroup, FALSE, null, 10);
            Div(null, 8);
            
            Div(array('class' => array('block-content')), 8);
                $tabitems = array();
                $tabitems['intro'] = array('id' => 'intro', 'icon' => 'gi-cogwheel', 'tooltip' => 'System Settings');
                $tabitems['general'] = array('id' => 'general', 'icon' => 'gi-circle_info', 'tooltip' => 'General');
                $tabitems['customise'] = array('id' => 'customise', 'icon' => 'fa-tint', 'tooltip' => 'Customise');
                $tabitems['system'] = array('id' => 'system', 'icon' => 'fa-cogs', 'tooltip' => 'System');
                $tabitems['services'] = array('id' => 'services', 'icon' => 'fa-sitemap', 'tooltip' => 'External Services');
                $tabitems['finance'] = array('id' => 'finance', 'icon' => 'gi-calculator', 'tooltip' => 'Finance');
                $tabitems['security'] = array('id' => 'security', 'icon' => 'gi-keys', 'tooltip' => 'Security');
                $tabitems['expiry'] = array('id' => 'expiry', 'icon' => 'gi-bin', 'tooltip' => 'Expiry Policies');
                PrepareTabs($tabitems, (!empty($_GET['activetab']) ? $_GET['activetab'] : null));
                Tabs($tabitems, 9);
                Div(array('class' => 'tab-content'), 9);
                //Intro page
                if (OpenTabContent($tabitems['intro'], 10)) {
                    Para(array('well' => 'small'), 11);
                    echo str_repeat("\t", 12)."This is the System Settings console.";
                    Para(null, 11);
                    CloseTabContent($tabitems['intro'], 10);
                }
                //General settings
                if (OpenTabContent($tabitems['general'], 10)) {
                    CloseTabContent($tabitems['general'], 10);
                }
                //Customisation settings
                if (OpenTabContent($tabitems['customise'], 10)) {
                    CloseTabContent($tabitems['customise'], 10);
                }
                //System settings
                if (OpenTabContent($tabitems['system'], 10)) {
                    CloseTabContent($tabitems['system'], 10);
                }
                //Credentials
                if (OpenTabContent($tabitems['services'], 10)) {
                    CloseTabContent($tabitems['services'], 10);
                }
                //Finance settings
                if (OpenTabContent($tabitems['finance'], 10)) {
                    CloseTabContent($tabitems['finance'], 10);
                }
                //Security settings
                if (OpenTabContent($tabitems['security'], 10)) {
                    CloseTabContent($tabitems['security'], 10);
                }
                //Security settings
                if (OpenTabContent($tabitems['expiry'], 10)) {
                    CloseTabContent($tabitems['expiry'], 10);
                }
                Div(null, 9);
            Div(null, 8); //table content block
            Div(null, 7); //table block            
            break;
        case 'sys_log':
            $menuitems = array();
//            $menuitems[] = array('colour' => 'info', 'tooltip' => 'Reload the table from the server', 'caption' => 'Refresh', 'script' => 'RefreshDataTable(dt_syslog);', 'icon' => 'fa-refresh');
            stdTitleBlock('System Log', $do, $menuitems, TRUE);
            Div(array('class' => 'add-bottom-margin-std'), 8);
            $buttongroup = array();
            $buttongroup[] = array('icon' => 'gi-bin', 'caption' => 'Clear Log', 'iconalign' => 'left', 'type' => 'button', 'script' => "confirmExecSyscall('Clear System Log', 'Are you sure you want to clear the entire system log? This action cannot be undone.', '/syscall.php?do=clearsyslog', { cbSuccess: function(){ RefreshDataTable(dt_syslog); } } ) ", 'colour' => 'danger', 'sizeadjust' => 1);
            $buttongroup[] = array('icon' => 'gi-cogwheel', 'caption' => 'System Settings', 'iconalign' => 'left', 'type' => 'button', 'url' => "/workspace.php?ws=settings", 'style' => 'alt', 'sizeadjust' => 1);
            ButtonGroup($buttongroup, FALSE, null, 10);
            Div(null, 8);
            Div(array('class' => array('block-content')), 8);
            $table = array('id' => 'dt_syslog', 'ajaxsrc' => '/datatable.php',
                    'params' => array('inc' => 'dtSysLog.php', 'fnrow' => 'dtGetRow'),
                    'GET' => array(),
                    'drawcallback' => "dtDefDrawCallBack( oSettings );",
                    'description' => 'System Log',
                    'responsive' => TRUE, 'hover' => FALSE, 'striped' => FALSE,
                    'columns' => array(
                        array('caption' => 'Date/Time', 'fieldname' => 'Recorded'),
                        array('caption' => 'User', 'fieldname' => 'User', 'hide' => array('xs', 'sm')),
                        array('caption' => 'Event', 'fieldname' => 'Caption', 'hide' => array('xs')),
                        array('caption' => 'Description', 'fieldname' => 'Description', 'hide' => array('xs')),
                        array('caption' => 'Actions', 'searchable' => FALSE, 'sortable' => FALSE)
                    ),
                    'sortby' => array('column' => 'Recorded', 'direction' => 'desc')
            );
            LoadDatatable($table, array(), 9,
                defaultTablemenu($table, array('export' => TRUE, 'merge' => FALSE, 'bulkemail' => FALSE, 'addtogroup' => FALSE)), TRUE
            );
            Div(null, 8); //table content block
            Div(null, 7); //table block            
            break;
        case 'etemplates':
            $menuitems = array();
//            $menuitems[] = array('colour' => 'info', 'tooltip' => 'Reload the table from the server', 'caption' => 'Refresh', 'script' => 'RefreshDataTable(dt_syslog);', 'icon' => 'fa-refresh');
            stdTitleBlock('Email Templates', $do, $menuitems, TRUE);
            Div(array('class' => array('block-content')), 8);
            $table = array('id' => 'dt_etemplates', 'ajaxsrc' => '/datatable.php',
                    'params' => array('inc' => 'dtEmailTemplates.php', 'fnrow' => 'dtGetRow'),
                    'GET' => array(),
                    'drawcallback' => "dtDefDrawCallBack( oSettings );",
                    'description' => 'Email Templates', 'initlength' => 50,
                    'responsive' => TRUE, 'hover' => FALSE, 'striped' => FALSE,
                    'columns' => array(
                        array('caption' => 'Link Mnemonic', 'fieldname' => 'Mnemonic', 'hide' => array('xs'), 'width' => '10em'),
                        array('caption' => 'Group', 'fieldname' => 'Group', 'hide' => array('xs', 'sm')),
                        array('caption' => 'Description', 'fieldname' => 'Description', 'width' => '35%'),
                        array('caption' => 'Settings', 'fieldname' => 'Settings', 'searchable' => FALSE, 'sortable' => FALSE, 'width' => '20%'),
                        array('caption' => 'Pr.', 'fieldname' => 'Priority', 'textalign' => 'center', 'width' => '4em', 'searchable' => FALSE, 'hide' => array('xs')),
                        array('caption' => 'Last Modified', 'fieldname' => 'LastModified', 'hide' => array('xs', 'sm', 'md')),
                        array('caption' => 'Actions', 'searchable' => FALSE, 'sortable' => FALSE)
                    ),
                    'sortby' => array(array('column' => 'Group', 'direction' => 'asc'), array('column' => 'Description', 'direction' => 'asc'))
            );
            LoadDatatable($table, array(), 9,
                defaultTablemenu($table, array('export' => TRUE, 'merge' => FALSE, 'bulkemail' => FALSE, 'addtogroup' => FALSE)), TRUE
            );
            Div(null, 8); //table content block
            Div(null, 7); //table block            
            break;
        case 'ptemplates':
            $menuitems = array();
//            $menuitems[] = array('colour' => 'info', 'tooltip' => 'Reload the table from the server', 'caption' => 'Refresh', 'script' => 'RefreshDataTable(dt_syslog);', 'icon' => 'fa-refresh');
            stdTitleBlock('Document Templates', $do, $menuitems, TRUE);
            Div(array('class' => array('block-content')), 8);
            $table = array('id' => 'dt_ptemplates', 'ajaxsrc' => '/datatable.php',
                    'params' => array('inc' => 'dtPaperTemplates.php', 'fnrow' => 'dtGetRow'),
                    'GET' => array(),
                    'drawcallback' => "dtDefDrawCallBack( oSettings );",
                    'description' => 'Document Templates', 'initlength' => 50,
                    'responsive' => TRUE, 'hover' => FALSE, 'striped' => FALSE,
                    'columns' => array(
                        array('caption' => 'Link Mnemonic', 'fieldname' => 'Mnemonic', 'hide' => array('xs'), 'width' => '10em'),
                        array('caption' => 'Group', 'fieldname' => 'Group', 'hide' => array('xs', 'sm')),
                        array('caption' => 'Description', 'fieldname' => 'Description', 'width' => '35%'),
                        array('caption' => 'Settings', 'fieldname' => 'Settings', 'searchable' => FALSE, 'sortable' => FALSE, 'width' => '20%'),
                        array('caption' => 'Last Modified', 'fieldname' => 'LastModified', 'hide' => array('xs', 'sm', 'md')),
                        array('caption' => 'Actions', 'searchable' => FALSE, 'sortable' => FALSE)
                    ),
                    'sortby' => array(array('column' => 'Group', 'direction' => 'asc'), array('column' => 'Description', 'direction' => 'asc'))
            );
            LoadDatatable($table, array(), 9,
                defaultTablemenu($table, array('export' => TRUE, 'merge' => FALSE, 'bulkemail' => FALSE, 'addtogroup' => FALSE)), TRUE
            );
            Div(null, 8); //table content block
            Div(null, 7); //table block            
            break;
        case 'gensettings':
            $menuitems = array();
            stdTitleBlock('General Settings', $do, $menuitems, TRUE);
            Div(array('class' => array('block-content')), 8);
            Div(array('class' => 'row'), 9); //Row 1
            Div(array('class' => 'col-lg-6'), 10);
                SimpleHeading('Committee Roles', 5, 'sub', 14);
                $sql = 
                "SELECT tblcommitteerole.CommitteeRoleID, tblcommitteerole.Role, tblcommitteerole.IsChair
                 FROM tblcommitteerole
                 ORDER BY tblcommitteerole.Role";
                $reftypes = AllRecords($SYSTEM_SETTINGS['Database'], $sql);
                $table = array(
                    'prototype' => array(
                        'edit' => array(
                            'dlgname' => 'editstdrecord', 'fields' => array('CommitteeRoleID' => FALSE, 'Role' => array('type' => 'string', 'caption' => 'Role'), 'IsChair' => array('type' => 'switch', 'caption' => 'Chair')) 
                        ),
                        'del' => array(
                            'exec' => 'delstdrecord',
                            'title' => 'Delete Committee Role',
                            'message' => 'Are you sure you want to delete this Committee Role?'
                        ),
                        'reload' => array('do' => $do, 'divid' => 'wsMain'),
                        'tablename' => 'tblcommitteerole',
                        'title' => 'Committee Role'
                    ),
                    'header' => FALSE, 'striped' => TRUE, 'condensed' => TRUE, 'borders' => 'none', 'responsive' => FALSE, 'valign' => 'centre', 'margin' => TRUE,
                    'columns' => array(
                        array(
                            'field' => array('name' => 'Role', 'type' => 'string'),
                            'function' => 'stdTableItem'
                        ),
                        array(
                            'field' => array('name' => 'Chair', 'type' => 'boolint'),
                            'function' => 'stdTableItem'
                        ),
                        array(
                            'field' => array('name' => 'RecordActions', 'type' => 'control'),
                            'function' => 'stdTableItem', 'caption' => "Actions&#8193;"
                        ),
                    ),
                );
                ButtonGroup(stdTableButtons($table, 
                array(
                    'edit' => array('icon' => 'fa-plus-square', 'caption' => 'Add Committee Role', 'tooltip' => 'Create a new Committee Role')
                )), FALSE, null, 15, FALSE);
                StaticTable($reftypes, $table, array(), 15);                 
                
            Div(null, 10); //Row 1, Left Column
            Div(array('class' => 'col-lg-6'), 10);
                SimpleHeading('Work Roles', 5, 'sub', 14);
                $sql = 
                "SELECT tblworkrole.WorkRoleID, tblworkrole.WorkRole
                 FROM tblworkrole
                 ORDER BY tblworkrole.WorkRole";
                $reftypes = AllRecords($SYSTEM_SETTINGS['Database'], $sql);
                $table = array(
                    'prototype' => array(
                        'edit' => array(
                            'dlgname' => 'editstdrecord', 'fields' => array('WorkRoleID' => FALSE, 'WorkRole' => array('type' => 'string', 'caption' => 'Role')) 
                        ),
                        'del' => array(
                            'exec' => 'delstdrecord',
                            'title' => 'Delete Work Role',
                            'message' => 'Are you sure you want to delete this work role?'
                        ),
                        'reload' => array('do' => $do, 'divid' => 'wsMain'),
                        'tablename' => 'tblworkrole',
                        'title' => 'Work Role'
                    ),
                    'header' => FALSE, 'striped' => TRUE, 'condensed' => TRUE, 'borders' => 'none', 'responsive' => FALSE, 'valign' => 'centre', 'margin' => TRUE,
                    'columns' => array(
                        array(
                            'field' => array('name' => 'WorkRole', 'type' => 'string'),
                            'function' => 'stdTableItem'
                        ),
                        array(
                            'field' => array('name' => 'RecordActions', 'type' => 'control'),
                            'function' => 'stdTableItem', 'caption' => "Actions&#8193;"
                        ),
                    ),
                );
                ButtonGroup(stdTableButtons($table, 
                array(
                    'edit' => array('icon' => 'fa-plus-square', 'caption' => 'Add Work Role', 'tooltip' => 'Create a new Work Role')
                )), FALSE, null, 15, FALSE);
                StaticTable($reftypes, $table, array(), 15);                 
            Div(null, 10); //Row 1, Right Column
            Div(null, 9); //Row 1
            Div(array('class' => 'row'), 9); //Row 2
            Div(array('class' => 'col-lg-6'), 10);
                SimpleHeading('Places of Study', 5, 'sub', 14);
                $sql = 
                "SELECT tblplaceofstudy.PlaceOfStudyID, tblplaceofstudy.PlaceOfStudyDesc
                 FROM tblplaceofstudy
                 ORDER BY tblplaceofstudy.PlaceOfStudyDesc";
                $reftypes = AllRecords($SYSTEM_SETTINGS['Database'], $sql);
                $table = array(
                    'prototype' => array(
                        'edit' => array(
                            'dlgname' => 'editstdrecord', 'fields' => array('PlaceOfStudyID' => FALSE, 'PlaceOfStudyDesc' => array('type' => 'string', 'caption' => 'Place of Study')) 
                        ),
                        'del' => array(
                            'exec' => 'delstdrecord',
                            'title' => 'Delete place of Study',
                            'message' => 'Are you sure you want to delete this place of Study?'
                        ),
                        'reload' => array('do' => $do, 'divid' => 'wsMain'),
                        'tablename' => 'tblplaceofstudy',
                        'title' => 'Place of Study'
                    ),
                    'header' => FALSE, 'striped' => TRUE, 'condensed' => TRUE, 'borders' => 'none', 'responsive' => FALSE, 'valign' => 'centre', 'margin' => TRUE,
                    'columns' => array(
                        array(
                            'field' => array('name' => 'PlaceOfStudyDesc', 'type' => 'string'),
                            'function' => 'stdTableItem'
                        ),
                        array(
                            'field' => array('name' => 'RecordActions', 'type' => 'control'),
                            'function' => 'stdTableItem', 'caption' => "Actions&#8193;"
                        ),
                    ),
                );
                ButtonGroup(stdTableButtons($table, 
                array(
                    'edit' => array('icon' => 'fa-plus-square', 'caption' => 'Add place of Study', 'tooltip' => 'Create a new place of Study')
                )), FALSE, null, 15, FALSE);
                StaticTable($reftypes, $table, array(), 15);                 
            Div(null, 10); //Row 2, Left Column
            Div(array('class' => 'col-lg-6'), 10);
                SimpleHeading('Places of Employment', 5, 'sub', 14);
                $sql = 
                "SELECT tblplaceofwork.*, COUNT(DISTINCT tblchildren.PlaceOfWorkID) AS `ChildCount`,
                        COALESCE(tblparent.PlaceOfWorkOrder, tblplaceofwork.PlaceOfWorkOrder) AS `ListOrder`,
                        MIN(IF(tblplaceofwork.PlaceOfWorkParentID IS NULL, tblrootitems.PlaceOfWorkOrder, tblsameparent.PlaceOfWorkOrder)) AS `MinPlaceOfWorkOrder`,
                        MAX(IF(tblplaceofwork.PlaceOfWorkParentID IS NULL, tblrootitems.PlaceOfWorkOrder, tblsameparent.PlaceOfWorkOrder)) AS `MaxPlaceOfWorkOrder`
                 FROM tblplaceofwork
                 LEFT JOIN tblplaceofwork AS tblchildren ON tblchildren.PlaceOfWorkParentID = tblplaceofwork.PlaceOfWorkID
                 LEFT JOIN tblplaceofwork AS tblparent ON tblparent.PlaceOfWorkID = tblplaceofwork.PlaceOfWorkParentID
                 LEFT JOIN tblplaceofwork AS tblsameparent ON tblsameparent.PlaceOfWorkParentID = tblplaceofwork.PlaceOfWorkParentID
                 LEFT JOIN tblplaceofwork AS tblrootitems ON tblrootitems.PlaceOfWorkParentID IS NULL
                 GROUP BY tblplaceofwork.PlaceOfWorkID
                 ORDER BY ListOrder, tblplaceofwork.PlaceOfWorkParentID, tblplaceofwork.PlaceOfWorkOrder";
                $placesofwork = AllRecords($SYSTEM_SETTINGS['Database'], $sql);
                $table = array(
                    'prototype' => array(
                        'moveup' => array('exec' => 'moveplaceofworkup', 'params' => array(), 'fieldname' => 'PlaceOfWorkOrder'),
                        'movedown' => array('exec' => 'moveplaceofworkdown', 'params' => array(), 'fieldname' => 'PlaceOfWorkOrder'),
                        'edit' => array('dlgname' => 'editplaceofwork'),
                        'insert' => array('dlgname' => 'insertplaceofwork'),
                        'del' => array(
                            'exec' => 'delplaceofwork',
                            'title' => 'Delete place of Employment',
                            'message' => 'Are you sure you want to delete this place of Employment?'
                        ),
                        'reload' => array('do' => $do, 'divid' => 'wsMain'),
                    ),
                    'header' => FALSE, 'striped' => TRUE, 'condensed' => TRUE, 'borders' => 'none', 'responsive' => FALSE, 'valign' => 'centre', 'margin' => TRUE,
                    'columns' => array(
                        array(
                            'field' => array('name' => 'PlaceOfWorkDesc', 'type' => 'string'),
                            'function' => 'stdTableItem', 'caption' => 'Description'
                        ),
                        array(
                            'field' => array('name' => 'PlaceOfWorkActions', 'type' => 'control'),
                            'function' => 'stdTableItem', 'caption' => "Actions&#8193;"
                        ),
                    ),
                );
                ButtonGroup(stdTableButtons($table, 
                array(
                    'edit' => array('icon' => 'fa-plus-square', 'caption' => 'Add Place of Employment to root', 'tooltip' => 'Create a new Place of Employment in the root')
                )), FALSE, null, 15, FALSE);
                StaticTable($placesofwork, $table, array(), 15);                 
            Div(null, 10); //Row 2, Right Column
            Div(null, 9); //Row 2
            Div(array('class' => 'row'), 9); //Row 3
            Div(array('class' => 'col-lg-6'), 10);
                SimpleHeading('Sectors', 5, 'sub', 14);
                $sql = 
                "SELECT tblsector.SectorID, tblsector.SectorName
                 FROM tblsector
                 ORDER BY tblsector.SectorName";
                $reftypes = AllRecords($SYSTEM_SETTINGS['Database'], $sql);
                $table = array(
                    'prototype' => array(
                        'edit' => array(
                            'dlgname' => 'editstdrecord', 'fields' => array('SectorID' => FALSE, 'SectorName' => array('type' => 'string', 'caption' => 'Sector')) 
                        ),
                        'del' => array(
                            'exec' => 'delstdrecord',
                            'title' => 'Delete Sector',
                            'message' => 'Are you sure you want to delete this Sector?'
                        ),
                        'reload' => array('do' => $do, 'divid' => 'wsMain'),
                        'tablename' => 'tblsector',
                        'title' => 'Sector'
                    ),
                    'header' => FALSE, 'striped' => TRUE, 'condensed' => TRUE, 'borders' => 'none', 'responsive' => FALSE, 'valign' => 'centre', 'margin' => TRUE,
                    'columns' => array(
                        array(
                            'field' => array('name' => 'SectorName', 'type' => 'string'),
                            'function' => 'stdTableItem'
                        ),
                        array(
                            'field' => array('name' => 'RecordActions', 'type' => 'control'),
                            'function' => 'stdTableItem', 'caption' => "Actions&#8193;"
                        ),
                    ),
                );
                ButtonGroup(stdTableButtons($table, 
                array(
                    'edit' => array('icon' => 'fa-plus-square', 'caption' => 'Add Sector', 'tooltip' => 'Create a new Sector')
                )), FALSE, null, 15, FALSE);
                StaticTable($reftypes, $table, array(), 15);                 
            Div(null, 10); //Row 3, Left Column
            Div(array('class' => 'col-lg-6'), 10);
            Div(null, 10); //Row 3, Right Column
            Div(null, 9); //Row 3
            break;
        case 'editmsfeedlg':
            ModalHeader('Membership Rate');
            ModalBody(FALSE);
            $fees = new crmMSFees($SYSTEM_SETTINGS['Database'], $SYSTEM_SETTINGS['Membership']['GradeCaption']);
            $fieldsets = array();
            $fields = array();
            $fields[] = array('name' => 'MSGradeID', 'kind' => 'hidden');
            if(isset($_GET['MSFeeID'])) {
                $fee = $fees->GetFeeByID($_GET['MSFeeID']);
                $fields[] = array('name' => 'MSFeeID', 'kind' => 'hidden');
            } else {
                $fee = $fees->FeeTemplate($_GET['MSGradeID']);
            }
            $fields[] = array('name' => 'GradeCaption', 'caption' => $SYSTEM_SETTINGS['Membership']['GradeCaption'], 'kind' => 'static', 'formatting' => '<primary><b>');
            $fields[] = array('name' => 'ValidFrom', 'caption' => 'Valid From', 'kind' => 'control', 'type' => 'date', 'required' => TRUE);
            $fields[] = array('name' => 'ValidUntil', 'caption' => 'Valid Until', 'kind' => 'control', 'type' => 'date');
            foreach($fee['_currencies'] AS $iso4217 => $currency) {
                $field = array('name' => $iso4217, 'caption' => $currency->Currency, 'kind' => 'control', 'type' => 'grid');
                foreach(array('Value1Y' => '1 Year', 'GroupValue1Y' => 'Group', 'Value3Y' => '3 Year') AS $key => $caption) {
                    $field['fields'][] = array(
                        'name' => $key.'_'.$iso4217, 'caption' => $caption, 'type' => 'money', 'currencysymbol' => $currency->Symbol,
                        'rightaddon' => array('type' => 'label', 'caption' => $currency->ISO4217)
                    );
                    if(!empty($fee[$key][$iso4217])) {
                        $fee[$key.'_'.$iso4217] = $fee[$key][$iso4217]['Price']->Net;
                        $fee[$key.'_'.$iso4217.'_MSFeeValueID'] = $fee[$key][$iso4217]['MSFeeValueID'];
                        array_unshift($fields, array('name' => $key.'_'.$iso4217.'_MSFeeValueID', 'kind' => 'hidden'));
                    }
                }
                $fields[] = $field;
            }
            $fieldsets[] = array('fields' => $fields);
            //print_r($fee);
            $formitem = array(
                'id' => 'frmEditMSFee', 'style' => 'standard',
                'datasource' => $fee, 'buttons' => array(),
                'fieldsets' => $fieldsets, 'borders' => FALSE,
            );
            Form($formitem);
            ModalBody(TRUE);
            ModalFooter("frmEditMSFee", "/syscall.php?do=savemsfee", "function( frmElement, jsonResponse ){ RefreshDataTable(dt_msfees); }");
            break;
        case 'mssettings':
            $menuitems = array();
//            $menuitems[] = array('colour' => 'info', 'tooltip' => 'Reload the table from the server', 'caption' => 'Refresh', 'script' => 'RefreshDataTable(dt_syslog);', 'icon' => 'fa-refresh');
            stdTitleBlock('Membership Settings', $do, $menuitems, TRUE);
            Div(array('class' => array('block-content')), 8);
            
            Div(array('class' => 'row'), 9); //Row 1
            Div(array('class' => 'col-lg-6'), 10);
                SimpleHeading('Membership '.$SYSTEM_SETTINGS['Membership']['GradeCaption'], 5, 'sub', 14);
                $sql = 
                "SELECT tblmsgrade.MSGradeID, tblmsgrade.GradeCaption, tblmsgrade.Available, tblmsgrade.AutoElect, tblmsgrade.ApplComponents, tblmsgrade.DisplayOrder,
                        tblmsgrade.IsRetired, tblmsgrade.ApplyOnline, tblmsgrade.GraduationFrom, tblmsgrade.GraduationUntil
                 FROM tblmsgrade
                 ORDER BY tblmsgrade.DisplayOrder";
                $grades = AllRecords($SYSTEM_SETTINGS['Database'], $sql);
                $minmax = CalcMinMax($grades, "DisplayOrder");
                $table = array(
                    'prototype' => array(
                        'moveup' => array('exec' => 'moveup', 'params' => array('MinDisplayOrder' => $minmax['Min'])),
                        'movedown' => array('exec' => 'movedown', 'params' => array('MaxDisplayOrder' => $minmax['Max'])),
                        'edit' => array('dlgname' => 'editgrade'),
                        'reload' => array('do' => $do, 'divid' => 'wsMain'),
                    ),
                    'header' => TRUE, 'striped' => TRUE, 'condensed' => TRUE, 'borders' => 'none', 'responsive' => FALSE, 'valign' => 'centre', 'margin' => TRUE,
                    'columns' => array(
                        array(
                            'field' => array('name' => 'GradeCaption', 'type' => 'string'),
                            'function' => 'stdTableItem', 'caption' => $SYSTEM_SETTINGS['Membership']['GradeCaption']
                        ),
                        array(
                            'field' => array('name' => 'GradeDetails', 'type' => 'string'),
                            'function' => 'stdTableItem'
                        ),
                        array(
                            'field' => array('name' => 'RecordActions', 'type' => 'control'),
                            'function' => 'stdTableItem', 'caption' => "Actions&#8193;"
                        ),
                    ),
                );
                ButtonGroup(stdTableButtons($table, 
                array(
                    'edit' => array('icon' => 'fa-plus-square', 'caption' => 'Add Grade', 'tooltip' => 'Add a new Grade')
                )), FALSE, null, 15, FALSE);
                StaticTable($grades, $table, array(), 15);                
            Div(null, 10); //Row 1, Left Column
            Div(array('class' => 'col-lg-6'), 10);
                //Reserve this block for use of categories in future versions           
            Div(null, 10); //Row 1, Right Column
            Div(null, 9); //Row 1
            Div(array('class' => 'row'), 9); //Row 2
            Div(array('class' => 'col-xs-12'), 10);
                SimpleHeading('Membership Rates', 5, 'sub', 14);
                $filters = ParseTableFilters(
                    array(
                        array('getparam' => 'CurrentTariff', 'caption' => 'Current only', 'value' => 1, 'radiogroup' => 1),
                        array('getparam' => 'Available', 'caption' => 'Available '.ToPlural($SYSTEM_SETTINGS['Membership']['GradeCaption']), 'value' => 1, 'radiogroup' => 2),
                    ), 
                    array('ClearAll' => TRUE, 'Ignore' => 'do', 'script' => "LoadContent('wsMain', '/load.php?%FILTER%', { spinner: false });")
                );
                $table = array('id' => 'dt_msfees', 'ajaxsrc' => '/datatable.php',
                    'params' => array('inc' => 'dtMSFees.php', 'fnrow' => 'dtGetRow'),
                    'GET' => $filters['paramlist'],
                    'drawcallback' => "dtDefDrawCallBack( oSettings );",
                    'description' => 'Membership Rates', 'initlength' => 10,
                    'norecordsmsg' => FmtText("<warning>No fee records found</warning>"),
                    'responsive' => TRUE, 'hover' => FALSE, 'striped' => FALSE,
                    'columns' => array(
                        array('caption' => '#.', 'fieldname' => 'MSFeeID', 'textalign' => 'center', 'width' => '4em', 'hide' => array('xs', 'sm')),
                        array('caption' => $SYSTEM_SETTINGS['Membership']['GradeCaption'], 'fieldname' => 'DisplayOrder'),
                        array('caption' => 'Valid', 'fieldname' => 'ValidFrom', 'textalign' => 'center'),
                        array('caption' => '1 Year', 'fieldname' => 'Value1Y', 'textalign' => 'center'),
                        array('caption' => 'Group', 'fieldname' => 'GroupValue1Y', 'textalign' => 'center'),
                        array('caption' => '3 Year', 'fieldname' => 'Value3Y', 'textalign' => 'center'),
                        array('caption' => 'Actions', 'searchable' => FALSE, 'sortable' => FALSE)
                    ),
                    'sortby' => array('column' => 1, 'direction' => 'asc')
                );
                LoadDatatable($table, array(), 9,
                    defaultTablemenu($table, array('export' => TRUE, 'merge' => FALSE, 'bulkemail' => FALSE, 'addtogroup' => FALSE, 'additems' => $filters['menuitems'])), TRUE
                );
            Div(null, 10);
            Div(null, 9); //Row 2
            Div(array('class' => 'row'), 9); //Row 3
            Div(array('class' => 'col-lg-6'), 10);
                SimpleHeading('Application Stages', 5, 'sub', 14);
                $sql = 
                "SELECT tblapplicationstage.ApplicationStageID, tblapplicationstage.StageOrder, tblapplicationstage.StageName, tblapplicationstage.SubmissionStage,
                        tblapplicationstage.PaymentRequired, tblapplicationstage.StageColour, tblapplicationstage.IsCompletionStage, tblapplicationstage.IsElectionStage,
                        tblapplicationstage.CategorySelector
                 FROM tblapplicationstage 
                 WHERE tblapplicationstage.CategorySelector = 'members'
                 ORDER BY tblapplicationstage.StageOrder";
                $stages = AllRecords($SYSTEM_SETTINGS['Database'], $sql);
                $table = array(
                    'prototype' => array(
                        'edit' => array('dlgname' => 'editstage', 'params' => array('CategorySelector' => 'members')),
                        'del' => array(
                            'exec' => 'delapplstage',
                            'title' => 'Delete Application Stage',
                            'message' => 'Are you sure you want to delete this stage?'
                        ),
                        'reload' => array('do' => $do, 'divid' => 'wsMain'),
                    ),
                    'header' => FALSE, 'striped' => TRUE, 'condensed' => TRUE, 'borders' => 'none', 'responsive' => FALSE, 'valign' => 'centre', 'margin' => TRUE,
                    'columns' => array(
                        array(
                            'field' => array('name' => 'StageOrder', 'type' => 'integer'),
                            'function' => 'stdTableItem', 'textalign' => 'center'
                        ),
                        array(
                            'field' => array('name' => 'StageName', 'type' => 'string'),
                            'function' => 'stdTableItem'
                        ),
                        array(
                            'field' => array('name' => 'StageDetails', 'type' => 'string'),
                            'function' => 'stdTableItem'
                        ),
                        array(
                            'field' => array('name' => 'RecordActions', 'type' => 'control'),
                            'function' => 'stdTableItem', 'caption' => "Actions&#8193;"
                        ),
                    ),
                );
                ButtonGroup(stdTableButtons($table, 
                array(
                    'edit' => array('icon' => 'fa-plus-square', 'caption' => 'Add Stage', 'tooltip' => 'Add a new Stage')
                )), FALSE, null, 15, FALSE);
                StaticTable($stages, $table, array(), 15);                 
            Div(null, 10); //Row 3, Left Column
            Div(array('class' => 'col-lg-6'), 10);
                SimpleHeading('Referee Types', 5, 'sub', 14);
                $sql = 
                "SELECT tblrefereetype.RefereeTypeID, tblrefereetype.RefereeTypeDesc
                 FROM tblrefereetype
                 ORDER BY tblrefereetype.RefereeTypeDesc";
                $reftypes = AllRecords($SYSTEM_SETTINGS['Database'], $sql);
                $table = array(
                    'prototype' => array(
                        'edit' => array(
                            'dlgname' => 'editstdrecord', 'fields' => array('RefereeTypeID' => FALSE, 'RefereeTypeDesc' => array('type' => 'string', 'caption' => 'Description')) 
                        ),
                        'del' => array(
                            'exec' => 'delstdrecord',
                            'title' => 'Delete Referee Type',
                            'message' => 'Are you sure you want to delete this referee type?'
                        ),
                        'reload' => array('do' => $do, 'divid' => 'wsMain'),
                        'tablename' => 'tblrefereetype',
                        'title' => 'Referee Type'
                    ),
                    'header' => FALSE, 'striped' => TRUE, 'condensed' => TRUE, 'borders' => 'none', 'responsive' => FALSE, 'valign' => 'centre', 'margin' => TRUE,
                    'columns' => array(
                        array(
                            'field' => array('name' => 'RefereeTypeDesc', 'type' => 'string'),
                            'function' => 'stdTableItem'
                        ),
                        array(
                            'field' => array('name' => 'RecordActions', 'type' => 'control'),
                            'function' => 'stdTableItem', 'caption' => "Actions&#8193;"
                        ),
                    ),
                );
                ButtonGroup(stdTableButtons($table, 
                array(
                    'edit' => array('icon' => 'fa-plus-square', 'caption' => 'Add Referee Type', 'tooltip' => 'Add a new Referee type')
                )), FALSE, null, 15, FALSE);
                StaticTable($reftypes, $table, array(), 15);                 
            Div(null, 10); //Row 3, Right Column
            Div(null, 9); //Row 3
            Div(array('class' => 'row'), 9); //Row 4
            Div(array('class' => 'col-lg-6'), 10);
            Div(null, 10); //Row 4, Left Column
            Div(array('class' => 'col-lg-6'), 10);
            Div(null, 10); //Row 4, Right Column
            Div(null, 9); //Row 4
            Div(null, 8); //content block
            Div(null, 7); //container block            
            break;
        case 'email_queue':
            $menuitems = array();
            stdTitleBlock('Email Queue', $do, $menuitems, TRUE);
            Div(array('class' => 'add-bottom-margin-std'), 8);
            $buttongroup = array();
            $buttongroup[] = array(
                'icon' => 'gi-inbox_out', 'caption' => 'Process Email Queue', 'iconalign' => 'left', 'type' => 'button',
                'script' => "execSyscall('/bg.php?mq=1', { parseJSON: false, defErrorDlg: true, cbPosted: function( options ){ dlgBGProcessStarted('A request has been sent to process the email queue') } } )", 
                'colour' => 'info', 'sizeadjust' => 1
            );
            $buttongroup[] = array('icon' => 'gi-cogwheel', 'caption' => 'System Settings', 'iconalign' => 'left', 'type' => 'button', 'url' => "/workspace.php?ws=settings", 'style' => 'alt', 'sizeadjust' => 1);
            ButtonGroup($buttongroup, FALSE, null, 10);
            Div(null, 8);
            Div(array('class' => array('block-content')), 8);
            $table = array('id' => 'dt_emailqueue', 'ajaxsrc' => '/datatable.php',
                    'params' => array('inc' => 'dtEmailQueue.php', 'fnrow' => 'dtGetRow'),
                    'GET' => array(),
                    'drawcallback' => "dtDefDrawCallBack( oSettings );",
                    'description' => 'Email Queue', 'initlength' => 50,
                    'norecordsmsg' => FmtText("<warning>No messages found</warning>"),
                    'responsive' => TRUE, 'hover' => FALSE, 'striped' => FALSE,
                    'columns' => array(
                        array('caption' => '#.', 'fieldname' => 'EmailQueueID', 'textalign' => 'center', 'width' => '5em', 'hide' => array('xs', 'sm')),
                        array('caption' => 'Pr.', 'fieldname' => 'Priority', 'textalign' => 'center', 'width' => '4em', 'searchable' => FALSE),
                        array('caption' => 'Queued', 'fieldname' => 'Queued'),
                        array('caption' => 'From', 'fieldname' => 'FromEmail', 'hide' => array('xs')),
                        array('caption' => 'To', 'fieldname' => 'To'),
                        array('caption' => 'Subject', 'fieldname' => 'Subject'),
                        array('caption' => 'Actions', 'searchable' => FALSE, 'sortable' => FALSE)
                    ),
                    'sortby' => array('column' => 'Priority', 'direction' => 'desc')
            );
            LoadDatatable($table, array(), 9, array(
                'processqueue' => array(
                    'icon' => 'gi-inbox_out', 'caption' => 'Process Email Queue',
                    'script' => "execSyscall('/bg.php?mq=1', { parseJSON: false, defErrorDlg: true, cbPosted: function( options ){ dlgBGProcessStarted('A request has been sent to process the email queue') } } )", 
                )), TRUE
            );
            Div(null, 8); //table content block
            Div(null, 7); //table block            
            break;
        case 'recentfiles':
            $menuitems = array();
//            $menuitems[] = array('colour' => 'info', 'tooltip' => 'Reload the table from the server', 'caption' => 'Refresh', 'script' => 'RefreshDataTable(dt_syslog);', 'icon' => 'fa-refresh');
            stdTitleBlock('Recent Files', $do, $menuitems, TRUE);
            Div(array('class' => array('block-content')), 8);
            $table = array('id' => 'dt_recentfiles', 'ajaxsrc' => '/datatable.php',
                'params' => array('inc' => 'dtRecentFiles.php', 'fnrow' => 'dtGetRow'),
                'GET' => array('personid'),
                'drawcallback' => "dtDefDrawCallBack( oSettings );",
                'description' => 'Recent Files',
                'responsive' => TRUE, 'hover' => FALSE, 'striped' => FALSE,
                'columns' => array(
                    array('caption' => 'Updated', 'fieldname' => 'LastModified', 'width' => '9.25em'),
                    array('caption' => 'Document', 'fieldname' => 'DisplayName'),
                    array('caption' => 'Type', 'fieldname' => 'FileType'),
                    array('caption' => 'Actions', 'searchable' => FALSE, 'sortable' => FALSE)
                ),
                'sortby' => array('column' => 'LastModified', 'direction' => 'desc')
            );
            LoadDatatable($table, array(), 9,
                defaultTablemenu($table, array('export' => TRUE, 'merge' => FALSE, 'bulkemail' => FALSE, 'addtogroup' => FALSE)), TRUE
            );
            Div(null, 8); //table content block
            Div(null, 7); //table block            
            break;
            break;
        case 'dplog':
            $menuitems = array();
//            $menuitems[] = array('colour' => 'info', 'tooltip' => 'Reload the table from the server', 'caption' => 'Refresh', 'script' => 'RefreshDataTable(dt_syslog);', 'icon' => 'fa-refresh');
            stdTitleBlock('Data Protection Log', $do, $menuitems, TRUE);
            Div(array('class' => array('block-content')), 8);
            $table = array('id' => 'dt_dplog', 'ajaxsrc' => '/datatable.php',
                    'params' => array('inc' => 'dtDPLog.php', 'fnrow' => 'dtGetRow'),
                    'GET' => array('personid'),
                    'drawcallback' => "dtDefDrawCallBack( oSettings );",
                    'description' => 'Data Protection Log',
                    'responsive' => TRUE, 'hover' => FALSE, 'striped' => FALSE,
                    'columns' => array(
                        array('caption' => 'Recorded', 'fieldname' => 'Recorded'),
                        array('caption' => 'Action', 'fieldname' => 'ActionType', 'hide' => array('xs')),
                        array('caption' => 'Description', 'fieldname' => 'Description'),
                        array('caption' => 'Purpose', 'fieldname' => 'Purpose', 'sortable' => FALSE),
                        array('caption' => 'Closed', 'fieldname' => 'Closed'),
                        array('caption' => 'Actions', 'searchable' => FALSE, 'sortable' => FALSE)
                    ),
                    'sortby' => array('column' => 'Recorded', 'direction' => 'desc')
            );
            LoadDatatable($table, array(), 9,
                defaultTablemenu($table, array('export' => TRUE, 'merge' => FALSE, 'bulkemail' => FALSE, 'addtogroup' => FALSE)), TRUE
            );
            Div(null, 8); //table content block
            Div(null, 7); //table block            
            break;
        case 'sidebar_scheduler':
            $stdMenuitems = array();
            $stdMenuitems[] = array('colour' => 'info', 'script' => "LoadContent('wsSide', '/load.php?do=sidebar_scheduler', { divid: 'sidebar_scheduler', spinner: false } );", 'icon' => 'fa-refresh');
            $stdMenuitems[] = array('colour' => 'info', 'function' => 'toggle', 'icon' => 'fa-arrows-v', 'style' => 'alt');
            stdTitleBlock('Scheduler', $do.'_inner', $stdMenuitems);
            Div(array('class' => array('block-content')), 8);
            $datasource = array();
            foreach(array('bg', 'gc', 'mq') AS $key) {
                $data = LastRun($key);
                $str = "<{$data['colour']}><b>".$data['LastRunDate'].'</b><br><i>'.$data['AgeTxt']."</i></{$data['colour']}>";
                if($key == 'mq') {
                    if($SYSTEM_SETTINGS['System']['Email']['Paused']) {
                        $str .= "<br><{$data['warningcolour']}><b>Warning:</b> The email sending queue is paused.</{$data['warningcolour']}>";
                    }
                    $sql = "SELECT COUNT(EmailQueueID) FROM tblemailqueue";
                    $count = SingleValue($SYSTEM_SETTINGS['Database'], $sql);
                    if($count > 0) {
                        $str .= "<br><{$data['colour']}><b>".SinPlu($count, 'message')."</b> in the queue.</{$data['colour']}>";
                    }
                }
                $datasource[] = array(
                    'caption' => $data['caption'],
                    'value' => FmtText($str) 
                );
            }
            StaticTable($datasource, $summaryTable, array(), 9);            
            Div(null, 8); //table content block
            Div(null, 7); //recent block
            break;            
            break;
        case 'sidebar_syslog':
            $stdMenuitems = array();
            $stdMenuitems[] = array('colour' => 'info', 'function' => 'toggle', 'icon' => 'fa-arrows-v', 'style' => 'alt');
            stdTitleBlock('System Status', $do, $stdMenuitems);
            Div(array('class' => array('block-content')), 8);
            $datasource = array(
                array('caption' => 'Version', 'value' => '0.10.60.4363'),
                array('caption' => 'Compiled', 'value' => '23/05/2017 10:22:34'),
                array('caption' => 'Web Host', 'value' => $SYSTEM_SETTINGS['System']['ThisURL']),
                array('caption' => 'Apache Version', 'value' => apache_get_version()),
                array('caption' => 'PHP Version', 'value' => phpversion()),
                array('caption' => 'MySQL Host', 'value' => mysqli_get_host_info($SYSTEM_SETTINGS['Database'])),
                array('caption' => 'MySQL Protocol', 'value' => mysqli_get_proto_info($SYSTEM_SETTINGS['Database'])),
                array('caption' => 'MySQL Version', 'value' => mysqli_get_server_info($SYSTEM_SETTINGS['Database'])),
/*                    array('caption' => '', 'value' => ''),
                    array('caption' => '', 'value' => ''),
                    array('caption' => '', 'value' => ''),
                    array('caption' => '', 'value' => ''),
                    array('caption' => '', 'value' => ''),
                    array('caption' => '', 'value' => ''),
                    array('caption' => '', 'value' => ''),
                    array('caption' => '', 'value' => ''),
                    array('caption' => '', 'value' => ''),
                    array('caption' => '', 'value' => ''),
                    array('caption' => '', 'value' => ''),
                    array('caption' => '', 'value' => ''),
                    array('caption' => '', 'value' => ''),*/
            );
            $strstats = str_replace('  ', "\n", mysqli_stat($SYSTEM_SETTINGS['Database']));
            $statslines = explode("\n", $strstats);
            $stats = array();
            foreach($statslines AS $line) {
                $keyvalue = explode(":", $line);
                if(count($keyvalue) == 2) {
                    $stats[str_replace(' ', '_', strtolower($keyvalue[0]))] = trim($keyvalue[1]);
                }
            }
            $datasource[] = array('caption' => 'MySQL Uptime', 'value' => secondsToTime($stats['uptime']));
            $datasource[] = array('caption' => 'Questions', 'value' => number_format($stats['questions'], 0, '.', ','));
            $datasource[] = array('caption' => 'Slow queries', 'value' => number_format($stats['slow_queries'], 0, '.', ','));
            $datasource[] = array('caption' => 'Avg. Queries per second', 'value' => number_format($stats['queries_per_second_avg'], 4, '.', ','));
            $sql = "SHOW variables";
            $qry = mysqli_query($SYSTEM_SETTINGS['Database'], $sql);
            if($qry) {
                $vars = array();
                while($row = mysqli_fetch_assoc($qry)) {
                    $vars[$row['Variable_name']] = $row['Value'];
                }
                $datasource[] = array('caption' => 'MySQL Maximum Packet Size', 'value' => FormatFileSize($vars['max_allowed_packet']));
                $datasource[] = array('caption' => 'Group_Concat Max Length', 'value' => FormatFileSize($vars['group_concat_max_len']));
                $datasource[] = array('caption' => 'Storage Engine', 'value' => $vars['storage_engine']);
                if(strcasecmp($vars['storage_engine'], 'InnoDB') == 0) {
                    $datasource[] = array('caption' => 'InnoDB Version', 'value' => $vars['innodb_version']);
                }
                $datasource[] = array('caption' => 'MySQL Timezone', 'value' => $vars['system_time_zone']);
                $datasource[] = array('caption' => 'MySQL Thread Handling', 'value' => $vars['thread_handling']);
            }
            StaticTable($datasource, $summaryTable, array(), 9);
            Div(null, 8); //table content block
            Div(null, 7); //recent block
            break;
        default:
            if(defined('__DEBUGMODE') && __DEBUGMODE)
            {
                SimpleAlertBox('error', 'The requested content cannot be found: '.$do);
            }
    }
}
elseif(defined('__DEBUGMODE') && __DEBUGMODE)
{
    alertNoPermission();    
}

function reloadCall($source) {
    $Result = '';
    if(!empty($source['_reload'])) {
        parse_str($source['_reload'], $params);
        if(!empty($params['tabid']) || !empty($params['divid'])) {
            if(!empty($params['tabid'])) {
                $Result = "function() { LoadContent($('#{$params['tabid']}'), '/load.php?";
            } else {
                $Result = "function() { LoadContent($('#{$params['divid']}'), '/load.php?";
            }
            $count = 0;
            foreach($params AS $key => $value) {
                $Result .= ($count > 0 ? "&" : "").$key."=".rawurlencode($value);
                $count++;
            }
            $Result .= "', { } ); }";
        }
    }
    return $Result;
}

//prototypes is of the form prototype => settings, with settings:
//  icon, caption, tooltip
function stdTableButtons($table, $prototypes = array())
{
    $stdBtnGroup = array();
    foreach($prototypes AS $prototype => $settings) {
        if(isset($table['prototype'][$prototype])) {
            $stdBtnGroup[$prototype] = array(
                'icon' => (isset($settings['icon']) ? $settings['icon'] : DefaultIcon($prototype)),
                'iconalign' => 'left',
                'caption' => $settings['caption'],
                'tooltip' => (!empty($settings['tooltip']) ? $settings['tooltip'] : null),
                'script' => dlgPrototype($table, 'edit', array()),
            );
        }
    }
    return $stdBtnGroup;    
}

function dlgPrototype($table, $prototype, $data = array())
{
    $Result = "javascript:void(0)";
    if(isset($table['prototype'][$prototype])) {
        $pt = $table['prototype'][$prototype];
        $Result = "OpenDialog('{$pt['dlgname']}', { ".(!empty($pt['large']) ? "large:true,": "")." urlparams: {";
        $count = 0;
        foreach($data AS $key => $value) {
            if(!is_null($value)) {
                $Result .= ($count > 0 ? ', ' : '').$key.": ".OutputJSValue($value);
                $count++;
            }
        }
        if(isset($pt['params'])) {
            foreach($pt['params'] AS $key => $value) {
                if(!isset($data[$key])) {
                    $Result .= ($count > 0 ? ', ' : '').$key.": ".OutputJSValue($value);
                    $count++;
                }
            }
        }
        if(!empty($table['prototype']['reload'])) {
            $Result .= ($count > 0 ? ', ' : '')."_reload: ".OutputJSString(http_build_query($table['prototype']['reload']));
            $count++;
        }
        if($pt['dlgname'] === 'editstdrecord') {
            foreach(array('title', 'tablename') AS $key) {
                if(isset($table['prototype'][$key])) {
                    $Result .= ($count > 0 ? ', ' : '')."_{$key}: ".OutputJSString($table['prototype'][$key]);
                    $count++;
                }
            }
            if(!empty($pt['fields'])) {
                $Result .= ($count > 0 ? ', ' : '')."_fields: ".OutputJSString(http_build_query($pt['fields']));
                $count++;
            }
        }
        $Result .= "}, } );";
    }
    return $Result;
}

//Prototype for a confirm + execute process
function execPrototype($table, $prototype, $data = array(), $confirm = TRUE) {
    $Result = "javascript:void(0)";
    if(isset($table['prototype'][$prototype])) {
        $pt = $table['prototype'][$prototype];
        if($confirm) {
            $Result = "bootbox.confirm({ title: ".OutputJSString((!empty($pt['title']) ? $pt['title'] : 'Confirm'))
                    .", message: ".OutputJSString((!empty($pt['message']) ? $pt['message'] : 'Are you sure?'))
                    .", callback: function( result ) { if( result) { ";
        } else {
            $Result = "";
        }
        $count = 0;
        if(is_string($pt)) {
            $Result .= "execSyscall('/syscall.php?do={$pt}', { postparams: { "; 
        } else {
            $Result .= "execSyscall('/syscall.php?do={$pt['exec']}', { postparams: { ";
            if(isset($pt['params'])) {
                foreach($pt['params'] AS $key => $value) {
                    if(!isset($data[$key])) {
                        $Result .= ($count > 0 ? ', ' : '').$key.": ".OutputJSValue($value);
                        $count++;
                    }
                }
            }
            if($pt['exec'] === 'delstdrecord') {
                foreach(array('title', 'tablename') AS $key) {
                    if(isset($table['prototype'][$key])) {
                        $Result .= ($count > 0 ? ', ' : '')."_{$key}: ".OutputJSString($table['prototype'][$key]);
                        $count++;
                    }
                }
            }
        }
        foreach($data AS $key => $value) {
            $Result .= ($count > 0 ? ', ' : '').$key.": ".OutputJSValue($value);
            $count++;
        }
        $Result .= " }, defErrorDlg: true";
        if(!empty($table['prototype']['reload'])) {
            $Result .= ", cbSuccess: ";
            if(!empty($table['prototype']['reload']['tabid']) || !empty($table['prototype']['reload']['divid'])) {
                if(!empty($table['prototype']['reload']['tabid'])) {
                    $Result .= "function() { LoadContent($('#{$table['prototype']['reload']['tabid']}'), '/load.php?";
                } else {
                    $Result .= "function() { LoadContent($('#{$table['prototype']['reload']['divid']}'), '/load.php?";
                }
                $count = 0;
                foreach($table['prototype']['reload'] AS $key => $value) {
                    $Result .= ($count > 0 ? "&" : "").$key."=".rawurlencode($value);
                    $count++;
                }
                $Result .= "', { } ); }";
            }
        }
        $Result .= " } );";
        if($confirm) {
            $Result .= " } } });";
        }
    }
    return $Result;
}

function NoteTableItem($table, $data, $column, $isheader, $sourceindex)
{
    global $SYSTEM_SETTINGS;
    $Result = '';
    if($isheader) {
        $Result = (isset($column['caption']) ? $column['caption'] : "");
    } else {
        switch($column['field']['name']) {
            case 'Avatar':
                if(!empty($data['AuthorID'])) {
                    $Result = "<img class=\"img-circle\" alt=\"".htmlspecialchars($data['AuthorName'])."\" src=\"img/avatar/{$data['AuthorID']}.jpg".($SYSTEM_SETTINGS["System"]["NoAvatarCaching"] ? "?".time() : "")."\" onerror=\"if(this.src != 'img/avatar/avatar_user.png'){this.src = 'img/avatar/avatar_user.png';}\">";
                }
                break;
            case 'Note':
                if(!empty($data['NoteID'])) {
                    $Result  = "<div class=\"note\"><p class=\"author\">";
                    $Result .= "<em><b>".htmlspecialchars($data['AuthorName'])."</b>, ".date('j M Y H:i', strtotime($data['LastModified'].' UTC')).":</em></p><p>".TrimTrailingBreaks($data['NoteText']);
                    $Result .= "</p><p class=\"status\">";
                    $Result .= "<span class=\"smallcaps text-primary\">".$data['CategoryNames']."</span>";
                    $Result .= "</p></div>";
                } else {
                    $Result = $data;
                }
                break;
            case 'NoteActions':
                if(!empty($data['NoteID'])) {
                    $dtBtnGroup = array();
                    $dtBtnGroup[0] = array(
                        'icon' => 'fa-pencil', 'tooltip' => 'Edit this Note', 'type' => 'button', 'colour' => 'info', 'script' => "OpenDialog('editnote', { large: true, urlparams: { NoteID: {$data['NoteID']}, ".(isset($data['PersonID']) ? "PersonID: ".$data['PersonID'] : "OrganisationID: ".$data['OrganisationID'])." } } )"
                    );
                    $dtBtnGroup[1] = array(
                        'icon' => 'fa-times', 'tooltip' => 'Delete this Note', 'type' => 'button', 'colour' => 'danger', 'script' => "execSyscall('/syscall.php?do=delnote', { parseJSON: true, defErrorDlg: true, postparams: { NoteID: {$data['NoteID']} }, cbSuccess: function(){ LoadContent('noteslist', 'load.php?do=reloadnoteslist', { urlparams: { ".(isset($data['PersonID']) ? "personid: ".$data['PersonID'] : "organisationid: ".$data['OrganisationID'])." } } ); } })"
                    );
                    $Result = ButtonGroup($dtBtnGroup, TRUE, null, 11, TRUE);
                }
                break;
        }
    }
    return $Result;
}

function MoniesTableItem($table, $data, $column, $isheader, $sourceindex)
{
    $Result = '';
    if($isheader) {
        $Result = (isset($column['caption']) ? $column['caption'] : "");
    } else {
        $value = (empty($data[$column['field']['name']]) ? "": $data[$column['field']['name']]);
        $colour = (!empty($data['Reversed']) ? 'muted' : ($data['AllocatedAmount'] < 0 ? 'danger' : 'success'));
        switch($column['field']['name'])
        {
            case 'direction':
                $Result = AdvIcon(array(
                    'icon' => (!empty($data['Reversed']) ? 'fa-history': ($data['AllocatedAmount'] < 0 ? 'fa-arrow-circle-o-up' : 'fa-arrow-circle-o-down')),
                    'tooltip' => (!empty($data['Reversed']) ? 'Reversed '.date('j F Y', strtotime($data['Reversed'].' UTC')) : ($data['AllocatedAmount'] < 0 ? 'Money out' : 'Money in')),
                    'ttplacement' => 'top',
                    'colour' => $colour,
                    'fixedwidth' => TRUE,
                ));
                break;
            case 'date':
                $Result = FmtText("<{$colour}>".date('Y-m-d', strtotime($data['Received'].' UTC'))."</{$colour}>");
                break;
            case 'value':
                $Result = FmtText("<{$colour}><b>".ScaledIntegerAsString($data['AllocatedAmount'], "money", 100, FALSE, $data['Symbol'])."</b></{$colour}>") ;
                break;
            case 'info':
                $str = $data['TransactionType'].(!empty($data['TransactionReference']) ? '; Ref: '.$data['TransactionReference'] : '');
                $Result = LinkTo("<{$colour}>{$str}</{$colour}>", array('script' => "OpenDialog('moneyitem', { urlparams: { MoneyID: {$data['MoneyID']} } });", 'urlcolour' => $colour));
                break;
        }
    }
    return $Result;
}

function docTableItem($table, $data, $column, $isheader, $sourceindex)
{
    $Result = '';
    if($isheader) {
        $Result = (isset($column['caption']) ? $column['caption'] : "");
    } else {
        $value = (empty($data[$column['field']['name']]) ? "": $data[$column['field']['name']]);
        switch($column['field']['name'])
        {
            case 'DisplayName':
                $icon = AdvIcon(array(
                    'icon' => $data['StorageIcon'],
                    'colour' => $data['StorageIconColour'],
                    'tooltip' => 'Stored in '.$data['StorageDescription'],
                    'ttplacement' => 'top',
                    'fixedwidth' => TRUE
                ));
                $Result = $icon.'&#8200;'.LinkTo($data['DisplayName'], array('script' => "DownloadDocument({$data['DocumentID']});", 'urlcolour' => $data['TextColour']), $data['ToolTip']);
                break;
            case 'DocumentActions':
                if(!empty($data['DocumentID'])) {
                    $dtBtnGroup = array();
                    $dtBtnGroup[0] = array(
                        'type' => 'button', 'ttplacement' => 'top', 'icon' => $data['DownloadIcon'], 'tooltip' => 'Download this file', 'colour' => 'info',
                        'script' => "DownloadDocument({$data['DocumentID']});"
                    );
                    $dtBtnGroup[1] = array(
                        'type' => 'button', 'ttplacement' => 'top', 'icon' => 'gi-eye_open', 'tooltip' => 'View download log', 'colour' => 'info',
                        'script' => "OpenDialog('downloaddata', { large: false, urlparams: { DocumentID: {$data['DocumentID']} } } );"
                    );
                    $dtBtnGroup[2] = array(
                        'type' => 'button', 'ttplacement' => 'top', 'icon' => 'gi-circle_info', 'tooltip' => 'More information',
                        'colour' => (($data['TextColour'] == 'warning') || ($data['TextColour'] == 'danger') ? $data['TextColour'] : 'info'),
                        'script' => "OpenDialog('docinfo', { large: true, urlparams: { DocumentID: {$data['DocumentID']} } } );"
                    );
                    $dtBtnGroup[3] = array(
                        'type' => 'button', 'ttplacement' => 'top', 'icon' => 'fa-times', 'tooltip' => 'Delete this file', 'colour' => 'danger',
                        'script' => "confirmExecSyscall('Delete Document', '<b>Deleting a document is an action that cannot be undone.</b> Are you sure you want to delete this document?', '/syscall.php?do=deldocument', { parseJSON: true, defErrorDlg: true, postparams: { DocumentID: {$data['DocumentID']} }, cbSuccess: function(){ LoadContent('wsSide', 'load.php?do=sidebar_ddjobfiles', { divid: 'sidebar_ddjobfiles', spinner: true, urlparams: { directdebitjobid: {$data['DirectDebitJobID']} } } ); } });"
                    );
                    $Result = ButtonGroup($dtBtnGroup, FALSE, null, 11, TRUE);
                }
                break;
        }
    }
    return $Result;        
}

function stdTableItem($table, $data, $column, $isheader, $sourceindex)
{
    $Result = '';
    if($isheader) {
        $Result = (isset($column['caption']) ? $column['caption'] : "");
    } else {
        $value = (empty($data[$column['field']['name']]) ? "": $data[$column['field']['name']]);
        //Keep this list alphabetic for ease of editing, leaving actions grouped together at the end
        switch($column['field']['name'])
        {
            case 'Address':
                $Result = "<span class=\"text-info\"><em>".(!empty($data['Title']) ? $data['Title'] : ucfirst($data['AddressType']))."</em></span><br>".AddressToMemo($data);
                break;
            case 'Email':
                if(IsValidEmailAddress($value)) {
                    $colour = 'success';
                    $icon = 'fa-check-circle';
                    $tooltip = 'This is a valid email address';
                } else {
                    $colour = 'danger';
                    $icon = 'fa-exclamation-triangle';
                    $tooltip = 'This email address is not valid!';
                }
                $Result = AdvIcon(array('icon' => $icon, 'colour' => $colour, 'tooltip' => $tooltip, 'ttplacement' => 'top', 'fixedwidth' => TRUE))."&#8200;";
                //file_put_contents("D:\\temp\\data.txt", print_r($data, TRUE));
                if(!empty($table['prototype']['edit']['params']['PersonID'])) {
                    $Result .= LinkTo($value, array('script' => "OpenDialog('".(!empty($table['prototype']['sendemail']['dlgname']) ? $table['prototype']['sendemail']['dlgname'] : 'sendemail')."', { large: true, urlparams: { Email: '{$value}' } });"));
                } else {
                    $Result .= "<a href=\"mailto:{$value}\" class=\"text-{$colour}\">".htmlspecialchars($value)."</a>";
                }
                break;
            case 'GradeCaption':
                if(empty($data['Available'])) {
                    $format = "<muted><em>";
                } else {
                    $format = "<primary><b>";
                }
                $Result = FmtText("{$format}".$data['GradeCaption'].CloseFormattingString($format));
                break;
            case 'GradeDetails':
                $str = '';
                if(empty($data['Available'])) {
                    $format = "<muted><em>";
                    $str = '<danger>Not available</danger>';
                } else {
                    $format = "";
                }
                if(!empty($data['AutoElect'])) {
                    $str .= (empty($str) ? "" : "; ")."Auto-election";
                }

                if(!empty($data['IsRetired'])) {
                    $str .= (empty($str) ? "" : "; ")."Retired";
                }
                $str .= (!empty($data['ApplyOnline']) ? "" : (empty($str) ? "" : "; ")."<warning>Cannot apply online</warning>");
                if(!empty($data['GraduationFrom'])) {
                    $str .= (empty($str) ? "" : "; ")."Graduation from ".$data['GraduationFrom'];
                    if(!empty($data['GraduationUntil'])) {
                        $str .= " until ".$data['GraduationUntil'];
                    }
                } elseif(!empty($data['GraduationUntil'])) {
                    $str .= (empty($str) ? "" : "; ")."Graduation until ".$data['GraduationUntil'];
                }
                $selected = explode(',', $data['ApplComponents']);
                if(!empty($selected)) {
                    $components = new crmApplicationComponents;
                    $str .= (empty($str) ? "" : "; ")."Application: ";
                    $count = 0;
                    foreach($selected AS $component) {
                        $caption = $components->GetCaption($component);
                        $str .= ($count > 0 ? ", " : "").$caption;
                        $count++;
                    }
                } elseif(!empty($data['ApplyOnline'])) {
                    $str .= (empty($str) ? "" : "; ")."<danger>Missing application components</danger>";
                }
                $Result = FmtText("{$format}{$str}".CloseFormattingString($format));
                break;
            case 'PlaceOfWorkDesc':
                $Result = (!empty($data['PlaceOfWorkParentID']) ? "&emsp;&#9492;&ensp;".htmlspecialchars($data['PlaceOfWorkDesc']) : FmtText("<primary><b>{$data['PlaceOfWorkDesc']}</b></primary>"));
                break;
            case 'GrantAwarded':
                $Result = ToolTip(date("j F Y", strtotime($data['Awarded'].'UTC')), AgeText($data['Awarded']), 'left'); 
                break;
            case 'GrantName':
                $Result = (empty($data['Description']) ? FmtText('<info><b>'.$data['Title'].'</b></info>') : ToolTip('<b>'.$data['Title'].'</b>', $data['Description'], 'top', 'info'));
                if(!empty($data['Comment'])) {
                    $Result .= '<br>'.htmlspecialchars($data['Comment']);
                }
                break;
            case 'Group':
                $Result = FmtText('<info><b>'.$data['GroupName'].'</b></info>');
                if(!empty($data['Comment'])) {
                    $Result .= '<br>'.htmlspecialchars($data['Comment']);
                }
                break;
            case 'Online':
                $Result = AdvIcon(array('icon' => $data['CategoryIcon'], 'tooltip' => $data['CategoryName'], 'ttplacement' => 'top', 'fixedwidth' => TRUE))
                        . "&#8200;<a href=\"{$data['URL']}\" target=\"_blank\">".htmlspecialchars($data['URL'])."</a>";
                break;
            case 'PhoneDescription':
                $Result = htmlspecialchars($data['Description']);
                break;
            case 'PhoneNo':
                $Result = AdvIcon(array('icon' => $data['Icon'], 'tooltip' => $data['PhoneType'], 'ttplacement' => 'top', 'fixedwidth' => TRUE))
                        . "&#8200;".htmlspecialchars($data['PhoneNo'])."";
                break;
            case 'StageOrder':
                $Result = $data['StageOrder'].'.';
                break;
            case 'StageName':
                $fmt = '<'.$data['StageColour'].'><b>';
                $Result = FmtText("{$fmt}".$data['StageName'].CloseFormattingString($fmt));
                break;
            case 'StageDetails':
                $Result = '';
                if($data['SubmissionStage'] == 0) {
                    $Result .= (empty($Result) ? "" : "; ")."<b>Submission stage</b>";
                } else {
                    $Result .= (empty($Result) ? "" : "; ").($data['SubmissionStage'] < 0 ? 'Pre' : 'Post')."-submission stage";
                }
                if(!empty($data['PaymentRequired'])) {
                    $Result .= (empty($Result) ? "" : "; ")."Payment required";
                }
                if(!empty($data['IsCompletionStage'])) {
                    $Result .= (empty($Result) ? "" : "; ")."<b>Completion stage</b>";
                }
                if(!empty($data['IsElectionStage'])) {
                    $Result .= (empty($Result) ? "" : "; ")."<b>Election</b>";
                }
                break;
            case 'CondSearchTerm':
                $fields = new crmFields;
                $operators = new crmOperators;
                $field = $fields->Find($data['Fieldname']);
                $operator = $operators->Find($data['Operator']);
                if(!empty($field) && !empty($operator)) {
                    $Result = (!empty($data['ConditionID']) ? strtoupper($data['Logic']).' ' : "").htmlspecialchars($field['caption']).' '.htmlspecialchars($operator['operator']);
                    if($operator['valuetype'] == 'value') {
                        $Result .= ' '.htmlspecialchars($data['SearchValue']);
                    }
                }
                break;
            //ACTIONS section
            case 'AddressActions':
                $dtBtnGroup = array();
                if(empty($data['Indirect'])) {
                    $dtBtnGroup[0] = array(
                        'icon' => 'fa-pencil', 'tooltip' => 'Edit this postal address', 'type' => 'button', 'colour' => 'info', 'script' => dlgPrototype($table, 'edit', $data),
                    );
                    $dtBtnGroup[1] = array(
                        'icon' => 'gi-align_left', 'tooltip' => 'Show as address label', 'type' => 'button', 'colour' => 'info', 'script' => dlgPrototype($table, 'label', $data),
                    );
                    $dtBtnGroup[2] = array(
                        'icon' => 'fa-times', 'tooltip' => 'Delete this postal address', 'type' => 'button', 'colour' => 'danger', 'script' => execPrototype($table, 'del', array_select_key($data, array('AddressID', 'AddressToPersonID', 'AddressToOrganisationID'))),
                    );
                }
                $Result = ButtonGroup($dtBtnGroup, FALSE, null, 11, TRUE);
                break;
            case 'EmailActions':
                $dtBtnGroup = array();
                $dtBtnGroup[0] = array(
                    'icon' => 'fa-pencil', 'tooltip' => 'Edit this email address', 'type' => 'button', 'colour' => 'info', 'script' => dlgPrototype($table, 'edit', $data),
                );
                $dtBtnGroup[1] = array(
                    'icon' => 'gi-message_new', 'tooltip' => 'Send Email', 'type' => 'button', 'colour' => 'primary', 'script' => dlgPrototype($table, 'sendemail', array('Email' => $data['Email'])),
                );
                $dtBtnGroup[2] = array(
                    'icon' => 'fa-times', 'tooltip' => 'Delete this email address', 'type' => 'button', 'colour' => 'danger', 'script' => execPrototype($table, 'del', array('EmailID' => $data['EmailID'], 'Email' => $data['Email'])),
                );
                $Result = ButtonGroup($dtBtnGroup, FALSE, null, 11, TRUE);
                break;
            case 'GrantActions':
                $dtBtnGroup = array();
                $dtBtnGroup[0] = array(
                    'icon' => 'fa-pencil', 'tooltip' => 'Edit this Entry', 'type' => 'button', 'colour' => 'info', 'script' => dlgPrototype($table, 'edit', $data),
                );
                $dtBtnGroup[1] = array(
                    'icon' => 'fa-times', 'tooltip' => 'Delete this Grant record', 'type' => 'button', 'colour' => 'danger', 'script' => execPrototype($table, 'del', array_select_key($data, array('PersonToGrantID'))),
                );
                $Result = ButtonGroup($dtBtnGroup, FALSE, null, 11, TRUE);
                break;
            case 'GroupActions':
                $dtBtnGroup = array();
                $dtBtnGroup[0] = array(
                    'icon' => 'fa-pencil', 'tooltip' => 'Edit this Entry', 'type' => 'button', 'colour' => 'info', 'script' => dlgPrototype($table, 'edit', $data),
                );
                $dtBtnGroup[1] = array(
                    'icon' => 'fa-times', 'tooltip' => 'Remove from Group', 'type' => 'button', 'colour' => 'danger', 'script' => execPrototype($table, 'del', array_select_key($data, array('PersonToPersonGroupID'))),
                );
                $Result = ButtonGroup($dtBtnGroup, FALSE, null, 11, TRUE);
                break;
            case 'OnlineActions':
                $dtBtnGroup = array();
                $dtBtnGroup[0] = array(
                    'icon' => 'fa-pencil', 'tooltip' => 'Edit this online item', 'type' => 'button', 'colour' => 'info', 'script' => dlgPrototype($table, 'edit', $data),
                );
                $dtBtnGroup[1] = array(
                    'icon' => 'fa-times', 'tooltip' => 'Delete this online item', 'type' => 'button', 'colour' => 'danger', 'script' => execPrototype($table, 'del', array_select_key($data, array('CategoryName', 'PersonToOnlineID', 'OrganisationToOnlineID', 'URL'))),
                );
                $Result = ButtonGroup($dtBtnGroup, FALSE, null, 11, TRUE);
                break;
            case 'PhoneActions':
                $dtBtnGroup = array();
                $dtBtnGroup[0] = array(
                    'icon' => 'fa-pencil', 'tooltip' => 'Edit this phone number', 'type' => 'button', 'colour' => 'info', 'script' => dlgPrototype($table, 'edit', $data),
                );
                $dtBtnGroup[1] = array(
                    'icon' => 'fa-times', 'tooltip' => 'Delete this phone number', 'type' => 'button', 'colour' => 'danger', 'script' => execPrototype($table, 'del', array_select_key($data, array('PersonToPhoneID', 'OrganisationToPhoneID', 'PhoneNo'))),
                );
                $Result = ButtonGroup($dtBtnGroup, FALSE, null, 11, TRUE);
                break;
            case 'PlaceOfWorkActions':
                $dtBtnGroup = array();
                $dtBtnGroup[] = array(
                    'icon' => 'gi-up_arrow', 'tooltip' => 'Move this item up', 'type' => 'button', 'colour' => 'info', 'script' => execPrototype($table, 'moveup', $data, FALSE),
                    'disabled' => $data['PlaceOfWorkOrder'] == $data['MinPlaceOfWorkOrder']
                );
                $dtBtnGroup[] = array(
                    'icon' => 'gi-down_arrow', 'tooltip' => 'Move this item up', 'type' => 'button', 'colour' => 'info', 'script' => execPrototype($table, 'movedown', $data, FALSE),
                    'disabled' => $data['PlaceOfWorkOrder'] == $data['MaxPlaceOfWorkOrder'] 
                );
                $dtBtnGroup[] = array(
                    'icon' => 'fa-pencil', 'tooltip' => 'Edit this item', 'type' => 'button', 'colour' => 'info', 'script' => dlgPrototype($table, 'edit', $data),
                );
                $dtBtnGroup[] = array(
                    'icon' => 'fa-plus', 'tooltip' => 'Add a child item', 'type' => 'button', 'colour' => ($data['ChildCount'] > 0 ? 'primary' : 'warning'), 'script' => dlgPrototype($table, 'insert', $data),
                    'disabled' => !empty($data['PlaceOfWorkParentID'])
                );
                $dtBtnGroup[] = array(
                    'icon' => 'fa-times', 'tooltip' => 'Delete this item', 'type' => 'button', 'colour' => 'danger', 'script' => execPrototype($table, 'del', $data, TRUE),
                    'disabled' => !empty($data['ChildCount'])
                );
                
                $Result = ButtonGroup($dtBtnGroup, FALSE, null, 11, TRUE);
                break;
            case 'RecordActions':
                $dtBtnGroup = array();
                if(!empty($table['prototype']['moveup'])) {
                    $fieldname = (isset($table['prototype']['moveup']['fieldname']) ? IdentifierStr($table['prototype']['moveup']['fieldname']) : 'DisplayOrder');
                    $dtBtnGroup[] = array(
                        'icon' => 'gi-up_arrow', 'tooltip' => 'Move this item up', 'type' => 'button', 'colour' => 'info', 'script' => execPrototype($table, 'moveup', $data, FALSE),
                        'disabled' => $data[$fieldname] == (isset($data['Min'.$fieldname]) ? $data['Min'.$fieldname] : $table['prototype']['moveup']['params']['Min'.$fieldname])
                    );
                }
                if(!empty($table['prototype']['movedown'])) {
                    $fieldname = (isset($table['prototype']['moveup']['fieldname']) ? IdentifierStr($table['prototype']['moveup']['fieldname']) : 'DisplayOrder');
                    $dtBtnGroup[] = array(
                        'icon' => 'gi-down_arrow', 'tooltip' => 'Move this item up', 'type' => 'button', 'colour' => 'info', 'script' => execPrototype($table, 'movedown', $data, FALSE),
                        'disabled' => $data[$fieldname] == (isset($data['Max'.$fieldname]) ? $data['Max'.$fieldname] : $table['prototype']['movedown']['params']['Max'.$fieldname]) 
                    );
                }
                if(!empty($table['prototype']['edit'])) {
                    $dtBtnGroup[] = array(
                        'icon' => 'fa-pencil', 'tooltip' => 'Edit this item', 'type' => 'button', 'colour' => 'info', 'script' => dlgPrototype($table, 'edit', $data),
                    );
                }
                if(!empty($table['prototype']['del'])) {
                    $dtBtnGroup[] = array(
                        'icon' => 'fa-times', 'tooltip' => 'Delete this item', 'type' => 'button', 'colour' => 'danger', 'script' => execPrototype($table, 'del', $data, TRUE)
                    );
                }
                $Result = ButtonGroup($dtBtnGroup, FALSE, null, 11, TRUE);
                break;
            //Default for fields is to just show the content
            default:
                if(isset($data[$column['field']['name']])) {
                    $Result = htmlspecialchars($data[$column['field']['name']]);
                }
                break;
                
        }
    }
    return $Result;
}

function committeeTableItem($table, $data, $column, $isheader, $sourceindex)
{
    $Result = '';
    if($isheader) {
        $Result = (isset($column['caption']) ? $column['caption'] : "");
    } else {
        //$value = (empty($data[$column['field']['name']]) ? "": $data[$column['field']['name']]);
        switch($column['field']['name'])
        {
            case 'CommitteeName':
                $fmt = ($data['IsActive'] ? '<b><primary>' : '<b><muted>');
                $Result = FmtText($fmt.$data['CommitteeName'].CloseFormattingString($fmt));
                break;
            case 'Role':
                $fmt = ($data['IsCurrent'] ? ($data['IsChair'] ? '<success>' : '<info>') : '<muted>');
                $Result = FmtText($fmt.$data['Role'].CloseFormattingString($fmt));
                break;
            case 'StartDate':
                if(!empty($data['StartDate'])) {
                    $fmt = ($data['IsActive'] ? '' : '<muted>');
                    $Result = FmtText($fmt.date('j M Y', strtotime($data['StartDate'].' UTC')).CloseFormattingString($fmt));
                }
                break;
            case 'EndDate':
                if(!empty($data['EndDate'])) {
                    $fmt = ($data['IsActive'] ? '' : '<muted>');
                    $Result = FmtText($fmt.date('j M Y', strtotime($data['EndDate'].' UTC')).CloseFormattingString($fmt));
                }
                break;
            case 'Status':
                if($data['IsCurrent']) {
                    $Result = FmtText('<b><info>Current</info></b>');
                } elseif($data['IsActive']) {
                    $Result = FmtText('<info>Active</info>');
                } else {
                    $Result = FmtText('<muted>Ended</muted>');
                }
                break;
            case 'CommitteeActions':
                $dtBtnGroup = array();
                $dtBtnGroup[0] = array(
                    'icon' => 'fa-pencil', 'tooltip' => 'Edit this entry', 'type' => 'button', 'colour' => 'primary', 'script' => dlgPrototype($table, 'edit', array('PersonToCommitteeID' => $data['PersonToCommitteeID'])),
                    'disabled' => !$data['IsActive']
                );
                $dtBtnGroup[1] = array(
                    'icon' => 'fa-level-down', 'tooltip' => 'End Term', 'type' => 'button', 'colour' => 'info', 'script' => dlgPrototype($table, 'endterm', array('PersonToCommitteeID' => $data['PersonToCommitteeID'])),
                    'disabled' => (!$data['IsActive'] || !empty($data['EndDate']))
                );
                $dtBtnGroup[2] = array(
                    'icon' => 'gi-random', 'tooltip' => 'Change Role', 'type' => 'button', 'colour' => 'info', 'script' => dlgPrototype($table, 'changerole', array('PersonToCommitteeID' => $data['PersonToCommitteeID'])),
                    'disabled' => (!$data['IsActive'] || !empty($data['EndDate']))
                );
                $dtBtnGroup[3] = array(
                    'icon' => 'fa-times', 'tooltip' => 'Remove this entry', 'type' => 'button', 'colour' => 'danger', 'script' => execPrototype($table, 'del', array_select_key($data, array('CommitteeName', 'PersonToCommitteeID'))),
                );
                $Result = ButtonGroup($dtBtnGroup, FALSE, null, 11, TRUE);
                break;
        }
    }
    return $Result;
}

/*function frmPersonPersonal(crmPerson $PERSON)
{
    global $SYSTEM_SETTINGS;
    SimpleHeading('Personal Details', 4, 'sub', 11);    
    $personal = $PERSON->GetRecord('personal');
    $fieldsets = array();
    $fieldsets[] = array('fields' => array(
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
        'onsubmit' => "submitForm( 'frmPersonPersonal', '/syscall.php?do=savePersonPersonal', { parseJSON: true, defSuccessDlg: true, defErrorDlg: true } ); return false;",
        'datasource' => $personal, 'buttons' => DefFormButtons("Save Changes"),
        'fieldsets' => $fieldsets, 'borders' => TRUE
    );
    Form($formitem);
    jsFormValidation('frmPersonPersonal');    
}*/

function frmPersonMedia(crmPerson $PERSON)
{
    SimpleHeading('Media', 4, 'sub', 11);
    $personal = $PERSON->GetRecord('personal');
    Div(array('class' => 'row'), 12);
    Div(array('class' => 'col-xs-12'), 13);
    echo str_repeat("\t", 14)."<ul class=\"media-list push\">";
    echo str_repeat("\t", 15)."<li class=\"media\">\n";
    echo str_repeat("\t", 16)."<a class=\"pull-left\" href=\"javascript:void(0)\">\n";
    echo str_repeat("\t", 17)."<img class=\"img-circle\" id=\"previewAvatar\" alt=\"Avatar\" src=\"img/avatar/{$PERSON->PersonID}.jpg\" onerror=\"if(this.src != 'img/avatar/avatar_user.png'){this.src = 'img/avatar/avatar_user.png';}\">";
    echo str_repeat("\t", 16)."</a>\n";
    echo str_repeat("\t", 16)."<div class=\"media-body\">\n";
    echo str_repeat("\t", 17)."<p>\n";
    echo str_repeat("\t", 18)."<b>Avatar</b><br>\n";
    echo str_repeat("\t", 18).Fullname($personal)."\n";    
    echo str_repeat("\t", 17)."</p>\n";
    echo str_repeat("\t", 16)."<div>\n";
    echo str_repeat("\t", 15)."</li>\n";
    echo str_repeat("\t", 14)."</ul>\n";
    Div(null, 13); //col
    Div(null, 12); //row avatar preview
    Div(array('class' => array('row', 'pull-down')), 12);
    Div(array('class' => 'col-xs-12'), 13);
    echo str_repeat("\t", 14)."<div class=\"imageBox\">\n";
    echo str_repeat("\t", 15)."<div class=\"thumbBox\"></div>\n";
    echo str_repeat("\t", 15)."<div class=\"avatarspinner\" style=\"display: none;\">Loading...</div>\n";
    echo str_repeat("\t", 14)."</div>\n";
    echo str_repeat("\t", 14)."<input id=\"avatar-file\" type=\"file\" name=\"avatar-file\" style=\"display:none\">\n";
    $buttongroup = array();
    $buttongroup['browse'] = array('icon' => 'fa-camera', 'iconalign' => 'left', 'caption' => 'Browse...', 'tooltip' => 'Select image to upload', 'type' => 'button', 'script' => "SelectFile();", 'id' => 'file', 'colour' => 'primary');
    $buttongroup['zoomout'] = array('icon' => 'fa-search-minus', 'iconalign' => 'left', 'tooltip' => 'Zoom out', 'type' => 'button', 'id' => 'btnZoomOut', 'colour' => 'info');
    $buttongroup['zoomin'] = array('icon' => 'fa-search-plus', 'iconalign' => 'left', 'tooltip' => 'Zoom in', 'type' => 'button', 'id' => 'btnZoomIn', 'colour' => 'info');
    $buttongroup['crop'] = array('icon' => 'fa-crop', 'iconalign' => 'left', 'caption' => 'Apply', 'tooltip' => 'Crop image and save as avatar', 'type' => 'button', 'id' => 'btnCrop', 'colour' => 'success');
    $buttongroup['clear'] = array('icon' => 'fa-ban', 'iconalign' => 'left', 'tooltip' => 'Clear your Avatar', 'type' => 'button', 'script' => 'ClearAvatar()', 'colour' => 'warning');
    ButtonGroup($buttongroup, FALSE, NULL, 9);
    echo str_repeat("\t", 14)."<div class=\"cropped\">\n";
    echo str_repeat("\t", 14)."</div>\n";
    echo str_repeat("\t", 14)."<p class=\"well well-sm pull-down\">To upload an avatar, click the 'Browse' button to upload an image file. Use the mouse and the zoom buttons to position (by dragging) and resize the image into the highlighted square. Click Apply to upload the selected portion of the image as the new avatar. To delete an avatar, click the clear button.</p>\n";
    echo str_repeat("\t", 14)."<script src=\"js/helpers/cropbox.js\"></script>\n";
    echo str_repeat("\t", 14)."<script src=\"js/avatar.js\"></script>\n";
    Div(null, 13); //col
    Div(null, 12); //row avatar controls
}

function frmOrganisationGeneral(crmOrganisation $ORGANISATION)
{
    global $SYSTEM_SETTINGS;
    SimpleHeading('General Details', 4, 'sub', 11);    
    $general = $ORGANISATION->GetRecord('general');
    $fieldsets = array();
    $fieldsets[] = array('fields' => array(
        array('name' => 'OrganisationID', 'kind' => 'hidden'),
        array('name' => 'Ringgold', 'caption' => 'Ringgold ID', 'kind' => 'control', 'type' => 'string', 'size' => 6, 'rightaddon' => array('type' => 'button', 'colour' => 'primary', 'script' => 'RinggoldLookup();', 'icon' => 'fa-search'), 'hint' => 'Enter a Ringgold ID and click the lookup button to automatically retrieve organisational details'),
        array('name' => 'Name', 'caption' => 'Name', 'kind' => 'control', 'type' => 'string', 'required' => TRUE),
        array('name' => 'VATNumber', 'caption' => 'VAT Number', 'kind' => 'control', 'type' => 'string', 'size' => 4),
        array('name' => 'CharityReg', 'caption' => 'Charity Reg.', 'kind' => 'control', 'type' => 'string', 'size' => 4),    
        array('name' => 'Dissolved', 'caption' => 'Dissolved', 'kind' => 'control', 'type' => 'date', 'readonly' => !empty($general['Dissolved'])),
    ));
    $formitem = array(
        'id' => 'frmOrganisationGeneral', 'style' => 'standard', 'spinner' => TRUE,
        'onsubmit' => "submitForm( 'frmOrganisationGeneral', '/syscall.php?do=saveOrganisationGeneral', { parseJSON: true, defSuccessDlg: true, defErrorDlg: true } ); return false;",
        'datasource' => $general, 'buttons' => DefFormButtons("Save Changes"),
        'fieldsets' => $fieldsets, 'borders' => TRUE
    );
    Form($formitem);
    jsFormValidation('frmOrganisationGeneral');    
}

function ImportWarning($RECORD, $tabs = 12)
{
    $data = $RECORD->GetRecord();
    if(!empty($data['ImportedFrom'])) {
        SimpleAlertBox('info', 'The original record data was imported from '.$data['ImportedFrom'], $tabs);
    }
}

function HistoryTable($RECORD, $tabs = 12)
{
    $table = array('id' => 'dt_record_history', 'ajaxsrc' => '/datatable.php',
        'params' => array('inc' => 'dtRecordHistory.php', 'fnrow' => 'dtGetRow'),
        'GET' => array('personid', 'organisationid'),
        'drawcallback' => "dtDefDrawCallBack( oSettings );",
        'responsive' => TRUE, 'hover' => FALSE, 'striped' => FALSE,
        'columns' => array(
            array('caption' => '', 'fieldname' => 'Icon', 'searchable' => FALSE, 'sortable' => FALSE),
            array('caption' => 'Recorded', 'fieldname' => 'Recorded', 'width' => '9.25em'),
            array('caption' => 'Description', 'fieldname' => 'Description'),
            array('caption' => 'Source', 'fieldname' => 'Source', 'hide' => array('xs'), 'width' => '20em'),
            array('caption' => 'Actions', 'searchable' => FALSE, 'sortable' => FALSE)
        ),
        'sortby' => array('column' => 'Recorded', 'direction' => 'desc')
    );
    Datatable($table, array(), $tabs);
    jsInitDatatable($table, TRUE, $tabs);  
}

function INVTable($RECORD, $tabs = 12)
{
    $data = $RECORD->GetRecord(); 
    $buttonitems = array();
    Div(array('class' => 'add-bottom-margin-std'), $tabs);
    $buttonitems['newinv'] = array(
        'icon' => 'fa-plus-square', 'caption' => 'New Invoice', 'iconalign' => 'left', 'type' => 'button', 'colour' => 'warning', 'tooltip' => 'Raise a manual invoice',
        'disabled' => ($data['AllowInteraction'] ? FALSE : TRUE),
        'script' => "execSyscall('/syscall.php?do=createinvdoc', { parseJSON: true, defErrorDlg: true, postparams: { {$RECORD->IDField}: {$RECORD->IDFieldValue}, InvoiceType: 'invoice', ISO4217: '{$RECORD->ISO4217}' }, cbSuccess: function(response){ window.location.href = response.continueurl } })",
    );
    $buttonitems['newcn'] = array(
        'icon' => 'fa-plus-square', 'caption' => 'New Credit Note', 'iconalign' => 'left', 'type' => 'button', 'colour' => 'warning', 'tooltip' => 'Raise a manual credit note',
        'disabled' => ($data['AllowInteraction'] ? FALSE : TRUE),
        'script' => "execSyscall('/syscall.php?do=createinvdoc', { parseJSON: true, defErrorDlg: true, postparams: { {$RECORD->IDField}: {$RECORD->IDFieldValue}, InvoiceType: 'creditnote', ISO4217: '{$RECORD->ISO4217}' }, cbSuccess: function(response){ window.location.href = response.continueurl } })",
    );
    ButtonGroup($buttonitems, FALSE, NULL, $tabs+1);
    Div(null, $tabs);       
    $table = array(
        'id' => 'dt_record_invoices', 'ajaxsrc' => '/datatable.php',
        'params' => array('inc' => 'dtRecordInvoices.php', 'fnrow' => 'dtGetRow'),
        'GET' => array('personid', 'organisationid'),
        'drawcallback' => "dtDefDrawCallBack( oSettings );",
        'responsive' => TRUE, 'hover' => FALSE, 'striped' => FALSE, 'initlength' => 10,
        'norecordsmsg' => FmtText("<warning>No invoicing documents found</warning>"),
        'columns' => array(
            array('caption' => '#', 'fieldname' => 'InvoiceID', 'width' => '7em', 'textalign' => 'center', 'hide' => array('xs')),
            array('caption' => 'Type', 'fieldname' => 'InvoiceType', 'width' => '5em', 'textalign' => 'center', 'hide' => array('xs')),
            array('caption' => 'No', 'fieldname' => 'InvoiceNo', 'textalign' => 'center'),
            array('caption' => 'Date', 'fieldname' => 'InvoiceDate', 'width' => '7em'),
            array('caption' => 'Due', 'fieldname' => 'InvoiceDue', 'width' => '7em', 'hide' => array('xs', 'sm', 'md')),
            array('caption' => 'Status', 'fieldname' => 'Status', 'width' => '5em'),
            array('caption' => 'Total', 'fieldname' => 'Total', 'textalign' => 'right', 'width' => '8em'),
            array('caption' => 'Actions', 'searchable' => FALSE, 'textalign' => 'center', 'sortable' => FALSE)
        ),
        'sortby' => array('column' => 'ValidFrom', 'direction' => 'asc')
    );
    Datatable($table, array(), $tabs);
    jsInitDatatable($table, TRUE, $tabs);
}

function DDITable($RECORD, $tabs = 12)
{
    $data = $RECORD->GetRecord(); 
    $buttonitems = array();
    Div(array('class' => 'add-bottom-margin-std'), $tabs);
    $buttonitems['add'] = array(
        'icon' => 'fa-plus-square', 'caption' => 'New Instruction', 'iconalign' => 'left', 'type' => 'button', 'colour' => 'success', 'tooltip' => 'Add a new Direct Debit instruction to this record',
        'disabled' => ($data['AllowInteraction'] ? FALSE : TRUE)
    );
    if (is_a($RECORD, 'crmOrganisation')) {
        $buttonitems['add']['script'] = "OpenDialog( 'editddi', { large: true, urlparams: { OrganisationID: {$RECORD->OrganisationID} } } )";
    } elseif(is_a($RECORD, 'crmPerson')) {
        $buttonitems['add']['script'] = "OpenDialog( 'editddi', { large: true, urlparams: { PersonID: {$RECORD->PersonID} } } )";
    }
    ButtonGroup($buttonitems, FALSE, NULL, $tabs+1);
    Div(null, $tabs);       
    $table = array(
        'id' => 'dt_record_ddi', 'ajaxsrc' => '/datatable.php',
        'params' => array('inc' => 'dtRecordDDI.php', 'fnrow' => 'dtGetRow'),
        'GET' => array('personid', 'organisationid'),
        'drawcallback' => "dtDefDrawCallBack( oSettings );",
        'responsive' => TRUE, 'hover' => FALSE, 'striped' => FALSE,
        'norecordsmsg' => FmtText("<warning>No Direct Debit Instructions</warning>"),
        'columns' => array(
            array('caption' => 'Valid From', 'fieldname' => 'ValidFrom', 'hide' => array('xs')),
            array('caption' => 'Type', 'fieldname' => 'Type', 'searchable' => FALSE, 'sortable' => TRUE),
            array('caption' => 'Scope', 'fieldname' => 'Scope', 'searchable' => FALSE, 'sortable' => TRUE),
            array('caption' => 'Status', 'fieldname' => 'Status', 'searchable' => FALSE, 'sortable' => TRUE),
            array('caption' => 'Reference', 'fieldname' => 'FormalDDReference'),
            array('caption' => 'Count', 'fieldname' => 'TransactionCount', 'textalign' => 'center', 'hide' => array('xs', 'sm')),
            array('caption' => 'Last Used', 'fieldname' => 'LastUsed', 'hide' => array('xs', 'sm')),
            array('caption' => 'Actions', 'searchable' => FALSE, 'sortable' => FALSE)
        ),
        'sortby' => array('column' => 'ValidFrom', 'direction' => 'asc')
    );
    Datatable($table, array(), $tabs);
    jsInitDatatable($table, TRUE, $tabs);
}

function DiscountTable($RECORD, $tabs = 12)
{
    $data = $RECORD->GetRecord(); 
    $buttonitems = array();
    Div(array('class' => 'add-bottom-margin-std'), $tabs);
    $buttonitems['add'] = array(
        'icon' => 'fa-plus-square', 'caption' => 'Attach Discount Code', 'iconalign' => 'left', 'type' => 'button', 'colour' => 'success', 'tooltip' => 'Attach a Discount Code to this record',
        'disabled' => ($data['AllowInteraction'] ? FALSE : TRUE)
    );
    if (is_a($RECORD, 'crmOrganisation')) {
        $buttonitems['add']['script'] = "OpenDialog( 'attachdiscount', { large: true, urlparams: { OrganisationID: {$RECORD->OrganisationID} } } )";
    } elseif(is_a($RECORD, 'crmPerson')) {
        $buttonitems['add']['script'] = "OpenDialog( 'attachdiscount', { large: true, urlparams: { PersonID: {$RECORD->PersonID} } } )";
    }
    ButtonGroup($buttonitems, FALSE, NULL, $tabs+1);
    Div(null, $tabs);       
    $table = array(
        'id' => 'dt_record_discounts', 'ajaxsrc' => '/datatable.php',
        'params' => array('inc' => 'dtRecordDiscounts.php', 'fnrow' => 'dtGetRow'),
        'GET' => array('personid', 'organisationid'),
        'drawcallback' => "dtDefDrawCallBack( oSettings );",
        'responsive' => TRUE, 'hover' => FALSE, 'striped' => FALSE,
        'norecordsmsg' => FmtText("<warning>No Discount Codes attached</warning>"),
        'columns' => array(
            array('caption' => 'Code', 'fieldname' => 'DiscountCode', 'sortable' => TRUE),
            array('caption' => 'Description', 'fieldname' => 'Description', 'sortable' => TRUE),
            array('caption' => 'Discount', 'fieldname' => 'Discount', 'sortable' => TRUE, 'searchable' => FALSE),
            array('caption' => 'Count', 'fieldname' => 'RefCount', 'textalign' => 'center', 'sortable' => TRUE, 'searchable' => FALSE, 'hide' => array('xs')),
            array('caption' => 'Add.Info', 'sortable' => FALSE),
            array('caption' => 'Actions', 'searchable' => FALSE, 'sortable' => FALSE)
        ),
        'sortby' => array('column' => 'Code', 'direction' => 'asc')
    );
    Datatable($table, array(), $tabs);
    jsInitDatatable($table, TRUE, $tabs);   
}

function PublicationTable($RECORD, $tabs = 12)
{
    $data = $RECORD->GetRecord();
    $buttonitems = array();
    Div(array('class' => 'add-bottom-margin-std'), $tabs);
    $buttonitems['add'] = array(
        'icon' => 'fa-plus-square', 'caption' => 'Add Subscription', 'iconalign' => 'left', 'type' => 'button', 'colour' => 'success', 'tooltip' => 'Add a subscription to this record',
        'disabled' => ($data['AllowInteraction'] ? FALSE : TRUE)
    );
    if (is_a($RECORD, 'crmOrganisation')) {
        $buttonitems['add']['script'] = "OpenDialog( 'editpubsubscription', { urlparams: { OrganisationID: {$RECORD->OrganisationID} } } )";
    } elseif(is_a($RECORD, 'crmPerson')) {
        $buttonitems['add']['script'] = "OpenDialog( 'editpubsubscription', { urlparams: { PersonID: {$RECORD->PersonID} } } )";
    }
    ButtonGroup($buttonitems, FALSE, NULL, $tabs+1);
    Div(null, $tabs);
    $table = array(
        'id' => 'dt_record_publication', 'ajaxsrc' => '/datatable.php',
        'params' => array('inc' => 'dtRecordPublication.php', 'fnrow' => 'dtGetRow'),
        'GET' => array('personid', 'organisationid'),
        'drawcallback' => "dtDefDrawCallBack( oSettings );",
        'responsive' => TRUE, 'hover' => FALSE, 'striped' => FALSE,
        'columns' => array(
            array('caption' => 'Publication', 'fieldname' => 'Title'),
            array('caption' => 'Type', 'fieldname' => 'Type', 'searchable' => FALSE, 'sortable' => TRUE, 'hide' => array('xs', 'sm')),
            array('caption' => 'Status', 'fieldname' => 'Status'),
            array('caption' => 'Actions', 'searchable' => FALSE, 'sortable' => FALSE)
        ),
        'sortby' => array('column' => 'Title', 'direction' => 'asc')
    );
    Datatable($table, array(), $tabs);
    jsInitDatatable($table, TRUE, $tabs);
}

function WorkflowTable($RECORD, $tabs = 12)
{
    $data = $RECORD->GetRecord();
    $buttonitems = array();
    $buttonitems['add'] = array(
        'icon' => 'fa-gears', 'caption' => 'Add to Workflow', 'iconalign' => 'left', 'type' => 'button', 'colour' => 'primary', 'tooltip' => 'Add this record to a new Workflow',
        'disabled' => ($data['AllowInteraction'] ? FALSE : TRUE)
    );
    if (is_a($RECORD, 'crmOrganisation')) {
        $buttonitems['add']['script'] = "OpenDialog( 'addworkflow', { urlparams: { OrganisationID: {$RECORD->OrganisationID} } } )";
    } elseif(is_a($RECORD, 'crmPerson')) {
        $buttonitems['add']['script'] = "OpenDialog( 'addworkflow', { urlparams: { PersonID: {$RECORD->PersonID} } } )";
    }
    if(is_null($data['WorkflowStatus'])) {
        Para(array('well' => 'small'), $tabs);
        echo str_repeat("\t",$tabs)."This record is not currently included in any workflow.";
        Para(null, $tabs);
        ButtonGroup($buttonitems, FALSE, NULL, $tabs);
    } else {
        Div(array('class' => 'add-bottom-margin-std'), $tabs);
        ButtonGroup($buttonitems, FALSE, NULL, $tabs+1);
        Div(null, $tabs);
        $table = array(
            'id' => 'dt_record_workflow', 'ajaxsrc' => '/datatable.php',
            'params' => array('inc' => 'dtRecordWorkflow.php', 'fnrow' => 'dtGetRow'),
            'GET' => array('personid', 'organisationid'),
            'drawcallback' => "dtDefDrawCallBack( oSettings );",
            'responsive' => TRUE, 'hover' => FALSE, 'striped' => FALSE,
            'columns' => array(
                array('caption' => '#.', 'fieldname' => 'WorkflowItemID', 'textalign' => 'center', 'width' => '4em', 'hide' => array('xs')),
                array('caption' => '', 'fieldname' => 'Priority', 'searchable' => FALSE, 'textalign' => 'center'),
                array('caption' => 'Updated', 'fieldname' => 'LastUpdated', 'width' => '9.25em', 'hide' => array('xs')),
                array('caption' => 'Category', 'fieldname' => 'CategoryNames', 'searchable' => FALSE, 'sortable' => FALSE, 'hide' => array('xs', 'sm')),
                array('caption' => '', 'textalign' => 'center', 'fieldname' => 'Avatar', 'searchable' => FALSE, 'sortable' => FALSE, 'hide' => array('xs', 'sm', 'md')),
                array('caption' => 'Assigned To', 'fieldname' => 'AssignedTo', 'sortable' => FALSE),
                array('caption' => 'Recent', 'fieldname' => 'Recent', 'sortable' => FALSE, 'width' => '25%'),
                array('caption' => 'Actions', 'searchable' => FALSE, 'sortable' => FALSE)
            ),
            'sortby' => array('column' => 'Priority', 'direction' => 'desc')
        );
        Datatable($table, array(), $tabs);
        jsInitDatatable($table, TRUE, $tabs);
    }
}

function NotesList($RECORD, $tabs = 12)
{
    global $SYSTEM_SETTINGS;
    $columns = array(
        array(
            'field' => array('name' => 'Avatar', 'type' => 'control'),
            'function' => 'NoteTableItem', 'textalign' => 'center', 'width' => '8em'
        ),
        array(
            'field' => array('name' => 'Note', 'type' => 'control'),
            'function' => 'NoteTableItem', 'caption' => 'Note'
        ),
    );    
    $columns[] = array(
        'field' => array('name' => 'NoteActions', 'type' => 'control'),
        'function' => 'NoteTableItem'
    );
    $table = array(
        'header' => FALSE,
        'striped' => TRUE,
        'condensed' => TRUE,
        'borders' => 'none',
        'responsive' => FALSE,
        'valign' => 'centre',
        'margin' => TRUE,
        'columns' => $columns
    );
    $notes = new crmNotes($SYSTEM_SETTINGS['Database'], $RECORD);
    $data = $notes->GetNotes($_GET);
    StaticTable($data['notes'], $table, array(), $tabs+1);
    if($notes->NoteCount > 0) {
        Pagination(
            array(
                'size' => 'small',
                'align' => 'right',
                'currentpage' => $data['page'],
                'pagecount' => $data['pagecount'],
                'script' => "LoadContent('noteslist', 'load.php?do=reloadnoteslist', { spinner: false, urlparams: { {$RECORD->IDField}: {$RECORD->IDFieldValue}, page: %page% } } );",
            ),
            $tabs+1
        );
    }
}

function DocumentTable($RECORD, $tabs = 12)
{
    Div(array('class' => 'add-bottom-margin-std'), $tabs);
    $buttongroup = array();
    $buttongroup[] = array('icon' => 'gi-cloud-download', 'caption' => 'Upload Document', 'iconalign' => 'left', 'type' => 'button', 'script' => "$('#upload_file').trigger('click');return false;", 'colour' => 'success', 'sizeadjust' => 1);
    ButtonGroup($buttongroup, FALSE, null, $tabs+1);
    echo str_repeat("\t", $tabs+1)."<input id=\"upload_file\" type=\"file\" name=\"upload_file\" style=\"display:none\">\n";
    echo str_repeat("\t", $tabs+1)."<script type=\"text/javascript\">\n";
    echo str_repeat("\t", $tabs+2)."jQuery(function($) {\n";
    if (is_a($RECORD, 'crmOrganisation')) {
        $str = "OrganisationID: {$RECORD->OrganisationID}";
    } elseif(is_a($RECORD, 'crmPerson')) {
        $str = "PersonID: {$RECORD->PersonID}";
    }
    echo str_repeat("\t", $tabs+3)."AddUploadHandler('upload_file', { maxuploadsize: 16000000, postparams: { $str }, cbSuccess: function(){ RefreshDataTable(dt_record_document); } } );\n";
    echo str_repeat("\t", $tabs+2)."});\n";
    echo str_repeat("\t", $tabs+1)."</script>\n";
    Div(null, $tabs);
    $table = array('id' => 'dt_record_document', 'ajaxsrc' => '/datatable.php',
        'params' => array('inc' => 'dtRecordDocument.php', 'fnrow' => 'dtGetRow'),
        'GET' => array('personid', 'organisationid'),
        'drawcallback' => "dtDefDrawCallBack( oSettings );",
        'responsive' => TRUE, 'hover' => FALSE, 'striped' => FALSE,
        'columns' => array(
            array('caption' => 'Updated', 'fieldname' => 'LastModified', 'width' => '9.25em'),
            array('caption' => 'Document', 'fieldname' => 'DisplayName'),
            array('caption' => 'Type', 'fieldname' => 'FileType'),
            array('caption' => 'Actions', 'searchable' => FALSE, 'sortable' => FALSE)
        ),
        'sortby' => array('column' => 'Priority', 'direction' => 'desc')
    );
    Datatable($table, array(), $tabs);
    jsInitDatatable($table, TRUE, $tabs); 
}

function stdTitleBlock($caption, $id, $menuitems = array(), $full = FALSE) {
    $classes = array('block');
    if($full) {
        $classes[] = 'full';
    }
    Div(array('class' => $classes, 'id' => $id), 7);
    $titleitem = array(
        'caption' => $caption,
        'glyphbuttons' => $menuitems,
        'id' => 'stdTitleBlock'
    );
    BlockTitle($titleitem, 8);
}

function MSTableLink($data, $params)
{
    $script = "LoadContent('msBreakdownList', '/load.php?do=table_membership', { spinner: true, urlparams: { ";
    $count = 0;
    foreach($params AS $paramname => $paramvalue) {
        if(is_null($paramvalue)) {
            $value = $data[$paramname];
        } else {
            $value = $paramvalue;
        }
        $script .= ($count > 0 ? ', ' : '').$paramname.': '.OutputJSValue((is_numeric($value) ? intval($value) : $value));
        $count++;
    }
    $script .= " } })";
    return array('script' => $script);
} 

/*function MSTableLink($data, $caption, $params)
{
    $script = "LoadContent('msBreakdownList', '/load.php?do=table_membership', { spinner: true, urlparams: { ";
    $count = 0;
    foreach($params AS $param) {
        $script .= ($count > 0 ? ', ' : '').$param.': '.OutputJSValue((is_numeric($data[$param]) ? intval($data[$param]) : $data[$param]));
        $count++;
    }
    $script .= " } })";
    return LinkTo($caption, array('script' => $script));
}*/

function ApplicationTableLink($data, $caption, $params)
{
    $script = "LoadContent('msBreakdownList', '/load.php?do=table_applications', { spinner: true, hide: 'msataglance', urlparams: { ";
    $count = 0;
    foreach($params AS $param) {
        $script .= ($count > 0 ? ', ' : '').$param.': '.OutputJSValue((is_numeric($data[$param]) ? intval($data[$param]) : $data[$param]));
        $count++;
    }
    $script .= " } });";
    return LinkTo($caption, array('script' => $script, 'urlcolour' => $data['StageColour']));
}

function stdSidebarMenuitems()
{
    $Result = array();
    $Result[] = array('colour' => 'info', 'function' => 'toggle', 'icon' => 'fa-arrows-v', 'style' => 'alt');
    return $Result;
}


function LoadDatatable($table, $contclasses = array(), $tabs = 9, $menu = TRUE, $replaceStdMenu = FALSE) {
    if(!empty($menu)) {
        if(is_array($menu) && $replaceStdMenu) {
            $table['tablemenu'] = $menu;
        } else {
            $options = array('export' => TRUE, 'merge' => TRUE, 'bulkemail' => TRUE, 'addtogroup' => TRUE);
            if(is_array($menu)) {
                $options['additems'] = $menu;
            }
            $table['tablemenu'] = defaultTablemenu($table, $options);
        }
    }
    Datatable($table, $contclasses, $tabs);
    jsInitDatatable($table, TRUE, 9);
    return;
}

//options
//  export
//  addtogroup
//  bulkemail
//  additems
function defaultTablemenu($datatable, $options = array()) {
    $Result = array(array('icon' => 'fa-refresh', 'caption' => 'Reload table', 'script' => "RefreshDataTable($(this).closest('.dataTables_wrapper').find('table').dataTable());"));
    $export = ((!isset($options['export']) || !empty($options['export'])) && HasPermission('export_table'));
    $merge = ((!isset($options['merge']) || !empty($options['merge'])) && HasPermission('merge_table'));
    $bulkemail = ((!isset($options['bulkemail']) || !empty($options['bulkemail'])) && HasPermission('bulkemail_table'));
    $addtogroup = (!isset($options['addtogroup']) || !empty($options['addtogroup']));
    if($export || $bulkemail || $addtogroup) {
        $Result[] = array('type' => 'divider');
        if($export) {
            $Result['export'] = array(
                'icon' => 'gi-cloud-upload', 'caption' => 'Export table data',
                'script' => "OpenDialog('export_table', { large: true, urlparams: { description: '".(!empty($datatable['description']) ? $datatable['description'] : "")."', request: $('#".$datatable['id']."').dataTable().fnSettings().sAjaxSource+'&sSearch='+$('#".$datatable['id']."').dataTable().fnSettings().oPreviousSearch.sSearch } });"
            );
        }
        if($merge) {
            $Result['merge'] = array(
                'icon' => 'fa-file-pdf-o', 'caption' => 'Document merge',
                'script' => "OpenDialog('export_paper', { large: true, urlparams: { description: '".(!empty($datatable['description']) ? $datatable['description'] : "")."', request: $('#".$datatable['id']."').dataTable().fnSettings().sAjaxSource+'&sSearch='+$('#".$datatable['id']."').dataTable().fnSettings().oPreviousSearch.sSearch } });"
            );
        }
        if($bulkemail) {
            $Result['bulkemail'] = array(
                'icon' => 'gi-message_new', 'caption' => 'Bulk email',
                'script' => "OpenDialog('email_table', { large: true, urlparams: { description: '".(!empty($datatable['description']) ? $datatable['description'] : "")."', request: $('#".$datatable['id']."').dataTable().fnSettings().sAjaxSource+'&sSearch='+$('#".$datatable['id']."').dataTable().fnSettings().oPreviousSearch.sSearch, sourceurl: window.location.href } });"
            );
        }
        if($addtogroup) {
            $Result['addtogroup'] = array('icon' => 'fa-users', 'caption' => 'Add to Group', 'script' => "OpenDialog('table_to_group', { urlparams: { request: $('#".$datatable['id']."').dataTable().fnSettings().sAjaxSource+'&sSearch='+$('#".$datatable['id']."').dataTable().fnSettings().oPreviousSearch.sSearch } });");
        }
    }
    if(!empty($options['additems'])) {
        if(!empty($options['additems'])) {
            $first = reset($options['additems']);
            if($first['type'] == 'item') {
                $Result[] = array('type' => 'divider');
            }
            $Result = array_merge($Result, $options['additems']);
        }
    }
    return $Result;
}

function ModalHeader($title)
{
    echo str_repeat("\t", 6)."<div class=\"modal-header\">\n";
    echo str_repeat("\t", 7)."<button type=\"button\" class=\"close\" data-dismiss=\"modal\" aria-hidden=\"true\">&times;</button>\n";
    echo str_repeat("\t", 7)."<h3 class=\"modal-title\">".FmtText($title)."</h3>\n";
    echo str_repeat("\t", 6)."</div>\n";
}

function ModalBody($closing = FALSE, $errormsg = "The changes have not been saved!", $errorcaption = "Save Error")
{
    if($closing) {
        echo str_repeat("\t", 6)."</div>\n";
    } else {
        echo str_repeat("\t", 6)."<div class=\"modal-body\">\n";
        if(!empty($errormsg)) {
            echo str_repeat("\t", 7)."<div class=\"alert alert-danger alert-dismissable display-none\">\n";
            echo str_repeat("\t", 8)."<button type=\"button\" class=\"close\" data-dismiss=\"alert\" aria-hidden=\"true\">&times;</button>\n";
            echo str_repeat("\t", 8)."<h4><i class=\"fa fa-times-circle\"></i> ".htmlspecialchars($errorcaption)."</h4> ".FmtText($errormsg)."\n";
            echo str_repeat("\t", 7)."</div>\n";
        }
    }
}

//TODO: refactor for ModalFooter to take its parameters from an array, making this cleaner and more versatile

function ModalFooter($formID, $saveurl = '', $cbSuccess = '', $savecaption = "Save changes", $cbPosted = '', $validate = '')
{
    echo str_repeat("\t", 6)."<div class=\"modal-footer\">\n";
    if(!empty($formID)) {
        echo str_repeat("\t", 7)."<button type=\"button\" class=\"btn btn-sm btn-default\" data-dismiss=\"modal\">".(stripos($savecaption, 'cancel') !== FALSE ? 'Abort' : 'Cancel')."</button>\n";
    }
    if(!empty($formID) && !empty($saveurl)) {
        $script = "submitForm('{$formID}', '{$saveurl}', { defErrorDlg: ".(empty($cbPosted) ? "true" : "false").", defSuccessDlg: false, parseJSON: true, modal: true";
        if(!empty($validate)) {
            $script .= ", validate: ".$validate;
        }
        if(!empty($cbSuccess)) {
            $script .= ", cbSuccess: ".$cbSuccess;
        }
        if(!empty($cbPosted)) {
            $script .= ", cbPosted: ".$cbPosted;
        }
        $script .= " } );";
        echo str_repeat("\t", 7)."<button id=\"dlgConfirmationBtnSave\" type=\"button\" class=\"btn btn-sm btn-primary\" onclick=\"{$script}\">".htmlspecialchars($savecaption)."</button>\n";
    } elseif(!empty($saveurl)) {
        echo str_repeat("\t", 7)."<button id=\"dlgConfirmationBtnSave\" type=\"button\" class=\"btn btn-sm btn-primary\" onclick=\"{$saveurl}\">".htmlspecialchars($savecaption)."</button>\n";
    } else {
        //No form, so just present a close button
        echo str_repeat("\t", 7)."<button type=\"button\" class=\"btn btn-sm btn-info\" data-dismiss=\"modal\">Close</button>\n";
    }
    echo str_repeat("\t", 6)."</div>\n";
    if(!empty($formID)) {
        jsFormValidation($formID, TRUE, 6, TRUE);
    }
}

//tabs
//form (array or id)
//url (url to save form to)
//script
//nocancel (default is FALSE)
//savecaption (default is "Save Changes")
//closecaption
//onsuccess
//onpost
//onvalidate
//confirm
//  title
//  message
function NewModalFooter($settings) {
    $tabs = (isset($settings['tabs']) ? intval($settings['tabs']) : 6);
    $formID = (isset($settings['formid']) ? IdentifierStr($settings['formid']) : (isset($settings['form']) ? (is_array($settings['form']) ? IdentifierStr($settings['form']['id']) : IdentifierStr($settings['form'])) : null));
    echo str_repeat("\t", $tabs)."<div class=\"modal-footer\">\n";
    $saveCaption = (!empty($settings['savecaption']) ? PunctuatedTextStr($settings['savecaption']) : "Save changes");
    $onclick = null;
    if(!empty($formID)) {
        //Add cancel/ignore button
        if(empty($settings['nocancel'])) {
            echo str_repeat("\t", $tabs+1)."<button type=\"button\" class=\"btn btn-sm btn-default\" data-dismiss=\"modal\">".(stripos($saveCaption, 'cancel') !== FALSE ? 'Abort' : 'Cancel')."</button>\n";
        }
        if(!empty($settings['url'])) {
            $onclick = "submitForm('{$formID}', '{$settings['url']}', { defErrorDlg: ".(empty($settings['onpost']) ? "true" : "false").", defSuccessDlg: false, parseJSON: true, modal: true";
            if(!empty($settings['onvalidate'])) {
                $onclick .= ", validate: ".$settings['onvalidate'];
            }
            if(!empty($settings['onsuccess'])) {
                $onclick .= ", cbSuccess: ".$settings['onsuccess'];
            }
            if(!empty($settings['onpost'])) {
                $onclick .= ", cbPosted: ".$settings['onpost'];
            }
            $onclick .= " } );";
        }
    } elseif(!empty($settings['url'])) {
        //URL, but no form;
        $onclick = "window.location.href = ".$settings['url'];
    } elseif(!empty($settings['script'])) {
        $onclick = $settings['script'];
    } else {
        //No form and no URL, so just put close button
        $closeCaption = (!empty($settings['closecaption']) ? PunctuatedTextStr($settings['closecaption']) : "Close");
        echo str_repeat("\t", $tabs+1)."<button type=\"button\" class=\"btn btn-sm btn-info\" data-dismiss=\"modal\">".htmlspecialchars($closeCaption)."</button>\n";
    }
    if(!empty($onclick)) {
        if(isset($settings['confirm'])) {
            $str = "bootbox.confirm({ ";
            if(!empty($settings['confirm']['title'])) {
                $str .= "title: ".OutputJSString($settings['confirm']['title']).", ";
            }
            $str .= "message: ".OutputJSString((!empty($settings['confirm']['message']) ? $settings['confirm']['message'] : "Are you sure?")).", ";
            $str .= "callback: function(result){ if( result ){ {$onclick} } }})";
            $onclick = $str;
        }
        echo str_repeat("\t", $tabs+1)."<button id=\"dlgConfirmationBtnSave\" type=\"button\" class=\"btn btn-sm btn-primary\" onclick=\"{$onclick}\">".htmlspecialchars($saveCaption)."</button>\n";
    }
    echo str_repeat("\t", $tabs)."</div>\n";
    if(!empty($formID)) {
        jsFormValidation($formID, TRUE, $tabs);
    }
}

?>