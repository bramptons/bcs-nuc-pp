<?php

/**
 * @author Guido Gybels
 * @copyright 2015
 * @project BCS CRM
 * @description This unit contains non-interactive routines that implement background processing, garbage collection and the email queue
 */

require_once("initialise.inc");
require_once("person.inc");
require_once("messaging.inc");
use Aws\S3\S3Client;

$options = getopt("", array('gc', 'bg', 'mq', 'fe'));
ignore_user_abort(true);
set_time_limit(86400);

/**
 * BACKGROUND PROCESSING
 */
if(!empty($_GET['bg']) || isset($options['bg'])) {
    $handle = fopen(CONSTLocalStorageRoot."_bg.lock", "w+");
    if (flock($handle, LOCK_EX | LOCK_NB)) {
        try {
            //Begin Background Processing
            $startProcessing = microtime(true);
            AddToSysLog(array('IsSystem' => TRUE, 'Caption' => 'Background Processor', 'Description' => 'The background processor is starting.', 'Expiry' => $SYSTEM_SETTINGS['ExpiryPolicies']['BGProc']));
            //Remove expired authentication tokens
            $sql = 
            "DELETE FROM tblauth WHERE Expires < UTC_TIMESTAMP()";
            ExecuteAndLog($sql, 'Background Processor', 'Purging expired authentication tokens. %affected% deleted.');
            
            //Remove expired Groups
            $sql = 
            "DELETE FROM tblpersongroup WHERE Expires < UTC_TIMESTAMP()";
            ExecuteAndLog($sql, 'Background Processor', 'Removing expired groups. %affected% deleted.');

            //Renew free membership
            $sql = 
            "SELECT tblperson.PersonID, tblperson.MSNumber, tblperson.DoNotContact,
                    tblperson.Firstname, tblperson.Middlenames, tblperson.Lastname, tblperson.Title, tblperson.Gender, tblperson.ExtPostnominals,
                    CONCAT_WS(' ', tblperson.Title, tblperson.Firstname, tblperson.Middlenames, tblperson.Lastname) AS `Fullname`,
	                IF((tblperson.DoNotContact > 0) OR (tblperson.Deceased IS NOT NULL), 0, 1) AS `AllowInteraction`, 
                    tblperson.MSNextRenewal AS `PrevMSNextRenewal`, DATE_ADD(tblperson.MSNextRenewal, INTERVAL 1 YEAR) AS `NextMSNextRenewal`,
                    CONVERT_TZ(DATE_SUB(tblperson.MSNextRenewal, INTERVAL 1 SECOND), 'Europe/London', 'UTC') AS `ChangeDateTime`,                    
                    CONVERT_TZ(tblperson.MSNextRenewal, 'Europe/London', 'UTC') AS `ChangedDateTime`,               
                    tblpersonms.PersonMSID, tblpersonms.MSGradeID,
	                tblpersonms.BeginDate, tblpersonms.EndDate, tblpersonms.MSFlags,
                    COALESCE(tblmsgrade.GradeCaption, '') AS `GradeCaption`,
                    tblmsstatus.MSStatusID,
                    COALESCE(tblmsstatus.MSStatusCaption, 'Not a Member') AS `MSStatusTxt`,
                    tblnewmsstatus.MSStatusID AS `NewMSStatusID`, tblnewmsstatus.MSStatusTxt AS `NewMSStatusTxt`
             FROM tblperson
             LEFT JOIN tblpersonms ON (tblpersonms.PersonID = tblperson.PersonID) AND (tblpersonms.BeginDate <= UTC_TIMESTAMP()) AND ((tblpersonms.EndDate IS NULL) OR (tblpersonms.EndDate >= UTC_TIMESTAMP()))
             LEFT JOIN tblmsstatus ON tblmsstatus.MSStatusID = tblpersonms.MSStatusID
             LEFT JOIN tblmsgrade ON (tblperson.Deceased IS NULL) AND (tblmsgrade.MSGradeID = tblpersonms.MSGradeID) AND (FIND_IN_SET('ismember', tblmsstatus.MSStatusFlags))
             LEFT JOIN (SELECT tblmsstatus.MSStatusID, COALESCE(tblmsstatus.MSStatusCaption, 'Not a Member') AS `MSStatusTxt`
                        FROM tblmsstatus 
                        WHERE FIND_IN_SET('ismember', tblmsstatus.MSStatusFlags) AND FIND_IN_SET('msbenefits', tblmsstatus.MSStatusFlags)
                        ORDER BY tblmsstatus.MSStatusID 
                        LIMIT 1
             ) AS `tblnewmsstatus` ON TRUE
             WHERE (tblperson.Deceased IS NULL) AND FIND_IN_SET('free', tblpersonms.MSFlags) AND (NOT FIND_IN_SET('norenewal', tblperson.MSFlags)) AND FIND_IN_SET('ismember', tblmsstatus.MSStatusFlags) AND (tblperson.MSNextRenewal < CURRENT_DATE())";
/*            ProcessRecordsAndLog(
                $sql,
                "RenewFreeMembers",
                'Background Processor',
                'Renewing membership for free members. %affected% removed.'
            );*/            
            
            //Remove Renewal transactions for Records with No Renewal set
            $sql = 
            "SELECT tblrenewal.RenewalID, tblrenewal.PersonID, tblinvoiceitem.InvoiceItemID, COALESCE(tblinvoiceitem.DiscountID, tblrenewal.DiscountID) AS `DiscountID`
             FROM tblrenewal
             INNER JOIN tblperson ON (tblperson.PersonID = tblrenewal.PersonID) AND (tblperson.Deceased IS NULL)
             LEFT JOIN (SELECT tblinvoiceitem.InvoiceItemID, tblinvoiceitem.LinkedID, tblinvoiceitemtype.Mnemonic, tblinvoiceitem.DiscountID
		                FROM tblinvoiceitem
                        INNER JOIN tblinvoice ON (tblinvoice.InvoiceID = tblinvoiceitem.InvoiceID) AND (tblinvoice.InvoiceType = 'invoice')
                        INNER JOIN tblinvoiceitemtype ON (tblinvoiceitemtype.InvoiceItemTypeID = tblinvoiceitem.InvoiceItemTypeID)
             ) AS `tblinvoiceitem` ON (tblinvoiceitem.Mnemonic = 'ms_renewal') AND (tblinvoiceitem.LinkedID = tblrenewal.RenewalID)               
             WHERE FIND_IN_SET('norenewal', tblperson.MSFlags)
            ";
            ProcessRecordsAndLog(
                $sql,
                "DeleteRenewalItem",
                'Background Processor',
                'Deleting membership renewal transactions where Do not renew is set. %affected% removed.'
            );
            //End membership for records with Do Not Renew that have passed the renewal date
            $sql =
            "SELECT tblperson.PersonID, tblperson.MSNumber, IF(FIND_IN_SET('free', tblpersonms.MSFlags), 1, 0) AS `MSFree`,
                    tblperson.Firstname, tblperson.Middlenames, tblperson.Lastname, tblperson.Title, tblperson.Gender, tblperson.ExtPostnominals,
                    CONCAT_WS(' ', tblperson.Title, tblperson.Firstname, tblperson.Middlenames, tblperson.Lastname) AS `Fullname`,
	                IF((tblperson.DoNotContact > 0) OR (tblperson.Deceased IS NOT NULL), 0, 1) AS `AllowInteraction`,
                    tblperson.MSNextRenewal, tblperson.DoNotContact, tblperson.Deceased,
                    DATE_SUB(CONVERT_TZ(tblperson.MSNextRenewal, 'Europe/London', 'UTC'), INTERVAL 1 SECOND) AS `MembershipEnds`,
                    CONVERT_TZ(tblperson.MSNextRenewal, 'Europe/London', 'UTC') AS `MembershipEnded`,                    
                    tblpersonms.PersonMSID, tblpersonms.MSGradeID,
	                tblpersonms.BeginDate, tblpersonms.EndDate, tblpersonms.MSFlags,
                    COALESCE(tblmsgrade.GradeCaption, '') AS `GradeCaption`,
                    tblmsstatus.MSStatusID,
                    COALESCE(tblmsstatus.MSStatusCaption, 'Not a Member') AS `MSStatusTxt`,
                    tblnewmsstatus.MSStatusID AS `NewMSStatusID`, tblnewmsstatus.MSStatusTxt AS `NewMSStatusTxt`
             FROM tblperson
             LEFT JOIN tblpersonms ON (tblpersonms.PersonID = tblperson.PersonID) AND (tblpersonms.BeginDate <= UTC_TIMESTAMP()) AND ((tblpersonms.EndDate IS NULL) OR (tblpersonms.EndDate >= UTC_TIMESTAMP()))
             LEFT JOIN tblmsstatus ON tblmsstatus.MSStatusID = tblpersonms.MSStatusID
             LEFT JOIN tblmsgrade ON (tblperson.Deceased IS NULL) AND (tblmsgrade.MSGradeID = tblpersonms.MSGradeID) AND (FIND_IN_SET('ismember', tblmsstatus.MSStatusFlags))
             LEFT JOIN (SELECT tblmsstatus.MSStatusID, COALESCE(tblmsstatus.MSStatusCaption, 'Not a Member') AS `MSStatusTxt`
                        FROM tblmsstatus 
                        WHERE FIND_IN_SET('msend', tblmsstatus.MSStatusFlags) AND FIND_IN_SET('norenewal', tblmsstatus.MSStatusFlags)
                        ORDER BY tblmsstatus.MSStatusID 
                        LIMIT 1
             ) AS `tblnewmsstatus` ON TRUE
             WHERE FIND_IN_SET('norenewal', tblperson.MSFlags) AND FIND_IN_SET('ismember', tblmsstatus.MSStatusFlags) AND (tblperson.MSNextRenewal < CURRENT_DATE())
            ";
            ProcessRecordsAndLog(
                $sql,
                "TerminateNoRenewals",
                'Background Processor',
                'Ending membership past the renewal date where Do not renew has been set. %affected% processed.'
            );
            
            //Put membership in overdue stage 
            $sql = 
            "SELECT tblperson.PersonID, tblperson.MSNumber, tblperson.DoNotContact,
	                tblperson.Firstname, tblperson.Middlenames, tblperson.Lastname, tblperson.Title, tblperson.Gender, tblperson.ExtPostnominals,
                    CONCAT_WS(' ', tblperson.Title, tblperson.Firstname, tblperson.Middlenames, tblperson.Lastname) AS `Fullname`,
	                IF((tblperson.DoNotContact > 0) OR (tblperson.Deceased IS NOT NULL), 0, 1) AS `AllowInteraction`,
                    tblperson.MSNextRenewal,
                    CONVERT_TZ(DATE_SUB(tblperson.MSNextRenewal, INTERVAL 1 SECOND), 'Europe/London', 'UTC') AS `CurrentStatusEnds`,                    
                    CONVERT_TZ(tblperson.MSNextRenewal, 'Europe/London', 'UTC') AS `NewStatusStarts`,                    
                    tblpersonms.PersonMSID, tblpersonms.MSGradeID,
	                tblpersonms.BeginDate, tblpersonms.EndDate, tblpersonms.MSFlags,
                    COALESCE(tblmsgrade.GradeCaption, '') AS `GradeCaption`,
                    tblmsstatus.MSStatusID,
                    COALESCE(tblmsstatus.MSStatusCaption, 'Not a Member') AS `MSStatusTxt`,
                    tblnewmsstatus.MSStatusID AS `NewMSStatusID`, tblnewmsstatus.MSStatusTxt AS `NewMSStatusTxt`,
                    IF(tblperson.MSNextRenewal < CURRENT_DATE(),
                        2,
                        IF(CURRENT_DATE() >= DATE_SUB(tblperson.MSNextRenewal, INTERVAL {$SYSTEM_SETTINGS["Membership"]['RenewalCycleStart']} DAY), 1, 0)
                    ) AS `RenewalCycle`,                    
                    tblinvoiceitem.InvoiceID, tblrenewal.RenewalID, tblddi.DDIID,
                    IF(tblddi.DDIID IS NOT NULL, 1, 0) AS `DirectDebit`                    
             FROM tblperson
             LEFT JOIN tblpersonms ON (tblpersonms.PersonID = tblperson.PersonID) AND (tblpersonms.BeginDate <= UTC_TIMESTAMP()) AND ((tblpersonms.EndDate IS NULL) OR (tblpersonms.EndDate >= UTC_TIMESTAMP()))
             LEFT JOIN tblmsstatus ON tblmsstatus.MSStatusID = tblpersonms.MSStatusID
             LEFT JOIN tblmsgrade ON (tblperson.Deceased IS NULL) AND (tblmsgrade.MSGradeID = tblpersonms.MSGradeID) AND (FIND_IN_SET('ismember', tblmsstatus.MSStatusFlags))
             LEFT JOIN  tblddi ON (tblddi.PersonID = tblperson.PersonID) AND (tblperson.ISO4217 = 'GBP') AND (tblddi.InstructionScope = 'members') 
             LEFT JOIN (SELECT tblmsstatus.MSStatusID, COALESCE(tblmsstatus.MSStatusCaption, 'Not a Member') AS `MSStatusTxt`
                        FROM tblmsstatus 
                        WHERE FIND_IN_SET('ismember', tblmsstatus.MSStatusFlags) AND FIND_IN_SET('overdue', tblmsstatus.MSStatusFlags)
                        ORDER BY tblmsstatus.MSStatusID 
                        LIMIT 1
             ) AS `tblnewmsstatus` ON TRUE
             LEFT JOIN tblrenewal ON tblrenewal.PersonID = tblperson.PersonID
             LEFT JOIN (SELECT tblinvoiceitem.InvoiceItemID, tblinvoiceitem.LinkedID, tblinvoiceitem.ItemQty, tblinvoiceitem.ItemUnitPrice, tblinvoiceitem.ItemVATRate,
	                           tblinvoiceitem.ItemNet, tblinvoiceitem.ItemVAT, tblinvoiceitem.ItemDate, tblinvoiceitem.DiscountID, 
                               COALESCE(tblinvoiceitem.Description, tblinvoiceitemtype.TypeName) AS `Description`,
	                           tblinvoiceitemtype.CategorySelector, tblinvoiceitemtype.Mnemonic,
                               tblinvoice.InvoiceID, tblinvoice.ISO4217, tblinvoice.InvoiceDate, tblinvoice.InvoiceDue,
			                   CONCAT_WS(' ', IF(tblinvoice.InvoiceNo IS NULL, IF(tblinvoice.InvoiceType = 'creditnote', 'Draft', 'Pro Forma'), ''),
                                              IF(tblinvoice.InvoiceType = 'creditnote', 'Credit Note', IF(tblinvoice.InvoiceNo IS NULL, CONCAT('#', CAST(tblinvoice.InvoiceID AS CHAR)), CONCAT('Invoice ', tblinvoice.InvoiceNo)))
                               ) AS `InvoiceCaption`
		                FROM tblinvoiceitem
                        INNER JOIN tblinvoice ON (tblinvoice.InvoiceID = tblinvoiceitem.InvoiceID) AND (tblinvoice.InvoiceType = 'invoice') AND (tblinvoice.InvoiceNo IS NULL)
                        INNER JOIN tblinvoiceitemtype ON (tblinvoiceitemtype.InvoiceItemTypeID = tblinvoiceitem.InvoiceItemTypeID)
                        WHERE tblinvoiceitem.Processed IS NULL
             ) AS `tblinvoiceitem` ON (tblinvoiceitem.Mnemonic = 'ms_renewal') AND (tblinvoiceitem.LinkedID = tblrenewal.RenewalID)                
             WHERE (tblperson.Deceased IS NULL) AND (NOT FIND_IN_SET('free', tblpersonms.MSFlags)) AND FIND_IN_SET('ismember', tblmsstatus.MSStatusFlags) AND (tblnewmsstatus.MSStatusID <> tblmsstatus.MSStatusID) AND (tblperson.MSNextRenewal < CURRENT_DATE())
             GROUP BY tblperson.PersonID
             ";
            ProcessRecordsAndLog(
                $sql,
                "TransitionToOverdue",
                'Background Processor',
                'Moving membership status to overdue stage. %affected% updated.'
            );

            //Create Membership Renewal Records for upcoming renewals - People
            $sql = 
            "INSERT INTO tblrenewal (WSCategoryID, PersonID, MSGradeID, Created, LastModified, RenewFor, RenewFlags, DiscountID)
             SELECT tblwscategory.WSCategoryID, tblperson.PersonID, tblmsgrade.MSGradeID, UTC_TIMESTAMP(), UTC_TIMESTAMP(), 1, IF(FIND_IN_SET('free', tblpersonms.MSFlags), 'free', ''), tbldiscount.DiscountID 
             FROM tblperson
             INNER JOIN tblpersonms ON (tblpersonms.PersonID = tblperson.PersonID) AND (tblpersonms.BeginDate <= UTC_TIMESTAMP()) AND ((tblpersonms.EndDate IS NULL) OR (tblpersonms.EndDate >= UTC_TIMESTAMP()))
             LEFT JOIN tblmsstatus ON tblmsstatus.MSStatusID = tblpersonms.MSStatusID
             INNER JOIN tblmsgrade ON (tblperson.Deceased IS NULL) AND (tblmsgrade.MSGradeID = tblpersonms.MSGradeID) AND (FIND_IN_SET('ismember', tblmsstatus.MSStatusFlags))
             LEFT JOIN tblrenewal ON tblrenewal.PersonID = tblperson.PersonID
             LEFT JOIN tblmsgrade AS tblmsrenewalgrade ON tblmsrenewalgrade.MSGradeID = tblrenewal.MSGradeID
             LEFT JOIN  tblddi ON (tblddi.PersonID = tblperson.PersonID) AND (tblperson.ISO4217 = 'GBP') AND (tblddi.InstructionScope = 'members')
             LEFT JOIN tblwscategory ON tblwscategory.CategorySelector = 'members'
             LEFT JOIN (SELECT tbldiscounttoperson.DiscountToPersonID, tbldiscounttoperson.RefCount, tbldiscounttoperson.PersonID,
                               tbldiscount.DiscountID, tbldiscount.DiscountCode, tbldiscount.Description,
                               tbldiscount.CategorySelector, tbldiscount.InvoiceItemTypeID,
                               tbldiscount.Discount, tbldiscount.ValidFrom, tbldiscount.ValidUntil,
                               tblinvoiceitemtype.Mnemonic, tblinvoiceitemtype.TypeName
                        FROM tbldiscounttoperson
                        INNER JOIN tblperson ON tblperson.PersonID = tbldiscounttoperson.PersonID
                        INNER JOIN tbldiscount ON tbldiscounttoperson.DiscountID = tbldiscount.DiscountID
                        LEFT JOIN tblinvoiceitemtype ON tblinvoiceitemtype.InvoiceItemTypeID = tbldiscount.InvoiceItemTypeID
                        WHERE ((tbldiscounttoperson.Expires IS NULL) OR (tbldiscounttoperson.Expires >= UTC_TIMESTAMP()))
                                            AND
                               (tbldiscounttoperson.RefCount > 0)
                                            AND
                               ((tbldiscount.ValidFrom IS NULL) OR (tbldiscount.ValidFrom <= DATE_FORMAT(CONVERT_TZ(tblperson.MSNextRenewal, '{$SYSTEM_SETTINGS['System']['Timezone']}', 'UTC'), '%Y-%m-%d %H:%i:%s')))
                                            AND
                               ((tbldiscount.ValidUntil IS NULL) OR (tbldiscount.ValidUntil >= DATE_FORMAT(CONVERT_TZ(tblperson.MSNextRenewal, '{$SYSTEM_SETTINGS['System']['Timezone']}', 'UTC'), '%Y-%m-%d %H:%i:%s')))
                                            AND
                               ((tbldiscount.CategorySelector IS NULL) OR (tbldiscount.CategorySelector = 'members'))
                                            AND
                               ((tbldiscount.InvoiceItemTypeID IS NULL) OR (tblinvoiceitemtype.Mnemonic = 'ms_renewal'))
                        ORDER BY tbldiscounttoperson.DiscountToPersonID
             ) AS tbldiscount ON tbldiscount.PersonID = tblperson.PersonID 
             WHERE (NOT FIND_IN_SET('norenewal', tblperson.MSFlags)) AND (tblrenewal.RenewalID IS NULL) AND (tblperson.Deceased IS NULL) AND (tblperson.MSNextRenewal IS NOT NULL) AND (DATE_SUB(tblperson.MSNextRenewal, INTERVAL {$SYSTEM_SETTINGS['Membership']['RenewalCycleStart']} DAY) <= UTC_TIMESTAMP)
             GROUP BY tblperson.PersonID";
            ExecuteAndLog($sql, 'Background Processor', 'Creating renewal records for upcoming renewals. %affected% created.');
            //Create Payment Items for upcoming non-free Membership renewals that haven't got a payment item associated yet - People
            $sql =
            "SELECT tblrenewal.RenewalID, tblrenewal.PersonID, tblrenewal.WSCategoryID, tblrenewal.MSGradeID,  tblrenewal.Created, tblrenewal.LastModified, 
                    tblrenewal.RenewFor, tblrenewal.RenewFlags, tblrenewal.DiscountID,
	                tblwscategory.WSCategoryID, tblperson.PersonID,
                    tblperson.Firstname, tblperson.Middlenames, tblperson.Lastname, tblperson.Title, tblperson.Gender, tblperson.ExtPostnominals,
                    CONCAT_WS(' ', tblperson.Title, tblperson.Firstname, tblperson.Middlenames, tblperson.Lastname) AS `Fullname`,
                    COALESCE(tblmsrenewalgrade.MSGradeID, tblmsgrade.MSGradeID) AS `MSGradeID`,
                    COALESCE(tblmsrenewalgrade.GradeCaption, tblmsgrade.GradeCaption) AS `GradeCaption`,
                    tblperson.MSNumber, tblperson.DoNotContact, tblperson.ISO3166, 
                    COALESCE(tblinvoiceitem.ISO4217, tblperson.ISO4217) AS `ISO4217`,
                    tblperson.MSMemberSince, tblperson.MSNextRenewal, tblddi.DDIID,
                    IF(tblddi.DDIID IS NOT NULL, 1, 0) AS `DirectDebit`,
                    GROUP_CONCAT(DISTINCT tbltransfer.TransferID SEPARATOR ',') AS `OpenTransferIDs`
             FROM tblrenewal
             INNER JOIN tblperson ON (tblperson.PersonID = tblrenewal.PersonID) AND (tblperson.Deceased IS NULL)
             LEFT JOIN tblcountry ON tblcountry.ISO3166 = tblperson.ISO3166
             INNER JOIN tblpersonms ON (tblpersonms.PersonID = tblperson.PersonID) AND (tblpersonms.BeginDate <= UTC_TIMESTAMP()) AND ((tblpersonms.EndDate IS NULL) OR (tblpersonms.EndDate >= UTC_TIMESTAMP()))
             LEFT JOIN tblmsstatus ON tblmsstatus.MSStatusID = tblpersonms.MSStatusID
             INNER JOIN tblmsgrade ON (tblperson.Deceased IS NULL) AND (tblmsgrade.MSGradeID = tblpersonms.MSGradeID) AND (FIND_IN_SET('ismember', tblmsstatus.MSStatusFlags))
             LEFT JOIN tblmsgrade AS tblmsrenewalgrade ON tblmsrenewalgrade.MSGradeID = tblrenewal.MSGradeID
             LEFT JOIN tblddi ON (tblddi.PersonID = tblperson.PersonID) AND (tblperson.ISO4217 = 'GBP') AND (tblddi.InstructionScope = 'members') AND ".CondValidDDI()."
             LEFT JOIN tblwscategory ON tblwscategory.CategorySelector = 'members'
             LEFT JOIN tbltransfertoperson ON tbltransfertoperson.PersonID = tblperson.PersonID
             LEFT JOIN tbltransfer ON (tbltransfer.TransferID = tbltransfertoperson.TransferID) AND (tbltransfer.IsOpen <> 0)
             LEFT JOIN (SELECT tblinvoiceitem.InvoiceItemID, tblinvoiceitem.LinkedID, tblinvoiceitem.ItemQty, tblinvoiceitem.ItemUnitPrice, tblinvoiceitem.ItemVATRate,
	                           tblinvoiceitem.ItemNet, tblinvoiceitem.ItemVAT, tblinvoiceitem.ItemDate, tblinvoiceitem.DiscountID, 
                               COALESCE(tblinvoiceitem.Description, tblinvoiceitemtype.TypeName) AS `Description`,
	                           tblinvoiceitemtype.CategorySelector, tblinvoiceitemtype.Mnemonic,
                               tblinvoice.InvoiceID, tblinvoice.ISO4217, tblinvoice.InvoiceDate, tblinvoice.InvoiceDue,
			                   CONCAT_WS(' ', IF(tblinvoice.InvoiceNo IS NULL, IF(tblinvoice.InvoiceType = 'creditnote', 'Draft', 'Pro Forma'), ''),
                                              IF(tblinvoice.InvoiceType = 'creditnote', 'Credit Note', IF(tblinvoice.InvoiceNo IS NULL, CONCAT('#', CAST(tblinvoice.InvoiceID AS CHAR)), CONCAT('Invoice ', tblinvoice.InvoiceNo)))
                               ) AS `InvoiceCaption`
		                FROM tblinvoiceitem
                        INNER JOIN tblinvoice ON (tblinvoice.InvoiceID = tblinvoiceitem.InvoiceID) AND (tblinvoice.InvoiceType = 'invoice') AND (tblinvoice.InvoiceNo IS NULL)
                        INNER JOIN tblinvoiceitemtype ON (tblinvoiceitemtype.InvoiceItemTypeID = tblinvoiceitem.InvoiceItemTypeID)
             ) AS `tblinvoiceitem` ON (tblinvoiceitem.Mnemonic = 'ms_renewal') AND (tblinvoiceitem.LinkedID = tblrenewal.RenewalID)
             WHERE (NOT FIND_IN_SET('free', tblrenewal.RenewFlags)) AND (NOT FIND_IN_SET('norenewal', tblperson.MSFlags)) AND (tblinvoiceitem.InvoiceItemID IS NULL)
             GROUP BY tblrenewal.RenewalID";
            ProcessRecordsAndLog(
                $sql,
                "CreateMSRenewalItem",
                'Background Processor',
                'Creating membership renewal transactions. %affected% added.'
            );

            //Lapse records that have exceeded the grace period
            $sql = 
            "SELECT tblperson.PersonID, tblperson.MSNumber, tblperson.DoNotContact,
	                tblperson.Firstname, tblperson.Middlenames, tblperson.Lastname, tblperson.Title, tblperson.Gender, tblperson.ExtPostnominals,
                    CONCAT_WS(' ', tblperson.Title, tblperson.Firstname, tblperson.Middlenames, tblperson.Lastname) AS `Fullname`,
	                IF((tblperson.DoNotContact > 0) OR (tblperson.Deceased IS NOT NULL), 0, 1) AS `AllowInteraction`,
                    tblperson.MSNextRenewal,
                    DATE_SUB(CONVERT_TZ(tblperson.MSNextRenewal, 'Europe/London', 'UTC'), INTERVAL 1 SECOND) AS `CurrentStatusEnds`,                    
                    CONVERT_TZ(tblperson.MSNextRenewal, 'Europe/London', 'UTC') AS `NewStatusStarts`,                    
                    tblpersonms.PersonMSID, tblpersonms.MSGradeID,
	                tblpersonms.BeginDate, tblpersonms.EndDate, tblpersonms.MSFlags,
                    COALESCE(tblmsgrade.GradeCaption, '') AS `GradeCaption`,
                    tblmsstatus.MSStatusID,
                    COALESCE(tblmsstatus.MSStatusCaption, 'Not a Member') AS `MSStatusTxt`,
                    tblnewmsstatus.MSStatusID AS `NewMSStatusID`, tblnewmsstatus.MSStatusTxt AS `NewMSStatusTxt`,                
                    tblinvoiceitem.InvoiceID, tblinvoiceitem.InvoiceItemID, tblrenewal.RenewalID, tblddi.DDIID,
                    IF(tblddi.DDIID IS NOT NULL, 1, 0) AS `DirectDebit`,
                    COALESCE(tblinvoiceitem.DiscountID, tblrenewal.DiscountID) AS `DiscountID`
             FROM tblperson
             LEFT JOIN tblpersonms ON (tblpersonms.PersonID = tblperson.PersonID) AND (tblpersonms.BeginDate <= UTC_TIMESTAMP()) AND ((tblpersonms.EndDate IS NULL) OR (tblpersonms.EndDate >= UTC_TIMESTAMP()))
             LEFT JOIN tblmsstatus ON tblmsstatus.MSStatusID = tblpersonms.MSStatusID
             LEFT JOIN tblmsgrade ON (tblperson.Deceased IS NULL) AND (tblmsgrade.MSGradeID = tblpersonms.MSGradeID) AND (FIND_IN_SET('ismember', tblmsstatus.MSStatusFlags))
             LEFT JOIN  tblddi ON (tblddi.PersonID = tblperson.PersonID) AND (tblperson.ISO4217 = 'GBP') AND (tblddi.InstructionScope = 'members') 
             LEFT JOIN (SELECT tblmsstatus.MSStatusID, COALESCE(tblmsstatus.MSStatusCaption, 'Not a Member') AS `MSStatusTxt`
                        FROM tblmsstatus 
                        WHERE FIND_IN_SET('lapsed', tblmsstatus.MSStatusFlags)
                        ORDER BY tblmsstatus.MSStatusID 
                        LIMIT 1
             ) AS `tblnewmsstatus` ON TRUE
             LEFT JOIN tblrenewal ON tblrenewal.PersonID = tblperson.PersonID
             LEFT JOIN (SELECT tblinvoiceitem.InvoiceItemID, tblinvoiceitem.LinkedID, tblinvoiceitem.ItemQty, tblinvoiceitem.ItemUnitPrice, tblinvoiceitem.ItemVATRate,
	                           tblinvoiceitem.ItemNet, tblinvoiceitem.ItemVAT, tblinvoiceitem.ItemDate, tblinvoiceitem.DiscountID, 
                               COALESCE(tblinvoiceitem.Description, tblinvoiceitemtype.TypeName) AS `Description`,
	                           tblinvoiceitemtype.CategorySelector, tblinvoiceitemtype.Mnemonic,
                               tblinvoice.InvoiceID, tblinvoice.ISO4217, tblinvoice.InvoiceDate, tblinvoice.InvoiceDue,
			                   CONCAT_WS(' ', IF(tblinvoice.InvoiceNo IS NULL, IF(tblinvoice.InvoiceType = 'creditnote', 'Draft', 'Pro Forma'), ''),
                                              IF(tblinvoice.InvoiceType = 'creditnote', 'Credit Note', IF(tblinvoice.InvoiceNo IS NULL, CONCAT('#', CAST(tblinvoice.InvoiceID AS CHAR)), CONCAT('Invoice ', tblinvoice.InvoiceNo)))
                               ) AS `InvoiceCaption`
		                FROM tblinvoiceitem
                        INNER JOIN tblinvoice ON (tblinvoice.InvoiceID = tblinvoiceitem.InvoiceID) AND (tblinvoice.InvoiceType = 'invoice') AND (tblinvoice.InvoiceNo IS NULL)
                        INNER JOIN tblinvoiceitemtype ON (tblinvoiceitemtype.InvoiceItemTypeID = tblinvoiceitem.InvoiceItemTypeID)
                        WHERE tblinvoiceitem.Processed IS NULL
             ) AS `tblinvoiceitem` ON (tblinvoiceitem.Mnemonic = 'ms_renewal') AND (tblinvoiceitem.LinkedID = tblrenewal.RenewalID)                
             WHERE (tblperson.Deceased IS NULL) AND (NOT FIND_IN_SET('free', tblpersonms.MSFlags)) AND FIND_IN_SET('ismember', tblmsstatus.MSStatusFlags) AND (tblnewmsstatus.MSStatusID <> tblmsstatus.MSStatusID) AND (DATE_ADD(tblperson.MSNextRenewal, INTERVAL {$SYSTEM_SETTINGS['Membership']['RenewalGraceInterval']} MONTH) < CURRENT_DATE())
             GROUP BY tblperson.PersonID
             ";
            ProcessRecordsAndLog(
                $sql,
                "TransitionToLapsed",
                'Background Processor',
                'Moving membership status to lapsed. %affected% updated.'
            );            
            
            //Send out Renewal Reminders to non-DD payers with a non-zero transaction
            $sql = 
            "SELECT tblrenewal.RenewalID, tblrenewal.PersonID, tblrenewal.WSCategoryID, tblrenewal.MSGradeID,  tblrenewal.Created, tblrenewal.LastModified, tblrenewal.RenewFor, tblrenewal.RenewFlags,
	                tblwscategory.WSCategoryID, tblperson.PersonID,
                    tblperson.Firstname, tblperson.Middlenames, tblperson.Lastname, tblperson.Title, tblperson.Gender, tblperson.ExtPostnominals,
                    CONCAT_WS(' ', tblperson.Title, tblperson.Firstname, tblperson.Middlenames, tblperson.Lastname) AS `Fullname`,
                    COALESCE(tblmsrenewalgrade.MSGradeID, tblmsgrade.MSGradeID) AS `MSGradeID`,
                    COALESCE(tblmsrenewalgrade.GradeCaption, tblmsgrade.GradeCaption) AS `GradeCaption`,
                    tblperson.MSNumber, tblperson.DoNotContact, tblperson.ISO3166, 
                    COALESCE(tblinvoiceitem.ISO4217, tblperson.ISO4217) AS `ISO4217`,
                    tblperson.MSMemberSince, tblperson.MSNextRenewal,
                    IF(tblperson.MSNextRenewal < CURRENT_DATE(),
                        2,
                        IF(CURRENT_DATE() >= DATE_SUB(tblperson.MSNextRenewal, INTERVAL {$SYSTEM_SETTINGS["Membership"]['RenewalCycleStart']} DAY), 1, 0)
                    ) AS `RenewalCycle`,                    
                    tblinvoiceitem.InvoiceID
             FROM tblrenewal
             INNER JOIN tblperson ON (tblperson.PersonID = tblrenewal.PersonID) AND (tblperson.Deceased IS NULL) AND (tblperson.DoNotContact = 0)
             LEFT JOIN tblcountry ON tblcountry.ISO3166 = tblperson.ISO3166
             INNER JOIN tblpersonms ON (tblpersonms.PersonID = tblperson.PersonID) AND (tblpersonms.BeginDate <= UTC_TIMESTAMP()) AND ((tblpersonms.EndDate IS NULL) OR (tblpersonms.EndDate >= UTC_TIMESTAMP()))
             LEFT JOIN tblmsstatus ON tblmsstatus.MSStatusID = tblpersonms.MSStatusID
             INNER JOIN tblmsgrade ON (tblperson.Deceased IS NULL) AND (tblmsgrade.MSGradeID = tblpersonms.MSGradeID) AND (FIND_IN_SET('ismember', tblmsstatus.MSStatusFlags))
             LEFT JOIN tblmsgrade AS tblmsrenewalgrade ON tblmsrenewalgrade.MSGradeID = tblrenewal.MSGradeID
             LEFT JOIN tblddi ON (tblddi.PersonID = tblperson.PersonID) AND (tblperson.ISO4217 = 'GBP') AND (tblddi.InstructionScope = 'members')
             LEFT JOIN tblwscategory ON tblwscategory.CategorySelector = 'members'
             LEFT JOIN (SELECT tblinvoiceitem.InvoiceItemID, tblinvoiceitem.LinkedID, tblinvoiceitem.ItemQty, tblinvoiceitem.ItemUnitPrice, tblinvoiceitem.ItemVATRate,
	                           tblinvoiceitem.ItemNet, tblinvoiceitem.ItemVAT, tblinvoiceitem.ItemDate, tblinvoiceitem.DiscountID, 
                               COALESCE(tblinvoiceitem.Description, tblinvoiceitemtype.TypeName) AS `Description`,
	                           tblinvoiceitemtype.CategorySelector, tblinvoiceitemtype.Mnemonic,
                               tblinvoice.InvoiceID, tblinvoice.ISO4217, tblinvoice.InvoiceDate, tblinvoice.InvoiceDue,
			                   CONCAT_WS(' ', IF(tblinvoice.InvoiceNo IS NULL, IF(tblinvoice.InvoiceType = 'creditnote', 'Draft', 'Pro Forma'), ''),
                                              IF(tblinvoice.InvoiceType = 'creditnote', 'Credit Note', IF(tblinvoice.InvoiceNo IS NULL, CONCAT('#', CAST(tblinvoice.InvoiceID AS CHAR)), CONCAT('Invoice ', tblinvoice.InvoiceNo)))
                               ) AS `InvoiceCaption`
		                FROM tblinvoiceitem
                        INNER JOIN tblinvoice ON (tblinvoice.InvoiceID = tblinvoiceitem.InvoiceID) AND (tblinvoice.InvoiceType = 'invoice') AND (tblinvoice.InvoiceNo IS NULL)
                        INNER JOIN tblinvoiceitemtype ON (tblinvoiceitemtype.InvoiceItemTypeID = tblinvoiceitem.InvoiceItemTypeID)
                        WHERE tblinvoiceitem.Processed IS NULL
             ) AS `tblinvoiceitem` ON (tblinvoiceitem.Mnemonic = 'ms_renewal') AND (tblinvoiceitem.LinkedID = tblrenewal.RenewalID)
             WHERE ((tblperson.MSLastReminder <= DATE_SUB(CURRENT_DATE, INTERVAL {$SYSTEM_SETTINGS["Membership"]["RenewalReminderInterval"]} DAY)) OR (tblperson.MSLastReminder IS NULL))
                            AND
                   (NOT FIND_IN_SET('free', tblrenewal.RenewFlags))
                            AND
                   (NOT FIND_IN_SET('norenewal', tblperson.MSFlags))
                            AND
                   (tblddi.DDIID IS NULL)
                            AND
                   (tblinvoiceitem.InvoiceItemID IS NOT NULL)";
            ProcessRecordsAndLog(
                $sql,
                "SendMSRenewalReminders",
                'Background Processor',
                'Sending membership renewal reminders. %affected% added.'
            );
                   
            //Send annual feedback email; low priority item (see BGLimitFactor in the systems settings)
            $sql = 
            "SELECT tblperson.PersonID, tblperson.Firstname, tblperson.Middlenames, tblperson.Lastname, tblperson.Title, tblperson.Gender, tblperson.ExtPostnominals,
                    CONCAT_WS(' ', tblperson.Title, tblperson.Firstname, tblperson.Middlenames, tblperson.Lastname) AS `Fullname`,
                    tblmsgrade.MSGradeID, tblmsgrade.GradeCaption, tblmsstatus.MSStatusID, 
                    tblperson.MSNumber, tblperson.DoNotContact, tblperson.ISO3166, tblperson.ISO4217, tblperson.MSMemberSince, tblperson.MSNextRenewal,
                    MAX(tblpersontoemailtemplate.Recorded) AS `LastSent`,
                    'ms_survey_m6' AS `Mnemonic`,
                    GROUP_CONCAT(DISTINCT tblemail.Email ORDER BY EmailID SEPARATOR ',') AS `Emails`
             FROM tblperson
             INNER JOIN tblemail ON tblemail.PersonID = tblperson.PersonID
             LEFT JOIN tblcountry ON tblcountry.ISO3166 = tblperson.ISO3166
             INNER JOIN tblpersonms ON (tblpersonms.PersonID = tblperson.PersonID) AND (tblpersonms.BeginDate <= UTC_TIMESTAMP()) AND ((tblpersonms.EndDate IS NULL) OR (tblpersonms.EndDate >= UTC_TIMESTAMP()))
             LEFT JOIN tblmsstatus ON tblmsstatus.MSStatusID = tblpersonms.MSStatusID
             INNER JOIN tblmsgrade ON (tblperson.Deceased IS NULL) AND (tblmsgrade.MSGradeID = tblpersonms.MSGradeID) AND (FIND_IN_SET('ismember', tblmsstatus.MSStatusFlags))
             LEFT JOIN tblpersontoemailtemplate ON (tblpersontoemailtemplate.PersonID = tblperson.PersonID) AND (Mnemonic = 'ms_survey_m6')
             WHERE (tblperson.Deceased IS NULL) AND (tblperson.DoNotContact = 0) AND (CURRENT_DATE < tblperson.MSNextRenewal) AND (CURRENT_DATE >= DATE_SUB(tblperson.MSNextRenewal, INTERVAL 6 MONTH))
             GROUP BY tblperson.PersonID
             HAVING (LastSent IS NULL) OR (LastSent < DATE_SUB(tblperson.MSNextRenewal, INTERVAl 1 YEAR))
             ".(empty($SYSTEM_SETTINGS['System']['Email']['BGLimitFactor']) ? "" : "LIMIT ".max(intval($SYSTEM_SETTINGS['System']['Email']['BGLimitFactor']), 1)*$SYSTEM_SETTINGS['System']['Email']['MaxSendRate']);
            ProcessRecordsAndLog(
                $sql,
                "BGSendTemplate",
                'Background Processor',
                'Sending annual feedback requests. %affected% added.'
            );
            
            //Add or Reinstate Membership publications to eligible person records where these do not as yet exist
            $sql = 
            "SELECT tblperson.PersonID, tblperson.MSNumber, 
                    tblpublication.PublicationID, tblpublication.PublicationType, tblpublication.PublicationScope, tblpublication.Title,
                    tblpublicationtoperson.PublicationToPersonID, tblpublicationtoperson.Suspended
             FROM tblperson
             LEFT JOIN tblpersonms ON (tblpersonms.PersonID = tblperson.PersonID) AND (tblpersonms.BeginDate <= UTC_TIMESTAMP()) AND ((tblpersonms.EndDate IS NULL) OR (tblpersonms.EndDate >= UTC_TIMESTAMP()))
             LEFT JOIN tblmsstatus ON tblmsstatus.MSStatusID = tblpersonms.MSStatusID
             LEFT JOIN tblmsgrade ON (tblperson.Deceased IS NULL) AND (tblmsgrade.MSGradeID = tblpersonms.MSGradeID) AND (FIND_IN_SET('ismember', tblmsstatus.MSStatusFlags))
             LEFT JOIN tblpublication ON (tblpublication.PublicationScope <> 'public') AND (NOT FIND_IN_SET('optin', tblpublication.Flags))
             INNER JOIN tblpublicationrule ON (tblpublicationrule.PublicationID = tblpublication.PublicationID)
													           AND
								              (tblpublicationrule.RuleScope = 'indmember')
												               AND
								              ((tblpublicationrule.RuleFilter = 'none') OR ((tblpublicationrule.RuleFilter = 'grade') AND (tblpublicationrule.FilterValueInt = tblpersonms.MSGradeID)))
             LEFT JOIN tblpublicationtoperson ON (tblpublicationtoperson.PersonID = tblperson.PersonID) AND (tblpublicationtoperson.PublicationID = tblpublication.PublicationID)
             WHERE FIND_IN_SET('msbenefits', tblmsstatus.MSStatusFlags) AND (tblperson.DoNotContact = 0) AND (tblperson.Deceased IS NULL) AND ((tblpublicationtoperson.PublicationToPersonID IS NULL) OR (tblpublicationtoperson.Suspended <> 0))
             GROUP BY tblperson.PersonID, tblpublication.PublicationID";
            ProcessRecordsAndLog(
                $sql,
                "AddRestrictedPublications",
                'Background Processor',
                'Creating subscriptions for restricted publications. %affected% added.'
            );             
            
            //Suspend or Remove Membership only publications for non-members/overdue members
            $sql = 
            "SELECT tblpublicationtoperson.PublicationToPersonID, tblpublicationtoperson.PublicationToPersonID AS `SubscriptionID`, tblpublicationtoperson.Suspended,
	                tblperson.PersonID, tblperson.MSNumber, tblperson.DoNotContact, tblperson.Deceased,
                    tblperson.Firstname, tblperson.Middlenames, tblperson.Lastname, tblperson.Title, tblperson.Gender, tblperson.ExtPostnominals,
                    CONCAT_WS(' ', tblperson.Title, tblperson.Firstname, tblperson.Middlenames, tblperson.Lastname) AS `Fullname`,
                    IF(FIND_IN_SET('free', tblpersonms.MSFlags), 1, 0) AS `MSFree`,
                    IF((tblperson.DoNotContact > 0) OR (tblperson.Deceased IS NOT NULL), 0, 1) AS `AllowInteraction`,
	                tblpersonms.PersonMSID, tblpersonms.MSGradeID,
                    COALESCE(tblmsgrade.GradeCaption, '') AS `GradeCaption`,
                    tblmsstatus.MSStatusID,
	                COALESCE(tblmsstatus.MSStatusCaption, 'Not a Member') AS `MSStatusTxt`,
	                IF(tblperson.Deceased IS NULL AND FIND_IN_SET('ismember', tblmsstatus.MSStatusFlags), 1, 0) AS `IsMember`,
                    IF(tblperson.Deceased IS NULL AND FIND_IN_SET('msbenefits', tblmsstatus.MSStatusFlags), 1, 0) AS `MSBenefits`,
	                tblpublicationtoperson.Complimentary, tblpublicationtoperson.CustomerReference,
                    tblpublicationtoperson.Qty, tblpublicationtoperson.StartDate, tblpublicationtoperson.EndDate, tblpublicationtoperson.LastReminder,
                    tblpublication.PublicationID, tblpublication.PublicationType, tblpublication.PublicationScope, tblpublication.Title,
                    IF(((tblpublication.PublicationScope = 'public') AND FIND_IN_SET('autosubscribe', tblpublication.Flags))
                                        OR
                       ((tblpublication.PublicationScope = 'members') AND NOT FIND_IN_SET('optin', tblpublication.Flags)),
	                1, 0) AS `AutoManaged`,
                    IF(FIND_IN_SET('optin', tblpublication.Flags), 1, 0) AS `Optin`,
                    IF(FIND_IN_SET('autosubscribe', tblpublication.Flags), 1, 0) AS `AutoSubscribe`,
                    COUNT(DISTINCT(tblpublicationrule.PublicationRuleID)) AS `RuleCount`
             FROM tblpublicationtoperson
             INNER JOIN tblpublication ON tblpublication.PublicationID = tblpublicationtoperson.PublicationID
             LEFT JOIN tblperson ON tblperson.PersonID = tblpublicationtoperson.PersonID
             LEFT JOIN tblpersonms ON (tblpersonms.PersonID = tblperson.PersonID) AND (tblpersonms.BeginDate <= UTC_TIMESTAMP()) AND ((tblpersonms.EndDate IS NULL) OR (tblpersonms.EndDate >= UTC_TIMESTAMP()))
             LEFT JOIN tblmsstatus ON tblmsstatus.MSStatusID = tblpersonms.MSStatusID
             LEFT JOIN tblmsgrade ON (tblperson.Deceased IS NULL) AND (tblmsgrade.MSGradeID = tblpersonms.MSGradeID) AND (FIND_IN_SET('ismember', tblmsstatus.MSStatusFlags))
             LEFT JOIN tblpublicationrule ON (tblpublicationrule.PublicationID = tblpublication.PublicationID) 
										                  AND
                                             (tblpublicationrule.RuleScope = IF(tblperson.Deceased IS NULL AND FIND_IN_SET('ismember', tblmsstatus.MSStatusFlags), 'indmember', 'indnonmember'))
                                                          AND
                                             ((tblpublicationrule.RuleFilter = 'none') OR ((tblpublicationrule.RuleFilter = 'grade') AND (tblpublicationrule.FilterValueInt = tblpersonms.MSGradeID)))
            WHERE (tblpublicationtoperson.Qty <> 0) AND (tblpublication.PublicationScope <> 'public') AND (tblpublicationtoperson.Complimentary = 0) AND (tblpublicationtoperson.EndDate IS NULL) AND (NOT FIND_IN_SET('msbenefits', tblmsstatus.MSStatusFlags))
            GROUP BY tblpublicationtoperson.PublicationToPersonID
            ";
            ProcessRecordsAndLog(
                $sql,
                "CancelRestrictedPublications",
                'Background Processor',
                'Updating restricted publication subscriptions. %affected% updated.'
            );               


            //BG Processing complete
            $endProcessing = microtime(true);
            AddToSysLog(array('IsSystem' => TRUE, 'Caption' => 'Background Processor', 'Description' => 'The background processor has completed. Duration was '.mtDuration($startProcessing, $endProcessing), 'Expiry' => $SYSTEM_SETTINGS['ExpiryPolicies']['BGProc']));
        } catch( Exception $e ) {
            AddToSysLog(array('EntryKind' => 'error', 'IsSystem' => TRUE, 'Expiry' => $SYSTEM_SETTINGS['ExpiryPolicies']['LogErrors'], 'Caption' => 'Background Processor', 'Description' => $e->getMessage().' ('.$e->getCode().')'));
        }
        fwrite($handle, time());
        flock($handle, LOCK_UN);
    } else {
        if(defined('__DEBUGMODE') && __DEBUGMODE) {
            AddToSysLog(array('EntryKind' => 'warning', 'IsSystem' => TRUE, 'Expiry' => $SYSTEM_SETTINGS['ExpiryPolicies']['LogErrors'], 'Caption' => 'Background Processor', 'Description' => 'Warning: unable to obtain file lock: background processing aborted'));
        }
    }
    fclose($handle);
}

