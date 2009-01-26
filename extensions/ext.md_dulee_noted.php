<?php
/* ===========================================================================
ext.md_dulee_noted.php ---------------------------
Add a non-editable (by the user) notes custom field type. 
            
INFO ---------------------------
Developed by: Ryan Masuga, masugadesign.com
Created:   May 18 2008
Last Mod:  Oct 08 2008

Related Thread: http://expressionengine.com/forums/viewthread/79841/

CHANGELOG ---------------------------
1.2.1 - Bug fix for PHP 4 (Constructor function name was wrong)
1.2.0 - Work with LG Addon Updater, MSM, hook changes
1.1.0 - Rewrote how stuff is added to the header. Added update checking.
1.0.8 - Initial release.

http://expressionengine.com/docs/development/extensions.html
=============================================================================== */
if ( ! defined('EXT')) { exit('Invalid file request'); }


if ( ! defined('MD_DN_version')){
	define("MD_DN_version",			"1.2.1");
	define("MD_DN_docs_url",		"http://www.masugadesign.com/the-lab/scripts/dulee-noted/");
	define("MD_DN_addon_id",		"MD Dulee Noted");
	define("MD_DN_extension_class",	"Md_dulee_noted");
	define("MD_DN_cache_name",		"mdesign_cache");
}

class Md_dulee_noted
{

	var $settings		= array();
	var $name           = 'MD Dulee Noted';
	var $type           = 'md_notes';
	var $version        = MD_DN_version;
	var $description    = 'Create a non-editable note field type for adding general weblog instructions.';
	var $settings_exist = 'y';
	var $docs_url       = MD_DN_docs_url;

// --------------------------------
//  PHP 4 Constructor
// --------------------------------
	function Md_dulee_noted($settings='')
	{
		$this->__construct($settings);
	}

// --------------------------------
//  PHP 5 Constructor
// --------------------------------
	function __construct($settings='')
	{
		global $IN, $SESS;
		if(isset($SESS->cache['mdesign']) === FALSE){ $SESS->cache['mdesign'] = array();}
		$this->settings = $this->_get_settings();
		$this->debug = $IN->GBL('debug');
	}


	function _get_settings($force_refresh = FALSE, $return_all = FALSE)
	{
		global $SESS, $DB, $REGX, $LANG, $PREFS;

		// assume there are no settings
		$settings = FALSE;

		// Get the settings for the extension
		if(isset($SESS->cache['mdesign'][MD_DN_addon_id]['settings']) === FALSE || $force_refresh === TRUE)
		{
			// check the db for extension settings
			$query = $DB->query("SELECT settings FROM exp_extensions WHERE enabled = 'y' AND class = '" . MD_DN_extension_class . "' LIMIT 1");

			// if there is a row and the row has settings
			if ($query->num_rows > 0 && $query->row['settings'] != '')
			{
				// save them to the cache
				$SESS->cache['mdesign'][MD_DN_addon_id]['settings'] = $REGX->array_stripslashes(unserialize($query->row['settings']));
			}
		}

		// check to see if the session has been set
		// if it has return the session
		// if not return false
		if(empty($SESS->cache['mdesign'][MD_DN_addon_id]['settings']) !== TRUE)
		{
			$settings = ($return_all === TRUE) ?  $SESS->cache['mdesign'][MD_DN_addon_id]['settings'] : $SESS->cache['mdesign'][MD_DN_addon_id]['settings'][$PREFS->ini('site_id')];
		}
		return $settings;
	}


