<?php

/**
 * @file
 * template.php
 */

/**
 * Implements hook_preprocess_node
 */
function dsv_internal_theme_preprocess_page(&$variables) {
	drupal_add_js('var $ = jQuery;', 'inline');
	if (isset($variables['node']->nid) && ($variables['node']->nid == 41)) {
		drupal_add_js('//www2.dsv.su.se/js/dsv-pp.js', array('type' => 'external', 'scope' => 'footer'));
	}
	global $_SESSION;
	if (!isset($_SESSION['login_reloaded'])) {
		$_SESSION['login_reloaded'] = 1;
		header("Refresh:0");
	}
 // drupal_clear_css_cache();
}

function dsv_internal_theme_preprocess_node(&$variables) {
	if ($variables['submitted']) {
		if ($variables['teaser']) {
      // We add a date to teaser views and align to the left of the title
      $variables['title_prefix'] = '<div class=prefix_node_creation_date>'.format_date($variables['node']->created, 'custom', 'Y-m-d').'</div>';
      // Remove classis 'submitted by' string
			$variables['submitted'] = '';
      // Strip tags for teasers
      if (isset($variables['content']['body'])) {
        $variables['content']['body']['0']['#markup'] = strip_tags($variables['content']['body']['0']['#markup'], '<a>, <br>');
      }
			// Remove images for 'page' content type
			if ($variables['type'] == 'page' && isset($variables['content']['body'])) {
				$variables['content']['body']['0']['#markup'] = preg_replace('/<(\s*)img[^<>]*>/i', '', $variables['content']['body']['0']['#markup']);
				$variables['content']['body']['0']['#markup'] = str_replace('[[dsv_staff]]', '', $variables['content']['body']['0']['#markup']);
			}
		} else {
      if ($variables['type'] == 'calendar_item') {
        $variables['title_suffix'] = '<p class="calendar-title-suffix">Evenemang</p>';
      }
			$user=user_load($variables['uid']);
			$name=format_username($user);
			/*if (!isset($user->field_firstname['und'][0]['value']) || (!isset($user->field_lastname['und'][0]['value']))) {
				$name = $username;
			} else {
				$name = $user->field_firstname['und'][0]['value'] . ' ' . $user->field_lastname['und'][0]['value'];
			}*/
			$variables['submitted'] = 
				t('Page editor').
				t(': !author', array('!author' => $name)).
				'<br>'.
				t('Last edited').
				t(': !datetime', array('!datetime' => format_date($variables['node']->changed, 'utan_tider')));
		}
	}
}

function dsv_internal_theme_preprocess_comment(&$variables) {
  // Change the Permalink to display #1 instead of 'Permalink'
  $comment = $variables['comment'];
  $uri = entity_uri('comment', $comment);
  $uri['options'] += array('attributes' => array(
    'class' => 'permalink',
    'rel' => 'bookmark',
  ));
  $variables['permalink'] = l('#' . $variables['comment']->cid, $uri['path'], $uri['options']);
  $variables['submitted'] = t('!username commented on !datetime', array(
    '!username' => $variables['author'],
    '!datetime' => $variables['created'],
  ));
}

/**
* Implementation of hook_menu_alter().
*/
function dsv_internal_theme_menu_alter(&$menu) {
	if (isset($menu['taxonomy/term/%taxonomy_term'])) {
	//	$menu['taxonomy/term/%taxonomy_term']['page callback'] = 'dsv_internal_theme_taxonomy_term_page';
	}
}
/**
* Callback function for taxonomy/term/%taxonomy_term.
*
* @param $tid
* The term id.
*
* @return
* Themed page for a taxonomy term, specific to the term's vocabulary.
*/
function dsv_internal_theme_taxonomy_term_page($term = 'all') {
	$children = taxonomy_get_children($term->tid);
	if ($children) {
		// If has children taxonomy terms. then show only them
		return views_embed_view('taxonomy_with_terms', 'page', $term->tid);

	} else {
		// If has no chilren, show only content leafs
		return views_embed_view('taxonomy_with_content', 'page', $term->tid);
	}
}

function dsv_internal_theme_menu_link__menu_block($variables) {
  return theme_menu_link($variables);
}

/**
 * Overrides theme_menu_link().
 */