/**
 * GARBAGE COLLECTOR
 */
if(!empty($_GET['gc']) || isset($options['gc'])) {
    $handle = fopen(CONSTLocalStorageRoot."_gc.lock", "w+");
    if (flock($handle, LOCK_EX | LOCK_NB)) {
        try {
            //Start Garbage Collection
            $startProcessing = microtime(true);
            AddToSysLog(array('IsSystem' => TRUE, 'Caption' => 'Garbage Collection', 'Description' => 'Garbage Collection is starting.', 'Expiry' => $SYSTEM_SETTINGS['ExpiryPolicies']['BGProc']));
            
            //Remove expired records
            $sql =
            "DELETE FROM tblsyslog WHERE Expiry < UTC_TIMESTAMP()";
            ExecuteAndLog($sql, 'Garbage Collector', 'Removing expired system log entries. %affected% deleted.');
            $sql =
            "DELETE FROM tblnote WHERE Expires < UTC_TIMESTAMP()";
            ExecuteAndLog($sql, 'Garbage Collector', 'Removing expired notes. %affected% deleted.');
            $sql =
            "DELETE FROM tbldataprotection WHERE (Closed IS NOT NULL) AND (DATE_ADD(Recorded, INTERVAL {$SYSTEM_SETTINGS['ExpiryPolicies']['DPEntries']} MONTH) < UTC_TIMESTAMP())";
            ExecuteAndLog($sql, 'Garbage Collector', 'Removing data protection entries older than '.SinPlu($SYSTEM_SETTINGS['ExpiryPolicies']['DPEntries'], 'month').'. %affected% deleted.');
            
            //Remove discount codes that have expired or have ref count of zero
            $sql =
            "DELETE tbldiscounttoperson
             FROM tbldiscounttoperson
             LEFT JOIN tbldiscount ON tbldiscount.DiscountID = tbldiscounttoperson.DiscountID
             WHERE (tbldiscounttoperson.RefCount = 0) OR (tbldiscounttoperson.Expires < UTC_TIMESTAMP()) OR ((tbldiscount.ValidUntil IS NOT NULL) AND (tbldiscount.ValidUntil < UTC_TIMESTAMP()))";
            ExecuteAndLog($sql, 'Garbage Collector', 'Removing used and expired discount codes from person records. %affected% deleted.');
            $sql =
            "DELETE tbldiscounttoorganisation
             FROM tbldiscounttoorganisation
             LEFT JOIN tbldiscount ON tbldiscount.DiscountID = tbldiscounttoorganisation.DiscountID
             WHERE (tbldiscounttoorganisation.RefCount = 0) OR (tbldiscounttoorganisation.Expires < UTC_TIMESTAMP()) OR ((tbldiscount.ValidUntil IS NOT NULL) AND (tbldiscount.ValidUntil < UTC_TIMESTAMP()))";
            ExecuteAndLog($sql, 'Garbage Collector', 'Removing used and expired discount codes from organisation records. %affected% deleted.');
            
            //GC Complete            
            $endProcessing = microtime(true);
            AddToSysLog(array('IsSystem' => TRUE, 'Caption' => 'Garbage Collection', 'Description' => 'Garbage Collection is completed. Duration was '.mtDuration($startProcessing, $endProcessing), 'Expiry' => $SYSTEM_SETTINGS['ExpiryPolicies']['BGProc']));
        } catch( Exception $e ) {
            AddToSysLog(array('EntryKind' => 'error', 'IsSystem' => TRUE, 'Expiry' => $SYSTEM_SETTINGS['ExpiryPolicies']['LogErrors'], 'Caption' => 'Garbage Collection error', 'Description' => $e->getMessage().' ('.$e->getCode().')'));
        }
        fwrite($handle, time());
        flock($handle, LOCK_UN);
    } else {
        if(defined('__DEBUGMODE') && __DEBUGMODE) {
            AddToSysLog(array('EntryKind' => 'warning', 'IsSystem' => TRUE, 'Expiry' => $SYSTEM_SETTINGS['ExpiryPolicies']['LogErrors'], 'Caption' => 'Garbage Collection', 'Description' => 'Warning: unable to obtain file lock: garbage collection aborted'));
        }
    }
    fclose($handle);    
}

