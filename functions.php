<?php

/**
 * Grabs the branch this was pushed to.
 *
 * GitHub branches are prefixed with 'refs/heads/' so it's
 * simple enough to just replace that and return whats left.
 *
 * @return string
 *
 * @author JayWood
 * @since  NEXT
 */
function get_branch_pushed() {
	if ( ! isset( $_REQUEST['payload'] ) ) {
		return '';
	}

	$payload = $_REQUEST['payload'];
	$decoded = json_decode( $payload );
	if ( ! $decoded || ! $decoded->refs ) {
		return '';
	}

	return str_replace( 'refs/heads/', '', $decoded->refs );
}

/**
 * Gets and parses a list of environments from the constant.
 *
 * @return array an array of environments.
 *
 * @author JayWood
 * @since  NEXT
 */
function get_environments() {
	static $environments;

	if ( null !== $environments ) {
		return $environments;
	}

	if ( ! defined( 'ENVIRONMENTS' ) || empty( ENVIRONMENTS ) ) {
		$environments = array();
		return $environments;
	}

	$environments = json_decode( ENVIRONMENTS, true );
	$out = [];

	foreach( $environments as $name => $env ) {
		if ( empty( $env['remote_branch'] ) || empty( $env['repo'] ) || empty( $env['local_branch'] ) ) {
			continue;
		}
		$out[ $name ] = $env;
	}

	// Update the static variable.
	$environments = $out;

	return $out;
}

/**
 * Checks HTTP user agent for beanstalk.
 *
 * @return bool
 *
 * @author JayWood
 * @since  NEXT
 */
function user_is_beanstalk() {
	if ( empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
		return false;
	}

	return $_SERVER['HTTP_USER_AGENT'] === 'beanstalkapp.com';
}

/**
 * Checks the user agent for GitHub headers.
 *
 * @return bool
 * @link https://developer.github.com/webhooks/#delivery-headers
 *
 * @author JayWood
 * @since  NEXT
 */
function user_is_github() {
	if ( empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
		return false;
	}

	return 0 === strpos( $_SERVER['HTTP_USER_AGENT'], 'GitHub-Hookshot/' );
}