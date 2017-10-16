<?php
/**
 * Simple PHP Git deploy script
 *
 * Automatically deploy the code using PHP and Git.
 *
 * @version 1.3.1
 * @link    https://github.com/markomarkovic/simple-php-git-deploy/
 */
// die();
// =========================================[ Configuration start ]===
/**
 * It's preferable to configure the script using `deploy-config.php` file.
 *
 * Rename `deploy-config.example.php` to `deploy-config.php` and edit the
 * configuration options there instead of here. That way, you won't have to edit
 * the configuration again if you download the new version of `deploy.php`.
 */
if ( file_exists( basename( __FILE__, '.php' ) . '-config.php' ) ) {
	define( 'CONFIG_FILE', basename( __FILE__, '.php' ) . '-config.php' );
	require_once CONFIG_FILE;
} else {
	define( 'CONFIG_FILE', __FILE__ );
}

// Helper functions
require_once 'functions.php';

/**
 * Protect the script from unauthorized access by using a secret access token.
 * If it's not present in the access URL as a GET variable named `sat`
 * e.g. deploy.php?sat=Bett...s the script is not going to deploy.
 *
 * @var string
 */
if ( ! defined( 'SECRET_ACCESS_TOKEN' ) ) { define( 'SECRET_ACCESS_TOKEN', 'BetterChangeMeNowOrSufferTheConsequences' );
}

/**
 * The address of the remote Git repository that contains the code that's being
 * deployed.
 * If the repository is private, you'll need to use the SSH address.
 *
 * @var string
 */
if ( ! defined( 'REMOTE_REPOSITORY' ) ) { define( 'REMOTE_REPOSITORY', 'https://github.com/markomarkovic/simple-php-git-deploy.git' );
}

/**
 * The branch that's being deployed.
 * Must be present in the remote repository.
 *
 * @var string
 */
if ( ! defined( 'BRANCH' ) ) { define( 'BRANCH', 'master' );
}

/**
 * The location that the code is going to be deployed to.
 * Don't forget the trailing slash!
 *
 * @var string Full path including the trailing slash
 */
if ( ! defined( 'TARGET_DIR' ) ) { define( 'TARGET_DIR', '/tmp/simple-php-git-deploy/' );
}

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
if ( ! defined( 'DELETE_FILES' ) ) { define( 'DELETE_FILES', false );
}

/**
 * The directories and files that are to be excluded when updating the code.
 * Normally, these are the directories containing files that are not part of
 * code base, for example user uploads or server-specific configuration files.
 * Use rsync exclude pattern syntax for each element.
 *
 * @var serialized array of strings
 */
if ( ! defined( 'EXCLUDE' ) ) { define('EXCLUDE', serialize(array(
	'.git',
)));
}

/**
 * Temporary directory we'll use to stage the code before the update. If it
 * already exists, script assumes that it contains an already cloned copy of the
 * repository with the correct remote origin and only fetches changes instead of
 * cloning the entire thing.
 *
 * @var string Full path including the trailing slash
 */
if ( ! defined( 'TMP_DIR' ) ) { define( 'TMP_DIR', '/tmp/spgd-' . md5( REMOTE_REPOSITORY ) . '/' );
}

/**
 * Whether to remove the TMP_DIR after the deployment.
 * It's useful NOT to clean up in order to only fetch changes on the next
 * deployment.
 */
if ( ! defined( 'CLEAN_UP' ) ) { define( 'CLEAN_UP', true );
}

/**
 * Output the version of the deployed code.
 *
 * @var string Full path to the file name
 */
if ( ! defined( 'VERSION_FILE' ) ) { define( 'VERSION_FILE', TMP_DIR . 'VERSION' );
}

/**
 * Time limit for each command.
 *
 * @var int Time in seconds
 */
if ( ! defined( 'TIME_LIMIT' ) ) { define( 'TIME_LIMIT', 0 );
}

/**
 * OPTIONAL
 * Backup the TARGET_DIR into BACKUP_DIR before deployment.
 *
 * @var string Full backup directory path e.g. `/tmp/`
 */
if ( ! defined( 'BACKUP_DIR' ) ) { define( 'BACKUP_DIR', false );
}

/**
 * OPTIONAL
 * Whether to invoke composer after the repository is cloned or changes are
 * fetched. Composer needs to be available on the server machine, installed
 * globaly (as `composer`). See http://getcomposer.org/doc/00-intro.md#globally
 *
 * @var boolean Whether to use composer or not
 * @link http://getcomposer.org/
 */
if ( ! defined( 'USE_COMPOSER' ) ) { define( 'USE_COMPOSER', false );
}

/**
 * OPTIONAL
 * The options that the composer is going to use.
 *
 * @var string Composer options
 * @link http://getcomposer.org/doc/03-cli.md#install
 */
if ( ! defined( 'COMPOSER_OPTIONS' ) ) { define( 'COMPOSER_OPTIONS', '--no-dev' );
}

