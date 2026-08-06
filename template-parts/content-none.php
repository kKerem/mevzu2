<?php
/**
 * Template part for displaying a message that posts cannot be found
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package mevzu2
 */

?>

<section class="no-results not-found">
	<header class="page-header">
		<h1 class="page-title"><?php esc_html_e( 'Sonuç Bulunamadı', 'mevzu2' ); ?></h1>
	</header><!-- .page-header -->

	<div class="page-content">
		<?php
		if ( is_home() && current_user_can( 'publish_posts' ) ) :

			printf(
				'<p>' . wp_kses(
					/* translators: 1: link to WP admin new post page. */
					__( 'Ready to publish your first post? <a href="%1$s">Get started here</a>.', 'mevzu2' ),
					array(
						'a' => array(
							'href' => array(),
						),
					)
				) . '</p>',
				esc_url( admin_url( 'post-new.php' ) )
			);

		elseif ( is_search() ) :
			?>

			<p><?php esc_html_e( 'Üzgünüz, ancak arama terimlerinizle eşleşen hiçbir şey yok. Lütfen farklı anahtar kelimelerle tekrar deneyin.', 'mevzu2' ); ?></p>
			
			<form role="search" method="get" class="search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
				<div class="input-group">
					<span class="screen-reader-text"><?php echo _x( 'Arama Sonucu:', 'label' ); ?></span>
					<input type="search" class="form-control rounded" placeholder="<?php echo esc_attr_x( 'Aramak istediğiniz kelimeyi yazın...', 'placeholder' ); ?>" aria-label="Ara" aria-describedby="search-addon" value="<?php echo get_search_query(); ?>" name="s" />
					<button type="submit" class="btn btn-primary">Ara</button>
				</div>
			</form>
			<?php

		else :
			?>

			<p><?php esc_html_e( 'Görünüşe göre aradığınızı bulamıyoruz. Belki arama yardımcı olabilir.', 'mevzu2' ); ?></p>
			<form role="search" method="get" class="search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
				<div class="input-group">
					<span class="screen-reader-text"><?php echo _x( 'Arama Sonucu:', 'label' ); ?></span>
					<input type="search" class="form-control rounded" placeholder="<?php echo esc_attr_x( 'Aramak istediğiniz kelimeyi yazın...', 'placeholder' ); ?>" aria-label="Ara" aria-describedby="search-addon" value="<?php echo get_search_query(); ?>" name="s" />
					<button type="submit" class="btn btn-primary">Ara</button>
				</div>
			</form>
<?php
		endif;
		?>
	</div><!-- .page-content -->
</section><!-- .no-results -->
