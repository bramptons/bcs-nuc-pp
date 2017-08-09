<?php
require_once("initialise.inc");
require_once("bcswidgets.inc");

ob_start();
?>
<?php
//If an authentication cookie is presented, its token will be checked. If found and still valid, the corresponding user details and permissions will be retrieved.
$PAGETITLE = "";
switch("/workspace.php") {
    case '/workspace.php':
        $WORKSPACE = array(
            'id' => (isset($_GET['ws']) ? IdentifierStr($_GET['ws']) : 'people'),
        );
        $PAGETITLE = ucfirst($WORKSPACE['id']);        
        switch($WORKSPACE['id']){
            
        }
        break;
    case '/record.php':
        $RECORD = array(
            'type' => (isset($_GET['rec']) ? IdentifierStr($_GET['rec']) : ''),
        );
        switch($RECORD['type']) {
            case 'directdebitjob':
                $RECORD['gridleft'] = 'col-lg-9';
                $RECORD['gridright'] = 'col-lg-3';
                break;
            case 'invoice':
                $RECORD['gridleft'] = 'col-lg-8';
                $RECORD['gridright'] = 'col-lg-4';
                break;
            default:
                $RECORD['gridleft'] = 'col-md-8';
                $RECORD['gridright'] = 'col-md-4';
        }
        $PAGETITLE = ucfirst($RECORD['type']);
        break;
    case '/full.php':
        $PAGELOADER = array(
            'id' => (isset($_GET['page']) ? IdentifierStr($_GET['page']) : 'editor'),
        );
        $PAGETITLE = ucfirst($PAGELOADER['id']);        
        break;
}
Authenticate();
if(!$AUTHENTICATION['Authenticated'] && (strpos('', 'AllowGuest') === FALSE))
{
    header('Location: https://'.$_SERVER['HTTP_HOST'].'/login.php?redirect=/workspace.php'.(empty($_SERVER['QUERY_STRING']) ? "" : "&".$_SERVER['QUERY_STRING']), TRUE, 307);
    die();
}
?>
<!DOCTYPE html>
<!--[if IE 8]>         <html class="no-js lt-ie9" lang="en-GB"> <![endif]-->
<!--[if gt IE 8]><!--> <html class="no-js" lang="en-GB"> <!--<![endif]-->
	<head>
		<meta charset="UTF-8">    
		<meta http-equiv="Content-Type" content="text/html;charset=utf-8">
		<title><?php echo (!empty($PAGETITLE) ? $PAGETITLE : ""); ?></title>
		<meta name="description" content="">
		<meta name="author" content="Guido Gybels">
		<meta name="robots" content="">
		<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1.0">

		<link rel="icon" href="/img/favicon.ico">
		<link rel="apple-touch-icon" href="/img/icon57.png" sizes="57x57">
		<link rel="apple-touch-icon" href="/img/icon72.png" sizes="72x72">
		<link rel="apple-touch-icon" href="/img/icon76.png" sizes="76x76">
		<link rel="apple-touch-icon" href="/img/icon114.png" sizes="114x114">
		<link rel="apple-touch-icon" href="/img/icon120.png" sizes="120x120">
		<link rel="apple-touch-icon" href="/img/icon144.png" sizes="144x144">
		<link rel="apple-touch-icon" href="/img/icon152.png" sizes="152x152">

		<link rel="stylesheet" href="/css/bootstrap.min.css">
		<link rel="stylesheet" href="/css/plugins.css">
		<link rel="stylesheet" href="/css/main.css">
		<link rel="stylesheet" href="/css/bcs.css">
		<link rel="stylesheet" href="/css/themes.css">
		<link rel="stylesheet" href="/css/fullcalendar.css">

		<script src="/js/vendor/modernizr-2.7.1-respond-1.4.2.min.js"></script>
		<script src="/js/moment.min.js"></script>

	</head>
	<body>
<?php if(file_exists(CONSTLocalStorageRoot."googletagmanager.txt")){ echo file_get_contents(CONSTLocalStorageRoot."googletagmanager.txt"); } ?>
		


<?php
require_once('ccontrols.inc');
?>
		<div id="page-container" class="sidebar-partial sidebar-visible-lg sidebar-no-animations footer-fixed header-fixed-top">
			<!-- Main Sidebar -->
<?php
require_once("sidebar.inc");
?>
			<div id="sidebar">
				<!-- Wrapper for scrolling functionality -->
				<div class="sidebar-scroll">
					<!-- Sidebar Content -->
					<div class="sidebar-content">
