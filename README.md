## WDS Build Scripts

Using a combination of PHP and Bash; here are some scripts to help you grab code from Github or Beanstalk (via Git) and then run commands to create you website/app.

PHP forked from [https://github.com/markomarkovic/simple-php-git-deploy/](https://github.com/markomarkovic/simple-php-git-deploy/)

### Requirements

A server you manage (not shared hosting) with the following installed:

- Git
- Node
- NPM or Yarn
- Gulp
- Bower

### Getting Started

- Download and place these files in the root directory of your project.

For example:

```
- wp-content/
- - build.sh
- - deploy-config.php
- - deploy.php
- - ftp-sync.sh
- - trigger.sh
```

### Non-Jenkins Setup

- Open `deploy-config.php` and create a secret access token (this can be anything)
- Configure the rest `deploy-config.php` to meet your needs
- Open `build.sh` and configure to meet your needs
- Save and commit these files to your repository
- In your Github or Bitbucket project, configure a webhook to point at `https://yourproject.com/wp-content/deploy.php?sat=your-secret-access-token`
- There have been instances where the server user doesn't always have permission to run the script. If that happens, just add that user to the list of sudoers - `adduser <username> sudo`

When a commit is pushed to `master` (or whatever branch you configured) this will trigger the webhook - which will ping this URL and kick off the deployment + build.

#### Bonus

You could also kick-off a deployment, just by visiting the same webhook URL in a web browser. Then you can watch the deployment + build happen in real time!

### Jenkins Setup

For Jenkins, you'll notice there are three extra files.
* jenkins-compile.sh
* jenkins-config.sh
* jenkins-ftp-sync.sh

All jenkins files will need to be present in the root of your repository. Do not add these files to your `.gitignore`. These were built specifically for a Jenkins setup, though you are free to use them externally should you see the need.

#### Build setup ( Jenkins )
* Add a build-step, set it to `Execute Shell`
* First call the `jenkins-compile.sh` - no parameters needed.
```
#!/bin/bash

. "$WORKSPACE/jenkins-compile.sh"
```
* Add another build step for the FTP sync

```
#!/bin/bash

#Execute the FTP deploy syncing script.
 . "$WORKSPACE/ftp-sync.sh" -u "ftp-username" -p "ftp-password" -h "ftp-domain.com" -d "wp-content" --port "2222" -s "true"
```

* Finish setting up Jenkins how you see fit.

#### FTP Flags
```
 -u [--username]     The username for the FTP host.
 -p [--password]     The password for the FTP host.
 -h [--host]         The IP address for the remote FTP host.
 -d [--directory]    Where you will be pushing the files to.
 -s [--sftp]         Set to enable SFTP
 --port              The port to use for FTP.
```


### Contributing

Your issues and PRs are welcome! :sunglasses:
