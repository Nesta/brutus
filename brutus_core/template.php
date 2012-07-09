<?php
// Auto-rebuild the theme registry during theme development.
if (theme_get_setting('brutus_core_rebuild_registry')) {
  drupal_rebuild_theme_registry();
}

function brutus_core_theme(&$existing, $type, $theme, $path) {
  // get all directories inside theme 
  // http://www.3oheme.com/blog/como-hacer-que-drupal-busque-ficheros-tpl-en-todos-los-subdirectorios-de-tu-theme
 
  $patho = drupal_get_path('theme', 'brutus_core');
  $path = $patho . '/' . 'templates';
  $results = scandir($path);
    foreach ($results as $result) {
        if ($result !== '.' && $result !== '..' && is_dir($path . '/' . $result)) {
              $existing['node']['theme paths'][] = $path . '/' . '/' . $result;
              $existing['block']['theme paths'][] = $path . '/' . $result;
              $existing['page']['theme paths'][] = $path . '/' . $result;
              $existing['content_field']['theme paths'][] = $path . '/' . $result;
            }
      }
    return array();
  }    


/**
 * Page preprocessing
 */
function brutus_core_preprocess_page(&$vars) {
  global $language, $theme_key, $theme_info, $user;

   $vars['doctype'] = '<!DOCTYPE html>' . "\n";
    $vars['html5shim'] = '<!--[if lt IE 9]><script src="'. base_path() . path_to_theme() .'/scripts/html5forIE.js"></script><![endif]-->';
  
  // Add to array of helpful body classes
  $body_classes = explode(' ', $vars['body_classes']);                                               // Default classes
  if (isset($vars['node'])) {
    $body_classes[] = ($vars['node']) ? 'full-node' : '';                                            // Full node
    $body_classes[] = (($vars['node']->type == 'forum') || (arg(0) == 'forum')) ? 'forum' : '';      // Forum page
  }
  else {
    $body_classes[] = (arg(0) == 'forum') ? 'forum' : '';                                            // Forum page
  }
  if (module_exists('panels') && function_exists('panels_get_current_page_display')) {               // Panels page
    $body_classes[] = (panels_get_current_page_display()) ? 'panels' : '';
  }
  $body_classes[] = 'layout-'. (($vars['sidebar_first']) ? 'first-main' : 'main') . (($vars['sidebar_second']) ? '-last' : '');  // Sidebars active
  $body_classes = array_filter($body_classes);                                                       // Remove empty elements
  $vars['body_classes'] = implode(' ', $body_classes);                                               // Create class list separated by spaces
  $vars['body_id'] = 'pid-' . strtolower(brutus_core_clean_css_identifier(drupal_get_path_alias($_GET['q'])));            // Add a unique page id

  // Generate links tree & add Superfish class if dropdown enabled, else make standard primary links
  $vars['primary_links_tree'] = '';
  if ($vars['primary_links']) {
    if (theme_get_setting('primary_menu_dropdown') == 1) {
      // Check for menu internationalization
      if (module_exists('i18nmenu')) {
        $vars['primary_links_tree'] = i18nmenu_translated_tree(variable_get('menu_primary_links_source', 'primary-links'));
      }
      else {
        $vars['primary_links_tree'] = menu_tree(variable_get('menu_primary_links_source', 'primary-links'));
      }
      $vars['primary_links_tree'] = preg_replace('/<nav class="menu/i', '<nav class="menu sf-menu', $vars['primary_links_tree'], 1);
    }
    else {
      $vars['primary_links_tree'] = theme('links', $vars['primary_links'], array('class' => 'menu'));
    }
  }
}

/**
 * Node preprocessing
 */
