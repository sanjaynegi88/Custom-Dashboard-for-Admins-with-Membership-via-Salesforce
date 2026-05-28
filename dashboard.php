<?php
/*
Plugin Name: 108 User Dashboard
Description: Simple Dashboard Template for User/Members
Version: 1.0
Author: 108Ideas
*/

@ini_set( 'display_errors', '1' );

// Allow everything EXCEPT notices, warnings, deprecated
error_reporting(
    E_ALL
    & ~E_NOTICE
    & ~E_USER_NOTICE
    & ~E_WARNING
    & ~E_USER_WARNING
    & ~E_DEPRECATED
    & ~E_USER_DEPRECATED
    & ~E_STRICT
);


// Enqueue intl-tel-input




// Enqueue scripts
function custom_phone_input_scripts() {
    wp_enqueue_style(
        'intl-tel-input-css',
        'https://cdn.jsdelivr.net/npm/intl-tel-input@18.5.3/build/css/intlTelInput.css'
    );

    wp_enqueue_script(
        'intl-tel-input-js',
        'https://cdn.jsdelivr.net/npm/intl-tel-input@18.5.3/build/js/intlTelInput.min.js',
        array(),
        null,
        true
    );

    wp_enqueue_script(
        'intl-tel-utils',
        'https://cdn.jsdelivr.net/npm/intl-tel-input@18.5.3/build/js/utils.js',
        array('intl-tel-input-js'),
        null,
        true
    );
}
add_action('wp_enqueue_scripts', 'custom_phone_input_scripts');


// Shortcode with attributes
function custom_phone_input_shortcode($atts) {

    $atts = shortcode_atts(array(
        'id' => 'phone',
        'name' => 'phone',
        'class' => '',
        'placeholder' => 'Enter phone number'
    ), $atts);

    $field_id = esc_attr($atts['id']);

    ob_start(); ?>

    <input 
        type="tel"
        id="<?php echo esc_attr($atts['id']); ?>"
        name="<?php echo esc_attr($atts['name']); ?>"
        class="<?php echo esc_attr($atts['class']); ?>"
        placeholder="<?php echo esc_attr($atts['placeholder']); ?>"
        required
        style="width:100%; padding:10px;"
    >

    <script>
    document.addEventListener("DOMContentLoaded", function () {

        const input = document.querySelector("#<?php echo $field_id; ?>");

        if (!input) return;

        const iti = window.intlTelInput(input, {
            initialCountry: "in",
            separateDialCode: true,
            preferredCountries: ["in", "us", "gb"],
            utilsScript: "https://cdn.jsdelivr.net/npm/intl-tel-input@18.5.3/build/js/utils.js"
        });

        // Only numbers allowed
        input.addEventListener("input", function () {
            this.value = this.value.replace(/[^\d]/g, '');
        });

        // Validate on blur
        input.addEventListener("blur", function () {
            if (input.value && !iti.isValidNumber()) {
                alert("Invalid phone number");
            }
        });

        // Store full number in hidden field (optional)
        const hidden = document.createElement("input");
        hidden.type = "hidden";
        hidden.name = "<?php echo esc_attr($atts['name']); ?>_full";
        input.parentNode.appendChild(hidden);

        input.addEventListener("change", function () {
            hidden.value = iti.getNumber(); // +919876543210
        });

    });
    </script>

    <?php
    return ob_get_clean();
}
add_shortcode('phone_input', 'custom_phone_input_shortcode');













add_action('wp_ajax_md_load_page', 'md_load_page_content');
add_action('wp_ajax_nopriv_md_load_page', 'md_load_page_content');

function md_load_page_content() {

    // Optional security
    // check_ajax_referer('md_ajax_nonce', 'nonce');

    $page_id = intval( $_POST['page_id'] ?? 0 );
    if ( ! $page_id ) {
        wp_die( 'Invalid page.' );
    }

    $post = get_post( $page_id );
    if ( ! $post || $post->post_status !== 'publish' ) {
        wp_die( 'Page not found.' );
    }

    $content = '';

    // ✅ Elementor-aware rendering
    if ( class_exists( '\Elementor\Plugin' ) ) {

        $document = \Elementor\Plugin::instance()->documents->get( $page_id );

        if ( $document && $document->is_built_with_elementor() ) {
            $content = \Elementor\Plugin::instance()
                ->frontend
                ->get_builder_content_for_display( $page_id );
        }
    }

    // ✅ Fallback for non-Elementor pages
    if ( empty( $content ) ) {
        $content = apply_filters( 'the_content', $post->post_content );
    }

    // Ensure shortcodes execute
    $content = do_shortcode( $content );

    echo $content;
    wp_die();
}



function dashboard_108_enqueue_scripts() {

    // Register an empty script handle
    wp_register_script(
        'dashboard-inline-js',
        '',
        array('jquery'),
        null,
        true
    );

    wp_enqueue_script('dashboard-inline-js');

    // Pass PHP data to JS
    wp_localize_script('dashboard-inline-js', 'mdAjax', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('md_ajax_nonce')
    ));

    // Inline JS
    wp_add_inline_script('dashboard-inline-js', dashboard_108_inline_js());

}
add_action('wp_enqueue_scripts', 'dashboard_108_enqueue_scripts');


function dashboard_108_enqueue_styles() {
    // Plugin directory URL
    $plugin_url = plugin_dir_url( __FILE__ );

    // Register and enqueue the CSS file
    wp_register_style(
        'dashboard-css',                      
        $plugin_url . '/assets/dashboard.css',                 
        array(),                                   
        filemtime( plugin_dir_path( __FILE__ ) . '/assets/dashboard.css' ) // Version for cache-busting
    );
    wp_enqueue_style( 'dashboard-css' );

    wp_enqueue_script('member-panel-js', plugin_dir_url( __FILE__ ).'/assets/dashboard.js', [], null, true);
wp_localize_script('member-panel-js', 'ajaxurl', admin_url('admin-ajax.php'));

}
add_action( 'wp_enqueue_scripts', 'dashboard_108_enqueue_styles' );




add_shortcode('108-member-dashboard','dashboard_for_108_members');

function dashboard_for_108_members($attr){


if ( ! is_user_logged_in() ) {
    return '<p>You must be logged in to view this dashboard.</p>';
  }


$user = wp_get_current_user();
$user_id = $user->ID;

$type = get_user_meta( $user_id, 'user_type', true );

if($type == 'complainant'){

$cdashboard = fetch_108_complainant_dashboard();
 return $cdashboard; 
}

ob_start();


$user = wp_get_current_user();
$email = $user->user_email;
$phone = get_user_meta( $user->ID, 'phone', true );

if ( empty( $phone ) ) {
    $phone = get_user_meta( $user->ID, 'user_phone', true );
}

if ( empty( $phone ) ) {
    $phone = get_user_meta( $user->ID, 'billing_phone', true );
}
$phone = $phone ? $phone : '—';
$first_name = get_user_meta( $user->ID, 'first_name', true );
$last_name  = get_user_meta( $user->ID, 'last_name', true );
$full_name = trim( $first_name . ' ' . $last_name );

$membership_type = get_user_meta( $user->ID, 'membership_type', true );
$membership_label = ! empty( $membership_type ) ? $membership_type : 'Non Member';

?>


<div class="md-dashboard-wrapper">

  <!-- LEFT SIDEBAR -->
  <aside class="md-sidebar">

    <div class="md-profile">
    
<?php
$user_id = get_current_user_id(); // or pass specific user ID
$image = get_user_meta($user_id, 'user_picture', true);

// fallback placeholder
$placeholder = plugin_dir_url(__FILE__) . 'assets/avatar.svg';

// decide which to use
$img_src = !empty($image) ? $image : $placeholder;
?>

<div class="md-avatar cs-av">
    <img src="<?php echo esc_attr($img_src); ?>" alt="User Avatar">
</div>



<h2><?php echo esc_html( $full_name ); ?></h2>

<span class="md-badge">
  <?php echo esc_html( $membership_label ); ?>
</span>

<div class="md-contact">
  <p><?php echo esc_html( $email ); ?></p>

  <p><?php echo esc_html( $phone ); ?>
  </p>


</div>
    </div>

<nav class="md-nav">
  <a class="md-route active" data-pageid="dashboard">
    <span class="md-icon icon-dashboard"></span> Dashboard
  </a>

  <a class="md-route" data-pageid="PROFILE">
    <span class="md-icon icon-profile"></span> My Profile
  </a>

<?php
    $user_id = get_current_user_id();
    $current_user = wp_get_current_user();
    $current_email = $current_user->user_email;

    $parent_account_id = get_user_meta($user_id, 'organization', true);

    // If no organisation → show nothing
    if ( !empty($parent_account_id) ) {
  
?>
    <a class="md-route" data-pageid="3674">
    <span class="md-icon icon-members"></span> Manage Roster
  </a>
<?php } ?>

<?php
if ( is_user_logged_in() ) {
    $user_id = get_current_user_id();
    $account_type = get_user_meta( $user_id, 'account_type', true );

    if ( $account_type === 'main' ) {
        ?>
        <a class="md-route" data-pageid="APPLICATIONS">
            <span class="md-icon icon-company"></span> Manage Company Profile
        </a>
        <?php
    }
}
?>


  <a class="md-route" data-pageid="my-events">
    <span class="md-icon icon-events"></span> My Events
  </a>

  <a class="md-route" data-pageid="my-courses">
    <span class="md-icon icon-courses"></span> My Courses
  </a>

  <a class="md-route" data-pageid="2585">
    <span class="md-icon icon-exams"></span> My Exams
  </a>

  <a class="md-route" data-pageid="ce-credits">
    <span class="md-icon icon-credits"></span> My CE Credits
  </a>



  <a class="md-logout" href="<?php echo wp_logout_url(); ?>">
     <span class="md-icon icon-community"></span> Logout
  </a>
</nav>


    
  </aside>

  <!-- MAIN CONTENT -->
<main class="md-main">

  <!-- LOADER -->
  <div id="md-loader" style="display:none;">
    <div class="md-spinner"></div>
  </div>

  <!-- DASHBOARD -->
  <div id="md-default-dashboard">

<div class="wlcm-msg md-card" style="margin-bottom: 20px;">
<h2>Welcome <?php echo $full_name; ?></h2>
<p>We're glad you’re here. This is your place to manage your membership, access benefits, manage your learning and more.</p>
</div>

    <?php echo do_shortcode('[dashboard_main_content]'); ?>
  </div>

  <!-- MY COURSES (LearnDash) -->
  <div id="md-my-courses" style="display:none;">
    <?php echo do_shortcode('[ld_profile]'); ?>
  </div>


  
<!-- MY CE CREDITS -->
<div id="md-ce-credits" style="display:none;">
  <?php echo do_shortcode('[sf_ce_credits]'); ?>
</div>



<!-- MY EVENTS -->
<div id="md-my-events" style="display:none;">
    <div class="md-card">
        <h3>My Events</h3>
<?php 
echo gf_fetch_registered_active_events($user_id);
?>
    </div>
</div>


<!-- APPLICATIONS FORM -->
<div id="md-applications" style="display:none;">
<?php
$company_id = get_user_meta( $user_id, 'organization', true );
        $query = "SELECT FIELDS(ALL) FROM Account WHERE id = '$company_id'";
        $response = SF_APIConnector::getSQueryObject( $query, 1 );
?>

  <div class="md-card">
    <h3>Manage Company Profile</h3>

    <form id="company-profile-form" enctype="multipart/form-data">

      <div class="form-group">
        <label>Company Name</label>
        <input type="text" name="company_name" required value="<?php echo $response->Name; ?>">
      </div>

        <div class="form-group">
        <label>Company Phone</label>
        <input type="tel" name="company_phone" required value="<?php echo $response->Phone; ?>">
        </div>

      <div class="form-group">
        <label>Company Description</label>
        <textarea name="company_description" required><?php echo $response->IS_Bio__c; ?></textarea>
      </div>

        <div class="form-group">
        <label>Facebook URL</label>
        <input type="text" name="company_facebook" required value="<?php echo $response->IS_Facebook__c; ?>">
        </div>

        <div class="form-group">
        <label>Instagram URL</label>
        <input type="text" name="company_instagram" required value="<?php echo $response->IS_Instagram__c; ?>">
        </div>

        <div class="form-group">
        <label>Linkedin URL</label>
        <input type="text" name="company_linkedin" required value="<?php echo $response->IS_LinkedIn__c; ?>">
        </div>

      <div class="form-group">
        <label>Company Logo</label>
        <?php 
        if($response->IS_Photo__c !=""){
          ?>
          <img src="<?php echo $response->IS_Photo__c; ?>" height="100" />
          <?php 
        }
        ?>
        <input type="file" name="company_logo" accept="image/*">
      </div>

      <button type="submit" class="cmn-btn primary">
        Save Profile
      </button>

    </form>

    <div id="company-profile-msg"></div>

  </div>
</div>




  <!-- AJAX CONTENT -->
  <div id="md-dynamic-content" style="display:none;"></div>

</main>


</div>







<?php
return ob_get_clean();
}




