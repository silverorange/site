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
                    files=$(git diff-tree --diff-filter=ACRM --no-commit-id --name-only -r HEAD)
                    if [ -n "$files" ]; then ./vendor/bin/php-cs-fixer check \
                    --config ./.php-cs-fixer.php \
                    $files; fi
                '''
            }
        }

        stage('Lint Entire Project') {
            when {
                branch 'master'
            }
            steps {
                sh 'composer run lint'
            }
        }
    }
}
