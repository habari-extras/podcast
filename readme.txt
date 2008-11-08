Plugin: Podcast
URL: http://habariproject.org
Version: 1.0.2
Author: Habari Project

Podcast is a Habari plugin that is intended to make creating and updating podcast feeds as simple as possible. It allows you to create and edit feeds, including the iTunes settings for the feed, and create and edit podcast posts.

Podcast is fully integrated with the Habari media silo, allowing you to point and click to add an mp3 file to a podcast post. It also has a built-in media player that is inserted in podcast posts at the location where you have inserted the link to the mp3 file.

Requirements 

Habari.

Installation

1. Copy the plugin directory into your user/plugins directory or the site's plugins directory.
2. Go to the plugins page of your Habari admin panel.
3. Click on the Activate button for Podcast.

Usage

General

When Podcast is activated, it creates a new content type called, naturally enough, podcast. You will see a new menu item under the admin menu's 'New' submenu to create podcast posts, and a new menu item under the 'Manage' submenu to manage podcast posts.

Podcast has three main screens for configuration. Two of these, 'Manage Feeds' and 'Edit feed' are located on the plugins page of the admin area and are used to manage and edit your podcast feeds. The third is located on the post publish page and is reached by clicking the 'Enclosures' tab under the tags area when you are creating or editing a podcast post.

Manage Feeds

Podcast is able to create and maintain as many feeds as you wish, a capability you may know as category casting. To create or delete a podcast feed use this configuration form.

1. Navigate to the Plugins page of the admin area.
2. Select 'Manage Feeds' from the Podcast dropdown menu. The feed management form will open up. To create a new feed
	a. type in the feed's name in the top edit box 
	b.select the feed type from the next select box
	c.click the 'Save' button. Currently iTunes is the only feed type available.

	To delete a feed you no longer want
	a. uncheck the feed you want to delete at the bottom of the form.
	b. click the 'Save' button.

Edit feed

Once you have created a feed, a new option will be added to the Podcast plugin's dropdown menu, 'Edit name_of_feed feed'. There will be a menu item added for each feed you create. Several items are required. Their labels have an asterisk ( * ) at the end. Without filling in these items, your changes will not be saved. To edit a feed's options

1. Navigate to the Plugins page of the admin area.
2. Select 'Edit name_of_feed feed' from the Podcast dropdown menu. A configuration form for the selected feed will open up. The options in the form will relate to the type of feed you are creating. For now, this means iTunes feed options. For iTunes the following options are available:
	a. Podcast Author - defaults to the current user's display name
	b. Podcast Subtitle - defaults to the blog's tagline
	c. Podcast Summary = defaults to the blog's tagline
	d. Podcast Owner Name - defaults to the current user's display name
	e. Podcast Owner Email = defaults to the current user's email
	f. Content Rating - defaults to 'No Rating'. 
	g. Podcast Artwork URL  - enter the url for the artwork to supply to iTunes. These needs to be an image no larger than 300 x 300 pixels
	h. Podcast Category - Choose the appropriate categories from the select boxes. You need to select at least one, and can have up to three
	i. Block Podcast - defaults to unchecked. Do not check this unless you want to block your podcast from iTunes
	j. New podcast url - if you move your feed to a new url, you need to notify iTunes. do so by entering the new url in this field

When you are done making the changes you desire, click the 'Save' button. A message will pop up in the messages area of the admin to notify you when you have successfully edited the feed options.

Creating and Editing a Podcast Post

After you have the feed set up, you are ready to begin creating posts for your podcast. To do so select New->Podcast from the admin's main menu. You will be taken to a publish page that, for the most part, is like a normal entry's publish page. Write a post as you normally would, complete with title, content, and tags, then you'll be ready to add the podcast specific items. If you have uploaded you audio files to a directory accessible to the Habari media silo, you'll find your work is much easier.

1. Click on the location in your post where you want the podcast player to appear, then open the Habari silo and navigate to the directory containing your audio file. While audio files currently don't have an associated icon, you'll see their title's listed, and will note that underneath each a menu item has been added that says 'add to name_of_feed'. Click the 'add to ..' item to insert a link to the file in your post where you last had the cursor. The link added will have the format

	<a href="http://url/path/to/file/filename.mp3" rel="enclosure">filename.mp3</a>