/**
 * EMAIL QUEUE
 */
if(!empty($_GET['mq']) || isset($options['mq'])) {
    $handle = fopen(CONSTLocalStorageRoot."_mq.lock", "w+");
    if (flock($handle, LOCK_EX | LOCK_NB)) {
        try {
            //Process the Email Queue
            ProcessMailQueue($SYSTEM_SETTINGS['System']['Email']['SliceSize']);
        } catch( Exception $e ) {
            AddToSysLog(array('EntryKind' => 'error', 'IsSystem' => TRUE, 'Expiry' => $SYSTEM_SETTINGS['ExpiryPolicies']['LogErrors'], 'Caption' => 'Email Queue', 'Description' => $e->getMessage().' ('.$e->getCode().')'));
        }
        fwrite($handle, time());
        flock($handle, LOCK_UN);
    } else {
        if(defined('__DEBUGMODE') && __DEBUGMODE) {
            AddToSysLog(array('EntryKind' => 'warning', 'IsSystem' => TRUE, 'Expiry' => $SYSTEM_SETTINGS['ExpiryPolicies']['LogErrors'], 'Caption' => 'Email Queue', 'Description' => 'Warning: unable to obtain file lock: mail queue processing aborted'));
        }
    }
    fclose($handle);    
}

/**
 * FINANCE DATA EXPORT
 */
