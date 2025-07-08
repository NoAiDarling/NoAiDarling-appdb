<?php
require_once(BASE."include/util.php");
require_once(BASE."include/application.php");
/******************************************/
/* bug class and related functions */
/******************************************/


/**
 * Bug Link class for handling Bug Links and thumbnails
 */
class Bug
{
    var $iLinkId;

    // parameters necessary to create a new Bug with Bug::create()
    var $iVersionId;
    var $iBug_id;

    // values retrieved from bugzilla
    var $sShort_desc;
    var $sBug_status;
    var $sResolution;
    var $sSubmitTime;
    var $iSubmitterId;
    var $bQueued;

    /**    
     * Constructor, fetches the data and bug objects if $ilinkId is given.
     */
    function __construct($iLinkId = null, $oRow = null)
    {
        if(!$iLinkId && !$oRow)
          return;

        if(!$oRow)
        {
            $sQuery = "SELECT * FROM buglinks
                       WHERE linkId = '?'";
            if($hResult = query_parameters($sQuery, $iLinkId))
            {
                $oRow = query_fetch_object($hResult);
            }
        }

        if($oRow)
        {
            $this->iLinkId = $oRow->linkId;
            $this->iBug_id = $oRow->bug_id;
            $this->iVersionId = $oRow->versionId;
            $this->bQueued = ($oRow->state=='queued') ? true : false;
            $this->sSubmitTime = $oRow->submitTime;
            $this->iSubmitterId = $oRow->submitterId;
        }
    }

    /**
     * Creates a new Bug.
     */
    function create()
    {
        $oVersion = new Version($this->iVersionId);

        // Security, if we are not an administrator or a maintainer,
        // the Bug must be queued.
        if($this->mustBeQueued())
        {
            $this->bQueued = true;
        } else
        {
            $this->bQueued = false;
        }

        /* lets check for a valid bug id */
        if(!is_numeric($this->iBug_id))
        {
            addmsg($this->iBug_id." is not a valid bug number.", "red");
            return false;
        }

        // HTTP/GETs Bugzilla html bug list page to parse via search url
        $sBugsSearchUrl = "https://bugs.winehq.org/buglist.cgi?columnlist=short_desc%2Cbug_status%2Cresolution%2C&f1=bug_id&f2=bug_id&f3=bug_id&j_top=OR&o1=equals&o2=equals&o3=equals&order=Importance&query_format=advanced&v1=" . $this->iBug_id;
        $sHtml = file_get_contents($sBugsSearchUrl);
        $oDom = new DOMDocument('1.0', 'utf-8');
        $oDom->loadHTML($sHtml);

        // Parses html for <table class="bz_buglist"> containing bug list
        $oXpath = new DomXPath($oDom);
        $aTbls = $oXpath->query("//*[@class='bz_buglist']");

        // Displays first only Bugzilla <table class='bz_buglist'>
        if ($aTbls->count() == 0)
        {
            addmsg("There is no bug in Bugzilla with that bug number.", "red");
            return false;
        }

        /* Check for duplicates */
        if($this->isDuplicate())
        {
           addmsg("The Bug link has already been submitted.", "red");
           return false;
        }

        /* passed the checks so lets insert the puppy! */
        $hResult = query_parameters("INSERT INTO buglinks (versionId, bug_id, ".
                                    "submitTime, submitterId, state) ".
                                    "VALUES('?', '?', ?, '?', '?')",
                                    $this->iVersionId, $this->iBug_id,
                                    "NOW()",
                                    $_SESSION['current']->iUserId,
                                    $this->bQueued ? 'queued' : 'accepted');
        if($hResult)
        {
            $this->iLinkId = query_appdb_insert_id();

            $this->SendNotificationMail();

            return true;
        } else
        {
            addmsg("Error while creating a new Bug link.", "red");
            return false;
        }
    }

    function purge()
    {
        return $this->delete();
    }

    /**    
     * Deletes the Bug from the database. 
     * and request its deletion from the filesystem (including the thumbnail).
     *
     * Return true if successful, false if an error occurs
     */
    function delete()
    {
        $sQuery = "DELETE FROM buglinks 
                   WHERE linkId = '?'";
        if(!($hResult = query_parameters($sQuery, $this->iLinkId)))
            return false;

        return true;
    }