/**
 * OPTIONAL
 * The COMPOSER_HOME environment variable is needed only if the script is
 * executed by a system user that has no HOME defined, e.g. `www-data`.
 *
 * @var string Path to the COMPOSER_HOME e.g. `/tmp/composer`
 * @link https://getcomposer.org/doc/03-cli.md#composer-home
 */
if ( ! defined( 'COMPOSER_HOME' ) ) { define( 'COMPOSER_HOME', false );
}

/**
 * OPTIONAL
 * Run build.sh?
 */
if ( ! defined( 'BUILD_APP' ) ) { define( 'BUILD_APP', false );
}

/**
 * OPTIONAL
 * Any additional commands to run with gulp.
 */
if ( ! defined( 'BUILD_APP_OPTIONS' ) ) { define( 'BUILD_APP_OPTIONS', '' );
}

/**
 * The locations of the theme for building.
 */
if ( ! defined( 'BUILD_DIR' ) ) { define( 'BUILD_DIR', '~/apps/' );
}

/**
 * OPTIONAL
 * Email address to be notified on deployment failure.
 *
 * @var string A single email address, or comma separated list of email addresses
 *      e.g. 'someone@example.com' or 'someone@example.com, someone-else@example.com, ...'
 */
if ( ! defined( 'EMAIL_ON_ERROR' ) ) { define( 'EMAIL_ON_ERROR', false );
}

// ===========================================[ Configuration end ]===

