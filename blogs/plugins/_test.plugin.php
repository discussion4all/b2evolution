<?php
/**
 * This file implements the TEST plugin.
 *
 * For the most recent and complete Plugin API documentation
 * see {@link Plugin} in ../evocore/_plugin.class.php.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://cvs.sourceforge.net/viewcvs.py/evocms/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
 *
 * {@internal Open Source relicensing agreement:
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package plugins
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE - {@link http://fplanque.net/}
 * @author blueyed: Daniel HAHLER
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * TEST Plugin
 *
 * This plugin responds to virtually all possible plugin events :P
 *
 * @package plugins
 */
class test_plugin extends Plugin
{
	/**
	 * Variables below MUST be overriden by plugin implementations,
	 * either in the subclass declaration or in the subclass constructor.
	 */
	var $name = 'Test';
	var $code = 'evo_TEST';
	var $priority = 50;
	var $version = 'CVS $Revision$';
	var $author = 'The b2evo Group';
	var $help_url = '';  // empty URL defaults to manual wiki, in this case: http://manual.b2evolution.net/Plugins/test_plugin';

	/*
	 * These variables MAY be overriden.
	 */
	var $apply_rendering = 'opt-out';
	var $number_of_installs = 1;


	/**
	 * Constructor.
	 *
	 * Should set name and description in a localizable fashion.
	 * NOTE FOR PLUGIN DEVELOPERS UNFAMILIAR WITH OBJECT ORIENTED DEV:
	 * This function has the same name as the class, this makes it a "constructor".
	 * This means that this function will be called automagically by PHP when this
	 * plugin class is instantiated ("loaded").
	 */
	function test_plugin()
	{
		$this->short_desc = T_('Test plugin');
		$this->long_desc = T_('This plugin responds to virtually all possible plugin events :P');
	}


	/**
	 * Get the settings that the plugin can use.
	 *
	 * Those settings are transfered into a Settings member object of the plugin
	 * and can be edited in the backoffice (Settings / Plugins).
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @see PluginSettings
	 * @see Plugin::PluginSettingsValidateSet()
	 * @return array
	 */
	function GetDefaultSettings()
	{
		$r = array(
			'click_me' => array(
					'label' => T_('Click me!'),
					'defaultvalue' => '1',
					'type' => 'checkbox',
				),
			'input_me' => array(
					'label' => T_('How are you?'),
					'defaultvalue' => '',
					'note' => T_('Welcome to b2evolution'),
				),
			'my_select' => array(
					'label' => T_('Selector'),
					'id' => $this->classname.'_my_select',
					'onchange' => 'document.getElementById("'.$this->classname.'_a_disabled_one").disabled = ( this.value == "sun" );',
					'defaultvalue' => 'one',
					'type' => 'select',
					'options' => array( 'sun' => T_('Sunday'), 'mon' => T_('Monday') ),
				),
			'a_disabled_one' => array(
					'label' => 'This one is disabled',
					'id' => $this->classname.'_a_disabled_one',
					'type' => 'checkbox',
					'defaultvalue' => '1',
					'disabled' => true, // this can be useful if you detect that something cannot be changed. You probably want to add a 'note' then, too.
					'note' => 'Change the above select input to "'.T_('Monday').'" to enable it.',
				),
			);

		if( isset($this->Settings) )
		{ // we're asked for the settings for editing:
			if( $this->Settings->get('my_select') == 'mon' )
			{
				$r['a_disabled_one']['disabled'] = false;
			}
		}

		return $r;
	}


	/**
	 * We trigger an extra event ourself (which we also provide ourself).
	 *
	 * @return array
	 */
	function GetExtraEvents()
	{
		return array(
				// Gets "min" and "max" as params and should return a random number in between:
				'test_plugin_get_random' => T_('TEST event that returns a random number.'),
			);
	}


	/**
	 * User settings.
	 *
	 * @see Plugin::GetDefaultUserSettings()
	 * @see PluginUserSettings
	 * @see Plugin::PluginUserSettingsValidateSet()
	 * @return array
	 */
	function GetDefaultUserSettings()
	{
		return array(
				'echo_random' => array(
					'label' => T_('Echo a random number in AdminBeginPayload event'),
					'type' => 'checkbox',
					'defaultvalue' => '0',
				),
				'deactivate' => array(
					'label' => T_('Deactivate'),
					'type' => 'checkbox',
					'defaultvalue' => '0',
				),
			);
	}


