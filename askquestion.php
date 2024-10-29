<?php
/**
 * Plugin Name: Ask Question
 * Plugin URI: http://plugins.birdshost.com/ask-question-2/
 * Description: Simple Plugin to let your users ask questions.
 * Version: 1.1
 * Author: Birds Host
 * Author URI: http://www.birdshost.com/
 * Text Domain: askquestion-plugin
 * License: CC0
 */

function askquestion_init() {
    $plugin_dir = basename(dirname(__FILE__));
    load_plugin_textdomain( 'askquestion-plugin', false, $plugin_dir . '\\lang\\' );
}
add_action('plugins_loaded', 'askquestion_init');

function create_askquestion_postype() {

    $labels = array(
        'name' => __('Ask Question', 'askquestion-plugin'),
        'singular_name' => __('Ask Question', 'askquestion-plugin'),
        'add_new' => __('New Ask Question', 'askquestion-plugin'),
        'add_new_item' => __('Add new Ask Question', 'askquestion-plugin'),
        'edit_item' => __('Edit Ask Question', 'askquestion-plugin'),
        'new_item' => __('New Ask Question', 'askquestion-plugin'),
        'view_item' => __('View Ask Question', 'askquestion-plugin'),
        'search_items' => __('Search Ask Question s', 'askquestion-plugin'),
        'not_found' =>  __('No Ask Question found', 'askquestion-plugin'),
        'not_found_in_trash' => __('No Ask Question found in Trash', 'askquestion-plugin'),
        'parent_item_colon' => '',
    );

    $args = array(
        'label' => __('Ask Question', 'askquestion-plugin'),
        'labels' => $labels,
        'public' => false,
        'can_export' => true,
        'show_ui' => true,
        'menu_position'     => 32,
        '_builtin' => false,
        'capability_type' => 'post',
        'menu_icon'         => plugin_dir_url(__FILE__).'images/icon.png',
        'hierarchical' => false,
        'rewrite' => array( "slug" => "askquestion" ),
        'supports'=> array('title', 'editor', 'comments'),
        'show_in_nav_menus' => true
    );

    register_post_type( 'askquestion', $args);
}

add_action( 'init', 'create_askquestion_postype' );

function create_askquestion_tags() {

    $labels = array(
        'name'              => __( 'Ask Tags' ),
        'singular_name'     => __( 'Ask Tag'),
        'search_items'      => __( 'Search Ask Tags' ),
        'all_items'         => __( 'All Ask Tags' ),
        'parent_item'       => __( 'Parent Ask Tag' ),
        'parent_item_colon' => __( 'Parent Ask Tag:' ),
        'edit_item'         => __( 'Edit Ask Tag' ),
        'update_item'       => __( 'Update Ask Tag' ),
        'add_new_item'      => __( 'Add New Ask Tag' ),
        'new_item_name'     => __( 'New Ask Tag Name' ),
        'menu_name'         => __( 'Ask Tags' ),
    );

    $args = array(
        'hierarchical'      => false,
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => array( 'slug' => 'asktag' ),
    );

    register_taxonomy( 'asktag', 'askquestion', $args );

}

add_action('init', 'create_askquestion_tags');

function askquestion_title_placeholder( $title ){

    $screen = get_current_screen();

    if ( 'askquestion' == $screen->post_type ){
        $title = __('your question here', 'askquestion-plugin');
    }

    return $title;
}

add_filter( 'enter_title_here', 'askquestion_title_placeholder' );

