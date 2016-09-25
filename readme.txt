=== CheetahO Image Optimizer ===
Contributors: cheetaho
Tags: Image Optimizer, Image Optimiser, Optimize, Optimise, Images, Media, Performance, SEO, faster loading times, smushit, smush.it, compress, tinypng, tinyjpeg, pngquant, jpegmini, pagespeed, pagespeed insights, optimize jpeg, optimize png
Requires at least: 3.0.1
Tested up to: 4.6.1
Stable tag: 1.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/old-licenses/gpl-2.0.html


This plugin allows you to optimize your WordPress images through the CheetahO API. This is BETA version at this moment.

== Description ==

This plugin allows you to optimize new and existing Wordpress image uploads through [CheetahO Image optimizer](http://cheetaho.com/) API. You can choose lossless or lossy optimization modes. At this moment supported filetypes are JPEG, PNG.

> **Get your FREE account with us**

> Sign up for your [FREE CheetahO.com Account](http://app.cheetaho.com/) and try out our plugin.


= About the plugin =
* You can use your CheetahO API key on as many sites/blogs as you like. We have no per-site license.
* All images uploaded throught the media uploader are optimized on-the-fly. All generated thumbnails are optimized too.
* All images already present in the media library can be optimized individually, or using the Bulk Action.
* All optimization is carried out by sending images to CheetahO, and pulling the optimized files to your Wordpress installation.
* To use this plugin, you must obtain a full API key from [http://app.cheetaho.com/admin/api-credentials).


Once you have obtained your credentials, from your Wordpress admin, go to the settings page and there you can enter your API credentials, and select your optimization preferences. Once you have done this, click on **Save**. If everything is in order, it will simply say "settings saved" and give you a reassuring green tick that your credentials are valid. You can now start optimizing images from within Media Library. Any image you upload from now on, through any of the media upload screens will be optimized on-the-fly.

= Features on the way =
* Optimize entire media library in one click.
* Optimize your currently active theme.
* WordPress Multisite support.

Please send bug reports, problems, feature requests and so on to support (at) cheetaho dot com, or directly to the author of this plugin.

= Connect with CheetahO.com =
* Website: http://cheetaho.com/
* [Twitter](https://twitter.com/cheetahocom "@cheetahocom")


== Installation ==

To install the CheetahO Wordpress Plugin:

1. Upload files to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Enter your CheetahO API key into the plugin settings page.
4. Any images you upload from now on using Wordpress's Media Upload will be optimized according to your settings. Auto-generated thumbnails will also be optimized.
5. Images already present can be optimized from within the Media Library.

== Screenshots ==

1. Cheetaho.com wordpress plugin settings page. You must enter your credentials, and select your optimization mode from there. `/assets/screenshot-1.png`
2. Media library with optimization button.  `/assets/screenshot-2.png`

== Frequently Asked Questions ==

= Where can I get an API key? =

From cheetago panel, right [here](http://app.cheetaho.com/admin/api-credentials).


== Changelog ==

= 1.1 =
* Add redirect to settings page after plugin activation.
* Add custom image quality setting, for greater savings if you know what you're doing.

= 1.0 =
* First version. Supports lossy and lossless optimization of JPG, PNG images.
* Hooks to Media Uploader to optimize all uploaded images, including generated thumbnails.
* Allows optimization of existing images in Wordpress Media Library.

== TODO ==
* Separate bulk optimization page.
* API status and info about optimized images
* Allow image optimize once again
* Original images backup folder

== Any Questions? ==
Contact us by email support (at) cheetaho dot com.
