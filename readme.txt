=== Media Downloader ===
Contributors: Ederson Peka
Tags: media, audio, podcast, post, player, mp3, flash
Requires at least: 2.7
Tested up to: 2.9
Stable tag: 0.1.3

Lists MP3 files from a folder.

== Description ==

Media Downloader plugin lists MP3 files from a folder by replacing the [media] smart tag. It reads MP3 information directly from the files. It also can try to get rid of stupid content blockers (mainly corporatives), changing all links to .MP3 files into some download URL without the string "MP3".

== Installation ==

1. Extract the contents of the archive
2. Upload the contents of the mediadownloader folder to your 'wp-content/plugins' folder
3. Log in to your WordPress admin and got to the 'Plugins' section. You should now see Media Downloader in the list of available plugins
4. Activate the plugin by clicking the 'activate' link
5. Now go to the 'Options' section and select 'Media Downloader' where you can configure the plugin

== Frequently Asked Questions ==

= How should I configure it? Where should I throw my MP3 files? How do I use this thing? What's the smart tag syntax? =

An example may help... Let's say you have a folder called "music" under your root folder, and for its time it has some subfolders, like: "Beethoven", "Mozart", "Bach" and "Haendel".

First of all, you should configure Media Downloader by typing "music" in the "MP3 Folder" field, on settings page (and then clicking on "Update Options", for sure).

That done, you can edit a post talking 'bout Johann Sebastian Bach and insert anywhere on it the smart tag "[media:Bach]". Media Downloader will create a list of all files under the "music/Bach" directory. This is actually very simple. ;-)
