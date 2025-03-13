#! /usr/bin/env bash

# Current existing ssh keys, and ssh configuration, go into ~/.ssh

# The permissions of the directory and files within are important
# The following files cannot allow group or other access:
# ~/.ssh/{config,identity,id_dsa,id_rsa,...}
# You could allow read access to .pub files, although this is unnecessary
# For otherse to read .pub files, ~/.ssh/ must allow others to execute

# See permissions
ls -ld ~/.ssh
ls -la ~/.ssh/*

# Set permissions to allow only owner access
chmod 700 ~/.ssh
chmod 600 ~/.ssh/*

# Generate a new identity key pair
# Typically (if you don't have an ssh key) accept the default filename
# Enter your own password when prompted, this password is for the ssh key
ssh-keygen

# See the new files, verify their permissions
ls -la ~/.ssh/*

# This key can be added, e.g., to Github to allow ssh cloning/pushing/etc.
# Paste the contents of your public key into your Github account

cat ~/.ssh/id_rsa.pub
