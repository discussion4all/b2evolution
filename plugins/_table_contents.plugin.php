<?php
/**
 * This file implements the Table of Contents plugin for b2evolution
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2019 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package plugins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Table of Contents plugin.
 *
 * @package plugins
 */
class table_contents_plugin extends Plugin
{
	var $name;
	var $code = 'b2evoTOC';
	var $priority = 110;
	var $version = '6.11.3';
	var $group = 'rendering';
	var $subgroup = 'infoitem';
	var $short_desc;
	var $long_desc;
	var $help_topic = 'table-of-contents-plugin';
	var $widget_icon = 'list';
	var $number_of_installs = 1;


	/**
	 * Init
	 */
	function PluginInit( & $params )
	{
		$this->name = T_('Table of Contents');
		$this->short_desc = T_('Render table of contents from header html tags.');
		$this->long_desc = sprintf( T_('This renderer generates a (nested) bullet list from all %s found in the content by short tag %s'), '<code>&lt;Hx id="xxx"&gt;</code>', '<code>[toc]</code>' );
	}


	/**
	 * Perform rendering
	 *
	 * @param array Associative array of parameters
	 * @return boolean true if we can render something for the required output format
	 */
	function RenderItemAsHtml( & $params )
	{
		$content = & $params['data'];

		// Reset list from previous content:
		$this->cached_toc = NULL;

		// Data for using inside callback function below:
		$this->current_Item = $this->get_Item_from_params( $params );
		$this->current_content = $content;

		// Replace `[toc]` short tag with nested/bullet list from all found header anchored tags:
		$content = replace_content_outcode( '#\[toc\]#i', array( $this, 'callback_render_toc' ), $content, 'replace_content_callback' );

		return true;
	}


	/**
	 * Perform rendering of Message content
	 *
	 * @see Plugin::RenderMessageAsHtml()
	 */
	function RenderMessageAsHtml( & $params )
	{
		return true;
	}


	/**
	 * Perform rendering of Email content
	 *
	 * @see Plugin::RenderEmailAsHtml()
	 */
	function RenderEmailAsHtml( & $params )
	{
		return true;
	}


	function FilterCommentContent( & $params )
	{
		$Comment = & $params['Comment'];
		if( in_array( $this->code, $Comment->get_renderers_validated() ) )
		{	// Always allow rendering for comment:
			$comment_Item = & $Comment->get_Item();
			$render_params = array_merge( array( 'data' => & $Comment->content, 'Item' => & $comment_Item ), $params );
			$this->RenderItemAsHtml( $render_params );
		}
		return false;
	}


	/**
	 * Generate table of contents from content
	 *
	 * @param object Item
	 * @param string Content
	 * @return string Rendered content
	 */
	function genereate_toc( $Item, $content )
	{
		$toc = '';

		if( empty( $Item ) )
		{	// Item must be defined for initialize URL:
			return $toc;
		}

		if( preg_match_outcode( '#<h([1-6])[^>]+id=([^>\s]+)[^>]*>(.+?)</h\1>#i', $content, $header_matches ) )
		{	// If at least one `<Hx id="xxx">` is found in content:
			$item_url = $Item->get_permanent_url();
			$min_header_level = min( $header_matches[1] );
			$toc .= '<ul class="evo_plugin__table_of_contents">';
			foreach( $header_matches[3] as $h => $header_text )
			{
				$toc .= '<li style="margin-left:'.( ( $header_matches[1][ $h ] - $min_header_level ) * 10 ).'px">'
						.'<a href="'.$item_url.'#'.trim( $header_matches[2][ $h ], '"\'' ).'">'.utf8_strip_tags( $header_text ).'</a>'
					.'</li>';
			}
			$toc .= '</ul>';
		}

		return $toc;
	}


	/**
	 * Callback function to render table of contents short tag
	 *
	 * @param array Matches of `[toc]` short tag
	 */
	function callback_render_toc( $toc_matches )
	{
		if( $this->cached_toc === NULL )
		{	// Initialize table of contents once for the requested content and store into cache:
			$this->cached_toc = $this->genereate_toc( $this->current_Item, $this->current_content );
		}

		return $this->cached_toc;
	}


	/**
	 * Get keys for block/widget caching
	 *
	 * Maybe be overriden by some widgets, depending on what THEY depend on..
	 *
	 * @param integer Widget ID
	 * @return array of keys this widget depends on
	 */
	function get_widget_cache_keys( $widget_ID = 0 )
	{
		global $Collection, $Blog, $Item;

		return array(
				'wi_ID'        => $widget_ID, // Have the widget settings changed ?
				'set_coll_ID'  => isset( $Blog ) ? $Blog->ID : NULL, // Have the settings of the blog changed ? (ex: new skin)
				'cont_coll_ID' => isset( $Blog ) ? $Blog->ID : NULL, // Has the content of the displayed blog changed ?
				'item_ID'      => isset( $Item ) ? $Item->ID : NULL, // Has the Item page changed?
			);
	}


	/**
	 * Get definitions for widget specific editable params
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @param local params like 'for_editing' => true
	 */
	function get_widget_param_definitions( $params )
	{
		return array(
			'title' => array(
				'label' => T_('Title'),
				'size' => 60,
				'defaultvalue' => T_('Table of Contents'),
			),
		);
	}


	/**
	 * Event handler: SkinTag (widget)
	 *
	 * @param array Associative array of parameters.
	 * @return boolean did we display?
	 */
	function SkinTag( & $params )
	{
		global $Item, $disp;

		if( $disp != 'single' && $disp != 'page' )
		{	// Don't display this widget for not post pages:
			return false;
		}

		if( empty( $Item ) )
		{	// Don't display this widget when no Item object:
			return false;
		}

		// Generate table of contents:
		$toc = $this->genereate_toc( $Item, $Item->get_prerendered_content( 'htmlbody' ) );

		if( empty( $toc ) )
		{	// Don't display widget when current Item has no anchor header tags in content:
			return false;
		}

		echo $params['block_start'];

		$widget_title = $this->get_widget_setting( 'title', $params );
		if( ! empty( $widget_title ) )
		{	// We want to display a title for the widget block:
			echo $params['block_title_start'];
			echo $widget_title;
			echo $params['block_title_end'];
		}

		echo $params['block_body_start'];

		// Display table of contents:
		echo $toc;

		echo $params['block_body_end'];

		echo $params['block_end'];

		return true;
	}
}

?>