function show_asks(  ) {

    $isCaptcha = get_option('askquestion_setting_captcha');

    if($isCaptcha)
    {
        require_once(dirname(__FILE__).'/captcha/captcha.php');

        $publickey = get_option( 'askquestion_setting_captcha_publickey' );
        $privatekey = get_option( 'askquestion_setting_captcha_privatekey' );
        $resp = null;
        $error = null;
    }

    ob_start();

    wp_enqueue_style( 'askquestion', plugins_url('askquestion.css',__FILE__) );

    if( 'POST' == $_SERVER['REQUEST_METHOD']
        && !empty( $_POST['action'] )
        && $_POST['post_type'] == 'askquestion' && $_POST['question'] != "")
    {
        if ($isCaptcha && $_POST["recaptcha_response_field"])
        {
            $resp = recaptcha_check_answer ($privatekey,
                $_SERVER["REMOTE_ADDR"],
                $_POST["recaptcha_challenge_field"],
                $_POST["recaptcha_response_field"]);

            if ($resp->is_valid) {

                $title =  $_POST['question'];

                $post = array(
                    'post_title'	=> $title,
                    'post_status'	=> 'draft',
                    'post_type'		=> 'askquestion'
                );

                $id = wp_insert_post($post);

                echo "<div class='alert success'>".__('<b>Success!</b> Ask Question is now ready for approval.')."</div>";

                if(isset($_POST['username']))
                {
                    add_post_meta($id, 'askquestion_username', $_POST['username']);
                }
                if(isset($_POST['email']))
                {
                    add_post_meta($id, 'askquestion_email', $_POST['email']);
                }

                if(get_option('askquestion_setting_email') == true)
                {
                    $mailtext = __('New Ask Question Received', 'askquestion-plugin');

                    $admin_email = get_option('admin_email');
                    wp_mail( $admin_email,  $mailtext, "Ask Question: ".$title);
                }

            }
            else
            {
                $error = $resp->error;
                echo "<div class='alert danger'>".__('<b>Error!</b> The Captcha was wrong.')."</div>";
            }
        }
        else if(!$isCaptcha)
        {
            $title =  $_POST['question'];

            $post = array(
                'post_title'	=> $title,
                'post_status'	=> 'draft',
                'post_type'		=> 'askquestion'
            );

            $id = wp_insert_post($post);

            echo "<div class='alert success'>".__('<b>Success!</b> Ask Question is now ready for approval.')."</div>";

            if(isset($_POST['username']))
            {
                add_post_meta($id, 'askquestion_username', $_POST['username']);
            }
            if(isset($_POST['email']))
            {
                add_post_meta($id, 'askquestion_email', $_POST['email']);
            }

            if(get_option('askquestion_setting_email') == true)
            {
                $mailtext = __('New Ask Question Received', 'askquestion-plugin');

                $admin_email = get_option('admin_email');
                wp_mail( $admin_email,  $mailtext, "Ask Question: ".$title);
            }
        }
        else
        {
            echo "<div class='alert danger'>".__('<b>Error!</b> You have to fill out the Captcha.')."</div>";
        }
    }
    else if('POST' == $_SERVER['REQUEST_METHOD'] && !empty( $_POST['action'] ) && $_POST['question'] == "")
    {
        echo "<div class='alert danger'>".__('<b>Error!</b> You have to fill out the Question.')."</div>";
    }
    ?>

    <div id="askquestion">
        <form id="newask" name="newask" method="post" action="">

            <label for="question" id="questionLabel"><?php _e('Question to ask', 'askquestion-plugin'); ?></label><br />
            <input type="text" id="question" value="" tabindex="1" size="20" name="question" />

            <?php
            if(get_option('askquestion_setting_user_response') == true)
            {
                echo display_userdatafields();
            }
            ?>

            <p><input type="submit" value="Ask Question" tabindex="6" id="submit" name="submit" /></p>

            <input type="hidden" name="post_type" id="post_type" value="askquestion" />
            <input type="hidden" name="action" value="post" />
            <?php wp_nonce_field( 'new-post' ); ?>
            <?php
            if($isCaptcha)
            {
                ?>
            <div id="captcha-div">
            <?php echo recaptcha_get_html($publickey, $error); ?>
            </div>
            <script>
                jQuery( "#question" ).focus(function() {
                    if ( jQuery( "#captcha-div" ).is( ":hidden" ) ) {
                        jQuery( "#captcha-div" ).slideDown( "slow" );
                    }
                });
            </script>
            <?php } ?>
        </form>

    <?php
    askquestion_output_normal();
    $output = ob_get_contents();
    ob_end_clean();
    return $output;
}
add_shortcode('askquestion', 'show_asks');

