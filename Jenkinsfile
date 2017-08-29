pipeline {
    agent any
    stages {
        stage('Install Linter') {
            steps {
                sh '''
                    rm -rf composer.json composer.lock vendor/
                    composer require 'silverorange/coding-standard'
                    ./vendor/bin/phpcs --config-set installed_paths vendor/silverorange/coding-standard/src
                '''
            }
        }

        stage('Lint Modified Files') {
            when {
                not {
                    branch 'master'
                }
            }
            steps {
                sh '''
                    master_sha=$(git rev-parse origin/master)
                    newest_sha=$(git rev-parse HEAD)
                    ./vendor/bin/phpcs \
                    --standard=SilverorangeTransitional \
                    --tab-width=4 \
                    --encoding=utf-8 \
                    --warning-severity=0 \
                    --extensions=php \
                    $(git diff --diff-filter=ACRM --name-only $master_sha...$newest_sha)
                '''
            }
        }

        stage('Lint Entire Project') {
            when {
                branch 'master'
            }
            steps {
                sh './vendor/bin/phpcs'
            }
        }

        stage('Install Composer Dependencies') {
            steps {
                sh '''
                    rm -rf vendor/ composer.json composer.lock
                    git checkout composer.json
                    git checkout composer.lock || true
                    composer install
                '''
            }
        }
    }
}
