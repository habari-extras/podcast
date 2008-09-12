<?php $theme->display('header'); ?>
<!--begin content-->
<div id="content">
	<div class="postnav">
		<div class="postprev"><?php $theme->prev_post_link( $theme ); ?></div>
		<div class="postnext"><?php $theme->next_post_link( $theme ); ?></div>
	</div>

	<div id="post-<?php echo $post->id; ?>" class="<?php echo $post->statusname; ?>">
		<h1><a href="<?php echo $post->permalink; ?>" rel="bookmark" title="<?php echo $post->title; ?>"><?php echo $post->title_out; ?></a></h1>
		<div class="pubMeta"><?php _e( 'Posted by' ); ?> <?php echo $post->author->displayname; ?> <?php _e( 'on' ); ?> <?php echo $post->pubdate_out; ?></div>
		<?php if ( $user ) { ?>
			<div class="edit"><a href="<?php URL::out( 'admin', 'page=publish&slug=' . $post->slug); ?>" title="<?php _e( 'Edit post' ); ?>"><?php _e( 'Edit post' ); ?></a></div>
		<?php } ?>
		<div class="entry">
			<?php echo $post->content_out; ?>
		</div><!-- end entry -->
	</div><!-- end id post-* -->

	<div class="entryMeta">	
		<p>
			<?php if ( count( $post->tags ) ) { ?>
				<?php _e( 'This entry is filed under' ); ?> <?php echo $post->tags_out; ?>. 
			<?php } ?>
			<?php _e( 'You can follow any responses to this entry through the' ); ?>
			<a href="<?php echo $post->comment_feed_link; ?>"> feed</a>
			<?php if ( !$post->info->comments_disabled ) { ?>
				<?php _e( 'or leave you own' ); ?> <a href="#comments_form">comment</a>.
			<?php } elseif ( $post->info->comments_disabled ) { ?>
				. <?php _e( 'New comments are currently closed.' ); ?>
			<?php } ?>
		</p>
	</div><!-- end entryMeta -->

	<?php $theme->display('comments'); ?>
</div><!--end content-->
<?php $theme->display('sidebar'); ?>
<?php $theme->display('footer'); ?>