pipeline {
    agent any
    stages {
        stage('Install Composer Dependencies') {
            steps {
                sh 'rm -rf composer.lock vendor/'
                sh 'composer install'
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
                        body_text=$(curl -u sogitbot:$auth_token $query_url | jq .body)
                        if line_of_links=$(echo $body_text | grep -o \'[Rr]equires.*\\r\'); then
                            echo 'Requirements found'
                            echo $line_of links \
                            | grep -o $github_links \'github.com\\/silverorange\\/\\w*\\/pull\\/[0-9]*' \
                            | while read -r line ; do
                                echo "Processing $line"
                            done
                        fi
                    '''
                }
            }
        }
    }
}
