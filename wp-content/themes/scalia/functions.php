<?php

require get_template_directory() . '/inc/template-tags.php';

require get_template_directory() . '/inc/options.php';
require get_template_directory() . '/inc/content.php';
require get_template_directory() . '/inc/post-types/init.php';
require get_template_directory() . '/inc/woocommerce.php';
require get_template_directory() . '/inc/megamenu/megamenu.class.php';
require get_template_directory() . '/inc/megamenu/megamenu-walker.class.php';

require get_template_directory() . '/plugins/plugins.php';

if ( ! isset( $content_width ) ) {
	$content_width = 1170;
}

if(!function_exists('scalia_setup')) :
function scalia_setup() {
	load_theme_textdomain('scalia', get_template_directory() . '/languages');
	add_theme_support('automatic-feed-links');
	add_theme_support('post-thumbnails');
	add_theme_support('woocommerce');
	add_theme_support('title-tag');
	set_post_thumbnail_size(672, 372, true);
	add_image_size('scalia-post-thumb', 256, 256, true);
	register_nav_menus(array(
		'primary' => __('Top primary menu', 'scalia'),
		'footer'  => __('Footer menu', 'scalia'),
	));
	add_theme_support('html5', array(
		'search-form', 'comment-form', 'comment-list', 'gallery', 'caption'
	));
	add_theme_support('post-formats', array(
		'aside', 'image', 'video', 'audio', 'quote', 'link', 'gallery',
	));
	add_theme_support('custom-background', apply_filters('scalia_custom_background_args', array(
		'default-color' => 'ffffff',
	)));
	add_theme_support('featured-content', array(
		'featured_content_filter' => 'scalia_get_featured_posts',
		'max_posts' => 6,
	));
	add_filter('use_default_gallery_style', '__return_false');

	add_filter('jpeg_quality', create_function('', 'return 80;'));

	if(!get_option('scalia_theme_options')) {
		update_option('scalia_theme_options', scalia_first_install_settings());
		scalia_generate_custom_css();
	}
	if(!get_option('pw_options')) {
		$pw_options = array(
			'donation' => 'yes',
			'customize_by_default' => 'yes',
			'post_types' => array('post', 'page', 'scalia_pf_item', 'scalia_news', 'product'),
			'sidebars' => array('page-sidebar', 'footer-widget-area', 'shop-sidebar'),
		);
		update_option('pw_options', $pw_options);
	}
	if(!get_option('shop_catalog_image_size')) {
		update_option('shop_catalog_image_size', array('width' => 540, 'height' => 670, 'crop' => 1));
	}
	if(!get_option('shop_single_image_size')) {
		update_option('shop_single_image_size', array('width' => 880, 'height' => 1100, 'crop' => 1));
	}
	if(!get_option('shop_thumbnail_image_size')) {
		update_option('shop_thumbnail_image_size', array('width' => 180, 'height' => 180, 'crop' => 1));
	}
	if(!get_option('wpb_js_content_types')) {
		update_option('wpb_js_content_types', array('post', 'page', 'product', 'scalia_news', 'scalia_pf_item'));
	}
	update_option('layerslider-authorized-site', 1);
	$megamenu = new Scalia_Mega_Menu();
	add_filter('attachment_fields_to_edit', 'scalia_attachment_extra_fields', 10, 2);
	add_filter('attachment_fields_to_save', 'scalia_attachment_extra_fields_save', 10, 2);
}
endif;
add_action('after_setup_theme', 'scalia_setup');

function scalia_theme_option_admin_notice() {
	if(isset($_GET['page']) && $_GET['page'] == 'options-framework') {
		if(!is_writable(get_stylesheet_directory() . '/css/custom.css')) {
?>
<div class="error">
	<p><?php printf(__('Scalia\'s styles cannot be customized because file "%s" is not writebale. Please change file permissions. Then click "Save Changes" button.', 'scalia'), get_stylesheet_directory() . '/css/custom.css'); ?></p>
</div>
<?php
		}
	}
}
add_action( 'admin_notices', 'scalia_theme_option_admin_notice' );

function scalia_attachment_extra_fields($fields, $post) {
	$attachment_link = get_post_meta($post->ID, 'attachment_link', true);
    $fields['attachment_link'] = array(
		'input' => 'html',
		'html' => '<input type="text" id="attachments-' . $post->ID . '-attachment_link" style="width: 500px;" name="attachments[' . $post->ID . '][attachment_link]" value="' . esc_attr( $attachment_link ) . '" />',
		'label' => __('Link', 'scalia'),
		'value' => $attachment_link
	);

	$highligh = (bool) get_post_meta($post->ID, 'highlight', true);
	$fields['highlight'] = array(
		'input' => 'html',
		'html' => '<input type="checkbox" id="attachments-' . $post->ID . '-highlight" name="attachments[' . $post->ID . '][highlight]" value="1"' . ($highligh ? ' checked="checked"' : '') . ' />',
		'label' => __('Show as Highlight?', 'scalia'),
		'value' => $highligh
	);
	return $fields;
}

function scalia_attachment_extra_fields_save($post, $attachment) {
	update_post_meta($post['ID'], 'highlight', isset($attachment['highlight']));
	update_post_meta($post['ID'], 'attachment_link', $attachment['attachment_link']);
	return $post;
}

/* SIDEBAR & WIDGETS */

function scalia_count_widgets($sidebar_id) {

	global $_wp_sidebars_widgets, $sidebars_widgets;
	if(!is_admin()) {
		if(empty($_wp_sidebars_widgets))
			$_wp_sidebars_widgets = get_option('sidebars_widgets', array());
		$sidebars_widgets = $_wp_sidebars_widgets;
	} else {
		$sidebars_widgets = get_option('sidebars_widgets', array());
	}
	if(is_array($sidebars_widgets) && isset($sidebars_widgets['array_version']))
		unset($sidebars_widgets['array_version']);

	$sidebars_widgets = apply_filters('sidebars_widgets', $sidebars_widgets);

	if(isset($sidebars_widgets[$sidebar_id])) {
		return count($sidebars_widgets[$sidebar_id]);
	}
	return 0;
}

function scalia_dynamic_sidebar_params($params) {
	$footer_widgets_class = 'col-md-4 col-sm-6 col-xs-12';
	if(scalia_count_widgets('footer-widget-area') >= 4) {
		$footer_widgets_class = 'col-md-3 col-sm-6 col-xs-12';
	}
	if(scalia_count_widgets('footer-widget-area') == 2) {
		$footer_widgets_class = 'col-sm-6 col-xs-12';
	}
	if(scalia_count_widgets('footer-widget-area') == 1) {
		$footer_widgets_class = 'col-xs-12';
	}
	$footer_widgets_class .= ' count-'.scalia_count_widgets('footer-widget-area');
	$params[0]['before_widget'] = str_replace('scalia__footer-widget-class__scalia', esc_attr($footer_widgets_class), $params[0]['before_widget']);
	return $params;
}
add_filter('dynamic_sidebar_params', 'scalia_dynamic_sidebar_params');