function brutus_core_preprocess_node(&$vars) {
  // Build array of handy node classes
  $node_classes = array();
  $node_classes[] = $vars['zebra'];                                      // Node is odd or even
  $node_classes[] = (!$vars['node']->status) ? 'node-unpublished' : '';  // Node is unpublished
  $node_classes[] = ($vars['sticky']) ? 'sticky' : '';                   // Node is sticky
  $node_classes[] = (isset($vars['node']->teaser)) ? 'teaser' : 'full-node';    // Node is teaser or full-node
  $node_classes[] = 'node-type-'. $vars['node']->type;                   // Node is type-x, e.g., node-type-page
  $node_classes[] = (isset($vars['skinr'])) ? $vars['skinr'] : '';       // Add Skinr classes if present
  $node_classes = array_filter($node_classes);                           // Remove empty elements
  $vars['node_classes'] = implode(' ', $node_classes);                   // Implode class list with spaces

  // Add node_top and node_bottom region content
  $vars['node_top'] = theme('blocks', 'node_top');
  $vars['node_bottom'] = theme('blocks', 'node_bottom');
  
  // Add format date
  $vars['submitted'] = t('Submitted by !username on', array('!username' => $vars['name']));
  $vars['submitted_date'] = t('!datetime', array('!datetime' => $vars['date']));
  $vars['submitted_pubdate'] = format_date($vars['created'], 'custom', 'Y-m-d');


}

/**
 * Views preprocessing
 * Add view type class (e.g., node, teaser, list, table)
 */
function brutus_core_preprocess_views_view(&$vars) {
  $vars['css_name'] = $vars['css_name'] .' view-style-'. views_css_safe(strtolower($vars['view']->type));
}

// Add Zen Tabs styles
if (theme_get_setting('brutus_core_zen_tabs')) {
  drupal_add_css( drupal_get_path('theme', 'brutus_core') .'/css/tabs.css', 'theme', 'screen');
}

/*
 *	 This function creates the body classes that are relative to each page
 *	
 *	@param $vars
 *	  A sequential array of variables to pass to the theme template.
 *	@param $hook
 *	  The name of the theme function being called ("page" in this case.)
 */


function brutus_core_preprocess_comment_wrapper(&$vars) {
  $classes = array();
  $classes[] = 'comment-wrapper';
  
  // Provide skinr support.
  if (module_exists('skinr')) {
    $classes[] = $vars['skinr'];
  }
  $vars['classes'] = implode(' ', $classes);
}


/*
 *	This function create the EDIT LINKS for blocks and menus blocks.
 *	When overing a block (except in IE6), some links appear to edit
 *	or configure the block. You can then edit the block, and once you are
 *	done, brought back to the first page.
 *
 * @param $vars
 *   A sequential array of variables to pass to the theme template.
 * @param $hook
 *   The name of the theme function being called ("block" in this case.)
 */ 