if(!empty($_GET['fe']) || isset($options['fe'])) {
    $handle = fopen(CONSTLocalStorageRoot."_fe.lock", "w+");
    if (flock($handle, LOCK_EX | LOCK_NB)) {
        try {
            //Execute the finance export
            if(!empty($SYSTEM_SETTINGS['Finance']['Export']['Enabled'])) {
                //Get documents which have not yet been exported 
                $FY = FinancialYear();
                $sql = 
                "SELECT tblinvoice.InvoiceID,
		                IF((SUM(tblinvoiceitem.ItemVAT+tblinvoiceitem.ItemNet) = tblmoney.AllocatedAmount) OR ((tblinvoice.InvoiceType = 'creditnote') AND (tblinvoice.InvoiceNo IS NOT NULL)), 1, 0) AS `Settled`,
                        IF(tblinvoice.InvoiceDate < '{$FY['Start']['AsString']}',
                           'P',
                           IF(tblinvoice.InvoiceDate > '{$FY['End']['AsString']}', 'N', 'C') 
                        ) AS `FinYear`,
                        IF(tblinvoice.InvoiceDate < '{$FY['Start']['AsString']}',
                            12 - ABS(TIMESTAMPDIFF(MONTH, '{$FY['Start']['AsString']}', tblinvoice.InvoiceDate) MOD 12),
                            (TIMESTAMPDIFF(MONTH, '{$FY['Start']['AsString']}', tblinvoice.InvoiceDate) MOD 12)+1
                        ) AS `AccPeriod`
                 FROM tblinvoice
                 LEFT JOIN tblcurrency ON tblcurrency.ISO4217 = tblinvoice.ISO4217
                 LEFT JOIN tblinvoiceitem ON tblinvoiceitem.InvoiceID = tblinvoice.InvoiceID
                 LEFT JOIN (SELECT tblmoneytoinvoice.MoneyToInvoiceID, tblmoneytoinvoice.InvoiceID,
                                   SUM(IF(tblmoney.Reversed IS NULL, tblmoneytoinvoice.AllocatedAmount, 0)) AS `AllocatedAmount`,
                                   COUNT(DISTINCT tblmoneytoinvoice.MoneyToInvoiceID) AS `AllocatedCount`
                            FROM tblmoneytoinvoice
                            INNER JOIN tblmoney ON tblmoney.MoneyID = tblmoneytoinvoice.MoneyID
                            GROUP BY tblmoneytoinvoice.InvoiceID 
                 ) AS tblmoney ON tblmoney.InvoiceID = tblinvoice.InvoiceID
                 LEFT JOIN tblinvoicetoperson ON tblinvoicetoperson.InvoiceID = tblinvoice.InvoiceID
                 LEFT JOIN tblperson ON tblperson.PersonID = tblinvoicetoperson.PersonID
                 LEFT JOIN tblinvoicetoorganisation ON tblinvoicetoorganisation.InvoiceID = tblinvoice.InvoiceID
                 LEFT JOIN tblorganisation ON tblorganisation.OrganisationID = tblinvoicetoorganisation.OrganisationID
                 WHERE (tblinvoice.InvoiceNo IS NOT NULL) AND (tblinvoice.EDISent IS NULL)
                 GROUP BY tblinvoice.InvoiceID
                 ".($SYSTEM_SETTINGS['Finance']['Export']['SettledOnly'] ? "HAVING Settled = 1": "")."
                 ORDER BY InvoiceID ASC";
                if($query = mysqli_query($SYSTEM_SETTINGS['Database'], $sql)) {
                    $filename = tempnam(sys_get_temp_dir(), 'exp');
                    $exphandle = fopen($filename, 'w');
                    if ($exphandle) {
                        WriteBOM($exphandle, $SYSTEM_SETTINGS['Finance']['Export']['BOM']);
                        //Write header
                        if($SYSTEM_SETTINGS['Finance']['Export']['Header']) {
                            $fieldcount = 0;
                            foreach($SYSTEM_SETTINGS["Finance"]['Export']['Fields'] AS $field => $settings)
                            {
                                if((strcasecmp($field, 'ID') == 0) && ($fieldcount == 0) && !empty($SYSTEM_SETTINGS["Finance"]['Export']['ExcelCSV'])) {
                                    $field = 'id';
                                }
                                fwrite($exphandle, ($fieldcount > 0 ? ',' : ''). '"'.addcslashes($field, '"').'"');
                                $fieldcount++;
                            }
                            fwrite($exphandle, "\r\n");
                        }
                        //Iterate through each invoice
                        $outputcount = 0;
                        while($row = mysqli_fetch_assoc($query)) {
                            $INVOICE = new crmInvoice('eventsFinance', $SYSTEM_SETTINGS['Database'], $row['InvoiceID'], InvoiceSettings());
                            if($INVOICE->Found) {
                                //$invoicedata = array_merge($INVOICE->Invoice, $row);
                                $invoiceitems = $INVOICE->InvoiceItems;
                                foreach($invoiceitems AS $invoiceitem) {
                                    $data = array_merge($INVOICE->Invoice, $row, $invoiceitem);
                                    $fieldcount = 0;
                                    foreach($SYSTEM_SETTINGS["Finance"]['Export']['Fields'] AS $field => $settings) {
                                        $output = "";
                                        if(!is_array($settings)) {
                                            if(is_null($settings) || ($settings === '')) {
                                                $output = $settings;
                                            } else {
                                                if(isset($data[$settings])) {
                                                    $output = $data[$settings];
                                                } else {
                                                    $output = null;
                                                }
                                            }
                                        } elseif(!is_assoc($settings)) {
                                            $values = array();
                                            //Concatenate multiple fields
                                            foreach($settings AS $fieldname) {
                                                $values[] = (isset($data[$fieldname]) ? $data[$fieldname] : "");
                                            }
                                            $output = implode(' ', $values);
                                        } else {
                                            $cont = FALSE;
                                            if(isset($settings['if'])) {
                                                if(isset($settings['if']['field'])) {
                                                    $testfield = $settings['if']['field'];
                                                    $testvalue = (isset($data[$testfield]) ? $data[$testfield] : null);
                                                    $operator = (isset($settings['if']['operator']) ? trim($settings['if']['operator']) : '=');
                                                    $comparisonvalue = (isset($settings['if']['compareto']) ? $settings['if']['compareto'] : null);
                                                    switch($operator) {
                                                        case '<':
                                                            $cont = ($testvalue < $comparisonvalue);
                                                            break;
                                                        case '>':
                                                            $cont = ($testvalue > $comparisonvalue);
                                                            break;
                                                        case '<=':
                                                        case '=<':
                                                            $cont = ($testvalue <= $comparisonvalue);
                                                            break;
                                                        case '=>':
                                                        case '>=':
                                                            $cont = ($testvalue >= $comparisonvalue);
                                                            break;
                                                        case '<>':
                                                            $cont = ($testvalue <> $comparisonvalue);
                                                            break;
                                                        case 'is':
                                                        case '==':
                                                        default:
                                                            $cont = ($testvalue == $comparisonvalue);
                                                    }
                                                }
                                                
                                            } else {
                                                $cont = TRUE;
                                            }
                                            if($cont) {
                                                if(isset($settings['static'])) {
                                                    $output = $settings['static'];
                                                } else {
                                                    $fieldname = $settings['field'];
                                                    $output = (isset($data[$fieldname]) ? $data[$fieldname] : null);
                                                    if(isset($settings['mappings'])) {
                                                        foreach($settings['mappings'] AS $lookfor => $replacewith) {
                                                            if(strcasecmp($output, $lookfor) == 0) {
                                                                $output = $replacewith;
                                                                break;
                                                            }
                                                        }
                                                    }
                                                    $fieldtype = (isset($settings['fieldtype']) ? $settings['fieldtype'] : (is_numeric($output) ? (ctype_digit($output) ? 'integer' : 'float') : 'string'));
                                                }
                                            } else {
                                                $output = (isset($settings['if']['false']) ? $settings['if']['false'] : null);
                                                $fieldtype = (isset($settings['falsefieldtype']) ? $settings['falsefieldtype'] : (isset($settings['fieldtype']) ? $settings['fieldtype'] : (is_numeric($output) ? (ctype_digit($output) ? 'integer' : 'float') : 'string')));
                                            }
                                            switch($fieldtype) {
                                                case 'integer':
                                                case 'float':
                                                    $output = strval($output);
                                                    break;
                                                case 'date':
                                                    $output = date((empty($settings['format']) ? 'Y-m-d' : $settings['format']), strtotime($output.(!empty($settings['UTC']) ? 'UTC' : '')));
                                                    break;
                                                case 'gmdate':
                                                    $output = gmdate((empty($settings['format']) ? 'Y-m-d' : $settings['format']), strtotime($output.(!empty($settings['UTC']) ? 'UTC' : '')));
                                                    break;
                                                case 'scaledinteger':
                                                    $output = ScaledIntegerAsString($output, "scaledinteger", 100, TRUE);
                                                    break;
                                                case 'scaledintegerNN':
                                                    if(!is_null($output)) {
                                                        $output = ScaledIntegerAsString($output, "scaledinteger", 100, TRUE);
                                                    }
                                                    break;
                                            }
                                        }
                                        if(is_null($output)) {
                                            fwrite($exphandle, ($fieldcount > 0 ? ',' : ''));
                                        } elseif(strlen($output) == 0) {
                                            fwrite($exphandle, ($fieldcount > 0 ? ',' : '').'""');
                                        } else {
                                            if(is_numeric($output)) {
                                                if(!empty($SYSTEM_SETTINGS["Finance"]['Export']['ExcelCSV'])) {
                                                    fwrite($exphandle, ($fieldcount > 0 ? ',' : '').'"=""'.addcslashes($output, '"').'"""');
                                                } elseif(!empty($SYSTEM_SETTINGS["Finance"]['Export']['QuoteAll'])) {
                                                    fwrite($exphandle, ($fieldcount > 0 ? ',' : '').'"'.addcslashes(strval($output), '"').'"');
                                                } else {
                                                    fwrite($exphandle, ($fieldcount > 0 ? ',' : '').$output);
                                                }
                                            } else {
                                                fwrite($exphandle, ($fieldcount > 0 ? ',' : '').'"'.addcslashes($output, '"').'"');
                                            }
                                        }
                                        $fieldcount++;
                                    }
                                    fwrite($exphandle, "\r\n");
                                    $outputcount++;
                                }
                                if(empty($SYSTEM_SETTINGS["Finance"]['Export']['Testmode'])) {
                                    $sql = "UPDATE tblinvoice SET EDISent = UTC_TIMESTAMP() WHERE InvoiceID = ".$INVOICE->InvoiceID;
                                    if(mysqli_query($SYSTEM_SETTINGS["Database"], $sql)) {
                                    
                                    } else {
                                        throw new crmException('Unable to update invoice record (EDISent): '.mysqli_error($SYSTEM_SETTINGS['Database']), mysqli_errno($SYSTEM_SETTINGS['Database']));
                                    }
                                }
                            } else {
                                throw new crmException('Unable to load invoice #'.$row['InvoiceID']);
                            }
                        }
                        fclose($exphandle);
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
                        $doctitle = "Finance export ".date('d M Y');
                        $docfilename = mysqli_real_escape_string($SYSTEM_SETTINGS["Database"], FilenameStr($SYSTEM_SETTINGS['System']['SystemName']))." finance data ".date('Ymd').".csv";
                        $sql =
                        "INSERT INTO tbldocument (LastModified, DocTitle, `Filename`, `Mimetype`, Bucket, Objectname, Data, Expires)
                         VALUES (UTC_TIMESTAMP(),
                            '{$doctitle}',
                            '{$docfilename}',
                            'text/csv',
                            ".($stored && ($SYSTEM_SETTINGS["System"]["FileStore"] == 'S3') ? "'".mysqli_real_escape_string($SYSTEM_SETTINGS['Database'], $SYSTEM_SETTINGS['Storage']['Bucket'])."'" : "NULL").",
                            ".($stored && ($SYSTEM_SETTINGS["System"]["FileStore"] == 'S3') ? "'".mysqli_real_escape_string($SYSTEM_SETTINGS['Database'], $objectname)."'" : "NULL").",
                            ".(!$stored ? "LOAD_FILE(\"".mysqli_real_escape_string($SYSTEM_SETTINGS["Database"], $filename)."\")" : "NULL").",
                            DATE_ADD(UTC_TIMESTAMP(), INTERVAL ".max(1, intval($SYSTEM_SETTINGS["ExpiryPolicies"]['Export']))." DAY)
                         )";                        
                        if(mysqli_query($SYSTEM_SETTINGS["Database"], $sql)) {
                            $documentid = mysqli_insert_id($SYSTEM_SETTINGS["Database"]);
                            $sql = 
                            "INSERT INTO tblrecentfile (DocumentID) VALUES ({$documentid})";
                            if(mysqli_query($SYSTEM_SETTINGS["Database"], $sql)) {
                                AddToSysLog(array('EntryKind' => 'success', 'IsSystem' => TRUE, 'Caption' => 'Finance Export', 'Description' => 'Finance export completed: '.$docfilename));
                            } else {
                                throw new crmException('Unable to link document record: '.mysqli_error($SYSTEM_SETTINGS['Database']), mysqli_errno($SYSTEM_SETTINGS['Database']));
                            }
                        } else {
                            throw new crmException('Unable to create document record: '.mysqli_error($SYSTEM_SETTINGS['Database']), mysqli_errno($SYSTEM_SETTINGS['Database']));
                        }
                        unlink($filename);
                    } else {
                        $error = error_get_last();
                        throw new crmException('Unable to create temporary file for export: '.$error['message'], $error['type']);
                    }
                } else {
                    throw new crmException('Unable to retrieve invoice list for export: '.mysqli_error($SYSTEM_SETTINGS['Database']), mysqli_errno($SYSTEM_SETTINGS['Database']));
                }
            }
        } catch( Exception $e ) {
            AddToSysLog(array('EntryKind' => 'error', 'IsSystem' => TRUE, 'Expiry' => $SYSTEM_SETTINGS['ExpiryPolicies']['LogErrors'], 'Caption' => 'Finance Export', 'Description' => $e->getMessage().' ('.$e->getCode().')'));
        }
        fwrite($handle, time());
        flock($handle, LOCK_UN);
    } else {
        if(defined('__DEBUGMODE') && __DEBUGMODE) {
            AddToSysLog(array('EntryKind' => 'warning', 'IsSystem' => TRUE, 'Expiry' => $SYSTEM_SETTINGS['ExpiryPolicies']['LogErrors'], 'Caption' => 'Finance Export', 'Description' => 'Warning: unable to obtain file lock: finance export aborted'));
        }
    }
    fclose($handle);    
} 

