<?php get_header(); ?>

	<div class="container">
		<?php get_template_part("sablon/reklamlar"); ?>
		<?php echo reklam('govde_ust_reklam'); ?>

		<?php ramazan(); ?>

		<?php get_template_part('sayfalar/page-default') ?>
	</div>

<?php get_footer();