function dsv_internal_theme_menu_link(array $variables) {
  $element = $variables['element'];
  $sub_menu = '';

  if ($element['#below']) {
    // Prevent dropdown functions from being added to management menu so it
    // does not affect the navbar module.
    if (($element['#original_link']['menu_name'] == 'management') && (module_exists('navbar'))) {
      $sub_menu = drupal_render($element['#below']);
    }
    elseif ((!empty($element['#original_link']['depth'])) && ($element['#original_link']['depth'] == 1)) {
      // Add our own wrapper.
      unset($element['#below']['#theme_wrappers']);
      $sub_menu = '<ul class="dropdown-menu">' . drupal_render($element['#below']) . '</ul>';
      // Generate as standard dropdown.
      // $element['#title'] .= ' <span class="caret"></span>';
      $element['#attributes']['class'][] = 'dropdown';
      $element['#localized_options']['html'] = TRUE;

      // Set dropdown trigger element to # to prevent inadvertant page loading
      // when a submenu link is clicked.
      $element['#localized_options']['attributes']['data-target'] = '#';
      // $element['#localized_options']['attributes']['class'][] = 'dropdown-toggle';
      // $element['#localized_options']['attributes']['data-toggle'] = 'dropdown';
    }
  }
  // On primary navigation menu, class 'active' is not set on active menu item.
  // @see https://drupal.org/node/1896674
  if (($element['#href'] == $_GET['q'] || ($element['#href'] == '<front>' && drupal_is_front_page())) && (empty($element['#localized_options']['language']))) {
    $element['#attributes']['class'][] = 'active';
  }
  $output = l($element['#title'], $element['#href'], $element['#localized_options']);
  return '<li' . drupal_attributes($element['#attributes']) . '>' . $output . $sub_menu . "</li>\n";
}

function dsv_internal_theme_aggregator_block_item($variables) {
  // Display the external link to the item.
  return '<a target="_blank" href="' . check_url($variables['item']->link) . '">' . check_plain($variables['item']->title) . "</a>\n";
}

function dsv_internal_theme_more_link ($variables) {
  if ($variables['url'] == 'aggregator/sources/1') {
    return '<div class="more-link">' . l(t('View more'), $variables['url'], array('attributes' => array('title' => $variables['title']))) .
    ' | ' . l(t('Subscribe'), 'https://lists.su.se/mailman/listinfo/driftinfo-at-su.se', array('attributes' => array('title' => t('Subscribe'), 'target' => '_blank'))) . '</div>';
  } else {
    return '<div class="more-link">' . l(t('View more'), $variables['url'], array('attributes' => array('title' => $variables['title']))) . '</div>';    
  }
}

/**
 * Implements template_preprocess_views_view(). Adding subscribe-link below views.
 */
function dsv_internal_theme_preprocess_views_view(&$vars) {
    $view = $vars['view'];
    global $user;
    $uid = $user->uid;
    if ($view->name == 'latest_articles' && $view->current_display == 'block') {
      $sub_anslag = subscriptions_get_subscription($uid, 'node', 'tid', 56);
      $string = $sub_anslag ? "Redigera prenumeration" : "Prenumerera på Anslagstavlan";
      $vars['footer'] = '<a href="nyhetsflode" class="pane-block">Visa fler</a> | <a href="user/'.$user->uid.'/subscriptions/taxa" class="pane-block">'.$string.'</a>';
    }
    if ($view->name == 'feed_calendar_items') {
      $sub_calendar = subscriptions_get_subscription($uid, 'node', 'type', 'calendar_item');
      $string = $sub_calendar ? "Redigera prenumeration" : "Prenumerera på Evenemang";
      $vars['footer'] = '<a href="user/'.$user->uid.'/subscriptions/type" ">'.$string.'</a>';
      if ($vars['more'] && !$vars['pager']) {
        $vars['footer'] = '<a href="evenemang">Visa fler</a> | ' . $vars['footer'];
        $vars['more'] = NULL;
      }
    }
}

function dsv_internal_theme_breadcrumb ($variables) {
  $output = '';
  $breadcrumb = $variables['breadcrumb'];
  // Determine if we are to display the breadcrumb.
  $bootstrap_breadcrumb = theme_get_setting('bootstrap_breadcrumb');
  if (($bootstrap_breadcrumb == 1 || ($bootstrap_breadcrumb == 2 && arg(0) == 'admin')) && !empty($breadcrumb)) {
    if (menu_get_active_title()) {
  		end($breadcrumb);
		$breadcrumb[key($breadcrumb)]['data'] = menu_get_active_title();
  	}
    $output = theme('item_list', array(
      'attributes' => array(
        'class' => array('breadcrumb'),
      ),
      'items' => $breadcrumb,
      'type' => 'ol',
    ));
  }
  return $output;
}