function scalia_sidebar_init() {
	register_sidebar(array(
		'name'          => __('Page Sidebar', 'scalia'),
		'id'            => 'page-sidebar',
		'description'   => __('Main sidebar that appears on the left.', 'scalia'),
		'before_widget' => '<aside id="%1$s" class="widget %2$s">',
		'after_widget'  => '</aside>',
		'before_title'  => '<h4 class="widget-title">',
		'after_title'   => '</h4>',
	));
	register_sidebar(array(
		'name'          => __('Footer Widget Area', 'scalia'),
		'id'            => 'footer-widget-area',
		'description'   => __('Footer Widget Area.', 'scalia'),
		'before_widget' => '<div id="%1$s" class="widget inline-column scalia__footer-widget-class__scalia %2$s">',
		'after_widget'  => '</div>',
		'before_title'  => '<h3 class="widget-title">',
		'after_title'   => '</h3>',
	));
	register_sidebar(array(
		'name' => __('Shop sidebar', 'scalia'),
		'id' => 'shop-sidebar',
		'description' => __('Appears on posts and pages except the optional Front Page template, which has its own widgets', 'scalia'),
		'before_widget' => '<section id="%1$s" class="widget %2$s">',
		'after_widget' => '</section>',
		'before_title' => '<h4 class="widget-title">',
		'after_title' => '</h4>',
	));
	register_sidebar(array(
		'name' => __('Shop widget area', 'scalia'),
		'id' => 'shop-widget-area',
		'description' => __('Appears on posts and pages except the optional Front Page template, which has its own widgets', 'scalia'),
		'before_widget' => '<section id="%1$s" class="widget inline-column col-md-4 col-sm-6 col-xs-12 %2$s">',
		'after_widget' => '</section>',
		'before_title' => '<h4 class="widget-title shop-widget-title">',
		'after_title' => '</h4>',
	));
}
add_action('widgets_init', 'scalia_sidebar_init');

function scalia_scripts() {
	wp_enqueue_style('scalia-icons', get_template_directory_uri() . '/css/icons.css');
	wp_enqueue_style('scalia-reset', get_template_directory_uri() . '/css/reset.css');
	wp_enqueue_style('scalia-grid', get_template_directory_uri() . '/css/grid.css');
	wp_enqueue_style('scalia-style', get_stylesheet_uri(), array('scalia-icons', 'scalia-reset', 'scalia-grid'));
	wp_enqueue_style('scalia-ie', get_template_directory_uri() . '/css/ie.css', array('scalia-style', 'scalia-icons', 'scalia-reset', 'scalia-grid'));
	wp_style_add_data('scalia-ie', 'conditional', 'lt IE 9');
	wp_enqueue_style('scalia-header', get_template_directory_uri() . '/css/header.css');
	wp_enqueue_style('scalia-widgets', get_template_directory_uri() . '/css/widgets.css');
	wp_enqueue_style('scalia-portfolio', get_template_directory_uri() . '/css/portfolio.css');
	wp_register_style('scalia-gallery', get_template_directory_uri() . '/css/gallery.css');

	if(file_exists(get_stylesheet_directory() . '/css/custom.css')) {
		wp_enqueue_style('scalia-custom', get_stylesheet_directory_uri() . '/css/custom.css', array('scalia-style', 'scalia-icons', 'scalia-reset', 'scalia-grid', 'scalia-style'));
	}

	if(is_singular() && comments_open() && get_option('thread_comments')) {
		wp_enqueue_script('comment-reply', array(), false, true);
	}

	wp_enqueue_style('js_composer_front');
	wp_enqueue_script('scalia-respond', get_template_directory_uri() . '/js/respond.min.js', false, true, true);
	wp_enqueue_script('scalia-combobox', get_template_directory_uri() . '/js/combobox.js', array('jquery'), false, true);
	wp_enqueue_script('scalia-checkbox', get_template_directory_uri() . '/js/checkbox.js', array('jquery'), false, true);
	wp_enqueue_script('scalia-jquery-easing', get_template_directory_uri() . '/js/jquery.easing.js', array('jquery'), false, true);
	wp_register_script('scalia-diagram-line', get_template_directory_uri() . '/js/diagram_line.js', array('jquery', 'scalia-jquery-easing'), false, true);
	wp_register_script('scalia-raphael-js', get_template_directory_uri() . '/js/raphael.js', array('jquery'), false, true);
	wp_register_script('scalia-diagram-circle', get_template_directory_uri() . '/js/diagram_circle.js', array('jquery', 'scalia-raphael-js'), false, true);
	wp_enqueue_script('scalia-modernizr-script', get_template_directory_uri() . '/js/modernizr.custom.js', array('jquery'), false, true);
	wp_enqueue_script('scalia-dl-menu-script', get_template_directory_uri() . '/js/jquery.dlmenu.js', array('jquery', 'scalia-modernizr-script'), false, true);
	wp_enqueue_script('scalia-header', get_template_directory_uri() . '/js/header.js', array('jquery'), false, true);
	wp_register_script('scalia-carousel', get_template_directory_uri() . '/js/jquery.carouFredSel.js', array('jquery'), false, true);
	wp_register_script('scalia-gallery', get_template_directory_uri() . '/js/gallery.js', array('jquery', 'scalia-carousel'), false, true);
	wp_register_script('scalia-news-carousel', get_template_directory_uri() . '/js/news-carousel.js', array('jquery', 'scalia-carousel'), false, true);
	wp_register_script('scalia-clients-grid-carousel', get_template_directory_uri() . '/js/clients-grid-carousel.js', array('jquery', 'scalia-carousel'), false, true);
	wp_register_script('scalia-testimonials-carousel', get_template_directory_uri() . '/js/testimonials-carousel.js', array('jquery', 'scalia-carousel'), false, true);
	wp_register_script('scalia-widgets', get_template_directory_uri() . '/js/widgets.js', array('jquery', 'scalia-carousel', 'jquery-effects-core'), false, true);
	wp_register_script('scalia-restable', get_template_directory_uri() . '/js/jquery.restable.js', array('jquery'), false, true);
	wp_register_script('scalia-responsive-tabs', get_template_directory_uri() . '/js/easyResponsiveTabs.js', array('jquery'), false, true);
	wp_register_style('scalia-odometr', get_template_directory_uri() . '/css/odometer-theme-default.css');
	wp_register_script('scalia-odometr', get_template_directory_uri() . '/js/odometer.js', array('jquery'), false, true);
	wp_register_script('scalia-quickfinders-effects', get_template_directory_uri() . '/js/quickfinders_effects.js', array('jquery'), false, true);
	wp_register_script('scalia-related-products-carousel', get_template_directory_uri() . '/js/related-products-carousel.js', array('jquery', 'scalia-carousel'), false, true);
	wp_register_script('scalia-sticky', get_template_directory_uri() . '/js/jquery.sticky.js', array('jquery'), false, true);
	wp_register_style('scalia-blog', get_template_directory_uri() . '/css/blog.css');

	/* Lazy Loading */
	wp_enqueue_script('scalia-lazy-loading', get_template_directory_uri() . '/js/jquery.lazyLoading.js', array(), false, true);
	wp_enqueue_script('scalia-transform', get_template_directory_uri() . '/js/jquery.transform.js', array(), false, true);
	wp_enqueue_script('jquery-effects-drop', array(), false, true);


	wp_enqueue_script('scalia-scripts', get_template_directory_uri() . '/js/functions.js', array('jquery', 'scalia-combobox', 'scalia-restable', 'scalia-responsive-tabs', 'scalia-odometr', 'scalia-sticky', 'scalia-dl-menu-script'), false, true);

	wp_enqueue_script('mousewheel-script', get_template_directory_uri() . '/js/fancyBox/jquery.mousewheel.pack.js', array(), false, true);
	wp_enqueue_script('fancybox-script', get_template_directory_uri() . '/js/fancyBox/jquery.fancybox.pack.js', array(), false, true);
	wp_enqueue_script('fancybox-init-script', get_template_directory_uri() . '/js/fancyBox/jquery.fancybox-init.js', array('mousewheel-script', 'fancybox-script'), false, true);
	wp_enqueue_style('fancybox-style', get_template_directory_uri() . '/js/fancyBox/jquery.fancybox.css');

	wp_enqueue_script('scalia-vc_elements', get_template_directory_uri() . '/js/vc_elements_init.js', array('jquery'), false, true);
	wp_enqueue_style('scalia-vc_elements', get_template_directory_uri() . '/css/vc_elements.css');

	wp_register_style('scalia-nivoslider-style', get_template_directory_uri() . '/css/nivo-slider.css', array());
	wp_register_script('scalia-nivoslider-script', get_template_directory_uri() . '/js/jquery.nivo.slider.pack.js', array('jquery'));
	wp_register_script('scalia-nivoslider-init-script', get_template_directory_uri() . '/js/nivoslider-init.js', array('jquery', 'scalia-nivoslider-script'));
	wp_localize_script('scalia-nivoslider-init-script', 'nivoslider_options', array(
		'effect' => scalia_get_option('slider_effect') ? scalia_get_option('slider_effect') : 'random',
		'slices' => scalia_get_option('slider_slices') ? scalia_get_option('slider_slices') : 15,
		'boxCols' => scalia_get_option('slider_boxCols') ? scalia_get_option('slider_boxCols') : 8,
		'boxRows' => scalia_get_option('slider_boxRows') ? scalia_get_option('slider_boxRows') : 4,
		'animSpeed' => scalia_get_option('slider_animSpeed') ? scalia_get_option('slider_animSpeed')*100 : 500,
		'pauseTime' => scalia_get_option('slider_pauseTime') ? scalia_get_option('slider_pauseTime')*1000 : 3000,
		'directionNav' => scalia_get_option('slider_directionNav') ? true : false,
		'controlNav' => scalia_get_option('slider_controlNav') ? true : false,
	));

	wp_register_script('scalia-imagesloaded', get_template_directory_uri() . '/js/imagesloaded.min.js', array('jquery'), '', true);
	wp_register_script('scalia-isotope', get_template_directory_uri() . '/js/isotope.min.js', array('jquery'), '', true);
	wp_register_script('scalia-transform', get_template_directory_uri() . '/js/jquery.transform.js', array('jquery'), '', true);
	wp_register_script('scalia-juraSlider', get_template_directory_uri() . '/js/jquery.juraSlider.js', array('jquery'), '', true);
	wp_register_script('scalia-portfolio', get_template_directory_uri() . '/js/portfolio.js', array('jquery', 'scalia-dl-menu-script'), '', true);
	wp_register_script('scalia-removewhitespace', get_template_directory_uri() . '/js/jquery.removeWhitespace.min.js', array('jquery'), '', true);
	wp_register_script('scalia-collageplus', get_template_directory_uri() . '/js/jquery.collagePlus.min.js', array('jquery'), '', true);
	wp_register_script('scalia-blog', get_template_directory_uri() . '/js/blog.js', array('jquery'), '', true);
}
add_action('wp_enqueue_scripts', 'scalia_scripts');

