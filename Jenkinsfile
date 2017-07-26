pipeline {
    agent any
    stages {
        stage('Install Composer Dependencies') {
            steps {
                sh 'rm -rf composer.lock vendor/'
                sh 'composer install'
            }
        }

        stage('Lint') {
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

        stage('Test') {
            steps {
                sh 'echo $CHANGE_ID'
                sh 'echo $BRANCH_NAME'
                sh 'echo $JOB_NAME'
                sh 'echo $JOB_BASE_NAME'
                sh 'echo $CHANGE_TARGET'
                withCredentials([string(credentialsId: '2c149a6f-e5fa-41a0-bb32-1fb23595de77', variable: 'auth_token')]) {
                    sh '''
                        echo $auth_token
                        var=$(echo \'silverorange/site/PR-231\' | sed -e \'s/PR-/pulls\\//g\')
                        query_url=\'https://api.github.com/repos/\'$var
                        curl -u sogitbot:$auth_token $query_url | jq .body | \
                        grep -o \'[Rr]equires.*\\r\'
                    '''
                }
            }
        }
    }
}
