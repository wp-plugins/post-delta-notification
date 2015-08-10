<?php
/**
 * @package Post-Delta-Notification
 * @version 1.0.3
 */
/*
Plugin Name: Post Delta Notification
Plugin URI: http://wordpress.org/plugins/post-delta-notification
Description: Allows users to receive an email if a post is updated.
Author: Michael George
Version: 1.0.3

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

if ( ! class_exists( "PostDeltaNotification" ) ) {
    class PostDeltaNotification {
        var $adminOptionsName = "PostDeltaNotificationAdminOptions";

        function __construct() {
            $this->pdn_getAdminOptions();
        }

        //Returns an array of admin options
        function pdn_GetAdminOptions() {
            $PostDeltaNotificationAdminOptions = array(
                                "fromAddress" => ""
                                ,"fromName" => ""
                                ,"limitToXRecipients" => 0
                                ,"footerAlignment" => 'left'
                                );
            $devOptions = get_option( $this->adminOptionsName );
            if ( ! empty( $devOptions ) ) {
                foreach ( $devOptions as $optionName => $optionValue ) {
                    $PostDeltaNotificationAdminOptions[$optionName] = $optionValue;
                }
            }
            update_option( $this->adminOptionsName, $PostDeltaNotificationAdminOptions );
            return $PostDeltaNotificationAdminOptions;
        }

        //Gets the settings link to show on the plugin management page
        //Thanks to "Floating Social Bar" plugin as the code is humbly taken from it
        function pdn_SettingsLink( $links ) {
            $setting_link = sprintf( '<a href="%s">%s</a>', add_query_arg( array( 'page' => 'post-delta-notification.php' ), admin_url( 'options-general.php' ) ), __( 'Settings', 'Post Delta Notification' ) );
            array_unshift( $links, $setting_link );
            return $links;
        }

        //Prints out the admin page
        function pdn_PrintOptionPage() {
            $devOptions = $this->pdn_GetAdminOptions();
            $workingURL = $_SERVER["REQUEST_URI"];

            if ( isset( $_POST['update_pdn_Settings'] ) ) {
                //The options are set regardless, but there are some error checks in here.
                //If we find errors, we'll just display them on the page with an warning that
                //the config likely won't work.
                $optionErrors = array();

                $devOptions['fromAddress'] = $_POST['PostDeltaNotification_fromAddress'];
                if( ! filter_var( $_POST['PostDeltaNotification_fromAddress'], FILTER_VALIDATE_EMAIL ) ) {
                    $optionErrors[] = "From address was not a valid email address.";
                }

                $devOptions['fromName'] = $_POST['PostDeltaNotification_fromName'];
                if ( empty( $_POST['PostDeltaNotification_fromName'] ) && $devOptions['useExternal'] ) {
                    $optionErrors[] = "From name is blank when using an external host.";
                }

                $devOptions['limitToXRecipients'] = $_POST['PostDeltaNotification_recLimit'];
                if ( ! is_numeric( $_POST['PostDeltaNotification_recLimit'] ) ) {
                    $optionErrors[] = "Recipient limit was not a number.";
                }
                if ( ! (int)$_POST['PostDeltaNotification_recLimit'] == $_POST['PostDeltaNotification_recLimit'] ) {
                    $optionErrors[] = "Recipient limit was not an integer.";
                }
                if ( (int)$_POST['PostDeltaNotification_recLimit'] < 0 ) {
                    $optionErrors[] = "Recipient limit was not a positive number. Use 0 if you want no limit.";
                } else {
                    $devOptions['limitToXRecipients'] = (int)$_POST['PostDeltaNotification_recLimit'];
                }

                $devOptions['footerAlignment'] = $_POST['PostDeltaNotification_footerAlignment'];

                $updated = update_option($this->adminOptionsName, $devOptions);
            }

            echo "<div class='updated'>\r";
            if ( isset( $updated ) && $updated ) {
                echo "\t<p><strong>Settings Updated.</strong></p>";
                if ( count( $optionsErrors ) > 0 ) {
                    echo "\t<p>There are errors in your configuration. Odds are good that emails will not be sent successfully.</p>\r";
                    echo "\t<ul>\r";
                    foreach ( $optionErrors as $error ) {
                        echo "\t\t<li>" . $error . "</li>\r";
                    }
                    echo "\t</ul>\r";
                }
            } else if ( isset( $updated ) && ! $updated ) {
                echo "<p><strong>Settings failed to update.</strong></p>\r";
                if ( count( $optionsErrors ) > 0 ) {
                    echo "\t<p>There are errors in your configuration. Odds are good that emails will not be sent successfully.</p>\r";
                    echo "\t<ul>\r";
                    foreach ( $optionErrors as $error ) {
                        echo "\t\t<li>" . $error . "</li>\r";
                    }
                    echo "\t</ul>\r";
                }
            }
            echo "</div>\r";
?>
<div id="post_delta_notification_option_page" style="width:80%">
<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
<input type='hidden' name='update_pdn_Settings' value='1'>
<h2>Post Delta Notification Settings</h2><?php
            echo "<h3 style='margin-bottom: -5px;'>Email From Address</h3>\r";
            echo "\t<p><input id='PostDeltaNotification_fromAddress' type='text' name='PostDeltaNotification_fromAddress' value='" . $devOptions['fromAddress'] . "' style='width: 250px;'><br>\r";
            echo "\tEmails sent to users will use this as the \"from\" address. If blank, the default WP from address is used.</p>\r";
            echo "<h3 style='margin-bottom: -5px;'>Email From Name</h3>\r";
            echo "\t<p><input id='PostDeltaNotification_fromName' type='text' name='PostDeltaNotification_fromName' value='" . $devOptions['fromName'] . "' style='width: 250px;'><br>\r";
            echo "\tEmails sent to users will use this as the \"from\" display name. If blank, the default WP from name is used.</p>\r";
            echo "<h3 style='margin-bottom: -5px;'>Per Email Recipient Limit</h3>\r";
            echo "\t<p><input id='PostDeltaNotification_recLimit' type='text' name='PostDeltaNotification_recLimit' value='" . $devOptions['limitToXRecipients'] . "' style='width: 150px;'><br>\r";
            echo "\tSome providers limit the number of recipients that can be on a single email. You can enter your limit here or set it to zero (\"0\") for unlimited. If the number of intended recipients is higher than this, he system will split the number across multiple messages.</p>\r";
            echo "<h3 style='margin-bottom: -5px;'>Subscribe Button Position</h3>\r";
            echo "\t<p><input id='PostDeltaNotification_footerAlignment_left' type='radio' name='PostDeltaNotification_footerAlignment' value='left'" . ( $devOptions['footerAlignment'] == 'left' ? " checked" : "" ) . ">Left</input>\r";
            echo "\t<input id='PostDeltaNotification_footerAlignment_center' type='radio' name='PostDeltaNotification_footerAlignment' value='center'" . ( $devOptions['footerAlignment'] == 'center' ? " checked" : "" ) . ">Center</input>\r";
            echo "\t<input id='PostDeltaNotification_footerAlignment_right' type='radio' name='PostDeltaNotification_footerAlignment' value='right'" . ( $devOptions['footerAlignment'] == 'right' ? " checked" : "" ) . ">Right</input><br>\r";
            echo "\tThe subscribe and unsubscribe buttons will appear at the bottom of each post and will be aligned by this option.</p>\r";
            echo "<input type='submit' value='Save'>\r";
?></form>
</div><?php
        } //End function pdn_PrintOptionPage

        function pdn_SendEmail( $post_id, $newPost, $oldPost ) {
            $devOptions = $this->pdn_GetAdminOptions();
            //Check if content has changed
            if ( $oldPost->post_content == $newPost->post_content ) {
                return;
            }
            $subject = "Updated blog post";
            $message = "<p>A blog post on \"" . get_bloginfo( 'name' ) . "\" has been updated. Click the post title below to see the new content.</p>\r";
            $message .= "<p><a href='" . get_permalink( $newPost->ID ) . "' target=_blank>" . apply_filters( 'the_title', $newPost->post_title ) . "</a></p>\r";
            $message .=  "<p>On this page, you can unsubscribe from future email updates.</p>\r";
            $addresses = get_post_meta( $newPost->ID, 'PDN_subscribers', true );
            $addresses = @array_unique( $addresses );
            //Only send emails if subscribers exist
            if ( count( $addresses ) >= 1 ) {
                add_filter( 'wp_mail_content_type', array( $this, 'set_html_content_type' ) );
                if ( $devOptions['limitToXRecipients'] > 0 ) {
                    $batchCount = floor( count( $addresses ) / $devOptions['limitToXRecipients'] ) + ( count( $addresses ) % $devOptions['limitToXRecipients'] == 0 ? 0 : 1 );
                    $addresses = array_values( $addresses );
                    for ( $i = 0; $i < $batchCount; $i++ ) {
                        $headers = array();
                        if ( ! empty( $devOptions['fromAddress'] ) ) {
                            $headers[] = "From: " . $devOptions['fromName'] . " <" . $devOptions['fromAddress'] . ">";
                        }
                        for ( $j = 0; $j < $devOptions['limitToXRecipients']; $j++ ) {
                            $headers[] = "Bcc: " . $addresses[($i * $devOptions['limitToXRecipients'] + $j)];
                        }
                        wp_mail( '', $subject, $message, $headers );
                    }
                } else {
                    $headers = array();
                    if ( ! empty( $devOptions['fromAddress'] ) ) {
                        $headers[] = "From: " . $devOptions['fromName'] . " <" . $devOptions['fromAddress'] . ">";
                    }
                    foreach ( $addresses as $address ) {
                        $headers[] = "Bcc: $address";
                    }
                    wp_mail( '', $subject, $message, $headers );
                }
                remove_filter( 'wp_mail_content_type', array( $this, 'set_html_content_type' ) );
            }
        }

        
        function set_html_content_type() {
            return 'text/html';
        }

        //Displays the subscribe/unsubscribe button on posts
        function pdn_ContentInsertion( $content ) {
            $devOptions = $this->pdn_GetAdminOptions();
            $my_post_id = get_the_ID();
            $return = $content;
            //Only show buttons if user is logged in
            if ( ! is_user_logged_in() ) {
                return $return;
            }
            $my_user_ID = get_current_user_id();
            $my_user = get_user_by( 'id', $my_user_ID );
            $my_email = $my_user->user_email;
            $addresses = get_post_meta( $my_post_id, 'PDN_subscribers', true );
            if ( is_array( $addresses ) ) {
                $addresses = array_unique( $addresses );
            } else {
                $addresses = array();
            }

            $postType = get_post_type( $my_post_id );
            //Check to see if the URL is a permalink, if not, we aren't doing anything
            //This prevents the suggest review button from appear on search results and post lists
            if ( "https://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'] == get_permalink() && !is_bool( $postType ) && $postType == 'post' ) {

                if ( count( $addresses ) >= 1 ) {
                    if ( in_array( $my_email, $addresses ) ) {
                        $subscribed = true;
                    } else {
                        $subscribed = false;
                    }
                } else {
                    $subscribed = false;
                }
                //As of WP 4.0, the ampersands ('&') in the urls below are getting escaped. I've changed them to be %26 instead and handle the conversion in pdn_parseRequest
                $return .= '<hr><div id="PostDeltaNotification_Unsub" style="' . ( $subscribed ? "" : "display: none;" ) . '"><p style="text-align: ' . $devOptions['footerAlignment'] . '"><span id="PostDeltaNotification_UnsubResultText"></span>You are following this posts updates via email.<br>
<button id="PostDeltaNotification_UnsubButton">Unsubscribe</button></p></div>
<script>
jQuery( "#PostDeltaNotification_UnsubButton" ).click( function(e){
        e.preventDefault;
        if ( window.XMLHttpRequest ) {// code for IE7+, Firefox, Chrome, Opera, Safari
            myRequest = new XMLHttpRequest();
        } else {// code for IE6, IE5
            myRequest = new ActiveXObject("Microsoft.XMLHTTP");
        }
        myRequest.open( "POST", "' . site_url() . '/?postdeltanotification=unsubscribe%26post=' . $my_post_id . '%26email=' . urlencode( $my_email ) . '", false ); //the false makes it synchronous
        myRequest.setRequestHeader( "Content-Type", "text/xml; charset=utf-8" );
        myRequest.send();
        if ( myRequest.status == 200 ) {
            jQuery( "#PostDeltaNotification_Unsub" ).toggle();
            jQuery( "#PostDeltaNotification_Sub" ).toggle();
            jQuery( "#PostDeltaNotification_SubResultText" ).html( "You have been unsubscribed.<br>" );
            jQuery( "#PostDeltaNotification_SubResultText" ).css( "color", "green" );
        } else {
            jQuery( "#PostDeltaNotification_UnsubResultText" ).html( "There was a problem unsubscribing you. Please try again.<br>" );
            jQuery( "#PostDeltaNotification_UnsubResultText" ).css( "color", "red" );
        }
    });
</script>
<div id="PostDeltaNotification_Sub" style="' . ( $subscribed ? "display: none;" : "" ) . '"><p style="text-align: ' . $devOptions['footerAlignment'] . '"><span id="PostDeltaNotification_SubResultText"></span>Click the button to subscribe to updates of this posts updates via email.<br>
<button id="PostDeltaNotification_SubButton">Subscribe</button></p></div>
<script>
jQuery( "#PostDeltaNotification_SubButton" ).click(function(e){
        e.preventDefault;
        if ( window.XMLHttpRequest ) {// code for IE7+, Firefox, Chrome, Opera, Safari
            myRequest = new XMLHttpRequest();
        } else {// code for IE6, IE5
            myRequest = new ActiveXObject("Microsoft.XMLHTTP");
        }
        myRequest.open( "POST", "' . site_url() . '/?postdeltanotification=subscribe%26post=' . $my_post_id . '%26email=' . urlencode( $my_email ) . '", false ); //the false makes it synchronous
        myRequest.setRequestHeader( "Content-Type","text/xml; charset=utf-8" );
        myRequest.send();
        if ( myRequest.status == 200 ) {
            jQuery( "#PostDeltaNotification_Unsub" ).toggle();
            jQuery( "#PostDeltaNotification_Sub" ).toggle();
            jQuery( "#PostDeltaNotification_UnsubResultText" ).html( "You have been subscribed.<br>" );
            jQuery( "#PostDeltaNotification_UnsubResultText" ).css( "color", "green" );
        } else {
            jQuery( "#PostDeltaNotification_SubResultText" ).html( "There was a problem subscribing you. Please try again.<br>" );
            jQuery( "#PostDeltaNotification_SubResultText" ).css( "color", "red" );
        }
    });
</script>';
            } //end if permalink
            return $return;
        }

        //Handler function for the javascript callbacks that happen when a user clicks the subscribe or unsubscribe buttons
        //As of WP 4.0, some funny things started happening with the javascript and their urls, so I have to do
        //some conversion and validation in here now.
        function pdn_ParseRequest( $wp ) {
            if ( array_key_exists( 'postdeltanotification', $_GET ) ) {
                //echo "<!-- " . print_r( $_GET, true ) . " -->\r";
                $request = explode( '&', $_GET['postdeltanotification'] );
                if ( ( is_array( $request ) && $request[0] == $_GET['postdeltanotification'] ) || $request === false ) {
                    wp_die( "Failure in PostDeltaNotification ParseRequest! Malformed operator.", "", array( "response" => 500 ) );
                }
                $operation = "";
                $post = "";
                $email = "";
                foreach ( $request as $part ) {
                    if ( strstr( $part , '=' ) !== false ) {
                        $subparts = explode( '=', $part );
                        if ( $subparts[0] == 'post' ) {
                            $post = $subparts[1];
                        } else if ( $subparts[0] == 'email' ) {
                            $email = sanitize_email( urldecode( $subparts[1] ) );
                        }
                    } else {
                        if ( $part == 'subscribe' ) {
                            $operation = "subscribe";
                        } else if ( $part == 'unsubscribe' ) {
                            $operation = "unsubscribe";
                        }
                    }
                }

                if ( empty( $operation ) || empty( $email ) || empty( $email ) ) {
                    wp_die( "Failure in PostDeltaNotification ParseRequest! Not all required operators found.", "", array( "response" => 500 ) );
                }

                //echo "<!-- operation: " . $operation . ". post: " . $post . ". email: " . $email . ". -->\r";
                $addresses = get_post_meta( $post, 'PDN_subscribers', true );
                if ( is_array( $addresses ) ) {
                    $key = array_search( $email, $addresses );
                } else {
                    $key = false;
                }
                if ( $operation == 'unsubscribe' ) {
                    if ( $key !== false ) {
                        unset( $addresses[$key] );
                    } else {
                        wp_die( "Not currently subscribed. " . count( $addresses ) . " subscribers.", "", array( "response" => 200 ) );
                    }
                } else if ( $operation == 'subscribe' ) {
                    if ( $key !== false ) {
                        wp_die( "Already subscribed. " . count( $addresses ) . " subscribers.", "", array( "response" => 200 ) );
                    }
                    if ( is_array( $addresses ) ) {
                        $addresses[] = $email;
                    } else {
                        $addresses = array( $email );
                    }
                }
                $addresses = array_unique( $addresses );
                $addresses = array_values( $addresses );
                if ( count( $addresses ) == 0 ) {
                    delete_post_meta( $post, 'PDN_subscribers' );
                    wp_die( "Success in PostDeltaNotification ParseRequest. " . count( $addresses ) . " subscribers.", "", array( "response" => 200 ) );
                } else {
                    if ( update_post_meta( $post, 'PDN_subscribers', $addresses ) === false ) {
                        wp_die( "Failure in PostDeltaNotification ParseRequest!", "", array( "response" => 500 ) );
                    } else {
                        wp_die( "Success in PostDeltaNotification ParseRequest. " . count( $addresses ) . " subscribers.", "", array( "response" => 200 ) );
                    }
                }
            }
        }

    } //End PostDeltaNotification class def
}

//Initialize the admin panel
if ( ! function_exists( "pdn_AddOptionPage" ) ) {
    function pdn_AddOptionPage() {
        global $svvsd_PDN;
        if ( ! isset( $svvsd_PDN ) ) {
            return;
        }
        if ( function_exists( 'add_options_page' ) ) {
            add_options_page( 'Post Delta Notification', 'Post Delta Notification', 'manage_options', basename( __FILE__ ), array( &$svvsd_PDN, 'pdn_PrintOptionPage' ) );
        }
    }    
}

//Create instance
if ( class_exists( "PostDeltaNotification" ) ) {
    $svvsd_PDN = new PostDeltaNotification();
}

//Actions and Filters
if ( isset( $svvsd_PDN ) ) {
    //Filters
    add_filter( 'plugin_action_links_' . plugin_basename( plugin_dir_path( __FILE__ ) . 'post-delta-notification.php' ), array( &$svvsd_PDN, 'pdn_SettingsLink' ) );
    add_filter( 'the_content', array( &$svvsd_PDN, 'pdn_ContentInsertion' ), 9 );

    //Actions
    add_action( 'admin_menu', 'pdn_AddOptionPage' );
    add_action( 'activate_PostDeltaNotification/post-delta-notification.php',  array( &$svvsd_PDN, '__construct' ) );
    add_action( 'post_updated', array( &$svvsd_PDN, 'pdn_SendEmail' ), 10, 3 );
    add_action( 'parse_request', array( &$svvsd_PDN, 'pdn_ParseRequest' ) );
}
?>