function scalia_admin_scripts_init() {
	$jQuery_ui_theme = 'ui-no-theme';
	wp_enqueue_script('jquery');
	wp_enqueue_style('scalia-jquery-ui-theme', get_template_directory_uri() . '/css/jquery-ui/' . $jQuery_ui_theme . '/jquery-ui.css');
	wp_enqueue_script('thickbox');
	wp_enqueue_style('thickbox');
	wp_enqueue_script('media-upload');
	wp_enqueue_style('scalia-admin-styles', get_template_directory_uri() . '/css/admin.css');
	wp_enqueue_script('scalia-color-picker-script', get_template_directory_uri() . '/js/colorpicker/js/colorpicker.js');
	wp_enqueue_style('scalia-color-picker-styles', get_template_directory_uri() . '/js/colorpicker/css/colorpicker.css');
	wp_enqueue_script('scalia-admin-functions', get_template_directory_uri() . '/js/admin_functions.js');
	wp_enqueue_script('scalia_page_settings-script', get_template_directory_uri() . '/js/page_meta_box_settings.js');
	wp_register_script('scalia_js_composer_js_custom_views', get_template_directory_uri() . '/js/scalia-composer-custom-views.js', array( 'wpb_js_composer_js_view' ), '', true ); 
}
add_action('admin_enqueue_scripts', 'scalia_admin_scripts_init');


/* OPEN GRAPH TAGS START */

function scalia_open_graph() {
	global $post;

	$og_description_length = 300;

	$output = "\n";

	if (is_singular(array('post', 'scalia_pf_item'))) {
		// title
		$og_title = esc_attr(strip_tags(stripslashes($post->post_title)));

		// description
		$og_description = trim($post->post_excerpt) != '' ? trim($post->post_excerpt) : trim($post->post_content);
		$og_description = esc_attr( strip_tags( strip_shortcodes( stripslashes( $og_description ) ) ) );
		if ($og_description_length)
			$og_description = substr( $og_description, 0, $og_description_length );
		if ($og_description == '')
			$og_description = $og_title;


		// site name
		$og_site_name = get_bloginfo('name');

		// type
		$og_type = 'article';

		// url
		$og_url = get_permalink();

		// image
		$og_image = '';
		$attachment_id = get_post_thumbnail_id($post->ID);
		if ($attachment_id) {
			$og_image = wp_get_attachment_url($attachment_id);
		}

		
		// Open Graph output
		$output .= '<meta property="og:title" content="'.trim(esc_attr($og_title)).'"/>'."\n";

		$output .= '<meta property="og:description" content="'.trim(esc_attr($og_description)).'"/>'."\n";

		$output .= '<meta property="og:site_name" content="'.trim(esc_attr($og_site_name)).'"/>'."\n";

		$output .= '<meta property="og:type" content="'.trim(esc_attr($og_type)).'"/>'."\n";

		$output .= '<meta property="og:url" content="'.trim(esc_attr($og_url)).'"/>'."\n";

		if (trim($og_image) != '')
			$output .= '<meta property="og:image" content="'.trim(esc_attr($og_image)).'"/>'."\n";

		// Google Plus output
		$output .= "\n";
		$output .= '<meta itemprop="name" content="'.trim(esc_attr($og_title)).'"/>'."\n";

		$output .= '<meta itemprop="description" content="'.trim(esc_attr($og_description)).'"/>'."\n";

		if (trim($og_image) != '')
			$output .= '<meta itemprop="image" content="'.trim(esc_attr($og_image)).'"/>'."\n";
	}

	echo $output;
}

add_action('wp_head', 'scalia_open_graph', 9999);

function scalia_open_graph_namespace($output) {
	if (!stristr($output,'xmlns:og')) {
		$output = $output . ' xmlns:og="http://ogp.me/ns#"';
	}
	if (!stristr($output,'xmlns:fb')) {
		$output=$output . ' xmlns:fb="http://ogp.me/ns/fb#"';
	}
	return $output;
}

add_filter('language_attributes', 'scalia_open_graph_namespace',9999);

/* OPEN GRAPH TAGS FINISH */

/* FONTS */

function scalia_additionals_fonts() {
	$scalia_fonts = apply_filters('scalia_additional_fonts', array());
	$user_fonts = get_option('scalia_additionals_fonts');
	if(is_array($user_fonts)) {
		return array_merge($user_fonts, $scalia_fonts);
	}
	return $scalia_fonts;
}

