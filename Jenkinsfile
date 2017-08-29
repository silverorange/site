pipeline {
    agent any
    stages {
        stage('Install Linter') {
            steps {
                sh '''
                    mv composer.json tmpComposer.json
                    if [[ -f composer.lock ]] ; then
                        mv composer.lock tmpComposer.lock
                    fi
                    rm -rf vendor/
                    composer require 'silverorange/coding-standard'
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
                    rm -rf composer.json composer.lock
                    mv tmpComposer.json composer.json
                    if [[ -f tmpComposer.lock ]]; then
                        mv tmpComposer.lock composer.lock
                    fi
                    sh 'composer install'
                '''
            }
        }
    }
}