function brutus_core_preprocess_block(&$vars, $hook) {
    $block = $vars['block'];

    // special block classes
    $classes = array('block');
    $classes[] = brutus_core_id_safe('block-' . $vars['block']->module);
    $classes[] = brutus_core_id_safe('block-' . $vars['block']->region);
    $classes[] = brutus_core_id_safe('block-id-' . $vars['block']->bid);
    $classes[] = 'clearfix';
    
    // support for Skinr Module
    if (module_exists('skinr')) {
      $classes[] = $vars['skinr'];
    }
    
    $vars['block_classes'] = implode(' ', $classes); // Concatenate with spaces

    if (theme_get_setting('brutus_core_block_editing') && user_access('administer blocks')) {
        // Display 'edit block' for custom blocks.
        if ($block->module == 'block') {
          $edit_links[] = l('<span>' . t('edit block') . '</span>', 'admin/build/block/configure/' . $block->module . '/' . $block->delta,
            array(
              'attributes' => array(
                'title' => t('edit the content of this block'),
                'class' => 'block-edit',
              ),
              'query' => drupal_get_destination(),
              'html' => TRUE,
            )
          );
        }
        // Display 'configure' for other blocks.
        else {
          $edit_links[] = l('<span>' . t('configure') . '</span>', 'admin/build/block/configure/' . $block->module . '/' . $block->delta,
            array(
              'attributes' => array(
                'title' => t('configure this block'),
                'class' => 'block-config',
              ),
              'query' => drupal_get_destination(),
              'html' => TRUE,
            )
          );
        }
        // Display 'edit menu' for Menu blocks.
        if (($block->module == 'menu' || ($block->module == 'user' && $block->delta == 1)) && user_access('administer menu')) {
          $menu_name = ($block->module == 'user') ? 'navigation' : $block->delta;
          $edit_links[] = l('<span>' . t('edit menu') . '</span>', 'admin/build/menu-customize/' . $menu_name,
            array(
              'attributes' => array(
                'title' => t('edit the menu that defines this block'),
                'class' => 'block-edit-menu',
              ),
              'query' => drupal_get_destination(),
              'html' => TRUE,
            )
          );
        }
        // Display 'edit menu' for Menu block blocks.
        elseif ($block->module == 'menu_block' && user_access('administer menu')) {
          list($menu_name, ) = split(':', variable_get("menu_block_{$block->delta}_parent", 'navigation:0'));
          $edit_links[] = l('<span>' . t('edit menu') . '</span>', 'admin/build/menu-customize/' . $menu_name,
            array(
              'attributes' => array(
                'title' => t('edit the menu that defines this block'),
                'class' => 'block-edit-menu',
              ),
              'query' => drupal_get_destination(),
              'html' => TRUE,
            )
          );
        }
        $vars['edit_links_array'] = $edit_links;
        $vars['edit_links'] = '<div class="edit">' . implode(' ', $edit_links) . '</div>';
      }
  }

/*
 * Override or insert PHPTemplate variables into the block templates.
 *
 *  @param $vars
 *    An array of variables to pass to the theme template.
 *  @param $hook
 *    The name of the template being rendered ("comment" in this case.)
 */

function brutus_core_preprocess_comment(&$vars, $hook) {
  // Add an "unpublished" flag.
  $vars['unpublished'] = ($vars['comment']->status == COMMENT_NOT_PUBLISHED);

  // If comment subjects are disabled, don't display them.
  if (variable_get('comment_subject_field_' . $vars['node']->type, 1) == 0) {
    $vars['title'] = '';
  }

  // Special classes for comments.
  $classes = array('comment');
  if ($vars['comment']->new) {
    $classes[] = 'comment-new';
  }
  $classes[] = $vars['status'];
  $classes[] = $vars['zebra'];
  if ($vars['id'] == 1) {
    $classes[] = 'first';
  }
  if ($vars['id'] == $vars['node']->comment_count) {
    $classes[] = 'last';
  }
  if ($vars['comment']->uid == 0) {
    // Comment is by an anonymous user.
    $classes[] = 'comment-by-anon';
  }
  else {
    if ($vars['comment']->uid == $vars['node']->uid) {
      // Comment is by the node author.
      $classes[] = 'comment-by-author';
    }
    if ($vars['comment']->uid == $GLOBALS['user']->uid) {
      // Comment was posted by current user.
      $classes[] = 'comment-mine';
    }
  }
  $vars['classes'] = implode(' ', $classes);
}

/* 	
 * 	Customize the PRIMARY and SECONDARY LINKS, to allow the admin tabs to work on all browsers
 * 	An implementation of theme_menu_item_link()
 * 	
 * 	@param $link
 * 	  array The menu item to render.
 * 	@return
 * 	  string The rendered menu item.
 */ 	

function brutus_core_menu_item_link($link) {
  if (empty($link['localized_options'])) {
    $link['localized_options'] = array();
  }

  // If an item is a LOCAL TASK, render it as a tab
  if ($link['type'] & MENU_IS_LOCAL_TASK) {
    $link['title'] = '<span class="tab">' . check_plain($link['title']) . '</span>';
    $link['localized_options']['html'] = TRUE;
  }

  return l($link['title'], $link['href'], $link['localized_options']);
}


/*
 *  Duplicate of theme_menu_local_tasks() but adds clear-block to tabs.
 */