add_shortcode('dashboard_main_content', 'dashboard_main_content_cb');

function dashboard_main_content_cb() {
    ob_start();


    $user = wp_get_current_user();
    $user_id = get_current_user_id();
    $courses = md_get_learndash_enrolled_courses( $user_id );
    $events = md_get_registered_events_from_orders( $user_id );
    $membership = md_get_membership_data( $user->ID );

    ?>

  <section class="md-grid">

    <!-- MEMBERSHIP STATUS (DOUBLE WIDTH) -->


        
<?php
$user_id = get_current_user_id();


$sf_id = get_user_meta($user_id, 'sf_object_id', true);

$start_date = get_user_meta($user_id, 'membership_start_date', true);
$end_date   = get_user_meta($user_id, 'membership_end_date', true);
$type       = get_user_meta($user_id, 'membership_type', true);
$meta_status = get_user_meta($user_id, 'membership_status', true);
$org_id = get_user_meta($user_id, 'organization', true);
$org        = get_user_meta($user_id, 'organization_name', true);
$account    = get_user_meta($user_id, 'account_type', true);



$query = "SELECT FIELDS(ALL) FROM Account WHERE Id = '".$sf_id."'";
$responseprofile = SF_APIConnector::getSQueryObject( $query, 1 );

$status = $responseprofile->IS_Status__c;

if($meta_status != $status){
    $payload = [
        'Id'   => $org_id,
        'IS_Status__c' => 'Active'
    ];
    
    $records[] = ['attributes' => [ 'type' => 'Account' ],] + $payload;
    $response = SF_APIConnector::postCURLObject(json_encode(['allOrNone' => false,'records'   => $records,]), 'PATCH' );
 update_user_meta($user_id, 'membership_status', $status);
}



// Need to Activate all the Membership

if (!empty($start_date) || !empty($end_date) || !empty($type) || !empty($status) || !empty($org) || !empty($account)):

    $days_left = null;
    $formatted_end = '';

    if (!empty($end_date)) {
        $end_timestamp = strtotime($end_date);
        $today = current_time('timestamp');

        $days_left = ceil(($end_timestamp - $today) / (60 * 60 * 24));
        $days_left = max(0, $days_left);

        $formatted_end = date_i18n(get_option('date_format'), $end_timestamp);
    }

    $formatted_start = !empty($start_date) ? date_i18n(get_option('date_format'), strtotime($start_date)) : '';
    $is_expiring = ($days_left !== null && $days_left <= 30);



$total_days = 365; // fallback



$progress = ($days_left / $total_days) * 100;
$progress = max(0, min(100, $progress));

$courses_count = 0;

if (function_exists('ld_get_mycourses')) {
    $courses = ld_get_mycourses($user_id);
    $courses_count = is_array($courses) ? count($courses) : 0;
}

/**
 * ✅ Events Count (from user_events meta array)
 */



/**
 * ✅ Static (for now)
 */
$certificates_count = 0;
$support_count = 0;

?>

<div class="md-card md-membership md-span-2">

  <h3>Membership Details</h3>

  <div class="md-membership-inner">

    <!-- LEFT INFO -->
    <div class="md-membership-info">

      <?php if (!empty($type)): ?>
        <div class="md-row">
          <span class="md-label">Plan:</span>
          <span class="md-value"><?php echo esc_html($type); ?></span>
        </div>
      <?php endif; ?>

      <?php if (!empty($status)): ?>
        <div class="md-row">
          <span class="md-label">Status:</span>
          <span class="md-value md-status status-<?php echo esc_attr(strtolower($status)); ?>">
            <?php echo esc_html($status); ?>
          </span>
        </div>
      <?php endif; ?>

      <?php if (!empty($org)): ?>
        <div class="md-row">
          <span class="md-label">Organization:</span>
          <span class="md-value"><?php echo esc_html($org); ?></span>
        </div>
      <?php endif; ?>

      <?php if (!empty($account)): ?>
        <div class="md-row">
          <span class="md-label">Role:</span>
          <span class="md-value"><?php echo esc_html($account); ?></span>
        </div>
      <?php endif; ?>

      <?php if (!empty($formatted_start)): ?>
        <div class="md-row">
          <span class="md-label">Start Date:</span>
          <span class="md-value"><?php echo esc_html($formatted_start); ?></span>
        </div>
      <?php endif; ?>

      <?php if (!empty($formatted_end)): ?>
        <div class="md-row">
          <span class="md-label">Renewal Date:</span>
          <span class="md-value"><?php echo esc_html($formatted_end); ?></span>
        </div>
      <?php endif; ?>

      <?php if ($is_expiring): ?>
        <div class="md-actions">
          <a href="/membership-renewal" class="cmn-btn primary">
            Renew Membership
          </a>
        </div>
      <?php endif; ?>

    </div>

    <!-- RIGHT SIDE (DAYS RING) -->
    <?php if ($days_left !== null): ?>
  <div class="md-ring-pie" style="--progress: <?php echo $progress; ?>;">
  <div class="md-ring-inner">
    <strong id="daysCounter"><?php echo $days_left; ?></strong>
    <span style="display:block;">Days Left</span>
  </div>
</div>
    <?php endif; ?>

  </div>

</div>

<?php endif; ?>





    <!-- STATS -->
    <div class="md-card md-stats">
        <?php
$user_id = get_current_user_id();

$courses = learndash_user_get_enrolled_courses($user_id);

$total_courses = is_array($courses) ? count($courses) : 0;
        ?>
    <div class="div-count-box course-total">
    <div>
        <img src="/wp-content/plugins/108-dashboard//assets/course.png" alt="" width="60">
    </div>
    <div class="stats-text">
    <strong class="box-count"><?php echo $total_courses; ?></strong><span>Courses</span></div>
    </div>
    <div class="div-count-box event-total">
    <div>
    <img src="/wp-content/plugins/108-dashboard//assets/event.png" alt="" width="60">
    </div>
    <div class="stats-text">
    <strong class="box-count"><?php echo count($events); ?></strong><span>Events</span></div>
    </div>
    <div class="div-count-box certificates-total">
        <div>
          <img src="/wp-content/plugins/108-dashboard//assets/certificate.png" alt="" width="60">
        </div>
        <div class="stats-text">
        <strong class="box-count">0</strong><span>Certificates</span></div>
    </div>
    <div class="div-count-box support-total">
        <div>
        <img src="/wp-content/plugins/108-dashboard//assets/support.png" alt="" width="60">
        </div>
        <div class="stats-text">
        <strong class="box-count">0</strong><span>Support</span></div>
    </div>
</div> 

  </section>


  <!-- MIDDLE ROW -->
  <section class="md-grid md-grid-2">

    <!-- COURSES -->
        <div class="md-card">
  <h3>Enrolled Courses</h3>

  <?php if ( empty( $courses ) ) : ?>

    <p class="md-muted">You are not enrolled in any courses yet.</p>

  <?php else : ?>
<div class="course-wrap">

<?php foreach ( $courses as $course_id ) : 

    $user_id = get_current_user_id();

    $title = get_the_title($course_id);
    $link  = get_permalink($course_id);

    $progress_data = learndash_course_progress([
        'user_id'   => $user_id,
        'course_id' => $course_id
    ]);

    $progress = !empty($progress_data['percentage']) ? $progress_data['percentage'] : 0;
?>

  <div class="md-course-card">

      <div class="course-header">
          <h4><?php echo esc_html($title); ?></h4>
          <span class="course-percent"><?php echo $progress; ?>%</span>
      </div>

      <div class="md-progress">
          <span style="width: <?php echo esc_attr($progress); ?>%"></span>
      </div>

      <div class="course-footer">
          <a href="<?php echo esc_url($link); ?>" class="course-btn">
              <?php echo $progress >= 100 ? 'View Course' : 'Continue Learning'; ?>
          </a>
      </div>

  </div>

<?php endforeach; ?>

</div>
  <?php endif; ?>
</div>


    <!-- REGISTERED EVENTS -->
<div class="md-card">
  <h3>Registered Events</h3>

  <?php if ( empty( $events ) ) : ?>

    <p class="md-muted">You have not registered for any events yet.</p>

  <?php else : ?>

    <?php foreach ( $events as $event ) : ?>

      <div class="md-event md-event-<?php echo esc_attr( $event['status'] ); ?>">
        <strong><?php echo esc_html( $event['title'] ); ?></strong>

        <?php if ( $event['start_date'] && $event['end_date'] ) : ?>
          <p>
            <?php echo esc_html( $event['start_date'] ); ?>
            –
            <?php echo esc_html( $event['end_date'] ); ?>
          </p>
        <?php endif; ?>

        <?php if ( $event['status'] === 'upcoming' ) : ?>
          <span class="md-badge">Upcoming</span>
        <?php elseif ( $event['status'] === 'ongoing' ) : ?>
          <span class="md-badge">Ongoing</span>
        <?php else : ?>
          <span class="md-badge md-badge-expired">Expired</span>
        <?php endif; ?>

        <a href="<?php echo esc_url( $event['permalink'] ); ?>">
          View Event
        </a>
      </div>

    <?php endforeach; ?>

  <?php endif; ?>
</div>


  </section>

  <!-- BOTTOM ROW -->
  <section class="md-grid md-grid-1">

    <!-- ANNOUNCEMENTS (FULL WIDTH) -->
    <!-- <div class="md-card">
      <h3>Announcements</h3>

      <div class="md-alert">
        📣 <strong>New course launched:</strong> Advanced WordPress
      </div>

      <div class="md-alert warning">
        ⚠️ <strong>Maintenance:</strong> Site down on May 5th
      </div>
    </div> -->

  </section>




<?php 
    return ob_get_clean();
}




function md_get_membership_data( $user_id ) {

    $type       = get_user_meta( $user_id, 'membership_type', true );
    $start_date = get_user_meta( $user_id, 'membership_start_date', true );
    $end_date   = get_user_meta( $user_id, 'membership_end_date', true );

    // NON MEMBER
    if ( empty( $start_date ) || empty( $end_date ) ) {
        return array(
            'status'     => 'non_member',
            'label'      => 'Non Member',
            'plan_name'  => 'Non Member',
            'days_left'  => 0,
            'start_date' => null,
            'end_date'   => null,
        );
    }

    $start_ts = strtotime( $start_date );
    $end_ts   = strtotime( $end_date );
    $now_ts   = current_time( 'timestamp' );

    $days_left = floor( ( $end_ts - $now_ts ) / DAY_IN_SECONDS );

    // EXPIRED
    if ( $days_left < 0 ) {
        return array(
            'status'     => 'expired',
            'label'      => sprintf( '%s (Expired)', $type ),
            'plan_name'  => $type,
            'days_left'  => 0,
            'start_date' => date( 'd M Y', $start_ts ),
            'end_date'   => date( 'd M Y', $end_ts ),
        );
    }

    // EXPIRING SOON
    if ( $days_left <= 30 ) {
        return array(
            'status'     => 'expiring',
            'label'      => sprintf( '%s (Expiring Soon)', $type ),
            'plan_name'  => $type,
            'days_left'  => $days_left,
            'start_date' => date( 'd M Y', $start_ts ),
            'end_date'   => date( 'd M Y', $end_ts ),
        );
    }

    // ACTIVE
    return array(
        'status'     => 'active',
        'label'      => $type,
        'plan_name'  => $type,
        'days_left'  => $days_left,
        'start_date' => date( 'd M Y', $start_ts ),
        'end_date'   => date( 'd M Y', $end_ts ),
    );
}