// If there's authorization error, set the correct HTTP header.
if ( ! isset( $_GET['sat'] ) || $_GET['sat'] !== SECRET_ACCESS_TOKEN || SECRET_ACCESS_TOKEN === 'BetterChangeMeNowOrSufferTheConsequences' ) {
	header( $_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden', true, 403 );
}

// Validates the branch name that was pushed to, if we should listen for it or not
if ( ! is_valid_branch() ) {
	return;
}

ob_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="robots" content="noindex">
	<title>WDS Lab deploy script</title>
	<style>
body { padding: 0 1em; background: #222; color: #fff; }
h2, .error { color: #c33; }
.prompt { color: #6be234; }
.command { color: #729fcf; }
.output { color: #999; }
	</style>
</head>
<body>
<?php
if ( ! isset( $_GET['sat'] ) || $_GET['sat'] !== SECRET_ACCESS_TOKEN ) {
	header( $_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden', true, 403 );
	die( '<h2>ACCESS DENIED!</h2>' );
}
if ( SECRET_ACCESS_TOKEN === 'BetterChangeMeNowOrSufferTheConsequences' ) {
	header( $_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden', true, 403 );
	die( "<h2>You're suffering the consequences!<br>Change the SECRET_ACCESS_TOKEN from it's default value!</h2>" );
}
?>
<pre>

Checking the environment ...

Running as <b><?php echo trim( shell_exec( 'whoami' ) ); ?></b>.

<?php
// Check if the required programs are available
$requiredBinaries = array( 'git', 'rsync' );
if ( defined( 'BACKUP_DIR' ) && BACKUP_DIR !== false ) {
	$requiredBinaries[] = 'tar';
	if ( ! is_dir( BACKUP_DIR ) || ! is_writable( BACKUP_DIR ) ) {
		header( $_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500 );
		die( sprintf( '<div class="error">BACKUP_DIR `%s` does not exists or is not writeable.</div>', BACKUP_DIR ) );
	}
}
if ( defined( 'USE_COMPOSER' ) && USE_COMPOSER === true ) {
	$requiredBinaries[] = 'composer --no-ansi';
}
foreach ( $requiredBinaries as $command ) {
	$path = trim( shell_exec( 'which ' . $command ) );
	if ( $path == '' ) {
		header( $_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500 );
		die( sprintf( '<div class="error"><b>%s</b> not available. It needs to be installed on the server for this script to work.</div>', $command ) );
	} else {
		$version = explode( "\n", shell_exec( $command . ' --version' ) );
		printf('<b>%s</b> : %s' . "\n"
			, $path
			, $version[0]
		);
	}
}
?>

Environment OK.

Using configuration defined in <?php echo CONFIG_FILE . "\n"; ?>

Deploying <?php echo REMOTE_REPOSITORY; ?> <?php echo LAB_BRANCH . "\n"; ?>
to        <?php echo TARGET_DIR; ?> ...

<?php
// The commands
$commands = array();

// ========================================[ Pre-Deployment steps ]===

if ( ! is_dir( TMP_DIR ) ) {
	// Clone the repository into the TMP_DIR
	$commands[] = sprintf(
	// Some repos do not allow shallow clones, so we have to clone the entire repo :( - may just be a bitbucket thing.
	// 'git clone --depth=1 --branch %s %s %s'
		'git clone --branch %s %s %s'
		, LAB_BRANCH
		, REMOTE_REPOSITORY
		, TMP_DIR
	);

	if ( ! empty( get_environments() ) ) {
		foreach( get_environments() as $remote_name => $env ) {
			$commands[] = sprintf(
					'git --git-dir="%1$s.git" --work-tree="%1$s" remote add %2$s %3$s',
					TMP_DIR,
					$remote_name,
					$env['repo']
			);
		}
	}

} else {
	// TMP_DIR exists and hopefully already contains the correct remote origin
	// so we'll fetch the changes and reset the contents.
	$commands = reset_branch( $commands );
}


// Handle environmental pushes after the build.
if ( ! empty( get_environments() ) ) {
	foreach( get_environments() as $remote_name => $env ) {
		$env_branch = $env['listen_branch'];
		$remote_branch = $env['remote_branch'];

		// Uncomment the following lines if you want to restrict deploys
		// while listening to the specific branch.
		if ( get_branch_pushed() !== $env_branch ) {
			continue;
		}

		$commands[] = sprintf(
			'git --git-dir="%1$s.git" --work-tree="%1$s" checkout %2$s',
			TMP_DIR,
			$env_branch
		);

		// You must reset the branch to match the origin.
		$commands = reset_branch( $commands, $env_branch, $remote_name );

		// Run composer and all that stuffs
		$commands = build_app( $commands );

		// Add all files, including VERSION ( for obvious reasons )
		$commands[] = sprintf(
			'git --git-dir="%1$s.git" --work-tree="%1$s" add -A .',
			TMP_DIR
		);

		// Commit build changes, set message to something simple.
		$commands[] = sprintf(
			'git --git-dir="%1$s.git" --work-tree="%1$s" commit -m "%2$s"',
			TMP_DIR,
			"BUILD origin/$env_branch for $remote_name/$remote_branch"
		);

		// Push build changes to remote environment, using force-push, I know...
		$commands[] = sprintf(
			'git --git-dir="%s.git" --work-tree="%s" push %2$s +%3$s',
			TMP_DIR,
			$remote_name,
			$remote_branch
		);
	}
} else {
	$commands = build_app( $commands );
}

// ==================================================[ Deployment ]===

if ( LAB_BRANCH === get_branch_pushed() ) {
// Compile exclude parameters
	$exclude = '';
	foreach ( unserialize( EXCLUDE ) as $exc ) {
		$exclude .= ' --exclude=' . $exc;
	}
// Deployment command
	$commands[] = sprintf(
		'rsync -rltgoDzvO %s %s %s %s'
		, TMP_DIR
		, TARGET_DIR
		, ( DELETE_FILES ) ? '--delete-after' : ''
		, $exclude
	);
}

// =======================================[ Post-Deployment steps ]===

// Remove the TMP_DIR (depends on CLEAN_UP)
if ( CLEAN_UP ) {
	$commands['cleanup'] = sprintf(
		'rm -rf %s'
		, TMP_DIR
	);
}

// =======================================[ Run the command steps ]===
$output = '';
error_log( __FILE__ . '::' . __LINE__ );
foreach ( $commands as $command ) {
	// continue;

	set_time_limit( TIME_LIMIT ); // Reset the time limit for each command
	if ( file_exists( TMP_DIR ) && is_dir( TMP_DIR ) ) {
		chdir( TMP_DIR ); // Ensure that we're in the right directory
	}
	$tmp = array();
	exec( $command . ' 2>&1', $tmp, $return_code ); // Execute the command
	// Output the result
	printf('
<span class="prompt">$</span> <span class="command">%s</span>
<div class="output">%s</div>
'
		, htmlentities( trim( $command ) )
		, htmlentities( trim( implode( "\n", $tmp ) ) )
	);
	$output .= ob_get_contents();
	error_log( "Return code: " . $return_code );
	error_log( "Output: " . implode( "\n", $tmp ) );
	ob_flush(); // Try to output everything as it happens

	// Error handling and cleanup
	if ( $return_code !== 0 ) {
		header( $_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500 );
		printf('
<div class="error">
Error encountered!
Stopping the script to prevent possible data loss.
CHECK THE DATA IN YOUR TARGET DIR!
</div>
'
		);
		if ( CLEAN_UP ) {
			$tmp = shell_exec( $commands['cleanup'] );
			printf('


Cleaning up temporary files ...

<span class="prompt">$</span> <span class="command">%s</span>
<div class="output">%s</div>
'
				, htmlentities( trim( $commands['cleanup'] ) )
				, htmlentities( trim( $tmp ) )
			);
		}
		$error = sprintf(
			'Deployment error on %s using %s!'
			, $_SERVER['HTTP_HOST']
			, __FILE__
		);
		error_log( $error );
		if ( EMAIL_ON_ERROR ) {
			$output .= ob_get_contents();
			$headers = array();
			$headers[] = sprintf( 'From: WDS Lab deploy script <no-reply@%s>', $_SERVER['HTTP_HOST'] );
			$headers[] = sprintf( 'X-Mailer: PHP/%s', phpversion() );
			mail( EMAIL_ON_ERROR, $error, strip_tags( trim( $output ) ), implode( "\r\n", $headers ) );
		}
		break;
	}// End if().
}// End foreach().
?>

Done.
</pre>
</body>
</html>