function brutus_core_menu_local_tasks() {
  $output = '';
  if ($primary = menu_primary_local_tasks()) {
    if(menu_secondary_local_tasks()) {
      $output .= '<nav class="tabs primary with-secondary clearfix">' . $primary . '</nav>';
    }
    else {
      $output .= '<nav class="tabs primary clearfix">' . $primary . '</nav>';
    }
  }
  if ($secondary = menu_secondary_local_tasks()) {
    $output .= '<ul class="tabs secondary clearfix">' . $secondary . '</ul>';
  }
  return $output;
}

/* 	
 * 	Add custom classes to menu item "block"
 */	
	
function brutus_core_menu_item($link, $has_children, $menu = '', $in_active_trail = FALSE, $extra_class = NULL) {
$class = ($menu ? 'expanded' : ($has_children ? 'collapsed' : 'leaf'));
  if (!empty($extra_class)) {
    $class .= ' '. $extra_class;
  }
  if ($in_active_trail) {
    $class .= ' active-trail';
  }
if (!empty($link)) {
// remove all HTML tags and make everything lowercase
$css_id = strtolower(strip_tags($link));
// remove colons and anything past colons
if (strpos($css_id, ':')) $css_id = substr ($css_id, 0, strpos($css_id, ':'));
// Preserve alphanumerics, everything else goes away
$pattern = '/[^a-z]+/ ';
$css_id = preg_replace($pattern, '', $css_id);
$class .= ' '. $css_id;
}
return '<li class="'. $class .'">'. $link . $menu ."</li>\n";
}

/*	
 *	Converts a string to a suitable html ID attribute.
 *	
 *	 http://www.w3.org/TR/html4/struct/global.html#h-7.5.2 specifies what makes a
 *	 valid ID attribute in HTML. This function:
 *	
 *	- Ensure an ID starts with an alpha character by optionally adding an 'n'.
 *	- Replaces any character except A-Z, numbers, and underscores with dashes.
 *	- Converts entire string to lowercase.
 *	
 *	@param $string
 *	  The string
 *	@return
 *	  The converted string
 */	

function brutus_core_id_safe($string) {
  // Replace with dashes anything that isn't A-Z, numbers, dashes, or underscores.
  $string = strtolower(preg_replace('/[^a-zA-Z0-9_-]+/', '-', $string));
  // If the first character is not a-z, add 'n' in front.
  if (!ctype_lower($string{0})) { // Don't use ctype_alpha since its locale aware.
    $string = 'id'. $string;
  }
  return $string;
}

/**
* Return a themed breadcrumb trail.
*
* @param $breadcrumb
* An array containing the breadcrumb links.
* @return
* A string containing the breadcrumb output.
*/
function brutus_core_breadcrumb($breadcrumb) {
  // Determine if we are to display the breadcrumb.
  $show_breadcrumb = theme_get_setting('brutus_core_breadcrumb');
  if ($show_breadcrumb == 'yes' || $show_breadcrumb == 'admin' && arg(0) == 'admin') {

    // Optionally get rid of the homepage link.
    $show_breadcrumb_home = theme_get_setting('brutus_core_breadcrumb_home');
    if (!$show_breadcrumb_home) {
      array_shift($breadcrumb);
    }

    // Return the breadcrumb with separators.
    if (!empty($breadcrumb)) {
      $breadcrumb_separator = theme_get_setting('brutus_core_breadcrumb_separator');
      $trailing_separator = $title = '';
      if (theme_get_setting('brutus_core_breadcrumb_title')) {
        if ($title = drupal_get_title()) {
          $trailing_separator = $breadcrumb_separator;
        }
      }
      elseif (theme_get_setting('brutus_core_breadcrumb_trailing')) {
        $trailing_separator = $breadcrumb_separator;
      }
      return '<div class="breadcrumb">' . implode($breadcrumb_separator, $breadcrumb) . "$trailing_separator$title</div>";
    }
  }
  // Otherwise, return an empty string.
  return '';
}
function brutus_core_clean_css_identifier($identifier, $filter = array(' ' => '-', '_' => '-', '/' => '-', '[' => '-', ']' => '')) {
 
   // By default, we filter using Drupal's coding standards.
   $identifier = strtr($identifier, $filter);
 
   // Valid characters in a CSS identifier are:
   // - the hyphen (U+002D)
   // - a-z (U+0030 - U+0039)
   // - A-Z (U+0041 - U+005A)
   // - the underscore (U+005F)
   // - 0-9 (U+0061 - U+007A)
   // - ISO 10646 characters U+00A1 and higher
   // We strip out any character not in the above list.
   $identifier = preg_replace('/[^\x{002D}\x{0030}-\x{0039}\x{0041}-\x{005A}\x{005F}\x{0061}-\x{007A}\x{00A1}-\x{FFFF}]/u', '', $identifier);
 
   return $identifier;
 
 }

