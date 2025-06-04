pipeline {
    agent any
    stages {
        stage('Install Composer Dependencies') {
            steps {
                sh 'rm -rf composer.lock vendor/'
                sh 'composer install'
            }
        }

        stage('Check Code Style for Modified Files') {
            when {
                not {
                    branch 'master'
                }
            }
            steps {
                sh '''
                    files=$(git diff-tree --diff-filter=ACRM --no-commit-id --name-only -r HEAD)
                    if [ -n "$files" ]; then composer run phpcs:ci \
                    $files; fi
                '''
            }
        }

        stage('Check Code Style for Entire Project') {
            when {
                branch 'master'
            }
            steps {
                sh 'composer run phpcs:ci'
            }
        }
    }
}
