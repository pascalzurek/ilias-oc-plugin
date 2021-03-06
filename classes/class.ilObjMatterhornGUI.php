<?php
/**
	+-----------------------------------------------------------------------------+
	| ILIAS open source                                                           |
	+-----------------------------------------------------------------------------+
	| Copyright (c) 1998-2009 ILIAS open source, University of Cologne            |
	|                                                                             |
	| This program is free software; you can redistribute it and/or               |
	| modify it under the terms of the GNU General Public License                 |
	| as published by the Free Software Foundation; either version 2              |
	| of the License, or (at your option) any later version.                      |
	|                                                                             |
	| This program is distributed in the hope that it will be useful,             |
	| but WITHOUT ANY WARRANTY; without even the implied warranty of              |
	| MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the               |
	| GNU General Public License for more details.                                |
	|                                                                             |
	| You should have received a copy of the GNU General Public License           |
	| along with this program; if not, write to the Free Software                 |
	| Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA. |
	+-----------------------------------------------------------------------------+
*/


include_once("./Services/Repository/classes/class.ilObjectPluginGUI.php");

/**
* User Interface class for Opencast repository object.
*
* User interface classes process GET and POST parameter and call
* application classes to fulfill certain tasks.
*
* @author Per Pascal Grube <pascal.grube@tik.uni-stuttgart.de>
*
* $Id$
*
* Integration into control structure:
* - The GUI class is called by ilRepositoryGUI
* - GUI classes used by this class are ilPermissionGUI (provides the rbac
*   screens) and ilInfoScreenGUI (handles the info screen).
*
* @ilCtrl_isCalledBy ilObjMatterhornGUI: ilRepositoryGUI, ilAdministrationGUI, ilObjPluginDispatchGUI
* @ilCtrl_Calls ilObjMatterhornGUI: ilPermissionGUI, ilInfoScreenGUI, ilObjectCopyGUI
* @ilCtrl_Calls ilObjMatterhornGUI: ilCommonActionDispatcherGUI
*
*/
class ilObjMatterhornGUI extends ilObjectPluginGUI
{
	/**
	* Initialisation
	*/
	protected function afterConstructor()
	{
		
		include_once("./Customizing/global/plugins/Services/Repository/RepositoryObject/Matterhorn/classes/class.ilMatterhornConfig.php");
		$this->configObject = new ilMatterhornConfig();
	}

    /**
     * Get type.
     */
    final function getType()
    {
        return "xmh";
    }
	
	/**
	* Handles all commmands of this class, centralizes permission checks
	*/
	function performCommand($cmd)
	{
		switch ($cmd)
		{
			case "editProperties":		// list all commands that need write permission here
			case "updateProperties":
			case "editEpisodes":
			case "trimEpisode":
			case "showTrimEditor":
			case "publish":
			case "retract":
			case "getEpisodes":
				$this->checkPermission("write");
				$this->$cmd();
				break;
			
			case "showSeries":			// list all commands that need read permission here
			case "showEpisode":
				$this->checkPermission("read");
				$this->$cmd();
				break;
            default:
                $this->checkPermission("read");
                $this->showSeries();
		}
	}

	/**
	* After object has been created -> jump to this command
	*/
	function getAfterCreationCmd()
	{
		return "editProperties";
	}

	/**
	* Get standard command
	*/
	function getStandardCmd()
	{
		return "showSeries";
	}
	
//
// DISPLAY TABS
//
	
	/**
	* Set tabs
	*/
	function setTabs()
	{
		global $ilTabs, $ilCtrl, $ilAccess;
		
		// tab for the "show content" command
		if ($ilAccess->checkAccess("read", "", $this->object->getRefId()))
		{
			$ilTabs->addTab("content", $this->txt("content"), $ilCtrl->getLinkTarget($this, "showSeries"));
		}

		// standard info screen tab
		$this->addInfoTab();

		// a "properties" tab
		if ($ilAccess->checkAccess("write", "", $this->object->getRefId()))
		{
			$ilTabs->addTab("manage", $this->txt("manage"), $ilCtrl->getLinkTarget($this, "editEpisodes"));
			$ilTabs->addTab("properties", $this->txt("properties"), $ilCtrl->getLinkTarget($this, "editProperties"));
		}

		// standard epermission tab
		$this->addPermissionTab();
	}
	


//
// Edit properties form
//

	/**
	* Edit Properties. This commands uses the form class to display an input form.
	*/
	function editProperties()
	{
		global $tpl, $ilTabs;
		
		$ilTabs->activateTab("properties");
		$this->initPropertiesForm();
		$this->getPropertiesValues();
		$tpl->setContent($this->form->getHTML());
	}
	
