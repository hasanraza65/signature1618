<?php

/*
Plugin Name: Antigro Designer
Description: A WordPress plugin for Antigro Designer.
Version: 1.0
*/

// Include the JWT library
require "vendor/autoload.php";
use Firebase\JWT\JWT;

// Basic URL for signing the JWT token
function get_antigro_basic_url() {
    // $basic_url = 'https://designer.antigro.com/';
    // $basic_url = 'https://designer-test.antigro.com';
    $basic_url = (!empty(get_option("antigro_basic_url"))) ? get_option("antigro_basic_url") : "";
return $basic_url; }

// Secret key for signing the JWT token
function get_antigro_secret_key() {
    // $secret_key = 'caBLLCsElivvm4E3EcyliUXHpMviWyx8v6nYC5emj54='; // Replace with your actual secret key
    // $secret_key = 'faBjMf3gEivZbcR5ctaxBJUJk+pRKy2Hp48sXVgoRn8='; // Replace with your actual secret key
    $secret_key = (!empty(get_option("antigro_secret_key"))) ? get_option("antigro_secret_key") : "";
    $secret_key = base64_decode($secret_key);
return $secret_key; }

// Client Design ID URL
function get_antigro_clientDesignId_URL() {
    // return "https://designer-test.antigro.com/en";
    return (!empty(get_option("antigro_clientDesignId_URL"))) ? get_option("antigro_clientDesignId_URL") : "";
}

// Get JWT Token
function get_antigro_jwt_token() {

    $basic_url = get_antigro_basic_url(); // Basic URL for signing the JWT token

    $secret_key = get_antigro_secret_key(); // Secret key for signing the JWT token

    // Check if the JWT library is available
    if (class_exists('Firebase\JWT\JWT')) {

        // Payload data for the JWT token
        $payload = [
            'iss' => $basic_url,
            'iat' => time(), // Issued at time
            'exp' => time() + 600, // Expiration time (1 hour)
        ];

        // $jwt_token = JWT::encode($payload, $secret_key, 'HS256');
        
        // Generate the JWT token
        $jwt_token = JWT::encode($payload, $secret_key, 'HS256');

    } else {
        echo 'JWT library not found. Please make sure it is properly installed.';
        return false;
    }

return $jwt_token; }