	/**
	 * Deactive the plugin for the current request if the user wants it so.
	 * @see Plugin::AppendLoginRegisteredUser()
	 */
	function AppendLoginRegisteredUser()
	{
		if( $this->UserSettings->get('deactivate') )
		{
			$this->forget_events();
		}
	}


	/**
	 * Define some dependencies.
	 *
	 * @see Plugin::GetDependencies()
	 * @return array
	 */
	function GetDependencies()
	{
		return array(
				'recommends' => array(
					'events_by_one' => array( array('Foo', 'Bar'), array('FooBar', 'BarFoo') ), // a plugin that provides "Foo" and "Bar", and one (may be the same) that provides "FooBar" and "BarFoo"
					'events' => array( 'some_event', 'some_other_event' ),
					'plugins' => array( array( 'some_plugin', '1' ) ), // at least version 1 of some_plugin
				),

				'requires' => array(
					// Same syntax as with the 'recommends' class above, but would prevent the plugin from being installed.
				),
			);
	}


	/**
	 * Gets asked for, if user settings get updated.
	 *
	 * We just add a note.
	 *
	 * @see Plugin::PluginUserSettingsUpdateAction()
	 */
	function PluginUserSettingsUpdateAction()
	{
		if( $this->UserSettings->get('echo_random') )
		{
			$this->msg( T_('TEST plugin: Random numbers have been disabled.') );
		}
		else
		{
			$this->msg( T_('TEST plugin: Random numbers have been enabled.') );
		}

		return true;
	}


	/**
	 * Event handlers:
	 */

	/**
	 * Event handler: Called when ending the admin html head section.
	 *
	 * @see Plugin::AdminEndHtmlHead()
	 * @param array Associative array of parameters
	 * @return boolean did we do something?
	 */
	function AdminEndHtmlHead( & $params )
	{
		echo '<!-- This comment was added by the TEST plugin -->';

		return true;
	}


	/**
	 * Event handler: Called right after displaying the admin page footer.
	 *
	 * @see Plugin::AdminAfterPageFooter()
	 * @param array Associative array of parameters
	 * @return boolean did we do something?
	 */
	function AdminAfterPageFooter( & $params )
	{
		echo '<p class="footer">This is the TEST plugin responding to the AdminAfterPageFooter event!</p>';

		return true;
	}


	/**
	 * Event handler: Called when displaying editor toolbars.
	 *
	 * @see Plugin::AdminDisplayToolbar()
	 * @param array Associative array of parameters
	 * @return boolean did we display a toolbar?
	 */
	function AdminDisplayToolbar( & $params )
	{
		echo '<div class="edit_toolbar">This is the TEST Toolbar</div>';

		return true;
	}


	/**
	 * Event handler: Called when displaying editor buttons.
	 *
	 * @see Plugin::AdminDisplayEditorButton()
	 * @param array Associative array of parameters
	 * @return boolean did we display ?
	 */
	function AdminDisplayEditorButton( & $params )
	{
		?>
		<input type="button" value="TEST" onclick="alert('Hi! This is the TEST plugin (AdminDisplayEditorButton)!');" />
		<?php
		return true;
	}


	/**
	 * @see Plugin::AdminDisplayItemFormFieldset()
	 */
	function AdminDisplayItemFormFieldset( & $params )
	{
		$params['Form']->begin_fieldset( 'TEST plugin' );
		$params['Form']->info_field( 'TEST plugin', 'This is the TEST plugin responding to the AdminDisplayItemFormFieldset event.' );
		$params['Form']->end_fieldset( 'Foo' );
	}


	/**
	 * @see Plugin::AdminBeforeItemEditCreate()
	 */
	function AdminBeforeItemEditCreate( & $params )
	{
		$this->msg( 'This is the TEST plugin responding to the AdminBeforeItemEditCreate event.' );
	}


	/**
	 * @see Plugin::AdminBeforeItemEditUpdate()
	 */
	function AdminBeforeItemEditUpdate( & $params )
	{
		$this->msg( 'This is the TEST plugin responding to the AdminBeforeItemEditUpdate event.' );
	}


	/**
	 * Event handler: Gets invoked in /admin/_header.php for every backoffice page after
	 *                the menu structure is build. You can use the {@link $AdminUI} object
	 *                to modify it.
	 *
	 * This is the hook to register menu entries. See {@link register_menu_entry()}.
	 *
	 * @see Plugin::AdminAfterMenuInit()
	 */
	function AdminAfterMenuInit()
	{
		$this->register_menu_entry( 'Test tab' );
	}