/**
 * ROW PROCESSING FUNCTIONS 
 */
 
function DeleteRenewalItem($datarow, $SYSTEM_SETTINGS) {
    $Result = FALSE;
    if(!empty($datarow['InvoiceItemID'])) {
        CancelInvoiceItem($datarow['InvoiceItemID']);
    }
    $sql = "DELETE FROM tblrenewal WHERE tblrenewal.RenewalID = ".intval($datarow['RenewalID']);
    if (mysqli_query($SYSTEM_SETTINGS["Database"], $sql)) {
        $Result = (mysqli_affected_rows($SYSTEM_SETTINGS["Database"]) > 0 ? TRUE : FALSE);
        AddHistory(array('type' => 'delete', 'description' => 'Renewal transaction removed', 'PersonID' => $datarow['PersonID'], 'flags' => 'system'), $Result);
        if(!empty($datarow['DiscountID'])) {
            AdjustDiscountRefCount(array('PersonID' => $datarow['PersonID'], 'DiscountID' => $datarow['DiscountID']));
        }
    }
    return $Result;
}

function AddRestrictedPublications($datarow, $SYSTEM_SETTINGS) {
    $Result = FALSE;
    if(!empty($datarow['PublicationToPersonID'])) {
        //Was suspended, reinstate
        $setSQL = new stmtSQL('UPDATE', 'tblpublicationtoperson', $SYSTEM_SETTINGS["Database"]);
        $setSQL->addWhere('PublicationToPersonID', 'integer', $datarow['PublicationToPersonID']);
        $setSQL->addField('Suspended', 'integer', 0);
        if(mysqli_query($SYSTEM_SETTINGS["Database"], $setSQL->SQL())) {
            $Result = (mysqli_affected_rows($SYSTEM_SETTINGS["Database"]) > 0 ? TRUE : FALSE);
            AddHistory(array('type' => 'edit', 'description' => 'Reinstated: '.$datarow['Title'], 'PersonID' => $datarow['PersonID'], 'flags' => 'system'), $Result);
        }        
    } else {
        //Create a new subscription record
        $setSQL = new stmtSQL('INSERT', 'tblpublicationtoperson', $SYSTEM_SETTINGS["Database"]);
        $setSQL->addField('PublicationID', 'integer', $datarow['PublicationID']);
        $setSQL->addField('PersonID', 'integer', $datarow['PersonID']);
        $setSQL->addField('Qty', 'integer', 1);
        //$setSQL->addFieldStmt('StartDate', 'CURRENT_DATE');
        if(mysqli_query($SYSTEM_SETTINGS["Database"], $setSQL->SQL())) {
            $Result = (mysqli_affected_rows($SYSTEM_SETTINGS["Database"]) > 0 ? TRUE : FALSE);
            AddHistory(array('type' => 'edit', 'description' => 'Subscribed: '.$datarow['Title'], 'PersonID' => $datarow['PersonID'], 'flags' => 'system'), $Result);
        }
    }
    return $Result;
}

