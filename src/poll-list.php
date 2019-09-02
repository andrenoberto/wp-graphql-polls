<?php
namespace WPGraphQL\Polls;

use WPGraphQL\Types;

class Poll_List extends \WPGraphQL\Type\WPObjectType {
  
  private static $fields;

  public function __construct() {
    $config = [
      'name' => 'PollList',
      'fields' => self::fields(),
      'description' => __( 'Returns a list of the existing polls', 'wp-graphql-polls' ),
    ];
    parent::__construct( $config );
  }

  public static function root_query_fields( $fields ) {
    $fields['polls'] = [
      'type' => Types::list_of( new Poll_List() ),
      'args' => [
        'id' => [
          'type' => Types::int(),
          'description' => __( 'The poll id you want to query', 'wp-graphql-polls' )
        ],
      ],
      'description' => __( 'Returns a list of the existing polls', 'wp-graphql-polls' ),
      'resolve' => function() {
        return Poll::get_polls();
      }
    ];

    return $fields;
  }

  public static function single_poll_action( $result, $source, $args, $context, $info, $type_name, $field_key, $field, $field_resolver ) {
    if ( 'polls' === $field_key && ! empty( $args['id'] ) ) {
      $result = array(
        Poll::get_poll_by_id( $args['id'] )
      );
    }

    return $result;
  }

  protected static function fields() {

    if ( null === self::$fields ) {
      self::$fields = function() {
        $fields = [
          'id' => [
            'type' => Types::int(),
            'description' => __( 'The poll id', 'wp-graphql-polls' )
          ],
          'question' => [
            'type' => Types::string(),
            'description' => __( 'The poll\'s question', 'wp-graphql-polls' )
          ],
          'totalVotes' => [
            'type' => Types::int(),
            'description' => __( 'The current number of votes', 'wp-graphql-polls' )
          ],
          'answers' => [
            'type' => Types::list_of( new Poll_Answer() ),
            'description' => __( 'The answers for the current poll', 'wp-graphql-polls' )
          ],
          'open' => [
            'type' => Types::boolean(),
            'description' => __( 'Tells if the current poll is available for voting or not', 'wp-graphql-polls' )
          ],
          'maxAnswers' => [
            'type' => Types::int(),
            'description' => __( 'The maximum number of answers allowed in this poll', 'wp-graphql-polls' )
          ],
          'voted' => [
            'type' => Types::boolean(),
            'description' => __( 'Tells if the user has voted or not', 'wp-graphql-polls' )
          ]
        ];

        return self::prepare_fields( $fields, 'PollList' );
      };
    }

    return ! empty( self::$fields ) ? self::$fields : null;
  }
}