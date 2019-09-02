<?php
/**
 * Plugin Name: WPGraphQL Polls for WP-Polls
 * Plugin URI:
 * Description: A GraphQL interface to be used with WP-Polls plugins
 * Author: WPGraphQL, Andre Noberto, Adrien Becuwe, 7aduta, Lester Chan
 * Author URI:
 * Text Domain: wp-graphql-polls
 * Domain Path: /languages
 * Version: 0.1
 * Requires at least: 4.7.0
 * Requires PHP: 5.5
 * License: GPL v3
 * 
 * @package WPGraphQL_Pools
 */

namespace WPGraphQL\Polls;

defined( 'ABSPATH' ) or die( 'Na na na!' );
define( 'PLUGIN_DIR', dirname( __FILE__ ) );

require_once( PLUGIN_DIR . '/src/poll.php' );
require_once( PLUGIN_DIR . '/src/poll-answer.php' );
require_once( PLUGIN_DIR . '/src/poll-list.php' );
require_once( PLUGIN_DIR . '/src/poll-vote.php' );
require_once( PLUGIN_DIR . '/src/vote.php' );

if ( ! class_exists( '\WPGraphQL\Pools' ) ) :

  final class Polls {
    /**
     * Stores the instance of the Polls class
     * 
     * @var Polls
     * @access private
     */
    private static $instance;

    /**
     * The instance of the Polls object
     */
    public static function instance() {
      if ( ! isset( self::$instace ) && ! ( self::$instance instanceof Polls ) ) {
        self::$instance = new Polls;
      }

      self::$instance->init();

      /**
       * Returns the Polls instance
       */
      return self::$instance;
    }

    /**
     * Initializes the plugin
     * 
     * @access private
     * @return void
     */
    private static function init() {      
      /**
       * Filter the rootMutation fields
       */
      add_filter( 'graphql_rootMutation_fields', [
        '\WPGraphQL\Polls\Poll_Vote',
        'root_mutation_fields'
      ], 10, 1 );

      /**
       * Filter the rootQuery fields
       */
      add_filter( 'graphql_rootQuery_fields', [
        '\WPGraphQL\Polls\Poll_List',
        'root_query_fields'
      ], 10, 1 );

      add_action( 'graphql_resolve_field', [
        '\WPGraphQL\Polls\Poll_List',
        'single_poll_action'
      ], 10, 9 );
    }
  }

endif;

function init() {
  return Polls::instance();
}

add_action( 'plugins_loaded', '\WPGraphQL\Polls\init' );