add_action('init', 'scalia_google_fonts_load_file');
function scalia_google_fonts_load_file() {
	global $scalia_fontsFamilyArray, $scalia_fontsFamilyArrayFull;
	$scalia_fontsFamilyArray = array();
	$scalia_fontsFamilyArrayFull = array();
	$additionals_fonts = scalia_additionals_fonts();
	foreach($additionals_fonts as $additionals_font) {
		$scalia_fontsFamilyArray[$additionals_font['font_name']] = $additionals_font['font_name'];
		$scalia_fontsFamilyArrayFull[$additionals_font['font_name']] = array('family' => $additionals_font['font_name'], 'variants' => array('regular'), 'subsets' => array());
	}
	$scalia_fontsFamilyArray = array_merge($scalia_fontsFamilyArray, array(
		'Arial' => 'Arial',
		'Courier' => 'Courier',
		'Courier New' => 'Courier New',
		'Georgia' => 'Georgia',
		'Helvetica' => 'Helvetica',
		'Palatino' => 'Palatino',
		'Times New Roman' => 'Times New Roman',
		'Trebuchet MS' => 'Trebuchet MS',
		'Verdana' => 'Verdana'
	));
	$scalia_fontsFamilyArrayFull = array_merge($scalia_fontsFamilyArrayFull, array(
		'Arial' => array('family' => 'Arial', 'variants' => array('regular', 'italic', '700', '700italic'), 'subsets' => array()),
		'Courier' => array('family' => 'Courier', 'variants' => array('regular', 'italic', '700', '700italic'), 'subsets' => array()),
		'Courier New' => array('family' => 'Courier New', 'variants' => array('regular', 'italic', '700', '700italic'), 'subsets' => array()),
		'Georgia' => array('family' => 'Georgia', 'variants' => array('regular', 'italic', '700', '700italic'), 'subsets' => array()),
		'Helvetica' => array('family' => 'Helvetica', 'variants' => array('regular', 'italic', '700', '700italic'), 'subsets' => array()),
		'Palatino' => array('family' => 'Palatino', 'variants' => array('regular', 'italic', '700', '700italic'), 'subsets' => array()),
		'Times New Roman' => array('family' => 'Times New Roman', 'variants' => array('regular', 'italic', '700', '700italic'), 'subsets' => array()),
		'Trebuchet MS' => array('family' => 'Trebuchet MS', 'variants' => array('regular', 'italic', '700', '700italic'), 'subsets' => array()),
		'Verdana' => array('family' => 'Verdana', 'variants' => array('regular', 'italic', '700', '700italic'), 'subsets' => array()),
	));
	$font_file = scalia_get_option('google_fonts_file');
	if(!file_exists($font_file)) {
		$font_file = get_template_directory() . '/fonts/webfonts.json';
	}
	$fontsList = json_decode(file_get_contents($font_file));
	if(is_object($fontsList) && isset($fontsList->kind) && $fontsList->kind == 'webfonts#webfontList' && isset($fontsList->items) && is_array($fontsList->items)) {
		foreach($fontsList->items as $item) {
			if(is_object($item) && isset($item->kind) && $item->kind == 'webfonts#webfont' && isset($item->family) && is_string($item->family)) {
				$scalia_fontsFamilyArray[$item->family] = $item->family;
				$scalia_fontsFamilyArrayFull[$item->family] = array(
					'family' => $item->family,
					'variants' => $item->variants,
					'subsets' => $item->subsets,
				);
			}
		}
	}
}

function scalia_fonts_list($full = false) {
	global $scalia_fontsFamilyArray, $scalia_fontsFamilyArrayFull;
	if($full) {
		return $scalia_fontsFamilyArrayFull;
	} else {
		return $scalia_fontsFamilyArray;
	}
}

add_action('wp_enqueue_scripts', 'scalia_load_fonts');
function scalia_load_fonts() {
	$options = scalia_get_theme_options();
	$fontsList = scalia_fonts_list(true);
	$fontElements = array_keys($options['fonts']['subcats']);
	unset($fontElements[0]);
	$exclude_array = array('Arial', 'Courier', 'Courier New', 'Georgia', 'Helvetica', 'Palatino', 'Times New Roman', 'Trebuchet MS', 'Verdana');
	$additionals_fonts = scalia_additionals_fonts();
	foreach($additionals_fonts as $additionals_font) {
		$exclude_array[] = $additionals_font['font_name'];
	}
	$fonts = array();
	$variants = array();
	$subsets = array();
	foreach($fontElements as $element) {
		if(($font = scalia_get_option($element.'_family')) && !in_array($font, $exclude_array) && isset($fontsList[$font])) {
			$font = $fontsList[$font];
			if(scalia_get_option($element.'_sets')) {
				$font['subsets'] = scalia_get_option($element.'_sets');
			} else {
				$font['subsets'] = implode(',',$font['subsets']);
			}
			if(scalia_get_option($element.'_style')) {
				$font['variants'] = scalia_get_option($element.'_style');
			} else {
				$font['variants'] = 'regular';
			}

			if(!in_array($font['family'], $fonts))
				$fonts[] = $font['family'];

			if(!isset($variants[$font['family']]))
				$variants[$font['family']] = array();

			$tmp = explode(',', $font['variants']);
			foreach ($tmp as $v) {
				if(!in_array($v, $variants[$font['family']]))
					$variants[$font['family']][] = $v;
			}

			$tmp = explode(',', $font['subsets']);
			foreach ($tmp as $v) {
				if(!in_array($v, $subsets))
					$subsets[] = $v;
			}
		}
	}
	if(count($fonts) > 0) {
		$inc_fonts = '';
		foreach ($fonts as $k=>$v) {
			if($k > 0)
				$inc_fonts .= '|';
			$inc_fonts .= $v;
			if(!empty($variants[$v]))
				$inc_fonts .= ':'.implode(',', $variants[$v]);
		}
		wp_enqueue_style('load-google-fonts','http://fonts.googleapis.com/css?family='.$inc_fonts.'&subset='.implode(',', $subsets));
	}
}

