<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Staff Timetable Shortcode
 * Usage: [staff_timetable staff_id="123" week="2025-38"]
 */
function payndle_render_staff_timetable($atts) {
    $atts = shortcode_atts([
        'staff_id' => '',
        'week' => ''
    ], $atts);

    // Accept numeric ID, staff slug, or URL query param ?staff=ID
    $staff_id = 0;
    $raw = isset($atts['staff_id']) ? trim($atts['staff_id']) : '';
    if (!empty($raw)) {
        if (ctype_digit($raw)) {
            $staff_id = absint($raw);
        } else {
            // try find by slug (post_name)
            $post = get_page_by_path($raw, OBJECT, 'staff');
            if ($post) $staff_id = $post->ID;
            else {
                // try by title
                $q = get_posts(array('post_type' => 'staff','title' => $raw,'posts_per_page' => 1,'fields'=>'ids'));
                if (!empty($q)) $staff_id = absint($q[0]);
            }
        }
    }

    // Allow URL query override (useful for staff links)
    if (empty($staff_id) && isset($_GET['staff'])) {
        $g = sanitize_text_field(wp_unslash($_GET['staff']));
        if (ctype_digit($g)) $staff_id = absint($g);
    }

    // If no valid staff id yet, render a selector showing staff from the 'staff' post type
    if (empty($staff_id)) {
        // Ensure selector styles and scripts are available for inline calendar behavior
        wp_enqueue_style('staff-timetable-css', plugin_dir_url(__FILE__) . 'assets/css/staff-timetable.css', [], '1.0');
        wp_enqueue_style('fullcalendar-core', 'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.css', [], '6.1.8');
        wp_enqueue_script('fullcalendar-core', 'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js', array(), '6.1.8', true);
        wp_enqueue_script('payndle-staff-timetable', plugin_dir_url(__FILE__) . 'assets/js/staff-timetable.js', array('fullcalendar-core'), '1.0', true);
        wp_localize_script('payndle-staff-timetable', 'payndleStaffTimetable', array(
            'rest_url' => rest_url('payndle/v1/staff-schedule'),
            'nonce' => wp_create_nonce('wp_rest')
        ));
        $all = get_posts(array(
            'post_type' => 'staff',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ));

        if (empty($all)) {
            return '<div class="staff-timetable">No staff found.</div>';
        }

    // Two-column layout: left = staff list, right = calendar container
    // Let CSS control the layout via .staff-row (flex) rather than inline styles
    $out = '<div class="staff-timetable staff-row">';
    $out .= '<div class="staff-list-column"><h3>Staff</h3><div class="staff-selector-grid">';
        foreach ($all as $p) {
            $pid = $p->ID;
            $avatar = '';
            $avatar_id = get_post_meta($pid, 'staff_avatar_id', true);
            if ($avatar_id) {
                $img = wp_get_attachment_image_src($avatar_id, 'thumbnail');
                if ($img) { $avatar = $img[0]; }
            }
            if (!$avatar) {
                $meta_url = get_post_meta($pid, 'staff_avatar', true);
                if (!empty($meta_url)) { $avatar = esc_url_raw($meta_url); }
            }
            if (!$avatar && has_post_thumbnail($pid)) {
                $img = wp_get_attachment_image_src(get_post_thumbnail_id($pid), 'thumbnail');
                if ($img) { $avatar = $img[0]; }
            }

            $link = esc_url(add_query_arg('staff', $pid));
            // Keep href for no-JS fallback; JS will handle clicks dynamically
            $out .= '<a class="staff-card" href="' . $link . '" data-staff-id="' . esc_attr($pid) . '">';
            if ($avatar) $out .= '<img class="staff-avatar" src="' . esc_attr($avatar) . '" alt="' . esc_attr(get_the_title($pid)) . '">';
            else $out .= '<div class="staff-avatar-initial">' . esc_html(substr(get_the_title($pid),0,1)) . '</div>';
            $out .= '<div class="staff-name">' . esc_html(get_the_title($pid)) . '</div>';
            $out .= '</a>';
        }
    $out .= '</div></div>'; // close grid and left column
    // right column will host the calendar when a staff is selected
        $out .= '<div class="staff-calendar-box">';
        $out .= '<div class="staff-calendar-column">';
        $out .= '<div class="staff-calendar-placeholder">'
            . '<h4>Pick a staff member</h4>'
            . '<p>Select someone from the list to the left to view their availability for the week. You can use the calendar to navigate weeks and click a booking to edit it (admins).</p>'
            . '<ul class="placeholder-sample"><li>Available: Mon 09:00–12:00</li><li>Available: Wed 14:00–18:00</li></ul>'
            . '</div>';
    // visible calendar element (hidden until a staff is selected)
    $out .= '<div class="staff-calendar" style="display:none;min-height:420px" aria-hidden="true" role="region" aria-label="Staff timetable"></div>';
    // close control (keyboard accessible) - visually hidden until calendar is shown
    $out .= '<button class="staff-calendar-close" style="display:none;" aria-label="Close timetable">Close</button>';
    $out .= '</div>'; // close calendar column
    $out .= '</div>'; // close calendar box
    $out .= '</div>'; // close staff-row
    return $out;
    }

    // Ensure the resolved ID is a staff post
    $staff_post = get_post($staff_id);
    if (!$staff_post || $staff_post->post_type !== 'staff') {
        return '<div class="staff-timetable">Invalid staff ID. <a href="' . esc_url(remove_query_arg('staff')) . '">Choose a staff member</a></div>';
    }

    if (!empty($atts['week'])) {
        list($year, $week) = explode('-', $atts['week']);
        $week = intval($week);
        $dto = new DateTime();
        $dto->setISODate(intval($year), $week);
    } else {
        $dto = new DateTime();
        $dto->setISODate((int)$dto->format('o'), (int)$dto->format('W'));
    }

    $start = clone $dto;
    $days = [];
    for ($i = 0; $i < 7; $i++) {
        $d = clone $start;
        $d->modify("+{$i} days");
        $days[] = $d;
    }

    $hours = [];
    for ($h = 9; $h <= 19; $h++) { $hours[] = sprintf('%02d:00', $h); }

    $date_from = $days[0]->format('Y-m-d');
    $date_to = $days[6]->format('Y-m-d');

    $args = [
        'post_type' => 'service_booking',
        'post_status' => ['publish','pending','draft'],
        'meta_query' => [
            [ 'key' => '_staff_id', 'value' => $staff_id, 'compare' => '=' ],
            [ 'key' => '_preferred_date', 'value' => [$date_from, $date_to], 'compare' => 'BETWEEN' ]
        ],
        'posts_per_page' => -1
    ];
    $bookings = get_posts($args);

    $slots = [];
    foreach ($bookings as $b) {
        $d = get_post_meta($b->ID, '_preferred_date', true);
        $t = get_post_meta($b->ID, '_preferred_time', true);
        if ($d && $t) {
            $slots[$d][$t][] = [ 'id' => $b->ID, 'title' => $b->post_title ];
        }
    }

    wp_enqueue_style('staff-timetable-css', plugin_dir_url(__FILE__) . 'assets/css/staff-timetable.css', [], '1.0');

    // Enqueue FullCalendar from CDN (global bundle that exposes plugins)
    wp_enqueue_style('fullcalendar-core', 'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.css', [], '6.1.8');
    // Use the global UMD build which exposes plugin constructors on the FullCalendar global
    wp_enqueue_script('fullcalendar-core', 'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js', array(), '6.1.8', true);
    // our init script
    wp_enqueue_script('payndle-staff-timetable', plugin_dir_url(__FILE__) . 'assets/js/staff-timetable.js', array('fullcalendar-core'), '1.0', true);
    wp_localize_script('payndle-staff-timetable', 'payndleStaffTimetable', array(
        'rest_url' => rest_url('payndle/v1/staff-schedule'),
        'nonce' => wp_create_nonce('wp_rest')
    ));

    // NOTE: REST route registration and callback are handled globally (see below)

    // Build left column staff list so both views use the same two-column layout
    $all_staff = get_posts(array(
        'post_type' => 'staff',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC'
    ));

    ob_start();
    ?>
    <div class="staff-timetable staff-row">
        <div class="staff-list-column">
            <h3>Staff</h3>
            <div class="staff-selector-grid">
                <?php foreach ($all_staff as $p):
                    $pid = $p->ID;
                    $avatar = '';
                    $avatar_id = get_post_meta($pid, 'staff_avatar_id', true);
                    if ($avatar_id) {
                        $img = wp_get_attachment_image_src($avatar_id, 'thumbnail');
                        if ($img) { $avatar = $img[0]; }
                    }
                    if (!$avatar) {
                        $meta_url = get_post_meta($pid, 'staff_avatar', true);
                        if (!empty($meta_url)) { $avatar = esc_url_raw($meta_url); }
                    }
                    if (!$avatar && has_post_thumbnail($pid)) {
                        $img = wp_get_attachment_image_src(get_post_thumbnail_id($pid), 'thumbnail');
                        if ($img) { $avatar = $img[0]; }
                    }
                    $link = esc_url(add_query_arg('staff', $pid));
                    $sel = ($pid == $staff_id) ? ' selected' : '';
                ?>
                    <a class="staff-card<?php echo $sel; ?>" href="<?php echo $link; ?>" data-staff-id="<?php echo esc_attr($pid); ?>">
                        <?php if ($avatar) : ?>
                            <img class="staff-avatar" src="<?php echo esc_attr($avatar); ?>" alt="<?php echo esc_attr(get_the_title($pid)); ?>">
                        <?php else : ?>
                            <div class="staff-avatar-initial"><?php echo esc_html(substr(get_the_title($pid),0,1)); ?></div>
                        <?php endif; ?>
                        <div class="staff-name"><?php echo esc_html(get_the_title($pid)); ?></div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="staff-calendar-box">
        <div class="staff-calendar-column">
            <div class="timetable-header">
                <h3>Timetable for <?php echo esc_html(get_the_title($staff_id)); ?></h3>
                <div class="timetable-controls">
                    <a class="timetable-prev" href="#" data-week="<?php echo esc_attr($dto->format('o') . '-' . $dto->format('W')); ?>">Previous</a>
                    <a class="timetable-next" href="#" data-week="<?php echo esc_attr($dto->format('o') . '-' . $dto->format('W')); ?>">Next</a>
                </div>
            </div>
            <div class="staff-calendar" data-staff-id="<?php echo esc_attr($staff_id); ?>" style="display:block;min-height:420px"></div>
            <button class="staff-calendar-close" style="display:inline-block;" aria-label="Close timetable">Close</button>
        </div>
        </div>
        </div>
            <noscript>
                <div class="timetable-fallback">
                    <?php echo '<p>Please enable JavaScript to view the interactive timetable. Falling back to a simple view.</p>'; ?>
                </div>
            </noscript>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

// Ensure shortcode is registered when plugin loads
add_action('init', function() {
    if (!shortcode_exists('staff_timetable')) {
        add_shortcode('staff_timetable', 'payndle_render_staff_timetable');
    }
});

// Removed temporary admin debug page and debug REST route to clean up plugin.

/**
 * REST callback moved to global scope so the route is registered during rest_api_init.
 */
function payndle_rest_get_staff_schedule($request) {
    $staff_id = $request->get_param('staff_id');
    if (empty($staff_id) || !is_numeric($staff_id)) {
        return new WP_Error('invalid_staff', 'Invalid or missing staff_id', array('status' => 400));
    }
    $staff_id = absint($staff_id);

    $start = $request->get_param('start');
    $end = $request->get_param('end');

    // Use WordPress timezone for parsing/formatting
    $wp_timezone = wp_timezone();

    if (empty($start) || empty($end)) {
        // Default to current ISO week
        $dt = new DateTime('now', $wp_timezone);
        $dt->setISODate((int)$dt->format('o'), (int)$dt->format('W'));
        $start = $dt->format('Y-m-d');
        $dt_end = clone $dt;
        $dt_end->modify('+6 days');
        $end = $dt_end->format('Y-m-d');
    }

    // Validate date format (YYYY-MM-DD)
    $date_re = '/^\d{4}-\d{2}-\d{2}$/';
    if (!preg_match($date_re, $start) || !preg_match($date_re, $end)) {
        return new WP_Error('invalid_date', 'Start and end must be YYYY-MM-DD', array('status' => 400));
    }
    // Use transient caching to speed up repeated requests for the same staff/week
    $transient_key = 'payndle_staff_sched_' . $staff_id . '_' . $start . '_' . $end;
    $cached = get_transient($transient_key);
    if ($cached !== false && is_array($cached)) {
        return rest_ensure_response($cached);
    }

    $args = array(
        'post_type' => 'service_booking',
        'post_status' => array('publish','pending','draft'),
        'posts_per_page' => -1,
        'meta_query' => array(
            array('key' => '_staff_id', 'value' => $staff_id, 'compare' => '='),
            array('key' => '_preferred_date', 'value' => array($start, $end), 'compare' => 'BETWEEN', 'type' => 'DATE')
        )
    );
    $q = get_posts($args);
    $events = array();

    // local helper to parse duration strings into minutes
    $duration_to_minutes = function($duration) {
        if (empty($duration)) return 60;
        $d = trim($duration);
        if (is_numeric($d)) return intval($d);
        if (strpos($d, ':') !== false) {
            $parts = explode(':', $d);
            $h = intval($parts[0]);
            $m = intval($parts[1] ?? 0);
            return $h * 60 + $m;
        }
        if (preg_match('/(\d+)\s*hour/i', $d, $m)) return intval($m[1]) * 60;
        if (preg_match('/(\d+)\s*min/i', $d, $m)) return intval($m[1]);
        return 60;
    };

    foreach ($q as $post) {
        $date = get_post_meta($post->ID, '_preferred_date', true);
        $time = get_post_meta($post->ID, '_preferred_time', true);
        if ($date && $time) {
            // Build a DateTime using WP timezone to ensure times match site settings
            try {
                $start_dt = new DateTime($date . ' ' . $time, $wp_timezone);
            } catch (Exception $e) {
                // skip invalid date/time
                continue;
            }

            // Determine duration: prefer service post meta, fallback to booking meta
            $service_id = get_post_meta($post->ID, '_service_id', true);
            $raw_duration = '';
            if ($service_id) {
                $raw_duration = get_post_meta($service_id, '_service_duration', true);
                if (empty($raw_duration)) $raw_duration = get_post_meta($service_id, 'service_duration', true);
            }
            if (empty($raw_duration)) {
                $raw_duration = get_post_meta($post->ID, '_service_duration', true);
            }

            $minutes = $duration_to_minutes($raw_duration);

            $end_dt = clone $start_dt;
            $end_dt->modify('+' . intval($minutes) . ' minutes');

            // Return times as floating local datetimes (no timezone offset) so the
            // frontend calendar renders the same hour the booking was saved.
            // FullCalendar treats timezone-less datetimes as local.
            $events[] = array(
                'id' => $post->ID,
                'title' => $post->post_title ?: ('Booking #' . $post->ID),
                'start' => $start_dt->format('Y-m-d\\TH:i:s'),
                'end' => $end_dt->format('Y-m-d\\TH:i:s'),
                'meta' => array('service_id' => $service_id, 'duration_minutes' => $minutes)
            );
        }
    }
    // Sort events by start time
    usort($events, function($a, $b) {
        return strcmp($a['start'], $b['start']);
    });

    // Cache for 60 seconds (lightweight; reduce if you need faster propagation)
    set_transient($transient_key, $events, 60);

    return rest_ensure_response($events);
}

add_action('rest_api_init', function() {
    register_rest_route('payndle/v1', '/staff-schedule', array(
        'methods' => 'GET',
        'callback' => 'payndle_rest_get_staff_schedule',
        'args' => array(
            'staff_id' => array('required' => true, 'sanitize_callback' => 'absint'),
            'start' => array('required' => false, 'sanitize_callback' => 'sanitize_text_field'),
            'end' => array('required' => false, 'sanitize_callback' => 'sanitize_text_field')
        ),
        'permission_callback' => function() { return true; }
    ));
    // Debug endpoint removed.
}, 10);