function display_userdatafields()
{
    ob_start(); ?>

    <div id="userdatafields">
        <div id="usernameDiv">
            <div id="usernamelabel"><b><label for="username"><?php _e('Name', 'askquestion-plugin'); ?></label></b></div>
            <div id="usernameinput"><input type="text" id="username" value="" tabindex="1" size="20" name="username" /></div>
        </div>
        <div id="useremailDiv">
            <div id="useremaillabel"><b><label for="email"><?php _e('Email', 'askquestion-plugin'); ?></label></b></div>
            <div id="useremailinput"><input type="email" id="email" value="" tabindex="1" size="20" name="email" /></div>
            <div id="emailmsg"><?php _e('If you provide an Email you will receive a message, once your Question is Answered.', 'askquestion-plugin'); ?></div>
        </div>
    </div>
    <div id="extenduserdata"><button type="button"><?php _e("Advanced Options", "askquestion-plugin"); ?></button></div>
    <script>
		jQuery( "#extenduserdata" ).click(function() {
			if ( jQuery( "#userdatafields" ).is( ":hidden" ) ) {
				jQuery( "#userdatafields" ).slideDown( "slow" );
			} else {
				jQuery( "#userdatafields" ).slideUp();
			}
		});
	</script>

    <?php $output = ob_get_contents();
    ob_end_clean();
    return $output;
}

function askquestion_output_normal()
{
		global $wp_query;
		
        if ( get_query_var('paged') ) {
            $paged = get_query_var('paged');
        } else if ( get_query_var('page') ) {
            $paged = get_query_var('page');
        } else {
            $paged = 1;
        }

        $args = array(
            'post_type' => 'askquestion',
            'post_status' => 'publish',
            'paged' => $paged,
            'orderby' => 'date',
            'posts_per_page' => get_option( 'askquestion_setting_number_askquestions', 5 )
        );

        query_posts($args);

        while ( have_posts() ) : the_post(); ?>

            <div class="entry">
                <div class="question">
                    <strong><?php the_title(); ?></strong>
                    <?php
						$customs = get_post_custom(get_the_ID());
						$username = ($customs['askquestion_username'][0]);
						if ($username) {
							echo ' from ' . $username;	
						}
					?>
                    <span class="date">
                        <?php
                        $allterms = get_the_terms(get_the_ID(), "asktag");

                        if(!empty($allterms))
                        {
                            $i = 0;
                            foreach($allterms as $term)
                            {
                                echo "<strong>" . $term->name . "</strong>";
                                $i++;
                                if($i != count($allterms))
                                {
                                    echo ", ";
                                }
                            }
                            echo " - ";
                        }

                        ?>
                        <?php the_time('j. F Y'); ?>
                    </span>
                </div>

                <div class="answer">
                    <p><?php the_content(); ?></p>
                </div>
            </div>

            <hr />

        <?php endwhile; ?>
        <?php askquestion_pagination($wp_query->max_num_pages); ?>
    </div> <!-- Ende askquestion Div -->
    <?php
    wp_reset_query();
}

function add_askquestion_columns($askquestion_columns) {
    $new_columns['cb'] = '<input type="checkbox" />';
    $new_columns['date'] = __('Date', 'askquestion-plugin');
    $new_columns['title'] = __('Ask Question', 'askquestion-plugin');
    $new_columns['answer'] = __('Answer', 'askquestion-plugin');
    $new_columns['username'] = __('Username', 'askquestion-plugin');
    $new_columns['email'] = __('Email', 'askquestion-plugin');

    return $new_columns;
}

add_filter('manage_edit-askquestion_columns', 'add_askquestion_columns');

add_action('manage_askquestion_posts_custom_column', 'manage_askquestion_columns', 10, 2);

function manage_askquestion_columns($column_name, $id) {
    $customs = get_post_custom($id);

    switch ($column_name) {
        case 'id':
            echo $id;
            break;
        case 'username':
            if(isset($customs['askquestion_username']))
            {
                foreach( $customs['askquestion_username'] as $key => $value)
                    echo $value;
            }
            break;
        case 'email':
            if(isset($customs['askquestion_email']))
            {
                foreach( $customs['askquestion_email'] as $key => $value)
                    echo $value;
            }
            break;
        case 'answer':
            echo get_the_content($id);
            break;
        default:
            break;
    }
}