	/**
	* Init  form.
	*
	* @param        int        $a_mode        Edit Mode
	*/
	public function initPropertiesForm()
	{
		global $ilCtrl;
	
		include_once("Services/Form/classes/class.ilPropertyFormGUI.php");
		$this->form = new ilPropertyFormGUI();
	
		// title
		$ti = new ilTextInputGUI($this->txt("title"), "title");
		$ti->setRequired(true);
		$this->form->addItem($ti);
		
		// description
		$ta = new ilTextAreaInputGUI($this->txt("description"), "desc");
		$this->form->addItem($ta);

		// vorlesungsnummer
		$tl = new ilTextAreaInputGUI($this->txt("lectureID"), "lectureID");
		$this->form->addItem($tl);

		// viewmode
		$vm = new ilCheckboxInputGUI($this->txt("viewmode"), "viewMode");
		$this->form->addItem($vm);

		// release episodes individually
        $mr = new ilCheckboxInputGUI($this->txt("manualRelease"), "manualRelease");
        $this->form->addItem($mr);

       	// download
        $download = new ilCheckboxInputGUI($this->txt("download"), "download");
        $this->form->addItem($download);

        
		// online
		$cb = new ilCheckboxInputGUI($this->lng->txt("online"), "online");
		$this->form->addItem($cb);
		
		$this->form->addCommandButton("updateProperties", $this->txt("save"));
	                
		$this->form->setTitle($this->txt("edit_properties"));
		$this->form->setFormAction($ilCtrl->getFormAction($this));
	}
	
	/**
	* Get values for edit properties form
	*/
	function getPropertiesValues()
	{
		$values = array();
		$values["title"] = $this->object->getTitle();
		$values["desc"] = $this->object->getDescription();
		$values["lectureID"] = $this->object->getLectureID();
		$values["online"] = $this->object->getOnline();
		$values["viewMode"] = $this->object->getViewMode();
        $values["manualRelease"] = $this->object->getManualRelease();
        $values["download"] = $this->object->getDownload();
		$this->form->setValuesByArray($values);
	}

	
	/**
	* Update properties
	*/
	public function updateProperties()
	{
		global $tpl, $lng, $ilCtrl;
	
		$this->initPropertiesForm();
		if ($this->form->checkInput())
		{
			$this->object->setTitle($this->form->getInput("title"));
			$this->object->setDescription($this->form->getInput("desc"));
			$this->object->setLectureID($this->form->getInput("lectureID"));				
			$this->object->setOnline($this->form->getInput("online"));
			$this->object->setViewMode($this->form->getInput("viewMode"));
			$this->object->setManualRelease($this->form->getInput("manualRelease"));
			$this->object->setDownload($this->form->getInput("download"));
			$this->object->update();
			ilUtil::sendSuccess($lng->txt("msg_obj_modified"), true);
			$ilCtrl->redirect($this, "editProperties");
		}

		$this->form->setValuesByPost();
		$tpl->setContent($this->form->getHtml());
	}

    public function publish()
    {
        global $ilCtrl, $ilLog;
        $ilLog->write("ID:".$_GET["id"]);
        if (preg_match('/^[0-9a-f\-]+/', $_GET["id"])) {            
            $this->object->publish($_GET["id"]);
            ilUtil::sendSuccess($this->txt("msg_episode_published"), true);
        } else {
            $ilLog->write("ID does not match in publish episode:".$_GET["id"]);
        }
        $ilCtrl->redirect($this, "editEpisodes");
    }

    public function retract()
    {
        global $ilCtrl, $ilLog;
        $ilLog->write("ID:".$_GET["id"]);
        if (preg_match('/^[0-9a-f\-]+/', $_GET["id"])) {            
            $this->object->retract($_GET["id"]);
            ilUtil::sendSuccess($this->txt("msg_episode_retracted"), true);
        } else {
            $ilLog->write("ID does not match in retract episode:".$_GET["id"]);
        }
        $ilCtrl->redirect($this, "editEpisodes");
    }

	
//
// Show content
//

	/**
	* Show content
	*/
	function showEpisode()
	{        
		global $tpl, $ilTabs;
		$this->checkPermission("read");
		$theodulbase = "./Customizing/global/plugins/Services/Repository/RepositoryObject/Matterhorn/templates/theodul";

		
		$player = new ilTemplate("tpl.player.html", true, false, "Customizing/global/plugins/Services/Repository/RepositoryObject/Matterhorn/");

		$player->setVariable("INITJS",$theodulbase );
		
		$tpl->setContent($player->get());
		$ilTabs->activateTab("content");
		
	}
	