function init_antigro_post() {

    // Create Design
    if(isset($_POST["antigro_create_design_submit"])) {

        $jwt_token = get_antigro_jwt_token(); // Get JWT Token

        // Check if cURL is available
        if ($jwt_token && function_exists('curl_init')) {

            $basic_url = get_antigro_basic_url(); // Basic URL for signing the JWT token
            $api_url = $basic_url . '/api/partner-backend/designs';

            // Your JSON data for the POST request
            $antigro_sellerid = (!empty(get_option("antigro_sellerid"))) ? get_option("antigro_sellerid") : "";
            $antigro_brandid = (!empty(get_option("antigro_brandid"))) ? get_option("antigro_brandid") : "";
            $antigro_color = (!empty(get_option("antigro_color"))) ? get_option("antigro_color") : "";
            $antigro_volume = (!empty(get_option("antigro_volume"))) ? get_option("antigro_volume") : 1;
            $antigro_order_id = (!empty(get_option("antigro_order_id"))) ? get_option("antigro_order_id") : "ORDER";

            $post_data = [
                "sellerId" => (!empty($_POST["antigro_create_design_sellerId"])) ? $_POST["antigro_create_design_sellerId"] : $antigro_sellerid,
                "brandId" => (!empty($_POST["antigro_create_design_brandId"])) ? $_POST["antigro_create_design_brandId"] : $antigro_brandid,
                "productCode" => (!empty($_POST["antigro_create_design_productCode"])) ? $_POST["antigro_create_design_productCode"] : "",
                "templateBindingType" => (!empty($_POST["antigro_create_design_templateBindingType"])) ? $_POST["antigro_create_design_templateBindingType"] : "",
                "templateId" => (!empty($_POST["antigro_create_design_templateId"])) ? $_POST["antigro_create_design_templateId"] : "",
                "productParameters" => [
                    "color" => (!empty($_POST["antigro_create_design_productParametersColor"])) ? $_POST["antigro_create_design_productParametersColor"] : $antigro_color
                ],
                "returnUrl" => (!empty($_POST["antigro_create_design_returnUrl"])) ? $_POST["antigro_create_design_returnUrl"]."?antigro_product_id=".$_POST["antigro_product_id"] : "",
                "volume" => (!empty($_POST["antigro_create_design_volume"])) ? $_POST["antigro_create_design_volume"] : $antigro_volume,
                "orderId" =>  $antigro_order_id . time(),
                "externalData" => [
                    "offerId" => (!empty($_POST["antigro_create_design_externalDataOfferId"])) ? $_POST["antigro_create_design_externalDataOfferId"] : "",
                    "buyerId" => (!empty($_POST["antigro_create_design_externalDataBuyerId"])) ? $_POST["antigro_create_design_externalDataBuyerId"] : "",
                    "nameAndSurname"  => (!empty($_POST["antigro_create_design_externalDataNameAndSurname"])) ? $_POST["antigro_create_design_externalDataNameAndSurname"] : ""
                ]
            ];

            // Set up cURL to send a POST request
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => $api_url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($post_data),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $jwt_token
                ],
            ]);

            // Execute the cURL request
            $response = curl_exec($curl);

            // Check for cURL errors
            if (curl_error($curl)) {
                $error = curl_error($curl);
                echo 'cURL error: '; var_dump($error); echo '<br/><hr/><br/>';
            } else {
                $response = json_decode($response, true);
                // var_dump($response); echo '<br/><hr/><br/>';
                // Client Design ID URL
                $redirect = get_antigro_clientDesignId_URL();
                $redirect = $redirect . "?clientDesignId=" . $response["id"];
                // var_dump($redirect); echo '<br/><hr/><br/>';
                echo '<script>window.location.href = "'.$redirect.'"</script>';
                wp_redirect($redirect);
            }

            // Close the cURL session
            curl_close($curl);
        } else {
            echo 'cURL is not available. Please make sure it is enabled on your server.';
        }
    }
    // Create Design

    // GET clientDesignId && volumeSum
    if( isset($_GET["clientDesignId"]) && isset($_GET["volumeSum"])) {
        if( !empty($_GET["clientDesignId"]) && !empty($_GET["volumeSum"]) ) {
            if (function_exists('WC')) {
                $clientDesignId = $_GET["clientDesignId"];
                $volumeSum = $_GET["volumeSum"];
                $antigro_product_id = $_GET["antigro_product_id"];

                // var_dump($clientDesignId); var_dump($volumeSum); var_dump($antigro_product_id); exit;

                // Get Design
                if( !empty($clientDesignId) ) {

                    $jwt_token = get_antigro_jwt_token(); // Get JWT Token
                    $basic_url = get_antigro_basic_url(); // Basic URL for signing the JWT token
                    $api_url = $basic_url . '/api/partner-backend/designs/' . $clientDesignId;

                    $curl = curl_init();

                    curl_setopt_array($curl, array(
                        CURLOPT_URL => $api_url,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING => '',
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 0,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => 'GET',
                        CURLOPT_HTTPHEADER => [ 'Authorization: Bearer ' . $jwt_token ],
                    ));

                    $response = curl_exec($curl);

                    curl_close($curl);

                    $antigro_design_data = base64_encode($response);

                    $response = json_decode($response, true);

                    if( $response["status"] == "ACCEPTED" ) {

                        if(!empty($antigro_product_id)) {

                            // Check if there are items already in the cart // Remove existing items from the cart
                            if (WC()->cart->get_cart_contents_count() > 0) {

                                $cart_item_key = WC()->cart->generate_cart_id($antigro_product_id);
                                $cart_item_data = array( 'key' => $cart_item_key, 'clientDesignId' => $clientDesignId, 'volumeSum' => $volumeSum, 'antigro_design_data' => $antigro_design_data ); // Add the item data in cart
                                WC()->cart->add_to_cart($antigro_product_id, 1, 0, array(), $cart_item_data); // Add the product to the cart

                                /*
                                // If needed, you can restore the previously stored cart items
                                if (is_array($current_cart_items) && count($current_cart_items) > 0) {
                                    foreach ($current_cart_items as $cart_item_key => $cart_item) {
                                        $cart_item['cart_item_data']['key'] = $cart_item_key;
                                        WC()->cart->add_to_cart($cart_item['product_id'], $cart_item['quantity'], $cart_item['variation_id'], $cart_item['variation'], $cart_item);
                                    }
                                }
                                */

                            } else {
                                $cart_item_key = WC()->cart->generate_cart_id($antigro_product_id);
                                $cart_item_data = array( 'key' => $cart_item_key, 'clientDesignId' => $clientDesignId, 'volumeSum' => $volumeSum, 'antigro_design_data' => $antigro_design_data ); // Add the item data in cart
                                WC()->cart->add_to_cart($antigro_product_id, 1, 0, array(), $cart_item_data); // Add the product to the cart    
                            }

                            if( is_user_logged_in() ) {
                                /*
                                $redirect = "?redirect=checkout";
                                $redirect = wc_get_checkout_url();
                                echo '<script>window.location.href = "'.$redirect.'";</script>';
                                wp_redirect($redirect); exit; // Redirect to the checkout page
                                */
                            }
                        }
        
                    } else {

                        if( is_user_logged_in() ) {
                            $redirect = get_antigro_clientDesignId_URL();
                            $redirect = $redirect . "?clientDesignId=" . $clientDesignId;
                            echo '<script>window.location.href = "'.$redirect.'"</script>';
                            wp_redirect($redirect);
                        }

                    }

                }
                // Get Design

            } else {
                // If WooCommerce is not active, provide an error message or alternative action
                echo "WooCommerce is not installed or activated.";
            }
        }
    }
    // GET clientDesignId && volumeSum

    // POST antigro_options_submit
    if(isset($_POST["antigro_options_submit"])) {

        if(!empty($_POST["antigro_basic_url"])) { update_option("antigro_basic_url", $_POST["antigro_basic_url"]); }

        if(!empty($_POST["antigro_secret_key"])) { update_option("antigro_secret_key", $_POST["antigro_secret_key"]); }

        if(!empty($_POST["antigro_clientDesignId_URL"])) { update_option("antigro_clientDesignId_URL", $_POST["antigro_clientDesignId_URL"]); }

        if(!empty($_POST["antigro_basic_url"]) && !empty($_POST["antigro_secret_key"]) && !empty($_POST["antigro_clientDesignId_URL"])) {
            update_option("antigro_status", "yes");
        }

        if(!empty($_POST["antigro_sellerid"])) { update_option("antigro_sellerid", $_POST["antigro_sellerid"]); }
        if(!empty($_POST["antigro_brandid"])) { update_option("antigro_brandid", $_POST["antigro_brandid"]); }
        if(!empty($_POST["antigro_color"])) { update_option("antigro_color", $_POST["antigro_color"]); }
        if(!empty($_POST["antigro_volume"])) { update_option("antigro_volume", $_POST["antigro_volume"]); }
        if(!empty($_POST["antigro_order_id"])) { update_option("antigro_order_id", $_POST["antigro_order_id"]); }

    }
    // POST antigro_options_submit

    // POST antigro_disconnect_submit
    if(isset($_POST["antigro_disconnect_submit"])) {
        update_option("antigro_status", "no");
    }
    // POST antigro_disconnect_submit

} add_action("init", "init_antigro_post");