function md_get_learndash_enrolled_courses( $user_id ) {

    if ( ! md_is_learndash_active() ) {
        return array();
    }

    // Get enrolled course IDs
    $course_ids = learndash_user_get_enrolled_courses( $user_id );

    if ( empty( $course_ids ) || ! is_array( $course_ids ) ) {
        return array();
    }

    $courses = array();

    foreach ( $course_ids as $course_id ) {

        $course = get_post( $course_id );
        if ( ! $course || $course->post_status !== 'publish' ) {
            continue;
        }

        // LearnDash progress
        $progress = learndash_course_progress( array(
            'user_id'   => $user_id,
            'course_id'=> $course_id
        ) );

        $percentage = isset( $progress['percentage'] )
            ? intval( $progress['percentage'] )
            : 0;

        $courses[] = array(
            'id'         => $course_id,
            'title'      => get_the_title( $course_id ),
            'permalink'  => get_permalink( $course_id ),
            'progress'   => $percentage,
        );
    }

    return $courses;
}

function md_is_learndash_active() {
    return function_exists( 'learndash_user_get_enrolled_courses' );
}


function md_get_registered_events_from_orders( $user_id ) {

    if ( ! function_exists( 'wc_get_orders' ) ) {
        return array();
    }

    $orders = wc_get_orders( array(
        'customer_id' => $user_id,
        'status'      => array( 'completed', 'processing' ),
        'limit'       => -1,
    ) );

    if ( empty( $orders ) ) {
        return array();
    }

    $events = array();
    $now_ts = current_time( 'timestamp' );

    foreach ( $orders as $order ) {

        foreach ( $order->get_items() as $item ) {

            $product = $item->get_product();
            if ( ! $product ) {
                continue;
            }

            $product_id = $product->get_id();

            // Check if product is EVENT type
            $record_type = get_post_meta( $product_id, 'sf_record_type_id', true );
            if ( $record_type !== '012au000000WRbQAAW' ) {
                continue;
            }

            // Event date meta
            $start_date = get_post_meta( $product_id, 'membership_start_date', true );
            $end_date   = get_post_meta( $product_id, 'membership_end_date', true );

            $start_ts = $start_date ? strtotime( $start_date ) : null;
            $end_ts   = $end_date ? strtotime( $end_date ) : null;

            // Determine event status
            if ( $end_ts && $end_ts < $now_ts ) {
                $status = 'expired';
            } elseif ( $start_ts && $start_ts > $now_ts ) {
                $status = 'upcoming';
            } else {
                $status = 'ongoing';
            }

            $events[ $product_id ] = array(
                'product_id' => $product_id,
                'title'      => $product->get_name(),
                'start_date' => $start_date ? date( 'd M Y', $start_ts ) : '',
                'end_date'   => $end_date ? date( 'd M Y', $end_ts ) : '',
                'status'     => $status,
                'order_id'   => $order->get_id(),
                'permalink'  => get_permalink( $product_id ),
            );
        }
    }

    return array_values( $events ); // remove duplicates
}





function dashboard_108_inline_js() {
    return <<<JS
jQuery(document).ready(function ($) {

  $('.md-route').on('click', function (e) {
    e.preventDefault();

    const pageId = $(this).data('pageid');

    $('.md-route').removeClass('active');
    $(this).addClass('active');

    // Hide everything first
    $('#md-default-dashboard').hide();
    $('#md-my-courses').hide();
    $('#md-ce-credits').hide();
    $('#md-dynamic-content').hide().empty();
    $('#md-loader').hide();

    /* ===== DASHBOARD ===== */
    if (pageId === 'dashboard') {
      $('#md-default-dashboard').show();
      return;
    }

    /* ===== MY COURSES ===== */
    if (pageId === 'my-courses') {
      $('#md-my-courses').show();
      return;
    }

    if (pageId === 'ce-credits') {
    $('#md-ce-credits').show();
    return;
    }

    /* ===== AJAX ROUTES ===== */
    if (!pageId || isNaN(pageId)) return;

    $('#md-loader').show();

    $.ajax({
      url: mdAjax.ajaxurl,
      type: 'POST',
      data: {
        action: 'md_load_page',
        nonce: mdAjax.nonce,
        page_id: pageId
      },
      success: function (response) {
        $('#md-loader').hide();
        $('#md-dynamic-content').html(response).fadeIn(200);
      },
      error: function () {
        $('#md-loader').hide();
        $('#md-dynamic-content').html('<p>Error loading content.</p>').fadeIn(200);
      }
    });

  });

});

JS;
}









add_shortcode('sf_ce_credits', 'sf_ce_credits_cb');

function sf_ce_credits_cb() {

    if ( ! is_user_logged_in() ) {
        return '<p>You must be logged in to view your CE credits.</p>';
    }

    ob_start();

    $user  = wp_get_current_user();
    $sf_id = get_user_meta( $user->ID, 'sf_object_id', true );

    if ( empty( $sf_id ) ) {
        ?>
        <div class="md-card">
            <h3>My CE Credits</h3>
            <p class="md-muted">CE credits are not available for your account.</p>
        </div>
        <?php
        return ob_get_clean();
    }

    // Salesforce query
    $query = "SELECT FIELDS(ALL) FROM IS_CE_Credit__c WHERE IS_Member__c = '".$sf_id."'";
    $responses = SF_APIConnector::getSQueryObject( $query, 50 );

    $approved = array();
    $pending  = array();
    $total_hours = 0;

    if ( ! empty( $responses )) {
        foreach ( $responses as $record ) {

            if ( empty( $record->IS_Credit_Status__c ) ) {
                continue;
            }

            if ( $record->IS_Credit_Status__c === 'Approved' ) {
                $approved[] = $record;
                $total_hours += (int) $record->IS_Credits_Earned__c;
            }

            if ( $record->IS_Credit_Status__c === 'Pending Approval' ) {
                $pending[] = $record;
            }
        }
    }
    ?>

    <!-- CURRENT CE CREDITS -->
    <div class="md-card md-ce-card">

        <div class="md-ce-header">
            <h3>Current CE Credits</h3>

       </div>
       <div class="ce_outer_container">
          <div class="ce_container">
        <?php if ( empty( $approved ) ) : ?>

            <p class="md-muted">No approved CE credits found.</p>

        <?php else : ?>

            <?php foreach ( $approved as $credit ) : ?>

                <div class="md-ce-row">
                    <strong><?php echo esc_html( $credit->IS_Credit_Source__c ); ?></strong>

                    <p>
                        Completed on <?php echo esc_html( $credit->IS_Earned_Date__c ); ?>
                        · <strong><?php echo esc_html( $credit->IS_Credits_Earned__c ); ?> Credits</strong>
                    </p>
                </div>

            <?php endforeach; ?>

        <?php endif; ?>
            </div>
            <!-- CE TOTAL RING -->
            <div class="md-ring md-ce-ring">
                <div class="md-ring-inner">
                    <strong><?php echo esc_html( $total_hours ); ?></strong>
                    <span>CE Credits</span>
                </div>
            </div>
            </div>
    </div>

    <!-- UNDER REVIEW CE CREDITS -->
    <div class="md-card" style="margin-top:24px;">

        <h3>Under Review CE Credits</h3>

        <?php if ( empty( $pending ) ) : ?>

            <p class="md-muted">No CE credits under review.</p>

        <?php else : ?>

            <?php foreach ( $pending as $credit ) : ?>

  <div class="md-ce-row md-ce-pending">
                    <strong><?php echo esc_html( $credit->IS_Credit_Source__c ); ?></strong>

                    <p>
                        Completed on <?php echo esc_html( $credit->IS_Earned_Date__c ); ?>
                        · <strong><?php echo esc_html( $credit->IS_Credits_Earned__c ); ?> Credits</strong>
                    </p>
                </div>

            <?php endforeach; ?>

        <?php endif; ?>

       

    </div>



        <!-- APPLY FOR CE CREDITS CTA -->
<div class="md-card md-ce-cta">

  <div class="md-ce-cta-inner">
    <h3>Apply for CE Credits</h3>

    <a href="/ce-credit-request/" class="cmn-btn">
      Apply Now
    </a>
  </div>

</div>   

    <?php
    return ob_get_clean();
}



//




add_action('wp_ajax_fetch_sf_courses_details', 'fetch_sf_courses_details');
add_action('wp_ajax_nopriv_fetch_sf_courses_details', 'fetch_sf_courses_details');

function fetch_sf_courses_details() {


    $sf_account_id = sanitize_text_field( $_POST['sf_account_id'] ?? '' );

    if ( empty( $sf_account_id ) ) {
        wp_send_json_error([
            'message' => 'SF Account ID is empty'
        ]);
    }
            $query = "SELECT Id,IS_Course__c,IS_Course__r.Name FROM IS_My_Courses__c WHERE IS_Account__c = '".$sf_account_id."'";
            $responses = SF_APIConnector::getSQueryObject( $query, 1000 );  


    // Simulated response (replace with Salesforce logic)
    wp_send_json_success([
        'received_account_id' => $responses,
        'status'              => 'Account received successfully'
    ]);
}


add_action('wp_ajax_handle_sf_account', 'handle_sf_account');
add_action('wp_ajax_nopriv_handle_sf_account', 'handle_sf_account');

function handle_sf_account() {


    $sf_account_id = sanitize_text_field( $_POST['sf_account_id'] ?? '' );

    if ( empty( $sf_account_id ) ) {
        wp_send_json_error([
            'message' => 'SF Account ID is empty'
        ]);
    }

    // Simulated response (replace with Salesforce logic)
    wp_send_json_success([
        'received_account_id' => $sf_account_id,
        'status'              => 'Account received successfully'
    ]);
}















add_action( 'gform_enqueue_scripts_6', function () {

    // Ensure jQuery is available
    wp_enqueue_script( 'jquery' );

    $ajax_url = esc_url( admin_url( 'admin-ajax.php' ) );
    $nonce    = wp_create_nonce( 'gf_form_9_nonce' );

    $js = <<<JS
(function($){

    // Prevent duplicate bindings on GF re-render
    $(document).off('gform_post_render.sfCourses');

    $(document).on('gform_post_render.sfCourses', function(event, formId) {

 //       if (formId !== 9) {
 //           return;
 //       }

        var \$sfField = $('.sf_account_id input, .sf_account_id select, .sf_account_id textarea');

        if (!\$sfField.length) {
            console.warn('SF Account field not found');
            return;
        }

        var sfAccountId = \$sfField.val();

        console.log('SF Account ID:', sfAccountId);

        if (!sfAccountId) {
            console.warn('SF Account ID is empty or missing');
            return;
        }

        $.ajax({
            url: "{$ajax_url}",
            type: "POST",
            dataType: "json",
            data: {
                action: "fetch_sf_courses_details",
                nonce: "{$nonce}",
                sf_account_id: sfAccountId
            },
            success: function (response) {
                console.log("AJAX Response:", response);

                if (!response || !response.success || !response.data || !response.data.received_account_id) {
                    console.warn("No course data returned");
                    return;
                }

                var \$select = $(".select_enrolled_exams");

                if (!\$select.length) {
                    console.warn("Course selector not found");
                    return;
                }

                \$select.empty().append('<option value="">Select Course</option>');

                $.each(response.data.received_account_id, function (i, course) {
                    if (course.IS_Course__c && course.IS_Course__r && course.IS_Course__r.Name) {
                        \$select.append(
                            $('<option>', {
                                value: course.Id,
                                text: course.IS_Course__r.Name
                            })
                        );
                    }
                });
            },
            error: function (xhr) {
                console.error("AJAX Error:", xhr.responseText);
            }
        });

    });

})(jQuery);
JS;

    wp_add_inline_script( 'jquery', $js );
});




