<?php get_header(); ?>

<div class="container">
  <div class="single-breadcrumb">
      <?php custom_breadcrumbs(); ?>
  </div>

  <?php echo (get_post_meta(get_the_ID(), 'reklamlari_gizle', true) == 0 ? reklam('govde_ust_reklam') : NULL); ?>

  <?php if( !empty(get_query_var('kur')) ) : ?>
    <?php get_template_part("sablon/finans-single-kur"); ?>
  <?php elseif( isset($_GET['maden']) || !empty($_GET['maden']) ) : ?>
    <?php get_template_part("sablon/finans-single-maden"); ?>
  <?php else:  ?>
    <?php get_template_part("sablon/finans"); ?>
  <?php endif; ?>
</div>

<?php get_footer(); ?>