function antigro_wp_head_css() { ?><style>
    #per-meter-dis { display: none; }
    .antigro_order_id { font-size: 10px !important; }
    .wc-item-meta { display: none; }
</style>
<script>
    jQuery(document).ready(function() {
        if(jQuery(".antigro_order_id_clear").length > 0) {
            jQuery(".antigro_order_id_clear").each(function() {
                var $this = jQuery(this);
                $this.next().next().remove();
            });
        }
    });
</script>
<?php } add_action("wp_head", "antigro_wp_head_css");

function antigro_admin_head_css() { ?><style>
    p { word-wrap: break-word; }
    #woocommerce-order-items .woocommerce_order_items_wrapper table.woocommerce_order_items table.display_meta tr:nth-child(3) {
    display: none !important;
}
</style><?php } add_action("admin_head", "antigro_admin_head_css");

/*
function antigro_wp_footer_redirect() { if(!empty($_GET["clientDesignId"]) && !empty($_GET["volumeSum"])) { ?>
    <script>
        jQuery(document).ready(function() {
            setTimeout(() => {
                window.location.href = "<?php echo wc_get_checkout_url(); ?>";
            }, 1000);
        });
    </script>
<?php } } add_action("wp_footer", "antigro_wp_footer_redirect"); */
    
function display_custom_data_on_checkout($item_name, $cart_item, $cart_item_key) {
    if (isset($cart_item['clientDesignId']) && isset($cart_item['antigro_design_data'])) {
        $clientDesignId = $cart_item['clientDesignId'];
        $antigro_design_data = $cart_item['antigro_design_data'];
        if(!empty($antigro_design_data)) {
            $antigro_design_data = base64_decode($antigro_design_data);
            $antigro_design_data = json_decode($antigro_design_data, true);
            if($antigro_design_data["productCode"] == "inkfusedtfprints-dtf-metersheet") {
                $item_name .= '<style>#per-meter-dis { display: block !important; }</style>';
            }
            if($antigro_design_data["id"]) {
                $edit_designer_url = get_antigro_clientDesignId_URL();
                $edit_designer_url = $edit_designer_url . "?clientDesignId=" . $antigro_design_data["id"];
                $item_name .= "<br/>";
                $item_name .= "<span class='antigro_order_id'>".$antigro_design_data["id"]."</span>";
                $item_name .= "<br/>";
                $item_name .= '<a href="'.$edit_designer_url.'">Preview</a>';
                $item_name .= "<br/>";
            }
            if(is_array($antigro_design_data["projectParameters"]) && count($antigro_design_data["projectParameters"]) > 0 ) {
                foreach($antigro_design_data["projectParameters"] as $k => $v) {
                    $item_name .= '<img width="100" height="100" src="'.$v["thumbUrl"].'" style="max-width: 100px; max-height: 100px; width: auto; height: auto; border: 1px solid #000; margin-right: 5px; margin-top: 10px; float: left;" />';
                }
                $item_name .= '<div class="antigro_order_id_clear" style="clear: both;"></div>';
            }
        }

        // $item_name .= "<br/>";
        // $item_name .= "<strong>Client Design ID:</strong>";
        // $item_name .= $clientDesignId;
        // $item_name .= "<br/>";
        // $item_name .= "<strong>Volume Sum:</strong>";
        // $item_name .= $volumeSum;
    }
return $item_name; }
add_filter('woocommerce_cart_item_name', 'display_custom_data_on_checkout', 10, 3);
add_filter('woocommerce_order_item_name', 'display_custom_data_on_checkout', 10, 3);

