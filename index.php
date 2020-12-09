<?php
/**
 * Plugin Name: Dropdownizer
 * Version: 0.9
 * Plugin URI: https://www.csilverman.com/
 * Description: Dropdowns!
 * Author: Chris Silverman
 * Author URI: https://www.csilverman.com/
 * Requires at least: 4.0
 * Tested up to: 4.0
 *
 * @package WordPress
 * @author Chris Silverman
 * @since 1.0.0
 */


//	https://florianbrinkmann.com/en/display-specific-gutenberg-blocks-of-a-post-outside-of-the-post-content-in-the-theme-5620/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter( 'the_content', 'get_the_blocks' );

/*
	The problem is that I need to use add_filter to invoke the function to begin with, but if I
	then invoke a the_content filter inside the function, that seem to creates a loop.
	
	The only thing I want to do with the filter inside the function is render embeds, so I'm
	going to set up different filter that contains only that functionality. 	
*/

global $wp_embed;
add_filter( 'mytheme_content_filter', array( $wp_embed, 'autoembed')); // remove the priority and see if that changes anything - do we need it?


function get_the_blocks( $content ) {
	
	$content_markup = '';

	global $post;
	
	
	/*	Yeah, I had to get the post content because $content was being filtered;
		I think it had already been rendered. In the examples I'm seeing, parse_blocks is applied directly to raw post content.

		Running parse_blocks() on the_content returned only one block that contained all the page content, instead of the content as the
		individual blocks.
		
		NOTE: This plugin seems to interfere with any filters running on themes. When active, it disables the FAQs and phone linker. That's because it's getting the original content from the database, overwriting the modifications that occur in the theme-based filter function.
		
		So I need to have the theme-based filter run *after* this runs.
		
		Update: stupid thing. I had a priority set on add_filter. I removed it,
		and the priority on the Offices theme's content filter, and they
		run in the correct order now.
	*/
	
	$some_content = get_post_field('post_content', $post->ID);
	
	$blocks = parse_blocks($some_content);
	
	foreach ( $blocks as $block ) {
	
//	    if (strpos( $block['blockName'], 'core-embed') == '0') {
//		    $content_markup .= render_block( $block );
//	    }
	    if (( $block['blockName'] === 'core/group' ) && ($block['attrs']['className'] === 'dropdown' )) {
		    
		    //	Get the header by iterating over the inner blocks
		    
		    $inner_blocks = $block['innerBlocks'];
		    $inner_block_content = '';

			foreach ( $inner_blocks as $inner_block ) {
				if ( $inner_block['blockName'] === 'core/heading' ) {
					$dropdown_title = strip_tags($inner_block['innerHTML']);
				}
				//	render_block does NOT render YouTube embeds, because reasons that make sense
				//	to somebody named "youknowriad" and no one else.
				//	Fortunately there's a fix for that.
				//	https://github.com/WordPress/gutenberg/issues/14080
				
//				else if (strpos( $inner_block['blockName'], 'core-embed') == '0') {
//					$inner_block_content .= render_block( $inner_block );
//				}
				else {
					$the_content = render_block( $inner_block );
					$inner_block_content .= $the_content;
				}
			}
			
			$inner_block_content = str_ireplace('width: 0px', '', $inner_block_content);

		    $the_dropdown_markup = '<details><summary>'.$dropdown_title.'</summary>'.$inner_block_content.'</details>';
			
			$content_markup .= apply_filters( 'mytheme_content_filter', $the_dropdown_markup);
			
//	        echo apply_filters( 'the_content', render_block( $block ) );
	    }
	    else {
		    $content_markup .= apply_filters( 'mytheme_content_filter', render_block( $block ));
	    }
	}

//	$content_markup = apply_filters( 'the_content', $content_markup );
//	print_r($blocks);

//	$content_markup = str_ireplace('figure', 'xfigure', $content_markup);

	return '<!-- Inserted by Dropdownizer plugin. Forces video embeds contained in dropdowns to have a size --><style>iframe { width: 100% !important; height: 100% !important; }</style>'.$content_markup;

/*
	
	//	$content isn't returning any blocks. I think it's being filtered.
	//	Maybe by apply_filters?
	
	echo '<!-- plugin -->';
	print_r(parse_blocks($content));
	echo '<!-- end plugin -->';
		
	return '<!-- start -->'.$content.'<!-- end -->';
*/

}













