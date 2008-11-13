{hi:display:header}
<div id="content">
	<!--begin loop-->
	{hi:posts}
		<div id="post-{hi:id}" class="{hi:statusname}">
			<h2><a href="{hi:permalink}" rel="bookmark" title="{hi:title}">{hi:title_out}</a></h2>
			<div class="pubMeta">{hi:pubdate_out}</div>
			{hi:?user}
				<div class="edit">
					<a href="{hi:editlink}" title="<?php _e( 'Edit post' ); ?>"><?php _e( 'Edit' ); ?></a>
				</div>
			{/hi:?}
			<div class="entry">
				{hi:content_excerpt}
			</div>
			<div class="meta">
				{hi:?count(tags)}
					<div class="tags"><?php _e( 'Tagged:' ); ?> {hi:tags_out}</div>
				{/hi:?}
				<div class="commentCount"><a href="{hi:permalink}#comments" title="<?php _e( 'Comments on this post' ); ?>">{hi:comments.approved.count} <?php echo _n( 'Comment', 'Comments', $posts_1->comments->approved->count ); ?></a></div>
			</div><br>
		</div>
	{/hi:posts}
	<!--end loop-->

	<div id="pagenav">
		{hi:@prev_page_link} {hi:@page_selector} {hi:@next_page_link}
	</div>
</div>
<!-- #content -->
{hi:display:sidebar}
{hi:display:footer}
