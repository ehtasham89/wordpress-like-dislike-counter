<?php 
/* Enqueue scripts for dislike system */
function liker()
{
    wp_enqueue_script('idev_like_post', plugins_url('liker.ajax.js', __FILE__), ['jquery'], '1.0', 1);
    wp_localize_script('idev_like_post', 'ajax_var', [
        'url'   => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('ajax-nonce'),
        ]
    );
}
add_action('init', 'liker');
/* Save like data*/
add_action('wp_ajax_nopriv_idev-post-like', 'idev_post_like');
add_action('wp_ajax_idev-post-like', 'idev_post_like');
function idev_post_like()
{
    $nonce = $_POST['nonce'];
    if (!wp_verify_nonce($nonce, 'ajax-nonce')) {
        die('Nope!');
    }

    if (isset($_POST['idev_post_like'])) {
        $post_id = $_POST['post_id']; // post id
        $post_like_count = get_post_meta($post_id, '_post_like_count', true); // post like count

        if (function_exists('wp_cache_post_change')) { // invalidate WP Super Cache if exists
            $GLOBALS['super_cache_enabled'] = 1;
            wp_cache_post_change($post_id);
        }

        if (is_user_logged_in()) { // user is logged in
            $user_id = get_current_user_id(); // current user
            $meta_POSTS = get_user_option('_liked_posts', $user_id); // post ids from user meta
            $meta_USERS = get_post_meta($post_id, '_user_liked'); // user ids from post meta
            $liked_POSTS = null; // setup array variable
            $liked_USERS = null; // setup array variable

            if (count($meta_POSTS) != 0) { // meta exists, set up values
                $liked_POSTS = $meta_POSTS;
            }

            if (!is_array($liked_POSTS)) { // make array just in case
                $liked_POSTS = [];
            }

            if (count($meta_USERS) != 0) { // meta exists, set up values
                $liked_USERS = $meta_USERS[0];
            }

            if (!is_array($liked_USERS)) { // make array just in case
                $liked_USERS = [];
            }

            $liked_POSTS['post-'.$post_id] = $post_id; // Add post id to user meta array
            $liked_USERS['user-'.$user_id] = $user_id; // add user id to post meta array
            $user_likes = count($liked_POSTS); // count user likes

            if (!AlreadyDisLiked($post_id)) {
                if (!AlreadyLiked($post_id)) { // like the post
                    update_post_meta($post_id, '_user_liked', $liked_USERS); // Add user ID to post meta
                    update_post_meta($post_id, '_post_like_count', ++$post_like_count); // +1 count post meta
                    update_user_option($user_id, '_liked_posts', $liked_POSTS); // Add post ID to user meta
                    update_user_option($user_id, '_user_like_count', $user_likes); // +1 count user meta
                    echo $post_like_count.' likes'; // update count on front end
                } else { // unlike the post
                    $pid_key = array_search($post_id, $liked_POSTS); // find the key
                    $uid_key = array_search($user_id, $liked_USERS); // find the key
                    unset($liked_POSTS[$pid_key]); // remove from array
                    unset($liked_USERS[$uid_key]); // remove from array
                    $user_likes = count($liked_POSTS); // recount user likes
                    update_post_meta($post_id, '_user_liked', $liked_USERS); // Remove user ID from post meta
                    update_post_meta($post_id, '_post_like_count', --$post_like_count); // -1 count post meta
                    update_user_option($user_id, '_liked_posts', $liked_POSTS); // Remove post ID from user meta
                    update_user_option($user_id, '_user_like_count', $user_likes); // -1 count user meta
                    echo 'count'.$post_like_count.' likes'; // update count on front end
                }
            } else {
                echo 'count you have already disliked.';
            }
        } else { // user is not logged in (anonymous)
            $ip = $_SERVER['REMOTE_ADDR']; // user IP address
            $meta_IPS = get_post_meta($post_id, '_user_IP'); // stored IP addresses
            $liked_IPS = null; // set up array variable

            if (count($meta_IPS) != 0) { // meta exists, set up values
                $liked_IPS = $meta_IPS[0];
            }

            if (!is_array($liked_IPS)) { // make array just in case
                $liked_IPS = [];
            }

            if (!in_array($ip, $liked_IPS)) { // if IP not in array
                $liked_IPS['ip-'.$ip] = $ip;
            } // add IP to array

            if (!AlreadyDisLiked($post_id)) {
                if (!AlreadyLiked($post_id)) { // like the post
                        update_post_meta($post_id, '_user_IP', $liked_IPS); // Add user IP to post meta
                        update_post_meta($post_id, '_post_like_count', ++$post_like_count); // +1 count post meta
                        echo $post_like_count.' likes'; // update count on front end
                } else { // unlike the post
                    $ip_key = array_search($ip, $liked_IPS); // find the key
                    unset($liked_IPS[$ip_key]); // remove from array
                    update_post_meta($post_id, '_user_IP', $liked_IPS); // Remove user IP from post meta
                    update_post_meta($post_id, '_post_like_count', --$post_like_count); // -1 count post meta
                    echo 'count'.$post_like_count.' likes'; // update count on front end
                }
            } else {
                echo 'count you have already disliked.';
            }
        }
    }

    exit;
}