<?php if($SYSTEM_SETTINGS["Customise"]["SidebarLogo"]): ?>
                        <div class="sidebar-logo clearfix">
                            <a href="<?php echo $SYSTEM_SETTINGS["General"]["Website"]; ?>" target="_blank">
                                <img id="corpLogo" src="<?php echo $SYSTEM_SETTINGS["Customise"]["Logos"]["Sidebar"]; ?>" alt="<?php echo 'logo for '.$SYSTEM_SETTINGS["General"]["OrgLongName"]; ?>">
                            </a>
                        </div><!-- END Logo -->
<?php else: ?>
                        <!-- Brand -->
						<a href="/index.php" class="sidebar-brand">
							Nucleus
						</a><!-- END Brand -->
<?php endif; ?>
                        
                        <!-- User Info -->
                        <div class="sidebar-section sidebar-user clearfix">
                            <div class="sidebar-user-avatar">
								<a href="<?php echo ($AUTHENTICATION['Authenticated'] ? '/record.php?rec=person&personid='.$AUTHENTICATION['Person']['PersonID'] : 'javascript:void(0)'); ?>"<?php echo (empty($AUTHENTICATION['Authenticated']) ? " onclick=\"LogIn(); return false;\"": ""); ?>>
									<img id="userAvatar" src="<?php echo ($AUTHENTICATION['Authenticated'] ? 'img/avatar/'.$AUTHENTICATION['Person']['PersonID'].'.jpg' : 'img/avatar/avatar_user.png') ?>" alt="<?php echo ($AUTHENTICATION['Authenticated'] ? "Click here to see your record" : "Logged-in users can click here to see their record") ?>" onerror="if(this.src != 'img/avatar/avatar_user.png'){this.src = 'img/avatar/avatar_user.png';}">
								</a>
                            </div>
							<div class="sidebar-user-name"><?php echo $AUTHENTICATION['Person']['Firstname']; ?></div>
							<div class="sidebar-user-links">
<?php if($AUTHENTICATION['Authenticated']): ?>
								<a href="/record.php?rec=person&personid=<?php echo $AUTHENTICATION['Person']['PersonID']; ?>" data-toggle="tooltip" data-placement="bottom" aria-label="Link to your Record"><i class="gi gi-user"></i></a>
<?php if($SYSTEM_SETTINGS["Security"]['AllowPasswordChange']): ?>
								<a href="javascript:void(0)" data-toggle="tooltip" data-placement="bottom" title="Change Password" onclick="$('#changemypwmodal').modal('toggle'); return false;" ><i class="fa fa-key"></i></a>
<?php endif; ?>
<?php if(HasPermission(array('adm_syssettings', 'adm_security'))): ?>
								<a href="/workspace.php?ws=settings" data-toggle="tooltip" data-placement="bottom" title="System Settings"><i class="gi gi-cogwheel"></i></a>
<?php endif; ?>
								<a href="javascript:void(0)" data-toggle="tooltip" data-placement="bottom" title="Log out" onclick="LogOut(); return false;"><i class="gi gi-exit"></i></a>
<?php else: ?>
								<a href="javascript:void(0)" data-toggle="tooltip" data-placement="bottom" title="Log In" onclick="LogIn(); return false;"><i class="fa fa-sign-in"></i></a>
<?php endif; ?>                                
							</div>                          
                        </div>
                         
                        <!-- Sidebar Navigation -->
                        <ul class="sidebar-nav">
<?php
//icon [optional]
//caption
//isopen
//items
//  id
//  caption
//  isopen
//  url
//  script
//  modal
//  items
if($AUTHENTICATION['Authenticated'] && !empty($AUTHENTICATION['Settings']['RecentItems'])) {
    $recentmenu = array(
        'id' => 'menurecentitems',
        'icon' => 'fa-compass',
        'caption' => 'Recent Items',
        'isopen' => TRUE,
        'items' => array()
    );
    $sql =
    "SELECT RecentItemID, Caption, URL
     FROM tblrecentitem
     WHERE `Token` = '".mysqli_real_escape_string($SYSTEM_SETTINGS['Database'], $AUTHENTICATION['Token'])."'
     ORDER BY Recorded DESC
     LIMIT ".intval($AUTHENTICATION['Settings']['RecentItems']);
    if($query = mysqli_query($SYSTEM_SETTINGS['Database'], $sql)) {
        while($item = mysqli_fetch_assoc($query)) {
            $recentmenu['items'][] = array(
                'caption' => $item['Caption'],
                'url' => $item['URL']
            );
        }
    }
    SBMenu($recentmenu);
}
/*if((!$Marvin->CurrentUser->Guest) && (!$Marvin->CurrentUser->Membership->IsMember) && ($SYSTEM_SETTINGS["Customise"]["Navigation"]["Shortcuts"]["Website"]))
{
    SBShortcut(array('caption' => "Join now!", 'icon' => 'gi-nameplate_alt', 'url' => '/apply.php?section=ms'));
}
if($SYSTEM_SETTINGS["Customise"]["Navigation"]["Shortcuts"]["Website"])
{
    SBShortcut(array('caption' => $SYSTEM_SETTINGS["General"]["OrgShortName"].' website', 'icon' => 'fa-external-link', 'url' => $SYSTEM_SETTINGS["General"]["Website"], 'target' => 'newwindow'));
}
if(IsAdministrator($Marvin))
{
    SBHeader(array('caption' => 'Administration'));
    SBShortcut(array('caption' => 'System', 'icon' => 'gi-settings'));
}*/
?>
					   </ul><!-- Sidebar Navigation -->
