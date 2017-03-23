=== CheetahO Image Optimizer ===
Contributors: cheetaho
Tags: Optimize image, image optimizer, compress jpeg, compress png, compress gif, image compression, wp smush, optimize gif, tinypng, shrink jpeg, optipng, wordpress performance
Requires at least: 3.0.1
Tested up to: 4.7.3
Stable tag: 1.2.9
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
Donate link:  https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=8EBKEZMR58UK4

This plugin allows you to optimize your WordPress images through the CheetahO API. Your website will be faster, keeping the images high quality.

== Description ==

This plugin allows you to optimize new and existing Wordpress image uploads through CheetahO Image [optimizer](http://cheetaho.com/) API. You can choose lossless or lossy optimization modes. At this moment supported filetypes are JPEG, PNG, GIF.
Smaller image sizes on your website means faster page loads and less bandwidth usage.
You can try a **live demo** <a href="https://addendio.com/try-plugin/?slug=cheetaho-image-optimizer" target="_blank">here</a> (via addendio.com).

> **Get your FREE account with us**

> Sign up for your FREE CheetahO.com [Account](http://app.cheetaho.com/) and try out CheetahO image optimization tool. We suggest:

> * 500 images (can buy more)
> * Max file size 4 MiB (with paid plan get more)
> * API access
> * ONE API key for multiple sites
> * Lossless or lossy optimization
> * Optimization Statistics and History
> * 'Bulk' optimize all the existing images in Media Library.
> * It is safe to test and use the plugin: all the original images are stored in backup folder (optional feature, you can disable it).
> * Skip already optimized images.
> * Optimize thumbnails as well.
> * Works with both HTTPS and HTTP websites

> Need more? Have a look at our plans: http://cheetaho.com/pricing/


= About the plugin =
* You can use your CheetahO API key on as many sites/blogs as you like. We have no per-site license.
* All images uploaded throught the media uploader are optimized on-the-fly. All generated thumbnails are optimized too.
* All images already present in the media library can be optimized individually, or using the Bulk Action.
* All optimization is carried out by sending images to CheetahO, and pulling the optimized files to your Wordpress installation.
* To use this plugin, you must obtain a full API key from CheetahO [panel](http://app.cheetaho.com/admin/api-credentials).

Once you have obtained your credentials, from your Wordpress admin, go to the settings page and there you can enter your API credentials, and select your optimization preferences. Once you have done this, click on **Save**. If everything is in order, it will simply say "settings saved" and give you a reassuring green tick that your credentials are valid. You can now start optimizing images from within Media Library. Any image you upload from now on, through any of the media upload screens will be optimized on-the-fly.

= Features on the way =
* Optimize your currently active theme.
* WordPress Multisite support.


Please send bug reports, problems, feature requests and so on to support (at) cheetaho dot com, or directly to the author of this plugin.

= Connect with CheetahO.com =
* Website: http://cheetaho.com/contact-us/
* [Twitter](https://twitter.com/cheetahocom "@cheetahocom")

**Keywords:** picture, optimization, pngout, shortpixel, compression, lossless, jpegtran, image, image optimization, shrink, optimize photos, compress, performance, tinypng, crunch, pngquant, optimize, jpegmini, lossy, kraken, seo, smushit, optipng, ewww, photo optimization, image optimizer, png, image optimize, jpg, jpeg, jpg optimization, optimize jpg, optimise jpg, shrink jpg, optimizer, optimiser, compresion, image cruncher, compress png, compress jpg, compress jpeg, image optimiser, improve pagerank, optimise, optimise jpeg, optimize jpeg, optimize png, optimise png, tinyjpg, smush, imsanity, wp smush, compress images, optimize images, shrink jpeg, compressor, faster website, imagify, prizm, optimus, improve page speed, smaller images, tinyjpeg, wordpress compression, wordPress image tool, reduce image size, remove exif, speed up site, speed up website, zara, optimize thumbnails


== Installation ==

To install the CheetahO Wordpress Plugin:

1. Upload files to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Enter your CheetahO API key into the plugin settings page.
4. Any images you upload from now on using Wordpress's Media Upload will be optimized according to your settings. Auto-generated thumbnails will also be optimized.
5. Images already present can be optimized from within the Media Library.

CheetahO - Getting Started
[youtube https://www.youtube.com/watch?v=ERikQMzZv8E]

== Screenshots ==

1. Cheetaho.com wordpress plugin settings page. You must enter your credentials, and select your optimization mode from there. `/assets/screenshot-1.png`
2. Media library with optimization button.  `/assets/screenshot-2.png`

== Frequently Asked Questions ==

= Where can I get an API key? =

From cheetago panel, right [here](http://app.cheetaho.com/admin/api-credentials).

= What happens after I optimize the 500 FREE images? =

If you exhaust your free image quota, CheetahO will stop working. You will have to wait for the next month's quota when your plan will renew.

= How does the one-time plan work? =

You can purchase a one-time plan for processing you images. You will pay for it only once, this is not plan subscription. This plan is recommended for websites that have a large gallery that needs to be optimized.

= I want to get original images. Where is the backup folder located? =

Your original images are saved in a backup folder located on your server at: /wp-content/uploads/CheetahoBackups/

= How does Bulk Optimization work? =

The Bulk option makes Cheetaho optimize all your images at once. You can optimize your images in the Media > Bulk CheetahO section by clicking on the compress images button.

Please be patient and do not close the WordPress admin while you are rolling the bulk processing on your media gallery.

= Are my images safe? =

Yes. All processed files are stored only for a few hours.

= What types of formats can be optimized? =

For now, Cheetaho supports JPEG, GIF, and PNG formats.

= I can not optimize image. Got error "Requested file size too large". How fix this? =
CheetahO lets to optimize image up to 4 MB with free subscription plan. If you would like to optimize bigger files, you will need to take other plans from [here](http://app.cheetaho.com/admin/billing/plans) 

= On how many websites can I use the plugin? =
You can use the plugin on as many sites as you wish. The only limit is the optimization quota of your account.

== Changelog ==
= 1.2.9 =
* Update readme file
* Testing with WordPress 4.7.3 version

= 1.2.8 =
* Fixed duplicated function issue. Thanks for http://alpakuauginimas.lt

= 1.2.7 =
* Add strings in translations 
* Add gif images compression
 
= 1.2.6 =
* Update readme
* Testing with WordPress 4.7.2 version
* Add website logo
* Set API calls use HTTPS

= 1.2.5 =
* Add backup options for optimized images
* Add 'Bulk' images optimization
* Test with wordpress 4.7 version
* Fix thumbnails optimization during image upload

= 1.2.4 =
* Fix image remove when get unexpected responce
* Add extra validation for thumbnails optimization
* Test with wordpress 4.6.1

= 1.2.3 =
* Fix bug in construct

= 1.2.2 =
* Add messages for users
* Check quota and API key if is valid 

= 1.2.1 =
* Fix bug with new added class

= 1.2 =
* Image optimization statistics block. 
* Change settings page interface and add support block.
* Add setting to choose which image size you would like to optimize.

= 1.1 =
* Add redirect to settings page after plugin activation.
* Add custom image quality setting, for greater savings if you know what you're doing.

= 1.0 =
* First version. Supports lossy and lossless optimization of JPG, PNG images.
* Hooks to Media Uploader to optimize all uploaded images, including generated thumbnails.
* Allows optimization of existing images in Wordpress Media Library.

== TODO ==
* Woocommerce support.
* NextGEN Gallery support.
* PDF files optimization support.
* Image file resolution resize.
* Retina images optimization support.

== Any Questions or suggestions? ==
Contact us by email support (at) cheetaho dot com.







