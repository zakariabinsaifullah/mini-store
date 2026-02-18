<?php
/**
 * Server-side render template for the Product Grid block.
 *
 * @package MiniStore
 *
 * @var array $attributes Block attributes passed from the render callback.
 */

defined( 'ABSPATH' ) || exit;

// Extract attributes with defaults
$columns          = isset( $attributes['columns'] ) ? absint( $attributes['columns'] ) : 3;
$products_to_show = isset( $attributes['productsToShow'] ) ? absint( $attributes['productsToShow'] ) : 6;
$order_by         = isset( $attributes['orderBy'] ) ? sanitize_text_field( $attributes['orderBy'] ) : 'date';
$order            = isset( $attributes['order'] ) ? sanitize_text_field( $attributes['order'] ) : 'DESC';
$show_price       = isset( $attributes['showPrice'] ) ? (bool) $attributes['showPrice'] : true;
$show_add_to_cart = isset( $attributes['showAddToCart'] ) ? (bool) $attributes['showAddToCart'] : true;
$show_image       = isset( $attributes['showImage'] ) ? (bool) $attributes['showImage'] : true;

// Query products
$query_args = array(
	'post_type'      => 'ms_product',
	'post_status'    => 'publish',
	'posts_per_page' => $products_to_show,
	'orderby'        => $order_by,
	'order'          => $order,
);

$products = new WP_Query( $query_args );

// Wrapper attributes
// Wrapper attributes
$gap = isset( $attributes['gap'] ) ? absint( $attributes['gap'] ) : 0;
$title_color = isset( $attributes['titleColor'] ) ? sanitize_hex_color( $attributes['titleColor'] ) : '';
$title_size = isset( $attributes['titleSize'] ) ? absint( $attributes['titleSize'] ) : 0;
$desc_color = isset( $attributes['descriptionColor'] ) ? sanitize_hex_color( $attributes['descriptionColor'] ) : ''; // Note: attribute is descriptionColor usually, but edit.js used descColor? Check Inspector.
$desc_size = isset( $attributes['descSize'] ) ? absint( $attributes['descSize'] ) : 0;
$price_color = isset( $attributes['priceColor'] ) ? sanitize_hex_color( $attributes['priceColor'] ) : '';
$price_size = isset( $attributes['priceSize'] ) ? absint( $attributes['priceSize'] ) : 0;
$btn_color = isset( $attributes['btnColor'] ) ? sanitize_hex_color( $attributes['btnColor'] ) : '';
$btn_bg_color = isset( $attributes['btnBgColor'] ) ? sanitize_hex_color( $attributes['btnBgColor'] ) : '';
$btn_label_size = isset( $attributes['btnLabelSize'] ) ? absint( $attributes['btnLabelSize'] ) : 0;
$btn_hover_color = isset( $attributes['btnHoverColor'] ) ? sanitize_hex_color( $attributes['btnHoverColor'] ) : '';
$btn_hover_bg_color = isset( $attributes['btnHoverBgColor'] ) ? sanitize_hex_color( $attributes['btnHoverBgColor'] ) : '';
$btn_label = isset( $attributes['btnLabel'] ) ? $attributes['btnLabel'] : __( 'Add to Cart', 'mini-store' );
$sale_price_color = isset( $attributes['salepriceColor'] ) ? sanitize_hex_color( $attributes['salepriceColor'] ) : '';

$style = "";
if ( $gap ) $style .= "--gap: {$gap}px;";
if ( $title_color ) $style .= "--title-color: {$title_color};";
if ( $title_size ) $style .= "--title-size: {$title_size}px;";
if ( $desc_color ) $style .= "--desc-color: {$desc_color};";
if ( $desc_size ) $style .= "--desc-size: {$desc_size}px;";
if ( $price_color ) $style .= "--price-color: {$price_color};";
if ( $price_size ) $style .= "--price-size: {$price_size}px;";
if ( $sale_price_color ) $style .= "--saleprice-color: {$sale_price_color};";
if ( $btn_color ) $style .= "--btn-color: {$btn_color};";
if ( $btn_bg_color ) $style .= "--btn-bg-color: {$btn_bg_color};";
if ( $btn_label_size ) $style .= "--btn-label-size: {$btn_label_size}px;";
if ( $btn_hover_color ) $style .= "--btn-hover-color: {$btn_hover_color};";
if ( $btn_hover_bg_color ) $style .= "--btn-hover-bg-color: {$btn_hover_bg_color};";

$wrapper_attributes = get_block_wrapper_attributes( array(
	'class' => 'columns-' . $columns,
	'style' => $style,
) );

?>
<div <?php echo $wrapper_attributes; ?>>
	<?php if ( $products->have_posts() ) : ?>
		<div class="mini-store-products">
			<?php
			while ( $products->have_posts() ) :
				$products->the_post();
				$product_id    = get_the_ID();
				$regular_price = get_post_meta( $product_id, '_ms_regular_price', true );
				$sale_price    = get_post_meta( $product_id, '_ms_sale_price', true );
				$price         = $sale_price ? $sale_price : $regular_price;
				?>
				<div class="mini-store-product">
					<?php if ( $show_image && has_post_thumbnail() ) : ?>
						<div class="product-image">
							<a href="<?php the_permalink(); ?>">
								<?php the_post_thumbnail( 'medium' ); ?>
							</a>
						</div>
					<?php endif; ?>
					
					<div class="product-details">
						<h3 class="product-title">
							<a href="<?php the_permalink(); ?>">
								<?php the_title(); ?>
							</a>
						</h3>
						
						<div class="product-excerpt">
							<?php the_excerpt(); ?>
						</div>

						<?php if ( $show_price && ! empty( $price ) ) : ?>
							<div class="product-price">
								<?php if ( $sale_price ) : ?>
									<del class="regular-price">৳<?php echo esc_html( number_format( (float) $regular_price, 2 ) ); ?></del>
									<ins class="sale-price">৳<?php echo esc_html( number_format( (float) $sale_price, 2 ) ); ?></ins>
								<?php else : ?>
									<span class="regular-price">৳<?php echo esc_html( number_format( (float) $regular_price, 2 ) ); ?></span>
								<?php endif; ?>
							</div>
						<?php endif; ?>
						
						<?php if ( $show_add_to_cart ) : ?>
							<button class="add-to-cart-button" data-product-id="<?php echo esc_attr( $product_id ); ?>">
								<?php echo esc_html( $btn_label ); ?>
							</button>
						<?php endif; ?>
					</div>
				</div>
			<?php endwhile; ?>
		</div>
	<?php else : ?>
		<div class="mini-store-no-products">
			<p><?php esc_html_e( 'No products found.', 'mini-store' ); ?></p>
		</div>
	<?php endif; ?>
</div>
<?php

wp_reset_postdata();