function fetch_108_complainant_dashboard(){

ob_start();


$user = wp_get_current_user();
$email = $user->user_email;
$phone = get_user_meta( $user->ID, 'phone', true );

if ( empty( $phone ) ) {
    $phone = get_user_meta( $user->ID, 'user_phone', true );
}

if ( empty( $phone ) ) {
    $phone = get_user_meta( $user->ID, 'billing_phone', true );
}
$phone = $phone ? $phone : '—';
$first_name = get_user_meta( $user->ID, 'first_name', true );
$last_name  = get_user_meta( $user->ID, 'last_name', true );
$full_name = trim( $first_name . ' ' . $last_name );

$membership_type = get_user_meta( $user->ID, 'membership_type', true );
$membership_label = ! empty( $membership_type ) ? $membership_type : 'Non Member';

?>


<div class="md-dashboard-wrapper">

  <!-- LEFT SIDEBAR -->
  <aside class="md-sidebar">

    <div class="md-profile">

<?php
$user_id = get_current_user_id(); // or pass specific user ID
$image = get_user_meta($user_id, 'user_picture', true);

// fallback placeholder
$placeholder = 'https://placehold.net/avatar.svg';

// decide which to use
$img_src = !empty($image) ? $image : $placeholder;
?>

<div class="md-avatar cs-av">
    <img src="<?php echo esc_attr($img_src); ?>" alt="User Avatar">
</div>



    <h2><?php echo esc_html( $full_name ); ?></h2>

<span class="md-badge">
  <?php echo esc_html( $membership_label ); ?>
</span>

<div class="md-contact">
  <p><?php echo esc_html( $email ); ?></p>

  <p><?php echo esc_html( $phone ); ?>
  </p>


</div>
    </div>

<nav class="md-nav">
  <a class="md-route active" data-pageid="dashboardc">
    🏠 My Complaints
  </a>

  <a class="md-route" data-pageid="PROFILE">
    👤 My Profile
  </a>

  <a class="md-logout" href="<?php echo wp_logout_url(); ?>">
    ⏻ Logout
  </a>
</nav>


    
  </aside>

  <!-- MAIN CONTENT -->
<main class="md-main">

  <!-- LOADER -->
  <div id="md-loader" style="display:none;">
    <div class="md-spinner"></div>
  </div>

  <!-- DASHBOARD -->
  <div id="md-default-dashboard">

<div class="wlcm-msg md-card" style="margin-bottom: 20px;">
<h2>Welcome <?php echo $full_name; ?></h2>
<p>This is your place to manage your Complaints and their Status</p>
</div>

    <?php echo do_shortcode('[dashboard_main_content_complainant]'); ?>
  </div>



  


  <!-- AJAX CONTENT -->
  <div id="md-dynamic-content" style="display:none;"></div>

</main>


</div>







<?php
return ob_get_clean();



}



add_shortcode('dashboard_main_content_complainant','dashboard_main_content_complainant');

function dashboard_main_content_complainant(){
$user = wp_get_current_user();
$user_id = $user->ID;
$sf_id = get_user_meta($user_id,'sf_object_id',true);
//
ob_start();

    $query = "SELECT FIELDS(ALL) FROM IS_Complaint__c WHERE IS_Complainant__c = '".$sf_id."'";
    $responses = SF_APIConnector::getSQueryObject( $query, 50 );

if ( ! empty( $responses ) && is_array( $responses ) ) {

    echo '<div class="md-card-wrapper">';

    foreach ( $responses as $row ) {

        $case_number = $row->Name ?? '';
        $subject     = $row->IS_Subject__c ?? '';
        $description = $row->IS_Description__c ?? '';
        $status      = $row->IS_Status__c ?? '';
        $priority    = $row->IS_Priority__c ?? '';
        $impact      = $row->IS_Impact__c ?? '';
        $method      = $row->IS_Reported_Method__c ?? '';
        $created     = ! empty( $row->CreatedDate )
            ? date( 'M d, Y', strtotime( $row->CreatedDate ) )
            : '';

        ?>

        <div class="md-card">

            <div class="md-card-header">
                <h3><?php echo esc_html( $case_number ); ?></h3>
                <span class="status <?php echo strtolower( esc_attr( $status ) ); ?>">
                    <?php echo esc_html( $status ); ?>
                </span>
            </div>

            <div class="md-card-body">

                <p><strong>Subject:</strong> <?php echo esc_html( $subject ); ?></p>

                <p><strong>Description:</strong><br>
                    <?php echo nl2br( esc_html( $description ) ); ?>
                </p>

                <p><strong>Priority:</strong> <?php echo esc_html( $priority ); ?></p>

                <p><strong>Impact:</strong> <?php echo esc_html( $impact ); ?></p>

                <p><strong>Reported Via:</strong> <?php echo esc_html( $method ); ?></p>

                <p><strong>Created:</strong> <?php echo esc_html( $created ); ?></p>

            </div>

        </div>

        <?php
    }

    echo '</div>';
      echo "<div class='md-card'><a href='/complaint'>New Complaint</a></div>";

} else {
    echo '<p>No complaints found.</p>';
}



return ob_get_clean();
}






























add_shortcode('108-member-manage', 'manage_member_panel_fromSF');

function manage_member_panel_fromSF() {

    ob_start();

    if ( ! is_user_logged_in() ) {
        return '<p>Please log in to continue.</p>';
    }

    $user_id = get_current_user_id();
    $current_user = wp_get_current_user();
    $current_email = $current_user->user_email;
    $user_sf = get_user_meta($user_id, 'sf_object_id', true);


    $query = "SELECT FIELDS(ALL) FROM IS_Affiliation__c WHERE IS_Account__c = '".$user_sf."' AND IS_Primary_Contact__c = true";

    $responses = SF_APIConnector::getSQueryObject($query, 1);


    if(empty($responses)){
      return '<p>No organisation found.</p>';
    }


// Check if user has a parent affiliation and also user is main account via soql 

//SELECT FIELDS(ALL) FROM IS_Affiliation__c WHERE IS_Account__c = '001au00000KEetKAAT' AND IS_Primary_Contact__c = true


        $parent_account_id   = $responses->IS_Parent_Account__c;
          


    $query = "SELECT FIELDS(ALL) FROM IS_Affiliation__c WHERE IS_Parent_Account__c = '" . esc_sql($parent_account_id) . "'";

    $responses = SF_APIConnector::getSQueryObject($query, 50);

    ?>

    <div id="member-panel-container">

        <h3>Organisation Member Roster</h3>


   <h3>Bulk Import</h3>
        <form id="csv-upload-form" enctype="multipart/form-data">
            <input type="file" name="member_csv" required>
            <button type="submit">Upload CSV</button>
            <a class="sample_csv" href="/wp-content/plugins/108-dashboard/assets/sample-csv.csv">Download Sample CSV</a>
            <a class="add_single_member">Add Member</a>
        </form>
        <div id="import-status" style="margin-top:20px; display:none;">
    <div class="spinner"></div>
    <div id="status-text">Starting...</div>
            </div>            

        <table class="member-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
           
                    <th>Role</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>

            <?php
            if ( ! empty($responses) ) {
                foreach ( $responses as $record ) {
               
                    $email = $record->IS_Email__c ?? '';
              
                    if ( strtolower($email) === strtolower($current_email) ) continue;

                    $wp_user = get_user_by('email', $email);
                    if (!$wp_user) continue;
                    
                    $wp_user_id = $wp_user->ID;

                    $name    = $wp_user->first_name . ' ' . $wp_user->last_name;
                    $phone   = get_user_meta($wp_user_id, 'billing_phone', true);

$roles = [];

if (!empty($record->IS_Owner__c)) {
    $roles[] = 'Owner';
}

if (!empty($record->IS_Primary_Contact__c)) {
    $roles[] = 'Primary';
}

if (!empty($record->IS_Billing_Contact__c)) {
    $roles[] = 'Billing';
}

// Fallback
if (empty($roles)) {
    $roles[] = 'Member';
}
              ?>

                    <tr data-userid="<?php echo $wp_user_id; ?>">
                        <td class="member-name"><?php echo esc_html($name); ?></td>
                        <td class="member-email"><?php echo esc_html($email); ?></td>
                   

                       <td>
    <?php foreach ($roles as $role): ?>
        <span class="member_role <?php echo strtolower($role); ?>">
            <?php echo esc_html($role); ?>
        </span>
    <?php endforeach; ?>
</td>
                        <td>
<a href="#" 
   class="edit-member action-btn edit-btn"
   data-userid="<?php echo $wp_user_id; ?>"
   data-name="<?php echo esc_attr($name); ?>"
   data-email="<?php echo esc_attr($email); ?>"
   data-phone="<?php echo esc_attr($phone); ?>"
   data-address="<?php echo esc_attr($address); ?>">
   Edit
</a>
&nbsp;
<a href="#" 
   class="delete-member action-btn delete-btn"
   data-userid="<?php echo $wp_user_id; ?>">
   Delete
</a>
                        </td>
                    </tr>

                    <?php
                }
            }
            ?>

            </tbody>
        </table>

        <!-- MODAL -->

        <div id="deleteModal" class="member-modal">
  <div class="modal-content" style="max-width:400px; text-align:center;">

    <h3 style="margin-bottom:10px;">Delete Account?</h3>
    <p style="font-size:14px; color:#64748b;">
      Are you sure you want to delete this account?
    </p>

    <div style="display:flex; gap:10px; margin-top:20px;">
      <button id="confirm-delete" class="cmn-btn primary" style="flex:1;">
        Yes, Delete
      </button>

      <button id="cancel-delete" style="
        flex:1;
        border-radius:100px;
        border:1px solid #e2e8f0;
        background:#fff;
        cursor:pointer;
      ">
        No
      </button>
    </div>

  </div>
</div>



<div id="addMemberModal" class="member-modal">
  <div class="modal-content">

    <span class="close-modal">&times;</span>

    <h3>Add New Member</h3>

    <input type="text" id="new-first-name" placeholder="First Name">
    <input type="text" id="new-last-name" placeholder="Last Name">
    <input type="email" id="new-email" placeholder="Email">

    <?php echo do_shortcode('[phone_input id="new-phone" name="new_phone"]'); ?>

    <button type="button" id="create-member-btn">
      <span class="btn-text">Save Member</span>
      <span class="btn-loader"></span>
    </button>

  </div>
</div>


<!-- EXISTING USER POPUP -->
<div id="existingUserModal" class="member-modal">
  <div class="modal-content" style="text-align:center;">
    <h3>User Already Exists</h3>
    <p>This user is already registered with another organization.</p>
    <button id="existing-ok">OK</button>
  </div>
</div>










        <div id="editModal" class="member-modal" style="display:none;">
            <div class="modal-content">
                <span class="close-modal">&times;</span>

                <input type="hidden" id="edit-userid">

<input type="text" id="edit-first-name">
<input type="text" id="edit-last-name">



                <input type="text" id="edit-email" readonly>
                <?php echo do_shortcode('[phone_input id="edit-phone" name="user_phone" class="custom-phone-input form-control my-phone" placeholder="Your phone"]'); ?>
                <input type="text" id="edit-address" placeholder="Address">

                <button id="save-member">
  <span class="btn-text">Save</span>
  <span class="btn-loader"></span>
</button>
            </div>
        </div>

     
</div>

    </div>

    <?php

    return ob_get_clean();
}




add_action('wp_ajax_update_member', 'update_member_callback');

function update_member_callback() {


    $user_id = intval($_POST['user_id']);

    if (!$user_id) {
        wp_send_json_error('Invalid user');
    }



    // ============================
    // 🔥 GET DATA
    // ============================
    $full_name = sanitize_text_field($_POST['name']);
    $email     = sanitize_email($_POST['email']);
    $phone     = sanitize_text_field($_POST['phone']);
    $address   = sanitize_text_field($_POST['address']);

    // ============================
    // 🔥 SPLIT NAME AGAIN (BACKEND SAFE)
    // ============================
    $name_parts = explode(' ', $full_name, 2);

    $first_name = $name_parts[0];
    $last_name  = isset($name_parts[1]) ? $name_parts[1] : '';

    // ============================
    // 🔥 UPDATE USER CORE
    // ============================
$userdata = [
    'ID'           => $user_id,
    'display_name' => $full_name,
    'first_name'   => $first_name,
    'last_name'    => $last_name
];

// ✅ ONLY update email if provided
if (!empty($email)) {
    $userdata['user_email'] = $email;
}

wp_update_user($userdata);

    // ============================
    // 🔥 UPDATE META
    // ============================
    update_user_meta($user_id, 'first_name', $first_name);
    update_user_meta($user_id, 'last_name', $last_name);
    update_user_meta($user_id, 'phone', $phone);
    update_user_meta($user_id, 'address', $address);

    wp_send_json_success('User updated');
}

