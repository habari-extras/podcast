{hi:display:header}
<div id="content">
	<!--begin loop-->
	{hi:posts}
		<div id="post-{hi:id}" class="{hi:statusname}">
			<h2><a href="{hi:permalink}" rel="bookmark" title="{hi:title}">{hi:title_out}</a></h2>
			<div class="pubMeta">{hi:pubdate.date}</div>
			{hi:?loggedin}
				<div class="edit">
					<a href="{hi:editlink}" title="{hi:"Edit post"}">{hi:"Edit"}</a>
				</div>
			{/hi:?}
			<div class="entry">
				{hi:content_excerpt}
			</div>
			<div class="meta">
				{hi:?count(tags)}
					<div class="tags">{hi:"Tagged:"} {hi:tags_out}</div>
				{/hi:?}
				<div class="commentCount"><a href="{hi:permalink}#comments" title="{hi:"Comments on this post"}">{hi:"{hi:comments.approved.count} Comment" "{hi:comments.approved.count} Comments" comments.approved.count}</a></div>
			</div><br />
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