function scalia_custom_fonts() {
	$options = scalia_get_theme_options();
	$fontElements = array_keys($options['fonts']['subcats']);
	unset($fontElements[0]);
	$additionals_fonts = scalia_additionals_fonts();
	$fonts_array = array();
	foreach($additionals_fonts as $additionals_font) {
		$fonts_array[] = $additionals_font['font_name'];
		$fonts_arrayFull[$additionals_font['font_name']] = $additionals_font;
	}
	$exclude_array = array();
	foreach($fontElements as $element) {
		if(($font = scalia_get_option($element.'_family')) && in_array($font, $fonts_array) && !in_array($font, $exclude_array)) {
			$exclude_array[] = $font;
?>

@font-face {
	font-family: '<?php echo sanitize_text_field($fonts_arrayFull[$font]['font_name']); ?>';
	src: url('<?php echo esc_url($fonts_arrayFull[$font]['font_url_eot']); ?>');
	src: url('<?php echo esc_url($fonts_arrayFull[$font]['font_url_eot']); ?>?#iefix') format('embedded-opentype'),
		url('<?php echo esc_url($fonts_arrayFull[$font]['font_url_woff']); ?>') format('woff'),
		url('<?php echo esc_url($fonts_arrayFull[$font]['font_url_ttf']); ?>') format('truetype'),
		url('<?php echo esc_url($fonts_arrayFull[$font]['font_url_svg'].'#'.$fonts_arrayFull[$font]['font_svg_id']); ?>') format('svg');
		font-weight: normal;
		font-style: normal;
}

<?php
		}
	}
}

add_action('wp_ajax_scalia_get_font_data', 'scalia_get_font_data');
function scalia_get_font_data() {
	if(is_array($_REQUEST['fonts'])) {
		$result = array();
		$fontsList = scalia_fonts_list(true);
		foreach ($_REQUEST['fonts'] as $font)
			if(isset($fontsList[$font]))
				$result[$font] = $fontsList[$font];
		echo json_encode($result);
		exit;
	}
	die(-1);
}

/* META BOXES */

function scalia_print_select_input($values = array(), $value = '', $name = '', $id = '') {
	if(!is_array($values)) {
		$values = array();
	}
?>
	<select name="<?php echo esc_attr($name) ?>" id="<?php echo esc_attr($id); ?>" class="scalia-combobox">
		<?php foreach($values as $key => $title) : ?>
			<option value="<?php echo esc_attr($key); ?>" <?php selected($key, $value); ?>><?php echo esc_html($title); ?></option>
		<?php endforeach; ?>
	</select>
<?php
}


function scalia_print_checkboxes($values = array(), $value = array(), $name = '', $id_prefix = '', $after = '') {
	if(!is_array($values)) {
		$values = array();
	}
	if(!is_array($value)) {
		$value = array();
	}
?>
	<?php foreach($values as $key => $title) : ?>
		<input name="<?php echo esc_attr($name); ?>" type="checkbox" id="<?php echo esc_attr($id_prefix.'-'.$key); ?>" value="<?php echo esc_attr($key); ?>" <?php checked(in_array($key, $value), 1); ?> />
		<label for="<?php echo esc_attr($id_prefix.'-'.$key); ?>"><?php echo esc_html($title); ?></label>
		<?php echo $after; ?>
	<?php endforeach; ?>
<?php
}

/* PLUGINS */

if(!function_exists('scalia_is_plugin_active')) {
	function scalia_is_plugin_active($plugin) {
		include_once(ABSPATH . 'wp-admin/includes/plugin.php');
		return is_plugin_active($plugin);
	}
}

/* DROPDOWN MENU */

class scalia_walker_primary_nav_menu extends Walker_Nav_Menu {
	function start_lvl(&$output, $depth = 0, $args = array()) {
		$indent = str_repeat("\t", $depth);
		$output .= "\n$indent<ul class=\"sub-menu dl-submenu styled\">\n";
	}
}

class scalia_walker_footer_nav_menu extends Walker_Nav_Menu {
	function start_lvl(&$output, $depth = 0, $args = array()) {
		$indent = str_repeat("\t", $depth);
		$output .= "\n$indent<ul class=\"sub-menu styled\">\n";
	}
}

function scalia_add_menu_item_classes($classes, $item) {
	if($item->current_item_ancestor || $item->current_item_parent) {
		$classes[] = 'menu-item-current';
	}
	if($item->current) {
		$classes[] = 'menu-item-active';
	}
	return $classes;
}
add_filter('nav_menu_css_class', 'scalia_add_menu_item_classes', 10, 2);

function scalia_add_menu_parent_class($items) {
	$parents = array();
	foreach($items as $item) {
		if($item->menu_item_parent && $item->menu_item_parent > 0) {
			$parents[] = $item->menu_item_parent;
		}
	}
	foreach($items as $item) {
		if(in_array($item->ID, $parents)) {
			$item->classes[] = 'menu-item-parent';
		}
	}
	return $items;
}
add_filter('wp_nav_menu_objects', 'scalia_add_menu_parent_class');

function scalia_get_data($data = array(), $param = '', $default = '', $prefix = '', $suffix = '') {
	if(is_array($data) && !empty($data[$param])) {
		return $prefix.$data[$param].$suffix;
	}
	if(!empty($default)) {
		return $prefix.$default.$suffix;
	}
	return $default;
}

function scalia_check_array_value($array = array(), $value = '', $default = '') {
	if(in_array($value, $array)) {
		return $value;
	}
	return $default;
}

/* PAGE TITLE */

function scalia_title($sep = '&raquo;', $display = true, $seplocation = '') {
	global $wpdb, $wp_locale;

	$m = get_query_var('m');
	$year = get_query_var('year');
	$monthnum = get_query_var('monthnum');
	$day = get_query_var('day');
	$search = get_query_var('s');
	$title = '';

	$t_sep = '%WP_TITILE_SEP%'; // Temporary separator, for accurate flipping, if necessary

	// If there is a post
	if(is_single() || is_page()) {
		$title = single_post_title('', false);
	}

	// If there's a post type archive
	if(is_post_type_archive()) {
		$post_type = get_query_var('post_type');
		if(is_array($post_type))
			$post_type = reset($post_type);
		$post_type_object = get_post_type_object($post_type);
		if(! $post_type_object->has_archive)
			$title = post_type_archive_title('', false);
	}

	// If there's a category or tag
	if(is_category() || is_tag()) {
		$title = single_term_title('', false);
	}

	// If there's a taxonomy
	if(is_tax()) {
		$term = get_queried_object();
		if($term) {
			$tax = get_taxonomy($term->taxonomy);
			$title = single_term_title('', false);
		}
	}

	// If there's an author
	if(is_author()) {
		$author = get_queried_object();
		if($author)
			$title = $author->display_name;
	}

	// Post type archives with has_archive should override terms.
	if(is_post_type_archive() && $post_type_object->has_archive)
		$title = post_type_archive_title('', false);

	// If there's a month
	if(is_archive() && !empty($m)) {
		$my_year = substr($m, 0, 4);
		$my_month = $wp_locale->get_month(substr($m, 4, 2));
		$my_day = intval(substr($m, 6, 2));
		$title = $my_year . ($my_month ? $t_sep . $my_month : '') . ($my_day ? $t_sep . $my_day : '');
	}

	// If there's a year
	if(is_archive() && !empty($year)) {
		$title = $year;
		if(!empty($monthnum))
			$title .= $t_sep . $wp_locale->get_month($monthnum);
		if(!empty($day))
			$title .= $t_sep . zeroise($day, 2);
	}

	// If it's a search
	if(is_search()) {
		/* translators: 1: separator, 2: search phrase */
		$title = sprintf(__('Search Results%1$s "%2$s"'), $t_sep, strip_tags($search));
	}

	// If it's a 404 page
	if(is_404()) {
		$title = __('Page not found', 'scalia');
	}

	$prefix = '';
	if(!empty($title))
		$prefix = " $sep ";

 	// Determines position of the separator and direction of the breadcrumb
	if('right' == $seplocation) { // sep on right, so reverse the order
		$title_array = explode($t_sep, $title);
		$title_array = array_reverse($title_array);
		$title = implode(" $sep ", $title_array) . $prefix;
	} else {
		$title_array = explode($t_sep, $title);
		$title = $prefix . implode(" $sep ", $title_array);
	}

	/**
	 * Filter the text of the page title.
	 *
	 * @since 2.0.0
	 *
	 * @param string $title       Page title.
	 * @param string $sep         Title separator.
	 * @param string $seplocation Location of the separator (left or right).
	 */
	$title = apply_filters('scalia_title', $title, $sep, $seplocation);

	// Send it out
	if($display)
		echo $title;
	else
		return $title;
}

function scalia_page_title() {
	$output = '';
	$title_class = '';
	$css_style = '';
	$css_style_title = '';
	$css_style_excerpt = '';
	$video_bg = '';
	$title_style = 1;
	$excerpt = '';
	if(is_singular() || is_post_type_archive('product') || is_tax('product_cat') || is_tax('product_tag')) {
		$post_id = 0;
		if(is_post_type_archive('product') || is_tax('product_cat') || is_tax('product_tag')) {
			$post_id = wc_get_page_id('shop');
		} else {
			global $post;
			$post_id = $post->ID;
		}
		$page_data = scalia_get_sanitize_page_title_data($post_id);
		$title_style = $page_data['title_style'];
		if($page_data['title_background_image']) {
			$css_style .= 'background-image: url('.$page_data['title_background_image'].');';
			$title_class = 'has-background-image';
		}
		if($page_data['title_background_color']) {
			$css_style .= 'background-color: '.$page_data['title_background_color'].';';
		}
		$video_bg = scalia_video_background($page_data['title_video_type'], $page_data['title_video_background'], $page_data['title_video_aspect_ratio'], $page_data['title_menu_on_video'], $page_data['title_video_overlay_color'], $page_data['title_video_overlay_opacity']);
		if($page_data['title_text_color']) {
			$css_style_title = 'color: '.$page_data['title_text_color'].';';
		}
		if($page_data['title_excerpt_text_color']) {
			$css_style_excerpt = 'color: '.$page_data['title_excerpt_text_color'].';';
		}
		if($page_data['title_icon']) {
			$icon_data = array();
			foreach($page_data as $key => $val) {
				if(strpos($key, 'title_icon') === 0) {
					$icon_data[str_replace('title_icon', 'icon', $key)] = $val;
				}
			}
			if(function_exists('scalia_build_icon_shortcode')) {
				$output .= '<div class="page-title-icon">'.do_shortcode(scalia_build_icon_shortcode($icon_data)).'</div>';
			}
		}
		$excerpt = $page_data['title_excerpt'];
	}

	if(is_tax() || is_category() || is_tag()) {
		$term = get_queried_object();
		$excerpt = $term->description;
	}

	$output .= '<div class="page-title-title"><'.($title_style == '2' ? 'h2' : 'h1').' style="'.$css_style_title.'">'.scalia_title('', false).'</'.($title_style == '2' ? 'h2' : 'h1').'></div>';
	if($excerpt) {
		$output .= '<div class="page-title-excerpt" style="'.$css_style_excerpt.'">'.$excerpt.'</div>';
	}
	if($title_style) {
		return '<div id="page-title" class="page-title-block page-title-style-'.$title_style.' '.$title_class.'" style="'.$css_style.'">'.$video_bg.'<div class="container">'.$output.'</div></div>';
	}
	return false;
}

function scalia_post_type_archive_title($label, $post_type) {
	if($post_type == 'product') {
		$shop_page_id = wc_get_page_id('shop');
		$page_title = get_the_title($shop_page_id);
		return $page_title;
	}
	return $label;
}
add_filter('post_type_archive_title', 'scalia_post_type_archive_title', 10, 2);

add_filter('woocommerce_show_page_title', '__return_false');

/* EXCERPT */

function scalia_excerpt_length($length) {
	return scalia_get_option('excerpt_length') ? intval(scalia_get_option('excerpt_length')) : 20;
}
add_filter('excerpt_length', 'scalia_excerpt_length');

function scalia_excerpt_more($more) {
	return '...';
}
add_filter('excerpt_more', 'scalia_excerpt_more');

/* EDITOR */

add_action('admin_init', 'scalia_admin_init');
function scalia_admin_init() {
	add_filter('tiny_mce_before_init', 'scalia_init_editor');
	add_filter('mce_buttons_2', 'scalia_mce_buttons_2');
}

function scalia_mce_buttons_2($buttons) {
	array_unshift($buttons, 'styleselect');
	return $buttons;
}

function scalia_init_editor($settings) {
	$style_formats = array(
		array(
			'title' => 'Styled Subtitle',
			'block' => 'div',
			'classes' => 'styled-subtitle'
		),
		array(
			'title' => 'Title H1',
			'block' => 'div',
			'classes' => 'title-h1'
		),
		array(
			'title' => 'Title H2',
			'block' => 'div',
			'classes' => 'title-h2'
		),
		array(
			'title' => 'Title H3',
			'block' => 'div',
			'classes' => 'title-h3'
		),
		array(
			'title' => 'Title H4',
			'block' => 'div',
			'classes' => 'title-h4'
		),
		array(
			'title' => 'Title H5',
			'block' => 'div',
			'classes' => 'title-h5'
		),
		array(
			'title' => 'Title H6',
			'block' => 'div',
			'classes' => 'title-h6'
		),
	);
	$settings['wordpress_adv_hidden'] = false;
	$settings['style_formats'] = json_encode($style_formats);
	return $settings;
}

/* SOCIALS */

function scalia_print_socials() {
	$socials_icons = array('twitter' => scalia_get_option('twitter_active'), 'facebook' => scalia_get_option('facebook_active'), 'linkedin' => scalia_get_option('linkedin_active'), 'googleplus' => scalia_get_option('googleplus_active'), 'stumbleupon' => scalia_get_option('stumbleupon_active'), 'rss' => scalia_get_option('rss_active'));
	if(in_array(1, $socials_icons)) {
?>
	<div class="socials">
		<?php foreach($socials_icons as $name => $active) : ?>
			<?php if($active) : ?>
				<div class="socials-item <?php echo esc_attr($name); ?>"><a href="<?php echo esc_url(scalia_get_option($name . '_link')); ?>" target="_blank" title="<?php echo esc_attr($name); ?>"><?php echo esc_html($name); ?></a></div>
			<?php endif; ?>
		<?php endforeach; ?>
		<?php do_action('scalia_print_socials'); ?>
	</div>
<?php
	}
}

/* PAGINATION */

function scalia_pagination($query = false) {
	if(!$query) {
		$query = $GLOBALS['wp_query'];
	}
	if($query->max_num_pages < 2) {
		return;
	}

	$paged        = get_query_var('paged') ? intval(get_query_var('paged')) : 1;
	$pagenum_link = html_entity_decode(get_pagenum_link());
	$query_args   = array();
	$url_parts    = explode('?', $pagenum_link);

	if(isset($url_parts[1])) {
		wp_parse_str($url_parts[1], $query_args);
	}

	$pagenum_link = remove_query_arg(array_keys($query_args), $pagenum_link);
	$pagenum_link = trailingslashit($pagenum_link) . '%_%';

	$format  = $GLOBALS['wp_rewrite']->using_index_permalinks() && ! strpos($pagenum_link, 'index.php') ? 'index.php/' : '';
	$format .= $GLOBALS['wp_rewrite']->using_permalinks() ? user_trailingslashit('page/%#%', 'paged') : '?paged=%#%';

	// Set up paginated links.
	$links = paginate_links(array(
		'base'     => $pagenum_link,
		'format'   => $format,
		'total'    => $query->max_num_pages,
		'current'  => $paged,
		'mid_size' => 1,
		'add_args' => array_map('urlencode', $query_args),
		'prev_text' => __('Prev', 'scalia'),
		'next_text' => __('Next', 'scalia'),
	));

	if($links) :

	?>
	<div class="sc-pagination">
		<?php echo $links; ?>
	</div><!-- .pagination -->
	<?php
	endif;
}

if(!function_exists('hex_to_rgb')) {
	function hex_to_rgb($color) {
		if(strpos($color, '#') === 0) {
			$color = substr($color, 1);
			if(strlen($color) == 3) {
				return array(hexdec($color[0]), hexdec($color[1]), hexdec($color[2]));
			} elseif(strlen($color) == 6) {
				return array(hexdec(substr($color, 0, 2)), hexdec(substr($color, 2, 2)), hexdec(substr($color, 4, 2)));
			}
		}
		return $color;
	}
}

function sclia_admin_bar_site_menu($wp_admin_bar) {
	if(! is_user_logged_in())
		return;
	if(! is_user_member_of_blog() && ! is_super_admin())
		return;

	$wp_admin_bar->add_menu(array(
		'id'    => 'scalia-theme-options',
		'title' => 'Scalia Theme Options',
		'href'  => admin_url('themes.php?page=options-framework'),
	));
}
add_action('admin_bar_menu', 'sclia_admin_bar_site_menu', 100);

if(!function_exists('scalia_user_icons_info_link')) {
function scalia_user_icons_info_link() {
	return esc_url(apply_filters('scalia_user_icons_info_link', get_template_directory_uri().'/fonts/user-icons-list.html'));
}
}

/* THUMBNAILS */

function scalia_post_thumbnail($size = 'scalia-post-thumb', $dummy = true, $class='img-responsive img-circle') {
	if(has_post_thumbnail()) {
		the_post_thumbnail($size, array('class' => $class));
	} elseif($dummy) {
		echo '<span class="sc-dummy '.$class.'"></span>';
	}
}

function scalia_attachment_url($attachcment, $size = 'full') {
	if((int)$attachcment > 0 && ($image_url = wp_get_attachment_url($attachcment, $size)) !== false) {
		return $image_url;
	}
	return false;
}

function scalia_generate_thumbnail_src($attachment_id, $size) {
	if(in_array($size, array_keys(scalia_image_sizes()))) {
		$filepath = get_attached_file($attachment_id);
		$thumbFilepath = $filepath;
		$image = wp_get_image_editor($filepath);
		if(!is_wp_error($image) && $image) {
			$thumbFilepath = $image->generate_filename($size);
			if(!file_exists($thumbFilepath)) {
				$scalia_image_sizes = scalia_image_sizes();
				if(!is_wp_error($image) && isset($scalia_image_sizes[$size])) {
					$image->resize($scalia_image_sizes[$size][0], $scalia_image_sizes[$size][1], $scalia_image_sizes[$size][2]);
					$image = $image->save($image->generate_filename($size));
				} else {
					$thumbFilepath = $filepath;
				}
			}
		}
		$image = wp_get_image_editor($thumbFilepath);
		if(!is_wp_error($image) && $image) {
			$upload_dir = wp_upload_dir();
			$sizes = $image->get_size();
			return array($upload_dir['baseurl'].'/'._wp_relative_upload_path($thumbFilepath), $sizes['width'], $sizes['height']);
		}
	}
	return wp_get_attachment_url($attachment_id, $size);
}

function scalia_get_thumbnail_image($attachment_id, $size, $icon = false, $attr = '') {
	$html = '';
	$image = scalia_generate_thumbnail_src($attachment_id, $size, $icon);
	if($image) {
		list($src, $width, $height) = $image;
		$hwstring = image_hwstring($width, $height);
		if(is_array($size))
			$size = join('x', $size);
		$attachment = get_post($attachment_id);
		$default_attr = array(
			'src' => $src,
			'class' => "attachment-$size",
			'alt' => trim(strip_tags(get_post_meta($attachment_id, '_wp_attachment_image_alt', true))),
		);
		if(empty($default_attr['alt']))
			$default_attr['alt'] = trim(strip_tags($attachment->post_excerpt));
		if(empty($default_attr['alt']))
			$default_attr['alt'] = trim(strip_tags($attachment->post_title));

		$attr = wp_parse_args($attr, $default_attr);
		$attr = apply_filters('wp_get_attachment_image_attributes', $attr, $attachment);
		$attr = array_map('esc_attr', $attr);
		$html = rtrim("<img $hwstring");
		foreach ($attr as $name => $value) {
			$html .= " $name=" . '"' . $value . '"';
		}
		$html .= ' />';
	}

	return $html;
}

function scalia_get_the_post_thumbnail($html, $post_id, $post_thumbnail_id, $size, $attr) {
	if(in_array($size, array_keys(scalia_image_sizes()))) {
		if($post_thumbnail_id) {
			do_action('begin_fetch_post_thumbnail_html', $post_id, $post_thumbnail_id, $size);
			if(in_the_loop())
				update_post_thumbnail_cache();
			$html = scalia_get_thumbnail_image($post_thumbnail_id, $size, false, $attr);
			do_action('end_fetch_post_thumbnail_html', $post_id, $post_thumbnail_id, $size);
		} else {
			$html = '';
		}
	}
	return $html;
}
add_filter('post_thumbnail_html', 'scalia_get_the_post_thumbnail', 10, 5);

function scalia_image_sizes() {
	return apply_filters('scalia_image_sizes', array(
		'scalia-portfolio-justified' => array(754, 500, true),
		'scalia-portfolio-1x' => array(845, 559, true),

		'scalia-blog-default' => array(540, 390, true),

		'scalia-portfolio-double-2x' => array(1287, 1049, true),
		'scalia-portfolio-double-2x-no-gaps' => array(1287, 1049, true),
		'scalia-portfolio-double-2x-hover' => array(1287, 861, true),
		'scalia-portfolio-double-2x-hover-no-gaps' => array(1287, 861, true),

		'scalia-portfolio-double-3x' => array(847, 726, true),
		'scalia-portfolio-double-3x-no-gaps' => array(880, 733, true),
		'scalia-portfolio-double-3x-hover' => array(847, 570, true),
		'scalia-portfolio-double-3x-hover-no-gaps' => array(880, 584, true),

		'scalia-portfolio-double-4x' => array(627, 580, true),
		'scalia-portfolio-double-4x-no-gaps' => array(660, 587, true),
		'scalia-portfolio-double-4x-hover' => array(627, 424, true),
		'scalia-portfolio-double-4x-hover-no-gaps' => array(660, 438, true),

		'scalia-portfolio-double-100%' => array(1002, 685, true),
		'scalia-portfolio-double-100%-no-gaps' => array(1019, 689, true),
		'scalia-portfolio-double-100%-hover' => array(1002, 536, true),
		'scalia-portfolio-double-100%-hover-no-gaps' => array(1019, 540, true),

		'scalia-portfolio-masonry' => array(754, 0, false),
		'scalia-portfolio-masonry-double' => array(1508, 0, false),
		'scalia-gallery-justified' => array(440, 400, true),
		'scalia-gallery-justified-double' => array(660, 600, true),
		'scalia-gallery-justified-double-4x' => array(880, 800, true),
		'scalia-gallery-masonry' => array(440, 0, false),
		'scalia-gallery-masonry-double' => array(880, 0, false),
		'scalia-gallery-metro' => array(0, 400, false),
		'scalia-gallery-fullwidth' => array(1170, 500, true),
		'scalia-gallery-sidebar' => array(870, 577, true),

		'scalia-person' => array(400, 400, true),
	));
}

/* FOOTER */

function scalia_theme_footer() {
?>
<?php if(scalia_get_option('custom_js')) : ?>
	<script type='text/javascript'>
		<?php echo stripslashes(scalia_get_option('custom_js')); ?>
	</script>
<?php endif; ?>
<?php
}
add_action('wp_footer','scalia_theme_footer', 15);

/* FONTS MANAGER */

function scalia_fonts_allowed_mime_types( $existing_mimes = array() ) {
	$existing_mimes['ttf'] = 'font/ttf';
	$existing_mimes['eot'] = 'font/eot';
	$existing_mimes['woff'] = 'font/woff';
	$existing_mimes['svg'] = 'font/svg';
	$existing_mimes['json'] = 'application/json';
	return $existing_mimes;
}
add_filter('upload_mimes', 'scalia_fonts_allowed_mime_types');

function scalia_modify_post_mime_types( $post_mime_types ) {
	$post_mime_types['font/ttf'] = array(__('TTF Font', 'scalia'), __( 'Manage TTFs', 'scalia' ), _n_noop( 'TTF <span class="count">(%s)</span>', 'TTFs <span class="count">(%s)</span>' ) );
	$post_mime_types['font/eot'] = array(__('EOT Font', 'scalia'), __( 'Manage EOTs', 'scalia' ), _n_noop( 'EOT <span class="count">(%s)</span>', 'EOTs <span class="count">(%s)</span>' ) );
	$post_mime_types['font/woff'] = array(__('WOFF Font', 'scalia'), __( 'Manage WOFFs', 'scalia' ), _n_noop( 'WOFF <span class="count">(%s)</span>', 'WOFFs <span class="count">(%s)</span>' ) );
	$post_mime_types['font/svg'] = array(__('SVG Font', 'scalia'), __( 'Manage SVGs', 'scalia' ), _n_noop( 'SVG <span class="count">(%s)</span>', 'SVGs <span class="count">(%s)</span>' ) );
	return $post_mime_types;
}
add_filter('post_mime_types', 'scalia_modify_post_mime_types');

/* Create fonts manager page */
add_action( 'admin_menu', 'scalia_fonts_manager_add_page');
function scalia_fonts_manager_add_page() {
	$page = add_theme_page(__('Fonts Manager','scalia'), __('Fonts Manager','scalia'), 'edit_theme_options', 'fonts-manager', 'scalia_fonts_manager_page');
	add_action('load-' . $page, 'scalia_fonts_manager_page_prepend');
}

/* Admin theme page scripts & css*/
function scalia_fonts_manager_page_prepend() {
	wp_enqueue_media();
	wp_enqueue_script('scalia-file-selector', get_template_directory_uri() . '/js/file-selector.js');
	wp_enqueue_script('scalia-font-manager', get_template_directory_uri() . '/js/font-manager.js');
}

/* Build admin theme page form */
function scalia_fonts_manager_page(){
	$additionals_fonts = get_option('scalia_additionals_fonts');
?>
<div class="wrap">

	<h2><?php _e('Font Manager', 'scalia'); ?></h2>

	<form id="fonts-manager-form" method="POST" enctype="multipart/form-data">
		<div class="font-pane empty" style="display: none;">
			<div class="remove"><a href="javascript:void(0);"><?php _e('Remove', 'scalia'); ?></a></div>
			<div class="field">
				<div class="label"><label for=""><?php _e('Font name', 'scalia'); ?></label></div>
				<div class="input"><input type="text" name="fonts[font_name][]" value="" /></div>
			</div>
			<div class="field">
				<div class="label"><label for=""><?php _e('Font file EOT url', 'scalia'); ?></label></div>
				<div class="file_url"><input type="text" name="fonts[font_url_eot][]" value="" data-type="font/eot" /></div>
			</div>
			<div class="field">
				<div class="label"><label for=""><?php _e('Font file SVG url', 'scalia'); ?></label></div>
				<div class="file_url"><input type="text" name="fonts[font_url_svg][]" value="" data-type="font/svg" /></div>
			</div>
			<div class="field">
				<div class="label"><label for=""><?php _e('ID inside SVG', 'scalia'); ?></label></div>
				<div class="input"><input type="text" name="fonts[font_svg_id][]" value="" /></div>
			</div>
			<div class="field">
				<div class="label"><label for=""><?php _e('Font file TTF url', 'scalia'); ?></label></div>
				<div class="file_url"><input type="text" name="fonts[font_url_ttf][]" value="" data-type="font/ttf" /></div>
			</div>
			<div class="field">
				<div class="label"><label for=""><?php _e('Font file WOFF url', 'scalia'); ?></label></div>
				<div class="file_url"><input type="text" name="fonts[font_url_woff][]" value="" data-type="font/woff" /></div>
			</div>
		</div>
		<?php if(is_array($additionals_fonts)) : ?>
			<?php foreach($additionals_fonts as $additionals_font) : ?>
				<div class="font-pane">
					<div class="remove"><a href="javascript:void(0);"><?php _e('Remove', 'scalia'); ?></a></div>
					<div class="field">
						<div class="label"><label for=""><?php _e('Font name', 'scalia'); ?></label></div>
						<div class="input"><input type="text" name="fonts[font_name][]" value="<?php echo esc_attr($additionals_font['font_name']); ?>" /></div>
					</div>
					<div class="field">
						<div class="label"><label for=""><?php _e('Font file EOT url', 'scalia'); ?></label></div>
						<div class="file_url"><input type="text" name="fonts[font_url_eot][]" value="<?php echo esc_attr($additionals_font['font_url_eot']); ?>" data-type="font/eot" /></div>
					</div>
					<div class="field">
						<div class="label"><label for=""><?php _e('Font file SVG url', 'scalia'); ?></label></div>
						<div class="file_url"><input type="text" name="fonts[font_url_svg][]" value="<?php echo esc_attr($additionals_font['font_url_svg']); ?>" data-type="font/svg" /></div>
					</div>
					<div class="field">
						<div class="label"><label for=""><?php _e('ID inside SVG', 'scalia'); ?></label></div>
						<div class="input"><input type="text" name="fonts[font_svg_id][]" value="<?php echo esc_attr($additionals_font['font_svg_id']); ?>" /></div>
					</div>
					<div class="field">
						<div class="label"><label for=""><?php _e('Font file TTF url', 'scalia'); ?></label></div>
						<div class="file_url"><input type="text" name="fonts[font_url_ttf][]" value="<?php echo esc_attr($additionals_font['font_url_ttf']); ?>" data-type="font/ttf" /></div>
					</div>
					<div class="field">
						<div class="label"><label for=""><?php _e('Font file WOFF url', 'scalia'); ?></label></div>
						<div class="file_url"><input type="text" name="fonts[font_url_woff][]" value="<?php echo esc_attr($additionals_font['font_url_woff']); ?>" data-type="font/woff" /></div>
					</div>
				</div>
			<?php endforeach; ?>
		<?php endif; ?>
		<div class="add-new"><a href="javascript:void(0);"><?php _e('+ Add new', 'scalia'); ?></a></div>
		<div class="submit"><button name="action" value="save"><?php _e('Save', 'scalia'); ?></button></div>
	</form>
</div>

<?php
}

/* Update fonts manager */
add_action('admin_menu', 'scalia_fonts_manager_update');
function scalia_fonts_manager_update() {
	if(isset($_GET['page']) && $_GET['page'] == 'fonts-manager') {
		if(isset($_REQUEST['action']) && $_REQUEST['action'] == 'save') {
			if(isset($_REQUEST['fonts']) && is_array($_REQUEST['fonts'])) {
				$fonts = array();
				foreach($_REQUEST['fonts'] as $font_param => $list) {
					foreach($list as $key => $item) {
						$fonts[$key][$font_param] = $_REQUEST['fonts'][$font_param][$key];
					}
				}
				foreach($fonts as $key => $font) {
					if(!$font['font_name']) {
						unset($fonts[$key]);
					}
				}
				update_option('scalia_additionals_fonts', $fonts);
			}
			wp_redirect(admin_url('themes.php?page=fonts-manager'));
		}
	}
}

/* LAYERSLIDER SKIN */

if(scalia_is_plugin_active('LayerSlider/layerslider.php') && class_exists('LS_Sources')) {
	LS_Sources::addSkins(get_template_directory().'/ls_skin/');
}

function scalia_theme_rewrite_flush() {
	flush_rewrite_rules();
}
add_action( 'after_switch_theme', 'scalia_theme_rewrite_flush' );

/* -------------------------------------------------------------------
			PERSONALIZED FUNCTIONS
---------------------------------------------------------------------- */

/**
 * Check to see if the current page has a parent or if the page itsel is a parent.
 * If current is a parent page, then it displays the child pages associated with it.
 * If current is a child page, then it displays all other child pages of its parent page.
 */
function list_child_pages() {
	global $post;
	if (is_page() && $post->post_parent) {
		$childpages = wp_list_pages('sort_column=menu_order&title_li=&child_of=' . $post->post_parent . '&echo=0');
	} else {
		$childpages = wp_list_pages('sort_column=menu_order&title_li=&child_of=' . $post->ID . '&echo=0');
	}
	if ($childpages) {
		$string = '<ul>' . $childpages . '</ul>';
	}
	return $string;
}

add_shortcode('list_childpages', 'list_child_pages');