add_action('wp_ajax_upload_members_csv', 'upload_members_csv_callback');

function upload_members_csv_callback() {

    if ( empty($_FILES['member_csv']['tmp_name']) ) {
        wp_send_json_error('No file');
    }

    $user_id = get_current_user_id();
    $parent_account_id = get_user_meta($user_id, 'organization', true);

    $handle = fopen($_FILES['member_csv']['tmp_name'], 'r');

    $created = 0;
    $skipped = 0;
    $row = 0;

    while (($data = fgetcsv($handle, 1000, ",")) !== false) {

        $row++;
        if ($row == 1) continue;

        $name  = sanitize_text_field($data[0] ?? '');
        $email = sanitize_email($data[1] ?? '');

        if (!$email) continue;

        if (email_exists($email)) {
            $skipped++;
            continue;
        }

        $username = sanitize_user(strtolower(str_replace(' ', '', $name)));
        if (!$username) $username = 'user';

        $base = $username;
        $i = 2;

        while (username_exists($username)) {
            $username = $base . $i++;
        }

        $password = wp_generate_password();
        $new_user = wp_create_user($username, $password, $email);

        if (!is_wp_error($new_user)) {

        // Split name into parts
        $name_parts = preg_split('/\s+/', trim($name));

// First name = first word
$first_name = $name_parts[0] ?? '';

// Last name = everything else
$last_name = '';

if (count($name_parts) > 1) {
    array_shift($name_parts);
    $last_name = implode(' ', $name_parts);
}        

                wp_update_user([
    'ID'         => $new_user,
    'first_name' => $first_name,
    'last_name'  => $last_name,

               
    ]);
   
            update_user_meta($new_user, 'organization', $parent_account_id);

            $created++;
        }
    }

    fclose($handle);

    wp_send_json_success([
        'created' => $created,
        'skipped' => $skipped
    ]);
}


add_action('wp_ajax_load_member_panel', function() {
    echo do_shortcode('[108-member-manage]');
    wp_die();
});

wp_localize_script('your-script', 'ajaxurl', admin_url('admin-ajax.php'));





add_action('wp_ajax_process_member_row', 'process_member_row');

function process_member_row() {

    $name  = sanitize_text_field($_POST['name']);
    $email = sanitize_email($_POST['email']);
    $current_user_id   = get_current_user_id();
    $parent_account_id = get_user_meta($current_user_id, 'organization', true);
    $parent_membership_start = get_user_meta($current_user_id, 'membership_start_date', true);
    $parent_membership_end = get_user_meta($current_user_id, 'membership_end_date', true);
    $parent_membership_type = get_user_meta($current_user_id, 'membership_type', true);
    $parent_membership_status = get_user_meta($current_user_id, 'membership_status', true);

    if (!$email) {
        wp_send_json_error('Invalid email');
    }

    $steps = [];

    // Step 1
    $steps[] = "🔍 Checking account...";

    if (email_exists($email)) {
        $steps[] = "⚠️ Already exists, skipping";
        wp_send_json_success($steps);
    }

    // Step 2
    $steps[] = "👤 Creating WordPress user...";

    $username = sanitize_user(strtolower(str_replace(' ', '', $name)));
    if (!$username) $username = 'user';

    $base = $username;
    $i = 2;

    while (username_exists($username)) {
        $username = $base . $i++;
    }

    $password = wp_generate_password();
    $user_id = wp_create_user($username, $password, $email);


    if (is_wp_error($user_id)) {
        wp_send_json_error('User creation failed');
    }


$name = trim(preg_replace('/\s+/', ' ', $name));

// Split name
$name_parts = explode(' ', $name);

$first_name = $name_parts[0] ?? '';
$last_name  = '';

if (count($name_parts) > 1) {
    array_shift($name_parts);
    $last_name = implode(' ', $name_parts);
}

    wp_update_user([
        'ID'         => $user_id,
        'first_name' => $first_name,
        'last_name'  => $last_name,
        'role'       => 'subscriber'
    ]);

update_user_meta($user_id, 'organisation', $parent_account_id);
update_user_meta($user_id, 'membership_type', $parent_membership_type);
update_user_meta($user_id, 'membership_start_date', $parent_membership_start);
update_user_meta($user_id, 'membership_end_date', $parent_membership_end);
update_user_meta($user_id, 'membership_status', $parent_membership_status);

$obj = new SF_108Connector_AdminSettings();
$result = $obj->sf_export_user_sync([$user_id], 'account', true);




    // Step 3
    $steps[] = "☁️ Syncing with Salesforce...";



    // Step 4
    $steps[] = "🔗 Creating affiliation...";

             $records[] = [
            'attributes' => [ 'type' => 'IS_Affiliation__c' ],
            'IS_Parent_Account__c'    => $parent_account_id,
            'IS_Account__c'     => $result,
            'RecordTypeId' => '012au000000WQfKAAW',

        ];

       $response = SF_APIConnector::postCURLObject(json_encode(['allOrNone' => false,'records'   => $records,]),'POST');
           


    // Step 5
    $steps[] = "✅ Done";

    wp_send_json_success($steps);
}




add_action('woocommerce_cart_calculate_fees', 'apply_bulk_discount_based_on_quantity', 20, 1);

function apply_bulk_discount_based_on_quantity($cart) {
    if (is_admin() && !defined('DOING_AJAX')) return;

    $target_product_id = 123; // 🔁 Replace with your product ID
    $min_qty = 10;
    $discount_percent = 10;

    $total_qty = 0;
    $discountable_total = 0;

    foreach ($cart->get_cart() as $cart_item) {
        if ($cart_item['product_id'] == $target_product_id) {
            $total_qty += $cart_item['quantity'];
            $discountable_total += $cart_item['line_total'];
        }
    }

    // Apply discount if quantity condition is met
    if ($total_qty >= $min_qty) {
        $discount = ($discountable_total * $discount_percent) / 100;

        $cart->add_fee(
            __('Bulk Discount (10%)', 'woocommerce'),
            -$discount
        );
    }
}



add_action('wp_ajax_update_company_profile', 'update_company_profile');

function update_company_profile() {

    if ( ! is_user_logged_in() ) {
        wp_send_json_error('Not logged in');
    }

    $user_id = get_current_user_id();
    $company_id = get_user_meta($user_id, 'organization', true);

    if (empty($company_id)) {
        wp_send_json_error('Company not found');
    }

    // 🔹 Sanitize inputs
    $name        = sanitize_text_field($_POST['company_name'] ?? '');
    $phone       = sanitize_text_field($_POST['company_phone'] ?? '');
    $description = sanitize_textarea_field($_POST['company_description'] ?? '');
    $facebook    = esc_url_raw($_POST['company_facebook'] ?? '');
    $instagram   = esc_url_raw($_POST['company_instagram'] ?? '');
    $linkedin    = esc_url_raw($_POST['company_linkedin'] ?? '');

    $logo_url = '';

    // 🔹 Handle logo upload
    if (!empty($_FILES['company_logo']['name'])) {

        require_once(ABSPATH . 'wp-admin/includes/file.php');

        $upload = wp_handle_upload($_FILES['company_logo'], ['test_form' => false]);

        if (!isset($upload['error'])) {
            $logo_url = $upload['url'];
        } else {
            wp_send_json_error('Image upload failed');
        }
    }

    // 🔹 Build Salesforce payload
    $record = [
        'attributes' => ['type' => 'Account'],
        'Id'         => $company_id,
        'Name'       => $name,
        'Phone'      => $phone,
        'IS_Bio__c'  => $description,
        'IS_Facebook__c'  => $facebook,
        'IS_Instagram__c' => $instagram,
        'IS_LinkedIn__c'  => $linkedin,
    ];

    // Only send logo if uploaded
    if (!empty($logo_url)) {
        $record['IS_Photo__c'] = $logo_url;
    }

    // 🔹 Send to Salesforce
    $response = SF_APIConnector::postCURLObject(
        json_encode([
            'allOrNone' => true,
            'records'   => [$record]
        ]),
        'PATCH'
    );



    if (!$response) {
        wp_send_json_error('Salesforce update failed');
    }

    wp_send_json_success('Updated successfully');
}







add_shortcode('company_directory', 'render_company_directory');

function render_company_directory() {

    ob_start();

    // 🔹 Step 1: Get users with account_type = main
    $users = get_users([
        'meta_key'   => 'account_type',
        'meta_value' => 'main',
        'number'     => -1
    ]);


    if (empty($users)) {
        return '<p>No companies found.</p>';
    }

    // 🔹 Step 2: Collect organization IDs
    $account_ids = [];

    foreach ($users as $user) {
        $org_id = get_user_meta($user->ID, 'organization', true);

        if (!empty($org_id)) {
            $account_ids[] = "'" . esc_sql($org_id) . "'";
        }
    }

    if (empty($account_ids)) {
        return '<p>No company records found.</p>';
    }

    // 🔹 Step 3: Single Salesforce Query
    $ids_string = implode(',', $account_ids);

    $query = "
        SELECT Id, Name, Phone, IS_Bio__c, 
               IS_Facebook__c, IS_Instagram__c, 
               IS_LinkedIn__c, IS_Photo__c
        FROM Account
        WHERE Id IN ($ids_string)
    ";

    $records = SF_APIConnector::getSQueryObject($query, 200);

    if (empty($records)) {
        return '<p>No Salesforce data found.</p>';
    }

    ?>

    <div class="company-grid">

        <?php foreach ($records as $company): ?>

            <div class="company-card">

                <!-- LOGO -->
                <?php if (!empty($company->IS_Photo__c)): ?>
                    <div class="company-logo">
                        <img src="<?php echo esc_url($company->IS_Photo__c); ?>" alt="">
                    </div>
                <?php endif; ?>

                <!-- NAME -->
                <h3><?php echo esc_html($company->Name); ?></h3>

                <!-- PHONE -->
                <?php if (!empty($company->Phone)): ?>
                    <p><strong>📞</strong> <?php echo esc_html($company->Phone); ?></p>
                <?php endif; ?>

                <!-- DESCRIPTION -->
                <?php if (!empty($company->IS_Bio__c)): ?>
                    <p class="company-desc">
                        <?php echo esc_html($company->IS_Bio__c); ?>
                    </p>
                <?php endif; ?>

                <!-- SOCIAL LINKS -->
<div class="company-social">

    <?php if (!empty($company->IS_Facebook__c)): ?>
        <a href="<?php echo esc_url($company->IS_Facebook__c); ?>" target="_blank" class="social-icon facebook">
            <!-- Facebook SVG -->
            <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor">
                <path d="M22 12.07C22 6.48 17.52 2 11.93 2S2 6.48 2 12.07c0 5.02 3.66 9.18 8.44 9.93v-7.02H7.9v-2.91h2.54V9.41c0-2.5 1.5-3.88 3.77-3.88 1.09 0 2.23.2 2.23.2v2.46h-1.26c-1.24 0-1.63.77-1.63 1.56v1.87h2.77l-.44 2.91h-2.33V22c4.78-.75 8.44-4.91 8.44-9.93z"/>
            </svg>
        </a>
    <?php endif; ?>

    <?php if (!empty($company->IS_Instagram__c)): ?>
        <a href="<?php echo esc_url($company->IS_Instagram__c); ?>" target="_blank" class="social-icon instagram">
            <!-- Instagram SVG -->
            <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor">
                <path d="M7 2C4.24 2 2 4.24 2 7v10c0 2.76 2.24 5 5 5h10c2.76 0 5-2.24 5-5V7c0-2.76-2.24-5-5-5H7zm10 2c1.66 0 3 1.34 3 3v10c0 1.66-1.34 3-3 3H7c-1.66 0-3-1.34-3-3V7c0-1.66 1.34-3 3-3h10zm-5 3a5 5 0 100 10 5 5 0 000-10zm0 2.2a2.8 2.8 0 110 5.6 2.8 2.8 0 010-5.6zM17.8 6.2a1.2 1.2 0 100 2.4 1.2 1.2 0 000-2.4z"/>
            </svg>
        </a>
    <?php endif; ?>

    <?php if (!empty($company->IS_LinkedIn__c)): ?>
        <a href="<?php echo esc_url($company->IS_LinkedIn__c); ?>" target="_blank" class="social-icon linkedin">
            <!-- LinkedIn SVG -->
            <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor">
                <path d="M4.98 3.5C4.98 4.88 3.87 6 2.5 6S0 4.88 0 3.5 1.12 1 2.5 1 4.98 2.12 4.98 3.5zM0 8h5v16H0V8zm7.5 0h4.7v2.2h.1c.65-1.2 2.24-2.5 4.6-2.5 4.92 0 5.83 3.24 5.83 7.45V24h-5v-7.6c0-1.8-.03-4.12-2.5-4.12-2.5 0-2.88 1.95-2.88 3.98V24h-5V8z"/>
            </svg>
        </a>
    <?php endif; ?>

</div>

            </div>

        <?php endforeach; ?>

    </div>

    <?php

    return ob_get_clean();
}