<?php
SBHeader(array('kind' => 'sectionheader', 'caption' => 'Notifications', 'id' => 'shNotifications', 'iconlist' => array(array('icon' => 'gi-refresh', 'script' => 'LoadNotifications( );'))));
?>                       
					   <div class="sidebar-section" id="sbalerts">
					   </div>

					</div><!-- END Sidebar Content -->
				</div><!-- END Wrapper for scrolling functionality -->
			</div><!-- END Main Sidebar -->
            <div id="changemypwmodal" class="modal" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                            <h4 class="modal-title"><i class="fa fa-key"></i> Change your Password</h4>
                        </div>
                        <div class="modal-body">
                            <form id="frmChangeMyPassword" class="form-horizontal" enctype="multipart/form-data" method="post" name="frmChangeMyPassword">
                                <input id="frmChangeMyPassword:SYSTEM_SOURCE" type="hidden" value="frmChangeMyPassword" name="SYSTEM_SOURCE">
                                <input id="frmChangeMyPassword:PersonID" type="hidden" value="" name="PersonID">
                                <div class="form-group">
                                    <label class="col-sm-4 control-label" for="frmChangeMyPassword:NewPassword1">
                                        Your New Password
                                        <span class="text-danger">*</span>
                                    </label>
                                    <div class="col-sm-5">
                                        <input id="frmChangeMyPassword:NewPassword1" class="form-control" type="password" value="" name="NewPassword1">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label" for="frmChangeMyPassword:NewPassword2">
                                        Confirm New Password
                                        <span class="text-danger">*</span>
                                    </label>
                                    <div class="col-sm-5">
                                        <input id="frmChangeMyPassword:NewPassword2" class="form-control" type="password" value="" name="NewPassword2">
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-sm btn-default" data-dismiss="modal">Cancel</button>
                            <button id="frmChangeMyPassword:btnChangeMyPassword" type="button" class="btn btn-sm btn-warning" disabled="disabled">Change</button>
                        </div>
                    </div>
                </div>
            </div>
<?php ADDPLUGIN('sidebar', 'sidebar.js'); ?>            

        <!-- Main Container -->
			<div id="main-container">
				<header class="navbar navbar-default navbar-fixed-top">
					<div class="navbar-header">
						<!-- Horizontal Menu Toggle for small screens (< 768px) -->
						<ul class="nav navbar-nav-custom pull-right visible-xs">
							<li>
								<a href="javascript:void(0)" data-toggle="collapse" data-target="#horizontal-menu-collapse"><i class="fa fa-bars fa-fw"></i> Menu</a>
							</li>
						</ul>
						<!-- Main Sidebar Toggle Button -->
						<ul class="nav navbar-nav-custom">
							<li>
								<a href="javascript:void(0)" onclick="App.sidebar('toggle-sidebar');">
								<i class="fa fa-ellipsis-h fa-fw"></i>
								</a>
							</li>
						</ul>
					</div><!-- END Navbar Header -->
					<!-- Horizontal Menu + Search -->
<?php
//Create a Menu Structure - built-in items first
define("CONST_MENU_HOME",                  0);
define("CONST_MENU_WORKSPACES",            1);
define("CONST_MENU_PERSONAL",              3);
define("CONST_MENU_TOOLS",                 5);

define("CONST_MENU_ADMINISTRATION",       99);

define("CONST_MENUITEM_WS_PEOPLE",          0);
define("CONST_MENUITEM_WS_ORGS",            1);
define("CONST_MENUITEM_WS_MEMBERSHIP",     10);
define("CONST_MENUITEM_WS_PUBLICATIONS",   11);
define("CONST_MENUITEM_WS_FINANCE",        20);