    /**
     * Move Bug out of the queue.
     */
    function unQueue()
    {
        // If we are not in the queue, we can't move the Bug out of the queue.
        if(!$this->bQueued)
            return false;

        if(query_parameters("UPDATE buglinks SET state = '?' WHERE linkId='?'",
                            'accepted', $this->iLinkId))
        {
            $this->bQueued = false;
            // we send an e-mail to interested people
            $this->mailSubmitter();
            $this->SendNotificationMail();
            // the Bug has been unqueued
            addmsg("The Bug has been unqueued.", "green");
        }
    }

    function update()
    {
        $oBug = new bug($this->iLinkId);

        // There is no need to have two links to a bug.  The update is still
        // considered successful
        if($this->isDuplicate())
            return $this->delete();

        if($this->iVersionId && $this->iVersionId != $oBug->iVersionId)
        {
            $hResult = query_parameters("UPDATE buglinks SET versionId = '?'
                                         WHERE linkId = '?'",
                                         $this->iVersionId, $this->iLinkId);

            if(!$hResult)
                return false;
        }

        return true;
    }

    /* Checks whether the version already has a link for this bug */
    public function isDuplicate()
    {
        $sQuery = "SELECT COUNT(linkId) as count
                   FROM buglinks 
                   WHERE versionId = '?'
                   AND bug_id = '?' AND linkId != '?'";
        if($hResult = query_parameters($sQuery, $this->iVersionId, $this->iBug_id, $this->iLinkId))
        {
            if(($oRow = query_fetch_object($hResult)))
            {
                return $oRow->count > 0;
            }
        }
        return false;
    }

    function mailSubmitter($bRejected=false)
    {
        global $aClean;
        if(!isset($aClean['sReplyText']))
            $aClean['sReplyText'] = "";

        if($this->iSubmitterId)
        {
            $oSubmitter = new User($this->iSubmitterId);
            $sAppName = Version::fullName($this->iVersionId);
            if(!$bRejected)
            {
                $sSubject =  "Submitted Bug Link accepted";
                $sMsg  = "The bug link you submitted between Bug ".$this->iBug_id." and ".  
                        $sAppName." has been accepted.";
            } else
            {
                 $sSubject =  "Submitted Bug Link rejected";
                 $sMsg  = "The bug link you submitted between Bug ".$this->iBug_id." and ".  
                        $sAppName." has been deleted.";
            }
            $sMsg .= $aClean['sReplyText']."\n";
            $sMsg .= "We appreciate your help in making the Application Database better for all users.";
                
            mail_appdb($oSubmitter->sEmail, $sSubject ,$sMsg);
        }
    }

 
    function SendNotificationMail($bDeleted=false)
    {
        $sAppName = version::fullName($this->iVersionId);
        $oVersion = new version($this->iVersionId);

        //show the application version URL and the bug URL
        $sMsg = $oVersion->objectMakeUrl()."\n\n";
        $sMsg .= BUGZILLA_ROOT."show_bug.cgi?id=".$this->iBug_id."\n";

        if(!$bDeleted)
        {
            if(!$this->bQueued)
            {
                $sSubject = "Link between Bug ".$this->iBug_id." and ".$sAppName." added by ".$_SESSION['current']->sRealname;
                if($this->iSubmitterId)
                {
                    $oSubmitter = new User($this->iSubmitterId);
                    $sMsg .= "This Bug Link has been submitted by ".$oSubmitter->sRealname.".\n";
                }
                addmsg("The Bug Link was successfully added into the database.", "green");
            } else // Bug Link queued.
            {
                $sSubject = "Link between Bug ".$this->iBug_id." and ".$sAppName." submitted by ".$_SESSION['current']->sRealname;
                $sMsg .= "This Bug Link has been queued.\n";
                addmsg("The Bug Link you submitted has been added to the database and queued for review by the maintainer or an administrator.", "green");
            }
        } else // Bug Link deleted.
        {
            $sSubject = "Link between Bug ".$this->iBug_id." and ".$sAppName." deleted by ".$_SESSION['current']->sRealname;
            addmsg("Bug Link deleted.", "green");
        }

        $sEmail = User::get_notify_email_address_list(null, $this->iVersionId);
        if($sEmail)
        {
            mail_appdb($sEmail, $sSubject ,$sMsg);
        }
    }

