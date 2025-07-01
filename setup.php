#!/usr/bin/env php
<?php

/**
 * WPLiteCore Development Setup Script
 * 
 * This script is for developers working ON the WPLiteCore library itself.
 * If you're just USING WPLiteCore in your project, you don't need this setup.
 * 
 * For end users: Just use WPLiteCore::create('your-api-url', 'your-key')
 */

echo "🚀 WPLiteCore Development Setup\n";
echo "===============================\n";
echo "ℹ️  This setup is for library developers and contributors.\n";
echo "ℹ️  If you're just using WPLiteCore in your project, you can skip this.\n\n";

$envFile = __DIR__ . '/.env';
$envExampleFile = __DIR__ . '/.env.example';

// Check if .env already exists
if (file_exists($envFile)) {
    echo "⚠️  .env file already exists.\n";
    echo "Do you want to overwrite it? (y/N): ";
    $handle = fopen("php://stdin", "r");
    $input = trim(fgets($handle));
    fclose($handle);
    
    if (strtolower($input) !== 'y' && strtolower($input) !== 'yes') {
        echo "✅ Setup cancelled. Existing .env file preserved.\n";
        exit(0);
    }
}

// Copy .env.example to .env
if (file_exists($envExampleFile)) {
    copy($envExampleFile, $envFile);
    echo "✅ Created .env file from template\n";
} else {
    // Create basic .env file
    $defaultEnv = <<<ENV
# WPLiteCore Environment Configuration

# API Configuration
WPLITE_API_URL=https://api.example.com/v2
WPLITE_HASH_KEY=your-secret-hash-key-here
WPLITE_SITE_URL=https://example.com

# Optional: Additional API Keys
WIREFRONT_API_KEY=your-api-key-here

# Debug Mode (true/false)
WPLITE_DEBUG=false

# Test Configuration
WPLITE_TEST_POST_ID=32
WPLITE_TEST_MEDIA_ID=41
WPLITE_TEST_CATEGORY_ID=1
WPLITE_TEST_USER_ID=1
WPLITE_TEST_TAG_ID=1
ENV;
    
    file_put_contents($envFile, $defaultEnv);
    echo "✅ Created basic .env file\n";
}

echo "\n📝 Next Steps for Library Development:\n";
echo "1. Edit the .env file with your test API credentials\n";
echo "2. Set your WPLITE_HASH_KEY (required for testing)\n";
echo "3. Update WPLITE_API_URL to your test endpoint\n";
echo "4. Run 'composer install' if you haven't already\n";
echo "5. Run 'vendor/bin/phpunit' to test your configuration\n\n";

echo "📚 For End Users (Using WPLiteCore in Projects):\n";
echo "You don't need this .env setup. Just use:\n";
echo "\$wpLite = WPLiteCore::create('your-api-url', 'your-key');\n\n";

echo "🔒 Security Note:\n";
echo "Never commit your .env file to version control!\n";
echo "The .env file is already included in .gitignore\n\n";

echo "🎉 Setup complete! Happy coding with WPLiteCore!\n";
