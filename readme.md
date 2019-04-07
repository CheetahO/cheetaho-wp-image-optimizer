# CheetahO Image Compression and Optimizer

[![Build Status](https://travis-ci.org/CheetahO/cheetaho-wp-image-optimizer.svg?branch=master)](https://travis-ci.org/CheetahO/cheetaho-wp-image-optimizer)
[![WordPress Plugin Downloads](https://img.shields.io/wordpress/plugin/dt/cheetaho-image-optimizer.svg)](https://wordpress.org/plugins/cheetaho-image-optimizer/)
[![WordPress Plugin Rating](https://img.shields.io/wordpress/plugin/r/cheetaho-image-optimizer.svg)](https://wordpress.org/support/plugin/cheetaho-image-optimizer/reviews/)
[![WordPress Plugin Version](https://img.shields.io/wordpress/plugin/v/cheetaho-image-optimizer.svg)](https://wordpress.org/plugins/cheetaho-image-optimizer/)
[![WordPress Tested Up To](https://img.shields.io/wordpress/v/cheetaho-image-optimizer.svg)](https://wordpress.org/plugins/cheetaho-image-optimizer/)
![CheetahO Image Compression and Optimizer](https://ps.w.org/cheetaho-image-optimizer/assets/banner-772x250.png)

This plugin allows you to compress and optimize your WordPress images through the CheetahO API. Your website will be faster, keeping the images high quality.

## Description

This plugin allows you to resize/scale images down to your specified size, optimize and compress new and existing Wordpress image uploads through CheetahO Image [optimizer](https://cheetaho.com/) API. You can choose lossless or lossy optimization modes. At this moment supported filetypes are JPEG, PNG, GIF.
Smaller image sizes on your website means faster page loads and less bandwidth usage. The faster your site loads, the more Google, Yahoo, Bing and other search engines will like it.
You can try a **live demo** <a href="https://addendio.com/try-plugin/?slug=cheetaho-image-optimizer" target="_blank">here</a> (via addendio.com).

Set a max width and height and large images will scale down as they are being compressed. CheetahO compress images uses proprietary algorithm & blazing fast servers to quickly compress images & optimize images. Also cuts all the unnecessary data without slowing down your website/blog which helps to boost seo & performance.

> **Get your FREE account with us**

> Sign up for your FREE CheetahO.com [account](https://app.cheetaho.com/) to get API key and try out CheetahO image optimization tool. We suggest:

> * Free 500 images every month (can buy more)
> * Max file size 4 MiB (with paid plan get more)
> * API access
> * Pixel-perfect optimization using innovative tools and progressive rendering.
> * ONE API key for multiple sites
> * Lossless or lossy optimization
> * Optimization Statistics and History
> * 'Bulk' optimize all the existing images in Media Library
> * It is safe to test and use the plugin: all the original images are stored in backup folder (optional feature, you can disable it)
> * Skip already optimized images
> * Optimize thumbnails as well
> * Works with both HTTPS and HTTP websites
> * Works with eCommerce websites using WooCommerce plugin
> * ALso compatible with galleries Modula, Foo Gallery and any other galleries or sliders
> * Keep or remove EXIF data from your images, compress images with lossless option. This option is great for photographers
> * Able to compress JPG, PNG, GIF images
> * Option to deactivate auto-optimizing images on image upload
> * Able to compress WOOCOMMERCE products images.
> * Images can be automatically resized before being optimized. No need to install additional plugins like Imsanity or Resize Image After Upload
> * Compress/resize images even your site is not public.
> * Compatible with WP Retina 2x – all retina images are automatically compressed.


> Need more? Have a look at our plans: https://cheetaho.com/pricing/


## About the plugin
* You can use your CheetahO API key on as many sites/blogs as you like. We have no per-site license.
* All images uploaded throught the media uploader are optimized on-the-fly. All generated thumbnails are optimized too.
* All images already present in the media library can be optimized individually, or using the Bulk Action.
* All optimization is carried out by sending images to CheetahO, and pulling the optimized files to your Wordpress installation.
* All full size images can be resized/rescaled before optimization by setting maximum width and height.
* To use this plugin, you must obtain a full API key from CheetahO [panel](https://app.cheetaho.com/admin/api-credentials).

Once you have obtained your credentials, from your Wordpress admin, go to the settings page and there you can enter your API credentials, and select your optimization preferences. Once you have done this, click on **Save**. If everything is in order, it will simply say "settings saved" and give you a reassuring green tick that your credentials are valid. You can now start optimizing images from within Media Library. Any image you upload from now on, through any of the media upload screens will be optimized on-the-fly.

Please send bug reports, problems, feature requests and so on to support (at) cheetaho dot com, or directly to the author of this plugin.

## Connect with CheetahO.com
* Website: https://cheetaho.com/contact-us/
* [Twitter](https://twitter.com/cheetahocom "@cheetahocom")

## Installation

To install the CheetahO Wordpress Plugin:

1. Upload files to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Enter your CheetahO API key into the plugin settings page.
4. Any images you upload from now on using Wordpress's Media Upload will be optimized according to your settings. Auto-generated thumbnails will also be optimized.
5. Images already present can be optimized from within the Media Library.

CheetahO - Getting Started
[youtube https://www.youtube.com/watch?v=ERikQMzZv8E]

## Tests

1. Clone CheetahO WordPress plugin to `/wp-content/plugins/` directory using command:
    ```shell
    git clone https://github.com/CheetahO/cheetaho-wp-image-optimizer.git
    ```
2. Install test env tests:
    ```shell
    bash bin/install-wp-tests.sh wordpress_test root 'mysql_pass' localhost latest false <cheetaho_api_key>
    ```
3. Run phpUnit tests:
    ```shell
    phpunit
    ```
4. add to path installed modules if you want to run phpcs  
    ```shell  
    phpcs --config-set installed_paths /root/.config/composer/vendor/phpcompatibility/phpcompatibility-paragonie,/root/.config/composer/vendor/phpcompatibility/phpcompatibility-wp,/root/.config/composer/vendor/wp-coding-standards/wpcs,/root/.config/composer/vendor/phpcompatibility/php-compatibility, /root/.config/composer/vendor/phpcompatibility/phpcompatibility-wp/
    ```

5. run command:
    ```shell
    phpcs --standard=phpcs.ruleset.xml --extensions=php .;
    ```
6. fix codestyle issue 
    ```shell
    phpcbf ./;    
    ```

## Frequently Asked Questions

- **Where can I get an API key?**

    From cheetago panel, right [here](https://app.cheetaho.com/admin/api-credentials).
    
---

- **Can I test/use Cheetaho image optimization plugin for free?**

    Yes you can. Register to CheetahO and get 500 free image optimization credits each month. Exceeding the monthly free quota will pause the optimization process till the quota is reset or extended by buying one of our plans.

---

- **What happens after I optimize the 500 FREE images?**

    If you exhaust your free image quota, CheetahO will stop working. You will have to wait for the next month's quota when your plan will renew.

---

- **How do the one-time plan work?**

    You can purchase a one-time plan for processing you images. You will pay for it only once, this is not plan subscription. This plan is recommended for websites that have a large gallery that needs to be optimized.

---

- **I want to get original images. Where is the backup folder located?**

    Your original images are saved in a backup folder located on your server at: /wp-content/uploads/CheetahoBackups/

---

- **How does Bulk Optimization work?**

    The Bulk option makes Cheetaho optimize all your images at once. You can optimize your images in the Media > Bulk CheetahO section by clicking on the compress images button.

    Please be patient and do not close the WordPress admin while you are rolling the bulk processing on your media gallery.

---

- **Are my images safe?**

    Yes. All processed files in our servers are stored only for a few hours.

---

- **What types of formats can be optimized?**

    For now, Cheetaho supports JPEG, GIF, and PNG formats.

---

- **I can not optimize image. Got error "Requested file size too large". How fix this?**

    CheetahO lets to optimize image up to 4 MB with free subscription plan. If you would like to optimize bigger files, you will need to take other plans from [here](http://app.cheetaho.com/admin/billing/plans) 

---

- **On how many websites can I use the plugin?**

    You can use the plugin on as many sites as you wish. The only limit is the optimization quota of your account.

---

- **Full size image resize. What is this?**

    You can resize/scale images by setting maximum width and height in Cheetaho settings page. CheetahO will resize only original (full size) images to a predefined size while keeping its aspect and proportions intact. The thumbnails will not be touched.

---

- **Do you have an API?**

    Yes, we have API. More information you can find here: https://cheetaho.com/api-documentation/

---

- **My site is DEV mode on localhost. Can I use plugin on a localhost installation?**

    You can not use plugin on a localhost. Your site is not public and our servers can not download images to optimize.

---

- **Can this plugin resize images?**

    If you choose the option to resize your images and set max with and height then the image can be resized to a predefined size while keeping its aspect and proportions intact.

---

## Have questions or need help?
We are here to help. <a href="https://cheetaho.com/contact-us/">Contact us</a>! Contact us by email: support (at) cheetaho dot com.









