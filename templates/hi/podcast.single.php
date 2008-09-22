{hi:display:header}
<!--begin content-->
<div id="content">
	<div class="postnav">
		<div class="postnext">{hi:@next_post_link}</div>
		<div class="postprev">{hi:@prev_post_link}</div>
	</div>
<p>Using the hi template</p>
	<div id="post-{hi:post.id}" class="{hi:post.statusname}">
		<h1><a href="{hi:post.permalink}" rel="bookmark" title="{hi:post.title}">{hi:post.title_out}</a></h1>
		<div class="pubMeta"><?php _e( 'Posted by' ); ?> {hi:post.author.displayname} <?php _e( 'on' ); ?> {hi:post.pubdate_out}</div>
		<?php if ( $user instanceOf User ) { ?>
			<div class="edit"><a href="<?php URL::out( 'admin', 'page=publish&slug=' . $post->slug); ?>" title="<?php _e( 'Edit post' ); ?>"><?php _e( 'Edit post' ); ?></a></div>
		<?php } ?>
		<div class="entry">
			{hi:post.content_out}
		</div><!-- end entry -->

		<script src="http://feeds.feedburner.com/~s/SagRising?i={hi:post.permalink}" type="text/javascript" charset="utf-8"></script>

	</div><!-- end id post-* -->

	<div class="entryMeta">	
		<p>
			<?php if ( count( $post->tags ) ) { ?>
				<?php _e( 'This entry is filed under' ); ?> <?php echo $post->tags_out; ?>. 
			<?php } ?>
			<?php _e( 'You can follow any responses to this entry through the' ); ?>
			<a href="{hi:post.comment_feed_link}"> feed</a>
			<?php if ( !$post->info->comments_disabled ) { ?>
				<?php _e( 'or leave you own' ); ?> <a href="#comments_form">comment</a>.
			<?php } elseif ( $post->info->comments_disabled ) { ?>
				. <?php _e( 'New comments are currently closed.' ); ?>
			<?php } ?>
		</p>
	</div><!-- end entryMeta -->

	{hi:display:comments}
</div><!--end content-->
{hi:display:sidebar}
{hi:display:footer}