function save_custom_data_to_order_item_meta($item_id, $values, $cart_item_key) {
    if (isset($values['clientDesignId'])) {
        wc_add_order_item_meta($item_id, 'clientDesignId', $values['clientDesignId']);
    }
    if (isset($values['volumeSum'])) {
        wc_add_order_item_meta($item_id, 'volumeSum', $values['volumeSum']);
    }
    if (isset($values['antigro_design_data'])) {
        wc_add_order_item_meta($item_id, 'antigro_design_data', $values['antigro_design_data']);
    }
} add_action('woocommerce_add_order_item_meta', 'save_custom_data_to_order_item_meta', 10, 3);

/*
// Display clientDesignId and volumeSum on the order confirmation page
function display_custom_data_on_thankyou($order_id) {
    if ( is_checkout() && !empty( is_wc_endpoint_url('order-received') ) ) {
        $order = wc_get_order($order_id);
        
        $clientDesignId = "";

        // Get the order items
        $order_items = $order->get_items();
        foreach ($order_items as $item_id => $item) {
            // Get the item meta for the current item
            $item_meta = wc_get_order_item_meta($item_id);

            // Get the clientDesignId from the item meta
            $clientDesignId = isset($item_meta['clientDesignId']) ? $item_meta['clientDesignId'][0] : '';

            // Do something with the clientDesignId
            if (!empty($clientDesignId)) {
                ?><script>alert("<?php echo "clientDesignId:" . $clientDesignId; ?>");</script><?php
            }
        }

        // Update ClientDesign
        if( !empty($clientDesignId) ) {

            $jwt_token = get_antigro_jwt_token(); // Get JWT Token
            $basic_url = get_antigro_basic_url(); // Basic URL for signing the JWT token
            $api_url = $basic_url . '/api/partner-backend/designs/' . $clientDesignId;

            $curl = curl_init();
            
            curl_setopt_array($curl, array(
                CURLOPT_URL => $api_url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'PATCH',
                CURLOPT_POSTFIELDS =>'{
                    "status" : "PAID",
                    "price" : {
                        "net" : 9.99,
                        "tax" : 23,
                        "currency" : "PLN"
                    },
                    "orderId": "CDE1234"
                }',
                CURLOPT_HTTPHEADER => array( 'Content-Type: application/json', 'Authorization: Bearer ' . $jwt_token ),
            ));
            
            $response = curl_exec($curl);
            
            curl_close($curl);

            ?><script>alert('<?php echo "response: " . $response . "Client Design ID: " . $clientDesignId . " -- Volume Sum: " . $volumeSum; ?>');</script><?php

        } else {
            ?><script>alert("<?php echo "Client Design ID Not Found"; ?>");</script><?php
        }
        // Update ClientDesign
    }
} add_action('woocommerce_thankyou', 'display_custom_data_on_thankyou', 10, 1);
*/

