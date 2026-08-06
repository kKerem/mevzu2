<?php
/**
 * The template for displaying comments
 *
 * This is the template that displays the area of the page that contains both the current comments
 * and the comment form.
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package mevzu2
 */

/*
 * If the current post is protected by a password and
 * the visitor has not yet entered the password we will
 * return early without loading the comments.*/
 
if ( post_password_required() ) {
	return;
}
?>
<div id="comments" class="comments-area tema-widget bg-white shadow-sm rounded-3 mb-3 mb-lg-4 mx-2 mx-md-0">

	<?php
	if (is_user_logged_in()) {
		$current_user = wp_get_current_user();
		$avatar = get_avatar($current_user->ID, 30); // You can adjust the size (30) as needed

		$author_profile_url = get_author_posts_url($current_user->ID);
		$author_id = $current_user->ID;
		$user_roles = (array) get_the_author_meta('roles', $author_id); // Rol dizisini zorunlu olarak array'e dönüştürüyoruz

		$user_avatar_url = mevzu_get_user_avatar_url($author_id);
		if ($user_avatar_url) {
			$avatar_url = '<img class="w-42 h-42 rounded-circle m-0 object-fit-cover" src="'. esc_url($user_avatar_url) .'" alt="' . esc_attr($current_user->display_name) . '">';
		}
		else {
			$avatar_url = m_default('avatar');
		}
	} else {
		$avatar_url = get_avatar('', 30); // Default avatar for non-logged in users
	}
	comment_form(array(
		'fields' => array(
			'author' => '<div class="comment-form-author pb-3"><input class="form-control" id="author" name="author" type="text" value="' . esc_attr( $commenter['comment_author'] ) . '" size="30" placeholder="Adınız" required></div>',
			'email'  => '<div class="comment-form-email pb-3"><input class="form-control" id="email" name="email" type="email" value="' . esc_attr( $commenter['comment_author_email'] ) . '" size="30" placeholder="E-posta" required></div>',
			'url'    => '<div class="comment-form-url pb-3"><input class="form-control" id="url" name="url" type="url" value="' . esc_attr( $commenter['comment_author_url'] ) . '" size="30" placeholder="Website"></div>',
			'cookies' => '<div class="comment-form-cookies-consent form-check pb-3"><input id="wp-comment-cookies-consent" class="form-check-input" name="wp-comment-cookies-consent" type="checkbox" value="yes" />' . '<label class="form-check-label" for="wp-comment-cookies-consent">Yorum yaparken adımı, e-posta adresimi ve web sitemi bu tarayıcıda saklayın.</label></div>',
		),
		'title_reply'          => 'Yorum Yaz',
		'title_reply_to'       => 'Bir Yanıt Yazın',
		'cancel_reply_link'    => 'Yanıtı İptal Et',
		'label_submit'         => 'Yorum Gönder',
		'class_submit' => 'btn btn-primary px-3 fw-semibold',
		'submit_button' => '<div class="row align-items-center"><div class="col-auto"><button name="%1$s" type="submit" id="%2$s" class="%3$s">%4$s</button></div><div class="col-12 col-lg mt-3 mt-lg-0 comment-not small d-none">Gönderdiğiniz yorum moderasyon ekibi tarafından incelendikten sonra yayınlanacaktır.</div></div>',
		'must_log_in'          => '<div class="must-log-in">Yorum yapabilmek için <a href="' . esc_url( home_url('/hesabim/giris') ) . '">giriş yapmalısınız</a>.</div>',
		'logged_in_as'         => '<div class="row align-items-center comment-form-author pb-3"><div class="col-auto pe-0">'.$avatar_url.'</div><div class="col logged-in-as fw-semibold">'.wp_get_current_user()->display_name.' </div><div class="col-auto"><a class="btn btn-outline-primary" href="' . wp_logout_url( get_permalink() ) . '" title="Çıkış Yap">Çıkış yap</a></div></div>',
		'comment_notes_before' => '',
		'comment_notes_after'  => '',
		
		'comment_field'        => '<textarea placeholder="Yorumunuzu buraya yazın..." class="custom-comment-field form-control" id="comment" name="comment" cols="45" rows="4" aria-required="true"></textarea>',
		
		'format'               => 'xhtml',
		// Diğer argümanlar
	));

	// You can start editing here -- including this comment!
	if ( have_comments() ) :
		?>
		<div class="mb-3 border-bottom mt-lg-4 px-3">
			<div class="row justify-content-between">
				<div class="col-auto">
					<h2 class="not-before yorumBaslik d-flex m-0 pb-2">
					<svg class="me-2" xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9h8m-8 4h6m4-9a3 3 0 0 1 3 3v8a3 3 0 0 1-3 3h-5l-5 3v-3H6a3 3 0 0 1-3-3V7a3 3 0 0 1 3-3z"></path></svg>
						 <?php
						$mevzu2_comment_count = get_comments_number();
						if ( '1' === $mevzu2_comment_count ) {
							printf(
								esc_html__( 'Yorumlar', 'mevzu2' )
							);
						} else {
							printf( 
								esc_html( _nx( '%1$s Yorum &ldquo;%2$s&rdquo;', '%1$s Yorum', $mevzu2_comment_count, 'comments title', 'mevzu2' ) ),
								number_format_i18n( $mevzu2_comment_count ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								'<span>' . wp_kses_post( get_the_title() ) . '</span>'
							);
						}
						?>
					</h2><!-- .comments-title -->
				</div>
				<div class="col-auto">
					<div class="dropdown">
						<a class="text-dark dropdown-toggle pb-2 px-1" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
						<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 20 20"><g fill="currentColor" fill-rule="evenodd" clip-rule="evenodd"><path d="M10.293 7.707a1 1 0 0 1 0-1.414l3-3a1 1 0 1 1 1.414 1.414l-3 3a1 1 0 0 1-1.414 0"/><path d="M17.707 7.707a1 1 0 0 1-1.414 0l-3-3a1 1 0 0 1 1.414-1.414l3 3a1 1 0 0 1 0 1.414"/><path d="M14 5a1 1 0 0 1 1 1v8a1 1 0 1 1-2 0V6a1 1 0 0 1 1-1m-4.293 7.293a1 1 0 0 1 0 1.414l-3 3a1 1 0 0 1-1.414-1.414l3-3a1 1 0 0 1 1.414 0"/><path d="M2.293 12.293a1 1 0 0 1 1.414 0l3 3a1 1 0 1 1-1.414 1.414l-3-3a1 1 0 0 1 0-1.414"/><path d="M6 15a1 1 0 0 1-1-1V6a1 1 0 1 1 2 0v8a1 1 0 0 1-1 1"/></g></svg>
						</a>

						<ul class="dropdown-menu">
							<li><h6 class="dropdown-header pb-0">Yorumları Sırala</h6></li>
							<li><hr class="dropdown-divider"></li>
							<li><a class="dropdown-item" href="<?php echo add_query_arg( 'comment_order', 'asc', get_permalink() ); ?>">Eskiden Yeniye</a></li>
							<li><a class="dropdown-item" href="<?php echo add_query_arg( 'comment_order', 'desc', get_permalink() ); ?>">Yeniden Eskiye</a></li>
						</ul>
					</div>
				</div>
			</div>
		</div>


		<?php
		if (get_comment_pages_count() > 1 && get_option('page_comments')) : ?>
			<nav class="custom-comments-navigation" role="navigation">
				<div class="nav-previous">
					<?php previous_comments_link(__('&larr; Eski Yorumlar')); ?>
				</div>
				<div class="nav-next">
					<?php next_comments_link(__('Yeni Yorumlar &rarr;')); ?>
				</div>
			</nav>
		<?php endif; ?>
		
		<ol class="comment-list m-0 p-3">
		<?php if ( ! function_exists( 'custom_comment_callback' ) ) {
			function custom_comment_callback($comment, $args, $depth) {
				$GLOBALS['comment'] = $comment;
				$c_user_id = $comment->user_id;
				?>
				<li <?php comment_class('mb-3'); ?> id="comment-<?php comment_ID(); ?>">
					<article class="comment d-flex">
						<div class="flex-shrink-0 mt-1">
							<?php 
							$avatar_rendered = false;
							if ( $c_user_id ) {
								$comment_avatar_url = mevzu_get_user_avatar_url($c_user_id);
								if ($comment_avatar_url) {
									echo '<img class="rounded-circle object-fit-cover me-3" src="' . esc_url($comment_avatar_url) . '" width="45" height="45" alt="' . esc_attr($comment->comment_author) . '">';
									$avatar_rendered = true;
								}
							}
							
							if (!$avatar_rendered) {
								if (function_exists('m_default')) {
									$a_url = m_default('avatar');
									$a_url = str_replace('width="100"', 'width="45"', $a_url);
									$a_url = str_replace('height="100"', 'height="45"', $a_url);
									echo str_replace('class="', 'class="rounded-circle object-fit-cover me-3 ', $a_url);
								} else {
									echo get_avatar($comment, 45, '', '', array('class' => 'rounded-circle object-fit-cover me-3', 'style' => 'width: 45px; height: 45px;'));
								}
							}
							?>
						</div>
						<div class="flex-grow-1" style="min-width: 0;">
                            <div class="d-flex align-items-center mb-1">
                                <h4 class="comment-author fs-6 mb-0 d-inline-block fw-semibold text-dark">
                                    <?php echo get_comment_author_link(); ?>
                                </h4>
                                <span class="px-1 small text-secondary">•</span>
                                <time class="comment-time small text-body">
                                    <?php echo saat(); ?>, <?php echo get_comment_time(); ?>
                                </time>
                                <?php edit_comment_link(__('Edit'), '<span class="edit-link small ps-1">', '</span>'); ?>
                            </div>
							<div class="comment-content">
								<div class="comment-text pb-2 pt-1 text-body">
									<?php comment_text(); ?>
								</div>
								<?php if ($comment->comment_approved == '0') : ?>
									<p class="comment-awaiting-moderation">
										<span class="badge bg-danger p-2">
											<?php _e('Your comment is awaiting moderation.'); ?>
										</span>
									</p>
								<?php endif; ?>
								<div class="comment-reply-link d-flex align-items-center gap-3">
									<?php if ( function_exists('mevzu_render_like_button') ) mevzu_render_like_button( get_comment_ID(), 'comment' ); ?>
									<?php comment_reply_link(array_merge($args, array(
										'depth' => $depth,
										'max_depth' => $args['max_depth']
									))); ?>
								</div>
							</div>
						</div>
					</article>
				<?php
			}
		}
		wp_list_comments(array(
			'style'       => 'ol',
			'short_ping'  => true,
			'avatar_size' => 34,
			'callback'    => 'custom_comment_callback',
		));
			?>
		</ol>

		
		<?php
		if (get_comment_pages_count() > 1 && get_option('page_comments')) : ?>
			<nav class="custom-comments-navigation" role="navigation">
				<div class="nav-previous">
					<?php previous_comments_link(__('&larr; Eski Yorumlar')); ?>
				</div>
				<div class="nav-next">
					<?php next_comments_link(__('Yeni Yorumlar &rarr;')); ?>
				</div>
			</nav>
		<?php endif; ?>
		
		<?php
		if ( ! comments_open() ) :
			?>
			<p class="no-comments text-center text-body small border-top mt-3 mb-0 py-3"><?php esc_html_e( 'Yeni yorumlara kapalı.', 'mevzu2' ); ?></p>
			<?php
		endif;

	endif; // Check for have_comments().

	
	?>

</div><!-- #comments -->