function askquestion_pagination($pages = '', $range = 5)
{
    $showitems = ($range * 2)+1;

    global $paged;

    if ( get_query_var('paged') ) {
        $paged = get_query_var('paged');
    } else if ( get_query_var('page') ) {
        $paged = get_query_var('page');
    } else {
        $paged = 1;
    }

    if($pages == '')
    {
        global $wp_query;
        $pages = $wp_query->max_num_pages;

        if(!$pages)
        {
            $pages = 1;
        }
    }

    if(1 != $pages)
    {
        ?>
        <div class="askquestion_pagination"><span><?php echo __('Page', 'askquestion-plugin'); ?> <?php echo $paged; ?> <?php echo __('of', 'askquestion-plugin');?> <?php echo $pages; ?></span>
            <?php
            if($paged > 2 && $paged > $range+1 && $showitems < $pages)
            { ?>

                <a href="<?php echo get_pagenum_link(1); ?>">&laquo;</a>

            <?php }

            if($paged > 1)
            { ?>
                <a href="<?php echo get_pagenum_link($paged - 1); ?>">&lsaquo;</a>;
            <?php }

            for ($i=1; $i <= $pages; $i++)
            {
                if (1 != $pages &&( !($i >= $paged+$range+1 || $i <= $paged-$range-1) || $pages <= $showitems ))
                {
                    if ($paged == $i)
                    { ?>
                        <span class="current"><?php echo $i; ?></span>
                    <?php }
                    else
                    { ?>
                        <a href="<?php echo get_pagenum_link($i); ?>" class="inactive"><?php echo $i; ?></a>
                    <?php }
                }
            }

            if ($paged < $pages)
            { ?>
                <a href="<?php echo get_pagenum_link($paged + 1); ?>">&rsaquo;</a>
            <?php }
            if ($paged < $pages-1 &&  $paged+$range-1 < $pages && $showitems < $pages)
            { ?>
                <a href="<?php echo get_pagenum_link($pages); ?>">&raquo;</a>
            <?php }
            ?>
        </div>
        <?php
    }
}

function askquestion_stats() {
?>
    <h4>Ask Question - Overview</h4>
    <br />
    <ul>
	    <li class="post-count">
            <?php
            $type = 'askquestion';
            $args = array(
                'post_type' => $type,
                'post_status' => 'publish',
                'posts_per_page' => -1);

            $my_query = query_posts( $args );
            ?>

            <a href="edit.php?post_type=askquestion&post_status=publish"><?php echo count($my_query); ?> <?php _e('published', 'askquestion-plugin'); ?></a>
        </li>
        <li class="page-count">
            <?php
            $args = array(
                'post_type' => $type,
                'post_status' => 'draft',
                'posts_per_page' => -1);

            $my_query = query_posts( $args );
            ?>
            <a href="edit.php?post_type=askquestion&post_status=draft"><?php echo count($my_query); ?> <?php _e('open', 'askquestion-plugin'); ?></a>

        </li>
    </ul>
<?php
    wp_reset_query();
}

add_action('activity_box_end', 'askquestion_stats');

function askquestion_settings_init() {

    add_settings_section(
        'askquestion_setting_section',
        __('Ask Question Settings', 'askquestion-plugin'),
        'askquestion_setting_section_callback',
        'reading'
    );

 	add_settings_field(
        'askquestion_setting_email',
        __('E-Mail Alert on new Ask', 'askquestion-plugin'),
        'askquestion_setting_callback',
        'reading',
        'askquestion_setting_section'
    );

    register_setting( 'reading', 'askquestion_setting_email' );

    add_settings_field(
        'askquestion_setting_captcha',
        __('Show Captcha', 'askquestion-plugin'),
        'askquestion_captcha_callback',
        'reading',
        'askquestion_setting_section'
    );

    register_setting( 'reading', 'askquestion_setting_captcha' );

    add_settings_field(
        'askquestion_setting_captcha_publickey',
        __('Captcha Public Key', 'askquestion-plugin'),
        'askquestion_captcha_puk_callback',
        'reading',
        'askquestion_setting_section'
    );

    register_setting( 'reading', 'askquestion_setting_captcha_publickey' );

    add_settings_field(
        'askquestion_setting_captcha_privatekey',
        __('Captcha Private Key', 'askquestion-plugin'),
        'askquestion_captcha_prk_callback',
        'reading',
        'askquestion_setting_section'
    );

    register_setting( 'reading', 'askquestion_setting_captcha_privatekey' );

    add_settings_field(
        'askquestion_setting_number_askquestions',
        __('Number of askquestions', 'askquestion-plugin'),
        'askquestion_number_callback',
        'reading',
        'askquestion_setting_section'
    );

    register_setting( 'reading', 'askquestion_setting_number_askquestions' );

    add_settings_field(
        'askquestion_setting_user_response',
        __('User Fields', 'askquestion-plugin'),
        'askquestion_setting_user_response_callback',
        'reading',
        'askquestion_setting_section'
    );

    register_setting( 'reading', 'askquestion_setting_user_response' );
 }

 add_action( 'admin_init', 'askquestion_settings_init' );

