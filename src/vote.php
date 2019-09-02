<?php
namespace WPGraphQL\Polls;

class Vote {
  
  public static function vote( $poll_id, $user_id, $answers ) {
    if ( self::validate_received_answers( $poll_id, $answers ) ) {

      if ( self::is_user_allowed_to_vote( $answers ) ) {

        if ( self::is_poll_open( $poll_id ) ) {

          if ( self::has_user_sent_more_answers_than_allowed( $poll_id, $answers ) ) {
            return self::get_user_exceeded_poll_max_answers_error_response( $poll_id );
          }

          if ( self::validate_user( $user_id ) ) {
            return self::get_user_unauthorized_error_response();
          }

          if ( ! self::has_user_voted( $poll_id, $user_id ) ) {
            self::set_cookies_when_enabled( $poll_id );
            self::update_answers_number_of_votes( $poll_id, $answers );
            $poll_successfully_updated = self::update_poll_statistics( $poll_id, $answers );

            if ( $poll_successfully_updated ) {
              self::insert_user_votes( $poll_id, $answers, $user_id );
              $response = self::get_vote_success_response();
            } else {
              $response = self::get_internal_server_error_response();
            }

          } else {
            $response = self::get_vote_already_registered_error_response();
          }

        } else {
          $response = self::get_poll_closed_error_response();
        }

      } else {
        $response = self::get_invalid_poll_or_unauthorized_error_response();
      }

    } else {
      $response = self::get_invalid_answers_error_response();
    }

    return ! empty( $response ) ? $response : [];
  }

  public static function has_user_voted( $poll_id, $user_id ) {
    $poll_logging_method = self::get_poll_logging_method();

    switch ( $poll_logging_method ) {
      // Do Not Log
      case 0:
        return false;
      // Logged By Cookie
      case 1:
        return check_voted_cookie( $poll_id );
      // Logged By IP
      case 2:
        return  check_voted_ip( $poll_id );
      // Logged By Cookie And IP
      case 3:
        $check_voted_cookie = check_voted_cookie( $poll_id );
        if( ! empty( $check_voted_cookie ) ) {
          return  $check_voted_cookie;
        } else {
          return false;
        }
      // Logged By Username
      case 4:
        return self::has_user_voted_in_poll( $poll_id, $user_id );
    }
  }

  public static function has_user_voted_in_poll( $poll_id, $user_id ) {
    global $wpdb;
    $votes = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->pollsip WHERE pollip_qid = %d AND pollip_userid = %d", $poll_id, $user_id ) );
    return $votes > 0;
  }

  public static function validate_received_answers( $poll_id, $answers ) {
    $received_answers = self::get_unique_ids_array_from_string( $answers );
    $poll_answers = self::get_answer_ids_by_poll_id( $poll_id );

    if ( count ( $received_answers ) === 0 ) {
      return false;
    }
    
    return count( array_intersect( $received_answers, $poll_answers ) ) === count( $received_answers );
  }

  public static function get_unique_ids_array_from_string( $ids ) {
    return array_unique(
      array_map(
        'intval',
        array_map( 'sanitize_key', explode( ',', $ids ) )
      )
    );
  }

  public static function get_answer_ids_by_poll_id( $id ) {
    global $wpdb;
    return $wpdb->get_col( $wpdb->prepare( "SELECT polla_aid FROM $wpdb->pollsa WHERE polla_qid = %d", $id ) );
  }

  public static function get_number_of_allowed_answers_from_poll_id( $id ) {
    global $wpdb;
    $poll = $wpdb->get_row( $wpdb->prepare( "SELECT pollq_multiple FROM $wpdb->pollsq WHERE pollq_id = %d AND pollq_active = 1", $id ) );
    
    return $poll->pollq_multiple || 1;
  }

  public static function is_poll_open( $id ) {
    global $wpdb;
    $number_of_open_polls = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->pollsq WHERE pollq_id = %d AND pollq_active = 1", $id ) );

    return $number_of_open_polls > 0;
  }

  public static function get_number_of_received_answers( $answers ) {
    $received_answers = self::get_unique_ids_array_from_string( $answers );

    return count( $received_answers );
  }

  public static function has_user_sent_more_answers_than_allowed( $poll_id, $answers ) {
    $number_of_allowed_answers  = self::get_number_of_allowed_answers_from_poll_id( $poll_id );
    $number_of_received_answers = self::get_number_of_received_answers( $answers );

    return $number_of_received_answers > $number_of_allowed_answers;
  }

  public static function get_user_exceeded_poll_max_answers_error_response( $poll_id ) {
    $number_of_allowed_answers  = self::get_number_of_allowed_answers_from_poll_id( $poll_id );

    return self::get_response(
      400,
      'This poll does not allow you to vote in more than ' . $number_of_allowed_answers . ' answers.'
    );
  }

  public static function get_user_unauthorized_error_response() {
    return self::get_response(
      403,
      'You are not authorized to vote in this poll.'
    );
  }

  public static function get_internal_server_error_response() {
    return self::get_response(
      500,
      'Something went wrong, please try it again.'
    );
  }

  public static function get_vote_already_registered_error_response() {
    return self::get_response(
      409,
      'You have already voted in this poll.'
    );
  }

