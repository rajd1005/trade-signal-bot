<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class TSB_Telegram_API {

    private $bot_token;

    public function __construct() {
        $this->bot_token = get_option( 'tsb_bot_token' );
    }

    public function send_message( $message, $reply_to_id = null, $target_chat_id = null ) {
        if ( empty( $this->bot_token ) ) return false;
        $chat_id = $target_chat_id ? $target_chat_id : get_option( 'tsb_free_chat_id' );
        if( empty( $chat_id ) ) return false;

        $url = "https://api.telegram.org/bot{$this->bot_token}/sendMessage";
        $body = array( 'chat_id' => $chat_id, 'text' => $message, 'parse_mode' => 'Markdown' );
        if ( $reply_to_id ) $body['reply_to_message_id'] = $reply_to_id;

        $response = wp_remote_post( $url, array( 'body' => $body, 'timeout' => 15 ) );
        if ( is_wp_error( $response ) ) return false;

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        return isset( $body['result']['message_id'] ) ? $body['result']['message_id'] : false;
    }

    public function delete_message( $chat_id, $message_id ) {
        if ( empty( $this->bot_token ) || empty($chat_id) || empty($message_id) ) return false;
        $url = "https://api.telegram.org/bot{$this->bot_token}/deleteMessage";
        wp_remote_post( $url, array( 'body' => array( 'chat_id' => $chat_id, 'message_id' => $message_id ) ) );
    }
}