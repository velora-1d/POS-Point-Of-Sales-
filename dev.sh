#!/bin/bash

# Load NVM (Node Version Manager)
export NVM_DIR="$HOME/.nvm"
if [ -s "$NVM_DIR/nvm.sh" ]; then
    . "$NVM_DIR/nvm.sh"
else
    echo "Warning: NVM not found at $NVM_DIR/nvm.sh. Using system node/npm."
fi

# Run the development command
composer dev
