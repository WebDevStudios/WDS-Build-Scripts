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

When a commit is pushed to `master` (or whatever branch you configured) this will trigger the webhook - which will ping this URL and kick off the deployment + build.

#### Bonus

You could also kick-off a deployment, just by visiting the same webhook URL in a web browser. Then you can watch the deployment + build happen in real time!

### Jenkins Setup

For Jenkins, you'll notice there are two extra files, `trigger.sh` and `ftp-sync.sh` - these were built specifically for a Jenkins setup, though you are free to use them externally should you see the need.

- Setup your normal Jenkins pre-build steps, or copy an existing setup.
- Scroll down to 'Build' and click 'Add build step'
- Select 'Execute Shell' twice, to add two build steps.
- Setup your build script first from `trigger.sh`

```
#!/bin/bash

# Execute our script
. "$WORKSPACE/trigger.sh"
```

- Setup the ftp sync after the build is done.

```
#!/bin/bash

#Execute the FTP deploy syncing script.
 . "$WORKSPACE/ftp-sync.sh" -u "username" -p "pass" -d "var/www/mysite" -h "127.0.0.7" --port "22" --sftp "true"
```

- Finish setting up Jenkins how you see fit.


### Contributing

Your issues and PRs are welcome! :sunglasses: