<?php
/*
Plugin Name: WP Subscribe
Plugin URI: http://mythemeshop.com/plugins/wp-subscribe/
Description: WP Subscribe is a simple but powerful subscription plugin which supports MailChimp, Aweber and Feedburner.
Author: MyThemeShop
Version: 1.0
Author URI: http://mythemeshop.com/
*/

require_once dirname(__FILE__) . '/Mailchimp.php';

// Add function to widgets_init that'll load our widget.
add_action( 'widgets_init', 'wp_subscribe_register_widget' );

// Register widget.
function wp_subscribe_register_widget() {
	register_widget( 'wp_subscribe' );
}

// Widget class.
class wp_subscribe extends WP_Widget {


/*-----------------------------------------------------------------------------------*/
/*	Widget Setup
/*-----------------------------------------------------------------------------------*/
	
	function wp_subscribe() {
		
		add_action('wp_enqueue_scripts', array(&$this, 'register_scripts'));
        add_action('admin_enqueue_scripts', array(&$this, 'register_admin_scripts'));

		/* Widget settings. */
		$widget_ops = array( 'classname' => 'wp_subscribe', 'description' => __('Displays Subscription Form, Supports - FeedBurner, MailChimp & AWeber.', 'wp-subscribe') );

		/* Widget control settings. */
		$control_ops = array( 'width' => 300, 'height' => 350, 'id_base' => 'wp_subscribe' );

		/* Create the widget. */
		$this->WP_Widget( 'wp_subscribe', __('WP Subscribe Widget', 'wp-subscribe'), $widget_ops, $control_ops );
	}

/*-----------------------------------------------------------------------------------*/
/*	Enqueue assets
/*-----------------------------------------------------------------------------------*/
	function register_scripts() { 
		// JS    
		// wp_register_script('wp-subscribe', plugins_url('js/wp-subscribe.js', __FILE__), array('jquery'));     
		// CSS     
		wp_register_style('wp-subscribe', plugins_url('css/wp-subscribe.css', __FILE__));
    }  
	function register_admin_scripts($hook) {
        if ($hook != 'widgets.php')
            return;
        wp_register_script('wp-subscribe-admin', plugins_url('js/wp-subscribe-admin.js', __FILE__), array('jquery'));  
        wp_enqueue_script('wp-subscribe-admin');
		wp_register_style('wp-subscribe-admin', plugins_url('css/wp-subscribe-admin.css', __FILE__));
		wp_enqueue_style('wp-subscribe-admin');
    }
    

/*-----------------------------------------------------------------------------------*/
/*	Display Widget
/*-----------------------------------------------------------------------------------*/
	
	function widget( $args, $instance ) {
		extract( $args );
        $defaults = $this->get_defaults();
        $instance = wp_parse_args( (array) $instance, $defaults ); 
        wp_enqueue_style( 'wp-subscribe' );

		/* Before widget (defined by themes). */
		echo $before_widget;
	   
		/* Display Widget */
		?>
        	
			<div class="wp-subscribe" id="wp-subscribe">
				<h4 class="title"><?php echo $instance['title'];?></h4>
                <p class="text"><?php echo $instance['text'];?></p>

                <?php if ($instance['service'] == 'feedburner') { ?>
                
                    <form action="http://feedburner.google.com/fb/a/mailverify" method="post" target="popupwindow" onsubmit="window.open('http://feedburner.google.com/fb/a/mailverify?uri=<?php echo $instance['feedburner_id']; ?>', 'popupwindow', 'scrollbars=yes,width=550,height=520');return true" _lpchecked="1">
        				<input class="email-field" type="text" value="" placeholder="<?php echo $instance['email_placeholder']; ?>" name="email">
        				<input type="hidden" value="<?php echo $instance['feedburner_id']; ?>" name="uri"><input type="hidden" name="loc" value="en_US">
                        <input class="submit" name="submit" type="submit" value="<?php echo $instance['button_text']; ?>">
                    </form>
                
                <?php } elseif ($instance['service'] == 'mailchimp') { ?>
                
                    <?php if (empty($_POST['mailchimp_email']) || (!empty($_POST['widget_id']) && $_POST['widget_id'] != $this->id)) { ?>
                    <form action="<?php echo add_query_arg('mailchimp_signup', '1'); ?>" method="post">
                        <input class="email-field" type="text" value="" placeholder="<?php echo $instance['email_placeholder']; ?>" name="mailchimp_email">
                        <input class="submit" name="submit" type="submit" value="<?php echo $instance['button_text']; ?>">
                        <input type="hidden" name="widget_id" value="<?php echo $this->id ?>" />
                        <?php wp_nonce_field('wp-subscribe-mailchimp'); ?>
                    </form>
                    <?php } else {
                        // process signup through API
                        $signup = $this->mailchimp_subscribe();
                        if ($signup['success']) { ?>
                            <p class="thanks"><?php echo $signup['message']; ?></p>
                        <?php } else { ?>
                            <p class="error"><?php echo $signup['message']; ?></p>
                        <?php }
                        }

                    } elseif ($instance['service'] == 'aweber') { ?>
                
                    <?php if (empty($_GET['aweber_signedup'])) { ?>
                        <form method="post" action="http://www.aweber.com/scripts/addlead.pl">
                            <div style="display: none;">
                                <input type="hidden" name="meta_split_id" value="" />
                                <input type="hidden" name="listname" value="" />
                                <input type="hidden" name="redirect" value="<?php echo add_query_arg('aweber_signedup', '1'); ?>" />
                                <input type="hidden" name="meta_redirect_onlist" value="<?php echo add_query_arg('aweber_signedup', '-1'); ?>" />
                            </div>
                            <input class="email-field" type="text" value="" placeholder="<?php echo $instance['email_placeholder']; ?>" name="email">
                            <input class="submit" name="submit" type="submit" value="<?php echo $instance['button_text']; ?>">
                        </form>
                    <?php } elseif ($_GET['aweber_signedup'] == 1) { ?>
                        <p class="thanks"><?php echo $instance['success_message']; ?></p>
                    <?php } elseif ($_GET['aweber_signedup'] == -1) { ?>
                        <p class="error"><?php echo $instance['already_subscribed_message']; ?></p>
                    <?php } ?>
                
                <?php } ?>
                <div class="clear"></div>
                
                <p class="footer-text"><?php echo $instance['footer_text'];?></p>
                
            </div><!--subscribe_widget-->
		
		<?php

		/* After widget (defined by themes). */
		echo $after_widget;
	}


/*-----------------------------------------------------------------------------------*/
/*	Update Widget
/*-----------------------------------------------------------------------------------*/
	
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		/* Strip tags to remove HTML (important for text inputs). */
		//$instance['service'] = $new_instance['service'];
		$instance = array_merge($instance, $new_instance);

		// Feedburner ID
		if (strpos($instance['feedburner_id'], 'http') === 0)
			$instance['feedburner_id'] = substr( $instance['feedburner_id'], strrpos( $instance['feedburner_id'], '/' )+1 );

		/* No need to strip tags for.. */

		return $instance;
	}
	

/*-----------------------------------------------------------------------------------*/
/*	Widget Settings
/*-----------------------------------------------------------------------------------*/
	 
	function form( $instance ) {
        $defaults = $this->get_defaults();
		$instance = wp_parse_args( (array) $instance, $defaults ); 
		?>
        <div class="wp_subscribe_options_form">

		<?php $this->output_select_field('service', __('Service:', 'wp-subscribe'), array('feedburner' => 'FeedBurner', 'mailchimp' => 'MailChimp', 'aweber' => 'AWeber'), $instance['service']); ?>
		
		<div class="clear"></div>
        
        <div class="wp_subscribe_account_details">
        <div class="wp_subscribe_account_details_feedburner" style="display: none;">
        	<?php $this->output_text_field('feedburner_id', __('Feedburner ID', 'wp-subscribe'), $instance['feedburner_id']); ?>
        </div><!-- .wp_subscribe_account_details_mailchimp -->

        <div class="wp_subscribe_account_details_mailchimp" style="display: none;">
        	<?php $this->output_text_field('mailchimp_api_key', __('Mailchimp API key', 'wp-subscribe'), $instance['mailchimp_api_key']); ?>
        	<a href="http://kb.mailchimp.com/accounts/management/about-api-keys#Finding-or-generating-your-API-key" target="_blank"><?php _e('Find your API key', 'wp-subscribe'); ?></a>
        	<?php $this->output_text_field('mailchimp_list_id', __('Mailchimp List ID', 'wp-subscribe'), $instance['mailchimp_list_id']); ?>
        	<a href="http://kb.mailchimp.com/lists/managing-subscribers/find-your-list-id" target="_blank"><?php _e('Find your list ID', 'wp-subscribe'); ?></a>
        </div><!-- .wp_subscribe_account_details_mailchimp -->

        <div class="wp_subscribe_account_details_aweber" style="display: none;">
        	<?php $this->output_text_field('aweber_list_id', __('AWeber List ID', 'wp-subscribe'), $instance['aweber_list_id']); ?>
        	<a href="https://help.aweber.com/entries/61177326-What-Is-The-Unique-List-ID-" target="_blank"><?php _e('Find your list ID', 'wp-subscribe'); ?></a>
        </div><!-- .wp_subscribe_account_details_aweber -->
        </div><!-- .wp_subscribe_account_details -->

        <h4 class="wp_subscribe_labels_header"><a class="wp-subscribe-toggle" href="#" rel="wp_subscribe_labels"><?php _e('Labels', 'wp-subscribe'); ?></a></h4>
        <div class="wp_subscribe_labels" style="display: none;">
        <?php 
        $this->output_text_field('title', __('Title', 'wp-subscribe'), $instance['title']);
        $this->output_text_field('text', __('Text', 'wp-subscribe'), $instance['text']);
        $this->output_text_field('email_placeholder', __('Email Placeholder', 'wp-subscribe'), $instance['email_placeholder']);
        $this->output_text_field('button_text', __('Button Text', 'wp-subscribe'), $instance['button_text']);
        $this->output_text_field('success_message', __('Success Message', 'wp-subscribe'), $instance['success_message']);
        $this->output_text_field('error_message', __('Error Message', 'wp-subscribe'), $instance['error_message']);
        $this->output_text_field('already_subscribed_message', __('Error: Already Subscribed', 'wp-subscribe'), $instance['already_subscribed_message']);
        $this->output_text_field('footer_text', __('Footer Text', 'wp-subscribe'), $instance['footer_text']);
        ?>
        </div><!-- .wp_subscribe_labels -->

        </div><!-- .wp_subscribe_options_form -->

	<?php
	}


	public function output_text_field($setting_name, $setting_label, $setting_value) {
        ?>

        <p class="wp-subscribe-<?php echo $setting_name; ?>-field">
            <label for="<?php echo $this->get_field_id($setting_name) ?>">
                <?php echo $setting_label ?>
            </label>

            <input class="widefat" 
                   id="<?php echo $this->get_field_id($setting_name) ?>" 
                   name="<?php echo $this->get_field_name($setting_name) ?>" 
                   type="text" 
                   value="<?php echo $setting_value ?>" />
        </p>

        <?php
    }

    public function output_select_field($setting_name, $setting_label, $setting_values, $selected) {
        ?>

        <p class="wp-subscribe-<?php echo $setting_name; ?>-field">
            <label for="<?php echo $this->get_field_id($setting_name) ?>">
                <?php echo $setting_label ?>
            </label>

            <select class="widefat" 
                    id="<?php echo $this->get_field_id($setting_name) ?>" 
                    name="<?php echo $this->get_field_name($setting_name) ?>">

                <?php foreach ($setting_values as $value => $label) : ?>

                    <option value="<?php echo $value; ?>" <?php selected( $selected, $value ); ?>>
                        <?php echo $label; ?>
                    </option>

                <?php endforeach ?>
            </select>
        </p>

        <?php
    }

    public function get_defaults() {
        return array(
            'service' => 'feedburner',
            'feedburner_id' => '',
            'mailchimp_api_key' => '',
            'mailchimp_list_id' => '',
            'aweber_list_id' => '',

            'title' => __('Get more stuff like this<br/> <span>in your inbox</span>', 'wp-subscribe'),
            'text' => __('Subscribe to our mailing list and get interesting stuff and updates to your email inbox.', 'wp-subscribe'),
            'email_placeholder' => __('Enter your email here', 'wp-subscribe'),
            'button_text' => __('Sign Up Now', 'wp-subscribe'),
            'success_message' => __('Thank you for subscribing.', 'wp-subscribe'),
            'error_message' => __('Something went wrong.', 'wp-subscribe'),
            'already_subscribed_message' => __('This email is already subscribed', 'wp-subscribe'),
            'footer_text' => __('we respect your privacy and take protecting it seriously', 'wp-subscribe'),
        );
    }

    // For MailChimp subscribe
    function get_widget_settings($widget_id) {
        global $wp_registered_widgets;
        $ret = array();

        if (isset($wp_registered_widgets)) {
            $widget = $wp_registered_widgets[$widget_id];
            $option_data = get_option($widget['callback'][0]->option_name);

            if (isset($option_data[$widget['params'][0]['number']])) {
                $ret = $option_data[$widget['params'][0]['number']];
            }
        }

        return $ret;
    }

    function mailchimp_subscribe() {
        $ret = array(
            'success' => false,
            'message' => '',
        );

        $email = isset($_POST['mailchimp_email']) ? trim($_POST['mailchimp_email']) : '';
        $nonce = isset($_POST['_wpnonce']) ? trim($_POST['_wpnonce']) : '';
        $mc_api_key = null;
        $mc_list_id = null;
        $error_message = '';
        $widget_id = isset($_POST['widget_id']) ? trim($_POST['widget_id']) : '';

        if (($widget_settings = $this->get_widget_settings($widget_id))) {
            $mc_api_key = isset($widget_settings['mailchimp_api_key']) ? $widget_settings['mailchimp_api_key'] : null;
            $mc_list_id = isset($widget_settings['mailchimp_list_id']) ? $widget_settings['mailchimp_list_id'] : null;
            $error_message = isset($widget_settings['error_message']) ? $widget_settings['error_message'] : '';
        }

        if ($email &&
                $widget_settings &&
                $mc_api_key != null &&
                $mc_list_id != null &&
                wp_verify_nonce($nonce, 'wp-subscribe-mailchimp')) {

            try {
                $list = new Mailchimp_Lists(new Mailchimp($mc_api_key));
                $resp = $list->subscribe($mc_list_id, array('email' => $email), null, 'html', false, true);

                if ($resp) {
                    $ret['success'] = true;
                    $ret['message'] = $widget_settings['success_message'];
                } else {
                    $ret['message'] = $widget_settings['error_message'];
                }
            } catch (Exception $ex) {
                $ret['message'] = $widget_settings['error_message'];
            }
        } else {
            $ret['message'] = $error_message;
        }
        return $ret;
    }

}