<?php
namespace WPGraphQL\Polls;

use GraphQLRelay\Relay;
use WPGraphQL\Types;

class Poll_Answer extends \WPGraphQL\Type\WPObjectType {

  private static $fields;

  public function __construct() {
    $config = [
      'name' => 'PollAnswer',
      'fields' => self::fields(),
      'description' => __( 'Return a list of answers', 'wp-graphql-polls' ),
    ];
    parent::__construct( $config );
  }

  protected static function fields() {

    if ( null === self::$fields ) {
      self::$fields = function() {
        $fields = [
          'id' => [
            'type' => Types::int(),
            'description' => __( 'The id for voting references.', 'wp-graphql-polls' )
          ],
          'description' => [
            'type' => Types::string(),
            'description' => __( 'A choosable option for the current poll.', 'wp-graphql-polls' )
          ],
          'votes' => [
            'type' => Types::int(),
            'description' => __( 'The current number of votes for this option.', 'wp-graphql-polls' )
          ]
        ];

        return self::prepare_fields( $fields, 'PollAnswer' );
      };
    }

    return ! empty( self::$fields ) ? self::$fields : null;
  }
}