define("CONST_MENUITEM_ADMIN_GENERAL",      1);
define("CONST_MENUITEM_ADMIN_MEMBERSHIP",   2);
define("CONST_MENUITEM_ADMIN_ETEMPLATES",  20);
define("CONST_MENUITEM_ADMIN_PTEMPLATES",  21);
define("CONST_MENUITEM_ADMIN_SETTINGS",    70);
define("CONST_MENUITEM_ADMIN_EMAILQUEUE",  71);
define("CONST_MENUITEM_ADMIN_SYSLOG",      90);

define("CONST_MENUITEM_PROFILE",          0);
define("CONST_MENUITEM_DPLOG",            2);
define("CONST_MENUITEM_SEARCH",           0);
define("CONST_MENUITEM_REPORTS",          10);
define("CONST_MENUITEM_EXPORT",           11);
define("CONST_MENUITEM_RECENTFILES",      80);
define("CONST_MENUITEM_CALCULATORS",      90);
define("CONST_MENUITEM_MSFEECALCULATOR",  1);
define("CONST_MENUITEM_XCHANGERATES",     2);

//Cache some permissions to avoid repeated calls to HasPermission
$menuPermissions = array();
$menuPermissions['AdminMenu'] = HasPermission(array('adm_syssettings', 'adm_security', 'adm_membership', 'adm_templates'));
$menuPermissions['Templates'] = HasPermission(array('adm_templates'));
$menuPermissions['SysSettings'] = HasPermission(array('adm_syssettings'));
$menuPermissions['MSSettings'] = HasPermission(array('adm_membership'));
$menuPermissions['GenSettings'] = HasPermission(array('adm_general'));

