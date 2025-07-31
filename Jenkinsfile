pipeline {
    agent any
    stages {
        stage('Install Composer Dependencies') {
            steps {
                sh 'rm -rf composer.lock vendor/'
                sh 'composer install'
            }
        }

        stage('Check PHP Coding Style') {
            steps {
                sh 'composer run phpcs:ci'
            }
        }

        stage('Check PHP Static Analysis') {
            steps {
                sh 'composer run phpstan:ci'
            }
        }
    }
}