add_action('wp_footer', function() {
?>

<div id="successModal" class="member-modal">
  <div class="modal-content" style="max-width:400px; text-align:center;">

    <h3 id="successMessage" style="margin-bottom:10px;">Success</h3>

    <div style="margin-top:20px;">
      <button id="success-ok" class="cmn-btn primary" style="width:100%;">
        OK
      </button>
    </div>

  </div>
</div>
<script>
jQuery(document).ready(function($){

    function initPhoneInputs() {

        if (typeof window.intlTelInput === "undefined") {
            console.log("intlTelInput not loaded yet...");
            return;
        }

        $(".custom-phone-input").each(function(){

            if ($(this).hasClass("iti-initialized")) return;

            const input = this;

            const iti = window.intlTelInput(input, {
                initialCountry: "in",
                separateDialCode: true,
                preferredCountries: ["in","us","gb"],
                utilsScript: "https://cdn.jsdelivr.net/npm/intl-tel-input@18.5.3/build/js/utils.js"
            });

            $(this).addClass("iti-initialized");

            // Only numbers
            input.addEventListener("input", function () {
                this.value = this.value.replace(/[^\d]/g, '');
            });

            // Hidden field for full number
            const hidden = document.createElement("input");
            hidden.type = "hidden";
            hidden.name = input.name + "_full";
            input.parentNode.appendChild(hidden);

            input.addEventListener("change", function () {
                hidden.value = iti.getNumber();
            });

        });
    }

    // Run multiple times (fix for AJAX / builders)
    setTimeout(initPhoneInputs, 300);
    setTimeout(initPhoneInputs, 1000);

});
</script>
<?php
});





function render_phone_input($args = []) {

    $defaults = [
        'id' => 'phone_' . rand(1000,9999),
        'name' => 'phone',
        'class' => '',
        'placeholder' => 'Enter phone number'
    ];

    $args = wp_parse_args($args, $defaults);

    ob_start(); ?>

    <input 
        type="tel"
        id="<?php echo esc_attr($args['id']); ?>"
        name="<?php echo esc_attr($args['name']); ?>"
        class="custom-phone-input <?php echo esc_attr($args['class']); ?>"
        placeholder="<?php echo esc_attr($args['placeholder']); ?>"
    >

    <?php
    return ob_get_clean();
}




add_action('wp_ajax_delete_member', 'delete_member_callback');

function delete_member_callback() {

    require_once ABSPATH . 'wp-admin/includes/user.php';

    if (!current_user_can('delete_users')) {
      //  wp_send_json_error('Permission denied');
    }

    if (empty($_POST['user_id'])) {
        wp_send_json_error('Missing user ID');
    }

    $user_id = intval($_POST['user_id']);

    if (!$user_id) {
        wp_send_json_error('Invalid user ID');
    }

    if ($user_id === get_current_user_id()) {
        wp_send_json_error('You cannot delete yourself');
    }

    $deleted = wp_delete_user($user_id);

    if ($deleted) {
        wp_send_json_success('User deleted');
    } else {
        wp_send_json_error('Delete failed');
    }

    wp_die(); // 🔥 VERY IMPORTANT
}


function send_to_salesforce_delete($user_id) {

    // TODO: Your Salesforce API call here

    return [
        'success' => true
    ];
}



add_action('wp_ajax_md_load_profile', 'md_load_profile');

function md_load_profile() {

    $user_id = get_current_user_id();
    $user = get_userdata($user_id);

    // PERSONAL
    $first_name = get_user_meta($user_id, 'first_name', true);
    $last_name  = get_user_meta($user_id, 'last_name', true);

    // BILLING
    $billing_address  = get_user_meta($user_id, 'billing_address_1', true);
    $billing_address2 = get_user_meta($user_id, 'billing_address_2', true);
    $billing_city     = get_user_meta($user_id, 'billing_city', true);
    $billing_state    = get_user_meta($user_id, 'billing_state', true);
    $billing_postcode = get_user_meta($user_id, 'billing_postcode', true);
    $billing_country  = get_user_meta($user_id, 'billing_country', true);

    // SHIPPING
    $shipping_address  = get_user_meta($user_id, 'shipping_address_1', true);
    $shipping_address2 = get_user_meta($user_id, 'shipping_address_2', true);
    $shipping_city     = get_user_meta($user_id, 'shipping_city', true);
    $shipping_state    = get_user_meta($user_id, 'shipping_state', true);
    $shipping_postcode = get_user_meta($user_id, 'shipping_postcode', true);
    $shipping_country  = get_user_meta($user_id, 'shipping_country', true);

    // IMAGE
    $image = get_user_meta($user_id, 'user_picture', true);
    ?>

    <form id="my-profile-form">

        <div class="profile-accordion">

            <!-- PERSONAL -->
            <div class="acc-item">
                <div class="acc-header pd_section">Personal Details</div>
                <div class="acc-content">

                    <div class="form-row">
                        <div class="form-group half">
                            <label>First Name</label>
                            <input type="text" name="first_name" value="<?php echo esc_attr($first_name); ?>">
                        </div>

                        <div class="form-group half">
                            <label>Last Name</label>
                            <input type="text" name="last_name" value="<?php echo esc_attr($last_name); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Email (cannot change)</label>
                        <input type="email" value="<?php echo esc_attr($user->user_email); ?>" disabled>
                    </div>

                    <div class="form-group">
    <label>Phone</label>
    <input type="text" name="phone" value="<?php echo esc_attr(get_user_meta($user_id, 'billing_phone', true)); ?>">
</div>

                    <div class="form-group">
                        <label>Profile Image</label>

                        <img id="profile-preview" src="<?php echo $image ? $image : 'https://placehold.net/avatar.svg'; ?>" height="80">
                        
                        <input type="file" id="profile-image" accept="image/*">
                    </div>

                </div>
            </div>

            <!-- BILLING -->
            <div class="acc-item">
                <div class="acc-header bill_item">Billing Address</div>
                <div class="acc-content">

                    <div class="form-group">
                        <label>Address Line 1</label>
                        <input type="text" name="billing_address" value="<?php echo esc_attr($billing_address); ?>">
                    </div>

                    <div class="form-group">
                        <label>Address Line 2</label>
                        <input type="text" name="billing_address_2" value="<?php echo esc_attr($billing_address2); ?>">
                    </div>

                    <div class="form-row">
                        <div class="form-group half">
                            <label>City</label>
                            <input type="text" name="billing_city" value="<?php echo esc_attr($billing_city); ?>">
                        </div>

                        <div class="form-group half">
                            <label>State</label>
                            <input type="text" name="billing_state" value="<?php echo esc_attr($billing_state); ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group half">
                            <label>Postcode</label>
                            <input type="text" name="billing_postcode" value="<?php echo esc_attr($billing_postcode); ?>">
                        </div>

                        <div class="form-group half">
                            <label>Country</label>
                            <input type="text" name="billing_country" value="<?php echo esc_attr($billing_country); ?>">
                        </div>
                    </div>

                </div>
            </div>

            <!-- SHIPPING -->
            <div class="acc-item">
                <div class="acc-header ship_item">Shipping Address</div>
                <div class="acc-content">

                    <div class="form-group">
                        <label>Address Line 1</label>
                        <input type="text" name="shipping_address" value="<?php echo esc_attr($shipping_address); ?>">
                    </div>

                    <div class="form-group">
                        <label>Address Line 2</label>
                        <input type="text" name="shipping_address_2" value="<?php echo esc_attr($shipping_address2); ?>">
                    </div>

                    <div class="form-row">
                        <div class="form-group half">
                            <label>City</label>
                            <input type="text" name="shipping_city" value="<?php echo esc_attr($shipping_city); ?>">
                        </div>

                        <div class="form-group half">
                            <label>State</label>
                            <input type="text" name="shipping_state" value="<?php echo esc_attr($shipping_state); ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group half">
                            <label>Postcode</label>
                            <input type="text" name="shipping_postcode" value="<?php echo esc_attr($shipping_postcode); ?>">
                        </div>

                        <div class="form-group half">
                            <label>Country</label>
                            <input type="text" name="shipping_country" value="<?php echo esc_attr($shipping_country); ?>">
                        </div>
                    </div>

                </div>
            </div>

            <!-- PASSWORD -->
            <div class="acc-item">
                <div class="acc-header password_change">Change Password</div>
                <div class="acc-content">

                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" id="new-password" name="new_password">
                    </div>

                    <div class="password-strength">
                        <div class="strength-bar"></div>
                        <span class="strength-text"></span>
                    </div>

                </div>
            </div>

        </div>

        <button type="submit" class="cmn-btn primary">Save Profile</button>

    </form>

    <?php
    wp_die();
}


add_action('wp_ajax_save_my_profile', 'save_my_profile');

function save_my_profile() {

    $user_id = get_current_user_id();

    if (!$user_id) {
        wp_send_json_error('Not logged in');
    }

    // =========================
    // PERSONAL
    // =========================
    $first_name = sanitize_text_field($_POST['first_name'] ?? '');
    $last_name  = sanitize_text_field($_POST['last_name'] ?? '');

    wp_update_user([
        'ID' => $user_id,
        'first_name'   => $first_name,
        'last_name'    => $last_name,
        'display_name' => trim($first_name . ' ' . $last_name)
    ]);
  update_user_meta($user_id, 'billing_phone', sanitize_text_field($_POST['phone'] ?? ''));
    // =========================
    // BILLING
    // =========================
    update_user_meta($user_id, 'billing_address_1', sanitize_text_field($_POST['billing_address'] ?? ''));
    update_user_meta($user_id, 'billing_address_2', sanitize_text_field($_POST['billing_address_2'] ?? ''));
    update_user_meta($user_id, 'billing_city', sanitize_text_field($_POST['billing_city'] ?? ''));
    update_user_meta($user_id, 'billing_state', sanitize_text_field($_POST['billing_state'] ?? ''));
    update_user_meta($user_id, 'billing_postcode', sanitize_text_field($_POST['billing_postcode'] ?? ''));
    update_user_meta($user_id, 'billing_country', sanitize_text_field($_POST['billing_country'] ?? ''));

    // =========================
    // SHIPPING
    // =========================
    update_user_meta($user_id, 'shipping_address_1', sanitize_text_field($_POST['shipping_address'] ?? ''));
    update_user_meta($user_id, 'shipping_address_2', sanitize_text_field($_POST['shipping_address_2'] ?? ''));
    update_user_meta($user_id, 'shipping_city', sanitize_text_field($_POST['shipping_city'] ?? ''));
    update_user_meta($user_id, 'shipping_state', sanitize_text_field($_POST['shipping_state'] ?? ''));
    update_user_meta($user_id, 'shipping_postcode', sanitize_text_field($_POST['shipping_postcode'] ?? ''));
    update_user_meta($user_id, 'shipping_country', sanitize_text_field($_POST['shipping_country'] ?? ''));

    // =========================
    // PASSWORD (OPTIONAL)
    // =========================
    if (!empty($_POST['new_password'])) {

        $password = $_POST['new_password'];

        // basic validation
        if (strlen($password) < 6) {
            wp_send_json_error('Password too short');
        }

        wp_set_password($password, $user_id);
    }

    // =========================
    // IMAGE (BASE64)
    // =========================
    if (!empty($_POST['user_image'])) {
        update_user_meta($user_id, 'user_picture', $_POST['user_image']);
    }

    wp_send_json_success([
        'message' => 'Profile updated successfully'
    ]);
}