$MAINMENU = array(
    'id' => 'horizontal-menu-collapse',
    'items' => array(
        CONST_MENU_HOME => array(
            'icon' => 'gi-home',
            'url' => '/index.php'
        ),
        CONST_MENU_WORKSPACES => array(
            'visible' => $AUTHENTICATION['Authenticated'],
            'caption' => 'Workspaces',
            'items' => array(
                CONST_MENUITEM_WS_PEOPLE => array(
                    'caption' => 'People',
                    'icon' => 'gi-group',
                    'url' => '/workspace.php?ws=people'
                ),
                CONST_MENUITEM_WS_ORGS => array(
                    'caption' => 'Organisations',
                    'icon' => 'gi-building',
                    'url' => '/workspace.php?ws=organisations'
                ),
                array(
                    'kind' => 'divider'
                ),                
                CONST_MENUITEM_WS_MEMBERSHIP => array(
                    'caption' => 'Membership',
                    'icon' => 'hi-star',
                    'url' => '/workspace.php?ws=members'
                ),
                CONST_MENUITEM_WS_PUBLICATIONS => array(
                    'caption' => 'Publications',
                    'icon' => 'gi-blog',
                    'url' => '/workspace.php?ws=publications'
                ),
                array(
                    'kind' => 'divider'
                ),                
                CONST_MENUITEM_WS_FINANCE => array(
                    'caption' => 'Finance',
                    'icon' => 'gi-calculator',
                    'url' => '/workspace.php?ws=finance'
                ),
                array(
                    'kind' => 'divider',
                    'visible' => $menuPermissions['AdminMenu'],
                ),
                CONST_MENU_ADMINISTRATION => array(
                    'caption' => 'Administration',
                    'icon' => 'gi-adjust_alt',
                    'url' => '/workspace.php?ws=settings',
                    'visible' => $menuPermissions['AdminMenu'],
                    'items' => array(
                        CONST_MENUITEM_ADMIN_GENERAL => array(
                            'caption' => 'General',
                            'icon' => 'gi-settings',
                            'url' => '/workspace.php?ws=gensettings',
                            'visible' => $menuPermissions['GenSettings']
                        ),
                        CONST_MENUITEM_ADMIN_MEMBERSHIP => array(
                            'caption' => 'Membership',
                            'icon' => 'hi-star',
                            'url' => '/workspace.php?ws=mssettings',
                            'visible' => $menuPermissions['MSSettings']
                        ),
                        array(
                            'kind' => 'divider',
                            'visible' => ($menuPermissions['MSSettings'] || $menuPermissions['GenSettings']) && ($menuPermissions['SysSettings'] || $menuPermissions['Templates']),
                        ),
                        CONST_MENUITEM_ADMIN_ETEMPLATES => array(
                            'caption' => 'Email Templates',
                            'icon' => 'fa-at',
                            'url' => '/workspace.php?ws=etemplates',
                            'visible' => $menuPermissions['Templates']
                        ),
                        CONST_MENUITEM_ADMIN_PTEMPLATES => array(
                            'caption' => 'Document Templates',
                            'icon' => 'fa-file-pdf-o',
                            'url' => '/workspace.php?ws=ptemplates',
                            'visible' => $menuPermissions['Templates']
                        ),
                        array(
                            'kind' => 'divider',
                            'visible' => $menuPermissions['Templates'] && $menuPermissions['SysSettings'],
                        ),
                        CONST_MENUITEM_ADMIN_SETTINGS => array(
                            'caption' => 'System Settings',
                            'icon' => 'gi-cogwheel',
                            'url' => '/workspace.php?ws=settings',
                            'visible' => $menuPermissions['SysSettings']
                        ),
                        CONST_MENUITEM_ADMIN_SYSLOG => array(
                            'caption' => 'System Log',
                            'icon' => 'gi-server_flag',
                            'url' => '/workspace.php?ws=syslog',
                            'visible' => $menuPermissions['SysSettings']
                        ),
                    )
                ),
            )
        ),
        CONST_MENU_PERSONAL => array(
            'caption' => 'Personal',
            'visible' => $AUTHENTICATION['Authenticated'],
            'items' => array(
                CONST_MENUITEM_PROFILE => array(
                    'caption' => 'My Profile',
                    'icon' => 'gi-user',
                    'url' => '/record.php?rec=person&personid='.$AUTHENTICATION['Person']['PersonID']
                ),
                CONST_MENUITEM_DPLOG => array(
                    'caption' => 'Data Protection Log',
                    'icon' => '',
                    'url' => '/workspace.php?ws=dplog'
                ),
            )
        ),
        CONST_MENU_TOOLS => array(
            'caption' => 'Tools',
            'visible' => $AUTHENTICATION['Authenticated'],
            'items' => array(
                CONST_MENUITEM_SEARCH => array(
                    'caption' => 'Search',
                    'icon' => 'fa-search',
                    'url' => '/workspace.php?ws=search',
                ),
                array(
                    'kind' => 'divider'
                ),
                CONST_MENUITEM_REPORTS => array(
                    'caption' => 'Reports',
                    'icon' => 'hi-list-alt',
                    'script' => "OpenDialog( 'test', { large: true } )",
//                    'url' => '/reports.php'
                ),
                CONST_MENUITEM_EXPORT => array(
                    'caption' => 'Export',
                    'icon' => 'gi-disk_export',
                    'url' => '/export.php'
                ),
                array(
                    'kind' => 'divider'
                ),
                CONST_MENUITEM_RECENTFILES => array(
                    'caption' => 'Recent files',
                    'icon' => 'gi-file',
                    'url' => '/workspace.php?ws=recentfiles'
                ),
                array(
                    'kind' => 'divider'
                ),
                CONST_MENUITEM_CALCULATORS => array(
                    'caption' => 'Calculators',
                    'visible' => $AUTHENTICATION['Authenticated'],
                    'items' => array(
                        CONST_MENUITEM_MSFEECALCULATOR => array(
                            'caption' => 'Membership',
                            'icon' => 'fa-bolt',
                            'script' => "OpenDialog('msfeecalculator', { large: true });"
                        ),
                        CONST_MENUITEM_XCHANGERATES => array(
                            'caption' => 'Exchange Rates',
                            'icon' => 'gi-divide',
                            'visible' => !empty($SYSTEM_SETTINGS['Finance']['CurrencyConverter']['Method']),
                            'script' => "OpenDialog('xchangerates', { large: false });"
                        ),
                    )
                ),
            )
            
        ),
    ),
    'input' => array(
        'placeholder' => 'Search...',
        'id' => 'inputQuickSearch',
        'name' => 'inputQuickSearch',
        'aria-label' => 'Quick Search',
        'onsubmit' => 'ExecQSearch( this ); return false;',
    ),
);

SortMenuItems($MAINMENU);
MainMenu($MAINMENU);