    function objectGetSubmitterId()
    {
        return $this->iSubmitterId;
    }

    function objectGetMailOptions($sAction, $bMailSubmitter, $bParentAction)
    {
        return new mailOptions();
    }

    function objectGetMail($sAction, $bMailSubmitter, $bParentAction)
    {
        /* We don't do this at the moment */
                return array(null, null, null);
    }

    public function objectGetParent($sClass = '')
    {
        return new version($this->iVersionId);
    }

    public function objectSetParent($iNewId, $sClass = '')
    {
        $this->iVersionId = $iNewId;
    }

    function objectGetChildren($bIncludeDeleted = false)
    {
        return array();
    }

    /* Get a list of bugs submitted by a given user */
    public static function listSubmittedBy($iUserId, $bQueued = true)
    {
        global $aClean;

        $sQuery = "SELECT buglinks.*, appFamily.appName, appVersion.versionName
                 FROM buglinks
                 LEFT JOIN appVersion
                 ON buglinks.versionId = appVersion.versionId
                 LEFT JOIN appFamily
                 ON appVersion.appId = appFamily.appId
                 WHERE buglinks.state = '?'
                 AND buglinks.submitterId = '?'
                 ORDER BY buglinks.versionId";
        $hResult = query_parameters($sQuery, $bQueued ? 'queued' : 'accepted', $iUserId);

        if(!$hResult || !query_num_rows($hResult))
            return FALSE;

        return $hResult;
    }

    function isOpen()
    {
        return ($this->sBug_status != 'CLOSED');
    }

    function allowAnonymousSubmissions()
    {
        return false;
    }

    function mustBeQueued()
    {
        $oVersion = new version($this->iVersionId);

        if($_SESSION['current']->hasPriv("admin") ||
           $_SESSION['current']->isMaintainer($oVersion->iVersionId) ||
           $_SESSION['current']->isSuperMaintainer($oVersion->iAppId))
        {
          return false;
        }

        return true;
    }

    function objectGetId()
    {
        return $this->iLinkId;
    }

    public function objectGetFilterInfo()
    {
        $oFilter = new FilterInterface();

        /* The following filters are only useful for admins */
        if(!$_SESSION['current']->hasPriv('admin'))
            return null;

        $oFilter->AddFilterInfo('onlyWithoutMaintainers', 'Only show bug links for versions without maintainers', array(FILTER_OPTION_BOOL), FILTER_VALUES_OPTION_BOOL, array('false','true'));
        $oFilter->AddFilterInfo('onlyMyMaintainedEntries', 'Only show bug links for versions you maintain', array(FILTER_OPTION_BOOL), FILTER_VALUES_OPTION_BOOL, array('false','true'));

        return $oFilter;
    }