	/**
	 * Event handler: Called when handling actions for the "Tools" menu.
	 *
	 * Use {@link $Messages} to add Messages for the user.
	 *
	 * @see Plugin::AdminToolAction()
	 */
	function AdminToolAction( $params )
	{
		global $Messages;

		$Messages->add( 'Hello, This is the AdminToolAction for the TEST plugin.' );
	}


	/**
	 * Event handler: Called when displaying the block in the "Tools" menu.
	 *
	 * @see Plugin::AdminToolPayload()
	 */
	function AdminToolPayload( $params )
	{
		echo 'Hello, This is the AdminToolPayload for the TEST plugin.';
	}


	/**
	 * Event handler: Method that gets invoked when our tab (?tab=plug_ID_X) is selected.
	 *
	 * You should catch params (GET/POST) here and do actions (no output!).
	 * Use {@link $Messages} to add messages for the user.
	 *
	 * @see Plugin::AdminTabAction()
	 */
	function AdminTabAction()
	{
		global $Plugins;

		$this->text_from_AdminTabAction = '<p>This is text from AdminTabAction for the TEST plugin.</p>'
			.'<p>Here is a random number: '
			.$Plugins->get_trigger_event_first_return('test_plugin_get_random', array( 'min'=>-1000, 'max'=>1000 )).'</p>';

		if( $this->param_text = param( $this->get_class_id('text') ) )
		{
			$this->text_from_AdminTabAction .= '<p>You have said: '.$this->param_text.'</p>';
		}
	}


	/**
	 * Event handler: Gets invoked when our tab is selected and should get displayed.
	 *
	 * @see Plugin::AdminTabPayload()
	 */
	function AdminTabPayload()
	{
		echo 'Hello, this is the AdminTabPayload for the TEST plugin.';

		echo $this->text_from_AdminTabAction;

		// TODO: this is tedious.. should either be a global function (get_admin_Form()) or a plugin helper..
		$Form = & new Form();
		$Form->begin_form();
		$Form->hidden_ctrl(); // needed to pass the "ctrl=tools" param
		$Form->hiddens_by_key( get_memorized() ); // needed to pass all other memorized params, especially "tab"

		$Form->text_input( $this->get_class_id().'_text', $this->param_text, '20', 'Text' );

		$Form->button_input(); // default "submit" button

		$Form->end_form();
	}


	/**
	 * Event handler: Gets invoked before the main payload in the backoffice.
	 *
	 * @see Plugin::AdminBeginPayload()
	 */
	function AdminBeginPayload()
	{
		global $Plugins;

		echo '<div class="panelblock center">TEST plugin: AdminBeginPayload event.</div>';

		if( $this->UserSettings->get('echo_random') )
		{
			echo '<div class="panelblock center">TEST plugin: A random number requested by user setting: '
					.$Plugins->get_trigger_event_first_return('test_plugin_get_random', array( 'min'=>0, 'max'=>1000 ) ).'</div>';
		}
	}


	/**
	 * Event handler: Called when rendering item/post contents as HTML.
	 *
	 * Note: return value is ignored. You have to change $params['content'].
	 *
	 * @see Plugin::RenderItemAsHtml()
	 */
	function RenderItemAsHtml( & $params )
	{
		$params['data'] = 'TEST['.$params['data'].']TEST';
	}


	/**
	 * Event handler: Called when rendering item/post contents as XML.
	 *
	 * Note: return value is ignored. You have to change $params['content'].
	 *
	 * @see Plugin::RenderItemAsXml()
	 */
	function RenderItemAsXml( & $params )
	{
		// Do the same as with HTML:
		$this->RenderItemAsHtml( $params );
	}


	/**
	 * Event handler: Called when rendering item/post contents other than XML or HTML.
	 *
	 * Note: return value is ignored. You have to change $params['content'].
	 *
	 * @see Plugin::RenderItem()
	 */
	function RenderItem()
	{
		// Do nothing.
	}


	/**
	 * Wrap a to be displayed IP address.
	 * @see Plugin::FilterIpAddress()
	 */
	function FilterIpAddress( & $params )
	{
		$params['data'] = '[[IP:'.$params['data'].' (TEST plugin)]]';
	}


	/**
	 * Event handler: Called before the plugin is installed.
	 * @see Plugin::BeforeInstall()
	 */
	function BeforeInstall()
	{
		global $Plugins;
		$this->msg( 'TEST plugin: BeforeInstall event.' );
		return true;
	}


	/**
	 * Event handler: Called when the plugin has been installed.
	 * @see Plugin::AfterInstall()
	 */
	function AfterInstall()
	{
		$this->msg( 'TEST plugin sucessfully installed. All the hard work we did was adding this message in the AfterInstall event.. ;)' );
	}


