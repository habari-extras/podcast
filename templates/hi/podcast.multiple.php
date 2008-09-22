{hi:display:header}
<div id="content">
	<!--begin loop-->
	<?php foreach ( $posts as $post ): ?>
		<div id="post-{hi:post.id}" class="{hi:post.statusname}">
			<h2><a href="{hi:post.permalink}" rel="bookmark" title="{hi:post.title}">{hi:post.title_out}</a></h2>
			<div class="pubMeta">{hi:post.pubdate_out}</div>
			<?php if ( $user instanceOf User ) { ?>
				<div class="edit">
					<a href="<?php URL::out( 'admin', 'page=publish&slug=' . $post->slug); ?>" title="<?php _e( 'Edit post' ); ?>"><?php _e( 'Edit' ); ?></a>
				</div>
			<?php } ?>
			<div class="entry">
				{hi:post.content_excerpt}
			</div>
			<div class="meta">
				<?php if ( count( $post->tags ) ) { ?>
					<div class="tags"><?php _e( 'Tagged:' ); ?> {hi:post.tags_out}</div>
				<?php } ?>
				<div class="commentCount"><a href="{hi:post.permalink}#comments" title="<?php _e( 'Comments on this post' ); ?>">{hi:post.comments.approved.count} <?php echo _n( 'Comment', 'Comments', $post->comments->approved->count ); ?></a></div>
			</div><br>
		</div>
	<?php endforeach; ?>
	<!--end loop-->

	<div id="pagenav">
		{hi:@prev_page_link} {hi:@page_selector} {hi:@next_page_link}
	</div>
</div>
<!-- #content -->
{hi:display:sidebar}
{hi:display:footer}
