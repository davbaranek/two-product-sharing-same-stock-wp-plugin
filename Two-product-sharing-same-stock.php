<?php
/**
 * Plugin Name: Two products sharing same stock
 * Plugin URI: https://www.davidbaranek.com/
 * Description: Simple plugin to connect two products to share same stock. After you buy one piece of one product, stock of a connected product will be also one piece less.
 * Version: 1.0
 * Author: David Baranek
 * Author URI: https://www.davidbaranek.com/
 */

//PAIRING PRODUCTS IN WP-ADMIN

add_action('woocommerce_product_options_general_product_data', 'connected_products_field');
add_action('woocommerce_process_product_meta', 'save_connected_products');

//Showing custom product field for pairing products

function connected_products_field() {
    global $woocommerce, $post;
    echo '<div class="product_custom_field">';
    woocommerce_wp_text_input(
        array(
            'id' => '_custom_product_number_field',
            'placeholder' => 'ID of paired product',
            'label' => __('ID of paired product to reduce stock number', 'woocommerce'),
            'type' => 'number',
            'custom_attributes' => array(
                'step' => 'any',
                'min' => '0'
            )
        )
    );

    if (get_post_meta(get_the_ID(), '_custom_product_number_field', true) !== "") {
        if (wc_get_product(get_post_meta(get_the_ID(), '_custom_product_number_field', true))) {
                $sparovany_produkt = wc_get_product( get_post_meta(get_the_ID(), '_custom_product_number_field', true) );
                echo '<p class="form-field">Paired products:<b><a href="/wp-admin/post.php?post='.$sparovany_produkt->get_id().'&action=edit"> '.$sparovany_produkt->get_title().'</a></b></p>';
                global $post;
                $tento_produkt_id = $post->ID;
                if ($tento_produkt_id !== $sparovany_produkt->get_id()) {
                    if (get_post_meta($sparovany_produkt->get_id(), '_custom_product_number_field', true) == $tento_produkt_id) {
                        echo '<p class="form-field">✔️Products are paired✔️</p>';
                    }
                } else {
                    echo '<p class="form-field">❌ You put same product ❌</p>';
                } 
        } else {
            echo '<p class="form-field">❌ A product does not exist ❌</p>';
        }  
    } 
    echo '</div>';
}

//Saving of paired product

function save_connected_products($post_id)
{
    $woocommerce_custom_product_number_field = $_POST['_custom_product_number_field'];
    if (!empty($woocommerce_custom_product_number_field)) {
        update_post_meta($post_id, '_custom_product_number_field', esc_attr($woocommerce_custom_product_number_field));
    }
}

//AN ACTION AFTER ORDER

//An user will order a product and if the product has a pair in the system, the paired product stock will be subtracted

add_action('woocommerce_thankyou', 'subtract_stock', 10, 1);

function subtract_stock( $order_id ) {
    if ( ! $order_id )
        return;
    if( ! get_post_meta( $order_id, '_thankyou_action_done', true ) ) {
        $order = wc_get_order( $order_id );
        $order_key = $order->get_order_key();
        $order_key = $order->get_order_number();
        foreach ( $order->get_items() as $item_id => $item ) {
            $product = $item->get_product();
            $product_id = $product->get_id();
            $prepojeny_produkt = get_post_meta( $product_id, '_custom_product_number_field', true );
            wc_update_product_stock( $prepojeny_produkt, $item->get_quantity(), 'decrease', '' );
        }
        $order->update_meta_data( '_thankyou_action_done', true );
        $order->save();
    }
}