?><!-- END Horizontal Menu -->
				</header>
                
				<!-- Page content -->
				<div id="page-content">

					<!-- Page Header -->
					
                    
					
					<ul class="breadcrumb breadcrumb-top">
						<?php Breadcrumbs(":/workspace.php"); ?>
					</ul>
					<!-- END Page Header -->

                    <div class="row">
                        <div class="col-md-8" id="wsMain">
                        </div>
                        <div class="col-md-4" id="wsSide">
                        </div>
                    </div>
				</div><!-- END Page Content -->

				<footer class="clearfix">
					<div class="pull-left">
						Developed by <a href="http://www.guidogybels.eu/" target="_blank">Guido Gybels</a>
					</div>
					<div class="pull-right">
						<a href="/about.php" target="_blank">About</a> &middot; <a href="#modal-terms" data-toggle="modal" class="register-terms">Terms</a><span class="hidden-xs"> &middot; Designed for <a href="http://www.mozilla.org/en-US/firefox/fx/" target="_blank">Firefox</a> &amp; <a href="https://www.google.com/chrome/browser/desktop/" target="_blank">Chrome</a></span>
					</div>
				</footer>

			</div><!-- END Main Container -->

<?php
switch($WORKSPACE['id']){
    case 'people':
        AddRecent('People', $_SERVER["REQUEST_URI"]);
        break;
    case 'organisations':
        AddRecent('Organisations', $_SERVER["REQUEST_URI"]);
        break;
    case 'publications':
        AddRecent('Publications', $_SERVER["REQUEST_URI"]);
        break;
    case 'members':
        AddRecent('Membership', $_SERVER["REQUEST_URI"]);
        break;
}
?>


            <!-- Containers for modal dialogs -->
            <div id="dlgStandard" class="modal" data-backdrop="static" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                    </div>    
                </div>
            </div>

            <div id="dlgLarge" class="modal" data-backdrop="static" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                    </div>    
                </div>
            </div>

            <div id="dlgConfirmation" class="modal" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog modal-sm">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                            <h3 class="modal-title" id="dlgConfirmationTitle">Confirm</h3>
                        </div>
                        <div class="modal-body" id="dlgConfirmationBody">
                            <p>Are you sure?</p>
                        </div>
                        <div class="modal-footer">
					       <button id="dlgConfirmationBtnYes" type="button" class="btn btn-sm btn-success" name="yes"><i class="gi gi-ok_2"></i> Yes</button>
					       <button id="dlgConfirmationBtnNo" type="button" class="btn btn-sm btn-primary" data-dismiss="modal" name="no"><i class="gi gi-ban"></i> No</button>
					       <button id="dlgConfirmationBtnCancel" type="button" class="btn btn-sm btn-warning" data-dismiss="modal" name="cancel"><i class="gi fa-times"></i> Cancel</button>
					       <button id="dlgConfirmationBtnRetry" type="button" class="btn btn-sm btn-primary" data-dismiss="modal" name="cancel"><i class="gi hi-repeat"></i> Retry</button>
                        </div>
                    </div>    
                </div>
            </div>

		</div><!-- END Page Container -->
		<!-- Scroll to top link, initialized in js/app.js - scrollToTop() -->
		<a href="#" id="to-top"><i class="fa fa-angle-double-up"></i></a>

		<!-- Include local copy of Jquery library -->
		<script src="/js/vendor/jquery-1.12.0.min.js"></script>

		<!-- Bootstrap.js, Bootbox.js, Jquery plugins and Custom JS code -->
		<script src="/js/vendor/bootstrap.min.js"></script>
		<script src="/js/plugins.js"></script>
		<script src="/js/vendor/bootstrap.min.js"></script>
		<script src="/js/slib.js"></script>
		<script src="/js/vendor/bootstrap.min.js"></script>
		<script src="/js/proui.js"></script>
		<script src="/js/app.js"></script>
 	       <script type="text/javascript">
            $(function() {
                LoadNotifications();
                //Refresh the notifications the first time after 1 minute 
                setTimeout(function() {
                    LoadNotifications();
                    //Then, schedule a reload every 5 minutes after that
                    setInterval(function() {
                        LoadNotifications();
	               }, 300000); //Refresh the notifications area every 5 minutes
                }, 60000);
            });
        </script>        

        <!-- Modal Terms -->
<?php TermsAndConditions(2); ?>
        <!-- END Modal Terms -->
<script type="text/javascript">
$(function() {
    LoadWorkspace( '<?php echo $WORKSPACE['id']; ?>', { } );
});