Note: There are two important points here. First, the link must have the rel="enclosure" attribute. The Podcast plugin relies on this when determining what links to replace with a player. Second, if your audio files are hosted externally, you won't be able to use the silo and will have to enter this link by hand. 

If you have more than one feed, you can use a post in as many of them as you like. You can either add the same audio file for each feed, or add more than one audio file to the post in the position you desire, with whichever feeds you want. Having more than one audio file in a post won't interfere with the individual feeds. Each will contain only the appropriate audio file. However, in the post as viewed on your blog, a player will appear for each feed and each file. Please note that the last link entered for each feed from the Habari silo in this manner will be the only one to be added to the actual podcast feed.

2. Click the 'Enclosures' tab underneath the tags area. A form will open containing the options for each indivdual feed that you have created for your site, one set of feed options after the other. For iTunes feeds these include

	a. Podcast Enclosure - this is the name of the audio file you are using for the feed. If you placed the file in the post with the Habari silo, this will have been automatically filled in for you. If not, enter it manually, being sure to enter a fully qualified url. This is the only required item.
	b. Subtitle - if you want the post to have a subtitle, enter it here
	c. Content Rating - this defaults to 'No Rating'. use this setting to override the feed rating for that particular post
	d. Summary - if you wish to have a summary for the post, enter it here
	e. Block - if you wish to block a particular post from iTunes, check this item

If the post is being added to more than one feed, continue on to make desired changes for each feed. After you are completely done, click the 'Save' button to save the post as a draft or to save any changes to an already published post, or click the 'Publish' button to publish the post. When you do so, the Podcast plugin will add any tags for the post to its iTunes keywords, calculate the length of the audio file for each feed, and calculate the running time for each audio file. These calculations can cause Podcast posts to take a little longer to save than regular entries, especially if the audio files are hosted on an external server.

Accessing Podcasts From Your Site

The Podcast plugin makes several changes to your blog

	1. Home page - Podcast posts show up on your home page intermixed with regular entries.
	2. Multiple post archives - Podcast posts show up in your tag urls intermixed with regular entries.
	3. Podcast urls - Podcasts are accessible by using an archive url of the form http://yoursite.com/podcast/name_of_podcast. This will contain only posts that have been assigned to the named podcast.
	4. Feed urls - Podcast feeds are accessible by using a url of the form http://yoursite.com/podcast/name_of_podcast/rss for an RSS-2 feed. The feed will contain only feed item versions of posts that have been assigned to the named podcast.

Podcast Templates

Podcast comes with four templates one each for an individual podcast post and for multiple podcast post pages, for both the rawphpengine and the hiengine. These will probably not fit with the theme of your blog, so it is recommended that you copy them to your theme directory and modify them as needed.

The Player

Podcast uses the niftyplayer to enable playing podcast audio files on your site. When a podcast post is viewed, the plugin searches it's content and replaces all links with the rel="enclosure" attribute with a player and a link to download the file. Thus, if you have multiple audio file links in the post, a separate player will be embedded for each of them.

Uninstallation

1. Got to the plugins page of your Habari admin panel.
2. Click on the Deactivate button.
3. Delete the Podcast directory from your user/plugins directory.

Cleanup

Simply deactivating Podcast makes no changes to your database other than taking it out of the list of active plugins. If you reactivate it, all your setting will remain in place. However, if you feel draconian and decide to stop podcasting completely and wish to remove all podcast related items from your database, refer to the following. However, unless you are experienced with database manipulation, you can create problems with your database if you perform any of the following actions. Backups are your friend. 

1. The plugin places an 'podcast__feeds' item and an item whose name is preceded by 'podcast__' for each feed you have created in your Options table. This can be safely deleted after you uninstall the plugin. 
2. The plugin stores items in the postinfo table for each post which belongs to a feed. These are named after the feed to which they belong. For example, if you have a feed named 'itunes', they will be named 'itunes. These entries can be safely deleted if you uninstall the plugin.
3. A 'podcast' entry is made in your posttype table. After noting it's id number, you can safely delete it.
4. The content_type for all podcast posts is the same as the podcast id number from the posttype table. You can delete these posts completely or change their content_type to one that remains in your posttype table, most likely the 'entry' type.

Changelog
Version 1.0.2
Fixed: No download link displayed under the player if no subtitle was entered under a post's iTunes settings

Version 1.0.1
Fixed: Locally hosted files were being deleted when the podcast's post was saved.

Version 1.0
Initial Release