function RenewFreeMembers($datarow, $SYSTEM_SETTINGS) {
    $Result = FALSE;
    try {
        if(StartTransaction($SYSTEM_SETTINGS["Database"])) {
            if($datarow['MSStatusID'] <> $datarow['NewMSStatusID']) {
                //Change of status - create ms history record in accordance
                if(!empty($datarow['PersonMSID']) && empty($datarow['EndDate'])) {
                    //Close the previous entry
                    $sql = "UPDATE tblpersonms SET EndDate = '{$datarow['ChangeDateTime']}' WHERE PersonMSID = {$datarow['PersonMSID']}";
                    if (!mysqli_query($SYSTEM_SETTINGS["Database"], $sql)) {
                        throw new crmException('Unable to update Membership History record: '.mysqli_error($SYSTEM_SETTINGS["Database"]), mysqli_errno($SYSTEM_SETTINGS["Database"]));
                    }
                }
                $setSQL = new stmtSQL('INSERT', 'tblpersonms', $SYSTEM_SETTINGS["Database"]);
                $setSQL->addField('PersonID', 'integer', $datarow['PersonID']);
                $setSQL->addField('BeginDate', 'datetime', $datarow['ChangedDateTime']); 
                $setSQL->addField('MSStatusID', 'integer', $datarow['NewMSStatusID']);
                $setSQL->addField('MSGradeID', 'integer', $datarow['MSGradeID']);
                $setSQL->addField('MSFlags', 'set', 'free');
                if (!mysqli_query($SYSTEM_SETTINGS["Database"], $setSQL->SQL())) {
                    throw new crmException('Unable to create membership history record: '.mysqli_error($SYSTEM_SETTINGS["Database"]), mysqli_errno($SYSTEM_SETTINGS["Database"]));
                }
            }
            $sql = "UPDATE tblperson SET MSNextRenewal = '{$datarow['NextMSNextRenewal']}', MSLastReminder = NULL WHERE PersonID = {$datarow['PersonID']}";
            if (mysqli_query($SYSTEM_SETTINGS["Database"], $sql)) {
                SendEmailTemplate(
                    'ms_renewed_free',
                    array('Data' => $datarow)
                );
                AddHistory(array('type' => 'delete', 'description' => 'Free Membership renewed to '.$datarow['NextMSNextRenewal'], 'PersonID' => $datarow['PersonID'], 'flags' => 'system'));
                $Result = TRUE;
            } else {
                throw new crmException('Unable to update person record while renewing free membership: '.mysqli_error($SYSTEM_SETTINGS["Database"]), mysqli_errno($SYSTEM_SETTINGS["Database"]));
            }            
            mysqli_commit($SYSTEM_SETTINGS["Database"]);
        } else {
            throw new crmException('Unable to renew free membership: '.mysqli_error($SYSTEM_SETTINGS["Database"]), mysqli_errno($SYSTEM_SETTINGS["Database"]));
        }
    } catch( Exception $e ) {
        mysqli_rollback($SYSTEM_SETTINGS["Database"]);
        $Result = FALSE;
        AddToSysLog(array('EntryKind' => 'error', 'IsSystem' => TRUE, 'Expiry' => $SYSTEM_SETTINGS['ExpiryPolicies']['LogErrors'], 'Caption' => 'Background Processor', 'Description' => $e->getMessage().' ['.$e->getCode().']', 'Data' => $datarow));
    }
    return $Result;            
}            

