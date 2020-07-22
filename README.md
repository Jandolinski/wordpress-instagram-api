# wordpress-instagram-api
Connect Wordpress with Insagram Basic API

## Basic usage

1. copy *class-Instagram.php* to your theme
2. Include file in your *functions.php* file:

```php 
require_once( __DIR__ . '/inc/class-Instagram.php');
```
3. Create app on https://developers.facebook.com/, click add Product -> Instagram Basic Display
4. Go to *Instagram Basic Display -> Basic Display*
5. Set *Valid OAuth Redirect URIs, Deauthorize Callback URL, Data Deletion Request URL* to your website URL 
6. Get *Instagram App ID, Instagram App Secret*
7. Go to *Roles -> Instagram Testers* and add your Instagram account
8. Open Instagram app, go to Settings and confirm your app
9. Go to your *functions.php* file and create initial function:
```php
function init_Instagram() {
    $insta_app_secret = '{YOUR_APP_SECRET}';
    $insta_app_id = '{YOUR_APP_ID}';
    $insta_redirect_url = '{YOUR_WEBSITE_URL}';
}
```
10. Go to: `https://api.instagram.com/oauth/authorize?client_id={YOUR_APP_ID}&redirect_uri={YOUR_WEBSITE_URL}&scope=user_profile,user_media&response_type=code`

11.Authenticate your Instagram test user by signing into the Authorization Window, then click Authorize to grant your app access to your profile data. Upon success, the page will redirect you to the redirect URI you included in the previous step and append an Authorization Code. For example:

`https://google.com/?code=AQDp3TtBQQ...#_`

Note that #_ has been appended to the end of the redirect URI, but it is not part of the code itself. Copy the code (without the #_ portion) so you can use it in the next step.

12. Return your *functions.php* and update your `init_Instagram()` function:
```php
function init_Instagram() {
    $insta_app_secret = '{YOUR_APP_SECRET}';
    $insta_app_id = '{YOUR_APP_ID}';
    $insta_redirect_url = '{YOUR_WEBSITE_URL}';

    $insta_code = '{YOUR_CODE_FROM_PREVIOUS_STEP}';



    $insta = new Instagram();

    $insta->install($insta_app_secret, $insta_app_id, $insta_redirect_url, $insta_code);
}
```

13. Call the `init_Instagram()` function on your website **WARNING: The `init_Instagram()` function should be called only once! **
```php
init_Instagram();
```
14. Remove `init_Instagram();` function
15. Start using Instagram on your website:
```php
$instagram = new Instagram();

$instagram->check_for_token_refresh();

/**
* Set what information you want to get
* 
* id - The Media's ID.
* caption - The Media's caption text. Not returnable for Media in albums.
* media_type - The Media's type. Can be IMAGE, VIDEO, or CAROUSEL_ALBUM.
* media_url - The Media's URL.
* permalink - The Media's permanent URL.
* thumbnail_url - The Media's thumbnail image URL. Only available on VIDEO Media.
* timestamp - The Media's publish date in ISO 8601 format.
* username - The Media owner's username.
* 
* 
* e.g. array('id', 'caption', 'media_url', 'permalink');
* 
*/
$media = $insta->get_media(array('id', 'media_url', 'permalink'));

foreach($media as $item) {
    $permalink = $item['permalink'];
    $img_url = $item['media_url'];
    
    //...

}

```
