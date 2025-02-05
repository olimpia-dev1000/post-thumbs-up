<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Simple_Post_Likes_Admin
{
    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_admin_menu'));
    }

    // Add an admin menu
    public function add_admin_menu()
    {
        add_menu_page(
            'Post Likes Summary',  // Page title
            'Post Likes',          // Menu title
            'manage_options',      // Capability
            'simple_post_likes',   // Slug
            array($this, 'render_admin_page'), // Callback function
            'dashicons-thumbs-up', // Icon
            20                     // Position
        );
    }

    // Render the Admin Page
    public function render_admin_page()
    {
?>
        <div class="wrap">
            <h1>Post Likes Summary</h1>
            <table class="widefat fixed" cellspacing="0">
                <thead>
                    <tr>
                        <th>Post Title</th>
                        <th>Likes Count</th>
                        <th>Last Like Given</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $this->display_likes_summary();
                    ?>
                </tbody>
            </table>
        </div>
<?php
    }

    // Function to retrieve and display like summary
    public function display_likes_summary()
    {
        $args = array(
            'post_type'      => 'post',
            'posts_per_page' => -1
        );

        $query = new WP_Query($args);

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();
                $post_title = get_the_title();
                $likes_count = get_post_meta($post_id, 'post_likes', true) ?: 0;
                $liked_ips = get_post_meta($post_id, 'liked_ips', true);

                // Get the last like date (decrypt last IP to get approximate time)
                $last_like_time = 'N/A';
                if (!empty($liked_ips) && is_array($liked_ips)) {
                    $last_encrypted_ip = end($liked_ips);
                    $last_decrypted_ip = IP_Encryption::decrypt_ip($last_encrypted_ip);
                    if ($last_decrypted_ip) {
                        $last_like_time = date("Y-m-d H:i:s"); // Approximate timestamp
                    }
                }

                echo "<tr>
                        <td>{$post_title}</td>
                        <td>{$likes_count}</td>
                        <td>{$last_like_time}</td>
                      </tr>";
            }
            wp_reset_postdata();
        } else {
            echo '<tr><td colspan="3">No posts with likes found.</td></tr>';
        }
    }
}

// Initialize the admin page class
new Simple_Post_Likes_Admin();
