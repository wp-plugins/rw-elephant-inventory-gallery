=== R.W. Elephant Inventory Gallery ===
Contributors: jpsteinwand
Tags: rental, inventory, gallery
Requires at least: 3.0
Tested up to: 4.0.1
Stable tag: 1.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Gallery displays R.W. Elephant rental inventory on your website.

== Description ==

This plugin displays a gallery of your R.W. Elephant rental inventory. The inventory is updated in real time via the R.W. Elephant API. To use this plugin you will need an R.W. Elephant account.

The gallery appearance is customizable. Using the settings page on your WordPress Dashboard, choose the name for your gallery and the location you want it displayed on your site. You can choose one of the included template designs or customize your own. You can also select the thumbnail size and which social sharing links to display on your items.


== Installation ==

Note: You need an R.W. Elephant ID and API key to use this plugin.

1. Upload the `rwe-gallery` directory and its contents to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Edit the plugin settings and enter your R.W. Elephant ID and API key and select the page on your site to display the gallery.


== Frequently Asked Questions ==

= How do I get an R.W. Elephant ID and API key? =

Visit [R.W. Elephant](http://www.rwelephant.com/) to sign up.


= Why does my gallery say `No Categories Found`? =

Check to make sure your R.W. Elephant ID and API key are correct. In some rare cases, a web server may block outgoing HTTP requests which prevents this plugin from communicating with the R.W. Elephant API.


= How do I change the title format?  =

The <title> HTML element is used to define the title of your browser window, the title used by search engines and the default name for a bookmark. You can change the title format in R.W. Elephant Inventory Gallery settings on the WordPress Dashboard. The following placeholders are available: [title] [separator] [gallery_name]. These placeholders will be replaced with the corresponding data on each gallery page. You can choose which placeholders to use and add additional characters as desired to customize the title of your gallery pages.

[title] = Name of the current category if viewing a category page, tag name when viewing a tag page, `search: ___` when searching or the item name on an item detail page.

[separator] = Separator caharcter(s) set by your selected theme (Usually set using wp_title in your theme's header.php).

[gallery_name] = The name of your gallery.

Most WordPress themes automatically append your site name to the end of the title, so the title format should normally end with a separator and a space. If your theme puts your site name first, begin with a space and a separator. The home page of your gallery will use the title of the WordPress page you have chosen for your gallery and will not use the gallery name or title format of the plugin. To change that title, edit the WordPress page you have chosen for your gallery.


= How to I customize the layout of my gallery? =

See the special section 'Customizing Templates' in the readme.txt file.


== Changelog ==

= 1.2 =
* Changed location for custom templates to /wp-content/rw-elephant-templates/
* Added SEO-friendly URL option for items and categories
* Added 300 pixel thumbnail option
* Changed large image size on item detail pages to 600 pixels (previously used whatever original size was in R.W. Elephant)
* Large image on item details page links to the original image
* Updated item detail templates to include thumbnail size (new placeholder [item_thumbnail_size])
* Updated wishlist submit to accomodate servers that use PHP caching

= 1.1 =
* Added a wishlist which allows visitors to add items from inventory and submit their contact information.

= 1.0 =
* This is the first release of the R.W. Elephant Inventory Gallery


== Upgrade Notice ==

= 1.2 =
IMPORTANT: If you customized your templates, back them up before upgrading! Upgrading will overwrite your templates! See Customizing Templates in readme.txt for more.


== Customizing Templates ==

NOTICE: If you have modified existing templates, be sure to back them up (or move them to the new location described below) before upgrading to version 1.2.

New custom template location in version 1.2! When modifying templates, create a `rw-elephant-templates` directory under your `wp-content` directory (`/wp-content/rw-elephant-templates/`). Then place modified templates in this location (you may copy from `/wp-content/plugins/rw-elephant-inventory-gallery/templates/custom/` or any of the included templates). Only copy the template files you wish to modify; if a template does not exist in the rw-elephant-templates directory, the default will be used. Moving the template location outside the plugin directory will prevent future updates from overwriting your templates.

The gallery is displayed on the chosen WordPress page by inserting it into the content of the page. If you wish to change the appearance of the page beyond the content area, such as to change the page width or change the way the page heading is displayed, change the page template in your WordPress theme. To select a different WordPress page template (if your Theme supports multiple page layouts): Pages->Edit->[Select your gallery page]->Template. You can modify or make a new page template by editing your WordPress theme. Normally you will want to use a full width page template.

The R.W. Elephant Inventory Gallery uses its own templates for the layout of the gallery pages. You can modify the provided templates. A template named `custom` with minimal styling is provided if you wish to use it to build your own template design. The gallery templates use placeholders which are be replaced with the appropriate gallery information when the pages are displayed on your website.

The included templates are located in the `/wp-content/plugins/rwe-gallery/templates/` directory. In this directory you will find sub-directories named for each template style. The individual template files are located in each sub-directory. Some parts of templates are shared. The shared templates located in the `common` template directory. Do not modify the templates in this location, instead place modified templates in `/wp-content/rw-elephant-templates/` as described above.

Wishlist templates: the included templates have added wishlist placeholders for the wishlist function as of version 1.1. There are three new placeholders: [add_to_wishlist] availble only on the item detail page, [view_wishlist] and [wishlist] available on all pages. View the templates included in version 1.1 for example positions. You can add these placeholders to your modified templates to add wishlist functionality. You will probably also want to copy the new wishlist styling from the CSS file.

Following is a list of templates and the placeholders available for each:

= Common templates (located in `/templates/common/`) =

* category-list.php

This is a sub-template used to create the main category list. This template is used to display each category.

[category_name] = name of the category
[category_url] = the URL of the category
[category_thumbnail] = the category thumbnail, including the <img> tag
[category_thumbnail_url] = the category thumbnail, the URL of the image only


* item-list.php

This is a sub-template that creates lists of items. It is used for listing items in a category, items for a tag and for search results. 

[item_name] = name of the item
[item_url] = full URL to the item
[item_quantity] = quantity of the item
[item_price] = price of the item
[item_dimensions] = dimensions of the item
[item_photo] = the item thumbnail, including the <img> tag
[item_photo_url] = the URL of the item thumbnail image


* search-form.php

This template is used to create the search form for searching your gallery. This template is included in other templates.

[gallery_name] => the name of the gallery
[gllery_url] => the URL of the main gallery page, submit the form to this location


* script.js

This contains JavaScript to switch the item image when you click on an alternate image. You can include additional JavaScript in this file. This file is included on all gallery pages.



= Individual templates (located in `/templates/*template_name*/`) =

* categories.php

This template displays the main categories. It is the home page of your gallery.

[gallery_name] = the name of the gallery
[gallery_url] = the URL of the main gallery page
[search_form] = displays the search form
[category_list] = this is the list of categories, created by the template `common/category-list.php`
[category_thumbnail_size] = the size of category thumbnail in pixels: 100, 200 or 300
[error] = displays error message on the page, if there is an error
[view_wishlist] = view wishlist button
[wishlist] = wishlist status messages and contents


* category.php

Lists the items in a selected category.

[gallery_name] = the name of the gallery
[gallery_url] = the URL of the main gallery page
[search_form] = displays the search form
[category_name] = the name of the category
[category_items] = the list of items, created by the template `common/item-list.php'
[category_thumbnail_size] = the size of category thumbnail in pixels: 100, 200 or 300
[error] = displays error message on the page, if there is an error
[page_url] = the URL of the current page (the category)
[view_wishlist] = view wishlist button
[wishlist] = wishlist status messages and contents


* item-detail.php

An individual item page.

[gallery_name] = the name of the gallery
[gallery_url] = the URL of the main gallery page
[search_form] = displays the search form
[item_name] = name of the item
[item_description] = item description
[item_quantity] = quantity
[item_category_name] = the name of the item's category
[item_category_url] = the URL of the item's category
[item_photo] = main photo, in img tag
[item_photo_url] = the URL to the main photo
[item_thumbnails] = the alternate images, each img is inside li a tags and linked to its corresponding large image.
[item_thumbnails_url] = a comma separated list of URL for the alternate images
[item_thumbnail_size] = the size of category thumbnail in pixels: 100, 200 or 300
[item_tags] = a list of tags for the item, inside li element and linked to the corresponding tag page
[item_dimensions] = dimensions of the item in format X x Y x Z
[item_price] = the price of the item
[error] = displays error message on the page, if there is an error
[page_url] = the URL of the current page (the item)
[social_links] = a div containing the sharing links enabled via plugin settings.
[add_to_wishlist] = Add to wishlist button
[view_wishlist] = view wishlist button
[wishlist] = wishlist status messages and contents


* search-results.php

A list of search results.

[gallery_name] = the name of the gallery
[gallery_url] = the URL of the main gallery page
[search_form] = displays the search form
[search_terms] = the search term(s) for these search results
[search_items] = a list of items that match the search, created by the template `common/item-list.php'
[category_thumbnail_size] = the size of category thumbnail in pixels: 100, 200 or 300
[error] = displays error message on the page, if there is an error
[page_url] = the URL of the current page (the search results)
[view_wishlist] = view wishlist button
[wishlist] = wishlist status messages and contents


* tag.php

List of items for a tag.

[gallery_name] = the name of the gallery
[gallery_url] = the URL of the main gallery page
[search_form] = displays the search form
[tag_name] = the name of the tag
[tag_items] = a list of items for this tag, created by the template `common/item-list.php'
[category_thumbnail_size] = the size of category thumbnail in pixels: 100, 200 or 300
[error] = displays error message on the page, if there is an error
[page_url] = the URL of the current page (the tag)
[view_wishlist] = view wishlist button
[wishlist] = wishlist status messages and contents


* style.css

The style sheet for the template. This file is included on all gallery pages.