function LoadWorkspace( wsName, wsOptions ) {
    var getParams;
    getParams = getUrlVars();
    switch( wsName ){
        case 'people':
            LoadContent('wsMain', '/load.php?do=table_people', { spinner: true } );
            LoadContent('wsSide', '/load.php?do=aag_people', { spinner: true } );
            break;
        case 'organisations':
            LoadContent('wsMain', '/load.php?do=table_organisations', { spinner: true } );
            //LoadContent('wsSide', '/load.php?do=aag_organisations', { spinner: true } );
            break;
        case 'members':
            var url = '/load.php?do=table_members';
            if( typeof getParams.breakdown == 'string' ) {
                url = url.concat('&hideataglance=1');
            }
            LoadContent('wsMain', url, 
                { spinner: true,
                  cbSuccess: function( div ) {
                      if( typeof getParams.breakdown == 'string' ) {
                          var tablename = getParams.breakdown;
                          inputoptions = JSON.parse( decodeURIComponent(getParams[getParams.breakdown]) );
                          //console.log(tablename);
                          //console.log(inputoptions);
                          LoadContent('msBreakdownList', '/load.php?do='+tablename, inputoptions );
                      }
                  },
                } 
            );        
            LoadContent('wsSide', '/load.php?do=aag_members', { spinner: true } );
            break;
        case 'publications':
            LoadContent('wsMain', '/load.php?do=table_publications', { spinner: true } );
            LoadContent('wsSide', '/load.php?do=aag_people', { spinner: true } );
            break;
        case 'finance':
            LoadContent('wsMain', '/load.php?do=finance', 
                { spinner: true,
                  cbSuccess: function( div ) {
                    //$('#tab-intro').data('loaded', true);
                    div.find('ul.nav-tabs li a').on('shown.bs.tab', function (e) {
                        var tabID = $(this).attr('href').substring(1);
                        var tabDiv = $('#'+tabID);
                        if( !tabDiv.data('loaded') ) {
                            LoadContent(tabDiv, '/load.php?do=finance_tab&tabid='+tabID,
                                { 
                                    spinner: true, 
                                    cbSuccess: function( tabdiv ) { $(tabdiv).data('loaded', true); }, 
                                }
                            );                            
                        } else {
                            //Already loaded - any datatables? If so, reload them
                            tabDiv.find('.dataTables_wrapper table').each( function() { $(this).dataTable().fnDraw() } );
                        }
                    });
                    LoadContent('tab-invoices', '/load.php?do=finance_tab&tabid=tab-invoices', { spinner: true, cbSuccess: function( tabdiv ) { $(tabdiv).data('loaded', true); } });
                  },
                } 
            );
            break;
        case 'settings':
            LoadContent('wsMain', '/load.php?do=system_settings', 
                { spinner: true,
                  cbSuccess: function( div ) {
                    $('#tab-intro').data('loaded', true);
                    div.find('ul.nav-tabs li a').on('shown.bs.tab', function (e) {
                        var tabID = $(this).attr('href').substring(1);
                        var tabDiv = $('#'+tabID);
                        if( !tabDiv.data('loaded') ) {
                            LoadContent(tabDiv, '/load.php?do=admin_panel&tabid='+tabID,
                                { 
                                    spinner: true, 
                                    cbSuccess: function( tabdiv ) { $(tabdiv).data('loaded', true); }, 
                                }
                            );                            
                        } else {
                            //Already loaded - any datatables? If so, reload them
                            tabDiv.find('.dataTables_wrapper table').each( function() { $(this).dataTable().fnDraw() } );
                        }
                    });
                  },
                } 
            );
            break;
        case 'dplog':
            LoadContent('wsMain', '/load.php?do=dplog', { spinner: true } );
            break;
        case 'recentfiles':
            LoadContent('wsMain', '/load.php?do=recentfiles', { spinner: true } );
            break;
        case 'recentfiles':
            LoadContent('wsMain', '/load.php?do=recentfiles', { spinner: true } );
            break;
        case 'syslog':
            LoadContent('wsMain', '/load.php?do=sys_log', { spinner: true } );
            LoadContent('wsSide', '/load.php?do=sidebar_syslog', { divid: 'sidebar_syslog', spinner: true } );
            LoadContent('wsSide', '/load.php?do=sidebar_scheduler', { divid: 'sidebar_scheduler', spinner: true } );
            break;
        case 'emailqueue':
            LoadContent('wsMain', '/load.php?do=email_queue', { spinner: true } );
            LoadContent('wsSide', '/load.php?do=sidebar_scheduler', { divid: 'sidebar_scheduler', spinner: true } );
            break;
        case 'mssettings':
            LoadContent('wsMain', '/load.php?do=mssettings', { spinner: true } );
            break;
        case 'gensettings':
            LoadContent('wsMain', '/load.php?do=gensettings', { spinner: true } );
            break;
        case 'etemplates':
            LoadContent('wsMain', '/load.php?do=etemplates', { spinner: true } );
            break;
        case 'ptemplates':
            LoadContent('wsMain', '/load.php?do=ptemplates', { spinner: true } );
            break;
        case 'quicksearch':
            LoadContent('wsMain', '/load.php?do=table_quicksearch&queryid=<?php echo (!empty($_GET['queryid']) ? intval($_GET['queryid']) : ''); ?>', { spinner: true } );
            break;
        case 'search':
            var search = location.search.substring(1);
            urlParams = (search ? JSON.parse('{"' + search.replace(/&/g, '","').replace(/=/g,'":"') + '"}',
                         function(key, value) { return key===""?value:decodeURIComponent(value) }) : {} );      
            if( urlParams.exec ) {
                //Show search results
                LoadContent('wsMain', '/load.php?do=searchresults',
                    {
                        spinner: true,
                        urlparams: urlParams,
                    }
                );
            } else {
                //Show search pages
                LoadContent('wsMain', '/load.php?do=newsearch',
                    {  spinner: true,
                       cbSuccess: function( div ) {
                            div.find('ul.nav-tabs li a').on('shown.bs.tab', function (e) {
                                var tabID = $(this).attr('href').substring(1);
                                var tabDiv = $('#'+tabID);
                                if( !tabDiv.data('loaded') ) {
                                    LoadContent(tabDiv, '/load.php?do=newsearchtab&tabid='+tabID,
                                        { 
                                            spinner: true, 
                                            urlparams: urlParams, 
                                            cbSuccess: function( tabdiv ) { $(tabdiv).data('loaded', true); }, 
                                        }
                                    );
                                }
                            });
                            
                            if(urlParams.tab) {
                                GotoTab( urlParams.tab );
                            } else {
                                LoadContent('tab-qbe', '/load.php?do=newsearchtab&tabid=tab-qbe', { spinner: true, urlparams: urlParams, cbSuccess: function( tabdiv ) { $(tabdiv).data('loaded', true); } });
                            }
                       },
                    } 
                );
            }
            break;
    }
}

