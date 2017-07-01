#!bin/bash

# Get the build directory
build_dir=$1

# No build directory? Bail!
if [ $# -eq 0 ];
  then
    echo "You must pass in a build directory!"
    exit 1
fi

# Go to the build directory...
cd $build_dir

# NODE STUFF
echo "Looking for package.json..."
if [ ! -f package.json ];
  then
    echo "Missing package.json"
  else
    echo "Installing node modules..."
    if [ ! -d "node_modules" ];
      then
       npm install
      else
       echo "node modules already installed"
    fi
fi

# BOWER STUFF
echo "Looking for bower.json..."
if [ ! -f bower.json ];
  then
    echo "Missing bower.json"
  else
    echo "Installing bower componenents..."
    if [ ! -d bower_components ]; then
       bower install
    else
       echo "bower componenents already installed"
    fi
fi

# GULP STUFF
echo "Looking for Gulpfile.js..."
if [ ! -f Gulpfile.js ];
  then
    echo "Missing gulpfile.js"
  else
    echo "Building your app!"
    gulp
fi
