#!/bin/bash

#######################################################
#              DO NOT EDIT THIS FILE
#######################################################

showHelp() {
cat << EOF
Usage: ${0##*/} -u <USERNAME> -p <PASSWORD> -h <IP> -d <REMOTEDIRECTORY> [--sftp] [-p <PORT>]
Syncs a remote directory with recent changes to the current directory
the script is ran within.

	-u [--username]     The username for the FTP host.
	-p [--password]     The password for the FTP host.
	-h [--host]         The IP address for the remote FTP host.
	-d [--directory]    Where you will be pushing the files to.
	-s [--sftp]         Set to enable SFTP
	-p [--port]         The port to use for FTP.

EOF
}

while [[ $# -gt 1 ]]; do
	key="$1"
	case $key in
	    -u|--username)
	    ftp_user="$2"
	    shift # past argument
	    ;;
	    -p|--password)
	    ftp_password="$2"
	    shift # past argument
	    ;;
	    -h|--host)
	    ftp_host="$2"
	    shift # past argument
	    ;;
	    -d|--directory)
	    remote_dir="$2"
	    shift # past argument
	    ;;
	    --port)
	    # Must use --port, since -p is already taken above.
	    PORT="$2"
	    shift # past argument
	    ;;
	    -s|--sftp)
	    # TODO: There is a better way of handling args, right now we must set -s "true"
	    PREFIX="sftp"
	    shift # past argument
	    ;;
	    *)
	      # Skip this option, nothing special
	    ;;
	esac
	shift # past argument or value
done

if [ -z ${ftp_user+x} ]; then
	echo "ERROR: FTP Username not found."
	echo ""
	showHelp
	exit 1
fi

if [ -z ${ftp_password+x} ]; then
	echo "ERROR: FTP Password not found."
	echo ""
	showHelp
	exit 1
fi

if [ -z ${ftp_host+x} ]; then
	echo "ERROR: FTP Host/IP not found."
	echo ""
	showHelp
	exit 1
fi

if [ -z ${remote_dir+x} ]; then
	echo "ERROR: Remote directory not specified."
	echo ""
	showHelp
	exit 1
fi

# Set port default 21 if not specified in the flags.
if [ -z ${PORT+x} ]; then
	PORT="21"
fi

# Set Prefix FTP if not specified in the flags.
if [ -z ${PREFIX+x} ]; then
	PREFIX="ftp"
fi

# retrieve the absolute path of this script in a portable manner
BASE_DIR=$(cd $(dirname "$0") && pwd)
local_dir="$WORKSPACE"; # we consider here that the web site sources are sibling of this script

echo "============================================="
echo "Sending data to $ftp_user on $ftp_host via $PREFIX to $remote_dir"
echo "Base Directory: $WORKSPACE"
echo "============================================="

# use lftp to synchronize the source with the FTP server for only modified files.
FTPCOMMAND="
#debug;
set sftp:auto-confirm yes;
open -p ${PORT} ${PREFIX}://${ftp_user}:${ftp_password}@${ftp_host};
lcd ${local_dir};
cd ${remote_dir};
mirror --only-newer \
       --ignore-time \
       --reverse \
       --parallel=5 \
       --verbose \
       --exclude .git/ \
       --exclude .gitignore \
       --exclude-glob composer.* \
       --exclude-glob node_modules/ \
       --exclude-glob *.sh"

lftp -c "$FTPCOMMAND" || exit $?