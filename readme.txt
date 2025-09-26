=== Dropify ===
Contributors: jhaineymilevis
Tags: woocommerce, dropi, dropshipping
Requires at least: 5.2.3
Tested up to: 6.4.2
Requires PHP: 7.0
Stable tag: 4.6.9
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This plugin enables the import of products from the dropi platform to woocomerce

== Description ==

This plugin enables the import of products from the dropi platform to woocomerce

It also generates the orders in dropi when the order is placed

== Installation ==


1. Upload `dropi_woo_integration` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Dropi Menu, Ingress your Access Token.


== Frequently Asked Questions ==

= What is dropi? =
It is a dropshipping platform in Colombia: https://dropi.co
= Anyone can register? =
Yes, anyone can create a free account and start dropshipping
= How do I code the authentication token? =
You must register on dropi.co as a dropshipper. Then go to the My stores menu, and create a store with the name of the domain where you have your woocomerce installed, when creating the store you will be generated an access token

== Changelog ==
= 1.0 =
* First version
* Import products and create dropi orders
= 1.1 =
* A modal is shown to edit the product before importing
* Ajax import product and sync order
= 1.2 =
* fix secutiry issues
= 1.3 =
* show image modal on click
* fix bogota city name
* show success message on token save
= 1.4 =
* fix image default when product dont have gallery
* fix Description html error on product list
* fix some cities names
= 1.5 =
* fix SIN RECAUDO orders
= 1.6 =
* fix products events js 
= 1.7 = 
* fix constants
= 1.8 = 
* fix images url
= 1.9 = 
* update and show imported product
* send metada from dropi to woocomerce
* fix sale price
= 2.0 = 
* privated_products for providers *
= 2.1 = 
* add avatar *
= 2.2 = 
* add variations to order notes *
= 2.3 = 
* save order id on dropi *
= 2.4 = 
* fix cities troubles *
= 2.5 = 
* fix shipping troubles *
= 2.5 = 
* fix updates *
= 2.6 = 
* fix updates *
= 2.7 = 
* Sync by SKU. *
= 2.8 = 
* Fix quantity bug. *
= 2.9 = 
* Fix quantity bug. *
= 2.9.1 = 
* Fix total bug. *
= 2.9.2 = 
* Fix import bug. *
= 3.0 = 
* Add Variations Support *
= 3.1 = 
* Variations Fixs *
= 3.10 = 
* Fix cities and departments *
= 3.11 = 
* Massive sync *
* fix stock issue on vriant products *
= 3.13 = 
* Se añade soporte para dorpi panama *
= 3.17 = 
* fix variation problems *
= 3.23 = 
* create product if no exist for supplier users *
= 3.24 = 
* create product if no exist for supplier users fix *
* variables sync fix when varitna existe *
= 3.32 = 
* send zip code *
= 3.34 = 
* fix country *
= 3.44 = 
* correccion precio de envio no se enviaba a dropi *
= 3.46 = 
* filtro por productos que tengan stock*
* filtro por productos que tengan descripcion*
* creacion y asignacion de categoria al producto sincronizado*
= 3.47 = 
*concatena la direccion 2*
= 3.58 = 
*se incorpora el multitoken*
= 3.60 = 
* Se agrega funcionalidad para Chile, Panamá, México*
= 4.0 = 
* Se arregla el selector del checkout de ciudades*

== Upgrade Notice ==
= 1.0 =
Import products and create dropi orders
= 1.1 =
A modal is shown to edit the product before importing
Ajax import product and sync order
= 1.2 =
fix secutiry issues
= 1.3 =
show image modal on click
fix bogota city name
show success message on token save
= 1.4 =
fix image default when product dont have gallery
fix Description html error on product list
fix some cities names
= 1.5 =
fix SIN RECAUDO orders
= 1.6 = 
fix products events js 
= 1.7 = 
fix constants
= 1.8 = 
fix images url
= 1.9 = 
update and show imported product
send metada from dropi to woocomerce
fix sale price
= 2.0 = 
privated_products for providers
= 2.1 = 
add avatar
= 2.2 = 
add variations to order notes
= 2.3 = 
save order id on dropi
= 2.5 = 
fix cities troubles
= 2.5 = 
fix shipping troubles
= 2.5 = 
fix updates 
= 2.5 = 
fix updates
= 2.7 = 
Sync by SKU
= 2.8 = 
Fix quantity bug.
= 2.9.1 = 
Fix total bug.
= 2.9.2 = 
Fix import bug.
= 3.0 = 
Add Variations Support
= 3.1 = 
variations FIXS
= 3.10 = 
Fix cities and departments
= 3.11 = 
Massive sync
fix stock issue on vriant products
= 3.13 = 
Se añade soporte para dorpi panama 
= 3.17 = 
fix variation problems
= 3.23 = 
create product if no exist for supplier users
= 3.24 = 
create product if no exist for supplier users fix
variables sync fix when varitna existe
= 3.32 = 
send zip code
= 3.34 = 
fix country
= 3.44 = 
correccion precio de envio no se enviaba a dropi
= 3.46 = 
filtro por productos que tengan stock
filtro por productos que tengan descripcion
creacion y asignacion de categoria al producto sincronizado
= 3.47 = 
concatena la direccion 2
= 3.58 = 
se incorpora el multitoken
= 4.0 = 
* Se arregla el selector del checkout de ciudades*

== Screenshots ==
1. This show how to configure plugin adding token
2. This show how dropi products are showed


svn cp trunk tags/2.0
vn ci -m "tagging version 2.0"