/*
PHP CSS Browser Selector v0.0.1
Bastian Allgeier (http://bastian-allgeier.de)
http://bastian-allgeier.de/css_browser_selector
License: http://creativecommons.org/licenses/by/2.5/
Credits: This is a php port from Rafael Lima's original Javascript CSS Browser Selector: http://rafael.adm.br/css_browser_selector
*/

function brutus_core_css_browser_selector($ua=null) {
    $ua = ($ua) ? strtolower($ua) : strtolower($_SERVER['HTTP_USER_AGENT']);    

    $g = 'gecko ';
    $w = 'webkit ';
    $s = 'safari ';
    $b = array();
    
    // browser
    if(!preg_match('/opera|webtv/i', $ua) && preg_match('/msie\s(\d)/', $ua, $array)) {
        $b[] = 'ie ie' . $array[1];
    } else if(strstr($ua, 'firefox/2')) {
        $b[] = $g . ' ff2';   
    } else if(strstr($ua, 'firefox/3.5')) {
        $b[] = $g . ' ff3 ff3_5';
    } else if(strstr($ua, 'firefox/3')) {
        $b[] = $g . ' ff3';
    } else if(strstr($ua, 'gecko/')) {
        $b[] = $g;
    } else if(preg_match('/opera(\s|\/)(\d+)/', $ua, $array)) {
        $b[] = 'opera opera' . $array[2];
    } else if(strstr($ua, 'konqueror')) {
        $b[] = 'konqueror';
    } else if(strstr($ua, 'chrome')) {
        $b[] = $w . ' ' . $s . ' chrome';
    } else if(strstr($ua, 'iron')) {
        $b[] = $w . ' ' . $s . ' iron';
    } else if(strstr($ua, 'applewebkit/')) {
        $b[] = (preg_match('/version\/(\d+)/i', $ua, $array)) ? $w . ' ' . $s . ' ' . $s . $array[1] : $w . ' ' . $s;
    } else if(strstr($ua, 'mozilla/')) {
        $b[] = $g;
    }

    // platform       
    if(strstr($ua, 'j2me')) {
        $b[] = 'mobile';
    } else if(strstr($ua, 'iphone')) {
        $b[] = 'iphone';    
    } else if(strstr($ua, 'ipod')) {
        $b[] = 'ipod';    
    } else if(strstr($ua, 'mac')) {
        $b[] = 'mac';   
    } else if(strstr($ua, 'darwin')) {
        $b[] = 'mac';   
    } else if(strstr($ua, 'webtv')) {
        $b[] = 'webtv';   
    } else if(strstr($ua, 'win')) {
        $b[] = 'win';   
    } else if(strstr($ua, 'freebsd')) {
        $b[] = 'freebsd';   
    } else if(strstr($ua, 'x11') || strstr($ua, 'linux')) {
        $b[] = 'linux';   
    }
        
    return join(' ', $b);
    
}

/**
  *  * Generate doctype for templates
  *   */
function _brutus_doctype() {
    return '<!DOCTYPE html>' . "\n";
}




?>