	function showSeries()
	{     

		global $tpl, $ilTabs, $ilCtrl;
		
		$this->checkPermission("read");

		$released_episodes = $this->extractReleasedEpisodes(true);
		if ( ! $this->object->getViewMode() ) {
            $seriestpl = new ilTemplate("tpl.series.html", true, true,  "Customizing/global/plugins/Services/Repository/RepositoryObject/Matterhorn/");
            $seriestpl->setCurrentBlock($this->object->getDownload()?"headerdownload":"header");
            $seriestpl->setVariable("TXT_FINISHED_RECORDINGS", $this->getText("finished_recordings"));
            $seriestpl->setVariable("TXT_TITLE", $this->getText("title"));
            $seriestpl->setVariable("TXT_PREVIEW", $this->getText("preview"));
            $seriestpl->setVariable("TXT_DATE", $this->getText("date"));
            if($this->object->getDownload()){
                $seriestpl->setVariable("TXT_ACTION", $this->getText("action"));
            }
            $seriestpl->parseCurrentBlock();
            foreach($released_episodes as $item)
            {
                $seriestpl->setCurrentBlock($this->object->getDownload()?"episodedownload":"episode");
                //$ilLog->write("Adding: ".$item["title"]);
                    
                $ilCtrl->setParameterByClass("ilobjmatterhorngui", "id", $item['mhid']);
                $seriestpl->setVariable("CMD_PLAYER", $ilCtrl->getLinkTargetByClass("ilobjmatterhorngui", "showEpisode"));
                $seriestpl->setVariable("PREVIEWURL", $item["previewurl"]);
                $seriestpl->setVariable("TXT_TITLE", $item["title"]);
                $seriestpl->setVariable("TXT_DATE", ilDatePresentation::formatDate(new ilDateTime($item["date"],IL_CAL_DATETIME)));
                if($this->object->getDownload()){
                    $seriestpl->setVariable("DOWNLOADURL", $item["downloadurl"]);
                    $seriestpl->setVariable("TXT_DOWNLOAD", $this->getText("download"));
                }
                $seriestpl->parseCurrentBlock();    
            }
            $seriestpl->touchblock("footer");
            $html = $seriestpl->get();
            $tpl->setContent($html);
          } else {		
			$tpl->addCss($this->plugin->getStyleSheetLocation("css/xmh.css"));
			$seriestpl = new ilTemplate("tpl.series.html", true, true,  "Customizing/global/plugins/Services/Repository/RepositoryObject/Matterhorn/");
            foreach($released_episodes as $item)
            {
                $seriestpl->setCurrentBlock("videodiv");             
                $ilCtrl->setParameterByClass("ilobjmatterhorngui", "id", $item['mhid']);
                $seriestpl->setVariable("CMD_PLAYER", $ilCtrl->getLinkTargetByClass("ilobjmatterhorngui", "showEpisode"));
                $seriestpl->setVariable("PREVIEWURL", $item["previewurl"]);
                $seriestpl->setVariable("TXT_TITLE", $item["title"]);
                $seriestpl->setVariable("TXT_DATE", ilDatePresentation::formatDate(new ilDateTime($item["date"],IL_CAL_DATETIME)));
                $seriestpl->parseCurrentBlock();
            }
			$html = $seriestpl->get();
			$tpl->setContent($html);
		}
		$tpl->setPermanentLink($this->object->getType(), $this->object->getRefId());
		$ilTabs->activateTab("content");
	}

	private function extractProcessingEpisodes($workflow)
	{
        global $ilLog;
        $totalops = 0.0;
        $finished = 0;
        $running = "Waiting";
        
        foreach ($workflow["operations"]["operation"] as $operation){
            //search for trim. If it will run, count only up to here if it is not finished yet, otherwise count from here
            if($operation["id"] == "trim"){
                if($operation["if"] == "true"){
                    if($state === "SUCCEEDED"){
                        $totalops = 0.0;
                        $finished = 0;
                    } else {
                        break;
                    }
                }
            }
            $totalops += 1.0;
            $state = (string)$operation["state"];
            if($state == "SKIPPED" || $state === "SUCCEEDED"){
            $ilLog->write($state);    
                $finished += 1.0;
            }
            
            if((string)$state == "RUNNING"){
                $running = $operation["description"];
            }
        }

	    $episode = array(
	        'title' => $workflow["mediapackage"]['title'],
	        'mhid' => $workflow['id'],
	        'date' => $workflow["mediapackage"]['start'],
	        'processdone' => $finished/$totalops*100.0,
	        'processcount' => $finished."/".$totalops,
	        'running' =>  $running
	    );
	    return $episode;
	}
	
