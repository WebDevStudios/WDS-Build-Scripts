<?php

/**
 * Grabs the branch this was pushed to.
 *
 * GitHub branches are prefixed with 'refs/heads/' so it's
 * simple enough to just replace that and return whats left.
 *
 * @return string|boolean The branch name on success, false otherwise.
 *
 * @author JayWood
 * @since  NEXT
 */
function get_branch_pushed() {

	$payload = get_payload();

	if ( user_is_beanstalk() ) {
		return $payload->branch;
	} elseif ( user_is_github() ) {
		return str_replace( 'refs/heads/', '', $payload->refs );
	}
	return false;
}

/**
 * Grabs the payload from the deploy hook.
 *
 *
 * @return mixed
 *
 * @author JayWood
 * @since  NEXT
 */
function get_payload() {
	static $payload;

	if ( null !== $payload ) {
		return $payload;
	}

	if ( user_is_beanstalk() ) {
		// Beanstalk has a special way of handling the payload.
		$input = json_decode( file_get_contents( 'php://input' ) );
		if ( isset( $input->payload ) ) {
			$payload = $input->payload;
		}
	} elseif ( user_is_github() ) {
		$input = $_REQUEST['payload'] ?: '';
		$payload = json_decode( $input );
	}

	return $payload;
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
		if ( empty( $env['remote_branch'] ) || empty( $env['repo'] ) || empty( $env['listen_branch'] ) ) {
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

/**
 * Helper that validates a branch call.
 *
 * Checks the constants and the environments registered, if the branches
 * do not match, the script will return false.
 *
 * @return bool
 *
 * @author JayWood
 * @since  NEXT
 */
function is_valid_branch() {
	$branch_pushed = get_branch_pushed();
	if ( empty( $branch_pushed ) ) {
		return false;
	}

	$env = get_environments();
	if ( ! empty( $env ) ) {
		foreach( $env as $environment_name => $data ) {
			if ( $data['local_branch'] === $branch_pushed ) {
				return true;
			}
		}
	}

	if ( defined( 'BRANCH' ) && ! empty( BRANCH ) ) {
		return BRANCH === $branch_pushed;
	}

	return false;
}