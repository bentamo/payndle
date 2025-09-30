<?php
// ===== Shortcode: Sequential User + Business Registration =====
function sequential_registration_form() {
    if (is_user_logged_in()) {
        return '<p>You are already logged in.</p>';
    }

    $output = '';

    // STEP 1: Handle User Registration
    if (isset($_POST['step']) && $_POST['step'] == 'user' && wp_verify_nonce($_POST['sequential_registration_nonce'], 'sequential_registration')) {
        $username   = sanitize_user($_POST['username']);
        $email      = sanitize_email($_POST['email']);
        $password   = sanitize_text_field($_POST['password']);
        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name  = sanitize_text_field($_POST['last_name']);

        $errors = new WP_Error();

        if (empty($username) || empty($email) || empty($password)) {
            $errors->add('field', 'All required fields must be filled in.');
        }
        if (!is_email($email)) {
            $errors->add('email_invalid', 'Invalid email address.');
        }
        if (username_exists($username)) {
            $errors->add('username_exists', 'Username already exists.');
        }
        if (email_exists($email)) {
            $errors->add('email_exists', 'Email already registered.');
        }

        if (empty($errors->errors)) {
            $user_id = wp_create_user($username, $password, $email);

            if (!is_wp_error($user_id)) {
                wp_update_user(array(
                    'ID'         => $user_id,
                    'first_name' => $first_name,
                    'last_name'  => $last_name,
                ));

                // Store temporary user_id in hidden field for Step 2
                $output .= '<h3>Step 2: Register Your Business</h3>';
                $output .= '
                <form method="post">
                    <input type="hidden" name="user_id" value="'.$user_id.'">
                    <input type="hidden" name="step" value="business">
                    
                    <label>Business Name:</label><br>
                    <input type="text" name="business_name" required><br><br>

                    <label>Business Type:</label><br>
                    <input type="text" name="business_type" required><br><br>

                    <label>Business Location:</label><br>
                    <input type="text" name="business_location" required><br><br>

                    '.wp_nonce_field('sequential_registration', 'sequential_registration_nonce', true, false).'
                    <input type="submit" value="Register Business">
                </form>
                ';
                return $output;
            } else {
                $output .= '<p style="color:red;">❌ Error: '.$user_id->get_error_message().'</p>';
            }
        } else {
            foreach ($errors->get_error_messages() as $error) {
                $output .= '<p style="color:red;">❌ '.$error.'</p>';
            }
        }
    }

    // STEP 2: Handle Business Registration
    elseif (isset($_POST['step']) && $_POST['step'] == 'business' && wp_verify_nonce($_POST['sequential_registration_nonce'], 'sequential_registration')) {
        $user_id           = intval($_POST['user_id']);
        $business_name     = sanitize_text_field($_POST['business_name']);
        $business_type     = sanitize_text_field($_POST['business_type']);
        $business_location = sanitize_text_field($_POST['business_location']);

        // Generate unique business code
        $business_code = 'BIZ-' . strtoupper(wp_generate_password(6, false, false));

        // Create "business" post
        $post_id = wp_insert_post(array(
            'post_title'   => $business_name,
            'post_type'    => 'business',
            'post_status'  => 'publish',
            'post_author'  => $user_id,
        ));

        if ($post_id) {
            update_post_meta($post_id, 'business_type', $business_type);
            update_post_meta($post_id, 'business_location', $business_location);
            update_post_meta($post_id, 'business_code', $business_code);

            update_user_meta($user_id, 'business_name', $business_name);
            update_user_meta($user_id, 'business_type', $business_type);
            update_user_meta($user_id, 'business_location', $business_location);
            update_user_meta($user_id, 'business_code', $business_code);

            $output .= '<p style="color:green;">✅ Business registered successfully!<br>
            Your Business Code: <strong>'.$business_code.'</strong></p>';
        } else {
            $output .= '<p style="color:red;">❌ Failed to save business. Please try again.</p>';
        }
    }

    // Default: Show Step 1 User Registration Form
    if ($output == '') {
        $output .= '<h3>Step 1: Register User</h3>
        <form method="post">
            <input type="hidden" name="step" value="user">
            
            <label>Username <span style="color:red;">*</span></label><br>
            <input type="text" name="username" required><br><br>

            <label>Email <span style="color:red;">*</span></label><br>
            <input type="email" name="email" required><br><br>

            <label>Password <span style="color:red;">*</span></label><br>
            <input type="password" name="password" required><br><br>

            <label>First Name</label><br>
            <input type="text" name="first_name"><br><br>

            <label>Last Name</label><br>
            <input type="text" name="last_name"><br><br>

            '.wp_nonce_field('sequential_registration', 'sequential_registration_nonce', true, false).'
            <input type="submit" value="Register User">
        </form>';
    }

    return $output;
}
add_shortcode('sequential_registration', 'sequential_registration_form');


// ===== Register Business Post Type =====
function create_business_post_type_seq() {
    register_post_type('business',
        array(
            'labels' => array(
                'name' => __('Businesses'),
                'singular_name' => __('Business')
            ),
            'public' => true,
            'has_archive' => true,
            'supports' => array('title', 'author'),
        )
    );
}
add_action('init', 'create_business_post_type_seq');
?>