	private function extractReleasedEpisodes($skipUnreleased=false)
	{
	
	    global $ilCtrl;
	    
	    $released_episodes  = $this->object->getReleasedEpisodes();
	    $episodes = array();
	    
	    foreach($this->object->getSearchResult()->mediapackage as $value) {
	        if ($skipUnreleased && $this->object->getManualRelease()){
	            if(! in_array($value['id'],$released_episodes)){
	                continue;
	            }
	        }
	        $previewurl = "unset";
	        foreach ($value->attachments->attachment as $attachment){
	            if ('presentation/search+preview' ==  $attachment['type']){
	                $previewurl = $attachment->url;
	                //prefer presentation/search+preview over presenter/search+preview
	                break 1;
	            } elseif ('presenter/search+preview' ==  $attachment['type']) {
	                $previewurl = $attachment->url;
	                // continue searching for a presentation/search+preview
	            }
	        }
	        $downloadurl = "unset";
	        foreach ($value->media->track as $track){
	            if ('composite/sbs' ==  $track['type']) {
	                $downloadurl = $track->url;
	                break;
	            }
	            if('presentation/delivery' ==  $track['type'] && 'video/mp4' == $track->mimetype){
	                $downloadurl = $track->url;
	            }
	        }
	         
	        $ilCtrl->setParameterByClass("ilobjmatterhorngui", "id", $this->obj_id."/".(string)$value['id']);
	        $published = in_array($value['id'],$released_episodes);
	         
	        $episodes[(string)$value['id']] = array(
	            "title" => (string)$value->title,
	            "date" => (string)$value['start'],
	            "mhid" => $this->obj_id."/".(string)$value['id'],
	            "previewurl" => (string)$previewurl,
	            "downloadurl" => $downloadurl,
	            "viewurl" => $ilCtrl->getLinkTargetByClass("ilobjmatterhorngui", "showEpisode"),
	        );
    	    if ($this->object->getManualRelease()){
    	        $ilCtrl->setParameterByClass("ilobjmatterhorngui", "id", (string)$value['id']);
    	        $episodes[(string)$value['id']]["publishurl"] = $ilCtrl->getLinkTargetByClass("ilobjmatterhorngui", $published?"retract":"publish");
    	        $episodes[(string)$value['id']]["txt_publish"] = $this->getText($published?"retract":"publish");
    	    }
	    }
	    
	    uasort($episodes,array($this, 'sortbydate'));	     
	    return $episodes;
    }
	
    private function extractScheduledEpisode($workflow){
        $scheduled_episode = array(
            'title' => $workflow["mediapackage"]['title'],
            'mhid' => $workflow['id'],
        );
        $workflowconfig = $workflow['configurations']['configuration'];
        foreach($workflowconfig as $configuration){
            switch ($configuration['key']) {
                case 'schedule.start':
                    $scheduled_episode['startdate'] = $configuration['$']/1000;
                    continue;
                case 'schedule.stop':
                    $scheduled_episode['stopdate'] = $configuration['$']/1000;
                    continue;
                case 'schedule.location':
                    $scheduled_episode['location'] = $configuration['$'];
                    continue;
            }
        }
        return $scheduled_episode;
    }
    
    private function extractOnholdEpisode($workflow){
        global $ilCtrl;
        $ilCtrl->setParameterByClass("ilobjmatterhorngui", "id", $workflow['id']);
        $onhold_episode = array(
            'title' => $workflow["mediapackage"]['title'],
            'trimurl' => $ilCtrl->getLinkTargetByClass("ilobjmatterhorngui", "showTrimEditor"),
            'date' => $workflow["mediapackage"]['start'],
        );
        return $onhold_episode;
        
    }
    
    public function getEpisodes() {
	        $processingEpisodes = $this->object->getProcessingEpisodes();
	        $tempEpisodes = $processingEpisodes['workflows'];
	        $process_items = array();
	        if(is_array($tempEpisodes) && 0 < $tempEpisodes['totalCount']){
	            if(1 == $tempEpisodes['totalCount']){
	                $process_items[] = $this->extractProcessingEpisodes($tempEpisodes['workflow']);
	            }else {
	                foreach($tempEpisodes['workflow'] as $workflow) {
	                    $process_items[] = $this->extractProcessingEpisodes($workflow);
	                }
	            }
	        }
	        uasort($process_items,array($this, 'sortbydate'));
	        foreach ($process_items as $key=>$value){
	            $process_items[$key]["date"] = ilDatePresentation::formatDate(new ilDateTime($value["date"],IL_CAL_DATETIME));
	        }
		
	        $finished_episodes = $this->extractReleasedEpisodes();
	        foreach ($finished_episodes as $key=>$value){
	            $finished_episodes[$key]["date"] = ilDatePresentation::formatDate(new ilDateTime($value["date"],IL_CAL_DATETIME));
	        }	
	
	        $scheduled_items = array();
	        $scheduledEpisodes = $this->object->getScheduledEpisodes();
	        $tempEpisodes = $scheduledEpisodes['workflows'];
	        if(is_array($tempEpisodes) && 0 < $tempEpisodes['totalCount']){
	            if(1 == $tempEpisodes['totalCount']){
	                $workflow = $tempEpisodes['workflow'];
	                $scheduled_items[] = $this->extractScheduledEpisode($workflow);	                 
	            }else {
	                foreach($tempEpisodes['workflow'] as $workflow) {
	                    $scheduled_items[] = $this->extractScheduledEpisode($workflow);	                     
	                }
	            }
	        }
	        uasort($scheduled_items,array($this, 'sortbystartdate'));
	        foreach ($scheduled_items as $key=>$value){
	            $scheduled_items[$key]["startdate"] = ilDatePresentation::formatDate(new ilDateTime($value["startdate"],IL_CAL_UNIX));
	            $scheduled_items[$key]["stopdate"] = ilDatePresentation::formatDate(new ilDateTime($value["stopdate"],IL_CAL_UNIX));
	        }
	
	
	        $onhold_items = array();
	        $onHoldEpisodes = $this->object->getOnHoldEpisodes();
	        $tempEpisodes = $onHoldEpisodes['workflows'];
	        if(is_array($tempEpisodes) && 0 < $tempEpisodes['totalCount']){
	            if(1 == $tempEpisodes['totalCount']){
	                $workflow = $tempEpisodes['workflow'];
	                $onhold_items[] = $this->extractOnholdEpisode($workflow);
	            }else {
	                foreach($tempEpisodes['workflow'] as $workflow) {
	                    $onhold_items[] = $this->extractOnholdEpisode($workflow);
	                }
	            }
	        }
	
	        uasort($onhold_items,array($this, 'sortbydate'));
	        foreach ($onhold_items as $key=>$value){
	            $onhold_items[$key]["date"] = ilDatePresentation::formatDate(new ilDateTime($value["date"],IL_CAL_DATETIME));
	        }
	
	        $data = array();
	        $data['lastupdate'] = $this->object->getLastFSInodeUpdate();
	        $data['finished'] = $finished_episodes;
	        $data['processing'] = $process_items;
	        $data['onhold'] = $onhold_items;
	        $data['scheduled'] = $scheduled_items;
	        header('Vary: Accept');
	        header('Content-type: application/json');
	        echo json_encode($data);
	        // no further processing!
	        exit;
	    }
	