function CancelRestrictedPublications($datarow, $SYSTEM_SETTINGS) {
    $Result = FALSE;
    try {
        if(StartTransaction($SYSTEM_SETTINGS["Database"])) {
            if($datarow['IsMember']) {
                if(empty($datarow['MSBenefits'])) {
                    //Suspend the subscription
                    $setSQL = new stmtSQL('UPDATE', 'tblpublicationtoperson', $SYSTEM_SETTINGS["Database"]);
                    $setSQL->addWhere('PublicationToPersonID', 'integer', $datarow['PublicationToPersonID']);
                    $setSQL->addField('Suspended', 'integer', 1);
                    //$setSQL->addFieldStmt('LastReminder', 'UTC_TIMESTAMP()');
                    if(mysqli_query($SYSTEM_SETTINGS["Database"], $setSQL->SQL())) {
                        $Result = (mysqli_affected_rows($SYSTEM_SETTINGS["Database"]) > 0 ? TRUE : FALSE);
                        AddHistory(array('type' => 'edit', 'description' => 'Suspended: '.$datarow['Title'], 'PersonID' => $datarow['PersonID'], 'flags' => 'system'), $Result);
                    }
                }
            } else {
                //Delete the subscription altogether
                $sql = "DELETE FROM tblpublicationtoperson WHERE tblpublicationtoperson.PublicationToPersonID = ".intval($datarow['PublicationToPersonID']);
                if(mysqli_query($SYSTEM_SETTINGS["Database"], $sql)) {
                    $Result = (mysqli_affected_rows($SYSTEM_SETTINGS["Database"]) > 0 ? TRUE : FALSE);
                    AddHistory(array('type' => 'delete', 'description' => 'Cancelled: '.$datarow['Title'], 'PersonID' => $datarow['PersonID'], 'flags' => 'system'), $Result);
                }
            }
            mysqli_commit($SYSTEM_SETTINGS["Database"]);
        } else {
            throw new crmException('Unable to adjust publication subscription: '.mysqli_error($SYSTEM_SETTINGS["Database"]), mysqli_errno($SYSTEM_SETTINGS["Database"]));
        }
    } catch( Exception $e ) {
        mysqli_rollback($SYSTEM_SETTINGS["Database"]);
        $Result = FALSE;
        AddToSysLog(array('EntryKind' => 'error', 'IsSystem' => TRUE, 'Expiry' => $SYSTEM_SETTINGS['ExpiryPolicies']['LogErrors'], 'Caption' => 'Background Processor', 'Description' => $e->getMessage().' ['.$e->getCode().']', 'Data' => $datarow));
    }
    return $Result;            
}
            
function TerminateNoRenewals($datarow, $SYSTEM_SETTINGS) {
    $Result = FALSE;
    try {
        if(StartTransaction($SYSTEM_SETTINGS["Database"])) {
            $sql = "UPDATE tblperson SET MSLastReminder = NULL WHERE PersonID = {$datarow['PersonID']}";
            if (!mysqli_query($SYSTEM_SETTINGS["Database"], $sql)) {
                throw new crmException('Unable to update person record: '.mysqli_error($SYSTEM_SETTINGS["Database"]), mysqli_errno($SYSTEM_SETTINGS["Database"]));
            }
            if(!empty($datarow['PersonMSID']) && empty($datarow['EndDate'])) {
                //Close the previous entry
                $sql = "UPDATE tblpersonms SET EndDate = '{$datarow['MembershipEnds']}' WHERE PersonMSID = {$datarow['PersonMSID']}";
                if (mysqli_query($SYSTEM_SETTINGS["Database"], $sql)) {
                                                
                } else {
                    throw new crmException('Unable to update Membership History record: '.mysqli_error($SYSTEM_SETTINGS["Database"]), mysqli_errno($SYSTEM_SETTINGS["Database"]));
                }                    
            }
            $setSQL = new stmtSQL('INSERT', 'tblpersonms', $SYSTEM_SETTINGS["Database"]);
            $setSQL->addField('PersonID', 'integer', $datarow['PersonID']);
            $setSQL->addField('BeginDate', 'datetime', $datarow['MembershipEnded']); 
            $setSQL->addField('MSStatusID', 'integer', $datarow['NewMSStatusID']);
            $setSQL->addField('MSGradeID', 'integer', $datarow['MSGradeID']);
            $setSQL->addField('MSFlags', 'set', 'norenewal');
            if (!mysqli_query($SYSTEM_SETTINGS["Database"], $setSQL->SQL())) {
                throw new crmException('Unable to create membership history record: '.mysqli_error($SYSTEM_SETTINGS["Database"]), mysqli_errno($SYSTEM_SETTINGS["Database"]));
            }
            SendEmailTemplate(
                ($datarow['MSFree'] ? 'ms_ended_norenewal_free' : 'ms_ended_norenewal'),
                array('Data' => $datarow)
            );
            AddHistory(array('type' => 'delete', 'description' => 'Membership status changed to '.$datarow['NewMSStatusTxt'], 'PersonID' => $datarow['PersonID'], 'flags' => 'system'));
            $Result = TRUE;
            mysqli_commit($SYSTEM_SETTINGS["Database"]);
        } else {
            throw new crmException('Unable to end membership: '.mysqli_error($SYSTEM_SETTINGS["Database"]), mysqli_errno($SYSTEM_SETTINGS["Database"]));
        }
    } catch( Exception $e ) {
        mysqli_rollback($SYSTEM_SETTINGS["Database"]);
        $Result = FALSE;
        AddToSysLog(array('EntryKind' => 'error', 'IsSystem' => TRUE, 'Expiry' => $SYSTEM_SETTINGS['ExpiryPolicies']['LogErrors'], 'Caption' => 'Background Processor', 'Description' => $e->getMessage().' ['.$e->getCode().']', 'Data' => $datarow));
    }
    return $Result;            
}

function CreateMSRenewalItem($datarow, $SYSTEM_SETTINGS) {
    $Result = FALSE;
    try {
        if(StartTransaction($SYSTEM_SETTINGS["Database"])) {
            if(!empty($datarow['OpenTransferIDs'])) {
                $ids = explode(',', $datarow['OpenTransferIDs']);
                foreach($ids AS $id) {
                    $TRANSFER = new crmTransfer($SYSTEM_SETTINGS["Database"], $id);
                    if(!empty($TRANSFER->Transfer['InvoiceItemID'])) {
                        CancelInvoiceItem($TRANSFER->Transfer['InvoiceItemID']);
                    }
                    $sql = "UPDATE tbltransfer SET IsOpen = 0, Cancelled = UTC_TIMESTAMP() WHERE tbltransfer.TransferID = {$TRANSFER->TransferID}";
                    mysqli_query($SYSTEM_SETTINGS["Database"], $sql);
                }
                AddHistory(array('type' => 'delete', 'description' => 'Cancelling open transfer request (start of renewal cycle)', 'PersonID' => $datarow['PersonID'], 'flags' => 'system'));
            }
            $msfees = new crmMSFees($SYSTEM_SETTINGS['Database'], $SYSTEM_SETTINGS['Membership']['GradeCaption']);
/*            $discount = LocateDiscount(array(
                'PersonID' => $datarow['PersonID'],
                'CategorySelector' => 'members',
                'Mnemonic' => 'ms_renewal',
            ), FALSE);*/
            $params = array(
                'ISO4217' => $datarow['ISO4217'],
                'MSGradeID' => $datarow['MSGradeID'],
                'ISO3166' => $datarow['ISO3166'],
                'ForDate' => $datarow['MSNextRenewal'],
                'IsDD' => (!empty($datarow['DDIID'])),
                'DiscountID' => (!empty($datarow['DiscountID']) ? $datarow['DiscountID'] : null),
            );
            $fee = $msfees->CalculateFee($params);
            if(!$fee->HasError) {
                if($fee->Net <> 0) {
                    $INVOICE = GetProForma(array('ISO4217' => $datarow['ISO4217'], 'PersonID' => $datarow['PersonID']));
                    $INVOICE->NewItem(array(
                        'Mnemonic' => 'ms_renewal',
                        'LinkedID' => $datarow['RenewalID'],
                        'ItemNet' => $fee->Net,
                        'ItemVATRate' => $fee->VATRate,
                        'DiscountID' => (!empty($datarow) ? $datarow['DiscountID'] : null),
                        'ItemDate' => $datarow['MSNextRenewal'],
//                        'Description' => '%TypeName%, '.$datarow['GradeCaption'],
                        'Explain' => json_encode($fee->Explanation),
                    ), $datarow);
                    //Send "start of renewal" email message
                    SendEmailTemplate(
                        ($datarow['DirectDebit'] ? 'ms_renewal_start_dd' : 'ms_renewal_start_paid'),
                        array('Renewal' => $datarow, 'Invoice' => $INVOICE->Invoice),
                        array(
                            'attachments' => array(array(
                                'Filename' => FilenameStr($INVOICE->Invoice['InvoiceCaption'].'.pdf'),
                                'MimeType' => 'application/pdf',
                                'Data' => $INVOICE->PDF($SYSTEM_SETTINGS["Templates"]['letterhead'])
                            )),
                            'update' => array(
                                'tblperson' => array(
                                    'where' => array(
                                        'targetfieldname' => 'PersonID',
                                        'fieldtype' => 'integer',
                                    ),
                                    'statements' => array(array(
                                        'fieldname' => 'MSLastReminder',
                                        'statement' => 'CURRENT_DATE()',
                                    )),
                                ),
                            ),
                        )
                    );
                }
            } else {
                throw new crmException('Unable to calculate renewal fee for '.$datarow['Fullname'].' ('.$datarow['MSNumber'].')', 1);
            }
            $Result = TRUE;
            mysqli_commit($SYSTEM_SETTINGS["Database"]);
        } else {
            throw new crmException('Unable to create renewal transaction: '.mysqli_error($SYSTEM_SETTINGS["Database"]), mysqli_errno($SYSTEM_SETTINGS["Database"]));
        }
    } catch( Exception $e ) {
        mysqli_rollback($SYSTEM_SETTINGS["Database"]);
        $Result = FALSE;
        AddToSysLog(array('EntryKind' => 'error', 'IsSystem' => TRUE, 'Expiry' => $SYSTEM_SETTINGS['ExpiryPolicies']['LogErrors'], 'Caption' => 'Background Processor', 'Description' => $e->getMessage().' ['.$e->getCode().']', 'Data' => $datarow));
    }
    return $Result;
}