// Hook into WooCommerce order status change to "Paid"
function antigro_custom_action_on_order_paid($order_id) {
    // Get the order object
    $order = wc_get_order($order_id);

    // Get order items
    $order_items = $order->get_items();

    // Loop through order items to retrieve item meta
    foreach ($order_items as $item_id => $item_data) {
        // Get item meta (e.g., 'clientDesignId')
        $clientDesignId = wc_get_order_item_meta($item_id, 'clientDesignId', true);

        // Do something with $clientDesignId
        if (!empty($clientDesignId)) {
            // Your custom actions here
            // For example, log or process the clientDesignId
            // error_log('Client Design ID: ' . $clientDesignId);

            // Get the order item quantity
            $quantity = $item_data->get_quantity();

            // Get the order total price
            $net = $order->get_total();

            // Get the order total tax
            $tax = $order->get_total_tax();

            // Get the currency
            $currency = $order->get_currency();

            $jwt_token = get_antigro_jwt_token(); // Get JWT Token
            $basic_url = get_antigro_basic_url(); // Basic URL for signing the JWT token
            $api_url = $basic_url . '/api/partner-backend/designs/' . $clientDesignId;

            $curl = curl_init();
            
            curl_setopt_array($curl, array(
                CURLOPT_URL => $api_url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'PATCH',
                CURLOPT_POSTFIELDS =>'{
                    "status" : "PAID",
                    "price" : {
                        "net" : "'.$net.'",
                        "tax" : "'.$tax.'",
                        "currency" : "'.$currency.'"
                    },
                    "orderId": "'.$order_id.'-'.$quantity.'-'.$clientDesignId.'"
                }',
                CURLOPT_HTTPHEADER => array( 'Content-Type: application/json', 'Authorization: Bearer ' . $jwt_token ),
            ));
            
            $response = curl_exec($curl);
            
            curl_close($curl);
        }
    }
}
add_action('woocommerce_order_status_paid', 'antigro_custom_action_on_order_paid');
add_action('woocommerce_order_status_processing', 'antigro_custom_action_on_order_paid', 10, 1);

// Add a custom admin menu
function antigro_designer_menu() {
    add_menu_page(
        'Antigro Designer', // Page title
        'Antigro Designer', // Menu title
        'manage_options', // Capability required to access
        'antigro-designer', // Menu slug
        'antigro_designer_page' // Callback function to display the page
    );
} add_action('admin_menu', 'antigro_designer_menu'); // Hook to add the menu