	private function sortbydate($a, $b, $field="date") {
	    if ($a[$field] == $b[$field]) {
        	return 0;
	    }
	    return ($a[$field] < $b[$field]) ? -1 : 1;
	}

	private function sortbystartdate($a, $b) {
	        return $this->sortbydate($a,$b,"startdate");
    }

	
	function editEpisodes(){
        global $tpl, $ilTabs, $ilCtrl;
        $editbase = "./Customizing/global/plugins/Services/Repository/RepositoryObject/Matterhorn/templates/edit";
        $this->checkPermission("write");

        $seriestpl = new ilTemplate("tpl.series.edit.html", true, true,  "Customizing/global/plugins/Services/Repository/RepositoryObject/Matterhorn/");
        $seriestpl->setCurrentBlock("pagestart");
        $seriestpl->setVariable("TXT_UPLOAD_FILE", $this->getText("upload_file"));
        $seriestpl->setVariable("TXT_NO_FILES", $this->getText("no_files"));
        $seriestpl->setVariable("TXT_NONE_FINISHED", $this->getText("none_finished"));
        $seriestpl->setVariable("TXT_NONE_PROCESSING", $this->getText("none_processing"));                                
        $seriestpl->setVariable("TXT_NONE_ONHOLD", $this->getText("none_onhold"));
        $seriestpl->setVariable("TXT_NONE_SCHEDULED", $this->getText("none_scheduled"));
        $seriestpl->setVariable("INITJS",$editbase );
        $seriestpl->setVariable("CMD_PROCESSING", $ilCtrl->getLinkTarget($this, "getEpisodes", "", true));
        $seriestpl->setVariable("SERIES_ID",$this->object->getId());
        if ($this->object->getManualRelease()){
        	$seriestpl->setVariable("COLS_FINISHED", "4");
        } else {
        	$seriestpl->setVariable("COLS_FINISHED", "3");
        }
        
        $seriestpl->setVariable("TXT_FINISHED_RECORDINGS", $this->getText("finished_recordings"));
        $seriestpl->parseCurrentBlock();      
        $seriestpl->setCurrentBlock($this->object->getManualRelease()?"headerfinished":"headerfinishednoaction");
        $seriestpl->setVariable("TXT_TITLE", $this->getText("title"));
        $seriestpl->setVariable("TXT_PREVIEW", $this->getText("preview"));
        $seriestpl->setVariable("TXT_DATE", $this->getText("date"));
        if ($this->object->getManualRelease()){
            $seriestpl->setVariable("TXT_ACTION", $this->getText("action"));
        }
        $seriestpl->parseCurrentBlock();        
        $seriestpl->touchblock("footerfinished");

        $seriestpl->setCurrentBlock("processing");
        $seriestpl->setVariable("TXT_PROCESSING", $this->getText("processing"));
        $seriestpl->setVariable("TXT_RUNNING", $this->getText("running"));
        $seriestpl->setVariable("TXT_TITLE", $this->getText("title"));
        $seriestpl->setVariable("TXT_RECORDDATE", $this->getText("recorddate"));
        $seriestpl->setVariable("TXT_PROGRESS", $this->getText("progress"));
        $seriestpl->parseCurrentBlock();
        
        $seriestpl->setCurrentBlock("onhold");
        $seriestpl->setVariable("TXT_ONHOLD_RECORDING", $this->getText("onhold_recordings"));
        $seriestpl->setVariable("TXT_TITLE", $this->getText("title"));
        $seriestpl->setVariable("TXT_RECORDDATE", $this->getText("recorddate"));
        $seriestpl->parseCurrentBlock();

        

        $seriestpl->setCurrentBlock("uploadesction");
        $seriestpl->setVariable("TXT_ADD_NEW_EPISODE", $this->getText("add_new_episode"));
        $seriestpl->setVariable("TXT_TRACK_TITLE", $this->getText("track_title"));
        $seriestpl->setVariable("TXT_TRACK_PRESENTER", $this->getText("track_presenter"));
        $seriestpl->setVariable("TXT_TRACK_DATE", $this->getText("track_date"));
        $seriestpl->setVariable("TXT_TRACK_TIME", $this->getText("track_time"));
        $seriestpl->setVariable("TXT_ADD_FILE", $this->getText("add_file"));
        $seriestpl->parseCurrentBlock();


        $seriestpl->setCurrentBlock("pageend");
        $seriestpl->setVariable("TXT_SCHEDULED_RECORDING", $this->getText("scheduled_recordings"));
        $seriestpl->setVariable("TXT_TITLE", $this->getText("title"));
        $seriestpl->setVariable("TXT_STARTDATE", $this->getText("startdate"));
        $seriestpl->setVariable("TXT_ENDDATE", $this->getText("enddate"));
        $seriestpl->setVariable("TXT_LOCATION", $this->getText("location"));
        $seriestpl->parseCurrentBlock();
        
        $html = $seriestpl->get();
        $tpl->setContent($html);
        $tpl->addCss($this->plugin->getStyleSheetLocation("css/xmh.css"));
        $tpl->setPermanentLink($this->object->getType(), $this->object->getRefId());
        $ilTabs->activateTab("manage");
	}
	
    
    /**$trimview->setVariable("TXT_LEFT_TRACK", $this->getText("startdate"));
     * Show the trim episode Page
     */
    function showTrimEditor()
    {        
        global $tpl, $ilTabs, $ilCtrl, $ilLog;
        $this->checkPermission("write");
        $trimbase = "./Customizing/global/plugins/Services/Repository/RepositoryObject/Matterhorn/templates/trim";

        if (preg_match('/^[0-9a-f\-]+/', $_GET["id"])) {
            $workflow = $this->object->getWorkflow($_GET["id"]);
            $namespaces = $workflow->getNamespaces(true);
            $mediapackage = $workflow->children($namespaces['ns3'])->mediapackage; 
            $ilLog->write($this->object->getSeries());
            if (!strpos($this->object->getSeries(),trim($mediapackage->series))) {
              $ilCtrl->redirect($this, "editEpisodes");
            }
            $previewtracks = array();
            $worktracks = array();            
            foreach($mediapackage->media->track as $track){
                $trackattribs = $track->attributes();
                $ilLog->write((string)$trackattribs['type']." >>".(string)$track->mimetype."<<");
                switch ((string)$trackattribs['type']){
                    case "composite/iliaspreview":
                        if(!array_key_exists("sbs",$previewtracks)){
                            $previewtracks["sbs"] = array();
                        }
                        if("video/mp4" === (string)$track->mimetype){
                            $previewtracks['sbs']['mp4'] = $track;
                            $_SESSION["mhpreviewurlpreviewsbsmp4".$_GET["id"]] = (string)$track->url;
                            $ilLog->write("setting mp4 sbs: ".(string)$trackattribs['type']." ".(string)$track->mimetype);
                        } else {
                            $previewtracks['sbs']['webm'] = $track;
                            $_SESSION["mhpreviewurlpreviewsbswebm".$_GET["id"]] = (string)$track->url;
                            $ilLog->write("setting webm sbs: ".(string)$trackattribs['type']." ".(string)$track->mimetype);
                        }    
                        break;                    
                    case "presentation/preview" :
                        if(!array_key_exists("presentation",$previewtracks)){
                            $previewtracks['presentation'] = array();
                        }
                        if("video/mp4" === (string)$track->mimetype){
                            $previewtracks['presentation']['mp4'] = $track;
                            $_SESSION["mhpreviewurlpreviewpresentationmp4".$_GET["id"]] = (string)$track->url;
                            $ilLog->write("setting mp4 presentation: ".(string)$trackattribs['type']." ".(string)$track->mimetype);
                        } else {
                            $previewtracks['presentation']['webm'] = $track;
                            $_SESSION["mhpreviewurlpreviewpresentationwebm".$_GET["id"]] = (string)$track->url;
                            $ilLog->write("setting webm presentation: ".(string)$trackattribs['type']." ".(string)$track->mimetype);
                        }
                        break;
                    case "presenter/preview" :
                        if(!array_key_exists("presenter",$previewtracks)){
                            $previewtracks['presenter'] = array();
                        }
                        if("video/mp4" === (string)$track->mimetype){
                            $previewtracks['presenter']['mp4'] = $track;
                            $_SESSION["mhpreviewurlpreviewpresentermp4".$_GET["id"]] = (string)$track->url;
                            $ilLog->write("setting mp4 presenter: ".(string)$trackattribs['type']." ".(string)$track->mimetype);
                        } else {
                            $previewtracks['presenter']['webm'] = $track;
                            $_SESSION["mhpreviewurlpreviewpresenterwebm".$_GET["id"]] = (string)$track->url;
                            $ilLog->write("setting webm presenter: ".(string)$trackattribs['type']." ".(string)$track->mimetype);
                        }
                        break;
                    case "presentation/work":
                        $worktracks['presentation'] = $track;
                        break;
                    case "presenter/work":
                        $worktracks['presenter'] = $track;
                        break;
                }
            }       
            $trimview = new ilTemplate("tpl.trimview.html", true, true, "Customizing/global/plugins/Services/Repository/RepositoryObject/Matterhorn/");
            $trimview->setCurrentBlock("formstart");
            $trimview->setVariable("TXT_ILIAS_TRIM_EDITOR", $this->getText("ilias_trim_editor"));
            $trimview->setVariable("TXT_TRACK_TITLE", $this->getText("track_title"));
            $trimview->setVariable("TRACKTITLE",$mediapackage->title);
            $trimview->setVariable("INITJS",$trimbase );
            $trimview->setVariable("CMD_TRIM", $ilCtrl->getFormAction($this, "trimEpisode"));
            $trimview->setVariable("WFID",$_GET["id"]);
            $trimview->parseCurrentBlock();
            if(2 == count($worktracks)){
                $trimview->setCurrentBlock("dualstream");
                $trimview->setVariable("TXT_LEFT_TRACK", $this->getText("keep_left_side"));
                $trimview->setVariable("TXT_RIGHT_TRACK", $this->getText("keep_right_side"));
                $presenterattributes = $worktracks['presenter']->attributes();
                $trimview->setVariable("LEFTTRACKID", $presenterattributes['id']);
                $trimview->setVariable("LEFTTRACKTYPE", $presenterattributes['type']);
                $presentationattributes = $worktracks['presentation']->attributes();
                $trimview->setVariable("RIGHTTRACKID", $presentationattributes['id']);
                $trimview->setVariable("RIGHTTRACKTYPE", $presentationattributes['type']);
                $trimview->setVariable("FLAVORUNSET", $this->getText("flavor_unset"));
                $trimview->setVariable("FLAVORPRESENTER", $this->getText("flavor_presenter"));
                $trimview->setVariable("FLAVORPRESENTATION", $this->getText("flavor_presentation"));
                $trimview->parseCurrentBlock();
            } else {
                $trackkeys = array_keys($worktracks);
                $trackkey = $trackkeys[0];
                $trimview->setCurrentBlock("singlestream");
                $trimview->setVariable("TXT_LEFT_TRACK_SINGLE", $this->getText("left_side_single"));
                $attributes = $worktracks[$trackkey]->attributes();                
                $trimview->setVariable("LEFTTRACKID", $attributes['id']);
                $trimview->setVariable("LEFTTRACKTYPE", $attributes['type']);
                $trimview->setVariable("FLAVORUNSET", $this->getText("flavor_unset"));
                $trimview->setVariable("FLAVORPRESENTER", $this->getText("flavor_presenter"));
                $trimview->setVariable("FLAVORPRESENTATION", $this->getText("flavor_presentation"));    
                $trimview->parseCurrentBlock();
            }
            $trimview->setCurrentBlock("video");
            $trimview->setVariable("TXT_DOWNLOAD_PREVIEW", $this->getText("download_preview"));
            // if there are two tracks, there is also a sbs track. Otherwise use the only track present.
            if (array_key_exists('sbs', $previewtracks)) {
                $downloadurlmp4 = "./Customizing/global/plugins/Services/Repository/RepositoryObject/Matterhorn/MHData/".CLIENT_ID."/".trim($mediapackage->series)."/".$_GET["id"]."/previewsbs.mp4";
                $downloadurlwebm = "./Customizing/global/plugins/Services/Repository/RepositoryObject/Matterhorn/MHData/".CLIENT_ID."/".trim($mediapackage->series)."/".$_GET["id"]."/previewsbs.webm";
            } else {
                if (array_key_exists('presentation', $previewtracks)){
                    $downloadurlmp4 = "./Customizing/global/plugins/Services/Repository/RepositoryObject/Matterhorn/MHData/".CLIENT_ID."/".trim($mediapackage->series)."/".$_GET["id"]."/previewpresentation.mp4";
                    $downloadurlwebm = "./Customizing/global/plugins/Services/Repository/RepositoryObject/Matterhorn/MHData/".CLIENT_ID."/".trim($mediapackage->series)."/".$_GET["id"]."/previewpresentation.webm";
                } else {
                    $downloadurlmp4 = "./Customizing/global/plugins/Services/Repository/RepositoryObject/Matterhorn/MHData/".CLIENT_ID."/".trim($mediapackage->series)."/".$_GET["id"]."/previewpresenter.mp4";
                    $downloadurlwebm = "./Customizing/global/plugins/Services/Repository/RepositoryObject/Matterhorn/MHData/".CLIENT_ID."/".trim($mediapackage->series)."/".$_GET["id"]."/previewpresenter.webm";
                }
            }
            $trimview->setVariable("DOWNLOAD_PREVIEW_URL_MP4", $downloadurlmp4);
            $trimview->setVariable("DOWNLOAD_PREVIEW_URL_WEBM", $downloadurlwebm);
            $mpattribs = $mediapackage->attributes();
            $duration = (int)$mpattribs['duration'];
            $trimview->setVariable("TRACKLENGTH", $duration/1000);
            $trimview->parseCurrentBlock();
            $trimview->setCurrentBlock("formend");
            $hours = floor($duration/3600000);
            $duration = $duration%3600000;
            $min = floor($duration/60000);
            $duration = $duration%60000;
            $sec = floor($duration/1000);            
            $trimview->setVariable("TXT_TRIMIN", $this->getText("trimin"));
            $trimview->setVariable("TXT_TRIMOUT", $this->getText("trimout"));          
            $trimview->setVariable("TXT_CONTINUE", $this->getText("continue"));                          
            $trimview->setVariable("TXT_SET_TO_CURRENT_TIME", $this->getText("set_to_current_time"));
            $trimview->setVariable("TXT_PREVIEW_INPOINT", $this->getText("preview_inpoint"));
            $trimview->setVariable("TXT_PREVIEW_OUTPOINT", $this->getText("preview_outpoint"));                        
            $trimview->setVariable("TXT_INPOINT", $this->getText("inpoint"));
            $trimview->setVariable("TXT_OUTPOINT", $this->getText("outpoint"));                        
            $trimview->setVariable("TRACKLENGTH", sprintf("%d:%02d:%02d",$hours,$min,$sec));
            $trimview->parseCurrentBlock();
            $tpl->setContent($trimview->get());
            $ilTabs->activateTab("manage");
        } else {
            $ilCtrl->redirect($this, "editEpisodes");
        }
        
    }