/* Test if user already liked post */
function AlreadyLiked($post_id)
{ // test if user liked before
    if (is_user_logged_in()) { // user is logged in
        $user_id = get_current_user_id(); // current user
        $meta_USERS = get_post_meta($post_id, '_user_liked'); // user ids from post meta
        $liked_USERS = ''; // set up array variable

        if (count($meta_USERS) != 0) { // meta exists, set up values
            $liked_USERS = $meta_USERS[0];
        }

        if (!is_array($liked_USERS)) { // make array just in case
            $liked_USERS = [];
        }

        if (in_array($user_id, $liked_USERS)) { // True if User ID in array
            return true;
        }

        return false;
    } else { // user is anonymous, use IP address for voting

        $meta_IPS = get_post_meta($post_id, '_user_IP'); // get previously voted IP address
        $ip = $_SERVER['REMOTE_ADDR']; // Retrieve current user IP
        $liked_IPS = ''; // set up array variable

        if (count($meta_IPS) != 0) { // meta exists, set up values
            $liked_IPS = $meta_IPS[0];
        }

        if (!is_array($liked_IPS)) { // make array just in case
            $liked_IPS = [];
        }

        if (in_array($ip, $liked_IPS)) { // True is IP in array
            return true;
        }

        return false;
    }
}

function AlreadyDisLiked($post_id)
{ // test if user liked before
    if (is_user_logged_in()) { // user is logged in
        $user_id = get_current_user_id(); // current user
        $meta_USERS = get_post_meta($post_id, '_user_unliked'); // user ids from post meta
        $liked_USERS = ''; // set up array variable

        if (count($meta_USERS) != 0) { // meta exists, set up values
            $liked_USERS = $meta_USERS[0];
        }

        if (!is_array($liked_USERS)) { // make array just in case
            $liked_USERS = [];
        }

        if (in_array($user_id, $liked_USERS)) { // True if User ID in array
            return true;
        }

        return false;
    } else { // user is anonymous, use IP address for voting

        $meta_IPS = get_post_meta($post_id, '_user_unlike_IP'); // get previously voted IP address
        $ip = $_SERVER['REMOTE_ADDR']; // Retrieve current user IP
        $liked_IPS = ''; // set up array variable

        if (count($meta_IPS) != 0) { // meta exists, set up values
            $liked_IPS = $meta_IPS[0];
        }

        if (!is_array($liked_IPS)) { // make array just in case
            $liked_IPS = [];
        }

        if (in_array($ip, $liked_IPS)) { // True is IP in array
            return true;
        }

        return false;
    }
}
/* Front end button */
function getPostLikeLink($post_id)
{
    $like_count = get_post_meta($post_id, '_post_like_count', true); // get post likes
    $count = (empty($like_count) || $like_count == '0') ? '0' : esc_attr($like_count);
    if (AlreadyLiked($post_id)) {
        $class = esc_attr(' liked');
        $title = esc_attr('You have already liked');
    } else {
        $class = esc_attr('');
        $title = esc_attr('Like it');
    }
    $output = '<a href="#" class="idev-post-like'.$class.'" data-post_id="'.$post_id.'" title="'.$title.'">&nbsp;'.$count.' likes</a>';

    return $output;
}

/* Retrieve User Likes and Show on Profile */
add_action('show_user_profile', 'show_user_likes');
add_action('edit_user_profile', 'show_user_likes');
function show_user_likes($user)
{
    ?>        
    <table class="form-table">
        <tr>
			<th><label for="user_likes"><?php _e('You Like:');
    ?></label></th>
			<td>
            <?php
            $user_likes = get_user_option('_liked_posts', $user->ID);
    if (!empty($user_likes) && count($user_likes) > 0) {
        $the_likes = $user_likes;
    } else {
        $the_likes = '';
    }
    if (!is_array($the_likes)) {
        $the_likes = [];
    }
    $count = count($the_likes);
    $i = 0;
    if ($count > 0) {
        $like_list = '';
        echo "<p>\n";
        foreach ($the_likes as $the_like) {
            $i++;
            $like_list .= '<a href="'.esc_url(get_permalink($the_like)).'" title="'.esc_attr(get_the_title($the_like)).'">'.get_the_title($the_like)."</a>\n";
            if ($count != $i) {
                $like_list .= ' &middot; ';
            } else {
                $like_list .= "</p>\n";
            }
        }
        echo $like_list;
    } else {
        echo '<p>'._e('You don\'t like anything yet.')."</p>\n";
    }
    ?>
            </td>
		</tr>
    </table>
<?php 
}

/* Add a shortcode to your posts instead
 * type [idev_liker] in your post to output the button
 */
function idev_like_shortcode()
{
    return getPostLikeLink(get_the_ID());
}
add_shortcode('idev_liker', 'idev_like_shortcode');
