<?php
// bootstrap 5 wp_nav_menu walker
class bootstrap_5_wp_nav_menu_walker extends Walker_Nav_menu
{
  private $current_item;
  private $dropdown_menu_alignment_values = [
    'dropdown-menu-start',
    'dropdown-menu-end',
    'dropdown-menu-sm-start',
    'dropdown-menu-sm-end',
    'dropdown-menu-md-start',
    'dropdown-menu-md-end',
    'dropdown-menu-lg-start',
    'dropdown-menu-lg-end',
    'dropdown-menu-xl-start',
    'dropdown-menu-xl-end',
    'dropdown-menu-xxl-start',
    'dropdown-menu-xxl-end'
  ];

  function start_lvl(&$output, $depth = 0, $args = null)
  {
    $dropdown_menu_class[] = '';
    foreach($this->current_item->classes as $class) {
      if(in_array($class, $this->dropdown_menu_alignment_values)) {
        $dropdown_menu_class[] = $class;
      }
    }
    $indent = str_repeat("\t", $depth);
    $submenu = ($depth > 0) ? ' sub-menu' : '';
    $output .= "\n$indent<ul class=\"dropdown-menu$submenu " . esc_attr(implode(" ",$dropdown_menu_class)) . " depth_$depth\">\n";
  }

  function start_el(&$output, $item, $depth = 0, $args = null, $id = 0)
  {
    $this->current_item = $item;

    $indent = ($depth) ? str_repeat("\t", $depth) : '';

    $li_attributes = '';
    $class_names = $value = '';

    $classes = empty($item->classes) ? array() : (array) $item->classes;

    $classes[] = ($args->walker->has_children) ? 'dropdown' : '';
    $classes[] = 'nav-item';
    $classes[] = 'nav-item-' . $item->ID;
    if ($depth && $args->walker->has_children) {
      $classes[] = 'dropdown-menu dropdown-menu-end';
    }

    $class_names =  join(' ', apply_filters('nav_menu_css_class', array_filter($classes), $item, $args));
    $class_names = ' class="' . esc_attr($class_names) . '"';

    $id = apply_filters('nav_menu_item_id', 'menu-item-' . $item->ID, $item, $args);
    $id = strlen($id) ? ' id="' . esc_attr($id) . '"' : '';

    $output .= $indent . '<li ' . $id . $value . $class_names . $li_attributes . '>';

    $attributes = !empty($item->attr_title) ? ' title="' . esc_attr($item->attr_title) . '"' : '';
    $attributes .= !empty($item->target) ? ' target="' . esc_attr($item->target) . '"' : '';
    $attributes .= !empty($item->xfn) ? ' rel="' . esc_attr($item->xfn) . '"' : '';
    $attributes .= !empty($item->url) ? ' href="' . esc_attr($item->url) . '"' : '';

    // ACTIVE CLASSI KALDIRILDI
    $active_class = ($item->current || $item->current_item_ancestor || in_array("current_page_parent", $item->classes, true) || in_array("current-post-ancestor", $item->classes, true)) ? '' : '';
    $nav_link_class = ( $depth > 0 ) ? 'dropdown-item ' : 'nav-link d-flex align-items-center';
    $attributes .= ( $args->walker->has_children ) ? ' class="'. $nav_link_class . $active_class . ' dropdown-toggle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false"' : ' class="'. $nav_link_class . $active_class . '"';

    $item_output = $args->before;
    $item_output .= '<a' . $attributes . '>';
    $menu_ikon = get_post_meta($item->ID, 'menu-ikon', true);
    if(!$menu_ikon) {
      $menu_ikon = "";
    }
    $item_output .= $args->link_before . $menu_ikon . apply_filters('the_title', $item->title, $item->ID) . $args->link_after;
    $item_output .= '</a>';
    $item_output .= $args->after;

    $output .= apply_filters('walker_nav_menu_start_el', $item_output, $item, $depth, $args);
  }
}


class Custom_Walker_Nav_Menu extends Walker_Nav_Menu {
  private $is_last_parent = false; // Son parent kontrolü için

  // Menü öğesi (ul) başlatma işlemi
  function start_lvl( &$output, $depth = 0, $args = null ) {
      // Eğer son parent'ı bulduysak, child öğelerini dışarı yazdır
      if ($this->is_last_parent && $depth == 1) {
          $output .= '<ul class="sub-menu">';
      } else {
          parent::start_lvl( $output, $depth, $args );
      }
  }

  // Menü öğesi (ul) sonlandırma işlemi
  function end_lvl( &$output, $depth = 0, $args = null ) {
      // Eğer son parent'ı bulduysak, child öğelerinin kapanışını yap
      if ($this->is_last_parent && $depth == 1) {
          $output .= '</ul>';
      } else {
          parent::end_lvl( $output, $depth, $args );
      }
  }

  // Menü öğesi (li) başlatma işlemi
  function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
      // Eğer item, son parent'taysa child'ları dışarı al
      if ($depth == 0 && $item->menu_item_parent == 0) {
          $this->is_last_parent = true; // Son parent'ı işaretle
      }

      // Eğer son parent'ın child öğesindeysek, onları dışarıya yazdır
      if ($this->is_last_parent && $depth == 1) {
          $output .= '<li class="nav-item">';
          $output .= '<a class="nav-link" href="' . esc_url($item->url) . '">' . esc_html($item->title) . '</a>';
          $output .= '</li>';
      } else {
          parent::start_el( $output, $item, $depth, $args, $id );
      }
  }

  // Menü öğesi (li) sonlandırma işlemi
  function end_el( &$output, $item, $depth = 0, $args = null ) {
      // Eğer son parent'ı bulduysak, sonlandırma yapılmaz
      if ($this->is_last_parent && $depth == 1) {
          // Child öğesi dışarı alındı, sonlandırma yapılmaz
      } else {
          parent::end_el( $output, $item, $depth, $args );
      }
  }
}