</script>

<?php
if((count($VALIDATION) > 0) || (isset($DATATABLES) && (count($DATATABLES) > 0)) || (count($HANDLERS) > 0))
{
	echo "\n\t\t<!-- JQuery routines for this Page -->\n";
    echo "\t\t<script type=\"text/javascript\">\n";
    echo "\t\t\tjQuery(function($) {\n\n";
    if(count($VALIDATION) > 0)
    {
        echo "\t\t\t\t//Form Validation\n";
        foreach($VALIDATION AS $aformid => $data) {
            jsValidation($aformid, $data);
        }
        echo "\n";
    }
    if(count($HANDLERS) > 0)
    {
        echo "\t\t\t\t//Attach Event Handlers\n";
        foreach($HANDLERS AS $controlid => $events)
        {
            foreach($events AS $eventname => $eventdata)
            {
                assert(isset($eventdata['function']));
                echo "\t\t\t\t$('#".escSelector($controlid)."').{$eventname}(function( event ) {\n";
                if(is_string($eventdata['function']))
                {
                    $lines = explode("\n", $eventdata['function']);
                    foreach($lines AS $line)
                    {
                        echo "\t\t\t\t\t".rtrim($line)."\n";
                    }
                }
                echo "\t\t\t\t})".(!empty($eventdata['firstrun']) ? ".".$eventname."()" : "").";\n";
            }
        }
//    $HANDLERS[$trusted_controlid][$trusted_event] = array('function' => $function, 'firstrun' => $firstrun);
    }
    if ((isset($DATATABLES)) && (count($DATATABLES) > 0))
    {
        echo "\t\t\t\t//Datatables Initialisation\n";
        echo "\t\t\t\tApp.datatables();\n\n";
        foreach($DATATABLES AS $datatable) {
            jsInitDatatable($datatable);            
        }
        echo "\n";
    }    
    echo "\t\t\t});\n";
    echo "\t\t</script>\n";
}

if(count($PLUGINS) > 0)
{
	echo "\n\t\t<!-- Load Additional Plugins for this page -->\n";
	foreach($PLUGINS AS $name => $script)
	{
        if(is_string($script))
        {
            if(strcasecmp(substr($script, 0, 4), 'http') == 0)
            {
                echo "\t\t<script src=\"{$script}\"></script>\n";
            }
            else
            {
                echo "\t\t<script src=\"js/{$script}\"></script>\n";
            }
        }
        elseif(is_array($script))
        {
            echo "\t\t<script type=\"text/javascript\">\n";
            foreach($script AS $line)
            {
                echo "\t\t\t".$line."\n";
            }
            echo "\t\t</script>\n";
        }
	}
}
?>
	</body>
</html>
<?php ob_end_flush(); ?>