    public static function objectGetEntries($sState, $iRows = 0, $iStart = 0, $sOrderBy = '', $bAscending = true, $oFilters = null)
    {
        $sExtraTables = '';
        $aOptions = $oFilters ? $oFilters->getOptions() : array('onlyWithoutMaintainers' => 'false', 'onlyMyMaintainedEntries' => 'false');
        $sWhereFilter = '';
        $bOnlyMyMaintainedEntries = false;

        $oBug = new bug();

        if(getInput('onlyMyMaintainedEntries', $aOptions) == 'true'
           || ($sState != 'accepted' && !$oBug->canEdit()))
        {
            $bOnlyMyMaintainedEntries = true;
        }

        /* This combination doesn't make sense */
        if(getInput('onlyWithoutMaintainers', $aOptions) == 'true'
           && getInput('onlyMyMaintainedEntries', $aOptions) == 'true')
        {
            return false;
        }

        if(getInput('onlyWithoutMaintainers', $aOptions) == 'true')
        {
            $sWhereFilter .= " AND appVersion.hasMaintainer = 'false'";
        }

        /* Selecting 0 rows makes no sense, so we assume the user
         wants to select all of them
         after an offset given by iStart */
        if(!$iRows)
          $iRows = bug::objectGetEntriesCount($sState, $oFilters);

        if($bOnlyMyMaintainedEntries)
        {
            $sQuery = "SELECT buglinks.* FROM buglinks, appVersion,
                        appMaintainers WHERE
                        buglinks.versionId = appVersion.versionId
                        AND
                        appMaintainers.userId = '?'
                        AND
                        (
                            (
                                appMaintainers.superMaintainer = '1'
                                AND
                                appMaintainers.appId = appVersion.appid
                            )
                            OR
                            (
                                appMaintainers.superMaintainer = '0'
                                AND
                                appMaintainers.versionId = appVersion.versionId
                            )
                        )
                        AND
                        appMaintainers.state = 'accepted'
                        AND
                        buglinks.state = '?'$sWhereFilter LIMIT ?,?";

            $hResult = query_parameters($sQuery, $_SESSION['current']->iUserId,
                                        $sState, $iStart, $iRows);
        } else
        {
            $sQuery = "SELECT buglinks.*, appFamily.appName, appVersion.versionName
                 FROM buglinks
                 LEFT JOIN appVersion
                 ON buglinks.versionId = appVersion.versionId
                 LEFT JOIN appFamily
                 ON appVersion.appId = appFamily.appId
                 WHERE buglinks.state = '?' $sWhereFilter
                 LIMIT ?, ?";
            $hResult = query_parameters($sQuery, $sState, $iStart, $iRows);
        }

        return $hResult;
    }

    public static function objectGetEntriesCount($sState, $oFilters = null)
    {
        $sExtraTables = '';
        $aOptions = $oFilters ? $oFilters->getOptions() : array('onlyWithoutMaintainers' => 'false', 'onlyMyMaintainedEntries' => 'false');
        $sWhereFilter = '';
        $bOnlyMyMaintainedEntries = false;

        $oBug = new bug();

        if(getInput('onlyMyMaintainedEntries', $aOptions) == 'true'
           || ($sState != 'accepted' && !$oBug->canEdit()))
        {
            $bOnlyMyMaintainedEntries = true;
        }

        /* This combination doesn't make sense */
        if(getInput('onlyWithoutMaintainers', $aOptions) == 'true'
           && getInput('onlyMyMaintainedEntries', $aOptions) == 'true')
        {
            return false;
        }

        if(getInput('onlyWithoutMaintainers', $aOptions) == 'true')
        {
            $sExtraTables = ',appVersion';

            $sWhereFilter .= " AND appVersion.hasMaintainer = 'false' AND appVersion.versionId = buglinks.versionId";
        }

        if($bOnlyMyMaintainedEntries)
        {
            $sQuery = "SELECT COUNT(buglinks.linkId) as cnt FROM buglinks, appVersion,
                        appMaintainers WHERE
                        buglinks.versionId = appVersion.versionId
                        AND
                        appMaintainers.userId = '?'
                        AND
                        (
                            (
                                appMaintainers.superMaintainer = '1'
                                AND
                                appMaintainers.appId = appVersion.appid
                            )
                            OR
                            (
                                appMaintainers.superMaintainer = '0'
                                AND
                                appMaintainers.versionId = appVersion.versionId
                            )
                        )
                        AND
                        appMaintainers.state = 'accepted'
                        AND
                        buglinks.state = '?'$sWhereFilter";

            $hResult = query_parameters($sQuery, $_SESSION['current']->iUserId,
                                        $sState);
        } else
        {
            $sQuery = "select count(*) as cnt from buglinks$sExtraTables where buglinks.state = '?'$sWhereFilter";
            $hResult = query_parameters($sQuery, $sState);
        }

        $oRow = query_fetch_object($hResult);

        return $oRow->cnt;
    }

    function objectGetHeader()
    {
        // Header handled by objectDrawCustomTable
        return NULL;
    }