function askquestion_setting_section_callback() {
     echo '<p>'.__("Configure your Asks", "askquestion-plugin").'</p>';
}

function askquestion_setting_callback() {
    echo '<input name="askquestion_setting_email" id="gv_thumbnails_insert_into_excerpt" type="checkbox" value="1" class="code" ' . checked( 1, get_option( 'askquestion_setting_email' ), false ) . ' />';
}

function askquestion_captcha_callback() {
    echo '<input name="askquestion_setting_captcha" type="checkbox" value="1" class="code" ' . checked( 1, get_option( 'askquestion_setting_captcha' ), false ) . ' />';
}

function askquestion_setting_user_response_callback() {
    echo '<input name="askquestion_setting_user_response" id="gv_thumbnails_insert_into_excerpt" type="checkbox" value="1" class="code" ' . checked( 1, get_option( 'askquestion_setting_user_response' ), false ) . ' />' . __('Would you like to display User Fields, like E-Mail and Username? This would give your users the possibility to receive a Notification if an Answer is answered.', 'askquestion-plugin');
}

function askquestion_captcha_prk_callback() {
    echo '<input name="askquestion_setting_captcha_privatekey" id="gv_thumbnails_insert_into_excerpt" type="text" class="code" value="' . get_option( 'askquestion_setting_captcha_privatekey' ) . '" />
        <p class="description">' . __('Get a key from <a href="https://www.google.com/recaptcha/admin/create" target="_blank">https://www.google.com/recaptcha/admin/create</a>', 'askquestion-plugin') . "</p>";
}

function askquestion_captcha_puk_callback() {
    echo '<input name="askquestion_setting_captcha_publickey" id="gv_thumbnails_insert_into_excerpt" type="text" class="code" value="' . get_option( 'askquestion_setting_captcha_publickey' ) . '" />
        <p class="description">' . __('Get a key from <a href="https://www.google.com/recaptcha/admin/create" target="_blank">https://www.google.com/recaptcha/admin/create</a>', 'askquestion-plugin') . "</p>";
}

function askquestion_number_callback() {
    echo '<input name="askquestion_setting_number_askquestions" id="gv_thumbnails_insert_into_excerpt" type="text" class="code" value="' . get_option( 'askquestion_setting_number_askquestions', 5 ) . '" />
        <p class="description">' . __('How much askquestions you want to display on one page.', 'askquestion-plugin') . "</p>";
}

add_action( 'admin_menu', 'add_user_menu_bubble' );

function add_user_menu_bubble() {

    global $menu;

    foreach ( $menu as $key => $value ) {
        if ( $menu[$key][2] == 'edit.php?post_type=askquestion' ) {

            $type = 'askquestion';
            $args = array(
                'post_type' => $type,
                'post_status' => 'draft',
                'posts_per_page' => -1);

            $my_query = query_posts( $args );
            if(count($my_query) > 0)
            {
                $menu[$key][0] .= '    <span class="update-plugins"><span class="plugin-count">' . count($my_query) . '</span></span> ';
            }
            wp_reset_query();
            return;
        }
    }

}

function publish_askquestion_hook($id)
{
    $customs = get_post_custom($id);
    if(isset($customs['askquestion_email']))
        wp_mail( $customs['askquestion_email'],  get_bloginfo('name').__(' - Ask Question - Answer Received', 'askquestion-plugin'), __('Your Ask Question has been Answered!', 'askquestion-plugin'));
}

add_action( 'publish_askquestion', 'publish_askquestion_hook' );
?>