add_action('wp_footer', 'md_render_logout_modal');

function md_render_logout_modal() {
    ?>
    
    <div id="logoutModal" class="md-modal">
        <div class="md-modal-content">

            <h3>Logout?</h3>
            <p>Do you really wish to logout?</p>

            <div class="md-modal-actions">
                <button id="confirm-logout" class="danger">Yes</button>
                <button id="cancel-logout">No</button>
            </div>

        </div>
    </div>

    <?php
}


add_action('wp_ajax_ajax_logout', 'ajax_logout');
add_action('wp_ajax_nopriv_ajax_logout', 'ajax_logout');

function ajax_logout() {

    wp_logout();

    wp_send_json_success();
}





add_action('wp_ajax_check_email_and_load_gf', 'check_email_and_load_gf');
add_action('wp_ajax_nopriv_check_email_and_load_gf', 'check_email_and_load_gf');

function check_email_and_load_gf() {

    $email = sanitize_email($_POST['email']);
    $domain = substr(strrchr($email, "@"), 1);
    $phone = sanitize_text_field($_POST['user_phone']);
    if (!$domain) {
        wp_send_json(['error' => 'Invalid email']);
    }

    // 🔥 Get all "main" account users
    $users = get_users([
        'meta_key'   => 'account_type',
        'meta_value' => 'main'
    ]);

    $matched_org = '';

    foreach ($users as $user) {

        $user_email = $user->user_email;
        $user_domain = substr(strrchr($user_email, "@"), 1);

        if ($user_domain === $domain) {

            $matched_org = get_user_meta($user->ID, 'organisation_name', true);
            break;
        }
    }

    // ✅ If domain matched
    if ($matched_org) {

        $html = '
            <div class="org-match-box">
                <p>Your domain is already registered with <strong>' . esc_html($matched_org) . '</strong></p>

                <label>
                    <input type="radio" name="org_choice" value="yes">
                    Yes, sign me up with ' . esc_html($matched_org) . '
                </label><br>

                <label>
                    <input type="radio" name="org_choice" value="no">
                    No, I need a separate registration
                </label>

                <button id="org-submit-btn">Continue</button>
            </div>
        ';

        wp_send_json([
            'exists' => false,
            'org_match' => true,
            'html' => $html,
            'org_name' => $matched_org
        ]);
    }

    // ❌ No match → show GF directly
    $map = get_option('gf_Member_Event_form_mapping', []);
    $form_id = (int) ($map['individual_membership_ispa'] ?? 0);

    $form_html = do_shortcode("[gravityform id='{$form_id}' ajax='true']");

    wp_send_json([
        'exists' => false,
        'org_match' => false,
        'form_html' => $form_html
    ]);
}


add_action('wp_ajax_handle_org_choice', 'handle_org_choice');
add_action('wp_ajax_nopriv_handle_org_choice', 'handle_org_choice');

function handle_org_choice() {

    $choice = sanitize_text_field($_POST['choice']);
    $email  = sanitize_email($_POST['email']);
    $org    = sanitize_text_field($_POST['org']);
$phone = sanitize_text_field($_POST['user_phone']);
$first_name = sanitize_text_field($_POST['first_name']);
$last_name = sanitize_text_field($_POST['last_name']);
    if ($choice === 'no') {

        $map = get_option('gf_Member_Event_form_mapping', []);
        $form_id = (int) ($map['individual_membership_ispa'] ?? 0);

        $form_html = do_shortcode("[gravityform id='{$form_id}' ajax='true']");

        wp_send_json([
            'show_form' => true,
            'form_html' => $form_html
        ]);
    }

    // ✅ YES → create subscriber + login

    // 🔥 Call your Salesforce function here
    // example:
    // create_salesforce_subscriber($email, $org);

    // Create WP user if not exists
    if (!email_exists($email)) {
        $matched_user_id = intval($_POST['matched_user_id'] ?? 0);
        $password = wp_generate_password();
       $parent_account_id = get_user_meta($matched_user_id, 'organization', true);                 
        $user_id = wp_create_user($email, $password, $email);
        update_user_meta($user_id, 'billing_phone', $phone);  
    
        wp_update_user([
            'ID' => $user_id,
            'first_name'   => $first_name,
        'last_name'    => $last_name,
        'display_name' => $first_name . ' ' . $last_name
        ]);
          $obj=new SF_108Connector_AdminSettings();
         $result = $obj->sf_export_user_sync([$user_id],true);   
          
          
        $records[] = [
            'attributes' => [ 'type' => 'IS_Affiliation__c' ],
            'IS_Parent_Account__c'    => '',
            'IS_Account__c'     => $result->id,
            'RecordTypeId' => '012au000000WQfKAAW',

        ];

       $response = SF_APIConnector::postCURLObject(json_encode(['allOrNone' => false,'records'   => $records,]),'POST');
                    



    } else {
        $user = get_user_by('email', $email);
        $user_id = $user->ID;
    }

    // Auto login
    wp_set_current_user($user_id);
    wp_set_auth_cookie($user_id);

    $html = '
        <div class="success-box">
            <p>You have been registered with <strong>' . esc_html($org) . '</strong></p>
            <a href="/dashboard" class="dashboard-btn">Visit Dashboard</a>
        </div>
    ';

    wp_send_json([
        'success' => true,
        'html' => $html
    ]);
}







function gf_fetch_registered_active_events($user_id){

    $sf_id = get_user_meta($user_id, 'sf_object_id', true);

    if (empty($sf_id)) {
        return '<p>No Salesforce ID found.</p>';
    }

    // 🔥 Fetch registrations
    $query = "SELECT Name, IS_Event__c 
              FROM IS_Registration__c 
              WHERE IS_Account__c = '".$sf_id."' 
              AND RecordTypeId = '012au000000WRLGAA4'";

    $registrations = SF_APIConnector::getSQueryObject($query, 50);

    // ✅ Safety check
    if (empty($registrations) || !is_array($registrations)) {
        return '<p>No events found.</p>';
    }

    ob_start();

    echo '<div class="md-events-grid">';

    foreach ($registrations as $reg) {

        if (empty($reg->IS_Event__c)) continue;

        // 🔥 Fetch event details
        $event = gf_fetch_event_details($reg->IS_Event__c);

        // ✅ Ensure object
        if (!$event || !is_object($event)) continue;

        // =========================
        // SAFE FIELD EXTRACTION
        // =========================
        $title   = $event->Name ?? 'Untitled Event';
        $city    = $event->IS_City__c ?? '';
        $country = $event->IS_Country__c ?? '';
        $desc    = $event->IS_Event_Description__c ?? '';
        $image   = $event->IS_Image_Thumbnail__c ?? '';
        $status  = strtolower($event->IS_Status__c ?? 'active');

        $start = !empty($event->IS_Start_date__c) 
            ? date('d M Y, h:i A', strtotime($event->IS_Start_date__c)) 
            : '';

        $end = !empty($event->IS_End_date__c) 
            ? date('d M Y, h:i A', strtotime($event->IS_End_date__c)) 
            : '';

        ?>

        <div class="md-event-card">

            <?php if ($image): ?>
                <div class="event-thumb">
                    <img src="<?php echo esc_url($image); ?>" />
                </div>
            <?php endif; ?>

            <div class="event-body">

                <h3><?php echo esc_html($title); ?></h3>

                <?php if ($city || $country): ?>
                    <p class="event-meta">
                        📍 <?php echo esc_html(trim($city . ', ' . $country, ', ')); ?>
                    </p>
                <?php endif; ?>

                <?php if ($start): ?>
                    <p class="event-meta">
                        🗓 <?php echo esc_html($start); ?>
                        <?php if ($end): ?> → <?php echo esc_html($end); ?><?php endif; ?>
                    </p>
                <?php endif; ?>

                <span class="event-status status-<?php echo esc_attr($status); ?>">
                    <?php echo esc_html(ucfirst($status)); ?>
                </span>

                <?php if ($desc): ?>
                    <p class="event-desc">
                        <?php echo esc_html(wp_trim_words($desc, 20)); ?>
                    </p>
                <?php endif; ?>

            </div>

        </div>

        <?php
    }

    echo '</div>';

    return ob_get_clean();
}



function gf_fetch_event_details($eventID){

$query = "SELECT FIELDS(ALL) FROM IS_Event__c WHERE Id = '".$eventID."'";

$responses = SF_APIConnector::getSQueryObject( $query,1);

return $responses;

}



























function custom_step_form_domain_check_shortcode() {

    ob_start();

    // STEP 1 SUBMIT
    if (isset($_POST['custom_step_submit']) && !isset($_POST['final_submit'])) {

        $email = sanitize_email($_POST['user_email']);
        $first = sanitize_text_field($_POST['first_name']);
        $last  = sanitize_text_field($_POST['last_name']);
        $phone = sanitize_text_field($_POST['user_phone']);

        $domain = substr(strrchr($email, "@"), 1);

        $users = get_users([
            'meta_key'   => 'account_type',
            'meta_value' => 'main'
        ]);

        $matched_org = '';
        $matched_user_id = 0;

        foreach ($users as $user) {
            $user_domain = substr(strrchr($user->user_email, "@"), 1);

            if ($user_domain === $domain) {
                $matched_org = get_user_meta($user->ID, 'organization_name', true);
                $matched_user_id = $user->ID;
                break;
            }
        }

        ?>

        <div class="org-step-2">

            <?php if ($matched_org): ?>

                <p>Your domain is already registered with <strong><?php echo esc_html($matched_org); ?></strong></p>

                <label><input type="radio" name="org_choice" value="yes"> Yes</label>
                <label><input type="radio" name="org_choice" value="no"> No</label>

            <?php else: ?>

                <p>We were unable to find your organization.</p>
                <input type="hidden" id="org_choice" value="no">

            <?php endif; ?>

            <div id="org_input_box" style="display:none;">
                <label>Add your Organization name</label>
                <input type="text" id="org_name_input">
            </div>

            <form method="post" id="final_form" style="display:none;">
                <input type="hidden" name="user_email" value="<?php echo esc_attr($email); ?>">
                <input type="hidden" name="first_name" value="<?php echo esc_attr($first); ?>">
                <input type="hidden" name="last_name" value="<?php echo esc_attr($last); ?>">
                <input type="hidden" name="user_phone" value="<?php echo esc_attr($phone); ?>">
                <input type="hidden" name="matched_user_id" value="<?php echo esc_attr($matched_user_id); ?>">

                <input type="hidden" name="org_choice" id="final_choice">
                <input type="hidden" name="new_org_name" id="final_org_name">
                <input type="hidden" name="final_submit" value="1">

                <button type="submit">Submit</button>
            </form>

        </div>

        <script>
        jQuery(function($){

            let matched = <?php echo $matched_org ? 'true' : 'false'; ?>;

            // If NOT FOUND → show input immediately
            if(!matched){
                $('#org_input_box').show();
            }

            $('input[name="org_choice"]').on('change', function(){

                let val = $(this).val();
                $('#final_choice').val(val);

                if(val === 'yes'){
                    $('#org_input_box').hide();
                    $('#final_form').show();
                }

                if(val === 'no'){
                    $('#org_input_box').show();
                    $('#final_form').hide();
                }
            });

            $('#org_name_input').on('input', function(){

                let val = $(this).val().trim();

                if(val){
                    $('#final_org_name').val(val);
                    $('#final_choice').val('no');
                    $('#final_form').show();
                } else {
                    $('#final_form').hide();
                }
            });

        });
        </script>

        <?php
        return ob_get_clean();
    }

    // FINAL SUBMIT
    if (isset($_POST['final_submit'])) {

        $email = sanitize_email($_POST['user_email']);
        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name  = sanitize_text_field($_POST['last_name']);
        $phone      = sanitize_text_field($_POST['user_phone']);
        $choice     = sanitize_text_field($_POST['org_choice']);
        $matched_user_id = intval($_POST['matched_user_id']);

        // ✅ YES → OLD LOGIC (UNCHANGED)
        if ($choice === 'yes') {

            $parent_account_id = get_user_meta($matched_user_id, 'organization', true);

            if (!email_exists($email)) {

                $password = wp_generate_password();
                $user_id = wp_create_user($email, $password, $email);

                update_user_meta($user_id, 'billing_phone', $phone);

                wp_update_user([
                    'ID' => $user_id,
                    'first_name' => $first_name,
                    'last_name'  => $last_name
                ]);

                $obj = new SF_108Connector_AdminSettings();
                $result = $obj->sf_export_user_sync([$user_id], true);

                $records[] = [
                    'attributes' => ['type' => 'IS_Affiliation__c'],
                    'IS_Parent_Account__c' => $parent_account_id,
                    'IS_Account__c' => $result,
                    'RecordTypeId' => '012au000000WQfKAAW',
                ];

                SF_APIConnector::postCURLObject(json_encode([
                    'allOrNone' => false,
                    'records'   => $records,
                ]), 'POST');

            } else {
                $user = get_user_by('email', $email);
                $user_id = $user->ID;
            }

            wp_set_current_user($user_id);
            wp_set_auth_cookie($user_id);

            echo '<div class="success-box">Registered successfully</div>';
            return ob_get_clean();
        }

        // ❌ NO → HANDLE NEW ORG NAME
        if ($choice === 'no') {

            $new_org = sanitize_text_field($_POST['new_org_name']);

            // 👉 HERE you can create org OR send to Salesforce
            // echo or debug:
            echo '<div class="success-box">';
            echo 'Organization submitted: ' . esc_html($new_org);
            echo '</div>';

            return ob_get_clean();
        }
    }

    // STEP 1 FORM
    ?>

    <form method="post">
        <input type="text" name="first_name" required placeholder="First Name">
        <input type="text" name="last_name" required placeholder="Last Name">
        <input type="email" name="user_email" required placeholder="Email">
        <input type="text" name="user_phone" required placeholder="Phone">
        <button type="submit" name="custom_step_submit">Next</button>
    </form>

    <?php

    return ob_get_clean();
}