function TransitionToOverdue($datarow, $SYSTEM_SETTINGS) {
    $Result = FALSE;
    try {
        if(StartTransaction($SYSTEM_SETTINGS["Database"])) {
            if(!empty($datarow['PersonMSID']) && empty($datarow['EndDate'])) {
                //Close the previous entry
                $sql = "UPDATE tblpersonms SET EndDate = '{$datarow['CurrentStatusEnds']}' WHERE PersonMSID = {$datarow['PersonMSID']}";
                if (mysqli_query($SYSTEM_SETTINGS["Database"], $sql)) {
                                                
                } else {
                    throw new crmException('Unable to update Membership History record: '.mysqli_error($SYSTEM_SETTINGS["Database"]), mysqli_errno($SYSTEM_SETTINGS["Database"]));
                }                    
            }            
            $setSQL = new stmtSQL('INSERT', 'tblpersonms', $SYSTEM_SETTINGS["Database"]);
            $setSQL->addField('PersonID', 'integer', $datarow['PersonID']);
            $setSQL->addField('BeginDate', 'datetime', $datarow['NewStatusStarts']); 
            $setSQL->addField('MSStatusID', 'integer', $datarow['NewMSStatusID']);
            $setSQL->addField('MSGradeID', 'integer', $datarow['MSGradeID']);
            if (!mysqli_query($SYSTEM_SETTINGS["Database"], $setSQL->SQL())) {
                throw new crmException('Unable to create membership history record: '.mysqli_error($SYSTEM_SETTINGS["Database"]), mysqli_errno($SYSTEM_SETTINGS["Database"]));
            }
            AddHistory(array('type' => 'delete', 'description' => 'Membership status changed to '.$datarow['NewMSStatusTxt'], 'PersonID' => $datarow['PersonID'], 'flags' => 'system'));
            $options = array(
                'update' => array(
                    'tblperson' => array(
                        'where' => array(
                            'targetfieldname' => 'PersonID',
                            'fieldtype' => 'integer',
                        ),
                        'statements' => array(array(
                            'fieldname' => 'MSLastReminder',
                            'statement' => 'CURRENT_DATE()',
                        )),
                    ),
                ),
            );
            if(!empty($datarow['InvoiceID'])) {
                $INVOICE = new crmInvoice('eventsFinance', $SYSTEM_SETTINGS["Database"], $datarow['InvoiceID'], InvoiceSettings());
                $options['attachments'] = array(
                    array(
                        'Filename' => FilenameStr($INVOICE->Invoice['InvoiceCaption'].'.pdf'),
                        'MimeType' => 'application/pdf',
                        'Data' => $INVOICE->PDF($SYSTEM_SETTINGS["Templates"]['letterhead'])
                    )
                );
            }
            SendEmailTemplate(
                ($datarow['DirectDebit'] ? 'ms_renewal_overdue_dd' : 'ms_renewal_overdue_nondd'),
                array('Renewal' => $datarow, 'Invoice' => (!empty($INVOICE) ? $INVOICE->Invoice : array())),
                $options
            );
            $Result = TRUE;
            mysqli_commit($SYSTEM_SETTINGS["Database"]);
        } else {
            throw new crmException('Unable to adjust membership status to overdue: '.mysqli_error($SYSTEM_SETTINGS["Database"]), mysqli_errno($SYSTEM_SETTINGS["Database"]));
        }
    } catch( Exception $e ) {
        mysqli_rollback($SYSTEM_SETTINGS["Database"]);
        $Result = FALSE;
        AddToSysLog(array('EntryKind' => 'error', 'IsSystem' => TRUE, 'Expiry' => $SYSTEM_SETTINGS['ExpiryPolicies']['LogErrors'], 'Caption' => 'Background Processor', 'Description' => $e->getMessage().' ['.$e->getCode().']', 'Data' => $datarow));
    }
    return $Result;
}

function TransitionToLapsed($datarow, $SYSTEM_SETTINGS) {
    $Result = FALSE;
    try {
        if(StartTransaction($SYSTEM_SETTINGS["Database"])) {
            if(!empty($datarow['PersonMSID']) && empty($datarow['EndDate'])) {
                //Close the previous entry
                $sql = "UPDATE tblpersonms SET EndDate = '{$datarow['CurrentStatusEnds']}' WHERE PersonMSID = {$datarow['PersonMSID']}";
                if (mysqli_query($SYSTEM_SETTINGS["Database"], $sql)) {
                                                
                } else {
                    throw new crmException('Unable to update Membership History record: '.mysqli_error($SYSTEM_SETTINGS["Database"]), mysqli_errno($SYSTEM_SETTINGS["Database"]));
                }                    
            }            
            $setSQL = new stmtSQL('INSERT', 'tblpersonms', $SYSTEM_SETTINGS["Database"]);
            $setSQL->addField('PersonID', 'integer', $datarow['PersonID']);
            $setSQL->addField('BeginDate', 'datetime', $datarow['NewStatusStarts']); 
            $setSQL->addField('MSStatusID', 'integer', $datarow['NewMSStatusID']);
            $setSQL->addField('MSGradeID', 'integer', $datarow['MSGradeID']);
            if (!mysqli_query($SYSTEM_SETTINGS["Database"], $setSQL->SQL())) {
                throw new crmException('Unable to create membership history record: '.mysqli_error($SYSTEM_SETTINGS["Database"]), mysqli_errno($SYSTEM_SETTINGS["Database"]));
            }
            AddHistory(array('type' => 'delete', 'description' => 'Membership status changed to '.$datarow['NewMSStatusTxt'], 'PersonID' => $datarow['PersonID'], 'flags' => 'system'));
            $options = array(
                'update' => array(
                    'tblperson' => array(
                        'where' => array(
                            'targetfieldname' => 'PersonID',
                            'fieldtype' => 'integer',
                        ),
                        'statements' => array(array(
                            'fieldname' => 'MSLastReminder',
                            'statement' => 'NULL',
                        )),
                    ),
                ),
            );
            if(!empty($datarow['InvoiceItemID'])) {
                CancelInvoiceItem($datarow['InvoiceItemID']);
            }
            SendEmailTemplate(
                'ms_lapsed',
                array('Renewal' => $datarow),
                $options
            );
            $Result = TRUE;
            AdjustDiscountRefCount(array('PersonID' => $datarow['PersonID'], 'DiscountID' => $datarow['DiscountID']));
            mysqli_commit($SYSTEM_SETTINGS["Database"]);
        } else {
            throw new crmException('Unable to adjust membership status to lapsed: '.mysqli_error($SYSTEM_SETTINGS["Database"]), mysqli_errno($SYSTEM_SETTINGS["Database"]));
        }
    } catch( Exception $e ) {
        mysqli_rollback($SYSTEM_SETTINGS["Database"]);
        $Result = FALSE;
        AddToSysLog(array('EntryKind' => 'error', 'IsSystem' => TRUE, 'Expiry' => $SYSTEM_SETTINGS['ExpiryPolicies']['LogErrors'], 'Caption' => 'Background Processor', 'Description' => $e->getMessage().' ['.$e->getCode().']', 'Data' => $datarow));
    }
    return $Result;
}

function SendMSRenewalReminders($datarow, $SYSTEM_SETTINGS) {
    $Result = FALSE;
    try {
        $invoice = new crmInvoice('eventsFinance', $SYSTEM_SETTINGS['Database'], $datarow['InvoiceID'], InvoiceSettings());
        $Result = SendEmailTemplate(
            ($datarow['RenewalCycle'] > 1 ? 'ms_renewal_remind_after' : 'ms_renewal_remind_before'),
            array('Renewal' => $datarow, 'Invoice' => $invoice->Invoice),
            array(
                'attachments' => array(
                    array(
                        'Filename' => FilenameStr($invoice->Invoice['InvoiceCaption'].'.pdf'),
                        'MimeType' => 'application/pdf',
                        'Data' => $invoice->PDF($SYSTEM_SETTINGS["Templates"]['letterhead']),
                    ),
                ),
                'update' => array(
                    'tblperson' => array(
                        'where' => array(
                            'targetfieldname' => 'PersonID',
                            'fieldtype' => 'integer',
                        ),
                        'statements' => array(array(
                            'fieldname' => 'MSLastReminder',
                            'statement' => 'CURRENT_DATE',
                        )),
                    ),
                ),                            
            )
        );
    } catch( Exception $e ) {
        AddToSysLog(array('EntryKind' => 'error', 'IsSystem' => TRUE, 'Expiry' => $SYSTEM_SETTINGS['ExpiryPolicies']['LogErrors'], 'Caption' => 'Background Processor', 'Description' => $e->getMessage().' ['.$e->getCode().']', 'Data' => $datarow));
    }
    return $Result;
}

function BGSendTemplate($datarow, $SYSTEM_SETTINGS) {
    $Result = FALSE;
    try {
        $emails = explode(',', $renewal['Emails']);
        $Result = SendEmailTemplate(
            $datarow['Mnemonic'],
            $datarow,
            array(
                $destination = $emails[0],
            )
        );
    } catch( Exception $e ) {
        AddToSysLog(array('EntryKind' => 'error', 'IsSystem' => TRUE, 'Expiry' => $SYSTEM_SETTINGS['ExpiryPolicies']['LogErrors'], 'Caption' => 'Background Processor', 'Description' => $e->getMessage().' ['.$e->getCode().']', 'Data' => $datarow));
    }
    return $Result;
}


//Attachments
//  Filename
//  MimeType
//  Object
//      Bucket
//      Objectname
//  DocumentID
//  Data                



/**
 * Support functions
 */
function ExecuteAndLog($sql, $caption, $successmsg = null) {
    global $SYSTEM_SETTINGS;
    $Result = FALSE;
    $query = mysqli_query($SYSTEM_SETTINGS['Database'], $sql);
    if($query) {
        $Result = TRUE;
        if(!empty($successmsg)) {
            $entrykind = 'success';
            $log = TRUE;
            if(stripos($successmsg, '%affected%') !== FALSE) {
                $count = mysqli_affected_rows($SYSTEM_SETTINGS['Database']);
                $log = ($count > 0);
                $successmsg = str_replace('%affected%', SinPlu($count, 'record'), $successmsg);
                $entrykind = ($count > 0 ? 'success' : 'info');
            }
            if($log) {
                AddToSysLog(array(
                    'EntryKind' => $entrykind, 'IsSystem' => TRUE, 'Caption' => $caption,
                    'Description' => $successmsg,
                    'Expiry' => $SYSTEM_SETTINGS['ExpiryPolicies']['BGProc'],
                    'Data' => (defined('__DEBUGMODE') && __DEBUGMODE ? array('sql' => $sql) : null),
                ));
            }
        }
    } else {
        AddToSysLog(array(
            'EntryKind' => 'error', 'IsSystem' => TRUE, 'Caption' => $caption,
            'Description' => 'Database Error: '.mysqli_error($SYSTEM_SETTINGS["Database"]).' ('.mysqli_errno($SYSTEM_SETTINGS["Database"]).')',
            'Expiry' => $SYSTEM_SETTINGS['ExpiryPolicies']['BGProc'],
            'Data' => (defined('__DEBUGMODE') && __DEBUGMODE ? array('sql' => $sql) : null),
        ));
    }
    return $Result;
}

function ProcessRecordsAndLog($sql, $callback, $caption, $successmsg) {
    global $SYSTEM_SETTINGS;
    $Result = FALSE;
    $query = mysqli_query($SYSTEM_SETTINGS['Database'], $sql);
    if($query) {
        $count = 0;
        while($row = mysqli_fetch_assoc($query)) {
            if(call_user_func($callback, $row, $SYSTEM_SETTINGS)) {
                $count++;
            }
        }
        $entrykind = 'info';
        $log = TRUE;
        if(stripos($successmsg, '%affected%') !== FALSE) {
            $log = ($count > 0);
            $successmsg = str_replace('%affected%', SinPlu($count, 'record'), $successmsg);
            $entrykind = 'success';
        }
        if($log) {
            AddToSysLog(array(
                'EntryKind' => $entrykind, 'IsSystem' => TRUE, 'Caption' => $caption,
                'Description' => $successmsg,
                'Expiry' => $SYSTEM_SETTINGS['ExpiryPolicies']['BGProc'],
                'Data' => (defined('__DEBUGMODE') && __DEBUGMODE ? array('sql' => $sql) : null),
            ));
        }
    } else {
        AddToSysLog(array(
            'EntryKind' => 'error', 'IsSystem' => TRUE, 'Caption' => $caption,
            'Description' => 'Database Error: '.mysqli_error($SYSTEM_SETTINGS["Database"]).' ('.mysqli_errno($SYSTEM_SETTINGS["Database"]).')',
            'Expiry' => $SYSTEM_SETTINGS['ExpiryPolicies']['BGProc'],
            'Data' => (defined('__DEBUGMODE') && __DEBUGMODE ? array('sql' => $sql) : null),
        ));
    }    
    return $Result;            
}

?>
