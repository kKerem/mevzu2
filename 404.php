<?php get_header(); ?>

	<div class="container">
		<div class="notfound py-3 py-md-5">
		<img src="<?php bloginfo('template_url');?>/img/404.png" alt="404" class="opacity-25 mb-3">
			<h2><?php esc_html_e( 'Sayfa bulunamadı', 'mevzu' ); ?></h2>
			<p><?php esc_html_e( 'Bu konumda hiçbir şey bulunamamış gibi görünüyor.', 'mevzu' ); ?></p>
			<a href="<?php bloginfo('url'); ?>" class="btn btn-primary btn-lg">Anasayfa</a>
		</div>
	</div>

<?php
get_footer();