	/**
	 * Event handler: Called before the plugin is going to be un-installed.
	 * @see Plugin::BeforeUninstall()
	 */
	function BeforeUninstall()
	{
		$this->msg( 'TEST plugin sucessfully un-installed. All the hard work we did was adding this message.. ;)' );
		return true;
	}


	/**
	 * Event handler: called when a new user has registered.
	 * @see Plugin::AfterUserRegistration()
	 */
	function AfterUserRegistration( $params )
	{
		$this->msg( 'The TEST plugin welcomes the new user '.$params['User']->dget('login').'!' );
	}


	/**
	 * Event handler: Called at the end of the "Login" form.
	 * @see Plugin::DisplayLoginFormFieldset()
	 */
	function DisplayLoginFormFieldset( & $params )
	{
		$params['Form']->info_field( 'TEST plugin', 'This is added by the TEST plugin.' );
	}


	/**
	 * Event handler: Called when a user tries to login.
	 * @see Plugin::LoginAttempt()
	 */
	function LoginAttempt()
	{
		// $this->msg( 'NO LOGIN!', 'login_error' );
		$this->msg( 'This the TEST plugin responding to the LoginAttempt event.', 'note' );
	}


	/**
	 * Automagically login every user as "demouser" who is not logged in and does not
	 * try to currently.
	 *
	 * To enable/test it, change the "if-0" check below to "if( 1 )".
	 *
	 * @see Plugin::AlternateAuthentication()
	 */
	function AlternateAuthentication()
	{
		if( 0 ) // you should only enable it for test purposes, because it automagically logs every user in as "demouser"!
		{
			global $Session, $Messages, $UserCache;

			if( $demo_User = & $UserCache->get_by_login('demouser') )
			{ // demouser exists:
				$Session->set_User( $demo_User );
				$Messages->add( 'Logged in as demouser.', 'success' );
				return true;
			}
		}
	}


	/**
	 * @see Plugin::DisplayValidateAccountFormFieldset()
	 */
	function DisplayValidateAccountFormFieldset( & $params )
	{
		$params['Form']->info( 'TEST plugin', 'This is the TEST plugin responding to the ValidateAccountFormSent event.' );
	}


	/**
	 * Gets provided as plugin event (and gets also used internally for demonstration).
	 *
	 * @param array Associative array of parameters
	 *              'min': mininum number
	 *              'max': maxinum number
	 * @return integer
	 */
	function test_plugin_get_random( & $params )
	{
		return rand( $params['min'], $params['max'] );
	}

}


/*
 * $Log$
 * Revision 1.43  2006/06/13 21:33:40  blueyed
 * Add note when updating PluginUserSettings
 *
 * Revision 1.42  2006/06/06 20:35:50  blueyed
 * Plugins can define extra events that they trigger themselves.
 *
 * Revision 1.41  2006/05/30 19:39:55  fplanque
 * plugin cleanup
 *
 * Revision 1.40  2006/05/24 20:43:19  blueyed
 * Pass "Item" as param to Render* event methods.
 *
 * Revision 1.39  2006/05/22 20:35:37  blueyed
 * Passthrough some attribute of plugin settings, allowing to use JS handlers. Also fixed submitting of disabled form elements.
 *
 * Revision 1.38  2006/05/05 19:36:24  blueyed
 * New events
 *
 * Revision 1.37  2006/05/02 01:47:58  blueyed
 * Normalization
 *
 * Revision 1.36  2006/04/24 15:43:37  fplanque
 * no message
 *
 * Revision 1.35  2006/04/22 02:36:39  blueyed
 * Validate users on registration through email link (+cleanup around it)
 *
 * Revision 1.34  2006/04/21 16:53:27  blueyed
 * Bumping TODO, please comment.
 *
 * Revision 1.33  2006/04/20 22:24:08  blueyed
 * plugin hooks cleanup
 *
 * Revision 1.32  2006/04/19 22:26:25  blueyed
 * cleanup/polish
 *
 * Revision 1.31  2006/04/19 20:14:03  fplanque
 * do not restrict to :// (does not catch subdomains, not even www.)
 *
 * Revision 1.30  2006/04/19 18:55:37  blueyed
 * Added login handling hook: AlternateAuthentication
 *
 * Revision 1.29  2006/04/18 17:06:14  blueyed
 * Added "disabled" to plugin (user) settings (Thanks to balupton)
 *
 * Revision 1.28  2006/04/11 21:22:26  fplanque
 * partial cleanup
 *
 */
?>