  public static function get_poll_closed_error_response() {
    return self::get_response(
      401,
      'You can not vote in a poll that is currently closed.'
    );
  }

  public static function get_invalid_poll_or_unauthorized_error_response() {
    return self::get_response(
      403,
      'The poll id is not valid or you are not authorized to vote.'
    );
  }

  public static function get_invalid_answers_error_response() {
    return self::get_response(
      400,
      'The provided answers are not valid.'
    );
  }

  public static function get_vote_success_response() {
    return self::get_response(
      201,
      'Thanks for your vote.'
    );
  }

  public static function get_response( $status_code, $message ) {
    $response = [
      'status'  => $status_code,
      'message' => $message
    ];

    return $response;
  }

  public static function is_user_allowed_to_vote( $answers ) {
    return check_allowtovote();
  }

  public static function get_poll_logging_method() {
    return (int) get_option( 'poll_logging_method' );
  }

  public static function get_poll_cookielog_expiry() {
    return (int) get_option( 'poll_cookielog_expiry' );
  }

  public static function validate_user( $user_id ) {
    $poll_logging_method = self::get_poll_logging_method();

    if ( $poll_logging_method === 4 ) {
      /**
       * If you are using any authorization plugin with GraphQL
       * you can validade if the voter has the same user id
       * as the one who's sending the request
       */
      $user_id = apply_filters( 'wp_graphql_polls_validate_user_id', $user_id );
      
      return $user_id === 0;
    }

      return false;
  }

  public static function get_voter_username_by_id( $user_id ) {
    if ( !empty( $user_id ) ) {
      $user = get_userdata( $user_id );
      $username = $user->user_login;
    } elseif ( ! empty( $_COOKIE['comment_author_' . COOKIEHASH] ) ) {
      $username = $_COOKIE['comment_author_' . COOKIEHASH];
    } else {
      $username = __( 'Guest', 'wp-polls' );
    }
    
    $username = sanitize_text_field( $username );

    return $username;
  }

  public static function set_cookies_when_enabled( $poll_id ) {
    $poll_logging_method = self::get_poll_logging_method();

    if ( $poll_logging_method === 1 || $poll_logging_method === 3 ) {
      $cookie_expiry = self::get_poll_cookielog_expiry();
      
      if ( $cookie_expiry === 0 ) {
        $cookie_expiry = YEAR_IN_SECONDS;
      }
      
      setcookie(
        'voted_' . $poll_id,
        implode( ',', $received_answers ),
        $vote_timestamp + $cookie_expiry,
        apply_filters( 'wp_polls_cookiepath', SITECOOKIEPATH )
      );
    }
  }

  public static function update_answers_number_of_votes( $poll_id, $answers ) {
    $normalized_answers = self::get_unique_ids_array_from_string( $answers );
    $index = 0;
    
    foreach ( $normalized_answers as $answer_id ) {
      if ( self::update_answer_number_of_votes( $poll_id, $answer_id ) ) {
        unset( $normalized_answers[$index] );
      }

      $index++;
    }
  }

  public static function update_answer_number_of_votes( $poll_id, $answer_id ) {
    global $wpdb;
    $updated_answer = $wpdb->query( "UPDATE $wpdb->pollsa SET polla_votes = (polla_votes + 1) WHERE polla_qid = $poll_id AND polla_aid = $answer_id" );

    return ! ! $updated_answer;
  }

  public static function update_poll_statistics( $poll_id, $answers ) {
    global $wpdb;
    $normalized_answers       = self::get_unique_ids_array_from_string( $answers );
    $update_operation_result  = $wpdb->query( "UPDATE $wpdb->pollsq SET pollq_totalvotes = (pollq_totalvotes+" . count( $normalized_answers ) . "), pollq_totalvoters = (pollq_totalvoters + 1) WHERE pollq_id = $poll_id AND pollq_active = 1" );

    return ! ! $update_operation_result;
  }

  public static function insert_user_votes( $poll_id, $answers, $user_id ) {
    global $wpdb;
    $normalized_answers = self::get_unique_ids_array_from_string( $answers );
    $vote_details       = self::get_vote_details_from_user_id( $user_id );

    foreach ( $normalized_answers as $answer_id ) {
      $wpdb->insert(
        $wpdb->pollsip,
        array(
          'pollip_qid'        => $poll_id,
          'pollip_aid'        => $answer_id,
          'pollip_ip'         => $vote_details['user_ip'],
          'pollip_host'       => $vote_details['user_host'],
          'pollip_timestamp'  => $vote_details['vote_timestamp'],
          'pollip_user'       => $vote_details['username'],
          'pollip_userid'     => $user_id
        ),
        array(
          '%s',
          '%s',
          '%s',
          '%s',
          '%s',
          '%s',
          '%d'
        )
      );
    }
  }

  public static function get_vote_details_from_user_id( $user_id ) {
    $details = array(
      'username'        => self::get_voter_username_by_id( $user_id ),
      'user_ip'         => get_ipaddress(),
      'user_host'       => @gethostbyaddr( $user_ip ),
      'vote_timestamp'  => current_time( 'timestamp' )
    );

    return $details;
  }
}