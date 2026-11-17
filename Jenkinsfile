pipeline {
    agent any
    options {
        disableConcurrentBuilds()
        timestamps()
    }
    stages {
        stage('Build production assets') {
            steps {
                // Requires compatible Node/npm, PHP 8.2+ and Composer on the agent.
                sh 'npm ci && npm run build'
            }
        }
        stage('Stage unsigned Nextcloud release') {
            steps {
                sh 'bash scripts/stage-release.sh'
            }
        }
        // A trusted signing job can run occ integrity:sign-app against the
        // staged app. Certificates and private keys must never enter this repo.
        stage('Archive unsigned release') {
            steps {
                archiveArtifacts artifacts: 'build/*.tar.gz', fingerprint: true
            }
        }
    }
}
