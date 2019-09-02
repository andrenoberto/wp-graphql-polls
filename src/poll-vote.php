<?php
namespace WPGraphQL\Polls;

use GraphQLRelay\Relay;
use WPGraphQL\Types;

class Poll_Vote {

  private static $mutation;

  /**
   * Takes an array of fields from the RootMutation and return the fields
   * with the "poll" mutation field added
   * 
   * @param array $fields The fields in the RootMutation of the Schema
   * 
   * @return array $fields
   */
  public static function root_mutation_fields( $fields ) {
    $fields['vote'] = self::mutation();

    return $fields;
  }

  protected static function mutation() {

    if ( empty( self::$mutation ) ) {
      self::$mutation = Relay::mutationWithClientMutationId([
        'name' => 'Vote',
        'isPrivate' => false,
        'description' => __( 'Inserts a vote in a poll', 'wp-graphql-polls' ),
        'inputFields' => [
          'id' => [
            'type' => Types::non_null( Types::int() ),
            'description' => __( 'The poll id you want to insert a vote', 'wp-graphql-polls' )
          ],
          'userId' => [
            'type' => Types::non_null( Types::int() ),
            'description' => __( 'The user id that are voting', 'wp-graphql-polls' )
          ],
          'answers' => [
            'type' => Types::non_null( Types::string() ),
            'description' => __( 'The answers you are voting.', 'wp-graphql-polls' )
          ],
        ],
        'outputFields' => [
          'status' => [
            'type' => Types::int(),
            'description' => __( 'The status code of the request.', 'wp-graphql-polls' )
          ],
          'message' => [
            'type' => Types::string(),
            'description' => __( 'Describes the status of the request.', 'wp-graphql-polls' )
          ]
        ],
        'mutateAndGetPayload' => function ( $input ) {

          /**
           * Register a vote and returns a confirmation for the user
           */
          return Vote::vote( $input['id'], $input['userId'], trim ( $input['answers'] ) );
        }
      ]);
    }

    return ( ! empty( self::$mutation ) ) ? self::$mutation : null;
  }
}