#!bin/bash

# Get the main directory of the script.
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

# Add files to this list in order for them to be built.
# All files should be on a new line, and do not use a comma
# to separate the items, this isn't PHP.
BUILD_FILES=(
	"themes/yourthemename"
)

function message {
	echo "============================================="
	echo "$1"
	echo "============================================="
}

message "Build from $DIR";

for i in "${BUILD_FILES[@]}"; do :

	# Build the app directories
	message "Executing build for $DIR/$i"
	sh "$DIR/build.sh" "$DIR/$i"

done