add_shortcode('custom_step_form', 'custom_step_form_domain_check_shortcode');




add_action('wp_ajax_add_new_member', 'add_new_member_callback');

function add_new_member_callback() {

    if ( ! is_user_logged_in() ) {
        wp_send_json_error('Not logged in');
    }

    $current_user_id = get_current_user_id();
    $current_org     = get_user_meta($current_user_id, 'organization', true);

    $first_name = sanitize_text_field($_POST['first_name']);
    $last_name  = sanitize_text_field($_POST['last_name']);
    $email      = sanitize_email($_POST['email']);
    $phone      = sanitize_text_field($_POST['phone']);

    if ( empty($email) ) {
        wp_send_json_error('Email required');
    }

    // 🔍 CHECK IF USER EXISTS
    $user = get_user_by('email', $email);

    if ( $user ) {

        $existing_org = get_user_meta($user->ID, 'organization', true);

        // 🚫 Already in another org
        if ( !empty($existing_org) && $existing_org != $current_org ) {
            wp_send_json_success([
                'exists_other_org' => true
            ]);
        }

        // ✅ Same org → allow
        wp_send_json_success([
            'exists' => true,
            'user_id' => $user->ID
        ]);
    }

    // 🆕 CREATE USER
    $username = sanitize_user(current(explode('@', $email)));

    if ( username_exists($username) ) {
        $username .= '_' . wp_generate_password(4, false);
    }
                dbg("Inserting User");
    $user_id = wp_insert_user([
        'user_login' => $username,
        'user_email' => $email,
        'user_pass'  => wp_generate_password(12, true),
        'first_name' => $first_name,
        'last_name'  => $last_name,
        'role'       => 'subscriber',
    ]);
                dbg($user_id);
    if ( is_wp_error($user_id) ) {
        wp_send_json_error($user_id->get_error_message());
    }

    // ✅ SAVE META
    update_user_meta($user_id, 'billing_phone', $phone);
    update_user_meta($user_id, 'organization', $current_org);
    update_user_meta($user_id, 'account_type', 'member');

    $obj    = new SF_108Connector_AdminSettings();
    $result = $obj->sf_export_user_sync(array($user_id),true);            


    $records[] = [
    'attributes' => [ 'type' => 'IS_Affiliation__c' ],
    'IS_Parent_Account__c' => $current_org,
    'IS_Account__c' => $result, 
    'RecordTypeId' => '012au000000WQfKAAW',
    ];

    $response = SF_APIConnector::postCURLObject(json_encode(['allOrNone' => false,'records'   => $records,]),'POST');

    dbg($response);
wp_send_json_success([
    'created'   => true,
    'user_id'   => $user_id,
    'first_name'=> $first_name,
    'last_name' => $last_name,
    'email'     => $email,
    'phone'     => $phone
]);

}














function member_step_registration_shortcode() {

    ob_start();
    echo '<div class="member-step-wrapper">';
    // =========================
    // STEP 1 SUBMIT
    // =========================
    if (isset($_POST['step1_submit'])) {

        $email = sanitize_email($_POST['email']);
        $first = sanitize_text_field($_POST['first_name']);
        $last  = sanitize_text_field($_POST['last_name']);
        $phone = sanitize_text_field($_POST['phone']);

        $domain = substr(strrchr($email, "@"), 1);

        $users = get_users([
            'meta_key'   => 'account_type',
            'meta_value' => 'main'
        ]);

        $matched_org = '';
        $matched_org_id = '';

        foreach ($users as $user) {

            $user_domain = substr(strrchr($user->user_email, "@"), 1);

            if ($user_domain == $domain) {
                $matched_org = get_user_meta($user->ID, 'organisation_name', true);
       ;
                $matched_org_id = get_user_meta($user->ID, 'organization', true);
                break;
            }
        }
        ?>

        <div class="step-2">

            <?php if ($matched_org): ?>

                <p class="org-message success">
Your email domain has been successfully matched with the organization <strong><?php echo esc_html($matched_org); ?></strong>. By proceeding, your account will be registered under this organization and associated with its membership profile.  
</p>

                <form method="post">
                    <input type="hidden" name="email" value="<?php echo esc_attr($email); ?>">
                    <input type="hidden" name="first_name" value="<?php echo esc_attr($first); ?>">
                    <input type="hidden" name="last_name" value="<?php echo esc_attr($last); ?>">
                    <input type="hidden" name="phone" value="<?php echo esc_attr($phone); ?>">
                    <input type="hidden" name="org_id" value="<?php echo esc_attr($matched_org_id); ?>">
                    <input type="hidden" name="final_submit" value="1">
                    <button type="button" id="back_btn" class="btn-secondary">Back</button>
                    <button type="submit">Continue</button>
                    
                </form>

            <?php else: ?>

                <p class="org-message">We couldn’t find an organization associated with your email domain.  
Please use the search below to locate and select your organization from our directory.
</p>

                <input type="text" id="org_search_input" placeholder="Search organization">
                <button type="button" id="org_search_btn">Search</button>

                <div id="org_results"></div>

                <form method="post" id="final_form" style="display:none;">
                    <input type="hidden" name="email" value="<?php echo esc_attr($email); ?>">
                    <input type="hidden" name="first_name" value="<?php echo esc_attr($first); ?>">
                    <input type="hidden" name="last_name" value="<?php echo esc_attr($last); ?>">
                    <input type="hidden" name="phone" value="<?php echo esc_attr($phone); ?>">
                    <input type="hidden" name="org_id" id="selected_org_id">
                    <input type="hidden" name="final_submit" value="1">
                </form>

            <?php endif; ?>

        </div>

        <script>

    document.addEventListener('click', function(e){

    if(e.target.id === 'back_btn'){
        window.history.back();
    }

});
        document.addEventListener('click', function(e){

            // SEARCH
            if(e.target.id === 'org_search_btn'){

                let keyword = document.getElementById('org_search_input').value.trim();

                if(!keyword){
                    alert('Enter organization');
                    return;
                }

                document.getElementById('org_results').innerHTML = 'Searching...';

                let data = new FormData();
                data.append('action','search_salesforce_org');
                data.append('keyword',keyword);

                fetch('/wp-admin/admin-ajax.php',{
                    method:'POST',
                    body:data
                })
                .then(res => res.text())
                .then(html => {
                    document.getElementById('org_results').innerHTML = html;
                });
            }

            // SELECT ORG
            let item = e.target.closest('.org-result-item');
            if(item){

                document.getElementById('selected_org_id').value = item.dataset.id;
                document.getElementById('final_form').submit();
            }

        });
        </script>

        <?php
        return ob_get_clean();
    }

    // =========================
    // FINAL SUBMIT
    // =========================
    if (isset($_POST['final_submit'])) {

        $email = sanitize_email($_POST['email']);
        $first = sanitize_text_field($_POST['first_name']);
        $last  = sanitize_text_field($_POST['last_name']);
        $phone = sanitize_text_field($_POST['phone']);
        $org_id = sanitize_text_field($_POST['org_id']);

        if (!email_exists($email)) {

            $password = wp_generate_password();
            $user_id = wp_create_user($email, $password, $email);

            wp_update_user([
                'ID' => $user_id,
                'first_name' => $first,
                'last_name'  => $last
            ]);

            update_user_meta($user_id, 'billing_phone', $phone);

            // 🔥 SF SYNC
            $obj = new SF_108Connector_AdminSettings();
            $sf_account_id = $obj->sf_export_user_sync([$user_id], true);

            // 🔥 AFFILIATION
            $records[] = [
                'attributes' => ['type' => 'IS_Affiliation__c'],
                'IS_Parent_Account__c' => $org_id,
                'IS_Account__c' => $sf_account_id,
                'RecordTypeId' => '012au000000WQfKAAW',
            ];

            SF_APIConnector::postCURLObject(json_encode([
                'allOrNone' => false,
                'records' => $records
            ]), 'POST');

        } else {
            $user = get_user_by('email', $email);
            $user_id = $user->ID;
        }

       // wp_set_current_user($user_id);
       // wp_set_auth_cookie($user_id);

        echo '<div class="success-box">Registration successful</div>';

        return ob_get_clean();
    }

    // =========================
    // STEP 1 FORM
    // =========================
    ?>
    <p class="org-message">
To get started, please register using your work email address.  
We’ll use your email domain to automatically match you with your organization and streamline your membership setup.
</p>
    <form method="post">
        <input type="text" name="first_name" placeholder="First Name" required>
        <input type="text" name="last_name" placeholder="Last Name" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="text" name="phone" placeholder="Phone" required>

        <button type="submit" name="step1_submit">Next</button>
    </form>

    <?php
echo "</div>";
    return ob_get_clean();
}

add_shortcode('member_step_registration', 'member_step_registration_shortcode');










add_action('wp_ajax_search_salesforce_org', 'search_salesforce_org');
add_action('wp_ajax_nopriv_search_salesforce_org', 'search_salesforce_org');

function search_salesforce_org(){

    $keyword = sanitize_text_field($_POST['keyword']);

    if (empty($keyword)) {
        echo '<p>No keyword provided</p>';
        wp_die();
    }

    // 🔥 Salesforce SOQL
    $query = "SELECT Id, Name FROM Account WHERE Name LIKE '%" . esc_sql($keyword) . "%' AND PersonEmail = null";

    // 🔥 Use YOUR existing connector
    $results = SF_APIConnector::getSQueryObject($query, 10);

    if (empty($results)) {
        echo '<p>No organizations found</p>';
        wp_die();
    }

    foreach ($results as $org) {

        echo '<div class="org-result-item" 
                data-id="'.esc_attr($org->Id).'" 
                data-name="'.esc_attr($org->Name).'"
                style="padding:10px;border-bottom:1px solid #ddd;cursor:pointer;">
                '.esc_html($org->Name).'
              </div>';
    }

    wp_die();
}