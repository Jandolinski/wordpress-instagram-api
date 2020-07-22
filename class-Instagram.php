<?php

class Instagram
{  

    private $app_secret;
    private $app_id;
    private $redirect_url;
    private $code;

    private $short_access_token;
    private $long_access_token;
    private $long_access_token_expires;

    function __construct() {
    }

    function install($app_secret, $app_id, $redirect_url, $code) {
        $this->app_secret = $app_secret;
        $this->app_id = $app_id;
        $this->redirect_url = $redirect_url;
        $this->code = $code;

        // Create tables
        $this->create_instagram_api_table();
        $this->create_instagram_api_data_table();

        // Token install
        $this->get_short_access_token($this->code);
        $this->exchange_short_token_to_long($this->short_access_token);


        $this->save_token_to_db($this->long_access_token, $this->long_access_token_expires);
        $this->save_instagram_data_to_db($this->app_secret, $this->app_id, $this->redirect_url);

    }

    function get_short_access_token($code) {

        $connection_c = curl_init();
        curl_setopt($connection_c, CURLOPT_URL, 'https://api.instagram.com/oauth/access_token');
        curl_setopt($connection_c, CURLOPT_POST, 1);
        curl_setopt($connection_c, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($connection_c, CURLOPT_POSTFIELDS, "client_id=". $this->app_id ."&client_secret=". $this->app_secret ."&grant_type=authorization_code&redirect_uri=". $this->redirect_url ."&code=" . $code);
        $json_return = curl_exec($connection_c);
        curl_close($connection_c);
        $response = json_decode($json_return, true);

        $this->short_access_token = $response['access_token'];
        
    }

    function exchange_short_token_to_long($token) {

        $connection_c = curl_init();
        $connection_c_url = 'https://graph.instagram.com/access_token?grant_type=ig_exchange_token&client_secret=' . $this->app_secret . '&access_token=' . $token;
        curl_setopt($connection_c, CURLOPT_URL, $connection_c_url);
        curl_setopt($connection_c, CURLOPT_RETURNTRANSFER, 1);
        $connection_c_json_return = curl_exec($connection_c);
        curl_close($connection_c);
        $connection_c_json_decoded = json_decode($connection_c_json_return, true);
        $long_access_token = $connection_c_json_decoded['access_token'];
        $long_access_token_expires = $connection_c_json_decoded['expires_in'];

        $long_access_token_expires_date = new DateTime(date('Y-m-d H:i:s') . '+' . $long_access_token_expires . ' seconds');

        $long_access_token_expires_date_formatted = $long_access_token_expires_date->format('Y-m-d H:i:s');

        $this->long_access_token = $long_access_token;
        $this->long_access_token_expires = $long_access_token_expires_date_formatted;
    }

    function save_token_to_db($token, $expires) {
        global $wpdb;

        $result = $wpdb->get_results("SELECT id FROM ". $wpdb->prefix . "instagram_api");

        // if db empty
        if(count($result) == 0) {
            
            $wpdb->insert($wpdb->prefix . 'instagram_api', array(
                'long_access_token' => $token,
                'expires_in' => $expires
            ));

        } else {
            $row_id = $result[0]->id;

            $wpdb->replace($wpdb->prefix . 'instagram_api', array(
                'id' => $row_id,
                'long_access_token' => $token,
                'expires_in' => $expires
            ));
        }

    }
    
    function save_instagram_data_to_db($app_secret, $app_id, $redirect_url) {

        global $wpdb;

        $result = $wpdb->get_results("SELECT id FROM ". $wpdb->prefix . "instagram_api_data");

        // if db empty
        if(count($result) == 0) {
            
            $wpdb->insert($wpdb->prefix . 'instagram_api_data', array(
                'app_secret' => $app_secret,
                'app_id' => $app_id,
                'redirect_url' => $redirect_url
            ));

        } else {

            $row_id = $result[0]->id;

            $wpdb->replace($wpdb->prefix . 'instagram_api_data', array(
                'id' => $row_id,
                'app_secret' => $token,
                'app_id' => $expires,
                'redirect_url' => $redirect_url
            ));
        }

    }
    
    function create_instagram_api_table() {
        global $wpdb;
    
        $charset_collate = $wpdb->get_charset_collate();
    
        $table_name = $wpdb->prefix . "instagram_api";
        
        $sql = "CREATE TABLE $table_name (
          id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
          long_access_token VARCHAR(200) NOT NULL,
          expires_in VARCHAR(200) NOT NULL,
          created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP 
        ) $charset_collate;";
        
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );    
    }
    
    function create_instagram_api_data_table() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
    
        $table_name = $wpdb->prefix . "instagram_api_data";
        
        $sql = "CREATE TABLE $table_name (
          id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
          app_secret VARCHAR(200) NOT NULL,
          app_id VARCHAR(200) NOT NULL,
          redirect_url VARCHAR(200) NOT NULL
        ) $charset_collate;";
        
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );    
    }

    function get_token() {

        global $wpdb;

        $result = $wpdb->get_results("SELECT * FROM ". $wpdb->prefix . "instagram_api");

        return $result[0]->long_access_token;

    }

    function get_token_expire_date() {

        global $wpdb;

        $result = $wpdb->get_results("SELECT * FROM ". $wpdb->prefix . "instagram_api");

        return $result[0]->expires_in;

    }

    function get_app_secret() {
        
        global $wpdb;

        $result = $wpdb->get_results("SELECT * FROM ". $wpdb->prefix . "instagram_api_data");

        return $result[0]->app_secret;
    
    }

    function check_for_token_refresh() {

        global $wpdb;

        $expire_date = $this->get_token_expire_date();
        // 1 day before expire date
        $expire_date_time = strtotime($expire_date . '-1 day');

        $today = date('Y-m-d H:i:s');
        $today_time = strtotime($today);

        if($today_time >= $expire_date_time) {
            $this->refresh_token();
        }
    }

    function refresh_token() {
        global $wpdb;

        $old_token = $this->get_token();

        $app_secret = $this->get_app_secret();

        $connection_c = curl_init();
        $connection_c_url = 'https://graph.instagram.com/access_token?grant_type=ig_exchange_token&client_secret=' . $app_secret . '&access_token=' . $old_token;
        curl_setopt($connection_c, CURLOPT_URL, $connection_c_url);
        curl_setopt($connection_c, CURLOPT_RETURNTRANSFER, 1);
        $connection_c_json_return = curl_exec($connection_c);
        curl_close($connection_c);
        $connection_c_json_decoded = json_decode($connection_c_json_return, true);

        $new_access_token = $connection_c_json_decoded['access_token'];
        $new_expires = $connection_c_json_decoded['expires_in'];

        $new_expires_date = new DateTime(date('Y-m-d H:i:s') . '+' . $new_expires . ' seconds');

        $new_expires_date_formatted = $new_expires_date->format('Y-m-d H:i:s');

        $this->save_token_to_db($new_access_token, $new_expires_date_formatted);
    }

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
    function get_media($args) {

        $args_str = implode(',', $args);

        $token = $this->get_token();

        $connection_c = curl_init();
        $connection_c_media_url = "https://graph.instagram.com/me/media?fields=$args_str&access_token=$token";

        curl_setopt($connection_c, CURLOPT_URL, $connection_c_media_url);
        curl_setopt($connection_c, CURLOPT_RETURNTRANSFER, 1);

        $connection_c_json_return = curl_exec($connection_c);

        curl_close($connection_c);

        $connection_c_json_decoded = json_decode($connection_c_json_return, true);

        $insta_data = $connection_c_json_decoded['data'];

        return $insta_data;

    }
}