    function objectWantCustomDraw($sWhat, $sQueued)
    {
        switch($sWhat)
        {
            case 'table':
                return true;
                break;
        }

        return false;
    }

    public static function objectDrawCustomTable($hResult, $sQueued, $bCanEdit=false, $sAllBugs=true)
    {
        // Adds bug ids to Bugzilla url search params
        $sBugIds = "";
        for($i = 1; $oRow = query_fetch_object($hResult); $i++)
        {
            $sBugIds .= $oRow->bug_id;

            if ($i < $hResult->num_rows)
                $sBugIds .= "%2C%20"; // Adds comma separator ", " between bug_ids
        }

        $sBugsSearchOpen = "";
        if(!$sAllBugs)
        {
            $sBugsSearchOpen = "&f1=bug_status&o1=notsubstring&v1=clos";
        }
        
        // HTTP/GETs Bugzilla html bug list page to parse via search url
        $sBugsSearchUrl = BUGZILLA_ROOT."/buglist.cgi?bug_id=$sBugIds&bug_id_type=anyexact&order=Importance&query_format=advanced&columnlist=short_desc%2Cbug_status".$sBugsSearchOpen;

        $sHtml = file_get_contents($sBugsSearchUrl);
        $oDom = new DOMDocument('1.0', 'utf-8');
        $oDom->loadHTML($sHtml);

        // Parses html for <table class="bz_buglist"> containing bug list
        $oXpath = new DomXPath($oDom);
        $aTbls = $oXpath->query("//*[@class='bz_buglist']");
        $aTblrows = $oXpath->query("//*[@class='bz_buglist']//tr");

        // Displays first only Bugzilla <table class='bz_buglist'>
        if ($aTbls->count() > 0)
        {
            $oTbl = $aTbls->item(0);

            // Overrides <table class="">
            // Replaces bugz style class with appdb css pattern matches */
            $oTbl->setAttribute('class', 'whq-table whq-table-full');

            // Inserts <thead> to <table> consistent with appdb css pattern matching
            $oTblhead = $oDom->createElement("thead");
            $oTblbody = $oTbl->firstChild;
            $oTbl->insertBefore($oTblhead, $oTblbody);
        }

        // Inits <tr> header detect flag
        $bIsTblhead = true;

        $aBugIdRows = [];
        // Iterates each <tr>
        foreach ($aTblrows as $oTblrow) {

            // Uses header flag to detect <table> header
            // Assumes first <table> <tr> is header
            if ($bIsTblhead)
            {
                $bIsTblhead = false;

                $iTblcell_count = 0;

                // Iterates each <tr> child
                foreach ($oTblrow->childNodes as $oTblcell) {

                    // Skips <tr> without children
                    // Treats empty childNodes as ignored padding */
                    if ($oTblcell->childNodes->length == 0)
                        continue;

                    // TODO: support <a> link click header sorting */
                    // Assigns nodeValue <td>
                    // Replaces <a> links with it's inner text
                    $oTblcell->nodeValue = $oTblcell->nodeValue;

                    // Skips < width=80> for description in cell 1 for readability
                    if ($iTblcell_count++ == 1)
                        continue;

                    $oTblcell->setAttribute("width", 80);
                }

                // Removes first <tr> from <tbody>
                $oTblbody->removeChild($oTblrow);

                // Adds <tr> to <thead> consistent with css pattern matches */
                $oTblhead->appendChild($oTblrow);

                // Adds appdb-specific extras to <table> <thead> from bugzilla
                $oTblcell = $oDom->createElement("th");
                $oTblrow->appendChild($oTblcell);
                $oTblcell->nodeValue = "App Name";
                $oTblcell->setAttribute("width", 80);

                // Adds appdb-specific extras to <table> <thead> from bugzilla
                $oTblcell = $oDom->createElement("th");
                $oTblrow->appendChild($oTblcell);
                $oTblcell->nodeValue = "App Version";
                $oTblcell->setAttribute("width", 80);

                $oTblcell = $oDom->createElement("th");
                $oTblrow->appendChild($oTblcell);
                $oTblcell->nodeValue = "Has Maintainer";
                $oTblcell->setAttribute("width", 80);

                $oTblcell = $oDom->createElement("th");
                $oTblrow->appendChild($oTblcell);
                $oTblcell->nodeValue = "Other apps affected";
                $oTblcell->setAttribute("width", 80);

                if ($bCanEdit)
                {
                    $oTblcell = $oDom->createElement("th");
                    $oTblrow->appendChild($oTblcell);
                    $oTblcell->nodeValue = "Delete";
                    $oTblcell->setAttribute("width", 80);

                    $oTblcell = $oDom->createElement("th");
                    $oTblrow->appendChild($oTblcell);
                    $oTblcell->nodeValue = "Edit";
                    $oTblcell->setAttribute("width", 80);
                }
            }
            else
            {
                // Extracts bugId from Bugzilla ID table row cell of index 1
                $oBugIdCell = $oTblrow->childNodes[1];
                $iBugId = trim($oBugIdCell->nodeValue);
                $sHref = $oBugIdCell->childNodes[1]->getAttribute("href");
                $oBugIdCell->childNodes[1]->setAttribute("href", BUGZILLA_ROOT.$sHref);

                $oBugDescCell = $oTblrow->childNodes[3];
                $sHref = $oBugDescCell->childNodes[1]->getAttribute("href");
                $oBugDescCell->childNodes[1]->setAttribute("href", BUGZILLA_ROOT.$sHref);

                $aBugIdRows[$iBugId] = $oTblrow->cloneNode(true);
                $oTblrow->parentNode->removeChild($oTblrow);
            }
        }

        // Set loop index back to first element to loop again
        query_data_seek($hResult, 0);
        for($i = 1; $oRow = query_fetch_object($hResult); $i++)
        {
            if (!array_key_exists($oRow->bug_id, $aBugIdRows))
            {
                continue;
            }
            
            //$oTblrow = $oDom->cloneNode("tr");
            $oTblrow = $aBugIdRows[$oRow->bug_id]->cloneNode(true);
            $oTbl->appendChild($oTblrow);

            // Adds extra appdb <table> cells to bugz <table>
            $oTblcell = $oDom->createElement("td");
            $oTblrow->appendChild($oTblcell);
            $oTblcellElem = $oDom->createElement("a");
            $oTblcell->appendChild($oTblcellElem);
            $oTblcellElem->setAttribute('href', 'objectManager.php?sClass=application&iId='.$oRow->appId);
            $oTblcellElem->nodeValue = $oRow->appName;

            // Adds extra appdb <table> cells to bugz <table>
            $oTblcell = $oDom->createElement("td");
            $oTblrow->appendChild($oTblcell);
            $oTblcellElem = $oDom->createElement("a");
            $oTblcell->appendChild($oTblcellElem);
            $oTblcellElem->setAttribute('href', 'objectManager.php?sClass=version&iId='.$oRow->versionId);
            $oTblcellElem->nodeValue = $oRow->versionName;

            // Adds extra appdb <table> cells to bugz <table>
            $oTblcell = $oDom->createElement("td");
            $oTblrow->appendChild($oTblcell);
            //$oTblcell->nodeValue = $oRow->hasMaintainer == NULL ? "No" : "YES";
            $oTblcellElem = $oDom->createElement("a");
            $oTblcell->appendChild($oTblcellElem);
            //$oTblcellElem->setAttribute('class', "");
            //$oTblcellElem->setAttribute('href', 'viewbugs.php?iBugId='.$oRow->bug_id);
            //$oTblcellElem->nodeValue = "View";
            $oTblcellElemm = $oDom->createElement("i");
            $oTblcellElem->appendChild($oTblcellElemm);
            $oTblcellElemm->setAttribute('class', $oRow->hasMaintainer == NULL ? "fa fa-close" : "fa fa-check");

            $oTblcell = $oDom->createElement("td");
            $oTblrow->appendChild($oTblcell);
            $oTblcellElem = $oDom->createElement("a");
            $oTblcell->appendChild($oTblcellElem);
            $oTblcellElem->setAttribute('class', "btn btn-default");
            $oTblcellElem->setAttribute('href', 'viewbugs.php?iBugId='.$oRow->bug_id);
            //$oTblcellElem->nodeValue = "View";
            $oTblcellElemm = $oDom->createElement("i");
            $oTblcellElem->appendChild($oTblcellElemm);
            $oTblcellElemm->setAttribute('class', "fa fa-table");

            $oM = new objectManager("bug");
            $oM->setReturnTo(array_key_exists('REQUEST_URI', $_SERVER) ? $_SERVER['REQUEST_URI'] : "");
            $oVersion = new Version($oRow->versionId);

            if ($bCanEdit)
            {
                $oTblcell = $oDom->createElement("td");
                $oTblrow->appendChild($oTblcell);
                $oTblcellElem = $oDom->createElement("a");
                $oTblcell->appendChild($oTblcellElem);
                $oTblcellElem->setAttribute('href', html_entity_decode($oM->makeUrl("delete", $oRow->linkId)));
                $oTblcellElem->setAttribute('class', "btn btn-default");
                $oTblcellElemm = $oDom->createElement("i");
                $oTblcellElem->appendChild($oTblcellElemm);
                $oTblcellElemm->setAttribute('class', "fa fa-trash-o");

                $oTblcell = $oDom->createElement("td");
                $oTblrow->appendChild($oTblcell);

                if ($oRow->state=='queued')
                {
                    $oTblcellElem = $oDom->createElement("a");
                    $oTblcell->appendChild($oTblcellElem);
                    $oTblcellElem->setAttribute('href', html_entity_decode($oM->makeUrl("edit", $oRow->linkId))."&sAllBugs&sState=queued");
                    $oTblcellElem->setAttribute('class', "btn btn-default");
                    $oTblcellElemm = $oDom->createElement("i");
                    $oTblcellElem->appendChild($oTblcellElemm);
                    $oTblcellElemm->setAttribute('class', "fa fa-edit");
                }
                else
                {
                    //$oTblcell->nodeValue = $oRow->state;

                    $oTblcellElem = $oDom->createElement("a");
                    $oTblcell->appendChild($oTblcellElem);
                    //$oTblcellElem->setAttribute('href', html_entity_decode($oM->makeUrl("edit", $oRow->linkId)));
                    //$oTblcellElem->setAttribute('class', "btn btn-default");
                    $oTblcellElemm = $oDom->createElement("i");
                    $oTblcellElem->appendChild($oTblcellElemm);
                    $oTblcellElemm->setAttribute('class', "fa fa-close");
                }
            }
        }

        // Ouputs html with manual changes above
        if ($aTbls->count() > 0)
        {
            echo $oDom->saveHTML($oTbl);
        }
    }

