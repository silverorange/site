pipeline {
    agent any
    stages {
        stage('Reset Build Environment') {
            steps {
                sh 'rm -rf composer.lock vendor/ jenkins-scripts/'
                sh 'git checkout composer.json'
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
                    composer update 'silverorange/coding-standard'
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

        stage('Test') {
            steps {
                withCredentials([string(credentialsId: '2c149a6f-e5fa-41a0-bb32-1fb23595de77', variable: 'auth_token')]) {
                    sh '''
                       api_url=$(echo $JOB_NAME | sed -e \'s/PR-/pulls\\//g\')
                       git clone git@github.com:Qcode/jenkins-scripts.git
                       npm install jenkins-scripts/
                       node jenkins-scripts/modifyComposer.js $auth_token $api_url
                       composer update
                    '''
                }
            }
        }
    }
}