	function settings_form($current)
	{
		global $DB, $DSP, $LANG, $IN, $PREFS, $SESS;

		// create a local variable for the site settings
		$settings = $this->_get_settings();

		$DSP->crumbline = TRUE;

		$DSP->title  = $LANG->line('extension_settings');
		$DSP->crumb  = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=utilities', $LANG->line('utilities')).
		$DSP->crumb_item($DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=extensions_manager', $LANG->line('extensions_manager')));

		$DSP->crumb .= $DSP->crumb_item($LANG->line('extension_title') . " {$this->version}");

		$DSP->right_crumb($LANG->line('disable_extension'), BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=toggle_extension_confirm'.AMP.'which=disable'.AMP.'name='.$IN->GBL('name'));

		$DSP->body = '';
		$DSP->body .= $DSP->heading($LANG->line('extension_title') . " <small>{$this->version}</small>");
		$DSP->body .= $DSP->form_open(
								array(
									'action' => 'C=admin'.AMP.'M=utilities'.AMP.'P=save_extension_settings'
								),
								array('name' => strtolower(MD_DN_extension_class))
		);
	
	// EXTENSION ACCESS
	$DSP->body .= $DSP->table_open(array('class' => 'tableBorder', 'border' => '0', 'style' => 'margin-top:18px; width:100%'));

	$DSP->body .= $DSP->tr()
		. $DSP->td('tableHeading', '', '2')
		. $LANG->line("access_rights")
		. $DSP->td_c()
		. $DSP->tr_c();

	$DSP->body .= $DSP->tr()
		. $DSP->td('tableCellOne', '30%')
		. $DSP->qdiv('defaultBold', $LANG->line('enable_extension_for_this_site'))
		. $DSP->td_c();

	$DSP->body .= $DSP->td('tableCellOne')
		. "<select name='enable'>"
					. $DSP->input_select_option('y', "Yes", (($settings['enable'] == 'y') ? 'y' : '' ))
					. $DSP->input_select_option('n', "No", (($settings['enable'] == 'n') ? 'y' : '' ))
					. $DSP->input_select_footer()
		. $DSP->td_c()
		. $DSP->tr_c()
		. $DSP->table_c();
		

		// CSS
		$DSP->body .= $DSP->table_open(array('class' => 'tableBorder', 'border' => '0', 'style' => 'margin-top:18px; width:100%'));

		$DSP->body .= $DSP->tr()
			. $DSP->td('tableHeading', '', '2')
			. $LANG->line("styling_title")
			. $DSP->td_c()
			. $DSP->tr_c();

		$DSP->body .= $DSP->tr()
			. $DSP->td('tableCellOne', '30%')
			. $DSP->qdiv('defaultBold', $LANG->line('css_label'))
			. $DSP->td_c();

		$DSP->body .= $DSP->td('tableCellTwo')
			. $DSP->input_textarea('css', $settings['css'], 11, 'textarea', '99%')
			. $DSP->td_c()
			. $DSP->tr_c();

		$DSP->body .= $DSP->table_c();


		// UPDATES
		$DSP->body .= $DSP->table_open(array('class' => 'tableBorder', 'border' => '0', 'style' => 'margin-top:18px; width:100%'));

		$DSP->body .= $DSP->tr()
			. $DSP->td('tableHeading', '', '2')
			. $LANG->line("check_for_updates_title")
			. $DSP->td_c()
			. $DSP->tr_c();

		$DSP->body .= $DSP->tr()
			. $DSP->td('', '', '2')
			. "<div class='box' style='border-width:0 0 1px 0; margin:0; padding:10px 5px'><p>" . $LANG->line('check_for_updates_info') . "</p></div>"
			. $DSP->td_c()
			. $DSP->tr_c();

		$DSP->body .= $DSP->tr()
			. $DSP->td('tableCellOne', '40%')
			. $DSP->qdiv('defaultBold', $LANG->line("check_for_updates_label"))
			. $DSP->td_c();

		$DSP->body .= $DSP->td('tableCellOne')
			. "<select name='check_for_updates'>"
				. $DSP->input_select_option('y', "Yes", (($settings['check_for_updates'] == 'y') ? 'y' : '' ))
				. $DSP->input_select_option('n', "No", (($settings['check_for_updates'] == 'n') ? 'y' : '' ))
				. $DSP->input_select_footer()
			. $DSP->td_c()
			. $DSP->tr_c();
			
			$DSP->body .= $DSP->table_c();

	

		$DSP->body .= $DSP->qdiv('itemWrapperTop', $DSP->input_submit("Submit"))
					. $DSP->form_c();
	}


	function save_settings()
	{
		global $DB, $IN, $LANG, $OUT, $PREFS, $REGX, $SESS;

		$LANG->fetch_language_file("md_dulee_noted");

		// create a default settings array
		$default_settings = array(
		//	"allowed_member_groups" => array(),
		//	"weblogs" => array()
		);

		// merge the defaults with our $_POST vars
		$site_settings = array_merge($default_settings, $_POST);

		// unset the name
		unset($site_settings['name']);

		// load the settings from cache or DB
		// force a refresh and return the full site settings
		$settings = $this->_get_settings(TRUE, TRUE);

		// add the posted values to the settings
		$settings[$PREFS->ini('site_id')] = $site_settings;

		// update the settings
		$query = $DB->query($sql = "UPDATE exp_extensions SET settings = '" . addslashes(serialize($settings)) . "' WHERE class = '" . MD_DN_extension_class . "'");

		$this->settings = $settings[$PREFS->ini('site_id')];

		if($this->settings['enable'] == 'y')
		{
			if (session_id() == "") session_start(); // if no active session we start a new one
		}
	}


	
	// --------------------------------
	//  Activate Extension
	// --------------------------------
	function activate_extension()
	{
		global $DB, $PREFS;
		
	  $default_settings = array(
									'enable' 			=> 'y',
									'check_for_updates' => 'y',
									'css' => "
/* Solspace Tags 2.0 tweaks for box class */
.publishRows div.box {
  height: auto; 
  padding: 5px 15px;
  }

.publishRows div.box p {
  font-size:12px;
  line-height: 1.4em; 
  color: #333;
  }

.publishRows div.box .left {
  float: left;
  margin: 0 8px 8px 0;
  }
"
  );

		
		// get the list of installed sites
		$query = $DB->query("SELECT * FROM exp_sites");

		// if there are sites - we know there will be at least one but do it anyway
		if ($query->num_rows > 0)
		{
			// for each of the sites
			foreach($query->result as $row)
			{
				// build a multi dimensional array for the settings
				$settings[$row['site_id']] = $default_settings;
			}
		}		
		
		$hooks = array(
		  'show_full_control_panel_end'         => 'show_full_control_panel_end',
			'publish_admin_edit_field_extra_row'  => 'publish_admin_edit_field_extra_row',
			'publish_form_field_unique'           => 'publish_form_field_unique',
			'lg_addon_update_register_source'     => 'lg_addon_update_register_source',
			'lg_addon_update_register_addon'      => 'lg_addon_update_register_addon'
		);
		
		foreach ($hooks as $hook => $method)
		{
			$sql[] = $DB->insert_string( 'exp_extensions', 
				array('extension_id' 	=> '',
					'class'			=> get_class($this),
					'method'		=> $method,
					'hook'			=> $hook,
					'settings'	=> addslashes(serialize($settings)),
					'priority'	=> 10,
					'version'		=> $this->version,
					'enabled'		=> "y"
				)
			);
		}

		// run all sql queries
		foreach ($sql as $query)
		{
			$DB->query($query);
		}
		return TRUE;
	}
	
	
	// --------------------------------
	//  Disable Extension
	// -------------------------------- 
	function disable_extension()
	{
		global $DB;
		$DB->query("DELETE FROM exp_extensions WHERE class = '" . get_class($this) . "'");
	}
	
	// --------------------------------
	//  Update Extension
	// --------------------------------  
	function update_extension($current='')
	{
		global $DB;
		
		if ($current == '' OR $current == $this->version)
		{
			return FALSE;
		}
		
		$DB->query("UPDATE exp_extensions
		            SET version = '".$DB->escape_str($this->version)."'
		            WHERE class = '".get_class($this)."'");
	}
	// END
	
	
	


	
	
	// --------------------------------
	//  Edit Custom Field
	// --------------------------------
	// 
	// <textarea  dir='ltr'  style='width:99%;' name='field_instructions' id='field_instructions' cols='90' rows='6' class='textarea' >
	
	function publish_admin_edit_field_extra_row($data, $r)
	{
		global $EXT, $LANG, $DB, $REGX;
	  // Check if we're not the only one using this hook
		if($EXT->last_call !== FALSE) $r = $EXT->last_call;
		
		
		if($this->settings['enable'] == 'y')
	  {
		// Set which blocks are displayed
		$items = array(
			"date_block" => "block",
			"select_block" => "none",
			"pre_populate" => "none",
			"text_block" => "none",
			"textarea_block" => "none",
			"rel_block" => "none",
			"relationship_type" => "none",
			"formatting_block" => "block",
			"formatting_unavailable" => "none",
			"direction_available" => "none",
			"direction_unavailable" => "none"
		);
		// is this field type equal to this type
		$selected = ($data["field_type"] == $this->type) ? " selected='true'" : "";		
		// Add the option to the select drop down
		$r = preg_replace("/(<select.*?name=.field_type.*?value=.select.*?[\r\n])/is", "$1<option value='" . $REGX->form_prep($this->type) . "'" . $selected . ">" . $REGX->form_prep($this->name) . "</option>\n", $r);
	  $js = "$1\n\t\telse if (id == '".$this->type."'){";
		foreach ($items as $key => $value)
		{
			$js .= "\n\t\t\tdocument.getElementById('" . $key . "').style.display = '" . $value . "';";
		}
    // automatically make this field have no formatting
		$js .= "\n\t\t\tdocument.field_form.field_fmt.selectedIndex = 0;\n";
		$js .= "\t\t}";
		// Add the JS
		$r = preg_replace("/(id\s*==\s*.rel.*?})/is", $js, $r);
    // If existing field, select the proper blocks
		if(isset($data["field_type"]) && $data["field_type"] == $this->type)
		{
			foreach ($items as $key => $value)
			{
				preg_match('/(id=.' . $key . '.*?display:\s*)block/', $r, $match);
				// look for a block
				if(count($match) > 0 && $value == "none")
				{
					$r = str_replace($match[0], $match[1] . $value, $r);
				}
				// no block matches
				elseif($value == "block")
				{ 
					preg_match('/(id=.' . $key . '.*?display:\s*)none/', $r, $match);
					if(count($match) > 0)
					{
						$r = str_replace($match[0], $match[1] . $value, $r);
					}
				}
			}
		}
	} // END if enabled
		return $r;
	}






	// --------------------------------
	//  Render the field - instructions only!
	// --------------------------------
	function publish_form_field_unique( $row, $field_data )
	{
		global $DSP, $EXT, $SESS;
		// Check if we're not the only one using this hook
		$r = ($EXT->last_call !== FALSE) ? $EXT->last_call : "";
		
		if($this->settings['enable'] == 'y')
	  {
		
		if ( ! class_exists('Typography'))
		{
			require PATH_CORE.'core.typography'.EXT;
		}
		
		$TYPE = new Typography;
		
		// http://expressionengine.com/docs/development/usage/typography.html
		$typeprefs = array(
		'text_format'   => $row['field_fmt'],
		'html_format'   => 'all',
		'auto_links'    => 'y',
		'allow_img_url' => 'y'
		);
		
		if($row["field_type"] == $this->type)
		{
		  $r .= "<div class='box'>" . $TYPE->parse_type($row['field_instructions'], $typeprefs) . "</div>";
			$SESS->cache['mdesign'][MD_DN_addon_id]['fields'][] = $row['field_id'];
		}
		
	}
		return $r;
	}

	

	// --------------------------------
	//  Edit Field Group
	// --------------------------------
	
	
	function show_full_control_panel_end($out)
	{
		global $DB, $EXT, $REGX, $IN, $SESS;
		
		// Check if we're not the only one using this hook
		if($EXT->last_call !== FALSE){
			$out = $EXT->last_call;
		}
	
		// Publish or Edit Pages Only
		if($IN->GBL('C', 'GET') == 'publish' || $IN->GBL('C', 'GET') == 'edit')
		{
			if(isset($SESS->cache['mdesign'][MD_DN_addon_id]['fields']) === TRUE)
			{
				foreach ($SESS->cache['mdesign'][MD_DN_addon_id]['fields'] as $field_id) {
					// nice little reg exp
					$pattern = '/(<div id="field_pane_on_(' . $field_id . ')".*?)(<div class=\'paddedWrapper\'.*?<\/div>)/mis';
					// remove the pad area
					$out = preg_replace($pattern, '$1', $out);
				}
				$out = str_replace("</head>", "<style type='text/css'>".$this->settings['css']."</style></head>", $out);
			}
		}
		
		// if we are displaying the custom field list in various places
		if($IN->GBL('M', 'GET') == 'blog_admin' && ($IN->GBL('P', 'GET') == 'field_editor' || $IN->GBL('P', 'GET') == 'update_weblog_fields')  || $IN->GBL('P', 'GET') == 'delete_field' || $IN->GBL('P', 'GET') == 'update_field_order')
		{
			// get the table rows
			if( preg_match_all("/C=admin&amp;M=blog_admin&amp;P=edit_field&amp;field_id=(\d*).*?<\/td>.*?<td.*?>.*?<\/td>.*?<\/td>/is", $out, $matches) )
			{
				// for each field id
				foreach($matches[1] as $key=>$field_id)
				{
					// get the field type
					$query = $DB->query("SELECT field_type FROM exp_weblog_fields WHERE field_id='" . $DB->escape_str($field_id) . "' LIMIT 1");

					// if the field type is this type
					if($query->row["field_type"] == $this->type)
					{
						$out = preg_replace("/(C=admin&amp;M=blog_admin&amp;P=edit_field&amp;field_id=" . $field_id . ".*?<\/td>.*?<td.*?>.*?<\/td>.*?)<\/td>/is", "$1" . $REGX->form_prep($this->name) . "</td>", $out);
					}
				}
			}
		}
		return $out;
	}




	/**
	* Register a new Addon Source
	*/
	function lg_addon_update_register_source($sources)
	{
		global $EXT;
		if($EXT->last_call !== FALSE)
			$sources = $EXT->last_call;
		/*
		<versions>
			<addon id='LG Addon Updater' version='2.0.0' last_updated="1218852797" docs_url="http://leevigraham.com/" />
		</versions>
		*/
		if($this->settings['check_for_updates'] == 'y')
		{
			$sources[] = 'http://masugadesign.com/versions/';
		}
		return $sources;
	}

	/**
	* Register a new Addon
	*/
	function lg_addon_update_register_addon($addons)
	{
		global $EXT;
		if($EXT->last_call !== FALSE)
			$addons = $EXT->last_call;
		if($this->settings['check_for_updates'] == 'y')
		{
			$addons[MD_DN_addon_id] = $this->version;
		}
		return $addons;
	}




  

// END
}
?>