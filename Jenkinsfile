pipeline {
    agent any
    options {
        disableConcurrentBuilds()
        timestamps()
    }
    stages {
        stage('Verify handoff documentation') {
            steps {
                sh 'node scripts/check-sovereign-docs.cjs'
                sh 'node --test scripts/check-sovereign-docs.test.cjs'
            }
        }
        stage('Build production assets') {
            steps {
                // Requires compatible Node/npm, PHP 8.2+ and Composer on the agent.
                sh 'npm ci && npm run build'
            }
        }
        stage('Stage unsigned Nextcloud release') {
            steps {
                // No rsync/workspace copy: stage-release.sh allowlists app paths;
                // the package guard rejects generated-artifacts/ inside tarballs.
                sh 'bash scripts/check-package-contents.test.sh'
                sh 'bash scripts/stage-release.sh'
            }
        }
        // A trusted signing job can run occ integrity:sign-app against the
        // staged app. Certificates and private keys must never enter this repo.
        stage('Archive unsigned release') {
            steps {
                archiveArtifacts artifacts: 'build/*.tar.gz', excludes: '**/generated-artifacts/**', fingerprint: true
            }
        }
    }
}
