=== Post Office ===
Contributors: Jacotheron/Starsites
Donate link: http://www.starsites.co.za/
Tags: doc, docx, xlsx, parse, html, generate, image, upload, extract, word, excel, 2007, post, page
Requires at least: 2.8
Tested up to: 3.2
Stable tag: 1.0.12

Do you want to post your Word/Excel files? Now you can! This plugin will post these files for you (more on their way).

== Description ==
[support link]: http://support.starsites.co.za
            "Starsites Support"
[home link]: http://www.starsites.co.za
            "Starsites Home"

Do you have a lot of Microsoft Word / Excel Documents that you want to post or create a page from? This plugin will do that for you.

This plugin is easy to use. Just install it, set a few settings, and set optional settings on the upload page.
The plugin will do the rest for you. It will extract the contents once, and save it as a post/page, removing the
unrequired files (saving you space).

The 2007 files of Microsoft Office are all ZIP files, saving you even Bandwidth when uploading them with this plugin.

This Plugin is Free Open Source: A Great Plugin to Compliment WordPress, for Free.

Please note:
*     Do not use older Office files, they will not work (since they are not ZIP files).

*     This script might sometimes be very Resource Intensive as well as taking a long time to complete based on your document and
Server, please just be patient.

*     This script will not modify the time limit for proccessing large files and it will also not modify the maximum file
size for upload, thus it is possible for the script to run out of time.

*     We only test to make sure the plugin works on the latest versions of WordPress. This plugin requires features that was first
introduced in version 2.8 and should be able to work on WordPress 2.8 and further. The plugin, however might work on earlier versions,
but we can't provide support for those versions.

*     If you reqiure support, please open a ticket on our support website @ [support.starsites.co.za][support link]. Announcements and
more details available at [Starsites][website link].

== Installation ==

This section describes how to install the plugin and get it working.

1. Upload the plug-in folder ('post-office') into the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Set the Global Settings
1. Start uploading your Documents

== Changelog ==

= 1.0.12 =
* Bug Fix: Fixed a few Bugs and made it compatible with WP 3.2
= 1.0.8 =
* Bug Fix: Excel Rounding improved
= 1.0.7 =
* Bug Fix: Numbers not showing up in Excel (this is due to PHP version 4)
= 1 =
* The First Public version

== Upgrade Notice ==

= 1.0.12 =
* Bug Fix: Fixed a few Bugs and made it compatible with WP 3.2
= 1.0.8 =
This version improves the logic of the rounding and make it possible to set it on/off and define the precision.
= 1.0.7 =
Only required to update if having problems with the Excel Numbers
= 1.0.0 =
The first public version


== Frequently Asked Questions ==

= What file type can this plug-in proccess =

This plug-in can only process Word 2007 (.docx) and Excel 2007 (.xlsx) files.

= How long does it take to parse the file =

Depending on your server and hardware allocated to your WordPress installation it can take about less than a
second for a single file on a weak server or even faster on stronger servers. This is
much faster than any other method I could find to convert a Word Document to a WordPress Post/Page and it saves
bandwidth as it is a ZIP file.

= What is required on my server to run this plug-in =

This plugin was written as a PHP 5 script. It might not work on earlier versions (not tested on versions prior to PHP 5).
This plugin requires the following extensions for PHP to be active: ZLib; XML Parser; GD Image Library. These extensions
are active in a default PHP 5 installation (it might have been disabled afterwards). The plugin will warn you if it can't
find these.

= After I uploaded a document and it was parsed, the result says other than WordPress says. What happened? =

This is for example you uploaded a document and it displays an error, but you find the content in the Posts or
It displays no error but the new post does not exist.
This can only be caused by two or more documents being uploaded and parsed at the same time and another one finishing
a moment after the first one before the last result could be shown on the user's screen and thus overwriting the
previous result. It is not advisable to parse multiple files at the same time as this will result in very high
load on the server's resources (this is also the reason for only able to parse a single file at a time). You are now able
to view the log and look for the file you uploaded for the result.

= When the State of the new post is set to Publish, the post/page's markup is not standards complient. Why is this? =

This is caused by the function WordPress uses to insert the post. The function tries to add tags and other information to
the output of our script. This results in non standards complient code published in the post/page. We have tried solving it,
but then it was worse than now (we will continue to research solutions).
If you want standards complient markup, set the state to draft and open the post/page in the edit page, where you can publish
it from. This adds only the correct tags.

= What formattings can be recognised by this plugin? =

Currently this plugin can recognise Bold, Italics, Underlined, Strike-Through, Heading(1-3), Text Colors, Hyperlinks, Tables.
This plugin can also extract text from formatted WordArt text, but it is still not possible to extract the colors and other
details of the WordArt to replicate it.

== Quick Features ==

1. Upload file as a Post/Page
1. Uploads images inside into the Post/Page
1. Fast Content Extraction