    public function trimEpisode()
    {
        global $ilCtrl, $ilLog;
        //$ilLog->write("ID:".$_POST["wfid"]);
        if (preg_match('/^[0-9a-f\-]+/', $_POST["wfid"])) {
        
            $workflow = $this->object->getWorkflow($_POST["wfid"]);
            $namespaces = $workflow->getNamespaces(true);
            //$ilLog->write("namespaces: ". print_r($namespaces,true));
            $mediapackage = $workflow->children($namespaces['ns3'])->mediapackage; 
            if (!strpos($this->object->getSeries(),trim($mediapackage->series))) {
                $ilCtrl->redirect($this, "editEpisodes");
            }
            $mediapackagetitle = ilUtil::stripScriptHTML($_POST["tracktitle"]);
            $mediapackage["title"] = $mediapackagetitle;
            $tracks = array();
            if(isset($_POST["lefttrack"])){
                $track = array();
                $track['id'] = ilUtil::stripScriptHTML($_POST["lefttrack"]);
                $track['flavor'] = ilUtil::stripScriptHTML($_POST["lefttrackflavor"]);
                array_push($tracks,$track);
                
            }
            if(isset($_POST["righttrack"])){
                $track = array();
                $track['id'] = ilUtil::stripScriptHTML($_POST["righttrack"]);
                $track['flavor'] = ilUtil::stripScriptHTML($_POST["righttrackflavor"]);
                array_push($tracks,$track);
            }

            foreach($mediapackage->media->track as $track){
                $trackattribs = $track->attributes();
                if(false !== strpos($trackattribs['type'],"work")){
                    $keeptrack = false;
                    foreach($tracks as $guitrack){
                        if($guitrack['id'] === (string)$trackattribs['id']){
                            $trackattribs['type'] = $guitrack['flavor'];
                            $keeptrack = true;
                        }
                    }
                    if(!$keeptrack){
                      $removetrack = $trackattribs['id'];
                    }
                }
            }
            $dom_sxe = dom_import_simplexml($mediapackage);
            
            $dom = new DOMDocument('1.0');
            $dom_sxe = $dom->importNode($dom_sxe, true);
            $dom_sxe = $dom->appendChild($dom_sxe);


            $str_time = preg_replace("/^([\d]{1,2})\:([\d]{2})$/", "00:$1:$2", ilUtil::stripScriptHTML($_POST["trimin"]));
            list($hours, $minutes, $seconds) = sscanf($str_time, "%d:%d:%d");
            $trimin = $hours * 3600 + $minutes * 60 + $seconds;

            $str_time = preg_replace("/^([\d]{1,2})\:([\d]{2})$/", "00:$1:$2", ilUtil::stripScriptHTML($_POST["trimout"]));
            sscanf($str_time, "%d:%d:%d", $hours, $minutes, $seconds);
            $trimout = $hours * 3600 + $minutes * 60 + $seconds;

            $this->object->trim($_POST["wfid"], $dom->saveXML(), $removetrack, $trimin, $trimout);
            
            ilUtil::sendSuccess($this->txt("msg_episode_send_to_triming"), true);
        } else {
            $ilLog->write("ID does not match an episode:".$_POST["wfid"]);
        }                
        $ilCtrl->redirect($this, "editEpisodes");
    }

    
    function getText($a_text){
        return $this->txt($a_text);
    }

    
    
}
?>