    // returns a table row
    function objectGetTableRow()
    {
        // Table handled but objectDrawCustomTable
        return NULL;
    }

    function objectMakeUrl()
    {
        $oManager = new objectManager("bug", "View Bug");
        return $oManager->makeUrl("view", $this->objectGetId());
    }

    function objectMakeLink()
    {
        $sLink = "<a href=\"".$this->objectMakeUrl()."\">".
                 $this->sShort_desc."</a>";
        return $sLink;
    }

    function objectGetState()
    {
        return ($this->bQueued) ? 'queued' : 'accepted';
    }

    function canEdit()
    {
        if($_SESSION['current']->hasPriv("admin"))
        {
            return true;
        } else if($this->iVersionId)
        {
            if(maintainer::isUserMaintainer($_SESSION['current'],
                                          $this->iVersionId))
            {
                return true;
            }

            if($this->iSubmitterId == $_SESSION['current']->iUserId)
                return true;
        }

        return false;
    }

    function objectGetItemsPerPage($sState = 'accepted')
    {
        $aItemsPerPage = array(25, 50, 100, 200);
        $iDefaultPerPage = 25;
        return array($aItemsPerPage, $iDefaultPerPage);
    }

    function display()
    {
        global $aClean;
        $sQuery = "SELECT buglinks.*, appFamily.appName, appVersion.versionName
               FROM buglinks
               LEFT JOIN appVersion
               ON buglinks.versionId = appVersion.versionId
               LEFT JOIN appFamily
               ON appVersion.appId = appFamily.appId
               WHERE buglinks.linkId = '?'
               ORDER BY bug_id";

        if($hResult = query_parameters($sQuery, $this->iLinkId))
        {
            Bug::objectDrawCustomTable($hResult, false, $this->canEdit(), array_key_exists('sAllBugs', $aClean));
        }
    }

