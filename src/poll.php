<?php
namespace WPGraphQL\Polls;

class Poll {
  public static function get_polls() {
    $polls_from_database  = self::get_polls_from_database();
    $polls                = array();
    
    foreach ( $polls_from_database as $poll ) {
      $poll_id = (int) $poll->pollq_id;
      $polls[] = self::get_graphql_poll_by_id( $poll_id );
    }

    return $polls;
  }

  public static function get_poll_by_id( $id ) {
    $poll               = array();
    $poll_from_database = self::get_polls_from_database_by_id( $id );

    if ( self::is_poll_available( $id ) ) {
      $poll[] = self::get_graphql_poll_by_id( $id );
    }

    return $poll;
  }

  public static function get_polls_from_database() {
    global $wpdb;
    return $wpdb->get_results( "SELECT * FROM $wpdb->pollsq  ORDER BY pollq_timestamp DESC" );
  }

  public static function get_polls_from_database_by_id( $id ) {
    global $wpdb;
    return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wpdb->pollsq WHERE pollq_id = %d", $id ) );
  }

  public static function get_poll_voters_from_poll( $poll_id ) {
    global $wpdb;
    return $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT pollip_user FROM $wpdb->pollsip WHERE pollip_qid = %d AND pollip_user != %s ORDER BY pollip_user ASC", $poll_id, __( 'Guest', 'wp-polls' ) ) );
  }

  public static function get_normalized_poll_details_by_id( $id )
  {
    global $wpdb;
    $normalized_answers = array();
    $normalized_details = array();
    $poll_details       = self::get_poll_details_by_id( $id );

    $question     = $poll_details->pollq_question;
    $total_votes  = (int) $poll_details->pollq_totalvotes;
    $total_voters = (int) $poll_details->pollq_totalvoters;
    $start_date   = self::format_date_from_mysql( $poll_details->pollq_timestamp );
    $max_answers  = (int) $poll_details->pollq_multiple;
    $expiry_date  = trim ( $poll_details->pollq_expiry );
    $active       = (int) $poll_details->pollq_active;
    $voted        = self::has_user_voted( $id );
    
    if ( empty( $expiry_date ) ) {
        $end_date = __( 'No Expiry', 'wp-polls' );
    } else {
        $end_date = self::format_date_from_mysql( $poll_expiry );
    }

    $answers  = self::get_poll_answers_by_id( $id );

    foreach ( $answers as $answer ) {
      $normalized_answers[] = array(
        'id'            => (int) $answer->polla_aid,
        'description'   => (string) $answer->polla_answers,
        'votes'         => (int) $answer->polla_votes,
      );
    }
    
    
    $normalized_details['id']             = $id;
    $normalized_details['answers']        = $normalized_answers;
    $normalized_details['max_answers']    = $max_answers || 1;
    $normalized_details['open']           = ( $active === 1 ) ? true : false;
    $normalized_details['question']       = $question;
    $normalized_details['total_votes']    = $total_votes;
    $normalized_details['total_voters']   = $total_voters;
    $normalized_details['voted']          = $voted;

    return $normalized_details;
  }

  public static function get_poll_details_by_id( $id ) {
    global $wpdb;
    return $wpdb->get_row( $wpdb->prepare( "SELECT pollq_id, pollq_question,  pollq_totalvotes, pollq_active, pollq_timestamp, pollq_expiry, pollq_multiple, pollq_totalvoters FROM $wpdb->pollsq WHERE pollq_id = %d LIMIT 1", $id ) );
  }

  public static function format_date_from_mysql( $date ) {
    return mysql2date(
      sprintf( __( '%s @ %s', 'wp-polls' ), get_option( 'date_format' ), get_option( 'time_format' ) ),
      gmdate( 'Y-m-d H:i:s', $date )
    );
  }

  public static function get_poll_answers_by_id ( $id ) {
    global $wpdb;
    list( $order_by, $sort_order ) = _polls_get_ans_sort();
    return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wpdb->pollsa WHERE polla_qid = %d ORDER BY $order_by $sort_order", $id ) );
  }

  public static function has_user_voted( $poll_id ) {
    $user_id = apply_filters( 'wp_graphql_polls_validate_user_id', null );

    if ( $user_id > 0 ) {
      return Vote::has_user_voted_in_poll( $poll_id, $user_id );
    }

    return false;
  }

  public static function is_poll_available( $id ) {
    $poll_from_database = self::get_polls_from_database_by_id( $id );

    return ! ! $poll_from_database;
  }

  public static function get_graphql_poll_by_id( $id ) {
    $poll_details = self::get_normalized_poll_details_by_id( $id );
      
    return self::get_graphql_array_from_details( $poll_details );
  }

  public static function get_graphql_array_from_details( $poll_details ) {
    return array(
      'id'          => $poll_details['id'],
      'answers'     => $poll_details['answers'],
      'maxAnswers'  => $poll_details['max_answers'],
      'open'        => $poll_details['open'],
      'question'    => $poll_details['question'],
      'totalVotes'  => $poll_details['total_votes'],
      'voted'       => $poll_details['voted']
    );
  }
}