<?php
/**
 * Deployment configuration
 *
 * It's preferable to configure the script using this file instead of directly.
 *
 * Rename this file to `deploy-config.php` and edit the
 * configuration options here instead of directly in `deploy.php`.
 * That way, you won't have to edit the configuration again if you download the
 * new version of `deploy.php`.
 *
 * @version 1.3.1
 */

/**
 * Protect the script from unauthorized access by using a secret access token.
 * If it's not present in the access URL as a GET variable named `sat`
 * e.g. deploy.php?sat=project-client-name the script is not going to deploy.
 *
 * @var string
 */
define( 'SECRET_ACCESS_TOKEN', 'project-client-name' );

/**
 * The address of the remote Git repository that contains the code that's being
 * deployed.
 * If the repository is private, you'll need to use the SSH address.
 *
 * @var string
 */
define( 'REMOTE_REPOSITORY', 'git@github.com:JayWood/DeployTests.git' );

/**
 * The branch that's being deployed.
 * Must be present in the remote repository.
 *
 * @var string
 */
define( 'BRANCH', 'master' );

/**
 * The location that the code is going to be deployed to.
 * Don't forget the trailing slash!
 *
 * Example: /srv/users/username/apps/appname/public/wp-content/
 *
 * @var string Full path including the trailing slash
 */
define( 'TARGET_DIR', '/home/jay/html/deploytest-stuffs' );

/**
 * Whether to delete the files that are not in the repository but are on the
 * local (server) machine.
 *
 * !!! WARNING !!! This can lead to a serious loss of data if you're not
 * careful. All files that are not in the repository are going to be deleted,
 * except the ones defined in EXCLUDE section.
 * BE CAREFUL!
 *
 * @var boolean
 */
define( 'DELETE_FILES', false );

/**
 * The directories and files that are to be excluded when updating the code.
 * Normally, these are the directories containing files that are not part of
 * code base, for example user uploads or server-specific configuration files.
 * Use rsync exclude pattern syntax for each element.
 *
 * @var serialized array of strings
 */
define('EXCLUDE', serialize( array(
	'.git',
	'node_modules',
	'deploy',
)));

/**
 * Temporary directory we'll use to stage the code before the update. If it
 * already exists, script assumes that it contains an already cloned copy of the
 * repository with the correct remote origin and only fetches changes instead of
 * cloning the entire thing.
 *
 * Note: This directory will be placed at the server root (not the user's /tmp)!
 *
 * @var string Full path including the trailing slash
 */
define( 'TMP_DIR', '/tmp/wdsbuild-' . md5( REMOTE_REPOSITORY ) . '/' );

/**
 * Whether to remove the TMP_DIR after the deployment.
 * It's useful NOT to clean up in order to only fetch changes on the next
 * deployment.
 *
 * Note: By not cleaning up, deployments + builds take about ~5 seconds
 * since there is no need to re-run yarn install.
 */
define( 'CLEAN_UP', false );

/**
 * Output the version of the deployed code.
 *
 * @var string Full path to the file name
 */
define( 'VERSION_FILE', TMP_DIR . 'VERSION' );

/**
 * Time limit for each command.
 *
 * @var int Time in seconds
 */
define( 'TIME_LIMIT', 90 );

/**
 * OPTIONAL
 * Backup the TARGET_DIR into BACKUP_DIR before deployment.
 *
 * @var string Full backup directory path e.g. `/tmp/`
 */
define( 'BACKUP_DIR', false );

/**
 * OPTIONAL
 * Whether to invoke composer after the repository is cloned or changes are
 * fetched. Composer needs to be available on the server machine, installed
 * globaly (as `composer`). See http://getcomposer.org/doc/00-intro.md#globally
 *
 * @var boolean Whether to use composer or not
 * @link http://getcomposer.org/
 */
define( 'USE_COMPOSER', false );

/**
 * OPTIONAL
 * The options that the composer is going to use.
 *
 * @var string Composer options
 * @link http://getcomposer.org/doc/03-cli.md#install
 */
define( 'COMPOSER_OPTIONS', '--no-dev' );

/**
 * OPTIONAL
 * The COMPOSER_HOME environment variable is needed only if the script is
 * executed by a system user that has no HOME defined, e.g. `www-data`.
 *
 * @var string Path to the COMPOSER_HOME e.g. `/tmp/composer`
 * @link https://getcomposer.org/doc/03-cli.md#composer-home
 */
define( 'COMPOSER_HOME', false );

/**
 * OPTIONAL
 * Run build.sh?
 */
define( 'BUILD_APP', false );

/**
 * The locations of the theme for building.
 */
// define( 'BUILD_DIR', 'themes/client-theme' );
define( 'BUILD_DIR', TMP_DIR .'wp-content/themes/client-theme' ); // Use tmp dir, so we can build there.

/**
 * OPTIONAL
 * Email address to be notified on deployment failure.
 *
 * @var string A single email address, or comma separated list of email addresses
 *      e.g. 'someone@example.com' or 'someone@example.com, someone-else@example.com, ...'
 */
define( 'EMAIL_ON_ERROR', 'contact@webdevstudios.com' );

define( 'ENVIRONMENTS', json_encode( array(
	'production' => array(
		'repo' => 'git@bitbucket.org:jaywood/anotherrepo.git',
		'remote_branch' => 'master',
		'local_branch' => 'master',
	),
) ) );