// Callback function to display the admin page
function antigro_designer_page() { ?>
    <div class="wrap">

        <h2><?php _e("Antigro Designer"); ?></h2>

        <style>
            .antigro__designer_form { max-width: 600px; background: #fff; margin: 20px 0; padding: 20px; }

            .antigro__designer--row { display: grid; grid-template-columns: 1fr; grid-gap: 30px; }
            .antigro__designer--input { width: 100%; }
        </style>

        <form class="antigro__designer_form" method="post" action="">
            <h3>Status: <span style="color: <?php echo (get_option("antigro_status") == "yes") ? "#008000" : "#FF6600"; ?>; font-style: italic;"><?php echo (get_option("antigro_status") == "yes") ? "Active" : "Not Active"; ?></span></h3>
            <?php if(get_option("antigro_status") == "yes") { ?>
                <p>Want to deactivate the key for any reason? <input type="submit" name="antigro_disconnect_submit" class="button button-primary" value="Disconnect" /></p>
            <?php } ?>
        </form>

        <?php if(get_option("antigro_status") != "yes") { ?>

            <form class="antigro__designer_form" action="" method="post">
                <div class="antigro__designer--row">

                    <div class="antigro__designer--col">
                        <label for="antigro_basic_url"><?php _e("API Basic URL"); ?></label>
                        <input type="text" id="antigro_basic_url" name="antigro_basic_url" class="antigro__designer--input" value="<?php echo (!empty(get_option("antigro_basic_url"))) ? get_option("antigro_basic_url") : ""; ?>" placeholder="<?php _e("API Basic URL"); ?>" required="required" />
                    </div>
                    
                    <div class="antigro__designer--col">
                        <label for="antigro_secret_key"><?php _e("API Secret Key"); ?></label>
                        <input type="password" id="antigro_secret_key" name="antigro_secret_key" class="antigro__designer--input" value="<?php echo (!empty(get_option("antigro_secret_key"))) ? get_option("antigro_secret_key") : ""; ?>" placeholder="<?php _e("API Secret Key"); ?>" required="required" />
                    </div>
    
                    <div class="antigro__designer--col">
                        <label for="antigro_clientDesignId_URL"><?php _e("API Client Design ID URL"); ?></label>
                        <input type="text" id="antigro_clientDesignId_URL" name="antigro_clientDesignId_URL" class="antigro__designer--input" value="<?php echo (!empty(get_option("antigro_clientDesignId_URL"))) ? get_option("antigro_clientDesignId_URL") : ""; ?>" placeholder="<?php _e("API Client Design ID URL"); ?>" required="required" />
                    </div>

                    <div class="antigro__designer--col">
                        <label for="antigro_sellerid"><?php _e("Antigro Seller ID"); ?></label>
                        <input type="text" id="antigro_sellerid" name="antigro_sellerid" class="antigro__designer--input" value="<?php echo (!empty(get_option("antigro_sellerid"))) ? get_option("antigro_sellerid") : ""; ?>" placeholder="<?php _e("Antigro Seller ID"); ?>" required="required" />
                    </div>

                    <div class="antigro__designer--col">
                        <label for="antigro_brandid"><?php _e("Antigro Brand ID"); ?></label>
                        <input type="text" id="antigro_brandid" name="antigro_brandid" class="antigro__designer--input" value="<?php echo (!empty(get_option("antigro_brandid"))) ? get_option("antigro_brandid") : ""; ?>" placeholder="<?php _e("Antigro Brand ID"); ?>" required="required" />
                    </div>

                    <div class="antigro__designer--col">
                        <label for="antigro_color"><?php _e("Antigro Color"); ?></label>
                        <input type="text" id="antigro_color" name="antigro_color" class="antigro__designer--input" value="<?php echo (!empty(get_option("antigro_color"))) ? get_option("antigro_color") : ""; ?>" placeholder="<?php _e("Antigro Color"); ?>" />
                    </div>

                    <div class="antigro__designer--col">
                        <label for="antigro_volume"><?php _e("Antigro Volume"); ?></label>
                        <input type="text" id="antigro_color" name="antigro_volume" class="antigro__designer--input" value="<?php echo (!empty(get_option("antigro_volume"))) ? get_option("antigro_volume") : ""; ?>" placeholder="<?php _e("Antigro Volume"); ?>" />
                    </div>

                    <div class="antigro__designer--col">
                        <label for="antigro_order_id"><?php _e("Antigro Order ID"); ?></label>
                        <input type="text" id="antigro_order_id" name="antigro_order_id" class="antigro__designer--input" value="<?php echo (!empty(get_option("antigro_order_id"))) ? get_option("antigro_order_id") : ""; ?>" placeholder="<?php _e("Antigro Order ID"); ?>" />
                    </div>

                    <div class="antigro__designer--row">
                        <input type="submit" class="button button-primary" id="antigro_options_submit" name="antigro_options_submit" value="<?php _e("Activate Designer"); ?>" />
                    </div>
    
                </div>
            </form>
        <?php } ?>

        <?php /*
        <div class="antigro_create_design" id="antigro_create_design">
            <h2>Create a Design</h2>
            <?php echo do_shortcode('[antigro_button product_id="6947"]'); ?>
        </div>
        */ ?>

        <?php /*
        <?php
        // Get Design
        if(isset($_POST["antigro_get_design_submit"])) {

            $antigro_get_design_Id = (!empty($_POST["antigro_get_design_Id"])) ? $_POST["antigro_get_design_Id"] : "";
            if( !empty($antigro_get_design_Id) ) {

                $jwt_token = get_antigro_jwt_token(); // Get JWT Token
                $basic_url = get_antigro_basic_url(); // Basic URL for signing the JWT token
                $api_url = $basic_url . '/api/partner-backend/designs/' . $antigro_get_design_Id;

                $curl = curl_init();

                curl_setopt_array($curl, array(
                    CURLOPT_URL => $api_url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'GET',
                    CURLOPT_HTTPHEADER => [ 'Authorization: Bearer ' . $jwt_token ],
                ));

                $response = curl_exec($curl);

                curl_close($curl);

                print_r($response);

            }
        }
        // Get Design
        ?>

        <div class="antigro_get_design" id="antigro_get_design">
            <h2>Get a Design</h2>
            <form action="" method="post">
                <div>
                    <label for="antigro_get_design_Id">Design Id</label>
                    <input type="text" id="" name="antigro_get_design_Id" value="d25d65fc95e8419b81f71104f11d7d22" />
                </div>
                <div>
                    <input type="submit" class="button button-primary" id="antigro_get_design_submit" name="antigro_get_design_submit" value="Get Design" />
                </div>
            </form>
        </div>
        */ ?>
        
    </div>
<?php }

// Shortcode Button [antigro_button product_id="6947"]
function antigro_create_design_form_shortcode($atts) {
    // Extract shortcode attributes
    extract(shortcode_atts(array(
        'product_id' => '', // Default product ID 6947
    ), $atts));

    global $product;

    $product_id = (!empty($atts["product_id"])) ? esc_attr($atts["product_id"]) : $product->get_id();
    $productCode = get_post_meta($product_id, 'antigro_productCode', true);

    ob_start(); // Output the HTML form ?>

    <style>
        .antigro_create_design_submit { width: 100%; }
        .antigro_designer_button_toggle_divider,
        .antigro_button_divider { text-align: center; padding: 10px; }
        .antigro_designer_button_toggle { background: #000; color: #FFF; padding: 10px; text-align: center; cursor: pointer; }
        .antigro_designer_button_toggle_divider,
        /*.pewc-group-wrap { display: none; }*/
    </style>

    <form action="" id="antigro_designer_button" method="post" target="_blank">
        <input type="hidden" name="antigro_create_design_sellerId" value="<?php echo (!empty(get_option("antigro_sellerid"))) ? get_option("antigro_sellerid") : ""; ?>" />
        <input type="hidden" name="antigro_create_design_brandId" value="<?php echo (!empty(get_option("antigro_brandid"))) ? get_option("antigro_brandid") : ""; ?>" />
        <input type="hidden" name="antigro_create_design_productParametersColor" value="<?php echo (!empty(get_option("antigro_color"))) ? get_option("antigro_color") : ""; ?>" />
        <input type="hidden" name="antigro_create_design_volume" value="<?php echo (!empty(get_option("antigro_volume"))) ? get_option("antigro_volume") : 1; ?>" />
        <input type="hidden" name="antigro_create_design_productCode" value="<?php echo $productCode; ?>" />
        <input type="hidden" name="antigro_create_design_templateBindingType" value="dtftransfer_custom" />
        <input type="hidden" name="antigro_create_design_templateId" value="emptyTransparent" />
        <input type="hidden" name="antigro_create_design_returnUrl" value="<?php echo get_the_permalink(); // site_url("/"); ?>" />
        <input type="hidden" name="antigro_product_id" value="<?php echo $product_id; ?>" />
        <?php /*
        <input type="hidden" name="antigro_create_design_externalDataOfferId" value="XYZ" />
        <input type="hidden" name="antigro_create_design_externalDataBuyerId" value="1234" />
        <input type="hidden" name="antigro_create_design_externalDataNameAndSurname" value="John Kowalski" />
        */ ?>
        <input type="submit" class="antigro_create_design_submit" id="antigro_create_design_submit" name="antigro_create_design_submit" value="Use Our Online Sheet Designer" />
        <div class="antigro_button_divider"><?php _e("-- OR --"); ?></div>
    </form>

    <!--<div id="antigro_designer_button_toggle" class="antigro_designer_button_toggle" onclick="antigro_designer_button_toggle();">Upload Your Artwork</div>
    <div class="antigro_designer_button_toggle_divider"><?php //_e("-- OR --"); ?></div>-->

    <script>
       /* function antigro_designer_button_toggle() {
            var btn_txt = jQuery("#antigro_designer_button_toggle").text();
            if(btn_txt == "Upload Your Artwork") {
                jQuery("#antigro_designer_button_toggle").text("Build your own Gang Sheet");
                jQuery("#antigro_designer_button").hide();
                jQuery(".antigro_designer_button_toggle_divider").show();
                jQuery(".pewc-group-wrap").show();
            } else {
                jQuery("#antigro_designer_button_toggle").text("Upload Your Artwork");
                jQuery("#antigro_designer_button").show();
                jQuery(".antigro_designer_button_toggle_divider").hide();
                jQuery(".pewc-group-wrap").hide();
            }
        }*/
    </script>

<?php $output = ob_get_contents(); ob_get_clean(); return $output; } add_shortcode('antigro_button', 'antigro_create_design_form_shortcode');

// Add a custom metabox for WooCommerce product
function add_antigro_options_metabox() {
    add_meta_box(
        'antigro_options_metabox', // Unique ID
        'Antigro Options', // Metabox Title
        'antigro_options_metabox_callback', // Callback function
        'product', // Post type (product for WooCommerce)
        'normal', // Context (normal, advanced, side)
        'high' // Priority (high, core, default, low)
    );
} add_action('add_meta_boxes', 'add_antigro_options_metabox');

// Callback function to display the metabox content
function antigro_options_metabox_callback($post) {
    // Get the current value of the product code
    $productCode = get_post_meta($post->ID, 'antigro_productCode', true);
    ?>
    <label for="antigro_productCode"><?php _e("Antigro Product Code:"); ?></label>
    <br/><br/>
    <input type="text" id="antigro_productCode" name="antigro_productCode" value="<?php echo esc_attr($productCode); ?>" style="width: 100%;" />
    <?php
}

// Save the metabox data
function save_antigro_productCode_metabox($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return $post_id;

    if (!current_user_can('edit_post', $post_id)) return $post_id;

    $productCode = sanitize_text_field($_POST['antigro_productCode']);
    update_post_meta($post_id, 'antigro_productCode', $productCode);
} add_action('save_post', 'save_antigro_productCode_metabox');

// Add PDFs to list of permitted mime types
function my_prefix_pewc_get_permitted_mimes( $permitted_mimes ) {
    // Add PDF to the list of permitted mime types
    // $permitted_mimes['pdf'] = "application/pdf";
    // Remove a mime type - uncomment the line below if you wish to prevent JPGs from being uploaded
    unset( $permitted_mimes['jpg|jpeg|jpe'] );
    unset( $permitted_mimes['gif'] );
    return $permitted_mimes;
} add_filter( 'pewc_permitted_mimes', 'my_prefix_pewc_get_permitted_mimes' );

// Add a filter to modify the display of order item meta keys
function remove_order_item_meta($display_value, $meta, $item) {
    $meta_key_to_remove = array('clientDesignId', 'volumeSum', 'antigro_design_data');
    if (in_array($meta->key, $meta_key_to_remove)) {
        if($meta->key == 'clientDesignId' && !empty($_GET["post"]) && !empty($_GET["action"]) && $_GET["action"] == "edit") {

        } else {
            return '';
        }
    } // Empty string will remove the meta key and value
return $display_value; }
add_filter('woocommerce_order_item_display_meta_key', 'remove_order_item_meta', 10, 3);
add_filter('woocommerce_order_item_display_meta_value', 'remove_order_item_meta', 10, 3);