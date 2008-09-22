<?php $theme->display('header'); ?>
<div id="content">
	<!--begin loop-->
	<?php foreach ( $posts as $post ): ?>

		<div id="post-<?php echo $post->id; ?>" class="<?php echo $post->statusname; ?>">
			<h2><a href="<?php echo $post->permalink; ?>" rel="bookmark" title="<?php echo $post->title; ?>"><?php echo $post->title_out; ?></a></h2>
			<div class="pubMeta"><?php echo $post->pubdate_out; ?></div>
			<?php if ( $user ) { ?>
				<div class="edit">
					<a href="<?php URL::out( 'admin', 'page=publish&slug=' . $post->slug); ?>" title="<?php _e( 'Edit post' ); ?>"><?php _e( 'Edit' ); ?></a>
				</div>
			<?php } ?>
			<div class="entry">
				<?php echo $post->content_excerpt; ?>
			</div>
			<div class="meta">
				<?php if ( count( $post->tags ) ) { ?>
					<div class="tags"><?php _e( 'Tagged:' ); ?> <?php echo $post->tags_out; ?></div>
				<?php } ?>
				<div class="commentCount"><a href="<?php echo $post->permalink; ?>#comments" title="<?php _e( 'Comments on this post' ); ?>"><?php echo $post->comments->approved->count; ?> <?php echo _n( 'Comment', 'Comments', $post->comments->approved->count ); ?></a></div>
			</div><br>
		</div>

	<?php endforeach; ?>
	<!--end loop-->

	<div id="pagenav">
		<?php $theme->next_page_link( '&laquo;' . _t( 'Older' ) ); ?> <?php $theme->page_selector( null, array( 'leftSide' => 2, 'rightSide' => 2 ) ); ?> <?php $theme->prev_page_link( _t( 'Newer' ) . '&raquo;' ); ?>
	</div>
</div>
<!-- #content -->
<?php $theme->display('sidebar'); ?>
<?php $theme->display('footer'); ?>