    function objectHideReject()
    {
        return TRUE;
    }

    // NOTE: we don't have any editing support for this entry at this time
    //       so output the entry and a field to identify the bug id
    function outputEditor()
    {
        $this->display();
        echo '<input type="hidden" name="iBuglinkId" value="'.$this->iBug_id.'">';
        echo '<input type="hidden" name="iVersionId" value="'.$this->iVersionId.'">';
    }

    function getOutputEditorValues($aClean)
    {
        if(getInput('iBuglinkId', $aClean))
            $this->iBug_id = $aClean['iBuglinkId'];

        if($aClean['iVersionId'])
            $this->iVersionId = $aClean['iVersionId'];
    }
}


/*
 * Bug Link functions that are not part of the class
 */

function view_version_bugs($iVersionId = null, $aBuglinkIds)
{
    global $aClean;

    $bCanEdit = FALSE;
    $oVersion = new Version($iVersionId);

    // Security, if we are an administrator or a maintainer, we can remove or ok links.
    if(($_SESSION['current']->hasPriv("admin") ||
                 $_SESSION['current']->isMaintainer($oVersion->iVersionId) ||
                 $_SESSION['current']->isSuperMaintainer($oVersion->iAppId)))
    {
        $bCanEdit = TRUE;
    }

    $aBuglinkIds = array();
    $sQuery = "SELECT buglinks.*, appFamily.appName, appVersion.versionName
               FROM buglinks
               LEFT JOIN appVersion
               ON buglinks.versionId = appVersion.versionId
               LEFT JOIN appFamily
               ON appVersion.appId = appFamily.appId
               WHERE buglinks.versionId = '?'
               ORDER BY bug_id";

    if($hResult = query_parameters($sQuery, $iVersionId))
    {
        Bug::objectDrawCustomTable($hResult, false, $bCanEdit, array_key_exists('sAllBugs', $aClean));
    }

    if($_SESSION['current']->isLoggedIn())
    {
        echo "<form method=post action='".APPDB_ROOT."objectManager.php'>\n";
        echo '<input type="hidden" name="iVersionId" value="'.$iVersionId.'">',"\n";
        $sBuglinkId = isset($aClean['buglinkId']) ? $aClean['buglinkId'] : '';
        echo '<input type="text" name="iBuglinkId" value="'.$sBuglinkId.'" size="8">',"\n";
        echo '<input type="hidden" name="sSubmit" value="Submit">',"\n";
        echo '<input type="hidden" name="sClass" value="bug">',"\n";
        echo '<input type="hidden" name="sReturnTo" value="'.$oVersion->objectMakeUrl().'">',"\n";
        echo '<button type="submit" name="sSub" class="btn btn-default"><i class=\"fa fa-bug\"></i> Submit a new bug link</button>',"\n";
        echo '</form>',"\n";
    }

    // show only open link
    if ( isset( $aClean['sAllBugs'] ) )
    {
        $sURL = htmlentities(str_replace( '&sAllBugs', '', $_SERVER['REQUEST_URI']));
        $sLink = "<a href=\"{$sURL}\" class=\"btn btn-default\"><i class=\"fa fa-bug\"></i> Show <b>open</b> bugs</a>";
    }
    // show all link
    else
    {
        $sURL = htmlentities($_SERVER['REQUEST_URI'] . '&sAllBugs');
        $sLink = "<a href=\"{$sURL}\" class=\"btn btn-default\"><i class=\"fa fa-bug\"></i> Show <b>all</b> bugs</a>";
    }
    echo "<p>{$sLink}</p>\n";
}

?>
